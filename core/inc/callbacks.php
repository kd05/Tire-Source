<?php

/**
 * @param $row
 *
 * @return string
 */
function render_table_callback_reviews_get_review_id( $row ){
	$id = gp_if_set( $row, 'review_id' );
	$url = cw_add_query_arg( [ 'review' => $id ], get_admin_page_url( 'reviews' ) );
	return '<a href="' . $url . '">' . $id . ' (edit)</a>';
}

/**
 * @param $row
 *
 * @return string
 */
function render_table_callback_reviews_get_message( $row ){
	$message = gp_if_set( $row, 'message' );
	$message = gp_sanitize_textarea( $message );
	$excerpt = gp_excerptize( $message, 20 );
	$ret = '<span title="' . gp_test_input( $message ) . '">' . $excerpt . '</span>';
	return $ret;
}
