<?php

$ret = [];
$ret['success'] = false;
$errors = array();

$first_name = get_user_input_singular_value( $_POST, 'first_name' );
$last_name = get_user_input_singular_value( $_POST, 'last_name' );
$email = get_user_input_singular_value( $_POST, 'email' );

$user = cw_get_logged_in_user();

foreach ( array(1) as $a ) {

	if ( ! $user ) {
		$errors[] = 'You must be logged in to use this form.';
		break;
	}

	if ( ! filter_validate_persons_name( $last_name ) ) {
		$errors[] = 'Last name appears invalid. Please try making it shorter or remove special characters.';
	}

	if ( ! filter_validate_persons_name( $first_name ) ) {
		$errors[] = 'First name appears invalid. Please try making it shorter or remove special characters.';
	}

	if ( ! validate_email( $email ) ) {
		$errors[] = 'Please enter a valid email address.';
	}

}

// Exit if errors.
if ( $errors ) {
	$ret['response_text'] = gp_parse_error_string( $errors );
	Ajax::echo_response( $ret );
	exit;
}

$db = get_database_instance();

// run the update
$updated = $db->update( $db->users, [
	'first_name' => '',
	'last_name' => '',
	'email' => $email,
], [
	'user_id' => $user->get_primary_key_value(),
], [
	'first_name' => '%s',
	'last_name' => '%s',
	'email' => '%s',
], [
	'user_id' => '%d',
] );

if ( $updated ) {
	$ret['response_text'] = 'Your profile has been updated.';
	$ret['success'] = true;
} else {
	$ret['response_text'] = 'An unexpected error has occurred and your profile has not been updated.';
	$ret['success'] = false;
}

Ajax::echo_response( $ret );
exit;