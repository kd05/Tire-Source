<?php
/**
 * This template file is included from several places, one of them is on the cart page, and
 * others are return values from other ajax calls that run on the cart page and or the checkout page.
 */

// which file did we originate from? 'cart' or 'checkout'?
$page = get_global( 'page' );

$cart = get_cart_instance();

/**
 *
 * @var Cart_Receipt
 */
$receipt = get_global( 'receipt' );

$receipt = $receipt instanceof Cart_Receipt ? $receipt : new Cart_Receipt( $cart, Billing_Address::create_empty(), Shipping_Address::create_empty(), false );

$subtotal_display = print_price_dollars_formatted( $receipt->subtotal  );

// making a special exception to the displayed shipping price when not enough information is present
// to determine a value according to the database. Why? because this value according to the database
// is most likely zero, and we have banners on the site that say "Free Shipping".
// Note that there may still be cart messages that say "Please fill in all fields so we can determine your shipping rate"
// but this way, people won't see a FREE SHIPPING banner and then in their cart summary see shipping is "TBD".. that would look quite bad.
// Also, keep in mind we're not saying "FREE" here because there is no guarantee its free, it's whatever the price is according
// to the database which is likely zero but doesn't have to be.
$shipping_tbd = '$0.00';

$shipping_display = $receipt->shipping_to_be_determined ? $shipping_tbd : print_price_dollars_formatted( $receipt->shipping );
$tax_display = $receipt->tax_to_be_determined ? 'TBD' : print_price_dollars_formatted( $receipt->tax );

// $total_display = $receipt->total_is_to_be_determined() ? 'TBD' : print_price_dollars( $receipt->total );
// maybe just print the amount? I think people will get the idea.. or maybe they won't, I don't know.
// but why would we even have a receipt just to show a subtotal, and then 3 values with 'tbd'
$total_display = print_price_dollars_formatted( $receipt->total );

?>

<div class="cart-title">
	<h2>Order Summary</h2>
</div>
<div class="cart-box">
    <?php

    echo render_cart_receipt_from_primitive_values(
        $receipt->subtotal,
        $receipt->shipping,
        $receipt->tax,
        $receipt->total,
        app_get_locale(),
        $receipt->ontario_fee,
        get_ontario_tire_levy_cart_text( false, $receipt->ontario_fee_qty, get_ontario_tire_levy_amt() )
    );

    ?>

    <?php

    if ( $page === 'cart' ) {

        if ( $cart->is_every_item_in_stock() ) {
	        echo '<div class="cart-checkout button-1">';
	        echo '<a href="' . get_url( 'checkout' ) . '">Check Out</a>';
	        echo '</div>';
        }

	    echo '<div class="cart-checkout-quantity-message hidden">';
	    echo '<p>Quantities changed, hit update to refresh these values.</p>';
	    echo '</div>';
    }

    ?>

</div>
