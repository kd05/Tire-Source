<?php
/**
 * I run this file in developement to copy all files from WFL product
 * sync to click it wheels product sync.
 *
 * Note: this is quite dangerous if changes were made on CW, it will silently
 * override all changes in the product sync folder. Use with caution.
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

$source = CORE_DIR . '/classes/product-sync';
$dest = dirname( BASE_DIR ) . '/cw/core/classes/product-sync';

$idk = \CopyDir\xcopy( $source, $dest, 0755 );

var_dump( $idk );
