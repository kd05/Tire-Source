<?php

/**
 * Class Tires_Page
 */
Final Class Page_Packages extends Page_Products_Filters_Methods {

	/**
	 * Links lightbox triggers to lightbox content
	 *
	 * @var
	 */
	protected $loop_lightbox_uid;

    /**
     * $this->userdata['type'] if it's a valid package type.
     *
     * Often this is empty, and we'll choose a default. When choosing
     * a default, this does not change.
     *
     * @var
     */
	public $package_type;

    /**
     * Changes based on date. Use this if $this->package_type is null
     * and no tire is currently selected. If a tire is selected, we have
     * to force the type to correspond to the tire. Ie. if a winter tire
     * is selected, we have to pair it with a winter approved rim.
     *
     * @var string
     */
    protected $default_package_type;

    /**
     * @var  string
     */
    protected $force_package_type;

	/**
	 * @var
	 */
	public $locale;

	/**
	 *
	 * Page_Tires constructor.
	 *
     * Page_Packages constructor.
     * @param array $userdata
     * @param array $args
     * @param null $vehicle
     * @throws Exception
     */
	public function __construct( $userdata = array(), $args = array(), $vehicle = null) {
		$this->class_type = 'packages'; // before parent::__construct()
		$this->class_type_singular = 'package'; // this is used for some css classes

		$this->locale = app_get_locale();

		// parent construct order matters.. do it.. here.
		parent::__construct( $userdata, $args, $vehicle );
		$this->ajax_action = 'package_filters';

		$pkg = gp_if_set( $this->userdata, 'pkg' );
		if ( $pkg ) {
			$cart = get_cart_instance();

			/** @var Cart_Package $package */
			$package = $cart->get_package( $pkg );
			$this->cart_package = $package ? $package : null;
		} else {
			$this->cart_package = null;
		}

		if ( tire_type_is_valid( @$this->userdata['type'] ) ) {
		    $this->package_type = $this->userdata['type'];
        }

        $this->default_package_type = get_default_package_type();

		// note: add to cart type of "package" is probably no longer a thing.
		$this->atc['type'] = 'multi';

        list( $this->context, $this->sub_context ) = $this->determine_context();

        if ( $this->context !== 'invalid' ) {
            $this->setup_meta_titles_etc();
            Header::$canonical = $this->get_canonical_url();
            $this->top_image_args = $this->build_top_image_args();
        }
	}

	/**
	 *
	 */
	public function determine_context(){

		if ( $this->vehicle->is_complete() ) {

			$tire_1 = gp_if_set( $this->userdata, 'tire_1' );
			$tire_2 = gp_if_set( $this->userdata, 'tire_2' );

			if ( $tire_1 || $tire_2 ) {

				$tire_1_object = DB_Tire::create_instance_via_part_number( $tire_1 );
				$tire_2_object = DB_Tire::create_instance_via_part_number( $tire_2 );

				if ( ! $tire_1_object ) {
                    return [ 'invalid', null ];
				}

				// set the type in userdata but we also need to track that we're enforcing the type
//				$this->force_package_type = $tire_1_object ? $tire_1_object->model->type->get( 'slug' ) : '';
//				$this->userdata['_type'] = $this->force_package_type;

				if ( $this->vehicle->fitment_object->wheel_set->get_selected()->is_staggered() ){

					if ( ! $tire_1_object || ! $tire_2_object ) {
                        return [ 'invalid', null ];
					}

					if ( $tire_1_object->get( 'model_id' ) !== $tire_2_object->get( 'model_id' ) ) {
                        return [ 'invalid', null ];
					}

					$this->tire_1 = $tire_1_object;
					$this->tire_2 = $tire_2_object;
                    return [ 'by_vehicle', 'tire_selected' ];

				} else {

					$this->tire_1 = $tire_1_object;
                    return [ 'by_vehicle', 'tire_selected' ];
				}
			}

			$rim_1 = gp_if_set( $this->userdata, 'rim_1' );
			$rim_2 = gp_if_set( $this->userdata, 'rim_2' );

			if ( $rim_1 || $rim_2 ) {

				$rim_1_object = DB_Rim::create_instance_via_part_number( $rim_1 );

				if ( ! $rim_1_object ) {
                    return [ 'invalid', null ];
				}

				if ( $this->vehicle->fitment_object->wheel_set->get_selected()->is_staggered() ){

					$rim_2_object = DB_Rim::create_instance_via_part_number( $rim_2 );

					if ( ! $rim_1_object || ! $rim_2_object ) {
                        return [ 'invalid', null ];
					}

					if ( $rim_1_object->get( 'model_id' ) !== $rim_2_object->get( 'model_id' ) ) {
                        return [ 'invalid', null ];
					}

					$this->rim_1 = $rim_1_object;
					$this->rim_2 = $rim_2_object;
                    return [ 'by_vehicle', 'rim_selected' ];

				} else {

					$this->rim_1 = $rim_1_object;
                    return [ 'by_vehicle', 'rim_selected' ];
				}
			}

            return [ 'by_vehicle', null ];
		}

		return [ 'invalid', null ];
	}

    /**
     * @param $row
     * @return stdClass
     */
	public function build_loop_data( $row ){

	    $ret = new stdClass();

        $data = Staggered_Package_Multi_Size_Query::parse_row( $row );

        $ret->loop_data = $data['raw_data'];
        $ret->loop_staggered = $data['staggered'];
        $ret->loop_front_tire = $data['front_tire'];
        $ret->loop_rear_tire = $data['rear_tire']; // null if not staggered
        $ret->loop_front_rim = $data['front_rim'];
        $ret->loop_rear_rim = $data['rear_rim'];  // null if not staggered
        $ret->loop_vqdr = Vehicle_Query_Database_Row::create_instance_from_products( $ret->loop_front_tire, $ret->loop_rear_tire, $ret->loop_front_rim, $ret->loop_rear_rim );
        $ret->loop_lightbox_uid = $this->get_lightbox_unique_id( $ret->loop_vqdr );

        return $ret;
	}

	/**
	 * Note that we could render this button when upgrading an existing package, or when the package does
	 * not exist yet. In either case, we remove the package ID from the URL when removing the product.. so
	 * the user won't be upgrading an existing package after clicking the button.
	 */
	public function get_remove_selected_product_button(){

	    $type = gp_test_input( @$this->userdata['_type'] );
	    $query = $type ? [ '_type' => $type ] : [];

	    $url = get_vehicle_archive_url( 'packages', $this->vehicle->get_slugs(), $query );

		$op = '';
		$op .= '<div class="remove-selected-product">';
		$op .= '<a href="' . $url . '" title="Remove Selected Product"><i class="fa fa-times"></i></a>';
		$op .= '</div>';

		return $op;
	}

	/**
	 *
	 */
	public function render_selected_tire(){

		$staggered = $this->tire_1 && $this->tire_2;

		$brand = $this->tire_1->brand->get( 'name' );
		$model = $this->tire_1->model->get('name' );

		$title = $staggered ? 'Tires Selected' : 'Tire Selected';

		//			$above_tire_1 = $staggered ? 'Front' : '';
		//			$above_tire_2 = $staggered ? 'Rear' : '';

		$cls = [ 'products-selected type-tires' ];
		$cls[] = $staggered ? 'count-2' : 'count-1';

        $op = '';
		$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';

		// title
		$op .= '<div class="ps-title">';
		$op .= '<h2 class="like-h3">' . $title . '</h2>';
		$op .= '</div>';

		$op .= '<div class="ps-item">';

		// brand/model/image
		$op .= '<div class="ps-top">';

		$op .= $this->get_remove_selected_product_button();

		$op .= '<div class="product-titles">';
		$op .= '<p class="brand">' . gp_test_input( $brand ) . '</p>';
		$op .= '<p class="model">' . gp_test_input( $model ) . '</p>';
		$op .= '</div>';
		$op .= '<div class="image-wrap"><div class="background-image contain" style="' . gp_get_img_style( $this->tire_1->get_image_url( 'thumb' ) ) . '"></div></div>';
		$op .= '</div>'; // ps-top

		// spec tables

		// $fields = $this->get_tire_spec_table_fields();
		$fields = [ 'part_number', 'size', 'load_index', 'speed_rating', 'price', 'type', 'stock' ];

		$_vqdr = Vehicle_Query_Database_Row::create_instance_from_products( $this->tire_1, $this->tire_2 );

		if ( $staggered ) {
			$op .= '<div class="spec-tables type-tire count-2">';
			$op .= spec_table_tires( $this->tire_1, $_vqdr, VQDR_INT_TIRE_1, 'Front', $fields );
			$op .= spec_table_tires( $this->tire_2, $_vqdr, VQDR_INT_TIRE_2, 'Rear', $fields );
			$op .= '</div>';
		} else {
			$op .= '<div class="spec-tables type-tire count-1">';
			$op .= spec_table_tires( $this->tire_1, $_vqdr, VQDR_INT_TIRE_1, '', $fields );
			$op .= '</div>';
		}

		$op .= '</div>'; // ps-item
		$op .= '</div>'; // products-selected
		return $op;

	}

	/**
	 *
	 */
	public function render_selected_rim(){

		$op = '';

		$staggered = ( $this->rim_1 && $this->rim_2 );

		$brand = $this->rim_1->brand->get( 'name' );
		$model = $this->rim_1->model->get('name' );

		$title = $staggered ? 'Wheels Selected' : 'Wheel Selected';

		//			$above_tire_1 = $staggered ? 'Front' : '';
		//			$above_tire_2 = $staggered ? 'Rear' : '';

		$cls = [ 'products-selected type-rims' ];
		$cls[] = $staggered ? 'count-2' : 'count-1';

		$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';

		// title
		$op .= '<div class="ps-title">';
		$op .= '<h2 class="like-h3">' . $title . '</h2>';
		$op .= '</div>';

		$op .= '<div class="ps-item">';

		// brand/model/image
		$op .= '<div class="ps-top">';

		$op .= $this->get_remove_selected_product_button();

		$model_string = $model;
		// $model_string .= ' ' . $this->rim_1->get_finish_string();

		$op .= '<div class="product-titles">';
		$op .= '<p class="brand">' . gp_test_input( $brand ) . '</p>';
		$op .= '<p class="model">' . gp_test_input( $model ) . '</p>';
		$op .= '<p class="finish">' . gp_test_input( $this->rim_1->get_finish_string() ) . '</p>';
		$op .= '</div>';

		// $op .= '<p class="finish">' . gp_test_input( $this->rim_1->get_finish_string() ) . '</p>';
		$op .= '<div class="image-wrap"><div class="background-image contain" style="' . gp_get_img_style( $this->rim_1->get_image_url( 'thumb' ) ) . '"></div></div>';
		$op .= '</div>'; // ps-top

		// $fields = $this->get_rim_spec_table_fields();
		// $fields = [ 'size', 'offset', 'bolt_pattern', 'price', 'type', 'colors', 'part_number'];
		$fields = [ 'part_number', 'size', 'offset', 'bolt_pattern', 'price', 'type', 'stock' ];

		$_vqdr = Vehicle_Query_Database_Row::create_instance_from_products( null, null, $this->rim_1, $this->rim_2 );

		if ( $staggered ) {
			$op .= '<div class="spec-tables type-rim count-2">';
			$op .= spec_table_rims( $this->rim_1, $_vqdr, VQDR_INT_RIM_1, 'Front', $fields, [], $this->vehicle );
			$op .= spec_table_rims( $this->rim_2, $_vqdr, VQDR_INT_RIM_2, 'Rear', $fields, [], $this->vehicle );
			$op .= '</div>'; // spec-tables
		} else {
			$op .= '<div class="spec-tables type-rim count-1">';
			$op .= spec_table_rims( $this->rim_1, $_vqdr, VQDR_INT_RIM_1, null, $fields, [], $this->vehicle );
			$op .= '</div>'; // spec-tables
		}

		$op .= '</div>'; // ps-item
		$op .= '</div>'; // products-selected
		return $op;
	}

    /**
     * @return string|null
     */
	public function render_above_sidebar_filters(){

		if ( $this->sub_context === 'tire_selected' ) {
			return $this->render_selected_tire();
		}

		if ( $this->sub_context === 'rim_selected' ) {
            return $this->render_selected_rim();
		}
	}

	/**
	 *
	 */
	public function render_item_top(){

		$op = '';
		$op .= '<div class="product-titles">';

		if ( $this->sub_context === 'rim_selected' ) {

			$img_url = $this->loop_front_tire->get_image_url( 'thumb' );
			$img_url_2 = $this->loop_front_rim->get_image_url( 'thumb' );

			$brand_name = $this->loop_front_tire->brand->get( 'name' );
			$model_name = $this->loop_front_tire->model->get( 'name' );

			$op .= '<p class="brand">' . gp_test_input( $brand_name ) . '</p>';
			$op .= '<p class="model">' . gp_test_input( $model_name ) . '</p>';

		} else {

			$img_url = $this->loop_front_rim->get_image_url( 'thumb' );
			$img_url_2 = $this->loop_front_tire->get_image_url( 'thumb' );

			$brand_name = $this->loop_front_rim->brand->get( 'name' );
			$model_name = $this->loop_front_rim->model->get( 'name' );
			$finish = $this->loop_front_rim->get_finish_string();

			$op .= '<p class="brand">' . gp_test_input( $brand_name ) . '</p>';
			$op .= '<p class="model">' . gp_test_input( $model_name ) . '</p>';
			$op .= '<p class="finish">' . gp_test_input( $finish ) . '</p>';
		}

		$op .= '</div>'; // product-titles

		// old
		$op .= '<div class="img-wrap multi">';

		$op .= '<span title="View Details" class="img-link lb-trigger" data-for="' . $this->loop_lightbox_uid . '"></span>';

		$op .= '<div class="img-wrap-2">';

		$op .= '<div class="img-secondary">';
		$op .= '<div class="icon"><div class="icon-2"><i class="fa fa-plus"></i></div></div>';
		$op .= '<div class="background-image bg-secondary contain" style="' . gp_get_img_style( $img_url_2 ) . '"></div>';
		$op .= '</div>';

		$op .= '<div class="background-image bg-main contain" style="' . gp_get_img_style( $img_url ) . '"></div>';

		$op .= '</div>';
		$op .= '</div>';

		$op .= $this->get_package_details();

		return $op;
	}

	/**
	 * @param      $filter_slug
	 * @param bool $grouped_by_model - ignore this for packages
	 *
	 * @return null
	 */
	public function get_dynamic_filter_value_from_loop_data( $filter_slug, $grouped_by_model = false ) {

		// default value MUST be null, not false or ''
		$add = null;

		switch( $filter_slug ) {
			// package type here is tire type.. there is some logic for rims in the queries but that makes no difference right here.
			case 'package_type':
				$add = $this->loop_front_tire->model->type->get( 'slug' );
				break;
				// steel-alloy, not a thing for pacakges..
//			case 'rim_type':
//				$add = $this->loop_front_rim->get( 'type' );
//				break;
			case 'rim_style':
				$add = $this->loop_front_rim->get( 'style' );
				break;
			case 'rim_brand':
				$add = $this->loop_front_rim->get( 'brand_slug' );
				break;
			case 'rim_model':
				$add = $this->loop_front_rim->get( 'model_slug' );
				break;
			case 'rim_color_1':
				$add = $this->loop_front_rim->finish->get( 'color_1' );
				break;
			case 'rim_color_2':
				$add = $this->loop_front_rim->finish->get( 'color_2' );
				break;
			case 'rim_finish':
				$add = $this->loop_front_rim->finish->get( 'finish' );
				break;
			case 'tire_brand':
				$add = $this->loop_front_tire->get( 'brand_slug' );
				break;
			case 'tire_model':
				$add = $this->loop_front_tire->get( 'model_slug' );
				break;
			case 'tire_class':
				$add = $this->loop_front_tire->model->class->get( 'slug' );
				break;
			case 'tire_category':
				$add = $this->loop_front_tire->model->category->get( 'slug' );
				break;
			case 'tire_load_index':
				// note: this just makes no sense for staggered therefore we may disable it
				$add = $this->loop_front_tire->get( 'load_index' );
				break;
			case 'tire_speed_rating':
				// note: this just makes no sense for staggered therefore we may disable it
				$add = $this->loop_front_tire->get( 'speed_rating' );
				break;
			case 'package_price':
				$total_price = gp_if_set( $this->loop_data, 'total_price', 0 );
				$total_cents = dollars_to_cents( $total_price );
				$add = $total_cents;
				break;
		}

		return $add;
	}

	/**
	 * @return array
	 */
	public function get_allowed_filters() {

		$ret = array();

		$type = gp_if_set( $this->userdata, '_type' );

		if ( $this->context === 'by_vehicle' ) {
			if ( $this->sub_context === 'rim_selected' ) {

				// show tire filters
				$ret[] = 'package_type';
				$ret[] = 'tire_brand';
				$ret[] = 'tire_class';
				$ret[] = 'tire_category';

				// only show for non-staggered cuz it makes no sense for staggered
				if ( ! $this->vehicle->fitment_object->wheel_set->is_staggered() ) {
					$ret[] = 'tire_load_index';
					$ret[] = 'tire_speed_rating';
				}

				$ret[] = 'package_price';

			} else {

				// show rim filters
				$ret[] = 'package_type';
				$ret[] = 'rim_brand';
				$ret[] = 'rim_color_1';

				if ( $type !== 'winter' ) {
					$ret[] = 'rim_color_2';
					$ret[] = 'rim_finish';
				}

				$ret[] = 'rim_style';
				$ret[] = 'package_price';
			}
		}

		return $ret;
	}

	/**
     * @param Vehicle_Query_Database_Row $vqdr
     * @return string
     */
	public static function get_lightbox_unique_id( Vehicle_Query_Database_Row $vqdr ){

		$unique = implode( "|", array_map( function( $product ){
		    return $product->get_primary_key_value();
        }, $vqdr->get_db_products() ) );

		return md5( $unique );
	}

    /**
     * @param $img_url
     * @param string $caption
     * @return string
     */
	public function render_lightbox_image( $img_url, $caption = '' ) {

		gp_set_global( 'require_fancybox', true );

		$count = get_count( 'pkg_lightbox_image' );
		$data_fancybox = 'pkg-' . $count;

		$op = '';
		$op .= '<div class="img-wrap">';
		$op .= '<div class="img-wrap-2">';
		$op .= '<a data-caption="' . $caption . '" class="background-image contain has-lightbox" href="' . $img_url . '" data-fancybox="' . $data_fancybox . '" style="' . gp_get_img_style( $img_url ) . '">';
		// $op .= '<img src="' . $img_url . '">';
		$op .= '<span class="see-more"><i class="fa fa-search-plus"></i></span>';
		$op .= '</a>';
		$op .= '</div>'; // img-wrap-2
		$op .= '</div>'; // img-wrap

		return $op;
	}

    /**
     * @param bool $purchasable
     * @return string
     */
	public function get_lightbox_content( $purchasable = true ){

		$op = '';

		$lightbox_css_class = 'package-popup';
		$lightbox_css_class .= $this->loop_staggered ? ' staggered' : ' not-staggered';

		// data-lightbox-cat in case we want to select all and delete (like upon ajax pagination or filters for example)
		$op .= '<div class="lb-content" data-lightbox-class="' . $lightbox_css_class . '" data-lightbox-id="' . $this->loop_lightbox_uid . '" data-lightbox-cat="package-popup">';

		// title
		$op .= '<div class="lb-title">';
		$op .= '<h2 class="text like-h2">Your Wheel Package</h2>';
		$op .= '<button class="icon css-reset lb-close"><i class="fa fa-times"></i></button>';
		$op .= '</div>'; // lb-title

		$rim_brand = $this->loop_front_rim->brand->get( 'name' );
		$rim_model = $this->loop_front_rim->model->get( 'name' );
		$rim_image = $this->loop_front_rim->get_image_url();

		$tire_brand = $this->loop_front_tire->brand->get( 'name' );
		$tire_model = $this->loop_front_tire->model->get( 'name' );
		$tire_image = $this->loop_front_tire->get_image_url();

		// products
		$op .= '<div class="products">';
		$op .= '<div class="products-flex">';

		// Left Column - Rim(s)
		$op .= '<div class="pr-col col-left">';
		$op .= '<div class="pr-col-2">';

		// title
		$op .= '<div class="title-wrap product-titles">';
		$op .= '<h2 class="brand">' . gp_test_input( $rim_brand ) . '</h2>';
		$op .= '<p class="model">' . gp_test_input( $rim_model ) . ' (' . gp_test_input( $this->loop_front_rim->get_finish_string() ) . ')</p>';
		$op .= '</div>'; // title-wrap

		// img
		$op .= $this->render_lightbox_image( $rim_image, $this->loop_front_rim->brand_model_finish_name() );

		$fields_rims = [ 'part_number', 'size', 'offset', 'bolt_pattern', 'price', 'type', 'stock' ];

		if ( $this->loop_staggered ) {
			$op .= '<div class="spec-tables spec-rims count-2">';
			$op .= spec_table_rims( $this->loop_front_rim, $this->loop_vqdr, VQDR_INT_RIM_1, 'Front', $fields_rims, [], $this->vehicle );
			$op .= spec_table_rims( $this->loop_rear_rim, $this->loop_vqdr, VQDR_INT_RIM_2, 'Rear', $fields_rims, [], $this->vehicle );
			$op .= '</div>'; // spec-tables
		} else {
			$op .= '<div class="spec-tables spec-rims count-1">';
			$op .= spec_table_rims( $this->loop_front_rim, $this->loop_vqdr, VQDR_INT_RIM_1, '', $fields_rims, [], $this->vehicle );
			$op .= '</div>'; // spec-tables
		}

		$op .= '</div>'; // pr-col-2
		$op .= '</div>'; // col-left

		$op .= '<div class="sep"></div>'; // border thing

		// Right Column - Tire(s)
		$op .= '<div class="pr-col col-right">';
		$op .= '<div class="pr-col-2">';

		// title
		$op .= '<div class="title-wrap product-titles">';

		if ( ! $this->tire_1 && ! $this->rim_1 ) {
			$op .= '<p class="before-title suggested">Suggested Tire</p>';
		}

		$op .= '<h1 class="brand like-h1">' . gp_test_input( $tire_brand ) . '</h1>';
		$op .= '<h2 class="model">' . gp_test_input( $tire_model ) . '</h2>';
		$op .= '</div>'; // title-wrap

		// img
		$op .= $this->render_lightbox_image( $tire_image, $this->loop_front_tire->brand_model_name() );

		$fields_tires = [ 'part_number', 'size', 'load_index', 'speed_rating', 'price', 'type', 'stock' ];

		if ( $this->loop_staggered ) {
			$op .= '<div class="spec-tables spec-tires count-2">';
			$op .= spec_table_tires( $this->loop_front_tire, $this->loop_vqdr, VQDR_INT_TIRE_1, 'Front', $fields_tires );
			$op .= spec_table_tires( $this->loop_rear_tire, $this->loop_vqdr, VQDR_INT_TIRE_2, 'Rear', $fields_tires );
			$op .= '</div>'; // spec-tables
		} else {
			$op .= '<div class="spec-tables spec-tires count-1">';
			$op .= spec_table_tires( $this->loop_front_tire, $this->loop_vqdr, VQDR_INT_TIRE_1, '', $fields_tires );
			$op .= '</div>'; // spec-tables
		}


		$op .= '</div>'; // pr-col-2
		$op .= '</div>'; // col-right

		$op .= '</div>'; // products-flex
		$op .= '</div>'; // products

		$op .= $this->get_package_details();

		$op .= '<div class="buttons">';

		if ( $purchasable ) {
			// Add To Cart
			$atc = $this->get_current_add_to_cart_btn_args();
			$op .= '<div class="button-1 color-red"><button class="ajax-add-to-cart" data-cart="' . gp_json_encode( $atc ) . '">Add to Cart</button></div>';
		}

		// Change Tires/Rims
		list( $change_button_url, $change_text ) = $this->get_current_change_btn_url();
		$op .= '<div class="button-1 color-black"><a href="' . $change_button_url . '">' . $change_text . '</a></div>';
		$op .= '</div>';
		$op .= '</div>';
		return $op;
	}

	/**
	 * @return string
	 */
	public function get_package_details(){

		$op = '';
		$op .= '<div class="pkg-details">';
		$op .= '<p class="price-text">4 Wheels + 4 Tires + Accessories Kit</p>';
		$price = $this->get_current_package_price();
		$op .= '<p class="price like-h2 red bold">' . print_price_dollars( $price, ',', '$', '' ) . '</p>';

		$op .= '<p class="stock-level">' . $this->loop_vqdr->get_item_set_stock_amount_html() . '</p>';
		$op .= '</div>'; // details

		return $op;
	}

    /**
     * @param bool $install_kit
     * @return float
     */
	public function get_current_package_price( $install_kit = true ){

		$price = 0;

		if ( $this->loop_staggered ) {

			$price += $this->loop_front_rim->get_price_dollars_raw() * 2;
			$price += $this->loop_rear_rim->get_price_dollars_raw() * 2;

			$price += $this->loop_front_tire->get_price_dollars_raw() * 2;
			$price += $this->loop_rear_tire->get_price_dollars_raw() * 2;

		} else {
			$price += $this->loop_front_rim->get_price_dollars_raw() * 4;
			$price += $this->loop_front_tire->get_price_dollars_raw() * 4;
		}

		if ( $install_kit ) {
			$install_kit_price = get_install_kit_price( get_install_kit_part_number( $this->vehicle->fitment_object->stud_holes ) );
			$price += $install_kit_price;
		}

		return format_price_dollars( $price );
	}

	/**
	 * Current meaning the current loop item
	 *
	 * @return array
	 */
	public function get_current_add_to_cart_btn_args(){
		$atc = $this->atc;
		$atc['items'] = $this->get_add_to_cart_items();
		return $atc;
	}

    /**
     * @return array
     */
	public function get_current_change_btn_url(){

	    $query = [];

		// if a rim is selected, then we are showing tires, and the button links to page showing all rims for selected tire...
		// so it says "change rims", but we need to inject the tire(s) into the url. If that's not confusing I don't know what is.
		if ( $this->sub_context === 'rim_selected' ) {

			if ( $this->loop_staggered ) {
			    $query['tire_1'] = $this->loop_vqdr->db_tire_1->get( 'part_number' );
                $query['tire_2'] = $this->loop_vqdr->db_tire_2->get( 'part_number' );
			} else {
                $query['tire_1'] = $this->loop_vqdr->db_tire_1->get( 'part_number' );
			}

			$text = 'Change Rims';

		} else {

            if ( $this->loop_staggered ) {
                $query['rim_1'] = $this->loop_vqdr->db_rim_1->get( 'part_number' );
                $query['rim_2'] = $this->loop_vqdr->db_rim_2->get( 'part_number' );
            } else {
                $query['rim_1'] = $this->loop_vqdr->db_rim_1->get( 'part_number' );
            }

			$text = 'Change Tires';
		}

		list( $types, $type ) = $this->get_package_type_options_and_selected();

		// the type has to persist when the user clicks the link
		if ( $type ) {
			$change_btn_args['type'] = $type;
		}

		return [ get_vehicle_archive_url( 'packages', $this->vehicle->get_slugs(), $query ), $text ];
	}

	/**
	 *
	 */
	public function render_item_bottom(){

        list( $change_button_url, $change_text ) = $this->get_current_change_btn_url();

		$purchasable = $this->loop_vqdr->item_set_is_purchasable();

		$details = '';
		if ( $purchasable ) {
			$details .= '<div class="main">Details</div>';
			$details .= '<div class="extra">(Add To Cart)</div>';
		} else {
			$details .= '<div class="main">Details</div>';
		}

        $op = '';
		$op .= '<div class="pi-buttons count-2">';
		$op .= '<div class="pi-button color-red"><button title="View Details" type="button" class="css-reset lb-trigger" data-for="' . $this->loop_lightbox_uid . '">' . $details . '</button></div>';
		$op .= '<div class="pi-button color-black"><a href="' . $change_button_url . '">' . $change_text . '</a></div>';
		$op .= '</div>'; // pi-buttons

		$op .= $this->get_lightbox_content( $purchasable );
		return $op;
	}

	/**
	 * Call this from within a loop.. its dependant on loop items.
	 *
	 * @return array
	 */
	public function get_add_to_cart_items(){

		$items = array();

		// making this value equal among all items indicates that
		// we should try to add them to the same package upon
		// adding them to the cart, even though the package does not exist yet.
		// if the package does exist, items do not need a pkg temp ID,
		// instead, there is an array index for 'pkg' right next to 'items' in the parent array
		$pkg_temp_id = $this->package_exists ? null : 1;

		if ( $this->loop_staggered ) {

			// front tire
			$items[] = array(
				'type' => 'tire',
				'pkg_temp_id' => $pkg_temp_id,
				'loc' => 'front',
				'quantity' => 2,
				'part_number' => $this->loop_front_tire->get( 'part_number' ),
			);

			// rear tire
			$items[] = array(
				'type' => 'tire',
				'pkg_temp_id' => $pkg_temp_id,
				'loc' => 'rear',
				'quantity' => 2,
				'part_number' => $this->loop_rear_tire->get( 'part_number' ),
			);

			// front rim
			$items[] = array(
				'type' => 'rim',
				'pkg_temp_id' => $pkg_temp_id,
				'loc' => 'front',
				'quantity' => 2,
				'part_number' => $this->loop_front_rim->get( 'part_number' ),
			);

			// rear rim
			$items[] = array(
				'type' => 'rim',
				'pkg_temp_id' => $pkg_temp_id,
				'loc' => 'rear',
				'quantity' => 2,
				'part_number' => $this->loop_rear_rim->get( 'part_number' ),
			);

		} else {

			// front/universal tire
			$items[] = array(
				'type' => 'tire',
				'pkg_temp_id' => $pkg_temp_id,
				'loc' => 'universal',
				'quantity' => 4,
				'part_number' => $this->loop_front_tire->get( 'part_number' ),
			);

			// front/universal rim
			$items[] = array(
				'type' => 'rim',
				'pkg_temp_id' => $pkg_temp_id,
				'loc' => 'universal',
				'quantity' => 4,
				'part_number' => $this->loop_front_rim->get( 'part_number' ),
			);

		}

		return $items;
	}

	public function get_package_type_options_and_selected(){
	    return Page_Packages::get_package_type_options_and_selected__static( @$this->userdata['type'], get_default_package_type(), $this->tire_1, $this->rim_1 );
    }

    /**
     * ie. winter, summer, all-season, all-winter..
     *
     * Often no type is provided to us via the URL ($this->userdata['type']),
     * so we may choose a default type depending on the time of year.
     *
     * But if a tire or rim is already selected then we may have to filter
     * the available type options. ie. if the rim is not winter approved,
     * we can't let the user select winter as package type.
     *
     * Note that the package queries cannot run without a type, so it's not
     * an optional filter for the packages page.
     *
     * @param $chosen
     * @param $default
     * @param DB_Tire|null $tire_1
     * @param DB_Rim|null $rim_1
     * @return array
     */
	public static function get_package_type_options_and_selected__static( $chosen, $default, $tire_1, $rim_1 ){

        $items = Static_Array_Data::tire_model_types();

        if ( ! is_tire_type_valid( $chosen ) ) {
            $chosen = '';
        }

        if ( ! is_tire_type_valid( $default ) ) {
            // we should never get to here realistically.
            $default = 'all-season';
        }

        // if a rim is selected:
        if ( $rim_1 ) {

            if ( $rim_1->is_winter_approved() ) {
                return [ $items, $chosen ? $chosen : $default ];
            }

            $items = array_filter( $items, function( $key ) {
                return $key !== 'winter';
            }, ARRAY_FILTER_USE_KEY );

            if ( in_array( $chosen, array_keys( $items ) ) ) {
                return [ $items, $chosen ];
            } else if ( in_array( $default, array_keys( $items ) ) ) {
                return [ $items, $default ];
            } else {
                // I think we could only get to here if user visits a URL that
                // they would not be able to visit using the sidebar filters.
                return [ $items, @array_keys( $items )[0] ];
            }

        } else if ( $tire_1 ) {

            // force the package type according to the selected tire type
            $type = $tire_1->model->type->get( 'slug' );

            $items = [
                $type => get_tire_type_name( $type ),
            ];

            // return the single type and make it also selected.
            return [ $items, $type ];
        }

        return [ $items, $chosen ? $chosen : $default ];
    }

    /**
     * @param $userdata
     * @return array
     */
	public function query_products( $userdata ){

		if ( ! $this->context === 'by_vehicle' ) {
			return array();
		}

		list( $_, $type ) = $this->get_package_type_options_and_selected();

        $args = array();

        // only singular sizes here
        $sizes = $this->vehicle->fitment_object->export_sizes();
        $size = array_values( $sizes )[0];

        assert( validate_tire_size_array( $size ) );
        assert( validate_rim_size_array( $size ) );

        if ( $this->sub_context === 'tire_selected' ) {
            $args['tire_1'] = $this->tire_1;
            $args['tire_2'] = $this->tire_2; // might be null but that's fine
        } else if ( $this->sub_context === 'rim_selected' ) {
            $args['rim_1'] = $this->rim_1;
            $args['rim_2'] = $this->rim_2; // might be null but that's fine
        } else {

            // get the cheapest in stock tire.. or in a much more complicated way, the cheapest
            // in stock pair of tires that share the same model.. and of course, fit the fitment, while giving it the tire type.
            if ( $size['staggered'] ) {
                $tire_pairs = Vehicle_Queries_PHP::select_staggered_tires_ordered_by_package_best_recommended( $size, $type, $this->locale, 1 );
            } else {
                $tire_pairs = Vehicle_Queries_PHP::select_not_staggered_tires_ordered_by_package_best_recommended( $size, $type, $this->locale, 1 );
            }

            // NO RESULTS - because no tires were found
            if ( ! $tire_pairs ) {
                queue_dev_alert( "NO_PKG_RESULTS_CUZ_NO_TIRES" );
                return array();
            }

            /** @var Vehicle_Query_Database_Row $tire_pair */
            $tire_pair = $tire_pairs ? array_values( $tire_pairs )[0] : false;

            $tire_pair->setup_db_objects();

            if ( ! $tire_pair ) {
                return array();
            }

            $args['tire_1'] = $tire_pair->db_tire_1;

            if ( $size['staggered'] ) {
                $args['tire_2'] = $tire_pair->is_staggered() ? $tire_pair->db_tire_2 : null;
            }
        }

        $ret = query_packages_by_sizes( [ $size ], $type, $userdata, $args );
        return $ret;
	}
}


