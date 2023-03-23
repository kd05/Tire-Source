<?php

Header::$title = "Forgot Password";
Header::$meta_robots = 'noindex';
has_no_top_image();

cw_get_header();

?>
	<div class="page-wrap interior-page page-reset-password">
		<div class="main-content">
			<div class="container">
				<?php echo get_forgot_password_form(); ?>
			</div>
		</div>
	</div>
<?php

cw_get_footer();
