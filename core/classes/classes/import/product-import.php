<?php

/**
 * Class Product_Import
 */
Class Product_Import extends GP_Read_Csv {

	public static $default_process_count = 200;

    /**
     * @var - "CA" | "US" | null
     */
	public $locale;

	/**
	 * database table name
	 *
	 * @var
	 */
	public $table;

	/**
	 * 'update_insert', 'update_only', or 'insert_only'
	 *
	 * @var bool|mixed|string
	 */
	protected $method;

	/**
	 * 'init' or 'continue'.
	 *
	 * not to be confused with $this->method... sorry for the really generic names.
	 *
	 * When we 'init' a new import, we'll create an array in $_SESSION and store some data there.
	 *
	 * When we 'continue' an import, we'll look for data in $_SESSION, and continue where we left
	 * off from last time.
	 *
	 * This is a work-around for server timeout issues.
	 *
	 * @var
	 */
	public $action;

	/**
	 * To be generated if $this->action === 'init', otherwise
	 * passed in via $_POST['session_key']
	 *
	 * @var
	 */
	public $session_key;

	/**
	 * When $this->action === 'continue', we should have:
	 *
	 * $_POST['session_auth'] should be equal to $_SESSION[$this->session_key]['auth']
	 *
	 * Even though we have admin logged in checks, I want to properly authenticate because
	 * our nonce system may end up causing issues due to time limits. So.. each time
	 * ajax gets a response with instructions to continue, it will have to pass in a
	 * fairly simple randomly generated session authorization key.
	 *
	 * @var
	 */
	public $session_auth;

	/**
	 * If continue is true, we need to pass this value back in the response,
	 * and then get it again the next time we try to process the same file again.
	 *
	 * @var
	 */
	public $next_session_auth;

	/**
	 * True if we didn't reach the end of the CSV when processing rows this time.
	 *
	 * @var
	 */
	public $continue;

	/**
	 * How many items to process each time we run the script. See $this->action.
	 *
	 * @var
	 */
	public $process_count;

	/**
	 * file name of CSV file which should be uploaded to the server
	 * before running the import.
	 *
	 * @var bool|mixed
	 */
	protected $file_name;

	/**
	 * ADMIN_UPLOAD_DIR . '/' . $this->file_name;
	 *
	 * @var string
	 */
	protected $file_path;

	/**
	 * All part numbers inserted (or updated?)
	 *
	 * This is probably NOT IN USE
	 *
	 * @var
	 */
	protected $part_numbers = array();

	/**
	 * build an array as we process rows and track any errors or warnings.
	 *
	 * @var array
	 */
	protected $track_results = array();

	/**
	 * $_POST
	 *
	 * @var
	 */
	protected $input;

	/**
	 * A map of the the lower case string name we use in our import,
	 * mapped to what its called in the CSV.
	 *
	 * For example, the CSV header might have CAD_PRICE, but we use price_ca
	 * which is both used in the context of this import, and happens to be
	 * the same as the database column name.
	 *
	 * @var
	 */
	public $col_index_to_name = [];

	/**
	 * The required columns, but.. written as the array keys
	 * of $col_index_to_name.
	 *
	 * Probably, this is just array_keys( $this->col_index_to_name ).
	 *
	 * Ie. require all column (headings), otherwise, don't import any products.
	 *
	 * @var
	 */
	public $required_cols;

	/**
	 * The entire CSV in a php array
	 *
	 * @var array
	 */
	protected $csv = array();

	/**
	 * Row errors mean we abort the import (I think)
	 *
	 * @var int
	 */
	protected $row_error_count = 0;

	/**
	 * A row notice means the product was not inserted, but we'll continue processing the rest.
	 *
	 * @var int
	 */
	protected $row_notice_count = 0;

	/**
	 * products updated count.
	 *
	 * @var int
	 */
	protected $update_count = 0;

	/**
	 * products inserted count.
	 *
	 * @var int
	 */
	protected $insert_count = 0;

	/**
	 * we'll store the import date with each product, for all products
	 * updated or inserted in this import. This will help an admin
	 * delete products from old files after imports are run.
	 *
	 * @var string
	 */
	public $import_date;

	/**
	 * The import name is just an optional identifier for the import that an
	 * admin can enter in. It's not really necessary because all products
	 * updated/inserted are labelled with the date. Like the date....
	 * the import name gets applied to anything updated OR inserted.
	 *
	 * @var null|string
	 */
	public $import_name; // could be "DAI alloys spring 2018"

	/**
	 * ie, 1, 51, 101, etc.
	 *
	 * @var
	 */
	public $start_row;

	/**
	 * ie. 50, 100, 150, etc.
	 *
	 * @var
	 */
	public $end_row;

	/**
	 * count the number of rows skipped because why not
	 *
	 * @var
	 */
	public $rows_skipped = 0;

	/**
	 * the total number of rows that we process in this script (not session). processing
	 * doesn't mean inserting or updating, it just means we
	 * called the handle_row() function on a row.
	 *
	 * @var int
	 */
	public $rows_processed = 0;

	/**
	 * we'll need this in combination with $this->processed_the_last_row
	 * to know if we should continue processing via a form re-submission.
	 *
	 * @var bool
	 */
	public $processed_some_rows = false;

	/**
	 * @var bool
	 */
	public $processed_the_last_row = false;

	/**
	 * closely related to the number of rows in the CSV file,
	 * but first, we filter out the header row and remove any
	 * completely empty or possibly invalid rows, then count
	 * the result.
	 *
	 * Note that some CSVs have their header rows repeated.
	 * I believe that in this case, those will still count as "products"
	 * but when we get to importing them, we'll run into some sort of error,
	 * ie, " 'price' is not a valid price amount ".
	 *
	 * Also this is not defined super early in the script, so don't use it too early.
	 *
	 * @var
	 */
	public $products_in_csv;

	/**
	 * this is a hacky solution to passing in the part number to our
	 * add_row_message() method. we use the method a ton of times, and
	 * now I realize I want to put the part number in the table but I don't
	 * want to change all 20-30 usages to include a different parameter, so
	 * we'll store this globally and reference it inside the method.
	 *
	 * @var
	 */
	public $current_row;

	/**
	 * Product_Import constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {

		start_time_tracking( 'product_import' );

		parent::__construct( $args );

		$this->input = $_POST;

		switch ( @$this->input['locale'] ){
            case 'ca':
                $this->locale = "CA";
                break;
            case 'us':
                $this->locale = "US";
                break;
            default:
                $this->locale = null;
        }

		$this->session_init();

		$this->method = in_array( $this->method, array_keys( self::get_method_options() ) ) ? $this->method : '';
		if ( ! $this->method ) {
			$this->add_error( 'Select a valid import method.' );
		}
	}

	/**
	 * to be called once at the beginning of each import... probably in the constructor.
	 */
	private function session_init() {

		// 'init' or 'continue'
		$this->action = gp_if_set( $this->input, 'import_action' );

		// only expected when $this->action === 'continue'
		$this->session_key  = gp_if_set( $this->input, 'session_key' );
		$this->session_auth = gp_if_set( $this->input, 'session_auth' );

		switch ( $this->action ) {
			case 'init':

				$this->method      = get_user_input_singular_value( $this->input, 'method' );
				$this->import_date = get_date_formatted_for_database();
				$this->import_name = get_user_input_singular_value( $this->input, 'import_name' );
				$this->file_name   = gp_if_set( $this->input, 'file_name' );

				$this->process_count = (int) get_user_input_singular_value( $_POST, 'process_count', static::$default_process_count );
				$this->session_key   = self::generate_session_key();

				$this->start_row = 1;
				// technically, this is start_row + process_count - 1... but.. start row is 1 so it cancels out
				// in the first iteration, we should do 1-50, then 51-100 etc. when process count is 50
				// note that in the CSV the header row is 1, but.. we're going to use the product count as our number not the CSV row.
				$this->end_row = $this->process_count;

				// when the import is finished successfully, we'll store some information here for next time.
				$_SESSION[ $this->session_key ] = array();

				// these we'll put into session once, and ideally there's no reason to touch them afterwards.
				$_SESSION[ $this->session_key ][ 'process_count' ] = $this->process_count;
				$_SESSION[ $this->session_key ][ 'file_name' ]     = $this->file_name;
				$_SESSION[ $this->session_key ][ 'method' ]        = $this->method;
				$_SESSION[ $this->session_key ][ 'import_date' ]   = $this->import_date;
				$_SESSION[ $this->session_key ][ 'import_name' ]   = $this->import_name;

				// we'll update this later on before sending the ajax response ..
				$_SESSION[ $this->session_key ][ 'last_processed_row' ] = 0;
				$_SESSION[ $this->session_key ][ 'total_processed' ]    = 0;
				$_SESSION[ $this->session_key ][ 'total_updated' ]      = 0;
				$_SESSION[ $this->session_key ][ 'total_inserted' ]     = 0;
				$_SESSION[ $this->session_key ][ 'total_errors' ]       = 0;
				$_SESSION[ $this->session_key ][ 'total_notices' ]      = 0;

				// store some of the affected tables, afterwards we may print some summary data.
				$_SESSION[ $this->session_key ][ 'tire_brand_ids' ] = array();
				$_SESSION[ $this->session_key ][ 'tire_model_ids' ] = array();
				$_SESSION[ $this->session_key ][ 'rim_brand_ids' ]  = array();
				$_SESSION[ $this->session_key ][ 'rim_model_ids' ]  = array();
				$_SESSION[ $this->session_key ][ 'rim_finish_ids' ] = array();

				break;

			case 'continue':

				$session_key = gp_if_set( $this->input, 'session_key' );

				// i guess allow this to be an empty array even though i don't expect it to be empty.
				if ( ! isset( $_SESSION[ $session_key ] ) || ! is_array( $_SESSION[ $session_key ] ) ) {
					$this->add_error( 'Session information not found, so cannot continue from where the import left off last time.' );

					return;
				}

				$session_data = $_SESSION[ $session_key ];

				$auth_1 = gp_if_set( $_POST, 'session_auth' );
				$auth_2 = gp_if_set( $session_data, 'auth' );

				if ( ! $auth_1 || ( $auth_1 != $auth_2 ) ) {
					$this->add_error( 'Authorization error, cannot continue where we left off from last import' );

					return;
				}

				$this->process_count = (int) gp_if_set( $session_data, 'process_count' );
				$last_processed_row  = (int) gp_if_set( $session_data, 'last_processed_row' );

				$this->start_row = $last_processed_row + 1;
				// end and start rows are inclusive, so if we start at 51, add 49 to get to 100
				$this->end_row = $this->start_row + $this->process_count - 1;

				$this->file_name   = gp_if_set( $session_data, 'file_name' );
				$this->method      = gp_if_set( $session_data, 'method' );
				$this->import_date = gp_if_set( $session_data, 'import_date' );
				$this->import_name = gp_if_set( $session_data, 'import_name' );

				break;
			default:
				$this->add_error( 'Import action should be "init" or "continue" (dev error)' );
		}

		// define this last
		// some actions might not require the file to exist so we don't validate this right away.
		$this->file_path = ADMIN_UPLOAD_DIR . '/' . $this->file_name;
	}

	/**
	 *
	 */
	public static function generate_session_key() {

		$pre = '__product_import_';
		$c   = 1;

		while ( isset( $_SESSION[ $pre . $c ] ) ) {
			$c ++;
		}

		$key = $pre . $c;

		return $key;
	}

	/**
	 * Do something in between run() and get_response_array()... why ? because
	 * get_response_array() might not always be called after calling run()...
	 * but we should always call after_run() after calling run().
	 */
	public function after_run() {

	    // in case of newly added brands
        DB_Page::auto_insert_dynamic_pages();

		$_SESSION[ $this->session_key ][ 'total_processed' ] += $this->rows_processed;
		$_SESSION[ $this->session_key ][ 'total_updated' ]   += $this->update_count;
		$_SESSION[ $this->session_key ][ 'total_inserted' ]  += $this->insert_count;
		$_SESSION[ $this->session_key ][ 'total_errors' ]    += $this->row_error_count;
		$_SESSION[ $this->session_key ][ 'total_notices' ]   += $this->row_notice_count;

		// this needs to be included in the response if we're going to continue
		$this->next_session_auth = uniqid( $this->rows_skipped );

		// put anything random here....
		// note that we ALWAYS update this..
		// and we may return it in javascript so that it updates in the form.
		// this means after something unexpected happening, if the user just hits the submit button
		// again, its going to throw an error without processing files, forcing them to re-load the page..
		// which is a good thing.
		$_SESSION[ $this->session_key ][ 'auth' ] = $this->next_session_auth;

		// also will include this in the response
		$this->continue = $this->processed_some_rows && ! $this->processed_the_last_row;

		if ( $this->processed_the_last_row && ! $this->has_errors() ) {

			gp_cache_empty();
			$this->add_success_message( 'Import Complete. Database cache emptied.' );

			$total_processed = $_SESSION[ $this->session_key ][ 'total_processed' ];
			$total_updated   = $_SESSION[ $this->session_key ][ 'total_updated' ];
			$total_inserted  = $_SESSION[ $this->session_key ][ 'total_inserted' ];
			$total_errors    = $_SESSION[ $this->session_key ][ 'total_errors' ];
			$total_notices   = $_SESSION[ $this->session_key ][ 'total_notices' ];

			$this->add_success_message( 'Processed: ' . $total_processed );
			$this->add_success_message( 'Inserted: ' . $total_inserted );
			$this->add_success_message( 'Updated: ' . $total_updated );
			$this->add_success_message( 'Errors: ' . $total_errors );
			$this->add_success_message( 'Notices: ' . $total_notices );

			$delete_link = cw_add_query_arg( array(
				'import_date' . GET_VAR_NOT_EQUAL_TO_APPEND => $this->import_date,
			), get_admin_archive_link( $this->table ) );

			$this->add_success_message( '<a target="_blank" href="' . $delete_link . '">Click here</a> to view and delete all products not updated or inserted in this import.' );
		}
	}

	/**
	 * Execute an import based on user input, which should contain a hidden field for file name
	 */
	public function run() {

		// errors can definitely occur before we get to here
		if ( $this->has_errors() ) {
			return;
		}

		if ( $this->method === 'delete_all' && ! IN_PRODUCTION ) {
			$this->add_error( 'This feature is turned off.' );
			//			if ( $this->delete_all() ) {
			//				$this->success = true;
			//				$this->add_success_message( 'all rows deleted.' );
			//
			//				return;
			//			} else {
			//				$this->add_error( 'Error in deleting all rows' );
			//
			//				return;
			//			}
		}

		if ( ! file_exists( $this->file_path ) ) {
			$this->add_error( 'Could not find file: ' . $this->file_name );

			return;
		}

		// a little concerned about a possible php variable size limit exception here
		try {
			$this->csv = $this->csv_to_array( $this->file_path );
		} catch ( Exception $e ) {
			$this->add_error( 'Exception thrown: ' . $e->getMessage() );
		}

		if ( $this->has_errors() ) {
			return;
		}

		$this->validate_and_filter_csv();

		// catch required columns errors etc.
		if ( $this->has_errors() ) {
			return;
		}

		// we need to copy this to a new variable, so we can remove the first element, so
		// that functions that deal with counting the $this->csv array return consistent results.
		$_csv = $this->csv;

		// remove the header row, and then do nothing with it
		$first_row = array_shift( $_csv );

		// do an initial loop to collect all part numbers, so later we can compare all items
		// in the database with the part numbers in the CSV
		//		if ( $_csv && is_array( $_csv ) ) {
		//			foreach ( $_csv as $row_index => $row_data ) {
		//				$part_number          = gp_if_set( $row_data, 'part_number' );
		//				$this->part_numbers[] = $part_number;
		//			}
		//		}

		// currently redundant but that's ok - this needs to be checked immediately before looping through the data
		if ( $this->has_errors() ) {
			return;
		}

		// start at zero, we'll increment right away in the loop so that in effect, we start at 1.
		// the "row_number" is not the CSV row number, its the count of products processed.
		// ie. the first row with products in it will be row number 1.
		// to be clear, i believe that the CSV number will be $row_number + 1, this is if the CSV
		// count starts at 1, and row 1 is the header row.
		$row_number = 0;

		// *** LOOP ***
		// note on $this->csv.
		// it *should* be only the non-empty and non-first row of the csv file provided.
		if ( $_csv && is_array( $_csv ) ) {

			$count = count( $_csv );

			// we can now define this variable after we remove the header row, we'll use this to print out useful response messages.
			$this->products_in_csv = $count;

			// we need this to know when to stop processing the file (with repeated ajax requests)
			$this->processed_the_last_row = false;

			foreach ( $_csv as $row_index => $row_data ) {

				// this catches errors from the last time we can $this->handle_row()
				// note that errors are not common here.. mostly handle_row() may add notices
				if ( $this->has_errors() ) {
					return;
				}

				// the first body row is 1.
				$row_number ++;

				if ( $row_number < $this->start_row ) {
					$this->rows_skipped ++;
					continue;
				}

				if ( $row_number > $this->end_row ) {
					$this->rows_skipped ++;
					continue;
				}

				// processed_some_rows is now redundant, but we still use it instead of checking rows_processed > 0
				$this->processed_some_rows = true;
				$this->rows_processed ++;

				// when we re-submit, we'll $this->start_row will become this + 1.
				$_SESSION[ $this->session_key ][ 'last_processed_row' ] = $row_number;

				// once this occurs, we'll no longer continue;
				if ( $row_index == ( $count - 1 ) ) {
					$this->processed_the_last_row = true;
				}

				// $row_number should correspond to CSV row number
				$this->handle_row( $row_data, $row_number );
			}
		} else {
			$this->add_error( 'No rows found other than possibly the first row.' );
		}

		if ( $this->has_errors() ) {
			return;
		}
	}

	/**
	 * override in child class.
	 *
	 * @param $row_data
	 * @param $row_id
	 */
	public function handle_row( $row_data, $row_id ) {
		$this->add_row_message( $row_id, 'This method needs to be overriden in a child class', true );
	}

	/**
	 *
	 */
	protected function get_track_results_html() {

		$op = '';

		$col_map = array(
			'row' => 'Row - Part Number',
			'errors' => 'Errors',
			'notices' => 'Notices',
		);

		// assemble table data which is pretty similar to $this->track_results but slightly different format.
		$table_data = array();
		if ( $this->track_results && is_array( $this->track_results ) ) {
			foreach ( $this->track_results as $row_id => $row_messages ) {
				if ( $row_messages && is_array( $row_messages ) ) {

					$array_of_strings = array();

					// key here might be 'errors' or 'notices' and maybe 'row', $value is probably an array however
					foreach ( $row_messages as $key => $value ) {

						$string = is_array( $value ) ? implode( ', <br>', $value ) : $value;
						// just in case
						$string                   = gp_make_singular( $string );
						$array_of_strings[ $key ] = $string;

						// add columns in case any are found other than the expected ones: row, errors, notices
						// this basically does nothing.
						if ( ! isset( $col_map[ $key ] ) ) {
							$col_map[ $key ] = $key;
						}
					}

					$table_data[] = $array_of_strings;
					// ie. $table_data[] = array( 'row' => 1, 'error' => '', 'notices' => 'notice 1, notice 2, notice 3' )
				}
			}
		}

		// keep track of time and memory usage
		// $mem  = memory_get_usage();
		$mem  = memory_get_peak_usage();
		$kb   = number_format( round( $mem / ( 1024 ), 0 ), 0, '.', ',' );
		$_mem = $kb . ' kb';

		$time = end_time_tracking( 'product_import' );
		$time = round( $time, 5 ) . ' seconds';

		$rows = 'rows ' . $this->get_counts_text( false );
		$op   .= wrap_tag( implode_comma( [ $rows, $time, $_mem ] ) . '.', 'p' );

		$errors_notices = $this->row_error_count . ' Errors, ' . $this->row_notice_count . ' Notices';
		$op             .= wrap_tag( $errors_notices, 'p' );

		if ( $this->track_results ) {
			$op .= render_html_table_admin( $col_map, $table_data, array(
				// we include html links in some items..
				// if/when we print info from CSV to html, its generally sanitized first anyways.
				'sanitize' => false,
			) );
		}

		if ( $this->processed_the_last_row ) {
			$op .= '<p><strong>Import Complete</strong></p>';
		}

		return $op;
	}

	/**
	 * Shows on the page before the form is submitted
	 */
	public function get_pre_form_message() {

		$op = '';

		if ( $this->required_cols ) {
			$op .= '<p>The following column names are required. These are case insensitive, and order does not matter, but all columns must be present.</p>';

			$names = array();
			foreach ( $this->required_cols as $rq ) {
				$names[] = gp_if_set( $this->col_index_to_name, $rq, '??' );
			}

			$op .= '<p>' . implode_comma( $names ) . '.</p>';
		}

		return $op;
	}

	/**
	 * Validates that the required header row columns are found, and then
	 * changes the csv indexes from numeric to string
	 */
	public function validate_and_filter_csv() {

		$col_index_to_name = $this->col_index_to_name;

		// ie. 0 => part_number, 11 => bolt_pattern
		$csv_index_to_code_index = array();
		$header_col              = gp_if_set( $this->csv, 0, array() );

		foreach ( $col_index_to_name as $code_index => $code_title ) {

			$csv_found_index = null;

			if ( $header_col && is_array( $header_col ) ) {
				foreach ( $header_col as $csv_index => $csv_title ) {
					if ( $code_title && $csv_title && self::compare_strings( $csv_title, $code_title ) ) {
						$csv_found_index = $csv_index;
					}
				}
			}

			if ( $csv_found_index === null ) {
				$this->add_error( 'Could not find a required column in the CSV: ' . $code_title );
				continue;
			}

			$csv_index_to_code_index[ $csv_found_index ] = $code_index;
		}

		if ( $this->has_errors() ) {
			return;
		}


		$new_csv = array();
		if ( $this->csv && is_array( $this->csv ) ) {
			foreach ( $this->csv as $cc => $row ) {

				if ( $row && is_array( $row ) ) {
					foreach ( $row as $int => $cell ) {

						if ( $this->has_errors() ) {
							return;
						}

						$new_index = gp_if_set( $csv_index_to_code_index, $int, null );

						if ( $new_index === null ) {
							$this->add_hidden_message( 'A column was found in the CSV (' . $int . ') which is not one of the required columns. Aborting import to avoid loss of data.' );
						} else {
							$new_csv[ $cc ][ $new_index ] = $cell;
						}
					}
				}
			}
		}

		if ( count( $this->csv ) !== count( $new_csv ) ) {
			$this->add_error( 'Error in parsing CSV, which might be due to column names' );
		}

		$this->csv = $new_csv;
	}


	/**
	 * @param $row_id
	 * @param $msg
	 */
	protected function add_row_message( $row_id, $msg, $abort = false ) {

		$msg = gp_make_singular( $msg );

		if ( ! isset( $this->track_results[ $row_id ][ 'row' ] ) ) {
			$this->track_results[ $row_id ][ 'row' ] = $this->get_row_text( $row_id );
		}

		// by adding an error the next iteration of the loop should stop
		if ( $abort ) {
			$this->add_error( 'Aborting due to error in row ' . $this->get_row_text( $row_id ) );
			$this->row_error_count ++;
			$this->track_results[ $row_id ][ 'errors' ][] = $msg;

			return;
		}

		$this->row_notice_count ++;
		$this->track_results[ $row_id ][ 'notices' ][] = $msg;
	}

	/**
	 * Identify row to front-end user.
	 *
	 * row ID is passed in but current row part number is stored globally,
	 * therefore if the row IS does not correspond to the globally stored
	 * current row data, use 2nd parameter false.
	 *
	 * @param      $row_id
	 * @param bool $add_current_row_part_number
	 *
	 * @return string
	 */
	protected function get_row_text( $row_id, $add_current_row_part_number = true ) {
		$append = $add_current_row_part_number ? ' - ' . self::sanitize_part_number( gp_if_set( $this->current_row, 'part_number' ) ) : '';

		return '(' . $row_id . '' . $append . ')';
	}

	/**
	 * @param $str1
	 * @param $str2
	 */
	public static function compare_strings( $str1, $str2 ) {
		if ( self::simplify_string( $str1 ) === self::simplify_string( $str2 ) ) {
			return true;
		}

		return false;
	}

	/**
	 * use this to compare 2 strings to see if they are
	 * pretty much equal..
	 *
	 * @param $str
	 *
	 * @return mixed|string
	 */
	public static function simplify_string( $str ) {
		$str = trim( $str );
		$str = strtolower( $str );
		// replace white space characters with underscores
		$str = preg_replace( '/\s+/', '_', $str );
		$str = str_replace( '-', '_', $str );

		return $str;
	}

	/**
	 * @return array
	 */
	public static function get_method_options() {
		return array(
			'update_insert' => 'Update existing products and add new products',
			'update_only' => 'Update existing products only',
			'insert_only' => 'Insert new products only',
			// dev only
			// 'delete_all' => 'Delete All, ARE YOU SURE????',
		);
	}

	/**
	 * If everything goes smoothly, this will be equal to the maximum of $this->end_row and
	 * $this->products_in_csv.
	 *
	 * However, the session value is probably the most reliable.
	 *
	 * Also, the session value can be called at any time, whereas $this->products_in_csv is not setup
	 * right away.
	 */
	public function get_last_processed_row() {
		$ret = gp_if_set( $_SESSION[ $this->session_key ], 'last_processed_row' );

		return $ret;
	}

	/**
	 * ie. X to Y of Z
	 *
	 * @return string
	 */
	public function get_counts_text( $include_of = true ) {

		$up_to = $this->get_last_processed_row();

		$op = '';

		if ( $include_of ) {
			$op .= $this->start_row . ' - ' . $up_to . ' of ' . $this->products_in_csv;
		} else {
			$op .= $this->start_row . ' - ' . $up_to;
		}

		return $op;
	}

	/**
	 *
	 */
	public function get_status_html() {

		$op = '';
		$op .= '<p>Completed ' . $this->get_counts_text() . '</p>';

		if ( $this->continue ) {
			$op .= '<p>Continuing...</p>';
		} else if ( $this->processed_the_last_row ) {
			$op .= '<p>Finished all products.</p>';
		} else {
			// could mean we ran into an error...
		}

		$op .= '';

		return $op;
	}

	/**
	 * @return array
	 */
	public function get_response_array() {

		// use our own logic here..
		// $ret = $this->get_ajax_response();

		$ret = array();

		// update the status item..
		$ret[ 'status' ] = $this->get_status_html();

		// append updates in a list - regardless of error/continue/success
		if ( $this->processed_some_rows ) {
			$ret[ '_append' ] = $this->get_track_results_html();
		}

		// handles error message...
		if ( $this->has_errors() ) {

			$ret[ 'success' ] = false;
			$ret[ 'output' ]  = gp_parse_error_string( $this->get_errors() );

		} else if ( $this->continue ) {

			$ret[ 'continue' ] = true;

			// items to send to the next request via javascript -> form -> $_POST
			$ret[ 'persist' ] = array(
				'import_action' => 'continue',
				'session_key' => $this->session_key,
				'session_auth' => $this->next_session_auth
			);

			// we may end up needing this... javascript should already handle it.
			// $ret['new_nonce'] = '';
		} else {

			$ret[ 'success' ] = true;
			$ret[ 'output' ]  = gp_parse_error_string( $this->get_success_messages() );

			$ret[ 'persist' ] = array(
				'import_action' => 'init',
				'session_key' => '',
				'session_auth' => '',
			);
		}

		return $ret;
	}

	/**
	 * override in child class
	 *
	 * @return array
	 */
	public function get_all_products() {

		$db = get_database_instance();
		$q  = '';
		$q  .= 'SELECT part_number ';
		$q  .= 'FROM ' . $this->table . ' ';
		$q  .= 'GROUP BY part_number ';
		$q  .= ';';
		$r  = $db->pdo->query( $q )->fetchAll( PDO::FETCH_COLUMN );

		return $r;
	}

	/**
	 * I guess i'll leave this here.
	 *
	 * It offers a pretty inefficient way to delete all products that were not
	 * in this import.
	 */
	//	protected function get_delete_products_html() {
	//		return 'this is an old method and not in use any more';
	//		$op              = '';
	//		$db_products     = $this->get_all_products();
	//		$import_products = $this->part_numbers;
	//
	//		$to_delete = array_diff( $db_products, $import_products );
	//
	//		$tbl = new GP_Table();
	//		$tbl->add_css_class( 'gp-data-table' );
	//
	//		$tbl->set_columns( array(
	//			'part_number' => 'Part Number',
	//			'btn' => 'Delete',
	//		) );
	//
	//		if ( $to_delete ) {
	//			foreach ( $to_delete as $pn ) {
	//
	//				$btn = '';
	//				$btn .= '<input class="delete-rim-cb" type="checkbox" name="delete_rims[' . $pn . ']" value="1">';
	//
	//				$tbl->add_row( array(
	//					'part_number' => $pn,
	//					'btn' => $btn,
	//				), array(
	//					// class is needed to javascript knows how to remove them later on
	//					'class' => 'part-number-' . gp_make_letters_numbers_underscores( $pn ),
	//				) );
	//			}
	//		}
	//
	//		$bindings1   = array();
	//		$bindings1[] = array(
	//			'bind' => 'click',
	//			'action' => 'check_all',
	//			'closest' => 'form',
	//			'find' => '.delete-rim-cb',
	//		);
	//
	//		$bindings2   = array();
	//		$bindings2[] = array(
	//			'bind' => 'click',
	//			'action' => 'uncheck_all',
	//			'closest' => 'form',
	//			'find' => '.delete-rim-cb',
	//		);
	//
	//		$controls = '';
	//		$controls .= '<div class="form-controls">';
	//
	//		$controls .= '<div class="control-wrap type-check">';
	//		$controls .= '<button type="button" class="js-bind" data-bind="' . gp_json_encode( $bindings1, 'js' ) . '">Select All</button>';
	//		$controls .= '</div>';
	//
	//		$controls .= '<div class="control-wrap type-uncheck">';
	//		$controls .= '<button type="button" class="js-bind" data-bind="' . gp_json_encode( $bindings2, 'js' ) . '">De-Select All</button>';
	//		$controls .= '</div>';
	//
	//		$controls .= '</div>'; // form controls
	//
	//		$op .= '<div class="delete-products">';
	//		$op .= '<h2>Database products not in the CSV</h2>';
	//
	//		// form
	//		$op .= '<form id="products-delete" class="cw-ajax overlay-on-load" method="post" action="' . AJAX_URL . '">';
	//		$op .= get_nonce_input( 'cleanup_rims' );
	//		$op .= Ajax::get_action_field( 'cleanup_rims' );
	//
	//		// table
	//		$op .= $controls;
	//		$op .= $tbl->render();
	//		$op .= $controls;
	//		$op .= '<p><button type="submit">Delete Selected</button></p>';
	//		$op .= AJAX::get_response_div();
	//
	//		$op .= '<div class=""></div>';
	//
	//		$op .= '</form>';
	//		$op .= '</div>'; // delete-products
	//
	//		return $op;
	//	}

	/**
	 * A form to upload the file... should post to the same page, and if the file upload
	 * is successful, we'll display a different form that can parse that file (@see get_import_products_form_html())
	 */
	public function get_upload_file_form_html() {

		$id    = $this->table === DB_rims ? 'cw-import-rims-file-upload' : 'cw-import-tires-file-upload';
		$nonce = $this->table === DB_rims ? 'import_rims_file_upload' : 'import_tires_file_upload';

		$op = '';
		$op .= '<form id="' . $id . '" class="form-style-basic" method="post" action="" enctype="multipart/form-data">';
		$op .= '<input type="hidden" name="form_type" value="product_import_file">';
		$op .= get_nonce_input( $nonce );
		$op .= '<p><input type="file" name="file"></p>';
		$op .= '<p><button type="submit">Submit</button></p>';
		$op .= '</form>';

		return $op;
	}


	/**
	 * Make sure file exists first and file name is safe for printing in html.
	 */
	public function get_import_products_form_html( $file_name ) {

		$id           = $this->table === DB_rims ? 'cw-import-rims' : 'cw-import-tires';
		$ajax_action  = $this->table === DB_rims ? 'import_rims' : 'import_tires';
		$btn_text     = $this->table === DB_rims ? 'Import Rims' : 'Import Tires';
		$new_file_url = $this->table === DB_rims ? get_admin_page_url( 'import_rims' ) : get_admin_page_url( 'import_tires' );

		$cls = array( 'form-style-basic overlay-on-load js-bind' );

		$op = '';
		$op .= '<form id="' . $id . '" class="' . gp_parse_css_classes( $cls ) . '" method="post" action="' . AJAX_URL . '">';

		$op .= get_form_header( 'Ready to parse file' );

		$op .= '<div class="form-items">';

		$op .= '<p>' . $file_name . '</p>';

		$op .= '<p><button type="button"><a href="' . $new_file_url . '">Choose a different file</a></button></p>';

		$op .= '<p>Import names currently being used: ' . array_to_comma_sep_clean( get_all_column_values_from_table( $this->table, 'import_name' ) ) . '</p>';

		$op .= get_hidden_inputs_from_array( array(
			'file_name' => $file_name,
			'import_action' => 'init',
		), false );

		$op .= get_ajax_hidden_inputs( $ajax_action );

        $op .= get_form_select( array(
            'name' => 'locale',
            'label' => 'Price Region',
        ), [
            'items' => [
                'any' => 'US/CAD',
                'us' => 'US',
                'ca' => 'CAD'
            ],
            'current_value' => 'any',
        ]);

		$op .= get_form_input( array(
			'name' => 'import_name',
			'label' => 'Import Name (products inserted or updated will be labelled with this)',
		) );

		$op .= get_form_select( array(
			'name' => 'method',
			'label' => 'Import Type',
			'placeholder' => '...',
		), array(
			'items' => Product_Import_Tires::get_method_options(),
			'current_value' => 'update_insert',
		) );

		$op .= get_form_input( array(
			'name' => 'process_count',
			'label' => 'Process this many products at once. Choose a number of products so that each iteration is less than 30 seconds.',
			'value' => (int) get_user_input_singular_value( $_POST, 'process_count', static::$default_process_count ),
		) );

		$op .= get_form_submit( [ 'text' => $btn_text ] );

		$op .= '</div>';
		$op .= '</form>';

		// javascript will need these
		$op .= '<div class="product-import-response">';

		// a general status that is updated
		$op .= '<div class="product-import-status empty"></div>';

		// a list of updates, a new item gets appended with each response.
		$op .= '<div class="product-import-updates empty"></div>';

		$op .= '</div>';

		return $op;
	}

	/**
	 * we might need this in 2 places, one buried within handle_row() and another
	 * to keep track of all part numbers in CSV for deletion afterwards. We
	 * just need to make sure they use the same code.
	 *
	 * @param $v
	 */
	public static function sanitize_part_number( $v ) {
		$v = gp_test_input( $v );
		$v = ampersand_to_plus( $v );

		return $v;
	}

	/**
	 * @param $supplier_slug
	 * @param $row_id
	 */
	public function register_supplier( $supplier_slug, $supplier_name, $row_id ) {

		// ********** REGISTER SUPPLIER **************
		if ( $supplier_slug ) {
			$supplier_object = DB_Supplier::get_instance_via_slug( $supplier_slug );
			if ( $supplier_object ) {
				// there is very few suppliers. all data for suppliers can be handled manually, so there
				// is no reason to make any updates here.
			} else {

				// the whole purpose of suppliers is to have a 'supplier_email' field but this is
				// also managed manually, for now we just need to insert a supplier with the correct slug.
				// also, we don't store both the ID and slug for suppliers, unlike with models/brands/finishes
				$supplier_id = DB_Supplier::register( array(
					'supplier_slug' => $supplier_slug,
					'supplier_name' => $supplier_name,
				) );

				// I don't know what to write here. is this a fatal error? I would say no...
				// let them insert products without suppliers. products without suppliers simply won't get automated emails
				// sent when a user checks out, but no reason not to be able to sell the product altogether.
				if ( $supplier_id ) {
					// supplier slug works for this one single edit link
					$edit_supplier = get_admin_single_edit_link( DB_suppliers, $supplier_slug );
					$this->add_row_message( $row_id, 'A new ' . html_link_new_tab( $edit_supplier, 'supplier' ) . ' was registered ' . implode_comma( [ $supplier_slug ] ) . '. You should give them an email address.' );
				} else {
					$this->add_row_message( $row_id, 'Warning: could not find or insert a supplier. Automated emails will not be sent to the supplier upon checkout.', false );
				}
			}

		} else {
			$this->add_row_message( $row_id, 'Warning: supplier not provided. Automated emails will not be sent to the supplier upon checkout.', false );
		}
	}
}

