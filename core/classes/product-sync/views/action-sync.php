<?php

use Product_Sync_Admin_UI as ui;

use function _\sortBy;

assert( isset( $sync ) );

Header::$title = "Synchronize (" . $sync::KEY . ")";

$debug_only = (int) @$_GET['debug'];
$mock_prices = (int) @$_GET['mock_prices'];

echo ui::breadcrumb( [
    [ 'Product Sync', ui::get_url( null ) ],
    [ $sync::KEY, ui::get_url( $sync::KEY ) ],
    [ 'Synchronize', ui::get_url( $sync::KEY, [ 'action' => 'sync', 'limit' => (int) gp_if_set( $_GET, 'limit', 1000 ) ] ), [ 'new_tab' => true ] ],
] );

$last_valid_req = DB_Sync_Request::get_latest_without_errors( $sync::KEY );

$req_id = intval( @$_GET[ 'req_id' ] );
if ( $req_id ) {
    $req = DB_Sync_Request::create_instance_via_primary_key( $req_id );

    if ( $req && $req->get( 'sync_key' ) !== $sync::KEY ) {
        echo "<br> Sync key of request ID does not match.";
        return;
    }

} else {
    $req = $last_valid_req;
}

if ( ! $req ) {
    // this error pretty much only occurs when adding new suppliers
    echo "No valid fetch/requests were found. Can only synchronize when there are more than 0 valid products in the file the last time we fetched it.";
    return;
}

echo ui::br( 30 );

$req_id = $req->get_primary_key_value();

echo ui::render_table( null, [ [
    'Request ID' => html_link_new_tab( $req->get_admin_single_page_url(), $req_id),
    'Supplier' => $sync::SUPPLIER,
    'Locale' => $sync::LOCALE,
    'Type' => $sync::TYPE,
    'Products in file' => (int) $req->get( 'count_all' ),
    'Valid after parsing' => (int) $req->get( 'count_valid' ),
    'File parsed at' => gp_test_input( $req->get( 'inserted_at' ) ),
    'Accept Changes' => ui::get_accept_changes_form( $sync::KEY, $req_id ),
    'Run Inventory' => ui::get_run_inventory_btn( $sync ),
] ], [
    'title' => 'Fetch Info',
    'sanitize' => false,
    'add_count' => false
] );

$notes = $sync->get_admin_notes();

?>
    <p style="margin-top: -15px;"><strong>Supplier Notes: </strong><?= $notes ? implode( ", ", $notes ) : 'None'; ?></p>
    <br><br>
<?php

$prod_limit = gp_if_set( $_GET, 'limit', 1000 );

// for js
echo html_element( '', 'div', 'ps-response' );

list( $valid_products, $invalid_products ) = $sync->load_products_from_disk( $req->get_primary_key_value()  );

$show_mock_prices = function( $valid_products, $invalid_products ) use( $sync ) {

    $all_products = array_merge( $valid_products, $invalid_products );

    // see core/product-sync/ajax/sync-products
    $all_products = Product_Sync_Compare::index_by( $all_products, function( $prod ){
        return $prod['part_number'];
    }, true );

    list ( $updates_0, $updates_1, $ex_products, $ex_products_not_in_file, $count_ex_products_same ) =
        Product_Sync_Update::get_price_change_updates( $sync, $all_products );

    echo '<pre>' . print_r( [
            'count_ex_products' => count( $ex_products ),
            'count_ex_products_not_in_file' => count( $ex_products_not_in_file ),
            'count_ex_products_same' => $count_ex_products_same,
        ], true ) . '</pre>';

    echo ui::br();
    echo "Updates 0";
    echo ui::br();
    echo '<pre>' . print_r( $updates_0, true ) . '</pre>';
    echo ui::br();

    echo "Updates 1";
    echo ui::br();
    echo '<pre>' . print_r( $updates_1, true ) . '</pre>';
    echo ui::br();

};

