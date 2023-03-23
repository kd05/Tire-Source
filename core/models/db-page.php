<?php

/**
 * @see DB_Page_Meta
 * @see Page_Meta
 *
 * Class DB_Page
 */
Class DB_Page extends DB_Table {

	const RIM_BRAND_PREFIX = "_rim_brand_";
	const TIRE_BRAND_PREFIX = "_tire_brand_";
	const TIRE_TYPE_PREFIX = "_tire_type_";
	const LANDING_PAGE_PREFIX = "_landing_";

	protected static $prefix = "page_";
	protected static $primary_key = 'page_id';
	protected static $table = DB_pages;

	/*
	 * An array of keys required to instantiate the object.
	 */
	protected static $req_cols = array();

	// db columns
	protected static $fields = array(
		'page_id',
		'page_name',
		'page_slug',
		'page_type',
		'page_date',
		'page_template',
		'page_dynamic',
	);

	/**
	 * page_name - The human readable page identifier for the page, ie. "home", "contact"
	 *
	 * page_dynamic - If true, the page should automatically exist on the front-end
	 * due to its existence in the database table. This feature will require a front
	 * controller which we don't currently have, but I may end up using. If false, then
	 * inserting a database row does nothing, except that the code may use a function
	 * to query a page by name, and then retrieve database values from it.
	 *
	 * page_slug - determines the permalink if page_dynamic is true. Probably
	 * this would be something like "about-us" or "company/who-we-are", but also
	 * possibly "privacy-policy.php". Front controller (is using it) will do something
	 * along the lines of, find a page with page slug matching the current URL or
	 * show 404.
	 *
	 * page_template - If page_dynamic is true, determines the PHP file to serve.
	 *
	 * page_date - for pages that are not dynamic we probably don't need this.
	 *
	 * page_type - will default to page or null or empty string probably. In the future,
	 * we may add a type called "blog" and then query all pages ordered by date for example.
	 * Similar to WordPress post_type.
	 *
	 * Special note: The intended usage so far is to create non dynamic permanent pages.
	 * We may have a rule in place that non-dynamic pages can't be deleted. I don't know yet.
	 * The basic idea here is to have a logical place to store information on pages with
	 * given names. Then we can more or less hardcode all the pages but have access to functions
	 * like get_page_meta_via_name( "home", "top_title" ). The extended usage which we might
	 * add in the future, is the ability to create pages from the back-end and have them
	 * automatically show up on the site.
	 *
	 * @var array
	 */
	protected static $db_init_cols = array(
		'page_id' => 'int(11) unsigned NOT NULL auto_increment',
		'page_name' => 'varchar(255) default \'\'',
		'page_slug' => 'varchar(255) default \'\'',
		'page_type' => 'varchar(255) default \'\'',
		'page_date' => 'varchar(255) default \'\'',
		'page_template' => 'varchar(255) default \'\'',
		'page_dynamic' => 'bool DEFAULT 0',
	);

	protected static $db_init_args = array(
		'PRIMARY KEY (`page_id`)',
		'UNIQUE(page_name)',
		// can't do this because slug needs to be optional
		// 'UNIQUE(page_slug)',
	);

	/**
	 * DB_Cache constructor.
	 *
	 * @param       $data
	 * @param array $options
	 */
	public function __construct( $data, $options = array() ) {
		parent::__construct( $data, $options );
	}

	/**
	 * True if we have created a file corresponding to the pages
	 * names somewhere in the ADMIN_TEMPLATES directory. For example,
	 * "home" is registered. A newly created page called "test" is probably
	 * not.
	 *
	 * Note: there's a decent change we'll put some logic in place so that registered
	 * pages cannot be deleted.
	 */
	public function is_registered() {
		return Pages::exists( $this->get_name() );
	}

	/**
	 * For some pages we want them to be in the database but not allow the admin
	 * user to delete, because doing so would break a lot of things.
	 *
	 * @return bool
	 */
	public function can_be_deleted_via_admin_user() {

	    // only checks if the page name looks like a dynamic page,
        // doesn't check if the dynamically linked page exists (ie. for a real tire brand)
        // however, we simply don't allow these to be deleted via the admin panel, because doing
        // so could let us lose a lot of data. So if we have to delete dynamic pages, a dev will have
        // to do it. Or we'll have to add logic to check if the linked page even exists.
	    if ( $this->is_dynamic_via_name() ) {
            return false;
        }

	    if ( $this->is_registered() ) {
	        return false;
        }

	    // have to let deletion of general pages since there's also a tool to insert them.
		return true;
	}

	/**
	 * Sort of did a stupid thing and put an unused column in the database
	 * called "is_dynamic", and then decided to also call certain page names
	 * dynamic, ie "_tire_type_winter". So don't confuse the two.
	 *
	 * @return bool
	 */
	public function is_dynamic_via_name(){

		$name = $this->get( 'name' );

		$test = [
		    self::LANDING_PAGE_PREFIX,
			self::TIRE_BRAND_PREFIX,
			self::RIM_BRAND_PREFIX,
			self::TIRE_TYPE_PREFIX
		];

		$ret = false;

		foreach ( $test as $t ) {
			if ( strpos( $name, $t ) !== false ) {
				$ret = true;
			}
		}

		return $ret;
	}

	/**
	 * The field set for the admin page which edits this page...
	 *
	 * This adds field for the user to edit but you have to modify the code
	 * in the php files if these fields will do anything. For example, most
	 * pages will use a generic layout and so files will exist that include
	 * a default template file. That file will pay attention to blocks
	 * which are registered below.
	 *
     * @param bool $keys_only - if true, only the keys of the registered fields are returned.
     * @return Field_Set|array|null
     */
	public function get_admin_edit_field_set( $keys_only = false ) {

	    // add all keys here. This let's us display the available fields to edit
        // in the admin section. This is useful because different page types have
        // different fields to edit.
	    $keys = [];

		$name = $this->get_name();
		$field_set = new Field_Set();
		$presets   = new Field_Set_Item_Presets();

		if ( ! in_array( $name, [
			'tire',
			'rim',
		] ) ) {
			$field_set->register( $presets::meta_title( "") );
			$field_set->register( $presets::meta_desc( "") );
            $keys[] = 'meta_title';
            $keys[] = 'meta_desc';
		}

		// packages had existing textarea stored in options table form before we added any of this.
		if ( in_array( $name, [
				'_landing_tires',
				'_landing_rims',
				'_landing_packages',
			] ) ) {

			$field_set->register( $presets::html_textarea( "landing_desc", "Landing Description (Text in the top image, below the title). HTML is allowed.") );
            $keys[] = 'landing_desc';

            // Lower content for tires and wheels catalogues
            $field_set->register( $presets::html_textarea( "lower_desc", "Lower Description (Below Brands, HTML is allowed.)") );
            $keys[] = 'lower_desc';

		} else if ( $this->is_dynamic_via_name() ) {

            $field_set->register( $presets::archive_desc() );
            $keys[] = 'archive_desc';

            // Lower content for tires and wheels catalogues
            $field_set->register( $presets::html_textarea( "lower_desc", "Lower Description (Below list of products and pagination). HTML is allowed.") );
            $keys[] = 'lower_desc';
        }

		if ( $keys_only ) {
		    return $keys;
        }

		return $field_set && $field_set->count() > 0 ? $field_set : null;
	}

	/**
	 * @return array
	 */
	public function handle_ajax_edit_form() {

		$field_set       = $this->get_admin_edit_field_set();
		$field_set->page = $this;

		$field_set->save_fields();

		$success = ! $field_set->has_errors();

		return array(
			'msgs' => $field_set->get_messages( true ),
			'success' => $success,
		);
	}

    /**
     * Can pass in additional html in $args to add html that
     * doesn't belong in a field set.
     *
     * If you require a custom form for whatever reason, you don't have
     * to use this function. Just create the file in the correct directory
     * and do your own thing.
     *
     * @param array $args
     * @return string
     */
	public function render_ajax_edit_form( $args = [] ) {

		$field_set = $this->get_admin_edit_field_set();

		// in the future we may have use for rendering
		// edit forms without the use of field sets, but not right now.
		if ( ! $field_set ) {
			return '';
		}

		$field_set->page = $this;

		$op = '';
		$op .= '<form id="admin-edit-page" class="ajax-general form-style-basic admin-edit-page" action="' . AJAX_URL . '">';

		$op .= get_ajax_hidden_inputs_for_general_ajax( 'edit_page' );

		$op .= '<input type="hidden" name="page_id" value="' . (int) $this->get( 'page_id' ) . '">';

		$op .= '<div class="form-items">';

		$op .= $field_set ? $field_set->render_fields() : '';

		// $op .= get_form_submit();

		$op .= '</div>';

		$op .= '<div class="form-right-sidebar">';
		$op .= '<div class="sb-2">';
		$op .= '<button type="submit">Save Changes</button>';
		$op .= '</div>';
		$op .= '</div>';

		$op .= '</form>';

		return $op;
	}

	/**
	 * Allow deleting pages form the admin archive table..
	 *
	 * @return array
	 */
	public function get_admin_archive_page_args() {
		return array(
			'do_delete' => false,
		);
	}

	/**
	 *
	 */
	public function get_order_by_args_for_admin_table(){
		return [
			'page_name ASC',
		];
	}

    /**
     * @return bool
     */
	public function is_rim_brand(){
	    return strpos( $this->get( 'page_name' ), self::RIM_BRAND_PREFIX ) === 0;
    }

    public function is_tire_brand(){
        return strpos( $this->get( 'page_name' ), self::TIRE_BRAND_PREFIX ) === 0;
    }

    public function is_tire_type(){
        return strpos( $this->get( 'page_name' ), self::TIRE_TYPE_PREFIX ) === 0;
    }

    public function is_landing(){
        return strpos( $this->get( 'page_name' ), self::LANDING_PAGE_PREFIX ) === 0;
    }

	public function get_front_end_url_and_name(){

	    $name = $this->get( 'page_name' );

        if ( $this->is_rim_brand() ) {

            $rim_brand = $this->get_rim_brand();

            if ( $rim_brand ) {
                return [ $rim_brand->get_archive_url(), $rim_brand->get( 'name', '', true ) ];
            } else {
                return [ '', 'rim_brand_not_found' ];
            }

        } else if ( $this->is_tire_brand() ) {

            $tire_brand = $this->get_tire_brand();

            if ( $tire_brand ) {
                return [ $tire_brand->get_archive_url(), $tire_brand->get( 'name', '', true ) ];
            } else {
                return [ '', 'rim_brand_not_found' ];
            }

        } else if ( $this->is_tire_type() ) {

            $tire_type = $this->get_tire_type();

            if ( $tire_type ) {
                return [ $tire_type->get_archive_url(), $tire_type->get( 'name', '', true ) ];
            } else {
                return [ '', 'tire_type_not_found' ];
            }

        } else if ( $this->is_landing() ) {

            $slug = self::landing_page_slug_from_page_name( $name );

            // ie. get_url('tires'), 'tires'
            return [ get_url( gp_test_input( $slug ) ), gp_test_input( $slug ) ];

        } else {

            // ie. get_url( 'about_us' ), 'about_us'
            return [ get_url( gp_test_input( $name ) ), gp_test_input( $name )];
        }
    }

    public function omit_columns_for_admin_tables() {

        return [
            'page_date',
            'page_slug',
            'page_type',
            'page_template',
            'page_dynamic'
        ];
    }

	/**
	 * Add columns..
	 *
	 * @param $row
	 *
	 * @return array
	 */
	public function filter_row_for_admin_tables( $row ) {

		// see get_cell_data_for_admin_table
		if ( $row && is_object( $row ) ) {

			list( $link_url, $link_text ) = $this->get_front_end_url_and_name();

			$row->link = html_link_new_tab( $link_url, gp_test_input( $link_text ) );

			$row->meta_title = htmlspecialchars_but_allow_ampersand( get_page_meta( $this->get_id(), 'meta_title', true ) );
			$row->meta_desc = htmlspecialchars_but_allow_ampersand( get_page_meta( $this->get_id(), 'meta_desc', true ) );

			$keys = $this->get_admin_edit_field_set( true );

            $desc_keys = array_filter( [ 'landing_desc', 'archive_desc', 'lower_desc' ], function( $key ) use( $keys ){
                return in_array( $key, $keys );
            });

            // in order to allow our <hr> and <br> tags, @see get_cell_data_for_admin_table
			$row->other = implode( "<br><br>", array_map( function( $key ) use( $keys ){
                $value = htmlspecialchars_but_allow_ampersand( get_page_meta( $this->get_id(), $key, true ) );
                $max = 120;
                $end = strlen( $value ) > $max ? "..." : "";
                $value = substr( $value, 0, $max ) . $end;
                return "<strong>$key: </strong>$value";
            }, $desc_keys ));

			// not really needed
			// $row->editable_fields = implode( ", ", $keys );
		}

		return $row;
	}

	/**
	 * @param $key
	 *
	 * @return null
	 */
	public function get_cell_data_for_admin_table( $key, $value ) {

		switch( $key ) {
			case 'link':
				// return raw value to allow html (we put anchor tag in manually above)
				return $value;
			case 'page_dynamic':
				// simply fix an annoying zero thats showing up here
				return $value ? $value : "";
            case 'other':
                // returning raw value prevents sanitation from occuring.
                // we need to allow our own HTML added HTML.
                // the user entered value containing HTMl has been escaped.
                return $value;
		}

		// strictly null return is important
		return null;
	}

	/**
	 * @param $name
	 *
	 * @return mixed|string
	 */
	public static function tire_brand_slug_from_page_name( $name ) {
		return gp_test_input( str_replace( self::TIRE_BRAND_PREFIX, "", gp_test_input( $name ) ) );
	}

	/**
	 * @param $name
	 *
	 * @return mixed|string
	 */
	public static function rim_brand_slug_from_page_name( $name ) {
		return gp_test_input( str_replace( self::RIM_BRAND_PREFIX, "", gp_test_input( $name ) ) );
	}

	/**
	 * @param $name
	 *
	 * @return mixed|string
	 */
	public static function tire_type_slug_from_page_name( $name ) {
		return gp_test_input( str_replace( self::TIRE_TYPE_PREFIX, "", gp_test_input( $name ) ) );
	}

	/**
	 * ie. "_landing_tires" => "tires"
	 *
	 * @param $name
	 *
	 * @return string
	 */
	public static function landing_page_slug_from_page_name( $name ) {
		return gp_test_input( str_replace( self::LANDING_PAGE_PREFIX, "", gp_test_input( $name ) ) );
	}

	/**
	 * @param $slug
	 *
	 * @return string
	 */
	public static function page_name_from_tire_brand_slug( $slug ) {
		return self::TIRE_BRAND_PREFIX . gp_test_input( $slug );
	}

	/**
	 * @param $slug
	 *
	 * @return string
	 */
	public static function page_name_from_rim_brand_slug( $slug ) {
		return self::RIM_BRAND_PREFIX . gp_test_input( $slug );
	}

	/**
	 * @param $slug
	 *
	 * @return string
	 */
	public static function page_name_from_tire_type_slug( $slug ) {
		return self::TIRE_TYPE_PREFIX . gp_test_input( $slug );
	}

	/**
	 * ie. "tires" => "_landing_page_tires"
	 *
	 * @param $type
	 *
	 * @return string
	 */
	public static function page_name_via_landing_page_type( $type ) {
		return self::LANDING_PAGE_PREFIX . gp_test_input( $type );
	}

	/**
	 * @return DB_Tire_Brand|mixed|null
	 */
	public function get_tire_brand(){
	    if ( $this->is_tire_brand() ) {
            return DB_Tire_Brand::get_instance_via_slug( self::tire_brand_slug_from_page_name( $this->get_name() ) );
        }
	}

	/**
	 * @return DB_Rim_Brand|mixed|null
	 */
	public function get_rim_brand(){
	    if ( $this->is_rim_brand() ) {
            return DB_Rim_Brand::get_instance_via_slug( self::rim_brand_slug_from_page_name( $this->get_name() ) );
        }
	}

	/**
	 * @return DB_Rim_Brand|mixed|null
	 */
	public function get_tire_type(){
	    if ( $this->is_tire_type() ) {
            return DB_Tire_Model_Type::get_instance_via_slug( self::tire_type_slug_from_page_name( $this->get_name() ) );
        }
	}

	/**
	 * Override default parent action
	 */
	public function delete_self_if_has_singular_primary_key(){

		$db = get_database_instance(); $p = []; $q = '';

		$tbl = DB_page_meta;
		$q .= "SELECT meta_id  ";
		$q .= "FROM $tbl ";
		$q .= "WHERE page_id=:page_id ";
		$p[] = [ 'page_id', $this->get_id(), '%d' ];
		$q .= ";";

		$results = $db->get_results( $q, $p );

		// delete attached page meta
		array_map( function( $row ){
			$meta = DB_Page_Meta::create_instance_via_primary_key( gp_if_set( $row, 'meta_id' ) );

			if ( $meta ) {
				$meta->delete_self_if_has_singular_primary_key();
			}
		}, $results ? $results : [] );

		// delete self
		return parent::delete_self_if_has_singular_primary_key();
	}

	/**
	 * You can include this file which should probably print a form
	 * to let you edit the fields on the page.
	 *
	 * @return bool|string
	 */
	public function get_file_path_for_admin_edit_single_page_override() {

		$page_name = $this->get( 'page_name', false, true );

		if ( ! $page_name ) {
			return false;
		}

		$filename_to_check = make_safe_php_filename_from_user_input( $page_name, true );
		$base_path         = ADMIN_TEMPLATES . '/edit/edit-single-page-via-page-name';
		$path              = "$base_path/$filename_to_check.php";

		return $path && $filename_to_check && file_exists( $path ) ? $path : false;
	}

	/**
	 * @return bool|mixed
	 */
	public function get_id() {
		return (int) $this->get( 'page_id' );
	}

	/**
	 * @return bool|mixed
	 */
	public function get_name( $df = null, $clean = true ) {
		return $this->get( 'page_name', $df, $clean );
	}

	/**
	 * In case a row gets removed from the database it might be wise to default
	 * to an empty instance. This way your pages will still work but will probably
	 * just have no content for things you are trying to pull from the DB.
	 *
	 * To check if you have the empty instance you could probably just check
	 * if the instance returned has value from ->get_primary_key_value()
	 *
	 * @param      $page_name
	 * @param bool $default_to_empty_instance
	 *
	 * @return array|DB_Table|null|static
	 */
	public static function get_instance_via_name( $page_name, $default_to_empty_instance = false ) {

		// protect against passing in page_is() too early in case a page exists with no name.
		// names should be required.
		if ( ! $page_name ) {
			return null;
		}

		$db = get_database_instance();

		$rows = $db->get( static::$table, array(
			'page_name' => gp_test_input( $page_name ),
		) );

		$row = @$rows[ 0 ];

		$ret = $row ? self::create_instance_or_null( $row ) : null;

		if ( ! $ret && $default_to_empty_instance ) {
			return self::create_empty_instance_from_table( self::$table );
		}

		return $ret;
	}

	public static function auto_insert_dynamic_pages(){

        $insert_count = 0;

        $insert = function( $page_name ) use( &$insert_count ){
            $page_name = gp_test_input( $page_name );
            if ( ! DB_Page::get_instance_via_name( $page_name ) ) {
                $inserted = DB_Page::insert( [ 'page_name' => $page_name ] );
                $insert_count+= $inserted ? 1 : 0;
            }
        };

        $tire_brand_slugs = get_all_column_values_from_table( DB_tire_brands, 'tire_brand_slug' );
        $rim_brand_slugs = get_all_column_values_from_table( DB_rim_brands, 'rim_brand_slug' );
        $tire_type_slugs = [
            'winter',
            'summer',
            'all-season',
            'all-weather'
        ];

        array_map( function( $slug ) use ( $insert ){
            $insert( DB_Page::page_name_from_tire_brand_slug( $slug ) );
        }, gp_force_array( $tire_brand_slugs) );

        array_map( function( $slug ) use ( $insert ){
            $insert( DB_Page::page_name_from_rim_brand_slug( $slug ) );
        }, gp_force_array( $rim_brand_slugs) );

        array_map( function( $slug ) use ( $insert ){
            $insert( DB_Page::page_name_from_tire_type_slug( $slug ) );
        }, gp_force_array( $tire_type_slugs) );

        // include page names that should likely always exist here.
        // if needed, see Pages::get_all(), but currently we have no use
        // for literally all pages here...
        array_map( function( $name ) use ( $insert ){
            $insert( $name );
        }, [
            'home',
//		'rims',
//		'tires',
//		'packages',
            DB_Page::page_name_via_landing_page_type( 'tires' ),
            DB_Page::page_name_via_landing_page_type( 'rims' ),
            DB_Page::page_name_via_landing_page_type( 'packages' ),
        ] );

        return $insert_count;

    }
}
