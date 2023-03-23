<?php

/**
 * Class Rims_Query_Fitment_Sizes
 */
Final Class Rims_Query_Fitment_Sizes extends Product_Query_Filter_Methods {

	/**
	 * Array of sizes.
	 *
	 * @var array
	 */
	public $sizes;

	/**
	 * Rims_Query_Fitment_Sizes constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		$this->uid = 'rq_fitment';
		parent::__construct( $args );
		$this->sizes = array();

		// dont even know if we need these now.
		$this->staggered_result    = true;
		$this->result_has_tires    = false;
		$this->result_has_rims     = true;
		$this->sizes_use_sql_union = true;
	}

	/**
	 * @param $size
	 *
	 * @return array
	 */
	public function get_staggered_rims_by_size( $size ) {

		if ( ! validate_rim_size_array( $size ) ) {
			throw_dev_error( 'Invalid rim size' );
		}

		$db     = get_database_instance();
		$params = array();

		// $size array validated, so we can skip isset() checks
		$staggered = $size[ 'staggered' ];
		$oem       = gp_if_set( $size, 'oem' );
		$r1_size   = $staggered ? $size[ 'rims' ][ 'front' ] : $size[ 'rims' ][ 'universal' ];
		$r2_size   = $staggered ? $size[ 'rims' ][ 'rear' ] : null;

		// *** BEGIN SELECT ***
		$select = array();

		// do not do this:
		// $select[] = '*';

		// inject fitment slug / sub slug
		$fs_param = 'fitment_slug_' . (int) $this->count();
		$ss_param = 'sub_slug_' . (int) $this->count();

		$select[] = ':' . $fs_param . ' AS fitment_slug';
		$params[] = array( $fs_param, gp_if_set( $size, 'fitment_slug', '' ), '%s' );
		$select[] = ':' . $ss_param . ' AS sub_slug';
		$params[] = array( $ss_param, gp_if_set( $size, 'sub_slug', '' ), '%s' );

		if ( $oem ) {
			$select[] = '"1" AS oem';
		} else {
			$select[] = '"" AS oem';
		}

		if ( $staggered ) {

			$select[] = '"1" AS staggered';

			// part_numbers plural this was a group concat don't turn this on...
//			$select[] = 'front_rims.part_numbers AS front_part_numbers';
//			$select[] = 'rear_rims.part_numbers AS rear_part_numbers';

			// total price
			if ( app_is_locale_canada_otherwise_force_us() ) {
				$select[] = '(( front_rims.price_ca + rear_rims.price_ca ) * 2) AS total_price';
			} else {
				$select[] = '(( front_rims.price_us + rear_rims.price_us ) * 2) AS total_price';
			}

			$select[] = DB_Rim::prefix_alias_select( 'front_rims', 'r1_' );
			$select[] = DB_Rim::prefix_alias_select( 'rear_rims', 'r2_' );

		} else {

			$select[] = '"" AS staggered';

			// part_numbers plural this was a group concat don't turn this on...
			// $select[] = 'front_rims.part_numbers AS front_part_numbers';

			// total price
			if ( app_is_locale_canada_otherwise_force_us() ) {
				$select[] = '(( front_rims.price_ca ) * 4) AS total_price';
			} else {
				$select[] = '(( front_rims.price_us ) * 4) AS total_price';
			}

			// select front rims twice, the second time prefixed as if they are the rear
			$select[] = DB_Rim::prefix_alias_select( 'front_rims', 'r1_' );
			$select[] = DB_Rim::prefix_alias_select( 'front_rims', 'r2_' );
		}

		// select only front brand/model/finish, because this will be the same as the rear
		$select[] = DB_Rim_Brand::prefix_alias_select( 'r1_brands', 'rb1_' );
		$select[] = DB_Rim_Model::prefix_alias_select( 'r1_models', 'rm1_' );
		$select[] = DB_Rim_Finish::prefix_alias_select( 'r1_finishes', 'rf1_' );


		// BEGIN SQL
		$sql = '';
		$sql .= 'SELECT ' . implode_comma( $select ) . ' ';
		$sql .= 'FROM ( SELECT "dummy" FROM DUAL ) AS nothing ';

		// ******** INNER JOIN FRONT RIMS ********

		// apply all filters to front rims
		$front_rim_filters = gp_if_set( $this->queued_filters, 'rims', array() );

		// possibly override part number
		if ( isset( $this->queued_filters[ 'part_numbers' ][ 'front' ] ) ) {
			$front_rim_filters[ 'part_number' ] = $this->queued_filters[ 'part_numbers' ][ 'front' ];
		}

		$join_r1 = $this->select_best_fit_rim_in_each_finish( $r1_size, $front_rim_filters );
		$sql     .= 'INNER JOIN (' . $join_r1[ 0 ] . ') AS front_rims ON 1 = 1 ';;
		$params = array_merge( $params, $join_r1[ 1 ] );

		// ******** INNER JOIN REAR RIMS ********
		if ( $staggered ) {

			// dont apply any filters to rear rims. all possible filters (model, brand, finish, color_1, color_2, type)
			// are redundant because we join these on the same values for the front rims
			// update: we added a 'style' filter which.. may or may not abide by the statement above
			$rear_rim_filters = array();

			// possibly override part number
			if ( isset( $this->queued_filters[ 'part_numbers' ][ 'rear' ] ) ) {
				$rear_rim_filters[ 'part_number' ] = $this->queued_filters[ 'part_numbers' ][ 'rear' ];
			}

			$join_r2 = $this->select_best_fit_rim_in_each_finish( $r2_size, $rear_rim_filters );

			/**
			 * Warning: @see staggered-package-multi-size-query.php (2 places should (probably) be the same)
			 */
			$join_rear_rims_conditions = array(
				// 'front_rims.rim_id <> rear_rims.rim_id', // I think this should definitely be turned off
				'front_rims.model_id = rear_rims.model_id',
				'front_rims.finish_id = rear_rims.finish_id',
				'front_rims.type = rear_rims.type',
				'front_rims.style = rear_rims.style',
			);

			$sql .= 'INNER JOIN (' . $join_r2[ 0 ] . ') AS rear_rims ON ' . implode( ' AND ', $join_rear_rims_conditions ) . ' ';;
			$params = array_merge( $params, $join_r2[ 1 ] );
		}

		// *** Rim Models ***
		$sql .= 'INNER JOIN rim_models AS r1_models ON r1_models.rim_model_id = front_rims.model_id ';

		// *** Rim Brands ***
		$sql .= 'INNER JOIN rim_brands AS r1_brands ON r1_brands.rim_brand_id = front_rims.brand_id ';

		// *** Rim Finishes ***
		$sql .= 'INNER JOIN rim_finishes AS r1_finishes ON r1_finishes.rim_finish_id = front_rims.finish_id ';

		$sql .= 'WHERE 1 = 1 ';

		$sql .= 'GROUP BY r1_part_number ';

		return array( $sql, $params );
	}

	/**
	 * @param $sizes
	 *
	 * @return array
	 * @throws Exception
	 */
	public function get_sizes_unioned( $sizes, $union_all = false ) {

		// $group_by_part_number = gp_if_set( $this->args, 'group_by_part_number', true );

		$unions = array();
		$params = array();
		$sql    = '';

		// Loop to get Union conditions
		if ( $sizes && is_array( $sizes ) ) {
			foreach ( $sizes as $size ) {
				$_u = $this->get_staggered_rims_by_size( $size );
				if ( $_u ) {
					$unions[] = $_u[ 0 ];
					$params   = array_merge( $params, $_u[ 1 ] );
				} else {
					throw new Exception( 'Invalid rim size [1]' );
				}
			}
		}

		$union_str = $union_all ? 'UNION ALL' : 'UNION';
		$sql       .= '( ' . implode( ' ) ' . $union_str . ' ( ', $unions ) . ' )';

		return array( $sql, $params );
	}

	/**
	 * @return array
	 */
	public function get_results() {

		// prevent exception in case this isn't checked earlier
		if ( ! $this->sizes ) {
			return array();
		}

		// this must default to true.
		// update: its unclear to me if setting this to false even does anything. sorry :(
		$group_by_part_number = gp_if_set( $this->args, 'group_by_part_number', true );
		$union_all            = $group_by_part_number ? false : true;

		$db     = get_database_instance();
		$params = array();

		$sql    = '';
		$select = array();

		// get everything form the unions
		$select[] = '*';

		// selecting group concat also groups items so we can't do this
		if ( $group_by_part_number ) {
			// DO NOT do group_concat if not specifying a group by below
			// I don't remember or even care to check the lines below to see if that is the case
			// basically, just leave this off, that's all.
			//			$select[] = 'GROUP_CONCAT(fitment_slug) AS fitment_slugs';
			//			$select[] = 'GROUP_CONCAT(r1_part_number) AS r1_part_numbers';
			//			$select[] = 'GROUP_CONCAT(r2_part_number) AS r2_part_numbers';
		}

		// Select
		$sql .= $this->get_select_from_array( $select ) . ' ';

		// Union each size - this is where the majority of the logic is done (inside each union)
		$unions = $this->get_sizes_unioned( $this->sizes, $union_all );
		$sql    .= 'FROM ( ' . $unions[ 0 ] . ' ) AS all_data ';
		$params = array_merge( $params, $unions[ 1 ] );

		// apply filters or other data
		$sql .= 'WHERE 123 = 123 ';

		// may occur for singular staggered fitment
		$price_range_component = $this->get_price_range_component( 'total_price' );
		if ( $price_range_component ) {
			$sql    .= 'AND ' . $price_range_component->sql_with_placeholders() . ' ';
			$params = array_merge( $params, $price_range_component->parameters_array() );
		}

		// the price column in the database depends on our locale.
		// when using the total_price, this is a derived column which already takes care of can/u.s.
		$price_col = 'r1_' . DB_Rim::get_price_column( $this->locale );

		// may occur for singular non-staggered fitment
		$price_range_component_each = $this->get_price_range_component_each( $price_col );
		if ( $price_range_component_each ) {
			$sql    .= 'AND ' . $price_range_component_each->sql_with_placeholders() . ' ';
			$params = array_merge( $params, $price_range_component_each->parameters_array() );
		}

		if ( $group_by_part_number ) {
			$sql .= 'GROUP BY r1_part_number, r2_part_number ';
		}

		$order_by = '';
		$sort     = gp_if_set( $this->queued_filters, 'sort', '' );
		switch ( $sort ) {
			case 'single_product_page':
				$order_by = array(
					'r1_diameter',
					'r1_width',
					'r1_offset',
					'r1_center_bore',
					$price_col,
					// finish should be redundant here but w/e
					'r1_finish_id',
					'r1_rim_id',
				);
				break;
			case 'brand':
				$order_by = array(
					'r1_brand_slug',
					'r1_model_slug',
					'r1_color_1',
					'r1_color_2',
					'r1_finish',
					'total_price',
					'r1_rim_id',
				);
				break;
			case 'model':
				$order_by = array(
					'r1_model_slug',
					'r1_brand_slug',
					'r1_color_1',
					'r1_color_2',
					'r1_finish',
					'total_price',
					'r1_rim_id',
				);
				break;
			case 'price':
			default:
				$order_by = array(
					'total_price',
					'r1_brand_slug',
					'r1_model_slug',
					'r1_color_1',
					'r1_color_2',
					'r1_finish',
					'r1_rim_id',
				);
				break;
		}

		$sql .= $this->get_order_by_clause( $order_by ) . ' ';

		$sql .= $this->get_limit_clause( $params ) . ' ';

		$sql .= ';'; // end

		// $db->print_next_query_results();

		$results = $db->get_results( $sql, $params );

		return $results;
	}

	/**
	 * Feed each row of the query into here to get objects back..
	 *
	 * @param $row
	 */
	public static function parse_row( $row ) {

		$staggered = gp_if_set( $row, 'staggered' );

		$front  = collect_keys_with_prefix( $row, DB_Rim::get_fields(), 'r1_' );
		$model  = collect_keys_with_prefix( $row, DB_Rim_Model::get_fields(), 'rm1_' );
		$brand  = collect_keys_with_prefix( $row, DB_Rim_Brand::get_fields(), 'rb1_' );
		$finish = collect_keys_with_prefix( $row, DB_Rim_Finish::get_fields(), 'rf1_' );

		$model_obj  = DB_Rim_Model::create_instance_or_null( $model );
		$brand_obj  = DB_Rim_Brand::create_instance_or_null( $brand );
		$finish_obj = DB_Rim_Finish::create_instance_or_null( $finish );

		$front_object = DB_Rim::create_instance_or_null( $front, array(
			'model' => $model_obj,
			'brand' => $brand_obj,
			'finish' => $finish_obj,
		) );

		$ret[ 'raw_data' ]     = $row;
		$ret[ 'oem' ]          = gp_if_set( $row, 'oem' );
		$ret[ 'staggered' ]    = ( $staggered );
		$ret[ 'fitment_slug' ] = gp_if_set( $row, 'fitment_slug' );
		$ret[ 'sub_slug' ]     = gp_if_set( $row, 'sub_slug' );
		$ret[ 'total_price' ]  = gp_if_set( $row, 'total_price' );
		$ret[ 'front' ]        = $front_object;

		if ( $staggered ) {

			$rear = collect_keys_with_prefix( $row, DB_Rim::get_fields(), 'r2_' );

			$rear_object = DB_Rim::create_instance_or_null( $rear, array(
				'model' => $model_obj,
				'brand' => $brand_obj,
				'finish' => $finish_obj,
			) );

			$ret[ 'rear' ] = $rear_object;
		}

		return $ret;
	}
}