<?php
// this script is only for admin users. currently users must checkout in order to sign up
$user = cw_get_logged_in_user();

if ( ! $user || ! $user->is_administrator() ) {
	$response['response_text'] = 'Authorization error.';
	Ajax::echo_response( $response );
	exit;
}

//echo "<pre>"; print_r($_POST); echo "</pre>";

$coupon_code = get_user_input_singular_value( $_POST, 'coupon_code' );
$coupon_discount = get_user_input_singular_value( $_POST, 'coupon_discount' );
$coupon_validity = get_user_input_singular_value( $_POST, 'coupon_validity' );
$max_time_usable = get_user_input_singular_value( $_POST, 'max_time_usable' );
$status = get_user_input_singular_value( $_POST, 'status' );


try{



	$coupon_id = insert_coupon( $coupon_code, $coupon_discount, $coupon_validity, $max_time_usable, $status);

	if ( $coupon_id ) {
		// indicate success to javascript doesn't print a default error message..
		$response['success'] = true;
		$response['response_text'] = 'Success';
	} else {
		$response['success'] = false;
		$response['response_text'] = 'Error';
	}

    $response['success'] = true;
    $response['response_text'] = 'Success';

} catch ( Coupon_Exception $e ) {
	$response['success'] = false;
	$response['response_text'] = $e->getMessage();
}

Ajax::echo_response( $response );
exit;