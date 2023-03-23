<?php

/**
 *
 */
function get_basic_db_col_filter_options( $table, $column ) {

	$values = get_all_column_values_from_table( $table, $column, true );
	$ret    = array();

	if ( $values && is_array( $values ) ) {
		foreach ( $values as $value ) {
			$clean         = gp_test_input( $value );
			$ret[ $clean ] = $clean;
		}
	}

	return $ret;
}

// ************************************
//              Rims
// ************************************

/**
 * @param $name_min
 * @param $name_max
 */
function get_rim_offset_form_items_html( $name_min, $name_max, $userdata = array() ) {

	$val_1 = get_user_input_singular_value( $userdata, $name_min );
	$val_2 = get_user_input_singular_value( $userdata, $name_max );

	$op = '';
	$op .= '<p>';
	$op .= '<input name="' . gp_test_input( $name_min ) . '" value="' . $val_1 . '">';
	$op .= '</p>';

	$op .= '<p>';
	$op .= '<input name="' . gp_test_input( $name_max ) . '" value="' . $val_2 . '">';
	$op .= '</p>';

	return $op;
}

/**
 * @param $name_min
 * @param $name_max
 */
function get_rim_hub_bore_form_items_html( $name_min, $name_max, $userdata = array() ) {

	$val_1 = get_user_input_singular_value( $userdata, $name_min );
	$val_2 = get_user_input_singular_value( $userdata, $name_max );

	$op = '';
	$op .= '<p>';
	$op .= '<input name="' . gp_test_input( $name_min ) . '" value="' . $val_1 . '" placeholder="min">';
	$op .= '</p>';

	$op .= '<p>';
	$op .= '<input name="' . gp_test_input( $name_max ) . '" value="' . $val_2 . '" placeholder="max">';
	$op .= '</p>';

	return $op;
}

/**
 *
 */
function get_rim_diameter_filter_options() {

	$cols = get_all_column_values_from_table( DB_rims, 'diameter', true );

	$ret = array();
	if ( $cols && is_array( $cols ) ) {
		foreach ( $cols as $col ) {
			if ( $col && is_numeric( $col ) ) {
				$ret[ $col ] = gp_test_input( $col );
			}
		}
	}

	return $ret;
}

/**
 *
 */
function get_rim_width_filter_options() {

	$ret  = array();
	$cols = get_all_column_values_from_table( DB_rims, 'width', true );

	if ( $cols && is_array( $cols ) ) {
		foreach ( $cols as $col ) {
			if ( $col && is_numeric( $col ) ) {
				$ret[ $col ] = gp_test_input( $col );
			}
		}
	}

	return $ret;
}

/**
 *
 */
function get_rim_offset_filter_options() {

	$ret = array();

	// get cols and get/set cache
	$cols = get_all_column_values_from_table( DB_rims, 'offset', true );

	if ( $cols && is_array( $cols ) ) {
		foreach ( $cols as $col ) {
			if ( $col ) {
				$ret[ $col ] = gp_test_input( $col );
			}
		}
	}

	return $ret;
}

/**
 *
 */
function get_rim_bolt_pattern_filter_options() {

	$cache_key = 'rim_bolt_patterns';
	$cache     = gp_cache_get( $cache_key );
	if ( is_array( $cache ) ) {
		return $cache;
	}

	$ret = array();

	// dont cache these.. will cache combined result instead
	$cols1 = get_all_column_values_from_table( DB_rims, 'bolt_pattern_1', false );
	$cols2 = get_all_column_values_from_table( DB_rims, 'bolt_pattern_2', false );

	$cols = array_merge( $cols1, $cols2 );
	$cols = array_unique( $cols );
	$cols = array_filter( $cols );

	if ( $cols && is_array( $cols ) ) {
		foreach ( $cols as $col ) {
			if ( $col ) {
				$ret[ $col ] = gp_test_input( $col );
			}
		}
	}

	gp_cache_set( $cache_key, $ret );

	return $ret;
}

