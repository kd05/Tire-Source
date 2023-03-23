<?php

/**
 * Class SIS_The_Wheel_Group_Rims_US
 */
Class SIS_The_Wheel_Group_Rims_US extends Supplier_Inventory_Supplier {

    const HASH_KEY = Supplier_Inventory_Hash::KEY_TWG_RIMS_US;

    /**
     * SIS_The_Wheel_Group_Rims_US constructor.
     */
    public function __construct() {

        $this->allowed_suppliers                      = [ 'wheel-1' ];
        $this->type                                   = 'rims';
        $this->locale                                 = APP_LOCALE_US;
        $this->mark_products_not_in_array_as_not_sold = true;

        $this->ftp                   = new FTP_Get_Csv();
        $this->ftp->method           = 'sftp';
        $this->ftp->host             = self::$our_own_ftp_server_host;
        $this->ftp->username         = 'u95793629-twg';
        $this->ftp->password         = '##removed';
        $this->ftp->remote_file_name = 'WHEEL1INVENTORY.csv';
    }

    /**
     *
     */
    public function prepare_for_import() {

        $this->ftp->run();

        // convert CSV to array
        $this->csv = new CSV_To_Array( $this->ftp->get_local_full_path(), array(
            'part_number' => 'Item',
            'stock' => 'Total Onhand',
        ) );

        // delete local copy of file
        $this->ftp->unlink();

        $this->array = self::array_map_and_filter( $this->csv->array );
    }
}
