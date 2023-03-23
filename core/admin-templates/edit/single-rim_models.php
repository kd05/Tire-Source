<?php

if ( ! cw_is_admin_logged_in() ) {
	echo 'not admin';
	exit;
}

$pk = gp_if_set( $_GET, 'pk' );
$model = DB_Rim_Model::create_instance_via_primary_key( $pk );

if ( ! $model ) {
	echo 'Not Found';
	return;
}

$form_action = gp_if_set( $_POST, 'form_action' );
$rim_model_name = gp_if_set( $_POST, 'rim_model_name' );

switch( $form_action ) {
	case 'edit':

		$updated = $model->update_database_and_re_sync( array(
			'rim_model_name' => gp_test_input( $rim_model_name ),
            // html is allowed here:
            'rim_model_description' => trim( gp_force_singular( $_POST['rim_model_description'] ) )
		));

		$msg = $updated ? 'Update Successful' : 'Update Not Successful';
		break;
	default:
		break;
}

$db = get_database_instance();

// get model again even if not updated
$model = DB_Rim_Model::create_instance_via_primary_key( $pk );
$brand = DB_Rim_Brand::create_instance_via_primary_key( $model->get( 'rim_brand_id' ) );

$finish = DB_Rim_Finish::get_single_instance_using_model_id( $model->get_primary_key_value() );

echo '<div class="admin-section general-content">';
echo '<h1>Rim Model</h1>';
echo '<p><a href="' . get_admin_archive_link( 'rims', [ 'model_id' => $model->get_primary_key_value()] ) . '">Rims with this Model</a></p>';
echo '<p><a href="' . get_admin_archive_link( 'rim_finishes', [ 'model_id' => $model->get_primary_key_value()] ) . '">Finishes with this Model</a></p>';

echo wrap_tag( html_link_new_tab( $finish->get_single_product_page_url(), 'Single Product Page' ) );

echo render_html_table_admin( false, [ $model->to_array_for_admin_tables() ], [] );
echo '</div>';

echo '<div class="admin-section general-content">';
echo '<form class="form-style-basic" method="post">';

echo '<input type="hidden" name="form_action" value="edit">';

echo '<div class="form-items">';

echo get_form_header( 'Edit' );

if ( $msg ) {
	echo get_form_response_text( $msg );
}

echo $model->get_simple_form_input( 'rim_model_name' );

echo '<br>';
echo wrap_tag( "The description shows up on single rim pages. But, if left empty, the rim brand description will be used instead." );

echo $model->get_simple_form_textarea( 'rim_model_description', 'Rim Model Description (HTML is allowed)' );

echo get_form_submit( array(
	'text' => 'Submit',
));

echo '</div>'; // form-items

echo '</form>';
echo '</div>';


