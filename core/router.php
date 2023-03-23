<?php

/**
 * PHP Routing. Htaccess directs most requests to a front controller
 * which will invoke serve() below.
 *
 * To add/modify page URL structure, see get_routes().
 */
class Router {

    // not the same as $instance->routes
    static $routes = [];

    /** @var AltoRouter */
    static $altoRouter;

    /**
     * URL parameters for the currently matched route.
     *
     * Routes will need to set this manually in the render function,
     * and some might not need to.
     *
     * @var array
     */
    static $params = [];

    /**
     * ie. 'wheels', or 'tire_brand',
     *
     * Sometimes the same as (and other times not the same as)...
     * self::$current_db_page->get( 'name' );
     *
     * @var string
     */
    static $current_page;

    /**
     * When this is setup, the header will likely check the meta title,
     * meta descriptions, etc. from values stored in the database.
     *
     * @var DB_Page
     */
    static $current_db_page;

    static function get_routes() {

        // keys are page names.
        // this is what we pass to get_url(),
        // and also the page_name column in the pages table (for some but not all pages)
        $ret = [];

        $basic = function ( $path, $filepath, $auto = true ) {

            return [
                'path' => $path,
                'auto_db_page' => $auto,
                'render' => function () use ( $filepath ) {
                    include $filepath;
                },
            ];
        };

        $ret[ 'home' ] = $basic( '', CORE_DIR . '/pages/index.php');
        $ret[ 'account' ] = $basic( 'account', CORE_DIR . '/pages/account.php' );
        $ret[ 'order_details' ] = $basic( 'order-details', CORE_DIR . '/pages/order-details.php' );
        $ret[ 'edit_profile' ] = $basic( 'edit-profile', CORE_DIR . '/pages/edit-profile.php' );
        $ret[ 'logout' ] = $basic( 'logout', CORE_DIR . '/pages/logout.php' );
        $ret[ 'login' ] = $basic( 'login', CORE_DIR . '/pages/login.php' );
        $ret[ 'reviews' ] = $basic( 'reviews', CORE_DIR . '/pages/reviews.php' );
        $ret[ 'checkout' ] = $basic( 'checkout', CORE_DIR . '/pages/checkout.php' );
        $ret[ 'faq' ] = $basic( 'faq', CORE_DIR . '/pages/faq.php' );
        $ret[ 'return_policy' ] = $basic( 'return-policy', CORE_DIR . '/pages/return-policy.php' );
        $ret[ 'privacy_policy' ] = $basic( 'privacy-policy', CORE_DIR . '/pages/privacy-policy.php' );
        $ret[ 'shipping_policy' ] = $basic( 'shipping-policy', CORE_DIR . '/pages/shipping-policy.php' );
        $ret[ 'fitment_policy' ] = $basic( 'fitment-policy', CORE_DIR . '/pages/fitment-policy.php' );
        $ret[ 'warranty_policy' ] = $basic( 'warranty-policy', CORE_DIR . '/pages/warranty-policy.php' );
        $ret[ 'gallery' ] = $basic( 'gallery', CORE_DIR . '/pages/gallery.php' );
        $ret[ 'cart' ] = $basic( 'cart', CORE_DIR . '/pages/cart.php' );
        $ret[ 'contact' ] = $basic( 'contact', CORE_DIR . '/pages/contact.php' );
        $ret[ 'reset_password' ] = $basic( 'reset-password', CORE_DIR . '/pages/reset-password.php' );
        $ret[ 'forgot_password' ] = $basic( 'forgot-password', CORE_DIR . '/pages/forgot-password.php' );
        $ret[ 'compare_wheels' ] = $basic( 'compare-wheels', CORE_DIR . '/pages/compare-wheels.php' );
        $ret[ 'compare_tires' ] = $basic( 'compare-tires', CORE_DIR . '/pages/compare-tires.php' );
        $ret[ 'trims' ] = $basic( 'trims', CORE_DIR . '/pages/trims.php' );

        // this url will bypass the front-controller,
        // but set up the route anyways so we can register its url
        $ret[ 'blog' ] = [
            'path' => 'blog',
        ];

        $ret[ 'admin' ] = [
            'path' => 'cw-admin',
            'render' => function () {

                if ( ! isset( $_GET[ 'page' ] ) ) {
                    show_404();
                }

                set_global( 'is_admin', true );

                // user authentication is in the controller
                $admin = new Admin_Controller();
                $admin->render();
            },
        ];

        // Landing page
        $ret[ 'tires' ] = [
            'path' => 'tires',
            'render' => function () {

                Router::$current_db_page = DB_Page::get_instance_via_name( DB_Page::LANDING_PAGE_PREFIX . 'tires' );

                include CORE_DIR . '/pages/tires-landing.php';
            },
        ];

        // Landing page
        $ret[ 'wheels' ] = [
            'path' => 'wheels',
            'render' => function () {

                Router::$current_db_page = DB_Page::get_instance_via_name( DB_Page::LANDING_PAGE_PREFIX . 'rims' );

                include CORE_DIR . '/pages/rims-landing.php';
            },
        ];

        // Landing page
        $ret[ 'packages' ] = [
            'path' => 'packages',
            'render' => function () {

                Router::$current_db_page = DB_Page::get_instance_via_name( DB_Page::LANDING_PAGE_PREFIX . 'packages' );

                include CORE_DIR . '/pages/packages-landing.php';
            },
        ];

        // Archive page
        $ret[ 'tire_brand' ] = [
            'virtual' => true,
            'path' => 'tires/[:brand]',
            'render' => function ( $brand ) {

                $dynamic_page_name = DB_Page::TIRE_BRAND_PREFIX . gp_test_input( $brand );
                Router::$current_db_page = DB_Page::get_instance_via_name( $dynamic_page_name );

                $merge = [ 'brand' => $brand ];
                $page = new Page_Tires( array_merge( $_GET, $merge ), [
                    'context' => 'by_brand'
                ] );

                if ( $page->context === 'by_brand' ) {
                    cw_get_header();
                    echo $page->render();
                    cw_get_footer();
                } else {
                    Router::show_404();
                }
            },
        ];

        // Archive page
        $ret[ 'tire_type' ] = [
            'virtual' => true,
            'render' => function ( $type ) {

                $dynamic_page_name = DB_Page::TIRE_TYPE_PREFIX . gp_test_input( $type );
                Router::$current_db_page = DB_Page::get_instance_via_name( $dynamic_page_name );

                $merge = [ 'type' => $type ];
                $page = new Page_Tires( array_merge( $_GET, $merge ), [
                    'context' => 'by_type'
                ] );

                if ( $page->context === 'by_type' ) {
                    cw_get_header();
                    echo $page->render();
                    cw_get_footer();
                } else {
                    Router::show_404();
                }
            },
        ];

        // Archive page
        $ret[ 'tire_size' ] = [
            'virtual' => true,
            'render' => function ( $width, $profile, $diameter ) {

                $merge = [
                    'width' => $width,
                    'profile' => $profile,
                    'diameter' => $diameter
                ];

                $page = new Page_Tires( array_merge( $_GET, $merge ), [
                    'context' => 'by_size'
                ] );

                if ( $page->context === 'by_size' ) {
                    cw_get_header();
                    echo $page->render();
                    cw_get_footer();
                } else {
                    Router::show_404();
                }
            },
        ];

        // non-virtual route for above 3 virtual routes
        $ret[ 'tire_various' ] = [
            'url_omit' => true,
            'path' => 'tires/[:various]',
            'render' => function ( $various ) use ( $ret ) {

                if ( in_array( $various, [ 'summer', 'winter', 'all-season', 'all-weather' ] ) ) {
                    Router::$current_page = 'tire_type';
                    return $ret[ 'tire_type' ][ 'render' ]( $various );
                }

                $matches = [];
                $match = preg_match( '/^(\d\d\d)-(\d{2,3})R(\d\d)$/', $various, $matches );

                if ( $match ) {
                    list( $_str, $width, $profile, $diameter ) = $matches;

                    Router::$current_page = 'tire_size';
                    return $ret[ 'tire_size' ][ 'render' ]( $width, $profile, $diameter );
                }

                Router::$current_page = 'tire_brand';
                return $ret[ 'tire_brand' ][ 'render' ]( $various );
            }
        ];

        // Archive page
        $ret[ 'rim_size' ] = [
            'path' => 'wheels/by-size',
            'render' => function () {

                $diameter = @$_GET['diameter'] ? explode('-', $_GET['diameter'] ) : [];
                $width = @$_GET['width'] ? explode('-', $_GET['width'] ) : [];

                $page = new Page_Rims( array_merge( $_GET, [
                    'diameter' => $diameter,
                    'width' => $width
                ]), [
                    'context' => 'by_size',
                ] );

                if ( $page->context === 'by_size' ) {
                    cw_get_header();
                    echo $page->render();
                    cw_get_footer();
                } else {
                    Router::show_404();
                }
            },
        ];

        // Archive page
        $ret[ 'rim_brand' ] = [
            'path' => 'wheels/[:brand]',
            'render' => function ( $brand ) {

                $dynamic_page_name = DB_Page::RIM_BRAND_PREFIX . gp_test_input( $brand );
                Router::$current_db_page = DB_Page::get_instance_via_name( $dynamic_page_name );

                $merge = [ 'brand' => $brand ];
                $page = new Page_Rims( array_merge( $_GET, $merge ), [
                    'context' => 'by_brand'
                ] );

                if ( $page->context === 'by_brand' ) {
                    cw_get_header();
                    echo $page->render();
                    cw_get_footer();
                } else {
                    Router::show_404();
                }
            },
        ];

        // product page
        $ret[ 'tire_model' ] = [
            'path' => 'tires/[:brand]/[:model]/[:part_numbers]?',
            'render' => function ( $brand, $model, $part_numbers = '' ) {

                // some part numbers are very weird, ie. "175/70R1484TSPORTS TEMPEST I"
                // note: in prod (plesk), had to add AllowEncodedSlashes NoDecode to httpd.conf to make this work.
                $part_numbers_arr = $part_numbers ? array_filter( explode( "_", $part_numbers ) ) : [];
                $part_numbers_arr = array_map( 'urldecode', $part_numbers_arr );

                Router::$params['brand'] = $brand;
                // 'model' is reserved for vehicle query arguments (make/model/year/etc.)
                Router::$params['_model'] = $model;
                Router::$params['front'] = @$part_numbers_arr[0];
                Router::$params['rear'] = @$part_numbers_arr[1];

                include CORE_DIR . '/pages/tire.php';
            },
        ];

        // product page
        $ret[ 'rim_model' ] = [
            'path' => 'wheels/[:brand]/[:model]',
            'render' => function ( $brand, $model ) {

                Router::$params['brand'] = $brand;
                Router::$params['_model'] = $model;

                include CORE_DIR . '/pages/wheel.php';
            },
        ];

        // product page
        $ret[ 'rim_finish' ] = [
            'path' => 'wheels/[:brand]/[:model]/[:finish]/[:part_numbers]?',
            'render' => function ( $brand, $model, $finish, $part_numbers = '' ) {


                $finishes = parse_rim_finish_url_segment( $finish );
                $part_numbers_arr = $part_numbers ? array_filter( explode( "_", $part_numbers ) ) : [];
                $part_numbers_arr = array_map( 'urldecode', $part_numbers_arr );

                Router::$params['brand'] = $brand;
                Router::$params['_model'] = $model;
                Router::$params['color_1'] = @$finishes[0];
                Router::$params['color_2'] = @$finishes[1];
                Router::$params['finish'] = @$finishes[2];

                if ( @$_GET['by_size'] ) {
                    $_GET['diameter'] = @$_GET['diameter'] ? explode('-', $_GET['diameter'] ) : [];
                    $_GET['width'] = @$_GET['width'] ? explode('-', $_GET['width'] ) : [];
                } else {
                    Router::$params['front'] = @$part_numbers_arr[0];
                    Router::$params['rear'] = @$part_numbers_arr[1];
                }

                include CORE_DIR . '/pages/wheel.php';
            },
        ];

        // Archive page
        $ret[ 'vehicle_tires' ] = [
            'path' => 'tires/[:make]/[:model]/[:year]/[:trim]/[:fitment]',
            'render' => function ( $make, $model, $year, $trim, $fitment ) {

                $vehicle_arr = [
                    'make' => gp_test_input( $make ),
                    'model' => gp_test_input( $model ),
                    'year' => gp_test_input( $year ),
                    'trim' => gp_test_input( $trim ),
                    'fitment' => gp_test_input( $fitment ),
                    'sub' => gp_test_input( @$_GET['sub'] )
                ];

                $page = new Page_Tires( $_GET, [
                    'context' => 'by_vehicle',
                ], new Vehicle( $vehicle_arr ) );

                if ( $page->context === 'by_vehicle' ) {
                    cw_get_header();
                    echo $page->render();
                    cw_get_footer();
                } else {
                    Router::show_404();
                }

            },
        ];

        // Archive page
        $ret[ 'vehicle_rims' ] = [
            'path' => 'wheels/[:make]/[:model]/[:year]/[:trim]/[:fitment]',
            'render' => function ( $make, $model, $year, $trim, $fitment ) {

                $vehicle_arr = [
                    'make' => gp_test_input( $make ),
                    'model' => gp_test_input( $model ),
                    'year' => gp_test_input( $year ),
                    'trim' => gp_test_input( $trim ),
                    'fitment' => gp_test_input( $fitment ),
                    'sub' => gp_test_input( @$_GET['sub'] )
                ];

                $page = new Page_Rims( $_GET, [
                    'context' => 'by_vehicle',
                ], new Vehicle( $vehicle_arr ) );

                if ( $page->context === 'by_vehicle' ) {
                    cw_get_header();
                    echo $page->render();
                    cw_get_footer();
                } else {
                    Router::show_404();
                }
            },
        ];

        // Archive page
        $ret[ 'vehicle_packages' ] = [
            'path' => 'packages/[:make]/[:model]/[:year]/[:trim]/[:fitment]',
            'render' => function ( $make, $model, $year, $trim, $fitment ) {

                $vehicle_arr = [
                    'make' => gp_test_input( $make ),
                    'model' => gp_test_input( $model ),
                    'year' => gp_test_input( $year ),
                    'trim' => gp_test_input( $trim ),
                    'fitment' => gp_test_input( $fitment ),
                    'sub' => gp_test_input( @$_GET['sub'] )
                ];

                $page = new Page_Packages( $_GET, [], new Vehicle( $vehicle_arr ) );

                if ( $page->context === 'by_vehicle' ) {
                    cw_get_header();
                    echo $page->render();
                    cw_get_footer();
                } else {
                    Router::show_404();
                }

            },
        ];

        return $ret;
    }

