<?php

use GoogleShopping as Gs;

$a = @$_GET['action'];

$main_page = true;
$display = false;
$rows = [];
$omitted = [];
$db_rows = [];

if ( $a === 'download' || $a === 'view' ) {

    $main_page = false;

    $db_rows = Gs\get_rims_data();
    list( $rows, $omitted ) = Gs\map_rims_data( $db_rows );

    if ( $a === 'download' ) {

//        echo get_pre_print_r( $rows );
//        echo get_pre_print_r( $omitted );
//        exit;

        do{

            if ( empty( $rows ) ) {
                echo "No in stock/sold product found.";
                exit;
            }

            $date = date( "Y-m-d-G-i-a" );
            $filename = "google-shopping-rims-" . $date . '.csv';

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            $header_row = $rows ? array_keys( $rows[0] ) : [];

            if ( ! $header_row ) {
                break;
            }

            $_rows = array_merge( [ $header_row ], $rows );

            $fp = fopen('php://output', 'wb');

            foreach ( $_rows as $row ) {
                fputcsv($fp, $row);
            }

            fclose($fp);

            exit;

        } while( false );

    }

    if ( $a === 'view' ) {
        $display = true;
    }
}

cw_get_header();
Admin_Sidebar::html_before();

?>

<h3 style="margin-bottom: 10px;">Google Shopping CSV Export</h3>
<?= $main_page ? '<p>Choose an option.</p><br>' : '<br>'; ?>

    <form id="" action="<?= ADMIN_URL; ?>" method="get">
        <input type="hidden" name="page" value="google_shopping">
        <button type="submit" name="" value="">Main Page</button>
        <button type="submit" name="action" value="download">Download Rims CSV</button>
        <button type="submit" name="action" value="view">View Rims CSV</button>
    </form>

<?php

if ( $display ) {

    echo '<br><br>';

    echo wrap_tag( count( $rows ) . " Rim(s), " . count( $omitted ) . " Omitted." );

    $err_frequencies = array_count_values( array_map( function( $o ){
        return @$o['err'];
    }, $omitted ) );

    echo '<br>';

    echo wrap_tag( "Error frequencies: " );
    echo get_pre_print_r( $err_frequencies, true );

    echo '<br><br>';

    echo render_html_table_admin( null, $rows, [
        'title' => "Rims (" . count( $rows ) . ") ",
        'sanitize' => true,
    ] );

    echo '<br><br>';

    echo render_html_table_admin( null, $omitted, [
        'title' => "Rims Omitted (" . count( $omitted ) . ") ",
        'sanitize' => true,
    ] );

}

Footer::add_raw_html( function(){
    ?>
    <script>
        jQuery(document).ready(function(){
        });
    </script>
    <?php
});


Admin_Sidebar::html_after();
cw_get_footer();
