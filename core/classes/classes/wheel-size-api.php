<?php

require_once COMPOSER_DIR . '/autoload.php';

use Curl\Curl as Curl;

/**
 * Middleware between us and the api.
 *
 * Note: in code, we rarely use these methods directly. Instead,
 * there are other functions which wrap these ones, and will filter
 * the data and add a caching layer.
 *
 * Class Wheel_Size_Api
 */
Class Wheel_Size_Api {

    /**
     * We will filter out trims and fitments not belonging to each one
     * of these markets. Note that the methods of this class
     * will not do any filtering and always return raw data,
     * but we have wrapper functions that will filter the
     * raw data into more meaningful stuff. This is where we'll
     * be checking the markets.
     *
     * Although we prefer only usdm, I don't think there is enough
     * data if we don't also include eudm.
     */
    const MARKETS_ALLOWED = [ 'usdm' , 'eudm' ];

    // public static $user_key = IN_PRODUCTION ? '' : '12485bc16e9aa1cc4ea65b49739672ba'; // geekpower acc
    public static $user_key = WHEEL_SIZE_API_KEY;

    // no trailing slash here. It will get adder later however.
    public static $url = 'https://api.wheel-size.com/v1';

    /**
     * 30000 hits per day is the hard cap provided by the API.
     *
     * We let the API mostly deal with enforcing this limit,
     * but we will detect when we get close and start to throttle requests,
     * the thing we're trying to prevent is sql injection spam.
     *
     * @var int
     */
    public static $hits_per_hour_max = 1250;

    // basically no idea what any of these do,
    public static $referer = BASE_URL; // ???
    public static $userAgent = 'curl/7.27.0'; // ???
    public static $timeout = 30; // ???
    public static $returnTransfer = true; // ???
    public static $maxRedirects = 10; // ???
    public static $followLocation = ''; // ???
    public static $cookieFileLocation = ''; // ???

    public static $last_request_has_error;

    /**
     * Ie. did we return nothing due to sql injection attempt...
     *
     * @var
     */
    public static $last_request_flagged_for_spam;

    /**
     * I think more of a debugging tool.
     *
     * @var array
     */
    public static $request_urls_flagged_for_spam = [];

    /**
     * Wheel_Size_Api constructor.
     */
    public function __construct() {}

    /**
     * @param array $path
     * @param array $query
     * @return array|string
     */
    public static function get_url( $path = array(), $query = array() ) {

        $url = self::get_url_from_path( $path );

        // query gets passed in as an array
        $query = self::add_user_key_to_array( $query );

        $url = cw_add_query_arg( $query, $url );
        return $url;
    }

    /**
     * Ideally, we don't do this. But if we're under an attack, we'll
     * have to probably throttle requests for all users, in hopes
     * of detering the malicious users.
     *
     * Returns true if anything was throttled. Possibly sleeps for a bit.
     */
    public static function throttle_request(){

        // a bit messy to store this in options table but we need a quick and budget
        // solution for this, and I think this will work just fine. If we wanted
        // to not mess up the options table we could just store this in the cache
        // table actually. But for now I want it in the options table so that we
        // can get an indicator of whether or not this is happening again.
        $time_indicator = date( "Ymd_H" );
        $option_key = "api_requests_$time_indicator";
        $count = cw_get_option( $option_key );
        $count = $count && gp_is_integer( $count ) ? (int) $count : 0;
        cw_set_option( $option_key, $count + 1 );

        // shutting off throttling due to possible ddos causing
        // database connection errors (too many concurrent causes db shutdown maybe)
        // im not sure... and not sure if sleeping has anything to do with that.
        // leave the tracking in place however.
        return false;

        $limit_pct = $count / self::$hits_per_hour_max;

        // I think sleep only accepts int, this should accept float.
        $sleep = function( $seconds ){
            // not minding possible rounding errors here ...
            $microseconds = intval( $seconds * 1000000 );
            usleep( $microseconds );
        };

        $no_risk_pct = 0.2;
        $low_risk_pct = 0.4;
        $medium_risk_pct = 0.6;
        $high_risk_pct = 0.8;

        /**
         * In general, keep throttling low for now. Our SQL injection detecting may
         * actually prevent all forms of throttling anyways. After a while, we'll get
         * an idea of whether or not this is happening a lot, and if it is, we can
         * try to address throttling on an individual basis, or figure something else out.
         */
        if ( $limit_pct < $no_risk_pct ) {
            return false;
        } else if ( $limit_pct < $low_risk_pct ) {
            $sleep( 0.25 );
            return true;
        } else if ( $limit_pct < $medium_risk_pct ) {
            $sleep( 0.5 );
            return true;
        } else if ( $limit_pct < $high_risk_pct ) {
            $sleep( 0.75 );
            return true;
        } else {
            // at or above limit..
            // can't go too high here. keep in mind, this could affect normal users as well,
            // and even possibly google bot if it happens to be indexing.
            $sleep( 1 );
            return true;
        }
    }

    /**
     * @param $url
     *
     * @return bool
     */
    public static function url_is_spam_or_has_sql_injection( $url ) {

        // allow + either as literal character or encoded character.
        // there isn't any queries I know of that use plus, but we cannot allow
        // % or other encoded entities to pass by. So replace %20 with + first,
        // then once doing that, will fail on encoded characters.
        $_url = $url;
        $_url = str_replace( "%20", "+", $_url );

        // don't allow : once we remove these..
        $_url = str_replace( "http://", "", $_url );
        $_url = str_replace( "https://", "", $_url );

        // ie valid...
        // https://api.wheel-size.com/v1/trims/?make=alfa-romeo&model=giulia&year=2017&user_key=asdjhasd...

        // $clean = preg_replace( '//g', $url );
        // letters, numbers, dash, underscore, =, and forward slash def. required.
        // allowing . or , ir probably not, but can't be used in sql injection so I think its fine.
        $compare_with = preg_replace( '/[^a-zA-Z0-9-_?=&\/.,]/', "", $_url );

//		var_dump( $_url );
//		echo nl2br( "-----------------------  \n" );
//		var_dump( $compare_with );

        // if the preg replace modified the $url, deny it
        if ( $compare_with === $_url ) {
            return false;
        }

        return true;
    }

    /**
     * NOTE: current plan: 30000 hits per day, which is an average of 1250 hits per hour.
     *
     * @return mixed
     * @throws Exception
     */
    public static function send_request( $path = array(), $query = array() ) {

        static::$last_request_has_error = false;
        static::$last_request_flagged_for_spam = false;

        $url = self::get_url( $path, $query );
        Debug::add( $url, 'CURL' );

        if ( self::url_is_spam_or_has_sql_injection( $url ) ) {

            // deter attackers, also, make sure we don't log too much data...
            // sleep(4);

            Debug::add( gp_test_input( $url ), 'CURL SPAM/SQL QUERY' );

            // can probably just keep this on to see if it ever happens with legitimate data.
            // although once we verify there are no false positives, we should shut this off..
            // todo: shut off eventually
            log_data( $url, 'wheel-size-spam-queries' );

            self::$last_request_has_error = true;
            self::$request_urls_flagged_for_spam[] = gp_test_input( $url );

            return false;
        }

        // Do this after filtering the URL for spam. This way, malicious users
        // should not result in normal users having their stuff slowed down.
        // possibly does nothing, otherwise, may sleep for a bit.
        self::throttle_request();

        // this is a fallback only and we shouldn't get to here, but in case somehow
        // character encodings still exist, this should break them, which again
        // is just a fallback, but i'd rather keep it in place.
        $url = str_replace( "%", "", $url );
        $url = str_replace( ";", "", $url );

        // send to wheel-size.com
        $curl = new Curl();
        $curl->get( $url );

        if ($curl->error) {

            Debug::add( $curl->error_message, 'CURL' );

            // this property might not be used currently
            static::$last_request_has_error = true;

            log_data( get_var_dump( [ [ func_get_args() ], $curl]  ), 'wheel-size-request-error' );

            return false;

            // why was I doing this again?
//			return array(
//				'error' => 'error',
//			);
        }

        curl_close( $curl->curl );
        $response = $curl->response;

        // Note: force associative array. lots of other code will rely on response being
        // arrays instead of StdClass objects
        return json_decode( $response, true );
    }

    /**
     * force associative arrays
     *
     * @param $response
     * @return mixed
     */
    public static function decode_response( $response ) {
        return json_decode( $response, true );
    }

    /**
     *
     */
    // public static function get_years(){}

    /**
     * ie. ... /trims/?make=..&model=..&year=..
     *
     * @param $make
     * @param $model
     * @param $year
     * @return mixed
     * @throws Exception
     */
    public static function get_trims( $make, $model, $year ) {

        $path = array(
            'trims',
        );

        $query = array(
            'make' => $make,
            'model' => $model,
            'year' => $year,
        );

        return self::send_request( $path, $query );
    }

    /**
     * @param $make
     * @param $model
     * @return mixed
     * @throws Exception
     */
    public static function get_model_info( $make, $model ) {

        // todo: this endpoint is deprecated apparently, however, we're not using this anywhere except in tests.
        $path = array(
            'models',
            $make,
            $model
        );

        $query = array();
        return self::send_request( $path, $query );
    }

    /**
     * @param $make
     * @param $year
     * @return mixed
     * @throws Exception
     */
    public static function get_models_by_year( $make, $year ) {

        $path = array(
            'models',
        );

        $query = array(
            'make' => $make,
            'year' => $year,
        );

        return self::send_request( $path, $query );
    }

    /**
     * Not the same as get years by make and model. Getting that data is
     * actually harder and involved getting much more data then needed and sorting through it.
     *
     * @param $make
     * @return mixed
     * @throws Exception
     */
    public static function get_years_by_make( $make ) {

        $path = array(
            'years',
        );

        $query = array(
            'make' => $make,
        );

        return self::send_request( $path, $query );
    }

    /**
     * ie. ... /vehicles/?make=..&model=..&year=..
     *
     * This has fitment data and a ton of other stuff.
     *
     * Likely not in use
     *
     * @param $make
     * @param $model
     * @param $year
     */
//	public static function get_vehicle_info( $make, $model, $year ) {
//
//		$path = array(
//			'vehicles',
//		);
//
//		$query = array(
//			'make' => $make,
//			'model' => $model,
//			'year' => $year,
//		);
//
//		return self::send_request( $path, $query );
//	}

    /**
     * @return mixed
     * @throws Exception
     */
    public static function get_makes(){
        // https://..../models/make/model/year/
        $path = array(
            'makes',
        );

        $query = array();

        return self::send_request( $path, $query );
    }

    /**
     * ie. models/?make=chevrolet
     *
     * @param $make
     * @return mixed
     * @throws Exception
     */
    public static function get_models( $make ) {

        // https://..../models/make/model/year/
        $path = array(
            'models',
        );

        $query = array(
            'make' => $make,
        );

        return self::send_request( $path, $query );
    }

    /**
     * ie. ... /models/make/model/year
     *
     * This actually provides more of "summary" data for specific vehicles.
     *
     * You might want to use get_vehicle_info() instead, which returns data we need.
     *
     * I don't know if we'll ever need the data returned by this method.
     *
     * @param $make
     * @param $model
     * @param $year
     * @return mixed
     * @throws Exception
     */
    public static function get_detailed_info( $make, $model, $year ) {

        // https://..../models/make/model/year/
        $path = array(
            'models',
            $make,
            $model,
            $year,
        );

        $query = array();
        return self::send_request( $path, $query );
    }

    /**
     * @param $make
     * @param $model
     * @param $year
     * @param $trim
     * @return mixed
     * @throws Exception
     */
    public static function get_fitment_data( $make, $model, $year, $trim ){

        // https://..../models/make/model/year/
        $path = array(
            'search',
            'by_model',
        );

        $query = array(
            'make' => $make,
            'model' => $model,
            'year' => $year,
            'trim' => $trim,
        );

        return self::send_request( $path, $query );
    }

    /**
     * ie. returns: https://api...../path/variables/here/
     *
     * path can be array or string, or the full URL already, which will just get returned.
     *
     * @param array $path
     * @return array|string
     */
    public static function get_url_from_path( $path = array() ) {

        if ( is_array( $path ) ) {
            array_filter( $path ); // remove empty array values to prevent double slashes "//"
            $str = $path ? implode( '/', $path ) : '';
        } else {
            $str = $path;
            if ( strpos( $str, self::$url ) !== false ) {
                return $str;
            }
        }

        $str = trim( $str );
        $str = trim( $str, '/' );
        $ret      = self::$url . '/' . $str . '/'; // examples had slash at end...
        return $ret;
    }

    /**
     * @param array $args
     * @return array
     */
    public static function add_user_key_to_array( $args = array() ){

        if ( ! is_array( $args ) ) {
            $args = (array) $args;
            $args = array_filter( $args );
        }

        if ( is_array( $args ) && ! isset( $args['user_key'] ) ) {
            $args['user_key'] = self::$user_key;
        }

        return $args;
    }
}

