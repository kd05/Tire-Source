<?php

/**
 * @param $form_value
 */
function checkout_form_get_ship_to_text( $form_value, $locale ) {
	switch ( $form_value ) {
		case 'address':
			return 'Ship to my home, office, or local installer';
		case 'pickup':
			// its far easier to put (not available) than it is to hide this field unfortunately
			$ret = $locale == 'CA' ? 'Local Pick-Up' : '<span style="text-decoration: line-through">Local Pick-Up</span> (not available when shipping region is U.S.)';

			return $ret;
			break;
		default:
			return gp_test_input( $form_value );
	}
}

/**
 *
 */
function get_credit_card_month_options() {
	$ret = array(
		1 => '01',
		2 => '02',
		3 => '03',
		4 => '04',
		5 => '05',
		6 => '06',
		7 => '07',
		8 => '08',
		9 => '09',
		10 => '10',
		11 => '11',
		12 => '12'
	);

	return $ret;
}

/**
 * @return array
 */
function get_credit_card_year_options() {
	$year    = date( 'Y' );
	$options = array();
	for ( $x = 0; $x < 10; $x ++ ) {
		$options[ $year ] = $year;
		$year ++;
	}

	return $options;
}

/**
 * @param $text
 * @param $id
 */
function get_form_label( $text, $id, $is_required = false ) {

	$op = '';

	if ( $text ) {

		if ( $is_required ) {
			$text .= get_form_label_required_html( $is_required );
		}

		$op .= '<div class="item-label">';
		$op .= '<label for="' . $id . '">' . $text . '</label>';
		$op .= '</div>';
	}

	return $op;
}

/**
 * This should go inside of div.form-items, even if your form doesn't have any items.
 * $text will not be sanitized, to allow for anchor tags and other html.
 *
 * Also, add your own <p> tags around $text. adding plain text might not be styled properly.
 *
 * @param       $text
 * @param array $args
 */
function get_form_response_text( $text, $args = array() ) {

	$op = '';
	$op .= '<div class="response-text">';
	$op .= $text;
	$op .= '</div>';

	return $op;
}

/**
 * sanitize text beforehand.. as it could contain html
 *
 * @param       $text
 * @param array $args
 *
 * @return string
 */
function get_form_header( $text, $args = array() ) {

	$text = gp_force_singular( $text );
	$tagline = gp_if_set( $args, 'tagline' );

	if ( ! $text && ! $tagline ) {
		return '';
	}

	$op = '';
	$op .= '<div class="form-header">';

	if ( $text ) {
		$op .= '<h2>' . $text . '</h2>';
	}

	if ( $tagline ) {
		$op .= '<p>' . $tagline . '</p>';
	}

	$op .= '</div>';

	return $op;
}

/**
 * @param $args
 */
function get_form_textarea( $args ) {

	$name = gp_if_set( $args, 'name' );
	$id   = gp_if_set( $args, 'id', $name );
	$type = 'textarea';

	$value = get_array_value_force_singular( $args, 'value' );

	$placeholder = gp_if_set( $args, 'placeholder', '' );
	$label       = gp_if_set( $args, 'label', '' );

	$cls_1   = [ 'item-wrap', 'type-' . $type, 'item-' . $name ];
	$cls_1[] = gp_if_set( $args, 'add_class_1' );

	$cls_2   = [ 'item-inner' ];
	$cls_2[] = gp_if_set( $args, 'add_class_2' );

	$op = '';
	$op .= '<div class="' . gp_parse_css_classes( $cls_1 ) . '">';

	$op .= get_form_label( $label, $id, gp_if_set( $args, 'req', false ) );

	$op .= '<div class="' . gp_parse_css_classes( $cls_2 ) . '">';

	// by default, sanitize the value.
	$_value = gp_if_set( $args, 'sanitize_value', true ) ? gp_sanitize_textarea( $value ) : $value;

	$op .= array_to_html_element( 'textarea', array(
		'id' => gp_test_input( $id ),
		'name' => gp_test_input( $name ),
		'placeholder' => gp_test_input( $placeholder ),
	), true, $_value );

	$op .= '</div>'; // item-inner
	$op .= '</div>'; // item-wrap

	return $op;

}

