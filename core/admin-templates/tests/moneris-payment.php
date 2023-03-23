<?php

echo "nothing here at the moment.";

assert( ! IN_PRODUCTION );

//$payment = new App_Moneris_Pre_Auth_Capture( $locale );
//
//print_dev_alert( "Config", $payment->config );
//print_dev_alert( "Gateway", $payment->gateway );
//
//$payment->set_amount( '1.00' );
//$payment->set_random_order_id();
//$payment->set_card_number( $payment->get_testing_card_number() );
//$payment->set_card_month( '13');
//$payment->set_card_year( '20' );
//$payment->set_cvv( '123' );
//$payment->set_avs_street_name( 'Fake Street' );
//$payment->set_avs_street_number( '123' );
//$payment->set_avs_zipcode( '11101' );
//
//$payment->preauth();
//
//print_dev_alert( 'params', $payment->get_params() );
//print_dev_alert( 'verify', $payment->verify );
//print_dev_alert( 'preauth', $payment->preauth );
//
//if ( $payment->preauth_success() ) {
//    // Capture
//    $payment->capture();
//
//    print_dev_alert( 'capture', $payment->capture );
//
//    if ( $payment->capture_success() ) {
//        print_dev_alert( 'success', $payment->capture );
//    }
//}
//
//print_dev_alert( 'verify success: ' . get_string_for_log( $payment->verify_success() ) );
//print_dev_alert( 'preauth success: ' . get_string_for_log( $payment->preauth_success() ) );
//print_dev_alert( 'capture success: ' . get_string_for_log( $payment->capture_success() ) );