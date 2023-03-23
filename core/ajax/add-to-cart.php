<?php

$cart = Cart::load_instance();
$vehicle = Vehicle::create_instance_from_user_input( $_POST );

$count_before = $cart->count_items();

$success = false;
$html = '';
$update = false;

try{

	// each Add_To_Cart_Handler instance has its own Vehicle_General instance, so if you need to handle more than 1 vehicle in a single
	// submission then you'll have to use more than one Add_To_Cart_Handler instance.
	$atc = new Add_To_Cart_Handler( $cart, $vehicle );
	$success = $atc->run_user_data( $_POST );

	// nothing is permanent if we don't do this.
	if ( $success ) {
		$cart->commit();
	}

	$alert = IN_PRODUCTION ? '' : gp_array_to_js_alert( $atc->get_response() );
	$update = true; // if not true, $_SESSION won't be updated...

} catch( Exception $e ) {
	$success = false;
	$update = false;
	$alert = IN_PRODUCTION ? '' : $e->getMessage();
}

// nothing is permanent until we do this.
if ( $update ) {
	$cart->commit();
}

$count_after = $cart->count_items();

if ( $success ) {
	$html = '';
	$html .= '<div class="cart-msg">';
	$html .= '<p>Your item(s) have been added to the cart.</p>';

	if ( $count_before === 0 ) {
		$alert = get_add_to_cart_locale_alert();
		if ( $alert ) {
			$html .= '<p class="atc-locale-alert">' . $alert . '</p>';
		}
	}

	$html .= '<div class="buttons align-center">';
	$html .= '<div class="button-1 color-red"><button class="lb-close">Continue Shopping</button></div>';
	$html .= '<div class="button-1 color-black"><a href="' . get_url( 'cart' ) . '">View Cart</a></div>';
	$html .= '</div>';
	$html .= '</div>';
} else {
	$html = '';
	$html .= '<div class="cart-msg cart-msg-error">';
	$html .= '<p>There was an error adding items to your cart.</p>';
	$html .= '</div>';
}

// use this first for debugging if you need to
//if ( ! IN_PRODUCTION ) {
//	$html .= get_dev_alert( 'debug', $alert );
//}

// indicate success to javascript, though we may not do anything with it
$response['success'] = $success;

// probably not using in production
//if ( $alert ) {
//	$response['alert'] = $alert;
//}

$add_class = 'add-to-cart-response';
$add_class .= $success ? ' success' : ' error';

if ( $html ) {
	$response['actions'][] = array(
		'action' => 'lightbox',
		'add_class' => $add_class,
		'content' => $html,
		'close_btn' => true,
	);
}

$response['cart_count'] = $count_after;

Ajax::echo_response( $response );
exit;