<?php
/**
 * Its possible this file is NOT included.
 *
 * You can php -a and include it manually.
 *
 * @throws Exception
 */

/**
 * Contains functions for inserting data into empty
 * database tables. Only used for launching in a new environment,
 * from an empty database.
 *
 * USE WITH CAUTION. DO NOT USE IN PRODUCTION unless you really know
 * what you are doing.
 *
 * Class CW_DB_Seeders
 */
Class CW_DB_Seeders{

    public static function regions(){

        $d = array();

        $d[] = [ 'country_code' => 'CA', 'province_code' => 'AB', 'province_name' => 'Alberta', ];
        $d[] = [ 'country_code' => 'CA', 'province_code' => 'BC', 'province_name' => 'British Columbia', ];
        $d[] = [ 'country_code' => 'CA', 'province_code' => 'MB', 'province_name' => 'Manitoba', ];
        $d[] = [ 'country_code' => 'CA', 'province_code' => 'NB', 'province_name' => 'New Brunswick', ];
        $d[] = [ 'country_code' => 'CA', 'province_code' => 'NL', 'province_name' => 'Newfoundland and Labrador', ];
        $d[] = [ 'country_code' => 'CA', 'province_code' => 'NS', 'province_name' => 'Nova Scotia', ];
        $d[] = [ 'country_code' => 'CA', 'province_code' => 'NT', 'province_name' => 'Northwest Territories', ];
        $d[] = [ 'country_code' => 'CA', 'province_code' => 'NU', 'province_name' => 'Nunavut', ];
        $d[] = [ 'country_code' => 'CA', 'province_code' => 'ON', 'province_name' => 'Ontario', ];
        $d[] = [ 'country_code' => 'CA', 'province_code' => 'PE', 'province_name' => 'Prince Edward Island', ];
        $d[] = [ 'country_code' => 'CA', 'province_code' => 'QC', 'province_name' => 'Quebec', ];
        $d[] = [ 'country_code' => 'CA', 'province_code' => 'SK', 'province_name' => 'Saskatchewan', ];
        $d[] = [ 'country_code' => 'CA', 'province_code' => 'YT', 'province_name' => 'Yukon', ];


        $d[] = [
            'country_code' => 'US',
            'province_code' => 'AL',
            'province_name' => 'Alabama',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'AZ',
            'province_name' => 'Arizona',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'AR',
            'province_name' => 'Arkansas',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'CA',
            'province_name' => 'California',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'CO',
            'province_name' => 'Colorado',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'CT',
            'province_name' => 'Connecticut',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'DE',
            'province_name' => 'Delaware',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'FL',
            'province_name' => 'Florida',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'GA',
            'province_name' => 'Georgia',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'ID',
            'province_name' => 'Idaho',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'IL',
            'province_name' => 'Idaho',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'IN',
            'province_name' => 'Indiana',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'IA',
            'province_name' => 'Iowa',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'KS',
            'province_name' => 'Kansas',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'KY',
            'province_name' => 'Kentucky',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'LA',
            'province_name' => 'Louisiana',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'ME',
            'province_name' => 'Maine',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'MD',
            'province_name' => 'Maryland',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'MA',
            'province_name' => 'Massachusetts',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'MI',
            'province_name' => 'Michigan',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'MN',
            'province_name' => 'Minnesota',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'MS',
            'province_name' => 'Mississippi',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'MO',
            'province_name' => 'Missouri',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'MT',
            'province_name' => 'Montana',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'NE',
            'province_name' => 'Nebraska',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'NV',
            'province_name' => 'Nevada',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'NH',
            'province_name' => 'New Hampshire',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'NJ',
            'province_name' => 'New Jersey',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'NM',
            'province_name' => 'New Mexico',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'NY',
            'province_name' => 'New York',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'NC',
            'province_name' => 'North Carolina',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'ND',
            'province_name' => 'North Dakota',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'OH',
            'province_name' => 'Ohio',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'OK',
            'province_name' => 'Oklahoma',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'OR',
            'province_name' => 'Oregon',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'PA',
            'province_name' => 'Pennsylvania',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'RI',
            'province_name' => 'Rhode Island',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'SC',
            'province_name' => 'South Carolina',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'SD',
            'province_name' => 'South Dakota',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'TN',
            'province_name' => 'Tennessee',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'TX',
            'province_name' => 'Texas',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'UT',
            'province_name' => 'Utah',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'VT',
            'province_name' => 'Vermont',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'VA',
            'province_name' => 'Virginia',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'WA',
            'province_name' => 'Washington',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'WV',
            'province_name' => 'West Virginia',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'WI',
            'province_name' => 'Wisconsin',
        ];

        $d[] = [
            'country_code' => 'US',
            'province_code' => 'WY',
            'province_name' => 'Wyoming',
        ];

        $db = get_database_instance();

        foreach ( $d as $array ) {
            $db->insert( DB_regions, $array );
            echo '<pre>' . print_r( $array, true ) . '</pre>';
        }
    }

    public static function tax_rates(){

        $db = get_database_instance();

        $regions = $db->get_results( "SELECT * FROM regions;" );

        foreach ( $regions as $region ) {

            // not real tax rates, only for dev environment.
            $rate = $region->country_code === "US" ? 0 : 13;

            $update = [
                'region_id' => $region->region_id,
                'tax_rate' => $rate,
            ];

            print_r( $update );

            $db->insert( "tax_rates", $update, [
                'tax_rate_id' => '%d',
                'region_id' => '%d',
                'tax_rate' => '%s',
            ] );

        }

    }

    // make sure there are regions first
    public static function shipping_rates(){

        $regions = $db->get_results( "SELECT * FROM regions;" );

        foreach ( $regions as $region ) {

            $update = [
                'region_id' => $region->region_id,
                'price_tire' => 0,
                'price_rim' => 0,
                'price_mounted' => 0,
                'allow_shipping' => 1,
            ];

            print_r( $update );

            $db->insert( DB_shipping_rates, $update, [
                'region_id' => '%d',
                'price_tire' => '%d',
                'price_rim' => '%d',
                'price_mounted' => '%d',
                'allow_shipping' => '%d',
            ] );

        }
    }
}