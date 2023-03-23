<?php

use Product_Sync_Admin_UI as ui;
use function _\sortBy;

class Product_Sync_Pricing_UI{

    static function render(){

        // side-effect but that's fine, it only runs a very fast query.
        Product_Sync::ensure_suppliers_exist();

        $slug = @$_GET['supplier'];
        $type = @$_GET['type'];
        $locale = @$_GET['locale'];
        $suppliers = Product_Sync_Compare::get_ex_suppliers();

        if ( $slug ) {
            self::render_single( $slug, $type, $locale );
        } else {
            self::render_all( $suppliers );
        }
    }

    /**
     * @param $type
     * @param $locale
     * @return array
     */
    static function get_product_counts_by_supplier( $type, $locale ) {

        // these go directly into query string. Make sure they are hardcoded.
        $table = $type === 'tires' ? 'tires' : 'rims';
        $column = $locale === 'CA' ? 'sold_in_ca' : 'sold_in_us';

        $q = "SELECT supplier, COUNT(supplier) c from $table WHERE $column = '1' GROUP BY supplier;";

        $rows = Product_Sync::get_results( $q );

        $ret = [];

        foreach ( $rows as $row ) {
            $ret[$row['supplier']] = $row['c'];
        }

        return $ret;
    }

    /**
     * @param $suppliers
     */
    static function render_all( $suppliers ){

        $suppliers = sortBy( $suppliers, 'supplier_slug' );

        $syncs = Product_Sync::get_instances();

        $all_price_rules = Product_Sync_Compare::get_cached_indexed_price_rules();

        $sync_suppliers = array_map( function( $sync ) {
            return $sync::SUPPLIER;
        }, $syncs );

        $tires_ca_counts = self::get_product_counts_by_supplier( 'tires', 'CA' );
        $tires_us_counts = self::get_product_counts_by_supplier( 'tires', 'US' );
        $rims_ca_counts = self::get_product_counts_by_supplier( 'rims', 'CA' );
        $rims_us_counts = self::get_product_counts_by_supplier( 'rims', 'US' );

        $item = function( $count_products, $price_rules_separated, $edit_url ) {

            $count_rules = array_map( 'count', $price_rules_separated );
            $count_rules_str = implode( "/", $count_rules );

            ob_start();
            ?>
            <div style="margin-bottom: 5px;"><?= (int) $count_products; ?> Products</div>
            <div><a href="<?= gp_sanitize_href( $edit_url ); ?>"><?= $count_rules_str; ?> Price Rules</a></div>
            <?php
            return ob_get_clean();
        };

        $table_rows = array_map( function( $supplier ) use( $syncs, $sync_suppliers, $tires_ca_counts, $tires_us_counts, $rims_ca_counts, $rims_us_counts, $item, $all_price_rules  ){

            $slug = gp_test_input( $supplier['supplier_slug'] );
            $has_sync = in_array( $supplier['supplier_slug'], $sync_suppliers );

            $tires_ca_url = self::get_edit_url( $slug, 'tires', 'CA' );
            $tires_us_url = self::get_edit_url( $slug, 'tires', 'US' );
            $rims_ca_url = self::get_edit_url( $slug, 'rims', 'CA' );
            $rims_us_url = self::get_edit_url( $slug, 'rims', 'US' );

            $tires_ca_count = gp_if_set( $tires_ca_counts, $slug, 0 );
            $tires_us_count = gp_if_set( $tires_us_counts, $slug, 0 );
            $rims_ca_count = gp_if_set( $rims_ca_counts, $slug, 0 );
            $rims_us_count = gp_if_set( $rims_us_counts, $slug, 0 );

            $tires_ca_rules = self::get_supplier_price_rules( 'tires', 'CA', $slug );
            $tires_us_rules = self::get_supplier_price_rules( 'tires', 'US', $slug );
            $rims_ca_rules = self::get_supplier_price_rules( 'rims', 'CA', $slug );
            $rims_us_rules = self::get_supplier_price_rules( 'rims', 'US', $slug );

            return [
                'supplier' => $slug,
                // 'has_sync' => $has_sync,
                'tires_ca' => $item( $tires_ca_count, $tires_ca_rules, $tires_ca_url ),
                'rims_ca' => $item( $rims_ca_count, $rims_ca_rules, $rims_ca_url ),
                'tires_us' => $item( $tires_us_count, $tires_us_rules, $tires_us_url ),
                'rims_us' => $item( $rims_us_count, $rims_us_rules, $rims_us_url ),
            ];

        }, $suppliers );

        echo ui::breadcrumb( [
            [ 'Price Rules', get_admin_page_url( 'pricing' ) ],
            [ 'Display All', '' ],
        ] );

        echo ui::br(15);

        echo Product_Sync_Admin_UI::render_table( null, $table_rows, [
            'sanitize' => false
        ]);
    }

