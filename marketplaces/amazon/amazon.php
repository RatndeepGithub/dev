<?php

/* DEFINE CONSTANTS */
define( 'AMAZON_INTEGRATION_FOR_WOOCOMMERCE_VERSION', '1.0.0' );
define( 'CED_AMAZON_DIRPATH', plugin_dir_path( __FILE__ ) );
define( 'CED_AMAZON_URL', plugin_dir_url( __FILE__ ) );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-amazon-integration-for-woocommerce.php';


/**
* This file includes core functions to be used globally in plugin.
 *
* @link  http://www.cedcommerce.com/
*/
require_once plugin_dir_path( __FILE__ ) . 'includes/ced-amazon-core-functions.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_amazon() {

	$plugin = new Amazon_Integration_For_Woocommerce();
	$plugin->run();
}
