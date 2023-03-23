<?php
/**
 * Bootstrap the app
 */

define( 'BASE_DIR', dirname( dirname(__FILE__ ) ) );

define( 'CORE_DIR', BASE_DIR . '/core' );

/**
 * When true, the app will load while taking into consideration
 * that the database tables might not yet exist. This lets you
 * call the init_db() function without getting any errors before
 * its defined.
 *
 * Without this, we'd get errors when checking if a user is
 * logged in for example (which we always do on each execution).
 *
 * To use (from CLI)...
 *
 * > php -a
 * > define( "DOING_DB_INIT", true );
 * > include "load.php";
 * > init_db(); // creates all tables that don't exist
 * > init_users_table(); // insert a user so you can log in to the back-end
 *
 * Note: When creating all tables, sometimes you have to call init_db() twice.
 * I know this sounds ridiculous, but, its because of complicated foreign key
 * relationships, so some tables don't get created on first pass, but if you just
 * call it twice, it should insert everything. I haven't had time to work out
 * the table dependencies and give it the proper insertion order.
 *
 * Note: before creating tables, drop all the tables which you want to
 * re-create. It will not update table schema / ddl.
 */
if ( ! defined( 'DOING_DB_INIT' ) ) {
    define( 'DOING_DB_INIT', false );
}

if ( ! defined( 'CW_IS_WP_BLOG' ) ) {
    /**
     * true when we load the app from within /blog.
     *
     * The idea is that WordPress includes the application,
     * but the application certainly does not include WordPress.
     * When we're at /blog, we load both. At every other page,
     * we ignore WordPress.
     */
    define( 'CW_IS_WP_BLOG', false );
}

date_default_timezone_set( 'America/Toronto' );

ini_set("log_errors", 1);

// not working somehow
// ini_set("error_log", "/tmp/php-error.log");

// may use this again for cron jobs to put in a diff file...
// note: we also have a LOG_DIR which is different than this.
define( 'PHP_ERROR_LOG_DIR', dirname( BASE_DIR ) );

// BASE_DIR is public html - put it outside of this obviously.
// give it a unique name because in test environment we may have more than 1 file logging to php-error.log
ini_set( 'error_log', PHP_ERROR_LOG_DIR . '/ciw-php-error.log' );

if ( ! session_id() ) {

    // trying to increase the session duration.
    // note: this may not work flawlessly.
    // @see https://stackoverflow.com/questions/8311320/how-to-change-the-session-timeout-in-php
    $session_duration = 86400;

    // seems to do nothing, but adjusted php.ini on server so maybe that will work
    ini_set('session.gc_maxlifetime', $session_duration);

    // maybe need this also? not sure.
    session_set_cookie_params($session_duration);

    session_name( 'cw_session' );
    session_start();
}

// for debugging session duration stuff
if ( ! isset( $_SESSION['__start'] ) ) {
    $_SESSION['__start'] = date( 'Ymd h:i:sa' );
}

/**
 * Initialize the environment (prod or custom dev)
 *
 * To configure your own dev environment, see _env--example.php
 * in the root directory.
 *
 * In case of issues, @see dump_env_config
 */
