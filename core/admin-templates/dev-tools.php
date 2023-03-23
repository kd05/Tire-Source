<?php

if ( ! cw_is_admin_logged_in() ) {
    exit;
}

//echo 'SUPPLIER ';
//
//$db = get_database_instance();
//
//var_dump( $db->filter_param_type( 'asd' ) );
//var_dump( $db->filter_param_type( 's' ) );
//var_dump( $db->filter_param_type( '%s' ) );
//var_dump( $db->filter_param_type( '%d' ) );
//
//
//DB_Supplier::register( array(
//    'supplier_slug' => 'asdfasdfasdf',
//    'supplier_name' => 'ASDHASIUYDGASD',
//));

page_title_is( 'Dev Tools' );

$r1 = 'asdh9asd7h87gbhasd';
$r2 = 'asd9i7gas8du6gytasd';

$form_submit = gp_if_set( $_POST, 'form_submit' );
$empty_cache = gp_if_set( $_POST, 'empty_cache' );
$empty_session = gp_if_set( $_POST, 'empty_session' );
$empty_cart = gp_if_set( $_POST, 'empty_cart' );
$clear_supplier_inventory_hash = gp_if_set( $_POST, 'clear_supplier_inventory_hash' );

$reset_tire_inventory = gp_if_set( $_POST, 'reset_tire_inventory' ) == $r1;
$reset_rim_inventory = gp_if_set( $_POST, 'reset_rim_inventory' ) == $r2;

$fake_all_stock_levels = ! IN_PRODUCTION && (int) gp_if_set( $_POST, 'fake_all_stock_levels' ) === 1;

//if ( isset( $_GET['do_test'] ) && $_GET['do_test'] == 1 ) {
//
//	$ff = select_all_from_table( DB_rim_finishes );
//
//	foreach ( $ff as $f1=>$f2 ) {
//
//	    $obj = DB_Rim_Finish::create_instance_or_null( $f2 );
//
//	    if ( $obj ) {
//
//	        $data = array(
//		        'image_source_new' => $obj->fix_thickbox_url( $obj->get( 'image_source_new' ) ),
//            );
//
//	        // $obj->update_database_and_re_sync( $data );
//		    // echo $data['image_source_new'] . '<br>';
//		    // echo get_form_response_text( get_pre_print_r( $data ) );
//        }
//    }
//}

//$brand_logos = gp_if_set($_REQUEST, 'brand_logos');
//if ($brand_logos) {
//    brand_logos_db_init(true);
//    brand_logos_db_init(false);
//}

$msg = '';

if ( (int) $form_submit === 1 ) {

    if ( $empty_cache ) {
        gp_cache_empty();
        $msg = 'Cache Cleared.';
    }

    if ( $empty_session ) {
        $_SESSION = array();
        $msg = 'Session Cleared.';
    }

    if ( $empty_cart ) {
        $_SESSION[ SESSION_CART_CA ] = array();
        $_SESSION[ SESSION_CART_US ] = array();
        $msg = 'Cart Emptied.';
    }

    if ( $clear_supplier_inventory_hash ) {
        Supplier_Inventory_Hash::delete_all_hashes();
        $msg = 'The next time the inventory cron job runs, it will attempt to process all supplier files regardless of whether or not the files changed.';
    }

    if ( $reset_tire_inventory ) {
        reset_tire_inventory();
        $msg = 'Tire Inventory Reset.';
    }

    if ( $reset_rim_inventory ) {
        reset_rim_inventory();
        $msg = 'Rim Inventory Reset.';
    }

    if ( $fake_all_stock_levels && ! IN_PRODUCTION ) {
        fake_stock_levels( 'tires', APP_LOCALE_CANADA );
        fake_stock_levels( 'tires', APP_LOCALE_US );
        fake_stock_levels( 'rims', APP_LOCALE_CANADA );
        fake_stock_levels( 'rims', APP_LOCALE_US );
        $msg = 'All Stock Levels Randomly Generated.';
    }

    if ( ! $msg ) {
        $msg = 'no action taken';
    }
}

cw_get_header();
Admin_Sidebar::html_before();

