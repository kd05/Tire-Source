<?php

class Cron_Helper{

    /**
     * A global array to get merged into what we log in self::log_after().
     *
     * Try to use different indexes than what's in log_after().
     *
     * @var array
     */
    public static $merge_into_log_after = [];

    private static $log_id;

    /**
     *
     */
    static function config_ini(){

        ini_set( "upload_max_filesize", '100M' );
        ini_set( "post_max_size", '100M' );

        ini_set("memory_limit", '1024M');
        ini_set("max_execution_time", 3600);

        ini_set( 'error_reporting', E_ALL );
        ini_set( 'display_errors', 1 );

        // its possible that in some environments, the cron job user will not have permissions
        // to our normal error logging file, if it was created by another user
        ini_set("error_log", PHP_ERROR_LOG_DIR . '/ciw-cron-php-error.log' );
    }

    /**
     * Attempts to catch time or memory limits being encountered, thus triggering
     * a shutdown. Call this near the beginning of a cron script. If you do, you must
     * also set CRON_REACHED_END_OF_FILE to true before your script exits (or define
     * it at the bottom of your script).
     */
    static function register_shutdown_function(){

        $func = function(){

            // if we reached the end of the script, it basically means errors were not encountered
            if ( defined( 'CRON_REACHED_END_OF_FILE' ) && CRON_REACHED_END_OF_FILE ) {
                return;
            }

            // trying to catch the reason why we the script did not finish. keep in mind
            // we have separate assertion and exception handlers which may also log things.
            $data = [
                'event' => 'cron did not hit end of file - register shutdown function triggered.',
                'action' => gp_test_input( @$_GET['action'] ),
                'last_error' => error_get_last(),
                'time' => time(),
                'output' => ob_get_clean(),
                'date' => date( 'Ymd H:i:sa' ),
            ];

            log_data( $data, 'cron-shutdown', true, true, false );
        };

        register_shutdown_function( $func );
    }

    /**
     * Log before so we know when a process starts but does not finish.
     *
     * @param $action
     */
    static function log_before( $action ){

        start_time_tracking( 'cron_log' );
        self::$log_id = uniqid();

        $data = [
            'log_id' => self::$log_id,
            'date' => date( 'Ymd H:i:sa' ),
            'time_1' => time(),
        ];

        if ( $action === 'supplier_inventory' ) {
            $data['supplier_inventory_step'] = cw_get_option( 'supplier_inventory_step' );
        }

        self::log( $action, $data );

    }

    /**
     * @param $action
     */
    static function log_after( $action  ){

        $data = array_merge( [
            'log_id' => self::$log_id,
            'time_2' => time(),
            'seconds' => round( end_time_tracking( 'cron_log' ), 2 ),
            'peak_mem' => number_format( (float) memory_get_peak_usage(), 0, '', ',' ),
        ], self::$merge_into_log_after );

        self::log( $action, $data );

    }

    /**
     * @param $action
     * @param $data
     */
    static function log( $action, $data ) {
        log_data( $data, "cron-" . make_slug( $action ) . '-' . date('Y' ) . '.log' );
    }

}
