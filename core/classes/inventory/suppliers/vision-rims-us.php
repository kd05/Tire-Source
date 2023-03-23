<?php

/**
 * Class SIS_Vision_Rims_US
 */
Class SIS_Vision_Rims_US extends SIS_Vision {

    const HASH_KEY = Supplier_Inventory_Hash::KEY_VISION_RIMS_US;

    /**
     * SIS_Vision_Rims_US constructor.
     */
    public function __construct() {
        $this->allowed_suppliers                      = [ 'vision-wheels' ];
        $this->type                                   = 'rims';
        $this->locale                                 = APP_LOCALE_US;
        $this->mark_products_not_in_array_as_not_sold = true;
        $this->stock_column_name_in_csv               = 'INTQNTVW';
        $this->ftp                                    = self::vision_ftp_object();
    }
}
