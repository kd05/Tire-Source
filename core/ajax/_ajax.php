<?php

/**
 * Class Ajax
 */
class Ajax {

    const GLOBAL_NONCE_SECRET = '243wef3sdf23sdfsd';

    // probably no longer used
    protected static $current_action;

    /**
     * We'll store callbacks here which will call self::register_custom_route
     *
     * It's not totally necessary to do it this way but has some benefits.
     *
     * @var array
     */
    private static $init_fns = [];

    /**
     * @var array
     */
    private static $custom_routes = [];

    // register new ajax actions below, then put a similarly named file in core/ajax
    // This is only for method #1 (see self::serve())
    static function get_routes() {

        // keys are a.k.a. ajax actions
        // the secret is just any unique and persistent string that's not
        // trivial to guess (don't just use the action)
        $routes = array(
            'add_to_cart' => [
                'nonce_secret' => 'cw-cart-add-ajax',
            ],
            'change_password' => [
                'nonce_secret' => 'change-pw-23987gsadf68gfasd',
            ],
            'checkout' => [
                'nonce_secret' => 'checkout-d7afhj278asdfha7h27d',
            ],
            'cleanup_rims' => [
                'nonce_secret' => 'cw-deleting-rims',
            ],
            'cleanup_tires' => [
                'nonce_secret' => 'cw-deleting-rims',
            ],
            'compare' => [
                'nonce_secret' => 'compare87y7283egh67azsdf76gfas7dasd',
            ],
            'contact_1' => [
                'nonce_secret' => 'contactForm-number1-in-the-footer',
            ],
            'edit_profile' => [
                'nonce_secret' => 'edit-profile-asid7gasud6fgausd6fgasd',
            ],
            'forgot_password' => [
                'nonce_secret' => 'forgot-password-8hsd87gasdf76gasd76gasd',
            ],
            'general_ajax' => [
                'nonce_secret' => 'asdi76hbasd8u6gvasd76gvasd',
            ],
            'import_rims' => [
                'nonce_secret' => 'cw-rim-import',
            ],
            'import_tires' => [
                'nonce_secret' => 'cw-rim-import',
            ],
            'logout' => [
                'nonce_secret' => 'logout-asd7h7ashd87ghasd87gahsdf867gasf876g',
            ],
            'reset_password' => [
                'nonce_secret' => 'reset-password-83772345982346872364876234',
            ],
            'review_product' => [
                'nonce_secret' => 'review-products-9ahsd876ga78s6dasd',
            ],
            'set_country' => [
                'nonce_secret' => 'set-country-878fdg7yasdfh',
            ],
            'set_per_page' => [
                'nonce_secret' => 'set-the-posts-per-page-in-session-secret',
            ],
            'sign_in' => [
                'nonce_secret' => '123-signin-asdh3hdfhasfh7327asdh',
            ],
            'sign_up' => [
                'nonce_secret' => 'signup-555-kjhaiskudhg7asdkgy32',
            ],
            'supplier_email' => [
                'nonce_secret' => 'asd876tas8d6g86asgdGSADGasdghAS',
            ],
            'update_cart' => [
                'nonce_secret' => 'updating-cart-secret-nonce',
            ],
            'vehicle_lookup' => [
                'nonce_secret' => 'cw-finding-vehicles',
            ],
            'tire_model_image' => [
                'nonce_secret' => 'zxcbvkluyh3erkhsdz',
            ],
            'rim_finish_image' => [
                'nonce_secret' => 'zxcvo78h3kusdfkg66534s',
            ],
            'insert_coupon' => [
                'nonce_secret' => 'insert-coupon-bnobuycxwzgecvoyp',
            ],
        );

        foreach ( $routes as $key => $val ) {
            if ( ! isset( $val[ 'file' ] ) ) {
                $routes[ $key ][ 'file' ] = str_replace( "_", "-", $key ) . '.php';
            }
        }

        return $routes;
    }

    /**
     * When using __action parameter, send this under $_REQUEST['nonce']
     * if the file you are targeting requires a nonce (csrf) value.
     *
     * @param null $secret
     * @return false|string
     * @throws Exception
     */
    static function get_global_nonce( $secret = null ){
        $secret = $secret ? $secret : self::GLOBAL_NONCE_SECRET;
        return get_nonce_value( $secret, true );
    }

    /**
     * Call manually in /ajax/unregistered if the file requires csrf protection
     *
     * @param string $key
     * @param null $secret
     */
    static function check_global_nonce( $key = 'nonce', $secret = null ){

        $secret = $secret ? $secret : self::GLOBAL_NONCE_SECRET;

        if ( ! validate_nonce_value( $secret, @$_REQUEST[$key], true ) ) {
            $error = "Request authentication error. Re-loading the page and trying again may fix the issue.";
            echo json_encode( [
                'success' => false,
                'output' => $error,
            ] );
            exit;
        }
    }

    /**
     * @param $fn
     */
    static function add_init_fn( $fn ){
        self::$init_fns[] = $fn;
    }

    /**
     * Register a route via a callback (and therefore, no files have to be
     * put into core/ajax)
     *
     * You might want to put the calls to this inside of a function passed
     * to self::add_init_fn().
     *
     * When using this method, pass $_REQUEST['__route__'], not the
     * old $_REQUEST['ajax_action'].
     *
     * @param $name
     * @param array $opts
     */
    static function register_custom_route( $name, array $opts ){
        assert( isset( $opts['run'] ) && is_callable( $opts['run'] ) );
        self::$custom_routes[$name] = $opts;
    }

