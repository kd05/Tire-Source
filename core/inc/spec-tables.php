<?php

/**
 * @param       $rows
 * @param       $type
 * @param array $rows
 * @param array $args
 */
function spec_table_general( $rows, $type, $args = array() ) {

	$cls = [ 'spec-table', 'type-' . $type ];
	$cls[] = gp_if_set( $args, 'add_class' );
	$title = gp_if_set( $args, 'title' );
	$link_title = gp_if_set( $args, 'link_title' );

	// start html
	$op = '';
	$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';

	// we need a table wrap to align title to the left side of the table using flex
	// when showing 2 tables in a row, when .spec-table becomes width 50%;
	$op .= '<div class="table-wrap">';

	// front/rear sometimes
	if ( $title ) {
		$t = $link_title ? '<a href="' . $link_title . '" target="_blank"">' . $title . '</a>' : $title;
		$op .= '<p class="st-title">' . $t . '</p>';
	}

	$op .= '<table>';

	if ( $rows && is_array( $rows ) ){
		foreach ( $rows as $key=>$row ) {

			$slug = gp_if_set( $row, 'slug', $key );
			$left = gp_if_set( $row, 'left', '' );
			$left = trim( $left );
			$right = gp_if_set( $row, 'right', '' );
			$right = trim( $right );

			// html is valid inside $left and $right so no sanitation can be done here,
			if ( ( $left || $right ) && $slug ) {

				$op .= '<tr class="row-' . $slug . '">';

				$op .= '<th>';
				$op .= $left;
				$op .= '</th>';

				$op .= '<td>';
				$op .= $right;
				$op .= '</td>';

				$op .= '</tr>';


				// old way
				//				$op .= '<div class="st-row row-' . $slug . '">';
				//				$op .= '<p class="st-left">' . $left . '</p>';
				//				$op .= '<p class="st-right">' . $right . '</p>';
				//				$op .= '</div>';
			}
		}
	}

	$op .= '</table>';

	$op .= '</div>'; // table wrap
	$op .= '</div>'; // spec table

	return $op;
}

/**
 * For low stock threshold:
 *
 * Note: $vqdr contains $product
 *
 * @param DB_Tire                    $product
 * @param string                     $title
 * @param null                       $fields
 * @param array                      $args
 * @param Vehicle_Query_Database_Row $vqdr
 * @param                            $vqdr_item_in - VQDR_INT_TIRE_1, or VQDR_INT_TIRE_@
 *
 * @return string
 */
function spec_table_tires( DB_Tire $product, Vehicle_Query_Database_Row $vqdr, $vqdr_item_int, $title = '', $fields = null) {

	// not so sure how much we actually use the default fields here even though
	// sometimes we might pass in $fields to be the same thing.
	$fields_default = [ 'size', 'load_index', 'speed_rating', 'price', 'stock' ];
	$fields = $fields !== null ? $fields : $fields_default;

	$args['title'] = $title;
	$rows = array();

	// build rows, while filling in defaults
	if ( $fields && is_array( $fields ) ){
		foreach ( $fields as $key=>$field ) {

			if ( is_array( $field ) ) {
				$field['slug'] = gp_if_set( $field, 'slug', $key );
				$rows[] = $field;
			} else if ( gp_is_singular( $field ) ){

				$left = '';
				$right = '';

				switch( $field ) {
					case 'stock':
						$left = 'Availability: ';
						$right = $vqdr->get_item_stock_amount_html( $vqdr_item_int );
						break;
					case 'part_number':
						$left = 'SKU:';
						$right = '<a target="_blank" href="' . $product->get_url_with_part_number() . '">' . $product->get( 'part_number', null, true ) . '</a>';
						break;
					case 'size':
						$left = 'Size:';
						$right = gp_test_input( $product->get( 'size' ) );
						break;
					case 'speed_rating':
						$left = 'Speed Rating:';
						$right = gp_test_input( $product->get( 'speed_rating' ) );
						break;
					case 'load_index':
						$left = 'Load Index:';
						$right = gp_test_input( $product->get( 'load_index' ) );
						break;
					case 'spec':
						$left = 'Spec:';
						$right = gp_test_input( $product->get_spec() );
						break;
					case 'wall':
						// what is this anyways?
						break;
					case 'price':
						$left = 'Price (ea):';
						$right = $product->get_price_dollars_formatted();
						break;
					case 'type':
						$left = 'Type:';
						$type = $product->model->get( 'type' );
						$type_name = get_tire_type_name( $type );

						$right = '';
						$right .= '<span class="icon" title="' . gp_test_input( $type_name ) . '">';
						$right .= get_tire_type_icon( $type );
						$right .= '</span>';
						break;
					case '':
						break;
				}

				if ( $left || $right ) {
					$rows[$field] = array(
						'slug' => $field,
						'left' => $left,
						'right' => $right,
					);
				}
			}
		}
	}

	return spec_table_general( $rows, 'tire', $args );
}

