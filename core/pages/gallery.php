<?php

Header::$title = "Gallery";
Header::$canonical = get_url( 'gallery' );

gp_set_global( 'require_fancybox', true );

cw_get_header();

?>
    <div class="page-wrap interior-page page-gallery">
		<?php echo get_top_image( array(
			'title' => 'Gallery',
			'img' => get_image_src( 'iStock-883581726-wide-lg.jpg' ),
		) ); ?>
		<?php echo Components::grey_bar(); ?>
        <div class="main-content">
            <div class="container">
                <div class="general-content">
					<?php echo render_gallery( get_gallery_items_array() ); ?>
                </div>
            </div>
        </div>
    </div>
<?php

cw_get_footer();