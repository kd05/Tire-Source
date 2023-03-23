<?php

/**
 * Products have suppliers. Supplier data contains email addresses used
 * upon an order checkout.
 *
 * Class DB_Supplier
 */
Class DB_Supplier extends DB_Table{

	protected static $primary_key = 'supplier_id';
	protected static $table = DB_suppliers;

	protected static $prefix = 'supplier_';

	/*
	 * An array of keys required to instantiate the object.
	 */
	protected static $req_cols = array( 'supplier_slug' );

	// db columns
	// prefix to avoid conflicts with other tables when inner joining..
	// this applies to some cols, but if we're going to prefix some cols then i might
	// just end up prefixing others even if they don't really need it
	protected static $fields = array(
		'supplier_id',
		'supplier_slug',
		'supplier_name',
		'supplier_order_email',
		'supplier_order_email_us',
	);

	protected static $db_init_cols = array(
		'supplier_id' => 'int(11) unsigned NOT NULL auto_increment',
		'supplier_slug' => 'varchar(255) default \'\' UNIQUE',
		'supplier_name' => 'varchar(255) default \'\'',
		// may be a comma sep. list at some point
		'supplier_order_email' => 'varchar(511) default \'\'',
		'supplier_order_email_us' => 'varchar(511) default \'\'',
	);

	protected static $db_init_args = array(
		'PRIMARY KEY (`supplier_id`)',
	);

	protected $data;

	/**
	 * DB_Cache constructor.
	 *
	 * @param       $data
	 * @param array $options
	 */
	public function __construct( $data, $options = array() ){
		parent::__construct( $data, $options );
	}

	/**
	 * @param $slug
	 */
	public static function get_instance_via_slug( $slug ) {

		$db = get_database_instance();

		$q  = '';
		$q  .= 'SELECT * ';
		$q  .= 'FROM ' . static::$table . ' ';
		$q  .= 'WHERE supplier_slug = :slug ';
		$q  .= ';';
		$p = array();
		$p[] = [ 'slug', $slug, $db->str ];

		$row = gp_if_set( $db->get_results( $q, $p ), 0 );
		$ret = $row ? static::create_instance_or_null( $row ) : null;

		return $ret;
	}

	/**
	 * alias for insert..
	 *
	 * just adds a row to the table. may or may not do much
	 * validation below.
	 *
	 * @param $data
	 * @param $format
	 *
	 * @return bool|string
	 */
	public static function register( $data, $format = array() ) {

		// rather not throw exception as this will abort import...
		if ( ! isset( $data['supplier_slug'] ) || ! $data['supplier_slug'] ) {
			return false;
		}

		// ensure it doesn't exit.. maybe this is redundant we would be better off
		// just adding a unique constraint to sql which we'll probably do
		if ( static::get_instance_via_slug( $data['supplier_slug'] ) ) {
			return false;
		}

		return static::insert( $data, $format );
	}

	/**
	 * since we only store the slug in the tires and rims table, we sometimes
	 * don't have access to the primary key..
	 *
	 * @param $string
	 */
	public static function get_instance_from_primary_key_or_slug( $string ) {

		// the slug may be an integer ... which is ... very unlikely...
		$order = gp_is_integer( $string ) ? [ 'pk', 'slug' ] : [ 'slug', 'pk' ];

		$object = null;

		foreach ( $order as $o ) {

			if ( $object ) {
				break;
			}

			switch( $o ) {
				case 'pk':
					$object = static::create_instance_via_primary_key( $string );
					break;
				case 'slug':
					$object = static::get_instance_via_slug( $string );
					break;
				default:
					break;
			}
		}

		return $object;
	}

	/**
	 * For order notifications.
	 *
	 * @param $locale
	 *
	 * @return null|string
	 */
	public static function get_order_email_to_column_via_locale( $locale ) {
		switch( $locale ) {
			case APP_LOCALE_CANADA:
				return 'supplier_order_email';
				break;
			case APP_LOCALE_US:
				return 'supplier_order_email_us';
				break;
			default:
				return null;
		}
	}

	/**
	 * @param $locale
	 *
	 * @return null
	 */
	public function get_order_email_to( $locale ){
		$col = self::get_order_email_to_column_via_locale( $locale );
		return $col ? $this->get( $col, null, false ) : null;
	}

	/**
	 *
	 */
	public function get_admin_archive_page_args(){
		$args = array();
		$args['do_delete'] = true;
		return $args;
	}

	/**
	 * @return bool
	 */
	public function is_used_by_rims(){

		$db = get_database_instance();

		$p = [];
		$q = '';
		$q .= 'SELECT part_number ';
		$q .= 'FROM ' . DB_rims . ' ';
		$q .= 'WHERE supplier = :supplier ';
		$p[] = [ 'supplier', $this->get( 'slug' ), '%s'];

		$q .= 'GROUP BY rim_id ';
		$q .= '';
		$q .= ';';

		$r = $db->get_results( $q, $p );

		$ret = ( $r && count( $r ) > 0 );
		return $ret;
	}

	/**
	 * @return bool
	 */
	public function is_used_by_tires(){

		$db = get_database_instance();

		$p = [];
		$q = '';
		$q .= 'SELECT part_number ';
		$q .= 'FROM ' . DB_tires . ' ';
		$q .= 'WHERE supplier = :supplier ';
		$p[] = [ 'supplier', $this->get( 'slug' ), '%s'];

		$q .= 'GROUP BY tire_id ';
		$q .= '';
		$q .= ';';

		$r = $db->get_results( $q, $p );

		$ret = ( $r && count( $r ) > 0 );
		return $ret;
	}

}