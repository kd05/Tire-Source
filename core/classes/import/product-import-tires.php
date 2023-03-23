<?php

/**
 * Class Product_Import_Tires
 */
Class Product_Import_Tires extends Product_Import {

	public $col_index_to_name = array(
		'part_number' => 'PART NUMBER',
		'country' => 'COUNTRY',
		'type' => 'TYPE',
		'class' => 'CLASS',
		'category' => 'CATEGORY',
		'supplier' => 'SUPPLIER',
		'brand' => 'BRAND',
		'model' => 'MODEL',
		'size' => 'SIZE',
		'load_index' => 'LOAD INDEX',
		'speed_rating' => 'SPEED RATING',
		'utqg' => 'UTQG',
		'run_flat' => 'RUN FLAT',
		// 'description' => 'DESCRIPTION',
		'cost_ca' => 'CAD COST',
		'msrp_ca' => 'CAD MSRP',
		'price_ca' => 'CAD SALE PRICE',
		'cost_us' => 'US COST',
		'msrp_us' => 'US MSRP',
		'price_us' => 'US SALE PRICE',
	);

	/**
	 * Product_Import_Tires constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {

		parent::__construct( $args );

		$this->table = DB_tires;

		// important to do this first, and not change only $this->required_cols.
        // this is because $this->require_cols is only used for displaying the req cols,
        // but $this->col_index_to_name is used when parsing the csv.
        // lastly, note that when displaying req cols, $this->locale is null, so we
        // say that all cols are req, because the user has not chosen their locale yet (and yes,
        // its technically a lie saying certain cols are req when they might not be).
        if ( $this->locale === 'US' ) {
            unset ( $this->col_index_to_name['cost_ca'] );
            unset ( $this->col_index_to_name['msrp_ca'] );
            unset ( $this->col_index_to_name['price_ca'] );
        } else if ( $this->locale === 'CA' ) {
            unset ( $this->col_index_to_name['cost_us'] );
            unset ( $this->col_index_to_name['msrp_us'] );
            unset ( $this->col_index_to_name['price_us'] );
        }

        // used for display only
		$this->required_cols = array_keys( $this->col_index_to_name );
	}

	/**
	 * By now, a row should look like: array( 'part_number' => '...', 'next_col' => '...', ... );
	 * Note: remember to check whether you are inserting, updating, or both.
	 *
	 * @param $row
	 */
	public function handle_row( $row, $row_id ) {

		// pass some data globally to other functions, mainly add_row_message()
		$this->current_row = $row;

		$db = get_database_instance();

		$part_number = gp_if_set( $row, 'part_number' );
		$part_number = static::sanitize_part_number( $part_number );

		$type  = gp_if_set( $row, 'type' );
		$type  = make_slug( $type );
		$class = gp_if_set( $row, 'class' );

		// fix a typo that was/is in some csv rows
		if ( strtolower( $class ) === 'passanger' ) {
			$class = 'Passenger';
		}

		// $description  = gp_if_set( $row, 'description' );
		$category = gp_if_set( $row, 'category' );

		$supplier      = gp_if_set( $row, 'supplier' );
		$supplier_name = $supplier;
		$supplier_slug = make_slug( $supplier );

        if ( ! $supplier_slug ) {
            $this->add_row_message( $row_id, 'No supplier was specified - not inserting this product.' );

            return;
        }

		$brand        = gp_if_set( $row, 'brand' );
		$model        = gp_if_set( $row, 'model' );
		$size         = gp_if_set( $row, 'size' );
		$run_flat     = gp_if_set( $row, 'run_flat' );
		$load_index   = gp_if_set( $row, 'load_index' );
		$speed_rating = gp_if_set( $row, 'speed_rating' );
		$UTQG         = gp_if_set( $row, 'utqg' ); // Q G

		$msrp_ca = gp_if_set( $row, 'msrp_ca' );
		$cost_ca = gp_if_set( $row, 'cost_ca' );
		$msrp_us = gp_if_set( $row, 'msrp_us' );
		$cost_us = gp_if_set( $row, 'cost_us' );

		$price_ca = gp_if_set( $row, 'price_ca' );
		$price_us = gp_if_set( $row, 'price_us' );
		$image    = trim( gp_if_set( $row, 'image' ) );

		// dont allow "&" here
		// also note that we have to remove them before gp_test_input() which might be done later..
		$brand = ampersand_to_plus( $brand );
		$model = ampersand_to_plus( $model );

		$price_ca = format_price_dollars( $price_ca );
		$price_us = format_price_dollars( $price_us );

		$found = ( DB_Tire::create_instance_via_part_number( $part_number ) );

		// Skip this row?
		if ( $this->method === 'insert_only' && $found ) {
			$this->add_row_message( $row_id, 'Product already exists, not inserting.' );

			return;
		}

		// Skip this row?
		if ( $this->method === 'update_only' && ! $found ) {
			$this->add_row_message( $row_id, 'Product did not already exist, not inserting.' );

			return;
		}


		// verify type
		// note: part of the reason I'm doing this is because i'm sometimes seeing the header columns repeated every X number of rows
		// so this is a quick and easy way to to ensure that we skip those rows.
		if ( ! in_array( $type, array( 'summer', 'winter', 'all-season', 'all-weather' ) ) ) {
			$this->add_row_message( $row_id, 'Type is not valid: ' . $type . ' - not inserting this product.' );

			return;
		}

		// ********** Parse Tire Size String ************

		$_size = new Parsed_Tire_Size_String( $size );

		if ( $_size->error ) {
			$this->add_row_message( $row_id, 'Size string could not be parsed (' . $size . ') (' . $_size->error_type . ') - not inserting this product.' );

			return;
		}

		// our function should always return an error if one of these are empty, so this safety measure is fairly redundant.
		if ( ! $_size->width || ! $_size->profile || ! $_size->diameter ) {
			$this->add_row_message( $row_id, 'Tire size error (' . $size . ') - not inserting this product' );

			return;
		}


		// ********** Parse: Speed Rating, Load Index **************
		$SR = new Parsed_Tire_Speed_Rating( $speed_rating );
		$LI = new Parsed_Tire_Load_Index( $load_index );

		if ( ( $LI->load_index_1 && ! gp_is_integer( $LI->load_index_1 ) ) || $LI->load_index_2 && ! gp_is_integer( $LI->load_index_2 ) ) {
			$this->add_row_message( $row_id, 'load index is not valid: ' . $load_index . ' - not inserting this product.', false );

			return;
		}

		// *************** REGISTER BRAND ********************

		$brand_slug = make_slug( $brand );
		$model_slug = make_slug( $model );

		$brand_object = DB_Tire_Brand::get_instance_via_slug( $brand_slug );

		if ( $brand_object ) {
			$brand_id = $brand_object->get_primary_key_value();
		} else {
			$brand_id = register_tire_brand( $brand_slug, array( 'name' => $brand ) );

			// show admin link to new brand
			$edit_brand = get_admin_single_edit_link( DB_tire_brands, $brand_id );
			$this->add_row_message( $row_id, 'A new ' . html_link_new_tab( $edit_brand, 'brand' ) . ' was registered: ' . implode_comma( [ $brand ] ) . '.' );
		}

		if ( ! $brand_id ) {
			$this->add_row_message( $row_id, 'Brand not found. New one could not be inserted. Cannot insert product.', false );

			return;
		}

		// ********** REGISTER MODEL **************
		$model_object = DB_Tire_Model::get_instance_by_slug_brand( $model_slug, $brand_id );

		if ( $model_object ) {

			//  update image, but if image is empty.. don't erase the previous value.
			// also, we specifically don't update the names here. tire models once registered
			// are edited via the admin section.
            // not with new tire model images thing. may need to re-visit this and find
            // a different way.
//			if ( $image ) {
//				$model_object->update_database_and_re_sync( array(
//					'tire_model_image' => $image,
//				) );
//			}

			$model_id = $model_object->get_primary_key_value();

		} else {

            // insert a new model.
            // the image will have to be localized via admin UI later.
            $model_id = register_tire_model( $model_slug, $brand_id, array(
                'tire_model_name' => $model,
                'tire_model_type' => make_slug( $type ),
                'tire_model_class' => make_slug( $class ),
                'tire_model_category' => make_slug( $category ),
                'tire_model_run_flat' => make_slug( $run_flat ),
                'tire_model_image_new' => $image,
            ) );

			// show admin link to new model
			$edit_model = get_admin_single_edit_link( DB_tire_models, $model_id );
			$this->add_row_message( $row_id, 'A new ' . html_link_new_tab( $edit_model, 'model' ) . ' was registered: ' . implode_comma( [
					$brand,
					$model
				] ) . '.' );
		}

		if ( ! $model_id ) {
			$this->add_row_message( $row_id, 'Model not found. New one could not be inserted. Cannot insert product', false );

			return;
		} else {
			if ( ! in_array( $model_id, $_SESSION[ $this->session_key ][ 'tire_model_ids' ] ) ) {
				$_SESSION[ $this->session_key ][ 'tire_model_ids' ][] = $model_id;
			}
		}

		// **** Register Supplier ****
		$this->register_supplier( $supplier_slug, $supplier_name, $row_id );

		// Send data to array to pass into DB functions. Array keys here are database column names.
		$data = array(
			'part_number' => $part_number,
			'supplier' => $supplier_slug,
			// already clean
			'brand_id' => $brand_id,
			// already clean
			'brand_slug' => $brand_slug,
			// already clean
			'model_id' => $model_id,
			// already clean
			'model_slug' => $model_slug,
			// lt maybe
			'tire_sizing_system' => gp_test_input( $_size->tire_sizing_system ),
			// this column does not belong anymore
			// 'description' => gp_test_input( $description ),
			'size' => gp_test_input( $size ),
			// raw size string in csv, we may use this in the code or assemble our own size strings
			'width' => $_size->width,
			'profile' => $_size->profile,
			'diameter' => $_size->diameter,
			'load_index' => $LI->load_index_1,
			'load_index_2' => $LI->load_index_2,
			'speed_rating' => strtoupper( $SR->speed_rating ),
			'extra_load' => strtoupper( $SR->extra_load ),
			'is_zr' => $_size->is_zr ? 1 : 0,
			'utqg' => gp_test_input( $UTQG ),
            'import_name' => $this->import_name,
            'import_date' => $this->import_date,
//			'cost_ca' => format_price_dollars( $cost_ca ),
//			'msrp_ca' => format_price_dollars( $msrp_ca ),
//			'cost_us' => format_price_dollars( $cost_us ),
//			'msrp_us' => format_price_dollars( $msrp_us ),
//			'price_ca' => $price_ca > 0 ? $price_ca : '',
//			'price_us' => $price_us > 0 ? $price_us : '',
//			'sold_in_ca' => $price_ca > 0 ? 1 : '',
//			'sold_in_us' => $price_us > 0 ? 1 : '',
		);

        if ( $this->locale === null || $this->locale === 'CA' ){
            $data['cost_ca'] = format_price_dollars( $cost_ca );
            $data['msrp_ca'] = format_price_dollars( $msrp_ca );
            $data['price_ca'] = $price_ca > 0 ? $price_ca : '';
            $data['sold_in_ca'] = $price_ca > 0 ? 1 : 0;
        } else {
            // we do not set any values here. if the product already
            // exists, it's important that we leave them as they are. If we're
            // inserting the product, the sql default values will be what we need.
        }

        if ( $this->locale === null || $this->locale === 'US' ) {
            $data['cost_us'] = format_price_dollars( $cost_us );
            $data['msrp_us'] = format_price_dollars( $msrp_us );
            $data['price_us'] = $price_us > 0 ? $price_us : '';
            $data['sold_in_us'] = $price_us > 0 ? 1 : 0;
        } else {
            // see above
        }


		$do_insert = false;
		$do_update = false;

		// only updating and product found
		if ( $this->method === 'update_only' && $found ) {
			$do_update = true;
		}

		// only inserting, and product not found
		if ( $this->method === 'insert_only' && ! $found ) {
			$do_insert = true;
		}

		if ( $this->method === 'update_insert' ) {
			if ( $found ) {
				$do_update = true;
				$do_insert = false;
			} else {
				$do_insert = true;
				$do_update = false;
			}
		}

		if ( $do_update ) {

			$update = $db->update( $this->table, $data, $where = array( 'part_number' => $part_number ), array(), array() );

			if ( $update ) {
				$this->update_count ++;
			} else {
				$this->add_row_message( $row_id, 'Row could not be updated.' );
			}

			return;

		} else if ( $do_insert ) {

			$insert = $db->insert( $this->table, $data, array() );

			if ( $insert ) {
				$this->insert_count ++;
			} else {
				$this->add_row_message( $row_id, 'Row could not be inserted.' );
			}

			return;

		} else {

			// shouldn't happen based on our logic
			$this->add_row_message( $row_id, 'Row not inserted or updated.' );
		}

	}
}

