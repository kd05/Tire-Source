<?php

/**
 * This file is redundant because we could to this in a function..
 * But anyways, all the svg code used to be here, and I decided to move
 * each icon into its own file to avoid compiling every icon on every page load.
 * this is probably a pointless optimization but still an optimization anyways.
 */

// see @function gp_get_icon() ... setup this global then include the file
global $icon_name;

$icon_name = gp_force_singular( $icon_name );
$icon_name = gp_esc_db_col( $icon_name );

$file = CORE_DIR . '/svg-icons/' . $icon_name . '.php';

if ( file_exists( $file ) ) {
	include( $file );
}
