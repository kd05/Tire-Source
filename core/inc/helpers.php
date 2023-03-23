<?php

// ******************************
//           Randoms
// ******************************

/**
 * @param     $arr
 * @param int $count
 */
function gp_array_first_count( $arr, $count = 1 ) {

	$ret = array();

	if ( $arr && is_array( $arr ) ) {
		for ( $x = 1; $x <= $count; $x ++ ) {
			$ret[] = array_shift( $arr );
		}
	}

	return $ret;
}

/**
 * @param $arr
 *
 * @return null
 */
function gp_array_last( $arr ){

	if ( ! is_array( $arr ) ) {
		return null;
	}

	// if count zero
	if ( ! $arr ) {
		return null;
	}

	$last = array_values(array_slice($arr, -1))[0];

	// this would do the same i think
//	$end = end( $arr );
//	reset( $arr );

	return $last;
}

/**
 * We want the return value to have all the values of $base first,
 * then possibly have values of $add second, BUT more importantly, we don't want
 * $add to override the values from $base. If not for the last condition,
 * we could use a combination of array_unique( array_merge() ) to accomplish this.
 *
 * @param $base
 * @param $add
 *
 * @return array
 */
function gp_fill_array( $base, $add ) {

	$base = gp_make_array( $base );
	$add = gp_make_array( $add );

	$ret = $base;

	if ( $add ) {
		foreach ( $add as $k=>$v ) {
			if ( ! isset( $base[$k] ) ) {
				$ret[$k] = $v;
			}
		}
	}

	return $ret;
}

/**
 * Useful if array keys might be dynamic
 *
 * @param $arr
 */
function gp_array_first( $arr, $default = null ) {
	if ( is_array( $arr ) ) {
		$arr2 = array_values( $arr );

		return gp_if_set( $arr2, 0, $default );
	}

	return $default;
}

/**
 * @param $data
 * @param $index
 * @param $prefix
 * @param $default
 *
 * @return bool|mixed|null
 */
function gp_if_set_fallback_remove_prefix( $data, $index, $prefix, $default ) {
	$check1 = $index;
	$check2 = $prefix . $index;

	// check the prefixed version first
	return gp_first_set( $data, [ $check2, $check1 ], $default );
}

/**
 * @param      $data
 * @param      $index
 * @param      $prefix
 * @param null $default
 */
function gp_if_set_fallback_prefix( $data, $index, $prefix, $default = null ) {

	$check1 = $index;
	$check2 = $prefix . $index;

	return gp_first_set( $data, [ $check1, $check2 ], $default );
}

/**
 *
 * @param      $data
 * @param      $indexes
 * @param bool $default
 */
function gp_first_set( $data, $indexes, $default = null ) {

	$indexes = gp_make_array( $indexes );

	foreach ( $indexes as $index ) {
		$v = gp_if_set( $data, $index, null );
		if ( $v !== null ) {
			return $v;
		}
	}

	return $default;
}

/**
 * Use this in place of: isset( $data[$index] ) ? $data[$index] : $default,
 * or:  isset( $data->$index ) ? $data->$index : $default;
 *
 * The 3 purposes:
 * - save time
 * - eliminate typos such as isset( $data[$index] ) ? $data['some_other_index'] : default
 * - when something could be an object or array, but we just want the value.
 *
 * @param              $data
 * @param array|string $index
 * @param bool         $default
 *
 * @return bool|mixed
 */
function gp_if_set( $data, $index, $default = false ) {

	// you can use this to debug: (I prefer not to check validity of $index on every single call)
	// the debug backtrace should tell you what function called gp_if_set() that caused the error
	//	if ( ! gp_is_singular( $index ) ) {
	//		echo nl2br( "----------------------- \n" );
	//		echo nl2br( "----------------------- \n" );
	//		echo '<pre>' . print_r( debug_backtrace(), true ) . '</pre>';
	//	}

	if ( ! gp_is_singular( $index ) ) {
	    // throw a dev error so my ide doesn't always warn about uncaught exception
	    throw_dev_error( "Array index must be a scalar." );
	    exit;
	}

	if ( is_array( $data ) ) {
		return isset( $data[ $index ] ) ? $data[ $index ] : $default;
	} else if ( is_object( $data ) ) {
		return isset( $data->{$index} ) ? $data->{$index} : $default;
	}

	return $default;
}

/**
 * @param      $data
 * @param      $index
 * @param null $df
 */
function gp_if_set_and_not_empty( $data, $index, $df = null ) {
	$v = gp_if_set( $data, $index, $df );

	return empty( $v ) ? $df : $v;
}

/**
 * Pass in a string, or an array of strings, or an array of arrays of strings or maybe more arrays
 * returns a bunch of words with spaces.
 *
 * @param $classes
 *
 * @return string
 */
function gp_parse_css_classes( $classes ) {

	// for sake of efficiency, lets just return the string if that was passed in
	if ( is_string( $classes ) ) {
		return gp_test_input( $classes );
	}

	$classes = is_array( $classes ) ? $classes : (array) $classes;

	// remove empty array values, otherwise might have some double spaces in class list after implode
	$classes = array_filter( $classes );

	// trim each class
	// don't use this, it mis-handles nested arrays
	// array_map( 'trim', $classes );

	// can do array_unique to remove possible duplicates, but I don't think its needed
	// $classes = array_unique( $classes );
	// array_map( 'esc_attr', $classes );

	// call the function recursively if an array value is also an array
	if ( $classes ) {
		foreach ( $classes as $index => $class ) {
			if ( is_array( $class ) ) {
				$classes[ $index ] = gp_parse_css_classes( $class );
			} else if ( gp_is_singular( $class ) ) {
				$classes[ $index ] = trim( $class );
			} else {
				unset( $classes[ $index ] );
			}
		}
	}

	return implode( ' ', $classes );
}

/**
 * Wrapper for gp_array_to_html_element()
 *
 * Can do the same as wrap_tag() when $class and $more_atts are not passed in.
 *
 * Unlike wrap_tag(), can add classes, and any other html attributes.
 *
 * Note that class can be an array or string, gp_array_to_html_element() will parse
 * the class list if an array is given.
 *
 * @see gp_array_to_html_element()
 * @see wrap_tag()
 *
 * @param        $inner
 * @param        $tag
 * @param string $class
 * @param array  $more_atts
 */
function html_element( $inner, $tag, $class = '', $more_atts = array() ){

	// if $class, use that, otherwise, check $more_atts['class']

	if ( ! $class ) {
		$class = gp_if_set( $more_atts, 'class', '' );
	}

	if ( $class ) {
		$more_atts['class'] = $class;
	}

	return array_to_html_element( $tag, $more_atts, true, $inner );
}


/**
 * @param       $url
 * @param       $text
 * @param bool  $new_tab
 * @param array $atts
 */
function gp_get_link( $url, $text, $new_tab = false, $atts = array(), $tag = 'a' ) {

	$atts[ 'href' ] = isset( $atts[ 'href' ] ) ? $atts[ 'href' ] : $url;

	if ( $new_tab && ! isset( $atts[ 'target' ] ) ) {
		$atts[ 'target' ] = '_blank';
	}

	// kind of not necessary but w/e
	if ( isset( $atts[ 'class' ] ) ) {
		$class = $atts[ 'class' ];
		unset( $atts[ 'class' ] );
	} else {
		$class = '';
	}

	return html_element( $text, $tag, $class, $atts );
}

/**
 * Alias for gp_get_link which makes it a tad bit cleaner to add a css class.
 *
 * @param        $url
 * @param        $text
 * @param string $class
 * @param bool   $new_tab
 * @param array  $additional_html_attributes
 * @param string $tag
 *
 * @return string
 */
function gp_get_link_with_class( $url, $text, $class = '', $new_tab = false, $additional_html_attributes = [], $tag = 'a' ) {
	$additional_html_attributes[ 'class' ] = [ gp_if_set( $additional_html_attributes, 'class' ), $class ];

	return gp_get_link( $url, $text, $new_tab, $additional_html_attributes, $tag );
}

/**
 * @param string $tag
 * @param array  $atts
 */
