<?php

/**
 * A class for objects that aren't actually database tables, but should behave similar
 * to our other classes that to represent a single row from an existing table.
 * These classes may one day become database tables, and if they do, will
 * significantly reduce the amount of changes we have to make to the code
 * to account for this.
 *
 * As an example, take Tire Type. It has 4 options: 'winter', 'summer', 'all-season', 'all-weather'.
 * We're not creating a new database table for this. Instead we'll have an object with 2 fields: 'slug'
 * and 'name'. We'll make a static "factory" method, that takes in a slug, and returns an object
 * which contains the name. The name can be hardcoded, and doesn't need to be stored in the database.
 *
 * Class Virtual_DB_Table
 */
Class DB_Virtual_Table extends DB_Table{

	public function __construct( $data, $options = array() ) {
		parent::__construct( $data, $options );
	}

	/**
	 * Override this.
	 *
	 * Think of this as the return value of querying all rows in a (relatively small) table, but indexed
	 * by their primary key (which is often a "slug", and not an integer)
	 *
	 * @return array
	 */
	public static function get_all_data(){
		return array();
	}

	/**
	 * No guarantee this works on all "DB_Virtual_Table" objects, but for most of them
	 * it should make sense.
	 *
	 * @param $slug
	 */
	public static function slug_valid( $slug ) {
		$data = static::get_all_data();
		return in_array( $slug, array_keys( $data ) );
	}

	/**
	 * No guarantee this works on all "DB_Virtual_Table" objects, but for most of them
	 * it should make sense. You can check static::slug_valid() first if you want.
	 *
	 * @param $slug
	 */
	public static function create_instance_via_slug( $slug, $options = array() ) {

		$slug = gp_test_input( $slug );
		$data = static::get_all_data();
		$this_data = gp_if_set( $data, $slug, array() );

		if ( ! isset( $this_data['slug'] ) ) {
			$this_data['slug'] = $slug;
		}

		// if we're calling this function its safe to assume the object should have a name,
		// but even if it doesn't that's fine, this will end up doing nothing.
		if ( ! isset( $this_data['name'] ) ) {
			$this_data['name'] = $slug;
		}

		return new static( $this_data, $options );
	}
}

