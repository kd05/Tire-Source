<?php

/**
 * This function invalidates all previous forgot password URLs.
 *
 * @param $user
 */
function get_forgot_password_url_and_update_user( DB_User $user ) {

	$key = generate_forgot_password_key( $user );

	if ( ! $key ) {
		return false;
	}

	$db = get_database_instance();

	$update = $db->update( $db->users, array(
			'reset_key' => $key,
		), array(
			'user_id' => $user->get_primary_key_value(),
		) );

	if ( ! $update ) {
		return false;
	}

	$base = get_url( 'reset_password' );
	$ret  = cw_add_query_arg( array( 'key' => $key ), $base );

	return $ret;
}

/**
 * @param DB_User $user
 */
function generate_forgot_password_key( DB_User $user, $duration = 3600 ) {

	// in case of empty user... users could end up with same reset keys..
	if ( ! $user->get_primary_key_value() ) {
		throw_dev_error( 'User seems to be empty' );
	}

	$expiry = time() + $duration;

	// secret is a plain text string with maybe a lot of sensitive information in it
	$secret = '';

	// these 3 things don't actually do too much, since we are simply
	// making a random key and storing in the database. calling this function
	// twice with the same input should result in different output. be careful though,
	// some 'random' functions in php don't return different results in multiple function
	// calls within the same script, and close together.
	$secret .= $user->get_primary_key_value();
	$secret .= $user->get( 'email' );
	$secret .= $expiry; // i guess because why not

	global $forgot_password_counter;
	$forgot_password_counter = (int) $forgot_password_counter;
	$forgot_password_counter ++;
	$secret .= $forgot_password_counter;
	$secret = base64_encode( $secret );

	$auth = base64_encode( sha1( sha1( $secret ) ) );
	// replace non numeric since base 64 allows some stupid characters like +, -, and /
	$auth = preg_replace( '/[^a-zA-Z0-9]/', '5', $auth );

	$args = array(
		$expiry,
		$auth,
	);

	$ret = implode( '-', $args );

	return $ret;
}

Class Parsed_Forgot_Password_Key {

	public $key;
	public $expiry;
	public $auth;
	public $error;
	public $user;

	/**
	 * Parsed_Forgot_Password_Key constructor.
	 */
	public function __construct( $key ) {
		$this->key    = ''; // key is set in ->run()
		$this->expiry = '';
		$this->date   = '';
		$this->auth   = '';
		$this->error  = '';
		$this->user   = null;
		$this->run( $key );
	}

	/**
	 *
	 */
	public function run( $key ) {

		$key = gp_force_singular( $key );

		// key has no reason to have any special characters whatsoever
		$key = gp_test_input( $key );

		if ( ! $key ) {
			return false;
		}

		$this->key = $key;

		$arr = explode( '-', $key );

		// will have to update this if we add more info the the string
		if ( count( $arr ) !== 2 ) {
			$this->error = 'invalid';

			return false;
		}

		$ret          = [];
		$this->expiry = get_user_input_singular_value( $arr, 0 );
		$this->auth   = get_user_input_singular_value( $arr, 1 );

		$format         = 'Y-m-d G:i:s';
		$this->date_now = date( $format, time() );
		$this->time_now = time();
		$this->date     = date( $format, $this->expiry );

		// in reality this will normally be closer to 40 characters
		if ( strlen( $this->auth ) < 10 ) {
			$this->error = 'auth_length';

			return false;
		}

		if ( $this->expiry <= time() ) {
			$this->error = 'expired';

			return false;
		}

		$db      = get_database_instance();
		$p       = array();
		$q       = '';
		$q       .= 'SELECT * ';
		$q       .= 'FROM ' . $db->users . ' ';
		$q       .= 'WHERE reset_key = :reset_key ';
		$p[]     = [ 'reset_key', $key, '%s' ];
		$results = $db->get_results( $q, $p );
		$count   = count( $results );

		if ( $count === 0 ) {
			$this->error = 'no_user';

			return false;
		}

		/**
		 * this shouldn't happen in a million years
		 */
		if ( $count > 1 ) {
			$this->error = 'multiple_users';
			log_data( array(
				'ret' => $ret,
				'results' => $results,
			), 'multi-users-reset-key' );

			return false;
		}

		$row = gp_if_set( $results, 0 );

		/** @var DB_User|null $user */
		$user = DB_User::create_instance_or_null( $row );

		// sql gave us results, but somehow user isn't valid??? ... shouldn't happen
		if ( ! $user ) {
			$this->error = 'user_error';

			return false;
		}

		$this->user = $user;
	}

	/**
	 *
	 */
	public function get_error_message() {

		if ( $this->is_valid() ) {
			return '';
		}

		$url = get_url( 'forgot_password' );

		$msg = '<p>Invalid or expired key. Please try using the <a href="' . $url . '">forgot password form</a> again.</p>';

		return $msg;
	}

	/**
	 * @return bool
	 */
	public function is_valid() {
		if ( $this->key && $this->user && $this->user instanceof DB_User && $this->user->get_primary_key_value() ) {
			return true;
		}

		return false;
	}

}

