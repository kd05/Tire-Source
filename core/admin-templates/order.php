<?php



if ( ! cw_is_admin_logged_in() ) {
	exit;
}

$order_id = (int) gp_if_set( $_GET, 'order_id' );

page_title_is( 'Order ' . $order_id  );

/** @var DB_Order|null $order */
$order = DB_Order::create_instance_via_primary_key( $order_id );

if ( ! $order ) {
	echo 'Invalid order';
	exit;
}

/** @var DB_Transaction $transaction */
$transaction = DB_Transaction::create_instance_via_primary_key( $order->get( 'transaction_id' ) );

/** @var DB_User|null $user */
$user_id = $order->get('user_id' );
$user = $user_id ? DB_User::create_instance_via_primary_key( $user_id ) : false;

if ( ! $transaction ) {
	echo 'Order found, but transaction not found.';
	exit;
}

$post_back_success  = false;
$post_back_response = '';
include CORE_DIR . '/admin-templates/post-back/order.php';

cw_get_header();
Admin_Sidebar::html_before();

// ********* STATUS **********
?>
    <form action="" method="post" class="admin-section general-content form-style-basic">
        <h2>Status</h2>
        <p>The value that you set here will show up on the user's account page in a table showing their orders.</p>
        <?php echo get_nonce_input_via_secret( 'edit-single-order' ); ?>
        <input type="hidden" name="form_submitted" value="1">
		<?php
		if ( $post_back_response ) {
			echo get_form_response_text( $post_back_response );
		}

		$items = get_order_status_map();
		$items = array_merge( [ '' => 'none' ], $items );
		$_status = $order->get_and_clean( 'order_status' );

		// if we remove some items from the map, but the order still has one of those statuses,
        // then we'll want to just show it anyways.
		if ( $_status && ( ! in_array( $_status, array_keys( $items ) ) ) ) {
		    $items[$_status] = $_status;
        }

		?>
        <?php if ( $transaction->get( 'success' ) ) { ?>
            <div class="form-items">
		        <?php echo get_form_select( array(
			        'name' => '_status',
		        ), array(
			        'items' => $items,
			        'current_value' => $_status,
		        ) ); ?>
		        <?php echo get_form_submit( [ 'text' => 'Update Status' ] ); ?>
            </div>
        <?php } else { ?>
            <p>Order status cannot be edited for failed transactions</p>
        <?php } ?>
    </form>
<?php

// ********* EMAILS **********
echo '<div class="admin-section general-content">';
include 'partials/order-emails.php';
echo '</div>';


// ********* ITEMS **********
echo '<div class="admin-section general-content">';
echo '<h2>Details</h2>';
echo '<p>Some additional data can be found under Tables -> orders, transactions, order_items, and order_vehicles.</p>';
echo '<p><a href="' . get_front_end_single_order_link( $order_id ) . '">Front-end order details page.</a></p>';
echo '<p><a href="' . get_admin_single_edit_link( DB_transactions, $transaction->get_primary_key_value() ) . '">Transactions table page.</a></p>';

if ( ! $transaction->get( 'success' ) ) {
	echo '<p><strong class="red">This transaction was not successful.</strong></p>';
}

if ( $user_id ) {
    if ( $user ) {
	    echo '<p>Assigned to user: <a href="' . get_admin_single_user_url( $user_id ) . '">' . $user->get( 'email' ) . '</a></p>';
    } else {
	    echo '<p>Assigned to a user that doesn\'t currently exist.</p>';
    }
}

echo '<p><strong>Ship To: ' . $order->get( 'ship_to' )  . '</strong></p>';

if ( $transaction->get( 'success' ) ) {
	echo render_admin_successful_order_details( $order, $transaction );
} else {
    // dump all order/transaction details
    //I think this is redundant?? isnt this page already showing only successful?
	echo render_admin_failed_order_details( $order, $transaction );
}

echo '</div>';

?>

<div class="admin-section general-content">
    <h3>Kount Risk Assessment</h3>
    <p>Overall Status: <?= $transaction->get_kount_inquiry_result_code( true ); ?></p>
    <p>Kount Score: <?= $transaction->get_kount_score(); ?> out of 100. (Low score is good).</p>
    <?= get_pre_print_r( $transaction->get_kount_inquiry_response(), true ); ?>
</div>

<?php

Admin_Sidebar::html_after();
cw_get_footer();


