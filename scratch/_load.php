<?php

require dirname( dirname( __FILE__ ) ) . '/core/_init.php';

if ( ! PHP_SAPI === 'cli' ) {
    echo 'CLI only';
    exit;
}
