<?php

class Product_Sync {

    // 'tires' or 'rims'
    const TYPE = '';
    const KEY = '';

    // supplier slug used in the database
    const SUPPLIER = '';
    const LOCALE = '';

    const LOCAL_DIR = BASE_DIR . '/sync-files';

    // possibly 'local', meaning we keep the source data (ie. csv)
    // on the web server.
    const FETCH_TYPE = '';

    // name of CSV file inside of csv folder, if fetch type is 'local'
    const LOCAL_FILE = '';

    const FTP_SERVER = 'access764455319.webspace-data.io';

    const CRON_FETCH = true;
    const CRON_PRICES  = true;
    const CRON_EMAIL = true;

    /** @var Time_Mem_Tracker|null */
    public $tracker;

    /**
     * strtolower brand name -> brand name
     *
     * Helps avoid duplicate brands with similar names.
     *
     * @var string[]
     */
    public static $rim_brand_rename = [
        'cali' => 'Cali Off-Road',
        'cali-offroad' => 'Cali Off-Road',
        'cali offroad' => 'Cali Off-Road',
        'dl' => 'Dirty Life',
        'trailer' => 'Trailer Wheels',
        'atx series' => 'ATX',
        'american force cast' => 'American Force'
    ];

    public static $tire_brand_rename = [];

    /**
     * Product_Sync constructor.
     */
    function __construct(){
        $this->reset_time_tracker();
    }

    /**
     *
     */
    function reset_time_tracker(){
        $this->tracker = new Time_Mem_Tracker("sync_" . $this::KEY );
    }

    /**
     * @return array
     */
    static function get_instances(){

        $ret = [
            Product_Sync_CDA_Tire_CA::KEY => new Product_Sync_CDA_Tire_CA(),
            Product_Sync_DAI_Tire_CA::KEY => new Product_Sync_DAI_Tire_CA(),
            Product_Sync_DAI_Wheel_CA::KEY => new Product_Sync_DAI_Wheel_CA(),
            Product_Sync_DT_Tire_CA::KEY => new Product_Sync_DT_Tire_CA(),
            Product_Sync_DAI_Wheel_US::KEY => new Product_Sync_DAI_Wheel_US(),
            Product_Sync_Dynamic_Tire_CA::KEY => new Product_Sync_Dynamic_Tire_CA(),
            Product_Sync_RT_Wheel_CA::KEY => new Product_Sync_RT_Wheel_CA(),
            Product_Sync_RT_Wheel_US::KEY => new Product_Sync_RT_Wheel_US(),
            Product_Sync_Vision_Wheel_CA::KEY => new Product_Sync_Vision_Wheel_CA(),
            Product_Sync_Vision_Wheel_US::KEY => new Product_Sync_Vision_Wheel_US(),
            Product_Sync_Wheel_1_Rims_CA::KEY => new Product_Sync_Wheel_1_Rims_CA(),
            Product_Sync_Wheel_1_Rims_US::KEY => new Product_Sync_Wheel_1_Rims_US(),
            Product_Sync_Wheelpros_Wheel_CA_1::KEY => new Product_Sync_Wheelpros_Wheel_CA_1(),
            Product_Sync_Wheelpros_Wheel_CA_2::KEY => new Product_Sync_Wheelpros_Wheel_CA_2(),
            Product_Sync_Fastco_Tire_CA::KEY => new Product_Sync_Fastco_Tire_CA(),
            Product_Sync_Fastco_Wheel_CA::KEY => new Product_Sync_Fastco_Wheel_CA(),
        ];

        foreach ( $ret as $k => $v ) {
            if ( IS_WFL && IN_PRODUCTION && $v::LOCALE === 'US' ) {
                unset( $ret[$k] );
            }
        }

        return $ret;
    }

    /**
     * @param $key
     * @return self|null
     */
    static function get_via_key( $key ) {
        return @self::get_instances()[$key];
    }

    /**
     * Get the corresponding Supplier_Inventory_Supplier instance
     * (the one with the same type, locale, and supplier),
     * which can be used to run an inventory feed.
     *
     * @return Supplier_Inventory_Supplier|null
     */
    function get_inventory_instance(){

        $instances = Supplier_Inventory_Supplier::get_all_supplier_instances();

        $matches = [];

        /** @var Supplier_Inventory_Supplier $supp */
        foreach ( $instances as $supp ) {

            if ( $supp->type === $this::TYPE ) {
                if ( $supp->locale === $this::LOCALE ) {
                    if ( is_array( $supp->allowed_suppliers ) && in_array( $this::SUPPLIER, $supp->allowed_suppliers ) ) {
                        $matches[] = $supp;
                    }
                }
            }
        }

        // expecting exactly 1 match in most cases (possibly zero in others).
        // if more than 1, we can just ignore others for now it's not likely to
        // be a problem.
        return $matches ? $matches[0] : null;
    }

    /**
     * Possibly not necessary to run on every instantiation, but probably
     * good to run before doing anything important like updating the database.
     *
     * Could catch typos in Locale for example... if it was written as "Ca", then
     * you might have an if/else block that does US instead of CA products.
     */
    function assertions(){
        assert( ! empty( $this::KEY ) );
        assert( $this::TYPE === 'tires' || $this::TYPE === 'rims' );
        assert( $this::LOCALE === 'CA' || $this::LOCALE === 'US' );
    }

    /**
     * @return FTP_Get_Csv|null
     */
    function get_ftp_obj(){
        return null;
    }

    /**
     * Attempts to increase time/memory limits. We want to do this
     * before fetching/parsing/updating. No guarantee it has any effect,
     * depends on the server.
     */
    static function ini_config(){
        ini_set( 'memory_limit', '512M' );

        // even our most expensive scripts tend to complete within 10 seconds or so.
        ini_set( 'max_execution_time', '300' );
    }

    /**
     * The main method for subclasses to implement.
     *
     * @param array $row
     * @return array
     */
    function build_product( array $row ){
        return [];
    }

    /**
     * Get the matched price rules for the given product. They are returned
     * in order of priority (model rules first, then brand rules, then the supplier rule).
     *
     * The return value is an array of length 0 - 3. Use the first item in the array
     * as the price rule to base the price off of.
     *
     * @param $product
     * @param $indexed_price_rules - all price rules from database
     * @return false|array
     */
    function get_product_price_rules( array $product, array $indexed_price_rules ) {

        return Product_Sync_Compare::get_product_price_rules(
            $indexed_price_rules,
            $this::TYPE === 'tires' ? 'tires' : 'rims',
            $this::LOCALE,
            $this::SUPPLIER,
            @$product['brand_slug'],
            @$product['model_slug']
        );
    }

    /**
     * @param array $row
     * @param $all_price_rules
     * @return array
     * @throws Exception
     */
    function build_product_etc( array $row, $all_price_rules ) {

        $product = $this->build_product( $row );

        $product = $this->apply_global_product_rules( $product );

        $product = $this->apply_price( $product, $all_price_rules );

        list( $product, $errors ) = $this->validate_product( $product, false );

        return [ $product, $errors ];
    }

