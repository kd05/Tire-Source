<?php

$tracker = new Time_Mem_Tracker();

list( $a, $b, $c ) = MWS_Submit_Inventory_Feed::get_accessories_stock( "CA" );

$tracker->breakpoint("get_stock");

echo get_pre_print_r( $b );

$c = 0;
for ( $x = 0; $x <= 100000; $x++ ) {
    if ( isset( $a[$x] ) ) {
        $c++;
    }
}

$tracker->breakpoint("lookups");

echo $tracker->display_summary();
