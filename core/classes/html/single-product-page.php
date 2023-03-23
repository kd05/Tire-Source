<?php

/**
 * Class Single_Product_Page
 */
abstract class Single_Product_Page {

    /**
     * ie. $_GET
     *
     * @var array
     */
    public $userdata = [];

    /**
     * 'tire' or 'rim'
     *
     * @var
     */
    public $class_type;

    /**
     * 'by_vehicle', 'by_product', 'basic', 'invalid'.
     *
     * @var string
     */
    public $context;

    /*
     * rim model pages exist when no finish is selected. We put
     * these pages into sitemap to prevent duplicate content. It
     * will show all available finishes.
     */
    public $is_rim_model_page = false;

    /** @var */
    public $brand_slug;

    /** @var */
    public $model_slug;

    /** @var  DB_Tire_Brand|DB_Rim_Brand */
    public $brand;

    /** @var  DB_Tire_Model|DB_Rim_Model */
    public $model;

    /**
     * Single_Rim_Page only
     *
     * @var bool|mixed
     */
    public $color_1_slug;

    /**
     * Single_Rim_Page only
     *
     * @var bool|mixed
     */
    public $color_2_slug;

    /**
     * Single_Rim_Page only
     *
     * @var bool|mixed
     */
    public $finish_slug;

    /**
     * Single_Rim_Page only
     *
     * @var DB_Rim_Finish|null
     */
    public $finish;

    /**
     * @var array<DB_Rim_Finish>
     */
    public $all_finishes = [];

    /**
     * @var array<DB_Rim_Finish>
     */
    public $other_finishes = [];

    /** @var  Vehicle|null */
    public $vehicle;

    /**
     * @var string|null
     */
    public $part_number_1;

    /**
     * @var string|null
     */
    public $part_number_2;

    /**
     * Only present sometimes.
     *
     * @var DB_Tire|DB_Rim|null
     */
    public $product_1;

    /**
     * Only present sometimes (and only with vehicle + staggered fitment)
     *
     * @var  DB_Tire|DB_Rim|null
     */
    public $product_2;

    /**
     * only when adding to existing package (vehicle must also be present)
     *
     * @var
     */
    public $package_id;

    /**
     * array of Single_Product_Page_Table objects
     *
     * @var array
     */
    protected $tables;

    /**
     * @var array
     */
    public $top_image_args = [];

    public $context_debug;

    /**
     * Single_Product_Page constructor.
     *
     * @param $userdata
     */
    public function __construct( array $userdata ) {

        assert( (bool) $this->class_type );

        $this->userdata = $userdata;

        // let all queries know we're on a single product page
        $this->userdata[ 'sort' ] = 'single_product_page';

        // its not necessary to validate the package ID provided. We just send it to
        // the add to cart endpoint, and it will figure it out.
        $this->package_id = (int) gp_test_input( @$userdata['pkg'] );

        $this->brand_slug = gp_test_input( @$userdata['brand'] );
        $this->model_slug = gp_test_input( @$userdata['_model'] );

        if ( $this->class_type === 'rim' ) {
            $this->color_1_slug = gp_test_input( @$userdata['color_1'] );
            $this->color_2_slug = gp_test_input( @$userdata['color_2'] );
            $this->finish_slug = gp_test_input( @$userdata['finish'] );
        }

        // $this->vehicle should always be a Vehicle instance.
        $vehicle = Vehicle::create_instance_from_user_input( $userdata );

        if ( $vehicle->is_complete() ) {
            $this->vehicle = $vehicle;
            $this->vehicle->track_in_session_history();
        } else {
            $this->vehicle = new Vehicle( [] );
        }

        $this->part_number_1 = gp_test_input( @$userdata['front'] );

        if ( $vehicle->is_complete() && $vehicle->fitment_object->wheel_set->is_staggered() ) {
            $this->part_number_2 = gp_test_input( @$userdata['rear'] );
        }

        // not totally necessary but doesn't hurt
        unset( $userdata['front'] );
        unset( $userdata['rear'] );
    }

    abstract public function get_table_columns( $args = array() );

    abstract public function get_image_url( $size = 'reg', $fallback = true );

    /**
     * @param $cols
     * @param array $args
     * @return Single_Tires_Page_Table|Single_Rims_Page_Table
     */
    abstract public function get_table_rendering_object( $cols, $args = array() );

    abstract public function render_description();

