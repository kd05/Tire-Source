<?php

if ( ! cw_is_admin_logged_in() ) {
	exit;
}

page_title_is( 'Tax/Shipping' );

$msg = gp_get_global( 'postback_msg', '' );

// handle the updates...
include ADMIN_TEMPLATES . '/post-back/tax-shipping.php';

cw_get_header();
Admin_Sidebar::html_before();

$db = get_database_instance();
$p  = [];

$q = '';
$q .= 'SELECT *, regions.region_id AS region_id ';
$q .= 'FROM ' . $db->regions . ' AS regions ';
$q .= 'LEFT JOIN ' . $db->tax_rates . ' AS tax ON tax.region_id = regions.region_id ';
$q .= 'LEFT JOIN ' . $db->shipping_rates . ' AS shipping ON shipping.region_id = regions.region_id ';
$q .= 'GROUP BY regions.region_id ';
$q .= 'ORDER BY regions.country_code ASC, regions.province_code ASC ';
$q .= ';';

$regions = $db->get_results( $q, $p );

echo '<div class="admin-section general-content">';
echo '<h2>Tax/Shipping</h2>';
echo '<p>Edit all of your tax and shipping rates below for all Canadian or U.S. provinces.</p>';
echo '<p>All columns with an input box must have a value in them, otherwise for example, someone may get charged no taxes on their order.</p>';
echo '<p>Enter the tax rate as a number indicating a percent. For example, if the tax rate is 13.05%, just enter "13.05"</p>';
echo '<p>price_tire, price_rim, and price_mounted are in dollars. Enter something like "50", or "49.99".</p>';
echo '<p>When a user purchases mounting and balancing, they are charged the dollar amount under "price_mounted" for every pair of 1 tire and 1 rim that is mounted and balanced.</p>';
echo '<p>Items sold without a vehicle, or items with a vehicle but without mounting and balancing are treated the same, each tire is charged the dollar amount under "price_tire", and each rim under "price_rim".</p>';

if ( $msg ) {
	echo get_form_response_text( $msg );
}

echo '</div>';
echo '<br><br>';

echo '<form action="" method="post">';

echo '<input type="hidden" name="nonce" value="' . get_nonce_value( 'tax_shipping_post' ) . '">';
echo '<input type="hidden" name="form_submitted" value="1">';

$cols = false;
if ( $regions ) {
	foreach ( $regions as $i => $row ) {

		$row = gp_make_array( $row );
		$cols = $cols ? $cols : array_keys( $row );

		$region_id = get_user_input_singular_value( $row,  'region_id' );
		$country_code = get_user_input_singular_value( $row,  'country_code' );

		$tax_rate = get_user_input_singular_value( $row, 'tax_rate' );
		$price_tire = get_user_input_singular_value( $row, 'price_tire' );
		$price_rim = get_user_input_singular_value( $row, 'price_rim' );
		$price_mounted = get_user_input_singular_value( $row, 'price_mounted' );

		$price_tire = $price_tire ? $price_tire : 0;
		$price_rim = $price_rim ? $price_rim : 0;
		$price_mounted = $price_mounted ? $price_mounted : 0;

		// we used to hide tax rates for U.S. but now we are showing them.
		// if you decide U.S. gets free taxes, you'll have to also check the code handling checkouts and tax rates
		// i don't think it will be too hard to change, but simply removing fields from a form doesn't mean people get tax free.
		if ( true || $country_code === 'CA' ) {
			$row['tax_rate'] = '<input type="text" name="region[' . $region_id . '][tax_rate]" value="' . $tax_rate . '">';
		}

		$row['price_tire'] = '<input type="text" name="region[' . $region_id . '][price_tire]" value="' . $price_tire . '">';
		$row['price_rim'] = '<input type="text" name="region[' . $region_id . '][price_rim]" value="' . $price_rim . '">';
		$row['price_mounted'] = '<input type="text" name="region[' . $region_id . '][price_mounted]" value="' . $price_mounted . '">';

		$allow_shipping = gp_if_set( $row, 'allow_shipping' );
		$checked = $allow_shipping ? 'checked' : '';

		$row['allow_shipping'] = '<input type="checkbox" name="region[' . $region_id . '][allow_shipping]" value="1" ' . $checked . '>';

		$regions[$i] = $row;
	}
}

echo render_html_table( $cols, $regions, [ 'add_class' => 'admin-table' ] );

echo get_form_submit();

echo '</form>';

?>


<?php

Admin_Sidebar::html_after();
cw_get_footer();