function array_to_html_element( $tag = 'div', $atts = array(), $close = true, $inner_html = '' ) {

	$tag = $tag ? gp_test_input( $tag ) : 'div';

	$implodes = array();

	if ( $atts && is_array( $atts ) ) {
		foreach ( $atts as $a1 => $a2 ) {

			// turn array into css class list - BEFORE checking is_array etc.
			if ( $a1 === 'class' ) {
				$a2 = gp_parse_css_classes( $a2 );
			}

			if ( is_array( $a2 ) || is_object( $a2 ) ) {
				$a2 = gp_json_encode( $a2 );
			}

			if ( ! gp_is_singular( $a2 ) ) {
				$a2 = '';
			}

			if ( gp_is_integer( $a1 ) || ! $a1 ) {
				// use this for things like <select multiple> or.. <input .... disabled> or
				// if your string is already complete like.. $a2 = 'data-something="value"'
				$implodes[] = $a2;
			} else {
				// is there a simple approach to sanitize $a2 and guarantee to not break json encoding??
				// may run into double escaping backslashes and stuff
				$implodes[] = $a1 . '="' . $a2 . '"';
			}
		}
	}

	$atts = implode( ' ', $implodes );
	$atts = trim( $atts );
	$atts = $atts ? ' ' . $atts : '';
	// $atts could be like ' class="classlist" id="id" disabled'

	$op = '';
	$op .= '<' . $tag . $atts . '>';

	$op .= $inner_html;

	if ( $close ) {
		$op .= '</' . $tag . '>';
	}

	return $op;
}

/**
 * Used to print opening tags with a large number of, or highly variable attributes.
 * ie. <a target="..." href="..." class="cls1 cls2 maybe-cls3 maybe-cls4" data-something="..." style="...">
 * Lets you build the tag with an array, which is much easier and cleaner for complicated tags.
 * $atts['class'] and $atts['style'] can be arrays of css classes or styles.
 * All other $atts[''] elements can be arrays as well, but we'll json encode the array, assuming we want to read it
 * with javascript.
 *
 * @param       $tag
 * @param array $atts
 */
function gp_atts_to_container( $tag = 'div', $atts = array(), $close_tag = false, $inner_html = '' ) {

	// atts string
	$str = '';

	if ( isset( $atts[ 'class' ] ) && is_array( $atts[ 'class' ] ) ) {
		$atts[ 'class' ] = gp_parse_css_classes( $atts[ 'class' ] );
	}

	$implodes = array(
		// 'class' => ' ', // we have a better function for this already
		'style' => '; ',
	);

	// future: maybe allow for $atts['style'] = array( 'display' => 'block' ) for example
	// future: right now we only allow for array( 'display: block', 'background: red' );
	if ( ! empty( $implodes ) ) {
		foreach ( $implodes as $att => $glue ) {
			if ( isset( $atts[ $att ] ) && is_array( $atts[ $att ] ) ) {
				$att_val      = implode( $glue, $atts[ $att ] );
				$att_val      = trim( $att_val );
				$atts[ $att ] = $att_val;
			}
		}
	}

	if ( ! empty( $atts ) ) {
		foreach ( $atts as $key => $value ) {

			// this lets you pass in array values to be encoded for data attributes
			// should we check other conditions here, like only json encode if the $key has 'data' in its string position?
			// wouldnt want to unintentionally json encode something. But I suppose json encoded
			// is better than getting a php error for trying to store an array into a string
			if ( is_array( $value ) ) {
				$value = gp_attr_json( $value );
			}

			// is there ever a time we wouldn't want to do this??
			// mainly with optional classes, sometimes we end up with that extra space at the end
			$value = trim( $value );

			if ( $key === 'style' ) {
				// I think this will trim spaces, then possible semi-colons, then possibly more spaces but im not sure
				$value = trim( $value, ' ; ' );
			}

			$str .= $key . '="' . $value . '" '; // space at end
		}
	}

	// remove last space
	$str = trim( $str );

	if ( strlen( $str ) > 0 ) {
		$r = '<' . $tag . ' ' . $str . ' >';

	} else {
		$r = '<' . $tag . '>';
	}

	$r .= $inner_html;

	if ( $close_tag ) {
		$r .= '</' . $tag . '>';
	}

	return $r;
}

/**
 * to decode the JSON data in javascript, use the following:
 * var data = div.attr('data-json');
 * data = jQuery.parseJSON(data);
 *
 * @param $data
 *
 * @return string
 */
function gp_attr_json( $data ) {
	if ( is_array( $data ) ) {
		return htmlspecialchars( json_encode( $data ), ENT_QUOTES, 'UTF-8' );
	}

	// I think this works fine (its same as above)
	return htmlspecialchars( json_encode( $data ), ENT_QUOTES, 'UTF-8' );
}

/**
 * Type JS is good for arrays/objects used in data attributes, or data attributes with HTML
 *
 * Note that if you are using $type = 'js', then user input will be cleaned sufficiently to print on page
 *
 * @param        $data
 * @param string $type
 */
function gp_json_encode( $data, $type = 'js' ) {

	$ret = '';

	switch ( $type ) {
		case 'js':
			// In JavaScript, use either $.parseJSON() (deprecated as of jquery 3.0), or JSON.parse()
			// note that this is suitable for putting html directly into an html data attribute
			$ret = htmlspecialchars( json_encode( $data ), ENT_QUOTES, 'UTF-8' );;
			break;
		default:
			// if your going to do this may as well just call json_encode directly
			$ret = json_encode( $data );
			break;
	}

	return $ret;
}

/**
 * @param $str
 *
 * @return bool
 */
function gp_is_integer( $str ) {
	$type = gettype( $str );
	if ( $type === 'integer' || $type === 'string' ) {
		// casting as string is important, because otherwise
		// if the integer passed in corresponds to the ASCII code for
		// a letter on the keyboard, ctype_digit returns false instead of true
		return ctype_digit( (string) $str );
	}

	return false;
}

/**
 * Trim a string or an array of strings of variable length from the end of a string
 * Works how you would want (and maybe even expect) this to work: trim( 'abcdef_random-garbage', '_random-garbage' );
 *
 * @param $string
 * @param $trim
 */
function gp_trim_end( $string, $trim ) {
	$trim = is_array( $trim ) ? array_filter( $trim ) : array_filter( array( $trim ) );
	$trim = array_unique( $trim );
	if ( $trim ) {
		foreach ( $trim as $str ) {
			if ( strpos( $string, $str ) === strlen( $string ) - strlen( $str ) ) {
				$string = substr( $string, 0, strlen( $string ) - strlen( $str ) );
			}
		}
	}

	return $string;
}

/**
 * Checks if date string is in a specific date format.
 * Use with caution, probably not 100% accurate.
 *
 * @param        $date
 * @param string $format
 *
 * @return bool
 */
function gp_validate_date_format( $date, $format = 'm/d/Y' ) {
	if ( ! $date ) {
		return false;
	}
	try {
		$d = DateTime::createFromFormat( $format, $date );

		return $d && $d->format( $format ) === $date;
	} catch ( Exception $e ) {
		return false;
	}
}

/**
 * Checks if something is a valid DateTime string
 *
 * @param $date
 */
function gp_is_date_valid( $date ) {
	if ( ! $date ) {
		return false;
	}
	try {
		$dt = new DateTime( $date );
	} catch ( Exception $e ) {
		return false;
	}

	return true;
}

/**
 * @return string
 */
function gp_get_menu_button( $class = 'menu-btn', $count = 3 ) {
	$btn = '';
	$btn .= '<div class="' . $class . '">';
	if ( $count ) {
		for ( $x = 1; $x <= $count; $x ++ ) {
			$btn .= '<div class="bar"></div>';
		}
	}
	$btn .= '</div>';

	return $btn;
}

/**
 * Generates an excerpt of the desired text.
 * If no second paramter is passed then it will generate an excerpt 20 words long.
 * If any words are cut off by the excerpt then ( ... ) will be appended to the text.
 * Returns a string.
 *
 * @param string $content text that you would like an excerpt of
 * @param int    $num     number of words to contain in excerpt
 *
 * @return string
 */
