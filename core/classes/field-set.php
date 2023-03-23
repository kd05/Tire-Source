<?php

/**
 * Generally speaking, this is an array of form items, but
 * its also just an array of items that have methods for rendering
 * and saving/validating etc., which by default do absolutely nothing.
 * So this could be considered to be nothing more than an array of
 * things which do other things.
 *
 * The intended usage however, is that each field set represents
 * a single editable page in the back-end, and allows an admin
 * user to edit fields such as title, content, and whatever else
 * we wish to include. The field set serves 2 main purposes:
 * render a form for an admin to fill out, and handle
 * the submission of that form to update the database. Retrieving
 * values from the database at a later time is not something
 * done here, and can be done directly with get_page_meta() for example.
 *
 * @see DB_Page
 *
 * Class Field_Set
 */
Class Field_Set implements Iterator {

	use Add_Messages_Trait;

	private $position = 0;

	/**
	 * An array of Field_Set_Items which have a circular
	 * reference to $this.
	 *
	 * @var
	 */
	public $fields;

	/**
	 * For a very general field set this obviously doesn't belong here. But for the
	 * field sets that serve our intended purpose of this class, we will need to make
	 * the page object accessible to the callback functions found within each
	 * field set item. Passing them in is one way, but accessing via parent
	 * also works and in some ways is better I think. Of course, a hidden input
	 * field with just the ID of the DB_Page would also work, but, I don't know,
	 * lets just pick one and go with it.
	 *
	 * @var DB_Page|null
	 */
	public $page;

	/**
	 * If using this for a DB_Page, make sure to set $this->page first.
	 *
	 * @return string
	 */
	public function render_fields() {

		$op = '';

		/** @var Field_Set_Item $field */
		foreach ( $this as $field ) {
			$op .= $field->render();
		}

		return $op;
	}

	/**
	 * If using this for a DB_Page, make sure to set $this->page first.
	 *
	 * @return bool
	 */
	public function save_fields() {

		// inside the validate method we can call $field->parent->add_msg() and return false.
		$valid = true;
		/** @var Field_Set_Item $field */
		foreach ( $this as $field ) {
			if ( ! $field->validate() ) {
				$valid = false;
			}
		}

		if ( ! $valid ) {
			$this->add_error( "Validation did not pass." );

			return false;
		}

		// we can also decide to pass on validation above, and
		// do our own validation type stuff inside of save().
		// for example, inside of the save callback, we can do
		// $field->parent->add_msg( "Error due to..."), and then simply not save the
		// value to the database. This gives us the effect of only updating
		// the fields without validation errors, which is something we may
		// sometimes prefer instead of always forcing all fields to be valid,
		// or updating nothing at all.
		foreach ( $this as $field ) {
			$field->save();
		}

		$this->success = true;
	}

	/**
	 * @param $item
	 */
	public function register( $item ) {
		$_item = $item instanceof Field_Set_Item ? $item : new Field_Set_Item( $item );
		$_item->set_parent( $this );
		$this->fields[] = $_item;
	}

	/**
	 * @return int
	 */
	public function count(): int
    {
		return count( $this->fields );
	}

	/**
	 * Return the current element
	 * @link  http://php.net/manual/en/iterator.current.php
     * @return mixed
	 * @since 5.0.0
	 */
	public function current(): mixed {
		return $this->fields[ $this->position ];
	}

	/**
	 * Move forward to next element
	 * @link  http://php.net/manual/en/iterator.next.php
	 * @return void Any returned value is ignored.
	 * @since 5.0.0
	 */
	public function next(): void
    {
		$this->position ++;
	}

	/**
	 * Return the key of the current element
	 * @link  http://php.net/manual/en/iterator.key.php
	 * @return mixed scalar on success, or null on failure.
	 * @since 5.0.0
	 */
	public function key(): mixed {
		return $this->position;
	}

	/**
	 * Checks if current position is valid
	 * @link  http://php.net/manual/en/iterator.valid.php
	 * @return boolean The return value will be casted to boolean and then evaluated.
	 * Returns true on success or false on failure.
	 * @since 5.0.0
	 */
	public function valid(): bool
    {
		return isset( $this->fields[ $this->position ] );
	}

	/**
	 * Rewind the Iterator to the first element
	 * @link  http://php.net/manual/en/iterator.rewind.php
	 * @return void Any returned value is ignored.
	 * @since 5.0.0
	 */
	public function rewind(): void {
		$this->position = 0;
	}
}

/**
 * Track errors, general message, and success messages.
 *
 * Naturally we would love to have a function or a variable for
 * whether or not an operation was successful, but in reality
 * the definition of success depends on specifics of the operation
 * and your definition of success. Your version of "success" might look like:
 *
 * $success = $this->has_errors() === false, or
 * $success = $this->has_errors() === false && $this->has_success_msgs() === true
 *
 * Trait Add_Messages_Trait
 */
Trait Add_Messages_Trait {

	private $_msgs = [];

	/**
	 * @param bool   $prefix_with_type
	 * @param string $types
	 *
	 * @return array
	 */
	public function get_messages( $prefix_with_type = false, $types = "__ALL__" ) {

		if ( $types === "__ALL__" ) {
			$msgs = $this->_msgs;
		} else {

			$types = gp_is_singular( $types ) ? [ $types ] : $types;
			assert( is_array( $types ) );

			$msgs = array_filter( $this->_msgs, function ( $msg ) use ( $types ) {
				return in_array( $msg[ 'type' ], $types );
			} );
		}

		// convert array to strings...
		$msgs = array_map( function ( $msg ) use ( $prefix_with_type ) {
			$m = $msg[ 'msg' ];
			$t = $msg[ 'type' ];

			return $prefix_with_type ? "[$t] $m" : $m;
		}, $msgs );

		return $msgs;
	}

	/**
	 * @param $msg
	 * @param $type
	 */
	private function add_msg( $msg, $type ) {

		assert( strlen( $type ) > 0 );
		assert( gp_is_singular( $msg ) );

		$this->_msgs[] = array(
			'msg' => $msg,
			'type' => $type,
		);
	}

	/**
	 * @return bool
	 */
	public function has_errors() {
		return count( $this->get_messages( false, 'error' ) ) > 0;
	}

	/**
	 * @return bool
	 */
	public function has_success_msg() {
		return count( $this->get_messages( false, 'success' ) ) > 0;
	}

	/**
	 * @return bool
	 */
	public function has_general_msgs() {
		return count( $this->get_messages( false, 'general' ) ) > 0;
	}

	/**
	 * @param $msg
	 */
	public function add_error( $msg ) {
		$this->add_msg( $msg, 'error' );
	}

	/**
	 * @param $msg
	 */
	public function add_success_msg( $msg ) {
		$this->add_msg( $msg, 'success' );
	}

	/**
	 * @param $msg
	 */
	public function add_general_msg( $msg ) {
		$this->add_msg( $msg, 'general' );
	}
}