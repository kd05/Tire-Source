<?php

/**
 * Class DB_User
 */
Class DB_User extends DB_Table{

	protected static $primary_key = 'user_id';
	protected static $table = DB_users;

	/*
	 * An array of keys required to instantiate the object.
	 */
	protected static $req_cols = array();

	// db columns
	protected static $fields = array(
		'user_id',
		'first_name',
		'last_name',
		'role',
		'email',
		'signup_date',
		'password',
		'unique_login_counter',
		'reset_key',
		'reset_expiry',
		'failed_logins',
		'locked_status',
	);

	/**
	 * @var array
	 */
	protected static $db_init_cols = array(
		'user_id' => 'int(11) unsigned NOT NULL auto_increment',
		'email' => 'varchar(255) default \'\'',
		'first_name' => 'varchar(255) default \'\'',
		'last_name' => 'varchar(255) default \'\'',
		'role' => 'varchar(255) default \'\'',
		'password' => 'varchar(255) default \'\'',
		'signup_date' => 'varchar(255) default \'\'',
		// this needs to change whenever a users password does,
		// or when you want to make the user logged out everywhere.
		'unique_login_counter' => 'int(11) default 1',
		'reset_key' => 'varchar(255) default \'\'',
		'reset_expiry' => 'varchar(255) default \'\'',
		'failed_logins' => 'int(11)',
		'locked_status' => 'varchar(255) default \'\'',
	);

	protected static $db_init_args = array(
		'PRIMARY KEY (`user_id`)'
	);

	protected $data;

	/**
	 * DB_User constructor.
	 *
	 * @param       $data
	 * @param array $options
	 */
	public function __construct( $data, $options = array() ){
		parent::__construct( $data, $options );
	}

	/**
	 * @param $email
	 */
	public static function count_users_with_email( $email ) {

		$db = get_database_instance();
		$params = array();

		$q = '';
		$q .= 'SELECT * ';
		$q .= 'FROM ' . $db->users . ' ';
		$q .= 'WHERE LOWER(email) = :email ';
		$params[] = [ ':email', strtolower( $email ), '%s' ];
		$q .= ';';

		$results = $db->get_results( $q, $params );

		$ret = $results ? count( $results ) : false;
		return $ret;
	}

	/**
	 * this is also used to determine whether a user with an email already exists (ie. on checkout)
	 *
	 * @param $email
	 */
	public static function create_instance_via_email( $email, $options = array() ) {

		$email = gp_force_singular( $email );

		if ( ! $email ) {
			return null;
		}

		$db = get_database_instance();
		$params = array();
		$q = '';
		$q .= 'SELECT * ';
		$q .= 'FROM ' . $db->users . ' ';
		$q .= 'WHERE LOWER(email) = :email ';
		$params[] = [ ':email', strtolower( $email ), '%s' ];
		$q .= ';';

		$results = $db->get_results( $q, $params );
		$row    = gp_if_set( $results, 0 );

		if ( ! $row ) {
			return null;
		}

		// a bit redundant, but if 2 users have the same email, this may more or less disable
		// both of them. obviously, we don't insert 2 users with the same email but...
		//  we're here now, and this is easy to check.
		if ( count( $results ) > 1 ) {
			return null;
		}

		$options = $options ? $options : array();
		$ret = static::create_instance_or_null( $row, $options );
		return $ret;
	}

	/**
	 * @return bool|mixed
	 */
	public function get_role(){
		return $this->get( 'role' );
	}

	/**
	 * @return bool
	 */
	public function is_administrator(){
		return $this->get_role() === 'admin';
	}

	/**
	 * You can pass in array, will return true if the user is of at least one
	 * of the given roles.
	 *
	 * Recommend not using this as a way to check if the user has no role,
	 * which is also a possibility (default users are like this).
	 *
	 * @param string|array $role
	 *
	 * @return bool
	 */
	public function is_of_role( $role ) {

		$array_of_roles_to_check = gp_is_singular( $role ) ? [ $role ] : $role;

		if ( ! is_array( $array_of_roles_to_check ) ) {
			throw_dev_error( "Invalid role check" );
			exit;
		}

		$users_single_role = $this->get_role();
		return in_array( $users_single_role, $array_of_roles_to_check );
	}

	/**
	 * have to also reset the users failed login counter otherwise they'll get locked
	 * pretty soon again.
	 */
	public function unlock_user(){
		$this->update_database_and_re_sync( array(
			'locked_status' => '',
			'failed_logins' => 0,
		), array() );
	}

	/**
	 * 0, 1, or 2
	 * @param $v
	 */
	public function set_locked_status( $v ) {

		// will default to empty string I suppose.. kind of the only purpose of this function
		$v = (int) $v;

		// important to user this function.....
		if ( ! $v ) {
			$this->unlock_user();
		} else {
			$this->update_database_and_re_sync( array(
				'locked_status' => $v,
			), array() );
		}
	}

	/**
	 * @return bool
	 */
	public function is_locked(){
		// 0, 1 and maybe 2
		$v = $this->get_locked_status();
		return (bool) $v;
	}

	/**
	 *
	 */
	public function get_locked_message(){

		switch( $this->get_locked_status() ) {
			case 1:
				$rp = '<a href="' . get_url( 'forgot_password' ) . '">reset password</a>';
				$after = ' Please use the ' . $rp . ' functionality to get back in.';
				$ret = 'Your account has been locked due to too many failed login attempts.' . $after;
				break;
			case 2:
				$ret = 'Your account has been locked.';
				break;
			default:
				$ret = '';
		}

		return $ret;
	}

	/**
	 * @return bool|int|mixed
	 */
	public function get_locked_status(){
		$v = $this->get( 'locked_status' );
		$v = (int) $v;
		return $v;
	}

}

function insert_user(){

}