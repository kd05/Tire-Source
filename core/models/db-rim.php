<?php

/**
 * A class to interact with Rims table
 *
 * Class DB_Rim
 */
Class DB_Rim extends DB_Product {

	protected static $primary_key = 'rim_id';
	protected static $table = DB_rims;

	/*
	 * An array of keys required to instantiate the object.
	 */
	protected static $fields = array(
		'rim_id',
		'part_number',
		'supplier',
		'type',
		'style',
		'brand_id',
		'brand_slug',
		'model_id',
		'model_slug',
		'finish_id',
		'color_1',
		'color_2',
		'finish',
		'size',
		'width',
		'diameter',
		'bolt_pattern_1',
		'bolt_pattern_2',
		'seat_type',
		'offset',
		'center_bore',
		'import_name',
		'import_date',
		'msrp_ca',
		'cost_ca',
		'price_ca',
		'sold_in_ca',
		'stock_amt_ca',
		'stock_sold_ca',
		'stock_unlimited_ca',
		'stock_update_id_ca',
		'msrp_us',
		'cost_us',
		'price_us',
		'sold_in_us',
		'stock_amt_us',
		'stock_sold_us',
		'stock_unlimited_us',
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

	protected static $req_cols = array( 'part_number', 'bolt_pattern_1', 'center_bore' );
	// db columns

	/**
	 * @var array
	 */
	protected static $db_init_cols = array(
		'rim_id' => 'int(11) unsigned NOT NULL auto_increment',
		'part_number' => 'varchar(255) default \'\'',
		'supplier' => 'varchar(255) default \'\'',
		'type' => 'varchar(255) default \'\'',
		'style' => 'varchar(255) default \'\'',
		'brand_id' => 'int(11) unsigned NOT NULL',
		'brand_slug' => 'varchar(255) default \'\'',
		'model_id' => 'int(11) unsigned NOT NULL',
		'model_slug' => 'varchar(255) default \'\'',
		'finish_id' => 'int(11) unsigned NOT NULL',
		// slugs.. redundant yes, but also sometimes useful to have these 3 items here, and not have to inner join on finish id
		'color_1' => 'varchar(255) default \'\'',
		'color_2' => 'varchar(255) default \'\'',
		'finish' => 'varchar(255) default \'\'',
		'size' => 'varchar(255) default \'\'',
		'width' => 'varchar(255) default \'\'',
		'diameter' => 'varchar(255) default \'\'',
		'bolt_pattern_1' => 'varchar(255) default \'\'',
		'bolt_pattern_2' => 'varchar(255) default \'\'',
		'seat_type' => 'varchar(255) default \'\'',
		'offset' => 'varchar(255) default \'\'',
		'center_bore' => 'varchar(255) default \'\'',
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

	// ALTER TABLE rims ADD stock int(11) NOT NULL default 0
	// ALTER TABLE rims ADD stock_sold int(11) NOT NULL default 0
	// ALTER TABLE rims ADD stock_unlimited bool DEFAULT 1
	// ALTER TABLE rims ADD stock_update_id int(11) default NULL

	// ALTER TABLE tires ADD stock int(11) NOT NULL default 0
	// ALTER TABLE tires ADD stock_sold int(11) NOT NULL default 0
	// ALTER TABLE tires ADD stock_unlimited bool DEFAULT 1
	// ALTER TABLE tires ADD stock_update_id int(11) default NULL

	// CREATE INDEX idx_rim_part_number ON rims (part_number)

	// ALTER TABLE tires ADD INDEX(part_number)

	// CREATE INDEX idx_tire_part_number ON tires (part_number)

	/**
	 * @var array
	 */
	protected static $db_init_args = array(
		'PRIMARY KEY (`rim_id`)',
		'FOREIGN KEY (model_id) references ' . DB_rim_models . '(rim_model_id)',
		'FOREIGN KEY (brand_id) references ' . DB_rim_brands . '(rim_brand_id)',
		'FOREIGN KEY (finish_id) references ' . DB_rim_finishes . '(rim_finish_id)',
//		'CHK_STOCK_CA' => 'CONSTRAINT CHK_STOCK_CA CHECK ( stock_unlimited_ca = 0 OR stock_discontinued_ca = 0 )',
//		'CHK_STOCK_US' => 'CONSTRAINT CHK_STOCK_US CHECK ( stock_unlimited_us = 0 OR stock_discontinued_us = 0 )'
	);

	protected $data;

	/** @var DB_Rim_Brand */
	public $brand;

	/** @var DB_Rim_Model */
	public $model;

	/**
	 * @var DB_Rim_Finish
	 */
	public $finish;

	/**
	 * Tire constructor.
	 *
	 * @param $data
	 */
	public function __construct( $data, $options = array() ) {

		parent::__construct( $data, $options );

		$brand  = gp_if_set( $options, 'brand' );
		$model  = gp_if_set( $options, 'model' );
		$finish = gp_if_set( $options, 'finish' );

        // if you are querying large amounts of data at once, try to pass
        // inner join brand and models and then create those objects beforehand and pass
        // them in, in order to avoid extra database queries.

		if ( $brand instanceof DB_Rim_Brand ) {
			$this->brand = $brand;
		} else {
			$this->brand = DB_Rim_Brand::create_instance_via_primary_key( $this->get( 'brand_id' ) );
		}

		if ( $model instanceof DB_Rim_Model ) {
			$this->model = $model;
		} else {
			$this->model = DB_Rim_Model::create_instance_via_primary_key( $this->get( 'model_id' ) );
		}

		if ( $finish instanceof DB_Rim_Finish ) {
			$this->finish = $finish;
		} else {
			$this->finish = DB_Rim_Finish::create_instance_via_primary_key( $this->get( 'finish_id' ), array(
				'model' => $this->model,
			) );
		}

		// we want this to be true, but.. unfortunately, there are a few times when we
		// create an empty instance of this object intentionally, and therefore, we should
		// not assert these.
//        if ( ! CREATING_TABLES ) {
//            assert( $this->brand->get( 'rim_brand_id' ) === $this->get( 'brand_id' ) );
//            assert( $this->model->get( 'rim_model_id' ) === $this->get( 'model_id' ) );
//            assert( $this->finish->get( 'rim_finish_id' ) === $this->get( 'finish_id' ) );
//        }
	}

	/**
	 * @return string
	 */
	public function brand_model_finish_name(){
		return brand_model_finish_name( $this->brand, $this->model, $this->finish );
	}

	/**
	 * @return string
	 */
	public function get_size_with_offset_string() {

		$diameter = $this->get( 'diameter' );
		$width    = $this->get( 'width' );
		$offset   = $this->get( 'offset' );

		$op = '';
		$op .= $diameter . 'x' . $width . ' ET' . $offset;
		$op = gp_test_input( $op );

		return $op;
	}

	/**
	 * @return string
	 */
	public function get_finish_string() {
		return $this->finish->get_finish_string();
	}

	/**
	 * Get all or mostly all relevant sizing information in one relatively compact string.
	 *
	 * Ie: 17" X 6" 5x100 ET35 73.1mm
	 * Ie: 17" X 6" 5x100 (4x130) ET35 73.1mm
	 *
	 * @return string
	 */
	public function get_full_sizing_specs() {
		$d   = (int) $this->get( 'diameter', null, true );
		$w   = (int) $this->get( 'width', null, true );
		$off = "ET" . (int) $this->get( 'offset', null, true );
		$bp  = $this->get_bolt_pattern_text( $this->get( 'bolt_pattern_1' ), $this->get( 'bolt_pattern_2' ) );
		$cb  = $this->get_offset_with_mm();
		return $d . "\" X " . $w . "\" $bp $off $cb";
	}

	/**
	 * Bolt pattern, offset, hub bore
	 */
	public function get_secondary_sizing_specs( $vehicle = null ) {

		$offset   = $this->get( 'offset' );
		$hub_bore = $this->get( 'center_bore' );

		$ret = '';

		// possibly filter out dual bolt patterns and show just 1 ?
		// $ret .= $this->get_bolt_pattern_text( null, null, $vehicle, true );

		$bp1      = $this->get( 'bolt_pattern_1' );
		$bp2      = $this->get( 'bolt_pattern_2' );
		$ret .= $bp1;
		if ( $bp2 ) {
			$ret .= ' (' . $bp2 . ')';
		}

		$ret .= ' ET' . $offset;
		$ret .= ' ' . $hub_bore;

		return $ret;
	}

	/**
	 *
	 */
	public function get_cart_title() {

		$brand  = $this->brand->get( 'name' );
		$model  = $this->model->get( 'name' );
		$finish = $this->get_finish_string();

		$ret = '';
		$ret .= $brand . ' ' . $model;

		if ( $finish ) {
			$ret .= ' (' . $finish . ')';
		}

		$diameter = $this->get( 'diameter' );
		$width    = $this->get( 'width' );

		$ret .= ' Wheels ' . $diameter . '" X ' . $width . '"';

		return $ret;
	}

	/**
	 * Brand/Model/Finishes
	 */
	public function get_description() {
		$brand = $this->brand->get( 'name' );
		$model = $this->model->get( 'name' );
		$op    = '';
		$op    .= $brand . ' ' . $model;
		$op    .= ' (' . $this->get_finish_string() . ')';

		return $op;
	}

    /**
     * Basically the same as DB_Rim_Finish->get_slugs()
     *
     * Notice however that all slugs here are stored in the rims table,
     * which means we don't have to worry about inner joins / setting up
     * related foreign key objects to produce this array. When looping through
     * multiple products, this function is likely better to use than the
     * DB_Rim_Finish one, as it does not require additional queries.
     *
     * @return array
     */
	public function get_slugs(){
	    return [
            $this->get( 'brand_slug' ),
            $this->get( 'model_slug' ),
            $this->get( 'color_1' ),
            $this->get( 'color_2' ),
            $this->get( 'finish' ),
        ];
    }

    /**
     * Get rim finish URL
     *
     * @param $with_part_number
     * @param array $query
     * @return string
     */
	public function get_url( $with_part_number = false, array $query = [] ) {
	    // use the function below directly if you need to pass vehicle or part numbers.
        $part_numbers = $with_part_number ? [ $this->get( 'part_number' ) ] : [];
        return get_rim_finish_url( $this->get_slugs(), $part_numbers, [], $query);
	}

    /**
     * @param array $query
     * @return string
     */
    public function get_url_with_part_number( array $query = [] ) {
        return $this->get_url( true, $query );
    }

	/**
	 * @return bool|mixed|string
	 */
	public function get_offset() {
		// may add to this function in future if we need to add/remove "mm"
		// or ensure integer/float/whatever
		$v = $this->get( 'offset' );
		$v = trim( $v );
		$v = gp_test_input( $v );

		return $v;
	}

	/**
	 * @return string
	 */
	public function get_offset_with_mm() {
		$offset = $this->get( 'offset', null, true );
		$ret    = $offset . 'mm';

		return $ret;
	}

	/**
	 * Rims are winter approved if they are steel, or if they are alloy and only have a Primary Colour,
	 * but no secondary colour or finish.
	 *
	 * WARNING: This logic is repeated inside of some SQL queries where we don't
	 * have access to the DB_Rim object in question. If you change it only here, the
	 * system will not work as intended.
	 *
	 * @return bool
	 */
	public function is_winter_approved() {

		$is = false;

		$type = $this->get_type_slug();

		if ( $type === 'steel' ) {
			$is = true;
		} else if ( $type === 'alloy' ) {

			$c1 = $this->finish->get( 'color_1', null, true );
			$c2 = $this->finish->get( 'color_2', null, true );
			$ff = $this->finish->get( 'finish', null, true );

			if ( $c1 && ! $c2 && ! $ff ) {
				$is = true;
			}

		}

		return $is;
	}

	/**
	 *
	 */
	public function get_center_bore_with_mm() {
		$cb  = $this->get( 'center_bore', null, true );
		$ret = $cb . 'mm';

		return $ret;
	}

	/**
	 * @return bool|int|mixed
	 */
	public function get_price_dollars_raw( $locale = null ) {
		$locale = app_get_locale_from_locale_or_null( $locale );
		$val = $this->get( $this->get_price_column( $locale ) );
		$val = $val ? $val : 0;
		return $val;
	}

	/**
	 * @return float|int|mixed|null
	 */
	public function get_price_dollars_formatted( $thousands_sep = ',', $before = '$', $locale_for_printing = null, $locale_for_price = null ) {
		$locale_for_price = app_get_locale_from_locale_or_null( $locale_for_price );
		$val = $this->get_price_dollars_raw( $locale_for_price );
		return print_price_dollars( $val, $thousands_sep, $before, $locale_for_printing );
	}

    /**
     * @param string $size
     * @param bool $fallback
     * @return bool|string
     */
	public function get_image_url( $size = 'reg', $fallback = true ) {
		return $this->finish->get_image_url( $size, $fallback );
	}

    /**
     * does this product have the bolt pattern described in $str,
     * each product can have a single or dual bolt pattern.
     *
     * @param $str
     * @return bool
     */
	public function has_bolt_pattern( $str ) {

		$bp1 = $this->get( 'bolt_pattern_1' );
		$bp2 = $this->get( 'bolt_pattern_2' );

		$bp1 = strtolower( $bp1 );
		$bp2 = strtolower( $bp2 );
		$str = strtolower( $str );

		$bp1 = trim( $bp1 );
		$bp2 = trim( $bp2 );
		$str = trim( $str );

		$ret = $str === $bp1 || $str === $bp2;

		return $ret;
	}

	/**
	 * Returns a text representation of bolt pattern(s)
	 */
	public function get_bolt_pattern_text( $bp1 = null, $bp2 = null, Vehicle $vehicle = null, $filter_by_vehicle = false ) {

		$bp1 = $bp1 === null ? $this->get( 'bolt_pattern_1' ) : $bp1;
		$bp2 = $bp2 === null ? $this->get( 'bolt_pattern_2' ) : $bp2;

		$vehicle_complete = $vehicle && $vehicle instanceof Vehicle && $vehicle->is_complete();

		// show only 1 bolt pattern on rims with dual bolt pattern.. if vehicle matches one of them.
		// I think this is just silly. if a product has 2 bolt patterns now the user will have no clue about this
		// now we have other issues: cart text is saved to order items when a user is checked out
		// the same text is sent to suppliers. now suppliers will see only one bolt pattern and may say the part number has incorrect
		// data. or we put both bolt patterns, and the user goes to the cart and sees one, then gets an order confirmation and
		// sees 2.
		if ( $filter_by_vehicle && $vehicle_complete && $bp1 && $bp2 ) {
			if ( $this->has_bolt_pattern( $vehicle->fitment_object->bolt_pattern ) ) {

				// log before changing these vars
				queue_dev_alert( 'Bolt_Pattern_Filtered', [
					'pn' => gp_test_input( $this->get_description() . '-' . $this->get( 'part_number' ) ),
					'bp1' => $bp1,
					'bp2' => $bp2,
					'result' => $vehicle->fitment_object->bolt_pattern ]
				);

				$bp1 = $vehicle->fitment_object->bolt_pattern;
				$bp2 = '';
			}
		}

		$arr = array();
		if ( $bp1 ) {
			$arr[] = $bp1;
		}
		if ( $bp2 ) {
			$arr[] = $bp2;
		}

		$str = implode_comma( $arr );

		return gp_test_input( $str );
	}

	/**
	 *
	 */
	public function get_type_slug() {
		// steel or alloy
		$type = $this->get( 'type' );
		//		$type = trim( $type );
		//		$type = strtolower( $type );
		$type = make_slug( $type );

		return $type;
	}

	/**
	 * This is in the wrong spot. Rims dont have spec. It seems to be not in use,
	 * however, I am still not removing it at this time. It could be called dynamically.
	 */
	public function get_spec() {
		$load_index   = $this->get( 'load_index' );
		$speed_rating = $this->get( 'speed_rating' );

		return $load_index . ' ' . $speed_rating . '';
	}

	/**
	 * You'll have to add to this array when adding to a package, or when a vehicle is
	 * present but want to create a new package upon adding to cart.
	 */
	public function add_to_cart_default_args( $vehicle = null, $package = null ) {

		$ajax_action = 'add_to_cart';

		$args = array(
			'url' => AJAX_URL,
			'ajax_action' => $ajax_action,
			'nonce' => get_nonce_value( $ajax_action ),
			'part_number' => $this->get( 'part_number' ),
			'type' => 'rim',
		);

		if ( $vehicle instanceof Vehicle ) {
			if ( $vehicle->is_complete() ) {
				$args[ 'make' ]    = $vehicle->make;
				$args[ 'model' ]   = $vehicle->model;
				$args[ 'year' ]    = $vehicle->year;
				$args[ 'trim' ]    = $vehicle->trim;
				$args[ 'fitment' ] = $vehicle->fitment_slug;
			}
		}

		// the add the cart handler should verify package exists and if it does not should
		// try its best to handle it correctly. Therefore, we don't check whether the package exists here first.
		if ( $package ) {
			$args[ 'pkg' ] = $package;
		}

		return $args;
	}

	/**
	 * Get a partial instance which holds brand, model, and finish objects inside of one object,
	 * and also gives us access to some functions which require more than one of these at a time
	 * but are found within the DB_Rim class. This is of course, not used super often, but
	 * does serve a few purposes.
	 *
	 * Another note: this *may* be similar in approach to simply finding one product at
	 * random that matches the given attributes, but for the specific case of product reviews,
	 * they are linked to brands/models/finishes, and therefore still need to be valid if no
	 * matching products are found, but the brands/models/finishes still exist.
	 *
	 * @param $brand_slug
	 * @param $model_slug
	 * @param $c1
	 * @param $c2
	 * @param $ff
	 */
	public static function get_partial_product( $brand_slug, $model_slug, $c1, $c2, $ff ) {

		$brand = DB_Rim_Brand::get_instance_via_slug( $brand_slug );

		if ( $brand ) {

			$model = DB_Rim_Model::get_instance_by_slug_brand( $model_slug, $brand->get_primary_key_value() );

			if ( $model ) {

				$finish = DB_Rim_Finish::get_instance_via_finishes( $model->get_primary_key_value(), $c1, $c2, $ff );

				if ( $finish ) {

					return new static( array(
						'brand_id' => $brand->get_primary_key_value(),
						'brand_slug' => $brand->get( 'slug' ),
						'model_id' => $model->get_primary_key_value(),
						'model_slug' => $model->get( 'slug' ),
						'finish_id' => $finish->get_primary_key_value(),
						'color_1' => $finish->get( 'color_1' ),
						'color_2' => $finish->get( 'color_2' ),
						'finish' => $finish->get( 'finish' ),
					), array(
						'brand' => $brand,
						'model' => $model,
						'finish' => $finish,
					));
				}
			}
		}

		return null;

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
			case 'supplier':
				return get_admin_single_edit_anchor_tag( DB_suppliers, $value );
				break;
			case 'stock_update_id_ca':
				return get_admin_single_edit_anchor_tag( DB_stock_updates, $value );
				break;
			case 'stock_update_id_us':
				return get_admin_single_edit_anchor_tag( DB_stock_updates, $value );
				break;
			case 'model_id':
				return get_admin_single_edit_anchor_tag( DB_rim_models, $value );
				break;
			case 'brand_id':
				return get_admin_single_edit_anchor_tag( DB_rim_brands, $value );
				break;
			case 'finish_id':
				return get_admin_single_edit_anchor_tag( DB_rim_finishes, $value );
				break;
		}

		// returning null is not the same as false or "" here
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
}

/**
 * @param string $primary_color
 * @param string $secondary_color
 * @param string $finish
 */
function get_rim_finish_string( $primary_color = '', $secondary_color = '', $finish = '' ) {

	$primary_color   = gp_test_input( $primary_color );
	$secondary_color = gp_test_input( $secondary_color );
	$finish          = gp_test_input( $finish );

	$arr = [ $primary_color, $secondary_color, $finish ];
	$arr = array_filter( $arr, 'trim' );
	$arr = array_filter( $arr );
	$ret = implode( ', ', $arr );
	$ret = trim( $ret );

	return $ret;
}
