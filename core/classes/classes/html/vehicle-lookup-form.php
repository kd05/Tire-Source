<?php

/**
 * The heading for the form, which are the same between single product and
 * product archive pages, hence why we have a function for it.
 *
 * @param $is_tire
 * @param $has_tires_by_size_form
 * @param $has_vehicle
 * @return string
 */
function get_top_image_vehicle_lookup_heading( $is_tire, $has_tires_by_size_form, $has_vehicle ) {

    // not using this atm.
//    if ( $has_vehicle ) {
//        return '<h2>Change Vehicle</h2>';
//    }

    if ( $is_tire ) {

        if ( $has_tires_by_size_form ) {
            return '<p>Make sure your Tires will fit. <br>Search by <span class="by-vehicle active">Vehicle</span> or <span class="by-size">Size</span>.</p>';
        } else {
            return '<p>Make sure your Tires will fit. <br>Enter your Vehicle below.</p>';
        }

    } else {
        return '<p>Make sure your Wheels will fit. <br>Enter your Vehicle below.</p>';
    }
}

/**
 * Usage: get_top_image( [ 'right_col_content' => get_top_image_vehicle_lookup_form ] )
 *
 * Can have just a vehicle lookup form, or an additional tires by size form.
 *
 * @param array $args
 * @return string
 * @throws Exception
 */
function get_top_image_vehicle_lookup_form( array $args ) {

    $vehicle_lookup_args = gp_if_set( $args, 'vehicle_lookup_args', [] );
    $tires_by_size_args = gp_if_set( $args, 'tires_by_size_args', [] );

    // when true, make sure that $args['heading'] contains .by-vehicle and .by-size classes,
    // so that the user can toggle between the two forms.
    $do_tires = (bool) @$args['do_tires'];

    $vehicle_lookup_args['hide_shop_for'] = gp_if_set( $vehicle_lookup_args, 'hide_shop_for', true );

    // seems like a dumb class to add but makes some css easier (we can do :not(.in-top-image))
    $vehicle_lookup_args['add_class'] = [ 'in-top-image', @$vehicle_lookup_args['add_class'] ];

    $op = '';
    $op .= '<div class="top-image-vehicle-lookup">';

    if ( @$args['heading'] ) {
        $op .= '<div class="heading">';
        $op .= $args['heading'];
        $op .= '</div>';
    }

    if ( $do_tires ) {
        $op .= tires_by_size_form( $tires_by_size_args );
    }

    $op .= get_vehicle_lookup_form( $vehicle_lookup_args, @$vehicle_lookup_args['vehicle]'] );

    $op .= '</div>';

    return $op;
}

/**
 * @param array $args
 * @param null $vehicle
 * @return string
 * @throws Exception
 */
