<?php

/**
 * Class DB_User
 */
Class DB_Coupon extends DB_Table{

	protected static $primary_key = 'id';
	protected static $table = DB_coupons;

	/*
	 * An array of keys required to instantiate the object.
	 */
	protected static $req_cols = array();


    // db columns
    protected static $fields = array(
        'id',
        'coupon_code',
        'coupon_discount',
        'coupon_validity',
        'max_time_usable',
        'status',
        'created_at',
        'updated_at',
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
     * this is also used to determine whether a user with an email already exists (ie. on checkout)
     *
     * @param $email
     */
    public static function check_coupon_by_coupon_code( $coupon_code, $coupon_id = null) {

        $coupon_code = gp_force_singular( $coupon_code );

        if ( ! $coupon_code ) {
            return null;
        }

        $db = get_database_instance();
        $params = array();
        $q = '';
        $q .= 'SELECT * ';
        $q .= 'FROM ' . $db->coupons . ' ';
        $q .= 'WHERE LOWER(coupon_code) = :coupon_code ';
        $params[] = [ ':coupon_code', strtolower( $coupon_code ), '%s' ];


        //On edit check
        if($coupon_id){
            $q   .= ' AND id != :coupon_id ';
            $params[] = [ ':coupon_id', $coupon_id ];
        }

        $q .= ';';

        $results = $db->get_results( $q, $params );

        if ( count( $results ) > 0 ) {
            return true;
        }
        return false;
    }

}
