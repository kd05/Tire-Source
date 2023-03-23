<?php

$get = function( $key ) {
    return gp_test_input( gp_if_set( $_POST, $key, "" ) );
};

if ( ! isset( $_POST['submitted'] ) ) {
    $_POST['card'] = '4111111111111111';
    $_POST['month'] = '12';
    $_POST['year'] = '2024';
    $_POST['cvv'] = '123';
    $_POST['street_number'] = '123';
    $_POST['street_name'] = 'fake street';
    $_POST['postal'] = 'L1L3L3';
}

?>

<p>Use with caution. Authorizes a payment, but does not capture it. However, funds may still be withheld.</p>

<form action="" method="post">

    <input type="hidden" name="submitted" value="1">
    card: <input type="text" name="card" value="<?= $get( 'card' ); ?>"><br>
    month: <input type="text" name="month" value="<?= $get( 'month' ); ?>"><br>
    year: <input type="text" name="year" value="<?= $get( 'year' ); ?>"><br>
    cvv: <input type="text" name="cvv" value="<?= $get( 'cvv' ); ?>"><br>

    street_number: <input type="text" name="street_number" value="<?= $get( 'street_number' ); ?>"><br>
    street_name: <input type="text" name="street_name" value="<?= $get( 'street_name' ); ?>"><br>
    postal: <input type="text" name="postal" value="<?= $get( 'postal' ); ?>"><br>

    <button type="submit">Submit</button>

</form>

<?php

if ( @$_POST['submitted'] ) {

    $payment = new App_Moneris_Pre_Auth_Capture( app_get_locale() );
    $payment->set_order_id( "TEST-" . time() );
    $payment->set_amount( "1.00" );
    $payment->set_card_number( $get( 'card' ) );
    $payment->set_card_month( $get( 'month') );
    $payment->set_card_year( $get( 'year') );
    $payment->set_cvv( $get( 'cvv' ) );
    $payment->set_avs_street_number( $get( 'street_number') );
    $payment->set_avs_street_name( $get( 'street_name') );
    $payment->set_avs_zipcode( $get( 'postal') );

    echo "Attempting Preauth. Env: " . $payment->config->environment;

    // Send the request
    $payment->preauth();

    $receipt = $payment->preauth->receipt();

    $dump = [
        'failedAvs' => $payment->preauth->failedAvs,
        'failedCvd' => $payment->preauth->failedCvd,
        'success' => $payment->preauth_success(),
    ];

    $receipt_fields = [
        'code',
        'message',
        'avs_result',
        'cvd_result',
        // this is card type not card number
        'card',
        'authorization',
        'transaction',
        'reference',
    ];

    foreach ( $receipt_fields as $field ) {
        $dump[$field] = $receipt ? $receipt->read( $field ) : "__no_receipt";
    }

    echo get_pre_print_r( $dump, true );

    echo "<br><br>";

    echo "Attempting to void (ie. capture with $0.00 amount)...";

    echo "<br><br>";

    $void_success = $payment->capture_preauth_with_amt_zero();

    echo $void_success ? "Success" : "Error";
}