/**
 * returns empty string if you pass in false
 *
 * @param bool $is_required
 */
function get_form_label_required_html( $is_required = true ) {

	if ( ! $is_required ) {
		return '';
	}

	$op = '';
	$op .= '<span class="is-req"> *</span>';
	return $op;
}

/**
 * @param $args
 */
function get_form_input( $args ) {

	$name        = gp_if_set( $args, 'name' );
	$disabled    = (bool) gp_if_set( $args, 'disabled', false );
	$id          = gp_if_set( $args, 'id', $name );
	$type        = gp_if_set( $args, 'type', 'text' );
	$value       = get_user_input_singular_value( $args, 'value' );
	$placeholder = gp_if_set( $args, 'placeholder', '' );
	$label       = gp_if_set( $args, 'label', '' );

	$cls_1   = [ 'item-wrap', 'type-' . $type, 'item-' . $name ];
	$cls_1[] = gp_if_set( $args, 'add_class_1' );

	$cls_2   = [ 'item-inner' ];
	$cls_2[] = gp_if_set( $args, 'add_class_2' );

	$op = '';
	$op .= '<div class="' . gp_parse_css_classes( $cls_1 ) . '">';

	$op .= get_form_label( $label, $id, gp_if_set( $args, 'req', false ) );

	$op .= '<div class="' . gp_parse_css_classes( $cls_2 ) . '">';

	$atts           = array();
	$atts[ 'type' ] = gp_test_input( $type );

	$atts[ 'name' ] = $name;
	$atts[ 'id' ]   = $id;

	if ( $placeholder ) {
		$atts[ 'placeholder' ] = gp_test_input( $placeholder );
	}

	// by default, do the sanitation
	$_value = gp_if_set( $atts, 'sanitize_value', true ) ? gp_test_input( $value ) : $value;

	$atts[ 'value' ] = $_value;

	if ( $disabled ) {
		$atts[] = 'disabled';
	}

	$op .= array_to_html_element( 'input', $atts, false, '' );

	// $op .= '<input type="' . gp_test_input( $type ) . '" placeholder="' . gp_test_input( $placeholder ) . '" name="' . gp_test_input( $name ) . '" id="' . gp_test_input( $id ) . '" value="' . $value . '">';
	$op .= '</div>'; // item-inner
	$op .= '</div>'; // item-wrap

	return $op;
}

/**
 * Sanitize before calling if it contains user input
 *
 * Returns like 'data-thing="1" data-other="{..json...}"' from an array as input.
 *
 * @param $arr
 */
function get_data_attributes_string( $arr ) {

	$atts = array();

	if ( $arr && is_array( $arr ) ) {
		foreach ( $arr as $a1 => $a2 ) {

			if ( ! gp_is_singular( $a2 ) ) {
				$a2 = gp_json_encode( $a2 );
			}

			// kind of redundant but w/e
			if ( ! gp_is_singular( $a2 ) ) {
				$a2 = '';
			}

			$a1 = trim( $a1 );

			$atts[] = 'data-' . $a1 . '="' . $a2 . '"';
		}
	}

	$ret = $atts ? implode( ' ', $atts ) : '';

	return $ret;
}

/**
 * my apologies. this function it confusing. it calls get_select_options() and passed
 * its second parameter straight to that function. therefore, 2 parameters for arguments, you'll
 * have to your arguments in the correct array.
 *
 * It gets a bit harder to use with select 2, which is used almost always when we call this.
 * Off of my memory, the parameters you want to know about args:
 * $args['select_2'] = true
 * $args['add_class_2'] = 'on-white' - adds a box shadow
 * $args['add_class_1'] = 'height-sm?????' - forget about this one
 * $args['placeholder'] = "". If you are using a label and an empty placeholder, i'm not sure this works. Even with &nbsp; it doesn't work.
 *
 * Ultimately we should have had a wrapper function such as get_form_select_2, which takes care
 * of the confusing args above.
 *
 * @param       $args
 * @param array $args_options
 *
 * @return string
 */
