<?php

/**
 * currently you can only specify filename, not directory.
 *
 * @param $data
 * @param $filename
 * @param bool $append_year
 * @param bool $append_month
 * @param bool $append_day
 */
function log_data( $data, $filename, $append_year = false, $append_month = false, $append_day = false ) {

	if ( $append_year ) {
		$filename = $filename . '-' . date( 'Y' );
	}

	if ( $append_month ) {
		$filename = $filename . '-' . date( 'm' );
	}

	if ( $append_day ) {
		$filename = $filename . '-' . date( 'd' );
	}

	$log = new Logger( $filename );
	$log->log_data( $data );
}

/**
 * Filename might not be able to contain any directories, because
 * I don't know if they get created automatically or if we have issues
 * with not setting permissions or w/e.
 *
 * As far as I know, this function can only log a file directly into
 * the log directory, but not into other sub directories.
 *
 * It will not create the log directory first.... it may fail
 * on a brand new installation or w/e, or maybe it won't, I don't
 * know. It will work as long as the log directory already exists.
 *
 * Unlike log_data, it only logs what you give it. Log data adds
 * a bunch of stuff and serializes as JSON or w/e, which makes
 * some things very hard to read. This is why i'm adding this function.
 *
 * @param $filename_not_including_path
 * @param $file_contents
 * @return false|int
 */
function log_data_basic( $filename_not_including_path, $file_contents ){
    // very simple for now, unless something ends up not working in some environment.
    return file_put_contents( LOG_DIR . '/' . $filename_not_including_path, $file_contents, FILE_APPEND );
}

/**
 * pretty basic logger... json encodes stuff..
 *
 * Class Logger
 */
Class Logger {

	protected $filename;

	/**
	 * Logger constructor.
	 *
	 * @param $filename
	 */
	public function __construct( $filename ) {

		if ( ! $filename ) {
			$filename = 'gp-log.log';
		}

		// add .log
		if ( strpos( $filename, '.' ) === false ) {
			$filename = $filename . '.log';
		}

		$this->filename = $filename;
	}

	/**
	 *
	 * @param $data
	 */
	public function log_data( $data ) {

		$date_format = 'Ymd h:i:sa';

		// data should usually be an array, but u can pass in a string if u want also
		if ( gp_is_singular( $data ) ) {
			$data = array(
				'event' => $data,
			);
		}

		// note:... ideally, we always pass an array into here, so the code below is only a fallback
		// which will attempt to not lose data, but might not look good at all
		if ( ! is_array( $data ) ) {
			if ( is_object( $data ) ) {
				if ( $data instanceof stdClass ) {
					$_data = get_object_vars( $data );
				} else {
					// json encode would drop private class props I think
					$_data = get_var_dump( $data );
				}
			} else {
				// does this do anything at all? (can we even get to here?)
				$_data = gp_json_encode( $data );
			}
			$data = array(
				'event' => $_data,
			);
		}

		// add date if its not already there
		if ( ! isset( $data[ 'date' ] ) ) {
			$ts             = time(); // according to wp timezone
			$date           = date( $date_format, $ts );
			$data[ 'date' ] = $date;
		}

		if ( ! isset( $data['logged_in_user_email'] ) ) {
			if ( $user = cw_get_logged_in_user() ) {
				$data['logged_in_user_email'] = $user->get( 'email' );
			}
		}

		// put these indeces first, and then if others are set, put them in any order
		$order = array();

		// not doing anything atm
		$sorted = $this->order_log_data( $data, $order );

		// sanitize
		// would rather not sanitize data in a text file. this should be done if/when reading and printing
		// $sorted = $this->sanitize_array_data( $sorted );

		// add contents to log file
		$filename = $this->get_log_file_path();

        $file_add = json_encode( $sorted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . ",\r\n";

		$bytes = file_put_contents( $filename, $file_add, FILE_APPEND );
        chmod($filename, 0755);

        // in case file doesn't log for some reason (have reasons to believe this is in fact happening.)
        if ( ! $bytes ) {
            // phpmailer likes to both throw exceptions and echo sensitive userdata right to the screen,
            // we cannot let the exception go uncaught because uncaught exceptions will call this function
            // to attempt to log it.
            ob_start();
            try{
                $mail = get_php_mailer_instance( true );
                $mail->isHTML( true );
                $mail->addAddress( 'baljeet@geekpower.ca' );
                $mail->Body = $file_add;
                if ( ! $mail->send() ) {
                    // well shit, I don't know then...
                }
            } catch( Exception $e ){
                if ( ! IN_PRODUCTION ) {
                    echo get_pre_print_r( $e );
                }
            }

            $output = ob_get_clean();

            if ( ! IN_PRODUCTION ) {
                echo $output;
            }
        }

	}

	/**
	 * @return string
	 */
	public function get_log_file_path() {

		$dir = LOG_DIR;

		if ( ! file_exists( $dir ) ) {
			mkdir( $dir, 0755, true );
		}

		$ret = $dir . '/' . $this->filename;
		return $ret;
	}

	/**
	 * Filter the $data array before saving it..
	 *
	 * @param              $data
	 * @param array|object $order
	 */
	public function order_log_data( $data, $order = array( 'date', 'user' ) ) {
		return $data;
	}

}
