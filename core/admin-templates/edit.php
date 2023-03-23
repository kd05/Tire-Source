<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

$table = gp_esc_db_table( get_user_input_singular_value( $_GET, 'table' ) );

$pk = (int) @$_GET['pk'];

$page_title = $pk ? $table . ' - ' . $pk : $table;
page_title_is( $page_title );

// dev tool to delete and re-create an entire database table - not going to want this on in production
$allow_deletion_tool = ! IN_PRODUCTION;

// copy paste this if u need it live
if ( isset( $_GET['deletion'] ) && $_GET['deletion'] === '87h87h23d876gh8A62G8SD6GF7632GSEF8H36GS9E7YHD0H8DF78HG' ) {
    $allow_deletion_tool = true;
}

// returns false/null if table doesn't exist
$db_object = DB_Table::create_empty_instance_from_table( $table );

$form_submitted = (int) gp_if_set( $_POST, 'form_submitted' );
$delete = gp_if_set( $_POST, 'delete' );

$msg = '';
if ( $allow_deletion_tool && $form_submitted === 1 && $delete && $delete === $table ) {
    $drop = drop_and_create_table( $table, true );
	$msg = 'drop and create table (' . $drop . ')';
}

cw_get_header();
Admin_Sidebar::html_before();

if ( $allow_deletion_tool ) { ?>
    <form action="" method="post" class="admin-section general-content">
    <p><strong>Delete Tables (hidden and disabled in production). Enter a table name below and it will be dropped and re-created (all data will be lost).</strong></p>
    <p><strong><?php echo $msg; ?></strong></p>
    <input type="hidden" name="form_submitted" value="1">
    <input type="text" name="delete" value="">
    <input type="submit">
    </form>
<?php } ?>

<?php

queue_dev_alert( '(empty) object', $db_object );

// show table and stuff
if ( $db_object ) {

    $single_replace = CORE_DIR . '/admin-templates/edit/single-' . $table . '.php';
    $single_before = CORE_DIR . '/admin-templates/edit/single-before/' . $table . '.php';
    $single_after = CORE_DIR . '/admin-templates/edit/single-after/' . $table . '.php';

    if ( $pk && file_exists( $single_replace ) ) {

        // if you use on of these pages you are responsible for displaying the
        // database table associated with the query params.
        include $single_replace;
    } else {

        if ( $pk && file_exists( $single_before ) ) {
            include $single_before;
        }

        // this handles single and archive pages.
        $p = new Admin_Archive_Page( $db_object );
        echo $p->render();

        if ( $pk && file_exists( $single_after ) ) {
            include $single_after;
        }
    }


} else {

    // this is kind of like our really basic landing page..
    $map = DB_Table::get_table_class_map();
    $tables = array_keys( $map );

    echo '<div class="admin-section general-content">';

    echo '<h2>Clean Up</h2>';
    echo '<p><a href="' . get_admin_page_url( 'clean_tables' ) . '">This tool</a> identifies any brands, models, and finishes that are no longer used by rims or tires. You don\'t have to delete them, but you may want to.</p>';

	echo '<h2>Data Consistency Checking</h2>';
	echo '<p><a href="' . get_admin_page_url( 'columns' ) . '">This tool</a> lists some unique column values from your tables which can help you identify typos in your import files.</p>';

	echo '<h2>Tables</h2>';
	echo '<p>If a table name below is also in the sidebar, then you usually want to use the sidebar link instead (ie. Orders).</p>';

    echo '<ul>';

    if ( $tables ) {
        foreach ( $tables as $tbl ) {

            $after = '';

	        if ( $tbl === DB_amazon_processes ) {
		        $after = 'used to sync website inventory to amazon';
	        }

	        if ( $tbl === DB_stock_updates ) {
		        $after = 'check on automated supplier inventory updates';
	        }

	        if ( $tbl === DB_suppliers ) {
		        $after = 'edit supplier emails';
	        }

            if ( $tbl === DB_tires ) {
                $after = 'search/delete tires';
            }

	        if ( $tbl === DB_rims ) {
		        $after = 'search/delete rims';
	        }

	        if ( $tbl === DB_tire_brands ) {
		        $after = 'edit names, logo\'s, descriptions';
	        }

	        if ( $tbl === DB_tire_models ) {
		        $after = 'edit names, images, descriptions';
	        }

	        if ( $tbl === DB_rim_brands ) {
		        $after = 'edit names, logo\'s, descriptions';
	        }

	        if ( $tbl === DB_rim_models ) {
		        $after = 'edit names, descriptions';
	        }

	        if ( $tbl === DB_rim_finishes ) {
		        $after = 'edit names, images';
	        }

            if ( $after ) {
                $after = ' (' . $after . ')';
            }

            echo '<li><a href="' . get_admin_archive_link( $tbl ) . '">' . $tbl . '</a>' . $after . '</li>';
        }
    }
	echo '</ul>';
    echo '</div>';
}

Admin_Sidebar::html_after();
cw_get_footer();