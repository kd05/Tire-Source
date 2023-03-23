<?php

Class Reviews_Summary {

	public $rating;

	public $count;

	public $sum_stars;

	public $stars_1;
	public $stars_2;
	public $stars_3;
	public $stars_4;
	public $stars_5;

	/**
	 * Reviews_Summary constructor.
	 *
	 * @param $rows
	 */
	public function __construct( $rows ) {

		if ( $rows && is_array( $rows ) ) {
			foreach ( $rows as $row ) {

				$this->count ++;

				$rating          = (int) gp_if_set( $row, 'rating' );
				$this->sum_stars += $rating;

				switch ( $rating ) {
					case 1:
						$this->stars_1 ++;
						break;
					case 2:
						$this->stars_2 ++;
						break;
					case 3:
						$this->stars_3 ++;
						break;
					case 4:
						$this->stars_4 ++;
						break;
					case 5:
						$this->stars_5 ++;
						break;
				}

			}
		}

		if ( $this->count ) {
			$this->rating = round( $this->sum_stars / $this->count, 1 );
		} else {
			$this->rating = 0;
		}
	}
}

/**
 * Rows should be database results
 *
 * @param $rows
 */
function render_product_reviews_list( $rows, $limit = 4 ) {

	// possibly convert stdClass to array
	$rows = gp_force_array( $rows );

	if ( ! $rows ) {
		return '';
	}

	$op = '';
	$op .= '<div class="product-reviews-list">';
	$op .= '<div class="pr-flex">';

	$found_hidden = false;
	$cc = 0;

	foreach ( $rows as $row ) {

		$cc ++;

		$nickname = gp_if_set( $row, 'nickname' );
		$nickname = $nickname ? $nickname : 'Anonymous';
		$nickname = gp_test_input( $nickname );

		$message = gp_if_set( $row, 'message' );
		$message = gp_test_input( $message );

		$rating = gp_if_set( $row, 'rating' );
		$rating = (int) $rating;

		$cls = [ 'pr-item' ];

		if ( $cc > $limit ) {
			$found_hidden = true;
			$cls[] = 'hidden';
		}

		$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';
		$op .= '<div class="pr-item-2">';

		$op .= '<div class="item-top">';

		$op .= '<p class="nickname">' . $nickname . '</p>';

		$op .= '<div class="rating">';
		$op .= '<div class="rating-stars">';
		$op .= reviews_get_stars( $rating );
		$op .= '</div>';
		$op .= '<p>' . $rating . '/5</p>';
		$op .= '</div>';

		$op .= '</div>';

		$message = gp_sanitize_textarea( $message );

		$op .= '<div class="item-main">';
		$op .= nl2br( $message );
		$op .= '</div>';

		$op .= '</div>'; // pr-item-2
		$op .= '</div>'; // pr-item

	}

	$op .= '</div>'; // pr-flex

	$how_many = count( $rows ) - $limit;
	$text = '[Show ' . $how_many . ' More]';

	if ( $found_hidden ) {
		$op .= '<div class="pr-more">';
		$op .= '<button class="css-reset pr-more-trigger">' . $text . '</button>';
		$op .= '</div>';
	}

	$op .= '</div>'; // product-reviews-list

	return $op;
}

/**
 * @param $s1
 * @param $s2
 * @param $s3
 * @param $s4
 * @param $s5
 */
