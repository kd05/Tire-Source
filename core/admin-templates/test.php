<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

page_title_is( 'Tests' );

cw_get_header();
Admin_Sidebar::html_before();

// see _register-admin-pages.php
$registered_tests = gp_get_global( 'admin_test_pages', [] );

$desired_test = gp_if_set( $_GET, 'test' );

$found = false;

// check file exists and include it
if ( $desired_test ) {
	$found = false;
	foreach ( $registered_tests as $rt) {
		// convert underscores to dash
		$rt_slug = make_slug( $rt, false );

		$file_relative = 'tests/' . $rt_slug . '.php';
		$path = dirname( __FILE__ ) . '/' . $file_relative;

		if ( $desired_test && make_slug( $desired_test ) == $rt_slug ) {
			if ( file_exists( $path ) ) {
				$found = true;
				include $file_relative;
			}
		}
	}

	if ( ! $found ) {
		echo wrap_tag( 'Not found', 'p' );
	}
} else {

    echo wrap_tag( "Mostly Developer tools:");

	foreach ( $registered_tests as $t ) {
		echo '<p>';
		echo get_anchor_tag_simple( cw_add_query_arg( [ 'test' => $t ], get_admin_page_url( 'test' ) ), $t );
		echo '</p>';
	}
}





Admin_Sidebar::html_after();
cw_get_footer();