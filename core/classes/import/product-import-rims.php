<?php

/**
 * Class Product_Import_Rims
 */
Class Product_Import_Rims extends Product_Import {

	// what we call it => the values in the first row of the CSV
	public $col_index_to_name = array(
		'part_number' => 'PART NUMBER',
		'supplier' => 'SUPPLIER',
		'type' => 'TYPE',
		'style' => 'STYLE',
		'brand' => 'BRAND',
		'model' => 'MODEL',
		'color_1' => 'FINISH',
		'color_2' => 'SECONDARY FINISH',
		'finish' => '3RD FINISH',
		'size' => 'SIZE',
		'bolt_pattern' => 'BOLT PATTERN',
		'seat_type' => 'SEAT TYPE',
		'offset' => 'OFFSET',
		'center_bore' => 'CENTER BORE',
		'image' => 'IMAGE URL',
		'cost_ca' => 'CAD COST',
		'msrp_ca' => 'CAD MSRP',
		'price_ca' => 'CAD SALE PRICE',
		'cost_us' => 'US COST',
		'msrp_us' => 'US MSRP',
		'price_us' => 'US SALE PRICE',
	);

	/**
	 * Product_Import_Rims constructor.
	 *
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {

		parent::__construct( $args );

		$this->table = DB_rims;

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
	 *
	 */
	public function get_all_products() {

		$db = get_database_instance();
		$q  = '';
		$q  .= 'SELECT part_number ';
		$q  .= 'FROM ' . $this->table . ' ';
		$q  .= 'GROUP BY part_number ';
		$q  .= ';';
		$r  = $db->pdo->query( $q )->fetchAll( PDO::FETCH_COLUMN );

		return $r;
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

		// localize variables so we can modify them
		$part_number = gp_if_set( $row, 'part_number' );
		$part_number = static::sanitize_part_number( $part_number );

		$supplier    = gp_if_set( $row, 'supplier' );
		$supplier_name = $supplier;
		$supplier_slug = make_slug( $supplier );

		if ( ! $supplier_slug ) {
            $this->add_row_message( $row_id, 'No supplier was specified - not inserting this product.' );

            return;
        }

		$type        = gp_if_set( $row, 'type' );
		$style       = gp_if_set( $row, 'style' );
		$brand       = gp_if_set( $row, 'brand' );
		$model       = gp_if_set( $row, 'model' );
		$color_1     = gp_if_set( $row, 'color_1' );
		$color_2     = gp_if_set( $row, 'color_2' );
		$finish      = gp_if_set( $row, 'finish' );
		$size        = gp_if_set( $row, 'size' );

        // dont allow "&" here
        // also note that we have to remove them before gp_test_input() which might be done later..
        $brand = ampersand_to_plus( $brand );
        $model = ampersand_to_plus( $model );
        $color_1 = ampersand_to_plus( $color_1 );
        $color_2 = ampersand_to_plus( $color_2 );
        $finish = ampersand_to_plus( $finish );

		$RS = new Parsed_Rim_Size( $size );

		if ( $RS->error ) {
			$this->add_row_message( $row_id, 'Error parsing rim size (' . $size . ') (' . $RS->error_type . ') - not inserting this product.' );

			return;
		}

		$bolt_pattern = gp_if_set( $row, 'bolt_pattern' );
		$seat_type    = gp_if_set( $row, 'seat_type' );
		$offset       = gp_if_set( $row, 'offset' );
		$center_bore  = gp_if_set( $row, 'center_bore' );
		$msrp_ca = gp_if_set( $row, 'msrp_ca' );
		$cost_ca = gp_if_set( $row, 'cost_ca' );
		$msrp_us = gp_if_set( $row, 'msrp_us' );
		$cost_us = gp_if_set( $row, 'cost_us' );
		$price_ca = gp_if_set( $row, 'price_ca' );
		$price_us = gp_if_set( $row, 'price_us' );
		$price_ca = format_price_dollars( $price_ca );
		$price_us = format_price_dollars( $price_us );

		$image = trim( gp_if_set( $row, 'image' ) );

		$bolt_pattern_arr = parse_possible_dual_bolt_pattern( $bolt_pattern );
		$bolt_pattern_1 = gp_if_set( $bolt_pattern_arr, 0 );
		$bolt_pattern_2 = gp_if_set( $bolt_pattern_arr, 1 );

		if ( ! $bolt_pattern_1 ) {
			$this->add_row_message( $row_id, 'Could not identify a primary bolt pattern (' . $bolt_pattern . ') - not inserting this product' );

			return;
		}

		// Does product exist already?
//		$q  = '';
//		$q  .= 'SELECT part_number ';
//		$q  .= 'FROM ' . $db->rims;
//		$q  .= ' ';
//		$q  .= 'WHERE part_number = ? ';
//		$q  .= ';';
//		$st = $db->pdo->prepare( $q );
//		$st->bindValue( 1, $part_number );
//		$st->execute();
//		$found = $st->fetch();

		$found = ( DB_Rim::create_instance_via_part_number( $part_number ) );

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

		// ********** REGISTER BRAND **************
		$brand_slug = make_slug( $brand );

		$brand_object = DB_Rim_Brand::get_instance_via_slug( $brand_slug );

		if ( $brand_object ) {

			$brand_id = $brand_object->get_primary_key_value();

		} else {
			$brand_id = register_rim_brand( $brand_slug, array( 'name' => $brand ) );

			$edit_brand = get_admin_single_edit_link( DB_rim_brands, $brand_id );
			$this->add_row_message( $row_id, 'A new ' . html_link_new_tab( $edit_brand, 'brand' ) . ' was registered: ' . implode_comma( [ $brand ] ) . '.' );
		}

		if ( ! $brand_id ) {
			$this->add_row_message( $row_id, 'Brand not found. Could not create a new one. Therefore cannot insert product', false );

			return;
		}

		// ********** REGISTER MODEL **************
		$model_slug = make_slug( $model );

		$model_object = DB_Rim_Model::get_instance_by_slug_brand( $model_slug, $brand_id );

		if ( $model_object ) {
			$model_id = $model_object->get_primary_key_value();
		} else {
			$model_id = register_rim_model( $model_slug, $brand_id, array( 'name' => $model ) );

			$edit_model = get_admin_single_edit_link( DB_rim_models, $model_id );
			$this->add_row_message( $row_id, 'A new ' . html_link_new_tab( $edit_model, 'model' ) . ' was registered: ' . implode_comma( [ $brand, $model ] ) . '.' );
		}

		if ( ! $model_id ) {
			$this->add_row_message( $row_id, 'Model not found. Could not create a new one. Therefore cannot insert product', false );

			return;
		}

		// ********** REGISTER FINISH **************
		$color_1_slug = make_slug( $color_1 );
		$color_2_slug = make_slug( $color_2 );
		$finish_slug = make_slug( $finish );

		$color_1_name = gp_test_input( $color_1 );
		$color_2_name = gp_test_input( $color_2 );
		$finish_name = gp_test_input( $finish );

		$finish_object = DB_Rim_Finish::get_instance_via_finishes( $model_id, $color_1_slug, $color_2_slug, $finish_slug );

		// Update Finish
		if ( $finish_object ) {

			$rim_finish_id = $finish_object->get_primary_key_value();

            // update the image with the last non-empty value encountered
            // in an import file. (the admin UI will have to be used to
            // download the image afterwards).
            if ( $image ) {
                $finish_object->update_database_and_re_sync( array(
                    'image_source_new' => $image,
                ) );
            }

		} else {
			// Insert Finish
			$rim_finish_id = register_rim_finish( $model_id, $color_1_slug, $color_2_slug, $finish_slug, $color_1_name, $color_2_name, $finish_name, '', '', $image );

			$edit_finish = get_admin_single_edit_link( DB_rim_finishes, $rim_finish_id );
			$this->add_row_message( $row_id, 'A new rim ' . html_link_new_tab( $edit_finish, 'finish' ) . ' was registered: ' . implode_comma( [ $brand_slug, $model_slug, $color_1_slug, $color_2_slug, $finish_slug ] ) . '.' );
		}

		// unlikely error..
		if ( ! $rim_finish_id ) {
			$this->add_row_message( $row_id, 'Finish not found. Could not create a new one. Therefore cannot insert product', false );

			return;
		}

		// **** Register Supplier ****
		$this->register_supplier( $supplier_slug, $supplier_name, $row_id );

        // database update array. keys are columns.
        $data = array(
			'part_number' => gp_test_input( $part_number ),
			'supplier' => $supplier_slug,
			'type' => make_slug( $type ),
			'style' => make_slug( $style ), // seems to be "replica" or ""
			// already cleaned
			'brand_id' => $brand_id,
			// already cleaned
			'brand_slug' => $brand_slug,
			// already cleaned
			'model_id' => $model_id,
			// already cleaned
			'model_slug' => $model_slug,
			'finish_id' => $rim_finish_id,
			'color_1' => $color_1_slug,
			'color_2' => $color_2_slug,
			'finish' => $finish_slug,
			'size' => gp_test_input( $size ),
			'width' => $RS->width,
			'diameter' => $RS->diameter,
			'bolt_pattern_1' => gp_test_input( $bolt_pattern_1 ),
			'bolt_pattern_2' => gp_test_input( $bolt_pattern_2 ),
			'seat_type' => make_slug( $seat_type ),
			'offset' => gp_test_input( $offset ),
			'center_bore' => gp_test_input( $center_bore ),
            'import_name' => $this->import_name,
            'import_date' => $this->import_date,
//			'cost_ca' => format_price_dollars( $cost_ca ),
//            'cost_us' => format_price_dollars( $cost_us ),
//            'msrp_ca' => format_price_dollars( $msrp_ca ),
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