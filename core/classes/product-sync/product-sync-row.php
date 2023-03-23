<?php

class Product_Sync_Row{

    /**
     * Ie raw CSV data (single row)
     * @var
     */
    public $source = [];

    /**
     * Result of Product_Sync::build_product()
     *
     * @var
     */
    public $product = [];

    /**
     * Errors from Product_Sync::validate_product(), which
     * accepts $this->product as a parameter.
     *
     * @var array
     */
    public $validate_product_errors = [];

    /**
     * Errors added in the build_product function which is unique
     * to each supplier/file.
     *
     * @return array|mixed
     */
    public function get_source_errors(){

        if ( isset( $self->product['__meta']['errors'] ) ) {
            return $self->product['__meta']['errors'];
        }

        return [];
    }

    public function get_all_errors() {
        return array_merge( $this->get_source_errors(), $this->validate_product_errors );
    }
}