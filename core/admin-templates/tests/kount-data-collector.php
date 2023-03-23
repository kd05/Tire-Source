<?php

$test_mode = @$_POST[ 'test_mode' ] === "yes";

?>

    <p>App generated session ID: <?= App_Kount::get_session_id(); ?> (You may or may not want to use this).</p>
    <p>Data collection probably only needs to be done once per session.</p>

    <form method="post">
        <input type="hidden" name="submitted" value="1">
        <p>
            Session ID: <input type="text" name="session_id" value="">
        </p>
        <p>
            Test Mode: <input type="checkbox" name="test_mode" value="yes" checked>
        </p>
        <p>
            <button type="submit">Submit.</button>
        </p>
    </form>

    <br>

<?php

if ( @$_POST[ 'submitted' ] ) {

    $_POST['test_mode'] = $test_mode ? 'yes' : 'no';

    echo wrap_tag( "Submitting...." );

    echo get_pre_print_r( $_POST, true );

    echo App_Kount::get_data_collector_html( ! $test_mode, true, gp_test_input( $_POST[ 'session_id' ] ) );

    echo wrap_tag( "Check JS console for to see of data completion was successful." );

}


