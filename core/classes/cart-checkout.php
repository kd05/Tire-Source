<?php

/**
 * Use an "address" object to pass into functions that calculate shipping
 * or taxes. this should make it easier to change the conditions in the future,
 * if for example, we calculate shipping based on postal code rather than just province.
 *
 * Class Address
 */
Class Address {

	public $street_number;
	public $street_name;
	public $street_extra;
	public $city;

	/**
	 * should be 2 character uppercase province or state codes, but before
	 * you rely on this, you may want to verify.
	 *
	 * @var string
	 */
	public $province;
	public $postal;

	/**
	 * should be 2 character uppercase country code, and also
	 * only 'CA' or 'US'.
	 *
	 * @var string
	 */
	public $country;

	/**
	 * Address constructor.
	 *
	 * @param $street
	 * @param $city
	 * @param $province
	 * @param $country
	 * @param $postal
	 */
	public function __construct( $street_number, $street_name, $street_extra, $city, $province, $country, $postal ) {
		$this->street_number = gp_test_input( $street_number );
		$this->street_name   = gp_test_input( $street_name );
		$this->street_extra  = gp_test_input( $street_extra );
		$this->city          = gp_test_input( $city );
		$this->province      = gp_test_input( $province );
		$this->postal        = gp_test_input( $postal );
		$this->country       = gp_test_input( $country );
	}

	/**
	 *
	 */
	public function get_in_one_line() {

		$arr = array(
			$this->street_number . ' ' . $this->street_name
		);

		if ( $this->street_extra ) {
			$arr[] = $this->street_extra;
		}

		$arr[] = $this->city;
		$arr[] = $this->province;
		$arr[] = $this->country;
		$arr[] = $this->postal;

		$op = implode( ', ', $arr );
		return $op;
	}

	/**
	 * @return static
	 */
	public static function create_empty() {
		return new static ( '', '', '', '', '', '', '' );
	}
}

/**
 * Class Shipping_Address
 */
Class Shipping_Address extends Address {

	/**
	 * Address constructor.
	 *
	 * @param $street
	 * @param $city
	 * @param $province
	 * @param $country
	 * @param $postal
	 */
	public function __construct( $street_number, $street_name, $street_extra, $city, $province, $country, $postal ) {
		parent::__construct( $street_number, $street_name, $street_extra, $city, $province, $country, $postal );
	}
}

/**
 * Class Billing_Address
 */
Class Billing_Address extends Address {

	/**
	 * Billing_Address constructor.
	 *
	 * @param $street
	 * @param $city
	 * @param $province
	 * @param $country
	 * @param $postal
	 */
	public function __construct( $street_number, $street_name, $street_extra, $city, $province, $country, $postal ) {
		parent::__construct( $street_number, $street_name, $street_extra, $city, $province, $country, $postal );
	}
}

/**
 * This class is meant to bypass the entire shipping rates table
 * without running any queries or doing other logic.
 *
 * Its purpose is to replace the Shipping_Prices class when
 * shipping option is set to "local pickup".
 *
 * Note however, that at the time of writing this, local pickup has
 * been disabled as a shipping option.
 *
 * When shipping to U.S. or Canadian province, and you want free shipping,
 * you ideally should NOT be using this class. Instead, use the admin tool
 * to set the shipping rates to zero for your desired provinces and states,
 * even if that means all provinces and all states.
 *
 * Class Shipping_Prices_Free
 */
Class Shipping_Prices_Free extends Shipping_Prices {

	/**
	 * Shipping_Prices_Free constructor.
	 *
	 * @param Address $shipping_address
	 */
	public function __construct( Address $shipping_address ) {
		parent::__construct( $shipping_address );
	}

	/**
	 *
	 */
	public function run() {
		$this->valid                 = true;
		$this->price_per_tire        = 0;
		$this->price_per_rim         = 0;
		$this->price_per_tire_on_rim = 0;
	}
}

/**
 * Takes in an Address object, and returns the price to ship tires, rims and packages,
 * as well as an indicator of whether or not the address is valid, ie. do we even ship to that address?
 *
 * Class Shipping_Prices
 */
