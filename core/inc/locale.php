<?php

use Jaybizzle\CrawlerDetect\CrawlerDetect;

/**
 *
 */
function app_get_locale_text( $locale ){

	switch( $locale ) {
		case APP_LOCALE_CANADA;
			return 'Canada';
			break;
		case APP_LOCALE_US;
			return 'United States';
			break;
	}

	return '';
}

/**
 * Warning: an empty like value for $str CANNOT be considered valid.
 *
 * @param $str
 *
 * @return bool
 */
function app_is_locale_valid( $str ){
	$valid = app_get_valid_locales();
	$ret = in_array( $str, $valid );
	return $ret;
}

/**
 * Converts 2-3 lines of code into 1 line if code in many places.
 *
 * For example, in many functions we want locale to be able to be passed in,
 * but also default it to the current locale when not passed in.
 *
 * @param      $locale
 * @param bool $fail_silently - if false, throws an exception if $locale is not null and also not valid.
 *
 * @return string
 * @throws Exception
 */
function app_get_locale_from_locale_or_null( $locale, $fail_silently = false ) {

	$locale = $locale === null ? app_get_locale() : $locale;

	if ( app_is_locale_valid( $locale ) ) {
		return $locale;
	}

	if ( ! $fail_silently ) {
		throw new Exception( 'Input was not null or a valid locale.' );
	}
}

/**
 * upon each page load, if the locale is not set in $_SESSION, this function
 * will be called. So this is where we can check the cookie as well.
 *
 * @return string
 */
function app_get_default_locale(){
	return APP_LOCALE_CANADA;
}

/**
 * @return array
 */
function app_get_valid_locales(){
    return [ APP_LOCALE_CANADA, APP_LOCALE_US ];
}

/**
 * Cannot run this after headers sent.
 *
 * @param $str
 */
function app_set_locale( $str ) {

	$str = gp_test_input( $str );

	$cli = defined( 'CLI' ) && CLI;

	// verify locale is valid before setting
	if ( app_is_locale_valid( $str ) ) {

		// unset previous cookie
		if ( isset( $_COOKIE[APP_LOCALE_SESSION_COOKIE_INDEX] ) ) {

		    if ( ! $cli ) {
                setcookie( APP_LOCALE_SESSION_COOKIE_INDEX, $_COOKIE[APP_LOCALE_SESSION_COOKIE_INDEX], 1 );
            }
		}

		// set new cookie
        if ( ! $cli ) {
            setcookie( APP_LOCALE_SESSION_COOKIE_INDEX, $str );
        }

		// store in session for (easier?) access
		$_SESSION[APP_LOCALE_SESSION_COOKIE_INDEX] = $str;
		return true;
	}

	return false;
}

/**
 * Run this early on on each page load. If the locale is not set in
 * session, get it from the cookie, and set it. Otherwise, set it to the default,
 * which should set both the cookie and the session. app_get_locale() will check session
 * and then fallback to cookie... the fallback shouldn't occur if we run this however.
 */
function init_app_locale(){

	// echo '<pre>' . print_r( $_COOKIE, true ) . '</pre>';

	// check session
	$str = gp_if_set( $_SESSION, APP_LOCALE_SESSION_COOKIE_INDEX );

	if ( $str && app_is_locale_valid( $str ) ) {
		return;
	}

	// check cookie
	$str = gp_if_set( $_COOKIE, APP_LOCALE_SESSION_COOKIE_INDEX );

	if ( $str && app_is_locale_valid( $str ) ) {
		return;
	}

	// *** Avoid geo-locale by IP for crawlers ***
	// geo-locate by IP requires http request and will make every page load very slowly for search engines
	// who might not be sending in cookies to identify their session.
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';

	$CrawlerDetect = new CrawlerDetect;

	// avoid geo-locate by IP for crawlers as this will trigger http request and make
	// every single page load very slowly.
	if ( $CrawlerDetect->isCrawler( $ua ) ) {
		app_set_locale( app_get_default_locale() );
		$_SESSION['is_crawler'] = true;
		return;
	} else {
		// we shouldn't actually need to use this, but there are places
		// where we dump the entire session array (not in production)...
		// so basically if this is ever true then we'll have a clue that our crawler
		// detection might not be working
		$_SESSION['is_crawler'] = false;
	}


	// we want to avoid using app_get_ip_country_code() as it will fopen a file
	// via an external URL which takes time for first time visitors (or those without the cookie or session values set)
	if ( DISABLE_LOCALES ) {
		app_set_locale( app_get_default_locale() );
		return;
	}

	// check from IP address.
	// we never want to run this more than once per user if possible..
	// or to be more specific, once per user per session
	$code = app_get_ip_country_code( false );

	if ( $code && app_is_locale_valid( $code ) ) {
		$str = $code;
		// log_data( $code, 'set-code-ip-country' );
	} else {
		$str = app_get_default_locale();
	}

	// sets the locale in session/cookie or where ever, for next time
	app_set_locale( $str );
}

