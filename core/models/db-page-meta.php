<?php

/**
 * @see DB_Page
 * @see Page_Meta
 * Class DB_Page_Meta
 */
Class DB_Page_Meta extends DB_Table{

	protected static $primary_key = 'meta_id';
	protected static $table = DB_page_meta;

	// db columns
	protected static $fields = array(
		'meta_id',
		'page_id',
		'meta_key',
		'meta_value',
	);

	protected static $db_init_cols = array(
		'meta_id' => 'int(11) unsigned NOT NULL auto_increment',
		'page_id' => 'int(11) unsigned',
		'meta_key' => 'mediumtext',
		'meta_value' => 'longtext',
	);

	// page_id is technically a foreign key in pages table, but
	// maybe we won't register it.... I would rather not have the constraints
	// in place
	protected static $db_init_args = array(
		'PRIMARY KEY (`meta_id`)',
	);

	/**
	 * @return array
	 */
	public function get_admin_archive_page_args() {
		return array(
			'do_delete_on_single' => true,
		);
	}
}

/**
 * Caches page IDs from page names.
 *
 * When using page meta functions, normally only the ID is required,
 * so this is useful. However, if you need only the page object, its
 * good to use DB_Page::get_instance_via_name()
 *
 * Class Page_Name_Cache
 */
Class Page_Name_Cache{

	/**
	 * Caches page IDs via page name
	 *
	 * @var array
	 */
	private static $cache = null;

	/**
	 *
	 */
	public function clear_cache(){
		self::$cache = null;
	}

	/**
	 * An alias function exists to access this.
	 *
	 * @see get_page_id
	 *
	 * @param $name
	 */
	public static function get_page_id_via_name( $name, $use_cache = true ) {

		if ( $use_cache && self::$cache !== null && isset( self::$cache[$name] ) ) {
			return self::$cache[$name];
		}

		$db = get_database_instance();

		$rows = $db->get(
			DB_pages,
			[
				'page_name' => gp_force_singular( $name ),
			],
			[
				'page_name' => '%s',
			]
		);

		// handle stdClass or array
		$first = gp_if_set( $rows, 0 );

		// dont default to null because the cache check above uses isset() which
		// will return false on null values. If no page is found for a given name,
		// we still want to cache that result.
		$page_id = $first ? gp_if_set( $first, 'page_id',  null ) : null;

		if ( $use_cache ) {
			self::$cache[$name] = $page_id;
		}

		return $page_id;
	}
}

/**
 * @param      $page_name
 * @param bool $use_cache
 *
 * @return bool|mixed
 */
function get_page_id( $page_name, $use_cache = true ) {
	return Page_Name_Cache::get_page_id_via_name( $page_name, $use_cache );
}

/**
 * Handles getting and setting of meta values in the page meta table with
 * a caching layer built in and some options for automatic serialization/de-serialization
 * in JSON.
 *
 * Not to be confused with DB_Page_Meta which is the database model for the same table.
 *
 * Class Page_Meta
 */
