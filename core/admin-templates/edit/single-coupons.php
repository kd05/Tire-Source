<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

$coupon_id = gp_if_set( $_GET, 'pk' );
$table = gp_if_set( $_GET, 'table' );

/** @var DB_User $coupon */
$coupon = DB_Coupon::create_instance_via_primary_key( $coupon_id );


if ( ! $coupon ) {
	echo 'Invalid user';
	goto end_file;
} else {
	// Post back file
	gp_set_global( '_coupon', $coupon );
	include CORE_DIR . '/admin-templates/post-back/edit-single-coupons.php';

	if ( ! $coupon ) {
		echo 'Coupon does not exist.';
		goto end_file;
	}
}


echo '<div class="admin-section general-content">';
echo render_html_table_admin( false, [ $coupon->to_array() ], [ 'title' => 'Coupon Details' ] );
echo '</div>';

echo '<div class="admin-section general-content">';

$actions = array();


echo '<form method="post" action="" class="form-style-basic">';

echo '<input type="hidden" name="form_submitted" value="1">';
echo get_nonce_input( 'edit_single_coupons' );

echo get_form_header( 'Edit Coupon' );
echo '<br><br>';

echo '<div class="form-items">';

$response = gp_get_global( 'post_back_response' );
if ( $response ) {
	echo get_form_response_text( $response );
}



// first
echo   get_form_input( array(
    'label' => 'Coupon Code*',
    'name' => 'coupon_code',
    'id' => 'ac-coupon-code',
    'value' => $coupon->get( 'coupon_code' ),
) );


?>


    <div class="item-wrap type-text item-coupon_discount">
        <div class="item-label">
            <label for="ac-coupon-discount">Coupon Discount (%)*</label>
        </div>
        <div class="item-inner">
            <input type="number" min="0" max="100" name="coupon_discount" id="ac-coupon-discount" value="<?php echo $coupon->get( 'coupon_discount' ); ?>">
        </div>
    </div>
       
    
    <div class="item-wrap type-text item-coupon_validity">
        <div class="item-label">
            <label for="ac-coupon-validity">Coupon Validity*</label>
        </div>
        <div class="item-inner">
            <input class="date-picker-field" type="text" name="coupon_validity" id="ac-coupon-validity" value="<?php echo $coupon->get( 'coupon_validity' ); ?>">
        </div>
    </div>
    
    
   <div class="item-wrap type-text item-max_time_usable">
        <div class="item-label">
            <label for="ac-max-time-usable">Max Used*</label>
        </div>
        <div class="item-inner">
            <input type="number" min="0" name="max_time_usable" id="ac-max-time-usable" value="<?php echo $coupon->get( 'max_time_usable' ); ?>">
        </div>
    </div>


    <div class="item-wrap type-checkbox item-status">
        <div class="item-label">
            <label for="ac-max-time-usable">Status*</label>
        </div>
        <div class="item-inner">
            <input type="radio"  name="status" id="ac-make_admin" value="1" <?php echo $coupon->get('status') == "1" ? "checked" : "" ;?>>
            <label for="ac-status">Active</label>

            &nbsp;&nbsp;
            <input type="radio" name="status" id="su-make_admin" value="0" <?php echo $coupon->get('status') == "0" ? "checked" : "" ;?>>
            <label for="ac-status">InActive</label>
        </div>
    </div>
    
<?php

echo get_form_submit();

echo '</div>'; // form-items

echo '</form>';

echo '</div>';

end_file: