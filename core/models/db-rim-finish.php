<?php

/**
 * decided we had to add this class as a way to store images for rims.
 * its unfortunate I did it late, because there is some data redundancy in the rims
 * table, which also stores most of the data found here, and well, things are complicated.
 *
 * the redundancy occassionally can speed up some queries and also make them easier, but
 * going forward, I will likely try to rely on using only the rim finishes table and
 * ignore the redundant data in the rims table.
 *
 * Class DB_Cache
 */
Class DB_Rim_Finish extends DB_Table {

    use DB_Tire_Model_Or_Rim_Finish;
    public static $tire_model_or_rim_finish_type = 'rim';

    protected static $primary_key = 'rim_finish_id';
    protected static $table = DB_rim_finishes;

    /*
     * An array of keys required to instantiate the object.
     */
    protected static $req_cols = array(
        'color_1',
        'color_2',
        'finish',
// admin user can delete these, lets not have them be required.
//        'color_1_name',
//        'color_2_name',
//        'finish_name',
    );

    // db columns
    protected static $fields = array(
        'rim_finish_id',
        'model_id',
        'color_1',
        'color_2',
        'finish',
        'color_1_name',
        'color_2_name',
        'finish_name',
        'image_local',
        'image_source',
        'image_source_new',
        'rim_finish_inserted_at',
    );

    protected static $db_init_cols = array(
        'rim_finish_id' => 'int(11) unsigned NOT NULL auto_increment',
        'model_id' => 'int(11) unsigned NOT NULL',
        'color_1' => 'varchar(255) default \'\'',
        'color_2' => 'varchar(255) default \'\'',
        'finish' => 'varchar(255) default \'\'',
        'color_1_name' => 'varchar(255) default \'\'',
        'color_2_name' => 'varchar(255) default \'\'',
        'finish_name' => 'varchar(255) default \'\'',
        'image_local' => 'varchar(511) default \'\'',
        'image_source' => 'varchar(511) default \'\'',
        'image_source_new' => 'varchar(511) default \'\'',
        'rim_finish_inserted_at' => 'varchar(255) default \'\'',
    );
    protected static $db_init_args = array(
        'PRIMARY KEY (`rim_finish_id`)',
        'FOREIGN KEY (model_id) REFERENCES ' . DB_rim_models . '(rim_model_id)',
    );

    protected $data;

    /** @var  null|DB_Rim_Model */
    public $model;

    /** @var  null|DB_Rim_Brand */
    public $brand;

    /**
     * DB_Cache constructor.
     *
     * @param       $data
     * @param array $options
     */
    public function __construct( $data, $options = array() ) {

        parent::__construct( $data, $options );

        // we rarely have a finish object without a rim object therefore we won't normally
        // set these I guess. we can check setup_model() and setup_brand() if needed.
        $model = gp_if_set( $options, 'model' );
        if ( $model instanceof DB_Rim_Model ) {
            $this->model = $model;
        }

        $brand = gp_if_set( $options, 'brand' );
        if ( $brand instanceof DB_Rim_Brand ) {
            $this->brand = $brand;
        }
    }

    /**
     * @return bool
     */
    public function setup_model() {

        if ( $this->model instanceof DB_Rim_Model ) {
            return true;
        }

        $this->model = DB_Rim_Model::create_instance_via_primary_key( $this->get( 'model_id' ) );

        if ( $this->model ) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function setup_brand() {

        if ( $this->brand instanceof DB_Rim_Brand ) {
            return true;
        }

        if ( $this->setup_model() ) {

            $this->brand = DB_Rim_Brand::create_instance_via_primary_key( $this->model->get( 'rim_brand_id' ) );

            if ( $this->brand ) {
                return true;
            }
        }

        return false;
    }

    /**
     *
     */
    public function get_finish_string() {

        $c1 = $this->get( 'color_1_name' );
        $c2 = $this->get( 'color_2_name' );
        $ff = $this->get( 'finish_name' );

        return get_rim_finish_string( $c1, $c2, $ff );
    }

    /**
     * Override DB_Table::query_all so we can order by brand/model,
     * because we happen to need to sometimes.
     *
     * @param string $order_by_sql
     * @param bool $setup_brands - when true, doesn't do it super efficiently
     * @param bool $setup_models - when true, doesn't do it super efficiently
     * @return array
     */
    public static function query_all( $order_by_sql = '', $setup_brands = false, $setup_models = false ) {

        if ( ! $order_by_sql ) {
            $order_by_sql = 'rim_brand_slug, rim_model_slug, color_1, color_2, finish';
        }

        $db = get_database_instance();
        $q = '';

        // will unset some manually, so for now just select *
        $q .= 'SELECT * ';
        $q .= 'FROM ' . DB_rim_finishes . ' AS f ';
        $q .= 'INNER JOIN ' . DB_rim_models . ' AS m ON m.rim_model_id = f.model_id ';
        $q .= 'INNER JOIN ' . DB_rim_brands . ' AS b ON b.rim_brand_id = m.rim_brand_id ';
        $q .= 'ORDER BY ' . $order_by_sql . ' ';
        $q .= ';';

        $records = $db->get_results( $q );

        return array_map( function ( $row ) use ( $setup_brands, $setup_models ) {

            $obj = static::create_instance_or_null( $row );

            // note: we can setup brands and models efficiently, but it's not
            // worth it at this time. (see 2nd param of this classes constructor)

            if ( $setup_brands ) {
                $obj->setup_brand();
            }

            if ( $setup_models ) {
                $obj->setup_model();
            }

            return $obj;

        }, $records );
    }

    /**
     * @param $model_id
     * @param $color_1
     * @param $color_2
     * @param $finish
     * @param array $options
     * @return DB_Rim_Finish|null
     * @throws Exception
     */
    public static function get_instance_via_finishes( $model_id, $color_1, $color_2, $finish, $options = array() ) {

        $db = get_database_instance();

        $data = $db->get( static::$table, array(
            'model_id' => $model_id,
            'color_1' => $color_1 ? $color_1 : '',
            'color_2' => $color_2 ? $color_2 : '',
            'finish' => $finish ? $finish : '',
        ) );

        $row = $data ? gp_if_set( $data, 0 ) : false;

        if ( $row ) {
            return new static( $row, $options );
        }

        return null;
    }

    /**
     * Queries for 1 of possibly many instances using a brand and model slug,
     * returning the first one.
     *
     * @param $model_id
     * @return DB_Rim_Finish|null
     * @throws Exception
     */
    public static function get_single_instance_using_model_id( $model_id ) {

        $db = get_database_instance();

        $r = $db->get_results( 'select * from rim_finishes where model_id = :model_id order by rim_finish_id ASC', [
            [ 'model_id', $model_id, '%d' ]
        ] );

        if ( $r ) {
            return DB_Rim_Finish::create_instance_or_null( $r[ 0 ] );
        }

        return null;
    }

    /**
     * @return string
     */
    public function get_single_product_page_url() {
        return get_rim_finish_url( $this->get_slugs( true, true, true ) );
    }

    /**
     * @param $key
     * @param $value
     *
     * @return null|string
     */
    public function get_cell_data_for_admin_table( $key, $value ) {

        switch ( $key ) {
            case 'model_id':
                return get_admin_single_edit_anchor_tag( DB_rim_models, $value );
                break;
            case 'image_local':
                return format_image_cell_data_for_admin_table( $value );
                break;
        }

        // must not return false or ""
        return null;
    }

    /**
     * @param bool $brand
     * @param bool $model
     * @param bool $sanitize
     * @return array
     */
    public function get_slugs( $brand = true, $model = true, $sanitize = true ) {

        if ( $brand ) {
            $this->setup_brand();
            $_brand = $this->brand->get( 'slug' );
        } else {
            $_brand = null;
        }

        if ( $model ) {
            $this->setup_model();
            $_model = $this->model->get( 'slug' );
        } else {
            $_model = null;
        }

        $items = array_filter( [
            $_brand,
            $_model,
            $this->get( 'color_1' ),
            $this->get( 'color_2' ),
            $this->get( 'finish' ),
        ] );

        if ( $sanitize ) {
            $items = array_map( 'gp_test_input', $items );
        }

        return $items;
    }

    // useful with list()
    public function get_colors_arr(){
        return [ $this->get_and_clean( 'color_1' ), $this->get_and_clean( 'color_2' ), $this->get_and_clean( 'finish' ) ];
    }
}

/**
 * @param $model_id
 * @param $color_1
 * @param $color_2
 * @param $finish
 * @param $c1_name
 * @param $c2_name
 * @param $f_name
 * @param $image_local
 * @param $image_source
 * @param $image_source_new
 *
 * @return bool|string
 */
function register_rim_finish( $model_id, $color_1, $color_2, $finish, $c1_name, $c2_name, $f_name, $image_local, $image_source, $image_source_new ) {

    $obj = DB_Rim_Finish::get_instance_via_finishes( $model_id, $color_1, $color_2, $finish );

    if ( $obj ) {
        return false;
    }

    $db = get_database_instance();

    $rim_finish_id = $db->insert( DB_rim_finishes, array(
        'model_id' => (int) $model_id,
        'color_1' => gp_test_input( $color_1 ),
        'color_2' => gp_test_input( $color_2 ),
        'finish' => gp_test_input( $finish ),
        'color_1_name' => gp_test_input( $c1_name ),
        'color_2_name' => gp_test_input( $c2_name ),
        'finish_name' => gp_test_input( $f_name ),
        'image_local' => gp_test_input_alt( $image_local ),
        'image_source' => gp_test_input_alt( $image_source ),
        'image_source_new' => gp_test_input_alt( $image_source_new ),
    ) );

    return $rim_finish_id ? $rim_finish_id : false;
}