/**
 *
 */
function get_rim_hub_bore_filter_options() {

	$ret  = array();
	$cols = get_all_column_values_from_table( DB_rims, 'center_bore', true );

	if ( $cols && is_array( $cols ) ) {
		foreach ( $cols as $col ) {
			if ( $col ) {
				$ret[ $col ] = gp_test_input( $col );
			}
		}
	}

	return $ret;
}

/**
 *
 */
function get_rim_type_filter_options() {
	return Static_Array_Data::export_for_filters( Static_Array_Data::rim_types() );
}

/**
 * @return array
 */
function get_rim_style_filter_options(){
	return Static_Array_Data::export_for_filters( Static_Array_Data::rim_styles() );
}

/**
 *
 */
function get_rim_brand_filter_options() {

	$ret    = array();
	$brands = get_rim_brands();

	if ( $brands && is_array( $brands ) ) {
		/** @var DB_Tire_Brand $brand */
		foreach ( $brands as $brand ) {
			$ret[] = array(
				'value' => $brand->get( 'slug' ),
				'text' => $brand->get( 'name' ),
			);
		}
	}

	return $ret;
}

/**
 * @return array
 */
function get_rim_color_1_filter_options() {
	// finish 1 is primary color
	return get_rim_color_1_data();
}

/**
 * @return array
 */
function get_rim_color_2_filter_options() {
	// finish 2 is secondary color
	return get_rim_color_2_data();
}

/**
 * @return array
 */
function get_rim_finish_filter_options() {
	// finish 3 is actually just called "finish" on the front-end, and hardly ever included in the rims table
	return get_rim_finish_data();
}

/**
 *
 */
function get_rim_color_1_data() {
	return get_rim_finish_data_by_number( 1 );
}

/**
 *
 */
function get_rim_color_2_data() {
	return get_rim_finish_data_by_number( 2 );
}

/**
 * @return array
 */
function get_rim_finish_data() {
	return get_rim_finish_data_by_number( 3 );
}

/**
 * One function because rims table has 6 cols for 3 different finishes
 * that behave pretty similarly. We put the slug and name in the same table..
 *
 * @param int $number
 */
function get_rim_finish_data_by_number( $number = 1 ) {

	switch ( $number ) {
		case 1:
			$primary_col = 'color_1';
			$name_col    = 'color_1_name';
			break;
		case 2:
			$primary_col = 'color_2';
			$name_col    = 'color_2_name';
			break;
		case 3:
			$primary_col = 'finish';
			$name_col    = 'finish_name';
			break;
		default:
			throw new Exception( 'Invalid finish number' );
	}

	$data = get_all_column_values_from_table( DB_rim_finishes, $primary_col, true, [ $name_col ] );

	$ret = array();

	if ( $data && is_array( $data ) ) {
		foreach ( $data as $key => $value ) {
			$slug = get_user_input_singular_value( $value, $primary_col );
			$name = get_user_input_singular_value( $value, $name_col );
			if ( $name && $slug ) {
				$ret[ $slug ] = $name;
			}
		}
	}

	return $ret;

}

// ************************************
//                Tires
// ************************************

/**
 *
 */
function get_tire_brand_filter_options() {

	$ret    = array();
	$brands = get_tire_brands();

	if ( $brands && is_array( $brands ) ) {
		/** @var DB_Tire_Brand $brand */
		foreach ( $brands as $brand ) {

			$ret[] = array(
				'value' => $brand->get( 'slug' ),
				'text' => $brand->get( 'name' ),
			);

		}
	}

	return $ret;
}

/**
 * @return mixed
 */
function get_tire_type_filter_options() {
	return Static_Array_Data::export_for_filters( Static_Array_Data::tire_model_types() );
}

/**
 * @return mixed
 */
function get_tire_class_filter_options() {
	return Static_Array_Data::export_for_filters( Static_Array_Data::tire_model_classes() );
}

/**
 * @return mixed
 */