function get_form_select( $args, $args_options = array() ) {

	// Example:

//	$args = array(
//		'select_2' => true,
//		'label' => 'Select something',
//	);
//
//	$args_options = array(
//		'placeholder' => 'asdkljhasd',
//		'items' => array(
//			1 => 'one',
//			2 => 'two',
//		),
//		'current_value' => 1,
//	);


	$name        = gp_if_set( $args, 'name' );
	$placeholder = gp_if_set( $args, 'placeholder', '' );

	if ( $placeholder && ! isset( $args_options[ 'placeholder' ] ) ) {
		$args_options[ 'placeholder' ] = $placeholder;
	}

	$label         = gp_if_set( $args, 'label', '' );
	$id            = gp_if_set( $args, 'id', $name );
	$disabled      = (bool) gp_if_set( $args, 'disabled', false );
	$multiple      = gp_if_set( $args, 'multiple', false );
	$select_2      = gp_if_set( $args, 'select_2' );
	$select_2_args = gp_if_set( $args, 'select_2_args', array() );

	$data_attributes        = gp_if_set( $args, 'data_attributes', array() );
	$data_attributes_string = get_data_attributes_string( $data_attributes );
	$data_attributes_string = $data_attributes_string ? ' ' . $data_attributes_string : '';

	$data_select_args = $select_2_args ? gp_json_encode( $select_2_args ) : '';

	$cls_1   = [ 'item-wrap', 'type-select', 'item-' . $name ];
	$cls_1[] = gp_if_set( $args, 'add_class_1' );

	$cls_2   = [ 'item-inner' ];
	$cls_2[] = $select_2 ? 'select-2-wrapper' : '';
	$cls_2[] = gp_if_set( $args, 'add_class_2' );

	$op = '';
	$op .= '<div class="' . gp_parse_css_classes( $cls_1 ) . '">';
	$op .= get_form_label( $label, $id, gp_if_set( $args, 'req', false ) );
	$op .= '<div class="' . gp_parse_css_classes( $cls_2 ) . '">';

	$m = $multiple ? ' multiple' : '';

	$atts           = array();
	$atts[ 'name' ] = $name;
	$atts[ 'id' ]   = $id;

	if ( $data_select_args ) {
		$atts[ 'data-select-args' ] = $data_select_args;
	}

	if ( $multiple ) {
		$atts[] = 'multiple';
	}

	if ( $data_attributes_string ) {
		$atts[] = $data_attributes_string;
	}

	if ( $disabled ) {
		$atts[] = 'disabled';
	}

	$select_class = gp_if_set( $args, 'select_class' );
	if ( $select_class ) {
		$atts['class'] = gp_parse_css_classes( $select_class );
	}

	$op .= array_to_html_element( 'select', $atts, false );

	// example:
	//		$args_options = array(
	//			'placeholder' => 'Numbers',
	//			'items' => array( 25 => 'twenty-five' ),
	//			'current_value' => 25,
	//			'key_equals_value' => false,
	//		);

	$op .= get_select_options( $args_options );
	$op .= '</select>';

	$op .= '</div>'; // item-inner
	$op .= '</div>'; // item-wrap

	return $op;
}

/**
 * You should ensure that $args['current_value'] is what you expect in regards to array vs. singular.
 *
 * @param $args
 * @return string
 */
function get_select_options( $args ) {

	$items            = gp_if_set( $args, 'items' );
	$placeholder      = gp_if_set( $args, 'placeholder' );
	$key_equals_value = gp_if_set( $args, 'key_equals_value' );

	$current_value          = gp_if_set( $args, 'current_value' );
	$current_value_is_array = is_array( $current_value );

	if ( ! $current_value_is_array && ! gp_is_singular( $current_value ) ) {
		$current_value = null;
	}

	$op = '';

	// for now, placeholder is just the first option with an empty value.
	// if we want to change this behaviour, we should add an argument.
	if ( $placeholder ) {
		$op .= '<option value="">' . gp_test_input( $placeholder ) . '</option>';
	}

	if ( $items && is_array( $items ) ) {
		foreach ( $items as $k => $v ) {

			if ( $key_equals_value ) {
				$_k = $v;
				$_v = $v;

			} else {
				$_k = $k;
				$_v = $v;
			}

			// will have to do non-strict comparison for singular values to handle numbers properly I think..
			// if we need strict comparison, we should add an argument, but default to non strict
			$selected = $current_value_is_array ? in_array( $_k, $current_value ) : $_k == $current_value;
			$ss       = $selected ? ' selected="selected"' : '';

			// also, lets default to sanitizing all input. If we need to have html inside
			// the options, then we can add an argument to skip sanitation.
			$_k = gp_test_input( $_k );
			$_v = gp_test_input( $_v );

			$op .= '<option value="' . $_k . '"' . $ss . '>' . $_v . '</option>';
		}
	}

	return $op;
}

