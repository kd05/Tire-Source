<?php

/**
 * @return array
 */
function get_order_history_table_cols() {

	$ret = array(
		'date' => 'Date',
		'order_id' => 'Order ID',
		'total' => 'Total',
		'status' => 'Status',
		'see_more' => '&nbsp;',
	);

	return $ret;
}

/**
 * @param $str
 *
 * @return bool|DateTime|string
 */
function try_to_get_date_time( $str ) {

	$str = trim( $str );

	if ( ! $str ) {
		return false;
	}

	try{
		$dt = new DateTime( $str );
		return $dt;
	} catch ( Exception $e ) {
		return false;
	}
}

/**
 * @param $str
 * @param $format
 */
function convert_date_string_to_format( $str, $format ){
	try{
		$dt = new DateTime( $str );
		$ret = $dt->format( $format );
		return $ret;
	} catch ( Exception $e ) {
		return false;
	}
}

function get_order_status_map(){

	// the checkout script at the time of writing this, sets the status to "payment_received" on
	// successful transactions. I don't know if maybe the client would prefer "processing".
	$map = array(
		'payment_received' => 'Payment Received',
		'processing' => 'Processing',
		'in_transit' => 'In Transit',
		'completed' => 'Completed',
		'refunded' => 'Refunded',
		'returned' => 'Returned',
		'exchanged' => 'Exchanged',
	);

	return $map;
}

/**
 * will store order status probably as lower case str with underscore in DB
 *
 * @param $str
 */
function map_order_status_to_text( $str, $default = false  ) {
	$map = get_order_status_map();
	$ret = gp_if_set( $map, $str, $default );
	return $ret;
}

/**
 * @param DB_User $user
 */
function get_order_history_from_user( DB_User $user ) {

	$ret = array();

	$db = get_database_instance();
	$p = [];
	$q = '';
	$q .= 'SELECT * ';
	$q .= 'FROM ' . $db->orders . ' AS orders ';
	$q .= 'INNER JOIN ' . $db->transactions . ' AS trans ON orders.transaction_id = trans.transaction_id ';
	$q .= 'WHERE 1 = 1 ';

	$q .= 'AND orders.user_id = :user_id ';
	$p[] = [ 'user_id', $user->get( 'user_id' ) ];

	$q .= 'AND ( trans.success = "1" OR trans.success = 1 ) ';
	$q .= 'ORDER BY orders.order_date DESC ';
	$q .= ';';

	$results = $db->get_results( $q, $p );

	if ( $results ) {
		foreach ( $results as $row ) {

			$order = DB_Order::create_instance_via_primary_key( gp_if_set( $row, 'order_id' ) );

			// the SQL should take care of these properly but why not just assert anyways
			assert( $order->get( 'user_id' ) == $user->get_primary_key_value() );
			assert( ( $order->transaction->get( 'success' ) ) );

			$status = $order->get( 'order_status' );

			$ret[] = array(
				'order_id' => $order->get_primary_key_value(),
				'date' => convert_date_string_to_format( $order->get( 'date' ), 'M d, Y' ),
				'status' => map_order_status_to_text( $status, gp_test_input( $status ) ),
				'total' => print_price_dollars( $order->transaction->get( 'total' ), ',', '$', $order->transaction->get( 'locale' ) ),
				'see_more' => html_link( get_front_end_single_order_link( $order->get( 'order_id' ) ), 'Details' ),
			);
		}
	}

	return $ret;
}

/**
 * @param $order_id
 */
function get_front_end_single_order_link( $order_id ) {
	$ret = cw_add_query_arg( [ 'id' => (int) $order_id ], get_url( 'order_details' ) );
	return $ret;
}

/**
 * @param $msg
 */
function add_session_alert( $msg ) {

	if ( ! isset( $_SESSION['alerts'] ) ) {
		$_SESSION['alerts'] = array();
	}

	$_SESSION['alerts'][] = $msg;
}

/**
 * @return bool|mixed
 */
function get_session_alerts(){
	return gp_if_set( $_SESSION, 'alerts', array() );
}

/**
 * The idea is that whenever we have alerts stored in session, we want to try
 * to display them to the user and then delete them. If we get them, delete them, but
 * fail to display them, then the messages are lost. I would like to include in every ajax response.
 * This would work fine in the php to include it in the response, but our ajax code unfortunately has
 * different response handling functions (quite a few of them), and also when errors occur, then the
 * message will be lost. Basically, the plan is to just print this html on page load, and then show it
 * in a lightbox.
 *
 * @return string
 */
function get_session_alerts_html_and_remove(){

	$arr = get_session_alerts();
	$arr = gp_force_array( $arr );
	$arr = array_filter( $arr );

	$ret = $arr ? gp_array_to_paragraphs( $arr ) : '';

	delete_session_alerts();
	return $ret;
}

/**
 *
 */
function delete_session_alerts(){
	if ( isset( $_SESSION['alerts'] ) ) {
		$_SESSION['alerts'] = array();
	}
}

