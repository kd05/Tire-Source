<?php

use function _\sortBy;

class Product_Sync_Admin_UI {

    /**
     *
     */
    static function render() {

        // side-effect but that's fine, it only runs a very fast query.
        Product_Sync::ensure_suppliers_exist();

        $syncs = Product_Sync::get_instances();
        $sync = Product_Sync::get_via_key( @$_GET[ 'key' ] );

        if ( $sync ) {
            if ( @$_GET[ 'action' ] === 'sync' ) {
                self::render_action_sync( $sync );
            } else {
                self::render_single( $sync );
            }
            return;
        }

        self::render_all( $syncs );
    }

    /**
     * @param array $syncs
     */
    static function render_all( array $syncs ) {

        Header::$title = "Product Sync";

        echo self::breadcrumb( [
            [ 'Product Sync', self::get_url( null ) ],
            [ 'Display All', '' ],
        ] );

        echo self::br( 15 );

        ?>
        <div class="general-content">
            <p>Click Fetch to download, parse, and save a copy of the supplier file and associated products to our server (this does not make any updates to products).</p>
            <p>Click Synchronize (Last Valid) in the Actions column to view the changes in the last successful Fetch. From there you can choose to Approve/Sync the changes.</p>
            <p>If the last fetch had zero valid products, click the Synchronize button in the Last Fetch column for more info (check the Invalid Products tab). In most cases, always prefer the Last Valid button.</p>
            <br>
        </div>
        <?php

        $syncs = sortBy( $syncs, function( $s ) {
            return $s::KEY;
        });

        echo self::render_table( null, self::table_rows( $syncs ), [
            'title' => 'Product Syncs',
            'sanitize' => false,
        ] );
    }

    /**
     * @param array $syncs
     * @return array
     */
    static function table_rows( array $syncs ) {
        return array_map( [ 'Product_Sync_Admin_UI', 'table_row' ], $syncs );
    }