/**
 * Returns a very large and detailed list of vehicle information, which includes
 * bolt patterns, all fitments, all trims etc.
 *
 * If you only need the trims of a make/model/year, use the get_trims() method instead.
 *
 * When you need fitment data, we'll rely on a subset of this data, but probably via other more specific
 * functions.
 *
 * @param $make
 * @param $model
 * @param $year
 *
 * @return array|bool|mixed|null|string
 * @throws Exception
 */
//function get_vehicle_info( $make, $model, $year ) {
//
//	if ( ! $make ) {
//		throw new Exception( 'Invalid make' );
//	}
//
//	if ( ! $model ) {
//		throw new Exception( 'Invalid model' );
//	}
//
//	if ( ! $year ) {
//		throw new Exception( 'Invalid year' );
//	}
//
//	$cache_key = 'api_vehicle_info_' . $make . '_' . $model . '_' . $year;
//	$cache = gp_cache_get( $cache_key, null );
//
//	if ( $cache !== null ) {
//		return $cache;
//	}
//
//	$ret = Wheel_Size_Api::get_vehicle_info( $make, $model, $year );
//	gp_cache_set( $cache_key, $ret );
//
//	return $ret;
//}

/**
 * not in use..
 *
 * @param       $make
 * @param       $model
 * @param       $year
 * @param array $markets
 */
