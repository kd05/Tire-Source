<?php

/**
 * Class DB_Sub_Size
 */
Class DB_Sub_Size extends DB_Table{

	protected static $primary_key = 'sub_size_id';
	protected static $table = DB_sub_sizes;

	/*
	 * An array of keys required to instantiate the object.
	 */
	protected static $req_cols = array();

	// db columns
	protected static $fields = array(
		'sub_size_id',
		'target_width_1',
		'target_profile_1',
		'target_diameter_1',
		'target_width_2',
		'target_profile_2',
		'target_diameter_2',
		'sub_width_1',
		'sub_profile_1',
		'sub_diameter_1',
		'sub_width_2',
		'sub_profile_2',
		'sub_diameter_2',
	);

	protected static $db_init_cols = array(
		'sub_size_id' => 'int(11) unsigned NOT NULL auto_increment',
		'target_width_1' => 'varchar(31) default \'\'',
		'target_profile_1' => 'varchar(31) default \'\'',
		'target_diameter_1' => 'varchar(31) default \'\'',
		'target_width_2' => 'varchar(31) default \'\'',
		'target_profile_2' => 'varchar(31) default \'\'',
		'target_diameter_2' => 'varchar(31) default \'\'',
		'sub_width_1' => 'varchar(31) default \'\'',
		'sub_profile_1' => 'varchar(31) default \'\'',
		'sub_diameter_1' => 'varchar(31) default \'\'',
		'sub_width_2' => 'varchar(31) default \'\'',
		'sub_profile_2' => 'varchar(31) default \'\'',
		'sub_diameter_2' => 'varchar(31) default \'\'',
	);

	protected static $db_init_args = array(
		'PRIMARY KEY (`sub_size_id`)',
	);

	protected $data;

	/** @var  Tire_Atts_Pair */
	public $target_size;

	/** @var  Tire_Atts_Pair */
	public $sub_size;

	public $front_diff_inches;
	public $rear_diff_inches;
	public $front_variance;
	public $rear_variance;

	/**
	 * DB_Cache constructor.
	 *
	 * @param       $data
	 * @param array $options
	 */
	public function __construct( $data, $options = array() ){

		parent::__construct( $data, $options );

		$tw1 = $this->get( 'target_width_1' );
		$tp1 = $this->get( 'target_profile_1' );
		$td1 = $this->get( 'target_diameter_1' );
		$tw2 = $this->get( 'target_width_2' );
		$tp2 = $this->get( 'target_profile_2' );
		$td2 = $this->get( 'target_diameter_2' );

		$this->target_size = Tire_Atts_Pair::create_manually( $tw1, $tp1, $td1, $tw2, $tp2, $td2 );

		$sw1 = $this->get( 'sub_width_1' );
		$sp1 = $this->get( 'sub_profile_1' );
		$sd1 = $this->get( 'sub_diameter_1' );
		$sw2 = $this->get( 'sub_width_2' );
		$sp2 = $this->get( 'sub_profile_2' );
		$sd2 = $this->get( 'sub_diameter_2' );

		$this->sub_size = Tire_Atts_Pair::create_manually( $sw1, $sp1, $sd1, $sw2, $sp2, $sd2 );

		$this->front_diff_inches = $this->sub_size->front->overall_diameter - $this->target_size->front->overall_diameter;

		// avoid division by zero
		if ( $this->target_size->front->overall_diameter ) {
			$this->front_variance = round( ( $this->front_diff_inches / $this->target_size->front->overall_diameter ) * 100, 2 );
		}

		// only if both are staggered
		if ( $this->sub_size->staggered && $this->target_size->staggered ) {
			$this->rear_diff_inches = $this->sub_size->rear->overall_diameter - $this->target_size->rear->overall_diameter;

			// avoid division by zero
			if ( $this->target_size->rear->overall_diameter ) {
				$this->rear_variance = round( ( $this->rear_diff_inches / $this->target_size->rear->overall_diameter ) * 100, 2 );
			}
		}
	}

	/**
	 * Meant for virtual sub sizes that don't originate from the database table.
	 * You can make your own object, then call this fn.
	 */
	public function insert_if_not_exists(){

		$ex = get_sub_sizes( $this->target_size, $this->sub_size );

		if ( $ex ) {
			return false;
		}

		$db = get_database_instance();

		$insert_id = $db->insert( static::$table, $this->to_array( [ static::$primary_key ] ) );
		return $insert_id;
	}

	/**
	 * ie. "2255018-2254019" corresponding to target size
	 *
	 * ie. to use in an array index if using php to group similar sizes
	 */
	public function get_unique_target_size_string(){

		$arr = array();
		$arr[] = $this->get( 'target_width_1' );
		$arr[] = $this->get( 'target_profile_1' );
		$arr[] = $this->get( 'target_diameter_1' );

		if ( $this->target_size->staggered ) {
			$arr[] = '-';
			$arr[] = $this->get( 'target_width_2' );
			$arr[] = $this->get( 'target_profile_2' );
			$arr[] = $this->get( 'target_diameter_2' );
		}

		$ret = implode( '', $arr );
		$ret = gp_test_input( $ret );
		return $ret;
	}

	/**
	 *
	 */
	public function get_admin_table_row(){

		$t = array(
			'target' => link_to_edit_sub_size( $this->target_size->get_nice_name() ),
			'sub' => link_to_edit_sub_size( $this->sub_size->get_nice_name() ),
			'target_overall' => $this->get_target_overall_string(),
			'sub_overall' => $this->get_sub_overall_string(),
			'variance' => $this->get_variance_string(),
		);

		return $t;
	}

	/**
	 *
	 */
	public function get_variance_string(){

		$str = $this->get_percent_string( $this->front_variance );

		if ( $this->target_size->staggered || $this->sub_size->staggered ) {
			$str .= ' / ' . $this->get_percent_string( $this->rear_variance );
		}

		return $str;
	}

	/**
	 * @param $str
	 *
	 * @return string
	 */
	public static function get_percent_string( $str ) {
		return format_percent_string( $str );
	}

	/**
	 *
	 */
	public function get_sub_overall_string(){

		$str = round( $this->sub_size->front->overall_diameter, 2 );

		if ( $this->sub_size->staggered ) {
			$str = ' / ' . round( $this->sub_size->rear->overall_diameter, 2);
		}

		return $str;
	}

	/**
	 *
	 */
	public function get_target_overall_string(){

		$str = round( $this->target_size->front->overall_diameter, 2 );

		if ( $this->target_size->staggered ) {
			$str = ' / ' . round( $this->target_size->rear->overall_diameter, 2 );
		}

		return $str;
	}

	/**
	 *
	 */
	public function get_simple_sub_size_string( $sep = '-' ){

		$st = $this->get( 'staggered' );

		$w1 = $this->get( 'target_width_1' );
		$p1 = $this->get( 'target_profile_1' );
		$d1 = $this->get( 'target_diameter_1' );

		$op = '';
		$op .= $w1 . $p1 . $d1;

		if ( $st ) {

			$w2 = $this->get( 'target_width_2' );
			$p2 = $this->get( 'target_profile_2' );
			$d2 = $this->get( 'target_diameter_2' );

			$op .= $sep;
			$op .= $w2 . $p2 . $d2;
		}

		$op = gp_test_input( $op );
		return $op;
	}

	/**
	 * This function is not as trivial as you would think. The idea is that an admin user
	 * may be able to add sub sizes with a certain amount of variance, but then at a later time
	 * we can reduce the allow variance on the front-end if we need to, say to set a max
	 * from 3% down to 2.5% if someone has an issue with their fitment guarantee. This would be
	 * far easier than deleting many thousands of sub sizes in the database. Also, its really important
	 * to note that if a size is not valid, its possible that it won't even show up for the admin user,
	 * and then they won't be able to clean it up from the database. Therefore, its important
	 * to have more than 1 definition of what's valid. One is what can be stored in the DB,
	 * the other is what is shown to clients, and the latter is more likely to change over time.
	 *
	 * On top of this, we can also pass in an instance of Vehicle, or Fitment, and then do
	 * some pretty cool logic based on the vehicles make, model, or year.
	 *
	 * @param bool $extended
	 */
	public function is_valid_for_front_end_user() {

		// todo: remove this ...
		if ( ! IN_PRODUCTION ) {
			return true;
		}

		if ( ! $this->sub_size->valid ) {
			return false;
		}

		if ( ! $this->target_size->valid ) {
			return false;
		}

		if ( $this->target_size->staggered && ! $this->sub_size->staggered ){
			return false;
		}

		if ( ! $this->target_size->staggered && $this->sub_size->staggered ){
			return false;
		}

		// no more than 3% variance
		$max_var = 3;

		if ( abs( $this->front_variance > $max_var ) ) {
			return false;
		}

		if ( $this->sub_size->staggered && abs( $this->front_variance > $max_var ) ) {
			return false;
		}

		return true;

	}

}

