<?php

/**
 * Takes in an Order ID, and a Supplier Slug,
 *
 * Generates email content to send to the suppliers asking them to ship
 * X items to X address.
 *
 * We will probably run this on checkout (after successful payment)
 * (once we have the order, and run another function to get all suppliers related to that order)
 *
 * collects the different types of items and then does something with them,
 * whether that is to render the items in html, or the email body content
 * to send to the supplier. Note that we accept a SLUG for the supplier
 * in the __construct, because we 100% need an order, but its quite possible
 * that the supplier does not exist in the database, even though it exists
 * in the values of "order_items.supplier". At the time of checkout,
 * the slug *should* exist, but we also have the ability to re-send supplier emails,
 * which means, no guarantee that the supplier exists.
 *
 * Class Supplier_Order_Items
 */
Class Supplier_Order_Items{

	/**
	 * Array of DB_Order_Items s.t. type === 'rim'
	 *
	 * @var array
	 */
	public $rims;

	/**
	 * Array of DB_Order_Items s.t. type === 'tire'
	 *
	 * @var array
	 */
	public $tires;

	/**
	 * Array of DB_Order_Items s.t. type === 'install_kit'
	 *
	 * ... and belong to a package that has rims, which has this as its supplier.
	 * i've commented on this elsewhere and won't go into much detail here, but
	 * its basically quite tricky to understand.
	 *
	 * @var array
	 */
	public $install_kits;

	// the objects derived from raw database results
	// unfortunately we need both of these at different times
	public $rim_objects = array();
	public $tire_objects = array();
	public $install_kit_objects = array();

	/** @var DB_Order  */
	public $order;
	public $supplier_slug;

	/**
	 * Supplier_Order_Items constructor.
	 *
	 * @param DB_Order    $order
	 * @param DB_Supplier $supplier
	 */
	public function __construct( DB_Order $order, $supplier_slug ){

		$this->supplier_slug = $supplier_slug;
		$this->order = $order;

		// these run quite a few queries..
		$this->rims = $this->order->get_rims_via_supplier( $this->supplier_slug );
		$this->tires = $this->order->get_tires_via_supplier( $this->supplier_slug );
		$this->install_kits = $this->order->get_install_kits_via_supplier( $this->supplier_slug );

		// Parse rim items
		$this->rim_objects = array_map( function( $row ){
			// if somehow, $db_item does not exist its better to run into an
			// error and have it logged than to screw up the emails sent to suppliers.
			$item = DB_Order_Item::create_instance_or_null( $row );
			assert( $item->get( 'type' ) === 'rim' );
			return $item;
		}, $this->rims );

		// Parse tire items
		$this->tire_objects = array_map( function( $row ){
			$item = DB_Order_Item::create_instance_or_null( $row );
			assert( $item->get( 'type' ) === 'tire' );
			return $item;
		}, $this->tires );

		// Parse install kit items
		$this->install_kit_objects = array_map( function( $row ){
			$item = DB_Order_Item::create_instance_or_null( $row );
			assert( $item->get( 'type' ) === 'install_kit' );
			return $item;
		}, $this->install_kits );
	}

	/**
	 * @param $items
	 */
	public static function convert_items_to_email_summary( $items ) {

		// failing silently in any way here is not a good thing.
		assert( is_array( $items ) );

		$ret = array_map( function( $item ){
			assert( $item instanceof DB_Order_Item );
			return $item->get_email_summary_text();
		}, $items );

		return $ret;
	}

	/**
	 * @param $type
	 * @param $to
	 */
	public function get_db_order_items( $type, $case = 'all' ) {

		// we're not storing the mount_balance items right now, so its not a valid type
		assert( in_array( $type, [ 'tire', 'rim', 'install_kit' ] ) );

		switch( $type ) {
			case 'tire':
				$base = $this->tire_objects;
				break;
			case 'rim':
				$base = $this->rim_objects;
				break;
			case 'install_kit':
				$base = $this->install_kit_objects;
				break;
			default:
				throw_dev_error( 'Invalid type: ' . $type );
				exit;
		}

		// its EXTREMELY IMPORTANT that..
		// "with_mount_balance" UNION 'without_mount_balance' ========= 'all'
		// otherwise, items are going to not get shipped, or get double shipped.
		switch( $case ) {
			case 'all':
				return $base;
			case 'with_mount_balance':
				$ret = array_filter( $base, function( $item ) {
					/** @var DB_Order_Item $item */
					return $item->should_item_be_sent_to_shop_for_mounting_and_balancing();
				});

				$ret = array_values( $ret );

				return $ret;
			case 'without_mount_balance':

				$ret = array_filter( $base, function( $item ) {
					/** @var DB_Order_Item $item */
					return ! $item->should_item_be_sent_to_shop_for_mounting_and_balancing();
				});

				$ret = array_values( $ret );

				return $ret;
			default:
				throw_dev_error( 'Invalid case: ' . $case );
				exit;
		}
	}

	/**
	 * tires/rims/kits are arrays of DB_Order_Items
	 *
	 * @param string $br
	 * @param        $tires
	 * @param        $rims
	 * @param        $kits
	 *
	 * @return string
	 */
	public function get_email_tires_rims_and_install_kit_text( $tires, $rims, $kits, $br = "\r\n" ){

		$tt = self::convert_items_to_email_summary( $tires );
		$rr = self::convert_items_to_email_summary( $rims );
		$kk = self::convert_items_to_email_summary( $kits );

		$arr = array();

		$br2 = $br . $br;

		if ( $tt ) {
			$arr[] = '-- Tires --';
			$arr[] = implode( $br2, $tt );
		}

		if ( $rr ) {
			$arr[] = '-- Rims --';
			$arr[] = implode( $br2, $rr );
		}

		// small thing, but lets show the hardware kits title if the order contains
		// rims or hardware kits, so that the rim supplies know to pay attention to the hardware kits
		// and get proper confirmation that some rims should be shipped without hardware kits.
		// however, some suppliers only sell tires so we won't include this information in that case.
		if ( $rr || $kk ) {

			$arr[] = '-- Hardware Kits (1 unit supplies 4 rims) (see below) --';
			$arr[] = $kk ? implode( $br2, $kk ) : 'No Hardware Kits';
		}

		return implode( $br2, $arr );
	}

}

