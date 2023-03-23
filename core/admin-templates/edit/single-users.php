<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

$user_id = gp_if_set( $_GET, 'pk' );
$table = gp_if_set( $_GET, 'table' );

/** @var DB_User $user */
$user = DB_User::create_instance_via_primary_key( $user_id );

if ( ! $user ) {
	echo 'Invalid user';
	goto end_file;
} else {
	// Post back file
	gp_set_global( '_user', $user );
	include CORE_DIR . '/admin-templates/post-back/edit-single-users.php';

	if ( ! $user ) {
		echo 'User does not exist.';
		goto end_file;
	}

}

echo '<div class="admin-section general-content">';
echo render_html_table_admin( false, [ $user->to_array() ], [ 'title' => 'User Details' ] );
echo '</div>';

echo '<div class="admin-section general-content">';

$actions = array();
$locked_status = $user->get_locked_status();

if ( DB_User::count_users_with_email( $user->get( 'email' ) ) > 1 ) {
	echo '<p><strong>Warning: 2 users have ' . $user->get( 'email', null, true ) . ' as their email. Please change this immediately.</strong></p>';
}

echo '<form method="post" action="" class="form-style-basic">';

echo '<input type="hidden" name="form_submitted" value="1">';
echo get_nonce_input( 'edit_single_users' );

echo get_form_header( 'Edit User' );

echo '<p>Each user can edit most of these fields from their profile/account page. The main thing you will probably want to do here is lock or unlock a user. After ' . MAX_FAILED_LOGIN_ATTEMPTS . ' failed login attempts, a user will have their locked status set to 1, at which point they can use the reset password functionality to gain access back to their account. If you want to actually lock down a user\'s account you can set locked status 2.</p>';
echo '<br><br>';

echo '<div class="form-items">';

$response = gp_get_global( 'post_back_response' );
if ( $response ) {
	echo get_form_response_text( $response );
}

echo get_form_input( array(
	'name' => 'first_name',
	'label' => 'First Name',
	'value' => $user->get( 'first_name' ),
));

echo get_form_input( array(
	'name' => 'last_name',
	'label' => 'Last Name',
	'value' => $user->get( 'last_name' ),
));

echo get_form_input( array(
	'name' => 'email',
	'label' => 'Email',
	'value' => $user->get( 'email' ),
));

echo get_form_select( array(
	'label' => 'Locked Status',
	'name' => 'locked_status',
), array(
	'current_value' => $user->get( 'locked_status' ),
	'items' => array(
		'' => 'Locked Status 0 (or empty): not locked.',
		1 => 'Locked Status 1: can be unlocked via password reset.',
		2 => 'Locked Status 2: cannot be unlocked via password reset.',
	)
));

// password 1
echo get_form_input( array(
	'name' => 'password_1',
	'type' => 'password',
	// 'value' => gp_if_set( $_POST, 'password_1' ),
	'label' => 'Set Password',
));

// password 1
echo get_form_input( array(
	'name' => 'password_2',
	'type' => 'password',
	// 'value' => gp_if_set( $_POST, 'password_2' ),
	'label' => 'Confirm Password',
));

// delete checkbox
echo get_form_checkbox( array(
	'value' => 1,
	'name' => 'delete_user',
	'label' => 'Delete User',
));

// delete confirm
echo get_form_input( array(
	'name' => 'delete_confirm_email',
	'value' => '',
	'label' => 'Type the users email to confirm deletion.',
));

echo get_form_submit();

echo '</div>'; // form-items

echo '</form>';

echo '</div>';

end_file: