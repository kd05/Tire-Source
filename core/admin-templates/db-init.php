<?php

if ( ! cw_is_admin_logged_in() ) {
    exit;
}

$form_submitted = gp_if_set( $_POST, 'form_submitted' );
$create_tables = gp_if_set( $_POST, 'create_tables' );
$delete_table = gp_if_set( $_POST, 'delete_table' );


if ( $form_submitted === 'yes' ) {

    if ( $create_tables === 'yes' ) {
	    define( 'MODIFY_DB', true );
	    include CORE_DIR . '/classes/db-init.php';
    }

    if ( $delete_table ) {
        $db = get_database_instance();
        $params = array();
        $q = '';

        $q .= 'DROP TABLE ' . gp_esc_db_col( $delete_table );

        $q .= '';
        $q .= '';
        $q .= ';';

	    echo '<pre>' . print_r( $q, true ) . '</pre>';

        $st = $db->pdo->prepare( $q );
        $db->bind_params( $st, $params );
        $st->execute();

    }
}

cw_get_header();
Admin_Sidebar::html_before();

?>
    <form action="" method="post">
        <input type="hidden" name="form_submitted" value="yes">
        <button type="submit" name="create_tables" value="yes">Create DB Tables</button>
    </form>
    <br><br>

    <form action="" method="post">
        <input type="hidden" name="form_submitted" value="yes">
        <br>
        <p><label for="delete_table">Delete Table:</label></p>
        <br>
        <p><input type="text" name="delete_table"></p>
        <br>
        <button type="submit">Go</button>
    </form>

    <br><br>
    <p><a href="<?php echo get_db_init_early_link(); ?>">Create Tables URL for when creating tables but before users table exists.</a></p>
    <br><br>


<?php
echo gp_make_singular( $_POST );
?>


<?php
Admin_Sidebar::html_after();
cw_get_footer();