function get_tire_category_filter_options() {
	return Static_Array_Data::export_for_filters( Static_Array_Data::tire_model_categories() );
}

/**
 * @param        $value
 * @param string $default - may want to make this $value
 */
function get_speed_rating_name( $value, $default = '' ) {

	// showing both km and mph doesn't fit nicely into filters so, show just 1 i guess
	if ( app_get_locale() === 'US' ) {

		$names = array(
			'l' => 'L (75 mph)',
			'm' => 'M (81 mph)',
			'n' => 'N (87 mph)',
			'p' => 'P (93 mph)',
			'q' => 'Q (99 mph)',
			'r' => 'R (106 mph)',
			's' => 'S (112 mph)',
			't' => 'T (118 mph)',
			'u' => 'U (124 mph)',
			'h' => 'H (130 mph)',
			'v' => 'V (149 mph)',
			'w' => 'W (168 mph)',
			'y' => 'Y (186 mph)',
		);

	} else {

		$names = array(
			'l' => 'L (120 km/h)',
			'm' => 'M (130 km/h)',
			'n' => 'N (140 km/h)',
			'p' => 'P (150 km/h)',
			'q' => 'Q (160 km/h)',
			'r' => 'R (170 km/h)',
			's' => 'S (180 km/h)',
			't' => 'T (190 km/h)',
			'u' => 'U (200 km/h)',
			'h' => 'H (210 km/h)',
			'v' => 'V (240 km/h)',
			'w' => 'W (270 km/h)',
			'y' => 'Y (300 km/h)',
		);

	}



	$ret = gp_if_set( $names, strtolower( $value ), $default );
	return $ret;
}

/**
 *
 */
function get_tire_speed_rating_filter_options() {

	$raw = get_basic_db_col_filter_options( DB_tires, 'speed_rating' );

	$items = array();

	// replace a name of "V" with something like "V (up to xxx km/h)
	if ( $raw ) {
		// note, $k is equal to $v I believe
		foreach ( $raw as $k=>$v ) {
			$items[$k] = get_speed_rating_name( $v, $v );;
		}
	}

	return $items;
}

/**
 *
 */
function get_load_index_filter_options() {
	return array(
		'80' => 'At least 80',
		'85' => 'At least 85',
		'90' => 'At least 90',
		'95' => 'At least 95',
		'100' => 'At least 100',
	);
}

// ************************************
//              Other
// ************************************

/**
 * @param $title
 * @param $body
 * @param array $args
 * @return string
 */
function get_sidebar_accordion_item( $title, $body, $args = array() ) {
	$ai        = new Sidebar_Accordion_Item( $args );
	$ai->title = $title;
	$ai->body  = $body;

	return $ai->render();
}

/**
 * @param array $args
 * @return string
 */
