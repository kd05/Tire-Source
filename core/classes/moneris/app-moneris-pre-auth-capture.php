<?php

/**
 * Notes on AVS codes
 *
 * I'm getting an AVS response code of U back from a transaction with Visa
 *
 * See here: https://developer.moneris.com/More/Testing/AVS%20Result%20Codes
 *
 * U means not necessarily declined but not approved.
 *
 * The transaction still seems to go through however ??
 *
 * Therefore, we may turn off AVS checking al together, or might have to
 * put some logic in place to allow AVS code of U. For Visa, it may mean
 * that Visa itself checked the AVS result, but Moneris did not.
 *
 * The library we're using (CraigPaul\Moneris\Moneris) does not recognize
 * the AVS code of U, and therefore returns a default failed status when U is returned.
 */

/**
 * Class App_Moneris_Pre_Auth_Capture
 */
Class App_Moneris_Pre_Auth_Capture extends App_Moneris {

	/**
	 * @var \CraigPaul\Moneris\Response
	 */
	public $verify;

	/**
	 * @var \CraigPaul\Moneris\Response
	 */
	public $preauth;

	/**
	 * @var \CraigPaul\Moneris\Response
	 */
	public $capture;

	/**
	 * @var bool
	 */
	public $preauth_cancelled;

	/**
	 * App_Moneris_Pre_Auth constructor.
	 */
	public function __construct( $locale ) {
		parent::__construct( $locale );
		$this->preauth_cancelled = false;
	}

    /**
     * Pass in $this->preauth or $this->capture (response instances)
     *
     * Mostly for debugging.
     *
     * @param $response
     * @return array
     */
	public static function get_response_instance_summary( \CraigPaul\Moneris\Response $response){
	    return [
            "status" => $response->status,
            "successful" => $response->successful,
	        "errors" => $response->errors,
            "failedCvd" => $response->failedCvd,
            "failedAvs" => $response->failedAvs,
            "Receipt" => $response->receipt(),
        ];
    }

	/**
	 * after construct and setter methods, before send()
	 */
	public function get_params() {

		$params = [
			'order_id' => $this->get_order_id(),
            'amount' => $this->get_amount(),
            'credit_card' => $this->get_card_number(),
            'expiry_month' => $this->get_card_month(),
            'expiry_year' => $this->get_card_year(),
            'cvd' => $this->get_cvv(),
            'avs_street_number' => $this->get_avs_street_number(),
            'avs_street_name' => $this->get_avs_street_name(),
            'avs_zipcode' => $this->get_avs_zipcode(),
            'expdate' => $this->get_exp_date(),
		];

		return $params;
	}

	/**
	 * @return bool
	 */
	public function verify_success() {
		return $this->verify && $this->verify->successful && ! $this->verify->failedCvd && ! $this->verify->failedAvs;
	}

	/**
	 * See "note on AVS codes"
	 *
	 * @return bool
	 */
	public function preauth_success() {

        if ( ! $this->preauth ) {
            return false;
        }

        if ( $this->preauth->failedAvs ) {
            return false;
        }

        if ( $this->preauth->failedCvd ) {
            return false;
        }

        return (bool) $this->preauth->successful;
	}

    /**
     * @return bool
     */
	public function capture_success() {

	    // Going to not check cvd/avs failed here, because we already check in the preauth,
        // and we don't capture without first doing a preauth.
	    return $this->capture && $this->capture->successful;
	}

	/**
	 * cvv and avs. I think this step may be optional. We can use it to know in advance whether
	 * or not preauth() and capture() will fail due to avs/cvv, without actually running preauth().
	 * I know that with failed preauth() or capture() we must void. I don't know if we need to do that
	 * here or not. In our checkout system, we may store orders and transactions before even running verify,
	 * which in turn makes it redundant to run verify. In other words, we'll store failed transactions due to
	 * avs/cvv. So, this might not be in use.
	 */
	//	public function verify(){
	//		$params = $this->get_params();
	//		$params['order_id'] = 'V_' . gp_if_set( $params, 'order_id' );
	//		$this->verify = $this->gateway->verify( $params );
	//	}

	/**
	 * ie. cancel a previously pre authorized amount.
	 */
	public function capture_preauth_with_amt_zero() {

		if ( $this->preauth_cancelled ) {
			return false;
		}

		$this->preauth_cancelled = true;

		// override amt here with 0.00
		$this->capture = $this->gateway->capture( $this->preauth->transaction, null, '0.00' );

		return (bool) $this->capture_success();
	}

	/**
	 * Note: make sure $this->build_params() was run.
	 *
	 * preauthorize an amount.
	 *
	 * If you pre authorize the amount, and do not follow up with capturing that amount, make
	 * sure you use a capture_preauth_with_amt_zero(), otherwise funds will be locked in the users account.
	 *
	 * I'm assuming that if the preauth fails, there is no need to capture with amount zero.
	 *
	 * After running this, check $this->preauth_success()
	 */
	public function preauth() {
		$this->preauth = $this->gateway->preauth( $this->get_params() );
	}

	/**
	 * Before capturing, you should:
	 * run $this->build_params(),
	 * run $this->preauth(),
	 * check that $this->preauth_success() returns true,
	 *
	 * After running:
	 * check $this->capture_success()
	 */
	public function capture() {
		$this->capture = $this->gateway->capture( $this->preauth->transaction );
	}
}