<?php
/**
 * @see ADMIN_TEMPLATES . '/order.php'
 */

/** @var DB_Order $order */
$order = $order;

$email_data = get_emails_from_order_id( $order->get_primary_key_value() );
$email_data = gp_force_array( $email_data );

$_email_data = array_map(function($r){
	$obj = DB_Order_Email::create_instance_or_null( $r );
	return $obj->to_array_for_admin_tables();
}, $email_data );

echo render_html_table_admin( null, $_email_data, [ 'title' => 'Supplier Emails Log' ] );

$suppliers = $order->get_unique_suppliers();

$suppliers_overview = array();

if ( $suppliers ) {
	$count = 0;
	foreach ( $suppliers as $sup ) {
		$count++;

		$obj = new Supplier_Order_Items( $order, $sup );
		// $order_id = $order->get( 'order_id' );

		$supplier_object = DB_Supplier::get_instance_via_slug( $sup );

		$lb_id = 'preview-' . $count;
		$lb_content = nl2br( get_supplier_email_content( $obj ) );
		$preview = '';
		$preview .= get_general_lightbox_content( $lb_id, $lb_content, [ 'add_class' => 'general-lightbox email-preview alert-response-msg embed-page' ]);
		$preview .= '<button class="css-reset lb-trigger" data-for="' . $lb_id . '">Preview</button>';

		$send = get_simple_button_ajax_form( array(
			'confirm' => 'Are you sure you want to send an email to ' . gp_test_input( $sup ) . '?',
			'add_class' => 'supplier-resend',
			'ajax_action' => 'supplier_email',
			'inputs' => array(
				'order_id' => $order->get( 'order_id' ),
				'supplier' => $sup,
			),
			'btn_text' => '(Re)Send Email',
		));

		$suppliers_overview[] = array(
			'supplier' => get_admin_single_edit_anchor_tag( DB_suppliers, $sup ),
			'order_email' => $supplier_object ? $supplier_object->get_order_email_to( $order->get( 'locale' ) ) : '',
			'tires' => $obj->tires ? implode_comma( gp_array_column( $obj->tires, 'part_number' ) ) : '',
			'rims' => $obj->rims ? implode_comma( gp_array_column( $obj->rims, 'part_number' ) ) : '',
			'install_kits' => $obj->install_kits ? implode_comma( gp_array_column( $obj->install_kits, 'part_number' ) ) : '',
			'preview' => $preview,
			'send_email' => $send,
		);
	}
}

echo render_html_table_admin( null, $suppliers_overview, [ 'title' => 'Supplier Breakdown' ] );

// Send All Emails Button
echo get_simple_button_ajax_form( array(
	'confirm' => 'Are you sure you want to send emails to all suppliers?',
	'add_class' => 'supplier-resend-all',
	'ajax_action' => 'supplier_email',
	'inputs' => array(
		'order_id' => $order->get( 'order_id' ),
		'send_to_all' => 1,
	),
	'btn_text' => '(Re)Send All Supplier Emails',
));

// echo render_html_table_admin( null, $order->get_supplier_table_data(), [ 'title' => 'Suppliers' ] );