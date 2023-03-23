<?php

/**
 * The robert thibert CSV file has both canadian and U.S. warehouses in it and is
 * of a very particular format. Therefore, define a function and pass in the warehouses to look for,
 * and this will return data in the format we need.
 *
 * Sites/warehouses in robert thibert CSV as of jan 29 2019: CAL, EDM, MCT, MTL, NY, TOR, VAN, WIN
 * Statuses: Limited, Back ordered, Available (case sensitive)
 *
 * Class SIS_Robert_Thibert_Methods
 */
Abstract Class SIS_Robert_Thibert_Methods extends Supplier_Inventory_Supplier {

    public $warehouses = array();

    /**
     * Store this as $this->csv, and then pass $this->csv->array into
     * $this->process_robert_thibert_file()
     */
    public function get_robert_thibert_csv_object() {

        $csv = new CSV_To_Array( $this->ftp->get_local_full_path(), array(
            'part_number' => 'PartNumber',
            'qty' => 'Qty',
            'status' => 'Status',
            'site' => 'Site',
            'is_primary_site' => 'isPrimarySite',
        ) );

        return $csv;
    }

    /**
     * @return string
     */
    public function get_admin_info_extra_column() {
        return 'Warehouses: ' . implode_comma( $this->warehouses );
    }

    /**
     * Note: $warehouses is the "Site" column in the CSV.
     *
     * Currently, the options are: CAL, EDM, MCT, MTL, NY, TOR, VAN, WIN
     *
     * Note: you should still send the array through self::array_map_and_filter()
     * afterwards.
     *
     * @param $warehouses
     */
    public function process_robert_thibert_csv_array( $array ) {

        $ret = array();

        // Collect stock amounts from warehouses which are listed
        // in separate rows.
        // Have to be careful here, a stock level of zero for a part number
        // is very different than not defined, and we have both canadian and u.s.
        // products in the same CSV file. Therefore, we must ONLY include part numbers
        // in the return array if they are listed in the file AND in one of the $warehouses
        // passed in.
        array_map( function ( $row ) use ( &$ret ) {

            $site = trim( $row[ 'site' ] );

            // ignore $row if $row['site'] is not in $sites.
            $continue = false;

            // warehouses and sites mean the same thing basically
            foreach ( $this->warehouses as $_site ) {
                if ( CSV_To_Array::col_name_matches( $_site, $site ) ) {
                    $continue = true;
                    break;
                }
            }

            if ( ! $continue ) {
                return;
            }

            // trim conversion to string may actually convert 083834 to "83834" though i'm not sure.
            // later on there is logic to take care of leading zeros anyways. However,
            // I recommend not removing trim on the basis that it seems redundant.
            $pn = trim( $row[ 'part_number' ] );

            if ( ! $pn ) {
                return null;
            }

            // init array index to add/subtract stock for a given part number
            if ( ! isset( $ret[ $pn ] ) ) {
                $ret[ $pn ]                  = array();
                $ret[ $pn ][ 'part_number' ] = $pn;
                $ret[ $pn ][ 'stock' ]       = 0;
            }

            // It seems that when $row['status'] == 'Available', $row['qty'] is always 50
            // so, which I think means 50+ basically. In my opinion, we can just let this be 50
            if ( CSV_To_Array::col_name_matches( $row[ 'status' ], 'Limited' ) || CSV_To_Array::col_name_matches( $row[ 'status' ], 'Available' ) ) {
                $ret[ $pn ][ 'stock' ] += self::convert_qty_value_to_int( $row[ 'qty' ] );
            }

            // Most of the time, qty is zero here. If we had to remove this, it would probably
            // be ok? But.. i mean the data is there, I think it only makes sense to subtract it
            if ( CSV_To_Array::col_name_matches( $row[ 'status' ], 'Back ordered' ) ) {
                $ret[ $pn ][ 'stock' ] -= self::convert_qty_value_to_int( $row[ 'qty' ] );
            }

        }, $array );

        // after possibly subtracting back ordered quantities, we could be at a number less than zero
        // make this zero instead. Note that we cannot do this in the above loop. For example,
        // the result of 3 negative quantities followed by a positive quantity would be positive,
        // even though it most likely should not be.
        $ret = array_map( function ( $_ret ) {
            if ( $_ret[ 'stock' ] < 0 ) {
                $_ret[ 'stock' ] = 0;
            }

            return $_ret;
        }, $ret );

        // remove part number indexes.
        $ret = array_values( $ret );

        return $ret;
    }

    /**
     * Child classes will probably want to do this, therefore, call this
     * function in $child->prepare_for_import() if you wish.
     */
    public function robert_thibert_parent_class_prepare_for_import() {

        // this one file is misbehaving badly, going to try to figure out why
        $debug = [];

        $process_id = time();

        /**
         * No longer need to log stuff, but I will keep the logic below
         * so that we can turn it back on in the future if needed.
         */
        $do_log = ! IN_PRODUCTION;

        // log over and over because evidently, we're sometimes not reaching the end of this function
        $log = function ( $data, $counter ) use ( $do_log, $process_id ) {
            if ( $do_log ) {
                $data[ 'counter' ]  = $counter;
                $data[ 'time_mem' ] = get_time_and_mem_usage();
                $name               = "robert-thibert-inv-debug-$process_id";
                $string             = print_r( $data, true ) . "\r\n\r\n";
                $path               = LOG_DIR . "/$name.log";
                file_put_contents( $path, $string, FILE_APPEND );
                // log_data( $data, $name, true, true, true );
            }
        };

        $this->ftp->run();

        $path = $this->ftp->get_local_full_path();

        $debug[ 'ftp' ] = [
            'ftp_login_success' => $this->ftp->ftp_login_success,
            'time_in_seconds' => $this->ftp->time_in_seconds,
            'local_file_size' => $this->ftp->local_file_size,
            'local_file_exists' => $this->ftp->local_file_exists,
            'path' => $path,
            'path_exists' => file_exists( $path ),
            'filesize' => file_exists( $path ) ? filesize( $path ) : '..',
        ];

        // note: process for rt u.s. is getting to this step but never to step 2
        $log( $debug, 1 );

        $this->csv = $this->get_robert_thibert_csv_object();

        //		if ( $do_log ) {
        //		}

        $debug[ 'csv' ] = $this->csv->get_debug_array();

        $log( $debug, 2 );

        $array = $this->process_robert_thibert_csv_array( $this->csv->array );

        //		if ( $do_log ) {
        //		}

        $count  = $array ? count( $array ) : 0;
        $first  = gp_if_set( $array, 0 );
        $second = gp_if_set( $array, 1 );

        $debug[ 'array' ] = [
            'count' => $count,
            'first' => $first,
            'second' => $second,
        ];

        $log( $debug, 3 );

        // now do the other standard filtering
        $this->array = self::array_map_and_filter( $array );

        // note: on 2 separate attempts, the file processed exactly 949 items with database updates to products.
        // i have a feeling there is some sort of data corruption, ie. around row 950 - though not sure print_r() could even catch that
        if ( $do_log ) {
            file_put_contents( LOG_DIR . "/rt-full-array-final-$process_id.txt", print_r( $this->array, true ) );
        }

        // some files are producing no result, lets not delete those ones right now..
        if ( $this->array ) {
            // delete local copy of file
            $unlink = $this->ftp->unlink();
            // i know this is failing or else were sometimes not getting to here
            $debug[ 'unlink' ] = get_var_dump( $unlink );
        }

        $_count  = $this->array ? count( $this->array ) : 0;
        $_first  = gp_if_set( $this->array, 0 );
        $_second = gp_if_set( $this->array, 1 );

        $debug[ 'array_after' ] = [
            '_count' => $_count,
            '_first' => $_first,
            '_second' => $_second,
        ];

        $log( $debug, 4 );
    }
}