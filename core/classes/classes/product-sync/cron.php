<?php

namespace PS\Cron;
use Product_Sync as Ps;
use function _\sortBy;

/**
 * @return array
 */
function get_cron_state(){

    $str = cw_get_option( 'sync_state', '{}' );
    $ret = json_decode( $str, true );

    // for first time (or if value doesn't exist in options table)
    if ( ! isset( $ret['syncs'] ) ) {
        $ret['syncs'] = [];
    }

    return $ret;
}

/**
 * @param $state
 */
function set_cron_state( $state ) {
    cw_set_option( 'sync_state', json_encode( $state ) );
}

/**
 * We'll put this into state and in the filename of the logged file.
 */
function get_date_formatted(){
    return date( 'Ymd-His');
}

/**
 * @param $delete_old_files
 * @return void
 */
function init_cron_state( $delete_old_files = true ){

    // make more space in certain directories.
    if ( $delete_old_files ) {
        \PS\CleanupFiles\delete_old_sync_request_files();
        \PS\CleanupFiles\delete_old_price_update_files();
        \PS\CleanupFiles\delete_old_inventory_files();
    }

    $init_date = get_date_formatted();

    // associative array
    $syncs = Ps::reduce( Ps::get_instances(), function( $sync ) {

        /** @var Ps $sync */
        if ( ! $sync::CRON_FETCH ) {
            return false;
        }

        return [
            'done' => false,
            // these basically never change and are stored in the key, but put them
            // in anyways.
            'info' => implode( ", ", [ $sync::SUPPLIER, $sync::LOCALE, $sync::TYPE ] )
        ];

    }, true );

    $process = [
        'init_date' => $init_date,
        'done' => false,
        'done_at' => '',
        'syncs' => $syncs,
    ];

    cw_set_option( 'sync_state', json_encode( $process ) );
}

/**
 * Probably done when all syncs have been processed and
 * we're about to start a new process.
 */
function log_cron_state(){

    $state = get_cron_state();

    $date_prev = $state['init_date'] ? $state['init_date'] : 'no_prev_init_date';

    $filename = implode('_', [ $date_prev, uniqid() ] );

    $dir = LOG_DIR . '/sync-state';
    @mkdir($dir, 0755, true );

    file_put_contents( $dir . '/' . $filename, json_encode( $state, JSON_PRETTY_PRINT ) );
}

/**
 * @param $state
 * @return \Product_Sync|null
 */
function get_next_sync( $state ) {

    foreach ( $state['syncs'] as $key => $sync_result ) {

        if ( ! $sync_result['done'] ) {

            /** @var Ps $next_sync */
            $next_sync = @Ps::get_instances()[$key];

            // make sure the sync is still returned from get_instances
            if ( $next_sync ) {
                return $next_sync;
            }
        }
    }

    return null;
}

/**
 * We'll call this on a cron job probably every minute or so, and about
 * 20-30 times (enough to cover all syncs by doing one at a time, even if we
 * add syncs in the future).
 *
 * It checks options.sync_state, does the next sync (fetching it, and possibly
 * accepting all prices changes), then updates options.sync_state. Does one
 * sync at a time, since they take so much time/memory.
 *
 * @throws \Exception
 */
function cron_state_do_next(){

    $state = get_cron_state();

    // no-op
    if ( @$state['done'] ) {
        return;
    }

    $next_sync = get_next_sync( $state );

    // cron fetch was true when we added the sync, but might
    // no longer be. Mark this sync done and on the next tick
    // we'll check for the next sync.
    if ( ! $next_sync::CRON_FETCH ) {

        $state['syncs'][$next_sync::KEY]['done'] = true;
        $state['syncs'][$next_sync::KEY]['result'] = "cron_fetch_false";

        set_cron_state( $state );
        return;
    }

    // mark it done right away, before attempting. If we run into time/memory issues
    // we don't want to attempt it again next time.
    $state['syncs'][$next_sync::KEY]['done'] = true;
    $state['syncs'][$next_sync::KEY]['start_time'] = get_date_formatted();

    set_cron_state( $state );

    /** @var \DB_Sync_Request $request */

    start_time_tracking( 'sync_next' );

    // make sure we have a supplier based price rule before accepting prices,
    // otherwise, we'll most likely mark all products not sold.
    list( $sup_rules, $brand_rules, $model_rules ) =
        \Product_Sync_Pricing_UI::get_supplier_price_rules( $next_sync::TYPE, $next_sync::LOCALE, $next_sync::SUPPLIER, true );

    $accept_prices = count( $sup_rules ) > 0 && $next_sync::CRON_PRICES;

    // may or may not sync all prices
    list( $request, $fetch ) = $next_sync->create_sync_request( $accept_prices );

    $state['syncs'][$next_sync::KEY]['result'] = "created_sync_request";
    $state['syncs'][$next_sync::KEY]['req_id'] = $request->get_primary_key_value();
    $state['syncs'][$next_sync::KEY]['accept_prices'] = $next_sync::CRON_PRICES;

    $state['syncs'][$next_sync::KEY]['count_all'] = $request->get( 'count_all' );
    $state['syncs'][$next_sync::KEY]['count_valid'] = $request->get( 'count_valid' );
    $state['syncs'][$next_sync::KEY]['count_changes'] = $request->get( 'count_changes' );

    $state['syncs'][$next_sync::KEY]['time_taken'] = @number_format( end_time_tracking( 'sync_next' ), 5, '.', ',' );
    $state['syncs'][$next_sync::KEY]['peak_mem0'] = @number_format( memory_get_peak_usage( false ), 0, '.', ',' );
    $state['syncs'][$next_sync::KEY]['peak_mem1'] = @number_format( memory_get_peak_usage( true ), 0, '.', ',' );

    // $state['syncs'][$next_sync::KEY]['price_rules'] = \Product_Sync_Pricing_UI::get_supplier_price_rules( $next_sync::TYPE, $next_sync::LOCALE, $next_sync::SUPPLIER, false );
    // $state['syncs'][$next_sync::KEY]['time_mem'] = \Product_Sync::time_mem_summary( $next_sync->tracker );

    set_cron_state( $state );

    // now check if we're done
    $new_state = get_cron_state();
    $next_sync_2 = get_next_sync( $new_state );

    // we want to call this only once for each process (since it logs a file)..
    // so we say that "done" is true, and we won't get to here again.
    if ( ! $next_sync_2 ) {

        $new_state['done'] = true;
        $new_state['done_at'] = get_date_formatted();
        set_cron_state( $new_state );

        // after setting the state
        log_cron_state();
    }
}

