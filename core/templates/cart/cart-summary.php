<?php
/**
 * this is actually shown on checkout.php
 */

$cart = get_cart_instance();

?>

<div class="cart-title">
	<h2>Cart Summary</h2>
</div>
<div class="cart-box">
	<?php

	$packages = $cart->get_not_empty_package_ids();

	echo '<div class="cart-quick-summary">';

	if ( $packages ) {
		foreach ( $packages as $package ) {

			echo '<div class="cq-package exists">';

			$items = $cart->get_item_ids_by_package( $package, true );

			if ( $items ) {
				foreach ( $items as $_item ) {
					$item = $cart->get_item( $_item );

					$qty = $item->get_quantity();

					// dont print empty items (mount_balance/install_kit often are empty)
					if ( ! $qty ) {
					    continue;
                    }

					echo '<div class="cq-item">';

					echo '<div class="left">';
					echo '<p>';
					echo $item->get_receipt_summary_title();
					echo '</p>';
					echo '</div>';
					echo '<div class="right">';
					echo '<p>';
					echo $item->get_receipt_summary_price_on_2_lines();
					echo '</p>';
					echo '</div>';

					echo '</div>'; // item
				}
			}

			echo '</div>'; // package
		}
	}

	$np_items = $cart->get_non_packaged_item_ids();

	if ( $np_items ) {
		foreach ( $np_items as $_item ) {

			echo '<div class="cq-package not-really">';

			$item = $cart->get_item( $_item );
			echo '<div class="cq-item">';

			echo '<div class="left">';
			echo '<p>';
			echo $item->get_receipt_summary_title();
			echo '</p>';
			echo '</div>';

			echo '<div class="right">';
			echo '<p>';
			echo $item->get_receipt_summary_price_on_2_lines();
			echo '</p>';
			echo '</div>';

			echo '</div>';

			echo '</div>'; // package
		}
	}

	if ( ! $cart->count_items() ) {
	    echo '<p class="small-text">Your cart is empty.</p>';
    }

	echo '</div>';

	?>
</div>