/**
 *
 */
function get_form_reset_button( $args = array() ) {

	$text = gp_if_set( $args, 'text', '[Reset]' );

	$cls_1   = [ 'item-wrap', 'item-reset-form' ];
	$cls_1[] = gp_if_set( $args, 'add_class_1' );

	$cls_2   = [ 'item-inner' ];
	$cls_2[] = gp_if_set( $args, 'add_class_2' );

	$op = '';
	$op .= '<div class="' . gp_parse_css_classes( $cls_1 ) . '">';
	$op .= '<div class="' . gp_parse_css_classes( $cls_2 ) . '">';
	$op .= '<button type="reset" class="css-reset">' . $text . '</button>';
	$op .= '</div>'; // item-inner
	$op .= '</div>'; // item-wrap

	return $op;
}

/**
 * @param $args
 */
function get_form_submit( $args = array() ) {

	$text = gp_if_set( $args, 'text', 'Submit' );
	$type = gp_if_set( $args, 'type', 'submit' );

	// will add these later if needed
	$name  = gp_if_set( $args, 'name' );
	$value = gp_if_set( $args, 'value' );

	$cls_1   = [ 'item-wrap', 'item-submit' ];
	$cls_1[] = gp_if_set( $args, 'add_class_1' );

	$cls_2   = [ 'item-inner', 'button-1' ];
	$cls_2[] = gp_if_set( $args, 'add_class_2' );

	$op = '';
	$op .= '<div class="' . gp_parse_css_classes( $cls_1 ) . '">';
	$op .= '<div class="' . gp_parse_css_classes( $cls_2 ) . '">';
	$op .= '<button type="' . gp_test_input( $type ) . '">' . gp_test_input( $text ) . '</button>';
	$op .= '</div>'; // item-inner
	$op .= '</div>'; // item-wrap

	return $op;
}

/**
 * @param $args
 */
function get_form_checkbox( $args ) {

	$name     = gp_if_set( $args, 'name' );
	$split    = gp_if_set( $args, 'split', null );
	$id       = gp_if_set( $args, 'id', $name );
	$type     = gp_if_set( $args, 'type', 'checkbox' );
	$value    = get_user_input_singular_value( $args, 'value' );
	$checked  = gp_if_set( $args, 'checked', '' );
	$disabled = gp_if_set( $args, 'disabled', false );
	$label    = gp_if_set( $args, 'label', '' );
	$cc       = $checked ? ' checked' : '';

	// use ID not name because IDs are unique for groups of inputs, and names are not
	// for input type text we just use name however.
	$cls_1   = [ 'item-wrap', 'type-' . $type, 'item-' . $id ];
	$cls_1[] = gp_if_set( $args, 'add_class_1' );
	$cls_1[] = $split !== null ? 'split-' . $split : '';

	$cls_2   = [ 'item-inner' ];
	$cls_2[] = gp_if_set( $args, 'add_class_2' );

	$op = '';
	$op .= '<div class="' . gp_parse_css_classes( $cls_1 ) . '">';
	$op .= '<div class="' . gp_parse_css_classes( $cls_2 ) . '">';

	$atts = array(
		'type' => gp_test_input( $type ),
		'name' => gp_test_input( $name ),
		'id' => gp_test_input( $id ),
		'value' => gp_test_input( $value ),
	);

	if ( $cc ) {
		$atts[] = $cc;
	}

	if ( $disabled ) {
		$atts[] = 'disabled';
	}

	$op .= array_to_html_element( 'input', $atts, false );

	// $op .= '<input type="' . gp_test_input( $type ) . '" name="' . gp_test_input( $name ) . '" id="' . gp_test_input( $id ) . '" value="' . $value . '"' . $cc . '>';

	if ( $label ) {

		// should add nothing if $args['req'] is false
		$label .= get_form_label_required_html( gp_if_set( $args,'req', false ) );

		$op .= '<label for="' . $id . '">' . $label . '</label>';
	}
	$op .= '</div>'; // item-inner
	$op .= '</div>'; // item-wrap

	return $op;
}

