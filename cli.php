<?php
/**
 * ie.
 * php -a
 * include 'cli.php';
 * ... execute some code
 */

// tells the code to skip some things that would require database tables
// to exist, like clearing database cache, checking logged in user etc. This lets
// us use the CLI to insert database tables. It is necessary that the database
// exists and is running and you entered credentials for it (but not necessary that
// all required tables exist).
define( 'DOING_DB_INIT', true );

if ( defined( 'PHP_SAPI' ) && PHP_SAPI === 'cli' ) {
    include './core/_init.php';

    // I don't see much point in disabling it in production but for now
    // we don't use it there.
    if ( IN_PRODUCTION ) {
        echo "Disabled";
        exit;
    }
}
