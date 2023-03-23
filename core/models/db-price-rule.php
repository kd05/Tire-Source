<?php

/**
 * Class DB_Order_Item
 */
Class DB_Price_Rule extends DB_Table {

    protected static $table = 'price_rules';
    protected static $primary_key = 'id';

    protected static $fields = array(
        'id',
        'type',
        'locale',
        'supplier',
        'brand',
        'model',
        'rule_type',
        'msrp_pct',
        'msrp_flat',
        'cost_pct',
        'cost_flat',
        'map_pct',
        'map_flat',
    );

    protected static $db_init_cols = array(
        'id' => 'int(11) unsigned NOT NULL auto_increment',
        'type' => 'varchar(255) default \'\'',
        'locale' => 'varchar(255) default \'\'',
        'supplier' => 'varchar(255) default \'\'',
        'brand' => 'varchar(255) default \'\'',
        'model' => 'varchar(255) default \'\'',
        'rule_type' => 'varchar(255) default \'\'',
        'msrp_pct' => 'varchar(255) default \'\'',
        'msrp_flat' => 'varchar(255) default \'\'',
        'cost_pct' => 'varchar(255) default \'\'',
        'cost_flat' => 'varchar(255) default \'\'',
        'map_pct' => 'varchar(255) default \'\'',
        'map_flat' => 'varchar(255) default \'\'',
    );

    protected static $db_init_args = array(
        'PRIMARY KEY (id)',
        // 'UNIQUE(type, locale, supplier, brand, model)'
    );

    /**
     * @param $in
     * @param $allow_empty
     * @return array
     */
    static function check_pct( $in, $allow_empty ) {

        if ( ! $allow_empty && ! $in ) {
            return [ false, "Value is empty" ];
        }

        list( $valid, $msg ) = Product_Sync::check_decimal_str( $in );
        return [ $valid, $msg ];
    }

    /**
     * @param $in
     * @param $allow_empty
     * @return array
     */
    static function check_flat_rate( $in, $allow_empty ) {

        if ( ! $allow_empty && ! $in ) {
            return [ false, "Value is empty" ];
        }

        list( $valid, $msg ) = Product_Sync::check_decimal_str( $in );
        return [ $valid, $msg ];
    }

    /**
     * Does not check for valid type, supplier, locale, etc.
     *
     * Checks only pct/flat rate fields and the type. We need to be able
     * to call this before inserting the price rule, so we'll create an instance
     * with some fields left blank.
     *
     * @return array
     */
    function validate(){

        $errors = [];

        if ( $this->get( 'rule_type' ) === 'msrp' ) {

            list( $pct_valid, $pct_error ) = self::check_pct( $this->get( 'msrp_pct' ), false );
            list( $flat_valid, $flat_error ) = self::check_flat_rate( $this->get( 'msrp_flat' ), true );

            if ( ! $pct_valid ) {
                $errors[] = "MSRP % is not valid.";
            }

            if ( ! $flat_valid ) {
                $errors[] = "MSRP flat rate is not valid.";
            }
        }

        if ( $this->get( 'rule_type' ) === 'cost' ) {

            list( $pct_valid, $pct_error ) = self::check_pct( $this->get( 'cost_pct' ), false );
            list( $flat_valid, $flat_error ) = self::check_flat_rate( $this->get( 'cost_flat' ), true );

            if ( ! $pct_valid ) {
                $errors[] = "Cost % is not valid.";
            }

            if ( ! $flat_valid ) {
                $errors[] = "Cost flat rate is not valid.";
            }
        }

        if ( $this->get( 'rule_type' ) === 'map_cost' ) {

            list( $pct_valid, $pct_error ) = self::check_pct( $this->get( 'map_pct' ), false );
            list( $flat_valid, $flat_error ) = self::check_flat_rate( $this->get( 'map_flat' ), true );

            if ( ! $pct_valid ) {
                $errors[] = "MAP % is not valid.";
            }

            if ( ! $flat_valid ) {
                $errors[] = "MAP flat rate is not valid.";
            }
        }

        return $errors;
    }
}

