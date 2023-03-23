<?php

?>

<div class="admin-section general-content">
	<form class="form-style-basic js-remove-empty-on-submit" method="get" action="<?php echo get_admin_archive_link( 'rims' ); ?>">
		<?php

        $import_date_not = 'import_date' . GET_VAR_NOT_EQUAL_TO_APPEND;

		$not = [ 'supplier', 'import_name', 'import_date', 'page_num', $import_date_not ];
		echo get_hidden_inputs_from_array( get_array_except( $_GET, $not ), true );

		echo '<div class="form-items inline">';

		$stuff = function( $col ){
			echo get_form_select_from_unique_column_values( 'rims', $col, get_user_input_singular_value( $_GET, $col ) );
		};

		// NOTE: Columns with boolean values (or any false like values) do not work at the moment and therefore are commented out below.
		$stuff( 'supplier' );
		$stuff( 'brand_slug' );
		$stuff( 'model_slug' );
		$stuff( 'import_name' );
		$stuff( 'import_date' );
		echo get_form_select_from_unique_column_values( 'rims', 'import_date', get_user_input_singular_value( $_GET, $import_date_not ), $import_date_not );
//		$stuff( 'stock_unlimited_ca' );
//		$stuff( 'stock_discontinued_ca' );
//		$stuff( 'sold_in_ca' );
		$stuff( 'stock_update_id_ca' );
//		$stuff( 'stock_unlimited_us' );
//		$stuff( 'stock_discontinued_us' );
//		$stuff( 'sold_in_us' );
		$stuff( 'stock_update_id_us' );

        $stuff( 'sync_id_insert_ca' );
        $stuff( 'sync_date_insert_ca' );
        $stuff( 'sync_id_update_ca' );
        $stuff( 'sync_date_update_ca' );
        $stuff( 'sync_id_insert_us' );
        $stuff( 'sync_date_insert_us' );
        $stuff( 'sync_id_update_us' );
        $stuff( 'sync_date_update_us' );


		echo '</div>'; // form-items

		echo '<p><button type="submit">Filter</button></p>';
		echo '<br>';
		echo get_admin_edit_page_possible_filters_text();
		echo '<br>';
		echo '<p><a href="' . get_admin_archive_link( 'rims' ) . '">Reset</a></p>';
		echo '<br>';

		?>
	</form>
</div>