    /**
     * @param Product_Sync $sync
     * @return array
     */
    static function table_row( Product_Sync $sync ) {

        $ftp = $sync->get_ftp_obj();

        $supplier_price_rule = Product_Sync_Pricing_UI::get_single_price_rule( $sync::TYPE, $sync::LOCALE, $sync::SUPPLIER, '', '' );

        $last_valid_req = DB_Sync_Request::get_latest_without_errors( $sync::KEY );
        $last_valid_req = $last_valid_req ? $last_valid_req->to_array() : null;

        $reqs = Product_Sync::get_results( 'select * from sync_request where sync_key = :sync_key ORDER BY inserted_at DESC LIMIT 0, 1', [ [ 'sync_key', $sync::KEY ] ] );
        $updates = Product_Sync::get_results( 'select * from sync_update where sync_key = :sync_key ORDER BY date DESC LIMIT 0, 1', [ [ 'sync_key', $sync::KEY ] ] );

        $last_req = @$reqs[0];
        $last_update = @$updates[0];

        $last_req_id = $last_req ? (int) $last_req['id'] : 'N/A';
        $last_req_date = $last_req ? gp_test_input( $last_req['inserted_at'] ) : 'Not Found.';

        $last_valid_req_id = $last_valid_req ? (int) $last_valid_req['id'] : 'N/A';
        $last_valid_req_date = $last_valid_req ? gp_test_input( $last_valid_req['inserted_at'] ) : 'Not Found.';

        $last_update_id = $last_update ? (int) $last_update['sync_update_id'] : 'N/A';
        $last_update_date = $last_update ? gp_test_input( $last_update['date'] ) : 'Not Found.';

        $reqs_url = get_admin_archive_link( 'sync_request', [
            'sync_key' => $sync::KEY,
        ] );

        $updates_url = get_admin_archive_link( 'sync_update', [
            'sync_key' => $sync::KEY,
        ] );

        $format = function( $num ) {
            return number_format( floatval( $num ), 0, ',', '.' );
        };

        $_last_req = implode( self::br(10), [
            "ID: $last_req_id (" . html_link( $reqs_url, "View All" ) . ")",
            "Date: " . $last_req_date,
            "Products In File: " . $format( @$last_req['count_all'] ),
            "Valid Products: " . $format( @$last_req['count_valid'] ),
            "Changes Required: " . $format( @$last_req['count_changes'] ),
            gp_get_link( self::get_url( $sync::KEY, [ 'action' => 'sync', 'limit' => 1000, 'req_id' => $last_req_id ] ), 'Synchronize', false, [
                'title' => ""
            ] )
        ]);

        if ( $last_req_id && $last_req_id == $last_valid_req_id ) {
            $_last_valid_req = '{same}';
        } else {
            $_last_valid_req = implode( self::br(10), [
                "ID: $last_valid_req_id (" . html_link( $reqs_url, "View All" ) . ")",
                "Date: " . $last_valid_req_date,
                "Products In File: " . $format( @$last_valid_req['count_all'] ),
                "Valid Products: " . $format( @$last_valid_req['count_valid'] ),
                "Changes Required: " . $format( @$last_valid_req['count_changes'] ),
            ]);
        }

        $_update = implode( self::br(10), [
            "ID: $last_update_id (" . html_link( $updates_url, "View All" ) . ")",
            "Date: " . $last_update_date,
        ]);

        $warning = $supplier_price_rule ? '' : '<div class="red" style="font-weight: 700;">Warning: Supplier price rule not configured.</div>';

        $_fetch = $sync::CRON_FETCH ? "Yes" : "No";
        $_price = $sync::CRON_PRICES ? "Yes" : "No";
        $_alert = $sync::CRON_EMAIL ? "Yes" : "No";

        $file_etc = implode( self::br(5), array_filter( [
            $ftp ? "FTP File: " : '',
            $ftp ? $ftp->remote_file_name : '',
            $sync::FETCH_TYPE === 'local' ? 'Local file: ' : '',
            $sync::FETCH_TYPE === 'local' ? $sync::LOCAL_FILE : '',
            $sync::FETCH_TYPE === 'api' ? 'API/Custom' : '',
            "Auto Fetch (Once per day): $_fetch",
            "Auto Price Updates: $_price",
            "Email Alerts: $_alert",
            "Supplier Priority: " . $sync->get_priority(),
        ]));

        $key = implode( self::br(5), array_filter( [
            gp_get_link( self::get_url( $sync::KEY ), $sync::KEY ),
            '<div style="height: 3px;"></div>',
            $sync->get_admin_notes() ? "Notes: " : '',
            $sync->get_admin_notes() ? implode( ", ", $sync->get_admin_notes() ) : '',
            $warning,
        ]));

        list( $supplier_rules, $brand_rules, $model_rules ) = Product_Sync_Pricing_UI::get_supplier_price_rules(
                $sync::TYPE, $sync::LOCALE, $sync::SUPPLIER );

        $edit_price_rules_url = Product_Sync_Pricing_UI::get_edit_url( $sync::SUPPLIER, $sync::TYPE, $sync::LOCALE );

        $price_rules_counts = implode( ", ", [
            count( $supplier_rules ),
            count( $brand_rules ),
            count( $model_rules ),
        ]);

        $actions = implode( self::br(5), [
            gp_get_link( $edit_price_rules_url, "Edit Price Rules ($price_rules_counts)" ),
            gp_get_link( self::get_url( $sync::KEY, [ 'view' => 'insert' ] ), 'Fetch', false ),
            gp_get_link( self::get_url( $sync::KEY, [ 'action' => 'sync', 'limit' => 1000 ] ), 'Synchronize (Last Valid)' ),
            gp_get_link( self::get_supplier_db_page_link( $sync::TYPE, $sync::LOCALE, $sync::SUPPLIER ), 'Check Database Products' ),
        ] );

        return [
            'Key etc.' => html_element( $key, 'div', '', [
                'style' => 'max-width: 225px;'
            ] ),
            'Supplier' => $sync::SUPPLIER,
            'Type' => $sync::TYPE,
            'Locale' => $sync::LOCALE === 'CA' ? 'Canada' : 'US',
            'Parse File (Dev Tool)' => implode( self::br(2), [
                gp_get_link( self::get_url( $sync::KEY, [ 'view' => 'raw' ] ), 'Raw' ),
                gp_get_link( self::get_url( $sync::KEY, [ 'view' => 'parsed' ] ), 'Parsed' ),
                gp_get_link( self::get_url( $sync::KEY, [ 'view' => 'table', 'limit' => 999999, 'compact' => 1 ] ), 'Table (Source, All)' ),
                gp_get_link( self::get_url( $sync::KEY, [ 'view' => 'table', 'limit' => 50, 'compact' => 1 ] ), 'Table (Source, 50 Rows)' ),
                gp_get_link( self::get_url( $sync::KEY, [ 'view' => 'table', 'byProduct' => 1, 'limit' => 999999, 'compact' => 1 ] ), 'Table (Products, All)' ),
                gp_get_link( self::get_url( $sync::KEY, [ 'view' => 'table', 'byProduct' => 1, 'limit' => 50, 'compact' => 1 ] ), 'Table (Products, 50 Rows)' ),
                gp_get_link( self::get_url( $sync::KEY, [ 'view' => 'table', 'byProduct' => 1, 'limit' => 999999, 'filter' => 'invalid' ] ), 'Table (Invalid Products)' ),
                gp_get_link( self::get_url( $sync::KEY, [ 'view' => 'freq', 'filter' => 'all', 'byProduct' => 0 ] ), 'Column Counts (Source)' ),
                gp_get_link( self::get_url( $sync::KEY, [ 'view' => 'freq', 'filter' => 'all', 'byProduct' => 1 ] ), 'Column Counts (Products)' ),
            ] ),
            'FTP File etc.' => $file_etc,
            // 'Products In File' => number_format( floatval( $last_req_count ), 0, '.', ',' ),
            'Last Fetch' => $_last_req,
            'Last Valid Fetch' => $_last_valid_req,
            'Last Sync' => $_update,
            // 'Last Fetch/Sync' => $checked,
            'Actions' => $actions,

        ];
    }

