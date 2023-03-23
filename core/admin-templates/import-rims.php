<?php

if ( ! cw_is_admin_logged_in() ) {
    exit;
}

$ajax_action = 'import_rims';

// call early
Product_Import_Helper::handle_file_upload_request( true );

Header::$title = "Import Rims";
cw_get_header();
Admin_Sidebar::html_before( 'import-page' );
?>

    <div class="admin-section general-content">
        <h1>Import Rims</h1>
        <p>After a successful import you may want to:</p>
        <ul>
            <li>Check for newly added <a href="<?php echo get_admin_archive_link( 'rim_brands' ); ?>">Rim Brands</a> and
                add a brand logo and description.
            </li>
            <li>Localize <a href="<?php echo get_admin_page_url( 'rim_images' ); ?>">rim images</a>. Image URLs in rim
                imports are stored in a temporary queue, you have to "localize" them in order for them to be used. Only
                images that changed will need to be localized.
            </li>
            <li>Delete products from old imports by visiting Tables -> <a
                        href="<?php echo get_admin_archive_link( 'rims' ); ?>">Rims</a>. If your CSV is a "Master" list
                of all products, you'll be given an option to delete all products not found in the import after the
                import is done (this is easier than manually cleaning products).
            </li>
            <li><a href="<?php echo get_admin_page_url( 'clean_tables' ); ?>">Clean up</a> unused brands, models, and
                finishes no longer used by any products. This is optional and does not need to be done every time.
            </li>
        </ul>

        <?php

        $import = new Product_Import_Rims();
        echo $import->get_pre_form_message();
        Product_Import_Helper::render_forms( $import );

        ?>

    </div>
<?php
Admin_Sidebar::html_after();
cw_get_footer();