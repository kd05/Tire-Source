<?php
/**
 * This is a modified copy of the code in the kountClasses.php file from this repo:
 *
 * https://github.com/Moneris/eCommerce-Kount-API-PHP
 *
 * The repo itself is unfortunately pure trash. So this is a heavily modified version of it,
 * which is still kind of trash, because it inherited a lot from the old repo.
 */

namespace MonerisKountAPI;

class kountException extends \Exception{}

class kountCredentials{

    public $store_id;
    public $api_token;

    public function __construct($store_id, $api_token) {
        $this->store_id = $store_id;
        $this->api_token = $api_token;
    }
}

/**
 * Pass this to an httpPost or w/e
 *
 * Class kountConfig
 * @package MonerisKountAPI
 */
class kountConfig {

    public $host;
    public $protocol;
    public $file;
    public $port;
    public $api_version;
    public $client_timeout;

    public function __construct( $sandbox = true ) {

        $this->protocol = "https";

        if ( $sandbox ) {
            $this->host = "esqa.moneris.com";
        } else {
            $this->host = "www3.moneris.com";
        }

        $this->file = "/gateway2/servlet/MpgRequest";
        $this->port = "443";

        $this->api_version = "PHP - 1.2.0 - KOUNT";
        $this->client_timeout = "60";
    }

    /**
     * Generate the endpoint.
     *
     * @return string
     */
    public function to_url(){
        return implode( "", [
            $this->protocol . "://",
            $this->host . ":" . $this->port,
            $this->file
        ] );
    }
}

class kountHttpsPost {

    /**
     * @var kountConfig
     */
    public $config;

    /**
     * @var kountCredentials
     */
    public $credentials;

    /**
     * @var kountRequest
     */
    public $request;

    /**
     * @var kountResponse
     */
    public $response;

    /**
     * Raw request XML for debugging.
     *
     * @var
     */
    public $request_xml;

    /**
     * Raw response XML for debugging.
     *
     * @var
     */
    public $response_xml;

    public function __construct( kountRequest $request, kountConfig $config, kountCredentials $credentials ) {

        $this->request = $request;
        $this->config = $config;
        $this->credentials = $credentials;

        $this->request_xml = $this->toXML();

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $this->config->to_url() );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $this->request_xml );
        curl_setopt( $ch, CURLOPT_TIMEOUT, $this->config->client_timeout );
        curl_setopt( $ch, CURLOPT_USERAGENT, $this->config->api_version );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, TRUE );

        $response = curl_exec( $ch );

        // does not include the default response below
        $this->response_xml = $response;

        curl_close( $ch );

        if ( ! $response ) {

            $response = "<?xml version=\"1.0\"?><response><receipt>" .
                "<ReceiptId>Global Error Receipt</ReceiptId>" .
                "<ResponseCode>null</ResponseCode>" .
                "<AuthCode>null</AuthCode><TransTime>null</TransTime>" .
                "<TransDate>null</TransDate><TransType>null</TransType><Complete>false</Complete>" .
                "<Message>null</Message><TransAmount>null</TransAmount>" .
                "<CardType>null</CardType>" .
                "<TransID>null</TransID><TimedOut>null</TimedOut>" .
                "</receipt></response>";
        }

        $this->kountResponse = new kountResponse( $response );
    }

    public function getkountResponse() {

        return $this->kountResponse;
    }

    public function toXML() {

        $s = $this->credentials->store_id;
        $a = $this->credentials->api_token;

        return "<?xml version=\"1.0\"?>" .
            "<request>" .
            "<store_id>$s</store_id>" .
            "<api_token>$a</api_token>" .
            $this->request->toXML() .
            "</request>";
    }
}

class kountResponse {

    var $responseData;

    var $p; //parser

    var $currentTag;
    var $isKountInfo;
    var $kountInfo = array();

    public function __construct( $xmlString ) {
        $this->p = xml_parser_create();
        xml_parser_set_option( $this->p, XML_OPTION_CASE_FOLDING, 0 );
        xml_parser_set_option( $this->p, XML_OPTION_TARGET_ENCODING, "UTF-8" );
        xml_set_object( $this->p, $this );
        xml_set_element_handler( $this->p, "startHandler", "endHandler" );
        xml_set_character_data_handler( $this->p, "characterHandler" );
        xml_parse( $this->p, $xmlString );
        xml_parser_free( $this->p );
    }

    public function getkountResponse() {
        return @$this->responseData;
    }

    public function getReceiptId() {
        return @$this->responseData[ 'ReceiptId' ];
    }

