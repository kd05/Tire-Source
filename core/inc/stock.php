<?php
/**
 * a few things related to tire and rim stock levels
 */
/**
 * When one supplier is wrongly affecting the inventory of another supplier, we may need
 * to adjust the code, run this function, and then re-run all inventory data.
 *
 * Be sure to delete existing hash keys as well so the cron jobs will properly insert data.
 *
 * @see also: Supplier_Inventory_Import::delete_all_existing_hash_keys();
 */
function reset_tire_inventory(){
	return reset_product_inventory( 'tires' );
}

/**
 * @see reset_tire_inventory();
 */
function reset_rim_inventory(){
	return reset_product_inventory( 'rims' );
}

/**
 * @param $type
 *
 * @return int
 */
function reset_product_inventory( $type ) {

	assert( $type === 'tires' || $type === 'rims' );
	$tbl = $type === 'tires' ? 'tires' : 'rims';

	$db = get_database_instance();

	$updated = $db->update( $tbl, array(
		DB_Product::get_column_stock_amt( APP_LOCALE_CANADA ) => 0,
		DB_Product::get_column_stock_sold( APP_LOCALE_CANADA ) => 0,
		DB_Product::get_column_stock_unlimited( APP_LOCALE_CANADA ) => 1,
		DB_Product::get_column_stock_update_id( APP_LOCALE_CANADA ) => NULL,
		DB_Product::get_column_stock_discontinued( APP_LOCALE_CANADA ) => 0,
		DB_Product::get_column_stock_amt( APP_LOCALE_US ) => 0,
		DB_Product::get_column_stock_sold( APP_LOCALE_US ) => 0,
		DB_Product::get_column_stock_unlimited( APP_LOCALE_US ) => 1,
		DB_Product::get_column_stock_update_id( APP_LOCALE_US ) => NULL,
		DB_Product::get_column_stock_discontinued( APP_LOCALE_US ) => 0,
	), array(
		1 => 1,
	), array(
		DB_Product::get_column_stock_amt( APP_LOCALE_CANADA ) => '%d',
		DB_Product::get_column_stock_sold( APP_LOCALE_CANADA ) => '%d',
		DB_Product::get_column_stock_unlimited( APP_LOCALE_CANADA ) => '%d',
		DB_Product::get_column_stock_update_id( APP_LOCALE_CANADA ) => '%d',
		DB_Product::get_column_stock_discontinued( APP_LOCALE_CANADA ) => '%d',
		DB_Product::get_column_stock_amt( APP_LOCALE_US ) => '%d',
		DB_Product::get_column_stock_sold( APP_LOCALE_US ) => '%d',
		DB_Product::get_column_stock_unlimited( APP_LOCALE_US ) => '%d',
		DB_Product::get_column_stock_update_id( APP_LOCALE_US ) => '%d',
		DB_Product::get_column_stock_discontinued( APP_LOCALE_US ) => '%d',
	));

	return $updated;
}

/**
 * For testing environment only
 *
 * @param $type
 *
 * @throws Exception
 */
function fake_stock_levels( $type, $locale ){

	assert( $type === 'tires' || $type === 'rims' );

	if ( IN_PRODUCTION ) {
		throw new Exception( 'No' );
	}

	$q = $type === 'tires' ? 'SELECT tire_id FROM tires' : 'SELECT rim_id FROM rims';
	$r = get_database_instance()->get_results( $q, array() );

	foreach ( $r as $r1=>$r2 ) {

		// want to have lots of out of stock products so we don't have to
		// spend time searching for them when we need to test things.
		// note that in the real database, stock - stock_sold should probably
		// never be zero, but for now it doesn't hurt at all to make sure
		// that at least the system works if this is the case.
		$stock_sold = rand( 0, 25 );

		// NULL means unlimited, otherwise.. pick a number
		$stock_amt = rand(0, 20);

		// note: cannot have unlimited stock and discontinued at the same time
		$unlimited = rand(0, 100) < 20 ? 1 : 0;
		$discontinued = $unlimited ? 0 : ( rand( 0, 100 ) > 80 ? 1 : 0 );

		$data = array(
			DB_Product::get_column_stock_sold( $locale ) => $stock_sold,
			DB_Product::get_column_stock_amt( $locale ) => $stock_amt,
			DB_Product::get_column_stock_unlimited( $locale ) => $unlimited,
			DB_Product::get_column_stock_update_id( $locale ) => 11119999,
			DB_Product::get_column_stock_discontinued( $locale ) => $discontinued,
		);

		$where = $type === 'tires' ? array(
			'tire_id' => $r2->tire_id
		) : array(
			'rim_id' => $r2->rim_id
		);

		$tbl = $type === 'tires' ? 'tires' : 'rims';
		$u = get_database_instance()->update( $tbl, $data, $where );

		echo '<pre>' . print_r( array_merge( $data, $where ), true ) . '</pre>';
		echo nl2br( "----------------------- \n" . get_string_for_log( $u ));
	}
}