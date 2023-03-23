<?php

/**
 * Class DB_Shipping_Rate
 */
Class DB_Shipping_Rate extends DB_Table{

	/**
	 * @var string
	 */
	protected static $primary_key = 'shipping_rate_id';

	/**
	 * @var string
	 */
	protected static $table = DB_shipping_rates;

	/**
	 * @var array
	 */
	protected static $fields = array(
		'shipping_rate_id',
		'region_id',
		'price_tire',
		'price_rim',
		'price_mounted',
		'allow_shipping',
	);

	/**
	 * @var array
	 */
	protected static $db_init_cols = array(
		'shipping_rate_id' => 'int(11) unsigned NOT NULL auto_increment',
		'region_id' => 'int(11) unsigned',
		'price_tire' => 'varchar(255) default \'\'',
		'price_rim' => 'varchar(255) default \'\'',
		'price_mounted' => 'varchar(255) default \'\'',
		'allow_shipping' => 'bool default NULL',
	);

	/**
	 * @var array
	 */
	protected static $db_init_args = array(
		'PRIMARY KEY (`shipping_rate_id`)',
		'FOREIGN KEY (region_id) REFERENCES ' . DB_regions . '(region_id)',
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
