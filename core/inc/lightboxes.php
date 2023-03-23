<?php

define( 'LIGHTBOX_HTML_GLOBALS_INDEX', 'lightbox_html' );

/**
 * Provide the lightbox ID so that when we render the items later, we have the
 * option of only printing on lightbox per unique ID.
 *
 * The ID in this case should also be in the html, ie.
 *
 * <div class="lb-content" data-lightbox-id="{ID}">
 *
 * @param $lightbox_id
 * @param $html
 *
 * @return bool
 */
function queue_lightbox_html( $lightbox_id, $html ) {

	$arr = gp_get_global( LIGHTBOX_HTML_GLOBALS_INDEX, array() );

	if ( is_array( $arr ) ) {

	    if ( is_callable( $html ) ) {
	        $html = $html();
        }

		$arr[] = array(
            'id' => $lightbox_id,
            'html' => $html,
        );

		gp_set_global( LIGHTBOX_HTML_GLOBALS_INDEX, $arr );
		return true;
	}
	return false;
}

/**
 * I recommend filtering duplicate IDs.
 *
 * If two lightboxes are printed to the page with the same ID, the JS
 * will double the content in the resulting lightbox. We could fix this in
 * JS, but there's also no point in sending a ton of hidden content to the
 * client when there is no point for it. If two lightboxes have the same ID,
 * assume that they must have 100% identical content, because if not, then
 * you're doing it wrong. Therefore, if we include only the first and not the rest,
 * this should be the proper way.
 *
 * If an ID passed in is false like, we print the html anyways, but do not filter
 * duplicate IDs. Lightboxes should not have false-like IDs... but we still print the html.
 *
 * Lastly.. if duplicate IDs are present, we'll use the last item that was queued with that ID.
 * I think this makes much more sense than the first in case you need to override for some reason.
 *
 * @param bool $filter_repeated_ids
 *
 * @return string
 */
function get_queued_lightbox_html( $filter_duplicate_ids = true ){

	$arr = gp_get_global( LIGHTBOX_HTML_GLOBALS_INDEX, array() );

	$array_to_print = array();

	if ( is_array( $arr ) ) {
		foreach ( $arr as $k=>$v ) {

			$id = gp_if_set( $v, 'id' );
			$html = gp_if_set( $v, 'html' );

			if ( ! $html ) {
			    continue;
            }

            // if no ID is provided, still print the html.
            // not expecting this, but supporting it.
            if ( ! $id ) {
			    $array_to_print[] = $html;
			    continue;
            }

			// when the ID is repeated, the resulting array will use the last queued element.
            // but, be careful not to use the same array key as when we auto increment above.
			$array_to_print['__ids_not_false_like__' . $id] = $html;
		}
	}

	$ret = implode( "\r\n", $array_to_print );
    return $ret;
}

/**
 * To use the lightbox, make a trigger. Currently no function to make a trigger, simply give anything the class
 * "lb-trigger" along with 'data-for="your-lightbox-id"'. multiple triggers can be used on the same lightbox, and
 * triggers can be added to the page dynamically as well.
 *
 * Note: $args['add_class'] will end up adding a css class to the lightboxe's outer wrapper once cloned, not to
 * the outer wrapper below.
 *
 * @param $lightbox_id
 */
function get_general_lightbox_content( $lightbox_id, $content = '', $args = [] ) {

	$cls = gp_if_set( $args,'add_class', '' );

	// true by default
	$close_btn = gp_if_set( $args, 'close_btn', true );
	$data_close_btn = $close_btn ? '1' : '';

	// false by default
	$wrap_general_content = gp_if_set( $args, 'wrap_general_content', false );

	$op = '';
	$op .= '<div class="lb-content" data-lightbox-id="' . $lightbox_id . '" data-close-btn="' . $data_close_btn . '" data-lightbox-class="' . $cls . '">';
	$op .= '<div class="lb-content-2">';
	$op .= $wrap_general_content ? wrap( $content, '<div class="general-content">', '</div>' ) : $content;
	$op .= '</div>';
	$op .= '</div>'; // lb-content-inner

	return $op;
}

/**
 * @param $lightbox_id
 *
 * @param array $vehicle_lookup_args
 * @param null $vehicle
 * @return string
 * @throws Exception
 */
