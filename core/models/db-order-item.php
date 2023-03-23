<?php

/**
 * Class DB_Order_Item
 */
Class DB_Order_Item extends DB_Table{

	protected static $table = DB_order_items;

	protected static $primary_key = 'order_item_id';

	/**
	 * @var array
	 */
	protected static $fields = array(
		'order_item_id',
		'order_id',
		'order_vehicle_id',
		'package_id',
		'part_number',
		'type',
		'name',
		'description', // maybe optional
		'price',
		'loc',
		'quantity',
		'supplier',
		// serialized product data... the entire database row, some stuff repeated but is mostly for debugging
		'product',
	);

	/**
	 * @var array
	 */
	protected static $db_init_cols = array(
		'order_item_id' => 'int(11) unsigned NOT NULL auto_increment',
		'order_id' => 'int(11) unsigned default NULL',
		'order_vehicle_id' => 'int(11) unsigned default NULL',
		'package_id' => 'varchar(255) default \'\'',
		'part_number' => 'varchar(255) default \'\'',
		'type' => 'varchar(255) default \'\'',
		'name' => 'varchar(255) default \'\'',
		'description' => 'longtext',
		'price' => 'varchar(255) default \'\'',
		'loc' => 'varchar(255) default \'\'',
		'quantity' => 'varchar(255) default \'\'',
		'supplier' => 'varchar(255) default \'\'',
		'product' => 'longtext',
	);

	protected $db_order_vehicle;

	/**
	 * @var array
	 */
	protected static $db_init_args = array(
		'PRIMARY KEY (order_item_id)',
		'FOREIGN KEY (order_id) REFERENCES ' . DB_orders . '(order_id)',
		// we would like to do this but having issues for.. unknown reasons
		// 'FOREIGN KEY (order_vehicle_id) REFERENCES ' . DB_order_vehicles . '(order_vehicle_id)',
	);

	/**
	 * DB_Order_Item constructor.
	 *
	 * @param array $data
	 * @param array $options
	 */
	public function __construct( $data = array(), $options = array() ){
		parent::__construct( $data, $options );
	}

	/**
	 *
	 */
	public function get_order_vehicle(){

		if ( $this->db_order_vehicle instanceof DB_Order_Vehicle ) {
			return $this->db_order_vehicle;
		}

		if ( $this->get( 'order_vehicle_id' ) ) {
			$this->db_order_vehicle = DB_Order_Vehicle::create_instance_via_primary_key( $this->get( 'order_vehicle_id' ) );
		}

		return $this->db_order_vehicle;
	}

	/**
	 * It's very complex to split packages up where only some of the
	 * tires and rims in the package are mounted and balanced.
	 *
	 * There is many reasons this can become... extremely complex. Here are some examples of how. These are also not mutually exclusive:
	 *
	 * - Install kits come in sets of 4, yet a package could contain 8 tires, 8 rims, 2 install kits, and 3 units of mount/balance,
	 * in this case, we can't decide where to send the install kits to.
	 *
	 * - Staggered packages can have rims or tires where each one comes from a different supplier. Thought about this one earlier,
	 * but at the moments, its hard to wrap my head around this enough to explain the potential consequences.
	 *
	 * - A staggered package could have 4 wheels and 4 rims, but only 3 units of mount/balance. Which 1 location are we to decide to not
	 * apply mounting and balancing to (front left? back right?)? This is actually a bit of an issue with the cart itself, but, if we send all products
	 * to the same place, at least the company can call the customer and figure out. Otherwise, we're going to send 3/4 of the items
	 * to the shop, and 1/4 of them to the customer. We cannot determine how to split the items without asking the customer.
	 *
	 * - A non staggered package with 8 tires, 4 rims, and 4 units of m/b. Each of the 8 tires will be under the same part number,
	 * and therefore the same DB_Order_Item contains a qty 8. In this case, it is possible to split the item in 2, but is
	 * maybe a bit complex.
	 *
	 * @return bool
	 */
	public function should_item_be_sent_to_shop_for_mounting_and_balancing(){
		return $this->is_item_in_package_with_mount_balance();
	}

	/**
	 * @return bool
	 */
	public function is_item_in_package_with_mount_balance(){

		$package_id = $this->get( 'package_id' );

		if ( ! $package_id ) {
			return false;
		}

		$db = get_database_instance();
		$p = [];
		$q = '';
		$q .= 'SELECT * ';
		$q .= 'FROM order_items ';
		$q .= 'WHERE 1 = 1 ';

		$q .= 'AND order_id = :order_id ';
		$p[] = [ 'order_id', $this->get( 'order_id' ), '%d' ];

		// technically integer, but not 100% guaranteed
		$q .= 'AND package_id = :package_id ';
		$p[] = [ 'package_id', $package_id, ];

		// careful, mount_balance may exist with quantity zero
		$q .= 'AND type = "mount_balance" ';
		$q .= 'AND quantity > 0 ';
		$q .= ';';

		$results = $db->get_results( $q, $p );

		return (bool) $results;
	}

	/**
	 * Ie. 4x ({part_number}) ({brand/model/sizing specs})
	 *
	 * We'll probably put these under sub-headings: Tires, Rims, Hardware Kits, therefore
	 * the text below might not indicate the type of item.
	 */
	public function get_email_summary_text(){

		$t = $this->get_type();
		$q = $this->get_quantity();

		$pn = $this->get_and_clean( 'part_number' );
		$pn = ampersand_to_plus( $pn );

		// name has " for rims to represent inches
		$name = $this->get_and_strip_tags( 'name' );
		$name = ampersand_to_plus( $name ); // not really necessary

		$pkg = $this->get_and_clean( 'package_id' );

		switch ( $this->get_type() ) {
			case 'tire':
				$ret = $q . 'x ' . $pn . ' (' . $name . ')';
				break;
			case 'rim':
				$ret = $q . 'x ' . $pn . ' (' . $name . ')';
				break;
			case 'install_kit':

				$vehicle = $this->get_order_vehicle();

				if ( $vehicle ) {
					$ret = $q . 'x ' . $vehicle->get_make_model_year() . ' (' . $vehicle->get_sizing_specs_for_supplier_emails() . ')';
				} else {
					// this kind of indicates an exception but we can't exactly exit the script here or it may
					// interfere with a user receiving confirmation for their order.
					log_data( [ 'item' => $this ], 'install-kit-no-vehicle' );
					return 'install_kit_error';
				}

				break;
			default:
				// this probably will not trigger, because we don't sent mount/balance details in supplier emails
				// and there are only 4 item types as of now.
				$ret = $q . 'x ' . $pn . ' (' . $t . ') (' . $name . ')';

		}

		// this step is actually very important. We may change how we do this. But when suppliers
		// receive emails with rims and hardware kits, its essential that they know which rims
		// belong to which hardware kit, since a spigot ring may make up for the difference
		// between the rim center bore and the vehicles center bore.
		if ( ( $t === 'rim' || $t === 'install_kit' ) && $pkg ) {
			$ret = '[pkg ' . $pkg . '] ' . $ret;
		}

		return $ret;
	}

	/**
	 * hardware kits do not have suppliers directly. they belong to packages, which may have rims,
	 * which should have suppliers. its unlikely, but possible, that a staggered fitment contains
	 * two rims with the same brand/model/finish but a different supplier. Therefore, this
	 * returns an array always. When sending email to suppliers upon checkout, if this array
	 * has 2 values, we have no choice but to randomly choose 1 of them and only ask that supplier
	 * for a hardware kit. This looks for RIMS in the same package, not tires. Some packages don't
	 * have rims. If they don't have rims, then they shouldn't have installation kits assuming our
	 * other code worked properly.
	 *
	 * This runs queries.
	 *
	 * @return array
	 */
	public function get_install_kit_suppliers(){

		if ( $this->get_type() !== 'install_kit' ) {
			return array();
		}

		$db = get_database_instance();
		$p = [];
		$q = '';
		$q .= 'SELECT * ';
		$q .= 'FROM ' . DB_order_items . ' AS items ';

		$q .= 'WHERE 1 = 1 ';

		$q .= 'AND ( items.type = "rim" OR items.type = "tire" ) ';

		$q .= 'AND ( items.order_id = :order_id ) ';
		$p[] = [ 'order_id', $this->get( 'order_id' ), '%d' ];

		$q .= 'AND ( items.package_id = :package_id ) ';
		$p[] = [ 'package_id', $this->get( 'package_id' ), '%d' ];

		// maybe get rid of some products. for now, we don't care much about the products just
		// the suppliers
		$q .= 'GROUP BY items.supplier ';

		// even though we "randomly" choose 1 supplier to send emails to if
		// more than 1 is found, we need to always randomly choose the same supplier
		// if we repeat the operation. Therefore... still add an order by.
		$q .= 'ORDER BY items.order_item_id ASC ';

		$q .= ';';

		$r = $db->get_results( $q, $p );

		$suppliers = array();

		if ( $r ) {
			foreach ( $r as $rr ) {
				$sup = gp_if_set( $rr, 'supplier' );;
				if ( $sup && ! in_array( $sup, $suppliers ) ) {
					$suppliers[] = $sup;
				}
			}
		}

		return $suppliers;
	}

	/**
	 * @return bool|mixed
	 */
	public function get_install_kit_supplier_singular(){
		$sups = $this->get_install_kit_suppliers();
		return gp_if_set( $sups, 0 );
	}

	/**
	 * @return bool|mixed
	 */
	public function get_type(){
		return $this->get( 'type' );
	}

	/**
	 * @return int
	 */
	public function get_quantity(){
		$ret = (int) $this->get( 'quantity' );
		return $ret;
	}

	/**
	 * Each item returns an array that are supposed to represent different lines.
	 *
	 * Warning.. you might expect strings. So.. implode, or w/e you gotta do to put in line breaks.
	 *
	 * Also, NEVER return anything that's not an array from here. Other functions will implode()
	 * on this functions output, which will be an error if false is returned.
	 *
	 * @param $key
	 *
	 * @return array
	 */
	public function summary_table_cell_data( $key ) {

		$ret = [];

		switch( $key ) {
			case 'item':

				$order_vehicle = $this->get_order_vehicle();

				$type = $this->get( 'type' );
				$loc = $this->get( 'loc' );

				$loc_map = [ 'rear' => 'Rear', 'front' => 'Front' ];
				$loc_text = gp_if_set( $loc_map, $loc, '' );

				$description = $this->get( 'description' );

				// Name
				$ret[] = $this->get( 'name' );

				if ( $type !== 'mount_balance' && $type !== 'install_kit' ) {
					$ret[] = $this->get( 'part_number' );
				}

				// Description (maybe mount_balance, install_kit only)
				if ( $description ) {
					$ret[] = $description;
				}

				$type_map = [ 'tire' => 'Tire', 'rim' => 'Rim' ];
				$type_text = gp_if_set( $type_map, $type, '' );

				if ( $loc_text ) {
					$type_text .= ' (' . $loc_text . ')';
				}

				// ie. Tire (Front)
				if ( $type_text ){
					$ret[] = $type_text;
				}

				if ( $order_vehicle ) {
					$v_name = $order_vehicle->get( 'vehicle_name' ) . ' ' . $order_vehicle->get( 'fitment_name' );
					$ret[] = $v_name;
				}

				break;
			case 'quantity':
				$ret[] = $this->get( 'quantity' );
				break;
			case 'price':
				$ret[] = print_price_dollars_formatted( $this->get( 'price' ) );
				break;
		}

		return $ret;
	}
}

/**
 * @param $str
 *
 * @return string
 */
function table_cell_line( $str ) {
	$ret = '<span style="display: block; margin: 3px 0 3px 0;">' . $str . '</span>';
	return $ret;
}