    static function build_instance( $routes ) {

        $router = new AltoRouter( [], '', [
            // this let's URL segments contain dots, which is needed sometimes for fitment slugs,
            // ie. packages/..../255-35ZR19-8.5Jx19_ET32
            '' => '[^/]++',
        ]);

        foreach ( $routes as $page_name => $args ) {

            if ( @$routes[ $page_name ][ 'virtual' ] ) {
                continue;
            }

            $method = gp_if_set( $args, 'method', 'GET|POST' );
            $path = @$args[ 'path' ] ? '/' . $args[ 'path' ] : '/';
            $render = @$args[ 'render' ] ? $args[ 'render' ] : function ( ...$args ) {
            };
            $router->map( $method, $path, $render, $page_name );
        }

        return $router;
    }

    /**
     * Basically: BASE_URL . '/' . implode( '/', $parts ),
     *
     * but with sanitation and some forward slash logic.
     *
     * ie. $parts = [ 'wheels', '720-form' ] => https://tiresource/com/wheels/720-form
     *
     * @param array $parts
     * @param array $query
     * @param string|null $base
     * @param bool $trailing_slash
     * @return string
     */
    static function build_url( array $parts, array $query = [], $base = null, $trailing_slash = false ) {

        $base = $base === null ? BASE_URL : $base;

        $parts = array_map( function( $part ) {
            return urlencode( gp_force_singular( $part ) );
        }, $parts );

        if ( $parts ) {
            $url = rtrim( $base, '/' ) . '/' . implode( '/', $parts );
        } else {
            $url = $base;
        }

        // if $parts are empty, $url could end with /
        if ( $trailing_slash ) {
            $url = rtrim( $url, '/' ) . '/';
        }

        if ( $query ) {
            return cw_add_query_arg( $query, $url );
        } else{
            return $url;
        }
    }

