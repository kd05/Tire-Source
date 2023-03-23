<?php

/**
 * Class DB_Rim_Model
 */
Class DB_Rim_Model extends DB_Product_Model{

	protected static $prefix = 'rim_model_';
	protected static $table = DB_rim_models;
	protected static $primary_key = 'rim_model_id';
	protected static $fields = array(
		'rim_model_id',
		'rim_brand_id',
		'rim_model_slug',
		'rim_model_name',
        'rim_model_description',
        'rim_model_inserted_at',
	);

	/**
	 * @var array
	 */
	protected static $db_init_cols = array(
		'rim_model_id' => 'int(11) unsigned NOT NULL auto_increment',
		'rim_brand_id' => 'int(11) unsigned NOT NULL',
		'rim_model_slug' => 'varchar(255) default \'\'',
		'rim_model_name' => 'varchar(255) default \'\'',
        'rim_model_description' => 'text',
        'rim_model_inserted_at' => 'varchar(255) default \'\'',
	);

	/**
	 * @var array
	 */
	protected static $db_init_args = array(
		'PRIMARY KEY (`rim_model_id`)',
		'FOREIGN KEY (rim_brand_id) REFERENCES ' . DB_rim_brands . '(rim_brand_id)',
	);

	public function __construct( $data, $options = array() ) {
		parent::__construct( $data, $options );
	}

    /**
     * @param $slug
     * @return DB_Rim_Model|null
     */
	public static function get_instance_by_slug_brand( $slug, $brand_id, $options = array() ) {

		$db = get_database_instance();

		$q  = '';
		$q  .= 'SELECT * ';
		$q  .= 'FROM ' . static::$table . ' ';
		$q  .= 'WHERE rim_model_slug = :slug ';
		$q  .= 'AND rim_brand_id = :brand ';
		$q  .= '';
		$q  .= '';
		$q  .= ';';
		$st = $db->pdo->prepare( $q );
		$st->bindValue( 'slug', $slug, $db->str );
		$st->bindValue( 'brand', $brand_id, $db->str );
		$st->execute();
		$obj = $st->fetchObject();

		if ( ! $obj ) {
			return null;
		}

		$db_obj = static::create_instance_or_null( $obj, $options );
		if ( $db_obj ) {
			return $db_obj;
		}

		return null;
	}

	/**
	 * @param $key
	 * @param $value
	 *
	 * @return null|string
	 */
	public function get_cell_data_for_admin_table( $key, $value ){

		switch( $key ){
			case 'rim_brand_id':

			    // many sql queries. not super efficient but that's ok.
			    $brand = DB_Rim_Brand::create_instance_via_primary_key( $value );
			    $name = $brand ? $brand->get( 'name', '', true ) : '';

				return get_admin_single_edit_anchor_tag( DB_rim_brands, $value ) . " ($name)";

            case 'rim_model_description':
                return gp_test_input( $value );
		}

		// returning null is not the same as false or "" here
		return null;
	}
}