<?php

/**
 * Parent class for all the main archive pages: tires, wheels, packages. (however,
 * not the single tire, or single rim pages). The things these pages have in common:
 * a list of products, a sidebar with filters, a bunch of different contexts, pagination,
 * title by vehicle etc.
 */
Abstract Class Page_Products {

	/**
     * 'tires', 'rims', or 'packages'
	 *
	 * @var string
	 */
    public $class_type;

	/**
	 * @var
	 */
    public $class_type_singular;

	/**
	 * raw, uncleaned user input, $_GET or $_POST.
	 *
	 * @var array
	 */
    public $userdata;

	/**
	 * $_GET['pkg'] if its set (and possibly, if its a valid package ID).
	 * Depending on context, it may be valid to have an 'invalid' pkg specified.
	 *
	 * @var null|string
	 */
    public $package_id;

	/**
	 * @var
	 */
    public $package_exists;

	/**
	 * 'by_vehicle', 'by_size', 'by_brand', 'by_type', 'invalid', etc.
	 *
	 * @var
	 */
	public $context;

    /**
     * @var Vehicle
     */
    public $vehicle;

	/**
	 * Brand object for context 'by_brand'. Different from $this->loop_brand.
	 *
	 * @var DB_Tire_Brand|DB_Rim_Brand
	 */
	public $brand;

	/**
	 * The flex instance to use to build product loop html
	 *
	 * @var Product_Loop_Flex
	 */
	protected $flex;

	/**
	 * Add to cart partial arguments. $atc['items'] to be added on individual "product" basis.
	 *
	 * @var array
	 */
	protected $atc;

	/**
	 * @var boolean
	 */
	protected $loop_staggered;

	/**
     * Raw query results for a single row in the loop
     *
	 * @var stdClass|array
	 */
	protected $loop_data;

	/**
     * Used for all non-grouped queries, ie. those that show products
     * with add to cart buttons, and not tire models or rim finishes.
     *
	 * @var Vehicle_Query_Database_Row
	 */
	protected $loop_vqdr;

	/** @var  DB_Tire|null */
	protected $loop_front_tire;

	/** @var  DB_Tire|null */
	protected $loop_rear_tire;

	/** @var  DB_Rim|null */
	protected $loop_front_rim;

	/** @var  DB_Rim|null */
	protected $loop_rear_rim;

	/**
	 * for grouped results on rims pages only
	 *
	 * used for grouped queries, otherwise this can be found
	 * in $this->loop_front->finish
	 *
	 * @var  DB_Rim_Finish|null
	 */
	public $loop_finish;

	/**
	 * Used in grouped queries only. For queries that return tires or rims individually,
	 * the brand and model object should be found within the rim or tire object.
	 *
	 * @var  DB_Tire_Model|DB_Rim_Model|null
	 */
	protected $loop_model;

	/**
	 * Used in grouped queries only. For queries that return tires or rims individually,
	 * the brand and model object should be found within the rim or tire object.
	 *
	 * @var  DB_Tire_Brand|DB_Rim_Brand|null
	 */
	protected $loop_brand;

    /**
     * The selected front tire when we're choosing different rims
     * for a package.
     *
     * @var DB_Tire|null
     */
    protected $tire_1;

    /**
     * The selected rear tire when we're choosing different rims
     * for a package.
     *
     * @var DB_Tire|null
     */
    protected $tire_2;

	/**
     * The selected front rim when we're choosing different tires
     * for a package.
     *
     * @var  DB_Rim|null
     */
	protected $rim_1;

	/**
     * The selected rear rim when we're choosing different tires
     * for a package.
     *
     * @var  DB_Rim|null
     */
	protected $rim_2;

	/**
	 * Sub-context for when $this->context === 'by_vehicle'
	 *
	 * @var string
	 */
	protected $sub_context;

	/**
	 * @var
	 */
	protected $ajax_action;

    /**
     * all rows returned from database
     *
     * @var
     */
    protected $query_results;

	/**
     * The total number of results found in the query (for all pages).
     *
     * This used to not be redundant at one point. But upon adding dynamic
     * filters, we had to remove the sql limit clause, and therefore,
     * this is always === count( $this->query_results )
     *
	 * @var
	 */
	protected $found_rows;

	/**
	 * The number of products to show on the current page, taking
     * into account pagination.
     *
	 * @var
	 */
	protected $product_count;

	/**
	 * @var array
	 */
	protected $filters_data = array();

	/**
     * The cart package associated with $this->userdata['pkg'], I think.
     *
     * @var  Cart_Package
     */
	protected $cart_package;

	/**
	 * the rows to render based on 'per_page' and 'page' indexes of
	 * $this->userdata
	 *
	 * @var
	 */
	protected $query_results_to_render;

	/**
	 * ie. [ 'tire_type' => [ 'summer', 'winter' ], 'tire_brand' => [ 'sailun', 'mirage' ] ]
	 *
	 * @var
	 */
	protected $dynamic_filters;

    /**
     * 'img', 'overlay_opacity', 'title', 'header_tag', 'tagline', 'right_col_html', etc.
     *
     * @var
     */
    public $top_image_args = [];

	/**
	 * @var DB_Page|null
	 */
	public $db_page;

	/**
	 * Note: get's setup rather late. Don't access too early.
	 *
	 * @var Pagination_Stuff|null
	 */
	public $pagination;

    /**
     * Page_Products constructor.
     * @param array $userdata
     * @param array $args
     * @param Vehicle|null $vehicle
     * @throws Exception
     */
	public function __construct( $userdata = array(), $args = array(), $vehicle = null ) {

		if ( ! $this->class_type ) {
			throw new Exception( 'Class type is required to be set before calling the parent constructor.' );
		}

		// maybe make this an input argument at some point
		if ( Router::$current_db_page ) {
		    $this->db_page = Router::$current_db_page;
        }

		// store raw user input. we sanitize when we use it.
		$this->userdata = $userdata;

		if ( ! isset( $this->userdata['per_page'] ) ) {
		    $this->userdata['per_page'] = get_per_page_preference( 'front_end', 18 );
        }

        $this->vehicle = $vehicle ? $vehicle : new Vehicle([]);

		// check args for brand object
		$brand_obj = gp_if_set( $args, 'brand', null );

		if ( $brand_obj instanceof DB_Tire_Brand || $brand_obj instanceof DB_Product_Brand ) {
			$this->brand = $brand_obj;
		} else {
			// do your own logic in child object constructor (check $this->brand !== null, then maybe set it up)
		}

		// clean user input 'pkg' and set $this->package_id. no reason to make sure the package is valid.
		// when adding to cart, if the package_id is not a cart package, we will make a new one anyways.
		$pkg = get_user_input_singular_value( $userdata, 'pkg', null );
		if ( $pkg ) {
			$this->package_id     = $pkg;
			$cart                 = get_cart_instance();
			$this->package_exists = $cart->package_exists( $pkg ) ? true : false;
		} else {
			$this->package_exists = false;
		}

		$this->atc          = get_add_to_cart_partial_args( $this->vehicle, $this->package_id, array() );
		$this->filters_data = $this->get_filters_data();

        // setup default sort.
        if ( ! isset( $this->userdata[ 'sort' ] ) ) {
            list( $sort_items, $default_sort_by ) = $this->get_sort_by_options();
            $this->userdata[ 'sort' ] = $default_sort_by;
        }
	}

	public function is_tire(){
	    return $this->class_type === 'tires';
    }

    public function is_rim(){
	    return $this->class_type === 'rims';
    }

    public function is_pkg(){
	    return $this->class_type === 'packages';
    }

	public function get_value_via_class_type( $if_tires = null, $if_rims = null, $if_packages = null) {
	    switch( $this->class_type ) {
	        case 'tires':
	            return $if_tires;
            case 'rims':
	            return $if_rims;
            case 'packages':
                return $if_packages;
	    }
	}

    /**
     * Builds the array for $this->top_image_args
     *
     * @return array
     * @throws Exception
     */
	public function build_top_image_args(){

        $tires_wheels_pkgs = $this->get_value_via_class_type( 'Tires', 'Wheels', 'Packages' );

        $ret = [];

        // always h2
        $ret['header_tag'] = 'h2';

        // global default top image title.
        $ret['title'] = $tires_wheels_pkgs;

        /**
         * Setup images
         */

        if ( $this->is_tire() ) {
            $ret['img'] = 'tire-top.jpg';
        }

        if ( $this->is_rim() ) {
            $ret[ 'img' ] = 'iStock-123201626-wide-lg-2.jpg';
            $ret[ 'overlay_opacity' ] = 50;
            $ret[ 'img_tag' ] = true;
            $ret[ 'alt' ] = "Click It Wheels for wheels and tires canada";
        }

        if ( $this->is_pkg() ) {
            $ret['img'] = 'pkg-1.jpg';
        }

        /**
         * Titles and Taglines
         */

        if ( $this->context === 'by_brand' ) {
            $ret['title'] = "$tires_wheels_pkgs By Brand";
            $ret['tagline'] = $this->get_brand_name();
        }

        if ( $this->context === 'by_size' ) {

            $ret['title'] = "$tires_wheels_pkgs By Size";

            if ( $this->is_tire() ) {
                $ret['tagline'] = $this->get_tire_size_string();
            }
        }

        if ( $this->context === 'by_type' ) {
            if ( $this->is_tire() ) {
                $ret['title'] = 'Tires By Type';
                $ret['tagline'] = $this->get_tire_type_slug_and_name(false)[1];
            }
        }

        /**
         * Setup the vehicle lookup form in the top image.
         */
        $ret['right_col_html'] = call_user_func( function(){


            // if ( $this->context === 'by_vehicle' ) {}

            // by_brand/by_size applies to tires/rims, by_type is tires only
            if ( in_array( $this->context, [ 'by_brand', 'by_size', 'by_type' ] ) ) {

                $args = [];

                if ( $this->is_tire() ) {

                    $args['do_tires'] = true;
                    $args['tires_by_size_args'] = [];

                    if ( $this->context === 'by_size' ) {
                        $args['heading'] = get_top_image_vehicle_lookup_heading( true, false, false );
                    } else {
                        $args['heading'] = get_top_image_vehicle_lookup_heading( true, true, false );
                    }
                } else {
                    $args['heading'] = get_top_image_vehicle_lookup_heading( false, false, false );
                }

                if ( $this->context === 'by_brand' ) {
                    // this will make the brand filter pre-selected on the product archive page.
                    // actually, don't do this because it's quite likely no results
                    // will be found. In the case of no results, the sidebar filters are also
                    // hidden, so the person cannot unselect the brand, which is bad.
//                    $args['hidden_inputs'] = [
//                        'url_args' => [
//                            '_brand' => $this->brand->get( 'slug' )
//                        ]
//                    ];
                }

                $args['vehicle_lookup_args'] = [
                    'vehicle' => null,
                    'page' => $this->get_value_via_class_type( 'tires', 'rims' ),
                    'hide_shop_for' => true,
                ];

                return get_top_image_vehicle_lookup_form( $args );
            }
        });

        return $ret;
    }

    /**
     * Grabs the 'type' from user input, then returns an array of length
     * 2 or 3, containing the type slug, type name, and the icon HTML.
     *
     * ie. [ 'all-season', 'All Season' ]
     *
     * It's best to omit the icon if you don't need it.
     *
     * The tire type is validated (can only be one of 4 options) and
     * the returned slug and name are sanitized.
     *
     * Note that a lot of code that could use this function was written before it,
     * and does not use it.
     *
     * When on the tires by type page, $index is 'type', but whenever it is otherwise
     * used as a filter option, you use '_type' instead. This includes packages,
     * tires by brand, tires by size.
     *
     * @param bool $and_icon
     * @param string $index
     * @return array
     */
    public function get_tire_type_slug_and_name( $and_icon = false, $index = 'type' ){

	    $type = get_user_input_singular_value( $this->userdata, $index );

	    if ( is_tire_type_valid( $type ) ) {
	        if( $and_icon ) {
                return [ $type, get_tire_type_name( $type ), get_tire_type_icon( $type ) ];
            } else {
                return [ $type, get_tire_type_name( $type ) ];
            }
        }

        if( $and_icon ) {
            return [ '', '', '' ];
        } else {
            return [ '', '' ];
        }
    }

    /**
     * Note that if Router::current_db_page is set, then these values can be overriden.
     * This can only occur for certain page types (tire/rim by brand, and tire by type)
     */
    public function setup_meta_titles_etc(){

        /**
         * Dynamic pages exist for tire brands, rim brands, tire types, and
         * each of the 3 landing pages. Whenever a dynamic page exists, we'll
         * pull the meta title from the database, so whatever is below could
         * be overriden (when context is by_brand, by_type or landing).
         */

        // is one of the 3
        $tires_wheels_pkgs = $this->get_value_via_class_type( 'Tires', 'Wheels', 'Packages' );

        // tires/rims/packages
        if ( $this->context === 'by_vehicle' ) {

            $_vehicle = $this->vehicle->get_display_name( false, false );

            Header::$title = meta_title_add_company_branding( "$tires_wheels_pkgs - $_vehicle" );
        }

        // tires/rims
        if ( $this->context === 'by_brand' ) {

            $_brand = $this->get_brand_name();

            Header::$title = meta_title_add_company_branding( "$_brand $tires_wheels_pkgs" );
        }

        // tires/rims
        if ( $this->context === 'by_size' ) {

            if ( $this->is_tire() ) {

                $_size = $this->get_tire_size_string();

                // ie. 225/50R18 Tires
                Header::$title = meta_title_add_company_branding( "$_size Tires" );
            }

            if ( $this->is_rim() ) {

                Header::$title = meta_title_add_company_branding( "Wheels By Size" );
            }
        }

        // tires only
        if ( $this->context === 'by_type' ) {
            if ( $this->is_tire() ) {
                $_type = $this->get_tire_type_slug_and_name( false )[ 1 ];
                Header::$title = meta_title_add_company_branding( "$_type Tires" );
            }
        }

        if ( $this->context === 'landing' ) {

            if ( $this->is_tire() ) {
                Header::$title = meta_title_add_company_branding( "Browse Tires by Vehicle, Brand, Type, or Size" );
            }

            if ( $this->is_rim() ) {
                Header::$title = meta_title_add_company_branding( "Browse Wheels by Vehicle, Brand, or Size" );
            }

            if ( $this->is_pkg() ) {
                Header::$title = meta_title_add_company_branding( "Browse Tire and Wheel Packages for your Vehicle" );
            }
        }
    }

	/**
	 * @return mixed
	 */
	abstract protected function get_filters_data();

	/**
	 * @var Page_Products_Filters_Methods will override this.
	 *
	 * @return mixed
	 */
	abstract protected function render_filter( $filter );

    /**
     * @return array
     */
	public function get_userdata_for_filters(){

	    if ( $this->class_type === 'packages' ) {
	        $ret = $this->userdata;
	        list ( $types, $type ) = $this->get_package_type_options_and_selected();
	        $ret['type'] = $type;
	        return $ret;
        }

	    return $this->userdata;
    }

	/**
	 * @return bool
	 */
	public function context_new_package() {
		return $this->context === 'by_vehicle' && $this->sub_context === 'new_pkg';
	}

	/**
	 *
	 */
	public function get_clear_filters_link() {

        if ( $this->class_type === 'tires' || $this->class_type === 'rims' ) {

            if ( $this->context === 'by_size' ) {
                if ( $this->class_type === 'tires' ) {
                    return get_tire_size_url( $this->userdata['width'], $this->userdata['profile'], $this->userdata['diameter'] );
                } else {
                    return Router::build_url( [ 'wheels', 'by-size' ] );
                }
            }

            // only for class type tires
            if ( $this->context === 'by_type' ) {
                return Router::build_url( [ 'tires', $this->userdata['type'] ] );
            }

            if ( $this->context === 'by_brand' ) {
                return Router::build_url( [ $this->class_type === 'tires' ? 'tires' : 'wheels', $this->brand->get( 'slug' ) ] );
            }

            if ( $this->context === 'by_vehicle' ) {
                return get_vehicle_archive_url( $this->class_type, $this->vehicle->get_slugs() );
            }

        }

		if ( $this->class_type === 'packages' ) {

            $query = [];

            foreach ( [ 'pkg', 'tire_1', 'tire_2', 'rim_1', 'rim_2' ] as $opt ) {
                if ( gp_test_input( @$this->userdata[$opt] ) ) {
                    $query[$opt] = $this->userdata[$opt];
                }
            }

            return get_vehicle_archive_url( 'packages', $this->vehicle->get_slugs(), $query );
		}
	}

	/**
	 * @return array - [ $item, $default_sort_by ]
	 */
	public function get_sort_by_options() {

        $items = [];
        $default_sort_by = '';

		$brand_model_price = function ( $input = array() ) {
			$input[ 'price' ] = "Sort by Price";
			$input[ 'brand' ] = "Sort by Brand";
			$input[ 'model' ] = "Sort by Model";

			return $input;
		};

		switch ( $this->class_type ) {
			case 'packages':

				// since we dont show a sort by option we can just leave this blank...
				// the query will determine the default sort by which should be price.
				$default_sort_by = '';
				switch ( $this->context ) {
					case 'by_vehicle':
						// don't know if we'll need this
						break;
				}

				break;
			case 'tires':

				switch ( $this->context ) {
					case 'by_vehicle':
						$default_sort_by = 'price';
                        $items             = $brand_model_price( [] );
						break;
					case 'by_brand':
						$default_sort_by = 'price';
                        $items[ 'price' ]  = "Sort by Price";
                        $items[ 'model' ]  = "Sort by Model";
						break;
					case 'by_type':
						$default_sort_by = 'price';
                        $items             = $brand_model_price( [] );
						break;
					case 'by_size':
						$default_sort_by = 'price';
                        $items             = $brand_model_price( [] );
						break;
				}

				break;
			case 'rims':

				switch ( $this->context ) {
					case 'by_vehicle':
						$default_sort_by = 'price';
                        $items             = $brand_model_price( [] );
						break;
					case 'by_brand':
						$default_sort_by = 'price';
                        $items[ 'price' ]  = "Sort by Price";
                        $items[ 'model' ]  = "Sort by Model";
						break;
					case 'by_size':
						$default_sort_by = 'price';
                        $items             = $brand_model_price( [] );
						break;
				}

				break;
		}

		return [ $items, $default_sort_by ];
	}

	/**
	 *
	 */
	public function render_sort_by() {

	    // we don't need the default here, it's already been added to $this->userdata['sort']
		list( $items, $default ) = $this->get_sort_by_options();

		if ( ! $items ) {
			return '';
		}

		$cls = [ 'product-sort-by' ];
		$op  = '';

		$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';
		$op .= '<div class="select-2-wrapper on-white height-sm">';
		$op .= '<select name="sort" id="product-sort">';

		$op .= get_select_options( array(
			'placeholder' => 'Sort By',
			'items' => $items,
			'current_value' => get_user_input_singular_value( $this->userdata, 'sort', '' )
		) );

		$op .= '</select>';
		$op .= '</div>'; // select-2-wrapper
		$op .= '</div>'; // product-sort-by

		return $op;
	}

	/**
	 *
	 */
	public function render_product_count() {

		if ( $this->product_count === null || $this->found_rows === null ) {
			throw_dev_error( 'Product count and found rows required even if zero.' );
		}

		if ( $this->product_count === 0 ) {
			return '';
		}

		$page     = gp_if_set( $this->userdata, 'page' );
		$per_page = gp_if_set( $this->userdata, 'per_page' );
		$data     = get_product_showing_counts( $page, $per_page, $this->found_rows );

		$min   = (int) gp_if_set( $data, 0 );
		$max   = (int) gp_if_set( $data, 1 );
		$total = (int) gp_if_set( $data, 2 );

		$cls   = array( 'product-counts' );
		$cls[] = $total === 0 ? 'empty' : 'not-empty';

		$op = '';
		$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';
		$op .= '<p>Showing ' . $min . ' - ' . $max . ' of ' . $total . '</p>';
		$op .= '</div>';

		return $op;
	}

	/**
	 * Takes in $this->userdata, and returns possibly the same array, or a modified version of it.
	 * Primarily, we can use this to alter pagination attributes. Therefore, $this->userdata does not
	 * get sent to queries, only the return value of this function which is an array
	 */
	public function pre_filter_userdata_for_querying_products() {
		$ret               = $this->userdata;
		$ret[ 'per_page' ] = ''; // PHP pagination now
		$ret[ 'page' ]     = '';

		return $ret;
	}

    /**
     * @return string
     */
	public function get_canonical_url() {
		/**
		 * Notes...
		 *
		 * For each page that we expect search engines to get to (ie. not vehicle searches),
		 * we'll add a canonical that points back to page 1 of the results without sort by options...
         *
		 */
		if ( $this->class_type === 'packages' ) {
		    // landing page
            return Router::get_url( 'packages' );
        }

        if ( $this->class_type === 'rims' ) {

            if ( $this->context === 'by_vehicle' ) {
                // landing page
                return Router::get_url( 'wheels' );
            }

            if ( $this->context === 'by_brand' ) {
                return Router::build_url( [ 'wheels', $this->brand->get( 'slug' ) ]);
            }

            if ( $this->context === 'by_size' ) {
                return Router::get_url( 'rim_size' );
            }

        }

        if ( $this->class_type === 'tires' ) {

            if ( $this->context === 'by_vehicle' ) {
                // landing page
                return Router::get_url( 'tires' );
            }

            if ( $this->context === 'by_brand' ) {
                return Router::build_url( [ 'tires', $this->brand->get( 'slug' ) ]);
            }

            if ( $this->context === 'by_type' ) {
                return Router::build_url( [ 'tires', $this->userdata['type'] ] );
            }

            if ( $this->context === 'by_size' ) {
                return get_tire_size_url( $this->userdata['width'], $this->userdata['profile'], $this->userdata['diameter'] );
            }
        }
	}

	/**
	 * Ajax replaces this
	 */
	public function render_loop_and_pagination() {

		$op = '';

		$cls = [ 'product-loop-wrapper' ];

		// Product count is VISIBLE products based on the page number we are on
		$this->product_count = count( $this->query_results_to_render );

		if ( $this->product_count ) {
			$this->found_rows = count( $this->query_results );
			$cls[]            = 'not-empty';
		} else {
			$this->found_rows = 0;
			$cls[]            = 'empty';
		}

		// after $this->found_rows
		$this->pagination = new Pagination_Stuff( $this->get_page_number(), $this->get_per_page(), $this->found_rows );

		if ( $this->product_count && $this->found_rows > $this->product_count ) {
			$cls[] = 'paginated';
		} else {
			$cls[] = 'not-paginated';
		}

		// product-loop-wrapper
		$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';

		// switch left with right ??
		if ( $this->context === 'by_vehicle' ) {
		} else {
		}

		if ( $this->class_type === 'packages' ) {

			$right = $this->render_product_count();
			// possibly empty for packages
			$left = $this->render_sort_by();

		} else {

			if ( $this->context === 'by_vehicle' ) {
				$left  = $this->render_sort_by();
				$right = $this->render_product_count();
			} else {

				$sort_by            = $this->render_sort_by();
				$product_count_html = $this->render_product_count();

				if ( $sort_by ) {
					$left  = $product_count_html;
					$right = $sort_by;
				} else {
					$left  = '';
					$right = $product_count_html;
				}
			}
		}

		$op .= '<div class="before-loop">';

		// always keep this container here even if empty so that justify-content space between puts the right on the right
		$op .= '<div class="before-loop-left">';
		$op .= $left;
		$op .= '</div>';

		$op .= '<div class="before-loop-right">';
		$op .= $right;
		$op .= '</div>';
		$op .= '</div>'; // before-loop

		// the main loop
		$op .= $this->render_loop( $this->query_results_to_render );

		$op .= '<div class="after-loop">';
		$op .= $this->render_pagination( $this->pagination );
		$op .= '</div>';

		$op .= '<div>'; // product-loop-wrapper

		return $op;
	}

	/**
	 * Override this. Make sure to return an array, and setup
	 * the 2 required class properties.
	 *
	 * @see Page_Packages::query_products()     *
	 * @see Page_Rims::query_products()
	 * @see Page_Tires::query_products()
	 */
	public function query_products( $userdata ) {
		$this->product_count = 0;
		$this->found_rows    = 0;

		return array();
	}

	/**
	 *
	 */
	public function render_main_content() {

		$op = '';
		$op .= $this->render_title();

		// the button trigger for this is buried within $this->render_title()
		if ( $this->class_type === 'tires' && $this->context === 'by_size' ) {
			$op .= '<div class="lb-content lb-content-change-tire-size" data-lightbox-class="change-tire-size" data-lightbox-id="change_tire_size" data-close-btn="1">';
			$op .= tires_by_size_form( array(
				'title' => 'Tires By Size',
			), $this->userdata );
			$op .= '</div>';
		}

		// the button trigger for this is buried within $this->render_title()
		if ( $this->class_type === 'rims' && $this->context === 'by_size' ) {
			$op .= '<div class="lb-content lb-content-change-rim-size" data-lightbox-class="change-rim-size" data-lightbox-id="change_rim_size" data-close-btn="1">';
			$op .= get_rims_by_size_form( array(
				'title' => 'Wheels By Size',
			), $this->userdata );
			$op .= '</div>';
		}

		$op .= $this->render_loop_and_pagination();

        // Lower content for tires and wheels catalogues
        if ( $this->db_page ) {
            $lowerDesc = gp_render_textarea_content( get_page_meta( $this->db_page->get_id(), 'lower_desc' ) );
            if ($lowerDesc) {
                // adding a class to add a bit of spacing to this
                $op .= html_element( $lowerDesc, 'div', 'archive-lower-desc general-content' );
            }
        }

		return $op;
	}

    /**
     * @param Pagination_Stuff $pg
     * @return string
     */
	public function render_pagination( Pagination_Stuff $pg ) {

		$op = '';
		$op .= '<div class="pagination-section">';

		$op .= '<div class="pagination-wrap">';
		if ( $pg->should_do_pagination() ) {
			$op .= get_pagination_html( 1, $pg->last_page, $pg->page_num );
		}
		$op .= '</div>';

		$op .= '<div class="per-page-wrap">';

		$pp = array(
			'12' => '12 Items Per Page',
			'18' => '18 Items Per Page',
            '24' => '24 Items Per Page',
			'36' => '36 Items Per Page',
			'48' => '48 Items Per Page',
		);

		if ( cw_is_admin_logged_in() ) {
			$pp[ '10000' ] = '10000 (admin only)';
		};

		// for 1st param, @see get_valid_per_page_preference_contexts()
		$op .= get_per_page_options_html( 'front_end', $pp, gp_test_input( $this->userdata['per_page'] ) );

		$op .= '</div>';

		$op .= '</div>';

		return $op;
	}

	/**
	 * @return bool|int|mixed
	 */
	public function get_per_page() {
	    return (int) gp_test_input( $this->userdata['per_page'] );
	}

	/**
	 *
	 */
	public function get_page_number() {
		$page = gp_if_set( $this->userdata, 'page' );
		$page = $page && $page > 0 ? $page : 1;

		return (int) $page;
	}

	/**
	 *
	 */
	public function setup_query_results_to_render() {

		$per_page = $this->get_per_page();
		$page     = $this->get_page_number();

		$offset = ( $page * $per_page ) - $per_page;

		// in case of StdClass object
		$rr = array_slice( $this->query_results, $offset, $per_page );

		//		echo '<pre>' . print_r( $per_page, true ) . '</pre>';
		//		echo '<pre>' . print_r( $page, true ) . '</pre>';
		//		echo '<pre>' . print_r( $offset, true ) . '</pre>';
		//		echo '<pre>' . print_r( count( $this->query_results), true ) . '</pre>';
		//		echo '<pre>' . print_r( count( $rr), true ) . '</pre>';

		$this->query_results_to_render = $rr;
	}

	/**
	 * Override in child class.
	 */
	public function get_allowed_filters() {
		return array();
	}

	/**
	 * Override in child class.
	 *
	 * For example, if $key is 'tire_brand', all you need to do is return the tire brand
	 * slug for the current loop item. The keys correspond precisely to filter slugs, or the return
	 * values from $this->get_allowed_filters().
	 *
	 * In addition, this function should take into account all possible $filter_slug's that your
	 * class can return from get_allowed_filters().
	 *
     * @param $filter_slug
     * @param bool $grouped_by_model - applies to Page_Tires & Page_Rims only
     * @return null
     */
	public function get_dynamic_filter_value_from_loop_data( $filter_slug, $grouped_by_model = false ) {
		return null; // null must be the default value
	}


    /**
     * @param $filter_slug
     * @param $raw_data_cents
     * @return array
     */
	public function combine_price_filter_options( $filter_slug, $raw_data_cents ) {

		// we know the method will exist once its being used,
		// it just doesn't exist in this class due to hierarchy
		if ( method_exists( $this, 'pre_filter_a_filter_before_rendering' ) ) {
			// the filter array should now have an index $filters['items']
			// 2nd param false is kind of very important
			$filter = $this->pre_filter_a_filter_before_rendering( $filter_slug, false );
		} else {
			$filter = array();
		}

		$items = gp_if_set( $filter, 'items', array() );
		$items = gp_force_array( $items );
		$keys  = array_keys( $items );
		$ret   = array();

		if ( $keys && $keys ) {
			foreach ( $keys as $str ) {

				$arr = parse_price_range_string_in_cents( $str );

				if ( ! $arr ) {
					continue;
				}

				$min = $arr[ 0 ];
				$max = $arr[ 1 ];

				$count = sum_array_values_from_keys_within_range( $min, $max, $raw_data_cents );

				// ie. $ret['rim_price_each']['10000-19999'] = 6;
				// 6 rims between $100.00 and $199.99
				$ret[ $str ] = $count;
			}
		}

		// an example return value:
		// ie. 8 products under $99.99 etc.
		//		$eg = array(
		//			'0-9999' => 8,
		//			'10000-19999' => 12,
		//		);

		return $ret;
	}

	/**
	 * Override in child class.
	 */
	public function setup_dynamic_filters() {

		$ret             = array();
		$allowed_filters = $this->get_allowed_filters();

		// do this outside of loop
		$grouped_by_model = $this->are_results_grouped();

		if ( $this->query_results ) {

			// initialize an empty array index for each filter being printed
			if ( $allowed_filters ) {
				foreach ( $allowed_filters as $filter_slug ) {
					$ret[ $filter_slug ] = array();
				}
			}

			foreach ( $this->query_results as $row ) {

				// this is a bit of a time sink, and takes about .04 seconds per 400-ish results
				// however, we don't have much of a choice. setup_loop_data is the way
				// we map different sql columns in query results, to the same class properties.
				$this->setup_loop_data( $row );

				if ( $allowed_filters ) {
					foreach ( $allowed_filters as $key ) {

						// this is the only thing you need to do in your child class
						$add = $this->get_dynamic_filter_value_from_loop_data( $key, $grouped_by_model );

						// add to product counts
						if ( $add !== null ) {
							if ( ! isset( $ret[ $key ][ $add ] ) ) {
								$ret[ $key ][ $add ] = 0;
							}
							$ret[ $key ][ $add ] ++;
						}

					}
				}
			} // foreach

			// make sure this goes inside "if results"
			$price_filter_slugs = array(
				'rim_price_each',
				'rim_price',
				'tire_price_each',
				'tire_price',
				'package_price',
			);

			// foreach price filter slug, we need to transform the results of $ret[$filter_slug]
			// The original value is indexed by product prices, and valued by their counts.
			// this would be great if we wanted to list every single unique price as an option to filter by.
			// but we don't... instead we have price ranges, so we need to "sort" all unique prices
			// into pre-set price ranges defined by our filters.
			if ( $price_filter_slugs ) {
				foreach ( $price_filter_slugs as $filter_slug ) {
					if ( in_array( $filter_slug, $allowed_filters ) ) {
						$v = gp_if_set( $ret, $filter_slug );
						// must override $ret[$filter_slug] even if with an empty value
						$ret[ $filter_slug ] = $this->combine_price_filter_options( $filter_slug, $v );
					}
				}
			}

		} // if

		// setup the return value
		$this->dynamic_filters = $ret;

		// clear loop data left over from the last row in the query results
		// its technically redundant but I prefer to keep it anyways
		$this->setup_loop_data( array() );
	}

	/**
     * Query products, sort them maybe, determine which ones to display, and setup
     * dynamic filters.
	 */
	public function pre_render() {

		// before filters, loop, etc.
		$this->query_results = $this->query_products( $this->pre_filter_userdata_for_querying_products() );

		// this is a new thing where we show in stock products at the top
        // for all non-grouped searches, ie. tires/rims/packages by vehicle and tires by size.
		if ( ! $this->are_results_grouped() ) {

		    // query param for myself if I need to test things... normal users will never use this.
		    if ( ! @$_GET['__no_order_by_stock'] ) {
                $this->query_results = $this->order_products_by_stock( $this->query_results );
            }
        }

		// after querying
		$this->setup_query_results_to_render();

		// dynamic filters
		start_time_tracking( 'setup_dynamic_filters' );
		$this->setup_dynamic_filters();
		queue_dev_alert( 'setup_dynamic_filters  ' . end_time_tracking( 'setup_dynamic_filters' ), get_pre_print_r( $this->dynamic_filters ) );
	}


    /**
     * Accepts an array of stdClass objects and returns the sorted record
     * set in the same format.
     *
     * Sorts products into 3 categories. First show in stock items,
     * then low stock (this can mean diff. things), then out of stock
     * items. The items are already ordered ie. by price or whatever
     * the user specified. This maintains that order within each category.
     *
     * Possible in SQL but more straight forward to do this in PHP.
     *
     * The sorting logic is extremely simple and fast. However, calling
     * $this->build_loop_data() on the query results is not ideal for performance,
     * because we just throw the results out after, and it's done when setting
     * up dynamic filters and then when rendering products. Not optimizing
     * this unless justified.
     *
     * @param array $query_results
     * @return array
     */
	public function order_products_by_stock( array $query_results ){

	    $in_stock = [];
	    $partial_stock = [];
	    $no_stock = [];

	    foreach ( $query_results as $row ) {

	        $obj = $this->build_loop_data( $row );

	        /** @var Vehicle_Query_Database_Row $v */
	        $v = $obj->loop_vqdr;

	        $min_stock = $v->get_minimum_stock_amount();

	        if ( $min_stock === STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING || $min_stock >= 4 ) {
	            $in_stock[] = $row;
            } else if ( $min_stock > 0 ) {

	            if ( $v->is_staggered() || $v->is_pkg() ) {

	                // item_set_is_purchasable() is how we enforce either 2 or 4 of each product
                    // for staggered fitments depending on whether the rear uses the same part number
                    // as the front (happens on rims sometimes).
                    // for packages, whether staggered or not, we just require the full set of products
                    // to be able to be added to the cart. Otherwise, show them at the end of the list.
                    // no intermediate $partial_stock for staggered or packages.
	                if ( $v->item_set_is_purchasable() ) {
	                    $in_stock[] = $row;
                    } else {
	                    $no_stock[] = $row;
                    }

                } else {

	                // for non-staggered, non-packages we have the 3rd category consisting
                    // of 1-3 products at a time.
	                $partial_stock[] = $row;
                }
            } else {
	            $no_stock[] = $row;
            }
        }

	    return array_merge( $in_stock, $partial_stock, $no_stock );
    }

    /**
     * @return string
     */
	public function render() {

		// query and do everything we need to do BEFORE rendering pretty much anything
		$this->pre_render();

		// start html
		$op = '';
		$op .= '<div class="' . gp_parse_css_classes( $this->get_css_classes() ) . '">';

        $op .= get_top_image( $this->top_image_args );

		$op .= Components::grey_bar();

		if ( $this->class_type === 'tires' || $this->class_type === 'packages' ) {
            $op .= get_us_tire_inventory_lightbox_alert( $this->vehicle );
        }

		$op .= '<div class="main-content">';
		$op .= '<div class="wide-container">';
		$op .= '<div class="wide-container-inner">';

		// sidebar stuff
		$sidebar            = new Sidebar_Container();
		$sidebar->add_class = 'product-sidebar-container';
		$sidebar->add_class .= $this->query_results_to_render ? '' : ' results-empty';
		$sidebar->left      = $this->render_sidebar_content();
		$sidebar->right     = $this->render_main_content();
		$op                 .= $sidebar->render();

		$op .= '</div>'; // wide-container-inner
		$op .= '</div>'; // wide-container
		$op .= '</div>'; // main-content
        $op .= '</div>';

		return $op;
	}

    /**
     * @param $query
     * @return string|null
     */
	public function render_loop( $query ) {

        if ( $this->class_type === 'tires' ) {
            $this->flex = new Product_Loop_Flex_Tires();
        }

        if ( $this->class_type === 'rims' ) {
            $this->flex = new Product_Loop_Flex_Rims();
        }

        if ( $this->class_type === 'packages' ) {
            $this->flex = new Product_Loop_Flex_Packages();
        }

		if ( $this->are_results_grouped() ) {
			$this->flex->add_css_class( 'grouped-results' );
		}

		if ( $query && is_array( $query ) ) {

			foreach ( $query as $row ) {
				$this->setup_loop_data( $row );
				$this->flex->add_item_raw_html( $this->render_item() );
			}

            return $this->flex->render();

		} else {

            return $this->handle_no_results();
        }
	}

	/**
	 * @param string $default
	 *
	 * @return bool|mixed|string
	 */
	public function get_tire_type_name_from_userdata( $default = '' ) {

		$type = gp_if_set( $this->userdata, 'type' );

		if ( is_tire_type_valid( $type ) ) {
			$name = get_tire_type_name( $type, $default );
		} else {
			$name = $default;
		}

		return $name;
	}

	/**
	 * When we run a query but get no results back. OR maybe we wont use this
	 */
	public function handle_no_results() {

		// grab some data from flex instance so we don't repeat ourselves...
		// javascript relies on these selectors so its important they are correct even
		// when no results are found.
		$cls = $this->flex->classes;

		// not .no-results
		$cls[] = 'results-none';

		$op = '';

		// $this->flex->id is probably "product-loop"
		$op .= '<div id="' . $this->flex->id . '" class="' . gp_parse_css_classes( $cls ) . '">';

        $op .= '<div class="no-results">';

        if ( $this->class_type === 'packages' ) {

            // this might be _type on packages as a filter but type when tires by type
            $type = gp_if_set( $this->userdata, '_type' );

            $type_name = $this->get_tire_type_slug_and_name( false )[1];

            if ( $type_name ) {
                $op    .= '<p>No ' . $type_name . ' packages found, please try a different type.</p>';
            } else {

                // can't get to here I don't think, because a valid type is required.
                $op .= '<p>No Results Found, please try a different package type.</p>';
            }

        } else {
            $op .= '<p>No Results Found.';
        }

        $op .= '</div>';
		$op .= '</div>';

		return $op;
	}

    /**
     * @param $row
     * @return stdClass
     */
	public function build_loop_data( $row ){

	    $ret = new stdClass();

        if ( $this->class_type === 'tires' ) {

            // grouped queries (brand and type). Unlike rims, tires by size is not a grouped query.
            if ( in_array( $this->context, array( 'by_brand', 'by_type' ) ) ) {

                $ret->loop_data  = $row;
                $ret->loop_model = DB_Tire_Model::create_instance_or_null( $row );
                $ret->loop_brand = DB_Tire_Brand::create_instance_or_null( $row );

                return $ret;
            }

            $data = Tires_Query_Fitment_Sizes::parse_row( $row );

            $ret->loop_staggered  = gp_if_set( $data, 'staggered' );
            $ret->loop_front_tire = gp_if_set( $data, 'front' );
            $ret->loop_rear_tire  = gp_if_set( $data, 'rear' );
            $ret->loop_data       = gp_if_set( $data, 'raw_data' );
            $ret->loop_vqdr       = Vehicle_Query_Database_Row::create_instance_from_products( $ret->loop_front_tire, $ret->loop_rear_tire, null, null );

        } else if ( $this->class_type === 'rims' ) {

            // grouped queries (brand, and size)
            if ( in_array( $this->context, array( 'by_brand', 'by_size' ) ) ) {
                $ret->loop_data = $row;

                $ret->loop_model = DB_Rim_Model::create_instance_or_null( $row );
                $ret->loop_brand = DB_Rim_Brand::create_instance_or_null( $row );

                // doing another db query for finishes. not ideal but hardly makes a difference in the end.
                $ret->loop_finish = DB_Rim_Finish::create_instance_via_primary_key( gp_if_set( $row, 'finish_id' ) );

                return $ret;
            }

            $data = Rims_Query_Fitment_Sizes::parse_row( $row );

            $ret->loop_staggered = gp_if_set( $data, 'staggered' );
            $ret->loop_front_rim = gp_if_set( $data, 'front' );
            $ret->loop_rear_rim  = gp_if_set( $data, 'rear' );
            $ret->loop_data      = gp_if_set( $data, 'raw_data' );
            $ret->loop_vqdr      = Vehicle_Query_Database_Row::create_instance_from_products( null, null, $ret->loop_front_rim, $ret->loop_rear_rim );

        }

	    return $ret;
    }

	/**
     * Registers the "current" product by setting several
     * mutable class properties of $this, which are read later
     * on.
     *
     * I think mutating all the "loop_" fields like this is
     * totally not a good way to do it, but there's too many usages
     * of them now to re-factor it out.
     *
     * The method below handles tires and rims, but Page_Packages
     * defines its own method instead.
	 *
     * @param $row
     */
	public function setup_loop_data( $row ) {

	    $obj = $this->build_loop_data( $row );

	    foreach ( $obj as $key => $value ) {
	        $this->{$key} = $value;
        }
	}

    /**
     * @return string
     */
	public function render_item() {

		$op = '';

		$item_outer_css_class = gp_if_set( $this->flex, 'item_outer_class' );
		$item_inner_css_class = gp_if_set( $this->flex, 'item_inner_class' );

		$cls   = [];
		$cls[] = $item_outer_css_class;
		$cls[] = 'type-' . $this->class_type_singular;
		$cls[] = $this->loop_staggered ? 'item-staggered' : '';
		$cls[] = $this->are_results_grouped() ? 'item-grouped' : 'not-grouped-by-model';

		if ( $this->loop_vqdr ) {
			$stock_indicator = $this->loop_vqdr->get_item_set_stock_level_indicator();
			switch ( $stock_indicator ) {
				case STOCK_LEVEL_NO_STOCK:
					$cls[] = 'products-in-stock';
					break;
				case STOCK_LEVEL_LOW_STOCK:
					$cls[] = 'products-low-stock';
					break;
				case STOCK_LEVEL_IN_STOCK:
					$cls[] = 'products-no-stock';
					break;
			}
		}

		$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';
		$op .= '<div class="' . $item_inner_css_class . '">';

        $op .= '<div class="pi-top">';

        if ( $this->are_results_grouped() ) {
            $op .= $this->render_item_top_grouped_result();
        } else {
            $op .= $this->render_item_top();
        }

        $op .= '</div>'; // pi-top

        $op .= '<div class="pi-bottom">';

        if ( $this->are_results_grouped() ) {
            $op .= $this->render_item_bottom_grouped_result();
        } else {
            $op .= $this->render_item_bottom();
        }

        $op .= '</div>'; // pi-bottom

		$op .= '</div>'; // flex-item-inner
		$op .= '</div>'; // flex-item-outer

		return $op;
	}

    /**
     * @return string
     */
	public function get_loop_brand_model_name() {

		switch ( $this->class_type ) {

			case 'rims':

				if ( $this->loop_front_rim ) {
					return $this->loop_front_rim->brand_model_finish_name();
				} else {
					return brand_model_finish_name( $this->loop_brand, $this->loop_model, $this->loop_finish );
				}

				break;

			case 'tires':

				if ( $this->loop_front_tire ) {
					return $this->loop_front_tire->brand_model_name();
				} else {
					return brand_model_name( $this->loop_brand, $this->loop_model );
				}

				break;
		}

	}

	/**
	 * For tires/rims only. Page_Packages does its own thing.
	 */
	public function render_product_card_image( $img_url ) {

        $img_url = get_image_src( $img_url );
        $details_url = $this->get_details_button_url();
        $title = $this->get_loop_brand_model_name();

        if ( $this->class_type === 'tires' ) {
            $alt = "$title Tires";
        } else if ( $this->class_type === 'rims' ) {
            $alt = "$title Wheels";
        } else {
            // we technically don't call this function on packages page
            $alt = '';
        }

		ob_start();
		?>
        <div class="img-wrap">
            <div class="img-wrap-2">
                <a title="<?= $title; ?>" href="<?= $details_url; ?>">
                    <div class="img-tag-contain inherit-size">
                        <img src="<?= $img_url ? $img_url : image_not_available(); ?>" alt="<?= $img_url ? $alt : 'Image not found'; ?>">
                    </div>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
	}

	/**
	 * @return string
	 */
	public function render_sidebar_content() {

		$op = '';

		// on tires by brand, we show brand logo.
		// on tires by type... type logo etc.
		// when sub context is "new_pkg", we show the existing part numbers here..
		if ( method_exists( $this, 'render_above_sidebar_filters' ) ) {
			$op .= $this->render_above_sidebar_filters();
		}

		$op .= '<div id="product-sidebar-filters" class="product-sidebar-filters">';

		$op .= '<div class="sidebar-title-wrap">';
		$op .= '<h2 class="title">Filters</h2>';
		$op .= '<a class="clear" href="' . $this->get_clear_filters_link() . '">Clear Filters</a>';
		$op .= '</div>';

		$op .= $this->render_filters();

		$op .= '</div>';

		return $op;
	}

	/**
	 *
	 */
	public function render_filters() {

        $allowed_filters = $this->get_allowed_filters();
		$base        = BASE_URL . Router::get_modified_uri( true );

		// omit page so that when filters change we go back to page 1
		$omit_keys = array_merge( array_keys( $allowed_filters ), [ 'page', 'per_page' ] );
		$print_hidden = array_diff_key( $_GET, array_flip( $omit_keys ) );

        $op = '';
        $op .= '<form id="product-filters" action="' . gp_test_input( $base ) . '" method="get" class="">';

        $op .= '<div class="page-load-inputs">';
        // note: this only prints non-array values. This is fine for our needs.
        $op .= get_hidden_inputs_from_array( $print_hidden );
        $op .= '</div>';

        foreach ( $allowed_filters as $filter_name ) {
            $op .= $this->render_filter( $filter_name );
        }

        $op .= '</form>';

		return $op;
	}

    /**
     * The main title below the top image.
     *
     * @return string
     */
    public function render_title() {

        $titles = '';

        // sometimes this gets defaulted to winter/summer inside of $this->userdata,
        // but we only want to submit type if its already in the url.
        $package_type = tire_type_is_valid( @$_GET['_type'] ) ? $_GET['_type'] : '';

        switch ( $this->context ) {
            case 'by_vehicle':

                // can either do it here or in the top image.
                $do_vehicle_select = true;

                $titles .= '<div class="vehicle-titles">';

                $_vehicle = gp_test_input( $this->vehicle->get_display_name( false, false ) );
                $_fitment = gp_test_input( $this->vehicle->get_fitment_name() );

                $titles .= '<h2 class="vehicle">' . $_vehicle . '</h2>';

                if ( $do_vehicle_select ) {

                    $titles .= '<div class="fitment-change-vehicle">';

                    $titles .= '<p class="fitment">' . $_fitment . '</p>';

                    $titles .= '<button class="css-reset type-simple change-vehicle lb-trigger" data-for="change_vehicle">[Change Vehicle]</button>';

                    $titles .= '</div>';

                    $change_vehicle_lightbox_content = get_change_vehicle_lightbox_content( 'change_vehicle', [
                        'title' => 'Change Vehicle',
                        'hide_shop_for' => true,
                        'page' => $this->class_type,
                        'hidden_inputs' => $package_type ? [ 'url_args' => [ '_type' => $package_type ]] : [],
                    ], $this->vehicle );

                } else {
                    $titles .= '<p class="fitment">' . $_fitment . '</p>';
                }

                $titles .= '</div>';

                $titles .= $this->vehicle->render_vehicle_sub_nav( $this->class_type, [
                    'package_type' => $package_type,
                    'show_sub_sizes' => true,
                ] );


                break;
            case 'by_brand':

                $tires_wheels = $this->get_value_via_class_type( 'Tires', 'Wheels' );
                $_brand = $this->brand->get( 'name', '', true );

                $titles .= '<div class="general-titles">';

                // ie. 720Form Wheels, or Sailun Tires
                $titles .= '<h1 class="main">' . $_brand . ' ' . $tires_wheels . '</h1>';

                if ( $this->db_page ) {
                    $archive_desc = gp_render_textarea_content( get_page_meta( $this->db_page->get_id(), 'archive_desc' ) );
                    if ($archive_desc) {
                        $titles .= html_element( $archive_desc, 'div', 'general-content' );
                    }
                }

                $titles .= '</div>';

                break;
            case 'by_size':

                $titles .= '<div class="general-titles">';

                if ( $this->is_tire() ){

                    // lightbox content will exist outside of this div (regardless of printing title btn here)
                    $change_size         = '<button class="css-reset extra lb-trigger" data-for="change_tire_size">[change size]</button>';
                    $size           = $this->get_tire_size_string();
                    $title = "$size $change_size";

                    $titles .= '<h1 class="main">' . $title . '</h1>';
                }

                if ( $this->is_rim() ){

                    // lightbox content will exist outside of this div (regardless of printing title btn here)
                    $change_size         = '<button class="css-reset extra lb-trigger" data-for="change_rim_size">[change size]</button>';
                    $title = "Wheels By Size $change_size";

                    $titles .= '<h1 class="main">' . $title . '</h1>';
                }

                $titles .= '</div>';

                break;
            case 'by_type':

                $titles .= '<div class="general-titles">';

                // context by_type is only for tires right now.
                if ( $this->is_tire() ){

                    $type = get_user_input_singular_value( $this->userdata, 'type' );
                    $type_name = get_tire_type_name( $type ) . ' Tires';
                    $icon = get_tire_type_icon( $type );

                    $text_and_icon = get_text_and_icon_html( $type_name, $icon, "type-$type" );

                    $titles .= '<h1 class="main">' . $text_and_icon . '</h1>';

                    if ( $this->db_page ) {
                        $archive_desc = gp_render_textarea_content( get_page_meta( $this->db_page->get_id(), 'archive_desc' ) );
                        if ($archive_desc) {
                            $titles .= html_element( $archive_desc, 'div', 'general-content' );
                        }
                    }
                }

                $titles .= '</div>';

                break;
            case 'invalid':
            default:
                // we don't get to here.
                break;
        }

        $ret = '<div class="page-title-section">';
        $ret .= $titles;
        $ret .= '</div>';

        if ( isset( $change_vehicle_lightbox_content ) ) {
            $ret .= $change_vehicle_lightbox_content;
        }

        return $ret;
    }

    /**
	 * Ie. "Winter Tires"
	 *
	 * We might put this in some titles. See also render_title_by_type()
	 * which does a similar thing but does not use this function.
	 *
	 * @return string
	 */
	public function get_tires_by_type_text_without_icon() {

		$type = get_user_input_singular_value( $this->userdata, 'type' );

		$name = get_tire_type_name( $type );
		$name = $name . ' Tires';

		return $name;
	}

	/**
	 * @return string
	 */
	public function get_tire_size_string() {

		if ( $this->class_type !== 'tires' ) {
			return '';
		}

		$width    = get_user_input_singular_value( $this->userdata, 'width' );
		$profile  = get_user_input_singular_value( $this->userdata, 'profile' );
		$diameter = get_user_input_singular_value( $this->userdata, 'diameter' );

		$ret = $width . '/' . $profile . 'R' . $diameter;

		return $ret;
	}

	/**
	 * Not sure if we need to show this or not... has a lot of parameters..
	 *
	 * @return string
	 */
	public function get_rim_size_string() {

		if ( $this->class_type !== 'rims' ) {
			return '';
		}

		if ( $this->context !== 'by_size' ) {
			return '';
		}

		return '';
	}

	/**
	 * Only when context is 'by_brand'
	 *
	 * @return bool|mixed|string
	 */
	public function get_brand_name() {
	    if ( $this->context === 'by_brand' ) {
	        return gp_test_input( ampersand_to_plus( $this->brand->get( 'name', '', false ) ) );
        }
	}

	/**
	 * @return array
	 */
	public function get_css_classes() {

		$cls = array();

		if ( $this->class_type === 'tires' ) {
			$cls = array(
				'page-wrap',
				'products-page',
				'products-archive-page', // distinguish between products-landing-page
				'tires-page',
			);

			$cls[] = 'context-' . $this->context;
		}

		if ( $this->class_type === 'rims' ) {
			$cls = array(
				'page-wrap',
				'products-page',
				'products-archive-page', // distinguish between products-landing-page
				'rims-page',
			);

			$cls[] = 'context-' . $this->context;
		}

		if ( $this->class_type === 'packages' ) {
			$cls = array(
				'page-wrap',
				'products-page',
				'products-archive-page', // distinguish between products-landing-page
				'packages-page',
			);

			$cls[] = 'context-' . $this->context;
		}

		return $cls;
	}

	/**
	 *
	 */
	protected function render_product_to_package_with() {

		$object_front = $this->class_type === 'tires' ? $this->rim_1 : $this->tire_1;
		$object_rear  = $this->class_type === 'tires' ? $this->rim_2 : $this->tire_2;

		$details = array();
		$details = array_merge( $details, $object_front->summary_array() );

		// ->loop_staggered *should* be the same as this.. but I prefect to check the vehicle object according to the page here.
		if ( $this->vehicle->fitment_object->wheel_set->get_selected()->is_staggered() ) {
			$details = array_merge( $details, $object_rear->summary_array( 'rear_' ) );
		}

		return gp_render_details( $details );
	}

	/**
	 * @return string
	 */
	public function render_above_sidebar_filters() {

		if ( $this->context === 'by_brand' ) {

			$logo_url = '';

			if ( $this->class_type === 'rims' ) {
				$logo_url = $this->brand->get_logo();
			} else if ( $this->class_type === 'tires' ) {
				$logo_url = $this->brand->get_logo();
			}

			if ( $logo_url ) {

				$op = '';
				$op .= '<div class="above-filters-brand-logo">';
				$op .= '<div class="background-image contain" style="' . gp_get_img_style( $logo_url ) . '"></div>';
				$op .= '</div>';

				return $op;

			}
		}

		if ( $this->context_new_package() ) {
			return $this->render_product_to_package_with();
		}
	}

    /**
     * Each child class defines this themselves.
     *
     * @return string
     */
	public function render_item_top(){

	    if ( $this->class_type === 'tires' ) {

            $op = '';
            $op .= '<div class="product-titles">';
            $op .= '<p class="like-h2 brand">' . gp_test_input( $this->loop_front_tire->brand->get( 'name' ) ) . '</p>';
            $op .= '<p class="model">' . gp_test_input( $this->loop_front_tire->model->get( 'name' ) ) . '</p>';
            $op .= '</div>';

            $op .= get_compare_button_add_tire( $this->loop_front_tire->get( 'part_number') );

            $op .= $this->render_product_card_image( $this->loop_front_tire->get_image_url( 'thumb', false ) );

            $op .= get_tire_type_and_class_html( $this->loop_front_tire->model->get( 'type' ), $this->loop_front_tire->model->get( 'class' ) );

            if ( $this->loop_staggered ) {
                $op .= '<div class="spec-tables spec-tires count-2">';
                $op .= spec_table_tires( $this->loop_front_tire, $this->loop_vqdr, VQDR_INT_TIRE_1, 'Front' );
                $op .= spec_table_tires( $this->loop_rear_tire, $this->loop_vqdr, VQDR_INT_TIRE_2, 'Rear' );
                $op .= '</div>'; // spec-tables
            } else {
                $op .= '<div class="spec-tables spec-tires count-1">';
                $op .= spec_table_tires( $this->loop_front_tire, $this->loop_vqdr, VQDR_INT_TIRE_1 );
                $op .= '</div>'; // spec-tables
            }

            return $op;

        } else if ( $this->class_type === 'rims' ) {

            $op = '';
            $op .= '<div class="product-titles">';
            $op .= '<p class="brand">' . gp_test_input( $this->loop_front_rim->brand->get( 'name' ) ) . '</p>';
            $op .= '<p class="model">' . gp_test_input( $this->loop_front_rim->model->get( 'name' ) ) . '</p>';
            $op .= '<p class="finish">' . $this->loop_front_rim->get_finish_string() . '</p>';
            $op .= '</div>';

            $op .= get_compare_button_add_rim( $this->loop_front_rim->get( 'part_number' ) );

            $op .= $this->render_product_card_image( $this->loop_front_rim->get_image_url( 'thumb', false ) );

            $fields = [ 'size', 'offset', 'bolt_pattern', 'price', 'type', 'stock' ];

            if ( $this->loop_staggered ) {

                $op .= '<div class="spec-tables spec-rims count-2">';
                $op .= spec_table_rims( $this->loop_front_rim, $this->loop_vqdr, VQDR_INT_RIM_1, 'Front', $fields, [], $this->vehicle );
                $op .= spec_table_rims( $this->loop_rear_rim, $this->loop_vqdr, VQDR_INT_RIM_2, 'Rear', $fields, [], $this->vehicle );
                $op .= '</div>'; // spec-tables
            } else {
                $op .= '<div class="spec-tables spec-rims count-1">';
                $op .= spec_table_rims( $this->loop_front_rim, $this->loop_vqdr, VQDR_INT_RIM_1, '', $fields, [], $this->vehicle );
                $op .= '</div>'; // spec-tables
            }

            return $op;


        } else {

	        // packages page does its own thing.
	        return '';
        }
    }

    /**
     * Page_Tires and Page_Rims define this themselves.
     *
     * Does not apply to packages.
     *
     * @return string
     */
    public function render_item_top_grouped_result(){

        assert( $this->class_type === 'tires' || $this->class_type === 'rims' );

        $min_price = @$this->loop_data->min_price;
        $_min_price = print_price_dollars_formatted( $min_price );

        $op = '';

        $op .= '<div class="product-titles">';

//        $op .= wrap_tag( @$this->loop_data->max_stock );
//        $op .= wrap_tag( @$this->loop_data->min_stock );

        $op .= '<p class="brand">' . gp_test_input( $this->loop_brand->get( 'name' ) ) . '</p>';
        $op .= '<p class="model">' . gp_test_input( $this->loop_model->get( 'name' ) ) . '</p>';

        if ( $this->class_type === 'rims' ) {
            $op .= '<p class="finish">' . $this->loop_finish->get_finish_string() . '</p>';
        }

        $op .= '<p class="starting-from">Starting From: <span class="sf-price">' . $_min_price . ' (EA)</span></p>';

        if ( $this->class_type === 'rims' ) {

            if ( $this->loop_data->max_stock >= 4 ) {

            } else if ( $this->loop_data->max_stock >= 1 ) {
                $op .= html_element( Stock_Level_Html::render_alt(STOCK_LEVEL_LOW_STOCK, "Low Stock" ), "p", "stock" );
            } else {
                $op .= html_element( Stock_Level_Html::render_alt(STOCK_LEVEL_NO_STOCK, "Out Of Stock" ), "p", "stock" );
            }

        }

        $op .= '</div>';

        if ( $this->class_type === 'tires' ) {
            $op .= $this->render_product_card_image( $this->loop_model->get_image_url( 'thumb', false ) );
        } else {
            $op .= $this->render_product_card_image( $this->loop_finish->get_image_url( 'thumb', false ) );
        }

        if ( $this->class_type === 'tires' ) {
            $op .= get_tire_type_and_class_html( $this->loop_model->type->get(  'slug' ), $this->loop_model->class->get( 'slug' ) );
        }

        return $op;
    }

	/**
	 * for rims and tires, but not for packages.
	 *
	 * @see Page_Packages::render_item_bottom()
	 *
	 * @return string
	 */
	public function render_item_bottom() {

	    assert( $this->class_type === 'tires' || $this->class_type === 'rims' );

		// add to cart data for the button
        $atc = $this->get_add_to_cart_args();

		$details_url = $this->get_details_button_url();

		$stock_level_indicator = $this->loop_vqdr->get_item_set_stock_level_indicator();

        $op = '';

		if ( $stock_level_indicator === STOCK_LEVEL_NO_STOCK ) {

			$op .= '<div class="pi-buttons count-1">';
			$op .= '<div class="pi-button color-black"><a href="' . $details_url . '">Details</a></div>';
			$op .= '</div>'; // pi-buttons

		} else {

			$op .= '<div class="pi-buttons count-2">';
			$op .= '<div class="pi-button color-red"><a href="' . $details_url . '">Details</a></div>';
			$op .= '<div class="pi-button color-black"><button class="ajax-add-to-cart" data-cart="' . gp_json_encode( $atc ) . '">Add To Cart</button></div>';
			$op .= '</div>'; // pi-buttons
		}

		return $op;
	}

    /**
     * @return string
     */
	public function get_details_button_url(){

	    // not for packages
	    assert( $this->class_type === 'rims' || $this->class_type === 'tires' );

	    if ( $this->class_type === 'tires' ) {
            if ( $this->are_results_grouped() ){

                return get_tire_model_url_basic( $this->loop_brand->get( 'slug' ), $this->loop_model->get( 'slug' ) );

            } else {

                $v = $this->loop_vqdr;

                $_vehicle = $this->vehicle && $this->vehicle->is_complete() ? $this->vehicle->get_slugs() : [];

                $part_numbers = [
                    $v->db_tire_1->get( 'part_number' ),
                    $v->is_staggered() ? $v->db_tire_1->get('part_number' ) : null
                ];

                return get_tire_model_url( $v->db_tire_1->get_slugs(), $part_numbers, $_vehicle );
            }
        }

        if ( $this->class_type === 'rims' ) {

            if ( $this->are_results_grouped() ){

                $slugs = [
                    $this->loop_brand->get( 'slug' ),
                    $this->loop_model->get( 'slug' ),
                    $this->loop_finish->get( 'color_1' ),
                    $this->loop_finish->get( 'color_2' ),
                    $this->loop_finish->get( 'finish' ),
                ];

                if ( $this->context === 'by_size' ) {
                    return get_rim_finish_url_by_size( $slugs, $_GET );
                }

                if ( $this->context === 'by_brand' ) {
                    return get_rim_finish_url( $slugs );
                }

            } else {

                $v = $this->loop_vqdr;

                $_vehicle = $this->vehicle && $this->vehicle->is_complete() ? $this->vehicle->get_slugs() : [];

                $part_numbers = [
                    $v->db_rim_1->get( 'part_number' ),
                    $v->is_staggered() ? $v->db_rim_2->get('part_number' ) : null
                ];

                return get_rim_finish_url( $v->db_rim_1->get_slugs(), $part_numbers, $_vehicle );
            }
        }

    }

	/**
	 * For TIRES and RIMS only. Packages has its own function with the same name.
	 *
	 * @return array
	 */
	public function get_add_to_cart_args() {

	    assert( $this->class_type === 'tires' || $this->class_type === 'rims' );

		// add to cart arguments used globally (for all btns on page)..
        // we have to add specific args for the current loop item.
		$atc = $this->atc;

		$singular_type         = $this->class_type === 'tires' ? 'tire' : 'rim';

		$main_object_front = $this->class_type === 'tires' ? $this->loop_front_tire : $this->loop_front_rim;
		$main_object_rear  = $this->class_type === 'tires' ? $this->loop_rear_tire : $this->loop_rear_rim;

		// always type multi
		$atc[ 'type' ] = 'multi';

		if ( $this->loop_staggered ) {

			// this just has to be the same across items that should be added to the same package
			$pkg_temp_id = 1;

			// 2 front
			$atc[ 'items' ][] = array(
				'type' => $singular_type,
				'loc' => 'front',
				'part_number' => $main_object_front->get( 'part_number' ),
				'pkg_temp_id' => $pkg_temp_id,
				'quantity' => 2,
			);

			// 2 rear
			$atc[ 'items' ][] = array(
				'type' => $singular_type,
				'loc' => 'rear',
				'part_number' => $main_object_rear->get( 'part_number' ),
				'pkg_temp_id' => $pkg_temp_id,
				'quantity' => 2,
			);


		} else {

			$vqdr_int = $this->class_type === 'tires' ? VQDR_INT_TIRE_1 : VQDR_INT_RIM_1;

			// 4 universal
			$atc[ 'items' ][] = array(
				'type' => $singular_type,
				'loc' => 'universal',
				'part_number' => $main_object_front->get( 'part_number' ),
				'quantity' => $this->loop_vqdr->get_item_atc_adjusted_qty( $vqdr_int ),
			);
		}

		return $atc;

	}

	/**
     * When false, we show products with specific part numbers and add to cart buttons,
     * when true, we show tire models or rim finishes in the search results.
     *
     * Returns true for vehicle related searches or tires by size, otherwise false.
     *
	 * @return bool
	 */
	public function are_results_grouped() {
	    switch ( $this->class_type ) {
            case 'rims':
                return in_array( $this->context, [ 'by_brand', 'by_size' ] );
            case 'tires':
                // tires by size are not grouped
                return in_array( $this->context, [ 'by_brand', 'by_type' ] );
            default:
                return false;
        }
	}

    /**
     *
     * @param bool $for_image_link - no changes needed in this case.
     * @return array
     */
	public function get_details_button_text_and_title( $for_image_link = false ){

	    assert( $this->class_type === 'tires' || $this->class_type === 'rims' );

        if ( $this->class_type === 'tires' ) {

            if ( $this->are_results_grouped() ) {
                $count = gp_if_set( $this->loop_data, 'group_count' );
                $product_title = $this->get_loop_brand_model_name();
                return [ "View Tires ($count)", $product_title ];
            } else {
                $product_title = $this->get_loop_brand_model_name();
                return [ "Details", $product_title ];
            }


        } else if ( $this->class_type === 'rims' ) {

            if ( $this->are_results_grouped() ) {
                $count = gp_if_set( $this->loop_data, 'group_count' );
                $product_title = $this->get_loop_brand_model_name();
                return [ "View Rims ($count)", $product_title ];
            } else {
                $product_title = $this->get_loop_brand_model_name();
                return [ "Details", $product_title ];
            }

        } else {
            return [ "", "" ];
        }

    }

    /**
     * @return string
     */
	public function render_item_bottom_grouped_result() {

		$details_url = $this->get_details_button_url();
		list( $text, $title ) = $this->get_details_button_text_and_title( false );

        $op    = '';
		$op .= '<div class="pi-buttons count-1">';
		$op .= '<div class="pi-button color-black"><a title="' . $title . '" href="' . $details_url . '">' . $text . '</a></div>';
		$op .= '</div>'; // pi-buttons

		return $op;
	}
}
