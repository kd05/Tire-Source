<?php

/**
 * Pretty pointless interface
 *
 * Interface I_Export_As_Array
 */
Interface I_Export_As_Array{
	public function __construct( $data );
	public function init( $data );
	public function to_array();
}

/**
 * Warning: does not handle circular dependencies very well when calling
 * to_array_recursive().... so... maybe you'll want to avoid them.
 *
 * Class Export_As_Array
 */
Class Export_As_Array implements I_Export_As_Array{

	public $extra_data;

	/**
	 * @var array
	 */
	protected $props_to_export = array();

	/**
	 * Export_As_Array constructor.
	 */
	public function __construct( $data ){

		// not currently needed
//		if ( $data instanceof stdClass ) {
//			$data = get_object_vars( $data );
//		}

		$extra_data = array(); // passed by reference
		$_data = $this->separate_extraneous_data( $data, $extra_data );
		$this->extra_data = $extra_data;

		// note: we could pass in $_data in theory, but DO NOT do this. Just pass in
		// all $data. There are rare circumstances where we want the original $data passed in,
		// not just the $data according to $props_to_export.
		$this->init( $data );
	}

	/**
	 * If you don't want what you are setting to show up in the return value of $this->to_array(),
	 * then just use the class directly. Ie. $this->{$key} = $value.
	 *
	 * @param $key
	 * @param $value
	 */
	public function set( $key, $value, $fallback = true ) {

		if ( ! gp_is_singular( $key ) ) {
			return false;
		}

		if ( property_exists( $this, $key ) ) {
			$this->{$key} = $value;
			return true;
		}

		if ( $fallback ) {
			if ( is_array( $this->extra_data ) ) {
				$this->extra_data[$key] = $value;
				return true;
			}
		}

		return false;
	}

	/**
	 *
	 * @param $data
	 */
	public function init( $data ){
		$this->default_init( $data );
	}

	/**
	 * This is sufficient when each of your $props_to_export doesn't need to be an object.
	 *
	 * Don't just use this blindly. In most cases, writing your own init() function actually helps alot.
	 *
	 * @param $data
	 */
	public function default_init( $data ) {
		if ( $this->props_to_export && is_array( $this->props_to_export ) ){
			foreach ( $this->props_to_export as $prop ) {
				$this->{$prop} = gp_if_set( $data, $prop, null );
			}
		}
	}

	/**
	 * Returns the $data that share indexes with $this->properties.
	 * Sets the $extra array to track indexes of $data not found in $this->properties
	 *
	 * @param $data
	 * @param $extra
	 */
	public function separate_extraneous_data( $data, &$extra ) {

		$extra = $data;
		$ret = array();

		if ( ! is_array( $data ) ) {
			return $ret;
		}

		if ( ! $data ) {
			return $ret;
		}

		if ( $this->props_to_export ) {
			foreach ( $this->props_to_export as $prop ) {

				// if $data has the prop, put it in the return value and remove it
				// from the $extra array.
				if ( array_key_exists( $prop, $data ) ) {
					$ret[$prop] = $data[$prop];
					unset( $extra[$prop] );
				}

			}
		}

		return $ret;
	}

	/**
	 *
	 */
	public function to_array(){
		return $this->object_props_to_array( $this->props_to_export, $this->extra_data );
	}

	/**
	 * @param       $props - this is most likely $the_object->props_to_export
	 * @param array $merge_array - this could be $the_object->extra_data
	 *
	 * @return array
	 */
	public function object_props_to_array( $props, $merge_array = array() ) {

		$ret = array();

		if ( $props ) {
			foreach ( $props as $prop ) {
				$value = gp_if_set( $this, $prop, null );
				$value_arr = $this->to_array_recursive( $value );
				$ret[$prop] = $value_arr;
			}
		}

		if ( $merge_array ) {
			// probably not necessary, but just in case extra data has array elements
			// and those array elements could be other objects etc., then lets make sure to transform into an array.
			// note however, that if one day we want to store foreign objects into the output array that this may
			// prevent us from doing so.
			$merge_array = $this->to_array_recursive( $merge_array );
			$ret = array_merge( $ret, $merge_array );
		}

		return $ret;
	}

	/**
	 * Recursive array call. For each value, if its an object and has a method 'to_array', then we'll use that.
	 *
	 * If the value passed in is not an array (or object), it is returned.
	 *
	 * @param $thing
	 */
	protected function to_array_recursive( $thing ) {

		if ( is_array( $thing ) ) {
			foreach ( $thing as $key=>$value ) {
				$thing[$key] = $this->to_array_recursive( $value );
			}
			return $thing;
		} else if ( is_object( $thing ) ) {

			// this basically means the object extends this class (Export_As_Array)..
			// but for some objects, its not possible, so just check if they have a method.
			if ( method_exists( $thing, 'to_array' ) ) {

				// I believe that this is: redundant. doesn't cause infinite loop. will be kind of inefficient.
				// I really can't be sure however.
				// return $this->to_array_recursive( $thing->to_array() );

				// I think that this is sufficient, but it depends on if the objects to_array() method
				// returns objects ins some of the array values.
				return $thing->to_array();

			} else {

				$thing = get_object_vars( $thing );
				return $this->to_array_recursive( $thing );

				// convert object to array, but only public properties are picked up by this
//				$thing =  get_object_vars( $thing );
				//
				//				// $thing is an array now, but use recursion to check all indexes of $thing now as well
				//				return $this->to_array_recursive( $thing );
			}
		}

		return $thing;
	}
}