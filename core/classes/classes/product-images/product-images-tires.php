<?php

/**
 * Trait Product_Images_Tires
 */
Trait Product_Images_Tires {

    /**
     * @return bool
     */
    public static function mk_tire_dirs() {

        if ( ! self::mk_relative_dir( "/assets/images/prev-tires" ) ) {
            return false;
        }

        if ( ! self::mk_relative_dir( "/assets/images/_temp" ) ) {
            return false;
        }

        foreach ( [ "full", "reg", "thumb" ] as $size ) {
            $_dir = "/assets/images/tires/$size";
            if ( ! self::mk_relative_dir( $_dir ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate a sufficiently random filename so we can be confident the file
     * does not already exist.
     *
     * @param $tire_model_id
     * @return array
     */
    public static function get_tire_image_tmp_dir_and_filename( $tire_model_id ) {

        $tmp_name = implode( "-", [
            'tm_' . $tire_model_id,
            date( "Ymd" ),
            date( "his" ),
            rand( 1000, 9999 )
        ] );

        return [ '/assets/images/_temp', $tmp_name ];
    }

    /**
     * @param DB_Tire_Model $m
     * @param $ext
     * @return string|string[]|null
     */
    public static function get_tire_image_filename( DB_Tire_Model $m, $ext ) {

        $m->setup_brand();

        $items = [
            $m->brand->get( 'tire_brand_slug' ),
            $m->get( 'tire_model_slug' ),
            $m->type->get( 'slug' ) . '-tires',
            (int) $m->get_primary_key_value(),
        ];

        $filename = implode( "_", $items );
        $filename = preg_replace( '/[^A-Za-z0-9-_]+/', '', $filename );

        return $ext ? $filename . '.' . $ext : $filename;
    }

    /**
     * @param DB_Tire_Model $m
     * @param bool $soft_delete_full
     * @return array|bool
     * @throws Exception
     */
    public static function delete_tire_model_image( DB_Tire_Model $m, $soft_delete_full = true ) {

        $filename = $m->get( 'tire_model_image' );

        if ( ! $filename ) {
            return true;
        }

        // if file deletion fails here (which it shouldn't) its not a big deal.
        list( $del, $del_msg ) = self::delete_image_files_if_they_exist( 'tire', $filename, $soft_delete_full );

        $updated = $m->update_database_and_re_sync( [
            'tire_model_image' => '',
            'tire_model_image_origin' => '',
        ] );

        return (bool) $updated;
    }
}