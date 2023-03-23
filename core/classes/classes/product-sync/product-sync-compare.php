<?php

// from product to db_product (change price cols etc.)
// discontinued vs. not found in file, vs. found in file but invalid????
// determine brands/models/finishes from products
// compare new vs. existing
class Product_Sync_Compare{

    // this value sometimes just gets inlined, so you can't change
    // it only here.
    const KEY_SEP = "##";

    static $indexed_price_rule_cache;

    /**
     * @param $rows
     * @param $fn
     * @param bool $use_first - if multiple of the same index exists, do we want to include
     * the first usage of it or the last? Ie. this occurs if a part number is listed twice
     * within a file (and we're indexing by part number).
     * @return array
     */
    static function index_by( $rows, $fn, $use_first = true ) {

        $ret = [];

        foreach ( $rows as $k => $v ) {

            $_k = $fn($v);

            if ( $use_first ) {
                if ( ! isset( $ret[$_k] ) ) {
                    $ret[$_k] = $v;
                }
            } else {
                // use last...
                // just override the value if it already exists
                $ret[$_k] = $v;
            }
        }
        return $ret;
    }

    /**
     * @return array[]
     */
    static function get_all_price_rules(){
        return self::index_price_rules( Product_Sync::get_results( 'select * from price_rules' ) );
    }

    /**
     * @param $rules
     * @return array
     */
    static function index_price_rules( $rules ){
        return self::index_by( $rules, function ( $rule ) {
            return implode( "##", [
                $rule['type'],
                $rule['locale'],
                $rule['supplier'],
                $rule['brand'],
                $rule['model'],
            ]);
        } );
    }

    /**
     *
     */
    static function setup_cache_indexed_price_rules(){
        if ( self::$indexed_price_rule_cache === null ) {
            self::$indexed_price_rule_cache = self::get_all_price_rules();
        }
    }

    /**
     * @return array|null
     */
    static function get_cached_indexed_price_rules(){
        self::setup_cache_indexed_price_rules();
        return self::$indexed_price_rule_cache;
    }

    /**
     * Gets an array of 0-3 price rules, in order of priority
     * (ie. supplier/brand/model, supplier/brand, or just supplier).
     *
     * If no matching price rules are found then we can't determine the effective
     * price and the product will probably be considered invalid.
     *
     * @param $all_price_rules - ie. all rules from database, properly indexed
     * @param $type
     * @param $locale
     * @param $supplier
     * @param $brand
     * @param $model
     * @return array|false[]
     */
    static function get_product_price_rules( $all_price_rules, $type, $locale, $supplier, $brand, $model ){

        assert( $type === 'tires' || $type === 'rims' );
        assert( $locale === 'CA' || $locale === 'US' );

        $model_key = implode( "##", [ $type, $locale, $supplier, $brand, $model ] );
        $brand_key = implode( "##", [ $type, $locale, $supplier, $brand, '' ] );
        $supplier_key = implode( "##", [ $type, $locale, $supplier, '', '' ] );

        // multiple rules can match. Order is very important. The first existing rule
        // will (likely) be used.
        $rules = [
            $supplier && $brand && $model ? @$all_price_rules[$model_key] : false,
            $supplier && $brand ? @$all_price_rules[$brand_key] : false,
            $supplier ? @$all_price_rules[$supplier_key] : false,
        ];

        return array_values( array_filter( $rules ) );
    }

    /**
     * Returns empty string for false-like or invalid inputs, otherwise,
     * a string with 2 digits (possibly zeroes) after a decimal place.
     *
     * @param $in
     * @return int|string
     */
    static function format_price( $in ) {

        if ( ! $in || $in === '0' ) {
            return '';
        }

        $ret = number_format( floatval( $in ), 2, '.', '' );
        return $ret === '0.00' ? '' : $ret;
    }

    /**
     * @return array
     */
    static function get_ex_suppliers() {
        $suppliers = Product_Sync::get_results( 'select * from suppliers ORDER BY supplier_id DESC' );

        return self::index_by( $suppliers, function ( $s ) {

            return $s[ 'supplier_slug' ];
        } );
    }