    /**
     * @param array $product
     * @param array $all_price_rules
     * @return array
     * @throws Exception
     */
    function apply_price( array $product, array $all_price_rules ) {

        $price_rules = $this->get_product_price_rules( $product, $all_price_rules );
        $price_rule = $price_rules ? $price_rules[0] : false;

        // worth remembering that invalid products still get prices updated,
        // so its not enough to just add an error to a product if its data warrants
        // that we want to ensure price updates will mark it as not sold.
        // We want to use this for DT where we are not currently running product syncs
        // but are doing price updates.
        if ( @$product['__meta']['dont_sell'] ) {
            list( $price, $price_err, $price_type ) = [ 0, 'Omitted due to price.', 'omit' ];
        } else {
            list( $price, $price_err, $price_type ) = $this->get_effective_price( $product, $price_rule );
        }

        $product['effective_price'] = $price_err ? "" : $price;

        // show in most tables for products
        $product['__price_rule'] = \PS\PriceRules\get_price_rule_debug( $price_rule );

        $product['__price_type'] = @$price_rule['rule_type'] . ' --> ' . $price_type;

        // useful on some of the debugging pages (ie. "parsed")
        $product['__meta']['price_rules'] = $price_rules;

        if ( ! self::check_effective_price( $price ) ) {
            $product['__meta']['errors'][] = "Effective price could not be calculated ($price_err)";
        }

        return $product;
    }

    /**
     * @param $obj
     * @return string
     */
    static function serialize_for_admin_table( $obj ) {
        $parts = [];

        foreach ( $obj as $k => $v ) {
            $_v = is_scalar( $v ) ? $v : "{}";
            $parts[] = "$k: $_v";
        }

        return implode( ", ", $parts );
    }

    /**
     * Columns that must be found in the source, otherwise we'll add errors
     * to the product. Errors may be added in the build_product() method, meaning
     * each subclass must manually add the errors. (@see check_source_columns)
     *
     * Not all expected columns are necessarily required. If the diameter column is missing
     * from the source file, and we silently treat that as an empty string, the product
     * validation will fail anyways, therefore it's not necessary to add diameter to this.
     * Columns such as secondary bolt patterns are different because if the supplier
     * changes the column name, the likely effect is all rims will have no secondary
     * bolt pattern but can still pass product validation like this.
     *
     * @return array
     */
    function get_source_req_cols(){
        return [];
    }

    /**
     * You may want to call this in the build product method for each
     * sync instance.
     *
     * @param $required - @see $this->get_source_req_cols()
     * @param $source
     * @return array|string[]
     */
    function check_source_columns( array $required, array $source ){

        if ( $required ) {
            $missing = self::check_cols( $source, $required );

            if ( $missing ) {
                return [
                    "Source is missing expected columns: " . implode( ", ", $missing )
                ];
            }
        }

        return [];
    }

    /**
     * An array of strings to print in certain places in the back-end.
     *
     * If the file is not fully parsed, I may add info here.
     *
     * @return array
     */
    function get_admin_notes(){
        return [];
    }

    /**
     * Optionally map a column in the source to
     * one or more columns in the resulting product.
     *
     * We can use this to print debugging information in
     * various places. Ie. we can get all values of the Finish
     * column in a supplier file, and for each unique value, see
     * how that maps to color_1, color_2, and finish in the product.
     *
     * @return array
     */
    function source_col_debug_map(){
        return [];
    }

    /**
     * @param Time_Mem_Tracker $mem
     * @return array
     */
    function fetch_api( Time_Mem_Tracker $mem){
        return [];
    }

    /**
     * Fetch and parse data from the supplier (ie. via FTP, API, or in some cases, possibly
     * just from reading a file that we manually put onto our own server).
     *
     * Most Product_Sync sub classes don't need to override this method.
     *
     * @param Time_Mem_Tracker $mem
     * @param bool $build_products
     * @return Product_Sync_Fetch
     * @throws Exception
     */
    function fetch( Time_Mem_Tracker &$mem, $build_products = true ){

        Product_Sync::ini_config();

        if ( $this::FETCH_TYPE === 'local' ) {

            $path = self::LOCAL_DIR . '/' . $this::LOCAL_FILE;

            if ( ! file_exists( $path ) ) {
                return new Product_Sync_Fetch([
                    'source' => [
                        'path' => $path,
                    ],
                    'errors' => [ "Local file does not exist." ],
                ]);
            }

            list( $columns, $data, $error ) = Product_Sync::parse_csv( $path, $this->get_parse_csv_args() );

            $mem->breakpoint("parse_csv");

            $fetch = new Product_Sync_Fetch([
                'source' => [
                    'path' => $path,
                    'file_exists' => file_exists( $path )
                ],
                'columns' => $columns,
                'errors' => $error ? [ $error ] : [],
                'debug' => [],
            ]);

            if ( ! $error ) {

                $fetch->set_source_rows( $data );

                if ( $build_products ) {
                    $fetch->build_products( $this );
                    $mem->breakpoint("build_products");
                }
            }

            return $fetch;

        } else if ( $this::FETCH_TYPE === 'api' ) {

            $source = $this->fetch_api( $mem );

            $err = empty( $source ) ? "API returned no data." : '';

            $mem->breakpoint( 'api_fetch_parse' );

            $fetch = new Product_Sync_Fetch([
                'source' => [
                    'type' => 'api',
                ],
                'columns' => @array_keys( @$source[0] ),
                'errors' => $err ? [ $err ] : [],
                'debug' => [],
            ]);

            $fetch->set_source_rows( $source );

            if ( $build_products ) {
                $fetch->build_products( $this );
                $mem->breakpoint( 'api_build_products' );
            }

            return $fetch;
        } else {

            $ftp = $this->get_ftp_obj();

            if ( $ftp ) {

                $ftp->run();

                $mem->breakpoint("ftp_run");

                if ( $ftp->errors ) {
                    return new Product_Sync_Fetch([
                        'source' => $ftp,
                        'errors' => $ftp->errors,
                    ]);
                }

                list( $columns, $data, $error ) = Product_Sync::parse_csv( $ftp->get_local_full_path(), $this->get_parse_csv_args() );

                $mem->breakpoint("parse_csv");

                $fetch = new Product_Sync_Fetch([
                    'source' => $ftp,
                    'columns' => $columns,
                    'errors' => $error ? [ $error ] : [],
                    'debug' => [],
                ]);

                if ( ! $error ) {

                    $fetch->set_source_rows( $data );

                    if ( $build_products ) {

                        $fetch->build_products( $this );
                        $mem->breakpoint("build_products");
                    }
                }

                return $fetch;

            }

            // default, no fetch type, no ftp
            return new Product_Sync_Fetch([
                'errors' => [ "No source configured." ],
            ]);
        }
    }

    /**
     * @return string[]
     */
    static function get_credentials(){
        return \CW\ProductSync\Auth\get_credentials();
    }

