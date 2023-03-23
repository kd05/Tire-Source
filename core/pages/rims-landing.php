<?php

Header::$canonical = get_url( 'wheels' );

cw_get_header();

?>

<div class="page-wrap products-page product-landing-page rims-page">
    <?php
    // image arguments might be repeated in @var Page_Products
    echo get_top_image( array(
        'title' => 'Aftermarket Car Wheels',
        'img' => get_image_src( 'iStock-123201626-wide-lg-2.jpg' ),
        'img_tag' => true,
        'alt' => 'Click It Wheels for wheels and tires canada',
        'overlay_opacity' => 70,
        'after_title_html' => gp_render_textarea_content( get_page_meta( get_page_id( DB_Page::page_name_via_landing_page_type( 'rims' ) ), 'landing_desc' ) ),
    ) ); ?>
    <?php echo Components::grey_bar(); ?>
    <div class="main-content">
        <div class="wide-container">
            <div class="wide-container-inner">
                <div class="sidebar-container no-top">
                    <div class="sb-left">
                        <div class="sb-left-2">
                            <div class="vehicle-tabs vt-rims">
                                <div class="vt-controls">
                                    <button type="button" class="css-reset vt-trigger active vt-trigger-vehicle"
                                            data-for="vt-vehicle">Vehicle
                                    </button>
                                    <button type="button" class="css-reset vt-trigger not-active vt-trigger-rim-sizes"
                                            data-for="vt-rim-sizes">Size
                                    </button>
                                </div>
                                <div class="vt-body">
                                    <div id="vt-vehicle" class="vt-item vt-item-vehicle active">
                                        <?= get_vehicle_lookup_form( array(
                                            'page' => 'rims',
                                            'hide_shop_for' => true,
                                        ) ); ?>
                                    </div>

                                    <div id="vt-rim-sizes" class="vt-item vt-item-rim-sizes not-active">
                                        <?= get_rims_by_size_form( [], $_GET ); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sb-right">
                        <div class="sb-right-2">
                            <div class="section section-rim-brands">
                                <div class="title">
                                    <h2 class="like-h1">Wheels By Brand</h2>
                                </div>
                                <div class="body">
                                    <div class="product-brands rim-brands">
                                        <?php
                                        call_user_func( function () {

                                            $brands = get_rim_brands();
                                            include CORE_DIR . '/templates/brand-logos.php';
                                        } )
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <?php
                            $lower_desc = gp_render_textarea_content( get_page_meta(
                                    get_page_id( '_landing_rims' ), 'lower_desc' ) );

                            ?>

                            <div class="general-content">
                                <?= $lower_desc; ?>
                                <h2>Frequently Asked Questions</h2>
                            </div>

                            <div class="faq-items">
                                <div class="faq-controls all-hidden">
                                    <button class="css-reset expand-all">[Expand All]</button>
                                    <button class="css-reset collapse-all">[Collapse All]</button>
                                </div>

                                <div class="faq-item hidden">
                                    <div class="question">
                                        <h3 class="question-2">What Should I Look For When Buying New Wheels?</h3>
                                    </div>
                                    <div class="answer">
                                        <div class="answer-2 general-content">
                                            <p>We understand your desire to invest in durable and stylish <a href="https://tiresource.com/tires">tires</a> and <a href="https://tiresource.com/wheels">rims</a>. To help find the perfect match for your vehicle, use Click It Wheels make and model search tool.</p>
                                            <p>Tell us your vehicle make and model, and we’ll list all of the different tires and rims that are compatible. Simple! From <a href="https://tiresource.com/wheels/rtx">RTX Wheels</a> to <a href="https://tiresource.com/wheels/dai-alloys">DAI Alloys</a>, Click It Wheels offers a wide range of options, so you’re not bound to a product.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="faq-item hidden">
                                    <div class="question">
                                        <h3 class="question-2">What Aftermarket Rims or Wheels Will Fit My Vehicle?</h3>
                                    </div>
                                    <div class="answer">
                                        <div class="answer-2 general-content">
                                            <p>The first thing you need to do when you need new wheels is to measure your old ones. This way, you will know exactly what aftermarket wheels/rims will fit on your vehicle. To eliminate any confusion, Click It Wheels “vehicle make and model” search tool lists only wheels that fit.</p>
                                            <p>Please be aware that if you are installing aftermarket wheels, and/or changing tire sizes, you are customizing your vehicle; therefore, they may not fit exactly the same as the factory settings.</p>
                                            <p>We guarantee that the products will fit your unmodified vehicle, meaning rims will bolt on and will not interfere with OEM brakes, suspension, or body components. Rims that require hub-centric rings are still considered a correct fitment.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="faq-item hidden">
                                    <div class="question">
                                        <h3 class="question-2">Does Click It Wheels Sell OEM Wheels?</h3>
                                    </div>
                                    <div class="answer">
                                        <div class="answer-2 general-content">
                                            <p>OEM (Original Equipment Manufacturer) wheels are not available through Click It Wheels. However, we offer the most reliable replicas that mirror OEM designs and specifications. You won’t even be able to notice the difference!</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="faq-item hidden">
                                    <div class="question">
                                        <h3 class="question-2">Why Don’t You Have My Vehicle Listed in Your Search
                                            Tool?</h3>
                                    </div>
                                    <div class="answer">
                                        <div class="answer-2 general-content">
                                            <p>Click It Wheels “vehicle make and model” search tool lists popular brands including BMW, Ford, Audi, Volvo, Honda, and Nissan, and we constantly update our database. If your vehicle isn’t listed, it means that it is old or very unique. But don’t worry; you can contact us, and a member of our team will reach out to you for assistance.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="faq-item hidden">
                                    <div class="question">
                                        <h3 class="question-2">Are the Listed Prices Per One Wheel or Per Set?</h3>
                                    </div>
                                    <div class="answer">
                                        <div class="answer-2 general-content">
                                            <p>Prices for <a href="https://tiresource.com/wheels">rims</a> and <a href="https://tiresource.com/tires">tires</a> are listed individually. When added to the cart, we automatically add a quantity of 4 for you.</p>
                                            <p>Click It Wheels is here to match you and your vehicle with the perfect wheels. Browse our product pages and use our make and model search function to find the right wheels for your vehicle. If you have any questions, email us at info@email_removed.com or contact us directly.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "FAQPage",
        "mainEntity": [{
            "@type": "Question",
            "name": "What Should I Look For When Buying New Wheels?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "We understand your desire to invest in durable and stylish rims and tires. To help find the perfect match for your vehicle, use tiresource.COM make and model search tool.\r\nTell us your vehicle make and model, and we’ll list all of the different tires and rims that are compatible. Simple! From RTX Wheels to DAI Alloys, tiresource.COM offers a selection of options, so you’re bound to find a style that fits with your aesthetic."
            }
        },{
            "@type": "Question",
            "name": "What Wheels or Rims Will Fit My Vehicle?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "The first thing you need to do when you need new tires is to measure your old ones. This way, you will know exactly what tires will fit on your vehicle. To eliminate any confusion, tiresource.COM vehicle make and model search tool list only wheels that fit.\r\nPlease be aware that if you are installing aftermarket wheels, and/or changing tire sizes, you are customizing your vehicle; therefore, they may not fit exactly the same as the factory settings.\r\nWe guarantee that the products will fit your unmodified vehicle, meaning rims will bolt on and will not interfere with OEM brakes, suspension, or body components. Rims that require hub centric rings are still considered a correct fitment."
            }
        },{
            "@type": "Question",
            "name": "Why Don’t You Have My Vehicle Listed in Your Search Tool?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "tiresource.COM vehicle make and model search tool lists popular brands including BMW, Ford, Audi, Volvo, Honda, Nissan, and we constantly update our database. If your vehicle isn’t listed, it means that it is old or very unique. But don’t worry; you can contact us, and a member of our team will reach out to you for assistance."
            }
        },{
            "@type": "Question",
            "name": "Are the Listed Prices Per One Wheel or Per Set?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Often prices are listed as a set of 4, but if they are listed individually then it should be indicated by \"ea\" or \"(EA)\".\r\ntiresource.COM is here to match you and your vehicle with the perfect wheels. Browse our product pages and use our make and model search function to find the right wheels for your vehicle. If you have any questions, email us on info@email_removed.com or contact us directly."
            }
        }]
    }
</script>

<?php

cw_get_footer();