function get_change_vehicle_lightbox_content( $lightbox_id, $vehicle_lookup_args = array(), $vehicle = null ) {

	if ( ! isset( $vehicle_lookup_args[ 'title' ] ) ) {
		$vehicle_lookup_args[ 'title' ] = 'Find Your Vehicle';
	}

	$ret = get_general_lightbox_content( $lightbox_id, get_vehicle_lookup_form( $vehicle_lookup_args, $vehicle ), array(
		'add_class' => 'change-vehicle',
	));

	return $ret;

	//	$op = '';
	//	$op .= '<div class="lb-content" data-lightbox-class="change-vehicle" data-lightbox-id="' . gp_test_input( $lightbox_id ) . '">';
	//	$op .= '<button class="css-reset close-btn lb-close"><i class="fa fa-times"></i></button>';
	//	$op .= get_vehicle_lookup_form( $vehicle_lookup_args, $vehicle );
	//	$op .= '</div>';
	//
	//	return $op;
}

/**
 * @return string
 */
function get_fitment_tooltip_html(){
	$op = '';
	$op .= '<h2>OEM Fitments</h2>';
	$op .= '<p>These are fitments that you would find on the information sticker inside your door jamb.  Please choose the size option that came with your vehicle if you are looking for the same fitment.  You can also choose other size options within this same list to change your size so you don\'t need to select any sizes in the <strong>Aftermarket Fitments</strong> field.</p>';
	$op .= '';
	return $op;
}

/**
 * @return string
 */
function get_sub_size_tooltip_html(){
	$op = '';
	$op .= '<h2>Aftermarket Fitments</h2>';
	$op .= '<p>These are other recommended sizes that also fit your vehicle without changing your overall tire diameter.  These sizes are commonly used in aftermarket fitments.  Please choose the size option that you would like to upgrade to.</p>';
	$op .= '';
	return $op;
}

/**
 * @param string $title
 */
function get_warranty_policy_html( $title = '' ) {

	ob_start();

	$title = $title === true ? 'Warranty Policy' : $title;

	// we use title when in the lightbox context
	if ( $title ) {
		echo '<h1>' . $title . '</h1>';
	}

	?>
	<p>All products sold by tiresource.COM have a manufacturer’s limited warranty extended to the original purchaser to cover conditions that are reasonably considered to have been within the manufacturers’ control. Manufacturer warranty does not apply to wheels that have corrosion or discoloration to finish due to prolonged salt, brake dust or harsh wheel cleaning product exposure.</p>
	<?php

	return ob_get_clean();
}


/**
 * @param string $title
 *
 * @return string
 */
function get_shipping_policy_html( $title = '' ){

	ob_start();

	$title = $title === true ? 'Shipping Policy' : $title;

	// we use title when in the lightbox context
	if ( $title ) {
		echo '<h2 class="like-h1">' . $title . '</h2>';
	}

	if ( DISABLE_LOCALES ) {
		$s = 'We offer Free Shipping across Canada, except that we do not ship to the Yukon Territories, Northwest Territories or Nunavut.';
	} else {
		$s = 'We offer Free Shipping across Canada and the United States, except that we do not ship to the Yukon Territories, Northwest Territories or Nunavut.';
	}
	?>
	<p><?php echo $s; ?> The amount of time required to process your order and ship it depends on the product purchased, if the product is in stock, the warehouse it is coming from and where you are located. During our busiest seasons (fall and spring), order processing time may take up to an additional 5-7 business days. If a product ordered is not in stock and needs to be sourced out, please add 7-10 business days.</p>
	<ul>
		<li>Tires or Wheels - We expect to ship in 2-5 business days.</li>
		<li>Wheel and Tire Package - We expect to ship in approximately 3 - 5 business days.</li>
	</ul>
	<p>Unfortunately, there are some instances where the order can be delayed beyond the above terms. We will contact you by email with any updates. You can also check the <a href="<?php echo get_url( 'account' ); ?>">status</a> of your order online with the tracking number that will be provided to you.</p>
	<?php
	return ob_get_clean();
}

/**
 * @param string $title
 *
 * @return string
 */
