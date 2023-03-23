<?php

$do_submit = function(){

    $print = function( $msg ){
        $html = gp_is_singular( $msg ) ? wrap_tag( $msg ) : get_pre_print_r( $msg );
        echo $html;
    };

    start_time_tracking( 'supp' );

    $print_time = function( $msg = '' ) use( $print ){
        $elapsed = end_time_tracking( 'supp' );
        $print( trim( "[$elapsed] $msg") );
    };

    $print( '<br><br>-------------------<br><br>' );
    $print( 'Submit...');
    $cmp_hash = gp_if_set( $_POST, 'cmp_hash' );
    $print( $cmp_hash ? 'Compare Prev' : 'Do not compare prev' );

    $instances = Supplier_Inventory_Supplier::get_all_supplier_instances();
    $suppliers_submitted = get_user_input_array_value( $_POST, 'suppliers' );

    // filter out zero or non integer values
    $suppliers_to_run = $suppliers_submitted ? array_filter( $suppliers_submitted, function( $v ){
        return $v && gp_is_integer( $v );
    } ) : [];

    // sort ascending (in array keys)
    asort( $suppliers_to_run );

    // get an instance from the hash key
    $get_instance = function( $hash_key ) use( $instances ){

        if ( $instances ) {
            /** @var Supplier_Inventory_Supplier $instance */
	        foreach ( $instances as $instance ) {
                if ( $hash_key && $instance::HASH_KEY == $hash_key ){
                    return $instance;
                }
            }
        }

        return null;
    };

    foreach ( $suppliers_to_run as $hash_key => $order ){

	    $print( '<br><br>-------------------<br><br>' );

        $instance = $get_instance( $hash_key );

        if ( ! $instance ) {
            $print( 'Instance not found..');
            continue;
        }

        $hk = $instance::HASH_KEY;

        $print_time( "Before: $hk" );
        $print( "hash value: " . Supplier_Inventory_Hash::get_hash( $hk ) );

        if ( $cmp_hash ) {
            $result = Supplier_Inventory_Supplier::run_import_with_hash_checks( $instance );
        } else {
            $result = Supplier_Inventory_Supplier::run_import_without_hash_checks( $instance );
        }

        // I believe if we compare the hash of the data and its the same, then the supplier
        // will not be setup.
        if ( isset( $result->supplier ) ) {
            $result->supplier->save_log_file();
        } else {
            log_data( "$hk: Supplier not setup sufficiently to log this data (likely, the previous and current hash of the data is the same).", "admin-backend-manual-inventory-submit" );
        }

        $print_time( "After: $hk" );
        $print( "hash value: " . Supplier_Inventory_Hash::get_hash( $hk ) );

        $db_stock_update = $result && isset( $result->db_stock_update ) ? $result->db_stock_update : false;

        $print( $db_stock_update ? $db_stock_update : 'False-like result' );

    }

};

$output = '';
if ( gp_if_set( $_POST, 'do_submit' ) ) {
    ini_set( 'error_reporting', E_ALL );
    ini_set( 'display_errors', 1 );
    ob_start();
    $do_submit();
    $output = ob_get_clean();
}

$instances = Supplier_Inventory_Supplier::get_all_supplier_instances();

?>

    <form action="" method="post" class="">

        <input type="hidden" name="do_submit" value="1">

        <p>Run multiple supplier imports at once... 0 to skip, enter positive integer to run in that order.</p>

        <?php

        /** @var Supplier_Inventory_Supplier $instance */
        foreach ( $instances as $x => $instance ) {

            $hash_key = $instance::HASH_KEY;

            echo '<br>';
            echo get_form_input( array(
                'name' => "suppliers[$hash_key]",
                'label' => $hash_key,
                'id' => 'suppliers_' . $hash_key,
                // 'value' => $x + 1,
                'value' => 0
            ) );
        }

        echo '<br>';
        echo get_form_checkbox( array(
            'name' => 'cmp_hash',
            'label' => 'Compare to previous data and skip if the same',
            'value' => 1,
            'checked' => false,
        ));

        echo get_form_submit();

        ?>

    </form>

<?php

echo $output;

// in production we'll never see the output due 504 gateway timeout but script
// will still complete..
if ( $output ) {
    log_data( $output, 'test-supplier-inventory-' . time(), true, true, true );
}

