<?php

/**
 * Class DB_Region
 */
Class DB_Region extends DB_Table{

	/**
	 * @var string
	 */
	protected static $primary_key = 'region_id';

	/**
	 * @var string
	 */
	protected static $table = DB_regions;

	/**
	 * @var array
	 */
	protected static $fields = array(
		'region_id',
		'country_code',
		'province_code',
		'province_name',
	);

	/**
	 * @var array
	 */
	protected static $db_init_cols = array(
		'region_id' => 'int(11) unsigned NOT NULL auto_increment',
		'country_code' => 'varchar(255)',
		'province_code' => 'varchar(255)',
		'province_name' => 'varchar(255)',
	);

	/**
	 * @var array
	 */
	protected static $db_init_args = array(
		'PRIMARY KEY (`region_id`)',
	);

	/**
	 * DB_Shipping_Rate constructor.
	 *
	 * @param array $data
	 * @param array $options
	 */
	public function __construct( $data = array(), $options = array() ){
		parent::__construct( $data, $options );
	}
}