/**
 * @param $str - this is almost certainly user input
 *
 * @return array|bool
 */
function parse_forgot_password_key( $str, &$error = '' ) {

	$str = gp_force_singular( $str );

	if ( ! $str ) {
		return false;
	}

	$arr = explode( '-', $str );

	// will have to update this if we add more info the the string
	if ( count( $arr ) !== 2 ) {
		$error = 'invalid';

		return false;
	}

	$ret             = [];
	$ret[ 'full' ]   = $str;
	$ret[ 'expiry' ] = gp_if_set( $arr, 0 );
	$ret[ 'auth' ]   = gp_if_set( $arr, 1 );

	// in reality this will normally be closer to 40 characters
	if ( $ret[ 'auth' ] < 10 ) {
		$error = 'auth_length';

		return false;
	}

	if ( $ret[ 'expiry' ] > time() ) {
		$error = 'expired';

		return false;
	}

	$db      = get_database_instance();
	$p       = array();
	$q       = '';
	$q       .= 'SELECT * ';
	$q       .= 'FROM ' . $db->users . ' ';
	$q       .= 'WHERE reset_key = :reset_key ';
	$p[]     = [ 'reset_key', $str, '%s' ];
	$results = $db->get_results( $q, $p );
	$count   = count( $results );

	if ( $count === 0 ) {
		$error = 'no_user';

		return false;
	}

	/**
	 * this shouldn't happen in a million years
	 */
	if ( $count > 1 ) {
		$error = 'multiple_users';
		log_data( array(
			'ret' => $ret,
			'results' => $results,
		), 'multi-users-reset-key' );

		return false;
	}

	$row = gp_if_set( $results, 1 );

	/** @var DB_User|null $user */
	$user = DB_User::create_instance_or_null( $row );

	// sql gave us results, but somehow user isn't valid??? ... shouldn't happen
	if ( ! $user ) {
		$error = 'user_error';

		return false;
	}

	// if we get to here, the key belongs to the user and is valid
	$ret[ 'valid' ] = true;
	$ret[ 'user' ]  = $user;

	return $ret;
}

/**
 * @param $p1
 * @param $p2
 */
function validate_two_passwords( $p1, $p2, &$msg = '' ) {

	// Do NOT trim passwords here. Trim them beforehand.
	$p1 = gp_force_singular( $p1 );
	$p2 = gp_force_singular( $p2 );

	if ( ! $p1 || ! $p2 ) {
		$msg = 'Please enter your password twice.';

		return false;
	}

	if ( $p1 !== $p2 ) {
		$msg = 'Passwords do not match.';

		return false;
	}

	// ensure passwords exist, then are the same before validating p1, otherwise
	// the error messages are stupid.

	$m1 = ''; // passed by reference
	if ( ! validate_password( $p1, $m1 ) ) {
		$msg = $m1;

		return false;
	}

	return true;
}

/**
 * @param $email
 */
function user_email_exists( $email ) {
	$ex = DB_User::create_instance_via_email( $email );
	if ( $ex ) {
		return true;
	}

	return false;
}

/**
 *
 * see also: * see also: CORE_DIR . '/user-authentication.php'
 *
 * @param      $email
 * @param      $password_1
 * @param      $password_2
 * @param      $first_name
 * @param      $last_name
 * @param bool $admin
 * @param bool $dry_run - do all validation without the update
 *
 * @return bool|string
 * @throws User_Exception
 */
function insert_user_from_user_input( $email, $password_1, $password_2, $first_name, $last_name, $admin = false, $dry_run = false ) {

	$email = gp_force_singular( $email );
	$email = trim( $email );

	$password_1 = gp_force_singular( $password_1 );
	$password_1 = trim( $password_1 );

	$password_2 = gp_force_singular( $password_2 );
	$password_2 = trim( $password_2 );

	if ( ! $email || ! validate_email( $email ) ) {
		throw new User_Exception( 'Invalid email.' );
	}

	$password_error_message = ''; // by reference
	if ( ! validate_two_passwords( $password_1, $password_2, $password_error_message ) ) {
		throw new User_Exception( $password_error_message );
	}

	if ( ! filter_validate_persons_name( $first_name ) ) {
		throw new User_Exception( 'First name appears invalid. Please try making it shorter or remove special characters.' );
	}

	if ( ! filter_validate_persons_name( $last_name ) ) {
		throw new User_Exception( 'Last name appears invalid. Please try making it shorter or remove special characters.' );
	}

	// I guess do this near the end
	if ( user_email_exists( $email ) ) {
		throw new User_Exception( 'That email address is already taken.' );
	}

	// validation passed
	if ( $dry_run ) {
		return true;
	}

	$user_id = insert_user_direct( $email, $password_1, $first_name, $last_name, $admin );

	if ( ! $user_id ) {
		throw new User_Exception( 'The user could not be created.' );
	}

	return $user_id;
}