    /**
     * @param $slug
     * @param $type
     * @param $locale
     * @return string
     */
    static function get_edit_url( $slug, $type, $locale ) {
        return get_admin_page_url( 'pricing', [
            'supplier' => $slug,
            'type' => $type,
            'locale' => $locale,
        ] );
    }

    /**
     * @param $price_rules
     * @return array[]
     */
    static function separate_rules( $price_rules ) {

        $supplier_rules = [];
        $brand_rules = [];
        $model_rules = [];

        foreach ( $price_rules as $row ) {

            if ( $row['supplier'] && $row['brand'] && $row['model'] ) {
                $model_rules[] = $row;
            } else if ( $row['supplier'] && $row['brand'] ) {
                $brand_rules[] = $row;
            } else if ( $row['supplier'] ) {
                $supplier_rules[] = $row;
            }
        }

        return [ $supplier_rules, $brand_rules, $model_rules ];
    }

    /**
     * @param $type
     * @param $locale
     * @param $supplier
     * @param bool $separate
     * @return array[]
     */
    static function get_supplier_price_rules( $type, $locale, $supplier, $separate = true ) {

        $q = "";
        $q .= "SELECT * FROM price_rules ";
        $q .= "WHERE type = :type AND locale = :locale AND supplier = :supplier ";
        $q .= "ORDER BY type, locale, supplier, brand, model ";

        $params = [
            [ 'type', $type ],
            [ 'locale', $locale ],
            [ 'supplier', $supplier ],
        ];

        $rows = Product_Sync::get_results( $q, $params );

        if ( $separate ) {
            return self::separate_rules( $rows );
        } else {
            return $rows;
        }
    }

    /**
     * @param $supplier
     * @param $locale
     * @return array[]
     */
    static function get_tire_brand_counts_via_supplier( $supplier, $locale ) {

        $sold_in = $locale === 'CA' ? 'sold_in_ca' : 'sold_in_us';
        $q = "
                SELECT COUNT(*) count_products, b.tire_brand_id brand_id, 
                       b.tire_brand_slug brand_slug, b.tire_brand_name brand_name 
                FROM tires t                
                INNER JOIN tire_brands b ON t.brand_id = b.tire_brand_id
                WHERE t.{$sold_in} = '1' AND t.supplier = :supplier
                GROUP BY b.tire_brand_id 
                ORDER BY b.tire_brand_slug            
            ";

        $params = [
            [ 'supplier', $supplier ]
        ];

        return Product_Sync::get_results( $q, $params );
    }

    /**
     * @param $supplier
     * @param $locale
     * @return array[]
     */
    static function get_tire_model_counts_via_supplier( $supplier, $locale ) {

        // might need to run this (with admin privileges) (every time sql server restarts)
        // SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));

        $sold_in = $locale === 'CA' ? 'sold_in_ca' : 'sold_in_us';
        $q = "
                SELECT COUNT(*) count_products, m.tire_model_id model_id, m.tire_model_slug model_slug,
                       m.tire_model_name model_name, b.tire_brand_id brand_id, b.tire_brand_slug brand_slug,
                       b.tire_brand_name brand_name 
                FROM tires t
                INNER JOIN tire_models m ON t.model_id = m.tire_model_id
                INNER JOIN tire_brands b ON t.brand_id = b.tire_brand_id
                WHERE t.{$sold_in} = '1' AND t.supplier = :supplier
                GROUP BY m.tire_model_id 
                ORDER BY b.tire_brand_slug, m.tire_model_slug            
            ";


        $params = [
            [ 'supplier', $supplier ]
        ];

        return Product_Sync::get_results( $q, $params );
    }

    /**
     * @param $supplier
     * @param $locale
     * @return array[]
     */
    static function get_rim_brand_counts_via_supplier( $supplier, $locale ) {

        $sold_in = $locale === 'CA' ? 'sold_in_ca' : 'sold_in_us';
        $q = "
                SELECT COUNT(*) count_products, b.rim_brand_id brand_id, 
                       b.rim_brand_slug brand_slug, b.rim_brand_name brand_name 
                FROM rims r                
                INNER JOIN rim_brands b ON r.brand_id = b.rim_brand_id
                WHERE r.{$sold_in} = '1' AND r.supplier = :supplier
                GROUP BY b.rim_brand_id 
                ORDER BY b.rim_brand_slug            
            ";


        $params = [
            [ 'supplier', $supplier ]
        ];

        return Product_Sync::get_results( $q, $params );
    }

