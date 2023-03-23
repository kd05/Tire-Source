<?php
/*
 * Plugin Name: Click It Wheels - MU setup
 * Author: GeekPower
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Auto update minor WP versions
 */
define( 'WP_AUTO_UPDATE_CORE', 'minor' );

/**
 * Enable auto updates of all plugins.. wouldn't normally do this,
 * but for now if we have a very small number of plugins it might
 * be better. If it causes issues.. it might be good to shut this off.
 */
add_filter( 'auto_update_plugin', '__return_true' );

define( 'CW_IS_WP_BLOG', true );