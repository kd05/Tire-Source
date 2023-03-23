<?php

$sitemap = new App_Sitemap();;
$sitemap->build();

$arr = $sitemap->get_depth_1_array();

var_dump( count( $arr ) );

$xml = $sitemap->to_xml();

$sitemap->write_to_root_directory();

echo get_pre_print_r( $xml, true );

echo nl2br( "----------------------- \n" );
echo $sitemap->render_html_tables( false );