/**
 * will store this string in transactions probably.
 * may also display this on the front-end
 */
function app_get_currency(){

	$l = app_get_locale();
	$ret = '';
	switch( $l ) {
		case APP_LOCALE_CANADA:
			$ret = 'CAN';
			break;
		case APP_LOCALE_US:
			$ret = 'USD';
			break;
	}
	return $ret;
}

/**
 * this basically helps to turn an if, else if, if, .. into an if/else.
 *
 * use this when you want to ensure the locale is canada or US, and do a simple
 * if statement, without having to worry about the third case, where it's not
 * canada, and not U.S.
 *
 * @param null $locale
 *
 * @return bool
 */
function app_is_locale_canada_otherwise_force_us( $locale = null ){

	$locale = $locale !== null ? $locale : app_get_locale();

	if ( $locale === APP_LOCALE_CANADA ) {
		return true;
	}

	if ( $locale === APP_LOCALE_US ) {
		return false;
	}

	throw_dev_error( 'invalid locale.' );
	return false;
}

/**
 * 2 character country codes in all caps.. 'CA' or 'US'
 *
 * @return string
 */
function app_get_locale(){

	// may be disabling the U.S. locale at launch time..
	if ( DISABLE_LOCALES ) {
		return app_get_default_locale();
	}

	$str = gp_if_set( $_SESSION, APP_LOCALE_SESSION_COOKIE_INDEX );

	if ( $str && app_is_locale_valid( $str ) ) {
		return $str;
	}

	$str = app_get_default_locale();
	return $str;
}

/**
 * Do not call this every page load, it uses an online geoip database,
 * and requires curl which takes time. in fact, i'll probably put logic in here
 * to only ever call this once per session.
 *
 * should return 2 character country code of user.
 *
 * @return bool|mixed|string
 */
function app_get_ip_country_code( $bypass_session_check = false ){

	if ( ! $bypass_session_check ) {

		if ( gp_if_set( $_SESSION, 'country_code_fetched', false ) ) {
			return false;
		}

		$_SESSION['country_code_fetched'] = true;
	}

	$ip = app_get_ip_address();

	// this handles invalid $ip also
	$info = get_ip_info( $ip );

	$country_code = $info ? gp_if_set( $info, 'country_code', '' ) : '';
	$country_code = gp_test_input( $country_code );
	$country_code = strtoupper( $country_code );

	return $country_code;
}

/**
 * WARNING: Uses CURL to get data from external website.
 *
 * DO NOT RUN THIS EVERY PAGE LOAD PLEASE. Thanks...
 *
 * Currently, we MAY run it for first time visitors, and then set a cookie to avoid
 * running it (hopefully) ever again for the same person.
 *
 * @param null   $ip
 * @param string $purpose
 * @param bool   $deep_detect
 *
 * @return array|null|string
 */
