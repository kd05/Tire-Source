<?php

/**
 * We'll put 1 or more of these in header/footer which prints a message only if we've by passed maintenance mode.
 *
 * To be more clear, this exists so that, if you turn on maintenance mode and bypass it, you don't forget that its on,
 * and nobody other than you can access the site.
 */
function maintenance_mode_print_possible_warning(){
	if ( MAINTENANCE_MODE ) {
		echo '<div style="width: 100%; background: green; padding: 15px;"><h2 style="color: white; text-align: center;">MAINTENANCE MODE !!!! DO NOT FORGET TO TURN THIS OFF</h2></div>';
	}
}

// you can bypass maintenance mode like this...
if ( isset( $_GET['bypass_maintenance'] ) && $_GET['bypass_maintenance'] == 123 ) {
	$_SESSION['bypass_maintenance'] = true;
}

// NOTE: when bypassing maintenance, MAINTENANCE_MODE is still set to true.
// this is so we can show a big ass warning in the header so we don't forget to shut it off after turning it on!
if ( file_exists( BASE_DIR . '/maintenance-mode-indicator.php' ) ){
	define( 'MAINTENANCE_MODE', true );
	if ( ! gp_if_set( $_SESSION, 'bypass_maintenance' ) ) {
		echo 'tiresource.COM is undergoing scheduled maintenance and will be back shortly. Please check again in a few minutes.';
		exit;
	}
} else {
	define( 'MAINTENANCE_MODE', false );
}
