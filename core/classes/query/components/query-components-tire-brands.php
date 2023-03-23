<?php

/**
 * Class Query_Components_Tire_Brands
 */
Class Query_Components_Tire_Brands extends Query_Components {

	/**
	 * Query_Components_Tire_Brands constructor.
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
        return $this->simple_equality( 'tire_brand_id', $v, '%d' );
    }

    /**
	 * Note that this value is also in the tires table, so we can also
	 * use the function in query_components_tires
	 *
	 * @param $v
	 */
	public function get_slug( $v ) {
		return $this->simple_equality( 'tire_brand_slug', $v, '%s' );
	}
}