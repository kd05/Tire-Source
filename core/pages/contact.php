<?php

Header::$title = "Contact Us";
Header::$canonical = get_url( 'contact' );

cw_get_header();

?>
	<div class="page-wrap interior-page page-contact">
		<?php echo get_top_image( array(
			'title' => 'Contact Us',
			'img' => get_image_src( 'iStock-147461270-wide-lg.jpg' ),
            'overlay_opacity' => 54,
		)); ?>
		<?php echo Components::grey_bar(); ?>
		<div class="main-content">
			<div class="container">
				<div class="general-content">
					<?php echo gp_render_textarea_content( cw_get_option( 'content_contact' ) ); ?>
				</div>
			</div>
		</div>
	</div>
<?php

cw_get_footer();