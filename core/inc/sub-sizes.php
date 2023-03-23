<?php

/**
 * A pair of front + rear tire atts, or just front, and rear is empty
 *
 * Class Tire_Atts_Pair
 */
Class Tire_Atts_Pair{

	/**
	 * @var Tire_Atts_Single
	 */
	public $front;

	/**
	 * @var Tire_Atts_Single
	 */
	public $rear;

	/**
	 * ie. 2255018/2454518
	 *
	 * @var
	 */
	public $input;

	/**
	 * @var
	 */
	public $sep;

	/** @var bool  */
	public $valid;

	/**
	 * @var bool
	 */
	public $staggered;

	/**
	 * consider using simplify_tire_size() on $str before passing it in, but
	 * not when handling front-end user input from $_GET['sub'], instead use
	 * self::create_from_url_slug(). For example, when you use __construct(), strings
	 * MUST be 7 characters long or be staggered.
	 *
	 * Tire_Atts_Pair constructor.
	 *
	 * @param $str
	 */
	public function __construct( $str, $sep = '-' ){

		$this->sep = gp_test_input( $sep );
		$this->input = $str;
		$this->valid = false;
		$this->staggered = null;

		if ( strlen( $str ) === 7 ) {
			$this->front = new Tire_Atts_Single( $str );
			$this->valid = $this->front->valid;

			// leave null I suppose if size is not valid
			$this->staggered = $this->valid ? false : $this->staggered;

		} else {

			$this->front = new Tire_Atts_Single( '' );
		}

		if ( strpos( $str, $sep ) !== false ) {

			$arr = explode( $sep, $str );

			if ( count( $arr ) === 2 ) {

				$this->front = new Tire_Atts_Single( $arr[0] );
				$this->rear = new Tire_Atts_Single( $arr[1] );

				$this->staggered = true;

				if ( $this->front->valid && $this->rear->valid ) {
					$this->valid = true;
				}
			}
		}

		// prefer to have empty objects rather than null
		$this->front = $this->front ? $this->front : new Tire_Atts_Single( '' );
		$this->rear = $this->rear ? $this->rear : new Tire_Atts_Single( '' );
	}

	/**
	 * Example: 225-50-18, or 225-50-18_225-40-19
	 *
	 * @param        $input
	 * @param string $stg_sep
	 * @param string $size_sep
	 *
	 * @return bool|Tire_Atts_Pair
	 */
	public static function create_from_url_slug( $input, $stg_sep = '_', $size_sep = '-' ) {

		$input = gp_force_singular( $input );

		// explode on separator, remove all non-numeric, then re-assemble
		// this makes initial input like 225-50-17 valid, as well as
		// 225-50-17_225-40-18, as well as 225-50R18, etc. etc.
		if ( strpos( $input, $stg_sep ) !== false ) {
			$arr = explode( $stg_sep, $input );

			$stg = true;

			$t1 = gp_if_set( $arr, 0 );
			$t2 = gp_if_set( $arr, 1 );

		} else {

			$stg = false;
			$t1 = $input;
			$t2 = false;
		}

		$t1_arr = explode( $size_sep, $t1 );

		$w1 = gp_if_set( $t1_arr, 0 );
		$p1 = gp_if_set( $t1_arr, 1 );
		$d1 = gp_if_set( $t1_arr, 2 );
		$w2 = '';
		$p2 = '';
		$d2 = '';

		if ( $stg ) {

			$t2_arr = explode( $size_sep, $t2 );

			$w2 = gp_if_set( $t2_arr, 0 );
			$p2 = gp_if_set( $t2_arr, 1 );
			$d2 = gp_if_set( $t2_arr, 2 );
		}

		$obj = static::create_manually( $w1, $p1, $d1, $w2, $p2, $d2, $stg_sep );

		if ( $obj->valid ) {
			$obj->input = $input;
			return $obj;
		}

		return false;
	}

	/**
	 * When you don't have a string in the ridiculous format of "2255018", you can
	 * use this to create an instance from specific tire attributes
	 *
	 * @param $w1
	 * @param $p1
	 * @param $d1
	 * @param $w2
	 * @param $p2
	 * @param $d2
	 */
	public static function create_manually( $w1, $p1, $d1, $w2, $p2, $d2, $sep = '-' ) {

		$str = '';

		if ( $w1 || $p1 || $d1 ) {
			$str .= $w1 . $p1 . $d1;
		}

		if ( $w2 || $p2 || $d2 ) {
			$str .= $sep;
			$str .= $w2 . $p2 . $d2;
		}

		return new static( $str, $sep );
	}

	/**
	 * May or may not return an identical value to $this->input. its also
	 * possible that $this->input was a derived value, like if we used
	 * create_manually()
	 */
	public function convert_back_to_string(){

		$op = '';
		$op .= $this->front->width . $this->front->profile . $this->front->diameter;

		if ( $this->staggered ) {
			$op .= $this->sep;
			$op .= $this->rear->width . $this->rear->profile . $this->rear->diameter;
		}

		$op = gp_test_input( $op );

		return $op;
	}

	/**
	 * the slug is generally passed around in the URL, therefore
	 * this must be a unique identifier of this particular substitution size.
	 */
	public function get_slug(){

		$op = '';

		$f = [
			$this->front->width,
			$this->front->profile,
			$this->front->diameter
		];

		$op .= implode( '-', $f );

		if ( $this->staggered ) {

			$r = [
				$this->rear->width,
				$this->rear->profile,
				$this->rear->diameter
			];

			$op .= '_';
			$op .= implode( '-', $r );
		}

		$op = gp_test_input( $op );
		return $op;
	}

	/**
	 *
	 */
	public function get_nice_name( $sep = null ){

		if ( ! $this->valid ) {
			return gp_test_input( $this->input );
		}

		$op = '';
		$op .= $this->front->get_nice_name();

		if ( $this->staggered ) {
			$op .= $sep !== null ? $sep : $this->sep;
			$op .= $this->rear->get_nice_name();
		}

		return $op;
	}
}

