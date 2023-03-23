<?php
/**
 * Update product inventory via supplier data
 */

if ( ! defined( 'BASE_DIR' ) ) {
    exit;
} // exit on direct access

// key in options table
$key = 'supplier_inventory_step';
$supplier_inventory_step = (int) cw_get_option( $key );

switch ( $supplier_inventory_step ) {
    case 1:
        $instances = Supplier_Inventory_Supplier::get_cron_job_instances_via_step(1);
        // for next time
        cw_set_option( $key, 2 );
        break;
    case 2:
        $instances = Supplier_Inventory_Supplier::get_cron_job_instances_via_step(2);
        // for next time
        cw_set_option( $key, 3 );
        break;
    case 3:
        $instances = Supplier_Inventory_Supplier::get_cron_job_instances_via_step(3);
        // for next time
        cw_set_option( $key, 4 );
        break;
    case 4:
    default:
        $instances = Supplier_Inventory_Supplier::get_cron_job_instances_via_step(4);
        // for next time
        cw_set_option( $key, 1 );
        break;
}

// avoid possible last supplier never getting updated if script
// times out due to size of supplier files and # product in db.
if ( $instances ) {
    shuffle( $instances );
}

// RUN
$stdClass = Supplier_Inventory_Supplier::run_selected_imports_via_instances( $instances, true );

Cron_Helper::$merge_into_log_after[ 'count' ] = $stdClass->count;
Cron_Helper::$merge_into_log_after[ 'processed' ] = $stdClass->processed;
Cron_Helper::$merge_into_log_after[ 'skipped' ] = $stdClass->skipped;
