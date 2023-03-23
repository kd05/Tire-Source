<?php

/**
 * Class rims_Query_Grouped
 */
Class Rims_Query_Grouped extends Product_Query_Filter_Methods {

	protected $product_type = 'rims';

	/**
	 * Rims_Query_Grouped constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		$this->uid = 'rq_grp';
		parent::__construct( $args );

		// might not need these anymore
		$this->staggered_result = false;
		$this->result_has_tires = false;
		$this->result_has_rims  = true;
	}

	/**
	 *
	 */
	public function get_results() {

		$db     = get_database_instance();
		$sql    = '';
		$params = array();

		$filters_rims   = gp_if_set( $this->queued_filters, 'rims', array() );
		$filters_models = gp_if_set( $this->queued_filters, 'rim_models', array() );
		$filters_brands = gp_if_set( $this->queued_filters, 'rim_brands', array() );

		$comp_rims = new Query_Components_Rims( 'rims' );
		// may need to add some to this list...
		$comp_rims->apply_filter( $filters_rims, 'bolt_pattern', 'get_bolt_pattern', true );
		$comp_rims->apply_filter( $filters_rims, 'width', 'get_exact_width', true );
		$comp_rims->apply_filter( $filters_rims, 'diameter', 'get_diameter', true );
		$comp_rims->apply_filter( $filters_rims, 'center_bore_min', 'get_center_bore_min', true );
		$comp_rims->apply_filter( $filters_rims, 'center_bore_max', 'get_center_bore_max', true );
		$comp_rims->apply_filter( $filters_rims, 'offset_min', 'get_min_offset', true );
		$comp_rims->apply_filter( $filters_rims, 'offset_max', 'get_max_offset', true );
		$comp_rims->apply_filter( $filters_rims, 'type', 'get_type', true );
		$comp_rims->apply_filter( $filters_rims, 'style', 'get_style', true );

//		$comp_rims->apply_filter( $filters_rims, 'part_number', true, 'get_part_number' );
//		$comp_rims->apply_filter( $filters_rims, 'part_number_not_in', true, 'get_part_number_not_in' );

		$comp_finishes = new Query_Components_Rim_Finishes( 'finishes' );
		$comp_finishes->builder->add_to_self( 'finishes.rim_finish_id = rims.finish_id' );
		$comp_finishes->apply_filter( $filters_rims, 'color_1', 'get_color_1', true );
		$comp_finishes->apply_filter( $filters_rims, 'color_2', 'get_color_2', true );
		$comp_finishes->apply_filter( $filters_rims, 'finish', 'get_finish', true );

		$comp_brands = new Query_Components_Rim_Brands( 'brands' );
		$comp_brands->builder->add_to_self( 'brands.rim_brand_id = rims.brand_id' );
		$comp_brands->apply_filter( $filters_brands, 'slug', 'get_slug', true );

		$comp_models = new Query_Components_Rim_Models( 'models' );
		// don't be tempted to join this on finishes.model_id, its not the same thing
		$comp_models->builder->add_to_self( 'models.rim_model_id = rims.model_id' );
		$comp_models->apply_filter( $filters_models, 'slug', 'get_slug', true );

		$price_col = DB_Rim::get_column_price( $this->locale );

		$select   = array();
		$select[] = '*';
		$select[] = "CAST(rims.$price_col AS DECIMAL(7,2)) AS rim_price";

//		$select[] = 'COUNT(rims.part_number) as group_count';
//		$select[] = "CAST(rims.$price_col AS DECIMAL(7,2) ) AS min_price";

		// Begin Sql
		$sql .= $this->get_select_from_array( $select ) . ' ';
		$sql .= 'FROM ' . $db->rims . ' AS rims ';

		// finishes
		$sql .= 'INNER JOIN rim_finishes AS finishes ON ' . $comp_finishes->builder->sql_with_placeholders() . ' ';
		$params = array_merge( $params, $comp_finishes->builder->parameters_array() );

		// models
		$sql .= 'INNER JOIN rim_models AS models ON ' . $comp_models->builder->sql_with_placeholders() . ' ';
		$params = array_merge( $params, $comp_models->builder->parameters_array() );

		// brands
		$sql .= 'INNER JOIN rim_brands AS brands ON ' . $comp_brands->builder->sql_with_placeholders() . ' ';
		$params = array_merge( $params, $comp_brands->builder->parameters_array() );

		// Conditions
		$sql .= 'WHERE 1 = 1 ';

		$sql .= 'AND ' . DB_Rim::sql_assert_sold_and_not_discontinued_in_locale( 'rims' ) . ' ';

		$sql .= 'AND ' . $comp_rims->builder->sql_with_placeholders() . ' ';
		$params = array_merge( $params, $comp_rims->builder->parameters_array() );

		// Group by
		// $sql .= 'GROUP BY rims.finish_id ';

		// Order By
		$sort = gp_if_set( $this->queued_filters, 'sort', '' );

		$price_col = DB_Rim::get_price_column( $this->locale );

        switch ( $sort ) {
            case 'brand':
                $order_by = array(
                    'rims.brand_slug',
                    'rims.model_slug',
                    'finishes.color_1',
                    'finishes.color_2',
                    'finishes.finish',
                    'rim_price',
                );
                break;
            case 'model':
				$order_by = array(
					'rims.model_slug',
					'rims.brand_slug',
					'finishes.color_1',
					'finishes.color_2',
					'finishes.finish',
                    'rim_price',
				);
				break;
			case 'price':
			default:
				$order_by = array(
                    'rim_price',
					'rims.brand_slug',
					'rims.model_slug',
					'finishes.color_1',
					'finishes.color_2',
					'finishes.finish',
				);
				break;
		}

		$sql .= $this->get_order_by_clause( $order_by ) . ' ';

		$sql .= ';'; // end

        Debug::log_time( 'rims_grouped_1' );

        $rows = $db->get_results( $sql, $params );

        Debug::log_time( 'rims_grouped_2' );

        // manually do the group by in PHP on the fully ordered
        // and ungrouped set of products that the SQl returns.
        // this lets us get the minimum price in each group.
        // we could do this in SQL using a sub-query but we wouldn't
        // necesarily save much on performance and this is much simpler.
        $ret = [];

        foreach ( $rows as $row ) {

            $f = $row->finish_id;

            $stock = call_user_func( function() use( $row ){

                $cols = $this->locale === APP_LOCALE_CANADA ?
                    [ 'stock_unlimited_ca', 'stock_amt_ca' ] :
                    [ 'stock_unlimited_us', 'stock_amt_us' ];

                if ( $row->{$cols[0]} ){
                    return 999;
                } else{
                    return $row->{$cols[1]};
                }
            } );

            if ( isset( $ret[$f] ) ) {
                $ret[$f]->group_count++;

                // last item encountered is max price
                $ret[$f]->max_price = $row->rim_price;

                if ( $stock > $ret[$f]->max_stock ) {
                    $ret[$f]->max_stock = $stock;
                }

                if ( $stock < $ret[$f]->min_stock ) {
                    $ret[$f]->min_stock = $stock;
                }

            } else {
                // first item encountered is min price since all results are always ordered by price
                $row->min_price = $row->rim_price;
                $row->group_count = 1;

                $row->min_stock = $stock;
                $row->max_stock = $stock;

                $ret[$f] = $row;
            }
        }

        if ( @$this->args['sort_by_stock_group'] ) {

            // in stock, partially in stock, out of stock
            $groups = [[], [], []];

            foreach ( $ret as $r ) {

                if ( $r->max_stock >= 4 ) {
                    $groups[0][] = $r;
                } else if ( $r->max_stock >= 1 ) {
                    $groups[1][] = $r;
                } else {
                    $groups[2][] = $r;
                }
            }

            $ret = array_merge( $groups[0], $groups[1], $groups[2] );
        }

        Debug::log_time( 'rims_grouped_3' );

		return array_values( $ret );
	}

	/**
	 * @param $preset
	 *
	 * @return string
	 */
	protected function get_group_by_sql( $preset ) {
		return '';
	}

}