    /**
     * @param $entities
     * @param $limit
     * @return array
     */
    static function limit_items_shown( $entities, $limit ) {
        // this function used to shuffle entities if there were more of
        // them than the limit. But now I turned that off, so its a very
        // redundant function right now.
        return array_slice( $entities, 0, $limit );
    }

    /**
     * @param Product_Sync $sync
     * @throws Exception
     */
    static function render_single( Product_Sync $sync ) {
        require CORE_DIR . '/classes/product-sync/views/sync-single.php';
    }

    /**
     * @param $rows
     * @param $sort_by_value
     * @param $limit
     * @return array[]
     */
    static function get_frequency_data( $rows, $sort_by_value, $limit ) {

        $limit = intval( $limit );
        $limit = $limit ? $limit : 500;
        $columns = $rows ? array_keys( $rows[ 0 ] ) : [];
        $freq = Product_Sync::get_frequencies( $columns, $rows );

        return array_map( function ( $arr ) use ( $sort_by_value, $limit ) {

            $count_empty = @$arr[ '' ];

            if ( $sort_by_value ) {
                ksort( $arr );
            } else {
                asort( $arr, SORT_NUMERIC );
                $arr = array_reverse( $arr, true );
            }

            if ( count( $arr ) > $limit ) {

                $_arr = array_filter( $arr, function ( $count ) {

                    return $count > 1;
                } );

                $vals = array_keys( $arr );
                shuffle( $vals );
                return [
                    'count_unique_values' => count( $arr ),
                    'count_empty' => $count_empty,
                    'data' => "Full data not displayed. Number of unique values exceeded limit ($limit)",
                    'random_20' => implode( ", ", array_slice( $vals, 0, 20 ) ),
                    'with_freq_gt_1' => array_slice( $_arr, 0, 200 ),
                ];
            } else {

                return [
                    'count_unique_values' => count( $arr ),
                    'count_empty' => $count_empty,
                    'data' => $arr,
                ];
            }
        }, $freq );
    }

    /**
     * Intended for printing via self::pre_print_r
     *
     * @param Product_Sync $sync
     * @param Product_Sync_Fetch $fetch
     * @return array
     */
    static function get_fetch_summary( Product_Sync $sync, Product_Sync_Fetch $fetch ) {

        list( $valid, $invalid ) = $fetch::filter_valid( $fetch->rows );
        return [
            'filters' => $_GET,
            'error?' => $fetch->errors ? implode( ", ", $fetch->errors ) : "None",
            'count_all' => count( $fetch->rows ),
            'count_valid' => count( $valid ),
            'count_invalid' => count( $invalid ),
            'columns' => implode( ", ", $fetch->columns ),
            'column_count' => count( $fetch->columns ),
            // 'tracker' => $sync->tracker->display_summary(false),
            'debug' => $fetch->debug,
        ];
    }

    /**
     * @param $sync_key
     * @param $columns
     * @return string
     */
    static function link_cols( $sync_key, $columns ) {

        $anchors = array_map( function ( $col ) use ( $sync_key ) {

            return gp_get_link( self::get_url( $sync_key, [
                'showCol' => $col
            ] ), $col, true );
        }, $columns );

        return wrap_tag( implode( ", ", $anchors ), 'p' );
    }


    /**
     * @param $content
     * @param bool $sanitize - should probably NEVER be false
     * @param bool $nowrap - true for CSV's usually
     * @param bool $nl2br - true for CSV's usually
     * @return string
     */
    static function pre_print_r( $content, $sanitize = true, $nowrap = false, $nl2br = false ) {

        if ( $sanitize ) {
            $str = htmlspecialchars( print_r( $content, true ), ENT_SUBSTITUTE );
        } else {
            $str = print_r( $content, true );
        }

        if ( $nl2br ) {
            $str = nl2br( $str );
        }

        return html_element( $str, 'pre', '', [
            'style' => $nowrap ? 'white-space: nowrap;' : 'white-space: pre-wrap;',
        ] );
    }

