<?php

/** @var DB_User $user */
$user = gp_get_global( '_user' );

$cur_user = cw_get_logged_in_user();
$cur_user_id = $cur_user->get( 'user_id' );
$user_id = $user->get( 'user_id' );

$editing_self = $cur_user_id && $cur_user_id == $user_id;

$errors = array();
$form_submitted = gp_if_set( $_POST, 'form_submitted' );
$pw_success_msg = '';

if ( ! $user ) {
	return;
}

if ( ! cw_is_admin_logged_in() ) {
	return;
}

if ( ! $form_submitted ) {
	return;
}

$nonce = gp_if_set( $_POST, 'nonce' );

if ( ! validate_nonce_value( 'edit_single_users', $nonce ) ) {
	$errors[] = 'Nonce validation failed, please re-load the page without re-submitting the form (you may need to re-navigate to the page).';
}

$first_name = get_user_input_singular_value( $_POST, 'first_name' );
$last_name = get_user_input_singular_value( $_POST, 'last_name' );
$email = get_user_input_singular_value( $_POST, 'email' );

$password_1 = get_user_input_singular_value( $_POST, 'password_1' );
$password_2 = get_user_input_singular_value( $_POST, 'password_2' );

$delete_user = get_user_input_singular_value( $_POST, 'delete_user' );
$delete_confirm_email = get_user_input_singular_value( $_POST, 'delete_confirm_email' );

// won't show message here if user is deleted, their information won't show ..
$do_delete_user = false;
if ( $delete_user ) {

	if ( ! $delete_confirm_email ) {
		$errors[] = 'Please type the user\'s email to confirm deletion.';
		$do_delete_user = false;
	}

	if ( $delete_confirm_email != $user->get( 'email' ) ) {
		$errors[] = 'Email confirmation did not match.';
		$do_delete_user = false;
	}

	$do_delete_user = true;
}

$locked_status = get_user_input_singular_value( $_POST, 'locked_status' );

if ( $editing_self ) {
	if ( $locked_status ) {
		$errors[] = 'You can\'t lock yourself.';
	}

	if ( $delete_user ) {
		$errors[] = 'You can\'t delete yourself.';
	}
}

$do_password = false;
if ( $password_1 || $password_2 ) {

	$pw_error = ''; // reference
	if ( ! validate_two_passwords( $password_1, $password_2, $pw_error ) ) {
		$errors[] = $pw_error ? $pw_error : 'password error.';
	} else {
		$do_password = true;
	}
}

// i guess leave this off for admin users
//if ( ! filter_validate_persons_name( $first_name ) ) {
//	$response[] = 'Invalid first name';
//}
//
//if ( ! filter_validate_persons_name( $last_name ) ) {
//	$response[] = 'Invalid last name';
//}

if ( ! $email ) {
	$errors[] = 'User must have an email.';
}

if ( ! validate_email( $email ) ) {
	$errors[] = 'User email must be valid.';
}

$user_with_email = DB_User::create_instance_via_email( $email );
if ( $user_with_email && $user_with_email->get( 'user_id' ) != $user_id ) {
	$errors[] = 'Another user already has that email.';
}

if ( $errors ) {
	gp_set_global( 'post_back_response', gp_parse_error_string( $errors ) );
	return;
}

// update
if ( ! $errors ) {

	// handle delete user first, then skip the rest if we delete
	if ( $do_delete_user ) {

		$user_id = $user->get( 'user_id' );

		$deleted = delete_user( $user_id );
		delete_user_reviews( $user_id );

		if ( $deleted ) {
			$user = null;
			goto end_file;
		}

	}

	if ( $user->get( 'locked_status' ) != $locked_status ) {
		$user->set_locked_status( $locked_status );
	}

	$data = [
		'first_name' => $first_name,
		'last_name' => $last_name,
		'email' => $email,
	];

	$user->update_database_and_re_sync( $data, [] );

	if ( $do_password ) {

		$pw_success = update_users_password( $user, $password_1 );

		if ( $pw_success ) {

			if ( $editing_self ) {
				$pw_success_msg = 'Your password has been updated.';
				cw_make_user_logged_in( $cur_user ); // re-login..
			} else {
				$pw_success_msg = 'This users password has been updated, they have been logged out everywhere.';
			}
		}
	}

}

end_file:

if ( $errors ) {
	gp_set_global( 'post_back_response', gp_parse_error_string( $errors ) );
} else {

	$success_msg = ['User Updated.'];

	if ( $pw_success_msg ) {
		$success_msg[] = $pw_success_msg;
	}

	gp_set_global( 'post_back_response', gp_parse_error_string( $success_msg ) );
}

