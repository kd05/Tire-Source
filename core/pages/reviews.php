<?php

Header::$title = "Leave A Review";
Header::$meta_robots = "noindex";
Header::$canonical = get_url( 'reviews' );

$view = new Product_Review_Page( $_GET );

cw_get_header();

$cls   = [ 'page-wrap' ];
$cls[] = 'page-reviews';
$cls[] = $view->helper->is_tire ? 'type-tire' : '';
$cls[] = $view->helper->is_rim ? 'type-rim' : '';

?>
    <div class="<?php echo gp_parse_css_classes( $cls ); ?>">
		<?php
		echo get_top_image( array(
			'title' => 'Leave A Review',
			'img' => get_image_src( 'iStock-123201629-wide-lg.jpg' ),
			'overlay_opacity' => 65,
		) );
		echo Components::grey_bar();
		?>

        <div class="main-content">
			<?php

			if ( ! cw_is_user_logged_in() ) {

				echo get_general_lightbox_content( 'sign_in', get_sign_in_form( [ 'reload' => true ] ), [ 'add_class' => 'sign-in' ] );

				echo '<div class="container general-content">';
				echo '<h2>You must be logged in to post reviews</h2>';
				echo '<div class="button-1"><button class="css-reset lb-trigger" data-for="sign_in">Sign In</button></div>';
				echo '</div>';

			} else {
				echo $view->is_valid ? $view->render_sidebar_and_content() : '';
			}
			?>
        </div>
    </div>
<?php

cw_get_footer();