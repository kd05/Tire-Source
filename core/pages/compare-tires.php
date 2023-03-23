<?php

Header::$title = "Compare Tires";

// an array of part numbers
$items = gp_if_set( $_GET, 'items' );

cw_get_header();

?>
    <div class="page-wrap interior-page page-compare-tires page-type-compare-products">
	    <?php echo get_top_image( array(
		    'title' => 'Compare Tires',
		    'img' => get_image_src( 'iStock-884106776-wide-lg.jpg' ),
            'overlay_opacity' => 50,
	    ) ); ?>
	    <?php echo Components::grey_bar(); ?>
        <div class="main-content">
            <div class="container">
				<?php echo render_compare_tires( $items ); ?>
            </div>
        </div>
    </div>

<?php

cw_get_footer();