/**
 * @param DB_Rim                     $product
 * @param string                     $title
 * @param null                       $fields
 * @param array                      $args
 * @param Vehicle|null               $vehicle
 * @param Vehicle_Query_Database_Row $vqdr
 * @param                            $vqdr_item_int
 *
 * @return string
 */
function spec_table_rims( DB_Rim $product, Vehicle_Query_Database_Row $vqdr, $vqdr_item_int, $title = '', $fields = null, $args = array(), Vehicle $vehicle = null ) {

	// not so sure how much we actually use the default fields here even though
	// sometimes we might pass in $fields to be the same thing.
	$fields_default = [ 'size', 'offset', 'bolt_pattern', 'price', 'type', 'stock' ];
	$fields = $fields !== null ? $fields : $fields_default;

	$args['title'] = $title;
	$rows = array();

	// build rows, while filling in defaults
	if ( $fields && is_array( $fields ) ){
		foreach ( $fields as $key=>$field ) {

			if ( is_array( $field ) ) {
				$field['slug'] = gp_if_set( $field, 'slug', $key );
				$rows[] = $field;
			} else if ( gp_is_singular( $field ) ){

				$left = '';
				$right = '';

				switch( $field ) {
					case 'stock':
						$left = 'Availability: ';
						$right = $vqdr->get_item_stock_amount_html( $vqdr_item_int );
						break;
					case 'part_number':
						$left = 'SKU:';
						$right = '<a target="_blank" href="' . $product->get_url_with_part_number() . '">' . $product->get( 'part_number' ) . '</a>';
						break;
					case 'size':
						$left = 'Size:';
						$right = gp_test_input( $product->get( 'size' ) );
						break;
					case 'offset':
						$left = 'Offset:';
						$right = gp_test_input( $product->get_offset() );
						break;
					case 'bolt_pattern':

						$str = $product->get_bolt_pattern_text( null, null, $vehicle, true );
						// $str = $product->get_bolt_pattern_text();

						$left = 'Bolt Pattern:';

						$right = gp_test_input( $str );
						break;
					case 'price':
						$left = 'Price (ea):';
						$price = $product->get_price_dollars_formatted();
						$right = print_price_dollars( $price, ',', '$', '' );
						break;
					case 'type':
						// "winter" rims are now: steel or alloy, but with only color_1/finish/primary color, and not finish 2 or 3
						if ( $product->is_winter_approved() ) {
							$left = 'Winter Approved:';
							$right = '<span class="icon">' . get_tire_type_icon( 'winter' ) . '</span>';
						}
						break;
					case 'colors':
						$left = 'Colour(s):';
						$right = $product->get_finish_string();
						break;
					case '':
						break;
				}

				if ( $left || $right ) {
					$rows[$field] = array(
						'slug' => $field,
						'left' => $left,
						'right' => $right,
					);
				}
			}
		}
	}

	return spec_table_general( $rows, 'rim', $args );
}