/**
 * @param array $args
 *
 * @return string
 */
function get_ajax_logout_anchor_tag( $args = array(), $args_2 = array() ) {

	$data = get_ajax_logout_form_data( $args_2 );
	$json = gp_json_encode( $data );

	$logout_url = gp_if_set( $data, 'logout_url' );

	$cls   = [ 'ajax-logout' ];
	$cls[] = gp_if_set( $args, 'add_class' );

	$text = gp_if_set( $args, 'text', 'Log Out' );

	$op = '';
	$op .= '<a href="' . $logout_url . '" class="' . gp_parse_css_classes( $cls ) . '" data-ajax="' . $json . '">' . $text . '</a>';

	return $op;

}

/**
 * Get an array of data that should be used for ajax logout buttons...
 * most data gets send to the /ajax/logout.php script, but we
 * also have to include url, nonce, ajax_action etc.
 */
function get_ajax_logout_form_data( $args = array() ) {

	$logout_url = gp_if_set( $args, 'logout_url', get_url( 'logout' ) );

	$ret = [];

	$ret[ 'url' ]         = AJAX_URL;
	$ret[ 'ajax_action' ] = 'logout';
	$ret[ 'nonce' ]       = get_nonce_value( 'logout' );

	// as a fallback we may have a page that logs someone out without the use of ajax.
	// so if the ajax fails for some reason, we can use javascript to direct them to that page...
	// of course if javascript is broken then that won't work, but in that case we can
	// make the buttons anchor tags that link to the logout url, so if javascript is broken
	// they'll just go to the url instead of trying to send ajax.
	if ( $logout_url ) {
		$ret[ 'logout_url' ] = $logout_url;
	}

	// return an array. dont forget to gp_json_encode if you are putting it in a data attribute
	return $ret;
}

/**
 * @param $password
 */
function app_hash_password( $password ) {
	$ret = password_hash( $password, PASSWORD_BCRYPT );

	return $ret;
}

/**
 * @see make_user_logged_out_everywhere()
 *
 * @param DB_User $user
 */
function update_user_unique_login_counter( DB_User &$user  ){

	if ( ! $user ) {
		return false;
	}

	// update the user based on database values first, in case the php object was out of sync
	$user->re_sync();

	$prev = $user->get( 'unique_login_counter' );

	if ( gp_is_integer( $prev ) ) {
		$next   = $prev + 1;
		$is_int = true;
	} else {
		// previous should be an integer but in case its not...
		$next   = uniqid( $prev );
		$is_int = false;
	}

	$data = array(
		'unique_login_counter' => $next,
	);

	$data_format = array(
		'unique_login_counter' => $is_int ? '%d' : '%s',
	);

	$updated = $user->update_database_and_re_sync( $data, $data_format );

	if ( $updated ) {
		return true;
	}

	return false;
}

/**
 * This increments the unique_login_counter database column, which
 * should invalidate their session authorization string.
 *
 * @param DB_User $user
 */
function make_user_logged_out_everywhere( DB_User &$user, $in_script = true ) {

	// this ensures that cw_get_logged_in_user() doesn't return cached result the next time its called.
	if ( $in_script ) {
		cw_make_user_logged_out();
	}

	return update_user_unique_login_counter( $user );
}

/**
 *
 */
function delete_user( $user_id ){

	if ( ! $user_id ) {
		return false;
	}

	$db = get_database_instance();

	$p = [];
	$q = '';
	$q .= 'DELETE ';
	$q .= 'FROM ' . $db->users . ' ';
	$q .= 'WHERE user_id = :user_id ';
	$p[] = [ 'user_id', $user_id, '%d' ];

	$st = $db->bind_params( $q, $p );
	$deleted = $st->execute();

	return (bool) $deleted;
}

/**
 * @param $user_id
 */
function delete_user_reviews( $user_id ) {

	if ( ! $user_id ) {
		return false;
	}

	$db = get_database_instance();

	$p = [];
	$q = '';
	$q .= 'DELETE ';
	$q .= 'FROM ' . $db->reviews . ' ';
	$q .= 'WHERE user_id = :user_id ';
	$p[] = [ 'user_id', $user_id, '%d' ];

	$st = $db->bind_params( $q, $p );
	$deleted = $st->execute();

	return (bool) $deleted;
}

