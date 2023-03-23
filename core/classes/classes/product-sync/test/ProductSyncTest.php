<?php

use PHPUnit\Framework\TestCase;

class ProductSyncTest extends TestCase
{
    public function testParseLoadIndex()
    {
        $this->assertEquals(100, Product_Sync::str_decimal_to_int( '100.00000'));
        $this->assertEquals(99, Product_Sync::str_decimal_to_int( '99.'));
        $this->assertEquals(98, Product_Sync::str_decimal_to_int( '98.01'));
        $this->assertEquals(97, Product_Sync::str_decimal_to_int( '97'));
        $this->assertEquals(97, Product_Sync::str_decimal_to_int( 97));
        $this->assertEquals(96, Product_Sync::str_decimal_to_int( '96.0000000'));

        $this->assertEquals([100, 120], Product_Sync::parse_load_index( '100.00000 / 120'));
        $this->assertEquals([100, 231], Product_Sync::parse_load_index( '100.00000-231'));
        $this->assertEquals([100, 45], Product_Sync::parse_load_index( '100.00000,   45'));
    }

    function testParseSpeedRating(){
        $this->assertEquals('H', Product_Sync::parse_speed_rating( 'h' ));
        // $this->assertEquals('H', Product_Sync::parse_speed_rating( '0h' ));
        // $this->assertEquals('Y', Product_Sync::parse_speed_rating( '(Y)' ));
    }

    function testTrueFalse(){

        // true
        $this->assertEquals( true, Product_Sync::true_like_str( '1' ) );
        $this->assertEquals( true, Product_Sync::true_like_str( 'True' ) );
        $this->assertEquals( true, Product_Sync::true_like_str( 'yes' ) );
        $this->assertEquals( false, Product_Sync::true_like_str( 'false' ) );
        $this->assertEquals( false, Product_Sync::true_like_str( 0 ) );
        $this->assertEquals( false, Product_Sync::true_like_str( 0.01 ) );

        // false
        $this->assertEquals( true, Product_Sync::false_like_str( false ) );
        $this->assertEquals( true, Product_Sync::false_like_str( null ) );
        $this->assertEquals( true, Product_Sync::false_like_str( 0 ) );
        $this->assertEquals( true, Product_Sync::false_like_str( '' ) );
        $this->assertEquals( false, Product_Sync::false_like_str( '1' ) );
        $this->assertEquals( false, Product_Sync::false_like_str( 'true' ) );
        $this->assertEquals( false, Product_Sync::false_like_str( true ) );
    }

    function testExtraLoad(){
        $this->assertEquals( true, Product_Sync::is_extra_load( 'Xl' ) );
        $this->assertEquals( true, Product_Sync::is_extra_load( 'XL' ) );
        // would be hard for the function to get this one wrong:
        $this->assertEquals( false, Product_Sync::is_extra_load( '10C/E' ) );
    }

    function testIsZr(){
        $this->assertEquals( false, Product_Sync::is_zr_size( '205/55R16RFT' ) );
        $this->assertEquals( true, Product_Sync::is_zr_size( '205/50ZR17' ) );
    }

    function testCheckInt(){
        $this->assertTrue( Product_Sync::check_int('10', 0, 50), 1);
        $this->assertTrue( Product_Sync::check_int(10, 0, 50), 2);
        $this->assertTrue( Product_Sync::check_int(11, -2, 45), 3);
        $this->assertFalse( Product_Sync::check_int(12, 12, 1), 4);
        $this->assertFalse( Product_Sync::check_int(13, '14', '100'), 5);
    }

    function testDaiParseLoadIndex(){
        $this->assertEquals( [ 121, 119 ], Product_Sync::parse_dai_load_indexes( '10/E 121/119' ) );
        $this->assertEquals( [ 120, 116 ], Product_Sync::parse_dai_load_indexes( '120/116R' ) );
        $this->assertEquals( [ 103, null ], Product_Sync::parse_dai_load_indexes('103T XL' ) );
        $this->assertEquals( [ 104, null ], Product_Sync::parse_dai_load_indexes( '104Y' ) );
        $this->assertEquals( [ 87, null ], Product_Sync::parse_dai_load_indexes( '87H' ) );
        $this->assertEquals( [ null, null ], Product_Sync::parse_dai_load_indexes( 'idk 87H' ) );
        $this->assertEquals( [ null, null ], Product_Sync::parse_dai_load_indexes( '' ) );

        // input str technically invalid but if we just get 101 from this I suppose that's fine.
        $this->assertEquals( [ 101, null ], Product_Sync::parse_dai_load_indexes( '101/9H 34W' ) );
    }

    function testCheckCols(){
        $subject = [
            'a' => 2,
            'b' => 3,
            'c' => 10,
        ];
        $this->assertEquals( [ 'd' ], Product_Sync::check_cols( $subject, [ 'a', 'd' ] ) );
    }

