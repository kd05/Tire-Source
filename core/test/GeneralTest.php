<?php

use PHPUnit\Framework\TestCase;

class GeneralTest extends TestCase {

    // used to not be using phpunit, so this helper is
    // just a way to move tests to phpunit with minimal re-factoring
    // of already existing tests. You wouldn't use this otherwise.
    function assert( $val, $msg = 'No msg' ) {

        $this->assertEquals( true, $val, $msg );
    }

    function test_router_parse_url() {

        $this->assert( Router::parse_url( '/hi?bye=123' ) === [ [ 'hi' ], [ 'bye' => '123' ] ] );
        $this->assert( Router::parse_url( 'hi?bye=123' ) === [ [ 'hi' ], [ 'bye' => '123' ] ] );
        $this->assert( Router::parse_url( '/wheels/brand/model?color_1=c1' ) === [ [ 'wheels', 'brand', 'model' ], [ 'color_1' => 'c1' ] ] );
        $this->assert( Router::parse_url( '/wheels/brand/model/?color_1=c1&color_2=c2' ) === [ [ 'wheels', 'brand', 'model' ], [ 'color_1' => 'c1', 'color_2' => 'c2' ] ] );
    }

    function test_cw_add_query_arg() {

        $out = cw_add_query_arg( [ 'another' => 100 ], 'https://site.com/page/?param=23&other=55' );
        $this->assert( $out === 'https://site.com/page/?param=23&other=55&another=100' );

        $out = cw_add_query_arg( [ 'another' => 100 ], 'https://site.com/page?param=23&other=55' );
        $this->assert( $out === 'https://site.com/page?param=23&other=55&another=100' );
    }

    function test_router_prev_urls_redirect() {

        $this->assert( Router::prev_urls_redirect( '/tires.php?brand=hi' ) === [ BASE_URL . '/tires/hi', 301 ] );
        $this->assert( Router::prev_urls_redirect( '/tires/?brand=hi' ) === [ BASE_URL . '/tires/hi', 301 ] );
        $this->assert( Router::prev_urls_redirect( '/tires?brand=hi' ) === [ BASE_URL . '/tires/hi', 301 ] );
        $this->assert( Router::prev_urls_redirect( 'tires?brand=hi' ) === [ BASE_URL . '/tires/hi', 301 ] );
        $this->assert( Router::prev_urls_redirect( 'tires?brand=hi&other=123' ) == false );
        $this->assert( Router::prev_urls_redirect( 'tires?type=summer' ) === [ BASE_URL . '/tires/summer', 301 ] );
        $this->assert( Router::prev_urls_redirect( 'tires?width=215&profile=60&diameter=16' ) === [ BASE_URL . '/tires/215-60R16', 301 ] );
        $this->assert( Router::prev_urls_redirect( 'tires/brand/model?part_number=12345' ) === [ BASE_URL . '/tires/brand/model/12345', 301 ] );
        $this->assert( Router::prev_urls_redirect( 'wheels/brand/model/?color_1=c1' ) === [ BASE_URL . '/wheels/brand/model/c1', 301 ] );
        $this->assert( Router::prev_urls_redirect( 'wheels/brand/model?color_1=c1' ) === [ BASE_URL . '/wheels/brand/model/c1', 301 ] );
        $this->assert( Router::prev_urls_redirect( 'wheels/brand/model?color_1=c1&color_2=c2' ) === [ BASE_URL . '/wheels/brand/model/c1-with-c2', 301 ] );
        $this->assert( Router::prev_urls_redirect( 'wheels/brand/model?color_1=c1&color_2=c2&finish=ff' ) === [ BASE_URL . '/wheels/brand/model/c1-with-c2-and-ff', 301 ] );
        $this->assert( Router::prev_urls_redirect( 'wheels/brand/model?color_1=c1&part_number=12345' ) === [ BASE_URL . '/wheels/brand/model/c1/12345', 301 ] );
        $this->assert( Router::prev_urls_redirect( 'wheels/brand/model?color_1=c1&color_2=c2&part_number=12345' ) === [ BASE_URL . '/wheels/brand/model/c1-with-c2/12345', 301 ] );
        $this->assert( Router::prev_urls_redirect( 'wheels/brand/model?color_1=c1&color_2=c2&finish=ff&part_number=12345' ) === [ BASE_URL . '/wheels/brand/model/c1-with-c2-and-ff/12345', 301 ] );
    }

    function test_parse_rim_finish_url_segment() {

        $this->assert( parse_rim_finish_url_segment( '' ) === [ '', '', '' ] );
        $this->assert( parse_rim_finish_url_segment( 'gloss-black' ) === [ 'gloss-black', '', '' ] );
        $this->assert( parse_rim_finish_url_segment( 'gloss-black-with-stuff' ) === [ 'gloss-black', 'stuff', '' ] );
        $this->assert( parse_rim_finish_url_segment( 'gloss-black-with-stuff-and-more-stuff' ) === [ 'gloss-black', 'stuff', 'more-stuff' ] );

        // hmmm. Best to do it like this. Not going to explain why.
        $this->assert( parse_rim_finish_url_segment( 'gloss-black-with-' ) === [ 'gloss-black-with-', '', '' ] );
        $this->assert( parse_rim_finish_url_segment( 'gloss-black-with-stuff-and-' ) === [ 'gloss-black', 'stuff-and-', '' ] );

    }
}