function render_ratings_histogram( $s1, $s2, $s3, $s4, $s5, $html_after = '' ) {

	$total = $s1 + $s2 + $s3 + $s4 + $s5;
	$total = (int) $total;

	$arr = [
		5 => $s5,
		4 => $s4,
		3 => $s3,
		2 => $s2,
		1 => $s1,
	];

	$op = '';
	$op .= '<div class="ratings-histogram">';

	foreach ( $arr as $k => $v ) {
		$v  = (int) $v;
		$op .= '<div class="rh-row">';
		$op .= '';
		$op .= '<div class="rh-left">';
		$op .= '<span>' . $k . ' Star</span>';
		$op .= '</div>';

		$rh_bar = ['rh-bar'];
		$rh_bar[] = $v == 0 ? 'count-0' : '';

		$op .= '<div class="rh-mid">';
		$op .= '<div class="' . gp_parse_css_classes( $rh_bar ) . '" data-count="' . $v . '" data-of="' . $total . '">';

		if ( $total ) {
			$width = number_format( $v / $total, 2 ) * 100;
		} else {
			$width = 0;
		}

		$op .= '<div class="rh-bar-red" style="width: ' . $width . '%"></div>';
		$op .= '</div>';
		$op .= '</div>';

		$op .= '<div class="rh-right">';
		$op .= '<span>' . $v . '</span>';
		$op .= '</div>';
		$op .= '';
		$op .= '</div>';
	}

	$op .= '';
	$op .= '';
	$op .= '';

	$op .= $html_after; // leave a review link maybe

	$op .= '</div>';

	return $op;
}

/**
 * Ie. "star, star, star, star, star, 4.0 (25) Custom Reviews"
 *
 * @param $rating
 * @param $count
 */
function render_ratings_aggregate( $rating, $count, $args = array() ) {

	$after = gp_if_set( $args, 'after', 'Customer Reviews' );

	$count = $count ? $count : '0';
	$count_text = $count . ' '; // leave space
	$count_text .= $after;
	$count_text = trim( $count_text );

	$op = '';
	$op .= '<div class="ratings-aggregate">';
	$op .= '<div class="stars">' . reviews_get_stars( $rating ) . '</div>';
	$op .= '<div class="rating">(' . number_format( $rating, 1 ) . ')</div>';
	$op .= '<div class="count">' . $count_text . '</div>';
	$op .= '</div>';

	return $op;
}

/**
 * A horizontal histogram showing 1 bar for each star.
 *
 * @param $avg_rating
 */
function reviews_get_stars( $v ) {

	if ( $v < 0 || $v > 5 ) {
		return '';
	}

	// multiply by 2, then round to int, then divide to 2
	// so that in effect we round to nearest 0.5
	$v2 = $v * 2;
	$v2 = round( $v2, 0 );
	$v1 = $v2 / 2;

	$full_stars = floor( $v1 );
	$remainder  = $v1 - $full_stars;
	// remainder should be 0 or 0.5 but php is weird with rounding so lets leave a bit of room for error
	$half_stars  = $remainder > 0.1 ? 1 : 0;
	$empty_stars = 5 - $half_stars - $full_stars;

	$full_star  = '<i class="fas fa-star"></i>';
	$half_star  = '<i class="fas fa-star-half-alt"></i>';
	$empty_star = '<i class="far fa-star"></i>';

	$op = '';

	for ( $x = 1; $x <= $full_stars; $x ++ ) {
		$op .= $full_star;
	}

	for ( $x = 1; $x <= $half_stars; $x ++ ) {
		$op .= $half_star;
	}

	for ( $x = 1; $x <= $empty_stars; $x ++ ) {
		$op .= $empty_star;
	}

	return $op;
}

/**
 * Usage: single products page..
 *
 * @param       $reviews
 * @param array $args
 *
 * @return string
 */
function render_reviews_from_db_results( $reviews, $type, $args = array(), $url = '' ) {

	$type = $type === 'tire' ? 'tire' : 'rim';

	$cls   = [ 'product-reviews' ];
	$cls[] = 'type-' . $type;
	$cls[] = empty( $reviews ) ? 'is-empty' : '';

	$summary = new Reviews_Summary( $reviews );

	$op = '';
	$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';

	$op .= '<h2 class="like-h2">Customer Reviews</h2>';

	// stars
	$op .= render_ratings_aggregate( $summary->rating, $summary->count );

	// histogram

	$after = $url ? '<p class="leave-a-review"><a href="' . $url . '">Leave a Review</a></p>' : '';
	$op .= render_ratings_histogram( $summary->stars_1, $summary->stars_2, $summary->stars_3, $summary->stars_4, $summary->stars_5, $after );

	// list
	$op .= render_product_reviews_list( $reviews );

	$op .= '</div>';

	return $op;
}

