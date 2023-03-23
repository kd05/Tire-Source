<?php

require_once 'flex-items.php';

/**
 * Class Product_Loop_Flex
 */
Class Product_Loop_Flex extends Flex_Items{

	/**
	 * Product_Loop_Flex constructor.
	 */
	public function __construct(){
		parent::__construct();

		// do this after calling parent::__construct() to override previously set values
		$this->classes = array('product-loop');
		$this->set_id( 'product-loop' );
		$this->item_outer_class = 'flex-item-outer';
		$this->item_inner_class = 'flex-item-inner';
	}
}
