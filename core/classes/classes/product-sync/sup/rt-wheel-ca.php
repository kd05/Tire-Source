<?php

class Product_Sync_RT_Wheel_CA extends Product_Sync {

    const KEY = 'rt_wheel_ca';
    const TYPE = 'rims';
    const SUPPLIER = 'robert-thibert';
    const LOCALE = 'CA';

    const CRON_FETCH = true;
    const CRON_PRICES = true;

    /**
     * @return FTP_Get_Csv|null
     */
    function get_ftp_obj() {

        $ftp = new FTP_Get_Csv();
        $ftp->method = 'sftp';
        $ftp->host = self::FTP_SERVER;
        $ftp->username = 'u95793629-r-thibert';
        $ftp->password = self::get_credentials()['rt'];
        $ftp->remote_file_name = 'CIT010-CAD_WHEEL.csv';
        return $ftp;
    }

    /**
     * @return string[]
     */
    static function _source_req_cols(){
        return [
            'Series',
            'Pattern 2',
            'ColorEN',
            'PartType',
            'Status',
            'UPC Code',
        ];
    }

    /**
     * @return array
     */
    function get_source_req_cols() {
       return self::_source_req_cols();
    }

    /**
     * @param $row
     * @param $locale
     * @return float|int
     */
    static function get_stock( $row, $locale ) {

        if ( $locale === 'CA' ) {
            return array_sum( [
                @$row['Qty (Vancouver)'],
                @$row['Qty (Calgary)'],
                @$row['Qty (Montreal)'],
                @$row['Qty (Moncton)'],
                @$row['Qty (Toronto)'],
            ]);
        } else {
            return array_sum( [
                @$row['Qty (New York)'],
                @$row['Qty (Las Vegas)'],
            ]);
        }
    }

    /**
     * @param array $row
     * @param Product_Sync $sync
     * @return array
     */
    static function _build_product( array $row, Product_Sync $sync ){

        $errors = $sync->check_source_columns( $sync->get_source_req_cols(), $row );

        $part_number = @$row['ItemID'];

        if ( strpos( strtolower( $part_number ), 'blem' ) !== false ) {
            $errors[] = "BLEM wheel (used/scratched)";
        }

        if ( @$row['Brand'] === 'RT' ) {
            $brand = 'Robert Thibert';
        } else if ( @$row['Brand'] === 'RTX' && @$row['Series'] === 'RTX' ) {
            $brand = 'RTX';
        } else if ( in_array( @$row['Series'], [ 'Offroad', 'OE', 'R-Spec' ] ) ) {
            $brand = @$row['Brand'] . ' ' . @$row['Series'];
        } else {
            // still show it
            $brand = @$row['Brand'] . ' ' . @$row['Series'];
            $errors[] = "Invalid brand (the code ignores all except certain brands)";
        }

        $colors = explode( " with ", @$row['ColorEN'] );

        $width = @$row['Width (inch.)'];
        $diameter = @$row['Diameter (inch.)'];

        $bolt_pattern_1 = @$row['Pattern 1'] ? @$row['Bolt'] . 'x' . @$row['Pattern 1'] : '';
        $bolt_pattern_2 = @$row['Pattern 2'] ? @$row['Bolt'] . 'x' . @$row['Pattern 2'] : '';

        if ( $sync::LOCALE === 'CA' ) {
            $map = @$row['MAP (CAD)'];
            $msrp = @$row['MSRP (CAD)'];
        } else {
            $map = @$row['MAP (CAD)'];
            $msrp = @$row['MSRP (CAD)'];
        }

        $cost = @$row['Cost Price'];

        $stock = self::get_stock( $row, $sync::LOCALE );
        $discontinued = $row['Status'] === 'Discontinued';

        if ( $stock === 0 && $discontinued ) {
            $errors[] = "Stock 0 and discontinued";
        }

        return [
            'supplier' => $sync::SUPPLIER,
            'locale' => $sync::LOCALE,
            'upc' => @$row['UPC Code'],
            'part_number' => $part_number,
            'brand' => $brand,
            'model' => @$row['Model'],
            'color_1' => @$colors[0],
            'color_2' => @$colors[1],
            'finish' => '',
            'type' => strpos( strtolower( @$row['PartType'] ), 'alloy' ) !== false ? 'alloy' : 'steel',
            'width' => $width,
            'diameter' => $diameter,
            'bolt_pattern_1' => $bolt_pattern_1,
            'bolt_pattern_2' => $bolt_pattern_2,
            'seat_type' => @$row['Fixation Seat'],
            'offset' => @$row['Offset (mm)'],
            'center_bore' => @$row['HubBore (mm)'],
            'style' => @$row['Replica Make'] ? 'replica' : '',
            'image' => @$row['Image (01)'],
            'map_price' => $map,
            'msrp' => $msrp,
            'cost' => $cost,
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