if ( $sync::TYPE === 'tires' ) {

    // returns a slightly modified $valid_products
    list( $ex_products, $ex_brands, $ex_models, $derived_brands, $derived_models )
        = Product_Sync_Compare::compare_tires_all( $valid_products, $invalid_products, $sync->tracker );

    list( $to_delete, $to_mark_not_sold ) = Product_Sync_Compare::get_ex_products_to_delete( $sync::SUPPLIER, $sync::LOCALE, $valid_products, $ex_products );

    // in this context we can just pretend these are the same thing for now.
    // note that the __action column specifies delete/not-sold
    $to_delete = array_merge( $to_delete, $to_mark_not_sold );

    if ( $mock_prices ) {
        $show_mock_prices( $valid_products, $invalid_products );
        return;
    }

    if ( $debug_only ) {

        list( $b0, $m0, $m1, $t0, $t1 ) = Product_Sync_Update::create_mock_tire_updates(
            $valid_products,
            $derived_brands,
            $derived_models,
            $ex_brands,
            $ex_models
        );

        $sync->tracker->breakpoint('create_mock_updates');

        echo ui::pre_print_r( [
            'b0' => count( $b0 ),
            'm0' => count( $m0 ),
            'm1' => count( $m1 ),
            't0' => count( $t0 ),
            't1' => count( $t1 ),
        ]);

        ob_start();

        echo ui::render_table( null, ui::limit_items_shown( $b0, $prod_limit ), [
            'title' => 'Brands/Insert',
        ] );
        echo ui::render_table( null, ui::limit_items_shown( $m0, $prod_limit ), [
            'title' => 'Models/Insert',
        ] );
        echo ui::render_table( null, ui::limit_items_shown( $m1, $prod_limit ), [
            'title' => 'Models/Update',
        ] );
        echo ui::render_table( null, ui::limit_items_shown( $t0, $prod_limit ), [
            'title' => 'Tires/Insert',
        ] );
        echo ui::render_table( null, ui::limit_items_shown( $t1, $prod_limit ), [
            'title' => 'Tires/Update',
        ] );

        $tables = ob_get_clean();
        $sync->tracker->breakpoint('tables');
        echo $sync->tracker->display_summary( true );
        echo ui::br();
        echo $tables;
        return;
    }

    $sync->tracker->breakpoint( 'compute_diffs' );

    list( $new_products, $diff_products, $same_products ) = Product_Sync_Compare::split_ents( $valid_products );
    list( $new_brands ) = Product_Sync_Compare::split_ents( $derived_brands );
    list( $new_models, $diff_models ) = Product_Sync_Compare::split_ents( $derived_models );

    $sync->tracker->breakpoint( 'split_ents' );

    $_new_products_shown = Product_Sync_Compare::ents_to_table_rows(
        ui::limit_items_shown( $new_products, $prod_limit ),
        'tires',
        false
    );
    $_diff_products_shown = Product_Sync_Compare::ents_to_table_rows(
        ui::limit_items_shown( $diff_products, $prod_limit ),
        'tires',
        false
    );
    $_new_brands = Product_Sync_Compare::ents_to_table_rows( $new_brands, 'tire_brands', false );
    $_new_models = Product_Sync_Compare::ents_to_table_rows( $new_models, 'tire_models', false );
    $_diff_models = Product_Sync_Compare::ents_to_table_rows( $diff_models, 'tire_models', false );

    $sync->tracker->breakpoint( 'to_table_rows' );

    $_new_brands = _\sortBy( $_new_brands, [ '__key'] );
    $_new_models = _\sortBy( $_new_models, [ '__key'] );

    $sync->tracker->breakpoint( 'sort_tables' );

    ?>

    <?= ui::br(); ?>

    <div class="ps-tabs">
        <div class="controls">
            <div data-tab="default" class="active">Info</div>
            <div data-tab="prodNew">New Tires (<?= count( $new_products ); ?>)</div>
            <div data-tab="prodDiff">Changed Tires (<?= count( $diff_products ); ?>)</div>
            <div data-tab="prodDel">Tires to Delete (<?= count( $to_delete ); ?>)</div>
            <div data-tab="brandNew">New Brands (<?= count( $new_brands ); ?>)</div>
            <div data-tab="modelNew">New Models (<?= count( $new_models ); ?>)</div>
            <div data-tab="modelDiff">Changed Models (<?= count( $diff_models ); ?>)</div>
            <div data-tab="prodInvalid">Invalid Products (Supplier File) (<?= count( $invalid_products ); ?>)</div>
            <div data-tab="debug">Debug</div>
        </div>
    </div>

    <?= ui::br(); ?>

    <div class="ps-tab-content" data-tab="prodNew">
        <p>Items shown limited to <?= (int) $prod_limit; ?>.</p>
        <?= ui::render_table( null, $_new_products_shown, [
            'sanitize' => false,
        ] ); ?>
    </div>
    <div class="ps-tab-content" data-tab="prodDiff">
        <p>Items shown limited to <?= (int) $prod_limit; ?>.</p>
        <?= ui::render_table( null, $_diff_products_shown, [
            'sanitize' => false,
        ] ); ?>
    </div>
    <div class="ps-tab-content" data-tab="brandNew">
        <?= ui::render_table( null, $_new_brands, [
            'sanitize' => false,
        ] ); ?>
    </div>
    <div class="ps-tab-content" data-tab="modelNew">
        <?= ui::render_table( null, $_new_models, [
            'sanitize' => false,
        ] ); ?>
    </div>
    <div class="ps-tab-content" data-tab="modelDiff">
        <p>Differences to type, category, and class may be ignored. Differences in images shown below. </p>
        <?= ui::render_table( null, $_diff_models, [
            'sanitize' => false,
        ] ); ?>
    </div>

    <?php $sync->tracker->breakpoint( 'render_tables' ); ?>

    <?php

}

