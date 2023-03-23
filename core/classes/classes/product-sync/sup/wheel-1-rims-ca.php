<?php

/**
 * A.K.A. The Wheel Group, TWG
 *
 * Class Product_Sync_Wheel_1_Rims_CA
 */
class Product_Sync_Wheel_1_Rims_CA extends Product_Sync {

    const KEY = 'wheel_1_rims_ca';
    const TYPE = 'rims';
    const SUPPLIER = 'wheel-1';
    const LOCALE = 'CA';

    const CRON_FETCH = true;
    const CRON_PRICES = true;

    /**
     * Images are in a separate file stored on our server. We'll
     * lazily parse that file and store the results here.
     *
     * @var null
     */
    public static $parsed_image_file = null;

    /**
     * To get a new file: go to https://www.twggarage.com/ -> All Assets ->
     * Canada Pricing & Specs or US Pricing & Specs. Download excel file.
     * Open it and remove all columns except sku/image. Then save as CSV.
     */
    const WHEEL_1_IMAGE_FILE_PATH = 'twg-wheel-ca-images.csv';

    /**
     * @return FTP_Get_Csv|null
     */
    function get_ftp_obj() {
        $ftp                   = new FTP_Get_Csv();
        $ftp->method           = 'sftp';
        $ftp->host             = self::FTP_SERVER;
        $ftp->username         = 'u95793629-twg';
        $ftp->password         = @self::get_credentials()['wheel-1'];
        $ftp->remote_file_name = 'WHEEL1INVENTORY_CA.csv';
        return $ftp;
    }

    /**
     * @return string[]
     */
    function get_admin_notes(){
        return [
            'Static Image File (Product file not static). See twggarage.com (assets) for images.',
        ];
    }

    /**
     * @param Product_Sync $sync
     */
    static function lazy_load_image_file( Product_Sync $sync ){

        if ( $sync::$parsed_image_file === null ) {

            $sync->tracker->breakpoint( 'twg_image_before' );

            // the file also contains image url 2 and 3 which we might use eventually.
            list( $cols, $rows, $err ) = self::parse_csv( Product_Sync::LOCAL_DIR . '/' . $sync::WHEEL_1_IMAGE_FILE_PATH, [
                'columns' => [ 'SKU', 'IMAGE URL 1' ],
                'filter' => function( $row ) {
                    return (bool) @$row['SKU'];
                }
            ] );

            $sync->tracker->breakpoint( 'twg_image_after__' . count( $rows ) );

            $result = [];

            foreach ( $rows as $row ) {
                $result[$row['SKU']] = $row['IMAGE URL 1'];
            }

            $sync::$parsed_image_file = $result;
        }
    }

    /**
     * @return array
     */
    function get_source_req_cols() {
        return self::_source_req_cols();
    }

    /**
     * @return string[]
     */
    static function _source_req_cols(){
        return [
            'Color',
            'PCD2',
            'MAP',
            'Offset',
        ];
    }

    /**
     * @return array|string[][]
     */
    function source_col_debug_map(){
        return [
            'Finish' => [ 'color_1', 'color_2', 'finish' ],
        ];

        // todo: like this eventually
//        return [
//            'in' => [ 'Color', 'Finish' ],
//            'out' => [ 'color_1', 'color_2', 'finish' ],
//        ];
    }

    /**
     * Finish col examples:
     *
     * MATTE BLACK/MACHINED FACE/POLISHED LIP
     * GREY/MACHINED FACE/POLISHED LIP
     * BLACK W/ MACHINED FACE
     * MATTE BLACK/MACHINED UNDERCUT
     * MATTE BLACK W/BRONZE TINT
     * BRUSHED MATTE BLACK W/DARK TINT
     * MACHINED SPOKES & LIP
     *
     * Unlike some suppliers, & doesn't indicate a separator here. (Lip is not
     * the secondary color in MACHINED SPOKES & LIP)
     *
     * @param $color - "CHROME" or "NON CHROME" - maybe we don't need this anymore.
     * @param $finish
     * @return array|string[]|string[][]
     */
    static function parse_wheel1_finish( $color, $finish ) {

        // we want to explode on / but not W/
        $w_s = '__space__w__slash__';
        $finish = str_replace( " W/", $w_s, $finish );
        $finish = str_replace( ' w/', $w_s, $finish );
        $finish_arr = explode( '/', $finish );
        $finish_arr = array_map( function( $item ) use( $w_s ){
            return str_replace( $w_s, ' W/', $item );
        }, $finish_arr );

        return $finish_arr;
    }

    /**
     * @param array $row
     * @return array
     */
    function build_product( array $row ) {

        $errors = $this->check_source_columns( $this->get_source_req_cols(), $row );

        $finish_arr = self::parse_wheel1_finish( @$row['Color'], @$row['Finish'] );

        self::lazy_load_image_file( $this );

        $part_number = @$row['Item'];

        // $row has 'image' but it's only file name.
        if ( $part_number ) {
            $image = @self::$parsed_image_file[$part_number];

            // they have typoes on like half the images.
            if ( strpos( $image, 'ww.') === 0 ) {
                $image = 'w' . $image;
            }

        } else {
            $image = '';
        }

        // rims cols
        return [
            'upc' => '',
            'part_number' => $part_number,
            'brand' => @$row['Brand'],
            'model' => @$row['StyleName'],
            'color_1' => @$finish_arr[0],
            'color_2' => @$finish_arr[1],
            'finish' => @$finish_arr[2],
            // unsure about type, but csv seems to give no info.
            'type' => 'alloy',
            'width' => @$row['Width'],
            'diameter' => @$row['Diameter'],
            'bolt_pattern_1' => str_replace( '-', 'x', @$row['PCD1'] ),
            'bolt_pattern_2' => str_replace( '-', 'x', @$row['PCD2'] ),
            'seat_type' => @$row[''],
            'offset' => @$row['Offset'],
            'center_bore' => @$row['Hub'],
            'style' => '',
            'image' => $image,
            'map_price' => @$row['MAP'] > 0 ? @$row['MAP'] : '',
            'msrp' => @$row['MSRP'],
            'cost' => @$row[''],
            'stock' => @$row[''],
            'discontinued' => @$row[''],
            '__meta' => [
                'errors' => $errors,
            ],
        ];
    }
}
