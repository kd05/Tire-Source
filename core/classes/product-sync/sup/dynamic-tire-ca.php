<?php

use Curl\Curl as Curl;

class Product_Sync_Dynamic_Tire_CA extends Product_Sync {

    const KEY = 'dynamic_tire_ca';
    const TYPE = 'tires';
    const SUPPLIER = 'dynamic-tire';
    const LOCALE = 'CA';
    const FETCH_TYPE = 'api';
    const API_ACCOUNT = '11296';

    const CRON_FETCH = false;
    const CRON_PRICES = false;
    const CRON_EMAIL = false;

    /**
     * @return array|string[]
     */
    function get_admin_notes(){
        return [ "Not finished yet. Waiting on supplier." ];
    }

    /**
     * @param Time_Mem_Tracker $mem
     * @return array|void
     */
    function fetch_api( Time_Mem_Tracker $mem ){

        $account = self::API_ACCOUNT;
        $key = self::get_credentials()['dynamic-api'];
        $url = "https://b2b-api.dynamictire.com/api/item/searchItems/?secretKey=$key&CustomerCd=$account";

        $curl = new Curl();
        $res = $curl->get( $url );

        $response_arr = json_decode( $res->response, JSON_INVALID_UTF8_IGNORE );

        if ( json_last_error() ) {
            log_data( [
                'err' => json_last_error_msg(),
            ], 'dynamic-tire-json-err' );
        }

        $payload = @$response_arr['data'];
        return is_array( $payload ) ? $payload : [];
    }

    /**
     * @param array $row
     * @return array
     */
    function build_product( array $row ) {

        $errors = $this->check_source_columns( $this->get_source_req_cols(), $row );

        $desc = @$row['description'];
        $brand = @$row['brand'];
        $modelType = @$row['modelType'];
        $before_model_type = @explode( $modelType, $desc )[0];
        $model_name = trim( @explode( $brand, $before_model_type )[1] );

        $tireSize = new Parsed_Tire_Size_String( @$row['size'] );

        if ( $tireSize->error ) {
            $errors[] = "Could not parse tire size string ($tireSize->error_type)";
        }

        $load_indexes = explode( "/", @$row['li'] );

        return [
            'upc' => '',
            'part_number' => @$row['sku'],
            'brand' => $brand,
            'model' => $model_name,
            'type' => '',
            'class' => '',
            'category' => '',
            'image' => @$row['largeImageLink'],
            'size' => @$row['size'],
            'width' => $tireSize->width,
            'profile' => $tireSize->profile,
            'diameter' => $tireSize->diameter,
            'load_index_1' => @$load_indexes[0],
            'load_index_2' => @$load_indexes[1],
            'speed_rating' => @$row['sr'],
            'is_zr' => '',
            'extra_load' => '',
            'tire_sizing_system' => '',
            'map_price' => '',
            'msrp' => @$row['listPrice'],
            'cost' => '',
            'stock' => '',
            'discontinued' => '',
            '__meta' => [
                'errors' => $errors,
            ],
        ];
    }
}

