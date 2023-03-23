<?php

/**
 * @param $type
 *
 * @return bool
 */
function clear_compare_queue( $type ) {
	if ( isset( $_SESSION['compare'][$type] ) ) {
		unset( $_SESSION['compare'][$type] );
		return true;
	}
	return false;
}

/**
 *
 */
function remove_from_compare_queue( DB_Product $product ){

	if ( $product instanceof DB_Rim ) {
		$type = 'rim';
	} else if ( $product instanceof DB_Tire ) {
		$type = 'tire';
	} else {
		return false;
	}

	$part_number = $product->get( 'part_number', null, true );

	$queue = isset( $_SESSION['compare'][$type] ) ? $_SESSION['compare'][$type] : array();
	$queue = gp_make_array( $queue );

	if ( $queue && is_array( $queue ) ) {
		foreach ( $queue as $q1=>$q2 ) {
			$pn = gp_if_set( $q2, 'part_number' );
			if ( $pn == $part_number ) {
				unset( $queue[$q1] );
			}
		}
	}

	$_SESSION['compare'][$type] = $queue;
}


/**
 * @param DB_Product $product
 */
function add_to_compare_queue( DB_Product $product ){

	if ( $product instanceof DB_Rim ) {
		$type = 'rim';
	} else if ( $product instanceof DB_Tire ) {
		$type = 'tire';
	} else {
		return false;
	}

	$part_number = $product->get( 'part_number', null, true );

	$queue = isset( $_SESSION['compare'][$type] ) ? $_SESSION['compare'][$type] : array();
	$queue = gp_make_array( $queue );

	// unset the same product, we'll add it onto the end
	if ( $queue ) {
		foreach ( $queue as $q1=>$q2 ){
			$pn = gp_if_set( $q2, 'part_number' );

			if ( $pn == $part_number ) {
				unset( $queue[$q1] );
			}
		}
	}

	$queue[] = array(
		'type' => $type,
		'part_number' => $product->get( 'part_number', null, true ),
		'time' => time(), // we may set an expiry at some point
	);

	// limit to 10 items. we may lower this as well.. 10 is pretty high...
	array_slice( $queue, -10, 10 );
	$queue = array_values( $queue );

	// update the session
	$_SESSION['compare'][$type] = $queue;

	return true;
}

/**
 * @param $type
 */
function get_compare_queue_items( $type ) {

	$items = isset( $_SESSION['compare'][$type] ) ? $_SESSION['compare'][$type] : array();
	$items = gp_force_array( $items );
	$ret = array();

	if ( $items ) {
		foreach ( $items as $index=>$item ) {
			$part_number = get_user_input_singular_value( $item, 'part_number' );
			$product = $type === 'tire' ? DB_Tire::create_instance_via_part_number( $part_number ) : DB_Rim::create_instance_via_part_number( $part_number );

			if ( ! $product || ! $product->sold_and_not_discontinued_in_locale() ) {
				continue;
			}

			$ret[$index] = $item;
		}
	}

	return $ret;
}

/**
 * @param $type
 * @param array $args
 * @return string
 */
