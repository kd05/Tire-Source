<?php

/**
 * Class SIS_The_Wheel_Group_Rims_Canada
 */
Class SIS_The_Wheel_Group_Rims_Canada extends Supplier_Inventory_Supplier {

    const HASH_KEY = Supplier_Inventory_Hash::KEY_TWG_RIMS_CA;

    /**
     * SIS_The_Wheel_Group_Rims_Canada constructor.
     */
    public function __construct() {

        $this->allowed_suppliers                      = [ 'wheel-1' ];
        $this->type                                   = 'rims';
        $this->locale                                 = APP_LOCALE_CANADA;

        $this->mark_products_not_in_array_as_not_sold = true;
        $this->if_file_empty_mark_products_not_sold = false;

        $this->ftp           = new FTP_Get_Csv();
        $this->ftp->method   = 'sftp';
        $this->ftp->host     = self::$our_own_ftp_server_host;
        $this->ftp->username = 'u95793629-twg';
        $this->ftp->password = '##removed';

        // supplier keeps changing up these file names. sometimes one doesn't exist, and then sometimes
        // another is left not being updated.
        // WHEEL1INVENTORY_CA.csv
        // Wheel1inventory_CA.csv
        // $this->ftp->remote_file_name = 'Wheel1inventory_CA.csv';
        $this->ftp->remote_file_name = 'WHEEL1INVENTORY_CA.csv';
    }

    /**
     *
     */
    public function prepare_for_import() {

        $this->ftp->run();

        // convert CSV to array
        $this->csv = new CSV_To_Array( $this->ftp->get_local_full_path(), array(
            'part_number' => 'Item',
            'stock' => 'Total',
        ) );

        // delete local copy of file
        $this->ftp->unlink();

        $this->array = self::array_map_and_filter( $this->csv->array );
    }
}
