<?php

if ( ! cw_is_admin_logged_in() ) {
    echo 'Not authorized.';
	exit;
}

$response = array();
$response['delete_count'] = 0;
$delete_tires = gp_if_set( $_POST, 'delete_tires' );

if ( $delete_tires && is_array( $delete_tires ) ) {
	foreach ( $delete_tires as $part_number=>$delete ) {

		if ( ! $delete || $delete === '0' || $delete === 'false' )
			continue;

		// TIRES TIRES TIRES TIRES TIRES *********** dont get confused with rims
		$db = get_database_instance();
		$q = '';
		$q .= 'DELETE FROM ' . $db->tires . ' ';
		$q .= 'WHERE part_number = ? ';
		$q .= ';';
		$st = $db->pdo->prepare( $q );
		$st->bindParam( 1, $part_number );
		$delete = $st->execute();

		// tells the javascript to find elements based on selector from within the form and remove them from the page
		if ( $delete ) {
			$response['delete_within'][] = '.part-number-' . gp_make_letters_numbers_underscores( $part_number );
			$response['delete_count']++;
		}
	}
}

$response['output'] = '<p>' . $response['delete_count'] . ' products deleted.</p>';

Ajax::echo_response( $response );
exit;
