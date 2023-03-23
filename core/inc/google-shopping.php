<?php

namespace GoogleShopping;

/**
 * @param $str
 * @return string
 */
function format_price( $str ) {
    return @number_format( $str, 2, '.', '' );
}

/**
 * @return string[]
 */
function get_rims_taxonomies(){
    // not super clear which is best to use.
    // probably most rims should be 'auto', but some might be 'offroad',
    // but it might be hard to determine which ones are 'offroad',
    // so do we use default or auto? I don't know.
    return [
        'default' => 'Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Wheel Systems > Motor Vehicle Rims & Wheels',
        'auto' => 'Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Wheel Systems > Motor Vehicle Rims & Wheels > Automotive Rims & Wheels',
        'offroad' => 'Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Wheel Systems > Motor Vehicle Rims & Wheels > Off-Road and All-Terrain Vehicle Rims & Wheels',
    ];
}

/**
 * @return array[]
 */
function get_rims_data(){

    $params = [];

    $q = "SELECT * FROM rims r ";
    $q .= "INNER JOIN rim_brands b ON b.rim_brand_id = r.brand_id ";
    $q .= "INNER JOIN rim_models m ON m.rim_model_id = r.model_id ";
    $q .= "INNER JOIN rim_finishes f ON f.rim_finish_id = r.finish_id ";
    $q .= "WHERE 1 = 1 ";
    $q .= "AND supplier IN ('robert-thibert', 'dai') ";
    $q .= "AND sold_in_ca = 1 AND stock_discontinued_ca = 0 ";
    $q .= "ORDER BY supplier, rim_brand_slug, rim_model_slug, f.color_1, f.color_2, f.finish, diameter, width, price_ca ";

    return \DatabasePDO::get_results_( $q, $params );
}

/**
 * @param array $db_rims
 * @return array
 */
function map_rims_data( array $db_rims ) {

    $rows = [];
    $omitted = [];

    $omit = function( $rim, $err = '' ) use (&$omitted ) {
        $rim['err'] = $err;
        $omitted[] = $rim;
    };

    foreach ( $db_rims as $rim ) {

        $image_url = \DB_Rim_Finish::get_image_url_( $rim['image_local'], 'reg', true );
        $image_path = \DB_Rim_Finish::get_image_path_( $rim['image_local'], 'reg', true );

        if ( ! $image_url || ! file_exists( $image_path ) ) {
            $omit( $rim, 'No image' );
            continue;
        }

        if ( $rim['stock_unlimited_ca'] == false && $rim['stock_amt_ca'] < 4 ) {
            $omit( $rim, 'Not enough stock (' . $rim['stock_amt_ca'] . ')' );
            continue;
        }

        $price = $rim['price_ca'];

        if ( ! $price || ! is_numeric( $price ) || $price < 1 ) {
            $omit( $rim, 'Invalid price (or zero dollars)' );
            continue;
        }

        $desc = $rim['rim_brand_description'];

        if ( trim( $rim['rim_model_description'] ) ) {
            $desc = $rim['rim_model_description'];
        }

        $desc = trim( strip_tags( $desc ) );

        $url = get_rim_finish_url( [
            $rim['rim_brand_slug'],
            $rim['rim_model_slug'],
            $rim['color_1'],
            $rim['color_2'],
            $rim['finish'],
        ], [
            $rim['part_number']
        ] );

        $brand = $rim['rim_brand_name'];
        $model = $rim['rim_model_name'];
        $pn = $rim['part_number'];
        $d = $rim['diameter'];
        $w = $rim['width'];
        $bp1 = $rim['bolt_pattern_1'];
        $bp2 = $rim['bolt_pattern_2'];
        $bps = implode( "/", array_filter( [ $bp1, $bp2 ] ) );
        $off = $rim['offset'];
        $cb = $rim['center_bore'];

        $diam_width = $d . "x" . $w;

        $colors = implode( " / ", array_filter( [
            $rim['color_1_name'],
            $rim['color_2_name'],
            $rim['finish_name'],
        ] ) );

        $title = "$brand $model $colors Wheels ($pn) $diam_width $bps ET $off hubbore $cb";

        $keywords = [];

//        $keywords = [
//            'Wheels',
//            'Rims',
//        ];

        $row = [
            'id' => $rim['part_number'],
            'title' => $title,
            'description' => $desc,
            'link' => $url,
            'condition' => 'New',
            'price' => format_price( $rim['price_ca'] ),
            'availability' => 'In Stock',
            'image link' => $image_url,
            'gtin' => $rim['upc'],
            'mpn' => $pn,
            'brand' => $rim['rim_brand_name'],
            'google product code' => get_rims_taxonomies()['default'],
            // ie. keywords
            'Product Type' => implode( ", ", $keywords ),
            '__diameter' => $rim['diameter'],
            '__model_name' => strtoupper( $rim['rim_model_name'] ),
            '__bolt_pattern_1' => $rim['bolt_pattern_1'],
            '__bolt_pattern_2' => $rim['bolt_pattern_2'],
        ];

        $rows[] = $row;
    }

    return [ $rows, $omitted ];
}
