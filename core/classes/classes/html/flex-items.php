<?php

/**
 * Extend this class before use in most cases
 *
 * Class Flex_Items
 */
Class Flex_Items{

	/**
	 * html ID attribute
	 *
	 * @var
	 */
	public $id;

	/**
	 * css classes
	 *
	 * @var array
	 */
	public $classes;

	/**
	 * The flex items, where each item is an array, and should contain all the data
	 * necessary to render the html. In your own class which extends this one, the render_item() function
	 * should be the main one to override.
	 *
	 * @var  array
	 */
	public $items;

	public $item_defaults = array();

	/**
	 * default css class for item outer
	 *
	 * @var string
	 */
	public $item_outer_class;

	/**
	 * default css class for item inner
	 *
	 * @var string
	 */
	public $item_inner_class;

	/**
	 * Product_Loop_Flex constructor.
	 */
	public function __construct(){
		$this->items = array();
		$this->item_defaults = array();
		$this->id = '';
		// you can override these after calling parent::__construct() if you wish
		$this->classes = array('flex-items');
		$this->item_outer_class = 'flex-item-outer';
		$this->item_inner_class = 'flex-item-inner';
	}

	/**
	 * Items will be merged with this array before being rendered. Do this when all
	 * items should have something in common.
	 *
	 * @param $arr
	 */
	public function set_item_defaults( $arr ) {
		$this->item_defaults = $arr;
	}

	/**
	 * @param $id
	 */
	public function set_id( $id ){
		$this->id = $id;
	}

	/**
	 * @param $cls
	 */
	public function add_css_class( $cls ){
		$this->classes[] = $cls;
	}

	/**
	 * @param $item
	 */
	public function render_item_before( $item = array() ) {

		$cls = array(
			$this->item_outer_class,
		);

		$add_class = gp_if_set( $item, 'item_outer_add_class', '' );

		if ( $add_class ) {
			$cls[] = $add_class;
		}

		$op = '';
		$op .= '<div class="' . gp_parse_css_classes( $cls ) . '">';
		$op .= '<div class="' . $this->item_inner_class . '">';
		return $op;
	}

	/**
	 * @param $item
	 */
	public function render_item_after( $item = array() ) {
		$op = '';
		$op .= '</div>';
		$op .= '</div>';
		return $op;
	}

	/**
	 *
	 */
	public function render_item( $item = array() ){
		//  normally you can write the html inside of render_item, but you can also
		// just pass in plain html if its easier.
		return gp_if_set( $item, 'html' );
	}

	/**
	 * Return null to simply continue on as normal. Return empty string to render nothing.
	 * Otherwise, say no items were found or do whatever the #@&#$ you want.
	 */
	public function render_no_items(){
		return null;
	}

	/**
	 *
	 */
	public function render(){

		if ( ! $this->items ) {
			$ret = $this->render_no_items();
			// return null to continue rendering as normal.
			if ( $ret !== null ) {
				return $ret;
			}
		}

		$op = '';
		$op .= $this->render_opening_tag();
		$op .= '<div class="pl-flex">';

		if ( $this->items && is_array( $this->items ) ) {

			foreach ( $this->items as $item ) {

				// merge the defaults into the item
				if ( is_array( $item ) && is_array( $this->item_defaults ) ){
					$item = array_merge( $this->item_defaults, $item );
				}

				$op .= $this->render_item_before( $item );
				$op .= $this->render_item( $item );
				$op .= $this->render_item_after( $item );
			}
		} else {
			$op .= $this->not_found_html();
		}

		$op .= '</div>'; // pl-flex
		$op .= $this->render_closing_tag();

		return $op;
	}

	/**
	 *
	 */
	public function not_found_html(){
		$op = '';
		$op .= '<p class="not-found">No results</p>';
		return $op;
	}

	/**
	 * @param $item
	 */
	public function add_item( $item ) {
		$this->items[] = $item;
	}

	/**
	 * @param $str
	 */
	public function add_item_raw_html( $str ) {
		$this->add_item( array(
			'html' => $str,
		));
	}

	/**
	 * @return string
	 */
	public function render_opening_tag(){
		$op = '';
		$op .= '<div id="' . $this->id . '" class="' . gp_parse_css_classes( $this->classes ) . '">';
		return $op;
	}

	/**
	 * @return string
	 */
	public function render_closing_tag(){
		$op = '';
		$op .= '</div>';
		return $op;
	}
}