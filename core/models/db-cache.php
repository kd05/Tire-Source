<?php

/**
 * Class DB_Cache
 */
Class DB_Cache extends DB_Table{

	protected static $primary_key = 'cache_id';
	protected static $table = DB_cache;

	/*
	 * An array of keys required to instantiate the object.
	 */
	protected static $req_cols = array();

	// db columns
	protected static $fields = array(
		'cache_id',
		'cache_key',
		'cache_key_2',
		'cache_value',
		'cache_time',
		'cache_expiry',
		'cache_extra',
	);

	protected static $db_init_cols = array(
		'cache_id' => 'int(11) unsigned NOT NULL auto_increment',
		'cache_key' => 'varchar(255) default \'\'',
		'cache_key_2' => 'varchar(255) default \'\'',
		'cache_value' => 'longtext',
		'cache_time' => 'varchar(255) default \'\'',
		'cache_expiry' => 'varchar(255) default \'\'',
		'cache_extra' => 'longtext',
	);

	protected static $db_init_args = array(
		'PRIMARY KEY (`cache_id`)',
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
}