    /**
     * ie. store products onto disk (and insert a corresponding database row
     * into the sync_request table).
     *
     * For some suppliers that don't automate their product files, we may manually
     * store their file on the server, in which case we'll override this method and
     * make it a no-op, then also override the load_product_from_disk method to fetch
     * the products from the same file every time.
     *
     * There's an option to accept all prices because we store the number of differences
     * with the request. So we want to update prices in between fetching the data and
     * calculating the number of differences (so that price differences don't show up
     * if we accept the price changes).
     *
     * @param false $accept_all_prices
     * @return array
     * @throws Exception
     */
    function create_sync_request( $accept_all_prices = false ){

        $sync = $this;
        Product_Sync::ini_config();
        $sync->reset_time_tracker();

        $req_id = DB_Sync_Request::insert([
            'sync_key' => $sync::KEY,
            'type' => $sync::TYPE,
            'supplier' => $sync::SUPPLIER,
            'locale' => $sync::LOCALE,
            'inserted_at' => date( get_database_date_format() ),
            'errors' => '[]',
            'debug' => '{}',
        ]);

        $req = DB_Sync_Request::create_instance_via_primary_key( $req_id );

        // need randomness in file names in case database table gets emptied,
        // in that case primary keys would start at zero and we would have dir name conflicts.
        $rand = str_shuffle( substr( uniqid('', true ), 0, 9 ) );
        $k = $sync::KEY;
        $dir_name = "$k-$req_id-$rand";

        $req->update_database_and_re_sync([
            'dir_name' => $dir_name,
        ]);

        // note: $sync->fetch also mutates $sync->tracker
        $fetch = $sync->fetch( $sync->tracker, true );

        // unlink file, we don't need it in this context
        $fetch->cleanup();

        $sync->tracker->breakpoint("fetch_cleanup");

        if ( $fetch->errors ) {
            $req->update_database_and_re_sync([
                'errors' => $req::encode_json_arr( $fetch->errors ),
                'count_all' => 0,
                'count_valid' => 0,
            ]);

            $req->update_debug_via_callback(function($prev) use( $fetch ){
                $prev['fetch_debug'] = $fetch->debug;
            });

            return [ $req, $fetch ];
        }

        $base = LOG_DIR . '/sync-requests/' . $dir_name;
        @mkdir( $base, 0755, true );

        $source = $fetch->get_source_rows();

        // we'll read from this to approve/synchronize the supplier data
        file_put_contents( $base . '/source.json', json_encode( $source, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE ) );

        $sync->tracker->breakpoint( 'write_source' );

        $valid_products = $fetch->get_products( 'valid' );
        $invalid_products = $fetch->get_products( 'invalid' );

        $sync->tracker->breakpoint("valid_invalid");

        // can be kind of expensive
        if ( $accept_all_prices ) {

            // sometimes supplier write files with just a header row and no body rows,
            // in this case, we don't want to update prices, because all products will be marked
            // not sold.
            $count_all = count( $valid_products ) + count( $invalid_products );

            // check slightly higher than zero because some suppliers put random empty rows
            // at the start and end.
            if ( $count_all > 5 ) {
                $price_result = Product_Sync_Update::accept_price_changes( $sync, $valid_products, $invalid_products, $req_id, true, 'create_sync_request' );
                $sync->tracker->breakpoint("accept_price_changes");
                $req->update_debug_via_callback(function($prev) use( $price_result ){
                    $prev['price_result'] = $price_result;
                });
            } else {
                $req->update_debug_via_callback(function($prev) {
                    $prev['price_result'] = [
                        'event' => 'Supplier provided empty file. Price updates were not run.',
                    ];
                });
            }
        }

        file_put_contents( $base . '/time-mem.log', $sync->tracker->display_everything(false) );

        if ( $sync::TYPE === 'tires' ) {

            // valid/invalid products passed by ref and mutated
            list( $ex_products, $ex_brands, $ex_models, $derived_brands, $derived_models )
                = Product_Sync_Compare::compare_tires_all( $valid_products, $invalid_products, $sync->tracker );

            list( $to_delete, $to_mark_not_sold ) = Product_Sync_Compare::get_ex_products_to_delete( $sync::SUPPLIER, $sync::LOCALE, $valid_products, $ex_products );
            $ex_products = null;

            list( $prod_new, $prod_diff, $prod_same ) = Product_Sync_Compare::split_ents( $valid_products );
            list( $brands_new, $brands_diff, $brands_same ) = Product_Sync_Compare::split_ents( $derived_brands );
            list( $models_new, $models_diff, $models_same ) = Product_Sync_Compare::split_ents( $derived_models );

            $sync->tracker->breakpoint("compare_ex");

            $count_changes = array_sum( array_map( 'count', [
                $prod_new,
                $prod_diff,
                $to_delete,
                $brands_new,
                $brands_diff,
                $models_new,
                $models_diff,
            ] ) );

            $req->update_database_and_re_sync([
                'count_all' => count( $source ),
                'count_valid' => count( $valid_products ),
                'count_changes' => $count_changes,
                'prod_new' => count( $prod_new ),
                'prod_diff' => count( $prod_diff ),
                'prod_same' => count( $prod_same ),
                'prod_del' => count( $to_delete ),
                'brands_new' => count( $brands_new ),
                'brands_diff' => count( $brands_diff ),
                'brands_same' => count( $brands_same ),
                'models_new' => count( $models_new ),
                'models_diff' => count( $models_diff ),
                'models_same' => count( $models_same ),
                'finishes_new' => 0,
                'finishes_diff' => 0,
                'finishes_same' => 0,
            ]);

        } else {

            // returns a modified $valid_products
            list ( $ex_products, $ex_brands, $ex_models, $ex_finishes, $derived_brands, $derived_models, $derived_finishes )
                = Product_Sync_Compare::compare_rims_all( $valid_products, $invalid_products, $sync->tracker );

            // after above
            list( $to_delete, $to_mark_not_sold ) = Product_Sync_Compare::get_ex_products_to_delete( $sync::SUPPLIER, $sync::LOCALE, $valid_products, $ex_products );
            $ex_products = null;

            list( $prod_new, $prod_diff, $prod_same ) = Product_Sync_Compare::split_ents( $valid_products );
            list( $brands_new, $brands_diff, $brands_same ) = Product_Sync_Compare::split_ents( $derived_brands );
            list( $models_new, $models_diff, $models_same ) = Product_Sync_Compare::split_ents( $derived_models );
            list( $finishes_new, $finishes_diff, $finishes_same ) = Product_Sync_Compare::split_ents( $derived_finishes );

            $sync->tracker->breakpoint("compare_ex");

            // the count of changes that we care about...
            // when this is greater than zero, we may alert
            // a site admin that changes are needed
            $count_changes = array_sum( array_map( 'count', [
                $prod_new,
                $prod_diff,
                $to_delete,
                $brands_new,
                $brands_diff,
                $models_new,
                $models_diff,
                $finishes_new,
                $finishes_diff
            ] ) );

            $req->update_database_and_re_sync([
                'count_all' => count( $source ),
                'count_valid' => count( $valid_products ),
                'count_changes' => $count_changes,
                'prod_new' => count( $prod_new ),
                'prod_diff' => count( $prod_diff ),
                'prod_same' => count( $prod_same ),
                'prod_del' => count( $to_delete ),
                'brands_new' => count( $brands_new ),
                'brands_diff' => count( $brands_diff ),
                'brands_same' => count( $brands_same ),
                'models_new' => count( $models_new ),
                'models_diff' => count( $models_diff ),
                'models_same' => count( $models_same ),
                'finishes_new' => count( $finishes_new ),
                'finishes_diff' => count( $finishes_diff ),
                'finishes_same' => count( $finishes_same ),
            ]);
        }

        $this->tracker->breakpoint('sync_request_done');

        $req->update_database_and_re_sync([
            'total_time' => Product_Sync::time_mem_total_time( $sync->tracker )[0],
            'peak_mem' => Product_Sync::time_mem_total_time( $sync->tracker )[1],
        ]);

        $req->update_debug_via_callback(function($prev) use( $fetch, $sync ){
            $prev['fetch_debug'] = $fetch->debug;
            $prev['time_mem'] = Product_Sync::time_mem_summary( $sync->tracker );
            return $prev;
        });

        return [ $req, $fetch ];
    }

