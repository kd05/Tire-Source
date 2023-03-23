<?php

/**
 * Class Fitment_General
 */
Class Fitment_General extends Export_As_Array {

	public $market_slug; // ie "usdm"
	public $trim;
	public $stud_holes;
	public $pcd;
	public $bolt_pattern;
	public $lock_type;
	public $lock_text;
	public $center_bore;

	/**
	 * A circular dependency. We're kind of forced to add this in because
	 * of the unexpected requirement of changing rim width/offset variance
	 * based on vehicle make/model/year. This is by far the simplest way
	 * to accomplish this, because all size arrays come from Fitments
	 * and not Vehicles, and fitments would otherwise know nothing about
	 * the Vehicle they originated from.
	 *
	 * Definitely do not include this property in $this->props_to_export.
	 *
	 * Might only use Vehicle, and not Cart_Vehicle.
	 *
	 * @var Vehicle|Cart_Vehicle
	 */
	public $vehicle;

	/**
	 * optional user selected substitution size
	 *
	 * This could be stored in Wheel_Set_Parent, but I think instead I want to store
	 * the slug here, and then Wheel_Set_Parent can have a Wheel_Set_Sub which is
	 * created from $sub_slug. I think this is best, because we can have a very short
	 * representation of the data with only slugs all contained within a Fitment, and also
	 * because Wheel_Sets are almost exclusively used inside the context of Fitments.
	 *
	 * @var
	 */
	public $sub_slug;

	protected $props_to_export = array(
		'market_slug',
		'trim',
		'stud_holes',
		'pcd',
		'bolt_pattern',
		'lock_type',
		'lock_text',
		'center_bore',
		'sub_slug',
	);

	/**
	 * Fitment_General constructor.
	 *
	 * @param $data
	 */
	public function __construct( $data ) {
		parent::__construct( $data );
	}

	/**
	 * @param $data
	 */
	public function init( $data ) {
		$unset = array( 'wheels', 'wheel_sets', 'wheel_set' );
		$data  = unset_array_keys( $data, $unset );

		$this->market_slug  = gp_if_set( $data, 'market_slug', null );
		$this->trim         = gp_if_set( $data, 'trim', null );
		$this->stud_holes   = gp_if_set( $data, 'stud_holes', null );
		$this->pcd          = gp_if_set( $data, 'pcd', null );
		$this->bolt_pattern = gp_if_set( $data, 'bolt_pattern', null );
		$this->lock_type    = gp_if_set( $data, 'lock_type', null );
		$this->lock_text    = gp_if_set( $data, 'lock_text', null );
		$this->center_bore  = gp_if_set( $data, 'center_bore', null );

		// at this point, sub slug might be invalid, and I dont think this is the right place
		// to ensure its validity. right now its just raw user input.
		$this->sub_slug = gp_if_set( $data, 'sub_slug', null );

		$vehicle = gp_if_set( $data, 'vehicle' );
		if ( $vehicle && ( $vehicle instanceof Vehicle || $vehicle instanceof Cart_Vehicle ) ) {
			$this->vehicle = $vehicle;
		}
	}


	/**
	 *
	 */
	public static function get_rim_size_array( Fitment_General $fitment, Wheel_Set $wheel_set ) {

		$ret = $wheel_set->get_default_size_array();

		$has_vehicle = ( $fitment->vehicle );
		$make        = $has_vehicle ? $fitment->vehicle->make : '';
		$model       = $has_vehicle ? $fitment->vehicle->model : '';
		$year        = $has_vehicle ? $fitment->vehicle->year : '';
		$trim        = $has_vehicle ? $fitment->vehicle->trim : '';

		$bolt_pattern = $fitment->bolt_pattern;
		$stud_holes   = $fitment->stud_holes;

		// echo '<pre>' . print_r( $fitment, true ) . '</pre>';

		$center_bore = $fitment->center_bore;

		// ***** DEFAULT WIDTH/OFFSET VARIANCES ******

		$variance_width  = get_default_rim_width_variances();
		$variance_offset = get_default_rim_offset_variances();

		$front_offset_plus      = $variance_offset[ 'front' ][ 'plus' ];
		$front_offset_minus     = $variance_offset[ 'front' ][ 'minus' ];
		$rear_offset_plus       = $variance_offset[ 'rear' ][ 'plus' ];
		$rear_offset_minus      = $variance_offset[ 'rear' ][ 'minus' ];
		$universal_offset_plus  = $variance_offset[ 'universal' ][ 'plus' ];
		$universal_offset_minus = $variance_offset[ 'universal' ][ 'minus' ];

		$front_width_plus      = $variance_width[ 'front' ][ 'plus' ];
		$front_width_minus     = $variance_width[ 'front' ][ 'minus' ];
		$rear_width_plus       = $variance_width[ 'rear' ][ 'plus' ];
		$rear_width_minus      = $variance_width[ 'rear' ][ 'minus' ];
		$universal_width_plus  = $variance_width[ 'universal' ][ 'plus' ];
		$universal_width_minus = $variance_width[ 'universal' ][ 'minus' ];

		// ****** DYNAMIC WIDTH/OFFSET VARIANCES (maybe override defaults)********
		// note: most of these only override universal sizes and do not apply to staggered

		$all_width_variances  = null;
		$all_offset_variances = null;

		switch ( strtolower( $bolt_pattern ) ) {
			case '5x139.7':
				$universal_width_minus  = 2;
				$universal_width_plus   = 2;
				$universal_offset_minus = 25;
				$universal_offset_plus  = 25;
				break;
			case '5x135':
				$universal_width_minus  = 2;
				$universal_width_plus   = 2;
				$universal_offset_minus = 25;
				$universal_offset_plus  = 25;
				break;
			case '5x114.3':
				/// targeting mustang GT 5.0 rear wheels with 10mm offset
				/// note: default offset is 10 at the time of writing this (increased from 7)
				/// however.. it may go back down in the future.
				/// update: default offset was increased to 15mm and this was 10, so as a result
				/// I increased this up from 10mm to 15mm. then i decided this is all stupid, and why
				/// don't I just comment this out, but then I realized I just told the client this has
				/// custom rules and is hardcoded to 15 so now if the default changes again they
				/// may expect this to remain at 15. fun stuff... i'm just going to leave it on.
				$rear_offset_minus = 15;
				$rear_offset_plus = 15;
				break;
			case '5x150':
				$universal_width_minus  = 2;
				$universal_width_plus   = 2;
				$universal_offset_minus = 50;
				$universal_offset_plus  = 50;
				break;
			case '6x139.7':
				$universal_width_minus  = 2;
				$universal_width_plus   = 2;
				$universal_offset_minus = 25;
				$universal_offset_plus  = 25;
				break;
			case '6x135':
				$universal_width_minus  = 2;
				$universal_width_plus   = 2;
				$universal_offset_minus = 35;
				$universal_offset_plus  = 35;
				break;
		}

		// ie. the first number in the bolt pattern
		switch ( $stud_holes ) {
			case 8:
			case 10:
				$universal_width_minus  = 2;
				$universal_width_plus   = 2;
				$universal_offset_minus = 25;
				$universal_offset_plus  = 25;
				break;
		}

		// Renegade/Wrangler... make sure this overrides bolt pattern stuff above.
		if ( $make === 'jeep' ) {

			if ( $model === 'renegade' || $model === 'wrangler' ) {
				$universal_width_minus  = 2;
				$universal_width_plus   = 2;
				$universal_offset_minus = 60;
				$universal_offset_plus  = 60;
			}

		}

		if ( $wheel_set->is_staggered() ) {

			$ret[ 'rims' ][ 'front' ] = array(
				'bolt_pattern' => $bolt_pattern,
				'center_bore' => $center_bore,
				'diameter' => $wheel_set->front->get_diameter(),
				'offset' => $wheel_set->front->get_offset(),
				'width' => $wheel_set->front->get_rim_width(),
				'width_plus' => $front_width_plus,
				'width_minus' => $front_width_minus,
				'offset_plus' => $front_offset_plus,
				'offset_minus' => $front_offset_minus,
				'loc' => 'front', // may or may not need this
			);

			$ret[ 'rims' ][ 'rear' ] = array(
				'bolt_pattern' => $bolt_pattern,
				'center_bore' => $center_bore,
				'diameter' => $wheel_set->rear->get_diameter(),
				'offset' => $wheel_set->rear->get_offset(),
				'width' => $wheel_set->rear->get_rim_width(),
				'width_plus' => $rear_width_plus,
				'width_minus' => $rear_width_minus,
				'offset_plus' => $rear_offset_plus,
				'offset_minus' => $rear_offset_minus,
				'loc' => 'rear', // may or may not need this
			);

		} else {

			$ret[ 'rims' ][ 'universal' ] = array(
				'bolt_pattern' => $bolt_pattern,
				'center_bore' => $center_bore,
				// wheel sets use only front/rear, not universal
				'diameter' => $wheel_set->front->get_diameter(),
				'offset' => $wheel_set->front->get_offset(),
				'width' => $wheel_set->front->get_rim_width(),
				'width_plus' => $universal_width_plus,
				'width_minus' => $universal_width_minus,
				'offset_plus' => $universal_offset_plus,
				'offset_minus' => $universal_offset_minus,
				'loc' => 'universal', // may or may not need this
			);

		}

		queue_dev_alert( '---- Rim Size Plus Dynamic Variances ----', get_pre_print_r( $ret ) );

		return $ret;
	}

	/**
	 * For tires, Wheel_Set is sufficient for all fitment info, but for rims we need a Fitment also.
	 *
	 * @param Fitment_General $fitment
	 * @param Wheel_Set       $wheel_set
	 *
	 * @return array
	 */
	public static function get_size_from_fitment_and_wheel_set( Fitment_General $fitment, Wheel_Set $wheel_set ) {

		// this contains indexes like staggered, oem, fitment_slug, sub_slug
		$ret = $wheel_set->get_default_size_array();

		// you may notice that both the rim sizes and the tire sizes have the same information
		// in the get_default_size_array(). This is kind of weird, but its required so that
		// the tire size array and the rim size array can both work perfectly fine on their own,
		// but in the case of this function we want both values returned at once, so when
		// we merge arrays, some of the data is in fact repeated.

		$ret = array_merge( $ret, $wheel_set->get_tire_sizes_array() );
		$ret = array_merge( $ret, $fitment::get_rim_size_array( $fitment, $wheel_set ) );

		// Example return value
		//
		// $ret['staggered'] = true
		// $ret['oem'] = true/false
		// $ret['tires']['front'] = array(...)
		// $ret['tires']['rear'] = array(...)
		// $ret['rims']['front'] = array(...)
		// $ret['rims']['rear'] = array(...)
		//
		// OR....
		//
		// $ret['staggered'] = false
		// $ret['oem'] = true/false
		// $ret['tires']['universal'] = array(...)
		// $ret['rims']['universal'] = array(...)

		return $ret;
	}
}
