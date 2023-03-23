<?php


class Product_Sync_Update{

    /**
     * We might call this once and pass it to each function so that dates
     * are identical among items in the same update.
     *
     * @return false|string
     */
    static function get_database_formatted_date_now(){
        return date( get_database_date_format() );
    }

    /**
     * @param $sql
     * @param $params
     * @return mixed
     */
    static function execute( $sql, $params = [] ) {
        $db = get_database_instance();
        return $db->execute( $sql, $params );
    }

    /**
     * @param $callable
     * @param string $desc
     * @return string
     */
    static function with_transaction( $callable, $desc = '') {
        try{
            self::execute("START TRANSACTION;" );
            $callable();
            self::execute("COMMIT;" );
        } catch( Exception $e ) {

            log_data( [
                'desc' => $desc,
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'product-sync-update-exception' );

            self::execute("ROLLBACK;" );

            return $e->getMessage();
        }
    }

    /**
     * @param $response
     * @param $type
     * @param $desc
     */
    static function log_failed_updates_or_inserts( $response, $type, $desc ) {

        $count_all = count( $response );
        $errors = array_filter( $response );

        if ( count( $errors ) > 0 ) {

            $filename = implode( '-', [ $desc, $type, uniqid() ] ) . '.log';

            @mkdir( LOG_DIR . '/product-sync-fail', 0755, true );

            $result = [
                'count_all' => $count_all,
                'count_errors' => count( $errors ),
                'errors' => $errors,
            ];

            file_put_contents( LOG_DIR . '/product-sync-fail/' . $filename, json_encode( $result, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_IGNORE ) );
        }
    }

    /**
     * @param $table
     * @param $updates
     * @param false $log_failed
     * @param string $log_desc
     * @return array - array of errors or empty strings on success
     */
    static function do_updates( $table, $updates, $log_failed = false, $log_desc = '' ) {
        $db = get_database_instance();

        $ret = [];

        self::with_transaction( function() use( $table, $updates, $db, &$ret ) {
            foreach ( $updates as $key => $update ) {

                list( $data, $where, $data_format, $where_format ) = $update;

                if ( isset( $data['__valid'] ) ) {
                    if ( ! $data['__valid'] ) {
                        continue;
                    } else {
                        unset( $data['__valid'] );
                    }
                }

                try{
                    $success = $db->update( $table, $data, $where, $data_format, $where_format );
                    $ret[$key] = $success ? '' : 'Update did not return true.';
                } catch ( Exception $e ) {
                    $ret[$key] = "Update($table): " . $e->getMessage();
                }
            }
        } );

        if ( $log_failed ) {
            $log_desc = $log_desc ? $table . '-' . $log_desc : $table;
            self::log_failed_updates_or_inserts( $ret, 'update', $log_desc );
        }

        return $ret;
    }

    /**
     * @param $table
     * @param $inserts
     * @param bool $log_failed
     * @param string $log_desc
     * @return array - array of errors or empty strings on success
     */
    static function do_inserts( $table, $inserts, $log_failed = true, $log_desc = '' ) {
        $db = get_database_instance();

        $ret = [];

        self::with_transaction( function() use( $table, $inserts, $db, &$ret ) {
            foreach ( $inserts as $insert ) {

                list( $data, $format ) = $insert;

                // a special valid key lets us assemble partial updates/inserts
                // and view/debug them. Ie. we can print the tire insert arrays
                // without the requirement of the brands existing in the database.
                if ( isset( $data['__valid'] ) ) {
                    if ( ! $data['__valid'] ) {
                        continue;
                    } else {
                        unset( $data['__valid'] );
                    }
                }

                try{
                    $inserted = $db->insert( $table, $data, $format );
                    $ret[] = $inserted ? '' : 'Insert returned false';
                } catch ( Exception $e ) {
                    $ret[] = "Insert($table): " . $e->getMessage();
                }
            }
        } );

        if ( $log_failed ) {
            $log_desc = $log_desc ? $table . '-' . $log_desc : $table;
            self::log_failed_updates_or_inserts( $ret, 'insert', $log_desc );
        }

        return $ret;
    }

    /**
     * @param $ents
     * @param $date
     * @return array
     */
    static function tire_brand_inserts( $ents, $date ) {
        return Product_Sync::reduce( $ents, function( $ent ) use( $date ) {

            $data = [
                'tire_brand_name' => gp_test_input( $ent['name'] ),
                'tire_brand_slug' => gp_test_input( $ent['slug'] ),
                'tire_brand_inserted_at' => $date
            ];

            $format = [];

            return [ $data, $format ];
        }, true );
    }

    /**
     * @param $ents
     * @param $ex_brands
     * @param $date
     * @return array
     */
    static function tire_model_inserts( $ents, $ex_brands, $date ) {
        return Product_Sync::reduce( $ents, function( $ent ) use( $ex_brands, $date ){

            $brand = @$ex_brands[$ent['brand_slug']];

            $data = [
                '__valid' => (bool) $brand,
                'tire_brand_id' => (int) @$brand['tire_brand_id'],
                'tire_model_name' => gp_test_input( $ent['name'] ),
                'tire_model_slug' => gp_test_input( $ent['slug'] ),
                'tire_model_type' => gp_test_input( $ent['type'] ),
                'tire_model_class' => gp_test_input( $ent['class'] ),
                'tire_model_category' => gp_test_input( $ent['category'] ),
                'tire_model_image_new' => gp_test_input_alt( $ent['image'], true ),
                'tire_model_inserted_at' => $date,
            ];

            $format = [
                'tire_brand_id' => '%d'
            ];

            return [ $data, $format ];

        }, true );
    }

    /**
     * Tire models don't "change" brand, if the brand name changes, it will be a new
     * tire model with the same model name but a different brand.
     *
     * @param $ents
     * @return array
     */
    static function tire_model_updates( $ents ) {
        return Product_Sync::reduce( $ents, function( $ent ) {
            $ex = $ent['__ex'];
            $diff = $ent['__diff'];

            $data = [
                'tire_model_image_new' => isset( $diff['image'] ) ? $ent['image'] : $ex['tire_model_image_new'],
            ];

            $where = [
                'tire_model_id' => $ex['tire_model_id'],
            ];

            $data_format = [];

            $where_format = [
                'tire_model_id' => '%d'
            ];

            return [ $data, $where, $data_format, $where_format ];
        }, true );
    }

    /**
     * @param $ents
     * @return array
     */
    static function rim_brand_inserts( $ents, $date ) {
        return Product_Sync::reduce( $ents, function( $ent ) use( $date ) {

            $data = [
                'rim_brand_name' => gp_test_input( $ent['name'] ),
                'rim_brand_slug' => gp_test_input( $ent['slug'] ),
                'rim_brand_inserted_at' => $date,
            ];

            $format = [];

            return [ $data, $format ];

        }, true );
    }

    /**
     * @param $ents
     * @param $ex_brands
     * @param $date
     * @return array
     */
    static function rim_model_inserts( $ents, $ex_brands, $date ) {
        return Product_Sync::reduce( $ents, function( $ent ) use( $ex_brands, $date ) {

            $brand = @$ex_brands[$ent['brand_slug']];

            $data = [
                '__valid' => (bool) $brand,
                'rim_brand_id' => @$brand['rim_brand_id'],
                'rim_model_name' => gp_test_input( $ent['name'] ),
                'rim_model_slug' => gp_test_input( $ent['slug'] ),
                'rim_model_inserted_at' => $date,
            ];

            $format = [
                'rim_brand_id' => '%d',
            ];

            return [ $data, $format ];

        }, true );
    }

    /**
     * @param $ents
     * @param $ex_models
     * @param $date
     * @return array
     */
    static function rim_finish_inserts( $ents, $ex_models, $date ) {

        return Product_Sync::reduce( $ents, function( $ent ) use( $ex_models, $date ) {

            $model_key = implode( '##', [ $ent['brand_slug'], $ent['model_slug'] ] );
            $model = @$ex_models[$model_key];

            $data = [
                '__valid' => (bool) $model,
                'model_id' => @$model['rim_model_id'],
                'color_1' => $ent['color_1_slug'],
                'color_2' => $ent['color_2_slug'],
                'finish' => $ent['finish_slug'],
                'color_1_name' => $ent['color_1_name'],
                'color_2_name' => $ent['color_2_name'],
                'finish_name' => $ent['finish_name'],
                'image_source_new' => $ent['image'],
                'rim_finish_inserted_at' => $date
            ];

            $format = [
                'model_id' => '%d',
            ];

            return [ $data, $format ];

        }, true );
    }

    /**
     * Tire models don't "change" brand, if the brand name changes, it will be a new
     * tire model with the same model name but a different brand.
     *
     * @param $ents
     * @return array
     */
    static function rim_finish_updates( $ents ) {
        return Product_Sync::reduce( $ents, function( $ent ) {
            $ex = $ent['__ex'];
            $diff = $ent['__diff'];

            $data = [
                'image_source_new' => isset( $diff['image'] ) ? $ent['image'] : $ex['image_source_new'],
            ];

            $where = [
                'rim_finish_id' => $ex['rim_finish_id'],
            ];

            $data_format = [];

            $where_format = [
                'rim_finish_id' => '%d',
            ];

            return [ $data, $where, $data_format, $where_format ];

        }, true );
    }

    /**
     * @param $ents
     * @param $ex_brands
     * @param $ex_models
     * @param $req_id
     * @param $date
     * @return array
     */
    static function tire_inserts( $ents, $ex_brands, $ex_models, $req_id, $date ) {

        $ret = [];

        $format = Product_Sync_Update::get_database_col_formats('tires');

        foreach ( $ents as $ent ) {

            $model_key = implode( '##', [ $ent['brand_slug'], $ent['model_slug'] ] );

            $is_canada = $ent['locale'] === 'CA';
            $is_us = $ent['locale'] === 'US';
            assert( $is_canada || $is_us );

            $brand = @$ex_brands[$ent['brand_slug']];
            $model = @$ex_models[$model_key];

            $data = [
                '__valid' => $brand && $model,
                'upc' => $ent['upc'],
                'part_number' => $ent['part_number'],
                'supplier' => $ent['supplier'],
                'brand_id' => @$brand['tire_brand_id'],
                'brand_slug' => @$brand['tire_brand_slug'],
                'model_id' => @$model['tire_model_id'],
                'model_slug' => @$model['tire_model_slug'],
                'size' => $ent['size'],
                'width' => $ent['width'],
                'profile' => $ent['profile'],
                'diameter' => $ent['diameter'],
                'load_index' => $ent['load_index_1'],
                'load_index_2' => $ent['load_index_2'],
                'speed_rating' => $ent['speed_rating'],
                'is_zr' => $ent['is_zr'],
                'extra_load' => $ent['extra_load'],
                'tire_sizing_system' => $ent['tire_sizing_system'],
                'msrp_ca' => $is_canada ? $ent['msrp'] : '',
                'cost_ca' => $is_canada ? $ent['cost'] : '',
                'map_price_ca' => $is_canada ? $ent['map_price'] : '',
                'price_ca' => $is_canada ? $ent['effective_price'] : '',
                'sold_in_ca' => $is_canada && $ent['effective_price'] ? 1 : 0,
                'stock_discontinued_ca' => 1,
                'msrp_us' => $is_us ? $ent['msrp'] : '',
                'cost_us' => $is_us ? $ent['cost'] : '',
                'map_price_us' => $is_us ? $ent['map_price'] : '',
                'price_us' => $is_us ? $ent['effective_price'] : '',
                'sold_in_us' => $is_us && $ent['effective_price'] ? 1 : 0,
                'stock_discontinued_us' => 1,
                'sync_id_insert_ca' => $is_canada ? (int) $req_id : null,
                'sync_date_insert_ca' => $is_canada ? $date : '',
                'sync_id_insert_us' => $is_us ? (int) $req_id : null,
                'sync_date_insert_us' => $is_us ? $date : '',
            ];

            $ret[] = [ $data, $format ];
        }

        return $ret;
    }

    /**
     * @param $ex_products
     * @param $locale
     * @return array
     */
    static function mark_products_not_sold_updates( $ex_products, $locale ) {

        assert( $locale === 'CA' || $locale === 'US' );

        $ret = [];

        foreach ( $ex_products as $prod ) {

            if ( $locale === 'CA' ) {
                // probably only totally necessary to change sold_in, but
                // i think its much better to just reset certain other columns
                // which will make things better when browsing certain pages in the
                // back-end.
                $data = [
                    'price_ca' => '',
                    'sold_in_ca' => 0,
                    'stock_amt_ca' => 0,
                    'stock_sold_ca' => 0,
                    'stock_discontinued_ca' => 1,
                    'stock_unlimited_ca' => 0,
                ];
            } else {
                $data = [
                    'price_us' => '',
                    'sold_in_us' => 0,
                    'stock_amt_us' => 0,
                    'stock_sold_us' => 0,
                    'stock_discontinued_us' => 1,
                    'stock_unlimited_us' => 0,
                ];
            }

            $ret[] = [ $data,  [
                'part_number' => $prod['part_number'],
            ]];
        }

        return $ret;
    }

    /**
     * @param $ents
     * @param $ex_brands
     * @param $ex_models
     * @param $req_id
     * @param $date
     * @return array
     */
    static function tire_updates( $ents, $ex_brands, $ex_models, $req_id, $date ) {

        $ret = [];

        $data_format = self::get_database_col_formats( 'tires' );

        foreach ( $ents as $ent ) {

            $ex = $ent['__ex'];
            $diff = $ent['__diff'];

            $is_canada = $ent['locale'] === 'CA';
            $is_us = $ent['locale'] === 'US';
            assert( $is_canada || $is_us );

            $model_key = implode( '##', [ $ent['brand_slug'], $ent['model_slug'] ] );

            $brand = @$ex_brands[$ent['brand_slug']];
            $model = @$ex_models[$model_key];

            $where = [
                'part_number' => $ent['part_number'],
            ];

            $where_format = [];

            // if we get to here, the new product should be valid and different
            // from the existing product in at least one column. However, we don't
            // really have to care about which columns are different, just set all
            // columns to the new valid values.
            $data = [
                '__valid' => $brand && $model,
                'upc' => $ent['upc'],
                'supplier' => $ent['supplier'],
                'brand_id' => @$brand['tire_brand_id'],
                'brand_slug' => @$brand['tire_brand_slug'],
                'model_id' => @$model['tire_model_id'],
                'model_slug' => @$model['tire_model_slug'],
                'size' => $ent['size'],
                'width' => $ent['width'],
                'profile' => $ent['profile'],
                'diameter' => $ent['diameter'],
                'load_index' => $ent['load_index_1'],
                'load_index_2' => $ent['load_index_2'],
                'speed_rating' => $ent['speed_rating'],
                'is_zr' => $ent['is_zr'],
                'extra_load' => $ent['extra_load'],
                'tire_sizing_system' => $ent['tire_sizing_system'],
            ];

            $data = array_merge( $data, self::product_update_locale_cols( $ent, $is_canada, $req_id, $date ) );

            $ret[] = [ $data, $where, $data_format, $where_format ];
        }

        return $ret;
    }

    /**
     * @param $ent
     * @param $is_canada
     * @param $req_id
     * @param $date
     * @return array
     */
    static function product_update_locale_cols( $ent, $is_canada, $req_id, $date ){

        if ( $is_canada ) {
            return [
                'msrp_ca' => $ent['msrp'],
                'cost_ca' => $ent['cost'],
                'map_price_ca' => $ent['map_price'],
                'price_ca' => $ent['effective_price'],
                // 0.1 in case of rounding errors, which I don't expect to occur.
                'sold_in_ca' => $ent['effective_price'] > 0.1 ? '1' : '',
                'sync_id_update_ca' => $req_id ? (int) $req_id : '',
                'sync_date_update_ca' => $date
            ];
        } else {
            return [
                'msrp_us' => $ent['msrp'],
                'cost_us' => $ent['cost'],
                'map_price_us' => $ent['map_price'],
                'price_us' => $ent['effective_price'],
                'sold_in_us' => $ent['effective_price'] > 0.1 ? '1' : '',
                'sync_id_update_us' => $req_id ? (int) $req_id : '',
                'sync_date_update_us' => $date
            ];
        }
    }

    /**
     * @param $ents
     * @param $ex_brands
     * @param $ex_models
     * @param $ex_finishes
     * @param $req_id
     * @param $date
     * @return array
     */
    static function rim_inserts( $ents, $ex_brands, $ex_models, $ex_finishes, $req_id, $date ) {

        $ret = [];

        $format = self::get_database_col_formats( 'rims' );

        foreach ( $ents as $ent ) {

            $is_canada = $ent['locale'] === 'CA';
            $is_us = $ent['locale'] === 'US';
            assert( $is_canada || $is_us );

            $model_key = implode( '##', [ $ent['brand_slug'], $ent['model_slug'] ] );

            $finish_key = implode( '##', [
                $ent['brand_slug'],
                $ent['model_slug'],
                $ent['color_1_slug'],
                $ent['color_2_slug'],
                $ent['finish_slug'],
            ] );

            $brand = @$ex_brands[$ent['brand_slug']];
            $model = @$ex_models[$model_key];
            $finish = @$ex_finishes[$finish_key];

            $data = [
                '__valid' => $brand && $model && $finish,
                'upc' => $ent['upc'],
                'part_number' => $ent['part_number'],
                'supplier' => $ent['supplier'],
                'brand_id' => $brand['rim_brand_id'],
                'brand_slug' => $brand['rim_brand_slug'],
                'model_id' => $model['rim_model_id'],
                'model_slug' => $model['rim_model_slug'],
                'finish_id' => $finish['rim_finish_id'],
                'color_1' => $finish['color_1'],
                'color_2' => $finish['color_2'],
                'finish' => $finish['finish'],
                'type' => $ent['type'],
                'style' => $ent['style'],
                'size' => $ent['diameter'] . 'x' . $ent['width'],
                'width' => $ent['width'],
                'diameter' => $ent['diameter'],
                'bolt_pattern_1' => $ent['bolt_pattern_1'],
                'bolt_pattern_2' => $ent['bolt_pattern_2'],
                'seat_type' => $ent['seat_type'],
                'offset' => $ent['offset'],
                'center_bore' => $ent['center_bore'],
                'msrp_ca' => $is_canada ? $ent['msrp'] : '',
                'cost_ca' => $is_canada ? $ent['cost'] : '',
                'map_price_ca' => $is_canada ? $ent['map_price'] : '',
                'price_ca' => $is_canada ? $ent['effective_price'] : '',
                'sold_in_ca' => $is_canada && $ent['effective_price'] ? 1 : 0,
                'stock_discontinued_ca' => 1,
                'msrp_us' => $is_us ? $ent['msrp'] : '',
                'cost_us' => $is_us ? $ent['cost'] : '',
                'map_price_us' => $is_us ? $ent['map_price'] : '',
                'price_us' => $is_us ? $ent['effective_price'] : '',
                'sold_in_us' => $is_us && $ent['effective_price'] ? 1 : 0,
                'stock_discontinued_us' => 1,
                'sync_id_insert_ca' => $is_canada ? (int) $req_id : null,
                'sync_date_insert_ca' => $is_canada ? $date : '',
                'sync_id_insert_us' => $is_us ? (int) $req_id : null,
                'sync_date_insert_us' => $is_us ? $date : '',
            ];

            $ret[] = [ $data, $format ];
        }

        return $ret;
    }

    /**
     * @param $ents
     * @param $ex_brands
     * @param $ex_models
     * @param $ex_finishes
     * @param $req_id
     * @param $date
     * @return array
     */
    static function rim_updates( $ents, $ex_brands, $ex_models, $ex_finishes, $req_id, $date ) {

        $ret = [];

        $data_format = self::get_database_col_formats( 'rims' );

        foreach ( $ents as $ent ) {

            $ex = $ent['__ex'];
            $diff = $ent['__diff'];

            $is_canada = $ent['locale'] === 'CA';
            $is_us = $ent['locale'] === 'US';
            assert( $is_canada || $is_us );

            $model_key = implode( '##', [ $ent['brand_slug'], $ent['model_slug'] ] );

            $finish_key = implode( '##', [
                $ent['brand_slug'],
                $ent['model_slug'],
                $ent['color_1_slug'],
                $ent['color_2_slug'],
                $ent['finish_slug'],
            ] );

            $brand = @$ex_brands[$ent['brand_slug']];
            $model = @$ex_models[$model_key];
            $finish = @$ex_finishes[$finish_key];

            $where = [
                'part_number' => $ent['part_number'],
            ];

            $where_format = [];

            // if we get to here, the new product should be valid and different
            // from the existing product in at least one column. However, we don't
            // really have to care about which columns are different, just set all
            // columns to the new valid values.
            $data = [
                '__valid' => $brand && $model,
                'upc' => $ent['upc'],
                'supplier' => $ent['supplier'],
                'brand_id' => $brand['rim_brand_id'],
                'brand_slug' => $brand['rim_brand_slug'],
                'model_id' => $model['rim_model_id'],
                'model_slug' => $model['rim_model_slug'],
                'finish_id' => $finish['rim_finish_id'],
                'color_1' => $finish['color_1'],
                'color_2' => $finish['color_2'],
                'finish' => $finish['finish'],
                'type' => $ent['type'],
                'style' => $ent['style'],
                'size' => $ent['diameter'] . 'x' . $ent['width'],
                'width' => $ent['width'],
                'diameter' => $ent['diameter'],
                'bolt_pattern_1' => $ent['bolt_pattern_1'],
                'bolt_pattern_2' => $ent['bolt_pattern_2'],
                'seat_type' => $ent['seat_type'],
                'offset' => $ent['offset'],
                'center_bore' => $ent['center_bore'],
            ];

            $data = array_merge( $data, self::product_update_locale_cols( $ent, $is_canada, $req_id, $date ) );

            $ret[] = [ $data, $where, $data_format, $where_format ];
        }

        return $ret;
    }

    /**
     * Delete products and log all products deleted to a file.
     *
     * @param $to_delete
     * @param $type
     * @param $req_id
     * @param $sync_update_id
     * @param $sync_key
     */
    static function delete_products( $to_delete, $type, $req_id, $sync_update_id, $sync_key ) {

        list( $delete_sql, $delete_params ) = self::get_delete_products_sql( $to_delete, $type );

        if ( $delete_sql ) {

            $deleted = self::execute( $delete_sql, $delete_params );

            $delete_filename = implode( "-", [ 'r' . (int) $req_id, 'u' . (int) $sync_update_id, $sync_key, 'c' . count( $to_delete ), $deleted ? "1" : "0" ] );

            $json = json_encode( $to_delete, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_IGNORE );

            if ( $type === 'tires' ) {
                @mkdir( LOG_DIR . '/sync-delete-tires', 0755, true );
                @file_put_contents( LOG_DIR . '/sync-delete-tires/' . $delete_filename, $json );
            } else {
                @mkdir( LOG_DIR . '/sync-delete-rims', 0755, true );
                @file_put_contents( LOG_DIR . '/sync-delete-rims/' . $delete_filename, $json );
            }
        }
    }

    /**
     * @param $products
     * @param $type
     * @return array
     */
    static function get_delete_products_sql( $products, $type ) {
        $params = [];

        if ( ! $products ) {
            return [ false, false ];
        }

        // ie. :p0, :p1, :p2, etc.
        $in = implode( ", ", array_map( function( $prod ) use( &$params ){
            $key = ':p' . count( $params );
            $params[] = [ $key, $prod['part_number'] ];
            return $key;
        }, $products ) );

        if ( $type === 'tires' ) {
            $q = "delete from tires where part_number IN ($in); ";
        } else {
            assert( $type === 'rims' );
            $q = "delete from rims where part_number IN ($in); ";
        }

        return [ $q, $params ];
    }

    /**
     * Mock updates because we can't truly generate all the updates without
     * updating things incrementally. For example, brands have to be inserted before
     * creating the insert arrays for models, because they require the brand IDs.
     *
     * So with this fn, we'll just more or less ignore those, while still returning
     * enough data to print and verify that things are more or less lining up.
     *
     * @param $valid_tires - ie. Product_Sync_Compare::compare_tires()
     * @param $brands - ie. Product_Sync_Compare::compare_tire_brands()
     * @param $models - ie. Product_Sync_Compare::compare_tire_models()
     * @param $ex_brands - Product_Sync_Compare::get_ex_tire_brands()
     * @param $ex_models - Product_Sync_Compare::get_ex_tire_brands()
     * @return array
     */
    static function create_mock_tire_updates( $valid_tires, $brands, $models, $ex_brands, $ex_models ){

        $req_id = 0001;
        $date = self::get_database_formatted_date_now();

        list( $brands_to_insert ) = Product_Sync_Compare::split_ents( $brands );
        list( $models_to_insert, $models_to_update ) = Product_Sync_Compare::split_ents( $models );
        list( $tires_to_insert, $tires_to_update ) = Product_Sync_Compare::split_ents( $valid_tires );

        // 0: inserts, 1: updates
        $b0 = Product_Sync_Update::tire_brand_inserts( $brands_to_insert, $date );
        $m0 = Product_Sync_Update::tire_model_inserts( $models_to_insert, $ex_brands, $date );
        $m1 = Product_Sync_Update::tire_model_updates( $models_to_update );
        $t0 = Product_Sync_Update::tire_inserts( $tires_to_insert, $ex_brands, $ex_models, $req_id, $date );
        $t1 = Product_Sync_Update::tire_updates( $tires_to_update, $ex_brands, $ex_models, $req_id, $date );

        $first = function( $arr ) {
            return $arr[0];
        };

        return [
            array_map( $first, $b0 ),
            array_map( $first, $m0 ),
            array_map( $first, $m1 ),
            array_map( $first, $t0 ),
            array_map( $first, $t1 ),
        ];
    }

    /**
     * @param $valid_rims - ie. Product_Sync_Compare::compare_rims()
     * @param $brands - ie. Product_Sync_Compare::compare_rim_brands()
     * @param $models - ie. Product_Sync_Compare::compare_rim_models()
     * @param $finishes - ie. Product_Sync_Compare::compare_rim_finishes()
     * @param $ex_brands - Product_Sync_Compare::get_ex_rim_brands()
     * @param $ex_models - Product_Sync_Compare::get_ex_rim_brands()
     * @param $ex_finishes - Product_Sync_Compare::get_ex_rim_finishes()
     * @return array
     */
    static function create_mock_rim_updates( $valid_rims, $brands, $models, $finishes, $ex_brands, $ex_models, $ex_finishes ){

        $req_id = 0002;
        $date = self::get_database_formatted_date_now();

        list( $brands_to_insert ) = Product_Sync_Compare::split_ents( $brands );
        list( $models_to_insert ) = Product_Sync_Compare::split_ents( $models );
        list( $finishes_to_insert, $finishes_to_update ) = Product_Sync_Compare::split_ents( $finishes );
        list( $rims_to_insert, $rims_to_update ) = Product_Sync_Compare::split_ents( $valid_rims );

        $b0 = Product_Sync_Update::rim_brand_inserts( $brands_to_insert, $date );
        $m0 = Product_Sync_Update::rim_model_inserts( $models_to_insert, $ex_brands, $date );
        $f0 = Product_Sync_Update::rim_finish_inserts( $finishes_to_insert, $ex_models, $date );
        $f1 = Product_Sync_Update::rim_finish_updates( $finishes_to_update );
        $r0 = Product_Sync_Update::rim_inserts( $rims_to_insert, $ex_brands, $ex_models, $ex_finishes, $req_id, $date );
        $r1 = Product_Sync_Update::rim_updates( $rims_to_update, $ex_brands, $ex_models, $ex_finishes, $req_id, $date );

        $first = function( $arr ) {
            return $arr[0];
        };

        return [
            array_map( $first, $b0 ),
            array_map( $first, $m0 ),
            array_map( $first, $f0 ),
            array_map( $first, $f1 ),
            array_map( $first, $r0 ),
            array_map( $first, $r1 ),
        ];
    }

    /**
     * @param Product_Sync $sync
     * @param array $valid_products
     * @param $req_id
     */
    static function accept_all_changes( Product_Sync $sync, array $valid_products, $req_id ) {

        //  $to_delete = Product_Sync_Compare::get_ex_products_to_delete( $this::SUPPLIER, $this::LOCALE, $valid_products, $ex_products );

        $mem = new Time_Mem_Tracker();

        $req_id = (int) $req_id;
        $date = Product_Sync_Update::get_database_formatted_date_now();
        $sync->assertions();

        $supp = DB_Supplier::get_instance_via_slug( $sync::SUPPLIER );

        if ( ! $supp ) {
            $supp = DB_Supplier::insert([
                'supplier_slug' => $sync::SUPPLIER,
                'supplier_name' => $sync::SUPPLIER,
            ]);
        }

        $sync_update_id = DB_Sync_Update::insert([
            'sync_request_id' => $req_id,
            'sync_key' => $sync::KEY,
            'type' => $sync::TYPE,
            'locale' => $sync::LOCALE,
            'supplier' => $sync::SUPPLIER,
            'counts' => '{}',
            'debug' => '{}',
            'date' => $date,
        ]);

        $log_desc = $sync::KEY . '-Req' . $req_id . '-Upd' . $sync_update_id;

        $sync_update = DB_Sync_Update::create_instance_via_primary_key( $sync_update_id );

        if ( $sync::TYPE === 'tires' ) {

            $now_invalid = [];

            // mutates $valid_products and $now_invalid
            list( $ex_products, $ex_brands, $ex_models, $derived_brands, $derived_models )
                = Product_Sync_Compare::compare_tires_all( $valid_products, $now_invalid, $mem );

            // after above.
            list( $to_delete, $to_mark_not_sold ) = Product_Sync_Compare::get_ex_products_to_delete( $sync::SUPPLIER, $sync::LOCALE, $valid_products, $ex_products );

            $ex_products = null;

            list( $tires_to_insert, $tires_to_update ) = Product_Sync_Compare::split_ents( $valid_products );
            list( $brands_to_insert ) = Product_Sync_Compare::split_ents( $derived_brands );
            list( $models_to_insert, $models_to_update ) = Product_Sync_Compare::split_ents( $derived_models );

            $mem->breakpoint( 'split_ents' );

            $counts_0 = [
                'tires_to_insert' => count( $tires_to_insert ),
                'tires_to_update' => count( $tires_to_update ),
                'brands_to_insert' => count( $brands_to_insert ),
                'models_to_insert' => count( $models_to_insert ),
                'models_to_update' => count( $models_to_update ),
                'products_to_delete' => count( $to_delete ),
                'products_mark_not_sold' => count( $to_mark_not_sold ),
            ];

            $sync_update->update_json_column_via_callback( 'counts', function( $prev ) use( $counts_0 ){
                return array_merge( $prev, $counts_0 );
            } );

            $mem->breakpoint('split');

            self::do_inserts( 'tire_brands', self::tire_brand_inserts(
                $brands_to_insert,
                $date
            ), true, $log_desc );

            // refresh all brands from the database
            $ex_brands = Product_Sync_Compare::get_ex_tire_brands();

            $mem->breakpoint('brands');

            self::do_inserts( 'tire_models', self::tire_model_inserts(
                $models_to_insert,
                $ex_brands,
                $date
            ), true, $log_desc);

            self::do_updates( 'tire_models', self::tire_model_updates(
                $models_to_update
            ), true, $log_desc );

            // refresh all models from the database
            $ex_models = Product_Sync_Compare::get_ex_tire_models();

            $mem->breakpoint('models');

            self::do_inserts( 'tires', self::tire_inserts(
                $tires_to_insert,
                $ex_brands,
                $ex_models,
                $req_id,
                $date
            ), true, $log_desc );

            $mem->breakpoint('tires_inserted');

            self::do_updates( 'tires', self::tire_updates(
                $tires_to_update,
                $ex_brands,
                $ex_models,
                $req_id,
                $date
            ), true, $log_desc );

            $mem->breakpoint('tires_updated');

            self::do_updates( 'tires', self::mark_products_not_sold_updates( $to_mark_not_sold, $sync::LOCALE ) );

            $mem->breakpoint('tires_marked_not_sold');

            self::delete_products( $to_delete, 'tires', $req_id, $sync_update_id, $sync::KEY );

            $mem->breakpoint('tires_deleted');

            $sync_update->update_json_column_via_callback( 'debug', function( $prev ) use( $mem ){
                $prev['time_mem'] = Product_Sync::time_mem_summary( $mem );
                return $prev;
            } );

        } else {

            $now_invalid = [];

            // mutates $valid_products and $now_invalid
            list ( $ex_products, $ex_brands, $ex_models, $ex_finishes, $derived_brands, $derived_models, $derived_finishes )
                = Product_Sync_Compare::compare_rims_all( $valid_products, $now_invalid, $mem );

            // after above
            list( $to_delete, $to_mark_not_sold ) = Product_Sync_Compare::get_ex_products_to_delete( $sync::SUPPLIER, $sync::LOCALE, $valid_products, $ex_products );
            $ex_products = null;

            list( $rims_to_insert, $rims_to_update ) = Product_Sync_Compare::split_ents( $valid_products );
            list( $brands_to_insert ) = Product_Sync_Compare::split_ents( $derived_brands );
            list( $models_to_insert ) = Product_Sync_Compare::split_ents( $derived_models );
            list( $finishes_to_insert, $finishes_to_update ) = Product_Sync_Compare::split_ents( $derived_finishes );

            $mem->breakpoint('split_ents');

            $counts_0 = [
                'rims_to_insert' => count( $rims_to_insert ),
                'rims_to_update' => count( $rims_to_update ),
                'brands_to_insert' => count( $brands_to_insert ),
                'models_to_insert' => count( $models_to_insert ),
                'finishes_to_insert' => count( $finishes_to_insert ),
                'finishes_to_update' => count( $finishes_to_update ),
                'products_to_delete' => count( $to_delete ),
                'products_mark_not_sold' => count( $to_mark_not_sold ),
            ];

            $sync_update->update_json_column_via_callback( 'counts', function( $prev ) use( $counts_0 ){
                return array_merge( $prev, $counts_0 );
            } );

            $mem->breakpoint('split');

            self::do_inserts( 'rim_brands', self::rim_brand_inserts(
                $brands_to_insert,
                $date
            ), true, $log_desc );

            // refresh all brands from the database
            $ex_brands = Product_Sync_Compare::get_ex_rim_brands();

            $mem->breakpoint('brands');

            self::do_inserts( 'rim_models', self::rim_model_inserts(
                $models_to_insert,
                $ex_brands,
                $date
            ), true, $log_desc );

            // refresh all models from the database
            $ex_models = Product_Sync_Compare::get_ex_rim_models();

            $mem->breakpoint('models');

            self::do_inserts( 'rim_finishes', self::rim_finish_inserts(
                $finishes_to_insert,
                $ex_models,
                $date
            ), true, $log_desc );

            self::do_updates( 'rim_finishes', self::rim_finish_updates(
                $finishes_to_update
            ), true, $log_desc );

            $ex_finishes = Product_Sync_Compare::get_ex_rim_finishes();

            $mem->breakpoint('finishes');

            self::do_inserts( 'rims', self::rim_inserts(
                $rims_to_insert,
                $ex_brands,
                $ex_models,
                $ex_finishes,
                $req_id,
                $date
            ), true, $log_desc );

            $mem->breakpoint('rim_inserts');

            self::do_updates( 'rims', self::rim_updates(
                $rims_to_update,
                $ex_brands,
                $ex_models,
                $ex_finishes,
                $req_id,
                $date
            ), true, $log_desc );

            $mem->breakpoint('rim_updates');

            self::do_updates( 'rims', self::mark_products_not_sold_updates( $to_mark_not_sold, $sync::LOCALE ) );

            $mem->breakpoint('rims_marked_not_sold');

            self::delete_products( $to_delete, 'rims', $req_id, $sync_update_id, $sync::KEY );

            $mem->breakpoint('rims_deleted');

            $sync_update->update_json_column_via_callback( 'debug', function( $prev ) use( $mem ){
                $prev['time_mem'] = Product_Sync::time_mem_summary( $mem );
                return $prev;
            } );
        }
    }

    /**
     * Has a specific use case when comparing existing product prices with new values.
     *
     * @param $v1
     * @param $v2
     * @return bool
     */
    static function str_cmp_prices( $v1, $v2 ) {
        return strval( $v1 ) === strval( $v2 );
    }

    /**
     * @param $ex_product
     * @param $locale
     * @param $cost
     * @param $msrp
     * @param $map_price
     * @param $effective_price
     * @param $is_sold
     * @return bool
     */
    static function ex_product_needs_price_update( $ex_product, $locale, $cost, $msrp, $map_price, $effective_price, $is_sold ){

        assert( $locale === 'CA' || $locale === 'US' );
        $suffix = $locale === 'CA' ? '_ca' : '_us';

        if( ! self::str_cmp_prices( $ex_product['price' . $suffix], $effective_price ) ) {
            return true;
        }

        if( ! self::str_cmp_prices( $ex_product['sold_in' . $suffix], $is_sold ) ) {
            return true;
        }

        if( ! self::str_cmp_prices( $ex_product['cost' . $suffix], $cost ) ) {
            return true;
        }

        if( ! self::str_cmp_prices( $ex_product['msrp' . $suffix], $msrp ) ) {
            return true;
        }

        if( ! self::str_cmp_prices( $ex_product['map_price' . $suffix], $map_price ) ) {
            return true;
        }

        return false;
    }

    /**
     * @param Product_Sync $sync
     * @param array $products
     * @return array
     */
    static function get_price_change_updates( Product_Sync $sync, array $products ){

        $sync->assertions();

        // updates for products in the file and the database
        $updates_0 = [];

        // updates for products not in the file but in the database, all of which
        // contain the same updates, and if we decide to process those updates, will
        // mark all those products no longer sold.
        $updates_1 = [];

        $sync->tracker->breakpoint('before_build_updates');

        $table = $sync::TYPE === 'tires' ? 'tires' : 'rims';

        if ( $sync::LOCALE === 'CA' ) {
            $q = "SELECT part_number, upc, cost_ca, msrp_ca, map_price_ca, price_ca, sold_in_ca from $table WHERE supplier = :supplier;";
        } else {
            $q = "SELECT part_number, upc, cost_us, msrp_us, price_us, map_price_us, sold_in_us from $table WHERE supplier = :supplier;";
        }

        $results = Product_Sync::get_results( $q, [
            [ 'supplier', $sync::SUPPLIER ],
        ]);

        $sync->tracker->breakpoint( "get_supplier_products" );

        // going to log this
        $ex_products = Product_Sync_Compare::index_by( $results, function( $row ) {
            return $row['part_number'];
        });

        // start with all db products then remove some
        $ex_products_not_in_file = $ex_products;
        $count_ex_products_same = 0;

        $sync->tracker->breakpoint('get_products');

        foreach ( $products as $prod ) {

            // if the products are valid it should mean that the effective price is above zero
            // and other price columns are properly formatted.

            $pn = $prod['part_number'];

            if ( ! isset( $ex_products[$pn] ) ) {
                continue;
            }

            $ex_prod = $ex_products[$pn];

            unset( $ex_products_not_in_file[$pn] );

            $effective_price = Product_Sync::format_price( $prod['effective_price'], 10, '' );
            $cost = Product_Sync::format_price( $prod['cost'] );
            $msrp = Product_Sync::format_price( $prod['msrp'] );
            $map_price = Product_Sync::format_price( $prod['map_price'] );
            $is_sold = $effective_price > 10;

            $needs_update = self::ex_product_needs_price_update( $ex_prod, $sync::LOCALE, $cost, $msrp, $map_price, $effective_price, $is_sold );

            if ( ! $needs_update ) {
                $count_ex_products_same++;
                continue;
            }

            if ( $sync::LOCALE === 'CA' ) {
                $data = [
                    'cost_ca' => $cost,
                    'msrp_ca' => $msrp,
                    'map_price_ca' => $map_price,
                    'price_ca' => $effective_price,
                    'sold_in_ca' => $is_sold,
                ];
            } else {
                $data = [
                    'cost_us' => $cost,
                    'msrp_us' => $msrp,
                    'map_price_us' => $map_price,
                    'price_us' => $effective_price,
                    'sold_in_us' => $is_sold,
                ];
            }

            $where = [
                'part_number' => $prod['part_number'],
                'supplier' => $prod['supplier'],
            ];

            $input = Product_Sync::filter_keys( $prod, [
                'cost', 'msrp', 'map_price', 'effective_price'
            ] );

            $updates_0[] = [ $data, $where, [
                '__input' => $input,
                '__ex' => $ex_products[$pn],
                '__price_rules' => @$prod['__meta']['price_rules']
            ] ];
        }

        $sync->tracker->breakpoint( "price_updates_0" );

        // do after first loop (which sets up the required var)
        foreach ( $ex_products_not_in_file as $prod ) {

            if ( $sync::LOCALE === 'CA' ) {
                $data = [
                    'cost_ca' => '',
                    'msrp_ca' => '',
                    'map_price_ca' => '',
                    'price_ca' => '',
                    'sold_in_ca' => '',
                ];
            } else {
                $data = [
                    'cost_us' => '',
                    'msrp_us' => '',
                    'map_price_us' => '',
                    'price_us' => '',
                    'sold_in_us' => '',
                ];
            }

            $where = [
                'part_number' => $prod['part_number'],
            ];

            $updates_1[] = [ $data, $where, [
                '__ex' => $prod,
            ] ];
        }

        $sync->tracker->breakpoint( "price_updates_1" );

        return [ $updates_0, $updates_1, $ex_products, $ex_products_not_in_file, $count_ex_products_same ];
    }

    /**
     * stupid function
     *
     * @param $items
     * @param null $index
     * @return float|int
     */
    static function get_avg_price( $items, $index = null ) {

        $_items = Product_Sync::reduce( $items, function( $item ) use( $index ) {

            if ( $index === null ) {
                return $item > 0 ? $item : false;
            }

            return $item[$index] > 0 ? $item[$index] : false;

        }, true );


        if ( count( $_items ) ) {
            return array_sum( $_items ) / count( $_items );
        }

        return 0;
    }

    /**
     * From database
     *
     * @param $type
     * @param $locale
     * @param $supplier
     * @return int|mixed
     */
    static function get_current_avg_price( $type, $locale, $supplier ) {

        $table = $type === 'tires' ? 'tires' : 'rims';

        if ( $locale === 'CA' ) {
            $q = "select avg(price_ca) a, count(*) c from $table where supplier = :supp AND sold_in_ca = 1";
        } else {
            $q = "select avg(price_ca) a, count(*) c from $table where supplier = :supp AND sold_in_us = 1";
        }

        $params = [
            [ 'supp', $supplier ]
        ];

        $rows = get_database_instance()->get_results( $q, $params );

        if ( $rows ) {
            return $rows[0]->a;
        }

        return 0;
    }

    /**
     * @param $old_avg
     * @param $new_avg
     * @return string
     */
    static function get_pct_change( $old_avg, $new_avg ) {
        if ( $old_avg > 0 ) {
            $multiplier =  $new_avg / $old_avg;
            $pct_change = @number_format( ( $multiplier - 1 ) * 100, 3, '.', ',' );
        } else {
            $pct_change = '0';
        }

        return $pct_change;
    }

    /**
     * Invalid products can still have their prices updated, the product could be invalid for some other
     * reason, but if the supplier lists a part number and valid price(s) in a file (and the price
     * could be calculated without errors), then I don't see any reason to not update the product's price.
     *
     * @param Product_Sync $sync
     * @param array $valid_products
     * @param array $invalid_products
     * @param $req_id
     * @param bool $log
     * @param null $log_context
     * @return array
     */
    static function accept_price_changes( Product_Sync $sync, array $valid_products, array $invalid_products, $req_id, $log = true, $log_context = null ){

        $sync->assertions();
        $table = $sync::TYPE === 'tires' ? 'tires' : 'rims';

        // give priority to the valid product in case of duplicate part numbers.
        $all_products = array_merge( $valid_products, $invalid_products );

        $avg_price_before = self::get_current_avg_price( $sync::TYPE, $sync::LOCALE, $sync::SUPPLIER );

        $all_products = Product_Sync_Compare::index_by( $all_products, function( $prod ){
            return $prod['part_number'];
        }, true );

        $count_valid = count( $valid_products );
        $count_invalid = count( $invalid_products );
        $valid_products = null;
        $invalid_products = null;

        $sync->tracker->breakpoint( 'dup_part_numbers' );

        list ( $updates_0, $updates_1, $ex_products, $ex_products_not_in_file, $count_ex_products_same ) =
            self::get_price_change_updates( $sync, $all_products );

        $results_0 = self::do_updates( $table, $updates_0, false );

        $sync->tracker->breakpoint( 'do_price_updates_0' );

        $results_1 = self::do_updates( $table, $updates_1, false );

        $sync->tracker->breakpoint( 'do_price_updates_1' );

        $avg_price_after = self::get_current_avg_price( $sync::TYPE, $sync::LOCALE, $sync::SUPPLIER );
        $price_pct_change = self::get_pct_change( $avg_price_before, $avg_price_after );

        // its unlikely that we'll have any errors here.
        $errors_0 = array_filter( $results_0 );
        $errors_1 = array_filter( $results_1 );

        if ( $log ) {
            $filename = $sync::KEY . '-Req' . $req_id . '-' . date( 'Y-m-d-h-i-s' ) . '_' . rand(1000, 9999) . '.json';
        } else {
            $filename = '';
        }

        $db_counts = [
            'notes' => '0 for products in file and db, 1 for products in db but not in file.',
            'count_file' => count( $all_products ),
            'count_valid' => $count_valid,
            'count_invalid' => $count_invalid,
            'count_duplicate_part_numbers_in_file' => $count_valid + $count_invalid - count( $all_products ),
            'count_ex_products' => count( $ex_products ),
            'count_ex_products_same' => $count_ex_products_same,
            'count_0' => count( $updates_0 ),
            'count_1' => count( $updates_1 ),
            'count_errors_0' => count( $errors_0 ),
            'count_errors_1' => count( $errors_1 ),
            'price_rules' => Product_Sync_Pricing_UI::get_supplier_price_rules( $sync::TYPE, $sync::LOCALE, $sync::SUPPLIER, false )
        ];

        // columns for database table
        $db_insert = [
            'locale' => $sync::LOCALE,
            'supplier' => $sync::SUPPLIER,
            'type' => $sync::TYPE,
            'context' => $log_context,
            'req_id' => $req_id,
            'filename' => $filename,
            'prev_avg' => @number_format( $avg_price_before, 3, '.', ',' ),
            'new_avg' => @number_format( $avg_price_after, 3, '.', ',' ),
            'pct_change' => $price_pct_change,
            'date' => PS\Cron\get_date_formatted(),
            'counts' => DB_Table::encode_json_obj( $db_counts ),
        ];

        // possibly free some memory
        $ex_products = null;

        DB_Price_Update::insert( $db_insert );

        $dir = LOG_DIR . '/price-updates';
        @mkdir( $dir, 0755, true );

        if ( $log ) {

            $log_data = $db_insert;

            $log_counts = $db_counts;

            // more stuff to log file (some items potentially very large).
            $log_counts['errors_0'] = $errors_0;
            $log_counts['errors_1'] = $errors_1;
            $log_counts['updates_0'] = $updates_0;
            $log_counts['updates_1'] = $updates_1;
            $log_counts['time_mem'] = Product_Sync::time_mem_summary( $sync->tracker );

            $log_data['counts'] = $log_counts;

            @file_put_contents( $dir . '/' . $filename, json_encode( $log_data, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_IGNORE ) );
            $sync->tracker->breakpoint('log_to_file' );

        }

        return [ count( $updates_0 ), count( $updates_1 ), count( $errors_0 ), count( $errors_1 ), $count_ex_products_same ];
    }

    /**
     * specify int cols, otherwise default of string will be used.
     *
     * Will probably only do this for rims and tires. The other tables
     * have so few int columns, we can just write them as needed.
     *
     * @param $table
     * @return string[]
     */
    static function get_database_col_formats( $table ){

        if ( $table === 'tires' ) {
            return [
                'brand_id' => '%d',
                'model_id' => '%d',
                'is_zr' => '%d',
                'stock_amt_ca' => '%d',
                'stock_sold_ca' => '%d',
                'stock_unlimited_ca' => '%d',
                'stock_discontinued_ca' => '%d',
                'stock_update_id_ca' => '%d',
                'stock_amt_us' => '%d',
                'stock_sold_us' => '%d',
                'stock_unlimited_us' => '%d',
                'stock_discontinued_us' => '%d',
                'stock_update_id_us' => '%d',
                'sync_id_insert_ca' => '%d',
                'sync_id_update_ca' => '%d',
                'sync_id_insert_us' => '%d',
                'sync_id_update_us' => '%d',
            ];
        }

        if ( $table === 'rims' ) {
            return [
                'rim_id' => '%d',
                'brand_id' => '%d',
                'model_id' => '%d',
                'finish_id' => '%d',
                'stock_amt_ca' => '%d',
                'stock_sold_ca' => '%d',
                'stock_unlimited_ca' => '%d',
                'stock_discontinued_ca' => '%d',
                'stock_update_id_ca' => '%d',
                'stock_amt_us' => '%d',
                'stock_sold_us' => '%d',
                'stock_unlimited_us' => '%d',
                'stock_discontinued_us' => '%d',
                'stock_update_id_us' => '%d',
                'sync_id_insert_ca' => '%d',
                'sync_id_update_ca' => '%d',
                'sync_id_insert_us' => '%d',
                'sync_id_update_us' => '%d',
            ];
        }

        throw_dev_error( "get_database_col_formats $table" );
    }
}