    /**
     * Get a URL from route name and an associative array of arguments.
     *
     * Ie. Router::get_url('tire_brand', [ 'brand' => '720-form' ]) =>
     * "domain.com/tires/720-form"
     * which would be the same as Router::build_url( [ 'tires', '720-form' ] )
     *
     * So why do we have both? Primarily because of the pages database table,
     * which stores the route name under the page_name column. So we need to be able
     * to dynamically access the URL's via name for those items. For other URLs that are
     * highly unlikely to change, ie. /tires/720-form, it might be preferred to just
     * "inline" the URL, via either simple string concatenation or Router::build_url
     * for automatic sanitation.
     *
     * It might be worth using this also for URLs that are complex or likely to change,
     * (vehicle URLs maybe?), then we could update just the route path and not break any
     * existing URLs. Not sure that i'll try to do this yet however.
     *
     * Also, complex URLs will probably have their own wrapper function to invoke this one.
     *
     * @param $name
     * @param array $args
     * @param array $query_args
     * @return string
     */
    static function get_url( $name, array $args = [], array $query_args = [] ) {

        // backwards compat thing (maybe can remove soon).
        if ( $name === 'rims' ) {
            $name = 'wheels';
        }

        $args = array_map( function( $arg ) {
            return urlencode( gp_force_singular( $arg ) );
        }, $args );

        // ie. "/tires/brandName/modelName"

        try {
            $relativeUrl = self::$altoRouter->generate( $name, $args );
        } catch ( Exception $e ) {
            // route was not found
            return '';
        }

        $url = rtrim( BASE_URL, '/' ) . $relativeUrl;

        if ( $query_args ) {
            return cw_add_query_arg( $args, $url );
        }

        return $url;
    }