function gp_excerptize( $content, $num = 30 ) {
	$number = $num;
	//$content = apply_filters('the_content', $content);
	//echo "<pre style='display: none;'>".print_r($content, true)."</pre>";
	// $content = strip_tags( $content, '<br>' );
	$content = strip_tags( $content, '' );
	$content = str_replace( '&nbsp;', '', $content );

	$contentArray = explode( ' ', $content, $number + 1 );
	//echo '<pre>'.print_r($contentArray, true).'</pre>';
	$contentString = '';
	foreach ( $contentArray as $key => $value ) {
		if ( $key >= $number ) {
			$contentString .= '...';
			break;
		}
		$contentString .= trim( $value );
		if ( $key < $number - 1 ) {
			$contentString .= ' ';
		}
	}

	return $contentString;
}


/**
 * @param $str
 */
function gp_comma_string_to_array( $str, $sep = ',', $array_values = true ) {
	$str = trim( $str );
	$arr = explode( $sep, $str );
	$arr = array_map( 'trim', $arr );
	$arr = array_filter( $arr );

	if ( $array_values ) {
		$arr = array_values( $arr );
	}

	return $arr;
}

/**
 * @param      $str
 * @param bool $force_boolean
 *
 * @return bool|string
 */
function gp_shortcode_boolean( $str, $force_boolean = false ) {

	if ( $str === 'false' || $str === '0' || ! $str ) {
		return false;
	}

	if ( $str === 'true' || $str === '1' || ( $str && $force_boolean ) ) {
		return true;
	}

	return $str;
}

/**
 * Take user input and turn it into a valid container width (int), or return an empty string.
 * Examples of valid width: (string) "500px", (string) "750"
 * Examples of invalid width: (string) "<script>...", (string) "75%", (string) "2s7fd727d"
 * We sometimes use this to apply an inline style for width or max-width from a shortcode parameter
 *
 * @param $width
 */
function gp_shortcode_inline_width( $width ) {
	if ( $width === '' ) {
		return '';
	}

	$width = gp_trim_end( $width, 'px' );

	if ( gp_is_integer( $width ) ) {
		// gp_is_integer returns true on (string) "456", so cast as int in case it was a string
		$width = (int) $width;
		$width = $width < 2560 ? $width : 2560;
		$width = $width > 200 ? $width : 200;

		return $width;
	}

	return '';
}

/**
 * empty string or false returns an empty string
 * 0, or "false" returns: 'opacity: 0;'
 * other strings that represent numbers return ie, 'opacity: .5;'
 *
 * @param $overlay_opacity
 */
function gp_shortcode_overlay_style( $overlay_opacity ) {

	if ( $overlay_opacity === false || $overlay_opacity === '' || $overlay_opacity === null ) {
		return '';
	}

	$overlay_opacity = $overlay_opacity === "0" ? 0 : $overlay_opacity;
	$overlay_opacity = $overlay_opacity === "false" ? 0 : $overlay_opacity;
	$overlay_opacity = gp_get_shortcode_percent( $overlay_opacity );

	return 'opacity: ' . $overlay_opacity . '";';
}

/**
 * Accepts like 0.85, .85, or 85 to mean the same thing. false probably ends up as zero, as does ''.
 *
 * @param $str
 *
 * @return float
 */
function gp_get_shortcode_percent( $str ) {

	$str = (float) $str;
	if ( $str > 0 && $str < 1 ) {
		$str = $str * 100;
	}
	$str = round( $str / 100, 2 );

	// not a string though!
	return $str;
}

/**
 * Each time you call the function it returns the opposite of what it did last time..
 * Pass in $why if you might end up using this for more than 1 set of interchangeable values
 *
 * @param string $why
 * @param bool   $first
 */
function gp_get_alternate_value( $why = 'because', $default = true ) {

	$key = 'alt_value_' . $why;

	if ( ! gp_is_global_set( $key ) ) {
		gp_set_global( $key, ! $default );

		return $default;
	}

	$val = gp_get_global( $key, $default );

	if ( $val ) {
		gp_set_global( $key, false );

		return true;
	}

	gp_set_global( $key, true );

	return false;
}

// ******************************
//           Globals
// ******************************

/**
 * @param $key
 *
 * @return bool
 */
function gp_is_global_set( $key ) {
	return is_global_set( $key );
	// return isset( $GLOBALS[ 'gpGlobals' ][ $key ] );
}

/**
 * I think its nice to have all of our theme globals stored in the same array, so we can
 * quickly print all when debugging.
 *
 * @param $key
 * @param $value
 */
function gp_set_global( $key, $value ) {
	set_global( $key, $value );
	// $GLOBALS[ 'gpGlobals' ][ $key ] = $value;
}

/**
 * @param        $key
 * @param string $default
 *
 * @return bool|mixed
 */
function gp_get_global( $key, $default = '' ) {
	return get_global( $key, $default );
//	$gpGlobals = gp_if_set( $GLOBALS, 'gpGlobals', array() );
//	return gp_if_set( $gpGlobals, $key, $default );
}

// *******************************
//       User Input / Forms
// *******************************

/**
 * Use to validate that something isn't way too long
 * like millions of characters long, if we may write to a database somewhere or send
 * in an email.. If someones first name is 35000 characters long, this likely indicates spam.
 * for arrays, its not meant to be a perfectly accurate number (ie. array keys of 0 may contribute 1 to overall length)
 *
 * @param string|array|mixed $thing
 * @param bool               $add_keys
 *
 * @return int
 */
function gp_get_user_input_length( $thing, $add_keys = true ) {

	$ln = 0;

	// not checking this could result in infinite loop
	// careful not to only check is_bool, because is_bool( null ) might be false though im not totally sure
	if ( ! $thing ) {
		return 0;
	}

	if ( is_bool( $thing ) ) {
		return $thing ? 1 : 0;
	}
	// were going to call recursively so $thing might not be an array
	if ( is_string( $thing ) || is_int( $thing ) || is_float( $thing ) ) {
		return strlen( $thing );
	}
	// $thing = (array) $thing;
	if ( is_array( $thing ) || is_object( $thing ) ) {
		foreach ( $thing as $kk => $vv ) {
			if ( $add_keys ) {
				$ln = $ln + gp_get_user_input_length( $kk );
			}
			$ln = $ln + gp_get_user_input_length( $vv );
		}
	} else {
		// kind of a last resort fallback, not sure if we can end up here or not
		ob_start();
		var_dump( $thing );
		$string_thing = ob_get_clean();

		return strlen( $string_thing );
	}
	// the goal here is to never return 0 by default or accidentally
	// im fairly confident that that won't be the case
	return $ln;
}

/**
 * Make sure user input expected to be a string doesn't come through as an array
 */
function gp_make_string( $str ) {
	// if we have object or array, then notice will be printed
	if ( is_int( $str ) || is_float( $str ) ) {
		$str = (string) $str;
	}

	if ( is_bool( $str ) ) {
		$str = $str ? '1' : '0';
	}

	return is_string( $str ) ? $str : '';
}

/**
 * this massive load of garbage just puts a <p> around literally anything, including
 * <ul> AND every <li> inside of it. Thank you, internet.
 *
 * @param $text
 *
 * @return mixed|string
 */
