<?php

$print = $_SERVER;
$print['HTTP_COOKIE'] = '_removed';
echo '<pre>' . print_r( $print, true ) . '</pre>';
