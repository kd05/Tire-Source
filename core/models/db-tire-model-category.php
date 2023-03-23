<?php

/**
 * Class DB_Tire_Model_Category
 */
Class DB_Tire_Model_Category extends DB_Virtual_Table {

	protected static $fields = array(
		'slug',
		'name'
	);

	/**
	 * DB_Tire_Model_Category constructor.
	 *
	 * @param       $data
	 * @param array $options
	 */
	public function __construct( $data, $options = array() ) {
		parent::__construct( $data, $options );
	}

	/**
	 * @return array
	 */
	public static function get_all_data(){
		return Static_Array_Data::tire_model_categories();
	}
}
