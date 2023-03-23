<?php

Header::$title = "Shipping Policy";
Header::$canonical = get_url( 'shipping_policy' );

cw_get_header();

?>

    <div class="page-wrap page-return-policy">
		<?php echo get_top_image( array(
			'title' => 'Shipping Policy',
			'img' => get_image_src( 'iStock-172668228-wide-lg.jpg' ),
			'overlay_opacity' => 70,
		) ); ?>

		<?php echo Components::grey_bar(); ?>

        <div class="main-content">
            <div class="container general-content">
				<?php echo get_policy_sub_nav(); ?>
                <?php echo get_shipping_policy_html(); ?>
            </div>
        </div>
    </div>

<?php

cw_get_footer();