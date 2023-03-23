<?php
/**
 * We could put more code into the model file, but its easier to have all
 * code inside of the product-sync directory, so that we can easily copy
 * code to and from WFL/CW.
 */

namespace PS\PriceRules;

function get_entity_type( array $rule ) {

    $s = $rule['supplier'];
    $b = $rule['brand'];
    $m = $rule['model'];

    if ( $s && $b && $m ) {
        return [ 'model', [ $s, $b, $m ] ];
    }

    if ( $s && $b ) {
        return [ 'brand', [ $s, $b ] ];
    }

    if ( $s ) {
        return [ 'supplier', [ $s ] ];
    }

    return [ '', [] ];
}

/**
 * @param $pct
 * @return string
 */
function format_pct( $pct ) {

    if ( $pct == 0 ) {
        return "+0%";
    }

    if ( $pct > 0 ) {
        return "+" . $pct . "%";
    }

    return $pct . '%';
}

/**
 * @param $flat
 * @return string
 */
function format_flat( $flat ) {

    if ( $flat == 0 ) {
        return "+$0";
    }

    if ( $flat > 0 ) {
        return "+$" . $flat;
    }

    $_flat = ltrim(trim( $flat ), '-' );

    return "-$" . $_flat;
}

/**
 * For rendering
 *
 * @param $name
 * @param $pct
 * @param $flat
 * @return string
 */
function format_pct_flat( $name, $pct, $flat ) {
    $_pct = format_pct( $pct );
    $_flat = format_flat( $flat );
    return "$name $_pct $_flat";
}

/**
 * @param $rule
 * @return string
 */
function get_price_rule_debug( $rule ) {

    if ( ! $rule || ! is_array( $rule ) || ! $rule['rule_type'] ) {
        return "None.";
    }

    list( $e_type, $e_args ) = get_entity_type( $rule );

    $rule_type = $rule['rule_type'];

    $formatted = [
        'cost' => format_pct_flat( "cost", $rule['cost_pct'], $rule['cost_flat'] ),
        'msrp' => format_pct_flat( "msrp", $rule['msrp_pct'], $rule['msrp_flat'] ),
        'map' => format_pct_flat( "map", $rule['map_pct'], $rule['map_flat'] ),
    ];

    $ret = "$e_type/$rule_type: ";

    if ( $rule_type === 'cost' ) {
        unset( $formatted['msrp'] );
    }

    if ( $rule_type === 'map_cost' ) {
        unset( $formatted['msrp'] );
    }

    if ( $rule_type === 'msrp' ) {
        unset( $formatted['cost'] );
    }

    if ( $rule_type === 'map_msrp' ) {
        unset( $formatted['cost'] );
    }

    $ret .= implode( ", ", $formatted );
    return $ret;
}

/**
 * @param $in
 * @param $allow_empty
 * @return array
 */
function check_decimal_str( $in, $allow_empty ) {

    if ( ! $allow_empty && ! $in ) {
        return [ false, "Value is empty" ];
    }

    list( $valid, $msg ) = \Product_Sync::check_decimal_str( $in );
    return [ $valid, $msg ];
}

/**
 * @param $base
 * @param $pct
 * @param $flat
 * @param string $base_type_str
 * @return array - [ price, possible error string or '' ]
 */
function compute_price( $base, $pct, $flat, $base_type_str = 'cost/msrp/map' ) {

    list( $base_valid, $base_msg ) = check_decimal_str( $base, false );
    list( $pct_valid, $pct_msg ) = check_decimal_str( $pct, false );
    list( $flat_valid, $flat_msg ) = check_decimal_str( $flat, true );

    // the base price usually originates from a supplier file
    // $base < 2 to possibly catch rounding errors or if the supplier
    // decides to put a boolean value (ie. 1) in the column instead of a price
    if ( ! $base_valid || $base < 2 ) {
        return [ false, "Invalid $base_type_str ($base_msg)" ];
    }

    // these (pct and flat rate errors) are less likely to happen because we
    // validate these before storing them in the first place.
    if ( ! $pct_valid ) {
        return [ false, "Price rule percentage is not valid ($pct_msg)" ];
    }

    if ( ! $flat_valid ) {
        return [ false, "Price rule flat rate is invalid ($flat_msg)" ];
    }

    $base = number_format( $base, 2, '.', '' );
    $pct = number_format( $pct, 2, '.', '' );
    $flat = number_format( $flat, 2, '.', '' );

    $add_pct = bcmul( $base, bcadd( 1, bcdiv( $pct, 100, 4 ), 2 ), 2 );
    $ret = bcadd( $flat ? $flat : 0, $add_pct, 2 );

    return [ $ret, '' ];
}

