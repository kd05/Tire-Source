<?php

/**
 * Class DB_Stock
 */
Class DB_Stock_Update extends DB_Table {

	protected static $primary_key = 'stock_id';
	protected static $table = DB_stock_updates;

	/*
	 * An array of keys required to instantiate the object.
	 */
	protected static $req_cols = array();

	// db columns
	protected static $fields = array(
		'stock_id',
		'stock_filename',
		'stock_description',
		'stock_type',
		'stock_suppliers',
		'stock_locale',
		'stock_types_affected',
		'stock_suppliers_affected',
		'stock_brands_affected',
		'count_csv',
		'count_processed',
		'count_not_found',
		'count_updated',
		'count_in_stock',
		'count_no_stock',
		'count_not_sold',
		'count_total_stock',
		'stock_seconds',
		'stock_errors',
		'stock_date',
		'stock_dump',
	);

	protected static $db_init_cols = array(
		'stock_id' => 'int(11) unsigned NOT NULL auto_increment',
		'stock_filename' => 'text',
		'stock_description' => 'text',
		'stock_type' => 'varchar(255) default \'\'',
		'stock_suppliers' => 'varchar(255) default \'\'',
		'stock_locale' => 'varchar(255) default \'\'',
		'stock_types_affected' => 'text',
		'stock_suppliers_affected' => 'text',
		'stock_brands_affected' => 'text',
		'count_csv' => 'int(11) default NULL',
		'count_processed' => 'int(11) default NULL',
		'count_not_found' => 'int(11) default NULL',
		'count_updated' => 'int(11) default NULL',
		'count_in_stock' => 'int(11) default NULL',
		'count_no_stock' => 'int(11) default NULL',
		'count_not_sold' => 'int(11) default NULL',
		'count_total_stock' => 'int(11) default NULL',
		'stock_seconds' => 'varchar(255) default \'\'',
		'stock_errors' => 'longtext',
		'stock_date' => 'varchar(255) default \'\'',
		'stock_dump' => 'longtext',
	);

	protected static $db_init_args = array(
		'PRIMARY KEY (`stock_id`)',
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
     * @param $hash_key
     * @return mixed|null
     */
	public static function get_most_recent_via_hash_key( $hash_key ) {

        $db = get_database_instance();
        $r = $db->get_results( "SELECT * FROM stock_updates WHERE stock_description = :hash_key ORDER BY stock_id DESC LIMIT 0, 1;", [
            [ "hash_key", $hash_key, "%s"]
        ] );

        if ( count( $r ) > 0 ) {
            return self::create_instance_or_null( $r[0] );
        }

        return null;
    }

	/**
	 * @param $key
	 * @param $value
	 *
	 * @return null|string
	 */
	public function get_cell_data_for_admin_table( $key, $value ) {

		switch( $key ) {
			case 'stock_dump':
//				$v = gp_db_decode( $value );
//				if ( is_array( $v ) ) {
//					$v = clean_array_recursive( $v );
//					return get_pre_print_r( $v );
//				} 
				break;
			case 'stock_date':
				$dt = new DateTime( $value );
				return $dt->format( get_nice_date_format( true ) );
				break;
		}

		// must return null to indicate not to filter the value
		return null;
	}
}