    static function show_404() {

        Router::$current_page = null;
        Router::$current_db_page = null;
        show_404();
    }

    // ie. /wheels/?brand=720-form
    // or, /
    // or /?param=123
    // or /privacy-policy
    // etc.
    static function get_modified_uri( $omit_query = false ) {

        $uri = @$_SERVER[ 'REQUEST_URI' ];

        // don't remove the leading '/' if there's no prefix to remove
        if ( REQUEST_URI_PREFIX_IGNORE ) {
            $uri = gp_strip_prefix( '/' . REQUEST_URI_PREFIX_IGNORE, $uri );
        }

        if ( $omit_query && $uri ) {
            $uri = explode( '?', $uri )[0];
        }

        return $uri;
    }

    static function parse_url( $modified_uri ) {
        $parsed = parse_url( $modified_uri );
        $parts = explode( '/', trim( @$parsed['path'], '/' ) );
        $query = [];
        if(isset($parsed['query']) && is_null($parsed)) {
            parse_str( @$parsed['query'], $query );
        }

        return [ $parts, $query ];
    }

    /**
     * URL structure for nearly all product pages, with and without a vehicle, used to
     * be completely different. So we need to setup 301 redirects for SEO purposes from all
     * the old pages to the new pages. Note that we're really only concerned with URLs that
     * google has indexed. So actually we might not need to do it for vehicles, or a few
     * other pages.
     *
     * @param $modified_uri
     * @return array
     */
    static function prev_urls_redirect( $modified_uri ) {

        list( $parts, $query ) = Router::parse_url( $modified_uri );

        // if we don't check .php things should still work but would result 2 in redirects instead
        // of 1.
        if ( count( $parts ) === 1 && $parts[0] === 'wheels' || $parts[0] === 'wheels.php' ) {
            if ( @$query['brand'] && count( $query ) === 1 ) {
                return [ Router::build_url( [ 'wheels', $query['brand']] ), 301 ];
            }
        }

//        print_r( $parts );
//        print_r( $query );

        // /tires, or /tires.php
        if ( count( $parts ) === 1 && $parts[0] === 'tires' || $parts[0] === 'tires.php' ) {

            // /tires?brand=maxxis => /tires/maxxis
            if ( count( $query ) === 1 && @$query['brand'] ) {
                return [ Router::build_url( [ 'tires', $query['brand']] ), 301 ];
            }

            // /tires?type=summer => /tires/summer
            if ( count( $query ) === 1 && @$query['type'] ) {
                return [ Router::build_url( [ 'tires', $query['type']] ), 301 ];
            }

            // /tires?width=215&profile=60&diameter=16 => /tires/maxxis/wp-05/12345
            if ( @$query['width'] && @$query['profile'] && @$query['diameter'] ) {
                return [ get_tire_size_url( $query['width'], $query['profile'], $query['diameter'] ), 301 ];
            }
        }

        // ie. /tires/maxxis/wp-05
        if ( count( $parts ) === 3 && $parts[0] === 'tires' && $parts[1] && $parts[2] ) {

            // /tires/maxxis/wp-05?part_number=12345 => /tires/maxxis/wp-05/12345
            if ( count( $query ) === 1 && @$query['part_number'] ) {
                return [ get_tire_model_url( [ $parts[1], $parts[2] ], [ $query['part_number'] ] ), 301 ];
            }
        }

        // wheels/brand/model
        if ( count( $parts ) === 3 && $parts[0] === 'wheels' && $parts[1] && $parts[2] ) {

            // /wheels/brand/model?color-1=c1&color_2=c2 => /wheels/brand/model/c1-with-c2 etc.
            if ( @$query['color_1'] && @$query['color_2'] && @$query['finish'] ) {
                $part_numbers = @$query['part_number'] ? [ $query['part_number'] ] : [];
                return [ get_rim_finish_url( [ $parts[1], $parts[2], $query['color_1'], $query['color_2'], $query['finish'] ], $part_numbers ), 301 ];
            } else if ( @$query['color_1'] && @$query['color_2'] ) {
                $part_numbers = @$query['part_number'] ? [ $query['part_number'] ] : [];
                return [ get_rim_finish_url( [ $parts[1], $parts[2], $query['color_1'], $query['color_2'] ], $part_numbers ), 301 ];
            } else if ( @$query['color_1'] ) {
                $part_numbers = @$query['part_number'] ? [ $query['part_number'] ] : [];
                return [ get_rim_finish_url( [ $parts[1], $parts[2], $query['color_1'] ], $part_numbers ), 301 ];
            }
        }
    }

