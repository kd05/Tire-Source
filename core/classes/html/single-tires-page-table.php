<?php

/**
 * Class Single_Tires_Page_Table
 */
Class Single_Tires_Page_Table extends Single_Products_Page_Table {

	/**
	 * Single_Tires_Page_Table constructor.
	 */
	public function __construct( $columns, $args = array(), $vehicle = null, $package_id = null, $part_number = null ) {
		parent::__construct( $columns, $args, $vehicle, $package_id, $part_number );
		$this->class_type = 'tire';
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

		if ( ! $data[ 'front' ] instanceof DB_Tire ) {
			throw new Exception( 'Invalid front data' );
		}

		if ( $data[ 'staggered' ] && ! $data[ 'rear' ] instanceof DB_Tire ) {
			throw new Exception( 'Invalid rear data' );
		}

		// whether or not we have a vehicle, make this object with 1 or 2 tires
		$data['vqdr'] = Vehicle_Query_Database_Row::create_instance_from_products( $data['front'], $data['rear'], null, null );

		// this isnt needed anymore
		//		$product_exists = gp_if_set( $data, 'product_exists' );
		//		$vehicle_exists = gp_if_set( $data, 'vehicle_exists' );
		//		$vehicle_complete = gp_if_set( $data, 'vehicle_complete' );
		//		$part_number_current = gp_if_set( $data, 'part_number_current' );
		//		$fitment_current = gp_if_set( $data, 'fitment_current' );

		// convert existing string to array, leave existing array, or initialize empty array
		$add_class             = gp_if_set( $data, 'add_class', '' );
		$add_class             = $add_class ? gp_make_array( $add_class ) : array();
		$data[ 'add_class' ]   = $add_class;
		$data[ 'add_class' ][] = $data[ 'staggered' ] ? 'is-staggered' : 'not-staggered';

		// let the css do the logic for when to highlight the row.
		//		// note that similar logic is repeated inside the function to get the add to cart button text
		//		$data['add_class'][] = $product_exists ? 'product-exists' : 'product-does-not-exist';
		//		$data['add_class'][] = $vehicle_exists ? 'vehicle-exists' : 'vehicle-does-not-exist';
		//		$data['add_class'][] = $vehicle_complete ? 'vehicle-complete' : 'vehicle-not-complete';
		//		$data['add_class'][] = $part_number_current ? 'part-number-current' : 'part-number-not-current';
		//		$data['add_class'][] = $fitment_current ? 'fitment-current' : 'fitment-not-current';

		$this->rows[] = $data;
	}

	/**
     * @param $key
     * @param $row
     * @return string
     */
	protected function get_cell_data( $key, $row ) {

		$staggered = $row[ 'staggered' ];

		/** @var DB_Tire $front */
		$front = $row[ 'front' ];

		/** @var DB_Tire|null $rear */
		$rear = $row[ 'rear' ];

		/** @var Vehicle_Query_Database_Row $vqdr */
		$vqdr = $row['vqdr'];

		switch ( $key ) {
			case 'stock':
			case 'stock_mobile':
				$v1 = $vqdr->get_item_stock_amount_html( VQDR_INT_TIRE_1 );
				$v2 = $vqdr->is_staggered() ? $vqdr->get_item_stock_amount_html( VQDR_INT_TIRE_2 ) : false;
				$ret = wrap_array_elements_not_empty_in_p( [ $v1, $v2 ], $this->cell_separator );
				break;
			case 'part_number':

				$p1 = $front ? $front->get( 'part_number' ) : '';
				// $u1 = $front->get_url_with_part_number();

				$ret = '';
				// $ret .= '<p><a href="' . $u1 . '">' . gp_test_input( $p1 ) . '</a></p>';
                $ret .= '<p>' . gp_test_input( $p1 ) . '</p>';

				if ( $staggered ) {
					$ret .= $this->cell_separator;
					$p2  = $rear ? $rear->get( 'part_number' ) : '';
					// $u2  = $rear->get_url_with_part_number();
					// $ret .= '<p><a href="' . $u2 . '">' . gp_test_input( $p2 ) . '</a></p>';
                    $ret .= '<p>' . gp_test_input( $p2 ) . '</p>';
				}

				break;
			case 'oem':

				//				$oem = gp_if_set( $row, 'oem' );
				//
				//				if ( $oem ) {
				//					$ret = '<span class="oem-indicator is-oem"><i class="far fa-check-circle"></i></span>';
				//				} else {
				//					$ret = '<span class="oem-indicator not-oem"><i class="fa fa-check"></i></span>';
				//				}

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

				$v1 = $front->get_size();
				$v2 = $staggered ? $rear->get_size() : '';

				$ret = wrap_array_elements_not_empty_in_p( [ $v1, $v2 ], $this->cell_separator );

				break;
			case 'utog':

				$v1 = $front->get( 'utog' );
				$v2 = $staggered ? $rear->get( 'utog' ) : '';

				$ret = wrap_array_elements_not_empty_in_p( [ $v1, $v2 ], $this->cell_separator );

				break;
			// spec probably isn't used anymore
			case 'spec':
			case 'spec_mobile':

				$v1 = $front->get_spec();
				$v2 = $staggered ? $rear->get_spec() : '';

				$ret = wrap_array_elements_not_empty_in_p( [ $v1, $v2 ], $this->cell_separator );

				break;
			case 'load_index':

				$v1 = $front->get_load_index_text();
				$v2 = $staggered ? $rear->get_load_index_text() : '';

				$ret = wrap_array_elements_not_empty_in_p( [ $v1, $v2 ], $this->cell_separator );

				break;
			case 'speed_rating':

				$v1 = $front->get( 'speed_rating' );
				$v2 = $staggered ? $rear->get( 'speed_rating' ) : '';

				$ret = wrap_array_elements_not_empty_in_p( [ $v1, $v2 ], $this->cell_separator );

				break;
			case 'price_mobile':
			case 'add_to_cart':

				$items = array();

				if ( $staggered ) {

					$pkg_temp_id = 1; // can be anything, so long as its the same among items

					$items[] = array(
						'type' => 'tire',
						'part_number' => $front->get( 'part_number' ),
						'quantity' => 2,
						'pkg_temp_id' => $pkg_temp_id,
						'loc' => 'front',
					);

					$items[] = array(
						'type' => 'tire',
						'part_number' => $rear->get( 'part_number' ),
						'quantity' => 2,
						'pkg_temp_id' => $pkg_temp_id,
						'loc' => 'rear',
					);

				} else {

					$items[] = array(
						'type' => 'tire',
						'part_number' => $front->get( 'part_number' ),
						'quantity' => $vqdr->get_item_atc_adjusted_qty( VQDR_INT_TIRE_1 ),
						'loc' => 'universal',
					);
				}

				$atc_text = 'Add To Cart';
				// $atc_text = $this->get_add_to_cart_text( $row );

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

						// <p>, OR, <p><p.separator><p>
						// followed by div.button-wrapper
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