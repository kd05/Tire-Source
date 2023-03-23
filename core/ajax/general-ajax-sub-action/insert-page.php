<?php

$page_name_clean = make_page_name_from_user_input( gp_if_set( $_POST, 'page_name' ) );
$reload = false;
$alert = "";

if ( ! $page_name_clean ) {
	$alert = "You must provide a page name.";
	goto ajax_response;
}

if ( $page_name_clean && @$_POST['delete_page_instead'] ) {

    $db = get_database_instance();
    $deleted = $db->execute( "DELETE FROM pages WHERE page_name = :name;", [ [ 'name', $page_name_clean ] ] );
    $alert = "Delete page request sent (if it existed, it should be deleted): ($page_name_clean) ($deleted)";
    goto ajax_response;
}

// after delete stuff above
if ( DB_Page::get_instance_via_name( $page_name_clean, false ) ) {
    $alert = "Page already exists ($page_name_clean)";
    goto ajax_response;
}

if ( $page_name_clean === "__autofill" ) {

	$insert_count = DB_Page::auto_insert_dynamic_pages();

	$alert = "$insert_count pages inserted.";
	goto ajax_response;

} else {

	// for now we don't need dates. Any other columns can be edited after the
	// page is created.
	$page_id = DB_Page::insert( array(
		'page_name' => $page_name_clean,
		// 'page_date' => date( get_database_date_format() ),
	));

	if ( ! $page_id ) {
		$alert = "Insert failed for unknown reason.";
	} else {
		$alert = "Success. Page ID: $page_id";
	}

	goto ajax_response;
}

ajax_response:

$response = array(
	'_auto' => array(
		'reload' => (bool) $reload,
		'alert' => $alert,
	)
);

Ajax::echo_response( $response );
exit;