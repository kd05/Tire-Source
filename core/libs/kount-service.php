<?php

use MonerisKountAPI as Moneris;

/**
 * A public API that wraps some Moneris code, and is used to send
 * risk inquiries to Kount, ie. during checkout.
 *
 * Class Kount_Service
 */
Class Kount_Service {

    /**
     * Last inquiry response raw XML string. Used for debugging only.
     *
     * @var string
     */
    public static $last_response_xml = "";

    /**
     * Last request in raw XML, for debugging.
     *
     * @var string
     */
    public static $last_request_xml = "";

    /**
     * Returns the script tag for the data collector.
     *
     * Does not sanitize your input arguments.
     *
     * The script tag doesn't do much on its own. Refer to Moneris docs.
     * You have to add a div to the page with some class and then write
     * a bit of javascript to trigger the data collection process.
     *
     * @param $data_collector_url
     * @param $merchant_id
     * @param $session_id
     * @return false|string
     */
    public static function get_script_tag( $data_collector_url, $merchant_id, $session_id ) {

        if ( strpos( $data_collector_url, '/collect/sdk' ) === false ) {
            $data_collector_url = rtrim( $data_collector_url, '/' ) . '/collect/sdk';
        }

        $url = $data_collector_url . '?m=' . $merchant_id . '&s=' . $session_id;

        ob_start();
        ?>
        <script type="text/javascript" src="<?= $url; ?>"></script>
        <?php
        return ob_get_clean();
    }

    /**
     * @param array $txn
     * @return array
     */
    public static function filter_validate_update_txn( array $txn ){

        $defaults = [
            'type' => 'kount_update',
            'payment_type' => "CARD",
        ];

        return array_merge( $defaults, $txn );
    }

    /**
     * Validates your transaction array and adds some defaults.
     *
     * You should call this on your array before calling send_inquiry.
     *
     * @param $txn
     * @param array $products
     * @return array
     */
    public static function filter_validate_inquiry_txn( array $txn, $products = [] ) {

        $defaults = [
            'type' => 'kount_inquiry',
            'call_center_ind' => "N",
            // "If the ANI cannot be determined, merchant must pass 0123456789 as the ANID" - wow cool
            'auto_number_id' => "0123456789",
            'payment_type' => "CARD",
        ];

        $txn = self::merge_products( $txn, $products );

        return array_merge( $defaults, $txn );
    }

    /**
     * Send a "kount_inquiry" or "kount_update" request.
     *
     * Use one of the filter_validate_* methods on your transaction array first (recommended!).
     *
     * Note: kount credentials such as merchant_id, api_token, and website_id are
     * indexes of your $txn_array.
     *
     * Call self::filter_and_validate_txn() on $txn_array before passing it in.
     *
     * @param array $txn_array
     * @param $moneris_store_id
     * @param $moneris_api_token - not the kount api token
     * @param $test_mode
     * @return array
     */
    public static function send_request( array $txn_array, $moneris_store_id, $moneris_api_token, $test_mode ) {

        $transaction = new Moneris\kountTransaction( $txn_array );

        $request = new Moneris\kountRequest( $transaction );

        $config = new Moneris\kountConfig( (bool) $test_mode );

        $credentials = new Moneris\kountCredentials( $moneris_store_id, $moneris_api_token );

        $post = new Moneris\kountHttpsPost( $request, $config, $credentials );

        // for debugging
        self::$last_request_xml = $post->request_xml;
        self::$last_response_xml = $post->response_xml;

        return self::parse_response( $post->getkountResponse() );
    }

    /**
     * You can use this to build a product array in the correct format, if you prefer.
     *
     * @param $type
     * @param $item
     * @param $desc
     * @param $quantity
     * @param $price
     * @return array
     */
    public static function build_product( $type, $item, $desc, $quantity, $price ) {

        return [
            'type' => $type,
            'item' => $item,
            'desc' => $desc,
            'quant' => $quantity,
            'price' => $price,
        ];
    }

    /**
     * You will have to call this function on your $txn array
     * before sending to send_inquiry.
     *
     * Note: there is a limit of 5 products that can be sent. I think
     * it might be safest to silently ignore more than 5 passed in,
     * so that we don't get a scenario where it works until we hit
     * production and someone orders 6 products.
     *
     * Note: not to be called two times on the same array.
     *
     * @param $txn - an empty array or your txn array
     * @param $products - an array of product arrays. see eg_product
     * @return array
     */
    public static function merge_products( array $txn, array $products ) {

        foreach ( array_values( $products ) as $index => $product ) {

            // We have to pass 1 to 5 not 0 to 4
            $_ii = $index + 1;

            // actually, we can do this elsewhere.
//            if ( $index > 4 ) {
//                break;
//            }

            // letting this fail if you pass in malformed data.
            foreach ( self::get_product_keys() as $key ) {

                // ie. "prod_desc_1"
                $txn_key = self::make_txn_product_key( $_ii, $key );

                // the key should not already be set and for now, will enforce this if it is.
                assert( ! isset( $txn[ $txn_key ] ) );

                $txn[ $txn_key ] = $product[ $key ];
            }
        }

        return $txn;
    }

    /**
     * Product data is sent in the top level keys the XML, not
     * in a nested array structure like you might expect.
     *
     * @param $index
     * @param $product_key
     * @return string
     */
    protected static function make_txn_product_key( $index, $product_key ) {

        return "prod_" . $product_key . "_" . $index;
    }

    /**
     * Partial product keys for request vars such as prod_type_1
     *
     * @return array
     */
    protected static function get_product_keys() {

        return [ "type", "item", "desc", "quant", "price" ];
    }

    /**
     * The send_inquiry function returns some response object, this wil turn that into an array.
     *
     * @param Moneris\kountResponse $response
     * @return array
     */
    protected static function parse_response( Moneris\kountResponse $response ) {

        // I guess, use PascalCase, to be consistent with Moneris documentation
        return [
            'ResponseCode' => $response->getResponseCode(),
            'ReceiptId' => $response->getReceiptId(),
            'Message' => $response->getMessage(),
            'KountResult' => $response->getKountResult(),
            'KountScore' => $response->getKountScore(),
            'KountInfo' => $response->getKountInfo(),
        ];
    }

}