    function testFormatPrice(){
        $this->assertEquals( '3.00', bcadd('', 3, 2));
        $this->assertEquals( '3.00', bcadd('0.00', '3', 2));
        $this->assertEquals( '0.00', bcadd('0.00', '0.00', 2));
        $this->assertEquals( '1.00', bcadd('3.00', '-2.00', 2));
        $this->assertEquals( '', Product_Sync_Compare::format_price( '' ));
        $this->assertEquals( '', Product_Sync_Compare::format_price( '.' ));
        $this->assertEquals( '', Product_Sync_Compare::format_price( '0' ));
        $this->assertEquals( '', Product_Sync_Compare::format_price( '0.' ));
        $this->assertEquals( '', Product_Sync_Compare::format_price( '0.0' ));
        $this->assertEquals( '', Product_Sync_Compare::format_price( '0.00' ));
        $this->assertEquals( '1.00', Product_Sync_Compare::format_price( '1.' ));
        $this->assertEquals( '0.99', Product_Sync_Compare::format_price( '0.99' ));
        $this->assertEquals( '0.99', Product_Sync_Compare::format_price( '.99' ));
        $this->assertEquals( '235.00', Product_Sync_Compare::format_price( '235' ));
        $this->assertEquals( '111.20', Product_Sync_Compare::format_price( '111.2' ));
        $this->assertEquals( '133.33', Product_Sync_Compare::format_price( '133.33' ));
    }

    function testCheckDecimalStr(){
        $this->assertFalse( Product_Sync::check_decimal_str('00')[0], "1");
        $this->assertFalse( Product_Sync::check_decimal_str('0.-')[0], "2");
        $this->assertFalse( Product_Sync::check_decimal_str('.-03')[0], "3");
        $this->assertFalse( Product_Sync::check_decimal_str('sda')[0], "4");
        $this->assertFalse( Product_Sync::check_decimal_str('2.5z')[0], "5");
        $this->assertFalse( Product_Sync::check_decimal_str('05.10')[0], "6");
        $this->assertFalse( Product_Sync::check_decimal_str('5.')[0], "7");
        $this->assertFalse( Product_Sync::check_decimal_str('05.')[0], "8");
        $this->assertTrue( Product_Sync::check_decimal_str('.5')[0], "9");
        $this->assertTrue( Product_Sync::check_decimal_str('0.5')[0], "10");
        $this->assertTrue( Product_Sync::check_decimal_str('.50')[0], "11");
        $this->assertTrue( Product_Sync::check_decimal_str('0.50')[0], "12");
        $this->assertTrue( Product_Sync::check_decimal_str('.50023828724')[0], "13");
        $this->assertTrue( Product_Sync::check_decimal_str('0.23142321')[0], "14");
        $this->assertTrue( Product_Sync::check_decimal_str('0.23142321')[0], "15");
        $this->assertTrue( Product_Sync::check_decimal_str('1234')[0], "16");
        $this->assertTrue( Product_Sync::check_decimal_str('1234.5631')[0], "17");
        $this->assertTrue( Product_Sync::check_decimal_str('-.5')[0], "18");
        $this->assertTrue( Product_Sync::check_decimal_str('-0.5')[0], "19");
        $this->assertTrue( Product_Sync::check_decimal_str('-.50')[0], "20");
        $this->assertTrue( Product_Sync::check_decimal_str('-0.50')[0], "21");
        $this->assertTrue( Product_Sync::check_decimal_str('-.50023828724')[0], "22");
        $this->assertTrue( Product_Sync::check_decimal_str('-0.23142321')[0], "23");
        $this->assertTrue( Product_Sync::check_decimal_str('-0.23142321')[0], "24");
        $this->assertTrue( Product_Sync::check_decimal_str('-1234')[0], "25");
        $this->assertTrue( Product_Sync::check_decimal_str('-1234.5631')[0], "26");
    }

    function testComputePrice(){
        $this->assertEquals( false, \PS\PriceRules\compute_price( '', '10', '10')[0] );
        $this->assertEquals( false, \PS\PriceRules\compute_price( '400.00', '', '')[0] );
        $this->assertEquals( false, \PS\PriceRules\compute_price( '401.00', '', '100')[0] );
        $this->assertEquals( '130.00', \PS\PriceRules\compute_price( '100', '20', '10')[0] );
        $this->assertEquals( '506.00', \PS\PriceRules\compute_price( '500.00', '1', '1')[0] );
        $this->assertEquals( '-1.00', \PS\PriceRules\compute_price( '500.00', '-100', '-1')[0] );
        $this->assertEquals( '155.01', \PS\PriceRules\compute_price( '155.01', '0.00', '0')[0] );
    }

