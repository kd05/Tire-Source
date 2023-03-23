<?php

Class Pagination_Stuff {

	public $page_num;
	public $per_page;
	public $offset;
	public $found_rows;
	public $last_page;

	/**
	 * Pagination_Stuff constructor.
	 *
	 * @param $page_num
	 * @param $per_page
	 */
	public function __construct( $page_num, $per_page, $found_rows = 0 ) {

		$page_num       = (int) $page_num;
		$page_num       = $page_num ? $page_num : 1;
		$this->page_num = $page_num;

		$per_page       = $per_page === '-1' || $per_page === '0' || ! $per_page ? - 1 : $per_page;
		$per_page       = (int) $per_page;
		$this->per_page = $per_page;

		$this->offset = $per_page !== - 1 ? ( ( $per_page * $page_num ) - $per_page ) : 0;

		$this->found_rows = (int) $found_rows;

		$this->last_page = 0;
		if ( $this->per_page > 0 ) {
			$this->last_page = ceil( $this->found_rows / $this->per_page );
		}
	}

	/**
	 * ie. do we need to show links to other pages based on input provided?
	 *
	 * @return bool
	 */
	public function should_do_pagination(){

		if ( $this->per_page === -1 ) {
			return false;
		}

		if ( $this->page_num === 1 && $this->found_rows <= $this->per_page ) {
			return false;
		}

		return true;
	}

	/**
	 * @param $arr
	 */
	public static function from_user_data( $arr, $found_rows = 0 ) {
		$page_num = gp_if_set( $arr, 'page_num' );
		$per_page = gp_if_set( $arr, 'per_page' );
		return new static( $page_num, $per_page, $found_rows );
	}

	/**
	 * turns the current vars into html using an external function. if you dont like this html that's
	 * fine, the purpose of the class is not to render, but to determine properties relating to pagination.
	 * you can put those class props into another function.
	 *
	 * P.s. make sure you instantiated with $found_rows before using this. This means that in common use,
	 * you may instantiate once before running a query to get its limit clause, and then another time once the
	 * query is run, inserting the number of found rows.
	 *
	 * @param bool  $skip_if_not_needed - return nothing if the only page number we would show is "1"
	 * @param int   $range
	 * @param null  $url
	 * @param array $args
	 *
	 * @return string
	 */
	public function get_pagination_html( $skip_if_not_needed = true, $range = 2, $url = null, $args = array() ){

		if ( $skip_if_not_needed ) {
			if ( ! $this->should_do_pagination() ) {
				return '';
			}
		}

		return get_pagination_html_with_anchors( 1, $this->last_page, $this->page_num, $range, $url, $args );
	}

	/**
	 * simple but not flexible function to spit out pagination in the way that we normally do it
	 * for for tables in the admin section.
	 *
	 * @return string
	 */
	public function get_page_controls_html(){

		$ret = get_page_controls( [
			'pagination_html' => $this->get_pagination_html( true, 3 ),
			'per_page_html' => get_per_page_options_html_admin( 'admin', get_admin_per_page_options(), 100 ),
		]);

		return $ret;
	}
}

/**
 * @param $offset
 * @param $per_page
 *
 * @return string
 */
function get_sql_limit( $offset, $per_page ) {
	$op = '';

	// these can easily come directly from $_GET, sanitation 100% necessary.
	$per_page = (int) $per_page;
	$offset = (int) $offset;

	if ( $per_page > 0 ) {
		$op .= 'LIMIT ' . $offset . ', ' . $per_page;
	}

	return $op;
}

