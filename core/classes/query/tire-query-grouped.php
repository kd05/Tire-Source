<?php

/**
 * This might end up being almost identical to Tires_Query_General except
 * that we need different logic for group by and order by.
 *
 * Otherwise, even the inner joins will be the same. Although not every single
 * Tires_Query_General requires inner joining models and brands, nearly all of them do,
 * therefore we will probably just always do it.
 *
 * Class Tires_Query_Grouped
 */
Class Tires_Query_Grouped extends Product_Query_Filter_Methods{

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
     * @return array
     * @throws Exception
     */
	public function get_results(){

		$q = '';
		$db = get_database_instance();
		$params = array();

		$filters_tires = gp_if_set( $this->queued_filters, 'tires', array() );
		$filters_models = gp_if_set( $this->queued_filters, 'tire_models', array() );
		$filters_brands = gp_if_set( $this->queued_filters, 'tire_brands', array() );

		// tires
		// $comp_tires = new Query_Components_Tires( 'tires' );
//		$comp_tires->apply_filter( $filters_tires, 'part_number', true, 'get_part_number' );
//		$comp_tires->apply_filter( $filters_tires, 'part_number_not_in', true, 'get_part_number_not_in' );

		// models
		$comp_models = new Query_Components_Tire_Models( 'models' );
		$comp_models->builder->add_to_self( 'models.tire_model_id = tires.model_id' );

		// for this one, we can use the $comp_tires->get_model() function, or $comp_models->get_slug()
		$comp_models->apply_filter( $filters_models, 'slug', 'get_slug', true );
		$comp_models->apply_filter( $filters_models, 'type', 'get_type', true );
		$comp_models->apply_filter( $filters_models, 'class', 'get_class', true );
		$comp_models->apply_filter( $filters_models, 'category', 'get_category', true );

		// brands
		$comp_brands = new Query_Components_Tire_Brands( 'brands' );
		$comp_brands->builder->add_to_self( 'brands.tire_brand_id = tires.brand_id' );
		$comp_brands->apply_filter( $filters_brands, 'slug', 'get_slug', true );

        $price_col = DB_Tire::get_column_price( $this->locale );

		$select = array();
		$select[] = '*';
		$select[] = "CAST(tires.$price_col AS DECIMAL(7,2)) AS tire_price";
		// $select[] = 'COUNT(tires.part_number) AS group_count';
		// $select[] = "CAST(tires.$price_col AS DECIMAL(7,2) ) AS min_price";

		// Select
		$q .= $this->get_select_from_array( $select ) . ' ';
		$q .= 'FROM ' . $db->tires . ' AS tires ';

		// join brands
		$q .= 'INNER JOIN ' . $db->tire_brands . ' AS brands ON ' . $comp_brands->builder->sql_with_placeholders() . ' ';
		$params = array_merge( $params, $comp_brands->builder->parameters_array() );

		// join models
		$q .= 'INNER JOIN ' . $db->tire_models . ' AS models ON ' . $comp_models->builder->sql_with_placeholders() . ' ';
		$params = array_merge( $params, $comp_models->builder->parameters_array() );

		// Where
		$q .= 'WHERE 1 = 1 ';

		$q .= 'AND ' . DB_Tire::sql_assert_sold_and_not_discontinued_in_locale( 'tires', $this->locale ) . ' ';

		$q .= 'AND ' . $this->top_level_components->sql_with_placeholders() . ' ';
		$params = array_merge( $params, $this->top_level_components->parameters_array() );

		// Group By - done in PHP now
		// $q .= 'GROUP BY tires.model_id ';

		$sort = gp_if_set( $this->queued_filters, 'sort', '' );
		switch( $sort ) {
			case 'brand':
				$order_by = array(
					'tires.brand_slug',
					'tires.model_slug',
                    'tire_price',
				);
				break;
			case 'model':
				$order_by = array(
					'tires.model_slug',
					'tires.brand_slug',
                    'tire_price',
				);
				break;
			case 'price':
			default:
				$order_by = array(
                    'tire_price',
					'tires.brand_slug',
					'tires.model_slug',
				);
				break;
		}

		$q .= $this->get_order_by_clause( $order_by ) . ' ';
		$q .= ';';

		$rows = $db->get_results( $q, $params );

        // manually do the group by in PHP on the fully ordered
        // and ungrouped set of products that the SQl returns.
        // this lets us get the minimum price in each group.
        // we could do this in SQL using a sub-query but we wouldn't
        // necesarily save much on performance and this is much simpler.
        $ret = [];

        foreach ( $rows as $row ) {

            $m = $row->model_id;

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

            if ( isset( $ret[$m] ) ) {
                $ret[$m]->group_count++;

                // last item encountered is max price
                $ret[$m]->max_price = $row->tire_price;

                if ( $stock > $ret[$m]->max_stock ) {
                    $ret[$m]->max_stock = $stock;
                }

                if ( $stock < $ret[$m]->min_stock ) {
                    $ret[$m]->min_stock = $stock;
                }

            } else {
                // first item encountered is min price since all results are always ordered by price
                $row->min_price = $row->tire_price;
                $row->group_count = 1;

                $row->min_stock = $stock;
                $row->max_stock = $stock;

                $ret[$m] = $row;
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

        return array_values( $ret );
	}
}