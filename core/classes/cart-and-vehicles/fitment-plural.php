<?php

/**
 * A fitment that has an array of Wheels, which is almost certainly
 * all the sets of wheels that will fit.
 *
 * Class Fitment_Plural
 */
Class Fitment_Plural extends Fitment_General {

	/** @var Wheel_Set[] */
	public $wheels;

	protected $props_to_export = array(
		'market_slug',
		'trim',
		'stud_holes',
		'pcd',
		'bolt_pattern',
		'lock_type',
		'lock_text',
		'center_bore',
		'wheels',
	);

	/**
	 * Fitment constructor.
	 */
	public function __construct( $data ) {
		parent::__construct( $data );
	}

	/**
	 * @param $data
	 */
	public function init( $data ) {
		parent::init( $data );

		$wheels = gp_if_set( $data, 'wheels', array() );

		if ( $wheels && is_array( $wheels ) ) {
			foreach ( $wheels as $key => $wheel ) {
				// slug *should* be in both places, and the same in both places.
				$wheel_obj             = $wheel instanceof Wheel_Set_Parent ? $wheel : new Wheel_Set_Parent( $wheel );
				$wheel_obj->validate_dev_errors();

				$slug                  = gp_if_set( $wheel_obj, 'slug', $key );
				$this->wheels[ $slug ] = $wheel_obj;
			}
		} else {
			$this->wheels = array();
		}
	}

	/**
	 * Useful (maybe)... but not in use.
	 *
	 * Instead, @see Fitment_Singular::export_sizes();
	 */
	public function export_sizes( $skip_fitment_slugs = array() ) {

		$sizes = array();

		if ( $this->wheels && is_array( $this->wheels ) ) {

			foreach ( $this->wheels as $wheel_set ) {

				if ( $skip_fitment_slugs && in_array( $wheel_set->slug, $skip_fitment_slugs ) ) {
					continue;
				}

				$sizes[] = self::get_size_from_fitment_and_wheel_set( $this, $wheel_set );
			}
		}
		return $sizes;
	}

	/**
	 * Useful (maybe)... but not in use.
	 */
	public function export_oem_sizes() {

		$ret = array();
		if ( $this->wheels && is_array( $this->wheels ) ) {
			foreach ( $this->wheels as $wheel_set ) {
				if ( $wheel_set->is_oem() ) {
					$ret[] = self::get_size_from_fitment_and_wheel_set( $this, $wheel_set );
				}
			}
		}

		return $ret;
	}

	/**
	 *
	 */
	public function export_non_oem_sizes() {

		$ret = array();
		if ( $this->wheels && is_array( $this->wheels ) ) {
			foreach ( $this->wheels as $wheel_set ) {
				if ( ! $wheel_set->is_oem() ) {
					$ret[] = self::get_size_from_fitment_and_wheel_set( $this, $wheel_set );
				}
			}
		}
		return $ret;
	}
}