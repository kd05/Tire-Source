<?php

use phpseclib\Net\SFTP;

/**
 * Use SFTP or FTP to download a remote file and store it locally.
 *
 * Note: the name "Get_Csv" is misleading, this can get any file from FTP.
 *
 * Class FTP_Get_Csv
 */
Class FTP_Get_Csv {

    /**
     * I'm going to temporarily or permanently disable deleting local files,
     * due to occasional unknown errors with the files that the suppliers provide.
     *
     * When this is false, the files will remain in lib/logs/admin-uploads/inventory.
     *
     * @var bool
     */
    public static $deleting_local_file_enabled = false;

	/**
	 * if issues occur, try without a trailing slash and with ftp://
	 *
	 * @var
	 */
	public $host;

	/**
	 * 'sftp' or 'ftp'
	 *
	 * @var
	 */
	public $method;

	public $errors = array();
	public $events = array();

	public $username;
	public $password;
	public $port;

	/**
	 * set this much lower than the 504 gateway timeout that occurs after 30 seconds
     *
     * Update: maybe better to increase this for the sake of cron jobs.
	 *
	 * @var int
	 */
	public $timeout = 60;

	/**
	 * Note: this should actually be remote file path, which could be like:
	 *
	 * /dir/dir2/filename.csv
	 *
	 * In most cases, the file is in the root directory that the FTP/SFTP account has access to,
	 * so its just a filename.
	 *
	 * Also, I'm going to have to make this work with SFTP, but its possible that using a path
	 * rather than just a filename won't work for FTP.
	 *
	 * @var
	 */
	public $remote_file_name;

	// nothing by default. Can set this to avoid name conflicts or w/e.
	public $local_filename_prefix = '';

	public $ftp_connection;
	public $ftp_login_success;
	public $time_in_seconds;
	public $local_file_size;
	public $local_file_exists;

	/**
	 * created via remote file name passed in.
	 *
	 * Note: this exists because we first copy the file to our
	 * server via FTP, and then read from the file on our server.
	 *
	 * @var
	 */
	private $local_file_name;


	/**
	 * @var string
	 */
	private $local_file_dir;

	/**
	 * @var bool
	 */
	public $method_run_called_once = false;

	/**
	 * @var Callable|null
	 */
	public $sftp_after_login_callback;

	// helps us create unique IDs for the purposes of time tracking.
	public static $time_tracking_counter = 0;

	/**
	 * FTP_Get_Csv constructor.
	 */
	public function __construct() {
		$this->events[] = '__construct__';
		$this->local_file_dir = ADMIN_UPLOAD_DIR . '/inventory';

		if ( ! file_exists( $this->local_file_dir ) ) {
		    mkdir( $this->local_file_dir, 0755 );
        }
	}

    /**
     * After calling run, you might want to print/log this somewhere.
     */
	public function get_debug_array(){

	    return [
            'ftp_login_success' => (bool) $this->ftp_login_success,
            'time_in_seconds' => (int) $this->time_in_seconds,
            'timeout_limit' => (int) $this->timeout,
	        'remote_file_name' => $this->remote_file_name,
            'local_file_size' => (int) $this->local_file_size,
            'local_file_exists' => $this->local_file_exists,
            'local_path' => $this->get_local_full_path(),
        ];
    }

	/**
	 * @return string
	 */
	public function get_local_file_dir() {
		return $this->local_file_dir;
	}

	/**
	 * @return mixed
	 */
	public function get_local_file_name() {
		return $this->local_file_name;
	}

    /**
     * When calling file_get_contents, you'll want to url encode the file name, but
     * not the directories containing the file.
     *
     * @param bool $url_encode_file_name
     * @return string
     */
	public function get_local_full_path( $url_encode_file_name = false ) {

	    if ( $url_encode_file_name ) {
	        $fn = urlencode( $this->local_file_name );
        } else {
	        $fn = $this->local_file_name;
        }

		return $this->local_file_dir . '/' . $fn;
	}

    /**
     * Deletes the local copy of the file (maybe).
     *
     * The file won't be deleted when self::$deleting_local_file_enabled is false,
     * unless you specify $force = true. In general, don't force delete the file.
     *
     * @param bool $force
     * @return bool|null
     */
	public function unlink( $force = false ){

	    if ( ! $force ) {
	        if ( ! self::$deleting_local_file_enabled ) {
	            return false;
            }
        }

		$path = $this->get_local_full_path();

		if ( $path && file_exists( $path ) ) {
			return unlink( $path );
		}

		return null;
	}

	/**
	 * Get the target file and put in the local directory
	 */
	private function run_sftp() {

		$this->events[] = 'run_sftp';

		$this->port = $this->port ? $this->port : 22;

		$sftp                    = new SFTP( $this->host, $this->port, $this->timeout );
		$this->ftp_login_success = $sftp->login( $this->username, $this->password );

		if ( $this->ftp_login_success ) {

			// Note: this is not used at this time.
			// However, its useful for debugging at least so I will leave it here.
			if ( is_callable( $this->sftp_after_login_callback ) ){
				$func = $this->sftp_after_login_callback;
				$continue = $func( $this, $sftp );
				if ( ! $continue ) {
					return;
				}
			}

			// I think we need to run this if $this->remote_file_name is actually the path to the file.
			if ( strpos( $this->remote_file_name, '/' ) !== false ) {
				$sftp->enablePathCanonicalization();
			}

			$success = $sftp->get( $this->remote_file_name, $this->get_local_full_path() );

			if ( ! $success ) {
				$this->errors[] = 'sftp->get() returned false.';

				if ( $sftp->errors ) {
				    $this->errors = array_merge( $this->errors, $sftp->errors );
                }
			}
		}

		//		// ssh2_connect is undefined in some server environments
		//		$this->ftp_connection = ssh2_connect( $this->host, $this->port );
	}

	/**
	 * Get the target file and put in the local directory
	 *
	 * Maybe this will work with FTP other than plain FTP, but does not work with SFTP.
	 */
	private function run_plain_ftp() {

		$this->events[] = 'run_plain_ftp';

		$this->port = $this->port ? $this->port : 21;

		$this->ftp_connection = ftp_connect( $this->host, $this->port, $this->timeout );

		if ( $this->ftp_connection ) {

			$this->ftp_login_success = ftp_login( $this->ftp_connection, $this->username, $this->password );

			// make something ... passive ? getting a "ftp_fget(): Opening BINARY mode data connection" error.
			// see here: https://stackoverflow.com/questions/2496472/php-ftp-get-warning-ftp-get-opening-binary-mode-data-connection
			// call this AFTER FTP LOGIN, not after FTP connect which would seem really logical right ?
			ftp_pasv($this->ftp_connection, true);

			$local_file_resource     = fopen( $this->get_local_full_path(), 'w' );
			$ftp_get                 = ftp_fget( $this->ftp_connection, $local_file_resource, $this->remote_file_name, FTP_BINARY );
			fwrite( $local_file_resource, $ftp_get );
			fclose( $local_file_resource );
			ftp_close( $this->ftp_connection );
		}
	}

	/**
	 *
	 */
	public function run() {

        @mkdir( $this->local_file_dir );

	    self::$time_tracking_counter++;
	    $time_tracking_context = "ftp_get_csv_" . self::$time_tracking_counter;
        start_time_tracking( $time_tracking_context );

		if ( $this->method_run_called_once ) {
			throw_dev_error( 'Calling run() more than once is probably a code error. Consider making a new instance instead.' );
			exit;
		} else {
			$this->method_run_called_once = true;
		}

		$this->events[] = 'run';

		assert( $this->method != false, 'please define the method' );
		$this->local_file_name   = $this->get_local_filename_from_remote_file_name( $this->remote_file_name, 'csv' );

		switch ( $this->method ) {
			case 'ftp':
				$this->run_plain_ftp();
				break;
			case 'sftp':
				$this->run_sftp();
				break;
			default:
				$this->errors[] = 'Invalid method defined, no action taken.';
		}

		$this->local_file_exists = file_exists( $this->get_local_full_path() );

		if ( $this->local_file_exists ) {
			$this->local_file_size = filesize( $this->get_local_full_path() );
		}

		$this->time_in_seconds = end_time_tracking( $time_tracking_context );
	}

	/**
     * @param $remote_file_name
     * @param string $ext
     * @return string
     */
	public function get_local_filename_from_remote_file_name( $remote_file_name, $ext = 'csv' ) {
		$_filename_no_ext = get_path_info( $remote_file_name, 'filename' );
		$_filename_no_ext = strip_file_ext_and_clean_dots_slashes( $_filename_no_ext );
		$_filename_no_ext = $this->local_filename_prefix . $_filename_no_ext . '--' . time() . '--' . rand( 0, 10000 );

		// note: having some issues with files with spaces in them. This will convert
        // all spaces and any other not good remaining characters.
		$_filename_no_ext = make_slug( $_filename_no_ext, true );

		return $_filename_no_ext . '.' . $ext;
	}
}