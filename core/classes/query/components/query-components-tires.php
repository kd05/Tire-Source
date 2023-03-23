<?php

/**
 * Class Query_Components_Tires
 */
Class Query_Components_Tires extends Query_Components {

	public $table;

	/**
	 * Query_Components_Tires constructor.
	 *
	 * @param string $table
	 * @param bool   $prefixed
	 */
	public function __construct( $table, $prefixed = false ) {
		parent::__construct( $table, $prefixed );
	}

    /**
     * Note: DO NOT pass in $_GET as $filters,
     *
     * This function is more for calling within code when you are the one
     * creating the $filters array, or white-listing its values.
     *
     * Will contain size params, but you should use $this->get_size() if inserting
     * size parameters for a vehicle query.
     *
     * The array indexes are what we call them from code, which is similar
     * but not always identical to what we use in $_GET.
     * Ie. $_GET['_brand'] or $filters['brand']
     *
     * Lastly, there's nothing wrong with not using this function. If you are dealing
     * with $_GET, you can call apply_filter() only on the items that are allowed,
     * and fine tune the indexes used as well as whether or not to allow multiple values.
     *
     * @param $filters
     */
	public function apply_all_filters( $filters ) {

        // all filters will accept array values and turn them into OR conditions
        // if you don't want this, ensure each value of $filter is not an array.
        $allow_array = true;

        $this->apply_filter( $filters, 'part_number', 'get_part_number', $allow_array );
        $this->apply_filter( $filters, 'part_number_not_in', 'get_part_number_not_in', $allow_array );
        $this->apply_filter( $filters, 'brand', 'get_brand', $allow_array );
        $this->apply_filter( $filters, 'model', 'get_model', $allow_array );
        $this->apply_filter( $filters, 'tire_sizing_system', 'get_tire_sizing_system', $allow_array );
        $this->apply_filter( $filters, 'speed_rating', 'get_speed_rating', $allow_array );
        $this->apply_filter( $filters, 'speed_rating_min', 'get_speed_rating_min', $allow_array );
        $this->apply_filter( $filters, 'diameter', 'get_diameter', $allow_array );
        $this->apply_filter( $filters, 'width', 'get_width', $allow_array );
        $this->apply_filter( $filters, 'profile', 'get_profile', $allow_array );
    }

    /**
     * ie. speed rating = 95. if a vehicle specifies a speed rating,
     * you're going to want to use get_speed_rating_min instead.
     *
     * When a user filters tires by exact speed rating, we'll use this.
     *
     * @param $v
     *
     * @return Component_Builder
     */
    public function get_speed_rating( $v ) {
        return $this->simple_equality( 'speed_rating', $v, '%s' );
    }


    /**
	 * @param $v
	 */
	public function get_tire_sizing_system( $v ) {

		if ( ! $v ) {
			return false;
		}

		$v = gp_force_singular( $v );

		if ( ! $v ) {
			return false;
		}

		$v = trim( $v );
		$v = strtolower( $v );

		if ( $v == 'lt-metric' ) {
			$ret = $this->simple_equality( 'tire_sizing_system', $v, '%s' );
			return $ret;
		}

		return false;
	}

	/**
     * This does:
     *
     * speed_rating >= :min OR type = "winter",
     *
     * which can be combined with any tire type in the same query, ie.
     *
     * ( speed_rating >= :min OR type = "winter" ) AND type="winter",
     * ( speed_rating >= :min OR type = "winter" ) AND type="all-season",
     * ( speed_rating >= :min OR type = "winter" ) AND type="all-weather",
     * ( speed_rating >= :min OR type = "winter" ) AND type="summer",
     *
     * "type" here is actually tire_models.tire_model_type, and speed_rating is in the tires table.
	 *
	 * @param $v
	 */
	public function get_speed_rating_min_or_type_winter( $v, $tire_model_selector = 'models' ) {

		$allowed = get_speed_ratings_greater_than_or_equal_to( $v );
		$selector = $this->get_selector( 'speed_rating' );

		$ret = array(
			'relation' => 'OR',
			gp_esc_db_col( $tire_model_selector, true ) . '.tire_model_type = "winter"',
			$this->builder->get_sql_in( $selector, $allowed, '%s' ),
		);

		return $this->builder->get_return( $ret );
	}

	/**
	 * note: we may pass on calling this function for winter tires in some (probably vehicle) queries
	 *
	 * @param $v
	 */
	public function get_speed_rating_min( $v ) {
		$allowed = get_speed_ratings_greater_than_or_equal_to( $v );
		$selector = $this->get_selector( 'speed_rating' );
		if ( $allowed ) {
			return $this->builder->get_sql_in( $selector, $allowed, '%s' );
		}
	}

	/**
	 * load index does not work like speed rating in relation to winter tires.. therefore
	 * removing this function.
	 *
	 * @param $v
	 */
//	public function get_load_index_min_plus_tire_model_type( $v, $tire_model_selector = 'models' ) {
//
//		$ret = array(
//			'relation' => 'OR',
//			gp_esc_db_col( $tire_model_selector, true ) . '.tire_model_type = "winter"',
//			$this->get_load_index_min( $v ),
//		);
//
//		return $this->builder->get_return( $ret );
//	}


	/**
	 * @param $v
	 *
	 * @return Component_Builder
	 */
	public function get_load_index_min( $v ) {

		$p = $this->builder->get_parameter_name( 'loadIndex' );

		$params = array();
		$params[] = array( $p, $v, '%d' );

		$selector = $this->get_selector( 'load_index' );
		$sql = 'CAST(' . $selector . ' AS UNSIGNED) >= :' . $p;

		$ret = $this->builder->get_return( $sql, $params );
		return $ret;
	}

	/**
	 * Note: can also use Query_Components_Tire_Brands->get_slug()
	 *
	 * @param $v
	 */
	public function get_brand( $v ) {
		return $this->simple_equality( 'brand_slug', $v, '%s' );
	}

	/**
	 * Note: can also use Query_Components_Tire_Models->get_slug()
	 *
	 * @param $v
	 */
	public function get_model( $v ) {
		return $this->simple_equality( 'model_slug', $v, '%s' );
	}

	/**
	 * @param $v
	 *
	 * @return Component_Builder
	 */
	public function get_width( $v ) {
		return $this->simple_equality( 'width', $v, '%d' );
	}

	/**
	 * @param $v
	 *
	 * @return Component_Builder
	 */
	public function get_diameter( $v ) {
		return $this->simple_equality( 'diameter', $v, '%d' );
	}

	/**
	 * @param $v
	 *
	 * @return Component_Builder
	 */
	public function get_profile( $v ) {
		return $this->simple_equality( 'profile', $v, '%d' );
	}

	/**
	 *
	 */
	public function get_part_number( $value ){
		// using sql IN allow for array values or single value
		$selector = $this->get_selector( 'part_number' );
		return $this->builder->get_sql_in( $selector, $value, '%s' );
	}

	/**
	 * @param $value
	 */
	public function get_part_number_not_in( $value ) {
		// using sql NOT IN allow for array values or single value
		$selector = $this->get_selector( 'part_number' );
		return $this->builder->get_sql_not_in( $selector, $value, '%s' );
	}

	/**
	 * @param array $size
	 *
	 * @return Component_Builder
	 */
	public function get_size( array $size ) {

		$diameter = gp_if_set( $size, 'diameter' );
		$profile  = gp_if_set( $size, 'profile' );
		$width    = gp_if_set( $size, 'width' );
		$tire_sizing_system = gp_if_set( $size, 'tire_sizing_system' );

		$cond = array(
			'relation' => 'AND',
			$this->get_tire_sizing_system( $tire_sizing_system ),
			$this->get_width( $width ),
			$this->get_profile( $profile ),
			$this->get_diameter( $diameter ),
		);

		// always apply minimum load index.
		$load_index  = gp_if_set( $size, 'load_index' );

		// except if its zero.
		// note: some vehicle data does not specify this in which case, its fine to do nothing.
		if ( $load_index ) {
			$cond[] = $this->get_load_index_min( $load_index );
		}

		// $speed_rating = gp_if_set( $size, 'speed_rating' );
		// do not apply speed rating here. Speed rating minimum from vehicle fitments
		// are only applied to non-winter tires. We need to inner join tire models to check that
		// therefore, we cannot apply a speed rating here.

		return $this->builder->get_return( $cond, array() );
	}

	/**
	 * @param array $sizes
	 *
	 * @return Component_Builder
	 */
	public function get_sizes( array $sizes ) {

		$cond               = array();
		$cond[ 'relation' ] = 'AND';

		if ( $sizes && is_array( $sizes ) ) {
			foreach ( $sizes as $size ) {
				$cond[] = $this->get_size( $size );
			}
		}

		return $this->builder->get_return( $cond, array() );
	}
}

/**
 *
 */
function get_speed_ratings_greater_than_or_equal_to( $suggested ){

	// must be numerically indexed
	$ordered = array(
		'L',
		'M',
		'N',
		'P',
		'Q',
		'R',
		'S',
		'T',
		'U',
		'H',
		'V',
		'W',
		'Y',
	);

	$order = gp_if_set( array_flip( $ordered ), $suggested, null );

	if ( $order === null ) {
		return false; // not one of the items in $ordered
	}

	$ret = [];

	if ( $ordered ) {
		foreach ( $ordered as $k=>$v ) {
			if ( $k >= $order ) {
				$ret[] = $v;
			}
		}
	}

	return $ret;
}