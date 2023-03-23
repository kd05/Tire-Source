<?php

/**
 * Class Rim_Images_Migration
 */
class Rim_Images_Migration {

    /**
     * Gets all images from BASE_DIR . '/assets/images' which start with
     * {rim_brand}_{rim_model}_. Rim images used to go here some time ago.
     *
     * However, there are too many of them and we want them moved to a different
     * location. This function could give some false positives, which is unlikely
     * and not a big deal, because we can just manually move them back afterwards.
     *
     * Note: function could be very expensive, and much has been done to optimize it,
     * which is part of the reason why we can only get the "probable" rim images.
     */
    static function get_probable_rim_images_in_assets_dir() {

        $mem = new Time_Mem_Tracker( 'rims_assets' );

        // live server has upwards of 5k finishes and 10k images.
        // the product of which is 50 million, so we need to be careful
        // what we execute in the nested loop.
        $files = scandir( BASE_DIR . '/assets/images' );
        $mem->breakpoint( 'scan' );

        $finishes = DB_Rim_Finish::query_all( '', true, true );
        $mem->breakpoint( 'query' );

        $brands_models = [];

        foreach ( $finishes as $finish ) {
            $brand = $finish->brand->get( 'slug' );
            $model = $finish->model->get( 'slug' );
            $key = $brand . ',' . $model;

            if ( $brand && $model && ! isset( $brands_models[ $key ] ) ) {
                $brands_models[ $key ] = 1;
            }
        }

        // ie. "vision-off-road_turbine_" for all rim brands/models
        $prefixes = array_map( function ( $item ) {

            list( $brand, $model ) = explode( ',', $item );
            return $brand . '_' . $model . '_';
        }, array_keys( $brands_models ) );

        $mem->breakpoint( 'prefixes' );

        $move_files = [];

        $files = array_filter( $files, function ( $file ) use ( &$move_files ) {

            if ( preg_match( '/--v[\d]{0,2}./', $file ) ) {
                $move_files[] = $file;
                return false;
            }

            return true;
        } );

        $mem->breakpoint( 'regex --v#' );

        // now nested loop on less files
        foreach ( $files as $file ) {
            foreach ( $prefixes as $prefix ) {
                if ( strpos( $file, $prefix ) === 0 ) {
                    $move_files[] = $file;
                }
            }
        }

        // could be 5k or more.
        return $move_files;
    }

    /**
     * Helps detect possible false positives
     */
    static function count_probable_rim_brands(){

        // live server has upwards of 5k finishes and 10k images.
        // the product of which is 50 million, so we need to be careful
        // what we execute in the nested loop.
        $filenames = scandir( BASE_DIR . '/assets/old-rim-images' );

        $brands = [];

        foreach ( $filenames as $filename ) {
            $brands[] = explode( "_", $filename )[0];
        }

        $counts = array_count_values( $brands );
        asort( $counts );

        return $counts;
    }

    /**
     * A new way that looks at only the filenames and doesn't depend
     * on the finishes in the database. This way is perhaps more likely
     * to hit false positives, but oh well. If this happpens we can manually
     * move that false positive back into its original folder.
     */
    static function get_probable_rim_images_in_assets_dir_new() {

        $mem = new Time_Mem_Tracker( 'rims_assets' );

        // live server has upwards of 5k finishes and 10k images.
        // the product of which is 50 million, so we need to be careful
        // what we execute in the nested loop.
        $files = scandir( BASE_DIR . '/assets/images' );
        $mem->breakpoint( 'scan' );

        // probably don't need to compare tire brands but idk
        $tire_brands = DB_Tire_Brand::query_all();
        $tire_brand_slugs = array_map( function( $brand ) {
            return $brand->get( 'slug' );
        }, $tire_brands );

        return array_filter( $files, function( $file ) use( $tire_brand_slugs ) {

            // all lower case with at least 3 underscores and the portions
            // between the underscores not empty.
            // ie rim-brand_rim-model_rim-color-1_maybe-color-2_maybe-color-3.jpg
            $parts = array_filter( explode( "_", $file ) );

            if ( count( $parts ) > 2 && count( $parts ) < 6 ) {

                if ( in_array( $parts[0], $tire_brand_slugs ) ) {
                    // echo "TIRE BRAND " . $parts[0];
                    return false;
                }

                if ( strtolower( $file ) === $file ) {
                    return true;
                }
            }

            return false;
        } );
    }

    static function move_old_rims_images_in_assets_dir(){

        start_time_tracking('move_rims');

        if ( IS_WFL ) {
            $files = self::get_probable_rim_images_in_assets_dir_new();
        } else {
            $files = self::get_probable_rim_images_in_assets_dir();
        }

        $success = 0;

        $t1 = end_time_tracking( 'move_rims' );

        @mkdir( BASE_DIR . '/assets/old-rim-images', 0755, true );

        foreach ( $files as $filename ) {

            $old = BASE_DIR . '/assets/images/' . $filename;
            // we already had a folder called prev-rims, which is for something else.
            $new = BASE_DIR . '/assets/old-rim-images/' . $filename;
            if ( @rename( $old, $new ) ) {
                $success++;
            }
        }

        $t2 = end_time_tracking( 'move_rims' );

        // will probably just log or print this
        return [
            'files' => count( $files ),
            'success' => $success,
            't1' => $t1,
            't2' => $t2
        ];
    }

}