function get_product_sidebar_filter( $args = array() ) {

	$title    = gp_if_set( $args, 'title', '' );
	$start_open = gp_if_set( $args, 'start_open', false );
	$type     = gp_if_set( $args, 'type', 'radio' );
	$name     = gp_if_set( $args, 'name', '' );
	$items    = gp_if_set( $args, 'items', array() );
	$items = gp_force_array( $items );
	$userdata = gp_if_set( $args, 'userdata', array() );

	// by default, we will not render with no items. but we'll allow this to be changed
	$hide_if_no_items = gp_if_set( $args, 'hide_if_no_items', true );
	
	if ( $hide_if_no_items && count( $items ) === 0 ) {
		return '';
	}

	$is_checkbox = $type !== 'radio';

	// yes type-$slug not type-$type. this goes on the accordion item. Ie.. .sidebar-accordion-item.type-rim_color_1
	$cls   = [ 'type-' . gp_if_set( $args, 'slug' ) ];
	$cls[] = gp_if_set( $args, 'add_class' );

	$accordion_item_args = array(
		'add_class' => $cls,
	);

	// pass some $args into the $checkbox_args
	$checkbox_args = array();
	$checkbox_args['items'] = $items;
	$checkbox_args['name'] = $name;
	// definitely default this to true. Only for some required items will be change this to false (ie. package type)
	$checkbox_args['radio_button_can_be_unchecked'] = gp_if_set( $args, 'radio_button_can_be_unchecked', true );
	$checkbox_args['icon_map'] = gp_if_set( $args, 'icon_map', array() );

	// passed by reference into get_filter_checkbox_or_radio_button_html()
	$is_checked = false;

	// bypass rendering function if $args['html'] is present, just print that instead.
	if ( isset( $args[ 'html' ] ) ) {
		$body = $args[ 'html' ];
	} else {
		$checkbox_args['current_value'] = $is_checkbox ? get_user_input_array_value( $userdata, $name ) : get_user_input_singular_value( $userdata, $name );
		$body = get_filter_checkbox_or_radio_button_html( $checkbox_args, $is_checkbox, $is_checked );
	}

	// not expecting $args['visible'] to be set, but if it is, it overrides the value from $is_checked
	$accordion_item_args['visible'] = gp_if_set( $args, 'visible', ( $is_checked ) );

	// accordion item with title + body
	return get_sidebar_accordion_item( $title, $body, $accordion_item_args );
}

/**
 * @param array $args
 * @param bool  $is_checkbox - false for type="radio", true for type="checkbox"
 * @param bool  $is_checked - whether or not any items are checked. We may use this to open an accordion item on page load.
 *
 * @return string
 */
function get_filter_checkbox_or_radio_button_html( array $args, $is_checkbox = true, &$is_checked = false ) {

	$op = '';

	$items    = gp_if_set( $args, 'items', array() );
	$name     = gp_if_set( $args, 'name', '__no_name' );
	$icon_map = gp_if_set( $args, 'icon_map', array() );

	// Default this to false here. In this functions caller we may default it to true.
	$radio_button_can_be_unchecked = gp_if_set( $args, 'radio_button_can_be_unchecked', false );

	// note: we'll add a counter the ID to ensure they are unique.
	$base_id = $name;

	// some logic in case $name has or doesn't have '[]' or something like that.
	if ( $is_checkbox ) {
		if ( strpos( $name, '[' ) === false ) {
			$name = $name . '[]';
		} else {
			// I have no tested this part... names should not have [] in it already if possible..
			// however, even if they do its not a big deal, I just don't know if the ID will show up properly
			// or something stupid like, "brand[" or "bran"
			$base_id = $name;
			$pos     = strpos( $base_id, '[' );
			$base_id = substr( $base_id, 0, $pos );
		}
	}

	if ( $is_checkbox ) {
		$current_value = gp_if_set( $args, 'current_value', array() );
		$current_value = gp_make_array( $current_value ); // could be one or more values
	} else {
		$current_value = gp_if_set( $args, 'current_value', '' );
	}

	if ( $items && is_array( $items ) ) {
		$c = 0;
		foreach ( $items as $key => $item ) {

			$c ++;
			if ( gp_is_singular( $item ) ) {
				$text  = $item;
				$value = $key;
			} else {

				$text  = gp_if_set( $item, 'text' );
				// value as in <option value="">. This is a bit messy.. we still default to $key in case $value['value'] is not set.
				// careful not to default $value to $key is $value is set but empty.. this is valid ...
				$value = gp_if_set( $item, 'value', $key );
				$count = gp_if_set( $item, 'count' );
				// if we use these, they should technically be in $args rather than $item
				$show_count = gp_if_set( $item, 'show_count', true );
				$show_count_if_zero = gp_if_set( $item, 'show_count_if_zero', false );

				if ( $count && $show_count ) {
					$count_str = ' (' . $count . ')';
				} else if ( ! $count && $show_count_if_zero ){
					$count_str = ' ()';
				} else {
					$count_str = '';
				}

				$text .= $count_str;
			}

			$cls = array();
			$cls[] = ! $is_checkbox && $radio_button_can_be_unchecked ? 'allow-uncheck' : '';
			$text  = gp_test_input( $text );
			$value = gp_test_input( $value );

			$icon = $icon_map ? gp_if_set( $icon_map, $value, '' ) : '';

			if ( $is_checkbox ) {
				$id = $base_id . '_' . $c;
			} else {
				$id = $name . '_' . $c;
			}

			if ( $is_checkbox ) {
				$checked = in_array( $value, $current_value );
			} else {
				$checked     = $value == $current_value ? true : false;
			}

			// this value is passed by reference, and function caller may need to know about it.
			$is_checked = $checked;

			$checked_str = $checked ? 'checked' : '';

			$op .= '<div class="nice-looking-checkbox type-checkbox">';
			$op .= '<div class="cb-inner">';

			$type = $is_checkbox ? 'checkbox' : 'radio';

			$op .= '<input type="' . $type . '" class="' . gp_parse_css_classes( $cls ) . '" name="' . $name . '" value="' . $value . '" id="' . $id . '" ' . $checked_str . '>';
			$op .= '<label for="' . $id . '">';
			$op .= '<span class="label-flex">';

			if ( $icon ) {

				$op .= '<span class="label-text has-icon">';
				$op .= '<span class="label-text-icon">';
				$op .= $icon;
				$op .= '</span>';
				$op .= '<span class="label-text-text">';
				$op .= $text;
				$op .= '</span>';
				$op .= '</span>';

			} else {

				$op .= '<span class="label-text">';
				$op .= $text;
				$op .= '</span>';

			}

			if ( $is_checkbox ) {
				$op .= '<span class="label-icon"><i class="fa fa-plus"></i></span>';
			} else {
				$op .= '<span class="label-icon"><i class="fa fa-check"></i></span>';
			}

			$op .= '</span>'; // label flex
			$op .= '</label>';
			$op .= '</div>'; // cb-inner
			$op .= '</div>'; // nice-looking-checkbox
		}
	}

	return $op;
}

