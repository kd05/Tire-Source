<?php

/**
 * Definitely not a fully stacked query builder,
 * just some simple, possibly independent re-usable methods.
 *
 * Class Sql_Builder
 */
Class Sql_Builder {

	/**
	 * @param $select
	 */
	public function select( $select ) {

		if ( is_string( $select ) && $select ) {
			$select = array( $select );
		}

		$op = '';

		if ( $select && is_array( $select ) ) {
			$op .= 'SELECT ' . implode_comma(  $select ) . ' ';
		} else {
			$op .= 'SELECT * ';
		}

		return $op;
	}

	/**
	 * @param $values
	 */
	public function comma_sep( $values ) {

		if ( ! $values ) {
			return false;
		}

		$values = gp_make_array( $values );

		if ( ! gp_is_array_depth_1( $values ) ) {
			return false;
		}

		$str = '';
		// implode is easier but this lets us verify $value, and more easily decide what to do with
		// it if it doesnt exist, or is NULL, or is an array, or whatever else...
		foreach ( $values as $value ) {
			if ( $value ) {
				$str .= ', ' . $value;
			}
		}

		$str = trim( $str, ',' );
		$str = trim( $str );
		$str = trim( $str, ',' );

		return $str;
	}

	/**
	 * @param        $group
	 * @param string $condition
	 */
	public function condition_group( $groups, $condition = 'AND' ) {

		$default = ' ( 1 = 1 ) ';

		if ( strtolower( $condition ) === 'and' ) {
			$default = ' ( 1 = 1 ) ';
		} else if ( strtolower( $condition ) === 'or' ) {
			$default = ' ( 1 = 1 ) ';
		} else {
			throw new Exception( 'Condition needs to be AND or OR' ); // in the future maybe support more
		}

		if ( ! $groups || ! is_array( $groups ) ) {
			return $default;
		}

		if ( ! gp_is_array_depth_1( $groups ) ) {
			throw new Exception( 'Or group should be strictly a depth 1 array' );
		}

		// stuff is dynamic... some groups may be empty
		foreach ( $groups as $k=>$v ) {
			$v = trim( $v );
			if ( ! $v ) {
				unset( $groups[$k] );
			}
		}

		// great now we get to check again
		if ( ! $groups || ! is_array( $groups ) ) {
			return $default;
		}

		if ( count( $groups ) ===  1 ) {
			$inner = gp_array_first( $groups );
		} else {
			$inner = implode( ' ) ' . $condition . ' ( ', $groups );
		}

		$inner = trim( $inner );
		if ( $inner ) {
			$ret = '( ' . $inner . ' )';
		} else {
			$ret = $default;
		}

		return $ret;

	}

	/**
	 * @param $arr
	 */
	public function relation_group( $arr, $relation = 'AND' ) {

		// $arr will always eventually be a string when we call our function recursively
		// in that case, we return it, but only if its not empty. If its an empty string.. see below.
		if ( gp_is_singular( $arr ) && $arr ) {
			return $arr;
		}

		// this needs to mean ( if its an array and empty || if its a string and empty )
		// simply ! $arr will do the trick, but its not just being lazy, its intended
		if ( ! $arr ) {
			// hard to know for sure if this is best, but I think it makes sense
			// some sql statements could break if we said like...
			// AND condition1 AND condition2 AND {expecting conditions here} GROUP BY ... ORDER BY...
			if ( $relation === 'AND' ) {
				return '1 = 1';
			} else {
				return '1 = 2';
			}
		}

		$group = $arr;

		// relation might already be setup in the array
		$relation = gp_if_set( $arr, 'relation', $relation );
		$relation = strtoupper( $relation );
		if ( isset( $arr['relation'] ) ) {
			unset( $arr['relation'] );
		}

		$group = array();

		if ( $arr ) {
			foreach ( $arr as $k=>$v ) {

				if ( is_array( $v ) ) {
					$group[] = $this->relation_group( $v );
				} else {
					$group[] = $v;
				}
			}
		}

		if ( $relation === 'AND' ) {
			$ret = $this->and_group( $group );
		} else if ( $relation === 'OR' ) {
			$ret = $this->or_group( $group );
		} else {
			$ret = '...';
			// not sure yet
		}

		return $ret;
	}

	/**
	 * this is blowing my mind... but I think an and_group can be thought
	 * of as identical to a relation group... but an and_group cannot be nested,
	 * whereas a relation group can be nested. So when you make your own group,
	 * choosing a relation group with 'relation' => 'AND', seems identical to
	 * making an and_group, and it more or less is, because at the end of the day,
	 * the relation group will simply just wrap the "and_group" but only if each
	 * array value is singular. so ... relation group uses "relation groups and and/or groups",
	 * when they use and/or groups, those use condition groups, which are singular non-recursive
	 * groups essentially. Therefore just use a relation group always. I think this
	 * function can be private or protected, maybe that would help clarify things a little.
	 *
	 * @param array $conditions
	 */
	public function and_group( $conditions = array() ) {
		return $this->condition_group( $conditions, 'AND' );
	}

	/**
	 *
	 */
	public function or_group( $conditions = array() ){
		return $this->condition_group( $conditions, 'OR' );
	}

	/**
	 * "Update table_name" for lazy people.
	 *
	 * @param $table
	 *
	 * @return string
	 */
	public function update( $table ) {
		$table = gp_esc_db_col( $table );
		$str = 'UPDATE ' . $table;
		return $str;
	}
	/**
	 * @param        $data
	 * @param string $pre
	 *
	 * @return string
	 */
	public function set( $data, $pre = ':' ) {

		// 'col_name' => ':col_name'
		$params = $this->unbind( $data, $pre );

		// the.. array of strings.. which we need
		$str_array = array();

		if ( $params ) {
			foreach ( $params as $k=>$v ) {

				// should not be necessary
				$k = gp_esc_db_col( $k );

				// `col_name` = :col_name
				// if not using backticks (or dql quotes) then columns names with sql reserved strings cause errors
				$str_array[] = '`' . $k . '` = ' . $v;
			}
		}

		$str = '';
		$str .= 'SET ' . implode_comma(  $str_array );
		$str = gp_trim_comma_space( $str );
		return $str;
	}

	/**
	 * @param $data
	 * @param $pre_val
	 */
	public function where( $data, $pre = ':', $one_equals_one = true ) {

		// note: results of $set_args should be cleaned
		$set_args = $this->unbind( $data, $pre );
		$str = '';

		if ( $one_equals_one ) {
			$str .= 'WHERE 1 = 1 ';
		}

		if ( $set_args && is_array( $set_args ) ) {
			foreach ( $set_args as $k=>$v ) {
				$str .= 'AND ' . $k . ' = ' . $v . ' ';
			}
		}

		$str = gp_trim_comma_space( $str );
		return $str;
	}

	/**
	 * @param        $str
	 * @param string $pre
	 */
	public function make_param_string( $str, $pre = ':' ) {

		$pre = trim( $pre );

		if ( strpos( $pre, ':' ) === false ) {
			$pre = ':' . $pre;
		}

		$str = gp_esc_db_col( $str );
		$ret = $pre . $str;
		return $ret;
	}

	/**
	 * Just gives you back an array like: [ 'column_name' => ':column_name', ... ]
	 *
	 * Input is [ 'column_name' => '; DROP TABLE users;', ... ]
	 *
	 * Other functions rely on the fact that ALL output from this function is safe for SQL.
	 *
	 * $pre should obviously not come from user input.
	 *
	 * @param        $data
	 * @param string $pre
	 */
	public function unbind( $data, $pre = ':' ) {

		$pre = trim( $pre );
		if ( strpos( $pre, ':' ) === false ) {
			$pre = ':' . $pre;
		}

		$bindings = array();
		if ( $data && is_array( $data ) ) {
			foreach ( $data as $column=>$value ) {
				$value = null; // we're not putting $value in the output
				// ensure letter, numbers, and underscores only
				$c = gp_esc_db_col( $column );
				$bindings[$c] = $pre . $c;
			}
		}

		return $bindings;
	}
}