Class Page_Meta{

	/**
	 * Stores all post meta for a given page ID
	 *
	 * Not to be confused with meta ID which would be just
	 * one row in the meta table.
	 *
	 * @var array
	 */
	private static $cache = [];

	/**
	 *
	 */
	public static function cache_clear_all(){
		self::$cache = [];
	}

	/**
	 * @param $page_id
	 */
	public static function cache_clear_id( $page_id ) {
		if ( isset( self::$cache[$page_id] ) ) {
			unset( self::$cache[$page_id] );
		}
	}

	/**
	 * Returns true if at least one row was found and cached.
	 *
	 * In general, this return value is not super useful and not intended
	 * to be used often.
	 *
	 * @param      $page_id
	 * @param bool $reset
	 *
	 * @return bool
	 */
	public static function cache( $page_id, $reset = false ){

		if ( $reset ) {
			self::cache_clear_id( $page_id );
		} else if ( isset( self::$cache[$page_id] ) ) {
			// cache is already built
			return false;
		}

		$db = get_database_instance();

		$results = $db->get(
			DB_Page_Meta::get_table(),
			[
				'page_id' => $page_id,
			], [
				'page_id' => '%d',
			]
		);

		if ( $results ) {
			foreach ( $results as $result ) {
				self::$cache[$page_id][$result->meta_key] = $result->meta_value;
			}
			return true;
		}

		return false;
	}

	/**
	 * @param      $page_id
	 * @param bool $make_the_cache
	 * @param bool $reset_the_cache
	 *
	 * @return mixed|null
	 */
	public static function get_all_page_meta_via_id( $page_id, $make_the_cache = true, $reset_the_cache = false ) {

		if ( $make_the_cache ) {
			self::cache( $page_id, $reset_the_cache );
		}

		return isset( self::$cache[$page_id] ) ? self::$cache[$page_id] : null;
	}

	/**
	 * @param $page_id
	 * @param $meta_key
	 * @param $meta_value
	 */
	public static function get( $page_id, $meta_key, $check_cache = true ) {

		if ( ! $page_id ) {
			return null;
		}

		if ( $check_cache && isset( self::$cache[$page_id][$meta_key] ) ) {
			return self::$cache[$page_id][$meta_key];
		}

		// for now, always build the cache on the first call of this function.
		// later, if we need to optimize we can think about doing that.
		self::cache( $page_id, false );
		return isset( self::$cache[$page_id][$meta_key] ) ? self::$cache[$page_id][$meta_key] : null;
	}

	/**
	 * Tries to return an array of serialized data but if json decoding fails,
	 * will return the initial value. In the future, we could implement a check
	 * in get_page_meta() to try to determine if the value stored is JSON but,
	 * previous attempts to identify json strings didn't work out as flawlessly
	 * as hoped. Also, strings of numbers are also considered to be valid json.
	 *
	 * @param      $page_id
	 * @param      $meta_key
	 * @param bool $check_cache
	 *
	 * @return array|mixed|null
	 */
	public static function get_json_decoded( $page_id, $meta_key, $check_cache = true ) {
		$v = get_page_meta( $page_id, $meta_key, $check_cache );

		// important step for logic below.
		// we dont want to json encode 0 or "" for example which
		// might not throw errors but.. just not what we're expecting
		if ( ! $v ) {
			return $v;
		}

		// don't do a ternary operator here. For example... if we're
		// doing to call json_last_error() then its pretty important that
		// json_decode gets called.
		$_v = gp_make_object_into_array( json_decode( $v, true ) );
		return json_last_error() === JSON_ERROR_NONE ? $_v : $v;
	}

	/**
	 * @param $page_id
	 * @param $meta_key
	 * @param $meta_value
	 */
	public static function upsert( $page_id, $meta_key, $meta_value ) {

		$db = get_database_instance();

		// if you wanted to use php serialization you could always convert this beforehand, but
		// any non singular values will silently be converted to indexed arrays stored as json string.
		$store = gp_is_singular( $meta_value ) ? $meta_value : gp_db_encode( $meta_value, 'json' );

		// for now, always clear cache for this page ID on every instance of updating.
		// later on, if we need to optimize, we can do that..
		self::cache_clear_id( $page_id );

		// as of now I can't think of any reason why meta keys would not be
		// really simple strings containing only letters numbers and underscores,
		// therefore, we will always just sanitize meta key for now until there comes a time
		// when this causes some weird issue..
		return $db->upsert( DB_page_meta, [
			'page_id' => $page_id,
			'meta_value' => $store,
			'meta_key' => gp_test_input( $meta_key ),
		], [
			'page_id' => $page_id,
			'meta_key' => $meta_key,
		], [
			'page_id' => '%d',
			'meta_key' => '%s',
			'meta_value' => '%s',
		],[
			'page_id' => '%d',
			'meta_key' => '%s',
		] );
	}
}

/**
 * Alias for simpler usage.
 *
 * Note: get_page_id( $page_name ) may be helpful for the first parameter of this.
 *
 * @param      $page_id
 * @param      $meta_key
 * @param bool $check_cache
 *
 * @return null
 */
function get_page_meta( $page_id, $meta_key, $check_cache = true ) {
	return Page_Meta::get( $page_id, $meta_key, $check_cache );
}

/**
 * Will trim value before checking if its empty.
 *
 * @param      $page_id
 * @param      $meta_key
 * @param null $df
 * @param bool $check_cache
 * @return null
 */
function get_page_meta_with_fallback_if_empty( $page_id, $meta_key, $df = null, $check_cache = true ) {
	$v = get_page_meta( $page_id, $meta_key, $check_cache );
	return trim( $v ) ? $v : $df;
}

/**
 * Alias for simpler usage
 *
 * Note: get_page_id( $page_name ) may be helpful for the first parameter of this.
 *
 * @param      $page_id
 * @param      $meta_key
 * @param bool $check_cache
 *
 * @return array|mixed|null
 */
function get_page_meta_json_decoded( $page_id, $meta_key, $check_cache = true ) {
	return Page_Meta::get_json_decoded( $page_id, $meta_key, $check_cache );
}

/**
 * Alias for simpler usage
 *
 * Note: get_page_id( $page_name ) may be helpful for the first parameter of this.
 *
 * @param $page_id
 * @param $meta_key
 * @param $meta_value
 */
function update_page_meta( $page_id, $meta_key, $meta_value ) {
	return Page_Meta::upsert( $page_id, $meta_key, $meta_value );
}