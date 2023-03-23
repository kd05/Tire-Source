<?php

/**
 * For grouped (by model) and non-grouped tire queries. However, not for tire queries
 * that return staggered results, ie when querying tires by sizes.
 *
 * Class Tires_Query_General
 */
Class Tires_Query_General extends Product_Query_Filter_Methods{

	public $order_by;
	public $group_by;

	protected $product_type = 'tires';

	/**
	 * Tires_Query_Grouped constructor.
	 */
	public function __construct( $args = array() ){
		parent::__construct( $args );

		// let our filter functions know what is available in each row of our result set
		$this->staggered_result = false;
		$this->result_has_tires = true;
		$this->result_has_rims = false;
	}

	/**
	 *
	 */
	public function get_results(){

		$sql = '';
		$db = get_database_instance();
		$params = array();

		$filters_tires = gp_if_set( $this->queued_filters, 'tires' );
		$filters_models = gp_if_set( $this->queued_filters, 'tire_models' );
		$filters_brands = gp_if_set( $this->queued_filters, 'tire_brands' );

		// tires
		$comp_tires = new Query_Components_Tires( 'tires' );
		$comp_tires->apply_filter( $filters_tires, 'part_number', 'get_part_number', true );
		$comp_tires->apply_filter( $filters_tires, 'part_number_not_in', 'get_part_number_not_in', true );

		// models
		$comp_models = new Query_Components_Tire_Models( 'models' );
		// for this one, we can say use the $comp_tires->get_model() function, or $comp_models->get_slug()
		$comp_models->builder->add_to_self( 'models.tire_model_id = tires.model_id' );
		$comp_models->apply_filter( $filters_models, 'slug', 'get_slug', true );
		$comp_models->apply_filter( $filters_models, 'type', 'get_type', true );
		$comp_models->apply_filter( $filters_models, 'class', 'get_class', true );
		$comp_models->apply_filter( $filters_models, 'category', 'get_category', true );

		// brands
		$comp_brands = new Query_Components_Tire_Brands( 'brands' );
		$comp_brands->builder->add_to_self( 'brands.tire_brand_id = tires.brand_id' );
		$comp_brands->apply_filter( $filters_brands, 'slug', 'get_slug', true );

		$select = array();
		$select[] = '*';

		// Select
		$sql .= $this->get_select_from_array( $select ) . ' ';
		$sql .= 'FROM ' . $db->tires . ' AS tires ';

		// Join
		$sql .= 'INNER JOIN ' . $db->tire_brands . ' AS brands ON ' . $comp_brands->builder->sql_with_placeholders() . ' ';
		$params = array_merge( $params, $comp_brands->builder->parameters_array() );


		$sql .= 'INNER JOIN ' . $db->tire_models . ' AS models ON ' . $comp_models->builder->sql_with_placeholders() . ' ';
		$params = array_merge( $params, $comp_models->builder->parameters_array() );

		$sql .= '';

		// Conditions
		$sql .= 'WHERE 90 = 90 ';

		$sql .= 'AND ' . DB_Tire::sql_assert_sold_and_not_discontinued_in_locale( 'tires', $this->locale ) . ' ';

		$price_range_component = $this->get_price_range_component( 'total_price' );
		if ( $price_range_component ) {
			$sql .= 'AND ' . $price_range_component->sql_with_placeholders() . ' ';
			$params = array_merge( $params, $price_range_component->parameters_array() );
		}

		$price_range_component_each = $this->get_price_range_component_each( 'tires.price' );
		if ( $price_range_component_each ) {
			$sql .= 'AND ' . $price_range_component_each->sql_with_placeholders() . ' ';
			$params = array_merge( $params, $price_range_component_each->parameters_array() );
		}

		$sql .= 'AND ' . $comp_tires->builder->sql_with_placeholders() . ' ';
		$params = array_merge( $params, $comp_tires->builder->parameters_array() );

		$sql .= 'GROUP BY tires.part_number ';

		$order_by = '';
		$sort = gp_if_set( $this->queued_filters, 'sort', '' );

		switch( $sort ) {
			case 'single_product_page':
				$order_by = array(
					'diameter',
					'profile',
					'width',
					'size',
					'load_index',
					'speed_rating',
					DB_Tire::get_price_column( $this->locale ),
				);
				break;
			case 'brand':
				$order_by = array(
					'brand_slug',
					'model_slug',
					DB_Tire::get_price_column( $this->locale ),
				);
				break;
			case 'model':
				$order_by = array(
					'model_slug',
					'brand_slug',
					DB_Tire::get_price_column( $this->locale ),
				);
				break;
			case 'price':
				$order_by = array(
					DB_Tire::get_price_column( $this->locale ),
					'brand_slug',
					'model_slug',
				);
				break;
			default:
				$order_by = array(
					DB_Tire::get_price_column( $this->locale ),
					'brand_slug',
					'model_slug',
				);
				break;
		}

		$sql .= $this->get_order_by_clause( $order_by ) . ' ';

		$sql .= $this->get_limit_clause( $params ) . ' ';

		$sql .= ';'; // end

		return $db->get_results( $sql, $params );
	}

}