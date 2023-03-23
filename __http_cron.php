<?php
/**
 * Can use wget or curl on a cron job to bypass hard memory limits which exist during
 * normal cron job. Not ideal but w/e.
 *
 * Note: on live website, most cron jobs hit the cron.php file that lives one
 * folder up from public html dir. This file is for the more memory intensive
 * amazon processes.
 */

// hardcode this auth key in the crontab file. protects from public access.
if ( @$_GET[ '__auth__' ] == "834728003551" ) {

    // make sure to also pass in $_GET['action'], ie. "amazon_ca", or "amazon_us"

    define( 'DOING_CRON', true );

    // may or may not use this.
    define( 'DOING_HTTP_CRON', true );
    define( 'ENV', 'live' );

    // load app
    include 'core/_init.php';

    echo "Starting...\r\n";

    // controller will execute the cron script
    include CORE_DIR . '/cron/_controller.php';

    echo "Done...\r\n";

} else {
    echo "Not authorized.";
}
