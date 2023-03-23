<?php

/**
 * Class Supplier_Inventory_Overview
 */
Class Supplier_Inventory_Overview{

	public $locale;
	public $type;
	public $supplier;

	public $count_tires_ca;
	public $count_rims_ca;
	public $count_tires_us;
	public $count_rims_us;

	public $inventory_tires_ca;
	public $inventory_rims_ca;
	public $inventory_tires_us;
	public $inventory_rims_us;

	public $count_discontinued_tires_ca;
	public $count_discontinued_rims_ca;
	public $count_discontinued_tires_us;
	public $count_discontinued_rims_us;

	public $count_unlimited_tires_ca;
	public $count_unlimited_rims_ca;
	public $count_unlimited_tires_us;
	public $count_unlimited_rims_us;

	public $count_in_stock_tires_ca;
	public $count_in_stock_rims_ca;
	public $count_in_stock_tires_us;
	public $count_in_stock_rims_us;

	public $count_no_stock_tires_ca;
	public $count_no_stock_rims_ca;
	public $count_no_stock_tires_us;
	public $count_no_stock_rims_us;

	/**
	 * Product_Inventory_Overview constructor.
	 *
	 * NOTE: don't forget to call ->calculate(), which runs a lot of queries.
	 *
	 * @param $supplier
	 */
	public function __construct( $supplier ) {
		$this->supplier = $supplier;
		assert( strlen( $supplier ) > 0 );
	}

	/**
	 * runs a ton of queries...
	 */
	public function calculate(){

		// make things easier to read
		$sup = $this->supplier;
		$CA = APP_LOCALE_CANADA;
		$US = APP_LOCALE_US;

		$this->count_tires_ca = self::count_products( true, $CA, $sup, null, null, null );
		$this->count_rims_ca = self::count_products( false, $CA, $sup, null, null, null );
		$this->count_tires_us = self::count_products( true, $US, $sup, null, null, null );
		$this->count_rims_us = self::count_products( false, $US, $sup, null, null, null );
		$this->inventory_tires_ca = self::get_supplier_inventory_instances( true, $CA, $sup );
		$this->inventory_rims_ca = self::get_supplier_inventory_instances( false, $CA, $sup );
		$this->inventory_tires_us = self::get_supplier_inventory_instances( true, $US, $sup );
		$this->inventory_rims_us = self::get_supplier_inventory_instances( false, $US, $sup );
		$this->count_discontinued_tires_ca = self::count_products( true, $CA, $sup, 1, null, null );
		$this->count_discontinued_rims_ca = self::count_products( false, $CA, $sup, 1, null, null );
		$this->count_discontinued_tires_us = self::count_products( true, $US, $sup, 1, null, null );
		$this->count_discontinued_rims_us = self::count_products( false, $US, $sup, 1, null, null );
		$this->count_unlimited_tires_ca = self::count_products( true, $CA, $sup, null, 1, null );
		$this->count_unlimited_rims_ca = self::count_products( false, $CA, $sup, null, 1, null );
		$this->count_unlimited_tires_us = self::count_products( true, $US, $sup, null, 1, null );
		$this->count_unlimited_rims_us = self::count_products( false, $US, $sup, null, 1, null );
		$this->count_in_stock_tires_ca = self::count_products( true, $CA, $sup, 0, 0, 1 );
		$this->count_in_stock_rims_ca = self::count_products( false, $CA, $sup, 0, 0, 1 );
		$this->count_in_stock_tires_us = self::count_products( true, $US, $sup, 0, 0, 1 );
		$this->count_in_stock_rims_us = self::count_products( false, $US, $sup, 0, 0, 1 );
		$this->count_no_stock_tires_ca = self::count_products( true, $CA, $sup, 0, 0, 0 );
		$this->count_no_stock_rims_ca = self::count_products( false, $CA, $sup, 0, 0, 0 );
		$this->count_no_stock_tires_us = self::count_products( true, $US, $sup, 0, 0, 0 );
		$this->count_no_stock_rims_us = self::count_products( false, $US, $sup, 0, 0, 0 );
	}

	/**
	 * Do calculate() first
	 */
	public function get_table_row(){

		$ret = array();

		// make things easier to read
		$sup = $this->supplier;
		$CA = APP_LOCALE_CANADA;
		$US = APP_LOCALE_US;

		$ret['supplier'] = $this->supplier;

		$ret['tires_ca'] = self::link_to_supplier( $this->count_tires_ca, true, $CA, $sup );
		$ret['rims_ca'] = self::link_to_supplier( $this->count_rims_ca, false, $CA, $sup );
		$ret['tires_us'] = self::link_to_supplier( $this->count_tires_us, true, $US, $sup );
		$ret['rims_us'] = self::link_to_supplier( $this->count_rims_us, false, $US, $sup );

		$ret['inventory_tires_ca'] = self::supplier_instances_to_string( $this->inventory_tires_ca );
		$ret['inventory_rims_ca'] = self::supplier_instances_to_string( $this->inventory_rims_ca );
		$ret['inventory_tires_us'] = self::supplier_instances_to_string( $this->inventory_tires_us );
		$ret['inventory_rims_us'] = self::supplier_instances_to_string( $this->inventory_rims_us );

		$ret['not_accounted_for'] = self::issues_array_to_string( self::get_not_accounted_for_issues() );
		$ret['no_effect'] = self::issues_array_to_string( self::get_no_effect_issues() );

		// makes table more readable...
		$_supplier = '(' . $this->supplier . ')';

		$ret['Tires (CA)'] = $_supplier;
		$ret['in_stock_tires_ca'] = $this->count_in_stock_tires_ca;
		$ret['unlimited_tires_ca'] = $this->count_unlimited_tires_ca;
		$ret['no_stock_tires_ca'] = $this->count_no_stock_tires_ca;
		$ret['discontinued_tires_ca'] = $this->count_discontinued_tires_ca;

		$ret['Rims (CA)'] = $_supplier;
		$ret['in_stock_rims_ca'] = $this->count_in_stock_rims_ca;
		$ret['unlimited_rims_ca'] = $this->count_unlimited_rims_ca;
		$ret['no_stock_rims_ca'] = $this->count_no_stock_rims_ca;
		$ret['discontinued_rims_ca'] = $this->count_discontinued_rims_ca;

		$ret['Tires (US)'] = $_supplier;
		$ret['in_stock_tires_us'] = $this->count_in_stock_tires_us;
		$ret['unlimited_tires_us'] = $this->count_unlimited_tires_us;
		$ret['no_stock_tires_us'] = $this->count_no_stock_tires_us;
		$ret['discontinued_tires_us'] = $this->count_discontinued_tires_us;

		$ret['Rims (US)'] = $_supplier;
		$ret['in_stock_rims_us'] = $this->count_in_stock_rims_us;
		$ret['unlimited_rims_us'] = $this->count_unlimited_rims_us;
		$ret['no_stock_rims_us'] = $this->count_no_stock_rims_us;
		$ret['discontinued_rims_us'] = $this->count_discontinued_rims_us;

		return $ret;
	}

	/**
	 * Sets up one instance of self for every row in the suppliers
	 * table in the database, and calculates all values.
	 *
	 * Runs a TON of queries...
	 */
	public static function get_and_calculate_all_instances(){

		$suppliers = self::get_all_db_suppliers();
		$ret = array();

		if ( $suppliers ) {
			foreach ( $suppliers as $supplier ) {

				// don't want to fail silently here.
				assert( isset( $supplier->supplier_slug ) );
				$self = new self( $supplier->supplier_slug );
				$self->calculate();
				$ret[] = $self;
			}
		}

		return $ret;
	}

    /**
     * Ie. pass self::get_and_calculate_all_instances() into this,
     * or use the function yourself along with $this->get_table_row()
     * and do your own thing with the data.
     *
     * @param $instances
     * @return string
     */
	public static function render_table_from_instances( $instances ) {

		$rows = array();

		if ( $instances && is_array( $instances ) ) {
			/** @var Supplier_Inventory_Overview $self */
			foreach ( $instances as $self ) {
				$rows[] = $self->get_table_row();
			}
		}

		$ret = '';
		$ret .= render_html_table_admin( false, $rows );

//		$ret .= '<h3>The same data in a different format:</h3>';
//		if ( $rows ) {
//			foreach ( $rows as $row ) {
//				$ret .= '<div>';
//				if ( $row ) {
//					foreach ( $row as $k=>$v ) {
//						if ( $k === 'supplier' ) {
//							$ret .= '<h2>';
//							$ret .= $v;
//							$ret .= '</h2>';
//						} else{
//							$ret .= '<p>';
//							$ret .= '<strong>' . $k . '</strong>: ' . $v;
//							$ret .= '</p>';
//						}
//					}
//				}
//				$ret .= '</div>';
//			}
//		}

		return $ret;
	}


	/**
	 * Gets all rows from the suppliers table. Note that a "database" supplier
	 * is quite different than a "Supplier_Inventory_Supplier".
	 */
	public static function get_all_db_suppliers(){
		$db = get_database_instance();
		$ret = $db->get_results( 'SELECT * FROM suppliers ORDER BY supplier_id ASC' );
		return $ret;
	}

	/**
	 * @param $arr
	 *
	 * @return string
	 */
	public static function issues_array_to_string( $arr ) {
		if ( $arr && is_array( $arr ) ) {
			return implode( ' ', $arr );
		}
		return '';
	}

	/**
	 * Determine if tires or rims are not accounted for via a registered inventory supplier process.
	 */
	public function get_not_accounted_for_issues(){

		$ret = array();

		if ( $this->count_tires_ca > 0 && ! $this->inventory_tires_ca ) {
			$ret[] = 'CA tires not accounted for.';
		}

		if ( $this->count_tires_us > 0 && ! $this->inventory_tires_us ) {
			$ret[] = 'US tires not accounted for.';
		}

		if ( $this->count_rims_ca > 0 && ! $this->inventory_rims_ca ) {
			$ret[] = 'CA rims not accounted for.';
		}

		if ( $this->count_rims_us > 0 && ! $this->inventory_rims_us ) {
			$ret[] = 'US rims not accounted for.';
		}

		return $ret;
	}

	/**
	 * Find inventory processes that are not effecting any products.
	 *
	 * Note that this does not indicate an error since often we'll setup the inventory
	 * before the products are actually uploaded to the site.
	 */
	public function get_no_effect_issues(){

		$ret = array();

		if ( $this->inventory_tires_ca && $this->count_tires_ca < 1 ) {
			$ret[] = 'CA tire inventory has no effect.';
		}

		if ( $this->inventory_tires_us && $this->count_tires_us < 1 ) {
			$ret[] = 'US tire inventory has no effect.';
		}

		if ( $this->inventory_rims_ca && $this->count_rims_ca < 1 ) {
			$ret[] = 'CA rim inventory has no effect.';
		}

		if ( $this->inventory_rims_us && $this->count_rims_us < 1 ) {
			$ret[] = 'US rim inventory has no effect.';
		}

		return $ret;
	}

    /**
     * @param $arr
     * @return string|null
     */
	public static function supplier_instances_to_string( $instances ) {

		$names = is_array( $instances ) ? array_map( function( $instance ){
			/** @var Supplier_Inventory_Supplier $instance  */
			return $instance->get_admin_name();
		}, $instances ) : false;

		return gp_safe_implode( ', ', $names );
	}

    /**
     * @param $is_tire
     * @param $locale
     * @param $supplier
     * @return array|bool
     */
	public static function get_supplier_inventory_instances( $is_tire, $locale, $supplier ){
		assert( app_is_locale_valid( $locale ) );
		assert( strlen( $supplier ) > 0  );
		$type = $is_tire ? 'tires' : 'rims';
		$ret = Supplier_Inventory_Supplier::get_instances_with_filters( $locale, $type, $supplier );
		// ensure false
		$ret = $ret && is_array( $ret ) ? $ret : false;
		return $ret;
	}


	/**
	 * NOTE: parameters are mutually exclusive in the queries below.
	 *
	 * If you want to get in stock tires, you should also set unlimited to zero, so that
	 * when you split up tires into categories, the sum of all categories is the sum of all tires..
	 *
	 * @param      $is_tire
	 * @param      $locale
	 * @param      $supplier
	 * @param null $discontinued
	 * @param null $unlimited
	 * @param null $in_stock
	 *
	 * @return int
	 */
	public static function count_products( $is_tire, $locale, $supplier, $discontinued = null, $unlimited = null, $in_stock = null ){

		assert( app_is_locale_valid( $locale ) );
		assert( strlen( $supplier ) > 0  );

		$pk = $is_tire ? 'tire_id' : 'rim_id';
		$tbl = $is_tire ? DB_tires : DB_rims;

		$col_sold_in = gp_esc_db_col( DB_Product::get_column_sold_in( $locale ) );
		$col_discontinued = gp_esc_db_col( DB_Product::get_column_stock_discontinued( $locale ) );
		$col_unlimited = gp_esc_db_col( DB_Product::get_column_stock_unlimited( $locale ) );
		$col_amt = gp_esc_db_col( DB_Product::get_column_stock_amt( $locale ) );

		$db = get_database_instance();
		$q = '';
		$p = array();

		$q .= 'SELECT ' . gp_esc_db_col( $pk ) . ' ';

		// ties/rims
		$q .= 'FROM ' . gp_esc_db_table( $tbl ) . ' ';

		$q .= 'WHERE 1 = 1 ';

		// supplier
		$q .= 'AND supplier = :supplier ';
		$p[] = [ 'supplier', $supplier, '%s' ];

		// locale (sold in)
		$q .= "AND $col_sold_in = 1 ";

		// discontinued - true/false or no condition.
		if ( $discontinued !== null ) {
			if ( $discontinued ) {
				$q .= "AND $col_discontinued = 1 ";
			} else {
				$q .= "AND $col_discontinued = 0 ";
			}
		}

		// stock unlimited
		if ( $unlimited !== null ) {
			if ( $unlimited ) {
				$q .= "AND $col_unlimited = 1 ";
			} else {
				$q .= "AND $col_unlimited = 0 ";
			}
		}

		// in stock (not taking into account items sold, we're interested in the value updated from an inventory update)
		if ( $in_stock !== null ) {
			if ( $in_stock ) {
				$q .= "AND $col_amt > 0 ";
			} else {
				$q .= "AND $col_amt < 1 ";
			}
		}

		$q .= ';';

		return $db->count_results( $q, $p );
	}

    /**
     * Unfortunately, URLs to boolean columns don't work with our database models at this time.
     *
     * For example http://....?stock_unlimited_ca=0 is not working for 2 reasons:
     * first is the sql, second is the <select> filter elements shown on admin archive pages.
     * Lastly, ?stock_unlimited_ca=1 is showing results for zero somehow :(, even though
     * they are int types in database, perhaps passing true as string equates to matching
     * a value of string 0 somehow I really don't know, but I also don't have time to try
     * to address this right now.
     *
     * @param $is_tire
     * @param $locale
     * @param $supplier
     * @return string
     */
	public static function get_url_to_supplier( $is_tire, $locale, $supplier ) {
		$tbl = $is_tire ? DB_tires : DB_rims;
		$col_sold_in = DB_Product::get_column_sold_in( $locale );
		return get_admin_archive_link( $tbl, [
			$col_sold_in => 1,
			'supplier' => $supplier,
		] );
	}

    /**
     * @param $count
     * @param $locale
     * @param $is_tire
     * @param $supplier
     * @return string
     */
	public static function link_to_supplier( $text, $is_tire, $locale, $supplier ){
		$url = self::get_url_to_supplier( $is_tire, $locale, $supplier );
		return get_anchor_tag_simple( $url, $text );
	}
}
