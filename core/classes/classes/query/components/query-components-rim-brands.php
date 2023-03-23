<?php

/**
 * Class Query_Components_Rim_Brands
 */
Class Query_Components_Rim_Brands extends Query_Components {

	/**
	 * Query_Components_Rim_Brands constructor.
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
        $this->apply_filter( $filters, 'id','get_id', $allow_array);
        $this->apply_filter( $filters, 'slug', 'get_slug', $allow_array );
    }

    /**
     * @param $v
     */
    public function get_id( $v ) {
        return $this->simple_equality( 'rim_brand_id', $v, '%d' );
    }

	/**
	 * This db col is in rims table as well as rim brands table
	 *
	 * @param $v
	 */
	public function get_slug( $v ) {
		return $this->simple_equality( 'rim_brand_slug', $v, '%s' );
	}
}

/**
 * Class Query_Components_Rim_Brands
 */
Class Query_Components_Rim_Finishes extends Query_Components {

	/**
	 * Query_Components_Rim_Brands constructor.
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
        $this->apply_filter( $filters, 'color_1', 'get_color_1', $allow_array );
        $this->apply_filter( $filters, 'color_2', 'get_color_2', $allow_array );
        $this->apply_filter( $filters, 'finish', 'get_finish', $allow_array );
    }

    /**
     * @param $v
     */
    public function get_id( $v ) {
        return $this->simple_equality( 'rim_finish_id', $v, '%d' );
    }

    /**
	 * @param $v
	 */
	public function get_color_1( $v ) {
		return $this->simple_equality( 'color_1', $v, '%s' );
	}

	/**
	 * @param $v
	 */
	public function get_color_2( $v ) {
		return $this->simple_equality( 'color_2', $v, '%s' );
	}

	/**
	 * @param $v
	 */
	public function get_finish( $v ) {
		return $this->simple_equality( 'finish', $v, '%s' );
	}
}