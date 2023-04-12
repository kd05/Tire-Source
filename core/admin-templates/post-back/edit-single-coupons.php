<?php

/** @var DB_User $user */
$coupon = gp_get_global( '_coupon' );
$coupon_id = $coupon->get( 'id' );


$errors = array();
$form_submitted = gp_if_set( $_POST, 'form_submitted' );
$pw_success_msg = '';

//echo "--------------------------";
//echo "<pre>"; print_r($coupon); echo "</pre>";
//echo "<pre>"; print_r($form_submitted); echo "</pre>";
//echo "--------------------------";

if ( ! $coupon ) {
	return;
}

if ( ! cw_is_admin_logged_in() ) {
	return;
}

if ( ! $form_submitted ) {
	return;
}

$nonce = gp_if_set( $_POST, 'nonce' );

if ( ! validate_nonce_value( 'edit_single_coupons', $nonce ) ) {
	$errors[] = 'Nonce validation failed, please re-load the page without re-submitting the form (you may need to re-navigate to the page).';
}

$coupon_code  = get_user_input_singular_value( $_POST, 'coupon_code' );
$coupon_discount = get_user_input_singular_value( $_POST, 'coupon_discount' );
$coupon_validity = get_user_input_singular_value( $_POST, 'coupon_validity' );
$max_time_usable = get_user_input_singular_value( $_POST, 'max_time_usable' );
$status = get_user_input_singular_value( $_POST, 'status' );


if (!$coupon_code || !$coupon_validity) {
    $errors[] = 'Please Fill All Mandatory Fields';
}

if(!($coupon_discount > 0 && $coupon_discount <= 100)){
    $errors[] = 'Invalid Discount (Must be between 1 - 100).';
}

$check_coupon_code = DB_Coupon::check_coupon_by_coupon_code( $coupon_code, $coupon_id);
if($check_coupon_code){
    $errors[] = 'This coupon could already exists.';
}


if ( $errors ) {
	gp_set_global( 'post_back_response', gp_parse_error_string( $errors ) );
	return;
}

// update
if ( ! $errors ) {
	$data = [
		'coupon_code' => $coupon_code,
		'coupon_discount' => $coupon_discount,
		'coupon_validity' => $coupon_validity,
		'max_time_usable' => $max_time_usable,
		'status' => $status,
	];
    $coupon->update_database_and_re_sync( $data, [] );
}

end_file:

if ( $errors ) {
	gp_set_global( 'post_back_response', gp_parse_error_string( $errors ) );
} else {
	$success_msg = ['Coupon Updated.'];
	gp_set_global( 'post_back_response', gp_parse_error_string( $success_msg ) );
}

