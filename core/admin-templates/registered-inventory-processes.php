<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

page_title_is( 'Registered Inventory Processes' );

cw_get_header();
Admin_Sidebar::html_before();

echo '<div class="admin-section general-content">';

Admin_Sidebar::page_title( 'Registered Inventory Processes' );

echo '<p>' . get_admin_inventory_related_links( 'registered_inventory_processes' ) . '</p>';

$instances = Supplier_Inventory_Supplier::get_all_supplier_instances();

$rows = array();

/** @var Supplier_Inventory_Supplier $instance */
foreach ( $instances as $instance ) {

    $raw_file_url = cw_add_query_arg( [
        'supplier' => $instance->get_hash_key(),
    ], Admin_Controller::get_url( 'inventory_files') );

    $parsed_file_url = cw_add_query_arg( [
        'parsed' => '1',
    ], $raw_file_url );

	$row = array();

	// most recent corresponding entry in stock updates table
	$last = DB_Stock_Update::get_most_recent_via_hash_key( $instance::HASH_KEY );

	$row['description'] = $instance->get_admin_name();
	$row['locale'] =  $instance->locale;
	$row['type'] = $instance->type;
    $row['view_file'] = '<a href="' . $raw_file_url . '">raw</a>, <a href="' . $parsed_file_url . '">parsed</a>';
	$row['suppliers'] = implode_comma( $instance->allowed_suppliers );
	$row['filename'] = $instance->ftp ? $instance->ftp->remote_file_name : '';
	$row['extra'] = method_exists( $instance, 'get_admin_info_extra_column' ) ? $instance->get_admin_info_extra_column() : '';
	$row['url'] = get_anchor_tag_simple( get_admin_archive_link( DB_stock_updates, array(
		'stock_description' => $instance::HASH_KEY,
	)), 'Previous Updates' );

	if ( $last ) {

	    $_date = gp_test_input( $last->get( 'stock_date' ) );
	    $_in = gp_test_input( $last->get( 'count_in_stock' ) );
	    $_updated = gp_test_input( $last->get( 'count_updated' ) );

	    $row['last_update'] = "$_date, $_in / $_updated in stock.";

    } else {
	    $row['last_update'] = "";
    }

	$rows[] = $row;
}


// pagination..
echo render_html_table_admin( null, $rows );
echo '</div>';


Admin_Sidebar::html_after();
cw_get_footer();




