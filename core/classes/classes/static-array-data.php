<?php

/**
 * Holds static method to get hardcoded, non-changing array data for various things
 *
 * Class Static_Array_Data
 */
Class Static_Array_Data{
    /**
     * @var array
     */
    private $data;

    /**
	 * Data_Thing constructor.
	 *
	 * @param $data
	 */
	public function __construct( $data ) {
		$data = gp_make_array( $data );
		$this->data = $data;
	}

	/**
	 * @return array
	 */
	public function get_data(){
		return $this->data;
	}

	/**
	 * @param $method
	 */
	public static function make_new_instance( $method, $params = array() ) {

		$data = array();

		if ( method_exists( get_called_class(), $method ) && $method !== 'make_new_instance' ) {
			$data = call_user_func_array( array( get_called_class(), $method ), $params );
		}

		return new static( $data );
	}

	/**
	 * @param $data
	 *
	 * @return array
	 */
	public static function export_for_filters( $data ){
		$self = new static( $data );
		return $self->get_data_for_filters();
	}

	/**
	 * @return array
	 */
	public function get_data_for_filters(){

		$ret = array();

		if ( $this->data ) {
			foreach ( $this->data as $slug=>$arr ) {
				$ret[] = array(
					'value' => $slug,
					'text' => gp_if_set( $arr, 'name', $slug ),
				);
			}
		}

		return $ret;
	}

	/**
	 *
	 */
	public function get_valid_ids(){
		return array_keys( $this->data );
	}

	/**
	 *
	 */
	public static function rim_types(){

		$ret = array();

		$ret['steel'] = array(
			'name' => 'Steel',
		);

		$ret['alloy'] = array(
			'name' => 'Alloy',
		);

		return $ret;
	}

	/**
	 *
	 */
	public static function rim_styles(){

		$ret = array();

		// I think we can omit this. the filter title will be "Replica" and have only one option
		// showing non replica is silly
//		$ret[''] = array(
//			'name' => 'Not Replica',
//		);

		$ret['replica'] = array(
			'name' => 'Replica',
		);

		return $ret;
	}


	/**
	 * @return array
	 */
	public static function tire_speed_ratings(){

		$ret = array();

		$ret['h'] = array(
			'name' => 'H',
		);

		$ret['r'] = array(
			'name' => 'R',
		);

		$ret['v'] = array(
			'name' => 'V',
		);

		$ret['r'] = array(
			'name' => 'R',
		);

		$ret['s'] = array(
			'name' => 'S',
		);

		$ret['t'] = array(
			'name' => 'T',
		);

		$ret['w'] = array(
			'name' => 'W',
		);

		$ret['y'] = array(
			'name' => 'Y',
		);

		return $ret;
	}

	/**
	 * @return array
	 */
	public static function tire_model_categories(){

		$ret = array();

		$ret['all-terrain'] = array(
			'name' => 'All Terrain',
		);

		$ret['high-performance'] = array(
			'name' => 'High Performance',
		);

		$ret['highway-terrain'] = array(
			'name' => 'Highway Terrain',
		);

		$ret['mud'] = array(
			'name' => 'Mud',
		);

		$ret['mud-tire'] = array(
			'name' => 'Mud Tire',
		);

		$ret['performance-all-season'] = array(
			'name' => 'Performance All Season',
		);

		$ret['performance-winter'] = array(
			'name' => 'Performance Winter',
		);

		// have not seen this in use yet, but i'm guessing it might be a thing
		// it should be ok to include this because our dynamic filters will never show it
		// if no products have it.
		$ret['performance-summer'] = array(
			'name' => 'Performance Summer',
		);

		// have not seen this in use yet, but i'm guessing it might be a thing
		// it should be ok to include this because our dynamic filters will never show it
		// if no products have it.
		$ret['performance-all-weather'] = array(
			'name' => 'Performance All Weather',
		);

		$ret['touring'] = array(
			'name' => 'Touring',
		);

		$ret['ultra-high-performance'] = array(
			'name' => 'Ultra High Performance',
		);

		return $ret;
	}

	/**
	 * @return array
	 */
	public static function tire_model_classes(){

		$ret = array();

		$ret['commercial-light-truck'] = array(
			'name' => 'Commercial Light Truck',
		);

		$ret['light-truck'] = array(
			'name' => 'Light Truck',
		);

		$ret['passenger'] = array(
			'name' => 'Passenger',
		);

		$ret['suv'] = array(
			'name' => 'SUV',
		);

		$ret['truck'] = array(
			'name' => 'Truck',
		);

		return $ret;
	}

	/**
	 * @return array
	 */
	public static function tire_model_run_flat(){

		$ret = array();

		$ret['run-flat'] = array(
			'name' => 'Run Flat',
		);

		$ret['non-run-flat'] = array(
			'name' => 'Non Run Flat',
		);

		return $ret;
	}

	/**
	 * Used in filter options, and also to instantiate objects via slug (and populate the name).
	 * This function may be called A LOT of times over the course of a page load, therefore don't
	 * use a database query for this unless you cache the results in php memory.
	 *
	 * @return array
	 */
	public static function tire_model_types(){

		$ret = array();

		$ret['all-season'] = array(
			'name' => 'All Season',
		);

		$ret['all-weather'] = array(
			'name' => 'All Weather',
		);

		// some places will rely on these being in order.
		$ret['summer'] = array(
			'name' => 'Summer',
		);

		$ret['winter'] = array(
			'name' => 'Winter',
		);

		return $ret;
	}
}
