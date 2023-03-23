<?php

use CraigPaul\Moneris\Moneris;

/**
 * Probably all requests will extend this one.
 * Will have an App_Moneris_Config object, and a number of helper methods
 * for common operations, including some properties that are likely to be used
 * for all request types, including credit card info etc.
 *
 * Class App_Moneris
 */
Class App_Moneris{

	/** @var App_Moneris_Config  */
	public $config;

	/**
	 * @var \CraigPaul\Moneris\Gateway
	 */
	public $gateway;

	/**
	 * APP_LOCALE_CANADA|APP_LOCALE_US
	 *
	 * @var string
	 */
	public $locale;

	protected $amount;
	protected $card_number;
	protected $cvv;
	protected $card_month;
	protected $card_year;
	protected $avs_street_number;
	protected $avs_street_name;
	protected $avs_zipcode;

	/**
	 * probably user ID except accounts created upon checkout might
	 * not have a user ID at the time of sending the transaction.
	 *
	 * @var
	 */
	protected $cust_id;

	/**
	 * ideally the primary key of orders table. not sure if order will exist before transaction however.
	 *
	 * @var
	 */
	protected $order_id;

	/**
	 * address verification
	 *
	 * @var bool
	 */
	const DOING_AVS = true;

	/**
	 * card verification (cvv/cvd)
	 *
	 * @var bool
	 */
	const DOING_CVD = true;

	/**
	 * App_Moneris constructor.
	 */
	public function __construct( $locale ){
		$this->locale = $locale;
		$this->config = new App_Moneris_Config( $this->locale, MONERIS_TEST_MODE );
		$this->gateway = CraigPaul_Moneris_CA_US_Moneris::create($this->config->store_id, $this->config->api_token, $this->get_gateway_params() );
	}

	/**
	 * need these to create a gateway object
	 *
	 * @return array
	 */
	public function get_gateway_params(){
		$ret = array(
			'environment' => $this->config->environment,
			'avs' => self::DOING_AVS, // default: false
			'cvd' => self::DOING_CVD, // default: false
		);
		return $ret;
	}

//	/**
//	 *
//	 */
//	public function get_mpgHttpPost( $mpgRequest ){
//		$mpgHttpPost = new mpgHttpsPost( $this->config->store_id, $this->config->api_token, $mpgRequest );
//		return $mpgHttpPost;
//	}
//
//	/**
//	 * @param $mpgTxn
//	 */
//	public function get_mpgRequest( $mpgTxn ) {
//
//		$mpgRequest = new mpgRequest( $mpgTxn );
//
//		if ( app_get_locale() === 'US' ) {
//			$mpgRequest->setProcCountryCode( "US" ); //"US" for sending transaction to US environment
//		} else {
//			$mpgRequest->setProcCountryCode( "CA" );
//		}
//
//		if ( MONERIS_TEST_MODE ) {
//			$mpgRequest->setTestMode( true );
//		} else {
//			$mpgRequest->setTestMode( false );
//		}
//
//		return $mpgRequest;
//	}

	/**
	 * best not to use 411111... , some testing methods wont work.
	 *
	 * @return int
	 */
	public function get_testing_card_number(){
		return '4242424242424242';
	}

	/**
	 *
	 */
	public function set_card_number( $v ){
		$this->card_number = $v;
	}

	/**
	 *
	 */
	public function set_cvv( $v ){
		$this->cvv = $v;
	}

	/**
	 * @param $v
	 */
	public function set_cust_id( $v ) {
		$this->cust_id = $v;
	}

	/**
	 *
	 */
	public function set_card_year( $v ){
		$this->card_year = get_string_end( $v, 2 );
	}

	/**
	 *
	 */
	public function set_card_month( $v ){
		$this->card_month = gp_add_starting_zero( $v );
	}

	/**
	 * probably only for testing purposes
	 */
	public function set_random_order_id(){
		$v = 'ord-' . uniqid() . '-' . time();
		$this->set_order_id( $v );
	}

	/**
	 * @param $v
	 */
	public function set_order_id( $v ){
		$this->order_id = $v;
	}

	/**
	 * @param $v
	 */
	public function set_amount( $v ){
		$this->amount = format_in_dollars( $v );
	}

	/**
	 * In Dollars.
	 *
	 * @return mixed
	 */
	public function get_amount(){
		$ret = format_in_dollars( $this->amount );
		return $ret;
	}

	/**
	 * @return mixed
	 */
	public function get_cust_id(){
		return $this->cust_id;
	}

	/**
	 *
	 */
	public function get_card_number(){
		return $this->card_number;
	}

	/**
	 * @return mixed
	 */
	public function get_cvv(){
		return $this->cvv;
	}

	/**
	 * @return mixed
	 */
	public function get_card_year(){
		return get_string_end( $this->card_year, 2 );
	}

	/**
	 * @return mixed
	 */
	public function get_card_month(){
		return gp_add_starting_zero( $this->card_month );
	}

	/**
	 * YYMM, not MMYY
	 *
	 * @return string
	 */
	public function get_exp_date(){

		$year = $this->get_card_year();

		// do this when setting the variables i guess
//		if ( strlen( $year > 2 ) ) {
//			$year = get_string_end( $year, 2 );
//		}

		$month = $this->get_card_month();

		// $month = gp_add_starting_zero( $month );

		$ret = $year . $month;

		// if its not 4, it will (should) fail
		if ( strlen( $ret ) !== 4 ) {
			log_data( [ 'ret' => $ret, 'y' => $this->get_card_year(), 'm' => $this->get_card_month() ], 'app-moneris-invalid-exp-date' );
		}

		// return it anyways, because what else can we do..
		return $ret;
	}

	/**
	 *
	 */
	public function get_order_id(){
		return $this->order_id;
	}

	/**
	 * @return mixed
	 */
	public function get_avs_street_number(){
		return $this->avs_street_number;
	}

	/**
	 * @param $v
	 */
	public function set_avs_street_number( $v ) {
		$this->avs_street_number = $v;
	}

	/**
	 * @return mixed
	 */
	public function get_avs_street_name(){
		return $this->avs_street_name;
	}

	/**
	 * @param $v
	 */
	public function set_avs_street_name( $v ) {
		$this->avs_street_name = $v;
	}

	/**
	 * @return mixed
	 */
	public function get_avs_zipcode(){
		return $this->avs_zipcode;
	}

	/**
	 * @param $v
	 */
	public function set_avs_zipcode( $v ) {
		$this->avs_zipcode = $v;
	}
}