/**
 *
 */
function get_rims_by_size_form( $args, $userdata = array() ) {

	$diameters = get_rim_diameter_filter_options();
	asort( $diameters, SORT_NUMERIC );

	$widths = get_rim_width_filter_options();
	asort( $widths, SORT_NUMERIC );

	$bolt_patterns = get_rim_bolt_pattern_filter_options();
	asort( $bolt_patterns, SORT_NUMERIC );

	$offsets = get_rim_offset_filter_options();
	asort( $offsets, SORT_NUMERIC );

	$hub_bores = get_rim_hub_bore_filter_options();
	asort( $hub_bores, SORT_NUMERIC );

	//	$current_diameters = get_user_input_array_value( $userdata, 'diameter', true );
	//	$current_widths  = get_user_input_array_value( $userdata, 'width', true );
	//	$current_bolt_pattern = get_user_input_singular_value( $userdata, 'bolt_pattern' );

	$_diameters    = get_user_input_array_value( $userdata, 'diameter', true );
	$_widths       = get_user_input_array_value( $userdata, 'width', true );
	$_bolt_pattern = get_user_input_singular_value( $userdata, 'bolt_pattern' );
	$_offset_min   = get_user_input_singular_value( $userdata, 'offset_min' );
	$_offset_max   = get_user_input_singular_value( $userdata, 'offset_max' );
	$_hub_bore_min = get_user_input_singular_value( $userdata, 'hub_bore_min' );
	$_hub_bore_max = get_user_input_singular_value( $userdata, 'hub_bore_max' );

	$url = Router::get_url( 'rim_size' );
	$title = gp_if_set( $args, 'title', '' );

	$op = '';
	$op .= '<form class="rims-by-size form-style-1" id="rims-by-size" method="get" action="' . $url . '">';

	if ( $title ) {
		$op .= '<div class="form-header">';
		$op .= '<h2>' . $title . '</h2>';
		$op .= '</div>';
	}

	$op .= '<div class="form-items">';

	// ** Width (multi select)
	$op .= get_form_select( array(
		'name' => 'diameter[]',
		'multiple' => true,
        // 'label' => 'Diameter',
		'id' => 'rs-diameter',
		'select_2' => true,
		'select_2_args' => array(
			'placeholder' => 'Diameter',
		),
	), array(
		'placeholder' => 'None',
		'items' => $diameters,
		'current_value' => $_diameters,
	));

	// ** Width (multi select)
	$op .= get_form_select( array(
		'name' => 'width[]',
		'multiple' => true,
		'id' => 'rs-width',
		'select_2' => true,
		'select_2_args' => array(
			'placeholder' => 'Width',
		),
	), array(
		'placeholder' => 'None',
		'items' => $widths,
		'current_value' => $_widths,
	));

	// ** Bolt Pattern
	$op .= get_form_select( array(
		'name' => 'bolt_pattern',
		'id' => 'rs-bolt_pattern',
		'select_2' => true,
	), array(
		'placeholder' => 'Bolt Pattern',
		'items' => $bolt_patterns,
		'current_value' => $_bolt_pattern,
	));

	// ** Offset Min
	$op .= get_form_select( array(
		'name' => 'offset_min',
		'id' => 'rs-offset_min',
		'select_2' => true,
	), array(
		'placeholder' => 'Offset Min',
		'items' => $offsets,
		'current_value' => $_offset_min,
	));

	// ** Offset Max
	$op .= get_form_select( array(
		'name' => 'offset_max',
		'id' => 'rs-offset_max',
		'select_2' => true,
	), array(
		'placeholder' => 'Offset Max',
		'items' => $offsets,
		'current_value' => $_offset_max,
	));

	// ** Hub Bore Min
	$op .= get_form_select( array(
		'name' => 'hub_bore_min',
		'id' => 'rs-hub_bore_min',
		'select_2' => true,
	), array(
		'placeholder' => 'Hub Bore Min',
		'items' => $hub_bores,
		'current_value' => $_hub_bore_min,
	));

	// ** Hub Bore Max
	$op .= get_form_select( array(
		'name' => 'hub_bore_max',
		'id' => 'rs-hub_bore_max',
		'select_2' => true,
	), array(
		'placeholder' => 'Hub Bore Max',
		'items' => $hub_bores,
		'current_value' => $_hub_bore_max,
	));

	// Reset
	// this isnt working probably due to select 2
	// $op .= get_form_reset_button();

	// Submit
	$op .= get_form_submit( array(
		'text' => 'Search'
	));

	$op .= '</div>'; // form-items
	$op .= '</form>';

	return $op;
}

