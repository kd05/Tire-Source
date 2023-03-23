<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

$upsert_response = '';

// make_sub_sizes_for_testing();

// expecting only digits like 2255018, but if 225/50R18 is provided, that should be fine too
// dont forget, we could also have 225/50R18-23545R18
$target = get_user_input_singular_value( $_GET, 'target' );
$target = simplify_tire_size( $target );

$size               = $target ? new Tire_Atts_Pair( $target ) : false;
$size_is_provided   = ( $target );
$size_is_valid      = $size ? $size->valid : false;
$size_found_results = false;

$form_submitted = (bool) gp_if_set( $_POST, 'form_submitted' );
$insert_success = false;

if ( $form_submitted ) {

	$msgs = array( 'Form Submit Overview:' );

	while ( true ) {

		// hidden form value, make sure its the same as in $_GET
		$_target = get_user_input_singular_value( $_POST, '_target' );

		if ( $_target !== $target ) {
			$msgs[] = 'Sizes do not match.';
			break;
		}

		if ( ! $size_is_valid ) {
			$msgs[] = 'Invalid size.';
			break;
		}

		$db = get_database_instance();

		$sub_sizes         = gp_if_set( $_POST, 'sub_sizes' );
		$sub_sizes_arr     = explode( "\n", $sub_sizes );
		$sub_sizes_objects = array();
		$errors            = array();

		if ( $sub_sizes_arr ) {

			foreach ( $sub_sizes_arr as $ss ) {

				$ss = trim( $ss );

				if ( ! $ss ) {
					continue;
				}

				// conver 225/50R17 into 2255017
				$ss = simplify_tire_size( $ss );

				$obj = new Tire_Atts_Pair( $ss, '-' );

				if ( $obj->valid ) {
					$sub_sizes_objects[] = $obj;
				} else {
					$errors[] = 'Invalid sub size string: ' . $ss;
				}
			}
		}

		if ( $errors ) {
			$msgs = array_merge( $msgs, $errors );

		} else {

			if ( delete_sub_sizes( $size ) ) {
			} else {
				$msgs[] = 'No sizes removed.';
			}

			$insert_errors = array(); // passed by reference

			//            for ( $x = 0; $x <= 300; $x++ ) {
			//	            insert_sub_sizes( $sub_sizes_objects[array_rand($sub_sizes_objects)], $sub_sizes_objects, $insert_errors );
			//            }

			insert_sub_sizes( $size, $sub_sizes_objects, $insert_errors );

			if ( $insert_errors ) {
				$msgs = array_merge( $msgs, $insert_errors );
			} else {
				$insert_success = true;
				$msgs[]         = 'All sizes inserted.';
			}
		}

		// might want to not delete this
		break;

	} // while true

	$upsert_response = gp_array_to_paragraphs( $msgs );
}

$results = $size_is_valid ? get_sub_sizes( $size ) : array();

// generate the value for the text area
$sub_sizes = '';

if ( $form_submitted && ! $insert_success ) {
	$sub_sizes = gp_sanitize_textarea( gp_if_set( $_POST, 'sub_sizes' ) );
} else if ( $results ) {
	foreach ( $results as $row ) {
		$db_sub    = new DB_Sub_Size( $row );
		$sub_sizes .= $db_sub->sub_size->convert_back_to_string() . "\r\n";
	}
}

// HTML
cw_get_header();
Admin_Sidebar::html_before();

page_title_is( 'Substitution Sizes' );

?>
    <div class="admin-section general-content">
        <h1>Substitution Sizes</h1>
		<?php
		if ( $size_is_valid ) {
			echo render_html_table_admin( false, get_sub_size_table_data( $results ), [ 'title' => 'Existing sub sizes for ' . $size->get_nice_name() ] );
       };
		?>
    </div>
<?php

$search_form = '';

$search_form .= '<form class="admin-section form-style-basic" method="get" action="' . ADMIN_URL . '">';

$search_form .= '<input type="hidden" name="page" value="sub_sizes">';

if ( $size_is_provided ) {

	if ( ! $size_is_valid ) {
		$search_form .= get_form_response_text( 'Target size is not valid.' );
	} else if ( ! $results ) {
		// this is does not indicate an error, its just a notice.
		// to insert new size groups, you must search for one that doesn't exist yet
		$search_form .= get_form_response_text( 'No substitution sizes found for target size.' );
	}
}

$search_form .= '<div class="form-items">';

$search_form .= get_form_input( array(
	'name' => 'target',
	'label' => 'Search for a size (ie. 2255018, or 2255018-2454018)',
	'value' => gp_test_input( $target ),
) );