/**
 * @param $brand
 * @param $model
 * @param $color_1
 * @param $color_2
 * @param $finish
 */
function get_rim_reviews_by_product_attributes( $brand, $model, $color_1, $color_2, $finish, $approved = true  ) {

	$brand   = gp_force_singular( $brand );
	$model   = gp_force_singular( $model );
	$color_1 = gp_force_singular( $color_1 );
	$color_2 = gp_force_singular( $color_2 );
	$finish  = gp_force_singular( $finish );

	$db = get_database_instance();
	$p  = [];
	$q  = '';
	$q  .= 'SELECT * ';
	$q  .= 'FROM ' . DB_reviews . ' ';

	$q .= 'WHERE 1 = 1 ';

	$q   .= 'AND review_product_type = :review_product_type ';
	$p[] = [ ':review_product_type', 'rim' ];

	$q   .= 'AND review_brand = :review_brand ';
	$p[] = [ ':review_brand', $brand ];

	$q   .= 'AND review_model = :review_model ';
	$p[] = [ ':review_model', $model ];

	if ( $color_1 || $color_2 || $finish ) {

        if ( $color_1 ) {
            $q   .= 'AND review_color_1 = :color_1 ';
            $p[] = [ ':color_1', $color_1 ];
        } else {
            $q .= 'AND ( review_color_1 IS NULL OR review_color_1 = "" ) ';
        }

        if ( $color_2 ) {
            $q   .= 'AND review_color_2 = :color_2 ';
            $p[] = [ ':color_2', $color_2 ];
        } else {
            $q .= 'AND ( review_color_2 IS NULL OR review_color_2 = "" ) ';
        }

        if ( $finish ) {
            $q   .= 'AND review_finish = :finish ';
            $p[] = [ ':finish', $finish ];
        } else {
            $q .= 'AND ( review_finish IS NULL OR review_finish = "" ) ';
        }
    }

	if ( $approved !== null ) {
		if ( $approved ) {
			$q   .= 'AND approved = 1 ';
		} else {
			$q   .= 'AND approved = 0 ';
		}
	}

	// debatable whether this is useful
	$q .= 'GROUP BY user_id ';

	// order by date inserted, since we're probably using this function on the front-end
	$q .= 'ORDER BY date_inserted DESC, review_id DESC ';
	$q .= ';';

	$results = $db->get_results( $q, $p );

	return $results;
}

/**
 * @param $brand
 * @param $model
 */
function get_rim_leave_review_url( $brand_slug, $model_slug, $c1, $c2 = '', $ff = '' ) {

	$brand_slug = gp_test_input( $brand_slug );
	$model_slug = gp_test_input( $model_slug );


	$args = [];
	$args['brand'] = $brand_slug;
	$args['model'] = $model_slug;
	$c1 = gp_test_input( $c1 );

	if ( $c1 ) {
		$args['color_1'] = $c1;
	}

	$c2 = gp_test_input( $c2 );

	if ( $c2 ) {
		$args['color_2'] = $c2;
	}

	$ff = gp_test_input( $ff );

	if ( $ff ) {
		$args['finish'] = $ff;
	}

	$args['is_rim'] = 1;

	return cw_add_query_arg( $args, get_url( 'reviews' ) );
}


/**
 * @param $brand
 * @param $model
 */
function get_tire_leave_review_url( $brand_slug, $model_slug ) {

	$brand_slug = gp_test_input( $brand_slug );
	$model_slug = gp_test_input( $model_slug );


	$args = [];
	$args['brand'] = $brand_slug;
	$args['model'] = $model_slug;

	return cw_add_query_arg( $args, get_url( 'reviews' ) );
}

/**
 * @param $brand
 * @param $model
 */

