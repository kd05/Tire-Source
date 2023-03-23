<?php

/**
 * Ie. instance from a database query with joins
 *
 * Class DB_Derived_Table
 */
Class DB_Derived_Table extends DB_Table{

	protected static $primary_key = '';
	protected static $fields = array();
	protected static $table = null;

	/**
	 * DB_Derived_Table constructor.
	 */
	public function __construct( $data = array(), $options = array() ){
		parent::__construct( $data, $options );
	}

	/**
	 *
	 */
	public static function create_derived_table_from_database_results( $results ){

		$first = gp_if_set( $results, 0 );

		$fields = false;
		if ( $first ) {
			$first = gp_make_array( $first );
			if ( $first && is_array( $first ) ) {
				$fields = array_keys( $first );
			}
		}

		$empty = static::get_empty_instance();

		// have to set $fields and also pass them in to setup_data()
		// which seems redundant and maybe is.
		if ( $fields ) {
			$empty::set_fields( $fields );
		}

		$empty->setup_data( $fields, $results );
		return $empty;
	}

	/**
	 *
	 */
	public static function set_fields( $fields ){
		self::$fields = $fields;
	}
}