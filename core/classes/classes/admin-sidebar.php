<?php

/**
 * NOTE: register pages in Admin_Controller when adding them to the sidebar.
 *
 * see also @var Admin_Controller
 */

/**
 * Class Admin_Sidebar
 */
Class Admin_Sidebar{

	/**
	 * Admin_Sidebar constructor.
	 */
	public function __construct(){}

	/**
	 *
	 */
	public static function html_sidebar(){

	    $urls = array();

		$urls[] = array(
			'title' => 'Home',
			'url' => get_admin_page_url( 'home' ),
		);

        $urls[] = array(
            'title' => 'Users',
            'url' => get_admin_archive_link( 'users' ),
        );

        $urls[] = array(
            'title' => 'Orders',
            'url' => get_admin_page_url( 'orders' ),
        );

        $urls[] = array(
            'title' => 'Reviews',
            'url' => get_admin_archive_link( 'reviews' ),
        );

        $urls[] = array(
            'title' => 'Transaction Report',
            'url' => get_admin_page_url( 'transaction_report' ),
        );

		$urls [] = false;

        $urls[] = array(
            'title' => 'Manage Content',
            'url' => get_admin_page_url( 'content_management' ),
        );

        $urls[] = array(
            'title' => 'Edit Pages',
            'url' => get_admin_archive_link( DB_pages ),
        );

        $urls[] = array(
            'title' => 'Tables',
            'url' => get_admin_page_url( 'edit' ),
        );

        $urls[] = false;

        $urls[] = array(
            'title' => 'Product Sync',
            'url' => get_admin_page_url( 'product_sync' ),
        );

        $urls[] = array(
            'title' => 'Price Rules',
            'url' => get_admin_page_url( 'pricing' ),
        );

        $urls[] = array(
            'title' => 'Google Shopping',
            'url' => get_admin_page_url( 'google_shopping' ),
        );

        $urls[] = false;

        $urls[] = array(
			'title' => 'Import Tires',
			'url' => get_admin_page_url( 'import_tires' ),
		);

		$urls[] = array(
			'title' => 'Import Rims',
			'url' => get_admin_page_url( 'import_rims' ),
		);

        $urls[] = array(
            'title' => 'Tire Images',
            'url' => cw_add_query_arg( [
                'show' => 'not_ok'
            ], get_admin_page_url( 'tire_images' ) ),
        );

        $urls[] = array(
            'title' => 'Rim Images',
            'url' => cw_add_query_arg( [
                'show' => 'not_ok'
            ], get_admin_page_url( 'rim_images' ) ),
        );

		$urls[] = array(
			'title' => 'Other Images',
			'url' => get_admin_page_url( 'image_upload' ),
		);

        $urls[] = array(
            'title' => 'Substitution Sizes',
            'url' => get_admin_page_url( 'sub_sizes' ),
        );

        $urls[] = array(
            'title' => 'Tax/Shipping',
            'url' => get_admin_page_url( 'tax_shipping' ),
        );

		$urls[] = false;

        $urls[] = array(
            'title' => 'Inventory Overview',
            'url' => get_admin_page_url( 'inventory_overview' ),
        );

        $urls[] = array(
            'title' => 'Stock Import',
            'url' => get_admin_page_url( 'stock_import' ),
        );

        $urls[] = array(
            'title' => 'Vehicle Data Tool',
            'url' => get_url( 'trims' ),
        );

        $urls[] = array(
            'title' => 'Dev Tools',
            'url' => get_admin_page_url( 'dev_tools' ),
        );

        $urls[] = array(
            'title' => 'Tests',
            'url' => get_admin_page_url( 'test' ),
        );

		?>
		<div class="sb-inner">
			<ul id="admin-nav">
                <?php
                if ( $urls ) {
                    foreach ( $urls as $k=>$v ){
                        if ( ! $v ) {
                            echo '<li style="display: block; height: 8px"></li>';
                        }
                        $title = gp_if_set( $v, 'title' );
                        $url = gp_if_set( $v, 'url' );
                        $target = gp_if_set( $v, 'target' );
                        echo '<li><a target="' . $target . '" href="' . $url . '">' . $title . '</a></li>';
                    }
                }
                ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * to be used in template file
	 */
	public static function page_title( $title = '' ){
		if ( ! $title ) return;
		?>
		<div class="admin-page-title">
			<h1><?php echo $title; ?></h1>
		</div>
		<?php
	}

	/**
	 * To be used in template file
	 */
	public static function html_before( $add_class = '' ){
		$cls = [ 'admin-sidebar-page page-wrap' ];
		$cls[] = $add_class;
	    ?>
		<div class="<?php echo gp_parse_css_classes( $cls ); ?>">
		<div class="sb-left">
			<?php self::html_sidebar(); ?>
		</div>
		<div class="sb-right admin-content">
		<?php
	}

	/**
	 * To be used in template file
	 */
	public static function html_after(){
		?>
		</div> <!-- sb-right -->
		</div> <!-- admin-sidebar-page -->
		<?php

	}
}