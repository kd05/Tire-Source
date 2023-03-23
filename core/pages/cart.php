<?php

// config (before header)
Header::$title = "Cart";
has_no_top_image();

$cart = get_cart_instance();

$view = new Cart_Page( $cart );
set_global( 'view', $view );
set_global( 'page', 'cart' );

$ba = Billing_Address::create_empty();

// use these to possibly calculate the shipping value if its provided.
$country = app_get_locale();
$province = gp_if_set( $_POST, 'province' );

$sa = new Shipping_Address( '', '', '', '', $province, $country, '' );

// this global MUST be set to include TEMPLATES_DIR . '/cart/order-summary.php'
$receipt = new Cart_Receipt( $cart, $ba, $sa, false );

// had this off for a bit but... on now.
// and now its off.
// and now its on.
// and now its off.
$show_shipping_calculator = false;

set_global( 'receipt', $receipt );

cw_get_header();

?>
    <div class="page-wrap cart-page">
        <div class="main-content">
            <div class="container cart-container cart-header">
                <div class="cart-sidebar-flex">
                    <?php echo get_cart_and_checkout_top_content( 'Shopping Cart' ); ?>
                </div>
            </div>
            <div class="container cart-container cart-main">
                <div class="cart-sidebar-flex">
                    <div class="cs-left">
                        <div class="cart-section cs-main">
                            <div class="cart-title"><h2>Shopping Cart</h2></div>
                            <div class="cart-items" id="cart-items">
                                <?= $view->render_items(); ?>
                            </div>
                        </div>
                    </div>
                    <div class="cs-right">
                        <div class="cart-section order-summary">
					        <?php include TEMPLATES_DIR . '/cart/order-summary.php'; ?>
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