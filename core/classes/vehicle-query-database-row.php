<?php

define( 'VQDR_INT_TIRE_1', 1 );
define( 'VQDR_INT_TIRE_2', 2 );
define( 'VQDR_INT_RIM_1', 3 );
define( 'VQDR_INT_RIM_2', 4 );

/**
 * This is prefixed with "Vehicle_", but you should think of it as:
 *
 * "Any set of products (tires or rims) that can be paired via staggered fitments,
 * or via packages". As of now, this only applies to Vehicles. HOWEVER, it might end up
 * being useful to use this to represent literally any single or pair of
 * rims or tires which may or may not come from a vehicle.
 *
 * This object can represent all of the data in a single product card on an archive page,
 * or a single row in a product table for example. It could contain:
 *
 * Front tires (just tires)
 * Front and rear tires (staggered tires)
 * Front rims (just rims)
 * Front and rear rims (staggered rims)
 * Front Tire + Front Rim (regular package)
 * Front tire, rear tire, front rim, rear rim (staggered package)
 * With or without a vehicle.
 *
 * Class Vehicle_Query_Database_Row
 */
Class Vehicle_Query_Database_Row {

	/**
	 * This represents $this->db_tire_1, but holds more information, such as its location,
	 * and also allows us to get the product in the opposite location, without
	 * having to care about whether the product is a tire or a rim.
	 */
	const INT_TIRE_1 = VQDR_INT_TIRE_1;
	const INT_TIRE_2 = VQDR_INT_TIRE_2;
	const INT_RIM_1 = VQDR_INT_RIM_1;
	const INT_RIM_2 = VQDR_INT_RIM_2;

	/**
	 * We may use this in the future if we need to differentiate between a front tire with a vehicle,
	 * and a front tire without a vehicle.
	 *
	 * @var
	 */
	// public $has_vehicle;

	/**
	 * most likely always the same as app_get_locale()
	 *
	 * @var
	 */
	public $locale;

	/**
	 * @see Fitment_General::get_size_from_fitment_and_wheel_set()
	 *
	 * @var
	 */
	public $size;

	/**
	 * @see Vehicle
	 *
	 * @var
	 */
	public $fitment_slug;

	/**
	 * @see Vehicle
	 *
	 * @var
	 */
	public $sub_slug;

	/**
	 * @see Vehicle
	 *
	 * @var
	 */
	public $oem;

	// since front and rear should always have the same brand/model,
	// omit rear brand and rear model, but still prefix brand/model with front.
	public $front_tire;
	public $rear_tire;
	public $front_rim;
	public $rear_rim;
	public $tire_brand;
	public $tire_model;
	public $rim_brand;
	public $rim_model;
	public $rim_finish;

	/**
	 * @var DB_Tire|null
	 */
	public $db_tire_1;

	/**
	 * @var DB_Tire|null
	 */
	public $db_tire_2;

	/**
	 * @var DB_Rim|null
	 */
	public $db_rim_1;

	/**
	 * @var DB_Rim|null
	 */
	public $db_rim_2;

	/**
	 * Vehicle_Query_Database_Row constructor.
	 */
	public function __construct() {
	}

	/**
	 * todo: may need to let vehicle information be passed in here ...
	 *
	 * @param $tire_1
	 * @param $tire_2
	 * @param $rim_1
	 * @param $rim_2
	 */
	public static function create_instance_from_products( $tire_1 = null, $tire_2 = null, $rim_1 = null, $rim_2 = null, $locale = null ) {

		$locale = app_get_locale_from_locale_or_null( $locale );

		$self            = new self();
		$self->locale = $locale;
		$self->db_tire_1 = $tire_1 instanceof DB_Tire ? $tire_1 : null;
		$self->db_tire_2 = $tire_2 instanceof DB_Tire ? $tire_2 : null;
		$self->db_rim_1  = $rim_1 instanceof DB_Rim ? $rim_1 : null;
		$self->db_rim_2  = $rim_2 instanceof DB_Rim ? $rim_2 : null;

		if ( ! IN_PRODUCTION ) {
			queue_dev_alert( 'VQDB::create_instance_from_products', get_pre_print_r( $self->get_debug_array() ) );
		}

		return $self;
	}

	/**
	 * The meaning of 'entity' here basically means one row from one table
	 * in the database represented one of our class properties.
	 *
	 * @param $class_property
	 * @param $data - might be an array of stdClass
	 */
	private function setup_entity( $class_property, $fields, $raw_data, $prefix ) {

		$this->{$class_property} = new stdClass();

		foreach ( $fields as $field ) {
			$this->{$class_property}->$field = gp_if_set( $raw_data, $prefix . $field, null );
		}
	}

    /**
     * Get an array of DB_Tire or DB_Rim instances,
     *
     * ie. 2 tires for stg tire, tire + rim for pk, etc.
     *
     * @return array
     */
	public function get_db_products(){
	    return array_map( function( $int ) {
	        return $this->get_product_from_int( $int );
        }, $this->items_integers_array() );
    }

	/**
	 *
	 */
	public function get_debug_array(){

		$ret = array();
		$ret['integers'] = implode_comma( $this->items_integers_array( false ) );
		$ret['is_staggered'] = $this->is_staggered();
		$ret['count_products'] = $this->count_tire_and_rim_objects_in_self();
		$ret['is_tire'] = $this->is_tire();
		$ret['is_rim'] = $this->is_rim();
		$ret['is_pkg'] = $this->is_pkg();

		$ret['part_numbers'] = array_map( function( $int ){
			$item = $this->get_product_from_int( $int );
			return $item instanceof DB_Product ? $item->get( 'part_number' ) : '__UNDEFINED_PART_NUMBER__';
		}, $this->items_integers_array( false ) );

		foreach ( $this->items_integers_array( true ) as $int ) {
			$item = $this->get_product_from_int( $int );
			$cls = $item ? get_class( $item ) : '';
			$ret['db_' . $int . '_class'] = $cls;
		}

		return $ret;
	}

	/**
	 * intelligently setup class properties like $this->db_tire_1, based off of raw query results
	 * like $this->front_tire, $this->tire_brand, and $this->tire_model. This must be idempotent when
	 * being called many times on the same instance.
	 */
	public function setup_db_objects() {

		$stg     = $this->is_staggered( false );
		$is_tire = $this->is_tire( false );
		$is_rim  = $this->is_rim( false );

		// Front Tire
		if ( $is_tire && ! $this->db_tire_1 ) {
			$this->db_tire_1 = self::build_db_tire( $this->front_tire, $this->tire_brand, $this->tire_model );
		}

		// Rear Tire
		if ( $stg && $is_tire && ! $this->db_tire_2 ) {
			$this->db_tire_2 = self::build_db_tire( $this->rear_tire, $this->tire_brand, $this->tire_model );
		}

		// Front Rim
		if ( $is_rim && ! $this->db_rim_1 ) {
			$this->db_rim_1 = self::build_db_rim( $this->front_rim, $this->rim_brand, $this->rim_model, $this->rim_finish );
		}

		// Rear Rim
		if ( $stg && $is_rim && ! $this->db_rim_2 ) {
			$this->db_rim_2 = self::build_db_rim( $this->rear_rim, $this->rim_brand, $this->rim_model, $this->rim_finish );
		}
	}

    /**
     * @param bool $via_objects
     * @return bool
     */
	public function is_staggered( $via_objects = true ) {
		if ( $via_objects ) {
			return ( $this->db_tire_1 && $this->db_tire_2 ) || ( $this->db_rim_1 && $this->db_rim_2 );
		} else {
			return ( $this->front_tire && $this->rear_tire ) || ( $this->front_rim && $this->rear_rim );
		}
	}

	/**
	 * @param bool $via_objects
	 *
	 * @return bool
	 */
	public function is_rim( $via_objects = true ) {
		if ( $via_objects ) {
			return $this->db_rim_1 ? true : false;
		} else {
			return $this->db_rim_2 ? true : false;
		}
	}

	/**
	 * @param bool $via_objects
	 *
	 * @return bool
	 */
	public function is_tire( $via_objects = true ) {
		if ( $via_objects ) {
			return $this->db_tire_1 ? true : false;
		} else {
			return $this->front_tire ? true : false;
		}
	}

    /**
     * @param bool $via_objects
     * @return bool
     */
	public function is_pkg( $via_objects = true ) {
		return $this->is_rim( $via_objects ) && $this->is_tire( $via_objects );
	}

    /**
     * for example.. pair tires with rims if you have one entity for each one.
     *
     * @param Vehicle_Query_Database_Row $merge
     */
	public function merge_row( Vehicle_Query_Database_Row $merge ) {

		$props = array(
			'front_tire',
			'rear_tire',
			'tire_brand',
			'tire_model',
			'front_rim',
			'rear_rim',
			'rim_brand',
			'rim_finish',
			'rim_model',
		);

		foreach ( $props as $prop ) {
			if ( $this->{$prop} === null ) {
				$this->{$prop} = $merge->{$prop};
			}
		}
	}

    /**
     * @param $row
     * @param string $prefix
     */
	public function setup_front_tire( $row, $prefix = 't1_' ) {
		$this->setup_entity( 'front_tire', DB_Tire::get_fields(), $row, $prefix );
	}

    /**
     * @param $row
     * @param string $prefix
     */
	public function setup_rear_tire( $row, $prefix = 't2_' ) {
		$this->setup_entity( 'rear_tire', DB_Tire::get_fields(), $row, $prefix );
	}

    /**
     * @param $row
     * @param string $prefix
     */
	public function setup_front_rim( $row, $prefix = 'r1_' ) {
		$this->setup_entity( 'front_rim', DB_Rim::get_fields(), $row, $prefix );
	}

    /**
     * @param $row
     * @param string $prefix
     */
	public function setup_rear_rim( $row, $prefix = 'r2_' ) {
		$this->setup_entity( 'rear_rim', DB_Rim::get_fields(), $row, $prefix );
	}

    /**
     * @param $row
     * @param string $prefix
     */
	public function setup_tire_brand( $row, $prefix = 'tb1_' ) {
		$this->setup_entity( 'tire_brand', DB_Tire_Brand::get_fields(), $row, $prefix );
	}

    /**
     * @param $row
     * @param string $prefix
     */
	public function setup_tire_model( $row, $prefix = 'tm1_' ) {
		$this->setup_entity( 'tire_model', DB_Tire_Model::get_fields(), $row, $prefix );
	}

	/**
	 * @param        $row
	 * @param string $prefix
	 */
	public function setup_rim_finish( $row, $prefix = 'rf1_' ) {
		$this->setup_entity( 'rim_finish', DB_Rim_Finish::get_fields(), $row, $prefix );
	}

	/**
	 * @param        $row
	 * @param string $prefix
	 */
	public function setup_rim_model( $row, $prefix = 'rm1_' ) {
		$this->setup_entity( 'rim_model', DB_Rim_Model::get_fields(), $row, $prefix );
	}

	/**
	 * @param        $row
	 * @param string $prefix
	 */
	public function setup_rim_brand( $row, $prefix = 'rb1_' ) {
		$this->setup_entity( 'rim_brand', DB_Rim_Brand::get_fields(), $row, $prefix );
	}

	/**
	 * @param $rim
	 * @param $brand
	 * @param $model
	 * @param $finish
	 *
	 * @return null|static|DB_Rim
	 */
	public static function build_db_rim( $rim, $brand, $model, $finish ) {

		if ( ! $rim ) {
			return null;
		}

		$options = array();

		// inject related objects into the final product to avoid more db queries
		$_brand  = $brand ? DB_Rim_Brand::create_instance_or_null( $brand ) : false;
		$_model  = $model ? DB_Rim_Model::create_instance_or_null( $model ) : false;
		$_finish = $finish ? DB_Rim_Finish::create_instance_or_null( $finish, array(
			'brand' => $_brand,
			'model' => $_model,
		) ) : false;

		$options[ 'brand' ]  = $_brand ? $_brand : null;
		$options[ 'model' ]  = $_model ? $_model : null;
		$options[ 'finish' ] = $_finish ? $_finish : null;

		return DB_Rim::create_instance_or_null( $rim, $options );
	}

    /**
     * @param $tire
     * @param $brand
     * @param $model
     * @return DB_Tire|null
     * @throws Exception
     */
	public static function build_db_tire( $tire, $brand, $model ) {

		if ( ! $tire ) {
			return null;
		}

		$options = array();

		// inject related objects into the final product to avoid more db queries
		$_brand = $brand ? DB_Tire_Brand::create_instance_or_null( $brand ) : false;
		$_model = $model ? DB_Tire_Model::create_instance_or_null( $model ) : false;

		$options[ 'brand' ] = $_brand ? $_brand : null;
		$options[ 'model' ] = $_model ? $_model : null;

		return DB_Tire::create_instance_or_null( $tire, $options );
	}

	/**
	 * 1: Front tire (DB_Tire)
	 * 2: Rear tire (DB_Tire)
	 * 3: Front Rim (DB_Rim)
	 * 4: Rear Rim (DB_Rim)
	 *
	 * Sometimes, you might call this on array( 1, 2, 3, 4 ) knowing
	 * that some items won't be found. But if you can set $silent_if_error to
	 * false if you want to ensure you get an item. the script will abort if you don't however.
	 *
	 * @param      $int
	 * @param bool $silent_if_error
	 *
	 * @return DB_Rim|DB_Tire|null
	 */
	public function get_product_from_int( $int, $silent_if_error = true ) {

		switch ( $int ) {
			case self::INT_TIRE_1:
				$ret = $this->db_tire_1;
				break;
			case self::INT_TIRE_2:
				$ret = $this->db_tire_2;
				break;
			case self::INT_RIM_1:
				$ret = $this->db_rim_1;
				break;
			case self::INT_RIM_2:
				$ret = $this->db_rim_2;
				break;
			default:
				$ret = null;
		}

		if ( $ret ) {
			return $ret;
		}

		if ( ! $silent_if_error ) {
			// echo '<pre>' . print_r( $this, true ) . '</pre>';
			throw_dev_error( 'tried to access a front/rear tire/rim that does not exist' );
		}

		return null;
	}

    /**
     * Get an array of integers, such that when you call $this->get_product_from_int()
     * on each value, you'll get a DB_Tire or DB_Rim in return.
     *
     * @param bool $all
     * @return array
     */
	public function items_integers_array( $all = false ){
		$ret = [];

		if ( $all || $this->db_tire_1 ) {
			$ret[] = self::INT_TIRE_1;
		}
		if ( $all || $this->db_tire_2 ) {
			$ret[] = self::INT_TIRE_2;
		}
		if ( $all || $this->db_rim_1 ) {
			$ret[] = self::INT_RIM_1;
		}
		if ( $all || $this->db_rim_2 ) {
			$ret[] = self::INT_RIM_2;
		}
		return $ret;
	}

	/**
	 * Can use to verify things are working. For example, if you
	 * think you're working with staggered tires, you would expect this return value to be 2.
	 *
	 * In most cases, if this returns 0, something might be wrong. But, if your building
	 * this from raw database results, you might want to call setup_db_objects() first,
	 * otherwise, you may have started with self::create_instance_from_products().
	 *
	 * You can also get similar information from combining is_staggered(), is_tire(), is_rim(), and is_pkg()
	 */
	public function count_tire_and_rim_objects_in_self() {
		$c = 0;
		if ( $this->db_tire_1 ) {
			$c ++;
		}
		if ( $this->db_tire_2 ) {
			$c ++;
		}
		if ( $this->db_rim_1 ) {
			$c ++;
		}
		if ( $this->db_rim_2 ) {
			$c ++;
		}

		return $c;
	}

	/**
	 * @see: self::get_array_of_unique_db_products()
	 *
	 * Returns 0, 4, or 8 DB_Products in an array. Think of this
	 * as a full set of products for a vehicle. In all cases
	 * except the 0 products case, products will be repeated.
	 *
	 * Packages return 8 products. Tires/Rims only return 4. Invalid
	 * instances or ones not setup yet may return 0 but its not intended
	 * that this is useful with zero products.
	 *
	 * You can feed this into a function that adds up the prices of all
	 * products in an array for example.
	 */
	public function get_full_set_vehicle_products_in_an_array() {

		$this->setup_db_objects();

		$stg     = $this->is_staggered();
		$is_tire = $this->is_tire();
		$is_rim  = $this->is_rim();
		$is_pkg  = $this->is_pkg();

		$products = array();

		if ( $is_pkg ) {
			if ( $stg ) {
				$products[] = $this->db_tire_1;
				$products[] = $this->db_tire_1;
				$products[] = $this->db_tire_2;
				$products[] = $this->db_tire_2;
				$products[] = $this->db_rim_1;
				$products[] = $this->db_rim_1;
				$products[] = $this->db_rim_2;
				$products[] = $this->db_rim_2;
			} else {
				$products[] = $this->db_tire_1;
				$products[] = $this->db_tire_1;
				$products[] = $this->db_tire_1;
				$products[] = $this->db_tire_1;
				$products[] = $this->db_rim_1;
				$products[] = $this->db_rim_1;
				$products[] = $this->db_rim_1;
				$products[] = $this->db_rim_1;
			}
		} else if ( $is_tire ) {
			if ( $stg ) {
				$products[] = $this->db_tire_1;
				$products[] = $this->db_tire_1;
				$products[] = $this->db_tire_2;
				$products[] = $this->db_tire_2;
			} else {
				$products[] = $this->db_tire_1;
				$products[] = $this->db_tire_1;
				$products[] = $this->db_tire_1;
				$products[] = $this->db_tire_1;
			}
		} else if ( $is_rim ) {
			if ( $stg ) {
				$products[] = $this->db_rim_1;
				$products[] = $this->db_rim_1;
				$products[] = $this->db_rim_2;
				$products[] = $this->db_rim_2;
			} else {
				$products[] = $this->db_rim_1;
				$products[] = $this->db_rim_1;
				$products[] = $this->db_rim_1;
				$products[] = $this->db_rim_1;
			}
		}

		return $products;
	}

	/**
	 * @see: self::get_full_set_vehicle_products_in_an_array()
	 *
	 * returns UNIQUENESS in relation to LOCATION, not in relation to part numbers.
	 *
	 * returns 0 - 4 DB_Product's in an array which is fed into other functions.
	 */
	public function get_array_of_unique_db_products() {

		$this->setup_db_objects();

		$stg     = $this->is_staggered();
		$is_tire = $this->is_tire();
		$is_rim  = $this->is_rim();

		$products = array();

		if ( $is_tire ) {
			$products[] = $this->db_tire_1;
		}

		if ( $is_tire && $stg ) {
			$products[] = $this->db_tire_2;
		}

		if ( $is_rim ) {
			$products[] = $this->db_rim_1;
		}

		if ( $is_rim && $stg ) {
			$products[] = $this->db_rim_2;
		}

		return $products;
	}

	/**
	 * Pass in "front_tire" integer, get "rear_tire" integer.
	 *
	 * @param $int
	 *
	 * @return int|null
	 */
	public function reverse_item_location_int( $int ) {
		switch ( $int ) {
			case self::INT_TIRE_1:
				return self::INT_TIRE_2;
			case self::INT_TIRE_2:
				return self::INT_TIRE_1;
			case self::INT_RIM_1:
				return self::INT_RIM_2;
			case self::INT_RIM_2:
				return self::INT_RIM_1;
			default:
				return null;
		}
	}

	/**
	 * Pass in "front_tire" integer, get "front_rim" integer.
	 *
	 * @param $int
	 *
	 * @return int|null
	 */
	public function reverse_item_type_int( $int ) {
		switch ( $int ) {
			case self::INT_TIRE_1:
				return self::INT_RIM_1;
			case self::INT_TIRE_2:
				return self::INT_RIM_2;
			case self::INT_RIM_1:
				return self::INT_TIRE_1;
			case self::INT_RIM_2:
				return self::INT_RIM_1;
			default:
				return null;
		}
	}

    /**
     * Similar in idea to staggered_items_opposite_location_is_same_part_number but this function
     * can be used in different ways.
     *
     * Note: tires should never have the same part number for staggered fitments, but since
     * I don't control the vehicle data, I can't know for sure that it can't happen.
     *
     * @return bool
     */
	public function tires_use_same_part_number(){
        return $this->db_tire_1 && $this->db_tire_2 && $this->db_tire_1->get_primary_key_value() === $this->db_tire_2->get_primary_key_value();
    }

    /**
     * Rims in staggered fitments actually often do have the same part number even though
     * the tires are almost certainly different.
     *
     * @return bool
     */
	public function rims_use_same_part_number(){
	    return $this->db_rim_1 && $this->db_rim_2 && $this->db_rim_1->get_primary_key_value() === $this->db_rim_2->get_primary_key_value();
    }

	/**
	 * @param $int
	 *
	 * @return bool
	 */
	public function staggered_items_opposite_location_is_same_part_number( $int ){

		if ( ! $this->is_staggered() ) {
			return false;
		}

		$item = $this->get_product_from_int( $int, false );

		if ( ! $item ) {
			return false;
		}

		$item_2 = $this->get_product_from_int( $this->reverse_item_location_int( $int ) );

		if ( ! $item_2 ) {
			return false;
		}

		return $item->get_primary_key_value() === $item_2->get_primary_key_value();
	}

	/**
	 * @param $locale
	 *
	 * @return float|int
	 */
	public function get_total_price( $locale ) {
		$products = $this->get_full_set_vehicle_products_in_an_array();

		return $products ? get_aggregate_products_price( $products, $locale ) : 0;
	}

    /**
     * Return the stock amount for the lowest stock product among
     * the set of products. This can return STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING
     * or an integer.
     *
     * @return string
     */
	public function get_minimum_stock_amount(){

	    $min = STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING;

        /** @var DB_Product $product */
	    foreach ( $this->get_db_products() as $product ) {

	        $amt = $product->get_computed_stock_amount( app_get_locale() );

	        if ( $amt !== STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING ) {

	            if ( $min === STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING ) {
	                $min = $amt;
                } else if ( $amt < $min ) {
	                $min = $amt;
                }
            }
        }

	    return $min;
    }

	/**
	 * Ie. should we show the add to cart button?
	 *
	 * If you are going to show the add to cart button on the merit of the return value
	 * of this function, then you *may* want to also use $this->get_item_atc_adjusted_qty().
	 *
	 * To be more specific, I think you'll want to use that function only for non staggered
	 * and non packaged items set (ie. item sets with just one item).
	 */
	public function item_set_is_purchasable(){

		// For singular items
		if ( ! $this->is_staggered() && ! $this->is_pkg() ) {

			// gets the singular item
			$int = $this->items_integers_array( false )[0];
			$stock = $this->get_product_from_int( $int )->get_computed_stock_amount( $this->locale );

			if ( $stock === STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING ) {
				return true;
			}

			if ( $stock < 1 ) {
				return false;
			}

			return true;

		} else {

			foreach ( $this->items_integers_array( false ) as $int ) {

				$stock = $this->get_product_from_int( $int )->get_computed_stock_amount( $this->locale );
				$req = $this->get_item_atc_required_qty( $int );

				if ( $stock === STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING ) {
					continue;
				}

				if ( $stock < $req ) {
					return false;
				}
			}

			return true;
		}
	}

    /**
     * Returns the max of: the items stock level, the items desired add to cart quantity.
     *
     * Here are 2 scenarios where you might not end up using this function:
     *
     * 1. You don't show an add to cart button at all (so you don't need to use this function)
     * 2. You want to add the desired quantity to the cart anyways, even if it will result in not enough stock.
     *
     * The plan as of now: only singular items (not staggered, not packaged) can be added to the cart
     * with less than a full set. In those cases, we'll end up using this function.
     *
     * @param $int
     * @return int|mixed
     */
	public function get_item_atc_adjusted_qty( $int ) {

		$desired = $this->get_item_atc_desired_qty( $int );
		$stock = $this->get_product_from_int( $int )->get_computed_stock_amount( $this->locale );

		if ( $stock === STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING ) {
			return $desired;
		}

		$_stock = $stock > 0 ? $stock : 0;

		// generally, 0, 1, 2, 3, or 4.
		return min( $_stock, $desired );
	}

    /**
     * How many of the given item would we want to add to the cart within
     * the context of the full set of items.
     *
     * For example, the full set can be staggered, packaged, or neither.
     *
     * Basically, for staggered items, add 2. Otherwise, 4.
     *
     * Remember that if we have the same front/rear part numbers, add to
     * cart handlers do not condense this into one item with quantity 4. The
     * "items" in the handler still have one item per location, and each item
     * would have a qty of 2.
     *
     * @param $int
     * @return int
     * @see Cart_Item::$quantity
     * @see Cart_Item::$loc
     *
     */
	public function get_item_atc_desired_qty( $int ) {

		if ( $this->is_staggered() ) {
			return 2;
		}

		return 4;
	}

    /**
     * This is similar to but certainly not the same as the desired quantity.
     *
     * Think of this as: Get the items part number. Find items with the same
     * part number from the set of items. Sum up all quantities from those items
     * with the same part number.
     *
     * So if you provide a "front tire", this asks the questions, what is the stock
     * level of that front tire that would be required to to add the FULL SET of items
     * to the cart. Where the full set of items may also contain: rear tires, front rims,
     * or rear tires and front/rear rims.
     *
     * @param $int
     * @return int
     */
	public function get_item_atc_required_qty( $int ) {

		if ( $this->is_staggered() ) {
			if ( $this->staggered_items_opposite_location_is_same_part_number( $int ) ) {
				return 4;
			} else {
				return 2;
			}
		}

		return 4;

	}

	/**
	 * @param $int
	 *
	 * @return string
	 */
	public function get_item_stock_amount_html( $int ){

		$product = $this->get_product_from_int( $int, false );
		$qty = $product->get_computed_stock_amount( $this->locale );

		$product_type = $product->is_tire() ? 'tires' : 'rims';

		// use this when we determine whether or not to disable adding to cart,
		// but for now, we don't need this here.
		// $min_qty_to_purchase = $this->get_item_min_qty_to_purchase( $int );

		// do we say, 2 sets remaining, or 9 tires remaining?
		// use sets for non staggered only.
		$use_sets = ! $this->is_staggered();

		// enough stock for 1 set? If not, we may show text in orange.
		$semi_out_of_stock_max_not_inclusive = $this->is_staggered() ? 2 : 4;

		// if 4 full sets are available, we'll only show "in stock"
		// $in_stock_min = $is_stg ? ( $this->staggered_items_opposite_location_is_same_part_number( $int ) ? 16 : 8 ) : 16;

		// lets just keep it simple..
		$in_stock_min_inclusive = 16;

		if ( $qty === STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING || ( $qty >= $in_stock_min_inclusive )) {
			$indicator = STOCK_LEVEL_IN_STOCK;
		} else if ( $qty < 1 ) {
			$indicator = STOCK_LEVEL_NO_STOCK;
		} else {
			if ( $qty < $semi_out_of_stock_max_not_inclusive ) {
				$indicator = STOCK_LEVEL_SEMI_OUT_OF_STOCK;
			} else {
				$indicator = STOCK_LEVEL_LOW_STOCK;
			}
		}

		$ret = Stock_Level_Html::render( $indicator, $qty, $product_type, $use_sets );
		return $ret;
	}

	/**
	 * You're not going to want to use this on singular item sets.
	 *
	 * As of now, this returns a summary stock level. For example, it will only
	 * say Out of Stock, Low Stock, In Stock. In the future, there is no reason
	 * why we couldn't say 1/2/3 sets remaining. Doing so is quite easy for singular
	 * items, but for staggered packages would require a bit of math to determine
	 * the aggregate "sets remaining". Ie. if all but one item had infinite stock,
	 * the aggregate sets remaining would be that items quantity divided by its total stock.
	 *
	 * This currently only shows on the product cards on the archive packages page.
	 *
	 * @see Page_Packages
	 *
	 * @return string
	 */
	public function get_item_set_stock_amount_html(){

		$indicator = $this->get_item_set_stock_level_indicator();

		switch( $indicator ) {
			case STOCK_LEVEL_NO_STOCK:
				$text = 'Out of stock';
				break;
			case STOCK_LEVEL_LOW_STOCK:
				$text = 'Low stock';
				break;
			case STOCK_LEVEL_IN_STOCK:
				$text = 'In stock';
				break;
			default:
				$text = '';
		}

		$cls = Stock_Level_Html::css_class( $indicator );
		$icon = Stock_Level_Html::icon( $indicator );

		return Stock_Level_Html::render_via_html_components( $cls, $icon, $text );
	}

	/**
	 * Returns: STOCK_LEVEL_IN_STOCK, STOCK_LEVEL_NO_STOCK, STOCK_LEVEL_LOW_STOCK,
	 *
	 * For now, not considering STOCK_LEVEL_SEMI_OUT_OF_STOCK.
	 *
	 * @return string
	 */
	public function get_item_set_stock_level_indicator(){

		if ( ! $this->item_set_is_purchasable() ) {
			return STOCK_LEVEL_NO_STOCK;
		} else{

			$one_is_low_stock = false;

			foreach ( $this->items_integers_array( false ) as $int ) {

				$qty = $this->get_product_from_int( $int )->get_computed_stock_amount( $this->locale );

				if ( $qty === STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING ) {
					continue;
				}

				if ( $qty < 16 ) {
					$one_is_low_stock = true;
				}
			}

			if ( $one_is_low_stock ) {
				return STOCK_LEVEL_LOW_STOCK;
			}

			return STOCK_LEVEL_IN_STOCK;
		}
	}

}
