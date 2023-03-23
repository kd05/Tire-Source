<?php

if ( ! cw_is_admin_logged_in() ) {
	echo 'not admin';
	exit;
}

$pk = gp_if_set( $_GET, 'pk' );

// when we come from the tires or rims tables, we have to pass in slug because primary key is not stored
// but when we come from the suppliers table, we'll be passing in the primary key because that's how
// all of those tables work.
$supplier = DB_Supplier::get_instance_from_primary_key_or_slug( $pk );

if ( ! $supplier ) {
	echo 'Not Found';
	return;
}

$msg = '';
$form_action = gp_if_set( $_POST, 'form_action' );

switch( $form_action ) {
	case 'edit':

		$deletion_slug = gp_if_set( $_POST, 'deletion_slug' );

		if ( $deletion_slug && $deletion_slug == $supplier->get( 'slug' ) ){

			if ( $supplier->is_used_by_rims() || $supplier->is_used_by_tires() ) {
				$msg = 'Supplier cannot be deleted while its in use by either rims or tires.';
			} else {
				$deleted = $supplier->delete_self_if_has_singular_primary_key();
				$msg = $deleted ? 'Supplier Deleted' : 'Deletion not successful';
			}

		} else {

			$filter_email_value = function( $in ){
				return implode( ', ', get_email_array_from_string_or_array( $in ));
			};

			// remember to allow comma sep list of emails
			$updated = $supplier->update_database_and_re_sync( array(
				'supplier_name' => get_user_input_singular_value( $_POST, 'supplier_name' ),
				'supplier_order_email' => $filter_email_value( gp_if_set( $_POST, 'supplier_order_email' )  ),
				'supplier_order_email_us' => $filter_email_value( gp_if_set( $_POST, 'supplier_order_email_us' )  ),
			));

			$msg = $updated ? 'Update Successful' : 'Update Not Successful';
		}

		break;
	default:
		break;
}

// get model again even if not updated
$supplier = DB_Supplier::get_instance_from_primary_key_or_slug( $pk );

echo '<div class="admin-section general-content">';

if ( ! $supplier ) {
	echo '<h2>Supplier not found in database</h2>';
} else {

	// echo wrap_tag( html_link( get_admin_archive_link( DB_suppliers ), 'Suppliers' ), 'h1' );
	echo '<h1>Supplier</h1>';
	echo '<p>An email will be sent to the email shown below when an order is checked out.</p>';
	echo '<p>Using this supplier: <a href="' . get_admin_archive_link( 'rims', [ 'supplier' => $supplier->get( 'slug' ) ] ) . '">Rims</a>, <a href="' . get_admin_archive_link( 'tires', [ 'supplier' => $supplier->get( 'slug' ) ] ) . '">Tires</a></p>';
	echo render_html_table_admin( false, [ $supplier->to_array_for_admin_tables() ], [] );
	echo '</div>';

	echo '<div class="admin-section general-content">';

	// FORM
	echo '<form class="form-style-basic" method="post">';

	echo '<input type="hidden" name="form_action" value="edit">';

	echo '<div class="form-items">';

	echo get_form_header( 'Edit' );

	// response
	echo $msg ? get_form_response_text( $msg ) : '';

	echo $supplier->get_simple_form_input( 'supplier_name' );
	echo $supplier->get_simple_form_input( 'supplier_order_email' );
	echo $supplier->get_simple_form_input( 'supplier_order_email_us' );

	echo get_form_input( array(
		'name' => 'deletion_slug',
		'label' => 'Type the supplier_slug here to DELETE this supplier',
	));

	echo get_form_submit( array(
		'text' => 'Submit',
	));

	echo '</div>'; // form-items

	echo '</form>';
	echo '</div>';

}


