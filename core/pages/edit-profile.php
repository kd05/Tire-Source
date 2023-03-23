<?php

Header::$title = "Edit Profile";
Header::$meta_robots = 'noindex';
// has_no_top_image();

$user = cw_get_logged_in_user();

cw_get_header();

?>
	<div class="page-wrap interior-page page-edit-profile">
		<?php echo get_top_image( array(
			'title' => 'Edit Profile',
			'img' => get_image_src( 'iStock-459906517-wide-lg.jpg' ),
			'overlay_opacity' => 61,
		)); ?>
		<?php echo Components::grey_bar(); ?>
		<div class="main-content">
			<div class="container">
				<div class="general-content">
					<?php

					if ( ! $user ) {
						echo get_sign_in_form( array(
							'reload' => 1
						));
					} else {

						echo '<div class="edit-profile-forms">';

						echo '<div class="ep-item ep-profile">';
						echo get_edit_profile_form( $user );
						echo '</div>';

						echo '<div class="ep-item ep-password">';
						echo get_logged_in_change_password_form( $user );
						echo '</div>';

						echo '<div class="ep-item ep-forgot-password">';
						echo '<p>Forgot Your Password? <a href="' . get_url( 'forgot_password' ) . '">[Click Here]</a></p>';
						echo '</div>';

						echo '</div>';

					}

					?>
				</div>
			</div>
		</div>
	</div>
<?php

cw_get_footer();