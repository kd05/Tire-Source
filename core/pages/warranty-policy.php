<?php

Header::$title = "Warranty Policy";
Header::$canonical = get_url( 'warranty_policy' );

cw_get_header();

//  <a href="mailto: sales@email_removed.com">sales@email_removed.com</a>
?>

	<div class="page-wrap page-warranty-policy">
		<?php echo get_top_image( array(
			'title' => 'Warranty Policy',
			'img' => get_image_src( 'iStock-172668228-wide-lg.jpg' ),
			'overlay_opacity' => 70,
		)); ?>
		<?php echo Components::grey_bar(); ?>
		<div class="main-content">
			<div class="container general-content">
				<?php echo get_policy_sub_nav(); ?>
                <?php echo get_warranty_policy_html(); ?>
			</div>
		</div>
	</div>

<?php

cw_get_footer();