Class Pagination_Attributes{

	public $min;
	public $max;
	public $range;
	public $show_prev;
	public $show_next;
	public $in_range_of_min;
	public $in_range_of_max;
	public $min_to_show;
	public $max_to_show;

	/**
	 * Pagination_Attributes constructor.
	 *
	 * @param     $min
	 * @param     $max
	 * @param int $current
	 */
	public function __construct( $min, $max, $current = 1, $range = 2 ) {

		// hide next/previous buttons if there's nothing to show
		$show_prev = $current > $min;
		$show_next = $current < $max;

		// range of means show 7,8,9,10,11 if we're on page 9, therefore max numbers count is 5
		$max_numbers_count = ( $range * 2 ) + 1;

		// ie. we're on page 1,2, or 3, if $range = 2
		$in_range_of_min = ( $current - $range ) <= 1;

		// ie. we're on page 27,28, or 29, if $range = 2 and $max = 29
		$in_range_of_max = ( $current + $range ) >= $max;

		if ( $in_range_of_min ) {

			$min_to_show = $min;
			$max_to_show = $max_numbers_count;

		} else if ( $in_range_of_max ) {
			$max_to_show = $max;
			$min_to_show = $max - $max_numbers_count;
		} else {
			$max_to_show = $current + $range;
			$min_to_show = $current - $range;
		}

		// ensure we're not outside the limits
		$min_to_show = $min_to_show >= $min ? $min_to_show : $min;
		$max_to_show = $max_to_show <= $max ? $max_to_show : $max;

		$this->min = (int) $min;
		$this->max = (int) $max;
		$this->range = (int) $range;
		$this->show_prev = (int) $show_prev;
		$this->show_next = (int) $show_next;
		$this->in_range_of_min = (bool) $in_range_of_min;
		$this->in_range_of_max = (bool) $in_range_of_max;
		$this->min_to_show = (int) $min_to_show;
		$this->max_to_show = (int) $max_to_show;
	}
}