function get_tire_reviews_by_product_attributes( $brand, $model, $approved = null ) {

	$brand = gp_force_singular( $brand );
	$model = gp_force_singular( $model );

	$db = get_database_instance();
	$p  = [];
	$q  = '';
	$q  .= 'SELECT * ';
	$q  .= 'FROM ' . DB_reviews . ' ';

	$q .= 'WHERE 1 = 1 ';

	$q   .= 'AND review_product_type = :review_product_type ';
	$p[] = [ ':review_product_type', 'tire' ];

	$q   .= 'AND review_brand = :review_brand ';
	$p[] = [ ':review_brand', $brand ];

	$q   .= 'AND review_model = :review_model ';
	$p[] = [ ':review_model', $model ];

	if ( $approved !== null ) {
		if ( $approved ) {
			$q   .= 'AND approved = 1 ';
		} else {
			$q   .= 'AND approved = 0 ';
		}
	}

	// should we group by user ID? supposed to be 1 review per user, so.. does not matter i suppose.
	$q .= 'GROUP BY user_id ';

	// order by date inserted, since we're probably using this function on the front-end
	$q .= 'ORDER BY date_inserted DESC, review_id DESC ';
	$q .= ';';

	$results = $db->get_results( $q, $p );

	return $results;
}


/**
 * @param $product
 */
function get_reviews_by_product( $product, $approved = true ) {

	if ( $product instanceof DB_Tire ) {

		$brand = $product->get( 'brand_slug', '', true );
		$model = $product->get( 'model_slug', '', true );

		return get_tire_reviews_by_product_attributes( $brand, $model, $approved );

	} else if ( $product instanceof DB_Rim ) {

		$brand   = $product->get( 'brand_slug', '', true );
		$model   = $product->get( 'model_slug', '', true );
		$color_1 = $product->get( 'color_1', '', true );
		$color_2 = $product->get( 'color_2', '', true );
		$finish  = $product->get( 'finish ', '', true );

		return get_rim_reviews_by_product_attributes( $brand, $model, $color_1, $color_2, $finish, $approved );
	}

	return false;
}

/**
 * @param     $str
 * @param int $count
 */
function get_string_end( $str, $count = 1 ) {
	$str = trim( $str );
	$ln = strlen( $str );
	$str = substr( $str, $ln - $count, $ln );
	return $str;
}

/**
 *
 */
function get_credit_card_last_4( $str ){
	return get_string_end( $str, 4 );
}

/**
 * @param $v
 *
 * @return bool
 * @throws User_Exception
 */
function review_product_validate_rating( &$v ) {

	$v = (int) $v;

	if ( $v > 0 && $v < 6 ) {
		return true;
	}

	throw new User_Exception( 'Please select a valid rating.' );
}

/**
 * @param $v
 *
 * @return bool
 * @throws User_Exception
 */
function review_product_validate_nickname( &$v ) {

	// strip tags first, otherwise gp_test_input could increase the character count quite a bit
	$v = strip_tags( $v );

	$v = gp_test_input( $v );

	if ( strlen( $v ) > 20 ) {
		throw new User_Exception( 'Please use 20 character or less for your nickname.' );
	}

	return true;
}

/**
 * @param $v
 *
 * @return bool
 * @throws User_Exception
 */
function review_product_validate_message( $v ) {

	$v = gp_sanitize_textarea( $v );

	if ( strlen( $v ) > 10000 ) {
		throw new User_Exception( 'Please use less than 10000 characters.' );
	}

	return true;
}


/**
 * @param $nickname
 * @param $rating
 * @param $message
 * @param $brand
 * @param $model
 */
function review_product_tire_insert_from_user_input( $nickname, $rating, $message, $brand, $model ) {

	$args = [];
	// note: array indexes are generally not the database column names
	$args[ 'product_type' ] = 'tire';
	$args[ 'nickname' ]     = $nickname;
	$args[ 'rating' ]       = $rating;
	$args[ 'message' ]      = $message;
	$args[ 'brand' ]        = $brand;
	$args[ 'model' ]        = $model;

	return review_product_insert_from_user_input( $args );
}

/**
 * @param $nickname
 * @param $rating
 * @param $message
 * @param $brand
 * @param $model
 * @param $color_1
 * @param $color_2
 * @param $finish
 */
