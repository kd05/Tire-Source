<?php

if ( ! cw_is_admin_logged_in() ) {
    exit;
}

page_title_is( 'Transaction Report' );

cw_get_header();
Admin_Sidebar::html_before();

$format = 'M d, Y';
$db_format = get_database_date_format();

$start = gp_if_set( $_POST, 'start' );
$end = gp_if_set( $_POST, 'end' );

$start_date = try_to_get_date_time( $start );
$end_date = try_to_get_date_time( $end );

$start_formatted = $start_date ? $start_date->format( $format ) : '';
$end_formatted = $end_date ? $end_date->format( $format ) : '';

$form_submitted = gp_if_set( $_POST, 'form_submitted' );

?>
	<form action="" method="post" class="admin-section general-content form-style-basic">

		<?php echo get_form_header( 'Transaction Report' ); ?>
        <p>You can optionally set start and end dates using any valid date format, or hit submit to view all transactions.  The table shows successful transactions only, and pulls information from both the orders and transactions tables.</p>

		<input type="hidden" name="form_submitted" value="1">

		<?php echo get_form_input( array(
			'label' => 'Start Date',
			'value' => $start_formatted,
			'name' => 'start',
		)); ?>

		<?php echo get_form_input( array(
			'label' => 'End Date',
			'value' => $end_formatted,
			'name' => 'end',
		)); ?>

		<button type="submit">Submit</button>

	</form>
<?php

if ( $form_submitted ) {

	$ss = $start_date ? $start_date->format( $db_format ) : '';
	$ee = $end_date ? $end_date->format( $db_format ) : '';

	if ( ! $ss && ! $ee ) {
//		echo '<p>Showing all successful transactions</p>';
//		echo '<br>';
	}

	$db = get_database_instance();
	$p = [];
	$q = '';
	$q .= 'SELECT * ';
	$q .= 'FROM ' . $db->transactions . ' AS transactions ';
	$q .= 'INNER JOIN ' . $db->orders . ' AS orders ON orders.transaction_id = transactions.transaction_id ';
	$q .= '';
	$q .= 'WHERE 1 = 1 ';
	$q .= '';

	if ( $start_date ) {
		$q .= 'AND orders.order_date >= :order_date_start ';
		$p[] = [ 'order_date_start', $start_date->format( $db_format ) ];
	}

	if ( $end_date ) {
		$q .= 'AND orders.order_date <= :order_date_end ';
		$p[] = [ 'order_date_end', $end_date->format( $db_format ) ];
	}

	$q .= 'AND ( transactions.success = "1" OR transactions.success = 1 ) ';

	$q .= '';
	$q .= '';
	$q .= 'ORDER BY order_date DESC ';
	$q .= '';
	$q .= ';';

	$results = $db->get_results( $q, $p );

	queue_dev_alert( 't query', debug_pdo_statement( $q, $p ) );

	$cols = array(
		'transaction_id',
		'order_id',
		'order_date',
		'order_status',
		'auth_code',
		'reference_number',
		'trans_id',
		'user_id',
		'email',
		'success',
		'subtotal',
		'shipping',
		'tax',
		'ontario_fee',
		'ontario_fee_qty',
		'total',
		'locale',
        'heard_about',
	);

	$table_rows = array();

	if ( $results ) {
		foreach ( $results as $_r ) {

			$row = array();
			foreach ( $cols as $col ) {
				$row[$col] = strip_tags( gp_if_set( $_r, $col ) );
			}

			$table_rows[] = $row;
		}
	}

	echo render_html_table_admin( $cols, $table_rows );
}


page_footer:
Admin_Sidebar::html_after();
cw_get_footer();



