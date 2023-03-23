<?php
/**
 * A config file for a MWS library... when instantiating an AmazonCore subclass,
 * this file is included to define config vars.
 */

/**
 * If we have no use for this library I may remove it from composer dependencies.
 *
 * So... check composer.json file for:
 *
 * "cpigroup/php-amazon-mws": "^1.4"
 *
 * Our other (first) lib that does the same thing is: "mcs/amazon-mws": "^0.1.26"
 *
 * cpigroup requires a higher version of spatieXML or w/e, which requires
 * PHP 7+ and may cause issues with the first lib which also has the same dependency.
 *
 */

$make_config_array_via_mws_locale = function ( $mws_locale ) {
	$ret = [
		'merchantId' => MWS_SELLER_ID,
		'marketplaceId' => mws_get_marketplace_id_from_locale( $mws_locale ),
		'keyId' => MWS_ACCESS_KEY_ID,
		'secretKey' => MWS_SECRET_ACCESSS_KEY,
		// according to amazon, this doesn't matter (marketplace ID is very important though)
		'serviceUrl' => mws_get_service_url_from_mws_locale( $mws_locale ),
		// possibly empty, see comment in constant definition
		'MWSAuthToken' => MWS_AUTH_TOKEN,
	];

	return $ret;
};

// is this helping at all? just trying to be 1000% sure we don't mix up a CA and a US somewhere
$store[ mws_get_store_name_from_mws_locale( MWS_LOCALE_CA ) ] = $make_config_array_via_mws_locale( MWS_LOCALE_CA );
$store[ mws_get_store_name_from_mws_locale( MWS_LOCALE_US ) ] = $make_config_array_via_mws_locale( MWS_LOCALE_US );

// according to docs of the lib we're using, $store['serviceUrl'] overrides this
//  So... // does that mean we can comment it out?
// $AMAZON_SERVICE_URL = 'https://mws.amazonservices.com/';

//Location of log file to use - the sub folder should already exist
// due to other logs. If it doesn't, will the 3rd party library create it?
// if so, what permissions? Hopefully will be fine...
$logpath = LOG_DIR . '/mws/phpAmazonMWS-log.txt';

//Name of custom log function to use (we shouldn't need this hopefully)
$logfunction = '';

//Turn off normal logging - definitely false
$muteLog = false;