<?php

use PHPUnit\Framework\TestCase;

class ProductImageTest extends TestCase
{
    public function testConvertUrl(){
        $this->assertEquals( 'filename.jpg', Product_Images::possibly_convert_to_url( "filename.jpg" ));
        $this->assertEquals( 'filename test 123 ++ /.jpg', Product_Images::possibly_convert_to_url( "filename test 123 ++ /.jpg" ));
        $this->assertEquals( 'https://example-site.com/image.jpeg', Product_Images::possibly_convert_to_url( "https://example-site.com/image.jpeg" ));
        $this->assertEquals( 'https://example-site.com/image.jpeg', Product_Images::possibly_convert_to_url( "example-site.com/image.jpeg" ));
        $this->assertEquals( 'https://www.example-site.com/image+123-55.jpeg', Product_Images::possibly_convert_to_url( "www.example-site.com/image+123-55.jpeg" ));
    }
}