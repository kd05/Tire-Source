<?php



/**
 * @see \MCS\MWSClient->GetReport( $report_id );
 *
 * Class MWS_Submit_Inventory_Feed
 */
Class MWS_Submit_Inventory_Feed {

    public static function get_tire_cache(){

        $db = get_database_instance();

        $q = '';
        $q .= 'SELECT * ';
        $q .= 'FROM tires ';
        $q .= 'INNER JOIN tire_brands ON tire_brands.tire_brand_id = tires.brand_id ';
        $q .= 'INNER JOIN tire_models ON tire_models.tire_model_id = tires.model_id ';
        $q .= ';';

        $tires = $db->get_results( $q );

        $_tires = [];

        foreach ( $tires as $tire ) {
            $_tires[$tire->part_number] = $tire;
        }

        return function( $part_number ) use( &$_tires ) {

            $t = @$_tires[$part_number];

            if ( $t ) {
                return DB_Tire::create_instance_or_null( $t, [
                    'brand' => DB_Tire_Brand::create_instance_or_null( $t ),
                    'model' => DB_Tire_Model::create_instance_or_null( $t ),
                ]);
            }

            return null;
        };
    }

    public static function get_rim_cache(){

        $db = get_database_instance();

        $q = '';
        $q .= 'SELECT * ';
        $q .= 'FROM rims ';
        $q .= 'INNER JOIN rim_brands ON rim_brands.rim_brand_id = rims.brand_id ';
        $q .= 'INNER JOIN rim_models ON rim_models.rim_model_id = rims.model_id ';
        $q .= 'INNER JOIN rim_finishes ON rim_finishes.rim_finish_id = rims.finish_id ';
        $q .= ';';

        $rims = $db->get_results( $q );

        $_rims = [];

        foreach ( $rims as $rim ) {
            $_rims[$rim->part_number] = $rim;
        }

        return function( $part_number ) use( &$_rims ) {

            $r = @$_rims[$part_number];

            if ( $r ) {
                return DB_Rim::create_instance_or_null( $r, [
                    'brand' => DB_Rim_Brand::create_instance_or_null( $r ),
                    'model' => DB_Rim_Model::create_instance_or_null( $r ),
                    'finish' => DB_Rim_Finish::create_instance_or_null( $r ),
                ]);
            }

            return null;
        };
    }


    /**
     * Sends products and stock amounts to Amazon or pretends that they were sent
     * and actually doesn't.
     *
     * @param array $product_update_array
     * @param $mws_locale
     * @param bool $force_send
     * @return array
     */
	public static function send( array $product_update_array, $mws_locale, $force_send = false ){

	    $ret = [
	        'force_send' => $force_send ? "1" : "0",
            'can_update' => APP_CAN_UPDATE_AMAZON_MWS ? "1" : "0",
            'mock_send' => ! APP_CAN_UPDATE_AMAZON_MWS && ! $force_send
        ];

	    if ( $ret['mock_send'] ) {
	        $ret['_msg'] = 'APP_CAN_UPDATE_AMAZON_MWS (and force_send) are both false. Not sending any data to Amazon.';
	        return $ret;
        }

	    assert( in_array( $mws_locale, [ MWS_LOCALE_CA, MWS_LOCALE_US ] ) );

	    try{
            $amazon = Amazon_MWS::get_instance( $mws_locale );
            $update_response = $amazon->client->updateStock( $product_update_array );
        } catch (Exception $e ) {
	        $ret['exception'] = $e->getMessage();
            return $ret;
        }

        return array_merge( $ret, $update_response );
    }

    /**
     * Build the product update array by querying product inventory from database
     * and using what you provide from $acc_stock. Just builds the array and does
     * not send it.
     *
     * @param array $report - array of product data from amazon
     * @param array $acc_stock - @see get_accessories_stock()
     * @param $mws_locale
     * @return array
     */
	public static function build_product_update_array( array $report, array $acc_stock, $mws_locale ){

        if ( $mws_locale === MWS_LOCALE_CA ) {
            $app_locale = APP_LOCALE_CANADA;
        } else if ( $mws_locale === MWS_LOCALE_US ) {
            $app_locale = APP_LOCALE_US;
        } else {
            throw_dev_error( "Invalid MWS locale." );
            exit;
        }

        // $tracker = new Time_Mem_Tracker("build_product_update_array" );

        // the main payload
        $product_update_array = [];

        $products_not_found = [];

        // likely store this in db,
        // don't add anything too large.
        // will add more to this below.
        $aggregates = [
            'count_report' => count( $report ),
            'db_in_stock' => 0,
            'db_no_stock' => 0,
            'acc_in_stock' => 0,
            'acc_no_stock' => 0,
            'count_invalid_prefix' => 0,
        ];

        $track_not_found = function( $part_number, $why = '' ) use( &$products_not_found ){
            $products_not_found[] = [
                'part_number' => $part_number,
                'why' => $why ? $why : null,
            ];
        };

        $rim_cache = self::get_rim_cache();
        $tire_cache = self::get_tire_cache();

        foreach ( $report as $row ) {

            $part_number_amazon = gp_if_set( $row, 'seller-sku' );
            $us_prefix = 'us_';

            // get part number in our database
            if ( $mws_locale === MWS_LOCALE_US ) {

                if ( strpos( $part_number_amazon, $us_prefix ) === 0 ) {
                    $part_number_database = substr( $part_number_amazon, strlen( $us_prefix ) );
                } else {
                    $aggregates['count_invalid_prefix']++;
                    $track_not_found( $part_number_amazon, 'invalid_us_prefix' );
                    continue;
                }

            } else {

                // if running a canada import make sure the products don't have this prefix.
                if ( strpos( $part_number_amazon, $us_prefix ) === 0 ) {

                    $aggregates['count_invalid_prefix']++;
                    $track_not_found( $part_number_amazon, 'ca_product_has_us_prefix' );
                    continue;

                } else {
                    $part_number_database = $part_number_amazon;
                }
            }

            // this shouldn't occur... ?
            if ( ! $part_number_amazon ) {
                $track_not_found( $part_number_amazon, 'no_part_number' );
                continue;
            }

            // check for rims first, then tires.
            // amazon doesn't tell us what type of product we have.

            $product = $rim_cache( $part_number_database );

            if ( ! $product ) {
                $product = $tire_cache( $part_number_database );
            }

            if ( $product ) {

                $stock = $product->get_amazon_stock_amount( $app_locale );

                if ( $stock > 0 ) {
                    $aggregates['db_in_stock']++;
                } else {
                    $aggregates['db_no_stock']++;
                }

            } else {
                // check accessories files
                if ( isset( $acc_stock[$part_number_database] ) && $acc_stock[$part_number_database] ) {

                    // ie. $acc_stock[$part_number_database] = [ 'supplier_1' => 50, 'supplier_2' => 0 ]

                    $stock = max( $acc_stock[$part_number_database] );

                    if ( $stock > 0 ) {
                        $aggregates['acc_in_stock']++;
                    } else {
                        $aggregates['acc_no_stock']++;
                    }

                } else {
                    $track_not_found( $part_number_amazon, 'no_product' );
                    $stock = 0;
                }
            }

            // we may return strictly null above to indicate to do nothing to the product
            if ( $stock === null ) {
                $track_not_found( $part_number_amazon, 'stock_null_therefore_not_updating' );
            } else {
                $product_update_array[$part_number_amazon] = $stock;
            }
        }


        $aggregates['count_product_update_array'] = count( $product_update_array );

        // products that exist on amazon but not in our DB or inventory files
        $aggregates['count_products_not_found'] = count( $products_not_found );

        $aggregates['count_zero_stock'] = count( array_filter( $product_update_array, function ( $row ) {
            return $row < 1;
        } ) );

        $aggregates['count_total_stock'] = array_sum( $product_update_array );

        $aggregates['average_stock'] = $aggregates['count_product_update_array'] < 1 ? 0 : $aggregates['count_total_stock'] / $aggregates['count_product_update_array'];

        return [ $product_update_array, [
            'aggregates' => $aggregates,
            'products_not_found' => $products_not_found
        ] ];
    }

    /**
     * stock that comes directly from suppliers and not from products
     * in the database (though many parts numbers returned will also
     * be products in the database).
     *
     * We use this for accessories but it also applies to tires/rims
     * that exist on amazon but not in our database, for suppliers
     * that have an AMAZON_ONLY supplier import.
     *
     * @param $app_locale - APP_LOCALE_CANADA or APP_LOCALE_US, ** NOT ** MWS_LOCALE_CA or MWS_LOCALE_US
     * @return array
     */
	public static function get_accessories_stock( $app_locale ){

        start_time_tracking( '_get_accessories_stock');

	    $suppliers = Supplier_Inventory_Supplier::get_all_supplier_instances( function( $i ) use( $app_locale ){
	        return $i::AMAZON_ONLY && $i->locale === $app_locale;
        });

	    $supplier_names = [];
	    $errors = [];

	    // see example
        $stock = [];

        $stock_example = [
            'part_number_1' => [
                'supplier_1' => 10,
                'supplier_2' => 20,
            ],
            'part_number_2' => [
                'supplier_1' => 0,
            ],
        ];

        $counts = [];
        $times = [];

	    foreach ( $suppliers as $supplier ) {

	        $name = $supplier::HASH_KEY;
	        /** @var Supplier_Inventory_Supplier $supplier */
	        $supplier_names[] = $name;

	        start_time_tracking( '_prep');

	        // expensive function
	        $supplier->prepare_for_import();

	        $counts[$name] = count( $supplier->array );

	        // time to only fetch the file via ftp
	        $times[$name]['ftp'] = @$supplier->ftp->time_in_seconds;

	        // time to fetch and prepare the file
	        $times[$name]['prepare_for_import'] = end_time_tracking( '_prep' );

	        if ( $supplier->ftp->errors ) {
	            $errors[] = $name . ": " . implode( $supplier->ftp->errors, ", " ) . ".";
            } else if ( empty( $supplier->array ) ) {
                $errors[] = $name . " has no data.";
            }

            start_time_tracking( '_parse');

	        foreach ( $supplier->array as $arr ) {

	            if( ! isset( $stock[$arr['part_number'] ] ) ){
                    $stock[$arr['part_number'] ] = [];
                }

	            // if $arr contains same part number twice, ignore all but first.
                // this should not happen but might be possible that it does.
                if( ! isset( $stock[$arr['part_number']][$name] ) ){
                    $stock[$arr['part_number'] ][$name] = $arr['stock'];
                }
            }

            $times[$name]['parse'] = end_time_tracking( '_parse' );
        }

        // when 2 suppliers give the same part number
        $conflicts = array_filter( $stock, function( $s ) {
            return count( $s ) > 1;
        });

	    $details = [
            'errors' => $errors,
            'suppliers' => $supplier_names,
            'count_total' => count( $stock ),
            'count_conflicts' => count( $conflicts ),
            'total_time' => end_time_tracking( "_get_accessories_stock" ),
            'mem' => get_mem_formatted(),
            'peak_mem' => get_peak_mem_formatted(),
            'times' => $times,
            'counts' => $counts,
        ];

	    // could be large.. might log to file but not db.
	    $details_extended = [
	        'conflicts' => $conflicts,
        ];

	    return [ $stock, $details, $details_extended ];
    }

}
