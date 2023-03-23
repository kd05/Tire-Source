<?php
/**
 * auto fetch and possibly run updates for all syncs that are configured
 * to do so. This is a parent process which spawns other php process via
 * the http cron file, to try to avoid potential memory issues that could
 * occur doing many syncs in the same process. This file is probably hit once
 * per day (see crontab).
 */

use PS\Cron as Cron;
use Curl\Curl as Curl;

start_time_tracking('_sync_main');

$curl = new Curl();
$curl->get( BASE_URL . '/__http_cron.php?__auth__=834728003551&action=sync_init' );

for ( $x = 0; $x <= 20; $x++ ) {
    $curl = new Curl();
    $curl->get( BASE_URL . '/__http_cron.php?__auth__=834728003551&action=sync_next' );
}

// we'll just run this manually, because we want to do this less frequently than
// the above (perhaps twice per week instead of daily). We need to do the sync_email
// cron after all of the above (so do it a while later, the above should only take
// about 1-2 minutes).
//$curl = new Curl();
//$curl->get( BASE_URL . '/__http_cron.php?__auth__=834728003551&action=sync_email' );
