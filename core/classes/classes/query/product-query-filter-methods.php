<?php

/**
 * Class Product_Query_Filter_Methods
 */
Class Product_Query_Filter_Methods extends Product_Query_General{

	/**
	 * Product_Query_Filter_Methods constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args  = array()) {
		parent::__construct( $args );
	}

	/**
	 * To queue a filter simply means to add some data to an array which is a class property. Each query will then
	 * check for different filters that may or may not have been queued. Remember that filters can mean different things
	 * for different queries. So if you queue a filter that says "tire type should be winter", then tire queries work as
	 * expected, packaged queries have to add logic to both rims and tires, and rim queries don't care. This is why we need
	 * to first queue filters, and then apply them later.
	 *
	 * @param $raw_data
	 * @param $index
	 * @param $data_singular - whether to force the $callback to be called a maximum of 1 time
	 * @param $component
	 * @param $callback
	 */
	public function queue_filter( $raw_data, $index, $callback, $allow_array = false, $additional_callback_params = array() ) {

		// technically we could put this lower, but that would allow for some coding errors to remain undetected
		if ( ! method_exists( $this, $callback ) ) {
			throw_dev_error( 'Callback method does not exist in specified component (' . $callback . ')' );
		}

		// allow passing in just a value in 2nd parameter because sometimes our parameter doesnt exist in an array
		// and its pretty pointless to make an array consisting of only our data point, and then point to it with $index
		if ( $raw_data === null ) {
			$value = $index;
		} else {
			$value = gp_if_set( $raw_data, $index, null );
		}

		// careful not exit if values are set and equal to zero for example
		// return if value is not set, or "set" but equal to null.
		// empty string, or 0 are valid and need to be! see below...
		// false value? - i'm not sure yet ...
		if ( $value === null ) {
			return;
		}

		// in certain cases we may want to pass in $allow_array = [true,false], or [false,false] etc.
//		if ( is_array( $allow_array ) && array_is_simple( $allow_array, 2, true ) ) {
//			$allow_array_1 = $allow_array[0];
//			$allow_array_2 = $allow_array[1];
//		}

		// $allow_array can be thought of as "allow multiple values"
		// $value = force_non_indexed_array( $value, $allow_array );

		// we have to allow empty string or 0 values here so that we can queue parameters that say for example:
		// rims.color_2 = "". Without this, we're screwed!
		// However, with it we have to be very careful! Putting a hidden input into a form with an empty value upon submission
		// will send to a URL like page.php?part_number&whatever.
		// therefore $_GET['part_number'] will be ???? null? empty string ? false ?
		// it may cause the query to search for a product without a part number, rather than ignore the part number parameter altogether.
		// the desired behaviour is highly dependant on context, so values that exist but are strictly null should always be passed in
		// if you want the value to do nothing.

		// the force_non_indexed_array() function doesn't handle false-like values so we'll do it manually
		$value = $value === "" || $value === 0 || $value === "0" ? array( $value ) : force_non_indexed_array( $value, $allow_array );

		// i'm leaving this commented out so that you don't do this.
		// $value = array_filter( $value );

		// call the callback one or multiple times
		foreach ( $value as $v ) {
			// optional_todo: would be nice to allow for arrays of arrays, for filter methods that expect arrays to be passed in..
			if ( gp_is_singular( $v ) ) {
				$func_args = array( $v );
				if ( $additional_callback_params ) {
					$func_args = array_merge( $func_args, $additional_callback_params );
				}
				call_user_func_array( array( $this, $callback ), $func_args );
				// $this->{$callback}($v);
			} else {
				// we don't throw exception here. $raw_data is userdata, it could be an array of arrays or literally anything.
				// if its not what we expect, then we fail silently
			}

			// if logic at top of function is correct then we shouldn't need this
			if ( ! $allow_array ) {
				break;
			}
		}
	}

	/**
	 * For each individual query you may want to copy from here and then erase some lines
	 * in order to apply user filters. This function isn't meant to be called directly and does nothing.
	 */
	protected function all_available_filters() {

		// this function is not meant to be called its just for reference
		if ( false ) {
			$userdata = array();

			// different queries handle this in different ways, but we'll stick to one function to setup a price range
//			$this->queue_filter( $userdata, 'price', false, 'add_price_range' );
//			$this->queue_filter( $userdata, 'price_each', false, 'add_price_range_each' );
//
//			// rims
//			$this->queue_filter( $userdata, 'brand', true, 'add_rim_brand' );
//			$this->queue_filter( $userdata, 'model', true, 'add_rim_model' );
//			$this->queue_filter( $userdata, 'type', true, 'add_rim_type' );
//			$this->queue_filter( $userdata, 'color_1', true, 'add_rim_color_1' );
//			$this->queue_filter( $userdata, 'color_2', true, 'add_rim_color_2' );
//			$this->queue_filter( $userdata, 'finish', true, 'add_rim_finish' );

//			$query->queue_filter( $userdata, 'rim_type', true, 'add_rim_type' );
//			$query->queue_filter( $userdata, 'rim_style', true, 'add_rim_style' );
//
//			$this->queue_filter( $userdata, 'width', false, 'add_rim_width' );
//			$this->queue_filter( $userdata, 'diameter', false, 'add_rim_diameter' );
//			$this->queue_filter( $userdata, 'bolt_pattern', false, 'add_rim_bolt_pattern' );
//			$this->queue_filter( $userdata, 'offset_min', false, 'add_rim_offset_min' );
//			$this->queue_filter( $userdata, 'offset_max', false, 'add_rim_offset_max' );
//			$this->queue_filter( $userdata, 'hub_bore_min', false, 'add_rim_center_bore_min' );
//			$this->queue_filter( $userdata, 'hub_bore_max', false, 'add_rim_center_bore_max' );
//
//			// tires
//			$this->queue_filter( $userdata, 'part_number', true, 'add_tire_part_number' );
//			$this->queue_filter( $userdata, 'part_number_not', true, 'add_tire_part_number_not' );
//
//			$this->queue_filter( $userdata, 'speed_rating', true, 'add_tire_speed_rating' );
//			$this->queue_filter( $userdata, 'load_index', true, 'add_tire_load_index_min' );
//
//			// tire brands
//			$this->queue_filter( $userdata, 'brand', true, 'add_tire_brand' );
//
//			// tire models
//			$this->queue_filter( $userdata, 'model', true, 'add_tire_model' );
//			$this->queue_filter( $userdata, 'type', true, 'add_tire_type' );
//			$this->queue_filter( $userdata, 'class', true, 'add_tire_class' );
//			$this->queue_filter( $userdata, 'category', true, 'add_tire_category' );
		}

		Throw new exception( 'This function isnt doing anything' );
	}

	/**
	 * could be for a tire or a rim, and probably only applies to queries with
	 * staggered result sets, which at the moment means only vehicle queries.
	 *
	 * @param $value
	 */
	public function add_part_number_front( $value ){
		$this->queued_filters['part_numbers']['front'] = $value;
	}

	/**
	 * could be for a tire or a rim, and probably only applies to queries with
	 * staggered result sets, which at the moment means only vehicle queries. I'm not sure
	 * how this makes sense for vehicle queries with multiple sizes where some are staggered
	 * and some are not, or for queries that have a single size that isn't staggered. I don't know
	 * whether this will be ignored, or if it will cause the query to return nothing. And I also
	 * don't know what will happen if you also use add_rim_part_number(). This is currently
	 * being made specifically for single product pages where we have a vehicle, fitment, and product(s)
	 * selected, where the fitment is staggered and therefore we have 2 products.
	 *
	 * @param $value
	 */
	public function add_part_number_rear( $value ){
		$this->queued_filters['part_numbers']['rear'] = $value;
	}

	/**
	 * This is called 'add', but it it overrides previous values
	 * if they exist. There can only be one sort by value.
	 *
	 * @param $value
	 */
	protected function add_sort( $value ) {
		$this->queued_filters['sort'] = gp_test_input( $value );
	}

	/**
	 * @param $value
	 */
	protected function add_price_range_each( $value ) {
		$arr = parse_price_range_string_in_cents( $value );
		if ( $arr ) {
			$min = $arr[ 0 ];
			$max = $arr[ 1 ];
			if ( $min || $max ) {
				$this->queued_filters[ 'price_ranges_each' ][] = array(
					'min' => $min,
					'max' => $max,
				);
			}
		}
	}

	/**
	 * A price range can be added to any query, but only those queries that care about
	 * it may do something with the result. And of course, how we handle the result is highly
	 * dependant on the query. So we do nothing for now except store the min and max values
	 * for later on.
	 *
	 * @param $value
	 */
	protected function add_price_range( $value ) {

		$arr = parse_price_range_string_in_cents( $value );

		if ( $arr ) {
			$min = $arr[ 0 ];
			$max = $arr[ 1 ];

			if ( $min || $max ) {
				$this->queued_filters[ 'price_ranges' ][] = array(
					'min' => $min,
					'max' => $max,
				);
			}
		}
	}

	/**
	 * @param $value
	 */
	protected function add_rim_part_number( $value ) {
		$this->queued_filters['rims']['part_number'][] = $value;
	}

	/**
	 * @param $value
	 */
	protected function add_rim_part_number_not( $value ) {
		$this->queued_filters['rims']['part_number_not'][] = $value;
	}

	/**
	 * @param $value
	 */
	protected function add_rim_diameter( $value ) {
		$this->queued_filters['rims']['diameter'][] = $value;
	}

	/**
	 * @param $value
	 */
	protected function add_rim_width( $value ) {
		$this->queued_filters['rims']['width'][] = $value;
	}

	/**
	 * @param $value
	 */
	protected function add_rim_bolt_pattern( $value ) {
		// allow multiple values even though its unlikely we'll ever want to use more than 1
		$this->queued_filters['rims']['bolt_pattern'][] = $value;
	}

	/**
	 * This function should probably be called only once.
	 *
	 * @param $value
	 */
	protected function add_rim_offset_min( $value ) {
		// don't allow multiple values
		$this->queued_filters['rims']['offset_min'] = $value;
	}

	/**
	 * This function should probably be called only once.
	 *
	 * @param $value
	 */
	protected function add_rim_offset_max( $value ) {
		// don't allow multiple values
		$this->queued_filters['rims']['offset_max'] = $value;
	}

	/**
	 * For vehicle based queries, we only care about minimum. Vehicles have hub bores,
	 * and all rims that are larger will fit. For rims queried by custom sizes, the user may
	 * input a range of hub bores.
	 *
	 * @param $value
	 */
	protected function add_rim_center_bore_min( $value ) {
		// don't allow multiple values
		$this->queued_filters['rims']['center_bore_min'] = $value;
	}

	/**
	 * For queries based on custom rim sizes
	 *
	 * @param $value
	 */
	protected function add_rim_center_bore_max( $value ) {
		// don't allow multiple values
		$this->queued_filters['rims']['center_bore_max'] = $value;
	}

	/**
	 * @param $value
	 */
	protected function add_rim_brand( $value ) {
		// add to both rims, and rim brands, let the individual class decide which one to use
		$this->queued_filters['rims']['brand'][] = $value;
		$this->queued_filters['rim_brands']['slug'][] = $value;
	}

	/**
	 * @param $value
	 */
	protected function add_rim_model( $value ) {
		$this->queued_filters['rims']['model'][] = $value;
		$this->queued_filters['rim_models']['slug'][] = $value;
	}

	/**
	 * @param $value
	 */
	protected function add_rim_type( $value ) {
		$this->queued_filters['rims']['type'][] = $value;
	}

	/**
	 * $value I think would be 'replica' or ''
	 *
	 * @param $value
	 */
	protected function add_rim_style( $value ) {
		$this->queued_filters['rims']['style'][] = $value;
	}

	/**
	 * @param $v
	 */
	protected function add_rim_color_1( $value ) {
		$this->queued_filters['rims']['color_1'][] = $value;
	}

	/**
	 * @param $value
	 */
	protected function add_rim_color_2( $value ) {
		$this->queued_filters['rims']['color_2'][] = $value;
	}

	/**
	 * @param $v
	 */
	protected function add_rim_finish( $value ) {
		$this->queued_filters['rims']['finish'][] = $value;
	}

	/**
	 * @param $value
	 */
	protected function add_tire_part_number( $value ) {
		$this->queued_filters['tires']['part_number'][] = $value;
	}

	/**
	 * @param $value
	 */
	protected function add_tire_part_number_not( $value ) {
		$this->queued_filters['tires']['part_number_not'][] = $value;
	}

	/**
	 * @param $value
	 */
	protected function add_tire_brand( $value ) {
		// add to 2 places, let the specific query object choose which one to use
		$this->queued_filters['tires']['brand'][] = $value;
		$this->queued_filters['tire_brands']['slug'][] = $value;
	}

	/**
	 * @param $value
	 */
	protected function add_tire_model( $value ) {
		// add to 2 places, let the specific query object choose which one to use
		$this->queued_filters['tires']['model'][] = $value;
		$this->queued_filters['tire_models']['slug'][] = $value;
	}

	/**
	 * @param $value
	 */
	protected function add_tire_type( $value ) {
		// tires don't have types, they have models, which have types
		$this->queued_filters['tire_models']['type'][] = $value;
	}

	/**
	 * @param $value
	 */
	protected function add_tire_class( $value ) {
		$this->queued_filters['tire_models']['class'][] = $value;
	}

	/**
	 * @param $value
	 */
	protected function add_tire_category( $value ) {
		$this->queued_filters['tire_models']['category'][] = $value;
	}

	/**
	 * @param $value
	 */
	protected function add_tire_speed_rating( $value ) {
		$this->queued_filters[ 'tires' ][ 'speed_rating' ][] = $value;
	}

	/**
	 * @param $value
	 */
	protected function add_tire_load_index_min( $value ) {
		$this->queued_filters[ 'tires' ][ 'load_index_min' ][] = $value;
	}

}