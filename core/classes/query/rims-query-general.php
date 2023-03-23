<?php

/**
 * For grouped (by model) and non-grouped rim queries. However, not for rim queries
 * that return staggered results, ie when querying rims by sizes.
 *
 * Class Rims_Query_General
 */
Class Rims_Query_General extends Product_Query_Filter_Methods {

	/**
	 * Rims_Query_General constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		$this->uid = 'rq_gen';
		parent::__construct( $args );

		// might not need these anymore
		$this->staggered_result = false;
		$this->result_has_tires = false;
		$this->result_has_rims = true;
	}

	/**
	 *
	 */
	public function get_results() {

		$sql      = '';
		$db     = get_database_instance();
		$params = array();

		$filters_rims = gp_if_set( $this->queued_filters, 'rims', array() );
		$filters_brands = gp_if_set( $this->queued_filters, 'rim_brands', array() );
		$filters_models = gp_if_set( $this->queued_filters, 'rim_models', array() );

		// we generally just allow arrays on most of these values even though for many of them we expect singular
		// for diameter, bolt pattern and a couple others, we do expect arrays however.
		$comp_rims = new Query_Components_Rims( 'rims' );
		$comp_rims->apply_filter( $filters_rims, 'color_1', 'get_color_1', true );
		$comp_rims->apply_filter( $filters_rims, 'color_2', 'get_color_2', true );
		$comp_rims->apply_filter( $filters_rims, 'finish', 'get_finish', true );
		$comp_rims->apply_filter( $filters_rims, 'bolt_pattern', 'get_bolt_pattern', true );
		$comp_rims->apply_filter( $filters_rims, 'width', 'get_exact_width', true );
		$comp_rims->apply_filter( $filters_rims, 'diameter', 'get_diameter', true );
		$comp_rims->apply_filter( $filters_rims, 'center_bore_min', 'get_center_bore_min', true );
		$comp_rims->apply_filter( $filters_rims, 'center_bore_max', 'get_center_bore_max', true );
		$comp_rims->apply_filter( $filters_rims, 'type', 'get_type', true );
		$comp_rims->apply_filter( $filters_rims, 'style', 'get_style', true );
		$comp_rims->apply_filter( $filters_rims, 'offset_min', 'get_min_offset', true );
		$comp_rims->apply_filter( $filters_rims, 'offset_max', 'get_max_offset', true );
		$comp_rims->apply_filter( $filters_rims, 'part_number', 'get_part_number', true );
		$comp_rims->apply_filter( $filters_rims, 'part_number_not_in', 'get_part_number_not_in', true );

		$comp_brands = new Query_Components_Rim_Brands( 'brands' );
		$comp_brands->builder->add_to_self( 'brands.rim_brand_id = rims.brand_id' );
		$comp_brands->apply_filter( $filters_brands, 'slug', 'get_slug', true );

		$comp_models = new Query_Components_Rim_Models( 'models' );
		$comp_models->builder->add_to_self( 'models.rim_model_id = rims.model_id' );
		$comp_models->apply_filter( $filters_models, 'slug', 'get_slug', true );

		$select = array();
		$select[] = '*';

		// Begin Sql
		$sql .= $this->get_select_from_array( $select ) . ' ';
		$sql .= 'FROM ' . $db->rims . ' AS rims ';

		// Join Brands
		$sql    .= 'INNER JOIN ' . $db->rim_brands . ' AS brands ON ' . $comp_brands->builder->sql_with_placeholders() . ' ';
		$params = array_merge( $params, $comp_brands->builder->parameters_array() );

		// Join Models
		$sql    .= 'INNER JOIN ' . $db->rim_models . ' AS models ON ' . $comp_models->builder->sql_with_placeholders() . ' ';
		$params = array_merge( $params, $comp_models->builder->parameters_array() );

		// Join Finishes
		$sql    .= 'INNER JOIN ' . $db->rim_finishes . ' AS finishes ON finishes.rim_finish_id = rims.finish_id ';

		// Conditions
		$sql .= 'WHERE 1 = 1 ';

		$sql .= 'AND ' . DB_Rim::sql_assert_sold_and_not_discontinued_in_locale( 'rims', $this->locale ) . ' ';

		$sql    .= 'AND ' . $comp_rims->builder->sql_with_placeholders() . ' ';
		$params = array_merge( $params, $comp_rims->builder->parameters_array() );

		// Group By
		$sql .= 'GROUP BY rims.part_number ';

		// Order By
		$order_by = '';
		$sort = gp_if_set( $this->queued_filters, 'sort', '' );
		switch( $sort ) {
			case 'single_product_page':
				$order_by = array(
					'diameter',
					'width',
					'offset',
					'center_bore',
					DB_Rim::get_price_column( $this->locale ),
					'rim_id',
				);
				break;
			case 'brand':
				$order_by = array(
					'brand_slug',
					'model_slug',
					DB_Rim::get_price_column( $this->locale ),
					'rim_id',
				);
				break;
			case 'model':
				$order_by = array(
					'model_slug',
					'brand_slug',
					DB_Rim::get_price_column( $this->locale ),
					'rim_id',
				);
				break;
			case 'price':
				$order_by = array(
					DB_Rim::get_price_column( $this->locale ),
					'brand_slug',
					'model_slug',
					'rim_id',
				);
				break;
			default:
				$order_by = array(
					DB_Rim::get_price_column( $this->locale ),
					'brand_slug',
					'model_slug',
					'rim_id',
				);
				break;
		}

		$sql .= $this->get_order_by_clause( $order_by ) . ' ';

		$sql .= $this->get_limit_clause( $params ) . ' ';
		$sql .= ';'; // end

		return $db->get_results( $sql, $params );
	}

}

