<?php

$ret = [];
$ret['success'] = false;

$key = gp_if_set( $_POST, 'key' );
$parsed = new Parsed_Forgot_Password_Key( $key );
$valid = $parsed->is_valid();

$p1 = get_array_value_force_singular( $_POST, 'password_1' );
$p2 = get_array_value_force_singular( $_POST, 'password_2' );
$p1 = trim( $p1 );
$p2 = trim( $p2 );

// we show a more detailed message before rendering the form
if ( ! $valid ) {
	$ret['response_text'] = 'Invalid Key';
	Ajax::echo_response( $ret );
	exit;
}

$password_error_message = ''; // by reference
if ( ! validate_two_passwords( $p1, $p2, $password_error_message ) ) {
	$ret['response_text'] = $password_error_message;
	Ajax::echo_response( $ret );
	exit;
}

/**
 * This is a DB_User if $parsed->is_valid()
 *
 * @var DB_User $user
 */
$user = $parsed->user;

// reset password can unlock users with locked status of 1
if ( $user->get_locked_status() > 1 ) {
	$ret['response_text'] = 'Your account has been locked. Please contact us if you wish to unlock it.';
	Ajax::echo_response( $ret );
	exit;
}

$user->unlock_user();

$db = get_database_instance();
$updated = $db->update(
	$db->users,
	array(
		'password' => app_hash_password( $p1 ),
		'reset_key' => '', // clear the password reset key
	),
	array(
		'user_id' => $user->get_primary_key_value()
	)
);

if ( ! $updated ) {
	$ret['response_text'] = 'An unexpected error occurred';
	Ajax::echo_response( $ret );
	exit;
}

make_user_logged_out_everywhere( $user, true );

$ret['success'] = true;
$ret['response_text'] = '<p>Your password has been reset. You have been logged out on all devices. <a href="' . get_url( 'login' ) . '">Click here</a> to login.<p>';
Ajax::echo_response( $ret );
exit;