function addParagraphsNew( $text ) {
	// local variables
	$returntext = '';       // modified string to return back to caller
	$sections   = array();  // array of text sections returned by preg_split()
	$pattern1   = '%        # match: <tag attrib="xyz">contents</tag>
 ^                       # tag must start on the beginning of a line
 (                       # capture whole thing in group 1
   <                     # opening tag starts with left angle bracket
   (\w++)                # capture tag name into group 2
   [^>]*+                # allow any attributes in opening tag
   >                     # opening tag ends with right angle bracket
   .*?                   # lazily grab everything up to closing tag
   </\2>                 # closing tag for one we just opened
 )                       # end capture group 1
 $                       # tag must end on the end of a line
 %smx';                  // s-dot matches newline, m-multiline, x-free-spacing

	$pattern2 = '%        # match: \n--untagged paragraph--\n
 (?:                     # non-capture group for first alternation. Match either...
   \s*\n\s*+             # a newline and all surrounding whitespace (and discard)
 |                       # or...
   ^                     # the beginning of the string
 )                       # end of first alternation group
 (.+?)                   # capture all text between newlines (or string ends)
 (?:\s+$)?               # clear out any whitespace at end of string
 (?=                     # end of paragraph is position followed by either...
   \s*\n\s*              # a newline with optional surrounding whitespace
 |                       # or...
   $                     # the end of the string
 )                       # end of second alternation group
 %x';                    // x-free-spacing

	// first split text into tagged portions and untagged portions
	// Note that the array returned by preg_split with PREG_SPLIT_DELIM_CAPTURE flag will get one
	// extra member for each set of capturing parentheses. In this case, we have two sets; 1 - to
	// capture the whole HTML tagged section, and 2 - to capture the tag name (which is needed to
	// match the closing tag).
	$sections = preg_split( $pattern1, $text, - 1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );

	// now put it back together proccessing only the untagged sections
	for ( $i = 0; $i < count( $sections ); $i ++ ) {
		if ( preg_match( $pattern1, $sections[ $i ] ) ) { // this is a tagged paragraph, don't modify it, just add it (and increment array ptr)
			$returntext .= "\n" . $sections[ $i ] . "\n";
			$i ++; // need to skip over the extra array element for capture group 2
		} else { // this is an untagged section. Add paragraph tags around bare paragraphs
			$returntext .= preg_replace( $pattern2, "\n<p>$1</p>\n", $sections[ $i ] );
		}
	}
	$returntext = preg_replace( '/^\s+/', '', $returntext ); // clean leading whitespace
	$returntext = preg_replace( '/\s+$/', '', $returntext ); // clean trailing whitespace

	return $returntext;
}

/**
 *
 * This works best. doesn't add p tags around <ul> or <li>.
 *
 * (this is either right out of wp core, or maybe its a modified version of it?)
 *
 * Seem to be able to mix plain text with some html blocks in a text area
 * and returns the only string any human could conceivably want, unlike all the other
 * attempts at this.
 *
 * Replaces double line-breaks with paragraph elements.
 *
 * source: https://www.guruqa.com/topic.php?t=6527
 *
 * A group of regex replaces used to identify text formatted with newlines and
 * replace double line-breaks with HTML paragraph tags. The remaining
 * line-breaks after conversion become <<br />> tags, unless $br is set to '0'
 * or 'false'.
 *
 * @since 0.71
 *
 * @param string $pee The text which has to be formatted.
 * @param bool $br Optional. If set, this will convert all remaining line-breaks after paragraphing. Default true.
 *
 * @return string Text which has been converted into correct paragraph tags.
 */
function cw_wpautop($pee, $br = true) {
	$pre_tags = array();

    $pee = $pee ?? '';

	if ( trim( $pee ) === '' ) {
		return '';
	}
	$pee = $pee . "\n"; // just to make things a little easier, pad the end
	if ( strpos($pee, '<pre') !== false ) {
		$pee_parts = explode( '</pre>', $pee );
		$last_pee = array_pop($pee_parts);
		$pee = '';
		$i = 0;
		foreach ( $pee_parts as $pee_part ) {
			$start = strpos($pee_part, '<pre');
			// Malformed html?
			if ( $start === false ) {
				$pee .= $pee_part;
				continue;
			}
			$name = "<pre wp-pre-tag-$i></pre>";
			$pre_tags[$name] = substr( $pee_part, $start ) . '</pre>';
			$pee .= substr( $pee_part, 0, $start ) . $name;
			$i++;
		}
		$pee .= $last_pee;
	}
	$pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);
	// Space things out a little
	$allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|option|form|map|area|blockquote|address|math|style|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';
	$pee = preg_replace('!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee);
	$pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
	$pee = str_replace(array("\r\n", "\r"), "\n", $pee); // cross-platform newlines
	if ( strpos($pee, '<object') !== false ) {
		$pee = preg_replace('|\s*<param([^>]*)>\s*|', "<param$1>", $pee); // no pee inside object/embed
		$pee = preg_replace('|\s*</embed>\s*|', '</embed>', $pee);
	}
	$pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
	// make paragraphs, including one at the end
	$pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);
	$pee = '';
	foreach ( $pees as $tinkle ) {
		$pee .= '<p>' . trim($tinkle, "\n") . "</p>\n";
	}
	$pee = preg_replace('|<p>\s*</p>|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace
	$pee = preg_replace('!<p>([^<]+)</(div|address|form)>!', "<p>$1</p></$2>", $pee);
	$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee); // don't pee all over a tag
	$pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee); // problem with nested lists
	$pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
	$pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
	$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee);
	$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);
	if ( $br ) {
		$pee = preg_replace_callback('/<(script|style).*?<\/\\1>/s', '_cw_autop_newline_preservation_helper', $pee);
		$pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee); // optionally make line breaks
		$pee = str_replace('<WPPreserveNewline />', "\n", $pee);
	}
	$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee);
	$pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
	$pee = preg_replace( "|\n</p>$|", '</p>', $pee );
	if ( ! empty( $pre_tags ) ) {
		$pee = str_replace(array_keys($pre_tags), array_values($pre_tags), $pee);
	}

	return $pee;
}

/**
 * This is almost shortcode_unautop from WordPress but with some modifications
 * to address some issues.
 *
 * https://wordpress.stackexchange.com/questions/178144/is-shortcode-unautop-broken
 * https://core.trac.wordpress.org/attachment/ticket/14050/plugin.php#L61
 *
 * Shortcode tags are in array keys. Do the common sense thing but
 * array_flip the value when passing in.
 *
 * @param       $pee
 * @param array $shortcode_tags
 */
function cw_shortcode_unautop_alt( $pee, $shortcode_tags = [] ) {

	if ( ! $shortcode_tags ) {
		return $pee;
	}

	$tagregexp = join( '|', array_map( 'preg_quote', array_keys( $shortcode_tags ) ) );

	$pattern =
		'/'
		. '<p>'                              // Opening paragraph
		. '\\s*+'                            // Optional leading whitespace
		. '('                                // 1: The shortcode
		.     '\\[\\/?'                      // Opening bracket for opening or closing shortcode tag
		.     "($tagregexp)"                 // 2: Shortcode name
		.     '(?![\\w-])'                   // Not followed by word character or hyphen
		// Unroll the loop: Inside the opening shortcode tag
		.     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
		.     '(?:'
		.         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
		.         '[^\\]\\/]*'               // Not a closing bracket or forward slash
		.     ')*?'
		.     '[\\w\\s="\']*'                // Shortcode attributes
		.     '(?:'
		.         '\\s*+'                    // Optional leading whitespace, supports [footag /]
		.         '\\/\\]'                   // Self closing tag and closing bracket
		.     '|'
		.         '\\]'                      // Closing bracket
		.         '(?:'                      // Unroll the loop: Optionally, anything between the opening and closing shortcode tags
		.             '(?!<\/p>)'            // Not followed by closing paragraph
		.             '[^\\[]*+'             // Not an opening bracket, matches all content between closing bracket and closing shortcode tag
		.             '(?:'
		.                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
		.                 '[^\\[]*+'         // Not an opening bracket
		.             ')*+'
		.             '\\[\\/\\2\\]'         // Closing shortcode tag
		.         ')?'
		.     ')'
		. ')'
		. '\\s*+'                            // optional trailing whitespace
		. '<\\/p>'                           // closing paragraph
		. '/s';

	return preg_replace( $pattern, '$1', $pee );

}

/**
 * Newline preservation help function for cw_wpautop
 *
 * @since 3.1.0
 * @access private
 *
 * @param array $matches preg_replace_callback matches array
 *
 * @returns string
 */
function _cw_autop_newline_preservation_helper( $matches ) {
	return str_replace("\n", "<WPPreserveNewline />", $matches[0]);
}

/**
 *
 * tends to add <p> around <ul> or any other html block. not good when mixing plain text
 * with a few html blocks.
 *
 * https://stackoverflow.com/a/7409591/7220351
 *
 * @param      $string
 * @param bool $line_breaks
 * @param bool $xml
 *
 * @return string
 */
