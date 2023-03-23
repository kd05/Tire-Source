<?php

/**
 * Class Cart_Receipt
 */
Class Cart_Receipt {

	public $base;
	public $discount;
	public $subtotal;

	/**
	 * tire levy fee of ($4 per tire?), in Ontario only.
	 *
	 * Note: this I believe is not taxed. So.. be mindful of where you show it to the user,
	 * and also how you handle the price total calculations.
	 *
	 * @var
	 */
	public $ontario_fee;

	/**
	 * the quantity of tires sold to ontario which are subject to the $ontario_fee.
	 *
	 * we might need the quantity to display in the cart receipt.
	 *
	 * @var
	 */
	public $ontario_fee_qty;

	public $shipping;
	public $shipping_to_be_determined;
	public $pre_tax;
	public $tax_rate; // dynamic value
	public $tax;
	public $tax_to_be_determined;
	public $total;

	/**
	 * @var Cart
	 */
	public $cart;

	public $billing_address;
	public $tax_rate_by_address;

	public $shipping_address;
	public $shipping_prices;


	public function __construct( Cart $cart, Billing_Address $billing_address, Shipping_Address $shipping_address, $free_shipping = false ) {

		// defaults
		$this->tax_to_be_determined      = true;
		$this->shipping_to_be_determined = true;
		$this->base                      = 0;
		$this->discount                  = 0;
		$this->ontario_fee               = 0;
		$this->subtotal                  = 0;
		$this->shipping                  = 0;
		$this->pre_tax                   = 0;
		$this->tax                       = 0;
		$this->total                     = 0;

		$this->cart = $cart;

		$this->shipping_address    = $shipping_address;
		$this->billing_address     = $billing_address;
		$this->shipping_prices     = new Shipping_Prices( $shipping_address );
		$this->tax_rate_by_address = new Tax_Rate_By_Address( $billing_address );

		if ( $free_shipping ) {
			$this->shipping_prices = new Shipping_Prices_Free( $shipping_address );
		} else {
			$this->shipping_prices = new Shipping_Prices( $shipping_address );
		}

		$this->shipping_prices->price_per_tire        = $this->round( $this->shipping_prices->price_per_tire );
		$this->shipping_prices->price_per_rim         = $this->round( $this->shipping_prices->price_per_rim );
		$this->shipping_prices->price_per_tire_on_rim = $this->round( $this->shipping_prices->price_per_tire_on_rim );

		$this->shipping_to_be_determined = ! ( $this->shipping_prices->valid );

		if ( $this->shipping_prices->valid ) {
			$this->shipping_to_be_determined = false;
		}

		// show tax only if billing address is valid, and shipping could be calculated
		if ( $this->tax_rate_by_address->valid && $this->shipping_to_be_determined === false ) {
			$this->tax_to_be_determined = false;
		}

		$this->tax_rate = $this->tax_rate_by_address->tax_rate;

		$this->shipping = $this->get_shipping_amount();

		$this->setup_price_values();

		// echo '<pre>' . print_r( $this, true ) . '</pre>';

	}

	/**
	 * @return bool
	 */
	public function total_is_to_be_determined() {
		return $this->tax_to_be_determined || $this->shipping_to_be_determined;
	}

	/**
	 * get a debug array for printing (remove the massive cart object)
	 */
	public function get_debug() {
		$vars           = get_object_vars( $this );
		$vars[ 'cart' ] = null;

		return $vars;
	}

	/**
	 * @param $amt
	 */
	public function round( $amt ) {
        $amt = $amt ?? 0;
		// $log = get_string_for_log( $amt );
		$ret = round( $amt, 2 );
		//		$log .= '...' . get_string_for_log( $ret );
		//		log_data($log, 'cart-receipt-round');
		return $ret;
	}

	/**
	 *
	 */
	private function setup_price_values() {

		// this was almost certainly done at the very beginning of the page load, however
		// I don't want to take any chances whatsoever. These are cached class properties,
		// and if they are not up to date, then their values could be from a previous version
		// of the cart (like before an item was added or removed),
		// and we could end up charging someone for the completely wrong amount.
		// special note: $cart->cart_summary IS stored in $_SESSION but it is never ever loaded from session,
		// instead, every time we load the cart from $_SESSION into an object, we re-calculate it.
		$this->cart->clear_all_cached_data();
		$this->cart->setup_cart_summary();

		$cart = $this->cart;

		if ( $this->cart->items && is_array( $this->cart->items ) ) {
			foreach ( $this->cart->items as $item_id => $item ) {
				/** @var Cart_Item $item */
				$item = $cart->get_item( $item );
				if ( $item ) {
					$qty        = $item->get_quantity();
					$this->base += $this->round( $qty * $this->round( $item->get_price_raw() ) );

					// ontario environmental levy
					if ( $item->type === 'tire' && $this->billing_address->province === 'ON' ) {
						$this->ontario_fee_qty += $qty;
					}
				}
			}
		}

		// add the ontario levy fee
		$this->ontario_fee = $this->round( $this->ontario_fee_qty * $this->round( get_ontario_tire_levy_amt() ) );

		// ensure its 0 so it doesn't end up being "0.00" elsewhere and evaluate to a non-false value
		$this->ontario_fee = $this->ontario_fee > 0 ? $this->ontario_fee : 0;

		// base fee of all items (not including ontario levy fee)
		$this->base = $this->round( $this->base );

		// currently we don't have discounts, so using the variable but its just
		// going to always be zero for now.
		$this->discount = 0;
		$this->subtotal = $this->base - $this->discount;
		$this->subtotal = $this->subtotal > 0 ? $this->subtotal : 0;
		$this->subtotal = $this->round( $this->subtotal );

		$this->pre_tax = $this->subtotal + $this->shipping  + $this->ontario_fee;
		$this->pre_tax = $this->round( $this->pre_tax );

		// be careful, if billing address is valid, tax rate will exist but we must not add it to the total
		// unless shipping is complete, otherwise its inaccurate
		if ( ! $this->tax_to_be_determined ) {
			$this->tax = $this->pre_tax * ( $this->tax_rate );
			$this->tax = $this->round( $this->tax );
		}

		$this->total = $this->pre_tax + $this->tax;
		$this->total = $this->round( $this->total );
	}

	/**
	 *
	 */
	public function get_shipping_amount() {

		// would rather just return zero if its not valid..
		if ( ! $this->shipping_prices->valid ) {
			return 0;
		}

		$amt = 0;

		$amt += $this->shipping_prices->price_per_tire * $this->cart->summary->count_tires_alone;
		$amt = $this->round( $amt );
		$amt += $this->shipping_prices->price_per_rim * $this->cart->summary->count_rims_alone;
		$amt = $this->round( $amt );
		$amt += $this->shipping_prices->price_per_tire_on_rim * $this->cart->summary->count_tires_on_rims;
		$amt = $this->round( $amt );

		return $amt;
	}

}