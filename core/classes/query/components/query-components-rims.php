<?php

/**
 * Class Query_Components_Rims
 */
Class Query_Components_Rims extends Query_Components{

	/**
	 * Query_Components_Rims constructor.
	 *
	 * @param string $table
	 * @param bool   $prefixed
	 */
	public function __construct( $table = 'rims', $prefixed = false ){
		parent::__construct( $table, $prefixed );
	}

    /**
     * See Query_Component_Tires::apply_all_filters()
     *
     * @param $filters
     */
	public function apply_all_filters( $filters ) {

        $allow_array = true;

		$this->apply_filter( $filters, 'package_type', 'get_package_type', $allow_array );
        $this->apply_filter( $filters, 'part_number', 'get_part_number', $allow_array );
        $this->apply_filter( $filters, 'part_number_not_in', 'get_part_number_not_in', $allow_array );
        $this->apply_filter( $filters, 'type', 'get_type', $allow_array );
        $this->apply_filter( $filters, 'style', 'get_style', $allow_array );

        // we could use these but perhaps its better to just inner join finishes and use that instead.
//        $this->apply_filter( $filters, 'color_1', $allow_array, 'get_color_1' );
//        $this->apply_filter( $filters, 'color_2', $allow_array, 'get_color_2' );
//        $this->apply_filter( $filters, 'finish_3', $allow_array, 'get_finish_3' );

        // better to inner join models/brands I think. I can't really think of an instance where
        // we don't do that, therefore, ignoring model/brand slug here
//        $this->apply_filter( $filters, 'model_slug', $allow_array, 'get_model_slug' );
//        $this->apply_filter( $filters, 'brand_slug', $allow_array, 'get_brand_slug' );

        // for sizing parameters, see get_size(), or one of the many other functions
        // for min/max width, min/max offset, min_center_bore etc. etc.
    }

	/**
	 * @param $v
	 */
	public function get_package_type( $v ){
		if ( $v === 'winter' ) {
			$e = array(
				'relation' => 'OR',
				$this->get_type( 'steel' ),
				array(
					'relation' => 'AND',
					$this->get_type( 'alloy' ),
					$this->get_color_2( '' ),
					$this->get_finish( '' ),
				)
			);
			return $this->builder->get_return( $e );
		} else {
			// add this so we can tell from the query that this function at least got triggered, but ended up doing nothing
			// which is intentional in this case.
			return $this->builder->get_return( '"rim_pkg_type_not_winter" = "rim_pkg_type_not_winter"' );
		}
	}

	/**
	 *
	 */
	public function get_color_1( $v ){
		return $this->simple_equality( 'color_1', $v, '%s' );
	}

	/**
	 *
	 */
	public function get_color_2( $v ){
		return $this->simple_equality( 'color_2', $v, '%s' );
	}

	/**
	 *
	 */
	public function get_finish( $v ){
		return $this->simple_equality( 'finish', $v, '%s' );
	}

	/**
	 * @param $v
	 *
	 * @return Component_Builder
	 */
	public function get_model_slug( $v ) {
		return $this->simple_equality( 'model_slug', $v, '%s' );
	}

	/**
	 * @param $v
	 *
	 * @return Component_Builder
	 */
	public function get_model( $v ) {
		return $this->get_model_slug( $v );
	}

	/**
	 * @param $v
	 *
	 * @return Component_Builder
	 */
	public function get_brand_slug( $v ) {
		return $this->simple_equality( 'brand_slug', $v, '%s' );
	}

	/**
	 * @param $v
	 *
	 * @return Component_Builder
	 */
	public function get_brand( $v ) {
		return $this->get_brand_slug( $v );
	}

	/**
	 * @param $v
	 *
	 * @return Component_Builder
	 */
	public function get_type( $v ) {
		return $this->simple_equality( 'type', $v, '%s' );
	}

	/**
	 * @param $v
	 *
	 * @return Component_Builder
	 */
	public function get_style( $v ) {
		return $this->simple_equality( 'style', $v, '%s' );
	}

	/**
	 * @param $v
	 *
	 * @return Component_Builder
	 */
	public function get_bolt_pattern( $v ) {

		// ensure compatibility with database format vs. api format (mainly in relation to 'X' or 'x')
		$v = gp_fix_bolt_pattern_text( $v );

		$comp = array(
			'relation' => 'OR',
			$this->simple_equality( 'bolt_pattern_1', $v, '%s' ),
			$this->simple_equality( 'bolt_pattern_2', $v, '%s' ),
		);

		return $this->builder->get_return( $comp );
	}

	/**
	 * @param $v
	 *
	 * @return Component_Builder
	 */
	public function get_diameter( $v ) {
		return $this->simple_equality( 'diameter', $v, '%s' );
	}

	/**
	 * @param $v
	 */
	public function get_exact_width( $v ) {

		$p1 = $this->builder->get_parameter_name( 'exactWidth' );
		$sql = 'CAST( ' . $this->get_selector( 'width' ) . ' AS decimal(4,1)) = :' . $p1;
		$params = array();
		$params[] = array( $p1, $v, '%s' );
		return $this->builder->get_return( $sql, $params );
	}

	/**
	 * @param $v
	 */
	public function get_min_width( $v ) {

		$p1 = $this->builder->get_parameter_name( 'minWidth' );
		$sql = 'CAST( ' . $this->get_selector( 'width' ) . ' AS decimal(4,1)) >= :' . $p1;
		$params = array();
		$params[] = array( $p1, $v, '%s' );
		return $this->builder->get_return( $sql, $params );
		// return $this->simple_relation( 'width', $v, '%s', '>' );
	}

	/**
	 * @param $v
	 */
	public function get_max_width( $v ) {
		$p1 = $this->builder->get_parameter_name( 'maxWidth' );
		$sql = 'CAST( ' . $this->get_selector( 'width' ) . ' AS decimal(4,1)) <= :' . $p1;
		$params = array();
		$params[] = array( $p1, $v, '%s' );
		return $this->builder->get_return( $sql, $params );
		// return $this->simple_relation( 'width', $v, '%s', '<' );
	}

	/**
	 * @param $v
	 */
	public function get_min_offset( $v ) {
		$p1 = $this->builder->get_parameter_name( 'minOffset' );
		$sql = 'CAST( ' . $this->get_selector( 'offset' ) . ' AS DECIMAL(4,1)) >= :' . $p1;
		$params = array();
		$params[] = array( $p1, $v, '%s' );
		return $this->builder->get_return( $sql, $params );
		// return $this->simple_relation( 'offset', $v, '%d', '>=' );
	}

	/**
	 * @param $v
	 */
	public function get_max_offset( $v ) {
		$p1 = $this->builder->get_parameter_name( 'maxOffset' );
		$sql = 'CAST( ' . $this->get_selector( 'offset' ) . ' AS DECIMAL(4,1)) <= :' . $p1;
		$params = array();
		$params[] = array( $p1, $v, '%s' );
		return $this->builder->get_return( $sql, $params );
		// return $this->simple_relation( 'offset', $v, '%d', '<=' );
	}

	/**
	 * @param $v
	 *
	 * @return Component_Builder
	 */
	public function get_center_bore_min( $v ) {
		$p1 = $this->builder->get_parameter_name( 'centerBoreMin' );
		$sql = 'CAST( ' . $this->get_selector( 'center_bore' ) . ' AS decimal(4,1)) >= :' . $p1;
		$params = array();
		$params[] = array( $p1, $v, '%s' );
		return $this->builder->get_return( $sql, $params );
	}

	/**
	 * This (probably) isn't used for vehicle based queries, where we require only a minimum.
	 *
	 * @param $v
	 *
	 * @return Component_Builder
	 */
	public function get_center_bore_max( $v ) {
		$p1 = $this->builder->get_parameter_name( 'centerBoreMax' );
		$sql = 'CAST( ' . $this->get_selector( 'center_bore' ) . ' AS decimal(4,1)) <= :' . $p1;
		$params = array();
		$params[] = array( $p1, $v, '%s' );
		return $this->builder->get_return( $sql, $params );
	}

	/**
	 *
	 * @param $v
	 *
	 * @param      $v
	 * @param null $variance
	 *
	 * @return Component_Builder
	 */
	public function get_width( $v, $minus, $plus ) {

		// minus/plus should always be passed in now..
//		$plus = $plus || $plus === 0 ? $plus : get_default_width_variance();
//		$minus = $minus || $minus === 0 ? $minus : get_default_width_variance();

		$min = $v - $minus;
		$max = $v + $plus;

		$elements = array(
			'relation' => 'AND',
			$this->get_min_width( $min ),
			$this->get_max_width( $max ),
		);

		return $this->builder->get_return( $elements );
	}

	/**
	 * allows for a range of values centered around $v, in milimeters.
	 *
	 * @param     $v
	 * @param int $variance
	 */
	public function get_offset( $v, $minus, $plus ) {

		// minus/plus should always be passed in now.
//		$plus = $plus || $plus === 0 ? $plus : get_default_offset_variance();
//		$minus = $minus || $minus === 0 ? $minus : get_default_offset_variance();

		$min = $v - $minus;
		$max = $v + $plus;

		$elements = array(
			'relation' => 'AND',
			$this->get_min_offset( $min ),
			$this->get_max_offset( $max ),
		);

		return $this->builder->get_return( $elements );
	}


	/**
	 *
	 */
	public function get_part_number( $value ){
		// using sql IN allow for array values or single value
		if ( $value ){
			$selector = $this->get_selector( 'part_number' );
			return $this->builder->get_sql_in( $selector, $value, '%s' );
		}
	}

	/**
	 * @param $value
	 */
	public function get_part_number_not_in( $value ) {
		if ( $value ){
			// using sql NOT IN allow for array values or single value
			$selector = $this->get_selector( 'part_number' );
			return $this->builder->get_sql_not_in( $selector, $value, '%s' );
		}
	}

	/**
	 * @param array $size
	 */
//	public function get_rear_staggered_size( array $size ) {
//		return $this->get_rim_size_with_custom_width_variance( $size, 1, 0 );
//	}
//
//	/**
//	 * @param array $size
//	 */
//	public function get_front_staggered_size( array $size ) {
//		return $this->get_rim_size_with_custom_width_variance( $size, 0.5, 0.5 );
//	}

	/**
	 * @param array $size
	 *
	 * @return Component_Builder
	 */
	public function get_size( array $size ) {

		$offset = gp_if_set( $size, 'offset' );
		$diameter = gp_if_set( $size, 'diameter' );
		$bolt_pattern = gp_if_set( $size, 'bolt_pattern' );
		$width = gp_if_set( $size, 'width' );
		$center_bore = gp_if_set( $size, 'center_bore' );

		// these optional parameters are not usually set!
		// in some staggered queries, the width plus and width minus will be set and might not be equal
		// sending in null values will result in the system using its default values for variance,
		// which is very different than sending in a value of (int) 0
		$width_minus = gp_if_set( $size, 'width_minus', null );
		$width_plus = gp_if_set( $size, 'width_plus', null );

		// these currently are never passed in as far as I know,
		// but no guarantee of that at the time you are reading this.
		$offset_minus = gp_if_set( $size, 'offset_minus', null );
		$offset_plus = gp_if_set( $size, 'offset_plus', null );

		switch( strtolower( $bolt_pattern ) ) {
            case '5x114':
			case '5x114.3':
			case '5x115':
				$bolt_pattern_component = array(
					'relation' => 'OR',
                    $this->get_bolt_pattern( "5x114" ),
					$this->get_bolt_pattern( "5x114.3" ),
					$this->get_bolt_pattern( "5x115" ),
				);
				break;
			default:
				$bolt_pattern_component = $this->get_bolt_pattern( $bolt_pattern );
				break;
		}

		$cond = array(
			'relation' => 'AND',
			$this->get_diameter( $diameter),
			$bolt_pattern_component,
			$this->get_center_bore_min( $center_bore ),
			$this->get_width( $width, $width_minus, $width_plus ),
			$this->get_offset( $offset, $offset_minus, $offset_plus ),
		);

		return $this->builder->get_return( $cond, array() );
	}

	/**
	 * @param array $sizes
	 *
	 * @return Component_Builder
	 */
	public function get_sizes( array $sizes ) {

		$cond = array();
		$cond['relation'] = 'AND';

		if ( $sizes && is_array( $sizes ) ) {
			foreach ( $sizes as $size ) {
				$cond[] = $this->get_size( $size );
			}
		}

		return $this->builder->get_return( $cond, array() );
	}

}