    /**
     * Fetches the array of products stored onto our local server.
     *
     * Before the products were stored, they were probably validated and their effective
     * prices were calculated. Effective prices depend on the state of the database.
     *
     * After you load from disk, you probably want to re-calculated effective prices,
     * as price rules may have changed. You then need to either re-validate the entire
     * product (which ensures the effective price is valid) or simply filter/check each
     * product's effective price. Re-validation is tricky because rules of validation live in the code,
     * and the code may have changed since the file was initially parsed (in some cases, it's not clear
     * whether its best to re-validate). Previously valid products can become invalid if price rules
     * were, for example, changed from cost to msrp, and some products don't have an msrp.
     *
     * Also, I'm not sure yet whether we will store all products or only valid products onto disk.
     *
     * We may also decide to store and read from the source data, and re-parse the source data
     * instead of reading from the previously inserted products which resulted from the source data.
     *
     * Furthermore, whatever we do is likely to change over time. So that makes things even more fun
     * (/impossible to do correctly, and simply).
     *
     * @param $request_id - optional, since some files may always live only on disk.
     * @return array|mixed
     * @throws Exception
     */
    function load_products_from_disk( $request_id = false ) {

        Product_Sync::ini_config();

        $this->tracker->breakpoint("load_from_disk_start" );

        $req = $request_id ? DB_Sync_Request::create_instance_via_primary_key( $request_id ) : false;

        if ( $req ) {

            $dir = $req->get( 'dir_name' );

            if ( $this::KEY !== $req->get( 'sync_key' ) ) {
                $k = $this::KEY;
                $k2 = $req->get( 'sync_key' );
                $r = intval( $request_id );
                log_data("Keys don't match ($k) ($k2) ($r)", 'product-sync-key-mismatch.log' );
                return [];
            }

            $source_path = LOG_DIR . '/sync-requests/' . $dir . '/source.json';
            // $products_path = LOG_DIR . '/sync-requests/' . $dir . '/valid-products.json';

            if ( file_exists( $source_path ) ) {
                $source = json_decode( file_get_contents( $source_path ), JSON_INVALID_UTF8_SUBSTITUTE );

                $this->tracker->breakpoint("json_decode_source" );

                if ( json_last_error() || ! is_array( $source )) {
                    $m = json_last_error_msg();
                    $t = gettype( $source );
                    log_data("JSON decode error: $source_path ($m) ($t)", 'product-sync-decode-source.log' );
                }

                $all_price_rules = Product_Sync_Compare::get_cached_indexed_price_rules();

                $valid = [];
                $invalid = [];

                foreach ( $source as $source_key => $row ) {

                    list( $product, $errors ) = $this->build_product_etc( $row, $all_price_rules );
                    $product['__meta']['errors'] = $errors;

                    // might only do this when loading products from disk....
                    $product['__errors'] = implode( ", ", $errors );

                    $source[$source_key] = null;

                    if ( $errors ) {
                        $invalid[] = $product;
                    } else{
                        $valid[] = $product;
                    }
                }

                $this->tracker->breakpoint("build_products_etc" );

                return [ $valid, $invalid ];
            }
        }

        return [ [], [] ];
    }

    /**
     * Since some files are very large, we have to allow only paying attention
     * to certain columns, and also specifying a row filter function which will
     * omit some rows before they ever get stored into PHP memory. Luckily PHP
     * handles parsing CSV files efficiently, so they can have any number of rows
     * with basically the same memory consumption, until of course you decide to
     * store that row into an array.
     *
     * @return array
     */
    function get_parse_csv_args(){
//        $eg = [
//            'columns' => [ 'col1', 'brand' ],
//            'filter' => function( $row ) {
//                return $row['brand'] === 'HI';
//            }
//        ];
        return [];
    }

    /**
     * @param $path
     * @param array $args
     * @return array
     */
    static function parse_csv( $path, $args = [] ) {

        $handle = fopen( $path, 'r' );

        if ( ! $handle ) {
            fclose( $handle );
            return [ [], [], "Could not open file." ];
        }

        if ( isset( $args['columns'] ) ) {

            $csv_columns = fgetcsv( $handle, 50000, "," );
            $args_columns = $args['columns'];

            // ie. [ 2 => 'sku', 4 => 'some other column' ]
            // numeric indexes not necesarily (or likely) auto incrementing
            $columns = array_filter( $csv_columns, function( $col ) use( $args_columns ) {
                return in_array( $col, $args_columns );
            } );
        } else {
            // the first row is the columns
            $columns = fgetcsv( $handle, 50000, "," );
            $columns = array_map( 'trim', $columns );
        }

        if ( isset( $args['columns_omit'] ) ) {
            foreach ( $columns as $c1 => $c2 ) {
                if ( in_array( $c2, $args['columns_omit'] ) ) {
                    unset( $columns[$c1] );
                }
            }
        }

        // optional callback. Can return true, false, or an array
        // which is the resulting row (ie. you can also "map" the row).
        $filter_row = @$args['filter_row'];

        // optional callback
        // using this seems to make things 30% slower or more
        $map_cell = @$args['map_cell'];

        $count = 0;

        // sometimes there are many blank lines, so set some very high limit
        $limit = 500000;

        // an array of indexed arrays
        $body = [];

        do {
            $count++;
            $row = fgetcsv( $handle, 0, "," );

            if ( $row === false ) {
                break;
            }

            $_row = [];

            foreach ( $columns as $index => $col ) {
                if ( $map_cell ) {
                    $_row[$col] = $map_cell( @$row[$index] );
                } else {
                    $_row[$col] = trim( @$row[$index] );
                }
            }

            // filter callback was provided
            if ( $filter_row ) {
                $result = $filter_row( $_row );

                if ( $result === true ) {
                    $body[] = $_row;
                } else if ( is_array( $result ) ) {
                    $body[] = $result;
                } else {
                    // omit the row
                }
            } else {
                $body[] = $_row;
            }

        } while( $count <= $limit );

        // might not need to log this, but curious if it ever ends up happening.
        if ( $count >= $limit ) {
            log_data( [
                'file' => $path,
                'columns' => $columns,
                'count' => $count,
            ], "csv-limit-reached" );
        }

        // the body rows contain the columns as indexes, however, returning
        // the columns here could be important for validating the file (ie. checking
        // that certain columns exist)
        return [ $columns, $body, '' ];
    }

