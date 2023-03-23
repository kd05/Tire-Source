<?php

// category code
//P- Passenger
//MISC- Not sure what this is, we should probably put these as "Passenger"
//LT- Light Truck
//MT- Mud Tire
//IND- Industrial (not concerned about this since there are only 12

// sub category code ?
//4S- All Season
//OE- Original Equipment size (OEM)
//W- Winter
//AW- All Weather
//UHP- Ultra High Performance
//4SH- All Season Highway Terrain
//Steer- ?
//Traction- ?
//Misc- Not sure what these are
//Tubes- Tires with tubes inside (I would ignore these since they are old school)
//Rec-?
//IND- Industrial (again, not concerned about these)


class Product_Sync_CDA_Tire_CA extends Product_Sync {

    const KEY = 'cda_tire_ca';
    const TYPE = 'tires';
    const SUPPLIER = 'canada-tire';
    const LOCALE = 'CA';

    /**
     * We fetch most data from a static local file, but combine it
     * with some data from an inventory file which gets updated daily.
     *
     * For sake of convenience we use the 'api' fetch type, so we can
     * just define the fetch_api method to get and combine the data
     * from 2 sources.
     */
    const FETCH_TYPE = 'api';
    const LOCAL_FILE = 'wheelsforlessexportcsvMay132022.csv';

    const CRON_FETCH = true;
    const CRON_PRICES = true;
    const CRON_EMAIL = false;

    /**
     * We can get up to date prices from here, while the other
     * data likely comes from a static file sitting on the server.
     *
     * @var null
     */
    private static $inventory_file_data = null;

    /**
     * @return array
     */
    function get_source_req_cols() {
        return [
            'Status',
            'Sellable',
            'Purchaseable',
            'Is Winter',
            'Name',
            'UPC/EAN',
        ];
    }

    /**
     * @return array
     */
    function get_admin_notes(){
        $name = self::LOCAL_FILE;
        return [
            "File partially parsed but should not be synced yet. A static file contains most data ($name), with cost/msrp pulled from the inventory file which is updated regularly.",
        ];
    }

    /**
     * @return array|Closure[]
     */
    function get_parse_csv_args() {
        return [
            // remove some invalid utf-8 chars.
            // i guess the csv was in windows152 or smth, idk.
            // this makes the csv parsing take twice as long, but its still only about 1s, so
            // it's fine.
            'map_cell' => function( $cell ) {
                // silence this: "Notice: iconv(): Detected an incomplete multibyte character in input string"
                return @iconv("utf-8", "utf-8//ignore", trim( $cell ) );
            }
        ];
    }

    /**
     * @param $sub_cat
     * @return array - [ allow/include tire, model type ]
     */
    static function get_model_type( $sub_cat ) {

        if ( $sub_cat === '4S' ) {
            return [ true, 'all-season' ];
        }

        if ( $sub_cat === '4SH' ) {
            return [ true, 'all-season' ];
        }

        if ( $sub_cat === 'AW' ) {
            return [ true, 'all-weather' ];
        }

        if ( $sub_cat === 'W' ) {
            return [ true, 'winter' ];
        }

        if ( $sub_cat === 'WSL' ) {
            return [ true, 'winter' ];
        }

        return [ false, '' ];
    }

    /**
     * @param $cat
     * @return array
     */
    static function get_model_class( $cat ) {

        if ( $cat === 'P' ) {
            return [ true, 'passenger' ];
        }

        if ( $cat === 'MISC' ) {
            return [ true, 'passenger' ];
        }

        if ( $cat === 'LT' ) {
            return [ true, 'light-truck' ];
        }

        if ( $cat === 'MT' ) {
            return [ true, 'mud-tire' ];
        }

        return [ false, '' ];
    }

    /**
     * @param $cat
     * @param $sub_cat
     * @return array
     */
    static function get_model_category( $cat, $sub_cat ){

        if ( $sub_cat === 'UHP' ) {
            return [ true, 'ultra-high-performace' ];
        }

        if ( $sub_cat === '4SH' ) {
            return [ true, 'highway-terrain' ];
        }

        if ( $cat === 'MT' ) {
            return [ true, 'mud' ];
        }

        // include tires even if category is empty
        return [ true, '' ];
    }

    /**
     * Expensive... will fetch file over ftp and parse it.
     *
     * @return null
     */
    static function get_inventory_file_data( Time_Mem_Tracker $tracker ){
        if ( self::$inventory_file_data === null ) {

            $tracker->breakpoint('inv_file_before_ftp');

            $ftp = Supplier_Inventory_Supplier::cda_tire_universal_ftp_config();
            $ftp->run();

            $tracker->breakpoint('inv_file_after_ftp');

            list( $columns, $data, $error ) = Product_Sync::parse_csv( $ftp->get_local_full_path(), [
                'sku', 'msrp', 'customer_price', 'qty'
            ] );

            if ( $error ) {
                $tracker->breakpoint('inv_file_error: ' . $error );
                self::$inventory_file_data = [];
            } else {
                $tracker->breakpoint('inv_file_parsed');

                self::$inventory_file_data = Product_Sync_Compare::index_by( $data, function( $row ) {
                    return $row['sku'];
                } );
            }
        }

        return self::$inventory_file_data;
    }

