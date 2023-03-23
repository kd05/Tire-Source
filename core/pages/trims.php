<?php
/**
 * Admin only page to display all data from vehicle data API
 */

if ( ! cw_is_admin_logged_in() ) {
    show_404( "Not Found" );
    exit;
}

Header::$title = "Trims (Admin Page)";
Header::$meta_robots = 'noindex';

has_no_top_image();

$make = make_slug( gp_test_input( @$_GET['make'] ) );
$model = make_slug( gp_test_input( @$_GET['model'] ) );
$year = make_slug( gp_test_input( @$_GET['year'] ) );
$trim = make_slug( gp_test_input( @$_GET['trim'] ) );

cw_get_header();

$section = function( $bool_show, callable $callable ) {
    if ( $bool_show ) {
        ?>
        <div class="general-content" style="margin: 30px 0;">
            <?= call_user_func( $callable ); ?>
        </div>
        <?php
    }
};

$error = function( $str ) use( $section ){
    $section( true, function() use( $str ){
        echo '<h3>Error...</h3>';
        echo "<p><strong>" . gp_test_input( $str ) . "</strong></p>";
    });
};

?>
    <div class="page-wrap trims-page">
        <div class="main-content">
            <div class="container">

                <div class="general-content">
                    <p>This page is a debugging tool which outputs data from the vehicle data API.</p>
                    <p>It is only available if you are logged in as an admin user.</p>
                    <p>If you are having trouble finding your data, you must find the correct slugs (lowercase string using dashes instead of spaces). You can do so by using the vehicle
                        make and model tool to search for tires/rims/packages, then look to the URL. You can use the same values as where it says ?make=...&model=...&year=...</p>
                </div>
                <br>
                <form class="form-style-1 width-md" action="" method="get">

                    <div class="form-items">
                        <?= get_form_input( [
                            'name' => 'year',
                            'label' => 'Year',
                            'value' => $year,
                            'split' => 2
                        ] ); ?>

                        <?= get_form_input( [
                            'name' => 'make',
                            'label' => 'Make',
                            'value' => $make,
                            'split' => 2
                        ] ); ?>

                        <?= get_form_input( [
                            'name' => 'model',
                            'label' => 'Model',
                            'value' => $model,
                            'split' => 2
                        ] ); ?>

                        <?= get_form_input( [
                            'name' => 'trim',
                            'label' => 'Trim (read-only)',
                            'value' => $trim,
                            'split' => 2,
                            'disabled' => true,
                        ] ); ?>

                        <?= get_form_submit( [] ); ?>
                    </div>

                </form>

                <?php

                $year_make_model = $year && $make && $model;

                if ( $year_make_model ) {

                    // this is a huge hack.
                    // unfortunately I never thought I would have to delete one cache item at a time.
                    // you are about to see some very questionable things which I would never do
                    // outside of a debugging scenario.
                    $delete_cache_key_in_a_bad_way = function( $cache_key ){
                        // in case we screw something up, set the new value equal to the old value.
                        if ( $ex_cache = gp_cache_get( $cache_key ) ) {
                            // update the cache so it expires 1 second ago.
                            gp_cache_set( $cache_key, $ex_cache, -1 );

                            // clear expired cache
                            gp_cache_clear_expired();
                        }
                    };

                    // I also copied this string right out of the get_trims() function.
                    $delete_cache_key_in_a_bad_way( 'api_trims_' . $make . '_' . $model . '_' . $year );

                    // now, we should be able to get the uncached trims data.
                    $filtered_trims_data = get_trims( $make, $model, $year );
                    $raw_trims_data = Wheel_Size_Api::get_trims( $make, $model, $year );

                    if ( ! $filtered_trims_data ) {
                        $error( "Trims data could not be found for given make, model, and year." );
                    }

                    if ( $trim ) {
                        $delete_cache_key_in_a_bad_way( 'api_fitment_' . implode( '_', array( $make, $model, $year, $trim ) ) );
                        $raw_fitment_data = Wheel_Size_Api::get_fitment_data( $make, $model, $year, $trim );
                        $filtered_fitment_data = get_fitment_data( $make, $model, $year, $trim );

                        if ( ! $filtered_fitment_data ) {
                            $error( "Fitment data could not be found for given make, model, year, and trim." );
                        }

                    } else {
                        $raw_fitment_data = $filtered_fitment_data = [];
                    }

                    // Trims - Filtered
                    $section( $year && $make && $model, function() use( $filtered_trims_data, $make, $model, $year, $trim ) {
                        ?>
                        <h2>Filtered Trims Data</h2>
                        <p>This is what shows up in the trims dropdown in the vehicle make and model search. It is derived from the raw data shown below. Many operations are done on the data first, including filtering by market, and handling the generation and options in the resulting name.</p>
                        <?= get_pre_print_r( $filtered_trims_data, true ); ?>
                        <?php
                    });


                    // Links To Fitments via Trims
                    $section( $year && $make && $model, function() use( $filtered_trims_data, $make, $model, $year, $trim ) {

                        $this_url_without_trim = cw_add_query_arg( array_filter( [
                            'make' => $make,
                            'model' => $model,
                            'year' => $year,
                        ]), get_url( 'trims' ) );

                        echo '<h3>Click on a trim to view fitment data</h3>';

                        echo '<ul class="inline">';

                        $links = [];
                        $links[] = html_link( $this_url_without_trim, "- No Trim -" );

                        foreach ( $filtered_trims_data as $slug => $name ) {
                            $links[] = html_link( cw_add_query_arg( [
                                'trim' => gp_test_input( $slug ),
                            ], $this_url_without_trim), gp_test_input( $name ) );
                        }

                        echo wrap_tag( implode( ", &nbsp;&nbsp;", $links ) );
                    });

                    // Links to Shop Products via Fitments
                    $section( (bool) $filtered_fitment_data, function() use( $make, $model, $year, $trim, $filtered_fitment_data ) {

                        $fitments = is_array( @$filtered_fitment_data['wheels'] ) ? $filtered_fitment_data['wheels'] : [];

                        $link = function( $pagename, $fitment_slug ) use( $make, $model, $year, $trim ){
                            return get_vehicle_archive_url( $pagename, [ $make, $model, $year, $trim, $fitment_slug ] );
                        };

                        $table_data = array_map( function( $fitment ) use( $link ){

                            $slug = gp_test_input( @$fitment['slug'] );

                            return [
                                'name' => gp_test_input( @$fitment['name'] ),
                                'tires' => html_link( $link( 'tires', $slug ), "shop"),
                                'rims' => html_link( $link( 'rims', $slug ), "shop"),
                                'packages' => html_link( $link( 'packages', $slug ), "shop"),
                            ];

                        }, $fitments );

                        ?>
                        <h2>Links to shop tires/rims/packages via fitment</h2>
                        <?php
                        echo render_html_table_admin( null, $table_data );
                    });

                    // Trims - Raw
                    $section( $year_make_model, function() use( $raw_trims_data ) {
                        ?>
                        <h2>Raw Trims Data</h2>
                        <p>Exactly the data which the API provides for us to generate a list of trims. Used to generate the filtered trims data.</p>
                        <?= get_pre_print_r( $raw_trims_data, true ); ?>
                        <?php
                    });

                    // Fitments - Raw
                    $section( (bool) $raw_fitment_data, function() use( $raw_fitment_data ) {
                        ?>
                        <h2>Raw Fitment Data</h2>
                        <p></p>
                        <?= get_pre_print_r( $raw_fitment_data, true ); ?>
                        <?php
                    });

                    // Fitments - Filtered
                    $section( (bool) $filtered_fitment_data, function() use( $filtered_fitment_data ) {
                        ?>
                        <h2>Filtered Fitment Data</h2>
                        <p>Derived from the raw fitment data. This data is used to generate the list of fitments in the vehicle make and model search.</p>
                        <?= get_pre_print_r( $filtered_fitment_data, true ); ?>
                        <?php
                    });

                }

                ?>

            </div>
        </div>
    </div>

<style>
    #main .general-content pre{
        line-height: 1.5em;
        font-size: 14px;
        font-weight: 500;
    }
</style>

<?php

cw_get_footer();