    static function empty_query_redirect( $modified_uri ){
        if ( string_ends_with( $modified_uri, '?' ) ) {
            return [ BASE_URL . substr( $modified_uri, 0, strlen( $modified_uri ) - 1), 301 ];
        }
    }

    static function dot_php_redirect( $modified_uri ) {

        if ( strpos( $modified_uri, '.php' ) !== false ) {

            $new_uri = $modified_uri;
            $new_uri = str_replace( '.php/', '', $new_uri );
            $new_uri = str_replace( '.php', '', $new_uri );

            $location = BASE_URL . "$new_uri";

            return [ $location, 301 ];
        }
    }

    static function handle_redirect_result( $result ) {
        // otherwise, do nothing.
        if ( is_array( $result ) && @$result[0] ) {
            header( "Location: " . $result[0], true, @$result[1] ? $result[1] : 301 );
            exit;
        }
    }

    // prefer no trailing slashes anywhere. This is different from /blog however.
    static function trailing_slash_redirect( $modified_uri ) {

        // no re-directs for homepage
        // $_SERVER['request_uri'] is "/" at tiresource.com even without a trailing slash
        // also redirecting home page on dev causes issues when project is not in server root
        if ( $modified_uri === '/' || strpos( $modified_uri, '/?' ) === 0 ) {
            return false;
        }

        if ( string_ends_with( $modified_uri, '/' ) ) {

            $location = BASE_URL . rtrim( $modified_uri, '/' );

            return [ $location, 301 ];

        } else if ( strpos( $modified_uri, '/?' ) !== false ) {

            // wheels/?brand=123 => wheels?brand=123
            $new = str_replace( '/?', '?', $modified_uri );
            $location = BASE_URL . '/' . trim( $new, '/' );

            return [ $location, 301 ];
        }
    }