/**
 * Validates the input prices as well.
 *
 * On the result you should check that it's not false and that it is
 * greater than zero.
 *
 * @param array $rule
 * @param $msrp
 * @param $cost
 * @param $map_price
 * @return array - resulting price, and an error msg if resulting price is false.
 */
function apply_price_rule( array $rule, $msrp, $cost, $map_price ) {

    $rt = $rule['rule_type'];

    // if min advertised price exists and is valid, enforce it (if calculated price
//    // is below map)
//    if ( $price && $map_price && Product_Sync::check_decimal_str( $map_price )[0] ) {
//        if ( $price < $map_price ) {
//            $map_enforced = true;
//            $price = number_format( $map_price, 2, '.', '' );
//        }
//    }
//
//    // assume possible rounding errors even though they are not likely
//    if ( $price && $price > 0.5 ) {
//        return [ $price, '', $map_enforced ];
//    } else {
//        $err = $price_err ? $price_err : "Price not greater than zero (or could not be calculated.)";
//        return [ '', $err, $map_enforced ];
//    }

    if ( $rt === 'cost' || $rt === 'msrp' ) {

        // we don't force this to be filled out for this rule type, so we need
        // to add a default, because the compute price requires 0.00 over ''
        $map_pct = $rule['map_pct'] ? $rule['map_pct'] : '0.00';
        $map_flat = $rule['map_flat'] ? $rule['map_flat'] : '0.00';

        if ( $rt === 'cost' ) {
            $effective_type = 'cost';
            list( $price, $err ) = compute_price( $cost, $rule['cost_pct'], $rule['cost_flat'] );
        } else {
            $effective_type = 'msrp';
            list( $price, $err ) = compute_price( $msrp, $rule['msrp_pct'], $rule['msrp_flat'] );
        }

        // if price can't be calculated, just return an error without checking map.
        if ( $err ) {
            return [ false, "$rt error: " . $err, $effective_type ];
        }

        // cost and msrp price rules can also modify the map price before checking
        // if the base price is less than it. This can be done to increase or in some
        // cases decrease the map price.
        list( $effective_map, $effective_map_err ) = compute_price( $map_price, $map_pct, $map_flat );

        // map price did not exist or we could not calculate it.. return the original price.
        if ( $effective_map_err ) {
            return [ $price, $err, $effective_type ];
        } else {

            // we have a map price to check, so check it
            if ( $price < $effective_map ) {
                return [ $effective_map, '', 'map' ];
            } else {

                // map was not less than original price, return original
                return [ $price, $err, $effective_type ];
            }
        }
    }

    // try to use map price, but if it doesn't exist or isn't valid, fallback to cost or msrp
    if ( $rt === 'map_cost' || $rt === 'map_msrp' ) {

        list( $map_price, $map_err ) = compute_price( $map_price, $rule['map_pct'], $rule['map_flat'] );

        if ( ! $map_err ) {
            return [ $map_price, '', 'map' ];
        } else {

            if ( $rt === 'map_cost' ) {
                $fallback_type = 'cost';
                list( $fallback_price, $fallback_err ) = compute_price( $cost, $rule['cost_pct'], $rule['cost_flat'] );
            } else {
                $fallback_type = 'msrp';
                list( $fallback_price, $fallback_err ) = compute_price( $msrp, $rule['msrp_pct'], $rule['msrp_flat'] );
            }

            if ( ! $fallback_err ) {
                return [ $fallback_price, '', $fallback_type ];
            }

            return [ false, 'Fallback (' . $rt . ') price error: ' . $fallback_err, $fallback_type ];
        }
    }

    return [ false, "Invalid rule_type", '' ];
}
