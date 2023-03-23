<?php
/**
 * manually run a stock level update via csv. this is used as a testing page for
 * what will eventually run on a cron job.
 */

// define nonce secret first
$nonce_secret = 'importing-stock-137723';
include 'post-back/stock-import.php';

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

page_title_is( 'Stock Import' );

cw_get_header();
Admin_Sidebar::html_before();

?>
    <div class="admin-section general-content">

        <p>You can manually run an inventory import by choosing one of the options below.</p>
        <p>These are already setup to run at intervals so you rarely need to do this (mostly a developer tool).</p>

        <form id="stock-import" action="" method="post" class="form-style-basic">
			<?php echo get_nonce_input( $nonce_secret, true ); ?>

			<?php

            if ( isset( $msgs ) && $msgs ) {
	            echo get_form_response_text( gp_parse_error_string( $msgs ) );
            }

            if ( isset( $stock_update ) && $stock_update instanceof DB_Stock_Update ) {
	            echo render_html_table_admin( false, [ $stock_update->to_array_for_admin_tables() ] );
            }

            $class_names = Supplier_Inventory_Supplier::get_all_supplier_class_names();

            $items = array(
                '' => 'Choose 1',
            );

            array_map( function($cls) use( &$items ){
                $hash_key = constant( $cls . '::HASH_KEY' );
                $items[$hash_key] = $hash_key;
            }, $class_names );

            echo get_form_select( array(
                'name' => 'hash_key',
                'label' => 'Manually run a supplier inventory update',
            ), array(
                'items' => $items,
                'current_value' => '',
            ));

            echo get_form_submit( array(
                'text' => 'Run',
            ));

//			echo get_form_input( array(
//				'name' => 'csv_file',
//				'value' => '',
//				'label' => 'CSV File',
//				'type' => 'file',
//			) );
//			echo get_form_input( array(
//				'name' => 'column_part_number',
//				'value' => 'Item',
//				'label' => 'Name of the column for part number',
//			) );
//			echo get_form_input( array(
//				'name' => 'column_stock',
//				'value' => 'Total Onhand',
//				'label' => 'Name of the column for stock level',
//			) );

			?>

        </form>
    </div>
<?php
Admin_Sidebar::html_after();
cw_get_footer();