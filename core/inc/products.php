<?php

/**
 * generate a tire name from primitive values. most of the time
 * we don't need this because either suppliers or the vehicle api
 * provides this for us.
 */
function get_tire_name( $w, $p, $d, $speed_rating = '', $load_index = '', $is_zr = false, $is_lt = false ) {

	$R = $is_zr ? 'ZR' : 'R';

	$LT = $is_lt ? 'LT' : '';

	$str = $LT . (int) $w . '/' . (int) $p . $R . (int) $d;
	$spec = get_tire_spec( $speed_rating, $load_index );

	if ( $spec ) {
		$str .= ' ' . trim( $spec );
	}

	return $str;
}

/**
 * @param $speed_rating
 * @param $load_index
 * @return string
 */
function get_tire_spec( $speed_rating, $load_index ) {

	if ( ! $speed_rating && ! $load_index ) {
		return '';
	}

	$op = '';
	$op .= '(';
	$op .= trim( $load_index );
	$op .= trim( $speed_rating );
	$op .= ')';

	return $op;
}

/**
 * For every 1 inch increase in diameter, we (may) add 0.5 inches to the suggested rim width.
 *
 * For downsizing?? I guess we'll subtract the 0.5 most likely.
 *
 * @param Tire_Atts_Single $target_size
 * @param Tire_Atts_Single $sub_size
 * @param                         $current_rim_width
 *
 * @return float
 */
function get_suggested_substitution_size_rim_width( Tire_Atts_Single $target_size, Tire_Atts_Single $sub_size, $current_rim_width ) {

	$diff = $target_size->diameter - $sub_size->diameter;

	if ( $diff == 0 ) {
		return $current_rim_width;
	}

	$ret = $current_rim_width - ( $diff * 0.5 );
	// 6, or 6.5... we may need to change this to 6.0, or 6.5
	$ret = round( $ret, 1 );

	return $ret;
}

/**
 * This is used when verifying an item can be added to the cart.
 *
 * If the fitment is staggered (and you should test this before calling the function), then you should
 * set $loc = 'rear'.
 *
 * @param                 $part_number
 * @param Fitment_General $fitment
 * @param Wheel_Set       $wheel_set
 * @param string          $loc
 *
 * @return bool
 */
function tire_fits_fitment( $part_number, Fitment_Singular $fitment, $loc = 'front' ) {

	$part_number = gp_force_singular( $part_number );
	$part_number = gp_test_input( $part_number );

	// these generally mean pretty much the same thing
	$loc = $loc === 'universal' ? 'front' : $loc;

	$all_sizes = $fitment->export_sizes();
	$size = gp_array_first( $all_sizes );

	if ( $size['staggered'] ) {

		if ( $loc === 'front' ) {
			$query_size = $size['tires']['front'];
		} else if ( $loc === 'rear' ) {
			$query_size = $size['tires']['rear'];
		} else {
			return false;
		}

	} else {

		if ( $loc === 'front' ) {
			$query_size = $size['tires']['universal'];
		} else {
			return false;
		}
	}

	if ( ! $query_size ) {
		return false;
	}

	// run a query enforcing both the part number, and $size conditions. If we get results then
	// we know the tire fits the fitment.
	$comp = new Query_Components_Tires( 'tires', false );
	$comp->builder->add_to_self( $comp->get_part_number( $part_number ) );

	// run the same sizing conditions in all by size queries...
	// note: this is identical for a lot of parameters, but speed rating / load index and some compound
	// filter types including logic for more than 1 table are not taken into account
	$comp->builder->add_to_self( $comp->get_size( $query_size ) );

	$db = get_database_instance();
	$params = array();
	$q = '';
	$q .= 'SELECT part_number ';
	$q .= 'FROM ' . $db->tires . ' AS tires ';

	$q .= 'WHERE 1 = 1 AND ' . $comp->builder->sql_with_placeholders() . ' ';
	$params = array_merge( $params, $comp->builder->parameters_array() );

	$q .= '';
	$q .= ';';

	$results = $db->get_results( $q, $params );
	$ret = ( is_array( $results ) && count( $results ) > 0 );
	return $ret;
}

