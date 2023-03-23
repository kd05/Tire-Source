<?php

/**
 * A general class for pretty much all product (rim, tire, package) queries.
 *
 * Class Product_Query_General
 */
Class Product_Query_General {

	/**
	 * Leave null to use default. This goes directly into query,
	 * make sure the sql is cleaned.
	 *
	 * @var
	 */
	public $group_by;

	/**
	 * Leave null to use default. This goes directly into query,
	 * make sure the sql is cleaned.
	 *
	 * @var
	 */
	public $order_by;

	/**
	 * @var array
	 */
	public $params;

	/**
	 * only used sometimes ...
	 *
	 * @var array
	 */
	public $sizes = array();

	/**
	 * @var Component_Builder
	 */
	public $top_level_components;

	/**
	 * Some methods that are inherited may need to know about this.
	 *
	 * @var  boolean
	 */
	public $staggered_result;

	/**
	 * Some methods that are inherited may need to know about this.
	 *
	 * @var boolean
	 */
	public $result_has_tires;

	/**
	 * Some methods that are inherited may need to know about this.
	 *
	 * @var
	 */
	public $result_has_rims;

	/**
	 * True for all fitment size queries. When unioning sizes, and passing in filter
	 * params for rims, we need to do the filtering INSIDE the union, not outside. This is because
	 * our function "select best fit rims by size". We can't select the best fit rim, then apply a primary color filter,
	 * this could result in no results, when a fitting rim with a primary color does in fact exist.
	 *
	 * @var
	 */
	public $sizes_use_sql_union;

	/** @var  int|null number of items per page */
	protected $per_page;

	/** @var  int|null - current page numbers */
	protected $page;

	/**
	 * When querying by vehicle fitment sizes, which is a plural array,
	 * most filters need to be queued and applied to each tire and/or rim size
	 * inside of each inner join, which is inside of a union. So when calling
	 * apply_filter, we don't add to $this->top_level_conditions, instead we add
	 * to $this->queued_filters, and apply them later on (once for each size).
	 *
	 * @var array
	 */
	public $queued_filters;

	/** @var  array */
	protected $args;

	/** @var  string */
	protected $uid;

	/**
	 * 'US' or 'CA'. you can set this to possibly run queries for
	 * specific locale in the admin section... while not on that locale.
	 *
	 * @var
	 */
	public $locale;

	/**
	 * Product_Query_General constructor.
	 */
	public function __construct( $args = array() ) {
		$this->args           = $args;
		$this->queued_filters = array(); // for rim size unions

		$this->locale = gp_if_set( $args, 'locale', app_get_locale() );

		$uid                        = $this->uid !== null ? $this->uid : 'pq_general';
		$this->top_level_components = Component_Builder::make_new_instance( $uid );

		print_next_query();
	}

	/**
	 * Use this so we can easily toggle sql_calc_found_rows on or off, or
	 * do some additional logic to determine whats best to use... without having
	 * to modify 7-8 child classes all the time.
	 *
	 * @param array $select
	 */
	public function get_select_from_array( array $select ) {

		if ( ! $this->allow_pagination() ) {
			$sql = 'SELECT ' . implode_comma( $select );

			return $sql;
		}

		if ( $this->per_page === - 1 || $this->per_page === "-1" || $this->per_page === null ) {
			$sql = 'SELECT ' . implode_comma( $select );

			return $sql;
		}

		// don't forget to add your own space after this string
		$sql = 'SELECT SQL_CALC_FOUND_ROWS ' . implode_comma( $select );

		return $sql;
	}

	/**
	 * get a unique number... we need this for a few parameters to ensure uniqueness.
	 *
	 * @return int
	 */
	public function count() {
		global $__query_counter;
		$__query_counter = $__query_counter !== null ? $__query_counter : 0;
		$__query_counter ++;

		return $__query_counter;
	}

	/**
	 * Accepts an array or string...
	 *
	 * @param $order_by
	 *
	 * @return string
	 */
	public function get_order_by_clause( $args ) {
		return get_sql_order_by_from_array( $args );
	}

	/**
	 * @param $params
	 *
	 * @return string
	 */
	public function get_limit_clause( &$params ) {

		if ( ! $this->allow_pagination() ) {
			return '';
		}

		if ( $this->per_page === - 1 || $this->per_page === "-1" || $this->per_page === null ) {
			return '';
		}

		// localize variables...
		$per_page = $this->per_page;
		$page     = $this->page;


		// careful, these come form user input, and go into sql (well, after data binding)
		$per_page = $per_page ? (int) $per_page : 9;
		$page     = $page ? (int) $page : 1;
		$page     = $page && $page > 1 ? $page : 1;

		$offset = ( $page * $per_page ) - $per_page;

		$sql    = 'LIMIT :limit_1, :limit_2';
		$params = array_merge( $params, array(
			array( 'limit_1', $offset, '%d' ),
			array( 'limit_2', $per_page, '%d' ),
		) );

		return $sql;
	}

	/**
	 * Have to make sure we allow a value to says "don't paginate, ie. show all"
	 *
	 * @param $value
	 */
	public function set_page_number( $value ) {
		$value = $value === null ? null : (int) gp_test_input( $value );
		if ( $value !== null ) {
			$value = $value > 1 ? $value : 1;
		}
		$this->page = $value;
	}

	/**
	 * Have to make sure we allow this to default to a value that lets us know
	 * to not paginate all products.
	 *
	 * @param $value
	 */
	public function set_products_per_page( $value ) {

		$value = $value === null ? null : (int) gp_test_input( $value );

		if ( ! $value || $value === "-1" || $value === "0" ) {
			$value = - 1;
		}

		// if value was a string or something stupid, default it to be -1
		if ( $value >= 1 ) {
			$this->per_page = $value;
		} else {
			$this->per_page = - 1;
		}
	}

	/**
	 *
	 */
	public function allow_pagination() {
		// by default we allow. We still require pagination attributes to be set however.
		$allow = gp_if_set( $this->args, 'allow_pagination', true );

		return $allow;
	}

	/**
	 * Price range component for user input key of "price_each"
	 *
	 * @param $selector
	 *
	 * @return bool|Component_Builder
	 */
	public function get_price_range_component_each( $selector ) {

		// price ranges is an array of ranges, where each range is also an array
		// we may or may not need it to be like this, but it is.
		$price_ranges = isset( $this->queued_filters[ 'price_ranges_each' ] ) ? $this->queued_filters[ 'price_ranges_each' ] : false;

		if ( ! $price_ranges || ! is_array( $price_ranges ) ) {
			return false;
		}

		$selector = gp_esc_db_col( $selector );
		$builder  = Component_Builder::make_new_instance( 'priceRangesEach' );

		foreach ( $price_ranges as $price_range ) {
			$min  = gp_if_set( $price_range, 'min' );
			$max  = gp_if_set( $price_range, 'max' );
			$comp = get_singular_price_range_component( $selector, $min, $max );

			if ( $comp ) {
				$builder->add_to_self( $comp, array(), 'OR', 'priceRangesEach' );
			}
		}

		return $builder;
	}

	/**
	 * @param $selector
	 *
	 * @return bool|Component_Builder
	 */
	public function get_price_range_component( $selector ) {

		// price ranges is an array of ranges, where each range is also an array
		// we may or may not need it to be like this, but it is.
		$price_ranges = isset( $this->queued_filters[ 'price_ranges' ] ) ? $this->queued_filters[ 'price_ranges' ] : false;

		if ( ! $price_ranges || ! is_array( $price_ranges ) ) {
			return false;
		}

		$selector = gp_esc_db_col( $selector );
		$builder  = Component_Builder::make_new_instance( 'priceRanges' );

		foreach ( $price_ranges as $price_range ) {
			$min  = gp_if_set( $price_range, 'min' );
			$max  = gp_if_set( $price_range, 'max' );
			$comp = get_singular_price_range_component( $selector, $min, $max );
			if ( $comp ) {
				$builder->add_to_self( $comp, array(), 'OR', 'priceRanges' );
			}
		}

		return $builder;
	}

	/**
	 * Due to dynamic filters, we do pagination in PHP now, therefore,
	 * why is this still being used ???..
	 * @param $userdata
	 */
	public function setup_pagination_attributes( $userdata ) {
		if ( $this->allow_pagination() ) {
			$page     = gp_if_set( $userdata, 'page' );
			$per_page = gp_if_set( $userdata, 'per_page' );
			$this->set_page_number( $page );
			$this->set_products_per_page( $per_page );
		}
	}

	/**
	 * Select rims grouped by finish ID such that the rim ID selected
	 * is the best fitting one (taking into account stock level, width difference, and offset difference).
	 * In addition, most/all filters are applied to the rims inside this function.
	 * This is the main function in all vehicle queries involving rims. In staggered
	 * rim/package queries, this function is called twice within the same query. Also, this
	 * is put into a derived table / sub query of some sort, so this function alone
	 * .. well.. may return useful results but its not intended to be used alone.
	 *
	 * @param $size
	 * @param $rim_filters
	 */
	public function select_best_fit_rim_in_each_finish( $size, $rim_filters ) {

		// this was for a test...
		// return $this->select_best_fit_rims_does_not_work( $size, $rim_filters );

		// for an old logging thing...
		gp_set_global( 'rim_query_best_fit_func', 'NEW' );

		$temp_components = array();
		$table_1         = 'bf_rims_1';
		$table_2         = 'bf_rims_2';

		// unfortunately have 2 build 2 components to do the exact same thing
		// which is pretty stupid, but we need 2 to ensure we don't duplicate param names
		foreach ( [ $table_1, $table_2 ] as $tbl ) {

			$comp = new Query_Components_Rims( $tbl, false );
			$comp->apply_filter( $rim_filters, 'part_number', 'get_part_number', true );
			$comp->apply_filter( $rim_filters, 'brand', 'get_brand', true );
			$comp->apply_filter( $rim_filters, 'model', 'get_model', true );
			$comp->apply_filter( $rim_filters, 'type', 'get_type', true );
			$comp->apply_filter( $rim_filters, 'style', 'get_style', true );
			$comp->builder->add_to_self( $comp->get_size( $size ) );
			$comp->builder->add_to_self( DB_Rim::sql_assert_sold_and_not_discontinued_in_locale( $tbl, $this->locale ), [] );
			$comp->apply_filter( $rim_filters, 'color_1', 'get_color_1', true );

			if ( gp_if_set( $rim_filters, 'rim_package_type' ) === 'winter' ) {
				// "winter" rims can be steel or alloy, but must have only finish 1. no finish 2 or finish 3
				$comp->builder->add_to_self( $comp->get_color_2( '' ) );
				$comp->builder->add_to_self( $comp->get_finish( '' ) );
			} else {
				// allow for custom finish 2/3 if they are set
				$comp->apply_filter( $rim_filters, 'color_2', 'get_color_2', true );
				$comp->apply_filter( $rim_filters, 'finish', 'get_finish', true );
			}

			$temp_components[] = $comp;
			$comp              = null;
		}

		/** @var Query_Components_Rims $component_1 */
		$component_1 = $temp_components[ 0 ];

		/** @var Query_Components_Rims $component_2 */
		$component_2 = $temp_components[ 1 ];

		// init sql
		$q = '';
		$p = [];

		// first, select rims and the best fitment score from all rims grouped by finish,
		// then inner join all rims, calculating the fitment score again, but joining on
		// the fitment score being equal to the best (max) fitment score from the group.
		// we should be able to use SQL and simply order a sub query, and then group by outside of it,
		// but for whatever reason this does not work, either in mariadb, mysql or both.

		// we could get technical here, but just set each number high enough and it will work fine.
		$out_of_stock_weight = 10000;
		$width_weight        = 200;
		$offset_weight       = 1;

		// cast as int or risk sql injection
		$offset = (int) $size[ 'offset' ];

		// this throws an error for strings that don't resemble things like numbers.
		// we expect 6, 6.5 etc, not 6.0 but won't rule out the possibility of 6.0
		$width = number_format( $size[ 'width' ], 1, '.', '' );

		// Note: best_fitment_score means LOWEST fitment score. things like out of stock
		// or variances in width offset increase this value. ideally, we'd like it to always be zero

		$sums = array();

		// if out of stock, add 10000
		// unlike tires, not taking into consideration the super confusing scenario where we have
		// a staggered set and the front and rear part numbers happen to be the same, and therefore we need 4
		// of each, where we would otherwise require only 2 of each for staggered with diff. part numbers.
		if ( $this->locale === APP_LOCALE_US ) {
			$out_of_stock_condition = '( IF ( stock_unlimited_us = 1, 0, ( stock_amt_us - stock_sold_us < 4 ) ) ) ';
		} else {
			$out_of_stock_condition = '( IF ( stock_unlimited_ca = 1, 0, ( stock_amt_ca - stock_sold_ca < 4 ) ) ) ';
		}

		$sums[] = '(' . $out_of_stock_condition . ' * ' . $out_of_stock_weight . ')';

		// remember that widths in both $size and in the database can be like 6, or "6.5",
		// and less likely but maybe possible: "6.0". It doesn't really matter, SQL figures
		// out the math without any issues.
		$sums[] = '( ABS( width - "' . $width . '" ) * ' . $width_weight . ' )';

		// Offset should always be an integer value.
		$sums[] = '( ABS( offset - ' . $offset . ' ) * ' . $offset_weight . ' )';

		// we'll "select" this as a column
		$fitment_score = implode( ' + ', $sums );

		// tempting to select rim_finishes.* which in theory is perfectly ok.. except its not,
		// due to things that happen outside this function (we get a duplicate column error for model_id )
		$q .= 'SELECT rim_finishes.rim_finish_id AS rim_finish_id, best_fit_rims.*, rims_that_fit.best_fitment_score ';
		$q .= 'FROM rim_finishes ';

		// we first join rims and don't care about anything except for the best fitment score
		// within each group of finishes. I think we also have to be careful about what we select
		// from this query, so that values from the not-best fitting rim doesn't override values
		// from the rim that is the best fit...

		$q .= 'INNER JOIN ( SELECT *, MIN(' . $fitment_score . ') AS best_fitment_score ';

		// we have to apply all the conditions to get the list of rims that fit,
		// and from within that list get the min value of the fitment score.
		// omitting the conditions might mean that the best fitment score
		// is not found when we inner join a second time, and would result
		// in lots of product queries not showing results when it should.
		$q .= 'FROM rims AS ' . $table_1 . ' ';
		$q .= 'WHERE ' . $component_1->builder->sql_with_placeholders() . ' ';
		$p = array_merge( $p, $component_1->builder->parameters_array() );

		// without the group by in the first rims query, everything fails...
		$q .= 'GROUP BY finish_id ';
		$q .= ') AS rims_that_fit ON rims_that_fit.finish_id = rim_finish_id ';

		// join the rims again to re-calculate fitment scores, and eliminate
		// all the fitting rims without the highest fitment score.
		// careful.. we have to apply all conditions again here which seems redundant,
		// but its not, because otherwise the highest fitment score could be a rim that
		// does not fit, and upon the inner join, both will be eliminated.
		$q .= 'INNER JOIN ( SELECT *, (' . $fitment_score . ') AS fitment_score ';

		$q .= 'FROM rims AS ' . $table_2 . ' ';
		$q .= 'WHERE ' . $component_2->builder->sql_with_placeholders() . ' ';
		$p = array_merge( $p, $component_2->builder->parameters_array() );

		// careful. its tempting to join the 2 rims tables on rim IDs but this is wrong, they need to joined on their finish IDs.
		$q .= ') AS best_fit_rims ON best_fit_rims.fitment_score = best_fitment_score AND best_fit_rims.finish_id = rim_finish_id ';

		// since its possible that 2 rims have the same fitment score (though unlikely), we should still group
		// by finish ID. Luckily though, this time, we don't have to care about which one is chosen.
		$q .= 'GROUP BY rim_finish_id ';

		// can order by when debugging but otherwise its either inefficient or sql ignores it.
		// $q .= 'ORDER BY rim_finish_id ';

		queue_dev_alert( 'best_fit_new', debug_pdo_statement( $q, $p ) );

		// return the sql w/o a semi-colon as this goes inside another sub query
		return [ $q, $p ];
	}

	/**
	 *
	 * @param $size
	 * @param $rim_filters
	 *
	 * @return array
	 */
	public function select_best_fit_rims_does_not_work( $size, $rim_filters ) {

		throw_dev_error( 'This method has been replaced' );
		exit;

		// for an old logging thing...
		gp_set_global( 'rim_query_best_fit_func', 'OLD' );

		$table  = '_rims'; // name shouldn't really matter
		$params = array();

		// this can also be thought of as tire type but applied to rims.. ie. summer winter etc.
		// the rim type or just "type" means steel or alloy
		$rim_package_type = gp_if_set( $rim_filters, 'rim_package_type' );

		$comp = new Query_Components_Rims( $table );

		$comp->apply_filter( $rim_filters, 'part_number', 'get_part_number', true );
		$comp->apply_filter( $rim_filters, 'brand', 'get_brand', true );
		$comp->apply_filter( $rim_filters, 'model', 'get_model', true );

		// steel/alloy
		$comp->apply_filter( $rim_filters, 'type', 'get_type', true );

		// ie. replica
		$comp->apply_filter( $rim_filters, 'style', 'get_style', true );

		// sold_in_ca or sold_in_us
		$comp->builder->add_to_self( DB_Rim::sql_assert_sold_and_not_discontinued_in_locale( $table, $this->locale ), [] );

		$comp->apply_filter( $rim_filters, 'color_1', 'get_color_1', true );

		if ( $rim_package_type === 'winter' ) {

			// "winter" rims can be steel or alloy, but must have only finish 1. no finish 2 or finish 3
			$comp->builder->add_to_self( $comp->get_color_2( '' ) );
			$comp->builder->add_to_self( $comp->get_finish( '' ) );

		} else {

			// allow for custom finish 2/3 if they are set
			$comp->apply_filter( $rim_filters, 'color_2', 'get_color_2', true );
			$comp->apply_filter( $rim_filters, 'finish', 'get_finish', true );
		}

		// ** THE SIZE **
		$comp->builder->add_to_self( $comp->get_size( $size ) );

		// dont hardcode these parameter names... the function we're in can be called multiple times in one query, resulting in duplicate param name.
		$param_front_width  = $comp->builder->get_parameter_name( 'compareWidth' );
		$param_front_offset = $comp->builder->get_parameter_name( 'compareOffset' );

		// use same alias inside the derived table (ie. 'front_rims' twice)
		$sql = '';

		$sql .= 'SELECT * ';
		$sql .= 'FROM ( SELECT * ';
		$sql .= 'FROM ' . DB_rims . ' AS ' . gp_esc_db_col( $table ) . ' ';

		// the sql conditions that resulted from apply_filter() calls
		$sql .= 'WHERE 1 = 1 AND ' . $comp->builder->sql_with_placeholders() . ' ';

		$sql .= '';

		// todo: this actually doesn't do anything at all, as grouping ignores ordering
		$order_by = array(
			// this optional item might help prevent close width matches with very high offsets but, for now i'm leaving it off.
			// '( ABS (CAST(offset AS decimal(4,1)) - :' . $param_front_offset . ') ) <= 25 DESC',
			'rim_id DESC',
			'ABS (CAST(width AS decimal(4,1)) - :' . $param_front_width . ') ASC',
			'ABS (CAST(offset AS decimal(4,1)) - :' . $param_front_offset . ') ASC',
		);

		$params[] = array( $param_front_width, $size[ 'width' ], '%s' );
		$params[] = array( $param_front_offset, $size[ 'offset' ], '%s' );

		// testing a hack (using highest possible limit to force ordering) which doesn't seem to be fixing the issue.
		$sql .= 'ORDER BY ' . implode_comma( $order_by ) . ' ';
		$sql .= 'LIMIT 18446744073709551615 ';

		$sql .= ') AS rims ';

		$sql .= 'GROUP BY finish_id ';

		$params = array_merge( $params, $comp->builder->parameters_array() );

		return array( $sql, $params );
	}

	/**
	 * Sometimes does nothing. Other times converts database results into more useful array.
	 *
	 * @param $results
	 *
	 * @return array
	 */
	public static function parse_results( $results ) {

		if ( ! method_exists( get_called_class(), 'parse_row' ) ) {
			return $results;
		}

		$ret = array();

		if ( $results && is_array( $results ) ) {
			foreach ( $results as $row ) {
				$ret[] = static::parse_row( $row );
			}
		}

		return $ret;
	}

	/**
	 * @param $size
	 */
	public function get_tires_by_size_only( $size ) {

		$db   = get_database_instance();
		$comp = new Query_Components_Tires( 'tires' );
		$comp->builder->add_to_self( $comp->get_size( $size ) );
		$comp->builder->add_to_self( DB_Tire::sql_assert_sold_and_not_discontinued_in_locale( 'tires' ) );

		$q = '';
		$p = array();

		$q .= 'SELECT * ';
		$q .= 'FROM tires ';
		$q .= 'INNER JOIN tire_brands ON tire_brands.tire_brand_id = tires.brand_id ';
		$q .= 'INNER JOIN tire_models ON tire_models.tire_model_id = tires.model_id ';
		$q .= 'WHERE 7 = 7 ';

		$q .= 'AND ' . $comp->builder->sql_with_placeholders() . ' ';
		$p = array_merge( $p, $comp->builder->parameters_array() );
		$q .= ';';

		$results = $db->get_results( $q, $p );

		queue_dev_alert( 'pkg query TIRE size array', get_pre_print_r( $size ) );
		queue_dev_alert_for_query( 'get_tires_by_size_only', $q, $p, $results );

		return $results ? $results : array();
	}

	/**
	 * @param $size
	 */
	public function get_rims_by_size_only( $size ) {

		$db   = get_database_instance();
		$comp = new Query_Components_Rims( 'rims' );
		$comp->builder->add_to_self( $comp->get_size( $size ) );
		$comp->builder->add_to_self( DB_Tire::sql_assert_sold_and_not_discontinued_in_locale( 'rims' ) );

		$q = '';
		$p = array();

		$q .= 'SELECT * ';
		$q .= 'FROM rims ';
		$q .= 'INNER JOIN rim_brands ON rim_brands.rim_brand_id = rims.brand_id ';
		$q .= 'INNER JOIN rim_models ON rim_models.rim_model_id = rims.model_id ';
		$q .= 'INNER JOIN rim_finishes ON rim_finishes.rim_finish_id = rims.finish_id ';
		$q .= 'WHERE 6 = 6 ';

		$q .= 'AND ' . $comp->builder->sql_with_placeholders() . ' ';
		$p = array_merge( $p, $comp->builder->parameters_array() );
		$q .= ';';

		$results = $db->get_results( $q, $p );

		queue_dev_alert( 'pkg query RIM size array', get_pre_print_r( $size ) );
		queue_dev_alert_for_query( 'get_rims_by_size_only', $q, $p, $results );

		return $results ? $results : array();
	}
}
