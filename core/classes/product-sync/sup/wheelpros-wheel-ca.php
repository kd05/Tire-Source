<?php

/**
 * Split wheel pros into 2 instances because there are 45k+ products
 * in the file, which causes issues sometimes.
 *
 * One instance will whitelist the brands, the other will use all brands.
 *
 * In order to keep memory low, we filter out certain rows when parsing
 * the CSV. @see get_parse_csv_args. This tricks the code into thinking
 * that there exists two mutually exclusive files, even though they actually
 * originate from the same file.
 *
 * Class Product_Sync_Wheelpros_Wheel_CA_1
 */
class Product_Sync_Wheelpros_Wheel_CA_1 extends Product_Sync {

    const KEY = 'wheelpros_rims_ca_1';
    const TYPE = 'rims';
    const SUPPLIER = 'wheelpros-1';
    const LOCALE = 'CA';

    const IS_WHEELPROS_PRIMARY = true;

    // not working well on either site (file too large)
    const CRON_FETCH = false;
    const CRON_PRICES = false;
    const CRON_EMAIL = false;

    /**
     * @var array|null
     */
    private static $inv_price_data;

    /**
     * There is about 50ish brands. These are the ones with the most
     * products, so it accounts for about half the file.
     */
    const WHEELPROS_PRIMARY_BRANDS = [
        'Fuel 1PC',
        'Black Rhino',
        'TSW',
        'XD',
        'Moto Metal',
        // 'American Racing Forged',
        'KMC'
    ];

    /**
     * Read the separate FTP file that contains inventory and price data.
     * We need this in addition to the primary file that contains all the product data.
     * This is expensive on first call (fetches/parses a 15-30mb file), but then returns a cached
     * result.
     *
     * @param null|Time_Mem_Tracker $time_mem_tracker
     * @return array|null
     */
    static function get_inv_price_data( $time_mem_tracker = null ){

        if ( self::$inv_price_data !== null ) {
            return self::$inv_price_data;
        }

        $ftp                   = new FTP_Get_Csv();
        $ftp->method           = 'sftp';
        $ftp->host             = '44.234.227.42';
        $ftp->username         = 'click_it_wheels';
        $ftp->password         = self::get_credentials()['wheelpros'];
        $ftp->remote_file_name = 'CommonFeed/CAD/WHEEL/wheelInvPriceData.csv';
        $ftp->run();

        if ( $time_mem_tracker ) {
            $time_mem_tracker->breakpoint('wp_inv_ftp');
        }

        list( $columns, $data, $error ) = Product_Sync::parse_csv( $ftp->get_local_full_path(), [
            'columns' => [ 'PartNumber', '4033', '4035' ],
        ] );

        if ( $time_mem_tracker ) {
            $time_mem_tracker->breakpoint('wp_inv_parse_' . count( $data ));
        }

        if ( $error ) {
            log_data( $error, 'wheelpros-inv-price-data-err.log' );
        }

        if ( $data ) {

            $_data = Product_Sync_Compare::index_by( $data, function( $row ){
                return $row['PartNumber'];
            } );

            self::$inv_price_data = $_data;
            return $_data;
        }

        // we dont want this to be null after attempting the fetch
        self::$inv_price_data = [];
        return [];
    }

    /**
     * @return FTP_Get_Csv|null
     */
    function get_ftp_obj() {
        $ftp                   = new FTP_Get_Csv();
        $ftp->method           = 'sftp';
        $ftp->host             = '44.234.227.42';
        $ftp->username         = 'click_it_wheels';
        $ftp->password         = self::get_credentials()['wheelpros'];
        $ftp->remote_file_name = 'TechFeed/WHEEL/Wheel_TechGuide.csv';
        return $ftp;
    }

    /**
     * @return array
     */
    function get_parse_csv_args(){

        return [
            'columns' => [
                'sku',
                'upc',
                'brand_desc',
                'brand_cd',
                'style',
                'display_style_no',
                // 'product_desc',
                'size_desc',
                'steel_wheel',
                'diameter',
                'width',
                'bolt_pattern_metric',
                'offset',
                'centerbore',
                'load_rating_metric',
                'fancy_finish_desc',
                'division',
                'source_country',
                // 'bolt_pattern_mm1',
                'msrp',
                'map_price',
                'image_url',
            ],
            'filter_row' => function( $row ) {

                $brand = $row['brand_desc'];

                if ( IS_WFL ) {
                    $price_data = self::get_inv_price_data( $this->tracker );

                    $inv_data = @$price_data[$row['sku']];

                    $row['stock_langley'] = @$inv_data['4033'];
                    $row['stock_toronto'] = @$inv_data['4035'];
                } else {
                    $row['stock_langley'] = null;
                    $row['stock_toronto'] = null;
                }

                if ( $this::IS_WHEELPROS_PRIMARY ) {
                    return in_array( $brand, self::WHEELPROS_PRIMARY_BRANDS ) ? $row : false;
                } else {
                    return ! in_array( $brand, self::WHEELPROS_PRIMARY_BRANDS ) ? $row : false;
                }
            }
        ];

    }

