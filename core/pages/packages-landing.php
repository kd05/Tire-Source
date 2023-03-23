<?php

Header::$title = "Packages";
Header::$canonical = get_url( 'packages' );

cw_get_header();

?>

<div class="page-wrap products-page product-landing-page packages-page">
	<?php
	// image arguments might be repeated in @var Page_Products
    echo get_top_image( array(
		'title' => '',
		'overlay_opacity' => 58,
		'img' => get_image_src( 'pkg-1.jpg' ),
        'after_title_html' => gp_render_textarea_content( get_page_meta( get_page_id( DB_Page::page_name_via_landing_page_type( 'packages' ) ), 'landing_desc' ) ),
	) ); ?>
	<?php echo Components::grey_bar(); ?>
    <div class="main-content">
        <div class="wide-container">
            <div class="wide-container-inner">
                <div class="sidebar-container no-top">
                    <div class="sb-left">
                        <div class="sb-left-2">
                            <?php
                            echo '<div class="vehicle-tabs vt-rims">';

                            echo '<div class="vt-controls count-0">';
                            // echo '<button type="button" class="css-reset vt-trigger active vt-trigger-vehicle" data-for="#vt-vehicle">Vehicle Selection</button>';
                            // echo '<button type="button" class="css-reset vt-trigger hidden vt-trigger-tire-sizes" data-for="#vt-pkg-history">History</button>';
                            echo '</div>';

                            echo '<div class="vt-body">';

                            echo '<div class="vt-item vt-item-vehicle active" id="vt-vehicle">';
                            echo get_vehicle_lookup_form( array(
                                'page' => 'packages',
                                'title' => 'Select Your Vehicle',
                                'hide_shop_for' => true,
                            ) );
                            echo '</div>';

                            //echo '<div class="vt-item vt-item-tire-sizes hidden" id="vt-tire-sizes">';
                            //echo get_rims_by_size_form( $_GET );
                            //echo '</div>';

                            echo '</div>'; // vt-body
                            echo '</div>'; // vehicle-tabs
                            ?>
                        </div>
                    </div>
                    <div class="sb-right">
                        <div class="sb-right-2">
                            <div class="general-content">
                                <?php
                                $lower_desc = gp_render_textarea_content( get_page_meta(
                                    get_page_id( '_landing_packages' ), 'lower_desc' ) );

                                echo $lower_desc;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php

cw_get_footer();