/**
 * This is used when verifying an item can be added to the cart.
 *
 * If the fitment is staggered (and you should test this before calling the function), then you should
 * set $loc = 'rear'.
 *
 * important note: we might not check width and offset variance here due to complicated logic
 * that is found elsewhere, that might not be easy to repeat over here.
 *
 * @param                 $part_number
 * @param Fitment_General $fitment
 * @param Wheel_Set       $wheel_set
 * @param string          $loc
 *
 * @return bool
 */
function rim_fits_fitment( $part_number, Fitment_Singular $fitment, $loc = 'front' ) {

	$part_number = gp_force_singular( $part_number );
	$part_number = gp_test_input( $part_number );

	// these generally mean the same thing
	$loc = $loc === 'universal' ? 'front' : $loc;

	$all_sizes = $fitment->export_sizes();
	$size = gp_array_first( $all_sizes );

	if ( $size['staggered'] ) {

		if ( $loc === 'front' ) {
			$query_size = $size['rims']['front'];
		} else if ( $loc === 'rear' ) {
			$query_size = $size['rims']['rear'];
		} else {
			return false;
		}

	} else {

		if ( $loc === 'front' ) {
			$query_size = $size['rims']['universal'];
		} else {
			return false;
		}
	}

	if ( ! $query_size ) {
		return false;
	}

	$diameter = gp_if_set( $query_size, 'diameter' );
	$bolt_pattern = gp_if_set( $query_size, 'bolt_pattern' );
	$center_bore = gp_if_set( $query_size, 'center_bore' );

	// run a query enforcing both the part number, and $size conditions. If we get results then
	// we know the tire fits the fitment.
	$comp = new Query_Components_Rims( 'rims', false );
	$comp->builder->add_to_self( $comp->get_part_number( $part_number ) );

	// Warning: this does not work quite as well as you would expect, because packages
	// can have a lot of dynamic logic for the variance in width and offset that is allowed.
	// we are not going to attempt to repeat that logic here ... so ... let's just check the most necessary size parameters
	// $comp->builder->add_to_self( $comp->get_size( $query_size ) );

	// this is almost the same as $comp->get_size() but we're kind of forced to ignore
	// width and offset, because those can actually vary based on where they are being called.
	// update: we may also be including some pretty high width/offset variances based on bolt pattern and/or make model
	// if this functionality is within the get_size() function, then this may actually work. still, I think its best
	// to be very lenient on the rules here otherwise products may show up in search results taht cannot be added to the cart.
	$comp->builder->add_to_self( array(
		'relation' => 'AND',
		$comp->get_diameter( $diameter ),
		$comp->get_bolt_pattern( $bolt_pattern ),
		$comp->get_center_bore_min( $center_bore ),
	) );

	$db = get_database_instance();
	$params = array();
	$q = '';
	$q .= 'SELECT part_number ';
	$q .= 'FROM ' . $db->rims . ' AS rims ';

	$q .= 'WHERE 1 = 1 AND ' . $comp->builder->sql_with_placeholders() . ' ';
	$params = array_merge( $params, $comp->builder->parameters_array() );

	$q .= '';
	$q .= ';';

	$results = $db->get_results( $q, $params );
	$ret = ( is_array( $results ) && count( $results ) > 0 );
	return $ret;
}

/**
 * ie. pass in -2, and get [Minus 2"]
 *
 * @param $int
 */
function get_plus_minus_text_from_int( $int, $sq_brackets = true ){

	// ensure string zero for when we print it, we don't want empty value
	$abs_int = $int == 0 ? '0' : (int) abs( $int );

	$int = (int) $int;

	if ( ! $int ) {
		$ret = '+0 inch';
	} else if ( $int === 1 ) {
		$ret = '+1 inch';
	} else if ( $int === -1 ) {
		$ret = '-1 inch';
	} else if ( $int > 1 ) {
		$ret = '+' . $abs_int . ' inches';
	} else if ( $int < -1 ) {
		$ret = '-' . $abs_int . ' inches';
	} else {
		$ret = '';
	}


	// Old way. eg. Plus 3", Minus 1", Plus 0"
	// $inches = '"';
//	$_int = $int == 0 ? '0' : (int) abs( $int );
//	$ret = $int < 0 ? 'Minus ' . $_int . $inches  : 'Plus ' . $_int . $inches;

	if ( $sq_brackets ) {
		$ret = '[' . $ret . ']';
	}

	return $ret;
}

