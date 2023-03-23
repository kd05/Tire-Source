<?php

/**
 * For lack of a better name... returns the result without "?/"
 *
 * Should ensure you get:
 * example.url/com/get_stuff?stuff=123
 *
 * and not:
 *
 * example.url/com/get_stuff/?stuff=123
 *
 * @param array  $args
 * @param string $url
 *
 * @return string
 */
//function cw_add_query_arg_alt( $args = [], $url = '' ){
//	return cw_add_query_arg( $args, $url, true );
//}

/**
 *
 * Add query arguments to a URL.
 *
 * The URL may already contain some arguments.
 *
 * Preserves trailing slash in URL passed in if present.
 *
 * @param array  $args
 * @param string $url
 *
 * @return string
 */
function cw_add_query_arg( $args = array(), $url = '' ){

    $parts = parse_url( $url );

    $url_params = [];
    if(isset($parts[ 'query' ])) {
       parse_str( @$parts[ 'query' ], $url_params );
    }

    $_args = array_merge( $url_params, $args );

    // $url before any question mark
    $ret = rtrim( @explode( '?', $url )[ 0 ], '?' );

    if ( $_args ) {
        $ret .= "?" . http_build_query( $_args );
    }

    return $ret;
}

/**
 * Kind of like $base . '/' . $add, except that base and add could both have query args.
 *
 * @see cw_add_query_arg()
 *
 * @param        $base
 * @param string $add
 */
//function gp_combine_url( $base, $add = '' ) {
//
//	$b = parse_url( $base );
//	$a = parse_url( $add );
//
//	//	$a_scheme = isset( $a['scheme'] ) ? $a['scheme'] ) : ''; // ie. http
//	//	$a_host = isset( $a['host'] ) ? $a['host'] ) : ''; // ie. example.com
//	$a_path  = isset( $a[ 'path' ] ) ? $a[ 'path' ] : ''; // ie. /some-page/
//	$a_query = isset( $a[ 'query' ] ) ? $a[ 'query' ] : ''; // ie. somevar=23&othervar=50
//
//	$b_scheme = isset( $b[ 'scheme' ] ) ? $b[ 'scheme' ] : '';
//	$b_host  = isset( $b[ 'host' ] ) ? $b[ 'host' ] : '';
//	$b_query = isset( $b[ 'query' ] ) ? $b[ 'query' ] : '';
//	$b_path  = isset( $b[ 'path' ] ) ? $b[ 'path' ] : '';
//
//	$r = '';
//	if ( $b_scheme ) {
//		$r .= $b_scheme . '://';
//	}
//
//	if ( $b_host ) {
//		$r .= $b_host;
//	}
//
//	if ( $b_path ) {
//		$b_path = trim( $b_path, '/' );
//		$r      = trim( $r, '/' );
//		$r      .= '/' . $b_path . '/';
//	}
//
//	if ( $a_path ) {
//		$a_path = trim( $a_path, '/' );
//		$r      = trim( $r, '/' );
//		$r      .= '/' . $a_path . '/';
//	}
//
//	// add query args from both parts
//	$query_string = '';
//	$query_string .= $b_query ? $b_query : '';
//	$query_string .= $a_query ? $a_query : '';
//
//	if ( $query_string ) {
//		$r = trim( $r, '/' );
//		$r .= '?' . $query_string;
//	}
//
//	return $r;
//}

/**
 * Send a POST requst using cURL
 *
 * @param string $url to request
 * @param array $post values to send
 * @param array $options for cURL
 * @return string
 */
function curl_post($url, array $post = NULL, array $options = array())
{
	$defaults = array(
		CURLOPT_POST => 1,
		CURLOPT_HEADER => 0,
		CURLOPT_URL => $url,
		CURLOPT_FRESH_CONNECT => 1,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_FORBID_REUSE => 1,
		CURLOPT_TIMEOUT => 4,
		CURLOPT_POSTFIELDS => http_build_query($post)
	);

	$ch = curl_init();
	curl_setopt_array($ch, ($options + $defaults));
	if( ! $result = curl_exec($ch))
	{
		trigger_error(curl_error($ch));
	}
	curl_close($ch);
	return $result;
}

/**
 * Send a GET request using cURL
 *
 * @param string $url to request
 * @param array $get values to send
 * @param array $options for cURL
 * @return string
 */
function curl_get($url, array $get = NULL, array $options = array())
{
	$defaults = array(
		CURLOPT_URL => $url. (strpos($url, '?') === FALSE ? '?' : ''). http_build_query($get),
		CURLOPT_HEADER => 0,
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_TIMEOUT => 4
	);

	$ch = curl_init();
	curl_setopt_array($ch, ($options + $defaults));
	if( ! $result = curl_exec($ch))
	{
		trigger_error(curl_error($ch));
	}
	curl_close($ch);
	return $result;
}