/**
 * @param $order_id
 * @param $supplier
 * @param $to
 * @param $from
 * @param $subject
 * @param $content
 * @param $sent
 *
 * @return bool|string
 */
function track_order_supplier_email( $order_id, $supplier, $to, $from, $subject, $content, $sent ) {

	$db = get_database_instance();

	$email_id = $db->insert( DB_order_emails, array(
		'order_id' => $order_id,
		'supplier' => strip_tags( $supplier ),
		'to' => strip_tags( $to ),
		'from' => strip_tags( $from ),
		'subject' => strip_tags( $subject ),
		'content' => $content, // might be plain text might be html
		'date' => get_date_formatted_for_database(),
		'sent' => $sent ? 1 : 0
	), array(
		'sent' => $db->int,
		'order_id' => $db->int,
	));

	return $email_id;
}

/**
 * @param Supplier_Order_Items $obj
 */
function get_supplier_email_subject( Supplier_Order_Items $obj ) {
	return 'tiresource - Order ID ' . $obj->order->get( 'order_id' );
}

/**
 * DOES NOT SUPPORT LOCAL PICKUP. Woudln't be too hard to do, but local pickup is currently disabled/removed.
 *
 * @param Supplier_Order_Items $obj
 */
function get_supplier_email_content( Supplier_Order_Items $obj ) {

	$br = "\r\n";
	$br_2 = $br . $br;

	$op = '';

	$op .= 'Thank you for receiving our tiresource.COM order. For those of you that are using our shipping account for shipping please log into our shipping tool with the provided log in and password. For Wheel orders that have wheel hardware included please provide the required amount of chrome 6 spline tuner nuts or bolts with their respective tuner key. Only provide plastic hubcentric rings when needed (where the Hub bore and Wheel center bore are not the same). If you have any questions regarding this order please send them to "orders@email_removed.com".';
	$op .= $br_2;

	$date = $obj->order->get( 'order_date' );
	$dt = new DateTime( $date );
	$_date = $dt->format( 'M d, Y' );

	// in case of double sending emails, the supplier should be aware of the order number
	// also for obvious things like properly identifying an order if there is any issues.
	$op .= 'Order ID: ' . (int) $obj->order->get_primary_key_value();
	$op .= $br;
	$op .= 'Order Date: ' . $_date;

	$case_1 = 'without_mount_balance';
	$case_2 = 'with_mount_balance';

	$tires_1 = $obj->get_db_order_items( 'tire', $case_1 );
	$rims_1 = $obj->get_db_order_items( 'rim', $case_1 );
	$install_kits_1 = $obj->get_db_order_items( 'install_kit', $case_1 );

	$tires_2 = $obj->get_db_order_items( 'tire', $case_2 );
	$rims_2 = $obj->get_db_order_items( 'rim', $case_2 );
	$install_kits_2 = $obj->get_db_order_items( 'install_kit', $case_2 );

	$customer_address = $obj->order->get_shipping_address_summary_array( true, true, true );
	$company_address = $obj->order->get_click_it_wheels_address_summary_array();

	$ln = 50;
	$min = 5;
	$thing = '=';

	if ( $tires_1 || $rims_1 || $install_kits_1 ) {

		$op .= $br_2;
		$op .= email_title_thing( 'Send To Customer', $ln, $min, $thing );
		$op .= $br_2;

		$op .= implode( $br, $customer_address );
		$op .= $br_2;
		$op .= $obj->get_email_tires_rims_and_install_kit_text( $tires_1, $rims_1, $install_kits_1, $br );

		$op .= $br_2;
		$op .= email_title_thing( '', $ln, $min, $thing ) . '=';
	}

	if ( $tires_2 || $rims_2 || $install_kits_2 ) {

		$op .= $br_2;
		$op .= email_title_thing( 'Send To Click It Wheels', $ln + 6, $min, $thing );
		$op .= $br_2;

		$op .= implode( $br, $company_address );
		$op .= $br_2;
		$op .= $obj->get_email_tires_rims_and_install_kit_text( $tires_2, $rims_2, $install_kits_2, $br );

		$op .= $br_2;
		$op .= email_title_thing( '', $ln, $min, $thing )  . '=';
	}

	return $op;
}

