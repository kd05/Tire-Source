<?php

$response = [
	'_auto' => [],
];

$alert = $reload = false;

$run = function() use( &$response, &$alert, &$reload ){

	$page_id = (int) get_user_input_singular_value( $_POST, 'page_id' );
	$page = DB_Page::create_instance_via_primary_key( $page_id );

	if ( ! $page ) {
		$alert = "Invalid Page";
		return;
	}

	$arr = $page->handle_ajax_edit_form();

	// only re load on success. on success we may or may not have messages.
	// This is fine. We can alert a (success) message, and then immediately
	// and automatically re-load the page. That's fine.. but,
	// to have errors that require fixing, and then re-load the page, is not fine.

	if ( ! $arr['msgs'] ) {
		$arr['msgs'] = $arr['success'] ? "Success" : "Error";
	}

	$alert = gp_javascript_alert_from_msgs( $arr['msgs'] );
	$reload = (bool) $arr['success'];
};

// variables modified.. (pass by ref)
$run();

// a bit messy but w/e
$response['_auto']['alert'] = $alert;
$response['_auto']['reload'] = $reload;

Ajax::echo_response( $response );
exit;