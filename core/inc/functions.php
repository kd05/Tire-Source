<?php
/**
 * Project specific functions. See helpers.php for general functions
 */

/**
 * Get image source from IMAGES_DIR
 *
 * Example file names: "http://....image.jpg", "image.jpg", "logos/rims/rim-brand.png"
 *
 * @param string $filename
 *
 * @return string
 */
function get_image_src( $filename = '' ) {

    if ( ! $filename ) {
        return '';
    }

	$filename = gp_make_singular( $filename );
	$filename = trim( $filename );

	if ( ! $filename ) {
		return '';
	}

	if ( is_url_not_strict( $filename ) ) {
		return $filename;
	}

	if ( image_exists_in_images_dir( $filename ) ) {
		return IMAGES_URL . '/' . $filename;
	}

	return '';
}

/**
 * copying some stuff from get_image_src(), sorry.
 *
 * @param string $filename
 */
function get_video_src( $filename ) {

    if ( ! $filename ) {
        return '';
    }

	$filename = gp_make_singular( $filename );
	$filename = trim( $filename );

	if ( ! $filename ) {
		return '';
	}

	if ( is_url_not_strict( $filename ) ) {
		return $filename;
	}

	if ( file_exists( VIDEOS_DIR . '/' . $filename ) ) {
		return VIDEOS_URL . '/' . $filename;
	}

	return '';
}

/**
 * links an image, and maybe we'll try to tell if an image does not exist, but
 * we can only do this efficiently if the value in question is not a URL, in which case
 * we know that it should correspond to a file found in our ASSETS_DIR /images folder.
 *
 * (probably) does not sanitize. just puts $value in a link.
 *
 * @param $value
 *
 * @return string
 */
function format_image_cell_data_for_admin_table( $value ) {

	// get image source from a url or from a filename found in our images directory
	$src = get_image_src( $value );

	if ( ! $src ) {
		if ( ! $value ) {
			return '';
		} else {
			// note: the main purpose of this function is to get to here, when the file name provided
			// is not found. Otherwise, we wouldn't have this function, we'd just link to get_image_src()
			return $value . ' [image-not-found]';
		}
	}

	$target = '_blank';

	if ( is_url_not_strict( $value ) ) {
		$text = $value;
		$url  = $value;
	} else {
		$text = $value;
		$url  = $src;
	}

	$ret = get_anchor_tag_simple( $url, $text, [
		'target' => $target,
	] );

	return $ret;
}

/**
 * filename should be simple, like some-file.jpg.
 *
 * should not contain more than 1 dot, and should not contain slashes.
 *
 * there's no reason in the future why we absolutely could not have dots and slashes, but
 * the issue is that this could be used on user input, and might start with "../../../" for example,
 *
 * @param $filename
 *
 * @return bool|null
 */
function image_exists_in_images_dir( $filename ) {

	if ( strpos( $filename, '/' ) !== false ) {
		return false;
	}

	$filename = str_replace( './', '', $filename );
	$filename = str_replace( '..', '', $filename );

	// unfortunately, some image names like "starfire rsw 5.0.jpg" although
	// they are terrible names to use, should be considered legit
	//    if ( substr_count( $filename, '.' ) !== 1 ) {
	//        return false;
	//    }

	if ( file_exists( ASSETS_DIR . '/images/' . $filename ) ) {
		return true;
	}

	return false;
}

/**
 *
 */
function cw_get_header() {
    include CORE_DIR . '/templates/header.php';
}

/**
 *
 */
function cw_get_footer() {
    include CORE_DIR . '/templates/footer.php';
}

/**
 * @param $data
 *
 * @return array
 */
function gp_object_to_array( $data ) {
	if ( is_object( $data ) ) {
		$data = get_object_vars( $data );
	}

	// recursive call on all elements
	if ( is_array( $data ) ) {
		return array_map( 'gp_object_to_array', $data );
	}

	return $data;
}

/**
 * Simple wrapper function in case we ever implement a different DB class,
 * and also because our database class is singleton which is fine but if
 * that needs to change in the future, then we can change the logic here.
 *
 * @return DatabasePDO
 */
function get_database_instance() {
	return DatabasePDO::get_instance();
}

/**
 * This lets you bypass the map_ajax_action_to_secret() function, so you can use
 * a nonce secret, but not have to basically map an action to a secret and then pass in
 * the action which is an annoying middle step as it turns out.
 *
 * @param $secret
 * @return string
 */
function get_nonce_input_via_secret( $secret ) {
	return '<input type="hidden" name="nonce" value="' . get_nonce_value( false, $secret ) . '">';
}

/**
 * @param      $action
 * @param bool $action_is_secret - if this is false, and your action to nonce is not registered, an error will be thrown
 *
 * @return string
 */
function get_nonce_input( $action, $action_is_secret = false ) {
	return '<input type="hidden" name="nonce" value="' . get_nonce_value( $action, $action_is_secret ) . '">';
}

/**
 * @param $sub_action
 *
 * @return string
 */
function get_ajax_hidden_inputs_for_general_ajax( $sub_action ) {
	return get_ajax_hidden_inputs( 'general_ajax', $sub_action );
}

/**
 * Gets nonce and 'ajax_action' hidden input fields
 *
 * @param        $action
 * @param string $name
 */
function get_ajax_hidden_inputs( $action, $general_ajax_sub_action = null ) {
	$op = '';
	$op .= get_nonce_input( $action );
	$op .= Ajax::get_action_field( $action );

	// you should probably only do this if $action === 'general_ajax'
	if ( $general_ajax_sub_action ) {
		$op .= '<input type="hidden" name="general_ajax_sub_action" value="' . gp_test_input( $general_ajax_sub_action ) . '">';
	}

	return $op;
}

/**
 * You'll want to load this into the Session_Cart class, and then
 * use set_session_vehicles() after you do something with the data.
 *
 * The purpose of this function is to easily transition to database stored
 * vehicles/cart if we end up wanting to. Therefore, always use this function
 * to get the stuff from "session" even if you think you have an easier way.
 *
 * @return bool|mixed
 */
function get_session_cart() {

	if ( app_get_locale() === 'US' ) {
		return gp_if_set( $_SESSION, SESSION_CART_US, array() );
	} else {
		return gp_if_set( $_SESSION, SESSION_CART_CA, array() );
	}
}

/**
 * @param $arr
 */
function set_session_cart( $arr ) {

	if ( app_get_locale() === 'US' ) {
		$_SESSION[ SESSION_CART_US ] = $arr;

		return;
	} else {
		$_SESSION[ SESSION_CART_CA ] = $arr;

		return;
	}
}

/**
 * Use this if you need to extract additional, but also optional, information
 * out of the return value of the function. $context might be the name of the function you are calling.
 *
 * When performing complex actions, there's probably much better solutions.
 *
 * You can think of this a little bit like throwing an exception that doesn't cause errors when not caught.
 * listen_start() is like "try", listen_set() is like "throw", listen_get() is like "catch". Keep in mind if you
 * simply start throwing exception inside of functions that are already being called in many places, then you have to
 * now add try/catch blocks around every function call, and continue to use try/catch blocks in the future. This is
 * annoying then try/catch blocks aren't truly needed.
 *
 * Another very similar way to achieve the same thing, is pass an additional, optional, passed by reference variable to
 * a function, then conditionally check the variable after execution of the function. This does a similar thing, but
 * seems even a bit easier.
 *
 * So why not just return an array from a function? Well that's messy, and if your function is used in many different
 * places then you have to re-factor all of your code. Even the pass-by-reference method above may require some
 * re-factoring code, especially if you already had optional variables in your function call, now you have to change
 * the order of those, or put your pass by reference variable at the end, thereby nulling all the other default values.
 *
 * This is good for functions that return true or false, and in most cases all you care about is the primary return
 * value. But once in a while, you want a little bit more information, like why did the function return false? Or
 * better yet, did anything else happen that we might care to know?
 *
 * As far as drawbacks of using this go, I think the biggest drawback is again if you try to rely too heavily on it. Do
 * not make your code dependant on listen blocks. You should call listen_start(), then function, then listen_get() on 3
 * lines. It's meant to be used only to "supplement" a return value. Your code should be 100% functional even if we
 * make every single listen function do nothing. Once again, "supplemental" return values. If we have a function
 * add_to_cart(), and it returns false, we tell the user there was an error, but if we run listen_get() and get more
 * detailed information, we tell the user more detailed information. Code should never ever rely on the return value of
 * listen_get(). There's nothing preventing you from doing this, but if you do, your an idiot.
 *
 * @param string $context
 */
function listen_start( $context = '' ) {
	// note: you don't have to use listen_start(), but its a way of clearing any previous values
	// under the same context. you can otherwise just use listen_set()
	global $listen;
	$context            = $context ? $context : 0;
	$listen             = is_array( $listen ) ? $listen : array();
	$listen[ $context ] = null;
}

/**
 * @param $key
 * @param $value
 */
function listen_add_ajax_debug( $key, $value = '' ) {
	if ( $key && ! $value ) {
		$k = false;
		$v = $key;
	} else {
		$k = $key;
		$v = $value;
	}

	if ( $v && ! gp_is_singular( $v ) ) {
		$v = gp_make_singular( $v );
	}

	listen_add_key_value( $k, $v, 'ajax_debug' );
}

/**
 * all this does is change the order of function args and
 * then call listen_add()
 *
 * @param $key
 * @param $value
 * @param $context
 */
function listen_add_key_value( $key, $value, $context ) {
	listen_add( $value, $context, $key );
}

/**
 * @param        $value
 * @param string $context
 */
function listen_add( $value, $context = '', $key = '' ) {

	$ex = listen_get( $context, array() );
	$ex = gp_force_array( $ex );

	// the idea is to never lose data, and print an ugly looking array instead.
	if ( ! $key ) {
		$ex[] = $value;
	} else {
		if ( isset( $ex[ $key ] ) ) {

			// if its a string, make it an array with 1 element
			$ex[ $key ] = gp_make_array( $ex[ $key ] );

			// so that this doesnt break things..
			$ex[ $key ][] = $value;

		} else {
			$ex[ $key ] = $value;
		}
	}

	listen_set( $ex, $context );
}

/**
 * Will override any previous values. Be mindful of this.
 *
 * @param        $value
 * @param string $context
 */
function listen_set( $value, $context = '' ) {
	global $listen;
	$context            = $context ? $context : 0;
	$listen             = is_array( $listen ) ? $listen : array();
	$listen[ $context ] = $value;
}

/**
 * @param      $context
 * @param null $default
 *
 * @return null
 */
function listen_get( $context = 0, $default = null ) {
	global $listen;
	$r = isset( $listen[ $context ] ) ? $listen[ $context ] : $default;

	return $r;
}

/**
 * @param $title
 * @param $content
 */
function print_dev_alert( $title, $content = '', $print_r = true ) {

	if ( $print_r ) {
		$content = get_pre_print_r( $content );
	}

	echo get_dev_alert( $title, $content );
}

/**
 * javascript targets this. click on  .trigger, toggle .alert-content
 *
 * this is for development only..
 *
 * @param $title
 * @param $data
 */
function get_dev_alert( $title, $content = '' ) {

	$content = gp_make_singular( $content );
	$content = $content ? $content : '<br>';

	$op = '';
	$op .= '<div class="dev-alert">';
	$op .= '<p class="trigger">' . $title . '</p>';
	$op .= '<div class="alert-content">';
	$op .= $content;
	$op .= '</div>';
	$op .= '</div>';

	return $op;
}

/**
 * @param $thing
 */
function get_pre_print_r( $thing, $htmlspecialchars = false, $wrap = true ) {

	$body = print_r( $thing, true );

	if ( $htmlspecialchars ) {
		$body = htmlspecialchars( $body );
	}

	if ( $wrap ) {
		$ret = '<pre style="white-space: pre-wrap">' . $body . '</pre>';
	} else {
		$ret = '<pre>' . $body . '</pre>';
	}

	return $ret;
}

/**
 * Not sure if method_exists throws errors if you pass an array or null.
 *
 * @param $obj
 * @param $method
 */
