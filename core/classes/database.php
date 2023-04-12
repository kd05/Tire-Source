<?php

/**
 * Interface iDatabase
 */
Interface iDatabase {

	public static function get_instance();

	public function insert( $table, $data, $format );

	public function upsert( $table, $data, $format );

	public function update( $table, $data, $where, $data_format, $where_format );

	public function close_connection();

}

Class DatabasePDO implements iDatabase {

	/**
	 * @var DatabasePDO
	 */
	protected static $instance;

	/** @var PDO */
	public $pdo;

	/** @var Sql_Builder  */
	public $builder;

	// if you need to access these without an instance of the class, use the defined constants
	// in other words, im not going to bother making these static,
	// 2 ways to access the same parameters is more than enough.
	public $users = DB_users;
	public $orders = DB_orders;
	public $transactions = DB_transactions;
	public $order_items = DB_order_items;
	public $order_vehicles = DB_order_vehicles;
	public $reviews = DB_reviews;
	public $regions = DB_regions;
	public $tax_rates = DB_tax_rates;
	public $shipping_rates = DB_shipping_rates;
	public $tires = DB_tires;
	public $tire_brands = DB_tire_brands;
	public $tire_models = DB_tire_models;
	public $rims = DB_rims;
	public $rim_brands = DB_rim_brands;
	public $rim_models = DB_rim_models;
	public $cache = DB_cache;
	public $options = DB_options;
	public $amazon_processes= DB_amazon_processes;
	public $stock = DB_stock_updates;
	public $sub_sizes = DB_sub_sizes;
	public $rim_finishes = DB_rim_finishes;
    public $coupons = DB_coupons;

	// PDO param types
	public $int;
	public $str;
	public $null;
	public $bool;

	// used in ->get_results()
	private $print_next_query = false;
	private $print_next_query_string = false;
	private $print_next_query_results = false;

	/**
	 * Database constructor.
	 */
	public function __construct() {

		if ( $this->pdo === null ) {
			// not catching exception may reveal database details
			try {
				$this->pdo = new PDO( 'mysql:host=' . CW_DB_HOST . ';dbname=' . CW_DB_DATABASE, CW_DB_USERNAME, CW_DB_PASSWORD );
				Debug::add( 'db connected' );
				$this->pdo->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ );

				// don't convert null values ...
				$this->pdo->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_NATURAL );

				// Error handling
				if ( IN_PRODUCTION ) {
					$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
				} else {
					$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				}

			} catch ( PDOException $e ) {
				if ( IN_PRODUCTION ) {
					Debug::add( $e->getMessage(), 'database' );
					throw new Exception( 'DB Connection Error' );
				} else {
					throw $e;
				}
			}
		}

		$this->int = PDO::PARAM_INT;
		$this->str = PDO::PARAM_STR;
		$this->null = PDO::PARAM_NULL;
		$this->bool = PDO::PARAM_BOOL;

		$this->builder = new Sql_Builder();
	}

	/**
	 *
	 */
	public function print_next_query(){
		$this->print_next_query = true;
	}

	/**
	 *
	 */
	public function print_next_query_string(){
		$this->print_next_query_string = true;
	}

	/**
	 *
	 */
	public function print_next_query_results(){
		$this->print_next_query_results = true;
	}

	/**
	 * there might be an easier way to do this directly with PDO, but i just need this to work
	 * and not be so repetitive as i have to do this a bunch of times.
	 *
	 * @param      $q
	 * @param      $p
	 * @param      $col
	 * @param bool $allow_duplicate
	 * @param bool $allow_empty
	 *
	 * @return array
	 */
	public function get_results_and_fetch_all_values_of_column( $q, $p, $col, $allow_duplicate = true, $allow_empty = true ) {

		$r = $this->get_results( $q, $p );

		return gp_array_column( $r, $col, $allow_duplicate, $allow_empty );
	}

	/**
     * @param $queryString
     * @param array $params
     * @param bool $queue_hash
     * @return int
     */
	public function count_results( $queryString, $params = array(), $queue_hash = false ) {
		$r = $this->get_results( $queryString, $params, $queue_hash );
		return $r && is_array( $r ) ? count( $r ) : 0;
	}

	/**
     * @param $queryString
     * @param array $params - ie. [ [ "param_name", 123, "%d" ], [ "param_2", "stringParamValue", "%s" ] ]
     * @param bool $queue_hash
     * @return array
     */
	public function get_results( $queryString, $params = array(), $queue_hash = false ) {

		// $this->print_next_query();

		time_diff();
		Debug::log_time( 'DB_GET_RESULTS_BEFORE' );
		$print_string = $this->print_next_query_string || $this->print_next_query;
		$print_results = $this->print_next_query_results || $this->print_next_query;
		Debug::add( debug_pdo_statement( $queryString, $params ), 'DB_GET_RESULTS' );

		$this->print_next_query = false;
		$this->print_next_query_results = false;
		$this->print_next_query_string = false;

		try{

			if ( DEBUG_MODE && $print_string ) {
				$dev_alert_string = debug_pdo_statement( $queryString, $params );
				queue_dev_alert( 'Query String', $dev_alert_string );
			}

			// start time
			start_time_tracking( 'db_get_results' );

			// should we only track execute??? time diff seems very inconsistent with phpmyadmin
			$st = $this->pdo->prepare( $queryString );

			if ( $params ) {
				$st = $this->bind_params( $st, $params );
			}

			$st->execute();
			$results = $st->fetchAll();

			if ( $queue_hash && ! IN_PRODUCTION ) {
				$hash = md5( gp_json_encode( $results ) );
				$count = count( $results );
				queue_dev_alert( 'results_hashed (' . $queue_hash . ')(' . $count . ')(' . $hash . ')', get_pre_print_r( $results ) );
			}

			// time diff
			$time_diff = end_time_tracking( 'db_get_results' );

			if ( $time_diff > 0.05 ) {
				// we could log slow queries, but at this point I don't think we need to.
				// also, there are a few queries in the admin section that we know are quite slow
			}

			// print the time
			if ( DEBUG_MODE && $print_string ) {
				queue_dev_alert( $time_diff, '' );
			}

			if ( DEBUG_MODE && $print_results ) {
				queue_dev_alert( 'Query', get_pre_print_r( $results ) );
			}

			Debug::log_time( 'DB_GET_RESULTS_AFTER' );
			return $results;

		} catch ( Exception $e ){
			if ( DEBUG_MODE ) {
				echo '<pre>' . print_r( $e, true ) . '</pre>';

				echo '<br>============= query string (exception) ============= <br>';
				echo debug_pdo_statement( $queryString, $params );
				echo '<br>============= //////////// ============= <br>';

				exit;
			} else {
				Debug::add( $e, 'EXCEPTION' );
				return array();
			}
		}
	}

    /**
     * Returns an array of arrays.
     *
     * @param $query
     * @param array $params
     * @return array[]
     */
	static function get_results_( $query, array $params = [] ) {

	    $rows = get_database_instance()->get_results( $query, $params );

	    return array_map( function( $row ){
	        return (array) $row;
        }, $rows );
    }

	/**
	 * Ensure we have a valid parameter type as used in $this->pdo->bindValue.
	 *
	 * Sometimes we'll use %s or %d in our code, but PDO doesn't understand this.
	 *
     * @param $str
     * @return bool|int|mixed
     * @throws Exception
     */
	public function filter_param_type( $str ) {

		$default = PDO::PARAM_STR;

		if ( ! gp_is_singular( $str ) ) {
			return $default;
		}

		$map = array(
			's' => PDO::PARAM_STR,
			'%s' => PDO::PARAM_STR,
			'd' => PDO::PARAM_INT,
			'%d' => PDO::PARAM_INT,
		);

		// if $str is not an array key of $map, then leave $str unchanged.
		$ret = gp_if_set( $map, $str, $str );

		// ensure $str is one of these:
		$valid = array(
			PDO::PARAM_INT,
			PDO::PARAM_STR,
			PDO::PARAM_NULL,
			PDO::PARAM_BOOL,
		);

		// have to be careful about the strict comparison because PDO::PARAM_NULL is 0 which
		// means all false like values will be in the array but the entire purpose of this function
		// is to be 100% sure that a parmeter type is valid.
		$ret = in_array( $ret, $valid, true ) ? $ret :  $default;
		return $ret;
	}

	/**
     * @param $st
     * @param $params
     * @return bool|PDOStatement
     * @throws Exception
     */
	public function bind_params( $st, $params ) {

		if ( ! $st instanceof PDOStatement ) {
			$st = $this->pdo->prepare( $st );
		}

		if ( ! $params ) {
			return $st;
		}

		// array can be like
		// [ $paramName => $paramValue, $paramName2 => $paramValue2, ... ], OR (type string is inferred)
		// [ ['paramName', $paramValue, $paramType], ['pName2', $pValue2, $pType2], ... ]

		if ( $params && is_array( $params )) {
			foreach ( $params as $k=>$p ) {
				if ( gp_is_singular( $p ) ) {
					$parameter = $this->builder->make_param_string( $k );
					$value = $p;
					$type = $this->str;
				} else {
					$parameter = gp_if_set( $p, 0 );
					$value = gp_if_set( $p, 1 );
					$type = gp_if_set( $p, 2, $this->str );
					$type = $this->filter_param_type( $type );
				}

                // added nov 2022.. seems necessary to do this in my dev env
                // but not in production (my db/pdo is more picky).
                // Sucks to modify something that could affect hundreds or
                // thousands of other parts of code. But, I don't know how else to
                // fix this.
                // I think $parameter could be an integer (or string of digits) if using '?'
                // placeholders in query string
                if ( ! gp_is_integer( $parameter ) ) {
                    if ( strpos( $parameter, ':' ) !== 0 ) {
                        $parameter = ':' . $parameter;
                    }
                }

                // depending on pdo and/or db engine, parameter may have to be an integer
                // or start with ':'
				$st->bindValue( $parameter, $value, $type );
			}
		}

		return $st;
	}

	/**
     * @param $table
     * @param $where
     * @param array $where_format
     * @param null $mode
     * @return array
     * @throws Exception
     */
	public function get( $table, $where, $where_format = array(), $mode = null ) {

		if ( ! is_array( $where ) ) {
			throw new Exception( '"where" must be an array' );
		}

		$q = '';
		$q .= 'SELECT * ';
		$q .= 'FROM ' . gp_esc_db_table( $table ) . ' ';

		// WHERE 1 = 1 AND column_1 = :_column_1 AND ...
		$q .= $this->builder->where( $where, ':' );
		$q .= ' ';
		$q .= ';'; // ;

		$st = $this->pdo->prepare( $q );

		$p = array();

		// Bind the WHERE parameters
		if ( $where ) {
			foreach ( $where as $w1=>$w2 ) {
				// default to string
				if ( gp_if_set( $where_format, $w1, '%s' ) === '%s' ) {
					$st->bindValue( $w1, $w2, PDO::PARAM_STR );
					$p[] = [ $w1, $w2, PDO::PARAM_STR ];
				} else {
					$st->bindValue( $w1, $w2, PDO::PARAM_INT );
					$p[] = [ $w1, $w2, PDO::PARAM_INT ];
				}
			}
		}

		Debug::add( debug_pdo_statement( $st, $p ) );

		$st->execute();
		$r = $st->fetchAll();
		return $r;
	}

	/**
     * @param $table
     * @param $data
     * @param $where
     * @param array $data_format
     * @param array $where_format
     * @return bool|mixed|string
     * @throws Exception
     */
	public function upsert( $table, $data, $where, $data_format = array(), $where_format = array() ) {

		$rows = $this->get( $table, $where, $where_format );

		if ( $rows ) {
			$r = $this->update( $table, $data, $where, $data_format, $where_format );
		} else {
			$r =$this->insert( $table, $data, $data_format );
		}

		return $r;
	}

	/**
	 * $format arrays are key/value pairs, column_name=>type, where type is %s for string, %d for integer,
	 * You can leave them blank, and %s will be used, as opposed to not escaping values.
	 *
	 * @param       $table
	 * @param       $data
	 * @param       $where
	 * @param array $data_format
	 * @param array $where_format
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function update( $table, $data, $where, $data_format = array(), $where_format = array() ) {

		//		UPDATE table_name
		//		SET column1 = value1, column2 = value2, ...
		//		WHERE condition;

		if ( ! is_array( $data ) ) {
			throw new Exception( 'Data must be an array.' );
		}

		if ( ! is_array( $where ) ) {
			throw new Exception( 'Data must be an array.' );
		}

		if ( ! $where ) {
			throw new Exception( 'Where clause must not be empty to prevent you from updating every row of a table.' );
		}

		$q = '';

		// UPDATE table_name
		$q .= $this->builder->update( $table );
		$q .= ' ';

		// SET column1 = value1, column2 = value2 ...
		$q .= $this->builder->set( $data, ':' );
		$q .= ' ';

		// WHERE 1 = 1 AND column_1 = :_column_1 AND ...
		$q .= $this->builder->where( $where, ':_' );
		$q .= ' ';
		$q .= ';'; // ;

		// prepare
		$st = $this->pdo->prepare( $q );

		// Bind the SET parameters
		if ( $data ) {
			foreach ( $data as $d1=>$d2 ) {
				// default to string
				if ( gp_if_set( $data_format, $d1, '%s' ) === '%s' ) {
					$st->bindValue( $d1, $d2, PDO::PARAM_STR );
				} else {
					$st->bindValue( $d1, $d2, PDO::PARAM_INT );
				}
			}
		}

		// Bind the WHERE parameters
		if ( $where ) {
			foreach ( $where as $w1=>$w2 ) {
				// default to string
				if ( gp_if_set( $where_format, $w1,'%s' ) === '%s' ) {
					$st->bindValue( '_' . $w1, $w2, PDO::PARAM_STR );
				} else {
					$st->bindValue( '_' . $w1, $w2, PDO::PARAM_INT );
				}
			}
		}

		return $this->execute( $st );
	}

	/**
	 * Data values will always be SQL escaped as a string, unless otherwise specified in $format.
	 * So do not pass in a format array that is ordered based on the key/value paris in $data,
	 * the format should also be key/value paris, ie. column_name=>%d
	 *
     * @param $table
     * @param $data
     * @param array $format
     * @return bool|string
     * @throws Exception
     */
	public function insert( $table, $data, $format = array() ) {

		if ( ! is_array( $data ) ) {
			throw new Exception( 'Data must be an array to insert into table' );
		}

		// would rather not throw exception here..
		if ( ! $data ) {
			return false;
		}

		$bind = array();

		// ie. convert "part_number" to ":part_number"
		// this maybe shouldn't be called $params
		$params = $this->builder->unbind( $data );

		$columns = array_keys( $params );

		// add back ticks otherwise cols named with reserved sql keywords break everything
		$columns = array_map( function($c){
			$c = gp_esc_db_col( $c );
			$c = '`' . $c . '`';
			return $c;
		}, $columns );

		$q = '';
		$q .= 'INSERT INTO ' . gp_esc_db_col( $table );
		$q .= ' ';

		// (`column_1`, `column_2`, ...)
		$q .= '( ' . implode_comma( $columns ) . ') ';

		// VALUES ( :column_1, :column_2, ... )
		$q .= 'VALUES (' . implode_comma(  $params ) . ' ) ';
		$q .= ';';

		// loop through $data, then check for value found in $format, defaulting to string always
		foreach ( $data as $col => $val ) {
			$type = $this->filter_param_type( gp_if_set( $format, $col, '%s' ) );
			$bind[] = [ $col, $data[$col], $type ];
		}

//		echo '<pre>' . print_r( $q, true ) . '</pre>';
//		echo '<pre>' . print_r( $bind, true ) . '</pre>';
//		var_dump( $bind );
//		echo '<pre>' . print_r( debug_pdo_statement( $q, $bind ), true ) . '</pre>';
//		exit;

		// echo '<pre>' . print_r( debug_pdo_statement( $q, $bind ), true ) . '</pre>';

		$st = $this->bind_params( $q, $bind );
		$success = $this->execute( $st );

		if ( $success ) {
			// note that last insert ID doesn't always work as expected but in the context
			// of our function, and inserting one row at a time, I think it should be pretty reliable.
			// this does however only work if the table has an auto increment primary key, and
			// no idea if it will work reliably if the table has 2 primary keys.
			$last_insert_id = $this->pdo->lastInsertId();
			if ( $last_insert_id ) {
				return $last_insert_id;
			}

			// fallback just in case
			return true;
		}

		return false;
	}

	/**
	 * @param string|PDOStatement $st
	 * @param null $input_params
	 *
	 * @return mixed
	 */
	public function execute( $st, $input_params = null ) {

		if ( ! $st instanceof PDOStatement ) {
			$st = $this->bind_params( $st, $input_params );
			$success = $st->execute();
		} else {
			$success = $st->execute( $input_params );
		}

		Debug::add( $st->queryString, 'PDOStatement' );

		if ( ! $success ) {
			Debug::add( $st->errorInfo(), 'PDO Error' );
		}

		return $success;
	}

	/**
	 * See the function get_database_instance(), and use that instead of this directly.
	 *
	 * @return DatabasePDO
	 */
	public static function get_instance() {
		if ( self::$instance !== null ) {
			return self::$instance;
		}

		return self::$instance = new self();
	}

	/**
	 *
	 */
	public function close_connection() {
		$this->pdo = null;
	}
}

