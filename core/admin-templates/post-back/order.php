<?php

if ( ! cw_is_admin_logged_in() ) {
	return;
}

$errors = array();
$success_msg = '';

$form_submitted = gp_if_set( $_POST, 'form_submitted' );
$nonce = gp_if_set( $_POST, 'nonce' );

if ( ! $form_submitted ) {
	goto end_file;
}

if ( validate_nonce_value( 'edit-single-order', $nonce, true ) ) {
	$errors = 'Nonce validation error.';
	goto end_file;
}

$_status = gp_if_set( $_POST, '_status' );
$_status = gp_test_input( $_status );

// i guess we'll allow empty status
$order->update_database_and_re_sync(array(
	'order_status' => $_status,
));

$post_back_response = '<p>Status Updated.</p>';
$post_back_success = true;
return;

end_file:

$post_back_success = ( ! $errors );
if ( $errors ) {
	$post_back_response = gp_parse_error_string( $errors );
}