    /**
     * With 25k rims, all rims, full cols === about 250mb memory (a lot).
     *
     * Also, we might end up having 60k rims.
     *
     * @param string $supplier
     * @param false $full_cols
     * @return array
     */
    static function get_ex_rims( $supplier = '', $full_cols = false ){

        $params = [];

        // we usually only use full cols if a supplier is specified (or not at all)
        // using full cols for all products generally takes up way too much memory.
        if ( $full_cols ) {
            $cols = "rims.*, b.rim_brand_name, m.rim_model_name, f.color_1_name, f.color_2_name, f.finish_name, f.image_source_new ";
        } else {
            $cols = "
            rims.rim_id,
            rims.upc,
            rims.part_number,
            rims.supplier,
            rims.type,
            rims.style,
            # rims.brand_id,
            rims.brand_slug,
            # rims.model_id,
            rims.model_slug,
            # rims.finish_id,
            # rims.color_1,
            # rims.color_2,
            # rims.finish,
            rims.size,
            rims.width,
            rims.diameter,
            rims.bolt_pattern_1,
            rims.bolt_pattern_2,
            rims.seat_type,
            rims.offset,
            rims.center_bore,
            # rims.import_name,
            # rims.import_date,
            rims.msrp_ca,
            rims.cost_ca,
            rims.map_price_ca,
            rims.price_ca,
            rims.sold_in_ca,
            rims.stock_amt_ca,
            # rims.stock_sold_ca,
            # rims.stock_unlimited_ca,
            # rims.stock_discontinued_ca,
            # rims.stock_update_id_ca,
            rims.msrp_us,
            rims.cost_us,
            rims.map_price_us,
            rims.price_us,
            rims.sold_in_us,
            rims.stock_amt_us,
            # rims.stock_sold_us,
            # rims.stock_unlimited_us,
            # rims.stock_discontinued_us,
            # rims.stock_update_id_us,
            # rims.sync_id_insert_ca,
            # rims.sync_date_insert_ca,
            # rims.sync_id_update_ca,
            # rims.sync_date_update_ca,
            # rims.sync_id_insert_us,
            # rims.sync_date_insert_us,
            # rims.sync_id_update_us,
            # rims.sync_date_update_us,
            # b.rim_brand_name,
            b.rim_brand_slug,
            # m.rim_model_name,
            m.rim_model_slug,               
            # f.rim_finish_id,
            # f.model_id,
            f.color_1,
            f.color_2,
            f.finish,
            # f.color_1_name,
            # f.color_2_name,
            # f.finish_name,
            # f.image_local,
            # f.image_source,
            # f.rim_finish_inserted_at,
            f.image_source_new
            ";
        }

        // note: the query runs very fast (~ .3s), but takes up
        // a ton of memory of using all columns and all suppliers
        $q = "
        SELECT $cols FROM rims            
        INNER JOIN rim_finishes f ON f.rim_finish_id = rims.finish_id
        INNER JOIN rim_models m ON m.rim_model_id = f.model_id
        INNER JOIN rim_brands b ON m.rim_brand_id = b.rim_brand_id
        ";

        if ( $supplier ) {
            $q .= " WHERE supplier = :supplier ";
            $params[] = [ 'supplier', $supplier ];
        }

        $rows = Product_Sync::get_results( $q, $params );

        return self::index_by( $rows, function( $row ) {
            return $row['part_number'];
        });
    }

    /**
     * @param string $supplier
     * @param $full_cols
     * @return array
     */
    static function get_ex_tires( $supplier = '', $full_cols = false ){

        Product_Sync::ini_config();

        $params = [];

        // we usually only use full cols if a supplier is specified (or not at all)
        // using full cols for all products generally takes up way too much memory.
        if ( $full_cols ) {
            $cols = "tires.*, b.tire_brand_name, m.tire_model_name, m.tire_model_image_new ";
        } else {
            $cols = "
            tires.tire_id,
            tires.part_number,
            tires.supplier,
            # tires.brand_id,
            tires.brand_slug,
            # tires.model_id,
            tires.model_slug,
            tires.size,
            # tires.description,
            tires.width,
            tires.profile,
            tires.diameter,
            tires.load_index,
            tires.load_index_2,
            tires.speed_rating,
            tires.is_zr,
            tires.extra_load,
            # tires.utqg,
            tires.tire_sizing_system,
            # tires.import_name,
            # tires.import_date,
            tires.msrp_ca,
            tires.cost_ca,
            tires.price_ca,
            tires.map_price_ca,
            tires.sold_in_ca,
            tires.stock_amt_ca,
            # tires.stock_sold_ca,
            tires.stock_unlimited_ca,
            tires.stock_discontinued_ca,
            # tires.stock_update_id_ca,
            tires.msrp_us,
            tires.cost_us,
            tires.map_price_us,
            tires.price_us,
            tires.sold_in_us,
            # tires.stock_amt_us,
            # tires.stock_sold_us,
            # tires.stock_unlimited_us,
            # tires.stock_discontinued_us,
            # tires.stock_update_id_us,
            # tires.sync_id_insert_ca,
            # tires.sync_date_insert_ca,
            # tires.sync_id_update_ca,
            # tires.sync_date_update_ca,
            # tires.sync_id_insert_us,
            # tires.sync_date_insert_us,
            # tires.sync_id_update_us,
            # tires.sync_date_update_us,
            # b.tire_brand_name,
            b.tire_brand_slug,
            # m.tire_model_name,
            m.tire_model_slug,
            m.tire_model_image_new            
            ";
        }

        $q = "
        SELECT $cols FROM tires        
        INNER JOIN tire_models m ON m.tire_model_id = tires.model_id
        INNER JOIN tire_brands b ON b.tire_brand_id = tires.brand_id
        ";

        if ( $supplier ) {
            $q .= " WHERE supplier = :supplier ";
            $params[] = [ 'supplier', $supplier ];
        }

        $rows = Product_Sync::get_results( $q, $params );

        return self::index_by( $rows, function( $row ) {
            return $row['part_number'];
        });
    }

