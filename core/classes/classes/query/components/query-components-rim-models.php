<?php

/**
 * Class Query_Components_Rim_Models
 */
Class Query_Components_Rim_Models extends Query_Components {

	/**
	 * Query_Components_Rim_Models constructor.
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
    }

    /**
     * @param $v
     */
    public function get_id( $v ) {
        return $this->simple_equality( 'rim_model_id', $v, '%d' );
    }

    /**
	 * This db col is in rims table as well as rim brands table
	 *
	 * @param $v
	 */
	public function get_slug( $v ) {
		return $this->simple_equality( 'rim_model_slug', $v, '%s' );
	}
}