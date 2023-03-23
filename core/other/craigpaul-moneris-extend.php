<?php
/**
 * These classes extends composer library: craigpaul/moneris-api. The
 * library did not support CA/US payments, but this one does (should?).
 *
 * Definitely not recommended that you update the craigpaul library.
 */

// Composer.json file at the time that this was stable.
//{
//	"name": "geekpower/tiresource",
//    "description": "Custom System",
//    "type": "project",
//    "require": {
//	"curl/curl": "^1.8",
//        "phpmailer/phpmailer": "^6.0",
//        "craigpaul/moneris-api": "^0.6.3",
//        "thunderer/shortcode": "^0.6.5",
//        "phpseclib/phpseclib": "~2.0",
//        "mcs/amazon-mws": "^0.1.26"
//    }
//}

/**
 * Extend the processor class to support US/CA transactions
 *
 * Class CraigPaul_Moneris_CA_US_Processor
 */
Class CraigPaul_Moneris_CA_US_Processor extends \CraigPaul\Moneris\Processor {

	/**
	 * API configuration.
     *
     * A partial config array which get completed via $this->config()
	 *
	 * @var array
	 */
	protected $config = [
		'protocol' => 'https',
		'host' => '',
		'port' => '443',
		'url' => '',
		'api_version' => 'PHP - 2.5.6',
		'timeout' => 60,
	];

	/**
	 * Retrieve the API configuration.
	 *
	 * @param string $environment
	 *
	 * @return array
	 */
	public function config( $environment = '' ) {

        // Made changes here June 2020, @see core/READMEs/moneris-june-2020

		switch ( $environment ) {
            case CraigPaul_Moneris_CA_US_Moneris::ENV_US_LIVE:
			case CraigPaul_Moneris_CA_US_Moneris::ENV_CA_LIVE:
				$this->config[ 'host' ] = 'www3.moneris.com';
				$this->config[ 'url' ]  = '/gateway2/servlet/MpgRequest';
				break;
            case CraigPaul_Moneris_CA_US_Moneris::ENV_US_TEST:
			case CraigPaul_Moneris_CA_US_Moneris::ENV_CA_TEST:
				$this->config[ 'host' ] = 'esqa.moneris.com';
				$this->config[ 'url' ]  = '/gateway2/servlet/MpgRequest';
				break;
		}

		// old ENV_US_LIVE
        // $this->config[ 'host' ] = 'esplus.moneris.com';
        // $this->config[ 'url' ]  = '/gateway_us/servlet/MpgRequest';

		// old ENV_US_TEST:
        // $this->config[ 'host' ] = 'esplusqa.moneris.com';
        // $this->config[ 'url' ]  = '/gateway_us/servlet/MpgRequest';

		return $this->config;
	}
}

/**
 * Class CraigPaul_Moneris_CA_US_Transaction
 */
Class CraigPaul_Moneris_CA_US_Transaction extends \CraigPaul\Moneris\Transaction {

	/**
	 * Extend this method to possibly change 'preauth' to 'us_preauth' etc.
     *
     * We no longer need to do this. See core/READMEs/moneris-june-2020
	 *
	 * @return bool|string
	 */
//	public function toXml() {
//
//		if ( isset( $this->params['type'] ) ) {
//			$this->params['type'] = CraigPaul_Moneris_CA_US_Gateway::prepend_txn_type_for_possible_us_txn( $this->gateway->environment, $this->params['type'] );
//		}
//
//		return parent::toXml();
//	}
}

/**
 * Class CraigPaul_Moneris_CA_US_Gateway
 */
Class CraigPaul_Moneris_CA_US_Gateway extends \CraigPaul\Moneris\Gateway {

	/**
	 * Process a transaction through the Moneris API.
	 *
     * @param \CraigPaul\Moneris\Transaction $transaction
     * @return \CraigPaul\Moneris\Response
     */
	protected function process( \CraigPaul\Moneris\Transaction $transaction ) {
		$processor = new CraigPaul_Moneris_CA_US_Processor( new \GuzzleHttp\Client() );

		return $processor->process( $transaction );
	}

	/**
     * DEPRECATED. @see core/READMEs/moneris-june-2020
     *
	 * Somewhere before sending the request, you'll want to filter $params['type'] through
	 * this function, which may leave type unmodified, or may prepend "us_".
	 *
	 * Doing this too early will cause the CraigPaul library to throw validation errors
	 * for unsupported transaction types. Doing so too late may mean that XML is already
	 * assembled from $params so this does nothing. Hopefully doing it at the correct time
	 * will work...
	 *
     * @param $environment
     * @param $txnType
     * @deprecated
     * @return string
     */
//	public static function prepend_txn_type_for_possible_us_txn( $environment, $txnType ){
//
//		// U.S. env uses different 'types' for certain operations
//		switch ( $environment ) {
//			case CraigPaul_Moneris_CA_US_Moneris::ENV_US_LIVE:
//			case CraigPaul_Moneris_CA_US_Moneris::ENV_US_TEST:
//
//				// taking this logic right out of the moneris php client library.
//				// appends us_ to certain transaction types
//				if ( ( strpos( $txnType, "us_" ) !== 0 ) ) {
//					if ( ( strcmp( $txnType, "txn" ) === 0 ) || ( strcmp( $txnType, "acs" ) === 0 ) || ( strcmp( $txnType, "group" ) === 0 ) ) {
//						//do nothing
//					} else {
//						$txnType = "us_$txnType";
//					}
//				}
//				break;
//		}
//
//		return $txnType;
//	}

	/**
	 * Get or create a new Transaction instance.
	 *
	 * Override superclass method to inject our own Transaction instance
	 * that is aware of CA/US...
	 *
	 * @param array|null $params
	 *
	 * @return \CraigPaul\Moneris\Transaction
	 */
	protected function transaction( array $params = null ) {

		if ( is_null( $this->transaction ) || ! is_null( $params ) ) {
			return $this->transaction = new CraigPaul_Moneris_CA_US_Transaction( $this, $params );
		}

		return $this->transaction;
	}

}

/**
 * Class CraigPaul_Moneris_CA_US_Moneris
 */
Class CraigPaul_Moneris_CA_US_Moneris extends \CraigPaul\Moneris\Moneris {

	const ENV_CA_LIVE = 'live_ca';
	const ENV_CA_TEST = 'test_ca';
	const ENV_US_LIVE = 'live_us';
	const ENV_US_TEST = 'test_us';

	/**
	 * Create a new Moneris instance.
	 *
	 * @param string $id
	 * @param string $token
	 * @param array  $params
	 *
	 * @return void
	 */
	public function __construct( $id = '', $token = '', array $params = [] ) {
		parent::__construct( $id, $token, $params );

		assert( in_array( $this->environment, [
			static::ENV_CA_LIVE,
			static::ENV_CA_TEST,
			static::ENV_US_LIVE,
			static::ENV_US_TEST,
		] ) );
	}

	/**
	 * Create and return a new Gateway instance.
	 *
	 * @return \CraigPaul\Moneris\Gateway
	 */
	public function connect() {
		$gateway = new CraigPaul_Moneris_CA_US_Gateway( $this->id, $this->token, $this->environment );

		if ( isset( $this->params[ 'avs' ] ) ) {
			$gateway->avs = boolval( $this->params[ 'avs' ] );
		}

		if ( isset( $this->params[ 'cvd' ] ) ) {
			$gateway->cvd = boolval( $this->params[ 'cvd' ] );
		}

		return $gateway;
	}
}