if ( $sync::TYPE === 'rims' ) {

    list ( $ex_products, $ex_brands, $ex_models, $ex_finishes, $derived_brands, $derived_models, $derived_finishes )
        = Product_Sync_Compare::compare_rims_all( $valid_products, $invalid_products, $sync->tracker );

    // after above
    list( $to_delete, $to_mark_not_sold ) = Product_Sync_Compare::get_ex_products_to_delete( $sync::SUPPLIER, $sync::LOCALE, $valid_products, $ex_products );

    // in this context we can just pretend these are the same thing for now.
    $to_delete = array_merge( $to_delete, $to_mark_not_sold );

    $ex_products = null;

    list( $brands_to_insert ) = Product_Sync_Compare::split_ents( $derived_brands );
    list( $models_to_insert ) = Product_Sync_Compare::split_ents( $derived_models );
    list( $finishes_to_insert, $finishes_to_update ) = Product_Sync_Compare::split_ents( $derived_finishes );
    list( $rims_to_insert, $rims_to_update ) = Product_Sync_Compare::split_ents( $valid_products );

    if ( $mock_prices ) {
        $show_mock_prices( $valid_products, $invalid_products );
        return;
    }

    if ( $debug_only ) {

        list( $b0, $m0, $f0, $f1, $r0, $r1 ) = Product_Sync_Update::create_mock_rim_updates(
            $valid_products,
            $derived_brands,
            $derived_models,
            $derived_finishes,
            $ex_brands,
            $ex_models,
            $ex_finishes
        );

        $sync->tracker->breakpoint('create_mock_updates');

        echo ui::pre_print_r( [
            'b0' => count( $b0 ),
            'm0' => count( $m0 ),
            'f0' => count( $f0 ),
            'f1' => count( $f1 ),
            'r0' => count( $r0 ),
            'r1' => count( $r1 ),
        ]);

        ob_start();

        echo ui::render_table( null, ui::limit_items_shown( $b0, $prod_limit ), [
            'title' => 'Brands/Insert',
        ] );
        echo ui::render_table( null, ui::limit_items_shown( $m0, $prod_limit ), [
            'title' => 'Models/Insert',
        ] );
        echo ui::render_table( null, ui::limit_items_shown( $f0, $prod_limit ), [
            'title' => 'Finishes/Insert',
        ] );
        echo ui::render_table( null, ui::limit_items_shown( $f1, $prod_limit ), [
            'title' => 'Finishes/Update',
        ] );
        echo ui::render_table( null, ui::limit_items_shown( $r0, $prod_limit ), [
            'title' => 'Rims/Insert',
        ] );
        echo ui::render_table( null, ui::limit_items_shown( $r1, $prod_limit ), [
            'title' => 'Rims/Update',
        ] );

        $tables = ob_get_clean();
        $sync->tracker->breakpoint('tables');
        echo $sync->tracker->display_summary( true );
        echo ui::br();
        echo $tables;

        return;
    }

    list( $new_products, $diff_products, $same_products ) = Product_Sync_Compare::split_ents( $valid_products );
    list( $new_brands ) = Product_Sync_Compare::split_ents( $derived_brands );
    list( $new_models ) = Product_Sync_Compare::split_ents( $derived_models );
    list( $new_finishes, $diff_finishes ) = Product_Sync_Compare::split_ents( $derived_finishes );

    $sync->tracker->breakpoint( 'filter_ents' );

    $_new_products_shown = Product_Sync_Compare::ents_to_table_rows(
        ui::limit_items_shown( $new_products, $prod_limit ),
        'rims',
        false
    );
    $_diff_products_shown = Product_Sync_Compare::ents_to_table_rows(
        ui::limit_items_shown( $diff_products, $prod_limit ),
        'rims',
        false
    );
    $_new_brands = Product_Sync_Compare::ents_to_table_rows( $new_brands, 'rim_brands', false );
    $_new_models = Product_Sync_Compare::ents_to_table_rows( $new_models, 'rim_models', false );
    $_new_finishes = Product_Sync_Compare::ents_to_table_rows( $new_finishes, 'rim_finishes', false );
    $_diff_finishes = Product_Sync_Compare::ents_to_table_rows( $diff_finishes, 'rim_finishes', false );

    $sync->tracker->breakpoint( 'map_ents' );

    ?>

    <?= ui::br(); ?>

    <div class="ps-tabs">
        <div class="controls">
            <div data-tab="default" class="active">Info</div>
            <div data-tab="prodNew">New Rims (<?= count( $new_products ); ?>)</div>
            <div data-tab="prodDiff">Changed Rims (<?= count( $diff_products ); ?>)</div>
            <div data-tab="prodDel">Rims to Delete (<?= count( $to_delete ); ?>)</div>
            <div data-tab="brandNew">New Brands (<?= count( $new_brands ); ?>)</div>
            <div data-tab="modelNew">New Models (<?= count( $new_models ); ?>)</div>
            <div data-tab="finishNew">New Finishes (<?= count( $new_finishes ); ?>)</div>
            <div data-tab="finishDiff">Changed Finishes (<?= count( $diff_finishes ); ?>)</div>
            <div data-tab="prodInvalid">Invalid Products (<?= count( $invalid_products ); ?>)</div>
            <div data-tab="debug">Debug</div>
        </div>
    </div>

    <?= ui::br(); ?>

    <div class="ps-tab-content" data-tab="prodNew">
        <p>Items shown limited to <?= (int) $prod_limit; ?>.</p>
        <?= ui::render_table( null, $_new_products_shown, [
            'sanitize' => false,
        ] ); ?>
    </div>
    <div class="ps-tab-content" data-tab="prodDiff">
        <p>Items shown limited to <?= (int) $prod_limit; ?>.</p>
        <?= ui::render_table( null, $_diff_products_shown, [
            'sanitize' => false,
        ] ); ?>
    </div>
    <div class="ps-tab-content" data-tab="brandNew">
        <?= ui::render_table( null, $_new_brands, [
            'sanitize' => false,
        ] ); ?>
    </div>
    <div class="ps-tab-content" data-tab="modelNew">
        <?= ui::render_table( null, $_new_models, [
            'sanitize' => false,
        ] ); ?>
    </div>
    <div class="ps-tab-content" data-tab="finishNew">
        <?= ui::render_table( null, $_new_finishes, [
            'sanitize' => false,
        ] ); ?>
    </div>
    <div class="ps-tab-content" data-tab="finishDiff">
        <?= ui::render_table( null, $_diff_finishes, [
            'sanitize' => false,
        ] ); ?>
    </div>

    <?php $sync->tracker->breakpoint( 'render_tables' ); ?>

    <?php
}

