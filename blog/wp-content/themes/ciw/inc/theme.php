<?php
/**
 * Theme functions
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Wraps wp_get_attachment_image_src
 *
 * @param        $image_id
 * @param string $size
 *
 * @return bool
 */
function gp_get_img_url( $image_id, $size = 'large' )
{
	$src = wp_get_attachment_image_src( $image_id, $size, false );
	$url = isset( $src[ 0 ] ) ? $src[ 0 ] : false;
	return $url;
}