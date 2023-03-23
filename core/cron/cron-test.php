<?php
/**
 * Test file to see if cron jobs are working or w/e...
 *
 * When included via the controller, logging will be setup so you'll know it got run.
 */

if ( (int) @$_GET['info'] === 1 ) {

    ob_start();
    phpinfo();
    $_ = ob_get_clean();

    file_put_contents( LOG_DIR . '/cron-info-' . time(), $_, FILE_USE_INCLUDE_PATH );
}

log_data( ["cron-test.php file was run.", time(), date( 'r') ], "cron-test-file" );

Cron_Helper::$merge_into_log_after['__test'] = "Making sure this works too.";