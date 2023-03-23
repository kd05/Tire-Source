<?php

/**
 * Class DB_Price_Update
 */
Class DB_Price_Update extends DB_Table{

    protected static $primary_key = 'id';
    protected static $table = 'price_updates';

    // db columns
    protected static $db_init_cols = array(
        'id' => 'int(11) unsigned NOT NULL auto_increment',
        'locale' => 'varchar(255) default \'\'',
        'supplier' => 'varchar(255) default \'\'',
        'type' => 'varchar(255) default \'\'',
        'context' => 'varchar(255) default \'\'',
        'req_id' => 'int(11) unsigned DEFAULT NULL',
        'filename' => 'varchar(511) default \'\'',
        'prev_avg' => 'varchar(255) default \'\'',
        'new_avg' => 'varchar(255) default \'\'',
        'pct_change' => 'varchar(255) default \'\'',
        'date' => 'varchar(255) default \'\'',
        'counts' => 'longtext',
    );

    protected static $fields = array(
        'id',
        'locale',
        'supplier',
        'type',
        'context',
        'req_id',
        'filename',
        'prev_avg',
        'new_avg',
        'pct_change',
        'date',
        'counts',
    );

    protected static $db_init_args = array(
        'PRIMARY KEY (`id`)',
    );

    /**
     * @return array|mixed
     */
    function get_counts(){
        return self::decode_json_obj( $this->get( 'counts' ) );
    }
}
