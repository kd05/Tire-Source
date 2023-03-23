<?php

if ( ! cw_is_admin_logged_in() ) {
	echo 'not admin';
	exit;
}

$pk = gp_if_set( $_GET, 'pk' );
$model = DB_Tire_Model::create_instance_via_primary_key( $pk );

if ( ! $model ) {
	echo 'Not Found';
	return;
}

$form_action = gp_if_set( $_POST, 'form_action' );
$tire_model_name = gp_if_set( $_POST, 'tire_model_name' );
$tire_model_type = gp_if_set( $_POST, 'tire_model_type' );
$tire_model_category = gp_if_set( $_POST, 'tire_model_category' );
$tire_model_class = gp_if_set( $_POST, 'tire_model_class' );
$tire_model_run_flat = gp_if_set( $_POST, 'tire_model_run_flat' );

while( true ) {

	switch( $form_action ) {
		case 'edit':

			$updated = $model->update_database_and_re_sync( array(
				'tire_model_name' => gp_test_input( $tire_model_name ),
				'tire_model_type' => make_slug( $tire_model_type ),
				'tire_model_category' => make_slug( $tire_model_category ),
				'tire_model_class' => make_slug( $tire_model_class ),
				'tire_model_run_flat' => make_slug( $tire_model_run_flat),
                'tire_model_description' => trim( gp_force_singular( $_POST['tire_model_description'] ) ),
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
$model = DB_Tire_Model::create_instance_via_primary_key( $pk );
$brand = DB_Tire_Brand::create_instance_via_primary_key( $model->get( 'tire_brand_id' ) );

echo '<div class="admin-section general-content">';
echo '<h1>Tire Model</h1>';
echo '<p><a href="' . get_admin_archive_link( 'tires', [ 'model_id' => $model->get_primary_key_value()] ) . '">Tires with this Model ID</a></p>';

echo wrap_tag( get_anchor_tag_simple( get_tire_model_url_basic( $brand->get( 'slug' ), $model->get( 'slug' ) ), 'Single Product Page' ) );

echo render_html_table_admin( false, [ $model->to_array_for_admin_tables() ], [] );
echo '</div>';

// abandoned tire models wont really cause any issues and they can be re-inserted again in the future
//echo '<div class="admin-section general-content">';
//echo '<form class="form-style-basic" method="post">';
//echo '<input type="hidden" name="form_action" value="edit">';
//echo get_form_header( 'Delete' );
//echo '<p>Deleting can only occur if the model is not used by any existing products in the database.</p>';
//echo '<div class="form-items">';
//echo get_form_checkbox( array(
//	'name' => 'delete',
//	'value' => 1,
//	'label' => 'Delete',
//));
//echo get_form_submit( [ 'text' => 'Delete' ] );
//echo '</div>'; // form-items
//echo '</form>';
//echo '</div>'; // admin-section

echo '<div class="admin-section general-content">';
echo '<form class="form-style-basic" method="post">';

echo '<input type="hidden" name="form_action" value="edit">';

echo '<div class="form-items">';

echo get_form_header( 'Edit' );

if ( $msg ) {
	echo get_form_response_text( $msg );
}

echo $model->get_simple_form_input( 'tire_model_name' );
echo $model->get_simple_form_input( 'tire_model_type' );
echo $model->get_simple_form_input( 'tire_model_category' );
echo $model->get_simple_form_input( 'tire_model_class' );
echo $model->get_simple_form_input( 'tire_model_run_flat' );

// echo $model->get_simple_form_input( 'tire_model_image' );

echo '<br>';
echo wrap_tag( "The description shows up on single tire pages. But, if left empty, the tire brand description will be used instead." );
echo $model->get_simple_form_textarea( 'tire_model_description', 'Tire Model Description (HTML is allowed)' );

echo get_form_submit( array(
	'text' => 'Submit',
));

echo '</div>'; // form-items

echo '</form>';
echo '</div>';


