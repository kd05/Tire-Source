<?php

if ( ! cw_is_admin_logged_in() ) {
    exit;
}

// early
Product_Import_Helper::handle_file_upload_request( false );

Header::$title = "Import Tires";
cw_get_header();
Admin_Sidebar::html_before( 'import-page' );

?>
    <div class="admin-section general-content">
        <h1>Import Tires</h1>
        <p>After a successful import you may want to:</p>
        <ul>
            <li>Check for newly added <a href="<?php echo get_admin_archive_link( 'tire_brands' ); ?>">Tire Brands</a>
                and add tire brand logo's.
            </li>
            <li>Upload new tire model images using the <a href="<?php echo get_admin_page_url( 'image_upload' ); ?>">Image
                    Uploader</a>.
            </li>
            <li>Check for newly added <a href="<?php echo get_admin_archive_link( 'tire_models' ); ?>">Tire Models</a>
                and set descriptions and images.
            </li>
            <li>Delete products from old imports by visiting Tables -> <a
                        href="<?php echo get_admin_archive_link( 'tires' ); ?>">Tires</a>. If your CSV is a "Master"
                list of all products, you'll be given an option to delete all products not found in the import after the
                import is done (this is easier than manually cleaning products).
            </li>
            <li><a href="<?php echo get_admin_page_url( 'clean_tables' ); ?>">Clean up</a> unused brands, models, and
                finishes no longer used by any products. This is optional and does not need to be done every time.
            </li>
        </ul>
        <?php

        $import = new Product_Import_Tires();
        echo $import->get_pre_form_message();
        Product_Import_Helper::render_forms( $import );

        ?>

    </div> <!-- admin-section -->

<?php
Admin_Sidebar::html_after();
cw_get_footer();