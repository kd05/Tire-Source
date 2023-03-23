<?php

if ( ! cw_is_admin_logged_in() ) {
    exit;
}

Admin_Controller::with_header_footer_and_sidebar( function () {

    page_title_is( "Rim Images" );

    $finishes = DB_Rim_Finish::query_all( false, true, true );

    ?>

    <div class="admin-section general-content">
        <h2>Rim Images</h2>
        <p>After running a product import, make sure to localize all items that need attention. Localizing will download an image from a URL and create compressed copies of the image for showing up on the front-end.</p>
        <?php
        Product_Images_Admin_UI::render(false, $_GET, $finishes );
        ?>
    </div>
    <?php
} );
