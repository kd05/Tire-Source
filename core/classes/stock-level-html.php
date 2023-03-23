<?php

/**
 * Ie. <span class="stock-level-indicator s-level-none">......</span>
 *
 * The inner html contains an icon and some text.
 *
 * Class Stock_Level_Html
 */
Class Stock_Level_Html{

	/**
     * @param $indicator - ie. STOCK_LEVEL_NO_STOCK or similarly named constant
     * @param $qty
     * @param $product_type
     * @param $use_sets
     * @return string
     */
	public static function render( $indicator, $qty, $product_type, $use_sets ) {
		$text = self::text( $indicator, $qty, $product_type, $use_sets );
		$icon = self::icon( $indicator );
		$add_class = self::css_class( $indicator );

		if ( ! IN_PRODUCTION ) {
			$text .= ' (' . $qty . ')';
		}

		return self::render_via_html_components( $add_class, $icon, $text );
	}

    /**
     * Simpler more easy to customize way to render.
     *
     * @param $indicator
     * @param $text
     * @return string
     */
	public static function render_alt( $indicator, $text ) {

        $icon = self::icon( $indicator );
        $add_class = self::css_class( $indicator );

        return self::render_via_html_components( $add_class, $icon, $text );
    }

	/**
	 * You may want to use this instead of ->render() if you need to customize things.
	 *
	 * @param $add_class
	 * @param $icon
	 * @param $text
	 *
	 * @return string
	 */
	public static function render_via_html_components( $add_class, $icon, $text ) {

		if ( ! $icon && ! $text ) {
			return '';
		}

		$cls = ['stock-level-indicator'];
		$cls[] = $add_class;

		$inner = '';

		// ie. <span class="stock-level s-level-low"><span class="icon">i.fa.fa...</span><span class="text">3 sets remaining</span>
		$inner .= '<span class="sl-bg">';
		$inner .= ! $icon ? '' : html_element( $icon, 'span', 'sl-icon' ) . ' ';
		$inner .= html_element( $text, 'span', 'sl-text' );;
		$inner .= '</span>';

		$ret = html_element( $inner, 'span', $cls );
		return $ret;
	}

	/**
	 * You must pass in indicator.
	 *
	 * If indicator if STOCK_LEVEL_LOW_STOCK or STOCK_LEVEL_SEMI_OUT_OF_STOCK,
	 * then you must pass in $qty also.
	 *
	 * Indicator cannot properly be determined from $qty alone, as it depends on context.
	 *
	 * Product type: 'tires' or 'rims', plural.
	 *
	 * Use Sets: Say 3 sets remaining, or 13 tires/rims/products remaining?
	 *
	 * @see Vehicle_Query_Database_Row::get_item_stock_amount_html()
	 *
     * @param $indicator
     * @param $qty
     * @param $product_type
     * @param $use_sets
     * @return string
     */
	public static function text( $indicator, $qty, $product_type, $use_sets ) {

		switch( $indicator ) {
			case STOCK_LEVEL_NO_STOCK:
				return 'Out of stock';
				break;
			case STOCK_LEVEL_SEMI_OUT_OF_STOCK:
			case STOCK_LEVEL_LOW_STOCK:
				return self::items_remaining_text( $qty, $product_type, $use_sets );
				break;
			case STOCK_LEVEL_IN_STOCK:
				return 'In stock';
				break;
		}

		return '';
	}

	/**
	 * @param $qty
	 *
	 * @return string
	 */
	public static function items_remaining_text( $qty, $type, $use_sets ){

		if ( $qty === STOCK_VALUE_MEANING_UNLIMITED_IN_PHP_STRING ) {
			return 'In stock';
		}

		// pass in the plural version of tire/rim because, that's just how we're going to do it.
		switch( $type ) {
			case 'tires':
				$products = 'tires';
				$product_singular = 'tire';
				break;
			case 'rims':
				$products = 'rims';
				$product_singular = 'rim';
				break;
			default:
				$products = 'products';
				$product_singular = 'product';
		}

		$ret = '';

		$qty = (int) $qty;

		// NOTE: we're probably going to write "Out of stock" instead here,
		// but, not within this function. If you call this function, its gonna show the number.
		if ( $qty <= 0 ) {
			$ret .= '0 ' . $products . ' remaining';
		} else if ( ! $use_sets || ( $qty > 0 && $qty < 4) ) {

			if ( $qty == 1 ) {
				$ret = $qty . ' ' . $product_singular . ' remaining';
			} else {
				$ret = $qty . ' ' . $products . ' remaining';
			}

		} else {

			$sets = intval( floor( $qty / 4 ) );

			if ( $sets == 1 ) {
				$ret = $sets . ' set remaining';
			} else {
				$ret = $sets . ' sets remaining';
			}

		}

		return $ret;
	}

	/**
	 * @param $indicator
	 */
	public static function icon( $indicator ){

		switch( $indicator ) {
			case STOCK_LEVEL_NO_STOCK:
				$ret = '<i class="fas fa-exclamation-triangle"></i>';
				break;
			case STOCK_LEVEL_SEMI_OUT_OF_STOCK:
				// make this look slightly different, I guess
				$ret = '<i class="fas fa-exclamation-circle"></i>';
				break;
			case STOCK_LEVEL_LOW_STOCK:
				$ret = '<i class="fas fa-exclamation-triangle"></i>';
				break;
			case STOCK_LEVEL_IN_STOCK:
				$ret = '<i class="far fa-check-square"></i>';
				break;
			default:
				$ret = '';
		}

		return $ret;

	}

	/**
	 * @param      $cat
	 * @param bool $has_bg
	 *
	 * @return string
	 */
	public static function css_class( $indicator ) {

		switch( $indicator ) {
			case STOCK_LEVEL_NO_STOCK:
				$add_class = 's-level-none';
				break;
			case STOCK_LEVEL_SEMI_OUT_OF_STOCK:
				$add_class = 's-level-semi-none';
				break;
			case STOCK_LEVEL_LOW_STOCK:
				$add_class = 's-level-low';
				break;
			case STOCK_LEVEL_IN_STOCK:
				$add_class = 's-level-in';
				break;
			default:
				$add_class = '';
		}

		// $add_class .= $has_bg ? ' has-bg' : ' no-bg';

		return $add_class;
	}

}
