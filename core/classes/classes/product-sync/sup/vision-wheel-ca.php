<?php

class Product_Sync_Vision_Wheel_CA extends Product_Sync {

    const KEY = 'vision_rims_ca';
    const TYPE = 'rims';
    const SUPPLIER = 'vision-wheels';
    const LOCALE = 'CA';
    const LOCAL_FILE = 'CDP6 specs and pricing.csv';
    const FETCH_TYPE = 'local';

    const CRON_FETCH = true;
    const CRON_PRICES = true;

    /**
     * @return string[]
     */
    static function _source_req_cols(){
        return [
            'Barcode',
        ];
    }

    /**
     * @return array
     */
    function get_source_req_cols() {
        return self::_source_req_cols();
    }

    /**
     * @return array|string[]
     */
    function get_admin_notes(){
        return [
            "Static File (Not automatically updated)"
        ];
    }

    // if brackets provided, we need to use what's in the brackets.
    // 6-5.5 (6-139.7)
    // 8-6.5 (8-165.1)
    // 8-170
    // 5-5 (5-127)
    // 6-135
    // 5-5.5 (5-139.7)
    // 5-4.75 (5-120.65)
    // 8-180
    // 5-4.25 (5-108)
    // 4-110
    // 5-100
    // 4-4.25 (4-108)
    // 5-May
    // 6-6.5 (6-165.1)
    // 6-127
    // Apr-98
    static function parse_vision_bolt_pattern( $str, &$info = [] ){

        $str = trim( $str );

        if ( strpos( $str, '(' ) !== false ) {
            $info[] = 'brackets';
            $str = rtrim( $str, ')' );
            $str = trim( @explode( "(", $str )[1] );
            $info[] = 'inner: ' . $str;
        }

        $str = str_replace( '-', 'x', $str );

        $arr = explode( 'x', $str );

        $info[] = 'arr: ' . implode( ', ', $arr );

        if ( count( $arr ) === 2 && is_numeric( $arr[0] ) && is_numeric( $arr[1] ) ) {
            return $str;
        }

        return '';
    }

    /**
     * @param $finish
     * @return array
     */
    static function parse_vision_finishes( $finish ) {

        $_finish = strtolower( $finish );

        // keys will be case insensitive.
        // they come from the product file.
        // they are too ridiculous to attempt to parse programmatically,
        // so we just hardcode key/value pairs.
        // values below get exploded on comma.
        // from all values in the file, we ommited the ones with colors
        // that represent only a primary color (ie. Black, Gloss Black, etc.)
        $map = [
            'Gloss Black Machined Face' => 'Gloss Black, Machined Face',
            'Gloss Black Milled Spoke' => 'Gloss Black, Milled Spoke',
            'Gunmetal Machined Lip' => 'Gunmetal, Machined Lip',
            'Gloss Black Machined Lip' => 'Gloss Black, Machined Lip',
            'Anthracite with Satin Black Lip' => 'Anthracite, Satin Black Lip',
            'Gunmetal Machined Face' => 'Gunmetal, Machined Face',
            'Red Tint Milled Spoke' => 'Red Tint, Milled Spoke',
            'Winter Paint-Silver( Salt Resistant)' => 'Winter Paint - Silver (Salt Resistant)',
            'Gloss Black Milled Spoke with Red Tint' => 'Gloss Black, Milled Spoke, Red Tint',
            'Matte Black Machined Lip' => 'Matte Black, Machined Lip',
            'Gloss Black Milled Spoke with Black Bolt Inserts' => 'Gloss Black, Milled Spoke, Black Bolt Inserts',
            'Satin Black with Black Bolt Inserts' => 'Satin Black, Black Bolt Inserts',
            'Gloss Black Mirror Machined Face and Lip' => 'Gloss Black, Mirror Machined Face/Lip',
            'Matte Black Machined Face' => 'Matte Black, Machined Face',
            'Matte Black w\Anthracite Spoke Ends' => 'Matte Black, Anthracite Spoke Ends',
            'Gloss Black with Milled Windows' => 'Gloss Black, Milled Windows',
            'Satin Grey Machined Face/Lip' => 'Satin Grey, Machined Face/Lip',
            'Hyper Silver Machined Face' => 'Hyper Silver, Machined Face',
            'Gloss Black with Milled Center' => 'Gloss Black, Milled Center',
            'As-Cast Machined Face Machined Ring/Lip' => 'As-Cast Machined Face, Machined Ring/Lip',
            'Silver Machined Face' => 'Silver, Machined Face',
            'Gloss Black Machined Lip Milled Spoke' => 'Gloss Black, Machined Lip, Milled Spoke',
        ];

        $_map = [];

        foreach ( $map as $k => $v ) {
            $_map[strtolower($k)] = $v;
        }

        if ( isset( $_map[$_finish] ) ) {
            $mapped = $_map[$_finish];
        } else {
            // if the thing isn't found in the map just treat the input as the primary color.
            $mapped = $finish;
        }

        // we cannot allow with or and to be in color_1/color_2/finish or
        // routing will break on click it wheels
        // (ie. /wheels/brand/model/color_1-and-color_2-with-finish)
        $mapped = str_replace( ' with ', ' w/ ', $mapped );
        $mapped = str_replace( ' and ', '/', $mapped );

        return array_map( 'trim', explode(',', $mapped ) );
    }

