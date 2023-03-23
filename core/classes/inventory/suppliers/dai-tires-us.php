<?php

/**
 * Class SIS_DAI_Tires_Canada
 */
Class SIS_DAI_Tires_US extends SIS_Dai {

    const HASH_KEY = Supplier_Inventory_Hash::KEY_DAI_TIRES_US;

    /**
     * SIS_DAI_Tires_Canada constructor.
     */
    public function __construct() {
        $this->allowed_suppliers                      = [ 'dai' ];
        $this->type                                   = 'tires';
        $this->locale                                 = APP_LOCALE_US;
        $this->mark_products_not_in_array_as_not_sold = true;

        $this->warehouse_columns = self::get_warehouse_columns_indexes_via_locale( $this->locale );

        $this->ftp = self::get_ftp_instance();
    }
}
