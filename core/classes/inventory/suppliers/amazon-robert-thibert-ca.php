<?php

/**
 * For accessories inventory to send to Amazon. The file used for
 * CA rims also has all canada inventory in it, including accessories,
 * so extend the rt canada rims class and override some values.
 *
 * Class SIS_Amazon_Robert_Thibert_CA
 */
class SIS_Amazon_Robert_Thibert_CA extends SIS_Robert_Thibert_Rims_Canada{

    const HASH_KEY = Supplier_Inventory_Hash::KEY_AMAZON_ROBERT_THIBERT_CA;

    const AMAZON_ONLY = true;

    public function __construct(){

        parent::__construct();

        $this->ftp->local_filename_prefix = "amz-";

        // possibly has no effect
        $this->allowed_suppliers = [];

        // possibly has no effect
        $this->type = "";

        // possibly has no effect
        $this->mark_products_not_in_array_as_not_sold = false;

        $this->locale = APP_LOCALE_CANADA;
    }

}