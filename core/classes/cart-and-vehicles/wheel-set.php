<?php

/**
 * A Wheel_Set contains two Wheel_Pairs (one front, one back) (a wheel pair tire + rim).
 *
 * $showing_fp_only, means that the Rear Wheel_Pair is empty and redundant (use the front instead).
 *
 * if $showing_fp_only is false, then Front/Rear Wheel_Pairs do not contain the same information, and we have a
 * staggered fitment.
 *
 * Although this class can function on its own, the value of $is_stock is lost if its not contained within a Vehicle.
 *
 * Class CP_Fitment_Data_Wheel_Set
 */
Class Wheel_Set extends Export_As_Array {

	/**
	 * A possible plus or minus size, if one is defined by the user.
	 *
	 * this plus/minus size is also an instance of this class.
	 *
	 * @var  Wheel_Set_Sub|null
	 */
	public $wheel_set_sub;

	/**
	 * Array of possible substitution sizes,
	 *
	 * @var Wheel_Set_Sub[]
	 */
	public $all_wheel_set_subs;

	/**
	 * circular dependency...
	 *
	 * If $this->is_sub() then $this->parent->wheel_set_sub === $this, otherwise
	 * if $this->is_parent() and $this->wheel_set_sub, then $this->wheel_set_sub->parent
	 * === $this.
	 *
	 * @var  Wheel_Set_Parent|null
	 */
	public $parent;

	/**
	 * ie. is "OEM"
	 *
	 * @var
	 */
	public $is_stock;

	/**
	 * The opposite is is "staggered"
	 *
	 * @var
	 */
	public $showing_fp_only; // the opposite of "staggered"

	/**
	 * (non-sub size) fitment slug, for Wheel_Set only
	 * @var
	 */
	public $slug;

	/**
	 * Name of the fitment size using this wheel set, Ie.
	 * 225/50R18 (91 V), OR 225/50R18 (91 V) / 245/45R18 (91 V)
	 *
	 * @var
	 */
	public $name;

	/**
	 * Front tire/wheel name, used to create $this->name
	 *
	 * @var
	 */
	public $name_front;

	/**
	 * Rear tire/wheel name, used to create $this->name (if staggered)
	 * @var
	 */
	public $name_rear;

	/**
	 * Extended tire size information broken down into front and rear.
	 *
	 * This repeats some data. We generally rely on the data NOT found within this
	 * property as the "correct" data. Of course, it should be identical. The purpose of this
	 * is to hold tire sizing attributes in a light weight object that is not aware of vehicles or fitments,
	 * but only the tire sizing attributes that are applicable to substitution sizes. Ie.
	 * $this->tire_atts_pair->front->diameter should be equal to $this->front->get_diameter()
	 *
	 *
	 * Another note: each item in $db_subs, is a DB_Sub_Size object, which should also have its own
	 * Tire_Atts_Pair object.
	 *
	 * @var  Tire_Atts_Pair
	 */
	public $tire_atts_pair;


	/**
	 * This is where sizing data is held the for the "selected fitment". Vehicle data
	 * is stored in the Fitment object (ie. $this). Data for the selected wheel and tire sizes
	 * are stored in a wheel pair.
	 *
	 * @var  Wheel_Pair
	 */
	public $front;

	/**
	 * Rear tire/wheel data for staggered fitments.
	 *
	 * @var  Wheel_Pair
	 */
	public $rear;

	/**
	 * For sub sizes, we cannot make the diameter lower than the lowest diameter
	 * recommended by our vehicle API. Ie. not lower than any of the $vehicle->fitment_plural->wheel_sets
	 * array (which is an array of Wheel_Set objects). So if a user is given 16, 17, 18 options initially,
	 * and they select an option. We might show 16, 17, 18, 19, 20 as possible sub diameters from what they picked.
	 *
	 * @var
	 */
	public $min_sub_diameter;

	/**
	 * Class properties used in $this->to_array(). $this->to_array() generates the array stored in cart (in the
	 * session), and therefore, must also be sufficient to re-instantiate this object without any data loss, via the
	 * constructor.
	 *
	 * @var array
	 */
	protected $props_to_export = array(
		'is_stock',
		'showing_fp_only',
		'wheel_set_sub',
		'slug',
		'name',
		'name_front',
		'name_rear',
		'front',
		'rear',
		'min_sub_diameter',
	);

	/**
	 * CP_Fitment_Data_Wheel_Set constructor.
	 */
	public function __construct( $data ) {
		// note: parent::__construct() calls $this->init( $data );
		parent::__construct( $data );

		if ( ! $this->is_parent() && ! $this->is_sub() ) {
			throw_dev_error( 'Wheel Sets must be parents or substitution sizes which belong to parents. If you need a wheel set for some general purpose, just make it a parent.' );
		}

		// we would like to run this here, but we cannot. Sometimes, additional actions after __construct()
		// are required before the object is in a state that is valid. Therefore, leaving this here so you know
		// not to do this right here, but recommend that you do it once on each object you create at some point
		// before using it.
		// $this->validate_dev_errors();
	}

	/**
	 * @param $data
	 */
	public function init( $data ) {

		// note: for Wheel_Set_Sub, these data points could be empty or incorrect (cloned from their parent),
		// but in the process of creating the object, they will have their values corrected at some point after this.
		$this->name       = gp_if_set( $data, 'name', null );
		$this->name_front = gp_if_set( $data, 'name_front', null );
		$this->name_rear  = gp_if_set( $data, 'name_rear', null );

		// note: for Wheel_Set_Parent, this is a "fitment slug" which relates to our vehicle api data,
		// if this is a Wheel_Set_Sub, then slug is the substitution size slug, which relates to data on our sub_sizes table.
		$this->slug = gp_if_set( $data, 'slug' );

		$this->is_stock        = gp_if_set( $data, 'is_stock', null );
		$this->showing_fp_only = gp_if_set( $data, 'showing_fp_only', null );

		$this->min_sub_diameter = gp_if_set( $data, 'min_sub_diameter', null );

		// check slug exists too, because IIRC we may once in a while create this object with empty data.
		// nevermind, just don't do this. when we make sub sizes we might make an instance with empty data,
		// then add data. or make the instance with partial data, then add/change. point is.. its just not good
		// to treat this as an error, which is crappy because in the end this value should never truly be zero,
		// and so, we don't have a valid way to detect it.
		//		if ( ! $this->min_sub_diameter && $this->slug && $this->name ) {
		//			log_data( $this, 'invalid-min-sub-diameter' );
		//		}

		$front       = gp_if_set( $data, 'front', array() );
		$this->front = $front instanceof Wheel_Pair ? $front : new Wheel_Pair( $front, 'front' );

		$rear       = gp_if_set( $data, 'rear', array() );
		$this->rear = $rear instanceof Wheel_Pair ? $rear : new Wheel_Pair( $rear, 'rear' );

		$this->tire_atts_pair = $this->get_tire_atts_pair_object();

		// The plan here is to store the entire wheel set substitution size in the cart in an array
		// therefore, its possible to use that array to create the sub size (ie. new Wheel_Set_Sub( $data['wheel_set_sub'] )
		// However, our singular fitment objects are always going to re-create the wheel_set_subs via the
		// slug that is provided, when the php script is executed.
		// therefore, think that when initializing a Wheel_Set_Parent, we will ignore whatever is found
		// in $data['wheel_set_sub']. The same data will be put back there so long as the substitution size
		// that corresponds to the slug still exists in the database.
		// the main reason i am pointing this out is because most of the objects (relating to the cart, like this one)
		// are exported to an array and stored in the cart in $_SESSION, and then re-instantiated again from that array,
		// rather than their primitive values and api/database hits. But in this case, I think we're going to go
		// a different route.
		// its also worth noting that the slug itself is basically has 100% sufficient information
		// to re-generate the substitution wheel set in pretty much any circumstances.
		// the only information its missing is how we determined the rim width, which comes from a simple formula anyways.
		if ( $this->is_parent() && isset( $data[ 'wheel_set_sub' ] ) ) {
			// $this->wheel_set_sub = new Wheel_Set_Sub( $data['wheel_set_sub'] );
		}

		// Note: right here is NOT the place to do this.
		// We will do this manually, when its needed. Most likely when we first create a vehicle
		// from user input, and that user input has a valid vehicle, has a selected fitment, and
		// has a selected substitution size.
		//		if ( $this->is_parent() ) {
		//			$this->run_substitution_operations();
		//		}
	}

	/**
	 * Need this logic for both rims and tires and don't want to repeat it, so we'll stick it here.
	 *
	 * @param      $stg_sep
	 * @param bool $is_oem - applies to non-sub sizes maybe
	 *
	 * @return string
	 */
	public function get_product_table_fitment_name_html( $stg_sep, $is_oem = false ) {

		// $is_oem = true;

		$ret        = '';
		$front_html = '';
		$rear_html  = '';

		if ( $this->is_sub() && $this instanceof Wheel_Set_Sub ) {

			$front_html .= '<p class="plus-minus">' . get_plus_minus_text_from_int( $this->get_plus_minus_front() ) . '</p>';
			$front_html .= '<p>' . $this->get_name_front( false ) . '</p>';

			if ( $this->is_staggered() ) {

				$rear_html .= '<p class="plus-minus">' . get_plus_minus_text_from_int( $this->get_plus_minus_rear() ) . '</p>';
				$rear_html .= '<p>' . $this->get_name_rear( false ) . '</p>';
			}

		} else {

			$oem_str = $is_oem ? ' (OEM)' : '';

			$name_front = $this->get_name_front( false ) . $oem_str;
			$front_html .= '<p>' . $name_front . '</p>';

			if ( $this->is_staggered() ) {
				$name_rear = $this->get_name_front( false ) . $oem_str;
				$rear_html .= '<p>' . $name_rear . '</p>';
			}
		}

		if ( $front_html ) {
			$ret .= $front_html;
		}

		if ( $rear_html ) {
			$ret .= $stg_sep;
			$ret .= $rear_html;
		}

		return $ret;
	}

	/**
	 * Note: $sub_slug could be user input and could be non-empty and invalid.
	 *
	 * The sub slug must correspond to a size entered into the database.
	 *
	 * Also, this fn. ensures the sub slug is valid. You can pass in raw user input as a $sub_slug,
	 * just ensure $sub_slug is not empty before calling the fn.
	 *
	 * @param $sub_slug
	 */
	public function apply_substitution_size_via_slug( $sub_slug ) {

		// throw an error here so we force the dev to know and handle the difference between
		// "user did not specify a slug" and "user specified a slug but it wasn't valid"
		if ( ! $sub_slug ) {
			throw_dev_error( 'Must provide a slug to apply a sub size' );
			exit;
		}

		if ( ! $this->is_parent() ) {
			throw_dev_error( 'Cannot apply sub sizes to sub sizes' );
			exit;
		}

		// Note: a "pair" means front and rear tire sizes
		$sub_atts_pair = Tire_Atts_Pair::create_from_url_slug( $sub_slug );

		// we'll have just one "min sub diameter". At this point if the vehicle
		// has staggered fitments, we will probably make this the lowest of all
		// front/rear diameters available.
		if ( $sub_atts_pair->front->diameter < $this->min_sub_diameter ) {
			return false;
		}

		if ( $this->is_staggered() && $sub_atts_pair->rear->diameter < $this->min_sub_diameter ) {
			return false;
		}

		if ( ! $sub_atts_pair->valid ) {
			return false;
		}

		// by submitting the target size ($this->tire_atts_pair), and the $sub_atts_pair, we know
		// that sub_slug is valid in the sense that it is present in the sub_sizes database table
		$sub_size = query_results_get_first( get_sub_sizes( $this->tire_atts_pair, $sub_atts_pair ) );

		$db_sub = DB_Sub_Size::create_instance_or_null( $sub_size );

		if ( ! $db_sub ) {
			return false;
		}

		// maybe ensures not too much variance or something like that
		if ( ! $db_sub->is_valid_for_front_end_user() ) {
			return false;
		}

		if ( $this instanceof Wheel_Set_Parent ) {

			$this->wheel_set_sub = $this->create_substitution_wheel_set( $db_sub );

			if ( $this->wheel_set_sub ) {
				return true;
			} else {
				return false;
			}

		}

		return false;
	}

	/**
	 *
	 */
	public function is_parent() {

		if ( $this instanceof Wheel_Set_Sub ) {
			return false;
		}

		if ( $this instanceof Wheel_Set_Parent ) {
			return true;
		}

		return null;
	}

	/**
	 * @return bool|null
	 */
	public function is_sub() {

		if ( $this instanceof Wheel_Set_Sub ) {
			return true;
		}

		if ( $this instanceof Wheel_Set_Parent ) {
			return false;
		}

		// this basically means false. wheel sets should be parent or subs, not neither.
		// but in case they are, the answer to is_sub() is "no"
		return null;
	}

	/**
	 * Ideally, this should be run after __construct(), to ensure we have no data
	 * inconsistencies. However, we can't do that because when we create Sub sizes from
	 * parent sizes, there has to be a period of time that the object is actually not in a valid state,
	 * until we adjust all the properties so that it is. Therefore, we can call this as needed, but ideally
	 * only once, once the object is in its final state and should no longer be mutable. The purpose of
	 * this function is to reduce the change of developer error, and hopefully eliminate any
	 * silent bugs that would otherwise go unnoticed.
	 */
	public function validate_dev_errors() {

		// possibly repeated in __construct() but that's fine
		if ( ! $this->is_parent() && ! $this->is_sub() ) {
			throw_dev_error( 'Must be parent/sub. If you are unsure what this means, make a parent and ignore sub.' );
			exit;
		}

		if ( $this->is_parent() ) {
			if ( $this->wheel_set_sub ) {
				if ( ! $this->wheel_set_sub->is_sub() ) {
					throw_dev_error( 'Parent specifies sub size, but sub size is not a Wheel_Set_Sub' );
					exit;
				}
			}
		}

		if ( $this->is_sub() ) {

			// this feels dirty.. forcing a circular dependency.
			// However, some of the code relies on it, so better to catch it always,
			// rather than just sometimes.
			if ( ! $this->parent ) {
				throw_dev_error( 'A sub size must point to its parent.' );
			}
		}
	}

	/**
	 * Fitment name is just the name for parents, but for sub sizes
	 * we may want to indicate the parent we originated from.
	 */
	public function get_fitment_and_or_sub_name( $include_plus_minus = true ) {

		if ( $this->is_sub() ) {
			$op = '';

			// we could show base size followed by sub size but I think this will confuse some people..
			// instead... hopeully $include_plus_minus will be true, which helps to indicate we're using a sub size
			//			$op .= $this->parent->get_name();
			//			$op .= ' : ';
			$op .= $this->get_name( $include_plus_minus );

			return $op;
		}

		// for parents..
		return $this->get_name();
	}

	/**
	 * Trivial function right now. But maybe later it will be better to use
	 * a method rather than a property. For example, we can run a 'generate name'
	 * function if $this->name is null, if objects don't always have names.
	 *
	 * @return mixed
	 */
	public function get_name( $include_plus_minus = true ) {
		$op = '';

		if ( $include_plus_minus && $this->is_sub() ) {
			$op .= $this->get_plus_minus_text();
			$op .= ' ' . $this->name;
		} else {
			$op = $this->name;
		}

		return $op;
	}

	/**
	 * @param bool $with_plus_minus
	 *
	 * @return string
	 */
	public function get_name_front( $with_plus_minus = true ) {

		$op = '';

		// check instance so editor knows fn. exists
		if ( $with_plus_minus && $this->is_sub() && $this instanceof Wheel_Set_Sub ) {
			$op .= get_plus_minus_text_from_int( $this->get_plus_minus_front() );
			$op .= ' ';
		}

		$op .= $this->name_front;

		return $op;
	}

	/**
	 * @param bool $with_plus_minus
	 *
	 * @return string
	 */
	public function get_name_rear( $with_plus_minus = true ) {

		$op = '';

		// check instance so editor knows fn. exists
		if ( $with_plus_minus && $this->is_sub() && $this instanceof Wheel_Set_Sub ) {
			$op .= get_plus_minus_text_from_int( $this->get_plus_minus_rear() );
			$op .= ' ';
		}

		$op .= $this->name_rear;

		return $op;
	}


	/**
	 * .. used to call these different things. now the slug has diff. meaning
	 * based on whether $this is parent/sub
	 *
	 * @return mixed
	 */
	public function get_slug() {

		if ( $this->is_sub() ) {
			return $this->slug;
		}

		return $this->slug;
	}

	/**
	 * Add 'is_sub' to array representation of each wheel set object. This is because
	 * wheel sets stored in an array don't know about their instances. Note: we probably do not
	 * rely on the value of is_sub in the resulting array. Instead, wheel sets are subs if they belong to
	 * a parent wheel set... its complicated. Point is, this is informational. We may log it in the cart
	 * array when orders are submitted.
	 */
	public function to_array() {

		$arr = parent::to_array();

		if ( ! isset( $arr[ 'is_sub' ] ) ) {
			$arr[ 'is_sub' ] = $this->is_sub() ? '1' : '';
		}

		return $arr;
	}

	/**
	 * holds sizing information including overall dimensions, and is sometimes
	 * passed around to other functions as a summary of data, even though it may repeat
	 * some information from the wheel set.
	 *
	 * @return Tire_Atts_Pair|null
	 */
	public function get_tire_atts_pair_object() {

		$w1 = $this->front->get_width();
		$p1 = $this->front->get_profile();
		$d1 = $this->front->get_diameter();

		$w2 = $this->is_staggered() ? $this->rear->get_width() : '';
		$p2 = $this->is_staggered() ? $this->rear->get_profile() : '';
		$d2 = $this->is_staggered() ? $this->rear->get_diameter() : '';

		$pair = Tire_Atts_Pair::create_manually( $w1, $p1, $d1, $w2, $p2, $d2 );

		return $pair;
	}

	/**
	 * Gives you $this, or $this->wheel_set_sub.
     *
	 * @return $this|Wheel_Set_Sub
	 */
	public function get_selected() {

		// substitution wheel sets should not have other substitution wheel sets
		if ( $this->is_sub() ) {
			return $this;
		}

		if ( $this->wheel_set_sub ) {
			return $this->wheel_set_sub;
		}

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function is_oem() {
		return $this->is_stock;
	}

	/**
	 * @return bool
	 */
	public function is_staggered() {
		$ret = ! $this->showing_fp_only;

		return $ret;
	}

	/**
	 * Gets sizes that can be passed into a query.
	 *
	 * Make sure you check is_staggered() first so you know what to expect in the return value.
	 *
	 * @return array
	 */
	public function get_tire_sizes_array() {

		$ret = $this->get_default_size_array();

		$ret[ 'oem' ] = ( $this->is_oem() ) ? true : false;

		if ( $this->is_staggered() ) {
			$ret[ 'staggered' ]        = true;
			$ret[ 'tires' ][ 'front' ] = $this->get_front_tire_size_array( 'front' );
			$ret[ 'tires' ][ 'rear' ]  = $this->get_rear_tire_size_array();
		} else {
			$ret[ 'staggered' ]            = false;
			$ret[ 'tires' ][ 'universal' ] = $this->get_front_tire_size_array( 'universal' );
		}

		return $ret;
	}

	/**
	 * returns an array containing indeces for staggered, oem, sub_slub, and fitment_slug.
	 *
	 * Generally speaking, ALL queries that require a tire or rim size REQUIRE these to be set.
	 *
	 * Therefore, when exporting a tire size array, or a rim size array, or a tire AND rim size array,
	 * you want to start with this, and add indeces for 'tires', 'rims', or both.
	 *
	 * @param Wheel_Set $wheel_set
	 */
	public function get_default_size_array() {

		$ret = array();
		$ret['staggered'] = $this->is_staggered();
		$ret['oem'] = $this->is_oem();

		// we have to actually pass these into the size array, so that SQL can "select" them
		// "AS" fitment_slug/sub_slug, so that when we group our queries (especially those run
		// with multiple sizes), we know which size the particular product originated from.
		// we especially need this on the single product pages when we shove a bunch of sizes
		// into a query, and then all of the sudden have to show an add to cart button
		// that needs to know what size the product is associated with.
		if ( $this->is_sub() ) {
			$ret['sub_slug'] = $this->get_slug();
			$ret['fitment_slug'] = $this->parent->get_slug();
		} else {
			$ret['fitment_slug'] = $this->get_slug();
		}

		return $ret;
	}

	/**
	 * Check is_staggered() first.
	 *
	 * front size is "universal" size when not staggered.
	 *
	 * @param string $loc - 'front' or 'universal'
	 *
	 * @return array
	 */
	public function get_front_tire_size_array( $loc = 'front' ) {

		$ret = array(
			'width' => $this->front->get_width(),
			'diameter' => $this->front->get_diameter(),
			'profile' => $this->front->get_profile(),
			'speed_rating' => $this->front->speed_rating,
			'load_index' => $this->front->load_index,
			'tire_sizing_system' => $this->front->get_tire_sizing_system(),
			'loc' => $loc,
		);

		return $ret;
	}

	/**
	 * Check is_staggered() first
	 */
	public function get_rear_tire_size_array() {

		// DO NOT return an empty array. other code relies on this function returning certain array keys.
		$ret = array(
			'width' => null,
			'diameter' => null,
			'profile' => null,
			'speed_rating' => null,
			'load_index' => null,
			'tire_sizing_system' => null,
			'loc' => 'rear',
		);

		if ( $this->is_staggered() ) {
			$ret = array(
				'width' => $this->rear->get_width(),
				'diameter' => $this->rear->get_diameter(),
				'profile' => $this->rear->get_profile(),
				'speed_rating' => $this->rear->speed_rating,
				'load_index' => $this->rear->load_index,
				'tire_sizing_system' => $this->rear->get_tire_sizing_system(),
				'loc' => 'rear',
			);
		}

		return $ret;
	}
}