/**
 * @param array $args
 */
function get_sign_up_form( $args = array() ) {

	$title = gp_if_set( $args, 'title', 'Sign Up' );

	// form
	$op = '';
	$op .= '<form id="sign-up" class="sign-up-ajax form-style-1 on-white-bg width-sm btn-width-lg" action="' . AJAX_URL . '">';
	$op .= get_ajax_hidden_inputs( 'sign_up' );

	// form header
	if ( $title ) {
		$op .= '<div class="form-header">';
		$op .= '<h2>' . gp_test_input( $title ) . '</h2>';
		$op .= '</div>'; // form-header
	}

	// form items
	$op .= '<div class="form-items">';

	// first
	//	$op .= get_form_input( array(
	//		'label' => 'First Name',
	//		'name' => 'first_name',
	//		'id' => 'su-first_name',
	//	) );
	//
	//	// last
	//	$op .= get_form_input( array(
	//		'label' => 'Last Name',
	//		'name' => 'last_name',
	//		'id' => 'su-last_name',
	//	) );

	// first
	$op .= get_form_input( array(
		'label' => 'First Name',
		'name' => 'first_name',
		'id' => 'su-first-name',
	) );

	// last
	$op .= get_form_input( array(
		'label' => 'Last Name',
		'name' => 'last_name',
		'id' => 'su-last-name',
	) );

	// email
	$op .= get_form_input( array(
		'label' => 'Email',
		'name' => 'email',
		'id' => 'su-email',
	) );

	// password 1
	$op .= get_form_input( array(
		'label' => 'Password',
		'name' => 'password_1',
		'type' => 'password',
		'id' => 'su-password_1',
	) );

	// password 2
	$op .= get_form_input( array(
		'label' => 'Confirm Password',
		'name' => 'password_2',
		'type' => 'password',
		'id' => 'su-password_2',
	) );

	if ( cw_is_admin_logged_in() ) {
		// admin
		$op .= get_form_checkbox( array(
			'label' => 'Make an admin user',
			'name' => 'make_admin',
			'id' => 'su-make_admin',
			'value' => 1,
		) );
	}

	// Submit
	$op .= get_form_submit( array(
		'text' => 'Sign Up',
	) );

	$op .= '</div>'; // form-items

	$op .= '</form>';

	return $op;
}

/**
 * @param array $args
 */
function get_password_reset_form( $key, $args = array() ) {

	if ( $key instanceof Parsed_Forgot_Password_Key ) {
		$parsed = $key;
		$key    = $parsed->key;
	} else {
		$parsed = new Parsed_Forgot_Password_Key( $key );
	}

	$valid = $parsed->is_valid();

	// form options
	$title   = gp_if_set( $args, 'title', 'Reset Password' );
	$form_id = 'reset-password';
	$cls     = [ 'form-style-1', 'reset-password', 'on-white-bg', 'width-sm', 'btn-width-lg' ];
	$cls[]   = ! $valid ? 'invalid' : '';

	// Html Begin
	$op = '';
	$op .= '<form id="' . $form_id . '" class="' . gp_parse_css_classes( $cls ) . '" action="' . AJAX_URL . '">';

	if ( $title ) {
		$op .= get_form_header( $title );
	}

	if ( $valid ) {
		$op .= get_ajax_hidden_inputs( 'reset_password' );
	}

	if ( $valid ) {
		$op .= '<input type="hidden" name="key" value="' . gp_test_input( $key ) . '">';
	}

	$op .= '<div class="form-items">';

	// make sure this is inside of div.form-items
	if ( ! $valid ) {
		// $msg should have <p> tag
		$msg = $parsed->get_error_message();
		$op  .= get_form_response_text( $msg );
	}

	if ( $valid ) {
		$op .= get_form_input( array(
			'type' => 'password',
			'name' => 'password_1',
			'label' => 'Password',
		) );

		$op .= get_form_input( array(
			'type' => 'password',
			'name' => 'password_2',
			'label' => 'Confirm Password',
		) );

		$op .= get_form_submit( array(
			'text' => 'Reset Password',
		) );
	}

	$op .= '</div>'; // form-items

	$op .= '</form>';

	return $op;
}

