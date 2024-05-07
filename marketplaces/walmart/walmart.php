<?php

define( 'WALMART_WOOCOMMERCE_INTEGRATION_VERSION', '2.1.5' );
define( 'CED_WALMART_DIRPATH', plugin_dir_path( __FILE__ ) );
define( 'CED_WALMART_URL', plugin_dir_url( __FILE__ ) );
define( 'CED_WALMART_ABSPATH', untrailingslashit( plugin_dir_path( __DIR__ ) ) );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-walmart-woocommerce-integration.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/ced-walmart-core-functions.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_walmart() {

	$plugin = new Walmart_Woocommerce_Integration();
	$plugin->run();
}
