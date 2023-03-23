<?php



if ( ! cw_is_admin_logged_in() ) {
	exit;
}

page_title_is( 'Columns' );

cw_get_header();
Admin_Sidebar::html_before();

$items = array();

$items[DB_tires]= array(
    'supplier',
    'width',
    'profile',
    'diameter',
    'load_index',
    'load_index_2',
    'speed_rating',
    'is_zr',
    'extra_load',
    'size',
    'price_ca',
    'price_us',
    'import_name',
    'import_date'
);

$items[DB_tire_models]= array(
	'tire_model_type',
	'tire_model_class',
	'tire_model_category',
	'tire_model_run_flat',
	// 'size', // too much info maybe
);

$items[DB_rims] = array(
    'style',
    'type',
    'seat_type',
    'size',
    'bolt_pattern_1',
	'bolt_pattern_2',
    'center_bore',
    'width',
    'offset',
    'diameter',
	'price_ca',
	'price_us',
    'import_name',
    'import_date'
);

$items[DB_rim_finishes] = array(
	'color_1',
	'color_2',
	'finish',
);

$items[DB_suppliers] = array(
    'supplier_order_email',
	'supplier_order_email_us',
);


$_items = array();
foreach ( $items as $tbl=>$cols ) {
	if ( $cols && is_array( $cols ) ) {
		foreach ( $cols as $c1=>$c2 ) {
			$vals = get_all_column_values_from_table( $tbl, $c2 );
			$vals = gp_make_array( $vals );
			$_items[$tbl][$c2] = $vals;
		}
	}
}

$op = '';

$op .= call_user_func( function(){

    ob_start();

    $db = get_database_instance();

    $rims = $db->get_results( "SELECT part_number FROM rims WHERE supplier = \"\"" );
    $tires = $db->get_results( "SELECT part_number FROM tires WHERE supplier = \"\"" );

    $results_to_html = function ( $db_results, $table ) {
        return array_map( function ( $row ) use( $table ) {

            return gp_get_link( get_admin_archive_link( $table, [
                    'part_number' => gp_test_input( $row->part_number ),
            ] ), gp_test_input( $row->part_number ) );
        }, $db_results );
    };

    ?>
    <div class="admin-action general-content">
        <h2>Tires without a Supplier</h2>
        <p><?= implode( ", ", $results_to_html( $tires, 'tires' ) ); ?></p>
        <h2>Rims without a Supplier</h2>
        <p><?= implode( ", ", $results_to_html( $rims, 'rims' ) ); ?></p>
    </div>

    <?php

    return ob_get_clean();
});

if ( $_items ) {
	foreach ( $_items as $tbl=>$data ) {
	    $op .= '<div class="admin-section general-content">';
		$op .= '<h2>' . $tbl . '</h2>';
		if ( $data && is_array( $data ) ) {
			foreach ( $data as $col=>$values ) {
				$op .= '<p>';
				$op .= '<strong>' . $col . ': </strong>';
				$links = array();
				if ( $values && is_array( $values ) ) {

				    // i guess just do some php sorting since our sql function doesn't support sorting
                    // numerically right now. the items are sorted alphabetically by default.
				    switch( $tbl ) {
                        case DB_tires:

                            switch( $col ) {
	                            case 'load_index':
	                            case 'load_index_2':
	                            case 'diameter':
	                            case 'price_us':
                                case 'price_ca':
                                    asort( $values, SORT_NUMERIC );
                                    break;
                            }

                            break;
					    case DB_rims:

						    switch( $col ) {
							    case 'center_bore':
							    case 'width':
							    case 'offset':
							    case 'diameter':
							    case 'price_us':
							    case 'price_ca':
								    asort( $values, SORT_NUMERIC );
								    break;
						    }

						    break;
                    }


					foreach ( $values as $value ) {
						$url = get_admin_archive_link( $tbl, array(
							$col => $value
						));

//						if ( $value === "" ) {
//						    $value = '{empty_string}';
//                        }

						$links[] =  '<a href="' . $url . '">' . $value . '</a>';
					}
				}
				$op .= implode_comma( $links );
				$op .= '</p>';
			}
		}
		$op .= '</div>';
	}
}

?>

    <div class="admin-section general-content">
        <h1>Columns</h1>
        <p>Shows unique values for some database columns. You should look out for: typos, "N/A", or values that don't correspond to the column name. "N/A" values generally should not be used if possible, because the code will not treat it the same as no value at all. For example, an alloy rim with secondary finish of "N/A" will not be considered winter approved because the secondary finish is not empty.</p>
        <p><strong>I highly recommend looking through all of this data after each tire or rim import.</strong></p>
    </div>

    <div class="admin-section general-content columns-debug">
		<?php echo $op; ?>
    </div>

<?php

Admin_Sidebar::html_after();
cw_get_footer();

