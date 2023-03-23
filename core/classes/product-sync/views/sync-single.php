<?php

use Product_Sync_Admin_UI as ui;
use function _\map;
use function _\pick;
use function _\groupBy;

assert( isset( $sync ) );

if ( ! $sync ) {
    echo "Not found.";
    return;
}

$k = $sync::KEY;
$view = gp_test_input( @$_GET[ 'view' ] );

if ( @$_GET[ 'view' ] === 'insert' ) {
    Header::$title = "($k) INSERT";
    list( $req ) = $sync->create_sync_request();

    if ( ! $req ) {
        echo "Error.";
        exit;
    }

    echo ui::br();
    echo "<h2>Sync Request Created. (ID: " . (int) $req->get_primary_key_value() . ")</h2>";
    echo ui::br();
    echo gp_get_link( get_admin_page_url( 'product_sync' ), "Back to Product Sync" );
    echo ui::br();
    echo gp_get_link( get_admin_page_url( 'product_sync', [
        'action' => 'sync',
        'key' => $sync::KEY,
        'limit' => 1000,
        'req_id' => (int) $req->get_primary_key_value(),
    ] ), "Synchronize Now" );
    echo ui::br();
    echo $sync->tracker->display_everything( true );
    echo ui::br();
    echo ui::pre_print_r( $req->to_array() );
    echo ui::br();
    exit;
}

?>

<?= ui::breadcrumb( [
    [ 'Product Sync', ui::get_url( null ) ],
    [ $sync::KEY, ui::get_url( $sync::KEY ) ],
    @$_GET[ 'view' ] ? [ @$_GET[ 'view' ], '' ] : false,
] ); ?>
<?= ui::br(); ?>
    <h3>Product Sync: <?= $sync::KEY ?></h3>
<?= ui::br(); ?>

<?= ui::render_table( null, [ ui::table_row( $sync ) ], [
    'add_count' => false,
    'sanitize' => false,
] ); ?>

<?= ui::br(); ?>
<?php

