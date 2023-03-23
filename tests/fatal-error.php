<?php

include '../load.php';

if ( ! defined( 'BASE_DIR' ) ) exit; // exit on direct access

if ( ! cw_is_admin_logged_in() ) {
	echo 'admin only';
	exit;
}

// test server settings for error handling
echo testing_calling_function_that_does_not_exist();
