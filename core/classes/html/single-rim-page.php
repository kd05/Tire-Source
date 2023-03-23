<?php

/**
 * Class Single_Rim_Page
 */
class Single_Rim_Page extends Single_Product_Page {

    /**
     * Single_Rim_Page constructor.
     * @param array $userdata
     */
    public function __construct( array $userdata ) {

        // before parent::__construct()
        $this->class_type = 'rim';

        // do second-ish
        parent::__construct( $userdata );

        $this->context = 'invalid';
        $this->context_debug = 'r0';

        $brand_obj = DB_Rim_Brand::get_instance_via_slug( $this->brand_slug );

        if ( $brand_obj ) {
            // model SLUG and brand ID

            $model_obj = DB_Rim_Model::get_instance_by_slug_brand( $this->model_slug, $brand_obj->get_primary_key_value() );

            if ( $model_obj ) {

                // its necessary to set these now, even if finish_obj is not created below.
                $this->brand = $brand_obj;
                $this->model = $model_obj;

                $this->all_finishes = self::get_all_finishes( $this->brand->get_primary_key_value(), $this->model->get_primary_key_value() );

                if ( $this->color_1_slug ) {

                    $finish_obj = DB_Rim_Finish::get_instance_via_finishes( $model_obj->get_primary_key_value(), $this->color_1_slug, $this->color_2_slug, $this->finish_slug );

                    if ( $finish_obj ) {

                        $this->finish = $finish_obj;

                        $this->other_finishes = array_filter( $this->all_finishes, function ( $f ) use( $finish_obj ) {

                            return $f->get_primary_key_value() !== $this->finish->get_primary_key_value();
                        } );

                        $p1 = DB_Rim::create_instance_via_part_number( $this->part_number_1 );

                        if ( $p1 && $p1->sold_and_not_discontinued_in_locale() && $p1->get( 'finish_id' ) == $this->finish->get_primary_key_value() ) {
                            $this->product_1 = $p1;

                            $p2 = DB_Rim::create_instance_via_part_number( $this->part_number_2 );
                            if ( $p2 && $p2->sold_and_not_discontinued_in_locale() && $p2->get( 'finish_id' ) == $this->finish->get_primary_key_value() ) {
                                $this->product_2 = $p2;
                            }
                        }

                        if ( $this->part_number_1 && ! $this->product_1 ) {
                            $this->context = 'invalid';
                            $this->context_debug = 'r1';
                        } else if ( $this->part_number_2 && ! $this->product_2 ) {
                            $this->context = 'invalid';
                            $this->context_debug = 'r2';
                        } else if ( $this->vehicle && $this->vehicle->is_complete() ) {
                            if ( $this->vehicle->fitment_object->wheel_set->is_staggered() ) {
                                if ( $this->product_1 && ! $this->product_2 ) {
                                    $this->context = 'invalid';
                                    $this->context_debug = 'r3';
                                } else if ( $this->product_2 && ! $this->product_1 ) {
                                    $this->context = 'invalid';
                                    $this->context_debug = 'r4';
                                } else {
                                    $this->context_debug = 'r5';
                                    $this->context = 'by_vehicle';
                                }
                            } else {
                                $this->context_debug = 'r6';
                                $this->context = 'by_vehicle';
                            }
                        } else if ( $this->finish && @$this->userdata[ 'by_size' ] == '1' ) {
                            $this->context = 'by_size';
                        } else if ( $this->product_1 ) {
                            $this->context = 'by_product';
                        } else {
                            $this->context = 'basic';
                        }
                    }

                } else {
                    // shouldn't be necessary
                    // $this->other_finishes = $this->all_finishes;
                    $this->is_rim_model_page = true;
                    $this->context = '';
                }

                Footer::$print_hidden['contR'] = $this->context_debug;
            }
        }

        if ( $this->context !== 'invalid' ) {

            $db_products = $this->queue_table_objects();

            $this->setup_meta_titles_etc();

            $this->top_image_args = $this->get_top_image_args();
        }

        // put schema on both rim model page and with finish selected, I guess
        if ( $this->context === 'basic' && $db_products ) {
            $schema = [
                '@context' => 'http://schema.org/',
                '@type' => 'ItemList',
                'itemListElement' => []
            ];

            /** @var DB_Rim $db_rim */
            foreach ( $db_products as $db_rim ) {

                $stock = $db_rim->get_computed_stock_amount( APP_LOCALE_CANADA );

                $in_stock = $stock === STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING ? true : $stock >= 2;
                $img_url = $db_rim->finish->get_image_url( 'reg', false );
                $logo_url = $this->brand->get_logo( false );

                $schema['itemListElement'][] = [
                    '@type' => "Product",
                    'name' => $db_rim->brand_model_finish_name(),
                    'url' => Router::get_url( 'rim_model', [ $this->brand->get( 'slug' ), $this->model->get( 'slug') ] ),
                    'image' => $img_url ? $img_url : '',
                    'description' => '',
                    'SKU' => $db_rim->get_and_clean( 'part_number' ),
                    'brand' => [
                        '@type' => "Brand",
                        'name' => $this->brand->get_and_clean( 'name' ),
                        'logo' => $logo_url ? $logo_url : '',
                    ],
                    'offers' => [
                        '@type' => "Offer",
                        'priceCurrency' => 'CAD',
                        'price' => cents_to_dollars_alt( $db_rim->get_price_cents() ),
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
    public function setup_meta_titles_etc() {

        // /wheels/brand/model/finish, and /wheels/brand/model/finish/part-number => /wheels/brand/model
        Header::$canonical = get_rim_model_url( $this->brand->get( 'slug' ), $this->model->get( 'slug' ) );

        // ie. 720Form GTF1 Matt Black, Milled Spokes
        Header::$title = $this->brand_model_finish_name( false )
            . ' - tiresource.COM';

        Header::$meta_description = 'Buy '
            . brand_model_finish_name( $this->brand, $this->model, $this->finish, false )
            . ' at tiresource.COM. We offer FREE SHIPPING on an extensive selection of wheels and rims!';
    }

    /**
     * Do at about the same time as $this->post_context_config().
     *
     * @return array
     * @throws Exception
     */
    public function get_top_image_args() {

        $ret = [];

        $ret[ 'title' ] = 'Wheels';

        // the h1 is further down the page and contains brand, model, finish
        $ret[ 'header_tag' ] = 'h2';

        // I think it's best to omit the finish here because it makes
        // things look too busy especially if a vehicle is selected.
        $ret[ 'tagline' ] = gp_test_input( $this->brand_model_name() );

        $ret[ 'img' ] = get_image_src( 'iStock-123201626-wide-lg-2.jpg' );
        $ret[ 'overlay_opacity' ] = 50;
        $ret[ 'img_tag' ] = true;
        $ret[ 'alt' ] = "Click It Wheels for wheels and tires canada";

        // going to put this in Single_Product_Page, since it is very similar for tires/rims
        $ret[ 'right_col_html' ] = $this->get_top_image_vehicle_lookup_form();

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
            $cols[ 'price_mobile' ] = 'Price (ea)';
            $cols[ 'stock_mobile' ] = 'Availability';
            $cols[ 'fitment' ] = gp_if_set( $args, 'fitment', 'Vehicle Fitment' );
            $cols[ 'size' ] = 'Size';
            $cols[ 'bolt_pattern' ] = 'Bolt Pattern';
            $cols[ 'hub_bore' ] = 'Hub Bore';
            $cols[ 'part_number' ] = 'Part Number';
            $cols[ 'price' ] = 'Price (ea)';
            $cols[ 'stock' ] = 'Availability';
            $cols[ 'add_to_cart' ] = '';

            return $cols;
        }

        // for everything else...
        $cols[ 'price_mobile' ] = 'Price (ea)';
        $cols[ 'stock_mobile' ] = 'Availability';
        $cols[ 'size' ] = 'Size';
        $cols[ 'part_number' ] = 'Part Number';
        $cols[ 'bolt_pattern' ] = 'Bolt Pattern';
        $cols[ 'hub_bore' ] = 'Hub Bore';
        $cols[ 'price' ] = 'Price (ea)';
        $cols[ 'stock' ] = 'Availability';
        $cols[ 'add_to_cart' ] = '';

        return $cols;
    }

    /**
     * @param string $size
     * @param bool $fallback
     * @return bool|string
     */
    public function get_image_url( $size = 'reg', $fallback = true ) {

        if ( $this->is_rim_model_page ) {

            if ( $this->all_finishes ) {
                return $this->all_finishes[0]->get_image_url( $size, $fallback );
            }

        } else {
            return $this->finish->get_image_url( $size, $fallback );
        }
    }

    public static function get_all_finishes( $brand_id, $model_id ) {

        $db = get_database_instance();

        $not_dc = DB_Product::sql_assert_sold_and_not_discontinued_in_locale( 'rims', app_get_locale() );

        // query rims first to omit finishes where there would be no products
        // sold.
        $q = "
        SELECT * FROM rims
        WHERE brand_id = :brand_id AND model_id = :model_id
        AND $not_dc 
        ";

        $rims = $db->get_results( $q, [
            [ 'brand_id', $brand_id, '%d' ],
            [ 'model_id', $model_id, '%d' ],
        ] );

        $finish_ids = array_unique( array_map( function ( $r ) {

            return (int) $r->finish_id;
        }, $rims ) );

        // < 5 finish IDs (not worried about nested queries)
        $db_finishes = array_filter( array_map( function ( $finish_id ) {

            $obj = DB_Rim_Finish::create_instance_via_primary_key( $finish_id );

            if ( $obj ) {
                $obj->setup_brand();
                $obj->setup_model();
                return $obj;
            }

            return null;
        }, $finish_ids ) );

        // order alphabetically by name
        usort( $db_finishes, function ( $f1, $f2 ) {

            return strcasecmp( $f1->get_finish_string(), $f2->get_finish_string() );
        } );

        return $db_finishes;
    }

    public static function render_other_colors( $finishes, $vehicle, $title_html ) {

        $op = '';

        // could be empty
        if ( $finishes ) {

            $op .= '<div class="other-colors">';

            // likely just wrap this in a p tag
            $op .= $title_html;

            $op .= '<div class="oc-items">';
            $op .= '<div class="oc-flex">';

            /** @var DB_Rim_Finish $db_finish */
            foreach ( $finishes as $db_finish ) {

                $title = implode( " ", array_filter( [
                    $db_finish->brand->get( 'name', '', true ),
                    $db_finish->model->get( 'name', '', true ),
                    $db_finish->get( 'color_1_name', '', true ),
                    $db_finish->get( 'color_2_name', '', true ),
                    $db_finish->get( 'finish_name', '', true ),
                ] ) );

                $vehicle_slugs = $vehicle && $vehicle->is_complete() ? $vehicle->get_slugs() : [];
                $url = get_rim_finish_url( $db_finish->get_slugs(), [], $vehicle_slugs );

                if ( $db_finish ) {
                    $op .= '<div class="oc-item">';
                    $op .= '<a title="' . $title . '" href="' . $url . '">';
                    $op .= '<span class="background-image contain" style="' . gp_get_img_style( $db_finish->get_image_url( 'thumb' ) ) . '"></span>';
                    $op .= '</a>';
                    $op .= '</div>';
                }
            }

            $op .= '</div>'; // oc-items
            $op .= '</div>'; // oc-flex
            $op .= '</div>';
        }

        return $op;
    }

    private static function link_finishes( $finishes, $before = '' ) {

        return array_map( function ( $f ) use( $before ) {

            /** @var DB_Rim_Finish $f */

            return $before . html_element( $f->get_finish_string(), 'a', '', [
                    'href' => $f->get_single_product_page_url(),
                ] );

        }, $finishes );
    }

    /**
     * @return string
     */
    public function get_rims_other_sizes_html() {

        if ( $this->is_rim_model_page ) {

            if ( $this->all_finishes ) {

                $count = count( $this->all_finishes );
                $before = $count === 1 ? "Available in 1 colour: " : "Available in $count colours: ";

                $links = self::link_finishes( $this->all_finishes, '- ' );

                $title = wrap_tag( "$before <span style='display: block; height: 8px;'></span>" . implode( '<br>', $links ));

                return self::render_other_colors( $this->all_finishes, $this->vehicle, $title );
            } else {
                return wrap_tag( 'No products found in your selected country (try selecting a different flag at the top right).', "p" );
            }

        } else {

            if ( $this->other_finishes ) {

                $before = "Also available in these colours: ";

                $links = self::link_finishes( $this->other_finishes, '- ' );

                $title = wrap_tag( "$before <span style='display: block; height: 8px;'></span>" . implode( '<br>', $links ));

                return self::render_other_colors( $this->other_finishes, $this->vehicle, $title );
            }
        }

        return '';
    }

    /**
     *
     */
    public function render_description() {

        $brand = gp_test_input( $this->brand->get( 'name' ) );
        $model = gp_test_input( $this->model->get( 'name' ) );

        $op = '';

        $op .= get_rim_brand_logo_html( $this->brand_slug );

        // $_brand = "$brand $model $finish";

        $op .= '<div class="product-titles size-large type-rim">';
        $op .= '<h1 class="multi-line">';
        $op .= '<span title="Brand" class="brand">' . $brand . ' </span>';

        if ( $this->is_rim_model_page ) {
            $op .= '<span title="Model" class="model">' . $model . ' </span>';
        } else {
            $model_url = get_rim_model_url( $this->brand->get( 'slug' ), $this->model->get( 'slug' ) );
            $op .= '<span title="Model" class="model"><a href="' . $model_url . '" style="color: black;">' . $model . '</a></span>';
        }

        if ( ! $this->is_rim_model_page ) {
            $op .= '<span title="Finish" class="finish">' . $this->finish->get_finish_string() . '</span>';
        }

        $op .= '</h1>';

        // only show when specific product is selected
        if ( $this->product_1 ) {
            $op .= '<p class="sku"><strong>SKU: </strong>' . gp_test_input( $this->product_1->get( 'part_number' ) ) . '</p>';
        }

        $op .= '</div>'; // product-titles

        $op .= '<div class="mobile-image">';
        $op .= '<div class="bg-wrap">';
        $op .= '<div class="background-image contain" style="' . gp_get_img_style( $this->get_image_url( 'reg' ) ) . '"></div>';
        $op .= '</div>';
        $op .= '</div>';

        $model_description = $this->model->get( 'description' ) ?? '';
        $brand_description = $this->brand->get( 'description' ) ?? '';
        if ( trim( $model_description ) ) {
            $op .= gp_render_textarea_content( $this->model->get( 'description' ) );
        } else if ( trim( $brand_description ) ) {
            $op .= gp_render_textarea_content( $this->brand->get( 'description' ) );
        }

        $op .= $this->get_rims_other_sizes_html();

        return $op;
    }

    /**
     * @param       $cols
     * @param array $args
     *
     * @return Single_Rims_Page_Table
     */
    public function get_table_rendering_object( $cols, $args = array() ) {

        $package_id = $this->package_id ? $this->package_id : null;
        $part_number = $this->product_1 ? $this->product_1->get( 'part_number' ) : null;

        return new Single_Rims_Page_Table( $cols, $args, $this->vehicle, $package_id, $part_number );
    }
}
