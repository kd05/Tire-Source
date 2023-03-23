<?php

// do this early, it modified global vars before cw_get_header()
$page    = new Single_Tire_Page( array_merge( $_GET, Router::$params ) );
$context = $page->context;

if ( $context === 'invalid' ) {
	cw_redirect_home();
	exit;
}

gp_set_global( 'require_fancybox', true );

cw_get_header();

echo get_us_tire_inventory_lightbox_alert( $page->vehicle );

?>
    <div class="page-wrap single-product-page tire-page">
		<?php echo get_top_image( $page->get_top_image_args() ); ?>
		<?php echo Components::grey_bar(); ?>
        <div class="main-content">
            <div class="single-product-container">
                <?php echo $page->render_title(); ?>
                <div class="sp-main">
                    <div class="left">
                        <div class="left-inner">
                            <?php echo $page->render_image(); ?>
                        </div>
                    </div>
                    <div class="right">
                        <div class="right-inner">
                            <div class="content-wrap general-content">
								<?php echo $page->render_description(); ?>
                            </div>
                            <?php include CORE_DIR . '/templates/policy-panel.php'; ?>
                        </div>
                    </div>
                </div>
                <div class="sp-tables">
					<?php echo $page->render_tables(); ?>
                </div>
                <div class="sp-reviews">
	                <?php echo $page->render_reviews(); ?>
                </div>
            </div>
        </div>
    </div>
<?php

cw_get_footer();