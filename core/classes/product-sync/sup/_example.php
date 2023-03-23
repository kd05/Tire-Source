<?php

class Product_Sync_EXAMPLE extends Product_Sync {

    const KEY = 'egSupplier_rims_ca';
    const TYPE = 'rims';
    const SUPPLIER = 'egSupplier';
    const LOCALE = 'CA';

    /**
     * @return FTP_Get_Csv|null
     */
    function get_ftp_obj() {
        $ftp                   = new FTP_Get_Csv();
        $ftp->method           = 'sftp';
        $ftp->host             = self::FTP_SERVER;
        $ftp->username         = '';
        $ftp->password         = @self::get_credentials()['eg'];
        $ftp->remote_file_name = 'folder/Filename.csv';
        return $ftp;
    }

    /**
     * @return array
     */
    function get_source_req_cols() {
        return [];
    }

    /**
     * @param array $row
     * @return array
     */
    function build_product( array $row ) {

        $errors = $this->check_source_columns( $this->get_source_req_cols(), $row );

        // rims cols
        return [
            'supplier' => '',
            'locale' => '',
            'upc' => '',
            'part_number' => '',
            'brand' => '',
            'model' => '',
            'color_1' => '',
            'color_2' => '',
            'finish' => '',
            'type' => '',
            'width' => '',
            'diameter' => '',
            'bolt_pattern_1' => '',
            'bolt_pattern_2' => '',
            'seat_type' => '',
            'offset' => '',
            'center_bore' => '',
            'style' => '',
            'image' => '',
            'map_price' => '',
            'msrp' => '',
            'cost' => '',
            'stock' => '',
            'discontinued' => '',
            '__meta' => [
                'errors' => $errors,
            ],
        ];
    }
}
