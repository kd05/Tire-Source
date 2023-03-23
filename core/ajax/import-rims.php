<?php

if ( ! cw_is_admin_logged_in() ) {
    echo 'Not authorized.';
	exit;
}

$import = new Product_Import_Rims();
$import->run();
$import->after_run();

Ajax::echo_response( $import->get_response_array() );