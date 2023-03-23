<?php

class Product_Sync_Fetch{

    /**
     * Not to be confused with Product_Sync_Row->source
     *
     * @var FTP_Get_Csv|null
     */
    public $source;

    /**
     * Global errors with fetching data.
     *
     * @var array|mixed
     */
    public $errors = [];

    /**
     * @var array<Product_Sync_Row>
     */
    public $rows = [];

    /**
     * @var array
     */
    public $columns = [];

    /**
     * Printed in admin UI. Could contain time tracking data,
     * or pretty much whatever. Just avoid passwords, etc.
     *
     * @var array|mixed
     */
    public $debug = [];

    function __construct( $props ){
        $this->source = @$props['source'] ? $props['source'] : null;
        $this->rows = @$props['rows'] ? $props['rows'] : [];
        $this->columns = @$props['columns'] ? $props['columns'] : [];
        $this->errors = @$props['errors'] ? $props['errors'] : [];
        $this->debug = @$props['debug'] ? $props['debug'] : [];
    }

    /**
     * @return array
     */
    function get_source_rows(){
        return array_map( function( $row ) {
            return $row->source;
        }, $this->rows );
    }

    /**
     * Array of products with keys from Product_Sync::build_product,
     * and some additional array keys starting with __.
     */
    public function to_product_array(){
        return array_map( function( $row ) {
            /** @var Product_Sync_Row $row */
            $ret = $row->product;
            $ret['__source'] = $row->source;
            $ret['__errors'] = $row->get_all_errors();
            return $ret;
        }, $this->rows );
    }

    /**
     * Pass $ex_products because it requires a very expensive query, and you might also
     * need it after calling this function.
     *
     * @param $type
     * @param $ex_products
     * @return array[]
     */
    public function to_product_array_with_diffs( $type, $ex_products ){

        $products = $this->to_product_array();

        if ( $type === 'tires' ) {
            return Product_Sync_Compare::compare_tires( $products, $ex_products );
        } else {
            return Product_Sync_Compare::compare_rims( $products, $ex_products );
        }
    }

    /**
     * @param string $filter
     * @return array
     */
    function get_products( $filter = '' ){

        return Product_Sync::reduce( $this->rows, function( $row ) use( $filter ){

            if ( $filter === 'valid' ) {
                return $row->get_all_errors() ? false : $row->product;
            }

            if ( $filter === 'invalid' ) {
                return $row->get_all_errors() ? $row->product : false;
            }

            return $row->product;

        }, true );
    }

    /**
     * @param array $data
     */
    function set_source_rows( array $data ) {
        $this->rows = Product_Sync::reduce( $data, function( $source_row ) {
            $obj = new Product_Sync_Row();
            $obj->source = $source_row;
            return $obj;
        });
    }

    /**
     * Calls build product on each row of $this->rows, expecting that
     * the source has already been set. @see set_source_rows
     *
     * @param Product_Sync $sync
     * @throws Exception
     */
    function build_products( Product_Sync $sync ){

        $all_price_rules = Product_Sync_Compare::get_cached_indexed_price_rules();

        foreach ( $this->rows as $index => $row ) {
            /** @var Product_Sync_Row $row */

            list( $product, $errors ) = $sync->build_product_etc( $row->source, $all_price_rules );

            $this->rows[$index]->product = $product;
            $this->rows[$index]->validate_product_errors = $errors;
        }
    }

    /**
     * Call after build rows. $this->get_raw_data() might return nothing
     * after you call ->cleanup()
     */
    function cleanup(){
        if ( $this->source instanceof FTP_Get_Csv ) {
            $this->source->unlink();
        }
    }

    /**
     * @return false|string
     */
    function get_raw_data(){

        if ( $this->source instanceof FTP_Get_Csv ) {
            return @file_get_contents( $this->source->get_local_full_path() );
        }

        return '';
    }

    /**
     * @param $rows
     * @param $by
     * @param bool $errors
     * @param bool $product_meta
     * @return array
     */
    static function map_rows( $rows, $by, $errors = false, $product_meta = true ){
        return array_map( function( $row ) use( $by, $errors, $product_meta ){

            /** @var Product_Sync_Row $row */
            $product = $row->product;
            $row_errors = $row->get_all_errors();

            if ( ! $product_meta ) {
                unset( $product['__meta'] );
            }

            if ( $by === 'source' ) {
                return array_merge( [
                    '__error_count' => count( $row_errors ) ? count( $row_errors ) : '',
                ], $row->source );

            } else if ( $by === 'product' ) {
                $ret = $product;
            } else if ( $by === 'source_product' ) {
                $ret = array_merge( $row->source, [
                    '__result' => self::serialize_for_admin_table( $row->product )
                ]);
            } else if ( $by === 'product_source' ) {
                $ret = array_merge( $row->product, [
                    '__source' => self::serialize_for_admin_table( $row->source )
                ]);
            } else {
                assert(false, "Invalid map by" );
            }

            if ( $errors ) {
                $before = [
                    '__has_errors' => $row_errors ? "Yes" : "",
                ];
                $after = [
                    '__errors' => implode( ", ", $row_errors ),
                ];

                $ret = array_merge( $before, $ret, $after );
            }

            return $ret;

        }, $rows );
    }

    /**
     * @param $rows
     * @return array[]
     */
    static function filter_valid( $rows ){
        $valid = [];
        $invalid = [];

        /** @var Product_Sync_Row $row */
        foreach ( $rows as $row ) {
            if ( $row->get_all_errors() ) {
                $invalid[] = $row;
            } else{
                $valid[] = $row;
            }
        }

        return [ $valid, $invalid ];
    }

    /**
     * @param $product
     * @return string
     */
    static function serialize_for_admin_table( $product ) {
        $atts = [];
        foreach ( $product as $key => $val ) {

            if ( $key === '__meta' ) {
                continue;
            }

            $val = is_scalar( $val ) ? $val : json_encode( $val );
            $atts[] = "$key: $val";
        }

        return implode( ", ", $atts );
    }
}
