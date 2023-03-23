<?php

/**
 * Stores a global array in static class property.
 *
 * Functions below will access it.
 *
 * Class Globals
 */
Class Globals{
	public static $data = array();
}

/**
 * @param $key
 * @param $value
 */
function set_global( $key, $value ) {
	if ( is_array( Globals::$data ) ) {
		Globals::$data[$key] = $value;
	}
}

/**
 * @param $key
 * @param $default
 *
 * @return bool|mixed
 */
function get_global( $key, $default = null ) {
	return gp_if_set( Globals::$data, $key, $default );
}

/**
 * @param $key
 *
 * @return bool
 */
function is_global_set( $key ) {
	$ret = is_array( Globals::$data ) && isset( Globals::$data[$key] );
	return $ret;
}

/**
 * Not sure if we'll need this.
 *
 * @return array
 */
function get_all_globals(){
	return Globals::$data;
}

/**
 * Not sure if we'll need this.
 *
 * @param $v
 */
function set_all_globals( $v ){
	Globals::$data = $v;
}
