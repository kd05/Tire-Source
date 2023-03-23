<?php
/**
 * A few functions that need to be loaded early on
 */



/**
 * @param Exception $e
 */
function app_exception_handler( $e ) {

	// echo '<pre>' . print_r( $e, true ) . '</pre>';
	// we could wrap this segment of code in a try/catch block, but if the code below throws
	// an exception, we're basically not going to be able to log it, and what else can we do other than
	// show an empty screen or w/e.

	// this might get converted to json and will be ugly but the info will be there
	$data = array(
		'message' => $e->getMessage(),
		'line' => $e->getLine(),
		'file' => $e->getFile(),
		'code' => $e->getCode(),
		'trace' => $e->getTraceAsString(),
		'previous' => $e->getPrevious() ? print_r( $e->getPrevious() ) : null,
	);

    // in case of very early exceptions, check that log_data exists first.
	if ( function_exists( 'log_data' ) ) {
		log_data( $data, 'exceptions', true, true, false );
	}

	// do nothing in production
	if ( IN_PRODUCTION ) {
		echo 'An error has occurred please try a different page.';
		exit;
	} else {
        // when not in production
        echo '<pre>' . print_r( $data, true ) . '</pre>';
        exit;
    }
}

/**
 * Catches failed assertions, although, depends on some other config/settings.
 *
 * @param        $script
 * @param        $line
 * @param string $code
 * @param string $msg
 */
function app_assert_handler($script, $line, $code = '', $msg = ''){

	$msg = 'Failed Assertion [code/msg/script/line][' . $code . '][' . $msg  . '][' . $script . '][' . $line . ']';

	$e     = new Exception();
	$trace = $e->getTraceAsString();
	$msg .= ' ... trace ... ' . $trace;

	if ( IN_PRODUCTION ) {
		log_data( $msg, 'app-assert-handler', true, true, true );
		exit;
	} else {
		echo '<pre>' . print_r( $msg, true ) . '</pre>';
		exit;
	}
}

/**
 * Update: we may use the assert_options() function to register an assertion callback.
 *
 * @param $assertion
 * @param $msg
 */
//function app_assert( $assertion, $msg ) {
//	if ( ( $assertion ) == false ) {
//		throw_dev_error( $msg );
//	}
//}

/**
 * @return int|mixed
 */
function time_diff(){
	global $script_start;
	if ( $script_start === null ) {
		$script_start = microtime( true );
		return 0;
	}
	$now = microtime( true );
	return $now - $script_start;
}

/**
 * @return array
 */
function get_peak_mem_formatted(){

    $format = function( $bytes ){
        return number_format( (float) $bytes, 0, "", "," );
    };

    return [ $format( memory_get_peak_usage() ), $format( memory_get_peak_usage( true ) ) ];
}

/**
 * @return array
 */
function get_mem_formatted() {

    $format = function( $bytes ){
        return number_format( (float) $bytes, 0, "", "," );
    };

    return [ $format( memory_get_usage() ), $format( memory_get_usage( true ) ) ];
}

/**
 * @param string $context
 */
function start_time_tracking( $context = 'time' ) {

	global $time_tracking;
	$time_tracking = $time_tracking === null ? array() : $time_tracking;

	$start = microtime( true );
	$time_tracking[$context]['start'] = $start;
}

/**
 * @param string $context
 * @return float|string|null
 */
function end_time_tracking( $context = 'time' ) {

	global $time_tracking;
	$time_tracking = $time_tracking === null ? array() : $time_tracking;

	$start = isset( $time_tracking[$context]['start'] ) ? $time_tracking[$context]['start'] : null;

	if ( $start === null ) {
		return null;
	}

	$end = microtime( true );
	return $end - $start;
}

/**
 * Make sure to call this one time at the very start of your script
 * so that you can get accurate numbers on things that compare with
 * previous states, like time_diff() for example.
 *
 * @return array
 */
function get_time_and_mem_usage(){
	return array(
		'mem' => memory_get_usage(),
		'peak_mem' => memory_get_peak_usage(),
		'time' => time_diff(),
	);
}

/**
 * timezone ? depends on use case...
 *
 * @return string
 */
function get_database_date_format(){
	$format = 'Y-m-d H:i:s';
	return $format;
}

/**
 * @return false|string
 */
function get_database_date_now(){
	$date = date( get_database_date_format(), gp_time() );
	return $date;
}

/**
 * @param bool $time
 *
 * @return string
 */
function get_nice_date_format( $time = false ){
	if ( $time ) {
		return 'M j Y g:ia';
	} else {
		return 'M j Y';
	}
}

/**
 * Get current time formatted for database storage.
 *
 * @return string
 * @throws Exception
 */
function get_date_formatted_for_database(){

	$dt = new DateTime( 'now', new DateTimeZone( 'America/Toronto' ) );

	// im not sure whether to include timezone in the format
	$ret = $dt->format( get_database_date_format() );
	return $ret;
}

/**
 * @return bool|mixed
 */
function cw_is_admin(){
	return get_global( 'is_admin' );
}