<?php

require_once( CORE_DIR . '/classes/error-handler.php' );

/**
 * Accepts a single CSV file and does some operations (maybe turn into html table)
 *
 * Class GP_Read_Csv
 */
Class GP_Read_Csv extends GP_Error_Handler {

	protected $args;

	/**
	 * GP_Read_Csv constructor.
	 */
	public function __construct( $args ) {
		parent::__construct();
		$this->args = $args;
	}

	/**
	 * @param $full_path
	 */
	public function csv_to_array( $full_path ) {

		if ( ! file_exists( $full_path ) ) {
			$this->add_error( 'file not found: ' . $full_path );
		}

		$arr = array();

		$handle = fopen( $full_path, 'r' );
		if ( $handle ) {
			// remember first row is probably header row
			while ( ( $row = fgetcsv( $handle, 0, "," ) ) !== false ) {
				if ( ! $this->is_row_empty( $row ) ) {
					$arr[] = $row;
				}
			}
		}

		return $arr;
	}

	/**
	 * @param $row
	 */
	public function is_row_empty( $row ) {

		if ( is_array( $row ) && $row ) {
			foreach ( $row as $k=>$v ) {
				$v = trim( $v );
				if ( $v ) {
					return false;
				}
			}
			return true;
		}

		return $row ? false : true;
	}

}