/**
 * @param      $f
 * @param      $r
 * @param bool $staggered
 *
 * @return string
 */
function get_plus_minus_text( $f, $r, $staggered = false, $sq_brackets = true, $inches = '"', $stg_sep = ' / ' ){

	$_f = get_plus_minus_text_from_int( $f, $sq_brackets );
	$_r = get_plus_minus_text_from_int( $r, $sq_brackets );

	if ( $staggered ) {
		$ret = $_f . $stg_sep . $_r;
	} else {
		$ret = $_f;
	}

	return $ret;
}

/**
 * @param $class
 *
 * @return bool|mixed|string
 */
function get_tire_class_name( $class ) {
	$obj = DB_Tire_Model_Class::create_instance_via_slug( $class );
	$ret = $obj ? $obj->get( 'name' ) : '';
	return $ret;
}

/**
 * @param $type
 */
function is_tire_type_valid( $type ) {
	$type = gp_force_singular( $type );

	if ( ! $type ) {
		return false;
	}

	$types = Static_Array_Data::tire_model_types();
	$ret = ( in_array( $type, array_keys( $types ) ) );
	return $ret;
}

/**
 * @param $type
 */
function get_tire_type_name( $type, $default = '' ) {
	$obj = DB_Tire_Model_Type::create_instance_via_slug( $type );
	$ret = $obj ? $obj->get( 'name' ) : $default;
	return $ret;
}

/**
 * @param $class
 */
function get_tire_class_text_and_icon( $class ) {
	$text = get_tire_class_name( $class );
	$icon = get_tire_class_icon( $class );
	return get_text_and_icon_html( $text, $icon, 'tire-class' );
}

/**
 * @param $type
 *
 * @return string
 */
function get_tire_type_text_and_icon( $type ) {
	$text = get_tire_type_name( $type );
	$icon = get_tire_type_icon( $type );
	return get_text_and_icon_html( $text, $icon, 'tire-type' );
}

/**
 * @param        $text
 * @param        $icon
 * @param string $add_class
 */
function get_text_and_icon_html( $text, $icon, $add_class = '' ) {

	$op = '';

	$cls = [ 'text-icon-html' ];
	$cls[] = $add_class;

	$op .= '<span class="' . gp_parse_css_classes( $cls ) . '">';

	$op .= '<span class="icon">';
	$op .= $icon;
	$op .= '</span>';

	$op .= '<span class="text">';
	$op .= $text;
	$op .= '</span>';

	$op .= '</span>';

	return $op;
}

/**
 *
 */
function get_winter_approved_html( $text = 'Winter Approved' ){
	$icon = get_tire_type_icon( 'winter' );
	return get_text_and_icon_html( $text, $icon, 'rim-winter-approved' );
}

/**
 *
 */
function get_rim_style_name( $slug, $df = '' ){

	$slug = strtolower( $slug );
	$slug = trim( $slug );

	// note: slug isnt even stored in the database and the style column has
	// for example 'REPLICA' or nothing.
	// so this function basically just maps REPLICA to Replica
	switch( $slug ){
		case 'replica':
			return 'Replica';
			break;
	}

	return gp_test_input( $df );

}

/**
 * @param        $slug
 * @param string $df
 *
 * @return string
 */
function get_rim_type_name( $slug, $df = '' ) {

	$slug = strtolower( $slug );
	$slug = trim( $slug );

	switch( $slug ){
		case 'steel':
			return 'Steel';
			break;
		case 'alloy':
			return 'Alloy';
	}

	return gp_test_input( $df );
}

/**
 * Use get_tire_type_icon() if you only need one... or this when you need all of them.
 *
 * @return array
 */
