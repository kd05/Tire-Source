<?php

Class Wheel_Set_Sub extends Wheel_Set {

	/**
	 * Wheel_Set_Sub constructor.
	 *
	 * @param array $data
	 */
	public function __construct( $data = array() ) {
		parent::__construct( $data );
	}

	/**
	 * NOTE: We will probably store this in $this->name, therefore you probably shouldn't use this function,
	 * except when constructing an object.
	 *
	 * The name for the substitution size when $this IS a substitution wheel set.
	 * This requires a parent wheel set. Non substitution wheel sets do not have parent wheel sets.
	 *
	 * @return string
	 */
	public function generate_sub_name() {

		if ( ! $this->is_sub() ) {
			return '';
		}

		$op = '';
		$op .= $this->generate_sub_name_front();

		if ( $this->parent->is_staggered() ) {
			$op .= ' / ';
			$op .= $this->generate_sub_name_rear();
		}

		return $op;
	}

	/**
	 * @param bool   $sq_brackets
	 * @param string $inches
	 * @param string $stg_sep
	 *
	 * @return string
	 */
	public function get_plus_minus_text( $sq_brackets = true, $inches = '"', $stg_sep = ' / '){
		$f = $this->get_plus_minus_front();
		$r = $this->is_staggered() ? (int) $this->get_plus_minus_rear() : null;
		return get_plus_minus_text( $f, $r, $this->is_staggered(), $sq_brackets, $inches, $stg_sep );
	}

	/**
	 * @return int
	 */
	public function get_plus_minus_front(){
		$v = (int) $this->front->get_diameter() - $this->parent->front->get_diameter();
		return $v;
	}

	/**
	 * @return int
	 */
	public function get_plus_minus_rear(){

		if ( $this->is_staggered() && $this->parent->is_staggered() ) {
			$v = (int) $this->rear->get_diameter() - $this->parent->rear->get_diameter();
			return $v;
		}

		return null;
	}

	/**
	 * this basically has to copy logic from generate_sub_name_front(), make sure
	 * they are the same.
	 */
	public function generate_sub_name_rear() {

		if ( ! $this->is_sub() ) {
			return '';
		}

		if ( ! $this->parent->is_staggered() ) {
			return '';
		}

		$is_zr = false;
		$is_lt = false;

		if ( strpos( $this->parent->rear->tire, 'ZR' ) !== false ) {
			$is_zr = true;
		}

		if ( strpos( $this->parent->rear->tire, 'LT' ) !== false || $this->parent->rear->tire_sizing_system == 'lt-metric' ) {
			$is_lt = true;
		}

		$w = $this->rear->get_width();
		$p = $this->rear->get_profile();
		$d = $this->rear->get_diameter();

		$sr = $this->rear->speed_rating;
		$li = $this->rear->load_index;

		return get_tire_name( $w, $p, $d, $sr, $li, $is_zr, $is_lt );
	}

	/**
	 *
	 */
	public function generate_sub_name_front() {

		if ( ! $this->is_sub() ) {
			return '';
		}

		$is_zr = false;
		$is_lt = false;

		if ( strpos( $this->parent->front->tire, 'ZR' ) !== false ) {
			$is_zr = true;
		}

		if ( strpos( $this->parent->front->tire, 'LT' ) !== false || $this->parent->front->tire_sizing_system == 'lt-metric' ) {
			$is_lt = true;
		}

		$w = $this->front->get_width();
		$p = $this->front->get_profile();
		$d = $this->front->get_diameter();

		$sr = $this->front->speed_rating;
		$li = $this->front->load_index;

		return get_tire_name( $w, $p, $d, $sr, $li, $is_zr, $is_lt );
	}
}

