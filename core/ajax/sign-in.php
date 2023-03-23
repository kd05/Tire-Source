<?php

// use different function for password to allow special characters
$password = get_array_value_force_singular( $_POST, 'password' );
$email    = get_user_input_singular_value( $_POST, 'email' );

$ret = [ 'success' => false ];

// definitely not printing out messages like "that user doesn't exist" or "that password was incorrect"
// one message for pretty much every error, except for a few specific ones, like, email invalid, or password empty
$all_errors_message = 'Please try again.';

listen_add_ajax_debug( 0 );

$logging_in_as = false;
$verified = false;
$errors   = array();

foreach ( array( 1 ) as $nothing ) {

	if ( cw_is_user_logged_in() ) {
		listen_add_ajax_debug( '01' );
		$errors[] = 'You are already logged in. Please ' . get_ajax_logout_anchor_tag( [ 'text' => 'logout' ] ) . ' first.';
		break;
	}

	if ( ! validate_email( $email ) ) {
		// $ret['response_text'] = 'Please enter a valid email address';
		// actually nevermind indicating an invalid email
		$errors[] = $all_errors_message;
		break;
	}

	//
	if ( ! $password ) {
		listen_add_ajax_debug( 2 );
		$errors[] = 'Please enter a password.';
		break;
	}

	// this may reveal valid user emails.... but what else can we do if we want the person to know
	// that their account is locked... we kind of have to print this.
	$logging_in_as = DB_User::create_instance_via_email( $email );

	if ( ! $logging_in_as ) {
		listen_add_ajax_debug( 3 );
		$errors[] = $all_errors_message;
		break;
	}

	if ( $logging_in_as->is_locked() ) {
		listen_add_ajax_debug( '2a' );
		$errors[] = $logging_in_as->get_locked_message();
		break;
	}

	if ( ! verify_user_password( $logging_in_as, $password ) ) {
		track_failed_user_login( $logging_in_as );
		$errors[] = $all_errors_message;
		listen_add_ajax_debug( 5 );
		break;
	}

	$verified = true;
}

// show the user a login error
if ( $errors || ! $verified ) {
	$ret[ 'response_text' ] = gp_parse_error_string( $errors );
	Ajax::echo_response( $ret );
	exit;
}

// Do the login
if ( $logging_in_as && cw_make_user_logged_in( $logging_in_as ) ) {

	// reset the failed logins counter
	$logging_in_as->update_database_and_re_sync( array(
		'failed_logins' => 0,
	), array(
		'failed_logins' => '%d',
	));

	// send response message back even though javascript may decide not to show it and just reload the page.
	$ret[ 'success' ] = true;

	// possibly only shown when no redirect or reload takes place, which I doubt will occur.
	$ret[ 'response_text' ] = 'Success';

	// reload, or redirect ?
	$reload        = (int) get_user_input_singular_value( $_POST, 'reload' );
	$redirect      = get_user_input_singular_value( $_POST, 'redirect', '' );
	$redirect      = gp_test_input( $redirect );
	$redirect_args = get_user_input_singular_value( $_POST, 'redirect_args', array() );

	// pass back to javascript whatever information was submitted
	// (except of course, we may use the get_url() function to transform the redirection value)
	// let javascript determine the priority if both reload is true, and a location is given.
	$ret[ 'reload' ]   = ( $reload );
	$base              = $redirect ? get_url( $redirect ) : '';
	$base              = $base ? cw_add_query_arg( $redirect_args, $base ) : '';
	$ret[ 'location' ] = $base;

} else {
	$ret[ 'success' ]       = false;
	$ret[ 'response_text' ] = $all_errors_message;
	listen_add_ajax_debug( 6 );
}

// if we get to here, email and password is correct.
Ajax::echo_response( $ret );
exit;