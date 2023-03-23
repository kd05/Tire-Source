<?php

Class HtmlEmail {

	/**
	 * @param $items
	 */
	public static function prop_value_pairs( array $items ) {

		$op = '';
		$op .= '';
		$op .= '';
		$op .= '';

		if ( $items ) {
			foreach ( $items as $item ) {

				$prop  = gp_if_set( $item, 'prop' );
				$value = gp_if_set( $item, 'value' );

				if ( ! $prop && ! $value ) {
					continue;
				}

				$op .= '<p style="margin: 4px 0 4px 0; padding: 0;">';
				if ( $prop ) {
					$op .= '<strong>' . $prop . ': </strong>';
				}
				$op .= $value;
				$op .= '</p>';
			}
		}

		return $op;
	}

	/**
	 * @param $text
	 */
	public static function header( $text ) {
		return '<h2>' . $text . '</h2>';
	}

	public static function th() {
		return '<th style="padding: 5px; border: 1px solid grey;">';
	}

	public static function _th() {
		return '</th>';
	}

	public static function td() {
		return '<td style="padding: 5px; border: 1px solid grey;">';
	}

	public static function _td() {
		return '</td>';
	}

	public static function tr() {
		return '<tr>';
	}

	public static function _tr() {
		return '</tr>';
	}

	public static function t_line() {
		return '<span style="display: block; margin: 3px 0 3px 0;">';
	}

	public static function _t_line() {
		return '</span>';
	}
}