/**
 * A single (front or rear) tire size
 *
 * Class Tire_Atts_Single
 */
Class Tire_Atts_Single{

	/**
	 * 2255018
	 *
	 * @var
	 */
	public $input;

	/** @var bool  */
	public $valid;

	/**
	 * 225
	 *
	 * @var bool|string
	 */
	public $width;

	/**
	 * 50
	 *
	 * @var bool|string
	 */
	public $profile;

	/**
	 * 18
	 *
	 * @var
	 */
	public $diameter;

	// public $diameter_inches;

	/**
	 * @var bool|int|string
	 */
	public $profile_inches;

	/**
	 * Ie. the diameter on the outside of the tire.
	 *
	 * @var int
	 */
	public $overall_diameter;

	/**
	 * optional_todo: maybe allow $input to be non-simple, like 225/50R18, or LT220/75ZR20
	 *
	 * Tire_Atts_Single constructor.
	 *
	 * @param $input
	 */
	public function __construct( $input ){

		$this->input = $input;

		if ( gp_is_integer( $input ) && strlen( $input ) === 7 ) {
			$this->input = $input;
			$this->width = (int) substr( $input, 0, 3 );
			$this->profile = (int) substr( $input, 3, 2 );
			$this->diameter = (int) substr( $input, 5, 2 );

			$this->width_inches = $this->width * MM_TO_INCHES;
			$this->profile_inches  = ( $this->width_inches * ( $this->profile / 100 ) );
			$this->overall_diameter = $this->diameter + ( $this->profile_inches * 2 );

			// valid profiles are multiples of 5, normally between about 30-65
			if ( $this->profile % 5 !== 0 ) {
				$this->valid = false;
				return;
			}

			// widths are like... 225 or 215..
			// technically, I don't think 220 is "valid" but i'm not checking that.
			if ( $this->width % 5 !== 0 ) {
				$this->valid = false;
				return;
			}

			// have to be careful of strings like 1110000
			if ( $this->width > 0 && $this->profile > 0 && $this->diameter > 0 ) {
				$this->valid = true;
			} else {
				$this->valid = false;
			}

		} else {
			$this->valid = false;
		}
	}

	/**
	 * @param $width
	 * @param $profile
	 * @param $diameter
	 */
	public static function create_from_atts( $width, $profile, $diameter ) {

		$string = (int) $width . (int) $profile . (int) $diameter;

		$obj = new static( $string );

		if ( $obj->valid ) {
			return $obj;
		}

		return false;
	}

	/**
	 *
	 */
	public function get_nice_name(){

		if ( ! $this->valid ) {
			return 'invalid: ' . gp_test_input( $this->input );
		}

		$op = '';
		$op .= (int) $this->width;
		$op .= '/' . (int) $this->profile;

		// R is more correct, but / is easier to read
		// $op .= 'R' . (int) $this->diameter;
		$op .= '/' . (int) $this->diameter;
		return $op;
	}
}