//function get_trims_by_market( $make, $model, $year, $markets = array( 'usdm' ) ){
//
//	if ( ! $make ) {
//		throw new Exception( 'Invalid make' );
//	}
//
//	if ( ! $model ) {
//		throw new Exception( 'Invalid model' );
//	}
//
//	if ( ! $year ) {
//		throw new Exception( 'Invalid year' );
//	}
//
//	// its a little frustrating to think about how we should handle caching here.
//	// the point is, in order to get trims only by market, we actually have to fetch a very large amount
//	// of data from the API. That data also has ALL fitment data, which we almost know for sure we are going to end up
//	// needing, because immediately after we return the list of trims, someone will select one option from that list
//	// and then we'll have to retrieve the fitment data for the specific trim. Again, that data is already here.
//	// However, I don't want to sort and store 12 different sets of fitment data to the database caching table
//	// when we might need only 1 of them. So in the end, even though this returns all trim and all fitment data together
//	// I think we're going to only extract what we need, and ignore the rest of it. Afterwards, we'll do another more
//	// specific query to get the data we just had, and then we'll only cache the relevant parts.
//
//	// $test = Wheel_Size_Api::get_vehicle_info( $make, $model, $year );
//}

/**
 * Get all trims for a make, model, and year.
 * Note that when using this function, we don't know if the trims returned will
 * have fitment data for US Domestic Market. Therefore we might not use this.
 *
 * @param $make
 * @param $model
 * @param $year
 * @return array|bool|mixed|string|null
 * @throws Exception
 */
