<?php

/**
 * @param $col
 * @param $args
 */
function sql_make_table_col_string( $col, $args ) {
	$col = trim( $col );
	$args = trim( $args );
	$op = '';
	$op .= '`' . $col . '`';
	$op .= ' ' . $args;
	return trim( $op );
}

/**
 * pretty ugly function but better than nothing to help create database tables when starting
 * with arrays to store the data rather than formatted sql.
 *
 * @param $table
 * @param $cols
 * @param $extra
 *
 * @return bool
 */
function sql_create_table_if_not_exists( $table, $cols, $extra ) {

	$q = '';
	$table_args = '';

	// ie. $cols = [ 'user_id' => 'int(11) NOT NULL AUTO INCREMENT', 'email' => '....', ... ]
	if ( $cols && is_array( $cols ) ) {
		foreach ( $cols as $col_name => $col_data ) {
			$table_args .= sql_make_table_col_string( $col_name, $col_data ) . ',';
		}
	}

	// ie. [ 'PRIMARY KEY (`cache_id`)', '....' ]
	$table_args .= implode_comma( $extra );

	$q .= 'CREATE TABLE IF NOT EXISTS `' . gp_esc_db_table( $table ) . '` (';
	$q .= trim( $table_args );
	$q .= ');';

    //    echo nl2br( "-----------------------  \n" );
//    echo $q;

    // let it throw exception (and let global exception handler deal with it)
    $db = get_database_instance();
    $st = $db->pdo->prepare( $q );
    $db->pdo->errorInfo();
    return $st->execute();
}


