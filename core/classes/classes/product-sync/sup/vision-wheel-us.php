<?php

class Product_Sync_Vision_Wheel_US extends Product_Sync {

    const KEY = 'vision_rims_us';
    const TYPE = 'rims';
    const SUPPLIER = 'vision-wheels';
    const LOCALE = 'US';
    const LOCAL_FILE = 'P6 CC spec and pricing.csv';
    const FETCH_TYPE = 'local';

    const CRON_FETCH = true;
    const CRON_PRICES = true;

    /**
     * @return array
     */
    function get_source_req_cols() {
        return Product_Sync_Vision_Wheel_CA::_source_req_cols();
    }

    /**
     * @return array|string[]
     */
    function get_admin_notes(){
        $s = new Product_Sync_Vision_Wheel_CA();
        return $s->get_admin_notes();
    }


    /**
     * @param array $row
     * @return array
     */
    function build_product( array $row ) {
        return Product_Sync_Vision_Wheel_CA::_build_product( $row, $this );
    }
}
