<?php

/**
 * This is a database caching which we'll use primarily for CURL requests which
 * we would like to avoid on page load, or hundreds of time in the same user session for example
 * so we'll store the results of those requests temporarily in a table.
 */

/**
 * The object cache is simply used in addition to our database caching functions
 * to make sure we're not hitting the database twice in one script.
 *
 * Sometimes we'll call a function that checks database cache many times per request.
 * But perhaps a more important issue is reducing the chance if data inconsistency when
 * calling those functions more than once.
 *
 * Class PHP_Object_Cache
 */
Class PHP_Object_Cache{

	protected static $cache;

	/**
	 * PHP_Object_Cache constructor.
	 */
	public function __construct(){}

	/**
	 * @param      $key
	 * @param bool $default
	 *
	 * @return bool
	 */
	public static function get( $key, $default = false ) {
		if ( is_array( $key ) ) {
			$key = implode( '_', $key );
		}
		return isset( self::$cache[$key] ) ? self::$cache[$key] : $default;
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public static function exists( $key ) {
		if ( is_array( $key ) ) {
			$key = implode( '_', $key );
		}
		return isset( self::$cache[$key] );
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public static function set( $key, $value ) {
		if ( is_array( $key ) ) {
			$key = implode( '_', $key );
		}
		self::$cache[$key] = $value;
	}

	/**
	 *
	 */
	public static function empty_cache(){
		self::$cache = array();
	}
}

/**
 *
 * @param  array|string  $key - if this is an array, it should have indexes of only 0 and 1
 * @param null $default
 *
 * @return array|bool|mixed|null|string
 * @throws Exception
 */
function gp_cache_get( $key, $default = null, $args = array() ) {

	// nevermind this..
	// $obj_to_array = gp_if_set( $args, 'obj_to_array', true );

	if ( PHP_Object_Cache::exists( $key ) ) {
		return PHP_Object_Cache::get( $key, $default );
	}

	if ( is_array( $key ) ) {
		$k1 = gp_if_set( $key, 0 );
		$k2 = gp_if_set( $key, 1 );
	} else if ( is_string( $key ) ){
		$k1 = $key;
		$k2 = false;
	} else {
		Throw new Exception( 'invalid cache key (get): ' . get_var_dump( $key ) );
	}

	$k1 = gp_test_input( $k1 );
	$k2 = gp_test_input( $k2 );

	$db = get_database_instance();
	$q = '';
	$q .= 'SELECT * ';
	$q .= 'FROM ' . DB_cache . ' ';
	$q .= 'WHERE 1 = 1 ';
	$q .= 'AND cache_key = ? ';
	if ( $k2 ) {
		$q .= 'AND cache_key_2 = ? ';
	}
	$q .= '';
	$q .= 'ORDER BY cache_time DESC ';
	$q .= ';';

	$st = $db->pdo->prepare( $q );
	$st->bindParam( 1, $k1 );

	if ( $k2 ) {
		$st->bindParam( 2, $k2 );
	}

	Debug::add( array( $st->queryString, $k1, $k2 ), 'cache_get');

	$st->execute();
	$r = $st->fetch(); // just fetch() NOT fetchAll()

	if ( empty( $r ) ) {
		Debug::add( 'empty query result, returning default value', 'CACHE' );
		Debug::add( func_get_args() );
		return $default;
	}

	$value = gp_if_set( $r, 'cache_value' );
	$expiry = gp_if_set( $r, 'expiry' );
	$time = gp_time();

	// make sure we check expiry exists here so we don't run gp_cache_clear_expired a million times per page load
	// we dont clear all expired every time we miss finding a cached object, we clear expired when it becomes known
	// there is at least one expired cache row in the database.
	if ( $expiry && $time > $expiry ) {
		gp_cache_clear_expired();
		Debug::add( 'cache cleared, returning default', 'CACHE' );
		// careful not to return false, as that could be a valid stored database value
		return $default;
	}

	$value = trim( $value );
	if ( ! $value ) {
		return $value;
	}

	// I prefer to use json here for a few reasons. php serialize may work..
	$method = 'json'; // php for serialize, json for json deocde
	$value = gp_db_decode( $value, $method );

//	if ( $obj_to_array ) {
//		if ( $value instanceof stdClass ) {
//			$value = get_object_vars( $value );
//		}
//	}

	// in case the string stored was too long for database cell

	if ( $method === 'json' ) {
		if ( json_last_error() === JSON_ERROR_NONE ) {
//			Debug::add( 'no json last error, returning value', 'CACHE' );
//			Debug::add( get_var_dump( $value ), 'CACHE' );
			// var_dump( $value );
			return $value;
		} else {
			Debug::add( 'json decoding error, returning default', 'CACHE' );
			return $default;
		}
	}

	// Debug::add(array( 'end of function return value', $value ), 'CACHE' );
	return $value;
}

/**
 *
 * @param        $key
 * @param        $value
 * @param int    $seconds
 *
 * @return mixed
 * @throws Exception
 */
function gp_cache_set( $key, $value, $seconds = 3600 ) {

	PHP_Object_Cache::set( $key, $value );

	if ( is_array( $key ) ) {
		$k1 = gp_if_set( $key, 0 );
		$k2 = gp_if_set( $key, 1 );
	} else if ( is_string( $key ) ){
		$k1 = $key;
		$k2 = false;
	} else {
		Throw new Exception( 'invalid cache key (set): ' . get_var_dump( $key ) );
	}

	Debug::add( array( $key, $value ), 'cache_set' );

	$db = get_database_instance();

	$time = gp_time();
	$expiry = $time + $seconds;

	$where = array(
		'cache_key' => $k1,
	);
	$where_format = array();

	$data = array(
		'cache_key' => $k1,
		'cache_value' => gp_db_encode( $value, 'json' ),
		'cache_time' => $time,
		'cache_expiry' => $expiry,
	);

	// non specified formats will be strings
	$data_format = array(
		'cache_time' => '%d',
		'cache_expiry' => '%d',
	);

	if ( $k2 ) {
		$data['cache_key_2'] = $k2;
		$where['cache_key_2'] = $k2;
	}

	$ex = $db->get( DB_cache, $where, $where_format );

	Debug::add( $ex, 'Existing Cache' );

	if ( $ex ) {
		$r = $db->update( DB_cache, $data, $where, $data_format, $where_format );
	} else {
		$r = $db->insert( DB_cache, $data, $data_format );
	}

	return $r;
}

/**
 * this deletes all rows in the cache table that are expired
 */
function gp_cache_clear_expired(){

	$time = gp_time();

	$db = get_database_instance();

	$p = array();
	$q = '';

	$q .= 'DELETE FROM ' . $db->cache . ' ';

	$q .= 'WHERE 1 = 1 ';

	// I guess I don't know SQL cuz this breaks things
	// $q .= 'AND cache_expiry NOT IN ( NULL, "", 0, -1 ) ';

	// the point is that if cache_expiry is empty, 0, or negative 1, it should not expire
	// we don't currently use cache that doesn't expire afaik, but I feel we should try to support it.
	$q .= 'AND cache_expiry >= 1 ';

	$q .= 'AND cache_expiry < :time ';
	$p[] = [ 'time', $time, '%d' ];

	$q .= ';';

	$result = $db->execute( $q, $p );
	Debug::add( 'CLEAR EXPIRED CACHE (' . $result . ')' );
	return $result;
}

/**
 * Check the time the cache was cleaned last.
 *
 * If its greater than CACHE_CLEAN_SECONDS seconds ago,
 * clean up cache items that have expired.
 *
 */
function gp_cache_check_last_clean(){

	$last_clean = gp_cache_get( 'last_clean', false );
	$last_clean = (int) $last_clean;

	Debug::add( $last_clean, 'LAST_CLEAN' );
	$time = gp_time();
	Debug::add( $last_clean, 'TIME' );

	if ( ! $last_clean ) {
		Debug::add( 'No last clean' );
		gp_cache_set( 'last_clean', $time );
	}

	if ( $last_clean && ( $last_clean + CACHE_CLEAN_SECONDS ) < $time ) {
		gp_cache_clear_expired();
		gp_cache_set( 'last_clean', $time );
	}
}

/**
 * Empties all database cache..
 */
function gp_cache_empty(){

	PHP_Object_Cache::empty_cache();

	$db = get_database_instance();
	$q = '';
	$q .= 'DELETE FROM ' . DB_cache;
	$q .= ';';
	$st = $db->pdo->prepare( $q );
	return $st->execute();
}