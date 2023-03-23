<?php

/**
 * @param       $sizes
 * @param       $userdata
 * @param array $args
 * @return array
 */
function query_tires_by_sizes( $sizes, $userdata, $args = array() ) {

	$query = new Tires_Query_Fitment_Sizes( $args );
	$query->sizes = $sizes; // object will validate

	$query->queue_filter( $userdata, 'sort', 'add_sort' );

	// tire models, tire brands, and tire specific filters, but not tire sizing filters, those are in $sizes
	$query->queue_filter( $userdata, 'brand', 'add_tire_brand', true );
	$query->queue_filter( $userdata, 'model', 'add_tire_model', true );
	$query->queue_filter( $userdata, 'type', 'add_tire_type', true );
	$query->queue_filter( $userdata, 'class', 'add_tire_class', true );
	$query->queue_filter( $userdata, 'category', 'add_tire_category', true );
	$query->queue_filter( $userdata, 'speed_rating', 'add_tire_speed_rating', true );
	$query->queue_filter( $userdata, 'load_index', 'add_tire_load_index_min', true );

	// for staggered
	$query->queue_filter( $userdata, 'price', 'add_price_range', true );

	// for non staggered
	$query->queue_filter( $userdata, 'price_each', 'add_price_range_each', true );

	$query->queue_filter( $userdata, 'part_number_front', 'add_part_number_front' );
	$query->queue_filter( $userdata, 'part_number_rear', 'add_part_number_rear' );

	$ret = $query->get_results();
	return $ret;
}

/**
 * Should whitelist $userdata for this. The function allows to filter by a lot more
 * parameters than we normally want to filter by...
 *
 * @param       $userdata
 * @param array $args
 * @return array
 */
function query_tires_general( $userdata, $args = array() ) {

	$query = new Tires_Query_General( $args );

	$query->setup_pagination_attributes( $userdata );

	$query->queue_filter( $userdata, 'sort', 'add_sort' );

	// tires
	$query->queue_filter( $userdata, 'speed_rating', 'add_tire_speed_rating', true );
	$query->queue_filter( $userdata, 'load_index', 'add_tire_load_index_min', true );
	$query->queue_filter( $userdata, 'part_number', 'add_tire_part_number', true );
	$query->queue_filter( $userdata, 'part_number_not_in', 'add_tire_part_number_not', true );

	// models
	$query->queue_filter( $userdata, 'model', 'add_tire_model', true );
	$query->queue_filter( $userdata, 'type', 'add_tire_type', true );
	$query->queue_filter( $userdata, 'class', 'add_tire_class', true );
	$query->queue_filter( $userdata, 'category', 'add_tire_category', true );

	// brands
	$query->queue_filter( $userdata, 'brand', 'add_tire_brand', true );

	return $query->get_results();
}

/**
 * @param       $userdata
 * @param array $args
 * @return array
 */
function query_tires_grouped_by_model( $userdata, $args = array() ){

	$query = new Tires_Query_Grouped( $args );

	$query->setup_pagination_attributes( $userdata );

	$query->queue_filter( $userdata, 'sort', 'add_sort' );

	// tire models, tire brands only
	// no tire specific, tire size specific, or price filters
	// rows of results are not for specific part numbers, they are for tire models
	$query->queue_filter( $userdata, 'brand', 'add_tire_brand', true );
	$query->queue_filter( $userdata, 'model', 'add_tire_model', true );
	$query->queue_filter( $userdata, 'type', 'add_tire_type', true );
	$query->queue_filter( $userdata, 'class', 'add_tire_class', true );
	$query->queue_filter( $userdata, 'category', 'add_tire_category', true );

	return $query->get_results();
}

/**
 * @param $type - 'summer', 'winter', 'all-season', 'all-weather'
 * @return bool
 */
function tire_type_is_valid( $type ) {
	return in_array( $type, array_keys( Static_Array_Data::tire_model_types() ) );
}

/**
 * See also: @param $raw
 * @return false
 * @var Wheel_Set::get_size_array()
 *
 * Return value of this and above must be the same.
 *
 * This will not likely validate that all sizes are present and valid values, that
 * is something that our query functions should do once the data is fed into them.
 *
 */
function get_tire_size_array_from_userdata( $raw ) {

	$diameter = gp_if_set( $raw, 'diameter' );
	$diameter = gp_force_singular( $diameter );

	$profile = gp_if_set( $raw, 'profile' );
	$profile = gp_force_singular( $profile );

	$width = gp_if_set( $raw, 'width' );
	$width = gp_force_singular( $width );

	$dd = gp_comma_string_to_array( $diameter, '-', true );
	$ww = gp_comma_string_to_array( $width, '-', true );
	$pp = gp_comma_string_to_array( $profile, '-', true );

	if ( count( $dd ) === 1 && count( $pp ) === 1 && count( $ww ) === 1 ) {

		$size['staggered'] = false;
		$size['tires']['universal'] = array(
			'diameter' => gp_test_input( $dd[0] ),
			'width' => gp_test_input( $ww[0] ),
			'profile' => gp_test_input( $pp[0] ),
		);

	} else if ( count( $dd ) === 2 && count( $pp ) === 2 && count( $ww ) === 2 ) {

		$size['staggered'] = true;
		$size['tires']['front'] = array(
			'diameter' => gp_test_input( $dd[0] ),
			'width' => gp_test_input( $ww[0] ),
			'profile' => gp_test_input( $pp[0] ),
		);
		$size['tires']['rear'] = array(
			'diameter' => gp_test_input( $dd[1] ),
			'width' => gp_test_input( $ww[1] ),
			'profile' => gp_test_input( $pp[1] ),
		);

	} else{
		return false;
	}

	return $size;
}