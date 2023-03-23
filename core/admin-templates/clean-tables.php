<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

page_title_is( 'Clean Tables' );

$response = array();

$delete_from_rim_brands = get_user_input_array_value( $_POST, 'delete_from_rim_brands' );

// post back to clean up different tables
// NOTE: the actions below will simply take a list of IDs and attempt to delete them (without verifying those IDs).
// this is where our foreign key constraints (which are all in place) become pretty important to protect data
// both from coding error and or attempting csrf attacks or w/e else.
if ( isset( $_POST['nonce'] ) ) {

    $response[] = 'Form submitted...';

    if ( ! validate_nonce_value( 'admin_clean_tables', $_POST['nonce'], true ) ) {
        $response[] = 'Nonce error, please re-navigate to the page.';
    } else {

	    $items = array();

	    // not doing suppliers for 2 reasons: 1. theres probably only about 5
        // 2. have to check foreign keys in 2 tables (rims and tires) which the re-useable code
        // does not support. having a supplier in the DB does no harm... of course
        // some imports will have typos which will introduce new suppliers that have no purpose...
        // but idk, we'll have to just manually clean these every year or w/e. if anything
        // ill put a deletion checkbox on the single supplier page if they can be deleted.
//	    $items[] = array(
//		    'listen' => 'delete_from_suppliers',
//		    'class' => 'DB_Supplier',
//	    );

	    $items[] = array(
		    'listen' => 'delete_from_rim_models',
		    'class' => 'DB_Rim_Model',
	    );

	    $items[] = array(
		    'listen' => 'delete_from_rim_brands',
		    'class' => 'DB_Rim_Brand',
	    );

	    $items[] = array(
		    'listen' => 'delete_from_rim_finishes',
		    'class' => 'DB_Rim_Finish',
	    );

	    $items[] = array(
		    'listen' => 'delete_from_tire_brands',
		    'class' => 'DB_Tire_Brand',
	    );

	    $items[] = array(
		    'listen' => 'delete_from_tire_models',
		    'class' => 'DB_Tire_Model',
	    );

	    array_map( function ( $item ) use ( &$response ) {

		    $listen = gp_if_set( $item, 'listen' );

		    /** @var DB_Table $class */
		    $class = gp_if_set( $item, 'class' );

		    if ( ! $listen ) {
			    return;
		    }

		    $delete = get_user_input_array_value( $_POST, $listen );

		    if ( $delete && is_array( $delete ) ) {

			    // put this inside of if ( $delete ) because only 1 item is submitted each time..
			    $response[] = $listen . ':';

			    foreach ( $delete as $primary_key ) {

				    $pre = '[object with ID: ' . $primary_key . ']';

				    $obj = $class::create_instance_via_primary_key( $primary_key );

				    // not doing our own logic here to ensure the row must not be deleted, this
                    // we will have to let foreign key constraints handle this.
				    if ( $obj ) {

					    $deleted = $obj->delete_self_if_has_singular_primary_key();

					    if ( $deleted ) {
						    $response[] = $pre . ' Deleted.';
					    } else {
						    $response[] = $pre . ' Not deleted.';
					    }

				    } else {
					    $response[] = $pre . ' Not found.';
				    }
			    }
		    }

	    }, $items );
    }
}

cw_get_header();
Admin_Sidebar::html_before();

?>

    <div class="admin-section general-content">
        <h1>Clean Up</h1>
        <p>Cleaning up Brands/Models/Finishes is optional. Your site should work in the same way even if you don't clean them up.</p>
		<p>If the list below is large or some items for sure won't be used anymore, then it's good to clean them up. To clean them up (delete them), click Select All, and Delete Selected.</p>
        <p>If no items show up in the list below, it means they cannot be deleted, because, for example, you cannot delete a tire brand if tires exist which use that brand.</p>
		<?php
		if ( $response ) {
			echo '<form class="form-style-basic">';
			echo get_form_response_text( gp_array_to_paragraphs( $response ) );
			echo '</form>';
		}
		?>

    </div>

    <div class="admin-section">
		<?php
		echo delete_foreign_objects_form_table( 'rim_models', 'rim_model_id', 'rims', 'model_id' );
		?>
    </div>

    <div class="admin-section">
		<?php
		echo delete_foreign_objects_form_table( 'rim_brands', 'rim_brand_id', 'rims', 'brand_id' );
		?>
    </div>

    <div class="admin-section">
		<?php
        // its complicated but due to some slight redundancies in our database tables, its ok to only cross reference the rims table when deleting
        // rim finishes, even though finishes related to models which relate to brands and.. idk its just confusing.
        // there are also foreign key constraints in place so we shouldn't lose any data that is necessary, and the items
        // that show up in the list should be ok for deletion. In the worst case scenario, maybe some tables will have to be deleted in a certain
        // order but so far deleting all at once seems to work fine as well.
		echo delete_foreign_objects_form_table( 'rim_finishes', 'rim_finish_id', 'rims', 'finish_id' );
		?>
    </div>

    <div class="admin-section">
		<?php
		echo delete_foreign_objects_form_table( 'tire_brands', 'tire_brand_id', 'tires', 'brand_id' );
		?>
    </div>

    <div class="admin-section">
		<?php
		echo delete_foreign_objects_form_table( 'tire_models', 'tire_model_id', 'tires', 'model_id' );
		?>
    </div>

<?php

Admin_Sidebar::html_after();
cw_get_footer();
