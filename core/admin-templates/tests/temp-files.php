<?php

require_once CORE_DIR . '/libs/images.php';

//phpinfo();
//exit;

ini_set( 'MEMORY_LIMIT', '512M' );

$dir = dirname( __FILE__ );

if ( file_exists( $dir . '/big-img.png' ) ) {
    $f1 = $dir . '/big-img.png';
    $f2 = $dir . '/big-img-2.png';
} else {
    $f1 = $dir . '/big-img-2.png';
    $f2 = $dir . '/big-img.png';
}

var_dump( file_exists( $f1 ) );
var_dump( file_exists( $f2 ) );

$mem = new Time_Mem_Tracker();

copy( $f1, $f2 );
unlink( $f1 );

$mem->breakpoint( 'copy/del' );

$image = new \Libs\Images\ImageResize($f2);
$image->resizeToBestFit(1200, 1200);
$image->save($dir . '/a-' . time() . '.png' );

$mem->breakpoint( 'md' );

$image = new \Libs\Images\ImageResize($f2);
$image->resizeToBestFit(111, 111);
$image->save($dir . '/abc-' . time() . '.png' );

$mem->breakpoint( 'thumb' );

echo $mem->display_summary();