function get_tire_type_icon_map(){
	return array(
		'winter' => gp_get_icon( 'winter' ),
		'summer' => gp_get_icon( 'summer' ),
		'all-season' => gp_get_icon( 'all_season' ),
		'all-weather' => gp_get_icon( 'all_weather' ),
	);
}

/**
 * Make sure logic here is the same as in get_tire_type_icon_map()
 *
 * @param $type
 */
function get_tire_type_icon( $type ) {

	switch( $type ) {
		case 'winter':
			$ret = gp_get_icon( 'winter' );
			break;
		case 'summer':
			$ret = gp_get_icon( 'summer' );
			break;
		case 'all-season':
			$ret = gp_get_icon( 'all_season' );
			break;
		case 'all-weather':
			$ret = gp_get_icon( 'all_weather' );
			break;
		default:
			$ret = '';
	}

	return $ret;
}

/**
 * @param $v
 */
function get_tire_class_icon( $class ) {

	switch( $class ) {
		case 'passenger':
			$ret = gp_get_icon( 'car_type_car' );
			break;
		case 'suv':
			$ret = gp_get_icon( 'car_type_suv' );
			break;
		case 'truck':
			$ret = gp_get_icon( 'car_type_truck' );
			break;
		default:
			$ret = '';
	}

	return $ret;

}

/**
 * @param $type
 */
function get_rim_type_icon( $type ) {

	switch( $type ) {
		case 'steel':
			$ret = gp_get_icon( 'winter' );
			break;
		case 'alloy':
			// don't think we'll ever use this, so summer might not be the most accurate icon here
			$ret = gp_get_icon( 'summer' );
			break;
		default:
			$ret = '';
	}

	return $ret;
}

/**
 * Use the current date/time to get the default tire type,
 * 'summer', 'winter', 'all-weather', or 'all-season'
 */
function get_default_package_type(){

	$dt = new DateTime();
	$year = $dt->format( 'Y' );

	// march 31 of this year
	$summer_start = $year . '0331';

	// oct 31 of this year
	$summer_end = $year . '1031';

	$yyyymmdd = $dt->format( 'Ymd' );

	if ( $yyyymmdd <= $summer_end && $yyyymmdd >= $summer_start ) {
		$type = 'all-season';
	} else {
		$type =  'winter';
	}

	$ret = tire_type_is_valid( $type ) ? $type : 'all-season';
	return $ret;
}

/**
 * @param $products
 * @param $locale
 *
 * @return float|int
 */
function get_aggregate_products_price( $products, $locale ) {

	$total = 0;

	assert( app_is_locale_valid( $locale ), 'agg_price_invalid_locale');

	foreach ( $products as $p ) {

		assert( $p instanceof DB_Product, 'agg_price_not_product' );
		$col = $locale === APP_LOCALE_CANADA ? 'price_ca' : 'price_us';
		$val = round( $p->get( $col ), 2 );
		$total = $total + $val;
		$total = round( $total, 2 );
	}

	return $total;
}

/**
 * @param $brand
 * @param $model
 * @return string
 */
function brand_model_name( $brand, $model ) {

    $b = '';
    $m = '';

    if ( $brand instanceof DB_Product_Brand ) {
        $b = $brand->get_and_clean( 'name' );
    }

    if ( $model instanceof DB_Product_Model ) {
        $m = $model->get_and_clean( 'name' );
    }

    $ret = $b . ' ' . $m;
    $ret = trim( $ret );

    return $ret;
}

/**
 * @param DB_Product_Brand $brand
 * @param DB_Product_Model $model
 * @param null             $finish
 * @param bool             $addBrackets
 *
 * @return string
 */
function brand_model_finish_name( $brand, $model, $finish = null, $addBrackets = true ) {

    $ret = brand_model_name( $brand, $model );

    if ( $finish && $finish instanceof DB_Rim_Finish ) {
        if ($addBrackets) {
            $ret .= ' (' . gp_test_input( $finish->get_finish_string() ) . ')';
        } else {
            $ret .= ' ' . gp_test_input( $finish->get_finish_string() );
        }
    }

    $ret = trim( $ret );

    return $ret;
}

