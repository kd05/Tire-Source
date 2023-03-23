<?php

/**
 * Many amazon requests take many steps, including initiating a request, intermittently
 * polling to see if the request is done, and then retrieving the data.
 *
 * There is also a subscriptions API where you can setup push notifications when requests
 * are done, however, this unfortunately might be more hassle than it is worth, especially
 * to test in a not-live environment (localhost). So for now at least, to update inventory data
 * from the website to amazon, we're going to use a polling method to grab the product data
 * from amazon, so we can assemble a list of inventory data along with part numbers to submit
 * later on as a feed. We may also use this to check on the status of the inventory feeds after updating.
 *
 * Class DB_Amazon_Process
 */
Class DB_Amazon_Process extends DB_Table{

	protected static $primary_key = 'process_id';
	protected static $prefix = 'process_';
	protected static $table = DB_amazon_processes;

	/*
	 * An array of keys required to instantiate the object.
	 */
	protected static $req_cols = array();

	// db columns
	protected static $fields = array(
		'process_id',
		'process_locale',
		'process_report_id',
		'process_complete',
		'process_type',
		'process_status',
		'process_time_start',
		'process_time_last',
		'process_steps',
		'feed_submission_result_1',
		'feed_submission_result_2',
		'process_mutable_array',
	);

	protected static $db_init_cols = array(
		'process_id' => 'int(11) unsigned NOT NULL auto_increment',
		'process_report_id' => 'text',
		'process_locale' => 'varchar(255)',
		'process_complete' => 'bool DEFAULT NULL',
		'process_type' => 'varchar(255)',
		'process_status' => 'varchar(255)',
		'process_time_start' => 'int',
		'process_time_last' => 'int',
		'process_steps' => 'varchar(255)',
		'feed_submission_result_1' => 'longtext',
		'feed_submission_result_2' => 'longtext',
		'process_mutable_array' => 'longtext',
	);

	protected static $db_init_args = array(
		'PRIMARY KEY (`process_id`)',
	);

	protected $data;

	/**
	 * json decoded version of a string in $data
	 *
	 * @var
	 */
	private $mutable_array;
	private $feed_submission_result_1;
	private $feed_submission_result_2;

	/**
	 * DB_Cache constructor.
	 *
	 * @param       $data
	 * @param array $options
	 */
	public function __construct( $data, $options = array() ){
		parent::__construct( $data, $options );
	}

	/**
	 *
	 */
	public function re_sync(){
		parent::re_sync();
		$this->mutable_array = null;
		$this->feed_submission_result_1 = null;
		$this->feed_submission_result_2 = null;
	}

	/**
	 * dynamically bound to parent::set()
	 */
	public function callback_on_set_process_mutable_array(){
		$this->mutable_array = null;
	}

	/**
	 * dynamically bound to parent::set()
	 */
	public function callback_on_set_feed_submission_result_1(){
		$this->feed_submission_result_1 = null;
	}

	/**
	 * dynamically bound to parent::set()
	 */
	public function callback_on_set_feed_submission_result_2(){
		$this->feed_submission_result_2 = null;
	}

	/**
	 * caches array result in class property, otherwise json decodes
	 * an element of $this->data.
	 *
	 * Things get a bit messy now making sure both the private class property (array)
	 * and data[$data_index} (json string) represent the same data.
	 *
	 * See $this->callback_on_set_{$class_property}, and $this->re_sync().
	 *
	 * @param $class_property
	 * @param $data_index
     * @return mixed
	 */
	public function get_arr_from_serialized_data_index( $class_property, $data_index ) {

		if ( isset( $this->{$class_property} ) && is_array( $this->{$class_property} )) {
			return $this->{$class_property};
		}

		$v = $this->get( $data_index );
		$arr = $v ? gp_db_decode( $v, 'json' ) : array();
		$arr = $arr && is_array( $arr ) ? $arr : array();
		$this->{$class_property} = $arr;
		return $this->{$class_property};
	}

	/**
	 * @return mixed
	 */
	public function get_mutable_array(){
		return $this->get_arr_from_serialized_data_index( 'mutable_array', 'process_mutable_array' );
	}

    /**
     * Update json encoded database column, by passing
     * in a function which accepts an array (the json decoded
     * database value), and returns an array.
     *
     * @param callable $updater
     * @return bool
     * @throws Exception
     */
	public function update_process_mutable_array( callable $updater ) {

	    $arr = $this->get_mutable_array();

	    $arr = $updater( $arr );

        return $this->update_database_and_re_sync( [
            'process_mutable_array' => gp_db_encode( $arr, 'json' ),
        ] );
    }

    /**
     * @param $data
     * @param $key
     * @throws Exception
     */
    public function append_to_process_mutable_array( $data, $key = null ) {
	    $this->update_process_mutable_array( function( $arr ) use( $data, $key ) {

	        if ( $key !== null ) {
                $arr[$key] = $data;
            } else {
                $arr[] = $data;
            }

	        return $arr;
        });
    }

	/**
	 * @return mixed
	 */
	public function get_feed_submission_result_1(){
		return $this->get_arr_from_serialized_data_index( 'feed_submission_result_1', 'feed_submission_result_1' );
	}

	/**
	 * @return mixed
	 */
	public function get_feed_submission_result_2(){
		return $this->get_arr_from_serialized_data_index( 'feed_submission_result_2', 'feed_submission_result_2' );
	}
}