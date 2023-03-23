<?php
/**
 * Send US product inventory to Amazon
 */

$mws_locale = MWS_LOCALE_US;

// ya fairly redundant but want to have proof that we did it right
Cron_Helper::$merge_into_log_after['mws_locale'] = $mws_locale;

mws_check_for_feed_submission_updates( $mws_locale );
$tick = new MWS_Inventory_Tick( $mws_locale );
$tick->run();

Cron_Helper::$merge_into_log_after['_debug'] = $tick->debug;