/**
 * @param        $url
 * @param        $page_num
 * @param string $get_var
 *
 * @return string
 */
function add_page_num_to_url( $url, $page_num, $get_var = 'page_num' ){
	$ret = cw_add_query_arg( [
		$get_var => (int) $page_num,
	], $url );
	return $ret;
}

/**
 * found online. should return the full URL without $_GET. to add $_GET, do it yourself.
 *
 * @return mixed
 */
function full_path()
{
	$s = &$_SERVER;
	$ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true:false;
	$sp = strtolower($s['SERVER_PROTOCOL']);
	$protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
	$port = $s['SERVER_PORT'];
	$port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
	$host = isset($s['HTTP_X_FORWARDED_HOST']) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
	$host = isset($host) ? $host : $s['SERVER_NAME'] . $port;
	$uri = $protocol . '://' . $host . $s['REQUEST_URI'];
	$segments = explode('?', $uri, 2);
	$url = $segments[0];
	return $url;
}

/**
 *
 */
function get_pagination_html_with_anchors( $min, $max, $current = 1, $range = 2, $url = null, $args = array() ){

	$atts = new Pagination_Attributes( $min, $max, $current, $range );

	if ( ! $url ) {
		$url = cw_add_query_arg( gp_sanitize_array_depth_1( get_array_except( $_GET, 'page_num' ) ), full_path() );
	}

	// button html
	$prev_btn_inner = '<i class="fa fa-angle-left"></i><span class="text">Prev</span>';
	$next_btn_inner = '<span class="text">Next</span><i class="fa fa-angle-right"></i>';

	$op = '';
	$op .= '<div class="pagination-type-1">';

	$prev_url = add_page_num_to_url( $url, $current - 1);
	$next_url = add_page_num_to_url( $url, $current + 1);
	$last_url = add_page_num_to_url( $url, $max);
	$first_url = add_page_num_to_url( $url, $min );

	// the "previous" button
	if ( $atts->show_prev ) {
		$op .= '<a href="' . $prev_url . '" class="page-btn page-prev" data-num="' . ( $current - 1 ) . '"><span class="btn-inner">' . $prev_btn_inner . '</span></a>';
	}

	// if $range = 2, and we're on page 4, prepend "..." to "2, 3, 4"
	if ( ! $atts->in_range_of_min ) {
		$op .= '<a href="' . $first_url . '" title="Go To Page ' . $min . '" class="page-btn page-dots page-first" data-num="' . $min . '"><span class="btn-inner">...</span></a>';
	}

	// listing page numbers
	for ( $x = $atts->min_to_show; $x <= $atts->max_to_show; $x ++ ) {

		$is_current = ( $x == $current );

		$cls = 'page-btn page-number css-reset';
		if ( $is_current ) {
			$cls .= ' is-current';
		}

		$cur_url = add_page_num_to_url( $url, $x );
		$disabled = $is_current ? ' disabled' : '';

		$op .= '<a href="' . $cur_url . '" title="Go To Page ' . $x . '" class="' . gp_parse_css_classes( $cls ) . '" data-num="' . $x . '"><span class="btn-inner">' . $x . '</span></a>';

//		$op       .= '<button type="button" class="' . $cls . '" data-num="' . $x . '"' . $disabled . '>';
//		$op       .= '<span class="btn-inner">' . $x . '</span>';
//		$op       .= '</button>';
	}

	// if $range = 2, 20 pages exist, and we're on page 10, show dots after "10, 11, 12 ..."
	if ( ! $atts->in_range_of_max ) {
		$op .= '<a href="' . $last_url . '" title="Go To Page ' . $max . '" class="page-btn page-dots page-last" data-num="' . $max . '"><span class="btn-inner">...</span></a>';
	}

	// the "next" button
	if ( $atts->show_next ) {
		$op .= '<a href="' . $next_url . '" class="page-btn page-prev" data-num="' . ( $current + 1 ) . '"><span class="btn-inner">' . $next_btn_inner . '</span></a>';
	}

	$op .= '</div>';

	return $op;

}

