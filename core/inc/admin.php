<?php

/**
 * @param      $order_id
 * @param bool $construct_objects
 *
 * @return array|array[]DB_Order_Item
 */
function get_order_items_from_order_id( $order_id, $construct_objects = false, $order_by_preset = '' ) {

	$db = get_database_instance();
	$p  = [];
	$q  = '';
	$q  .= 'SELECT * ';
	$q  .= 'FROM ' . $db->order_items . ' AS items ';
	// $q .= 'LEFT JOIN ' . $db->order_vehicles . ' AS vehicles ON vehicles.order_vehicle_id = items.order_vehicle_id ';

	$q   .= 'WHERE items.order_id = :order_id ';
	$p[] = [ 'order_id', $order_id ];

	$q .= '';

	switch( $order_by_preset ) {
        case 'not_sure_we_need_this...':
            $order_by = [ 'order_item_id ASC' ];
            break;
        default:

	        // show packaged items first, then non-packages items
	        // show rims before tires, then install kit and mount balance..
	        // show front tires/rims before rear tires/rims
	        // lastly.. put the item ID just to ensure consistency
	        // should more or less do it ..
	        $order_by = array(
		        'package_id > 0 DESC',
		        'package_id ASC',
		        'type = "rim" DESC',
		        'type = "tire" DESC',
		        'type = "install_kit" DESC',
		        'type = "mount_balance" DESC',
		        'loc = "universal" DESC',
		        'loc = "front" DESC',
		        'loc = "rear" DESC',
		        'order_item_id ASC',
	        );

            break;
    }

    $q .= 'ORDER BY ' . implode_comma( $order_by ) . ' ';

	$q .= ';';

	$results = $db->get_results( $q, $p );

	if ( $construct_objects ) {
	    $results = gp_make_array( $results );
	    $results = array_map( function( $v ){
	        return DB_Order_Item::create_instance_or_null( $v );
        }, $results );
    }

	return $results;
}

//$_user_id = gp_if_set( $order_array, 'user_id' );
//if ( $_user_id ) {
//	$order_array['user_id'] = '<a href="' . get_admin_single_user_url( $_user_id ) . '">' . $_user_id . ' (edit)</a>';
//}

/**
 * @param DB_Order $order
 */
function render_admin_order_items( DB_Order $order ) {

	$op = '';

	$items = get_order_items_from_order_id( $order->get( 'order_id' ) );

	$items_by_package     = array();
	$package_vehicles_ids = array();

	// filter the columns included in the table for the query results for order items
	if ( $items ) {
		foreach ( $items as $ii ) {

			$item = DB_Order_Item::create_instance_or_null( $ii );

			/// $row = array();

			$package_id = $item->get( 'package_id' );
			$package_id = $package_id ? $package_id : '';

			//		$row['package_id'] = $package_id;
			//		$row['name'] = $item->get( 'name' );
			//		$row['part_number'] = $item->get( 'part_number' );

			$row = $item->to_array( [], [ 'supplier', 'part_number', 'type', 'name', 'loc', 'quantity', 'price' ] );

			$items_by_package[ $package_id ][]   = $row;
			$package_vehicles_ids[ $package_id ] = $item->get( 'order_vehicle_id' );
		}
	}

	$p_count = 0;

	if ( $items_by_package ) {
		foreach ( $items_by_package as $package_id => $items ) {

			$op .= '<hr>';

			if ( $package_id ) {
				$p_count ++;

				$vehicle_table_title = 'Package #' . $p_count . '  - Vehicle';
				$items_table_title   = 'Package #' . $p_count . ' - Items';

				// $op .= '<h3>Package #' . $p_count . '</h3>';
				// $op .= '<p>The first table shows vehicle information, the second table is items that were purchased for that vehicle</p>';
			} else {
				// $op .= '<h3>Items Not belonging to a Vehicle</h3>';

				$vehicle_table_title = '';
				$items_table_title   = 'Items Not Packaged';

			}

			$order_vehicle_id = gp_if_set( $package_vehicles_ids, $package_id );
			$order_vehicle    = $order_vehicle_id ? DB_Order_Vehicle::create_instance_via_primary_key( $order_vehicle_id ) : false;

			if ( $order_vehicle ) {
			    // vehicle table array
				$_v = $order_vehicle->to_array( [], [
					'vehicle_name',
					'fitment',
					'sub',
					'fitment_name',
					'sub_name',
					'bolt_pattern',
					'lock_text',
					'lock_type',
					'center_bore',
					'oem',
					'staggered',
				] );

				// vehicle table html
				$op .= render_html_table_admin( false, array( $_v ), [ 'title' => $vehicle_table_title ] );
			}

			// items table html
			// render all the order items, this applies to both packages with a vehicle and fake "packages" without a vehicle.
			$op .= render_html_table_admin( false, $items, [ 'title' => $items_table_title ] );
		}
	}

	return $op;
}

