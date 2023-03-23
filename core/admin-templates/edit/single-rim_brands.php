<?php

if ( ! cw_is_admin_logged_in() ) {
	echo 'not admin';
	exit;
}

$pk = gp_if_set( $_GET, 'pk' );
$brand = DB_Rim_Brand::create_instance_via_primary_key( $pk );

if ( ! $brand ) {
	echo 'Not Found';
	return;
}

$form_action = gp_if_set( $_POST, 'form_action' );
$rim_brand_name = gp_if_set( $_POST, 'rim_brand_name' );

while( true ) {

	switch( $form_action ) {
		case 'edit':

			$updated = $brand->update_database_and_re_sync( array(
				'rim_brand_name' => gp_test_input( $rim_brand_name ),
				'rim_brand_logo' => get_user_input_singular_value( $_POST, 'rim_brand_logo' ),
				'rim_brand_description' => trim( gp_force_singular( $_POST['rim_brand_description'] ) ),
			));

			if ( $updated ) {
				$msg = 'Update Successful.';
				break;
			} else {
				$msg = 'Update Not Successful.';
			}

			break;
		default:
			break;
	}

	break;
}

// get model again even if not updated
$brand = DB_Rim_Brand::create_instance_via_primary_key( $pk );

echo '<div class="admin-section general-content">';
echo '<h1>Rim Brand</h1>';
echo '<p><a href="' . get_admin_archive_link( 'rims', [ 'brand_id' => $brand->get_primary_key_value()] ) . '">Rims with this Brand</a></p>';
echo '<p><a href="' . get_admin_archive_link( 'rim_models', [ 'rim_brand_id' => $brand->get_primary_key_value() ] ) . '">Models with this Brand</a></p>';

echo wrap_tag( html_link_new_tab( cw_add_query_arg( [ 'brand' => $brand->get( 'slug' )], get_url( 'rims' )), 'Product Archive Page' ) );

// echo '<p><a href="' . get_admin_archive_link( 'rim_finishes', [ 'rim_brand_id' => $brand->get_primary_key_value() ] ) . '">Finishes with this Brand</a></p>';

echo render_html_table_admin( false, [ $brand->to_array_for_admin_tables() ], [] );
echo '</div>';


echo '<div class="admin-section general-content">';
echo '<form class="form-style-basic" method="post">';

echo '<input type="hidden" name="form_action" value="edit">';

echo '<div class="form-items">';

echo get_form_header( 'Edit' );

if ( $msg ) {
	echo get_form_response_text( $msg );
}

echo $brand->get_simple_form_input( 'rim_brand_name' );
echo $brand->get_simple_form_input( 'rim_brand_logo' );

echo '<br>';
echo wrap_tag( "The description shows up on single rim pages, but only if the rim model description is left blank." );
echo $brand->get_simple_form_textarea( 'rim_brand_description', 'Rim Brand Description (HTML is allowed)' );


echo get_form_submit( array(
	'text' => 'Submit',
));

echo '</div>'; // form-items

//if ( $brand->can_be_deleted() ) {
//
//}

echo '</form>';
echo '</div>';