/**
 * @param $min     - basically always 1
 * @param $max     - the maximum number of pages in the results
 * @param $current - current page
 */
function get_pagination_html( $min, $max, $current = 1, $range = 2 ) {

	$atts = new Pagination_Attributes( $min, $max, $current, $range );

	// button html
	$prev_btn_inner = '<i class="fa fa-angle-left"></i><span class="text">Prev</span>';
	$next_btn_inner = '<span class="text">Next</span><i class="fa fa-angle-right"></i>';

	$op = '';
	$op .= '<div class="gp-pagination link-to-input" data-form="#product-filters" data-input-name="page">';

	// the "previous" button
	if ( $atts->show_prev ) {
		$op .= '<button type="button" class="page-btn page-prev css-reset" data-num="' . ( $current - 1 ) . '">';
		$op .= '<span class="btn-inner">' . $prev_btn_inner . '</span>';
		$op .= '</button>';
	}

	// if $range = 2, and we're on page 4, prepend "..." to "2, 3, 4"
	if ( ! $atts->in_range_of_min ) {
		$op .= '<button type="button" title="Go To Page 1" class="page-btn page-dots page-first css-reset" data-num="' . $min . '">';
		$op .= '<span class="btn-inner">...</span>';
		$op .= '</button>';
	}

	// listing page numbers
	for ( $x = $atts->min_to_show; $x <= $atts->max_to_show; $x ++ ) {

		$is_current = ( $x == $current );

		$cls = 'page-btn page-number css-reset';
		if ( $is_current ) {
			$cls .= ' is-current';
		}

		$disabled = $is_current ? ' disabled' : '';
		$op       .= '<button type="button" class="' . $cls . '" data-num="' . $x . '"' . $disabled . '>';
		$op       .= '<span class="btn-inner">' . $x . '</span>';
		$op       .= '</button>';
	}

	// if $range = 2, 20 pages exist, and we're on page 10, show dots after "10, 11, 12 ..."
	if ( ! $atts->in_range_of_max ) {
		$op .= '<button type="button" title="Go To Page ' . $max . '" class="page-btn page-dots page-last css-reset" data-num="' . $max . '">';
		$op .= '<span class="btn-inner">...</span>';
		$op .= '</button>';
	}

	// the "next" button
	if ( $atts->show_next ) {
		$op .= '<button type="button" class="page-btn page-next css-reset" data-num="' . ( $current + 1 ) . '">';
		$op .= '<span class="btn-inner">' . $next_btn_inner . '</span>';
		$op .= '</button>';
	}

	$op .= '</div>';

	return $op;
}

/**
 * @return array
 */
function random_filters_temp() {
	return array(
		array(
			'value' => 'opt_1',
			'text' => 'Filter Option 1',
		),
		array(
			'value' => 'opt_2',
			'text' => 'Filter Option 2',
		),
		array(
			'value' => 'opt_3',
			'text' => 'Filter Option 3',
		),
		array(
			'value' => 'opt_4',
			'text' => 'Filter Option 4',
		),
		array(
			'value' => 'opt_5',
			'text' => 'Filter Option 5',
		),
		array(
			'value' => 'opt_6',
			'text' => 'Filter Option 6',
		),
	);
}