function get_return_policy_html( $title = '' ){

	ob_start();

	$email = '<a href="mailto: returns@email_removed.com">returns@email_removed.com</a>';

	$title = $title === true ? 'Return/Exchange Policy' : $title;

	// we use title when in the lightbox context
	if ( $title ) {
		echo '<h2 class="like-h1">' . $title . '</h2>';
	}

	?>

	<p>tiresource.COM wants you to be completely satisfied with your purchase. If you have any issues with the products you’ve received please contact us using the “Let’s Get In Touch” section at the bottom of our home page and our team of experts will work with you to find a solution. Applications for a return may be e-mailed to <?= $email; ?>.  Please attach as many images or videos of your issue as possible and ensure that all original packaging has been held.</p>
	<p>Our Return Policy is simple: You may return any wheel or tire within 30 days of your purchase if the product(s) is deemed defective or does not fit properly.</p>
	<h2>Damaged/Incorrect Items</h2>
	<p>If you have received items that appear to be damaged or incorrect, please send an e-mail to <?= $email; ?> and include images of what you have received. One of our customer service reps will be assigned to your case and will work to get the appropriate items returned and replaced right away.  Light scratches or abrasions on the inside barrel, inner lip or back of spokes are not considered damaged or defective.</p>
	<h2>Returning New Products</h2>
	<p>tiresource.COM wants you to be completely satisfied with your purchase. Our Return Policy is simple: you may return any brand-new product, with original packaging within 30 days of your purchase.</p>
	<p><em>Please make sure you always test-fit your wheel before mounting a tire (unless we have mounted the tires for you) in the front and rear of your vehicle, this way you can be easily helped if there is a fitment issue. tiresource.COM requests that images be provided related to the fitment issue, in order to validate the claim with the manufacturer (ex: image of brake contact point). tiresource.COM will absorb all shipping charges incurred on returns related to fitment.</em></p>
	<p>Any product returned on the basis of aesthetic value alone is subject to an exchange only.  Products must not be damaged or used in any way or the return will be declined. All returned products are fully inspected upon receipt, and refunds are subject to decline based on the condition of the returned items. Note that as part of our quality control practice, prior to any shipment of wheels or tires, products are fully inspected to ensure and maintain the high quality of products. Products returned may be subject to a restocking fee of up to 20%. The client is also responsible for shipping the products back to us in all original packaging. </p>
	<p>If you would like to make a return or exchange, you must contact us and obtain approval. tiresource.COM reserves the rights to ask for images corroborating any claims of in-correct fitment. Any returns that do not receive prior approval will be refused and returned to the customer. All items must be returned with receipt and waybill, in the original state in which they had been received.</p>
	<p>Returns will not be accepted on items that are:</p>
	<ul>
		<li>Without original packaging</li>
		<li>Used or damaged</li>
		<li>Mounted and balanced (unless we have mounted them for you)</li>
		<li>Returned more than 30 days after purchase</li>
	</ul>
	<p>Note we are not responsible for any damage that may occur during shipment of the items during return. For your own protection, you may wish to send your return by insured parcel post or courier. Also, if we made an error in order processing or shipping, we will take care of the return and its respective charges.</p>
	<p>Please send all returns to the address specified after approval. All refunds will be credited to the account used during purchase (Credit Card). All returned items must be fully inspected before being credited. We will make every effort to process your refund quickly and you will be notified of your refund by e-mail.</p>

	<?php

	return ob_get_clean();
}

/**
 * @param string $title
 *
 * @return string
 */