function get_ip_info($ip = NULL, $purpose = "location", $deep_detect = TRUE) {
	$output = NULL;
	if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
		$ip = @$_SERVER["REMOTE_ADDR"];
		if ($deep_detect) {
			if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
				$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
				$ip = $_SERVER['HTTP_CLIENT_IP'];
		}
	}
	$purpose    = str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));
	$support    = array("country", "countrycode", "state", "region", "city", "location", "address");
	$continents = array(
		"AF" => "Africa",
		"AN" => "Antarctica",
		"AS" => "Asia",
		"EU" => "Europe",
		"OC" => "Australia (Oceania)",
		"NA" => "North America",
		"SA" => "South America"
	);
	if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {

		$file = 'http://www.geoplugin.net/json.gp?ip=' . $ip;

		// would require allow_url_fopen I think
		// $ipdat = @json_decode(file_get_contents($file));

		$ipdat = @json_decode( file_get_contents_curl( $file ) );

		if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2) {
			switch ($purpose) {
				case "location":
					$output = array(
						"city"           => @$ipdat->geoplugin_city,
						"state"          => @$ipdat->geoplugin_regionName,
						"country"        => @$ipdat->geoplugin_countryName,
						"country_code"   => @$ipdat->geoplugin_countryCode,
						"continent"      => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
						"continent_code" => @$ipdat->geoplugin_continentCode
					);
					break;
				case "address":
					$address = array($ipdat->geoplugin_countryName);
					if (@strlen($ipdat->geoplugin_regionName) >= 1)
						$address[] = $ipdat->geoplugin_regionName;
					if (@strlen($ipdat->geoplugin_city) >= 1)
						$address[] = $ipdat->geoplugin_city;
					$output = implode(", ", array_reverse($address));
					break;
				case "city":
					$output = @$ipdat->geoplugin_city;
					break;
				case "state":
					$output = @$ipdat->geoplugin_regionName;
					break;
				case "region":
					$output = @$ipdat->geoplugin_regionName;
					break;
				case "country":
					$output = @$ipdat->geoplugin_countryName;
					break;
				case "countrycode":
					$output = @$ipdat->geoplugin_countryCode;
					break;
			}
		}
	}
	return $output;
}

/**
 * Ensures an ip address is both a valid IP and does not fall within
 * a private network range.
 *
 * @param $ip
 *
 * @return bool
 */
function validate_ip($ip) {

	if (strtolower($ip) === 'unknown')
		return false;

	// generate ipv4 network address
	$ip = ip2long($ip);

	// if the ip is set and not equivalent to 255.255.255.255
	if ($ip !== false && $ip !== -1) {
		// make sure to get unsigned long representation of ip
		// due to discrepancies between 32 and 64 bit OSes and
		// signed numbers (ints default to signed in PHP)
		$ip = sprintf('%u', $ip);
		// do private network range checking
		if ($ip >= 0 && $ip <= 50331647) return false;
		if ($ip >= 167772160 && $ip <= 184549375) return false;
		if ($ip >= 2130706432 && $ip <= 2147483647) return false;
		if ($ip >= 2851995648 && $ip <= 2852061183) return false;
		if ($ip >= 2886729728 && $ip <= 2887778303) return false;
		if ($ip >= 3221225984 && $ip <= 3221226239) return false;
		if ($ip >= 3232235520 && $ip <= 3232301055) return false;
		if ($ip >= 4294967040) return false;
	}
	return true;
}

/**
 * @return mixed
 */
function get_ip_address() {

	// check for shared internet/ISP IP
	if (!empty($_SERVER['HTTP_CLIENT_IP']) && validate_ip($_SERVER['HTTP_CLIENT_IP'])) {
		return $_SERVER['HTTP_CLIENT_IP'];
	}

	// check for IPs passing through proxies
	if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		// check if multiple ips exist in var
		if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') !== false) {
			$iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			foreach ($iplist as $ip) {
				if (validate_ip($ip))
					return $ip;
			}
		} else {
			if (validate_ip($_SERVER['HTTP_X_FORWARDED_FOR']))
				return $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
	}
	if (!empty($_SERVER['HTTP_X_FORWARDED']) && validate_ip($_SERVER['HTTP_X_FORWARDED']))
		return $_SERVER['HTTP_X_FORWARDED'];
	if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && validate_ip($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
		return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
	if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && validate_ip($_SERVER['HTTP_FORWARDED_FOR']))
		return $_SERVER['HTTP_FORWARDED_FOR'];
	if (!empty($_SERVER['HTTP_FORWARDED']) && validate_ip($_SERVER['HTTP_FORWARDED']))
		return $_SERVER['HTTP_FORWARDED'];

	// return unreliable ip since all else failed
	return @$_SERVER['REMOTE_ADDR'];
}

/**
 * the method that our app uses to get the ip address, which may change over time
 * if some are more reliable than others.
 *
 * @return mixed
 */
function app_get_ip_address(){
	return get_ip_address();
}