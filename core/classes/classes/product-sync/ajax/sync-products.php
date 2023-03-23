<?php

if ( ! cw_is_admin_logged_in() ) {
    echo "Error.";
    exit;
}

// might exit
Ajax::check_global_nonce();

$sync = Product_Sync::get_via_key( $_POST['sync'] );
$req = DB_Sync_Request::create_instance_via_primary_key( @$_POST['req_id'] );
$accept_type = @$_POST['accept_type'];

$send_err = function( $msg ) {
    Ajax::echo_response([
        'success' => false,
        'output' => $msg,
    ]);
};

if ( $accept_type === 'prices' || $accept_type === 'all' ) {

    if ( $sync && $req && $sync::KEY === $req->get( 'sync_key' ) ) {

        $sync->assertions();

        $all_price_rules = Product_Sync_Compare::get_cached_indexed_price_rules();
        list( $valid_products, $invalid_products ) = $sync->load_products_from_disk( $req->get_primary_key_value() );

        if ( $accept_type === 'prices' ) {

            list( $count_0, $count_1, $errs_0, $errs_1, $count_ex_products_same ) =
                Product_Sync_Update::accept_price_changes( $sync, $valid_products, $invalid_products, $req->get_primary_key_value(), true, 'manual_sync' );

            $count_all = $count_0 + $count_1;
            $count_errs = $errs_0 + $errs_1;

            $msg = "$count_all product(s) updated with $count_errs error(s). $count_ex_products_same existing product(s) had the same prices as what's in the file. $count_1 product(s) were found in the database that are not in the supplier file and they have been marked not sold. You should run the inventory for this supplier now, as some products may have gone from not sold to sold in CA/US, or the other way around.";

            Ajax::echo_response( [
                'success' => true,
                'output' => $msg,
                'mem' => $sync->tracker->breakpoints,
            ]);

            exit;

        }

        Product_Sync_Update::accept_all_changes( $sync, $valid_products, $req->get_primary_key_value() );

        // make all inventory feeds run on next cron job (even if the file hasn't changed since last time)
        Supplier_Inventory_Hash::delete_all_hashes();

        Ajax::echo_response([
            'success' => true,
            'output' => 'Products updated. Re-load the page to confirm that everything worked (there should be nothing left to insert/change). After that, click the Run Inventory button at the top of the page.',
        ]);

        exit;

    } else {
        $send_err("Sync or Request not Valid." );
        exit;
    }


} else {
    $send_err( "Please choose an option." );
    exit;
}

exit;

