<?php

/**
 * Class Single_Rims_Page_Table
 */
Class Single_Rims_Page_Table extends Single_Products_Page_Table {

	/**
	 * Single_Rims_Page_Table constructor.
	 *
	 * @param array $columns
	 * @param array $args
	 */
	public function __construct( $columns, $args = array(), $vehicle = null, $package_id = null, $part_number = null ) {
		parent::__construct( $columns, $args, $vehicle, $package_id, $part_number );
		$this->class_type     = 'rim';
		$this->cell_separator = '<p class="sep"></p>';
	}

	/**
	 * @param $row
	 */
	public function add_row( $data ) {

		if ( ! isset( $data[ 'front' ] ) ) {
			throw new Exception( 'Invalid data' );
		}

		if ( ! isset( $data[ 'rear' ] ) ) {
			$data[ 'rear' ] = null;
		}

		if ( ! isset( $data[ 'staggered' ] ) ) {
			$data[ 'staggered' ] = false;
		}

		if ( ! $data[ 'front' ] instanceof DB_Rim ) {
			throw new Exception( 'Invalid front data' );
		}

		if ( $data[ 'staggered' ] && ! $data[ 'rear' ] instanceof DB_Rim ) {
			throw new Exception( 'Invalid rear data' );
		}

		// whether or not we have a vehicle, make this object with 1 or 2 rims
		$data['vqdr'] = Vehicle_Query_Database_Row::create_instance_from_products( null, null, $data['front'], $data['rear'] );

		// we pass these variables in to avoid running functions once per row, which may be wasteful..
		//		$product_exists = gp_if_set( $data, 'product_exists' );
		//		$vehicle_exists = gp_if_set( $data, 'vehicle_exists' );
		//		$vehicle_complete = gp_if_set( $data, 'vehicle_complete' );
		//		$part_number_current = gp_if_set( $data, 'part_number_current' );
		//		$fitment_current = gp_if_set( $data, 'fitment_current' );

		// we dont do highlighting crap anymore, instead, tables showing kind of make sense
		// convert existing string to array, leave existing array, or initialize empty array
		//		$add_class = gp_if_set( $data, 'add_class', '' );
		//		$add_class = $add_class ? gp_make_array( $add_class ) : array();
		//		$data['add_class'] = $add_class;
		//		$data['add_class'][] = $data['staggered'] ? 'is-staggered' : 'not-staggered';
		//
		//		// let the css do the logic for when to highlight the row.
		//		// note that similar logic is repeated inside the function to get the add to cart button text
		//		$data['add_class'][] = $product_exists ? 'product-exists' : 'product-does-not-exist';
		//		$data['add_class'][] = $vehicle_exists ? 'vehicle-exists' : 'vehicle-does-not-exist';
		//		$data['add_class'][] = $vehicle_complete ? 'vehicle-complete' : 'vehicle-not-complete';
		//		$data['add_class'][] = $part_number_current ? 'part-number-current' : 'part-number-not-current';
		//		$data['add_class'][] = $fitment_current ? 'fitment-current' : 'fitment-not-current';

		$this->rows[] = $data;
	}

    /**
     * @param         $key
     * @param $row
     * @return array|string
     */
	protected function get_cell_data( $key, $row ) {

		// skipping isset because exception would have already been thrown if these were not set
		$staggered = $row[ 'staggered' ];

		/** @var DB_Rim $front */
		$front = $row[ 'front' ];

		/** @var DB_Rim $rear */
		$rear = $row[ 'rear' ];

		/** @var Vehicle_Query_Database_Row $vqdr */
		$vqdr = $row['vqdr'];

		switch ( $key ) {
			case 'stock':
			case 'stock_mobile':
				$v1 = $vqdr->get_item_stock_amount_html( VQDR_INT_RIM_1 );
				$v2 = $vqdr->is_staggered() ? $vqdr->get_item_stock_amount_html( VQDR_INT_RIM_2 ) : false;
				$ret = wrap_array_elements_not_empty_in_p( [ $v1, $v2 ], $this->cell_separator );
				break;
			case 'part_number':

				$p1 = $front ? $front->get( 'part_number' ) : '';

				// $u1 = $front->get_url( [ 'part_number' => $front->get( 'part_number' ) ] );

				$ret = '';
				// $ret .= '<p><a href="' . $u1 . '">' . gp_test_input( $p1 ) . '</a></p>';
                $ret .= '<p>' . gp_test_input( $p1 ) . '</p>';

				if ( $staggered ) {
					$p2  = $rear ? $rear->get( 'part_number' ) : '';
					// $u2  = $rear->get_url( [ 'part_number' => $rear->get( 'part_number' ) ] );
					$ret .= $this->cell_separator;
					// $ret .= '<p><a href="' . $u2 . '">' . gp_test_input( $p2 ) . '</a></p>';
                    $ret .= '<p>' . gp_test_input( $p2 ) . '</p>';
				}

				break;
			case 'fitment':

				// oem might apply to non-sub (ie. parent) wheel set
				$oem = gp_if_set( $row, 'oem' );

				$sub_slug = gp_if_set( $row, 'sub_slug' );

				// The fitment slug is meaningless if we dont have a vehicle. The vehicle belongs to this class. If we're showing
				// multiple vehicles in the same table, then this will not work. Sub slug represents the sub size that
				// belongs to this classes vehicle with the fitment that matches the fitment slug.
				$fitment_slug = gp_if_set( $row, 'fitment_slug' );

				if ( $fitment_slug ) {
					if ( $sub_slug ) {

						$wheel_set = $this->vehicle && $this->vehicle->has_wheel_set() ? $this->vehicle->fitment_object->wheel_set->get_wheel_set_sub_by_slug( $sub_slug ) : false;

						// DO NOT pass in OEM here, this is for a sub size which is never OEM but its parent might be an OEM fitment
						return $wheel_set ? $wheel_set->get_product_table_fitment_name_html( $this->cell_separator, false ) : gp_test_input( $sub_slug );
					} else {
						$wheel_set = $this->vehicle ? $this->vehicle->get_wheel_set_via_fitment_slug( $fitment_slug ) : false;

						return $wheel_set ? $wheel_set->get_product_table_fitment_name_html( $this->cell_separator, $oem ) : gp_test_input( $fitment_slug );
					}
				}

				return '??';

				break;
			case 'size':

				$v1 = $front->get_size_with_offset_string();
				$v2 = $staggered ? $rear->get_size_with_offset_string() : '';

				$ret = wrap_array_elements_not_empty_in_p( [ $v1, $v2 ], $this->cell_separator );
				break;
			case 'offset':

				$v1 = $front->get( 'offset' );
				$v2 = $staggered ? $rear->get( 'offset' ) : '';

				$ret = wrap_array_elements_not_empty_in_p( [ $v1, $v2 ], $this->cell_separator );

				break;
			case 'bolt_pattern':

				$v1 = $front->get_bolt_pattern_text( null, null, $this->vehicle, true );
				$v2 = $staggered ? $rear->get_bolt_pattern_text( null, null, $this->vehicle, true ) : '';

				$ret = wrap_array_elements_not_empty_in_p( [ $v1, $v2 ], $this->cell_separator );

				break;
			case 'hub_bore':

				$v1 = $front->get( 'center_bore' );
				$v2 = $staggered ? $rear->get( 'center_bore' ) : '';

				$ret = wrap_array_elements_not_empty_in_p( [ $v1, $v2 ], $this->cell_separator );

				break;

			case 'price_mobile':
			case 'add_to_cart':

				$items = array();

				if ( $staggered ) {

					$pkg_temp_id = 1; // can be anything, so long as its the same among items

					$items[] = array(
						'type' => 'rim',
						'part_number' => $front->get( 'part_number' ),
						'quantity' => 2,
						'pkg_temp_id' => $pkg_temp_id,
						'loc' => 'front',
					);

					$items[] = array(
						'type' => 'rim',
						'part_number' => $rear->get( 'part_number' ),
						'quantity' => 2,
						'pkg_temp_id' => $pkg_temp_id,
						'loc' => 'rear',
					);

				} else {

					$items[] = array(
						'type' => 'rim',
						'part_number' => $front->get( 'part_number' ),
						'quantity' => $vqdr->get_item_atc_adjusted_qty( VQDR_INT_RIM_1 ),
						'loc' => 'universal',
					);

				}

				$atc_text = 'Add To Cart';

				$fitment_slug = gp_if_set( $row, 'fitment_slug' );
				$sub_slug     = gp_if_set( $row, 'sub_slug' );

				$button_html = $vqdr->item_set_is_purchasable() ? $this->get_add_to_cart_btn( $items, $atc_text, $fitment_slug, $sub_slug ) : '';

				switch ( $key ) {
					case 'add_to_cart':
						$ret = $button_html;
						break;
					// price, and a button in one cell for mobile
					case 'price_mobile':

						$v1 = $front->get_price_dollars_formatted();
						$v2 = $staggered ? $rear->get_price_dollars_formatted() : '';

						$price = wrap_array_elements_not_empty_in_p( [ $v1, $v2 ], $this->cell_separator );

						// 2 prices followed by possibly one button
						$ret = $price . $button_html;
						break;
				}

				break;
			case 'price':

				$v1 = $front->get_price_dollars_formatted();
				$v2 = $staggered ? $rear->get_price_dollars_formatted() : '';

				$price = wrap_array_elements_not_empty_in_p( [ $v1, $v2 ], $this->cell_separator );

				$ret = array(
					'value' => $price,
					// 'cell_after' => $this->get_add_to_cart_btn( $items, $text )
				);

				break;
			default:
				$ret = '';
		}

		return $ret;
	}
}