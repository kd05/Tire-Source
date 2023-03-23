<?php

require_once 'product-loop-flex.php';

/**
 * Class Product_Loop_Flex_Packages
 */
Class Product_Loop_Flex_Packages extends Product_Loop_Flex{

	public function __construct(){
		parent::__construct();
		$this->add_css_class( 'product-loop-packages' );
		$this->set_item_defaults( array(
			'item_outer_add_class' => 'type-package',
		));
	}

	/**
	 * The params needed to render an $item is potentially too highly dependant
	 * on the context of where it is being called from, therefore, just pass an html
	 * element into the item, and that will be how its rendered.
	 *
	 * @param array $item
	 */
	public function render_item( $item = array() ) {
		$op = '';
		$op .= gp_if_set( $item, 'html' );
		return $op;
	}

	/**
	 * @param array $item
	 *
	 * @return string
	 */
	public function render_item_before( $item = array() ){
		return '';
	}

	/**
	 * @param array $1item
	 *
	 * @return string
	 */
	public function render_item_after( $item = array() ){
		return '';
	}
}