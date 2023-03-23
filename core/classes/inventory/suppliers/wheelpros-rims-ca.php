<?php

/**
 * Class SIS_Wheelpros_Rims_CA
 */
Class SIS_Wheelpros_Rims_CA extends Supplier_Inventory_Supplier {

    const HASH_KEY = Supplier_Inventory_Hash::KEY_WHEELPROS_RIMS_CA;

    /**
     * SIS_The_Wheel_Group_Rims_US constructor.
     */
    public function __construct() {

        // wheelpros 1/2 are for product sync
        $this->allowed_suppliers                      = [ 'wheelpros', 'wheelpro', 'wheelpros-1', 'wheelpros-2' ];
        $this->type                                   = 'rims';
        $this->locale                                 = APP_LOCALE_CANADA;

        $this->mark_products_not_in_array_as_not_sold = true;
        $this->if_file_empty_mark_products_not_sold = false;

        $this->ftp                   = new FTP_Get_Csv();
        $this->ftp->method           = 'sftp';
        $this->ftp->host             = '44.234.227.42';
        $this->ftp->username         = 'click_it_wheels';
        $this->ftp->password         = '##removed';
        $this->ftp->remote_file_name = 'CommonFeed/CAD/WHEEL/wheelInvPriceData.csv';
    }

    /**
     *
     */
    public function prepare_for_import() {

        $this->ftp->run();

        $inv = with( new CSV_To_Array( $this->ftp->get_local_full_path(), [
            'part_number' => 'PartNumber',
            'total_qoh' => 'TotalQOH',
            // seems CA warehouses start with 4. many U.S. warehouses starting with 1.
            // langley b.c.
            '4033' => '4033',
            // toronto
            '4035' => '4035',
        ] ) )->array;

        $inv = array_map( function( $item ){
            $item['stock'] = intval( $item['4033'] ) + intval( $item['4035'] );
            return $item;
        }, $inv );

        $this->array = self::array_map_and_filter( $inv );
    }
}