/**
 * @param $order '
 */
function render_admin_successful_order_details( DB_Order $order, DB_Transaction $transaction ) {

	$op = '';

	$shipping_is_billing = $order->get( 'shipping_is_billing' );

	$billing = $order->get_billing_address_summary_array( true, true, true );

	$op .= '<p><strong>Billing Address</strong></p>';
	$op .= '<p>' . implode( '<br>', $billing ) . '</p>';

	if ( $order->get( 'ship_to' ) == 'address' ) {
		if ( $shipping_is_billing ) {
			$op .= '<p><strong>Shipping Address is the same.</strong></p>';
		} else {
			$op       .= '<p><strong>Shipping Address</strong></p>';
			$shipping = $order->get_shipping_address_summary_array( true, true, true );
			$op       .= '<p>' . implode( '<br>', $shipping ) . '</p>';
		}
	}

	// Order Table
	$order_array = $order->to_array_for_admin_tables( [], [
		'order_id',
		'user_id',
		'order_status',
		'order_date',
		'locale',
		'email',
		'register',
		'ship_to',
		'admin_email_sent',
		'user_email_sent'
	] );

	$op .= '<hr>';

	// $order_array = $order->to_array( ['cart']);
	$op .= render_html_table_admin( false, [ $order_array ], [ 'title' => 'Order Details' ] );

	// $op .= '<h2>Transaction Details</h2>';

	// Transaction Table
	$op .= render_html_table_admin( false, [
		$transaction->to_array_for_admin_tables( [], [
			'trans_id',
			'reference_number',
			'auth_code',
			// 'card_type', // data seems to not be present
			'last_4',
			'currency',
			'subtotal',
			'tax',
			'shipping',
			'ontario_fee',
			'ontario_fee_qty',
			'total'
		] )
	], [
		'title' => 'Transaction Details',
	] );

	// get all the order items in several tables, broken down by package
	$op .= render_admin_order_items( $order );



	return $op;
}


/**
 * @param $order
 */
function render_admin_failed_order_details( DB_Order $order, DB_Transaction $transaction ) {

	$op = '';
	$op .= '';

	$op .= '<h2>Order Details</h2>';
	$op .= render_html_table_admin( false, [ $order->to_array( [ 'cart' ] ) ] );

	$op .= '<h2>Transaction Details</h2>';
	$op .= render_html_table_admin( false, [ $transaction->to_array() ] );

	return $op;
}

/**
 * @param string $page
 *
 * @param array $query_args
 * @return string
 * @see Admin_Controller::register_page();
 *
 */
function get_admin_page_url( $page = '', array $query_args = [] ) {
    return cw_add_query_arg( $query_args, Admin_Controller::get_url( $page ) );
}

/**
 * ie. orders whos transactions have success = 1
 */
