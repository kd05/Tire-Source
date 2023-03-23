<?php

/**
 * Class DB_Table
 */
Class DB_Table {

	protected static $prefix; // can setup if all (or most) of your fields contain a similar prefix
	protected static $table = '';
	protected static $primary_key = '';
	protected static $fields = array();
	protected $data; // def not static

    // helps with lazy loading
    protected $json_decode_cache = [];

	protected static $db_init_cols = array();
	protected static $db_init_args = array();

	/*
	 * An array of keys required to instantiate the object from a general array or object
	 * which may or may not be a row of data from the database.
	 */
	protected static $req_cols = array();

	/**
	 * Leave this null if all $req_cols are also required to be
	 * not empty.
	 *
	 * @var null
	 */
	protected static $req_cols_not_empty = null;

	/**
	 * Not sure if we'll need options
	 *
     * DB_Table constructor.
     * @param $data
     * @param array $options
     * @throws Exception
     */
	public function __construct( $data, $options = array() ){
		$this->setup_data( static::$fields, $data );
	}

    /**
     * @param $fields
     * @param $data
     * @throws Exception
     */
	public function setup_data( $fields, $data ) {

	    // reset this cache
	    $this->json_decode_cache = [];

		if ( $fields && ( is_array( $fields ) || is_object( $fields ) ) ) {
			foreach ( $fields as $field ) {
				$value = gp_if_set( $data, $field, null );
				$this->set( $field, $value );
			}
		}
	}

    /**
     * Get an array of instances of self, with all items
     * in DB.
     *
     * Does not inner join anything, so not always efficient
     * if your table has relations to other tables.
     *
     * @param string $order_by_sql
     * @return array
     */
	public static function query_all($order_by_sql = ""){

	    $db = get_database_instance();
	    $table = static::get_table();
	    $pk = static::get_primary_key();

	    $order_by_sql = $order_by_sql ? "ORDER BY $order_by_sql" : "ORDER BY " . $pk;

	    $q = 'SELECT * FROM ' . $table . ' ';
        $q .= $order_by_sql;
        $q .= ";";

	    $r = $db->get_results( $q );

	    return array_map( function( $row ) {
	        return static::create_instance_or_null( $row );
        }, $r );
    }

    /**
     * For JSON encoded fields, you can make a getter function that passes
     * the column to this function.
     *
     * @param $field
     * @return mixed
     */
	protected function lazy_load_json( $field ) {

	    if ( ! array_key_exists( $field, $this->json_decode_cache ) ) {

	        $v = $this->get( $field );

	        if ( strlen( $v ) === 0 ) {
	            $v = "{}";
            }

	        $this->json_decode_cache[$field] = json_decode( $v, true );
        }

	    return $this->json_decode_cache[$field];
    }

    /**
     * ie. "table.col_1 AS table_col_1, table.col_2 AS table_col_2",
     *
     * Used in an SQL string.
     *
     * @param null $table
     * @param null $prefix
     * @return string
     */
	public static function prefix_alias_select( $table = null, $prefix = null ) {

		$table = $table === null ? static::$table : $table;
		$prefix = $prefix === null ? static::$table . '_' : $prefix;

		$fields = static::get_fields();

		$arr = array();
		if ( $fields ) {
			foreach ( $fields as $field ) {
				$arr[] = $table . '.' . $field . ' AS ' . $prefix . $field;
			}
		}

		return implode_comma(  $arr );
	}

	/**
	 * @param $thing - instance of $class, primary key, or an array/object of data to make a new $class
	 * @param $class - Class name that we're trying to make an instance of (should extend DB_Table)
	 * @param $prop - the property in your class where you store the instance of $class
     * @return bool
	 */
	public function set_foreign_object( $thing, $class, $prop ) {

		if ( ! property_exists( $this, $prop ) ) {
			return false;
		}

		// create_instance_or_null will also check this, but we're not 100% that method exists
		if ( $thing instanceof $class ) {
			$this->{$prop} = $thing;
			return true;
		}

		// maybe prevent infinite loop on coding error
		if ( get_class( $this) === $class ) {
			return false;
		}

		// assume $thing is the primary key if its singular
		if ( gp_is_singular( $thing ) ) {
			if ( method_exists( $class, 'create_instance_via_primary_key' ) ) {
				$this->{$prop} = $class::create_instance_via_primary_key( $thing );
			}
		} else {
			if ( method_exists( $class, 'create_instance_or_null' ) ) {
				$this->{$prop} = $class::create_instance_or_null( $thing );
			}
		}

		if ( $this->{$prop} instanceof $class ) {
			return true;
		}

		return false;
	}

	/**
	 * @return string
	 */
	public static function get_primary_key(){
		return static::$primary_key;
	}

	/**
	 * @return array
	 */
	public static function get_fields(){
		return static::$fields;
	}

	/**
	 * @return string
	 */
	public static function get_table(){
		return static::$table;
	}

	/**
	 * @return mixed
	 */
	public static function get_db_init_columns(){
		return static::$db_init_cols;
	}

	/**
	 * @return mixed
	 */
	public static function get_db_init_args(){
		return static::$db_init_args;
	}

    /**
     * @param $data
     * @param array $format
     * @return bool|string
     */
	public static function insert( $data, $format = array() ) {

		$db = get_database_instance();

		$_data = array();
		$_format = array();

		// assemble arrays using only the correct table columns... ignoring
		// anything else passed in in $data and $format, otherwise we'll get errors.
		if ( static::$fields ) {
			foreach ( static::$fields as $f ) {

				// I guess check array key exists in case we want to specify $field['col'] = null
				// isset will return false for null values
				if ( array_key_exists( $f, $data ) ) {
					$_data[$f] = gp_if_set( $data, $f );
					$_format[$f] = gp_if_set( $format, $f );
				}
			}
		}

		// skip empty insert statements?? maybe some tables would benefit from it however..
//		if ( ! $_data ) {
//			return false;
//		}

		// this is where it gets a bit tricky.. if the table does not have an auto increment
		// primary key, then what is insert ID?... currently all tables will, but if some don't
		// then i'm not sure what to do here.... inserting and not getting an ID back means you can't
		// access the object you just inserted.
		$insert_id = $db->insert( static::$table, $_data, $_format );
		return $insert_id ? $insert_id : false;
	}

	/**
	 * Can use this to get a list of tables that have a corresponding DB_Table object
	 */
	public static function get_table_class_map(){

		// database table name mapped to the name of the model class
		$map = array(
			DB_pages => 'DB_Page',
			DB_page_meta => 'DB_Page_Meta',
			DB_amazon_processes => 'DB_Amazon_Process',
			DB_stock_updates => 'DB_Stock_Update',
            DB_Sync_Request::get_table() => 'DB_Sync_Request',
            DB_Sync_Update::get_table() => 'DB_Sync_Update',
            DB_Price_Rule::get_table() => 'DB_Price_Rule',
            DB_Price_Update::get_table() => 'DB_Price_Update',
			DB_suppliers => 'DB_Supplier',
			DB_tires => 'DB_Tire',
			DB_tire_brands => 'DB_Tire_Brand',
			DB_tire_models => 'DB_Tire_Model',
			DB_rims => 'DB_Rim',
			DB_rim_brands => 'DB_Rim_Brand',
			DB_rim_models => 'DB_Rim_Model',
			DB_rim_finishes => 'DB_Rim_Finish',
			DB_users => 'DB_User',
			DB_orders => 'DB_Order',
			DB_transactions => 'DB_Transaction',
			DB_order_items => 'DB_Order_Item',
			DB_order_vehicles => 'DB_Order_Vehicle',
			DB_order_emails => 'DB_Order_Email',
			DB_reviews => 'DB_Review',
			DB_regions => 'DB_Region',
			DB_tax_rates => 'DB_Tax_Rate',
			DB_shipping_rates => 'DB_Shipping_Rate',
			DB_cache => 'DB_Cache',
			DB_options => 'DB_Option',
			DB_sub_sizes => 'DB_Sub_Size',
		);

		return $map;
	}

    /**
     * @param $table
     * @param $pk
     * @return bool|DB_Table|static|null
     */
	public static function get_instance_via_table_name_and_primary_key( $table, $pk ){
		/** @var DB_Table|null $class */
		$class = self::map_table_to_class( $table );
		$obj = $pk && $class ? $class::create_instance_via_primary_key( $pk ) : false;
		return $obj;

	}

	/**
	 * Returns the class name of a class that extends this one, or false.
	 *
	 * @param $table
	 *
	 * @return bool|mixed
	 */
	public static function map_table_to_class( $table ) {
		$map = self::get_table_class_map();
		$class = isset( $map[$table] ) ? $map[$table] : false;
		$class = $class && is_subclass_of( $class, get_class() ) ? $class : false;
		return $class;
	}

	/**
	 * We can use empty instances to access data that actually,
	 * doesn't really belong in this class,  like the arguments to
	 * create the table if it doesn't exist. For the sake of simplicity,
	 * we have included that data into each object. It makes it easier
	 * to modify database columns when you only have to change one file
	 * each time.
	 *
	 * @param $table
	 *
	 * @return null|DB_Table
	 */
	public static function create_empty_instance_from_table( $table ) {

		/** @var DB_Table $class */
		$class = self::map_table_to_class( $table );

		if ( $class ) {
			return $class::get_empty_instance();
		}

		return null;
	}

	/**
	 * @return static
	 */
	public static function get_empty_instance(){
		return new static( array(), array() );
	}

	/**
	 * This *should* do the trick. If you are not sure, you may just want to
	 * get a new object from the primary key value yourself. Object properties other
	 * than fields may not get re-synced, which is actually partially why I want this function
	 * to exist. Right now I don't believe we have any use for this, but in the future, it may be good
	 * to keep the same object state, but re_sync the fields with whatever is in the database.
	 */
	public function re_sync(){
		$clone = static::create_instance_via_primary_key( $this->get_primary_key_value() );

		// cached data
        $this->json_decode_cache = [];

		$this->setup_data( static::$fields, $clone->to_array() );
	}

	/**
	 * This can have unexpected results if your table has composite primary keys,
	 * which currently, the class does not support.
	 */
	public function delete_self_if_has_singular_primary_key(){

		$pk = $this->get_primary_key_value();
		$table = gp_esc_db_table( static::$table );

		if ( ! $pk || ! $table ) {
			throw_dev_error( 'Cannot delete' );
		}

		$db = get_database_instance();
		$p = [];
		$q = '';
		$q .= 'DELETE ';
		$q .= 'FROM ' . $table . ' ';
		$q .= 'WHERE ' . gp_esc_db_col( static::$primary_key ) . ' = :pk ';
		$p[] = [ 'pk', $pk ];
		$q .= '';
		$q .= ';';

		$st = $db->bind_params( $q, $p );
		$deleted = $st->execute();

		return (bool) $deleted;
	}

	/**
	 * In most cases you will probably want to use $this->update_database_and_re_sync(),
	 *
	 * However!.. for classes that use the set_foreign_object() method in their constructor,
	 * it might be better to do:
	 *
	 * $object->update_database_but_not_instance(),
	 * $object = $object::create_instance_from_primary_key( $object->get_primary_key_value() )
	 *
	 * The difference is that the second method will re run __construct() and setup
	 * the class properties that might represent other objects.
	 *
	 * @see $this->update_database_and_re_sync()
	 * @see $this->re_sync()
	 *
	 * @param       $data
	 * @param array $data_format
     * @return bool
     * @throws Exception
	 */
	public function update_database_but_not_instance( $data, $data_format = array() ) {

		$db = get_database_instance();

		$pk = static::$primary_key;

		$pk_int = gp_is_integer( $pk );

		$updated = $db->update(
			static::$table,
			$data,
			array(
				static::$primary_key => $this->get_primary_key_value(),
			),
			$data_format,
			array(
				static::$primary_key => $pk_int ? '%d' : '%s',
			)
		);

		return (bool) $updated ;
	}

	/**
	 * would be better just to modify self, but.. I don't think we can say $this = new self()
	 * from within an object. The potential issue here is that the function doesn't return
	 * whether or not the update was successful.
	 *
	 * @param       $data
	 * @param array $data_format
     * @return bool
     * @throws Exception
	 */
	public function update_database_and_re_sync( $data, $data_format = array() ){

		$updated = $this->update_database_but_not_instance( $data, $data_format );

		if ( $updated ) {
			$this->re_sync();
			return true;
		}

		return false;
	}

	/**
	 * @param $key
	 *
	 * @return bool|mixed
	 */
	public function get_and_clean( $key ){
		return $this->get( $key, null, true );
	}

	/**
	 * I use this for some items where we expect some illegal characters like
	 * single or double quotes, but we don't want to allow XXS.
	 *
     * @param $key
     * @return bool|mixed|string
     */
	public function get_and_strip_tags( $key ) {
		$v = $this->get( $key );
		$v = strip_tags( $v );
		return $v;
	}

    /**
     * @param $key
     * @param null $default
     * @param bool $clean
     * @return bool|mixed|string
     */
	public function get( $key, $default = null, $clean = false ) {

		// ie. if a column is "tire_brand_name", you can call $obj->get( 'name' ),
		// instead of $obj->get( 'tire_brand_name' );
		if ( static::$prefix && ! in_array( $key, static::$fields ) ) {
			$key_2 = static::$prefix . $key;
			// leave key unmodified if its not found
			if ( in_array( $key_2, static::$fields ) ) {
				$key = $key_2;
			}
		}

		// its not impossible for $this->data to contain array keys not in static::$fields.

		$ret = gp_if_set( $this->data, $key, $default );
		$ret = $clean ? gp_test_input( $ret ) : $ret;
		return $ret;
	}

	/**
	 * In child classes, can add rows. Not necessary just to change values,
	 * instead, see $this->get_cell_data_for_admin_tables.
	 *
	 * @param $row
	 *
	 * @return stdClass
	 */
	public function filter_row_for_admin_tables( $row ) {
		return $row;
	}

	/**
	 * child classes can add their own args to be passed to the admin edit page..
	 *
	 * args might be for adding extra columns, or putting some html before or after..
	 *
	 * @return array
	 */
	public function get_admin_archive_page_args(){
		return array();
	}

	/**
	 * this MUST return strictly NULL to indicate to use the raw (but first sanitized) database results.
	 *
	 * Do not simply return $this->get( $key ), even though in theory that is the same as raw database results.
	 *
	 * Only return not null when you want to show it instead of raw database results.
	 *
	 * Also.. override in your child class.
	 *
     * @param $key
     * @param $value
     * @return null
     */
	public function get_cell_data_for_admin_table( $key, $value ){
		return null;
	}

	/**
	 * Just calls to_array() but also will filter the data returned so its formatted for admin usage.
	 * In many cases, filtering probably just adds links to certain items.
	 *
     * @param array $exclude
     * @param array $use_only
     * @param bool $link_pk
     * @return array|stdClass
     */
	public function to_array_for_admin_tables( $exclude = array(), $use_only = array(), $link_pk = false ){

		$arr = $this->to_array( $exclude, $use_only );

		if ( $arr ) {

			if ( method_exists( $this, 'filter_row_for_admin_tables' ) ) {
				$arr = $this->filter_row_for_admin_tables( $arr );
			}

			foreach ( $arr as $key=>$value ) {

				if ( $link_pk && $key === static::$primary_key ) {
					$arr[$key] = $this->get_admin_link_to_self();
					continue;
				}

				$_value = $this->get_cell_data_for_admin_table( $key, $value );

				if ( $_value === null ) {
					//  note: this was added quite late as I realized some stuff may have not been getting
					// properly sanitized when printing, so it is possible that a few tables are adversely affected
					$arr[$key] = strip_tags( $value );
				} else {
					// this will often have html.. so no sanitation here
					$arr[$key] = $_value;
				}
			}
		}

		return $arr;
	}

	/**
     * @param array $exclude
     * @param array $use_only
     * @return array
     * @throws Exception
     */
	public function to_array( $exclude = array(), $use_only = array() ){

		$arr = array();

		if ( $use_only && is_array( $use_only )) {
			foreach ( $use_only as $field ) {
				if ( $exclude && in_array( $field, $exclude ) ) {
					continue;
				}
				$arr[$field] = $this->get( $field );
			}
		} else {
			if ( static::$fields && is_array( static::$fields ) ) {
				foreach ( static::$fields as $field ) {

					if ( $exclude && in_array( $field, $exclude ) ) {
						continue;
					}

					$arr[$field] = $this->get( $field );
				}
			}
		}

		return $arr;
	}

	/**
     * @param null $default
     * @return bool|mixed|string
     */
	public function get_primary_key_value( $default = null ){
		return $this->get( static::$primary_key, $default );
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function set( $key, $value ) {

		$method = 'callback_on_set_' . $key;
		if ( method_exists( $this, $method ) ) {
			$this->$method( $key, $value );
		}

		$this->data[ $key ] = $value;
	}

    /**
     * @param $data
     * @return bool
     */
	public static function data_contains_all_fields( $data ) {

		$arr = gp_make_array( $data );

		$error = false;
		if ( static::$fields ){
			foreach ( static::$fields as $field ) {
				if ( ! array_key_exists( $field, $arr ) ) {
					$error = true;
				}
			}
		}

		return ! $error;
	}

	/**
	 * Returns parameter 1 if parameter 3 is 'CA',
	 * Returns parameter 2 if parameter 3 is 'US',
	 * Throws an exception otherwise.
	 *
	 * Does not validate in any way the values passed in to parameters 1 and 2.
	 *
	 * This is useful mainly just for tires and rims where each table has
	 * a lot of columns in pairs with both a canadian and u.s. version.
	 *
     *
     * @param $column_ca
     * @param $column_us
     * @param $locale
     * @return mixed
     * @throws Exception
     */
	public static function switch_column_via_locale( $column_ca, $column_us, $locale ) {
		switch( $locale ) {
			case APP_LOCALE_CANADA:
				return $column_ca;
			case APP_LOCALE_US:
				return $column_us;
			default:
				throw new Exception( 'Invalid locale based column.' );
		}
	}

	/**
	 * Data could be the return value of a database query. You can check if
	 * data contains all the information required to make an object directly,
	 * and otherwise, use other functions to make a new object from a primary key
	 * and/or slug, part number etc.
	 *
	 * Of course when inner joining, be mindful of column names, because if a data
	 * point appears to be present, I can't predict that it actually belongs to another table.
	 * Ie. if two tables have a column "name" and you inner join them together. This is
	 * also why I have setup the column names that I expect to inner join to all have
	 * their own unique prefixes.
	 *
     * @param $data
     * @return bool
     * @throws Exception
     */
	public static function req_cols_not_empty( $data ) {

		$error = false;
		$fields = array();

		if ( static::$req_cols_not_empty !== null ) {
			$fields = static::$req_cols_not_empty;
		} else {
			$fields = static::$req_cols;
		}

		if ( $fields && is_array( $fields ) ) {
			foreach ( $fields as $field ) {
				$val = gp_if_set( $data, $field, null );
				if ( ! $val ) {
					$error = true;
				}
			}
		}

		return ! $error;
	}

	/**
	 * This is basically a wrapper for __construct() that verifies data and returns
	 * the object, otherwise null. In most cases you should use this rather than
	 * creating the instance manually.
	 *
     * @param $data
     * @param array $options
     * @param bool $force_req
     * @return static|null
     * @throws Exception
     */
	public static function create_instance_or_null( $data, $options = array(), $force_req = true ) {

		if ( ! $data ) {
			return null;
		}

		if ( $data instanceof static ) {
			return $data;
		}

		// convert to array
		if ( is_object( $data ) ) {
			$data = gp_object_to_array( $data );
		}

		if ( ! is_array( $data ) ) {
			return null;
		}

		if ( $force_req ) {
			$found_req = true;
			if ( static::$req_cols ) {
				foreach ( static::$req_cols as $col ) {
					if ( ! isset( $data[ $col ] ) ) {
						$found_req = false;
						break;
					}
				}
			}

			if ( ! $found_req ) {
				return null;
			}
		}

		// can't do new self()
		return new static( $data, $options );
	}

    /**
     * @param $pk
     * @param array $options
     * @return DB_Table|null|static
     * @throws Exception
     */
	public static function create_instance_via_primary_key( $pk, $options = array() ) {

		if ( $pk instanceof static ){
			return $pk;
		}

		$db     = get_database_instance();
		$result = $db->get( static::$table, array( static::$primary_key => $pk ) );
		$row    = gp_if_set( $result, 0 );
		if ( ! $row ) {
			return null;
		}

		return static::create_instance_or_null( $row, $options );
	}

	/**
	 * @return string
	 */
	public static function db_init_table_col_str( $col, $str ){
		$col = trim( $col );
		$str = trim( $str );
		$op = '';
		$op .= '`' . $col . '`';
		$op .= ' ' . $str;
		return trim( $op );
	}

    /**
     * Call from a child class.
     *
     * Only works when you setup $db_init_cols in child class
     * with the column you require.
     *
     * @param $column
     * @return string
     */
	public static function get_add_column_ddl( $column ) {

	    $_table = gp_esc_db_table( static::$table );
	    $_column = gp_esc_db_col( $column );

	    // $args IS sql, there is no need to escape it. Escaping WILL break it, in some cases.
	    $args = static::$db_init_cols[$column];

	    return "ALTER TABLE $_table ADD $_column " . $args . ";";
    }

//
//	/**
//	 * @param $table
//	 * @param $query
//	 *
//	 * @return bool
//	 */
//	public static function db_init_create_table_sql( $table, $query ){
//
//		if ( $table && $query ) {
//			$q = '';
//			$q .= 'CREATE TABLE IF NOT EXISTS `' . gp_esc_db_table( $table ) . '` (';
//			$q .= trim( $query );
//			$q .= ');';
//		} else {
//			echo 'invalid create table';
//			exit;
//		}
//
//		print_dev_alert( $q );
//
//		queue_dev_alert( 'CREATE_TABLE', $q );
//
//		try{
//			$db = get_database_instance();
//			$st = $db->pdo->prepare( $q );
//			$db->pdo->errorInfo();
//			return $st->execute();
//		} catch ( Exception $e ) {
//			queue_dev_alert( 'CREATE_TABLE_EXCEPTION', $e );
//		}
//	}

    /**
     * @param $class
     * @return mixed
     */
    public static function db_init_create_table_via_class_name( $class ) {
        return call_user_func([$class, 'db_init_create_table_if_not_exists']);
    }

	/**
	 * @see db_init_create_table_via_class_name
	 */
	public static function db_init_create_table_if_not_exists(){
		$cols = static::get_db_init_columns();
		$args = static::get_db_init_args();
		return sql_create_table_if_not_exists( static::get_table(), $cols, $args );
	}

    /**
     * @param $key
     * @param null $label
     * @return string
     */
	public function get_simple_form_textarea( $key, $label = null ) {

		$v = $this->get( $key );

		return get_form_textarea( array(
			'name' => gp_test_input( $key ),
			'label' => $label === null ? gp_test_input( $key ) : $label,
			'value' => $v,
		));
	}

	/**
	 * weird function to be in this place but makes our job
	 * easier for admin sections when come columns are editable for single
	 * rows.
	 */
	public function get_simple_form_input( $key, $args = array() ){

		// not likely manually overriding the label very often
		$label = gp_if_set( $args,'label', gp_test_input( $key ) );

		// might add some brackets or something once in a while
		$label .= gp_if_set( $args, 'after_label', '' );

		return get_form_input( array(
			'name' => gp_test_input( $key ),
			'label' => $label,
			'value' => $this->get_and_clean( $key ),
		));
	}

	/**
	 * Checks to see if the object should be able to be deleted
	 * when viewing it from the admin single edit page, then if it can,
	 * tries to delete it, which may still fail due to sql constraints.
	 */
	public function handle_admin_single_edit_page_deletion_request( &$error_msg ){

		$args = $this->get_admin_archive_page_args();

		$do_delete_on_single = gp_if_set( $args, 'do_delete_on_single' );

		if ( ! $do_delete_on_single ) {
			$error_msg = "Not authorized. See ->get_admin_archive_page_args for this object.";
			return false;
		}

		$deleted = $this->delete_self_if_has_singular_primary_key();

		if ( $deleted ) {
			return true;
		}

		$error_msg = "Deletion probably failed. Try re-loading the page to confirm";
		return false;
	}

    /**
     * @param $userdata
     * @return Component_Builder
     * @throws Exception
     */
	public function get_admin_archive_page_component_builder( $userdata ) {

		$component = new Query_Components( static::$table, false );

		if ( static::$fields ) {
			foreach ( static::$fields as $field ) {

				// this does not currently support empty values which is pretty ridiculous but its because
				// we have some forms with method="get" that post empty values into the URL and this would break them
				if ( isset( $userdata[$field] ) && $userdata[$field] ){
					$component->builder->add_to_self( $component->simple_equality( $field, $userdata[$field] ) );
				}

				$not_equal_to = $field . GET_VAR_NOT_EQUAL_TO_APPEND;

				// this might not work with all data types and i dont plan on using it much
				// for the product imports, after running an import we'll link to the rims or tires page showing all other products
				// found in other imports. this is mostly useless when an admin imports with many files, but when using a master list its useful.
				if ( isset( $userdata[$not_equal_to] ) ){
					$component->builder->add_to_self( $component->simple_relation( $field, $userdata[$not_equal_to], '%s', '<>' ) );
				}
			}
		}

		return $component->builder;
	}

	/**
	 * Note: some admin pages have their own specific page to
	 * edit a single row, therefore this only works most of the time.
	 *
	 * @return string
	 */
	public function get_admin_single_page_url(){
		return get_admin_single_edit_link( static::$table, (int) $this->get_primary_key_value() );
	}

	/**
	 * Note: some admin pages have their own specific page to
	 * edit a single row, therefore this only works most of the time.
	 *
     * @param array $args
     * @return string
     * @throws Exception
     */
	public function get_admin_link_to_self( $args = array() ){

		$pk = $this->get_primary_key_value();

		if ( ! isset( $args['text'] ) ) {
            $args['text'] = (int) $pk . ' (edit)';
        }

		return get_anchor_tag_simple( $this->get_admin_single_page_url(), (int) $pk . ' (edit)', $args );
	}

    /**
     * @param bool $empty_on_error
     * @return array
     */
	public static function show_indexes( $empty_on_error = true ){

        $db = get_database_instance();
        $tbl = gp_esc_db_table( static::$table );

        try{
            $sql = "SHOW INDEXES FROM $tbl";
            $ret = $db->get_results( $sql );
        } catch( Exception $e ) {

            if ( $empty_on_error ) {
                return array();
            }

            $ret = [ [ 'exception' => $e->getMessage() ] ];
        }
        return $ret;
    }

    /**
     * Printing this in admin somewhere. It runs a bunch of tests to try
     * to ensure proper configuration of DB objects and looks for data
     * inconsistencies between the code and structure of the database.
     *
     * Some tests include:
     * - Ensure column in database are identical to self::$fields
     * - Ensure self::$fields is the same as the array keys of
     *
     */
    public function get_configuration_test_debug_html(){

        ob_start();

        $fields = static::$fields;
        $cols = static::$db_init_cols;
        $table = static::$table;
        $pk = static::$primary_key;

        // create an instance with empty data which allows better access to some things
        $self = static::create_empty_instance_from_table( $table );

        echo '<h2>' . $table . '</h2>';
        $diff = array_diff( $fields, array_keys( $cols ) );

        if ( $diff ) {
            echo '<p>WARNING... fields of model object do not match the database initialization arguments.</p>';
            echo '<pre>' . print_r( $fields, true ) . '</pre>';
            echo '<pre>' . print_r( $cols, true ) . '</pre>';
            echo '<pre>' . print_r( $diff, true ) . '</pre>';
        } else {
            // echo '<p>no issues...</p>';
        }

        $indexes = array_map( 'get_object_vars', $self::show_indexes() );

        // checking just tires and rims on this one...
        // we could print all indexes for all tables but I prefer to try to print only things that are possible errors,
        // so that its obvious when errors occur.
        if ( in_array( $table, [ DB_tires, DB_rims ] ) ) {

            // check if an index exists on the part number column
            if ( in_array( 'part_number', array_keys( array_column( $indexes, 'Column_name' ) ) ) ) {
                echo wrap_tag( 'An index was found on the part number column (as expected). Dumping all indexes for confirmation.' );
                echo render_html_table_admin( false, $indexes );
            } else {
                echo wrap_tag( 'NO INDEX FOUND ON PART NUMBER COLUMN, this will make many operations, including imports, VERY slow.' );
            }
        }

        $get_arbitrary_database_row_array = function() use ( $table, $pk ){
            $db = get_database_instance();
            $results = $db->get_results( "SELECT * FROM $table ORDER BY $pk ASC LIMIT 0, 1" );
            $ret = gp_if_set( $results, 0, null );
            $ret = $ret ? gp_make_array( $ret ) : $ret;
            return $ret;
        };

        $db_row = $get_arbitrary_database_row_array();

        if ( ! $db_row ) {
            echo wrap_tag( 'Table is empty - so not comparing table columns to fields in class.' );
        } else {

            $db_row_columns = array_keys( $db_row );

            $diff_between_fields_and_db_row = array_diff( $fields, $db_row_columns );
            if ( $diff_between_fields_and_db_row ) {
                echo wrap_tag( 'An arbitrary row from the database has columns which are different from the fields configured in the class.' );
                echo nl2br( "-----------------------fields \n" );
                echo '<pre>' . print_r($fields, true) . '</pre>';
                echo nl2br( "-----------------------db_row_columns \n" );
                echo '<pre>' . print_r($db_row_columns, true) . '</pre>';
                echo nl2br( "-----------------------diff \n" );
                echo '<pre>' . print_r($diff_between_fields_and_db_row, true) . '</pre>';
            }
        }

        return ob_get_clean();
    }

    /**
     * @param $value
     * @return false|string
     */
    static function encode_json_arr( $value ) {
        $value = is_array( $value ) ? $value : [];
        return json_encode( $value, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE );
    }

    /**
     * @param $value
     * @return array|mixed
     */
    static function decode_json_arr( $value ) {
        if ( ! $value ) {
            return [];
        }

        return json_decode( $value, JSON_INVALID_UTF8_SUBSTITUTE );
    }

    /**
     * @param $value
     * @return false|string
     */
    static function encode_json_obj( $value ) {
        $value = is_array( $value ) ? $value : [];
        return json_encode( $value, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE );
    }

    /**
     * @param $value
     * @return array|mixed
     */
    static function decode_json_obj( $value ) {
        if ( ! $value ) {
            return [];
        }
        return json_decode( $value, JSON_INVALID_UTF8_SUBSTITUTE );
    }

    /**
     * Queries and updates the database. Not efficient in a large loop.
     *
     * @param $column
     * @param $callback
     * @param bool $is_indexed
     * @return bool
     * @throws Exception
     */
    function update_json_column_via_callback( $column, $callback, $is_indexed = true ) {
        if ( $is_indexed ) {
            $ex = self::decode_json_obj( $this->get( $column ) );
            $new_value = $callback( $ex );
            return $this->update_database_and_re_sync( [
                $column => self::encode_json_obj( $new_value )
            ] );
        } else {
            $ex = self::decode_json_arr( $this->get( $column ) );
            $new_value = $callback( $ex );
            return $this->update_database_and_re_sync( [
                $column => self::encode_json_arr( $new_value )
            ] );
        }
    }

    /**
     * Add file admin-templates/edit-archive-before/{table_name.php}
     *
     * Then call this method passing in the table an an array of database
     * columns. Be aware that each column you pass will have all its possible
     * values queried, so avoid columns that are likely to have too many
     * possible values (like part_number on tires/rims).
     *
     * @param $table
     * @param $columns
     * @return false|string
     */
    static function get_archive_page_filters_form( $table, $columns ){
        ob_start();

        ?>

        <div class="admin-section general-content">
            <form class="form-style-basic js-remove-empty-on-submit" method="get" action="<?php echo get_admin_archive_link( $table ); ?>">
                <?php


                echo get_hidden_inputs_from_array( get_array_except( $_GET, $columns ), true );

                echo '<div class="form-items inline">';

                foreach ( $columns as $column ) {
                    echo get_form_select_from_unique_column_values( $table, $column, get_user_input_singular_value( $_GET, $column ) );
                }

                echo '</div>'; // form-items

                echo '<p><button type="submit">Filter</button></p>';
                echo '<br>';
                echo get_admin_edit_page_possible_filters_text();
                echo '<br>';
                echo '<p><a href="' . get_admin_archive_link( $table ) . '">Reset</a></p>';
                ?>
            </form>
        </div>

        <?php

        return ob_get_clean();
    }
}
