<?php

/**
 * Each Field_Set_Item represents a single form field,
 * and has methods to render, validate, and save the field.
 *
 * Class Field_Set_Item
 */
Class Field_Set_Item {

	use Has_Callbacks_Array;

	/**
	 * circular dependency
	 *
	 * @var Field_Set
	 */
	public $parent;
	public $callbacks;
	public $args;

	/**
	 * Field_Set_Item constructor.
	 *
	 * @param $args
	 */
	public function __construct( $args, $callbacks = [], $parent = null ) {
		$this->args      = $args;
		$this->callbacks = $callbacks;

		if ( $parent instanceof Field_Set ) {
			$this->set_parent( $parent );
		}
	}

	/**
	 * Static method to get an instance with easier to use parameters.
	 *
	 * Injects $render, $save, $validate into $callbacks array
	 *
	 * @param       $render
	 * @param       $save
	 * @param null  $validate
	 * @param array $args
	 * @param array $callbacks
	 * @param null  $parent
	 */
	public static function instance_from_callbacks( $render, $save = null, $validate = null, $args = [], $callbacks = [], $parent = null ) {
		$callbacks[ 'render' ]   = gp_if_set_and_not_empty( $callbacks, 'render', $render );
		$callbacks[ 'save' ]     = gp_if_set_and_not_empty( $callbacks, 'save', $save );
		$callbacks[ 'validate' ] = gp_if_set_and_not_empty( $callbacks, 'validate', $validate );

		return new self( $args, $callbacks, $parent );
	}

	/**
	 * @return bool|mixed
	 */
	public function get_form_name() {
		return gp_if_set( $this->args, 'name', gp_if_set( $this->args, 'meta_key', '' ) );
	}

	/**
	 * @param Field_Set $obj
	 */
	public function set_parent( Field_Set $obj ) {
		$this->parent = $obj;
	}

	/**
	 * @return bool|mixed
	 */
	public function get_registered_callbacks_array() {
		return $this->callbacks ? $this->callbacks : [];
	}

	/**
	 *
	 */
	public function save() {
		if ( $this->is_callable( 'save' ) ) {
			return $this->call( 'save', [ $this ] );
		}
	}

	/**
	 * @return mixed|null
	 */
	public function validate() {
		if ( $this->is_callable( 'validate' ) ) {
			return $this->call( 'validate', [ $this ] );
		}

		// this is a very important default, so that unless
		// we specify otherwise, forms can be saved..
		return true;
	}

	/**
	 *
	 */
	public function render() {
		if ( $this->is_callable( 'render' ) ) {
			return $this->call( 'render', [ $this ] );
		}
	}
}

/**
 * Trait Has_Callbacks_Array
 */
Trait Has_Callbacks_Array {

	/**
	 * Override in the class using this trait.
	 *
	 * @return bool|mixed
	 */
	public function get_registered_callbacks_array() {
		return array();
	}

	/**
	 * @param $name
	 *
	 * @return bool
	 */
	public function is_callable( $name ) {
		$cbs = $this->get_registered_callbacks_array();

		return isset( $cbs[ $name ] ) && is_callable( $cbs[ $name ] );
	}

	/**
	 * Call a callback function found in $this->args['cb'].
	 *
	 * Check $this->is_callable() first, so you can handle if its not set.
	 *
	 * @param       $name
	 * @param array $parameters
	 */
	public function call( $name, array $parameters ) {

		$cbs = $this->get_registered_callbacks_array();

		if ( isset( $cbs[ $name ] ) ) {
			return call_user_func_array( $cbs[ $name ], $parameters );
		}

		return null;
	}
}