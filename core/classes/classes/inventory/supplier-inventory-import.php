<?php

/**
 * Updates stock levels of tires/rims from an CSV file.
 *
 * @see Supplier_Inventory_Supplier
 *
 * Class Supplier_Inventory_Import
 */
Class Supplier_Inventory_Import {

	/**
	 * @var Supplier_Inventory_Supplier
	 */
	public $supplier;

	/**
	 * Track events/debug but with time info.
	 *
	 * @var
	 */
	public $profile;

	/**
	 * Debug stuff... track events that happen.
	 *
	 * I think.. this one gets stored in the database, whereas $this->profile
	 * only is shown if we decide to print it during testing.
	 *
	 * @var
	 */
	public $operations = array();

	/**
	 * @var array
	 */
	public $errors = array();

	/**
	 * Track errors or w/e indexed by the row number.
	 *
	 * @var
	 */
	public $row_messages;

	/**
	 * Object representing a single row in 'stock_updates' table
	 *
	 * @var DB_Stock_Update
	 */
	public $db_stock_update;

	/**
	 * numbers of rows in the array.
	 *
	 * This would be better named $count_array, but the database table already has a column
	 * for count_csv.
	 *
	 * @var
	 */
	public $count_csv;

	/**
	 * numbers of rows that we looped through.
	 *
	 * I think, but I'm not sure.. that this will be equal to $this->count_csv if we didn't encounter any errors.
	 *
	 * But I'm not confident on relying on these 2 values being equal as an indicator of success of the overall
	 * operation.
	 *
	 * @var
	 */
	public $count_processed;

	/**
	 * number of products found and updated
	 *
	 * @var
	 */
	public $count_updated;

	/**
	 * @var
	 */
	public $count_not_found;

	/**
	 * The sum of all product quantities. We can use this to get an average.
	 * If the average is super low, like.. say 1-2, then perhaps something went wrong..
	 * ie. we may have grabbed the wrong column which had a lot of empty values or something.
	 *
	 * @var
	 */
	public $count_total_stock;

	/**
	 * I forget if we will ever mark products as unlimited in stock. If we do,
	 * we will increment this.
	 *
	 * @var int
	 */
	public $count_stock_unlimited;

	/**
	 * @var
	 */
	public $count_not_sold;

	/**
	 * We'll want to track this in case more or less all products are updated with zero stock.
	 *
	 * In this case, probably something went wrong.
	 *
	 * @var
	 */
	public $count_no_stock;

	/**
	 * @var
	 */
	public $count_in_stock;

	/**
	 * Ie. $this->suppliers_affected['supplier_slug'] = 60;
	 *
	 * This is updated via $this->register_if_not_in_array()
	 *
	 * @var array
	 */
	public $suppliers_affected = array();

	/**
	 * Ie. $this->brands_affected['brand_slug'] = {number_of_products_affected_with_brand_slug};
	 *
	 * This is updated via $this->register_if_not_in_array()
	 *
	 * @var array
	 */
	public $brands_affected = array();

	/**
	 * Ie. $this->types_affected['tires'] = 400;
	 *
	 * This is updated via $this->register_if_not_in_array()
	 *
	 * @var array
	 */
	public $types_affected = array();

	/**
	 * Possibly hash key just something that lets us know which supplier, locale, etc.
	 *
	 * @var mixed
	 */
	public $description;

    /**
     * Supplier_Inventory_Import constructor.
     * @param Supplier_Inventory_Supplier $supplier
     * @throws Exception
     */
	public function __construct( Supplier_Inventory_Supplier $supplier ) {

		$this->supplier     = $supplier;
		$this->operations[] = 'construct';

		// forget why, but we need both of these i think.
		start_time_tracking( 'Supplier_Inventory_Import' );
		start_time_tracking( 'stock_profile' );

		// ** After: start_time_tracking( 'stock_profile' ); **
		$this->profile( 'start' );

		assert( app_is_locale_valid( $this->supplier->locale ), 'invalid supplier inventory import locale.' );
		assert( $this->supplier->type !== 'universal', 'type universal is not allowed.' );

		// let empty arrays not throw assertion errors. The count_processed will be zero indicating a "successful" operation without any data.
		if ( $this->supplier->array ) {
			assert( isset( $this->supplier->array[ 0 ][ 'part_number' ] ), 'required column part number not found in first row of the array.' );
			assert( isset( $this->supplier->array[ 0 ][ 'stock' ] ), 'required column stock not found in first row of the array.' );
		}

		// not really the place to be asserting this, but idk.
		assert( is_array( $this->supplier->allowed_suppliers ) );

		// this happens for AMAZON_ONLY classes. We can trigger this the
        // import is run manually via the admin, (or accidentally somehow in a cron job).
		if ( empty( $this->supplier->allowed_suppliers ) ) {
		    // don't return yet.
		    $this->errors[] = "Allowed suppliers is empty. Nothing to update.";
        }

		if ( $this->supplier->ftp->errors ) {
			foreach ( $this->supplier->ftp->errors as $ftp_error ) {
				$this->errors[] = '[ftp] ' . $ftp_error;
			}
		}

		// pick up errors from the CSV object and store them in the DB_Stock_Update.
		// this might give us insight as to why an import might have been run without any rows processed.
		if ( $this->supplier->csv && $this->supplier->csv->errors ) {
			foreach ( $this->supplier->csv->errors as $csv_error ) {
				$this->errors[] = '[csv] ' . $csv_error;
			}
		}

		// multiple suppliers are fine as long as we have a single type (tires or rims) and a single locale AND...
		// most importantly.. that every supplier in the database is only targeted by 1 file.
		// if for example, we have 2 files, both of which update the same 2 suppliers, then this will fail
		// if using $this->supplier->mark_products_not_in_array_as_not_sold set to true.
		// assert( count( $this->supplier->allowed_suppliers ) === 1, 'for now, we can only allow 1 supplier in supplier inventory import.' );

		$stock_filename    = $this->supplier->ftp ? gp_test_input( $this->supplier->ftp->remote_file_name ) : '';
		$stock_description = $this->supplier->get_hash_key() ? $this->supplier->get_hash_key() : '';

		// insert database row to track this operation - do this very early, so that $this->after_run() does
		// not fail, and also so that we always keep track of errors.
		$update_id = DB_Stock_Update::insert( array(
			'stock_type' => $this->supplier->type,
			'stock_suppliers' => implode( ', ', $this->supplier->allowed_suppliers ),
			'stock_locale' => $this->supplier->locale,
			'stock_filename' => $stock_filename,
			'stock_description' => $stock_description,
			'stock_date' => date( get_database_date_format(), gp_time() ),
		) );

		// its a bit tricky, but by adding an error we ensure that $this->run_main() will not be called but
		// when we do $this->run_cleanup(), this error will be logged. I want to avoid throwing an exception
		// on this one because i dont want to prevent other inventory scripts from completing.
		if ( DOING_CRON && ! $supplier->process_in_cron_job ) {
			$this->errors[] = 'Process is marked to not run on a cron job. Aborting the script.';
		}

		$this->db_stock_update = DB_Stock_Update::create_instance_via_primary_key( $update_id );
		assert( $update_id && $this->db_stock_update );
		$this->operations[] = 'create_db_row';

		$this->count_csv                                   = count( $this->supplier->array );
		$this->count_processed                             = 0;
		$this->count_updated                               = 0;
		$this->count_total_stock                           = 0;
		$this->count_stock_unlimited                       = 0;
		$this->count_not_found                             = 0;
		$this->count_not_allowed_due_to_supplier           = 0;
		$this->count_not_allowed_due_to_not_sold_in_locale = 0;
		$this->count_in_stock                              = 0;
		$this->count_no_stock                              = 0;
		$this->count_not_sold                              = 0;

		// things dont work if these dont start as exactly empty arrays
		$this->suppliers_affected = array();
		$this->brands_affected    = array();
		$this->types_affected     = array();
	}

	/**
	 * @param $msg
	 */
	public function profile( $event ) {
		$this->profile[ $event ] = [
			'time' => end_time_tracking( 'stock_profile' ),
			'mem' => memory_get_peak_usage(),
		];
	}

	/**
	 * process the array
	 */
	public function run() {

		if ( ! $this->errors ) {
			$this->run_main();
		}

		$this->run_cleanup();
	}

	/**
	 * @param $row_number
	 * @param $msg
	 */
	public function add_row_message( $row_number, $msg ) {
		$row_number                          = $row_number === null ? 'unknown' : $row_number;
		$this->row_messages[ $row_number ][] = $msg;
	}

	/**
	 * Process the CSV
	 */
	private function run_main() {

		$this->operations[] = 'run_main';
		$this->profile( 'run_main' );

		if ( empty( $this->supplier->allowed_suppliers ) ) {
		    return;
        }

		$db = get_database_instance();

		if ( $this->supplier->type === 'tires' ) {
		    $ex_products = self::get_ex_tires();
            $type = 'tires';
            $db_table = 'tires';
        } else if ( $this->supplier->type === 'rims' ){
            $ex_products = self::get_ex_rims();
            $type = 'rims';
            $db_table = 'rims';
        } else{
		    throw_dev_error( "only tires/rims types are valid right now." );
		    exit;
        }

		// handling empty files first.
		if ( ! $this->supplier->array ) {

            // probably false for all suppliers.
            if ( $this->supplier->if_file_empty_mark_products_not_sold ){
                $this->operations[] = 'empty_file--mark_not_sold';
                $this->set_products_not_in_array_to_not_sold();
            } else {
                $this->operations[] = 'empty_file--do_nothing';
            }

            // a necessary return.
		    return;
        }

        $this->profile( 'start_transaction' );

		$db->execute("START TRANSACTION;");

		// not sure if this is necessary, but in case of error in loop,
        // maybe this helps tables to not get locked for a long time.
		register_shutdown_function( function() use( $db ){
            $db->execute("COMMIT;");
        });

        $commit_every = 1000;

        // the main loop to update inventory levels.
        // note that within the loop, we'll update the "stock_update_id_" of the product.
        // this is an important side effect, because after the loop, we'll then check all products
        // of the same supplier that have an old stock update ID (and mark them discontinued).
        // As a result of this, when we "continue" based on some conditions below, those products
        // will be marked discontinued.
        foreach ( array_values( $this->supplier->array ) as $row_number => $row ) {

            // in theory, this won't lock the tires/rims tables for too long ? idk.
            if ( $row_number % $commit_every === 0 ) {
                $db->execute("COMMIT;");
                $db->execute("START TRANSACTION;");
                // $this->profile( 'commit_' . $row_number );
            }

            $this->count_processed ++;
            $part_number = $row[ 'part_number' ];
            $part_number = trim( $part_number );

            $product = @$ex_products[$part_number];

            if ( ! $product ) {
                $this->count_not_found ++;
                continue;
            }

            // ensure the supplier of the product matches the allowed suppliers
            if ( ! in_array( $product['supplier'], $this->supplier->allowed_suppliers ) ) {
                $this->count_not_allowed_due_to_supplier++;
                continue;
            }

            // it is **IMPORTANT** that we do not do "this" (therefore, leave the condition below commented out).
            // some products are sold on amazon, but not the website. Therefore, we must track inventory on the
            // website even for products not sold, so that their inventory levels eventually get passed to amazon.
//				if ( (int) $product->get( $product::get_column_sold_in( $this->supplier->locale ) ) !== 1 ) {
//					$this->count_not_allowed_due_to_not_sold_in_locale++;
//					continue;
//				}

            // don't cast to int yet
            $stock = trim( $row[ 'stock' ] );

            if ( $stock == STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING ) {
                $this->count_stock_unlimited ++;
                $stock           = 0;
                $stock_unlimited = true;
                $this->count_in_stock ++;
            } else {
                // now cast to int
                $stock = (int) $stock;
                // for zero for our next comparison..
                $stock           = $stock > 0 ? $stock : 0;
                $stock_unlimited = false;

                if ( $stock === 0 ) {
                    $this->count_no_stock ++;
                } else {
                    $this->count_total_stock += $stock;
                    $this->count_in_stock ++;
                }
            }

            if ( $this->supplier->locale === APP_LOCALE_CANADA ) {
                $col_stock_amt = 'stock_amt_ca';
                $col_stock_sold = 'stock_sold_ca';
                $col_stock_unlimited = 'stock_unlimited_ca';
                $col_stock_update_id = 'stock_update_id_ca';
                $col_stock_discontinued = 'stock_discontinued_ca';
            } else {
                $col_stock_amt = 'stock_amt_us';
                $col_stock_sold = 'stock_sold_us';
                $col_stock_unlimited = 'stock_unlimited_us';
                $col_stock_update_id = 'stock_update_id_us';
                $col_stock_discontinued = 'stock_discontinued_us';
            }

            $updated = $db->update( $db_table, [
                $col_stock_amt => $stock,
                $col_stock_sold => 0,
                $col_stock_unlimited => $stock_unlimited ? 1 : 0,
                $col_stock_update_id => (int) $this->db_stock_update->get_primary_key_value(),
                $col_stock_discontinued => 0,
            ], [
                'part_number' => $part_number,
            ], [
                $col_stock_amt => '%d',
                $col_stock_sold => '%d',
                $col_stock_unlimited => '%d',
                $col_stock_update_id => '%d',
                $col_stock_discontinued => '%d',
            ] );

            if ( ! $updated ) {
                $this->errors[] = 'Product update failed ' . $part_number . '.';
            }

            // ie. $this->{types/brands/suppliers}_affected++
            $this->register_if_not_in_array( 'suppliers_affected', gp_test_input( $product['supplier'] ) );
            $this->register_if_not_in_array( 'brands_affected', gp_test_input( $product['brand_slug'] ) );
            $this->register_if_not_in_array( 'types_affected', $type );

            $this->count_updated ++;
        }

        $this->profile( 'commit_0' );
        $db->execute("COMMIT;");
        $this->profile( 'commit_1' );

		if ( $this->supplier->mark_products_not_in_array_as_not_sold ) {
			$this->count_not_sold = $this->set_products_not_in_array_to_not_sold();
		}
	}

	/**
	 * Products not in the array that have one of the suppliers in $this->supplier->allowed_suppliers
	 * get marked as not sold. We have to take into account the locale, and only mark locale specific columns.
	 *
	 * For each of these products, mark stock_discontinued_us or stock_discontinued_ca to true, but
	 * in addition to this, reset the other stock related columns back to default values.
	 *
	 * The same query works on both tires and rims (by only changing table name) since their column
	 * structure for stock related items is the same.
	 */
	public function set_products_not_in_array_to_not_sold() {

		// I think if we did do a universal file, we would have to have allowed tire suppliers
		// and allowed rims suppliers, instead of just allowed suppliers.
		// but, we can always process the same file in 2 different operations if we need to.
		if ( $this->supplier->type === 'universal' ) {
			throw new Exception( 'Universal supplier inventory import type is not supported at this time if using the set_products_not_in_array_to_not_sold method.' );
		}

		$table = $this->supplier->type === 'tires' ? DB_tires : DB_rims;

		$stock_update_id = $this->db_stock_update->get_primary_key_value();

		// database column names (same for tires and rims tables)
		$col_stock_unlimited    = gp_esc_db_col( DB_Product::get_column_stock_unlimited( $this->supplier->locale ) );
		$col_stock_update_id    = gp_esc_db_col( DB_Product::get_column_stock_update_id( $this->supplier->locale ) );
		$col_stock_amt          = gp_esc_db_col( DB_Product::get_column_stock_amt( $this->supplier->locale ) );
		$col_stock_sold         = gp_esc_db_col( DB_Product::get_column_stock_sold( $this->supplier->locale ) );
		$col_stock_discontinued = gp_esc_db_col( DB_Product::get_column_stock_discontinued( $this->supplier->locale ) );

		/**
		 * We're running a query similar to...
		 *
		 * UPDATE rims
		 * SET
		 * stock_amt_ca = 0,
		 * stock_sold_ca = 0,
		 * stock_unlimited_ca = 0,
		 * stock_discontinued_ca = 1,
		 * stock_update_id_ca = 18
		 * WHERE 1 = 1
		 * AND ( stock_update_id_ca <> 18 OR stock_update_id_ca IS NULL )
		 * AND sold_in_ca = 1
		 * AND supplier IN ("canada-tire", "canada-tire-supply") ;
		 */

		$db = get_database_instance();
		$p  = array();
		$q  = '';
		$q  .= 'UPDATE ' . gp_esc_db_table( $table ) . ' ';
		$q  .= 'SET ';
		$q  .= $col_stock_amt . ' = 0, ';
		$q  .= $col_stock_sold . ' = 0, ';
		$q  .= $col_stock_unlimited . ' = 0, ';
		$q  .= $col_stock_discontinued . ' = 1, ';
		$q  .= $col_stock_update_id . ' = :set_stock_update_id ';

		$p[] = [ 'set_stock_update_id', $stock_update_id, '%d' ];

		$q .= 'WHERE 1 = 1 ';

		// products not having the stock update ID that we just ran.
		// son of a bitch. don't forget the IS NULL check (wasted 5 hours on this one)
		$q   .= "AND ( $col_stock_update_id <> :cmp_stock_update_id OR $col_stock_update_id IS NULL ) ";
		$p[] = [ 'cmp_stock_update_id', $stock_update_id, '%d' ];

		// $col_sold_in = DB_Product::get_column_sold_in( $this->supplier->locale );

        // it is important to not assert the "sold_in" condition here.
        // this is because we track inventory for all products regardless of whether they are sold on the website,
        // this is so that some products can have their inventory levels sent to amazon, but not be sold
        // on the website.
        // therefore, leave this commented out.
		// $q .= "AND $col_sold_in = 1 ";

		$list = sql_get_comma_separated_list( $this->supplier->allowed_suppliers, $p, '%s', 'supplier_' );
		$q    .= "AND supplier IN ( $list )";

		$q .= '; ';

		$st      = $db->bind_params( $q, $p );
		$execute = $st->execute();

		if ( ! $execute ) {
			log_data( debug_pdo_statement( $q, $p ), 'failed_update--set_products_not_in_array_to_not_sold' );
		}

		$rowCount = $execute ? (int) $st->rowCount() : false;

		// update counts
		$this->count_updated += $rowCount;

		// actually we can't do these effectively because we don't know for sure which suppliers, types, and brands were affected.
		// technically we can kind of know the type since were not supporting universal, but since we do support multiple
		// suppliers, we can't know how much of $rowCount is attribute to each one.
		// $this->register_if_not_in_array( 'suppliers_affected', '__discontinued_count', $rowCount );

		return $rowCount;
	}

	/**
	 * @see $this->types_affected
	 * @see $this->brands_affected
	 * @see $this->suppliers_affected
	 *
	 * @param $class_prop
	 * @param $value
	 */
	public function register_if_not_in_array( $class_prop, $value, $increment_by = 1 ) {

		assert( gp_is_integer( $increment_by ) );

		if ( is_array( $this->{$class_prop} ) ) {
			if ( ! isset( $this->{$class_prop}[ $value ] ) ) {
				$this->{$class_prop}[ $value ] = 0;
			}
			$this->{$class_prop}[ $value ] += $increment_by;
		}
	}

	/**
	 * Must follow run_main()
	 */
	private function run_cleanup() {

		$average_stock = $this->count_updated ? round( $this->count_total_stock / $this->count_updated, 1 ) : 0;

		// dump any other data we care to store into the DB
		$dump = array(
			'average_stock' => $average_stock,
			'count_unlimited' => $this->count_stock_unlimited,
			'count_not_allowed_due_to_supplier' => $this->count_not_allowed_due_to_supplier,
			'count_not_allowed_due_to_not_sold_in_locale' => $this->count_not_allowed_due_to_not_sold_in_locale,
			'operations' => $this->operations,
            'ftp_debug' => $this->supplier->ftp ? $this->supplier->ftp->get_debug_array() : null,
            'profile' => $this->profile,
		);

		// ie. "mazzi: 45, rtx: 50, other_rim_brand: 12"
		$affected_to_string = function ( $affected ) {
			$a2 = array();
			foreach ( $affected as $k => $v ) {
				$a2[] = $k . ': ' . $v;
			}

			return implode( ', ', $a2 );
		};

		$this->db_stock_update->update_database_and_re_sync( array(
			'stock_types_affected' => $affected_to_string( $this->types_affected ),
			'stock_suppliers_affected' => $affected_to_string( $this->suppliers_affected ),
			'stock_brands_affected' => $affected_to_string( $this->brands_affected ),
			'stock_errors' => gp_db_encode( $this->errors ),
			'count_csv' => $this->count_csv,
			'count_processed' => $this->count_processed,
			'count_updated' => $this->count_updated,
			'count_not_found' => $this->count_not_found,
			'count_in_stock' => $this->count_in_stock,
			'count_no_stock' => $this->count_no_stock,
			'count_total_stock' => $this->count_total_stock,
			'count_not_sold' => $this->count_not_sold,
			'stock_seconds' => round( end_time_tracking( 'Supplier_Inventory_Import' ), 2 ),
			'stock_dump' => gp_db_encode( $dump ),
		) );
	}

    /**
     * @return array
     */
    static function get_ex_rims(){

        Product_Sync::ini_config();

        $q = "
        SELECT part_number, supplier, brand_slug, model_slug, sold_in_ca, sold_in_us FROM rims
        ";

        $rims = get_database_instance()->get_results( $q );

        $rims = array_map(function( $rim ) {
            return (array) $rim;
        }, $rims );

        return self::index_by( $rims, function( $rim ) {
            return $rim['part_number'];
        });
    }

    /**
     * @return array
     */
    static function get_ex_tires(){

        $q = "
        SELECT part_number, supplier, brand_slug, model_slug, sold_in_ca, sold_in_us FROM tires        
        ";

        $tires = get_database_instance()->get_results( $q );

        $tires = array_map(function( $tire ) {
            return (array) $tire;
        }, $tires );

        return self::index_by( $tires, function( $tire ) {
            return $tire['part_number'];
        });
    }

    /**
     * @param $rows
     * @param $fn
     * @return array
     */
    static function index_by( $rows, $fn ) {
        $ret = [];
        foreach ( $rows as $k => $v ) {
            $_k = $fn($v);
            $ret[$_k] = $v;
        }
        return $ret;
    }
}