    /**
     * @return string
     * @throws Exception
     */
    public function get_top_image_vehicle_lookup_form() {

        if ( $this->context === 'by_vehicle' || $this->context === 'invalid' ) {
            return '';
        }

        $args = [];

        if ( $this->class_type === 'tire' ) {

            $base_url = get_tire_model_url_basic( $this->brand->get( 'slug' ), $this->model->get( 'slug' ) );

            $args[ 'do_tires' ] = true;
            $args[ 'tires_by_size_args' ] = [];
            $args[ 'heading' ] = get_top_image_vehicle_lookup_heading( true, true, false );

        } else {

            $args[ 'heading' ] = get_top_image_vehicle_lookup_heading( false, false, false );

            if ( $this->is_rim_model_page ) {

                if ( $this->all_finishes ) {
                    /** @var DB_RIM_Finish $f */
                    $f = $this->all_finishes[0];
                    $base_url = get_rim_finish_url( $f->get_slugs( true, true ));
                } else {
                    return '';
                }

            } else {
                $base_url = get_rim_finish_url( $this->finish->get_slugs( true, true ));
            }
        }


        $args[ 'vehicle_lookup_args' ] = [
            'vehicle' => null,
            'page' => $this->class_type === 'tire' ? 'tires' : 'rims',
            'hide_shop_for' => true,
            'hidden_inputs' => [
                'base_url' => $base_url,
            ],
        ];

        return get_top_image_vehicle_lookup_form( $args );
    }

    /**
     * @return bool|mixed|string
     */
    public function brand_name() {

        $ret = $this->brand ? $this->brand->get_and_clean( 'name' ) : '';

        return $ret;
    }

    /**
     * @return bool|mixed|string
     */
    public function model_name() {

        $ret = $this->model ? $this->model->get_and_clean( 'name' ) : '';

        return $ret;
    }

    /**
     * @return string
     */
    public function brand_model_name() {

        return brand_model_name( $this->brand, $this->model );
    }

    /**
     * For rims
     *
     * @param bool $add_brackets
     * @return string
     */
    public function brand_model_finish_name( $add_brackets = true ) {

        return brand_model_finish_name( $this->brand, $this->model, $this->finish, $add_brackets );
    }


    /**
     * @return string
     */
    public function get_all_sizes_link() {

        if ( $this->class_type === 'tire' ) {
            return get_tire_model_url_basic( $this->brand->get( 'slug' ), $this->model->get( 'slug' ) );
        }

        if ( $this->class_type === 'rim' ) {
            return get_rim_finish_url( [
                $this->brand->get( 'slug' ),
                $this->model->get( 'slug' ),
                $this->color_1_slug,
                $this->color_2_slug,
                $this->finish_slug
            ] );
        }
    }

    /**
     * True when 2 part numbers provided in URL and vehicle is selected.
     */
    public function is_staggered() {

        if ( $this->vehicle->trim_exists() && $this->product_1 && $this->product_2 ) {
            return true;
        }

        return false;
    }

    /**
     * @param $product
     * @return bool
     */
    public function is_product_valid( $product ) {

        if ( $this->class_type === 'tire' ) {
            return $product && $product instanceof DB_Tire;
        }

        if ( $this->class_type === 'rim' ) {
            return $product && $product instanceof DB_Rim;
        }

        return false;
    }

    /**
     * Gets the URL to the page currently shown.
     *
     * @param bool $with_vehicle
     * @param bool $with_sub_size
     * @param bool $with_part_numbers
     * @return string
     */
    public function get_url( $with_vehicle = false, $with_sub_size = false, $with_part_numbers = false ) {

        if ( $with_vehicle ) {

            $vehicle_slugs = $this->vehicle && $this->vehicle->is_complete() ? $this->vehicle->get_slugs() : [];

            // possibly remove sub size
            if ( $vehicle_slugs && ! $with_sub_size ) {
                $vehicle_slugs = array_slice( $vehicle_slugs, 0, 5 );
            }

        } else {
            $vehicle_slugs = [];
        }

        if ( $with_part_numbers ) {
            $part_numbers = array_filter( [
                $this->product_1 ? $this->product_1->get( 'part_number' ) : '',
                $this->product_2 ? $this->product_2->get( 'part_number' ) : ''
            ] );
        } else {
            $part_numbers = [];
        }

        if ( $this->class_type === 'tire' ){
            return get_tire_model_url( [ $this->brand->get( 'slug' ), $this->model->get( 'slug' )], $part_numbers, $vehicle_slugs, [] );
        }

        if ( $this->class_type === 'rim' ){

            // it's unlikely we'll call this function when this is the case.
            if ( $this->is_rim_model_page ) {
                return get_rim_model_url( $this->brand->get( 'slug' ), $this->model->get( 'slug' ) );
            } else {
                return get_rim_finish_url( $this->finish->get_slugs( true, true ), $part_numbers, $vehicle_slugs, [] );
            }
        }
    }