?>

    <div class="ps-tab-content active" data-tab="default">
        <h2><?= $sync::TYPE === 'tires' ? 'Approve Tires' : 'Approve Rims'; ?> (<?= $sync::LOCALE; ?>) (<?= $sync::SUPPLIER; ?>)</h2>
        <p><strong><?= @count( $same_products ); ?></strong> product(s) from the suppliers file were valid and the same as the products currently in the database.</p>
        <p>Click on different tabs to see the changes which have to be made.</p>
        <p>When you are ready to <strong>accept changes</strong>, click the submit button near the top. Then if this produces a success message, click the Run Inventory button beside the submit button.</p>
        <p>If the changes listed are not what you want to accept, this usually means the code has to be updated before accepting changes (or you might want to change the price rules).</p>
        <p>If there are no items showing up in the "New" or "Changed" tabs, it means your database is in sync with the supplier's file.</p>
        <p>The Invalid Products tab lists invalid products from the suppliers file, not your database. Invalid products don't make it into the database to begin with. If there are items in this tab, but not other tabs, then you don't need to approve any changes.</p>
        <p>Part numbers from the Invalid Products tab that are also in your database will also be in the Products to Delete tab (and when you approve changes, those products will be deleted). If all or nearly all products are in both the Invalid and Delete tabs, then it probably indicates an error in parsing the supplier file (perhaps the supplier changed the columns in the file). Check the Invalid Products tab for more info. If all products have price errors, it could be because you have not yet setup the supplier price rule yet.</p>
        <br>
    </div>

    <div class="ps-tab-content" data-tab="debug">
        <div>
            <?php
            echo ui::render_table( null, [ ui::table_row( $sync ) ], [
                'title' => 'Supplier',
                'sanitize' => false,
            ] );
            ?>
            <div class="general-content">
                <p>
                    <?= html_link_new_tab( ui::get_url( $sync::KEY, [
                        'action' => 'sync',
                        'debug' => 1,
                        'limit' => (int) $prod_limit,
                    ] ), "View Mock Updates" ); ?>
                </p>
                <p>
                    <?= html_link_new_tab( ui::get_url( $sync::KEY, [
                        'action' => 'sync',
                        'mock_prices' => 1,
                        'limit' => (int) $prod_limit,
                    ] ), "View Mock Price Updates" ); ?>
                </p>
            </div>
            <?= ui::br(20); ?>
            <?= $sync->tracker->display_summary( true ); ?>
            <?= ui::br(); ?>
            <?= ui::pre_print_r( $sync ); ?>
            <?= ui::br(); ?>
            <?= ui::pre_print_r( $req ); ?>
        </div>
    </div>

    <div class="ps-tab-content" data-tab="prodInvalid">
        <p>Invalid products from within the suppliers file.</p>
        <p>Items shown limited to <?= (int) $prod_limit; ?>.</p>
        <p>Error counts:</p>
        <br>
        <?= ui::pre_print_r( Product_Sync::frequencies( array_column( $invalid_products, '__errors' ) ) ); ?>
        <br>
        <?= ui::render_table( null, ui::limit_items_shown( $invalid_products, $prod_limit ), [
            'sanitize' => true,
        ] ); ?>
    </div>

    <?php

    // if we're deleting the product because its invalid, add the errors to the
    // info displayed. We might be deleting the product because its in the DB but
    // not in the file
    $to_delete = array_map( function( $db_product ) use( $invalid_products ){
        $pn = $db_product['part_number'];
        $prod = @$invalid_products[$pn];
        $db_product['__errors'] = $prod ? $prod['__errors'] : 'Not in file.';
        return $db_product;
    }, $to_delete );

    ?>

    <div class="ps-tab-content" data-tab="prodDel">
        <p>If the product is sold in the other locale, it will be marked not sold in the current locale instead of deleted.</p>
        <p>Items shown limited to <?= (int) $prod_limit; ?>.</p>
        <?= ui::render_table( null, ui::limit_items_shown( $to_delete, $prod_limit ), [
            'sanitize' => true,
        ] ); ?>
    </div>

