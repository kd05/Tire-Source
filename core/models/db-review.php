<?php

/**
 * Class DB_Review
 */
Class DB_Review extends DB_Table {

	protected static $primary_key = 'review_id';
	protected static $table = DB_reviews;

	/*
	 * An array of keys required to instantiate the object.
	 */
	protected static $req_cols = array( 'review_product_type', 'rating' );

	/**
	 * @var array
	 */
	protected static $fields = array(
		'review_id',
		'review_product_type',
		'review_brand',
		'review_model',
		'review_color_1',
		'review_color_2',
		'review_finish',
		'rating',
		'nickname',
		'user_id',
		'message',
		'date_inserted',
		'date_updated',
		'approved',
		'last_edit_user_id'
	);

	/**
	 * @var array
	 */
	protected static $db_init_cols = array(
		'review_id' => 'int(11) unsigned NOT NULL auto_increment',
		'review_product_type' => 'varchar(255) default \'\'',
		'review_brand' => 'varchar(255) default \'\'',
		'review_model' => 'varchar(255) default \'\'',
		'review_color_1' => 'varchar(255) default \'\'',
		'review_color_2' => 'varchar(255) default \'\'',
		'review_finish' => 'varchar(255) default \'\'',
		'rating' => 'varchar(255) default \'\'',
		'nickname' => 'varchar(255) default \'\'',
		'user_id' => 'varchar(255) default \'\'',
		'message' => 'longtext',
		'date_inserted' => 'varchar(255) default \'\'',
		'date_updated' => 'varchar(255) default \'\'',
		'approved' => 'bool default NULL',
		'last_edit_user_id' => 'varchar(255) default \'\'',
	);

	/**
	 * @var array
	 */
	protected static $db_init_args = array(
		'PRIMARY KEY (`review_id`)',
	);

	protected $data;

	/**
	 * DB_User constructor.
	 *
	 * @param       $data
	 * @param array $options
	 */
	public function __construct( $data, $options = array() ) {
		parent::__construct( $data, $options );
	}

	/**
	 * database value could be string zero so.. simple comparison doesn't always work.
	 */
	public function is_approved() {
		$v   = $this->get( 'approved' );
		$ret = $v == 1 || $v == "1";

		return $ret;
	}

	/**
	 * @param $user_id
	 * @param $tire_brand
	 * @param $tire_model
	 */
	public static function get_tire_review_via_user_id( $user_id, $tire_brand, $tire_model ) {

		$user_id    = gp_test_input( $user_id );
		$tire_brand = gp_test_input( $tire_brand );
		$tire_model = gp_test_input( $tire_model );

		$db = get_database_instance();
		$p  = [];
		$q  = '';
		$q  .= 'SELECT * ';
		$q  .= 'FROM ' . self::$table . ' ';

		$q .= 'WHERE 1 = 1 ';

		$q   .= 'AND review_brand = :review_brand ';
		$p[] = [ ':review_brand', $tire_brand ];

		$q   .= 'AND review_model = :review_model ';
		$p[] = [ ':review_model', $tire_model ];

		$q   .= 'AND user_id = :user_id ';
		$p[] = [ ':user_id', $user_id ];

		// order by not super important but lets ALWAYS leave the primary key present
		// to ensure we don't get different products with the same input
		$q .= 'ORDER BY date_updated DESC, date_inserted DESC, review_id DESC ';

		$q .= 'LIMIT 0,1 ';
		$q .= ';';

		$results = $db->get_results( $q, $p );

		$row = gp_if_set( $results, 0 );

		if ( $row ) {
			return self::create_instance_or_null( $row );
		}

		return null;

	}

	/**
	 * @param $user_id
	 * @param $tire_brand
	 * @param $tire_model
	 */
	public static function get_rim_review_via_user_id( $user_id, $rim_brand, $rim_model, $color_1, $color_2, $finish ) {

		$user_id   = gp_test_input( $user_id );
		$rim_brand = gp_test_input( $rim_brand );
		$rim_model = gp_test_input( $rim_model );
		$color_1   = gp_test_input( $color_1 );
		$color_2   = gp_test_input( $color_2 );
		$finish    = gp_test_input( $finish );

		$db = get_database_instance();
		$p  = [];
		$q  = '';
		$q  .= 'SELECT * ';
		$q  .= 'FROM ' . self::$table . ' ';

		$q .= 'WHERE 1 = 1 ';

		$q   .= 'AND review_brand = :review_brand ';
		$p[] = [ ':review_brand', $rim_brand ];

		$q   .= 'AND review_model = :review_model ';
		$p[] = [ ':review_model', $rim_model ];

		$q   .= 'AND user_id = :user_id ';
		$p[] = [ ':user_id', $user_id ];

		if ( $color_1 ) {
			$q   .= 'AND review_color_1 = :c1 ';
			$p[] = [ ':c1', $color_1 ];
		} else {
			$q .= 'AND ( review_color_1 IS NULL OR review_color_1 = "" ) ';
		}

		if ( $color_2 ) {
			$q   .= 'AND review_color_2 = :c2 ';
			$p[] = [ ':c2', $color_2 ];
		} else {
			$q .= 'AND ( review_color_2 IS NULL OR review_color_2 = "" ) ';
		}

		if ( $finish ) {
			$q   .= 'AND review_finish = :f3 ';
			$p[] = [ ':f3', $finish ];
		} else {
			$q .= 'AND ( review_finish IS NULL OR review_finish = "" ) ';
		}

		// order by not super important but lets ALWAYS leave the primary key present
		// to ensure we don't get different products with the same input
		$q .= 'ORDER BY date_updated DESC, date_inserted DESC, review_id DESC ';

		$q .= 'LIMIT 0,1 ';
		$q .= ';';

		$results = $db->get_results( $q, $p );

		$row = gp_if_set( $results, 0 );

		if ( $row ) {
			return self::create_instance_or_null( $row );
		}

		return null;
	}

	/**
	 * @param $key
	 */
	public function get_cell_data_for_admin_table( $key, $value ) {

		// must default to null not false or ''
		$ret = null;

		switch ( $key ) {
			case 'message':
				$m = $this->get( 'message' );
				$e = gp_excerptize( $m, 10 );

				return $e;
			case 'review_product_type':

				$review_product_type = $this->get( 'review_product_type' );
				$review_brand        = $this->get( 'review_brand' );
				$review_model        = $this->get( 'review_model' );
				$approved = $this->get( 'approved' );
				$f1 = $this->get( 'review_color_1' );
				$f2 = $this->get( 'review_color_2' );
				$f3 = $this->get( 'review_finish' );

				switch ( $review_product_type ) {
					case 'tire':

						if ( $approved ) {
							$url = get_tire_model_url_basic( $review_brand, $review_model );
							$link = get_anchor_tag_simple( $url, 'view' );
							return $value . ' - ' . $link;
						}

						return $value;

					case 'rim':

						if ( $approved ) {
							$url = get_rim_finish_url( [ $review_brand, $review_model, $f1, $f2, $f3 ] );
							$link = get_anchor_tag_simple( $url, 'view' );
							return $value . ' - ' . $link;
						}

						return $value;

					default:
						return $value;
				}


				break;
			case '':
				break;
		}

		return $ret;
	}

	/**
	 * @return array
	 */
	public function get_order_by_args_for_admin_table() {
		$ret   = [];
		$ret[] = 'date_updated DESC';

		return $ret;
	}
}
