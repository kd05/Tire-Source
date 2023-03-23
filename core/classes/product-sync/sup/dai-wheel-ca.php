<?php

class Product_Sync_DAI_Wheel_CA extends Product_Sync {

    const KEY = 'dai_wheel_ca';
    const TYPE = 'rims';
    const SUPPLIER = 'dai';
    const LOCALE = 'CA';

    const CRON_FETCH = true;
    const CRON_PRICES = true;

    /**
     * @return FTP_Get_Csv|null
     */
    function get_ftp_obj() {
        $ftp                   = new FTP_Get_Csv();
        $ftp->method           = 'sftp';
        $ftp->host             = self::FTP_SERVER;
        $ftp->username         = 'u95793629-dai';
        $ftp->password         = self::get_credentials()['dai'];
        $ftp->remote_file_name = 'DAI_wheel_list.csv';
        return $ftp;
    }

    /**
     * @return string[]
     */
    static function _source_req_cols(){
        return [
            'FINISH EN',
            'MSRP',
            'PRICE',
        ];
    }

    /**
     * @return array|string[]
     */
    function get_source_req_cols(){
        return self::_source_req_cols();
    }

    /**
     * Used for CA and US product syncs
     *
     * @param array $row
     * @param Product_Sync $sync
     * @return array
     */
    static function _build_product( array $row, Product_Sync $sync ) {

        $errors = $sync->check_source_columns( $sync->get_source_req_cols(), $row );

        $brand = @$row['MANUFACTURER'];
        if ( string_ends_with( strtolower( $brand ), ' wheels' ) ){
            $brand = substr( $brand, 0, -1 * strlen( ' wheels' ) );
        }

        if ( strtolower( $brand ) === 'dai' ) {
            $brand = 'DAI Alloys';
        }

        // space is necessary. One finish is: "Black E-Coating"
        $colors = explode( ' - ', @$row['FINISH EN'] );

        // all seem to start with ' for some reason
        // '6921109015617
        $upc = trim( @$row['UPC'], '\'');

        // ie. 20x9.5
        $size = @$row['SIZE'];
        $diameter_width = explode( 'x', $size );

        $bolt_patterns = explode( '/', @$row['BOLT PATTERN'] );

        $discontinued = self::true_like_str( @$row['DISCONTINUED'] );
        $stock = @$row['Stock'];

        if ( $stock == 0 && $discontinued ) {
            $errors[] = "Stock 0 and discontinued.";
        }

        return [
            'supplier' => $sync::SUPPLIER,
            'locale' => $sync::LOCALE,
            'upc' => $upc,
            'part_number' => @$row['CODE'],
            'brand' => $brand,
            'model' => @$row['NAME'],
            'color_1' => trim( @$colors[0] ),
            'color_2' => trim( @$colors[1] ),
            'finish' => trim( @$colors[2] ),
            'type' => strtolower( $brand ) === 'rnb' ? 'steel' : 'alloy',
            'width' => isset( $diameter_width[1] ) ? $diameter_width[1] : '',
            'diameter' => @$diameter_width[0],
            'bolt_pattern_1' => @$bolt_patterns[0],
            'bolt_pattern_2' => @$bolt_patterns[1],
            'seat_type' => @$row['SEAT TYPE'],
            'offset' => @$row['OFFSET'],
            'center_bore' => @$row['CENTER BORE'],
            'style' => '',
            'image' => @$row['IMAGE'],
            'map_price' => @$row['MAP ENFORCED'] ? @$row['MAP'] : '',
            'msrp' => @$row['MSRP'],
            'cost' => @$row['PRICE'],
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