Class Admin_Archive_Page {

	/**
	 * as in database model
	 *
	 * @var DB_Table|null
	 */
	public $db_object;

	/**
	 * database table name
	 *
	 * @var
	 */
	public $table;

	/**
	 * Some tables are wrapped in forms..
	 *
	 * @var
	 */
	public $post_back_response;

	/**
	 * @var array|stdClass
	 */
	public $query_results;

	public $per_page;
	public $page_num;
	public $offset;
	public $doing_pagination;
	public $found_rows;
	public $doing_single;

	/** @var  $_GET ['pk'] */
	public $pk;

	/**
	 * might have a primary key, in which case we're doing single
	 *
	 * @var bool
	 */
	public $doing_archive;

	/**
	 * Admin_Archive_Page constructor.
	 *
	 * @param DB_Table $db_object
	 * @param array    $args
	 */
	public function __construct( DB_Table $db_object, $args = array() ) {

		// I dont see any purpose to passing in $args to this fn.
		// instead, some classes might have their own get_admin_archive_page_args() method.
		$this->args = $args;
		$this->args = _array_merge( $args, $db_object->get_admin_archive_page_args() );

		$this->db_object = $db_object;
		$this->table     = $db_object->get_table();

		$this->pk            = (int) gp_if_set( $_GET, 'pk' );
		$this->doing_archive = ! $this->pk;

		// important to do this right about here. we need tables/objects/args, but also need to do
		// this before queries are run
		$this->handle_post_back();

		$page_num = (int) get_user_input_singular_value( $_GET, 'page_num' );
		$page_num = $page_num ? $page_num : 1;

		$per_page = get_per_page_preference( 'admin', 50 );
		$per_page = $per_page === '-1' || $per_page === '0' || ! $per_page ? - 1 : $per_page;

		$offset = $per_page !== - 1 ? ( ( $per_page * $page_num ) - $per_page ) : 0;

		$this->page_num = (int) $page_num;
		$this->per_page = (int) $per_page;
		$this->offset   = (int) $offset;

		$this->doing_pagination = $per_page > 0;

		if ( $this->doing_archive ) {
			$this->query_results = $this->query_archive();
		} else {
			$this->query_results = $this->query_single();
		}
	}

	/**
	 * this isn't super efficient when we have a file in admin-templates/edit/single-{table}.php
	 * but that's fine. I think we're just going to re-create the object inside that template file. Not sure
	 * yet if this will also run or not.
	 */
	public function query_single() {

		$this->doing_single = true;

		$pk_value = get_user_input_singular_value( $_GET, 'pk' );

		$db          = get_database_instance();
		$p           = array();
		$pk_col_name = gp_esc_db_col( $this->db_object->get_primary_key() );

		$q   = '';
		$q   .= 'SELECT * ';
		$q   .= 'FROM ' . gp_esc_db_col( $this->table ) . ' ';
		$q   .= 'WHERE 1 = 1 ';
		$q   .= 'AND ' . $pk_col_name . ' = :pk ';
		$p[] = [ 'pk', $pk_value ];
		$q   .= ';';

		$results = $db->get_results( $q, $p );

		$this->found_rows = $results ? 1 : 0;

		queue_dev_alert( 'admin archive query single', $q );

		return $results;
	}

	/**
	 *
	 */
	public function query_archive() {

		$this->doing_single = false;

		$pk_value = get_user_input_singular_value( $_GET, 'pk' );
		$db       = get_database_instance();

		$p = array();

		$pk_col_name = gp_esc_db_col( $this->db_object->get_primary_key() );

		$q = '';
		if ( $this->doing_pagination ) {
			$q .= 'SELECT SQL_CALC_FOUND_ROWS * ';
		} else {
			$q .= 'SELECT * ';
		}

		$q .= 'FROM ' . gp_esc_db_col( $this->table ) . ' ';

		$q .= 'WHERE 1 = 1 ';

		if ( $pk_value ) {
			$q   .= 'AND ' . $pk_col_name . ' = :pk ';
			$p[] = [ 'pk', $pk_value ];
		}

		/** @var Component_Builder $components */
		$components = $this->db_object->get_admin_archive_page_component_builder( $_GET );

		if ( $components ) {
			$q .= 'AND (' . $components->sql_with_placeholders() . ') ';
			$p = array_merge( $p, $components->parameters_array() );
		}

		$order_by = false;
		if ( method_exists( $this->db_object, 'get_order_by_args_for_admin_table' ) ) {
			$order_by = $this->db_object->get_order_by_args_for_admin_table();
		}

		// may as well default to always showing newest items first... this is especially important
		// for orders, users, reviews etc. for other tables, maybe it doesn't matter..
		$order_by = $order_by ? $order_by : [ $pk_col_name . ' DESC' ];

		$q .= get_sql_order_by_from_array( $order_by ) . ' ';

		if ( $this->doing_pagination ) {
			$q .= 'LIMIT ' . (int) $this->offset . ', ' . (int) $this->per_page . ' ';
		}

		$q .= ';';

		$results = $db->get_results( $q, $p );

		if ( $this->doing_pagination ) {
			$this->found_rows = get_sql_found_rows();
		}

		queue_dev_alert( 'admin archive query', $q );

		return $results;
	}

	/**
	 *
	 */
	public function get_html_table() {

		$data = array();

		$results = $this->query_results;

		// could be array or stdClass object
		if ( $results ) {

			$row_count = 0;

			foreach ( $results as $k => $row ) {

				$row_count ++;

				if ( $row ) {

					// by making the object in a weird way, we ensure its valid and we also
					// skip the __construct() function which might do other things like query for foreign objects
					$object = DB_Table::create_empty_instance_from_table( $this->table );
					$object->setup_data( $object->get_fields(), $row );

					if ( method_exists( $object, 'filter_row_for_admin_tables' ) ) {
						$row = $object->filter_row_for_admin_tables( $row );
					}

					if ( $this->get_arg( 'do_delete' ) ) {
						$data[ $k ][ 'delete' ] = '<input type="checkbox" name="delete[]" value="' . $object->get_primary_key_value() . '">';
					}

					$data[ $k ][ 'count' ] = $row_count;

					foreach ( $row as $r1 => $r2 ) {
						$pk = $this->db_object->get_primary_key();
						if ( $r1 === $pk ) {
							$value = '<a href="' . get_admin_single_edit_link( $this->table, $r2 ) . '">edit (' . $r2 . ')</a>';
						} else {
							$value = $object->get_cell_data_for_admin_table( $r1, $r2 );
							$value = $value !== null ? $value : gp_test_input( $r2 );
						}

						$data[ $k ][ $r1 ] = $value;
					}
				}
			}
		}

		// merging the objects fields is maybe always redundant? if not it mostly is..
		$cols = array_merge( array_keys( gp_force_array( gp_array_first( $data ) ) ), $this->db_object->get_fields() );
		$cols = array_unique( $cols );

        if ( isset( $object ) && method_exists( $object, 'omit_columns_for_admin_tables' ) ) {
            $omit = $object->omit_columns_for_admin_tables();
            $cols = array_filter( $cols, function( $col ) use( $omit ){
                return ! in_array( $col, $omit );
            } );
        }

		$args = array(
			'add_class' => 'admin-table',
		);

		$ret = render_html_table( $cols, $data, $args );

		return $ret;
	}

	/**
	 * @param      $key
	 * @param null $df
	 *
	 * @return bool|mixed
	 */
	public function get_arg( $key, $df = null ) {
		return gp_if_set( $this->args, $key, $df );
	}

	/**
	 * @return string
	 */
	public function render_per_page_options() {
		$op = '';
		$op .= get_per_page_options_html_admin( 'admin', get_admin_per_page_options(), 100 );

		return $op;
	}

	/**
	 * @return string
	 */
	public function get_page_base_url() {
		$url = get_admin_archive_link( $this->table );

		return $url;
	}

	/**
	 * @return string
	 */
	public function get_url_minus_page_num() {

		$base = full_path();

		$args = gp_sanitize_array_depth_1( $_GET );

		if ( isset( $args[ 'page_num' ] ) ) {
			unset( $args[ 'page_num' ] );
		}

		return cw_add_query_arg( $args, $base );
	}

	/**
	 * @param string $add_class
	 *
	 * @return string
	 */
	public function get_admin_page_controls( $add_class = '' ) {

		$pagination_html = $this->doing_pagination ? $this->render_pagination() : '';
		$per_page_html   = $this->render_per_page_options();

		return get_page_controls( [
				'pagination_html' => $pagination_html,
				'per_page_html' => $per_page_html,
			] );
	}

	/**
	 *
	 */
	public function render_pagination() {

		$ret = '';

		if ( $this->doing_pagination ) {
			$max = ceil( $this->found_rows / $this->per_page );

			if ( $max > 1 ) {
				$ret = get_pagination_html_with_anchors( 1, $max, $this->page_num, 3 );
			}
		}

		return $ret;

	}

	public function get_found_rows() {

		if ( $this->found_rows && $this->doing_pagination ) {
			return $this->found_rows;
		}

		if ( $this->query_results && is_array( $this->query_results ) ) {
			return count( $this->query_results );
		}

		return false;
	}

	// field names that exist in $_GET for the purposes of filtering the data.
	public function get_archive_page_filter_keys(){

	    $ret = [];

	    if ( $this->db_object ) {
            foreach ( $this->db_object->get_fields() as $field ) {

                if ( $field === 'page' || $field === 'table' ) {
                    continue;
                }

                if ( isset( $_GET[$field] ) ) {
                    $ret[] = $field;
                }
            }
        }

	    return $ret;
    }

	/**
	 *
	 */
	public function render_title() {

		$table = $this->db_object->get_table();

		$title = $table;

		if ( $this->doing_single ) {
            $title .= '(single) <a href="' . $this->get_page_base_url() . '">[go back]</a>';
		} else {
			$found = $this->get_found_rows();
			$title     .= $found ? ' (' . $found . ')' : '';

			if ( count( $this->get_archive_page_filter_keys() ) ) {
			    $title .= " " . get_anchor_tag_simple( $this->get_page_base_url(), "(View All)" );
            }
		}



		$op = '';
		$op .= '<h1 class="like-h2 tt-upper">' . $title . '</h1>';
		$op .= '';

		return $op;
	}

	/**
	 *
	 */
	public function render() {

		// for some (primary key / single row) pages, we have our own template file that ignores what is below.
		// for the pages where this file does not exist, we just go a very generic page showing 1 row of a table.
		// when we need to include forms for updates, we'll use this optional template.
		if ( $this->doing_single ) {
			$file = CORE_DIR . '/admin-templates/edit/single-' . $this->table;
			if ( file_exists( $file ) ) {
				include $file;

				return;
			}
		}

		// admin page controls have pagination and a posts per page option
		$controls = $this->doing_single ? '' : $this->get_admin_page_controls();
		$op       = '';

		// an optional template to insert some html near the top of the page, in addition to what's below
		$file_before = CORE_DIR . '/admin-templates/edit-archive-before/' . $this->table . '.php';
        $file_after = CORE_DIR . '/admin-templates/edit-archive-after/' . $this->table . '.php';

		if ( file_exists( $file_before ) ) {
		    ob_start();
			include $file_before;
			$op .= ob_get_clean();
		}

		$op .= $this->render_title();

		$op .= $controls;

		// have an option to delete individual rows of a table via a checkbox.. this is currently used on tires and rims.
		$do_delete = $this->get_arg( 'do_delete' );

		// adds deletion controls at the top of the table (check all (checkboxes), uncheck all, delete selected)
		if ( $do_delete ) {
			$op .= '<form method="post" class="form-style-basic">';

			$op .= get_hidden_inputs_from_array( array(
				'do_delete' => 1,
				'nonce' => get_nonce_value( 'admin_edit_do_delete', true ),
			) );

			$msgs = gp_if_set( $this->post_back_response, 'msgs' );

			if ( $msgs ) {
				$op .= get_form_response_text( gp_array_to_paragraphs( $msgs ) );
			}

			// tell javascript what to do via data attribute
			$js_select_all   = [];
			$js_select_all[] = [
				'bind' => 'click',
				'action' => 'check_all',
				'closest' => 'form',
				'find' => '.cell-delete input',
			];

			$js_deselect_all   = [];
			$js_deselect_all[] = [
				'bind' => 'click',
				'action' => 'uncheck_all',
				'closest' => 'form',
				'find' => '.cell-delete input',
			];

			$op .= '<div class="form-table-controls">';
			$op .= '<button class="js-bind" data-bind="' . gp_json_encode( $js_select_all ) . '" type="button">Select All (for deletion)</button>';
			$op .= '<button class="js-bind" data-bind="' . gp_json_encode( $js_deselect_all ) . '" type="button">De-select All</button>';
			$op .= '<button type="submit">Delete Selected</button>';
			$op .= '</div>';
		}

		// if $do_delete, table will render some checkboxes
		$op .= $this->get_html_table();

		if ( $do_delete ) {
			$op .= '</form>';
		}

        if ( file_exists( $file_after ) ) {
            ob_start();
            include $file_after;
            $op .= ob_get_clean();
        }

		return $op;
	}

	/**
	 *
	 */
	public function handle_post_back() {
		if ( $this->doing_archive ) {
			$method = 'handle_post_back_' . $this->table;
			if ( method_exists( $this, $method ) ) {
				$this->$method();
			}
		}
	}

	/**
	 * Delete tires or rims via primary keys. Note that we could do this
	 * pretty easily for all tables, but most tables have dependencies and should not
	 * be deleted without checking their relationships. In the case of tires/rims however,
	 * we can delete.
	 */
	public function handle_post_back_delete_tires_or_rims() {

		$do_delete = gp_if_set( $_POST, 'do_delete' );

		// deleting tires and rims works the same way since we have $this->db_object which
		// is already going to be a DB_Tire or DB_Rim by now..
		if ( $do_delete ) {

			$this->post_back_response[ 'msgs' ]   = array();
			$this->post_back_response[ 'msgs' ][] = 'Deleting Products...';

			if ( ! validate_nonce_value( 'admin_edit_do_delete', gp_if_set( $_POST, 'nonce' ), true ) ) {
				$this->post_back_response[ 'msgs' ][] = 'Nonce was not validated. Please re-navigate to the page and try again.';

				return;
			}

			$delete = get_user_input_array_value( $_POST, 'delete', array() );

			if ( $delete && is_array( $delete ) ) {
				foreach ( $delete as $primary_key_value ) {

					$obj = $this->db_object->create_instance_via_primary_key( $primary_key_value );

					if ( $obj ) {
						$obj_name = method_exists( $obj, 'get_cart_title' ) ? $obj->get_cart_title() : $obj->get_primary_key_value();
						$msg_pre = '[' . gp_test_input( $obj_name ) . ']';

						$deleted = $obj->delete_self_if_has_singular_primary_key();

						if ( $deleted ) {
							$this->post_back_response[ 'msgs' ][] = $msg_pre . ': deleted.';
						} else {
							$this->post_back_response[ 'msgs' ][] = $msg_pre . ': could not be deleted.';
						}

					} else {
						$msg_pre                              = '[' . gp_test_input( $primary_key_value ) . ']';
						$this->post_back_response[ 'msgs' ][] = $msg_pre . ' product not found so could not be deleted';
					}
				}
			} else {
				$this->post_back_response[ 'msgs' ][] = 'No products to delete';
			}
		}

	}

	/**
	 * This is being called dynamically, see $this->handle_post_back()
	 */
	public function handle_post_back_tires() {
		$this->handle_post_back_delete_tires_or_rims();
	}

	/**
	 * Dynamic function call b/c database table is "pages".
	 *
	 * Admittedly, this is super messy. The code originated for only
	 * tires and rims, but then we had to extend this for other tables. So..
	 * my apologies in advance if you need to work on this.
	 */
	public function handle_post_back_pages(){

		$do_delete = gp_if_set( $_POST, 'do_delete' );

		$obj = $this->db_object;
		assert( $obj::get_table() === DB_pages );

		$add_message = function( $msg ){
			$this->post_back_response['msgs'] = gp_if_set( $this->post_back_response, 'msgs', [] );
			$this->post_back_response['msgs'][] = $msg;
		};

		// deleting tires and rims works the same way since we have $this->db_object which
		// is already going to be a DB_Tire or DB_Rim by now..
		if ( $do_delete ) {

			$add_message( "Deleting Pages..." );

			// CSRF prevention
			if ( ! validate_nonce_value( 'admin_edit_do_delete', gp_if_set( $_POST, 'nonce' ), true ) ) {
				$add_message( "Nonce error. Please re-load the page and try again." );
				return;
			}

			$delete = get_user_input_array_value( $_POST, 'delete', array() );

			if ( $delete && is_array( $delete ) ) {
				foreach ( $delete as $primary_key_value ) {

					$page = DB_Page::create_instance_via_primary_key( $primary_key_value );

					if ( ! $page ) {
						$add_message( "Page specified for deletion was not found" );
						continue;
					}

					$name = $page->get( 'page_name', null, true );

					// if a file exists corresponding to this page, then it is registered and should not be deleted.
					if ( ! $page->can_be_deleted_via_admin_user() ) {
						$add_message( "Page with name ($name) is a \"registered\" page and cannot be deleted." );
					} else {
						$deleted = $page->delete_self_if_has_singular_primary_key();
						$add_message( $deleted ? "$name Deleted." : "Deletion failed for unknown reason." );
					}
				}
			} else {
				$add_message( "No pages to delete" );
			}
		}
	}

	/**
	 * This is being called dynamically, see $this->handle_post_back()
	 */
	public function handle_post_back_suppliers(){

		$primary_keys_to_delete = gp_if_set( $_POST, 'delete', array() );

		if ( ! $primary_keys_to_delete || ! is_array( $primary_keys_to_delete ) ) {
			return;
		}

		$msgs = [];
		$count = count( $primary_keys_to_delete );
		$msgs[] = "Attempting to delete $count suppliers...";

		// these tables store supplier slugs
		$tire_suppliers = get_all_column_values_from_table( DB_tires, 'supplier' );
		$rim_suppliers = get_all_column_values_from_table( DB_rims, 'supplier' );

		// the items to delete is the auto increment integer primary key which is not the slug
		foreach ( $primary_keys_to_delete as $primary_key ) {

			$supplier = DB_Supplier::create_instance_via_primary_key( $primary_key );

			if ( ! $supplier ) {
				$msgs[] = "A supplier specified was not found (" . gp_test_input( $primary_key ) . ")";
				continue;
			}
			
			$supplier_slug = $supplier->get('supplier_slug' );

			$name = gp_test_input( $supplier_slug );

			// due to foreign key constraints i dont think we actually need to check this...
			if ( in_array( $supplier_slug, $tire_suppliers ) ) {
				$msgs[] = "$name is used by tires and cannot be deleted.";
				continue;
			}

			// due to foreign key constraints i dont think we actually need to check this...
			if ( in_array( $supplier_slug, $rim_suppliers ) ) {
				$msgs[] = "$name is used by rims and cannot be deleted.";
				continue;
			}

			$deleted = $supplier->delete_self_if_has_singular_primary_key();

			if ( $deleted ) {
				$msgs[] = "$name was deleted.";
			} else {
				$msgs[] = "$name could not be deleted for unknown reason (possibly its in use in another table). Another possibility is that it was actually deleted but the code failed to pick up on that. Please re-load the page the see if it still exists.";
			}
		}

		$this->post_back_response[ 'msgs' ] = $msgs;
	}

	/**
	 * This is being called dynamically, see $this->handle_post_back()
	 */
	public function handle_post_back_rims() {
		$this->handle_post_back_delete_tires_or_rims();
	}
}

