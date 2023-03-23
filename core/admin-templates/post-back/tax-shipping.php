<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

$nonce = gp_if_set( $_POST, 'nonce' );
$form_submitted = gp_if_set( $_POST, 'form_submitted' );

if ( ! $form_submitted ) {
	goto file_end;
}

$errors = array();

if ( ! validate_nonce_value( 'tax_shipping_post', $nonce ) ) {
	echo 'Invalid nonce please re-load.';
	exit;
}

$region = gp_if_set( $_POST, 'region' );

$db = get_database_instance();

if ( $region ) {
	foreach ( $region as $region_id=>$data ) {

		$region_id = (int) $region_id;

		$ex = DB_Region::create_instance_via_primary_key( $region_id );

		if ( ! $ex ) {
			echo 'Invalid region ID';
			continue;
		}

		$price_tire = get_user_input_singular_value( $data, 'price_tire' );
		$price_rim = get_user_input_singular_value( $data, 'price_rim' );
		$price_mounted = get_user_input_singular_value( $data, 'price_mounted' );

		// sometimes not set.. if checkbox is not checked
		$allow_shipping = gp_if_set( $data,'allow_shipping', null );

		$tax_rate = get_user_input_singular_value( $data, 'tax_rate' );

		$upsert = $db->upsert( DB_tax_rates, array(
			'region_id' => $region_id,
			'tax_rate' => $tax_rate,
		), array(
			'region_id' => $region_id,
		));

		if ( ! $upsert ) {
			$errors[] = 'Tax information for region ID (' . $region_id . ') may have not been updated.';
		}

		// tax rates for CA only - update: tax rates are now for both CA and US
//		if ( $ex->get( 'country_code' ) === 'CA' ) {
//			$upsert = $db->upsert( DB_tax_rates, array(
//				'region_id' => $region_id,
//				'tax_rate' => $tax_rate,
//			), array(
//				'region_id' => $region_id,
//			));
//
//			if ( ! $upsert ) {
//				$errors[] = 'Tax information for region ID (' . $region_id . ') may have not been updated.';
//			}
//		}

		// Update shipping rates now
		$upsert = $db->upsert( DB_shipping_rates, array(
			'region_id' => $region_id,
			'price_tire' => $price_tire,
			'price_rim' => $price_rim,
			'price_mounted' => $price_mounted,
			'allow_shipping' => $allow_shipping ? 1 : 0,
		), array(
			'region_id' => $region_id,
		));

		if ( ! $upsert ) {
			$errors[] = 'Shipping information for region ID (' . $region_id . ') may have not been updated.';
		}

	}
}

if ( $errors ) {
	$msg = gp_parse_error_string( $errors );
} else {
	$msg = '<p>Updates have been made successfully.</p>';
}

gp_set_global( 'postback_msg', $msg );

// has goto
file_end: