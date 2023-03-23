<?php

/**
 * Class SIS_Robert_Thibert_Rims_Canada
 */
Class SIS_Robert_Thibert_Rims_Canada extends SIS_Robert_Thibert_Methods {

    const HASH_KEY = Supplier_Inventory_Hash::KEY_ROBERT_THIBERT_RIMS_CA;

    /**
     * SIS_Robert_Thibert_Rims_Canada constructor.
     */
    public function __construct() {
        $this->allowed_suppliers                      = [ 'robert-thibert' ];
        $this->type                                   = 'rims';
        $this->locale                                 = APP_LOCALE_CANADA;
        $this->mark_products_not_in_array_as_not_sold = true;

        $this->ftp           = new FTP_Get_Csv();
        $this->ftp->method   = 'sftp';
        $this->ftp->host     = self::$our_own_ftp_server_host;
        $this->ftp->username = 'u95793629-r-thibert';
        $this->ftp->password = '##removed';
        // used to be this:
        // $ftp->remote_file_name = 'CIT010_INVENTORY.csv';
        $this->ftp->remote_file_name = 'CIT010-CAD_INVENTORY.csv';

        // note: do not include NY for canadian import (duh)
        $this->warehouses = array(
            'CAL',
            'EDM',
            'MCT', // Moncton I think....
            'MTL',
            'TOR',
            'VAN',
            'WIN',
        );
    }

    /**
     *
     */
    public function prepare_for_import() {
        $this->robert_thibert_parent_class_prepare_for_import();
    }
}