    /**
     * @param $supplier
     * @param $locale
     * @param array $new_valid_products - valid products from supplier file
     * @param array $all_ex_products
     * @return array
     */
    static function get_ex_products_to_delete( $supplier, $locale, array $new_valid_products, array $all_ex_products ) {

        assert( (bool) $supplier, "Supplier should not be empty." );
        assert( in_array( $locale, [ 'CA', 'US' ] ) );

        // faster lookup when part numbers are in keys
        $_part_numbers = array_flip( array_column( $new_valid_products, 'part_number' ) );

        $to_delete = [];
        $to_mark_not_sold = [];

        foreach ( $all_ex_products as $prod ) {

            if ( $prod['supplier'] === $supplier ) {

                $part_number = $prod['part_number'];

                $is_in_new_valid_products = isset( $_part_numbers[$part_number] );

                if ( ! $is_in_new_valid_products ) {

                    $sold_in_this_locale = $locale === 'CA' ? (bool) $prod['sold_in_ca'] : (bool) $prod['sold_in_us'];
                    $sold_in_other_locale = $locale === 'CA' ? (bool) $prod['sold_in_us'] : (bool) $prod['sold_in_ca'];

                    if ( $sold_in_this_locale ) {
                        if ( $sold_in_other_locale ) {
                            $prod['__action'] = 'mark_not_sold';
                            $to_mark_not_sold[] = $prod;
                        } else {
                            $prod['__action'] = 'delete';
                            $to_delete[] = $prod;
                        }
                    } else {
                        if ( ! $sold_in_other_locale ) {
                            $prod['__action'] = 'delete';
                            $to_delete[] = $prod;
                        } else {
                            // do nothing, although we expect products to not
                            // end up in this state???? Idk its too confusing.
                        }
                    }
                }
            }
        }

        return [ $to_delete, $to_mark_not_sold ];
    }

    /**
     * @return array
     */
    static function get_ex_rim_brands(){

        $brands = Product_Sync::get_results( 'select * from rim_brands' );

        return self::index_by( $brands, function( $brand ) {
            return $brand['rim_brand_slug'];
        });
    }

    /**
     * @return array
     */
    static function get_ex_rim_models(){

        $q = "
        SELECT * FROM rim_models
        INNER JOIN rim_brands ON rim_models.rim_brand_id = rim_brands.rim_brand_id 
        ";

        $models = Product_Sync::get_results( $q );

        return self::index_by( $models, function( $model ) {
            return $model['rim_brand_slug'] . self::KEY_SEP . $model['rim_model_slug'];
        });
    }

    /**
     * @return array
     */
    static function get_ex_rim_finishes(){

        $q = "
        SELECT * FROM rim_finishes
        INNER JOIN rim_models ON rim_models.rim_model_id = rim_finishes.model_id
        INNER JOIN rim_brands ON rim_models.rim_brand_id = rim_brands.rim_brand_id
        ";

        $finishes = Product_Sync::get_results( $q );

        return self::index_by( $finishes, function( $finish ) {
            return implode( self::KEY_SEP, [
                $finish['rim_brand_slug'],
                $finish['rim_model_slug'],
                $finish['color_1'],
                $finish['color_2'],
                $finish['finish'],
            ] );
        });
    }

