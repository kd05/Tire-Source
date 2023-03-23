<?php

if ( ! @$_POST['submitted'] ) {
    $_POST['avs_response'] = 'A';
    $_POST['cvd_response'] = 'A';

    $_POST['ip_address'] = app_get_ip_address();
    $_POST['amount'] = '100.00';
    $_POST['order_id'] = '100';

    $_POST['email'] = 'fake@email.com';
    $_POST['payment_token'] = '4111111111111111';

    $_POST['prod_type'] = 'type';
    $_POST['prod_item'] = 'the-sku-123';
    $_POST['prod_desc'] = 'a product thing';
    $_POST['prod_quant'] = '4';
    $_POST['prod_price'] = '25.00';
}

$get = function( $key ) {
    return gp_test_input( @$_POST[$key] );
};

?>

<p>Your IP Address: <?= app_get_ip_address(); ?></p>
<p>Default generated session ID: <?= App_Kount::get_session_id(); ?></p>

    <form action="" method="post">

        <input type="hidden" name="submitted" value="1">

        email: <input type="text" name="email" value="<?= $get( 'email' ); ?>"><br>
        order_id: <input type="text" name="order_id" value="<?= $get( 'order_id' ); ?>"><br>
        ip_address: <input type="text" name="ip_address" value="<?= $get( 'ip_address' ); ?>"><br>
        payment_token: <input type="text" name="payment_token" value="<?= $get( 'payment_token' ); ?>"><br>
        amount: <input type="text" name="amount" value="<?= $get( 'amount' ); ?>"><br>
        avs_response: <input type="text" name="avs_response" value="<?= $get( 'avs_response' ); ?>"><br>
        cvd_response: <input type="text" name="cvd_response" value="<?= $get( 'cvd_response' ); ?>"><br>
        session_id: <input type="text" name="session_id" value="<?= $get( 'session_id' ); ?>"><br>
        prod_type: <input type="text" name="prod_type" value="<?= $get( 'prod_type' ); ?>"><br>
        prod_item: <input type="text" name="prod_item" value="<?= $get( 'prod_item' ); ?>"><br>
        prod_desc: <input type="text" name="prod_desc" value="<?= $get( 'prod_desc' ); ?>"><br>
        prod_quant: <input type="text" name="prod_quant" value="<?= $get( 'prod_quant' ); ?>"><br>
        prod_price: <input type="text" name="prod_price" value="<?= $get( 'prod_price' ); ?>"><br>
        test_mode: <input type="checkbox" name="test_mode" value="yes" checked><br>

        <button type="submit">Do Kount Inquiry</button>

    </form>

<?php

if ( @$_POST['submitted'] ) {

    $test_mode = @$_POST['test_mode'] === 'yes';
    $locale = APP_LOCALE_CANADA;

    // stupid checkboxes
    $_POST['test_mode'] = $test_mode ? 'yes' : 'no';

    // will sanitize
    echo get_pre_print_r( $_POST, true );

    $store_id = App_Moneris_Config::get_store_id( $locale, $test_mode );
    $api_token = App_Moneris_Config::get_api_token( $locale, $test_mode );

    $transaction = App_Kount::build_inquiry_txn( ! $test_mode, [
        'type' => 'kount_inquiry',
        'payment_type' => 'CARD',
        'email' => $get( 'email' ),
        'order_id' => $get( 'order_id' ),
        'ip_address' => $get( 'ip_address' ),
        'payment_token' => $get( 'payment_token' ),
        'amount' => $get( 'amount' ),
        'avs_response' => $get( 'avs_response' ),
        'cvd_response' => $get( 'cvd_response' ),
        'session_id' => $get( 'session_id' ),
    ]);

    $transaction = Kount_Service::filter_validate_inquiry_txn( $transaction, [
        Kount_Service::build_product($_POST['prod_type'], $_POST['prod_item'], $_POST['prod_desc'], $_POST['prod_quant'], $_POST['prod_price'] ),
    ] );

    $txn_to_print = $transaction;
    unset( $txn_to_print['kount_api_key'] );

    echo "<br>";
    echo "Request Array:";
    echo "<br>";

    echo get_pre_print_r( $txn_to_print, true );

    $response = Kount_Service::send_request( $transaction, $store_id, $api_token, $test_mode );

    echo "<br>";
    echo "Response XML:";
    echo "<br>";

    echo htmlspecialchars( Kount_Service::$last_response_xml );

    echo "<br>";
    echo "Response Array: \r\n";
    echo "<br>";

    echo get_pre_print_r( $response, true );
}


