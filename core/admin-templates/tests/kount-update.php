<?php

if ( ! @$_POST[ 'submitted' ] ) {
    $_POST[ 'order_id' ] = '100';
    $_POST[ 'evaluate' ] = 'false';
    $_POST[ 'payment_token' ] = '4111111111111111';
}

$get = function( $key ) {
    return gp_test_input( @$_POST[$key] );
}

?>
    <p>Default generated session ID: <?= App_Kount::get_session_id(); ?></p>

    <form action="" method="post">

        <input type="hidden" name="submitted" value="1">

        kount_transaction_id: <input type="text" name="kount_transaction_id" value="<?= $get( 'kount_transaction_id' ); ?>"><br>
        session_id: <input type="text" name="session_id" value="<?= $get( 'session_id' ); ?>"><br>
        order_id: <input type="text" name="order_id" value="<?= $get( 'order_id' ); ?>"><br>
        evaluate: <input type="text" name="evaluate" value="<?= $get( 'evaluate' ); ?>"><br>
        refund_status: <input type="text" name="refund_status" value="<?= $get( 'refund_status' ); ?>"><br>
        payment_response: <input type="text" name="payment_response" value="<?= $get( 'payment_response' ); ?>"><br>
        avs_response: <input type="text" name="avs_response" value="<?= $get( 'avs_response' ); ?>"><br>
        cvd_response: <input type="text" name="cvd_response" value="<?= $get( 'cvd_response' ); ?>"><br>
        financial_order_id: <input type="text" name="financial_order_id" value="<?= $get( 'financial_order_id' ); ?>"><br>
        payment_token: <input type="text" name="payment_token" value="<?= $get( 'payment_token' ); ?>"><br>
        test_mode: <input type="checkbox" name="test_mode" value="yes" checked><br>

        <button type="submit">Do Kount Update</button>

    </form>

<?php

if ( @$_POST[ 'submitted' ] ) {

    $test_mode = @$_POST[ 'test_mode' ] === 'yes';
    $locale = APP_LOCALE_CANADA;

    // stupid checkboxes
    $_POST[ 'test_mode' ] = $test_mode ? 'yes' : 'no';

    // escapes html
    echo get_pre_print_r( $_POST, true );

    $store_id = App_Moneris_Config::get_store_id( $locale, $test_mode );
    $api_token = App_Moneris_Config::get_api_token( $locale, $test_mode );

    $transaction = App_Kount::build_update_txn( ! $test_mode, [
        'kount_transaction_id' => $get( 'kount_transaction_id' ),
        'session_id' => $get( 'session_id' ),
        'order_id' => $get( 'order_id' ),
        'evaluate' => $get( 'evaluate' ),
        'refund_status' => $get( 'refund_status' ),
        'payment_response' => $get( 'payment_response' ),
        'avs_response' => $get( 'avs_response' ),
        'cvd_response' => $get( 'cvd_response' ),
        'financial_order_id' => $get( 'financial_order_id' ),
    ] );

    $transaction = Kount_Service::filter_validate_update_txn( $transaction );

    $txn_to_print = $transaction;
    unset( $txn_to_print[ 'kount_api_key' ] );

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


