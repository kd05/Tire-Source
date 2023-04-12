<?php

// early
require CORE_DIR . '/other/user-authentication.php';

require CORE_DIR . '/cron/cron-helper.php';
require CORE_DIR . '/inc/wp-blog.php';
require CORE_DIR . '/inc/helpers.php';
require CORE_DIR . '/inc/per-page.php';
require CORE_DIR . '/inc/helpers2.php';
require CORE_DIR . '/inc/functions.php';
require CORE_DIR . '/inc/urls.php';
require CORE_DIR . '/inc/users.php';
require CORE_DIR . '/inc/stock.php';
require CORE_DIR . '/inc/sub-sizes.php';
require CORE_DIR . '/inc/supplier-ftp.php';
require CORE_DIR . '/inc/admin.php';
require CORE_DIR . '/inc/database.php';
require CORE_DIR . '/inc/lightboxes.php';
require CORE_DIR . '/inc/nonce.php';
require CORE_DIR . '/inc/account.php';
require CORE_DIR . '/inc/orders.php';
require CORE_DIR . '/inc/products.php';
require CORE_DIR . '/inc/callbacks.php';
require CORE_DIR . '/inc/compare.php';
require CORE_DIR . '/inc/validation.php';
require CORE_DIR . '/inc/forms.php';
require CORE_DIR . '/inc/checkout.php';
require CORE_DIR . '/inc/locale.php';
require CORE_DIR . '/inc/reviews.php';
require CORE_DIR . '/inc/shortcodes.php';
require CORE_DIR . '/inc/supplier-emails.php';
require CORE_DIR . '/inc/emails.php';
require CORE_DIR . '/inc/money.php';
require CORE_DIR . '/inc/filters.php';
require CORE_DIR . '/inc/tire-query-functions.php';
require CORE_DIR . '/inc/rim-query-functions.php';
require CORE_DIR . '/inc/package-query-functions.php';
require CORE_DIR . '/inc/cache.php';
require CORE_DIR . '/inc/spec-tables.php';
require CORE_DIR . '/inc/product-images.php';
require CORE_DIR . '/inc/coupons.php';

// Admin stuff / whatever ...
require CORE_DIR . '/classes/admin-sidebar.php';
require CORE_DIR . '/classes/tests.php';
require CORE_DIR . '/classes/vehicle-queries-php.php';
require CORE_DIR . '/classes/vehicle-query-database-row.php';

// inventory things
require CORE_DIR . '/classes/inventory/_includes.php';

// ftp/csv used for suppliers
require CORE_DIR . '/classes/ftp-get-csv.php';
require CORE_DIR . '/classes/csv-to-array.php';

// Front-End Classes
require CORE_DIR . '/classes/add-to-cart-handler.php';
require CORE_DIR . '/ajax/_ajax.php';
require CORE_DIR . '/classes/background-image.php';
require CORE_DIR . '/classes/components.php';
require CORE_DIR . '/classes/globals.php';

// cart and vehicle classes
require CORE_DIR . '/classes/export-as-array.php';
require CORE_DIR . '/classes/cart-checkout.php';
require CORE_DIR . '/classes/cart-and-vehicles/cart.php';
require CORE_DIR . '/classes/cart-and-vehicles/cart-receipt.php';
require CORE_DIR . '/classes/cart-and-vehicles/cart-item.php';
require CORE_DIR . '/classes/cart-and-vehicles/cart-package.php';
require CORE_DIR . '/classes/cart-and-vehicles/cart-vehicle.php';
require CORE_DIR . '/classes/cart-and-vehicles/vehicle.php';
require CORE_DIR . '/classes/cart-and-vehicles/fitment-no-wheels.php';
require CORE_DIR . '/classes/cart-and-vehicles/fitment-singular.php';
require CORE_DIR . '/classes/cart-and-vehicles/fitment-plural.php';
require CORE_DIR . '/classes/cart-and-vehicles/wheel-set.php';
require CORE_DIR . '/classes/cart-and-vehicles/wheel-set-parent.php';
require CORE_DIR . '/classes/cart-and-vehicles/wheel-set-sub.php';
require CORE_DIR . '/classes/cart-and-vehicles/wheel-pair.php';

