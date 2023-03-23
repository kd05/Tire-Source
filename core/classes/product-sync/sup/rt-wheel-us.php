<?php

class Product_Sync_RT_Wheel_US extends Product_Sync {

    const KEY = 'rt_wheel_us';
    const TYPE = 'rims';
    const SUPPLIER = 'robert-thibert';
    const LOCALE = 'US';

    const CRON_FETCH = true;
    const CRON_PRICES = true;

    /**
     * @return FTP_Get_Csv|null
     */
    function get_ftp_obj() {
        $ftp = new FTP_Get_Csv();
        $ftp->method = 'sftp';
        $ftp->host = self::FTP_SERVER;
        $ftp->username = 'u95793629-r-thibert';
        $ftp->password = self::get_credentials()['rt'];
        $ftp->remote_file_name = '/US/CIT010-US_WHEEL.csv';
        return $ftp;
    }

    /**
     * @return array
     */
    function get_source_req_cols() {
        return Product_Sync_RT_Wheel_CA::_source_req_cols();
    }

    /**
     * @param array $row
     * @return array
     */
    function build_product( array $row ) {
        return Product_Sync_RT_Wheel_CA::_build_product( $row, $this );
    }
}