/**
 * @param array $args
 */
function get_forgot_password_form( $args = array() ) {

	$title = gp_if_set( $args, 'title', 'Forgot Password' );

	$op = '';
	$op .= '<form id="forgot-password" class="form-style-1 on-white-bg width-sm btn-width-lg forgot-password" action="' . AJAX_URL . '">';

	// ajax action and nonce
	$op .= get_ajax_hidden_inputs( 'forgot_password' );

	if ( $title ) {
		$op .= '<div class="form-header">';
		$op .= '<h2>Password Reset</h2>';
		$op .= '</div>';
	}

	$op .= '<div class="form-items">';

	// Email
	$op .= get_form_input( array(
		'name' => 'email',
		'id' => 'fp-email',
		'label' => 'Email',
	) );

	// Submit
	$op .= get_form_submit( array(
		'text' => 'Submit',
	) );

	$op .= '</div>';

	$op .= '</form>';
	$op .= '';

	return $op;

}

/**
 * @param $args
 */
function get_sign_in_form( $args = array() ) {

	$title = gp_if_set( $args, 'title', 'Sign In' );

	$redirect      = gp_if_set( $_GET, 'redirect', gp_if_set( $args, 'redirect' ) );
	$redirect_args = gp_if_set( $_GET, 'redirect_args', gp_if_set( $args, 'redirect_args', array() ) );
	$reload        = gp_if_set( $_GET, 'reload', gp_if_set( $args, 'reload' ) );

	$redirect      = gp_test_input( $redirect );
	$redirect_args = gp_force_array( $redirect_args );

	// form
	$op = '';
	$op .= '<form id="sign-in" class="sign-in-ajax form-style-1 on-white-bg width-sm btn-width-lg" action="' . AJAX_URL . '">';
	$op .= get_ajax_hidden_inputs( 'sign_in' );

	if ( $reload ) {
		$op .= '<input type="hidden" name="reload" value="1">';
	}
	if ( $redirect ) {
		$op .= '<input type="hidden" name="redirect" value="' . $redirect . '">';
	}
	if ( $redirect_args ) {
		$op .= get_hidden_inputs_from_array( array(
			'redirect_args' => $redirect_args,
		) );
	}

	// form header
	if ( $title ) {
		$op .= get_form_header( $title );
	}

	// form items
	$op .= '
    <div class="form-items">';

	// email
	$op .= get_form_input( array(
		'label' => 'Email',
		'name' => 'email',
		'value' => IN_PRODUCTION ? "" : "admin@site.com",
		'id' => 'si-email',
	) );

	// password
	$op .= get_form_input( array(
		'label' => 'Password',
		'name' => 'password',
		'type' => 'password',
        'value' => IN_PRODUCTION ? "" : "password",
		'id' => 'si-password',
	) );

	// Submit
	$op .= get_form_submit( array(
		'text' => 'Sign In',
	) );

	$op .= '</div>'; // form-items

	$op .= '<div class="form-footer-link">';
	$op .= '<a href="' . get_url( 'forgot_password' ) . '">Forgot Your Password?</a>';
	$op .= '</div>';

	$op .= '</form>';

	return $op;
}

/**
 * Sign in and Sign up form.
 */
function get_sign_in_and_sign_up_form( $args = array(), $a1 = array(), $a2 = array() ) {

	// which form starts active ?
	$active = gp_if_set( $args, 'active', 'sign_in' );

	$op = '';
	$op .= '<div class="sign-in-wrapper tabs-wrapper">';

	// Sign In
	$c1   = [ 'tab-item', 'item-sign-in' ];
	$c1[] = $active === 'sign_in' ? 'active' : 'not-active';
	$op   .= '
    <div class="' . gp_parse_css_classes( $c1 ) . '">';

	// Form
	$op .= get_sign_in_form( $a1 );

	// switch forms
	$op .= '
        <div class="switch">';
	$op .= '
            <button type="button" class="tab-trigger css-reset" data-for=".item-sign-up">[Create An Account]</button>
            ';
	$op .= '
        </div>
        ';

	$op .= '
    </div>
    ';

	// Sign Up
	$c2   = [ 'tab-item', 'item-sign-up' ];
	$c2[] = $active === 'sign_up' ? 'active' : 'not-active';
	$op   .= '
    <div class="' . gp_parse_css_classes( $c2 ) . '">';

	// Form
	$op .= get_sign_up_form( $a2 );

	// switch forms
	$op .= '
        <div class="switch">';
	$op .= '
            <button type="button" class="tab-trigger css-reset" data-for=".item-sign-in">[Already Registered?]</button>
            ';
	$op .= '
        </div>
        ';

	$op .= '
    </div>
    '; // sign up

	$op .= '
</div>'; // sign-in-wrapper

	return $op;
}

