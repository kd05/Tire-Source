<?php

/**
 * this class handles the order submission form on checkout.
 *
 * Class Checkout_Submit
 */
Class Checkout_Submit {

    public $doing_production_test = false;

	protected $errors;
	protected $debug;
	protected $success;
	public $success_msg;

	/** @var  App_Moneris_Pre_Auth_Capture */
	public $payment;

	// raw request data, ie $_POST
	public $raw;

	public static $duplicate_txn_key = 'processed_transaction_amounts';

	/**
	 * Access all street, city, province, country, and postal fields through
	 * this object, in $this->cart->receipt->shipping_address->city for example.
	 *
	 * @var Cart_Receipt
	 */
	public $cart_receipt;

	public $update_receipt;
	public $receipt_html;
	public $items_html;
	public $confirm_total_html;

	public $inserted_user_id;

	public $first_name;
	public $last_name;

	public $confirm_total;

	public $company;
	public $phone;
	public $email;

	public $sh_first_name;
	public $sh_last_name;
	public $sh_company;

	public $register;
	public $password_1;
	public $password_2;
	public $ship_to;
	public $shipping_is_billing;

	public $sh_postal;
	public $card;
	public $cvv;
	public $card_month;
	public $card_year;
	public $accept_terms;

	/**
	 * user created upon checkout.
	 *
	 * @var  DB_User|null
	 */
	public $db_user;

	/**
	 * user already logged in
	 *
	 * @var  DB_User|null
	 */
	public $logged_in_user;

	/**
	 * can be true if registering on checkout
	 *
	 * @var
	 */
	public $user_inserted;

	/** @var  DB_Order|null */
	public $db_order;

	/** @var  DB_Transaction|null */
	public $db_transaction;

	/** @var  array DB_Order_Item[] */
	public $db_order_items;

	/** @var  array DB_Order_Vehicle[] */
	public $db_order_vehicles;

	public $user_email_sent;
	public $admin_email_sent;

	/**
	 * will store some email data here to re-use some email functions
	 * in 2 places, one for user confirmation email, the other for admin
	 *
	 * @var
	 */
	public $cached_email_data;

	/**
	 * CA or US
	 *
	 * @var string
	 */
	public $locale;

	/**
	 * An object to attach to facebook pixel code tracking
	 * for a "Purchse" event on a successful purchase. We return
	 * this to the JS and JS sends the results using fqb().
	 *
	 * We have to ensure we build this at the right time, before
	 * emptying the cart on a successful purchase, as the contents
	 * of the cart or needed to build this. Lots of mutability
	 * here unfortunately.
	 *
	 * @var
	 */
	public $fbq_purchase = [];

    /**
     * Google tag manager purchase tracking thing..
     *
     * When the order is successful, add some fields to this (value, currency, transaction_id).
     *
     * Set it up only if order is successful.
     *
     * @var array
     */
	public $gtag_fields = [];

	public $kount_inquiry_request = [];
    public $kount_inquiry_response = [];

    public $kount_update_request = [];
    public $kount_update_response = [];
    /**
     * @var array
     */
    private $map_names;
    /**
     * @var true
     */
    private $update_confirm_total;
    /**
     * @var string|null
     */
    private $heard_about;
    /**
     * @var false|int
     */
    private $mailing_list;
    /**
     * @var string|null
     */
    private $sh_phone;

    /**
	 * The construct serves 2 main purposes... to run the checkout, and to
	 * re-generate the html for the receipt. The receipt HTML is not done inside this
	 * class, but instead, we construct everything from user data and then use public
	 * properties to get the receipt html.
	 *
	 * Checkout_Submit constructor.
	 *
	 * @param $raw
	 */
	public function __construct( $raw, Cart $cart ) {

		// do this early
		$u                    = cw_get_logged_in_user();
		$this->logged_in_user = $u ? $u : null;

		$this->locale = app_get_locale();
		$this->raw    = $raw;

		$this->errors  = array();
		$this->debug   = array();
		$this->success = false;

		$this->inserted_user_id = false;
		$this->receipt_html     = '';

		// if this is true, javascript needs to put $this->confirm_total_html somewhere inside the form
		$this->update_confirm_total = false;
		$this->confirm_total_html   = '';

		// for generic validation, ie. "The First Name field is required"
		$this->map_names = array(
			'first_name' => 'First Name',
			'last_name' => 'Last Name',
			'company' => 'Company',
			'phone' => 'Phone',
			'email' => 'Email',
			'street_number' => 'Street Number',
			'street_name' => 'Street Name',
			'street_extra' => get_street_extra_text(),
			'city' => 'City',
			'province' => get_province_label( $this->locale ),
			'country' => 'Country',
			'postal' => get_postal_code_label( $this->locale ),
			'sh_first_name' => 'First Name (shipping)',
			'sh_last_name' => 'Last Name (shipping)',
			'sh_company' => 'Company (shipping)',
			'sh_street_number' => 'Street Number (shipping)',
			'sh_street_name' => 'Street Name (shipping)',
			'sh_street_extra' => get_street_extra_text() . ' (shipping)',
			'sh_city' => 'City (shipping)',
			'sh_province' => get_province_label( $this->locale ) . ' (shipping)',
			'sh_country' => 'Country (shipping)',
			'sh_postal' => get_postal_code_label( $this->locale ) . ' (shipping)',
			'password_1' => 'Password',
			'password_2' => 'Confirm Password',
			'ship_to' => 'Ship To', // wont make much sense if we print this to user
			'card' => 'Card Number',
			'cvv' => 'CVV',
			'card_month' => 'Expiry Month',
			'card_year' => 'Expiry Year',
			'accept_terms' => 'Terms and Conditions', // shouldn't print to user like this
			'heard_about' => '"How did you hear about us"',
		);

		$this->update_receipt = get_user_input_singular_value( $raw, 'update_receipt' );
		$this->update_receipt = ( $this->update_receipt );

		$this->confirm_total = get_user_input_singular_value( $raw, 'confirm_total' );
		$this->confirm_total = format_price_dollars( $this->confirm_total );

		$this->first_name = get_user_input_singular_value( $raw, 'first_name' );
		$this->last_name  = get_user_input_singular_value( $raw, 'last_name' );
		$this->company    = get_user_input_singular_value( $raw, 'company' );
		$this->phone      = get_user_input_singular_value( $raw, 'phone' );

		if ( $this->logged_in_user ) {
			$this->email = $this->logged_in_user->get( 'email' );
		} else {
			$this->email = get_user_input_singular_value( $raw, 'email' );
		}

		// we only have 1 column for this in the DB, therefore one of 2 fields end up there.
		$this->heard_about = get_user_input_singular_value( $raw, 'heard_about' );
		if ( $this->heard_about === 'other' ) {
			$this->heard_about = get_user_input_singular_value( $raw, 'heard_about_other' );
		}

		$this->mailing_list = (bool) gp_if_set( $raw, 'mailing_list' );
		$this->mailing_list = $this->mailing_list ? 1 : false;

		$this->register            = (bool) gp_if_set( $raw, 'register' );
		$this->password_1          = get_user_input_singular_value( $raw, 'password_1' );
		$this->password_2          = get_user_input_singular_value( $raw, 'password_2' );

		// We no longer have different shop to options, but to maintain backwards compatibility,
		// we will hardcode this value to be "address". If its not, people won't be able to checkout.
		// $this->ship_to             = get_user_input_singular_value( $raw, 'ship_to' );
		$this->ship_to = 'address';

		$this->shipping_is_billing = get_user_input_singular_value( $raw, 'shipping_is_billing' );

		$card       = get_user_input_singular_value( $raw, 'card' );
		$card       = clean_credit_card_number( $card );
		$card       = trim( $card );
		$this->card = $card;

		$cvv       = get_user_input_singular_value( $raw, 'cvv' );
		$this->cvv = strip_non_numeric( $cvv );

		$this->card_month   = get_user_input_singular_value( $raw, 'card_month' );
		$this->card_month   = gp_add_starting_zero( $this->card_month );
		$this->card_year    = get_user_input_singular_value( $raw, 'card_year' );
		$this->accept_terms = get_user_input_singular_value( $raw, 'accept_terms' );

		$street_number = get_user_input_singular_value( $raw, 'street_number' );
		$street_name   = get_user_input_singular_value( $raw, 'street_name' );
		$street_extra  = get_user_input_singular_value( $raw, 'street_extra' );

		$city     = get_user_input_singular_value( $raw, 'city' );
		$province = get_user_input_singular_value( $raw, 'province' );
		$country  = get_user_input_singular_value( $raw, 'country' );
		$postal   = get_user_input_singular_value( $raw, 'postal' );

		if ( $this->ship_to == 'address' ) {

			if ( $this->shipping_is_billing ) {

				// personal (stored in this object)
				$this->sh_first_name = $this->first_name;
				$this->sh_last_name  = $this->last_name;
				$this->sh_company    = $this->company;
				$this->sh_phone      = $this->phone;

				// address (stored in another object)
				$sh_street_number = $street_number;
				$sh_street_name   = $street_name;
				$sh_street_extra  = $street_extra;
				$sh_city          = $city;
				$sh_province      = $province;
				$sh_country       = $country;
				$sh_postal        = $postal;

			} else {

				// personal (stored in this object)
				$this->sh_first_name = get_user_input_singular_value( $raw, 'sh_first_name' );
				$this->sh_last_name  = get_user_input_singular_value( $raw, 'sh_last_name' );
				$this->sh_company    = get_user_input_singular_value( $raw, 'sh_company' );
				$this->sh_phone      = get_user_input_singular_value( $raw, 'sh_phone' );

				// address (stored in another object)
				$sh_street_number = get_user_input_singular_value( $raw, 'sh_street_number' );
				$sh_street_name   = get_user_input_singular_value( $raw, 'sh_street_name' );
				$sh_street_extra  = get_user_input_singular_value( $raw, 'sh_street_extra' );
				$sh_city          = get_user_input_singular_value( $raw, 'sh_city' );
				$sh_province      = get_user_input_singular_value( $raw, 'sh_province' );
				$sh_country       = get_user_input_singular_value( $raw, 'sh_country' );
				$sh_postal        = get_user_input_singular_value( $raw, 'sh_postal' );
			}

		} else {

			// personal (stored in this object)
			$this->sh_first_name = '';
			$this->sh_last_name  = '';
			$this->sh_company    = '';
			$this->sh_phone      = '';

			// address (stored in another object)
			$sh_street_number = '';
			$sh_street_name   = '';
			$sh_street_extra  = '';
			$sh_city          = '';
			$sh_province      = '';
			$sh_country       = '';
			$sh_postal        = '';

		}

		$shipping_address = new Shipping_Address( $sh_street_number, $sh_street_name, $sh_street_extra, $sh_city, $sh_province, $sh_country, $sh_postal );
		$billing_address  = new Billing_Address( $street_number, $street_name, $street_extra, $city, $province, $country, $postal );

		// $shipping_free      = $this->ship_to === 'pickup';
		$shipping_free = false;
		$this->cart_receipt = new Cart_Receipt( $cart, $billing_address, $shipping_address, $shipping_free );

		// ALWAYS JUST RETURN THIS... it makes it so that valid form submissions can be processed.
		$this->update_confirm_total = true;
		$pd                         = $this->cart_receipt->total_is_to_be_determined() ? 'TBD' : print_price_dollars_formatted( $this->get_price_total() );
		$this->confirm_total_html   = get_form_checkbox( array(
			'req' => true,
			'label' => 'I confirm the price total after shipping and tax calculations: ' . $pd . '.',
			'checked' => $this->confirmed_total_matches_price_total(),
			'name' => 'confirm_total',
			'value' => $this->get_price_total(),
		) );

        // setup before preauth, we use it also in kount inquiry
        $this->payment = new App_Moneris_Pre_Auth_Capture( $this->locale );
	}

	/**
	 * If this screws up due to weird rounding issues or doubles being formatted like 99.1, then
	 * we're going to have an issue.
	 */
	public function confirmed_total_matches_price_total() {

		$total     = $this->get_price_total();
		$confirmed = format_price_dollars( $this->confirm_total );

		$ret = $total === $confirmed;

		return $ret;
	}

	/**
	 * This 100% needs to return in a proper format like "99.99" or "100.00" or "115.10" or "0.00" or null
	 */
	public function get_price_total() {
		$ret = $this->cart_receipt->total_is_to_be_determined() ? null : format_price_dollars( $this->cart_receipt->total );

		return $ret;
	}

	/**
	 * Pass in html name attribute, get the label, or placeholder, or what we call it..
	 */
	public function get_validation_name( $name, $default = '' ) {
		return gp_if_set( $this->map_names, $name, $default );
	}

	/**
	 * Most user input values are class properties of this class, ie. $this->first_name, but, some of them are not...
	 * ie. $this->cart_receipt->shipping_address->street
	 *
     * @param $prop
     * @param string $df
     * @return bool|mixed|string
     */
	public function get( $prop, $df = '' ) {

		if ( isset( $this->{$prop} ) ) {
			return $this->{$prop};
		}

		$ret = $df;

		$shipping = [
			'sh_street_number' => 'street_number',
			'sh_street_name' => 'street_name',
			'sh_street_extra' => 'street_extra',
			'sh_city' => 'city',
			'sh_province' => 'province',
			'sh_country' => 'country',
			'sh_postal' => 'postal',
		];

		if ( in_array( $prop, array_keys( $shipping ) ) ) {
			$ret = gp_if_set( $this->cart_receipt->shipping_address, $shipping[$prop], $df );
		}

		$billing = [ 'street_number', 'street_name', 'street_extra', 'city', 'province', 'country', 'postal' ];

		if ( in_array( $prop, $billing ) ) {
			$ret = gp_if_set( $this->cart_receipt->billing_address, $prop, $df );
		}

		return $ret;
	}

	/**
	 * step 1 is verify_price_and_acceptance()
	 */
	public function validation_step_2() {

		$req = array();

		$req[] = 'first_name';
		$req[] = 'last_name';
		// $req[] = 'company';
		$req[] = 'phone';

		if ( ! $this->logged_in_user ) {
			$req[] = 'email';
		}

		$req[] = 'street_number';
		$req[] = 'street_name';
		$req[] = 'city';
		$req[] = 'province';
		$req[] = 'country';
		$req[] = 'postal';
		$req[] = 'card';
		$req[] = 'cvv';
		$req[] = 'card_month';
		$req[] = 'card_year';

		// Note: $this->ship_to is now hardcoded!
		if ( $this->ship_to === 'address' ) {

			if ( ! $this->shipping_is_billing ) {

				$req[] = 'sh_first_name';
				$req[] = 'sh_last_name';
				// $req[] = 'sh_company';
				$req[] = 'sh_phone';
				// $req[] = 'sh_email'; // not a field
				$req[] = 'sh_street_number';
				$req[] = 'sh_street_name';
				$req[] = 'sh_city';
				$req[] = 'sh_province';
				$req[] = 'sh_country';
				$req[] = 'sh_postal';
			}

		} else if ( $this->ship_to === 'pickup' ) {
			// cool
		} else {
			$this->add_error( 'Please select a valid shipping option.' );
		}

		// not sure if we're going to show this..
		//		if ( $this->logged_in_user ) {
		//			unset( $req['email'] );
		//		}

		foreach ( $req as $rr ) {
			$v = $this->get( $rr );
			if ( ! $v ) {

				if ( $rr === 'cvv' ) {
					$this->add_error( 'The CVV field is required. This is the 3 or 4 digit code found on the back of your credit card.' );
					continue;
				}

				$this->add_error( 'The ' . $this->get_validation_name( $rr ) . ' field is required.' );
			}
		}

		if ( ! $this->ship_to ) {
			$this->add_error( 'Please select a valid shipping option.' );
		}

		// return after required fields, then do more specific validation..
		//		if ( $this->has_errors() ) {
		//			return;
		//		}

		// validate card parameters
		if ( ! validate_credit_card_luhn_check( $this->card ) ) {
			$this->add_error( 'Your credit card number appears to be invalid.' );
		}

		// i'm not actually 100% sure what the limits are this..
		if ( strlen( $this->card ) > 19 || strlen( $this->card < 13 ) ) {
			$this->add_error( 'Your credit card number appears to be too long or too short.' );
		}

		$year_valid = true;
		if ( ! in_array( $this->card_year, array_keys( get_credit_card_year_options() ) ) ) {
			$this->add_error( 'The expiration year of your card is not a valid option.' );
			$year_valid = false;
		}

		$month_valid = true;
		if ( ! in_array( $this->card_month, array_keys( get_credit_card_month_options() ) ) ) {
			$this->add_error( 'The expiration month of your card is not a valid option.' );
			$month_valid = false;
		}

		if ( $year_valid && $month_valid ) {
			if ( $this->card_year < date( 'Y' ) ) {
				$this->add_error( '[1] Your credit card appears to be expired.' );
			} else if ( $this->card_year == date( 'Y' ) ) {

				if ( $this->card_month < date( 'm' ) ) {
					$this->add_error( '[2] Your credit card appears to be expired.' );
				}
			}
		}

		if ( strlen( $this->cvv ) !== 3 && strlen( $this->cvv ) !== 4 ) {
			$this->add_error( 'Please enter a valid CVV. This is the 3 or 4 digit code found on the back of your credit card.' );
		}
	}

	/**
	 *
	 */
	public function verify_price_and_acceptance() {

		if ( $this->cart_receipt->total_is_to_be_determined() ) {
			$this->add_error( 'Please fill in all billing and shipping information so that your price total can be determined.' );

			// return here because otherwise the "please confirm total" message makes no sense.
			return;
		}


		if ( ! $this->accept_terms ) {
			$this->add_error( 'Please check the box that says you agree to our return (and other) policies.' );
		}

		// we'll validate these after req fields.. to show more meaningful errors
		//		if ( ! $this->cart_receipt->shipping_prices->valid ) {
		//			$this->add_error( 'Missing required shipping information.' );
		//		}
		//		if ( ! $this->cart_receipt->tax_rate_by_address->valid ) {
		//			$this->add_error( 'Missing required billing information.' );
		//		}

		if ( ! $this->confirmed_total_matches_price_total() ) {

			// in the past, i've had rounding issues occur which prevent cart checkouts from happening
			// so I 100% want to know if the confirm total often ends up being very close to the actual total,
			// and also just in general to know how often this is happening.
			if ( $this->confirm_total && $this->cart_receipt->total ) {
				$log = get_string_for_log( $this->confirm_total );
				$log .= ' .. ' . get_string_for_log( $this->cart_receipt->total );
				log_data( $log, 'confirm-total-mismatch' );
			}

			$this->add_error( 'Please check the box to confirm the price total.' );
		}
	}

	/**
	 *
	 */
	public function run_update_items() {
		ob_start();
		// globals for template file
		set_global( 'page', 'checkout' );
		set_global( 'receipt', $this->cart_receipt );
		include TEMPLATES_DIR . '/cart/cart-summary.php';
		$this->items_html = ob_get_clean();
	}

	/**
	 *
	 */
	public function run_update_receipt() {
		$this->receipt_html = get_receipt_html( $this->cart_receipt, '', 'checkout' );
	}

	/**
	 * @param string $code
	 *
	 * @return string
	 */
	public function unexpected_error_msg( $code = '' ) {
		$ret = 'An unexpected error occurred';
		$ret .= $code ? ' [' . $code . ']' : '';

		return $ret;
	}

	/**
	 * @return bool|mixed
	 */
	public function get_billing_province_name() {
		$p   = $this->cart_receipt->billing_address->province;
		$c   = $this->cart_receipt->billing_address->country;
		$ret = get_province_name( $p, $c, $p );

		return $ret;
	}

	/**
	 * @return bool|mixed
	 */
	public function get_billing_country_name() {
		$c   = $this->cart_receipt->billing_address->country;
		$ret = get_country_name( $c, $c );

		return $ret;
	}

	/**
	 * @return bool|mixed
	 */
	public function get_shipping_province_name() {
		$p   = $this->cart_receipt->shipping_address->province;
		$c   = $this->cart_receipt->shipping_address->country;
		$ret = get_province_name( $p, $c, $p );

		return $ret;
	}

	/**
	 * @return bool|mixed
	 */
	public function get_shipping_country_name() {
		// fixed a bug here on apr 16 2019. was previously using the shipping address which
		// normally was the same as billing.
		// $c   = $this->cart_receipt->billing_address->country;
		$c   = $this->cart_receipt->shipping_address->country;
		$ret = get_country_name( $c, $c );

		return $ret;
	}

	//	/**
	//	 * if a user isn't logged in then of course this doesn't work
	//	 * until after a user is inserted, if they want to register.
	//	 */
	//	public function get_user_id(){
	//
	//		if ( $this->logged_in_user ) {
	//			return $this->logged_in_user->get( 'user_id' );
	//		} else if ( $this->user_inserted && $this->db_user ) {
	//			return $this->db_user->get( 'user_id' );
	//		}
	//
	//		return false;
	//	}

	/**
	 *
	 */
	public function insert_order() {

		$db = get_database_instance();

		$cart = $this->cart_receipt->cart;

		$transaction_id = $db->insert( $db->transactions, [
			'subtotal' => $this->format_in_dollars( $this->cart_receipt->subtotal ),
			'shipping' => $this->format_in_dollars( $this->cart_receipt->shipping ),
			'tax' => $this->format_in_dollars( $this->cart_receipt->tax ),
			'total' => $this->format_in_dollars( $this->cart_receipt->total ),
			'ontario_fee' => $this->format_in_dollars( $this->cart_receipt->ontario_fee ),
			'ontario_fee_qty' => (int) $this->cart_receipt->ontario_fee_qty,
			'currency' => app_get_locale() === APP_LOCALE_US ? 'USD' : 'CDN',
			'last_operation' => 'insert',
			'success' => '',
			'response_code' => '',
			'response_message' => '',
			'card_month' => $this->card_month,
			'card_year' => $this->card_year,
			'last_4' => get_credit_card_last_4( $this->card ),
			'cvd_result' => '',
			'avs_result' => '',
			'card_type' => '',
			'auth_code' => '',
			'trans_id' => '',
			'reference_number' => '',
            'kount_data' => '{}',
            'txn_extra' => '{}',
		], [
			'ontario_fee_qty' => '%d',
		] );

		if ( ! $transaction_id ) {
			$this->add_error( $this->unexpected_error_msg( 'trans_insert' ) );

			return;
		}

		$this->db_transaction = DB_Transaction::create_instance_via_primary_key( $transaction_id );

		$order_id = $db->insert( $db->orders, [
			'transaction_id' => (int) $transaction_id,
			// have to update this later if we insert a user
			'user_id' => $this->logged_in_user ? (int) $this->logged_in_user->get( 'user_id' ) : null,
			'order_date' => get_date_formatted_for_database(),
			// storing the entire cart as an array means we can load up a new Cart instance in
			// the exact same way its done in the $_SESSION and have access to all cart functions.
			// that being said, yes most of the data is also stored elsewhere. its undecided which
			// data will be used for which purposes, or if this is even needed.
			'cart' => gp_db_encode( $cart->to_array() ), // might not need this any more
			'order_status' => 'pending_payment',
			'locale' => app_get_locale(),
			'ship_to' => $this->ship_to,
			'shipping_is_billing' => (bool) $this->shipping_is_billing,
			'first_name' => $this->first_name,
			'last_name' => $this->last_name,
			'company' => $this->company,
			'phone' => $this->phone,
			'email' => $this->email,
			'heard_about' => $this->heard_about,
			'mailing_list' => $this->mailing_list,
			'street_number' => $this->cart_receipt->billing_address->street_number,
			'street_name' => $this->cart_receipt->billing_address->street_name,
			'street_extra' => $this->cart_receipt->billing_address->street_extra,
			'city' => $this->cart_receipt->billing_address->city,
			'province' => $this->get_billing_province_name(),
			'postal' => $this->cart_receipt->billing_address->postal,
			'country' => $this->get_billing_country_name(),
			'sh_first_name' => $this->sh_first_name,
			'sh_last_name' => $this->sh_last_name,
			'sh_company' => $this->sh_company,
			'sh_phone' => $this->sh_phone,
			// fixed errors here on april 16 2019 (was storing billing info for street number, name, and extra)
			'sh_street_number' => $this->cart_receipt->shipping_address->street_number,
			'sh_street_name' => $this->cart_receipt->shipping_address->street_name,
			'sh_street_extra' => $this->cart_receipt->shipping_address->street_extra,
			'sh_city' => $this->cart_receipt->shipping_address->city,
			'sh_province' => $this->get_shipping_province_name(),
			'sh_postal' => $this->cart_receipt->shipping_address->postal,
			'sh_country' => $this->get_shipping_country_name(),
		], [
			'user_id' => '%d',
			'transaction_id' => '%d',
		] );

		if ( ! $order_id ) {
			$this->add_error( $this->unexpected_error_msg( 'order_insert' ) );

			return;
		}

		// When instantiating the Order object, the ->user property is automatically setup,
		// however, if the user is registering upon checkout, the user_id will not have been
		// put into the database yet. Therefore, be cautious about accessing $this->db_order->user too early.
		$this->db_order = DB_Order::create_instance_via_primary_key( $order_id );

		$map_package_ids_to_order_vehicle_ids = array();

		if ( $cart->items ) {
			foreach ( $cart->items as $id => $data ) {
				$item = $cart->get_item( $id );

				$order_vehicle_id = '';

				// if item is in a package..
				if ( $item->package_id ) {

					// check existing
					$order_vehicle_id = gp_if_set( $map_package_ids_to_order_vehicle_ids, $item->package_id );

					if ( ! $order_vehicle_id ) {

						$package = $cart->get_package( $item->package_id );

						$order_vehicle_id = $db->insert( $db->order_vehicles, [
							'fitment_name' => $package->fitment->wheel_set->name,
							'vehicle_name' => $package->vehicle->get_display_name(),
							'make' => $package->vehicle->make,
							'model' => $package->vehicle->model,
							'year' => $package->vehicle->year,
							'trim' => $package->vehicle->trim,
							'fitment' => $package->fitment->wheel_set->slug,
							'sub' => $package->fitment->wheel_set->get_selected()->is_sub() ? $package->fitment->wheel_set->get_selected()->get_slug() : '',
							'sub_name' => $package->fitment->wheel_set->get_selected()->is_sub() ? $package->fitment->wheel_set->get_selected()->get_name( true ) : '',
							'bolt_pattern' => $package->fitment->bolt_pattern,
							'lock_type' => $package->fitment->lock_type,
							'lock_text' => $package->fitment->lock_text,
							'market_slug' => $package->fitment->market_slug,
							'center_bore' => $package->fitment->center_bore,
							'staggered' => $package->fitment->wheel_set->get_selected()->is_staggered() ? 1 : '',
							'oem' => $package->fitment->wheel_set->is_oem() ? 1 : '',
							'fitment_data' => gp_db_encode( $package->fitment->to_array() ),
						], [

						] );

						if ( ! $order_vehicle_id ) {
							$this->add_error( $this->unexpected_error_msg( 'order_vehicle' ) );

							return;
						}

						$this->db_order_vehicles[] = DB_Order_Vehicle::create_instance_via_primary_key( $order_vehicle_id );

						// save in an array for re-use when another item has the same package
						// note: sometimes 2 packages can have the same vehicle, but we still store 1 vehicle for each package.
						$map_package_ids_to_order_vehicle_ids[ $item->package_id ] = $order_vehicle_id;
					}

				}

				// sometimes we have empty install kits or mount/balance items
				if ( $item->get_quantity() > 0 ) {

					if ( $item->type === 'mount_balance' ) {
						$desc = $this->cart_receipt->cart->get_mount_balance_item_description( $item );
					} else if ( $item->type === 'install_kit' ) {
						$desc = $this->cart_receipt->cart->get_install_kit_item_description( $item );
					} else {
						$desc = '';
					}

					$product = $item->get_db_product();

					$order_item_id = $db->insert( $db->order_items, [
						'order_id' => $order_id,
						'part_number' => $item->part_number,
						'type' => $item->type,
						// this NEEDS to contain all available sizing data in a way that a human can easily verify it is correct
						'name' => $item->get_cart_title_in_one_line(),
						'description' => $desc,
						'price' => $item->get_price_raw(),
						'loc' => $item->loc,
						'package_id' => $item->package_id ? $item->package_id : null,
						'order_vehicle_id' => $order_vehicle_id ? $order_vehicle_id : null,
						'quantity' => $item->get_quantity() ? $item->get_quantity() : 0,
						'supplier' => $product ? $product->get( 'supplier' ) : '',
						'product' => $product ? gp_db_encode( $product->to_array() ) : '',
					], [
						'order_vehicle_id' => '%d',
						'package_id' => '%d',
						'quantity' => '%d',
					] );

					if ( ! $order_item_id ) {
						$this->add_error( $this->unexpected_error_msg( 'order_item' ) );

						return;
					}

					$this->db_order_items[] = DB_Order_Item::create_instance_via_primary_key( $order_item_id );
				}
			}
		}
	}

	/**
	 *
	 */
	public function insert_user_dry_run() {

		// see if user can be inserted (run as dry run)
		if ( $this->register ) {
			try {

				if ( ! $this->password_1 && ! $this->password_2 ) {
					$this->add_error( 'You have indicated that you want to create an account on checkout, so you must choose a password.' );

					return;
				}

				// DRY RUN, not database inserts should be done here.
				$can_insert = insert_user_from_user_input( $this->email, $this->password_1, $this->password_2, $this->first_name, $this->last_name, false, true );

				if ( ! $can_insert ) {
					// this message should not trigger
					$this->add_error( 'Cannot insert user.' );
				}

			} catch ( User_Exception $e ) {
				// this message should not trigger
				$this->add_error( $e->getMessage() );
			} catch ( Exception $e ) {
				$this->add_error( 'Could not insert user. Your transaction has not been processed.' );
			}
		}
	}

	/**
	 * Payment should be processed before this, therefore we don't add errors. This can't fail more or less,
	 * if it does, ok not the end of the world, all the users information will be stored with their order, but
	 * the create account on checkout will simply fail. run insert_user_dry_run() early on.
	 */
	public function insert_user_for_real() {

		if ( $this->register ) {

			try {
				$user_id = insert_user_from_user_input( $this->email, $this->password_1, $this->password_2, $this->first_name, $this->last_name, false, false );
			} catch ( Exception $e ) {
				$this->add_error( 'Could not register your account.' );

				return;
			}

			$this->db_user       = $user_id ? DB_User::create_instance_via_primary_key( $user_id ) : null;
			$this->user_inserted = $user_id && $this->db_user;

			if ( $this->user_inserted ) {

				// Not this way:
				//				$this->db_order->update_database_and_re_sync( array(
				//					'user_id' => (int) $this->db_user->get( 'user_id' )
				//				), array(
				//					'user_id' => '%d',
				//				) );

				// Update database only
				$this->db_order->update_database_but_not_instance(  array(
					'user_id' => (int) $this->db_user->get( 'user_id' )
				), array(
					'user_id' => '%d',
				) );

				// Re-load the entire object from the database.
				// Now, $this->db_order->user should be a DB_User object.
				$this->db_order = DB_Order::create_instance_via_primary_key( $this->db_order->get_primary_key_value() );

				assert( (bool) $this->db_order, 'noOrder_11' );

			} else {
				$this->add_error( 'An unexpected error occurred while trying to create your account.' );
			}
		}
	}

	/**
	 * Do this if you insert the user but then the capture transaction fails
	 */
	public function delete_newly_inserted_user() {

		// careful ... don't delete a user just because they were signed in ...
		if ( $this->register && $this->user_inserted && $this->db_user ) {
			$db = get_database_instance();
			$q  = '';
			$q  .= 'DELETE ';
			$q  .= 'FROM ' . $db->users . ' ';
			$q  .= 'WHERE user_id = :user_id ';
			$q  .= ';';
			$st = $db->pdo->prepare( $q );

			try {
				$user_id = $this->db_user->get( 'user_id' );
				$st->bindParam( ':user_id', $user_id, $db->int );
				$st->execute();

				$this->db_order->update_database_and_re_sync( array(
					'user_id' => '', // we could indicate 'deleted' but... sql column type may be int
				), array() );

				return true;
			} catch ( Exception $e ) {
				return false;
			}
		}
	}

	/**
	 * @param $amt
	 *
	 * @return string
	 */
	public function format_in_dollars( $amt ) {
		return format_in_dollars( $amt );
	}

    /**
     *
     */
	public function build_kount_inquiry_array(){

        // on localhost dev env, sometimes this is not a real IP
        $ip = app_get_ip_address();

        // this is might not be 100% fail safe.
        // when invalid IPs are submitted, kount will tell us,
        // and we'll have a log of it at least.
        if ( strlen( $ip ) < 8 ) {
            $ip = "12.34.56.78";
        }

        $bill = $this->cart_receipt->billing_address;
        $ship = $this->cart_receipt->shipping_address;

        $request = [
            'payment_type' => 'CARD',
            'email' => $this->email,
            'order_id' => $this->db_order->get_primary_key_value(),
            'financial_order_id' => $this->db_transaction->get_primary_key_value(),
            'ip_address' => $ip,
            // note: only works for canadian payments, but w/e, still putting currency here
            // dynamically. Might be useful if we log USD and kount does not work, we'll know why.
            'currency' => $this->locale === APP_LOCALE_US ? "USD" : "CAD",
            'payment_token' => $this->card,
            'amount' => $this->format_in_dollars( $this->cart_receipt->total ),
            'customer_name' => $this->first_name . " " . $this->last_name,
            'bill_street_1' => $bill->street_number . " " . $bill->street_name,
            'bill_street_2' => $bill->street_extra,
            'bill_country' => $bill->country,
            'bill_city' => $bill->city,
            'bill_province' => $bill->province,
            'bill_postal_code' => $bill->postal,
            // these would be for if we did this after authorization
            // 'avs_response' => "",
            // 'cvd_response' => "",
        ];

        if ( ! $this->shipping_is_billing ) {
            $request = array_merge( $request, [
                'ship_name' => $this->sh_first_name . " " . $this->sh_last_name,
                'ship_street_1' => $ship->street_number . " " . $ship->street_name,
                'ship_street_2' => $ship->street_extra,
                'ship_city' => $ship->city,
                'ship_postal_code' => $ship->postal,
                'ship_province' => $ship->province,
            ] );
        }

        // adds API key, and defaults, etc.
        $request = App_Kount::build_inquiry_txn( IN_PRODUCTION, $request );

        $products = array_map( function( $item ){

            /** @var DB_Order_Item $item */

            $type = $item->get( 'type' );
            $part_number = $item->get( 'part_number' );

            // this product "title" should be sufficient for the description, ie.
            // "RTX ENVY (Gloss Black) Wheels 20" X 8.5" 5x120 ET38 74.1"
            // or, "Wheel Installation Kit (Nuts)"
            $desc = $item->get( 'name' );

            // should already be properly dollar formatted
            $price_each = $this->format_in_dollars( $item->get( 'price' ) );

            $quantity = $item->get( 'quantity' );

            return Kount_Service::build_product( $type, $part_number, $desc, $quantity, $price_each );

        }, $this->db_order_items );

        // adds more defaults, and merges in the products.
        return Kount_Service::filter_validate_inquiry_txn( $request, $products );
    }

    /**
     * Send transaction data to Kount to recieve a risk inquiry response,
     * if the response is "A", continue with the transaction, if the response
     * is "D", we can continue or not continue, but the merchant is then
     * liable for charge backs.
     */
	public function do_kount_inquiry(){

	    $test_mode = ! IN_PRODUCTION;

        $request = $this->build_kount_inquiry_array();

        // store request
	    $this->db_transaction->update_kount_data( function( $ex ) use( $request ){

	        // must not store these values in the database
            $request['kount_api_key'] = "********";
            $request['payment_token'] = "********" . get_credit_card_last_4( $request['payment_token'] );

            $ex['inquiry_request'] = $request;
	        return $ex;
        } );

        $response = Kount_Service::send_request( $request, $this->payment->config->store_id, $this->payment->config->api_token, $test_mode );

        // store response
        $this->db_transaction->update_kount_data( function( $kount_data ) use( $response ){
            $kount_data['inquiry_response'] = $response;
            return $kount_data;
        } );

        $code = @$response['KountResult'];

        // going to need these for the kount update later on.
        $this->kount_inquiry_request = $request;
        $this->kount_inquiry_response = $response;

        if ( $code === "A" ) {

            // this means accept the transaction.
            // we can safely do nothing here.

        } else {
            $this->add_error( "Your transaction was declined." );
        }
    }

    /**
     * Build and send kount update, and store request/response to database.
     *
     * @throws Exception
     */
    public function do_kount_update(){

        $test_mode = ! IN_PRODUCTION;

        // these must originate from the preauth transaction, not the capture
        // transaction. If this breaks, we can get them from $this->payment->preauth.
        // NOTICE confusing naming convention: DB has "result". Request uses "response"
        $avs_response = $this->db_transaction->get( 'avs_result' );
        $cvd_response = $this->db_transaction->get( 'cvd_result' );

        // performs a full re-eval if true.
        // makes the score more accurate after preauth and capture...
        // however, we don't currently base our decision off of this
        // because the payment has already been processed by now.
        // its safe to set this to false eventually. For now, I want
        // to see how much the score changes after preauth, so we can
        // decide whether to change our process in the future.
        $re_eval_bool = false;

        $request = App_Kount::build_update_txn( ! $test_mode, [
            'kount_transaction_id' => @$this->kount_inquiry_response['KountInfo']['TRAN'],
            'session_id' => @$this->kount_inquiry_request['session_id'],
            'evaluate' => $re_eval_bool ? "true" : "false",
            'payment_response' => $this->db_transaction->get( 'success' ) ? "A" : "D",
            'avs_response' => $avs_response,
            'cvd_response' => $cvd_response,
            'order_id' => $this->db_order->get_primary_key_value(),
            'financial_order_id' => $this->db_transaction->get_primary_key_value(),
        ] );

        $request = Kount_Service::filter_validate_update_txn( $request );

        $response = Kount_Service::send_request( $request, $this->payment->config->store_id, $this->payment->config->api_token, $test_mode );

        // May or may not need these later.
        $this->kount_update_request = $request;
        $this->kount_update_response = $response;

        $this->db_transaction->update_kount_data( function( $ex ) use( $request, $response ) {

            $request['kount_api_key'] = "********";

            $ex['update_request'] = $request;
            $ex['update_response'] = $response;

            return $ex;
        });
    }

	/**
	 *
	 */
	public function do_preauth() {

		// orders have to be unique so if we re-create the orders table then primary keys get re-assigned
		// to moneris order IDs and we have problems, so append a timestamp.
		$this->payment->set_order_id( $this->db_order->get_primary_key_value() . '-' . time() );
		$this->payment->set_amount( $this->cart_receipt->total );
		$this->payment->set_card_number( $this->card );
		$this->payment->set_card_month( $this->card_month );
		$this->payment->set_card_year( $this->card_year );
		$this->payment->set_cvv( $this->cvv );
		$this->payment->set_avs_street_number( $this->cart_receipt->billing_address->street_number );
		$this->payment->set_avs_street_name( $this->cart_receipt->billing_address->street_name );
		$this->payment->set_avs_zipcode( $this->cart_receipt->billing_address->postal );

		// Send the request
		$this->payment->preauth();

        $receipt = $this->payment->preauth->receipt();

        // read from receipt object
        $read = function( $key, $default = "" ) use( $receipt ) {
            return $receipt ? $receipt->read( $key ) : $default;
        };

        // $preauth_success = (bool) $this->payment->preauth_success();

        // The keys here MUST BE database columns.
        // These values actually get overriden later by the capture
        // process, which is not ideal. This is why we're now also
        // going to store most of the same data (and more) in a serialized
        // txn_extra column.
        $preauth_database_update = [
            'last_operation' => 'preauth',
            'response_code' => $read( 'code' ),
            'response_message' => $read( 'message' ),
            'avs_result' => $read( 'avs_result' ),
            'cvd_result' => $read( 'cvd_result' ),
            'card_type' => $read( 'card' ),
            'auth_code' => $read( 'authorization' ),
            'trans_id' => $read( 'transaction' ),
            'reference_number' => $read( 'reference' ),
        ];

        // additional txn data.
        // note that some of the above data gets over written by the capture request,
        // the data below will not.
        $preauth_txn_extra = [
                'avs_result' => $preauth_database_update['avs_result'],
                'cvd_result' => $preauth_database_update['cvd_result'],
                'failedAvs' => $this->payment->preauth->failedAvs,
                'failedCvd' => $this->payment->preauth->failedCvd,
                'success' => $this->payment->preauth_success(),
            ] + $preauth_database_update;

        // Update the txn_extra JSON column
        $this->db_transaction->update_txn_extra( function( $ex ) use( $preauth_txn_extra ){
            $ex['preauth'] = $preauth_txn_extra;
            return $ex;
        } );

        $this->db_transaction->update_database_and_re_sync( $preauth_database_update );

        // intentionally using the same generic error message here...
        // don't want to give clues to bad users.
        if ( $this->payment->preauth->failedAvs ) {
            $this->add_error( "Transaction declined." );
        } else if ( $this->payment->preauth->failedCvd ) {
            $this->add_error( "Transaction declined." );
        } else if ( ! $this->payment->preauth_success() ) {
            $this->add_error( "Transaction declined." );
        }
	}

	/**
	 *
	 */
	public function do_capture() {

		// Send the request
		$this->payment->capture();

		$receipt = $this->payment->capture->receipt();

        // read from receipt object
		$read = function( $key, $default = "" ) use( $receipt ) {
		    return $receipt ? $receipt->read( $key ) : $default;
        };

		$success = (bool) $this->payment->capture_success();

        // The keys here MUST BE database columns.
        // These values may over write some values previously stored
        // in the database during preauth. So, we intentionally omit some.
        $capture_response_db_update = [
            'last_operation' => 'capture',
            'success' => $success,
            'response_code' => $read( 'code' ),
            'response_message' => $read( 'message' ),
            'auth_code' => $read( 'authorization' ),
            'trans_id' => $read( 'transaction' ),
            'reference_number' => $read( 'reference' ),
        ];

        // contains some repeated data but that's ok.
        // note that avs_result and cvd_result appears to not be
        // in the capture receipt. That's fine, I'm storing it so that
        // I know.
        $capture_txn_extra = [
            'avs_result' => $read( 'avs_result' ),
            'cvd_result' => $read( 'cvd_result' ),
            'failedAvs' => $this->payment->capture->failedAvs,
            'failedCvd' => $this->payment->capture->failedCvd,
            'card_type' => $read( 'card' ),
            ] + $capture_response_db_update;

        // Update the txn_extra JSON column
        $this->db_transaction->update_txn_extra( function( $ex ) use( $capture_txn_extra ){
            $ex['capture'] = $capture_txn_extra;
            return $ex;
        } );

		// this update mostly overrides values from the preauth, which is why we also
        // have the txn_extra column as well, which contains a lot of repeated data.
		$this->db_transaction->update_database_and_re_sync( $capture_response_db_update );

		if ( $success ) {

			// update order status.
			// don't do the user ID here - its taken care of elsewhere
			$this->db_order->update_database_and_re_sync( array(
				'order_status' => 'payment_received',
			) );

		} else {
			$this->add_error( "Transaction declined. Payment could not be processed." );
		}
	}

	/**
	 * Invalid options should be hidden from the user on the page, so this is unlikely to add errors, but
	 * of course we still need to check. Things here can get complex, we have to consider 2 main things:
	 * only ship to the currently selected locale (US or CA), but also, we'll verify that we have shipping rates
	 * in the database for the selected province or state. This is done via get_province_options() which is the same
	 * function used to print province options in the html. The third thing which gets really weird, is when the user
	 * says their shipping address is their billing address, but we cannot ship to their billing address. Note that
	 * billing address is not restricted by locale.
	 */
	public function shipping_billing_location_validation() {

		// Remember, shipping address is a copy of billing address when $this->shipping_is_billing is true

		$pickup = $this->ship_to === 'pickup';

		// not local pickup in the U.S.
		if ( $this->locale === 'US' ) {
			if ( $pickup ) {
				$this->add_error( 'Local pickup is not available for U.S. customers.' );

				return;
			}
		}

		// Only ship to Canada if locale is Canada
		if ( $this->cart_receipt->shipping_address->country == 'CA' ) {
			if ( $this->locale !== 'CA' ) {
				$this->add_error( 'You are trying to ship your order to Canada which is not your shipping region. To ship to Canada, change your shipping region using the flags at the top right of your screen. Otherwise, change your shipping address.' );

				return;
			}
		}

		if ( DISABLE_LOCALES && $this->cart_receipt->shipping_address->country === 'US' ) {
			$this->add_error( 'Sorry, we cannot ship to the U.S. at this time, but please check back later.' );
			return;
		}

		// Only ship to U.S. if locale is U.S.
		if ( $this->cart_receipt->shipping_address->country == 'US' ) {
			if ( $this->locale !== 'US' ) {
				$this->add_error( 'Can only ship to U.S if you have selected U.S. as your locale.' );

				return;
			}
		}

		// ensure valid billing province was selected
		$billing_province_options = get_province_options( $this->cart_receipt->billing_address->country );

		if ( ! in_array( $this->cart_receipt->billing_address->province, array_keys( $billing_province_options ) ) ) {
			$this->add_error( 'Please select a valid billing province.' );
		}

		// ensure valid shipping province was selected
		$shipping_province_options = get_province_options( $this->cart_receipt->shipping_address->country, true );

		if ( $this->ship_to == 'address' && ! in_array( $this->cart_receipt->shipping_address->province, array_keys( $shipping_province_options ) ) ) {

			if ( $this->shipping_is_billing && $this->ship_to == 'address' ) {
				$this->add_error( 'You have stated that your shipping address is your billing address, but we cannot ship to that location based on your current locale. Please manually enter your shipping address to choose from a list of valid options.' );
			} else {
				$this->add_error( 'Please select a valid shipping province.' );
			}
		}

	}

	/**
	 * allow only one submission (of a certain amount?) every X seconds.
	 *
	 * @param int $window - in seconds
	 *
	 * @return bool
	 */
	function is_transaction_duplicate( $window, $amt ){

		$payments = gp_if_set( $_SESSION, self::$duplicate_txn_key, array() );

		if ( $payments && is_array( $payments ) ) {
			foreach ( $payments as $key=>$data ){

				$time = gp_if_set( $data, 'time' );
				$_amt = gp_if_set( $data,'amt' );

				if ( ! $time || ! $_amt  ) {
					continue;
				}

				if ( time() - $time <= $window ) {

					if ( $amt == $_amt ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * pass in the price total. pass in in case successful txn triggers emptying cart, which may change the total depending
	 * on how we calculate it.
	 *
	 * @param $amt
	 */
	public function track_transaction_for_duplicates( $amt ){
		$_SESSION[self::$duplicate_txn_key] = isset( $_SESSION[self::$duplicate_txn_key] ) && is_array( $_SESSION[self::$duplicate_txn_key] ) ? $_SESSION[self::$duplicate_txn_key] : array();
		$_SESSION[self::$duplicate_txn_key][] = array(
			'amt' => $amt,
			'time' => time(),
		);
	}

	/**
	 *
	 */
	public function check_duplicate_transaction(){

		$window = 30;
		$amt = $this->get_price_total();

		if ( $this->is_transaction_duplicate( $window, $amt  ) ) {
			$this->add_error( 'A duplicate transaction has been detected. Please wait ' . $window . ' seconds.' );
		}
	}

	/**
	 *
	 */
	public function run() {

		$session_locale = app_get_locale();

		// this check should be redundant but may help prevent dev errors
		// and potentially charging the wrong currency.
		if ( $session_locale !== $this->cart_receipt->cart->locale ){
			$this->add_error( '[1] Shipping Region Error. Please re-load the page.' );
			return;
		}

		// trying to think of what would happen if a user opened a new tab and changed their shipping region,
		// then submitted the previously opened checkout form. Actually though, we're fine, because the confirmed
		// price total would check that we do would catch this. But regardless, let's put this check in place.
		if ( $session_locale !== gp_if_set( $this->raw, 'app_locale' ) ){
			$this->add_error( '[2] Your Shipping Region has changed. Please re-load the page.' );
			return;
		}

		if ( ! $this->cart_receipt->cart->count_items( false ) ) {
			$this->add_error( 'Your cart is empty.' );
			return;
		}

		if ( ! $this->cart_receipt->cart->is_every_item_in_stock() ) {
			$this->add_error( Cart::get_out_of_stock_msg_with_link_to_cart_page( true) );
			return;
		}

		// __construct() could add errors
		if ( $this->has_errors() ) {
			return;
		}

		// switching the order of these things, or returning after each one individually can be done if
		// needed, but for certain reasons, i think its best to do 3 at a time, then check for errors.
		// some of the printed error messages will make a bit more sense this way, which is partially
		// due to some pretty odd rules relating to shipping address.
		$this->validation_step_2();
		$this->shipping_billing_location_validation();
		$this->verify_price_and_acceptance();

		if ( $this->has_errors() ) {
			return;
		}

		if ( $this->register ) {
			// see if user insert will probably be successful. Definitely do this before storing the order
			$this->insert_user_dry_run();
		}

		// possible errors here: "email address exists, invalid first name, passwords don't match, etc"
		if ( $this->has_errors() ) {
			return;
		}

		// do this after validation. its payments we're concerned about..
		$this->check_duplicate_transaction();

		// possible error: "please submit payment only once every 5 seconds"
		if ( $this->has_errors() ) {
			return;
		}

		// Insert Order, Transaction, Order Items, Order Vehicles. After transaction, we'll
		// update some order/transaction fields. note: this is a lot of data to insert..
		// so we want to not do it when not necessary, however, I prefer to do it before preauth() so that
		// when a transaction fails, we know what was in the transaction, and just for other reasons.
		$this->insert_order();

		// not sure insert_order() will add any errors.
		if ( $this->has_errors() ) {
			return;
		}

        // not available for our U.S. merchant account
        if ( $this->locale === APP_LOCALE_CANADA ) {
            $this->do_kount_inquiry();
        }

        if ( $this->has_errors() ) {
            return;
        }

		// credit card, address, cvv, and amount pre authorization - to be followed by capture()
		$this->do_preauth();

		// very important to check this error - it will capture an error if pre auth fails.
		if ( $this->has_errors() ) {
			return;
		}

		// if we get to here, pre-authorization was successful, therefore we must either:
		// capture the full amount, or capture a zero amount before the script ends.

		if ( $this->register ) {
			// insert into database
			$this->insert_user_for_real();
		}

		if ( $this->has_errors() ) {

			// add an extra message just to be extra clear to the user.
			$this->add_error( 'Your payment has not been processed.' );

			// capture with amount zero - unlock funds from users account.
			$this->payment->capture_preauth_with_amt_zero();

			return;
		}

		// capture the full amount
		$this->do_capture();

		if ( $this->has_errors() || ! $this->payment->capture_success() ) {

			if ( ! $this->has_errors() ) {
				$this->add_error( $this->unexpected_error_msg() );
			}

			// make sure to remove the user before exiting.
			$this->delete_newly_inserted_user();

			return;
		}

		if ( $this->locale === APP_LOCALE_CANADA ) {
            $this->do_kount_update();
        }

		// track transaction so we can check for duplicate transaction on the next one
		$this->track_transaction_for_duplicates( $this->get_price_total() );

		$this->user_email_sent  = (bool) $this->send_user_email();
		$this->admin_email_sent = (bool) $this->send_admin_email();

		$this->db_order->update_database_and_re_sync( array(
			'user_email_sent' => (int) $this->user_email_sent,
			'admin_email_sent' => (int) $this->admin_email_sent,
		), array() );

		$this->success = true;

		if ( $this->user_inserted ) {
			$this->success_msg = '<p>Thank you for your purchase. Please check your email for your order confirmation. Your user account has been created. <a href="' . get_url( 'login' ) . '">Click here to login</a>.</p>';
		} else {
			$this->success_msg = '<p>Thank you for your purchase. Please check your email for your order confirmation.</p>';
		}

		// send emails to all suppliers immediately. I kind of think this shouldn't be on. The admin user
		// has a really simple button that performs this action in the admin section. To me it makes
		// more sense to have the admin look over everything before sending it. so if this gets commented
		// out, now you know why.
		send_all_supplier_order_emails( $this->db_order );

		$this->cart_receipt->cart->subtract_stock_levels_from_database_for_all_items_in_cart();

		// timing is important. We can do this at different times, but must be before
		// the cart is emptied.
		$this->build_fbq_purchase();

		$this->gtag_fields = [
		    'value' => $this->cart_receipt->total,
            'currency' => $this->locale === APP_LOCALE_CANADA ? 'CAD' : 'USD',
            'transaction_id' => 'ORDER-' . $this->db_order->get_primary_key_value(),
        ];

		// empty cart. This needs to be done late. After subtracting stock levels
		// in DB and building fbq array.
		empty_cart( $this->locale );
	}

	/**
	 *
	 */
	public function get_user_email_content() {

		$op = '';

		$login = '<a target="_blank" href="' . get_url( 'account' ) . '">log in</a>';

		$op .= '<p>Thank you for your purchase. Your details are below. If you created an account, you can ' . $login . ' to check the status of your order.</p>';
		$op .= '<p>If any of the data below appears to be incorrect, please contact us immediately.</p>';

		$op .= $this->get_email_order_id();

		$op .= HtmlEmail::header( 'Billing' );
		$op .= HtmlEmail::prop_value_pairs( $this->email_billing_array() );
		$op .= '';

		$op .= HtmlEmail::header( 'Shipping' );
		$op .= HtmlEmail::prop_value_pairs( $this->email_shipping_array() );

		$op .= HtmlEmail::header( 'Items' );
		$op .= $this->email_items_table();

		$op .= HtmlEmail::header( 'Receipt' );
		$op .= HtmlEmail::prop_value_pairs( $this->email_receipt_array() );

		return $op;
	}

	/**
	 * @return string
	 */
	public function get_email_order_id(){
		$ret = '<p>Your order ID is: ' . $this->db_order->get_primary_key_value() . '</p>';
		return $ret;
	}

	/**
	 *
	 */
	public function get_admin_email_content() {

		$op = '';
		$op .= '<p>A new order has been submitted. Details are below.</p>';

		$op .= $this->get_email_order_id();

		$op .= HtmlEmail::header( 'Billing' );
		$op .= HtmlEmail::prop_value_pairs( $this->email_billing_array() );

		$op .= HtmlEmail::header( 'Shipping' );
		$op .= HtmlEmail::prop_value_pairs( $this->email_shipping_array() );

		$op .= HtmlEmail::header( 'Items' );
		$op .= $this->email_items_table();

		$op .= HtmlEmail::header( 'Receipt' );
		$op .= HtmlEmail::prop_value_pairs( $this->email_receipt_array() );

		return $op;
	}

	/**
	 * Non-logged in users fill out their email address.
	 */
	public function get_user_email() {

		$user = cw_get_logged_in_user();

		if ( $user ) {
			return $user->get( 'email' );
		}

		return $this->email;
	}

	/**
	 *
	 */
	public function get_user_first_last_name() {

		$user = cw_get_logged_in_user();

		if ( $user ) {
			$n1 = $user->get( 'first_name' );
			$n2 = $user->get( 'last_name' );
		} else {
			$n1 = $this->first_name;
			$n2 = $this->last_name;
		}

		$ret = $n1 . ' ' . $n2;
		$ret = trim( $ret );

		return $ret;
	}

	/**
	 * Email to USER (Customer)
	 */
	public function send_user_email() {

		try {
			$mail = get_php_mailer_instance();

			$mail->isHtml( true );
			$mail->addAddress( $this->get_user_email(), $this->get_user_first_last_name() );

			$mail->Subject = 'Your Order Confirmation - Click It Wheels';
			$mail->Body    = $this->get_user_email_content();

			$mail->setFrom( get_email_from_address(), get_email_from_name() );
			$mail->addReplyTo( get_email_reply_to_address(), get_email_reply_to_name() );

			$send = php_mailer_send( $mail );

			if ( ! $send ) {
				log_data( $mail, 'send-user-email-' . date( 'Y-m-d' ) );
			}

			return $send;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * ADMIN email
	 */
	public function send_admin_email() {

		try {

			$mail = get_php_mailer_instance();

			$mail->addAddress( get_admin_email_to() );

			$mail->isHtml( true );
			$mail->Subject = 'New Order';
			$mail->Body    = $this->get_admin_email_content();

			$mail->setFrom( get_admin_email_from() );
			$send = php_mailer_send( $mail );

			if ( ! $send ) {
				log_data( $mail, 'send-admin-email-' . date( 'Y-m' ) );
			}

			return $send;

		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 *
	 */
	public function add_debug( $msg ) {
		$this->debug[] = $msg;
	}

	/**
	 *
	 */
	public function add_error( $msg ) {
		$this->success = false;
		if ( gp_is_singular( $msg ) ) {
			$this->errors[] = $msg;
		} else {
			$this->errors = array_merge( $this->errors, $msg );
		}
	}

	public function has_errors() {
		$ret = count( $this->errors ) > 0 || ! is_array( $this->errors );

		return $ret;
	}

	/**
	 *
	 */
	public function get_ajax_response() {

		$response = [];

		$response[ 'success' ] = $this->success;

		if ( $this->success ) {
			$response[ 'response_text' ] = $this->success_msg;
		} else {
			$response[ 'response_text' ] = gp_parse_error_string( $this->errors );
		}

		// receipt html
		$response[ 'receipt_html' ] = $this->receipt_html;
		$response[ 'items_html' ]   = $this->items_html;

		// if the boolean param to update is true, update it, even if the update string is empty
		if ( $this->update_confirm_total ) {
			$response[ 'confirm_total' ] = $this->confirm_total_html;
		}

		if ( ! IN_PRODUCTION ) {
			$response[ '_debug' ] = gp_array_to_js_alert( $this->debug );
		}

		// nov 2019 - adding facebook pixel tracking via JS.
		// what we put here will end up being tracked as a "Purchase"
		// @see checkout-form.js
		// @see https://developers.facebook.com/docs/facebook-pixel/implementation/conversion-tracking/
		$response['fbq_purchase'] = $this->fbq_purchase;

		if ( $this->success ) {
		    $response['gtag_fields'] = $this->gtag_fields;
        } else{
		    $response['gtag_fields'] = [];
        }

		return $response;
	}

	/**
	 * Timing matters unfortunately. Do this on successful purchase only,
	 * and before emptying the cart.
	 */
	public function build_fbq_purchase(){

		// build now, we'll attach this later to the ajax response.
		$this->fbq_purchase = [
			'currency' => app_is_locale_canada_otherwise_force_us( $this->locale ) ? "CAN" : "USD",
			'value' => $this->cart_receipt->total,
			// we probably don't have to send items but why not just send them,
			// its quite easy to do so.
			'contents' => call_user_func( function() {
				return array_values( array_map( function( $item ) {

					/** @var Cart_Item $item */

					// id and quantity are required fields apparently.
					return [
						'id' => $item->part_number,
						'quantity' => (int) $item->quantity,
						// send price I guess (optional). Price is for a single item.
						'price' => round( $item->get_price_raw(), 2 ),
					];
				}, $this->cart_receipt->cart->items ) );
			} ),
		];
	}

	/**
	 * @param        $index
	 * @param string $default
	 *
	 * @return bool|mixed
	 */
	public function get_raw_data( $index, $default = '' ) {
		$ret = gp_if_set( $this->raw, $index, $default );

		return $ret;
	}

	/**
	 *
	 */
	public function email_billing_array() {

		$key = 'billing';

		if ( isset( $this->cached_email_data[ $key ] ) ) {
			return $this->cached_email_data[ $key ];
		}

		$ret = array();

		$ret[] = array(
			'prop' => 'Contact Email',
			'value' => $this->get_user_email(),
		);

		$ret[] = array(
			'prop' => 'First Name',
			'value' => $this->first_name,
		);

		$ret[] = array(
			'prop' => 'Last Name',
			'value' => $this->last_name,
		);

		$ret[] = array(
			'prop' => 'Company',
			'value' => $this->company,
		);

		$ret[] = array(
			'prop' => 'Phone',
			'value' => $this->phone,
		);

		$ret[] = array(
			'prop' => 'Address',
			'value' => $this->cart_receipt->billing_address->get_in_one_line(),
		);

		$this->cached_email_data[ 'billing' ] = $ret;

		return $ret;
	}

	/**
	 * We could use this to send the card type along with the transaction, but I don't think its needed.
	 * We could also send last 4 digits of credit card. This one.. well seems like it would be useful for some people
	 * for tax purposes, but so far I haven't seen this value returned from moneris, so we could either make it
	 * ourselves, or just omit it. Anyways, for now.. this might be empty.
	 *
	 * @return array
	 */
	public function email_payment_array() {

		$key = 'payment';

		if ( isset( $this->cached_email_data[ $key ] ) ) {
			return $this->cached_email_data[ $key ];
		}

		$ret = array();

		$this->cached_email_data[ $key ] = $ret;

		return $ret;
	}

	/**
	 *
	 */
	public function email_receipt_array() {

		$key = 'receipt';

		if ( isset( $this->cached_email_data[ $key ] ) ) {
			return $this->cached_email_data[ $key ];
		}

		$ret = array();

		$locale = $this->db_order->get( 'locale' );
		// or $locale = app_get_locale() should do the same
		$before = '$';
		$t_sep  = ',';

		$ret[] = array(
			'prop' => 'Sub-Total',
			'value' => print_price_dollars( $this->cart_receipt->subtotal, $t_sep, $before, $locale ),
		);

		$ret[] = array(
			'prop' => 'Shipping',
			'value' => print_price_dollars( $this->cart_receipt->shipping, $t_sep, $before, $locale ),
		);

        $fee = $this->db_transaction->get( 'ontario_fee' );

        // fee is taxed, show it before tax amount.
        // orders placed before 05/08/2020 did not have this fee taxed.
        if ( $fee && $fee >= 0 ) {
            $ret[] = array(
                'prop' => get_ontario_tire_levy_cart_text( true ),
                'value' => print_price_dollars( $this->cart_receipt->ontario_fee, $t_sep, $before, $locale ),
            );
        }

		$ret[] = array(
			'prop' => 'Tax',
			'value' => print_price_dollars( $this->cart_receipt->tax, $t_sep, $before, $locale ),
		);

		$ret[] = array(
			'prop' => 'Total',
			'value' => print_price_dollars( $this->cart_receipt->total, $t_sep, $before, $locale ),
		);

		$this->cached_email_data[ $key ] = $ret;

		return $ret;
	}

	/**
	 *
	 */
	public function email_shipping_array() {

		$key = 'shipping';

		if ( isset( $this->cached_email_data[ $key ] ) ) {
			return $this->cached_email_data[ $key ];
		}

		$ret = array();

		// only one shipping option now
//		$ret[] = array(
//			'prop' => 'Shipping Option',
//			'value' => $this->ship_to,
//		);

		if ( $this->ship_to === 'address' ) {

			if ( $this->shipping_is_billing ) {
				$ret[] = array(
					'value' => get_shipping_is_billing_text( true ),
				);
			} else {

				$ret[] = array(
					'prop' => 'First Name',
					'value' => $this->sh_first_name,
				);

				$ret[] = array(
					'prop' => 'Last Name',
					'value' => $this->sh_last_name,
				);

				$ret[] = array(
					'prop' => 'Company',
					'value' => $this->sh_company,
				);

				$ret[] = array(
					'prop' => 'Phone',
					'value' => $this->sh_phone,
				);

				$ret[] = array(
					'prop' => 'Address',
					'value' => $this->cart_receipt->shipping_address->get_in_one_line(),
				);
			}
		}

		$this->cached_email_data[ $key ] = $ret;

		return $ret;
	}

	/**
	 *
	 */
	public function email_items_table() {

		$key = 'items_table';

		if ( isset( $this->cached_email_data[ $key ] ) ) {
			return $this->cached_email_data[ $key ];
		}

		$tr      = HtmlEmail::tr();
		$_tr     = HtmlEmail::_tr();
		$td      = HtmlEmail::td();
		$_td     = HtmlEmail::_td();
		$th      = HtmlEmail::th();
		$_th     = HtmlEmail::_th();
		$t_line  = HtmlEmail::t_line();
		$_t_line = HtmlEmail::_t_line();

		$cols = array(
			'item' => 'Item',
			'quantity' => 'Quantity',
			'price' => 'Price',
		);

		ob_start();

		echo '<table style="border-collapse: collapse; border: 1px solid grey;">';

		// headers
		echo $tr;

		foreach ( $cols as $c1 => $c2 ) {
			echo $th;
			echo $c2;
			echo $_th;
		}

		echo $_tr;

		// rows

		if ( $this->db_order_items ) {
			/** @var DB_Order_Item $db_order_item */
			foreach ( $this->db_order_items as $db_order_item ) {

				// <tr>
				echo $tr;

				foreach ( $cols as $c1 => $c2 ) {
					$cell_data = $db_order_item->summary_table_cell_data( $c1 );

					$cell_data = gp_make_array( $cell_data );

					echo $td;

					if ( $cell_data && is_array( $cell_data ) ) {
						foreach ( $cell_data as $cell_line ) {
							// ie. <span style="...">
							echo $t_line;
							echo $cell_line;
							echo $_t_line;
						}
					}

					echo $_td;
				}

				// </tr>
				echo $tr;
			}
		}

		echo '</table>';

		$ret = ob_get_clean();

		$this->cached_email_data[ $key ] = $ret;

		return $ret;
	}
}

// note on payment status, from https://github.com/craigpaul/moneris-api
//The status will return a status code matching the appropriate error returned. See below for an example of the possible statuses returned.
//
//ERROR                    = -23;
//INVALID_TRANSACTION_DATA = 0;
//
//FAILED_ATTEMPT            = -1;
//CREATE_TRANSACTION_RECORD = -2;
//GLOBAL_ERROR_RECEIPT      = -3;
//
//SYSTEM_UNAVAILABLE    = -14;
//CARD_EXPIRED          = -15;
//INVALID_CARD          = -16;
//INSUFFICIENT_FUNDS    = -17;
//PREAUTH_FULL          = -18;
//DUPLICATE_TRANSACTION = -19;
//DECLINED              = -20;
//NOT_AUTHORIZED        = -21;
//INVALID_EXPIRY_DATE   = -22;
//
//CVD               = -4;
//CVD_NO_MATCH      = -5;
//CVD_NOT_PROCESSED = -6;
//CVD_MISSING       = -7;
//CVD_NOT_SUPPORTED = -8;
//
//AVS             = -9;
//AVS_POSTAL_CODE = -10;
//AVS_ADDRESS     = -11;
//AVS_NO_MATCH    = -12;
//AVS_TIMEOUT     = -13;
//
//POST_FRAUD = -22;`

//$kount_inquiry_optional_fields = [
//    'auto_number_id' => '',
//    'avs_response' => '',
//    'cvd_response' => '',
//    'bill_street_1' => '',
//    'bill_street_2' => '',
//    'bill_country' => '',
//    'bill_city' => '',
//    'bill_province' => '',
//    'bill_postal_code' => '',
//    'dob' => '',
//    'epoc' => '',
//    'gender' => '',
//    'last4' => '',
//    'customer_name' => '',
//    'financial_order_id' => '',
//    'ship_street_1' => '',
//    'ship_street_2' => '',
//    'ship_city' => '',
//    'ship_email' => '',
//    'ship_name' => '',
//    'ship_postal_code' => '',
//    'ship_province' => '',
//    'ship_type' => '',
//    'customer_id' => '',
//    'local_attrib_n' => '',
//    'data_key' => '',
//];
