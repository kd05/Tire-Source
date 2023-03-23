<?php

$cart = get_cart_instance();
$checkout = new Checkout_Submit( $_POST, $cart );

// these values are added with javascript based on user actions
// we don't want to mistakenly do one over the other..
$update_receipt = gp_if_set( $_POST, 'update_receipt' );
$process_payment = gp_if_set( $_POST, 'process_payment' );

if ( $update_receipt ) {
	// we actually don't need to do anything, because now we always do the ->run_update_receipt()
} else if ( $process_payment ){
	// careful.. this will attempt to process a payment.
	// and, the form auto submits via javascript to this file when certain form inputs change

	$checkout->run();

} else {
	$checkout->add_error( 'An error has occurred.' );
}

// these store class properties in $checkout that will show up in ->get_ajax_response()
$checkout->run_update_receipt();
$checkout->run_update_items();

// should be emptied if transaction was successful
$response['cart_count'] = get_cart_count();

$response = $checkout->get_ajax_response();
Ajax::echo_response( $response );
exit;


