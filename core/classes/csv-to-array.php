<?php

/**
 * Convert a file path ending with .csv into an array,
 * according to a column map passed in.
 *
 * The file stored locally will be deleted
 *
 * Class CSV_To_Array
 */
Class CSV_To_Array{

	/**
	 * @var array
	 */
	public $array = array();

	/**
	 * @var array
	 */
	public $errors = array();

	/**
	 * a bit of debug...
	 *
	 * @var array
	 */
	public $events = array();

	/**
	 * For example, $map might be: array( 'part_number' => 'Part Number', 'price' => 'Product Price' ].
	 *
	 * If the array has no header column you can pass in a $map like: [ 'part_number' => 0, 'total' => 15 ],
	 * and the third param below set to false.
	 *
	 * The keys will show up in $this->array. The values are what we look for in the CSV header row.
	 *
	 * CSV_To_Array constructor.
	 *
	 * @param      $path
	 * @param      $map
	 * @param bool $has_header_column - if false, $map should have integer indexes
	 */
	public function __construct( $path, $map, $has_header_column = true ){

		// return an array according to the array keys of $map,
		// and ignoring other columns in the CSV

		if ( ! $map ) {
			$this->errors[] = 'Column map required.';
			return false;
		}

		if ( ! file_exists( $path ) ) {
			$this->errors[] = 'File does not exist.';
			return false;
		}

		// don't forget fclose if handle exists
		$handle = fopen( $path, 'r' );

		if ( ! $handle ) {
			$this->errors[] = 'Error loading file.';
			return false;
		}

		if ( $has_header_column ) {

			// the header row
			$first = fgetcsv( $handle, 0, "," );
			$count_first = is_array( $first ) ? count( $first ) : 0;
			$this->events[] = 'header_row_count_' . $count_first;

			$slugs_to_ints = array();

			// loop through the first row to map the column integer
			// indexes to the slugs defined in the array keys of $map
			foreach ( $map as $slug=>$desired_name ) {

				$integer_matches = array();

				// find all column names that match, whether that's 0, 1, or more than 1.
				if ( $first ) {
					foreach ( $first as $integer=>$actual_name ) {

						if ( self::col_name_matches( $actual_name, $desired_name ) ) {
							$integer_matches[] = $integer;
						}
					}
				}

				$count = count( $integer_matches );

				if ( $count === 1 ) {
					$slugs_to_ints[$slug] = $integer_matches[0];
				} else if ( $count < 1 ) {
					$this->errors[] = 'Missing a required column in the CSV: ' . gp_test_input( $desired_name ) . '.';
					fclose( $handle );
					return false;
				} else {
					$this->errors[] = 'Found 2 columns in the CSV with similar/same name: ' . gp_test_input( $desired_name ) . '.';
					fclose( $handle );
					return false;
				}
			}
		} else {
			$slugs_to_ints = $map;
		}

		// count non-header rows
		$c = 0;

		// despite all attempts, there still seems to randomly be infinite loops on files that
		// at other times, process without any issues. So, we may have to impose a limit.
		// in practice, 100k is about the largest number of rows seen.
		$limit = 500000;

		// make sure to break ...
		while( true && $c <= $limit ) {

			$c++;
			$row = fgetcsv( $handle, 0, "," );

			// eventually there should be no row... (except that seems to not always be the case)
			if ( ! $row || ! is_array( $row )) {
				$this->events[] = 'last_row_encountered';
				break;
			}

			// I think some file formats may have an extra trailing comma potentially,
			// which makes it so that the very last row encounters is an empty or almost empty array
			// So.. hopefully this will be a safe way to prevent that, we would expect all body
			// rows to have the same number of columns as the header row, and if they do not,
			// what can we really do ...

			/**
			 * Update:
			 *
			 * This check seems logical, but it seems as though some suppliers leave empty
			 * row or header columns occassionally, and therefore this might break files
			 * that would otherwise process just fine. I think we can instead do an isset
			 * check below and throw an error if not set. But even then, its hard
			 * to know whether an error should be thrown or not. This amounts to allowing
			 * for different counts of rows as long as we dont try to access on of those values.
			 */

//			if ( $has_header_column && $count_first !== count( $row ) ) {
//				$this->events[] = "Row number $c had length not same as header row";
//				break;
//			}

			$row_array = array();

			// looking for empty rows that might be at the end of a csv file here
			$all_values_empty = true;

			// map is like [ 'part_number' => 'PartNumber' ], don't need the value here anymore
			foreach ( $map as $m1=>$m2 ) {
				// ie. $row_array['part_number'] = $row[25]

				if ( isset( $row[$slugs_to_ints[$m1]] ) ){
					$csv_value = $row[$slugs_to_ints[$m1]];
				} else {
					// dont like to do this, but for now we have to. See comment above.
					$csv_value = null;
				}

				if ( $csv_value ) {
					$all_values_empty = false;
				}

				$row_array[$m1] = $csv_value;
			}

			if ( $all_values_empty ) {
				$this->events[] = "Empty row on count $c";
			} else {
				$this->array[] = $row_array;
			}
		}

		fclose( $handle );
	}

    /**
     * Simply generates a numerically indexed array from the path to a locally
     * stored CSV file. Does a couple other things along the way:
     * - Filters out rows that have all empty values
     * - Puts a limit on the number of rows because sometimes this appears to be infinite or something.
     * - Skips the header row if you chose to do so (a lot of the files have a header row).
     *
     * This method sort of doesn't belong here because it has nothing to do with the rest
     * of the class. You either use this method alone, or make an instance of this class.
     *
     * WARNING: pay attention to the return value.
     *
     * @param $path_to_csv
     * @param bool $skip_header_row
     * @return array - An array of the resulting array and the error message, hopefully none.
     */
	public static function build_numerically_indexed_array( $path_to_csv, $skip_header_row = true ){

        $handle = fopen( $path_to_csv, 'r' );

        if ( ! $handle ) {
            return [[], "Could not open file via fopen."];
        }

        // prevent files that for some unknown reason, have infinite numbers of rows
        $count = 0;

        if ( $skip_header_row ) {
            // Has the side effect of moving an internal pointer or something
            fgetcsv( $handle, 0, "," );
        }

        $rows = [];

        do {
            $count++;

            $row = fgetcsv( $handle, 0, "," );

            if ( $row && is_array( $row ) ) {
                $rows[] = $row;
            }

            // some files actually have over 100k rows, so make sure the limit is large.
        } while ( $count < 500000 );

        // filter out rows that have all empty values, because sometimes this happens.
        $rows = array_filter( $rows, function( $row ) {

            // return true once encountering a single truthy value in the row.
            foreach ( $row as $value ) {
                if ( trim($value) ) {
                    return true;
                }
            }

            return false;
        });

        fclose( $handle );

        return [ $rows, "" ];
    }

	/**
	 * @param $str
	 *
	 * @return mixed|string
	 */
	public static function col_name_for_comparison( $str ) {
		$str = trim( strtolower( $str ) );
		return make_slug( $str, true );
	}

	/**
	 * don't want to dump the entire array because sometimes its just not useful
	 */
	public function get_debug_array(){

		$ret = array();
		$arr = is_array( $this->array ) ? $this->array : array();
		$arr = array_values( $arr );

		$ret['events'] = $this->events;
		$ret['errors'] = $this->errors;
		$ret['array'] = count( $arr );

		// to see format, of header row and first body row
		$ret['first'] = gp_json_encode( gp_if_set( $arr, 0 ) );
		$ret['second'] = gp_json_encode( gp_if_set( $arr, 1 ) );

		return $ret;
	}

	/**
	 * Column name could be 'PART NUMBER', and column we're looking for ($match) could be 'Part Number',
	 * we'll probably call this a match.
	 *
     * @param $col_name
     * @param $match
     * @return bool
     */
	public static function col_name_matches( $col_name, $match ) {
		$ret = $col_name && self::col_name_for_comparison( $col_name ) === self::col_name_for_comparison( $match );
		return $ret;
	}
}
