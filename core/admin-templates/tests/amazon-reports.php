<?php

$action = @$_POST['action'];
$report_id = @$_POST['report_id'];
$locale = @$_POST['locale'];
$mws_locale = $locale === "US" ? MWS_LOCALE_US : MWS_LOCALE_CA;

// will already exist on live site, but possibly not otherwise
@mkdir( LOG_DIR .' /mws', true, 0755 );

?>

    <form method="post">
        <select name="action" id="">
            <option value="">Choose an Action</option>
            <option value="request_report">Request Report</option>
            <option value="get_report">Get Report</option>
        </select>
        <br>
        <br>
        <select name="locale" id="">
            <option value="CA">CA</option>
            <option value="US">US</option>
        </select>
        <br>
        <br>
        <input type="text" placeholder="Report ID" name="report_id">
        <br>
        <br>
        <button type="submit">Submit</button>
    </form>
    <br>
    <hr>
    <br>

<?php

if ( $action === 'request_report' ) {

    try{

        $amazon = Amazon_MWS::get_instance( $mws_locale );
        $report_id = $amazon->client->RequestReport( MWS_REPORT_GET_MERCHANT_LISTINGS_ALL_DATA );

        echo wrap_tag( "Requesting Report..." );
        echo get_pre_print_r( $report_id, true );

    } catch ( Exception $e ) {
        echo wrap_tag( $e->getMessage() );
    }

} else if ( $action === 'get_report' ) {

    $amazon = Amazon_MWS::get_instance( $mws_locale );
    $status = $amazon->client->GetReportRequestStatus( $report_id );

    echo wrap_tag( "Report Request Status: " );
    echo get_pre_print_r( $status, true );

    $report = $amazon->client->GetReport( $report_id );

    $filename = "mws-manual-report-" . time() . '.log';
    $path = LOG_DIR . '/mws/' . $filename;

    echo wrap_tag( "Check Logs Directory ($path)" );

    $file = file_put_contents( $path, print_r( $report, true ), FILE_APPEND | FILE_USE_INCLUDE_PATH );
    echo wrap_tag( "file_put_contents? " . ($file ? "1" : "0") );
    echo wrap_tag( "Filesize: " . filesize( $path ) );

    if ( @$report[0] ) {
        echo wrap_tag( "First few items..." );
        echo get_pre_print_r( array_splice( $report, 0, 5 ) );
    }
}