function nl2p( $string, $line_breaks = false, $xml = true ) {

	$string = str_replace( array( '<p>', '</p>', '<br>', '<br />' ), '', $string );

	// It is conceivable that people might still want single line-breaks
	// without breaking into a new paragraph.
	if ( $line_breaks == true ) {
		$ret = '<p>' . preg_replace( array( "/([\n]{2,})/i", "/([^>])\n([^<])/i" ), array(
				"</p>\n<p>",
				'$1<br' . ( $xml == true ? ' /' : '' ) . '>$2'
			), trim( $string ) ) . '</p>';
	} else {
		$ret = '<p>' . preg_replace( array(
				"/([\n]{2,})/i",
				"/([\r\n]{3,})/i",
				"/([^>])\n([^<])/i"
			), array( "</p>\n<p>", "</p>\n<p>", '$1<br' . ( $xml == true ? ' /' : '' ) . '>$2' ),

				trim( $string ) ) . '</p>';
	}

	$ret = str_replace( '<p></p>', '', $ret );

	return $ret;
}

/**
 * Don't be too fooled as regular tags can still have onClick or onLoad
 * attributes and therefore still execute javascript.
 *
 * @param $str
 */
function gp_strip_script_tags( $str ) {
	$tags = '<h1><h2><h3><h4><h5><h6><p><span><br><a><ul><ol><li><hr>';
	return strip_tags( $str, $tags );
}

/**
 * function name is a bit misleading. it actually tries to keep most tags, but not script.
 *
 * @param $str
 * @return string
 */
function gp_strip_tags( $str ) {
	return gp_strip_script_tags( $str );
}

/**
 * wraps <p> around line breaks. allows html, but not <script>
 *
 * @param $string
 * @return string
 */
function gp_render_textarea_content( $string ) {

    $string = $string ?? '';

	$ret = trim( $string );

	if ( $ret ) {
		$ret = cw_wpautop( $ret );
	}

	$ret = gp_strip_script_tags( $ret );
	$ret = trim( $ret );
	return $ret;
}

/**
 * @param $prefix
 * @param $array
 * @return array
 */
function gp_array_keys_strip_prefix( $prefix, $array ) {
	$new_array = $array;
	if ( strlen( $prefix ) > 0 && is_array( $array ) ) {
		// need to start empty to preserve the order
		$new_array = array();
		foreach ( $array as $key => $value ) {
			$new_key               = gp_strip_prefix( $prefix, $key );
			$new_array[ $new_key ] = $value;
		}
	}

	return $new_array;
}

/**
 * @param $pre
 * @param $string
 * @return false|string
 */
function gp_strip_prefix( $prefix, $string ) {
	if ( substr( $string, 0, strlen( $prefix ) ) == $prefix ) {
		$string = substr( $string, strlen( $prefix ) );
	}

	return $string;
}

/**
 * @param $prefix
 * @param $array
 * @return array
 */
function gp_array_keys_add_prefix( $prefix, $array ) {

	$new_array = $array;

	if ( strlen( $prefix ) > 0 && is_array( $array ) ) {

		// need to start empty to preserve the order
		$new_array = array();

		foreach ( $array as $key => $value ) {
			$new_key               = $prefix . $key;
			$new_array[ $new_key ] = $value;
		}

	}

	return $new_array;
}

/**
 * @param $value
 * @return string
 */
function gp_sanitize_textarea( $value ) {
	// this probably won't work well for all purposes..
	return gp_test_input( $value );
}

/**
 * @param $arr
 * @return array
 */
function gp_sanitize_array_depth_1( $arr ) {

	// important fallback if you are dealing with user input.
	// fallback to empty array if expected value is not an array.
	if ( ! is_array( $arr ) ) {
		return array();
	}

	$ret = array();

	foreach ( $arr as $k => $v ) {

		// in case $v is an array, make it an empty string
		$v = gp_force_singular( $v );

		$ret[ gp_test_input( $k ) ] = gp_test_input( $v );
	}

	return $ret;
}

/**
 * @param $str
 * @return string|string[]
 */
function htmlspecialchars_but_allow_ampersand( $str ) {

    if ( ! is_scalar( $str ) ) {
        return '';
    }

    $str = htmlspecialchars( $str );

    // note: htmlspecialchars( htmlspecialchars( "&" ) ) => "&amp;amp;"
    // the loop: &amp;amp; => &amp; => &
    while( strpos( $str, '&amp;' ) !== false ) {
        $str = str_replace( "&amp;", "&", $str );
    }

    return $str;
}

/**
 * sanitize user input that's expected to be a string and not contain
 * html and stuff like that.
 *
 * @param $data
 *
 * @return string
 */
function gp_test_input( $data ) {

    $data = $data ?? '';

	// if we expect input to be string, and its not, then it becomes an empty string, not raw uncleaned data
	if ( ! gp_is_singular( $data ) ) {
		return '';
	}

	// avoid un-intended type casting in these cases
	// its debatable whether or not we should do this.
	// I guess....... don't, because we built the system without it.
	// when you want to control your false like values without un-intended typecasting,
	//do it outside of this function.
//	if ( $data === null || $data === false || $data === 0 || $data === "" || $data === "0" ){
//		return $data;
//	}

	$data = trim( $data );
	$data = stripslashes( $data );
    $data = htmlspecialchars( $data, ENT_QUOTES );

	// double ampersands in original str
	while( strpos( $data, '&amp;&amp;' ) !== false ) {
		$data = str_replace( '&amp;amp;', '&amp;', $data );
	}

	return $data;
}

/**
 * @param $str
 * @param bool $allow_ampersand
 * @return string|string[]
 */
function gp_test_input_alt( $str, $allow_ampersand = true ) {
    $str = gp_test_input( $str );

    if ( $allow_ampersand ) {
        while( strpos( $str, '&amp;' ) !== false ) {
            $str = str_replace( "&amp;", "&", $str );
        }
    }

    return $str;
}

/**
 * @param $url
 * @return string
 */
function gp_sanitize_href( $url ) {

    $url = is_scalar( $url ) ? $url : '';

    // not sure this really matters.
    $url = stripslashes( $url );

    // entquotes needed to ensure its safe when placed inside
    // of single quotes I think.
    // we could use filter_var, but i'm not totally sure what's better.
    $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8', false);

    // prevent <a href="javascript:alert(document.cookie)"></a>
    // not totally sure this is sufficient, but idk.
    if ( strpos( strtolower( $url ), 'javascript:' ) === 0 ) {
        return '';
    }

    // allow ampersand.
    // double encode false in htmlspecialchars may make this redundant.
    while( strpos( $url, '&amp;' ) !== false ) {
        $url = str_replace( "&amp;", "&", $url );
    }


    return $url;
}

/**
 * @param $str
 *
 * @return mixed|string
 */
function trim_br_spaces( $str ) {
	$str = str_replace( '<br />', '', $str );
	$str = str_replace( '<br>', '', $str );
	$str = trim( $str );

	return $str;
}

/**
 * @param $img_url
 */
function gp_get_img_style( $img_url ) {
	return $img_url ? 'background-image: url(\'' . $img_url . '\')' : '';
}

/**
 * Make sure we have a semi-colon at the end, unless string is empty
 *
 * @param $str
 */
function gp_fix_style( $str ) {
	if ( $str ) {
		$str = trim( $str );
		$str = trim( $str, ';' );
		$str = trim( $str );
		$str = $str . ';';
	}

	return $str;
}

/**
 * When you think you have a string, but might have an array..
 *
 * Apparently is_scalar() is / should be the same as this.
 *
 * @param $str
 *
 * @return bool
 */
function gp_is_singular( $str ) {
	return is_string( $str ) || is_int( $str ) || is_float( $str ) || is_bool( $str ) || is_null( $str );
}

/**
 * Makes long arrays easier to read on javascript alerts, not used for production however.
 *
 * @param      $arr
 * @param null $depth - internal function parameter, do not use.
 *
 * @return string
 */
