<?php

use Product_Sync_Admin_UI as ui;

$supplier = gp_test_input( @$_GET['supplier'] );
$type = gp_test_input( @$_GET['type'] );
$locale = gp_test_input( @$_GET['locale'] );

Header::$title = "Database $supplier $type $locale";

$getUniq = function( $data, $column, $sort = 'default' ) {

    $vals = array_column( $data, $column );
    $vals = array_map( 'trim', $vals );
    $freq = array_count_values( $vals );

    if ( $sort === 'default' ) {
        ksort( $freq );
    } if ( $sort === 'numeric' ) {
        ksort( $freq, SORT_NUMERIC );
    }

    return $freq;
};

$print = function( $column, $frequencies, $link_table = false ) use( $supplier ){

    $items = [];

    foreach ( $frequencies as $value => $count ) {
        $_count = " (" . (int) $count. ")";

        if ( $link_table ) {
            $url = get_admin_archive_link( $link_table, [
                'supplier' => $supplier,
                $column => gp_sanitize_href( $value ),
            ]);

            $items[] = gp_get_link( $url, gp_test_input( $value ) ) . $_count;
        } else {
            $items[] = gp_test_input( $value ) . $_count;
        }
    }

    echo wrap_tag( gp_test_input( $column ) . ': ' . implode(", ", $items ), 'p' );
    echo ui::br(15);
};

$mem = new Time_Mem_Tracker();

$link = function( $col ) use( $supplier, $type, $locale ) {
    return ui::get_supplier_db_page_link( $type, $locale, $supplier, $col );
};

echo wrap_tag( "Column values (and product counts) of " . $supplier . " products currently stored in the database.", 'p' );
echo ui::br(15);
echo wrap_tag( implode( ' | ', [
    gp_get_link( $link( '' ), 'Default Columns' ),
    gp_get_link( $link( 'part_numbers' ), 'Part Numbers / UPC' ),
    gp_get_link( $link( 'alt' ), 'US/CA Columns (Developer)' ),
]), 'p' );
echo ui::br(15);

$common_alt_cols = [
    'import_name' => [ 'default', true ],
    'import_date' => [ 'default', true ],
    'stock_amt_ca' => [ 'numeric', true ],
    'stock_sold_ca' => [ 'numeric', true ],
    'stock_unlimited_ca' => [ 'default', true ],
    'stock_discontinued_ca' => [ 'default', true ],
    'stock_update_id_ca' => [ 'numeric', true ],
    'stock_amt_us' => [ 'numeric', true ],
    'stock_sold_us' => [ 'numeric', true ],
    'stock_unlimited_us' => [ 'default', true ],
    'stock_discontinued_us' => [ 'default', true ],
    'stock_update_id_us' => [ 'numeric', true ],
    'sync_id_insert_ca' => [ 'numeric', true ],
    'sync_date_insert_ca' => [ 'default', true ],
    'sync_id_update_ca' => [ 'numeric', true ],
    'sync_date_update_ca' => [ 'default', true ],
    'sync_id_insert_us' => [ 'numeric', true ],
    'sync_date_insert_us' => [ 'default', true ],
    'sync_id_update_us' => [ 'numeric', true ],
    'sync_date_update_us' => [ 'default', true ],
    'msrp_ca' => [ 'default', true ],
    'cost_ca' => [ 'default', true ],
    'price_ca' => [ 'default', true ],
    'map_price_ca' => [ 'default', true ],
    'sold_in_ca' => [ 'default', true ],
    'msrp_us' => [ 'default', true ],
    'cost_us' => [ 'default', true ],
    'map_price_us' => [ 'default', true ],
    'price_us' => [ 'default', true ],
    'sold_in_us' => [ 'default', true ],
];

if ( $type === 'tires' ) {
    $products = Product_Sync_Compare::get_ex_tires( $supplier );
    $mem->breakpoint( 'products' );

    if ( @$_GET['cols'] === 'alt' ) {
        $cols = $common_alt_cols;
    } else if ( @$_GET['cols'] === 'part_numbers' ) {
        $cols = [
            'part_number' => [ 'length', true ],
            'upc' => [ 'length', true ],
        ];
    } else {
        $cols = [
            'width' => [ 'numeric', true ],
            'profile' => [ 'numeric', true ],
            'diameter' => [ 'numeric', true ],
            'load_index' => [ 'default', true ],
            'load_index_2' => [ 'default', true ],
            'speed_rating' => [ 'default', true ],
            'is_zr' => [ 'number', true ],
            'extra_load' => [ 'default', true ],
            'tire_sizing_system' => [ 'default', true ],
            'sold_in_ca' => [ 'numeric', true ],
            'brand_slug' => [ 'brand_slug', true ],
            'model_slug' => [ 'model_slug', true ],
            'size' => [ 'default', true ],
            $locale === 'CA' ? 'price_ca' : 'price_us' => [ 'numeric', true ],
        ];
    }
} else {
    $products = Product_Sync_Compare::get_ex_rims( $supplier, true );

    if ( @$_GET['cols'] === 'alt' ) {
        $cols = $common_alt_cols;
    } else if ( @$_GET['cols'] === 'part_numbers' ) {
        $cols = [
            'part_number' => [ 'length', true ],
            'upc' => [ 'length', true ],
        ];
    } else {
        $cols = [
            'type' => [ 'default', true ],
            'style' => [ 'default', true ],
            'brand_slug' => [ 'default', true ],
            'model_slug' => [ 'default', true ],
            'color_1' => [ 'default', true ],
            'color_2' => [ 'default', true ],
            'finish' => [ 'default', true ],
            'size' => [ 'default', true ],
            'width' => [ 'numeric', true ],
            'diameter' => [ 'numeric', true ],
            'bolt_pattern_1' => [ 'default', true ],
            'bolt_pattern_2' => [ 'default', true ],
            'seat_type' => [ 'default', true ],
            'offset' => [ 'numeric', true ],
            'center_bore' => [ 'numeric', true ],
            $locale === 'CA' ? 'price_ca' : 'price_us' => [ 'numeric', true ],
        ];
    }
}

foreach ( $cols as $col => $opts ) {
    $link_table = $type === 'tires' ? 'tires' : 'rims';
    $print( $col, $getUniq( $products, $col, $opts[0] ), $opts[1] ? $link_table : '' );
    $mem->breakpoint( $col );
}

echo ui::br(15);
echo $mem->display_summary( true );