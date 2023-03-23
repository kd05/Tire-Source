<?php
/**
 * An example of the _env.php file which is git ignored but required in each environment.
 *
 * See core/_init.php for more info.
 *
 * On the live website, you only need one constant: USE_LIVE_CONFIG, true
 */

define( 'IN_PRODUCTION', false );

define( 'BASE_URL', 'http://localhost:8080/your-local-website-url' );

// in dev, $_SERVER['REQUEST_URI'] might be 'tiresource/wheels',
// so define the directory name here to ignore 'tiresource', so
// that our router understands the URI is only /wheels.
// on live, this should be an empty string.
// In dev, if this is empty, pages probably won't work.
define( 'REQUEST_URI_PREFIX_IGNORE', '' );

define( 'SITE_DEVELOPER_EMAIL', 'your_email@email.com' );

// required db creds
define( 'CW_DB_HOST', 'your.database.host' );
define( 'CW_DB_USERNAME', 'your_db_user' );
define( 'CW_DB_DATABASE', 'your_db_db' );
define( 'CW_DB_PASSWORD', 'your_db_pass' );

// optional SMTP
define( 'SMTP_IS', false );
define( 'SMTP_HOST', '' );
define( 'SMTP_AUTH', false );
define( 'SMTP_USER', '' );
define( 'SMTP_PASS', '' );
define( 'SMTP_SECURE', '' );
define( 'SMTP_PORT', 0 );