/**
 *
 */
function get_edit_profile_form( DB_User $user ) {

	$op = '';

	$op .= '<form id="edit-profile" action="' . AJAX_URL . '" class="edit-profile form-style-1 on-white-bg btn-width-lg ajax-general">';

	$op .= get_ajax_hidden_inputs( 'edit_profile' );

	$op .= get_form_header( 'Edit Profile' );

	$op .= '<div class="form-items">';

	// first
	$op .= get_form_input( array(
		'label' => 'First Name',
		'name' => 'first_name',
		'value' => $user->get( 'first_name', null, true ),
	) );

	// last
	$op .= get_form_input( array(
		'label' => 'Last Name',
		'name' => 'last_name',
		'value' => $user->get( 'last_name', null, true ),
	) );

	// email
	$op .= get_form_input( array(
		'label' => 'Email',
		'name' => 'email',
		'value' => $user->get( 'email', null, true ),
	) );

	$op .= get_form_submit( array( 'text' => 'Submit' ) );

	$op .= '</div>';

	$op .= '</form>';

	return $op;
}

/**
 * @param DB_User $user
 */
function get_logged_in_change_password_form( DB_User $user ) {

	$op = '';

	$op .= '<form id="change-password" action="' . AJAX_URL . '" class="change-password form-style-1 on-white-bg btn-width-lg ajax-general">';

	$op .= get_ajax_hidden_inputs( 'change_password' );

	$op .= get_form_header( 'Change Password' );

	$op .= '<div class="form-items">';

	$op .= get_form_input( array(
		'label' => 'Current Password',
		'name' => 'current_password',
		'type' => 'password',
		'value' => '',
	) );

	$op .= get_form_input( array(
		'label' => 'New Password',
		'name' => 'password_1',
		'type' => 'password',
		'value' => '',
	) );

	$op .= get_form_input( array(
		'label' => 'Confirm New Password',
		'name' => 'password_2',
		'type' => 'password',
		'value' => '',
	) );

	$op .= get_form_submit( array( 'text' => 'Change Password' ) );

	$op .= '</div>';

	$op .= '</form>';

	return $op;

}

/**
 * used for some simple admin filters on the edit.php page
 *
 * @param array $cols
 */
function get_multiple_form_select_from_unique_column_values( $table, $cols = array(), $userdata = array() ) {

	$op = '';

	if ( $cols ) {
		foreach ( $cols as $k=>$v ) {
			$op .= get_form_select_from_unique_column_values( $table, $v, get_user_input_singular_value( $userdata, $v ) );
		}
	}

	return $op;
}

/**
 * @param       $table
 * @param       $col
 * @param array $a1
 * @param array $a2
 */
function get_form_select_from_unique_column_values( $table, $col, $current_value = '', $form_name = null, $a1 = array(), $a2 = array() ) {

	$col = gp_test_input( $col );
	$form_name = $form_name ? $form_name : $col;

	// def. need no cache here
	$u = get_all_column_values_from_table( $table, $col, false );
	$items = array();
	$items[''] = ''; // don't do $items[] it ends up as string "0" after for submission

	if ( $u ) {
		foreach ( $u as $u1=>$u2 ) {
			// $u2 => $u2, name is value
			$items[gp_test_input( $u2 )] = gp_test_input( $u2 );
		}
	}

	$args_1 = array(
		'name' => $form_name,
		'label' => $form_name,
	);

	$args_1 = array_merge( $args_1, $a1 );

	$args_2 = array(
		'items' => $items,
		'current_value' => $current_value,
	);

	$args_2 = array_merge( $args_2, $a2 );

	$ret = get_form_select( $args_1, $args_2 );
	return $ret;
}