function render_compare_queue( $type, $args = array() ) {

	$op = '';
	$items_html = '';
	$hidden_inputs = '';

	$type = gp_test_input( $type );
	$items = get_compare_queue_items( $type );

	$item_count = count( $items );
	$one_item = count( $items ) === 1;
	$no_items = ! count( $items );

	$is_tire = $type === 'tire';
	$is_rim = ! $is_tire;

	$hover_titles = '';
	$title = $is_tire ? 'Compare Tires' : 'Compare Wheels';
	$sub = '';
	$action = $is_tire ? BASE_URL . '/compare-tires.php' : BASE_URL . '/compare-wheels.php';

	// begin html
	$compare_queue = [ 'compare-queue' ];
	$compare_queue[] = $is_tire ? 'type-tires' : 'type-rims';
	$compare_queue[] = $one_item ? 'one-item' : '';
	$compare_queue[] = $no_items ? 'no-items' : '';
	$op .= '<div class="' . gp_parse_css_classes( $compare_queue ) . '">';

	$op .= '<div class="cq-titles general-titles">';
	$op .= '<h2 class="main">' . $title . '</h2>';

	$sub = '';

	if ( $one_item ) {
		if ( $is_tire ) {
			$op .= '<button class="css-reset sub-sm lb-close">Select at least 1 more tire to compare.</button>';
		} else {
			$op .= '<button class="css-reset sub-sm lb-close">Select at least 1 more wheel to compare.</button>';
		}
	} else if ( $no_items ) {
		if ( $is_tire ) {
			$op .= '<button class="css-reset sub-sm lb-close">There are no products in your compare queue.</button>';
		} else {
			$op .= '<button class="css-reset sub-sm lb-close">There are no products in your compare queue.</button>';
		}
	} else {
		// we leave this text for consistency.. lightbox looks too empty without this sub title.
		// so.. we'll just find something to say even if its not necessarily needed
		$op .= '<button class="css-reset sub-sm lb-close">Add more items or click compare.</button>';
	}

	$op .= '</div>';

	$cq_items = [ 'cq-items' ];
	$cq_items[] = count( $items ) > 0 ? '' : 'empty';

	if ( $items ) {

		$cc = 0;
		// cq-items
		$items_html .= '<div class="' . gp_parse_css_classes( $cq_items ) . '">';

		foreach ( $items as $item ) {

			$part_number = get_user_input_singular_value( $item, 'part_number' );
			$product = $type === 'tire' ? DB_Tire::create_instance_via_part_number( $part_number ) : DB_Rim::create_instance_via_part_number( $part_number );

			$cc++;
			$is_last = $cc === $item_count;

			if ( ! $product ) {
				continue;
			}

			$item_title = $product->get_cart_title();

			// print in form later
			$hidden_inputs .= '<input type="hidden" name="items[]" value="' . $part_number . '" checked>';

			$ht = [ 'item' ];
			$ht[] = $is_last ? 'is-current' : '';
			$hover_titles .= '<div class="' . gp_parse_css_classes( $ht ) . '" data-part-number="' . $part_number . '">';
			$hover_titles .= '<p class="title">' . $item_title . '</p>';
			$hover_titles .= '</div>';

			$cq_item = ['cq-item'];
			$cq_item[] = $is_last ? 'is-current' : '';

			$items_html .= '<div class="' . gp_parse_css_classes( $cq_item ) . '">';
			$items_html .= '<div class="cq-item-2">';

			$items_html .= '<div class="background-image contain" data-part-number="' . $part_number . '" title="' . $item_title . '" style="' . gp_get_img_style( $product->get_image_url() ) . '">';

			// remove button
			$items_html .= '<div class="item-remove">';

			$ajax_remove = add_compare_button_ajax_args( [] );
			$ajax_remove['type'] = $type;
			$ajax_remove['action'] = 'remove';
			$ajax_remove['part_number'] = $part_number;

			$btn = '';
			$btn .= '<span class="screen-reader-text">Remove</span>';
			$btn .= '<i class="fa fa-times"></i>';

			// <button>
			$items_html .= array_to_html_element( 'button', array(
				'type' => 'button',
				'class' => 'css-reset compare-products',
				'data-ajax' => $ajax_remove,
			), true, $btn );

			$items_html .= '</div>'; // item-remove

			$items_html .= '</div>'; // background-image

			//			$op .= '<p>' . $product->get_cart_title() . '</p>';
			//			$op .= '<p>' . $part_number . '</p>';

			$items_html .= '</div>'; // cq-items-2
			$items_html .= '</div>'; // cq-item

		}

		$items_html .= '</div>'; // cq-items

	} else {
		//		$items_html .= '<div class="cq-no-items">';
		//		$items_html .= '<p>Please select some products to compare.</p>';
		//		$items_html .= '</div>';
	}

	$op .= $items_html;

	$op .= '<form class="cq-controls" method="get" action="' . $action . '" target="_blank">';
	$op .= $hidden_inputs;

	$op .= '<div class="cq-hover-titles">';
	$op .= $hover_titles;
	$op .= '</div>';

	// include div even if empty inside, for justify content space between
	$op .= '<div class="clear-all">';

	$ajax_clear = add_compare_button_ajax_args( [] );
	$ajax_clear['type'] = $type;
	$ajax_clear['action'] = 'clear';

	// <button>
	$op .= array_to_html_element( 'button', array(
		'type' => 'button',
		'class' => 'css-reset compare-products',
		'data-ajax' => $ajax_clear,
	), true, '[Remove All]' );

	$op .= '</div>'; // clear-all

	$op .= '<div class="submit">';

	$submit_cls = [ 'css-reset' ];
	$sub_cls[] = $one_item ? 'disabled' : '';
	$submit_cls[] = $no_items ? 'disabled' : '';

	$op .= array_to_html_element( 'button', array(
		'type' => 'submit',
		'class' => 'css-reset',
		$one_item || $no_items ? 'disabled' : '',
	), true, '[Compare]' );

	$op .= '</div>'; // submit

	$op .= '</div>'; // compare-queue
	$op .= '';

	return $op;

}