<?php

Footer::add_raw_html( function () {

    ?>
    <script>
        jQuery(document).ready(function ($) {

            (function () {

                $('body').on('submit', '.ps-accept-changes-form', function(e){
                    e.preventDefault();

                    var form = $(this);

                    if ( confirm( "Accept changes? This cannot be easily undone." ) ) {

                        if ( gp_body_is_loading() ) {
                            alert( "Please wait." );
                            return;
                        }

                        gp_body_loading_start();

                        $.ajax( {
                            url: form.attr('action'),
                            data: form.serializeArray(),
                            type: 'POST',
                            dataType: 'json',
                            error: function(a, b, c){
                                gp_body_loading_end();
                                alert( "Unexpected Error.");
                                console.error(a, b, c);
                            },
                            success: function( response ){
                                gp_body_loading_end();
                                console.log(response);
                                var success = response.success || false;
                                var msg = response.output || "No response";
                                $('.ps-response').empty().append(msg).show().css({
                                    background: success ? 'green' : 'red',
                                    marginBottom: 22,
                                    padding: '15px 25px',
                                    color: 'white',
                                });
                            }
                        });
                    }
                });

            })();


            (function () {
                $('.ps-tabs .controls div').on('click', function () {
                    var tab = $(this).attr('data-tab');
                    $.each($('.ps-tabs .controls div'), function () {

                        if ($(this).attr('data-tab') === tab) {
                            $(this).addClass('active');
                        } else {
                            $(this).removeClass('active');
                        }
                    });
                    $.each($('.ps-tab-content'), function () {
                        if ($(this).attr('data-tab') === tab) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                });
            })();

        });
    </script>
    <?php
} );
