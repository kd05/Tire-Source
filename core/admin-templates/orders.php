<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

page_title_is( 'Successful Orders' );

$page_num = gp_if_set( $_GET, 'page_num' );
$per_page = get_per_page_preference( 'admin' );

$pg = new Pagination_Stuff( $page_num, $per_page );
$orders = get_successful_orders( $pg->offset, $pg->per_page );

$orders = array_map( function( $r ){

	$r = gp_make_array( $r );

	$order = DB_Order::create_instance_or_null( $r );

	$array = $order->to_array_for_admin_tables( [], [ 'order_id', 'order_status', 'order_date', 'total', 'locale', 'first_name', 'last_name', 'user_id', 'email', 'phone', 'company', 'ship_to', 'admin_email_sent', 'user_email_sent' ], false );

	$order_id = $order->get( 'order_id' );
	$array['order_id'] = get_anchor_tag_simple( get_admin_single_order_url( $order_id ), $order_id . ' (edit)' );
	$array['total'] = gp_if_set( $r, 'total' ); // this was from a join

    $array['kount_result'] = gp_test_input( $order->transaction->get_kount_inquiry_result_code( true ) );
    $array['kount_score'] = gp_test_input( $order->transaction->get_kount_score() );

	return $array;

}, $orders );

cw_get_header();
Admin_Sidebar::html_before();

echo '<div class="admin-section general-content">';
echo '<h1>Successful Orders</h1>';
echo '<p>Click "edit" to view detailed information for a single order. From there, you can also set the order status.</p>';

echo '<p>If admin_email_sent or user_email_sent equal to 0 means emails could not be sent, and you may want to contact the customer.</p>';

echo '<p><a href="' . get_admin_page_url( 'orders_failed' ) . '">Click here</a> to view failed orders.</p>';


// pagination and per page inputs
echo with( new Pagination_Stuff( $page_num, $per_page, get_sql_found_rows() ) )->get_page_controls_html();

// pagination..
echo render_html_table_admin( false, $orders );
echo '</div>';


Admin_Sidebar::html_after();
cw_get_footer();

