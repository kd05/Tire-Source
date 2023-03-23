<?php

/**
 * Class SIS_Robert_Thibert_Rims_US
 */
Class SIS_Robert_Thibert_Rims_US extends SIS_Robert_Thibert_Methods {

    const HASH_KEY = Supplier_Inventory_Hash::KEY_ROBERT_THIBERT_RIMS_US;

    /**
     * SIS_Robert_Thibert_Rims_Canada constructor.
     */
    public function __construct() {
        $this->allowed_suppliers                      = [ 'robert-thibert' ];
        $this->type                                   = 'rims';
        $this->locale                                 = APP_LOCALE_US;
        $this->mark_products_not_in_array_as_not_sold = true;

        $this->ftp                   = new FTP_Get_Csv();
        $this->ftp->method           = 'sftp';
        $this->ftp->host             = self::$our_own_ftp_server_host;
        $this->ftp->username         = 'u95793629-r-thibert';
        $this->ftp->password         = '##removed';
        $this->ftp->remote_file_name = '/US/CIT010-US_INVENTORY.csv';

        $this->warehouses = array(
            'NY',
        );
    }

    /**
     *
     */
    public function prepare_for_import() {
        $this->robert_thibert_parent_class_prepare_for_import();
    }
}