    /**
     *
     */
    public function render_image() {

        $img_url = $this->get_image_url( 'reg', false );

        if ( $this->class_type === 'rim' ) {

            if ( $this->is_rim_model_page ) {
                $caption = brand_model_finish_name( $this->brand, $this->model, @$this->all_finishes[0] );
            } else {
                $caption = brand_model_finish_name( $this->brand, $this->model, $this->finish );
            }

            $alt_text = $caption . ' Wheels';

        } else {
            $caption = $this->brand_model_name();
            $alt_text = $caption . ' Tires';
        }
        
        ob_start();
        ?>
        <a class="img-tag-background img-tag-contain hover-overlay" href="<?= $img_url ? $img_url : image_not_available(); ?>" data-fancybox="product-main-image" data-caption="<?= $caption; ?>">
            <img src="<?= $img_url ? $img_url : image_not_available() ?>" alt="<?= $img_url ? $alt_text : "Image not found"; ?>">
            <span class="hover-overlay"></span>
            <span class="see-more">Click To Enlarge</span>
        </a>
        <?php
        return ob_get_clean();
    }

    /**
     * @return string
     */
    public function render_title() {

        if ( $this->context === 'by_vehicle' ) {

            $links = [
                html_link( get_vehicle_archive_url( 'tires', $this->vehicle->get_slugs()), 'Tires' ),
                html_link( get_vehicle_archive_url( 'rims', $this->vehicle->get_slugs()), 'Wheels' ),
                html_link( get_vehicle_archive_url( 'packages', $this->vehicle->get_slugs()), 'Packages' ),
            ];

            // we don't pass part numbers when a user changes sub size
            $sub_select_url = $this->get_url( true, false, false );

            $change_vehicle_btn = '  <button class="css-reset extra change-vehicle lb-trigger" data-for="sp_change_vehicle">[Change Vehicle]</button>';

            $change_vehicle_lightbox_content = get_change_vehicle_lightbox_content( 'sp_change_vehicle', [
                'title' => 'Change Vehicle',
                'hide_shop_for' => true,
                'base_url' => $this->get_url( false, false, false ),
            ], $this->vehicle );

            ob_start();
            ?>
            <div class="sp-title">
                <div class="vehicle-titles">
                    <div class="vehicle-and-sub">
                        <h2 class="vehicle"><?= gp_test_input( $this->vehicle->get_display_name( false ) ); ?><?= $change_vehicle_btn; ?></h2>
                        <?= $this->vehicle->render_sub_size_select( [
                            'on_white' => true,
                            'base_url' => $sub_select_url,
                        ] ); ?>
                    </div>
                    <p class="fitment"><?= gp_test_input( $this->vehicle->get_fitment_name() ); ?></p>
                    <p class="product-quick-links">See all <?= implode( ' / ', $links ); ?> for your vehicle.</p>
                </div>
            </div>
            <?= isset( $change_vehicle_lightbox_content ) ? $change_vehicle_lightbox_content : ''; ?>
            <?php
            return ob_get_clean();
        } else {
            // css needs this
            echo '<div class="sp-title empty"></div>';
        }
    }

    /**
     * @param $sizes
     * @param array $_userdata - unintentional things could happen if you pass in $this->userdata. Use only as needed.
     * @return array
     */
    public function query_products_by_vehicle_sizes( $sizes, $_userdata = array() ) {

        $brand = $this->brand->get( 'slug' );
        $model = $this->model->get( 'slug' );

        if ( $this->class_type === 'rim' ) {

            $userdata = array_merge( [
                'per_page' => -1,
                'sort' => 'single_product_page',
            ], $_userdata );

            $args = [
                'group_by_part_number' => false,
            ];

            // important to pass in empty strings here over null values
            $c1 = $this->color_1_slug ? $this->color_1_slug : '';
            $c2 = $this->color_2_slug ? $this->color_2_slug : '';
            $ff = $this->finish_slug ? $this->finish_slug : '';

            $results = query_rims_by_sizes_from_brand_model_finish( $sizes, $brand, $model, $c1, $c2, $ff, $userdata, $args );

            return Rims_Query_Fitment_Sizes::parse_results( $results );

        } else if ( $this->class_type === 'tire' ) {

            $userdata = array_merge( [
                'per_page' => -1,
                'sort' => 'single_product_page',
                'brand' => $brand,
                'model' => $model
            ], $_userdata );

            $args = [
                'group_by_part_number' => false,
            ];

            $results = query_tires_by_sizes( $sizes, $userdata, $args );
            return Tires_Query_Fitment_Sizes::parse_results( $results );

        } else {
            return array();
        }

    }