    /**
     * @param array $product
     * @param $price_rule
     * @return array
     */
    function get_effective_price( array $product, $price_rule ) {

        if ( $price_rule && is_array( $price_rule )) {

            $msrp = @$product['msrp'];
            $cost = @$product['cost'];
            $map_price = @$product['map_price'];

            list( $price, $price_err, $effective_type ) = \PS\PriceRules\apply_price_rule( $price_rule, $msrp, $cost, $map_price );

            // might not be necessary to check this, i'm not sure.
            if ( ! $price || $price < 2 ) {
                $price = '';
                $price_err = "Invalid Price (zero)";
            }

            return [ $price, $price_err, $effective_type ];
        }

        return [ '', "No price rules found", '' ];
    }

    /**
     * @param $product
     * @param $index
     */
    function format_product_price_index( &$product, $index ) {

        $val = isset( $product[$index] ) ? $product[$index] : null;

        if ( $val && is_numeric( $val ) && $val > 0 ) {
            $product[$index] = number_format( round( $val, 2 ), 2, '.', '' );
        }
    }

    /**
     * Call after $this->build_product(), and before $this->validate_product()
     *
     * Sub classes will all implement build_product, but likely not
     * this method.
     *
     * @param $product
     * @return array
     */
    function apply_global_product_rules( $product ) {

        if ( ! isset( $product['__meta']['errors'] ) ) {
            $products['__meta']['errors'] = [];
        }

        $product = array_map( function( $val ) {
            return is_scalar( $val ) ? trim( $val ) : $val;
        }, $product );

        $_brand = strtolower( $product['brand'] );

        // before setting up brand slug
        if ( $this::TYPE === 'rims' ) {
            if ( isset( self::$rim_brand_rename[$_brand] ) ) {
                $product['brand'] = self::$rim_brand_rename[$_brand];
            }
        }

        // before setting up brand slug
        if ( $this::TYPE === 'tires' ) {
            if ( isset( self::$tire_brand_rename[$_brand] ) ) {
                $product['brand'] = self::$tire_brand_rename[$_brand];
            }
        }

        if ( ! isset( $product['supplier'] ) ) {
            $product['supplier'] = $this::SUPPLIER;
        }

        if ( ! isset( $product['locale'] ) ) {
            $product['locale'] = $this::LOCALE;
        }

        if ( ! isset( $product['brand_slug'] ) ) {
            $product['brand_slug'] = make_slug( @$product['brand'] );
        }

        if ( ! isset( $product['model_slug'] ) ) {
            $product['model_slug'] = make_slug( @$product['model'] );
        }

        if ( ! isset( $product['effective_price'] ) ) {
            $product['effective_price'] = '';
        }

        self::format_product_price_index( $product, 'cost' );
        self::format_product_price_index( $product, 'msrp' );
        self::format_product_price_index( $product, 'map_price' );
        self::format_product_price_index( $product, 'effective_price' );

        if ( $this::TYPE === 'tires' ) {

            // trim all columns
            foreach ( $product as $k => $v ) {
                $product[$k] = is_scalar( $v ) ? trim( $v ) : $v;

                // all boolean false values should be empty string.
                // note that trim( 0 ) === "0" and "0" is false-like.
                // is_zr has to be inserted to database as 0 or 1, but we'll
                // take care of that later. For now we'll work with true or ""
                if ( ! $product[$k] ) {
                    $product[$k] = '';
                }
            }

            // has to be after certain things
            $product = gp_array_sort_by_keys( $product, self::get_tire_req_cols( true ) );

        } else {

            // before sort
            if ( ! isset( $product['color_1_slug'] ) ) {
                $product['color_1_slug'] = make_slug( @$product['color_1'] );
            }

            if ( ! isset( $product['color_2_slug'] ) ) {
                $product['color_2_slug'] = make_slug( @$product['color_2'] );
            }

            if ( ! isset( $product['finish_slug'] ) ) {
                $product['finish_slug'] = make_slug( @$product['finish'] );
            }

            // trim all columns
            foreach ( $product as $k => $v ) {
                $product[$k] = is_scalar( $v ) ? trim( $v ) : $v;

                // all boolean false values except offset should
                // prefer empty string. note that trim( 0 ) === "0"
                // and "0" is false-like (and an offset of 0 is valid)
                if ( ! in_array( $k, [ 'offset' ] ) ) {
                    if ( ! $product[$k] ) {
                        $product[$k] = '';
                    }
                }
            }

            // has to be after certain things
            $product = gp_array_sort_by_keys( $product, self::get_rim_req_cols( true ) );
        }

        return $product;
    }

    /**
     * Call $this->apply_global_product_rules() on $product first.
     *
     * Since computing the effective price depends on price rules being setup
     * in the database, we have an option of whether or not to validate it. Sometimes,
     * you'll want to compute and validate that at a later time.
     *
     * @param $product
     * @param $check_effective_price
     * @return array|mixed
     */
    function validate_product( $product, $check_effective_price ) {
        if ( $this::TYPE === 'tires' ) {
            return self::validate_tire( $product, $check_effective_price );
        } else {
            return self::validate_rim( $product, $check_effective_price );
        }
    }

    /**
     * @param $price
     * @return bool|string
     */
    static function get_price_err( $price ) {
        if ( ! $price ) {
            return "No price.";
        }
        if ( $price < 0 ) {
            return "Price less than 0";
        }

        list( $valid, $err ) = self::check_decimal_str( $price );

        if ( ! $valid ) {
            return "Formatting error: " . $err;
        }

        return true;
    }

    /**
     * @param $price
     * @return bool
     */
    static function check_effective_price( $price ){
        return $price && $price > 10 && self::check_decimal_str( $price )[0];
    }

    /**
     * @param $price
     * @param int $min
     * @param string $default
     * @return mixed|string
     */
    static function format_price( $price, $min = 0, $default = '' ) {
        if ( is_numeric( $price ) && $price > $min ) {
            // not even sure if this is safe against rounding errors
            return @number_format( $price, 2, '.', '' );
        } else {
            return $default;
        }
    }

