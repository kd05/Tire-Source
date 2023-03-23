<?php

Header::$title = "Order Details";
Header::$meta_robots = "noindex";

$order_id = (int) gp_if_set( $_GET, 'id' );
$order    = DB_Order::create_instance_via_primary_key( $order_id );
$user     = cw_get_logged_in_user();

if ( $user && $user->is_administrator() && $order ) {
	// admin can view any order if the order is a real order
} else if ( $order && $user && $order->user && $order->user->get( 'user_id' ) == $user->get( 'user_id' ) ) {
	// a non admin user is viewing their own order
} else {
	show_404();
	exit;
}

$order_date  = $order->get( 'order_date' );
$_order_date = convert_date_string_to_format( $order_date, 'M d, Y' );
$page_title  = 'Your ' . $_order_date . ' Order';

cw_get_header();

$status              = $order->get( 'order_status' );
$status_text         = map_order_status_to_text( $status, gp_test_input( $status ) );
$shipping_is_billing = (bool) $order->get( 'shipping_is_billing' );

?>
    <div class="page-wrap interior-page page-order-details">
		<?php echo get_top_image( array(
			'title' => $page_title,
			'img' => get_image_src( 'iStock-459906517-wide-lg.jpg' ),
			'overlay_opacity' => 61,
		) ); ?>
		<?php echo Components::grey_bar(); ?>
        <div class="main-content">
            <div class="container order-details-wrapper">
                <div class="general-titles">
                    <h2 class="main">Order ID: <?php echo $order->get( 'order_id' ); ?></h2>
                    <p class="sub-sm">Order Status: <?php echo $status_text; ?></p>
                </div>

                <div class="cart-section order-details-items">
                    <div class="cart-title"><h2>Items</h2></div>
                    <div class="cart-box">
			            <?php echo get_front_end_order_details_items_table( $order ); ?>
                    </div>
                </div>

                <div class="cart-section order-details-address">
                    <div class="cart-title">
                        <h2>Address</h2>
                    </div>
                    <div class="cart-box">
                        <div class="address-details">
                            <div class="adr-flex">
                                <div class="adr-col adr-billing">
                                    <div class="adr-col-2">
                                        <?php
                                        $with_company = true;
                                        $with_phone = true;
                                        $with_name = true;

                                        // I think we can omit the email here .. the user can edit their email via their profile page
                                        $with_email = false;

                                        ?>
                                        <p class="title">Billing</p>
										<?php
										$billing_array = $order->get_billing_address_summary_array( $with_company, $with_phone, $with_email, $with_name );
										echo gp_email_details( $billing_array );
										?>
                                    </div>
                                </div>
                                <div class="adr-col adr-shipping">
                                    <div class="adr-col-2">
                                        <p class="title">Shipping</p>
										<?php
										if ( $shipping_is_billing ) {
											echo '<p>' . get_shipping_is_billing_text( true ) . '</p>';
										} else {
											$shipping_array = $order->get_shipping_address_summary_array( $with_company, $with_phone, $with_email, $with_name );
											echo gp_email_details( $shipping_array );
										}
										?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cart-section order-details-receipt">
                    <div class="cart-title">
                        <h2>Receipt</h2>
                    </div>
                    <div class="cart-box">
						<?php echo get_front_end_order_details_receipt( $order ); ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
<?php

cw_get_footer();