/**
 * @param $results
 */
function get_sub_size_table_data( $results ) {

	$table = array();

	if ( $results && is_array( $results ) ) {
		foreach ( $results as $row ) {
			$db_sub_size = DB_Sub_Size::create_instance_or_null( $row );
			$table[] = $db_sub_size->get_admin_table_row();
		}
	}

	return $table;
}

/**
 * Don't forget to DELETE FIRST, otherwise, we're going to have duplicates
 *
 * @param Tire_Atts_Pair $target_size
 * @param array Tire_Atts_Pair[] $sub_sizes
 *
 * @return bool
 */
function insert_sub_sizes( Tire_Atts_Pair $target_size, array $sub_sizes, &$errors ){

	$db = get_database_instance();

	$errors = is_array( $errors ) ? $errors : array();

	if ( $sub_sizes ) {

		/** @var Tire_Atts_Pair $sub_size */
		foreach ( $sub_sizes as $sub_size ) {

			if ( ! $sub_size instanceof Tire_Atts_Pair ) {
				throw_dev_error( 'Invalid array of sub sizes, must objects' );
				break;
			}

			// indicate initial strings so we know which sizes did not get inserted
			$initial_string = gp_test_input( $sub_size->input );
			$error_prefix = '[error: ' . $initial_string . ']';

			if ( ! $target_size->valid ) {
				$errors[] = 'Target size is not valid.';
				continue;
			}

			if ( ! $sub_size->valid ) {
				$errors[] = $error_prefix . ' Size provided is not valid.';
				continue;
			}

			if ( $target_size->staggered && ! $sub_size->staggered ) {
				$errors[] = $error_prefix . ' size is staggered but sub size is not.';
				continue;
			}

			if ( ! $target_size->staggered && $sub_size->staggered ) {
				$errors[] = $error_prefix . 'Sub size is staggered but target size is not.';
				continue;
			}

			if ( $target_size->staggered ) {

				$insert = array(
					'target_width_1' => $target_size->front->width,
					'target_profile_1' => $target_size->front->profile,
					'target_diameter_1' => $target_size->front->diameter,
					'target_width_2' => $target_size->rear->width,
					'target_profile_2' => $target_size->rear->profile,
					'target_diameter_2' => $target_size->rear->diameter,
					'sub_width_1' => $sub_size->front->width,
					'sub_profile_1' => $sub_size->front->profile,
					'sub_diameter_1' => $sub_size->front->diameter,
					'sub_width_2' => $sub_size->rear->width,
					'sub_profile_2' => $sub_size->rear->profile,
					'sub_diameter_2' => $sub_size->rear->diameter,
				);

			} else {

				$insert = array(
					'target_width_1' => $target_size->front->width,
					'target_profile_1' => $target_size->front->profile,
					'target_diameter_1' => $target_size->front->diameter,
					'sub_width_1' => $sub_size->front->width,
					'sub_profile_1' => $sub_size->front->profile,
					'sub_diameter_1' => $sub_size->front->diameter,
				);

			}

			$inserted = $db->insert( $db->sub_sizes, $insert );

			if ( ! $inserted ) {
				$errors[] = $error_prefix . ' Size could not be inserted.';
			}
		}
	}
}

/**
 * @param Tire_Atts_Pair $size
 *
 * @return mixed
 */
function delete_sub_sizes( Tire_Atts_Pair $size ) {

	$db = get_database_instance();
	$p = [];
	$q = '';
	$q .= 'DELETE ';
	$q .= 'FROM ' . $db->sub_sizes . ' ';
	$q .= 'WHERE 1 = 1 ';

	// Is staggered
	if ( $size->staggered ) {

		$q .= 'AND target_width_1 = :target_width_1 ';
		$q .= 'AND target_profile_1 = :target_profile_1 ';
		$q .= 'AND target_diameter_1 = :target_diameter_1 ';

		$p = array_merge( $p, array(
			['target_width_1', $size->front->width],
			['target_profile_1', $size->front->profile],
			['target_diameter_1', $size->front->diameter],
		));

		$q .= 'AND target_width_2 = :target_width_2 ';
		$q .= 'AND target_profile_2 = :target_profile_2 ';
		$q .= 'AND target_diameter_2 = :target_diameter_2 ';

		$p = array_merge( $p, array(
			['target_width_2', $size->rear->width],
			['target_profile_2', $size->rear->profile],
			['target_diameter_2', $size->rear->diameter],
		));

	} else {

		$q .= 'AND target_width_1 = :target_width_1 ';
		$q .= 'AND target_profile_1 = :target_profile_1 ';
		$q .= 'AND target_diameter_1 = :target_diameter_1 ';

		$p = array_merge( $p, array(
			['target_width_1', $size->front->width],
			['target_profile_1', $size->front->profile],
			['target_diameter_1', $size->front->diameter],
		));

		$q .= 'AND ( target_width_2 = :target_width_2 OR target_width_2 IS NULL ) ';
		$q .= 'AND ( target_profile_2 = :target_profile_2 OR target_profile_2 IS NULL ) ';
		$q .= 'AND ( target_diameter_2 = :target_diameter_2 OR target_diameter_2 IS NULL ) ';

		// CAREFUL not to delete substitution sizes with the same front size
		$p = array_merge( $p, array(
			['target_width_2', ''],
			['target_profile_2', ''],
			['target_diameter_2', ''],
		));

	}

	$q .= '';
	$q .= '';
	$q .= ';';

	$delete = $db->execute( $q, $p );

	return $delete;
}