    /**
     * Vehicle + Fitment
     *
     * We may show this in addition to the Vehicle + Fitment + Product table
     *
     * @see $this->queue_table_for_selected_fitment_with_product()
     */
    public function queue_table_for_all_products_with_vehicle_and_fitment() {

        $cols = $this->get_table_columns();

        // get the table object
        // a more accurate title description would be "other sizes that fit your vehicles selected fitment size and/or that fitment sizes available sub sizes"
        $table = $this->get_table_rendering_object( $cols, array(
            'title' => 'Other Sizes That Fit Your Vehicle',
            // 'after_title' => '',
            // 'add_class' => '',
        ) );

        $table->set_no_results_html( 'No results.' );

        if ( $this->vehicle && $this->vehicle->has_wheel_set() ) {

            // Combine BASE sizes with all sub sizes
            $sizes = $this->vehicle->fitment_object->export_sizes();
            $sizes = array_merge( $sizes, $this->vehicle->fitment_object->export_sub_sizes_except( [] ) );
            $query = $this->query_products_by_vehicle_sizes( $sizes );

        } else {
            $query = array();
        }

        // queue the table for rendering later
        $table = $this->add_table_rows_for_vehicle_query( $table, $query );
        $this->tables[] = $table;
    }

    /**
     * Vehicle + Fitment + Product (we get here from product archive pages)
     */
    public function queue_table_for_selected_fitment_with_product() {

        $cols = $this->get_table_columns();

        // get the table object
        $table = $this->get_table_rendering_object( $cols, array(
            'title' => 'Selected Product',
            // we don't need this IF we are showing the 2nd table below, which we probably are
            // 'after_title' => '<a href="' . $this->get_url( true, true, false, false ) . '">[See All Substitution Sizes]</a>',
            'add_class' => '',
        ) );

        if ( $this->vehicle && $this->vehicle->trim_exists() ) {

            $userdata = array();

            // inject front and possibly rear part numbers into the query
            if ( $this->product_1 ) {
                $userdata[ 'part_number_front' ] = $this->product_1->get( 'part_number' );
            }

            if ( $this->product_2 ) {
                $userdata[ 'part_number_rear' ] = $this->product_2->get( 'part_number' );
            }

            // query by the selected size
            $query = $this->query_products_by_vehicle_sizes( $this->vehicle->fitment_object->export_sizes(), $userdata );

            // add rows to the table
            if ( $query ) {
                foreach ( $query as $q ) {

                    $front = gp_if_set( $q, 'front' );
                    $staggered = gp_if_set( $q, 'staggered' );
                    $rear = $staggered ? gp_if_set( $q, 'rear' ) : null;
                    $fitment_slug = gp_if_set( $q, 'fitment_slug' );

                    $table->add_row( array(
                        'front' => $front,
                        'rear' => $rear,
                        'staggered' => $staggered,
                        'fitment_slug' => $fitment_slug,
                        'sub_slug' => gp_if_set( $q, 'sub_slug' ),
                        'oem' => gp_if_set( $q, 'oem' ),
                    ) );
                }
            } else {
                $table->set_no_results_html( 'The product(s) selected do not fit the fitment selected for your vehicle.' );
            }

            // queue the table for rendering later
            $this->tables[] = $table;
        }
    }

