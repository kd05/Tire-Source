<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

page_title_is( "Admin Home" );
cw_get_header();
Admin_Sidebar::html_before();

//if ( isset( $_GET['region_init'] ) ) {
//    include CORE_DIR . '/db-init/insert-regions.php';
//}
//
//if ( isset( $_GET['shipping_init'] ) ) {
//	include CORE_DIR . '/db-init/dummy-shipping-rates.php';
//}

?>

    <div class="admin-section general-content">
        <h1>Admin Home</h1>
        <p>Choose a page from the sidebar.</p>
    </div>
<?php



Admin_Sidebar::html_after();
cw_get_footer();