/**
 * include just a first parameter to get all sizes that match. include a second param
 * to see if the sub size is found for a given target size.
 *
 * @param Tire_Atts_Pair $target_size
 * @param Tire_Atts_Pair $sub_size
 *
 * @return array
 */
function get_sub_sizes( Tire_Atts_Pair $target_size, $sub_size = null ) {

	if ( ! $target_size->valid ) {
		return array();
	}

	if ( $sub_size ){
		if ( ! $sub_size instanceof Tire_Atts_Pair ) {
			throw_dev_error( 'invalid sub size' );
			exit;
		}
	}

	$db = get_database_instance();
	$p  = [];
	$q  = '';
	$q  .= 'SELECT * ';
	$q  .= 'FROM ' . $db->sub_sizes . ' ';
	$q  .= '';
	$q  .= 'WHERE 1 = 1 ';

	// not staggered
	if ( ! $target_size->staggered ) {

		$q .= 'AND target_width_1 = :target_width_1 ';
		$q .= 'AND target_profile_1 = :target_profile_1 ';
		$q .= 'AND target_diameter_1 = :target_diameter_1 ';

		$q .= 'AND ( target_width_2 = "" OR target_width_2 IS NULL ) ';
		$q .= 'AND ( target_profile_2 = "" OR target_profile_2 IS NULL ) ';
		$q .= 'AND ( target_diameter_2 = "" OR target_diameter_2 IS NULL ) ';

		$p = array_merge( $p, array(
			[ 'target_width_1', $target_size->front->width, '%d' ],
			[ 'target_profile_1', $target_size->front->profile, '%d' ],
			[ 'target_diameter_1', $target_size->front->diameter, '%d' ],
		) );

	} else {

		$q .= 'AND target_width_1 = :target_width_1 ';
		$q .= 'AND target_profile_1 = :target_profile_1 ';
		$q .= 'AND target_diameter_1 = :target_diameter_1 ';
		$q .= 'AND target_width_2 = :target_width_2 ';
		$q .= 'AND target_profile_2 = :target_profile_2 ';
		$q .= 'AND target_diameter_2 = :target_diameter_2 ';

		$p = array_merge( $p, array(
			[ 'target_width_1', $target_size->front->width, '%d' ],
			[ 'target_profile_1', $target_size->front->profile, '%d' ],
			[ 'target_diameter_1', $target_size->front->diameter, '%d' ],
			[ 'target_width_2', $target_size->rear->width, '%d' ],
			[ 'target_profile_2', $target_size->rear->profile, '%d' ],
			[ 'target_diameter_2', $target_size->rear->diameter, '%d' ],
		) );

	}

	if ( $sub_size ){

		// not staggered
		if ( ! $sub_size->staggered ) {

			$q .= 'AND sub_width_1 = :sub_width_1 ';
			$q .= 'AND sub_profile_1 = :sub_profile_1 ';
			$q .= 'AND sub_diameter_1 = :sub_diameter_1 ';
			$q .= 'AND ( sub_width_2 = "" OR sub_width_2 IS NULL ) ';
			$q .= 'AND ( sub_profile_2 = "" OR sub_profile_2 IS NULL ) ';
			$q .= 'AND ( sub_diameter_2 = "" OR sub_diameter_2 IS NULL ) ';

			$p = array_merge( $p, array(
				[ 'sub_width_1', $sub_size->front->width, '%d' ],
				[ 'sub_profile_1', $sub_size->front->profile, '%d' ],
				[ 'sub_diameter_1', $sub_size->front->diameter, '%d' ],
			) );

		} else {

			$q .= 'AND sub_width_1 = :sub_width_1 ';
			$q .= 'AND sub_profile_1 = :sub_profile_1 ';
			$q .= 'AND sub_diameter_1 = :sub_diameter_1 ';
			$q .= 'AND sub_width_2 = :sub_width_2 ';
			$q .= 'AND sub_profile_2 = :sub_profile_2 ';
			$q .= 'AND sub_diameter_2 = :sub_diameter_2 ';

			$p = array_merge( $p, array(
				[ 'sub_width_1', $sub_size->front->width, '%d' ],
				[ 'sub_profile_1', $sub_size->front->profile, '%d' ],
				[ 'sub_diameter_1', $sub_size->front->diameter, '%d' ],
				[ 'sub_width_2', $sub_size->rear->width, '%d' ],
				[ 'sub_profile_2', $sub_size->rear->profile, '%d' ],
				[ 'sub_diameter_2', $sub_size->rear->diameter, '%d' ],
			) );

		}
	}

	$order_by = array(
		'target_diameter_1 ASC',
		'target_width_1 ASC',
		'target_profile_1 ASC',
		'target_diameter_2 ASC',
		'target_width_2 ASC',
		'target_profile_2 ASC',
		'sub_diameter_1 ASC',
		'sub_width_1 ASC',
		'sub_profile_1 ASC',
		'sub_diameter_2 ASC',
		'sub_width_2 ASC',
		'sub_profile_2 ASC',
	);

	if ( $order_by ) {
		$q .= 'ORDER BY ' . implode_comma( $order_by ) . ' ';
	}

	// semi-colon
	$q .= ';';

	$results = $db->get_results( $q, $p );

	return $results;
}

