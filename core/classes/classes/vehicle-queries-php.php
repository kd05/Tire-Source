<?php

/**
 * __construct(), then set filters manually.
 *
 * Some rear filters are missing because in all staggered queried,
 * the front and rear brand/models/finishes are the same.
 *
 *
 * Class Vehicle_Queries_PHP
 */
Class Vehicle_Queries_PHP {

	public $locale;

	/**
	 * an array of 1 or more vehicle fitment size arrays.
	 *
	 * @see Fitment_General::get_size_from_fitment_and_wheel_set()
	 *
	 * @var
	 */
	public $sizes;

	public $front_tire_filters = array();
	public $rear_tire_filters = array();
	public $tire_model_filters = array();
	public $tire_brand_filters = array();
	public $front_rim_filters = array();
	public $rear_rim_filters = array();
	public $rim_brand_filters = array();
	public $rim_model_filters = array();
	public $rim_finish_filters = array();
	public $other_filters = array();

	/**
	 * We could use $this->apply_filter_other() for this, but package_type is important
	 * enough to package queries that I want to make it very easy to not make any mistakes with,
	 * as well as to find usages of it.
	 *
	 * @var string|null
	 */
	public $package_type;

	/**
	 * Not sql, just a string, ie. "single_product_page" or "brand".
	 *
	 * "brand" will mean user selected "brand" which means in reality we may order
	 * by brand, then model, then finish, then price etc. etc.
	 *
	 * @var string
	 */
	public $sort;

	/**
	 * for profiling, @see self::track_time_or_debug()
	 *
	 * @var
	 */
	public $times = array();

	/**
	 * @var string
	 */
	public $time_tracking_context = 'vehicle_query';

	/**
	 * Vehicle_Queries_PHP constructor.
	 */
	public function __construct( $locale ) {
		$this->locale = $locale;
		start_time_tracking( $this->time_tracking_context );
		$this->track_time_or_debug( '__construct' );
		assert( app_is_locale_valid( $locale ) );
	}

	/**
	 * @param $data_src
	 * @param $data_index
	 * @param $allow_array
	 * @param $allow_false_like_value
	 */
	public function apply_tire_1_filter( $filter_name, $data_src, $data_index, $allow_array = true, $allow_false_like_value = false ) {
		$this->apply_filter( 'front_tire_filters', $filter_name, $data_src, $data_index, $allow_array, $allow_false_like_value );
	}

	/**
	 * @param $data_src
	 * @param $data_index
	 * @param $allow_array
	 * @param $allow_false_like_value
	 */
	public function apply_tire_2_filter( $filter_name, $data_src, $data_index, $allow_array = true, $allow_false_like_value = false ) {
		$this->apply_filter( 'rear_tire_filters', $filter_name, $data_src, $data_index, $allow_array, $allow_false_like_value );
	}

	/**
	 * @param $data_src
	 * @param $data_index
	 * @param $allow_array
	 * @param $allow_false_like_value
	 */
	public function apply_rim_1_filter( $filter_name, $data_src, $data_index, $allow_array = true, $allow_false_like_value = false ) {
		$this->apply_filter( 'front_rim_filters', $filter_name, $data_src, $data_index, $allow_array, $allow_false_like_value );
	}

	/**
	 * @param $data_src
	 * @param $data_index
	 * @param $allow_array
	 * @param $allow_false_like_value
	 */
	public function apply_rim_2_filter( $filter_name, $data_src, $data_index, $allow_array = true, $allow_false_like_value = false ) {
		$this->apply_filter( 'rear_rim_filters', $filter_name, $data_src, $data_index, $allow_array, $allow_false_like_value );
	}

	/**
	 * @param $data_src
	 * @param $data_index
	 * @param $allow_array
	 * @param $allow_false_like_value
	 */
	public function apply_tire_brand_filter( $filter_name, $data_src, $data_index, $allow_array = true, $allow_false_like_value = false ) {
		$this->apply_filter( 'tire_brand_filters', $filter_name, $data_src, $data_index, $allow_array, $allow_false_like_value );
	}

	/**
	 * @param $data_src
	 * @param $data_index
	 * @param $allow_array
	 * @param $allow_false_like_value
	 */
	public function apply_tire_model_filter( $filter_name, $data_src, $data_index, $allow_array = true, $allow_false_like_value = false ) {
		$this->apply_filter( 'tire_model_filters', $filter_name, $data_src, $data_index, $allow_array, $allow_false_like_value );
	}

	/**
	 * @param $data_src
	 * @param $data_index
	 * @param $allow_array
	 * @param $allow_false_like_value
	 */
	public function apply_rim_brand_filter( $filter_name, $data_src, $data_index, $allow_array = true, $allow_false_like_value = false ) {
		$this->apply_filter( 'rim_brand_filters', $filter_name, $data_src, $data_index, $allow_array, $allow_false_like_value );
	}

	/**
	 * @param $data_src
	 * @param $data_index
	 * @param $allow_array
	 * @param $allow_false_like_value
	 */
	public function apply_rim_model_filter( $filter_name, $data_src, $data_index, $allow_array = true, $allow_false_like_value = false ) {
		$this->apply_filter( 'rim_model_filters', $filter_name, $data_src, $data_index, $allow_array, $allow_false_like_value );
	}

	/**
	 * @param $data_src
	 * @param $data_index
	 * @param $allow_array
	 * @param $allow_false_like_value
	 */
	public function apply_rim_finish_filter( $filter_name, $data_src, $data_index, $allow_array = true, $allow_false_like_value = false ) {
		$this->apply_filter( 'rim_finish_filters', $filter_name, $data_src, $data_index, $allow_array, $allow_false_like_value );
	}

	/**
	 * @param $data_src
	 * @param $data_index
	 * @param $allow_array
	 * @param $allow_false_like_value
	 */
	public function apply_other_filter( $filter_name, $data_src, $data_index, $allow_array = true, $allow_false_like_value = false ) {
		$this->apply_filter( 'other_filters', $filter_name, $data_src, $data_index, $allow_array, $allow_false_like_value );
	}

	/**
	 * "Applying" a filter does not necessarily do anything. It just stores some value
	 * in an array somewhere. Its up to the query when/if/how it wants to deal with those values.
	 *
	 * @see $this->get_debug_array(). Without this, you'll have a very hard time keeping track of filters.
	 *
	 * To know the $filter_name to use:
	 *
	 * @see Query_Components_Rims::apply_all_filters()
	 * @see Query_Components_Tires::apply_all_filters()
	 * @see Query_Components_Tire_Models::apply_all_filters()
	 * @see Query_Components_Tire_Brands::apply_all_filters()
	 * @see Query_Components_Rim_Brands::apply_all_filters()
	 * @see Query_Components_Rim_Models::apply_all_filters()
	 * @see Query_Components_Rim_Finishes::apply_all_filters()
	 *
	 * $data_src is an array, where the filter value should be found with index $data_index.
	 * This just lets us skip checking isset() a million times but otherwise is seemingly redundant.
	 *
	 * If $data_src[$data_index] === NULL, then nothing will happen when calling this.
	 *
	 * If $data_src[$data_index] == FALSE, (loose comparison) then there is a parameter for whether or you want that to
	 * get applied.
	 *
	 * @param      $name
	 * @param      $data_src
	 * @param      $data_index
	 * @param bool $allow_array
	 * @param bool $allow_false_like_value - if true, and $data_src[$data_index] is empty, the SQL may end up saying
	 *                                     ... AND table.col = ""
	 */
	protected function apply_filter( $prop_name, $filter_name, $data_src, $data_index, $allow_array = true, $allow_false_like_value = false  ) {

		assert( isset( $this->{$prop_name} ), 'prop_name_not_set_' . $prop_name );

		$value = $data_src !== null ? gp_if_set( $data_src, $data_index, null ) : $data_index;

		// best to not add anything to the array on null values.
		if ( $value === null || ( $allow_false_like_value === false && ! $value ) ) {
			// should be able to remove a filter this way if it was already set before hand.
			if ( isset( $this->{$prop_name}[$filter_name] ) ) {
				unset( $this->{$prop_name}[$filter_name]);
			}
			return;
		}

		if ( $allow_array ) {
			// ensure existing value is an array, and if it was previously singular and non-empty, keep that value in the result.
			$ex = $this->{$prop_name}[$filter_name];
			$ex = is_array( $ex ) ? $ex : ( gp_is_singular( $ex ) ? [ $ex ] : array() );
			$this->{$prop_name}[$filter_name] = $ex;

			// add a value
			$this->{$prop_name}[$filter_name][] = $value;
		} else {
			$this->{$prop_name}[$filter_name] = $value;
		}
	}

	/**
	 *
	 */
	public function get_debug_array( $merge = array() ){
		$arr = get_object_vars( $this );
		// $arr['results'] = $this->results ? count( $this->results ) : 0;
		$arr = $merge ? array_merge( $merge ) : $arr;
		return $arr;
	}

	/**
	 * @param string $desc
	 * @param null   $v - could be a not a time ...
	 */
	public function track_time_or_debug( $desc, $v = null ) {

		$v = $v === null ? end_time_tracking( $this->time_tracking_context ) : $v;

		if ( in_array( $desc, array_keys( $this->times ) ) ) {
			$this->times[ 'idk' ][] = $v;
		} else {
			$this->times[ $desc ] = $v;
		}
	}

    /**
     * tires/rims have same columns for managing stock,
     * 'stock, 'stock_sold', and 'stock_unlimited'
     *
     * @param $rim_or_tire_std_class
     * @param $locale
     * @return bool
     */
	public static function is_in_stock( $rim_or_tire_std_class, $locale ) {

		$unlimited = DB_Product::get_column_stock_unlimited( $locale );
		$amt = DB_Product::get_column_stock_amt( $locale );
		$sold = DB_Product::get_column_stock_sold( $locale );

		return $rim_or_tire_std_class->{$unlimited} || ( $rim_or_tire_std_class->{$amt} - $rim_or_tire_std_class->{$sold} ) > 3;
	}

	/**
	 * More or less like array_column( get_object_vars( $array_of_objects ) ),
	 * but allows custom callback function, and optional array unique.
	 *
	 * If you wanted to perform an SQL group by on more than 1 column via PHP,
	 * you could for example, { return col_1 . $col_2 } in the $cb function.
	 *
	 * @param      $array_of_objects
	 * @param      $cb
	 * @param bool $array_unique
	 *
	 * @return array
	 */
	public static function get_unique_values( $array_of_objects, $cb, $array_unique = true ) {
		$values = array_map( $cb, $array_of_objects );
		$values = $array_unique ? array_unique( $values ) : $values;
		$values = array_values( $values );

		return $values;
	}

	/**
	 * @see self::select_staggered_tires_ordered_by_package_best_recommended()
	 *
	 * run this on each array before calling this function.
	 *
	 * @param $front_rims
	 * @param $rear_rims
	 *
	 * @return array
	 */
	public static function pair_rims_with_common_rim_finish_ids( &$front_rims, &$rear_rims ) {

		// Filter rims to include only those who have finishes found in both front and rear
		$front_finish_ids = self::get_unique_values( $front_rims, function ( $row ) {
			return $row->finish_id;
		}, true );

		$rear_finish_ids = self::get_unique_values( $rear_rims, function ( $row ) {
			return $row->finish_id;
		}, true );

		$common_finish_ids = array_intersect( $front_finish_ids, $rear_finish_ids );

		// remove front rims without a matching rear rim
		$front_rims = array_filter( $front_rims, function ( $row ) use ( $common_finish_ids ) {
			return in_array( $row->finish_id, $common_finish_ids );
		} );

		// remove rear rims without a matching front rim
		$rear_rims = array_filter( $rear_rims, function ( $row ) use ( $common_finish_ids ) {
			return in_array( $row->finish_id, $common_finish_ids );
		} );

		return $common_finish_ids;
	}

    /**
     * Part of the reason we even use a function for this is because
     * we have a mirror function for staggered fitments, so i'd like to just have
     * one function for both even though this is seemingly redundant.
     *
     * $rims_grouped_by_finish is raw database results, an array of stdClass objects without any prefixes.
     * Ie.. "SELECT * FROM rims where... sizes". However, make sure to run them through the best_fit/grouping
     * function first.
     *
     * The $pkg_recommended_tire variable represents exactly 1 tire in our universal vehicle query format. Each rim
     * is paired with the same tire, and the return value is an array of the same object type.
     *
     * In the future, we could make $tire an array of Vehicle_Query_Database_Row's, and then use PHP to
     * intelligently pair tires with rims based on whatever rules we would like. For now, $tire represents
     * the cheapest in stock tire that fits the vehicle (and subject to package type), and this is good enough.
     *
     * @param                            $rims_grouped_by_finish_id
     * @param Vehicle_Query_Database_Row $pkg_recommended_tire
     * @return array
     */
	private static function pair_rims_with_same_tire( $rims_grouped_by_finish_id, Vehicle_Query_Database_Row $pkg_recommended_tire ) {

		$ret = array();

		if ( $rims_grouped_by_finish_id ) {

			foreach ( $rims_grouped_by_finish_id as $db_row ) {

				$staggered_package_row = clone $pkg_recommended_tire;

				$staggered_package_row->setup_front_rim( $db_row, '' );
				$staggered_package_row->setup_rim_brand( $db_row, '' );
				$staggered_package_row->setup_rim_model( $db_row, '' );

				// ensure each Vehicle_Query_Database_Row has the DB_Product objects setup,
				// which eliminates possibility of doing this many times per row on subsequent filtering or sorting
				$staggered_package_row->setup_db_objects();

				$ret[] = $staggered_package_row;
			}
		}

		return $ret;
	}

	private static function pair_staggered_tires_with_single_rim_pair(){

	}

	/**
	 * @param                            $common_finish_ids
	 * @param                            $front_rims
	 * @param                            $rear_rims
	 * @param Vehicle_Query_Database_Row $tire_pair
	 *
	 * @return array
	 */
	private static function pair_staggered_rims_with_staggered_tire_pair( $common_finish_ids, $front_rims, $rear_rims, Vehicle_Query_Database_Row $tire_pair ) {

		$ret = array();

		if ( $common_finish_ids ) {

			foreach ( $common_finish_ids as $finish_id ) {

				// get the front rim with this finish id
				$_front_rims = array_filter( $front_rims, function ( $row ) use ( $finish_id ) {
					return $row->finish_id == $finish_id;
				} );

				// get the rear rim with this finish id
				$_rear_rims = array_filter( $rear_rims, function ( $row ) use ( $finish_id ) {
					return $row->finish_id == $finish_id;
				} );

				// logically, we expect this, BUT it depends on whether the $common_finish_ids passed in were truly
				// the common_finish_ids generated from $front_rims and $rear_rims. I think its possible that
				// we may want to show a different list of finish IDs for whatever reason. Therefore, I think
				// we're going to change the behaviour of this function a little.
				//				assert( count( $_front_rims ) === 1 );
				//				assert( count( $_rear_rims ) === 1 );

				// this no longer means an error for sure although its possible you don't want this to trigger
				if ( ! $_front_rims || ! $_rear_rims ){
					queue_dev_alert( 'common finish IDs were not truly the common finish IDs of front/rear rims passed in' );
					continue;
				}

				// array_values is necessary herre
				$_front_rims = array_values( $_front_rims );
				$_rear_rims = array_values( $_rear_rims );

				$staggered_package_row = clone $tire_pair;

				$staggered_package_row->setup_front_rim( $_front_rims[0], '' );
				$staggered_package_row->setup_rim_brand( $_front_rims[0], '' );
				$staggered_package_row->setup_rim_model( $_front_rims[0], '' );
				$staggered_package_row->setup_rim_finish( $_front_rims[0], '' );
				$staggered_package_row->setup_rear_rim( $_rear_rims[0], '' );

				// ensure each row has the DB_Product objects setup.
				// this will likely happen again, but would rather be on the safe side, because
				// $ret could be fed directly into usort() which would then setup_db_objects()
				// many more times than if we just do it now.
				$staggered_package_row->setup_db_objects();

				$ret[] = $staggered_package_row;
			}
		}

		return $ret;
	}

	/**
	 * This is hard due to multiple size requirements and due to that fact that previous
	 * multi size queries support a mix of staggered and non-staggered sizes. For now,
	 * the best fit in stock rims logic is repeated in rims queries that rely only on SQL (not in this file)
	 * and in the package queries (found here) that use PHP to do the pairing.
	 */
	// public function rims_query_not_staggered(){}

	/**
	 * @see rims_query_not_staggered() for comment
	 */
	//	public function rims_query_staggered(){
	//		$size = gp_if_set( array_values( $this->sizes ), 0 );
	//		$rims_that_fit_front = self::query_single_set_of_rims_by_size( $size, 'front', $this->locale, [], [], [], [] );
	//		$rims_that_fit_rear = self::query_single_set_of_rims_by_size( $size, 'rear', $this->locale, [], [], [], [] );
	//	}

	/**
	 * Long story short: this can be done in one SQL query, but it can get messy, and PHP has less limitations.
	 * Also, this query in SQL isn't so bad at all, but this function is a simplified version of the staggered function.
	 * I don't want one to be pure SQL and one to be a mix of SQL and PHP.
	 *
	 * @return array
	 */
	public function package_query_not_staggered( $tire = null, $rim = null ){

		if ( $tire && ! $rim ) {
			$group_by = 'rims';
			$this->front_tire_filters['part_number'] = $tire;
		} else if ( $rim && ! $tire ) {
			$group_by = 'tires';
			$this->front_rim_filters['part_number'] = $rim;
		} else if ( ! $rim && ! $tire ) {
			$group_by = 'rims';
		} else {
			throw_dev_error( 'no' );
			exit;
		}

		if ( $tire && $rim ) {

		} else if ( $tire && ! $rim ) {

		} else if ( $rim && ! $tire ) {
			$group_by = 'tires';
		} else {
			$group_by = 'rims';
		}

		$this->track_time_or_debug( 'package_query_not_staggered' );

		// not handling multiple sizes ...
		$size = gp_if_set( array_values( $this->sizes ), 0 );

		assert( ( $size ), 'size is required' );
		assert( validate_tire_size_array( $size ) );
		assert( validate_rim_size_array( $size ) );

		$pkg_recommended_tires = self::select_not_staggered_tires_ordered_by_package_best_recommended( $size, $this->package_type, $this->locale, 1, $this->front_tire_filters, $this->tire_brand_filters, $this->tire_model_filters );
		$this->track_time_or_debug( 'after_tires' );

		// intentionally querying rims even if no tires found so i can test things
		$rims_that_fit = self::query_single_set_of_rims_by_size( $size, 'universal', $this->locale, $this->package_type, $this->front_rim_filters, $this->rim_brand_filters, $this->rim_model_filters, $this->rim_finish_filters );
		$this->track_time_or_debug( 'after_pkg_query_rims' );
		$this->track_time_or_debug( 'rims_that_fit', count( $rims_that_fit ) );

		// This means package has no results since no tires were found.
		if ( ! $pkg_recommended_tires ) {
			queue_dev_alert( 'no_tires_found--not_stg_pkg_query' );
			return array();
		}

		if ( $group_by === 'rims' ) {

			/** @var Vehicle_Query_Database_Row $tire_row */
			$pkg_recommended_tire = $pkg_recommended_tires ? $pkg_recommended_tires[ 0 ] : false;

			// Choose in stock, and best fit rim for each finish.
			$best_fit_rims_grouped_by_finish = self::group_rims_by_finish_and_select_in_stock_best_fit( $rims_that_fit, $this->locale );
			$this->track_time_or_debug( 'best_fit_rims_grouped_by_finish', count( $best_fit_rims_grouped_by_finish ) );

			$vehicle_query_db_rows = self::pair_rims_with_same_tire( $best_fit_rims_grouped_by_finish, $pkg_recommended_tire );
			$this->track_time_or_debug( 'vehicle_query_db_rows', count( $vehicle_query_db_rows ) );

		} else {

			$_rim = new Vehicle_Query_Database_Row();

			$vehicle_query_db_rows = array();

			if ( $pkg_recommended_tires ) {
				/** @var Vehicle_Query_Database_Row $tire */
				foreach ( $pkg_recommended_tires as $tire ) {



				}
			}

			// Choose in stock, and best fit rim for each finish.
			$best_fit_rims_grouped_by_finish = self::group_rims_by_finish_and_select_in_stock_best_fit( $rims_that_fit, $this->locale );
			$this->track_time_or_debug( 'best_fit_rims_grouped_by_finish', count( $best_fit_rims_grouped_by_finish ) );

			$vehicle_query_db_rows = self::pair_rims_with_same_tire( $best_fit_rims_grouped_by_finish, $pkg_recommended_tire );
			$this->track_time_or_debug( 'vehicle_query_db_rows', count( $vehicle_query_db_rows ) );

		}



		// this was almost certainly already done but I prefer to do it again and be 100% sure
		$vehicle_query_db_rows = array_map( function( $row ){
			/** @var Vehicle_Query_Database_Row $row */
			$row->setup_db_objects();
			return $row;
		}, $vehicle_query_db_rows );

		// Apply filters via PHP
		// we need this for the total price of tire + rim for example, because this does
		// not exist until after pairing which is done via PHP.
		$vehicle_query_db_rows = array_filter( $vehicle_query_db_rows, function( $row ) {
			/** @var Vehicle_Query_Database_Row $row */
			return true;
		});

		// Final Sorting
		// Once again we can't do this in the initial SQL since some values
		// of each row change depending on how you pair them.
		usort( $vehicle_query_db_rows, function( $r1, $r2 ) {

			/** @var Vehicle_Query_Database_Row $r1 */
			/** @var Vehicle_Query_Database_Row $r2 */
			$p1 = $r1->get_total_price( $this->locale );
			$p2 = $r2->get_total_price( $this->locale );

			return 0;
		});

		return $this->return_and_profile( $vehicle_query_db_rows, 'vehicle pkg main query' );
	}

	/**
	 * Just puts some overview below the footer (when not in production) and returns
	 * what you pass in.
	 *
	 * @param $results
	 */
	public function return_and_profile( $results, $name = 'vehicle main query' ) {

		$this->track_time_or_debug( 'return_and_profile' );

		$count = $results ? count( $results ) : 0;
		queue_dev_alert( $name . ' (' . $count . ')', get_pre_print_r( $this->get_debug_array() ) );
		return $results;
	}

	/**
	 * Long story short: Yes this is sort of possible in one giant SQL query, but it gets very messy. PHP also seems to
	 * have possibilities that although aren't in place right now, could be added in the future, where its very un
	 * clear whether or not its even possible in one SQL query. So, the general idea here is: Select the rims and tires
	 * that fit in separate SQL queries. Give those results to PHP to pair rims with tires and choose the best fitting
	 * rim for each finish. Then do one final (partial) filtering and sorting function before returning the final
	 * results.
	 */
	public function package_query_staggered() {

		assert( false, 'this is not functional' );

		$this->track_time_or_debug( 'package_query_staggered' );

		// not handling multiple sizes ...
		$size = gp_if_set( array_values( $this->sizes ), 0 );

		assert( validate_tire_size_array( $size ) );
		assert( validate_rim_size_array( $size ) );

		$tire_pairs = self::select_staggered_tires_ordered_by_package_best_recommended( $size, $this->package_type, $this->locale, 1, [], [] );
		$this->track_time_or_debug( 'after_tires' );

		// intentionally querying rims even if no tires found so i can test things
		$front_rims_that_fit = self::query_single_set_of_rims_by_size( $size, 'front', $this->locale, $this->package_type, $this->front_rim_filters, $this->rim_brand_filters, $this->rim_model_filters, $this->rim_finish_filters );
		$rear_rims_that_fit = self::query_single_set_of_rims_by_size( $size, 'rear', $this->locale, $this->package_type, $this->rear_rim_filters, $this->rim_brand_filters, $this->rim_model_filters, $this->rim_finish_filters );
		$this->track_time_or_debug( 'after_rims_front_rear' );
		$this->track_time_or_debug( 'after_rims_front_rear', count( $front_rims_that_fit ) . ' ... ' . count( $rear_rims_that_fit ) );

		// This means package has no results since no tires were found.
		if ( ! $tire_pairs ) {
			queue_dev_alert( 'no_tires_found--stg_pkg_query' );
			return array();
		}

		/** @var Vehicle_Query_Database_Row $tire_pair */
		$tire_pair = $tire_pairs ? $tire_pairs[ 0 ] : false;

		// Choose in stock, and best fit rim for each finish.
		$front_rims_grouped_by_finish = self::group_rims_by_finish_and_select_in_stock_best_fit( $front_rims_that_fit, $this->locale );
		$rear_rims_grouped_by_finish  = self::group_rims_by_finish_and_select_in_stock_best_fit( $rear_rims_that_fit, $this->locale );

		// front/rear rims are passed by reference here and a few items are probably removed from each array.
		$common_finish_ids = self::pair_rims_with_common_rim_finish_ids( $front_rims_grouped_by_finish, $rear_rims_grouped_by_finish );

		// loops through the finish IDs, and creates a row where each contains front tire, rear tire, front rim, rear rim etc.
		$results = self::pair_staggered_rims_with_staggered_tire_pair( $common_finish_ids, $front_rims_grouped_by_finish, $rear_rims_grouped_by_finish, $tire_pair );

		// ensure DB_Product objects are setup within each $row before calling array_filter() or usort()
		$results = array_map( function( $row ){
			/** @var Vehicle_Query_Database_Row $row */
			$row->setup_db_objects();
			return $row;
		}, $results );

		// Filter via PHP
		// some filters we cannot apply in the initial SQL, because items must be paired
		// first before we can properly filter them.
		$results = array_filter( $results, function ( $row ) {
			/** @var Vehicle_Query_Database_Row $row */
			return true;
		} );

		// Final Sorting
		// Since items are paired with PHP we cannot rely on SQL to do the final sorting here.
		// For example, we cannot sort by package price until after we have chosen which tires/rims
		// will be shown in each result set. Before that, the price of each row is not defined.
		usort( $results, function ( $r1, $r2 ) {

			/** @var Vehicle_Query_Database_Row $r1 */
			/** @var Vehicle_Query_Database_Row $r2 */

			$p1 = $r1->get_total_price( $this->locale );
			$p2 = $r2->get_total_price( $this->locale );

			if ( $p1 != $p2 ) {
				return $p1 < $p2 ? -1 : 1;
			}

			// todo: have to know if tires/rims are selected to determine which brand name (tire/rim) to order by
			$cmp_brand = '';

			return 0;
		} );

		return $this->return_and_profile( $results, 'vehicle staggered pkg main query' );
	}

	/**
	 * In a staggered query for packages or rims, we can accurately choose 1 rim from each finish
	 * without knowing anything about the other rim. We just choose the lowest priced item
	 * from within the same finish ID that is also in stock.
	 *
	 * This is unlike pairing the cheapest set of in stock tire models, because with rims, we
	 * group finish first and pick each product from within that finish. For tires, we're actually
	 * choosing the tire model that has the cheapest in stock pair from among all tires, so
	 * we use SQL for that since it requires a pretty large inner join and massive sorting algorithm.
	 *
	 * @param $arr
	 */
	public static function group_rims_by_finish_and_select_in_stock_best_fit( $rims, $locale ) {

		// we may call this on rear rims even if query is not staggered
		if ( ! $rims ) {
			return array();
		}

		$finish_ids = static::get_unique_values( $rims, function ( $row ) {
			return $row->finish_id;
		}, true );

		$best_fit = array();

		// could check if the count of finish IDs is not less than the count of rims before looping.
		foreach ( $finish_ids as $finish_id ) {

			// collect rims with this finish
			$rims_by_finish = array_filter( $rims, function ( $row ) use ( $finish_id ) {
				$ret = $row->finish_id == $finish_id;

				return $ret;
			} );

			// sort all rims then choose the first one.
			// this is slightly less efficient than finding the min and then finding the
			// rim with that minimum value..
			usort( $rims_by_finish, function ( $r1, $r2 ) use( $locale ) {

				// need at least one set
				$in_stock_1 = static::is_in_stock( $r1, $locale );
				$in_stock_2 = static::is_in_stock( $r2, $locale );

				// prioritize in stock
				if ( $in_stock_1 !== $in_stock_2 ) {
					return $in_stock_1 > $in_stock_2 ? - 1 : 1;
				}

				$width_var_1 = abs( $r1->width - $r1->size[ 'width' ] );
				$width_var_2 = abs( $r2->width - $r2->size[ 'width' ] );

				// lowest width var trumps lowest offset var
				if ( $width_var_1 !== $width_var_2 ) {
					return $width_var_1 > $width_var_2 ? 1 : - 1;
				}

				$offset_var_1 = abs( (int) $r1->offset - (int) $r1->size[ 'offset' ] );
				$offset_var_2 = abs( (int) $r2->offset - (int) $r2->size[ 'offset' ] );

				// if more than 1 rim still remains after stock/width sort, choose
				// the one with the lowest offset
				if ( $offset_var_1 !== $offset_var_1 ) {
					return $offset_var_1 > $offset_var_2 ? 1 : - 1;
				}

				return 0;
			} );

			// logically this must be set
			$best_fit[] = array_values( $rims_by_finish )[ 0 ];
		}

		return $best_fit;
	}

	/**
	 * Queries a single (non-staggered) set of rims by vehicle fitment size array, and other filters if you provide
	 * them.
	 *
	 * The $size array must contain fitment_slug, front/rear/universal sizes etc., therefore you must
	 * also specify $loc (location).
	 *
	 * @param       $size
	 * @param       $loc
	 * @param       $locale
	 * @param array $rim_filters
	 * @param array $brand_filters
	 * @param array $model_filters
	 * @param array $finish_filters
	 *
	 * @return array
	 */
	public static function query_single_set_of_rims_by_size( $size, $loc, $locale, $pkg_type = '', $rim_filters = [], $brand_filters = [], $model_filters = [], $finish_filters = [] ) {

		assert( validate_rim_size_array( $size ), 1235123 );
		assert( in_array( $loc, [ 'front', 'rear', 'universal' ] ), 12372662312 );
		assert( app_is_locale_valid( $locale ), 1237126232 );

		// if above assertions pass then this won't fail
		$size_specific = $size[ 'rims' ][ $loc ];

		$db   = get_database_instance();
		$comp = new Query_Components_Rims( 'rims' );
		$comp->builder->add_to_self( $comp->get_size( $size ) );
		$comp->builder->add_to_self( DB_Tire::sql_assert_sold_and_not_discontinued_in_locale( 'rims' ) );

		// Rim Conditions
		$rim_filters['package_type'] = $pkg_type;
		$comp_rims = new Query_Components_Rims( 'rims' );
		$comp_rims->builder->add_to_self( $comp_rims->get_size( $size_specific ) );
		$comp_rims->apply_all_filters( $rim_filters );

		// Rim Finish Conditions
		$comp_finishes = new Query_Components_Rim_Finishes( 'rim_finishes' );
		$comp_finishes->builder->add_to_self( 'rim_finishes.rim_finish_id = rims.finish_id' );
		$comp_finishes->apply_all_filters( $finish_filters );

		// Rim Model Conditions
		$comp_models = new Query_Components_Rim_Models( 'rim_models' );
		$comp_models->builder->add_to_self( 'rim_models.rim_model_id = rims.model_id' );
		$comp_models->apply_all_filters( $model_filters );

		// Rim Brand Conditions
		$comp_brands = new Query_Components_Rim_Brands( 'rim_brands' );
		$comp_brands->builder->add_to_self( 'rim_brands.rim_brand_id = rims.brand_id' );
		$comp_brands->apply_all_filters( $brand_filters );

		$q = '';
		$p = array();

		$q .= 'SELECT * ';
		$q .= 'FROM rims ';

		// join brands, models, finishes, and apply all filters including the inner join conditions.
		foreach ( [ $comp_brands, $comp_models, $comp_finishes ] as $comp ) {
			$q .= 'INNER JOIN ' . $comp->get_table() . ' ON ' . $comp->builder->sql_with_placeholders() . ' ';
			$p = array_merge( $p, $comp->builder->parameters_array() );
		}

		// conditions for just rims, or general conditions not in the other components
		$q .= 'WHERE 6 = 6 ';
		$q .= 'AND ' . $comp_rims->builder->sql_with_placeholders() . ' ';
		$p = array_merge( $p, $comp_rims->builder->parameters_array() );

		$q .= ';';

		$results = $db->get_results( $q, $p );
		queue_dev_alert_for_query( 'get_rims_by_size_only', $q, $p, $results );

		// its important to add some derived columns to the result set.
		// this could be done more or less just as easily in the SQL select statement.
		// for sure, code relying on this function relies on these extra columns being present.
		if ( $results ) {
			$results = array_map( function ( $row ) use ( $size, $size_specific, $loc, $locale ) {
				$row->fitment_slug      = $size[ 'fitment_slug' ];
				$row->sub_slug          = gp_if_set( $size, 'sub_slug' );
				$row->staggered         = $size[ 'staggered' ];
				$row->oem               = $size[ 'oem' ];
				$row->size              = $size_specific;
				$row->loc               = $loc;
				$unit_locale_price      = $locale === APP_LOCALE_CANADA ? $row->price_ca : $row->price_us;
				$row->unit_locale_price = round( $unit_locale_price, 2 );
				return $row;
			}, $results );
		}

		return $results ? $results : array();
	}

	/**
	 * @param       $size
	 * @param       $package_type
	 * @param       $locale
	 * @param int   $limit
	 * @param array $t1_filters
	 * @param array $brand_filters
	 * @param array $model_filters
	 */
	public static function select_not_staggered_tires_ordered_by_package_best_recommended( $size, $package_type, $locale, $limit = 1, $t1_filters = array(), $brand_filters = array(), $model_filters = array() ) {

		assert( validate_tire_size_array( $size ), 'invalid tire size' );
		assert( app_is_locale_valid( $locale ), 'locale invalid' );
		assert( is_tire_type_valid( $package_type ), 'invalid package/tire type' );
		assert( $size['staggered'] == false, 'there is another fn. for staggered' );

		// Front Tires Conditions
		$comp_1 = new Query_Components_Tires( 'tires' );
		$comp_1->builder->add_to_self( $comp_1->get_size( $size[ 'tires' ][ 'universal' ] ) );
		$comp_1->builder->add_to_self( DB_Tire::sql_assert_sold_and_not_discontinued_in_locale( 'tires' ) );
		$comp_1->apply_all_filters( $t1_filters );

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
		$speed_rating_1 = gp_if_set( $size['tires']['universal'], 'speed_rating' );

		if ( $speed_rating_1 ) {
			$comp_1->builder->add_to_self( $comp_1->get_speed_rating_min_or_type_winter( $speed_rating_1, 'tire_models' ) );
		}

		// Tire Models Conditions
		$model_filters[ 'type' ] = $package_type;
		$comp_models             = new Query_Components_Tire_Models( 'tire_models' );
		$comp_models->builder->add_to_self( 'tire_models.tire_model_id = tires.model_id' );
		$comp_models->apply_all_filters( $model_filters );

		// Tire Brand Conditions
		$comp_brands = new Query_Components_Tire_Models( 'tire_brands' );
		$comp_brands->builder->add_to_self( 'tire_brands.tire_brand_id = tires.brand_id' );
		$comp_brands->apply_all_filters( $brand_filters );

		$db = get_database_instance();

		// Begin SQL
		$p = array();
		$q = '';

		$select = array();

		// apparently, has to be first thing to select
		$select[] = '*';

		// select price
		$price_col = $locale === APP_LOCALE_CANADA ? 'price_ca' : 'price_us';
		$select[] = 'CAST( ( tires.' . $price_col . '* 4 ) AS DECIMAL(7,2) ) AS total_price';

		// require 4 tires for non stg pkg
		if ( $locale === APP_LOCALE_US ) {
			$select[] = '( tires.stock_unlimited_us OR ( tires.stock_amt_us - tires.stock_sold_us ) >= 4 ) AS in_stock';
		} else {
			$select[] = '( tires.stock_unlimited_ca OR ( tires.stock_amt_ca - tires.stock_sold_ca ) >= 4 ) AS in_stock';
		}

		$q .= 'SELECT ' . implode_comma( $select ) . ' ';

		// Select Front Tires
		$q .= 'FROM ' . DB_tires . ' AS tires ';

		// Inner join models and brands
		/** @var Query_Components $comp */
		foreach ( [ $comp_brands, $comp_models ] as $comp ) {
			$q .= 'INNER JOIN ' . $comp->get_table()  . ' AS ' . $comp->get_table() . ' ';
			$q .= 'ON ' . $comp->builder->sql_with_placeholders() . ' ';
			$p = array_merge( $p, $comp->builder->parameters_array() );
		}

		// Front Size etc.
		$q .= 'AND ' . $comp_1->builder->sql_with_placeholders() . ' ';
		$p = array_merge( $p, $comp_1->builder->parameters_array() );

		// Where clause - not needed right now
		// $q .= 'WHERE 4 = 4 ';

		// Order By
		$order_by = array();

		// Look for cheapest, in stock tire pairs that fit and have the same model
		$order_by[] = 'in_stock DESC';
		$order_by[] = 'total_price ASC';

		// ensure deterministic results
		$order_by[] = 'tires.part_number';

		$q .= 'ORDER BY ' . implode_comma( $order_by ) . ' ';

		// maybe "LIMIT 0, 1"
		$q .= $limit ? get_sql_limit( 0, $limit ) : '';

		$results = $db->get_results( $q, $p );
		queue_dev_alert_for_query( 'non-stg tires for pkg', $q, $p, $results );

		$pairs = array();

		// return results in a universal format used for vehicle queries
		if ( $results ) {
			$pairs = array_map( function ( $row ) {
				$_row = new Vehicle_Query_Database_Row();
				$_row->setup_front_tire( $row, '' );
				$_row->setup_tire_model( $row, '' );
				$_row->setup_tire_brand( $row, '' );
				return $_row;
			}, $results );
		}

		return $pairs;
	}


	/**
	 * "package best recommended" currently means: in stock and lowest price.
	 *
	 * This query is quite similar to a staggered tire query (Tire_Query_Fitment_Sizes), however we only need to select
	 * 1 pair of tires here (ideally, in stock, and cheapest), and we require a package type here.
	 *
	 * 1 "pair" means, 2 front and 2 rear tires, not 1 pair of 4 tires.
	 *
	 * Package type is required, because in package queries, its very complex to pair different
	 * tires with different rims. Instead, each rim is paired with the same "recommended" tire(s), therefore,
	 * in both tires and rims, we force a package type. Otherwise, winter tires could be paired with non winter
	 * approved rims.
	 *
	 * We can inject $t1_filters['part_number'] and $t2_filters['part_number'] to see if 1 or 2
	 * pre-selected tires fit on a vehicle (can can be paired together - they must be of the same model)
	 *
	 * Set limit to 1 to return 1 row. However, in the future, we can set to -1, and potentially use PHP
	 * logic to pair different tires with different rims in a package query (ie. what I just mentioned above is very challenging).
	 *
	 * A note on staggered: we generally only need 2 tires to be considered in stock, but if the front and rear tires happen
	 * to have the same part number, then we need 4.
	 *
	 * This should return an array of Vehicle_Query_Database_Row objects.
	 *
	 * @param       $size
	 * @param       $package_type
	 * @param       $locale
	 * @param int   $limit
	 * @param array $t1_filters
	 * @param array $t2_filters
	 * @param array $model_filters
	 * @param array $brand_filters
	 *
	 * @return array
	 */
	public static function select_staggered_tires_ordered_by_package_best_recommended( $size, $package_type, $locale, $limit = 1, $t1_filters = array(), $t2_filters = array(), $model_filters = array(), $brand_filters = array() ) {

		assert( validate_tire_size_array( $size ), 'invalid tire size' );
		assert( app_is_locale_valid( $locale ), 'locale invalid' );
		assert( is_tire_type_valid( $package_type ), 'invalid package/tire type' );
		assert( $size['staggered'], 'there is another fn. for non-staggered' );

		// Front Tires Conditions
		$comp_1 = new Query_Components_Tires( 'front_tires' );
		$comp_1->builder->add_to_self( $comp_1->get_size( $size[ 'tires' ][ 'front' ] ) );
		$comp_1->builder->add_to_self( DB_Tire::sql_assert_sold_and_not_discontinued_in_locale( 'front_tires' ) );
		$comp_1->apply_all_filters( $t1_filters );

		// Rear Tires Conditions
		$comp_2 = new Query_Components_Tires( 'rear_tires' );
		$comp_2->builder->add_to_self( $comp_2->get_size( $size[ 'tires' ][ 'rear' ] ) );
		$comp_2->builder->add_to_self( DB_Tire::sql_assert_sold_and_not_discontinued_in_locale( 'rear_tires' ) );
		$comp_2->apply_all_filters( $t2_filters );

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
		$speed_rating_1 = gp_if_set( $size['tires']['front'], 'speed_rating' );
		$speed_rating_2 = gp_if_set( $size['tires']['rear'], 'speed_rating' );
		$speed_rating_2 = $speed_rating_2 ? $speed_rating_2 : $speed_rating_1;

		if ( $speed_rating_1 ) {
			$comp_1->builder->add_to_self( $comp_1->get_speed_rating_min_or_type_winter( $speed_rating_1, 'front_tire_models' ) );
		}

		if ( $speed_rating_2 ) {
			$comp_2->builder->add_to_self( $comp_2->get_speed_rating_min_or_type_winter( $speed_rating_2, 'front_tire_models' ) );
		}

		// Front Tire Models Conditions
		$model_filters[ 'type' ] = $package_type;
		$comp_models             = new Query_Components_Tire_Models( 'front_tire_models' );
		$comp_models->builder->add_to_self( 'front_tire_models.tire_model_id = front_tires.model_id' );
		$comp_models->apply_all_filters( $model_filters );

		// Front Tire Brand Conditions
		$comp_brands = new Query_Components_Tire_Models( 'front_tire_brands' );
		$comp_brands->builder->add_to_self( 'front_tire_brands.tire_brand_id = front_tires.brand_id' );
		$comp_brands->apply_all_filters( $brand_filters );

		$db = get_database_instance();

		// Begin SQL
		$p = array();
		$q = '';

		$select = array();

		$price_col = $locale === APP_LOCALE_CANADA ? 'price_ca' : 'price_us';

		$select[] = 'CAST( ( front_tires.' . $price_col . ' * 2 ) AS DECIMAL(7,2) ) + CAST( ( rear_tires.' . $price_col . ' * 2 ) AS DECIMAL(7,2) ) AS total_price';


		// we can add these to the return value so we shouldn't need them to show up in the results
		//    $select[] = ':sub_slug AS sub_slug';
		//    $select[] = ':fitment_slug AS fitment_slug';
		//    $select[] = ':oem AS oem';
		//
		//    // sub slug is set sometimes, oem/fitment are expected
		//    $p[] = array( 'fitment_slug', $size['fitment_slug'] );
		//    $p[] = array( 'oem', $size['oem'] );
		//    $p[] = array( 'sub_slug', gp_if_set( $size, 'sub_slug' ) );

		// only requiring 2 tires to be considered in stock


		if ( $locale === APP_LOCALE_US ) {
			$select[] = 'CASE WHEN front_tires.stock_unlimited_us THEN 999 ELSE ( front_tires.stock_amt_us - front_tires.stock_sold_us ) END AS stock_1';
			$select[] = 'CASE WHEN rear_tires.stock_unlimited_us THEN 999 ELSE ( rear_tires.stock_amt_us - rear_tires.stock_sold_us ) END AS stock_2';
		} else {
			$select[] = 'CASE WHEN front_tires.stock_unlimited_ca THEN 999 ELSE ( front_tires.stock_amt_ca - front_tires.stock_sold_ca ) END AS stock_1';
			$select[] = 'CASE WHEN rear_tires.stock_unlimited_ca THEN 999 ELSE ( rear_tires.stock_amt_ca - rear_tires.stock_sold_ca ) END AS stock_2';
		}


//		$select[] = '( front_tires.stock_unlimited OR ( front_tires.stock - front_tires.stock_sold ) >= 2 ) AS front_tires_in_stock';
//		$select[] = '( rear_tires.stock_unlimited OR ( rear_tires.stock - rear_tires.stock_sold ) >= 2 ) AS rear_tires_in_stock';

		// I hate to do this but select * does not play nice when joining a table onto itself..
		// so now we have to say... SELECT front_tires.tire_id AS t1_tire_id, front_tires... etc., about 50 times
		$select[] = DB_Tire::prefix_alias_select( 'front_tires', 't1_' );
		$select[] = DB_Tire::prefix_alias_select( 'rear_tires', 't2_' );
		$select[] = DB_Tire_Model::prefix_alias_select( 'front_tire_models', 'tm1_' );
		$select[] = DB_Tire_Brand::prefix_alias_select( 'front_tire_brands', 'tb1_' );

		$q .= 'SELECT ' . implode_comma( $select ) . ' ';

		// Select Front Tires
		$q .= 'FROM ' . DB_tires . ' AS front_tires ';

		// Join Front Models
		$q .= 'INNER JOIN ' . DB_tire_models . ' AS front_tire_models ';
		$q .= 'ON ' . $comp_models->builder->sql_with_placeholders() . ' ';
		$p = array_merge( $p, $comp_models->builder->parameters_array() );

		// Join Front Brands - don't need rear, rear brand will === front brand
		$q .= 'INNER JOIN ' . DB_tire_brands . ' AS front_tire_brands ';
		$q .= 'ON ' . $comp_brands->builder->sql_with_placeholders() . ' ';
		$p = array_merge( $p, $comp_brands->builder->parameters_array() );

		// Join Rear Tires
		$q .= 'INNER JOIN ' . DB_tires . ' AS rear_tires ';
		$q .= 'ON 1 = 1 ';

		// Front/Rear tires must have same model
		$q .= 'AND rear_tires.model_id = front_tires.model_id ';

		// Front Size etc.
		$q .= 'AND ' . $comp_1->builder->sql_with_placeholders() . ' ';
		$p = array_merge( $p, $comp_1->builder->parameters_array() );

		// Rear size etc.
		$q .= 'AND ' . $comp_2->builder->sql_with_placeholders() . ' ';
		$p = array_merge( $p, $comp_2->builder->parameters_array() );

		// Where clause
		$q .= 'WHERE 4 = 4 ';

		// Order By
		$order_by = array();

		// in stock first
		$order_by[] = 'CASE WHEN t1_part_number = t2_part_number THEN ( stock_1 >= 4 AND stock_2 >= 4 ) ELSE ( stock_1 >= 2 AND stock_2 >= 2 ) END DESC';

		// then price
		$order_by[] = 'total_price ASC';

		// this is not necessary but i think it doesn't hurt.
		$order_by[] = 'front_tires.part_number = rear_tires.part_number';

		// ensure deterministic results
		$order_by[] = 'front_tires.part_number';
		$order_by[] = 'rear_tires.part_number';

		$q .= 'ORDER BY ' . implode_comma( $order_by ) . ' ';

		// maybe "LIMIT 0, 1"
		$q .= $limit ? get_sql_limit( 0, $limit ) : '';

		$results = $db->get_results( $q, $p );
		queue_dev_alert_for_query( 'stg tires new', $q, $p, $results );

		$pairs = array();

		if ( $results ) {
			$pairs = array_map( function ( $row ) {
				$_row = new Vehicle_Query_Database_Row();
				$_row->setup_front_tire( $row, 't1_' );
				$_row->setup_rear_tire( $row, 't2_' );
				$_row->setup_tire_model( $row, 'tm1_' );
				$_row->setup_tire_brand( $row, 'tb1_' );
				return $_row;
			}, $results );
		}

		return $pairs;
	}

}