    /**
     * @return array|string[]
     */
    function get_admin_notes(){

        if ( $this::IS_WHEELPROS_PRIMARY ) {
            $note = 'Only these brands: ' . implode( ', ', $this::WHEELPROS_PRIMARY_BRANDS );
        } else{
            $note = 'All brands except: ' . implode( ', ', $this::WHEELPROS_PRIMARY_BRANDS );
        }

        $notes = [ $note ];

        return $notes;
    }

    /**
     * @return array
     */
    function get_source_req_cols() {
        return [
            'steel_wheel',
        ];
    }

    /**
     * @param $in
     * @return array
     */
    static function parse_wheelpros_finish( $in ) {

        // order matters (in the case of W/ and /)
        $separators = [ " WITH ", " W/ ", " AND ", " & ", "/" ];

        $_in = $in;

        foreach ( $separators as $index => $sep ) {
            $_in = str_replace( $sep, "__sep123__", $_in );
        }

        $parts = explode( "__sep123__", $_in );
        $parts = array_map( 'trim', $parts );
        $part0 = $parts[0];
        $part1 = @$parts[1];

        // add all remaining parts with & in between (in case there are more than 1, which there might not be)
        $part2 = implode( " & ", array_slice( $parts, 2, 100 ) );

        return [ $part0, $part1, $part2 ];
    }

    /**
     * Get the model name from the style column, possibly removing
     * the brand/model code prefix.
     *
     * @param $model - style column
     * @param $brand_code - brand_cd in column
     * @param $model_code - display_style_no column
     * @return string
     */
    static function get_model( $model, $brand_code, $model_code ){

        // sometimes there's a prefix containing some combination of the
        // brand code and model number (and in a very inconsistent format).
        // we might want to remove this.
        // ie. "AB-125 Orion" instead of just "Orion", and there may be
        // a brand and model code column which may or may not be AB, and 125,
        // and the prefix may or may not have a dash in between, or even a space
        // afterwards, before the "main" model name.
        $model = trim( $model );

        // try stripping "AB-125" or "AB125", but only if something else
        // remains after the prefix.
        if ( $brand_code && $model_code ) {

            $prefixes = [
                $brand_code . ' ' . $model_code,
                $brand_code . $model_code,
                $brand_code . '-' . $model_code,
            ];

            foreach ( $prefixes as $prefix ) {

                $_model = trim( gp_strip_prefix( $prefix, $model ) );

                if ( $_model && $_model !== $model ) {
                    return $_model;
                }
            }
        }

        return $model;
    }

    /**
     * @param array $row
     * @return array
     */
    function build_product( array $row ) {

        $errors = $this->check_source_columns( $this->get_source_req_cols(), $row );

        $colors = self::parse_wheelpros_finish( @$row['fancy_finish_desc'] );

        $bolt_patterns = explode( "/", @$row['bolt_pattern_metric'] );

        if ( IS_WFL ) {
            $stock = $row['stock_langley'] + $row['stock_toronto'];

            if ( $stock < 1 ) {
                $errors[] = "Out of stock in CA (wheelpros)";
            }
        } else {
            $stock = null;
        }

        return [
            'upc' => @$row['upc'],
            'part_number' => @$row['sku'],
            'brand' => @$row['brand_desc'],
            'model' => self::get_model( @$row['style'], @$row['brand_cd'], @$row['display_style_no'] ),
            'color_1' => @$colors[0],
            'color_2' => @$colors[1],
            'finish' => @$colors[2],
            'type' => self::true_like_str( @$row['steel_wheel'] ) ? 'steel' : 'alloy',
            'width' => @$row['width'],
            'diameter' => @$row['diameter'],
            'bolt_pattern_1' => @$bolt_patterns[0],
            'bolt_pattern_2' => @$bolt_patterns[1],
            'seat_type' => @$row[''],
            'offset' => @$row['offset'],
            'center_bore' => @$row['centerbore'],
            'style' => '',
            'image' => @$row['image_url'],
            'map_price' => @$row['map_price'],
            'msrp' => @$row['msrp'],
            'cost' => '',
            'stock' => $stock,
            'discontinued' => null,
            '__meta' => [
                'errors' => $errors,
            ],
        ];
    }
}

class Product_Sync_Wheelpros_Wheel_CA_2 extends Product_Sync_Wheelpros_Wheel_CA_1{
    const KEY = 'wheelpros_rims_ca_2';
    const TYPE = 'rims';
    const SUPPLIER = 'wheelpros-2';
    const LOCALE = 'CA';

    const IS_WHEELPROS_PRIMARY = false;

    // not working well on either site (file too large)
    const CRON_FETCH = false;
    const CRON_PRICES = false;
    const CRON_EMAIL = false;
}