/**
 * This configures the width variance for most vehicle queries containing rims.
 *
 * EXCEPT: sometimes there may be special overrides based on bolt pattern, vehicle info, or other data.
 *
 * front/rear are for STAGGERED. When not staggered, use 'universal' instead.
 *
 * This function must return the expected array indexes.
 */
function get_default_rim_width_variances() {

	$ret = array();

	// non-staggered
	$ret['universal']['plus'] = 1;
	$ret['universal']['minus'] = 1;

	$ret['front']['plus'] = 0.5;
	$ret['front']['minus'] = 0.5;

	$ret['rear']['plus'] = 1;
	$ret['rear']['minus'] = 0;

	return $ret;
}


/**
 * @see get_default_rim_width_variances(), but this is for OFFSETS.
 */
function get_default_rim_offset_variances() {

	$ret = array();

	// this used to be 7, we may increase it to 10.
	// $default = 10;
	// update march 2019: increasing to 15mm as claudio asked
	$default = 15;

	// non-staggered
	$ret['universal']['plus'] = $default;
	$ret['universal']['minus'] = $default;

	$ret['front']['plus'] = $default;
	$ret['front']['minus'] = $default;

	$ret['rear']['plus'] = $default;
	$ret['rear']['minus'] = $default;

	return $ret;
}


/**
 * apart from some special rules, most size queries allow for this
 * much width variance in inches.
 *
 * BE CAREFUL, this may not be the only place in the code where this
 * default variance is set (sorry). Its unlikely to change from 1, but
 * use caution if you do change it.
 *
 * @return int
 */
//function get_default_width_variance(){
//	return 1;
//}

/**
 * default allowed offset in milimeters. if vehicle API says
 * 35, we may allow between 28 and 42 for example.
 *
 * Currently, there is no special staggered logic for this (as there is with width variance)
 *
 * However, we may be adding some special rules based on bolt patterns for trucks, and
 * or makes and models. So in some places, a much higher value may be used.
 *
 */
//function get_default_offset_variance(){
//	return 7;
//}