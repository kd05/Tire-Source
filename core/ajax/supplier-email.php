<?php

if ( ! cw_is_admin_logged_in() ) {
    echo 'Not authorized.';
	exit;
}

$ret = array();
$ret['success'] = false;

$supplier_slug = gp_if_set( $_POST, 'supplier' );
$order_id = (int) gp_if_set( $_POST, 'order_id' );

$order = DB_Order::create_instance_via_primary_key( $order_id );
$supplier = DB_Supplier::get_instance_via_slug( $supplier_slug );
$send_to_all = (bool) gp_if_set( $_POST, 'send_to_all' );

if ( ! $order ) {
	$ret['alert'] = 'Invalid order ID provided.';
	Ajax::echo_response( $ret );
	exit;
}

if ( $send_to_all ) {

	$results = send_all_supplier_order_emails( $order );
	$ret['alert'] = gp_if_set( $results, 'msg', 'Emails sent.');
	$ret['success'] = true;
	Ajax::echo_response( $ret );
	exit;

} else

// do not fail if the supplier does not exist, only if none was provided.
if ( ! $supplier_slug ) {
	$ret['alert'] = 'Supplier is empty.';
	Ajax::echo_response( $ret );
	exit;
}

// passed by reference
$to_text = '';
$sent = send_single_supplier_email( $order, $supplier_slug, $to_text );

if ( $sent ) {
	$alert = 'Mail sent to: ' . $to_text . '.';
} else {
	$alert = 'Error: Mail not sent to: ' . $to_text . '.';
}

$ret['alert'] = $alert;
$ret['success'] = true;

Ajax::echo_response( $ret );
exit;

