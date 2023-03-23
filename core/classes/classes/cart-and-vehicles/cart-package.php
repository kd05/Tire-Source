<?php

/**
 * Class Cart_Package
 */
Class Cart_Package extends Export_As_Array {

	public $id; // link to cart items

	// in the context of the cart, the vehicle is simplified, and a fitment belongs
	// to the package, rather than to the vehicle.
	/** @var  Cart_Vehicle */
	public $vehicle;

	/** @var  Fitment_Singular */
	public $fitment;

	/**
	 * I don't even know if we'll need this.
	 *
	 * @var  Vehicle|null
	 */
	protected $vehicle_complete;

	/** @var  array|null */
	protected $upgrades;

	/**
	 * See comments for $user_allows_mount_balance
	 *
	 * @var
	 */
	public $user_allows_install_kit;

	/**
	 * Update: this may not work how its described below.
	 *
	 * We default this to true. When a user manually removes their accessories kit, this becomes false, until
	 * the state of the package changes in a way that we decide we should set it back to true. This way, in general,
	 * whenever the package state changes, or at literally any other time,  we can call sync_additional_items().
	 *
	 * Possible Cart_Package state changes:
	 * 1. Increasing/Decreasing quantities (may or may not revert value to true)
	 * 2. Adding/removing Tires/Rims, which may or may not render the package "complete" afterwards (probably reverts
	 * value to true).
	 * 3. Removing mount & balance and/or accessories kits. (will not revert value to true)
	 *
	 * In case you are wondering why this name is stupid, its because $can_add_mount_balance means something completely
	 * different, and will be a function instead.
	 *
	 * @var
	 */
	public $user_allows_mount_balance;

	/**
	 * Mark this as null before calling get_summary_data() to avoid checking cached results
	 *
	 * @var array
	 */
	public $summary_data;

	protected $props_to_export = array(
		'id',
		'vehicle',
		'fitment',
		'user_allows_install_kit',
		'user_allows_mount_balance',
	);

	/**
	 * Cart_Package constructor.
	 *
	 * @param $data
	 */
	public function __construct( $data ) {
		parent::__construct( $data );
	}

	/**
	 * @param $data
	 */
	public function init( $data ) {

		// both of these default to true, which is very important. We're going to be extra careful to handle null values
		// because isset and array_key_exists behave differently, and we just dont want null to end up meaning false.
		$user_allows_install_kit = gp_if_set( $data, 'user_allows_install_kit', null );
		$user_allows_install_kit = $user_allows_install_kit === null ? true : $user_allows_install_kit;
		$user_allows_install_kit = $user_allows_install_kit === "0" || $user_allows_install_kit === "false" ? false : $user_allows_install_kit;
		// we could enforce boolean but for now we're not going to just in case we ever decide to store a string value here instead.
		$this->user_allows_install_kit = $user_allows_install_kit;

		$user_allows_mount_balance = gp_if_set( $data, 'user_allows_mount_balance', null );
		$user_allows_mount_balance = $user_allows_mount_balance === null ? true : $user_allows_mount_balance; // if null then true
		$user_allows_mount_balance = $user_allows_mount_balance === "0" || $user_allows_mount_balance === "false" ? false : $user_allows_mount_balance;
		$this->user_allows_mount_balance = $user_allows_mount_balance;

		$this->id      = gp_if_set( $data, 'id' );

		// do not pass in an instance of Vehicle(), this contains way more information that we need
		// use a simple of array of only make/model/year/trim (+ make_name etc.) etc., or a Cart_Vehicle instance
		$vehicle       = gp_if_set( $data, 'vehicle', array() );

		$this->vehicle = $vehicle instanceof Cart_Vehicle ? $vehicle : new Cart_Vehicle( $vehicle );

		$fitment_arr = gp_if_set( $data, 'fitment', array() );
		$this->fitment = $fitment_arr instanceof Fitment_Singular ? $fitment_arr : new Fitment_Singular( $fitment_arr );
	}

	/**
	 * @param bool $order
	 *
	 * @return array
	 */
	public function get_item_ids( $order = false ){
		$cart = get_cart_instance();
		$items = $cart->get_item_ids_by_package( $this->id, $order );
		return $items;
	}

	/**
	 * @param bool $order
	 *
	 * @return array Cart_Item[]
	 */
	public function get_items( $order = false ){

		$cart = get_cart_instance();
		$items = array();

		$ids = $this->get_item_ids( $order );
		if ( $ids ) {
			foreach ( $ids as $id ) {
				$item = $cart->get_item( $id );
				if ( $item ) {
					$items[] = $item;
				}
			}
		}

		return $items;
	}

	/**
	 * The package type is the type of the tires belonging to the package, if tires exist, and
	 * otherwise false. We store the tire_type into the cart item automatically on insert
	 * so that we don't have to query the database here to find the value.
	 *
	 * @return bool|mixed
	 */
	public function get_package_type(){

		$items = $this->get_items( false );

		// if tires exist with more than 1 type each then something went wrong
		// and there is no good fallback, therefore just find the first tire, if there is one
		// and return the tire type that is found there.
		if ( $items ) {
			foreach ( $items as $item ) {
				$type = gp_if_set( $item, 'type' );
				if ( $type === 'tire' ){
					$tire_type = gp_if_set( $item, 'tire_type' );
					return $tire_type;
				}
			}
		}

		return false;
	}

	/**
	 *
	 */
	public function has_tires(){
		$data = $this->collect_summary_data();
		$count = gp_if_set( $data, 'tires', 0 );
		return $count > 0;
	}

	/**
	 *
	 */
	public function has_rims(){
		$data = $this->collect_summary_data();
		$count = gp_if_set( $data, 'rims', 0 );
		return $count > 0;
	}

	/**
	 *
	 */
	public function render_package_upgrades(){
		// we would use this if the UI was logical, and had a package
		// with 1 vehicle and several items, then there would be a place
		// to render the package specific upgrades, but instead when an upgrade
		// is specific to a package, we have to just render it on one or on multiple items
		// ie. for staggered package 'add rims' we just render it on both front and rear tires.
	}

	/**
	 * @param $upgrades
	 * @param $context - item or package
	 * @param $id
	 *
	 * @return string
	 */
	public function render_upgrades( $upgrades, $context, $id ) {
		$op = '';
		if ( $upgrades ) {
			foreach ( $upgrades as $upgrade ) {
				$op .= $this->render_upgrade( $upgrade, $context, $id );
			}
		}
		return $op;
	}
	/**
	 *
	 * Call this function basically any time we *might* want to render an upgrade.
	 * It will determine that the time is not right and do nothing, or it will return
	 * the html for the upgrade.
	 *
	 * @param        $upgrade
	 * @param        $context
	 * @param string $id
	 */
	public function render_upgrade( $upgrade, $context, $id ) {

		if ( ! $upgrade ) {
			return '';
		}

		$cart = get_cart_instance();

		// some confusing things are below. Remember... $upgrade is an array and has
		// a context. The $context variable is also called $context, but
		// corresponds to where we are *possibly* going to render the item.
		// so for each place where items might get rendered, we loop through all
		// upgrades, and if their context corresponds to the context where we
		// are rendering, then we render the item. Then there is also
		// item type... so a bit more logic to be done.

		$render = false;
		$upgrade_context = gp_if_set( $upgrade, 'context' );
		$upgrade_item_type = gp_if_set( $upgrade, 'item_type' );
		$upgrade_context_id = gp_if_set( $upgrade, 'context_id' );

		// at least one of these should end up null
		$item = $context === 'item' ? $cart->get_item( $id ) : null;
		$package = $context === 'package' ? $cart->get_package( $id ) : null;

		// order of conditions below is important
		foreach ( array(1) as $nothing ) {

			// All Items (usually we'll specify item_type instead of context 'all_items')
			if ( $upgrade_context === 'all_items' && $context === 'item' ) {
				$render = true;
				break;
			}

			// All Packages (not sure if we use this)
			if ( $upgrade_context === 'all_packages' && $context === 'package' ) {
				$render = true;
				break;
			}

			// Items
			if ( $upgrade_context === 'item' && $context === 'item' && $item ) {

				// render on items with specified ID
				if ( $upgrade_context_id && $upgrade_context_id == $id ) {
					$render = true;
					break;
				}

				// render on items with specified type
				if ( $upgrade_item_type && $upgrade_item_type === $item->type ) {
					$render = true;
					break;
				}

			}

			// Packages
			if ( $upgrade_context === 'package' && $context === 'package' && $package ) {

				// render on packages with specified ID
				if ( $upgrade_context_id && $upgrade_context_id == $id ) {
					$render = true;
					break;
				}

				// render on items with specified type
				if ( $upgrade_item_type && $upgrade_item_type === $item->type ) {
					$render = true;
					break;
				}
			}
		}

		if ( $render ) {
			$ret = $this->render_upgrade_raw( $upgrade );
			return $ret;
		}

		return '';
	}

    /**
     * Renders an upgrade without checking whether or not it should be rendered.
     *
     * @param $upgrade
     * @return string
     */
	protected function render_upgrade_raw( $upgrade ) {

		$type = gp_if_set( $upgrade, 'type' );
		$context = gp_if_set( $upgrade, 'context' );
		$context_id = gp_if_set( $upgrade, 'context_id' );
		$op = '';

		switch( $type ) {
			case 'add_tires':

				// hide this upgrade suggestion for US
				if ( US_TIRES_HAVE_NO_INVENTORY && app_get_locale() === APP_LOCALE_US ) {
					return '';
				}

				$url = gp_if_set( $upgrade, 'url' );
				if ( $url ) {
					$op .= '<p><a href="' . $url . '">Add Tires</a></p>';
				}
				break;
			case 'add_rims':
				$url = gp_if_set( $upgrade, 'url' );
				if ( $url ) {
					$op .= '<p><a href="' . $url . '">Add Rims</a></p>';
				}
				break;
			default:
				return '';
		}

		return $op;
	}

	public function get_vehicle_slugs(){
	    return [
            $this->vehicle->make,
            $this->vehicle->model,
            $this->vehicle->year,
            $this->vehicle->trim,
            $this->fitment->wheel_set->slug,
            $this->fitment->wheel_set->get_selected()->is_sub() ? $this->fitment->wheel_set->get_sub_slug() : ''
        ];
    }

    /**
     * @param array $merge
     * @return array
     */
	public function completed_vehicle_summary_array( $merge = array() ) {

		$ret = array(
			'make' => $this->vehicle->make,
			'model' => $this->vehicle->model,
			'year' => $this->vehicle->year,
			'trim' => $this->vehicle->trim,
			'fitment' => $this->fitment->wheel_set->slug,
		);

		if ( $this->fitment->wheel_set->get_selected()->is_sub() ){
			$ret['sub'] = $this->fitment->wheel_set->get_sub_slug();
		}

		if ( $merge ) {
			$ret = array_merge( $ret, $merge );
		}

		return $ret;
	}

	/**
	 *
	 */
	public function analyze_for_upgrades(){

		if ( $this->upgrades !== null ) {
			return $this->upgrades;
		}

		// each upgrade will be an array. a package could have more than 1 upgrade
		// upgrades may be linked to an item (ie. we show the upgrade link inside the item box)
		// if we have more than 1 upgrade, we may choose to show only one on the page at a time.. we'll see.
		$upgrades = array();
		$data = $this->collect_summary_data();

		$tires = gp_if_set( $data, 'tires' );
		$rims = gp_if_set( $data, 'rims' );
		$front_tires = gp_if_set( $data, 'front_tires' );
		$rear_tires = gp_if_set( $data, 'rear_tires' );
		$front_rims = gp_if_set( $data, 'front_rims' );
		$rear_rims = gp_if_set( $data, 'rear_rims' );
		$universal_rims = gp_if_set( $data, 'universal_rims' );
		$universal_tires = gp_if_set( $data, 'universal_tires' );

		$fitment_selected = $this->is_fitment_selected();

		if ( $this->fitment->wheel_set->get_selected()->is_staggered() ) {

			$t1_part_numbers = isset( $data['part_numbers']['tires']['front'] ) ? $data['part_numbers']['tires']['front'] : array();
			$t1_part_number = gp_array_first( $t1_part_numbers );

			$t2_part_numbers = isset( $data['part_numbers']['tires']['rear'] ) ? $data['part_numbers']['tires']['rear'] : array();
			$t2_part_number = gp_array_first( $t2_part_numbers );

			$r1_part_numbers = isset( $data['part_numbers']['rims']['front'] ) ? $data['part_numbers']['rims']['front'] : array();
			$r1_part_number = gp_array_first( $r1_part_numbers );

			$r2_part_numbers = isset( $data['part_numbers']['rims']['rear'] ) ? $data['part_numbers']['rims']['rear'] : array();
			$r2_part_number = gp_array_first( $r2_part_numbers );


			// might be the same logic as with non-staggered
			// upgrades are not available when a user removes only front or only rear tires..
			// when they remove both, we can show link to add tires
			if ( $fitment_selected && $front_rims && $rear_rims && ! $tires ) {

				$upgrades[] = array(
					'type' => 'add_tires',
					'context' => 'item', // render on (some) items
					'item_type' => 'rim', // render on this item type only
                    'url' => get_vehicle_archive_url( 'packages', $this->get_vehicle_slugs(), [
                        'pkg' => $this->id,
                        'rim_1' => $r1_part_number,
                        'rim_2' => $r2_part_number
                    ] ),
				);
			}

			if ( $fitment_selected && $front_tires && $rear_tires && ! $rims ) {

				$upgrades[] = array(
					'type' => 'add_rims',
					'context' => 'item', // render on (some) items
					'item_type' => 'tire', // render on this item type only
                    'url' => get_vehicle_archive_url( 'packages', $this->get_vehicle_slugs(), [
                        'pkg' => $this->id,
                        'tire_1' => $t1_part_number,
                        'tire_2' => $t2_part_number
                    ] ),
				);
			}


		} else {

			$t1_part_numbers = isset( $data['part_numbers']['tires']['universal'] ) ? $data['part_numbers']['tires']['universal'] : array();
			$t1_part_number = gp_array_first( $t1_part_numbers );

			$r1_part_numbers = isset( $data['part_numbers']['rims']['universal'] ) ? $data['part_numbers']['rims']['universal'] : array();
			$r1_part_number = gp_array_first( $r1_part_numbers );

			if ( $fitment_selected && $rims && ! $tires ) {

				$upgrades[] = array(
					'type' => 'add_tires',
					'context' => 'item', // render on (some) items
					'item_type' => 'rim', // render on this item type only
                    'url' => get_vehicle_archive_url( 'packages', $this->get_vehicle_slugs(), [
                        'pkg' => $this->id,
                        'rim_1' => $r1_part_number,
                    ] ),
				);
			}

			if ( $fitment_selected && $tires && ! $rims ) {

				$upgrades[] = array(
					'type' => 'add_rims',
					'context' => 'item', // render on (some) items
					'item_type' => 'tire', // render on this item type only
                    'url' => get_vehicle_archive_url( 'packages', $this->get_vehicle_slugs(), [
                        'pkg' => $this->id,
                        'tire_1' => $t1_part_number,
                    ] ),
				);
			}
		}

		$this->upgrades = $upgrades;
		return $this->upgrades;
	}

	/**
	 * This is similar to when we check Vehicle::is_complete() but
	 * cart vehicles don't have fitments. Pretty much all cart packages
	 * require a selected fitment, therefore we can use this just as a fallback
	 * measure when doing certain operations. It should ensure we can do things
	 * like access properties from $this->fitment. Also, if everything goes
	 * correctly, then I don't think we can possibly have cart packages without
	 * selected fitments, but for example if access to the API gets cut off, or
	 * if the API loses data, then this will return false.
	 */
	public function is_fitment_selected(){

		if ( $this->fitment->get_selected_wheel_set() ) {
			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function vehicle_and_fitment_is_valid(){

		if ( $this->is_fitment_selected() && $this->cart_vehicle_is_valid() ) {
			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function cart_vehicle_is_valid(){
		$v = $this->vehicle;
		if ( $v && $v->make && $v->model && $v->year && $v->trim ) {
			return true;
		}
		return false;
	}

	/**
	 * Reset to avoid getting a cached version on collect_summary_data(). Resetting
	 * will attempt to be done automatically any time we add, remove, or modify in any way, cart
	 * items that have anything to do with a particular package.
	 */
	public function reset_summary_data(){
		$this->summary_data = null;
	}

	/**
	 * Be aware: this function runs on every page load, once for every package in the cart.
	 */
	public function collect_summary_data(){

		if ( $this->summary_data !== null ) {
			return $this->summary_data;
		}

		// functions that use collect_summary_data() will not check if these array indexes are set.
		// it is expected that they will be. Therefore, don't remove any.
		$data = array(
			'tires' => 0,
			'rims' => 0,
			'front_tires' => 0,
			'rear_tires' => 0,
			'front_rims' => 0,
			'rear_rims' => 0,
			'universal_rims' => 0,
			'universal_tires' => 0,
			'mount_balance_quantity' => 0,
			'install_kit_quantity' => 0,
			// calculate after the loop
			'tires_on_rims' => 0,
			'tires_alone' => 0,
			'rims_alone' => 0,
		);

		$items = $this->get_items( false );

		if ( $items ){
			/** @var Cart_Item $item */
			foreach ( $items as $item ) {

				$qty = $item->quantity;

				switch( $item->type ) {
					case 'tire':

						$data['tires'] += $qty;

						if ( $item->loc === 'rear' ) {
							$data['rear_tires'] += $qty;
							$data['part_numbers']['tires']['rear'][] = $item->part_number;
						} else if ( $item->loc === 'front' ) {
							$data['front_tires'] += $qty;
							$data['part_numbers']['tires']['front'][] = $item->part_number;
						} else if ( $item->loc === 'universal' ){
							$data['universal_tires'] += $qty;
							$data['part_numbers']['tires']['universal'][] = $item->part_number;
						}

						break;
					case 'rim':

						$data['rims']+= $qty;

						if ( $item->loc === 'rear' ) {
							$data['rear_rims'] += $qty;
							$data['part_numbers']['rims']['rear'][] = $item->part_number;
						} else if ( $item->loc === 'front' ) {
							$data['front_rims'] += $qty;
							$data['part_numbers']['rims']['front'][] = $item->part_number;
						} else if ( $item->loc === 'universal' ){
							$data['universal_rims'] += $qty;
							$data['part_numbers']['rims']['universal'][] = $item->part_number;
						}

						break;
					case 'mount_balance':

						// each package can have only 1 mount_balance item, therefore override value
						$data['mount_balance_quantity'] = $qty;
						break;

					case 'install_kit':

						// each package can have only 1 install_kit item, therefore override value
						$data['install_kit_quantity'] = $qty;
						break;
					default:
						break;
				} // switch

			} // foreach
		}

		// number of tires mounted on rims.. a count of 4 means we have 4 tires + 4 rims + 4 mount balance in the same package.
		// we're not going to re-verify that the tires fit the rims here, this should be done upon add to cart handler and may
		// require expensive database queries. be mindful of the fact that the code found right here runs
		// on every single page load, once for every package in the cart. database queries should not be done here.
		$data['tires_on_rims'] = min( array( $data['tires'], $data['rims'], $data['mount_balance_quantity'] ) );

		// do this after calculating 'tires_on_rims'
		$data['tires_alone'] = $data['tires'] - $data['tires_on_rims'];
		$data['rims_alone'] = $data['rims'] - $data['tires_on_rims'];

		$this->summary_data = $data;
		return $this->summary_data;
	}
}