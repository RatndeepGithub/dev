<?php

/**
 * Multichannel by CedCommerce
 *
 * @link              https://woocommerce.com/vendor/cedcommerce/
 * @since             1.0.0
 * @package           Multichannel_By_Cedcommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Multichannel by CedCommerce
 * Plugin URI:        https://woocommerce.com/vendor/cedcommerce/
 * Description:       Connect your WooCommerce Store to multiple marketplaces like eBay, Amazon, Walmart and Etsy.
 * Version:           1.0.2
 * Author:            CedCommerce
 * Author URI:        https://woocommerce.com/vendor/cedcommerce/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       multichannel-for-woocommerce
 *
 * Woo: 18734002690668:28780bb681d237d76373db743fca127a
 * WC requires at least: 4.0
 * WC tested up to: 8.1.1
 *
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'MULTICHANNEL_BY_CEDCOMMERCE_VERSION', '1.0.2' );
define( 'CED_MCFW_DIRPATH', plugin_dir_path( __FILE__ ) );
define( 'CED_MCFW_URL', plugin_dir_url( __FILE__ ) );
define( 'CED_MCFW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'CED_MCFW_API_URL', 'https://api.cedcommerce.com/mcfw-backend/api/v1/' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-multichannel-for-woocommerce-activator.php
 */
function activate_multichannel_by_cedcommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-multichannel-for-woocommerce-activator.php';
	Multichannel_By_Cedcommerce_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-multichannel-for-woocommerce-deactivator.php
 */
function deactivate_multichannel_by_cedcommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-multichannel-for-woocommerce-deactivator.php';
	Multichannel_By_Cedcommerce_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_multichannel_by_cedcommerce' );
register_deactivation_hook( __FILE__, 'deactivate_multichannel_by_cedcommerce' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-ced-mcfw-core-functions.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-multichannel-for-woocommerce.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_multichannel_by_cedcommerce() {

	$plugin = new Multichannel_By_Cedcommerce();
	$plugin->run();
}

/**
 * Check WooCommmerce active or not.
 *
 * @since 1.0.0
 */
function ced_woocommerce_active() {
	/** Get active plugin list
	 *
	 * @since 1.0.0
	 */
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		return true;
	}
	return false;
}

/**
 * This code runs when WooCommerce is not activated,
 *
 * @since 1.0.0
 */
function deactivate_ced_mcfw_woo_missing() {
	deactivate_plugins( CED_MCFW_PLUGIN_BASENAME );
	add_action( 'admin_notices', 'ced_mcfw_woo_missing_notice' );
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
}
/**
 * Callback function for sending notice if woocommerce is not activated.
 *
 * @since 1.0.0
 */
function ced_mcfw_woo_missing_notice() {
	// translators: %s: search term !!
	echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html( __( 'Multichannel by CedCommerce requires WooCommerce to be installed and active. You can download %s from here.', 'mcfw-woocommerce-integration' ) ), '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>' ) . '</p></div>';
}

function ced_mcfw_welcome_message() {
	if ( get_transient( 'ced_mcfw_show_notice' ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Welcome to <strong>Multichannel by CedCommerce</strong> . Get ready to supercharge your multi-channel selling. Manage, list, and sell seamlessly across all your channels . </p><p><a href="admin.php?page=sales_channel">Get started</a></p></div>';
	}
	delete_transient( 'ced_mcfw_show_notice' );
}

function ced_mcfw_notice_activation() {
	set_transient( 'ced_mcfw_show_notice', 'yes', 5 );
}

if ( ced_woocommerce_active() ) {

	register_activation_hook( __FILE__, 'ced_mcfw_notice_activation' );
	run_multichannel_by_cedcommerce();

	$supported_marketplaces = array(
		'etsy'    => array(
			'parent-slug' => 'woocommerce-etsy-integration/woocommerce-etsy-integration.php',
			'parent-name' => 'Etsy Integration for WooCommerce',
			'is_on'       => true,
		),
		'walmart' => array(
			'parent-slug' => 'walmart-integration-for-woocommerce/walmart-woocommerce-integration.php',
			'parent-name' => 'Walmart Integration for WooCommerce',
			'is_on'       => false,
		),
		'ebay'    => array(
			'parent-slug' => 'ebay-integration-for-woocommerce/woocommerce-ebay-integration.php',
			'parent-name' => 'eBay Integration for WooCommerce',
			'is_on'       => false,
		),
		'amazon'  => array(
			'parent-slug' => 'amazon-for-woocommerce/amazon-for-woocommerce.php',
			'parent-name' => 'Amazon for WooCommerce',
			'is_on'       => false,
		),
	);
	$mode                   = $_GET['mode'] ?? '';
	$subscription_details   = get_option( 'ced_mcfw_subscription_details' . $mode, array() );

	$subscibed_marketplaces = isset( $subscription_details['selected_marketplace'] ) ? explode( ',', base64_decode( $subscription_details['selected_marketplace'] ) ) : array();
	foreach ( $supported_marketplaces as $marketplace => $marketplace_info ) {
		$subscription_active = in_array( ucwords( $marketplace ), $subscibed_marketplaces );
		/**
		 * Filter for getting active plugin list
		 *
		 * @since  1.0.0
		 */
		if ( file_exists( ( CED_MCFW_DIRPATH . 'marketplaces/' . esc_attr( $marketplace ) . '/' . esc_attr( $marketplace ) . '.php' ) ) && $subscription_active && ! in_array( $marketplace_info['parent-slug'], apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			include_once CED_MCFW_DIRPATH . 'marketplaces/' . esc_attr( $marketplace ) . '/' . esc_attr( $marketplace ) . '.php';
			$run_function = 'run_' . $marketplace;
			$run_function();
		}
	}



	add_action( 'admin_notices', 'ced_mcfw_welcome_message' );



} else {
	add_action( 'admin_init', 'deactivate_ced_mcfw_woo_missing' );
}
