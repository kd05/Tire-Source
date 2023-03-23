<?php

Header::$title = "Reset Password";
Header::$meta_robots = 'noindex';
has_no_top_image();

$key = get_user_input_singular_value( $_GET, 'key' );
$parsed = new Parsed_Forgot_Password_Key( $key );

cw_get_header();

?>
	<div class="page-wrap interior-page page-reset-password">
		<div class="main-content">
			<div class="container">
				<?php echo get_password_reset_form( $parsed ); ?>
			</div>
		</div>
	</div>
<?php

queue_dev_alert( 'parsed password key', get_pre_print_r( $parsed ) );

cw_get_footer();