    /**
     * @return array
     */
    static function get_ex_tire_brands(){

        $brands = Product_Sync::get_results( 'select * from tire_brands' );

        return self::index_by( $brands, function( $brand ) {
            return $brand['tire_brand_slug'];
        });
    }

    /**
     * @return array
     */
    static function get_ex_tire_models(){

        $q = "
        SELECT * FROM tire_models
        LEFT JOIN tire_brands ON tire_models.tire_brand_id = tire_brands.tire_brand_id 
        ";

        $models = Product_Sync::get_results( $q );

        return self::index_by( $models, function( $model ) {
            return $model['tire_brand_slug'] . self::KEY_SEP . $model['tire_model_slug'];
        });
    }

    /**
     * @param $ents
     * @return array[]
     */
    static function split_ents( $ents ) {
        return [
            self::filter_ents( $ents, 'new' ),
            self::filter_ents( $ents, 'diff' ),
            self::filter_ents( $ents, 'same' ),
        ];
    }

    /**
     * @see split_ents
     *
     * @param $entities - one of the outputs from compare_tires or compare_rims
     * @param $type
     * @return array
     */
    static function filter_ents( $entities, $type ) {

        switch( $type ) {
            case 'new':
                return array_filter( $entities, function( $ent ) {
                    return ! $ent['__ex'];
                } );
            case 'diff':
                return array_filter( $entities, function( $ent ) {
                    return $ent['__diff'] != false;
                } );
            case 'same':
                return array_filter( $entities, function( $ent ) {
                    return $ent['__ex'] && ! $ent['__diff'];
                } );
        }

        return $entities;
    }

    /**
     * @param $ex
     * @param $new
     * @param array $compare_items
     * @return array
     */
    static function diffs_helper( $ex, $new, array $compare_items ) {

        $diffs = [];

        foreach ( $compare_items as $arr ) {
            // new_key comes from build_product
            // ex key comes from database column (often these are different)
            @list( $new_key, $ex_key, $cmp_fn ) = $arr;

            if ( $cmp_fn ) {
                if ( ! $cmp_fn( $new[$new_key], $ex[$ex_key] ) ) {
                    $diffs[$new_key] = $ex[$ex_key];
                }
            } else if ( $new[$new_key] != $ex[$ex_key] ) {
                $diffs[$new_key] = $ex[$ex_key];
            }
        }

        return $diffs;
    }

    /**
     * @param $ex
     * @param $new
     * @return array
     */
    static function cmp_tire_brand( $ex, $new ) {
        return [];
    }

    /**
     * Tire models, unlike rim models, have several more important columns:
     * type, class, and category. They also have run-flat but that's not used on the
     * site anywhere, and it's hard to get from supplier files, so we may just
     * completely ignore it. When it comes to the other 3, supplier files do not seem
     * to have sufficient information to accurately get these values. Therefore,
     * we may only insert these values for new models, and after that, an admin can
     * edit type/class/category from the back-end. Therefore, if a file gives differences
     * for these columns, we likely just ignore it, otherwise it would overwrite changes
     * performed by the admin.
     *
     * @param $ex
     * @param $new
     * @return array
     */
    static function cmp_tire_model( $ex, $new ) {

        $diffs = [];

        if ( $new['image'] && $new['image'] !== $ex['tire_model_image_new'] ) {
            $diffs['image'] = $ex['tire_model_image_new'];
        }

        return $diffs;
    }

    /**
     * @param $ex
     * @param $new
     * @return array
     */
    static function cmp_tire( $ex, $new ) {

        $is_ca = $new['locale'] === 'CA';

        // note that non strict comparison is sufficient
        // to handle prices. ie. 110 == "110.00" is true
        $diffs = self::diffs_helper( $ex, $new, [
            [ 'width', 'width', null ],
            [ 'profile', 'profile', null ],
            [ 'diameter', 'diameter', null ],
            [ 'size', 'size', null ],
            [ 'load_index_1', 'load_index', null ],
            [ 'load_index_2', 'load_index_2', null ],
            [ 'speed_rating', 'speed_rating', null ],
            [ 'tire_sizing_system', 'tire_sizing_system', null ],
            [ 'extra_load', 'extra_load', null ],
            [ 'brand_slug', 'tire_brand_slug', null ],
            [ 'model_slug', 'tire_model_slug', null ],
            [ 'msrp', $is_ca ? 'msrp_ca' : 'msrp_us', null ],
            [ 'cost', $is_ca ? 'cost_ca' : 'cost_us', null ],
            [ 'map_price', $is_ca ? 'map_price_ca' : 'map_price_us', null ],
            [ 'effective_price', $is_ca ? 'price_ca' : 'price_us', null ],
        ] );

        // is an sql boolean column, so using default comparison
        // above results in things like: was: "0" now: ""
        if ( boolval( $ex['is_zr'] ) != boolval( $new['is_zr'] ) ) {
            $diffs['is_zr'] = $ex['is_zr'];
        }

        return $diffs;
    }

