<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// load the non-wordpress app
include dirname( ABSPATH ) . '/load.php';

add_filter('show_admin_bar', '__return_false');

add_theme_support( 'post-thumbnails' );

// adds wp_head to apps main header
add_filter( 'cw_head', function(){
	wp_head();
});

// adds wp_footer to apps main footer
add_filter( 'cw_footer', function(){
	wp_footer();
});
