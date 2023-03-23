<?php

/**
 * Class SIS_DT_Tire_Tires_Canada
 */
Class SIS_DT_Tire_Tires_Canada extends Supplier_Inventory_Supplier {

    const HASH_KEY = Supplier_Inventory_Hash::KEY_DT_TIRE_TIRES_CA;

    /**
     * SIS_CDA_Tire_Rims_Canada constructor.
     */
    public function __construct() {

        $this->allowed_suppliers                      = [ 'dt' ];
        $this->type                                   = 'tires';
        $this->locale                                 = APP_LOCALE_CANADA;
        $this->mark_products_not_in_array_as_not_sold = true;

        $this->ftp           = new FTP_Get_Csv();
        $this->ftp->method   = 'sftp';
        $this->ftp->host     = self::$our_own_ftp_server_host;
        $this->ftp->username = 'u95793629-dt-tire';
        $this->ftp->password = '##removed';

        $this->ftp->remote_file_name = "Production/From_DTTire/DT_Inventory.csv";
    }

    /**
     *
     */
    public function prepare_for_import() {

        $this->ftp->run();

        /**
         * This file is unlike all the other files because of the presence of the discontinued column.
         * We need to implement a bit of logic that can easily be handled in this phase of the import without
         * touching the product import script that processes $this->array.
         *
         * If a product is discontinued but has quantity -> continue to sell it on our site.
         * If a product is discontinued but no quantity -> mark is discontinued on our site (will not be sold)
         * If a product is not discontinued just treat it like all other suppliers -> if in stock, sell it,
         * if not in stock, show it in search results as out of stock and don't allow adding to cart.
         *
         * Therefore, the way to accomplish this is to simply remove the rows that are discontinued
         * and have a quantity of zero. Then the next phase (the import script) will process the same
         * way that other suppliers do (which at this time is: making products not sold via the "discontinued"
         * columns when not found in an import script).
         */
        $this->csv = new CSV_To_Array( $this->ftp->get_local_full_path(), [
            'part_number' => 0,
            'stock' => 4,
        ], false );

        $this->ftp->unlink();

        $array = array_map( function ( $row ) {
            // redundant array map
            return array(
                'part_number' => $row[ 'part_number' ],
                'stock' => $row[ 'stock' ] ,
            );

        }, $this->csv->array );

        // remove empty
        $array = array_values( array_filter( $array ) );

        // now object is ready for import
        $this->array = $array;
    }
}