function gp_array_to_js_alert( $arr, $depth = null ) {

	$op = '';
	$op .= '';

	$arr = gp_make_array( $arr );

	$depth = $depth !== null ? $depth : 0;

	if ( is_array( $arr ) && $arr ) {
		$count = 0;
		foreach ( $arr as $k => $v ) {

			$count ++;

			$pre = '[' . $count . ']';

			if ( $depth !== 0 ) {
				$pre .= '{' . $depth . '}';
			}

			$pre .= ' - ';

			if ( gp_is_singular( $v ) ) {
				$op .= '   ' . $pre . $v;
			} else {
				$op .= gp_array_to_js_alert( $v, $depth );
			}
		}
	}

	return $op;
}

/**
 * @param $arr
 *
 * @return string
 */
function gp_array_to_list( $arr ) {

	$op = '';
	$op .= '';

	if ( is_object( $arr ) ) {
		$arr = get_object_vars( $arr );
	}

	if ( is_array( $arr ) && $arr ) {

		$op .= '<ul>';

		foreach ( $arr as $k => $v ) {
			$op .= gp_array_to_list( $v );
		}

		$op .= '</ul>';

	} else if ( gp_is_singular( $arr ) ) {
		$op .= '<li>' . $arr . '</li>';
	}

	return $op;
}

/**
 * not to be used to make something pretty, but when you'd rather
 * var dump an array or object, rather than encountering a php error.
 * you may want to sanitize the data afterwards, especially if you are expecting it
 * to be a string and it ends up being an object or an array, and possibly came from a form.
 *
 * @param $thing
 */
function gp_make_singular( $thing ) {

	if ( gp_is_singular( $thing ) ) {
		return $thing;
	}

	// should we do this?? what about json_decode( '{}' ) ?
	if ( is_array( $thing ) && ! $thing ) {
		return '{}';
	}

	//	ob_start();
	//	var_dump( $thing );
	//	return ob_get_clean();

	// better way i think...
	return json_encode( $thing );
}

/**
 * the point of this function is not just blindly cast unknown float values to (int) because
 * it is known that this is not reliable. I think if we round first, then its ok to cast to int.
 *
 * some testing may need to be done to be sure..
 *
 * @param $thing
 */
function gp_force_int( $thing ) {

	if ( ! gp_is_singular( $thing ) ) {
		return 0;
	}

	$int = round( $thing, 0 );
	$int = (int) $int;
	return $int;
}

/**
 * For when you expect user input to be a string, and don't want fatal errors
 * if it comes in as an array.
 *
 * @param $thing
 * @param string $default
 * @return mixed|string
 */
function gp_force_singular( $thing, $default = '' ) {

	if ( gp_is_singular( $thing ) ) {
		return $thing;
	}

	return $default;
}

/**
 * @param $thing
 *
 * @return string
 */
function gp_get_print_r( $thing ) {
	$op = '';
	$op .= '<pre>';
	$op .= print_r( $thing, true );
	$op .= '</pre>';

	return $op;
}

/**
 * this is more complicated then it needs to be. recommend just keeping it simple and maybe not using this.
 *
 * @param        $str
 * @param string $method
 * @param string $default
 */
function gp_sanitize_string( $val, $method = 'basic', $args = array() ) {

	// default isn't always used, but in some cases we might return a default, ie. if trying
	// to force an integer, and the value is a string of letters
	$default = gp_if_set( $args, 'default', '' );

	// may convert the value into a string representation of it, or to the default, however, we don't return it at that point
	// we'll still apply any sanitation functions afterwards to ensure you don't get unclean data back after calling this
	// function.
	if ( ! gp_is_singular( $val ) ) {
		$if_not_singular = gp_if_set( $args, 'if_not_singular', 'make_singular' );
		switch ( $if_not_singular ) {
			case 'make_singular':
				$val = gp_make_singular( $val );
				break;
			case 'throw_exception':
				throw new Exception( 'Expecting string in `gp_sanitize_string`, but got object or an array' );
				break;
			case 'default':
				$val = $default;
				break;
			default:
				$val = $default;
				break;
		}
	}

	$arr = explode( '|', $method );

	if ( $arr && is_array( $arr ) ) {
		foreach ( $arr as $case ) {
			switch ( $case ) {
				case 'raw':
					// do nothing
				case 'trim':
					$val = trim( $val );
					break;
				case 'basic':
					$val = gp_test_input( $val );
					break;
				case 'test_input':
					$val = gp_test_input( $val );
					break;
				case 'safe_tags':
					$val = strip_tags( $val, '<p><br><a><div>' );
					break;
				case 'int':
					$val = trim( $val );
					$val = $val ? (int) $val : $default;
					$val = (int) $val;
					break;
				case 'force_int':
					$val = trim( $val );
					$val = (int) $val;
					break;
				default:
					break;
			}
		}
	} else {
		return $default;
	}

	return $val;
}

/**
 * @param array $cols
 */
function gp_get_table_headers_html_from_array( $cols = array() ) {

	$html = '';

	if ( is_array( $cols ) == false ) {
		return '';
	}

	$html .= '<tr>';
	foreach ( $cols as $index => $title ) {
		$html .= '<th>' . $title . '</th>';
	}
	$html .= '</tr>';

	return $html;
}

/**
 * Note that this grabs a single row and should be used inside a loop in most cases...
 *
 * @param $cols - We check the index of this array before printing data, and also for ordering
 * @param $data - An array where index=>html
 *
 * @return string
 */
function gp_get_table_row_html_from_arrays( $cols, $data, $args = array() ) {

	$sanitize         = gp_if_set( $args, 'sanitize', 'test_input' );
	$sanitize_options = gp_if_set( $args, 'sanitize_options', array() );
	$html             = '';
	$html             .= '<tr>';
	if ( $cols ) {
		foreach ( $cols as $index => $title ) {

			$cell_data = gp_if_set( $data, $index );

			$str = gp_make_singular( $cell_data );

			if ( $sanitize ) {
				$str = gp_sanitize_string( $str, $sanitize, $sanitize_options );
			}

			$html .= '<td>' . $str . '</td>';
		}
	}
	$html .= '</tr>';

	return $html;
}

/**
 * @param $table
 */
function select_all_from_table( $table ) {

	$db = get_database_instance();

	$q = 'SELECT * ';
	$q .= 'FROM ' . gp_esc_db_col( $table ) . ' ';
	$q .= ';';

	return $db->get_results( $q, [] );
}

/**
 *
 */
function gp_db_table_to_html_table( $table, $args = array() ) {

	$args[ 'count' ] = true;

	$q  = 'SELECT * ';
	$q  .= 'FROM ' . $table . ' ';
	$q  .= ';';
	$db = get_database_instance();

	$st = $db->pdo->prepare( $q );
	$st->execute();
	$r = $st->fetchAll();

	return gp_db_results_to_html_table( $r, $args );
}

/**
 * @param        $r
 * @param string $cols
 * @param array  $skip
 */