function get_trims( $make, $model, $year ) {

    if ( ! $make ) {
        throw new Exception( 'Invalid make' );
    }

    if ( ! $model ) {
        throw new Exception( 'Invalid model' );
    }

    if ( ! $year ) {
        throw new Exception( 'Invalid year' );
    }

    $cache_key = 'api_trims_' . $make . '_' . $model . '_' . $year;
    $trims = gp_cache_get( $cache_key, null );

    if ( $trims !== null ) {
        return $trims;
    }

    $r = Wheel_Size_Api::get_trims( $make, $model, $year );

    // example of what $r might look like. it often contains 15-20 items. I've cut this down to 2.
//	$example_of_r = array (
//        0 =>
//            array (
//                'slug' => '20tdi-150-b9',
//                'name' => '2.0TDi 150 [B9]',
//                'trim' => '2.0TDi 150',
    // note on 'body': this mainly exists for japanese domestic marketplace. It will normally be null for our data.
//                'body' => NULL,
//                'generation' => 'B9',
//                'production_start_year' => 2016,
//                'production_end_year' => 2018,
    // note on 'options': this is a newer data point (2020ish). It will give users more info in their trim. We will
    // likely try to include some information on the options here.
//                'options' =>
//                    array (
//                    ),
//                'markets' =>
//                    array (
//                        0 =>
//                            array (
//                                'slug' => 'eudm',
//                                'abbr' => 'EUDM',
//                                'name' => 'European domestic market',
//                                'name_en' => 'European domestic market',
//                            ),
//                    ),
//            ),
//        1 =>
//            array (
//                'slug' => '20tdi-163-b9',
//                'name' => '2.0TDi 163 [B9]',
//                'trim' => '2.0TDi 163',
//                'body' => NULL,
//                'generation' => 'B9',
//                'production_start_year' => 2016,
//                'production_end_year' => 2018,
//                'options' =>
//                    array (
//                    ),
//                'markets' =>
//                    array (
//                        0 =>
//                            array (
//                                'slug' => 'eudm',
//                                'abbr' => 'EUDM',
//                                'name' => 'European domestic market',
//                                'name_en' => 'European domestic market',
//                            ),
//                    ),
//            ),
//    );

//    $r = [
//        [
//            'slug' => 'test123',
//            'name' => 'test123',
//            'options' => [],
//            'markets' => [ 'usdm' => [] ],
//        ],
//        [
//            'slug' => 'test123',
//            'name' => 'test123',
//            'options' => [],
//            'markets' => [ 'usdm' => [] ],
//        ]
//    ];

    // filter the list of trims by market, so that a user cannot select a trim which will be
    // guaranteed to lead to an empty fitment list. This is because the list of fitments
    // is filtered by market. Now, we filter trims by market also. In the early
    // stages of the API, it was not possible to do this.
    $r = array_filter( is_array( $r ) ? $r : [], function( $trim ){

        $markets = gp_force_array( @$trim['markets'] );

        // get the array keys of $trim['markets'] and ensure at least one of them belongs to one of our allowed markets.
        return count( array_intersect( Wheel_Size_Api::MARKETS_ALLOWED, array_column( $markets, 'slug' ) ) ) >= 1;
    });

    // append the options to the trim name (which may or may not already have the generation appended)
    // note that there is 2 general approaches to adding the options to the name. We can either choose
    // whether or not to show the generation first and then show the options, or the other way around.
    // when we add the options first, we end up with a lot of things that would be duplicate
    // except that one shows the options and ones does not. Ie. 320d, and 320d (option 1, option 2).
    // this is not ideal. So we can compare the trims for duplicates first, showing the generation
    // where needed, then add the options later. This is likely the better approach.
    // this would result in 320d [VI], 320d [VII] (option 1, option 2).
    $add_options_string_to_name = function( $name_without_options, array $options ){

        $options = array_map( function( $option ){
            return gp_test_input( $option );
        }, is_array( $options ) ? $options : [] );

        if ( count( array_filter( $options ) ) > 0 ) {
            $options_str = implode( ", ", $options );
            return $name_without_options . " ($options_str)";
        } else {
            return $name_without_options;
        }
    };

    /**
     * Extract two names and the slug. We will return one of the 2
     * names afterwards.
     */
    $r = array_map( function( $trim ) use( $add_options_string_to_name ){

        $slug = gp_test_input( @$trim['slug'] );

        // the data appears to assert that these variables are named accurately.
        $name_simplified = gp_test_input( @$trim['trim'] );
        $name_simplified = $name_simplified ? $name_simplified : $slug;

        // do not add the options yet.
        // $name_simplified = $add_options_string_to_name( $name_simplified, @$trim['options'] );

        // appears to basically always be $trim['trim'] with $trim['generation'] added in []
        $name_with_generation = gp_test_input( @$trim['name'] );
        $name_with_generation = $name_with_generation ? $name_with_generation : $slug;

        // do not add the options yet.
        // $name_with_generation = $add_options_string_to_name( $name_with_generation, @$trim['options'] );

        // however, I am still falling back to slug, because I don't know if 100%, or 99%
        // or 75% of the data is structured according to what is normally found.
        return [
            'slug' => $slug,
            'name_preferred' => $name_simplified,
            'name_fallback' => $name_with_generation,
            'options' => is_array( @$trim['options'] ) ? $trim['options'] : []
        ];

    }, $r );

    $all_preferred_names = array_column( $r, 'name_preferred' );
    $count_preferred_names = array_count_values( $all_preferred_names );

    $ret = [];

    /**
     * assemble the return value. Do so in such a way that if two trims would
     * have the same name without taking into the account the generation, then
     * append the generation to both of them, which should mean that they no
     * longer have the same name. But they still could if the generation is
     * also the same. For the time being, I will not filter out duplicate
     * names after taking this step because in all likelihood, this will
     * not be an issue. it depends on the data which I do not control.
     *
     * Note that the generation is almost always in the slug, and the
     * slug is the array key. So there cannot be 2 of the same slug
     * in the return value.
     *
     */
    foreach ( $r as $trim ) {
        $slug = $trim['slug'];
        if ( $count_preferred_names[$trim['name_preferred']] <= 1 ) {
            $ret[$slug] = $trim['name_preferred'];
        } else {
            $ret[$slug] = $trim['name_fallback'];
        }

        // add the options string regardless of whether or not we use the generation.
        $ret[$slug] = $add_options_string_to_name( $ret[$slug], $trim['options']);
    }

    // example of $ret:
//    Array
//    (
//        [10tfsi-8x-restyling] => 1.0TFSi
//        [14tdi-8x-restyling] => 1.4TDi
//        [14tfsi-8x-restyling] => 1.4TFSi
//        [16tdi-8x-restyling-b30] => 1.6TDi [b30]
//        [16tdi-8x-restyling-b75] => 1.6TDi [b75]
//        [18tfsi-8x-restyling] => 1.8TFSi
//        [25-tfsi-gb] => 25 TFSi
//        [30-tfsi-gb] => 30 TFSi
//        [35-tfsi-gb] => 35 TFSi
//    )

    // dont clog up caching table for requests that didn't even get send to the API
    if ( ! Wheel_Size_Api::$last_request_flagged_for_spam ) {
        gp_cache_set( $cache_key, $ret );
    }

    return $ret;
}

