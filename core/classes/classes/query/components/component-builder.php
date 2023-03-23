<?php

/**
 * This class is lower level code that all queries rely on. I recommend not touching it at all.

 * Class Component_Builder
 */
Class Component_Builder{

	protected $world; // not public "may?" be causing issues
	protected $elements;
	protected $parameters;

	/**
	 * Increment each time get_return() is called. I just want to make sure our recursion
	 * count is not blowing up exponentially.
	 *
	 * @var int
	 */
	protected static $recursion_counter;

	/**
	 * Note: recommend using self::create_new_instance() rather than __construct()
	 *
	 * Component_Builder constructor.
	 *
	 * @param World $world
	 * @param array $elements
	 * @param array $parameters
	 */
	public function __construct( World $world ){
		$this->world = $world;
		self::$recursion_counter = self::$recursion_counter !== null ? self::$recursion_counter : 0;
	}

	/**
	 * @return int
	 */
	public static function get_recursion_counter(){
		return self::$recursion_counter;
	}

	/**
	 * Get the first instance of the object... after that, most functions
	 * return new instances of the same class. Each instance that gets
	 * generated from an instance, carries over the "World" object, which
	 * ensures that newly created parameter names are unique among all
	 * offspring.
	 *
	 * @param $uid
	 * @param $counter
	 */
	public static function make_new_instance( $uid, $counter = null ) {
		$world = new World( $uid, $counter );
		return new static( $world );
	}

	/**
	 * @param $uid
	 * @param $counter
	 */
	public static function make_new_world( $uid, $counter = null ) {
		$world = new World( $uid, $counter );
		return $world;
	}

	/**
	 * Adds a simple relation to "self".. ie, adds to $this->elements, and to $this->parameters, without returning anything.
	 *
	 * Specifying a $key and a $relation ("AND"/"OR"), lets you call this function multiple times with the same key, which
	 * will create a sub-relation between the components added. Ie, you can say column = value, or call the function multiple times
	 * which ends up being, ( column = value1 OR column = value2 OR column = value3 )
	 *
	 * @param        $selector
	 * @param        $operator
	 * @param        $value
	 * @param string $type
	 * @param string $key
	 * @param string $relation
	 * @param string $description
	 */
	public function add_simple_relation( $selector, $operator, $value, $type = '', $key = '', $relation = '', $description = 'asr' ) {
		$new_component = $this->get_simple_relation( $selector, $operator, $value, $type, $description );
		$this->add_to_self( $new_component, array(), $relation, $key );
	}

	/**
	 * @param        $selector
	 * @param        $value
	 * @param string $rel
	 * @param string $key
	 * @param string $description
	 */
	public function get_simple_relation( $selector, $operator, $value, $type, $description = 'sr' ) {

		$p1 = $this->get_parameter_name( $description );
		// table.col = :parameter_name
		$condition = gp_esc_db_col( $selector ) . ' ' . $operator . ' ' . ':' . $p1;
		$params = array(
			$this->create_parameter( $p1, $value, $type ),
		);
		return $this->get_return( $condition, $params );
	}

	/**
	 * @param        $selector
	 * @param        $values
	 * @param string $type
	 */
	public function get_sql_not_in( $selector, $values, $type = '%s' ) {

		$values = force_non_indexed_array( $values, true );

		$params = array();
		$placeholders = array();
		if ( $values && is_array( $values ) ) {
			foreach ( $values as $value ) {
				$name = $this->get_parameter_name( 'sql_in' );
				$params[] = array( $name, $value, $type );
				$placeholders[] = ':' . $name;
			}
		}

		$selector = gp_esc_db_col( $selector );
		$ele = $selector . ' NOT IN (' . implode_comma(  $placeholders ) . ')';

		return $this->get_return( $ele, $params );
	}

	/**
	 * Returns a new instance of self for an sql statement like:
	 *
	 * table.column IN (:placeholder_1, :placeholder2, :placeholder3)
	 *
	 * If each value has a different type.. then I guess don't use this.
	 *
	 * @param $selector
	 * @param $values
	 */
	public function get_sql_in( $selector, $values, $type = '%s' ) {

		$values = force_non_indexed_array( $values, true );

		// this is pretty important since we sometimes call 'sql in' with an empty array or false
		// we need to first of all, not break sql, and second of all, probably make the query return
		// nothing. spitting out no sql statement at all is not valid. one example of this is where
		// we get an array of allowed tire speed ratings based on 1 provided. if the one provided
		// is not recognized, then the allow speed ratings will be an empty array. if this is the case
		// we cannot show all speed ratings. ideally, we want to show no tires (or tires without a speed rating,
		// which should be none). we could also return something like '1 = 2'.. but I think this is fine..
		if ( ! $values ) {
			$values = array( 0 => '' );
		}

		$params = array();
		$placeholders = array();
		// get multiple unique parameter names
		if ( $values && is_array( $values ) ) {
			foreach ( $values as $value ) {
				$name = $this->get_parameter_name( 'sql_in' );
				$params[] = array( $name, $value, $type );
				$placeholders[] = ':' . $name;
			}
		}

		$selector = gp_esc_db_col( $selector );
		$ele = $selector . ' IN (' . implode_comma(  $placeholders ) . ')';

		return $this->get_return( $ele, $params );
	}

	/**
	 * Note: this is protected. If you think you need to use it, think again...
	 * Use the get_return() method instead, and pass in your own $elements and $parameters.
	 * It will make a new instance for you, and then call set_elements() and set_parameters().
	 * If you can't call get_return() because you don't have an instance, then create a new instance
	 * (by injecting dependencies into __construct() or just use the static method).
	 *
	 * @param $elements
	 */
	protected function set_elements( $elements ) {
		$this->elements = $elements;
	}

	/**
	 * @return array
	 */
	public function elements(){
		// default could be empty array or string.. which should result in more or less
		// the same behaviour
		return $this->elements ? $this->elements : array();
	}

	/**
	 * Don't forget to get the parameters which need to be bound
	 * to the placeholders. Also, you probably want to prefix this with "AND "
	 */
	public function sql_with_placeholders(){

		$elements = $this->elements();

		if ( $elements ) {
			$bb = new Sql_Builder();
			$ret = $bb->relation_group( $elements );
		} else {
			$ret = '(1 = 1)';
		}
		return $ret;
	}

	/**
	 * This returns raw parameters which is an array of objects. Use parameters_array()
	 * when you end up binding your statement.
	 *
	 * @return array
	 */
	public function parameters(){
		$ret = is_array( $this->parameters ) ? $this->parameters : array();
		return $ret;
	}

	/**
	 * @param $parameters
	 */
	protected function set_parameters( $parameters ) {

		if ( ! $parameters ) {
			$this->parameters = array();
			return;
		}

		if ( ! is_array( $parameters ) ) {
			throw new exception( 'Invalid parameters' );
		}

		// empty our array
		$this->parameters = array();

		// convert $params that are arrays into Bind_Param objects (if they are valid)
		if ( is_array( $parameters ) ) {
			foreach ( $parameters as $param ) {
				$this->add_parameter( $param );
			}
		}
	}

	/**
	 * @param string $description
	 *
	 * @return mixed|string
	 */
	public function get_parameter_name( $description = '' ) {
		$str = $this->world->get_unique_string( $description );
		$str = gp_esc_db_col( $str );
		return $str;
	}

	/**
	 * @param        $name
	 * @param        $value
	 * @param string $type
	 *
	 * @return Bind_Param
	 */
	public function create_parameter( $name, $value, $type = '%s' ){
		return new Bind_Param( $name, $value, $type );
	}

	/**
	 * @param $param
	 */
	public function add_parameter( $param ) {

		if ( ! $param )
			return;

		if ( $param instanceof Bind_Param ) {
			$this->parameters[] = $param;
			return;
		}

		if ( is_array( $param ) ) {

			if ( array_keys_exist( $param, [ 0, 1 ] ) ) {

				// we could perhaps just do $this->parameters[] = $value.. but lets only add what we expect.
				$type = gp_if_set( $param, 2 ); // this one is optional
				$p_object = $this->create_parameter( $param[0], $param[1], $type );

				// in case create_parameter does its own validation
				if ( $p_object ) {
					$this->parameters[] = $p_object;
				} else {
					throw new Exception( 'Invalid parameter' );
				}

			} else {
				Throw new Exception( "Single parameter array in array of parameters is invalid." );
			}

			// avoid exception
			return;
		}

		Throw new Exception( "Cannot add a parameter that's not an array or of the proper instance." );
	}

	/**
	 * @param $params
	 */
	public function merge_parameters( $params ) {

		if ( ! $params )
			return;

		if ( ! is_array( $params ) ){
			throw new Exception ("Params must be an array, where each element is a parameter array, or a parameter object" );
		}

		// throw exceptions
		foreach ( $params as $key=>$value ) {
			$this->add_parameter( $value );
		}
	}

	/**
	 * Export parameters in an array format that we use to bind parameters via PDO. Its just a simple
	 * array of arrays. Each sub-array is a non-indexed array of [ $name, $value, $type ] where $type is optional
	 *
	 * @return array
	 */
	public function parameters_array(){

		$ret = array();

		if ( $this->parameters && is_array( $this->parameters ) ) {

			/** @var Bind_Param $p */
			foreach ( $this->parameters as $p ) {

				if ( $p instanceof Bind_Param ) {
					$ret[] = [ $p->name, $p->value, $p->type ];
					continue;
				}

				throw new Exception ('invalid param ??' );
			}
		}

		return $ret;
	}

	/**
	 * @param        $selector, column, or table.column
	 * @param        $value
	 * @param        $type
	 * @param string $rel
	 * @param string $description
	 *
	 * @return Component_Builder
	 */
	public function simple_relation( $selector, $value, $type, $rel = '<', $description = 'sr' ){

		$selector = gp_esc_db_col( $selector );
		$name = $this->get_parameter_name( $description );

		$params = array();
		$params[] = $this->create_parameter( $name, $value, $type );

		$str = $selector . ' ' . $rel . ' :' . $name;
		return $this->get_return( $str, $params );
	}

	/**
	 * Note: Never add self to self... param names get re-used and this breaks PDO.
	 *
	 * @param        $component
	 * @param string $relation
	 *
	 * @throws Exception
	 */
	public function add_to_self( $component, $params = array(), $relation = 'AND', $key = false ) {

		// allow the component to be a simple array.
		if ( ! $component instanceof $this ){
			$component = $this->get_return( $component, $params );
		} else {
			// if $component is an instance of self, its unlikely we'll have $params passed in, but
			// its totally possible.
			$this->merge_parameters( $params );
		}

		$elements = $this->elements();

		if ( $relation ) {
			if ( $key ) {
				$elements[$key]['relation'] = $relation;
			} else {
				$elements['relation'] = $relation;
			}
		}

		// key can be used to write a function to set a value, and then make it so that calling it
		// more than once creates a nested AND or OR relation
		if ( $key ) {
			$elements[$key][] = $component;
		} else {
			$elements[] = $component;
		}

		// create a new component which will merge all elements and all parameters from elements
		// this is because elements can be instances of self, which themselves contain elements and parameters.
		$new_self = $this->get_return( $elements );

		// now we just update our own properties based on the "new_self", so we modify the existing
		// object rather than returning a new one.
		$this->set_elements( $new_self->elements() );

		// have to merge params from new_self, not simply add them.
		$this->merge_parameters( $new_self->parameters() );
	}

	/**
	 * Be very careful when merging instances!!!! If you merge the same instances on themselves,
	 * then parameter names will get repeated. Instead... build a "copy" of the same instance which
	 * will have identical "conditions" but in fact have different parameter placeholders. (ie. :param_1, :param_2)
	 *
	 * @param $instance_1
	 * @param $instance_2
	 */
	public function merge_instances( $instance_1, $instance_2, $relation = 'AND' ) {
		$arr = array( $instance_1, $instance_2 );
		$ret = $this->merge_array( $arr,  $relation );
		return $ret;
	}

	/**
	 * @param array  $instances
	 * @param string $relation
	 *
	 * @return Component_Builder
	 */
	public function merge_array( array $instances, $relation = 'AND' ){

		$elements = array();

		if ( $relation ) {
			$elements['relation'] = $relation;
		}

		// here we could extract all the elements from all the instances..
		// or simply build an array of instances and pass those into get_return(),
		// this way, get_return() handles extracting and combining parameters from each instance
		foreach ( $instances as $instance ) {
			if ( $instance instanceof $this ) {
				$elements[] = $instance;
			}
		}

		return $this->get_return( $elements );
	}

	/**
	 * Loop through all elements of $elements. For each one, if its an instance of self,
	 * We want to get that instances $elements and $parameters and store them in $e, and $p. Then
	 * at the end, we'll accumulate all $elements and all $parameters from all of $elements elements (seriously).
	 * Finally, return a new instance of self from the resulting list of all sub-contained $elements and
	 * $parameters. The fun part is, this is done every single time we call a method that is meant to return
	 * elements and parameters. This way, methods that return elements and parameters return an instance of self
	 * instead, and that instance contains elements that can be other instances of self that contain more elements that
	 * could be more instances of self and etc. etc. etc. This basically means, we can put the return value of methods that return instances
	 * of self, right into the $elements of another method that returns an instance of self.
	 *
	 * This function is hard to understand, and extremely important to every single query and every single
	 * page on the website. I highly recommend not even placing your cursor inside of it.
	 *
	 * @param       $elements
	 * @param array $parameters
	 *
	 * @return Component_Builder
	 */
	public function get_return( $elements, $parameters = array() ) {

		self::$recursion_counter++;

		// in reality this actually stays quite low. I believe it only ever counts once for each
		// index in a multi-dimensional array, as opposed to the number of indexes in the array multiplied by
		// the number of indexes in the array, if that makes any sense.
		if ( self::$recursion_counter > 5000 ) {
			Throw new Exception( 'Probable infinite loop in Component_Builder' );
		}

		$e = array();
		$p = array();

		// sometimes instead of parameters being passed in, $elements are instances of self, which contain parameters themselves
		// but the lowest level of components (almost always) have parameters, and higher level components that use lower level components
		// still need the option to add their own parameters in addition to the other components.
		if ( $parameters ) {
			$p = array_merge( $parameters, $p );
		} else {
			$p = array();
		}

		if ( gp_is_singular( $elements ) ) {
			$ret = new self( $this->world );
			// elements doesn't have to be an array, however, array with 1 string element (probably) ends up
			// being the same as a string
			$ret->set_elements( $elements );
			$ret->set_parameters( $p );
			return $ret;
		}

		// get rid of empty string and empty array elements of $elements
		$elements = array_filter( $elements );

		// Loop
		if ( $elements && is_array( $elements ) ) {
			foreach ( $elements as $key=>$element ) {

				if ( is_array( $element ) ) {
					$element = array_filter( $element );
				}

				// empty string or empty array
				if ( ! $element ) {
					continue;
				}

				$key_integer = gp_is_integer( $key );
				$element_singular = gp_is_singular( $element );

				// ie. 'relation' => 'AND,  ... or ... 'relation' => 'OR',
				if ( $key === 'relation' && $element_singular ) {
					$e[$key] = $element;
					continue;
				}

				if ( $element_singular ) {
					if ( $key_integer ) {
						$e[] = $element;
					} else {
						$e[$key] = $element;
					}
					continue;
				}

				// $class = get_class( $this );
				if ( $element instanceof $this ){

					$__e = $element->elements();
					$__p = $element->parameters();

					// now the (ridiculous) recursion
					$__self = $this->get_return( $__e, $__p );

					// merge the parameters
					$p = array_merge( $__p, $p );

					// add the elements
					if ( $key_integer ) {
						$e[] = $__self->elements();
					} else {
						$e[$key] = $__self->elements();
					}

					continue;
				}

				if ( is_array( $element ) ) {

					// here, we pass an empty array of parameters. if sub-elements
					// contain instances of self, which contain more parameters, they will get
					// picked up, and we can merge them afterwards.
					$__self = $this->get_return( $element, array() );

					// merge any parameters that may have been picked up
					$p = array_merge( $__self->parameters(), $p );

					// add the elements
					if ( $key_integer ) {
						$e[] = $__self->elements();
					} else {
						$e[$key] = $__self->elements();
					}

					continue;
				}

				throw_dev_error( 'Should not get to here in foreach loop [TEMPORARY]' );
			}
		}

		// make new instance, but transfer the world object over
		$ret = new self( $this->world );
		$ret->set_elements( $e );
		$ret->set_parameters( $p );
		return $ret;
	}

}

/**
 * Class Bind_Param
 */
Class Bind_Param{

	public $name;
	public $value;
	public $type;

	/**
	 * Bind_Param constructor.
	 *
	 * @param        $name
	 * @param        $value
	 * @param string $type
	 */
	public function __construct( $name, $value, $type = '%s' ){
		$this->name = $name;
		$this->value = $value;
		$this->type = $type;
	}

	/**
	 * @return array
	 */
	public function export(){
		$ret = [ $this->name, $this->value, $this->type ];
		return $ret;
	}
}