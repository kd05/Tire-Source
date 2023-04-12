<?php


/**

 * @param      $coupon_code
 * @param      $coupon_discount
 * @param      $coupon_validity
 * @param      $max_time_usable
 * @param      $status
 *
 * @return bool|string
 * @throws Coupon_Exception
 */
function insert_coupon( $coupon_code, $coupon_discount, $coupon_validity, $max_time_usable, $status) {

    $coupon_code = gp_force_singular( $coupon_code );
    $coupon_code = trim( $coupon_code );

    $coupon_discount = gp_force_singular( $coupon_discount );
    $coupon_discount = trim( $coupon_discount );

    $coupon_validity = gp_force_singular( $coupon_validity );
    $coupon_validity = trim( $coupon_validity );

    $max_time_usable = gp_force_singular( $max_time_usable );
    $max_time_usable = trim( $max_time_usable );

    if (!$coupon_code || !$coupon_validity) {
        throw new Coupon_Exception( 'Please Fill All Mandatory Fields' );
    }

    if(!($coupon_discount > 0 && $coupon_discount <= 100)){
        throw new Coupon_Exception( 'Invalid Discount (Must be between 1 - 100).' );
    }

    $check_coupon_code = DB_Coupon::check_coupon_by_coupon_code( $coupon_code );
    if($check_coupon_code){
        throw new Coupon_Exception( 'This coupon could already exists.' );
    }



    $coupon_id = insert_coupon_direct( $coupon_code, $coupon_discount, $coupon_validity, $max_time_usable, $status);

    if ( ! $coupon_id ) {
        throw new Coupon_Exception( 'The coupon could not be created.' );
    }

    return $coupon_id;
}




/**

 * @param      $coupon_code
 * @param      $coupon_discount
 * @param      $coupon_validity
 * @param      $max_time_usable
 * @param      $status
 *
 * @return bool|string
 * @throws Coupon_Exception
 */
function insert_coupon_direct( $coupon_code, $coupon_discount, $coupon_validity, $max_time_usable, $status) {
    $db = get_database_instance();
    // no cleaning of data at this point
    $coupon_id = $db->insert( DB_coupons, array(
        'coupon_code' => trim( $coupon_code ),
        'coupon_discount' => $coupon_discount,
        'coupon_validity' => $coupon_validity,
        'max_time_usable' => $max_time_usable,
        'status' => $status,
    ) );

    if ( $coupon_id ) {
        return $coupon_id;
    }
    return false;
}