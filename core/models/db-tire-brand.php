<?php

/**
 * Class DB_Tire_Brand
 */
Class DB_Tire_Brand extends DB_Product_Brand{

	protected static $prefix = 'tire_brand_';
	protected static $table = DB_tire_brands;
	protected static $primary_key = 'tire_brand_id';

	/**
	 * @var array
	 */
	protected static $req_cols = array(
		'tire_brand_id',
		'tire_brand_slug',
		'tire_brand_name',
	);

	protected static $fields = array(
		'tire_brand_id',
		'tire_brand_slug',
		'tire_brand_name',
		'tire_brand_logo',
        'tire_brand_description',
        'tire_brand_inserted_at',
	);

	/**
	 * @var array
	 */
	protected static $db_init_cols = array(
		'tire_brand_id' => 'int(11) unsigned NOT NULL auto_increment',
		'tire_brand_slug' => 'varchar(255) default \'\'',
		'tire_brand_name' => 'varchar(255) default \'\'',
		'tire_brand_logo' => 'varchar(255) default \'\'',
        'tire_brand_description' => 'text',
        'tire_brand_inserted_at' => 'varchar(255) default \'\'',
	);

	/**
	 * @var array
	 */
	protected static $db_init_args = array(
		'PRIMARY KEY (`tire_brand_id`)',
	);

	/**
	 * DB_Tire_Brand constructor.
	 *
	 * @param       $data
	 * @param array $options
	 */
	public function __construct( $data, $options = array() ) {
		parent::__construct( $data, $options );
	}

    /**
     * @param $brand_id
     * @return array
     */
	public static function get_models_with_brand_id( $brand_id ) {

	    $db = get_database_instance();

	    $q = 'select * from tire_models where tire_brand_id = :brand_id ';
	    $params = [];
	    $params[] = [ 'brand_id', $brand_id, '%d' ];

	    return array_map( function( $row ) {
	        return DB_Tire_Model::create_instance_or_null( $row );
        }, $db->get_results( $q, $params ) );
    }

	/**
	 * @param $slug
	 */
	public static function get_instance_via_slug( $slug ) {

		$db = get_database_instance();

		$q  = '';
		$q  .= 'SELECT * ';
		$q  .= 'FROM ' . static::$table . ' ';
		$q  .= 'WHERE tire_brand_slug = :slug ';
		$q  .= '';
		$q  .= ';';
		$st = $db->pdo->prepare( $q );
		$st->bindValue( 'slug', $slug, $db->str );
		$st->execute();
		$obj = $st->fetchObject();

		if ( ! $obj ) {
			return null;
		}

		$db_obj = static::create_instance_or_null( $obj );
		if ( $db_obj ) {
			return $db_obj;
		}

		return null;
	}

	/**
	 *
	 */
	public function get_logo( $fallback = false ){

		$ret = get_image_src( $this->get( 'tire_brand_logo' ) );
		$ret = trim( $ret );

		if ( $fallback && ! $ret ) {
			$ret = image_not_available();
		}

		return $ret;
	}

    /**
     * @param $key
     * @param $value
     *
     * @return null|string
     */
    public function get_cell_data_for_admin_table( $key, $value ){

        switch( $key ){
            case 'tire_brand_logo':
                return format_image_cell_data_for_admin_table( $value );
            case 'tire_brand_description':
                return gp_test_input( $value );
            case 'linked_page':
                // returning raw value here bypassses sanitation. In filter_row_for_admin_tables
                // we set this to be an anchor tag.
                return $value;
        }

        // must not return false or ""
        return null;
    }

	/**
	 * @param $row
	 */
	public function filter_row_for_admin_tables( $row ) {

        if ( $row && is_object( $row ) ) {

            $db_page = $this->get_page();

            if ( $db_page ) {
                $row->linked_page = gp_get_link( $db_page->get_admin_single_page_url(), $db_page->get( 'name' ) );
            } else {
                $row->linked_page = '';
            }
        }

		return $row;
	}

	/**
	 * @return DB_Page|null|mixed
	 */
	public function get_page(){
		$name = DB_Page::page_name_from_tire_brand_slug( $this->get( 'slug' ) );
		$ret = $name ? DB_Page::get_instance_via_name( $name, false ) : false;
		return $ret;
	}

	/**
	 * @return string
	 */
	public function get_archive_url(){
		return cw_add_query_arg( [
			'brand' => $this->get( 'slug', '', true )
		], get_url( 'tires' ) );
	}
}