function review_product_rim_insert_from_user_input( $nickname, $rating, $message, $brand, $model, $color_1, $color_2, $finish ) {

	$args = [];
	// note: array indexes are generally not the database column names
	$args[ 'product_type' ] = 'rim';
	$args[ 'nickname' ]     = $nickname;
	$args[ 'rating' ]       = $rating;
	$args[ 'message' ]      = $message;
	$args[ 'brand' ]        = $brand;
	$args[ 'model' ]        = $model;
	$args[ 'color_1' ]      = $color_1;
	$args[ 'color_2' ]      = $color_2;
	$args[ 'finish' ]       = $finish;

	return review_product_insert_from_user_input( $args );
}

/**
 * Note: don't use this function directly, use review_product_rim_insert_from_user_input() or
 * review_product_tire_insert_from_user_input()
 *
 * @param $data
 */
function review_product_insert_from_user_input( $data ) {

	// we'll assume all data could be user input even though some of it
	// definitely isn't, and the stuff that is may have been sanitized once already.

	$user    = cw_get_logged_in_user();
	$user_id = $user ? (int) $user->get( 'user_id' ) : false;

	if ( ! $user_id || ! $user ) {
		throw new Exception( 'Invalid user' );
	}

	$brand        = get_user_input_singular_value( $data, 'brand' );
	$model        = get_user_input_singular_value( $data, 'model' );
	$color_1      = get_user_input_singular_value( $data, 'color_1' );
	$color_2      = get_user_input_singular_value( $data, 'color_2' );
	$finish       = get_user_input_singular_value( $data, 'finish' );
	$nickname     = get_user_input_singular_value( $data, 'nickname', '', true );
	$rating       = get_user_input_singular_value( $data, 'rating' );
	$message      = get_array_value_force_singular( $data, 'message' );
	$message      = gp_sanitize_textarea( $message );
	$product_type = get_user_input_singular_value( $data, 'product_type' );

	// should throw its own User_Exception
	if ( ! review_product_validate_rating( $rating ) ) {
		// therefore this shouldn't trigger
		return false;
	}

	// should throw its own User_Exception
	if ( ! review_product_validate_nickname( $nickname ) ) {
		return false;
	}

	// should throw its own User_Exception
	if ( ! review_product_validate_message( $message ) ) {
		return false;
	}

	// ensure products exist, and also that a user doesn't already have a review for this product.
	// ensuring existing review does not exist should also be done beforehand, and update function should be used instead.
	if ( $product_type === 'tire' ) {
		$product = DB_Tire::get_single_product_from_brand_model( $brand, $model );

		if ( ! $product ) {
			// Exception not user exception
			throw new Exception( 'Tire is Invalid.' );
		}

		$ex = DB_Review::get_tire_review_via_user_id( $user_id, $brand, $model );

		if ( $ex ) {
			// Exception not user exception
			throw new Exception( 'Tire review already exists.' );
		}

	} else if ( $product_type === 'rim' ) {

		$product = DB_Rim::get_partial_product( $brand, $model, $color_1, $color_2, $finish );

		if ( ! $product ) {
			// Exception not user exception
			throw new Exception( 'Rim is invalid.' );
		}

		$ex = DB_Review::get_rim_review_via_user_id( $user_id, $brand, $model, $color_1, $color_2, $finish );

		if ( $ex ) {
			// Exception not user exception
			throw new Exception( 'Rim review already exists.' );
		}

	} else {
		// Exception not user exception
		throw new Exception( 'Invalid product type' );
	}

	$db = get_database_instance();

	$format            = 'Y-m-d H:i:s';
	$date_inserted     = date( $format );
	$date_updated      = $date_inserted;
	$last_edit_user_id = $user_id;

	// data to insert. array keys must be valid database columns..
	$insert = array(
		'review_product_type' => $product_type,
		'review_brand' => $brand,
		'review_model' => $model,
		'review_color_1' => $color_1,
		'review_color_2' => $color_2,
		'review_finish' => $finish,
		'rating' => $rating,
		'nickname' => $nickname,
		'user_id' => $user_id,
		'message' => $message,
		'date_inserted' => $date_inserted,
		'date_updated' => $date_updated,
		'last_edit_user_id' => $last_edit_user_id,
	);

	// %s for string, %s for int
	$insert_format = array(
		'review_product_type' => '%s',
		'review_brand' => '%s',
		'review_model' => '%s',
		'review_color_1' => '%s',
		'review_color_2' => '%s',
		'review_finish' => '%s',
		'rating' => '%d',
		'nickname' => '%s',
		'user_id' => '%d',
		'message' => '%s',
		'date_inserted' => '%s',
		'date_updated' => '%s',
		'last_edit_user_id' => '%d',
	);

	$review_id = $db->insert( $db->reviews, $insert, $insert_format );

	if ( $review_id ) {
		return $review_id;
	}

	throw new Exception( 'Could not insert' );
}

