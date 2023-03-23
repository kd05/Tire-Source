<?php

/**
 * Rims import for CDA Tire supplier
 *
 * Class SIS_CDA_Tire_Rims_Canada
 */
Class SIS_CDA_Tire_Rims_Canada extends Supplier_Inventory_Supplier {

    const HASH_KEY = Supplier_Inventory_Hash::KEY_CDA_TIRE_RIMS_CA;

    /**
     * SIS_CDA_Tire_Rims_Canada constructor.
     */
    public function __construct() {

        $this->allowed_suppliers                      = [ 'canada-tire', 'canada-tire-supply' ];
        $this->type                                   = 'rims';
        $this->locale                                 = APP_LOCALE_CANADA;
        $this->mark_products_not_in_array_as_not_sold = true;

        $this->ftp = self::cda_tire_universal_ftp_config();
    }

    /**
     *
     */
    public function prepare_for_import() {

        $this->ftp->run();

        // convert CSV to array
        $this->csv = new CSV_To_Array( $this->ftp->get_local_full_path(), array(
            'part_number' => 'sku',
            'stock' => 'qty',
        ) );

        // delete local copy of file
        $this->ftp->unlink();

        $this->array = self::array_map_and_filter( $this->csv->array );
    }
}