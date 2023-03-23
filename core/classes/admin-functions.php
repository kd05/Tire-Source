<?php

/**
 * Class Admin_Functions
 */
Class Admin_Functions{

    /**
     * @return string[]
     */
	public static function csv_mime_types(){
		return array(
			'text/plain',
			'application/vnd.ms-excel',
			'text/x-csv',
			'application/csv',
			'application/x-csv',
			'text/csv',
			'text/comma-separated-values',
			'text/x-comma-separated-values',
			'text/tab-separated-values',
		);
	}

    /**
     * @return array
     */
	public static function upload_tmp_csv(){

		$response = array();
		$file = gp_if_set( $_FILES, 'file', array() );

		if ( $file ) {

			$name = gp_if_set( $file, 'name' );
			$type = gp_if_set( $file, 'type' );
			$tmp_name = gp_if_set( $file, 'tmp_name' );
			$error = gp_if_set( $file, 'error', 'no_file' );
			$size = gp_if_set( $file, 'size' );

			if ( $error ) {
				$response['error'] = 'Error: ' . $error;
				return $response;
			}

			// mime type can be faked anyways, and this prevents xslx so, i dont think this is needed
			// admin must be logged in to upload files, and files should be prevented direct access via .htaccess to avoid
			// security risks
//			$allowed_types = self::csv_mime_types();
//			if ( ! in_array( $type, $allowed_types ) ) {
//				$response['error'] = 'Invalid mime type for CSV: ' . $type;
//				return $response;
//			}

			// is this pretty redundant??
			if ( strpos( $name, '.csv' ) === false ) {
				$response['error'] = 'Error: Filename should contain .csv.';
				return $response;
			}

			$pathinfo = pathinfo( $name );
			// $suf = '-' . date('Ymd-Hisa-T');
			$suf = '-' . date('Ymd-His-T');
			$name2 = $pathinfo['filename'] . $suf . '.' . $pathinfo['extension'];
			$name2 = self::sanitize_filename( $name2 );

			if ( strlen( $name2 ) >= 255 ) {
			    $name2 = string_half( str_shuffle( $name2 ) );
            }

			$destination = ADMIN_UPLOAD_DIR . '/' . $name2;

			if ( file_exists( $destination ) ) {
				$response['error'] = 'File already exists: ' . $name2 . ' (file names that are too long may also trigger this error.)';
				return $response;
			}

			$move = move_uploaded_file( $tmp_name, $destination );

			if ( $move ) {
				return array(
					// lets not reveal file location to front-end i guess... just pass in filename
					// 'destination' => $destination,
					'file_name' => $name2,
					'file_upload_success' => true,
				);
			}

			$response['error'] = 'File appeared valid, but for an unknown reason, could not be moved into the temporary directory for parsing.';
			return $response;
		}
	}

	/**
	 * A little over the top sanitation below, but its used very rarely.
	 *
	 * @param $str
	 *
	 * @return mixed|string
	 */
	public static function sanitize_filename( $str ) {
		$str = strip_tags($str);
		$str = preg_replace('/[\r\n\t ]+/', ' ', $str);
		$str = preg_replace('/[\"\*\/\:\<\>\?\'\|]+/', ' ', $str);
		$str = strtolower($str);
		$str = html_entity_decode( $str, ENT_QUOTES, "utf-8" );
		$str = htmlentities($str, ENT_QUOTES, "utf-8");
		$str = preg_replace("/(&)([a-z])([a-z]+;)/i", '$2', $str);
		$str = str_replace(' ', '-', $str);
		$str = rawurlencode($str);
		$str = str_replace('%', '-', $str);
		return $str;
	}
}