    /**
     * Brand + Model + (finishes maybe) + Product. This should be the default
     * page to link to when showing a part number with a link (but without a vehicle)
     */
    public function queue_table_for_single_product_without_vehicle() {

        $cols = $this->get_table_columns();

        $table = $this->get_table_rendering_object( $cols, array(
            'title' => 'Product Specs',
            'after_title' => '<a href="' . $this->get_all_sizes_link() . '">[See All Sizes]</a>',
            'add_class' => '',
        ) );

        if ( $this->product_1 ) {

            $table->add_row( array(
                'staggered' => false,
                'front' => $this->product_1,
            ) );

            // not sure if we need to add this, so for now its off.
            // $this->all_products_shown[] = $this->product;
        } else {
            // unlikely message I believe..
            $table->set_no_results_html( 'Product not found.' );
        }

        // queue the table for rendering later
        $this->tables[] = $table;
    }

    /**
     * The default page: Just Brand + Model + (finishes for rims only)
     */
    public function queue_table_for_all_products_without_vehicle() {

        // model instances
        $db_products = [];
        $cols = $this->get_table_columns();

        $userdata = $this->userdata;
        $userdata[ 'sort' ] = 'single_product_page';

        if ( $this->class_type === 'rim' ) {
            $query = query_rims_by_finishes( $this->brand_slug, $this->model_slug, $this->color_1_slug, $this->color_2_slug, $this->finish_slug, $userdata );

        } else if ( $this->class_type === 'tire' ) {

            $query = query_tires_general( array_merge( $userdata, [
                'brand' => $this->brand_slug,
                'model' => $this->model_slug,
            ]) );

        } else {
            $query = array();
        }

        $table = $this->get_table_rendering_object( $cols, array(
            'title' => 'Available Sizes',
            'add_class' => 'all-sizes',
        ) );

        if ( $query ) {
            foreach ( $query as $q ) {

                // for efficiency sake, its a pretty good idea to inject the brand and model into the product objects
                // because some tables will show 100+ sizes, which would mean about 200 extra (simple) database queries

                if ( $this->class_type === 'rim' ) {
                    $product = new DB_Rim( $q, [
                        'brand' => $this->brand,
                        'model' => $this->model,
                        'finish' => $this->finish,
                    ] );

                    $db_products[] = $product;
                }

                if ( $this->class_type === 'tire' ) {

                    $product = new DB_Tire( $q, [
                        'brand' => $this->brand,
                        'model' => $this->model
                    ] );

                    $db_products[] = $product;
                }


                $table->add_row( array(
                    'front' => $product,
                    'staggered' => false,
                ) );
            }
        } else {
            // this is an unlikely message to be shown
            $table->set_no_results_html( 'No products found.' );
        }

        // queue the table for rendering later
        $this->tables[] = $table;

        return $db_products;
    }

    public function queue_tables_for_rim_finishes() {

        $db_products = [];

        foreach ( $this->all_finishes as $db_finish ) {
            /** @var DB_Rim_Finish $db_finish */

            $finish_name = $db_finish->get_finish_string();

            $userdata = [
                'sort' => 'single_product_page',
            ];

            list( $c1, $c2, $ff ) = $db_finish->get_colors_arr();

            $rims = query_rims_by_finishes( $this->brand_slug, $this->model_slug, $c1, $c2, $ff, $userdata );
            $count = is_array( $rims ) ? count( $rims ) : 0;

            ob_start();
            ?>
            <span title="Click to enlarge" class="table-title-with-image" href="<?= $db_finish->get_image_url( 'full' ); ?>" data-fancybox="rim-model-finishes" data-caption="<?= $finish_name; ?>">
                <span class="image-wrap">
                    <img src="<?= $db_finish->get_image_url('thumb' ); ?>" alt="">
                </span>
                <span class="title-wrap">
                    <span class="title"><?= $finish_name; ?></span>
                    <span class="etc">available in <?= $count; ?> size(s):</span>
                </span>
            </span>
            <?php
            $title_html = ob_get_clean();

            $table = $this->get_table_rendering_object( $this->get_table_columns(), [
                'title' => $title_html,
            ] );

            $table->set_no_results_html( 'No sizes available.' );

            foreach ( $rims as $q ) {

                $product = new DB_Rim( $q, [
                    'brand' => $this->brand,
                    'model' => $this->model,
                    'finish' => $db_finish,
                ] );

                $db_products[] = $product;

                $table->add_row( array(
                    'front' => $product,
                    'staggered' => false,
                ) );
            }

            $this->tables[] = $table;
        }

        return $db_products;
    }

