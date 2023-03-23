<?php

/**
 * Class SIS_DAI_Rims_US
 */
Class SIS_DAI_Rims_US extends SIS_Dai {

    const HASH_KEY = Supplier_Inventory_Hash::KEY_DAI_RIMS_US;

    /**
     * SIS_DAI_Rims_US constructor.
     */
    public function __construct() {
        $this->allowed_suppliers                      = [ 'dai' ];
        $this->type                                   = 'rims';
        $this->locale                                 = APP_LOCALE_US;
        $this->mark_products_not_in_array_as_not_sold = true;

        $this->warehouse_columns = self::get_warehouse_columns_indexes_via_locale( $this->locale );

        $this->ftp = self::get_ftp_instance();
    }
}