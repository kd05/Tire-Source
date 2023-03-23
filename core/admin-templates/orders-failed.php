<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

page_title_is( 'Orders' );

$page_num = gp_if_set( $_GET, 'page_num' );
$per_page = get_per_page_preference( 'admin' );

$pg = new Pagination_Stuff( $page_num, $per_page );
$orders = get_un_successful_orders( $pg->offset, $pg->per_page );

$orders = array_map( function( $r ){

	$r = gp_make_array( $r );
	$order = DB_Order::create_instance_or_null( $r );
	$r = gp_fill_array( $order->to_array_for_admin_tables(), $r );

	$r['order_id'] = gp_get_link( get_admin_single_edit_link( DB_orders, $r['order_id'] ), $r['order_id'] );
    $r['transaction_id'] = gp_get_link( get_admin_single_edit_link( DB_transactions, $r['transaction_id'] ), $r['transaction_id'] );

	return $r;

}, $orders );

cw_get_header();
Admin_Sidebar::html_before();

echo '<div class="admin-section general-content">';
echo '<h1>Failed Orders</h1>';
echo '<p>When a user tries to checkout and payment is declined, they will sometimes get a basic message. The table below shows a bit more information. For the full set of all available information you may need to log in to your payment gateway and search for the reference number below.</p>';
echo '<p>Transactions can fail due to an incorrect address, insufficient funds, or for many other reasons. The items below are informational and do not indicate that action is required on your part.</p>';
echo '<p>Some columns of interest are: response_code, response_message, avs_result, cvd_result, auth_code, trans_id, reference_number. To understand the exact meanings of the data, you can refer to the documentation of your payment gateway. AVS refers to address verification services, and CVD means card validation digits (a.k.a. cvv).</p>';
echo '<p><a href="' . get_admin_page_url( 'orders' ) . '">Click here</a> to view successful orders.</p>';

// pagination and per page inputs
echo with( new Pagination_Stuff( $page_num, $per_page, get_sql_found_rows() ) )->get_page_controls_html();

// pagination..
echo render_html_table_admin( false, $orders );
echo '</div>';


Admin_Sidebar::html_after();
cw_get_footer();




