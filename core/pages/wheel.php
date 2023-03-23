<?php

// do this early, it modified global vars before cw_get_header()
$view = new Single_Rim_Page( array_merge( $_GET, Router::$params ) );
$context = $view->context;

if ( $context === 'invalid' ) {
	cw_redirect_home();
	exit;
}

if ( $context !== 'landing' && $context !== 'invalid' ) {
	gp_set_global( 'require_fancybox', true );
}

cw_get_header();

?>
    <div class="page-wrap single-product-page rim-page">
        <?php echo get_top_image( $view->get_top_image_args() ); ?>
		<?php echo Components::grey_bar(); ?>
        <div class="main-content">
            <div class="single-product-container">
                <?php echo $view->render_title(); ?>
                <div class="sp-main">
                    <div class="left">
                        <div class="left-inner">
                            <?php echo $view->render_image(); ?>
                        </div>
                    </div>
                    <div class="right">
                        <div class="right-inner">
                            <div class="content-wrap general-content">
		                        <?php echo $view->render_description(); ?>
                            </div>
                            <?php include CORE_DIR . '/templates/policy-panel.php'; ?>
                        </div>
                    </div>
                </div>
                <div class="sp-tables">
		            <?php echo $view->render_tables(); ?>
                </div>
                <div class="sp-reviews">
		            <?php echo $view->render_reviews(); ?>
                </div>
            </div>
        </div>
    </div>
<?php

cw_get_footer();