// general..
require CORE_DIR . '/classes/static-array-data.php';
require CORE_DIR . '/classes/logger.php';
require CORE_DIR . '/classes/product-filters-html.php';
require CORE_DIR . '/classes/database.php';
require CORE_DIR . '/classes/html-email.php';
require CORE_DIR . '/classes/debug.php';
require CORE_DIR . '/classes/sitemap.php';
require CORE_DIR . '/classes/footer.php';
require CORE_DIR . '/classes/checkout-submit.php';
require CORE_DIR . '/classes/gp-read-csv.php';
require CORE_DIR . '/classes/header.php';
require CORE_DIR . '/classes/icons.php';
require CORE_DIR . '/classes/sidebar-container.php';
require CORE_DIR . '/classes/user-exception.php';
require CORE_DIR . '/classes/stock-level-html.php';
require CORE_DIR . '/classes/coupon-exception.php';

// import
require CORE_DIR . '/classes/import/product-import.php';
require CORE_DIR . '/classes/import/product-import-rims.php';
require CORE_DIR . '/classes/import/product-import-tires.php';

// models
require CORE_DIR . '/models/db-table.php';
require CORE_DIR . '/models/db-virtual-table.php';
require CORE_DIR . '/models/db-tire-model-or-rim-finish-trait.php';
require CORE_DIR . '/models/db-amazon-process.php';
require CORE_DIR . '/models/db-cache.php';
require CORE_DIR . '/models/db-option.php';
require CORE_DIR . '/models/db-sub-size.php';
require CORE_DIR . '/models/db-order.php';
require CORE_DIR . '/models/db-order-item.php';
require CORE_DIR . '/models/db-order-vehicle.php';
require CORE_DIR . '/models/db-order-email.php';
require CORE_DIR . '/models/db-product.php';
require CORE_DIR . '/models/db-product-brand.php';
require CORE_DIR . '/models/db-product-model.php';
require CORE_DIR . '/models/db-regions.php';
require CORE_DIR . '/models/db-review.php';
require CORE_DIR . '/models/db-rim.php';
require CORE_DIR . '/models/db-rim-brand.php';
require CORE_DIR . '/models/db-rim-finish.php';
require CORE_DIR . '/models/db-rim-model.php';
require CORE_DIR . '/models/db-shipping-rate.php';
require CORE_DIR . '/models/db-stock-update.php';
require CORE_DIR . '/models/db-tax-rate.php';
require CORE_DIR . '/models/db-tire.php';
require CORE_DIR . '/models/db-tire-brand.php';
require CORE_DIR . '/models/db-tire-model.php';
require CORE_DIR . '/models/db-tire-model-category.php';
require CORE_DIR . '/models/db-tire-model-class.php';
require CORE_DIR . '/models/db-tire-model-run-flat.php';
require CORE_DIR . '/models/db-tire-model-type.php';
require CORE_DIR . '/models/db-transaction.php';
require CORE_DIR . '/models/db-supplier.php';
require CORE_DIR . '/models/db-user.php';
require CORE_DIR . '/models/db-page.php';
require CORE_DIR . '/models/db-page-meta.php';

require CORE_DIR . '/models/db-coupon.php';

require CORE_DIR . '/classes/pages.php';
require CORE_DIR . '/classes/field-set.php';
require CORE_DIR . '/classes/field-set-item.php';
require CORE_DIR . '/classes/field-set-item-presets.php';

// pages
// require_once as needed perhaps
//require CORE_DIR . '/classes/html/landing-page-product.php';
//require CORE_DIR . '/classes/html/landing-page-tires.php';
//require CORE_DIR . '/classes/html/landing-page-rims.php';

require CORE_DIR . '/classes/html/vehicle-lookup-form.php';
require CORE_DIR . '/classes/html/sidebar-accordion-item.php';
require CORE_DIR . '/classes/html/page-products.php';
require CORE_DIR . '/classes/html/page-products-filters-methods.php';
require CORE_DIR . '/classes/html/page-tires.php';
require CORE_DIR . '/classes/html/page-rims.php';
require CORE_DIR . '/classes/html/page-cart.php';
require CORE_DIR . '/classes/html/page-packages.php';

// Single Tire/Rim Pages
require CORE_DIR . '/classes/html/single-product-page.php';
require CORE_DIR . '/classes/html/single-tire-page.php';
require CORE_DIR . '/classes/html/single-rim-page.php';

// Reviews
require CORE_DIR . '/classes/html/product-review-helper.php';
require CORE_DIR . '/classes/html/product-review-page.php';

// Tables
require CORE_DIR . '/classes/html/single-products-page-table.php';
require CORE_DIR . '/classes/html/single-tires-page-table.php';
require CORE_DIR . '/classes/html/single-rims-page-table.php';