/**
 * Not the same as get_models() which only requires make
 *
 * @param $make
 * @param $year
 * @return array|bool|mixed|string|null
 * @throws Exception
 */
function get_models_by_year( $make, $year ) {

    if ( ! $make ) {
        Throw new Exception( 'Invalid make' );
    }

    if ( ! $year ) {
        Throw new Exception( 'Invalid year' );
    }

    $ret = array();

    $cache_key = 'api_models_by_year_' . $make . '_' . $year;
    $cache = gp_cache_get( $cache_key, null );

    if ( $cache !== null ) {
        return $cache;
    }

    $r = Wheel_Size_Api::get_models_by_year( $make, $year );

    if ( $r && is_array( $r ) ) {
        foreach ( $r as $k=>$v ) {
            $slug = gp_if_set( $v, 'slug' ); // ie. 2017, identical to name most likely
            $name = gp_if_set( $v, 'name' ); // ie. 2017
            $ret[$slug] = $name;
        }
    }

    // dont clog up caching table for requests that didn't even get send to the API
    if ( ! Wheel_Size_Api::$last_request_flagged_for_spam ) {
        gp_cache_set( $cache_key, $ret );
    }

    return $ret;
}

/**
 * @param $make
 * @param $model
 */
function get_years_by_make( $make ) {

    $ret = array();

    if ( ! $make ) {
        Throw new Exception( 'Invalid make' );
    }

    $cache_key = 'api_years_by_make_' . $make;
    $cache = gp_cache_get( $cache_key, null );

    if ( $cache !== null ) {
        return $cache;
    }

    $r = Wheel_Size_Api::get_years_by_make( $make );

    if ( $r && is_array( $r ) ) {
        foreach ( $r as $k=>$v ) {

            $slug = gp_if_set( $v, 'slug' ); // ie. 2017, identical to name most likely
            $name = gp_if_set( $v, 'name' ); // ie. 2017

            $ret[$slug] = $name;
        }
    }

    // sort keys (show new years first) and maintain association.
    // note that maintaining array keys is important here because they will get send to other API requests
    // actually this isnt needed. It's already sorted with new years first. Thankfully javascript just totally ignores that
    // however and applies its own sorting. So we still get to fix it in the javascript instead.
    // krsort( $ret );


    // dont clog up caching table for requests that didn't even get send to the API
    if ( ! Wheel_Size_Api::$last_request_flagged_for_spam ) {
        gp_cache_set( $cache_key, $ret );
    }

    return $ret;
}

/**
 * Get model info based on make and model..
 *
 * This is summary data about the vehicle and different generations, but not data we need often.
 *
 * It seems to have market data buried within, but instead of explicitely stating the market
 * it just has something like title => 'T250 [2007 .. 2011] [USDM] Saloon' which is fairly useless to us.
 *
 * @param $make
 * @param $model
 */
function get_model_info( $make, $model ) {

    if ( ! $make ) {
        throw new Exception( 'Invalid make' );
    }

    if ( ! $model ) {
        throw new Exception( 'Invalid model' );
    }

    $cache_key = 'api_model_info_' . $make . '_' . $model;
    $cache = gp_cache_get( $cache_key, null );

    if ( $cache !== null ) {
        return $cache;
    }

    $ret = Wheel_Size_Api::get_model_info( $make, $model );

    // remove unnecessary data. Actually, its possible that all data
    // except years is not needed, in which case why do we have this function in the first place ?
    // In fact maybe we'll add caching to our get_years function and just do it that way.
    //	if ( isset( $r->generations ) ) {
    //		unset( $r->generations );
    //	}
    //
    //	if ( isset( $r->rims ) ) {
    //		unset( $r->rims );
    //	}
    //
    //	if ( isset( $r->tires ) ) {
    //		unset( $r->tires );
    //	}

    // dont clog up caching table for requests that didn't even get send to the API
    if ( ! Wheel_Size_Api::$last_request_flagged_for_spam ) {
        gp_cache_set( $cache_key, $ret );
    }

    return $ret;
}

/**
 * Get all Models based only on Make. Note that our form will probably use
 * the Make -> Year -> Model approach, rather than Make -> Model -> Year
 *
 * In other words, we might not need this function for anything.
 *
 * @param $make
 */
function get_models( $make ) {

    $ret = array();

    if ( ! $make ) {
        throw new Exception( 'Invalid make' );
    }

    $cache_key = 'api_models_' . $make;
    $cache = gp_cache_get( $cache_key, null );

    if ( $cache !== null ) {
        return $cache;
    }

    $r = Wheel_Size_Api::get_models( $make );

    if ( $r && is_array( $r ) ) {
        foreach ( $r as $k=>$v ) {
            $slug = gp_if_set( $v, 'slug' );
            $name = gp_if_set( $v, 'name_en' );
            $name = $name ? $name : gp_if_set( $v, 'name' ); // just in case
            $ret[$slug] = $name;
        }
    }

    // dont clog up caching table for requests that didn't even get send to the API
    if ( ! Wheel_Size_Api::$last_request_flagged_for_spam ) {
        gp_cache_set( $cache_key, $ret );
    }

    return $ret;
}

/**
 * Get all Makes, ie. Chevrolet, Ford etc.
 *
 * @return array|bool|mixed|null
 */
