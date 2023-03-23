<?php

/**
 * Class DB_Tire_Model_Type
 */
Class DB_Tire_Model_Type extends DB_Virtual_Table{

	protected static $table = null;
	protected static $primary_key = null;
	protected static $fields = array(
		'slug',
		'name',
	);

	/**
	 * DB_Tire_Model_Type constructor.
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
		return Static_Array_Data::tire_model_types();
	}

	/**
	 * @param $slug
	 */
	public static function get_instance_via_slug( $slug ) {
		$d = self::get_all_data();

		if ( $d ) {
			foreach ( $d as $_slug => $data ) {

				if ( $_slug === $slug ) {
					$_data = $data;
					$_data['slug'] = gp_test_input( $_slug );
					return self::create_instance_or_null( $_data );
				}
			}
		}

		return null;
	}

	/**
	 * @return DB_Page|null|mixed
	 */
	public function get_page(){
		$name = DB_Page::page_name_from_tire_type_slug( $this->get( 'slug' ) );
		$ret = $name ? DB_Page::get_instance_via_name( $name, false ) : false;
		return $ret;
	}

	/**
	 * @return string
	 */
	public function get_archive_url(){
		return cw_add_query_arg( [
			'type' => $this->get( 'slug', '', true )
		], get_url( 'tires' ) );

	}
}
