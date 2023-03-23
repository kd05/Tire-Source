<?php

namespace CW\Migrations;

function rewrite_urls( $mock = false, $to = 'http://localhost:8080/tiresource', $from = 'https://tiresource.com' ) {
    sql_find_replace( 'page_meta', 'meta_value', $from, $to );
}

function sql_find_replace( $table, $column, $from, $to ){

    $table = gp_esc_db_col( $table );
    $column = gp_esc_db_col( $column );

    $sql = "UPDATE $table SET $column = replace( $column, :from, :to );";
    $params = [[ 'from', $from ], [ 'to', $to ]];

    echo "Executing: $sql Replacements: " . json_encode( $params );
    get_database_instance()->execute( $sql, $params );
}
