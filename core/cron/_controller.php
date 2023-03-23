<?php
/**
 * Front controller for cron job processes.
 *
 * @see cron.php (one dir up from public_html) (the server hits cron.php, which includes core/cron/_main.php)
 */

ob_start();

Cron_Helper::register_shutdown_function();
Cron_Helper::config_ini();

$action = @$_GET['action'];

Cron_Helper::log_before( $action );

switch ( $action ) {
    case 'sitemap':
        require CORE_DIR . '/cron/sitemap.php';
        break;
    case 'supplier_inventory':
        require CORE_DIR . '/cron/supplier-inventory.php';
        break;
    case 'amazon_ca':
        require CORE_DIR . '/cron/mws-inventory-ca.php';
        break;
    case 'amazon_us':
        require CORE_DIR . '/cron/mws-inventory-us.php';
        break;
    case 'sync_main':
        require CORE_DIR . '/classes/product-sync/cron/sync_main.php';
        break;
    case 'sync_init':
        require CORE_DIR . '/classes/product-sync/cron/sync_init.php';
        break;
    case 'sync_next':
        require CORE_DIR . '/classes/product-sync/cron/sync_next.php';
        break;
    case 'sync_email':
        require CORE_DIR . '/classes/product-sync/cron/sync_email.php';
        break;
    case 'cron_test':
        require CORE_DIR . '/cron/cron-test.php';
        break;
    default:
        log_data( [ "An action needs to be passed to cron file.", time() ], 'cron-no-action.log' );
}

// output can catch some errors/warnings
$output = ob_get_clean();

if ( $output ) {
    Cron_Helper::$merge_into_log_after["_output"] = $output;
}

Cron_Helper::$merge_into_log_after["_mem"] = get_mem_formatted();
Cron_Helper::$merge_into_log_after["_peak_mem"] = get_peak_mem_formatted();

// log to file after
Cron_Helper::log_after( $action );

// necessary when we register the shutdown function
if ( ! defined( "CRON_REACHED_END_OF_FILE" ) ) {
    define( 'CRON_REACHED_END_OF_FILE', true );
}