    /**
     * @param $supplier
     * @param $locale
     * @return array[]
     */
    static function get_rim_models_counts_via_supplier( $supplier, $locale ) {

        // might need to run this (with admin privileges) (every time sql server restarts)
        // SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));

        $sold_in = $locale === 'CA' ? 'sold_in_ca' : 'sold_in_us';
        $q = "
                SELECT COUNT(*) count_products, m.rim_model_id model_id, m.rim_model_slug model_slug,
                       m.rim_model_name model_name, m.rim_brand_id brand_id, 
                       b.rim_brand_slug brand_slug, b.rim_brand_name brand_name 
                FROM rims r
                INNER JOIN rim_models m ON r.model_id = m.rim_model_id
                INNER JOIN rim_brands b ON r.brand_id = b.rim_brand_id
                WHERE r.{$sold_in} = '1' AND r.supplier = :supplier
                GROUP BY m.rim_model_id 
                ORDER BY b.rim_brand_slug, m.rim_model_slug         
            ";


        $params = [
            [ 'supplier', $supplier ]
        ];

        return Product_Sync::get_results( $q, $params );
    }

    /**
     * @param $name
     * @param $ex_value
     * @return string
     */
    static function pct_input( $name, $ex_value ) {
        return html_element( '', 'input', '', [
            'type' => 'number',
            'step' => '0.01',
            'name' => gp_test_input( $name ),
            'value' => gp_test_input( $ex_value ),
            'style' => 'min-width: 80px;',
        ] );
    }

    /**
     * @param $type
     * @param $locale
     * @param $supplier
     * @param string $brand
     * @param string $model
     * @return mixed|null
     * @throws Exception
     */
    static function get_single_price_rule( $type, $locale, $supplier, $brand = '', $model = '' ) {

        $where = [
            'type' => $type,
            'locale' => $locale,
            'supplier' => $supplier,
            'brand' => $brand ? $brand : '',
            'model' => $model ? $model : '',
        ];

        $rows = get_database_instance()->get( 'price_rules', $where );

        return $rows ? (array) $rows[0] : null;
    }

    /**
     * @param $price_rule_id
     * @return string
     * @throws Exception
     */
    static function get_price_rule_delete_btn( $price_rule_id ) {

        $button = html_element( 'Delete', 'button', '', [
            'type' => 'submit',
        ]);

        return html_element( $button, 'form', '', [
            'class' => 'ps-price-rule-insert',
            'data-nonce' => Ajax::get_global_nonce(),
            'data-url' => AJAX_URL . '?__route__=price-rule-update',
            'data-delete-id' => (int) $price_rule_id,
            'data-price-rule-action' => 'delete',
        ] );
    }

    /**
     * @param $in
     * @param $is_pct
     * @return string
     */
    static function format_pct_or_flat_rate( $in, $is_pct ) {

        // if the value is invalid make sure to still display it so
        // the admin user can see that it's likely invalid.
        if ( $in && ! Product_Sync::check_decimal_str( $in )[0] ) {
            return gp_test_input( $in );
        }

        if ( $in ) {
            if ( $is_pct ) {
                if ( $in >= 0 ) {
                    return gp_test_input( "+$in %" );
                } else {
                    // $in should already start with -
                    return gp_test_input( "$in %" );
                }
            } else {
                if ( $in >= 0 ) {
                    return gp_test_input( '+ $' . $in );
                } else {
                    // $in should already start with -
                    return gp_test_input( '- $' . abs( $in ) );
                }
            }

        } else {
            return '';
        }
    }

    /**
     * @param $price_rules
     * @return array
     * @throws Exception
     */
    static function format_price_rules_for_html_table( $price_rules ){
        return array_map( function( $rule ) {
            $rule_type = gp_test_input( $rule['rule_type'] );
            return [
                'type' => gp_test_input( $rule['type'] ),
                'locale' => gp_test_input( $rule['locale'] ),
                'supplier' => gp_test_input( $rule['supplier'] ),
                'brand' => gp_test_input( $rule['brand'] ),
                'model' => gp_test_input( $rule['model'] ),
                'rule_type' => gp_if_set( self::get_rule_type_options(), $rule_type, $rule_type ),
                'msrp_pct' => self::format_pct_or_flat_rate( $rule['msrp_pct'], true ),
                'msrp_flat' => self::format_pct_or_flat_rate( $rule['msrp_flat'], false ),
                'cost_pct' => self::format_pct_or_flat_rate( $rule['cost_pct'], true ),
                'cost_flat' => self::format_pct_or_flat_rate( $rule['cost_flat'], false ),
                'map_pct' => self::format_pct_or_flat_rate( $rule['map_pct'], true ),
                'map_flat' => self::format_pct_or_flat_rate( $rule['map_flat'], false ),
                'delete' => self::get_price_rule_delete_btn( $rule['id'] ),
            ];
        }, $price_rules );
    }

