<?php
/**
 * Note: its valid to use this file without passing in an action. The cart should
 * not be modified, but we'll still return the cart receipt and cart count at the end of this script.
 */

$success = false;
$response_text = '';

/** @var Cart $cart */
$cart = Cart::load_instance();

// @see new JS_Action() in the javascript.
$javascript_actions = array();

// collect and sanitize data
$action = gp_if_set( $_POST, 'action' );
$action = gp_test_input( $action );

$quantity = gp_if_set( $_POST, 'quantity' );
$quantity = (int) $quantity;
$quantity = $quantity < 0 ? 0 : $quantity;
$quantity = $quantity > 99 ? 99 : $quantity; // just because why not..

// the item_id is passed in as just 'item'
$item_id = gp_if_set( $_POST, 'item' );

// force singular just to be safe. feeding empty array to $cart->get_item() will return
// Cart_Item object, but empty string will return false;
$item_id = gp_make_singular( $item_id );

/** @var Cart_Item|false $item */
$item = $cart->get_item( $item_id );

$package_id = $item ? $item->package_id : false;

/**
 * only some items have packages
 *
 * @var Cart_Package $package
 */
$package = $cart->get_package( $package_id );

// Prevent removal of mount balance items, instead set their quantity to zero.
// when the items are removed entirely, the cart no longer renders them. Instead, we just want to render
// them differently when their quantity is zero.
if ( $action === 'remove_item' && $item && ( $item->type === 'mount_balance' || $item->type === 'install_kit' ) ) {
	$action = 'set_quantity';
	$quantity = 0;
}

// **** REMOVE_ITEM ****
if ( $action === 'remove_item' && $item ){
	$success = ( $cart->item_delete( $item ) );
}

// **** SET_QUANTITY ****
if ( $action === 'set_quantity' && $item ) {

	// once a user modified mount_balance in any way, we no longer try to adjust quantity, unless the value
	// they select is too large to be allowed.
	if ( $item->type === 'mount_balance' && $package ) {
		$package->user_allows_mount_balance = false;
	}

	// once a user modified mount_balance in any way, we no longer try to adjust quantity, unless the value
	// they select is too large to be allowed.
	if ( $item->type === 'install_kit' && $package ) {
		$package->user_allows_install_kit = false;
	}

	// update type tire, rim, install_kit, or mount_balance
	$success = $cart->item_update_single_property( $item, 'quantity', $quantity );
}

// **** ADD_MOUNT_BALANCE / ADD_INSTALL_KIT ****
if ( $action === 'add_mount_balance' || $action === 'add_install_kit' ) {
	// the intended way to add mount balance is to pass in the item (whose quantity is probably zero,
	// and has a package that it belongs to). however, we can skip the item and provide a package instead.
	$_p = $item ? $item : gp_if_set( $_POST, 'package' );

	if ( $action === 'add_mount_balance' ) {

		if ( DISABLE_MOUNT_BALANCE ) {
			$success = false;
			$response_text = 'Mount & Balance is currently disabled';
			goto build_response;
		}

		if ( app_get_locale() == 'US' ) {
			$success = false;
			$response_text = 'Mount & Balance is not available when shipping region is set to U.S.';
			goto build_response;
		} else {
			$success = $cart->set_mount_balance_to_suggested_quantity( $item, $_p );
			if ( $success ) {
				$javascript_actions[] = array(
					'action' => 'lightbox',
					'add_class' => 'mount-balance-disclaimer general-lightbox width-lg-1',
					'content' => get_mount_balance_disclaimer_lightbox_inner_html(),
					'close_btn' => true,
				);
			}
		}
	}

	if ( $action === 'add_install_kit' ) {
		$success = $cart->set_install_kit_to_suggested_quantity( $item, $_p );
	}
}

// I think $cart->commit() needs to be after this.
build_response:

// commit back to session, even though its possible nothing changed, just do it always regardless
$cart->commit();

// render html back to javascript
$Cart_Page = new Cart_Page( $cart );

$response = array();
$response['success'] = ( $success ); // javascript wont update html if this is false
$response['cart_items_html'] = $Cart_Page->render_items();
$response['receipt_html'] = get_receipt_html( false, get_user_input_singular_value( $_POST, 'province' ), 'cart' );
$response['response_text'] = $response_text;
$response['actions'] = $javascript_actions ? $javascript_actions : null;
$response['cart_count'] = $cart->count_items();

Ajax::echo_response( $response );
exit;
