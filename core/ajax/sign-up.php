<?php

// this script is only for admin users. currently users must checkout in order to sign up
$user = cw_get_logged_in_user();

if ( ! $user || ! $user->is_administrator() ) {
	$response['response_text'] = 'Authorization error.';
	Ajax::echo_response( $response );
	exit;
}

// let admin users make other admin users
if ( $user->is_administrator() ) {
	$admin = (bool) gp_if_set( $_POST, 'make_admin' );
} else {
	$admin = false;
}

$response = array();

$first_name = get_user_input_singular_value( $_POST, 'first_name' );
$last_name = get_user_input_singular_value( $_POST, 'last_name' );

$email = gp_if_set( $_POST, 'email' );
$password_1 = gp_if_set( $_POST, 'password_1' );
$password_2 = gp_if_set( $_POST, 'password_2' );

try{

	$user_id = insert_user_from_user_input( $email, $password_1, $password_2, $first_name, $last_name, $admin );

	if ( $user_id ) {
		// indicate success to javascript doesn't print a default error message..
		$response['success'] = true;
		$response['response_text'] = 'Success';
	} else {
		$response['success'] = false;
		$response['response_text'] = 'Error';
	}

} catch ( User_Exception $e ) {
	$response['success'] = false;
	$response['response_text'] = $e->getMessage();
}

Ajax::echo_response( $response );
exit;