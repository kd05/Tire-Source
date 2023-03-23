<?php

/**
 * DAI has up to 4 imports:
 *
 * Tires CA, Tires US, Rims CA, Rims US
 *
 * (Some of them might be shut off).
 *
 * Currently all data is in the same file. This class
 * holds some common functionality between imports.
 *
 * Class SIS_Dai_Methods
 */
Abstract Class SIS_Dai extends Supplier_Inventory_Supplier {

    /**
     * The column indexes (integers) in the CSV file to read from...
     *
     * For US imports, this is just Keene, for CA
     * its everything else.
     *
     * Note: we're going to read by column index not column heading,
     * because DAI sometimes renames a warehouse.
     *
     * @var array
     */
    public $warehouse_columns = array();

    /**
     * Get the FTP Object that can retrieve the file
     * with the inventory data (applies to many imports).
     *
     * @return FTP_Get_Csv
     */
    public static function get_ftp_instance() {
        $ftp                   = new FTP_Get_Csv();
        $ftp->method           = 'sftp';
        $ftp->host             = self::$our_own_ftp_server_host;
        $ftp->username         = 'u95793629-dai';
        $ftp->password         = '##removed';
        $ftp->remote_file_name = 'DAI_inventory_list.csv';

        return $ftp;
    }

    /**
     * All 4 DAI sub classes will use this to get inventory data.
     *
     * Setup warehouses in the class constructor to change how this behaves.
     *
     * @return mixed|void
     */
    public function prepare_for_import() {

        $this->ftp->run();

        list( $csv_data, $error ) = CSV_To_Array::build_numerically_indexed_array( $this->ftp->get_local_full_path(), true );

        // quite unlikely
        if ( $error ) {
            log_data( [ "DAI Error: " . $error, static::HASH_KEY ], "dai-csv-error" );
        }

        $arr = self::process_csv_data( $csv_data, $this->warehouse_columns );

        $this->array = self::array_map_and_filter( $arr );

        // delete FTP file
        $this->ftp->unlink();
    }

    /**
     * Build the array of inventory levels from almost raw CSV data. The CSV data
     * passed in should have its header column removed. Note: we don't use column
     * names for DAI (unlike other suppliers...), we instead just use column indexes.
     *
     * @param array $csv_data - [ [0 => "abcd-the-part-number", 1 => "23", 2 => "50+", 3 => "0"], [1 => ...] ]
     * @param array $warehouse_columns - [1, 3]
     * @return array
     */
    public static function process_csv_data(array $csv_data, array $warehouse_columns){

        return array_reduce( $csv_data, function( $acc, $row ) use( $warehouse_columns ){

            // I don't control the input data so I guess fail silently if
            // the part number doesn't exist.
            $part_number = trim( @$row[0] );

            if ( ! $part_number ) {
                return $acc;
            }

            $stock = array_reduce( $warehouse_columns, function( $acc, $col_index) use( $row ){
                return $acc + self::convert_qty_value_to_int( (int) @$row[$col_index] );
            }, 0 );

            $acc[] = [
                'part_number' => $part_number,
                'stock' => $stock,
            ];

            return $acc;

        }, [] );
    }

    /**
     * @param $locale
     * @return array
     */
    public static function get_warehouse_columns_indexes_via_locale( $locale ) {

        // First column from file (June 2020)
        // "CODE","LAVAL","TORONTO","DARTMOUTH","VANCOUVER","EDMONTON","TRANSBEC MONTREAL","ST-BRUNO","OTTAWA","MOUNT-PEARL","CHOMEDEY","QUEBEC","KEENE","SCARBOROUGH","DAI MONTREAL"

        switch( $locale ) {
            case APP_LOCALE_CANADA:
                // All except Keene
                return [ 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 13, 14 ];
                break;
            case APP_LOCALE_US:
                // Keene
                return [ 12 ];
                break;
            default:
                throw_dev_error("Invalid locale.");
                exit;
        }

    }

    /**
     * Spits out extra info somewhere in the admin portion of the website.
     */
    public function get_admin_info_extra_column() {
        return 'Warehouse Column Indexes (first column is 0): ' . implode( ', ', $this->warehouse_columns );
    }
}
