<?php

include '../load.php';

if ( ! defined( 'BASE_DIR' ) ) exit; // exit on direct access

if ( ! cw_is_admin_logged_in() ) {
	echo 'admin only';
	exit;
}

// can use this to test our app exception handler and server settings for whether or not to display errors etc.
throw new Exception( 'Test exception from /tests/exception.php' );