<?php

?>

<div class="admin-section general-content">
    <?php
    echo wrap_tag( get_admin_inventory_related_links( 'stock_updates' ), 'p' );
    ?>
	<form class="form-style-basic js-remove-empty-on-submit" method="get" action="<?php echo get_admin_archive_link( 'rims' ); ?>">
		<?php

		$import_date_not = 'import_date' . GET_VAR_NOT_EQUAL_TO_APPEND;

		$not = [ 'supplier', 'import_name', 'import_date', 'page_num', $import_date_not ];
		echo get_hidden_inputs_from_array( get_array_except( $_GET, $not ), true );

		echo '<div class="form-items inline">';

		$stuff = function( $col ){
			echo get_form_select_from_unique_column_values( 'stock_updates', $col, get_user_input_singular_value( $_GET, $col ) );
        };

		$stuff( 'stock_description' );
		$stuff( 'stock_type' );
		$stuff( 'stock_locale' );


		echo '</div>'; // form-items

		echo '<p><button type="submit">Filter</button></p>';
		echo '<br>';
		echo get_admin_edit_page_possible_filters_text();
		echo '<br>';
		echo '<p><a href="' . get_admin_archive_link( 'stock_updates' ) . '">Reset</a></p>';
		echo '<br>';

		?>
	</form>
</div>