    /**
     * @param $portions
     * @return string
     */
    static function breadcrumb( $portions ) {

        $portions = array_filter( $portions );

        if ( ! $portions ) {
            return '';
        }

        $inner = implode( " &gt; ", array_map( function ( $portion ) {

            $args = @$portion[2] ? @$portion[2] : [];

            return $portion[ 1 ]
                ? gp_get_link( $portion[ 1 ], gp_test_input( $portion[ 0 ] ), $args )
                : wrap_tag( gp_test_input( $portion[ 0 ] ), 'span' );
        }, $portions ) );

        return wrap_tag( $inner, 'p' );
    }

    /**
     * @param $cols
     * @param $rows
     * @param array $args
     * @return string
     */
    static function render_table( $cols, $rows, $args = [] ) {

        if ( ! isset( $args[ 'add_count' ] ) ) {
            $args[ 'add_count' ] = true;
        }

        if ( ! isset( $args[ 'sanitize' ] ) ) {
            $args[ 'sanitize' ] = true;
        }

        return render_html_table_admin( $cols, $rows, $args );
    }

    /**
     * @param int $h
     * @return string
     */
    static function br( $h = 10 ) {

        return '<div style="height: ' . (int) $h . 'px"></div>';
    }

    /**
     * @param null $key
     * @param array $query
     * @return string
     */
    static function get_url( $key = null, array $query = [] ) {

        $query[ 'page' ] = 'product_sync';
        if ( $key ) {
            $query[ 'key' ] = $key;
        }

        $query = gp_array_sort_by_keys( $query, [ 'page', 'key', 'view' ] );

        return Router::build_url( [ 'cw-admin' ], $query );
    }

    /**
     * @param Product_Sync $sync
     * @throws Exception
     */
    static function render_action_sync( Product_Sync $sync ) {
        require CORE_DIR . '/classes/product-sync/views/action-sync.php';
    }

    /**
     * @param $diffs
     * @return string
     */
    static function diffs_to_html( $diffs ) {

        $parts = [];

        foreach ( $diffs as $key => $arr ) {
            if ( $arr[ 0 ] ) {
                $parts[] = "field: " . $key;
                $parts[] = "was: " . gp_test_input( $arr[ 1 ] );
                $parts[] = "now: " . gp_test_input( $arr[ 2 ] );
            }
        }

        return implode( "<br>", $parts );
    }

    /**
     * @param $type
     * @param $locale
     * @param $supplier
     * @param $cols
     * @return string
     */
    static function get_supplier_db_page_link( $type, $locale, $supplier, $cols = '' ) {
        return get_admin_page_url( 'supplier_products', [
            'type' => gp_test_input( $type ),
            'supplier' => gp_test_input( $supplier ),
            'locale' => gp_test_input( $locale ),
            'cols' => gp_test_input( $cols ),
        ] );
    }

    /**
     * @param Product_Sync $sync
     * @return string
     */
    static function get_run_inventory_btn( Product_Sync $sync ){

        $supp = $sync->get_inventory_instance();

        ob_start();

        // this value is just like hardcoded in the /admin-templates file
        // so things will break if it gets changed there.
        $nonce_secret = 'importing-stock-137723';

        if ( $supp ) {
            ?>
            <form action="<?= get_admin_page_url( 'stock_import' ); ?>" method="post" target="_blank">
                <input type="hidden" name="nonce" value="<?= get_nonce_value( $nonce_secret, true ); ?>">
                <input type="hidden" name="hash_key" value="<?= gp_test_input( $supp::HASH_KEY ); ?>">
                <button type="submit">Run Inventory</button>
            </form>
            <?php
        } else {
            echo wrap_tag( "Not Found." );
        }

        return ob_get_clean();
    }

    /**
     * @param $sync_key
     * @param $req_id
     * @return false|string
     * @throws Exception
     */
    static function get_accept_changes_form( $sync_key, $req_id ){

        ob_start();
        ?>
        <form class="ps-accept-changes-form" action="<?= AJAX_URL . '/?__route__=sync_products' ?>" method="post" target="_blank">
            <input type="hidden" name="nonce" value="<?= Ajax::get_global_nonce(); ?>">
            <input type="hidden" name="sync" value="<?= gp_test_input( $sync_key ); ?>">
            <input type="hidden" name="req_id" value="<?= gp_test_input( $req_id ); ?>">
            <select name="accept_type" id="">
                <option value="">Choose an Option</option>
                <option value="">..</option>
                <option value="">..</option>
                <option value="prices">Price Changes Only</option>
                <option value="">..</option>
                <option value="">..</option>
                <option value="all">Accept All Changes</option>
                <option value="">..</option>
                <option value="">..</option>
            </select>
            <button type="submit">Submit</button>
        </form>
        <?php

        return ob_get_clean();
    }
}