    /**
     * @param $ex
     * @param $new
     * @return array<array>
     */
    static function cmp_rim_brand( $ex, $new ) {
        return [];
    }

    /**
     * @param $ex
     * @param $new
     * @return array
     */
    static function cmp_rim_model( $ex, $new ) {
        return [];
    }

    /**
     * @param $ex
     * @param $new
     * @return array
     */
    static function cmp_rim_finish( $ex, $new ) {

        $diffs = [];

        if ( $new['image'] && $new['image'] !== $ex['image_source_new'] ) {
            $diffs['image'] = $ex['image_source_new'];
        }

        return $diffs;
    }

    /**
     * @param $ex
     * @param $new
     * @return array
     */
    static function cmp_rim( $ex, $new ) {

        $is_ca = $new['locale'] === 'CA';

        // note that non strict comparison is sufficient
        // to handle prices. ie. 110 == "110.00" is true
        $diffs = self::diffs_helper( $ex, $new, [
            [ 'upc', 'upc', null ],
            [ 'supplier', 'supplier', null ],
            [ 'type', 'type', null ],
            [ 'style', 'style', null ],
            [ 'brand_slug', 'brand_slug', null ],
            [ 'model_slug', 'model_slug', null ],
            [ 'color_1_slug', 'color_1', null ],
            [ 'color_2_slug', 'color_2', null ],
            [ 'finish_slug', 'finish', null ],
            [ 'width', 'width', null ],
            [ 'diameter', 'diameter', null ],
            [ 'bolt_pattern_1', 'bolt_pattern_1', null ],
            [ 'bolt_pattern_2', 'bolt_pattern_2', null ],
            [ 'seat_type', 'seat_type', null ],
            [ 'offset', 'offset', null ],
            [ 'center_bore', 'center_bore', null ],
            [ 'msrp', $is_ca ? 'msrp_ca' : 'msrp_us', null ],
            [ 'cost', $is_ca ? 'cost_ca' : 'cost_us', null ],
            [ 'map_price', $is_ca ? 'map_price_ca' : 'map_price_us', null ],
            [ 'effective_price', $is_ca ? 'price_ca' : 'price_us', null ],
        ] );

        return $diffs;
    }

    /**
     * @param array $tires
     * @param $ex_brands
     * @return array
     */
    static function compare_tire_brands( array $tires, $ex_brands ){

        $brands = [];

        foreach ( $tires as $tire_key => $tire ) {

            $brand_key = $tire['brand_slug'];

            if ( ! isset( $brands[$brand_key] ) ) {
                $brands[$brand_key] = [
                    '__key' => $brand_key,
                    'slug' => $tire['brand_slug'],
                    'name' => $tire['brand'],
                    '__ex' => false,
                    '__diff' => [],
                ];
            }
        }

        foreach ( $brands as $brand_key => $brand ) {
            if ( isset( $ex_brands[$brand_key] ) ) {
                $brands[$brand_key]['__ex'] = $ex_brands[$brand_key];
                $brands[$brand_key]['__diff'] = self::cmp_tire_brand( $ex_brands[$brand_key], $brand );
            }
        }

        return $brands;
    }

    /**
     * @param array $tires - products from $sync->build_product()
     * @param $ex_models - rows from database
     * @return array
     */
    static function compare_tire_models( array $tires, $ex_models ) {

        $models = [];

        foreach ( $tires as $tire ) {

            $model_key = implode( self::KEY_SEP, [ $tire['brand_slug'], $tire['model_slug'] ] );

            if ( ! isset( $models[$model_key] ) ) {
                $models[$model_key] = [
                    '__key' => $model_key,
                    'brand_slug' => $tire['brand_slug'],
                    'slug' => $tire['model_slug'],
                    'name' => $tire['model'],
                    'type' => $tire['type'],
                    'class' => $tire['class'],
                    'category' => $tire['category'],
                    'image' => $tire['image'],
                    '__ex' => false,
                    '__diff' => [],
                ];
            }

            // get first non empty image
            if ( ! $models[$model_key]['image'] ) {
                $models[$model_key]['image'] = $tire['image'];
            }
        }

        foreach ( $models as $model_key => $model ) {
            if ( isset( $ex_models[$model_key] ) ) {
                $models[$model_key]['__ex'] = $ex_models[$model_key];
                $models[$model_key]['__diff'] = self::cmp_tire_model( $ex_models[$model_key], $model );
            }
        }

        return $models;
    }

