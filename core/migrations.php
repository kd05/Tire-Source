<?php
/**
 * file is probably included on every page load. Checks the options table and runs
 * certain callbacks as needed. Once migrations are run in prod/dev environments, they can
 * likely be marked as not active.
 */

$db = get_database_instance();
include CORE_DIR . '/migrations/images.php';

// useful to ignore exceptions if possibly adding columns that already exist
$execute_silent = function( $sql, $params = [] ) {
    try{
        $db = get_database_instance();
        return $db->execute( $sql, $params );
    } catch( Exception $e ) {
        return false;
    }
};

// this file is pretty new so old migrations don't appear below.
$migrations = [
    [ 0, 'migrations__tires__image_col_src', function(){
        CW\Migrations\Images\tire_model_image_origin__column();
    }],
    [ 0, 'migrations__tires__image_col_src_new', function(){
        CW\Migrations\Images\tire_model_image_new__column();
    }],
    [ 0, 'migrations__move_old_rims_images', function(){
        $rand = uniqid();
        log_data( ['rand' => $rand], "move_old_rims_images" );

        // could hit memory or time limits, so log both before and after
        // so we can confirm it was completed.
        $result = Rim_Images_Migration::move_old_rims_images_in_assets_dir();

        log_data( array_merge( $result, [
            'rand' => $rand
        ]), "move_old_rims_images" );
    }],
    [ 0, 'migrations__product_sync_main', function() use( $execute_silent ){
        $execute_silent("ALTER TABLE rim_brands ADD rim_brand_inserted_at varchar(255) DEFAULT '';");
        $execute_silent("ALTER TABLE rim_models ADD rim_model_inserted_at varchar(255) DEFAULT '';");
        $execute_silent("ALTER TABLE rim_finishes ADD rim_finish_inserted_at varchar(255) DEFAULT '';");
        $execute_silent("ALTER TABLE tire_brands ADD tire_brand_inserted_at varchar(255) DEFAULT '';");
        $execute_silent("ALTER TABLE tire_models ADD tire_model_inserted_at varchar(255) DEFAULT '';");
        $execute_silent( "ALTER TABLE tires ADD sync_id_insert_ca int(11) default NULL" );
        $execute_silent( "ALTER TABLE tires ADD sync_date_insert_ca varchar(255) default ''" );
        $execute_silent( "ALTER TABLE tires ADD sync_id_update_ca int(11) default NULL" );
        $execute_silent( "ALTER TABLE tires ADD sync_date_update_ca varchar(255) default ''" );
        $execute_silent( "ALTER TABLE tires ADD sync_id_insert_us int(11) default NULL" );
        $execute_silent( "ALTER TABLE tires ADD sync_date_insert_us varchar(255) default ''" );
        $execute_silent( "ALTER TABLE tires ADD sync_id_update_us int(11) default NULL" );
        $execute_silent( "ALTER TABLE tires ADD sync_date_update_us varchar(255) default ''" );
        $execute_silent( "ALTER TABLE rims ADD sync_id_insert_ca int(11) default NULL" );
        $execute_silent( "ALTER TABLE rims ADD sync_date_insert_ca varchar(255) default ''" );
        $execute_silent( "ALTER TABLE rims ADD sync_id_update_ca int(11) default NULL" );
        $execute_silent( "ALTER TABLE rims ADD sync_date_update_ca varchar(255) default ''" );
        $execute_silent( "ALTER TABLE rims ADD sync_id_insert_us int(11) default NULL" );
        $execute_silent( "ALTER TABLE rims ADD sync_date_insert_us varchar(255) default ''" );
        $execute_silent( "ALTER TABLE rims ADD sync_id_update_us int(11) default NULL" );
        $execute_silent( "ALTER TABLE rims ADD sync_date_update_us varchar(255) default ''" );
        $execute_silent( "ALTER TABLE tires ADD map_price_ca varchar(255) default ''" );
        $execute_silent( "ALTER TABLE tires ADD map_price_us varchar(255) default ''" );
        $execute_silent( "ALTER TABLE rims ADD map_price_ca varchar(255) default ''" );
        $execute_silent( "ALTER TABLE rims ADD map_price_us varchar(255) default ''" );
        $execute_silent( "ALTER TABLE tires ADD upc varchar(255) default ''" );
        $execute_silent( "ALTER TABLE rims ADD upc varchar(255) default ''" );

        // careful, some of these fns were only meant for dev and print output.
        ob_start();
        try{
            DB_Sync_Request::db_init_create_table_if_not_exists();
            DB_Sync_Update::db_init_create_table_if_not_exists();
            DB_Price_Rule::db_init_create_table_if_not_exists();
        } catch ( Exception $e ) {

        }
        $ignore = ob_get_clean();
    }],
    [ 0, 'migrations__price_update_table', function() use( $execute_silent ){
        ob_start();
        try{
            DB_Price_Update::db_init_create_table_if_not_exists();
            $ignore = ob_get_clean();
        } catch ( Exception $e ) {

        }
    }],
];

foreach ( $migrations as $arr ) {

    list( $active, $option_key, $callback ) = $arr;

    if ( ! $active ) {
        continue;
    }

    if ( ! cw_get_option( $option_key ) ) {

        if ( ! IN_PRODUCTION ) {
            echo "RUNNING MIGRATION: " . $option_key . " \r\n";
        }

        $file = $option_key . '.log';

        log_data( [
            'migration' => $option_key,
            '__type' => 'before',
        ], $file );

        try{
            $callback();

            log_data( [
                'migration' => $option_key,
                '__type' => 'after',
            ], $file );

        } catch ( Exception $e ) {

            log_data( [
                'migration' => $option_key,
                '__type' => 'exception',
                'e' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], $file );
        }

        cw_set_option($option_key, 1);

        if ( ! IN_PRODUCTION ) {
            echo "MIGRATION ENDED: " . $option_key . " \r\n";
            echo "(database might have been updated). You can reload the page now.";
            exit;
        }
    }
}
