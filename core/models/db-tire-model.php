<?php

/**
 * Class DB_Tire_Model
 */
Class DB_Tire_Model extends DB_Product_Model {

    use DB_Tire_Model_Or_Rim_Finish;
    public static $tire_model_or_rim_finish_type = 'tire';

    protected static $prefix = 'tire_model_';
    protected static $table = DB_tire_models;
    protected static $primary_key = 'tire_model_id';

    /**
     * @var array
     */
    protected static $fields = array(
        'tire_model_id',
        'tire_brand_id',
        'tire_model_slug',
        'tire_model_name',
        'tire_model_type',
        'tire_model_category',
        'tire_model_class',
        'tire_model_run_flat',
        'tire_model_description',
        'tire_model_image',
        'tire_model_image_origin',
        'tire_model_inserted_at',
    );

    /**
     * @var array
     */
    protected static $req_cols = array(
        'tire_model_id',
        'tire_brand_id',
        'tire_model_slug',
        'tire_model_name',
        'tire_model_type',
        'tire_model_category',
        'tire_model_class',
        'tire_model_run_flat',
    );

    /**
     * @var array
     */
    protected static $db_init_cols = array(
        'tire_model_id' => 'int(11) unsigned NOT NULL auto_increment',
        'tire_brand_id' => 'int(11) unsigned NOT NULL',
        'tire_model_slug' => 'varchar(255) default \'\'',
        'tire_model_name' => 'varchar(255) default \'\'',
        'tire_model_type' => 'varchar(255) default \'\'',
        'tire_model_class' => 'varchar(255) default \'\'',
        'tire_model_category' => 'varchar(255) default \'\'',
        'tire_model_run_flat' => 'varchar(255) default \'\'',
        'tire_model_description' => 'longtext',
        'tire_model_image' => 'varchar(255) default \'\'',
        'tire_model_image_origin' => 'varchar(255) default \'\'',
        'tire_model_inserted_at' => 'varchar(255) default \'\'',
    );

    /**
     * @var array
     */
    protected static $db_init_args = array(
        'PRIMARY KEY (`tire_model_id`)',
        'FOREIGN KEY (tire_brand_id) REFERENCES ' . DB_tire_brands . '(tire_brand_id)',
    );

    /** @var DB_Tire_Brand|null */
    public $brand;

    /** @var DB_Tire_Model_Type */
    public $type;

    /** @var DB_Tire_Model_Category */
    public $category;

    /** @var DB_Tire_Model_Class */
    public $class;

    /** @var DB_Tire_Model_Run_Flat */
    public $run_flat;

    public function __construct( $data, $options = array() ) {

        parent::__construct( $data, $options );

        $b = @$options[ 'brand' ];

        // haven't really tested this part
        if ( $b ) {
            if ( $b instanceof DB_Tire_Brand ) {
                $this->brand = $options[ 'brand' ];
            } else if ( is_array( $b ) || is_object( $b ) ) {
                $this->brand = DB_Tire_Brand::create_instance_or_null( $b );
            }
        }

        // note: data for these objects is likely hardcoded, therefore the lines below don't require database queries
        $this->type = DB_Tire_Model_Type::create_instance_via_slug( $this->get( 'tire_model_type' ) );
        $this->category = DB_Tire_Model_Category::create_instance_via_slug( $this->get( 'tire_model_category' ) );
        $this->class = DB_Tire_Model_Class::create_instance_via_slug( $this->get( 'tire_model_class' ) );
        $this->run_flat = DB_Tire_Model_Run_Flat::create_instance_via_slug( $this->get( 'tire_model_run_flat' ) );
    }

    /**
     * @return bool
     */
    public function setup_brand() {

        if ( ! $this->brand instanceof DB_Tire_Brand ) {
            // could return null if PK not found in DB
            $this->brand = DB_Tire_Brand::create_instance_via_primary_key( $this->get( 'tire_brand_id' ) );
        }

        return $this->brand instanceof DB_Tire_Brand;
    }

    /**
     * @param $slug
     * @param $brand_id
     * @param array $options
     * @return DB_Tire_Model|null
     * @throws Exception
     */
    public static function get_instance_by_slug_brand( $slug, $brand_id, $options = array() ) {

        $db = get_database_instance();

        $q = '';
        $q .= 'SELECT * ';
        $q .= 'FROM ' . static::$table . ' ';
        $q .= 'WHERE tire_model_slug = :slug ';
        $q .= 'AND tire_brand_id = :brand ';
        $q .= ';';
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
     * Can return html
     */
    public function get_description() {

        $v = $this->get( 'description' );
        return gp_render_textarea_content( $v );
    }

    /**
     * @param $key
     * @param $value
     *
     * @return null|string
     */
    public function get_cell_data_for_admin_table( $key, $value ) {

        switch ( $key ) {
            case 'tire_brand_id':

                // many sql queries. not super efficient but that's ok.
                $brand = DB_Tire_Brand::create_instance_via_primary_key( $value );
                $name = $brand ? $brand->get( 'name', '', true ) : '';

                return get_admin_single_edit_anchor_tag( DB_tire_brands, $value ) . " ($name)";
            case 'tire_model_description':
                return gp_test_input( $value );
            case 'tire_model_image':
                return format_image_cell_data_for_admin_table( $value );
        }

        // returning null is not the same as false or "" here
        return null;
    }

    /**
     * Returns an array of tire model instances, each of which have a brand
     * instance setup, and does so efficiently (using an inner join instead of
     * additional queries).
     *
     * @param string $order_by_sql
     * @return array
     */
    public static function query_all( $order_by_sql = '' ) {

        if ( ! $order_by_sql ) {
            $order_by_sql = 'tire_brand_slug, tire_model_slug, tire_model_id';
        }

        $db = get_database_instance();
        $q = '';

        // will unset some manually, so for now just select *
        $q .= 'SELECT * ';
        $q .= 'FROM tire_models AS m ';
        $q .= 'INNER JOIN tire_brands AS b on m.tire_brand_id = b.tire_brand_id ';
        $q .= 'ORDER BY ' . $order_by_sql . ' ';
        $q .= ';';

        $records = $db->get_results( $q );

        return array_map( function ( $row ) {

            // pass the same data to tire brand constructor because
            // all column names are unique among these 2 tables.
            $brand = DB_Tire_Brand::create_instance_or_null( $row );

            return DB_Tire_Model::create_instance_or_null( $row, [
                'brand' => $brand
            ] );

        }, $records );
    }
}