function get_fitment_policy_html( $title = '' ){
	ob_start();

	$title = $title === true ? 'Fitment Policy' : $title;

	// we use title when in the lightbox context
	if ( $title ) {
		echo '<h2 class="like-h1">' . $title . '</h2>';
	}

	?>
	<h2>Guaranteed Fitment Policy</h2>
	<h3>Our Commitment</h3>
	<p></p>
	<p>All shipped orders are verified by our tire and wheel experts for Guaranteed Fitment, unless you’ve opted out of our Guaranteed Fitment.</p>
	<h3>How We Review Your Order</h3>
	<p>tiresource.COM goes through many steps to ensure that all orders we ship will fit your described vehicle.</p>
	<p>To the best of our knowledge, the tiresource.COM vehicle, wheel, tire and tire and wheel package selectors are accurate. Due to the possibility of typographical errors, modified vehicles, wear of vehicle, age of vehicle, or other factors, our proprietary selectors may not always be 100% accurate. Our vehicle database is sourced and verified by third party and to the best of our knowledge is accurate and up to date.</p>
	<p>Please be aware that if you are installing aftermarket wheels, and/or changing tire size you are customizing your vehicle; therefore, the products you order from tiresource.COM may not fit exactly the same as the factory settings.</p>
	<p><strong>If the order is approved for our 100% Guaranteed Fitment and the product doesn’t fit we will offer you a replacement or a full refund, including shipping charges (*some conditions apply).</strong></p>
	<h3>To qualify for our 100% Guaranteed Fitment the following conditions must be met:</h3>
	<div class="box">
		<ul>
			<li><strong>You must provide your vehicle information.</strong> The selected vehicle information must be accurate and complete. It is the customers’ responsibility to provide accurate and complete vehicle information.</li>
			<li><strong>The vehicle must be unmodified and has all original factory settings.</strong> Please note, older vehicles may no longer have factory parameters and therefore, may not qualify for our 100% Fitment Guarantee.</li>
			<li><strong>You are purchasing a full set of tires, wheels or tires and wheels package.</strong> Less than a set (3 or less) does not qualify for our 100% Fitment Guarantee.</li>
		</ul>
	</div>
	<h3>We guarantee that the products you have purchased will fit your vehicle, meaning the following:</h3>
	<p><strong>Rims: </strong>Rims will bolt on and will not interfere on OEM brakes, suspension, or body components. Rims that require hub centric rings or different wheel nuts/bolts are still considered a correct fitment.</p>
	<p><strong>Tires: </strong>Will fall within the approved overall tire diameter, load rating, and speed rating (winter tires are approved to have a lower speed rating).</p>
	<p><strong>Tire and Wheel Packages: </strong>The package will bolt on and will not interfere on OEM brakes, suspension, or body components. Rims that require hub centric rings or different wheel nuts/bolts are still considered a correct fitment.</p>
	<p>When you place an order, you must select your vehicle information from the drop-down menus presented to you in the search by vehicle areas. Please note, the description of the vehicle may differ from your markings on the car. Always refer to your user manual for a full description of your vehicle. The failure to choose the correct vehicle may affect the selection of the product that fits your vehicle and your claim for fitment guarantee may be rejected.</p>
	<p><img src="<?php echo get_image_src( 'vehicle-specs.png' ); ?>" title="Tire and Loading Information"></p>
	<h3>Driver's Side Door Sticker Example</h3>
	<p>When upsizing or downsizing tires/wheels always refer to your original equipment tire size. Correct tire and wheel information can usually be found on the inside of the driver’s side door on a sticker. It is the responsibility of the customer to verify that this information is correct prior to placing the order in order to qualify for Guaranteed Fitment.</p>
	<p><strong>If you choose not to provide your vehicle information, or chose to skip vehicle check, when placing an order our Guaranteed Fitment is not applicable.</strong></p>
	<p>Once we receive your order our first priority is to check fitment. This is done by our fitment team that employs various methods and manufactures databases to check fitment. We may come to the conclusion that the products you have ordered will not fit your described vehicle. In this case, you will be notified and will be provided with alternative options. If you still feel confident about your original purchase and want to proceed with the order as is, we will ship it to you with a disclaimer to that effect and such order is not covered by our Guaranteed Fitment Policy.</p>
	<p>Since we don’t have your vehicle present to physically qualify it for fitment we rely on our collective experience and expertise, and consult numerous fitment sources to make approve fitment. Due to mechanical wear and tear of a vehicle, tires and/or wheels may not fit as intended. It is impossible to take these factors into account when verifying fitment. Therefore, on rare occasions when a customer claims the product does not fit we reserve the right to refuse such a claim based on our previous experience with the same vehicle and/ or manufacturer’s fitment data available to the public. In such cases we will request pictures showing the issues with fitment and will assess the claim on a case by case basis.</p>
	<h3>The 100% Fitment Guarantee claim will be denied in these circumstances:</h3>
	<div class="box">
		<ul>
			<li>The vehicle description chosen or described by the customer is not complete, inaccurate or wrong.</li>
			<li>The vehicle is not listed when order is placed.</li>
			<li>The vehicle is listed on the manufacturer’s website as an approved fitment but the customer or the installer claims the product does not fit.</li>
			<li>The manufacturer representative has qualified the product as an approved fit for your described vehicle but the customer or the installer claims that the product doesn’t fit.</li>
			<li>The order was not approved for 100% Guaranteed Fitment and the order was shipped with the disclaimer to that effect</li>
			<li>The order was approved as a fitment based on the previously filled orders for exact same vehicle and product shipped (we keep such data) but the product didn’t fit due to the mechanical wear and tear.</li>
		</ul>
	</div>
	<p>*Please note, you are responsible for a safe keeping and handling of the order that is under our Guaranteed Fitment policy. If the order is returned and we determine that the product was damaged, this voids the Guaranteed Fitment policy. The refund will then be issued as per our return/refund policy.</p>
	<?php
	return ob_get_clean();
}
