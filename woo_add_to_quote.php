<?php
/*
Plugin Name: Woo Add To Quote
Plugin URI: https://wpexperts.io
Description: This Plugin adds woocommerce add to quote functionality and much more...
Version: 1.2
Author: wpexpertsio
Author URI: https://wpexperts.io
*/

define('WATQ_PLUGIN_URL',plugin_dir_url( __FILE__ ) );

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Check if WooCommerce is active
 **/

function watq_check_if_woo_is_active(){

    if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        $class = "error";
        $message = __("Quote plugin requires Woocommerce plugin to be activated.", 'watq');
        echo"<div class=\"$class\"> <p>$message</p></div>";
    }
}
add_action('admin_init','watq_check_if_woo_is_active');

require_once('functions.php');