function get_makes(){

    $ret = array();

    $cache_key = 'api_makes';
    $cache = gp_cache_get( $cache_key, null );

    // useful to set small number of makes in development:
//	$cache = array(
//		'chevrolet' => 'Chevrolet',
//		'volkswagen' => 'Volkswagen',
//		'ford' => 'Ford',
//		'ferrari' => 'Ferrari',
//		'audi' => 'Audi',
//	);

    if ( $cache !== null  ) {

        // the normal rule here is to say if cache is not equal to null return it,
        // but if the cache value for get all vehicle makes is empty, we know something went wrong.
        // not only that, but this is indeed happening and i am yet to figure out why because it only happens
        // some of the time.
        if ( $cache ) {
            return $cache;
        } else {
            log_data( '..', 'get-makes-cache-is-empty' );
        }
    }

    $r = Wheel_Size_Api::get_makes();

    // I guess we'll exclude before caching the data.
    // it wouldn't make much of a difference either way.

    global $exclude_makes_array;
    $exclude = is_array( $exclude_makes_array ) && $exclude_makes_array ? $exclude_makes_array : array();

    if ( $r && is_array( $r ) ) {
        foreach ( $r as $k=>$v ) {
            $slug = gp_if_set( $v, 'slug' );
            $name = gp_if_set( $v, 'name_en' );
            $name = $name ? $name : gp_if_set( $v, 'name' ); // just in case

            $allow = true;

            if ( $exclude ) {

                foreach ( $exclude as $e1=>$e2 ) {
                    if ( gp_two_strings_more_or_less_the_same( $name, $e2 ) ){
                        $allow = false;
                        break;
                    }
                }
            }

            if ( $allow ) {
                $ret[$slug] = $name;
            }
        }
    }

    // the return value should NEVER be empty here, however, it is being empty
    // and whats worse, only some of the time. For now a solution will be to never cache
    // all makes with an empty result because this can never be correct.
    // for other more specific API requests like getting trims or fitments,
    // it is possibly to have empty AND valid requests, so we can't very
    // easily know when to cache or not cache those results.
    if ( $ret ) {

        // this one has no user input, shouldn't need to check for sql/spam flag of request
        // add more seconds than normal for this one. it loads on page load a lot of the time.
        gp_cache_set( $cache_key, $ret, 86400 );
    } else {
        log_data( '', 'get-makes-return-is-empty' );
    }

    return $ret;
}

/**
 * This is essentially the array that we'll use to generate <select> <options> for fitments.
 *
 * The key is a unique fitment slug, and the value is the displayed version.
 *
 * @param $make
 * @param $model
 * @param $year
 * @param $trim
 * @return array
 */
function get_fitment_names( $make, $model, $year, $trim ){
    $fitment_data = get_fitment_data( $make, $model, $year, $trim );
    return get_fitment_names_from_fitment_data( $fitment_data );
}

/**
 * remove generation and body stuff which is appended
 * to the string in brackets or square brackets sometimes.
 *
 * @param $str
 */
/**
 * Possibly remove the generation which shows up in square brackets
 * after the engine size or w/e. In most cases, the generation gives
 * far too much info and is just a bunch of random letters and numbers
 * and not relevant for most customers using the site.
 *
 * @param $str
 * @param bool $remove_generation
 * @return string|string[]|null
 */
function filter_wheel_size_api_vehicle_trim_name( $str, $remove_generation = true ) {

    $str = gp_force_singular( $str );

    if ( $remove_generation ) {
        // remove anything inside of [] including the []
        $str = preg_replace( "/\[[\s\S]{0,}\]/", '', $str );
    }

    $str = gp_test_input( $str );

    return $str;
}

/**
 * @param $fitment_data
 */
//function get_fitment_names_specific_from_fitment_data( $fitment_data ) {
//
//	$ret = array();
//
//	// U.S. domestic market
//	$wheels = gp_if_set( $fitment_data, 'wheels' );
//
//	if ( $wheels && is_array( $wheels ) ) {
//		foreach ( $wheels as $k=>$v ) {
//			$name = gp_if_set( $v, 'name' );
//			$slug = gp_if_set( $v, 'slug' );
//			$ret[$slug] = $name;
//		}
//	}
//
//	return $ret;
//}

/**
 * This returns some data from ALL wheel sets found within $fitment_data,
 * without actually returning all data, which is quite a bit. We store this
 * in the vehicle instance for a bit of an optimization..
 *
 * @param $fitment_data
 */
function get_wheel_sets_overview_data( $fitment_data ) {

    $ret = array();

    // U.S. domestic market
    $wheels = gp_if_set( $fitment_data, 'wheels' );

    if ( $wheels && is_array( $wheels ) ) {
        foreach ( $wheels as $k=>$v ) {
            $name = gp_if_set( $v, 'name' );
            $slug = gp_if_set( $v, 'slug' );
            $ret[$slug] = $name;
        }
    }

    return $ret;
}

/**
 * @param $fitment_data
 * @return array
 */
function get_fitment_names_from_fitment_data( $fitment_data ) {

    $ret = array();

    // U.S. domestic market
    $wheels = gp_if_set( $fitment_data, 'wheels' );

    if ( $wheels && is_array( $wheels ) ) {
        foreach ( $wheels as $k=>$v ) {
            $name = gp_if_set( $v, 'name' );
            $slug = gp_if_set( $v, 'slug' );
            $ret[$slug] = $name;
        }
    }

    return $ret;
}

/**
 * Sets up fitment data to only have specified slugs. Although rather inefficient,
 * you basically have to first get the fitment data, then based on user input decide
 * which slug you want, then call this function again. The fitment slug comes from the
 * fitment data itself, and represents many different values. It would be tough to filter
 * out the data by something more specific than the fitment slug (ie. this would require a function
 * with a lot of parameters and no guarunteed result)
 *
 * @param       $fitment_data
 * @param array $fitment_slugs
 */
//function filter_fitment_data( $fitment_data, $fitment_slugs ){
//
//	$wheels = gp_if_set( $fitment_data, 'wheels' );
//	$wheels_ret = array();
//
//	if ( ! is_array( $fitment_slugs ) ){
//		$fitment_slugs = (array) $fitment_slugs;
//	}
//
//	if ( $wheels ) {
//		foreach ( $wheels as $w1 => $w2 ) {
//			if ( in_array( $w1, $fitment_slugs ) ) {
//				$wheels_ret[$w1] = $w2;
//			}
//		}
//	}
//
//	$fitment_data['wheels'] = $wheels_ret;
//	return $fitment_data;
//}

