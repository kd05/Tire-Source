<?php

/**
 * Get URL from page name.
 *
 * @param string $name - name of page (ie pages.page_name) or route in Router.
 * @param array $params - @see Router
 * @return bool|mixed|string
 */
function get_url( $name = '', array $params = [] ) {

    // rarely used, but used at least once.
    if ( strpos( $name, 'http://' ) !== false || strpos( $name, 'https://' ) !== false ) {
        return $name;
    }

    return Router::get_url( $name, $params );
}

/**
 * @param $brand
 * @param $model
 * @param array $query_args
 * @return string
 */
function get_tire_model_url_basic( $brand, $model, $query_args = array() ) {
    return Router::build_url( [ 'tires', $brand, $model ], $query_args );
}

/**
 * @param array $brand_model
 * @param array $part_numbers
 * @param array $vehicle
 * @param array $query
 * @return string
 */
function get_tire_model_url( array $brand_model, array $part_numbers = [], array $vehicle = [], array $query = []){

    @list( $brand, $model ) = $brand_model;

    if ( $vehicle ) {
        $_vehicle = array_filter( [
            'make' => @$vehicle[0],
            'model' => @$vehicle[1],
            'year' => @$vehicle[2],
            'trim' => @$vehicle[3],
            'fitment' => @$vehicle[4],
            'sub' => @$vehicle[5],
        ] );

        $query = array_merge( $query, $_vehicle );
    }

    if ( $part_numbers ) {
        $_part_number = implode( "_", array_filter( [ @$part_numbers[0], @$part_numbers[1] ] ) );
        return Router::build_url( [ 'tires', $brand, $model, $_part_number ], $query );
    }

    return Router::build_url( [ 'tires', $brand, $model ], $query );
}

function get_tire_size_url( $width, $profile, $diameter ) {
    $width = (int) gp_test_input( $width );
    $profile = (int) gp_test_input( $profile );
    $diameter = (int) gp_test_input( $diameter );
    return Router::build_url( [ 'tires', $width . '-' . $profile . 'R' . $diameter ] );
}

/**
 * @param $brand
 * @param $model
 * @param array $query
 * @return string
 */
function get_rim_model_url( $brand, $model, array $query = [] ) {
    return Router::build_url( [ 'wheels', $brand, $model ], $query );
}

/**
 * Best to use this when creating a rims by size URL on the rim finish page.
 *
 * Don't get this confused with a rim archive URL that is also "by size".
 *
 * This ensures array values are handled properly and that query keys are sorted.
 *
 * @param array $brand_model_finish - array of slugs
 * @param $userdata - possibly just $_GET
 * @param array $query - additional query arguments (likely not needed)
 * @return string
 */
function get_rim_finish_url_by_size( array $brand_model_finish, $userdata, array $query = [] ) {

    $diameter_arr = gp_force_array_depth_1_scalar( @$userdata['diameter'], true );
    $width_arr = gp_force_array_depth_1_scalar( @$userdata['width'], true );

    // don't array filter yet. We need all of these array keys for below.
    // also, order of keys here will be reflected in URL.
    $_userdata = [
        'by_size' => 1,
        'diameter' => implode( '-', $diameter_arr ),
        'width' => implode( '-', $width_arr ),
        'bolt_pattern' => gp_force_singular( @$userdata['bolt_pattern'] ),
        'hub_bore_min' => gp_force_singular( @$userdata['hub_bore_min'] ),
        'hub_bore_max' => gp_force_singular( @$userdata['hub_bore_max'] ),
        'offset_min' => gp_force_singular( @$userdata['offset_min'] ),
        'offset_max' => gp_force_singular( @$userdata['offset_max'] ),
    ];

    // now we can filter
    $_query = array_filter( $_userdata, function( $val ) {
        return $val === '0' ? true : (bool) $val;
    } );

    // ignore keys in $query that are defined in $_userdata
    foreach ( $query as $q1 => $q2 ) {
        if ( ! in_array( $q1, array_keys( $_userdata ) ) ) {
            $_query[$q1] = $q2;
        }
    }

    // $_userdata was already sorted as needed, so this is a no-op right now.
    $_query = gp_array_sort_by_keys( $_query, [] );

    return get_rim_finish_url( $brand_model_finish, [], [], $_query );
}

