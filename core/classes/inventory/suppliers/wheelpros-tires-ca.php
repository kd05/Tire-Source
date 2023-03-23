<?php

/**
 * Class SIS_Wheelpros_Tires_CA
 */
Class SIS_Wheelpros_Tires_CA extends Supplier_Inventory_Supplier {

    const HASH_KEY = Supplier_Inventory_Hash::KEY_WHEELPROS_TIRES_CA;

    /**
     * SIS_Wheelpros_Tires_CA constructor.
     */
    public function __construct() {

        // sFTP DNS Address - sftp.wheelpros.com
        // STATIC IP 1 - 44.234.227.42
        // STATIC IP 2 - 44.234.227.43
        // STATIC IP 3 - 44.234.227.45

        $this->allowed_suppliers                      = [ 'wheelpros', 'wheelpro' ];
        $this->type                                   = 'tires';
        $this->locale                                 = APP_LOCALE_CANADA;

        $this->mark_products_not_in_array_as_not_sold = true;
        $this->if_file_empty_mark_products_not_sold = false;

        $this->ftp                   = new FTP_Get_Csv();
        $this->ftp->method           = 'sftp';
        // better to use one of the static IP's, perhaps.
        $this->ftp->host             = '44.234.227.42';
        $this->ftp->username         = 'click_it_wheels';
        $this->ftp->password         = '##removed';
        $this->ftp->remote_file_name = 'CommonFeed/CAD/TIRE/tireInvPriceData.csv';
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
