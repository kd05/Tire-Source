<?php

Class Cart_Page {

	/** @var Cart  */
	public $cart;

	/** @var  Cart_Item|null */
	protected $current_item;

	/** @var  Cart_Package|null */
	protected $current_package;

	/** @var  DB_Tire|null */
	protected $current_tire;

	/** @var  DB_Rim|null */
	protected $current_rim;

	/**
	 * Cart_Page constructor.
	 *
	 * @param Cart $cart
	 */
	public function __construct( $cart ) {
		$this->cart = $cart;
		
		// we will need full DB_Tire and DB_Rim objects to render images and (maybe) compute prices
		$this->cart->link_item_products_to_database_tables();
	}

	/**
	 * @return Cart
	 */
	public function get_cart(){
		return $this->cart;
	}

	/**
	 * @return string
	 */
	public function get_item_vehicle_title(){
		if ( $this->current_package && $this->current_package->vehicle_and_fitment_is_valid() ) {
			return $this->current_package->vehicle->get_display_name();
		}
		return '';
	}

	/**
	 * @return string
	 */
	public function get_item_vehicle_size_text(){

		if ( $this->current_package && $this->current_package->fitment ) {
			return $this->current_package->fitment->get_fitment_name();
		}

		return '';
	}

	/**
	 *
	 */
	public function get_quantity_select(){
		$v = $this->current_item->quantity;
		$op = '';
		$op .= '<input type="number" min="0" max="99" value="' . (int) $v . '" step="1">';
		return $op;
	}

	/**
	 * @param $item
	 *
	 * @return string
	 */
	public function get_install_kit_item_description( $item ) {
		return $this->cart->get_install_kit_item_description( $item );
	}

	/**
	 * @param $item
	 *
	 * @return string
	 */
	public function get_mount_balance_item_description( $item ) {
		return $this->cart->get_mount_balance_item_description( $item );
	}

	/**
	 * @param $item Cart_Item
	 * @param $package
	 *
	 * @return string
	 */
	public function render_item( $item, $package = null ){

		$item = $this->cart->get_item( $item );

		if ( ! $item || ! $item instanceof Cart_Item ) {
			return  '';
		}

		if ( $item->type === 'mount_balance' && DISABLE_MOUNT_BALANCE ) {
			return '';
		}

		// dynamic css classes array
		$cls = array( 'cart-item' );

		$package = $package instanceof Cart_Package ? $package : null;

		$has_tires = $package && $package->has_tires();
		$has_rims = $package && $package->has_rims();
		$quantity = $item->quantity && $item->quantity !== "0" ? (int) $item->quantity : 0;

		// I believe this is redundant:
//		if ( ! $quantity ) {
//			if ( $item->type === 'mount_balance' ) {
//				if ( ! $has_tires || ! $has_rims ) {
//					return '';
//				}
//			}
//			if ( $item->type === 'install_kit' ) {
//				if ( ! $has_rims ) {
//					return '';
//				}
//			}
//		}

		// leave this empty to render the item the normal way.
		// add text to it to render something completely different.
		$inner_html = '';

		if ( ( $item->type === 'mount_balance' || $item->type === 'install_kit' ) && ! $quantity ){

			// class for form item wrapper outside of this if statement
			$cls[] = 'item-simple';

			// simplify to "type 1" or "type 2"
			$t1 = $item->type === 'mount_balance';
			$t2 = $item->type === 'install_kit';

			$enabled = $t1 ? ( $this->cart->get_suggested_mount_balance_quantity( $package ) > 0 ) : ( $this->cart->get_suggested_install_kit_quantity( $package ) > 0 );

			$_cls = $t1 ? [ 'add-mount-balance'] : ['add-install-kit'];
			$_cls[] = $enabled ?  'enabled' : 'disabled';

			$inner_html = '';
			$inner_html .= '<div class="' . gp_parse_css_classes( $_cls ) . '">';

			if ( $t1 ) {
				$btn_text = '<span class="text">Add Mount & Balance</span>';

				if ( app_get_locale() == 'US' ) {
					$disabled_text = '<p class="msg">Not available when shipping region is U.S.</p>';
				} else {
					$disabled_text = '<p class="msg">Requires rims and tires.</p>';
				}

			} else if ( $t2 ) {
				$btn_text = '<span class="text">Add Accessories Kit</span>';
				$disabled_text = '<p class="msg">Requires at least 4 rims.</p>';
			} else{
				$btn_text = '';
				$disabled_text = '';
			}

			if ( $enabled ) {

				$ajax_action = 'update_cart';

				$btn_args = array(
					'url' => AJAX_URL,
					'nonce' => get_nonce_value( $ajax_action ),
					'item' => $item->id,
					'ajax_action' => $ajax_action,
					'action' => $t1 ? 'add_mount_balance' : 'add_install_kit',
				);

				$inner_html .= '<button type="button" class="update-btn ajax-update-cart-btn css-reset" data-update-cart="' . gp_json_encode( $btn_args ) .'">';
				$inner_html .= '<i class="fa fa-plus-circle"></i>';
				$inner_html .= $btn_text;
				$inner_html .= '</button>';

			} else {

				$inner_html .= '<p class="update-btn">';
				$inner_html .= '<i class="fa fa-plus-circle"></i>';
				$inner_html .= $btn_text;
				$inner_html .= '</p>';

				// sometimes empty
				$inner_html .= $disabled_text;
			}

			$inner_html .= '</div>'; // add-mount-balance/add-install-kit

		}

		$this->current_item = $item;
		$this->current_package = $package;
		$this->current_rim = $item->type === 'rim' ? DB_Rim::create_instance_via_part_number( $item->part_number, false, true ) : null;
		$this->current_tire = $item->type === 'tire' ? DB_Tire::create_instance_via_part_number( $item->part_number, false, true ) : null;

		$type = $this->current_item->type;

		// add some more css classes
		$cls[] = 'type-' . gp_test_input( $type );
		$cls[] = $has_tires ? 'pkg-has-tires' : 'pkg-no-tires';
		$cls[] = $has_rims ? 'pkg-has-rims' : 'pkg-no-rims';
		$cls[] = $this->cart->is_item_in_stock( $item->id ) ? 'in-stock' : 'out-of-stock';

		$op = '';
		$op .= '<div class="' . gp_parse_css_classes( $cls ) . '" data-id="' . $item->id . '">';
		$op .= '<div class="item-inner">';

		if ( $inner_html ) {
			// Render a small cart item... just like a line of text, for example "Add mount balance"
			$op .= $inner_html;
		} else {

			// *** render a full cart item, with 3 columns, image, details, controls. ***

			$img_url = $item->get_cart_img_url();
			$title = $item->get_cart_title();
			$title_2 = $item->get_cart_title_2();
			$sku = $item->get_cart_sku();
			$location = $item->get_cart_location_text();

			$price = $item->get_price_raw();
			$price_formatted = print_price_dollars_formatted( $price );

			$vehicle_title = $this->get_item_vehicle_title();
			$vehicle_size = $this->get_item_vehicle_size_text();

			gp_set_global( 'require_fancybox', true );

			$op .= '<div class="col col-img">';
			$op .= '<div class="col-inner">';
			$op .= '<div class="img-wrap">';

			if ( $item->is_tire() ) {
				$caption = $item->db_tire->brand_model_name();
			} else if ( $item->is_rim() ) {
				$caption = $item->db_rim->brand_model_finish_name();
			} else {
				$caption = '';
			}

			$op .= '<a data-caption="' . $caption . '" href="' . $img_url . '" class="background-image contain" data-fancybox="cart-item-' . $item->id . '" style="' . gp_get_img_style( $img_url ) . '"><i class="fa fa-search-plus"></i></a>';

			// $op .= '<img src="' . $img_url . '">';
			$op .= '</div>';
			$op .= '</div>'; // col-inner
			$op .= '</div>'; // col-img

			$op .= '<div class="col col-text">';
			$op .= '<div class="col-inner">';

			$url = false;

			if ( $item->type === 'tire' && $item->get_db_tire() && $item->db_tire ){
				$url = $item->db_tire->get_url_with_part_number();
			}

			if ( $item->type === 'rim' && $item->get_db_rim() && $item->db_rim ){
				$url = $item->db_rim->get_url_with_part_number();
			}

			if ( $title ) {
				if ( $url ) {
					$op .= '<p class="title"><a href="' . $url . '">' . gp_test_input( $title ) . '</a></p>';
				} else {
					$op .= '<p class="title">' . gp_test_input( $title ) . '</p>';
				}
			}

			if ( $title_2 ) {
				$op .= '<p class="title-2">' . gp_test_input( $title_2 ) . '</p>';
			}

			if ( $sku ) {
				$op .= '<p class="sku">SKU: ' . gp_test_input( $sku ) . '</p>';
			}

			if ( $location ) {
				$op .= '<p class="location">' . gp_test_input( $location ) . '</p>';
			}

			if ( ! $package ) {

				$choose_vehicle_html = call_user_func( function() use( $item ){

					$ret = '';

					if ( $item->db_rim ) {

                        $url = $item->db_rim->get_url( false, [
                            'replace' => $item->id
                        ]);

                    } else if ( $item->db_tire ) {

                        $url = $item->db_tire->get_url( false, [
                            'replace' => $item->id
                        ]);

                    } else{
					    return '';
                    }

					$lightbox_id = 'choose-vehicle-' . $item->id;
					$ret .= '<p class="no-vehicle"><a href="" class="lb-trigger" data-for="' . $lightbox_id . '">Choose Vehicle (Recommended)</a></p>';

					$ret .= '<p class="optional-item-text">You must choose your vehicle to qualify for our fitment guarantee.</p>';

					$ret .= get_change_vehicle_lightbox_content( $lightbox_id, array(
						'title' => 'Choose Vehicle',
						'tagline' => 'You must choose your vehicle to qualify for our fitment guarantee.',
						'hide_shop_for' => true,
						'base_url' => $url,
					) );

					return $ret;
				});

				$op .= $choose_vehicle_html;
			}

			if ( $vehicle_title ) {
				$op .= '<p class="vehicle-title">' . gp_test_input( $vehicle_title ) . '</p>';
			}

			if ( $vehicle_size ) {
				$op .= '<p class="vehicle-size">' . gp_test_input( $vehicle_size ) . '</p>';
			}

			if ( $item->type === 'mount_balance' ) {
				$op .= '<p class="optional-item-text">' . $this->get_mount_balance_item_description( $item ). '</p>';
				$op .= '<p class="warning">Only purchase this if you do not require a tire-pressure monitoring system (TPMS).</p>';
			}

			if ( $item->type === 'install_kit' ) {
				$op .= '<p class="optional-item-text">' . $this->get_install_kit_item_description( $item ). '</p>';
			}

//			$op .= '<div class="mobile-price-qty">';
//			$op .= '<p class="price-mobile">' . $price_formatted . '</p>';
//			$op .= '<p class="qty-mobile">' . $price_formatted . '</p>';
//			$op .= '</div>';

			if ( $package && ( $type === 'rim' || $type === 'tire' ) ) {
				$upgrades = $package->analyze_for_upgrades();
				if ( $upgrades ) {
					$op .= '<div class="item-upgrades">';
					$op .= $package->render_upgrades( $upgrades, 'item', $item->id );
					$op .= '</div>';
				}
			}

			// Check Stock Levels and show a message.
			if ( $item->type === 'tire' || $item->type === 'rim' ) {

				$this->cart->setup_stock_summary( true );
				$stock_data = $this->cart->stock_summary->get_data_block_from_item_id( $item->id );

				$stock = (int) $stock_data['stock'];

				if ( ! $stock_data['in_stock'] ) {

					// this will actually make it yellow, which should help it stand out cuz we use
					// red in a lot of other places already
					$cls = Stock_Level_Html::css_class( STOCK_LEVEL_LOW_STOCK );
					$icon = Stock_Level_Html::icon( STOCK_LEVEL_NO_STOCK );

					$_count = (int) count( $stock_data['item_ids'] );
					$_qty = (int) $stock_data['qty'];

					if ( $_count > 1 ) {
						$end = $_count . ' items in your cart make up for a total quantity of ' . $_qty . '.';
					} else {
						$end = 'You are attempting to purchase ' . $_qty . '.';
					}

					if ( $item->type === 'tire' ) {

						if ( $stock === 1 ) {
							$rm = '1 tire remaining';
						} else {
							$rm = $stock . ' tires remaining';
						}

					} else {
						if ( $stock === 1 ) {
							$rm = '1 rim remaining';
						} else {
							$rm = $stock . ' rims remaining';
						}
					}


					$text = 'Out of stock - ' . $rm . '. ' . $end;
					$op .= Stock_Level_Html::render_via_html_components( $cls, $icon, $text );

				}

			}

			// *** END THE 2nd COLUMN with the details ***

			$op .= '</div>'; // col-inner
			$op .= '</div>'; // col-text

			$op .= '<div class="col col-price">';
			$op .= '<div class="col-inner">';

			// use a .price-wrap in case we add a sale price later on
			$op .= '<div class="price-wrap">';
			$op .= '<p class="price">' . $price_formatted . '/ea</p>';
			$op .= '</div>';

			$op .= '<div class="qty-wrap">';
			$op .= $this->get_item_quantity_form( $item->id );
			$op .= '</div>';

			$op .= '<div class="remove-wrap">';
			$op .= $this->get_remove_cart_item_button( $this->current_item->id );
			$op .= '</div>';

			$op .= '</div>'; // col-inner
			$op .= '</div>'; // col-price

//			$op .= '<div class="col col-mobile-remove">';
//			$op .= '<div class="col-inner">';
//			$op .= '<div class="remove-wrap">';
//			$op .= $this->get_remove_cart_item_button( $this->current_item->id );
//			$op .= '</div>';
//			$op .= '</div>';
//			$op .= '</div>';

		}

		$op .= '</div>'; // item-inner
		$op .= '</div>'; // cart-item
		return $op;
	}

	/**
	 * To keep consistency in html, this renders both real packages that have vehicles
	 * and multiple items, as well as not-real packages that have exactly 1 non-packaged item.
	 *
	 * @param $package
	 *
	 * @return string
	 */
	public function render_package( $package, $items = null ) {

		if ( ! $package && $items && is_array( $items ) ){
			$packaged = false;
		} else if ( $package ){
			$package = $this->cart->get_package( $package ) ; // in case ID was passed in
			$packaged = true;
			$items = $this->cart->get_item_ids_by_package( $package, true );
		} else {
			return '';
		}

		$package_id = $package ? $package->id : '';

		$op = '';
		$package = $this->cart->get_package( $package );

		$cls = [ 'cart-package' ];
		$cls[] = $packaged ? 'packaged' : 'single-item';
		$cls[] = $package && $package->has_tires() ? 'has-tires' : 'no-tires';
		$cls[] = $package && $package->has_rims() ? 'has-rims' : 'no-rims';

		$cls[] = $this->cart->get_suggested_install_kit_quantity( $package ) > 0 ? 'install-kit-valid' : 'install-kit-not-valid';
		$cls[] = $this->cart->get_suggested_mount_balance_quantity( $package ) > 0 ? 'mount-balance-valid' : 'mount-balance-not-valid';

		$op .= '<div class="' . gp_parse_css_classes( $cls ) . '" data-id="' . $package_id . '">';
		if ( $items ) {
			foreach ( $items as $item ) {
				$op .= $this->render_item( $item, $package );
			}
		}
		$op .= '</div>';

		return $op;
	}

	/**
	 * @param $msg
	 */
	public function get_cart_alert( $msg, $item_cls = '' ) {

		$cls = [ 'cart-item item-simple'];
		$cls[] = $item_cls;

		$op = '';
		$op .= '<div class="cart-package cart-alert">';
		$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';
		$op .= '<div class="item-inner">';
		$op .= $msg;
		$op .= '</div>';
		$op .= '</div>';
		$op .= '</div>';
		return $op;
	}

	/**
	 *
	 */
	public function render_items() {

		if ( ! $this->cart->count_items() ) {
		    return '<div class="no-results"><p>There are no items in your cart.</p></div>';
        }

		$op = '';

		if ( ! $this->cart->is_every_item_in_stock() ) {
			$msg = 'Oops, it looks like some of the products in your cart are currently out of stock. Please remove or adjust the quantities of some of the highlighted items below.';
			$op .= $this->get_cart_alert( wrap_tag( $msg, 'p' ), 'out-of-stock' );
		}

		// $op .= get_dev_alert( 'cart', get_pre_print_r( $this->cart->to_array() ) );

		$package_ids = $this->cart->get_not_empty_package_ids();
		if ( $package_ids && is_array( $package_ids ) ) {
			foreach ( $package_ids as $package_id ) {
				$op .= $this->render_package( $package_id );
			}
		}

		$np_item_ids = $this->cart->get_non_packaged_item_ids();

		if ( $np_item_ids && is_array( $np_item_ids ) ) {
			foreach ( $np_item_ids as $item_id ) {
				// render "dummy" packages with 1 item each
				$op .= $this->render_package( null, array( $item_id ) );
			}
		}

		return $op;
	}

	/**
	 * @param $item_id
	 */
	public function get_item_quantity_form( $item_id ) {

		$item = $this->cart->get_item( $item_id );

		if ( ! $item ) {
			return '';
		}

		$op = '';
		$op .= '<form class="cart-qty-select ajax-update-cart-form" action="' . AJAX_URL . '">';

		$ajax_action = 'update_cart';
		$op .= get_hidden_inputs_from_array( array(
			'action' => 'set_quantity',
			'ajax_action' => $ajax_action,
			'nonce' => get_nonce_value( $ajax_action ),
			'item' => $item->id,
		) );

		$op .= '<div class="input-wrap">';
		$op .= '<input type="number" min="0" max="99" name="quantity" value="' . $item->quantity . '">';
		$op .= '</div>';

		$op .= '<div class="update-wrap">';
		$op .= '<button class="css-reset" type="submit">Update</button>';
		$op .= '</div>';

		$op .= '</form>';

		return $op;
	}

	/**
	 * @param $item_id
	 *
	 * @return string
	 */
	public function get_remove_cart_item_button( $item_id ) {
		$data = $this->get_remove_cart_item_array( $item_id );
		$op   = '';
		$inner = '';
		$inner .= '<i class="fa fa-times-circle"></i>';
		$inner .= '<span class="text inherit">Remove</span>';
		$op   .= '<button class="ajax-update-cart-btn css-reset" data-update-cart="' . gp_json_encode( $data ) . '">' . $inner . '</button>';

		return $op;
	}

	/**
	 * @param $item_id
	 */
	public function get_remove_cart_item_array( $item_id ) {
		$data = array(
			'url' => AJAX_URL,
			'nonce' => get_nonce_value( 'update_cart' ),
			'ajax_action' => 'update_cart',
			'action' => 'remove_item',
			'item' => $item_id,
		);
		return $data;
	}
}