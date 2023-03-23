<?php

/**
 * Class DB_Order_Email
 */
Class DB_Order_Email extends DB_Table{

	protected static $primary_key = 'email_id';
	protected static $table = DB_order_emails;

	/*
	 * An array of keys required to instantiate the object.
	 */
	// protected static $req_cols = array();

	/**
	 * table columns
	 *
	 * @var array
	 */
	protected static $fields = array(
		'email_id',
		'order_id',
		'supplier',
		'to',
		'from',
		'subject',
		'content',
		'date',
		'sent',
	);

	/**
	 * @var array
	 */
	protected static $db_init_cols = array(
		'email_id' => 'int(11) unsigned NOT NULL auto_increment',
		// its pretty important to track the supplier slug and not just the email it got sent to
		// for many reasons, including because the email content is strongly dependant on this
		'order_id' => 'int(11) unsigned NOT NULL',
		'supplier' => 'varchar(255) default \'\'',
		'to' => 'varchar(255) default \'\'',
		'from' => 'varchar(255) default \'\'',
		'subject' => 'varchar(255) default \'\'',
		'content' => 'longtext',
		'date' => 'varchar(255)',
		'sent' => 'bool',
	);

	/**
	 * @var array
	 */
	protected static $db_init_args = array(
		'PRIMARY KEY (`email_id`)',
		'FOREIGN KEY (`order_id`) REFERENCES ' . DB_orders . '(order_id)',
	);

	protected $data;

	/**
	 * @param $key
	 * @param $value
	 *
	 * @return null
	 */
	public function get_cell_data_for_admin_table( $key, $value ){

		switch( $key ) {
			case 'content':

				$c = get_count( 'order_email_lightbox');
				$lb_id = 'order-email-' . $c;

				$ret = '';
				$ret .= get_general_lightbox_content( $lb_id, nl2br( $value ), [ 'add_class' => 'general-lightbox email-content embed-page' ] );
				$ret .= '<button class="css-reset lb-trigger" data-for="' . $lb_id . '">View</button>';
				return $ret;
				break;
			case 'sent':
				$ret = (int) $value ? 'Yes' : 'No';
				return $ret;
				break;
		}

		// return null to indicate no changes
		return null;
	}
}

/**
 * @param $order_id
 */
function get_emails_from_order_id( $order_id ) {

	$db = get_database_instance();
	$p = [];
	$q = '';
	$q .= 'SELECT * ';
	$q .= 'FROM ' . DB_order_emails . ' ';
	$q .= 'WHERE 1 = 1 ';
	$q .= 'AND order_id = :order_id ';

	$q .= 'ORDER BY date DESC ';

	$p[] = [ 'order_id', $order_id, '%d' ];

	$q .= ';';
	return $db->get_results( $q, $p );
}