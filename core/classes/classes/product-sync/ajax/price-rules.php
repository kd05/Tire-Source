<?php

use Product_Sync_Admin_UI as ui;
use Product_Sync_Pricing_UI as pricing;

if ( ! cw_is_admin_logged_in() ) {
    echo "Error";
    exit;
}

// might exit
Ajax::check_global_nonce();

$number_format = function( $in ) {

    if ( $in === '0' || $in === 0 ) {
        return '0.00';
    }

    if ( ! $in ) {
        return '';
    }

    return Product_Sync::check_decimal_str( $in )[0] ? number_format( $in, 2, '.', '' ) : '';
};

// pass supplier, brand, and model as slug
$type = gp_test_input( @$_POST['type'] );
$locale = gp_test_input( @$_POST['locale'] );
$supplier = gp_test_input( @$_POST['supplier'] );
$brand = gp_test_input( @$_POST['brand'] );
$model = gp_test_input( @$_POST['model'] );
$entity_type = gp_test_input( @$_POST['entity_type'] );
$rule_type = gp_test_input( @$_POST['rule_type'] );
$msrp_pct = $number_format( @$_POST['msrp_pct'] );
$msrp_flat = $number_format( @$_POST['msrp_flat'] );
$cost_pct = $number_format( @$_POST['cost_pct'] );
$cost_flat = $number_format( @$_POST['cost_flat'] );
$map_pct = $number_format( @$_POST['map_pct'] );
$map_flat = $number_format( @$_POST['map_flat'] );
$brand_model = gp_test_input( @$_POST['brand_model'] );

$price_rule_action = @$_POST['price_rule_action'];
$delete_id = (int) @$_POST['delete_id'];

$send_err = function( $msg ) {
    Ajax::echo_response([
        'error' => $msg,
    ]);
    exit;
};

$send_success = function( $msg ) {
    Ajax::echo_response([
        'success' => $msg,
        'msg' => $msg,
    ]);
    exit;
};

if ( $price_rule_action === 'delete' ) {
    $to_delete = DB_Price_Rule::create_instance_via_primary_key( $delete_id );
    if ( $to_delete ) {
        $to_delete->delete_self_if_has_singular_primary_key();
        $send_success("Price rule deleted.");
    } else {
        $send_err( "Price rule did not exist (could not delete).");
    }
}

if ( $brand_model && ! $model ) {
    $brand = @explode( '##', $brand_model )[0];
    $model = @explode( '##', $brand_model )[1];
}

if ( $entity_type === 'supplier' ) {
    $ex = pricing::get_single_price_rule( $type, $locale, $supplier );
} else if ( $entity_type === 'brand' ) {

    if ( ! $brand ) {
        $send_err( "Please choose a brand." );
    }

    $ex = pricing::get_single_price_rule( $type, $locale, $supplier, $brand );
} else if ( $entity_type === 'model' ) {

    if ( ! $brand || ! $model ) {
        $send_err( "Please choose a brand/model." );
    }

    $ex = pricing::get_single_price_rule( $type, $locale, $supplier, $brand, $model );
} else {
    $send_err( "Invalid request.");
}

if ( ! $supplier ) {
    $send_err( "Invalid supplier." );
}

$cost_flat = $cost_flat ? $cost_flat : '0.00';
$msrp_flat = $msrp_flat ? $msrp_flat : '0.00';
$map_flat = $map_flat ? $map_flat : '0.00';

if ( $rule_type === 'cost' ) {

    if ( ! $cost_pct ) {
        $send_err( "Cost pct is required (enter 40 to sell at 40% above cost).");
    }

    $update_fields = [
        'rule_type' => $rule_type,
        'msrp_pct' => '',
        'msrp_flat' => '',
        'cost_pct' => $cost_pct,
        'cost_flat' => $cost_flat,
        'map_pct' => $map_pct == 0 ? '0.00' : $map_pct,
        'map_flat' => $map_flat,
    ];

} else if ( $rule_type === 'msrp' ) {

    if ( ! $msrp_pct ) {
        $send_err( "MSRP pct is required for MSRP rule type (enter 0.00 to sell at MSRP).");
    }

    $update_fields = [
        'rule_type' => $rule_type,
        'msrp_pct' => $msrp_pct,
        'msrp_flat' => $msrp_flat,
        'cost_pct' => '',
        'cost_flat' => '',
        'map_pct' => $map_pct == 0 ? '0.00' : $map_pct,
        'map_flat' => $map_flat,
    ];

} else if ( $rule_type === 'map_cost' ){

    if ( ! $map_pct ) {
        $send_err( "Enter 0 in map_pct to sell at MAP price.");
    }

    if ( ! $cost_pct ) {
        $send_err( "Cost % is required.");
    }

    $update_fields = [
        'rule_type' => $rule_type,
        'msrp_pct' => '',
        'msrp_flat' => '',
        'cost_pct' => $cost_pct,
        'cost_flat' => $cost_flat,
        'map_pct' => $map_pct,
        'map_flat' => $map_flat,
    ];


} else if ( $rule_type === 'map_msrp' ) {

    if ( ! $map_pct ) {
        $send_err( "Enter 0 in map_pct to sell at MAP price.");
    }

    if ( ! $msrp_pct ) {
        $send_err( "MSRP % is required. Enter 0 to sell at MSRP (when MAP price does not exist for a product)");
    }

    $update_fields = [
        'rule_type' => $rule_type,
        'msrp_pct' => $msrp_pct,
        'msrp_flat' => $msrp_flat,
        'cost_pct' => '',
        'cost_flat' => '',
        'map_pct' => $map_pct,
        'map_flat' => $map_flat,
    ];

} else if ( $rule_type ){
    $send_err( "Invalid rule type.");
} else {
    $send_err( "Please select a rule type." );
}

$mock_price_rule = new DB_Price_Rule( $update_fields );

// checks rule type and pct/flat rate fields for formatting etc.
$errors = $mock_price_rule->validate();

if ( $errors ) {
    Ajax::echo_response([
        'errors' => implode( ', ', $errors )
    ]);
    exit;
}

if ( $ex ) {
    $updated = get_database_instance()->update( 'price_rules',
        $update_fields,
        [
            'id' => $ex['id']
        ]
    );

    $send_success( 'Price rule updated. (ID: ' . (int) $ex['id'] . ')' );

} else {

    $insert_fields = $update_fields;
    $insert_fields['type'] = $type;
    $insert_fields['locale'] = $locale;
    $insert_fields['supplier'] = $supplier;

    if ( $entity_type === 'brand' ) {
        $insert_fields['brand'] = $brand;
    }

    if ( $entity_type === 'model' ) {
        $insert_fields['brand'] = $brand;
        $insert_fields['model'] = $model;
    }

    $insert_id = get_database_instance()->insert( 'price_rules', $insert_fields );

    $send_success( 'Price rule inserted (ID: ' . (int) $insert_id . ').' );
}