    /**
     * @param $modified_uri
     * @return array
     */
    static function other_redirects( $modified_uri ){

        if ( strpos( $modified_uri, 'wheels/robert-thibert') !== false ) {
            return [ BASE_URL . '/wheels/rtx', 301 ];
        }

    }

    // checks $_SERVER, and serves a page.
    static function serve() {

        $modified_uri = Router::get_modified_uri();

        // these can call a redirect and exit.
        Router::handle_redirect_result( Router::prev_urls_redirect( $modified_uri ) );
        Router::handle_redirect_result( Router::empty_query_redirect( $modified_uri ) );
        Router::handle_redirect_result( Router::dot_php_redirect( $modified_uri ) );
        Router::handle_redirect_result( Router::trailing_slash_redirect( $modified_uri ) );
        Router::handle_redirect_result( Router::other_redirects( $modified_uri ) );

        queue_dev_alert( "Modified URI: " . $modified_uri );

        $match = Router::$altoRouter->match( $modified_uri );

        queue_dev_alert( "Match: " . @$match['name'], $match );

        if ( $match ) {

            if ( @$_COOKIE[ 'admin_tables_overflow_visible' ] ) {
                Header::$body_classes[] = 'admin-tables-overflow-visible';
            }

            Router::$current_page = @$match[ 'name' ];
            $route = @Router::$routes[ @$match[ 'name' ] ];

            queue_dev_alert( "Route", $route );

            if ( @$route[ 'auto_db_page' ] ) {
                Router::$current_db_page = DB_Page::get_instance_via_name( @$match[ 'name' ] );
            }

            // call render function
            call_user_func_array( $match[ 'target' ], $match[ 'params' ] );
        } else {
            Router::show_404();
        }
    }
}

Router::$routes = Router::get_routes();
Router::$altoRouter = Router::build_instance( Router::$routes );
