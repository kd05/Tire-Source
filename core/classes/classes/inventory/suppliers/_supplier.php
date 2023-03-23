<?php

/**
 * Superclass for supplier inventory imports... the sub classes will
 * have the methods necessary to generate inventory data (ie. via ftp/csv).
 *
 * In addition to simply running the imports there is a lot of admin pages
 * that display information about the imports, much of that logic depends
 * on what is in this class.
 *
 * This object is passed into Supplier_Inventory_Import after calling $this->prepare_for_import()
 *
 * @see Supplier_Inventory_Import
 * @see Supplier_Inventory_Hash
 * @see FTP_Get_Csv
 * @see CSV_To_Array
 *
 * Class Supplier_Get_Inventory_Data
 */
Abstract Class Supplier_Inventory_Supplier {

    /**
     * Each sub class generates this array, ie. via ftp/csv or whatever.
     *
     * Example: array( [ 'part_number' => 123, 'stock' => 12 ], [ 'part_number' => 1234, 'stock' => 0 ] );
     *
     * @var array
     */
    public $array = [];

    /**
     * Probably "tires" or "rims",
     *
     * Maybe "universal"... might not support this however.
     *
     * @var
     */
    public $type;

    /**
     * The supplier slugs that the import is allowed to modify...
     *
     * If a supplier specifies an inventory value for some part number
     * that has a different supplier in the database, we don't want to
     * be updating that part number. This is a problem because a lot of
     * suppliers have far more items in their inventory data than products
     * in our database.
     *
     * Unsure if more than 1 will be supported.
     *
     * @var array
     */
    public $allowed_suppliers = [];

    /**
     * When true, the database should not get updated with inventory
     * resulting from this class. It is used only to retrieve inventory
     * for sending to Amazon. Most of the time, this is false.
     *
     * @var bool
     */
    const AMAZON_ONLY = false;

    /**
     * APP_LOCALE_CANADA or APP_LOCALE_US ('CA' or 'US')
     *
     * @var
     */
    public $locale;

    /**
     * A unique ID sort of thing that each sub class must define.
     *
     * It's called a hash key because we store the hash of the
     * data in the options table as a way to check if its identical
     * to when we ran it the last time, and if it is, not run it again
     * (more or less just an optimization).
     *
     * @see Supplier_Inventory_Hash
     */
    const HASH_KEY = '';

    /**
     * The FTP object holds ftp credentials but also has a run() method.
     *
     * Usually we'll setup the $ftp object in the constructor, but DEFINITELY don't call
     * ->run() until prepare_for_import()
     *
     * @var FTP_Get_Csv|null
     */
    public $ftp;

    /**
     * a separate FTP hosting account that does not contain the website,
     * but used strictly for the purposes of ... well ... file transfers.
     *
     * @var string
     */
    public static $our_own_ftp_server_host = '##removed';

    /**
     * The CSV_To_Array object which in most cases is not the data in the final form.
     *
     * The object will generally hold all rows of the CSV but only select columns,
     * and will rename those columns. After renaming columns, we often have to go
     * through another step of filtering the data to arrive at $this->array.
     *
     * Therefore, this object might not be needed in this class but we'll keep it
     * here anyways. Use $this->array, not $this->csv->array.
     *
     * @var CSV_To_Array|null
     */
    public $csv;

    /**
     * For most supplier this will be true.
     *
     * It means that if our database includes a part number that is not
     * found in the data we process, then that product should no longer be sold.
     *
     * We will not delete it from the database, because it may be included in the
     * next inventory import. Instead, set the columns "sold_in_ca" or "sold_in_us" to 0.
     *
     * The method of doing this will probably be selecting all tires/rims that
     * match the allowed suppliers but whose stock_update_id_ca, or stock_update_id_us
     * column is not the stock_update_id that was just created in the same script.
     *
     * UPDATE: defaulting this to TRUE!
     *
     * @var
     */
    public $mark_products_not_in_array_as_not_sold = true;

    /**
     * Setting this to false will make it not run unless its done manually.
     *
     * When AMAZON_ONLY is true, they will also not run, and its not necessary to
     * set this to false.
     *
     * @var bool
     */
    public $process_in_cron_job = true;

    /**
     * When the supplier provides an empty file, we can either mark all tires/rims
     * discontinued, or we can do nothing, leaving inventory as it was before we ran
     * the import.
     *
     * We seem to be getting empty files on rare occasions for suppliers where the file
     * is almost always not empty. I'm wondering if its because the file is being written
     * as we're trying to read it. In any case, the default behaviour now will have to be to
     * do nothing. This could be a problem if a supplier deletes a file and permanently stops
     * uploading it, because all inventory for that supplier will always remain the same. We'll
     * have to just cross that bridge when we get there.
     *
     * @var int
     */
    public $if_file_empty_mark_products_not_sold = false;

    /**
     * This function probably gets a CSV file from FTP and should never be called
     * automatically like when constructing an object. Only call this right before
     * actually running an import.
     *
     * @return mixed
     */
    abstract public function prepare_for_import();

    /**
     * Save a log file containing part numbers and stock amounts.
     *
     * It's expected that you already called $this->prepare_for_import().
     *
     * The plan right now is to only log all the data when we run it
     * manually from the back-end.
     */
    public function save_log_file(){

        $filename = implode( "-", [
                'INV',
                static::HASH_KEY,
                time()
            ] ) . '.txt';

        // approximately, or exactly CSV format
        $contents = implode( "\r\n", array_map( function( $item ){

            $pn = @$item['part_number'];
            $stock = @$item['stock'];

            // might not be necessary to log the types but doing it anyways.
            $parts = [
                $pn,
                gettype( $pn ),
                $stock,
                gettype( $stock ),
            ];

            return implode( ",", $parts );
        }, $this->array ) );

        $path = LOG_DIR . '/' . $filename;

        $bytes = file_put_contents( $path, $contents, FILE_APPEND );
        chmod($path, 0755);

        return $bytes > 0;
    }

    /**
     * Class names in this array will be instantiated and then imported
     * via cron job and will also be able to be run manually.
     *
     * Warning: you might want to filter out instances with AMAZON_ONLY constant being true.
     */
    public static function get_all_supplier_class_names() {

        $ret = array();

        $ret[] = 'SIS_DT_Tire_Tires_Canada';
        $ret[] = 'SIS_CDA_Tire_Tires_Canada';
        $ret[] = 'SIS_CDA_Tire_Rims_Canada';
        $ret[] = 'SIS_DAI_Tires_Canada';
        $ret[] = 'SIS_Dynamic_Tire_Tires_CA';
        $ret[] = 'SIS_Vision_Rims_CA';
        $ret[] = 'SIS_Vision_Rims_US';
        $ret[] = 'SIS_DAI_Rims_Canada';
        $ret[] = 'SIS_DAI_Rims_US';
        $ret[] = 'SIS_The_Wheel_Group_Rims_Canada';
        $ret[] = 'SIS_The_Wheel_Group_Rims_US';
        $ret[] = 'SIS_Robert_Thibert_Rims_Canada';
        $ret[] = 'SIS_Robert_Thibert_Rims_US';

        $ret[] = 'SIS_Wheelpros_Tires_CA';
        $ret[] = 'SIS_Wheelpros_Rims_CA';

        $ret[] = 'SIS_Amazon_Robert_Thibert_CA';

        // nov 2022
        $ret[] = 'SIS_Fastco_Tires_CA';
        $ret[] = 'SIS_Fastco_Rims_CA';

        // DAI tires only for CA, not for US
        // $ret[] = 'SIS_DAI_Tires_US';

        return $ret;
    }

    /**
     * @param $step - 1, 2, 3, or 4
     * @return array
     */
    public static function get_cron_job_instances_via_step( $step ) {

        $step = (int) $step;

        $all = self::get_all_supplier_instances(function( $i ){

            /** @var Supplier_Inventory_Supplier $i */

            // WFL has no US products currently.
            if ( IS_WFL && $i->locale === APP_LOCALE_US ) {
                return false;
            }

            return $i->process_in_cron_job && ( $i::AMAZON_ONLY === false );
        });

        // if we include US keys on WFL, that will be fine (they won't be returned)
        $step_1_keys = [
            SIS_DAI_Tires_Canada::HASH_KEY,
            SIS_DAI_Tires_US::HASH_KEY,
            SIS_DAI_Rims_Canada::HASH_KEY,
            SIS_DAI_Rims_US::HASH_KEY,
        ];

        $step_2_keys = [
            SIS_Robert_Thibert_Rims_Canada::HASH_KEY,
            SIS_The_Wheel_Group_Rims_Canada::HASH_KEY,
            SIS_The_Wheel_Group_Rims_US::HASH_KEY,
            SIS_Vision_Rims_CA::HASH_KEY,
            SIS_Vision_Rims_US::HASH_KEY,
        ];

        $step_3_keys = [
            SIS_CDA_Tire_Tires_Canada::HASH_KEY,
            SIS_CDA_Tire_Rims_Canada::HASH_KEY,
            SIS_Fastco_Tires_CA::HASH_KEY,
            SIS_Fastco_Rims_CA::HASH_KEY,
        ];

        $step_keys_merged = array_merge( $step_1_keys, $step_2_keys, $step_3_keys );

        // ya its not the simplest way to accomplish this probably
        if ( $step === 1 ) {
            $ret = array_filter( $all, function( $instance ) use( $step_1_keys ){
                return in_array( $instance::HASH_KEY, $step_1_keys );
            } );
        } else if ( $step === 2 ) {
            $ret = array_filter( $all, function( $instance ) use( $step_2_keys ){
                return in_array( $instance::HASH_KEY, $step_2_keys );
            } );
        } else if ( $step === 3 ) {
            $ret = array_filter( $all, function( $instance ) use( $step_3_keys ){
                return in_array( $instance::HASH_KEY, $step_3_keys );
            } );
        } else if ( $step === 4 ) {
            $ret = array_filter( $all, function( $instance ) use( $step_keys_merged ){
                return ! in_array( $instance::HASH_KEY, $step_keys_merged );
            } );
        } else {
            // shouldn't get to here
            return [];
        }

        return array_values( $ret );
    }

    /**
     * @return string
     */
    public function get_admin_name() {

        $ret = static::HASH_KEY;

        if ( ! $this->process_in_cron_job ) {
            $ret .= ' (NOT_ACTIVE)';
        }

        return $ret;
    }

    /**
     * Given a locale, type, and supplier slug, see if a we have a supplier inventory object setup
     * to handle those parameters.
     *
     * @param $locale
     * @param $type
     * @param $supplier_slug
     * @return array|bool
     */
    public static function get_instances_with_filters( $locale, $type, $supplier_slug ) {

        $ret = array_filter( self::get_all_supplier_instances(), function ( $instance ) use ( $locale, $type, $supplier_slug ) {

            /** @var Supplier_Inventory_Supplier $instance */
            if ( $instance->locale == $locale ) {
                if ( $instance->type == $type ) {
                    if ( in_array( $supplier_slug, $instance->allowed_suppliers ) ) {
                        return true;
                    }
                }
            }

            return false;
        } );

        return $ret && is_array( $ret ) ? array_values( $ret ) : false;
    }

    /**
     * @param $key - also the unique ID
     *
     * @return mixed
     */
    public static function get_class_name_via_hash_key( $key ) {
        foreach ( self::get_all_supplier_class_names() as $cls ) {
            if ( constant( $cls . '::HASH_KEY' ) === $key ) {
                return $cls;
            }
        }
    }

    /**
     * @param $key
     * @return Supplier_Inventory_Supplier
     */
    public static function get_instance_via_hash_key( $key ) {
        $cls = self::get_class_name_via_hash_key( $key );

        if ( $cls ) {
            return new $cls();
        }
    }

    /**
     * A lot of columns are 24+ or 50+, simply convert those to integers.
     *
     * For now this function is redundant. If in the future we run into problems,
     * it will be beneficial to run all quantity values through the same function.
     *
     * @param $val
     * @return int
     */
    public static function convert_qty_value_to_int( $val ) {
        return (int) trim( str_replace( '+', '', $val ) );
    }

    /**
     * Return an array of instances of self that we want to process inventory for.
     *
     * On a cron job, we'll call this function, and run the import for each value
     * returned.
     *
     * You might want to filter out instances with AMAZON_ONLY constant being true.
     *
     * @param null $filter
     * @return array
     */
    public static function get_all_supplier_instances( $filter = null ) {
        $ret = array_map( function ( $cls ) {
            return new $cls();
        }, self::get_all_supplier_class_names() );

        if ( $filter ) {
            $ret = array_filter( $ret, $filter );
        }

        return $ret;
    }

    /**
     * @param array $instances
     * @param bool $with_hash_checking
     * @return stdClass
     */
    public static function run_selected_imports_via_instances( $instances = array(), $with_hash_checking = true ) {

        $ret = new stdClass();

        $ret->count     = count( $instances );
        $ret->skipped   = array();
        $ret->processed = array();

        /** @var Supplier_Inventory_Supplier $instance */
        foreach ( $instances as $instance ) {

            if ( DOING_CRON && ! $instance->process_in_cron_job ) {
                $result = false;
            } else if ( $with_hash_checking ) {
                $result = static::run_import_with_hash_checks( $instance );
            } else {
                $result = static::run_import_without_hash_checks( $instance );
            }

            if ( $result ) {
                $ret->processed[] = $instance->get_hash_key();
            } else {
                $ret->skipped[] = $instance->get_hash_key();
            }
        }

        return $ret;
    }

    /**
     * Hash checking means we don't run the import if the data in the import
     * was the same as it was the last time we ran it. We determine this
     * by hashing the data and storing it in the options table.
     *
     * 2 ways to call this:
     *
     * Supplier_Inventory_Import::run_import_with_hash_checking( new SIS_Dai_Rims() );
     *
     * SIS_Dai_Rims::run_import_with_hash_checking();
     *
     * @param null $instance
     *
     * @return bool|Supplier_Inventory_Import
     * @throws Exception
     */
    public static function run_import_with_hash_checks( $instance = null ) {

        // use new static() not new self()
        /** @var Supplier_Inventory_Supplier $self */
        $self = is_object( $instance ) ? $instance : new static();

        if ( DOING_CRON && ! $self->process_in_cron_job ) {
            return false;
        }

        $self->prepare_for_import();

        // note that $self->array is setup after we call ->prepare_for_import()
        if ( Supplier_Inventory_Hash::cmp_prev( $self->get_hash_key(), $self->array ) ) {

            // delete the file for real.. better to not keep all of these, I suppose.
            if ( $self->ftp ) {
                $self->ftp->unlink( true );
            }

            // log whenever we skip the import, because it's not otherwise in the database.
            log_data( [
                "supplier" => $self->get_hash_key(),
                "count" => count( $self->array ),
            ], "supp-inv-skip-due-to-same-file", true );

            return false;
        }

        $import = new Supplier_Inventory_Import( $self );
        $import->run();

        // even if errors, I think we should still update the hash value.
        // otherwise, its quite likely that errors will occur and we'll just continue to run the import
        // over and over again with the same errors every 30 minutes or however often we run the cron job.
        if ( $import->count_processed > 0 ) {
            Supplier_Inventory_Hash::update_hash( $self->get_hash_key(), Supplier_Inventory_Hash::make_hash( $self->array ) );
        }

        // if ( $import->was_successful() ) {}

        return $import;
    }

    /**
     * 2 ways to call this:
     *
     * Supplier_Inventory_Import::run_import_without_hash_checks( new SIS_Dai_Rims() );
     *
     * SIS_Dai_Rims::run_import_without_hash_checks();
     *
     * @param null $instance
     *
     * @return bool|Supplier_Inventory_Import
     */
    public static function run_import_without_hash_checks( $instance = null ) {

        // use new static() not new self()
        /** @var Supplier_Inventory_Supplier $self */
        $self = is_object( $instance ) ? $instance : new static();

        if ( DOING_CRON && ! $self->process_in_cron_job ) {
            return false;
        }

        $self->prepare_for_import();

        $import = new Supplier_Inventory_Import( $self );
        $import->run();

        return $import;
    }

    /**
     * The passed in array must have $row['part_number'] and $row['stock'] set.
     *
     * Will probably want to pass most arrays through this before returning them.
     *
     * @param $array
     * @return array
     */
    public static function array_map_and_filter( $array ) {

        if ( ! $array ) {
            return array();
        }

        // some suppliers put 24+ or 50+
        $array = array_map( function ( $row ) {

            $row[ 'part_number' ] = trim( $row[ 'part_number' ] );

            if ( ! $row[ 'part_number' ] ) {
                // array_filter after to remove these values.
                return null;
            }

            // sometimes part numbers are like 083812 even though opening the file
            // in excel does not show the leading zero.
            // note: this logic is probably repeated later on when we run the import..
//			if ( gp_is_integer( $row[ 'part_number' ] ) ) {
//				$row[ 'part_number' ] = ltrim( $row[ 'part_number' ], '0' );
//			}

            $row[ 'stock' ] = self::convert_qty_value_to_int( $row[ 'stock' ] );

            return $row;

        }, $array );

        // note that the array filter here does not remove items with qty of zero,
        // if it did that would be a huge mistake, but the first level array values
        // of array is either a non empty array or null
        // being a bit overkill on array_values again here but better safe than sorry.
        return array_values( array_filter( $array ) );
    }

    /**
     * FTP configuration for the file that's used for both tires and rims for
     * cda tire. (and maybe product sync to get prices now)
     */
    public static function cda_tire_universal_ftp_config() {

        $ftp                   = new FTP_Get_Csv();
        $ftp->method           = 'sftp';
        $ftp->host             = self::$our_own_ftp_server_host;
        $ftp->username         = 'u95793629-cda-tire';
        $ftp->password         = '##removed';
        $ftp->remote_file_name = '4167498473_stockinfo.csv';

        return $ftp;
    }

    /**
     * Simply fixes possible issues with non-uniform variable syntax on php < 7
     *
     * For example, we can't do $obj->supplier::HASH_KEY, so the workaround will
     * be a function to retrieve the constant.
     *
     * @return string
     */
    public function get_hash_key() {
        return $this::HASH_KEY;
    }

    /**
     * Must return the same as get_hash_key()...
     *
     * @return string
     */
    public static function get_hash_key_static() {
        return static::HASH_KEY;
    }
}
