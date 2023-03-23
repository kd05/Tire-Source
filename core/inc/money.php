<?php

/**
 * @param $amt
 *
 * @return string
 */
function print_price_dollars_formatted( $amt ) {
	return print_price_dollars( $amt, ',', '$', app_get_locale() );
}

/**
 * @param        $amt
 * @param        $thousands_sep
 * @param string $before
 * @param string $locale
 */
function print_price_dollars( $amt, $thousands_sep = '', $before = '', $locale_for_printing = '' ) {

	$amt = str_replace( '$', '', $amt );
	$amt = format_price_dollars( $amt );
	$str = number_format( $amt, '2', '.', $thousands_sep );

	$ret = '';

	$ret .= $before;
	$ret .= $str;

	if ( $locale_for_printing === APP_LOCALE_CANADA ) {
		$ret = 'CAD ' . $ret;
	} else if ( $locale_for_printing === APP_LOCALE_US ) {
		$ret = 'USD ' . $ret;
	}

	$ret = trim( $ret );
	return $ret;
}

/**
 * @param $amt
 *
 * @return string
 */
function format_in_dollars( $amt ) {
	$in = get_string_for_log( $amt );
	$ret = number_format( $amt, 2, '.', '' );
	$out = get_string_for_log( $ret );
	// log_data( $in . ' .. ' . $out, 'format_in_dollars' );
	return $ret;
}

/**
 * we could use ob_start(), var_dump(), ob_get_clean(), but the issue
 * is that this includes quotes, and when those are json encoded, the log file looks ugly.
 * So use this mainly when you want to store a singular value, but include its type.
 *
 * Example output: [float] 1.23
 *
 * @param $var
 */
function get_string_for_log( $var ) {

	$ret = '';

	if ( gp_is_singular( $var ) ) {

		if ( gettype( $var ) === 'boolean' && ! $var ) {
			$vv = "0";
		} else {
			$vv = $var;
		}

		$ret = '[' . gettype( $var ) . '] ' . $vv;
		return $ret;
	}

	// this might not look good but i don't know if we will even need it
	return gp_make_singular( $var );
}

/**
 * $267.55 => 267.55
 * 45.2385 => 45.23
 * abcd23.553 => 23.55
 * 45 => 45
 * 45.00 => 45
 * 45.000001 => 45 (I think)
 * '' or false or null etc. => not sure.. 0 I think.. maybe check > 0 to know..
 *
 * Use number_format on the result when printing, otherwise it might show up as an integer amount.
 */
function format_price_dollars( $str ) {

    $str = $str ?? 0;

	if ( strpos( $str, '$' ) !== false ) {
		$str = trim( $str, '$' );
	}

	return round( $str, 2 );
}

/**
 * @param $v
 */
function cents_to_dollars( $v ) {

	$log = get_string_for_log( $v );

	$dollars = bcdiv( $v, 100 );
	$dollars = round( $dollars, 2 );
	$ret = format_price_dollars( $dollars );

    //	if ( ! IN_PRODUCTION ) {
//        $log .= ' ... ' . get_string_for_log( $v );
//		log_data( $log, 'cents_to_dollars' );
//	}

	return $ret;
}

function cents_to_dollars_alt( $integer_cents ) {

    if ( $integer_cents < 1 ) {
        return '0.00';
    }

    return number_format( round( $integer_cents / 100, 2 ), 2, '.', '' );
}

/**
 * @param $v
 */
function dollars_to_cents( $v ) {

	$log = get_string_for_log( $v );

	// careful. php casting to (int) is a pile of crap. when $v is not known...
	// "Never cast an unknown fraction to integer, as this can sometimes lead to unexpected results." from the PHP Manual on Integer.
	// $ret = (int) ( $v * 100 );

	// if bcmath extension is not installed, then I think $v / 100 is ok, so long as we round() before casting to (int)
	// at least that's my theory.
	$cents = bcmul( $v, 100 );
	$ret = round( $cents, 0 );
	$ret = (int) $ret;

	// if ( ! IN_PRODUCTION ) {
//        $log .= ' ... ' . get_string_for_log( $ret );
//		log_data( $log, 'dollars_to_cents' );
	// }

	return $ret;
}

/**
 * This is more for when a user enters an amount, and you tell them to use the proper format.
 * This is not for the result of dividing or multiplying things via php or sql, although
 * after you divide and then try to do the proper conversion, you probably want to run it through
 * this before sending it off to a payment gateway.
 *
 * Make sure you trim and strip your dollar sign first.
 *
 * This is valid: 4445.54, 99.00
 * These are not: 454.3, 454.324, 99
 *
 * Max digits left also caps the amount. Ie 4 max digits left means a number larger than 9999.99 is invalid.
 *
 * @param     $str
 * @param int $max_digits_left
 */
//function gp_validate_price_dollars_string( $str, $max_digits_left = 4 ) {
//	$max_digits_left = (int) $max_digits_left;
//
//	// we have to subtract 1 still..
//	if ( $max_digits_left < 2 ) {
//		return false;
//	}
//
//	$matches = array();
//	// IMPORTANT: this doens't allow amounts less than 1 dollar! (maybe)
//	// 1-9, followed by (max digits left) # of digits, followed by a dot, followed by exactly 2 digits
//	$match = preg_match( '/^[1-9]{1}[\d]{0,' . ( $max_digits_left - 1 ) . '}[.]{1}[\d]{2}$/', $str, $matches );
//
//	// boolean
// return (bool) $match;
//}
//
///**
// * Convert (int) cents to dollars without a dollar sign
// *
// * In general simplify to int when printing to the screen and you want $980.00 to just say $980
// * But we may also use this function to convert cents to dollars to send to authorize.net, so probably
// * don't want to simplify as they seem to expect dollar format with 2 decimals
// *
// * @param $cents
// *
// * @return string
// */
//function gp_convert_cents( $cents, $simplify_to_int = false, $thousands_sep = '' ) {
//	// this often causes a actual integer (like 7628) to round up or down by 1 (seriously, it does)
//	// $cents = (int) $cents;
//
//	$matches = array();
//
//	// match the first sequence of only numbers
//	// ie. $44353=> 44353
//	// 343 => 343
//	// &nbsp343 => 343
//	//// 109.99999 => 109 (no rounding)
//	// the number being passed in should be properly formatted as cents
//	/// but we have to be careful when the value might have been multiplied by something
//	/// like a tax percentage. in general the rounding needs to be done before getting to here...
//	/// dont be an idiot and just strip non numeric characters
//	/// because then 99.999999999 => 99999999999 and you might charge someone a billion dollars.
//	$match = preg_match( '/([0-9]*)/', $cents, $matches );
//
//	if ( $match ) {
//		$cents = gp_if_set( $matches, 1, 0 );
//	} else {
//		$cents = 0;
//	}
//
//	$dollars = round( $cents / 100, 2 );
//	$dollars = sprintf( '%0.2f', $dollars );
//
//	$string = number_format( $dollars, 2, '.', $thousands_sep );
//
//	// what was this for again???
//	//	if ( $trim ) {
//	//        $string = gpm_trim_end( $trim, $string );
//	//    }
//
//	// this means remove .00 from the end, but not .10 or .09 or .55
//	if ( $simplify_to_int ) {
//
//		$trim = array(
//			'.00',
//		);
//
//		$string = gpm_trim_end( $trim, $string );
//	}
//
//	return $string;
//}