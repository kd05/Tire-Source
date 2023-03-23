<?php

/**
 * Class DB_Product_Fetch
 */
Class DB_Sync_Update extends DB_Table{

    protected static $primary_key = 'sync_update_id';
    protected static $table = 'sync_update';

    // db columns
    protected static $db_init_cols = array(
        'sync_update_id' => 'int(11) unsigned NOT NULL auto_increment',
        'sync_request_id' => 'int(11) unsigned DEFAULT NULL',
        'sync_key' => 'varchar(255) default \'\'',
        'type' => 'varchar(255) default \'\'',
        'locale' => 'varchar(255) default \'\'',
        'supplier' => 'varchar(255) default \'\'',
        'counts' => 'longtext',
        'debug' => 'longtext',
        'date' => 'varchar(255) default \'\'',
    );

    protected static $fields = array(
        'sync_update_id',
        'sync_request_id',
        'sync_key',
        'type',
        'locale',
        'supplier',
        'counts',
        'debug',
        'date',
    );

    protected static $db_init_args = array(
        'PRIMARY KEY (`sync_update_id`)',
    );

    function get_debug(){
        return self::decode_json_obj( $this->get( 'debug' ) );
    }

    function get_counts(){
        return self::decode_json_arr( $this->get( 'counts' ) );
    }
}