function gp_db_results_to_html_table( $r, $args = array() ) {

	// add a left column for row count?
	$count = gp_if_set( $args, 'count', false );

	$cols = gp_if_set( $args, 'cols', 'all' );
	if ( ! $cols && ! is_array( $cols ) ) {
		$cols = 'all';
	}

	$skip = gp_if_set( $args, 'skip', array() );

	$col_map = array();
	$op      = '';

	// assemble the column names in an array..
	if ( $cols == 'all' ) {
		$cols = array();
		$r1   = gp_array_first( $r );
		if ( $r1 && is_array( $r1 ) || is_object( $r1 ) ) {
			foreach ( $r1 as $cc => $vv ) {
				$cols[ $cc ] = $cc; // put the value in the key so its easier to unset later
			}
		}
	}

	// remove skipped columns if any are specified
	if ( $skip ) {
		if ( ! is_array( $skip ) ) {
			$skip = array( $skip );
		}
		if ( $skip ) {
			foreach ( $skip as $sk ) {
				if ( isset( $cols[ $sk ] ) ) {
					unset( $cols[ $sk ] );
				}
			}
		}
	}

	// col map requires key=>value pairs, not just an array of values, which may be the case, but lets make sure
	// in other words, we don't know exactly what format $cols is in.
	// but $col_map will be in a format like, "column_index" => "column_name".. (where column name might be equal to column  index)
	if ( is_array( $cols ) ) {
		foreach ( $cols as $kk => $vv ) {

			if ( gp_is_integer( $kk ) && $vv ) {
				// columns not indexed, ie. array( 'col_1', 'col_2' )
				$col_map[ $vv ] = $vv;
			} else if ( $kk && ! $vv ) {
				$col_map[ $kk ] = $kk;
			} else {
				$col_map[ $kk ] = $vv;
			}
		}
	}

	if ( $count ) {
		$col_map = array_merge( array( '_count' => 'count' ), $col_map );
	}

	// css class
	$cls = array(
		'gp-data-table'
	);

	$cls[] = gp_if_set( $args, 'add_class' );

	$op .= '<table class="' . gp_parse_css_classes( $cls ) . '">';
	$op .= '<tbody>';
	$op .= gp_get_table_headers_html_from_array( $col_map );

	$row_args = array(
		// turn off sanitation. we'll do our own unless data is coming from a callback
		'sanitize' => '',
	);

	if ( $r && is_array( $r ) ) {
		$cc = 0;
		/**
		 * @var          $index
		 * @var stdClass $row
		 */
		foreach ( $r as $index => $row ) {

			// should convert stdClass to array also
			// Do not use same var as in foreach() ($row)
			$_row = gp_force_array( $row );

			if ( $count ) {
				$_row = array_merge( array( '_count' => $cc ), $_row );
				$cc ++;
			}

			// loop through an entire row, and possibly run a callback function. the row itself should be passed
			// into the function. we can use this to format html contents or put form inputs/buttons/links or whatever else.
			if ( $_row && is_array( $_row ) ) {
				foreach ( $_row as $r1 => $r2 ) {

					$callback = gp_if_set( $args, 'row_callback_' . $r1, null );

					if ( $callback && is_callable( $callback ) ) {
						$r2 = call_user_func( $callback, $_row );
					} else {
						// sanitize
						$r2 = gp_test_input( $r2 );
					}

					$_row[ $r1 ] = $r2;
				}
			}

			$op .= gp_get_table_row_html_from_arrays( $col_map, $_row, $row_args );
		}
	}
	$op .= '</tbody>';
	$op .= '</table>';

	return $op;
}


/**
 *
 */
//function gp_get_current_url(){
//}

/**
 * @param $str
 *
 * @return mixed
 */
function gp_esc_db_table( $str ) {
	return gp_make_letters_numbers_underscores( $str );
}

/**
 * @param $table
 * @param $column
 */
function gp_sql_get_selector( $table, $column ) {

	$table  = gp_esc_db_col( $table, false );
	$column = gp_esc_db_col( $column, false );

	if ( $table ) {
		$s = $table . '.' . $column;
	} else {
		$s = $column;
	}

	return gp_esc_db_col( $s, true );
}

/**
 * Note: we rely heavily on this function to prevent SQL injection.
 * Remove characters that are not letter, numbers, or underscores.
 *
 * This applies to columns like... "something_id", and also to table/col selectors
 * like "table_name.column_name"
 *
 * @param $str
 *
 * @return mixed
 */
function gp_esc_db_col( $str, $allow_dot = true ) {

	$str = gp_force_singular( $str );

	// we allow dash.. although, i don't think we need to.
	if ( $allow_dot ) {
		$pattern = '[^\-._a-zA-Z0-9]';
	} else {
		$pattern = '[^\-_a-zA-Z0-9]';
	}

	$str = preg_replace( '/' . $pattern . '/', '', $str );

	return $str;
}

/**
 * @param $str
 */
function gp_make_letters_numbers_underscores( $str ) {
	if ( is_array( $str ) ) {
		$arr = array_map( 'gp_make_letters_numbers_underscores', $str );

		return $arr;
	}
	$str = preg_replace( '/[^A-Za-z0-9_]+/', '', $str );

	return $str;
}

/**
 * @param        $str
 * @param string $replace
 *
 * @return mixed
 */
function gp_replace_whitespace( $str, $replace = '_' ) {
	$str = preg_replace( '/\s+/', $replace, $str );

	return $str;
}

/**
 * @param $str
 *
 * @return string
 */
function gp_trim_comma_space( $str ) {
	$str = trim( $str );
	$str = trim( $str, ',' );
	$str = trim( $str );

	return $str;
}

/**
 * @param $data
 *
 * @return bool
 */
