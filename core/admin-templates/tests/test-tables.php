<?php

$db = get_database_instance();
$class_map = DB_Table::get_table_class_map();

echo '<div class="general-content">';

echo '<p>Checks if all DB table objects are setup properly. If we miss an element of Obj::$fields, then $obj->get( \'field\' ) will return null and we might not be aware of it.</p>';

// todo: ideally this stuff should all just be built into the DB_Table() object
if ( $class_map ) {

	foreach ( $class_map as $table=>$class_name ) {
		$obj = DB_Table::create_empty_instance_from_table( $table );
        echo $obj->get_configuration_test_debug_html();
	}
}

echo '</div>';