Class Parsed_Tire_Speed_Rating {

	public $speed_rating; // ie. "V"
	public $extra_load; // "XL" or ""

	public function __construct( $str ) {

		$str = trim( $str );
		$str = strtoupper( $str );

		if ( strpos( $str, 'XL' ) !== false || strpos( $str, 'xl' ) !== false ) {
			$str              = str_replace( 'XL', '', $str );
			$str              = str_replace( 'xl', '', $str );
			$this->extra_load = 'XL';
		} else {
			$this->extra_load = '';
		}

		$str                = trim( $str );
		$this->speed_rating = $str;
	}
}

Class Parsed_Tire_Load_Index {

	public $load_index_1;
	public $load_index_2; // only sometimes

	/**
	 * $str could be "115" or maybe "115/112"
	 *
	 * Parsed_Tire_Load_Index constructor.
	 *
	 * @param $str
	 */
	public function __construct( $str ) {

		$str = trim( $str );

		if ( strpos( $str, '/' ) !== false ) {
			$ll = explode( '/', $str );

			$load_1 = gp_if_set( $ll, 0 );
			$load_2 = gp_if_set( $ll, 1 );

			$this->load_index_1 = gp_test_input( $load_1 );
			$this->load_index_2 = gp_test_input( $load_2 );

		} else {

			// p.s. most load index strings end up here
			$this->load_index_1 = gp_test_input( $str );
			$this->load_index_2 = '';
		}

	}
}