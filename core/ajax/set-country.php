<?php

// do nothing in this case
if ( DISABLE_LOCALES ) {
	$response['success'] = false;
	Ajax::echo_response( $response );
	exit;
}

$response = array();
// we used javascript to add hidden field with name '_country'
// when clicking buttons with name 'country'
$country = gp_if_set( $_POST, '_country' );
$success = true;

if ( $country == 'CA' ) {
	$success = app_set_locale( 'CA' );
} else if ( $country == 'US' ) {
	$success = app_set_locale( 'US' );
} else {
	$success = false;
}

$response['success'] = $success;
Ajax::echo_response( $response );
exit;