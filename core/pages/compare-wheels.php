<?php

Header::$title = "Compare Wheels";
// has_no_top_image();

// an array of part numbers
$items = gp_if_set( $_GET, 'items' );

cw_get_header();

?>
	<div class="page-wrap interior-page page-compare-wheels page-type-compare-products">
		<?php echo get_top_image( array(
			'title' => 'Compare Wheels',
			'img' => get_image_src( 'iStock-123201626-wide-lg-2.jpg' ),
            'img_tag' => true,
            'alt' => 'Click It Wheels for wheels and tires canada',
            'overlay_opacity' => 50,
		) ); ?>
		<?php echo Components::grey_bar(); ?>
		<div class="main-content">
			<div class="container">
				<?php echo render_compare_rims( $items ); ?>
			</div>
		</div>
	</div>
<?php

cw_get_footer();