call_user_func( function(){

    // A helper to save a few lines of code. I actually sometimes write it out the long way
    // for constants that are not otherwise defined in this file.. this ensures my IDE knows its a constant.
    $set_default_constant = function( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    };

    $assert_defined = function( $name, $msg ){
        if ( ! defined( $name ) ) {
            echo $msg;
            exit;
        }
    };

    $assert_defined_in_file = function( $name ) use( $assert_defined ){
        $assert_defined( $name, "The $name constant must be defined in the _env.php file in the root directory." );
    };

    /**
     * True when loading the app from a php cli.
     *
     * Ie. when using php -a from command line in a dev environment.p
     */
    define( 'IS_CLI', defined( 'PHP_SAPI' ) && PHP_SAPI === 'cli' );

    /**
     * Files that are executed via cron jobs will set this to true.
     */
    if ( ! defined( 'DOING_CRON' ) ) {
        define( 'DOING_CRON', false );
    }

    if ( ! file_exists( BASE_DIR . '/_env.php' ) ) {
        echo "You must create an _env.php file in the root directory and configure it for your specific environment.";
        exit;
    } else {

        /**
         * Note that _env.php should have access to IS_CLI and DOING_CRON constants in case they
         * are needed.
         */
        require BASE_DIR . '/_env.php';
    }

    if ( ! defined( 'ENV' ) ) {
        define( 'ENV', "__CUSTOM" );
    }

    /**
     * On the live website, we'll setup only this constant, and then
     * put the config in this file.
     *
     * For other envs, you'll have to do config in _env.php
     */
    if ( defined( 'USE_LIVE_CONFIG' ) && USE_LIVE_CONFIG ) {

        define( 'IN_PRODUCTION', true );
        define( 'BASE_URL', 'https://tiresource.com' );
        define( 'USE_LIVE_DATABASE_CREDS', true );
        define( 'USE_LIVE_SMTP_CREDS', true );
        define( 'MONERIS_TEST_MODE', false );
        define( 'SITE_DEVELOPER_EMAIL', 'baljeet@geekpower.ca' );

        if ( ! defined ( 'REQUEST_URI_PREFIX_IGNORE' ) ) {
            define( 'REQUEST_URI_PREFIX_IGNORE', '' );
        }

    } else {

        // required in each env
        $assert_defined_in_file( 'BASE_URL' );

        /**
         * When true, supresses many error messages and lots of
         * debug output to the front-end users.
         *
         * 100% required that this is true on the live website.
         *
         * In dev envs, almost certainly you'll want false.
         *
         * Setting to true could risk payments, emails, or amazon stuff
         * acting as though it was the live website.
         *
         * There is no safe default value for this, so it is always required.
         */
        $assert_defined_in_file( 'IN_PRODUCTION' );
    }

    /**
     * When true, uses the Moneris sandbox API.
     */
    $set_default_constant( 'MONERIS_TEST_MODE', ! IN_PRODUCTION );

    /**
     * Database - you must define these in _env.php
     */
    if ( defined( 'USE_LIVE_DATABASE_CREDS' ) && USE_LIVE_DATABASE_CREDS ) {
        define( 'CW_DB_HOST', 'localhost' );
        define( 'CW_DB_USERNAME', '##removed' );
        define( 'CW_DB_DATABASE', '##removed' );
        define( 'CW_DB_PASSWORD', '##removed' );
    } else {
        $assert_defined_in_file( 'CW_DB_HOST' );
        $assert_defined_in_file( 'CW_DB_USERNAME' );
        $assert_defined_in_file( 'CW_DB_DATABASE' );
        $assert_defined_in_file( 'CW_DB_PASSWORD' );
    }

    /**
     * SMTP - you can omit these from your _env.php file
     */
    if ( defined( 'USE_LIVE_SMTP_CREDS' ) && USE_LIVE_SMTP_CREDS ) {

        define( 'SMTP_IS', true );
        define( 'SMTP_HOST', 'smtp.ionos.com' );
        define( 'SMTP_AUTH', true );
        define( 'SMTP_USER', '##removed' );
        define( 'SMTP_PASS', '##removed' );
        define( 'SMTP_SECURE', 'ssl' );
        define( 'SMTP_PORT', 465 );

    } else {

        // the site can/should work without smtp setup, but if/when emails
        // are sent, no guarantee they'll work of course. Certain automated emails
        // won't get triggered when not in production anyways.
        $set_default_constant( 'SMTP_IS', false );
        $set_default_constant( 'SMTP_HOST', "" );
        $set_default_constant( 'SMTP_AUTH', false );
        $set_default_constant( 'SMTP_USER', "" );
        $set_default_constant( 'SMTP_PASS', "" );
        $set_default_constant( 'SMTP_SECURE', "" );
        $set_default_constant( 'SMTP_PORT', "" );
    }

    /**
     * The email of the developer in charge of the hosted environment.
     */
    if ( ! defined( 'SITE_DEVELOPER_EMAIL' ) ) {
        define( 'SITE_DEVELOPER_EMAIL', 'baljeet@geekpower.ca' );
    }

    /**
     * A legacy constant which still has a few usages, but can otherwise
     * be ignored.
     */
    define( 'DEBUG_MODE', ! IN_PRODUCTION );

    /**
     * Almost certainly only true in production, however, from time
     * to time, I may use a CLI in a local environment to try to
     * test something on Amazon. Note that there is no sandbox/testing
     * environment for the MWS API.
     */
    if ( ! defined( 'APP_CAN_UPDATE_AMAZON_MWS' ) ) {
        define( 'APP_CAN_UPDATE_AMAZON_MWS', IN_PRODUCTION );
    }
});