/**
 * Gets a full set of fitment data based on Make, Model, Year, and Trim.
 * Note: the array of fitments is found at $ret['wheels'], where $ret is the return value of this function.
 * The other array indexes of $ret are for like bolt pattern, stud holes, etc.
 *
 * @param $make
 * @param $model
 * @param $year
 * @param $trim
 *
 * @return array|bool|mixed|null|string
 * @throws Exception
 */
function get_fitment_data( $make, $model, $year, $trim ) {

    if ( ! $make ){
        Throw new Exception( 'Invalid make' );
    }

    if ( ! $model ){
        Throw new Exception( 'Invalid model' );
    }

    if ( ! $year ){
        Throw new Exception( 'Invalid year' );
    }

    if ( ! $trim ){
        Throw new Exception( 'Invalid trim' );
    }

    $cache_key = 'api_fitment_' . implode( '_', array( $make, $model, $year, $trim ) );
    $cache = gp_cache_get( $cache_key, null );

    if ( $cache !== null ) {
        return $cache;
    }

    // fitment data returns an array of fitments for each possible market (ie. european domestic market, north america etc.)
    // sometimes this is just one market, other times a trim could exist in several markets.
    // we'll have to do a lot of sorting through this data and then decide what we need to store in our database.
    $r = Wheel_Size_Api::get_fitment_data( $make, $model, $year, $trim );

    // don't do extra work and also don't cache this value (ie return early on this)
    if ( Wheel_Size_Api::$last_request_flagged_for_spam ) {
        return [];
    }

    // this is a market priority system. We check for resluts in each market until a result is found, and then
    // rely on those results. The return value from the API is an array of markets. Each market contains a trim and
    // general vehicle info, each of which contain an index called 'wheels' which is the fitment data we're after...
    // vehicle data is buried within there however, so we're going to have to just have one function to return both sets of data
    // then call other functions later to get more specifically what we need.
    $markets = Wheel_Size_Api::MARKETS_ALLOWED;
    $fd_single = array();
    $ret = array();

    // Find one set of data via market
    if ( $r && is_array( $r ) ) {
        foreach ( $r as $result ) {
            $market = gp_if_set( $result, 'market' );
            $market_slug = gp_if_set( $market, 'slug' );

            $found = false;
            if ( $markets ) {
                foreach ( $markets as $ms ) {
                    if ( $market_slug == $ms ) {
                        $found = true;
                        $fd_single = $result;
                        break;
                    }
                }
            }

            if ( $found ) {
                break;
            }
        }
    }

    // Fitment data which is now from one market only

    if ( $fd_single ) {

        // NOTE: $v contains a mix of stdClass Objects and arrays so be careful here
        $market = gp_if_set( $fd_single, 'market' );
        $market_slug = gp_if_set( $market, 'slug' );

        //			if ( $market_slug !== 'usdm' ){
        //				continue;
        //			}

        $v_trim = gp_if_set( $fd_single, 'trim' ); // $trim is already defined in this function
        $stud_holes = gp_if_set( $fd_single, 'stud_holes' );
        $pcd = gp_if_set( $fd_single, 'pcd' );

        // ***** NOTE ******* API: "centre", Our code: "center"
        $center_bore = gp_if_set( $fd_single, 'centre_bore' );
        $bolt_pattern = gp_if_set( $fd_single, 'bolt_pattern' );

        // this is generally a large array where the fitments are actually stored
        // im highly debating whether we should go through this data and do some error checking
        // and assemble our own array. The issue is that there could be about 1 million possible
        // return values here, and i don't know 100% that every single one is in the same format
        // we're obviously relying on the fact that they are. Also old cars have different sizing systems
        // so if we try to set our own logical defaults and stuff we may end up loosing data
        // so for now, we're going to put in the raw data from the API, so that for potential edge cases
        // in the future, we can rely on whatever the API gives us, and leave the interpretation of the data
        // for later on in the code.
        $wheels = gp_if_set( $fd_single, 'wheels' );
        $wheels_filtered = array();

        // loop through and store our own values for name and slug which will be used by other functions
        // remember to consider staggered fitments here.
        // Remember that we have a "Fitment" class that might be tempting to use here, however we cant.
        // the Fitment class relies on data in a certain format. This function grabs from the API and then transforms the
        // format of the data by a small amount, and also checks and/or stores database cache. So we need to have the filtering logic here
        // to ensure we never instantiate a Fitment object without the data being filtered first.
        if ( $wheels && is_array( $wheels ) ) {
            foreach ( $wheels as $w1=>$w2 ) {

                // do these first
                // API calls it speed_index, but we call it speed_rating
                if ( isset( $w2['front']['speed_index'] ) ) {
                    $w2['front']['speed_rating'] = $w2['front']['speed_index'];
                    unset( $w2['front']['speed_index'] );
                }
                if ( isset( $w2['rear']['speed_index']  ) ) {
                    $w2['rear']['speed_rating'] = $w2['rear']['speed_index'];
                    unset( $w2['rear']['speed_index'] );
                }

                // lets try to make slug url friendly. special chars should be only: dash, period, underscore, tilde
                $front = gp_if_set( $w2, 'front' );
                $rear = gp_if_set( $w2, 'rear' );
                $staggered = gp_if_set( $w2, 'showing_fp_only' ) == false; // triple equals might not work
                $is_oem = gp_if_set( $w2, 'is_stock' );
                $oem_str = $is_oem ? ' (OEM)' : ''; // for name, not slug

                // trying to make this a little bit URL friendly
                $suffix = '';
                if ( $staggered ) {
                    $suffix .= '-staggered';
                }

                if ( $is_oem ) {
                    $suffix .= '-oem';
                }

                // A note here on wheel names:
                // For fitments, we get the wheel names directly from the API
                // However, for substitution sizes, we have to assemble names from other data, which
                // is a bit tricky because this now requires us to re-do some of the logic and now I don't
                // know how to take care of the really odd edge cases, like vehicles with wheel names in
                // a completely different format. We can redo things like LT275/65R18, or 230/35ZR19,
                // but we can't predict every single other wheel name. Now this means when we copy fitment data
                // and apply substitution sizes, the sub size names might not be in the same format or contain
                // the same information as before. However, if you change something here, be sure to
                // attempt to make the same change inside of the @var Wheel_Set object(s)

                $front_load_index = gp_if_set( $front, 'load_index' );
                $front_speed_rating = gp_if_set( $front, 'speed_rating' ); // api calls it 'speed_index'
                $front_tire = gp_if_set( $front, 'tire' ); // ie 225/50R16, 230/40RZ17
                $front_rim = gp_if_set( $front, 'rim' ); // ie. 7Jx16 ET41
                $front_service_description = $front_load_index . $front_speed_rating;
                $front_wheel_slug = $front_tire . '-' . $front_rim;
                $front_wheel_name = $front_tire;
                if ( $front_service_description ) {
                    $front_wheel_name .= ' (' . $front_service_description . ')';
                }

                $rear_wheel_name = '';

                if ( $staggered ) {

                    $rear_load_index = gp_if_set( $rear, 'load_index' );
                    $rear_speed_rating = gp_if_set(  $rear, 'speed_rating' );
                    $rear_tire = gp_if_set(  $rear, 'tire' ); // ie 225/50R16, 230/40RZ17
                    $rear_rim = gp_if_set(  $rear, 'rim' ); // ie. 7Jx16 ET41
                    $rear_service_description = $rear_load_index . $rear_speed_rating;
                    $rear_wheel_slug = $front_tire . '-' . $rear_rim;
                    $rear_wheel_name = $rear_tire;
                    if ( $rear_service_description ) {
                        $rear_wheel_name .= ' (' . $rear_service_description . ')';
                    }

                    // slug (staggered)
                    $wheel_slug = 'F~' . $front_wheel_slug . 'R~' . $rear_wheel_slug;

                    // name (staggered)
                    $wheel_name = $front_wheel_name . ' / ' . $rear_wheel_name . $oem_str;

                } else {
                    // slug
                    $wheel_slug = $front_wheel_slug;

                    // name
                    $wheel_name = $front_wheel_name . $oem_str;
                }

                // suffix contains staggered and oem if they are true
                $wheel_slug .= $suffix;
                $wheel_slug = str_replace( '/', '-', $wheel_slug );
                $wheel_slug = gp_replace_whitespace( $wheel_slug, '_' );

                // these are indexes we are adding to the raw data returned from the API
                // note that the wheel slug should not be reverse engineered to get fitment parameters
                // instead we'll print it to forms and store in session when somebody selects a size
                // and then use it to find the full set of data, via the slug as an array index.
                // the wheel name is used for front-end display purposes
                $w2['slug'] = $wheel_slug;
                $w2['name'] = $wheel_name;

                // this was added in later... separated names in case we need them..
                $w2['name_front'] = $front_wheel_name;
                $w2['name_rear'] = $rear_wheel_name;

                $wheels_filtered[$wheel_slug] = $w2;
            }
        }

        // Inject MIN sub size diameter.
        // Doing this super early, before these arrays are converted to objects might save a TON
        // of hassle in our construct methods for Vehicle, Fitment_Plural, Fitment_Singular, and Wheel_Set
        // the only maybe tiny downside is that we have to grab the diameter here, we can't use the
        // Wheel_Pair->get_diameter() function. its pretty easy to get the diameter, but if that fn. changes
        // then no guarantee the dev is going to know to change things here

        $diameters = array();

        if ( $wheels_filtered ) {
            foreach ( $wheels_filtered as $slug=>$arr ) {

                // both rim_diameter and tire_diameter seem to be set, but should be the same..
                $d1_r = isset( $arr['front']['rim_diameter'] ) ? $arr['front']['rim_diameter'] : false;
                $d1_t = isset( $arr['front']['tire_diameter'] ) ? $arr['front']['tire_diameter'] : false;

                // rear tires/rims
                $d2_r = isset( $arr['rear']['rim_diameter'] ) ? $arr['rear']['rim_diameter'] : false;
                $d2_t = isset( $arr['rear']['tire_diameter'] ) ? $arr['rear']['tire_diameter'] : false;

                // Be careful. Adding zero or NULL to the array is not good. NULL can happen very easily
                // for non-staggered fitments. The purpose of this array is to find the MINIMUM. It should never
                // be zero.
                if ( $d1_r ) {
                    $diameters[] = (int) $d1_r;
                } else if ( $d1_t ) {
                    $diameters[] = (int) $d1_t;
                }

                if ( $d2_r ) {
                    $diameters[] = (int) $d1_r;
                } else if ( $d2_t ) {
                    $diameters[] = (int) $d1_t;
                }
            }
        }

        // should not be zero unless there are no wheel sets found which I believe is possible
        $min_diameter = min( $diameters );

        if ( $wheels_filtered ) {
            foreach ( $wheels_filtered as $slug=>$arr ) {
                $wheels_filtered[$slug]['min_sub_diameter'] = $min_diameter;
            }
        }

        $ret = array(
            'market_slug' => $market_slug,
            'trim' => $v_trim,
            'stud_holes' => $stud_holes,
            'pcd' => $pcd,
            'bolt_pattern' => $bolt_pattern,
            'lock_type' => gp_if_set( $fd_single, 'lock_type' ), // these appear to be empty in some example results
            'lock_text' => gp_if_set( $fd_single, 'lock_text' ), // these appear to be empty in some example results
            'center_bore' => $center_bore,
            'wheels' => $wheels_filtered, // array of sizes that fit
        );
    }

    gp_cache_set( $cache_key, $ret );
    return $ret;
}