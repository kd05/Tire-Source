<?php

?>

<!-- Form -->
<form id="checkout" class="checkout form-style-1" action="<?php echo AJAX_URL; ?>">
	<?php echo get_ajax_hidden_inputs( 'checkout' ); ?>
    <input type="hidden" name="app_locale" value="<?php echo app_get_locale(); ?>">
    <div class="cart-section billing">
        <div class="cart-title"><h2>Billing Information</h2></div>
        <div class="cart-box">
            <div class="cart-box-2">
				<?php
				if ( ! $user ) {
					echo '<div class="open-lightbox-wrapper">';
					// note: put lightbox content outside of the form we're in
					echo '<button type="button" class="css-reset lb-trigger" data-for="' . $lightbox_id . '">Sign In</button>';
					echo '</div>';
				}
				?>
                <div class="form-items">
					<?php

					// first
					echo get_form_input( array(
						'req' => true,
						'name' => 'first_name',
						'label' => 'First Name',
					) );

					// last
					echo get_form_input( array(
						'req' => true,
						'name' => 'last_name',
						'label' => 'Last Name',
					) );

					// cannot show email when a user is logged in because then
					// we could have 2 emails and we wouldn't know what the mailing list consent box is for
					if ( ! $user ) {
						// email
						echo get_form_input( array(
							'name' => 'email',
							'label' => 'Email',
						) );
					}

					// phone
					echo get_form_input( array(
						'req' => true,
						'name' => 'phone',
						'label' => 'Phone',
					) );

					// company
					echo get_form_input( array(
						'name' => 'company',
						'label' => 'Company',
					) );

					echo get_address_form_inputs( array() );

					if ( ! $user ) {

						// register
						echo get_form_checkbox( array(
							'split' => 1,
							'name' => 'register',
							'value' => 1,
							'label' => 'Create an account on checkout',
						) );

						// Password 1
						echo get_form_input( array(
							'req' => true,
							'label' => 'Password',
							'add_class_1' => 'hidden',
							'name' => 'password_1',
							'type' => 'password',
						) );

						// Password 2
						echo get_form_input( array(
							'req' => true,
							'label' => 'Confirm Password',
							'add_class_1' => 'hidden',
							'name' => 'password_2',
							'type' => 'password',
						) );
					}
					?>
                </div>
            </div>
        </div>
    </div>
    <div class="cart-section shipping">
        <div class="cart-title"><h2>Shipping Information</h2></div>
        <div class="cart-box">
            <div class="cart-box-2">
                <div class="shipping-sub-section form-items sub-section-address active">
					<?php

					// Ship To Home (Radio Button)
					//											echo get_form_checkbox( array(
					//												'type' => 'radio',
					//												'name' => 'ship_to',
					//												'value' => 'address',
					//												'id' => 'ship_to_address',
					//												'label' => checkout_form_get_ship_to_text( 'address', app_get_locale() ),
					//												'checked' => 1,
					//											) );

					?>
                    <div class="body">
						<?php

						// Shipping Is Billing checkbox
						echo get_form_checkbox( array(
							'split' => 1,
							'type' => 'checkbox',
							'name' => 'shipping_is_billing',
							'value' => 1,
							'label' => get_shipping_is_billing_text( true ),
							'checked' => 1,
						) );

						?>
                        <div class="form-items shipping-items hidden">
							<?php

							// first
							echo get_form_input( array(
								'req' => true,
								'name' => 'sh_first_name',
								'label' => 'First Name',
							) );

							// last
							echo get_form_input( array(
								'req' => true,
								'name' => 'sh_last_name',
								'label' => 'Last Name',
							) );

							// company
							echo get_form_input( array(
								'req' => true,
								'name' => 'sh_company',
								'label' => 'Company',
							) );

							// phone
							echo get_form_input( array(
								'req' => true,
								'name' => 'sh_phone',
								'label' => 'Phone',
							) );

							// email
							//		                                echo get_form_input( array(
							//			                                'name' => 'email',
							//			                                'placeholder' => 'Email',
							//		                                ));

							echo get_address_form_inputs( array(
								'name_pre' => 'sh_',
							), true );
							?>
                        </div>

                    </div>
                </div>

				<?php if ( false ) {
					// local pickup is no longer wanted as an option
					// if you turn it back on, well I don't know.. test it.
					// the form submission may still handle it as possibility, and the styles
					// may or may not need changing. there is (was) also javascript logic to
					// show/hide certain things.
					?>

                    <div class="shipping-sub-section form-items sub-section-pickup not-active">
						<?php

						// Local Pickup
						echo get_form_checkbox( array(
							'type' => 'radio',
							'name' => 'ship_to',
							'value' => 'pickup',
							'id' => 'ship_to_pickup',
							'disabled' => app_get_locale() == 'US' ? true : false,
							'label' => checkout_form_get_ship_to_text( 'pickup', app_get_locale() ),
							'checked' => false,
						) );
						?>
                        <div class="body">
                            <p>...</p>
                        </div>
                    </div>

				<?php } ?>

            </div>
        </div>
    </div>
    <div class="cart-section cart-summary-mobile cart-section-mobile">
		<?php echo $cart_summary; ?>
    </div>
    <div class="cart-section order-summary-mobile cart-section-mobile">
		<?php echo $order_summary; ?>
    </div>
    <div class="cart-section policies cart-section-mobile">
        <div class="cart-title">
            <h2>Policies</h2>
        </div>
        <div class="cart-box">
            <?php include CORE_DIR . '/templates/policy-panel.php'; ?>
        </div>
    </div>
    <div class="cart-section payment">
        <div class="cart-title"><h2>Payment Information</h2></div>
        <div class="cart-box">
            <div class="cart-box-2">
                <div class="form-items">
					<?php

					// need .item-wrap to get padding/margin etc.
					echo '<div class="item-wrap item-cc-icons split-1">';
					echo get_credit_card_icons_html();
					echo '</div>';

					// card number
					echo get_form_input( array(
						'req' => true,
						'name' => 'card',
						'label' => 'Card Number',
					) );

					// cvv
					echo get_form_input( array(
						'req' => true,
						'name' => 'cvv',
						'label' => 'CVV',
					) );

					// expiry month
					echo get_form_select( array(
						'req' => true,
						'name' => 'card_month',
						'select_2' => true,
						'add_class_2' => 'on-white',
						'label' => 'Expiry Month',
					), array(
						// 'placeholder' => 'Expiry Month',
						'items' => get_credit_card_month_options(),
					) );

					// expiry month
					echo get_form_select( array(
						'req' => true,
						'name' => 'card_year',
						'select_2' => true,
						'add_class_2' => 'on-white',
						'label' => 'Expiry Year',
					), array(
						// 'placeholder' => 'Expiry Year',
						'items' => get_credit_card_year_options(),
					) );

					// Heard About (Select)
					echo get_form_select( array(
						'req' => false,
						'label' => 'How did you hear about us?',
						'name' => 'heard_about',
						'select_2' => true,
						'add_class_2' => 'on-white',
					), array(
						'placeholder' => '-',
						'items' => array(
							'internet_search' => 'Internet Search',
							'instagram' => 'Instagram',
							'facebook' => 'Facebook',
							'pinterest' => 'Pinterest',
							'friend' => 'Friend',
							'other' => 'Other',
						),
					) );

					// Heard About Text
					echo get_form_input( array(
						'label' => 'Other',
						'name' => 'heard_about_other',
					) );

					$_ret_id  = 'at-return';
					$_war_id  = 'at-warranty';
					$_ship_id = 'at-shipping';
					$_fit_id  = 'at-fitment';

					// lightbox triggers
					$_ret  = '<a class="lb-trigger" data-for="' . $_ret_id . '" target="_blank" href="' . get_url( 'return_policy' ) . '">Return</a>';
					$_war  = '<a class="lb-trigger" data-for="' . $_war_id . '" target="_blank" href="' . get_url( 'warranty_policy' ) . '">Warranty</a>';
					$_ship = '<a class="lb-trigger" data-for="' . $_ship_id . '" target="_blank" href="' . get_url( 'shipping_policy' ) . '">Shipping</a>';
					$_fit  = '<a class="lb-trigger" data-for="' . $_fit_id . '" target="_blank" href="' . get_url( 'fitment_policy' ) . '">Fitment</a>';

					// lightbox args
					$lb_before = '<div class="general-content">';
					$lb_after  = '</div>';
					$lb_args   = array(
						'add_class' => 'general-lightbox embed-page'
					);

					// hidden lightbox content
					echo get_general_lightbox_content( $_ret_id, wrap( get_return_policy_html( true ), $lb_before, $lb_after ), $lb_args );
					echo get_general_lightbox_content( $_war_id, wrap( get_warranty_policy_html( true ), $lb_before, $lb_after ), $lb_args );
					echo get_general_lightbox_content( $_ship_id, wrap( get_shipping_policy_html( true ), $lb_before, $lb_after ), $lb_args );
					echo get_general_lightbox_content( $_fit_id, wrap( get_fitment_policy_html( true ), $lb_before, $lb_after ), $lb_args );

					// Accept Terms
					echo get_form_checkbox( array(
						'req' => true,
						'name' => 'accept_terms',
						'split' => 1,
						'label' => 'I agree to the the ' . $_ret . ', ' . $_war . ', ' . $_ship . ' and ' . $_fit . ' policies.',
						'value' => 1,
					) );

					// Mailing List
					echo get_form_checkbox( array(
						'label' => 'I consent to receive emails from Click It Wheels.',
						'value' => 1,
						'name' => 'mailing_list',
					) );

					// Submit
					echo get_form_submit( array(
						'text' => 'Pay Now',
					) );
					?>

                </div>
            </div>
        </div>
    </div>
</form>
<!-- /Form -->