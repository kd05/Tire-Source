<?php

/**
 * Class DB_Transaction
 */
Class DB_Transaction extends DB_Table{

	protected static $table = DB_transactions;

	/**
	 * @var string
	 */
	protected static $primary_key = 'transaction_id';

	/**
	 * @var array
	 */
	protected static $fields = array(
		'transaction_id',
		'subtotal',
		'ontario_fee',
		'ontario_fee_qty',
		'shipping',
		'tax',
		'total',
		'currency',
		'card_month',
		'card_year',
		'last_4',
		'last_operation',
		'success',
		'response_code',
		'response_message',
		'cvd_result',
		'avs_result',
		'card_type',
		'auth_code',
		'trans_id',
		'reference_number',
        'kount_data',
        'txn_extra'
	);

	// json decoded, lazy loaded, see the getter.
	private $kount_data__obj = null;

    // json decoded, lazy loaded, see the getter.
	private $txn_extra__obj = null;

	/**
	 * *some* of the indexes correspond to values passed in to CraigPaul/Moneris/Receipt::read(), which
	 * are generally not the exact same was what moneris calls them, but see the function to get the mapping.
	 * a few columns were prefixed to be more clear when inner joining.
	 *
	 * @var array
	 */
	protected static $db_init_cols = array(
		'transaction_id' => 'int(11) unsigned NOT NULL auto_increment',
		// date/time. may or may not update when verified/captured
		'subtotal' => 'varchar(255) default \'\'',
		'ontario_fee' => 'varchar(255) default \'\'',
		'ontario_fee_qty' => 'smallint default NULL',
		'shipping' => 'varchar(255) default \'\'',
		'tax' => 'varchar(255) default \'\'',
		'total' => 'varchar(255) default \'\'',
		'currency' => 'varchar(255) default \'\'',
		'card_month' => 'varchar(255) default \'\'',
		'card_year' => 'varchar(255) default \'\'',
		'last_4' => 'varchar(255) default \'\'',
		// probably: insert|preauth|capture
		'last_operation' => 'varchar(255) default \'\'',
		'success' => 'varchar(255) default \'\'',
		'response_code' => 'varchar(255) default \'\'',
		'response_message' => 'varchar(255) default \'\'',
		'cvd_result' => 'varchar(255) default \'\'',
		'avs_result' => 'varchar(255) default \'\'',
		'card_type' => 'varchar(255) default \'\'',
		'auth_code' => 'varchar(255) default \'\'', // ie. Receipt[$data['authorization']]
		'trans_id' => 'varchar(255) default \'\'', // ie. Receipt[$data['authorization']]
		'reference_number' => 'varchar(255) default \'\'', // ie. Receipt[$data['reference']]
        'kount_data' => 'text',
        'txn_extra' => 'text',
	);

	/**
	 * @var array
	 */
	protected static $db_init_args = array(
		'PRIMARY KEY (`transaction_id`)'
	);

	/**
	 * DB_Transaction constructor.
	 *
     * @param array $data
     * @param array $options
     * @throws Exception
     */
	public function __construct( $data = array(), $options = array() ){
		parent::__construct( $data, $options );
	}

    /**
     * Get the linked order ID.
     *
     * Not particularly efficient if used within a loop.
     *
     * @param bool $get_instance_instead
     * @return bool|DB_Order|DB_Table|null
     * @throws Exception
     */
	public function get_order_id( $get_instance_instead = false ) {

	    $db = get_database_instance();

        $orders = $db->get( DB_orders, [
            'transaction_id' => $this->get_primary_key_value(),
        ], [
            'transaction_id' => '%d',
        ]);

        $order_id = $orders ? $orders[0]->order_id : false;

        if ( ! $order_id ) {
            return false;
        }

        if ( $get_instance_instead ) {
            return DB_Order::create_instance_via_primary_key( $order_id );
        }

        return (int) $order_id;
    }

    /**
     * Returns an object. $this->get( 'kount_data' ) will return
     * a JSON string.
     *
     * @return mixed|null
     */
	public function get_kount_data(){
	    return $this->lazy_load_json( 'kount_data' );
    }

    /**
     * Helper function to update the kount data which is a JSON serialized object.
     *
     * Pass in an updater function which will be given the existing JSON decoded
     * object.
     *
     * @param callable $updater
     * @return bool
     * @throws Exception
     */
    public function update_kount_data( callable $updater ) {

	    $ex = $this->get_kount_data();
	    $new = $updater( $ex );
	    return $this->update_database_and_re_sync( [
	        'kount_data' => json_encode( $new ),
        ]);
    }

    /**
     * See update_kount_data
     *
     * @param callable $updater
     * @return bool
     * @throws Exception
     */
    public function update_txn_extra( callable $updater ) {

        $ex = $this->get_txn_extra();
        $new = $updater( $ex );
        return $this->update_database_and_re_sync( [
            'txn_extra' => json_encode( $new ),
        ]);
    }

    /**
     * @return array|mixed
     */
    public function get_kount_inquiry_request(){
        $d = $this->get_kount_data();
        $ret = @$d['inquiry_request'];
        return is_array( $ret ) ? $ret : [];
    }

    /**
     * The entire response array from the risk inquiry
     *
     * @return array|mixed
     */
    public function get_kount_inquiry_response(){
        $d = $this->get_kount_data();
        $ret = @$d['inquiry_response'];
        return is_array( $ret ) ? $ret : [];
    }

    /**
     * @return array|mixed
     */
    public function get_kount_update_request(){
        $d = $this->get_kount_data();
        $ret = @$d['update_request'];
        return is_array( $ret ) ? $ret : [];
    }

    /**
     * @return array|mixed
     */
    public function get_kount_update_response(){
        $d = $this->get_kount_data();
        $ret = @$d['update_response'];
        return is_array( $ret ) ? $ret : [];
    }

    /**
     * ie. "A", "D",
     *
     * or if $readable, "Accept", "Decline".
     *
     * @param bool $readable - if false, then "A" or "D", etc.
     * @return string
     */
    public function get_kount_inquiry_result_code( $readable = false ) {

        // $code = @$this->get_kount_data()['KountResult'];
        $response = $this->get_kount_inquiry_response();
        $code = @$response['KountResult'];

        return $readable ? self::get_kount_result_code_map($code) : $code;
    }

    /**
     * Convert Kount response code to human readable text for
     * display in the back-end.
     *
     * @param $code
     * @return string
     */
    public static function get_kount_result_code_map( $code ) {

        if ( $code == 'A' ) {
            return "Accept";
        }

        if ( $code == 'D' ) {
            return "Decline";
        }

        if ( $code == 'R' ) {
            return "Review";
        }

        return "Unrecognized Code: " . gp_test_input( $code );
    }

    /**
     * @return string
     */
    public function get_kount_score(){
        return @$this->get_kount_inquiry_response()['KountScore'];
    }

    /**
     * Returns an object. $this->get( 'kount_data' ) will return
     * a JSON string.
     *
     * @return mixed|null
     */
    public function get_txn_extra(){
        return $this->lazy_load_json( 'txn_extra' );
    }

    /**
     * Possibly adds two columns to the transactions table.
     *
     * I might just call this on script load for a while, and then
     * eventually remove it.
     */
	public static function check_table_ddl_for_kount_updates(){

	    $key = "txn_cols_added_for_kount_updates";

	    if ( ! cw_get_option( $key ) ) {

	        $db = get_database_instance();

	        cw_set_option( $key, 1 );

	        foreach ( [ 'kount_data', 'txn_extra'] as $col ) {
	            $db->execute( static::get_add_column_ddl( $col ) );
            }
        }
    }
}


