<?php
/**
 * Copies all inventory files from WFL to CW in local developement.
 *
 * Can be very destructive. Will override all changes in CW.
 */

include dirname( dirname(__FILE__) ) . '/_init.php';
include CORE_DIR . '/inc/copy-dir.php';

if ( IN_PRODUCTION ) {
    echo "Not allowed in production.";
    exit;
}

if ( ! IS_WFL ) {
    echo "Works only ON WFL and in developement.";
    exit;
}

$source = CORE_DIR . '/classes/inventory';
$dest = dirname( BASE_DIR ) . '/cw/core/classes/inventory';

$idk = \CopyDir\xcopy( $source, $dest, 0755 );

var_dump( $idk );