function get_successful_orders( $offset, $per_page ) {

	$db  = get_database_instance();
	$p   = [];
	$q   = '';
	$q   .= 'SELECT SQL_CALC_FOUND_ROWS * ';
	$q   .= 'FROM ' . $db->orders . ' AS orders ';
	$q   .= 'INNER JOIN ' . $db->transactions . ' AS trans ON trans.transaction_id = orders.transaction_id ';
	$q   .= 'WHERE 1 = 1 ';
	$q   .= 'AND CAST( trans.success AS SIGNED ) = :trans_success ';
	$p[] = [ 'trans_success', 1, '%d' ];

	$q .= '';
	$q .= 'ORDER BY order_date DESC ';
	$q .= get_sql_limit( $offset, $per_page );
	$q .= ';';

	$results = $db->get_results( $q, $p );

	return $results;
}

/**
 * ie. orders whos transactions have success <> 1
 */
function get_un_successful_orders( $offset, $per_page ) {

	$db  = get_database_instance();
	$p   = [];
	$q   = '';
	$q   .= 'SELECT SQL_CALC_FOUND_ROWS * ';
	$q   .= 'FROM ' . $db->orders . ' AS orders ';
	$q   .= 'INNER JOIN ' . $db->transactions . ' AS trans ON trans.transaction_id = orders.transaction_id ';
	$q   .= 'WHERE 1 = 1 ';
	$q   .= 'AND CAST( trans.success AS SIGNED ) <> :trans_success ';
	$p[] = [ 'trans_success', 1, '%d' ];

	$q .= '';
	$q .= 'ORDER BY order_date DESC ';
	$q .= get_sql_limit( $offset, $per_page );
	$q .= ';';

	$results = $db->get_results( $q, $p );

	return $results;
}

/**
 * @param        $url
 * @param        $base
 * @param string $after
 * @param string $target
 *
 * @return string
 */
function get_edit_link( $url, $base, $after = ' (edit)', $target = '' ) {

	$text = $base;
	if ( $after ) {
		$text .= $after;
	}

	$op = '';
	$op .= '<a target="' . $target . '" href="' . $url . '">' . $text . '</a>';

	return $op;
}

/**
 * @param string $url
 * @param string $text
 * @param array  $args
 *
 * @return string
 */
function get_anchor_tag_simple( $url = '', $text = '', $args = array() ) {
	$args[ 'url' ]  = $url;
	$args[ 'text' ] = $text;

	return get_anchor_tag( $args );
}

/**
 * alias for get_anchor_tag_simple() with shorter name
 *
 * @param string $url
 * @param string $text
 * @param array  $args
 *
 * @return string
 */
function html_link( $url = '', $text = '', $args = array() ) {
    return get_anchor_tag_simple( $url, $text, $args );
}

/**
 * @param string $url
 * @param string $text
 * @param array  $args
 *
 * @return string
 */
function html_link_new_tab( $url = '', $text = '', $args = array() ) {
    $args['target'] = gp_if_set( $args,'target', '_blank' );
    return html_link( $url, $text, $args );
}

/**
 * ie. from an array.
 *
 * indeces: url, text, target, class
 *
 * @param $arr
 *
 * @return string
 */
function get_anchor_tag( $arr ) {

	$atts = [];

	if ( isset( $arr[ 'href' ] ) ) {
		$href = $arr[ 'href' ];
	} else if ( isset( $arr[ 'url' ] ) ) {
		$href = $arr[ 'url' ];
	} else {
		$href = '';
	}

    $atts[ 'href' ] = gp_sanitize_href( $href );

	$target = gp_if_set( $arr, 'target' );
	$class  = gp_if_set( $arr, 'class' );
	$title  = gp_if_set( $arr, 'title' );

	if ( $target ) {
		$atts[ 'target' ] = $target;
	}

	if ( $class ) {
		$atts[ 'class' ] = $class;
	}

	if ( $title ) {
		$atts[ 'title' ] = $title;
	}

	$inner = gp_if_set( $arr, 'text' );

	return array_to_html_element( 'a', $atts, true, $inner );
}

