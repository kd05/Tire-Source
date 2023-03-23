<?php

Class Tests{

	public static function stock_level_updates_and_best_fit_rims(){

	}

	/**
	 *
	 */
	public static function vehicle_api_auth(){


		$make = 'bmw';
		$model = '3-series';
		$year = 2015;
		$trim = '316d-vi-lci';
		$fitment = '225-45R18-8Jx18_ET34';

		$data = array(
			'make' => $make,
			'model' => $model,
			'year' => $year,
			'trim' => $trim,
			'fitment' => $fitment,
		);

		$v = get_model_info( 'bmw', '3-series' );

		echo '<pre>' . print_r( Debug::render( true ), true ) . '</pre>';

	}

	/**
	 *
	 */
	public static function get_vehicle_data_from_api(){


		$make = 'bmw';
		$model = '3-series';
		$year = 2015;
		$trim = '316d-vi-lci';
		$fitment = '225-45R18-8Jx18_ET34';

		$data = array(
			'make' => $make,
			'model' => $model,
			'year' => $year,
			'trim' => $trim,
			'fitment' => $fitment,
		);

		$v = get_model_info( 'bmw', '3-series' );

		echo '<pre>' . print_r( $v, true ) . '</pre>';

		echo nl2br( "-----------------------  \n" );

		$f = get_fitment_data( $make, $model, $year, $trim );

		echo '<pre>' . print_r( $f, true ) . '</pre>';

	}

	/**
	 * @return array
	 */
	public static function rims_with_same_finish_slugs_but_different_finish_ids(){

		$select = array();
		$select[] = DB_Rim::prefix_alias_select( 'rims', 'r1_' );
		$select[] = DB_Rim::prefix_alias_select( 'rims_2', 'r2_' );

		$db = get_database_instance();

		$q = '';
		$p = array();
		$q .= 'SELECT ' . implode_comma( $select ) . ' ';
		$q .= 'FROM rims ';
		$q .= '';
		// $q .= 'INNER JOIN rims AS rims_2 ON rims.finish_id = rims_2.finish_id AND ( rims.color_1 <> rims_2.color_1 OR rims.color_2 <> rims_2.color_2 OR rims.finish <> rims_2.finish )';

		$q .= 'INNER JOIN rims AS rims_2 ON rims.color_1 = rims_2.color_1 AND rims.color_2 = rims_2.color_2 AND rims.finish = rims_2.finish AND rims.finish_id <> rims_2.finish_id ';

		$q .= 'WHERE 1 = 1 ';
		$q .= '';

		$q .= '';
		// $q .= 'LIMIT 0, 1000 ';
		$q .= ';';

		$results = $db->get_results( $q, $p );
		return $results;
	}

	/**
	 *
	 */
	public static function rims_with_finish_slugs_that_dont_match_finish_id(){

		$db = get_database_instance();

		$q = '';
		$p = array();
		$select = array();
		$select[] = DB_Rim_Finish::prefix_alias_select( 'rim_finishes', 'ff_' );
		$select[] = DB_Rim::prefix_alias_select( 'rims', 'rr_' );
		$q .= 'SELECT ' . implode_comma( $select ) . ' ';
		$q .= 'FROM rim_finishes ';
		$q .= 'INNER JOIN rims ON ( rims.finish_id = rim_finishes.rim_finish_id AND ( rims.color_1 <> rim_finishes.color_1 OR rims.color_2 <> rim_finishes.color_2 OR rims.finish <> rim_finishes.finish ) ) ';
		$q .= '';
		$q .= 'WHERE 1 = 1 ';

		$q .= 'ORDER BY rim_finishes.rim_finish_id ';
		//$q .= 'AND (';
		//$q .= ')';

		$q .= '';
		// $q .= 'LIMIT 0, 1000 ';
		$q .= ';';

		$results = $db->get_results( $q, $p );

		return $results;
	}

	/**
	 * try rims_with_finish_slugs_that_dont_match_finish_id() maybe..
	 * this one runs out of memory pretty fast..
	 *
	 * @return array
	 */
	public static function rims_with_same_finish_id_but_different_finish_slugs(){

		$select = array();
		$select[] = DB_Rim::prefix_alias_select( 'rims', 'r1_' );
		$select[] = DB_Rim::prefix_alias_select( 'rims_2', 'r2_' );

		$db = get_database_instance();

		$q = '';
		$p = array();
		$q .= 'SELECT ' . implode_comma( $select ) . ' ';
		$q .= 'FROM rims ';
		$q .= '';
		$q .= 'INNER JOIN rims AS rims_2 ON rims.finish_id = rims_2.finish_id AND ( rims.color_1 <> rims_2.color_1 OR rims.color_2 <> rims_2.color_2 OR rims.finish <> rims_2.finish )';
		$q .= 'WHERE 1 = 1 ';
		$q .= '';

		$q .= '';
		$q .= 'LIMIT 0, 1000 ';
		$q .= ';';

		$results = $db->get_results( $q, $p );
		return $results;
	}

	/**
	 * you can use when inner joining a table on itself, along with
	 * DB_Table::prefix_alias_select().
	 *
	 * @param $cols
	 * @param $prefixes
	 *
	 * @return array
	 */
	public static function make_table_cols_array( $cols, $prefixes ) {
		$cols = $cols ? $cols : array();
		$prefixes = $prefixes ? $prefixes : array( '' );
		$ret = array();

		if ( $prefixes ) {
			foreach ( $prefixes as $pre ) {
				if ( $cols ) {
					foreach ( $cols as $c1=>$c2 ) {
						$ret[] = $pre . $c2;
					}
				}
			}
		}

		return $ret;
	}
}