/**
 * @param array $brand_model_finish - ie. DB_Rim_Finish->get_slugs()
 * @param array $vehicle - ie. Vehicle->get_slugs()
 * @param array $part_numbers - possibly an array of 2 part numbers if vehicle is present and staggered
 * @param array $query
 * @return string
 */
function get_rim_finish_url( array $brand_model_finish, array $part_numbers = [], array $vehicle = [], array $query = []){

    @list( $brand, $model, $color_1, $color_2, $finish ) = $brand_model_finish;

    if ( $color_1 || $color_2 || $finish ) {

        $_finish = build_rim_finish_url_segment( [ $color_1, $color_2, $finish ] );

        if ( $vehicle ) {
            $_vehicle = array_filter( [
                'make' => @$vehicle[0],
                'model' => @$vehicle[1],
                'year' => @$vehicle[2],
                'trim' => @$vehicle[3],
                'fitment' => @$vehicle[4],
                'sub' => @$vehicle[5],
            ] );

            $query = array_merge( $query, $_vehicle );
        }

        if ( $part_numbers ) {
            $_part_number = implode( "_", array_filter( [ @$part_numbers[0], @$part_numbers[1] ] ) );
            return Router::build_url( [ 'wheels', $brand, $model, $_finish, $_part_number ], $query );
        }

        return Router::build_url( [ 'wheels', $brand, $model, $_finish ], $query );
    }

    // rim model URL
    // since it doesn't currently support part numbers or vehicles,
    // just ignore those if they are passed in.
    return Router::build_url( [ 'wheels', $brand, $model ], $query );
}

/**
 * Tires/Wheels/Packages archive page with vehicle selected
 *
 * @param $page_type
 * @param array $vehicle_args - ie Vehicle->get_slugs()
 * @param array $query
 * @return string
 */
function get_vehicle_archive_url( $page_type, array $vehicle_args, array $query = [] ) {

    $base = cw_match( $page_type, [ 'tires', 'rims', 'wheels', 'packages' ], [ 'tires', 'wheels', 'wheels', 'packages' ] );
    @list ( $make, $model, $year, $trim, $fitment, $sub ) = $vehicle_args;

    if ( $sub ) {
        $query['sub'] = $sub;
    }

    $query = gp_array_sort_by_keys( $query, [ 'sub', 'type', 'tire_1', 'tire_2', 'rim_1', 'rim_2' ] );

    return Router::build_url( [ $base, $make, $model, $year, $trim, $fitment ], $query );
}

/**
 * @param array $colors_finishes
 * @return mixed|string
 */
function build_rim_finish_url_segment( array $colors_finishes ) {

    @list( $color_1, $color_2, $finish ) = $colors_finishes;

    if ( $color_1 && $color_2 && $finish ) {
        return "$color_1-with-$color_2-and-$finish";
    } else if ( $color_1 && $color_2 ) {
        return "$color_1-with-$color_2";
    } else if ( $color_1 ){
        return $color_1;
    }

    return '';
}

/**
 * @see core/tests/index.php
 *
 * @param $str
 * @return array
 */
function parse_rim_finish_url_segment( $str ) {

    $split = function( $s, $sub ){

        $pos = strpos( $s, $sub );

        // if string starts with sub, go to else branch.
        if ( $pos > 0 && ! string_ends_with( $s, $sub )) {
            return [ substr( $s, 0, $pos), substr( $s, $pos + strlen( $sub ), strlen( $s ) )];
        } else {
            return [ $s, '' ];
        }
    };

    @list( $color_1, $color_2_and_finish ) = $split( $str, '-with-' );

    if ( $color_2_and_finish ) {
        @list( $color_2, $finish ) = $split( $color_2_and_finish, '-and-' );

        return [ $color_1, $color_2, $finish ];
    } else {
        return [ $color_1, '', '' ];
    }

}