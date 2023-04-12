<?php
/**
 * Register files in core/admin-templates,
 *
 * and core/admin-templates/tests (see bottom)
 */

if ( ! defined( 'BASE_DIR' ) ) {
    exit;
}

/**
 * Register each file in core/admin-templates here,
 *
 * If the file is not registered, the URL will not be accessible. So in addition
 * to creating a new file in core/admin-templates, you have to register it here.
 *
 * Admin pages are served through a URL like /cw-admin?page=something,
 * this lets our code know which file to serve for the 'something' page.
 */

Admin_Controller::register_page( 'home', 'home.php' );
Admin_Controller::register_page( 'edit', 'edit.php' );
Admin_Controller::register_page( 'import_rims', 'import-rims.php' );
Admin_Controller::register_page( 'import_tires', 'import-tires.php' );
Admin_Controller::register_page( 'tax_shipping', 'tax-shipping.php' );
Admin_Controller::register_page( 'insert_user', 'insert-user.php' );
Admin_Controller::register_page( 'orders', 'orders.php' );
Admin_Controller::register_page( 'orders_failed', 'orders-failed.php' );
Admin_Controller::register_page( 'order', 'order.php' );
Admin_Controller::register_page( 'content', 'content.php' );
Admin_Controller::register_page( 'content_home', 'content-home.php' );
Admin_Controller::register_page( 'gallery', 'gallery.php' );
Admin_Controller::register_page( 'faq', 'faq.php' );
Admin_Controller::register_page( 'test', 'test.php' );
Admin_Controller::register_page( 'image_upload', 'image-upload.php' );
Admin_Controller::register_page( 'images', 'images.php' );
Admin_Controller::register_page( 'rim_finishes', 'rim-finishes.php' );
Admin_Controller::register_page( 'sub_sizes', 'sub-sizes.php' );
Admin_Controller::register_page( 'clean_tables', 'clean-tables.php' );
Admin_Controller::register_page( 'transaction_report', 'transaction-report.php' );
Admin_Controller::register_page( 'content_contact', 'content-contact.php' );
Admin_Controller::register_page( 'content_privacy_policy', 'content-privacy-policy.php' );
Admin_Controller::register_page( 'content_refund_policy', 'content-refund-policy.php' );
Admin_Controller::register_page( 'columns', 'columns.php' );
Admin_Controller::register_page( 'stock_import', 'stock-import.php' );
Admin_Controller::register_page( 'registered_inventory_processes', 'registered-inventory-processes.php' );
Admin_Controller::register_page( 'inventory_overview', 'inventory-overview.php' );
Admin_Controller::register_page( 'inventory_files', 'inventory-files.php' );
Admin_Controller::register_page( 'rim_images', 'rim-images.php' );
Admin_Controller::register_page( 'tire_images', 'tire-images.php' );
Admin_Controller::register_page( 'dev_tools', 'dev-tools.php' );
Admin_Controller::register_page( 'content_management', 'content-management.php' );
Admin_Controller::register_page( 'supplier_products', 'supplier-products.php' );
Admin_Controller::register_page( 'product_sync', 'product-sync.php' );
Admin_Controller::register_page( 'pricing', 'pricing.php' );
Admin_Controller::register_page( 'google_shopping', 'google-shopping.php' );

Admin_Controller::register_page( 'coupons', 'coupons.php' );
Admin_Controller::register_page( 'insert_coupon', 'insert-coupon.php' );

/**
 * register the test pages here.
 *
 * Test pages usually just print debugging info, and have nothing
 * to do with unit tests for the most part. Test pages are meant for
 * developers.
 *
 * Test pages are PHP files that live in admin-templates/tests/.
 *
 * The admin-templates/test.php file will list and serve those files.
 *
 * Underscores get converted to dashes...
 *
 * To register the file admin-templates/tests/abc-123.php, add abc_123.
 */
gp_set_global( 'admin_test_pages', [
    'temp_files',
    'moneris_tests',
    'test_tables',
    'test_products',
    'time_memory',
    'sitemap',
    'supplier_inventory',
    // 'amazon_us_test',
    'amazon_reports',
    'amazon_accessories',
    'amazon_single_product',
    'amazon_inventory_tick',
    'rims_best_fit_with_stock',
    'queries',
    'ftp_pull',
    'part_numbers',
    'send_email',
    'moneris_payment',
    'kount_data_collector',
    'kount_inquiry',
    'kount_update',
    'payment_preauth',
    'min_prices',
    'php_vars',
    'php_info',
    'admin_cron',
    'sync_email',
] );