/**
 * @param $table
 * @param array $query_args
 * @return string
 */
function get_admin_archive_link( $table, $query_args = array() ) {

	$url = cw_add_query_arg( array(
		'page' => 'edit',
		'table' => gp_esc_db_col( $table ),
	), ADMIN_URL );

	$url = $query_args ? cw_add_query_arg( $query_args, $url ) : $url;

	return $url;
}

/**
 * @param $table
 * @param $pk
 * @return string
 */
function get_admin_single_edit_link( $table, $pk ) {

	$url = cw_add_query_arg( array(
		'page' => 'edit',
		'table' => gp_esc_db_col( $table ),
		'pk' => gp_test_input( $pk ),
	), ADMIN_URL );

	return $url;
}

/**
 * @param       $table
 * @param       $pk
 * @param array $args
 *
 * @return string
 */
function get_admin_single_edit_anchor_tag( $table, $pk, $args = array() ) {
	$text = gp_if_set( $args, 'text', gp_test_input( $pk ) );
	return get_anchor_tag_simple( get_admin_single_edit_link( $table, $pk ), $text, $args );
}

/**
 * pagination and per page. this just wraps some css classes around divs pretty much.
 *
 * @param $pagination_html
 * @param $per_page_html
 */
function get_page_controls( $args = array() ) {

	$pagination_html = @$args['pagination_html'];
	$per_page_html = @$args['per_page_html'];

	$op = '';

	$cls   = [ 'admin-archive-page-controls' ];
	$cls[] = gp_if_set( $args, 'add_class' );

	$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';

	if ( $pagination_html ) {
		$op .= '<div class="left">';
		$op .= $pagination_html;
		$op .= '</div>';
	}

	$op .= '<div class="right">';
	$op .= $per_page_html;
	$op .= '</div>';

	$op .= '</div>';

	return $op;
}