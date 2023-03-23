<?php

$amazon_process = DB_Amazon_Process::create_instance_via_primary_key( (int) @$_GET['pk'] );

// dev tool to manually delete a process.
// this is useful to manually re-submit inventory as existing processes can otherwise
// make that impossible.
if ( $amazon_process && @$_GET['delete_confirm'] == 44444 ) {
    $amazon_process->delete_self_if_has_singular_primary_key();
    echo "Deleted.";
    exit;
}

?>

<br>
<br>

<?= wrap_tag( "process_mutable_array" ); ?>
<br>
<?= get_pre_print_r( $amazon_process->get_mutable_array(), true ); ?>
<br>

