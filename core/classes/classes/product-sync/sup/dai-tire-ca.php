<?php


class Product_Sync_DAI_Tire_CA extends Product_Sync {

    const KEY = 'dai_tire_ca';
    const TYPE = 'tires';
    const SUPPLIER = 'dai';
    const LOCALE = 'CA';

    const CRON_FETCH = true;
    const CRON_PRICES = true;

    /**
     * @return FTP_Get_Csv|null
     */
    function get_ftp_obj() {
        $ftp                   = new FTP_Get_Csv();
        $ftp->method           = 'sftp';
        $ftp->host             = self::FTP_SERVER;
        $ftp->username         = 'u95793629-dai';
        $ftp->password         = self::get_credentials()['dai'];
        $ftp->remote_file_name = 'DAI_tire_list.csv';
        return $ftp;
    }

    /**
     * @return array
     */
    function get_source_req_cols() {
        return [

        ];
    }

    /**
     * @param array $row
     * @return array
     */
    function build_product( array $row ) {

        $errors = $this->check_source_columns( $this->get_source_req_cols(), $row );

        // all seem to start with ' for some reason
        // '6921109015617
        $upc = trim( @$row['UPC'], '\'');

        $load_index_arr = self::parse_dai_load_indexes(@$row['LOAD DESCRIPTION']);
        $is_xl = strpos( @$row['LOAD DESCRIPTION'], 'XL' ) !== false;

        $tire_sizing_system = cw_match( strtolower( @$row['SERVICE TYPE'] ), [ 'im', 'lt' ], [ 'metric', 'lt-metric' ], false, '' );

        // all means all-season (confirmed)
        $type = cw_match( strtolower( @$row['SEASON'] ), [ 'winter', 'all', 'summer' ], [ 'winter', 'all-season', 'summer' ], true, '' );

        $width = (int) @$row[ 'WIDTH' ];
        $profile = (int) @$row[ 'PROFILE' ];
        $diameter = (int) @$row[ 'WHEEL DIAMETER' ];

        $is_zr = strtolower( @$row['CONSTRUCTION'] ) === 'zr';

        $size_r = $is_zr ? 'ZR' : 'R';
        $size = $width && $profile && $diameter ? "$width/$profile{$size_r}{$diameter}" : '';

        $discontinued = self::true_like_str( @$row['DISCONTINUED'] );
        $stock = @$row['Stock'];

        if ( $stock == 0 && $discontinued ) {
            $errors[] = "Stock 0 and discontinued.";
        }

        return [
            'supplier' => $this::SUPPLIER,
            'locale' => $this::LOCALE,
            'upc' => $upc,
            'part_number' => @$row[ 'CODE' ],
            'brand' => @$row[ 'BRAND' ],
            'model' => @$row[ 'MODEL' ],
            'type' => $type,
            'class' => $tire_sizing_system === 'lt-metric' ? 'light-truck' : 'passenger',
            'category' => '',
            'image' => @$row['IMAGE'],
            'size' => $size,
            'width' => $width,
            'profile' => $profile,
            'diameter' => $diameter,
            'load_index_1' => $load_index_arr[0],
            'load_index_2' => $load_index_arr[1],
            'speed_rating' => self::parse_speed_rating( @$row[ 'SPEED RATING' ] ),
            'is_zr' => $is_zr,
            'extra_load' => $is_xl ? 'XL' : '',
            'tire_sizing_system' => $tire_sizing_system,
            'map_price' => @$row['MAP'],
            'msrp' => @$row['MSRP'],
            'cost' => @$row['PRICE'],
            'stock' => $stock,
            'discontinued' => $discontinued,
            '__meta' => [
                'errors' => $errors,
            ],
        ];
    }
}