    /**
     * @param array $tires
     * @param $ex_tires - get_ex_tires( '', false )
     * @return array[]
     */
    static function compare_tires( array $tires, $ex_tires  ){

        foreach ( $tires as $tire_key => $tire ) {

            $ex_tire = @$ex_tires[$tire['part_number']];

            if ( $ex_tire ) {
                $tires[$tire_key]['__ex'] = $ex_tire;
                $tires[$tire_key]['__diff'] = self::cmp_tire( $ex_tire, $tire );
            } else {
                $tires[$tire_key]['__ex'] = false;
                $tires[$tire_key]['__diff'] = [];
            }
        }

        return self::index_by( $tires, function( $tire ) {
            return $tire['part_number'];
        });
    }

    /**
     * Expensive..
     *
     * Might be some perf or memory benefits to pass by ref here, not sure.
     *
     * @param array $valid_tires
     * @param array $invalid_tires
     * @param $time_mem_tracker
     * @return array
     */
    static function compare_tires_all( array &$valid_tires, array &$invalid_tires, &$time_mem_tracker ) {

        $ex_tires = Product_Sync_Compare::get_ex_tires();
        $ex_brands = Product_Sync_Compare::get_ex_tire_brands();
        $ex_models = Product_Sync_Compare::get_ex_tire_models();

        if ( $time_mem_tracker ) {
            $time_mem_tracker->breakpoint('query_ex');
        }

        $valid_tires = Product_Sync_Compare::compare_tires( $valid_tires, $ex_tires );
        $derived_brands = Product_Sync_Compare::compare_tire_brands( $valid_tires, $ex_brands );
        $derived_models = Product_Sync_Compare::compare_tire_models( $valid_tires, $ex_models );

        // both passed by ref and possibly mutated
        self::check_products_for_supplier_conflicts( $valid_tires, $invalid_tires, 'tires' );

        if ( $time_mem_tracker ) {
            $time_mem_tracker->breakpoint('diffs_etc');
        }

        return [ $ex_tires, $ex_brands, $ex_models, $derived_brands, $derived_models ];
    }

    /**
     * @param array $rims
     * @param $ex_brands
     * @return array
     */
    static function compare_rim_brands( array $rims, $ex_brands ){

        $brands = [];

        foreach ( $rims as $rim ) {

            $brand_key = $rim['brand_slug'];

            if ( ! isset( $brands[$brand_key] ) ) {
                $brands[$brand_key] = [
                    '__key' => $brand_key,
                    'slug' => $rim['brand_slug'],
                    'name' => $rim['brand'],
                    '__ex' => false,
                    '__diff' => [],
                ];
            }
        }

        foreach ( $brands as $brand_key => $brand ) {
            if ( isset( $ex_brands[$brand_key] ) ) {
                $brands[$brand_key]['__ex'] = $ex_brands[$brand_key];
                $brands[$brand_key]['__diff'] = self::cmp_rim_brand( $ex_brands[$brand_key], $brand );
            }
        }

        return $brands;
    }

    /**
     * @param array $rims - products from $sync->build_product()
     * @param $ex_models - rows from database
     * @return array
     */
    static function compare_rim_models( array $rims, $ex_models ) {

        $models = [];

        foreach ( $rims as $rim ) {

            $model_key = implode( self::KEY_SEP, [ $rim['brand_slug'], $rim['model_slug'] ] );

            if ( ! isset( $models[$model_key] ) ) {
                $models[$model_key] = [
                    '__key' => $model_key,
                    'brand_slug' => $rim['brand_slug'],
                    'slug' => $rim['model_slug'],
                    'name' => $rim['model'],
                    '__ex' => false,
                    '__diff' => [],
                ];
            }
        }

        foreach ( $models as $model_key => $model ) {
            if ( isset( $ex_models[$model_key] ) ) {
                $models[$model_key]['__ex'] = $ex_models[$model_key];
                $models[$model_key]['__diff'] = self::cmp_rim_model( $ex_models[$model_key], $model );
            }
        }

        return $models;
    }