/**
 * @param $links
 */
function get_admin_links( $links ) {

	$links = gp_force_array( $links );

	$cls = [ 'admin-links' ];

	$cls[] = $links ? '' : 'empty';

	$op = '';
	$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';

	if ( $links ) {
		foreach ( $links as $link ) {
			$op .= '<p>' . get_anchor_tag( $link ) . '</p>';
		}
	}

	$op .= '';
	$op .= '</div>';

	return $op;
}

/**
 * for use on edit.php. some tables have their own template files and therefore,
 * this function might bring you to a page without any functionality
 *
 * users, 23 ... => ... '<a href=".../admin?page=edit&table=users&pk=23"></a>
 *
 * @param $table
 * @param $pk
 */
function get_admin_single_table_row_anchor_tag( $table, $pk, $after = ' (edit)' ) {
	$url = get_admin_single_table_row_url( $table, $pk );

	return get_edit_link( $url, $pk, $after, '' );
}

/**
 * @param $table
 * @param $primary_key
 */
function get_admin_single_table_row_url( $table, $primary_key ) {
	$url = get_admin_archive_link( $table, [ 'pk' => (int) $primary_key ] );

	return $url;
}


/**
 * @param $user_id
 */
function get_admin_single_user_url( $user_id ) {
	// this uses the edit.php template file and then does some file exists logic to include another
	$ret = cw_add_query_arg( [ 'pk' => $user_id ], get_admin_archive_link( 'users' ) );

	return $ret;
}

/**
 *
 */
function get_admin_single_order_url( $order_id ) {
	// this is its own template file
	$ret = cw_add_query_arg( [ 'order_id' => $order_id ], get_admin_page_url( 'order' ) );

	return $ret;
}


/**
 * @param $table
 * @param $col
 * @param $vaue
 */
function sql_count_columns_with( $table, $col, $value, $type = '%s' ) {

	$db = get_database_instance();

	$p   = [];
	$q   = '';
	$q   .= 'SELECT * ';
	$q   .= 'FROM ' . $table . ' ';
	$q   .= 'WHERE ' . gp_esc_db_col( $col ) . ' = :col ';
	$p[] = [ 'col', $value, $type ];
	$q   .= '';
	$q   .= ';';

	$r = $db->get_results( $q, $p );

	$ret = $r ? count( $r ) : 0;

	return $ret;
}

/**
 * You can use this to find orphaned foreign keys.
 *
 * Ie. select "rim_models" where "rim_model_id" is not one of the values of
 * "rims.model_id"
 *
 * @param $t1
 * @param $c1
 * @param $t2
 * @param $c2
 */
function sql_get_rows_not_in( $t1, $c1, $t2, $c2 ) {

	$db = get_database_instance();
	$p  = [];
	$q  = '';
	$q  .= 'SELECT * ';
	$q  .= 'FROM ' . gp_esc_db_table( $t1 ) . ' ';
	$q  .= 'WHERE ' . gp_esc_db_col( $c1 ) . ' NOT IN ( SELECT ' . gp_esc_db_col( $c2 ) . ' FROM ' . gp_esc_db_table( $t2 ) . ') ';
	$q  .= ';';

	$r = $db->get_results( $q, $p );

	return $r;
}

/**
 * don't call this on tables with composite primary keys, you may delete more than you expect. Also be aware
 * that for rims and tire models, the slug is not a primary key at all. Different brands can have models with
 * the same slug. Only the rim_model_id/tire_model_id is a singular pk.
 *
 * Note that it should be ok to delete rim brands not associated with rims, but that is associated with rim models.
 * This is tricky to explain, but with the way that the import works, we should be ok with this if everything works
 * as intended.
 *
 * @param $t1
 * @param $c1
 * @param $t2
 * @param $c2
 */
