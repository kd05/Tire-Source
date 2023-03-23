<?php

if ( gp_if_set( $_POST, 'run_process_ca' ) ) {
    echo wrap_tag( "Form Submitted..." . MWS_LOCALE_CA );
    mws_check_for_feed_submission_updates(MWS_LOCALE_CA);
	$tick = new MWS_Inventory_Tick( MWS_LOCALE_CA );
	$tick->run();
	echo '<pre>' . print_r( $tick, true ) . '</pre>';
	echo '<br><br>';
}

if ( gp_if_set( $_POST, 'run_process_us' ) ) {
	echo wrap_tag( "Form Submitted..." . MWS_LOCALE_US );
    mws_check_for_feed_submission_updates(MWS_LOCALE_US);
	$tick = new MWS_Inventory_Tick( MWS_LOCALE_US );
	$tick->run();
	echo '<pre>' . print_r( $tick, true ) . '</pre>';
	echo '<br><br>';
}

echo '<form method="post">';

echo wrap_tag( "Emulates a single tick of the cron job for amazon inventory updates. Submitting this form may take database inventory levels and send them to amazon. Its possible but not guaranteed that in a test environment, inventory levels are generated but not actually sent. Proceed with caution." );
echo '<br>';
echo '<br>';

echo wrap_tag( html_element( 'Run CA', 'button', '', [
		'type' => 'submit',
		'name' => 'run_process_ca',
		'value' => 1,
	]
), 'p' );

echo '<br>';
echo '<br>';

echo wrap_tag( html_element( 'Run US', 'button', '', [
		'type' => 'submit',
		'name' => 'run_process_us',
		'value' => 1,
	]
), 'p' );

echo '</form>';

