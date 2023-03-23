<?php

/**
 * Amazon Marketplace Web Services.
 *
 * Initially, the goal of this class is to upload product inventory data
 * from the website to MWS. In the future, we may also sync other product data.
 *
 * @see https://developer.amazonservices.com/index.html/
 *
 * Class Amazon_MWS
 */
Class Amazon_MWS{

	/**
	 * The "client" is "us" in a sense, but this property is the
	 * main object in a 3rd party library for interfacing with MWS.
	 *
	 * @see https://packagist.org/packages/mcs/amazon-mws
	 *
	 * @var MCS\MWSClient
	 */
	public $client;

	/**
	 * Amazon_MWS constructor.
	 */
	public function __construct( $mws_locale ){

		assert_mws_locale_valid( $mws_locale );

		$this->client = new MCS\MWSClient( array(
			'Marketplace_Id' => mws_get_marketplace_id_from_locale( $mws_locale ),
			'Seller_Id' => MWS_SELLER_ID,
			'Access_Key_ID' => MWS_ACCESS_KEY_ID,
			'Secret_Access_Key' => MWS_SECRET_ACCESSS_KEY,
			'MWSAuthToken' => MWS_AUTH_TOKEN
		));
	}

	/**
	 * Kind of like singleton except we can have 1 instance per locale, for a total
	 * of 2 instances. Not sure what that's called in OO terminology.
	 *
	 * @param $locale - APP_LOCALE_CANADA or APP_LOCALE_US
	 *
	 * @return Amazon_MWS
	 */
	public static function get_instance( $locale ){

		global $amazon_mws_instances;
		$amazon_mws_instances = $amazon_mws_instances === null ? [] : $amazon_mws_instances;

		if ( isset( $amazon_mws_instances[$locale]) ) {
			return $amazon_mws_instances[$locale];
		}

		$amazon_mws_instances[$locale] = new self( $locale );
		return $amazon_mws_instances[$locale];
	}

    /**
     * @param $dirname
     * @param $filename
     * @param $data
     */
    public static function log_data( $dirname, $filename, $data ) {

        $dir = rtrim( LOG_DIR . '/mws/' . $dirname, '/' );

        if ( ! file_exists( $dir ) ) {
            mkdir( $dir, 0755, true );
        }

        if ( ! file_exists( $dir ) ) {
            throw_dev_error( 'could not make directory to log MWS stuff.' );
            exit;
        }

        $str = print_r( $data, true ) . PHP_EOL;
        @file_put_contents( $dir . '/' . $filename, $str, FILE_APPEND );
    }
}