    /**
     * Note 3 ways to do ajax:
     *
     * 1. Register route in self::get_routes(), then drop a file in core/ajax. Pass $_REQUEST['ajax_action']
     * to target the file. $_REQUEST['ajax_action'] will have underscores converted to dashes before looking
     * up the filename.
     *
     * 2. Use add_init_fn and register_custom_route to register a callback (and no need to place
     * any files in core/ajax. Pass $_REQUEST['__route__'] as the name in register_custom_route.
     * In your callback, make sure to check nonce/csrf if its necessary. You may want to use
     * self::check_global_nonce() for this (which uses the same nonce token for different requests)
     *
     * 3. Similar to number 2 but drop a file in core/ajax/__routes instead of calling
     * register_custom_route. Pass $_REQUEST['__route__'] and check nonce if its needed.
     *
     * #1 was the original way to do things, but it's tedious, and requires modifying this file
     * to add routes (which is especially annoying if writing code to run on both WFL and CW).
     *
     * #2 and #3 were newly added. So most things are still using #1.
     *
     * @param array $routes
     */
    static function serve( array $routes ) {

        // possibly add custom routes.
        foreach ( self::$init_fns as $fn ) {
            $fn();
        }

        $include_file = null;

        // check method #2 or #3
        $__route__ = @$_REQUEST['__route__'];

        if ( $__route__ ) {

            /**
             * Method #3
             */
            if ( isset( self::$custom_routes[$__route__] ) ) {
                $include_file = function() use( $__route__ ) {
                    $run = self::$custom_routes[$__route__]['run'];
                    $run();

                    // for this case we won't require the callback to always exit
                    // (unlike what we do for files).
                    exit;
                };
            } else {

                /**
                 * Method #2
                 */
                $file = $__route__;
            // for security, we have to ensure filename doesn't start with ../ or similar,
            // which could allow executing unintended files.
            $file = preg_replace( '/[^a-zA-Z0-9-_]/','', $file );
                $path = CORE_DIR . '/ajax/__routes/' . $file . '.php';

            if ( file_exists( $path ) ) {

                    $include_file = function() use( $path ) {

                        // the file is responsible for checking nonce token if necessary.
                        // it may decide to use self::check_global_nonce()
                include $path;
                    };
                } else {
                    // just let it do the previous ajax thing
            }
        }
        } else {

            /**
             * Method #1
             */
        $ajax_action = @$_REQUEST[ 'ajax_action' ];
        $nonce = @$_REQUEST['nonce'];
        $route = @$routes[ $ajax_action ];

            if ( $route ) {

                $include_file = function() use( $ajax_action, $route, $nonce ) {

        // nonce check
        if ( ! @$route[ 'skip_nonce' ] ) {
            if ( ! validate_nonce_value( $ajax_action, $nonce ) ) {
                $error = "Request authentication error. Re-loading the page and trying again may fix the issue.";
                echo json_encode( [
                    'success' => false,
                    'plain_text' => $error,
                    'output' => $error,
                    'response_text' => $error,
                ] );
                exit;
            }
        }

            // file should echo response and exit
            require CORE_DIR . '/ajax/' . @$route[ 'file' ];
                };
            }
        }


        try{

            if ( $include_file ) {

                // Ajax::echo_response will use the same time tracking context
                start_time_tracking( 'ajax_serve' );

                $include_file();

                // silent errors here can get really annoying
                echo "File did not exit.";
                exit;
            } else {

                echo 'Invalid action.';
            exit;
            }

        } catch( Exception $e ) {

            if ( IN_PRODUCTION ) {
                $output = wrap_tag( "Unknown Error.");
            } else {
                $output = get_pre_print_r( array(
                    'Exception' => get_pre_print_r( $e ),
                    'CallStack' => generate_call_stack_debug(),
                ));
            }

            log_data([
                'msg' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ], 'ajax-exceptions', true, true );

            // some older (?) forms used output but newer ones use response.
            // we need to use both here since we don't know what the front-end expects.
            self::echo_response( [
                'success' => false,
                'output' => $output,
                'response' => $output,
            ] );
            exit;
        }
    }

    /**
     * @param $action
     * @return string
     */
    static function get_action_field( $action ) {
        return '<input type="hidden" name="ajax_action" value="' . $action . '">';
    }

    /**
     * Try to use this function to respond with JSON from ajax files, since it
     * adds some debugging stuff when not in production.
     *
     * @param $response - an array most likely, which will be json encoded
     * @param null $ajax_action - legacy param
     */
    public static function echo_response( $response, $ajax_action = null ) {

        // possibly not used, but left here for backwards compat
        if ( ! is_array( $response ) ) {
            echo $response;
            exit;
        }

        if ( ! IN_PRODUCTION ) {
            $response['__debug'] = [
                'idk' => gp_array_to_js_alert( listen_get( 'ajax_debug' ) ),
                'post' => $_POST,
                'files' => $_FILES,
                'debug' => Debug::render(),
                'time' => end_time_tracking( 'ajax_serve' ),
            ];
        }

        echo json_encode( $response );
        exit;
    }

}