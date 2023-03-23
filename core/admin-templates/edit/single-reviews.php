<?php

if ( ! cw_is_admin_logged_in() ) {
	echo 'not admin';
	exit;
}

$pk = gp_if_set( $_GET, 'pk' );
$review = DB_Review::create_instance_via_primary_key( $pk );

if ( ! $review ) {
	echo 'Review not found';
	return;
}

$approved_response = '';
$delete_response = '';
$delete_review = gp_if_set( $_POST, 'delete_review' );
$action_approve = gp_if_set( $_POST, 'action_approve' );

if ( $action_approve ) {

	$approved = (bool) gp_if_set( $_POST, 'approved' );

	if ( $approved ) {
		$review->update_database_and_re_sync( array(
			'approved' => 1,
		));

		$approved_response = 'Success';

	} else {
		$review->update_database_and_re_sync( array(
			'approved' => 0,
		));

		$approved_response = 'Success';
	}

} else if ( $delete_review ) {

//	if ( $review->get( 'approved' ) ) {
//		$delete_response = 'Please un-approve the review first, then delete it.';
//	}

	$deleted = $review->delete_self_if_has_singular_primary_key();
	$delete_response = 'Deleted. When you re-load the page, you will no longer be able to access this data.';
}


$user = DB_User::create_instance_via_primary_key( $review->get(  'user_id' ) );
$user_link = $user ? get_admin_single_user_url( $user->get( 'user_id' ) ) : '';
$user_email = $user ? $user->get( 'email' ) : '';

echo '<div class="admin-section general-content">';
echo '<h2>Edit Single Review</h2>';
echo '<p>From here, you can approve, edit, or delete an existing review. Reviews will not show up on the site unless they approved. While a review is not approved, a user can edit it. This includes if you approve a review, and then un-approve it (the user will be able to edit it again).</p>';

if ( $user ) {
	echo '<p>This review belongs to user with email: <a href="' . $user_link . '">' . $user_email . '</a></p>';
} else {
	echo '<p>The user for this review no longer exists.</p>';
}

echo render_html_table_admin( false, [ $review->to_array() ] );

echo '</div>'; // admin-section

// approve
echo '<div class="admin-section general-content">';
echo '<form action="" method="post" class="form-style-basic">';
echo '<input type="hidden" name="action_approve" value="1">';

if ( $approved_response ) {
	echo get_form_response_text( $approved_response );
}

echo get_form_checkbox( array(
	'name' => 'approved',
	'checked' => $review->get( 'approved' ),
	'value' => 1,
	'label' => 'Approved',
));

echo get_form_submit( [ 'text' => 'Set Approved Status' ] );
echo '</form>';
echo '</div>'; // admin-section

// edit fields
echo '<div class="admin-section">';

// delete
echo '<div class="admin-section general-content">';
echo '<form action="" method="post" class="form-style-basic">';

if ( $delete_response ) {
	echo get_form_response_text( $delete_response );
}

echo get_form_checkbox( array(
	'name' => 'delete_review',
	'value' => 1,
	'label' => 'Delete',
));

echo get_form_submit( [ 'text' => 'Delete' ] );
echo '</form>';
echo '</div>'; // admin-section

// edit fields
echo '<div class="admin-section">';

$userdata = $_POST;
$userdata['review'] = $pk;
$view = new Product_Review_Page( $userdata );
echo $view->render_sidebar_and_content();

echo '</div>';

