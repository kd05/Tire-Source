<?php

/**
 * Class Query_ComponentsR
 */
Class Query_Components {

	/** @var Component_Builder */
	public $builder;

	/** @var  string */
	protected $table;

	public static $empty_value_indicator = '__EMPTY__';

	/**
	 * Lets $this->table be a table like 'tires', or a prefix like 'front_tire_'
	 *
	 * @var
	 */
	protected $prefixed;

	/**
	 * Ensures uniqueness of parameters always
	 *
	 * @var int
	 */
	private static $instance_counter;

	/**
	 * Query_Components constructor.
	 */
	public function __construct( $table, $prefixed = false ) {

		// some not totally needed code...but this helps to ensure uniqueness of param names
		self::$instance_counter = self::$instance_counter !== null ? (int) self::$instance_counter : 0;
		self::$instance_counter ++;

		$uid   = $table . self::$instance_counter;
		$world = new World( $uid );

		$this->builder  = new Component_Builder( $world );
		$this->table    = $table;
		$this->prefixed = $prefixed;
	}

    /**
     * @return string
     */
	public function get_table(){
        assert( ! $this->prefixed );
        return $this->table;
    }

	/**
	 * @param        $col
	 * @param        $value
	 * @param string $type
	 * @param string $rel
	 * @param string $description
	 */
	public function simple_relation( $col, $value, $type = '%d', $rel = '<', $description = 'sr' ) {
		$selector = $this->get_selector( $col ); // this adds the table name

		return $this->builder->simple_relation( $selector, $value, $type, $rel, $description );
	}

	/**
	 * @param        $col
	 * @param        $value
	 * @param string $type
	 * @param string $description
	 *
	 * @return Component_Builder
	 */
	public function simple_equality( $col, $value, $type = '%s', $description = 'se' ) {
		$selector = $this->get_selector( $col ); // this adds the table name

		return $this->builder->simple_relation( $selector, $value, $type, '=', $description );

	}

	/**
	 * @param      $column
	 *
	 * @return string
	 */
	public function get_selector( $column ) {
		// add a dot between table and column if not prefixed.
		$middle = $this->prefixed ? '' : '.';
		$base   = $this->table; // remember this could be empty and that is valid
		$ret    = $base . $middle . $column;

		return gp_esc_db_col( $ret );
	}

	/**
	 * @param       $raw_data
	 * @param       $index
	 * @param bool  $allow_array
	 * @param       $callback
	 * @param array $additional_callback_params
	 *
	 * @throws Exception
	 */
	public function apply_filter( $raw_data, $index, $callback, $allow_array = false, $additional_callback_params = array(), $relation = 'OR', $relation_index = null ) {

		// ensure callback exists right away to prevent hidden errors in the code
		if ( ! method_exists( $this, $callback ) ) {
			throw_dev_error( 'Callback method does not exist (' . $callback . ')' );
		}

		$relation_index = $relation_index === null ? $index : $relation_index;

		if ( $raw_data === null ) {
			$value = $index;
		} else {
			$value = gp_if_set( $raw_data, $index, null );
		}

		// in some cases we need to allow a query to search for empty results
		// I don't want to use values that are similar to false because often we default
		// user input to any one of empty string, null, etc. when its not set.
		// so use a special empty constant...
		if ( $value === self::$empty_value_indicator ) {

			$value = array( "" );

		} else if ( gp_is_singular( $value ) ) {

			// this is rather important. a lot of queries will search for empty columns and
			// end up with no results if we don't have this.
			if ( ! $value ) {
				return;
			} else {
				$value = array( $value );
			}

		} else {
			// $allow_array can be thought of as "allow multiple values"
			$value = force_non_indexed_array( $value, $allow_array );
		}

		// call the callback one or multiple times
		foreach ( $value as $v ) {
			if ( gp_is_singular( $v ) ) {
				$func_args = array( $v );
				if ( $additional_callback_params ) {
					$func_args = array_merge( $func_args, $additional_callback_params );
				}

				$new_component = call_user_func_array( array( $this, $callback ), $func_args );
				$this->builder->add_to_self( $new_component, array(), $relation, $relation_index );

				// $this->{$callback}($v);
			} else {
				// we don't throw exception here. $raw_data is userdata, it could be an array of arrays or literally anything.
				// therefore.. if its not what we expect, then we fail silently
			}

			// if logic at top of function is correct then we shouldn't need this
			if ( ! $allow_array ) {
				break;
			}
		}

	}
}

/**
 * @param $min
 * @param $max
 */
function get_singular_price_range_component( $selector, $min, $max, $prices_in_dollars = false, $database_prices_in_dollars = true ) {

	if ( ! $min && ! $max ) {
		return false;
	}

	if ( $database_prices_in_dollars ) {
		if ( ! $prices_in_dollars ) {
			$min = cents_to_dollars( $min );
			$max = cents_to_dollars( $max );
		}
	} else {
		// db in cents... params in dollars
		if ( $prices_in_dollars ) {
			$min = dollars_to_cents( $min );
			$max = dollars_to_cents( $max );
		}
	}

	$selector = gp_esc_db_col( $selector );
	$builder  = Component_Builder::make_new_instance( 'priceRange' );
	$params = array();

	$p1 = $builder->get_parameter_name( 'min' );
	$p2 = $builder->get_parameter_name( 'max' );

	$conditions = array(
		'relation' => 'AND',
	);

	if ( $min ) {
		$conditions[] = 'CAST( ' . $selector . ' AS decimal(6,2)) >= :' . $p1;
		// $conditions[] = $selector . ' >= :' . $p1;
		$params[]     = array( $p1, $min, '%s' );
	}

	if ( $max ) {
		$conditions[] = 'CAST( ' . $selector . ' AS decimal(6,2)) <= :' . $p2;
		// $conditions[] = $selector . ' <= :' . $p2;
		$params[]     = array( $p2, $max, '%s' );
	}

	$ret = $builder->get_return( $conditions, $params );

	return $ret;
}