/**
 * try to remove all characters except numbers, and a possible dash to indicate
 * 2 sizes in one (staggered)
 *
 * @param $str
 */
function simplify_tire_size( $str ) {
	//Make alphanumeric (removes all other characters)
	$str = preg_replace( "/[^0-9-]/", "", $str );
	$str = trim( $str );
	return $str;
}

/**
 * @param string $tire_size_str
 * @param array  $args
 */
function get_edit_sub_size_url( $tire_size_str ) {

	$tire_size_str = simplify_tire_size( $tire_size_str );

	$base = get_admin_page_url( 'sub_sizes' );

	$args = [
		'target' => $tire_size_str,
	];

	return cw_add_query_arg( $args, $base );
}

/**
 * @param       $tire_size_str
 * @param array $args
 *
 * @return string
 */
function link_to_edit_sub_size( $tire_size_str, $args = array() ) {
	$args['href'] = get_edit_sub_size_url( $tire_size_str );
	$args['text'] = $tire_size_str;
	return get_anchor_tag( $args );
}

/**
 * @param $results
 */
function query_results_get_first( $results ) {

	if ( ! $results ) {
		return array();
	}

	$first = gp_if_set( $results, 0, false );

	if ( ! $first ) {
		return array();
	}

	// it might be a stdClass
	$first = gp_make_array( $first );

	return $first;
}

/**
 * @param $user_input
 */
function make_sub_slug_valid( $user_input ) {

	$obj = Tire_Atts_Pair::create_from_url_slug( $user_input, '_' );

	if ( $obj->valid ) {
		return $obj->convert_back_to_string();
	}

	return false;
}

/**
 * Ie. is $_GET['sub'] in a valid format that represents a tire size, either
 * staggered or non staggered.
 *
 * This is valid: 195-50-16. This is also valid: 195-50-16_205-50-17
 */
function is_sub_slug_valid( $user_input ){
	// create a testing object and see if the object is valid
	$obj = Tire_Atts_Pair::create_from_url_slug( $user_input, '_' );

	$ret = $obj->valid;
	return $ret;
}


/**
 * A group is the collection of a single target size with multiple sub sizes,
 * each of these "sizes" is their own object, which may or may not be staggered.
 *
 * Also... I used this to generate a testing list of fake sub sizes. I can't remember
 * or tell right now if it has a use other than generating fake data.
 *
 * Class Sub_Size_Group
 */