//if ( isset( $_GET['region_init'] ) ) {
//    include CORE_DIR . '/db-init/insert-regions.php';
//}
//
//if ( isset( $_GET['shipping_init'] ) ) {
//	include CORE_DIR . '/db-init/dummy-shipping-rates.php';
//}

?>

    <div class="admin-section general-content">
        <h1>Developer Tools</h1>
        <form action="" method="post">
            <?php echo wrap_tag( wrap_tag( $msg, 'strong' ) ) . '<br>'; ?>
            <input type="hidden" name="form_submit" value="1">
            <div class="form-items general-content">
                <p>The <a href="<?php echo get_admin_archive_link( DB_cache ); ?>">Database Cache</a> stores hard to get
                    information (temporarily) for quick access. Its main purpose is vehicle data. Most items expire
                    automatically on their own. The cache may need to be emptied if the code changes. Emptying the cache
                    is safe to do at any time.</p>
                <p>
                    <button type="submit" name="empty_cache" value="1">Empty Database Cache</button>
                </p>
                <p>Emptying the session will log you out, empty your cart, and may get rid of a few other pieces of
                    data.</p>
                <button type="submit" name="empty_session" value="1">Empty Session</button>
                <button type="submit" name="empty_cart" value="1">Empty Cart</button>
                <button type="submit" name="clear_supplier_inventory_hash" value="1">Ignore previous stock updates on
                    next cron job
                </button>
                <?php
                if ( ! IN_PRODUCTION ) {
                    ?>
                    <p>Reset product inventory data (all products will be in stock until next cron job). You generally
                        do
                        not need to do this.</p>
                    <button type="submit" name="reset_tire_inventory" value="<?php echo $r1; ?>">Reset Tire Inventory
                    </button>
                    <button type="submit" name="reset_rim_inventory" value="<?php echo $r2; ?>">Reset Rim Inventory
                    </button>

                    <button type="submit" name="fake_all_stock_levels" value="1">Fake All Stock Levels (Dev only)
                    </button>
                    <?php
                }
                ?>
            </div>
        </form>

        <hr>
        <form class="ajax-general form-style-basic" action="<?php echo AJAX_URL; ?>" id="insert-page">

            <?php echo get_ajax_hidden_inputs_for_general_ajax( 'insert_page' ); ?>
            <div class="form-items">
                <p>ie. for <a href="<?= ADMIN_URL . '?page=edit&table=pages'; ?>">Edit Pages</a></p>

                <?php

                echo get_form_input( array(
                    'label' => 'Insert New Page (hint: "__autofill")',
                    'tooltip' => 'test',
                    'name' => 'page_name',
                    'value' => '',
                ) );

                ?>
                <p>
                    <input id="delete_page_instead" type="checkbox" value="1" name="delete_page_instead">
                    <label for="delete_page_instead">Delete page instead</label>
                </p>

                <div class="item-wrap">
                    <button type="submit">Submit</button>
                </div>
            </div>
        </form>
        <hr>
        <br>
        <?php $url = BASE_URL . '/packages?make=bmw&model=3-series&year=2015&trim=0a7f590e47&fitment=F~225-50ZR17-7.5Jx17_ET37R~225-50ZR17-8.5Jx17_ET47-staggered'; ?>
        <p>Link to vehicle with staggered fitment: <a href="<?= $url; ?>">Click here</a></p>
        <p>

    </div>
<?php

// http://localhost:8080/tiresource/cw-admin/?page=home&fake_stock_levels=1&tbl=ALL
if ( ! IN_PRODUCTION && isset( $_GET[ 'fake_stock_levels' ] ) && $_GET[ 'fake_stock_levels' ] = 1 ) {
    echo 'generating stock levels.....';

    if ( $_GET[ 'tbl' ] === 'ALL' ) {
        fake_stock_levels( 'tires', APP_LOCALE_CANADA );
        fake_stock_levels( 'tires', APP_LOCALE_US );
        fake_stock_levels( 'rims', APP_LOCALE_CANADA );
        fake_stock_levels( 'rims', APP_LOCALE_US );
    } else {
        fake_stock_levels( $_GET[ 'tbl' ], $_GET[ 'locale' ] );
    }
}

Admin_Sidebar::html_after();
cw_get_footer();