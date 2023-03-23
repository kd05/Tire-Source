<?php

/**
 * A.K.A. The Wheel Group, TWG
 *
 * Class Product_Sync_Wheel_1_Rims_US
 */
class Product_Sync_Wheel_1_Rims_US extends Product_Sync {

    const KEY = 'wheel_1_rims_us';
    const TYPE = 'rims';
    const SUPPLIER = 'wheel-1';
    const LOCALE = 'US';

    const FETCH_TYPE = 'local';
    const LOCAL_FILE = 'US+Jobber+Price+Sheet+with+complete+descriptions+05-06-2022.csv';

    // see Product_Sync_Wheel_1_Rims_CA for more info
    public static $parsed_image_file = null;
    const WHEEL_1_IMAGE_FILE_PATH = 'twg-wheel-us-images.csv';

    const CRON_FETCH = true;
    const CRON_PRICES = true;

    function get_admin_notes(){
        return [
            "Static File (Not automatically updated)",
        ];
    }

    /**
     * @return array
     */
    function get_source_req_cols() {
        return [
            'PCD2',
        ];
    }

    /**
     * @return array
     */
    function get_parse_csv_args() {
        return [
            'columns_omit' => [
                'BULLET POINTS',
                'SALES DESCRIPTION',
            ],
            'filter' => function( $row ) {
                return true;
            }
        ];
    }

    /**
     * @param array $row
     * @return array|string[]|string[][]
     */
    static function get_colors( array $row ){

        // chrome/non-chrome
        // $color_col = @$row['BASIC_FINISH'];

        // ie. "Model (Model-Number) Color 1 / Color 2 W/ Finish"
        // other times: "Model Model-Number Color 1 / Color 2 W/ Finish" (Retarded)
        // we need to exclude the model number.
        $desc = @$row['SHORT_DESCRIPTION'];

        // ie. model
        $name = @$row['NAME'];

        // ie. model number
        $style_number = @$row['STYLE_NUMBER'];

        // remove the model/model-number from start, if present
        if ( strpos( $desc, "$name $style_number" ) ) {
            $finish = substr( $desc, strlen( "$name $style_number" ) );
        } else {

            // take what's after ")" (trimmed) if a ")" is found
            $_a = explode( ")", $desc );
            $finish = trim( array_pop( $_a ) );
            $finish = $finish ? $finish : $desc;
        }

        return Product_Sync_Wheel_1_Rims_CA::parse_wheel1_finish( '', $finish );
    }

    /**
     * @param array $row
     * @return array
     */
    function build_product( array $row ) {

        $errors = self::check_cols( $row, $this->get_source_req_cols() );

        $colors = self::get_colors( $row );

        // rims cols
        return [
            'upc' => str_replace( '\'', '', @$row['UPC'] ),
            'part_number' => @$row['SKU'],
            'brand' => @$row['BRAND'],
            'model' => @$row['NAME'],
            'color_1' => @$colors[0],
            'color_2' => @$colors[1],
            'finish' => @$colors[2],
            // unsure about type, but csv seems to give no info.
            'type' => 'alloy',
            'width' => @$row['WHEEL_WIDTH'],
            'diameter' => @$row['DIAMETER'],
            'bolt_pattern_1' => str_replace( '-', 'x', @$row['PCD1'] ),
            'bolt_pattern_2' => str_replace( '-', 'x', @$row['PCD2'] ),
            'seat_type' => @$row[''],
            'offset' => @$row['OFFSETNUM'],
            'center_bore' => @$row['HUB'],
            'style' => '',
            'image' => @$row['IMAGE URL 1'],
            'map_price' => @$row['MAP PRICE'],
            'msrp' => @$row['US MSRP'],
            'cost' => '',
            'stock' => null,
            'discontinued' => false,
            '__meta' => [
                'errors' => $errors,
            ],
        ];
    }
}
