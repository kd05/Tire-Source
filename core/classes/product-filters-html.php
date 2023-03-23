<?php

/**
 * Methods called dynamically here. Check self::call() before assuming
 * a method is not in use.
 *
 * Class Product_Filters_Html
 */
Class Product_Filters_Html {

	public function __construct() {
	}

	/**
	 * @param $fn
	 */
	public static function call( $fn, array $func_args = array() ) {
		$cls = get_called_class();
		if ( method_exists( $cls, $fn ) ) {
			return call_user_func_array( array( $cls, $fn ), $func_args );
		}
	}

//	/**
//	 * Primary color
//	 */
//	public static function get_rim_color_1( $userdata, $args = array() ) {
//		return get_product_sidebar_filter( array(
//			'title' => 'Primary Colour',
//			'name' => 'color_1',
//			'type' => 'radio',
//			'items' => array(),
//			'userdata' => $userdata,
//		) );
//	}
//
//
//	/**
//	 * @param $userdata
//	 */
//	public static function get_rim_brand( $userdata, $args = array() ) {
//
//		// might be identical to tire brand
//		return get_product_sidebar_filter( array(
//			'title' => gp_if_set( $args, 'title' ),
//			'name' => gp_if_set( $args, 'form_name' ),
//			'type' => gp_if_set( $args, 'type', 'radio' ),
//			'items' => array(),
//			'userdata' => $userdata,
//		) );
//	}
//
//	/**
//	 * @param $userdata
//	 */
//	public static function get_tire_brand( $userdata, $args = array() ) {
//
//		// might be identical to rim brand
//		return get_product_sidebar_filter( array(
//			'title' => gp_if_set( $args, 'title' ),
//			'name' => gp_if_set( $args, 'form_name' ),
//			'type' => gp_if_set( $args, 'type', 'radio' ),
//			'items' => get_tire_brand_filter_options(),
//			'userdata' => $userdata,
//		) );
//	}
//
//	/**
//	 * @param $userdata
//	 *
//	 * @return string
//	 */
//	public function get_tire_type( $userdata, $args = array() ) {
//
//		return get_product_sidebar_filter( array(
//			'title' => 'Type',
//			'name' => '_type',
//			'type' => 'radio',
//			'items' => Static_Array_Data::export_for_filters( Static_Array_Data::tire_model_types() ),
//			'userdata' => $userdata,
//		) );
//	}
//
//	/**
//	 * @param $userdata
//	 *
//	 * @return string
//	 */
//	public function get_tire_class( $userdata, $args = array() ) {
//
//		$args['userdata'] = $userdata;
//
//		return get_product_sidebar_filter( array(
//			'title' => gp_if_set( $args, 'title' ),
//			'name' => gp_if_set( $args, 'form_name' ),
//			'type' => gp_if_set( $args, 'type', 'radio' ),
//			'items' => Static_Array_Data::export_for_filters( Static_Array_Data::tire_model_classes() ),
//			'userdata' => $userdata,
//		) );
//	}
//
//	/**
//	 * @param $userdata
//	 *
//	 * @return string
//	 */
//	public function get_tire_category( $userdata, $args = array() ) {
//
//		return get_product_sidebar_filter( array(
//			'title' => gp_if_set( $args, 'title' ),
//			'name' => gp_if_set( $args, 'form_name' ),
//			'type' => gp_if_set( $args, 'type', 'radio' ),
//			'items' => Static_Array_Data::export_for_filters( Static_Array_Data::tire_model_categories() ),
//			'userdata' => $userdata,
//		) );
//	}
//
//	/**
//	 * @param $userdata
//	 *
//	 * @return string
//	 */
//	public function get_tire_load_index( $userdata, $args = array() ) {
//
//		return get_product_sidebar_filter( array(
//			'title' => gp_if_set( $args, 'title' ),
//			'name' => gp_if_set( $args, 'form_name' ),
//			'type' => gp_if_set( $args, 'type', 'radio' ),
//			'items' => get_load_index_filter_options(),
//			'userdata' => $userdata,
//		) );
//	}
//
//	/**
//	 * @param $userdata
//	 *
//	 * @return string
//	 */
//	public function get_tire_speed_rating( $userdata, $args = array() ) {
//
//		return get_product_sidebar_filter( array(
//			'title' => gp_if_set( $args, 'title' ),
//			'name' => gp_if_set( $args, 'form_name' ),
//			'type' => gp_if_set( $args, 'type', 'radio' ),
//			'items' => Static_Array_Data::export_for_filters( Static_Array_Data::tire_speed_ratings() ),
//			'userdata' => $userdata,
//		));
//	}
//
//	/**
//	 * @param $userdata
//	 *
//	 * @return string
//	 */
//	public function get_tire_price( $userdata, $args = array() ) {
//
//		return get_product_sidebar_filter( array(
//			'title' => gp_if_set( $args, 'title' ),
//			'name' => gp_if_set( $args, 'form_name' ),
//			'type' => gp_if_set( $args, 'type', 'radio' ),
//			'items' => array(
//				'0-10000' => 'Under $100.00',
//				'10000-19999' => '$100.00 - $199.99',
//				'20000-29999' => '$200.00 - $299.00',
//				'30000-39999' => '$300.00 - $399.00',
//				'40000-' => 'Over $400.00',
//			),
//			'userdata' => $userdata,
//		));
//	}

}