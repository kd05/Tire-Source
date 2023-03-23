<?php

$ftp                   = new FTP_Get_Csv();
echo '<pre>' . print_r( $ftp, true ) . '</pre>';

$ftp->remote_file_name = 'test.csv';
$ftp->method           = 'sftp';
$ftp->host             = 'access764455319.webspace-data.io';
$ftp->port             = 22;
$ftp->username         = 'u95793629-cda-tire';
$ftp->password         = '##removed';
$ftp->run();

$ftp2 = clone $ftp;
$ftp2->password = "********";
echo '<pre>' . print_r( $ftp2, true ) . '</pre>';

//$test = file_get_contents( 'ssh2.sftp://' . $ftp->username . ':' . $ftp->password . '@access764455319.webspace-data.io/cda-tire/test.csv' );
//echo '<pre>' . print_r( $test, true ) . '</pre>';

