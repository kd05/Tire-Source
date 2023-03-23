<?php

/**
 * A Wheel_Set_Parent should correspond to fitment data returned from our
 * vehicle API. It may have a Wheel_Set_Sub, which is a partial clone of its self,
 * with some properties changed. It's odd because, Wheel_Set_Sub both inherits from
 * Wheel_Set, and Wheel_Set_Parent is composed of Wheel_Set_Sub. So we're doing
 * both inheritance and composition. So, what we'll do is split up the Parent and Sub
 * classes because otherwise you have functions like get_sub_wheel_set() which is needed
 * for parent, but totally not good when its available to a wheel set that is already a sub.
 * We're working here on the principle that although Wheel Sets can have Wheel Sets, we expect
 * that each Wheel Set Parent has either 0 or exactly 1 Wheel Set Sub. And a Wheel Set Sub
 * does not have any further Wheel Set Subs.
 *
 * Class Wheel_Set_Parent
 */
Class Wheel_Set_Parent extends Wheel_set{

	/**
	 * Wheel_Set_Parent constructor.
	 *
	 * @param array $data
	 */
	public function __construct( $data = array() ) {
		parent::__construct( $data );
	}

	/**
	 * @return string
	 */
	public function get_sub_slug(){

		if ( $this->is_parent() && $this->wheel_set_sub ) {
			return $this->wheel_set_sub->get_slug();
		}

		return '';
	}

	/**
	 * Ensures that you can reliably use $this->wheel_set_sub.
	 *
	 * @return bool
	 */
	public function has_substitution_wheel_set(){

		if ( $this->wheel_set_sub && $this->wheel_set_sub instanceof Wheel_Set_Sub ) {
			return true;
		}

		return false;
	}

	/**
	 * @param $sub_slug
	 */
	public function get_wheel_set_sub_by_slug( $sub_slug ) {

		if ( $this->all_wheel_set_subs ) {
			/** @var Wheel_Set $ws */
			foreach ( $this->all_wheel_set_subs as $ws ) {

				if ( $ws->get_slug() == $sub_slug ) {
					return $ws;
				}
			}
		}

		return null;
	}

	/**
	 * This should probably be run on SELECTED fitments only. Non-selected fitments
	 * probably never need to know about their available sub sizes. Running for all fitments
	 * could cause quite a few DB queries and a lot of work overall, given that some vehicles
	 * have 10-15 fitments already.
	 */
	public function generate_all_wheel_set_subs(){

		queue_dev_alert( 'Running sub size operations. before = ', get_pre_print_r( $this ) );

		$subs          = get_sub_sizes( $this->tire_atts_pair );

		$ret = array();

		if ( $subs ) {
			foreach ( $subs as $sub ) {

				$db_sub = DB_Sub_Size::create_instance_or_null( $sub );

				if ( ! $db_sub->is_valid_for_front_end_user() ) {
					continue;
				}

				if ( $db_sub->sub_size->front->diameter < $this->min_sub_diameter ) {
					queue_dev_alert( 'skipped_sub_size_front', get_pre_print_r( [ $this->min_sub_diameter, $db_sub ] ) );
					continue;
				}

				// sub_size->rear_diameter is zero if $this is not staggered
				if ( $this->is_staggered() && $db_sub->sub_size->rear->diameter < $this->min_sub_diameter ) {
					queue_dev_alert( 'skipped_sub_size_rear', get_pre_print_r( [ $this->min_sub_diameter, $db_sub ] ) );
					continue;
				}

				$ws = $this->create_substitution_wheel_set( $db_sub );
				$ws->validate_dev_errors();
				// index by slug, we will access these at some point via slug (probably single product page table)
				$ret[$ws->get_slug()] = $ws;

			}
		}

		queue_dev_alert( 'Running sub size operations. after = ', get_pre_print_r( $this ) );

		return $ret;
	}

	/**
	 * Creates a Wheel_Set_Sub, using a Wheel_Set_Parent and a single row from sub_sizes db table.
	 *
	 * @param DB_Sub_Size $db_sub
	 */
	public function create_substitution_wheel_set( DB_Sub_Size $db_sub ) {

		// queue_dev_alert( 'create sub wheel set', get_pre_print_r( $db_sub ) );

		$arr = $this->object_props_to_array( array(
			'is_stock',
			'showing_fp_only',
			'front',
			'rear',
			'min_sub_diameter',
			// not these!
			//			'slug',
			//			'sub_slug',
			//			'name',
			//			'name_front',
			//			'name_rear',
		) );

		// clone most information, including the Wheel_Pair's ($this->front, and $this->rear)
		// to a new Wheel Set Substitution object. Then, we'll make the necessary changes based on
		// information held in $db_sub.
		$wheel_set = new Wheel_Set_Sub( $arr );

		// child needs to have reference to parent
		$wheel_set->parent = $this;

		// Override the previous values for width, diameter, profile, and possibly rim width
		// DO THIS EARLY. other functions (like getting names) rely on this being done first.
		$wheel_set->front->set_diameter( $db_sub->sub_size->front->diameter );
		$wheel_set->front->set_tire_width( $db_sub->sub_size->front->width );
		$wheel_set->front->set_tire_profile( $db_sub->sub_size->front->profile );

		$rim_width_1 = get_suggested_substitution_size_rim_width( $db_sub->target_size->front, $db_sub->sub_size->front, $wheel_set->front->get_rim_width() );
		$wheel_set->front->set_rim_width( $rim_width_1 );

		if ( $wheel_set->parent->is_staggered() ) {

			$wheel_set->rear->set_diameter( $db_sub->sub_size->rear->diameter );
			$wheel_set->rear->set_tire_width( $db_sub->sub_size->rear->width );
			$wheel_set->rear->set_tire_profile( $db_sub->sub_size->rear->profile );

			$rim_width_2 = get_suggested_substitution_size_rim_width( $db_sub->target_size->rear, $db_sub->sub_size->rear, $wheel_set->rear->get_rim_width() );
			$wheel_set->rear->set_rim_width( $rim_width_2 );
		}

		// do this after size attributes are adjusted, but before getting names.
		$wheel_set->tire_atts_pair = $wheel_set->get_tire_atts_pair_object();

		// these were textual values from parent wheel set, and do not correspond
		// to the substitution size provided. So, let's get rid of them.
		$wheel_set->front->tire = null;
		$wheel_set->rear->tire = null;
		$wheel_set->front->rim = null;
		$wheel_set->rear->rim = null;

		// Setup the slug. This is how we identify the "current" wheel set when a user loads a new page
		// ie. this is $_GET['sub']
		$wheel_set->slug = $db_sub->sub_size->get_slug();

		// Setup new names now that sizes are changed
		$wheel_set->name       = $wheel_set->generate_sub_name();
		$wheel_set->name_front = $wheel_set->generate_sub_name_front();
		$wheel_set->name_rear  = $wheel_set->generate_sub_name_rear();

		$wheel_set->validate_dev_errors();

		queue_dev_alert( 'wheel set created', get_pre_print_r( $wheel_set ) );
		return $wheel_set;
	}

}