/**
 * This automatically logs the user out everywhere (if successful), due to the fact that logged
 * in status depends on hashed password and unique_login_counter.
 *
 * Update: also put the failed logins back to zero, otherwise the user might be
 * locked or get locked on the first next failed attempt to login.
 *
 * @param DB_User $user
 * @param         $password
 */
function update_users_password( DB_User $user, $password ) {

	$data = array(
		'password' => app_hash_password( $password ),
		'unique_login_counter' => $user->get( 'unique_login_counter' ) + 1,
		'failed_logins' => 0,
	);

	$data_format = array(
		'password' => '%s',
		'unique_login_counter' => '%d',
		'failed_logins' => '%d',
	);

	$updated = $user->update_database_and_re_sync( $data, $data_format );

	return (bool) $updated;
}

/**
 * Updates the user password but DOES NOT make them logged out. Well, it logs them out but
 * then logs them in again, so that they remained logged in after using this. If you want them to
 * be logged out, then.. call this function and log them out afterwards.
 *
 * This should only be used to update the currently logged in users password.
 *
 * If the update fails, and stay logged in is false, the user won't be logged out automatically.
 *
 * @param DB_User $user
 * @param         $password
 */
function update_current_users_password( $password, $stay_logged_in = true ) {

	$cur = cw_get_logged_in_user();

	if ( ! $cur ) {
		return false;
	}

	$updated = update_users_password( $cur, $password );

	// note: password update will automatically log a user out
	if ( $updated && $stay_logged_in ) {
		cw_make_user_logged_in( $cur );
	}

	return $updated;
}

/**
 * must return whether or not the user was locked as a result of running this function.
 *
 * @param DB_User $user
 */
function track_failed_user_login( DB_User $user ){

	$user->update_database_and_re_sync(
		[
			'failed_logins' => (int) $user->get( 'failed_logins' ) + 1,
		],
		[
			'failed_logins' => '%d',
		]
	);

	$failed_logins = (int) $user->get( 'failed_logins' );

	if ( $failed_logins >= MAX_FAILED_LOGIN_ATTEMPTS ) {

		$locked_status = (int) $user->get( 'locked_status' );

		// don't demote a user from locked status 2 to locked status 1
		if ( $locked_status < 1 ) {
			$user->update_database_and_re_sync(
				[
					'locked_status' => 1,
					// not tracking locked time anymore
					// 'locked_time' => time(),
				],
				[
					'locked_status' => '%d',
					// 'locked_time' => '%d',
				]
			);
			return true;
		}
	}

	return false;
}


/**
 * @param DB_User $user
 * @param         $password
 *
 * @return bool
 */
function verify_user_password( DB_User $user, $plain_text ) {

	$hashed_pw = $user->get( 'password' );

	if ( ! $hashed_pw ) {
		return false;
	}

	if ( ! $plain_text ) {
		return false;
	}

	$v = app_verify_password( $plain_text, $hashed_pw );

	return (bool) $v;
}

/**
 * @param $plain_text
 * @param $hashed
 *
 * @return bool
 */
function app_verify_password( $plain_text, $hashed ) {

//	$now = time();
//	$count = isset( $_SESSION['login_attempts']['count'] ) ? $_SESSION['login_attempts']['count'] : 0;
//	$count = $count ? $count : 0;
//	$last = isset( $_SESSION['login_attempts']['last'] ) ? $_SESSION['login_attempts']['last'] : $now;
//	if ( $now - $last > 1800 ){
//		$_SESSION['login_attempts']['count'] = 0;
//	}
//	$sleep = array(
//		5 => 1,
//		10 => 2,
//	);

	$ret = password_verify( $plain_text, $hashed );
	return (bool) $ret;
}

/**
 * You must validate email and password if you are going to use this function.
 *
 * @param $email
 * @param $password - plain text, will be hashed
 * @param $first_name
 * @param $last_name
 * @param bool $admin
 * @return bool|string
 * @throws Exception
 */
function insert_user_direct( $email, $password, $first_name, $last_name, $admin = false ) {

	$db = get_database_instance();

	$hash = app_hash_password( $password );

	$format = 'Y-m-d G:i:s';
	$date   = date( $format );

	// no cleaning of data at this point
	$user_id = $db->insert( DB_users, array(
		'email' => trim( $email ),
		'password' => $hash,
		'signup_date' => $date,
		'unique_login_counter' => 1,
		'reset_key' => '',
		'reset_expiry' => '',
		// in case these are forced to be integers one day,
		// we'll set it up with zero values for now
		'locked_status' => '',
		'failed_logins' => 0,
		'role' => $admin ? 'admin' : '',
		'first_name' => trim( $first_name ),
		'last_name' => trim( $last_name ),
	) );

	if ( $user_id ) {
		return $user_id;
	}

	return false;
}