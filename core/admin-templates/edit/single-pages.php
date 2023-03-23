<?php

// these variables are likely used in included files below, so be careful changing their names.
$page_id = (int) get_user_input_singular_value( $_POST, 'pk' );
$db_page = DB_Page::create_instance_via_primary_key( $pk );
$page_name_clean = $db_page->get_name( "", true );

// ie. ADMIN_TEMPLATES /edit/edit-single-page-via-page-name/page-name-but-with-dashes.php
$path = $db_page->get_file_path_for_admin_edit_single_page_override();

if ( $db_page ) {

	echo '<div class="general-content" style="margin-top: 40px; margin-bottom: 40px;">';
	echo html_element( "Page: $page_name_clean", 'h2' );
	page_title_is( "Page: $page_name_clean" );

	echo '<p>';
	echo get_anchor_tag_simple( get_admin_archive_link( DB_pages ), "Back to All Pages" );
	echo "<span> &bull; </span>";
	echo get_anchor_tag_simple( $db_page->get_front_end_url_and_name()[0], "View", [ 'target' => '_blank' ]);
	echo '</p>';

	echo render_html_table_admin( false, [ $db_page->to_array_for_admin_tables() ], [ 'title' => 'Page' ] );

	echo '</div>';

	// Render the fields to edit the page
	if ( file_exists( $path ) ) {
	    // possibly never used
		include $path;
	} else {
	    ?>
        <div class="general-content" style="margin-bottom: 20px;">
            <h2 style="margin-bottom: 8px;">Edit Content</h2>
            <p>Meta title's and descriptions often have defaults. Enter a value below to override the default.</p>
        </div>
        <?= $db_page->render_ajax_edit_form(); ?>
    <?php
	}


    $meta_safe_for_printing = [];
    $meta = Page_Meta::get_all_page_meta_via_id( $db_page->get_id() );

    if( $meta ) {

        foreach ( $meta as $key => $value ) {
            $k = htmlspecialchars( $key );
            $v = htmlspecialchars( $value );
            $meta_safe_for_printing[$k] = $v;
        }

        echo '<br>';
        echo '<br>';
        echo render_html_table_admin( false, [ $meta_safe_for_printing ], [ 'title' => 'All Page Meta (For display only)' ] );
    }


} else {
	echo "Page not found";
}
