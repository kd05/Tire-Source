<?php

$pw_error = 'Your current password was not entered correctly.';

$ret = [];
$ret['success'] = false;
$errors = array();

$current_password = gp_if_set( $_POST, 'current_password' );

$password_1 = gp_if_set( $_POST, 'password_1' );
$password_2 = gp_if_set( $_POST, 'password_2' );

$user = cw_get_logged_in_user();

foreach ( array(1) as $a ) {

	if ( ! $user ) {
		$errors[] = 'You must be logged in to use this form.';
		break;
	}

	// this is ... completely pointless?? I think $user will be false if they are locked
	if( $user->is_locked() ) {
		$errors[] = $user->get_locked_message();
		break;
	}

	if ( ! verify_user_password( $user, $current_password ) ) {

		$locked = track_failed_user_login( $user );

		// if tracking the failed login attempt rendered the user locked..
		if ( $locked ) {
			$errors[] = $user->get_locked_message();
			break;
		} else {
			$errors[] = $pw_error;
		}
		break;
	}

	$msg = ''; // by reference
	if ( ! validate_two_passwords( $password_1, $password_2, $msg ) ) {
		$errors[] = $msg;
		break;
	}
}

// Exit if errors.
if ( $errors ) {
	$ret['response_text'] = gp_parse_error_string( $errors );
	Ajax::echo_response( $ret );
	exit;
}

$db = get_database_instance();

$updated = update_current_users_password( $password_1 );
$logged_out_everywhere = make_user_logged_out_everywhere( $user );
cw_make_user_logged_out();

listen_add_ajax_debug( $user );

if ( $updated ) {
	$ret['success'] = true;
	$ret['success_2'] = $logged_out_everywhere;
	$ret['response_text'] = 'Your password has been updated. You have been logged out on all devices including this one. Please <a href="' . get_url( 'login' ) . '">Login</a> again.';
} else {
	$ret['success'] = false;
	$ret['response_text'] = 'An unexpected error has occurred, and your password could not be updated.';
}

// re-get the user object in case we need to continue using it
// $user = cw_get_logged_in_user();

Ajax::echo_response( $ret );
exit;


