<?php
/**
 * @see MWS_Inventory_Tick
 * @see functions-amazon.php
 */

/**
 * The marketplace ID representing U.S. We need this to
 * retrieve or update products sold in U.S. using MWS.
 */
define( 'MWS_MARKETPLACE_ID_CANADA', 'A2EUQ1WTGCTBG2' );

/**
 * The marketplace ID representing U.S. We need this to
 * retrieve or update products sold in U.S. using MWS.
 */
define( 'MWS_MARKETPLACE_ID_US', 'ATVPDKIKX0DER' );

/**
 * The string stored in the database and passed to functions to
 * represent the canadian marketplace in the context of amazon
 * seller central / mws.
 *
 * Not to be confused with APP_LOCALE_CANADA. Basically the same thing,
 * but we should use a different set of constants for amazon specifically,
 * for reasons I don't care to explain.
 */
define( 'MWS_LOCALE_CA', 'canada' );

/**
 * The string stored in the database and passed to functions to
 * represent the canadian marketplace in the context of amazon
 * seller central / mws.
 */
define( 'MWS_LOCALE_US', 'usa' );

/**
 * Amazon MWS
 *
 * amazon calls this a merchant token, but which one of the below items is it?
 */
define( 'MWS_MERCHANT_TOKEN', '##removed' );

// MWS Developer ID: 373562486543
// AWS Access Key ID: AKIAIROPRL4HYURXVXPQ
// Client Secret: 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

define( 'MWS_MARKETPLACE_ID', MWS_MARKETPLACE_ID_CANADA );

// is this right?
define( 'MWS_SELLER_ID', MWS_MERCHANT_TOKEN );

define( 'MWS_ACCESS_KEY_ID', '##removed' );
define( 'MWS_SECRET_ACCESSS_KEY', '##removed' );

/**
 * even though AWS insists this is required things somehow (seem to) work without it,
 * and also, we don't have it. Assuming we could find it at some point in the future,
 * it might not hurt to put it here. I wonder why amazon would say its required and
 * then you don't send it in and then you can request and submit feeds and everything
 * processes with zero warnings and zero errors.
 */
define( 'MWS_AUTH_TOKEN', '' );

/**
 * We may have to be very careful here to maintain the trailing slash.
 *
 * The amazon DOCS do not have a trailing slash, but the library we're using
 * this for gave an example with the trailing slash, so I'm going to assume
 * the library will be checking for this and handling it properly.
 *
 * According to Amazon, using CA or US service URL should produce the same results,
 * .. we have to additionally send in market place ID lists to specify when
 * an action should apply to only one marketplace, but still, lets just
 * use the "correct" service URLs.
 */
define( "MWS_SERVICE_URL_US", "https://mws.amazonservices.com/" );
define( "MWS_SERVICE_URL_CA", "https://mws.amazonservices.ca/" );
define( 'MWS_STORE_NAME_CA', "tiresource_CA" );
define( 'MWS_STORE_NAME_US', "tiresource_US" );

/**
 * Contains config options for one of (2?) MWS libs. Configures both
 * CA and US marketplaces.
 */
define( 'MWS_PHP_AMAZON_MWS_CONFIG_PATH', CORE_DIR . '/other/phpAmazonMWS-config.php' );

/**
 * After requesting a report we will eventually stop requesting to see if its done,
 * if enough time has passed. Otherwise, an unforseen error might lead to us checking
 * the status of a report for the rest of time.
 */
define( 'MWS_MAX_AWAITING_REPORT_TIME', 86400 );

/**
 * "process_type" column value in database table "amazon_processes".
 *
 * Possibly redundant. In the future, we may use other types. For now,
 * inventory is the only type we use.
 */
define( 'MWS_INVENTORY_TYPE_INVENTORY', 'inventory' );

/**
 * After we ask amazon for all product data, this will be the process_status.
 */
define( 'MWS_INVENTORY_STATUS_AWAITING_REPORT', 'awaiting_report' );

/**
 * An error with submitting the request report data or with retrieving it.
 */
define( 'MWS_INVENTORY_STATUS_REQUEST_REPORT_ERROR', 'request_report-error' );

/**
 * Means we submitted inventory levels to amazon.
 *
 * Probably means that all products on amazon recieved our inventory level.
 */
define( 'MWS_INVENTORY_STATUS_FEED_SUBMITTED', 'feed_submitted' );

/**
 * An error occurred trying to submit the inventory feed to amazon.
 */
define( 'MWS_INVENTORY_STATUS_FEED_SUBMITTED_ERROR', 'feed_submitted-error' );

/**
 * Feed submitted and we successfully checked on the status of the feed
 * at a later time, and amazon returned something (which may indicate some errors).
 */
define( 'MWS_INVENTORY_STATUS_FEED_COMPLETE', 'feed_complete' );

/**
 * This gets sent to amazon to tell them we want all product data.
 */
define( 'MWS_REPORT_GET_MERCHANT_LISTINGS_ALL_DATA', '_GET_MERCHANT_LISTINGS_ALL_DATA_' );


/**
 * @param $mws_locale
 *
 * @return bool
 */
