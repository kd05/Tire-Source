<?php

$pk = (int) @$_GET['pk'];

// get model again even if not updated
$db_trans = DB_Transaction::create_instance_via_primary_key( $pk );

$db_order = $db_trans->get_order_id( true );
$order_id = @$db_order->get_primary_key_value();

$orders_page_url = get_admin_single_order_url( $order_id );
$orders_table_page_url = get_admin_single_edit_link( DB_orders, $order_id );

$preauth = @$db_trans->get_txn_extra()['preauth'];
$capture = @$db_trans->get_txn_extra()['capture'];


?>

<div class="admin-section general-content">

    <h1>Transaction <?= $pk ?></h1>
    <p>All Transactions: <?= gp_get_link( get_admin_archive_link( DB_transactions ), "Click here." ); ?></p>
    <p>Orders page: <?= gp_get_link( $orders_page_url, "Click here." ); ?></p>
    <p>Orders table page: <?= gp_get_link( $orders_table_page_url, "Click here." ); ?></p>

    <?= render_html_table_admin( false, [ $db_trans->to_array_for_admin_tables() ], [] ); ?>

    <p>Inquiry Request:</p>

    <?= get_pre_print_r( $db_trans->get_kount_inquiry_request(), true ); ?>

    <p>Inquiry Response:</p>

    <?= get_pre_print_r( $db_trans->get_kount_inquiry_response(), true ); ?>

    <p>Update Request:</p>

    <?= get_pre_print_r( $db_trans->get_kount_update_request(), true ); ?>

    <p>Update Response:</p>

    <?= get_pre_print_r( $db_trans->get_kount_update_response(), true ); ?>

    <p>Preauth:</p>

    <?= get_pre_print_r( $preauth, true ); ?>

    <p>Capture:</p>

    <?= get_pre_print_r( $capture, true ); ?>

</div>

<?php

