<?php

/**
 * Get a process that is awaiting a report, so that we can check
 * if the report is complete, and if it is, submit inventory data.
 *
 * @param $mws_locale
 *
 * @return array|null|DB_Amazon_Process
 */
function mws_get_process_awaiting_report( $mws_locale ) {

	assert_mws_locale_valid( $mws_locale );

	$db = get_database_instance();
	$p  = [];
	$q  = '';
	$q  .= 'SELECT * ';
	$q  .= 'FROM ' . DB_amazon_processes . ' ';
	$q  .= 'WHERE 1 = 1 ';
	$q  .= 'AND process_type = "' . MWS_INVENTORY_TYPE_INVENTORY . '" ';
	$q  .= 'AND process_status = "' . MWS_INVENTORY_STATUS_AWAITING_REPORT . '" ';
	$q  .= 'AND process_complete = 0 ';
	$q  .= 'AND process_locale = :locale ';
	$p[] = [ 'locale', $mws_locale, '%s' ];
	$q  .= '';
	$q  .= 'ORDER BY process_id DESC ';
	$q  .= 'LIMIT 0, 1 ';
	$q  .= ';';

	$results = $db->get_results( $q, $p );
	$ret = $results ? DB_Amazon_Process::create_instance_or_null( $results[0] ) : null;
	return $ret;
}

/**
 * If an existing process exists then you should not call this function.
 *
 * In other words, check mws_get_process_awaiting_report() first.
 *
 * @see MWS_Inventory_Tick
 * @see mws_get_process_awaiting_report()
 */
function mws_init_inventory_process( $mws_locale ) {

	assert_mws_locale_valid( $mws_locale );

	$db     = get_database_instance();
	$amazon = Amazon_MWS::get_instance( $mws_locale );

	try {
		$report_id = $amazon->client->RequestReport( MWS_REPORT_GET_MERCHANT_LISTINGS_ALL_DATA );
		$error     = $report_id ? false : 'no_report_id';
	} catch ( Exception $e ) {
		$error     = $e->getMessage();
		$report_id = false;
	}

	$status = $error ? MWS_INVENTORY_STATUS_REQUEST_REPORT_ERROR : MWS_INVENTORY_STATUS_AWAITING_REPORT;
	$time   = gp_time();

	$row = array(
		'process_locale' => $mws_locale,
		'process_report_id' => $report_id,
		'process_type' => 'inventory',
		'process_complete' => 0,
		'process_status' => $status,
		'process_time_start' => $time,
		'process_time_last' => $time,
		'process_steps' => 1,
		'process_mutable_array' => gp_db_encode( [
			'date_inserted' => date( get_database_date_format() ),
		] ),
	);

	// insert row into database..
	$insert_id = $db->insert( DB_amazon_processes, $row, array(
		'process_steps' => '%d',
	) );

	if ( ! $insert_id ) {
		log_data( $row, 'mws_inventory_init_no_insert_id' );

		return null;
	}

	return DB_Amazon_Process::create_instance_via_primary_key( $insert_id );
}

/**
 * Checks for updates from amazon on a previously submitted inventory feed,
 * and updates the database with the results. This is more or less the last
 * step of a 4ish step process. Feed submission results take time to process,
 * so we run this at intervals, and not too often, to avoid throttling.
 */
function mws_check_for_feed_submission_updates( $mws_locale ) {

	assert_mws_locale_valid( $mws_locale );

	$db = get_database_instance();
	$p  = [];
	$q  = '';
	$q  .= 'SELECT * ';
	$q  .= 'FROM amazon_processes ';
	$q  .= 'WHERE 1 = 1 ';
	$q  .= 'AND process_status = "' . MWS_INVENTORY_STATUS_FEED_SUBMITTED . '" ';

	$q  .= 'AND process_locale = :locale ';
	$p[] = [ 'locale', $mws_locale, '%s' ];

	$q  .= 'ORDER BY process_id DESC ';

	// when things work we should only have 1. But process more
	// than 1, up to a small limit, in case things are not working.
	$q .= 'LIMIT 0, 20 ';
	$q .= ';';

	$results = $db->get_results( $q, $p );

	$amazon = new Amazon_MWS( $mws_locale );

	if ( $results ) {
		foreach ( $results as $result ) {

			$process = DB_Amazon_Process::create_instance_or_null( $result );

			$process->update_database_and_re_sync( array(
				'process_steps' => (int) $process->get( 'steps' ) + 1,
			), array(
				'process_steps' => '%d',
			));

			// array stored from when we submitted inventory, which holds the ID we need.
			$feed_submission_result_1 = $process->get_feed_submission_result_1();

			$FeedSubmissionId = gp_if_set( $feed_submission_result_1, 'FeedSubmissionId' );
			//			$FeedType = gp_if_set( $feed_submission_result_1, 'FeedType' );
			//			$SubmittedDate = gp_if_set( $feed_submission_result_1, 'SubmittedDate' );
			//			$FeedProcessingStatus = gp_if_set( $feed_submission_result_1, 'FeedProcessingStatus' );

			// no reason to expect this to happen
			if ( ! $FeedSubmissionId ) {
				$process->update_database_and_re_sync( array(
					'process_status' => 'feed_submission_id_error',
					'process_complete' => 1,
				), array(
					'process_complete' => '%d',
				) );

				continue;
			}

			try {
				$report = $amazon->client->GetFeedSubmissionResult( $FeedSubmissionId );
			} catch ( Exception $e ) {
				$report = false;
				log_data( [
					'error' => 'Exception: GetFeedSubmissionResult',
					'message' => $e->getMessage()
				], 'mws_check_for_feed_submission_updates' );
			}

			if ( $report && is_array( $report ) ) {
				$process->update_database_and_re_sync( array(
					'process_status' => MWS_INVENTORY_STATUS_FEED_COMPLETE,
					'process_complete' => 1,
					'feed_submission_result_2' => gp_db_encode( $report, 'json' ),
				), array(
					'process_complete' => '%d',
				) );
			}
		}
	}
}

/**
 * Generate an array index representing the current timestamp
 * such that its not already in the array passed in.
 *
 * @param $arr
 * @return int|string
 */
function get_array_timestamp_index( array $arr ) {

	$time = gp_time();
	$key  = $time;
	$c    = 0;

	while ( array_key_exists( $key, $arr ) ) {
		$c ++;
		$key = $time . '_' . $c;
	}

	return $key;
}
