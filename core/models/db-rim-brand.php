<?php

/**
 * Class DB_Rim_Brand
 */
Class DB_Rim_Brand extends DB_Product_Brand{

	protected static $prefix = 'rim_brand_';
	protected static $table = DB_rim_brands;
	protected static $primary_key = 'rim_brand_id';
	protected static $fields = array(
		'rim_brand_id',
		'rim_brand_slug',
		'rim_brand_name',
		'rim_brand_description',
		'rim_brand_logo',
        'rim_brand_inserted_at',
	);

	/**
	 * @var array
	 */
	protected static $db_init_cols = array(
		'rim_brand_id' => 'int(11) unsigned NOT NULL auto_increment',
		'rim_brand_slug' => 'varchar(255) default \'\'',
		'rim_brand_name' => 'varchar(255) default \'\'',
		'rim_brand_description' => 'text',
		'rim_brand_logo' => 'varchar(255) default \'\'',
        'rim_brand_inserted_at' => 'varchar(255) default \'\'',
	);

	/**
	 * @var array
	 */
	protected static $db_init_args = array(
		'PRIMARY KEY (`rim_brand_id`)',
	);

	/**
	 * DB_Rim_Brand constructor.
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

        $q = 'select * from rim_models where rim_brand_id = :brand_id ';
        $params = [];
        $params[] = [ 'brand_id', $brand_id, '%d' ];

        return array_map( function( $row ) {
            return DB_Rim_Model::create_instance_or_null( $row );
        }, $db->get_results( $q, $params ) );
    }

    /**
     * @param $slug
     * @return DB_Rim_Brand|null
     */
	public static function get_instance_via_slug( $slug ) {

		$db = get_database_instance();

		$q  = '';
		$q  .= 'SELECT * ';
		$q  .= 'FROM ' . static::$table . ' ';
		$q  .= 'WHERE rim_brand_slug = :slug ';
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
	 * Can return html
	 */
	public function get_description(){
		$v = $this->get( 'description' );
		return gp_render_textarea_content( $v );
	}

    /**
     * @param bool $fallback
     * @return string
     */
	public function get_logo( $fallback = false ){

		$ret = get_image_src( $this->get( 'rim_brand_logo' ) );
		$ret = trim( $ret );

		if ( $fallback && ! $ret ) {
			$ret = image_not_available();
		}

		return $ret;
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
     * @param $key
     * @param $value
     * @return null|string
     */
	public function get_cell_data_for_admin_table( $key, $value ) {

		switch( $key ) {
			case 'rim_brand_description':
			    return gp_test_input( $value );
            case 'rim_brand_logo':
                return format_image_cell_data_for_admin_table( $value );
            case 'linked_page':
                // returning raw value here bypassses sanitation. In filter_row_for_admin_tables
                // we set this to be an anchor tag.
                return $value;
		}

		return null;
	}

	/**
	 * @return DB_Page|null|mixed
	 */
	public function get_page(){
		$name = DB_Page::page_name_from_rim_brand_slug( $this->get( 'slug' ) );
		return $name ? DB_Page::get_instance_via_name( $name, false ) : false;
	}

	/**
	 * @return string
	 */
	public function get_archive_url(){
	    return Router::build_url( ['wheels', $this->get( 'slug' )]);
	}
}
