<?php

/* DEFINE CONSTANTS */
define( 'CED_ETSY_DIRPATH', plugin_dir_path( __FILE__ ) );
define( 'CED_ETSY_URL', plugin_dir_url( __FILE__ ) );
define( 'CED_ETSY_ABSPATH', untrailingslashit( plugin_dir_path( __DIR__ ) ) );


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woocommmerce-etsy-integration.php';
/**
* This file includes core functions to be used globally in plugin.
*/
require_once plugin_dir_path( __FILE__ ) . 'includes/ced-etsy-core-functions.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_etsy() {

	$plugin = new Woocommmerce_Etsy_Integration();
	$plugin->run();
}
