<?php

/**
 * Inventory cron job may run a few times per hour and requires
 * running many times to complete a process.
 *
 * This will help us throttle those processes by not STARTING
 * them more than once for each defined interval of the day.
 *
 * Class MWS_Inventory_Intervals
 */
Class MWS_Inventory_Intervals{

	/**
	 * For example, array( 0, 86400 ) should limit us to running once per day,
	 * and will run very early on in the day.
	 *
	 * array( 1, 0 ) or array() *should* mean not to run it at all, but there are probably
	 * better ways to turn off the updates.
	 *
	 * Interval ranges are probably inclusive. They can overlap, and it generally doesn't
	 * matter if they cover every second of the day. We'll just loop through the intervals
	 * and find the first one (if any) that match the current time.
	 *
	 * @return array
	 */
	public static function get_intervals(){
		// the general idea is to find out which interval we're in where the numbers
		// below represent the number of seconds elapsed since the start of the current day,
		// then once we get the interval, we can see if a process was already started in the same interval.
		$ret = array();
		$ret[] = array( 0, 21600 );
		$ret[] = array( 21600, 43200 );
		$ret[] = array( 43200, 64800 );
		$ret[] = array( 64800, 86400 );
		return $ret;
	}

	/**
	 *
	 */
	public static function get_current_interval_as_timestamps(){

		$arr = self::get_intervals();

		$now = time();

		$today = new DateTime();
		$today->modify( 'today' );
		$start_of_day = $today->getTimestamp();

		if ( $arr ) {
			foreach ( $arr as $interval ) {
				if ( $start_of_day + $interval[0] <= $now ) {
					if ( $start_of_day + $interval[1] >= $now ) {
						return array(
							$start_of_day + $interval[0],
							$start_of_day + $interval[1],
						);
					}
				}
			}
		}

		return false;
	}

	/**
	 * Input parameter is probably self::get_current_interval_as_timestamps() BUT
	 * you should first check if $interval is non-empty. This functions returns
	 * false if $interval is empty or not valid, which only indicates you should
	 * start a new process if $interval is valid.
	 */
	public static function get_processes_starting_in_interval( $interval, $mws_locale ){

		assert_mws_locale_valid( $mws_locale );

		// due to the name of the function, we're also checking the endpoint
		// of the interval, even though it may seem a bit redundant.

		if ( $interval && isset( $interval[0] ) && isset( $interval[1] ) ){

			$db = get_database_instance();

			$p = [];
			$q = '';
			$q .= 'SELECT * ';
			$q .= 'FROM ' . DB_amazon_processes . ' ';
			$q .= 'WHERE 1 = 1 ';
			$q .= 'AND process_type = "' . MWS_INVENTORY_TYPE_INVENTORY . '" ';

			$q .= 'AND process_time_start >= :time_1 ';
			$p[] = [ 'time_1', $interval[0], '%d' ];

			$q .= 'AND process_time_start <= :time_2 ';
			$p[] = [ 'time_2', $interval[1], '%d' ];

			$q .= 'AND process_locale = :locale ';
			$p[] = [ 'locale', $mws_locale, '%s' ];

			$q .= '';
			$q .= 'ORDER BY process_id DESC ';
			$q .= ';';

			$results = $db->get_results( $q, $p );

			return $results ? $results : false;
		}

		return false;
	}
}



