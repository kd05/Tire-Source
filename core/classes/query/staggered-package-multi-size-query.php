<?php

/**
 * Note: this is the ONLY package query. It supports both staggered and
 * non-staggered fitments, as well as both single sized and multi-sizes queries.
 * So.. don't be confused by the name. It could just be called Package_Query.
 *
 * Class Staggered_Package_Multi_Size_Query
 */
Final Class Staggered_Package_Multi_Size_Query extends Product_Query_Filter_Methods {

	public $params;

	/** @var  DB_Tire|null */
	protected $tire_1;

	/** @var  DB_Tire|null */
	protected $tire_2;

	/** @var  DB_Rim|null */
	protected $rim_1;

	/** @var  DB_Rim|null */
	protected $rim_2;

	/** @var  boolean */
	protected $group_by_tires;

	/** @var  boolean */
	protected $group_by_rims;

	/**
	 * Corresponds to tire type: summer, winter, all-season, or all-weather,
	 * but may affect only tires, or both tires and wheels, since
	 * steel rims are best suited for winter. Not sure how we will deal
	 * with this yet.
	 *
	 * @var
	 */
	protected $package_type;

	/** @var  array */
	public $sizes;

	/**
	 * Staggered_Package_Multi_Size_Query constructor.
	 */
	public function __construct( $args = array() ) {
		$this->uid = 'pkg';

		parent::__construct( $args );

		// maybe not needed now
		$this->staggered_result    = true;
		$this->result_has_tires    = true;
		$this->result_has_rims     = true;
		$this->sizes_use_sql_union = true;

		$tire_1 = gp_if_set( $args, 'tire_1' );
		if ( $tire_1 ) {
			if ( ! $tire_1 instanceof DB_Tire ) {
				throw new Exception( 'Tire 1 needs to be an object' );
			}
			$this->tire_1 = $tire_1;
		}

		$tire_2 = gp_if_set( $args, 'tire_2' );
		if ( $tire_2 ) {
			if ( ! $tire_2 instanceof DB_Tire ) {
				throw new Exception( 'Tire 2 needs to be an object' );
			}
			$this->tire_2 = $tire_2;
		}

		$rim_1 = gp_if_set( $args, 'rim_1' );
		if ( $rim_1 ) {
			if ( ! $rim_1 instanceof DB_Rim ) {
				throw new Exception( 'Rim 1 needs to be an object' );
			}
			$this->rim_1 = $rim_1;
		}

		$rim_2 = gp_if_set( $args, 'rim_2' );
		if ( $rim_2 ) {
			if ( ! $rim_2 instanceof DB_Rim ) {
				throw new Exception( 'Rim 2 needs to be an object' );
			}
			$this->rim_2 = $rim_2;
		}

		if ( ! $this->rim_1 && ! $this->tire_1 ) {
			throw_dev_error( 'The package query no longer works if you dont inject either tires or rims' );
		}

		if ( $tire_1 ) {
			$this->group_by_rims = true;
		} else if ( $rim_1 ) {
			$this->group_by_tires = true;
		} else {
			$this->group_by_rims = true;
		}

	}

	/**
	 * @param $v
	 */
	public function set_package_type( $v ) {
		$this->package_type = $v;
	}

	/**
	 * Apply args first.
	 *
	 * @return bool
	 */
	public function get_grouping_by_rims() {
		return $this->group_by_rims;
	}

	/**
	 * Apply args first.
	 *
	 * @return bool
	 */
	public function get_grouping_by_tires() {
		return $this->group_by_tires;
	}

	/**
	 * @param $size
	 */
	public function get_staggered_package_size( $size ) {

		// this lets us not check isset on $size all the time
		if ( ! validate_package_size_array( $size ) ) {
			throw_dev_error( 'size array invalid' );
		}

		$db     = get_database_instance();
		$params = array();

		// possibly inject tires or rims. Logic is done beforehand to ensure the combinations of these values make sense.
		// so whatever we have right now, we will attempt to inject into the query. This is not the place to validate them.
		$tire_1_part_number = $this->tire_1 instanceof DB_Tire ? $this->tire_1->get( 'part_number' ) : '';
		$tire_2_part_number = $this->tire_2 instanceof DB_Tire ? $this->tire_2->get( 'part_number' ) : '';
		$rim_1_part_number  = $this->rim_1 instanceof DB_Rim ? $this->rim_1->get( 'part_number' ) : '';
		$rim_2_part_number  = $this->rim_2 instanceof DB_Rim ? $this->rim_2->get( 'part_number' ) : '';

		// queries used to work like this but no longer do
		assert( $tire_1_part_number || $rim_1_part_number, 'get_staggered_package_size required a rim(s) or tire(s) to be injected in order to work as intended.' );

		// since we validated the $size array, we can skip isset() checks on $size.
		$staggered = $size[ 'staggered' ];
		$t1_size   = $staggered ? $size[ 'tires' ][ 'front' ] : $size[ 'tires' ][ 'universal' ];
		$t2_size   = $staggered ? $size[ 'tires' ][ 'rear' ] : null;
		$r1_size   = $staggered ? $size[ 'rims' ][ 'front' ] : $size[ 'rims' ][ 'universal' ];
		$r2_size   = $staggered ? $size[ 'rims' ][ 'rear' ] : null;

		// Front Tires
		$front_tire_filters                  = gp_if_set( $this->queued_filters, 'tires', array() );
		$front_tire_filters[ 'part_number' ] = $tire_1_part_number ? $tire_1_part_number : null;

		$comp_t1 = new Query_Components_Tires( 'front_tires', false );
		$comp_t1->apply_filter( $front_tire_filters, 'part_number', 'get_part_number', true );
		$comp_t1->apply_filter( $front_tire_filters, 'brand', 'get_brand', true );
		$comp_t1->apply_filter( $front_tire_filters, 'model', 'get_model', true );
		$comp_t1->apply_filter( $front_tire_filters, 'load_index_min', 'get_load_index_min' );
		$comp_t1->apply_filter( $front_tire_filters, 'speed_rating', 'get_speed_rating', true );
		$comp_t1->builder->add_to_self( DB_Tire::sql_assert_sold_and_not_discontinued_in_locale( 'front_tires', $this->locale ), [] );
		$comp_t1->builder->add_to_self( $comp_t1->get_size( $t1_size ) );

		// rear tires - some columns are skipped because when we inner join, we make them equal to the front tire columns.
		$rear_tire_filters                       = array();
		$rear_tire_filters[ 'part_number' ]      = $tire_2_part_number ? $tire_2_part_number : null;
		$rear_tire_filters[ 'load_index_min' ]   = gp_if_set( $front_tire_filters, 'load_index_min', null );
		$rear_tire_filters[ 'speed_rating' ]     = gp_if_set( $front_tire_filters, 'speed_rating', null );
		$rear_tire_filters[ 'speed_rating_min' ] = gp_if_set( $front_tire_filters, 'speed_rating_min', null );

		$comp_t2 = new Query_Components_Tires( 'rear_tires', false );

		if ( $staggered ) {
			$comp_t2->apply_filter( $rear_tire_filters, 'part_number', 'get_part_number', true );
			$comp_t2->apply_filter( $rear_tire_filters, 'load_index_min', 'get_load_index_min' );
			$comp_t2->apply_filter( $rear_tire_filters, 'speed_rating', 'get_speed_rating', true );
			$comp_t2->builder->add_to_self( 'front_tires.model_id = rear_tires.model_id' );
			$comp_t2->builder->add_to_self( $comp_t2->get_size( $t2_size ) );
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

		if ( $staggered ) {

			$select[] = '"1" AS staggered';

			// total price
			if ( app_is_locale_canada_otherwise_force_us() ) {
				$select[] = '( ( front_tires.price_ca + rear_tires.price_ca + front_rims.price_ca + rear_rims.price_ca ) * 2 ) AS total_price';
			} else {
				$select[] = '( ( front_tires.price_us + rear_tires.price_us + front_rims.price_us + rear_rims.price_us ) * 2 ) AS total_price';
			}

			// to (possibly) pair cheapest tire with rim
			if ( app_is_locale_canada_otherwise_force_us() ) {
				$select[] = '(front_tires.price_ca + rear_tires.price_ca) AS tires_price';
			} else {
				$select[] = '(front_tires.price_us + rear_tires.price_us) AS tires_price';
			}

			$select[] = DB_Tire::prefix_alias_select( 'front_tires', 't1_' );
			$select[] = DB_Tire::prefix_alias_select( 'rear_tires', 't2_' );

			$select[] = DB_Rim::prefix_alias_select( 'front_rims', 'r1_' );
			$select[] = DB_Rim::prefix_alias_select( 'rear_rims', 'r2_' );

		} else {

			$select[] = '"" AS staggered';

			// total price
			if ( app_is_locale_canada_otherwise_force_us() ) {
				$select[] = '( (front_tires.price_ca + front_rims.price_ca) * 4) AS total_price';
			} else {
				$select[] = '( (front_tires.price_us + front_rims.price_us) * 4) AS total_price';
			}

			// to (possibly) pair cheapest tire with rim
			if ( app_is_locale_canada_otherwise_force_us() ) {
				$select[] = 'front_tires.price_ca AS tires_price';
			} else {
				$select[] = 'front_tires.price_us AS tires_price';
			}

			// select front tires twice, the second time prefixed as if they are the rear
			$select[] = DB_Tire::prefix_alias_select( 'front_tires', 't1_' );
			$select[] = DB_Tire::prefix_alias_select( 'front_tires', 't2_' );

			// select front rims twice, the second time prefixed as if they are the rear
			$select[] = DB_Rim::prefix_alias_select( 'front_rims', 'r1_' );
			$select[] = DB_Rim::prefix_alias_select( 'front_rims', 'r2_' );
		}

		// select tire brands/models with aliases
		$select[] = DB_Tire_Brand::prefix_alias_select( 't1_brands', 'tb1_' );
		$select[] = DB_Tire_Model::prefix_alias_select( 't1_models', 'tm1_' );

		// select rim brands/models/finishes with aliases
		$select[] = DB_Rim_Brand::prefix_alias_select( 'r1_brands', 'rb1_' );
		$select[] = DB_Rim_Model::prefix_alias_select( 'r1_models', 'rm1_' );
		$select[] = DB_Rim_Finish::prefix_alias_select( 'r1_finishes', 'rf1_' );

		// BEGIN SQL
		$sql = '';
		$sql .= 'SELECT ' . implode_comma( $select ) . ' ';
		$sql .= 'FROM ( SELECT "dummy" FROM DUAL ) AS nothing ';

		// ******** INNER JOIN FRONT TIRES********
		$sql    .= 'INNER JOIN ' . $db->tires . ' AS front_tires ON ' . $comp_t1->builder->sql_with_placeholders() . ' ';
		$params = array_merge( $params, $comp_t1->builder->parameters_array() );
		// ******** end INNER JOIN FRONT TIRES********

		// ******** INNER JOIN REAR TIRES********
		if ( $staggered ) {
			$sql    .= 'INNER JOIN ' . $db->tires . ' AS rear_tires ON ' . $comp_t2->builder->sql_with_placeholders() . ' ';
			$params = array_merge( $params, $comp_t2->builder->parameters_array() );
		}
		// ******** end JOIN REAR TIRES********

		// ******** INNER JOIN FRONT RIMS ********

		// apply all queued filters to front rim
		$front_rim_filters = gp_if_set( $this->queued_filters, 'rims', array() );

		// null as default is very important, false or empty string (may) cause 'rims.part_number = ""'
		$front_rim_filters[ 'part_number' ] = $rim_1_part_number ? $rim_1_part_number : null;

		// simply define the package type, and let select_best_fit_rims() deal with the logic.
		// the logic is probably just some rules for dealing with winter packages. see the function for more info
		$front_rim_filters[ 'rim_package_type' ] = $this->package_type;

		$join_r1 = $this->select_best_fit_rim_in_each_finish( $r1_size, $front_rim_filters );
		$sql     .= 'INNER JOIN ( ' . $join_r1[ 0 ] . ' ) AS front_rims ON 1 = 1 ';
		$params  = array_merge( $params, $join_r1[ 1 ] );
		// ******** end INNER JOIN FRONT RIMS ********

		// ******** INNER JOIN REAR RIMS ********
		if ( $staggered ) {

			// we dont need to apply filters to the rear rims for the most part, because it just so happens that for each
			// filter that we could apply, rear rims are forced to have the same value as the front rims. obviously this could change..
			// and if it does, there might be some pretty annoying debugging to do, but hopefully you will read this and figure it out.
			$rear_rim_filters                       = array();
			$rear_rim_filters[ 'part_number' ]      = $rim_2_part_number ? $rim_2_part_number : null;
			$rear_rim_filters[ 'rim_package_type' ] = $this->package_type;

			/**
			 * Warning: @see rims-query-fitment-sizes.php (2 places should (probably) be the same)
			 */
			$join_rear_rims_conditions = array(
				// 'front_rims.rim_id <> rear_rims.rim_id', // I think this needs to be off.
				'front_rims.model_id = rear_rims.model_id',
				'front_rims.finish_id = rear_rims.finish_id',
				'front_rims.type = rear_rims.type',
				'front_rims.style = rear_rims.style',
			);

			$join_r2 = $this->select_best_fit_rim_in_each_finish( $r2_size, $rear_rim_filters );
			$sql     .= 'INNER JOIN ( ' . $join_r2[ 0 ] . ' ) AS rear_rims ON ' . implode( ' AND ', $join_rear_rims_conditions ) . ' ';
			$params  = array_merge( $params, $join_r2[ 1 ] );
		}
		// ******** end INNER JOIN REAR RIMS ********

		// inner join front/rear tire/rim models/brands

		// *** Tire Models ***
		$tire_model_filters = gp_if_set( $this->queued_filters, 'tire_models' );

		// package type is probably required, but we don't require it here when doing the query.
		$tire_model_filters[ 'type' ] = $this->package_type ? $this->package_type : null;

		$comp_t1_models = new Query_Components_Tire_Models( 't1_models', false );
		$comp_t1_models->builder->add_to_self( 't1_models.tire_model_id = front_tires.model_id' );
		$comp_t1_models->apply_filter( $tire_model_filters, 'type', 'get_type', true );
		$comp_t1_models->apply_filter( $tire_model_filters, 'class', 'get_class', true );
		$comp_t1_models->apply_filter( $tire_model_filters, 'category', 'get_category', true );

		// *** Tire Models ***
		$sql    .= 'INNER JOIN tire_models AS t1_models ON ' . $comp_t1_models->builder->sql_with_placeholders() . ' ';
		$params = array_merge( $params, $comp_t1_models->builder->parameters_array() );

		// *** Tire Brands ***
		$sql .= 'INNER JOIN tire_brands AS t1_brands ON t1_brands.tire_brand_id = front_tires.brand_id ';

		// *** Rim Models ***
		$sql .= 'INNER JOIN rim_models AS r1_models ON r1_models.rim_model_id = front_rims.model_id ';

		// *** Rim Brands ***
		$sql .= 'INNER JOIN rim_brands AS r1_brands ON r1_brands.rim_brand_id = front_rims.brand_id ';

		// *** Rim Finishes ***
		$sql .= 'INNER JOIN rim_finishes AS r1_finishes ON r1_finishes.rim_finish_id = front_rims.finish_id ';

		// do not do this:
		// these inner joins are not only redundant but they DESTROY the query time as well (by a factor of about x4)
		// $sql .= $staggered ? 'INNER JOIN tire_brands AS t2_brands ON t2_brands.tire_brand_id = rear_tires.brand_id ' : '';
		// $sql .= $staggered ? 'INNER JOIN tire_models AS t2_models ON t2_models.tire_model_id = rear_tires.model_id ' : '';
		// $sql .= $staggered ? 'INNER JOIN rim_brands AS r2_brands ON r2_brands.rim_brand_id = rear_rims.brand_id ' : '';
		// $sql .= $staggered ? 'INNER JOIN rim_models AS r2_models ON r2_models.rim_model_id = rear_rims.model_id ' : '';

		$sql .= 'WHERE 1 = 1 ';

		// ** Speed Rating / Load Index / only for non-winter tires **
		// note: this logic is more or less repeated in tire-query-fitment-sizes.php

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

		// Careful right here. We have class properties $this->group_by_rims, and $this->group_by_tires.
		// make sure those class properties reflect the logic below if you change anything...
		if ( $tire_1_part_number ) {

			$sql .= 'GROUP BY r1_part_number ';

			// ordering here has no real effect
			// $sql .= 'ORDER BY tires_price '; // I believe this is redundant

		} else if ( $rim_1_part_number ) {

			$sql .= 'GROUP BY t1_part_number ';
			// ordering here has no real effect
			// $sql .= 'ORDER BY tires_price ';

		} else {

			throw_dev_error( 'We should no longer get to here in pkg query' );
			exit;

			// you might think, this will pair the cheapest in stock tire.. but.. it does not.
			$sql .= 'GROUP BY r1_part_number ';
			// $sql .= 'ORDER BY in stock derived column that doesnt exist yet ';
			$sql .= 'ORDER BY tires_price ASC ';

		}

		// do not end the $sql with ';', it belongs inside of a UNION
		return array( $sql, $params );
	}

	/**
	 *
	 */
	public function get_sizes_unioned( $sizes, $userdata = array() ) {

		$unions = array();
		$params = array();
		$sql    = '';

		// Loop to get Union conditions
		if ( $sizes && is_array( $sizes ) ) {
			foreach ( $sizes as $size ) {
				$_u = $this->get_staggered_package_size( $size );
				if ( $_u ) {
					$unions[] = $_u[ 0 ];
					$params   = array_merge( $params, $_u[ 1 ] );
				} else {
					throw new Exception( 'Invalid package size [3]' );
				}
			}
		}

		$sql .= '( ' . implode( ' ) UNION ( ', $unions ) . ' )';

		return array( $sql, $params );
	}

	/**
	 * @return array
	 */
	public function get_results( $userdata = array() ) {

		// prevent exception in case this isn't checked earlier
		if ( ! $this->sizes ) {
			return array();
		}

		$db     = get_database_instance();
		$sql    = '';
		$params = array();

		// Begin Select
		$select = array();

		// this captures all aliased columns from within the "UNION" (technically, single size queries don't use a UNION)
		$select[] = '*';

		// Select
		$sql .= $this->get_select_from_array( $select ) . ' ';

		// Union each size - this is where most of the logic is done.
		$unions = $this->get_sizes_unioned( $this->sizes, $userdata );

		$sql    .= 'FROM ( ' . $unions[ 0 ] . ' ) AS all_data ';
		$params = array_merge( $params, $unions[ 1 ] );

		// apply filters or other data
		$sql .= 'WHERE "pkg" = "pkg" ';

		$price_range_component = $this->get_price_range_component( 'total_price' );
		if ( $price_range_component ) {
			$sql    .= 'AND ' . $price_range_component->sql_with_placeholders() . ' ';
			$params = array_merge( $params, $price_range_component->parameters_array() );
		}

		// this likely does nothing I believe
		$sql    .= 'AND ' . $this->top_level_components->sql_with_placeholders() . ' ';
		$params = array_merge( $params, $this->top_level_components->parameters_array() );

		// this might be redundant... grouping is done from inside each unioned size.
		$sql .= 'GROUP BY r1_part_number, r2_part_number, t1_part_number, t2_part_number ';

		// don't feel like explaining again... but this is basically a simplistic order by that does the job
		// even if it is once in a while the tiniest but redundant
		$sql .= 'ORDER BY total_price ASC, r1_model_slug, t1_model_slug ';

		$sql .= $this->get_limit_clause( $params ) . ' ';

		$sql .= ';'; // end

		$results = $db->get_results( $sql, $params );

		return $results;
	}

	/**
	 * @param $row
	 */
	public static function parse_row( $row ) {

		$staggered   = gp_if_set( $row, 'staggered' );
		$total_price = gp_if_set( $row, 'total_price' );

		// front/rear tires/rims
		$t1 = collect_keys_with_prefix( $row, DB_Tire::get_fields(), 't1_' );
		$t2 = collect_keys_with_prefix( $row, DB_Tire::get_fields(), 't2_' );
		$r1 = collect_keys_with_prefix( $row, DB_Rim::get_fields(), 'r1_' );
		$r2 = collect_keys_with_prefix( $row, DB_Rim::get_fields(), 'r2_' );

		// front tire model/brand
		$tm1 = collect_keys_with_prefix( $row, DB_Tire_Model::get_fields(), 'tm1_' );
		$tb1 = collect_keys_with_prefix( $row, DB_Tire_Brand::get_fields(), 'tb1_' );

		// inject brand/model objects into tire/rim objects
		$tire_1 = DB_Tire::create_instance_or_null( $t1, array(
			'model' => DB_Tire_Model::create_instance_or_null( $tm1 ),
			'brand' => DB_Tire_Brand::create_instance_or_null( $tb1 ),
		) );

		if ( $staggered ) {

			// rear tire model/brand
			//			$tm2 = collect_keys_with_prefix( $row, DB_Tire_Model::get_fields(), 'tm2_' );
			//			$tb2 = collect_keys_with_prefix( $row, DB_Tire_Brand::get_fields(), 'tb2_' );

			$tire_2 = DB_Tire::create_instance_or_null( $t2, array(
				'model' => DB_Tire_Model::create_instance_or_null( $tm1 ),
				'brand' => DB_Tire_Brand::create_instance_or_null( $tb1 ),
			) );
		} else {
			$tire_2 = null;
		}

		// front rim model/brand/finish
		$rb1 = collect_keys_with_prefix( $row, DB_Rim_Brand::get_fields(), 'rb1_' );
		$rm1 = collect_keys_with_prefix( $row, DB_Rim_Model::get_fields(), 'rm1_' );
		$rf1 = collect_keys_with_prefix( $row, DB_Rim_Finish::get_fields(), 'rf1_' );

		$rim_brand  = DB_Rim_Brand::create_instance_or_null( $rb1 );
		$rim_model  = DB_Rim_Model::create_instance_or_null( $rm1 );
		$rim_finish = DB_Rim_Finish::create_instance_or_null( $rf1 );

		$rim_1 = DB_Rim::create_instance_or_null( $r1, array(
			'model' => $rim_model,
			'brand' => $rim_brand,
			'finish' => $rim_finish,
		) );

		if ( $staggered ) {

			// use the same model/brand/finish, these must be the same
			// unless we one day support staggered queries where they are not but it makes no sense really
			$rim_2 = DB_Rim::create_instance_or_null( $r2, array(
				'model' => $rim_model,
				'brand' => $rim_brand,
				'finish' => $rim_finish,
			) );
		} else {
			$rim_2 = null;
		}

		// lets throw all values in the array even though rear tires/rims could be null
		// this way we know what to expect. if staggered is false, then we should not be
		// referencing rear tire/rim anyways.
		$ret                   = array();
		$ret[ 'raw_data' ]     = $row;
		$ret[ 'total_price' ]  = $total_price;
		$ret[ 'fitment_slug' ] = gp_if_set( $row, 'fitment_slug' );
		$ret[ 'staggered' ]    = $staggered;
		$ret[ 'front_tire' ]   = $tire_1;
		$ret[ 'rear_tire' ]    = $tire_2;
		$ret[ 'front_rim' ]    = $rim_1;
		$ret[ 'rear_rim' ]     = $rim_2;

		return $ret;
	}
}
