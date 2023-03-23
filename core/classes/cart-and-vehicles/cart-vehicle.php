<?php

/**
 * This is used for our cart where we store only basic information for the vehicle,
 * and the vehicle fitment data is stored in a different place.
 *
 * Class Cart_Vehicle
 */
Class Cart_Vehicle extends Export_As_Array {

	public $make;
	public $model;
	public $year;
	public $trim;

	// store all names in the cart object because they are potentially expensive to get
	// from just the slugs which are passed through pages via $_GET
	public $make_name;
	public $model_name;
	public $year_name;
	public $trim_name;

	protected $props_to_export = array(
		'make',
		'model',
		'year',
		'trim',
		'make_name',
		'model_name',
		'year_name',
		'trim_name',
	);

	/**
	 * Vehicle_Basic constructor.
	 *
	 * @param $data
	 */
	public function __construct( $data ) {
		parent::__construct( $data );
	}

	/**
	 * Vehicles in cart packages do not store fitment information, therefore
	 * we have to repeat some logic from inside of Vehicle_General->get_display_name().
	 * In our cart package, we need to combine both fitment information and vehicle name.
	 */
	public function get_display_name() {
		$ret = get_vehicle_display_name( $this->make_name, $this->model_name, $this->year_name, $this->trim_name );

		return $ret;
	}

	/**
	 * @return array
	 */
	public function make_model_year_trim_array(){
		$ret = array(
			'make' => $this->make,
			'model' => $this->model,
			'year' => $this->year,
			'trim' => $this->trim,
		);
		return $ret;
	}

	/**
	 * @param $data
	 */
	public function init( $data ) {

		if ( $data instanceof Vehicle ) {
			$data = array(
				'make' => $data->make,
				'model' => $data->model,
				'year' => $data->year,
				'trim' => $data->trim,
				'make_name' => $data->make_name,
				'model_name' => $data->make_name,
				'year_name' => $data->make_name,
				'trim_name' => $data->make_name,
			);
		}

		$this->make  = get_user_input_singular_value( $data, 'make' );
		$this->model = get_user_input_singular_value( $data, 'model' );
		$this->year  = get_user_input_singular_value( $data, 'year' );
		$this->trim  = get_user_input_singular_value( $data, 'trim' );
		$this->make_name = get_user_input_singular_value( $data, 'make_name' );
		$this->model_name = get_user_input_singular_value( $data, 'model_name' );
		$this->year_name = get_user_input_singular_value( $data, 'year_name' );
		$this->trim_name = get_user_input_singular_value( $data, 'trim_name' );
	}
}