/**
 * @param null $index
 *
 * @return array|bool|mixed
 */
function get_credit_card_icon_data( $index = null, $locale = null ) {

	$locale = app_get_locale_from_locale_or_null( $locale, true );

	$placeholder_icon = '';

	// we might not use 'name' and 'slug', we basically just need icons..
	$data = array(
		'amex' => array(
			'name' => 'American Express',
			'slug' => 'amex',
			'icon' => 'amex-transparent.png',
			'icon_opaque' => 'amex-transparent.png',
			'alt' => 'American Express icon',
		),
		'visa' => array(
			'name' => 'Visa',
			'slug' => 'visa',
			'icon' => 'visa-transparent.png',
			'icon_opaque' => 'visa-icon.jpg',
			'alt' => 'Visa icon',
		),
		'mastercard' => array(
			'name' => 'Mastercard',
			'slug' => 'mastercard',
			'icon' => 'mastercard-transparent.png',
			'icon_opaque' => 'mastercard-icon.jpg',
			'alt' => 'Mastercard icon',
		),
		'discover' => array(
			'name' => 'Discover',
			'slug' => 'discover',
			'icon' => 'discover-transparent.png',
			'icon_opaque' => 'discover-transparent.png',
			'alt' => 'Discover card icon',
		)
	);

	// filter out US icons if returning more than just 1 icon
	if ( $locale === APP_LOCALE_US && $index === null ) {
		$data = array_filter( $data, function( $key ) {

			// allow only these keys
			return in_array( $key, [
				'mastercard',
				'visa'
			] ) ? true : false;

		}, ARRAY_FILTER_USE_KEY );
	}

	return $index === null ? $data : gp_if_set( $data, $index );
}

/**
 * @param null $locale
 *
 * @return array
 */
function get_credit_card_icon_keys_via_locale( $locale = null ) {
	$locale = app_get_locale_from_locale_or_null( $locale );

	switch( $locale ) {
		case APP_LOCALE_CANADA:
			return [ 'amex', 'visa', 'mastercard', 'discover' ];
			break;
		case APP_LOCALE_US:
			return [ 'visa', 'mastercard' ];
			break;
	}
}

/**
 * Some icons have a transparent and non-transparent version,
 * so you can specify in $args which ones to grab.
 *
 * @param array $args
 * @param null  $locale
 *
 * @return string
 */
function get_credit_card_icons_html( $args = [], $locale = null ){

	$locale = app_get_locale_from_locale_or_null( $locale );

	$transparent = gp_if_set( $args,'transparent', true );

	$get = get_credit_card_icon_keys_via_locale( $locale );

	$op = '';

	if ( $get ) {

		$op .= '<div class="cc-icons">';
		$op .= '<div class="cc-icons-2">';

		foreach ( $get as $g1=>$g2 ) {

			$data = get_credit_card_icon_data( $g2 );

			$cls = [ 'cc-item', 'cc-item-' . gp_if_set( $data, 'slug' ) ];

			if ( $data ) {

				if ( $transparent ) {
					$cls[] = 'cc-transparent';
					$icon = gp_if_set( $data, 'icon' );
				} else {
					$cls[] = 'cc-opaque';
					$icon = gp_if_set( $data, 'icon_opaque', gp_if_set( $data, 'icon' ) );
				}

				$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';
				$op .= '<img src="' . get_image_src( $icon ) . '" alt="' . gp_if_set( $data, 'alt' ) . '">';
				$op .= '</div>';
			}
		}

		$op .= '</div>';
		$op .= '</div>';
	}

	return $op;
}

/**
 * Put this inside of div.item-inner to pick up styles.
 *
 * @param $msg
 *
 * @return string
 */
function get_form_tooltip( $msg, $msg_left = '' ) {
	$op = '';

	$cls = [ 'form-tooltip' ];
	$cls[] = $msg_left ? 'split' : '';

	$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';

	// maybe.. "optional" or "required"
	if ( $msg_left ) {
		$op .= '<p class="left">' . $msg_left . '</p>';
	}

	$op .= '<p class="right">' . $msg . '</p>';
	$op .= '</div>';
	return $op;
}