// Flex Items
require CORE_DIR . '/classes/html/flex-items.php';
require CORE_DIR . '/classes/html/product-loop-flex.php';
require CORE_DIR . '/classes/html/product-loop-flex-rims.php';
require CORE_DIR . '/classes/html/product-loop-flex-tires.php';
require CORE_DIR . '/classes/html/product-loop-flex-packages.php';

// Query
require CORE_DIR . '/classes/query/product-query-general.php';
require CORE_DIR . '/classes/query/product-query-filter-methods.php';

require CORE_DIR . '/classes/query/rims-query-general.php'; // general before grouped
require CORE_DIR . '/classes/query/rims-query-grouped.php';
require CORE_DIR . '/classes/query/rims-query-fitment-sizes.php';

require CORE_DIR . '/classes/query/tire-query-general.php'; // general before grouped
require CORE_DIR . '/classes/query/tire-query-grouped.php';
require CORE_DIR . '/classes/query/tire-query-fitment-sizes.php';

require CORE_DIR . '/classes/query/staggered-package-multi-size-query.php';

// Query Components
require CORE_DIR . '/classes/query/components/component-builder.php';
require CORE_DIR . '/classes/query/components/world.php';
require CORE_DIR . '/classes/query/components/query-components.php';
require CORE_DIR . '/classes/query/components/query-components-tires.php';
require CORE_DIR . '/classes/query/components/query-components-tire-models.php';
require CORE_DIR . '/classes/query/components/query-components-tire-brands.php';
require CORE_DIR . '/classes/query/components/query-components-rims.php';
require CORE_DIR . '/classes/query/components/query-components-rim-models.php';
require CORE_DIR . '/classes/query/components/query-components-rim-brands.php';

require CORE_DIR . '/classes/sql-builder.php';
require CORE_DIR . '/classes/wheel-size-api.php';

// Admin Includes, but we may need them in non-admin sections sometimes
require_once CORE_DIR . '/classes/admin-controller.php';
require_once CORE_DIR . '/classes/admin-archive-page.php';
require_once CORE_DIR . '/classes/admin-functions.php';

// Moneris
require CORE_DIR . '/classes/moneris/app-moneris.php';
require CORE_DIR . '/classes/moneris/app-moneris-config.php';
require CORE_DIR . '/classes/moneris/app-moneris-pre-auth-capture.php';
require CORE_DIR . '/other/craigpaul-moneris-extend.php';

// require CORE_DIR . '/classes/moneris/app-moneris-purchase.php';

// Composer autoload
require COMPOSER_DIR . '/autoload.php';

// Moneris Unified Api PHP
require MONERIS_DIR . '/eCommerce-Unified-API-PHP-master/mpgClasses.php';

// amazon MWS
require CORE_DIR . '/classes/amazon/amazon-mws.php';
require CORE_DIR . '/classes/amazon/mws-inventory-intervals.php';
require CORE_DIR . '/classes/amazon/mws-inventory-tick.php';
require CORE_DIR . '/classes/amazon/mws-submit-inventory-feed.php';
require CORE_DIR . '/inc/amazon.php';

require CORE_DIR . '/libs/moneris-kount.php';
require CORE_DIR . '/libs/kount-service.php';
require CORE_DIR . '/classes/app-kount.php';

require CORE_DIR . '/classes/time-mem-tracker.php';
require CORE_DIR . '/classes/product-images/product-images-tires.php';
require CORE_DIR . '/classes/product-images/product-images-rims.php';
require CORE_DIR . '/classes/product-images/product-images.php';
require CORE_DIR . '/classes/product-images/rim-images-migration.php';
require CORE_DIR . '/classes/product-images/product-images-admin-ui.php';

require CORE_DIR . '/router.php';

require CORE_DIR . '/classes/product-sync/_includes.php';
require CORE_DIR . '/models/db-price-rule.php';
require CORE_DIR . '/models/db-sync-request.php';
require CORE_DIR . '/models/db-sync-update.php';
require CORE_DIR . '/models/db-price-update.php';

require CORE_DIR . '/inc/google-shopping.php';

/**
 * drop_and_create_table() may pay attention to this
 */
define( 'CREATING_TABLES', false );

// example to try to re-initialize entire database
//$tbls = DB_Table::get_table_class_map();
//foreach ( $tbls as $tbl=>$cls ) {
//    if ( $tbl !== 'users' ) {
//        $d = drop_and_create_table( $tbl, true );
//        var_dump( $tbl );
//        var_dump( $d );
//    }
//}


