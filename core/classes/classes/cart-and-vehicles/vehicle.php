<?php

/**
 * A general vehicle class. It does a lot of things, unlike @var Cart_Vehicle.
 *
 * Class Vehicle
 */
Class Vehicle {

	public $make;
	public $model;
	public $year;
	public $trim;

	/**
	 * active fitment size slug
	 *
	 * @var bool|mixed
	 */
	public $fitment_slug;

	/**
	 * active substitution size slug
	 *
	 * @var
	 */
	public $sub_slug;

	public $make_name;
	public $model_name;
	public $year_name;
	public $trim_name;

	// an array of (all) fitment data, indexed by slug
	// we almost certainly dont need to store this data in the class, but
	// we need it to create the $fitment object, so since we have it we may as well store it for now.
	public $fitment_data_arr_ext;

	// repeated data from $fitment_data for easy access
	public $fitment_names;

	/** @var  Fitment_Singular */
	public $fitment_object;

	/** @var Fitment_Plural */
	public $fitment_plural;

	/**
	 * This probably just means that either make, model, year, or trim was empty.
	 * We could add a check to make sure year is numeric for example.
	 *
	 * @var bool
	 */
	public $required_params_empty;

	/**
	 * If fitment data is not found, it could mean that the vehicle doesn't exist, or that
	 * the wheel size API just doesn't have any fitment data for it. We can't really distinguish very easily.
	 *
	 * @var bool
	 */
	public $fitment_data_found;

	/**
	 * Get a list of trims from make, model, year (from the API and/or database cache).
	 * If the trim provided is in the list of trims returned, then we know that all 4
	 * properties are valid: make, model, year, trim. If the trim is not found, then we know
	 * that one of the 4 is not correct. It doesn't matter which one of the 4, we just ask the user
	 * to re-select their vehicle.
	 *
	 * @var  bool
	 */
	public $trim_not_found;

	/**
	 * Vehicle constructor.
	 *
	 * @param $data
	 */
	public function __construct( $data ) {

		$this->make         = gp_if_set( $data, 'make' );
		$this->model        = gp_if_set( $data, 'model' );
		$this->year         = gp_if_set( $data, 'year' );
		$this->trim         = gp_if_set( $data, 'trim' );
		$this->fitment_slug = gp_if_set( $data, 'fitment' );
		$this->sub_slug     = gp_if_set( $data, 'sub' );

		// setup empty defaults which should be overriden
		$this->trim_not_found        = true;
		$this->required_params_empty = true;
		$this->fitment_data_found    = false;
		$this->fitment_names         = array();
		$fitment_data_arr_ext        = array();

		// check only make, model, year, trim, but not fitment.
		// also be aware that we may __construct() this function based on data that we cannot see
		// and when it doesn't need to be constructed. Therefore, this check is very important to
		// prevent API calls that would slow down the loading of the page, and have a 100% chance
		// of returning an error.
		if ( $this->make && $this->model && $this->year && $this->trim ) {
			$this->required_params_empty = false;
		} else {
			return;
		}

		// this validates that make, model, year, and trim are all valid.
		// if false, then we don't know what went wrong, but we can simply prompt the user
		// to re-select their vehicle.
		$trims = get_trims( $this->make, $this->model, $this->year );

		if ( $this->trim && $trims && in_array( $this->trim, array_keys( $trims ) ) ) {
			$this->trim_not_found = false;
		} else {
			return;
		}

		// if we get to here, trim exists, so fill in display names for make, model, and trim
		$makes           = get_makes();
		$this->make_name = gp_if_set( $makes, $this->make, $this->make );
		$this->make_name = gp_test_input( $this->make_name );

		$models           = get_models( $this->make );
		$this->model_name = gp_if_set( $models, $this->model, $this->model );
		$this->model_name = gp_test_input( $this->model_name );

		$this->trim_name = gp_if_set( $trims, $this->trim, $this->trim );
		$this->trim_name = gp_test_input( $this->trim_name );

		$this->year_name = gp_test_input( $this->year );

		// get_fitment_data will check database cache, and if not found, will hit the API.
		// so once again, be mindful of when you are running this code, and don't hit the API on page load
		// if its not needed.
		try {

			$fitment_data_arr_ext = get_fitment_data( $this->make, $this->model, $this->year, $this->trim );
			if ( $fitment_data_arr_ext ) {
				$this->fitment_data_found = true;
			}
		} catch ( Exception $e ) {
			$this->fitment_data_found = false;
			$fitment_data_arr_ext     = array();
		}

		/**
		 * inject vehicle, @see Fitment_General->vehicle.
		 */
		$fitment_data_arr_ext[ 'vehicle' ] = $this->get_simple_vehicle_instance();

		// remember that a fitment plural is a single object that has many wheel sets
		$this->fitment_plural = new Fitment_Plural( $fitment_data_arr_ext );

		// get the selected wheel set from within $fitment_data_arr_ext, and we'll construct a
		// "singular" fitment object, which contains only one set of wheels
		$this->fitment_names = get_fitment_names_from_fitment_data( $fitment_data_arr_ext );

		$all_wheels             = gp_if_set( $fitment_data_arr_ext, 'wheels', array() );
		$singular_wheel_set_arr = gp_if_set( $all_wheels, $this->fitment_slug );

		// remove the array of all wheels, and replace with our own array of just one set of wheels
		$singular_fitment_data = $fitment_data_arr_ext;
		if ( isset( $singular_fitment_data[ 'wheels' ] ) ) {
			unset( $singular_fitment_data[ 'wheels' ] );
		}

		$singular_fitment_data[ 'wheel_set' ] = $singular_wheel_set_arr;

		// add the 'suggested' user input of $_GET['sub']
		// it might be a totally invalid string at this point. Fitment object
		// will have to take care of that.
		if ( $this->sub_slug ) {
			$singular_fitment_data[ 'sub_slug' ] = $this->sub_slug;
		}

		/**
		 * inject vehicle, @see Fitment_General->vehicle.
		 */
		$singular_fitment_data[ 'vehicle' ] = $this->get_simple_vehicle_instance();

		$this->fitment_object = new Fitment_Singular( $singular_fitment_data );
	}

	/**
	 * @return string
	 */
	public function get_fitment_slug() {
		if ( $this->trim_exists() && $this->fitment_object ) {
			return $this->fitment_object->get_fitment_slug();
		}

		return '';
	}

	/**
	 * @param $fitment_slug
	 *
	 * @return null|Wheel_Set
	 */
	public function get_wheel_set_via_fitment_slug( $fitment_slug ) {

		if ( $this->fitment_plural && $this->fitment_plural->wheels ) {

			/** @var Wheel_Set $wheel_set */
			foreach ( $this->fitment_plural->wheels as $wheel_set ) {

				if ( $wheel_set->get_slug() == $fitment_slug ) {
					return $wheel_set;
				}
			}
		}

		return null;
	}

	/**
	 * pass around a vehicle with just the make/model/year/trim info and possibly fitment slug
	 * and sub slug, but definitely not all fitment information which is very large.
	 */
	public function get_simple_vehicle_instance() {
		$ret = new Cart_Vehicle( $this->complete_vehicle_summary_array( true, true ) );

		return $ret;
	}

	/**
	 * @return string
	 */
	public function get_sub_slug() {
		if ( $this->trim_exists() && $this->fitment_object ) {
			return $this->fitment_object->get_sub_slug();
		}

		return '';
	}

	/**
	 * @return bool
	 */
	public function trim_exists() {
		return ! ( $this->trim_not_found );
	}

	/**
	 * $user_input is likely $_GET,
	 * Sanitizes data and returns an instance of self if the data is valid.
	 * In case its not already obvious, we ignore all data inside of $user_input other than
	 * the data we're interested in.
	 *
	 * @param $user_input
	 *
	 * @return null|Vehicle
	 */
	public static function create_instance_from_user_input( $user_input ) {

		// this user input going straight to html (hence, gp_test_input)
		$make    = get_user_input_singular_value( $user_input, 'make' );
		$model   = get_user_input_singular_value( $user_input, 'model' );
		$year    = get_user_input_singular_value( $user_input, 'year' );
		$trim    = get_user_input_singular_value( $user_input, 'trim' );
		$fitment = get_user_input_singular_value( $user_input, 'fitment' );
		$sub     = get_user_input_singular_value( $user_input, 'sub' );

		// we just go right ahead and make the vehicle even though some params may be empty
		// the error checking functions inside the object will let us know whats not correct
		$v = new self( array(
			'make' => $make,
			'model' => $model,
			'year' => $year,
			'trim' => $trim,
			'fitment' => $fitment,
			'sub' => $sub,
		) );

		return $v;
	}

	/**
	 * Returns true if the API has data based on make/model/year/trim
	 */
	public function exists() {

		// these values may be null
		if ( $this->required_params_empty ) {
			return false;
		}

		if ( $this->trim_not_found ) {
			return false;
		}

		return true;
	}

	/**
	 * Basically, this is true if $_GET['fitment'] is not empty AND $_GET['fitment'] is valid.
	 *
	 * Note: this does not check validity of the vehicle, only that we have a valid fitment object.
	 * One day, we may want to create Vehicle or Fitment instances with not-real vehicles.
	 */
	public function selected_fitment_exists() {
		if ( $this->fitment_object && $this->fitment_object->wheel_set && $this->fitment_object->wheel_set->slug ) {
			return true;
		}

		return false;
	}

	/**
	 * A complete vehicle has a valid make, model, year, trim, fitment, at least
	 * one set of wheels in fitment data, and the fitment slug matches the fitment data.
	 * For most operations you want to ensure the vehicle is "complete", but there are some scenarios
	 * where the vehicle object won't be complete, but is still valid for some operations.
     *
     * @return bool
     */
	public function is_complete() {

		if ( ! $this->exists() ) {
			return false;
		}

		if ( ! $this->has_fitment_data() ) {
			return false;
		}

		if ( ! $this->selected_fitment_exists() ) {
			return false;
		}

		return true;
	}

	/**
	 * This might be identical to selected_fitment_exists() but that's fine. if "a" selected fitment,
	 * emphasis on singular, exists, it means it should have one wheel set. Therefore,
	 * selected_fitment_exists basically means $this->has_wheel_set(). But, in case they change
	 * in the future, I'll still use 2 functions for this.
	 */
	public function has_wheel_set() {
		if ( $this->fitment_object && $this->fitment_object->wheel_set && $this->fitment_object->wheel_set->slug ) {
			return true;
		}

		return false;
	}

	/**
	 * Can check this before directly accessing $this->fitment_object->wheel_set->wheel_set_sub, to ensure
	 * you don't get an error, and also because its possible the user didn't select a sub size.
	 */
	public function has_substitution_wheel_set() {

		if ( $this->has_fitment_data() && $this->fitment_object ) {
			return $this->fitment_object->has_substitution_wheel_set();
		}

		return false;
	}

	/**
	 * Avoid checking if the vehicle exists() first.
	 *
	 * @return bool
	 */
	public function has_fitment_data() {

		// these values may be null
		if ( ! $this->fitment_data_found ) {
			return false;
		}

		return true;
	}

	/**
	 * If fitment is not valid, we'll have to ask the user to select a new fitment.
	 * Keep in mind to also check is $this->fitment_data is empty.
	 * In that case, you'll want to show a different message.
	 */
	public function is_fitment_valid() {

		if ( ! $this->fitment_object ) {
			return false;
		}

		// this should be an indicator that $this->fitment_object was instantiated with an empty array
		if ( ! in_array( $this->fitment_slug, array_keys( $this->fitment_plural->wheels ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function has_errors() {

		if ( $this->required_params_empty ) {
			return true;
		}

		if ( $this->trim_not_found ) {
			return true;
		}

		return false;
	}

	/**
	 * Shows a potential error with the vehicle object. Some return values only indicate errors in particular contexts.
	 */
	public function get_single_error_type() {
		$type = '';
		if ( $this->required_params_empty || $this->trim_not_found ) {
			$type = 'no_vehicle';
		} else if ( $this->trim_not_found ) {
			$type = 'vehicle_error';
		} else if ( ! $this->fitment_data_found ) {
			$type = 'no_fitment_data';
		} else if ( ! $this->is_fitment_valid() ) {
			$type = 'fitment_invalid';
		}

		return $type;
	}

    /**
     * Map error type to description in case we need to show the error. We might just show new selection form instead.
     *
     * @param $type
     * @return bool|mixed
     */
	public function get_general_error_message( $type, $default = '' ) {
		$map = array(
			'no_vehicle' => 'No vehicle selected',
			'vehicle_error' => 'Invalid Vehicle',
			'no_fitment_data' => 'This vehicle has no fitment data',
			'fitment_invalid' => 'Invalid fitment size',
		);

		return gp_if_set( $map, $type, $default );
	}

	public function get_slugs(){
	    return [
	        gp_test_input( $this->make ),
            gp_test_input( $this->model ),
            gp_test_input( $this->year ),
            gp_test_input( $this->trim ),
            gp_test_input( $this->fitment_slug ),
            gp_test_input( $this->sub_slug )
        ];
    }

    /**
     * Check is_complete() first, or else you might not get what you expect.
     *
     * @param bool $allow_fitment
     * @param bool $allow_sub
     * @return array
     */
	public function complete_vehicle_summary_array( $allow_fitment = true, $allow_sub = true ) {

		$ret            = array();
		$ret[ 'make' ]  = gp_test_input( $this->make );
		$ret[ 'model' ] = gp_test_input( $this->model );
		$ret[ 'year' ]  = gp_test_input( $this->year );
		$ret[ 'trim' ]  = gp_test_input( $this->trim );

		if ( $allow_fitment && $this->fitment_object ) {
			if ( $this->fitment_object->has_wheel_set() ) {
				$ret[ 'fitment' ] = gp_test_input( $this->fitment_object->wheel_set->slug );
			}
		}

		if ( $allow_sub && $this->fitment_object ) {
			if ( $this->fitment_object->has_substitution_wheel_set() ) {
				$ret[ 'sub' ] = gp_test_input( $this->fitment_object->wheel_set->wheel_set_sub->slug );
			}
		}

		return $ret;
	}

    /**
     * @param bool $with_fitment
     * @param bool $with_trim
     * @return string
     */
	public function get_display_name( $with_fitment = false, $with_trim = true ) {

		$trim_name = $with_trim ? $this->trim_name : '';

		$ret = get_vehicle_display_name( $this->make_name, $this->model_name, $this->year_name, $trim_name);

		if ( $with_fitment ) {
            $ret .= ' ' . gp_test_input( $this->get_fitment_name() );
		}

		// trim redundant i think
		return trim( $ret );
	}

	/**
	 *
	 */
	public function get_fitment_name() {

		if ( $this->fitment_object && $this->fitment_object->has_wheel_set() ) {

			if ( $this->fitment_object->wheel_set->get_selected()->is_sub() ) {
				$ret = $this->fitment_object->wheel_set->wheel_set_sub->get_name( true );
			} else {
				$ret = $this->fitment_object->wheel_set->get_name();
			}

			return $ret;

		}

		return '';
	}

    /**
     * Array keys are "sub slugs" and array values are "sub names".
     *
     * The array keys aren't useful on their own until you add them as arguments to a URL.
     *
     * @param bool $include_plus_minus
     * @return array
     */
	public function get_sub_size_select_options( $include_plus_minus = true ) {

		$ret = array();

		// the vehicle must have a wheel set, which means the same as, a user has selected their fitment.
		if ( ! $this->has_wheel_set() ) {
			return $ret;
		}

		// even if a user has selected their fitment, sub sizes might not be applicable here.
		// its assumed that $this->fitment_object->wheel_set->generate_all_wheel_set_subs() was called long before now.
		if ( ! $this->fitment_object->wheel_set->all_wheel_set_subs ) {
			return $ret;
		}

		foreach ( $this->fitment_object->wheel_set->all_wheel_set_subs as $ws ) {

			// the slug and name should always both exist
			$ret[ $ws->get_slug() ] = $ws->get_name( $include_plus_minus );
		}

		return $ret;
	}

    /**
     * $args['base_url'] needs to be URL. &sub= will get added to it.
     *
     * @param array $args
     * @return string
     */
	public function render_sub_size_select( $args = array() ) {

		$base_url = gp_if_set( $args, 'base_url' );
		$on_white  = gp_if_set( $args, 'on_white', false );
		$height_sm = gp_if_set( $args, 'height_sm', false );

		// array of sub_slug => sub_name
		$sub_size_items = $this->get_sub_size_select_options( true );

		// should be empty if user has not selected a sub size
		$current_sub_slug = $this->fitment_object->get_sub_slug();

		$items         = [];
		$current_value = '';

		// assemble an array whose array keys are URLs containing all vehicle data including sub slugs
		if ( $sub_size_items ) {
			foreach ( $sub_size_items as $sub_slug => $sub_name ) {

				$_url = cw_add_query_arg( array(
					'sub' => $sub_slug,
				), $base_url );

				if ( $current_sub_slug && $sub_slug == $current_sub_slug ) {
					$current_value = $_url;
				}

				$items[ $_url ] = $sub_name;
			}
		}

		$count = count( $items );

		// add the placeholder <option>, but in such a a way that <option value="">
		// becomes the URL without the sub size applied.
		$_items = array_merge( [
            $base_url => $count . ' Substitution Sizes',
		], $items );

		$add_class_1   = [ 'sub-size-select' ];
		$add_class_1[] = 'count- ' . $count;

		$add_class_2   = [];
		$add_class_2[] = $height_sm ? 'height-sm' : ''; // makes less tall
		$add_class_2[] = $on_white ? 'on-white' : ''; // for single product page we need a box shadow

		$html = '<div class="select-vehicle-size-wrapper type-sub">';

		$html .= get_form_select( array(
			'add_class_1' => gp_parse_css_classes( $add_class_1 ),
			'add_class_2' => gp_parse_css_classes( $add_class_2 ),
			'select_class' => 'href-on-change',
			'label' => '',
			'name' => 'no_name',
			'select_2' => true,
		), array(
			// 'placeholder' => '+/- Sizes (' . $count . ')', // put in $_items instead
			'items' => $_items,
			'current_value' => $current_value,
		) );

		$html .= '</div>';

		return $html;
	}

    /**
     * @param $page
     * @param array $args
     * @return string
     */
	public function render_vehicle_sub_nav( $page, $args = array() ) {

		$op = '';

		// css classes (indicate current item)
		$cls_tires = [ 'item' ];
		$cls_rims  = [ 'item' ];
		$cls_pkgs  = [ 'item' ];

		// if the user specified a type filter on the tires page, pass that in, so we can
        // select the same type on the packages page.
		$package_type = gp_if_set( $args, 'package_type', null );

		if ( ! is_tire_type_valid( $package_type ) ) {
		    $package_type = null;
        }

		// $page is the page that we're on, not the page we're linking to.
		switch ( $page ) {
			case 'tires':
				$cls_tires[] = 'current';
				break;
			case 'rims':
				$cls_rims[] = 'current';
				break;
			case 'packages':
				$cls_pkgs[] = 'current';
				break;
		}

        // note: .left-only not styled on responsive yet. Might not be used.
		$show_sub_sizes = gp_if_set( $args, 'show_sub_sizes', true );
		$cls = [ 'vehicle-sub-nav', $show_sub_sizes ? '' : 'left-only' ];

		$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';

		$op .= '<div class="sub-nav-flex">';

		$op .= '<div class="sub-nav-left">';

		$car_icon = '<i class="fa fa-car"></i>';

		$text_tires = '<span>' . $car_icon . ' Tires</span>';
		$text_rims  = '<span>' . $car_icon . ' Wheels</span>';
		$text_pkgs  = '<span>' . $car_icon . ' Packages</span>';

		$tires_url = get_vehicle_archive_url( 'tires', $this->get_slugs() );
        $rims_url = get_vehicle_archive_url( 'rims', $this->get_slugs() );
        $pkgs_url = get_vehicle_archive_url( 'packages', $this->get_slugs(), [
            '_type' => $package_type
        ] );

		$op .= '<div class="' . gp_parse_css_classes( $cls_tires ) . '">';
		$op .= '<a href="' . $tires_url . '">' . $text_tires . '</a>';
		$op .= '</div>';

		$op .= '<div class="' . gp_parse_css_classes( $cls_rims ) . '">';
		$op .= '<a href="' . $rims_url . '">' . $text_rims . '</a>';
		$op .= '</div>';

		$op .= '<div class="' . gp_parse_css_classes( $cls_pkgs ) . '">';
		$op .= '<a href="' . $pkgs_url . '">' . $text_pkgs . '</a>';
		$op .= '</div>';

		$op .= '</div>';

		if ( $show_sub_sizes ) {
            $op .= '<div class="sub-nav-right">';

            $op .= '<div class="item change-size">';

            $op .= $this->render_sub_size_select( [
                'base_url' => get_vehicle_archive_url( $page, array_slice( $this->get_slugs(), 0, 5 ) ),
                'height_sm' => true,
            ] );

            $op .= '</div>';

            $op .= '</div>';

            $op .= '</div>'; // sub-nav-flex
        }

		$op .= '</div>'; // vehicle-sub-nav

		return $op;
	}

	/**
	 * When you instantiate a vehicle, you may want to call this to store a users previous
	 * vehicle selection.
	 */
	public function track_in_session_history() {

		if ( $this->trim_not_found ) {
			return;
		}

		$make            = gp_test_input( $this->make );
		$model           = gp_test_input( $this->model );
		$year            = gp_test_input( $this->year );
		$trim            = gp_test_input( $this->trim );
		$fitment         = gp_test_input( $this->fitment_slug );
		$session_slug    = implode( '_', array( $make, $model, $year, $trim, $fitment ) );
		$vehicle_history = gp_if_set( $_SESSION, 'vehicle_history' );

		if ( isset( $vehicle_history[ $session_slug ] ) ) {
			return;
		} else {
			$_SESSION[ 'vehicle_history' ][ $session_slug ] = array(
				// store the name into session so we dont have to make a new vehicle instance for each history item when printing
				// (as this would be VERY inefficient with large numbers of vehicles)
				'name' => $this->get_display_name( true ),
				'make' => $make,
				'model' => $model,
				'year' => $year,
				'trim' => $trim,
				'fitment' => $fitment,
			);
		}

	}

	/**
	 * This may be for development only...
	 */
	public static function render_session_history_urls() {

		$vehicle_history = gp_if_set( $_SESSION, 'vehicle_history' );

		$base_urls = array(
			'Tires' => get_url( 'tires' ),
			'Rims' => get_url( 'rims' ),
			'Packages' => get_url( 'packages' ),
		);

		$op = '';

		if ( $vehicle_history ) {
			foreach ( $vehicle_history as $slug => $data ) {

				$vehicle_html = '';

				$name    = gp_if_set( $data, 'name' );
				$make    = gp_if_set( $data, 'make' );
				$model   = gp_if_set( $data, 'model' );
				$year    = gp_if_set( $data, 'year' );
				$trim    = gp_if_set( $data, 'trim' );
				$fitment = gp_if_set( $data, 'fitment' );

				$vehicle_html .= '<p>';
				$vehicle_html .= $name . ': ';

				foreach ( $base_urls as $name => $url ) {
					$full_url     = cw_add_query_arg( array(
						'make' => $make,
						'model' => $model,
						'trim' => $trim,
						'year' => $year,
						'fitment' => $fitment,
					), $url );
					$vehicle_html .= '<a href="' . $full_url . '">' . $name . '</a> ';
				}

				$vehicle_html .= '</p>';
				$op           .= trim( $vehicle_html );
			}
		}

		return $op;
	}

	/**
	 * @param $thing
	 */
	public static function create_instance_from_something( $thing ) {

		if ( ! $thing ) {
			return null;
		}

		if ( $thing instanceof Vehicle ) {
			return $thing;
		}

		$vehicle = self::create_instance_from_user_input( $thing );
		if ( $vehicle->is_complete() ) {
			return $vehicle;
		}

		return null;
	}
}

/**
 * @param $make_name
 * @param $model_name
 * @param $year_name
 * @param $trim_name
 * @return string
 */
function get_vehicle_display_name( $make_name, $model_name, $year_name, $trim_name ) {

    return implode( " ", array_filter( [
        gp_test_input( $year_name ),
        gp_test_input( $make_name ),
        gp_test_input( $model_name ),
        gp_test_input( $trim_name ),
    ] ) );
}