function gp_is_serialized( $data ) {
	// if it isn't a string, it isn't serialized
	if ( ! is_string( $data ) ) {
		return false;
	}
	$data = trim( $data );
	if ( 'N;' == $data ) {
		return true;
	}
	if ( ! preg_match( '/^([adObis]):/', $data, $badions ) ) {
		return false;
	}
	switch ( $badions[ 1 ] ) {
		case 'a' :
		case 'O' :
		case 's' :
			if ( preg_match( "/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data ) ) {
				return true;
			}
			break;
		case 'b' :
		case 'i' :
		case 'd' :
			if ( preg_match( "/^{$badions[1]}:[0-9.E-]+;\$/", $data ) ) {
				return true;
			}
			break;
	}

	return false;
}

/**
 * This function IS NOT RELIABLE right now.
 *
 * @param $thing
 */
function gp_is_json( $thing ) {
	$is = preg_match( '/[^,:{}\\[\\]0-9.\\-+Eaeflnr-u \\n\\r\\t]/', preg_replace( '/"(\\.|[^"\\\\])*"/', '', $thing ) );

	return $is;
}

/**
 * see also: gp_is_json
 *
 * @param        $thing
 * @param string $method
 * @param array  $args
 */
function gp_db_decode( $thing, $method = 'json', $args = array() ) {

	if ( $method === 'json' ) {
		// convert object to array but not other things..
		// not sure how this will behave for strings, boolean, ints, especially empty values etc.
		return gp_make_object_into_array( json_decode( $thing, true ) );

		//		if ( gp_is_json( $thing ) ) {
		//			Debug::add( $thing, 'IS_JSON' );
		//		} else {
		//			Debug::add( 'method is JSON but value is not JSON', 'ERROR' );
		//		}
	}

	if ( $method === 'php' ) {
		if ( gp_is_serialized( $thing ) ) {
			return unserialize( $thing );
		}
	}

	return $thing;
}

/**
 * convert a value into something we can store into a database. Ie. array to json string.
 *
 * @param        $thing
 * @param string $method
 * @param array  $args
 */
function gp_db_encode( $thing, $method = 'json', $args = array() ) {

	if ( gp_is_singular( $thing ) ) {
		Debug::add( $thing, 'SINGULAR' );

		return $thing;
	}

	switch ( $method ) {
		case 'json':
			return json_encode( $thing );
			break;
		case 'php':
			return serialize( $thing );
			break;
		case 'default': // json by default ?
			return json_encode( $thing );
			break;

	}
}

/**
 * returns a timestamp. we may add some timezone logic later on, so we should
 * try to always use this function instead of just time().
 */
function gp_time() {
	return time();
}

/**
 * @param bool $allow_std_class
 */
function gp_force_array( $thing, $allow_std_class = true ) {

	if ( is_array( $thing ) ) {
		return $thing;
	}

	if ( $thing instanceof stdClass && $allow_std_class ) {
		return get_object_vars( $thing );
	}

	return array();
}

/**
 * @param $thing
 */
function gp_convert_object_to_array_recursive( $thing ) {

	if ( is_array( $thing ) || is_object( $thing ) ) {

		$thing = gp_make_array( $thing );

		// i think would be pretty safe to skip is_array() here
		if ( $thing && is_array( $thing ) ) {
			foreach ( $thing as $k=>$v ) {
				$thing[$k] = gp_convert_object_to_array_recursive( $v );
			}
		}

		return $thing;
	}

	// dont convert non array-object values
	return $thing;
}

/**
 * Convert something into an array. If that somethings is "like" false, we should
 * return an empty array, not false/null.
 *
 * @param $thing
 *
 * @return array
 */
function gp_make_array( $thing ) {

	if ( is_array( $thing ) ) {
		return $thing;
	}

	if ( ! $thing ) {
		return array();
	}

	if ( is_object( $thing ) ) {
		$ret = get_object_vars( $thing );

		return $ret;
	}

	if ( gp_is_singular( $thing ) ) {
		$ret = (array) $thing;

		return $ret;
	}

	// not sure we can even get to here
	return array();
}

/**
 * @param $thing
 */
function gp_make_object_into_array( $thing ) {
	if ( is_object( $thing ) ) {
		$thing = gp_make_array( $thing );
	}

	return $thing;
}

/**
 * @param $thing
 *
 * @return string
 */
function get_var_dump( $thing ) {
	ob_start();
	var_dump( $thing );

	return ob_get_clean();
}

/**
 *
 */
function array_to_line_breaks( $arr ) {
	$arr = gp_force_array( $arr );
	$op  = '';
	if ( $arr ) {
		$op .= '<p>' . implode( '<br>', $arr ) . '</p>';
	}

	return $op;
}

/**
 * @param $arr
 */
function wrap_p_tags_array( $arr ) {
	$arr = gp_force_array( $arr );
	return $arr ? '<p>' . implode( '</p><p>', $arr ) . '</p>' : '';
}

/**
 * @param $arr - does not have to be an array
 *
 * @return string
 */
function gp_array_to_paragraphs( $arr ) {
	$str = '';

	if ( ! $arr ) {
		return '';
	}

	if ( gp_is_singular( $arr ) ) {
		if ( strpos( $arr, '<p' ) === false ) {
			$str = '<p>' . $arr . '</p>';
		} else {
			// dont be an idiot and pass in broken html, or you'll just get broken html back
			$str = $arr;
		}
	}

	if ( $arr && is_array( $arr ) ) {
		foreach ( $arr as $a => $b ) {
			$str .= '<p>' . gp_make_singular( $b ) . '</p>';
		}
	}

	return $str;
}

/**
 * this could just be called, "wrap p tags around array values"
 *
 * @param $arr
 */
function gp_parse_error_string( $arr ) {
	return gp_array_to_paragraphs( $arr );
}

/**
 * Cart is completely broken if this function isn't working properly.
 *
 * We dont care specifically about auto increment, although this may behave in that way,
 * we just need to analyze an array and return an array key that doesn't already exist.
 *
 * HOWEVER, be very careful. If we are using integers as array keys, which I believe we are, then
 * lets try to make sure we enter them in descending order, because a lot of other php functions
 * that work on arrays, or even if javascript decodes an array, will often order the array from
 * low to high when it detects all integer indexes. So returning a random integer that is simply
 * not in use by any other array keys is not generally a good idea.
 *
 * @param $arr
 * @param $min
 */
function gp_get_next_array_key( $arr, $min = 1, $taken = array() ) {

	if ( ! gp_is_integer( $min ) ) {
		$min = 1;
	}

	if ( ! is_array( $arr ) ) {
		return $min;
	}

	if ( ! $arr ) {
		return $min;
	}

	$taken = gp_make_array( $taken );

	$largest = $min;
	$keys    = array_keys( $arr );

	// simply looping through the array has some drawbacks if some keys are integers and some are not.
	while ( in_array( $largest, $keys ) || in_array( $largest, $taken ) ) {
		$largest ++;
	}

	return $largest;
}

/**
 * sometimes json encoding things and then sending back to server via ajax converts
 * boolean false to "false"
 *
 * @param $str
 */
function gp_convert_string_boolean( $str ) {

	if ( $str === "false" ) {
		$str = false;
	}

	if ( $str === "0" ) {
		$str = 0;
	}

	if ( $str === "true" ) {
		$str = true;
	}

	// do we convert "1" into 1 ? "1" into true ??? does "0" become (int) 0, or boolean false,
	// these are tough questions to answer, and depend on the specific use case.

	return $str;
}

/**
 * @param $arr
 */
function gp_email_details( $arr ) {

	$op = '';

	if ( $arr && is_array( $arr ) ) {
		foreach ( $arr as $key => $value ) {
			$op .= '<p>';
			$op .= '<strong>' . $key . ': </strong>';
			$op .= gp_make_singular( $value );
			$op .= '</p>';
		}
	}

	return $op;
}

/**
 * Is the thing an array, where each item is singular?
 *
 * @param $arr
 */
function gp_is_array_depth_1( $arr ) {

	if ( ! is_array( $arr ) ) {
		return false;
	}

	// its not of depth more than 1.. so...
	if ( count( $arr ) === 0 ) {
		return true;
	}

	$is = true;
	foreach ( $arr as $k => $v ) {
		if ( ! gp_is_singular( $v ) ) {
			$is = false;
		}
	}

	return $is;
}

/**
 * @param $bool
 *
 * @return int
 */
function bool_to_plus_minus_1( $bool ) {
	return $bool ? 1 : -1;
}

/**
 * @param $arr
 */
function get_debug_array( $arr ) {

	$ret = array();

	if ( is_array( $arr ) ) {
		foreach ( $arr as $k=>$v ) {

			if ( gp_is_singular( $v ) ){
				$ret[$k] = $v;
			} else{

				if ( is_array( $v ) || is_object( $v ) ) {
					$v = '__count__: ' . count( $v );
				} else{
					$v = $v ? 1 : '';
				}

				$ret[$k] = $v;
			}
		}
	}

	return $ret;
}

/**
 * Capture the output of an anon function passed in.
 *
 * Can be used in place of a self invoking anon function when
 * passing this as a parameter to another function for example.
 *
 * @param $anon_function_or_string
 *
 * @return string
 */
function gp_capture_output( $anon_function_or_string ){

	ob_start();

	if ( is_callable( $anon_function_or_string ) ) {
		$anon_function_or_string();
	} else if ( gp_is_singular( $anon_function_or_string ) ) {
		echo $anon_function_or_string;
	}

	return ob_get_clean();
}

/**
 * Returns a string of css classes...
 *
 * this_function( $args, 'css-class' ) likely results in "css-class",
 *
 * but $args can override this value, and also add to it.
 *
 * In almost all cases I use $args['base_class'] and $args['add_class'].
 *
 * $args['base_class'] is almost never used, there is hardly ever a reason for it.
 *
 * $args['add_class'] is much more common however.
 *
 * @param        $target_array
 * @param        $default_base_class - can be overriden via $args[$index_base]
 * @param string $base_class_index
 * @param string $add_class_index
 *
 * @return string
 */
function gp_extract_css_classes( $args = array(), $default_base_class = '', $index_base = 'base_class', $index_add = 'add_class' ) {
	$base_class = $index_base ? gp_if_set( $args, $index_base, $default_base_class ) : $default_base_class;
	$add_class  = $index_add ? gp_if_set( $args, $index_add, '' ) : '';

	// do not append strings. $add_class could be array. gp_class_list will handle nested arrays.
	return gp_class_list( [ $base_class, $add_class ] );
}

/**
 * alias for function with longer than necessary name
 *
 * @param $classes
 *
 * @return string
 */
function gp_class_list( $classes ) {
	return gp_parse_css_classes( $classes );
}

/**
 * This can replace foreach loops and generally help reduce the total number
 * of lines of code..
 *
 * @param $input
 * @param $callback - accepts parameters: $key, $value, $carry, must return $carry every time.
 *
 * @return string
 */
function gp_safe_array_reduce_on_keys_and_values( $input, $callback, $initial = null ) {

	$ret = $initial;

	// perhaps we could check is_iterable() but idk if thats even a function or if its works on certain php versions
	if ( $input && ( is_array( $input ) || is_object( $input ) ) ) {
		foreach ( $input as $key => $value ) {

			// intentionally do not check if $callback is callable, I would prefer to throw default error/warning
			$ret = $callback( $key, $value, $ret );
		}
	}

	return $ret;
}

/**
 * Implode an array of html strings... and stop wasting time
 * with ternary operators and many lines that could be done on 1.
 *
 * @param        $arr
 * @param bool   $filter
 * @param string $separator
 */
function gp_implode_html( $arr, $filter = true, $separator = "\r\n" ) {
	$arr = $arr && is_array( $arr ) ? $arr : [];
	$arr = $filter ? array_filter( $arr ) : $arr;

	return implode( $separator, $arr );
}