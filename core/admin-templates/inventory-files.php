<?php
/**
 * Dumps the contents of CSV files provided by suppliers for the purposes of inventory.
 *
 * Can also dump the prepared files which shows the exact inventory levels that
 * we will try to update during an inventory import.
 *
 * I will link to this page from elsewhere in the admin section. There is no
 * UI for selecting the suppliers, the page just renders via some $_GET vars.
 *
 * This page downloads, parses, and displays CSV files via FTP which could be
 * up to 5mb. No guarantee it works for all suppliers if their files are huge.
 */

if ( ! cw_is_admin_logged_in() ) {
    exit;
}

// the hash_key (UID) of the supplier
$hash_key = @$_GET[ 'supplier' ];

// dump raw CSV or parse the data by calling the prepare_for_import() function
$parsed = @$_GET[ 'parsed' ] == "1";

// supplier instance or null
$supplier = Supplier_Inventory_Supplier::get_instance_via_hash_key( $hash_key );

$title = gp_test_input( $hash_key );
$title .= $parsed ? " - parsed" : "- raw";

page_title_is( $title );

cw_get_header();
Admin_Sidebar::html_before();

?>
    <div class="admin-section general-content">

        <h3><?= $title; ?></h3>

        <?php

        if ( ! $supplier ) {
            echo wrap_tag( "Supplier not found." );
        } else {

            // dump CSV file
            if ( ! $parsed ) {

                if ( $ftp = $supplier->ftp ) {

                    /** @var FTP_Get_Csv $ftp */

                    $ftp->run();

                    if ( $ftp->errors ) {
                        echo wrap_tag( "FTP Errors:" );
                        echo get_pre_print_r( $ftp->errors, true );
                    }

                    echo get_pre_print_r( $ftp->get_debug_array(), true );

                    // get contents before deleting the file.
                    $contents = file_get_contents( $ftp->get_local_full_path( false ), false );

                    // delete local file
                    $ftp->unlink();

                    echo '<pre style="white-space: pre-wrap">';
                    echo trim( htmlspecialchars( $contents, ENT_SUBSTITUTE | ENT_IGNORE ) );
                    echo '</pre>';

                } else {
                    echo wrap_tag( "Supplier FTP object not found." );
                }
            }

            // dump the parsed/prepared file
            if ( $parsed ) {

                $supplier->prepare_for_import();

                echo wrap_tag( "Count: " . count( $supplier->array ) );

                // dump the parsed data
                foreach ( $supplier->array as $index => $prod ) {

                    echo implode( ", ", [
                        (int) $index,
                        htmlspecialchars( $prod[ 'part_number' ] ),
                        htmlspecialchars( $prod[ 'stock' ] ),
                    ] );

                    echo "<br>";
                }
            }
        }

        ?>

    </div>
<?php

Admin_Sidebar::html_after();
cw_get_footer();