    public function getResponseCode() {
        return @$this->responseData[ 'ResponseCode' ];
    }

    public function getMessage() {
        return @$this->responseData[ 'Message' ];
    }

    public function getKountInfo() {
        return @$this->kountInfo;
    }

    public function getKountResult() {
        return @$this->responseData[ 'KountResult' ];
    }

    public function getKountScore() {
        return @$this->responseData[ 'KountScore' ];
    }

    public function characterHandler( $parser, $data ) {

        @$this->responseData[ $this->currentTag ] .= $data;

        if ( $this->isKountInfo ) {
            $this->kountInfo[ $this->currentTag ] = $data;

        }
    }

    public function startHandler( $parser, $name, $attrs ) {

        $this->currentTag = $name;

        if ( $this->currentTag == "KountInfo" ) {
            $this->isKountInfo = 1;
        }
    }

    public function endHandler( $parser, $name ) {

        $this->currentTag = $name;

        if ( $name == "KountInfo" ) {
            $this->isKountInfo = 0;
        }

        $this->currentTag = "/dev/null";
    }
}

class kountRequest {

    public static $fieldsByType = [
        'kount_inquiry' => array( 'kount_merchant_id', 'kount_api_key', 'order_id', 'call_center_ind', 'currency', 'data_key', 'email', 'customer_id', 'auto_number_id', 'financial_order_id', 'payment_token', 'payment_type', 'ip_address', 'session_id', 'website_id', 'amount', 'payment_response', 'avs_response', 'cvd_response', 'bill_street_1', 'bill_street_2', 'bill_country', 'bill_city', 'bill_postal_code', 'bill_phone', 'bill_province', 'dob', 'epoc', 'gender', 'last4', 'customer_name', 'ship_street_1', 'ship_street_2', 'ship_country', 'ship_city', 'ship_email', 'ship_name', 'ship_postal_code', 'ship_phone', 'ship_province', 'ship_type', 'udf' ),
        'kount_update' => array( 'kount_merchant_id', 'kount_api_key', 'order_id', 'session_id', 'kount_transaction_id', 'evaluate', 'refund_status', 'payment_response', 'avs_response', 'cvd_response', 'last4', 'financial_order_id', 'payment_token', 'payment_type' )
    ];

    /**
     * @var kountTransaction
     */
    public $transaction;

    public function __construct( kountTransaction $transaction ) {
        $this->transaction = $transaction;
    }

    public static function get_allowed_inquiry_fields(){
        return self::$fieldsByType['kount_inquiry'];
    }

    public static function get_allowed_update_fields(){
        return self::$fieldsByType['kount_update'];
    }

    public function toXML() {

        $txnArray = $this->transaction->getArray();

        $type = $txnArray['type'];

        if ( ! $type ) {
            Throw new kountException("No transaction type specified.");
        }

        if ( ! isset( self::$fieldsByType[$type] ) ){
            Throw new kountException("Transaction type must be kount_inquiry or kount_update, not $type.");
        }

        $allowed_keys = self::$fieldsByType[$type];

        $xml_array = [];

        foreach ( $allowed_keys as $key ) {
            if ( array_key_exists( $key, $txnArray ) ) {
                $xml_array[$key] = $txnArray[$key];
            }
        }

        $prod_fields = ["prod_type_", "prod_item_", "prod_desc_", "prod_quant_", "prod_price_"];

        // add dynamic product fields, ie, prod_type_1, where 1 is the first product.
        foreach( $txnArray as $key => $val ) {

            foreach ( $prod_fields as $field ) {

                // if it starts with prod_type_, prod_item_, prod_desc_, etc.
                if ( strpos( $val, $field ) === 0 ) {
                    $xml_array[$key] = $val;
                }
            }
        }

        $ret = "";

        $ret .= "<$type>";

        // a bit gross to have no sanitation at all, but that's how it used to be.
        // we'll have to expect that all html/xml special chars are escaped beforehand.
        // and you'll just have to know that this comment is here. good luck.
        foreach ( $xml_array as $xml_key => $xml_val ) {
            $ret .= "<$xml_key>";
            $ret .= $xml_val;
            $ret .= "</$xml_key>";
        }

        $ret .= "</$type>";

        return $ret;
    }
}


class kountTransaction {

    public $arr;
    public $attributeAccountInfo = null;
    public $sessionAccountInfo = null;

    public function __construct( array $txn_array ) {

        $this->arr = $txn_array;
    }

    public function getArray() {

        return $this->arr;
    }

}
