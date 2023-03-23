<?php
/**
 * Holds some functions for creating the database tables.
 *
 * Could call via CLI when deploying.
 */

/**
 * When you drop/empty/create the users table you can call this
 * from cli to re-insert an admin user.
 *
 * @param $email
 * @param $pw
 * @param string $fname
 * @param string $lname
 * @return bool|string
 * @throws Exception
 */
function init_users_table($email, $pw, $fname = '', $lname = ''){
    return insert_user_direct( $email, $pw, $fname, $lname, true );
}

/**
 * For instructions on how to use this function, @see DOING_DB_INIT
 *
 * (sometimes you can use it easily, but other times you can't).
 */
function init_db(){

    // map table name (string) to class name for the "model"
    $classes = array_values( DB_table::get_table_class_map() );

    // attempt to create all tables in the correct order to satisfy foreign key constraints..

    // **** NOTE: A couple of things below are out of order.. if you get some
    // exceptions around malformed sql constraints, just run the entire process
    // again and all tables should eventually get inserted. ****
    $sort_order = [
        'DB_Supplier',
        'DB_Tire_Brand',
        'DB_Tire_Model',
        'DB_Tire',
        'DB_Rim_Brand',
        'DB_Rim_Model',
        'DB_Rim_Finish',
        'DB_Rim',
        'DB_User',
        'DB_Transaction',
        'DB_Order',
        'DB_Order_Vehicle',
        'DB_Order_Item',
        'DB_Order_Email',
        'DB_Review',
        'DB_Region',
        'DB_Tax_Rate',
        'DB_Shipping_Rate',
        'DB_Page',
        'DB_Page_Meta',
    ];

    $classes_sorted = array_values( array_unique( array_merge( array_intersect( $sort_order, $classes ), $classes )));

    $results = array_map( function( $class ){

        try{
            $result = DB_Table::db_init_create_table_via_class_name( $class );

            $text = $result ? "CREATED (or already existed?)" : "NOT_CREATED (because it already existed?)";

        } catch ( Exception $e ){
            $text = "Exception: " . $e->getMessage();
        }

        return "$class: $text";
    }, $classes_sorted );

    print_r( $results );
}