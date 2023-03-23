<?php

/**
 * Only some pages have a corresponding row in the database. Other pages only
 * exist because we decide to use a common name for different functions related
 * to that page, most notable, get_url() for example.
 *
 * So basically, if you register a page here, it gives an easy way to
 * retrieve the URL. We may use this in the future to add other
 * configuration options to page entities.
 *
 * Class Pages
 */
Class Pages {

	/**
	 * For now, just put all data into a record set.
	 *
	 * If we need an OOP interface in the future, at least our
	 * register functions should work the same way.
	 *
	 * @var
	 */
	private static $data = [];

	/**
	 * @param $page_name
	 *
	 * @return bool|mixed
	 */
	public static function get( $name ) {
		return gp_if_set( self::$data, $name, null );
	}

	/**
	 * Have not tested this yet.
	 *
	 * @param bool $include_aliases
	 */
	//	public static function get_all( $include_aliases = false ) {
	//		return array_values( array_filter( self::$data, function( $d ) use( $include_aliases ) {
	//			if ( ! $include_aliases && gp_if_set( $d, 'is_alias' ) ) {
	//				return false;
	//			}
	//			return true;
	//		}) );
	//	}

	/**
	 * Returns the URL used to register the page which may be a relative URL, and
	 * in the case of the homepage, the URL might be an empty string.
	 *
	 * Returns strictly NULL if the page does not exist, and possibly
	 * empty string for home.
	 *
	 * @param $name
	 *
	 * @return bool|mixed
	 */
	public static function get_relative_url_or_null( $name ) {
		// in the future, we could register pages with an optional callbable "get_url" index
		// and then pass args into it. For example, getting the single tires page could have parameters
		// for brand and model.
		$page = self::get( $name );

		return $page ? $page[ 'url' ] : null;
	}

	/**
	 * @param $name
	 *
	 * @return bool
	 */
	public static function exists( $name ) {
		return $name && isset( self::$data[ $name ] ) && self::$data[ $name ];
	}

	/**
	 * @param       $name
	 * @param       $url - should be relative...
	 * @param array $args
	 */
	public static function register( $name, $url, $args = [] ) {
		$args[ 'url' ]       = isset( $args[ 'url' ] ) ? $args[ 'url' ] : $url;
		$args[ 'name' ]      = $name;
		self::$data[ $name ] = $args;
	}

	/**
	 * Since we may programmatically insert a DB page for each registered
	 * page, its important that you register your aliases with this. For example,
	 * for the sake of get_url(), passing 'rims' or 'wheels' will return the same,
	 * but we must only have a DB page for 'rims', and not for 'wheels'.
	 *
	 * @param       $name
	 * @param       $url
	 * @param array $args
	 */
	public static function register_alias( $name, $url, $args = [] ) {
		self::register( $name, $url, array_merge( $args, [
			'is_alias' => true
		] ) );
	}
}

/**
 *
 */
function cw_init_pages() {

	Pages::register( 'home', BASE_URL );
	Pages::register( 'blog', "blog" );
	Pages::register( 'admin', 'cw-admin?page=home' );

	// htaccess does stuff with php extensions here
	Pages::register( 'tire', "tires" );
	Pages::register( 'rim', "wheels" );

	Pages::register( 'tires', "tires" );
	Pages::register( 'packages', "packages" );
	Pages::register( 'rims', "wheels" );
	Pages::register_alias( 'wheels', "wheels" );
	Pages::register( 'account', "account" );
	Pages::register( 'order_details', "order-details" );
	Pages::register( 'edit_profile', "edit-profile" );
	Pages::register( 'logout', "logout" );
	Pages::register( 'login', "login" );
	Pages::register( 'reviews', "reviews" );
	Pages::register( 'checkout', "checkout" );
	Pages::register( 'faq', "faq" );
	Pages::register( 'return_policy', "return-policy" );
	Pages::register( 'privacy_policy', "privacy-policy" );
	Pages::register( 'shipping_policy', "shipping-policy" );
	Pages::register( 'fitment_policy', "fitment-policy" );
	Pages::register( 'warranty_policy', "warranty-policy" );
	Pages::register( 'what_we_do', "what-we-do" );
	Pages::register( 'gallery', "gallery" );
	Pages::register( 'cart', "cart" );
	Pages::register( 'contact', "contact" );
	Pages::register( 'reset_password', "reset-password" );
	Pages::register( 'forgot_password', "forgot-password" );

}

cw_init_pages();