$search_form .= get_form_submit( [ 'text' => 'Search' ] );

$search_form .= '</div>'; // form-items
$search_form .= '</form>';
echo $search_form;

$upsert_form = '';

if ( $size_is_valid ) {

	$form_header = $results ? 'Update sub sizes for target size of: ' . $size->get_nice_name() : 'Add sub sizes for target size of: ' . $size->get_nice_name();
	$upsert_type = $results ? 'update' : 'insert';

	// Form
	$upsert_form .= '<form class="admin-section form-style-basic" method="post">';

	$upsert_form .= '<input type="hidden" name="form_submitted" value="1">';
	$upsert_form .= '<input type="hidden" name="_target" value="' . gp_test_input( $target ) . '">';

	// nonce
	$upsert_form .= get_nonce_input( 'upsert_sub_size', true );

	// insert/update
	$upsert_form .= '<input type="hidden" name="upsert_type" value="' . $upsert_type . '">';

	// header
	$upsert_form .= get_form_header( $form_header );

	if ( $upsert_response ) {
		$upsert_form .= get_form_response_text( $upsert_response );
	}

	$upsert_form .= '<div class="form-items">';

	// Sub Sizes Textarea
	$upsert_form .= get_form_textarea( array(
		'name' => 'sub_sizes',
		'value' => $sub_sizes,
	) );

	$upsert_form .= get_form_submit();

	$upsert_form .= '</div>'; // form-items
	$upsert_form .= '</form>';

	echo $upsert_form;

} else if ( ! $size_is_provided ) {

	// query all, but group in php so we can calculate some aggregate values
	$db = get_database_instance();
	$p  = [];
	$q  = '';
	$q  .= 'SELECT * ';
	$q  .= 'FROM ' . $db->sub_sizes . ' ';
	$q  .= '';
	$q  .= '';
	$q  .= '';
	$q  .= '';

	$order_by = array(
		'target_diameter_1 ASC',
		'target_width_1 ASC',
		'target_profile_1 ASC',
		'target_diameter_2 ASC',
		'target_width_2 ASC',
		'target_profile_2 ASC',
		'sub_diameter_1 ASC',
		'sub_width_1 ASC',
		'sub_profile_1 ASC',
		'sub_diameter_2 ASC',
		'sub_width_2 ASC',
		'sub_profile_2 ASC',
	);

	if ( $order_by ) {
		$q .= 'ORDER BY ' . implode_comma( $order_by ) . ' ';
	}

	$q .= ';';

	$results = $db->get_results( $q, $p );

	$grouped = array();
	$table   = array();

	if ( $results ) {
		foreach ( $results as $row ) {

			$obj = DB_Sub_Size::create_instance_or_null( $row );
			$key = $obj->get_unique_target_size_string();

			if ( ! isset( $grouped[ $key ] ) ) {
				$grouped[ $key ] = array();
			}

			$grouped[ $key ][] = $obj;
		}

		$obj = null;
		$key = null;
	}

	$sub_sizes_url = get_admin_page_url( 'sub_sizes' );

	if ( $grouped ) {
		foreach ( $grouped as $key => $group ) {

			$t         = array();
			$subs      = array();
			$vars      = array(); // variances
			$max_var   = null;
			$first_obj = null;

			if ( $group && is_array( $group ) ) {

				/** @var DB_Sub_Size $obj */
				foreach ( $group as $obj ) {

					if ( $first_obj === null ) {
						$first_obj = $obj;
					}

					$subs[] = link_to_edit_sub_size( $obj->sub_size->get_nice_name( ' - ' ) );
					$vars[] = $obj->get_variance_string();

					if ( abs( $obj->front_variance ) >= abs( $max_var ) ) {
						$max_var = $obj->front_variance;
					}

					if ( abs( $obj->rear_variance ) >= abs( $max_var ) ) {
						$max_var = $obj->rear_variance;
					}

				}

			}

			$t[ 'target' ]           = link_to_edit_sub_size( $first_obj->target_size->get_nice_name() );
			$t[ 'subs' ]             = implode_comma( $subs );
			$t[ 'variances' ]        = implode_comma( $vars );
			$t[ 'largest_variance' ] = format_percent_string( $max_var );

			$table[] = $t;

		}
	}

	echo '<div class="admin-section general-content">';

	echo '<h1>All Sub Sizes</h1>';

	echo render_html_table_admin( false, $table, [] );
	echo '</div>';

}


?>


<?php

Admin_Sidebar::html_after();
cw_get_footer();