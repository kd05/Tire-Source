<?php

/**
 * Class Debug
 */
Class Debug {

	public static $items = array();

    /**
     * @param $msg
     * @param string $type
     */
	public static function add( $msg, $type = '' ) {

		$msg = gp_make_singular( $msg );

		$str = '';
		$str .= '[';
		$str .= get_function_called();
		$str .= ']';

		if ( $type ) {
			$str .= ' [' . $type . ']';
		}

		$str           .= ' ' . $msg;
		self::$items[] = $str;
	}

    /**
     * @param string $loc
     * @param array $extra
     */
	public static function log_time( $loc = '', $extra = array() ) {
		$arr = array_merge( $extra, get_time_and_mem_usage() );
		self::add( $arr, $loc );
	}

    /**
     * @param false $by_pass_in_production_check
     * @return string
     */
	public static function render( $by_pass_in_production_check = false ) {

		$op = '';
		if ( ! IN_PRODUCTION || $by_pass_in_production_check ) {
			if ( self::$items && is_array( self::$items ) ) {
				foreach ( self::$items as $kk => $item ) {
					$op .= '<pre>' . $kk . ': ' . gp_make_singular( $item ) . '</pre><br>';
				}
			}
		}

		return $op;
	}

}

/**
 * @return string
 */
function get_function_called() {

	$bt     = debug_backtrace();
	$caller = array_shift( $bt );

	$file = gp_if_set( $caller, 'file' );
	$line = gp_if_set( $caller, 'line' );

	$ret = $file . ': ' . $line;

	return $ret;
}