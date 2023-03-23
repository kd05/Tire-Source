<?php

/**
 * https://developer.wheel-size.com
 */
define( 'WHEEL_SIZE_API_KEY', '354e85f6c407386bfc84f3ae08c0a77f' );

// could be int 0 or empty string, but not false. not sure about null.
define( '__DEFINED_FALSE', '' );

define( 'APP_LOCALE_CANADA', 'CA' );
define( 'APP_LOCALE_US', 'US' );

/**
 * used in $_SESSION, $_COOKIE
 */
define( 'APP_LOCALE_SESSION_COOKIE_INDEX' , 'cw_locale' );

/**
 * Supplier Emails: @see /core/classes/models/db-supplier.php
 */

define( 'ASSETS_DIR', BASE_DIR . '/assets' );
define( 'IMAGES_DIR', ASSETS_DIR . '/images' );
define( 'VIDEOS_DIR', ASSETS_DIR . '/videos' );

// Composer
define( 'COMPOSER_DIR', BASE_DIR . '/vendor' );

// Assets
define( 'ASSETS_URL', BASE_URL . '/assets' );
define( 'IMAGES_URL', ASSETS_URL . '/images' );
define( 'VIDEOS_URL', ASSETS_URL . '/videos' );
define( 'CSS_URL', ASSETS_URL . '/css' );
define( 'JS_URL', ASSETS_URL . '/js' );

// Ajax Url
define( 'AJAX_URL', BASE_URL . '/ajax.php' );

/**
 * Moneris API may live here.
 */
define( 'LIB_DIR', BASE_DIR . '/lib' );

// allow LOG_DIR to get defined inside of _env.php if needed.
if ( ! defined( 'LOG_DIR' ) ) {

    /**
     * see @var Logger
     */
    define( 'LOG_DIR', dirname( BASE_DIR ) . '/tiresource-logs' );
}

if ( ! file_exists( LOG_DIR ) ) {
    mkdir( LOG_DIR, 0755 );
}

// ensure the .htaccess file exists, in case LOG_DIR is publicly accessible, which it might not be.
if ( ! file_exists( LOG_DIR . '/.htaccess' ) ) {
    file_put_contents( LOG_DIR . '/.htaccess', "Deny From All", FILE_USE_INCLUDE_PATH );
}

/**
 * Moneris payment gateway api
 */
define( 'MONERIS_DIR', LIB_DIR . '/moneris' );

/**
 * Templates are stored outside of public access, but may be included
 * by other files, and normally contain html that's meant to be displayed.
 * They should still exit on direct access.
 */
define( 'TEMPLATES_DIR', CORE_DIR . '/templates' );

/**
 * Admin Template files have a different directory.
 */
define( 'ADMIN_TEMPLATES', CORE_DIR . '/admin-templates' );

/**
 * Admin Url.
 */
define( 'ADMIN_URL', BASE_URL . '/cw-admin' );

/**
 * Admin uploads store things such as CSV files used for product imports.
 *
 * In case of errors with the imports, it's nice to have these files persist.
 *
 * Since it exists inside the logs dir, we don't have to create an .htaccess file.
 */
define( 'ADMIN_UPLOAD_DIR', LOG_DIR . '/admin-uploads' );

if ( ! file_exists( ADMIN_UPLOAD_DIR ) ) {
    mkdir( ADMIN_UPLOAD_DIR, 0755 );
}

/**
 * Database Tables
 *
 * the Database_PDO object uses these constants to store class properties,
 * so if you change them that's fine, but if you add or remove, then see
 * @var DatabasePDO. Also, see: @var DB_Table::map_table_to_class()
 * and finally, if the table has a class which extends DB_Table, make sure
 * you define the table inside of that class as well.
 * Table column definitions exist in the class which extends DB_Table, so
 * you don't have to use that, but if you set it up correctly, you can
 * create the class, and use ClassName/DB_Table:: db_init_create_table_if_not_exists()
 */

