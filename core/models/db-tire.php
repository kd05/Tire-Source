<?php

/**
 * A class to interact with Tires table
 *
 * Class Tire
 */
Class DB_Tire extends DB_Product {

	protected static $primary_key = 'tire_id';
	protected static $table = DB_tires;

	/*
	 * An array of keys required to instantiate the object.
	 */
	protected static $req_cols = array( 'part_number' );

	// db columns
	protected static $fields = array(
		'tire_id',
		'part_number',
		'supplier',
		'brand_id',
		'brand_slug',
		'model_id',
		'model_slug',
		'size',
		'description',
		'width',
		'profile',
		'diameter',
		'load_index',
		'load_index_2',
		'speed_rating',
		'is_zr',
		'extra_load',
		'utqg',
		'tire_sizing_system',
        'import_name',
        'import_date',
		'msrp_ca',
		'cost_ca',
		'price_ca',
		'sold_in_ca',
		'stock_amt_ca',
		'stock_sold_ca',
		'stock_unlimited_ca',
		'stock_discontinued_ca',
		'stock_update_id_ca',
		'msrp_us',
		'cost_us',
		'price_us',
		'sold_in_us',
		'stock_amt_us',
		'stock_sold_us',
		'stock_unlimited_us',
		'stock_discontinued_us',
		'stock_update_id_us',
        'sync_id_insert_ca',
        'sync_date_insert_ca',
        'sync_id_update_ca',
        'sync_date_update_ca',
        'sync_id_insert_us',
        'sync_date_insert_us',
        'sync_id_update_us',
        'sync_date_update_us',
	);

	/**
	 * @var array
	 */
	protected static $db_init_cols = array(
		'tire_id' => 'int(11) unsigned NOT NULL auto_increment',
		'part_number' => 'varchar(255) default \'\'',
		'supplier' => 'varchar(255) default \'\'',
		'brand_id' => 'int(11) unsigned default NULL',
		'brand_slug' => 'varchar(255) default \'\'',
		'model_id' => 'int(11) unsigned default NULL',
		'model_slug' => 'varchar(255) default \'\'',
		// short description string with size + load index + speed rating and possibly more
		// note: this can also contain some information that isn't even available in other columns
		'size' => 'varchar(255) default \'\'', // 225/65R17
		'description' => 'longtext',
		'width' => 'varchar(255) default \'\'', // 225
		'profile' => 'varchar(255) default \'\'', // 65
		'diameter' => 'varchar(255) default \'\'', // 17
		'load_index' => 'varchar(255) default \'\'',
		// some light truck tires have 2 load indexes, one for when mounted on the inner of 2 adjacent tires
		'load_index_2' => 'varchar(255) default \'\'',
		'speed_rating' => 'varchar(255) default \'\'',
		'is_zr' => 'bool default NULL', // probably "XL" or ""
		'extra_load' => 'varchar(255) default \'\'', // probably "XL" or ""
		'utqg' => 'varchar(255) default \'\'', // universal tire quality grade UTQG
		'tire_sizing_system' => 'varchar(255) default \'\'',
        'import_name' => 'varchar(255) default \'\'',
        'import_date' => 'varchar(255) default \'\'',
		'msrp_ca' => 'varchar(255) default \'\'',
		'cost_ca' => 'varchar(255) default \'\'',
		'price_ca' => 'varchar(255) default \'\'',
		'sold_in_ca' => 'varchar(15) default \'\'',
		'stock_amt_ca' => 'int(11) NOT NULL default 0',
		'stock_sold_ca' => 'int(11) NOT NULL default 0',
		'stock_unlimited_ca' => 'bool DEFAULT 1',
		'stock_discontinued_ca' => 'bool DEFAULT 0',
		'stock_update_id_ca' => 'int(11) default NULL',
		'msrp_us' => 'varchar(255) default \'\'',
		'cost_us' => 'varchar(255) default \'\'',
		'price_us' => 'varchar(255) default \'\'',
		'sold_in_us' => 'varchar(15) default \'\'',
		'stock_amt_us' => 'int(11) NOT NULL default 0',
		'stock_sold_us' => 'int(11) NOT NULL default 0',
		'stock_unlimited_us' => 'bool DEFAULT 1',
		'stock_discontinued_us' => 'bool DEFAULT 0',
		'stock_update_id_us' => 'int(11) default NULL',
        'sync_id_insert_ca' => 'int(11) default NULL',
        'sync_date_insert_ca' => 'varchar(255) default \'\'',
        'sync_id_update_ca' => 'int(11) default NULL',
        'sync_date_update_ca' => 'varchar(255) default \'\'',
        'sync_id_insert_us' => 'int(11) default NULL',
        'sync_date_insert_us' => 'varchar(255) default \'\'',
        'sync_id_update_us' => 'int(11) default NULL',
        'sync_date_update_us' => 'varchar(255) default \'\'',
	);

	/**
	 * @var array
	 */
	protected static $db_init_args = array(
		'PRIMARY KEY (tire_id)',
		'FOREIGN KEY (brand_id) REFERENCES ' . DB_tire_brands . '(tire_brand_id)',
		'FOREIGN KEY (model_id) REFERENCES ' . DB_tire_models . '(tire_model_id)',
//		'CHK_STOCK_CA' => 'CONSTRAINT CHK_STOCK_CA CHECK ( stock_unlimited_ca = 0 OR stock_discontinued_ca = 0 )',
//		'CHK_STOCK_US' => 'CONSTRAINT CHK_STOCK_US CHECK ( stock_unlimited_us = 0 OR stock_discontinued_us = 0 )'
	);

	/**
	 * Tire constructor.
	 *
	 * @param $data
	 */
	public function __construct( $data, $options = array() ) {

		parent::__construct( $data, $options );

		// if you are querying large amounts of data at once, try to pass
        // inner join brand and models and then create those objects beforehand and pass
        // them in, in order to avoid extra database queries.
		$brand = gp_if_set( $options, 'brand' );
		$model = gp_if_set( $options, 'model' );

		if ( $brand instanceof DB_Tire_Brand ) {
			$this->brand = $brand;
		} else {
			$this->brand = DB_Tire_Brand::create_instance_via_primary_key( $this->get( 'brand_id' ) );
		}

		if ( $model instanceof DB_Tire_Model ) {
			$this->model = $model;
		} else {
			$this->model = DB_Tire_Model::create_instance_via_primary_key( $this->get( 'model_id' ) );
		}

		// we want this to be true, but.. unfortunately, there are a few times when we
		// create an empty instance of this object intentionally, and therefore, we should
		// not assert these.
//		if ( ! CREATING_TABLES ) {
//            assert( $this->brand->get( 'tire_brand_id' ) === $this->get( 'brand_id' ) );
//            assert( $this->model->get( 'tire_model_id' ) === $this->get( 'model_id' ) );
//        }
	}

    /**
     * Note: this returns a single product from a list of possibly very many products. Which product
     * we choose might be totally insignificant, OR we may try to prioritize products based on some condition.
     * I am not sure yet.
     *
     * @param $brand
     * @param $model
     * @return DB_Tire|null
     */
	public static function get_single_product_from_brand_model( $brand, $model ) {

		// assuming inputs could be user input
		$brand = gp_test_input( $brand );
		$model = gp_test_input( $model );

		if ( ! $brand || ! $model ) {
			return null;
		}

		$db = get_database_instance();
		$p = [];
		$q = '';
		$q .= 'SELECT * ';
		$q .= 'FROM ' . static::$table . ' ';

		$q .= 'WHERE 1 = 1 ';

		$q .= 'AND brand_slug = :brand_slug ';
		$p[] = [ 'brand_slug', $brand ];

		$q .= 'AND model_slug = :model_slug ';
		$p[] = [ 'model_slug', $model ];

		// should be redundant
		// $q .= 'GROUP BY part_number ';

		// order by not super important but lets ALWAYS leave the primary key present
		// to ensure we don't get different products with the same input
		// $q .= 'ORDER BY tire_id ';

		$q .= 'LIMIT 0,1 ';
		$q .= ';';

		$results = $db->get_results( $q, $p );

		$row = gp_if_set( $results, 0 );

		if ( $row ) {
			return self::create_instance_or_null( $row );
		}

		return null;
	}

	/**
	 * @return bool|mixed|string
	 */
	public function get_model_type_name(){
		$type = $this->model->get( 'type' );
		$types = Static_Array_Data::tire_model_types();
		$data = gp_if_set( $types, $type, array() );
		$ret = gp_if_set( $data, 'name', '' );
		$ret = trim( $ret );
		return $ret;
	}

	/**
	 * @return string
	 */
	public function brand_model_name(){
		return brand_model_name( $this->brand, $this->model );
	}

	/**
	 * future spot for dual load index return text..
	 */
	public function get_load_index_text(){

		$li_1 = $this->get( 'load_index' );
		$li_2 = $this->get( 'load_index_2' );

		// should always have at least the first ..
		$arr = array();

		$arr[] = $li_1;
		if ( $li_2 ) {
			$arr[] = $li_2;
		}

		$ret = implode( '/', $arr );
		$ret = trim( $ret );
		return $ret;
	}

	/**
	 * Ie. Ovation Tires TR192 (All Season)
	 *
	 * @return string
	 */
	public function get_brand_model_and_model_type_name(){

		$arr = [];
		$arr[] = $this->brand->get( 'name' );
		$arr[] = $this->model->get( 'name' );
		$type_name = $this->get_model_type_name();
		$arr[] = " ($type_name)";

		return implode( " ", $arr );
	}

	/**
	 * Sometimes this is empty..
	 */
	public function get_description(){

		$ret = $this->get( 'description' ) ?? '';
		$ret = trim( $ret );

		if ( $ret ) {
			return $ret;
		}

		$model = $this->model->get( 'name' );
		$brand = $this->brand->get( 'name' );
		$type = $this->get_model_type_name();
		$spec = $this->get_spec();

		$ret = '';
		$ret .= $model;
		$ret .= ' ' . $brand;
		$ret .= ' ' . $type;
		$ret .= ' (' . $spec . ')';

		$ret = trim( $ret );
		return $ret;
	}

	/**
	 *
	 */
	public function get_cart_title(){
		return $this->get_description();
	}

	/**
	 * @param $thing
	 */
	public function set_brand_object( $thing = null ) {

		$obj = null;

		if ( $thing instanceof DB_Tire_Brand ) {
			$this->brand = $thing;
			return true;
		}

		if ( ! gp_is_singular( $thing ) ) {

			// makes sure array/object has some required fields
			$obj = DB_Tire_Brand::create_instance_or_null( $thing );
		}

		if ( ! $obj instanceof DB_Tire_Brand ) {
			$obj = DB_Tire_Brand::create_instance_via_primary_key( $this->get( 'brand' ) );
		}

		if ( $obj instanceof DB_Tire_Brand ) {
			$this->brand = $obj;
			return true;
		}

		return false;
	}

	/**
	 * @return bool|int|mixed
	 */
	public function get_price_dollars_raw( $locale = null ){
		$locale = app_get_locale_from_locale_or_null( $locale );
		$val = $this->get( $this->get_price_column( $locale ) );
		$val = $val ? $val : 0;
		return $val;
	}

	/**
	 * @return float|int|mixed|null
	 */
	public function get_price_dollars_formatted( $thousands_sep = ',', $before = '$', $locale = null ){
		$locale = app_get_locale_from_locale_or_null( $locale );
		$val = $this->get_price_dollars_raw( $locale );
		return print_price_dollars( $val, $thousands_sep, $before, $locale );
	}

	/**
	 * @return array
	 */
	public function summary_array( $pre = '' ){
		$ret = array();

		$size_data = array();
		$size_data[] = $this->get('size' );

		$model_data = array();
		$model_data[] = $this->get( 'brand_slug' ) . ' (' . $this->get( 'model_slug' ) . ')';
		$model_data[] = $this->model->get( 'class' );
		$model_data[] = $this->model->get( 'category' );
		$model_data[] = $this->model->get( 'type' );

		$ret['part_number'] = $this->get( 'part_number' ) . ' (' . $this->get_primary_key_value() . ')';
		$ret['sr'] = $this->get( 'speed_rating' ) . ' (' . $this->get( 'load_index' ) . ')';
		$ret['description'] = $this->get( 'description' );
		$ret['model'] = implode_comma(  $model_data );
		$ret['size'] = implode_comma(  $size_data );
		$ret['type'] = $this->get( 'type' );
		$ret['price'] = $this->get( 'price' ) . ' (msrp: . ' . $this->get( 'msrp' ) . ')';

		if ( $pre ) {
			$ret = gp_array_keys_add_prefix( $pre, $ret );
		}

		return $ret;
	}

	/**
	 * We may store both full size string, and parsed size string in the database,
	 * therefore, we should use a function to display, because i'm not sure
	 * if we will show the size column, or re-assemble the other cols into one.
	 *
	 * Note: this is raw CSV value. In the future, we may want to assemble this
	 * from primitive values, BUT we'll lose some information. On the plus side,
	 * formatting will be more consistent. Right now there are inconsistencies in spacing.
	 *
	 * If we do update this, be aware that many other places may be using $this->get( 'size' )
	 * directly, and those will need to be updated.
	 */
	public function get_size(){
		$ret = $this->get( 'size' );
		return $ret;
	}

	/**
	 * @return string
	 */
	public function get_sizing_specs(){

		$size = $this->get( 'size' );
		$load_index = $this->get( 'load_index' );
		$speed_rating = $this->get( 'speed_rating' );

		// todo: when truck tires load index are split into 2 cols, we'll need to modify this (maybe)
		$ret = '';
		$ret .= $size;
		$ret .= ' ' . $load_index;
		$ret .= ' ' . $speed_rating;
		$ret .= '';
		return $ret;
	}

	/**
	 *
	 */
	public function get_name() {
		// obviously change this later
		return $this->get( 'brand' ) . ' - ' . $this->get( 'model' );
	}

    /**
     * @param string $size
     * @param bool $fallback
     * @return bool|string
     */
	public function get_image_url( $size = 'reg', $fallback = true ){
		return $this->model->get_image_url( $size, $fallback );
	}

    /**
     * @return array
     */
	public function get_slugs(){
	    return [ $this->get( 'brand_slug' ), $this->get( 'model_slug' ) ];
    }

    /**
     * @param bool $with_part_number
     * @param array $query
     * @return string
     */
	public function get_url( $with_part_number = false, $query = [] ){
	    $part_numbers = $with_part_number ? [ $this->get( 'part_number' ) ] : [];
	    return get_tire_model_url( $this->get_slugs(), $part_numbers, [], $query );
	}

    /**
     * @return string
     */
    public function get_url_with_part_number(){
        return $this->get_url( true );
    }

	/**
	 * ie. "97 V",  or for some truck tires, "101/99 H"
     *
     * @param null $load_index
     * @param null $speed_rating
     * @return string
     */
	public function get_spec( $load_index = null, $speed_rating = null ){

		$load_index = $load_index !== null ? $load_index : $this->get_load_index_text();
		$speed_rating = $speed_rating !== null ? $speed_rating : $this->get( 'speed_rating' );

		$ret = $load_index . ' ' . $speed_rating;
		return $ret;
	}

	/**
	 * You'll have to add to this array when adding to a package, or when a vehicle is
	 * present but want to create a new package upon adding to cart.
     *
     * @param null $vehicle
     * @param null $package
     * @return array
     */
	public function add_to_cart_default_args( $vehicle = null, $package = null ){

		$ajax_action = 'add_to_cart';

		$args = array(
			'url' => AJAX_URL,
			'ajax_action' => $ajax_action,
			'nonce' => get_nonce_value( $ajax_action ),
			'part_number' => $this->get( 'part_number' ),
			'type' => 'tire',
		);

		if ( $vehicle instanceof Vehicle ) {
			// not sanitizing user input because it probably already was sanitized, but also because
			// if vehicle is complete, it means the api returned results, meaning user input is valid.
			if ( $vehicle->is_complete() ) {
				$args['make'] = $vehicle->make;
				$args['model'] = $vehicle->model;
				$args['year'] = $vehicle->year;
				$args['trim'] = $vehicle->trim;
				$args['fitment'] = $vehicle->fitment_slug;
			}
		}

		// the add the cart handler should verify package exists and if it does not should
		// try its best to handle it correctly. Therefore, we don't check whether the package exists here first.
		if ( $package ) {
			$args['pkg'] = $package;
		}

		return $args;
	}

    /**
     * create a partial instance with only data pertaining to brand and model.
     *
     * this isn't used much, but lets us simply hold brand + model in one object, plus
     * we could potentially use methods that rely on both brand + model, but are found
     * in the DB_Tire class.
     *
     * @param $brand_slug
     * @param $model_slug
     * @return DB_Tire|null
     */
	public static function get_partial_product( $brand_slug, $model_slug ) {

		$brand = DB_Tire_Brand::get_instance_via_slug( $brand_slug );

		if ( $brand ) {

			$model = DB_Tire_Model::get_instance_by_slug_brand( $model_slug, $brand->get_primary_key_value() );

			if ( $model ) {

				return new static( array(
					'brand_slug' => $brand->get( 'slug' ),
					'brand_id' => $brand->get_primary_key_value(),
					'model_slug' => $model->get( 'slug' ),
					'model_id' => $model->get_primary_key_value(),
				), array(
					'brand' => $brand,
					'model' => $model,
				));
			}
		}

		return null;
	}

	/**
	 *
	 */
	public function get_admin_archive_page_args(){
		$args = array();
		$args['do_delete'] = true;
		return $args;
	}

	/**
	 * @param $key
	 *
	 * @return null
	 */
	public function get_cell_data_for_admin_table( $key, $value ){

		switch( $key ){
			case 'part_number':
				$ret = get_anchor_tag_simple( $this->get_url_with_part_number(), $this->get( 'part_number' ), [
					'target' => '_blank',
				] );
				return $ret;
				break;
			case 'stock_update_id_ca':
				return get_admin_single_edit_anchor_tag( DB_stock_updates, $value );
				break;
			case 'stock_update_id_us':
				return get_admin_single_edit_anchor_tag( DB_stock_updates, $value ); 
				break;
			case 'supplier':
				return get_admin_single_edit_anchor_tag( DB_suppliers, $value );
				break;
			case 'model_id':
				return get_admin_single_edit_anchor_tag( DB_tire_models, $value );
				break;
			case 'brand_id':
				return get_admin_single_edit_anchor_tag( DB_tire_brands, $value );
				break;
		}

		// returning null is not the same as false or "" here
		return null;
	}
}
