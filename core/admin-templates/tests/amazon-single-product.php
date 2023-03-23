<?php

$submitted = gp_if_set( $_POST, '_submitted' ) === 'askduhjkhgasdybv2asd';

$response = new stdClass();
$response->msgs = [];

$run = function() use( &$response ){

	$response->msgs[] = 'Submitted...';

	$feed_submission_id = get_user_input_singular_value( $_POST, 'feed_submission_id' );
	$sku = get_user_input_singular_value( $_POST, 'sku' );
	$new_lib = (bool) gp_if_set( $_POST, 'new_lib' );
	$stock = (int) gp_if_set( $_POST, 'stock' );
	$mws_locale = gp_if_set( $_POST, 'mws_locale' );

	if ( ! mws_is_locale_valid( $mws_locale ) ) {
		$response->msgs[] = "Invalid locale.";
		return;
	}

	if ( $feed_submission_id ) {

		// Check on previously submitted Feed
		$amazon = new Amazon_MWS( $mws_locale );

		$response->msgs[] = "Checking on feed submission ID: $feed_submission_id...";

		try{
			$report = $amazon->client->GetFeedSubmissionResult( $feed_submission_id );
			$response->msgs[] = "Result: " . get_pre_print_r( $report );
		} catch ( Exception $e ) {
			$response->msgs[] = "Result (exception): " . get_pre_print_r( $e );
		}

		return;
	}

	if ( ! $sku || ! $mws_locale ){
		$response->msgs[] = "Missing sku or locale.";
		return;
	}

	// Update a single product
	$product_update_array = array(
		$sku => $stock,
	);

	if ( $new_lib ) {
		$response->msgs[] = 'Using New Lib';
		$response->msgs[] = "Update array: " . get_pre_print_r( $product_update_array );
		$result = mws_submit_inventory( $product_update_array, $mws_locale );

	} else {
		$amazon = new Amazon_MWS( $mws_locale );
		$response->msgs[] = 'Using MCS MWS Lib';
		$response->msgs[] = "Update array: " . get_pre_print_r( $product_update_array );
		$result = $amazon->client->updateStock( $product_update_array );
	}

	$response->msgs[] = "Result: " . get_pre_print_r( $result );

};

if ( $submitted ) {
	$run();
}

echo '<form method="post" class="form-style-basic">';

echo '<input type="hidden" name="_submitted" value="askduhjkhgasdybv2asd">';

$msg = "Update the stock amount for a single product. Testing tool only. Not recommended to use this for other reasons.";
echo get_form_header( 'Amazon submit inventory', [ 'tagline' => $msg ] );

echo '<div class="form-items">';

echo get_form_select( array(
	'name' => 'mws_locale',
	'label' => 'Locale',
), array(
	'items' => array(
		'' => '',
		MWS_LOCALE_CA => MWS_LOCALE_CA,
		MWS_LOCALE_US => MWS_LOCALE_US
	)
) );

// 50459017976
echo get_form_input( array(
	'name' => 'feed_submission_id',
	'label' => 'Check on previously submitted feed submission ID (include this/locale OR stock/sku/locale)',
));

echo get_form_checkbox( array(
	'name' => 'new_lib',
	'label' => 'Use New Library',
	'value' => 1,
));

echo get_form_input( array(
	'type' => 'text',
	'name' => 'sku',
	'label' => 'SKU',
));

echo get_form_input( array(
	'type' => 'number',
	'name' => 'stock',
	'label' => 'Stock',
	'atts' => array(
		'min' => 0,
		'step' => 1,
	)
));

echo $response->msgs ? get_form_response_text( gp_parse_error_string( $response->msgs ) ) : '';

echo get_form_submit();

echo '</div>';
echo '</form>';

