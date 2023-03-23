<?php

use Gumlet\ImageResize as ImageResize;
use Curl\Curl as Curl;

/**
 * Tools for downloading and compressing tire and rim images.
 *
 * Class Product_Images
 */
Class Product_Images {

    use Product_Images_Rims;
    use Product_Images_Tires;

    /**
     * @param $path
     * @return mixed
     */
    public static function get_image_type( $path ) {

        $info = getimagesize( $path );

        // one of IMAGETYPE_ constants
        return @$info[ 2 ];
    }

    /**
     * /var/www/html/assets/images/blah.jpg => jpg
     *
     * @param $path
     * @param bool $lower
     * @return string
     */
    public static function extension_from_path( $path, $lower = true ) {

        $info = new \SplFileInfo( $path );
        $e = $info->getExtension();
        $e = $lower ? strtolower( $e ) : $e;
        return $e;
    }

    /**
     * "a.b.c" => "a.b"
     *
     * Should/might accept the full path to a file.
     *
     * @param $filename
     * @return string
     */
    public static function remove_extension( $filename ) {

        $ext = self::extension_from_path( $filename );

        if ( ! $ext ) {
            return $filename;
        }

        // -1 to account for the dot
        return substr( $filename, 0, strlen( $filename ) - strlen( $ext ) - 1 );
    }

    /**
     * https://example.com/image.jpg?thing=1 => 'image'
     *
     * @param $url
     * @return string
     */
    public static function filename_from_url( $url ) {

        return pathinfo( parse_url( $url, PHP_URL_PATH ), PATHINFO_FILENAME );
    }

    /**
     * https://example.com/IMAGE.JPG?thing=1 => 'jpg'
     *
     * @param $url
     * @param bool $lower
     * @return string
     */
    public static function extension_from_url( $url, $lower = true ) {

        // if url was encoded we might see image.jpg%3fformat%3d500w instead of ?format=500w,
        // which screws things up. Hopefully a simply url decode does the trick.
        $_url = rawurldecode( $url );

        $ret = pathinfo( parse_url( $_url, PHP_URL_PATH ), PATHINFO_EXTENSION );
        return $lower ? strtolower( $ret ) : $ret;
    }

    /**
     * Check an image URL for some basic things before we try to download
     * it onto our server. This function might not be safe enough to use
     * on a public facing form (perhaps there are ways to get around the
     * extension check). In our case, admin users are providing the URLs.
     *
     * @param $url
     * @return array
     */
    public static function validate_image_url( $url ) {

        if ( ! $url ) {
            return [ false, "URL is empty" ];
        }

        if ( ! count( array_filter( [
            strpos( $url, 'http://' ) === 0,
            strpos( $url, 'https://' ) === 0,
        ] ) ) ) {
            return [ false, "URL must start with http:// or https://" ];
        }

        $e = self::extension_from_url( $url, true );

        if ( ! in_array( $e, [ 'jpg', 'png', 'jpeg' ] ) ) {
            $_e = htmlspecialchars( $e );
            return [ false, "Invalid Extension ($_e). Must be jpg, jpeg, or png" ];
        }

        return [ true, "" ];
    }

    /**
     * Trying to allow spaces and other special characters inside the filename
     * of an image without destroying any other valid URLs.
     *
     * "http://site.com/image 123.jpg?size=dont-break-this" => "http://site.com/image%20123.jpg?size=dont-break-this"
     *
     * I would bet that this function is not perfect, but it's not trivial unfortunately.
     *
     * @param $url
     * @return string|string[]
     */
    public static function encode_url( $url ) {

        // have to decode first in case url or portion of it was
        // already encoded.
        // note: rawurlencode uses %20 for space, urlencode uses +,
        // I think we want %20.
        $url = rawurlencode( rawurldecode( $url ) );

        $replace = [
            "%3A" => ":",
            "%2F" => "/",
            "%3f" => "?",
            "%26" => "&",
        ];

        foreach ( $replace as $k => $v ) {
            $url = str_replace( $k, $v, $url );
        }

        return $url;
    }

    /**
     * @param $entity
     * @return array
     */
    static function get_image_data( $entity ) {

        assert( $entity instanceof DB_Tire_Model || $entity instanceof DB_Rim_Finish );
        $is_tire = $entity instanceof DB_Tire_Model;

        $image_local = $is_tire ? $entity->get( 'tire_model_image' ) : $entity->get( 'image_local' );
        $image_source = $is_tire ? $entity->get( 'tire_model_image_origin' ) : $entity->get( 'image_source' );
        $image_source_new = $is_tire ? $entity->get( 'tire_model_image_new' ) : $entity->get( 'image_source_new' );

        return [ $image_local, $image_source, $image_source_new ];
    }

    /**
     * Checks if different image sizes are of a minimum size. The reason is that
     * some suppliers return tiny (< 5kb) generic not found images for some
     * deprecated URL's or 404's or whatever. The only way to even try to detect this
     * is just check the image size.
     *
     * This also checks that expected sizes exist (ie. the files exist on server).
     *
     * @param $entity
     * @param int[] $limits
     * @return bool
     */
    static function check_image_filesizes( $entity, $limits = [ 5000, 5000, 2000 ] ) {
        assert( $entity instanceof DB_Tire_Model || $entity instanceof DB_Rim_Finish );

        foreach ( [ 'full', 'reg', 'thumb' ] as $index => $size ) {
            if ( $entity->get_image_filesize( $size ) <= $limits[$index] ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns false if the entity has no images, or if the new source is not the
     * same as the old source.
     *
     * @param $entity
     * @return false
     */
    static function entity_requires_image_updates( $entity ){
        assert( $entity instanceof DB_Tire_Model || $entity instanceof DB_Rim_Finish );
        $is_tire = $entity instanceof DB_Tire_Model;

        if ( ! self::check_image_filesizes( $entity ) ) {
            return true;
        }

        list( $local, $source, $source_new ) = self::get_image_data( $entity );

        if ( $source_new && $source_new !== $source ) {
            return true;
        }

        return false;
    }

    /**
     * If the input is not a URL, checks the server for the file in various places,
     * then if found, returns the path and URL to the image.
     *
     * /image_src should be checked before /assets/images in most cases.
     *
     * @param $filename_or_url
     * @param string[] $paths
     * @return array|string[]
     */
    static function check_local_files( $filename_or_url, $paths = [ '/image_src', '/assets/images' ] ) {

        if ( ! $filename_or_url ) {
            return [ '', '' ];
        }

        if ( is_url_not_strict( $filename_or_url ) ) {
            return [ '', $filename_or_url ];
        }

        $check_server = function( $dir, $filename ) {

            $ext = self::extension_from_path( $filename );
            $path = BASE_DIR . $dir . '/' . $filename;
            $url = BASE_URL . $dir . '/' . $filename;

            if ( $ext ) {
                if ( file_exists( $path ) ) {
                    return [ $path, $url ];
                }
            } else {

                foreach ( [ 'jpg', 'jpeg', 'png' ] as $e ) {
                    if ( file_exists( $path . '.' . $e ) ) {
                        return [
                            $path . '.' . $e,
                            $url . '.' . $e,
                        ];
                    }
                }
            }

            return [ '', '' ];
        };

        foreach ( $paths as $path ) {

            list( $path, $url ) = $check_server($path, $filename_or_url );

            if ( $url ) {
                return [ $path, $url ];
            }
        }
    }

    /**
     * If the URL looks like a thickbox image, check for that same image file
     * on our local server, returning the URL to that file if it exists.
     *
     * Note: we'll likely do this only when the input URL is not found.
     *
     * @param $url
     * @return array|false|string[]
     */
    static function convert_thickbox_url( $url ) {

        // they are all 404's. we have all those images in image_src, mostly.
        if ( is_url_not_strict( $url ) && preg_match( "/[\d]{0,}-thickbox./", $url ) ){
            // without extension.
            $filename = self::filename_from_url( $url );
            return self::check_local_files( $filename, [ '/image_src' ] )[1];
        }

        return false;
    }

    /**
     * $image_url is most likely Product_Images::convert_non_url_image_src( $source )
     * where $source is rim_finsihes.image_source_new, or tire_models.tire_model_image_new.
     *
     * The source may or may not be the image URL. If it's a filename for example,
     * image URL could be BASE_URL/assets/images/filename. We need to store the source
     * in the image columns in the database, not the resulting image URL. The reason is
     * that the source may change often, and we want to easily be able to check when
     * the new source is not the same as the old source, so for this reason, store the source,
     * and not the derived image URL in the database.
     *
     * @param $source - image url or filename
     * @param $image_url - derived from source, sometimes the same
     * @param DB_Tire_Model_Or_Rim_Finish $entity
     * @param array $debug
     * @return array
     * @throws Exception
     */
    static function set_product_image( $source, $image_url, $entity, &$debug = [] ) {

        assert( $entity instanceof DB_Tire_Model || $entity instanceof DB_Rim_Finish );
        $is_tire = $entity instanceof DB_Tire_Model;
        $success_msg = "Success.";

        $debug['input'] = [ $source, $image_url ];

        /**
         * Approximate steps;
         * - Validate the Image URL (protocol, extension)
         * - If valid, download the image from the URL to a temporary folder, with a temporary name.
         * - Set rim_finish.image_local or tire_model.tire_model_image column to ""
         * - Delete previous image files from server, but move the prev full size one to diff folder instead.
         * - Move (not copy) the temp image to assets/images/rims/full
         * - Create compressed versions of image in images/rims/reg, and images/rims/thumb
         * - If all successful, update database columns to reflect new images and sources.
         */

        if ( ! $image_url ) {
            return [ false, "No image provided." ];
        }

        if ( ! is_url_not_strict( $image_url ) ) {
            // to prevent this, the developer should convert non URL sources to URLs
            // before calling this function.
            return [ false, "Failed to convert image source to a URL." ];
        }

        // fix some dropbox links
        // supplier gives url to html page: www.dropbox.com/s/ymtvm67qtjh43in/DIRTYLIFE9303R1.png?dl=0
        // we want: www.dropbox.com/s/dl/ymtvm67qtjh43in/DIRTYLIFE9303R1.png
        // we can leave ?dl=0 on the end.
        if ( strpos( $image_url, 'dropbox.com/s/' ) !== false ) {
            $debug[] = "Is dropbox URL";
            if ( strpos( $image_url, 'dropbox.com/s/dl/' ) === false ) {
                $image_url = str_replace( 'dropbox.com/s/', 'dropbox.com/s/dl/', $image_url );
                $debug[] = "Was not already download URL. Is now: " . $image_url;
            }
        }


        list( $temp_dir, $temp_filename ) = self::get_rim_image_tmp_dir_and_filename( (int) $entity->get_primary_key_value() );

        list( $url_success, $url_msg ) = Product_Images::validate_image_url( $image_url );

        if ( ! $url_success ) {
            return [ false, $url_msg ];
        }

        $download_image = function( $url ) use( $temp_dir, $temp_filename ){
            return Product_Images::download_file_from_url_without_validation( self::encode_url( $url ), $temp_dir, $temp_filename );
        };

        // first attempt
        $download = $download_image( $image_url );

        // check https if image_url is http
        if ( ! $download[ 'success' ] ) {
            if ( strpos( $image_url, 'http://' ) === 0 ) {
                $https_image_url = str_replace( 'http://', 'https://', $image_url );
                $download = $download_image( $https_image_url );
                $success_msg = "Success. (found image at https instead of http)";
            }
        }

        // if image URL filename contains "-thickbox.", check /image_src
        // (do this after trying fetch the image via URL).
        if ( ! $download[ 'success' ] ) {

            $thickbox_local_url = self::convert_thickbox_url( $image_url );

            if ( $thickbox_local_url ) {
                $download = $download_image( $thickbox_local_url );
                $success_msg = "Success. (thickbox URL was invalid, but a corresponding thickbox image was found locally on the server.)";
            }
        }

        if ( ! $download[ 'success' ] ) {
            return [ false, $download['msg'] ];
        }

        $base_dir = rtrim( BASE_DIR, '/' );
        self::mk_rim_dirs();
        self::mk_tire_dirs();

        if ( $is_tire ) {
            $entity->update_database_and_re_sync( [
                'tire_model_image' => '',
                'tire_model_image_origin' => '',
            ] );

            $filename = self::get_tire_image_filename( $entity, self::get_suggested_extension( $download[ 'dest_path' ] ) );

            $path_full = "$base_dir/assets/images/tires/full/$filename";
            $path_reg = "$base_dir/assets/images/tires/reg/$filename";
            $path_thumb = "$base_dir/assets/images/tires/thumb/$filename";

        } else {
            $entity->update_database_and_re_sync( [
                'image_local' => '',
                'image_source' => '',
            ] );

            $filename = self::get_rim_image_filename( $entity, self::get_suggested_extension( $download[ 'dest_path' ] ) );

            $path_full = "$base_dir/assets/images/rims/full/$filename";
            $path_reg = "$base_dir/assets/images/rims/reg/$filename";
            $path_thumb = "$base_dir/assets/images/rims/thumb/$filename";
        }

        // delete the old files (the full sized image is probably moved instead of deleted).
        list( $delete_success, $delete_message ) = self::delete_image_files_if_they_exist( $is_tire ? 'tire' : 'rim', $filename, true );

        if ( ! $delete_success ) {
            return [ false, $delete_message ];
        }

        // move temp image to proper location now.
        $renamed = rename( $download[ 'dest_path' ], $path_full );

        // very unlikely error, I think.
        if ( ! $renamed ) {
            return [ false, "Could not move full size image from temp folder to assets/[rims/tires]/full" ];
        }

        list( $reg_success, $reg_info ) = self::compress_reg( $path_full, $path_reg );

        if ( ! $reg_success ) {
            return [ false, "Failed to compress reg size image: " . @$reg_info[ 'error' ] ];
        }

        // perhaps compress thumb form the reg sized instead of full, I suppose.
        // some full size images are 50mb+, and could cause memory errors.
        list( $thumb_success, $thumb_info ) = self::compress_thumb( $path_reg, $path_thumb );

        if ( ! $thumb_success ) {
            return [ false, "Failed to compress thumbnail image: " . @$thumb_info[ 'error' ] ];
        }

        if ( $is_tire ) {
            $entity->update_database_and_re_sync( [
                'tire_model_image' => $filename,
                // important to store the image_url passed in, not something else if
                // we ended up converting it.
                'tire_model_image_origin' => $source,
                // empty string or same as origin I think is more or less the same.
                'tire_model_image_new' => $source,
            ] );

        } else {
            $entity->update_database_and_re_sync( [
                'image_local' => $filename,
                // important to store the image_url passed in, not something else if
                // we ended up converting it.
                'image_source' => $source,
                // empty string or same as source I think is more or less the same.
                'image_source_new' => $source,
            ] );

        }

        return [ true, $success_msg ];
    }

    /**
     * You should call self::validate_image_url() on $url, and not call this function if it fails,
     * and ensure that $relative_dir and $filename are sanitized/validated/hardcoded values.
     *
     * @param $url - you may want to call self::encode_url() on this first.
     * @param $relative_dir - ie. "/assets/images/temp" - WITH LEADING SLASH
     * @param $filename_no_ext - ie. "image", extensions from URL is appended.
     * @return array - indexed array with all keys always present
     */
    public static function download_file_from_url_without_validation( $url, $relative_dir, $filename_no_ext ) {

        $ext = self::extension_from_url( $url, true );
        $filename = $filename_no_ext . ".$ext";

        // possibly not necessary
        $relative_dir = preg_replace( "/[^a-zA-Z0-9-_\/]/", "", $relative_dir );
        $relative_dir = str_replace( "//", "", $relative_dir );

        $dest_dir = rtrim( BASE_DIR, '/' ) . "$relative_dir";

        $dest_path = "$dest_dir/$filename";

        // always return array with these keys
        $ret = [
            'success' => false,
            'msg' => '',
            'dest_path' => $dest_path,
            'dest_dir' => $dest_dir,
            'ext' => $ext,
            'curl' => null,
        ];

        // i shouldn't have to do this but just being a bit safe.
        // you should call the validate function first.
        if ( $ext === 'php' ) {
            $ret[ 'msg' ] = 'PHP ext never allowed.';
            return $ret;
        }

        if ( ! self::mk_relative_dir( $relative_dir ) ) {
            $ret[ 'msg' ] = 'Failed to create dir: ' . htmlspecialchars( $relative_dir );
            return $ret;
        }

        if ( file_exists( $dest_path ) ) {
            // ie. you as the developer should delete it with code, not the admin user.
            $ret[ 'msg' ] = "File already exists at destination. It must be deleted first.";
            return $ret;
        }

        // curl to get image file (can take half a second or more)
        $curl = new Curl();

        $ret[ 'curl' ] = $curl;

        $handle = fopen( $dest_path, 'w+' );

        $curl->setOpt( CURLOPT_FILE, $handle );

        // follow re-directs. This is useful when url has http but server requires https.
        $curl->setOpt( CURLOPT_FOLLOWLOCATION, true );

        // get payload
        $curl->get( $url );

        // do not return, only break. Need to cleanup at the end.
        while ( true ) {

            // I think we get a better error msg if we check this before the http response code.
            if ( $curl->error ) {
                $delete_file = true;
                $ret[ 'msg' ] = "Curl error {$curl->error_code}: {$curl->error_message}";
                break;
            }

            if ( ! $curl->isSuccess() ) {
                $delete_file = true;
                $ret[ 'msg' ] = "Invalid http response code: " . (int) $curl->http_status_code . " (file not found at URL).";
                break;
            }

            $delete_file = false;
            break;
        }

        // disable writing to file (?? not sure what this even does)
        $curl->setOpt( CURLOPT_FILE, null );

        // wont be able to access the file if we don't do this
        fclose( $handle );

        // not sure if some errors can allow an empty or non-empty file
        // to still exist? Kind of makes no sense to try to delete it,
        // but keeping this here anyways.
        if ( $delete_file ) {

            if ( file_exists( $dest_path ) ) {
                unlink( $dest_path );
            }

            // important.. otherwise, wrong error msg will get displayed.
            return $ret;
        }

        // remember that we checked that the file did not exist
        // before we tried to download it. So if it does exist now,
        // then I suppose that means success (though, could it still be empty?)
        // perhaps, checking filesize should be done afterwards.
        if ( file_exists( $dest_path ) ) {
            $ret[ 'success' ] = true;
            $ret[ 'msg' ] = "Success.";
            return $ret;
        } else {
            // hoping we can't get to here, but who knows.
            $ret[ 'msg' ] = "File could not be downloaded for unknown reasons.";
            return $ret;
        }
    }

    /**
     * @param $source
     * @param $dest
     * @return array
     */
    public static function compress_thumb( $source, $dest ) {

        return self::compress( $source, $dest, 225, 60 );
    }

    /**
     * @param $source
     * @param $dest
     * @return array
     */
    public static function compress_reg( $source, $dest ) {

        return self::compress( $source, $dest, 950, 60 );
    }

    /**
     * Pass in a callback function that provides you the ImageResize instance, and must
     * return an array consisting of certain indexes (see below).
     *
     * @param $source
     * @param $dest
     * @param $max_size
     * @param int $compression - jpg compression only
     * @return array
     */
    public static function compress( $source, $dest, $max_size, $compression = 60 ) {

        $arr = [
            'error' => "",
            'scaled' => null,
            'method' => null,
        ];

        if ( file_exists( $dest ) ) {
            $arr['error'] = "Cannot compress file because it already exists.";
            return [ false, $arr ];
        }

        // was a p.i.t.a to install on dev env, so i'll use a fallback as well.
        if ( extension_loaded( 'imagick' ) ) {

            $arr['method'] = 'Imagick';

            try {

                $img = new Imagick( $source );

                // for jpeg only.
                // no idea if setImageCompression even does anything.
                $img->setImageCompression( Imagick::COMPRESSION_JPEG );
                $img->setImageCompressionQuality( $compression );

                // hardcoded value for png. Gain minimal savings by going to 9 (lowest filesize)
                $img->setOption( 'png:compression-level', 8 );

                // ?? no clue what these do. Found on internet. just make files bigger.
                // $img->setOption('png:format', 'png64');
                // $img->setOption('png:bit-depth', '16');
                // $img->setOption('png:color-type', 6);

                if ( $img->getImageWidth() > $max_size || $img->getImageHeight() > $max_size ) {

                    $arr['scaled'] = true;

                    if ( $img->getImageWidth() >= $img->getImageHeight() ) {
                        $img->scaleImage( $max_size, 0 );
                    } else {
                        $img->scaleImage( 0, $max_size );
                    }
                } else {
                    $arr['scaled'] = false;
                }

                $img->writeImage( $dest );

                $img->destroy();

                return [ true, $arr ];

            } catch ( Exception $e ) {

                $arr['error'] = "Imagick Exception: " . $e->getMessage();

                return [ false, $arr ];
            }

        } else {

            $arr['method'] = 'Gumlet/ImageResize';

            try {

                $img = new ImageResize( $source );

                // keeps transparent background on pngs
                $img->quality_truecolor = false;

                // makes things look more identical to original (for pngs only?)
                $img->gamma_correct = false;

                if ( $img->getSourceWidth() > $max_size || $img->getSourceHeight() > $max_size ) {

                    $arr['scaled'] = true;

                    $img->resizeToLongSide( $max_size, false );

                } else {
                    $arr['scaled'] = false;
                }

                if ( $img->source_type === IMAGETYPE_PNG ) {
                    // png quality must be between 1-9.
                    // 9 is smaller filesize.
                    // 9 is not much better than 8, but much slower.
                    // no point having options for the quality here, just hardcode it.
                    $img->save( $dest, null, 8, 0777 );
                } else {
                    $img->save( $dest, null, $compression, 0777 );
                }

                return [ file_exists( $dest ), [
                    'error' => file_exists( $dest ) ? "" : "Not sure why.",
                    'scaled' => $arr['scaled'],
                    'method' => 'Gumlet/ImageResize',
                ] ];

            } catch ( \Gumlet\ImageResizeException $e ) {

                $arr['error'] = "Gumlet Exception: " . $e->getMessage();

                return [ false, $arr ];
            }
        }
    }

    /**
     * Move a file (if it exists) to a new directory, returning true
     * if the file no longer exists at its original location afterwards.
     *
     * The idea is to soft-delete a file and make room for storing a new
     * file at its location.
     *
     * @param $path_to_file
     * @param $rel_dir - ie. "/assets/old-rim-images"
     * @param $filename_no_ext - you should (must?) ensure this is unique.
     * @return bool
     */
    public static function move_to_new_dir_if_exists( $path_to_file, $rel_dir, $filename_no_ext ) {

        if ( ! file_exists( $path_to_file ) ) {
            return true;
        }

        $base = rtrim( BASE_DIR, '/' );
        self::mk_relative_dir( $rel_dir );

        $ext = self::extension_from_path( $path_to_file );

        $dest = $base . $rel_dir . '/' . $filename_no_ext . '.' . $ext;

        $renamed = rename( $path_to_file, $dest );

        return $renamed && ! file_exists( $path_to_file );
    }

    /**
     * @param $path_to_file
     * @return bool
     */
    public static function ensure_deleted( $path_to_file ) {

        if ( file_exists( $path_to_file ) ) {
            @unlink( $path_to_file );
        }

        return ! file_exists( $path_to_file );
    }

    /**
     * @param $dir - WITH leading slash!
     * @param int $mode
     * @return bool
     */
    public static function mk_relative_dir( $dir, $mode = 0777 ) {

        $path = rtrim( BASE_DIR, '/' ) . $dir;

        if ( ! file_exists( $path ) ) {
            mkdir( $path, $mode, true );
        }

        return file_exists( $path );
    }

    /**
     * @param $type
     * @param $filename
     * @param bool $soft_delete_full_size
     * @return array
     */
    public static function delete_image_files_if_they_exist( $type, $filename, $soft_delete_full_size = true ) {

        $base = rtrim( BASE_DIR, '/' );

        if ( $type === 'tire' ) {

            $path_full = "$base/assets/images/tires/full/$filename";
            $path_reg = "$base/assets/images/tires/reg/$filename";
            $path_thumb = "$base/assets/images/tires/thumb/$filename";

            $prev_dir = "/assets/images/prev-tires";

        } else if ( $type === 'rim' ) {

            $path_full = "$base/assets/images/rims/full/$filename";
            $path_reg = "$base/assets/images/rims/reg/$filename";
            $path_thumb = "$base/assets/images/rims/thumb/$filename";

            $prev_dir = "/assets/images/prev-rims";

        } else {
            throw_dev_error( "Invalid type." );
            exit;
        }

        if ( $soft_delete_full_size ) {

            // soft delete previous full sizes image
            if ( ! self::move_to_new_dir_if_exists( $path_full, $prev_dir, implode( "_", [
                self::remove_extension( $filename ),
                date( "Ymd" ),
                date( "hi" ),
                rand( 100, 999 ),
            ] ) ) ) {
                return [ false, "Could not soft-delete the previous full sized image." ];
            }

        } else {

            if ( ! self::ensure_deleted( $path_full ) ) {
                return [ false, "Could not delete the previous full sized image." ];
            }

        }

        if ( ! self::ensure_deleted( $path_reg ) ) {
            return [ false, "Could not delete the previous reg sized image." ];
        }

        if ( ! self::ensure_deleted( $path_thumb ) ) {
            return [ false, "Could not delete the previous thumb sized image." ];
        }

        return [ true, "" ];
    }

    /**
     * Many images have the .jpg extension but are actually PNG image files,
     * for those, we may or may not want to copy them with the png extension
     * instead of jpg.
     *
     * @param $path - doesnt work on URL right now.
     * @return bool|string
     */
    public static function get_suggested_extension( $path ) {

        if ( ! $path || ! file_exists( $path ) ) {
            return false;
        }

        // when using imagick, its important to not use png images that use
        // jpg extension, as is often the case. When we do this we lose transparency
        // and it looks like shit. This wasn't an issue with Gumlet/ImageResize, but,
        // is with imagick. The easiest solution is to just rename to the proper extension.
        if ( self::get_image_type( $path ) === IMAGETYPE_PNG ) {
            return 'png';
        }

        // probably jpg
        return self::extension_from_path( $path );
    }

    // old way. Just going to leave this here in case imagick ends up not working.
    public static function __legacy_gumlet_compress( $source, $dest, $build_args ) {

//        try{
//
//            $image = new ImageResize($source);
//
//            // keeps transparent background on pngs
//            $image->quality_truecolor = false;
//
//            // makes things look more identical to original (for pngs only?)
//            $image->gamma_correct = false;
//
//            $args = $build_args( $image );
//
//            $max_width = $args['max_width'];
//            $max_height = $args['max_height'];
//
//            switch( $image->source_type ) {
//                case IMAGETYPE_JPEG:
//
//                    if ( isset( $args['quality_jpg'] ) ) {
//                        $quality = $args['quality_jpg'];
//                    } else {
//                        $quality = @$args['quality'];
//                    }
//
//                    break;
//                case IMAGETYPE_PNG:
//
//                    if ( isset( $args['quality_png'] ) ) {
//                        $quality = $args['quality_png'];
//                    } else {
//                        $quality = @$args['quality'];
//                    }
//
//                    break;
//                default:
//                    $quality = null;
//            }
//
//            $max = max( $max_width, $max_height );
//
//            $image->resizeToLongSide($max, false);
//
//            $image->save( $dest, null, $quality, 0755 );
//
//            if ( @$args['then'] ) {
//                call_user_func( $args['then'], $image );
//            }
//
//            return [ file_exists( $dest ), [
//                'error' => file_exists( $dest ) ? "" : "Not sure why.",
//                'source_size' => filesize( $source ),
//                'dest_size' => filesize( $dest ),
//                'source_width' => $image->getSourceWidth(),
//                'source_height' => $image->getSourceHeight(),
//                'dest_width' => $image->getDestWidth(),
//                'dest_height' => $image->getDestHeight(),
//            ]];
//
//        } catch (\Gumlet\ImageResizeException $e ){
//
//            return [ false, [
//                'error' => "Gumlet Exception: " . $e->getMessage()
//            ]];
//        }
    }

    /**
     * See test file.
     *
     * $in could be things like:
     * just-a-filename.jpg
     * example.com/filename.jpg
     * www.example.com/filename.jpg
     * http://example.com/filename.jpg
     * https://example.com/filename.jp2
     *
     * Anything that looks like a URL should have https:// added, unless
     * it already starts with http:// or https://
     *
     * @param $in
     * @return string
     */
    static function possibly_convert_to_url( $in ) {

        if ( strpos( $in, 'www.' ) === 0 ) {
            $in = "https://" . $in;
        }

        // ie. example.com/{anything}
        // but not www.example.com/{anything}
        if ( preg_match( '/^[a-zA-Z0-9-_]+\.[a-zA-Z0-9]+\/[\S]+/', $in ) ) {
            $in = "https://" . $in;
        }

        return $in;
    }
}