    /**
     * They put no model column in the file, but have descriptions
     * like this. Kind of ridiculous.
     *
     * 5-108/114.3 GM VIS CROSS
     * 4-100/114.3 CHR VIS CROSS
     * 4-100/114.3 WPS VIS CROSS
     * 5-110 BLK OVAL SNOW WHEEL
     * 5-100/114.3 CHR VIS CROSS
     * 5-105/115 CHR VIS BANE
     * 5-150 ANTH W/SBLIP VIS ROCKER
     * 5-5.5 CHR VIS SULTAN
     * 5-114.3 GBML VIS MANX
     *
     * @param $desc
     * @return string
     */
    static function parse_vision_model( $desc ) {

        $lower = strtolower( $desc );
        $pos_vis = strpos( $lower, 'vis' );
        $pos_vor = strpos( $lower, 'vor' );

        if ( $pos_vis !== false ) {
            $ret = substr( $desc, $pos_vis + 3 );
        } else if ( $pos_vor !== false ) {
            $ret = substr( $desc, $pos_vor + 3 );
        } else {
            return '';
        }

        return trim( $ret );
    }

    /**
     * @param array $row
     * @param $locale
     * @return float|int
     */
    static function get_vision_stock( array $row, $locale ) {

        assert( $locale === 'CA' || $locale === 'US' );

        // from inv file...
        // columns here are a bit diff.
        //INTQNTAL – Inventory Quantity Alabama.
        //INTQNTCA – Inventory Quantity California
        //INTQNTIN – Inventory Quantity Indiana
        //INTQNTTX – Inventory Quantity Texas
        //INTQNTNC – Inventory Quantity North Carolina
        //INTQNTON – Inventory Quantity Ontario

        if ( $locale === 'CA' ) {
            return array_sum( [
                @$row['Qty ON'],
            ] );
        } else {
            return array_sum( [
                @$row['Qty AL'],
                @$row['Qty CA'],
                @$row['Qty IN'],
                @$row['Qty TX'],
                @$row['Qty NC'],
            ] );
        }

    }

    /**
     * @param array $row
     * @param Product_Sync $sync
     * @return array
     */
    static function _build_product( array $row, Product_Sync $sync ) {

        $errors = $sync->check_source_columns( $sync->get_source_req_cols(), $row );

        // ie. 20X9
        $size = @$row['Size'];
        $size_arr = explode( 'x', strtolower( $size ) );

        $brand = @$row['Brand'];

        if ( $brand === 'VIS-VIS' ) {
            $brand = 'Vision';
        } else if ( $brand === 'VIS-VOR' ) {
            $brand = 'Vision Off Road';
        } else if ( $brand === 'VIS-ATV' ) {
            $brand = 'Vision ATV';
        } else if ( $brand === 'VIS-HD' ) {
            $brand = 'Heavy Duty';
        } else if ( $brand === 'VIS-AM' ) {
            $brand = 'American Muscle';
        } else {
            $errors[] = "Brand is not one of the allowed brands";
        }

        $finish_arr = self::parse_vision_finishes( @$row['Finish'] );

        $stock = self::get_vision_stock( $row, $sync::LOCALE );
        $discontinued = self::true_like_str( @$row['Discontinued']);

        if ( $stock === 0 && $discontinued ) {
            $errors[] = "Stock 0 and discontinued.";
        }

        // rims cols
        return [
            'upc' => @$row['Barcode'],
            'part_number' => @$row['PartNo'],
            'brand' => $brand,
            'model' => self::parse_vision_model( @$row['Description']),
            'color_1' => @$finish_arr[0],
            'color_2' => @$finish_arr[1],
            'finish' => @$finish_arr[2],
            'type' => 'alloy',
            'width' => @$size_arr[1],
            'diameter' => @$size_arr[0],
            'bolt_pattern_1' => self::parse_vision_bolt_pattern( @$row['Bolt Pattern1']),
            'bolt_pattern_2' => self::parse_vision_bolt_pattern( @$row['Bolt Pattern2']),
            'seat_type' => '',
            'offset' => @$row['Offset'],
            'center_bore' => @$row['Bore'],
            'style' => '',
            'image' => @$row['XL Pic'],
            'map_price' => '',
            'msrp' => @$row['CAD MSRP'],
            'cost' => @$row['Price'],
            'stock' => $stock,
            'discontinued' => $discontinued,
            '__meta' => [
                'errors' => $errors,
            ],
        ];
    }

    /**
     * @param array $row
     * @return array
     */
    function build_product( array $row ) {
        return self::_build_product( $row, $this );
    }
}
