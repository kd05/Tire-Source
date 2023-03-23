<?php

/**
 * Class App_Moneris_Config
 */
Class App_Moneris_Config {

	public $store_id;
	public $api_token;

	// 7 for "SSL-enabled merchant"
	public $crypt_type;

	/**
	 * @var
	 */
	public $locale;

	const CA_TEST_STORE_ID = "store5";
	const CA_TEST_API_TOKEN = "yesguy";

    const CA_LIVE_STORE_ID = "##removed";
    const CA_LIVE_API_TOKEN = "##removed";

	const US_TEST_STORE_ID = "store5";
	const US_TEST_API_TOKEN = "yesguy";

    const US_LIVE_STORE_ID = "##removed";
    const US_LIVE_API_TOKEN = "##removed";

	/**
	 * @see CraigPaul_Moneris_CA_US_Moneris
	 *
	 * @var string
	 */
	public $environment;

    /**
     * App_Moneris_Config constructor.
     * @param $locale
     * @param $test_mode
     */
    public function __construct( $locale, $test_mode ) {

        $this->locale = $locale;

        $this->environment = self::get_environment( $locale, $test_mode );
        $this->store_id = self::get_store_id( $locale, $test_mode );
        $this->api_token = self::get_api_token( $locale, $test_mode );
    }

    /**
     * @param $locale
     * @param $test_mode
     * @return string
     */
	public static function get_api_token( $locale, $test_mode ) {
        if ( $locale === APP_LOCALE_CANADA ) {
            return $test_mode ? self::CA_TEST_API_TOKEN : self::CA_LIVE_API_TOKEN;
        } else if ( $locale === APP_LOCALE_US ) {
            return $test_mode ? self::US_TEST_API_TOKEN : self::US_LIVE_API_TOKEN;
        } else {
            throw_dev_error("Invalid locale in get_api_token");
        }
    }

    /**
     * @param $locale
     * @param $test_mode
     * @return string
     */
	public static function get_store_id( $locale, $test_mode ) {

	    if ( $locale === APP_LOCALE_CANADA ) {
	        return $test_mode ? self::CA_TEST_STORE_ID : self::CA_LIVE_STORE_ID;
        } else if ( $locale === APP_LOCALE_US ) {
	        return $test_mode ? self::US_TEST_STORE_ID : self::US_LIVE_STORE_ID;
        } else {
	        throw_dev_error("Invalid locale in get_store_id");
        }
    }

    /**
     * @param $locale
     * @param $test_mode
     * @return string
     */
    public static function get_environment( $locale, $test_mode ) {

        if ( $locale === APP_LOCALE_CANADA ) {

            if ( $test_mode ) {
                return CraigPaul_Moneris_CA_US_Moneris::ENV_CA_TEST;
            } else {
                return CraigPaul_Moneris_CA_US_Moneris::ENV_CA_LIVE;
            }

        } else if ( $locale === APP_LOCALE_US ) {

            if ( $test_mode ) {
                return CraigPaul_Moneris_CA_US_Moneris::ENV_US_TEST;
            } else {
                return CraigPaul_Moneris_CA_US_Moneris::ENV_US_LIVE;
            }

        } else {
            throw_dev_error("Invalid locale in get_environment");
        }
    }
}

