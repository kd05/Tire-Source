<?php

/**
 * @param $str
 *
 * @return bool
 */
function validate_credit_card_luhn_check( $str ){

	$checksum = '';

	foreach (str_split(strrev((string) $str)) as $i => $d) {
		$checksum .= $i %2 !== 0 ? $d * 2 : $d;
	}

	$ret = array_sum(str_split($checksum)) % 10 === 0;
	return $ret;
}

/**
 * @param $str
 *
 * @return mixed
 */
function strip_non_numeric( $str ) {
    $str = $str ?? '';
	$str = preg_replace("/[^0-9]/", "", $str);

	return $str;
}

/**
 * @param $str
 */
function clean_credit_card_number( $str ) {
	return strip_non_numeric( $str );
}

/**
 * @param $v
 *
 * @return bool
 */
function filter_validate_persons_name( &$v ) {
	$v = strip_tags( $v );
	$v = gp_test_input( $v );

	if ( strlen( $v ) > 100 ) {
		return false;
	}

	return true;
}
/**
 * @param $v
 *
 * @return mixed
 */
function validate_email( $v ){
	// must wrap in brackets, otherwise filter_var will return the text, we want true.
	$ret = ( filter_var( $v, FILTER_VALIDATE_EMAIL ) );
	return $ret;
}

/**
 *
 */
function validate_password( $v , &$msg = '' ){

	$v = gp_force_singular( $v );
	$v = trim( $v );

	if ( strlen( $v ) < 8 ) {
		$msg = 'Please use at least 8 characters for your password';
		return false;
	}

	return true;
}
