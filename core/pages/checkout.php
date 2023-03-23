<?php

// TEMPLATES_DIR . '/cart/order-summary.php' needs to know about this
// config (before header)
Header::$title = "Checkout";
has_no_top_image();

$user = cw_get_logged_in_user();

$cart = get_cart_instance();

$cart_items_all_in_stock = $cart->is_every_item_in_stock();

$ba      = Billing_Address::create_empty();
$sa      = Shipping_Address::create_empty();
$receipt = new Cart_Receipt( $cart, $ba, $sa );

// this global MUST be set to include TEMPLATES_DIR . '/cart/order-summary.php'
set_global( 'receipt', $receipt );

// for cart/order-summary.php
set_global( 'page', 'checkout' );

// tell the header to print this
set_global( 'print_kount_data_collector', true );

// Header
cw_get_header();

// these need to be in 2 places (desktop/mobile). therefore, store output in a variable.
ob_start();
include TEMPLATES_DIR . '/cart/cart-summary.php';
$cart_summary = ob_get_clean();

ob_start();
include TEMPLATES_DIR . '/cart/order-summary.php';
$order_summary = ob_get_clean();

//$checkout = new Checkout_Submit( $_POST, $cart );
//$checkout->insert_order();
//echo nl2br( "-----------------------  \n" );
//echo $checkout->email_items_table();
//echo nl2br( "-----------------------  \n" );
//$checkout->send_admin_email();

//$checkout->insert_order();
//echo '<pre>' . print_r( $checkout, true ) . '</pre>';

$lightbox_id      = 'sign_in';
if ( ! $user ) {
	$lightbox_content = get_general_lightbox_content( $lightbox_id, get_sign_in_form( [ 'reload' => true ] ), [ 'add_class' => 'sign-in' ] );
	echo $lightbox_content;
}

?>
    <div class="page-wrap interior-page page-checkout">
        <div class="main-content">
            <div class="container cart-container cart-header">
                <div class="cart-sidebar-flex">
					<?php echo get_cart_and_checkout_top_content( 'Checkout' ); ?>
                </div>
            </div>
            <div class="container cart-container cart-main">
                <div class="cart-sidebar-flex">
                    <div class="cs-left">
                        <?php
                        if ( $cart_items_all_in_stock ) {
	                        include TEMPLATES_DIR . '/cart/checkout-form.php';
                        } else {
                            ?>
                            <div class="cart-section checkout-no-stock">
                                <div class="cart-title"><h2>Cart Item Availability Notice</h2></div>
                                <div class="cart-box">
                                    <p class="like-p-large"><?php echo $cart::get_out_of_stock_msg_with_link_to_cart_page(); ?></p>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    <div class="cs-right">
                        <div class="cart-section cart-summary">
							<?php echo $cart_summary; ?>
                        </div>
                        <div class="cart-section order-summary">
							<?php echo $order_summary; ?>
                        </div>
                        <div class="cart-section policies">
                            <div class="cart-title">
                                <h2>Policies</h2>
                            </div>
                            <div class="cart-box">
                                <?php include CORE_DIR . '/templates/policy-panel.php'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php

cw_get_footer();