    /**
     * returns columns that are missing in subject
     *
     * @param $subject
     * @param $req
     * @return array
     */
    static function check_cols( $subject, $req ) {
        $missing = [];

        foreach ( $req as $col ) {
            if ( ! array_key_exists( $col, $subject ) ) {
                $missing[] = $col;
            }
        }

        return $missing;
    }

    /**
     * Every build_product() method must return an array with at least these keys.
     *
     * This also determines the order that columns are displayed in tables.
     *
     * @param false $order_instead
     * @return string[]
     */
    static function get_tire_req_cols( $order_instead = false ){

        return array_values( array_filter( [
            'supplier',
            'locale',
            'upc',
            'part_number',
            'brand',
            'brand_slug',
            'model',
            'model_slug',
            'type',
            'class',
            'category',
            'image',
            'size',
            'width',
            'profile',
            'diameter',
            'load_index_1',
            'load_index_2',
            'speed_rating',
            'is_zr',
            'extra_load',
            'tire_sizing_system',
            'map_price',
            'msrp',
            'cost',
            $order_instead ? 'effective_price' : '',
            'stock',
            'discontinued',
        ]));
    }

    /**
     * Use validate_product(). Do not call this directly.
     *
     * @param array $tire
     * @param $check_effective_price
     * @return array
     */
    static function validate_tire( array $tire, $check_effective_price ) {

        // the build product function can add its own errors if it
        // needs to do so on a per-file basis. If you want to not do
        // this, unset the index beforehand.
        if ( @$tire['__meta']['errors'] ) {
            $errors = $tire['__meta']['errors'];
        } else {
            $errors = [];
        }

        // if cols are missing this would indicate a dev error.
        // note that tire can be empty and we won't get to here.
        $missing_cols = self::check_cols( $tire, self::get_tire_req_cols( false ) );

        // missing columns indicates a code error in the build_product method
        if ( $missing_cols ) {
            $errors[] = "Tire is missing some columns: " . implode( ", ", $missing_cols );
            return [ $tire, $errors ];
        }

        if ( ! $tire['part_number'] ) {
            $errors[] = 'No part number';
            return [ $tire, $errors ];
        }

        if ( ! $tire['model'] ) {
            $errors[] = 'No model.';
        }

        if ( ! self::check_int( $tire['diameter'], 1, 99 ) ) {
            $errors[] = 'Invalid diameter.';
        }

        if ( ! self::check_int( $tire['width'], 1, 999 ) ) {
            $errors[] = 'Invalid width.';
        }

        if ( ! self::check_int( $tire['profile'], 1, 999 ) ) {

            // this is valid apparently: LT30X9.50R15C
            // profile 9.50
            if ( ! self::check_decimal_str( $tire['profile'] ) ) {
                $errors[] = 'Invalid profile.';
            }
        }

        if ( ! $tire['size'] ) {
            $errors[] = 'Size should not be empty.';
        }

        if ( ! self::check_int( $tire['load_index_1'], 1, 300 ) ) {
            $errors[] = 'Invalid load index.';
        }

        if ( $tire['load_index_2'] && ! self::check_int( $tire['load_index_2'], 1, 300 ) ) {
            $errors[] = 'Invalid load index 2.';
        }

        // not sure about this
        if ( strlen( $tire['speed_rating'] ) > 1 ) {
            $errors[] = 'Speed rating not recognized (more than 1 letter).';
        }

        // not sure about this. We may or may not want to allow values other than "" and "XL"
        if ( $tire['extra_load'] && $tire['extra_load'] !== 'XL' ) {
            $errors[] = "Extra load error. Expected XL or nothing.";
        }

        // indicates a developer error
        if ( $tire['is_zr'] && strval( $tire['is_zr'] ) !== '1' ) {
            $errors[] = "Incorrectly formatted is_zr";
        }

        if ( ! in_array( $tire['tire_sizing_system'], [ 'metric', 'lt-metric'] ) ) {
            $errors[] = "Expected metric or lt-metric for tire sizing system.";
        }

        if ( $check_effective_price && ! self::check_effective_price( $tire['effective_price'] ) ) {
            $errors[] = "Effective price could not be calculated.";
        }

        return [ $tire, $errors ];
    }

    /**
     * Every build_product() method must return an array with at least these keys.
     *
     * This also determines the order that columns are displayed in tables.
     *
     * @param false $order_instead
     * @return array
     */
    static function get_rim_req_cols( $order_instead = false ){
        return array_values( array_filter( [
            'supplier',
            'locale',
            'part_number',
            'upc',
            'type',
            'style',
            'brand_slug',
            'brand',
            'model_slug',
            'model',
            'color_1_slug',
            'color_1',
            'color_2_slug',
            'color_2',
            'finish_slug',
            'finish',
            'width',
            'diameter',
            'bolt_pattern_1',
            'bolt_pattern_2',
            'seat_type',
            'offset',
            'center_bore',
            'image',
            'map_price',
            'msrp',
            'cost',
            $order_instead ? 'effective_price' : '',
            'stock',
            'discontinued',
        ]));
    }

    /**
     * @param array $rim
     * @param $check_effective_price
     * @return array
     */
    static function validate_rim( array $rim, $check_effective_price ) {

        if ( @$rim['__meta']['errors'] ) {
            $errors = $rim['__meta']['errors'];
        } else {
            $errors = [];
        }

        $_rim = $rim;
        unset( $_rim['__meta'] );
        unset( $_rim['effective_price'] );
        if ( empty( $_rim ) ) {
            // $wheel['__meta']['errors'] may have more info.
            $errors[] = "Wheel has no data";
            return [ $rim, $errors ];
        }

        $missing_cols = self::check_cols( $rim, self::get_rim_req_cols( false ) );

        // missing columns indicates a code error in the build_product method
        if ( $missing_cols ) {
            $errors[] = "Wheel is missing some columns: " . implode( ", ", $missing_cols );
            return [ $rim, $errors ];
        }

        $req_non_empty = [
            'part_number',
            'brand',
            'model',
            'color_1',
        ];

        // check strlen to ensure a value exists (so that offset 0 is valid)
        foreach ( $req_non_empty as $col ) {
            if ( strlen( $rim[$col] ) === 0 ) {
                $errors[] = "$col cannot be empty";
            }
        }

        // dev error most likely
        if ( ! in_array( $rim['type'], [ 'steel', 'alloy' ] ) ) {
            $errors[] = "Expected steel or alloy for type";
        }

        // dev error most likely
        if ( ! in_array( $rim['style'], [ '', 'replica'] ) ) {
            $errors[] = "Expected replica or nothing for style";
        }

        // can be like 6.5
        if ( is_numeric( $rim['width'] ) ) {
            $rim['width'] = round( $rim['width'], 2 );
        } else {
            $errors[] = 'Invalid width (non numeric)';
        }

        // I think these are always integers...
        // but round doesn't let things end in .00 (it will convert to int)
        if ( is_numeric( $rim['diameter'] ) ) {
            $rim['diameter'] = round( $rim['diameter'], 2 );
        } else {
            $errors[] = 'Invalid diameter (non numeric)';
        }

        if ( is_numeric( $rim['center_bore'] ) ) {
            // converts 6.00 to 6, 6.10 to 6.1 etc.
            $rim['center_bore'] = round( $rim['center_bore'], 2 );
        } else {
            $errors[] = "Invalid center bore (non numeric)";
        }

        if ( is_numeric( $rim['offset'] ) ) {
            // converts 6.00 to 6, 6.10 to 6.1 etc.
            $rim['offset'] = round( $rim['offset'], 2 );
        } else {
            $errors[] = "Invalid offset (non numeric)";
        }

        list( $b1, $b1_err ) = self::check_bolt_pattern( $rim['bolt_pattern_1'], false );
        list( $b2, $b2_err ) = self::check_bolt_pattern( $rim['bolt_pattern_2'], true );

        if ( $b1_err ) {
            $errors[] = "Bolt Pattern 1 Error: " . $b1_err;
        } else {
            $rim['bolt_pattern_1'] = $b1;
        }

        if ( $b2_err ) {
            $errors[] = "Bolt Pattern 2 Error: " . $b1_err;
        } else {
            $rim['bolt_pattern_2'] = $b2;
        }

        if ( $check_effective_price && ! self::check_effective_price( $rim['effective_price'] ) ) {
            $errors[] = "Effective price could not be calculated.";
        }

        return [ $rim, $errors ];
    }