/**
 * If having trouble setting up new environments, it can be useful to
 * print or log this.
 *
 * @return array
 */
function dump_env_config(){

    // not that you should literally every print the output of this function
    // in production. In any case, I'm still going to mask certain sensitive
    // information.
    $mask = function( $str ) {
        return str_pad( "", strlen( $str ), "*" );
    };

    return [
        'IN_PRODUCTION' => IN_PRODUCTION,
        'IS_CLI' => IS_CLI,
        'DOING_CRON' => DOING_CRON,
        'DOING_DB_INIT' => DOING_DB_INIT,
        'BASE_URL' => BASE_URL,
        'SITE_DEVELOPER_EMAIL' => SITE_DEVELOPER_EMAIL,

        'DB_HOST' => CW_DB_HOST,
        'DB_NAME' => CW_DB_DATABASE,
        'DB_USER' => $mask( CW_DB_USERNAME ),
        'DB_PASS' => $mask( CW_DB_PASSWORD ),

        'SMTP_IS' => SMTP_IS,
        'SMTP_HOST' => SMTP_HOST,
        'SMTP_AUTH' => SMTP_AUTH,
        'SMTP_USER' => $mask( SMTP_USER ),
        'SMTP_PASS' => $mask (SMTP_PASS ),
        'SMTP_SECURE' => SMTP_SECURE,
        'SMTP_PORT' => SMTP_PORT,
    ];
}

// a small set of functions that need to be included very early on.
include CORE_DIR . '/other/pre-load-functions.php';

// handle uncaught exceptions. this function is defined in pre-load-functions.php
set_exception_handler( 'app_exception_handler' );

// after pre-load functions, start tracking the script time.
global $pre_load_debug;
$pre_load_debug = array();
$pre_load_debug['script_start'] = get_time_and_mem_usage();

// order matters
include CORE_DIR . '/config.php';
include CORE_DIR . '/constants.php';
include CORE_DIR . '/other/config-amazon.php';
include CORE_DIR . '/includes.php';

// http://php.net/manual/en/function.assert-options.php
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
//assert_options(ASSERT_QUIET_EVAL, 0);
assert_options( ASSERT_BAIL, 1 );
assert_options(ASSERT_CALLBACK, 'app_assert_handler');

/**
 * define urls/filenames for admin section
 */
include 'admin-templates/_register-admin-pages.php';

if ( ! DOING_DB_INIT ) {
    // the first call sort of initializes some stuff related to whether or
    // not a user is logged in. It's perhaps not necessary to do this, however,
    // its been here a long time... don't remove it for no reason.
    $nothing = cw_get_logged_in_user();
}

/**
 * checks session and cookies for app locale (U.S. or Canada), and possibly sets a cookie
 * @see DISABLE_LOCALES
 */
if ( DOING_DB_INIT ) {
    // setting to canada is good enough to initialize database tables
    app_set_locale(APP_LOCALE_CANADA);
} else if ( DOING_CRON ) {
    // used to not matter, but started randomly seeing some error logs about
    // unset $_SERVER vars or something like that, which, i suppose don't get setup
    // when doing cron jobs.
    app_set_locale(APP_LOCALE_CANADA);
} else {
    // check cookies and stuff and determine whether the user is on CA or US
    init_app_locale();
}

// clean expired cache items every X number of seconds
define( 'CACHE_CLEAN_SECONDS', 1800 );

// clear expired cache, unless were trying to init the db
if ( ! DOING_DB_INIT ) {
    gp_cache_check_last_clean();
}

// log some time tracking stuff
if ( $pre_load_debug && is_array( $pre_load_debug ) ) {
    foreach ( $pre_load_debug as $kk=>$vv ) {
        Debug::add( $vv, $kk );
    }
}

// logs the time it took the app to bootstrap itself
Debug::log_time( 'script_after_load' );

// queued dev alerts can be printed conditionally, ie. if were not in production, or if an admin is logged in.
queue_dev_alert( 'Session (after load)', get_pre_print_r( $_SESSION ) );

// include these late
include CORE_DIR . '/other/maintenance.php';
include CORE_DIR . '/other/db-init.php';

queue_dev_alert( "Memory (after script load)", [ get_mem_formatted(), get_peak_mem_formatted() ]);

if ( DOING_DB_INIT ) {
    // for CLI
    echo "DOING_DB_INIT is TRUE. You may want to call the init_db() function. \r\n";
}

include CORE_DIR . '/migrations.php';