function get_vehicle_lookup_form( $args = array(), $vehicle = null ) {

	$page = gp_if_set( $args, 'page', 'packages' );
	$page = $page === 'rims' ? 'wheels' : $page;

	// title mainly for forms in a lightbox
	$title         = gp_if_set( $args, 'title', '' );
	$button_text   = gp_if_set( $args, 'button_text', 'Search' );
	$hidden_inputs = gp_if_set( $args, 'hidden_inputs', array() );

    // lets us modify the final URL a person is sent to with vehicle data added
	$base_url = gp_if_set( $args, 'base_url' );
	$url_args = gp_if_set( $args, 'url_args' );

	if ( $base_url ) {
		$hidden_inputs[ 'base_url' ] = $base_url;
	}

	if ( $url_args ) {
		$hidden_inputs[ 'url_args' ] = $url_args;
	}

	$hide_shop_for = gp_if_set( $args, 'hide_shop_for', false );

	if ( $vehicle instanceof Vehicle && $vehicle->trim_exists() ) {

		// show a form with fields pre-filled

		$make  = $vehicle->make;
		$model = $vehicle->model;
		$year  = $vehicle->year;
		$trim  = $vehicle->trim;

		$all_makes  = get_makes();
		$all_years  = get_years_by_make( $make );
		$all_models = get_models_by_year( $make, $year );
		$all_trims  = get_trims( $make, $model, $year );

		// complete means we have a fitment
		if ( $vehicle->is_complete() ) {
			$fitment      = $vehicle->fitment_slug;
			$all_fitments = get_fitment_names_from_fitment_data( get_fitment_data( $make, $model, $year, $trim ) );
			$all_subs     = $vehicle->get_sub_size_select_options();
			$current_sub  = $vehicle->has_substitution_wheel_set() ? $vehicle->fitment_object->wheel_set->get_selected()->get_slug() : '';

		} else {
			$fitment      = '';
			$current_sub  = '';
			$all_fitments = array();
			$all_subs     = array();
		}

	} else {

		// show the empty form....

		$make        = '';
		$model       = '';
		$year        = '';
		$trim        = '';
		$fitment     = '';
		$current_sub = '';

		// first field always has options
		$all_makes    = get_makes();
		$all_years    = array();
		$all_models   = array();
		$all_trims    = array();
		$all_fitments = array();
		$all_subs     = array();
	}

	$fields = gp_if_set( $args, 'fields', [
        'shop_for',
        'make',
        'year',
        'model',
        'trim',
        'fitment',
        'sub',
    ]);

	$cls   = [ 'vehicle-lookup', 'form-style-1' ];
	$cls[] = gp_if_set( $args, 'add_class', '' );
	$cls[] = $hide_shop_for ? 'hide-shop-for' : '';

	// Begin Html
	$op = '';
	$op .= '<form id="vehicle-lookup" class="' . gp_parse_css_classes( $cls ) . '" method="post" action="' . AJAX_URL . '">';

	// nonce field and ajax action
	$op .= get_ajax_hidden_inputs( 'vehicle_lookup' );

	// These are extra arguments passed to our ajax file.
	// Currently, 'base_url' and 'url_args' may come from our single product pages
	if ( $hidden_inputs && is_array( $hidden_inputs ) ) {
		$op .= get_hidden_inputs_from_array( $hidden_inputs );
	}

	$tagline = gp_if_set( $args, 'tagline' );

	// Form Title
	if ( $title || $tagline ) {
		$op .= get_form_header( $title, array(
			'tagline' => $tagline,
		) );
	}

	// html
	$op .= gp_if_set( $args, 'after_title' );

	// Begin items
	$op .= '<div class="form-items">';

	// Shop For
	if ( $hide_shop_for ) {
		$op .= '<input type="hidden" name="shop_for" value="' . $page . '">';
	} else if ( in_array( 'shop_for', $fields ) ) {

		$op .= '<div class="item-wrap type-select item-shop_for">';
		$op .= '<div class="item-inner select-2-wrapper">';

		$op .= '<select name="shop_for" id="vl-shop_for">';

		$opt = array(
			'tires' => 'Tires',
			'wheels' => 'Wheels',
			'packages' => 'Packages',
		);

		$op .= get_select_options( array(
			'items' => $opt,
			'current_value' => $page,
		) );

		$op .= '</select>';
		$op .= '</div>';  // item-inner
		$op .= '</div>';  // item-wrap
	}

	// Make
	if ( in_array( 'make', $fields ) ) {
		$op .= '<div class="item-wrap type-select item-makes">';
		$op .= '<div class="item-inner select-2-wrapper">';

		$op .= '<select name="make" id="vl-makes">';

		$op .= get_select_options( array(
			'placeholder' => 'Make',
			'items' => $all_makes,
			'current_value' => $make,
		) );

		$op .= '</select>';
		$op .= '</div>';  // item-inner
		$op .= '</div>';  // item-wrap
	}

	// Year
	if ( in_array( 'year', $fields ) ) {
		$op .= '<div class="item-wrap type-select item-years">';
		$op .= '<div class="item-inner select-2-wrapper">';

		$op .= '<select name="year" id="vl-years">';

		$op .= get_select_options( array(
			'placeholder' => 'Year',
			'items' => $all_years,
			'current_value' => $year,
		) );

		$op .= '</select>';
		$op .= '</div>';  // item-inner
		$op .= '</div>';  // item-wrap
	}

	// Model
	if ( in_array( 'model', $fields ) ) {
		$op .= '<div class="item-wrap type-select item-models">';
		$op .= '<div class="item-inner select-2-wrapper">';

		$op .= '<select name="model" id="vl-models">';

		$op .= get_select_options( array(
			'placeholder' => 'Model',
			'items' => $all_models,
			'current_value' => $model,
		) );

		$op .= '</select>';
		$op .= '</div>';  // item-inner
		$op .= '</div>';  // item-wrap
	}

	// Trim
	if ( in_array( 'trim', $fields ) ) {
		$op .= '<div class="item-wrap type-select item-trims">';
		$op .= '<div class="item-inner select-2-wrapper">';

		$op .= '<select name="trim" id="vl-trims">';

		$op .= get_select_options( array(
			'placeholder' => 'Trim',
			'items' => $all_trims,
			'current_value' => $trim,
		) );

		$op .= '</select>';
		$op .= '</div>';  // item-inner
		$op .= '</div>';  // item-wrap
	}

	// Fitment
	if ( in_array( 'fitment', $fields ) ) {
		$op .= '<div class="item-wrap type-select item-fitments">';
		$op .= '<div class="item-inner select-2-wrapper">';

		$fitment_lightbox_id = 'fitment-tooltip';
		$op .= get_form_tooltip( '<a class="lb-trigger" data-for="' . $fitment_lightbox_id . '" href="">What\'s this?</a>', 'Required field' );

		// add html to footer
		queue_lightbox_html( $fitment_lightbox_id, get_general_lightbox_content( $fitment_lightbox_id, get_fitment_tooltip_html(), array(
			'add_class' => 'general-lightbox width-lg-1',
			'wrap_general_content' => true,
		)));

		$op .= '<select name="fitment" id="vl-fitments">';

		// note: placeholder string is repeated in javascript code
		$op .= get_select_options( array(
			'placeholder' => 'OEM Fitments',
			'items' => $all_fitments,
			'current_value' => $fitment,
		) );

		$op .= '</select>';
		$op .= '</div>';  // item-inner
		$op .= '</div>';  // item-wrap
	}

	// Sub Size
	if ( in_array( 'sub', $fields ) ) {

		$op .= '<div class="item-wrap type-select item-sub">';
		$op .= '<div class="item-inner select-2-wrapper">';

		$sub_size_lightbox_id = 'sub-size-tooltip';
		$op .= get_form_tooltip( '<a class="lb-trigger" data-for="' . $sub_size_lightbox_id . '" href="">What\'s this?</a>', 'Optional field' );

		// add html to footer
		queue_lightbox_html( $sub_size_lightbox_id, get_general_lightbox_content( $sub_size_lightbox_id, get_sub_size_tooltip_html(), array(
			'add_class' => 'general-lightbox width-lg-1',
			'wrap_general_content' => true,
		)));

		$op .= '<select name="sub" id="vl-subs">';

		// note: placeholder string is repeated in javascript code
		$op .= get_select_options( array(
			'placeholder' => 'Aftermarket Fitments',
			'items' => $all_subs,
			'current_value' => $current_sub,
		) );

		$op .= '</select>';
		$op .= '</div>';  // item-inner
		$op .= '</div>';  // item-wrap

	}

	// Submit
	$op .= '<div class="item-wrap item-submit">';
	$op .= '<div class="button-1">';
	$op .= '<a class="vl-submit disabled" href="">' . $button_text . '</a>';
	$op .= '</div>';
	$op .= '</div>'; // item-submit

	$op .= '</div>'; // form items
	$op .= '</form>';

	return $op;
}