<?php

/**
 * Sync database inventory to amazon.
 *
 * This runs one tick of that process, which requires many ticks
 * over a period of time in order to complete.
 *
 * Be mindful of amazon throttling limits. Running every 15-30 minutes
 * seems to be ok.
 *
 * Class MWS_Inventory_Tick
 */
Class MWS_Inventory_Tick {

	/**
	 * @var DB_Amazon_Process|null
	 */
	public $db_process_before;

	/**
	 * @var DB_Amazon_Process|null
	 */
	public $db_process;

	/**
	 * @var MWS_Submit_Inventory_Feed|null
	 */
	public $feed;

	/**
	 * log what happened or didnt for debugging.
	 *
	 * update: might put this into a log file somewhere each time we run this.
	 *
	 * @var array
	 */
	public $debug = array();

	/**
	 * MWS_LOCALE_CA|MWS_LOCALE_US
	 *
	 * @var string
	 */
	public $mws_locale;

	public $app_locale;

	public function __construct( $mws_locale ) {

		assert_mws_locale_valid( $mws_locale );
		$this->mws_locale = $mws_locale;

		if ( $this->mws_locale === MWS_LOCALE_US ) {
		    $this->app_locale = APP_LOCALE_US;
        } else if ( $this->mws_locale === MWS_LOCALE_CA ) {
		    $this->app_locale = APP_LOCALE_CANADA;
        }

	}

	/**
	 *
	 */
	public function run() {

		$this->debug[] = 'run: ' . time();

		$ex = mws_get_process_awaiting_report( $this->mws_locale );

		if ( $ex ) {
			$this->debug[]           = 'found_existing';
			$this->db_process_before = clone $ex;
			$this->db_process        = $ex;
			$this->continue_process();

			return;
		}

		$interval = MWS_Inventory_Intervals::get_current_interval_as_timestamps();

		if ( $interval && ! MWS_Inventory_Intervals::get_processes_starting_in_interval( $interval, $this->mws_locale ) ) {

			// this process should be $ex_process the next time we call mws_inventory_tick()
			$new = mws_init_inventory_process( $this->mws_locale );

			// if we made a new one just exit basically. the next time we call run() it should be $ex.
			if ( $new ) {
				$this->debug[]           = 'created_new';
				$this->db_process_before = null;
				$this->db_process        = $new;

				return;
			} else {
				// shouldn't get to here...
				log_data( 'Failed to init inventory process', 'init-inventory-error' );
			}
		}

		$this->debug[] = 'existing not found, but too early to create a new process.';
	}

	/**
	 * Attempt to continue a process looking at only the current state that the process is in.
	 */
	public function continue_process() {

		assert( $this->db_process->get( 'type' ) === MWS_INVENTORY_TYPE_INVENTORY );
		assert( $this->db_process->get( 'process_locale' ) == $this->mws_locale );

		// don't continue completed processes
		if ( $this->db_process->get( 'complete' ) ) {
			return;
		}

		switch ( $this->db_process->get( 'status' ) ) {
			case MWS_INVENTORY_STATUS_AWAITING_REPORT:
				$this->continue_process_awaiting_report();
				break;
			default:
				$this->debug[] = 'not_valid_status';
		}
	}

	/**
	 * Check to see if the report is complete (which has all part numbers),
	 * then if it is, get corresponding product stock level in database and send feed
	 * to amazon, otherwise, do nothing until next time.
	 */
	private function continue_process_awaiting_report() {

		assert( $this->db_process->get( 'process_locale' ) === $this->mws_locale );

		$this->debug[] = 'continue_process_awaiting_report';
		assert( $this->db_process->get( 'status' ) == MWS_INVENTORY_STATUS_AWAITING_REPORT );

		$report_id = $this->db_process->get( 'report_id' );
		assert( $report_id );

		if ( $this->db_process->get( 'process_complete' ) ) {
			$this->debug[] = 'process_already_complete';
			return;
		}

		$this->db_process->update_database_and_re_sync( array(
			'process_time_last' => gp_time(),
			'process_steps' => (int) $this->db_process->get( 'steps' ) + 1,
		) );

		$amazon = Amazon_MWS::get_instance( $this->mws_locale );
		$report_status   = $amazon->client->GetReportRequestStatus( $report_id );

		$this->debug['GetReportRequestStatus'] = $report_status;

		if ( isset( $report_status[ 'ReportProcessingStatus' ] ) && $report_status[ 'ReportProcessingStatus' ] === '_DONE_' ) {

            $this->debug[] = 'report_processing_done';

            // note: this accounts for upwards of 80mb of mem, depending on how big the report is of course.
            // overall, uses a decent chunk of memory for what it does.
            $report = $amazon->client->GetReport( $report_id );

            if ( ! $report ) {

                $this->db_process->append_to_process_mutable_array( [
                    'event' => 'empty_report',
                    'msg' => 'GetReportRequestStatus said the report was done, but when requesting the report, it was empty. It either failed or there are no products.',
                    'time' => time(),
                ]);

                return;
            }

            // do this before calling $profile_db
            start_time_tracking( "mws_inventory" );

            // stores some info in database.
            // keeps track of time, mem usage, etc.
            // can be helpful for processes that fail mid-way
            $profile_db = function( $event, array $more = [] ) {

                $bytes = function( $b ) {
                    return number_format( (float) $b, 0, '.', ',' );
                };

                $this->db_process->append_to_process_mutable_array( array_merge( [
                    'event' => $event,
                    'time' => time(),
                    'seconds' => end_time_tracking('mws_inventory'),
                    'mem' => [ $bytes( memory_get_usage() ), $bytes( memory_get_usage( true ) ) ],
                    'peak_mem' => [ $bytes( memory_get_peak_usage() ), $bytes( memory_get_peak_usage( true ) ) ],
                ], $more ) );
            };

            $profile_db( "report_ready: building inventory feed", [
                "DOING_CRON" => DOING_CRON ? "1" : "0",
            ] );

            // very expensive (depending on locale)
            list( $acc_stock, $acc_details, $acc_details_extended ) = MWS_Submit_Inventory_Feed::get_accessories_stock( $this->app_locale );

//            $acc_stock = [];
//            $acc_details = ["test"];
//            $acc_details_extended = ["test"];

            $profile_db( "after_get_accessories_stock" );

            $this->db_process->append_to_process_mutable_array([
                'type' => 'acc_details',
                'value' => $acc_details,
            ]);

            // fairly expensive
            list( $product_update_array, $extra ) = MWS_Submit_Inventory_Feed::build_product_update_array( $report, $acc_stock, $this->mws_locale );

            $profile_db( "after_build_product_update_array" );

            // log extended product information before sending (this is a lot of data).
            Amazon_MWS::log_data( 'feeds-' . date( 'Y' ), implode( '-', [
                'mws-feed',
                date( 'YmdHis'),
                $this->mws_locale,
                $this->db_process->get_primary_key_value(),
                'products.txt'
            ] ), [
                'can_submit_real_feeds' => get_var_dump( APP_CAN_UPDATE_AMAZON_MWS ),
                'product_counts' => $extra['aggregates'],
                'acc_details' => $acc_details,
                'acc_details_extended' => $acc_details_extended,
                'products_not_found' => $extra['products_not_found'],
                'product_update_array' => $product_update_array,
            ] );

            // logs a very large file and may actually increase memory usage, so, profiling this also.
            $profile_db( "after_log_product_update_array" );

            $this->db_process->append_to_process_mutable_array([
                'type' => 'aggregates',
                'value' => $extra['aggregates']
            ]);

            // Send, or mock send
            $result = MWS_Submit_Inventory_Feed::send( $product_update_array, $this->mws_locale, false );

            $profile_db( "after_send_feed" );

            // feed submission result -> file
            Amazon_MWS::log_data( 'feeds-' . date( 'Y' ), implode( '-', [
                'mws-feed',
                date( 'YmdHis'),
                $this->mws_locale,
                $this->db_process->get_primary_key_value(),
                'response.txt'
            ] ), $result );

            // feed submission result -> database
            $this->db_process->update_database_and_re_sync( array(
                'feed_submission_result_1' => gp_db_encode( $result, 'json' ),
            ) );

            // ie. data was actually sent to Amazon
            if ( $result['mock_send'] === false ) {

                // this means success, basically.
                if ( @$result['FeedSubmissionId'] ){

                    // mark status as feed submitting, but do not mark it as complete,
                    // its only complete when we check on the status of this feed submission later.
                    $this->db_process->update_database_and_re_sync( array(
                        'process_status' => MWS_INVENTORY_STATUS_FEED_SUBMITTED,
                    ) );

                    $profile_db( "end: successful." );

                } else {

                    // feed failed for some unknown reason (amazon returns a result that we didn't expect)
                    // so mark status with error, and also process as complete so that we don't try to send
                    // the same feed again on the next inventory tick.
                    $this->db_process->update_database_and_re_sync( array(
                        'process_complete' => 1,
                        'process_status' => MWS_INVENTORY_STATUS_FEED_SUBMITTED_ERROR,
                    ) );
                }

            } else {

                // mark the process complete in both ways (complete and status).
                // this helps us test code in dev env without sending to amazon.
                $this->db_process->update_database_and_re_sync( array(
                    'process_complete' => 1,
                    'process_status' => MWS_INVENTORY_STATUS_FEED_COMPLETE,
                ) );
            }

		} else {

            $t1 = $this->db_process->get( 'time_start' );
            $t2 = $this->db_process->get( 'time_last' );

            // unlikely that this will occur
            if ( abs( $t2 - $t1 ) >= MWS_MAX_AWAITING_REPORT_TIME ) {

                $this->debug[] = 'exceeded_max_waiting_time';

                $this->db_process->update_database_and_re_sync( array(
                    'process_complete' => 1,
                    'process_status' => 'awaiting_report_exceeded_max_time',
                ) );

            } else {

                // sometimes this happens once or twice before the report is ready, then
                // we submit the feed.
                $this->db_process->update_process_mutable_array( function( $arr ){

                    $arr[] = [
                        'event' => 'report_not_ready',
                        'time' => time(),
                    ];

                    return $arr;
                });
            }
		}
	}
}