<?php

/**
 * A singular fitment is one with just one set of wheels.
 *
 * Our app deals primarily with vehicles that have exactly 1 fitment selected, and then
 * possibly one substitution size selected which is a mutation of a selected fitment.
 *
 * Class Fitment_Singular
 */
Class Fitment_Singular extends Fitment_General {

	/** @var Wheel_Set_Parent */
	public $wheel_set;

	/**
	 * Fitment constructor.
	 */
	public function __construct( $data ){

		// this will call $this->init()
		parent::__construct( $data );

		// store all available sub sizes. this will let us print the sub size <select>
		// Note: this runs once for every package in the cart. Not totally ideal.
		// This also hits the database. Definitely no big deal with 2 or 3 items in the cart.
		// It is a known issue with like 15 cart packages but what can we really do.
		// page generation time is still quite small (still under half a second),
		// despite doing a lot of work to get  all sub sizes for all vehicles.
		// we could consider doing this only conditionally, but at this point I don't know
		// if this would cause errors elsewhere.
		if ( $this->has_wheel_set() ) {
			$this->wheel_set->all_wheel_set_subs = $this->wheel_set->generate_all_wheel_set_subs();
		}

		// add the sub size to the wheel set, but only in the Fitment_Singular constructor for now
		if ( $this->sub_slug ) {
			$this->apply_substitution_size( $this->sub_slug );
		}
	}

	/**
	 * @param $data
	 */
	public function init( $data ) {
		parent::init( $data );

		// all wheel sets available for a vehicle
		$wheel_set_arr = gp_if_set( $data, 'wheel_set', array() );

		// the selected wheel set
		$this->wheel_set = $wheel_set_arr instanceof Wheel_Set_Parent ? $wheel_set_arr : new Wheel_Set_Parent( $wheel_set_arr );
	}

	/**
	 * Apply the sub size means to setup $this->wheel_set->wheel_set_sub
	 *
	 * @param $sub_slug
	 */
	public function apply_substitution_size( $sub_slug ) {

		if ( $this->wheel_set && $sub_slug ) {
			return $this->wheel_set->apply_substitution_size_via_slug( $sub_slug );
		}

		return false;
	}

	/**
	 * This means a user selected a "fitment"
	 */
	public function has_wheel_set(){
		// lots of ways we could check this, just check that slug exists in case
		// the object exists but is empty.
		if ( $this->wheel_set && $this->wheel_set->slug ) {
			return true;
		}
		return false;
	}

	/**
	 * Means the user selected a sub size, and the sub size is valid (was found in database),
	 * and $this->wheel_set->wheel_set_sub is an instance of Wheel_Set_Sub.
	 */
	public function has_substitution_wheel_set(){

		if ( $this->wheel_set instanceof Wheel_Set_Parent ){
			return $this->wheel_set->has_substitution_wheel_set();
		}

		return false;
	}

	/**
	 * Fitments (should) but might not have a selected wheel set. Wheel sets
	 * may have a selected substitution size wheel set.
	 *
	 * @return Wheel_Set|Wheel_Set_Sub|null
	 */
	public function get_selected_wheel_set(){
		$ret = $this->wheel_set ? $this->wheel_set->get_selected() : null;
		return $ret;
	}

	/**
	 * Return an array of sizes containing at most, 1 size.
	 *
	 * If a fitment and a sub size is selected, it exports the sub size, otherwise, if a fitment
	 * size is selected, it exports the fitment size.
	 *
	 * @return array
	 */
	public function export_sizes() {
		$sizes = array();
		if ( $this->get_selected_wheel_set() ) {
			$sizes[] = self::get_size_from_fitment_and_wheel_set( $this, $this->get_selected_wheel_set() );
		}
		return $sizes;
	}

	/**
	 * Still returns an array of sizes, with no more than 1 size.
	 */
	public function export_selected_sub_size(){
		$sizes = array();

		if ( $this->has_substitution_wheel_set() ) {
			$sizes[] = self::get_size_from_fitment_and_wheel_set( $this, $this->wheel_set->wheel_set_sub );
		}

		return $sizes;
	}

    /**
     * @param array $skip_slugs
     * @return array
     */
	public function export_sub_sizes_except( $skip_slugs = array() ) {

		$sizes = array();

		if ( $this->has_wheel_set() ) {

			if ( $this->wheel_set->all_wheel_set_subs ) {

				foreach ( $this->wheel_set->all_wheel_set_subs as $ws ) {

					if ( in_array( $ws->get_slug(), $skip_slugs ) ) {
						continue;
					}

					$sizes[] = self::get_size_from_fitment_and_wheel_set( $this, $ws );
				}
			}
		}
		return $sizes;
	}

	/**
	 * "Fitment" slug corresponds to a Wheel_Set_Parent.
	 */
	public function get_fitment_slug(){
		if ( $this->wheel_set ) {
			return $this->wheel_set->get_slug();
		}
		return '';
	}

	/**
	 * The sub slug can only exist if the parent wheel set exists.
	 *
	 * This function might be a bad idea. Are we returning the slug that the user suggested, or only the slug
	 * if its valid and we setup the substitution size wheel set object? We can't always know what's needed.
	 *
	 * @return string
	 */
	public function get_sub_slug(){
		$ret = $this->wheel_set && $this->wheel_set->get_selected()->is_sub() ? $this->wheel_set->get_selected()->get_slug() : '';
		return $ret;
	}

	/**
	 *
	 */
	public function get_fitment_name( $include_plus_minus_text = true ){
		$ret = $this->wheel_set ? $this->wheel_set->get_selected()->get_fitment_and_or_sub_name( $include_plus_minus_text ) : '';
		return $ret;
	}
}

