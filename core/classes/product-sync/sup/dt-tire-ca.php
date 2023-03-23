<?php

class Product_Sync_DT_Tire_CA extends Product_Sync {

    const KEY = 'dt_tire_ca';
    const TYPE = 'tires';
    const SUPPLIER = 'dt';
    const LOCALE = 'CA';

    const CRON_FETCH = true;
    const CRON_PRICES = true;
    const CRON_EMAIL = false;

    /**
     * @return FTP_Get_Csv|null
     */
    function get_ftp_obj() {
        $ftp = new FTP_Get_Csv();
        $ftp->method = 'sftp';
        $ftp->host = self::FTP_SERVER;
        $ftp->username = 'u95793629-dt-tire';
        $ftp->password = self::get_credentials()['dt'];
        $ftp->remote_file_name = "Production/From_DTTire/Catalog Update.csv";
        return $ftp;
    }

    /**
     * @return array|string[]
     */
    function get_admin_notes(){
        return [
            "File should not be synced yet. Waiting on images from the supplier.."
        ];
    }

    /**
     * @return array
     */
    function get_source_req_cols(){
        return [
            'Size',
            'LoadRange',
            'SizePrefix',
            'AvailQty',
            'IsDiscontinued'
        ];
    }

    /**
     * @param array $row
     * @return array
     */
    function build_product( array $row ) {

        $errors = $this->check_source_columns( $this->get_source_req_cols(), $row );

        $load_index_arr = Product_Sync::parse_load_index( @$row['LoadIndex'] );

        $is_lt = strtolower( @$row['SizePrefix'] ) === 'lt';

        $brand = @$row[ 'Manufacturer' ];

        // i don't know what these are
        if ( string_ends_with( $brand, 'C/O' ) ) {
            $errors[] = "Ignoring Brands ending with C/O";
            // $brand = substr( $brand, 0, strlen( $brand ) - 3 );
        } else if ( string_ends_with( $brand, 'O/S' ) ) {
            $errors[] = "Ignoring Brands ending with O/S";
            // $errors[] = "Brand O/S";
            // $brand = substr( $brand, 0, strlen( $brand ) - 3 );
        }

        $msrp = @$row['MSRP'];
        $cost = @$row['Price'];

        // some products have 10000 here, i'm guessing that means something
        // other than the actual cost.
        if ( $msrp >= 9000 ) {
            $errors[] = "MSRP is too high (10000)";
        }

        if ( $cost >= 9000 ) {
            $errors[] = "Cost is too high (10000)";
        }

        $stock = @$row['AvailQty'];
        $discontinued = self::true_like_str( @$row['IsDiscontinued'] );

        if ( $stock == 0 && $discontinued ) {
            $errors[] = "Stock is 0 and discontinued";
        }

        return [
            'supplier' => $this::SUPPLIER,
            'locale' => $this::LOCALE,
            'upc' => '',
            'part_number' => @$row[ 'SKU' ],
            'brand' => $brand,
            'model' => @$row[ 'Model' ],
            'type' => self::true_like_str( @$row['IsWinterTire'] ) ? 'winter' : 'all-season',
            'class' => $is_lt ? 'light-truck' : 'passenger',
            'category' => '',
            'image' => '',
            'size' => @$row[ 'Size' ],
            'width' => @$row[ 'SectionWidth' ],
            'profile' => @$row[ 'AspectRatio' ],
            'diameter' => @$row[ 'RimSize' ],
            'load_index_1' => $load_index_arr[0],
            'load_index_2' => $load_index_arr[1],
            'speed_rating' => self::parse_speed_rating( @$row['SpeedRating'] ),
            'is_zr' => self::is_zr_size( @$row['Size'] ),
            'extra_load' => self::is_extra_load( @$row['LoadRange']) ? 'XL' : '',
            'tire_sizing_system' => $is_lt ? 'lt-metric' : 'metric',
            'map_price' => '',
            'msrp' => $msrp,
            'cost' => $cost,
            'stock' => $stock,
            'discontinued' => $discontinued,
            '__meta' => [
                'dont_sell' => $cost < 50,
                'errors' => $errors,
            ],
        ];
    }
}