    /**
     * @param Time_Mem_Tracker $mem
     * @return array
     */
    function fetch_api( Time_Mem_Tracker $mem){

        $mem->breakpoint('static_file_before');

        $path = self::LOCAL_DIR . '/' . self::LOCAL_FILE;
        list( $columns, $data, $error ) = Product_Sync::parse_csv( $path, $this->get_parse_csv_args() );

        $mem->breakpoint('static_file_count_' . count( $data ));

        if ( $error ) {
            return [];
        }

        $inv = self::get_inventory_file_data( $mem );

        $mem->breakpoint('inv_file_count_' . count( $data ));

        // if we can't get up to date prices, I would rather return no data at all
        if ( empty( $inv ) ) {
            return [];
        }

        $count_match = 0;

        foreach ( $data as $index => $row ) {

            $inv_row = @$inv[@$row['Code']];
            if ( $inv_row ) {
                $inv_cost = @$inv_row['customer_price'];
                $static_cost = $row['Customer Cost'];
                $diff = round( $inv_cost - $static_cost, 2 );
                $count_match++;
                // useful for debugging cols
                $data[$index]['inv_file__has_row'] = true;
                $data[$index]['inv_file__missing_part_number'] = '';
                $data[$index]['inv_file__cost_diff'] = $inv_cost && $static_cost ? $diff : '';
                // necessary cols
                $data[$index]['inv_file__customer_price'] = @$inv_row['customer_price'];
                $data[$index]['inv_file__msrp'] = @$inv_row['msrp'];
            } else {
                $data[$index]['inv_file__has_row'] = false;
                $data[$index]['inv_file__missing_part_number'] = @$row['Code'];
                $data[$index]['inv_file__cost_diff'] = '';
                $data[$index]['inv_file__customer_price'] = '';
                $data[$index]['inv_file__msrp'] = '';
            }
        }

        // ie. how many part numbers in the static file had part numbers in the dynamic one
        $mem->breakpoint('count_matches_' . $count_match);

        return $data;
    }

    /**
     * @param array $row
     * @return array
     */
    function build_product( array $row ) {

        $errors = $this->check_source_columns( $this->get_source_req_cols(), $row );

        $part_number = @$row['Code'];

        $brand = @$row['Brand'];
        $model = @$row['Model'];

        $active = @$row['Status'] === 'ACTIVE';
        $sellable = @$row['Sellable'] === 'T';
        $purchasable = @$row['Purchaseable'] === 'T';
        $is_winter = @$row['Is Winter'] === 'T';

        $desc_before_brand = @explode( $brand, @$row['Name'] )[0];

        $is_xl = strpos( $desc_before_brand, "XL" ) !== false;

        // models seem to all start with the brand name
        $model = trim( gp_strip_prefix( $brand, $model ) );

        if ( ! $active ) {
            $errors[] = "Not Active";
        }

        if ( ! $sellable ) {
            $errors[] = "Not Sellable";
        }

        if ( ! $purchasable ) {
            $errors[] = "Not Purchaseable";
        }

        $cat = @$row['Performance Category'];
        $sub_cat = @$row['Performance Sub Category'];
        list( $model_type_valid, $model_type ) = self::get_model_type( $sub_cat );
        list( $model_class_valid, $model_class ) = self::get_model_class( $cat );
        list( $model_category_valid, $model_category ) = self::get_model_category( $cat, $sub_cat );
        $is_lt = $model_class === 'light-truck';

        if ( ! $model_type_valid ) {
            $errors[] = "Cannot determine tire model type";
        }

        if ( ! $model_class_valid ) {
            $errors[] = "Invalid or ignored tire model class";
        }

        if ( ! $model_category_valid ) {
            $errors[] = "Invalid or ignored tire model category";
        }

        $load_indexes = explode( "/", @$row['Load Range'] );

        // seeing a lot of decimal values in here, which we don't want
        // to end up in the database.
        $sr = @$row['Speed Rating'];
        $valid_sr = self::get_valid_speed_ratings();

        if ( ! in_array( $sr, $valid_sr ) ) {
            $errors[] = "Speed rating not valid.";
        }

        $width = @$row['Section Width'];
        $profile = @$row['Sidewall Aspect Ratio'];
        $diameter = @$row['Rim Diameter'];

        $size = self::build_tire_size_str( $width, $profile, $diameter );

        // price updates run for both invalid and valid products,
        // so we have to make sure to omit the cost (and msrp and map if applicable)
        // for any products that shouldn't be sold.

        if ( $sellable && $active && $purchasable ) {
            $cost = @$row['inv_file__customer_price'];
            $msrp = @$row['inv_file__msrp'];
        } else {
            $cost = '';
            $msrp = '';
        }

        return [
            'upc' => @$row['UPC/EAN'],
            'part_number' => $part_number,
            'brand' => $brand,
            'model' => $model,
            'type' => $model_type,
            'class' => $model_class,
            'category' => $model_category,
            'image' => '',
            'size' => $size,
            'width' => $width,
            'profile' => $profile,
            'diameter' => $diameter,
            'load_index_1' => @$load_indexes[0],
            'load_index_2' => @$load_indexes[1],
            'speed_rating' => $sr,
            'is_zr' => false,
            'extra_load' => $is_xl ? 'XL' : '',
            'tire_sizing_system' => $is_lt ? 'lt-metric' : 'metric',
            'map_price' => '',
            'msrp' => $msrp,
            'cost' => $cost,
            'stock' => '',
            'discontinued' => '',
            '__meta' => [
                'errors' => $errors,
            ],
        ];
    }

}
