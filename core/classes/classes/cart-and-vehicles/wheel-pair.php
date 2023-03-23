<?php

Class Tire_Size{

	public $width;
	public $profile;
	public $diameter;

	public $width_inches;
	public $profile_inches;

	public $size;

	/**
	 * Tire_Size constructor.
	 *
	 * @param $width
	 * @param $profile
	 * @param $diameter
	 */
	public function __construct( $width, $profile, $diameter ) {

		$this->width = $width;
		$this->profile = $profile;
		$this->diameter = $diameter;

		$this->width_inches = $width * MM_TO_INCHES;
		$this->profile_inches  = ( $this->width_inches * ( $this->profile / 100 ) );

		$this->size = $diameter + ( $this->profile_inches * 2 );
	}
}

/**
 * Not to be confused with Wheel_Set.
 * A Wheel_Set has two Wheel_Pairs (one for front, one for rear).
 * Class Wheel_Pair
 */
Class Wheel_Pair extends Export_As_Array {

	public $loc; // 'front' or 'rear'
	public $tire_pressure;
	public $rim;
	public $rim_diameter;
	public $rim_width;
	public $rim_offset;
	public $tire;
	public $tire_sizing_system;
	public $tire_construction;
	public $tire_width;
	public $tire_aspect_ratio;
	// tire diameter is the same as rim diameter, therefore in the data, tire diameter is usually null
	public $tire_diameter;
	public $tire_section_width;
	public $tire_is_82series;
	public $speed_rating;
	public $load_index;

	protected $props_to_export = array(
		'loc',
		'tire_pressure',
		'rim',
		'rim_diameter',
		'rim_width',
		'rim_offset',
		'tire',
		'tire_sizing_system',
		'tire_construction',
		'tire_width',
		'tire_aspect_ratio',
		'tire_diameter',
		'tire_section_width', // don't know what this is, use tire_width instead!
		'tire_is_82series',
		'speed_rating', // api calls this 'speed_index' but we changed it
		'load_index',
	);

	/**
	 * CP_Fitment_Pair constructor.
	 *
	 * @param $data
	 */
	public function __construct( $data, $loc = 'front' ) {

		if ( is_array( $data ) ) {
			$data['loc'] = $loc;
		} else if ( is_object( $data ) ) {
			$data->loc = $loc;
		}

		parent::__construct( $data );
	}

	/**
	 * You might want to never use this, unless you are setting up a substitution size,
	 * and then you are forced to clone an existing wheel pair, and adjust some of its properties.
	 * otherwise, dynamic diameters are not supposed to a thing.
	 */
	public function set_diameter( $v ){
		$this->rim_diameter = (int) $v;
		$this->tire_diameter = (int) $v;
	}

	/**
	 * Why do we have this? Because when applying substitution sizes, for
	 * every 1 inch increase in diameter, we (may) add 0.5 inch increase in rim width.
	 *
	 * @param $v
	 */
	public function set_rim_width( $v ) {
		$this->rim_width = $v;
	}

	/**
	 * @param $v
	 */
	public function set_tire_profile( $v ) {
		$this->tire_aspect_ratio = (int) $v;
	}

	/**
	 * @param $v
	 */
	public function set_tire_width( $v ) {
		$this->tire_width = (int) $v;
	}

	/**
	 * tire diameter is null in the data, but in reality the same as rim diameter
	 */
	public function get_diameter() {
		return $this->rim_diameter;
	}

	/**
	 * I'm going to make this a function to access the property because we may
	 * want to filter the results before feeding them into queries. Ie. we may return just "LT" or..
	 * perhaps "lt-metric", or just whatever is in the data, which seems to be "lt-metric" or "metric".
	 * On "lt-metric" we may return nothing because this is default. On other weird and ridiculous tire
	 * sizing systems we may return some kind of "not_supported" thing. I don't know all of the tire sizing systems
	 * but some are very weird, and i've been sizes that don't specify profile.
	 */
	public function get_tire_sizing_system(){
		return $this->tire_sizing_system;
	}

	/**
	 * Just so we don't get confused about which property to access
	 *
	 * @return mixed
	 */
	public function get_width(){
		// tire_section_width is something else and im not sure what but it seems to be empty in the data;
		return $this->tire_width;
	}

	/**
	 * @return mixed
	 */
	public function get_rim_width(){
		return $this->rim_width;
	}

	/**
	 *
	 */
	public function get_offset(){
		return $this->rim_offset;
	}

	/**
	 * @return mixed
	 */
	public function get_profile(){
		return $this->tire_aspect_ratio;
	}

}