    /**
     * Rims By Size archive page leads to here. Show products, but
     * we may filter them by parameters such as width, diameter, bolt pattern, offset, center bore.
     */
    public function queue_table_by_rim_size() {

        $cols = $this->get_table_columns();

        // Table 1
        $table = $this->get_table_rendering_object( $cols, array(
            'title' => 'Products Filtered by Size',
            'after_title' => '<a href="' . $this->get_all_sizes_link() . '">[see all sizes]</a>',
            'add_class' => 'filtered-sizes',
        ) );

        if ( $this->class_type === 'rim' ) {

            $userdata = $this->userdata;
            $userdata[ 'sort' ] = 'single_product_page';

            // get the results based on sizes in $_GET. Then get all results from brand/model combination.. and we'll show 2 tables.
            // we can simply pass in all user data. the variable names should be repeated form the archive query, therefore,
            // this *should* return the exact same results.

            $query = query_rims_by_finishes( $this->brand_slug, $this->model_slug, $this->color_1_slug, $this->color_2_slug, $this->finish_slug, $userdata );
            // $query = query_rims_by_brand_model( $this->brand_slug, $this->model_slug, $this->userdata );

            if ( $query ) {
                foreach ( $query as $q ) {

                    // make sure to inject brand and model objects
                    $product = new DB_Rim( $q, [
                        'brand' => $this->brand,
                        'model' => $this->model,
                        'finish' => $this->finish,
                    ] );

                    $table->add_row( array(
                        'front' => $product,
                        'staggered' => false,
                    ) );
                }

            } else {
                $table->set_no_results_html( 'No products found according to size filters.' );
            }
        }

        // queue the table for rendering later
        $this->tables[] = $table;
    }

    /**
     * Accepts a table instance so you can pass the title and other things
     * into the constructor first.
     *
     * Accepts database query results but calls add_row with an array in
     * a specific format.
     *
     * @param Single_Products_Page_Table $table
     * @param $query_results
     * @return Single_Products_Page_Table
     */
    public function add_table_rows_for_vehicle_query( Single_Products_Page_Table $table, $query_results ) {

        // add rows to the table
        if ( $query_results ) {
            foreach ( $query_results as $q ) {

                $front = gp_if_set( $q, 'front' );
                $staggered = gp_if_set( $q, 'staggered' );
                $rear = $staggered ? gp_if_set( $q, 'rear' ) : null;

                $table->add_row( [
                    'front' => $front,
                    'rear' => $rear,
                    'staggered' => $staggered,
                    'fitment_slug' => gp_if_set( $q, 'fitment_slug' ),
                    'sub_slug' => gp_if_set( $q, 'sub_slug' ),
                    'oem' => gp_if_set( $q, 'oem' ),
                ] );
            }
        }

        return $table;
    }

    /**
     *
     */
    public function queue_table_for_selected_sub_size() {

        $cols = $this->get_table_columns( [
            'fitment' => 'Substitution Size',
        ] );

        // get the table object
        $table = $this->get_table_rendering_object( $cols, array(
            'title' => 'Selected Substitution Size',
            'after_title' => '<a href="' . $this->get_url( true, false, false ) . '">[See All Substitution Sizes]</a>',
        ) );

        $table->set_no_results_html( 'No products were found that fit your selected substitution size.' );

        if ( $this->vehicle && $this->vehicle->has_substitution_wheel_set() ) {
            $query = $this->query_products_by_vehicle_sizes( $this->vehicle->fitment_object->export_selected_sub_size() );
        } else {
            $query = array();
        }

        // queue the table for rendering later
        $table = $this->add_table_rows_for_vehicle_query( $table, $query );
        $this->tables[] = $table;
    }

    /**
     * This is the "base" size, or just the "fitment" size. Ie. $vehicle->fitment_object->wheel_set
     */
    public function queue_table_for_base_size() {

        $cols = $this->get_table_columns();

        // get the table object
        $table = $this->get_table_rendering_object( $cols, array(
            'title' => 'Recommended Sizes and Pricing',
            'after_title' => '<a title="See All Sizes" href="' . $this->get_url( false, false, false ) . '">[Remove Vehicle]</a>',
        ) );

        $table->set_no_results_html( 'No products were found that match your selected fitment size.' );

        if ( $this->vehicle && $this->vehicle->has_wheel_set() ) {

            $ff = $this->vehicle->fitment_object;

            $query = $this->query_products_by_vehicle_sizes( [ $ff::get_size_from_fitment_and_wheel_set( $ff, $ff->wheel_set ) ] );

        } else {
            $query = array();
        }

        // queue the table for rendering later
        $table = $this->add_table_rows_for_vehicle_query( $table, $query );
        $this->tables[] = $table;
    }

