<?php
/**
 * see also: CORE_DIR . '/inc/functions-users.php'
 */

///**
// * @param $user_id
// */
//function make_user_cookie_auth_key( DB_User $user, $duration = 7200 ) {
//
//	$expiry = time() + $duration;
//	$user_id = $user->get_primary_key_value( $user );
//
//	// since we store $auth in the database it practically doesn't matter what it is as long
//	// as its sufficiently random and long enough. of course, we'll ensure its unique as well, but
//	// if there was a 1 in a million chance of it being the same for 2 users, the users would still
//	// have to determine another users user_id and their expiry time in order to re-use the cookie.
//	$secret = $user_id + $expiry + 324861723723;
//	$auth = base64_encode( sha1( $secret . uniqid(  $user_id ) ) );
//	$auth = '';
//
//	$arr = array(
//		$user_id,
//		$expiry,
//		$auth
//	);
//
//	// parsing string uses explode on -, therefore, we must make sure that - is not used
//	// in any of the values
//	$str = implode( '-', $arr );
//}
//
///**
// * @param $key
// */
//function parse_user_cookie_auth_key( $key ) {
//
//	$key = gp_force_singular( $key );
//
//	$arr = explode( '-', $key );
//	$user_id = gp_if_set( $arr, 0, '' );
//	$expiry = gp_if_set( $arr, 1, '' );
//	$auth = gp_if_set( $arr, 2, '' );
//
//	if ( ! $user_id || ! $expiry || ! $auth ) {
//		return false;
//	}
//
//	// code that uses this function expects these array indexes to be set, unless
//	// the function returns false.
//	$ret = [];
//	$ret['user_id'] = $user_id;
//	$ret['expiry'] = $expiry;
//	$ret['auth'] = $auth;
//
//	return $ret;
//}

/**
 * Going to try to keep this as simple as possible. If you want to know if the function was successful,
 * you should call cw_is_user_logged_in() before and after calling this.
 */
function cw_make_user_logged_out(){

	// this is an array.. we put everything related to login in here, so that when
	// we unset it, it removes everything, and we don't have to come back here and change how we log users out.
	if ( isset( $_SESSION['logged_in_user'] ) ) {
		unset( $_SESSION['logged_in_user'] );
	}

	if ( isset( $GLOBALS['logged_in_user'] ) ) {
		unset( $GLOBALS['logged_in_user'] );
	}
}

/**
 * this function name is long but its important that you understand that this could grab a logged in user from PHP memory
 * and then make that user logged in again. This means that if immediately before calling this function, you updated the users
 * password and/or unique login counter and/or tried to log them out everywhere, then this function would actually log them back in
 * using the updated credentials... so... its pretty tricky. But, use this when you update user values that should NOT make them logged out.
 *
 */
//function re_sync_user_from_database_then_log_out_and_back_in(){
//
//	$user = cw_get_logged_in_user();
//
//	if ( $user ) {
//		cw_make_user_logged_out();
//		cw_make_user_logged_in( $user );
//		return true;
//	}
//	return false;
//}

/**
 * This does a few things:
 *
 * 1. Sets up $_SESSION data so that user *might* remain logged in (see #2) as long as their session persists.
 * 2. Uses a $_SESSION token which is a derivative of their hashed password, so that if their password changes,
 * then previous session IDs won't render them logged in.
 * 3. Sets up the DB_User object in $GLOBALS so that cw_get_logged_in_user() doesn't hit the database every time.
 * 4. Will call cw_make_user_logged_out() is $u is not a valid user ID or DB_User object.
 *
 *
 * @param $u - DB_User or $user_id
 *
 * @return bool
 */
function cw_make_user_logged_in( $u ){

	// have to get the user instance (if its not passed in) in order to log a user in
	// we could just store the ID in theory, but i'm trying to satisfy 2 conditions...
	// 1. cache the user object in PHP memory to avoid extra database calls to get the current user over and over
	// 2. be aware that the state of the user can change through the script.
	// 3. (less important).. now we can now do hacky things like create an invalid DB_User object, make that "object" the logged in user
	// and then trick our product reviews system into allowing us to insert many random product reviews in a very large loop (all with invalid user IDs!)
	// this only works with dynamic "current users" during script execution, because the insert fails if a user has already rated a product.

	if ( $u instanceof DB_User ) {
		$user = $u;
	} else {
		$user = DB_User::create_instance_via_primary_key( $u );
	}

	// If an invalid user or user_id was passed in, lets make the user logged out.
	// have to of course be careful tho.. cw_make_user_logged_out() can use cw_make_user_logged_in()
	// or the other way around... but not both. So.. in our case.. cw_make_user_logged_in( $invalid )
	// will trigger cw_make_user_logged_out().
	if ( ! $user || ! $user instanceof DB_User ) {
		cw_make_user_logged_out();
		return false;
	}

	$token = generate_session_auth_string_from_user( $user );

	// two things are required for $_SESSION storage
	$_SESSION['logged_in_user']['user_id'] = $user->get_primary_key_value();
	$_SESSION['logged_in_user']['token'] = $token;

	// setup the user in $GLOBALS so that cw_get_logged_in_user() doesn't hit the
	// database when its (almost inevitably) called after this.
	// In other words, this is PHP memory cache. In some cases this is an issue like when updating a users profile,
	// then subsequent calls of cw_get_logged_in_user() will return an outdated DB_User object for the duration of the script,
	// so in this case, we can simply make the user logged out then back in again (or just use the function
	// re_sync_user_from_database_then_log_out_and_back_in() )
	$GLOBALS['logged_in_user']['db_user'] = $user;
	return true;
}