    function testVisionBoltPattern(){
        $tests = [
            [ '6-5.5 (6-139.7)', '6x139.7' ],
            [ '8-170', '8x170' ],
            [ '5-5 (5-127)', '5x127' ],
            [ '5-4.75 (5-120.65)', '5x120.65' ],
            [ '6-6.5 (165.1)', '' ],
            [ ' trim 6-6.5 ( 5x165.1) ', '5x165.1' ],
            [ '5-May', '' ],
            [ 'Apr-98', '' ],
            [ '', '' ],
        ];

        foreach ( $tests as $index => $arr ) {
//            $info = [];
//            $actual = Product_Sync_Vision_Wheel_CA::parse_vision_bolt_pattern( $arr[0], $info );
//            print_r( $info );
            $this->assertEquals( $arr[1], Product_Sync_Vision_Wheel_CA::parse_vision_bolt_pattern( $arr[0] ), "Index $index" );
        }
    }

    function testVisionModel(){

        $tests = [
            [ '8-210 SB LIFTED VIS RIVAL', 'RIVAL' ],
            [ '8-210 GBMF LIFT FIT VISRIVAL', 'RIVAL' ],
            [ '5-150 CHR VOR RAZOR TEST123 ', 'RAZOR TEST123' ],
            [ '8-200 GBMF LIFTED VI RIVAL', '' ],
            [ '4-100/108 CHR BANE', '' ],
            [ 'SPYDER', '' ],
        ];

        foreach ( $tests as $index => $arr ) {
            $this->assertEquals( $arr[1], Product_Sync_Vision_Wheel_CA::parse_vision_model( $arr[0] ), "Index $index" );
        }
    }

    function testVarious(){
        $this->assertEquals( [
            'k1' => 23
        ], Product_Sync::filter_keys( [
            'k1' => 23,
            'k2' => 55,
        ], [ 'k1' ] ) );
    }

    function testWheelProsFinish(){

        $tests = [
            "SILVER W/ MIRROR CUT LIP" => [ "SILVER", "MIRROR CUT LIP", '' ],
            "GLOSS BLACK WITH MILLED LIP" => [ "GLOSS BLACK", "MILLED LIP", '' ],
            "MATTE BLACK W/ MACHINE FACE & DARK TINT" => [ "MATTE BLACK", "MACHINE FACE", "DARK TINT"],
            "MATTE BLACK W/ MACHINE FACE WITH SOMETHING & SOMETHING ELSE AND WHATEVER" =>
                [ "MATTE BLACK", "MACHINE FACE", "SOMETHING & SOMETHING ELSE & WHATEVER"],
        ];

        foreach ( $tests as $in => $out ) {
            $this->assertEquals( $out, Product_Sync_Wheelpros_Wheel_CA_1::parse_wheelpros_finish( $in ), $in );
        }
    }

    function testWheelProsParseModel(){

        // should strip the brand and model code prefixes, whether
        // there is a space between, a dash, or nothing, and should
        // trim the model name after possibly removing the prefix.
        $tests = [
            'AB,125,AB-125 MODEL NAME' => 'MODEL NAME',
            'AB,,AB-125 MODEL NAME' => 'AB-125 MODEL NAME',
            ',,AB-125 MODEL NAME' => 'AB-125 MODEL NAME',
            'AB,125,MODEL NAME' => 'MODEL NAME',
            // test space after
            'AB,125,AB125 MODEL NAME  ' => 'MODEL NAME',
            'AB,125,AB125MODEL NAME' => 'MODEL NAME',
            'AB,125,AB 125MODEL NAME' => 'MODEL NAME',
            'AB,125,AB 125  MODEL NAME' => 'MODEL NAME',
            'AB,125,MODEL NAME WITHOUT A PREFIX' => 'MODEL NAME WITHOUT A PREFIX',
        ];

        foreach ( $tests as $input => $expected ) {

            list( $brand_code, $model_code, $model ) = explode( ',', $input );

            $result = Product_Sync_Wheelpros_Wheel_CA_1::get_model( $model, $brand_code, $model_code );
            $this->assertEquals( $expected, $result, $input );

        }
    }

    function test_fastco_bolt_pattern_parsing(){
        $this->assertEquals(Product_Sync_Fastco_Wheel_CA::parse_fastco_bolt_pattern(''), ['', '']);
        $this->assertEquals(Product_Sync_Fastco_Wheel_CA::parse_fastco_bolt_pattern(' 5x110.3 '), ['5x110.3', '']);
        $this->assertEquals(Product_Sync_Fastco_Wheel_CA::parse_fastco_bolt_pattern('5x110.3 / 1468.0 '), ['5x110.3', '5x1468.0']);
        $this->assertEquals(Product_Sync_Fastco_Wheel_CA::parse_fastco_bolt_pattern('10x250/100 '), ['10x250', '10x100']);
        $this->assertEquals(Product_Sync_Fastco_Wheel_CA::parse_fastco_bolt_pattern(' 5x110.3 / 6x110 '), ['5x110.3', '6x110']);
    }
}
