<?php

/**
 * Class DB_Order
 */
Class DB_Order extends DB_Table {

	/**
	 * @var string
	 */
	protected static $primary_key = 'order_id';

	/**
	 * @var string
	 */
	protected static $table = DB_orders;

	/**
	 * @var array
	 */
	protected static $fields = array(
		'order_id',
		'transaction_id',
		'user_id',
		'order_date',
		'order_status',
		'locale',
		'ship_to',
		'shipping_is_billing',
		'first_name',
		'last_name',
		'company',
		'phone',
		'email',
		'heard_about',
		'mailing_list',
		'street_number',
		'street_name',
		'street_extra',
		'city',
		'province',
		'postal',
		'country',
		'sh_first_name',
		'sh_last_name',
		'sh_company',
		'sh_phone',
		'sh_street_number',
		'sh_street_name',
		'sh_street_extra',
		'sh_city',
		'sh_province',
		'sh_postal',
		'sh_country',
		'user_email_sent',
		'admin_email_sent',
		'cart',
	);

	/**
	 * @var array
	 */
	protected static $req_cols = array();

	/**
	 * @var array
	 */
	protected static $db_init_cols = array(
		'order_id' => 'int(11) unsigned NOT NULL auto_increment',
		'transaction_id' => 'int(11) unsigned NOT NULL',
		'user_id' => 'int(11) unsigned default NULL',
		'order_date' => 'varchar(255) default \'\'',
		'order_status' => 'varchar(255) default \'\'',
		'locale' => 'varchar(255) default \'\'',
		'ship_to' => 'varchar(255) default \'\'',
		'shipping_is_billing' => 'varchar(255) default \'\'',
		'first_name' => 'varchar(255) default \'\'',
		'last_name' => 'varchar(255) default \'\'',
		'company' => 'varchar(255) default \'\'',
		'phone' => 'varchar(255) default \'\'',
		'email' => 'varchar(255) default \'\'',
		'heard_about' => 'varchar(255) default \'\'',
		'mailing_list' => 'varchar(255) default \'\'',
		'street_number' => 'varchar(255) default \'\'',
		'street_name' => 'varchar(255) default \'\'',
		'street_extra' => 'varchar(255) default \'\'',
		'city' => 'varchar(255) default \'\'',
		'province' => 'varchar(255) default \'\'',
		'postal' => 'varchar(255) default \'\'',
		'country' => 'varchar(255) default \'\'',
		'sh_first_name' => 'varchar(255) default \'\'',
		'sh_last_name' => 'varchar(255) default \'\'',
		'sh_company' => 'varchar(255) default \'\'',
		'sh_phone' => 'varchar(255) default \'\'',
		'sh_street_number' => 'varchar(255) default \'\'',
		'sh_street_name' => 'varchar(255) default \'\'',
		'sh_street_extra' => 'varchar(255) default \'\'',
		'sh_city' => 'varchar(255) default \'\'',
		'sh_province' => 'varchar(255) default \'\'',
		'sh_postal' => 'varchar(255) default \'\'',
		'sh_country' => 'varchar(255) default \'\'',
		'user_email_sent' => 'varchar(255) default \'\'',
		'admin_email_sent' => 'varchar(255) default \'\'',
		// serialized cart has very detailed vehicle, package, and fitment information
		'cart' => 'longtext',
	);

	/**
	 * @var DB_User|null
	 */
	public $user;

	/** @var  DB_Transaction */
	public $transaction;

	/**
	 * @var array
	 */
	protected static $db_init_args = array(
		'PRIMARY KEY (order_id)',
		'FOREIGN KEY (transaction_id) REFERENCES ' . DB_transactions . '(transaction_id)',
		// not all orders have users and sql is giving us a lot of difficulty here therefore i'm just turning this off.
		// seems to be fine on mysql but mariaDB may be the source of the errors...
		// the issue is that here user_id has a default of NULL but in the users table its NOT NULL I believe
		// I don't know if its worth changing it.
		// 'FOREIGN KEY (user_id) REFERENCES ' . DB_users . '(user_id)'
	);

	/**
	 * DB_Order constructor.
	 *
	 * @param array $data
	 * @param array $options
	 */
	public function __construct( $data = array(), $options = array() ) {
		parent::__construct( $data, $options );
		$this->set_foreign_object( $this->get( 'user_id' ), 'DB_User', 'user' );
		$this->set_foreign_object( $this->get( 'transaction_id' ), 'DB_Transaction', 'transaction' );
	}

	/**
	 * @return array
	 */
	public function get_order_by_args_for_admin_table() {
		$ret   = [];
		$ret[] = 'order_date DESC';

		return $ret;
	}

	/**
	 * We have 2 possibilities:
	 *
	 * The email address stored at the time of the order.
	 *
	 * If the user was logged in, this should be the users emails address at that time.
	 *
	 * The user can change their email address after the fact however,
	 *
	 * I think it makes the most sense to always return the users CURRENT email
	 * address. However, for guest checkouts, we'll return the email on the order,
	 * which should not ever change over time.
	 *
	 * @param bool $check_user
	 */
	public function get_email_address( $check_user = true ) {
		$email = $check_user && $this->user ? $this->get( 'email' ) : $this->get( 'email' );

		return $email;
	}

	/**
	 * Returns an array so you can decide how to render it.
	 *
	 * @param bool $with_company
	 * @param bool $with_phone
	 * @param bool $with_email
	 * @param bool $with_name
	 *
	 * @return array
	 */
	public function get_billing_address_summary_array( $with_company = false, $with_phone = false, $with_email = false, $with_name = true ) {

		$ret = static::get_address_array_from_primitive_values( array(
			'first_name' => $this->get_and_clean( 'first_name' ),
			'last_name' => $this->get_and_clean( 'last_name' ),
			'street_number' => $this->get_and_clean( 'street_number' ),
			'street_name' => $this->get_and_clean( 'street_name' ),
			'street_extra' => $this->get_and_clean( 'street_extra' ),
			'company' => $this->get_and_clean( 'company' ),
			'phone' => $this->get_and_clean( 'phone' ),
			'city' => $this->get_and_clean( 'city' ),
			'province' => $this->get_and_clean( 'province' ),
			'country' => $this->get_and_clean( 'country' ),
			'postal' => $this->get_and_clean( 'postal' ),
			'email' => $this->get_email_address( true ),
			'with_company' => $with_company,
			'with_phone' => $with_phone,
			'with_email' => $with_email,
			'with_name' => $with_name,
		) );

		return $ret;
	}

	/**
	 * @return array
	 */
	public static function get_click_it_wheels_address_summary_array(){

		$ret = static::get_address_array_from_primitive_values( array(
			'street_number' => '130',
			'street_name' => 'Oakdale Road',
			'street_extra' => '',
			'company' => '',
			'phone' => '',
			'city' => 'North York',
			'province' => 'ON',
			'country' => 'Canada',
			'postal' => 'M3N 1V9',
			'email' => '',
			'with_company' => false,
			'with_phone' => false,
			'with_email' => false,
			'with_name' => false,
		) );

		return $ret;
	}

	/**
	 * Returns an array so you can decide how to render it.
	 *
	 * Note: when shipping_is_billing is true, it looks like we store the shipping info in the shipping columns anyways.
	 *
	 * @param bool $with_company
	 * @param bool $with_phone
	 *
	 * @return array
	 */
	public function get_shipping_address_summary_array( $with_company = false, $with_phone = false, $with_email = false, $with_name = true ) {

		$ret = static::get_address_array_from_primitive_values( array(
			'first_name' => $this->get_and_clean( 'sh_first_name' ),
			'last_name' => $this->get_and_clean( 'sh_last_name' ),
			'street_number' => $this->get_and_clean( 'sh_street_number' ),
			'street_name' => $this->get_and_clean( 'sh_street_name' ),
			'street_extra' => $this->get_and_clean( 'sh_street_extra' ),
			'company' => $this->get_and_clean( 'sh_company' ),
			'phone' => $this->get_and_clean( 'sh_phone' ),
			'city' => $this->get_and_clean( 'sh_city' ),
			'province' => $this->get_and_clean( 'sh_province' ),
			'country' => $this->get_and_clean( 'sh_country' ),
			'postal' => $this->get_and_clean( 'sh_postal' ),
			'email' => $this->get_email_address( true ),
			'with_company' => $with_company,
			'with_phone' => $with_phone,
			'with_email' => $with_email,
			'with_name' => $with_name,
		) );

		return $ret;
	}

	/**
	 * $args are generated from billing or shipping address.
	 *
	 * return value has optional array keys which you might want to use
	 * as labels, but sometimes we print without labels cuz the information
	 * should be pretty self explanatory.
	 *
	 * clean your data first ...
	 *
	 * This currently effects both emails sent to suppliers,
	 * and order info printed on the order-details page.
	 *
	 * @param $args
	 */
	public static function get_address_array_from_primitive_values( $args ) {

		$first_name    = gp_if_set( $args, 'first_name' );
		$last_name     = gp_if_set( $args, 'last_name' );
		$street_number = gp_if_set( $args, 'street_number' );
		$street_name   = gp_if_set( $args, 'street_name' );
		$street_extra  = gp_if_set( $args, 'street_extra' );
		$company       = gp_if_set( $args, 'company' );
		$phone         = gp_if_set( $args, 'phone' );
		$city          = gp_if_set( $args, 'city' );
		$province      = gp_if_set( $args, 'province' );
		$country       = gp_if_set( $args, 'country' );
		$postal        = gp_if_set( $args, 'postal' );
		$email         = gp_if_set( $args, 'email' );
		$with_company  = gp_if_set( $args, 'with_company' );
		$with_phone    = gp_if_set( $args, 'with_phone' );
		$with_email    = gp_if_set( $args, 'with_email' );
		$with_name     = gp_if_set( $args, 'with_name' );

		// bit of formatting
		$postal = strtoupper( $postal );

		// no reason to disallow ampersands here, allowing for company only because
		// i don't see the other fields being likely to have it.
		$company = str_replace( '&amp;', '&', $company );

		$arr = array();

		if ( $with_name ) {
			$arr[ 'Name' ] = trim( $first_name . ' ' . $last_name );
		}

		if ( $with_email ) {
			$arr[ 'Email' ] = $email;
		}

		if ( $company && $with_company ) {
			$arr[ 'Company' ] = $company;
		}

		if ( $phone && $with_phone ) {
			$arr[ 'Phone' ] = $phone;
		}

		$arr[ 'Street' ] = $street_number . ' ' . $street_name;

		if ( $street_extra ) {
			$arr[ get_street_extra_text() ] = $street_extra;
		}

		$arr[ 'City' ]     = $city;
		$arr[ 'Province' ] = $province . ', ' . $country;

		// our func. is not locale aware, so... postal/zip will have to suffice.
		// p.s. we cannot use the apps current locale because this may be displaying an order
		// placed under a different locale.
		$arr[ 'Postal/Zip' ] = strtoupper( $postal );

		return $arr;
	}

	/**
	 * @param $key
	 * @param $value
	 *
	 * @return null
	 */
	public function get_cell_data_for_admin_table( $key, $value ) {

		switch ( $key ) {
			case 'user_id':
				return get_admin_single_edit_anchor_tag( DB_users, $value );
				break;
		}

		// null means spit out raw data
		return null;
	}

	/**
	 * NOTE: install kits and mount/balance items probably will never have suppliers
	 * attached to them. However, you may still want to check the item type when you loop
	 * through these results. Its expected that each item returned would be of type 'tire' or 'rim'.
	 */
	public function get_order_items_with_supplier( $supplier_slug ) {

		$db = get_database_instance();
		$p  = [];
		$q  = '';
		$q  .= 'SELECT * ';
		$q  .= 'FROM ' . DB_order_items . ' AS items ';
		$q  .= '';
		$q  .= 'WHERE 1 = 1 ';

		$q   .= 'AND items.order_id = :order_id ';
		$p[] = [ 'order_id', $this->get( 'order_id' ), '%d' ];

		$q   .= 'AND items.supplier = :supplier ';
		$p[] = [ 'supplier', $supplier_slug, '%s' ];

		$q .= '';
		$q .= 'ORDER BY items.order_item_id ASC ';
		$q .= ';';

		$r = $db->get_results( $q, $p );

		return $r;
	}

	/**
	 * @param      $col
	 * @param bool $allow_duplicate
	 * @param bool $allow_empty
	 *
	 * @return array
	 */
	public function get_columns_values_from_order_items( $col, $allow_duplicate = true, $allow_empty = true ) {

		$col = gp_esc_db_col( $col );

		$db = get_database_instance();
		$p  = [];
		$q  = '';
		$q  .= 'SELECT order_item_id, order_id, ' . $col . ' ';
		$q  .= 'FROM ' . DB_order_items . ' AS items ';
		$q  .= 'WHERE 1 = 1 ';

		$q   .= 'AND items.order_id = :order_id ';
		$p[] = [ 'order_id', $this->get( 'order_id' ), '%d' ];

		// we may need predictability in the order of items returned
		$q .= 'ORDER BY items.order_item_id ASC ';
		$q .= ';';

		$ret = $db->get_results_and_fetch_all_values_of_column( $q, $p, $col, $allow_duplicate, $allow_empty );

		return $ret;
	}

	/**
	 * @param $supplier_slug
	 */
	public function get_rims_via_supplier( $supplier_slug ) {

		$ret = $this->get_items( array(
			'supplier' => $supplier_slug,
			'type' => 'rim',
		) );

		return $ret;
	}

	/**
	 * @param $supplier_slug
	 */
	public function get_tires_via_supplier( $supplier_slug ) {

		$ret = $this->get_items( array(
			'supplier' => $supplier_slug,
			'type' => 'tire',
		) );

		return $ret;
	}

	/**
	 * This is a complicated action. First we identify packages whose
	 * rims use the given supplier. then in case there is more than 1 supplier used,
	 * choose only 1, and see if it still belongs to that supplier.
	 *
	 * Note: staggered fitments might be able to have rims from different suppliers, although
	 * the brand would be the same, so this scenario would be quite ridiculous, and more or
	 * less shouldn't occur, however, it can.
	 *
	 * install kits don't have suppliers, they have package IDs, which may give us rims,
	 * which may have suppliers. I've explained this elsewhere so won't go into too much detail here.
	 *
	 * Note: this can be done a bit less efficiently using other methods already defined.
	 * This one is better to use when we only have one supplier in question.
	 * We could otherwise get all install kit items, and then loop to group them into their suppliers.
	 *
	 * @param $supplier_slug
	 */
	public function get_install_kits_via_supplier( $supplier_slug ) {

		$db = get_database_instance();
		$p  = [];
		$q  = '';
		$q  .= 'SELECT * ';
		$q  .= 'FROM ' . DB_order_items . ' AS items ';
		$q  .= '';
		$q  .= 'WHERE 1 = 1 ';

		// how many times do we have to do this?
		$q   .= 'AND items.order_id = :order_id ';
		$p[] = [ 'order_id', $this->get( 'order_id' ), '%d' ];

		$q .= 'AND items.type = "rim" ';

		$q   .= 'AND items.supplier = :supplier ';
		$p[] = [ 'supplier', $supplier_slug, '%s' ];

		$q .= 'GROUP BY items.package_id ';
		$q .= 'ORDER BY items.order_item_id ASC ';

		$q .= ';';

		// get the unique package IDs using $supplier_slug via rims
		$package_ids = $db->get_results_and_fetch_all_values_of_column( $q, $p, 'package_id', false, false );

		$ret = array();

		if ( $package_ids ) {
			foreach ( $package_ids as $package_id ) {

				$install_kit = $this->get_install_kit_item_from_package_id( $package_id );

				if ( $install_kit ) {
					$ret[] = $install_kit;
				}
			}
		}

		return $ret;
	}

	/**
	 * @param $package_id
	 */
	public function get_install_kit_item_from_package_id( $package_id ) {

		$items = $this->get_items( array(
			'package_id' => $package_id,
			'type' => 'install_kit',
		) );

		// there should be 0 or 1 install kit items.
		$item = gp_if_set( $items, 0 );

		return $item;
	}

	/**
	 * @return array
	 */
	public function get_unique_suppliers() {
		return $this->get_columns_values_from_order_items( 'supplier', false, false );
	}

	/**
	 * Do we even need this ?
	 *
	 * @return array
	 */
	public function get_unique_package_ids() {
		return $this->get_columns_values_from_order_items( 'package_id', false, false );
	}

	/**
	 * query the 'order_items' table for items using this order ID, and optional
	 * filters to be applied, like type, package_id, etc.
	 *
	 * @return array
	 */
	public function get_items( $args = array() ) {

		$db = get_database_instance();
		$p  = [];
		$q  = '';
		$q  .= 'SELECT * ';
		$q  .= 'FROM ' . DB_order_items . ' AS items ';
		$q  .= 'WHERE 1 = 1 ';

		// put this below instead i guess.
		//		$q .= 'AND items.order_id = :order_id ';
		//		$p[] = [ 'order_id', $this->get( 'order_id' ), '%d' ];

		$args[ 'order_id' ] = $this->get( 'order_id' );

		// don't un-hardcode the array keys here unless you escape them down below.
		$equalities = array(
			'order_id' => '%d',
			'package_id' => '%s',
			'loc' => '%s',
			'type' => '%s',
			'supplier' => '%s',
			'quantity' => '%d',
			'part_number' => '%s',
		);

		// add possible filters
		foreach ( $equalities as $e1 => $e2 ) {
			if ( isset( $args[ $e1 ] ) ) {
				$q   .= 'AND items.' . $e1 . ' = :' . $e1 . ' ';
				$p[] = [ $e1, $args[ $e1 ], $e2 ];
			}
		}

		// we may need predictability in the order of items returned
		// also, we'll always just order by package as well, so that things show up nicer
		// in the supplier emails
		$q .= 'ORDER BY items.package_id ASC, items.order_item_id ASC ';
		$q .= ';';

		$ret = $db->get_results( $q, $p );

		return $ret;
	}
}