define( 'DB_users', 'users' );
define( 'DB_orders', 'orders' );
define( 'DB_transactions', 'transactions' );
define( 'DB_order_items', 'order_items' );
define( 'DB_order_vehicles', 'order_vehicles' );
define( 'DB_reviews', 'reviews' );
define( 'DB_regions', 'regions' );
define( 'DB_tax_rates', 'tax_rates' );
define( 'DB_shipping_rates', 'shipping_rates' );
define( 'DB_tires', 'tires' );
define( 'DB_tire_brands', 'tire_brands' );
define( 'DB_tire_models', 'tire_models' );
define( 'DB_rims', 'rims' );
define( 'DB_rim_brands', 'rim_brands' );
define( 'DB_rim_models', 'rim_models' );
define( 'DB_cache', 'cache' );
define( 'DB_amazon_processes', 'amazon_processes' );
define( 'DB_order_emails', 'order_emails' );
define( 'DB_sub_sizes', 'sub_sizes' );
define( 'DB_suppliers', 'suppliers' );
define( 'DB_options', 'options' );
define( 'DB_pages', 'pages' );
define( 'DB_page_meta', 'page_meta' );
define( 'DB_stock_updates', 'stock_updates' );
define( 'DB_rim_finishes', 'rim_finishes' );

/**
 * $_SESSION indexes.
 *
 * Shopping carts are stored under different $_SESSION indexes based on locale.
 *
 */
// this one not used I think.
// ie. $_SESSION['cart'] = ...
define( 'SESSION_CART', 'cart' );
define( 'SESSION_CART_CA', 'cart_ca' );
define( 'SESSION_CART_US', 'cart_us' );

// when localizing rim images from a URL to the website, do we use
// the same name and override, or do we append a number to the filename?
// this may no longer apply to how we now handle images.
define( 'RIM_FINISH_IMAGES_OVERRIDE_EX', false );

/**
 * see CORE_DIR . '/inc/functions-nonce.php'.
 */
define( 'NONCE_TIME', 7200 );

/**
 * Technically, this doesn't have to be half of NONCE_TIME, but
 * half works fine.
 */
define( 'NONCE_HALF_TIME', 3600 );

/**
 * constants
 */
define( 'MM_TO_INCHES', 0.0393701 );

// user gets locked with status of 1 after this many
define( 'MAX_FAILED_LOGIN_ATTEMPTS', 10 );

// we will not include these in the list of available vehicle makes
global $exclude_makes_array;
$exclude_makes_array = array(
	'Alpine',
	'Aro',
	'Baic',
	'Baojun',
	'Beiqi',
	'Beiqi Huansu',
	'Beiqi Weiwang',
	'Borgward',
	'BYD',
	'Changan',
	'Changhe',
	'Chery',
	'Ciimo',
	// one of these 2 will work
	'Citroen',
	'Citroën',
	'Dacia',
	'Denza',
	'Dongfeng',
	'DS',
	'Everus',
	'FAW',
	'FAW Audi',
	'FAW Mazda',
	'FAW Toyota',
	'FAW Volkswagen',
	'Foday',
	'Force',
	'Foton',
	'GAC',
	'GAC Fiat',
	'GAC Honda',
	'GAC Toyota',
	'GAZ',
	'Geely',
	'Gratour',
	'Great Wall',
	'Haval',
	'Hawtai',
	'Hindustan',
	'Holden',
	'Huanghai',
	'Huasong',
	'Iveco',
	'JAC',
	'Jinbei',
	'JMC',
	'Kinglong',
	'LADA',
	'Lifan',
	'Luxgen',
	'Lynk&co',
	'Mahindra',
	'Maruti',
	'Maxus',
	'Mosler',
	'Perodua',
	'Proton',
	'Qiteng',
	'Qoros',
	'Ravon',
	'Red Flag',
	'Renault Samsung',
	'Riich',
	'Roewe',
	'Rover',
	'Seat',
	'Scoda',
	'Soueast',
	'Ssangyong',
	'TagAZ',
	'Tata',
	'UAZ',
	'Vauxhall',
	'VAZ',
	'Venucia',
	'Victory',
	'Vortex',
	'Wuling',
	'Yema',
	'ZAZ',
	'Brilliance',
	'Zotye Jiangnan',
	'ZX',
	// new brands added oct 2019:
	'Cupra',
	'Daewoo',
	'Daihatsu',
	'Enovate',
	'Enranger',
	'Haima',
	'Karry',
	'Landwind',
	'LDV',
	'LEVC',
	'Nio',
	'Panoz',
	'Peugeot',
	'Qiantu',
	'Rely',
	'Skoda',
	'Wey',
	'Xpeng',
);

