<?php

/**
 * Can help be used to help with form submissions or file imports among other things.
 *
 * Class GP_Error_Handler
 */
Class GP_Error_Handler{

	/**
	 * Defaults to false even if no errors.
	 *
	 * Success means $this->success == true, and $this->errors is empty
	 *
	 * @var
	 */
	protected $success;

	/**
	 * An array of error msgs
	 *
	 * @var
	 */
	protected $errors;

	/**
	 * An array of success messages, probably just 1.
	 *
	 * Only check this if $this->has_errors() returns false
	 *
	 * @var
	 */
	protected $success_messages;

	/**
	 * An array of messages (likely errors) that you may want to store in a database,
	 * but should not get shown to front-end users.
	 *
	 * @var
	 */
	protected $hidden_messages;

	/**
	 * An array of response data from the operation of the class
	 *
	 * ie. if handling a form submission, you could put the ID of a newly created post,
	 * or if handling a payment, the transaction ID and auth code.
	 *
	 * @var
	 */
	protected $response_data;


	// html opening and closing tags for parsing an array into an html string
	protected $error_before_html;
	protected $error_after_html;
	protected $success_before_html;
	protected $success_after_html;
	protected $hidden_before_html;
	protected $hidden_after_html;
	protected $before_each;
	protected $after_each;

	/**
	 * Here's how the logic works.
	 *
	 * success: defaults to false.
	 * errors: array of errors.
	 * success_messages: array of success messages.
	 * add_error(): adds to errors array AND sets success to false.
	 * add_success_message(): adds to success messages array, but does NOT change the value of success.
	 * has_errors(): true if errors array is not empty (has nothing to do with success).
	 * get_success(): true if errors is empty AND success is true, but has nothing to do with the success messages array.
	 *
	 * In the end: run all your stuff...
	 * if get_success() returns true, check get_success_messages(), otherwise, check get_errors().
	 *
	 * While running your stuff... do add_error() at any time. Then whenever necessary (ie. immediately after calling a method)...
	 * if has_errors() returns true, stop everything / return / exit.
	 *
	 * GP_Error_Handler constructor.
	 */
	public function __construct(){

		$this->success = false;
		$this->errors = array();
		$this->success_messages = array();
		$this->hidden_messages = array();

		$this->error_before_html = ''; // ie. '<div class="ajax-response error">';
		$this->error_after_html = '';
		$this->success_before_html = '';
		$this->success_after_html = '';
		$this->hidden_before_html = '';
		$this->hidden_after_html = '';
		$this->before_each = '<p>';
		$this->after_each = '</p>';
	}

	/**
	 * This is not the same as has_errors(),
	 *
	 * This should always be the method to check when trying to get the overall status of the entire transaction
	 *
	 * For this to return true, we need errors to be empty, and $this->success = true.
	 */
	public function get_success(){
		return $this->has_errors() == false && $this->success == true;
	}

	/**
	 * Best to use this always when adding an error
	 */
	public function add_error( $msg ){
		// we change errors to false here, but upon checking has_errors() don't return true just because success is false.
		$this->success = false;
		$this->errors[] = $msg;
	}

	/**
	 * Remember that the overall success of the form submission does not depend
	 * on the presence of success messages.  Success must be manually set to true.
	 * The reaosn is that we may set a default success message ahead of time,
	 * before determining if the entire transaction was truly successful.
	 *
	 * @param $msg
	 */
	public function add_success_message( $msg ){
		if ( $msg ) {
			$this->success_messages[] = $msg;
		}
	}

	/**
	 * Don't assume the overall transaction was successful just because this returns a non-empty array.
	 *
	 * Instead, use $this->get_success(), and if that returns true, then check this.
	 * @return array
	 */
	public function get_success_messages(){
		$msgs = is_array( $this->success_messages ) ? $this->success_messages : array_filter( array( $this->success_messages ) );
		return $msgs;
	}

	/**
	 * @return array
	 */
	public function get_errors(){
		$errors = is_array( $this->errors ) ? $this->errors : array_filter( array( $this->errors ) );
		return $errors;
	}

	/**
	 * Refers to the errors array not being empty.
	 *
	 * We do not check $this->success. Instead, use $this->get_success().
	 *
	 * @return bool
	 */
	public function has_errors(){
		return count( $this->get_errors() ) > 0;
	}

	/**
	 * If the hidden message is an error, you should also run add_error() or make sure success is false
	 *
	 * @param $msg
	 */
	public function add_hidden_message( $msg ) {
		if ( $msg ) {
			$this->hidden_messages[] = $msg;
		}
	}

	/**
	 *
	 */
	public function get_hidden_messages(){
		$msgs = is_array( $this->hidden_messages ) ? $this->hidden_messages : array_filter( array( $this->hidden_messages ) );
		return $msgs;
	}

	/**
	 *
	 */
	public function get_success_string(){
		return $this->parse_array( $this->get_success_messages(), $this->before_each, $this->after_each, $this->success_before_html, $this->success_after_html );
	}

	/**
	 *
	 */
	public function get_errors_string(){
		return $this->parse_array( $this->get_errors(), $this->before_each, $this->after_each, $this->error_before_html, $this->error_after_html );
	}

	/**
	 * @return string
	 */
	public function get_hidden_string(){
		return $this->parse_array( $this->get_hidden_messages(), $this->before_each, $this->after_each, $this->hidden_before_html, $this->hidden_after_html );
	}

	/**
	 * Parse an array of errors/msgs into an html string
	 *
	 * @param        $arr
	 * @param string $before_each
	 * @param string $after_each
	 * @param string $before_html
	 * @param string $after_html
	 *
	 * @return string
	 */
	public function parse_array( $arr, $before_each = '<p>', $after_each = '</p>', $before_html = '', $after_html = '' ) {
		$html = '';

		$html .= $before_html;

		if ( $arr && is_array( $arr ) ) {
			foreach ( $arr as $msg ) {
				$html .= $before_each;
				$html .= $msg;
				$html .= $after_each;
			}
		}

		$html .= $after_html;

		return $html;
	}

	/**
	 * ie. in an ajax call: $handler->run(); echo json_encode( $handler->get_ajax_response() ); exit;
	 *
	 * @return array
	 */
	public function get_ajax_response(){
		$response = array();
		if ( $this->get_success() == true ) {
			$response['success'] = true;
			$response['output'] = $this->get_success_string();
		} else {
			$response['success'] = false;
			$response['output'] = $this->get_errors_string();
		}
		return $response;
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function add_to_response_data( $key, $value ) {
		$this->response_data[$key] = $value;
	}

	/**
	 * @return array
	 */
	public function get_response_data(){
		$this->response_data = is_array( $this->response_data ) ? $this->response_data : array_filter( array( $this->response_data ) );
		return $this->response_data;
	}

	/**
	 * An indexed array of all messages/data stored
	 */
	public function get_overview(){

		// not including the ajax response here as it is redundant data
		$arr = array(
			'success' => $this->get_success(),
			'errors' => $this->get_errors(),
			'success_messages' => $this->get_success_messages(),
			'hidden_messages' => $this->get_hidden_messages(),
			'response_data' => $this->get_response_data(),
		);

		return $arr;
	}
}
