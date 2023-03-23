<?php

if ( ! cw_is_admin_logged_in() ) {
	echo 'not admin';
	exit;
}

$pk = gp_if_set( $_GET, 'pk' );
$finish = DB_Rim_Finish::create_instance_via_primary_key( $pk );

if ( ! $finish ) {
	echo 'Not Found';
	return;
}

$form_action = gp_if_set( $_POST, 'form_action' );
$color_1_name = get_user_input_singular_value( $_POST, 'color_1_name' );
$color_2_name = get_user_input_singular_value( $_POST, 'color_2_name' );
$finish_name = get_user_input_singular_value( $_POST, 'finish_name' );

while( true ) {

	switch( $form_action ) {
		case 'edit':

			$db = get_database_instance();

			$data = array();

			// doing some extra logic here to make sure an admin doesn't screw up our data.
			// if the correspond SLUG is not set, then the NAME must be empty. 'color_1' is a slug, 'color_1_name' is a name.
			if ( $finish->get( 'color_1' ) ) {
				$data['color_1_name'] = $color_1_name;
			}

			if ( $finish->get( 'color_2' ) ) {
				$data['color_2_name'] = $color_2_name;
			}

			if ( $finish->get( 'color_3' ) ) {
				$data['finish_name'] = $finish_name;
			}

			$data['image_local'] = ampersand_to_plus( get_user_input_singular_value( $_POST, 'image_local' ) );
			$data['image_source'] = ampersand_to_plus( get_user_input_singular_value( $_POST, 'image_source' ) );
			$data['image_source_new'] = ampersand_to_plus( get_user_input_singular_value( $_POST, 'image_source_new' ) );

			if ( $data ) {
				$updated = $finish->update_database_and_re_sync( $data );
			}

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
$finish = DB_Rim_Finish::create_instance_via_primary_key( $pk );

echo '<div class="admin-section general-content">';
echo '<h1>Rim Finish</h1>';
echo '<p>Disclaimer: Unless you understand how the columns for "image_local", "image_source", and "image_source_new" work, it is recommended that you do not edit them from this page (an explanation is found <a href="' . get_admin_page_url( 'rim_finishes' ) . '">here</a>).</p>';
echo '<p><a href="' . get_admin_archive_link( 'rims', [ 'finish_id' => $finish->get_primary_key_value()] ) . '">Rims with this Finish ID</a></p>';
echo render_html_table_admin( false, [ $finish->to_array_for_admin_tables() ], [] );
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

$after_label = ' (see disclaimer above)';

echo $finish->get_simple_form_input( 'color_1_name' );
echo $finish->get_simple_form_input( 'color_2_name' );
echo $finish->get_simple_form_input( 'finish_name' );
echo $finish->get_simple_form_input( 'image_local', [ 'after_label' => $after_label ] );
echo $finish->get_simple_form_input( 'image_source',  [ 'after_label' => $after_label ] );
echo $finish->get_simple_form_input( 'image_source_new',  [ 'after_label' => $after_label ] );

echo get_form_submit( array(
	'text' => 'Submit',
));

echo '</div>'; // form-items

echo '</form>';
echo '</div>';


