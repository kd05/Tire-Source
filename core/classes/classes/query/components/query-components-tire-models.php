<?php

/**
 * Class Query_Components_Tire_Models
 */
Class Query_Components_Tire_Models extends Query_Components {

	/**
	 * Query_Components_Tire_Models constructor.
	 *
	 * @param string $table
	 * @param bool   $prefixed
	 */
	public function __construct( $table, $prefixed = false ) {
		parent::__construct( $table, $prefixed );
	}

    /**
     * @param $filters
     */
	public function apply_all_filters( $filters ) {

        $allow_array = true;
        $this->apply_filter( $filters, 'id', 'get_id', $allow_array );
        $this->apply_filter( $filters, 'slug', 'get_slug', $allow_array );
        $this->apply_filter( $filters, 'type', 'get_type', $allow_array );
        $this->apply_filter( $filters, 'class', 'get_class', $allow_array );
        $this->apply_filter( $filters, 'category', 'get_category', $allow_array );
        $this->apply_filter( $filters, 'run_flat', 'get_run_flat', $allow_array );
    }

    /**
     * @param $v
     */
    public function get_id( $v ) {
        return $this->simple_equality( 'tire_model_id', $v, '%d' );
    }

	/**
	 * @param $v
	 */
	public function get_slug( $v ) {
		return $this->simple_equality( 'tire_model_slug', $v, '%s' );
	}

	/**
	 * @param $v
	 */
	public function get_type( $v ) {
		return $this->simple_equality( 'tire_model_type', $v, '%s' );
	}

	/**
	 * @param $v
	 */
	public function get_class( $v ) {
		return $this->simple_equality( 'tire_model_class', $v, '%s' );
	}

	/**
	 * @param $v
	 */
	public function get_category( $v ) {
		return $this->simple_equality( 'tire_model_category', $v, '%s' );
	}

	/**
	 * @param $v
	 */
	public function get_run_flat( $v ) {
		return $this->simple_equality( 'tire_model_run_flat', $v, '%s' );
	}
}