function mws_is_locale_valid( $mws_locale ) {
	return $mws_locale === MWS_LOCALE_CA || $mws_locale === MWS_LOCALE_US;
}

/**
 * @param $mws_locale
 */
function assert_mws_locale_valid( $mws_locale ) {
	if ( ! mws_is_locale_valid( $mws_locale ) ) {
		$_mws_locale = gp_test_input( $mws_locale );
		throw_dev_error( "MWS locale not valid ($_mws_locale)" );
		exit;
	}
	return true;
}

/**
 * @param $locale
 */
function mws_get_marketplace_id_from_locale( $mws_locale ){
	switch( $mws_locale ) {
		case MWS_LOCALE_CA:
			return MWS_MARKETPLACE_ID_CANADA;
		case MWS_LOCALE_US:
			return MWS_MARKETPLACE_ID_US;
		default:
			throw_dev_error( "Invalid mws locale ($mws_locale)" );
	}
}

/**
 * @param $mws_locale
 */
function mws_get_service_url_from_mws_locale( $mws_locale ) {

	assert_mws_locale_valid( $mws_locale );

	switch( $mws_locale ) {
		case MWS_LOCALE_CA:
			return MWS_SERVICE_URL_CA;
		case MWS_LOCALE_US:
			return MWS_SERVICE_URL_US;
		default:
			// can't happen unless our assertion above somehow fails
			throw_dev_error( "Invalid mws locale ($mws_locale)" );
	}
}

/**
 * @param $mws_locale
 *
 * @return string
 */
function mws_get_store_name_from_mws_locale( $mws_locale ) {

	assert_mws_locale_valid( $mws_locale );

	switch( $mws_locale ) {
		case MWS_LOCALE_CA:
			return MWS_STORE_NAME_CA;
		case MWS_LOCALE_US:
			return MWS_STORE_NAME_US;
		default:
			// can't happen unless our assertion above somehow fails
			throw_dev_error( "Invalid mws locale ($mws_locale)" );
	}
}

/**
 * Messy way to generate XML...
 *
 * Due to amazons disguisting lack of proper documentation, I had to find
 * the format of this XML here:
 *
 * https://github.com/matthiaskomarek/Amazon-xml-feeds/blob/master/feeds/_POST_INVENTORY_AVAILABILITY_DATA_.xml
 *
 * Even in MWS client API they had only one example of a SubmitFeed operation, which
 * was not of the correct feed type.
 *
 * @param $arr - an array indexed with SKU indexes and quantity values
 *
 * @return string
 */
function mws_inventory_array_to_feed_xml( $arr, $seller_id = MWS_SELLER_ID ){
	ob_start();
	?>
	<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">
		<Header>
			<DocumentVersion>1.01</DocumentVersion>
			<MerchantIdentifier><?php echo $seller_id; ?></MerchantIdentifier>
		</Header>
		<MessageType>Inventory</MessageType>
		<?php
		$count = 0;
		foreach ( $arr as $sku => $quantity ) {
			$count++
			?>
			<Message>
				<MessageID><?php echo $count; ?></MessageID>
				<OperationType>Update</OperationType>
				<Inventory>
					<SKU><?php echo $sku; ?></SKU>
					<Quantity><?php echo (int) $quantity; ?></Quantity>
				</Inventory>
			</Message>
		<?php } ?>
	</AmazonEnvelope>
	<?php
	return ob_get_clean();
}

/**
 * This modifies LIVE products on seller central based on amounts
 * in $product_array.
 *
 * NOTE: this uses a *SECOND* MWS library: https://github.com/CPIGroup/phpAmazonMWS
 *
 * We have another library that can do the same thing (but there seems to be errors).
 *
 * If we remove the composer dependency (or on a live site if the composer has not been updated,
 * then this might not work).
 *
 * P.s. i've tested this, and it does in fact work, but with the same result as
 * the first library (updates all products regardless of specified marketplace ID).
 *
 * There is no Dev/Production checks used below. When you call the function,
 * it submits the data.
 *
 * @param $product_array
 * @param $mws_locale
 *
 * @return array|bool
 */
function mws_submit_inventory( $product_array, $mws_locale ) {

	assert_mws_locale_valid( $mws_locale );
	$store = mws_get_store_name_from_mws_locale( $mws_locale );

	try {

		// store name determines which marketplace we're updating products in
		$amz = new AmazonFeed( $store , false, null, MWS_PHP_AMAZON_MWS_CONFIG_PATH );

		$amz->setFeedType( "_POST_INVENTORY_AVAILABILITY_DATA_" );

		$amz->setFeedContent( mws_inventory_array_to_feed_xml( $product_array ) );

		// the config that we setup for our store name contains a marketplace ID name,
		// so then is this step necessary ?
		$amz->setMarketplaceIds( mws_get_marketplace_id_from_locale( $mws_locale ) );

		$amz->submitFeed(); //this is what actually sends the request

		return $amz->getResponse();

	} catch ( Exception $e ) {
		$msg =  'MWS library error: ' . $e->getMessage();
		log_data( $msg, "amazon-feed-error" );
		if ( ! IN_PRODUCTION ) {
			die( $msg );
		}
	}

	return false;
}
