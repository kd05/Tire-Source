<?php

if ( ! cw_is_admin_logged_in() ) {
    echo 'not admin';
    exit;
}

$pk = gp_if_set( $_GET, 'pk' );
$brand = DB_Tire_Brand::create_instance_via_primary_key( $pk );

if ( ! $brand ) {
    echo 'Not Found';
    return;
}

$form_action = gp_if_set( $_POST, 'form_action' );
$tire_brand_name = gp_if_set( $_POST, 'tire_brand_name' );

while ( true ) {

    switch ( $form_action ) {
        case 'edit':

            $updated = $brand->update_database_and_re_sync( array(
                'tire_brand_name' => gp_test_input( $tire_brand_name ),
                'tire_brand_logo' => get_user_input_singular_value( $_POST, 'tire_brand_logo' ),
                'tire_brand_description' => trim( gp_force_singular( $_POST[ 'tire_brand_description' ] ) )
            ) );

            if ( $updated ) {
                $msg = 'Update Successful.';
                break;
            } else {
                $msg = 'Update Not Successful.';
            }

            break;
        default:
            break;
    }

    break;
}

// get model again even if not updated
$brand = DB_Tire_Brand::create_instance_via_primary_key( $pk );

echo '<div class="admin-section general-content">';
echo '<h1>Tire Brand</h1>';
echo '<p><a href="' . get_admin_archive_link( 'tires', [ 'brand_id' => $brand->get_primary_key_value() ] ) . '">Tires with this Brand</a></p>';

echo '<p><a href="' . get_admin_archive_link( 'tire_models', [ 'tire_brand_id' => $brand->get_primary_key_value() ] ) . '">Tire Models with this Brand</a></p>';

echo wrap_tag( html_link_new_tab( cw_add_query_arg( [ 'brand' => $brand->get( 'slug' )], get_url( 'tires' )), "Product Archive Page" ) );

echo render_html_table_admin( false, [ $brand->to_array_for_admin_tables() ], [] );
echo '</div>';

echo '<div class="admin-section general-content">';
echo '<form class="form-style-basic" method="post">';

echo '<input type="hidden" name="form_action" value="edit">';

echo '<div class="form-items">';

echo get_form_header( 'Edit' );

if ( $msg ) {
    echo get_form_response_text( $msg );
}

echo $brand->get_simple_form_input( 'tire_brand_name' );
echo $brand->get_simple_form_input( 'tire_brand_logo' );

echo '<br>';
echo wrap_tag( "The description shows up on single tire pages, but only if the tire model description is left blank." );
echo $brand->get_simple_form_textarea( 'tire_brand_description', 'Tire Brand Description (HTML is allowed)' );

echo get_form_submit( array(
    'text' => 'Submit',
) );

echo '</div>'; // form-items

echo '</form>';
echo '</div>';