function is_object_and_has_method( $obj, $method ) {
	if ( is_object( $obj ) ) {
		if ( method_exists( $obj, $method ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Passing in $userdata only has effect on first function call.
 * Note: Vehicle_General is not singleton. But we want to avoid multiple instances per page load
 * due to possibly database or API hits. Therefore, in most cases, just use the "primary" instance.
 *
 * @param null $userdata
 *
 * @return Vehicle
 */
function get_primary_vehicle_instance( $userdata = null ) {

	global $vehicle_primary_instance;
	if ( ! $vehicle_primary_instance instanceof Vehicle ) {
		$userdata                 = $userdata === null ? $_GET : $userdata;
		$vehicle_primary_instance = Vehicle::create_instance_from_user_input( $userdata );
	}

	return $vehicle_primary_instance;
}

Class Parsed_Tire_Size_String {

	public $error;
	public $error_type;
	public $width;
	public $diameter;
	public $profile;
	public $tire_sizing_system;
	public $is_zr;

	/**
	 * Parsed_Tire_Size_String constructor.
	 */
	public function __construct( $str ) {

		// [R][0-9]{2}

		$this->error = false;

		if ( ! $str || ! gp_is_singular( $str ) ) {
			$this->error      = true;
			$this->error_type = 'empty';

			return;
		}

		$str = trim( $str );

		// I believe 9 should be the minimum
		// this also intentionally returns false on weird sizing strings that omit the profile because
		// there's no way that i know of that we can handle those anyways, so ..
		if ( strlen( $str ) < 9 ) {
			$this->error      = true;
			$this->error_type = 'strlen';

			return;
		}

		// there are technically other uncommon systems but i dont think we're using more than 2
		// LT lowercase
		if ( strpos( strtolower( $str ), 'lt' ) === 0 ) {
			$this->tire_sizing_system = 'lt-metric';
		} else {
			$this->tire_sizing_system = 'metric';
		}

		if ( strpos( strtolower( $str ), 'zr' ) !== false ) {
			$this->is_zr = true;
		} else {
			$this->is_zr = false;
		}

		$width   = false;
		$matches = array();
		// the first 3 digits in a row found in the string (not necessarily starting from position 0),
		// ie. in 225/65R16, or LT225/65R16, this should give us "225"
		$preg = preg_match( '/([\d]{3})/', $str, $matches );

		if ( $preg ) {
			// first capture group, which should be the diameter
			$group = gp_if_set( $matches, 1 );
			$width = (int) $group;
		}

		$profile = false;
		$matches = array();
		// forward slash followed by 2 digits.
		// Remember, not all "valid-ish" strings even have forward slashes (or numeric profile indicators) in them, ie: 185R14C
		$preg = preg_match( '/\/([\d]{2})/', $str, $matches );

		if ( $preg ) {
			// first capture group, which should be the diameter
			$group   = gp_if_set( $matches, 1 );
			$profile = (int) $group;
		}

		$diameter = '';
		$matches  = array();
		// the first two digits after R. not expecting more than 1 match, but if there is, it returns the first one
		$preg = preg_match( '/[R]([\d]{2})/', $str, $matches );

		if ( $preg ) {
			// first capture group, which should be the diameter
			$group    = gp_if_set( $matches, 1 );
			$diameter = (int) $group;
		}

		// echo '<pre>' . print_r( array( $width, $profile, $diameter ), true ) . '</pre>';

		// set really high limits here mainly as a fallback, in case we end up with something ridiculous like
		// width = 22550, which should have been 225 width and 50 profile. I dont care if 500 is too large
		// for a tire, i have no idea what the largets tire would be..
		if ( ! $width || $width < 80 || $width > 500 ) {
			$this->error      = true;
			$this->error_type = 'width_range';

			return;

		}

		if ( ! $profile || $profile < 15 || $profile > 150 ) {
			$this->error      = true;
			$this->error_type = 'profile_range';

			return;
		}

		if ( ! $diameter || $diameter < 8 || $diameter > 40 ) {
			$this->error      = true;
			$this->error_type = 'diameter_range';

			return;
		}

		$this->width    = $width;
		$this->profile  = $profile;
		$this->diameter = $diameter;
	}
}

/**
 * Here's a fun example: 31X10.50R15
 *
 * Be careful, another more common, and legit example is this: LT215/75R15
 *
 * There is also this: 185R14C (width, diameter, and... C? )
 *
 * Most are in the correct format: 225/65R17
 *
 * @param $str
 *
 * @return array
 */
// function parse_tire_size_string( $str ) {}

/**
 * call this on all bolt pattern texts BEFORE saving to database, otherwise
 * our queries could screw up and return no results when looking for
 * 5X120, when theres lots of 5x120.
 */
function gp_fix_bolt_pattern_text( $str ) {

	// 5X100 => 5x100. upper or lower doesn't really matter we just need consistency
	// between importing products, and running sql queries.
	$str = strtolower( $str );

	// remove whitespace
	$str = preg_replace( '/\s+/', '', $str );

	return $str;
}

/**
 * Some imports had bolt patterns provided to us like "5-120".
 *
 * Ideally this should be "5x120", but lets change do a quick fix and change it in the code
 * instead. Be aware that sometimes dual bolt patterns are provided, but in this case, the separator
 * must be "/" and not "-"
 *
 * @param $str
 */
function gp_replace_single_dash_in_bolt_pattern_text_for_imports( $str ) {

	if ( strpos( $str, '-' ) === 1 && substr_count( $str, '-' ) === 1 ) {
		$str = str_replace( '-', 'x', $str );
	}

	return $str;
}

/**
 * Sometimes a single bolt pattern (in import scripts) is
 * given to us as "{bolt pattern 1} / {bolt pattern 2"}...
 *
 * For non dual bolt patterns, the second index of the returned array will be empty.
 *
 * this also takes care of trimming and replacing X with x and some other things.
 *
 * The result should be ready to update into a database.
 *
 * @param $str
 */
function parse_possible_dual_bolt_pattern( $str ) {

	$arr = explode( '/', $str );

	$ret = array(
		0 => gp_if_set( $arr, 0 ),
		1 => gp_if_set( $arr, 1 ),
	);

	// take care of some more formatting
	$ret = array_map( function ( $v ) {
		// this is a bit overkill here with some redundancies but its fine i'm sure
		$v = trim( $v );
		$v = strtolower( $v );
		$v = gp_replace_single_dash_in_bolt_pattern_text_for_imports( $v );
		$v = gp_fix_bolt_pattern_text( $v );
		$v = strpos( strtolower( $v ), 'x' ) !== false ? $v : '';

		return $v;
	}, $ret );

	return $ret;
}

/**
 * get_numeric('3'); // int(3)
 * get_numeric('1.2'); // float(1.2)
 * get_numeric('3.0'); // float(3)
 *
 * @param $val
 *
 * @return int|string
 */
function get_numeric( $val ) {
	if ( is_numeric( $val ) ) {
		return $val + 0;
	}

	return 0;
}

/**
 * the is_numeric() php function allows exponential notation, which
 * is generally not how we want users to enter numbers.
 *
 * @param $str
 */
function gp_is_numeric_and_normal( $str ) {

	if ( is_numeric( $str ) ) {

		if ( strpos( $str, 'e' ) !== false ) {
			return false;
		}

		if ( strpos( $str, '+' ) !== false ) {
			return false;
		}

		// I guess we'll allow for -

		// is
		return true;
	}

	return false;
}

Class Parsed_Rim_Size {

	public $diameter;
	public $width;
	public $error;
	public $error_type;

	/**
	 * Parsed_Rim_Size constructor.
	 *
	 * @param $str
	 */
	public function __construct( $str ) {

		// sometimes we get "5 X 15) for example
		$str = trim( $str );
		$str = str_replace( ' ', '', $str );
		$str = strtolower( $str );

		// "9Jx20 ET42" notation not supported here
		if ( strpos( $str, 'et' ) ) {
			$this->error      = true;
			$this->error_type = 'char_et_found';

			return;
		}

		// "9Jx20 ET42" notation not supported here
		if ( strpos( $str, 'j' ) ) {
			$this->error      = true;
			$this->error_type = 'char_j_found';

			return;
		}

		if ( strpos( $str, 'x' ) !== false ) {

			$arr = explode( 'x', $str );

			$diameter = gp_if_set( $arr, 0 );
			$diameter = trim( $diameter );

			$width = gp_if_set( $arr, 1 );
			$width = trim( $width );

			// diameter we expect integer only... for width we could have strings such as "5.5", "5.0", or "5"
			$diameter = gp_is_numeric_and_normal( $diameter ) ? get_numeric( $diameter ) : false;
			$width    = gp_is_numeric_and_normal( $width ) ? get_numeric( $width ) : false;

		} else {
			$this->error      = true;
			$this->error_type = 'char_x_not_found';

			return;
		}


		// check large range just as fallback measure in case we get like diameter = 188 (typo in 18x8)
		if ( $diameter < 8 || $diameter > 40 ) {
			$this->error      = true;
			$this->error_type = 'diameter';
		}

		if ( ! $width ) {
			$this->error      = true;
			$this->error_type = 'width';
		}

		if ( ! $this->error ) {
			$this->width    = $width;
			$this->diameter = $diameter;
		}
	}
}

/**
 * This parses a string such as "15x6.5" to return diameter: 15, width: 6.5
 *
 * Not only do we need to extract width and diameter, but we also need to make sure this ensures
 * the string is valid, because it could come from many different sources. Be sure to check
 * the 'error' index in the return array before relying on width and diameter values.
 *
 * Also, sometimes rim sizes are written as 9Jx20 ET42, which also gives offset (42) and flange type (J). I have no idea
 * how to handle this. If some strings have offset, and some don't, do we try to extract offset out of the ones that do?
 * we need a param for whether in invalid offset counts as an error. For this you'll either have to
 * expect the string to have an offset, to not have an offset, or specify that you really don't know or give a shit.
 *
 * @param $str
 *
 * @return array
 */
//function parse_rim_size( $str ) {
//
//	// ie. 225/65R17 becomes:
//	$ret = array(
//		'error' => false,
//		'error_type' => '',
//		'width' => '',
//		'diameter' => '',
//	);
//
//	$diameter = '';
//	$matches  = array();
//	// the first two digits after R. not expecting more than 1 match, but if there is, it returns the first one
//	$preg = preg_match( '/([\d]{2})/', $str, $matches );
//
//	if ( $preg ) {
//		// first capture group, which should be the diameter
//		$group    = gp_if_set( $matches, 1 );
//		$diameter = (int) $group;
//	}
//
//	$width   = '';
//	$matches = array();
//	// any number of digits after x or X, followed by a single ., followed by 0 or 5
//	$preg = preg_match( '/[xX]{1}([\d]*[\.][05])/', $str, $matches );
//
//
//	if ( $preg ) {
//		// first capture group
//		$group = gp_if_set( $matches, 1 );
//		$width = $group ? map_rim_width_to_number( $group ) : null;
//	} else {
//
//
//		$width   = '';
//		$matches = array();
//		// x or X followed by any number of digits. Ie. in 20x9, this should return 9
//		$preg = preg_match( '/[xX]{1}([\d]{1,})/', $str, $matches );
//
//		if ( $preg ) {
//			// first capture group
//			$group = gp_if_set( $matches, 1 );
//			$width = $group ? map_rim_width_to_number( $group ) : null;
//		}
//	}
//
//	// check large range just as fallback measure.
//	if ( $diameter < 8 || $diameter > 40 ) {
//		$ret[ 'error' ]      = true;
//		$ret[ 'error_type' ] = 'diameter';
//	}
//
//	if ( ! $width ) {
//		$ret[ 'error' ]      = true;
//		$ret[ 'error_type' ] = 'width';
//	}
//
//	if ( $ret[ 'error' ] ) {
//		return $ret;
//	}
//
//
//	$ret[ 'width' ]    = $width;
//	$ret[ 'diameter' ] = $diameter;
//
//	return $ret;
//}

/**
 * Rim width is usually a string like "6.5", or "6.0"
 *
 * @param $str
 *
 * @return mixed|null
 */
function map_rim_width_to_number( $str ) {

	// screw php float functions
	$map = array(
		'3' => 3,
		'3.0' => 3,
		'3.5' => 3.5,
		'4' => 4,
		'4.0' => 4,
		'4.5' => 4.5,
		'5' => 5,
		'5.0' => 5,
		'5.5' => 5.5,
		'6' => 6,
		'6.0' => 6,
		'6.5' => 6.5,
		'7' => 7,
		'7.0' => 7,
		'7.5' => 7.5,
		'8' => 8,
		'8.0' => 8,
		'8.5' => 8.5,
		'9' => 9,
		'9.0' => 9,
		'9.5' => 9.5,
		'10' => 10,
		'10.0' => 10,
		'10.5' => 10.5,
		'11' => 11,
		'11.0' => 11,
		'11.5' => 11.5,
		'12' => 12,
		'12.0' => 12,
		'12.5' => 12.5,
		'13' => 13,
		'13.0' => 13,
		'13.5' => 13.5,
		'14' => 14,
		'14.0' => 14,
		'14.5' => 14.5,
		'15' => 15,
		'15.0' => 15,
		'15.5' => 15.5,
	);

	// what happens if $str equals (int) 6 for example ???
	$width = isset( $map[ $str ] ) ? $map[ $str ] : null;

	// just to be safe..
	if ( $width === null ) {
		// function gp_is_integer returns true on (string) "6" for example
		if ( gp_is_integer( $str ) ) {
			$str2  = (string) $str;
			$width = isset( $map[ $str2 ] ) ? $map[ $str2 ] : null;
		}
	}

	return $width;
}

/**
 *
 */
function gp_render_details( $arr ) {

	$arr = gp_make_array( $arr );

	$op = '';
	if ( $arr && is_array( $arr ) ) {
		foreach ( $arr as $key => $value ) {
			$op .= '</p>';
			$op .= '<strong>' . $key . ': </strong>';
			$op .= gp_make_singular( $value );
			$op .= '</p>';
		}
	}

	return $op;
}

/**
 * Use while in development when you just need an image
 */
function default_img_url() {
	return IMAGES_URL . '/iStock-624406260.jpg';
}

/**
 * @param      $str
 * @param bool $allow_underscore
 *
 * @return mixed|string
 */
function make_slug( $str, $allow_underscore = false ) {

	//	if ( ! gp_is_singular( $str ) ) {
	//		log_data( get_pre_print_r( $str ), 'make_slug_on_object' );
	//		return '';
	//	}

	//Lower case everything
	$str = trim( $str );
	$str = strtolower( $str );
	//Make alphanumeric (removes all other characters)
	$str = preg_replace( "/[^a-z0-9_\s-]/", "", $str );
	//Clean up multiple dashes or whitespaces
	$str = preg_replace( "/[\s-]+/", " ", $str );

	if ( ! $allow_underscore ) {
		//Convert whitespaces and underscore to dash
		$str = preg_replace( "/[\s_]/", "-", $str );
	} else {
		// convert whitespace to underscore
		$str = preg_replace( "/[\s]/", "-", $str );
	}
	// not sure we need this again but I think we do
	$str = trim( $str );

	return $str;
}

/**
 *
 */
function get_mount_balance_data( $part_number ) {

	// add new prices here, and possibly just leave old ones in tact even if they aren't in use
	// it might not hurt to reference them one day when a part number shows up on an order
	// NOTE: mount and balance is not turned on for U.S.
	$arr = array(
		MOUNT_BALANCE_PART_NUMBER_27_MINUS => array(
			'price_ca' => '40.00',
			'price_us' => '30.00',
		),
		MOUNT_BALANCE_PART_NUMBER_27_30 => array(
			'price_ca' => '50.00',
			'price_us' => '38.00',
		),
		MOUNT_BALANCE_PART_NUMBER_30_33 => array(
			'price_ca' => '55.00',
			'price_us' => '42.00',
		),
		MOUNT_BALANCE_PART_NUMBER_33_PLUS => array(
			'price_ca' => '70.00',
			'price_us' => '62.00',
		),
	);

	foreach ( $arr as $k => $v ) {
		$arr[ $k ][ 'part_number' ] = $k;
	}

	return gp_if_set( $arr, $part_number, null );
}

define( 'INSTALL_KIT_DEFAULT_PART_NUMBER', 'install_kit_1' );

/**
 *
 */
function get_install_kit_data( $part_number ) {

	// if you change get_install_kit_number().. add new data here without removing old data
	$arr = array(
		INSTALL_KIT_DEFAULT_PART_NUMBER => array(
			'price_ca' => '64.99',
			'price_us' => '49.99',
		),
		// install kits are now flat rates and no dependant on stud holes
		//		'install_kit_4' => array(
		//			'price' => '60.00',
		//		),
		//		'install_kit_5' => array(
		//			'price' => '80.00',
		//		),
		//		'install_kit_6' => array(
		//			'price' => '100.00',
		//		),
		//		// not sure if this is a thing
		//		'install_kit_7' => array(
		//			'price' => '100.00',
		//		),
		//		'install_kit_8' => array(
		//			'price' => '120.00',
		//		),
		//		// not sure if this is a thing
		//		'install_kit_9' => array(
		//			'price' => '120.00',
		//		),
		//		'install_kit_10' => array(
		//			'price' => '140.00',
		//		),
		// need a default but it probably won't be used
		//		INSTALL_KIT_DEFAULT_PART_NUMBER => array(
		//			'price' => '64.99',
		//        ),
	);

	foreach ( $arr as $k => $v ) {
		$arr[ $k ][ 'part_number' ] = $k;
	}

	return gp_if_set( $arr, $part_number, null );
}

/**
 * Get the current install kit to use in the cart.
 *
 * @return string
 */
function get_install_kit_part_number( $stud_holes ) {

	return INSTALL_KIT_DEFAULT_PART_NUMBER;

	// dynamic prices based off of stud holes - no longer in place
	//	$stud_holes = (int) $stud_holes;
	//
	//	$find = 'install_kit_' . $stud_holes;
	//	$data = get_install_kit_data( $find );
	//
	//	// ideally we shouldn't be using the default part number, but i'd rather have this fallback then none at all.
	//    // the code was initially built to handle only one part number for install kit, and therefore, I don't know
	//    // what will happen in the cart when we return no part number.
	//    // hence, we have a fallback part number in use which is more or less guaranteed to be set.
	//	$data = $data ? $data : get_install_kit_data( INSTALL_KIT_DEFAULT_PART_NUMBER );
	//
	//	// if part number is not set, then I don't know what will happen.
	//	$part_number = gp_if_set( $data,'part_number' );
	//
	//	if ( ! $part_number ) {
	//	    throw_dev_error( 'install kit found without a part number even though were using a fallback part number' );
	//    }
	//	return $part_number;
}

/**
 * @param $part_number
 */
function get_install_kit_price( $part_number, $locale = null ) {
	$locale = if_not_false_like( $locale, app_get_locale() );
	assert( app_is_locale_valid( $locale ) );

	$index = $locale === APP_LOCALE_CANADA ? 'price_ca' : 'price_us';
	$data  = get_install_kit_data( $part_number );
	$ret   = gp_if_set( $data, $index, 0 );

	return $ret;
}

/**
 * If changing the price of mount_balance, change the part number here, and ADD
 * your price to get_mount_balance_data.
 *
 * Return value here must be found in get_mount_balance_data() array keys.
 *
 * @see get_mount_balance_data()
 */
function get_mount_balance_part_number( Fitment_Singular $fitment ) {

	// front tire/wheel dimensions
	$tire_attributes = $fitment->get_selected_wheel_set()->get_tire_atts_pair_object();

	// this redundant looking code is to indicate that we could do logic on both
	// front and rear diameters, but for simplicity, we're going to always choose the front.
	// if we wanted to choose the minimum, or the maximum, or w/e, it would be possible.
	// in most cases, the front and rear will fall into the same price category so its not a huge deal.
	$overall_diameter_1 = $tire_attributes->front->overall_diameter;
	$overall_diameter_2 = $tire_attributes->rear ? $tire_attributes->rear->overall_diameter : null;

	$overall_diameter = $overall_diameter_1;

	if ( $overall_diameter < 27 ) {
		return MOUNT_BALANCE_PART_NUMBER_27_MINUS;
	} else if ( $overall_diameter < 30 ) {
		return MOUNT_BALANCE_PART_NUMBER_27_30;
	} else if ( $overall_diameter < 33 ) {
		return MOUNT_BALANCE_PART_NUMBER_30_33;
	} else {
		return MOUNT_BALANCE_PART_NUMBER_33_PLUS;
	}
}

/**
 * "single" tire/rim mount balance price (most packages have 4 of these)
 *
 * @param $part_number
 */
function get_mount_balance_price( $part_number, $locale = null ) {
	$locale = if_not_false_like( $locale, app_get_locale() );
	assert( app_is_locale_valid( $locale ) );

	$index = $locale === APP_LOCALE_CANADA ? 'price_ca' : 'price_us';
	$data  = get_mount_balance_data( $part_number );
	$ret   = gp_if_set( $data, $index, 0 );

	return $ret;
}

/**
 * If you have a nested multi dimensional array (of arrays.. etc) ... then this class
 * will help you print a hidden input field with name like, 'args[rims][front][width][inches]'
 *
 * It loops through the array to find all singular values, then pairs those with an array of all keys
 * used up until that point.
 *
 * Class Array_Recursive_Map_Keys_To_Singular_Values
 */
Class Array_Recursive_Map_Keys_To_Singular_Values {

	public static $temp;

	public static $ret;

	public static $depth;

	public static $states;

	/**
	 * @param $array
	 */
	private static function iterate( $array ) {

		if ( $array && is_array( $array ) ) {

			self::$depth ++;

			foreach ( $array as $a1 => $a2 ) {

				// store temp before adding this key
				$temp_before = self::$temp;

				self::$temp[] = $a1;

				// recursion (until we find a string)
				if ( is_array( $a2 ) ) {
					self::iterate( $a2 );
					continue;
				}

				// add to return value, with the current key added
				self::$ret[] = [ self::$temp, $a2 ];

				// reset self::$temp back to old value
				// this is necessary when $array has more than 1 element
				self::$temp = $temp_before;
			}

			// lower the depth before the code below, AND use "<" comparison, not "<="
			self::$depth --;

			// now that a foreach loop has finished, we need to make sure
			// that self::$temp is not larger than our current depth.
			// you can think of this as like running array_pop() however,
			// array pop may run into some issues.

			$new_temp = array();

			if ( is_array( self::$temp ) ) {
				$c = 0;
				foreach ( self::$temp as $t ) {
					$c ++;
					if ( $c < self::$depth ) {
						$new_temp[] = $t;
					}
				}
			}

			// re-set the temporary array keys
			self::$temp = $new_temp;
		}
	}

	/**
	 * @param $array
	 */
	public static function run( $array ) {
		self::$ret    = array();
		self::$temp   = array();
		self::$states = array();
		self::$depth  = 0;
		self::iterate( $array );

		return self::$ret;
	}
}

/**
 * @param       $value
 * @param array $keys
 *
 * @return array
 */
function array_deep_pair_keys_with_singular_values( $value ) {
	return Array_Recursive_Map_Keys_To_Singular_Values::run( $value );
}

/**
 * @param $raw
 */
function get_hidden_inputs_from_array( $raw, $sanitize = true ) {

	// convert the array into something we can use to determine input names
	$arr = array_deep_pair_keys_with_singular_values( $raw );

	$op = '';

	if ( $arr && is_array( $arr ) ) {

		foreach ( $arr as $a1 => $a2 ) {

			$keys = gp_if_set( $a2, 0, array() );

			$value = gp_if_set( $a2, 1, '' );
			$value = $sanitize ? gp_test_input( $value ) : $value;

			$name = '';
			if ( $keys && is_array( $keys ) ) {

				// wondering if its ok to print 2 hidden inputs with names diameter[0] and diameter[1]
				// obviously it works, but in theory this should both just have names diameter[], but with 2
				// inputs we'd get the same thing. On the other hand, if we have numbers inside the [] then we
				// cant just always strip them out, because this might break things when the keys are needed.
				// so its hard to determine the correct logic, but we should be ok just printing [0], [1], [2], etc.
				// ie. $keys has array keys like 0,1,2,3,4... and in that order
				//				if ( is_array_numerically_indexed( $keys, false, true ) ) {
				//					$numbered_keys = true;
				//				} else {
				//					$numbered_keys = false;
				//				}

				$first = true;
				foreach ( $keys as $k1 => $k2 ) {

					if ( $sanitize ) {
						$k2 = gp_test_input( $k2 );
					}

					if ( $first ) {
						$first = false;
						$name  .= $k2;
					} else {
						$name .= '[' . $k2 . ']';
					}
				}
			}

			$op .= '<input type="hidden" name="' . $name . '" value="' . $value . '">';
		}
	}

	return $op;
}

/**
 * @param      $table
 * @param bool $are_you_sure - because why not
 *
 * @return bool
 */
function delete_all_from_table( $table, $are_you_sure = false ) {

	if ( ! $are_you_sure ) {
		return false;
	}

	$db = get_database_instance();
	$q  = '';
	$q  .= 'DELETE FROM ' . $table;
	$q  .= ';';

	if ( $db->pdo->prepare( $q )->execute() ) {
		return true;
	}

	return false;
}

/**
 * @return bool
 */
function delete_all_tires() {
	$db = get_database_instance();
	$q  = '';
	$q  .= 'DELETE FROM ' . $db->tires;
	$q  .= ';';
	if ( $db->pdo->prepare( $q )->execute() ) {
		return true;
	}

	return false;
}

/**
 * @return bool
 */
function delete_all_rims() {
	$db = get_database_instance();
	$q  = '';
	$q  .= 'DELETE FROM ' . $db->rims;
	$q  .= ';';
	if ( $db->pdo->prepare( $q )->execute() ) {
		return true;
	}

	return false;
}

/**
 * @param array $args
 * @param array $userdata
 * @return string
 */
function tires_by_size_form( $args = array(), $userdata = array() ) {

	$op = '';

	$title = gp_if_set( $args, 'title' );

	//	$widths    = get_all_tire_widths();
	//	$diameters = get_all_tire_diameters();
	//	$profiles  = get_all_tire_profiles();

	// passed by reference!
	$singles = array();

	// all unique combinations of width/diameter/profile
	$sizes = get_all_unique_tire_sizes( $singles, app_get_locale() );

	$_sizes = array();

	// store a smaller non-indexed array
	if ( $sizes && is_array( $sizes ) ) {
		foreach ( $sizes as $size ) {
			// skipping isset check on this one
			$d        = $size[ 'diameter' ];
			$p        = $size[ 'profile' ];
			$w        = $size[ 'width' ];
			$_sizes[] = array( $w, $p, $d );
		}
	}

	//	$current_width = get_user_input_singular_value( $userdata, 'width' );
	//	$current_diameter = get_user_input_singular_value( $userdata, 'diameter' );
	//	$current_profile = get_user_input_singular_value( $userdata, 'profile' );

	$current_width    = null;
	$current_diameter = null;
	$current_profile  = null;

	$widths    = gp_if_set( $singles, 'width', array() );
	$diameters = gp_if_set( $singles, 'diameter', array() );
	$profiles  = gp_if_set( $singles, 'profile', array() );

	$op .= '<form id="tire-size-select" class="tire-size-select form-style-1" method="get" data-base-url="' . BASE_URL . '/tires' . '" data-sizes="' . gp_json_encode( $_sizes ) . '">';

	if ( $title ) {
		$op .= '<div class="form-header">';
		$op .= '<h2>' . $title . '</h2>';
		$op .= '</div>';
	}

	$op .= '<div class="form-items">';

	$op .= '<div class="item-wrap type-select item-width">';
	$op .= '<div class="item-inner select-2-wrapper">';

	// Width
	$op .= '<select name="width" id="tss_width">';
	if ( $widths && is_array( $widths ) ) {
		asort( $widths, SORT_NUMERIC );

		$op .= get_select_options( array(
			'placeholder' => 'Tread Width',
			'items' => $widths,
			'key_equals_value' => true,
		) );

		//		$op .= '<option value="">Tread Width</option>';
		//		foreach ( $widths as $value ) {
		//			$v        = gp_test_input( $value );
		//			$selected = $v == $current_width ? ' selected="selected"' : '';
		//			$op       .= '<option value="' . $v . '"' . $selected . '>' . $v . '</option>';
		//		}
	}
	$op .= '</select>';
	$op .= '</div>'; // item-inner
	$op .= '</div>'; // item-wrap

	// Profile
	$op .= '<div class="item-wrap type-select item-width">';
	$op .= '<div class="item-inner select-2-wrapper">';
	$op .= '<select name="profile" id="tss_profile">';
	if ( $profiles && is_array( $profiles ) ) {
		asort( $profiles, SORT_NUMERIC );

		$op .= get_select_options( array(
			'placeholder' => 'Profile',
			'items' => $profiles,
			'key_equals_value' => true,
		) );

		//		$op .= '<option value="">Profile</option>';
		//		foreach ( $profiles as $value ) {
		//			$v        = gp_test_input( $value );
		//			$selected = $v == $current_profile ? ' selected="selected"' : '';
		//			$op       .= '<option value="' . $v . '"' . $selected . '>' . $v . '</option>';
		//		}
	}
	$op .= '</select>';
	$op .= '</div>'; // item-inner
	$op .= '</div>'; // item-wrap

	// Diameter
	$op .= '<div class="item-wrap type-select item-width">';
	$op .= '<div class="item-inner select-2-wrapper">';
	$op .= '<select name="diameter" id="tss_diameter">';
	if ( $diameters && is_array( $diameters ) ) {
		asort( $diameters, SORT_NUMERIC );
		$op .= get_select_options( array(
			'placeholder' => 'Diameter',
			'items' => $diameters,
			'key_equals_value' => true,
		) );
	}
	$op .= '</select>';
	$op .= '</div>'; // item-inner

	// button inside the last items wrapper
	// $op .= '<div class="reset-btn-wrap"><button type="button" class="css-reset">[Reset]</button></div>';

	$op .= '</div>'; // item-wrap

	$op .= get_form_reset_button();

	// submit
	$op .= '<div class="item-wrap item-submit">';
	$op .= '<div class="button-1"><button type="submit">Search</button></div>';
	$op .= '</div>'; // item-wrap

	$op .= '</div>'; // form-items

	$op .= '</form>';

	return $op;
}

/**
 * @param array $singles
 * @param null  $force_locale
 *
 * @return array
 */
function get_all_unique_tire_sizes( &$singles = array(), $force_locale = null ) {

	$db = get_database_instance();

	$col_sold_in = $col_discontinued = '';

	if ( $force_locale ) {

	    if ( $force_locale !== APP_LOCALE_CANADA && $force_locale !== APP_LOCALE_US ) {
	        return [];
        }

		$col_sold_in = 'tires.' . DB_Tire::get_column_sold_in( $force_locale );
		$col_discontinued = 'tires.' . DB_Tire::get_column_stock_discontinued( $force_locale );
    }

	$q  = '';
	$q  .= 'SELECT diameter, width, profile ';
	$q  .= 'FROM ' . $db->tires . ' AS tires ';

	if ( $force_locale ) {
	    $q .= "WHERE 1 = 1 ";
	    $q .= "AND $col_sold_in = 1 ";
	    $q .= "AND ( $col_discontinued IS NULL OR $col_discontinued <> 1 ) ";
    }

	$q  .= '';
	$q  .= '';
	$q  .= 'GROUP BY diameter, width, profile ';
	$q  .= 'ORDER BY diameter ASC, width ASC, profile ASC ';

	$q  .= ';';
	$st = $db->pdo->prepare( $q );
	$st->execute();
	$result = $st->fetchAll();

    // some tires have false like 'profile' in database which ends up
    // screwing up our tire size form by making the default profile value
    // an empty string instead of the placeholder. Anyways, this should fix things.
    $result = array_filter( $result, function( $row ) {
        return $row->diameter > 0 && $row->width > 0 && $row->profile > 0;
    } );

	$sizes  = array();

	$singles[ 'profile' ]  = array();
	$singles[ 'width' ]    = array();
	$singles[ 'diameter' ] = array();

	if ( $result ) {
		foreach ( $result as $row ) {

			$profile  = gp_if_set( $row, 'profile' );
			$width    = gp_if_set( $row, 'width' );
			$diameter = gp_if_set( $row, 'diameter' );

			$singles[ 'profile' ][]  = $profile;
			$singles[ 'width' ][]    = $width;
			$singles[ 'diameter' ][] = $diameter;

			$sizes[] = array(
				'profile' => $profile,
				'width' => $width,
				'diameter' => $diameter,
			);
		}
	}

	$singles[ 'profile' ]  = array_unique( $singles[ 'profile' ], SORT_NUMERIC );
	$singles[ 'width' ]    = array_unique( $singles[ 'width' ], SORT_NUMERIC );
	$singles[ 'diameter' ] = array_unique( $singles[ 'diameter' ], SORT_NUMERIC );

	return $sizes;
}

/**
 *
 */
function get_all_tire_widths() {
	return get_all_column_values_from_table( DB_tires, 'width' );
}

/**
 *
 */
function get_all_tire_diameters() {
	return get_all_column_values_from_table( DB_tires, 'diameter' );
}

/**
 *
 */
function get_all_tire_profiles() {
	return get_all_column_values_from_table( DB_tires, 'profile' );
}

/**
 * @param $arr
 */
function array_to_comma_sep_clean( $arr ) {
	$arr = clean_array_recursive( $arr );

	return implode_comma( $arr );
}

/**
 * @param      $table
 * @param      $column
 * @param bool $do_cache
 *
 * @return array
 */
function get_all_column_values_from_table( $table, $column, $do_cache = false, $add_return_cols = array() ) {

	$cache_key = $do_cache ? 'all_' . gp_esc_db_col( $table . '_' . $column, false ) : '';

	if ( $do_cache ) {
		$cache = gp_cache_get( $cache_key );
		if ( $cache ) {
			return $cache;
		}
	}

	$db = get_database_instance();

	// this sanitation is sufficient to prevent sql injection, although
	// we dont expect column or table to come directly from user input,
	$column = gp_esc_db_col( $column );
	$table  = gp_esc_db_table( $table );

	$select      = array(
		$column,
	);
	$return_cols = array( $column );

	if ( $add_return_cols ) {
		$select      = array_merge( $select, $add_return_cols );
		$return_cols = array_merge( $return_cols, $add_return_cols );
	}

	$q  = '';
	$q  .= 'SELECT ' . implode_comma( $select ) . ' ';
	$q  .= 'FROM ' . $table . ' ';
	$q  .= '';
	$q  .= 'GROUP BY ' . $column . ' ';
	$q  .= 'ORDER BY ' . $column . ' ASC ';
	$q  .= ';';
	$st = $db->pdo->prepare( $q );
	$st->execute();
	$result = $st->fetchAll();
	$ret    = array();

	if ( $result && is_array( $result ) ) {
		foreach ( $result as $row ) {

			// if more than 1 return column is specified, we'll return each one in an indexed array
			if ( count( $return_cols ) > 1 ) {

				$arr = array();
				foreach ( $return_cols as $col ) {
					$val         = gp_if_set( $row, $col );
					$arr[ $col ] = $val;
				}
				$ret[] = $arr;

			} else {

				// this needs to return a non-indexed array to not break the entire project
				$val   = gp_if_set( $row, $column );
				$ret[] = $val;
			}
		}
	}

	if ( $do_cache ) {
		gp_cache_set( $cache_key, $ret );
	}

	return $ret;
}

/**
 * @param $slug
 *
 * @return DB_Rim_Brand|null
 */
function get_rim_brand_via_slug( $slug ) {

	$db = get_database_instance();

	// expecting only one row
	$rows = $db->get( $db->rim_brands, array(
		'slug' => $slug,
	), array(
		'slug' => '%s',
	) );

	$row = gp_if_set( $rows, 0 );

	if ( $row ) {
		return new DB_Rim_Brand( $row );
	}

	return null;
}

/**
 * @param $slug
 *
 * @return DB_Tire_Brand|null
 */
function get_tire_brand_via_slug( $slug ) {

	$db = get_database_instance();

	// expecting only one row
	$rows = $db->get( $db->tire_brands, array(
		'slug' => $slug,
	), array(
		'slug' => '%s',
	) );

	$row = gp_if_set( $rows, 0 );

	if ( $row ) {
		return new DB_Tire_Brand( $row );
	}

	return null;
}

/**
 * @return array
 */
function get_tire_brands( $locale = null ) {

	$locale           = app_get_locale_from_locale_or_null( $locale );
	$locale_condition = DB_Product::sql_assert_sold_and_not_discontinued_in_locale( 'tires', $locale );

	$db = get_database_instance();

	$q = '';
	$q .= 'SELECT tire_brands.* ';
	$q .= 'FROM ' . $db->tire_brands . ' AS tire_brands ';
	// inner join products to show only available brands
	$q .= 'INNER JOIN ' . $db->tires . ' AS tires ON tires.brand_id = tire_brands.tire_brand_id AND ' . $locale_condition . ' ';

	$q .= '';
	$q .= '';
	$q .= 'GROUP BY tire_brands.tire_brand_id '; // the primary key
	$q .= 'ORDER BY tire_brands.tire_brand_slug ASC '; // alphabetical

	$q  .= ';';
	$r = $db->get_results( $q, [] );
	$brands = array();

	if ( $r ) {
		foreach ( $r as $row ) {
			$obj = DB_Tire_Brand::create_instance_or_null( $row );
			if ( $obj ) {
				$brands[] = $obj;
			}
		}
	}

	return $brands;
}

/**
 * @return array
 */
function get_rim_brands( $locale = null ) {

	$locale           = app_get_locale_from_locale_or_null( $locale );
	$locale_condition = DB_Product::sql_assert_sold_and_not_discontinued_in_locale( 'rims', $locale );

	$db = get_database_instance();

	$p = array();
	$q = '';
	$q .= 'SELECT rim_brands.* ';
	$q .= 'FROM ' . $db->rim_brands . ' AS rim_brands ';
	// inner join products to show only available brands
	$q .= 'INNER JOIN ' . $db->rims . ' AS rims ON rims.brand_id = rim_brands.rim_brand_id AND ' . $locale_condition . ' ';
	$q .= 'GROUP BY rim_brands.rim_brand_id '; // the primary key
	$q .= 'ORDER BY rim_brands.rim_brand_slug ASC '; // alphabetical

	$q .= ';';

	$r = $db->get_results( $q, $p );

	//	$st = $db->pdo->prepare( $q );
	//	$st->execute();
	//	$r = $st->fetchAll();

	$brands = array();

	if ( $r ) {
		foreach ( $r as $row ) {
			$obj = DB_Rim_Brand::create_instance_or_null( $row );
			if ( $obj ) {
				$brands[] = $obj;
			}
		}
	}

	return $brands;
}

/**
 * @param        $slug
 * @param string $type
 *
 * @return DB_Rim_Brand|DB_Tire_Brand|false
 */
function get_likely_brand_by_slug( $slug, $type = 'rims' ) {

	// this function used to do more, but fairly redundant as of now.
	if ( $type === 'rims' || $type === 'rim' ) {
		return DB_Rim_Brand::get_instance_via_slug( $slug );
	} else if ( $type === 'tires' || $type === 'tire' ) {
		return DB_Tire_Brand::get_instance_via_slug( $slug );
	}
}

/**
 * @param        $slug
 * @param string $type
 */
function get_likely_model_by_slug( $slug, $brand_id, $type = 'rims' ) {

	$db = get_database_instance();

	$by_rim  = false;
	$by_tire = false;
	if ( $type === 'rims' || $type === 'rim' ) {
		$by_rim      = true;
		$table       = $db->rim_models;
		$foreign_key = 'rim_brand_id';
		$slug_col    = 'rim_model_slug';
	} else if ( $type === 'tires' || $type === 'tire' ) {
		$by_tire     = true;
		$table       = $db->tire_models;
		$foreign_key = 'tire_brand_id';
		$slug_col    = 'tire_model_slug';
	} else {
		return false;
	}

	$q  = '';
	$q  .= 'SELECT * ';
	$q  .= 'FROM ' . $table . ' ';
	$q  .= 'WHERE ' . $slug_col . ' = :slug ';
	$q  .= 'AND ' . gp_esc_db_col( $foreign_key ) . ' = :foreign_key_value ';
	$q  .= '';
	$q  .= ';';
	$st = $db->pdo->prepare( $q );
	$st->bindValue( 'slug', $slug, $db->str );
	$st->bindValue( 'foreign_key_value', (int) $brand_id, $db->int );

	if ( ! $st->execute() ) {
		return false;
	}

	if ( ! $obj = $st->fetchObject() ) {
		return false;
	}

	if ( $by_rim ) {
		$db_obj = DB_Rim_Model::create_instance_or_null( $obj );
		if ( $db_obj ) {
			return $db_obj;
		}
	} else if ( $by_tire ) {
		$db_obj = DB_Tire_Model::create_instance_or_null( $obj );
		if ( $db_obj ) {
			return $db_obj;
		}
	}

	return false;
}

/**
 *
 * @param $slug
 * @param $brand_id
 *
 * @return DB_Tire_Model|false
 */
function get_tire_model( $slug, $brand_id ) {
	$val = get_likely_model_by_slug( $slug, $brand_id, 'tire' );

	if ( $val instanceof DB_Tire_Model ) {
		return $val;
	}

	return false;
}

/**
 * @param $slug
 * @param $brand_id
 *
 * @return DB_Rim_Model|false
 */
function get_rim_model( $slug, $brand_id ) {
	$val = get_likely_model_by_slug( $slug, $brand_id, 'rim' );
	if ( $val instanceof DB_Rim_Model ) {
		return $val;
	}

	return false;
}

/**
 * return false if one is already found,
 * otherwise the new primary key if inserted.
 *
 * @param       $slug
 * @param array $data
 *
 * @return bool|mixed
 */
function register_tire_brand( $slug, array $data ) {

	$ex = DB_Tire_Brand::get_instance_via_slug( $slug );

	if ( $ex ) {
		return false;
	}

	$db  = get_database_instance();
	$pre = 'tire_brand_';

	$name = gp_if_set_fallback_remove_prefix( $data, 'name', $pre, '' );

	// Insert into (tire or rim) BRANDS table, a slug and a name
	$new_primary_key = $db->insert( $db->tire_brands, array(
		$pre . 'slug' => $slug,
		$pre . 'name' => $name,
	) );

	return $new_primary_key;
}

/**
 * @param $brand_slug
 * @param $model_slug
 * @return string
 */
function get_suggested_tire_model_image_name( $brand_slug, $model_slug ) {
	$ret = 'tire-model-' . make_slug( $brand_slug ) . '-' . make_slug( $model_slug );

	return $ret;
}

/**
 * return false if one is already found,
 * otherwise the new primary key if inserted.
 *
 * @param       $slug
 * @param       $brand_id
 * @param array $data
 *
 * @return bool|string
 */
function register_tire_model( $slug, $brand_id, array $data ) {

	$ex = DB_Tire_Model::get_instance_by_slug_brand( $slug, $brand_id );

	if ( $ex ) {
		return false;
	}

	$db  = get_database_instance();

    return $db->insert( $db->tire_models, array(
        'tire_model_slug' => $slug,
        'tire_brand_id' => $brand_id,
        'tire_model_name' => @$data['tire_model_name'],
        'tire_model_type' => @$data['tire_model_type'],
        'tire_model_class' => @$data['tire_model_class'],
        'tire_model_category' => @$data['tire_model_category'],
        'tire_model_run_flat' => @$data['tire_model_run_flat'],
        'tire_model_image_new' => @$data['tire_model_image_new'],
    ) );
}

/**
 * inserts if not found.
 *
 * @param       $slug
 * @param array $data
 *
 * @return bool|mixed
 */
function register_rim_brand( $slug, array $data ) {

	$ex = DB_Rim_Brand::get_instance_via_slug( $slug );

	if ( $ex ) {
		return false;
	}

	$db  = get_database_instance();
	$pre = 'rim_brand_';

	$name = gp_if_set_fallback_remove_prefix( $data, 'name', $pre, '' );

	$new_primary_key = $db->insert( $db->rim_brands, array(
		$pre . 'slug' => $slug,
		$pre . 'name' => $name,
	) );

	return $new_primary_key;
}

/**
 * inserts if not found.
 *
 * @param       $slug
 * @param array $data
 *
 * @return bool|mixed
 */
function register_rim_model( $slug, $brand_id, array $data ) {

	$ex = DB_Rim_Model::get_instance_by_slug_brand( $slug, $brand_id );

	if ( $ex ) {
		return false;
	}

	$db  = get_database_instance();
	$pre = 'rim_model_';

	$name = gp_if_set_fallback_remove_prefix( $data, 'name', $pre, '' );

	// Insert into (tire or rim) BRANDS table, a slug and a name
	$new_primary_key = $db->insert( $db->rim_models, array(
		$pre . 'slug' => $slug,
		'rim_brand_id' => $brand_id,
		$pre . 'name' => $name,
	) );

	return $new_primary_key;
}

/**
 * @param     $str
 * @param int $iterations
 */
function ampersand_to_plus( $str ) {

	// double escaping & can cause this..
	while ( strpos( $str, '&amp;amp;' ) !== false ) {
		$str = str_replace( '&amp;amp;', '&amp;', $str );
	}

	$str = str_replace( '&amp;', '&', $str );
	$str = str_replace( '&', '+', $str );

	return $str;
}

/**
 * Checks if multiple keys exist in an array
 *
 * @param array        $array
 * @param array|string $keys
 *
 * @return bool
 */
function array_keys_exist( $array, $keys ) {

	if ( ! is_array( $array ) ) {
		return false;
	}

	$count = 0;
	if ( ! is_array( $keys ) ) {
		$keys = func_get_args();
		array_shift( $keys );
	}
	foreach ( $keys as $key ) {
		if ( isset( $array[ $key ] ) || array_key_exists( $key, $array ) ) {
			$count ++;
		}
	}

	return count( $keys ) === $count;
}

/**
 * @param $arr
 * @param $keys
 */
function unset_array_keys( $arr, $keys ) {
	return array_diff_key( $arr, array_flip( $keys ) );
}

/**
 * return value is an array with indexes equal to array values of $keys passed in
 *
 * @param $source
 * @param $keys
 * @param $prefix
 */
function collect_keys_with_prefix( $source, $keys, $prefix, $default = '' ) {

	$ret = array();

	$source = gp_force_array( $source, true );

	if ( $keys && is_array( $keys ) ) {
		foreach ( $keys as $key ) {
			$find = $prefix . $key;
			if ( isset( $source[ $find ] ) ) {
				$ret[ $key ] = $source[ $find ];
			} else {
				$ret[ $key ] = $default;
			}
		}
	}

	return $ret;
}

/**
 * @param      $raw
 * @param      $name
 * @param null $default
 *
 * @return null
 */
//function get_user_input_textarea_value( $raw, $name, $default = null ) {
//
//	$value = gp_if_set( $raw, $name, $default );
//
//	if ( ! $value )
//		return null;
//
//	$value = gp_force_singular( $value );
//
//	// this calls htmlspecialchars...
//	// so if you want to keep html as is, don't use get_user_input_textarea_value()
//	$value = gp_test_input( $value );
//
//	return $value;
//}

/**
 *
 */
function gp_add_starting_zero( $str ) {

	$map = [
		1 => '01',
		2 => '02',
		3 => '03',
		4 => '04',
		5 => '05',
		6 => '06',
		7 => '07',
		8 => '08',
		9 => '09',
	];

	$str = gp_if_set( $map, $str, $str );
	$str = (string) $str;

	return $str;
}

/**
 * Grab a value from an array. Make sure its singular. Maybe strip tags, then convert html entities
 * etc. in order to make it safe for printing and/or storing to database.
 *
 * If you use $strip_tags = true, some information may be lost. Otherwise, string may be converted
 * to another string, but I don't think any information will be lost.
 *
 * @param      $raw
 * @param      $name
 * @param null $default
 * @param bool $strip_tags
 *
 * @return null|string
 */
function get_user_input_singular_value( $raw, $name, $default = null, $strip_tags = false ) {
	$value = gp_if_set( $raw, $name, $default );

	if ( ! $value ) {
		return null;
	}

	$value = gp_force_singular( $value );

	if ( ! $value ) {
		return null;
	}

	// have to run this before gp_test_input which will convert html entities
	// the idea is that we would rather lose information by using strip tags, than keep it..
	if ( $strip_tags ) {
		$value = strip_tags( $value );
	}

	return gp_test_input( $value );
}

/**
 * @param      $arr
 * @param      $index
 * @param null $default
 * @return mixed|string
 */
function get_array_value_force_singular( $arr, $index, $default = null ) {
	$v   = gp_if_set( $arr, $index, $default );
	$ret = gp_force_singular( $v );

	return $ret;
}

/**
 * Grab a value from user input, make sure its a depth 1 array,
 * then sanitize and return.
 *
 * @param $raw
 * @param $name
 * @return array|null
 */
function get_user_input_array_value( $raw, $name, $convert_singular_to_array = false ) {

	$value = gp_if_set( $raw, $name, array() );

	if ( $convert_singular_to_array && gp_is_singular( $value ) ) {
		$value = array( $value );
	}

	if ( ! gp_is_array_depth_1( $value ) ) {
		return null;
	}

	$ret = array();

	foreach ( $value as $key => $val ) {
		$key         = gp_test_input( $key );
		$val         = gp_test_input( $val );
		$ret[ $key ] = $val;
	}

	return $ret;
}

/**
 * @return string
 */
function get_placeholder_rim_img_url() {
	// return 'http://directautoimport.com/img/p/large/4220475-14511-large.jpg';
}

/**
 * Looks for one or all $indexes found and not null within $arr.
 *
 * If $arr or $index are not arrays or empty, returns false.
 *
 * @param $arr
 * @param $indexes
 * @return bool
 */
function arr_multi_index_isset( $arr, $indexes, $check = 'single' ) {

	if ( ! $arr || ! is_array( $arr ) ) {
		return false;
	}

	if ( ! $indexes || ! is_array( $indexes ) ) {
		return false;
	}

	if ( $check === 'single' ) {

		foreach ( $indexes as $ii ) {
			if ( isset( $arr[ $ii ] ) ) {
				return true;
			}
		}

		return false;

	} else if ( $check === 'all' ) {

		foreach ( $indexes as $ii ) {
			if ( ! isset( $arr[ $ii ] ) ) {
				return false;
			}
		}

		return true;
	}

	throw new Exception ( 'Incorrect usage in variable $check' );
}

/**
 * @param PDOStatement|string $st
 * @param                     $params - ie. [ [ $name, $value, $type ], [...], ... ]
 * @return PDOStatement|string|string[]
 */
function debug_pdo_statement( $st, $params = array() ) {

	if ( $st instanceof PDOStatement ) {
		$str = $st->queryString;
	} else {
		$str = $st;
	}

	if ( $params && is_array( $params ) ) {
		foreach ( $params as $key => $p ) {

			if ( ! gp_is_integer( $key ) && gp_is_singular( $p ) ) {
				$find    = $key;
				$replace = $p;
				$type    = '%s';
			} else {
				$find    = gp_if_set( $p, 0 );
				$replace = gp_if_set( $p, 1 );
				$type    = gp_if_set( $p, 2, '%s' );
			}

			$_replace = $type === '%d' ? $replace : '"' . $replace . '"';

			$find = trim( $find, ':' );
			$find = ':' . $find;

			$str = str_replace( $find, $_replace, $str );
		}
	}

	return $str;
}

/**
 * @param       $array
 * @param array $skip
 * @return array
 */
function get_array_except( $array, $skip = array() ) {

	$ret = array();

	if ( gp_is_singular( $skip ) ) {
		$skip = array( $skip );
	}

	if ( $array && is_array( $array ) ) {
		foreach ( $array as $key => $value ) {

			if ( ! in_array( $key, $skip ) ) {
				$ret[ $key ] = $value;
			}
		}
	}

	return $ret;
}

/**
 * @param        $raw
 * @param        $allowed
 * @param string $default
 * @return array
 */
function get_array_whitelist( $raw, $allowed, $default = '' ) {

	$ret = array();

	if ( $allowed && is_array( $allowed ) ) {
		foreach ( $allowed as $index ) {

			if ( ! gp_is_singular( $index ) ) {
				$val = $default;
			} else {
				$val = gp_if_set( $raw, $index, $default );
			}

			$ret[ $index ] = $val;
		}
	}

	return $ret;
}

/**
 * @param null $vehicle
 * @param null $package_id
 * @param array $items
 * @return array
 */
function get_add_to_cart_partial_args( $vehicle = null, $package_id = null, $items = array() ) {

	$ret = array(
		'ajax_action' => 'add_to_cart',
		'url' => AJAX_URL,
		'nonce' => get_nonce_value( 'add_to_cart' ),
		'type' => 'multi',
	);

	if ( $vehicle instanceof Vehicle ) {

		if ( $vehicle->trim_exists() ) {
			$ret = array_merge( $ret, $vehicle->complete_vehicle_summary_array( false, false ) );
		}

		// this is weird.. we may have to complete this later because now, unlike before, some vehicles
		// are not complete, therefore we need to get the fitment slug from the row
		if ( $vehicle->is_complete() ) {
			$ret[ 'fitment' ] = $vehicle->fitment_object->wheel_set->slug;

			if ( $vehicle->has_substitution_wheel_set() ) {
				$ret[ 'sub' ] = $vehicle->fitment_object->wheel_set->wheel_set_sub->slug;
			}
		}
	}

	if ( $package_id ) {
		$ret[ 'pkg' ] = $package_id;
	}

	// this is often just an empty array to be added to later
	$ret[ 'items' ] = $items;

	return $ret;
}

/**
 * @param       $sql
 * @param       $as
 * @param array $conditions
 * @return string
 */
function sql_inner_join_select( $sql, $as, $conditions = array() ) {

	if ( ! $conditions ) {
		$conditions = array( '1 = 1' );
	}

	if ( gp_is_singular( $conditions ) ) {
		$conditions = array( $conditions );
	}

	$ret = 'INNER JOIN ( ' . $sql . ' ) AS ' . gp_esc_db_col( $as ) . ' ON ' . implode_comma( $conditions );

	return $ret;
}

/**
 * Ie. "( :parameter_prefix_1, :parameter_prefix_2 )"
 *
 * $parameters passed by reference and modified.
 *
 * Example Usage:
 *
 * $list = sql_get_comma_separated_list( [ "dai", "canada-tire-supply" ], $p, '%s', 'supplier_' );
 * $q .= "AND supplier IN ( $list )";
 *
 * Note: parameters added to the array in a way that's compatible with our
 * Database_PDO::bind_params() or w/e the function is. Not compatible
 * directly with PDO basically.
 *
 * @param        $values_array
 * @param        $parameters_ref - passed by reference
 * @param string $type - Use the PDO type constants or %s or %d as aliases of string and int
 * @param string $parameter_prefix - placeholders use this suffexed with a number to make them unique.
 *
 * @return string
 */
function sql_get_comma_separated_list( $values_array, &$parameters_ref, $type = '%s', $parameter_prefix = 'sql_comma_sep_placeholder_' ) {

	assert( is_array( $parameters_ref ) );

	$_array = array_values( $values_array );
	$values = [];

	foreach ( $_array as $count=>$v ) {

		$placeholder = $parameter_prefix . $count;

		$values[] = ':' . ltrim( $placeholder, ':' );
		$parameters_ref[] = [ $placeholder, $v, $type ];
	}

	$_values = implode( ', ', $values );

	return $_values;
}

/**
 * @param $arr
 *
 * @return string
 */
function implode_comma( $arr ) {
	return implode( ', ', $arr );
}

/**
 * @return int
 */
function get_sql_found_rows() {

	$db = get_database_instance();

	// Get the found rows
	// $st = $db->pdo->prepare( 'SELECT FOUND_ROWS() AS rows' );
	$st = $db->pdo->prepare( 'SELECT FOUND_ROWS() AS rowCount' );
	$st->execute();
	$rows = $st->fetchAll( PDO::FETCH_ASSOC );

	// Check if the rows is empty
	if ( empty( $rows ) ) {
		// No rows found
		return 0;
	}

	// Get the rows
	$rows = current( $rows );

	// Return the number of row-s
	return $rows[ 'rowCount' ];
}

/**
 * Calling this is kind of like throwing a fatal error that cannot
 * be caught. Script execution will end.
 *
 * When not in production, the error will be printed. Otherwise,
 * its logged and the script ends.
 *
 * Ideally we should never see these in production, of course, once
 * in a while we will.
 *
 * @param string $msg
 */
function throw_dev_error( $msg = 'Error', $type = '' ) {

	$log = '';
	$log .= '[' . $type . ']' . get_string_for_log( $msg );
	log_data( $log, 'dev-errors-' . date( 'Y-m-d' ) );

	if ( ! IN_PRODUCTION ) {
		echo $msg;
		echo nl2br( "-----------------------  \n" );
		echo '<pre>' . print_r( generate_call_stack_debug(), true ) . '</pre>';
		exit;
	} else {
		echo 'Error';
		exit;
	}
}

/**
 * @param $page
 * @param $per_page
 * @param $found_rows
 * @return array
 */
function get_product_showing_counts( $page, $per_page, $found_rows ) {

	$page       = (int) gp_test_input( $page );
	$per_page   = (int) gp_test_input( $per_page );
	$found_rows = (int) gp_test_input( $found_rows );

	$_page = $page && $page > 1 ? $page : 1;

	// order matters in next few operations
	$min = ( ( $_page - 1 ) * $per_page ) + 1;
	$min = $min <= $found_rows ? $min : $found_rows;
	$max = ( $min + $per_page ) - 1;
	$max = $max <= $found_rows ? $max : $found_rows;

	return array( $min, $max, $found_rows );
}

/**
 * @param array $arr
 *
 * @return bool
 */
function is_array_numerically_indexed( array &$arr, $re_numerate = false, $strict = false ) {

	$compare = array();

	foreach ( $arr as $key => $value ) {
		if ( ! gp_is_integer( $key ) ) {
			return false;
		}

		$compare[] = $value;
	}

	// checks if the original array also had indexes like 0,1,2,3,4....
	// p.s. i'm not 100% sure this works flawlessly
	// (=== may say true with different keys but all indexes numerically)
	if ( $strict ) {
		$ret = $compare === $arr;
	} else {
		$ret = true;
	}

	// re-map the array keys to force the keys to be like 0,1,2,3,4...
	if ( $re_numerate ) {
		$arr = array_values( $arr );
	}

	// be careful if you re-numerate but strict is true. you may get a false return value
	// and yet your array would have changed.
	return $ret;
}

/**
 * This is good for when you want to call foreach() on something, but be able
 * to control whether to do it more than once based on a boolean parameter. Seems
 * really redundant when you put it like that.. but you might not have control
 * over the foreach loop.
 *
 * - Converts singular values into arrays of length 1
 * - ( note: How to handle singular values that are "like" false? return array( => false ) or just false?
 * not something we can decide on easily. Therefore, maybe don't use this fn. if this is a problem ) )
 * - If not allowing multiple, and $value is an array, gets the first value
 * - If allowing multiple and $value is an array, then does nothing.
 *
 * Therefore, you turn singular values into arrays of length 1,
 * and for values that are already arrays, you can leave them untouched or force them to
 * be length 1.
 *
 * @param      $value
 * @param bool $allow_multiple
 *
 * @return array|null
 */
function force_non_indexed_array( $value, $allow_multiple = true ) {

	// get one or multiple values and turn it into an array of 1 or multiple elements...
	// $allow_array can be thought of more as: allow multiple values
	if ( $allow_multiple ) {

		// leaves array untouched, but converts singular value to an array of length 1
		$value = gp_make_array( $value );

	} else {

		// try to extract singular value from an array passed in... and
		// then yes, put that singular value back into an array, but the resulting array
		// we at least know the format of (up to depth 1)
		if ( is_array( $value ) && isset( $value[ 0 ] ) ) {
			$value = $value[ 0 ];
			if ( gp_is_singular( $value ) ) {
				$value = gp_make_array( $value );
			} else {
				return null;
			}
		} else if ( gp_is_singular( $value ) ) {
			$value = gp_make_array( $value );
		} else {
			return null;
		}
	}

	// we actually do need to do this again now..
	$value = gp_make_array( $value );

	return $value;
}

/**
 * @param $arr
 * @return array
 */
function clean_array_recursive( $arr ) {

	// also preserves empty arrays values on recursive calls
	if ( ! $arr ) {
		return $arr;
	}

	$ret = array();
	if ( is_array( $arr ) || is_object( $arr ) ) {
		foreach ( $arr as $key => $value ) {

			$key = gp_test_input( $key );
			if ( gp_is_singular( $value ) ) {
				$value       = gp_test_input( $value );
				$ret[ $key ] = $value;
			} else {
				// converts std object for example
				$value = gp_make_array( $value );
				if ( is_array( $value ) ) {
					$ret[ $key ] = clean_array_recursive( $value );
				}
			}
		}
	}

	return $ret;
}

/**
 * @param       $what
 * @param       $as
 * @param array $on
 *
 * @return string
 */
function sql_inner_join( $what, $as, array $on ) {
	$sql = '';
	$sql .= 'INNER JOIN ' . $what . ' AS ' . $as . ' ON ' . implode( ' AND ', $on );

	return $sql;
}

/**
 * A simple array is like: [ 'singular value', 123, 452 ]
 *
 * Numerically indexed, and every array element is singular.
 *
 * Not simple: [ 'singular value', array(...) ]
 *
 * Not simple: [ 0 => 'singular value', 'index' => 'other value' ]
 *
 * @param array $arr
 * @param null $count
 * @param bool $re_numerate
 * @return bool
 */
function array_is_simple( array &$arr, $count = null, $re_numerate = true ) {

	if ( ! is_array_numerically_indexed( $arr ) ) {
		return false;
	}

	if ( ! gp_is_array_depth_1( $arr ) ) {
		return false;
	}

	if ( $count !== null && count( $arr ) !== $count ) {
		return false;
	}

	// in case we have: [ 0 => 'a', 6 => 'b' ]
	if ( $re_numerate ) {
		$arr = array_values( $arr );
	}

	return true;
}

/**
 * @param array $arr
 * @param array $map
 *
 * @return mixed
 */
function re_map_array_keys( array $arr, array $map_keys ) {
	$ret = array();
	// lets preserve the order as well
	if ( $arr && is_array( $arr ) ) {
		foreach ( $arr as $key => $value ) {
			if ( isset( $map_keys[ $key ] ) ) {
				$ret[ $map_keys[ $key ] ] = $value;
			} else {
				$ret[ $key ] = $value;
			}
		}
	}

	return $ret;
}

/**
 * nicer than the output of debug_backtrace()
 *
 * @param bool $get_string
 *
 * @return array|string
 */
function generate_call_stack_debug( $get_string = true ) {
	$e     = new Exception();
	$trace = explode( "\n", $e->getTraceAsString() );
	// reverse array to make steps line up chronologically
	$trace = array_reverse( $trace );
	array_shift( $trace ); // remove {main}
	array_pop( $trace ); // remove call to this method
	$length = count( $trace );
	$result = array();

	for ( $i = 0; $i < $length; $i ++ ) {
		$result[] = ( $i + 1 ) . ')' . substr( $trace[ $i ], strpos( $trace[ $i ], ' ' ) ); // replace '#someNum' with '$i)', set the right ordering
	}

	if ( ! $get_string ) {
		return $result;
	}

	return "\t" . implode( "\n\t", $result );
}

/**
 * @param bool $str
 * @param bool $results
 */
function print_next_query( $str = true, $results = false ) {
	$db = get_database_instance();
	if ( $str ) {
		$db->print_next_query_string();
	}
	if ( $results ) {
		$db->print_next_query_results();
	}
}

/**
 * @param $arr - array or object so long as object properties are public
 * @param $conditions
 */
function array_meets_conditions( $arr, $conditions, $strict = false ) {

	if ( ! $conditions ) {
		return true;
	}

	// will convert objects via get_object_vars() which will at least get public properties
	$arr = gp_make_array( $arr );

	// assume true, loop until we find a condition that is not met
	if ( $conditions && is_array( $conditions ) ) {
		foreach ( $conditions as $key => $value ) {

			// gp_if_set DOES work with objects (if props are public)
			$array_value = gp_if_set( $arr, $key );

			if ( ! $strict && $value == $array_value ) {
				continue;
			} else if ( $strict && $value == $array_value ) {
				continue;
			}

			return false;
		}
	}

	return true;
}

/**
 * @param $name
 *
 * @return string
 */
function gp_get_icon( $name ) {
	ob_start();
	global $icon_name;
	$icon_name = $name;
	include 'icons.php';
	$icon_name = '';

	return ob_get_clean();
}

/**
 * @param $args
 * @return string
 */
function get_top_image( $args ) {

	$title = gp_if_set( $args, 'title' );
	$img   = gp_if_set( $args, 'img' );

	$img = $img ? get_image_src( $img ) : false;

	// empty string default means use css value, not inline style
	$overlay_opacity = gp_if_set( $args, 'overlay_opacity', '' );

	$cls   = [ 'top-image' ];
	$cls[] = gp_if_set( $args, 'add_class' );

	if ( @$args['right_col_html'] ) {
	    $cls[] = 'two-cols';
    }

    $tag = gp_if_set( $args, 'header_tag', 'h1' );

	$op = '';
	$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';

	if ( @$args['img_tag'] ) {
        $op .= '<div class="background-image standard">';
	    $op .= '<div class="img-tag-cover inherit-size">';
	    $op .= '<img src="' . $img . '" alt="' . @$args['alt'] . '" />';
        $op .= '<div class="overlay" style="' . gp_shortcode_overlay_style( $overlay_opacity ) . '"></div>';
        $op .= '</div>';
        $op .= '</div>';
    } else {
        $op .= '<div class="background-image standard" style="' . gp_get_img_style( $img ) . '"></div>';
        $op .= '<div class="overlay" style="' . gp_shortcode_overlay_style( $overlay_opacity ) . '"></div>';
    }

	$op .= '<div class="y-mid">';
	$op .= '<div class="x-mid">';
	$op .= '<div class="content general-content color-white">';

	if ( $title ) {
        $op .= html_element( $title, $tag, 'like-h1-xxl top-image-title' );
    }

	// a bit smaller than h1, maybe bold
	if ( @$args['tagline'] ) {
	    $op .= '<p class="tagline">' . $args['tagline'] . '</p>';
    }

	// add your own styles, or adjust the tagline tag.
    // sometimes its useful to do h1.tagline here.
    // or h1.tagline.size-sm
    $op .= gp_if_set( $args, 'after_title_html', '' );

	$op .= '</div>';

    if ( @$args['right_col_html'] ) {
        $op .= '<div class="right-col">';
        $op .= $args['right_col_html'];
        $op .= '</div>';
    }

	$op .= '</div>';
	$op .= '</div>';
	$op .= '';
	$op .= '</div>';

	return $op;
}

/**
 * @return int
 */
function get_cart_count() {
	$cart  = get_cart_instance();
	$count = $cart->count_items();

	return $count;
}

/**
 *
 */
function get_cart_count_indicator() {

	$cart  = get_cart_instance();
	$count = $cart->count_items();

	$empty = ( ! $count );
	$cls   = [ 'cart-count-indicator inherit' ];
	$cls[] = $empty ? 'empty' : 'not-empty';

	// maybe or may not show empty count, but we'll print (0) anyways, and then
	// css class can decide whether or not to show it. This also means when we
	// update the cart count via javascript, we'll insert (0) or (4) with brackets, not
	// just the number itself.
	$op = '';
	$op .= '<span class="' . gp_parse_css_classes( $cls ) . '">(' . (int) $count . ')</span>';

	return $op;
}

/**
 * @param $type
 * @param $class
 * @return string
 */
function get_tire_type_and_class_html( $type, $class ) {

	$op   = '';
	$text = '';

	if ( ! $type && ! $class ) {
		return '';
	}

	$op .= '<div class="tire-type-class">';

	if ( $type ) {
		$op   .= '<div class="icon icon-type">' . get_tire_type_icon( $type ) . '</div>';
		$text .= '<p class="text text-type">' . get_tire_type_name( $type ) . '</p>';
	}

	if ( $class ) {
		$op   .= '<div class="icon icon-class">' . get_tire_class_icon( $class ) . '</div>';
		$text .= '<p class="text text-type">' . get_tire_class_name( $class ) . '</p>';
	}

	$op .= '<div class="text-wrap">';
	$op .= $text;
	$op .= '</div>';

	$op .= '';
	$op .= '</div>'; // tire-type-class

	return $op;
}

// Mail
use \PHPMailer\PHPMailer\PHPMailer;

/**
 * Assuming this is already in a try catch block. The reason for this function
 * is because in development the function can echo output which we need to see but
 * we also don't want it to break ajax all the time, and we also don't to capture
 * the output every time.
 *
 * @param PHPMailer $mail
 */
function php_mailer_send( PHPMailer $mail ) {

	ob_start();

	$send = $mail->send();

	$debug = ob_get_clean();

	if ( $debug ) {
		listen_add_ajax_debug( 'php-mailer-send-output', $debug );
		log_data( $debug, 'php-mailer-send-output' );
	}

	return $send;
}

/**
 * @param PHPMailer $mail
 */
function set_php_mailer_smpt( PHPMailer $mail ) {

	if ( SMTP_IS ) {
		$mail->isSMTP();
	} else {
		return;
	}

	$mail->Host       = SMTP_HOST;
	$mail->SMTPAuth   = SMTP_AUTH;
	$mail->Username   = SMTP_USER;
	$mail->Password   = SMTP_PASS;
	$mail->SMTPSecure = SMTP_SECURE; // 'tls' or 'ssl' i think
	$mail->Port       = SMTP_PORT;
}

/**
 * @param $thing
 */
function get_email_array_from_string_or_array( $thing, &$success = false ) {

	if ( gp_is_singular( $thing ) && strpos( $thing, ',' ) !== false ) {
		$arr = explode( ',', $thing );
	} else if ( is_array( $thing ) ) {
		$arr = $thing;
	} else if ( gp_is_singular( $thing ) && $thing ) {
		$arr = [ $thing ];
	} else {
		$arr = [];
	}

	$initial_count = count( $arr );
	$success_count = 0;

	$arr = array_map( function ( $item ) {
	    return trim( $item );
	}, $arr );

	$arr = array_map( function ( $item ) use( &$success_count ) {

	    if ( $item && filter_var( $item, FILTER_VALIDATE_EMAIL ) ){
	        $success_count++;
	        return $item;
        } else{
	        return null;
        }
	}, $arr );

	// in case of a comma at end of string or something...
	$arr = array_filter( $arr );

	// should empty array indicate success? Now we don't know if some emails were invalid
    // or if there was just no email addresses to add.
	$success = $success_count && $success_count === $initial_count;
	return $arr;
}

/**
 * add one email, or array of emails, or comma sep list of emails
 *
 * @param $mail
 * @param $thing
 */
function app_php_mailer_add_addresses( PHPMailer &$mail, $thing ) {

    $arr = get_email_array_from_string_or_array( $thing );

	$success_count = 0;

	array_map( function( $item ) use( &$mail, &$success_count ){
	    if ( filter_var( $item, FILTER_VALIDATE_EMAIL ) ){
	        $success_count++;
	        $mail->addAddress( $item );
        }
    }, $arr );

	return $success_count > 0 && $success_count === count( $arr );
}

/**
 * @return null|PHPMailer
 */
function get_php_mailer_instance( $setup_smtp = true ) {

	$exceptions = ! ( IN_PRODUCTION );

	// errors can be echoed upon ->send() (I think) which can break ajax which makes testing hard.
	$exceptions = false;

	try {
		ob_start();
		$mail = new PHPMailer( $exceptions );

		$mail->SMTPDebug = IN_PRODUCTION ? 0 : 2; // 2 seems to just echo errors, no exceptions or w/e

		// $env = get_website_environment_string();

		// I would recommend to pass this in as true..
		if ( $setup_smtp ) {
			// $mail is object so passed by reference
			set_php_mailer_smpt( $mail );
		}

		$mail->isHTML( true );                                  // Set email format to HTML

		$debug = ob_get_clean();

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			listen_add_ajax_debug( 'smtp', $debug );
		} else {
			queue_dev_alert( 'phpmailer said something', $debug );
		}

	} catch ( Exception $e ) {
		throw $e; // app exception handler will catch
	}

	return $mail;
}

/**
 * @param $brand_slug
 */
function get_rim_brand_logo( $brand ) {

	$brand = make_slug( $brand );

	//	$arr = array(
	//		'braelin' => 'braelin.jpg',
	//		// '720-form' => '720-form.jpg',
	//		'720form' => '720-form.jpg',
	//		'cali-off-road' => 'cali-off-road.jpg',
	//		'art' => 'dai-art-wheels.jpg',
	//		'dip' => 'dip-wheels.jpg',
	//		'dirty-life' => 'dirty-life-wheels.png',
	//		'fast-wheels' => 'fast-wheels.jpg',
	//		'ionbilt' => 'ion-bilt.png',
	//		'ion-trailer' => 'ion-trailer.png',
	//		'ion' => 'ion-wheels.png',
	//		'mayhem' => 'mayhem-wheels.png',
	//		'mazzi' => 'mazzi-wheels.png',
	//		'phatfux' => 'phatfux-wheels.png',
	//		'replika' => 'replika-gif.gif',
	//		'ridler' => 'ridler-wheels.png',
	//		'ruffino' => 'ruffino-wheels.jpg',
	//		'touren' => 'touren-wheels.png',
	//		'steel-acier' => '',
	//	);

	// old script to move images from old loc to new
	//	$src = gp_if_set( $arr, $brand );
	//	$src = $src ? '/logos/rims/' . $src : '';
	//
	//	$brand_obj = DB_Rim_Brand::get_instance_via_slug( $brand );
	//
	//	if ( $src ) {
	//
	//		$base = 'rim-brand-' . $brand;
	//
	//		if ( string_ends_with( $src, 'jpg' ) ) {
	//			$ext = '.jpg';
	//		} else if ( string_ends_with( $src, 'png' ) ) {
	//			$ext = '.png';
	//		} else {
	//			$ext = false;
	//		}
	//
	//		if ( $ext ) {
	//			try{
	//
	//				$url = localize_image( get_image_src( $src ), $base, true );
	//				echo '<pre>' . print_r( $url, true ) . '</pre>';
	//
	//				$brand_obj->update_database_and_re_sync( array(
	//					'rim_brand_logo' => $base . $ext,
	//				));
	//			} catch ( Exception $e ) {
	//				echo '<pre>' . print_r( $e->getMessage(), true ) . '</pre>';
	//			}
	//
	//		}
	//	}

	$db  = get_database_instance();
	$p   = [];
	$q   = '';
	$q   .= 'SELECT rim_brand_logo ';
	$q   .= 'FROM ' . $db->rim_brands . ' ';
	$q   .= 'WHERE rim_brand_slug = :rim_brand_slug ';
	$p[] = [ 'rim_brand_slug', $brand, '%s' ];
	$q   .= ';';

	$results = $db->get_results( $q, $p );
	$row     = gp_array_first( $results );

	$rim_brand_logo = gp_if_set( $row, 'rim_brand_logo' );

	return get_image_src( $rim_brand_logo );
}

/**
 * This is stored in the brand object.. most likely you already have that object and should use it instead.
 *
 * @param $brand_slug
 */
function get_tire_brand_logo( $brand ) {

	$brand = make_slug( $brand );

	//	$arr = array(
	//		'durun' => 'durun-tires.png',
	//		'jinyu' => 'jinyu-tires.png',
	//		'mirage' => 'mirage-tires.png',
	//		'nexen' => 'nexen-tires.png',
	//		'ovation-tires' => 'ovation-tires.png',
	//		'sailun' => 'sailun-tires.png',
	//		'minerva' => 'minerva-tires.png',
	//	);

	$db  = get_database_instance();
	$p   = [];
	$q   = '';
	$q   .= 'SELECT tire_brand_logo ';
	$q   .= 'FROM ' . $db->tire_brands . ' ';
	$q   .= 'WHERE tire_brand_slug = :tire_brand_slug ';
	$p[] = [ 'tire_brand_slug', $brand, '%s' ];
	$q   .= ';';

	$results = $db->get_results( $q, $p );
	$row     = gp_array_first( $results );

	$tire_brand_logo = gp_if_set( $row, 'tire_brand_logo' );

	return get_image_src( $tire_brand_logo );
}

/**
 * a dev alert that shows the SQL string with bound parameters and the count
 * of the results if you pass that in ...
 *
 * @param       $name
 * @param       $sql
 * @param array $params
 * @param array $results
 */
function queue_dev_alert_for_query( $name, $sql, $params = array(), $results = array() ) {
	$count = $results ? count( $results ) : 0;
	queue_dev_alert( $name . ' (' . $count . ')', debug_pdo_statement( $sql, $params ) );
}

/**
 * Queue a dev alert, which means simply to store it in an array.
 *
 * In a live environment, we should definitely never print it.
 *
 * When not live.. we may print it.
 *
 * @param $name
 * @param $content
 */
function queue_dev_alert( $name, $content = '' ) {

	// avoid storing tons of data in an array if its not going to be used for anything.
	if ( IN_PRODUCTION ) {
		return;
	}

	global $dev_alerts;
	$dev_alerts   = $dev_alerts !== null ? $dev_alerts : array();
	$dev_alerts[] = array(
		'name' => $name,
		'content' => $content,
	);
}

/**
 * @return string
 */
function render_dev_alerts() {

	if ( IN_PRODUCTION ) {
		return '';
	}

	$op = '';

	global $dev_alerts;
	if ( $dev_alerts && is_array( $dev_alerts ) ) {
		foreach ( $dev_alerts as $k => $v ) {
			$name    = gp_if_set( $v, 'name' );
			$content = gp_if_set( $v, 'content' );
			$op      .= '<hr>';
			$op      .= get_dev_alert( $name, $content );
			$op      .= '<hr>';
		}
	}

	return $op;
}

/**
 * @param $slug
 *
 * @return string
 */
function get_tire_brand_logo_html( $slug ) {

	$src = get_tire_brand_logo( $slug );
	$op  = '';
	$op  .= '<div class="product-logo type-tire brand-' . gp_test_input( $slug ) . '">';
	$op  .= Background_Image::get( $src, [ 'contain' => true ] );
	// $op  .= '<img src="' . $src . '">';
	$op .= '</div>';

	return $op;
}

/**
 * @param $slug
 *
 * @return string
 */
function get_rim_brand_logo_html( $slug ) {

	$src = get_rim_brand_logo( $slug );
	$op  = '';
	$op  .= '<div class="product-logo type-rim brand-' . gp_test_input( $slug ) . '">';
	$op  .= Background_Image::get( $src, [ 'contain' => true ] );
	// $op  .= '<img src="' . $src . '">';
	$op .= '</div>';

	return $op;
}

/**
 * there is no reason to use this I don't think...
 *
 * @param $brand
 * @param $model
 */
//function get_partial_tire_product( $brand, $model ) {
//
//	$data = array(
//		'brand' => $brand,
//		'brand_name' => $brand,
//		'model' => $model,
//		'model_name' => $model,
//	);
//
//	$partial = new DB_Tire( $data );
//	return $partial;
//}

/**
 * @return string
 */
//function get_oem_no_results_message() {
//	return 'No OEM sizes were found for your vehicle';
//}

/**
 * @return string
 */
//function get_non_oem_no_results_message() {
//	return 'No alternate sizes were found for your vehicle';
//}

/**
 * @param bool $v
 */
function has_no_top_image( $v = true ) {
	set_global( 'has_top_image', ! $v );
}

/**
 * Note: deprecated, access Header::$title directly instead.
 *
 * @param null $str
 *
 * @return bool|mixed
 */
function page_title_is( $str = null ) {

	if ( $str !== null ) {
	    Header::$title = $str;
	}

	return Header::$title;
}

/**
 * Not in use anymore.
 *
 * Adds <link rel="canonical" href="{link}"> tag to the head of the page
 * The {link} value is returned by the get_url() function using the $pageName parameter
 * The GET params are kept in the {link} as well.
 *
 * @param string|null $pageName
 * @return string
 */
function page_canonical_add($pageName = null) {
    $canonical = null;
    if ($pageName !== null) {
        $pageUrl = get_url($pageName);
        if (!empty($_GET)) {
            $pageUrl .= '?' . http_build_query($_GET);
        }
        $canonical = '<link rel="canonical" href="' . $pageUrl . '"/>';
        Header::$head_arr[] = $canonical;
    }
    return $canonical;
}

/**
 * @param      $str
 * @param bool $allow_empty_input_string
 *
 * @return string
 */
function meta_title_add_company_branding( $str, $allow_empty_input_string = false ) {

    if ( ! $allow_empty_input_string && ! $str ) {
        return '';
    }

	return $str . ' | ' . 'tiresource.COM';
}

/**
 * Sets the current "page slug" or returns it. doesn't have to correspond
 * to actual slug in the URL, its just what we call it in the code. This will appear
 * in the <body> classes, and some code (specifically, template files)
 * may need to know what page its being run on.
 *
 * @see Pages::register, pass in a registered page name
 * @see cw_init_pages()
 *
 * @param null $str
 *
 * @return bool|mixed
 */
function page_is( $str = null ) {
	if ( $str !== null ) {
		set_global( '__page', $str );
	}

	return get_global( '__page' );
}

/**
 * Sum up the array values of $data, where the array keys are between $min and $max.
 *
 * In our use case.. $data is an array indexed by the price of products, and its values
 * are the number of products within a query result that have those prices.
 *
 * $min and $max are price values in cents. We want to know how many products are within a certain
 * range, for example, 0-10000, or 10000+
 *
 * @param $min
 * @param $max
 * @param $data
 * @return int|mixed
 */
function sum_array_values_from_keys_within_range( $min, $max, $data ) {

	$count = 0;
	$data  = gp_force_array( $data );

	if ( $min && $max ) {
		foreach ( $data as $key => $value ) {
			if ( $key >= $min && $key <= $max ) {
				$count += $value;
			}
		}
	} else if ( ! $min && $max ) {
		foreach ( $data as $key => $value ) {
			if ( $key <= $max ) {
				$count += $value;
			}
		}
	} else if ( $min && ! $max ) {
		foreach ( $data as $key => $value ) {
			if ( $key >= $min ) {
				$count += $value;
			}
		}
	}

	return $count;
}

/**
 * @param       $cols
 * @param       $data
 * @param array $args
 */
function render_html_table_admin( $cols, $data, $args = array() ) {
    // stupid:
    $add = @$args['add_class'];
    $add = is_array( $add ) ? $add : array_filter( [ $add ] );
    $args['add_class'] = array_filter( array_merge( [ 'admin-table' ], $add ) );

	// if this over cleans the data, pass in $args['sanitize'] = false
	return Html_Table::render( $cols, $data, $args );
}

/**
 *
 */
function render_gallery( $items = array(), $args = array() ) {

	$cls   = [ 'gallery' ];
	$cls[] = gp_if_set( $args, 'add_class' );

	$op = '';
	$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';
	$op .= '<div class="gallery-flex">';

	if ( $items ) {
		foreach ( $items as $item ) {

			$image   = get_user_input_singular_value( $item, 'image' );
			$img_url = get_image_src( $image );
			$caption = get_user_input_singular_value( $item, 'caption' );

			$op .= '<div class="gallery-item">';
			$op .= '<a href="' . $img_url . '" class="gallery-item-2" data-fancybox="gallery" data-caption="' . $caption . '">';
			$op .= '<span class="see-more"><i class="fa fa-plus-circle"></i></span>';
			$op .= '<span class="caption">' . $caption . '</span>';
			$op .= Background_Image::get( $img_url );
			$op .= '</a>';
			$op .= '</div>';

		}
	}

	$op .= '</div>';
	$op .= '</div>';

	return $op;

}

/**
 * @param       $cols
 * @param       $data
 * @param array $args
 */
function render_html_table( $cols, $data, $args = array() ) {
	return Html_Table::render( $cols, $data, $args );
}

/**
 * Class Table_General
 */
Class Html_Table {

	public $args;
	public $cols;

	/**
	 * Table_General constructor.
	 *
	 * @param $cols
	 * @param $args
	 */
	public function __construct() {
	}

	public static function sanitize_key( $k ) {
		$k = gp_test_input( $k );

		return $k;
	}

	/**
	 * $args['sanitize'] needs to be false by default because we often have
	 * html already in $data, like when we link columns to other pages.
	 *
	 * Therefore: make sure you sanitize your data, or pass in $args['sanitize'] = true
	 *
	 * @param $cols
	 * @param $data
	 * @param $args - Make sure to set $args['callback']
	 *
	 * @return string
	 */
	public static function render( $cols, $data, $args ) {

		$title = gp_if_set( $args, 'title' );

		// insert a count column at the beginning
		$add_count = gp_if_set( $args, 'add_count' );
		if ( $add_count ) {

			$count = 0;

			$data = gp_make_array( $data );
			$data = array_values( $data );

			$data = array_map( function ( $row ) use ( &$count ) {

				$count ++;

				// often stdClass
				$row = gp_make_array( $row );

				$row = $row ? array_merge( [ 'count' => $count ], $row ) : $row;

				return $row;

			}, $data );
		}

		// do this after possibly adding ['count'] to the $data
		if ( ! $cols && $data ) {
			$first = gp_if_set( array_values( $data ), 0 );

			$_d   = gp_make_array( $first );
			$cols = array_keys( $_d );
		}

		$cls   = [ 'table-wrap table-style-1' ];
		$cls[] = gp_if_set( $args, 'add_class' );

		$op = '';
		// .table-wrap
		$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';

		// might use this for pagination
		$op .= gp_if_set( $args, 'before_table', '' );

		if ( $title ) {
			$op .= '<p class="table-title">' . $title . '</p>';
		}

		$op .= '<table>';

		$callback = gp_if_set( $args, 'callback', '' );

		// must default to false as we often pass in html
		$sanitize = gp_if_set( $args, 'sanitize', false );

		$op .= self::get_header_row( $cols );

		if ( $data && is_array( $data ) ) {
			foreach ( $data as $row_key => $row_data ) {

				$op .= '<tr>';

				if ( $cols && is_array( $cols ) ) {
					foreach ( $cols as $col_key => $col_name ) {

						if ( gp_is_integer( $col_key ) && ! gp_is_integer( $col_name ) ) {
							$col_key = $col_name;
						}

						$col_key = self::sanitize_key( $col_key );

						// pass in column key and the entire row. the callback will have to
						// extract the data and return it. I guess add the $args as well.
						if ( $callback && is_callable( $callback ) ) {
							$cell_data = call_user_func_array( $callback, [ $col_key, $row_data, $args ] );
						} else {

                            $cell_data = gp_if_set( $row_data, $col_key );

                            if ( $sanitize ) {
                                if ( isset( $args['skip_sanitize'] ) && in_array( $col_key, $args['skip_sanitize'] ) ) {
                                    // do nothing
                                } else {
                                    $cell_data = gp_test_input( $cell_data );
                                }
                            }
						}

						$op .= '<td class="cell-' . $col_key . '">';
						$op .= gp_make_singular( $cell_data );
						$op .= '</td>';
					}
				}

				$op .= '</tr>';
			}
		}

		$op .= '</table>';

		// currently not using this, just in place because we have 'before_table'
		$op .= gp_if_set( $args, 'after_table', '' );

		$op .= '</div>';

		return $op;
	}

	/**
	 * @param $cols
	 */
	public static function get_header_row( $cols ) {

		$op = '';

		if ( $cols && is_array( $cols ) ) {
			foreach ( $cols as $c1 => $c2 ) {
				$c1 = self::sanitize_key( $c1 );
				$op .= '<th class="cell-' . $c1 . '">';
				$op .= $c2;
				$op .= '</th>';
			}
		}

		return $op;
	}
}

/**
 * @param null $format
 * @param int  $days_past
 *
 * @return false|string
 */
function random_date_past( $format = null, $days_past = 100 ) {
	$format = $format ? $format : get_database_date_format();
	$ret    = date( get_database_date_format(), rand( time() - ( 86400 * $days_past ), time() ) );

	return $ret;
}

/**
 * @param $subject
 * @param $ends_with
 */
function string_ends_with( $subject, $ends_with ) {

	$len     = strlen( $subject );
	$len_end = strlen( $ends_with );

	$end = substr( $subject, $len - $len_end, $len_end );

	if ( $end == $ends_with ) {
		return true;
	}

	return false;
}

/**
 * @param      $str
 * @param bool $floor
 *
 * @return bool|string
 */
function string_half( $str, $floor = true ) {

	if ( ! gp_is_singular( $str ) ) {
		return false;
	}

	if ( strlen( $str > 1 ) ) {
		$half_length = strlen( $str ) / 2;
		$end         = $floor ? floor( $half_length ) : ceil( $half_length );
		$ret         = substr( $str, 0, $end );

		return $ret;
	}

	return false;
}

/**
 * @param $table
 *
 * @return bool
 */
function drop_and_create_table( $table, $disable_foreign_key_constraints = false ) {

	$class = DB_Table::create_empty_instance_from_table( $table );

	if ( $class ) {
		drop_table( $class->get_table(), $disable_foreign_key_constraints );
		$class::db_init_create_table_if_not_exists();

		return true;
	}

	throw_dev_error( 'invalid table or db model object does not exist' );

	return false;
}

/**
 * returns true only if table was deleted, which doesn't happen if it didnt exist.
 *
 * @param bool $are_you_sure
 */
function drop_table( $table = '', $disable_foreign_key_constraints = false ) {

	//	if ( IN_PRODUCTION ) {
	//		throw_dev_error( 'This is turned off in production' );
	//		return;
	//	}

	$db = get_database_instance();

	// apparently this is session based only ??? so..
	// if the query below fails it shouldn't shut off all foreign key checks forever
	if ( $disable_foreign_key_constraints ) {
		$db->pdo->prepare( 'SET FOREIGN_KEY_CHECKS=0;' )->execute();
	}

	$p = [];
	$q = '';
	$q .= 'DROP TABLE ' . gp_esc_db_col( $table );
	$q .= ';';

	$st = $db->pdo->prepare( $q );
	$db->bind_params( $st, $p );

	$success = false;

	// exception is thrown if table doesn't exist
	try {
		$st->execute();
		$success = true;
	} catch ( Exception $e ) {
		$success = false;
	}

	if ( $disable_foreign_key_constraints ) {
		$db->pdo->prepare( 'SET FOREIGN_KEY_CHECKS=1;' )->execute();
	}

	return $success;

}

/**
 * @param $args
 *
 * @return string
 */
function get_sql_order_by_from_array( $args ) {

	if ( is_array( $args ) ) {
		$args = implode_comma( $args );
	}

	if ( ! gp_is_singular( $args ) ) {
		return '';
	}

	$ret  = '';
	$args = trim( $args );

	if ( $args ) {
		$ret = 'ORDER BY ' . $args;
	}

	return $ret;
}

/**
 * @param $locale
 *
 * @return string
 */
function get_postal_code_label( $locale ) {
	$ret = $locale === 'US' ? 'Zip Code' : 'Postal Code';

	return $ret;
}

function get_street_extra_text() {
	return 'Apt, Suite, Etc.';
}

/**
 * @param $locale
 *
 * @return string
 */
function get_province_label( $locale ) {
	$ret = $locale === 'US' ? 'State' : 'Province';

	return $ret;
}

/**
 *
 */
function empty_cart( $locale = null ) {

	$locale = $locale === null ? app_get_locale() : $locale;

	if ( $locale == 'CA' ) {
		$_SESSION[ SESSION_CART_CA ] = array();

		return true;
	}

	if ( $locale == 'US' ) {
		$_SESSION[ SESSION_CART_US ] = array();

		return true;
	}

	return false;
}

/**
 * $msg will get printed directly to the screen without any sanitation, so
 * ensure it does not contain sensitive information or untrusted user input.
 * I cannot sanitize because its possible we'd want to put a link in
 * it at some point.
 *
 * @param null $title
 * @param null $msg
 */
function show_404( $title = null, $msg = null ) {

	if ( $title ) {
		gp_set_global( '404_title', $title );
	}

	if ( $msg ) {
		gp_set_global( '404_msg', $msg );
	}

	//	http_response_code( 404);
	//	header("HTTP/1.0 404 Not Found");
	http_response_code( 404 );
	// header( 'location: ' . BASE_URL . '/404.php', true, 404 );

    header("HTTP/1.0 404 Not Found");

	include CORE_DIR . '/pages/404.php';
	exit;
}

/**
 *
 */
function cw_redirect_home(){
	$loc = BASE_URL;
	header( "Location: $loc", true, 302 );
	exit;
}

/**
 *
 */
function get_policy_sub_nav() {

	$items = array();

	//	$items[] = array(
	//		'page' => 'terms',
	//		'label' => 'Terms And Conditions',
	//	);

	$items[] = array(
		'page' => 'privacy_policy',
		'label' => 'Privacy Policy',
	);

	$items[] = array(
		'page' => 'return_policy',
		'label' => 'Return Policy',
	);

	$items[] = array(
		'page' => 'warranty_policy',
		'label' => 'Warranty Policy',
	);

	$items[] = array(
		'page' => 'shipping_policy',
		'label' => 'Shipping Policy',
	);

	$items[] = array(
		'page' => 'fitment_policy',
		'label' => 'Fitment Policy',
	);

	//	$items[] = array(
	//		'page' => 'warranty_policy',
	//		'label' => 'Warranty Policy',
	//	);

	$op = '';
	$op .= '<div class="policy-sub-nav">';
	$op .= '<ul>';

	foreach ( $items as $item ) {

		$page  = gp_if_set( $item, 'page' );
		$label = gp_if_set( $item, 'label' );

		$cls = [];
		if ( $page && Router::$current_page == $page ) {
			$cls[] = 'current-item';
		}

		$op .= '<li class="' . gp_parse_css_classes( $cls ) . '">';
		$op .= '<a href="' . get_url( $page ) . '">' . $label . '</a>';
		$op .= '</li>';
	}

	$op .= '</ul>';
	$op .= '</div>';

	return $op;
}

/**
 * @return string
 */
function image_not_available() {
	return get_image_src( 'no-image-2.png' );
	// return get_image_src( 'image-not-available.png' );
}

/**
 *
 */
function random_rim_image_url() {

	$images = array(
		'closeup-tire-sm.jpg',
	);

	$rnd = $images[ array_rand( $images ) ];

	return get_image_src( $rnd );
}

/**
 * @return string
 */
function random_tire_image_url() {

	$images = array(
		'Nexen CP 671.jpg',
		'Sailun Terramax AT.jpg',
		'Durun M626.jpg',
		'tire-square.jpg',
	);

	$rnd = $images[ array_rand( $images ) ];

	return get_image_src( $rnd );
}

/**
 * https://dzone.com/articles/get-country-ip-address-php
 *
 * @return array
 */
function getLocationInfoByIp() {
	$client  = @$_SERVER[ 'HTTP_CLIENT_IP' ];
	$forward = @$_SERVER[ 'HTTP_X_FORWARDED_FOR' ];
	$remote  = @$_SERVER[ 'REMOTE_ADDR' ];
	$result  = array( 'country' => '', 'city' => '' );
	if ( filter_var( $client, FILTER_VALIDATE_IP ) ) {
		$ip = $client;
	} elseif ( filter_var( $forward, FILTER_VALIDATE_IP ) ) {
		$ip = $forward;
	} else {
		$ip = $remote;
	}
	$ip_data = @json_decode( file_get_contents( "http://www.geoplugin.net/json.gp?ip=" . $ip ) );
	if ( $ip_data && $ip_data->geoplugin_countryName != null ) {
		$result[ 'country' ] = $ip_data->geoplugin_countryCode;
		$result[ 'city' ]    = $ip_data->geoplugin_city;
	}

	return $result;
}

/**
 * A message that we may show to a user when they first add an
 * item to the cart, or when their cart goes from not-empty to empty,
 * or possibly at some other time. We just want to let me know about the shipping
 * region and make sure they have selected the correct one.
 */
function get_add_to_cart_locale_alert() {

	if ( DISABLE_LOCALES ) {
		return '';
	}

	$op = '';

	switch( app_get_locale() ) {
        case APP_LOCALE_CANADA:
	        $op .= "Due to your currently selected shipping region, items added to your cart can only be shipped to Canada. If you require shipping to the United States then please set your shipping region using the flags at the top right of your screen.";
            break;
		case APP_LOCALE_US:
			$op .= "Due to your currently selected shipping region, items added to your cart can only be shipped to the United States. If you require shipping to Canada then please set your shipping region using the flags at the top right of your screen.";
			break;
    }

	return $op;
}

/**
 * @param $string
 * @param string $index
 * @return bool|mixed
 */
function get_path_info( $string, $index = 'filename' ) {

	$string = gp_force_singular( $string );
	$string = trim( $string );

	$path_info = pathinfo( $string );
	$ret       = gp_if_set( $path_info, $index );

	// ie..
	//	$path_info['dirname'];
	//	$path_info['basename'];
	//	$path_info['extension'];
	//	$path_info['filename'];

	return $ret;
}

/**
 * Does what it says. This cannot over clean the file. For example, we may
 * need to run user input through this in such a way that it targets an existing file
 * on the server to be overwritten. Therefore, we can't just return something url friendly.
 * We only eliminate dots and slashes so we have an idea of what directory it will end up in.
 *
 * @param $string
 *
 * @return bool|mixed
 */
function strip_file_ext_and_clean_dots_slashes( $string ) {

	if ( strpos( $string, '.' ) !== false ) {
		$string = get_path_info( $string, 'filename' );
	}

	// NOTE: get_path_info can still returns dots in file name, it just gets rid of the stuff after the last dot.
	$string = str_replace( '.', '', $string );
	$string = str_replace( '/', '', $string );

	return $string;
}

/**
 * This is for admin users only. Not going through the steps to ensure its secure enough for
 * random ass people to put files in a public facing directory.
 *
 * @param $url
 * @param $file_name
 * @return string
 */
function localize_image( $url, $file_name, $override = false, &$resulting_image_name = '' ) {

	if ( ! cw_is_admin_logged_in() ) {
		throw new User_Exception( 'For admins only' );
	}

	$url = trim( $url );

	// not saying that FTP works..
	$req = array( 'http://', 'https://', 'ftp://' );

	$pass = false;
	foreach ( $req as $r ) {
		if ( strpos( $url, $r ) !== false ) {
			$pass = true;
		}
	}

	if ( ! $pass ) {
		// we're just testing FTP, no reason to indicate to the user that FTP is all of the sudden supported.
		// throw new User_Exception( 'Image url requires one of: ' . implode_comma( $req ) );
		throw new User_Exception( 'Image url requires http:// or https://' );
	}

	$_url = $url;

	// ie. allow https:.../image.jpg?rep=False
	if ( strpos( $_url, '?' ) !== false ) {
		$__url = explode( '?', $_url );
		$_url  = gp_if_set( $__url, 0 );
	}

	if ( string_ends_with( $_url, '.jpg' ) ) {
		$ext = 'jpg';
	} else if ( string_ends_with( $_url, '.png' ) ) {
		$ext = 'png';
	} else if ( string_ends_with( $_url, '.jpeg' ) ) {
		$ext = 'jpeg';
	} else {
		throw new User_Exception( 'Image extension of URL should be .png, .jpg, or .jpeg' );
	}

	// ignore extension if its passed in...
	$file_name = trim( $file_name );
	$file_name = strip_file_ext_and_clean_dots_slashes( $file_name );

	// don't want dots or slashes when we do file_exists() and fopen()
	if ( strpos( $file_name, '.' ) ) {
		throw new User_Exception( '. is not allowed in file name.' );
	}

	if ( strpos( $file_name, '/' ) ) {
		throw new User_Exception( '/ is not allowed in file name.' );
	}

	// eliminate spaces and all other illegal characters etc.
	$file_name = make_slug( $file_name, true );

	$file_name_no_ext = $file_name;

	// add the file extension back after cleaning
	$file_name = $file_name . '.' . $ext;

	$target_file = BASE_DIR . '/assets/images/' . $file_name;
	$file_url    = BASE_URL . '/assets/images/' . $file_name;

	// add --{number} to end of file name
	// NOTE: do we want to do this?
	// My concern with overriding files is that a developer will make some little changes and
	// then re-upload the website, including the images folder. If we make the system rely on
	// overriding existing files when, for example, downloading rim images, then this could undo
	// several years of updating images from product imports. In general, its not a good idea to override file.
	// but now we have another issue... after we upload a new image, wouldn't it be best to delete the old file?
	// ffs I don't know. there is no guarantee that the file is not used elsewhere so...
	if ( $override === 'APPEND' ) {

		$c = 0;
		while ( file_exists( BASE_DIR . '/assets/images/' . $file_name ) ) {

			$c ++;

			// append something so its hopefully easy for us to identify images
			// that may no longer be needed at some point in the future
			$file_name = $file_name_no_ext . '--v' . $c . '.' . $ext;

			// necessary ?
			if ( $c > 10000 ) {
				throw new Exception( 'too many files with similar name' );
			}
		}

		// re-declare these with updated file name
		$target_file = BASE_DIR . '/assets/images/' . $file_name;
		$file_url    = BASE_URL . '/assets/images/' . $file_name;

	} else if ( ! $override ) {

		// check file exists unless we want to override existing file
		if ( file_exists( $target_file ) ) {
			throw new User_Exception( 'A file with that name already exists (' . $file_url . ')' );
		}
	}

	// curl to get image file (can take half a second or more)
	$curl        = new Curl\Curl();
	$file_handle = fopen( $target_file, 'w+' );
	$curl->setOpt( CURLOPT_FILE, $file_handle );
	$curl->get( $url );

	$error = false;

	if ( ! $curl->http_status_code ) {
		$error = 'Invalid http response code (file not found).';
	}

	if ( $curl->http_status_code < 200 || $curl->http_status_code >= 400 ) {
		$error = 'Invalid http response code (file not found).';
	}

	if ( $curl->error ) {
		$error = $curl->error_message ? $curl->error_message : 'error';
	}

	// disable writing to file
	$curl->setOpt( CURLOPT_FILE, null );

	if ( $error ) {

		// must delete empty file
		fclose( $file_handle );
		if ( file_exists( $target_file ) ) {
			unlink( $target_file );
		}

		throw new Exception( $error . get_pre_print_r( $curl ) );

	} else {

		// close the file for writing
		fclose( $file_handle );
	}

	// passed by reference, in case you need name like "image.jpg" and not the full URL
	// note: we'll only store names for most products, storing full URL is not needed.
	$resulting_image_name = $file_name;

	return $file_url;
}

/**
 * Some security checks will be in place but i'm not 100% sure this is safe for unauthenticated users.
 *
 * @param $file - ie. $_FILES['image']
 *
 * @return string - url to image on success
 * @throws Exception
 * @throws User_Exception
 */
function upload_image_from_file_with_admin_user( $file, $file_name = null, $override = false ) {

	if ( ! cw_is_admin_logged_in() ) {
		throw new Exception( 'Admin users only.' );
	}

	if ( ! $file ) {
		throw new User_Exception( 'No file' );
	}

	// ie. hai.jpg
	$name = gp_if_set( $file, 'name' );

	// image/jpeg
	$type = gp_if_set( $file, 'type' );

	// C:\xampp\tmp\php5FB0.tmp
	$tmp_name = gp_if_set( $file, 'tmp_name' );

	// 0
	$error = gp_if_set( $file, 'error', 'no_file' );

	// 23586
	$size = gp_if_set( $file, 'size' );

	// file name without extension
	if ( $file_name === null ) {
		$file_name = get_path_info( $name, 'filename' );
		// same as in "else". debatable whether this is needed here
		$file_name = trim( $file_name );
		$file_name = strip_file_ext_and_clean_dots_slashes( $file_name );
	} else {
		// this is def. needed. if user specified a file name, ignore any extension they provide, or
		// additional dots in the file name that could change the directory it ends up in..
		$file_name = trim( $file_name );
		$file_name = strip_file_ext_and_clean_dots_slashes( $file_name );
	}

	if ( ! $file_name ) {
		throw new Exception( 'Invalid file name' );
	}

	if ( strpos( $file_name, '.' ) !== false ) {
		throw new Exception( 'File name cannot have "."' );
	}

	if ( strpos( $file_name, '/' ) !== false ) {
		throw new Exception( 'File name cannot have "/"' );
	}

	if ( ! $size || ! $name || ! $tmp_name ) {
		throw new User_Exception( 'Size/name/tmp_name failed' );
	}

	if ( $error ) {
		throw new User_Exception( 'PHP File error: ' . $error );
	}

	// this does not guarantee file is safe
	if ( ! getimagesize( $tmp_name ) ) {
		throw new User_Exception( 'File might not be a valid image.' );
	}

	$ext = get_path_info( $name, 'extension' );

	if ( ! in_array( $ext, [ 'png', 'jpg', 'jpeg' ] ) ) {
		throw new User_Exception( 'File extension should be .jpg/jpeg or .png' );
	}

	// ie. F://..../assets/images/
	$uploads_dir = IMAGES_DIR;

	// ie. http:///.... /assets/images/
	$uploads_url = IMAGES_URL;

	$file_name_ext = $file_name . '.' . $ext;
	$destination   = $uploads_dir . '/' . $file_name . '.' . $ext;

	if ( file_exists( $destination ) ) {

		if ( $override ) {
			//  here's one reason to disable non-admin users
			unlink( $destination );
		} else {
			throw new Exception( 'File name already taken (' . gp_test_input( $file_name_ext ) . ').' );
		}
	}

	// put the file in the publicly accessible directory
	$move = move_uploaded_file( $tmp_name, $destination );

	if ( ! $move ) {
		throw new User_Exception( 'File appears valid but could not be moved.' );
	}

	// return url to the file
	return $uploads_url . '/' . $file_name . '.' . $ext;
}

/**
 * find jpegs and pngs located in BASE_DIR . '/assets/images/
 *
 * @param bool $as_url
 *
 * @return array
 */
function get_image_urls( $as_url = true ) {

	$dir    = BASE_DIR . '/assets/images/';
	$images = array();
	// star returns FILES apparently.. not just any random wildcard. meaning
	// images/logo's shouldn't show up here..
	foreach ( glob( $dir . '*.{jpg,JPG,jpeg,JPEG,png,PNG}', GLOB_BRACE ) as $file ) {

		if ( $as_url ) {
			$images[] = BASE_URL . '/assets/images/' . basename( $file );
		} else {
			$images[] = $file;
		}
	}

	return $images;
}

/**
 * get the suggested filename for storing, this is not for getting
 * the url.
 *
 * @param $brand_slug
 * @param $model_slug
 * @param $color_1
 * @param $color_2
 * @param $finish
 * @return string
 */
function get_rim_image_suggested_filename( $brand_slug, $model_slug, $color_1, $color_2, $finish ) {

	$arr = array(
		$brand_slug,
		$model_slug,
		$color_1,
		$color_2,
		$finish
	);

	foreach ( $arr as $k => $v ) {
		$arr[ $k ] = make_slug( $v );

		if ( ! $arr[ $k ] ) {
			unset( $arr[ $k ] );
		}
	}

	$str = implode( '_', $arr );

	return $str;
}

/**
 * @param $str
 *
 * @return string
 */
function format_percent_string( $str, $df = '?%' ) {

	if ( $str === null || $str === false || $str === '' ) {
		return $df;
	}

	if ( $str >= 0 ) {
		return '+' . $str . '%';
	}

	return $str . '%';
}

/**
 * @param $name
 *
 * @return bool|string
 */
function image_file_exists_from_name( $name ) {

	if ( file_exists( IMAGES_DIR . '/' . $name . '.png' ) ) {
		$ret = $name . '.png';
	} else if ( file_exists( IMAGES_DIR . '/' . $name . '.jpg' ) ) {
		$ret = $name . '.jpg';
	} else {
		return false;
	}

	return $ret;
}

/**
 * not for production
 */
function brand_logos_db_init( $is_tire = true ) {

	echo 'shut off';
	exit;

	$table = $is_tire ? DB_tire_brands : DB_rim_brands;

	$db = get_database_instance();
	$p  = [];
	$q  = '';
	$q  .= 'SELECT * ';
	$q  .= 'FROM ' . $table . ' ';
	$q  .= '';
	$q  .= ';';

	$results = $db->get_results( $q, $p );

	foreach ( $results as $row ) {

		$obj = $is_tire ? DB_Tire_Brand::create_instance_or_null( $row ) : DB_Rim_Brand::create_instance_or_null( $row );

		if ( $is_tire ) {
			$_logo = 'tire-brand-' . $obj->get( 'slug' );
		} else {
			$_logo = 'rim-brand-' . $obj->get( 'slug' );
		}

		$__logo = image_file_exists_from_name( $_logo );

		if ( $__logo ) {

			$update = $is_tire ? array(
				'tire_brand_logo' => $__logo,
			) : array(
				'rim_brand_logo' => $__logo,
			);

			$updated = $obj->update_database_and_re_sync( $update );

			if ( $updated ) {
				echo nl2br( "-----------------------  \n" );
				echo $__logo;
			}
		}
	}
}


/**
 * can run this when starting with fresh database..
 */
function update_empty_rim_brand_logos() {

	$db = get_database_instance();
	$p  = [];
	$q  = '';
	$q  .= 'SELECT * ';
	$q  .= 'FROM ' . $db->rim_brands . ' ';
	$q  .= '';
	$q  .= ';';

	$results = $db->get_results( $q, $p );

	foreach ( $results as $row ) {

		$obj = DB_Rim_Brand::create_instance_or_null( $row );

		$logo = $obj->get( 'logo' );

		if ( ! $logo ) {

			$_logo = 'rim-brand-' . $obj->get( 'rim_brand_slug' );

			$updated = $obj->update_database_and_re_sync( array(
				'rim_brand_logo' => $_logo,
			) );

			if ( $updated ) {
				echo 'updated';
			} else {
				echo 'not updated';
			}
		} else {
			echo 'had logo already';
		}
	}
}

/**
 * @param $a1
 * @param $a2
 * @return array
 */
function _array_merge( $a1, $a2 ) {

	if ( is_array( $a1 ) && is_array( $a2 ) ) {
		$a1 = $a2 ? array_merge( $a1, $a2 ) : $a1;

		return $a1;
	}

	return $a1;
}

/**
 * might not be using this. might over sanitize data.
 *
 * @return string
 */
function get_current_url() {
	$base = full_path();
	$args = gp_sanitize_array_depth_1( $_GET );

	return cw_add_query_arg( $args, $base );
}

/**
 * does not make the "$thing" clean by any means.
 *
 * it just means, the user wanted you to think that what they are providing is a URL.
 *
 * For example, we'll use this when an admin provides as an image "thing".
 *
 * If its got http in it, we'll say its a URL, if not, we'll assume its an image name
 * that should be found inside of IMAGES_URL.
 *
 * @param $thing
 *
 * @return bool
 */
function is_url_not_strict( $thing ) {

	if ( ! gp_is_singular( $thing ) ) {
		return false;
	}

	$thing = trim( $thing );

	if ( strpos( $thing, 'http://' ) === 0 ) {
		return true;
	}

	if ( strpos( $thing, 'https://' ) === 0 ) {
		return true;
	}

	return false;
}

/**
 * Does a strict check to see if $thing is a URL.
 *
 * @param $thing
 * @return bool
 */
function is_url( $thing ) {

	if ( ! gp_is_singular( $thing ) ) {
		return false;
	}

	$thing = trim( $thing );

	$ret = filter_var( $thing, FILTER_VALIDATE_URL ) !== false;

	return $ret;
}

/**
 * laravel has this fn..
 *
 * This causes an error... new Object()->do_something(), but..
 *
 * with ( new Object() )->do_something() does not
 *
 * @param $v
 *
 * @return mixed
 */
function with( $v ) {
	return $v;
}

/**
 *
 */
function file_get_contents_curl( $url ) {

	$curl = new Curl\Curl();
	$curl->get( $url );

	if ( $curl->error ) {
		return false;
	}

	return $curl->response;
}

/**
 * Html used on cart and checkout page.
 */
function get_cart_and_checkout_top_content( $title = '' ) {

	ob_start();
	?>
    <div class="cs-left">
        <h1 class="like-h1"><?php echo $title; ?></h1>
        <p>Your order will be processed on our secure servers.</p>
        <p>If you have any questions please see our <a href="<?php echo get_url( 'faq' ); ?>">FAQ</a></p>
    </div>
    <div class="cs-right">
		<?php
		if ( false ) {
			// at the time of turning this off, nothing has to be done to turn it back on in relation to styles
			// ... but change the phone number first.
			?>
            <p class="help">Need Some Help? Call Us</p>
            <p><a href="tel:18771231234" class="help red">1-877-123-1234</a></p>
		<?php } ?>
    </div>
	<?php
	return ob_get_clean();
}

/**
 * We'll put this in ajax response $response, as:
 *
 * $response['actions'] = array( 'action' => 'lightbox', 'content' => this_functions_output(), etc. )
 *
 * @return string
 */
function get_mount_balance_disclaimer_lightbox_inner_html() {
	$op = '';
	$op .= '<div class="general-content text-align-center">';
	$op .= '<h2>Mount & Balance Notice</h2>';
	$op .= '<p>' . get_mount_balance_disclaimer() . '</p>';
	$op .= '<div class="button-1 align-center"><button class="css-reset lb-close">Ok</button></div>';
	$op .= '</div>';

	return $op;
}

/**
 *
 */
function get_mount_balance_disclaimer() {
	$msg = '';
	$msg .= 'Your vehicle may be equipped with TPMS Sensors.  If you care to have TPMS Sensors installed in your wheels please deselect the Mounting and Balancing option so you can have them installed at a later date.  tiresource does not sell TPMS Sensors due to additional programming that may need to be done with the vehicle present.';

	return $msg;
}

/**
 * $target is likely $_GET. We'll use this to say something like:
 *
 * "possible filters that might be affecting your search query: cost=25, time=123712312",
 *
 * this also must return false if $target is empty after removing keys of $skip.
 *
 * this needs to prevent XXS. Its output is printed directly to html.
 *
 * @param       $target
 * @param array $skip
 *
 * @return string
 */
function get_cleaned_array_key_values_pairs_in_text( $target, $skip = array() ) {

	if ( $skip ) {
		$target = get_array_except( $target, $skip );
	}

	$target       = clean_array_recursive( $target );
	$intermediate = array();

	if ( $target && is_array( $target ) ) {
		foreach ( $target as $k => $v ) {
			// make it look kind of like $_GET because that's probably what $target is
			$intermediate[] = $k . '=' . $v;
		}
	}

	$ret = $intermediate ? implode_comma( $intermediate ) : '';

	return $ret;
}

/**
 *
 */
function get_admin_edit_page_possible_filters_text( $skip_more = array() ) {

	$skip = array_merge( [ 'page', 'table' ], $skip_more );

	// remove empty values because sometimes we have <form method="get"> which puts a bunch of empty crap in the URL
	if ( $_GET ) {
		foreach ( $_GET as $k => $v ) {
			if ( ! $v ) {
				$skip[] = gp_test_input( $k );
			}
		}
	}

	$ret = '';

	$possible_filters = get_cleaned_array_key_values_pairs_in_text( $_GET, $skip );
	if ( $possible_filters ) {
		$ret = '<p>Possible filters: <strong>' . $possible_filters . '</strong></p>';
	}

	return $ret;
}

/**
 * You could use this to compare an expected column name in a CSV file vs.
 * what is actually used...
 *
 * @param $str1
 * @param $str2
 *
 * @return bool
 */
function gp_two_strings_more_or_less_the_same( $str1, $str2 ) {

	if ( ! $str1 && ! $str2 ) {
		return true;
	}

	if ( gp_approximate_string( $str1 ) == gp_approximate_string( $str2 ) ) {
		return true;
	}

	return false;
}

/**
 * weird named function... if 2 strings are run through this and equal afterwards,
 * then they are more or less equal.
 *
 * Ie. "Images" is more or less equal to "Images" and
 *
 * "Image URL" is more or less equal to "image_url"
 *
 * @param $str
 *
 * @return mixed|string
 */
function gp_approximate_string( $str ) {
	$str = trim( $str );
	$str = strtolower( $str );
	// replace white space characters with underscores
	$str = preg_replace( '/\s+/', '_', $str );
	$str = str_replace( '-', '_', $str );
	$str = gp_replace_weird_characters( $str );

	return $str;
}

/**
 * there is no guarantee this works perfectly, it just seems to work for
 * what we need it for. If you need this for something very important maybe a regex
 * to replace all non a-zA-Z etc. would be better. For example, this replaces letter that
 * have weird variations like in different languages.. I don't know what you call them.
 *
 * @param $str
 * @return false|string
 */
function gp_replace_weird_characters( $str ) {
	$ret = iconv( 'UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $str );

	return $ret;
}

/**
 * we could change this to use array_column() but we'd still have to loop to take care of 3rd/4th params.
 *
 * @param      $data
 * @param      $column
 * @param bool $allow_duplicates
 * @param bool $allow_empty
 * @return array
 */
function gp_array_column( $data, $column, $allow_duplicate = true, $allow_empty = true ) {

	$ret = array();

	// NOTE: ensure that $data works with both arrays or stdClass objects. If you do not, you will break
	// lots of things. Right now it works because gp_if_set() handles arrays and objects.
	// $data is quite often sql results... which is quite often stdClass.

	if ( $data ) {
		foreach ( $data as $rr ) {

			$val = gp_if_set( $rr, $column );

			if ( ! $val && ! $allow_empty ) {
				continue;
			}

			if ( $allow_duplicate ) {
				$ret[] = $val;
			} else {
				if ( ! in_array( $val, $ret, true ) ) {
					$ret[] = $val;
				}
			}
		}
	}

	return $ret;

}

/**
 * An ajax form with some hidden inputs and a button, only the button should be visible.
 *
 * @param array $args
 * @return string
 */
function get_simple_button_ajax_form( $args = array() ) {

	// you probably want to include an ajax action for sure, but don't forget to register it
	// see ajax.php
	$ajax_action = gp_if_set( $args, 'ajax_action' );

	// an array of key/value pairs to print in hidden inputs
	$inputs = gp_if_set( $args, 'inputs', array() );

	$btn_text = gp_if_set( $args, 'btn_text', 'Submit' );

	$cls   = [ 'ajax-general' ];
	$cls[] = gp_if_set( $args, 'add_class' );

	$op = '';
	$op .= '<form action="' . AJAX_URL . '" class="' . gp_parse_css_classes( $cls ) . '" data-confirm-before="' . gp_if_set( $args, 'confirm', '' ) . '">';

	// add your own html here if you need to
	$op .= gp_if_set( $args, 'html_inside', '' );

	if ( $ajax_action ) {
		$op .= get_ajax_hidden_inputs( $ajax_action );
	}

	if ( $inputs ) {
		$op .= get_hidden_inputs_from_array( $inputs );
	}

	$btn_html = gp_if_set( $args, 'btn_html', '' );

	if ( $btn_html ) {
		$op .= $btn_html;
	} else if ( $btn_text ) {
		$op .= '<button class="' . gp_if_set( $args, 'btn_class', '' ) . '" type="submit">' . $btn_text . '</button>';
	}

	$op .= '</form>';

	return $op;
}

/**
 * will show in footer and maybe in receipts and whatnot in the future.
 *
 * @return string
 */
function get_operating_as_name() {
	// if you remove the Inc., you're going to have to adjust the footer to add back the dot.
	return 'Click It Wheels Inc.';
}

/**
 * returns dollars per tire that should be charged for
 * tires when (billing?) address is ontario.
 *
 * @return int
 */
function get_ontario_tire_levy_amt() {
	return 4;
}

/**
 * When you send the confirmation email, you may want to skip the brackets text.
 *
 * Why??? Because the confirmation emails only rely on data in the database.
 * the database only stores the quantity and the total amount.
 *
 * Therefore if we write 4 X $4.00 for example, the $4.00 has to come from the code,
 * which comes from the time the code is executed, not the time at which the order was placed.
 *
 * If the fee amount changes, and we add functionality later on to re-send confirmation emails
 * to users after their initial checkout date, then it may be inaccurate.
 *
 * @param $qty
 * @return string
 */
function get_ontario_tire_levy_cart_text( $do_simple = false, $qty = false, $price_each = false ) {

	if ( $do_simple ) {
		return 'Ontario Environmental Levy';
	}

	$ret = 'Ontario Environmental Levy (' . (int) $qty . 'X ' . print_price_dollars_formatted( $price_each ) . ')';

	return $ret;
}

/**
 * Helps to avoid extra lines just to define a variable and check stuff..
 *
 * @param $glue
 * @param $thing
 * @return string|null
 */
function gp_safe_implode( $glue, $thing, $df = null ) {

	if ( $thing && is_array( $thing ) ) {
		return implode( $glue, $thing );
	}

	return $df;
}

/**
 * Mini navigation between pages
 */
function get_admin_inventory_related_links( $current = null ) {

	$stuff = array();

	$stuff[] = array(
		'id' => 'suppliers_table',
		'url' => get_admin_archive_link( DB_suppliers ),
		'text' => 'Supplier\'s Table',
	);

	$stuff[] = array(
		'id' => 'inventory_overview',
		'url' => get_admin_page_url( 'inventory_overview' ),
		'text' => 'Inventory Overview',
	);

	$stuff[] = array(
		'id' => 'registered_inventory_processes',
		'url' => get_admin_page_url( 'registered_inventory_processes' ),
		'text' => 'Registered Inventory Processes',
	);

	$stuff[] = array(
		'id' => 'stock_updates',
		'url' => get_admin_archive_link( DB_stock_updates ),
		'text' => 'Stock Updates Table',
	);

	$ret = implode( ' | ', array_map( function ( $arr ) use ( $current ) {

		$id   = gp_if_set( $arr, 'id' );
		$url  = gp_if_set( $arr, 'url' );
		$text = gp_if_set( $arr, 'text' );

		if ( $current == $id ) {
			// $text .= ' (current)';
			return '<span>' . $text . '</span>';
		}

		return get_anchor_tag_simple( $url, $text );
	}, $stuff ) );

	return 'More info: ' . $ret;
}

/**
 * @param $data
 *
 * @return array
 */
function gp_transponse_array( $data ) {
	$retData = array();
	foreach ( $data as $row => $columns ) {
		foreach ( $columns as $row2 => $column2 ) {
			$retData[ $row2 ][ $row ] = $column2;
		}
	}

	return $retData;
}

/**
 * A-Za-z0-9 basically
 */
define( 'ALPHA_NUMERIC_SET_DEFAULT', 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789' );

/**
 * Omit characters such as I, L, O, 1 (one) and 0 (zero)
 */
define( 'ALPHA_NUMERIC_SET_SIMPLIFIED', 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjklmnpqrstuvwxyz23456789' );

/**
 * simple random-ish string.. of course, not cryptographically secure
 *
 * @param      $length
 * @param bool $omit_some_chars - omit I's, L's, 0's, 1's, O's
 *
 * @return string
 */
function rand_alpha_numeric( $length, $set = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789' ) {

	$strlen = strlen( $set );
	$ret    = '';

	for ( $x = 1; $x <= $length; $x ++ ) {
		$ret .= substr( $set, rand( 0, $strlen - 1 ), 1 );
	}

	return $ret;
}

/**
 * @param       $input
 * @param array $callbacks
 */
function gp_usort_multiple_callbacks( &$input, array $callbacks ) {

	// input passed by ref
	usort( $input, function ( $item_1, $item_2 ) use ( $callbacks ) {

		// when a callback returns a positive or negative integer, return that.
		foreach ( $callbacks as $callback ) {

			$ret = is_callable( $callback ) ? $callback( $item_1, $item_2 ) : 0;

			// def. use non-strict comparison here for false/null
			if ( ! $ret ) {
				// do the next comparison function
				continue;
			}

			return (int) $ret;
		}

		return 0;
	} );
}

/**
 * Should be printed on tire related pages if user selects U.S.
 *
 * @param Vehicle|null $vehicle
 * @return false|string
 */
function get_us_tire_inventory_lightbox_alert( $vehicle ){

    if ( ! US_TIRES_HAVE_NO_INVENTORY ) {
        return '';
    }

	if ( app_get_locale() !== APP_LOCALE_US ) {
		return '';
	}

	if ( $vehicle && $vehicle->is_complete() ) {
	    $rims_url = get_vehicle_archive_url( 'rims', $vehicle->get_slugs() );
    } else{
	    $rims_url = Router::build_url( [ 'wheels' ] );
    }

	ob_start();

	echo '<div class="us-tire-inventory-lightbox-wrapper" style="display: none;">';

	// in case of first time visitors being sent to the wrong locale (perhaps via use of a proxy),
    // we should make sure they have an easy link to the canadian side of the site.
	echo get_general_lightbox_content( "us-tire-inventory-alert", gp_capture_output( function() use( $rims_url ){
		?>
        <div class="general-content">
            <h2>Tire Inventory is Coming Soon to our U.S. Customers</h2>
            <p>In the meantime, shop our wide selection of <a href="<?php echo $rims_url; ?>">wheels</a> to find something that fits your vehicle!</p>
            <p>Otherwise, if you require shipping within Canada, <a href="" class="js-set-country-btn" data-country="ca">click here</a> to view our Canadian inventory of wheels, tires, and packages!</p>
        </div>
		<?php
	} ), [
		'add_class' => 'general-lightbox width-lg-1 us-tire-inventory-alert',
	] );

	echo '<button class="lb-trigger open-on-page-load" data-for="us-tire-inventory-alert"></button>';

	echo '</div>';

	return ob_get_clean();
}

/**
 * Print location value for user
 *
 * @param        $in
 * @param string $default
 *
 * @return bool|mixed
 */
function get_cart_item_location_text( $in, $default = "" ) {
	return gp_if_set( [
		'front' => 'Front',
		'rear' => 'Rear',
		'universal' => 'Universal'
	], $in, $default );
}

/**
 * The main concern is to remove dots and slashes so that if we include a file dynamically,
 * it doesn't end up being some arbitrary file.
 *
 * Pass in something like "submit_quote", returns "submit_quote" or "submit-quote".
 *
 * "../../../../malicious-file" would return "malicious-file".
 *
 * You can then prepend ".php" and see if the file exists in some directory.
 *
 * @param $filename_without_extension
 */
function make_safe_php_filename_from_user_input( $filename_without_extension, $convert_underscore_to_dash = false ) {

	if ( ! gp_is_singular( $filename_without_extension ) || ! $filename_without_extension ) {
		return false;
	}

	// a lot of times we put values with underscores in a hidden form element somewhere but name
	// the file with dashes instead.
	if ( $convert_underscore_to_dash ) {
		$filename_without_extension = str_replace( "_", "-", $filename_without_extension );
	}

	// allow dashes, underscores, letters, numbers (and maybe spaces?)
	// its possible there are other legit chars for filenames, but we're probably supporting them
	$filename_without_extension = preg_replace( "/[^a-zA-Z0-9\s\-_]/", "", $filename_without_extension );

	return $filename_without_extension;
}

/**
 * @param $arr
 *
 * @return array
 */
function gp_multi_dimensional_flatten( $arr ) {
	$ret = [];
	$it  = new RecursiveIteratorIterator( new RecursiveArrayIterator( $arr ) );
	foreach ( $it as $v ) {
		$ret[] = $v;
	}

	return array_values( $ret );
}

/**
 * @param $msgs - string or array or nested arrays
 */
function gp_javascript_alert_from_msgs( $msgs ) {

	if ( ! $msgs ) {
		return '';
	}

	$msgs = gp_is_singular( $msgs ) ? [ $msgs ] : [];
	assert( is_array( $msgs ) );
	$arr = gp_multi_dimensional_flatten( $msgs );

	return implode( "\n", $arr );
}

/**
 * I prefer page names to have underscores because I will reference pages by
 * name very often in the code. This will sanitize and ensure basically
 * letters/numbers/underscores only
 *
 * @param $in
 */
function make_page_name_from_user_input( $in ) {

	// general sanitation
	$out = gp_test_input( $in );

	// remove illegal chars, replace spaces with dashes
	$out = make_slug( $out, false );

	// make slug will have dashes, so do this after
	$out = str_replace( "-", "_", $out );

	return $out;
}

/**
 * Turn a string like "image_pos == left && background == true && red"
 * into an array. We may use args like this to help with content management in some cases.
 *
 * If we have to use 1 key for multiple values, we will let this return the raw values comma
 * separated and then we can choose to explode the value on comma for certain arguments.
 *
 * ie. "images == img1.jpg, img2.jpg"
 *
 * When only 1 value is specified and not a key value pair, key will be numerically index,
 * and value will be the argument
 *
 * @param        $in
 * @param string $equals_separator
 * @param string $args_separator
 * @return array
 */
function app_parse_admin_args_string( $in, $args_separator = "&&", $equals_separator = "==" ) {

	// in case its already an array for whatever reason. don't return that array, just
	// return nothing...
	$in  = gp_force_singular( $in );
	$ret = [];

	$args = array_map( function ( $arg ) {
		return trim( $arg );
	}, explode( $args_separator, $in ) );

	foreach ( $args as $arg ) {

		$arg_split = explode( $equals_separator, $arg );

		if ( count( $arg_split ) === 1 ) {
			$ret[] = trim( $arg_split[ 0 ] );
		} else if ( count( $arg_split ) > 1 ) {
			$ret[ trim( $arg_split[ 0 ] ) ] = trim( $arg_split[ 1 ] );
		}
	}

	return $ret;
}

/**
 * @param $array
 *
 * @return mixed
 */
function next_or_first( &$array ) {
	// must check if key() is null otherwise false like values in the array will trigger a reset.
	// returning current at the end only gives us the next because we called next at the beginning.
	return next( $array ) === false && key( $array ) === null ? reset( $array ) : current( $array );
}

/**
 * @param $in
 * @param $df
 *
 * @return mixed
 */
function or_null( $in, $df ) {
    return $in !== null ? $in : $df;
}

/**
 * @param        $in
 * @param string $df
 *
 * @return string
 */
function if_not_false_like( $in, $df = '' ) {
    return $in ? $in : $df;
}

/**
 * @param        $thing
 * @param string $before
 * @param string $after
 *
 * @return string
 */
function wrap( $thing, $before = '', $after = '' ) {
    return $before . $thing . $after;
}

/**
 * @param $thing
 * @param string $tag
 * @return string
 */
function wrap_tag( $thing, $tag = 'p' ) {
    $tag = gp_esc_db_table( $tag );

    return wrap( $thing, '<' . $tag . '>', '</' . $tag . '>' );
}

/**
 * Remove empty values from an array, then wrap each element in a before and after string,
 * and add an optional separator in between each element.
 *
 * @param $arr
 * @param string $before
 * @param string $after
 * @param string $separator
 * @return string
 */
function wrap_array_elements_not_empty( $arr, $before = '', $after = '', $separator = '' ) {

    $arr = gp_is_singular( $arr ) ? array( $arr ) : $arr;
    $arr = array_filter( $arr );

    $arr = array_map( function ( $v ) use ( $before, $after ) {
        return $v ? wrap( $v, $before, $after ) : null;
    }, $arr );

    $ret = implode( $separator, $arr );

    return $ret;
}

/**
 * @param        $arr
 * @param string $separator
 *
 * @return string
 */
function wrap_array_elements_not_empty_in_p( $arr, $separator = '' ) {
    return wrap_array_elements_not_empty( $arr, '<p>', '</p>', $separator );
}

/**
 * Auto incrementor of sorts....
 *
 * Returns the next number each time you call this with the same context.
 *
 * @param int $context
 * @param int $start
 *
 * @return mixed
 */
function get_count( $context = 1, $start = 1 ) {

    global $_func_get_count;
    $_func_get_count = or_null( $_func_get_count, array() );

    if ( ! isset( $_func_get_count[ $context ] ) ) {
        $_func_get_count[ $context ] = $start;
    } else {
        $_func_get_count[ $context ] ++;
    }

    return $_func_get_count[ $context ];
}

/**
 * @param $bytes
 * @param int $divide_by
 * @param string $suffix
 * @return string
 */
function cw_format_bytes( $bytes, $divide_by = 1024, $suffix = ' kb' ) {

    $b = $bytes ? $bytes : 0;

    if ( $bytes > 0 ) {
        $num = round( $bytes / $divide_by, 0 );
        return number_format( $num, 0, '.', ',' ) . $suffix;
    }

    return 0 . $suffix;
}

/**
 * ie. cw_match( 50, [ 1, 50, 30 ], [ "out1", "out2", "out3" ] ) => "out2"
 *
 * @param $match - target value
 * @param array $options - numerically indexed array
 * @param array $outputs - numerically indexed array
 * @param false $strict - do strict comparison between $match and values of $options ?
 * @param null $default
 * @param bool $throw
 * @return mixed|null
 */
function cw_match( $match, array $options, array $outputs, $strict = false, $default = null, $throw = false ) {
    $found = false;
    $_index = null;

    foreach ( $options as $index => $value ) {

        if ( $strict && $match === $value ) {
            $_index = $index;
            $found = true;
            break;
        } else if ( $match == $value ) {
            $_index = $index;
            $found = true;
            break;
        }
    }

    if ( $throw && ! $found ) {
        throw_dev_error( "Could not match input ($match)" );
        return null;
    }

    // let it fail I guess, if $outputs does not have the same indexes as $options.
    // (ie. they should be the same length)
    return $found ? $outputs[$_index] : $default;
}

/**
 * @param array $arr
 * @param array $keys
 * @return array
 */
function gp_array_sort_by_keys( array $arr, array $keys ) {

    $ret = [];

    // probably an easier way, but w/e
    foreach ( $keys as $key ) {
        if ( isset( $arr[$key] ) ) {
            $ret[$key] = $arr[$key];
        }
    }

    foreach ( $arr as $a1 => $a2 ) {
        if ( ! isset( $ret[$a1] ) ) {
            $ret[$a1] = $a2;
        }
    }

    return $ret;
}

/**
 * Accepts string or array and always returns an array, whose
 * values are scalar (ie. not object or array). Doesn't sanitize
 * anything otherwise.
 *
 * @param $input
 * @param bool $convert_singular
 * @return array|bool[]|false[]|float[]|int[]|string[]
 */
function gp_force_array_depth_1_scalar( $input, $convert_singular = true ) {

    if ( $convert_singular && is_scalar( $input ) ) {
        if ( $input ) {
            return [ $input ];
        }

        return [];
    }

    if ( is_array( $input ) ) {
        // force each array index to be scalar
        return array_map( function( $val ) {
            return is_scalar( $val ) ? $val : '';
        }, $input );
    }

    return [];
}
