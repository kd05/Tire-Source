<?php

$ret = [];
$ret['success'] = false;

$generic_error = 'An unexpected error has occurred.';
$generic_insert_success = 'Your review has been submitted for approval. You can still edit it while it is not approved.';

// could be admin, who's editing an existing review from another user
$user = cw_get_logged_in_user();
$user_id = $user ? $user->get_primary_key_value() : false;

$helper = new Product_Review_Helper( $_POST );

listen_add_ajax_debug( $helper );
listen_add_ajax_debug( 'here.....');

// so we can 'break'
foreach ( array(1) as $nothing ) {

	if ( ! $user ) {
		$ret['response_text'] = 'You must be logged in to edit or post reviews.';
		break;
	}

	// Review Exists
	if ( $helper->review ) {

		listen_add_ajax_debug( 'review exists...');

		// in the "new way", users can't edit their past reviews
		if ( ! $user->is_administrator() ) {

			if ( $helper->review->get( 'approved' ) ){
				$ret['response_text'] = 'Permission denied. You cannot edit reviews once they are approved.';
				break;
			}
		}

		listen_add_ajax_debug( 'user is not not an admin');

		// users can only edit their own, except if they are admins
//		if ( ! $user->is_administrator() && (int) $helper->review->get( 'user_id' ) !== (int) $user_id ) {
//			listen_add_ajax_debug( 'here 1' );
//			$ret['response_text'] = $generic_error;
//			break;
//		}

		// Try to update
		try{

			// this sanitizes and validates (and throws user_exceptions/exceptions).
			// it will find rating, message, nickname from $_POST
			$updated = review_product_update_from_user_input( $helper->review->get_primary_key_value(), $_POST );

			listen_add_ajax_debug( 'updated', $updated );

			if ( $updated ) {
				$ret['success'] = true;

				if ( cw_is_admin_logged_in() ) {
					$ret['response_text'] = 'This review has been updated.';
				} else {
					$ret['response_text'] = 'Your review has been updated and is awaiting approval.';
				}

			} else {
				listen_add_ajax_debug( 'here a' );
				$ret['response_text'] = $generic_error;
			}

		} catch ( User_Exception $e ) {
			$ret['response_text'] = $e->getMessage();
		} catch ( Exception $e ) {
			listen_add_ajax_debug( 'update exception', $e->getMessage() );
			$ret['response_text'] = $generic_error;
		}

	} else {

		listen_add_ajax_debug( 'review does not exist...');

		// if we don't have a product or an existing review, we can't do anything...
		if ( ! $helper->product ) {
			listen_add_ajax_debug( 'here 2' );
			$ret['response_text'] = $generic_error;
			break;
		}

		listen_add_ajax_debug( 'product exists...');

		// our insert functions will sanitize and validate all user input
		$brand = $helper->product->get( 'brand_slug' );
		$model = $helper->product->get( 'model_slug' );
		$c1 = $helper->product->is_rim() ? $helper->product->finish->get( 'color_1' ) : '';
		$c2 = $helper->product->is_rim() ? $helper->product->finish->get( 'color_2' ) : '';
		$ff = $helper->product->is_rim() ? $helper->product->finish->get( 'finish' ) : '';
		$nickname = gp_if_set( $_POST, 'nickname' );
		$message = gp_if_set( $_POST, 'message' );
		$rating = gp_if_set( $_POST, 'rating' );

		// Try to insert Tire or Rim review
		try{

			$inserted = false;
			if ( $helper->is_tire && $helper->product->is_tire() ) {
				$inserted = review_product_tire_insert_from_user_input( $nickname, $rating, $message, $brand, $model );
				listen_add_ajax_debug( 'tire_insert', $inserted );
			} else if ( $helper->is_rim && $helper->product->is_rim() ){
				$inserted = review_product_rim_insert_from_user_input( $nickname, $rating, $message, $brand, $model, $c1, $c2, $ff );
				listen_add_ajax_debug( 'rim_insert', $inserted );
			}

			if ( $inserted ) {
				$ret['success'] = true;
				$ret['response_text'] = $generic_insert_success;
			} else {
				$ret['response_text'] = $generic_error;
				listen_add_ajax_debug( 'here C' );
			}

		} catch ( User_Exception $e ) {
			$ret['response_text'] = $e->getMessage();
		} catch ( Exception $e ) {
			listen_add_ajax_debug( 'insert general exception', $e->getMessage() );
			$ret['response_text'] = $generic_error;
			listen_add_ajax_debug( 'here D' );
		}
	}
}

Ajax::echo_response( $ret );
exit;