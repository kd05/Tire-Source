<?php

/**
 * Class Single_Tire_Page
 */
Class Single_Tire_Page extends Single_Product_Page {

    /**
     * Single_Tire_Page constructor.
     * @param array $userdata
     * @throws Exception
     */
	public function __construct( array $userdata ) {

		// before parent::__construct()
		$this->class_type = 'tire';

		// do second-ish
		parent::__construct( $userdata );

		$brand_obj = DB_Tire_Brand::get_instance_via_slug( $this->brand_slug );

		// likely overwrite
        $this->context = 'invalid';
        $this->context_debug = 't0';

        if ( $brand_obj ) {
			$model_obj = DB_Tire_Model::get_instance_by_slug_brand( $this->model_slug, $brand_obj->get_primary_key_value() );

			// need both a brand and a model
			if ( $model_obj ) {
				$this->brand = $brand_obj;
				$this->model = $model_obj;

                $p1 = DB_Tire::create_instance_via_part_number( $this->part_number_1 );

                if ( $p1 && $p1->sold_and_not_discontinued_in_locale() && $p1->get( 'model_id' ) == $this->model->get_primary_key_value() ) {
                    $this->product_1 = $p1;

                    $p2 = DB_Tire::create_instance_via_part_number( $this->part_number_2 );
                    if ( $p2 && $p2->sold_and_not_discontinued_in_locale() && $p2->get( 'model_id' ) == $this->model->get_primary_key_value() ) {
                        $this->product_2 = $p2;
                    }
                }

                // note: 'by_vehicle' takes precedence over 'by_product' if both are present
                if ( $this->part_number_1 && ! $this->product_1 ) {
                    $this->context = 'invalid';
                    $this->context_debug = 't1';
                } else if ( $this->part_number_2 && ! $this->product_2 ) {
                    $this->context = 'invalid';
                    $this->context_debug = 't2';
                } else if ( $this->vehicle && $this->vehicle->is_complete() ) {
                    if ( $this->vehicle->fitment_object->wheel_set->is_staggered() ) {
                        if ( $this->product_1 && ! $this->product_2 ) {
                            $this->context = 'invalid';
                            $this->context_debug = 't3';
                        } else if ( $this->product_2 && ! $this->product_1 ) {
                            $this->context = 'invalid';
                            $this->context_debug = 't4';
                        } else {
                            $this->context = 'by_vehicle';
                            $this->context_debug = 't5';
                        }
                    } else {
                        $this->context = 'by_vehicle';
                        $this->context_debug = 't6';
                    }
                } else if ( $this->product_1 || $this->product_2 ) {
                    $this->context = 'by_product';
                } else {
                    $this->context = 'basic';
                }
			}
		}

        Footer::$print_hidden['contT'] = $this->context_debug;

        if ( $this->context !== 'invalid' ) {

            $db_products = $this->queue_table_objects();

            $this->init_meta_titles_etc();

            $this->top_image_args = $this->get_top_image_args();
        }

		// SCHEMA
		if ( $this->context === 'basic' && $db_products ) {
		    $schema = [
		        '@context' => 'http://schema.org/',
                '@type' => 'ItemList',
                'itemListElement' => []
            ];

		    /** @var DB_Tire $db_tire */
            foreach ( $db_products as $db_tire ) {

                $stock = $db_tire->get_computed_stock_amount( APP_LOCALE_CANADA );

                $in_stock = $stock === STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING ? true : $stock >= 2;

                $img_url = $this->model->get_image_url( 'reg', false );
                $logo_url = $this->brand->get_logo( false );

                $schema['itemListElement'][] = [
                    '@type' => "Product",
                    'name' => $this->brand->get_and_clean( 'name' ) . ' ' . $this->model->get_and_clean( 'name' ),
                    'url' => get_tire_model_url_basic( $this->brand->get_and_clean( 'slug' ), $this->model->get_and_clean( 'slug' )),
                    'image' => $img_url ? $img_url : '',
                    'description' => '',
                    'SKU' => $db_tire->get_and_clean( 'part_number' ),
                    'brand' => [
                        '@type' => "Brand",
                        'name' => $this->brand->get_and_clean( 'name' ),
                        'logo' => $logo_url ? $logo_url : '',
                    ],
                    'offers' => [
                        '@type' => "Offer",
                        'priceCurrency' => 'CAD',
                        'price' => cents_to_dollars_alt( $db_tire->get_price_cents() ),
                        'availability' => $in_stock ? 'http://schema.org/InStock' : 'http://schema.org/OutOfstock',
                    ]
                ];
            }

            Header::add_schema( $schema );
        }
	}

    /**
     *
     */
	public function init_meta_titles_etc(){

	    // the key here is ensure a canonical when a part number is present,
        // ie. tires/brand/model/123456 => tires/brand/model
        Header::$canonical = get_tire_model_url_basic ( $this->brand->get( 'slug' ), $this->model->get( 'slug' ) );

        $brand_name = $this->brand_name();
        $model_name = $this->model_name();

        $name = $brand_name . ' ' . $model_name;
        $_type = $this->model->get( 'type', '', true );

        Header::$title = "$name | $_type Tires | tiresource.COM";

        Header::$meta_description = "Buy $name $_type Tires at tiresource.COM. We offer an extensive selection of tires.";

    }

    /**
     * Do at about the same time as $this->post_context_config().
     *
     * @return array
     * @throws Exception
     */
	public function get_top_image_args(){

	    $ret = [];

	    $ret['title'] = 'Tires';

        // the h1 is further down the page and contains brand, model, finish
        $ret['header_tag'] = 'h2';

        $ret['tagline'] = gp_test_input( $this->brand_model_name() );

        $ret['img'] = get_image_src( 'tire-top.jpg' );

        // going to put this in Single_Product_Page, since it is very similar for tires/rims
        $ret['right_col_html'] = $this->get_top_image_vehicle_lookup_form();

        return $ret;
    }


	/**
	 * @return array
	 */
	public function get_table_columns( $args = array() ) {

		// note: similar logic is in queue table functions
		$with_vehicle = ( $this->vehicle && $this->vehicle->trim_exists() );
		$with_fitment = $this->vehicle && $this->vehicle->is_complete();
		$with_product = ( $this->product_1 );

		$cols = array();

		// if vehicle, we have to show the fitment
		if ( $with_vehicle ) {
			// sometimes we may want to call this Plus/Minus Sizes or something similar to that
			$cols[ 'price_mobile' ] = 'Price (ea)';
			$cols[ 'stock_mobile' ] = 'Availability';
			$cols[ 'fitment' ]      = gp_if_set( $args, 'fitment', 'Vehicle Fitment' );
			$cols[ 'size' ]         = 'Tire Size';

			// $cols[ 'spec_mobile' ]  = 'Spec';
			// use spec instead, because we don't have enough space
			//			$cols[ 'load_index' ]   = 'Load Index';
			//			$cols[ 'speed_rating' ] = 'Speed Rating';

			// speed rating + load index
			$cols[ 'spec' ]        = 'Spec';
			$cols[ 'part_number' ] = 'Part Number';
			$cols[ 'price' ]       = 'Price (ea)';
			$cols[ 'stock' ]       = 'Availability';
			$cols[ 'add_to_cart' ] = '';

			return $cols;
		}

		// for everything else...
		$cols[ 'price_mobile' ] = 'Price (ea)';
		$cols[ 'stock_mobile' ] = 'Availability';
		$cols[ 'size' ]         = 'Size';
		$cols[ 'part_number' ]  = 'Part Number';
		// $cols[ 'spec_mobile' ]  = 'Spec';
		//		$cols[ 'speed_rating' ] = 'Speed Rating';
		//		$cols[ 'load_index' ]   = 'Load Index';

		// speed rating + load index
		$cols[ 'spec' ] = 'Spec';

		$cols[ 'price' ]       = 'Price (ea)';
		$cols[ 'stock' ]       = 'Availability';
		$cols[ 'add_to_cart' ] = '';

		return $cols;
	}

    /**
     * @param string $size
     * @param bool $fallback
     * @return bool|string
     */
	public function get_image_url( $size = 'reg', $fallback = true ) {
		return $this->model->get_image_url( $size, $fallback );
	}

	/**
	 *
	 */
	public function render_description() {

		$op = '';

		$type  = $this->model->type->get( 'slug' );
		$class = $this->model->class->get( 'slug' );

		$op .= get_tire_brand_logo_html( $this->brand_slug );

		$op .= '<div class="product-titles">';

		$op .= '<h1 class="multi-line">';
        $op .= '<span title="Brand" class="brand">' . $this->brand->get_and_clean( 'name' ) . ' </span>';
        $op .= '<span title="Model" class="model">' . $this->model->get_and_clean( 'name' ) . '</span>';
		$op .= '</h1>';

		$op .= get_tire_type_and_class_html( $type, $class );

		if ( $this->product_1 && ! $this->is_staggered() ) {
			$op .= '<p class="sku"><strong>SKU: </strong>' . gp_test_input( $this->product_1->get( 'part_number' ) ) . '</p>';
		}

		$op .= '</div>'; // product-titles

		$op .= '<div class="mobile-image">';
		$op .= '<div class="bg-wrap">';
		$op .= '<div class="background-image contain" style="' . gp_get_img_style( $this->get_image_url( 'reg' ) ) . '"></div>';
		$op .= '</div>';
		$op .= '</div>'; // mobile-image

        $brand_description = $this->brand->get( 'description' ) ?? '';
        $model_description = $this->model->get( 'description' ) ?? '';

        if ( trim( $model_description ) ) {
            $op .= gp_render_textarea_content( $this->model->get( 'description' ) );
        } else if ( trim( $brand_description ) ) {
            $op .= gp_render_textarea_content( $this->brand->get( 'description' ) );
        }

		return $op;
	}

	/**
	 * @param       $cols
	 * @param array $args
	 *
	 * @return Single_Tires_Page_Table
	 */
	public function get_table_rendering_object( $cols, $args = array() ) {
		$package_id  = $this->package_id ? $this->package_id : null;
		$part_number = $this->product_1 ? $this->product_1->get( 'part_number' ) : null;

		return new Single_Tires_Page_Table( $cols, $args, $this->vehicle, $package_id, $part_number );
	}
}