    /**
     * @return string[]
     */
    static function get_rule_type_options(){
        return [
            'msrp' => 'MSRP (with MAP enforced)',
            'cost' => 'Cost (with MAP enforced)',
            'map_msrp' => 'MAP (with MSRP fallback)',
            'map_cost' => 'MAP (with Cost fallback)',
        ];
    }

    /**
     * @return string
     */
    static function get_rule_type_select(){

        $items = array_merge( [
            '' => "None",
        ], self::get_rule_type_options() );

        return html_element( get_select_options([
            'items' => $items,
        ]), 'select', '', [
            'style' => 'min-width: 80px;',
            'name' => 'rule_type',
        ]);
    }

    /**
     * For entity type 'supplier' you can still pass in brands, which will contain product counts,
     * which will iterate over to get the total product count for the supplier.
     *
     * @param $type
     * @param $locale
     * @param $supplier
     * @param string $entity_type
     * @param array $entities
     * @return string
     * @throws Exception
     */
    static function get_insert_price_rule_form_table( $type, $locale, $supplier, $entity_type = 'supplier', $entities = [] ) {

        if ( $entity_type === 'model' ) {

            $options = [
                '' => '-- Choose Model --',
            ];

            foreach ( $entities as $entity ) {
                $name = implode( ", ", [ $entity['brand_name'], $entity['model_name'] ] );
                $model_key = implode( '##', [ $entity['brand_slug'], $entity['model_slug'] ] );
                $options[$model_key] = $name . ' (' . (int) $entity['count_products'] . ')';
            }

            $entity_title = "Brand (# Products)";

            $entity_select = get_form_select( [
                'name' => 'brand_model',
            ], [
                'items' => $options,
            ] );

        } else if ( $entity_type === 'brand' ) {

            $options = [
                '' => '-- Choose Brand --',
            ];

            foreach ( $entities as $entity ) {
                $options[$entity['brand_slug']] = $entity['brand_name'] . ' (' . (int) $entity['count_products'] . ')';
            }

            $entity_title = "Brand (# Products)";

            $entity_select = get_form_select( [
                'name' => 'brand',
            ], [
                'items' => $options,
            ] );

        } else {

            $count = 0;
            foreach ( $entities as $ent ) {
                $count+= (int) @$ent['count_products'];
            }

            $entity_title = "Supplier (# Products)";
            $entity_select = gp_test_input( $supplier ) . ' (' . $count . ')';
        }

        $submit = html_element( 'Insert', 'button', '', [
            'class' => 'test',
            'type' => 'submit',
        ] );

        $table_row = [
            $entity_title => $entity_select,
            'rule_type' => self::get_rule_type_select(),
            'msrp_pct' => self::pct_input( 'msrp_pct', '' ),
            'msrp_flat' => self::pct_input( 'msrp_flat', '' ),
            'cost_pct' => self::pct_input( 'cost_pct', '' ),
            'cost_flat' => self::pct_input( 'cost_flat', '' ),
            'map_pct' => self::pct_input( 'map_pct', '' ),
            'map_flat' => self::pct_input( 'map_flat', '' ),
            'submit' => $submit,
        ];

        $table = ui::render_table( null, [ $table_row ], [
            'title' => 'Insert/Update',
            'add_class' => 'hide-csv-controls',
            'add_count' => false,
            'sanitize' => false,
        ] );

        return html_element( $table, 'form', '', [
            'class' => 'ps-price-rule-insert',
            'data-nonce' => Ajax::get_global_nonce(),
            'data-url' => AJAX_URL . '?__route__=price_rules',
            'data-type' => $type,
            'data-locale' => $locale,
            'data-supplier' => $supplier,
            'data-entity-type' => gp_test_input( $entity_type ),
        ] );
    }

    /**
     * @param $supplier
     * @param $type
     * @param $locale
     */
    static function render_single( $supplier, $type, $locale ) {
        require CORE_DIR . '/classes/product-sync/views/supplier-price-rules.php';
    }
}
