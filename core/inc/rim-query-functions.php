<?php

/**
 * Each row contains both a front and a rear product, even if none of the sizes in the array
 * of $sizes are "staggered".  $sizes can be a mix of staggered and non-staggered.
 *
 * returned
 * @param $sizes
 * @param array $userdata
 * @param array $args
 * @return array
 */
function query_rims_by_sizes( $sizes, $userdata = array(), $args = array() ){

	$query = new Rims_Query_Fitment_Sizes( $args );

	$query->sizes = $sizes;

	$query->setup_pagination_attributes( $userdata );

	$query->queue_filter( $userdata, 'sort', 'add_sort' );
	$query->queue_filter( $userdata, 'price_each', 'add_price_range_each', true );
	$query->queue_filter( $userdata, 'brand', 'add_rim_brand', true );
	$query->queue_filter( $userdata, 'model', 'add_rim_model', true );
	$query->queue_filter( $userdata, 'color_1', 'add_rim_color_1', true );
	$query->queue_filter( $userdata, 'color_2',  'add_rim_color_2', true );
	$query->queue_filter( $userdata, 'finish', 'add_rim_finish', true );
	$query->queue_filter( $userdata, 'type', 'add_rim_type', true );
	$query->queue_filter( $userdata, 'rim_style', 'add_rim_style' );

	// for staggered
	$query->queue_filter( $userdata, 'price', 'add_price_range', true );

	// for non staggered
	$query->queue_filter( $userdata, 'price_each', 'add_price_range_each', true );

	$query->queue_filter( $userdata, 'part_number_front', 'add_part_number_front' );
	$query->queue_filter( $userdata, 'part_number_rear', 'add_part_number_rear' );

	return $query->get_results();
}

/**
 * Each row contains only one product. Products are NOT indexed by front/rear.
 * Query is grouped by part_number (or primary key of rims table) by default.
 *
 * USAGE: single rims page and I believe no where else.
 *
 * @param       $userdata
 * @param array $args
 *
 * @return array
 */
function query_rims_general( $userdata, $args = array() ) {

	// $userdata['color_2'] = '';

	$query = new Rims_Query_General( $args );

	$query->setup_pagination_attributes( $userdata );

	$query->queue_filter( $userdata, 'price', 'add_price_range', true );
	$query->queue_filter( $userdata, 'sort', 'add_sort' );

	$query->queue_filter( $userdata, 'brand', 'add_rim_brand', true );
	$query->queue_filter( $userdata, 'model', 'add_rim_model', true );

	/**
	 * Warning about these 3:
	 *
	 * sometimes you are going to want to make sure that $userdata['color_2'] and $userdata['finish'] are
	 * set to empty strings, because this is very different than null or not set at all.
	 *
	 * @see query_rims_by_finishes
	 */
	$query->queue_filter( $userdata, 'color_1', 'add_rim_color_1', true );
	$query->queue_filter( $userdata, 'color_2', 'add_rim_color_2', true );
	$query->queue_filter( $userdata, 'finish', 'add_rim_finish', true );

	$query->queue_filter( $userdata, 'type', 'add_rim_type', true );
	$query->queue_filter( $userdata, 'rim_style', 'add_rim_style' );

	// custom size attributes
	$query->queue_filter( $userdata, 'diameter', 'add_rim_diameter', true );
	$query->queue_filter( $userdata, 'width', 'add_rim_width', true );
	$query->queue_filter( $userdata, 'bolt_pattern', 'add_rim_bolt_pattern', true );
	$query->queue_filter( $userdata, 'offset_min', 'add_rim_offset_min', true );
	$query->queue_filter( $userdata, 'offset_max', 'add_rim_offset_max', true );
	$query->queue_filter( $userdata, 'hub_bore_min', 'add_rim_center_bore_min', true );
	$query->queue_filter( $userdata, 'hub_bore_max', 'add_rim_center_bore_max', true );

	// echo '<pre>' . print_r( $query->queued_filters, true ) . '</pre>';

	return $query->get_results();
}

/**
 *
 * Usage: Rims Archive Page with context.. by size or by brand ?
 *
 * @param $userdata
 * @param array $args
 * @return array
 */
