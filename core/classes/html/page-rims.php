<?php

/**
 * Class Page_Rims
 */
Final Class Page_Rims extends Page_Products_Filters_Methods {

    /**
     * Page_Rims constructor.
     * @param array $userdata
     * @param array $args
     * @param null $vehicle
     */
	public function __construct( $userdata = array(), $args = array(), $vehicle = null ) {
		$this->class_type          = 'rims';
        $this->class_type_singular = 'rim';
        $this->ajax_action = 'rim_filters';
        parent::__construct( $userdata, $args, $vehicle );

        $brand_slug = gp_test_input( @$this->userdata['brand'] );
        if ( $brand_slug ) {
            $this->brand = DB_Rim_Brand::get_instance_via_slug( $brand_slug );
        }

        // empty brand instance by default
        if ( ! $this->brand instanceof DB_Rim_Brand ) {
            $this->brand = new DB_Rim_Brand( array() );
        }

        if ( @$args[ 'context' ] === 'by_brand' ) {
            // ensure brand was found in database
            if ( $this->brand->get( 'slug' ) ) {
                $this->context = 'by_brand';
            } else {
                $this->context = 'invalid';
            }
        }

        if ( @$args['context'] === 'by_size' ) {
            // nothing to validate. The page is valid even with no sizes specified.
            $this->context = 'by_size';
        }

        if ( @$args['context'] === 'by_vehicle' ) {
            if ( $this->vehicle->is_complete() ) {
                $this->context = 'by_vehicle';
            } else {
                $this->context = 'invalid';
            }
        }

        if ( $this->context !== 'invalid' ) {
            $this->setup_meta_titles_etc();
            Header::$canonical = $this->get_canonical_url();
            $this->top_image_args = $this->build_top_image_args();
        }
	}

    /**
     * The key is the NAME of the filter, which is not normally the same as the query argument.
     *
     * @param      $key
     * @param bool $results_are_grouped
     * @return bool|float|int|mixed|string|null
     */
	public function get_dynamic_filter_value_from_loop_data( $key, $results_are_grouped = false ) {

		// very important to return null, not an empty string
		$add = null;

		if ( $results_are_grouped ) {

			switch ( $key ) {
				case 'rim_brand':
					$add = $this->loop_brand->get( 'slug' );
					break;
				//				case 'rim_type':
				//					break;
				case 'rim_color_1':
					$add = gp_if_set( $this->loop_data, 'color_1', null );
					break;
				case 'rim_color_2':
					$add = gp_if_set( $this->loop_data, 'color_2', null );
					break;
				case 'rim_finish':
					$add = gp_if_set( $this->loop_data, 'finish', null );
					break;
				case 'rim_type':
					$add = strtolower( gp_if_set( $this->loop_data, 'type', '' ) );
					break;
				case 'rim_style':
					$add = strtolower( gp_if_set( $this->loop_data, 'style', '' ) );
					break;
			}

		} else {

			switch ( $key ) {
				case 'rim_brand':
					$add = $this->loop_front_rim->get( 'brand_slug' );
					break;
				case 'rim_color_1':
					$add = $this->loop_front_rim->finish->get( 'color_1' );
					break;
				case 'rim_color_2':
					$add = $this->loop_front_rim->finish->get( 'color_2' );
					break;
				case 'rim_finish':
					$add = $this->loop_front_rim->finish->get( 'finish' );
					break;
				case 'rim_price':
					$cents = $this->loop_front_rim->get_price_cents();
					$add   = $cents * 4;
					break;
				case 'rim_type':
					$add = strtolower( $this->loop_front_rim->get( 'type' ) );
					break;
				case 'rim_style':
					$add = strtolower( $this->loop_front_rim->get( 'style' ) );
					break;
				case 'rim_price_each':
					$cents = $this->loop_front_rim->get_price_cents();
					$add   = $cents;
					break;
			}

		}

		return $add;
	}

	/**
	 *
	 */
	public function get_allowed_filters() {

		// note on rim type:
		// some websites dont handle this, others have different ways of handling
		// for example, do we show a filter for steel or alloy, or is it better
		// to show 'winter approved' or not. also, surprisingly, the same rim model
		// can have both rims that are steel and others that are alloy.
		// perhaps the steel/alloy belongs to the finish instead. anyways, a lot of sites
		// don't even show filters for this, so that might be what we end up doing.
		// the search results themselves may show whether or not products are winter approved in the
		// result set, we just might not have a filter for them. if you add a winter approved filter,
		// i suggest searching the code to see what winter approved means in comments elsewhere.

		switch ( $this->context ) {
			case 'by_vehicle':

				if ( $this->vehicle->fitment_object->wheel_set->get_selected()->is_staggered() ) {
					return array(
						'rim_brand',
						'rim_color_1',
						'rim_color_2',
						'rim_finish',
						'rim_type',
						'rim_style',
						'rim_price', // set of 4
					);
				} else {
					return array(
						'rim_brand',
						'rim_color_1',
						'rim_color_2',
						'rim_finish',
						'rim_type',
						'rim_style',
						'rim_price_each', // each
					);
				}


			case 'by_brand':
				return array(
					// 'rim_type',
					'rim_color_1',
					'rim_color_2',
					'rim_finish',
					'rim_type',
					'rim_style',
				);
			case 'by_size':
				return array(
//					'rim_diameter',
//					'rim_width',
//					'rim_bolt_pattern',
//					'rim_hub_bore',
//					'rim_offset','rim_type',
					'rim_brand',
					'rim_color_1',
					'rim_color_2',
					'rim_finish',
					'rim_type',
					'rim_style',
				);
			default:
				break;
		}

		return [];
	}

	/**
	 *
	 * @param $userdata - PRE FILTERED $this->userdata, some modifications are made
	 *
	 * @return array
	 */
	public function query_products( $userdata ) {

		// $filters = $this->whitelist_userdata_for_filters();

		switch ( $this->context ) {
			case 'by_vehicle':
				return query_rims_by_sizes( $this->vehicle->fitment_object->export_sizes(), $userdata );
			case 'by_size':
				return query_rims_grouped_by_finish( $userdata, [
				    'sort_by_stock_group' => true,
                ] );
			case 'by_brand':
                return query_rims_grouped_by_finish( array_merge( $userdata, [ 'brand' => $this->brand->get( 'slug' ) ] ), [
                    'sort_by_stock_group' => true,
                ] );
		}
	}
}