// do first-ish
if ( @$_GET[ 'showCol' ] ) {

    $fetch = $sync->fetch( $sync->tracker, false );
    $source = $fetch::map_rows( $fetch->rows, 'source' );

    $showCol = urldecode( $_GET[ 'showCol' ] );

    $c = gp_test_input( $showCol );
    Header::$title = "$c ($k)";

    $colValues = array_map( function ( $row ) use ( $showCol ) {

        return strval( @$row->source[ $showCol ] );
    }, $fetch->rows );

    $rows = array_map( function ( $val ) use ( $showCol ) {

        return [
            $showCol => $val,
        ];
    }, $colValues );

    $sortValues = isset( $_GET[ 'sortValues' ] ) ? (bool) $_GET[ 'sortValues' ] : false;
    $limit = isset( $_GET[ 'limit' ] ) ? (int) $_GET[ 'limit' ] : 1000;

    $freq = ui::get_frequency_data( $rows, $sortValues, $limit );

    $sync->tracker->breakpoint( 'frequency_data' );

    echo $sync->tracker->display_summary( true );
    echo ui::br();

    echo ui::link_cols( $sync::KEY, $fetch->columns );
    echo ui::br();

    echo ui::pre_print_r( [
        'column' => $showCol,
        'allValues' => implode( ", ", $colValues ),
        'frequencies' => $freq
    ], 1 );
    echo ui::br();

    $fetch->cleanup();

} else {

    if ( @$_GET[ 'view' ] == '' ) {

        Header::$title = "Sync: $k";

        $q = "select * from sync_request WHERE sync_key = :sync_key ORDER BY id DESC LIMIT 0, 10";
        $reqs = Product_Sync::get_results( $q, [ [ 'sync_key', $sync::KEY ] ] );

        $reqs = array_map( [ 'DB_Sync_Request', 'map_to_admin_table' ], $reqs );

        echo ui::render_table( null, $reqs, [
            'add_count' => false,
            'sanitize' => false,
        ] );
    }

    if ( @$_GET[ 'view' ] === 'raw' ) {

        Header::$title = "($k) raw";

        $fetch = $sync->fetch( $sync->tracker );

        if ( $sync::FETCH_TYPE === 'local' ) {
            $path = Product_Sync::LOCAL_DIR . '/' . $sync::LOCAL_FILE;
            $raw_data = @file_get_contents( $path );
        } else {
            $raw_data = $fetch->get_raw_data();
        }

        echo ui::br();
        $sync->tracker->breakpoint( 'raw_data' );
        echo $sync->tracker->display_summary( true );
        echo ui::br();

        echo ui::link_cols( $sync::KEY, $fetch->columns );
        echo ui::br();
        echo ui::pre_print_r( $raw_data, 1, 1, 1 );

        $fetch->cleanup();
    }

    if ( @$_GET[ 'view' ] === 'parsed' ) {

        Header::$title = "($k) parsed";

        $fetch = $sync->fetch( $sync->tracker );

        $products = $fetch->to_product_array_with_diffs( $sync::TYPE, $sync->get_ex_products() );

        $sync->tracker->breakpoint( 'prod_array_diffs' );

        $summary = ui::get_fetch_summary( $sync, $fetch );

        // free some memory
        $fetch->rows = [];

        $sync->tracker->breakpoint( 'parsed' );
        echo $sync->tracker->display_summary( true );

        echo ui::link_cols( $sync::KEY, $fetch->columns );
        echo ui::br();
        echo ui::pre_print_r( $summary, 1 );
        echo ui::br();
        echo ui::pre_print_r( $products, 1 );
        $fetch->cleanup();
    }

    if ( @$_GET[ 'view' ] === 'table' ) {

        $fetch = $sync->fetch( $sync->tracker );
        $summary = ui::get_fetch_summary( $sync, $fetch );
        $offset = (int) gp_if_set( $_GET, 'offset', 0 );
        $limit = (int) gp_if_set( $_GET, 'limit', 999999 );

        list( $valid, $invalid ) = $fetch::filter_valid( $fetch->rows );

        // valid rows, invalid rows, or all rows
        $filtered_rows = cw_match( @$_GET[ 'filter' ], [ 'valid', 'invalid' ], [ $valid, $invalid ], true, $fetch->rows );

        if ( @$_GET[ 'byProduct' ] === '1' ) {

            Header::$title = "($k) products table";

            if ( @$_GET[ 'compact' ] === '1' ) {
                $mapped_rows = $fetch::map_rows( $filtered_rows, 'product', true, false );
            } else {
                $mapped_rows = $fetch::map_rows( $filtered_rows, 'product_source', true, false );
            }
        } else {
            Header::$title = "($k) source table";

            if ( @$_GET[ 'compact' ] === '1' ) {
                $mapped_rows = $fetch::map_rows( $filtered_rows, 'source', true, false );
            } else {
                $mapped_rows = $fetch::map_rows( $filtered_rows, 'source_product', true, false );
            }
        }

        $valid_products = $fetch::map_rows( $valid, 'product', false, false );

        $errors = Product_Sync::reduce( $fetch->rows, function ( $row ) {

            return implode( ", ", $row->get_all_errors() );
        } );

        echo ui::br();
        $sync->tracker->breakpoint( 'prepare_table' );
        echo $sync->tracker->display_summary( true );
        echo ui::br();
        echo ui::link_cols( $sync::KEY, $fetch->columns );
        echo ui::br();
        echo ui::pre_print_r( $summary, 1 );
        echo ui::br();
        echo ui::render_table( null, array_slice( $mapped_rows, $offset, $limit ), [
            'title' => 'Source/Products',
        ] );

        echo ui::br();

        if ( $sync::TYPE === 'tires' ) {

            $derived_brands = Product_Sync_Compare::compare_tire_brands( $valid_products, Product_Sync_Compare::get_ex_tire_brands() );
            $derived_models = Product_Sync_Compare::compare_tire_models( $valid_products, Product_Sync_Compare::get_ex_tire_models() );

            $_derived_brands = Product_Sync_Compare::ents_to_table_rows( $derived_brands, 'tire_brands' );
            $_derived_models = Product_Sync_Compare::ents_to_table_rows( $derived_models, 'tire_models' );

            echo ui::br();
            echo ui::render_table( null, $_derived_brands, [
                'title' => 'Derived Brands (' . count( $_derived_brands ) . ')',
            ] );
            echo ui::br();
            echo ui::render_table( null, $_derived_models, [
                'title' => 'Derived Models (' . count( $_derived_models ) . ')',
            ] );

        } else {

            $derived_brands = Product_Sync_Compare::compare_rim_brands( $valid_products, Product_Sync_Compare::get_ex_rim_brands() );
            $derived_models = Product_Sync_Compare::compare_rim_models( $valid_products, Product_Sync_Compare::get_ex_rim_models() );
            $derived_finishes = Product_Sync_Compare::compare_rim_finishes( $valid_products, Product_Sync_Compare::get_ex_rim_finishes() );

            $_derived_brands = Product_Sync_Compare::ents_to_table_rows( $derived_brands, 'rim_brands' );
            $_derived_models = Product_Sync_Compare::ents_to_table_rows( $derived_models, 'rim_models' );
            $_derived_finishes = Product_Sync_Compare::ents_to_table_rows( $derived_finishes, 'rim_finishes' );

            echo ui::br();
            echo ui::render_table( null, $_derived_brands, [
                'title' => 'Derived Brands (' . count( $_derived_brands ) . ')',
            ] );
            echo ui::br();
            echo ui::render_table( null, $_derived_models, [
                'title' => 'Derived Models (' . count( $_derived_models ) . ')',
            ] );
            echo ui::br();
            echo ui::render_table( null, $_derived_finishes, [
                'title' => 'Derived Finishes (' . count( $_derived_finishes ) . ')',
            ] );
        }

        echo ui::pre_print_r( array_count_values( $errors ) );

        echo ui::br();
        $sync->tracker->breakpoint( 'brands_models_etc' );
        echo $sync->tracker->display_summary( true );

        $fetch->cleanup();
    }

    if ( @$_GET[ 'view' ] === 'freq' ) {

        Header::$title = "($k) frequencies";

        $fetch = $sync->fetch( $sync->tracker );
        echo ui::link_cols( $sync::KEY, $fetch->columns );

        list( $valid, $invalid ) = $fetch::filter_valid( $fetch->rows );

        // valid rows, invalid rows, or all rows
        $filtered_rows = cw_match( @$_GET[ 'filter' ], [ 'valid', 'invalid' ], [ $valid, $invalid ], true, $fetch->rows );

        // display frequencies for source data or resulting product data
        if ( @$_GET[ 'byProduct' ] === '1' ) {
            $mapped_rows = $fetch::map_rows( $filtered_rows, 'product', false, false );
        } else {
            $mapped_rows = $fetch::map_rows( $filtered_rows, 'source', false, false );
        }

        $summary = ui::get_fetch_summary( $sync, $fetch );
        $summary[ 'frequencies' ] = ui::get_frequency_data( $mapped_rows, @$_GET[ 'sortValues' ], (int) @$_GET[ 'limit' ] );

        echo ui::br();
        $sync->tracker->breakpoint( 'build_freq_data' );
        echo $sync->tracker->display_summary( true );
        echo ui::br();

        echo ui::pre_print_r( $summary, true ) . '</pre>';
        $fetch->cleanup();
    }

}
