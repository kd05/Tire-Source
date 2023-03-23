<?php

Header::$canonical = get_url( 'tires' );

cw_get_header();

?>

<div class="page-wrap products-page product-landing-page tires-page">
    <?php
    // image arguments might be repeated in @var Page_Products
    echo get_top_image( array(
        'title' => 'Car Tires',
        'img' => get_image_src( 'tire-top.jpg' ),
        'overlay_opacity' => 58,
        'after_title_html' => gp_render_textarea_content( get_page_meta( get_page_id( DB_Page::page_name_via_landing_page_type( 'tires' ) ), 'landing_desc' ) ),
    ) ); ?>
    <?php echo Components::grey_bar(); ?>
    <div class="main-content">
        <div class="wide-container">
            <div class="wide-container-inner">
                <div class="sidebar-container no-top">

                    <div class="sb-left">
                        <div class="sb-left-2">
                            <div class="vehicle-tabs vt-tires">
                                <div class="vt-controls">
                                    <button type="button" class="css-reset vt-trigger active vt-trigger-vehicle"
                                            data-for="vt-vehicle">Vehicle
                                    </button>
                                    <button type="button" class="css-reset vt-trigger not-active vt-trigger-tires-sizes"
                                            data-for="vt-tire-sizes">Size
                                    </button>
                                </div>
                                <div class="vt-body">
                                    <div id="vt-vehicle" class="vt-item vt-item-vehicle active">
                                        <?= get_vehicle_lookup_form( array(
                                            'page' => 'tires',
                                            'hide_shop_for' => true,
                                        ) ); ?>
                                    </div>

                                    <div id="vt-tire-sizes" class="vt-item vt-item-tire-sizes not-active">
                                        <?= tires_by_size_form( [], $_GET ); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sb-right">
                        <div class="sb-right-2">
                            <div class="section section-tire-types">
                                <div class="title">
                                    <h2 class="like-h1">Tires By Type</h2>
                                </div>
                                <div class="body">
                                    <?php
                                    $tires_url = get_url( 'tires' );
                                    $types = [
                                        [
                                            'Summer',
                                            Router::build_url( [ 'tires', 'summer' ] ),
                                            get_image_src( 'summer-tires-small.jpg' ),
                                            'Summer tires for your car, truck, or SUV'
                                        ],
                                        [
                                            'Winter',
                                            Router::build_url( [ 'tires', 'winter' ] ),
                                            get_image_src( 'winter-tires-small.jpg' ),
                                            'Winter tires for your car, truck, or SUV'
                                        ],
                                        [
                                            'All Season',
                                            Router::build_url( [ 'tires', 'all-season' ] ),
                                            get_image_src( 'all-season-tires-small.jpg' ),
                                            'All-season tires for your car, truck, or SUV'
                                        ],
                                        [
                                            'All Weather',
                                            Router::build_url( [ 'tires', 'all-weather' ] ),
                                            get_image_src( 'all-weather-tires-small.jpg' ),
                                            'All-weather tires for your car, truck, or SUV'
                                        ],
                                    ];
                                    ?>
                                    <div class="tire-types">
                                        <div class="tt-flex">
                                            <?php foreach ( $types as $type ) { ?>
                                                <div class="tt-item">
                                                    <a class="tt-item-inner" href="<?= $type[ 1 ]; ?>">
                                                        <div class="img-tag-cover inherit-size">
                                                            <img src="<?= $type[2]; ?>" alt="<?= $type[3]; ?>">
                                                        </div>
                                                        <div class="content">
                                                            <p class="text"><?= $type[ 0 ]; ?><i
                                                                        class="fa fa-angle-right"></i></p>
                                                        </div>
                                                    </a>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="section section-tire-brands" style="margin-bottom: 0;">
                                <div class="title">
                                    <h2 class="like-h1">Tires By Brand</h2>
                                </div>
                                <div class="body">
                                    <div class="product-brands rim-brands">
                                        <?php
                                        call_user_func( function () {

                                            $brands = get_tire_brands();
                                            include CORE_DIR . '/templates/brand-logos.php';
                                        } )
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <?php
                            $lower_desc = gp_render_textarea_content( get_page_meta(
                                get_page_id( '_landing_tires' ), 'lower_desc' ) );

                            ?>

                            <div class="general-content">
                                <?= $lower_desc; ?>
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
