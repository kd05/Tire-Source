<?php

/**
 * Generates unique strings. This name is ridiculous.
 * Think of it like, query component objects that have the
 * same world object, can use the world they live in to ensure
 * uniqueness of parameter names.
 *
 * Class World
 */
Class World{

	protected static $instances;

	protected $uid;
	protected $counter;

	/**
	 * World constructor.
	 *
	 * @param $uid
	 * @param $counter
	 */
	public function __construct( $uid, $counter = null ) {
		$this->counter = $counter !== null && gp_is_integer( $counter ) ? $counter : 0;
		$this->uid = $uid;
	}

	/**
	 * This should not only return a unique string, but also ensure that
	 * the unique strings it returns are not composed of other unique strings that it
	 * may have returned at other times. this is not necessary for the code to work, but we
	 * have our own debug_pdo_statement() function which will run a string replace on an
	 * sql string with placeholders + an array of parameters to bind. Our function isn't
	 * smart enough to identify param names that are sub strings of other param names, which
	 * when trying to debug things, can lead to some pretty serious confusion.
	 *
	 * @param string $description
	 */
	public function get_unique_string( $description = '' ) {

		$this->counter++;

		// note: avoid using dashes. pdo will throw exception.

		$pre = 'uniq_' . $this->counter . '_';

		if ( $description ) {
			$str = $this->uid . '_' . $description . '_' . $this->counter;
		} else {
			$str = $this->uid . '_' . $this->counter;
		}

		$str = $pre . $str;

		return $str;
	}

	/**
	 *
	 * @param      $uid
	 * @param null $counter
	 */
	public static function get_existing( $uid  ) {
		$cls = get_called_class();
		if ( isset( static::$instances[$uid] ) && static::$instances[$uid] instanceof $cls ) {
			return static::$instances[$uid];
		}
	}
}
