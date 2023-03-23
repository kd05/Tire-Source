<?php

$context = gp_if_set( $_POST, 'context' );

$context = in_array( $context, get_valid_per_page_preference_contexts() ) ? $context : '';
$context = gp_test_input( $context ); // just in case

if ( ! $context ) {
	Ajax::echo_response( array(
		'success' => false,
	));
	exit;
}

// stores value in session
$success = set_user_per_page_preference( gp_test_input( $context ), gp_test_input( @$_POST['per_page'] ) );

Ajax::echo_response( array(
	'success' => (bool) $success,
));
exit;