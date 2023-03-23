<?php

$ret = array();
$logged_in = cw_is_user_logged_in();

// in general we're not going to show logout buttons to logged out users, but due to cache and stuff
// they might end up seeing one.
if ( ! $logged_in ) {
	$ret['success'] = false;
	$ret['alert'] = 'You were not logged in. Please re-load the page and/or clear your browser cache if it appears that you are logged in.';
	Ajax::echo_response( $ret );
	exit;
}


$logged_in_before = cw_is_user_logged_in();

cw_make_user_logged_out();

$logged_in_after = cw_is_user_logged_in();

$logged_out = ( $logged_in_before && ! $logged_in_after );

// I don't think we need to show any response text, we'll just reload the page with javascript,
// however, the reloading only happens if success is true.. so...
if ( $logged_out ) {
	$ret['success'] = true;
	Ajax::echo_response( $ret );
	exit;
}

// logically this shouldn't happen, because the script will stop before
// we get to here if they weren't logged in to begin with.
$ret['success'] = false;
$ret['alert'] = 'Something unexpected happened while trying to log you out. If you want to ensure you are logged out, please close and open your browser, and re-visit the site.';
Ajax::echo_response( $ret );
exit;