    /**
     * @param array $rims - products from $sync->build_product()
     * @param $ex_finishes - rows from database
     * @return array
     */
    static function compare_rim_finishes( array $rims, $ex_finishes ) {

        $finishes = [];

        foreach ( $rims as $rim ) {

            $finish_key = implode( self::KEY_SEP, [
                $rim['brand_slug'],
                $rim['model_slug'],
                $rim['color_1_slug'],
                $rim['color_2_slug'],
                $rim['finish_slug']
            ] );

            if ( ! isset( $finishes[$finish_key] ) ) {
                $finishes[$finish_key] = [
                    '__key' => $finish_key,
                    'brand_slug' => $rim['brand_slug'],
                    'model_slug' => $rim['model_slug'],
                    'color_1_slug' => $rim['color_1_slug'],
                    'color_2_slug' => $rim['color_2_slug'],
                    'finish_slug' => $rim['finish_slug'],
                    'color_1_name' => $rim['color_1'],
                    'color_2_name' => $rim['color_2'],
                    'finish_name' => $rim['finish'],
                    'image' => $rim['image'],
                    '__ex' => false,
                    '__diff' => [],
                ];
            }

            // get first non empty image
            if ( ! $finishes[$finish_key]['image'] ) {
                $finishes[$finish_key]['image'] = $rim['image'];
            }
        }

        foreach ( $finishes as $finish_key => $finish ) {
            if ( isset( $ex_finishes[$finish_key] ) ) {
                $finishes[$finish_key]['__ex'] = $ex_finishes[$finish_key];
                $finishes[$finish_key]['__diff'] = self::cmp_rim_finish( $ex_finishes[$finish_key], $finish );
            }
        }

        return $finishes;
    }

    /**
     * @param array $rims
     * @param $ex_rims - get_ex_rims( '', false )
     * @return array[]
     */
    static function compare_rims( array $rims, $ex_rims  ){

        foreach ( $rims as $key => $rim ) {

            $ex_rim = @$ex_rims[$rim['part_number']];

            if ( $ex_rim ) {
                $rims[$key]['__ex'] = $ex_rim;
                $rims[$key]['__diff'] = self::cmp_rim( $ex_rim, $rim );
            } else {
                $rims[$key]['__ex'] = false;
                $rims[$key]['__diff'] = [];
            }
        }

        return self::index_by( $rims, function( $rim ) {
            return $rim['part_number'];
        });
    }

    /**
     * For each valid product, if the product already exists in the database,
     * and the supplier is different, and the database supplier has higher priority
     * then the valid product's supplier, then we'll move the product from the valid products
     * to the invalid products. The reason we don't do these early on in the product validation
     * functions, is that this requires querying most or all products from the database, which
     * takes up a lot of memory, and I don't want that to be a requirement for parsing the supplier
     * file and building/validating products. So we have to call this function a little later on (and
     * not at all in certain contexts).
     *
     * Valid/Invalid products come from a supplier file (ie. the result of Product_Sync::build_product)
     *
     * Both items passed by ref because this may be faster or less memory intensive (haven't really
     * tested this). But memory is def. an issue on some supplier files.
     *
     * @param $valid_products - an array of "products" that have the "__ex" index set,
     * which represents the product found in the database, if any. From that, we can
     * check the current supplier (used in the database) vs. the new proposed supplier.
     * @param $invalid_products
     * @param $type
     */
    static function check_products_for_supplier_conflicts(&$valid_products, &$invalid_products, $type ) {

        assert( $type === 'tires' || $type === 'rims' );
        $priorities = Product_Sync::get_supplier_priorities( $type === 'tires' );

        // nothing to do if all suppliers have the same priority
        if ( empty( $priorities ) ) {
            return;
        }

        $now_invalid = [];

        foreach ( $valid_products as $index => $rim ) {

            if ( $rim['__ex'] ) {
                $current_supplier = $rim['__ex']['supplier'];
                $proposed_supplier = $rim['supplier'];

                if ( $current_supplier !== $proposed_supplier ) {

                    // often these are both 0. If they are the same, we allow the supplier to get
                    // changed.
                    $current_p = isset( $priorities[$current_supplier] ) ? $priorities[$current_supplier] : 0;
                    $proposed_p = isset( $priorities[$proposed_supplier] ) ? $priorities[$proposed_supplier] : 0;

                    if ( $proposed_p < $current_p ) {

                        unset( $valid_products[$index] );

                        // this is hacky, but invalid products tend to never the __ex or
                        // __diff key, so if we move a product from the valid products
                        // to invalid, then we should unset these keys. This makes table
                        // rendering functions work better because they expect all items
                        // to have the same indexes. (more specifically, the columns to
                        // be rendered in the table are often taken from the first item in
                        // the collection, so we don't want to have tables randomly showing
                        // 2 extra columns sometimes, and sometimes not).
                        unset( $rim['__ex'] );
                        unset( $rim['__diff'] );

                        $p_info = "$proposed_supplier $proposed_p, $current_supplier $current_p";
                        $e = explode( ", ", @$rim['__errors'] );
                        $rim['__errors'] = implode( ", ", array_filter( array_merge( $e, [
                            'Product already exists with a supplier (' . $current_supplier . ') that has higher priority (' . $p_info. ')',
                        ])));

                        $now_invalid[] = $rim;
                    }
                }
            }
        }

        // array values is likely redundant
        $invalid_products = array_values( array_merge( $now_invalid, $invalid_products ) );
    }

