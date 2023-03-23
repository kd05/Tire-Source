<?php

$db = get_database_instance();
$tms = $db->get_results( "select * from tire_models" );

function generate_min_tire_prices( $locale ){

    $db = get_database_instance();

    $price_col = DB_Tire::get_price_column( $locale );

    $q = 'select * from tires where 1 = 1 ';
    $q .= 'and ' . DB_Tire::sql_assert_sold_and_not_discontinued_in_locale( 'tires', $locale ) . ' ';

    // price has to be ASC, brand/model doesn't matter
    $q .= 'order by brand_slug ASC, model_slug ASC, ' . $price_col . ' ASC;';

    $rows = $db->get_results( $q );

    $min_prices = [];

    $cur_brand = '';
    $cur_model = '';

    if ( $rows ) {
        foreach ( $rows as $row ) {

            if ( $row->brand_slug === $cur_brand && $row->model_slug === $cur_model ) {

            } else {
                $min_prices[$row->brand_slug][$row->model_slug] = $row->{$price_col};
            }

            $cur_brand = $row->brand_slug;
            $cur_model = $row->model_slug;
        }
    }

    echo get_pre_print_r( $min_prices );
}

generate_min_tire_prices( APP_LOCALE_CANADA );


