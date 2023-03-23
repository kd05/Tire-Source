<?php

/**
 * @param       $sizes
 * @param       $userdata
 * @param array $args
 */
function query_packages_by_sizes( $sizes, $type, $userdata = array(), $args = array() ) {

	$query = new Staggered_Package_Multi_Size_Query( $args );

	// note: $sizes[0]['tires']['front']['part_number'] may exist
	// if we inject tire part numbers into the query to try to force a specific tire instead of
	// auto-choosing the least expensive one, then we need to put into the $sizes array
	$query->sizes = $sizes;

	// package type
	$query->set_package_type( $type );

	$query->setup_pagination_attributes( $userdata );

	if ( ! $type ) {
		throw_dev_error( 'Package queries require type to be set' );
	}

	$query->queue_filter( $userdata, 'price', 'add_price_range', true );
	$query->queue_filter( $userdata, 'sort', 'add_sort' );

	if ( $query->get_grouping_by_rims() ) {

		$query->queue_filter( $userdata, 'brand', 'add_rim_brand', true );
		$query->queue_filter( $userdata, 'finish', 'add_rim_finish', true );
		$query->queue_filter( $userdata, 'color_1', 'add_rim_color_1', true );
		$query->queue_filter( $userdata, 'color_2', 'add_rim_color_2', true );
		$query->queue_filter( $userdata, 'rim_style', 'add_rim_style', true );

	} else {

		$query->queue_filter( $userdata, 'brand','add_tire_brand', true );
		$query->queue_filter( $userdata, 'class', 'add_tire_class', true );
		$query->queue_filter( $userdata, 'category','add_tire_category', true );
		$query->queue_filter( $userdata, 'speed_rating', 'add_tire_speed_rating', true );
		$query->queue_filter( $userdata, 'load_index', 'add_tire_load_index_min', true );
	}

	return $query->get_results( $userdata );
}

/**
 * must contain sizes for front, and if the size is staggered, also for rear.
 *
 * must also have certain array indexes set to be valid.
 *
 * Note that a single array (for a package size for example) can return true
 * on both validate_tire_size_array() and validate_rim_size_array()
 *
 * @param $size
 */
function validate_tire_size_array( $size ){

	$req = [ 'diameter', 'width', 'profile' ];
	$staggered = gp_if_set( $size, 'staggered', false );
	$tires = gp_if_set( $size, 'tires', array() );
	$universal = gp_if_set( $tires, 'universal' );
	$front = gp_if_set( $tires, 'front', array() );
	$rear = gp_if_set( $tires, 'rear', array() );

	if ( $staggered ) {
		if ( ! array_keys_exist( $front, $req ) ) {
			return false;
		}
		if ( ! array_keys_exist( $rear, $req ) ) {
			return false;
		}
	} else {
		if ( ! array_keys_exist( $universal, $req ) ) {
			return false;
		}
	}

	return true;
}

/**
 * must contain sizes for front, and if the size is staggered, also for rear.
 *
 * must also have certain array indexes set to be valid.
 *
 * @param $size
 */
function validate_rim_size_array( $size ){

	$req = [ 'diameter', 'width', 'offset', 'bolt_pattern', 'center_bore' ];
	$staggered = gp_if_set( $size, 'staggered', false );
	$rims = gp_if_set( $size, 'rims', array() );
	$universal = gp_if_set( $rims, 'universal', array() );
	$front = gp_if_set( $rims, 'front', array() );
	$rear = gp_if_set( $rims, 'rear', array() );

	if ( $staggered ) {
		if ( ! array_keys_exist( $front, $req ) ) {
			return false;
		}
		if ( ! array_keys_exist( $rear, $req ) ) {
			return false;
		}
	} else {
		if ( ! array_keys_exist( $universal, $req ) ) {
			return false;
		}
	}

	return true;
}

/**
 * @param $size
 */
function validate_package_size_array( $size ) {
	$ret = validate_tire_size_array( $size ) && validate_rim_size_array( $size );
	return $ret;
}