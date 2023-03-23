<?php

$msgs = array();

if ( empty( $_POST ) ) {
	return;
}

if ( ! validate_nonce_value( $nonce_secret, gp_if_set( $_POST, 'nonce' ), true ) ) {
	return;
}

$msgs[] = 'Form submitted...';

$hash_key = gp_if_set( $_POST, 'hash_key' );
start_time_tracking( '__stock_import' );

$import = null;
$cls = Supplier_Inventory_Supplier::get_class_name_via_hash_key( $hash_key );

if ( $cls ) {

	$msgs[] = 'Running operation: ' . gp_test_input( $hash_key ) . '.';

	/** @var Supplier_Inventory_Supplier $instance */
	$instance = new $cls();
	$import = $instance::run_import_without_hash_checks();

	if ( $import->errors ) {
		$msgs[] = 'ERRORS:';
		$msgs[] = get_pre_print_r( $import->errors );
	} else {
		$msgs[] = 'OVERVIEW:';
		$msgs[] = get_pre_print_r( $import->db_stock_update );
	}

} else {
	$msgs[] = 'Import was not run.';
}


