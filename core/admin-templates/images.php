<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

page_title_is( 'Images' );

cw_get_header();
Admin_Sidebar::html_before();

?>

    <div class="admin-section general-content">

        <p><a href="<?php echo get_admin_page_url( 'image_upload' ); ?>">Upload</a></p>
        <p>Here is a list of all images used. If you are manually editing images for rim brands, tire brands, or tire models, you'll want to copy the value in the "filename" column.</p>

        <?php

        $images = get_image_urls();

        $table = array();

        if ( $images ) {
            $count = 0;
            foreach ( $images as $image ) {
                if ( string_ends_with( $image, '.jpg' ) || string_ends_with( $image, '.png' ) ) {

                    $count++;
                    $t = [];
                    $t['count'] = $count;
                    $t['url'] = '<a target="_blank" href="' . $image . '">' . $image . '</a>';
                    $t['filename'] = basename( $image );
                    $table[] = $t;
                }
            }
        }

        echo render_html_table_admin( false, $table, array() );

        ?>

    </div>

<?php
Admin_Sidebar::html_after();
cw_get_footer();