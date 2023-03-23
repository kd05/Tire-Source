<?php
/**
 * Look for products that may have inconsistencies
 */



/**
 * Class Product_Test
 */
Class Product_Test {

	public $name;
	public $results;
	public $sql;

	/**
	 * render a table...
	 *
	 * @return string
	 */
	public function render() {

		$rows = array();

		if ( $this->results && is_array( $this->results ) ) {

			foreach ( $this->results as $row ) {

				if ( $row instanceof DB_Product ) {
					$rows[] = $row->to_array();
					continue;
				}

				// assume its raw query results...
				$rows[] = $row;
			}
		}

		$ret = '';
		$ret .= '<h3>' . $this->name . '</h3>';
		$ret .= '<p>' . $this->sql . '</p>';
		$ret .= render_html_table_admin( false, $rows );

		return $ret;
	}

	/**
	 * @param $name
	 * @param $sql
	 * @param $params
	 *
	 * @return Product_Test
	 */
	public static function create( $name, $sql, $params ) {
		$db           = get_database_instance();
		$ret          = new self();
		$ret->name    = $name;
		$ret->sql     = debug_pdo_statement( $sql, $params );
		$ret->results = $db->get_results( $sql, $params );

		return $ret;
	}
}

/**
 * Class Product_Tests
 */
Class Product_Tests {

	/**
	 * @param $is_tire
	 * @param $locale
	 *
	 * @return Product_Test
	 */
	public static function get_sold_in_with_prices( $is_tire, $locale ) {

		$tbl  = $is_tire ? DB_tires : DB_rims;
		$name = 'Products with prices but not sold in locale: ' . $locale . ' ' . $tbl;
		assert( app_is_locale_valid( $locale ) );

		$col_price   = DB_Product::get_column_price( $locale );
		$col_sold_in = DB_Product::get_column_sold_in( $locale );

		$p = [];
		$q = '';
		$q .= 'SELECT * ';
		$q .= 'FROM ' . $tbl . ' ';

		$q .= "WHERE $col_price > 0 ";
		$q .= "AND $col_sold_in = 0 ";

		$q .= ';';

		return Product_Test::create( $name, $q, $p );
	}

	/**
	 * @param $is_tire
	 * @param $locale
	 *
	 * @return Product_Test
	 */
	public static function get_unlimited_and_discontinued( $is_tire, $locale ) {

		$tbl  = $is_tire ? DB_tires : DB_rims;
		$name = 'Products with stock both unlimited and discontinued: ' . $locale . ' ' . $tbl;
		assert( app_is_locale_valid( $locale ) );

		$col_unlimited    = DB_Product::get_column_stock_unlimited( $locale );
		$col_discontinued = DB_Product::get_column_stock_discontinued( $locale );

		$p = [];
		$q = '';
		$q .= 'SELECT * ';
		$q .= 'FROM ' . $tbl . ' ';
		$q .= "WHERE $col_unlimited = 1 ";
		$q .= "AND $col_discontinued = 1 ";
		$q .= ';';

		return Product_Test::create( $name, $q, $p );
	}

	/**
	 *
	 */
	public static function all() {

		$ret = array();

		$ret[] = self::get_sold_in_with_prices( true, APP_LOCALE_CANADA );
		$ret[] = self::get_sold_in_with_prices( false, APP_LOCALE_CANADA );
		$ret[] = self::get_sold_in_with_prices( true, APP_LOCALE_US );
		$ret[] = self::get_sold_in_with_prices( false, APP_LOCALE_US );

		$ret[] = self::get_unlimited_and_discontinued( true, APP_LOCALE_CANADA );
		$ret[] = self::get_unlimited_and_discontinued( false, APP_LOCALE_CANADA );
		$ret[] = self::get_unlimited_and_discontinued( true, APP_LOCALE_US );
		$ret[] = self::get_unlimited_and_discontinued( false, APP_LOCALE_US );

		return $ret;
	}

	/**
	 * @param $tests
	 */
	public static function render( $tests ) {

		$ret = '';

		if ( $tests ) {
			/** @var Product_Test $test */
			foreach ( $tests as $test ) {
				$ret .= $test->render();
			}
		}

		return $ret;
	}
}

echo Product_Tests::render( Product_Tests::all() );