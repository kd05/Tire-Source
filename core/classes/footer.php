<?php

Class Footer{

    // you can use this to add <script> tags if needed.
    public static $raw_html = [];

    // prints hidden in footer even in production. Only print things safe for public viewing.
    public static $print_hidden = [];

	public function __construct(){}

    /**
     * Pass in a callable that PRINTS its output.
     *
     * @param $callback
     * @param array $callback_args
     */
	public static function add_raw_html( $callback, array $callback_args = [] ) {
	    ob_start();
	    call_user_func_array( $callback, $callback_args );
	    self::$raw_html[] = ob_get_clean();
    }
}