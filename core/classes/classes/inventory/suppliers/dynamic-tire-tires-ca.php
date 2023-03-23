<?php

/**
 * Class SIS_Dynamic_Tire_Tires_CA
 */
Class SIS_Dynamic_Tire_Tires_CA extends Supplier_Inventory_Supplier {

    const HASH_KEY = Supplier_Inventory_Hash::KEY_DYNAMIC_TIRE_TIRES_CA;

    /**
     * SIS_Dynamic_Tire_Tires_CA constructor.
     */
    public function __construct() {

        $this->allowed_suppliers                      = [ 'dynamic-tire' ];
        $this->type                                   = 'tires';
        $this->locale                                 = APP_LOCALE_CANADA;
        $this->mark_products_not_in_array_as_not_sold = true;

        $this->ftp           = new FTP_Get_Csv();
        $this->ftp->method   = 'sftp';
        $this->ftp->host     = self::$our_own_ftp_server_host;
        $this->ftp->username = 'u95793629-dynamic';
        $this->ftp->password = '##removed';

        // yes with a date in the past and a space, brilliant
        // $this->ftp->remote_file_name = 'ItemInfos_04022019_ 93107.csv';
        // $this->ftp->remote_file_name = "ItemInfos.csv";
        $this->ftp->remote_file_name = "FileInfos.csv";
    }

    /**
     * Populate $this->array
     */
    public function prepare_for_import() {

        $this->ftp->run();

        $this->csv = new CSV_To_Array( $this->ftp->get_local_full_path(), [
            'part_number' => 0,
            'brand' => 2,
            'qty_toronto' => 3,
            'qty_montreal' => 4,
        ], false );

        $this->ftp->unlink();

        $this->array = $this->csv->array ? array_map( function ( $row ) {

            $qty_toronto  = self::convert_qty_value_to_int( $row[ 'qty_toronto' ] );
            $qty_montreal = self::convert_qty_value_to_int( $row[ 'qty_montreal' ] );
            $stock        = $qty_toronto + $qty_montreal;

            return array(
                'part_number' => $row[ 'part_number' ],
                'stock' => $stock,
            );
        }, $this->csv->array ) : [];
    }
}