Class Sub_Size_Group{

	public $base_width;
	public $base_profile;
	public $base_diameter;

	public $w_var;
	public $p_var;
	public $d_var;

	public $variations;

	public $db_subs;

	/** @var bool|Tire_Atts_Single */
	public $target;

	/**
	 * Sub_Size_Group constructor.
	 *
	 * @param $width
	 * @param $profile
	 * @param $diameter
	 */
	public function __construct( $width, $profile, $diameter ) {

		$this->w_var = array();
		$this->p_var = array();
		$this->d_var = array();
		$this->db_subs = array();

		$this->base_width = $width;
		$this->base_profile = $profile;
		$this->base_diameter = $diameter;

		$this->target = Tire_Atts_Single::create_from_atts( $this->base_width, $this->base_profile, $this->base_diameter );
	}

	/**
	 *
	 */
	public function make_variations(){

		$ret = array();

		if ( $this->w_var ) {
			foreach ( $this->w_var as $wv ) {

				$_w = $this->base_width + $wv;

				if ( $this->p_var ) {
					foreach ( $this->p_var as $pv ){

						$_p = $this->base_profile+ $pv;

						if ( $this->d_var ) {
							foreach ( $this->d_var  as $dv ) {
								$_d = $this->base_diameter + $dv;

								$obj = Tire_Atts_Single::create_from_atts( $_w, $_p, $_d );

								if ( $obj ) {
									$ret[] = $obj;
								} else {
									throw_dev_error( 'error 123123123' );
									exit;
								}
							}
						}
					}
				}
			}
		}

		$db_subs = array();
		if ( $ret ) {

			/** @var Tire_Atts_Single $r2 */
			foreach ( $ret as $t ) {

				$data = array();

				$data['target_width_1'] = $this->base_width;
				$data['target_profile_1'] = $this->base_profile;
				$data['target_diameter_1'] = $this->base_diameter;

				$data['sub_width_1'] = $t->width;
				$data['sub_profile_1'] = $t->profile;
				$data['sub_diameter_1'] = $t->diameter;

				$db_sub = new DB_Sub_Size( $data );
				$db_subs[] = $db_sub;
			}
		}

		usort( $db_subs, function($a, $b){

			if ( abs( $a->front_diff_inches ) >= abs( $b->front_diff_inches )  ) {
				return 1;
			}

			return -1;
		});

		$this->db_subs = $db_subs;

		array_map( function( $ss ){

			echo '...';
			echo $ss->sub_size->get_nice_name();
			echo '...';
			echo $ss->front_diff_inches;
			echo '<br>';

		}, $this->db_subs );
	}

	/**
	 * @param int $count
	 */
	public function get_best( $count = 1 ) {
		return gp_array_first_count( $this->db_subs, $count );
	}

	/**
	 * @param $v
	 */
	public function add_width_var( $v ) {
		$this->w_var[] = $v;
	}

	/**
	 * @param $v
	 */
	public function add_profile_var( $v ) {
		$this->p_var[] = $v;
	}

	/**
	 * @param $v
	 */
	public function add_diameter_var( $v ) {
		$this->d_var[] = $v;
	}
}

/**
 * DEV ENVIRONMENT ONLY
 */
function make_sub_sizes_for_testing(){

	if ( IN_PRODUCTION ) {
		throw_dev_error( 'No.' );
		exit;
	}

	$tire_sizes = get_all_unique_tire_sizes();

	for ( $x = -1; $x <= 4; $x++ ) {

		if ( $tire_sizes ) {
			foreach ( $tire_sizes as $tire_size ) {

				$w = gp_if_set( $tire_size, 'width' );
				$p = gp_if_set( $tire_size, 'profile' );
				$d = gp_if_set( $tire_size, 'diameter' );

				$grp = new Sub_Size_Group( $w, $p, $d );

				$grp->add_diameter_var($x);
				$grp->add_width_var(-20);
				$grp->add_width_var(-10);
				$grp->add_width_var(10);
				$grp->add_width_var(20);
				$grp->add_profile_var(-10);
				$grp->add_profile_var(-5);
				$grp->add_profile_var(5);
				$grp->add_profile_var(10);

				$grp->make_variations();

				$best = $grp->get_best( 2 );

				$br = '<br>';

				echo 'Target: ' . $grp->target->get_nice_name();
				echo $br;

				if ( $best ) {
					/** @var DB_Sub_Size $b */
					foreach ( $best as $b ) {
						echo $b->sub_size->get_nice_name();
						echo $br;

						// important to leave this off for now
						// $b->insert_if_not_exists();

					}
				}
			}
		}
	}
}
