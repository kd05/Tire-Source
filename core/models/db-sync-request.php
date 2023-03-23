<?php

/**
 * Class DB_Product_Fetch
 */
Class DB_Sync_Request extends DB_Table{

    protected static $primary_key = 'id';
    protected static $table = 'sync_request';

    // db columns
    protected static $fields = array(
        'id',
        'sync_key',
        'type',
        'supplier',
        'locale',
        'count_all',
        'count_valid',
        'count_changes',
        'dir_name',
        'errors',
        'prod_new',
        'prod_diff',
        'prod_same',
        'prod_del',
        'brands_new',
        'brands_diff',
        'brands_same',
        'models_new',
        'models_diff',
        'models_same',
        'finishes_new',
        'finishes_diff',
        'finishes_same',
        'debug',
        'total_time',
        'peak_mem',
        'inserted_at',
    );

    protected static $db_init_cols = array(
        'id' => 'int(11) unsigned NOT NULL auto_increment',
        'sync_key' => 'varchar(255) default \'\'',
        'type' => 'varchar(255) default \'\'',
        'supplier' => 'varchar(255) default \'\'',
        'locale' => 'varchar(255) default \'\'',
        'count_all' => 'varchar(255) default \'\'',
        'count_valid' => 'varchar(255) default \'\'',
        'count_changes' => 'int(11) default NULL',
        'dir_name' => 'varchar(255) default \'\'',
        'errors' => 'longtext',
        'prod_new' => 'int(11) default NULL',
        'prod_diff' => 'int(11) default NULL',
        'prod_same' => 'int(11) default NULL',
        'prod_del' => 'int(11) default NULL',
        'brands_new' => 'int(11) default NULL',
        'brands_diff' => 'int(11) default NULL',
        'brands_same' => 'int(11) default NULL',
        'models_new' => 'int(11) default NULL',
        'models_diff' => 'int(11) default NULL',
        'models_same' => 'int(11) default NULL',
        'finishes_new' => 'int(11) default NULL',
        'finishes_diff' => 'int(11) default NULL',
        'finishes_same' => 'int(11) default NULL',
        'debug' => 'longtext',
        'total_time' => 'varchar(255) default \'\'',
        'peak_mem' => 'varchar(255) default \'\'',
        'inserted_at' => 'varchar(255) default \'\'',
    );

    protected static $db_init_args = array(
        'PRIMARY KEY (`id`)',
    );

    /**
     * @param $cb
     * @return bool
     * @throws Exception
     */
    function update_debug_via_callback( $cb ) {
        return $this->update_json_column_via_callback( 'debug', $cb, true );
    }

    /**
     * Must sanitize all columns, since it can add html to columns if needed.
     *
     * @param $row
     * @return mixed
     */
    static function map_to_admin_table( $row ) {

        // sanitize everything
        $row = array_map( 'gp_test_input', $row );

        $unset = [
            'prod_new',
            'prod_diff',
            'prod_same',
            'prod_del',
            'brands_new',
            'brands_diff',
            'brands_same',
            'models_new',
            'models_diff',
            'models_same',
            'finishes_new',
            'finishes_diff',
            'finishes_same',
        ];

        $counts = [];

        foreach ( $unset as $col ) {
            $counts[] = "$col: " . (int) $row[$col];
            unset( $row[$col] );
        }

        $row['_counts'] = implode( ", ", $counts );

        unset( $row['debug'] );

        return $row;
    }

    /**
     * @param $sync_key
     * @return DB_Sync_Request|null
     * @throws Exception
     */
    static function get_latest_without_errors( $sync_key ) {
        $q = "
        select * from sync_request
        where sync_key = :sync_key
        and count_valid > 0 
        and ( errors = '[]' OR errors = '' )
        order by inserted_at DESC
        LIMIT 0, 1
        ";
        $p = [ [ 'sync_key', $sync_key ]];
        $reqs = Product_Sync::get_results( $q, $p );

        if ( $reqs ) {
            return self::create_instance_or_null($reqs[0]);
        }

        return null;
    }

    /**
     * @return array|mixed
     */
    function get_debug(){
        return self::decode_json_obj( $this->get( 'debug' ) );
    }

    /**
     * @return array|mixed
     */
    function get_errors(){
        return self::decode_json_arr( $this->get( 'errors' ) );
    }

    /**
     * @param $key
     * @param $value
     * @return string|null
     */
    public function get_cell_data_for_admin_table( $key, $value ) {

        switch( $key ) {
            case 'sync_key';
                return gp_get_link( Product_Sync_Admin_UI::get_url( $value ), $value );
        }
    }
}
