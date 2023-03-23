<?php

// update_users_password( DB_User::create_instance_via_primary_key(1), 'password' );

/**
 * App specific Kount e-fraud functions and config.
 *
 * Class App_Kount
 */
Class App_Kount {

    const TEST_DATA_COLLECTOR_URL = "https://tst.kaptcha.com";
    const LIVE_DATA_COLLECTOR_URl = "https://ssl.kaptcha.com";

    const TEST_MERCHANT_ID = "760000";
    const TEST_WEBSITE_ID = "BASIC1";
    const TEST_API_KEY = "##removed";

    const LIVE_MERCHANT_ID = "760000";
    const LIVE_WEBSITE_ID = "BASICHI";
    const LIVE_API_KEY = "##removed";

    /**
     * Have to put this on the page somewhere for the javascript to work,
     * apparently.
     *
     * @return string
     */
    public static function get_trigger_html() {

        return '<div style="opacity: 0; height: 0; margin: 0; padding: 0; width: 0;" class="kaxsdc" data-event="load"></div>';
    }

    /**
     * Get the script tag for the data collector process.
     *
     * @param $in_production
     * @param bool $include_trigger
     * @param null $session_id
     * @return false|string
     */
    public static function get_data_collector_html( $in_production, $include_trigger = true, $session_id = null ) {

        $url = $in_production ? self::LIVE_DATA_COLLECTOR_URl : self::TEST_DATA_COLLECTOR_URL;
        $merchant_id = $in_production ? self::LIVE_MERCHANT_ID : self::TEST_MERCHANT_ID;

        $session_id = $session_id !== null ? $session_id : self::get_session_id();
        ob_start();

        if ( $include_trigger ) {
            echo self::get_trigger_html();
        }

        echo Kount_Service::get_script_tag( $url, $merchant_id, $session_id );
        ?>
        <script>

            // script blocked
            // collect begun but not finished
            // collect end and successful i guess

            (function () {
                if (window.ka === undefined) {
                    console.log("window.ka not defined (kount SDK was not loaded)");
                } else {

                    var client = new ka.ClientSDK();

                    console.log('client', client);

                    client.setupCallback({
                        'collect-begin': function (params) {
                            console.log('ka collect begin', params.MercSessId, params.MerchantId);
                        },
                        'collect-end': function (params) {
                            console.log('ka collect end');
                        }
                    });

                    client.autoLoadEvents();
                }
            })();

        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Session ID sent to Kount in both the data collector and the risk
     * inquiry process. There are some important requirements for the
     * format of the returned session ID. These are satisfied by calling
     * md5 on a string. For example, cannot be longer than 32 characters
     * and must not contain special characters.
     *
     * @return string
     */
    public static function get_session_id() {

        // mask the session ID a bit, which is not necessary, but doesn't hurt.
        $constant_1 = "asdo72136asd";
        $constant_2 = "83dkld8as7hg";
        return md5( $constant_1 . session_id() . $constant_2 );
    }

    /**
     * @param $in_production
     * @return array
     */
    public static function get_credentials_array( $in_production ) {

        return [
            'kount_merchant_id' => $in_production ? self::LIVE_MERCHANT_ID : self::TEST_MERCHANT_ID,
            'website_id' => $in_production ? self::LIVE_WEBSITE_ID : self::TEST_WEBSITE_ID,
            'kount_api_key' => $in_production ? self::LIVE_API_KEY : self::TEST_API_KEY,
        ];
    }

    /**
     * Adds some defaults to an inquiry transaction array.
     *
     * Afterwards, you should call Kount_Service::filter_validate_inquiry_txn
     *
     * @param $in_production
     * @param array $ex_txn
     * @return array
     */
    public static function build_inquiry_txn( $in_production, array $ex_txn = [] ) {

        $defaults = array_merge( self::get_credentials_array( $in_production ), [
            'session_id' => self::get_session_id(),
            'ip_address' => app_get_ip_address(),
            'currency' => "CAD",
            'payment_type' => "CARD",
        ] );

        // even null values in $ex_txn will override defaults, in case you were wondering.
        return array_merge( $defaults, $ex_txn );
    }

    /**
     * Adds some defaults to an update transaction array.
     *
     * Afterwards, you should call Kount_Service::filter_validate_update_txn
     *
     * @param $in_production
     * @param array $ex_txn
     * @return array
     */
    public static function build_update_txn( $in_production, array $ex_txn ) {

        $defaults = array_merge( self::get_credentials_array( $in_production ), [
            'session_id' => self::get_session_id(),
            'evaluate' => "false",
        ] );

        // even null values in $ex_txn will override defaults, in case you were wondering.
        return array_merge( $defaults, $ex_txn );
    }

    /**
     * A minimal example of what you might send to Kount, assuming that you merge
     * self::build_transaction, and use Kount_Service::validate_transaction.
     *
     * @return array
     */
    public static function example_inquiry_txn_array() {

        return [
            // 'kount_inquiry' or 'kount_update' (I think always inquiry for now)
            'type' => 'kount_inquiry',
            'email' => 'fake@email.com',
            // merchant order ID, ie. primary key in our orders table
            'order_id' => '',
            'payment_type' => 'CARD',
            // credit card number
            'payment_token' => '',
            // in dollars with 2 decimal places
            'amount' => '',
            // single letter responses, I think
            'avs_response' => '',
            'cvd_response' => '',
        ];
    }
}
