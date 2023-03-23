<?php

/**
 * @see Product_Sync_Fastco
 */
class Product_Sync_Fastco_Wheel_CA extends Product_Sync
{

    const KEY = 'fastco_wheel_ca';
    const TYPE = 'rims';
    const SUPPLIER = 'fastco';
    const LOCALE = 'CA';

    const CRON_FETCH = true;
    const CRON_PRICES = true;
    const CRON_EMAIL = true;

    const FETCH_TYPE = 'api';

    /**
     * @param Time_Mem_Tracker $mem
     * @return array|false|mixed
     */
    function fetch_api(Time_Mem_Tracker $mem)
    {
        return Product_Sync_Fastco::get_rims_ca_data();
    }

    function get_admin_notes()
    {
        return ["Possibly ready to be synced. Please check data thoroughly before accepting all changes."];
    }

    /**
     * 'Color 1' => [ 'Color 1', '', '' ]
     * 'First color with machined face' => [ 'First color', 'machined face', '' ]
     * 'Thing with Something and Something else' => [ 'Thing', 'Something', 'Something else' ]
     *
     * @param $str
     * @return array
     */
    static function parse_fastco_colors($str){

        $color_2 = $finish = '';
        @list( $color_1, $rest ) = explode( ' with ', $str, 2 );

        if ( $rest ) {
            @list( $color_2, $finish ) = explode( ' and ', $rest, 2 );
        }

        return [
            trim( $color_1 ),
            trim( $color_2 ),
            trim( $finish ),
        ];
    }

    /**
     * 19x10.0 => [ "19", "10.0" ]
     *
     * @param $str
     * @return array
     */
    static function parse_fastco_rim_size( $str ) {
        $parts = explode( 'x', $str );
        $parts = array_map( 'trim', $parts );
        return [ $parts[0] ?? '', $parts[1] ?? ''];
    }

    /**
     * '5x114.3mm' => [ '5x114.3', '' ]
     *
     * @param $str
     * @return array
     */
    static function parse_fastco_bolt_pattern( $str ) {

        $str = trim( $str );

        // convert X to x
        $str = strtolower( $str );

        // sometimes we see "5x127mm", other times "6x135 / 139.7mm"
        $str = str_replace( 'mm', '', $str );

        if ( ! $str ) {
            return [ '', '' ];
        }

        $parts = explode( '/', $str, 2 );
        $parts = array_map( 'trim', $parts );
        $parts = array_filter( $parts );

        if ( count( $parts ) === 1 ) {
            return [ $parts[0], '' ];
        }

        // if second bolt pattern starts with {digits}x
        if ( preg_match('/^([\d]{1,})x/', $parts[1] ) ) {
            return [ $parts[0], $parts[1] ];
        } else {

            // if we get here we know we have something in $parts[1] due to trim
            // and array filtering earlier on.

            // so, convert [ '6x114.3', '120' ] to [ '6x114.3', 6x120' ]
            // or [ '6x114.3', '120.3' ] to [ '6x114.3', 6x120.3' ]
            // basically, anything in the 2nd bolt pattern, prepend "6x"
            // might not bother checking the value makes sense beyond that,
            // there might be some global validation for that, or we just
            // let it happen because it's hard not to.

            // get the num bolts from first bolt pattern
            if ( preg_match('/^([\d]{1,})x/', $parts[0], $matches ) ) {
                $num_bolts = $matches[1];
            } else {
                return [ $parts[0], '' ];
            }

            $parts[1] = $num_bolts . 'x' . $parts[1];

            return [ $parts[0], $parts[1] ];
        }
    }

    /**
     * @param array $row
     * @return array
     */
    function build_product(array $row)
    {

        $errors = $this->check_source_columns( $this->get_source_req_cols(), $row );

        $brand = @$row['Brand'];
        $colors = self::parse_fastco_colors( $row['EnglishColour'] );

        $count_finishes = count( array_filter( $colors ) );
        $is_winter = strtolower( @$row['Season'] ) === 'w';

        $desc = @$row['EnglishDescription'];

        // probably the description only starts with "Steel Wheel" for the brand "Steel / Acier",
        // but i'm not totally sure. I guess all other rims are alloy (again, not sure).
        $desc_steel = strpos( strtolower( $desc ), 'steel wheel' ) !== false;
        $brand_steel = $brand === 'Steel / Acier';

        list( $bolt_pattern_1, $bolt_pattern_2 ) = self::parse_fastco_bolt_pattern( @$row['BoltPattern'] );

        list( $diameter, $width ) = self::parse_fastco_rim_size( @$row['Size'] );

        // often negative
        $offset = trim(str_replace( '+', '', @$row['Offset'] ));

        $is_replica = strtolower( $brand ) === 'replika';

        $is_special = strtolower( $row['IsSpecial'] ) === 'true';

        // careful.. sometimes we see ".00" here, so we'll round before
        // checking if it is truthy. Note that round(".00", 2) happens
        // to equal 0 (or "0" ?), so its non-truthy.
        $special_price = round( $row['SpecialPrice'], 2 );

        // rims cols
        return [
            'supplier' => $this::SUPPLIER,
            'locale' => $this::LOCALE,
            'upc' => '',
            'part_number' => @$row['PartNo'],
            'brand' => $brand,
            'model' => @$row['Model'],
            'color_1' => $colors[0],
            'color_2' => $colors[1],
            'finish' => $colors[2],
            'type' => $desc_steel || $brand_steel ? 'steel' : 'alloy',
            'width' => $width,
            'diameter' => $diameter,
            'bolt_pattern_1' => $bolt_pattern_1,
            'bolt_pattern_2' => $bolt_pattern_2,
            'seat_type' => '',
            'offset' => $offset,
            'center_bore' => @$row['CenterBore'],
            'style' => $is_replica ? 'replica' : '',
            'image' => @$row['ImageURL'],
            'map_price' => @$row['MAP'],
            'msrp' => @$row['RetailPrice'],
            'cost' => $is_special && $special_price ? $special_price : @$row['DealerCost'],
            'stock' => '',
            'discontinued' => '',
            '__meta' => [
                'errors' => $errors,
            ],
        ];
    }
}