    /**
     * helper to replace array_map and array_filter which gets
     * really verbose when you have to use both at the same time.
     *
     * @param $input
     * @param $callback
     * @param bool $filter
     * @param false $pass_key
     * @return array
     */
    static function reduce( $input, $callback, $filter = true, $pass_key = false ) {

        $ret = [];

        foreach ( $input as $key => $value ) {

            if ( $pass_key ) {
                $result = $callback( $value, $key );
            } else {
                $result = $callback( $value );
            }

            if ( $filter && ! $result ) {
                continue;
            }

            $ret[$key] = $result;
        }

        return $ret;
    }

    /**
     * @param $columns
     * @param $data
     * @return array
     */
    static function get_frequencies( $columns, $data ) {

        $ret = [];

        // basically array_count_values but works properly with non integer numbers
        $countVals = function( $arr ) {

            $counts = [];

            foreach ( $arr as $key => $val ) {
                $v = strval( $val );
                if ( ! isset( $counts[$v] ) ) {
                    $counts[$v] = 1;
                } else {
                    $counts[$v]++;
                }
            }

            return $counts;
        };

        foreach ( $columns as $col ) {
            $colVals = @array_column( $data, $col );
            $ret[$col] = $countVals( $colVals );
        }

        return $ret;
    }

    /**
     *
     * @param $str
     * @return string
     */
    static function parse_speed_rating($str){
        // we could whitelist the options maybe but for now this seems to work fine
        // given the data in the supplier files.
        return strtoupper( $str );
    }

    /**
     * @param $str
     * @return bool
     */
    static function true_like_str( $str ) {
        return in_array( strtolower( $str ), [ '1', 'yes', 'true' ] );
    }

    /**
     * @param $str
     * @return bool
     */
    static function false_like_str( $str ) {
        return in_array( strtolower( $str ), [ '', '0', 'no', 'false' ] );
    }

    /**
     * @param $str
     * @return bool
     */
    static function is_extra_load( $str ) {
        return in_array( strtolower( $str ), [ 'xl' ] );
    }

    /**
     * ie. 225/50ZR16 => true
     * @param $sizeStr
     * @return bool
     */
    static function is_zr_size( $sizeStr ) {
        return strpos( strtolower( $sizeStr ), 'zr' ) !== false;
    }

