<?php
/**
 * nonces less than half expired get re-used when the page is loaded.
 * nonces aren't truly nonces, they can be used multiple times.
 * if we need true "numbers used only once" we can do so on a per-form basis.
 * these however, are "nonces" used for every single ajax form.
 */

/**
 * Note: $action doesn't have to be an AJAX action, some will
 * use post back or other methods.
 *
 * @see Ajax::$map_file
 *
 * @param $action
 */
/**
 * @param $action - possibly not an ajax action
 * @return mixed|bool
 */
function get_nonce_secret_via_ajax_action( $action ){

    $secrets = get_nonce_secrets();

    if ( @$secrets[$action] ) {
        return $secrets[$action];
    }

	throw_dev_error( 'Nonce secret not found:' . $action );
	return false;
}

// maps actions to secrets.
// the action is printed in the form hidden, but the secret is used for
// generating nonce values and we shouldn't reveal it to the user for security
// reasons.
function get_nonce_secrets(){

    $non_ajax_actions = [
        'import_rims_file_upload' => 'cw-rim-import-file-upload',
        'import_tires_file_upload' => 'cw-tire-import-file-upload',
        'logout_post_back' => 'logout-postback-1239786yasd8f67t8a7st6d',
        'tax_shipping_post' => 'taxShipping-asd8976gasd86gasd76gasd',
        'edit_single_users' => 'edit-users-for-admin-only',
    ];

    $ajax_actions = array_map( function( $route ) {
        return $route['nonce_secret'];
    }, Ajax::get_routes() );

    return array_merge( $non_ajax_actions, $ajax_actions );
}

/**
 * @param $action
 *
 * @return array
 */
function get_nonce_data( $action ) {
	$ret = isset( $_SESSION['_nonces'][$action] ) ? $_SESSION['_nonces'][$action] : array();
	return $ret;
}

/**
 * @param $action
 */
function set_nonce_data( $action, $data ) {
	$_SESSION['_nonces'][$action] = isset( $_SESSION['_nonces'][$action] ) && $_SESSION['_nonces'][$action] && is_array( $_SESSION['_nonces'][$action] ) ? $_SESSION['_nonces'][$action] : array();

	// some debug info that can be easily turned on and wont have any negative effects
//	if ( $data && is_array( $data ) ) {
//		foreach ( $data as $d1=>$d2 ) {
//			$expires = gp_if_set( $d2, 'expires' );
//			$data[$d1]['date_dont_use'] = date( 'M.d h:i:sa', $expires );
//			$data[$d1]['remaining_dont_use'] = $expires - time();
//		}
//	}

	$_SESSION['_nonces'][$action] = $data;
}

/**
 * mainly using this for testing
 */
function remove_all_session_nonce_data(){
	$_SESSION['_nonces'] = array();
}

/**
 * @param $action
 * @param bool $action_is_secret
 * @return bool|mixed|string
 */
function get_nonce_value( $action, $action_is_secret = false ){

	$now = time();

	// this must be the same as in validate_nonce
	if ( $action_is_secret ) {
		$secret = $action;
		$action = 'secret__' . $action;
	} else {
		$secret = get_nonce_secret_via_ajax_action( $action );
	}

	if ( ! $secret ) {
		return false;
	}

	$data = get_nonce_data( $action );

	// look for an existing value that's less than half expired and use that if found.
	if ( $data && is_array( $data ) ) {
		foreach ( $data as $k=>$v ) {
			$val = gp_if_set( $v, 'val' );
			$expires = gp_if_set( $v, 'expires' );

			$half_expiry_time_remains = $expires - $now >= NONCE_HALF_TIME;

			// Return existing value
			if ( $val && $expires && $half_expiry_time_remains ) {
				return $val;
			}
		}
	}

	// make a new nonce
	$nonce = make_nonce_string_raw( $action, $secret );

	$data[] = [
		'val' => $nonce,
		'expires' => $now + NONCE_TIME,
	];

	set_nonce_data( $action, $data );

	return $nonce;
}

/**
 * validate nonce and also remove expired nonces for a given action
 *
 * @param $action
 * @param $value
 * @param bool $action_is_secret
 * @return bool
 */
function validate_nonce_value( $action, $value, $action_is_secret = false  ){

	// this must be the same as in get_nonce_value()
	if ( $action_is_secret ) {
		$action = 'secret__' . $action;
	}

	$now = time();
	$data = get_nonce_data( $action );

	$valid = false;

	if ( $data && is_array( $data ) ) {
		foreach ( $data as $k=>$v ) {
			$val = gp_if_set( $v, 'val' );
			$expires = gp_if_set( $v, 'expires' );
			$is_expired = ( $expires <= $now );

			// echo get_string_for_log( $is_expired );
			// remove expired
			if ( $is_expired ) {
				unset( $data[$k] );
				continue;
			}

			// if not expired, see if value matches
			if ( $val && $val == $value ) {
				$valid = true;
			}
		}
	}

	// update the data now
	set_nonce_data( $action, $data );

	return $valid;
}

/**
 * This is stored in $_SESSION. It doesn't have to do anything magical. It just
 * needs to be fairly random, and not reveal sensitive information. It doesn't
 * even have to be unique. Even if someone gets a hold of someone else's nonce string,
 * they still have to visit the page for their nonce to be stored in the session so
 * that it can be validated when they try to use it.
 *
 * @param $action
 * @param $secret
 *
 * @return string
 */
function make_nonce_string_raw( $action, $secret ) {
	$rnd = $action . string_half( $secret ) . string_half( session_id() ) . time();
	// let's still use an irreversible hashing algorithm since
	// the string may contain a little bit of sensitive information.
	// the sensitive info is really just there so that different users
	// get different strings at different times.
	$nonce = hash( 'sha512', $rnd );
	return $nonce;
}

/**
 * should be non-reversible.. $str should have session_id() in it
 *
 * @param $str
 *
 * @return string
// */
//function randomize_secret_nonce_string( $str ){
//	$ret = hash( 'sha512', $str );
//	return $ret;
//}
