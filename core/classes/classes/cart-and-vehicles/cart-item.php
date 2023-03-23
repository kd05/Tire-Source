<?php

/**
 * Class Cart_Item
 */
Class Cart_Item extends Export_As_Array {

	public $id;
	public $type; // 'tire', 'rim', 'other???'
	public $loc;
	public $quantity;
	public $part_number;
	public $tire_type;
	public $price;
	public $package_id;
	public $timestamp;

	/** @var  DB_Rim|null */
	public $db_rim;

	/** @var  DB_Tire|null */
	public $db_tire;

	protected $props_to_export = array(
		'id',
		'type',
		'loc',
		'quantity',
		'part_number',
		'tire_type',
		'price',
		'package_id',
		'timestamp',
	);

	/**
	 * Cart_Item constructor.
	 *
	 * @param $data
	 */
	public function __construct( $data ) {
		parent::__construct( $data );
	}

	/**
	 * @return string
	 */
	public function get_receipt_summary_title(){
		return $this->get_cart_title_in_one_line();
	}

	/**
	 * @return bool
	 */
	public function is_tire_or_rim(){
		return $this->is_tire() || $this->is_rim();
	}

	/**
	 * @return bool
	 */
	public function is_tire(){
		return $this->type === 'tire';
	}

	/**
	 * @return bool
	 */
	public function is_rim(){
		return $this->type === 'rim';
	}

	/**
	 * @return string
	 */
	public function get_receipt_summary_price_on_2_lines(){
		$op = '';
		$op .= $this->get_quantity() . 'X <br>';
		$op .= print_price_dollars_formatted( $this->get_price_raw() );
		return $op;
	}

	/**
	 * @return string
	 */
	public function get_receipt_summary_price_text(){
		$price = $this->get_price_raw();
		$qty = $this->get_quantity();

		$op = '';
		$op .= $qty . ' X ' . print_price_dollars_formatted( $price );
		return $op;
	}

	/**
	 * see comment for get_db_rim
	 */
	public function get_db_tire(){
		if ( $this->type === 'tire' ) {
			if ( $this->db_tire instanceof DB_Tire ){
				return $this->db_tire;
			}

			$this->db_tire = DB_Tire::create_instance_via_part_number( $this->part_number, false, true );

			if ( ! $this->db_tire ) {

				$msg = 'A tire with part number ' . $this->part_number . ' has been removed from your cart since it has gone out of stock or is no longer available. Sorry for the inconvenience.';

				add_session_alert( $msg );

				$cart = get_cart_instance();
				$cart->item_delete( $this->id );
				$cart->commit(); // commit changes to session
				return false;
			}

			return $this->db_tire;
		}
	}

	/**
	 * Returns the unlimited constant for mount_balance or install_kit.
	 *
	 * $locale should be passed in from the Cart object...
	 *
	 * @param $locale
	 *
	 * @return int|string - an integer (can be negative) or STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING
	 */
	public function get_computed_stock_amt( $locale ){

		if ( $this->type === 'tire' || $this->type === 'rim' ) {
			$ret = $this->get_db_product()->get_computed_stock_amount( $locale );
		} else {
			// mount_balance/install_kit are not limited by stock
			$ret = STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING;
		}

		return $ret;
	}

	/**
	 * calling this may result in database queries, which sometimes you cannot avoid,
	 * but at other times you should.
	 *
	 * @return DB_Rim|DB_Tire|null
	 */
	public function get_db_product(){

		if ( $this->type === 'rim' ) {
			return $this->get_db_rim();
		}

		if ( $this->type === 'tire' ) {
			return $this->get_db_tire();
		}

		// for mount and balance etc.
		return null;
	}

	/**
	 * Instead of running a script to ensure all products in cart still exist in the database every so often,
	 * I think what we'll do is each time we try to get the database object, then if it doesnt
	 * exist, we'll remove the cart item and store a session message and let the user know the next
	 * time we can. If we need to, then in the future we may store the entire cart items database entity
	 * into the session array, but for the time being we're not doing that. the item will have to exist
	 * in the database from the time they add the item to the cart until they checkout.
	 *
	 * @return bool|DB_Rim|null|string|static
	 */
	public function get_db_rim(){
		if ( $this->type === 'rim' ) {
			if ( $this->db_rim instanceof DB_Rim ){
				return $this->db_rim;
			}

			$this->db_rim = DB_Rim::create_instance_via_part_number( $this->part_number, false, true );

			if ( ! $this->db_rim ) {

				$msg = 'A rim with part number ' . $this->part_number . ' has been removed from your cart since it has gone out of stock or is no longer available. Sorry for the inconvenience.';

				add_session_alert( $msg );

				$cart = get_cart_instance();
				$cart->item_delete( $this->id );
				$cart->commit(); // commit changes to session
				return null;
			}

			return $this->db_rim;
		}
	}

	/**
	 * Get quantity and validate for things like false, null, or
	 * values that shouldn't be there. returns an int.
	 *
	 * @param null $v
	 *
	 * @return int|null
	 */
	public function get_quantity( ){
		$v = $this->quantity;
		$v = $v === '0' ? 0 : $v;
		$v = (int) $v;
		return $v;
	}

	/**
	 * @return string
	 */
	public function get_cart_img_url(){

		switch( $this->type ) {
			case 'rim':

				// set class prop according to database table
				$this->get_db_rim();

				// use reg, we show in lightbox as well as thumb
				$ret = $this->db_rim ? $this->db_rim->get_image_url( 'reg' ) : image_not_available();
				break;
			case 'tire':

				// set class prop according to database table
				$this->get_db_tire();

                // use reg, we show in lightbox as well as thumb
				$ret = $this->db_tire ? $this->db_tire->get_image_url( 'reg' ) : image_not_available();
				break;
			case 'mount_balance':
				$ret = get_image_src( 'mount-balance.jpg' );
				break;
			case 'install_kit':
				$ret = get_image_src( 'accessories-kit.jpg' );
				break;
			default:
				$ret = '';
		}

		return $ret;
	}

	/**
	 * @return string
	 */
	public function get_cart_title_in_one_line(){

		$op = '';
		$op .= $this->get_cart_title();
		$op .= ' ' . $this->get_cart_title_2();
		$op .= '';

		$op = trim( $op );
		return $op;
	}

	/**
	 * @return string
	 */
	public function get_cart_title(){

		$ret = '';

		switch( $this->type ) {
			case 'rim':
				$rim = $this->get_db_rim();
				$ret = $rim ? $rim->get_cart_title() : 'Product not found';
				break;
			case 'tire':
				$tire = $this->get_db_tire();
				$ret = $tire ? $tire->get_cart_title() : 'Product not found';
				break;
			case 'mount_balance':
				$ret = 'Mount & Balance';
				break;
			case 'install_kit':
				$ret = 'Wheel Installation Kit (Nuts)';
				break;
		}

		return $ret;
	}

	/**
	 * @return string
	 */
	public function get_cart_title_2(){

		$ret = '';

		switch( $this->type ) {
			case 'rim':
				$rim = $this->get_db_rim();
				$ret = $rim ? $rim->get_secondary_sizing_specs() : 'Product not found';
				break;
			case 'tire':
				$tire = $this->get_db_tire();
				$ret = $tire ? $tire->get_sizing_specs() : 'Product not found';
				break;
			case 'mount_balance':
				break;
			case 'install_kit':
				break;
		}

		return $ret;
	}

	/**
	 * SKU to display on front-end
	 */
	public function get_cart_sku(){
		return $this->part_number;
	}

	/**
	 *
	 */
	public function get_cart_location_text(){

		if ( $this->loc === 'front' ) {
			return 'Front';
		}

		if ( $this->loc === 'rear' ) {
			return 'Rear';
		}

		return '';
	}

	/**
	 *
	 */
	public function get_price_raw(){

		switch( $this->type ) {
			case 'rim':
				$rim = $this->get_db_rim();
				$ret = $rim ? $rim->get_price_dollars_raw() : 0;
				break;
			case 'tire':
				$tire = $this->get_db_tire();
				$ret = $tire ? $tire->get_price_dollars_raw() : 0;
				break;
			case 'mount_balance':
				$ret = get_mount_balance_price( $this->part_number );
				break;
			case 'install_kit':
				$ret = get_install_kit_price( $this->part_number );
				break;
			default:
				$ret = 0;
		}

		return $ret;
	}

}