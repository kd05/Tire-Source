<?php
/**
 * Javascript just hits this file directly. We could probably add /ajax to the router
 * but right now I think DOING_AJAX has to be defined before loading _init.php, so
 * for now its easiest to leave this as is.
 */

define( 'DOING_AJAX', true );

include 'core/_init.php';

Ajax::serve( Ajax::get_routes() );
