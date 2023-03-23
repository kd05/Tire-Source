<?php

$sub_action = get_user_input_singular_value( $_POST, 'general_ajax_sub_action' );

// removed dots or slashes
$filename_no_ext = make_safe_php_filename_from_user_input( $sub_action, true );
$path = CORE_DIR . '/ajax/general-ajax-sub-action/' . $filename_no_ext . '.php';

if ( file_exists( $path) ){
	include $path;
	exit;
} else {

	$alert = cw_is_admin_logged_in() ? 'File not found.' : "Error";

	echo json_encode( array(
		'_auto' => array(
			'alert' => $alert,
		)
	));

	exit;
}