<?php

// ajax response
$ret = array();

// whether or not to include the compare queue html in the ajax response, possibly always true, even if something
// unexpected happened while making changes (like... removing an item that didn't exist, doesn't matter, just render)
$render = true;

// whether the action was successful. this may or may not matter. once again, if someone tries to remove
// and item and it wasn't there, who gives a shit. just render the lightbox. but.. still, lets just track this
// data so we can send it back, and then we'll decide what to do with it, if we think there is something we need to do.
$success = false;

// tire or rim part number or nothing
$part_number = get_user_input_singular_value( $_POST, 'part_number' );

// tire or rim
$type = get_user_input_singular_value( $_POST, 'type' );

// what are we doing with the tire or rim or the tire or rim part number or nothing
$action = get_user_input_singular_value( $_POST, 'action' );

// turn part number into a real product
$product = null;
if ( $part_number ) {
	if ( $type === 'tire' ) {
		$product = DB_Tire::create_instance_via_part_number( $part_number );
	} else if ( $type === 'rim' ) {
		$product = DB_Rim::create_instance_via_part_number( $part_number );
	}
}

if ( $action === 'clear' && $type ) {
	$cleared = clear_compare_queue( $type );
	$success = ( $cleared );
	$render = true;
} else if ( $action === 'remove' && $product ) {

	$removed = remove_from_compare_queue( $product );
	$success = ( $removed );
	// render even if item wasnt found
	$render = true;

} else if ( $action === 'add' && $product ) {
	$added = add_to_compare_queue( $product );
	// I think you get the idea by now
	$success = ( $added );
	$render = true;
} else {

	$success = false;

	// render i guess
	// remember there is no sensitive data in the lightbox html
	// we quite likely might render the queue as well when someone visits the compare-tires or compare-rims page
	$render = true;
}

$ret['success'] = ( $success );
if ( $render ) {
	$ret['lightbox'] = render_compare_queue( $type );
}

Ajax::echo_response( $ret );
exit;


