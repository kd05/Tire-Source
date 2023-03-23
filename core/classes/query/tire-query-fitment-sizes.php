<?php

/**
 * Class Tire_Query
 */
Final Class Tires_Query_Fitment_Sizes extends Product_Query_Filter_Methods {

	/**
	 * @var array
	 */
	public $sizes;

	/**
	 * TQ constructor.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );

		$this->sizes = array();

		// maybe dont need these now
		$this->staggered_result    = true;
		$this->result_has_tires    = true;
		$this->result_has_rims     = false;
		$this->sizes_use_sql_union = true;
	}

	/**
	 * @param $sizes
	 */
	public function get_sizes_unioned( $sizes, $union_all = false ) {

		$unions = array();
		$params = array();
		$sql    = '';

		// Loop to get Union conditions
		if ( $sizes && is_array( $sizes ) ) {
			foreach ( $sizes as $size ) {
				$_u = $this->get_staggered_tires_by_size( $size );
				if ( $_u ) {
					$unions[] = $_u[ 0 ];
					$params   = array_merge( $params, $_u[ 1 ] );
				} else {
					throw new Exception( 'Invalid tire size [1]' );
				}
			}
		}

		$union_str = $union_all ? 'UNION ALL' : 'UNION';
		$sql       .= '( ' . implode( ' ) ' . $union_str . ' ( ', $unions ) . ' )';

		return array( $sql, $params );
	}

	/**
	 * @param $size
	 */
	public function get_staggered_tires_by_size( $size ) {

		if ( ! validate_tire_size_array( $size ) ) {
			throw_dev_error( 'Invalid tire size array: ' . get_var_dump( $size ) );
		}

		$db     = get_database_instance();
		$params = array();

		$staggered = $size[ 'staggered' ];
		$oem       = gp_if_set( $size, 'oem' );
		$t1_size   = $staggered ? $size[ 'tires' ][ 'front' ] : $size[ 'tires' ][ 'universal' ];
		$t2_size   = $staggered ? $size[ 'tires' ][ 'rear' ] : null;

		// Tires Filters (queued)
		$t1_filters = gp_if_set( $this->queued_filters, 'tires', array() );
		$t2_filters = $t1_filters;

		// maybe override existing part number filter (note: for single tires page)
		if ( isset( $this->queued_filters[ 'part_numbers' ][ 'front' ] ) ) {
			$t1_filters[ 'part_number' ] = $this->queued_filters[ 'part_numbers' ][ 'front' ];
		}

		// maybe override existing part number filter (note: for single tires page)
		if ( isset( $this->queued_filters[ 'part_numbers' ][ 'rear' ] ) ) {
			$t2_filters[ 'part_number' ] = $this->queued_filters[ 'part_numbers' ][ 'rear' ];
		}

		// Front Tires
		$comp_t1 = new Query_Components_Tires( 'front_tires', false );
		$comp_t1->apply_filter( $t1_filters, 'part_number', 'get_part_number', true );
		$comp_t1->apply_filter( $t1_filters, 'brand', 'get_brand', true );
		$comp_t1->apply_filter( $t1_filters, 'model', 'get_model', true );

		// size specifies these already, but if user also adds filters, then we need to filter by both..
		// even though it may be redundant, like.. load_index > 100 AND load_index > 90
		$comp_t1->apply_filter( $t1_filters, 'load_index_min', 'get_load_index_min' );
		$comp_t1->apply_filter( $t1_filters, 'speed_rating', 'get_speed_rating', true );
		$comp_t1->builder->add_to_self( DB_Tire::sql_assert_sold_and_not_discontinued_in_locale( 'front_tires', $this->locale ), [] );

		// putting size last *may* let sql do simpler conditions first
		$comp_t1->builder->add_to_self( $comp_t1->get_size( $t1_size ) );

		// Rear Tires
		$comp_t2 = new Query_Components_Tires( 'rear_tires', false );

		if ( $staggered ) {
			$comp_t2->builder->add_to_self( $comp_t2->get_size( $t2_size ) );

			$comp_t2->apply_filter( $t2_filters, 'part_number', 'get_part_number', true );

			// dont need to do brand or model for rear (they are same as front)
			$comp_t2->apply_filter( $t2_filters, 'load_index_min', 'get_load_index_min', true );
			$comp_t2->apply_filter( $t2_filters, 'speed_rating', 'get_speed_rating', true );
			$comp_t2->builder->add_to_self( DB_Tire::sql_assert_sold_and_not_discontinued_in_locale( 'rear_tires', $this->locale ), [] );
		}

		// *** BEGIN SELECT ***
		$select = array();
		// $select[] = '*'; // don't do this

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

			if ( app_is_locale_canada_otherwise_force_us() ) {
				$select[] = '( ( front_tires.price_ca + rear_tires.price_ca ) * 2) AS total_price';
			} else {
				$select[] = '( ( front_tires.price_us + rear_tires.price_us ) * 2) AS total_price';
			}

			$select[] = DB_Tire::prefix_alias_select( 'front_tires', 't1_' );
			$select[] = DB_Tire::prefix_alias_select( 'rear_tires', 't2_' );

		} else {

			$select[] = '"" AS staggered';

			if ( app_is_locale_canada_otherwise_force_us() ) {
				$select[] = '( ( front_tires.price_ca ) * 4 ) AS total_price';
			} else {
				$select[] = '( ( front_tires.price_us ) * 4 ) AS total_price';
			}

			// select front tires twice, the second time prefixed as if they are the rear
			$select[] = DB_Tire::prefix_alias_select( 'front_tires', 't1_' );
			$select[] = DB_Tire::prefix_alias_select( 'front_tires', 't2_' );
		}

		// select brand and model (remember these are the same for both front and rear tires)
		$select[] = DB_Tire_Brand::prefix_alias_select( 't1_brands', 'tb1_' );
		$select[] = DB_Tire_Model::prefix_alias_select( 't1_models', 'tm1_' );


		// *** BEGIN SQL ***
		$sql = '';
		$sql .= 'SELECT ' . implode_comma( $select ) . ' ';
		$sql .= 'FROM ( SELECT "dummy" FROM DUAL ) AS nothing ';

		// ******** INNER JOIN FRONT TIRES********
		$sql .= 'INNER JOIN ' . $db->tires . ' AS front_tires ON ' . $comp_t1->builder->sql_with_placeholders() . ' ';

		$params = array_merge( $params, $comp_t1->builder->parameters_array() );
		// ******** end INNER JOIN FRONT TIRES********

		// ******** INNER JOIN REAR TIRES********
		if ( $staggered ) {

			// additional conditions for joining rear tires onto front tires
			$comp_t2->builder->add_to_self( array(
				'front_tires.model_id = rear_tires.model_id',
			) );

			$sql    .= 'INNER JOIN ' . $db->tires . ' AS rear_tires ON ' . $comp_t2->builder->sql_with_placeholders() . ' ';
			$params = array_merge( $params, $comp_t2->builder->parameters_array() );
		}
		// ******** end JOIN REAR TIRES********

		// *** Tire Models ***
		$tire_model_filters = gp_if_set( $this->queued_filters, 'tire_models' );

		$comp_t1_models = new Query_Components_Tire_Models( 't1_models', false );
		// front model = rear model
		$comp_t1_models->builder->add_to_self( 't1_models.tire_model_id = front_tires.model_id' );
		$comp_t1_models->apply_filter( $tire_model_filters, 'type', 'get_type', true);
		$comp_t1_models->apply_filter( $tire_model_filters, 'class', 'get_class', true );
		$comp_t1_models->apply_filter( $tire_model_filters, 'category', 'get_category', true );

		// *** Tire Models ***
		$sql    .= 'INNER JOIN tire_models AS t1_models ON ' . $comp_t1_models->builder->sql_with_placeholders() . ' ';
		$params = array_merge( $params, $comp_t1_models->builder->parameters_array() );

		// *** Tire Brands ***
		$sql .= 'INNER JOIN tire_brands AS t1_brands ON t1_brands.tire_brand_id = front_tires.brand_id ';

		// WHERE CLAUSE
		$sql .= 'WHERE 9 = 9 ';

		// ** Speed Rating / Load Index / only for non-winter tires **
		// note: this logic is more or less repeated in staggered-package-multi-size-query.php

		$t1 = new Query_Components_Tires( 'front_tires', false );
		$t2 = new Query_Components_Tires( 'rear_tires', false );

		/**
		 * Speed Rating Conditions.
		 *
		 * *** WARNING *** : Very similar code in 4 places. Must do the same thing in each place, otherwise, bad things
		 * could happen! This is especially true in package queries. If the logic does not line
		 * up with the logic in Vehicle_Queries_PHP then the packages page can show no results
		 * when in fact there are many packages that should be showing.
		 *
		 * Unfortunately, this is just not easy to generalize into one function at this time.
		 *
		 * If we did, it would need parameters such as: front/rear tires table name, front/rear tire models table name,
		 * speed rating 1, speed rating 2, is staggered.
		 *
		 * Note: vehicle data often does not specify speed rating. In this case, we do not need to enforce a minimum.
		 *
		 * @see Vehicle_Queries_PHP::select_staggered_tires_ordered_by_package_best_recommended()
		 * @see Vehicle_Queries_PHP::select_not_staggered_tires_ordered_by_package_best_recommended()
		 * @see Staggered_Package_Multi_Size_Query::get_staggered_package_size()
		 * @see Tires_Query_Fitment_Sizes::get_staggered_tires_by_size()
		 */
		$speed_rating_1 = gp_if_set( $t1_size, 'speed_rating' );
		$speed_rating_2 = gp_if_set( $t2_size, 'speed_rating' );
		$speed_rating_2 = $speed_rating_2 ? $speed_rating_2 : $speed_rating_1;

		if ( $speed_rating_1 ) {
			$t1->builder->add_to_self( $t1->get_speed_rating_min_or_type_winter( $speed_rating_1, 't1_models' ) );
		}

		if ( $staggered && $speed_rating_2 ) {
			$t2->builder->add_to_self( $t2->get_speed_rating_min_or_type_winter( $speed_rating_2, 't1_models' ) );
		}

		// add sql (or a bunch of (AND 1 = 1)'s)
		$sql .= 'AND ' . $t1->builder->sql_with_placeholders() . ' ';
		$sql .= 'AND ' . $t2->builder->sql_with_placeholders() . ' ';

		$params = array_merge( $params, $t1->builder->parameters_array() );
		$params = array_merge( $params, $t2->builder->parameters_array() );

		// show all combinations of part numbers, even if many exist with the same brand/model,
		// they could have different speed ratings and load index and this is fine,
		// I think we should still show all of them.
		// update: the above sentence might sound wrong. the important part is that we're grouping
		// by the part numbers and not the model. actually... i think grouping
		// by part numbers is redundant but its hard to understand for sure when
		// dealing with staggered fitments.
		$sql .= 'GROUP BY t1_part_number, t2_part_number ';

		$sql .= ''; // don't end this with semi colon

		return array( $sql, $params );
	}

	/**
	 *
	 */
	public function get_results() {

		// prevent exception in case this isn't checked earlier
		if ( ! $this->sizes ) {
			return array();
		}

		// this must default to true.
		// update: this confuses me. i think its only purpose (might be?) for staggered fitments
		// that have the same tire size on front/rear, which i dont even know if this exists.
		// and if it does, i dont even know that grouping by respects the order in this manner.
		// basically.. don't remove this because it may serve a purpose and it might not..
		// it was written long before i added this comment. p.s. it is also used with rims where
		// it may have a different meaning.
		$group_by_part_number = gp_if_set( $this->args, 'group_by_part_number', true );
		$union_all            = $group_by_part_number ? false : true;

		$db     = get_database_instance();
		$params = array();

		$sql    = '';
		$select = array();

		// get everything from the unions
		$select[] = '*';

		// update: I believe this does actually needs to be off for sure
		if ( $group_by_part_number ) {
			// just not sure we need these or if its possible that they could cause issues
			//			$select[] = 'GROUP_CONCAT(fitment_slug) AS fitment_slugs';
			//			$select[] = 'GROUP_CONCAT(t1_part_number) AS t1_part_numbers';
			//			$select[] = 'GROUP_CONCAT(t2_part_number) AS t2_part_numbers';
		}

		// Select
		$sql .= $this->get_select_from_array( $select ) . ' ';

		// Union each size - this is where the majority of the logic is done (inside each union)
		$unions = $this->get_sizes_unioned( $this->sizes, $union_all );
		$sql    .= 'FROM ( ' . $unions[ 0 ] . ' ) AS all_data ';
		$params = array_merge( $params, $unions[ 1 ] );

		// apply filters or other data
		$sql .= 'WHERE 321 = 321 ';

		// may occur for singular staggered fitment
		$price_range_component = $this->get_price_range_component( 'total_price' );
		if ( $price_range_component ) {
			$sql    .= 'AND ' . $price_range_component->sql_with_placeholders() . ' ';
			$params = array_merge( $params, $price_range_component->parameters_array() );
		}

		// price_ca or price_us
		$price_col = 't1_' . DB_Tire::get_price_column( $this->locale );

		// may occur for singular non-staggered fitment
		$price_range_component_each = $this->get_price_range_component_each( $price_col );
		if ( $price_range_component_each ) {
			$sql    .= 'AND ' . $price_range_component_each->sql_with_placeholders() . ' ';
			$params = array_merge( $params, $price_range_component_each->parameters_array() );
		}

		//  might be redundant to do UNION and not UNION ALL
		if ( $group_by_part_number ) {
			$sql .= 'GROUP BY t1_part_number, t2_part_number ';
		}

		$order_by = '';
		$sort     = gp_if_set( $this->queued_filters, 'sort', '' );
		switch ( $sort ) {
			case 'single_product_page':
				$order_by = array(
					't1_diameter',
					't1_profile',
					't1_width',
					't1_size',
					't1_load_index',
					't1_speed_rating',
					$price_col,
					't1_tire_id',
				);
				break;
			case 'brand':
				$order_by = array(
					't1_brand_slug',
					't1_model_slug',
					'total_price',
					't1_tire_id',
				);
				break;
			case 'model':
				$order_by = array(
					't1_model_slug',
					't1_brand_slug',
					'total_price',
					't1_tire_id',
				);
				break;
			case 'price':
				$order_by = array(
					'total_price',
					't1_brand_slug',
					't1_model_slug',
					't1_tire_id',
				);
				break;
			default:
				$order_by = array(
					'total_price',
					't1_brand_slug',
					't1_model_slug',
					't1_tire_id',
				);
				break;
		}

		$sql .= $this->get_order_by_clause( $order_by ) . ' ';

		$sql .= $this->get_limit_clause( $params ) . ' ';

		$sql .= ';'; // end

		return $db->get_results( $sql, $params );
	}

	/**
	 * Feed each row of the query into here to get objects back..
	 *
	 * @param $row
	 */
	public static function parse_row( $row ) {

		$staggered = gp_if_set( $row, 'staggered' );

		$front = collect_keys_with_prefix( $row, DB_Tire::get_fields(), 't1_' );
		$model = collect_keys_with_prefix( $row, DB_Tire_Model::get_fields(), 'tm1_' );
		$brand = collect_keys_with_prefix( $row, DB_Tire_Brand::get_fields(), 'tb1_' );

		// inject brand/model otherwise they will be queried again
		$front_object = DB_Tire::create_instance_or_null( $front, array(
			'model' => DB_Tire_Model::create_instance_or_null( $model ),
			'brand' => DB_Tire_Brand::create_instance_or_null( $brand ),
		) );

		$ret[ 'oem' ]          = gp_if_set( $row, 'oem' );
		$ret[ 'fitment_slug' ] = gp_if_set( $row, 'fitment_slug' );
		$ret[ 'sub_slug' ]     = gp_if_set( $row, 'sub_slug' );
		$ret[ 'total_price' ]  = gp_if_set( $row, 'total_price' );
		$ret[ 'staggered' ]    = ( $staggered );
		$ret[ 'raw_data' ]     = $row; // we need this too unfortunately
		$ret[ 'front' ]        = $front_object;

		if ( $staggered ) {

			$rear = collect_keys_with_prefix( $row, DB_Tire::get_fields(), 't2_' );

			$rear_object = DB_Tire::create_instance_or_null( $rear, array(
				'model' => DB_Tire_Model::create_instance_or_null( $model ),
				'brand' => DB_Tire_Brand::create_instance_or_null( $brand ),
			) );

			$ret[ 'rear' ] = $rear_object;

		}

		return $ret;
	}
}