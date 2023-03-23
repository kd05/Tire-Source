<?php

/**
 * this is hard to find a properly descriptive name.
 *
 * We put a string in some URL arguments (in the admin section)
 * so that we can tell SQL to do a not equal to comparison.
 *
 * For example, ?import_date__not_equal_to=2018-10-10
 */
define( 'GET_VAR_NOT_EQUAL_TO_APPEND', '__not_equal_to' );

/**
 * We can substitute this value into PHP variables representing an integer
 * value for the stock amount. This lets us hold a little bit more information in one
 * variable.
 *
 * This MUST be a string. -1 is not valid, because our stock - stock_sold amount
 * can also be equal to -1. NULL is tempting to use but I just don't want to due
 * to possible mis-understandings with other false-like values.
 *
 * This paradigm is NOT USED in the Database. Only in PHP.
 */
define( 'STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING', 'unlimited' );

/**
 * Categorize an integer quantity or STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING
 * into one of these. Once again, this is for PHP scripts (and not database storage)
 */
define( 'STOCK_LEVEL_LOW_STOCK', 'low_stock' );

/**
 * This should mean "in stock and also not low stock" which is definitely confusing.
 * This applies to every time we use low stock, in stock, out of stock. For example,
 * if you have 5 products is it low stock, in stock, or both? In the way that we
 * will attempt to use it, it should only ever be in 1 category.
 */
define( 'STOCK_LEVEL_IN_STOCK', 'in_stock' );

/**
 * Note that when a product, or a set of products meet this description, the
 * quantity required is dynamic. It usually means less than 1, 2, or 4 products.
 */
define( 'STOCK_LEVEL_NO_STOCK', 'out_of_stock' );

/**
 * This might not be in use, and was added at a later time than the other constants.
 *
 * Not technically zero stock, but not enough stock for 1 set (ie. 1-3)
 */
define( 'STOCK_LEVEL_SEMI_OUT_OF_STOCK', 'semi_out_of_stock' );


/**
 * The string value of these constants shows up on the cart and in emails etc.
 */
define( 'MOUNT_BALANCE_PART_NUMBER_27_MINUS', 'mount_balance_1' );
define( 'MOUNT_BALANCE_PART_NUMBER_27_30', 'mount_balance_2' );
define( 'MOUNT_BALANCE_PART_NUMBER_30_33', 'mount_balance_3' );
define( 'MOUNT_BALANCE_PART_NUMBER_33_PLUS', 'mount_balance_4' );