Class Shipping_Prices {

	/** @var Address */
	public $address;

	/**
	 * @var bool
	 */
	public $valid;

	/**
	 * dollar amount
	 *
	 * @var float
	 */
	public $price_per_tire;

	/**
	 * dollar amount
	 *
	 * @var float
	 */
	public $price_per_rim;

	/**
	 * dollar amount
	 *
	 * @var float
	 */
	public $price_per_tire_on_rim;
    /**
     * @var bool|int|mixed
     */
    private $price_per_mounted;

    /**
	 * Shipping_Prices constructor.
	 *
	 * @param Address $shipping_address
	 */
	public function __construct( Address $shipping_address ) {
		$this->address = $shipping_address;
		$this->valid   = false;
		$this->run();
	}

	/**
	 *
	 */
	public function run() {

		// everything should already be upper case but w/e
		$country  = $this->address->country;
		$province = $this->address->province;

		// we could also validate that country and province are valid but number 1
		// this isnt the place to do so, and number 2, if invalid country and province
		// are passed in, the query won't return any results, which.. kind of voids what I just said
		if ( ! $country || ! $province ) {
			$this->valid = false;
			return;
		}

		$db = get_database_instance();
		$p  = [];
		$q  = '';
		$q  .= 'SELECT * ';
		$q  .= 'FROM ' . $db->regions . ' AS r ';
		$q  .= 'INNER JOIN ' . $db->shipping_rates . ' AS s ON s.region_id = r.region_id ';
		$q  .= 'WHERE 1 = 1 ';

		$q   .= 'AND r.country_code = :country_code AND r.province_code = :province_code ';
		$p[] = [ 'country_code', $country ];
		$p[] = [ 'province_code', $province ];

		$q .= '';
		$q .= 'LIMIT 0, 1 ';
		$q .= ';';

		$results = $db->get_results( $q, $p );
		$row     = $results ? gp_if_set( $results, 0, null ) : null;

//		$_c  = gp_if_set( $row, 'country_code' );
//		$_p  = gp_if_set( $row, 'province_code' );

		$_pt = gp_if_set( $row, 'price_tire' );
		$_pr = gp_if_set( $row, 'price_rim' );
		$_pm = gp_if_set( $row, 'price_mounted' );
		$_allow = gp_if_set( $row, 'allow_shipping' );

		// Do we ship to the province or state given ??
		if ( ! $_allow || ! $row ){
			$this->valid = false;
			return;
		}

		// convert empty values to zero although we would expect 0 to be in place.
		$_pt = $_pt ? $_pt : 0;
		$_pr = $_pr ? $_pr : 0;
		$_pm = $_pm ? $_pm : 0;

		// not only CAN these values be zero, but chances are, they are ALL zero.
		// we built the system with shipping prices all in place, and then later on
		// decided to put the shipping prices into the price of the products.
		// therefore, database values may all be zero.. or may not be.
		$this->price_per_tire = $_pt;
		$this->price_per_rim = $_pr;

		// another note: mount and balance should not be available for U.S. customers
		// actually, U.S. site is disabled, and so is mount and balance
		// but in the future.. be aware, we may return a mount and balance price for U.S.
		// this does not mean we should use it however.
		$this->price_per_mounted = $_pm;

		// Mark the shipping rates as VALID
		$this->valid = true;
	}
}

/**
 * We should run this upon checkout to make sure the database results were valid, and we'll probably also run it in the
 * admin section and show alerts if some information was entered incorrectly.
 *
 * @param $country
 * @param $province
 * @param $price_tire
 * @param $price_rim
 * @param $price_mounted
 */
//function validate_shipping_price_data( $country, $province, $price_tire, $price_rim, $price_mounted ) {
//
//	if ( $country == 'CA' ) {
//		if ( $price_tire && $price_rim && $price_mounted ) {
//			return true;
//		}
//	} else if ( $country == 'US' ) {
//		if ( $price_tire && $price_rim ) {
//			return true;
//		}
//	}
//
//	return false;
//}

class Tax_Rate_By_Address {

	public $billing_address;
	public $tax_rate;
	public $valid;

	/**
	 * Tax_Rate_By_Address constructor.
	 *
	 * @param $address
	 */
	public function __construct( Address $billing_address ) {

		$this->billing_address = $billing_address;
		$this->valid           = false;
		$this->tax_rate        = 0;

		$country  = strtoupper( $this->billing_address->country );
		$province = strtoupper( $this->billing_address->province );

		// u.s. no longer has free shipping
//		if ( $country === 'US' ) {
//			$this->valid    = true;
//			$this->tax_rate = 0;
//			return;
//		}
//		if ( $country != 'CA' ) {
//			return;
//		}

		if ( $country != 'CA' && $country != 'US' ) {
			return;
		}

		// tax rate for canada..
		$db = get_database_instance();
		$p  = [];
		$q  = '';
		$q  .= 'SELECT * ';
		$q  .= 'FROM ' . $db->regions . ' AS r ';
		$q  .= 'INNER JOIN ' . $db->tax_rates . ' AS t ON t.region_id = r.region_id ';
		$q  .= 'WHERE 1 = 1 ';

		$q   .= 'AND r.country_code = :country_code AND r.province_code = :province_code ';
		$p[] = [ 'country_code', $country, '%s' ];
		$p[] = [ 'province_code', $province, '%s' ];

		$q .= 'LIMIT 0,1 ';
		$q .= ';';

		$results = $db->get_results( $q, $p );

		if ( $results ) {

			$row = gp_if_set( $results, 0 );

			// if tax rate is empty .. default to no taxes.
			$tax_rate = gp_if_set( $row, 'tax_rate', 0 );

			$this->tax_rate = round( $tax_rate / 100, 5 );
			$this->valid    = true;
		}
	}
}

