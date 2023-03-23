<?php

/**
 * Intermediary class to hold some similar methods for both rims and tires
 * Class Product
 */
Class DB_Product extends DB_Table {

	/** @var  DB_Tire_Brand|DB_Rim_Brand */
	public $brand;

	/** @var  DB_Tire_Model|DB_Rim_Model */
	public $model;

	/**
	 * Product constructor.
	 *
	 * @param       $data
	 * @param array $options
	 */
	public function __construct( $data, $options = array() ) {
		parent::__construct( $data, $options );
	}

	/**
	 * @param $locale
	 *
	 * @return mixed
	 */
	public static function get_column_stock_amt( $locale ) {
		return self::switch_column_via_locale( 'stock_amt_ca', 'stock_amt_us', $locale );
	}

	/**
	 * @param $locale
	 *
	 * @return mixed
	 */
	public static function get_column_stock_sold( $locale ) {
		return self::switch_column_via_locale( 'stock_sold_ca', 'stock_sold_us', $locale );
	}

	/**
	 * @param $locale
	 *
	 * @return mixed
	 */
	public static function get_column_stock_unlimited( $locale ) {
		return self::switch_column_via_locale( 'stock_unlimited_ca', 'stock_unlimited_us', $locale );
	}

	/**
	 * @param $locale
	 *
	 * @return mixed
	 */
	public static function get_column_stock_update_id( $locale ) {
		return self::switch_column_via_locale( 'stock_update_id_ca', 'stock_update_id_us', $locale );
	}

	/**
	 * @param $locale
	 *
	 * @return mixed
	 */
	public static function get_column_msrp( $locale ) {
		return self::switch_column_via_locale( 'msrp_ca', 'msrp_us', $locale );
	}

	/**
	 * @param $locale
	 *
	 * @return mixed
	 */
	public static function get_column_cost( $locale ) {
		return self::switch_column_via_locale( 'cost_ca', 'cost_us', $locale );
	}

	/**
	 * @param $locale
	 *
	 * @return mixed
	 */
	public static function get_column_price( $locale ) {
		return self::switch_column_via_locale( 'price_ca', 'price_us', $locale );
	}

	/**
	 * @param $locale
	 *
	 * @return mixed
	 */
	public static function get_column_sold_in( $locale ) {
		return self::switch_column_via_locale( 'sold_in_ca', 'sold_in_us', $locale );
	}

	/**
	 * @param $locale
	 *
	 * @return mixed
	 */
	public static function get_column_stock_discontinued( $locale ) {
		return self::switch_column_via_locale( 'stock_discontinued_ca', 'stock_discontinued_us', $locale );
	}

	/**
	 * Possibly just using to print an sql string and run it manually,
	 * not recommending that you ever execute this in php.
	 */
	public static function us_inv_update_sql() {

		$q = '';
		$q .= 'ALTER TABLE `' . static::$table . '` ';
		$q .= 'CHANGE `msrp` `msrp_ca` ' . static::$db_init_cols[ 'msrp_ca' ] . ' ,';
		$q .= 'CHANGE `cost` `cost_ca` ' . static::$db_init_cols[ 'cost_ca' ] . ' ,';
		$q .= 'CHANGE `stock` `stock_amt_ca` ' . static::$db_init_cols[ 'stock_amt_ca' ] . ' ,';
		$q .= 'CHANGE `stock_sold` `stock_sold_ca` ' . static::$db_init_cols[ 'stock_sold_ca' ] . ' ,';
		$q .= 'CHANGE `stock_unlimited` `stock_unlimited_ca` ' . static::$db_init_cols[ 'stock_unlimited_ca' ] . ' ,';
		$q .= 'CHANGE `stock_update_id` `stock_update_id_ca` ' . static::$db_init_cols[ 'stock_update_id_ca' ] . ' ';
		$q .= ';';

		echo nl2br( "-----------------------  \n" );
		echo $q;

		$q2 = 'ALTER TABLE `' . static::$table . '` ';

		$q2 .= 'ADD `msrp_us` ' . static::$db_init_cols[ 'msrp_us' ] . ', ';
		$q2 .= 'ADD `cost_us` ' . static::$db_init_cols[ 'cost_us' ] . ', ';
		$q2 .= 'ADD `stock_amt_us` ' . static::$db_init_cols[ 'stock_amt_us' ] . ', ';
		$q2 .= 'ADD `stock_sold_us` ' . static::$db_init_cols[ 'stock_sold_us' ] . ', ';
		$q2 .= 'ADD `stock_unlimited_us` ' . static::$db_init_cols[ 'stock_unlimited_us' ] . ', ';
		$q2 .= 'ADD `stock_update_id_us` ' . static::$db_init_cols[ 'stock_update_id_us' ] . ' ';

		$q2 .= 'ADD `stock_discontinued_us` ' . static::$db_init_cols[ 'stock_discontinued_us' ] . ' ';
		$q2 .= 'ADD `stock_discontinued_ca` ' . static::$db_init_cols[ 'stock_discontinued_ca' ] . ' ';

		$q2 .= ';';

		echo nl2br( "-----------------------  \n" );
		echo $q2;

//		$q3 = '';
//		$q3 = 'ALTER TABLE `' . static::$table . '` ';
//		$q3 .= 'ADD ' . static::$db_init_args['CHK_STOCK_CA'] . ', ';
//		$q3 .= 'ADD ' . static::$db_init_args['CHK_STOCK_US'];
//		$q3 .= ';';

		echo nl2br( "-----------------------  \n" );
		// echo $q3;

		/**
		 * WARNING MISSING COMMAS IN SOME OF THESE USE WITH CAUTION OR NOT AT ALL.
		 *
		 * ALTER TABLE `rims` CHANGE `msrp` `msrp_ca` varchar(255) default '' ,CHANGE `cost` `cost_ca` varchar(255) default '' ,CHANGE `stock` `stock_amt_ca` int(11) NOT NULL default 0 ,CHANGE `stock_sold` `stock_sold_ca` int(11) NOT NULL default 0 ,CHANGE `stock_unlimited` `stock_unlimited_ca` bool DEFAULT 1 ,CHANGE `stock_update_id` `stock_update_id_ca` int(11) default NULL ;
		 *
		 * ALTER TABLE `rims` ADD `msrp_us` varchar(255) default '', ADD `cost_us` varchar(255) default '', ADD `stock_amt_us` int(11) NOT NULL default 0, ADD `stock_sold_us` int(11) NOT NULL default 0, ADD `stock_unlimited_us` bool DEFAULT 1, ADD `stock_update_id_us` int(11) default NULL ADD `stock_discontinued_us` bool DEFAULT 0 ADD `stock_discontinued_ca` bool DEFAULT 0 ;
		 *
		 * ALTER TABLE `tires` CHANGE `msrp` `msrp_ca` varchar(255) default '' ,CHANGE `cost` `cost_ca` varchar(255) default '' ,CHANGE `stock` `stock_amt_ca` int(11) NOT NULL default 0 ,CHANGE `stock_sold` `stock_sold_ca` int(11) NOT NULL default 0 ,CHANGE `stock_unlimited` `stock_unlimited_ca` bool DEFAULT 1 ,CHANGE `stock_update_id` `stock_update_id_ca` int(11) default NULL ;
		 *
		 * ALTER TABLE `tires` ADD `msrp_us` varchar(255) default '', ADD `cost_us` varchar(255) default '', ADD `stock_amt_us` int(11) NOT NULL default 0, ADD `stock_sold_us` int(11) NOT NULL default 0, ADD `stock_unlimited_us` bool DEFAULT 1, ADD `stock_update_id_us` int(11) default NULL ADD `stock_discontinued_us` bool DEFAULT 0 ADD `stock_discontinued_ca` bool DEFAULT 0 ;*
		 *
		 */

		exit;
	}

	/**
	 * @return bool
	 */
	public function is_rim() {
		$ret = $this instanceof DB_Rim;

		return $ret;
	}

	/**
	 * @return bool
	 */
	public function is_tire() {
		$ret = $this instanceof DB_Tire;

		return $ret;
	}

	/**
	 *
	 */
	public function get_price_cents() {
		$dollars = method_exists( $this, 'get_price_dollars_raw' ) ? $this->get_price_dollars_raw() : 0;
		$ret     = dollars_to_cents( $dollars );

		return $ret;
	}

	/**
	 * Asserts that the product is sold in the given locale based on 2 conditions:
	 *
	 * 1. sold_in_ca references when the product was imported and whether it ever intended to be sold in canada.
	 *
	 * 2. stock_discontinued_ca *may* occur if a supplier inventory import did not specify the product AND
	 * we decided to mark all products with undefined stock levels as discontinued rather than one of many
	 * other possible options: unlimited stock, shows up on the site but with zero stock, deleted form the
	 * database entirely (this one is a bad option).
	 *
	 * Example return value: "( sold_in_ca = 1 AND stock_discontinued_ca = 0 )"
	 *
	 * Of course replace _ca with _us if $locale === APP_LOCALE_US
	 *
	 * It is ****IMPORTANT**** that the function mentioned below implements the same logic but in PHP.
	 *
	 * @see DB_Product::sold_and_not_discontinued_in_locale()
	 *
	 * @param string $table
	 */
	public static function sql_assert_sold_and_not_discontinued_in_locale( $table = '', $locale = null ) {
		$locale = app_get_locale_from_locale_or_null( $locale );
		$s1 = gp_sql_get_selector( $table, DB_Product::get_column_sold_in( $locale ) );
		$s2 = gp_sql_get_selector( $table, DB_Product::get_column_stock_discontinued( $locale ) );

		// ie. "( rims.sold_in_ca = 1 AND rims.stock_discontinued_ca = 0 )"
		return "( $s1 = 1 AND $s2 = 0 )";
	}

	/**
	 * @see DB_Product::sql_assert_sold_and_not_discontinued_in_locale()
	 */
	public function sold_and_not_discontinued_in_locale( $locale = null ) {
		$locale = app_get_locale_from_locale_or_null( $locale, true );
		$col_1 = DB_Product::get_column_sold_in( $locale );
		$col_2 = DB_Product::get_column_stock_discontinued( $locale );
		$require_1 = $this->get( $col_1 ) == 1;
		$require_2 = $this->get( $col_2 ) == 0;
		$ret = $require_1 && $require_2;
		return $ret;
	}

	/**
	 * I think we really just need the third parameter $allow_php_cache for the cart page where we have
	 * to get the same object from the database quite a few times in unrelated functions.
	 *
	 * @param       $part_number
	 * @param array $options
	 * @param bool  $allow_php_cache
	 *
	 * @return bool|null|string|static
	 */
	public static function create_instance_via_part_number( $part_number, $options = array(), $allow_php_cache = false ) {

		if ( $part_number instanceof static ) {
			return $part_number;
		}

		$part_number = gp_force_singular( $part_number );

		if ( ! $part_number ) {
			return null;
		}

		$cache_key = static::$table . '_part_number_' . $part_number;

		if ( $allow_php_cache ) {
			if ( PHP_Object_Cache::exists( $cache_key ) ) {
				$ret = PHP_Object_Cache::get( $cache_key );
				if ( $ret instanceof static ) {
					Debug::add( $cache_key, 'cached db model' );

					return $ret;
				}
			}
		}

		$db = get_database_instance();
		// static not self
		$result = $db->get( static::$table, array( 'part_number' => $part_number ) );
		$row    = gp_if_set( $result, 0 );

		if ( ! $row ) {
			return null;
		}

		$options = $options ? $options : array();
		$ret     = static::create_instance_or_null( $row, $options );

		if ( $allow_php_cache ) {
			PHP_Object_Cache::set( $cache_key, $ret );
		}

		return $ret;
	}

	/**
	 * get_price_column existed first ... but then we added a bunch
	 * of other functions like get_column_*( $locale ), so this is now
	 * just here to confuse you. Note that $locale must be valid,
	 * if not passed in, eventually an exception will be thrown...
	 *
	 * @param null $locale
	 */
	public static function get_price_column( $locale ) {
		return self::get_column_price( $locale );
	}

	/**
	 * What stock level should we send to amazon?
	 *
	 * For example, we may submit 4 less than what we have in stock
	 * in case orders are submitted via the website and amazon at the same time.
	 *
	 * We may put a cap.
	 *
	 * Stock level undefined or unlimited will likely also be treated differently
	 * on amazon.
	 *
	 * We *might* return null here to indicate "do nothing to amazon stock level",
	 * but I also don't know if we will need this for any reason.
	 *
	 * Update: if stock is discontinued then we certainly don't want to give any to amazon.
	 * However, when we mark stock discontinued we also set the stock to zero... Still...
	 * I think we'll do a "not discontinued" check here.
	 *
	 * @return int
	 */
	public function get_amazon_stock_amount( $locale ) {

		assert( app_is_locale_valid( $locale ) );

		// cap is probably not needed but just in case..
		// in reality most suppliers wont put more than 50
		// if anything it would be 50+, so 99 is a cap that
		// is unlikely to have any effect.
		// ** possibly not the real cap, see below **
		$cap = 99;

		// unsure if we should be adding this...
		if ( strpos( $this->get( 'part_number' ), 'WFL ' ) === 0 ) {
			return null;
		}

		// do not send in stock levels for wheels for less supplier
		if ( $this->get( 'supplier' ) === 'wheels-for-less' ) {
			// null means do not send any inventory amount. inventory is managed
			// manually for these via amazon.
			return null;
		}

		// see the comment on SEND_ZERO_INVENTORY_TO_AWS_FOR_TWG.
		if ( SEND_ZERO_INVENTORY_TO_AWS_FOR_TWG && $this->get( 'supplier' ) === 'wheel-1' ) {
			// return 0 for no inventory, not null.
			return 0;
		}

		// If discontinued, return 0, even though if our app is working properly, the computed stock amount will also be zero.
		if ( (int) $this->get( $this::get_column_stock_discontinued( $locale ) ) === 1 ) {
			return 0;
		}

		$stock = $this->get_computed_stock_amount( $locale );

		// unlimited is an alias for undefined which on the website, may mean unlimited stock, but
		// for amazon, it means don't sell.
		if ( $stock === STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING ) {
			return 0;
		}

		// dont sell anything on amazon if we don't have a certain min available.
		if ( $stock < 12 ) {
			return 0;
		}

		// reserve a certain amount for our own database.
		// if we have 20 in stock, only allow 12 to be purchased from amazon,
		// although its still possible that 20 be purchased on the website and 12
		// on amazon at the same time, but unlikely.
		$padding = 8;
		$stock   = $stock - $padding;

		// ensure between 0 and cap
		$stock = $stock > 0 ? $stock : 0;
		$stock = $stock < $cap ? $stock : $cap;

		return (int) $stock;
	}

	/**
	 * @param $locale
	 */
	public function get_stock_amt( $locale ) {
		return (int) $this->get( self::get_column_stock_amt( $locale ) );
	}

	/**
	 * @param $locale
	 */
	public function get_stock_sold( $locale ) {
		return (int) $this->get( self::get_column_stock_sold( $locale ) );
	}

	/**
	 * @param $locale
	 */
	public function get_stock_unlimited( $locale ) {
		return (bool) $this->get( self::get_column_stock_unlimited( $locale ) );
	}

	/**
	 * @param $locale
	 */
	public function get_stock_update_id( $locale ) {
		return (int) $this->get( self::get_column_stock_update_id( $locale ) );
	}

	/**
	 * When stock is unlimited, returns a pre-defined non-empty string to indicate this fact (@see
	 * STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING)
	 *
	 * When stock is not unlimited, return stock - stock_sold, which must be an integer, and can be less than zero.
	 *
	 * This means we can pass around one variable to other functions that encapsulates a little more than just a number
	 * could.
	 *
	 * Note that using -1 to mean unlimited is 100% wrong, because stock - stock_sold can in fact be - 1.
	 */
	public function get_computed_stock_amount( $locale ) {

		assert( app_is_locale_valid( $locale ) );

		if ( $this->get_stock_unlimited( $locale ) ) {
			return STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING;
		}

		$stock      = $this->get_stock_amt( $locale );
		$stock_sold = $this->get_stock_sold( $locale );
		$ret        = $stock - $stock_sold;

		return $ret;
	}
}
