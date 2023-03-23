<?php

/**
 * Used on the cart page for orders currently in the cart, but also
 * at on the order details page when showing an order from a previous date.
 *
 * - You have to pass in raw price values without formatting, so that we can check if the ontario tire fee is greater than zero
 * - You have to pass in the locale, because on the cart page its the current locale, but in order details it needs to be the locale
 * that the order was processed in.
 * - You have to pass in the ontario fee text because on the cart page we show detailed info including quantity and price, but in
 * order details, we only show the resulting amount because in the DB we store qty and total but not price of each.
 * - You probably don't want to change $t_sep or $d_sign... they are already what we use across the site.
 *
 * @see get_ontario_tire_levy_cart_text()
 *
 * @param      $subtotal
 * @param      $shipping
 * @param      $tax
 * @param      $total
 * @param null $ontario_fee
 */
function render_cart_receipt_from_primitive_values( $subtotal, $shipping, $tax, $total, $locale, $ontario_fee = 0, $ontario_fee_text = '',  $t_sep = ',', $d_sign = '$' ) {

	ob_start();

	?>
	<div class="cart-receipt">
		<table>
			<tr class="row-subtotal">
				<th>Sub-Total:</th>
				<td><?php echo print_price_dollars( $subtotal, $t_sep, $d_sign, $locale ); ?></td>
			</tr>
			<tr class="row-shipping">
				<th>Shipping:</th>
				<td><?php echo print_price_dollars( $shipping, $t_sep, $d_sign, $locale ); ?></td>
			</tr>
            <?php
            // this is now taxes, so show it before the tax amount.
            if ( $ontario_fee > 0 ) {
                ?>
                <tr class="row-subtotal">
                    <th><?php echo $ontario_fee_text; ?></th>
                    <td><?php echo print_price_dollars( $ontario_fee, $t_sep, $d_sign, $locale ); ?></td>
                </tr>
                <?php
            }
            ?>
			<tr class="row-tax">
				<th>Tax:</th>
				<td><?php echo print_price_dollars( $tax, $t_sep, $d_sign, $locale ); ?></td>
			</tr>
			<tr class="row-total">
				<th>Total:</th>
				<td><?php echo print_price_dollars( $total, $t_sep, $d_sign, $locale ); ?></td>
			</tr>
		</table>
	</div>

	<?php
	return ob_get_clean();
}

/**
 * @param DB_Order $order
 *
 * @return string
 */
function get_front_end_order_details_receipt( DB_Order $order ) {

	$ret = render_cart_receipt_from_primitive_values( $order->transaction->get( 'subtotal' ), $order->transaction->get( 'shipping' ), $order->transaction->get( 'tax' ), $order->transaction->get( 'total' ), $order->get( 'locale' ), $order->transaction->get( 'ontario_fee' ), get_ontario_tire_levy_cart_text( true ) );

	return $ret;
}

/**
 * @param DB_Order $order
 */
function get_front_end_order_details_items_table( DB_Order $order, $table_args = array() ) {

	$cols = array(
		'item' => 'Item',
		'quantity' => 'Quantity',
		'price' => 'Price',
	);

	$data = get_order_items_table_data_via_order( $order, array_keys( $cols ), '<br>' );

	$ret = render_html_table( $cols, $data, $table_args );

	return $ret;
}

/**
 * Queries the database for order items based on the provided $order.
 *
 * Will take care of ordering the results in a reasonable way...
 *
 * For example.. we may show all wheels then all tires..
 *
 * I think this is how it works now.. but in the future it might be better
 * to show items ordered by package basically..
 *
 * After that.. returns an array of $data that you *might* want to use
 * in a table. Can't explain the return value in words.. just print it or w/e..
 *
 * For example, the return value of this function might be the 2nd parameter of
 * render_html_table()
 *
 * @param DB_Order $order
 */
function get_order_items_table_data_via_order( DB_Order $order, $col_keys = null, $glue = '<br>' ) {

	// define the default column keys here which needs to include all possible keys used below.
	if ( $col_keys === null ) {
		$col_keys = array(
			'item',
			'quantity',
			'price'
		);
	}

	// only null means to use default column keys, not false or w/e
	if ( ! $col_keys ) {
		return array();
	}

	$items = get_order_items_from_order_id( $order->get( 'order_id' ), true );

	$ret = array_map( function ( $item ) use ( $col_keys, $glue ) {

		/** @var DB_Order_Item $item */
		$_i = array();

		foreach ( $col_keys as $key ) {

			$result = null;

			switch ( $key ) {
				case 'item':
					$result = implode( $glue, $item->summary_table_cell_data( $key ) );
					break;
				case 'quantity':
					$result = implode( $glue, $item->summary_table_cell_data( $key ) );
					break;
				case 'price':
					$result = implode( $glue, $item->summary_table_cell_data( $key ) );
					break;
			}

			$_i[ $key ] = $result;
		}

		return $_i;

	}, $items );

	return $ret;
}