<?php

/**
 * Tire models and rim finishes share a handful of similar functions
 * because they both contain data for product images.
 *
 * Trait DB_Tire_Model_Or_Rim_Finish
 */
Trait DB_Tire_Model_Or_Rim_Finish{

    public static $image_relative_paths = [
        'tire' => [
            'full' => '/assets/images/tires/full',
            'reg' => '/assets/images/tires/reg',
            'thumb' => '/assets/images/tires/thumb',
        ],
        'rim' => [
            'full' => '/assets/images/rims/full',
            'reg' => '/assets/images/rims/reg',
            'thumb' => '/assets/images/rims/thumb',
        ]
    ];

    /**
     * ie. /assets/images/rims/reg
     *
     * @param $filename - possibly comes from $this->get( 'image_local' )
     * @param string $size - size indicator, 'full', 'reg', or 'thumb'
     * @param bool $false_if_no_filename
     * @return bool|string
     */
    public static function get_image_path_relative_( $filename, $size = 'reg', $false_if_no_filename = true ) {

        if ( ! in_array( $size, [ 'full', 'reg', 'thumb' ] ) ) {
            throw_dev_error( 'invalid image size passed in: ' . $size );
        }

        if ( $false_if_no_filename && ! trim( $filename ) ) {
            return false;
        }

        if ( static::$tire_model_or_rim_finish_type === 'tire' ) {
            return self::$image_relative_paths['tire'][$size] . '/' . $filename;
        } else {
            return self::$image_relative_paths['rim'][$size] . '/' . $filename;
        }
    }

    /**
     * @param $filename - Possibly pass in $this->get( 'image_local' )
     * @param string $size - size indicator, 'full', 'reg', or 'thumb'
     * @param bool $false_if_no_filename
     * @return bool|string
     */
    public static function get_image_path_( $filename, $size = 'reg', $false_if_no_filename = true ) {

        $rel = self::get_image_path_relative_( $filename, $size, $false_if_no_filename );

        // can only happen if $false_if_no_filename is true
        if ( ! $rel ){
            return false;
        }

        return rtrim( BASE_DIR, '/' ) . $rel;
    }

    /**
     * @param $filename - Possibly pass in $this->get( 'image_local' )
     * @param string $size - size indicator, 'full', 'reg', or 'thumb'
     * @param bool $false_if_no_filename
     * @return bool|string
     */
    public static function get_image_url_( $filename, $size, $false_if_no_filename = true ) {

        $rel = self::get_image_path_relative_( $filename, $size, $false_if_no_filename );

        // can only happen if $false_if_no_filename is true
        if ( ! $rel ){
            return false;
        }

        return rtrim( BASE_URL, '/' ) . $rel;
    }

    /**
     * @param bool $sanitize
     * @return bool|mixed|string
     */
    public function get_image_filename( $sanitize = true ){

        if ( static::$tire_model_or_rim_finish_type === 'tire' ) {
            $ret = $this->get( 'tire_model_image' );
        } else {
            $ret = $this->get( 'image_local' );
        }

        // because we only store the images without special characters, it's
        // fine to use gp_test_input, it should not break any URLs
        return $sanitize ? gp_test_input( $ret ) : $ret;
    }

    /**
     * @param string $size
     * @param bool $fallback_to_not_found_image
     * @return bool|string
     */
    public function get_image_url( $size = 'reg', $fallback_to_not_found_image = true ) {

        $filename = $this->get_image_filename(true);

        $url = self::get_image_url_( $filename, $size, true );
        $path = self::get_image_path_( $filename, $size, true );

        if ( $path && file_exists( $path ) ) {
            return $url;
        }

        if ( $fallback_to_not_found_image ) {
            return image_not_available();
        }

        return false;
    }

    /**
     * Gets filesize in bytes of a rim finish image,
     *
     * $size can be 'full', 'reg', 'thumb'.
     *
     * This function is also a good way to check if the file
     * exists.
     *
     * @param string $size
     * @return false|int
     */
    public function get_image_filesize( $size = 'reg' ) {

        $filename = $this->get_image_filename(true);

        // when $image_name = '', $path ends up being a directory
        $path = self::get_image_path_( $filename, $size, true );

        // "$path &&" is VERY not redundant.
        return $path && file_exists( $path ) ? filesize( $path ) : 0;
    }

    /**
     * @param string $size
     * @return bool|string
     */
    public function get_image_path( $size = 'reg' ){

        $filename = $this->get_image_filename(true);

        if ( ! $filename ) {
            return false;
        }

        return self::get_image_path_( $filename, $size );
    }
}