/**
 * @param DB_Order $order
 * @param          $supplier_slug
 * @param          $to_text
 *
 * @return bool
 */
function send_single_supplier_email( DB_Order $order, $supplier_slug, &$to_text ) {

	$order_id = $order->get( 'order_id' );

	$supplier = DB_supplier::get_instance_via_slug( $supplier_slug );

	// passed by reference
	$used_fallback = null;

	// this might have comma's (we'll attempt to make this work properly)
	$to = get_supplier_email_with_admin_fallback( $supplier, $order->get( 'locale' ), $used_fallback );

	// we will log this value
	$to_text = $used_fallback ? '[fallback] ' . $to : $to;

	// object contains methods to help generate email
	$obj = new Supplier_Order_Items( $order, $supplier_slug );

	// addresses, from, subject, content
	$from = get_admin_email_from();
	$subject = get_supplier_email_subject( $obj );
	$content = get_supplier_email_content( $obj );

	try{

		// configure PHPMailer
		$mail = get_php_mailer_instance( true );

		// adds one or more email addresses allowing comma separated string
		$add_addresses_success = app_php_mailer_add_addresses( $mail, $to );

		if ( ! $add_addresses_success ) {
			$fb = get_admin_email_to( 'supplier_fallback' );
			$mail->addAddress( $fb );
			$to_text .= ' + extra fallback: ' . $fb;
		}

		$mail->setFrom( $from );
		$mail->addReplyTo( get_email_reply_to_address( 'suppliers' ), get_email_reply_to_name() );
		$mail->Body = $content;
		$mail->Subject = $subject;
		$mail->isHTML( false );

		// php mailer is just straight up echoing some output which breaks our stupid ajax
		// its not even an error or an exception, just echo. so.. our fn. will capture that
		// and maybe log it ?
		$sent = php_mailer_send( $mail );

	} catch ( Exception $e ) {
		// return false ?? lets just log that an exception occurred so we have some clue as to why
		$sent = false;
		$to_text = '[exception: ' . $e->getMessage() . '] ' . $to_text;
		// return false;
	}

	// Log the data into the DB
	track_order_supplier_email( $order_id, $supplier_slug, $to_text, $from, $subject, $content, $sent );

	return $sent;
}

