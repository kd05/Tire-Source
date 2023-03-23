<?php

/**
 * Class Cart
 */
Class Cart extends Export_As_Array {

	public $extra_data = array();

	/**
	 * @var array Cart_Package[]
	 */
	public $packages = array();

	/**
	 * @var array Cart_Item[]
	 */
	public $items = array();

	/** @var  Cart_Summary */
	public $summary;

	/** @var  Cart_Stock_Summary */
	public $stock_summary;

	public $locale;

	/**
	 * Native class properties
	 *
	 * @var array
	 */
	protected $props_to_export = array(
		'packages',
		'items',
		'summary',
	);

	protected static $instance_id_counter;
	protected static $instances;
	protected $instance_id;
    /**
     * @var true
     */
    private $summary_up_to_date;

    public function __construct( $data ) {

		$this->locale = app_get_locale();

		//		self::$instance_id_counter = self::$instance_id_counter !== null ? self::$instance_id_counter : 0;
		//		self::$instance_id_counter++;
		//		self::$instances[self::$instance_id_counter] = $this;
		//		$this->instance_id = self::$instance_id_counter;

		// We need to make the $cart global so that objects that belong to
		// the cart still have access to its functions. (ie. Cart_Package, and Cart_Item)
		// I would prefer to pass a Cart instance to each Cart_Package but then we get
		// recursion, which although still works, is preferable to avoid.

		// IMPORTANT: setup global $cart right away! functions within parent__construct() will
		// use 'get_cart_instance()' which, if a global is not found, will create a new cart instance,
		// resulting in an infinite loop.
		global $cart;
		$cart = $this;

		// after setting global $cart
		parent::__construct( $data );
	}

	/**
	 * This will be called from parent::__construct().
	 *
	 * @param $data
	 */
	public function init( $data ) {
		$packages = gp_if_set( $data, 'packages' );

		if ( $packages && is_array( $packages ) ) {
			foreach ( $packages as $pid => $package ) {
				$id               = gp_if_set( $package, 'id', $pid );
				$package_instance = $package instanceof Cart_Package ? $package : new Cart_Package( $package );
				// possible fix for if you are doing it wrong
				// (we do unfortunately require the ID to be in both the array index, and in the array values)
				// be aware however, I can't fix this if your ID is zero
				if ( ! $package_instance->id ) {
					$package_instance->id = $id;
				}
				$this->packages[ $id ] = $package_instance;
			}
		}

		$items = gp_if_set( $data, 'items' );
		if ( $items && is_array( $items ) ) {
			foreach ( $items as $index => $item ) {
				$id            = gp_if_set( $item, 'id', $index );
				$item_instance = $item instanceof Cart_Item ? $item : new Cart_Item( $item );
				// possible fix for if you are doing it wrong
				// (we do unfortunately require the ID to be in both the array index, and in the array values)
				// be aware however, I can't fix this if your ID is zero
				if ( ! $item_instance->id ) {
					$item_instance->id = $id;
				}
				$this->items[ $id ] = $item_instance;
			}
		}

		// this should have already been done on ->commit(), but we'll do it again anyways
		$this->remove_empty_packages();

		// do this last or close to last
		$this->setup_cart_summary();
	}

	/**
	 * Do this after a successful transaction.
	 *
	 * @return int - The number of tires or rims updated (not necessarily an error if zero)
	 */
	public function subtract_stock_levels_from_database_for_all_items_in_cart(){

		$items_updated = 0;

		if ( $this->items ) {
			/** @var Cart_Item $item */
			foreach ( $this->items as $item ) {
				$product = $item->get_db_product();
				// ie. skip mount_balance / install_kit
				if ( $product ) {

					// "stock_sold_ca" or "stock_sold_us"
					$col_sold = DB_Product::get_column_stock_sold( $this->locale );

					$updated = $product->update_database_and_re_sync( array(
						$col_sold => $product->get_stock_sold( $this->locale ) + $item->quantity,
					), array(
						$col_sold => '%d',
					));

					if ( $updated ) {
						$items_updated++;
					}
				}
			}
		}

		return $items_updated;
	}

	/**
	 * Setup summary data based on the current state of the cart. After updating items,
	 * be aware that existing summary data variables may be out of sync.
	 */
	public function setup_cart_summary() {

		// we could load the cart summary from session array on page load, but i don't think its needed
		// instead, when we load a cart from session to php memory we can re-calculate these values
		// as they are quite easy to calculate and I would rather re-calculate to always ensure
		// we are up to date rather than rely on data that could possibly be related to previous state.

		// so... create new instance with all counts set to zero
		$this->summary = new Cart_Summary();

		// items not assigned to packages
		$np_items = $this->get_non_packaged_item_ids();

		// packages not empty
		$packages = $this->get_not_empty_package_ids();

		// the sum of the above 2 variables should give us all items in the cart
		// although may skip items that shouldn't be there, like if their quantity is zero

		if ( $packages && is_array( $packages ) ) {
			foreach ( $packages as $p ) {
				$pkg = $this->get_package( $p );

				// always reset the packages cached summary data in this context..
				// in other contexts, sometimes its ok not to reset.
				$pkg->reset_summary_data();
				$sd = $pkg->collect_summary_data();

				// function has expected return indexes, so not checking isset
				$this->summary->count_tires_on_rims += $sd[ 'tires_on_rims' ];
				$this->summary->count_tires_alone   += $sd[ 'tires_alone' ];
				$this->summary->count_rims_alone    += $sd[ 'rims_alone' ];

			}
		}

		if ( $np_items ) {
			foreach ( $np_items as $i ) {
				$item = $this->get_item( $i );
				switch ( $item->type ) {
					case 'tire':
						$this->summary->count_tires_alone += $item->quantity;
						break;
					case 'rim':
						$this->summary->count_rims_alone += $item->quantity;
						break;
					default:
						break;
				}
			}
		}

		$this->summary_up_to_date = true;
	}

	/**
	 * the session array likely only stores part numbers of item which can
	 * be used to reference a row in the tires/rims table, when full information for the product is needed.
	 * there may be some operations where we don't need to query the database to perform cart operations...
	 * for example, we can easily add and remove items, and count the number of items in the cart
	 * based only on the ID (ie. info in the session array). But for other operations, such as
	 * (possibly) getting the price value, or retrieving the image URL, we'll need to query the database.
	 * So.. if you are sure you need to query the database for each tire/rim in your current Cart object, then
	 * you can call this function. The results of the query are stored/cached in an objects property, so
	 * calling this multiple times shouldn't cause any noticeable slow down.
	 */
	public function link_item_products_to_database_tables(){
		if ( $this->items ){
			/** @var Cart_Item $item */
			foreach ( $this->items as $item ) {
				if ( $item->is_rim() ) {
					$item->get_db_rim();
				} else if ( $item->is_tire() ) {
					$item->get_db_tire();
				}
			}
		}
	}

	/**
	 * @see $this->are_all_items_in_stock()
	 *
	 * @return string
	 */
	public static function get_out_of_stock_msg_with_link_to_cart_page( $new_tab = false ){
		$url = get_url( 'cart' );
		$text = 'cart';
		$link = $new_tab ? html_link_new_tab( $url, $text ) : html_link( $url, $text );
		return 'Some of the items in your cart are out of stock. Please visit the ' . $link . ' page to remove the items or lower their quantities.';
	}

	/**
	 *
	 */
	public function setup_stock_summary( $check_cached = false ){

		if ( $check_cached && $this->stock_summary instanceof Cart_Stock_Summary ) {
			return;
		}

		$this->stock_summary = $this->get_cart_stock_summary();
	}

	/**
	 * This more or less grouped items with same part numbers and gives
	 * aggregate summary values for those items. For example, if 2 separate
	 * items are the same tire, we need to know the total quantity of items
	 * in the cart among those 2 items.
	 *
	 * @return Cart_Stock_Summary
	 */
	public function get_cart_stock_summary(){

		$obj = new Cart_Stock_Summary();

		$data = array();

		// setup these indexes always, so we can more easily do things like
		// foreach ( $obj->data['rim'] as $rim_part_number => $rim_stock_data )
		$data['rim'] = array();
		$data['tire'] = array();
		$data['install_kit'] = array();
		$data['mount_balance'] = array();

		// maybe set to false
		$all_items_in_stock = true;

		if ( $this->items ) {
			/** @var Cart_Item $item */
			foreach ( $this->items as $item ) {

				// should be redundant
				if ( ! isset( $data[$item->type] ) ) {
					$data[$item->type] = array();
				}

				if ( ! isset( $data[$item->type][$item->part_number] ) ) {
					$data[$item->type][$item->part_number] = array();
				}

				$common_item_ids = $this->get_items_ids_with_conditions( array(
					'type' => $item->type,
					'part_number' => $item->part_number,
				));

				// store the cart item IDs..
				if ( ! isset( $data[$item->type][$item->part_number]['item_ids'] ) ) {
					$data[$item->type][$item->part_number]['item_ids'] = $common_item_ids;
				}

				// add to this with each item
				$qty = 0;

				// compute this value from one of the common items
				// must start as NULL.
				$stock = null;

				// depends on $qty and $stock
				$in_stock = null;

				if ( $common_item_ids ) {

					foreach ( $common_item_ids as $_item_id ) {
						$common_item = $this->get_item( $_item_id );

						$qty+= (int) $common_item->quantity;

						if ( $stock === null ) {
							$stock = $common_item->get_computed_stock_amt( $this->locale );
						}
					}
				}

				if ( $stock === STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING ) {
					$in_stock = true;
				} else if ( $qty > $stock ) {
					$in_stock = false;
				} else{
					$in_stock = true;
				}

				if ( ! $in_stock ) {
					$all_items_in_stock = false;
				}

				$data[$item->type][$item->part_number]['qty'] = $qty;
				$data[$item->type][$item->part_number]['stock'] = $stock;
				$data[$item->type][$item->part_number]['in_stock'] = $in_stock;
			}
		}

		$obj->data = $data;
		$obj->in_stock = $all_items_in_stock;

		return $obj;
	}

	/**
	 * Checks if the entire cart has sufficient stock level to checkout.
	 */
	public function is_every_item_in_stock(){
		$this->setup_stock_summary( true );
		return $this->stock_summary->in_stock;
	}

	/**
	 * @param $item_id
	 */
	public function is_item_in_stock( $item_id ) {

		$this->setup_stock_summary( true );

		// if item does not exist, there is no good fallback to "is this item in stock?"
		assert( $this->get_item( $item_id ) );

		$data = $this->stock_summary->get_data_block_from_item_id( $item_id );

		$ret = $data ? $data['in_stock'] : false;
		return $ret;
	}

	/**
	 * Loads from an array stored in $_SESSION by default. If you want
	 * to load the cart from a database later on, then you should be able
	 * to adjust the logic here.
	 *
	 * @return Cart
	 */
	public static function load_instance( $data = null ) {
		$data = $data !== null ? $data : get_session_cart();

		// this is a bit weird, but __construct() will setup the global
		global $cart;
		new self( $data );

		return $cart;
	}

	/**
	 *
	 */
	public function limit_all_packages_optional_items() {

		if ( $this->packages && is_array( $this->packages ) ) {
			foreach ( $this->packages as $package ) {
				$this->limit_package_optional_items( $package );
			}
		}

	}

	/**
	 * @param $package
	 */
	public function limit_package_optional_items( $package ) {
		$this->limit_package_mount_balance( $package );
		$this->limit_package_install_kit( $package );
	}

	/**
	 *
	 */
	public function reset_all_packages_summary_data() {
		if ( $this->packages ) {
			/** @var Cart_Package $package */
			foreach ( $this->packages as $package ) {
				$package->reset_summary_data();
			}
		}
	}

	/**
	 * We cache some summary data, but its important to un cache this at certain times,
	 * otherwise we run into data redundancy issues. In general, just call ->commit()
	 */
	public function clear_all_cached_data() {

		$this->reset_all_packages_summary_data();

		// NOTE: cart summary depends on each packages summary data, so do this last.
		$this->setup_cart_summary();
	}

	/**
	 * Commit to $_SESSION by default, but if we need to add
	 * database cart storage, we can modify the function below.
	 *
	 */
	public function commit() {

		// this cleans up not only packages with no items, but more importantly,
		// packages whose only items are mount_balance and/or install_kit
		$this->remove_empty_packages();

		// MUST do this before ->limit_all_packages_optional_items()
		$this->reset_all_packages_summary_data();
		$this->setup_cart_summary();

		// limit install_kit, or mount_balance quantities
		$this->limit_all_packages_optional_items();

		// this sync's (limits or adds) optional item quantities (most likely only install_kit)
		// the limiting portion is likely redundant, but this is how install kits will be
		// added automatically
		if ( $this->packages ) {
			foreach ( $this->packages as $package_id ) {
				$this->sync_package_optional_items( $package_id );
			}
		}

		// now unfortunately, DO THIS AGAIN, because packages may have changed.
		$this->reset_all_packages_summary_data();
		$this->setup_cart_summary();


		// now update $_SESSION['cart']
		set_session_cart( $this->to_array() );
	}

	/**
	 * For now, only used to remove all mount & balance items when mount & balance is disabled.
	 * its also highly unlikely mount balance items will exist in the first place, but when we commit
	 * the cart to session we'll ensure the mount/balance items are removed if the feature is turned off.
	 *
	 * this doesn't work well for mount_balance because the items always exist but with quantity of zero
	 *
	 * @param $type
	 */
//	public function remove_all_items_of_type( $type ) {
//
//		$count = 0;
//
//		if ( $this->items ) {
//			/** @var Cart_Item $item */
//			foreach ( $this->items as $item ) {
//				if ( $item->type == $type ) {
//					$removed = $this->item_delete( $item );
//					if ( $removed ) {
//						$count++;
//					}
//				}
//			}
//		}
//
//		return $count;
//	}

	/**
	 * Get the total number of items in the cart.
	 *
	 * @param      $include_mount_balance
	 * @param      $include_install_kit
	 * @param bool $include_empty
	 */
	public function count_items( $include_mount_balance = true, $include_install_kit = true, $include_empty = false ) {

		$count = 0;

		if ( $this->items && is_array( $this->items ) ) {
			/** @var Cart_Item $item */
			foreach ( $this->items as $item ) {

				$q = (int) $item->get_quantity();
				$t = $item->type;

				if ( $t === 'mount_balance' && ! $include_mount_balance ) {
					continue;
				}

				if ( $t === 'install_kit' && ! $include_install_kit ) {
					continue;
				}

				if ( $q === 0 && ! $include_empty ) {
					continue;
				}

				$count ++;
			}
		}

		return $count;
	}

	/**
	 * We should basically run this any time an item is modified in any way,
	 * or when loading from or storing into session.
	 */
	public function remove_empty_packages() {

		// remove packages who's only items are mount_balance/install_kit
		if ( $this->packages ) {
			foreach ( $this->packages as $package_id => $package ) {
				$items = $this->get_item_ids_by_package( $package );

				// remove rim/tire items with quantity 0
				if ( $items ) {
					foreach ( $items as $item_id ) {
						$item = $this->get_item( $item_id );
						if ( $item->type === 'tire' && $item->quantity === 0 ) {
							unset( $this->items[ $item_id ] );
						}
						if ( $item->type === 'rim' && $item->quantity === 0 ) {
							unset( $this->items[ $item_id ] );
						}
					}
				}

				// now check for packages without any tires/rims that may still have mount/balance and install kit items
				$items = $this->get_item_ids_by_package( $package );
				if ( $items ) {

					$tires = $this->count_package_items_by_type( $package, 'tire' );
					$rims  = $this->count_package_items_by_type( $package, 'rim' );

					// delete package
					if ( ! $tires && ! $rims ) {
						$this->delete_package( $package_id );
					}
				}
			}
		}

	}

	/**
	 *
	 */
	public function get_shipping_amount() {

	}

	/**
	 * Remove all items from $this->items that belong to package, and
	 * remove package from $this->packages
	 *
	 * @param $package_id
	 */
	public function delete_package( $package_id ) {
		$package = $this->get_package( $package_id );
		if ( $package ) {
			$items = $this->get_item_ids_by_package( $package );

			// remove all items from $cart->items array
			foreach ( $items as $item_id ) {

				if ( isset( $this->packages[ $package->id ]->items[ $item_id ] ) ) {
					unset( $this->packages[ $package->id ]->items[ $item_id ] );
				}

				if ( isset( $this->items[ $item_id ] ) ) {
					unset( $this->items[ $item_id ] );
				}
			}
			// remove package now
			if ( isset( $this->packages[ $package->id ] ) ) {
				unset( $this->packages[ $package->id ] );
			}
		}
	}

	/**
	 * @param array $conditions
	 *
	 * @return array
	 */
	public function get_items_ids_with_conditions( array $conditions ) {

		$item_ids = array();

		if ( $this->items && is_array( $this->items ) ) {
			/** @var Cart_Item $item */
			foreach ( $this->items as $item ) {
				// converting to array is inefficient but
				// function below will break if we don't convert to array and some
				// properties are not public.
				$item_array = $item->to_array();
				$match      = array_meets_conditions( $item_array, $conditions );
				if ( $match ) {
					$item_ids[] = $item->id;
				}
			}
		}

		return $item_ids;
	}

	/**
	 * @param $package
	 *
	 * @return bool
	 */
	public function limit_package_install_kit( $package ) {

		// ensure we prevent a loop. this is in case item_update_single_property re-syncs the cart,
		// which will limit package mount_balance/install_kit items.
		global $limit_package_recursion_indicator;

		$package = $this->get_package( $package );

		if ( ! $package ) {
			return false;
		}

		$item_id = $this->find_or_create_install_kit_id( $package );
		$item    = $item_id ? $this->get_item( $item_id ) : false;

		if ( ! $item ) {
			throw_dev_error( 'install_kit item must exist, even if quantity is zero' );
		}

		$suggested = $this->get_suggested_install_kit_quantity( $package );

		// prevent user from adding more mount/balance than would be possible based on rims/tires
		// note that we don't do this for install_kit, since someone may want 2 install kits for 1 set of tires.
		if ( $item->quantity > $suggested ) {
			if ( ! $limit_package_recursion_indicator ) {

				$limit_package_recursion_indicator = true;
				$this->item_update_single_property( $item, 'quantity', $suggested );
				$limit_package_recursion_indicator = null;

			}

			return true;
		}

		return false;
	}

	/**
	 * @param $package
	 *
	 * @return bool
	 */
	public function limit_package_mount_balance( $package ) {

		// ensure we prevent a loop. this is in case item_update_single_property re-syncs the cart,
		// which will limit package mount_balance/install_kit items.
		global $limit_package_recursion_indicator;

		$package = $this->get_package( $package );

		if ( ! $package ) {
			return false;
		}

		$item_id = $this->find_or_create_mount_balance_id( $package );
		$item    = $item_id ? $this->get_item( $item_id ) : false;

		if ( ! $item ) {
			throw_dev_error( 'mount_balance item must exist, even if quantity is zero' );
		}

		$suggested = DISABLE_MOUNT_BALANCE ? 0 : $this->get_suggested_mount_balance_quantity( $package );

		// prevent user from adding more mount/balance than would be possible based on rims/tires
		// note that we don't do this for install_kit, since someone may want 2 install kits for 1 set of tires.
		if ( $item->quantity > $suggested ) {

			if ( ! $limit_package_recursion_indicator ) {
				$limit_package_recursion_indicator = true;
				$this->item_update_single_property( $item, 'quantity', $suggested );
				$limit_package_recursion_indicator = null;
			}

			$this->item_update_single_property( $item, 'quantity', $suggested );

			return true;
		}

		return false;
	}

	/**
	 * Update: Syncing is turned on for install_kit, but not mount_balance. This is the way it
	 * *should* be. accessories kits should always be included unless a customer opts out. mount/balance
	 * should be optional unless a customer opts in.
	 *
	 * Mount/Balance, Accessories Kit
	 *
	 * I believe we need to call this every time a package is modified... but
	 * if its modified several times within one script (ie. adding multiple items) it should be safe to only
	 * call this after the last item is modified.
	 *
	 * @param $package_id
	 */
	public function sync_package_optional_items( $package_id ) {

		$package = $this->get_package( $package_id );

		if ( ! $package ) {
			return;
		}

		// turned off, see update above.
//		$why_mb     = '';
//		$success_mb = $this->sync_package_mount_balance( $package, $why_mb );
//
//		Debug::add( array(
//			'success' => $success_mb,
//			'why' => $why_mb,
//			'listen' => listen_get(),
//		), 'sync mount balance' );

		$why_ak     = '';
		$success_ak = $this->sync_package_install_kit( $package, $why_ak );

		Debug::add( array(
			'success' => $success_ak,
			'why' => $why_ak,
			'listen' => listen_get(),
		), 'sync accessories kit' );
	}

	/**
	 * Calculate the suggested mount and balance quantity for a package. You can rely on this being zero
	 * quite often, which means you shouldn't add mount and balance, and you should remove if it exists.
	 */
	public function get_suggested_mount_balance_quantity( $package_id ) {

		// note: by returning zero as the suggested quantity, we effectively disable mount and balance for u.s. customers
		if ( app_get_locale() === 'US' ) {
			return 0;
		}

		$package = $this->get_package( $package_id );

		if ( ! $package ) {
			return 0;
		}

		$data = $package->collect_summary_data();

		$tires           = $data[ 'tires' ];
		$rims            = $data[ 'rims' ];
		$front_tires     = $data[ 'front_tires' ];
		$rear_tires      = $data[ 'rear_tires' ];
		$front_rims      = $data[ 'front_rims' ];
		$rear_rims       = $data[ 'rear_rims' ];
		$universal_rims  = $data[ 'universal_rims' ];
		$universal_tires = $data[ 'universal_tires' ];

		// the big question here, are we verifying that the rims and tires fit each other, or
		// are we relying on the fact that items will not get packaged together if they dont fit?
		// I feel like its pretty safe to rely on our packaging system, and would also be innefficient
		// to re-check all fitment information here.
		// not only do packages have only one fitment ever, but we also are also never putting
		// more than 1 part number for rims/tires in non-staggered packages, and
		// 2 rim part numbers / 2 tire part numbers for staggered packages.
		// if the system does change one day, we might benefit from doing more logic here however.

		// not staggered fitment
		if ( ! $package->fitment->wheel_set->get_selected()->is_staggered() ) {

			// these 2 things should mean the same thing
			$qty = min( $rims, $tires );
			// $qty = min( $universal_rims, $universal_tires );

		} else {

			// staggered fitment
			$qty_front = min( $front_tires, $front_rims );
			$qty_back  = min( $rear_tires, $rear_rims );
			$qty       = (int) $qty_front + $qty_back;

		}

		//		echo '<pre>' . print_r( generate_call_stack_debug(), true ) . '</pre>';
		//		echo '<pre>' . print_r( $package, true ) . '</pre>';
		//		echo '<pre>' . print_r( $data, true ) . '</pre>';
		//		echo '<pre>' . print_r( $qty, true ) . '</pre>';

		return $qty;
	}

	/**
	 * @param $package_id
	 * @param $type
	 */
	public function get_package_item_ids_by_type( $package_id, $type ) {

		$package = $this->get_package( $package_id );
		if ( ! $package ) {
			return 0;
		}

		$ret           = array();
		$package_items = $this->get_item_ids_by_package( $package_id );

		if ( $package_items ) {
			foreach ( $package_items as $item_id ) {
				$item = $this->get_item( $item_id );
				if ( $item->type === $type ) {
					$ret[] = $item->id;
				}
			}
		}

		return $ret ? $ret : false;
	}

	/**
	 * @param $package_id
	 * @param $type
	 */
	public function count_package_items_by_type( $package_id, $type ) {
		$item_ids = $this->get_package_item_ids_by_type( $package_id, $type );

		return is_array( $item_ids ) ? count( $item_ids ) : 0;
	}

	/**
	 * returns the item_id or false
	 *
	 * @return bool
	 */
	public function find_or_create_mount_balance_id( $package_id ) {

		$item_ids = $this->get_package_item_ids_by_type( $package_id, 'mount_balance' );

		$item_id = $item_ids && is_array( $item_ids ) ? gp_array_first( $item_ids ) : false;

		if ( $item_id ) {
			return $item_id;
		}

		$package = $this->get_package( $package_id );

		$part_number = get_mount_balance_part_number( $package->fitment );

		$item_id     = $this->item_insert( array(
			'type' => 'mount_balance',
			'quantity' => 0,
			'part_number' => $part_number,
			'price' => get_mount_balance_price( $part_number ),
			'package_id' => $package->id,
		) );

		return $item_id;

	}

	/**
	 * @param $package_id
	 *
	 * @return bool|int|mixed|null|string
	 */
	public function find_or_create_install_kit_id( $package_id ) {

		$item_ids = $this->get_package_item_ids_by_type( $package_id, 'install_kit' );

		$item_id = $item_ids && is_array( $item_ids ) ? gp_array_first( $item_ids ) : false;

		if ( $item_id ) {
			return $item_id;
		}

		$package = $this->get_package( $package_id );

		$part_number = get_install_kit_part_number( $package->fitment->stud_holes );
		$item_id     = $this->item_insert( array(
			'type' => 'install_kit',
			'quantity' => 0,
			'part_number' => $part_number,
			'price' => get_install_kit_price( $part_number ),
			'package_id' => $package->id,
		) );

		return $item_id;
	}

	/**
	 * You can pass in a valid $item, or a valid $package, if you do both, $item
	 * will take priority.
	 *
	 * @param null $item
	 * @param null $package
	 * @param null $qty - leave null to have quantity determined automatically
	 *
	 * @return bool
	 */
	public function set_mount_balance_to_suggested_quantity( $item = null, $package = null, $qty = null ) {

		// the item can be any item in the package... but if its set, its probably
		// the mount balance item or the mount balance item ID.
		$item = $this->get_item( $item );
		if ( $item ) {
			$package_id = $item->package_id;
		} else {
			$package    = $this->get_package( $package );
			$package_id = $package ? $package->id : false;
		}

		if ( ! $package_id ) {
			return false;
		}

		// the mount balance item
		$_item_id = $this->find_or_create_mount_balance_id( $package_id );
		$_item    = $this->get_item( $_item_id );

		if ( ! $_item ) {
			return false;
		}

		// store a global variable in case item_update_single_property ends up causing a loop
		// note: it probably doesn't.
		global $set_package_item_recursion_indicator;

		if ( ! $set_package_item_recursion_indicator ) {

			$qty = $qty === null ? $this->get_suggested_mount_balance_quantity( $package_id ) : $qty;

			$set_package_item_recursion_indicator = true;
			$update                               = $this->item_update_single_property( $_item_id, 'quantity', $qty );
			$set_package_item_recursion_indicator = null;

			return (bool) $update;
		}

		return false;
	}

	/**
	 * You can pass in a valid $item, or a valid $package, if you do both, $item
	 * will take priority.
	 *
	 * @param null $item
	 * @param null $package
	 * @param null $qty - leave null to have quantity determined automatically
	 *
	 * @return bool
	 */
	public function set_install_kit_to_suggested_quantity( $item = null, $package = null, $qty = null ) {

		// the item can be any item in the package... but if its set, its probably
		// the mount balance item or the mount balance item ID.
		$item = $this->get_item( $item );
		if ( $item ) {
			$package_id = $item->package_id;
		} else {
			$package    = $this->get_package( $package );
			$package_id = $package ? $package->id : false;
		}

		if ( ! $package_id ) {
			return false;
		}

		// the mount balance item
		$_item_id = $this->find_or_create_install_kit_id( $package_id );
		$_item    = $this->get_item( $_item_id );

		if ( ! $_item ) {
			return false;
		}

		// store a global variable in case item_update_single_property ends up causing a loop
		// note: it probably doesn't.
		global $set_package_item_recursion_indicator;

		if ( ! $set_package_item_recursion_indicator ) {

			$qty = $qty === null ? $this->get_suggested_install_kit_quantity( $package_id ) : $qty;

			$set_package_item_recursion_indicator = true;
			$update                               = $this->item_update_single_property( $_item_id, 'quantity', $qty );
			$set_package_item_recursion_indicator = null;

			return (bool) $update;
		}

		return false;
	}

	/**
	 * Adds a mount and balance if it should be added, and removes if
	 * it it should be removed. Call this most of the time, except after
	 * a user manually removes it. However, we'll probably check when a user
	 * removes it anyways, and possibly not add it back once they remove it. We also
	 * may decide to always attempt to add it whenever adding items to the cart. Remember
	 * a user can add tires/rims, then change quantities or remove tires and/or rims, and
	 * then add other ones later. So if a package qualifies for mount and balance once it
	 * definitely does not mean it always does.
	 *
	 * Returns $item_id if the item has Mount/Balance AFTER this functions is completed.
	 * $why will be equal to "existed" or "added". Returns false if $item_id does not exist after the function
	 * is called.
	 *
	 * NO GAURUNTEE THIS WORKS IF YOU TURN IT BACK ON
	 */
	//	public function sync_package_mount_balance( $package_id, &$why = '' ) {
	//
	//		$package = $this->get_package( $package_id );
	//
	//		if ( ! $package ) {
	//			$why = 'invalid_package';
	//
	//			return false;
	//		}
	//
	//		$user_allows        = $package->user_allows_mount_balance;
	//		$mount_balance_id   = $this->find_or_create_mount_balance_id( $package );
	//		$mount_balance_item = $mount_balance_id ? $this->get_item( $mount_balance_id ) : false;
	//
	//		if ( ! $mount_balance_item ) {
	//			throw_dev_error( 'mount balance item needs to exist.' );
	//		}
	//
	//		$ex_quantity        = $mount_balance_item->quantity;
	//		$suggested_quantity = $this->get_suggested_mount_balance_quantity( $package );
	//
	//		// prevent user from adding more mount/balance than would be possible based on rims/tires
	//		// note that we don't do this for install_kit, since someone may want 2 install kits for 1 set of tires.
	//		if ( $ex_quantity > $suggested_quantity ) {
	//			$this->item_update_single_property( $mount_balance_id, 'quantity', $suggested_quantity );
	//			$why = 'quantity_lowered_to_' . (int) $suggested_quantity;
	//
	//			return true;
	//		}

	// sync to the suggested quantity whether that's zero or not

	// *************
	// This can be turned on to automatically add mount & balance to packages. In addition
	// to automatically adding them, it also syncs the quantity when changing the quantity of other items
	// in the same package or removing rims/tires.  SO.. BOTH of these are off (if the code below is commented out)
	// *************

	//		if ( $ex_quantity !== $suggested_quantity && $user_allows ) {
	//			$this->item_update_single_property( $mount_balance_id, 'quantity', $suggested_quantity );
	//			$why = 'quantity_adjusted_' . (int) $suggested_quantity;
	//			return true;
	//		}

	//		$why = 'quantity_not_adjusted';
	//
	//		return false;
	//	}

	/**
	 * Calculate the accessories kit quantity that a package *should* have.
	 * This does not take into account whether or not the user allows accessories kits.
	 *
	 * @param $package_id
	 *
	 * @return int|mixed
	 */
	public function get_suggested_install_kit_quantity( $package_id ) {

		$package = $this->get_package( $package_id );

		if ( ! $package ) {
			return 0;
		}

		$data = $package->collect_summary_data();

		// total number of tires/rims (regardless of staggered)
		$tires = $data[ 'tires' ];
		$rims  = $data[ 'rims' ];

		//		$front_tires     = $data[ 'front_tires' ];
		//		$rear_tires      = $data[ 'rear_tires' ];
		//		$front_rims      = $data[ 'front_rims' ];
		//		$rear_rims       = $data[ 'rear_rims' ];

		// for staggered pkgs these should be zero, for non-staggered, they should be the same as $tires/$rims
		//		$universal_rims  = $data[ 'universal_rims' ];
		//		$universal_tires = $data[ 'universal_tires' ];

		// for every 4 rims, we can include 1 accessories kit,
		$qty = floor( $rims / 4 );

		// floor returns float. (int) $qty sometimes causes rounding errors, so use round()
		$qty = round( $qty, 0 );

		return $qty;
	}

	/**
	 * Adds an accessories kit if the package has the required number of rims and a vehicle and the
	 * user allows. Also adjusts the quantity unless the user does not allow, in which case only the
	 * user can adjust the quantity.
	 *
	 * We'll try to return true of the package was modified, false otherwise... however don't
	 * rely too heavily on this return value.
	 *
	 * @param        $package_id
	 * @param string $why
	 *
	 * @return bool
	 *
	 * NO GAURUNTEE THIS WORKS IF YOU TURN IT BACK ON
	 */
	public function sync_package_install_kit( $package_id, &$why = '' ) {

		$package = $this->get_package( $package_id );

		if ( ! $package ) {
			$why = 'invalid_package';

			return false;
		}

		$user_allows      = $package->user_allows_install_kit;
		$install_kit_id   = $this->find_or_create_install_kit_id( $package );
		$install_kit_item = $install_kit_id ? $this->get_item( $install_kit_id ) : false;

		if ( ! $install_kit_item ) {
			throw_dev_error( 'install kit item needs to exist.' );
		}

		$ex_quantity        = $install_kit_item->quantity;
		$suggested_quantity = $this->get_suggested_install_kit_quantity( $package );

		if ( $ex_quantity > $suggested_quantity ) {
			// note: I guess we will allow this in case someone wants 2 accessories kits for 1 set of rims
			// however, disabling this probably wouldn't hurt anybody anyways..
			$this->item_update_single_property( $install_kit_id, 'quantity', $suggested_quantity );
			$why = 'quantity_lowered_to_' . (int) $suggested_quantity;

			return true;
		}

		// sync to the suggested quantity whether that's zero or not

		// *************
		// This can be turned on to automatically add install kits to packages. In addition
		// to automatically adding them, it also syncs the quantity when changing the quantity of other items
		// in the same package or removing rims/tires.  SO.. BOTH of these are off (if the code below is commented out)
		// *************

		if ( $ex_quantity !== $suggested_quantity && $user_allows ) {
			$this->item_update_single_property( $install_kit_id, 'quantity', $suggested_quantity );
			$why = 'quantity_adjusted_' . (int) $suggested_quantity;
			return true;
		}

		$why = 'quantity_not_adjusted';

		return false;
	}

	/**
	 * @param $thing
	 *
	 * @return bool|Cart_Item
	 */
	public function force_item_instance( $thing ) {

		// we could add a check in case a StdClass object is passed in, but at this point its not needed.
		if ( is_array( $thing ) ) {
			$item = new Cart_Item( $thing );
		} else if ( $thing instanceof Cart_Item ) {
			$item = $thing;
		} else {
			return false;
		}

		/** Cart_Item */
		return $item;
	}

	/**
	 * @param $thing
	 *
	 * @return bool|Cart_Package
	 */
	public function force_package_instance( $thing ) {

		// we could add a check in case a StdClass object is passed in, but at this point its not needed.
		if ( is_array( $thing ) ) {
			$item = new Cart_Package( $thing );
		} else if ( $thing instanceof Cart_Package ) {
			$item = $thing;
		} else {
			return false;
		}

		/** Cart_Package */
		return $item;
	}

	/**
	 * Returns the item_id of the $item if it exists. $item could be an object, array, or item_id.
	 *
	 * @param $item
	 */
	public function item_exists( $item ) {
		return $this->item_or_package_exists( $item, 'item' );
	}

	/**
	 * @param $item
	 */
	public function package_exists( $item ) {
		return $this->item_or_package_exists( $item, 'package' );
	}

	/**
	 * @param $thing
	 * @param $type
	 */
	protected function item_or_package_exists( $thing, $type ) {

		$id = false;

		if ( $type === 'item' ) {
			$instance = 'Cart_Item';
		} else if ( $type === 'package' ) {
			$instance = 'Cart_Package';
		} else {
			return false;
		}

		if ( gp_is_singular( $thing ) ) {
			$id = $thing;
		} else if ( is_array( $thing ) ) {
			$id = gp_if_set( $thing, 'id' );
		} else if ( $thing instanceof $instance ) {
			$id = $thing->id;
		} else if ( ! $thing ) {
			return false;
		} else {
			throw_dev_error( 'Invalid item/package' );
		}

		if ( ! $id ) {
			return false;
		}

		// use isset, to be consistent with our method of deleting: unset
		// isset returns false if array key exists and value is null

		if ( $type === 'item' ) {
			if ( isset( $this->items[ $id ] ) ) {
				return $id;
			}
		}

		if ( $type === 'package' ) {
			if ( isset( $this->packages[ $id ] ) ) {
				return $id;
			}
		}

		return false;
	}

	/**
	 * Item ID is generated if you do not pass one in.
	 *
	 * @param      $item
	 *
	 * @return bool|int|string - The newly inserted Item ID, or false
	 */
	public function item_insert( $item ) {

		// turns an array into Cart_Item
		$cart_item = $this->force_item_instance( $item );

		if ( ! $cart_item ) {
			return false;
		}

		// item already exists, do not overwrite it.
		if ( $this->item_exists( $item ) || $this->item_exists( $cart_item ) ) {
			return false;
		}

		// we require a type at minimum. Some types also require part numbers.
		// not verifying the actual type passed in however, only that you specified something.
		// you should do your own validation of type before calling item_insert()
		if ( ! $cart_item->type ) {
			return false;
		}

		// DONT PREVENT THIS
		// mount_balance and install_kit items are added with zero quantity, intentionally.
		if ( ! $cart_item->quantity ) {
			$cart_item->quantity = 0;
		}

		// verify rims
		if ( $cart_item->type === 'rim' ) {
			$db_rim = DB_Rim::create_instance_via_part_number( $cart_item->part_number );
			if ( ! $db_rim ) {
				return false;
			}
		}

		// verify tires
		if ( $cart_item->type === 'tire' ) {

			$db_tire = DB_Tire::create_instance_via_part_number( $cart_item->part_number );

			if ( ! $db_tire ) {
				return false;
			}

			// always insert tire type into the item.
			$type                 = $db_tire->model->get( 'type' );
			$type                 = gp_test_input( $type );
			$cart_item->tire_type = $type;
		}

		// make sure we have an ID, but fallback to auto generating an ID, because this
		// is probably how we'll do it most of the time.
		if ( ! $cart_item->id ) {
			$cart_item->id = $this->get_new_item_id();
		}

		// add a timestamp just for debugging or whatever else
		if ( ! $cart_item->timestamp ) {
			$cart_item->timestamp = time();
		}

		// Add the item
		$this->items[ $cart_item->id ] = $cart_item;

		return $cart_item->id;
	}

	/**
	 * @param      $item_id
	 * @param      $key
	 * @param      $value
	 * @param bool $allow_re_sync
	 *
	 * @return bool
	 */
	public function item_update_single_property( $item_id, $key, $value ) {

		$item = $this->get_item( $item_id );

		if ( ! $item ) {
			return false;
		}

		$success = $item->set( $key, $value );

		// not sure if ->set() will return false or null
		if ( $success === false ) {
			return false;
		}

		return $success;
	}

	/**
	 * returns true if the item existed and was removed.
	 *
	 * remember that install_kit and mount_balance items basically always exist but often with
	 * a quantity of zero. this can definitely cause some confusion when you use this function..
	 *
	 * @param $item
	 */
	public function item_delete( $item_id ) {

		// $item_id could be an ID, an array or Cart_Item, but $item is a Cart_Item
		$item = $this->get_item( $item_id );

		if ( ! $this->item_exists( $item_id ) ) {
			return false;
		}

		// remove the item
		if ( isset( $this->items[ $item->id ] ) ) {
			unset( $this->items[ $item->id ] );
		}

		return true;
	}

	/**
	 * If the $package_id passed in is an instance of Cart_Package, then we do not care to check
	 * if the package exists inside of $this->packages array.
	 *
	 * If it is an ID, then of course it has to exist, because the ID is only a pointer to
	 * the package, so we verify it does exist.
	 *
	 * If it is an array, then we'll convert it into an instance of Cart_Package.
	 *
	 * Therefore, if you have a package instance, you can check package_exists() directly to
	 * ensure its still belongs to the Cart. Or better yet, call get_package( $package->id ), as this
	 * will ensure you're not using an out of date version.
	 *
	 * @param $package_id
	 *
	 * @return bool|Cart_Package
	 */
	public function get_package( $package_id ) {

		// if package instance, return early, before checking if it exists.
		if ( $package_id instanceof Cart_Package ) {
			return $package_id;
		}

		$_package_id = $this->package_exists( $package_id );

		if ( ! $package_id ) {
			return false;
		}

		$package        = isset( $this->packages[ $_package_id ] ) ? $this->packages[ $_package_id ] : false;
		$package_object = $this->force_package_instance( $package );

		if ( ! $package_object ) {
			return false;
		}

		/** Cart_Package */
		return $package_object;
	}

	/**
	 * See get_package() for more info.
	 *
	 * @param $item_id
	 *
	 * @return bool|Cart_Item
	 */
	public function get_item( $item_id ) {

		// return early before verifying existence of package within the cart.
		if ( $item_id instanceof Cart_Item ) {
			return $item_id;
		}

		// $item_id could be an array or Cart_Item containing an 'id' index, but $_item_id is the ID.
		$_item_id = $this->item_exists( $item_id );

		if ( ! $_item_id ) {
			return false;
		}

		$item        = isset( $this->items[ $_item_id ] ) ? $this->items[ $_item_id ] : false;
		$item_object = $this->force_item_instance( $item );

		if ( ! $item_object ) {
			return false;
		}

		/** Cart_Item */
		return $item_object;
	}

	/**
	 * This loops through items to assemble a list of packages.
	 * The primary purpose of this function is for printing items on the cart page.
	 * Therefore, we may have to add some logic for ordering.
	 * Note: what to do with items that have an assigned package_id, but the package_id doesn't exist.
	 * Do we print them in an "invalid" package, or assume they have no package? I think the latter.
	 * In either case, we need to keep consistency with how we do it in $this->get_non_packaged_item_ids()
	 */
	public function get_not_empty_package_ids() {

		$package_ids = array();

		if ( $this->items && is_array( $this->items ) ) {
			/** @var Cart_Item $item */
			foreach ( $this->items as $item ) {
				$pid = gp_if_set( $item, 'package_id' );

				$quantity = gp_if_set( $item, 'quantity' );
				if ( ! $quantity || $quantity === "0" ) {
					continue;
				}

				// add to array only if its not already found
				if ( $pid && $this->package_exists( $pid ) ) {
					if ( ! in_array( $pid, $package_ids ) ) {
						$package_ids[] = $pid;
					}
				}
			}
		}

		return $package_ids;
	}

	/**
	 * Get all items in cart that either don't have a package, or have a package assigned
	 * but it doesn't exist.
	 */
	public function get_non_packaged_item_ids() {

		$item_ids = array();

		if ( $this->items && is_array( $this->items ) ) {
			foreach ( $this->items as $item_id => $item ) {
				$pid = gp_if_set( $item, 'package_id' );
				if ( $pid && $this->package_exists( $pid ) ) {
					continue;
				}

				if ( ! in_array( $item_id, $item_ids ) ) {
					$item_ids[] = $item_id;
				}
			}
		}

		return $item_ids;
	}

	/**
	 * @param $package_id
	 */
	public function get_item_ids_by_package( $package, $order = false ) {

		$package    = $this->get_package( $package );
		$package_id = $package ? $package->id : false;

		if ( ! $package ) {
			return array();
		}

		$items    = array();
		$item_ids = array();

		// get items first.. still need to order afterwards
		if ( $this->items && is_array( $this->items ) ) {
			foreach ( $this->items as $item_id => $item ) {
				$item = $this->get_item( $item );

				if ( $item && $package_id == $item->package_id ) {
					if ( $order ) {
						$items[] = $item;
					} else {
						$item_ids[] = $item->id;
					}

				}
			}
		}

		if ( ! $order ) {
			return array_unique( $item_ids );
		}

		// makes the most sense to order the items only when we get items from within a package
		// because otherwise we may mess up the ordering of items that are not packaged, and also
		// because when initially identify packages, we want to show them in order of when
		// the items were first added
		$order_by = array(
			array(
				'type' => 'tire',
				'loc' => 'front',
			),
			array(
				'type' => 'rim',
				'loc' => 'front',
			),
			array(
				'type' => 'tire',
				'loc' => 'rear',
			),
			array(
				'type' => 'rim',
				'loc' => 'rear',
			),
			array(
				'type' => 'tire',
			),
			array(
				'type' => 'rim',
			),
			array(
				'type' => 'install_kit',
			),
			array(
				'type' => 'mount_balance',
			),
			// leave the last condition empty, which ensure it is always met, meaning that any items
			// that don't meet previous conditions will still be appended to the resulting array
			array(),
		);

		$item_ids = array();

		if ( $order_by && is_array( $order_by ) && $items && is_array( $items ) ) {

			foreach ( $order_by as $conditions ) {
				foreach ( $items as $item ) {
					// $item is an object, but function is ok with that
					// make sure to make sure we didn't already add the item because some (actually all)
					// items will likely match more than 1 condition.
					if ( ! in_array( $item->id, $item_ids ) && array_meets_conditions( $item, $conditions, false ) ) {
						$item_ids[] = $item->id;
					}
				}
			}
		}

		// not totally sure we need this..
		$item_ids = array_unique( $item_ids );

		return $item_ids;
	}

	/**
	 * Make sure $userdata['type'] is set accordingly.
	 *
	 * Sometimes we need this for functions that should be handled the same for
	 * both tires and rims. Otherwise, we would have a ton of repetition.
	 *
	 * @param $userdata
	 */
	public function create_tire_or_rim_item_from_userdata( $userdata ) {

		$type = gp_if_set( $userdata, 'type' );

		if ( $type === 'rim' ) {
			return $this->create_rim_item_from_userdata( $userdata );
		}

		if ( $type === 'tire' ) {
			return $this->create_tire_item_from_userdata( $userdata );
		}

		return false;
	}

	/**
	 * Returns an array that is safe to put into ->item_insert(), or false.
	 *
	 * - Uses listen_set() to provide optional, supplementary info when returning false.
	 * - Passing supplementary data to $userdata will result in data loss. Add custom params to return value of this if
	 * needed.
	 * - This does not verify $userdata['type'] === 'tire'.
	 * - You must also verify the package exists beforehand.
	 * - We store this method in the Cart instance (not Cart_Item) in case we need to check if a package exists.
	 *
	 * @param $userdata
	 *
	 * @return array|bool
	 */
	public function create_tire_item_from_userdata( $userdata ) {

		$listen_context = 'create_tire_item_from_userdata'; // function name

		$loc = gp_if_set( $userdata, 'loc' );
		$loc = in_array( $loc, array( 'front', 'rear', 'universal' ) ) ? $loc : 'universal';

		$part_number = gp_if_set( $userdata, 'part_number' );
		$part_number = gp_test_input( $part_number );

		$tire = DB_Tire::create_instance_via_part_number( $part_number );

		if ( ! $tire ) {
			listen_set( 'part_number_invalid', $listen_context );

			return false;
		}

		$pkg = gp_if_set( $userdata, 'pkg' );
		$pkg = gp_test_input( $pkg );

		$quantity = gp_if_set( $userdata, 'quantity' );
		$quantity = (int) $quantity;
		$quantity = $quantity <= 8 && $quantity > 0 ? $quantity : 4;

		// items without packages are perfectly valid
		if ( $pkg && ! $this->package_exists( $pkg ) ) {
			listen_set( 'package_invalid', $listen_context );

			return false;
		}

		$item = array(
			'type' => 'tire',
			'part_number' => $part_number,
			'loc' => $loc,
			'quantity' => $quantity,
		);

		// already checked package exists
		if ( $pkg ) {
			$item[ 'package_id' ] = $pkg;
		}

		return $item;
	}

	/**
	 * RIM/WHEEL
	 *
	 * See $this->create_tire_item_from_userdata(), its pretty similar.
	 *
	 * @param $userdata
	 */
	public function create_rim_item_from_userdata( $userdata ) {

		$listen_context = 'create_rim_item_from_userdata'; // function name

		$loc = gp_if_set( $userdata, 'loc' );
		$loc = in_array( $loc, array( 'front', 'rear', 'universal' ) ) ? $loc : 'universal';

		$part_number = gp_if_set( $userdata, 'part_number' );
		$part_number = gp_test_input( $part_number );

		$rim = DB_Rim::create_instance_via_part_number( $part_number );

		if ( ! $rim ) {
			listen_set( 'part_number_invalid', $listen_context );

			return false;
		}

		$pkg = gp_if_set( $userdata, 'pkg' );
		$pkg = gp_test_input( $pkg );

		$quantity = gp_if_set( $userdata, 'quantity' );
		$quantity = (int) $quantity;
		$quantity = $quantity <= 8 && $quantity > 0 ? $quantity : 4;

		// items without packages are perfectly valid
		if ( $pkg && ! $this->package_exists( $pkg ) ) {
			listen_set( 'package_invalid', $listen_context );

			return false;
		}

		$item = array(
			'type' => 'rim',
			'part_number' => $part_number,
			'loc' => $loc,
			'quantity' => $quantity,
		);

		// already checked package exists
		if ( $pkg ) {
			$item[ 'package_id' ] = $pkg;
		}

		return $item;
	}

	/**
	 * The main purpose of this function is not to deny items from being added to the cart,
	 * but to let you know they don't belong in a certain package, therefore you should
	 * probably make a new package instead, and then try putting them in there.
	 *
	 * @param $item
	 * @param $package_id
	 */
	public function package_can_accept_item( $item, $package_id, &$why = '' ) {

		$package = $this->get_package( $package_id );

		if ( ! $package_id || ! $this->package_exists( $package_id ) || ! $package ) {
			$why = 'package_invalid';

			return false;
		}

		if ( ! $this->is_item_valid( $item ) ) {
			$why = 'item_invalid';

			return false;
		}

		$type        = $item[ 'type' ];
		$loc         = $item[ 'loc' ];
		$quantity    = $item[ 'quantity' ];
		$part_number = gp_if_set( $item, 'part_number' );

		// clear the summary_data before calling collect_summary_data()
		// everything will be broken if we don't do this, and call package_can_accept_item() in a loop
		$package->summary_data = null;
		$staggered             = $package->fitment->wheel_set->get_selected()->is_staggered();

		$data = $package->collect_summary_data();

		$tires           = $data[ 'tires' ];
		$rims            = $data[ 'rims' ];
		$front_tires     = $data[ 'front_tires' ];
		$rear_tires      = $data[ 'rear_tires' ];
		$front_rims      = $data[ 'front_rims' ];
		$rear_rims       = $data[ 'rear_rims' ];
		$universal_rims  = $data[ 'universal_rims' ];
		$universal_tires = $data[ 'universal_tires' ];

		// So many things to consider here.
		// - If a staggered package has 2 front rims...
		// - we try to add 2 more front rims. Shouldn't we just pretend they are rear and let them be added?
		// - we try to add 2 more rear rims. Is this the same as having 4 universal rims?
		// - do we care about having 2 front and 2 rears on a non staggered pkg?
		// - If adding 2 rims to pkg which has 2 rims from another brand, is this an issue?
		// - does this only come from coding errors, or is it possible that one day in the future
		// we'll allow people to make their own custom staggered fitment packages without even specifying
		// whether or not their custom vehicle is staggered. I think at a minimum a package must always
		// be staggered or non-staggered, otherwise we can't figure these things out.

		// check tires exist
		if ( $type === 'tire' ) {

			// check the simple stuff first
			if ( $staggered ) {

				if ( $loc === 'front' && $front_tires > 0 ) {
					$why = 'has_front_tires';

					return false;
				} else if ( $loc === 'rear' && $rear_tires > 0 ) {
					$why = 'has_rear_tires';

					return false;
				}

			} else {

				if ( $tires > 0 ) {
					$why = 'has_tires';

					return false;
				}
			}

			$fits = false;
			if ( $loc === 'universal' ) {
				$fits = tire_fits_fitment( $part_number, $package->fitment, 'front' );
			} else if ( $loc === 'front' ) {
				$fits = tire_fits_fitment( $part_number, $package->fitment, 'front' );
			} else if ( $loc === 'rear' ) {
				$fits = tire_fits_fitment( $part_number, $package->fitment, 'rear' );
			}

			if ( ! $fits ) {
				$why = 'tire_fitment_mismatch';

				return false;
			}

		} else if ( $type === 'rim' ) {


			// check the simple stuff first
			if ( $staggered ) {

				if ( $loc === 'front' && $front_rims > 0 ) {
					$why = 'has_front_rims';

					return false;
				} else if ( $loc === 'rear' && $rear_rims > 0 ) {
					$why = 'has_rear_rims';

					return false;
				}

			} else {

				if ( $rims > 0 ) {
					$why = 'has_rims';

					return false;
				}
			}

			$fits = false;
			if ( $loc === 'universal' ) {
				$fits = rim_fits_fitment( $part_number, $package->fitment, 'front' );
			} else if ( $loc === 'front' ) {
				$fits = rim_fits_fitment( $part_number, $package->fitment, 'front' );
			} else if ( $loc === 'rear' ) {
				$fits = rim_fits_fitment( $part_number, $package->fitment, 'rear' );
			}

			if ( ! $fits ) {
				$why = 'rim_fitment_mismatch';

				return false;
			}
		}

		$why = 'accepted';
		$why .= $staggered ? '_staggered' : '_non_staggered';
		$why .= '_' . gp_test_input( $type );

		return true;
	}

	/**
	 * could be renamed to: is_rim_or_tire_array_valid
	 *
	 * Important: if ( $item['package_id'] ) is empty, we still need to return true. Sometimes we need to check this
	 * before settings $item['package_id']. This just checks for really basic and important array types.
	 *
	 * @param $item
	 */
	public function is_item_valid( $item ) {

		if ( ! is_array( $item ) ) {
			return false;
		}

		if ( ! isset( $item[ 'type' ] ) ) {
			return false;
		}

		// we could just default the location to front or rear, but i'm more concerned about making
		// an error and having it go unnoticed. therefore.. whenever adding cart items that are tires or rims,
		// we'll have to remember to always specify a valid location. If we want to default the $item to have
		// a universal location.. do it long before adding to the cart I guess...
		if ( $item[ 'type' ] === 'rim' || $item[ 'type' ] === 'tire' ) {
			$loc = gp_if_set( $item, 'loc' );
			if ( ! in_array( $loc, array( 'front', 'rear', 'universal' ) ) ) {
				return false;
			}
		}

		if ( ! isset( $item[ 'quantity' ] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param Vehicle $vehicle
	 *
	 * @return bool|int
	 */
	public function create_package_from_vehicle_instance( $vehicle ) {

		if ( ! $vehicle instanceof Vehicle ) {
			return false;
		}

		if ( ! $vehicle->is_complete() ) {
			return false;
		}

		// this has no fitment data - this is stored separately in cart packages.
		// we also store names to avoid database/api queries to instantiate the cart object which needs to be done
		// on every page load.
		$vehicle_data = array(
			'make' => $vehicle->make,
			'model' => $vehicle->model,
			'year' => $vehicle->year,
			'trim' => $vehicle->trim,
			'make_name' => $vehicle->make_name,
			'model_name' => $vehicle->model_name,
			'year_name' => $vehicle->year_name,
			'trim_name' => $vehicle->trim_name,
		);

		if ( ! $vehicle->fitment_object ) {
			return false;
		}

		// this returns the package ID if its inserted, false otherwise
		return $this->create_package_directly( $vehicle_data, $vehicle->fitment_object );
	}

	/**
	 * You probably want to use other functions that do some validation for you. This
	 * returns the "package ID" of the newly created package regardless of what junk you pass in.
	 *
	 * @param $vehicle_data
	 * @param $fitment_data
	 */
	public function create_package_directly( $vehicle_data, Fitment_Singular $fitment_data ) {
		$package_id = $this->get_new_package_id();
		$data       = array(
			'id' => $package_id, // ID does into package instance
			'vehicle' => $vehicle_data,
			'fitment' => $fitment_data,
		);

		//		echo 888888;
		//		echo '<pre>' . print_r( $data, true ) . '</pre>';

		$package                       = new Cart_Package( $data );
		$this->packages[ $package_id ] = $package;

		// if an error occurred, return false. This also should not be zero.
		return $package_id;
	}

	/**
	 * See get_new_package_id()
	 *
	 * @return int|string
	 */
	protected function get_new_item_id() {

		$key = 'last_cart_item_id';

		// again, this absolutely cannot be zero.
		$_SESSION[$key] = gp_if_set( $_SESSION, $key, 1 );
		$last_id = $_SESSION[$key];

		$new = $last_id + 1;

		// this fallback probably shouldn't trigger
		while( in_array( $new, array_keys( $this->packages ) ) ) {
			$new++;
		}

		$_SESSION[$key] = $new;

		return $new;
	}

	/**
	 * - CANNOT return zero.
	 * - Must be unique to other package IDs
	 * - As a further requirement, lets try to make this unique based on each users session.
	 * - Why? Because IDs are passed around in $_GET. If a user deleted a package, and has another page open
	 * that references a package by its ID, its possible that it is now referencing another package.
	 * - Actually, the code does a good job of taking care of this. In the case of item IDs however,
	 * its just going to work better this way when a user adds an item to the cart, and then chooses
	 * a vehicle for that item later. We're going to pass something like $_GET['remove_item'] = 3 to the URL.
	 * So we would either like item with ID 3 to not exist, or to be the same item 3 at the time the user clicked the
	 * URL.
	 *
	 * @return bool|mixed
	 */
	protected function get_new_package_id() {

		$key = 'last_cart_package_id';

		// again, this absolutely cannot be zero.
		$_SESSION[$key] = gp_if_set( $_SESSION, $key, 1 );
		$last_id = $_SESSION[$key];

		$new = $last_id + 1;

		// this fallback probably shouldn't trigger
		while( in_array( $new, array_keys( $this->packages ) ) ) {
			$new++;
		}

		$_SESSION[$key] = $new;

		return $new;
	}

	/**
	 * @param $item
	 *
	 * @return string
	 */
	public function get_mount_balance_item_description( $item ) {

		$item = $this->get_item( $item );

		if ( ! $item || ! $item->type === 'mount_balance' ) {
			return '';
		}

		$ret = 'Each unit includes mount & balance for 1 tire and 1 rim.';

		return $ret;
	}

	/**
	 * @param $item
	 *
	 * @return string
	 */
	public function get_install_kit_item_description( $item ) {

		$item = $this->get_item( $item );

		if ( ! $item || ! $item->type === 'install_kit' ) {
			return '';
		}

		$package = $this->get_package( $item->package_id );

		if ( ! $package ) {
			return '';
		}

		// we should have most of the information we need to
		// put together a very detailed description if we need to,
		// but for now I think we just want to show many nuts are included
		// so that the user understands they only need 1 unit of hardware kit for each 4 rims
		$stud_holes   = $package->fitment->stud_holes;
		$pcd          = $package->fitment->pcd;
		$bolt_pattern = $package->fitment->bolt_pattern;
		$lock_type    = $package->fitment->lock_type;
		$lock_text    = $package->fitment->lock_text;
		$center_bore  = $package->fitment->center_bore;

		$nuts = $stud_holes * 4;
		$ret = 'Each unit includes a set of ' . $nuts . ' chrome nuts, 4 centering rings (as needed) and 4 tire valves.';
		// $ret = 'Each unit includes ........... ';

		return $ret;
	}
}

/**
 * @return Cart
 */
function get_cart_instance() {

	global $cart;

	if ( $cart instanceof Cart ) {
		return $cart;
	}

	// if we did: $cart = new Cart()... then... our global variable $cart
	// which is null, would be assigned to the return value of new Cart()... but
	// that would use Cart::__construct() which inside that function would use
	// the global $cart variable and override it with the current instance..
	// I think this is fine, but its a bit confusing, so we'l
	// rely on Cart::__construct() setting global $cart to the proper object.

	// Cart::load_instance() calls Cart::__construct() which sets up the global $cart variable.
	Cart::load_instance();

	return $cart;
}

/**
 * Class Cart_Summary
 */
Class Cart_Summary {
	public $count_tires_on_rims = 0;
	public $count_tires_alone = 0;
	public $count_rims_alone = 0;
}

/**
 * Class Cart_Stock_Summary
 */
Class Cart_Stock_Summary{

	public $data = array();
	public $in_stock = null;

	/**
	 * The data block array will contain:
	 *
	 * $arr['qty'], $arr['in_stock'], $arr['stock'], and $arr['item_ids'],
	 * such that $item_id passed in is in the array $arr['item_ids']
	 */
	public function get_data_block_from_item_id( $item_id ){

		foreach ( $this->data as $type => $type_data ) {
			if ( $type_data ) {
				foreach ( $type_data as $part_number => $data_block ) {
					if ( in_array( $item_id, $data_block['item_ids'] ) ) {
						return $data_block;
					}
				}
			}
		}

		return false;
	}
}