/**
 * @param $thing
 *
 * @return bool
 */
function cw_is_user_valid( $thing ) {
	// don't do any database stuff here... may be called many
	// times per script execution
	$ret = ( $thing instanceof DB_User );
	return $ret;
}

/**
 * Takes the user ID, hashed password, and 'unique_login_counter' and generates a random string
 * that is meant to be stored in $_SESSION. Even though the string is stored in $_SESSION, we'll still
 * obscure it a little bit in case someone uses this function totally incorrectly and sends this in a COOKIE or
 * otherwise reveals it. The point here, is that any time any of these 3 values change, all existing login should be
 * made invalid. The user ID of course does not change. The password may change, but the issue is that
 * the password can change back to what it previously was so its not enough on its own.
 * This is why we need the unique login counter...  by changing it to something that it never was
 * at any point in the past (ie. +1) we invalidate all existing login's that are using the php session ID (ie.
 * all login's that exist as of the time i'm writing this.)
 *
 * @param $user_id
 * @param $hashed_password
 */
function generate_session_auth_string_from_user( DB_User $user ) {

	$hashed_password = $user->get( 'password' );

	$unique_login_counter = $user->get( 'unique_login_counter' );
	$unique_login_counter = $unique_login_counter ? $unique_login_counter : 1;

	$user_id = $user->get_primary_key_value();

	$hashed_password = gp_force_singular( $hashed_password );
	$user_id = (int) $user_id;

	// we just need to make sure we can cut it in half and still have some random characters left
	// to work with. the length otherwise isnt super important
	if ( strlen( $hashed_password ) < 12 ) {
		Debug::add( 'invalid hashed password in generating an auth token' );
		return false;
	}

	// take half the hashed password
	$half_hashed_password = substr( $hashed_password, 0, floor( strlen( $hashed_password ) / 2 ) );

	// once again, this goes into $_SESSION, it doesn't need to be secure. It just needs to change when the users
	// password changes. We have simply obscured the original hashed password as an extra safety measure in case
	// someone uses this function incorrectly one day.
	$not_cryptographically_secure_random_string = md5( $half_hashed_password . $user_id . $unique_login_counter );
	return $not_cryptographically_secure_random_string;
}

/**
 * You can call this function once to take the user ID from $_SESSION and store the user object in script globals.
 *
 * After that, the function will rely on $GLOBALS and ignore $_SESSION (until the end of the script of course). In other words
 * don't screw around with $_SESSION or $GLOBALS directly to do logged in stuff. this should be 100% obvious. Just use this function
 * basically, and don't worry about it.
 *
 * This function should hopefully only be dependant on other functions found within this file. This is the goal anyways. We only need
 * a few related functions, and so long as are in sync, then it doesn't really matter what happens inside of them.
 *
 * @return bool|DB_User|null
 */
function cw_get_logged_in_user(){

	$user = isset( $GLOBALS['logged_in_user']['db_user'] ) ? $GLOBALS['logged_in_user']['db_user'] : false;

	if ( $user instanceof DB_User ) {

		if ( $user->is_locked() ) {
			cw_make_user_logged_out();
			return false;
		}

		return $user;
	}

	// now check session for the ID and token, and cache user in $GLOBALS for the next time we use this function.
	$user_id = isset( $_SESSION['logged_in_user']['user_id'] ) ? $_SESSION['logged_in_user']['user_id'] : '';

	if ( $user_id ) {
		$user = DB_User::create_instance_via_primary_key( $user_id );

		// user found with ID ??
		if ( $user && $user instanceof DB_User ) {

			if ( $user->is_locked() ) {
				cw_make_user_logged_out();
				return false;
			}

			// the token makes the user automatically logged out when the password changes, but also
			// lets us change the login auth counter to force them to be logged out everywhere as well
			$token = isset( $_SESSION['logged_in_user']['token'] ) ? $_SESSION['logged_in_user']['token'] : '';

			if ( $token && generate_session_auth_string_from_user( $user ) === $token ) {
				$GLOBALS['logged_in_user']['db_user'] = $user;
				return $user;
			} else {

				// if the token match failed, we should remove all traces of a user being logged in.
				cw_make_user_logged_out();
			}
		}
	}

	return false;
}

define( "USER_ROLE_ADMIN", 'admin' );
define( "USER_ROLE_WHOLESALE", 'not_in_use' );

/**
 * @return bool|DB_Table|DB_User|null
 */
function cw_is_user_logged_in(){
	$user = cw_get_logged_in_user();
	$ret = ( $user );
	return $ret;
}

/**
 * @return bool
 */
function cw_is_admin_logged_in(){
	$user = cw_get_logged_in_user();
	$ret = $user && $user->is_administrator();
	return $ret;
}

/**
 *
 */
function cw_get_logged_in_user_array(){
	$user = cw_get_logged_in_user();
	$ret = $user ? $user->to_array() : false;
	return $ret;
}
