<?php

/**
 * Class Tires_Page
 */
Final Class Page_Tires extends Page_Products_Filters_Methods {

	/**
	 * This is tire type. Not to be confused with $class_type;
	 *
	 * Ie. "summer", "winter", "all-season", "all-weather".
	 *
	 * @var
	 */
	protected $type;

	/**
	 * Ie. if context is 'by_vehicle',
	 * the buttons that link to single tire pages have to have make, model, year, trim, and fitment
	 * in their url.
	 *
	 * @var
	 */
	protected $details_button_default_query_args;

    /**
     * Page_Tires constructor.
     * @param array $userdata
     * @param array $args
     * @param null $vehicle
     * @throws Exception
     */
	public function __construct( $userdata = array(), $args = array(), $vehicle = null ) {
		$this->class_type = 'tires';
		$this->class_type_singular = 'tire';
        $this->ajax_action = 'tire_filters';

        // order matters
		parent::__construct( $userdata, $args, $vehicle );

		$brand_slug = gp_test_input( @$this->userdata['brand'] );
		if ( $brand_slug ) {
            $this->brand = DB_Tire_Brand::get_instance_via_slug( $brand_slug );
        }

        // empty brand instance by default
        if ( ! $this->brand instanceof DB_Tire_Brand ) {
            $this->brand = new DB_Tire_Brand( array() );
        }

        // do this regardless of context
        if ( is_tire_type_valid( @$userdata['type'] ) ) {
            $this->type = gp_test_input( @$userdata['type'] );
        }

        if ( @$args[ 'context' ] === 'by_brand' ) {
            // ensure brand was found in database
            if ( $this->brand->get( 'slug' ) ) {
                $this->context = 'by_brand';
            } else {
                $this->context = 'invalid';
            }
        }

        if ( @$args['context'] === 'by_type' ) {
            if ( $this->type ) {
                $this->context = 'by_type';
            } else {
                $this->context = 'invalid';
            }
        }

        if ( @$args['context'] === 'by_size' ) {
            if ( @$userdata['width'] && @$userdata['profile'] && @$userdata['diameter'] ) {
                $this->context = 'by_size';
            } else {
                $this->context = 'invalid';
            }
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
	 * @param $userdata
	 *
	 * @return array
	 */
	public function query_products( $userdata ) {

		switch ( $this->context ) {

			case 'by_vehicle':
				$sizes = $this->vehicle->fitment_object->export_sizes();
				// $sizes = $this->vehicle->fitment_plural->export_sizes();
				return query_tires_by_sizes( $sizes, $userdata );

			case 'by_size':
				$sizes = array();
				$sizes[] = get_tire_size_array_from_userdata( $userdata );
				return query_tires_by_sizes( $sizes, $userdata );

			case 'by_brand':

			    // the value might already be equal to this.
                $userdata['brand'] = $this->brand->get( 'slug' );
                return query_tires_grouped_by_model( $userdata );

			case 'by_type':
				return query_tires_grouped_by_model( $userdata );

			default:
				return array();
		}
	}

    /**
     * @param $key
     * @param bool $results_are_grouped
     * @return bool|mixed|string|null
     */
	public function get_dynamic_filter_value_from_loop_data( $key, $results_are_grouped = false ) {

		// very important to return null, not an empty string
		$add = null;

		if ( $results_are_grouped ) {

			switch ( $key ) {
				case 'tire_brand':
					$add = $this->loop_brand->get( 'slug' );
					break;
				// currently not a filter but might be one day
				case 'tire_model':
					$add = $this->loop_model->get( 'slug' );
					break;
				case 'tire_type':
					$add = $this->loop_model->type->get( 'slug' );
					break;
				case 'tire_class':
					$add = $this->loop_model->class->get( 'slug' );
					break;
				case 'tire_category':
					$add = $this->loop_model->category->get( 'slug' );
					break;
			}

		} else {

			switch ( $key ) {
				case 'tire_brand':
					$add = $this->loop_front_tire->get( 'brand_slug' );
					break;
				case 'tire_model':
					$add = $this->loop_front_tire->get( 'model_slug' );
					break;
				case 'tire_speed_rating':
					$add = $this->loop_front_tire->get( 'speed_rating' );
					break;
				case 'tire_load_index':
					$add = $this->loop_front_tire->get( 'load_index' );
					break;
				case 'tire_type':
					$add = $this->loop_front_tire->model->type->get( 'slug' );
					break;
				case 'tire_class':
					$add = $this->loop_front_tire->model->class->get( 'slug' );
					break;
				case 'tire_category':
					$add = $this->loop_front_tire->model->category->get( 'slug' );
					break;
				case 'tire_price':
					$cents = $this->loop_front_tire->get_price_cents();
					$add   = $cents * 4;
					break;
				case 'tire_price_each':
					$cents = $this->loop_front_tire->get_price_cents();
					$add   = $cents;
					break;
			}
		}

		return $add;
	}

    /**
     * @param bool $by_form_name
     * @return array
     */
	public function get_allowed_filters( $by_form_name = false ){

		$ret = array();

		switch ( $this->context ) {
			case 'by_vehicle':
				$ret[] = 'tire_type';
				$ret[] = 'tire_brand';
				// $ret[] = 'tire_model';
				$ret[] = 'tire_class';
				$ret[] = 'tire_category';
				$ret[] = 'tire_load_index';
				$ret[] = 'tire_speed_rating';
				if ( $this->vehicle->fitment_object->wheel_set->get_selected()->is_staggered() ) {
                    // for set of 4
					$ret[] = 'tire_price';
				} else {
                    // single tire price
					$ret[] = 'tire_price_each';
				}

				break;
			case 'by_size':
                $ret[] = 'tire_type';
				$ret[] = 'tire_brand';
				$ret[] = 'tire_class';
				$ret[] = 'tire_category';
				$ret[] = 'tire_load_index';
				$ret[] = 'tire_speed_rating';
                // single tire price
				$ret[] = 'tire_price_each';
				break;
			case 'by_brand':
				$ret[] = 'tire_type';
				$ret[] = 'tire_class';
				$ret[] = 'tire_category';
				break;
			case 'by_type':
				$ret[] = 'tire_brand';
				$ret[] = 'tire_class';
				$ret[] = 'tire_category';
				break;
			default:
				break;
		}

		return $ret;
	}
}
