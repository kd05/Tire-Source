<?php


class Product_Sync_DAI_Wheel_US extends Product_Sync {

    const KEY = 'dai_wheel_us';
    const TYPE = 'rims';
    const SUPPLIER = 'dai';
    const LOCALE = 'US';

    const CRON_FETCH = true;
    const CRON_PRICES = true;

    /**
     * @return FTP_Get_Csv|null
     */
    function get_ftp_obj() {
        $ftp                   = new FTP_Get_Csv();
        $ftp->method           = 'sftp';
        $ftp->host             = self::FTP_SERVER;
        $ftp->username         = 'u95793629-dai';
        $ftp->password         = self::get_credentials()['dai'];
        $ftp->remote_file_name = 'GOWAP_wheel_list.csv';
        return $ftp;
    }

    /**
     * @return array|string[]
     */
    function get_source_req_cols(){
        return Product_Sync_DAI_Wheel_CA::_source_req_cols();
    }

    /**
     * @param array $row
     * @return array
     */
    function build_product( array $row ) {
        return Product_Sync_DAI_Wheel_CA::_build_product( $row, $this );
    }
}