class Product_Import_Helper{

    // top of file b4 printing anything. redirects or exits..
    // note: uploading file is first step, which this function does.
    // parsing the file is not done here.
    static function handle_file_upload_request( $is_rim ){

        $page = $is_rim ? 'import_rims' : 'import_tires';
        $key = $is_rim ? 'product_import_rim_file' : 'product_import_tire_file';

        if ( @$_POST[ 'form_type' ] === 'product_import_file' ) {

            $res = Admin_Functions::upload_tmp_csv();
            $error = @$res[ 'error' ];
            $file_upload_success = @$res[ 'file_upload_success' ];
            $file_name = @$res[ 'file_name' ];

            if ( $error || ! $file_name || ! $file_upload_success ) {
                echo "ERROR UPLOADING FILE. Please hit back and try again... \r\n \r\n";
                echo htmlspecialchars( $file_name ) . "\r\n";
                echo htmlspecialchars( $error ) . "\r\n";
                exit;
            } else {
                $_SESSION[ $key ] = $file_name;
                header( "Location: " . ADMIN_URL . "?page=$page&ready=1" );
                exit;
            }
        }
    }

    static function render_forms( Product_Import $import ){

        $page = $import instanceof Product_Import_Rims ? 'import_rims' : 'import_tires';
        $key = $import instanceof Product_Import_Rims ? 'product_import_rim_file' : 'product_import_tire_file';

        // file ready for parsing, submit request via ajax
        if ( intval( @$_GET[ 'ready' ] ) === 1 ) {

            $file_name = @$_SESSION[ $key ];
            unset( $_SESSION[ $key ] );

            // in case of page reload
            if ( ! $file_name ) {
                echo wrap_tag( "Please re-upload for your file..." );
                echo wrap_tag( html_link( ADMIN_URL . "?page=$page", 'Click here.' ) );
            } else {
                echo $import->get_import_products_form_html( $file_name );
            }

        } else {
            echo $import->get_upload_file_form_html();
        }
    }
}