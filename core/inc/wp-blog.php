<?php
/**
 * Functions to support WordPress install in /blog which includes our
 * app in order to use its header, footer, styles, js, etc.
 */

/**
 * Allows code in /blog to inject html. When CW_IS_WP_BLOG
 * is false, does nothing. Not necessary top use but I think
 * it will be a bit cleaner than putting WordPress logic
 * in many places. This way, some WordPress related code
 * can stay in /blog
 *
 * callback_function_name can return or echo its output.
 *
 * In order to not conflict with other WordPress filters,
 * will use a "cw_" prefix.
 *
 * @param $filter_name
 */
function cw_print_wp_blog_filter( $filter_name ) {
	if ( CW_IS_WP_BLOG ) {
		echo apply_filters( $filter_name, "" );
	}
}