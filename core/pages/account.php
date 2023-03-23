<?php

Header::$title = "Account";

cw_get_header();

$user = cw_get_logged_in_user();

?>
    <div class="page-wrap interior-page page-account">
	    <?php echo get_top_image( array(
		    'title' => 'Account',
		    'img' => get_image_src( 'iStock-459906517-wide-lg.jpg' ),
            'overlay_opacity' => 61,
	    )); ?>
	    <?php echo Components::grey_bar(); ?>
        <div class="main-content">
            <div class="container account-container">
				<?php

				if ( ! cw_is_user_logged_in() ) {
					echo get_sign_in_form( array(
						'reload' => true,
					) );
				} else {

					echo '<div class="account-section account-welcome general-content">';
					echo '<h1 class="account-title">Welcome, ' . $user->get( 'first_name', null, true ) . '</h1>';

					if ( cw_is_admin_logged_in() ) {
						echo '<div class="button-1"><a href="' . get_admin_page_url( 'home' ) . '">Admin Panel</a></div>';
					}

					echo '</div>';

					echo '<div class="account-section account-order-history">';
					echo '<h2 class="account-title">Order History</h2>';

					$cols = get_order_history_table_cols();
					$data = get_order_history_from_user( $user );
					$args = array(
						'callback' => '',
						'add_class' => 'order-history',
					);

					echo render_html_table( $cols, $data, $args );
					echo '</div>';

					echo '<div class="account-section account-edit-profile">';
					echo '<h2 class="account-title">Edit Profile</h2>';
					echo '<div class="button-1"><a href="' . get_url( 'edit_profile' ) . '">Click Here</a></div>';
					echo '</div>';

					echo '<div class="account-section account-log-out">';
					echo '<h2 class="account-title">Log Out</h2>';
					echo '<div class="button-1">' . get_ajax_logout_anchor_tag( [ 'text' => 'Click Here' ] ) . '</div>';
					echo '</div>';
				}
				?>
            </div>
        </div>
		<?php
		//			$br = '<br><br><hr><br><br>';
		//			echo '<p><a href="' . get_url( 'reviews' ) . '">Leave a Review</a></p>';
		//			echo $br;
		//			echo get_sign_up_form();
		//			echo $br;
		//			echo get_sign_in_form();
		//			echo $br;
		//			echo get_forgot_password_form();
		//			echo $br;
		//			echo get_ajax_logout_anchor_tag();
		//			echo $br;
		//			echo get_password_reset_form( [], '' );
		//			echo $br;
		?>
    </div>

<?php

cw_get_footer();