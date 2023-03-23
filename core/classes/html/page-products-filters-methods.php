<?php

/**
 * For sidebar filters.
 *
 * Class Page_Products_Filters_Methods
 */
Abstract Class Page_Products_Filters_Methods extends Page_Products {

    /**
     * Page_Products_Filters_Methods constructor.
     * @param $userdata
     * @param array $args
     * @param null $vehicle
     * @throws Exception
     */
	public function __construct( $userdata, $args = array(), $vehicle = null ) {
		parent::__construct( $userdata, $args, $vehicle );
	}

	/**
	 * @param $filter_name
	 *
	 * @return bool|mixed
	 */
	public function get_filter_data( $filter_name ) {
		return gp_if_set( $this->filters_data, $filter_name, null );
	}

	/**
	 * All filter types here... tires, rims, packages. Prefix types when needed.
	 *
     * @param $filter_slug
     * @return mixed|string
     */
	protected function render_filter( $filter_slug ) {

		$allow_dynamic = true;

		$filter_array = $this->pre_filter_a_filter_before_rendering( $filter_slug, $allow_dynamic );

		// dont render filters with no options showing
		if ( $filter_array ) {
			return get_product_sidebar_filter( $filter_array );
		}

		return '';
	}

	/**
	 * Note: We call this function immediately before rendering to both add all the items and them possibly
	 * remove some in order to make the filter dynamic. However, for price filters, we also have to call this
	 * pretty early on (long before rendering), in order to sort prices into the price ranges that will be
	 * printed in the filter. When calling early, use $allow_dynamic = false. Otherwise, it should be true.
	 * Also, when calling early on we wrap it in a method_exists() call in parent class, so your IDE may not
	 * show that function call if you ctrl+click the function.
	 *
	 * @param      $filter_slug
	 * @param bool $allow_dynamic
	 *
	 * @return bool|mixed
	 */

	public function pre_filter_a_filter_before_rendering( $filter_slug, $allow_dynamic = false ){

		// the data before the callback. Should have no 'items' index, or an empty 'items' index.
		$filter = $this->get_filter_data( $filter_slug );

		// add the userdata, which is quite necessary to render items already checked or with values filled in
		$filter['userdata'] = $this->get_userdata_for_filters();

		$complete = gp_if_set( $filter, 'complete' );

		// I doubt we will use this, but if we don't want a callback to get $data['items'] then
		// just pass in $data['complete'] = true
		if ( $complete ) {
			return $filter;
		}

		// the callback takes in an array $filter, and returns an array $filter.
		// it probably adds an array index $filter['items'] with ALL filter items
		// (not dynamic based on results yet)
		$callback = gp_if_set( $filter, 'callback_add_items' );

		if ( method_exists( $this, $callback ) ) {
			$filter = $this->{$callback}( $filter );
		} else if ( $callback ) {
			throw_dev_error( 'Invalid callback supplied to a filter: ' . $filter_slug );
			exit;
		}

		// default to true
		$is_dynamic = gp_if_set( $filter, 'is_dynamic', true );

		// Now, to make a filter dynamic, we may remove some of the items, and we'll add the count
		// to the array. The count will (may) be used later when rendering. What we do with the count
		// can change in our rendering function. We may or may not render zero count items, and we may
		// or may not show the counts beside each item. Chances are we'll only render items with count > 0 AND
		// show the count beside them.
		// NOTE: if the filter is dynamic, then you must set all visible options in $this->dynamic_filters, otherwise
		// the filter will be shown as empty.
		if ( $is_dynamic && $allow_dynamic ) {

			$items = gp_if_set( $filter, 'items', array() );
			$_items = array();

			if ( $items && is_array( $items ) ) {
				foreach ( $items as $key=>$value ) {

					if ( is_array( $value ) ) {

						// this name is confusing. $value['value'] is actually our "key" here
						// because its the "value" in <option value="">, which in array format, is the key.
						// ie. $_key could be 'all-weather' not "All Weather"
						$_key = gp_if_set( $value, 'value' );

						$count = isset( $this->dynamic_filters[$filter_slug][$_key] ) ? $this->dynamic_filters[$filter_slug][$_key] : 0;

						if ( $count > 0 ) {
							$value['count'] = $count;
							$_items[$key] = $value;
						}
					} else if ( gp_is_singular( $value ) ) {

						$count = isset( $this->dynamic_filters[$filter_slug][$key] ) ? $this->dynamic_filters[$filter_slug][$key] : 0;

						if ( $count > 0 ) {
							$_items[$key] = array(
								'text' => $value,
								'count' => $count,
							);
						}

					} else {
						// in case we get to here, just return whatever was there beforehand.
						// I'm not expecting an object to be present however.
						$_items[$key] = $value;
					}
				}
			}

			$filter['items'] = $_items;
		}

		return $filter;
	}


    /**
     * @param      $filter_name
     * @param      $prop
     * @param null $default
     * @return bool|mixed|null
     */
	public function get_filter_property( $filter_name, $prop, $default = null ) {
		$item = $this->get_filter_data( $filter_name );
		if ( ! $item ) {
			return $default;
		}

		return gp_if_set( $item, $prop, $default );
	}

	/**
	 * All filters data with callback functions in place to generate the items needed.
	 *
	 * @return array
	 */
	protected function get_filters_data() {

		$ret = array();

		$ret[ 'tire_brand' ] = array(
			'name' => 'brand',
			'title' => 'Brand',
			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
			'callback_add_items' => 'get_tire_brand_filter_items',
		);

		$ret[ 'tire_model' ] = array(
			'name' => 'model',
			'title' => 'Model',
			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
			'callback_add_items' => 'get_tire_model_filter_items',
		);

		$ret[ 'tire_type' ] = array(
			'name' => 'type',
			'title' => 'Type',
			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
			'add_class' => 'always-open',
			'callback_add_items' => 'get_tire_type_filter_items',
		);

		$ret[ 'tire_class' ] = array(
			'name' => 'class',
			'title' => 'Class',
			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
			'callback_add_items' => 'get_tire_class_filter_items',
		);

		$ret[ 'tire_category' ] = array(
			'name' => 'category',
			'title' => 'Category',
			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
			'callback_add_items' => 'get_tire_category_filter_items',
		);

		$ret[ 'tire_load_index' ] = array(
			'name' => 'load_index',
			'title' => 'Load Index',
			// Dont be confused by terminology here... this filter IS DYNAMIC,
			// but, we can do the dynamic portion inside of the callback due to the
			// unique condition of being a "greater than or equal to" kind of filter, therefore
			// the normal dynamic filter code does not work. But by the time the callback is run, we
			// will have all the information needed (held within $this->dynamic_filters) to make the filter
			// dynamic.
			'is_dynamic' => false,
			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
			'callback_add_items' => 'get_tire_load_index_filter_items',
		);

		$ret[ 'tire_speed_rating' ] = array(
			'name' => 'speed_rating',
			'title' => 'Speed Rating',
			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
			'callback_add_items' => 'get_tire_speed_rating_filter_items',
		);

		$ret[ 'rim_diameter' ] = array(
			'name' => 'diameter',
			'title' => 'Diameter',
			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
			'callback_add_items' => 'get_rim_diameter_filter_items',
		);

		$ret[ 'rim_width' ] = array(
			'name' => 'width',
			'title' => 'Width',
			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
			'callback_add_items' => 'get_rim_width_filter_items',
		);

//		// some of these rim filters no longer exist, they have their own rim size form instead
//		$ret[ 'rim_bolt_pattern' ] = array(
//			'name' => 'bolt_pattern',
//			'title' => 'Bolt Pattern',
// //			'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
//			'callback_add_items' => 'get_rim_bolt_pattern_filter_items',
//		);
//
//		// note: front-end called this hub bore, we often use center_bore in php and database
//		$ret[ 'rim_center_bore' ] = array(
//			// 'name' => 'hub_bore',
//			'name_min' => 'hub_bore_min',
//			'name_max' => 'hub_bore_max',
//			'title' => 'Hub Bore',
//			// this callback adds an 'html' elements to the data
//			'callback_add_items' => 'get_rim_hub_bore_filter_items',
//		);
//
//		// note: front-end called this hub bore, we often use center_bore in php and database
//		$ret[ 'rim_offset' ] = array(
//			// 'name' => 'offset',
//			'name_min' => 'offset_min',
//			'name_max' => 'offset_max',
//			'title' => 'Offset',
//			// this callback adds an 'html' elements to the data
//			'callback_add_items' => 'get_rim_offset_filter_items',
//		);

		$ret[ 'rim_brand' ] = array(
			'name' => 'brand',
			'title' => 'Brand',
			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
			'callback_add_items' => 'get_rim_brand_filter_items',
		);

		$ret[ 'rim_color_1' ] = array(
			'name' => 'color_1',
			'title' => 'Primary Colour',
			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
			'callback_add_items' => 'get_rim_color_1_filter_items',
		);

		$ret[ 'rim_color_2' ] = array(
			'name' => 'color_2',
			'title' => 'Secondary Colour',
			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
			'callback_add_items' => 'get_rim_color_2_filter_items',
		);

		$ret[ 'rim_finish' ] = array(
			'name' => 'finish',
			'title' => 'Finish',
			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
			'callback_add_items' => 'get_rim_finish_filter_items',
		);

		// the different names for types here are confusing but necessary for... reasons ...
		$ret[ 'rim_type' ] = array(
			'name' => 'type',
			'title' => 'Type',
			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
			'callback_add_items' => 'get_rim_type_filter_items',
		);

		// debatable whether this should even be kept in place
		$ret[ 'rim_style' ] = array(
			'name' => 'rim_style',
			'title' => 'Replica',
			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
			'callback_add_items' => 'get_rim_style_filter_items',
		);

//		$ret[ 'rim_design' ] = array(
//			'name' => 'design',
//			'title' => 'Design Attributes',
//			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
//			'callback_add_items' => 'get_rim_design_filter_items',
//		);

		// Tire(ea)
		$ret[ 'tire_price_each' ] = array(
			'name' => 'price_each',
			'title' => 'Price (ea)',
			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
			'callback_add_items' => 'get_tire_each_price_filter_items',
		);

		// Tire Price set of 4
		$ret[ 'tire_price' ] = array(
			'name' => 'price',
			'title' => 'Price (set of 4)',
			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
			'callback_add_items' => 'get_tire_set_price_filter_items',
		);

		// Rim(ea)
		$ret[ 'rim_price_each' ] = array(
			'name' => 'price_each',
			'title' => 'Price (ea)',
			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
			'callback_add_items' => 'get_rim_each_price_filter_items',
		);

		// Rim Price set of 4
		$ret[ 'rim_price' ] = array(
			'name' => 'price',
			'title' => 'Price (set of 4)',
			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
			'callback_add_items' => 'get_rim_set_price_filter_items',
		);

		// Package set of 8
		$ret[ 'package_price' ] = array(
			'name' => 'price',
			'title' => 'Price',
			// 'type' => 'checkbox', // this makes multiple values selectable, but also makes url ugly
			'callback_add_items' => 'get_package_price_filter_items',
		);

		//  note: package queries require a type to be set.
		$ret[ 'package_type' ] = array(
			'name' => 'type',
			// this defaults to true in other items
			'title' => 'Type',
			'callback_add_items' => 'get_package_type_filter_items',
			// these 3 items make this filter behave like a "required" filter which
			// always shown all options, and always stays open. Its quite different
			// than all the other filters.
			'add_class' => 'always-open',
			'radio_button_can_be_unchecked' => false,
			'is_dynamic' => false, // this needs to show all options at all times
		);

		// ensure slug is in index
		foreach ( $ret as $k=>$v ) {
			if ( ! isset( $v['slug'] ) ) {
				$ret[$k]['slug'] = $k;
			}
		}

		return $ret;
	}

	/**
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_rim_diameter_filter_items( array $filter ) {
		$filter['items'] = get_rim_diameter_filter_options();
		return $filter;
	}

	/**
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_rim_width_filter_items( array $filter ) {
		$filter['items'] = get_rim_width_filter_options();
		return $filter;
	}

	/**
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_rim_bolt_pattern_filter_items( array $filter ) {
		$filter['items'] = get_rim_bolt_pattern_filter_options();
		return $filter;
	}

	/**
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_rim_hub_bore_filter_items( array $filter ) {

		$name_min = gp_if_set( $filter, 'name_min', 'hub_bore_min' );
		$name_max = gp_if_set( $filter, 'name_max', 'hub_bore_max' );
		$userdata = gp_if_set( $filter, 'userdata' );

		$filter['html'] = get_rim_hub_bore_form_items_html( $name_min, $name_max, $userdata );
		return $filter;
	}

	/**
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_rim_offset_filter_items( array $filter ) {

		$name_min = gp_if_set( $filter, 'name_min', 'offset_min' );
		$name_max = gp_if_set( $filter, 'name_max', 'offset_max' );
		$userdata = gp_if_set( $filter, 'userdata' );

		$filter['html'] = get_rim_offset_form_items_html( $name_min, $name_max, $userdata );
		return $filter;
	}

	/**
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_package_type_filter_items( array $filter ) {
	    list( $items, $selected ) = $this->get_package_type_options_and_selected();
		$filter['items'] = Static_Array_Data::export_for_filters( $items );
		$filter['icon_map'] = get_tire_type_icon_map();
		return $filter;
	}

	/**
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_tire_type_filter_items( array $filter ) {
		$filter[ 'items' ] = get_tire_type_filter_options();
		$filter['icon_map'] = get_tire_type_icon_map();
		return $filter;
	}

	/**
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_tire_brand_filter_items( array $filter ) {
		$filter[ 'items' ] = get_tire_brand_filter_options();

		return $filter;
	}

	/**
	 * currently, we don't have filters for models and I don't think we will.
	 *
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_tire_model_filter_items( array $filter ) {
		return array(); // empty array should cause filter to not render
		//		$filter['items'] = array();
		//		return $filter;
	}

	/**
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_tire_class_filter_items( array $filter ) {
		$filter[ 'items' ] = Static_Array_Data::export_for_filters( Static_Array_Data::tire_model_classes() );

		return $filter;
	}

	/**
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_tire_category_filter_items( array $filter ) {
		$filter[ 'items' ] = Static_Array_Data::export_for_filters( Static_Array_Data::tire_model_categories() );

		return $filter;
	}

	/**
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_tire_load_index_filter_items( array $filter ) {

		// frequency 'histogram' sort of.
		// ie. 3 tires with load index 91, 1 tire with load index 92 ...
		$frequencies = gp_if_set( $this->dynamic_filters, 'tire_load_index', array() );
		$frequencies = gp_make_array( $frequencies ); // in case of false

		// all items
		$_items = get_load_index_filter_options();
		$items = array();

		if ( $_items ) {
			foreach ( $_items as $key=>$value ) {

				foreach ( $frequencies as $f1=>$f2 ) {

					if ( $f1 >= $key ) {

						$items[$key] = isset( $items[$key] ) ? $items[$key] : array();
						$items[$key]['text'] = $value;
						$items[$key]['count'] = isset( $items[$key]['count'] ) ? $items[$key]['count'] : 0;
						$items[$key]['count']+= $f2;
					}
				}
			}
		}

		$filter[ 'items' ] = $items;
		return $filter;
	}

	/**
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_tire_speed_rating_filter_items( array $filter ) {
		$filter[ 'items' ] = get_tire_speed_rating_filter_options();

		return $filter;
	}

	/**
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_rim_brand_filter_items( array $filter ) {
		$filter[ 'items' ] = get_rim_brand_filter_options();

		return $filter;
	}

	/**
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_rim_type_filter_items( array $filter ) {
		$filter[ 'items' ] = get_rim_type_filter_options();

		return $filter;
	}

	/**
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_rim_finish_filter_items( array $filter ) {
		$filter[ 'items' ] = get_rim_finish_filter_options();

		return $filter;
	}

	/**
	 * @return array
	 */
	function get_rim_style_filter_items( array $filter ){
		$filter[ 'items' ] = get_rim_style_filter_options();

		return $filter;
	}

	/**
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_rim_color_1_filter_items( array $filter ) {
		$filter[ 'items' ] = get_rim_color_1_filter_options();

		return $filter;
	}

	/**
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_rim_color_2_filter_items( array $filter ) {
		$filter[ 'items' ] = get_rim_color_2_filter_options();

		return $filter;
	}

	/**
	 * Single Tire
	 *
	 * @param array $filter
	 */
	protected function get_tire_each_price_filter_items( array $filter ) {

		$items             = array(
			price_range_in_cents_array_key( 0, 99.99 ) => 'Under $100.00',
			price_range_in_cents_array_key( 100.00, 199.99 ) => '$100.00 to $199.99',
			price_range_in_cents_array_key( 200.00, 299.99 ) => '$200.00 to $299.99',
			price_range_in_cents_array_key( 300.00, 399.99 ) => '$300.00 to $399.99',
			price_range_in_cents_array_key( 400, null ) => 'Over $400.00',
		);

		$filter[ 'items' ] = $items;
		return $filter;
	}

	/**
	 * Set of 4 Tires
	 *
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_tire_set_price_filter_items( array $filter ) {

		$items             = array(
			price_range_in_cents_array_key( 0, 399.99 ) => 'Under $400.00',
			price_range_in_cents_array_key( 400.00, 599.99 ) => '$400.00 to $599.99',
			price_range_in_cents_array_key( 600.00, 799.99 ) => '$600.00 to $799.99',
			price_range_in_cents_array_key( 800.00, 999.99 ) => '$800.00 to $999.99',
			price_range_in_cents_array_key( 1000, null ) => 'Over $1000.00',
		);

		$filter[ 'items' ] = $items;
		return $filter;
	}

	/**
	 * Single Rim
	 *
	 * @param array $filter
	 */
	protected function get_rim_each_price_filter_items( array $filter ) {

		$items             = array(
			price_range_in_cents_array_key( 0, 199.99 ) => 'Under $200.00',
			price_range_in_cents_array_key( 200.00, 299.99 ) => '$200.00 to $299.99',
			price_range_in_cents_array_key( 300.00, 399.99 ) => '$300.00 to $399.99',
			price_range_in_cents_array_key( 400.00, 499.99 ) => '$400.00 to $499.99',
			price_range_in_cents_array_key( 500, null ) => 'Over $500.00',
		);

		$filter[ 'items' ] = $items;
		return $filter;
	}

	/**
	 * Set of 4 rims
	 *
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_rim_set_price_filter_items( array $filter ) {

		$items             = array(
			price_range_in_cents_array_key( 0, 599.99 ) => 'Under $600.00',
			price_range_in_cents_array_key( 600.00, 799.99 ) => '$600.00 to $799.99',
			price_range_in_cents_array_key( 800.00, 999.99 ) => '$800.00 to $999.99',
			price_range_in_cents_array_key( 1000.00, 1249.99 ) => '$1000.00 to $1250.00',
			price_range_in_cents_array_key( 1250, null ) => 'Over $1250.00',
		);
		$filter[ 'items' ] = $items;

		return $filter;
	}

	/**
	 * Set of 4 Tires and 4 Rims
	 *
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function get_package_price_filter_items( array $filter ) {
		$items             = array(
			price_range_in_cents_array_key( 0, 749.99 ) => 'Under $750.00',
			price_range_in_cents_array_key( 750.00, 999.99 ) => '$750.00 to $999.99',
			price_range_in_cents_array_key( 1000.00, 1249.99 ) => '$1000.00 to $1249.99',
			price_range_in_cents_array_key( 1250.00, 1499.99 ) => '$1250.00 to $1499.99',
			price_range_in_cents_array_key( 1500, null ) => 'Over $1500.00',
		);
		$filter[ 'items' ] = $items;

		return $filter;
	}
}

/**
 * Use for filtering products..... not for charging credit cards.
 *
 * $str should have a range like "min-max", where min and max are prices in cents.
 *
 * for only a minimum, you can have $str = "min+"
 *
 * for only a maximum, yuu should have $str = "0-max"
 *
 * @param $str
 *
 * @return array
 */
function parse_price_range_string_in_cents( $str ) {

	// seems a bit overkill.. but if $str is an array of of the correct length and depth, just return it.
	// its completely reasonable that we may call this function a few times over the course of a page load on the same data
	if ( is_array( $str ) && count( $str ) === 2 && is_array_numerically_indexed( $str ) && gp_is_array_depth_1( $str ) ) {
		return $str;
	}

	if ( ! gp_is_singular( $str ) ) {
		return null;
	}

	if ( strpos( $str, '-' ) !== false ) {
		$arr = explode( '-', $str );
		if ( count( $arr ) === 2 ) {
			$min = $arr[0];
			$max = $arr[1];
			return [$min, $max];
		}
	} else if ( strpos( $str, '+' ) ) {

		// ie "99999+"
		$arr = explode( '+', $str );

		if ( count( $arr ) === 2 ) {
			$min = $arr[0];
			$max = null;
			return [$min, $max];
		}
	} else if ( gp_is_integer( $str ) ) {

		// just a fallback shouldn't trigger if we setup our forms properly
		$min = $str;
		$max = null;
		return [$min,$max];
	}

	return null;
}

/**
 * @param $min_dollars
 * @param $max_dollars
 *
 * @return string
 */
function price_range_in_cents_array_key( $min_dollars, $max_dollars ) {

	// careful.. this won't catch "0.00"
	$min_dollars = $min_dollars ? $min_dollars : 0;
	$max_dollars = $max_dollars ? $max_dollars : 0;

	$min_cents = dollars_to_cents( $min_dollars );
	$max_cents = dollars_to_cents( $max_dollars );

	if ( $max_cents ){
		$str = $min_cents . '-' . $max_cents;
	} else {
		$str = $min_cents . '+';
	}

	return $str;
}