/**
 * @param       $part_number
 * @param array $args
 *
 * @return string
 */
function get_compare_button_add_tire( $part_number, $args = array() ) {
	$args['action'] = 'add';
	$args['type'] = 'tire';
	$args['part_number'] = $part_number;
	return get_compare_button( $args );
}

/**
 * @param       $part_number
 * @param array $args
 *
 * @return string
 */
function get_compare_button_add_rim( $part_number, $args = array() ) {
	$args['action'] = 'add';
	$args['type'] = 'rim';
	$args['part_number'] = $part_number;
	return get_compare_button( $args );
}

/**
 * @param array $args
 */
function add_compare_button_ajax_args( $args = array() ) {
	$args['url'] = AJAX_URL;
	$args['nonce'] = get_nonce_value( 'compare' );
	$args['ajax_action'] = 'compare';
	return $args;
}

/**
 * @param array $args
 */
function get_compare_button( $args = array() ) {

	$type = get_user_input_singular_value( $args, 'type' );
	$part_number = get_user_input_singular_value( $args, 'part_number' );
	$text = gp_if_set( $args, 'text', 'Compare' );

	$ajax = add_compare_button_ajax_args( [] );
	$ajax['type'] = $type;
	$ajax['action'] = gp_if_set( $args, 'action', 'add' );
	$ajax['part_number'] = $part_number;

	$op = '';
	$op .= '<div class="compare-products-button-wrapper">';
	$op .= '';

	$inner = '';
	$inner .= '<i class="fa fa-plus-circle"></i>';
	$inner .= '<span class="text">' . $text . '</span>';

	$op .= '<button class="css-reset compare-products type-' . $type . '" data-ajax="' . gp_json_encode( $ajax ) . '">' . $inner . '</button>';
	$op .= '</div>';

	return $op;

}

/**
 * @param       $items
 * @param array $args
 *
 * @return string
 */
function render_compare_rims( $items, $args = array() ) {
	return render_compare_products( 'rims', $items, $args );
}

/**
 * @param       $items
 * @param array $args
 */
function render_compare_tires( $items, $args = array() ) {
	return render_compare_products( 'tires', $items, $args );
}

/**
 * @param DB_Tire $p
 * @param         $cell
 */
function get_compare_tires_cell_data( DB_Tire $p, $cell ) {

	switch ( $cell ) {
		case 'part_number':
			$url = $p->get_url_with_part_number();
			$t = $p->get( 'part_number', null, true );
			$r = '<a target="_blank" href="' . $url . '">' . $t . '</a>';
			return $r;
		case 'brand':
			return $p->brand->get( 'name', null, true );
		case 'model':
			return $p->model->get( 'name', null, true );
		case 'img':
			$r = '';
			$r .= '<div class="img-wrap">';
			$r .= '<a target="_blank" href="' . $p->get_url_with_part_number() . '" class="background-image contain" style="' . gp_get_img_style( $p->get_image_url() ) . '"></div>';
			$r .= '</div>';
			return $r;
		case 'price':
			return $p->get_price_dollars_formatted();
		case 'size':
			$r = $p->get_size();
			return gp_test_input( $r );
		case 'width':
			return $p->get( 'width', null, true );
		case 'profile':
			return $p->get( 'profile', null, true );
		case 'diameter':
			return $p->get( 'diameter', null, true );
		case 'type':
			return get_tire_type_text_and_icon( $p->model->type->get( 'slug' ));
		case 'class':
			return get_tire_class_text_and_icon( $p->model->class->get( 'slug' ) );
		case 'category':
			return $p->model->category->get( 'name', null, true );
		case 'speed_rating':
			return $p->get( 'speed_rating', null, true );
		case 'load_index':
			return $p->get( 'load_index', null, true );
	}

	return '';
}

/**
 * @param DB_Rim $p
 * @param         $cell
 */
