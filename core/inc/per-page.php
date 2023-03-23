<?php

/**
 * Make sure to register valid contexts within here
 */
function get_valid_per_page_preference_contexts(){
	return array(
		'admin',
        'front_end',
		'default_context',
		// 'tires',
		// 'rims',
		// 'packages',
	);
}

/**
 * @param $context
 * @param $value
 * @return bool
 */
function set_user_per_page_preference( $context, $value ) {

    if ( ! in_array( $context, get_valid_per_page_preference_contexts() ) ){
        return false;
    }

    $_SESSION['per_page'][$context] = (int) gp_force_singular( $value );
    return true;
}

/**
 * @param $context
 * @param int $default
 * @return int
 */
function get_per_page_preference( $context, $default = 9 ) {
    $v = @$_SESSION['per_page'][$context];
    $v = $v ? $v : $default;
    return (int) gp_force_singular( $v );
}

/**
 * @return array
 */
function get_admin_per_page_options(){
	return array(
		20 => '20 per page',
		50 => '50 per page',
		100 => '100 per page',
		250 => '250 per page',
		500 => '500 per page',
		1000 => '1000 per page',
		5000 => '5000 per page',
		- 1 => 'no limit',
	);
}

/**
 * @param $context
 * @param $options
 * @param int $default
 * @return string
 */
function get_per_page_options_html_admin( $context, $options, $default = 20 ){

	$context = gp_test_input( $context );
	$current_value = get_per_page_preference( $context );
	if ( ! $current_value ) {
		$current_value = $default;
	}

	$op = '';
	$op .= '<form class="form-style-1 per-page-ajax per-page-ajax-admin" action="' . AJAX_URL . '" data-reload="1">';
	$op .= get_ajax_hidden_inputs( 'set_per_page' );
	$op .= '<input type="hidden" name="context" value="' . $context . '">';

	$op .= '<div class="form-items">';

	$op .= '<div class="item-wrap item-per_page">';
	$op .= '<div class="item-inner on-white">';

	$op .= '<select name="per_page">';

	if ( $options ) {
		foreach ( $options as $key=>$value ) {
			$key = gp_test_input( $key );
			$selected = $key == $current_value ? ' selected="selected"' : '';

			$op .= '<option value="' . $key . '"' . $selected . '>';
			$op .= gp_test_input( $value );
			$op .= '</option>';
		}
	}

	$op .= '</select>';

	$op .= '</div>'; // item-inner
	$op .= '</div>'; // item-wrap

	$op .= '</div>'; // form-items

	$op .= '</form>';

	return $op;

}

/**
 * Session storage for this value. This prevents "clear filters" from affecting this value.
 * Instead, per page preferences are based on context ('rims', 'tires', 'whatever').. once a user
 * sets up a per page preference for a context, it remains the same. This means that when a user changes it
 * we submit an ajax request to change the value in session, and when that request comes back successfully,
 * we can then submit a form that is it linked to, via attribute data-submit
 *
 * @param $context
 * @param $options
 * @param $selected_value
 * @return string
 */
function get_per_page_options_html( $context, $options, $selected_value ) {

	$op = '';
	$op .= '<form class="form-style-1 per-page-ajax" action="' . AJAX_URL . '" data-submit="#product-filters">';
	$op .= get_ajax_hidden_inputs( 'set_per_page' );
	$op .= '<input type="hidden" name="context" value="' . gp_test_input( $context ) . '">';
    $op .= '<input type="hidden" name="selected_value_for_debug" value="' . gp_test_input( $selected_value ) . '">';

	$op .= '<div class="form-items">';

	$op .= '<div class="item-wrap item-per_page">';
	$op .= '<div class="item-inner select-2-wrapper on-white">';

	$op .= '<select name="per_page">';

	if ( $options ) {
		foreach ( $options as $key=>$value ) {
			$key = gp_test_input( $key );
			$selected = $key == $selected_value ? ' selected="selected"' : '';

			$op .= '<option value="' . $key . '"' . $selected . '>';
			$op .= gp_test_input( $value );
			$op .= '</option>';
		}
	}

	$op .= '</select>';

	$op .= '</div>'; // item-inner
	$op .= '</div>'; // item-wrap

	$op .= '</div>'; // form-items

	$op .= '</form>';
	return $op;
}