function query_rims_grouped_by_finish( $userdata, $args = array() ) {

	$query = new Rims_Query_Grouped( $args );

	$query->setup_pagination_attributes( $userdata );

	$query->queue_filter( $userdata, 'price', 'add_price_range', true );
	$query->queue_filter( $userdata, 'sort', 'add_sort' );

	$query->queue_filter( $userdata, 'brand', 'add_rim_brand', true );
	$query->queue_filter( $userdata, 'model', 'add_rim_model', true );
	$query->queue_filter( $userdata, 'color_1', 'add_rim_color_1', true );
	$query->queue_filter( $userdata, 'color_2', 'add_rim_color_2', true );
	$query->queue_filter( $userdata, 'finish', 'add_rim_finish', true );
	$query->queue_filter( $userdata, 'type', 'add_rim_type', true );
	$query->queue_filter( $userdata, 'rim_style', 'add_rim_style' );

	// some of these filters might not do anything in Rims_Query_Grouped
	$query->queue_filter( $userdata, 'diameter', 'add_rim_diameter', true );
	$query->queue_filter( $userdata, 'width', 'add_rim_width', true );
	$query->queue_filter( $userdata, 'bolt_pattern', 'add_rim_bolt_pattern', true );
	$query->queue_filter( $userdata, 'offset_min', 'add_rim_offset_min', true );
	$query->queue_filter( $userdata, 'offset_max', 'add_rim_offset_max', true );
	$query->queue_filter( $userdata, 'hub_bore_min', 'add_rim_center_bore_min', true );
	$query->queue_filter( $userdata, 'hub_bore_max', 'add_rim_center_bore_max', true );

	return $query->get_results();
}

/**
 * If you want the query to ignore finishes for whatever reason, you must pass in strictly null for $c1, $c2, $ff
 *
 * @param        $sizes
 * @param        $brand
 * @param        $model
 * @param string $c1
 * @param string $c2
 * @param string $ff
 * @param array  $_userdata
 * @param array  $args
 *
 * @return array
 */
function query_rims_by_sizes_from_brand_model_finish( $sizes, $brand, $model, $c1 = '', $c2 = '', $ff = '', $_userdata = array(), $args = array() ) {

	$userdata = gp_make_array( $_userdata );

	$userdata['brand'] = $brand;
	$userdata['model'] = $model;

	// do NOT do this.. it could convert null to "" (technically it does not but... don't risk it)
	// $c1 = gp_test_input( $c1 );

	// null values are not the same as empty string
	if ( $c1 !== null ) {
		$userdata['color_1'] = $c1 ? $c1 : '';
	}

	if ( $c2 !== null ) {
		$userdata['color_2'] = $c2 ? $c2 : '';
	}

	if ( $ff !== null ) {
		$userdata['finish'] = $ff ? $ff : '';
	}

	return query_rims_by_sizes( $sizes, $userdata, $args );
}

/**
 * @param $brand
 * @param $model
 * @param $c1
 * @param $c2
 * @param $ff
 * @param array $userdata
 * @param array $args
 * @return array
 */
function query_rims_by_finishes( $brand, $model, $c1, $c2, $ff, $userdata = array(), $args = array() ) {

	$userdata = gp_force_array( $userdata );
	$userdata['brand'] = $brand;
	$userdata['model'] = $model;

	$userdata['color_1'] = $c1 ? $c1 : '';
	$userdata['color_2'] = $c2 ? $c2 : '';
	$userdata['finish'] = $ff ? $ff : '';

	return query_rims_general( $userdata, $args );
}

/**
 * The fn. below is not in use.
 *
 * I don't konw if we'll have a use for it.
 *
 * @see query_rims_by_finishes()
 *
 * @param       $brand
 * @param       $model
 * @param array $userdata
 * @param array $args
 *
 * @return array
 */
function query_rims_by_brand_model( $brand, $model, $userdata = array(), $args = array() ) {

	// userdata should already have colors/finish
	$userdata = gp_force_array( $userdata );
	$userdata['brand'] = $brand;
	$userdata['model'] = $model;

	return query_rims_general( $userdata, $args );
}


/**
 * legacy debugging thing
 *
 * @param $results
 */
function log_vehicle_query_summary_related_to_best_fit_rims( $results ) {

	$url = get_current_url();
	$count = $results ? count( $results ) : 0;
	$front_rim_ids = array();
	$rear_rim_ids = array();
	$finish_ids = array();

	if ( $results ) {
		foreach ( $results as $_r ) {

			$finish_ids[] = gp_if_set( $_r, 'r1_finish_id' );
			$front_rim_ids[] = gp_if_set( $_r, 'r1_rim_id' );

			// rear rims always set but sometimes to what the front are..
			if ( gp_if_set( $_r, 'staggered' ) ) {
				$rear_rim_ids[] = gp_if_set( $_r, 'r2_rim_id' );
			}
		}
	}

	// try to put same queries in same file...
	$uniq = string_half( md5( $url ) );

	log_data( [
		'u' => $url,
		'func' => gp_get_global( 'rim_query_best_fit_func', '?' ),
		'c' => $count,
		'ff' => $finish_ids,
		'r1' => $front_rim_ids,
		'r2' => $rear_rim_ids
	], 'rims-best-fit-' . $uniq, true, true, true );
}