function get_compare_rims_cell_data( DB_Rim $p, $cell ) {

	switch ( $cell ) {
		case 'part_number':
			$url = $p->get_url_with_part_number();
			$t = $p->get( 'part_number', null, true );
			$r = '<a target="_blank" href="' . $url . '">' . $t . '</a>';
			return $r;
		case 'brand':
			return $p->brand->get( 'name', null, true );
		case 'model':
			return $p->model->get( 'name', null, true );
		case 'img':
			$r = '';
			$r .= '<div class="img-wrap">';
			$r .= '<a target="_blank" href="' . $p->get_url_with_part_number() . '" class="background-image contain" style="' . gp_get_img_style( $p->get_image_url() ) . '"></div>';
			$r .= '</div>';
			return $r;
			break;
		case 'price':
			return $p->get_price_dollars_formatted();
		case 'size':
			$r = $p->get_size_with_offset_string();
			return gp_test_input( $r );
		case 'type':
			return get_rim_type_name( $p->get( 'type' ) );
		case 'color_1':
			return $p->finish->get( 'color_1_name' );
		case 'color_2':
			return $p->finish->get( 'color_2_name' );
		case 'finish':
			return $p->finish->get( 'finish' );
		case 'style':
			$r = get_rim_style_name( $p->get( 'style' ) );
			return $r;
		case 'diameter':
			return $p->get( 'diameter', null, true );
		case 'width':
			return $p->get( 'width', null, true );
		case 'offset':
			return $p->get_offset_with_mm();
		case 'bolt_pattern':

			$v = get_primary_vehicle_instance();
			return $p->get_bolt_pattern_text( null, null, $v, true );

		case 'hub_bore':
			return $p->get_center_bore_with_mm();
		case 'winter_approved':
			$r = $p->is_winter_approved() ? get_winter_approved_html() : '';
			return $r;
	}

	return '';
}

/**
 * @param       $type
 * @param       $items
 * @param array $args
 *
 * @return string
 */
function render_compare_products( $type, $items, $args = array() ) {

	$is_tire = $type === 'tires';
	$is_rim  = ! $is_tire;

	if ( $is_tire ) {
		$fields = array(
			'img' => '',
			'part_number' => 'Part Number',
			'brand' => 'Brand',
			'model' => 'Model',
			'price' => 'Price',
			'size' => 'Size Full',
			'width' => 'Tread Width',
			'profile' => 'Profile',
			'diameter' => 'Diameter',
			'type' => 'Type',
			'class' => 'Class',
			'category' => 'Category',
			'speed_rating' => 'Speed Rating',
			'load_index' => 'Load Index',
		);
	} else {

		$fields = array(
			'img' => '',
			'part_number' => 'Part Number',
			'price' => 'Price',
			'brand' => 'Brand',
			'model' => 'Model',
			'color_1' => 'Primary Colour',
			'color_2' => 'Secondary Colour',
			'finish' => 'Finish',
			'style' => 'Style',
			'size' => 'Size Full',
			'diameter' => 'Diameter',
			'width' => 'Width',
			'offset' => 'Offset',
			'bolt_pattern' => 'Bolt Pattern(s)',
			'hub_bore' => 'Hub Bore',
			'type' => 'Type',
			'winter_approved' => 'Winter Approved',
		);
	}

	$products = array();
	if ( $items && is_array( $items ) ) {
		foreach ( $items as $ii=>$part_number ) {
			$part_number = gp_test_input( $part_number );
			if ( $is_tire ) {
				$product = DB_Tire::create_instance_via_part_number( $part_number );
			} else {
				$product = DB_Rim::create_instance_via_part_number( $part_number );
			}

			// probably we'll check this already before we get to this point.
			if ( ! $product->sold_and_not_discontinued_in_locale() ) {
				continue;
			}

			if ( $product ) {
				$products[] = $product;
			}
		}
	}

	$op = '';
	$op .= '<div class="compare-products-wrap">';

	$title = $is_tire ? 'Compare Tires' : 'Compare Wheels';

	$op .= '<div class="cp-wrap-2">';

	// title doesn't work well due to putting overflow on table, lets just
	// use a top page title instead
//	$op .= '<div class="cp-title general-titles">';
//	$op .= '<h1 class="main">' . $title . '</h1>';
//	$op .= '</div>';

	$op .= '<div class="table-overflow">';
	$op .= '<table>';

	if ( $fields && is_array( $fields ) ) {

		foreach ( $fields as $f1=>$f2 ) {

			$op .= '<tr class="row-' . $f1 . '">';

			$op .= '<th>' . $f2 . '</th>';

			if ( $products ){
				foreach ( $products as $product ) {
					$cell = '';
					if ( $is_tire ) {
						$cell = get_compare_tires_cell_data( $product, $f1 );
					} else if ( $is_rim ) {
						$cell = get_compare_rims_cell_data( $product, $f1 );
					}

					$op .= '<td class="">';
					$op .= $cell;
					$op .= '</td>';
				}
			}

			$op .= '</tr>';
		}

	}

	$op .= '</table>';
	$op .= '</div>'; // table overflow
	$op .= '</div>'; // cp-wrap-2
	$op .= '</div>'; // compare-products-wrap

	return $op;
}