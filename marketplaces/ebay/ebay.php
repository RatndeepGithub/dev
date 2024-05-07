<?php

/* DEFINE CONSTANTS */
define( 'CED_EBAY_DIRPATH', plugin_dir_path( __FILE__ ) );
define( 'CED_EBAY_URL', plugin_dir_url( __FILE__ ) );
define( 'CED_EBAY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );




/**
* This file includes core functions to be used globally in plugin.
 *
* @link  https://woocommerce.com/vendor/cedcommerce
*/
require_once plugin_dir_path( __FILE__ ) . 'includes/ced-ebay-core-functions.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_ebay() {
	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-ebay-integration.php';
	$plugin = new EBay_Integration_For_Woocommerce();
	$plugin->run();
}
