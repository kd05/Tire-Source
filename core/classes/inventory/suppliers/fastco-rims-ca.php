<?php

Class SIS_Fastco_Rims_CA extends Supplier_Inventory_Supplier {

    const HASH_KEY = Supplier_Inventory_Hash::KEY_FASTCO_RIMS_CA;

    public function __construct() {

        $this->allowed_suppliers                      = [ 'fastco' ];
        $this->type                                   = 'rims';
        $this->locale                                 = APP_LOCALE_CANADA;
        $this->mark_products_not_in_array_as_not_sold = true;

        // setup the FTP object even though we don't use it, because some errors
        // may happen if $this->ftp is null.
        $this->ftp           = new FTP_Get_Csv();
        $this->ftp->method   = 'sftp';
        $this->ftp->host     = '';
        $this->ftp->username = '';
        $this->ftp->password = '';
        $this->ftp->remote_file_name = "N/A (Uses CW proxy)";
    }

    /**
     * Can't fetch from FTP directly, @see Product_Sync_Fastco
     *
     * @return mixed|void
     */
    public function prepare_for_import() {

        $data = Product_Sync_Fastco::get_rims_ca_data();

        $result = [];

        if ( $data ) {

            $result = array_map( function( $row ) {

                $part_number = @$row['PartNo'];
                $stock = (int) @$row['QtyAvailMtl'] + (int) @$row['QtyAvailCalgary'];

                if ( $part_number ) {
                    return [
                        'part_number' => $part_number,
                        'stock' => $stock
                    ];
                }

            }, $data );

            $result = array_filter( $result );
        }

        $this->array = $result;
    }
}
