<?php

/**
 * Trait Product_Images_Rims
 */
Trait Product_Images_Rims {

    /**
     * @return bool
     */
    public static function mk_rim_dirs() {

        if ( ! self::mk_relative_dir( "/assets/images/prev-rims" ) ) {
            return false;
        }

        if ( ! self::mk_relative_dir( "/assets/images/_temp" ) ) {
            return false;
        }

        foreach ( [ "full", "reg", "thumb" ] as $size ) {
            $_dir = "/assets/images/rims/$size";
            if ( ! self::mk_relative_dir( $_dir ) ) {
                return false;
            }
        }

        return true;

    }

    /**
     * @param $finish_id
     * @return array
     */
    public static function get_rim_image_tmp_dir_and_filename( $finish_id ) {

        $tmp_name = implode( "-", [
            'rf_' . $finish_id,
            date( "Ymd" ),
            date( "his" ),
            rand( 1000, 9999 )
        ] );

        return [ '/assets/images/_temp', $tmp_name ];
    }

    /**
     * @param DB_Rim_Finish $f
     * @param $ext
     * @return string|string[]|null
     */
    public static function get_rim_image_filename( DB_Rim_Finish $f, $ext ) {

        $items = $f->get_slugs( true, true, false );
        $items[] = $f->get_primary_key_value();

        $filename = implode( "_", $items );
        $filename = preg_replace( '/[^A-Za-z0-9-_]+/', '', $filename );

        return $ext ? $filename . '.' . $ext : $filename;
    }

    /**
     * There may or may not be a way for admin user to trigger this function atm.
     *
     * @param DB_Rim_Finish $f
     * @param bool $soft_delete_full
     * @return bool
     * @throws Exception
     */
    public static function delete_rim_finish_image( DB_Rim_Finish $f, $soft_delete_full = true ) {

        $filename = $f->get( 'image_local' );

        if ( ! $filename ) {
            return true;
        }

        // if file deletion fails here (which it shouldn't) its not a big deal.
        list( $del, $del_msg ) = self::delete_image_files_if_they_exist( 'rim', $filename, $soft_delete_full );

        $updated = $f->update_database_and_re_sync( [
            'image_local' => '',
            'image_source' => '',
        ] );

        return (bool) $updated;
    }
}