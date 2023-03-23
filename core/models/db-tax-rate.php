<?php

/**
 * Class DB_Tax_Rate
 */
Class DB_Tax_Rate extends DB_Table{

	/**
	 * @var string
	 */
	protected static $primary_key = 'tax_rate_id';

	/**
	 * @var string
	 */
	protected static $table = DB_tax_rates;

	/**
	 * @var array
	 */
	protected static $fields = array(
		'tax_rate_id',
		'region_id',
		'tax_rate',
	);

	/**
	 * @var array
	 */
	protected static $db_init_cols = array(
		'tax_rate_id' => 'int(11) unsigned NOT NULL auto_increment',
		'region_id' => 'int(11) unsigned NOT NULL',
		'tax_rate' => 'varchar(255)',
	);

	/**
	 * @var array
	 */
	protected static $db_init_args = array(
		'PRIMARY KEY (`tax_rate_id`)',
		'FOREIGN KEY (region_id) REFERENCES ' . DB_regions . '(region_id)',
	);

	/**
	 * DB_Tax_Rate constructor.
	 *
	 * @param array $data
	 * @param array $options
	 */
	public function __construct( $data = array(), $options = array() ){
		parent::__construct( $data, $options );
	}
}