/**
 * if $return[0] is empty, we probably don't send the email at all,
 * even though $return[1] is a non-empty string.
 *
 * @return array - items requiring changes, and email content
 */
function get_email_content(){

    $format = 'D M d Y g:i a';

    $syncs = Ps::get_instances();

    $syncs = sortBy( $syncs, function( $sync ) {
        // shows tires/ca first (ascending order)
        return [ $sync::SUPPLIER, $sync::TYPE === 'rims', $sync::LOCALE === 'US' ];
    });

    $items = Ps::reduce( $syncs, function( $sync ) {

        /** @var \Product_Sync $sync */

        if ( ! $sync::CRON_EMAIL ) {
            return false;
        }

        // the most recent sync request for this sync
        $q = "
        select * from sync_request where sync_key = :sync_key        
        order by inserted_at DESC LIMIT 0, 1
        ";
        $p = [ [ 'sync_key', $sync::KEY ]];

        $reqs = Ps::get_results( $q, $p );
        $req = $reqs ? $reqs[0] : null;

        if ( ! $req ) {
            return false;
        }

        if ( $req['count_changes'] < 1 ) {
            return false;
        }

        // only the requests with changes required
        return [ $sync, $req ];

    }, true );

    $op = '';

    $date_now = date( $format );

    $op .= '<h2 style="margin-bottom: 10px; padding-bottom: 10px;">Product Sync Required Changes</h2>';
    $op .= '<div>' . $date_now . '</div>';
    $op .= '<div>' . count($items) . ' file(s) with changes</div>';
    $op .= "\r\n";
    $op .= "\r\n";

    foreach ( $items as $item ) {

        /**
         * @var \Product_Sync $sync
         * @var array $req
         */
        list( $sync, $req ) = $item;

        $count_titles = [];
        $counts_arr = [];

        if ( $sync::TYPE === 'tires' ) {
            $count_titles['prod_new'] = "New Tires";
            $count_titles['prod_diff'] = "Changed Tires";
            $count_titles['prod_del'] = "Tires To Delete";
            $count_titles['brands_new'] = "New Brands";
            $count_titles['models_new'] = "New Models";
            $count_titles['models_diff'] = "Changed Models";
        } else {
            $count_titles['prod_new'] = "New Rims";
            $count_titles['prod_diff'] = "Changed Rims";
            $count_titles['prod_del'] = "Rims To Delete";
            $count_titles['brands_new'] = "New Brands";
            $count_titles['models_new'] = "New Models";
            $count_titles['finishes_new'] = "New Finishes";
            $count_titles['finishes_diff'] = "Changed Finishes";
        }

        // only show non-zero values
        foreach ( $count_titles as $c1 => $c2 ) {
            $c = (int) $req[$c1];
            if ( $c > 0 ) {
                $counts_arr[] = "$c $c2";
            }
        }

        $counts_str = implode(", ", $counts_arr );

        $inserted_at = date( $format, strtotime( $req['inserted_at'] ) );

        $title = strtoupper( $sync->get_admin_title() );
        $count_all = $req['count_all'];
        $count_valid = $req['count_valid'];
        $count_changes = $req['count_changes'];

        $op .= "$title ($inserted_at) \r\n";
        $op .= "$count_all products in file ($count_valid valid). $count_changes changes required. \r\n";

        if ( $counts_str ) {
            $op .= "($counts_str)";
        }

        $url = get_admin_page_url( 'product_sync', [
            'action' => 'sync',
            'key' => $sync::KEY,
            'req_id' => (int) $req['id'],
            'limit' => 1000,
        ] );

        $op .= '<div><a href="' . $url . '" target="_blank">Synchronize</a></div>';

        $op .= "\r\n";
        $op .= "\r\n";
    }

    return [ $items, $op ];
}