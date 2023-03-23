<?php

/**
 * @param $code
 */
function get_country_name( $code, $default = false  ) {

	$map = array(
		'CA' => 'Canada',
		'US' => 'United States',
	);

	$ret = gp_if_set( $map, $code, $default );
	return $ret;
}

/**
 *
 */
function get_address_allowed_country_form_items( $locale, $for_shipping = false ){

	if ( $for_shipping ) {
		if ( $locale == 'CA' ) {
			$items = array(
				'CA' => 'Canada',
			);
		} else {
			$items = array(
				'US' => 'United States',
			);
		}
	} else {

		// billing address isnt affected by locale
		$items = array(
			'CA' => 'Canada',
			'US' => 'United States',
		);
	}

	return $items;
}

/**
 * @param $province_code
 * @param $country_code
 */
function get_province_name( $province_code, $country_code, $default = false ) {

	$db = get_database_instance();
	$p = [];
	$q = '';
	$q .= 'SELECT province_name ';
	$q .= 'FROM ' . $db->regions . ' AS r ';

	$q .= 'WHERE province_code = :province_code AND country_code = :country_code ';
	$p[] = ['province_code', $province_code, '%s' ];
	$p[] = ['country_code', $country_code, '%s' ];

	$q .= 'LIMIT 0, 1 ';
	$q .= ';';

	$results = $db->get_results( $q, $p );

	$row = $results ? gp_if_set( $results, 0 ) : false;
	$name = gp_if_set( $row, 'province_name', $default  );

	return $name;
}

/**
 *
 */
function get_province_options( $country_code, $for_shipping = false ){

	$db = get_database_instance();
	$p = [];
	$q = '';
	$q .= 'SELECT * ';
	$q .= 'FROM ' . $db->regions . ' AS r ';

	if ( $for_shipping ) {
		$q .= 'INNER JOIN ' . $db->shipping_rates . ' AS s ON s.region_id = r.region_id ';
	}

	$q .= 'WHERE country_code = :country_code ';
	$p[] = [ 'country_code', $country_code, '%s' ];

	$q .= 'ORDER BY province_name ASC ';
	$q .= '';
	$q .= ';';

	$results = $db->get_results( $q, $p );

	$ret = array();

	if ( $results ) {
		foreach ( $results as $k=>$v ) {

			$country_code = gp_if_set( $v, 'country_code' );
			$province_code = gp_if_set( $v, 'province_code' );
			$province_name = gp_if_set( $v, 'province_name' );

			$allow_shipping = gp_if_set( $v, 'allow_shipping' );

			// Skip some items when we don't ship there..
			if ( $for_shipping && ! $allow_shipping ) {
				continue;
			}

//			if ( $for_shipping ) {
//				$price_tire = gp_if_set( $v, 'price_tire' );
//				$price_rim = gp_if_set( $v, 'price_rim' );
//				$price_mounted = gp_if_set( $v, 'price_mounted' );
//				if ( ! validate_shipping_price_data( $country_code, $province_code, $price_tire, $price_rim, $price_mounted ) ) {
//					continue;
//				}
//			}

			$ret[$province_code] = $province_name;
		}
	}

	return $ret;
}

/**
 * @param $args
 */
function get_address_form_inputs( $args, $for_shipping = false ) {

	$name_pre = gp_if_set( $args, 'name_pre' );
	$locale = gp_if_set( $args, 'locale', app_get_locale() );

	$op = '';

	// street number
	$op .= get_form_input( array(
		'req' => true,
		'name' => $name_pre . 'street_number',
		'label' => 'Street Number',
	));

	// street name
	$op .= get_form_input( array(
		'req' => true,
		'name' => $name_pre . 'street_name',
		'label' => 'Street Name',
	));

	// street extra (apt etc.)
	$op .= get_form_input( array(
		'name' => $name_pre . 'street_extra',
		'label' => get_street_extra_text(),
	));

	// city
	$op .= get_form_input( array(
		'req' => true,
		'name' => $name_pre . 'city',
		'label' => 'City',
	));

	// some country will be disabled, but put all provinces for all countries into data attributes
	$op .= get_form_select( array(
		'req' => true,
		'name' => $name_pre . 'province',
		'label' => get_province_label( $locale ),
		'select_2' => true,
		'add_class_2' => 'on-white',
		'data_attributes' => array(
			'provinces' => gp_json_encode( array(
				'CA' => array_merge( [ '' => '&nbsp;' ], get_province_options( 'CA', $for_shipping ) ),
				'US' => array_merge( [ '' => '&nbsp;' ], get_province_options( 'US', $for_shipping ) ),
			)),
		)
	));

	// Country Canada
	$op .= get_form_select( array(
		'req' => true,
		'name' => $name_pre . 'country',
		'label' => 'Country',
		'select_2' => true,
		'add_class_2' => 'on-white',
	), array(
		'items' => get_address_allowed_country_form_items( $locale, $for_shipping ),
		'current_value' => $locale,
	));

	// postal
	$op .= get_form_input( array(
		'req' => true,
		'name' => $name_pre . 'postal',
		'label' => $locale == 'CA' ? 'Postal Code' : 'Zip Code',
	));

	return $op;
}

/**
 * @param null $cart_receipt
 * @param string $province
 * @param string $page
 * @return false|string
 */
function get_receipt_html( $cart_receipt = null, $province = '', $page = 'checkout' ) {

	$province = gp_test_input( $province );

	// make a new cart receipt by default, if a province is provided
	if ( $province && ! $cart_receipt ) {
		$country = app_get_locale(); // can only ship to locale, and this is the shipping country
		$sa = new Shipping_Address( '', '', '', '', $province, $country, '' );
		$cart_receipt = new Cart_Receipt( get_cart_instance(), Billing_Address::create_empty(), $sa, false );
	}

    set_global( 'receipt', $cart_receipt );
    set_global( 'page', $page );

	ob_start();
	include TEMPLATES_DIR . '/cart/order-summary.php';
	return ob_get_clean();
}

/**
 * @param bool $period
 *
 * @return string
 */
function get_shipping_is_billing_text( $period = false ){
	$ret = 'Shipping address is the same as billing address';
	$ret .= $period ? '.' : '';
	return $ret;
}