    /**
     * Expensive..
     *
     * Might be some perf or memory benefits to pass by ref here, not sure.
     *
     * @param array $valid_rims
     * @param array $invalid_rims
     * @param $time_mem_tracker
     * @return array
     */
    static function compare_rims_all( array &$valid_rims, array &$invalid_rims, &$time_mem_tracker ) {

        $ex_rims = Product_Sync_Compare::get_ex_rims();
        $ex_brands = Product_Sync_Compare::get_ex_rim_brands();
        $ex_models = Product_Sync_Compare::get_ex_rim_models();
        $ex_finishes = Product_Sync_Compare::get_ex_rim_finishes();

        if ( $time_mem_tracker ) {
            $time_mem_tracker->breakpoint('query_ex');
        }

        $valid_rims = Product_Sync_Compare::compare_rims( $valid_rims, $ex_rims );
        $derived_brands = Product_Sync_Compare::compare_rim_brands( $valid_rims, $ex_brands );
        $derived_models = Product_Sync_Compare::compare_rim_models( $valid_rims, $ex_models );
        $derived_finishes = Product_Sync_Compare::compare_rim_finishes( $valid_rims, $ex_finishes );

        // both passed by ref and possibly mutated
        self::check_products_for_supplier_conflicts( $valid_rims, $invalid_rims, 'rims' );

        if ( $time_mem_tracker ) {
            $time_mem_tracker->breakpoint('diffs_etc');
        }

        return [ $ex_rims, $ex_brands, $ex_models, $ex_finishes, $derived_brands, $derived_models, $derived_finishes ];
    }

    /**
     * @param array $entities
     * @param $type
     * @param bool $ex_col
     * @return array|array[]
     */
    static function ents_to_table_rows( array $entities, $type, $ex_col = true ) {

        return array_map( function( $ent ) use( $type, $ex_col ){
            return self::ent_to_table_row( $ent, $type, $ex_col );
        }, $entities );
    }

    /**
     * @param array $entity
     * @param $type
     * @param false $ex_col
     * @return array
     */
    static function ent_to_table_row( array $entity, $type, $ex_col = true ) {

        $ex = $entity['__ex'];
        $diffs = @$entity['__diff'] ? @$entity['__diff'] : [];

        unset( $entity['__meta'] );

        // sanitize all values first.
        // we have to do this because some columns may have HTML added
        $entity = array_map( 'gp_test_input', $entity );

        if ( $ex ) {
            foreach ( $diffs as $key => $prev_value ) {
                $new_value = $entity[$key];

                // new_value was sanitize already, but not prev value
                $op = '';
                $op .= '<div style="color: #e10319; min-width: 125px;">Was: ' . gp_test_input( $prev_value ) . '</div>';
                $op .= '<div style="margin-bottom: 5px;"></div>';
                $op .= '<div style="color: green; min-width: 125px;">Now: ' . $new_value . '</div>';
                $entity[$key] = $op;
            }
        }

        unset( $entity['__ex'] );
        unset( $entity['__diff'] );

        if ( $ex_col ) {
            $entity = array_merge( [
                'exists' => $ex ? 'Yes' : '',
                'different' => $diffs ? 'Yes' : '',
            ], $entity );
        }

        return $entity;
    }

}