    /**
     *
     */
    public function queue_table_for_sub_sizes() {

        $cols = $this->get_table_columns( [
            'fitment' => 'Substitution Size',
        ] );

        // get the table object
        $table = $this->get_table_rendering_object( $cols, array(
            'title' => 'Substitution Sizes and Pricing',
        ) );

        $table->set_no_results_html( 'No products were found for substitution sizes.' );

        if ( $this->vehicle && $this->vehicle->has_wheel_set() ) {

            $ff = $this->vehicle->fitment_object;
            $query = $this->query_products_by_vehicle_sizes( $ff->export_sub_sizes_except( [] ) );

        } else {
            $query = array();
        }

        // queue the table for rendering later
        $table = $this->add_table_rows_for_vehicle_query( $table, $query );
        $this->tables[] = $table;
    }

    /**
     * Need to queue tables, then render them later, so that
     * we know what products are shown before the tables are even rendered.
     * Definitely don't run this more than once. and of course, it needs to be run
     * early enough and not too late.
     */
    public function queue_table_objects() {

        $with_vehicle = $this->vehicle && $this->vehicle->trim_exists();
        $with_fitment = $this->vehicle && $this->vehicle->has_wheel_set();
        // a sub size cant exist without a fitment size
        $with_sub_size = $this->vehicle && $this->vehicle->has_substitution_wheel_set();
        $with_product = ( $this->product_1 );

        // vehicle and part number selected. end up here from product archive
        // pages with vehicle selected when clicking 'details' on a specific product.
        if ( $with_fitment && $with_vehicle && $with_product ) {


            $this->queue_table_for_selected_fitment_with_product();
            $this->queue_table_for_all_products_with_vehicle_and_fitment();

            return;
        }

        // vehicle but no product selected. end up here when adding your vehicle from
        // the single product page, as opposed to clicking a specific part number from
        // a product archive page with vehicle selected.
        if ( $with_vehicle && $with_fitment ) {
            if ( $with_sub_size ) {
                $this->queue_table_for_selected_sub_size();
                // $this->queue_table_for_non_selected_sub_sizes();
            } else {
                // base size basically means "not sub size"
                $this->queue_table_for_base_size();
                $this->queue_table_for_sub_sizes();
            }

            return;
        }

        // if url has part number but no vehicle...
        // show a single table with a single product, and a link to "all sizes"
        if ( $this->context === 'by_product' ) {
            $this->queue_table_for_single_product_without_vehicle();

            return;
        }

        if ( $this->class_type === 'rim' ) {

            if ( $this->context === 'by_size' ) {

                $this->queue_table_by_rim_size();

                return;

            } else if ( $this->is_rim_model_page ) {

                return $this->queue_tables_for_rim_finishes();
            }
        }

        // this is basically the default action, however, I want to ensure we don't have a vehicle
        // because this would make it look as if the products shown fit the vehicle.
        if ( ! $with_vehicle ) {
            return $this->queue_table_for_all_products_without_vehicle();
        }
    }

    /**
     * @return string
     */
    public function render_reviews() {

        if ( $this->class_type === 'tire' ) {
            $reviews = get_tire_reviews_by_product_attributes( $this->brand_slug, $this->model_slug, true );
            $url = get_tire_leave_review_url( $this->brand_slug, $this->model_slug );
        }

        if ( $this->class_type === 'rim' ) {

            if ( $this->is_rim_model_page ) {
                $reviews = get_rim_reviews_by_product_attributes( $this->brand_slug, $this->model_slug, '', '', '', true );
                // don't show a leave review URL (requires selected finish)
                $url = '';

            } else {
                $reviews = get_rim_reviews_by_product_attributes( $this->brand_slug, $this->model_slug, $this->color_1_slug, $this->color_2_slug, $this->finish_slug, true );
                $url = get_rim_leave_review_url( $this->brand_slug, $this->model_slug, $this->color_1_slug, $this->color_2_slug, $this->finish_slug );
            }
        }

        return render_reviews_from_db_results( $reviews, $this->class_type, [], $url );
    }

    public function render_tables() {

        if ( empty( $this->tables ) ) {
            return '';
        }

        ob_start();

        array_walk( $this->tables, function ( $t ) {

            echo $t->render();
        } );

        return ob_get_clean();
    }
}