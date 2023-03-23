<?php

/**
 * Class DB_Cache
 */
Class DB_Option extends DB_Table {

	protected static $primary_key = 'option_id';
	protected static $table = DB_options;

	/*
	 * An array of keys required to instantiate the object.
	 */
	protected static $req_cols = array();

	// db columns
	protected static $fields = array(
		'option_id',
		'option_key',
		'option_value',
		'autoload',
	);

	protected static $db_init_cols = array(
		'option_id' => 'int(11) unsigned NOT NULL auto_increment',
		'option_key' => 'varchar(255) default \'\'',
		'option_value' => 'longtext',
		'autoload' => 'varchar(255) default \'\'',
	);
	protected static $db_init_args = array(
		'PRIMARY KEY (`option_id`)',
	);

	protected $data;

	/**
	 * DB_Cache constructor.
	 *
	 * @param       $data
	 * @param array $options
	 */
	public function __construct( $data, $options = array() ) {
		parent::__construct( $data, $options );
	}

	/**
	 * Note: some option values require html, sanitization is not done here.
	 *
	 * @param $key
	 * @param $value
     * @return bool
	 */
	public static function set_option_value( $key, $value ) {

		$db = get_database_instance();

		// Update/Insert
		$upsert = $db->upsert( static::$table, array(
			// have to pass in the key even when updating because we might be inserting
			'option_key' => gp_test_input( $key ),
			'option_value' => $value,
		), array(
			'option_key' => gp_test_input( $key ),
		) );

		return (bool) $upsert;
	}

	/**
	 * note: do your own sanitation after this.
	 *
	 * @param $key
     * @return bool|mixed|string|null
	 */
	public static function get_option_value( $key, $df = null ) {

		$self = static::get_instance_via_option_key( $key );

		if ( $self ) {
			return $self->get( 'option_value' );
		}

		return $df;
	}

	/**
	 * Note: some option values require html, NO sanitization on the value is done here.
	 *
	 * @param $key
	 * @return null|static|DB_Option
	 */
	public static function get_instance_via_option_key( $key ) {

		$db = get_database_instance();

		$rows = $db->get( static::$table, array(
			'option_key' => gp_test_input( $key ),
		) );

		$row_1 = $rows ? gp_if_set( $rows, 0 ) : false;

		if ( $row_1 ) {
			return new static ( $row_1 );
		}

		return null;
	}
}

/**
 * @param $key
 * @param $value
 *
 * @return bool
 */
function cw_set_option( $key, $value ) {
	return DB_Option::set_option_value( $key, $value );
}

/**
 * @param        $key
 * @param string|null $df
 * @return bool|mixed|string|null
 */
function cw_get_option( $key, $df = null ) {
	return DB_Option::get_option_value( $key, $df );
}