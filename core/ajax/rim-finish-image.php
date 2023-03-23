<?php

if ( ! cw_is_admin_logged_in() ) {
    echo "Not authorized.";
    exit;
}

$_POST['is_tire'] = false;

try{
    $ret = Product_Images_Admin_UI::handle_submit( $_POST );
    Ajax::echo_response( $ret );
} catch ( Exception $e ) {

    Ajax::echo_response( json_encode( [
        'success' => false,
        'msg' => 'Exception: ' . $e->getMessage(),
    ]) );
}

exit;