function delete_foreign_objects_form_table( $primary_table, $primary_key, $foreign_table, $foreign_key ) {

	$primary_table = gp_esc_db_table( $primary_table );
	$primary_key   = gp_esc_db_col( $primary_key );
	$foreign_table = gp_esc_db_table( $foreign_table );
	$foreign_key   = gp_esc_db_col( $foreign_key );

	$rows = sql_get_rows_not_in( $primary_table, $primary_key, $foreign_table, $foreign_key );
	$rows = gp_force_array( $rows );

	$count = 0;
	$rows  = array_map( function ( $row ) use ( $primary_table, $primary_key, &$count ) {

		$count ++;

		// likely stdClass
		$row = gp_force_array( $row );

		// put delete col first
		$row = array_merge( [ 'count' => $count, 'delete' => '' ], $row );

		$name  = 'delete_from_' . $primary_table . '[]';
		$value = gp_if_set( $row, $primary_key ); // column should be primary key

		$row[ 'delete' ] = '<input type="checkbox" name="' . $name . '" value="' . $value . '">';

		return $row;

	}, $rows );

	// html
	$op = '';
	$op .= '<form class="form-style-basic" method="post">';

	$op .= get_form_header( $primary_table );

	$op .= '<input type="hidden" name="nonce" value="' . get_nonce_value( 'admin_clean_tables', true ) . '">';

	// tell javascript what to do via data attribute
	$js_select_all   = [];
	$js_select_all[] = [
		'bind' => 'click',
		'action' => 'check_all',
		'closest' => 'form',
		'find' => '.cell-delete input',
	];

	$js_deselect_all   = [];
	$js_deselect_all[] = [
		'bind' => 'click',
		'action' => 'uncheck_all',
		'closest' => 'form',
		'find' => '.cell-delete input',
	];

	$op .= '<div class="form-table-controls">';
	$op .= '<button class="js-bind" data-bind="' . gp_json_encode( $js_select_all ) . '" type="button">Select All (for deletion)</button>';
	$op .= '<button class="js-bind" data-bind="' . gp_json_encode( $js_deselect_all ) . '" type="button">De-select All</button>';
	$op .= '<button type="submit">Delete Selected</button>';
	$op .= '</div>';

	$op .= render_html_table_admin( false, $rows, array() );
	$op .= '</form>';

	return $op;
}

/**
 * Be careful.. this updates the database as well as reads it. will clean script tags but allows other html...
 * just know what the fn. does before using it.
 *
 * Call this near the top of your script, after checking an admin is logged in, before header and everything else.
 *
 * PRINTS/echos the header and footer etc., does not return.
 *
 * @param $title
 * @param $option_key
 */
function print_simple_admin_page_to_edit_option_textarea( $title, $option_key ) {

	// handle post back
	$postback_response = '';
	if ( gp_if_set( $_POST, 'form_submitted', false ) ) {
		$updated           = cw_set_option( $option_key, gp_strip_script_tags( gp_if_set( $_POST, $option_key ) ) );
		$postback_response = $updated ? 'Updated' : 'No update made.';
	}

	cw_get_header();
	Admin_Sidebar::html_before();

	?>

    <div class="admin-section general-content gen-edit-<?php echo $option_key; ?>">
        <h1><?php echo $title; ?></h1>

        <form class="form-style-basic" method="post">
            <input type="hidden" name="form_submitted" value="1">

            <div class="form-items">

				<?php echo $postback_response ? get_form_response_text( $postback_response ) : ''; ?>

				<?php echo get_form_textarea( array(
					'add_class' => 'size-lg',
					'label' => 'Content',
					'name' => $option_key,
					// html is valid
					'value' => cw_get_option( $option_key, '' ),
				) );

				?>

				<?php echo get_form_submit(); ?>
            </div>
        </form>
    </div>

	<?php
	Admin_Sidebar::html_after();
	cw_get_footer();
}