/**
 * @param $order
 */
function send_all_supplier_order_emails( DB_Order $order ) {

	$suppliers = $order->get_unique_suppliers();
	$count_suppliers = is_array( $suppliers ) ? count( $suppliers ) : 0;

	$count_sent = 0;

	if ( $suppliers ) {
		foreach ( $suppliers as $supplier_slug ) {

			// ref
			$to_text = '';

			$sent = send_single_supplier_email( $order, $supplier_slug, $to_text );

			if ( $sent ) {
				$count_sent++;
			}
		}
	}

	$ret = array(
		'count_sent' => $count_sent,
		'count_suppliers' => $count_suppliers,
		'success' => $count_sent === $count_suppliers,
		'msg' => $count_sent . ' of ' . $count_suppliers . ' supplier emails sent successfully.',
	);

	return $ret;
}

/**
 * get the suppliers email. It is possible this is a comma separated list of emails.
 * Its also possible this value is junk. If the value is junk I don't think we will fall back,
 * because I prefer not to implode on comma and then validate each email.
 *
 * its not the end of the world. All emails to suppliers are logged. If an admin enters
 * an invalid supplier email, they'll know the email failed.
 *
 * The fallback exists because some suppliers may not exist anymore in the database
 * at the time of trying to send them an email, ... they exist in old data from the order items table,
 * when someone purchased an item from that supplier while they did exist in the database.
 *
 * @param      $db_supplier
 * @param bool $used_fallback
 *
 * @return bool|mixed|string
 */
function get_supplier_email_with_admin_fallback( $db_supplier, $locale, &$used_fallback = false ) {

	// send to fallback email if invalid locale is provided
	if ( ! app_is_locale_valid( $locale ) ) {
		$used_fallback = true;
		return get_admin_email_to( 'supplier_fallback' );
	}

	if ( $db_supplier && gp_is_singular( $db_supplier ) ) {
		$db_supplier = DB_Supplier::get_instance_via_slug( $db_supplier );
	}

	if ( $db_supplier && $db_supplier instanceof DB_Supplier ) {

		$email = $db_supplier->get_order_email_to( $locale );

		if ( $email ) {
			$used_fallback = false;
			return $email;
		}
	}

	$used_fallback = true;
	return get_admin_email_to( 'supplier_fallback' );
}

/**
 * ============= What does this do again ? ==============
 *
 * @param     $text
 * @param     $length
 * @param int $min
 */
function email_title_thing( $text, $length, $min = 3, $thing = '=' ) {

	$ret = trim( $text );
	$ret = $ret ? ' ' . $ret . ' ' : '';

	for ( $x = 0; $x < $min; $x++ ) {
	    $ret = $thing . $ret . $thing;
	}

	while( strlen( $ret ) <= $length ) {
		$ret = $thing . $ret . $thing;
	}

	return $ret;
}

