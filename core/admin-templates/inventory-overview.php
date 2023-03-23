<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

page_title_is( 'Suppliers Overview' );

cw_get_header();
Admin_Sidebar::html_before();

echo '<div class="admin-section general-content">';

Admin_Sidebar::page_title( 'Suppliers Overview' );
echo '<p>' . get_admin_inventory_related_links( 'inventory_overview' ) . '</p>';
echo '<p>The table below breaks down all tires and rims in your database by locale and supplier, and then shows whether or not there are automated inventory imports associated with them.</p>';

echo Supplier_Inventory_Overview::render_table_from_instances( Supplier_Inventory_Overview::get_and_calculate_all_instances() );

echo '</div>';

Admin_Sidebar::html_after();
cw_get_footer();




