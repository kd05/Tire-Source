<?php

/**
 * Class DB_Order_Item
 */
Class DB_Order_Vehicle extends DB_Table{

	protected static $table = DB_order_vehicles;

	protected static $primary_key = 'order_vehicle_id';

	/**
	 * @var array
	 */
	protected static $fields = array(
		'order_vehicle_id',
		'vehicle_name',
		'fitment',
		'fitment_name',
		'sub',
		'sub_name',
		'make',
		'model',
		'year',
		'trim',
		'bolt_pattern',
		'lock_type',
		'market_slug',
		'lock_text',
		'center_bore',
		'staggered',
		'oem',
		'fitment_data',
	);

	/**
	 * @var array
	 */
	protected static $db_init_cols = array(
		'order_vehicle_id' => 'int(11) unsigned NOT NULL auto_increment',
		'vehicle_name' => 'varchar(255) default \'\'',
		'fitment' => 'varchar(255) default \'\'',
		'fitment_name' => 'varchar(255) default \'\'',
		'sub' => 'varchar(255) default \'\'',
		'sub_name' => 'varchar(255) default \'\'',
		'make' => 'varchar(255) default \'\'',
		'model' => 'varchar(255) default \'\'',
		'year' => 'varchar(255) default \'\'',
		'trim' => 'varchar(255) default \'\'',
		'bolt_pattern' => 'varchar(255) default \'\'',
		'lock_type' => 'varchar(255) default \'\'',
		'market_slug' => 'varchar(255) default \'\'',
		'lock_text' => 'varchar(255) default \'\'',
		'center_bore' => 'varchar(255) default \'\'',
		'staggered' => 'varchar(255) default \'\'',
		'oem' => 'varchar(255) default \'\'',
		'fitment_data' => 'longtext',
	);

	/**
	 * @var array
	 */
	protected static $db_init_args = array(
		'PRIMARY KEY (`order_vehicle_id`)'
	);

	/**
	 * DB_Order_Item constructor.
	 *
	 * @param array $data
	 * @param array $options
	 */
	public function __construct( $data = array(), $options = array() ){
		parent::__construct( $data, $options );
	}

	/**
	 *
	 */
	public function get_make_model_year(){

		$ret = '';
		$ret .= $this->get_and_strip_tags( 'year' );
		$ret .= ' ';
		$ret .= $this->get_and_strip_tags( 'make' );
		$ret .= ' ';
		$ret .= $this->get_and_strip_tags( 'model' );

		$ret = trim( $ret );
		return $ret;
	}

	/**
	 * gets bolt pattern, lock text (maybe), and center bore. not sure
	 * if we'll end up sending this information to suppliers, because
	 * apparently the make/model/year is sufficient, but i'm not 100% positive that it is,
	 * because our vehicle API may use slightly different naming for a few vehicles.
	 *
	 * On the other hand, if we send this to suppliers and its wrong because the API
	 * has inaccurate information, then they might use this and ignore the API. And yet still,
	 * if the API has inaccurate information then the user probably purchased the wrong rims
	 * anyways, so what can we really do in this case.
	 */
	public function get_sizing_specs_for_supplier_emails(){

		$arr = array();
		$arr[] = $this->get_and_clean( 'bolt_pattern' );
		$arr[] = '-';
		$arr[] = $this->get_and_clean( 'lock_type' );
		$arr[] = $this->get_and_clean( 'lock_text' );
		$arr[] = '-';
		$arr[] = $this->get_and_clean( 'center_bore' );
		$ret = trim( implode( ' ', $arr ) );
		return $ret;
	}

}