/**
 * @param $review_id
 * @param $data
 */
function review_product_update_from_user_input( $review_id, $data ) {

	$general_error = 'Could not update review.';

	$user    = cw_get_logged_in_user();
	$user_id = (int) $user->get_primary_key_value();

	// user logged in ?
	if ( ! $user ) {
		listen_add_ajax_debug( 'review_update', 'no user.' );
		throw new User_Exception( $general_error );
	}

	$review = DB_Review::create_instance_via_primary_key( $review_id );

	// review exists ?
	if ( ! $review ) {
		listen_add_ajax_debug( 'review_update', 'no review.' );
		throw new User_Exception( $general_error );
	}

	// user not admin, is it their own review?
	if ( ! $user->is_administrator() && (int) $review->get( 'user_id' ) !== (int) $user_id ) {
		listen_add_ajax_debug( 'review_update', 'user does not have permission.' );
		throw new User_Exception( $general_error );
	}

	$nickname = get_user_input_singular_value( $data, 'nickname' );
	$rating   = get_user_input_singular_value( $data, 'rating' );
	$message  = get_array_value_force_singular( $data, 'message' );
	$message  = gp_sanitize_textarea( $message );

	// should throw its own User_Exception
	if ( ! review_product_validate_rating( $rating ) ) {
		// therefore this shouldn't trigger
		return false;
	}

	// should throw its own User_Exception
	if ( ! review_product_validate_nickname( $nickname ) ) {
		return false;
	}

	// should throw its own User_Exception
	if ( ! review_product_validate_message( $message ) ) {
		return false;
	}

	$db = get_database_instance();

	// Sql Update
	$updated = $db->update( $db->reviews, [
		'nickname' => $nickname,
		'rating' => $rating,
		'message' => $message,
		'last_edit_user_id' => $user_id,
		'date_updated' => get_date_formatted_for_database(),
	], [
		'review_id' => $review_id,
	], [
		'nickname' => '%s',
		'rating' => '%d',
		'message' => '%s',
		'last_edit_user_id' => '%d',
		'date_updated' => '%s',
	], [
		'review_id' => '%d',
	] );

	if ( $updated ) {
		return true;
	}

	throw new User_Exception( 'An error occurred while tyring to update your review.' );
}

/**
 * Maybe not used.
 *
 * @param $type
 * @param $brand
 * @param $model
 * @param $c1
 * @param $c2
 * @param $finish
 *
 * @return string
 */
function get_leave_review_url( $type, $brand, $model, $c1 = '', $c2 = '',  $finish = '') {

	$args = array();
	if ( $type === 'tire' ) {
		$args['brand'] = gp_test_input( $brand );
		$args['model'] = gp_test_input( $model );
	} else if ( $type === 'rim' ) {
		$args['is_rim'] = true;
		$args['brand'] = gp_test_input( $brand );
		$args['model'] = gp_test_input( $model );

		if ( $c1 ) {
			$args['color_1'] = gp_test_input( $c1);
		}

		if ( $c2 ) {
			$args['color_2'] = gp_test_input( $c1);
		}

		if ( $finish ) {
			$args['finish'] = gp_test_input( $finish);
		}
	}

	$ret = cw_add_query_arg( $args, get_url( 'reviews' ) );
	return $ret;
}