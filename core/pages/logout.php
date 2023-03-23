<?php

Header::$title = "Log Out";
Header::$meta_robots = 'noindex';
has_no_top_image();

cw_get_header();

?>
	<div class="page-wrap interior-page page-login">
		<?php echo Components::grey_bar(); ?>
		<div class="main-content">
			<div class="container">

				<?php

				if ( cw_is_user_logged_in() ) {
					echo '<h2 class="logout-header">You are currently logged in.</h2>';

					echo '<form class="logout-post" method="post" action="">';
					echo get_hidden_inputs_from_array( array(
						'submitted' => 'yes',
						'nonce' => get_nonce_value( 'logout_post_back' ),
					));
					echo '</form>';

				} else {
					echo '<h2 class="logout-header">You are not currently logged in.</h2>';
				}
				?>
			</div>
		</div>
	</div>
<?php

cw_get_footer();