function get_all_vehicle_makes(){
    return [
        'acura' => "Acura",
        'aiways' => "Aiways",
        'alfa-romeo' => "Alfa Romeo",
        'aston-martin' => "Aston Martin",
        'audi' => "Audi",
        'baw' => "BAW",
        'bentley' => "Bentley",
        'bjev' => "BAIC BJEV",
        'bmw' => "BMW",
        'bmw-alpina' => "BMW Alpina",
        'bugatti' => "Bugatti",
        'buick' => "Buick",
        'cadillac' => "Cadillac",
        'cheryexeed' => "CheryExeed",
        'chevrolet' => "Chevrolet",
        'chrysler' => "Chrysler",
        'datsun' => "Datsun",
        'dodge' => "Dodge",
        'dr' => "DR",
        'eagle' => "Eagle",
        'exeed' => "Exeed",
        'ferrari' => "Ferrari",
        'fiat' => "Fiat",
        'fisker' => "Fisker",
        'ford' => "Ford",
        'genesis' => "Genesis",
        'geo' => "GEO",
        'gmc' => "GMC",
        'honda' => "Honda",
        'huansu' => "Huansu",
        'hummer' => "Hummer",
        'hyundai' => "Hyundai",
        'infiniti' => "Infiniti",
        'isuzu' => "Isuzu",
        'jaguar' => "Jaguar",
        'jeep' => "Jeep",
        'jetour' => "Jetour",
        'jetta' => "Jetta",
        'keyton' => "Keyton",
        'kia' => "Kia",
        'lamborghini' => "Lamborghini",
        'lancia' => "Lancia",
        'land-rover' => "Land Rover",
        'lexus' => "Lexus",
        'lincoln' => "Lincoln",
        'lotus' => "Lotus",
        'man' => "MAN",
        'maserati' => "Maserati",
        'maybach' => "Maybach",
        'mazda' => "Mazda",
        'mclaren' => "McLaren",
        'mercedes' => "Mercedes-Benz",
        'mercedes-maybach' => "Mercedes-Maybach",
        'mercury' => "Mercury",
        'mg' => "MG",
        'mini' => "MINI",
        'mitsubishi' => "Mitsubishi",
        'nissan' => "Nissan",
        'oldsmobile' => "Oldsmobile",
        'opel' => "Opel",
        'plymouth' => "Plymouth",
        'polaris' => "Polaris",
        'polestar' => "Polestar",
        'pontiac' => "Pontiac",
        'porsche' => "Porsche",
        'ram' => "Ram",
        'red-flag' => "Hongqi",
        'renault' => "Renault",
        'rolls-royce' => "Rolls-Royce",
        'saab' => "Saab",
        'saturn' => "Saturn",
        'scion' => "Scion",
        'seres' => "Seres",
        'smart' => "Smart",
        'subaru' => "Subaru",
        'suzuki' => "Suzuki",
        'tesla' => "Tesla",
        'toyota' => "Toyota",
        'vinfast' => "VinFast",
        'volkswagen' => "Volkswagen",
        'volvo' => "Volvo",
        'weichai' => "Weichai",
        'weiwang' => "Weiwang",
        'zedriv' => "Zedriv",
        'zotye' => "Zotye",
    ];
}

define( 'DISABLE_MOUNT_BALANCE', false );
define( 'DISABLE_LOCALES', false );

/**
 * When true, we'll change some things on the u.s. portion of the site.
 */
define( 'US_TIRES_HAVE_NO_INVENTORY', true );

/**
 * add to url of css/js to prevent cache
 */
if ( IN_PRODUCTION ) {
    define( '_SCRIPTS_VERSION', '2.35' );
} else {
    define( '_SCRIPTS_VERSION', 'timestamped-' . time() );
}

/**
 * Temporarily (or permanently) sending inventory level of zero for
 * all "the wheel group" products to amazon. Note that TWG is
 * sometimes referred to as "wheel-1" (this is our supplier slug)
 */
define( 'SEND_ZERO_INVENTORY_TO_AWS_FOR_TWG', false );

define( 'IS_WFL', false );
