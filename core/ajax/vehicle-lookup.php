<?php

$response = array();
$errors = array();
// $response['data'] = $_POST;
$response_text = ''; // used only sometimes

$make = get_user_input_singular_value( $_POST, 'make' );
$make = get_user_input_singular_value( $_POST, 'make' );
$model = get_user_input_singular_value( $_POST, 'model' );
$year = get_user_input_singular_value( $_POST, 'year' );
$trim = get_user_input_singular_value( $_POST, 'trim' );
$fitment_slug = get_user_input_singular_value( $_POST, 'fitment' );
$sub_slug = get_user_input_singular_value( $_POST, 'sub' );

// type is like "simple" indicating possibly no 'shop_for' input
// update: $type simple might not be in use anymore (however, don't just remove it)
$type = get_user_input_singular_value( $_POST, 'type' );

// sanitation can break a url containing &. So don't.
// js will only call window.location on this there's no xss or anything
$base_url = gp_if_set( $_POST, 'base_url' );

$url_args = get_user_input_array_value( $_POST, 'url_args', true );

$shop_for = get_user_input_singular_value( $_POST, 'shop_for' );
$shop_for = in_array( $shop_for, array( 'tires', 'wheels', 'packages' ) ) ? $shop_for : '';

//if ( ! $base_url ) {
//	if ( $shop_for )
//		$base_url = get_url( $shop_for );
//	} else {
//		// if we get to here, its unlikely things are going to work out in a good way..
//		// we either need to specify, shop for = tires/wheels/packages, OR, we can simply provide a url
//		// which we will append the vehicles make/model/year/trim/fitment to via $_GET
//	}
//}
//
//if ( $url_args ) {
//    $base_url = cw_add_query_arg( $url_args, $base_url );
//}

// echo the request type back (if its valid) into the response array (as 'response_type') so that JS knows what to do with
// the data (more specifically, with $response['options']).
$request_type = gp_if_set( $_POST, 'request_type' );
$valid_types = array( 'models', 'makes', 'years', 'trims', 'fitments', 'get_url' ); // is fitments a thing? we may need info like what seasons are available
$response_type = in_array( $request_type, $valid_types ) ? $request_type : '';
$response[ 'response_type' ] = $response_type;

// JS may use this to avoid re-sending ajax
$response[ 'cache_args' ] = array(
    'make' => $make,
    'model' => $model,
    'year' => $year,
    'trim' => $trim,
    'fitment' => $fitment_slug,
);

// the "get_url" request type happens when a fitment is selected
// (but only on forms that show the fitment, which is not all of them)

switch ( $request_type ) {
    case 'years':

        if ( $make ) {
            $response[ 'options' ] = get_years_by_make( $make );
        } else {
            // if a user un-selects a make, then we'll get to here, and we want to clear
            // the years options, so this is not redundant.
            $response['options'] = [];
        }

        break;
    case 'models':

        if ( $make && $year ) {
            $response[ 'options' ] = get_models_by_year( $make, $year );

            if ( ! $response[ 'options' ] ) {
                $response[ 'response_text' ] = wrap_tag( "No models found." );
            }

        } else {
            $response['options'] = [];
        }

        break;
    case 'trims':

        if ( $make && $model && $year ) {

            $response[ 'options' ] = get_trims( $make, $model, $year );

            if ( ! $response[ 'options' ] ) {
                $response[ 'response_text' ] = wrap_tag( "No trims found for this model." );
            }
        } else {
            $response['options'] = [];
        }

        break;
    case 'fitments':

        if ( $make && $model && $year && $trim ) {

            $response['options'] = get_fitment_names( $make, $model, $year, $trim );

            // return the fitment options
            if ( ! $response['options'] ) {
                $response_text = wrap_tag( "No fitments found for this trim." );
            }

        } else {
            $response['options'] = [];
        }

        break;
    /**
     * This actually gets both the URL and the sub size options, since this is what
     * we need to do once a user selects their fitment option. This also might run when
     * the user un-selects a previously selected substitution size, etc.
     */
    case 'get_url':

        /**
         * when $response['set_url'] is set, javascript will blindly set the URL.
         * when $response['subs'] is set, javascript will blindly set the substitution sizes.
         */

        $set_url = '';

        // unlike other requests, we require a vehicle instance to get the sub sizes.
        $vehicle = Vehicle::create_instance_from_user_input( [
            'make' => $make,
            'model' => $model,
            'year' => $year,
            'trim' => $trim,
            'fitment' => $fitment_slug,
            'sub' => $sub_slug,
        ] );

        // if make/model/year/trim/fitment, then vehicle is complete
        if ( $vehicle->is_complete() ) {

            if ( $base_url ) {
                $set_url = cw_add_query_arg( $vehicle->complete_vehicle_summary_array( true, true ), $base_url );
            } else {
                $set_url = get_vehicle_archive_url( $shop_for, $vehicle->get_slugs(), $url_args );
            }

            // when the sub slug is not passed in, it means one of two things:
            // 1. a user just selected their fitment.
            // 2. a user had a fitment AND a sub size selected, but un-selected their sub size.
            // for case #2, its redundant to return the sub sizes.
            // however, we can't distinguish between #1 and #2, so we do the same thing regardless.
            if ( ! $sub_slug ) {
                $response[ 'sub_sizes' ] = $vehicle->get_sub_size_select_options();
            }
        }

        // empty string here is not the same as not set.
        $response[ 'set_url' ] = $set_url;

        break;
    default:
        // $response['output'] = '<p>Invalid</p>';
}

if ( $response_text ) {
    $response[ 'response_text' ] = $response_text;
}

if ( $errors && is_array( $errors ) ) {
    $response[ 'success' ] = false;
    $response[ 'output' ] = gp_parse_error_string( $errors );
}

Ajax::echo_response( $response );
exit;