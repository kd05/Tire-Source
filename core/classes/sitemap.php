<?php

// can't use constants in traits so... define outside.
define( 'SITEMAP_DEFAULT_INDICATOR', '__use_default_param__' );

/**
 * A general class/trait for building sitemaps as an array and then
 * exporting to XML or doing whatever else we need to do.
 *
 * Will use this trait in another class which handles the build method.
 *
 * Trait T_App_Sitemap
 */
Trait T_App_Sitemap{

	/**
	 * Array of pages.. maybe split into
	 * categories.. which we may also have to
	 * unsplit later on when generating xml.
	 *
	 * will be easier to debug like this maybe.
	 *
	 * @var
	 */
	public $data = [];

	// mutable static default properties, can set
	// these up before adding a large number of pages
	public static $default_last_mod;
	public static $default_change_freq;
	public static $default_priority;
	// Disallow rules from the robots.txt file
    /** @var array */
	public static $robots_disallow_rules = [];

	/**
	 * App_Sitemap constructor.
	 *
	 * @param $args
	 */
	public function __construct( $args = [] ) {
		$this->args = $args;
	}

	/**
	 * Add an item to the sitemap basically...
	 *
	 * pass in self::get() basically
	 *
	 * @param        $thing
	 * @param string $cat
	 */
	public function add_single( $thing, $cat = 'default' ) {

		if ( ! $thing ) {
			return;
		}

		if ( ! isset( $this->data[$cat] ) ) {
			$this->data[$cat] = [];
		}

		$this->data[$cat][] = $thing;
	}

	/**
     * Add a set of items to the sitemap.
     *
	 * @param        $pages
	 * @param string $cat
	 */
	public function add_set( $pages, $cat = 'default' ) {
		if ( $pages && is_array( $pages ) ) {
			foreach ( $pages as $page ) {
				$this->add_single( $page, $cat );
			}
		}
	}

	/**
     * filter/validate a loc/url
     *
     * @param $loc
     * @return string|null
     */
	public function filter_loc( $loc ) {

	    // empty string could mean home page, but null means we'll omit it
        // later on
		if ( $loc === null ) {
			return null;
		}

        // parse url to retrieve host and path
        $parsed = parse_url($loc);
        // Check if the link is blocked in robots.txt
        foreach(self::$robots_disallow_rules as $rule) {
            // check if page is disallowed to us
            if(preg_match("/^$rule/", @$parsed['path'])) {
                return null;
            }
        }

        // Check if the link is not canonicalised
//        $linkHtml = @file_get_contents($in);
        // this is incredibly expensive, so instead of hitting every page in the sitemap
        // to ensure its non canonical, i'm going to just ensure we don't add any of those
        // in the first place.
        // turning this off because:
        // - its incredibly expensive
        // - it seems to actually be filtering out pages that actually do point to themselves in the canonical
        // tag. I think there might be some issue with encoding the ? or maybe some backslashes existing
        // in one of the strings for comparison, causing it to give false positives.
        // - we can easily add only canonical pages in the first place so that this is not necessary.
//        $linkHtml = @file_get_contents($loc);

//        // Find <link rel="canonical"> tags
//        if ($linkHtml && preg_match("/<link\s+[^>]*rel=\"canonical\"[^>]*>/", $linkHtml, $match)) {
//            // Find href attribute value
//            if (!empty($match[0]) && preg_match("/href=\"(.+)\"/", $match[0], $hrefMath) && !empty($hrefMath[1])) {
//                $canonicalHref = $hrefMath[1];
//                // The link is not canonicalised (href doesn't match the link)

//                if ($canonicalHref !== $in) {
//                if ($canonicalHref !== $loc) {
//                    return null;
//                }
//            }
//        }

        // return an allowed URL
        return $loc;
	}

	/**
	 * Sets global defaults which can apply to multiple instances of $this->get(), see
	 * self::get() and SITEMAP_DEFAULT_INDICATOR
	 *
	 * @param null $default_last_mod
	 * @param null $default_change_freq
	 * @param null $default_priority
	 */
	public function set_defaults( $default_last_mod = null, $default_change_freq = null, $default_priority = null ) {
		self::$default_last_mod    = $default_last_mod;
		self::$default_change_freq = $default_change_freq;
		self::$default_priority    = $default_priority;
	}

    /**
     * Parse robots.txt file and get Disallow rules
     *
     * @return bool
     */
	public function parseRobotsFile() {
        $robotsFilePath = BASE_DIR . '/robots.txt';
        // The robots.txt file doesn't exist => the rules are empty
        if (!file_exists( $robotsFilePath )) return true;
        $robotstxt = @file($robotsFilePath);
        // Can't open the robots.txt file => the rules are empty
        if (empty($robotstxt)) return true;
        $rules = array();
        foreach($robotstxt as $line) {
            // skip blank lines
            if(!$line = trim($line)) continue;
            if(preg_match('/^\s*Disallow:(.*)/i', $line, $regs)) {
                // an empty rule implies full access - no further tests required
                if(!$regs[1]) return true;
                // add rules that apply to array for testing
                $rules[] = preg_quote(trim($regs[1]), '/');
            }
        }
        self::$robots_disallow_rules = $rules;
        return true;
    }

	/**
	 * Get an item representing a single URL in the sitemap.
	 *
	 * Passing in null parameters means not to include them in the result.
	 *
	 * Alternatively, to setup global defaults, see $this->set_defaults() and pass
	 * in a parameter value of SITEMAP_DEFAULT_INDICATOR
	 *
	 * @param      $loc
	 * @param null $last_mod
	 * @param null $change_freq
	 * @param null $priority
	 *
	 * @return array
	 */
	public function get( $loc, $change_freq = null, $priority = null, $last_mod = null ) {

		$_last_mod    = $last_mod === SITEMAP_DEFAULT_INDICATOR ? self::$default_last_mod : $last_mod;
		$_change_freq = $change_freq === SITEMAP_DEFAULT_INDICATOR ? self::$default_change_freq : $change_freq;
		$_priority    = $priority === SITEMAP_DEFAULT_INDICATOR ? self::$default_priority : $priority;

		$loc = $this->filter_loc( $loc );

		if ( ! $loc ) {
		    return [];
        }

        $ret = [
            'loc' => $loc,
        ];

		if ( $_last_mod !== null ) {
			$ret[ 'lastmod' ] = $_last_mod;
		}

		if ( $_change_freq !== null ) {
			$ret[ 'changefreq' ] = $_change_freq;
		}

		if ( $_priority !== null ) {
			$ret[ 'priority' ] = $_priority;
		}

		return $ret;
	}

	/**
	 * Can be used to print an html table. Contains array indexes we use in ->get().
	 */
	public static function get_table_cols(){
		// array values intended to be table headers
		return array(
			'loc' => 'loc',
			'lastmod' => 'last_mod',
			'changefreq' => 'change_freq',
			'priority' => 'priority',
			'cat' => 'cat',
		);
	}

	/**
	 * Maybe log this to ensure each category is working properly...
	 * may indicate inventory issues or whatnot.
	 *
	 * @return array
	 */
	public function export_counts(){
		$ret = [];

		if ( $this->data ) {
			foreach ( $this->data as $cat => $cat_data ) {
				$ret[$cat] = count( gp_force_array( $cat_data, false ) );
			}
		}

		return $ret;
	}

	/**
	 *
	 */
	public function render_html_tables( $all_cats_in_one = false, $table_args = [] ){

		$cols = $this->get_table_cols();

		if ( $all_cats_in_one ) {

			$all = $this->get_depth_1_array();

			$df_title = 'all cats';
			$count = is_array( $all ) ? count( $all ) : 0;
			$table_args['title'] = gp_if_set( $table_args, 'title', $df_title ) . " ($count)";

			return render_html_table_admin( $cols, $all, $table_args );

		} else {

			$ret = '';

			if ( $this->data ) {
				foreach ( $this->data as $cat=>$cat_data ) {
					$count = is_array( $cat_data ) ? count( $cat_data ) : 0;
					$table_args['title'] = gp_test_input( $cat ) . " ($count)";
					unset( $cols['cat'] );
					$ret .= render_html_table_admin( $cols, $cat_data, $table_args );
				}
			}

			return $ret;
		}
	}

    /***
     * Get xml string
     *
     * @return mixed
     */
	public function to_xml(){

        $arr = $this->get_depth_1_array();

        $xml = new SimpleXMLElement("<?xml version='1.0' encoding='UTF-8' ?>\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" />');

        array_map( function( $row ) use( &$xml ){

            $url = $xml->addChild( 'url' );

            if ( $row && is_array( $row ) ) {

                // ordering and filtering out empty (except priority, see below)
                $to_add = array_filter( array(
                    // url should be entity escaped according to: https://blog.spotibo.com/sitemap-guide/
                    'loc' => htmlspecialchars( gp_if_set( $row, 'loc' ) ),
                    'lastmod' => gp_if_set( $row, 'lastmod' ),
                    'changefreq' => gp_if_set( $row, 'changefreq' ),
                    'priority' => gp_if_set( $row, 'priority' ),
                ), function( $value ){

                    // allow strictly 0 for priority
                    if ( $value === 0 ) {
                        return true;
                    }

                    return $value ? true : false;
                } );

                // format priority like "0.5" - not sure it matters. also don't know if this will be .5 of 0.5
                if ( isset( $to_add['priority'] ) ) {
                    $to_add['priority'] = number_format( $to_add['priority'], 1, '.', '' );
                }

                // now add to xml
                if ( $to_add ) {
                    foreach ( $to_add as $xml_prop => $xml_value ) {
                        $url->addChild( $xml_prop, htmlspecialchars( $xml_value ) );
                    }
                }

            }


        }, $arr ? $arr : [] );

        return $xml->asXML();
    }

    /**
     * Write xml string to sitemap.xml
     */
    public function write_to_root_directory(){

        $xml_string = $this->to_xml();
        $path = BASE_DIR . '/sitemap.xml';

        if ( file_exists( $path ) ){
            unlink( $path );
        }

        file_put_contents( $path, $xml_string );
    }

	/**
	 * Collect record set from $this->data ( data is otherwise an
     * array broken into categories)
	 *
	 * @return array
	 */
	public function get_depth_1_array(){

		$ret = [];

		if ( $this->data ) {
			foreach ( $this->data as $cat => $sets ) {
				if ( $sets ) {
					foreach ( $sets as $s1 => $s2 ) {
                        // add the cat for when we print to tables but don't forget
                        // to remove it when we export to xml
						$s2['cat'] = $cat;
						$ret[] = $s2;
					}
				}
			}
		}

		return $ret;
	}
}

/**
 * A site map class specifically for this application.
 *
 * Ie. generates sitemap info from products in database, and
 * has options to create/write an xml file to the root directory.
 *
 * Class App_Sitemap
 */
Class App_Sitemap {

	use T_App_Sitemap;

	/**
	 *
	 */
	public function build() {

	    // do once, early.
        $this->parseRobotsFile();

		$this->static_pages();

		$this->blog_pages();

		$this->product_landing_pages();

		$this->tire_archive_pages();
		$this->rim_archive_pages();

		$this->tire_model_pages();
		$this->rim_model_pages();
	}

	/**
	 *
	 */
	public function static_pages(){

		$ret = [];

		$this->set_defaults( null, null, null );

		$ret[] = $this->get( BASE_URL, 'monthly', 1 );

		//$ret[] = $this->get( get_url( 'blog' ), 'weekly', .9 );
		$ret[] = $this->get( get_url( 'contact' ), 'monthly', null );
		$ret[] = $this->get( get_url( 'faq' ), 'monthly', null );
		$ret[] = $this->get( get_url( 'gallery' ), 'monthly', null );

		// these should not be included in sitemap
		// $ret[] = $this->get( get_url( 'cart' ), 'monthly', null );
		// $ret[] = $this->get( get_url( 'checkout' ), 'monthly', null );
		// $ret[] = $this->get( get_url( 'login' ), null, null );
		// $ret[] = $this->get( get_url( 'logout' ), null, null );
		// $ret[] = $this->get( get_url( 'account' ), null, null );
		// $ret[] = $this->get( get_url( 'order_details' ), null, null );
		// $ret[] = $this->get( get_url( 'edit_profile' ), null, null );

		$ret[] = $this->get( get_url( 'shipping_policy' ), 'monthly', null );
        $ret[] = $this->get( get_url( 'warranty_policy' ), 'monthly', null );

        // these have mostly copied content
        // $ret[] = $this->get( get_url( 'return_policy' ), 'monthly', null );
        // $ret[] = $this->get( get_url( 'privacy_policy' ), 'monthly', null );
        // $ret[] = $this->get( get_url( 'fitment_policy' ), 'monthly', null );

		$this->add_set( $ret, 'static' );
	}

	/**
	 *
	 */
	public function product_landing_pages(){

		$ret = [];

		// brand links can change often due to inventory levels but,, i don't know if this matters much..
		$ret[] = $this->get( get_url( 'tires' ), 'weekly', .8 );
		$ret[] = $this->get( get_url( 'wheels' ), 'weekly', .8 );
		$ret[] = $this->get( get_url( 'packages' ), 'weekly', .8 );

		$this->add_set( $ret, 'pr_landing' );
	}

	/**
	 *
	 */
	public function rim_archive_pages(){

		$brands = get_rim_brands( APP_LOCALE_CANADA );

		if ( $brands ) {
			/** @var DB_Tire_Brand $brand */
			foreach ( $brands as $brand ) {
				$_brand = gp_test_input( $brand->get( 'slug' ) );
				$_url = Router::build_url( [ 'wheels', $_brand ] );
				$this->add_single ( $this->get( $_url, 'weekly' ), 'rim_brands' );
			}
		}
	}

	public function tire_model_pages(){

		$db = get_database_instance();

		$tires = DB_tires;
		$models = DB_tire_models;
		$brands = DB_tire_brands;

		$params = [];
		$q = "";
		$q .= "SELECT * ";
		$q .= "FROM $tires AS tires ";
		$q .= "INNER JOIN $models AS models ON models.tire_model_id = tires.model_id ";
		$q .= "INNER JOIN $brands AS brands ON models.tire_brand_id = brands.tire_brand_id ";

		$q .= "WHERE 1 = 1 ";

		// ie. sold_in_ca = 1 and stock_discontinued_ca = 0
		$stock_condition = DB_Product::sql_assert_sold_and_not_discontinued_in_locale( 'tires', APP_LOCALE_CANADA );
		$q .= "AND $stock_condition ";

		$q .= "GROUP BY models.tire_model_id ";
		$q .= "ORDER BY brands.tire_brand_slug ASC, models.tire_model_slug ASC ";

		$results = $db->get_results( $q, $params );

		if ( $results && is_array( $results ) ) {
			foreach ( $results as $row ) {

				$brand = gp_test_input( $row->brand_slug );
				$model = gp_test_input( $row->model_slug );
				$url = Router::build_url( [ 'tires', $brand, $model ] );
				$this->add_single( $this->get( $url, 'weekly' ), 'single_tire_pages' );

			}
		}
	}

	public function rim_model_pages(){

		$db = get_database_instance();

		$rims = DB_rims;
		$finishes = DB_rim_finishes;
		$models = DB_rim_models;
		$brands = DB_rim_brands;

		$params = [];
		$q = "";
		$q .= "SELECT * ";
		$q .= "FROM $rims AS rims ";
		$q .= "INNER JOIN $finishes AS finishes ON finishes.rim_finish_id = rims.finish_id ";
		$q .= "INNER JOIN $models AS models ON models.rim_model_id = finishes.model_id ";
		$q .= "INNER JOIN $brands AS brands ON brands.rim_brand_id = models.rim_brand_id ";

		$q .= "WHERE 1 = 1 ";

		// ie. sold_in_ca = 1 and stock_discontinued_ca = 0
		$stock_condition = DB_Product::sql_assert_sold_and_not_discontinued_in_locale( 'rims', APP_LOCALE_CANADA );
		$q .= "AND $stock_condition ";

		$q .= "GROUP BY finishes.model_id ";
		$q .= "ORDER BY brands.rim_brand_slug ASC, models.rim_model_slug ASC, finishes.color_1 ASC, finishes.color_2 ASC, finishes.finish ASC ";

		$results = $db->get_results( $q, $params );

		if ( $results && is_array( $results ) ) {
			foreach ( $results as $row ) {

				$brand = gp_test_input( $row->rim_brand_slug );
				$model = gp_test_input( $row->rim_model_slug );

				$url = get_rim_model_url( $brand, $model );
				$this->add_single( $this->get( $url, 'weekly' ), 'single_rim_pages' );
			}
		}
	}

	/**
	 *
	 */
	public function tire_archive_pages(){

//		$not_needed_ref = [];
//		$_sizes = get_all_unique_tire_sizes( [], APP_LOCALE_CANADA );
//
//		if ( $_sizes ) {
//			foreach ( $_sizes as $_size ) {
//
//				$_url = cw_add_query_arg_alt( [
//					'width' => (int) gp_if_set( $_size, 'width' ),
//					'profile' => (int) gp_if_set( $_size, 'profile' ),
//					'diameter' => (int) gp_if_set( $_size, 'diameter' ),
//				], $tires_url );
//
//				// higher priority cuz maybe ppl search tire sizes sometimes ??
//				$this->add_single( $this->get( $_url, 'weekly' ), 'tire_sizes' );
//			}
//		}

		foreach ( [ 'winter', 'summer', 'all-season', 'all-weather' ] as $_type ) {
			$this->add_single( $this->get( Router::build_url( [ 'tires', $_type ] ), 'weekly' ), 'tire_types' );
		}

		$brands = get_tire_brands( APP_LOCALE_CANADA );

		if ( $brands ) {
			/** @var DB_Tire_Brand $brand */
			foreach ( $brands as $brand ) {
			    $_brand = $brand->get( 'slug' );
                $this->add_single( $this->get( Router::build_url( [ 'tires', $_brand ] ), 'weekly' ), 'tire_brands' );
			}
		}

	}

    public function blog_pages() {
	    $blogUrl = BASE_URL . '/blog/';
        $ret = [];
	    $ret[] = $this->get( $blogUrl, 'weekly', .9 );
        $ret[] = $this->get( $blogUrl . '12-emergency-items-you-should-have-in-your-car/', 'weekly', .9 );
        $ret[] = $this->get( $blogUrl . '12-tips-for-selecting-the-right-tire-for-your-car/', 'weekly', .9 );
        $ret[] = $this->get( $blogUrl . '4-common-signs-you-need-a-wheel-alignment/', 'weekly', .9 );
        $ret[] = $this->get( $blogUrl . '5-ways-to-rotate-your-tires-and-why-its-important/', 'weekly', .9 );
        $ret[] = $this->get( $blogUrl . '6-best-ways-to-prepare-your-car-for-college/', 'weekly', .9 );
        $ret[] = $this->get( $blogUrl . '8-helpful-tips-to-prepare-for-a-road-trip-this-summer/', 'weekly', .9 );
        $ret[] = $this->get( $blogUrl . 'changing-a-flat-tire-mistakes-to-steer-away-from/', 'weekly', .9 );
        $ret[] = $this->get( $blogUrl . 'easy-and-safe-steps-to-take-when-you-have-a-flat-tire/', 'weekly', .9 );
        $ret[] = $this->get( $blogUrl . 'how-tires-can-affect-your-cars-performance/', 'weekly', .9 );
        $ret[] = $this->get( $blogUrl . 'how-to-check-your-tire-pressure-the-right-way/', 'weekly', .9 );
        $ret[] = $this->get( $blogUrl . 'how-to-select-the-right-wheel-for-your-car/', 'weekly', .9 );
        $ret[] = $this->get( $blogUrl . 'how-to-tell-when-you-need-new-tires/', 'weekly', .9 );
        $ret[] = $this->get( $blogUrl . 'how-weather-conditions-affect-your-car/', 'weekly', .9 );
        $ret[] = $this->get( $blogUrl . 'page/2/', 'weekly', .9 );
        $ret[] = $this->get( $blogUrl . 'the-different-types-of-wheels-and-their-components/', 'weekly', .9 );
        $ret[] = $this->get( $blogUrl . 'the-most-common-car-problems-to-be-prepared-for/', 'weekly', .9 );

        $this->add_set( $ret, 'blog' );
    }
}

