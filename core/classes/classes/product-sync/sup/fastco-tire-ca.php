<?php

/**
 * @see Product_Sync_Fastco
 */
class Product_Sync_Fastco_Tire_CA extends Product_Sync
{

    const KEY = 'fastco_tire_ca';
    const TYPE = 'tires';
    const SUPPLIER = 'fastco';
    const LOCALE = 'CA';

    const CRON_FETCH = true;
    const CRON_PRICES = true;
    const CRON_EMAIL = true;

    const FETCH_TYPE = 'api';

    /**
     * @param Time_Mem_Tracker $mem
     * @return array|false|mixed
     */
    function fetch_api(Time_Mem_Tracker $mem)
    {
        return Product_Sync_Fastco::get_tires_ca_data();
    }

    function get_admin_notes()
    {
        return ["Possibly ready to be synced. Please check data thoroughly before accepting all changes."];
    }

    /**
     * @param array $row
     * @return array
     */
    function build_product(array $row)
    {

        $errors = $this->check_source_columns($this->get_source_req_cols(), $row);

        $types = [
            'All Season' => 'all-season',
            'Winter' => 'winter',
            'Summer' => 'summer',
            'All Weather' => 'all-weather',
        ];

        $is_lt = strtolower(@$row['ServiceType']) === 'lt';

        $is_special = strtolower( $row['IsSpecial'] ) === 'true';

        // careful.. sometimes we see ".00" here, so we'll round before
        // checking if it is truthy. Note that round(".00", 2) happens
        // to equal 0 (or "0" ?), so its non-truthy.
        $special_price = round( $row['SpecialPrice'], 2 );

        return [
            'supplier' => $this::SUPPLIER,
            'locale' => $this::LOCALE,
            'upc' => '',
            'part_number' => @$row['PartNo'],
            'brand' => @$row['Brand'],
            'model' => @$row['Model'],
            'type' => @$types[$row['EnglishTireType']],
            'class' => $is_lt ? 'light-truck' : 'passenger',
            'category' => '',
            'image' => @$row['ImageURL'],
            'size' => @$row['SizeDescription'],
            'width' => @$row['Width'],
            'profile' => (int) @$row['Profile'],
            'diameter' => (int) @$row['WheelDiameter'],
            'load_index_1' => @$row['LoadRating'],
            'load_index_2' => '',
            'speed_rating' => self::parse_speed_rating(@$row['SpeedRating']),
            'is_zr' => strpos( strtolower( @$row['SizeDescription'] ), 'zr' ) !== false,
            'extra_load' => strtolower(@$row['LoadDescription']) === 'xl' ? 'XL' : '',
            'tire_sizing_system' => strtolower(@$row['ServiceType']) === 'lt' ? 'lt-metric' : 'metric',
            'map_price' => @$row['MAP'],
            'msrp' => @$row['RetailPrice'],
            'cost' => $is_special && $special_price ? $special_price : @$row['DealerCost'],
            'stock' => '',
            'discontinued' => '',
            '__meta' => [
                'errors' => $errors,
            ],
        ];
    }
}
