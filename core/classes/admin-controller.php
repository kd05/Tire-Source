<?php

/**
 * Do admin user authentication, then include a file based on ?page=...
 *
 * REGISTER YOUR ADMIN TEMPLATE IN __CONSTRUCT()
 *
 * Class Admin_Controller
 */
Class Admin_Controller{

	/**
	 * register pages to files here..
	 * @see self::register_page()
	 *
	 * @var array
	 */
	public static $map = array();

	/**
	 * Admin_Page constructor.
	 */
	public function __construct(){}

	/**
	 * The filename should be found in ADMIN_TEMPLATES, or..
	 * /core/admin-templates/
	 *
	 * @param $key
	 * @param $filename
	 */
	public static function register_page( $key, $filename ) {
		self::$map[$key] = $filename;
	}

    /**
     * Helper for rendering pages within /admin-templates.
     *
     * Note: most pages aren't using this and do what this does manually
     * instead.
     *
     * @param $cb
     * @param array $cb_args
     */
    /**
     * @param $cb
     * @param array $cb_args
     * @param null $page_title
     */
    public static function with_header_footer_and_sidebar( $cb, array $cb_args = [], $page_title = null ) {

        if ( $page_title !== null ) {
            page_title_is( $page_title );
        }

        // allow the callback to modify globals used in header.
        // this lets you use things like page_title_is()
        ob_start();
        call_user_func_array( $cb, $cb_args );
        $output = ob_get_clean();

        cw_get_header();
        Admin_Sidebar::html_before();
        echo $output;
        Admin_Sidebar::html_after();
        cw_get_footer();
    }

    /**
     * We could check self::$map to ensure the page was registered,
     * but all we're doing is appending $_GET['page'] to the admin URL.
     *
     * @param $page
     * @return string
     */
	public static function get_url( $page ) {

		$keys = array_keys( self::$map );

		// I guess default to the admin home page
		$page = in_array( $page, $keys ) ? $page : 'home';

		$ret = cw_add_query_arg( [ 'page' => $page ], ADMIN_URL );
		return $ret;
	}

	/**
	 * Make sure you register your template file and put it in the
	 * correct directory, in order for it to be accessible.
	 */
	public function render(){

	    if ( ! isset( $_GET['page'] ) ) {
	        $_GET['page'] = 'home';
        }

		if ( ! cw_is_admin_logged_in() ) {
			show_404();
			exit;
		}


		$filename = gp_if_set( self::$map, $_GET['page'] );
		$file = ADMIN_TEMPLATES . '/' . $filename;

		if ( ! $filename || ! file_exists( $file ) ) {
			show_404();
			exit;
		}

		include( $file );
	}
}