    /**
     * Allows strings consisting of only digits.
     *
     * ie. "15" or 15 will have the same result.
     *
     * @param $val
     * @param null $min
     * @param null $max
     * @return bool
     */
    static function check_int( $val, $min = null, $max = null ) {

        $val = ltrim( $val, '-' );

        // true if string contains just digits (misleading fn name)
        if ( gp_is_integer( $val ) ) {
            if ( $min !== null && $val < $min ) {
                return false;
            }

            if ( $max !== null && $val > $max ) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Valid: 0, 0.0, .50, 0.00, 124213.1239238238123, 123, -123, -0 (maybe), -0.5 etc.
     *
     * Not valid: 0.0-, 0.0.0, 02.5, 111., +50 etc.
     *
     * In words, valid if: we have 1 or zero dots, otherwise, nothing but digits
     * and a possible leading - (to represent a negative number). Does not care
     * about the number of digits before or after the dot. For values
     * less than 1, the leading zero is probably optional.
     *
     * @param $in
     * @return array
     */
    static function check_decimal_str( $in ) {

        if ( strlen( $in ) === 0 ) {
            return [ true, '' ];
        }

        // leading - is always allowed
        $in = ltrim( $in, '-' );

        if ( strlen( $in ) === 0 ) {
            return [ false, 'string is empty or only contains -' ];
        }

        if ( $in === '.' ) {
            return [ false, "string contains only a dot (or -.)" ];
        }

        // if contains anything other than digits or .
        if ( preg_match( '/[^\d.]/', $in ) ) {
            return [ false, 'contains non digits or non dots' ];
        }

        $arr = explode( '.', $in );

        if ( count( $arr ) > 2 ) {
            return [ false, 'contains more than 1 dot' ];
        }

        // if $in contains only digits (then explode will give us arr of length 1)
        if ( count( $arr ) === 1 ) {

            // 0 is allowed, but not 00, or 01, or 059123
            if ( strpos( $arr[0], '0' ) === 0 && strlen( $arr[0] ) > 1 ) {
                return [ false, 'non decimal, non zero value, has leading zero that should not be present' ];
            }
        }

        if ( count( $arr ) === 2 ) {

            // 0.5 is allowed.
            // 02.5 means you should go kys
            if ( strpos( $arr[0], '0' ) === 0 && strlen( $arr[0] ) > 1 ) {
                return [ false, 'decimal value has leading zero that should not be present' ];
            }

            if ( strlen( $arr[1] ) === 0 ){
                return [ false, "A decimal is given but after the decimal contains no digits" ];
            }
        }

        return [ true, '' ];
    }

    /**
     * "100.0000" => 100
     *
     * @param $str
     * @return int
     */
    static function str_decimal_to_int( $str ) {
        return intval( round( $str, 0 ) );
    }

    /**
     * @param $str
     * @return false|string[]
     */
    static function parse_load_index($str){

        foreach ( [ '/', '-', ',' ] as $sep ) {
            $arr = explode( $sep, $str );
            if ( count($arr) > 1 ) {
                break;
            }
        }

        // not sure if this happens (maybe if $str is empty?)
        if ( count( $arr ) === 0 ) {
            $arr = [ '', '' ];
        }

        if ( count( $arr ) === 1 ) {
            $arr[] = '';
        }

        $arr = array_map( function( $load_index ) {
            return self::str_decimal_to_int( trim( $load_index ) );
        }, $arr );

        return array_slice( $arr, 0, 2 );
    }

    /**
     * Example inputs: 103T XL, 10/E 121/119, 120/116R, 104Y, 87H
     *
     * @param $str
     * @return array - [ load index 1 (or null), load index 2 (or null)]
     */
    static function parse_dai_load_indexes( $str ) {

        $matches = [];

        // ie. contains 2 or 3 digits followed by "/" and 2 or 3 digits.
        $fuck = preg_match( '/([\d]{2,3})\/([\d]{2,3})/', $str, $matches );

        if ( $fuck ) {
            return [ $matches[1], $matches[2] ];
        }

        // starts with 3 digits
        if ( preg_match( '/^[\d]{3}/', $str ) ) {
            return [ substr( $str, 0, 3 ), null ];
        }

        // starts with 2 digits
        if ( preg_match( '/^[\d]{2}/', $str ) ) {
            return [ substr( $str, 0, 2 ), null ];
        }

        return [ null, null ];
    }

    /**
     * Filters and validates a bolt pattern.
     *
     * Use the resulting bolt pattern only if error message is empty.
     *
     * @param $str
     * @param $allow_empty
     * @return array - resulting bolt pattern, possible error message
     */
    static function check_bolt_pattern( $str, $allow_empty ) {

        $str = trim( strtolower( $str ) );

        if ( ! $str ) {
            if ( $allow_empty ) {
                return [ '', '' ];
            } else {
                return [ '', 'Value is empty.' ];
            }
        }

        $arr = explode( 'x', $str );

        if ( count( $arr ) === 2 && is_numeric( $arr[0] ) && is_numeric( $arr[1] ) ) {
            return [ $str, '' ];
        }

        return [ '', 'Invalid bolt pattern (' . gp_test_input( $str ) . ')' ];
    }

    /**
     * @param $width
     * @param $profile
     * @param $diameter
     * @param $is_zr
     * @return string
     */
    static function build_tire_size_str( $width, $profile, $diameter, $is_zr = false ){

        if ( $is_zr ) {
            return $width . '/' . $profile . 'ZR' . $diameter;
        }

        return $width . '/' . $profile . 'R' . $diameter;
    }

    /**
     * Helper to convert array of objects to array of arrays.
     *
     * @param $query
     * @param $params
     * @return array[]
     */
    static function get_results( $query, $params = []) {
        $db = get_database_instance();
        $rows = $db->get_results( $query, $params );
        return array_map( function( $row ) {
            return (array) $row;
        }, $rows );
    }

    /**
     * @param Time_Mem_Tracker $mem
     * @return array
     */
    static function time_mem_total_time( Time_Mem_Tracker $mem ) {

        $breakpoints = $mem->breakpoints;
        $last = array_pop( $breakpoints );

        return [
            number_format( $last['delta_time_total'], 8, '.', '' ),
            $mem::_format_mem( $last['peak_mem'], false ),
        ];
    }

    /**
     * For storing into database possibly, json encoded.
     *
     * @param Time_Mem_Tracker $mem
     * @return array
     */
    static function time_mem_summary( Time_Mem_Tracker $mem ) {

        $ret = [];

        foreach ( $mem->breakpoints as $bp ) {

            $items = [
                $bp['desc'],
                number_format( $bp['delta_time_total'], 8, '.', '' ),
                $mem::_format_mem( $bp['peak_mem'], false ),
            ];

            $ret[] = implode( ', ', $items );
        }

        return $ret;
    }

    /**
     * Might just run this in a few different places on page load
     * in the admin section. When we add new product sync instances to
     * the code, we need those suppliers to get automatically inserted
     * because the supplier has to exist before the price rules can be added,
     * and price rules have to be added before products can be synchronized,
     * because without a supplier price rule, effective prices cannot be calculated,
     * so the products will have errors and cannot be inserted.
     */
    static function ensure_suppliers_exist(){

        // there are very few suppliers in the db.
        $suppliers = Product_Sync_Compare::get_ex_suppliers();

        foreach ( Product_Sync::get_instances() as $sync ) {

            if ( ! isset( $suppliers[$sync::SUPPLIER] ) ) {
                DB_Supplier::insert( [
                    'supplier_slug' => $sync::SUPPLIER,
                    'supplier_name' => $sync::SUPPLIER,
                ] );
            }
        }
    }

    /**
     * @param $values
     * @return array
     */
    static function frequencies( $values ){
        // array count values doesn't do well with floats
        $values = array_map( 'trim', $values );

        $ret = array_count_values( $values );

        asort( $ret );
        return array_reverse( $ret );
    }

    /**
     * @param array $target
     * @param array $keys
     * @return array
     */
    static function filter_keys( array $target, array $keys ) {
        $ret = [];

        // faster if there are many keys (ie. if keys are part numbers,
        // and target is many products).
        $flipped = array_flip( $keys );

        foreach ( $target as $key => $value ) {
            if ( isset( $flipped[$key] ) ) {
                $ret[$key] = $value;
            }
        }
        return $ret;
    }

    /**
     * @return array
     */
    function get_ex_products(){
        $this->assertions();
        if ( $this::TYPE === 'tires' ) {
            return Product_Sync_Compare::get_ex_tires('', false );
        } else {
            return Product_Sync_Compare::get_ex_rims('', false );
        }
    }

    /**
     * If two suppliers sell the same part numbers, then we may want to
     * add some priorities below. The default priority will probably be zero.
     * Higher priority will be able to own the product. If priorities are not
     * set up, it just means one sync will change suppliers for certain part
     * numbers, and then another sync will likely change it back. But then there
     * are also manual product imports, so, things could get confusing.
     *
     * @param $for_tires
     * @return int[]
     */
    static function get_supplier_priorities( $for_tires ){

        if ( $for_tires ) {
            // dt is known to have the same products as other tire suppliers.
            // 50 is just some arbitrary value that's greater than 0 (or the
            // priority for cda tire, and possibly dynamic tire).
            return [
                'dt' => 50,
            ];
        } else {
            return [];
        }
    }

    /**
     * @return int
     */
    function get_priority(){
        $ps = Product_Sync::get_supplier_priorities( $this::TYPE === 'tires' );
        return (int) @$ps[$this::SUPPLIER];
    }

    /**
     * @return string
     */
    function get_admin_title(){
        return implode( " ", [
            $this::SUPPLIER,
            $this::TYPE,
            $this::LOCALE,
        ]);
    }

    /**
     * Not necesarily all valid speed rating, but we can choose to whitelist
     * this for files that provide unexpected things in the speed ratings column,
     * like decimal numbers for example.
     *
     * @return string[]
     */
    static function get_valid_speed_ratings(){
        return [
            'L',
            'M',
            'N',
            'P',
            'Q',
            'R',
            'S',
            'T',
            'U',
            'H',
            'V',
            'W',
            'Y',
        ];
    }
}

