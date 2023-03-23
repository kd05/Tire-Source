<?php

$table = gp_if_set( $_POST, 'delete_db_row_table_name' );
$pk    = gp_if_set( $_POST, 'delete_db_row_primary_key' );

$obj = DB_Table::get_instance_via_table_name_and_primary_key( $table, $pk );

if ( $obj ) {

	// ref
	$error_msg = '';

	// this will check if the deletion is authorized and then attempt to delete
	// for most of our models, deletion is now allowed using this particular tool (or any tools in some cases)
	// for example, we cannot delete orders or transactions via the admin anywhere, even if
	// you open up the DOM and change the value above to orders and an order ID and submit the form.
	$deleted = $obj->handle_admin_single_edit_page_deletion_request( $error_msg );

} else {
	$deleted = false;
	$error_msg = "Could not find the row to delete.";
}

if ( ! $deleted ) {
	$error_msg = $error_msg ? $error_msg : "Unknown Error.";
} else {
	$success_msg = "[" . $obj::get_table() . "] Deleted.";
}

$ret = array(
	'_auto' => array(
		'alert' => $deleted ? $success_msg : $error_msg,
		'reload' => $deleted ? true : false,
	),
);

Ajax::echo_response( $ret );
exit;
