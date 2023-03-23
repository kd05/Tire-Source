<?php



/**
 * Class Add_To_Cart_Handler
 */
Class Add_To_Cart_Handler {

	/**
	 * $this->response['errors'] is an array of errors to show to the user
	 * $this->response['debug'] is an array of possible errors, but that aren't shown to the user
	 * @var array
	 */
	public $response;

	/** @var Cart */
	public $cart;

	/**
	 * Very simple debug array for when things go wrong. Shows where we got to and happened.
	 *
	 * @var array
	 */
	public $debug;

	/**
	 * @var Vehicle
	 */
	public $vehicle;

	/**
	 * Add_To_Cart_Handler constructor.
	 *
	 * @param Cart    $cart
	 * @param Vehicle $vehicle
	 */
	public function __construct( Cart $cart, Vehicle $vehicle ) {
		$this->response = array();
		$this->debug    = array();

		$this->cart    = $cart;
		$this->vehicle = $vehicle;
	}

	/**
	 * Calls the other more specific methods.
	 */
	public function run_user_data( $userdata ) {

		$ret = false;
		$type = gp_if_set( $userdata, 'type' );

		// type multi just has an array of $items... possibly just 1. No reason not to use it for single items.
		// in fact, it was added after our other $types and may be used almost 100% of the time
		if ( $type === 'multi' ) {
			$success = $this->run_type_multi( $userdata );
			if ( $success ) {
				$ret = true;
			} else {
				$this->add_error( 'Product(s) Failed' );

				$ret = false;
			}
		}

//		if ( $type === 'package' ) {
//			$this->add_error( 'This type should no longer be called' );
//			return false;
////			$success = $this->run_type_package( $userdata );
////			if ( $success ) {
////				return true;
////			} else {
////				$this->add_error( 'Package failed' );
////
////				return false;
////			}
//		}

		// possibly not in use anymore, in favor of type multi
		if ( $type === 'tire' ) {
			$item_id = $this->run_type_tire_or_rim( $userdata );
			if ( $item_id ) {
				$ret = true;
			} else {
				$this->add_error( 'Tire failed' );

				$ret = false;
			}
		}

		// possibly not in use anymore, in favor of type multi
		if ( $type === 'rim' ) {
			$item_id = $this->run_type_tire_or_rim( $userdata );
			if ( $item_id ) {
				$ret = true;
			} else {
				$this->add_error( 'Rim failed' );

				$ret = false;
			}
		}

		// remove an existing item ?
		$replace = get_user_input_singular_value( $userdata, 'replace' );

		if ( $replace ){

			// dont remove the item if the main action was not successful
			if ( $ret ) {
				$item_to_remove = $this->cart->get_item( $replace );

				if ( $item_to_remove ) {
					$this->cart->item_delete( $item_to_remove );
				}
			}
		}

		return $ret;
	}

	/**
	 * Checks if package exists. If it doesn't, and vehicle data is provided, attempts
	 * to create the package and returns the newly created package ID or false.
	 * - Returns false if package doesn't exist in the cart, and the package was not created.
	 * - Returns the package_id if a new package was created.
	 * - Should return false if we tried to make a new package, but failed.
	 * - Returns 'exists' if the package passed in already existed. Therefore, you already have the package_id.
	 *
	 * @param $user_input_string
	 *
	 * @return bool|int|string
	 */
	public function check_package_exists_or_create( $user_input_string, &$extra = '' ) {

		$pkg = $user_input_string;
		$pkg = gp_test_input( $pkg );
		// careful, json_encode and/or browser is trying to break our code here
		// (its turning (bool) false into (string) "false")
		$pkg = $pkg === "false" ? false : $pkg;
		$pkg = $pkg === "0" ? 0 : $pkg;

		if ( $pkg ) {

			if ( $this->cart->package_exists( $pkg ) ) {

				$extra = 'existed';

				return $pkg;

			} else {

				$package_id = $this->cart->create_package_from_vehicle_instance( $this->vehicle );

				if ( $package_id ) {
					$extra = 'pkg_invalid-new_success';

					return $package_id; // return package ID not boolean true
				} else {
					$extra = 'pkg_invalid-new_failed';

					return false;
				}

			}
		} else {

			$package_id = $this->cart->create_package_from_vehicle_instance( $this->vehicle );

			if ( $package_id ) {
				$extra = 'pkg_empty-new_success';

				return $package_id; // return package ID not boolean true
			} else {
				$extra = 'pkg_empty-new_failed';

				return false;
			}
		}
	}

	/**
	 * Make sure $userdata['type'] is set accordingly.
	 *
	 * Returns false on failure, the newly created $item_id on success. Might suck if we
	 * need to get the newly created $package_id, which could have been created for one of many
	 * different reasons. But in this case, we could pass a variable by reference.
	 *
	 * @param $userdata
	 */
	public function run_type_tire_or_rim( $userdata ) {

		$type = gp_if_set( $userdata, 'type' );

		if ( $type !== 'rim' && $type !== 'tire' ) {
			$this->add_error( 'Tire/Rim: Invalid Type' );

			return false;
		}

		// remember, if vehicle is complete and package is not provided,
		// we make a new package, and then put the item into that package.
		$vehicle_complete = $this->vehicle && $this->vehicle->is_complete();

		if ( $vehicle_complete ) {

			$this->add_debug( 'Vehicle is complete' );

			$pkg = gp_if_set( $userdata, 'pkg' );
			$pkg = $this->sanitize_pkg_string( $pkg );

			$this->add_debug( 'Initial package_id: ' . $pkg );

			// returns package ID or false
			$extra      = ''; // passed by reference
			$package_id = $this->check_package_exists_or_create( $pkg, $extra );

			$this->add_debug( 'check/create package: ' . $extra );
			$this->add_debug( 'check/create package_id: ' . $package_id );

			if ( ! $package_id ) {
				throw new Exception( 'Run tire/rim, ' . $extra );
			}

			// if $userdata['pkg'] is false or empty, that is fine.
			$userdata[ 'pkg' ] = $package_id;

			// remember, $item now has the index 'package_id', and not 'pkg' as passed in via $_GET
			$item = $this->cart->create_tire_or_rim_item_from_userdata( $userdata );

			// possibly not needed but that's fine
			if ( ! $item ) {
				$this->add_error( 'Invalid Tire/Rim Item (run rim/tire (1))' );

				return false;
			}

			// this re-checks item validity... more than once...
			$item_id = $this->verify_and_add_item_to_package( $item, $package_id, true );

			// return the $item_id or false, but not true.
			if ( $item_id ) {
				return $item_id;
			} else {
				$this->add_error( 'Verify and add item failed in run rime/tire' );

				return false;
			}

		} else {

			// add the item un-packaged...
			$item = $this->cart->create_tire_or_rim_item_from_userdata( $userdata );

			// it would be bad if our create_item_from_userdata functions returned an invalid $item
			if ( $this->cart->is_item_valid( $item ) ) {
				$item_id = $this->cart->item_insert( $item );

				// return the $item_id or false, but not true.
				if ( $item_id ) {
					return $item_id;
				} else {
					$this->add_error( 'Failed to add item un-packaged' );

					return false;
				}
			}

			$this->add_error( 'Invalid Tire/Rim Item (run rim/tire (2))' );

			return false;
		}
	}

	/**
	 * This item can be a tire or a rim. Not sure yet if it will support other item types.
	 * Fallback means we'll attempt to put the item in a newly created package if it didn't already exist.
	 *
	 * Check the validity of the $vehicle instance before calling this, because this only falls back to adding
	 * an item to a newly created package. It does not fallback to adding an item without a package.
	 *
	 * @param      $item
	 * @param      $package_id
	 * @param bool $fallback
	 */
	public function verify_and_add_item_to_package( $item, $package_id, $fallback = true, &$new_package_id = false ) {

		$this->add_debug( 'verify_and_add_item_to_package' );

		$why    = '';
		$accept = $this->cart->package_can_accept_item( $item, $package_id, $why );

		if ( $accept ) {
			$this->add_debug( 'Accepted: True' );
		} else {
			$this->add_debug( 'Accepted: False' );
		}

		$this->add_debug( 'Why: ' . $why );

		if ( $accept ) {

			$item_id = $this->cart->item_insert( $item );

			if ( $item_id ) {
				return $item_id;
			} else {
				$this->add_error( 'Could not add item' );

				return false;
			}

		} else {

			if ( ! $fallback ) {
				return false;
			}

			$this->add_debug( 'Fallback is true, doing fallback action' );

			// fallback: make new package and then insert
			if ( $this->cart->is_item_valid( $item ) ) {

				// remember we havent checked our vehicle instance in this function, so the fallback package ID might be false
				$fallback_package_id = $this->cart->create_package_from_vehicle_instance( $this->vehicle );

				$this->add_debug( 'Newly created fallback package ID: ' . $fallback_package_id );

				if ( ! $fallback_package_id ) {
					return false;
				} else {
					// some functions might need to know the ID of the new package in addition to the ID of the new item
					// this param is passed by reference.
					$new_package_id = $fallback_package_id;
				}

				// since we checked is_item_valid, we knows its an array
				$item[ 'package_id' ] = $fallback_package_id;

				// this also re-checks the items validity (since we modified it)
				$why     = '';
				$accept2 = $this->cart->package_can_accept_item( $item, $fallback_package_id, $why );

				if ( $accept2 ) {
					$this->add_debug( 'Accepted (Round 2): True' );
				} else {
					$this->add_debug( 'Accepted (Round 2): False' );
				}
				$this->add_debug( 'Why (Round 2): ' . $why );

				if ( $accept2 ) {
					$item_id = $this->cart->item_insert( $item );

					if ( $item_id ) {
						return $item_id;
					} else {
						$this->add_error( 'Fallback package, item still not added' );

						return false;
					}
				}

				return false;

			} else {
				$this->add_debug( 'Item is invalid...' );

				return false;
			}
		}
	}

	/**
	 * @param $userdata
	 */
	public function run_type_multi( $userdata ) {

		// see if package ID was passed in and if it is valid.
		$pkg = gp_if_set( $userdata, 'pkg' );
		$package = $pkg ? $this->cart->get_package( $pkg ) : null;
		$package = $package instanceof Cart_Package ? $package : null;
		// in case its otherwise unclear... this means we have $userdata['pkg'] AND the package exists in the Cart
		$package_exists = $package ? true : false;

		$items = gp_if_set( $userdata, 'items', array() );
		$items = is_array( $items ) ? $items : array();

		// defaults - may override
		$items_to_process = $items;
		$package_id_to_inject = $package ? $package->id : false;

		// this is complicated to explain. ... Let's do a scenario.
		// Select a vehicle -> Add 4 tires to cart -> Go to cart page hit "Add Rims"
		// -> This takes you to packages.php. All buttons there (on packages.php) do the "same"
		// thing, meaning they submit to this file and say "Add these 4 tires AND these 4 rims",
		// And some of them say "Also, add them to the package with this ID".
		// Several issues may arise:
		// 1. "upgrading an existing package"... ie. the package has rims OR tires, and we're saying: "add rims AND tires".
		// 2. Hitting the same button twice (the button doesn't magically update itself after ajax add to cart!!)
		// 3. The package might not exist on first click of the button, and SHOULD be full on second click of the button.
		// So, how do we handle this?
		// Loop through the items being submitted, and see which ones cannot be added to the package.
		// If all can be added: just continue on as normal (this means packages.php was not "upgrading an existing package")
		// If no items can be added: this means the button was probably clicked a second time, make a new package and add to that one instead
		// If some items can be added: this means packages.php was probably "upgrading an existing package", so add only a partial list of items.
		// All this logic is below:...

		// Vehicle AND package are both provided, and both are valid
		if ( $this->vehicle->is_complete() && $package_exists ) {

			$original_items_bkup = $items; // may adjust $items array
			$count_removed = 0;
			$count_items = count( $items );

			if ( $items && is_array( $items ) ) {
				foreach ( $items as $index=>$item ) {

					if ( ! $this->cart->package_can_accept_item( $item, $package->id ) ) {
						unset( $items[$index] );
						$count_removed++;
					}

					// Here is an alternate (more expensive, seemingly more prone to error) way to do "package_can_accept_item()"

//					$type = gp_if_set( $item, 'type' );
//					$part_number = gp_if_set( $item, 'part_number' );
//					if ( $type && $part_number ) {
//						$find_item_with = array(
//							'type' => $type,
//							'part_number' => $part_number,
//							'package_id' => $package->id,
//						);
//
//						if ( $this->cart->get_items_ids_with_conditions( $find_item_with ) ) {
//							unset( $items[$index] );
//							$count_removed++;
//						}
//					}
				}
			}

			// determine fallback behaviour
			// Note: lots of redundancy below. Variables get overriden with same values
			// but for sake of clarity, we do it anyways
			if ( $count_items === 0 ) {

				// process no items
				$items_to_process = array();

				// doesn't matter anyways
				$package_id_to_inject = null;

			} else if ( $count_items === $count_removed ) {
				// Use the original items, BUT, create a new package and use that ID instead.
				// (also redundant, we could simply do nothing)

				// Process all the items
				$items_to_process = $original_items_bkup;

				// BUT, put them in a new package
				$package_id_to_inject = $this->cart->create_package_from_vehicle_instance( $this->vehicle );

			} else if ( $count_removed === 0 ) {
				// Use the original items, and inject the original package ID
				// (again, redundant, but for sake of clarity...)

				// Process all the items
				$items_to_process = $original_items_bkup;

				// And.. in the same package that was passed in
				$package_id_to_inject = $package->id;

			} else {

				// Process PARTIAL list of items
				$items_to_process = $items;

				// into the same package that was passed in
				$package_id_to_inject = $package->id;
			}
		} else if ( $this->vehicle->is_complete() ){

			// Group items together based on their "package temporary IDs"
			// chances are, all items will have either the same pkg_temp_id, or
			// none of them will have this index set. But doing it this way would
			// in theory allow us to add 2 packages to the cart at one time.
			// although mixing between adding new packages and upgrading existing packages would be
			// quite confusing... for that we should simply just call run_type_multi() twice.

			$pkg_temp_ids = array();

			// get the unique "package temporary IDs from items (if any are set)
			if ( $items && is_array( $items ) ) {
				foreach ( $items as $index => $item ) {
					$pkg_temp_id = gp_if_set( $item, 'pkg_temp_id', false );
					if ( $pkg_temp_id ) {
						$pkg_temp_ids[$pkg_temp_id] = null;
					}
				}
			}

			// create a new package for each uniquely specified "package temp ID"
			if ( $pkg_temp_ids ){
				foreach ( $pkg_temp_ids as $x=>$y ) {
					$new_pkg_id = $this->cart->create_package_from_vehicle_instance( $this->vehicle );
					if ( $new_pkg_id ) {
						$pkg_temp_ids[$x] = $new_pkg_id;
					}
				}
			}

			// replace any "temp IDs" with real package IDs from newly create packages
			if ( $items && is_array( $items ) ) {
				foreach ( $items as $index => $item ) {
					if ( isset( $item['pkg_temp_id'] ) ) {
						// set $item['pkg'] not $item['pkg_temp_id']
						$items[$index]['pkg'] = gp_if_set( $pkg_temp_ids, $item['pkg_temp_id'] );
					}
				}
			}

			$items_to_process = $items;
			$package_id_to_inject = false; // no need to inject ID we already have it

		}

		$errors      = [];
		$items_found = false;
		if ( $items_to_process && is_array( $items_to_process ) ) {
			foreach ( $items_to_process as $item ) {

				$item_package_id = gp_if_set( $item, 'pkg' );

				// I don't think items will ever have package IDs specified
				// but just in case they do we will leave it. HOWEVER, this
				// is definitely not guaranteed to work well in all situations.
				if ( $package_id_to_inject && ! $item_package_id ) {
					$item['pkg'] = $package_id_to_inject;
				}

				$items_found = true;
				$success     = $this->run_type_tire_or_rim( $item );
				if ( $success ) {
					$this->add_debug( '1 item added' );
				} else {
					$errors[] = 'Item failed ' . gp_make_singular( $item );
				}
			}
		}

		// if a package was provided, sync it regardless of if it changed
//		if ( $package_exists ) {
//			$this->cart->sync_package_optional_items( $package );
//		}
//
//		// maybe sync the newly created package
//		if ( $package_id_to_inject && $package_id_to_inject != $package->id ) {
//			$this->cart->sync_package_optional_items( $package_id_to_inject );
//		}

		if ( $errors ) {
			$this->add_error( $errors );

			return false;
		}

		if ( ! $items_found ) {
			$this->add_error( 'No items were found to be added.' );

			return false;
		}

		$this->add_debug( 'All items added successfully' );

		return true;
	}

	/**
	 * Add multiple items to the same package...
	 *
	 * Be aware: this might not be the same as adding multiple single items with
	 * a package specified.
	 *
	 * Different rules may apply for multiple single items, vs. one function
	 * to add multiple items. Do we use the same rules for whether or not
	 * an item can be added to a package? What if some items can not be added?
	 * Do we do nothing and throw an error, do we make a new package
	 * for those items, or do we put only some items into the package, and do
	 * nothing with the remaining. I don't believe there is an obvious answer here.
	 *
	 * @param $items
	 * @param $package_id
	 */
	public function add_multiple_items_to_package( $items, $package_id ) {

		if ( ! $this->cart->package_exists( $package_id ) ) {
			listen_set( 'invalid_package', 'add_multiple_items_to_package' );

			return false;
		}

		if ( ! $items || ! is_array( $items ) ) {
			return false;
		}

		$item_errors = array();

		// I think $items here must only contain tires and rims. Mount & Balance, accessories probably done separately.
		foreach ( $items as $index => $item ) {

			$part_number = gp_if_set( $item, 'part_number' );
			$type        = gp_if_set( $item, 'type' );

			// skip item if it exists in the package, without throwing any errors
			// this happens intentionally when using the "upgrade to package" functionality
			// users go to package.php with a rim/tire selected
			if ( $part_number && $type ) {
				$item_exists = $this->cart->get_item_ids_by_package( array(
					'type' => $type,
					'part_number' => $part_number,
					'package_id' => $package_id,
				) );

				if ( $item_exists ) {
					continue;
				}
			}

			// checking item validity ensures its an array
			if ( ! $this->cart->is_item_valid( $item ) ) {
				$item_errors[ $index ] = 'Invalid Item';
				continue;
			}

			// set the items package ID
			$item[ 'package_id' ] = $package_id;

			// I can't think of an instance where our code will print the correct add to cart data to the front-end
			// and still want to $fallback to creating a new package, therefore, its false for now.
			// the other issue is that when sending a large number of items to a single package, if we fallback to making a new package
			// they're all going to end up in their own separate packages, instead of some in 1 package, and a bunch more in a second etc.
			$fallback = false;

			// try to add the item to the package, and possibly $fallback to making a new package.
			$item_id = $this->verify_and_add_item_to_package( $item, $package_id, $fallback );

			if ( $item_id ) {
				$this->add_debug( 'Item success' );
			} else {
				$item_errors[ $index ][] = 'Item Insert Failed.';
			}
		}

		if ( $item_errors ) {
			listen_set( $item_errors, 'add_multiple_items_to_package' );

			return false;
		}

		return true;
	}

	/**
	 * Important to do this for 2 reasons:
	 * - Sanitize user input
	 * - Browser or json_decode is trying to make us screw everything up by setting pkg to (string) "false"
	 *
	 * @param $pkg
	 */
	public function sanitize_pkg_string( $pkg ) {
		$pkg = gp_test_input( $pkg );
		// careful, json_encode and/or browser is trying to break our code here
		// (its turning (bool) false into (string) "false")
		$pkg = $pkg === "false" ? false : $pkg;
		$pkg = $pkg === "0" ? 0 : $pkg;

		return $pkg;
	}

	/**
	 * These are shown to the user.
	 *
	 * @param $msg
	 */
	public function add_error( $msg ) {
		$this->response[ 'errors' ][] = $msg;
	}

	/**
	 * @param $msg
	 */
	public function add_debug( $msg ) {
		$this->response[ 'debug' ][] = $msg;
	}

	/**
	 * @return array
	 */
	public function get_response() {
		return $this->response;
	}
}