<?php

// not necessary
if ( ! cw_is_admin_logged_in() ) {
    exit;
}

if ( @$_POST['submit'] == '1' ) {

    Ajax::check_global_nonce();

    $_GET['action'] = gp_test_input( @$_POST['action'] );

    include CORE_DIR . '/cron/_controller.php';
    echo "Done: " . $_GET['action'];
    echo '<br>';
    echo '<br>';

    echo get_pre_print_r( [
        'log_partial' => Cron_Helper::$merge_into_log_after,
    ], true );

    exit;
}

?>

<p>Manually run a cron job (dev tool)</p>

<br>
<br>

<form action="" method="post">
    <input type="hidden" name="submit" value="1">
    <input type="hidden" name="nonce" value="<?= Ajax::get_global_nonce(); ?>">
    <input type="text" name="action" placeholder="action" value="">
    <button type="submit">Submit</button>
</form>
