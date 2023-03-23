<?php


foreach ( [ 'tires', 'rims' ] as $tbl ) {

	$db = get_database_instance();
	$p = [];
	$q = '';
	$q .= 'SELECT part_number, LENGTH(part_number) AS length ';
	$q .= 'FROM ' . $tbl . ' ';
	$q .= '';
	$q .= '';
	$q .= 'ORDER BY length ASC ';
	$q .= ';';

	$results = $db->get_results( $q, $p );
	$results = gp_convert_object_to_array_recursive( $results );

	echo '<h1>' . $tbl . '</h1>';
	echo '<p>All part numbers used in database sorted by their length</p>';
	echo implode( ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ',  array_column( $results, 'part_number' ) );
}



