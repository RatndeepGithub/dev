<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://woocommerce.com/vendor/cedcommerce/
 * @since      1.0.0
 *
 * @package    Walmart_Woocommerce_Integration
 * @subpackage Walmart_Woocommerce_Integration/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Walmart_Woocommerce_Integration
 * @subpackage Walmart_Woocommerce_Integration/includes
 */
class Walmart_Woocommerce_Integration {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @var      Walmart_Woocommerce_Integration_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WALMART_WOOCOMMERCE_INTEGRATION_VERSION' ) ) {
			$this->version = WALMART_WOOCOMMERCE_INTEGRATION_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'walmart-woocommerce-integration';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Walmart_Woocommerce_Integration_Loader. Orchestrates the hooks of the plugin.
	 * - Walmart_Woocommerce_Integration_I18n. Defines internationalization functionality.
	 * - Walmart_Woocommerce_Integration_Admin. Defines all hooks for the admin area.
	 * - Walmart_Woocommerce_Integration_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-walmart-woocommerce-integration-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-walmart-woocommerce-integration-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-walmart-woocommerce-integration-admin.php';

		$this->loader = new Walmart_Woocommerce_Integration_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Walmart_Woocommerce_Integration_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	private function set_locale() {

		$plugin_i18n = new Walmart_Woocommerce_Integration_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Walmart_Woocommerce_Integration_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		// $this->loader->add_action( 'admin_menu', $plugin_admin, 'ced_walmart_add_menus', 23 );
		// $this->loader->add_filter( 'ced_sales_channels_list', $plugin_admin, 'ced_walmart_add_marketplace_menus_to_array', 13 );
		$this->loader->add_action( 'ced_sales_channel_include_template', $plugin_admin, 'ced_sales_channel_include_template' );
		$this->loader->add_action( 'ced_show_connected_accounts', $plugin_admin, 'ced_show_connected_accounts' );
		$this->loader->add_action( 'ced_show_connected_accounts_details', $plugin_admin, 'ced_show_connected_accounts_details' );

		$this->loader->add_action( 'wp_ajax_ced_walmart_delete_account', $plugin_admin, 'ced_walmart_delete_account' );
		$this->loader->add_action( 'wp_ajax_ced_walmart_append_category_attr', $plugin_admin, 'ced_walmart_append_category_attr' );
		// $this->loader->add_filter( 'woocommerce_product_data_tabs', $plugin_admin, 'ced_walmart_product_data_tabs' );
		// $this->loader->add_filter( 'woocommerce_product_data_panels', $plugin_admin, 'ced_walmart_product_data_panels' );
		// $this->loader->add_action( 'woocommerce_product_after_variable_attributes', $plugin_admin, 'ced_walmart_render_product_fields', 10, 3 );
		$this->loader->add_action( 'woocommerce_save_product_variation', $plugin_admin, 'ced_walmart_save_product_fields', 10, 2 );
		$this->loader->add_action( 'woocommerce_process_product_meta', $plugin_admin, 'ced_walmart_save_product_fields', 10, 2 );
		$this->loader->add_filter( 'cron_schedules', $plugin_admin, 'ced_walmart_cron_schedules' );
		$this->loader->add_action( 'save_post', $plugin_admin, 'ced_walmart_save_meta_data' );

		$this->loader->add_action( 'wp_ajax_ced_walmart_product_preview', $plugin_admin, 'ced_walmart_product_preview' );
		$this->loader->add_action( 'wp_ajax_ced_walmart_list_per_page', $plugin_admin, 'ced_walmart_list_per_page' );
		$this->loader->add_action( 'wp_ajax_ced_walmart_search_product_name', $plugin_admin, 'ced_walmart_search_product_name' );
		$this->loader->add_action( 'wp_ajax_ced_walmart_get_product_metakeys', $plugin_admin, 'ced_walmart_get_product_metakeys' );
		$this->loader->add_action( 'wp_ajax_ced_walmart_process_metakeys', $plugin_admin, 'ced_walmart_process_metakeys' );
		$this->loader->add_action( 'wp_ajax_ced_walmart_get_orders_manual', $plugin_admin, 'ced_walmart_get_orders_manual' );
		$this->loader->add_action( 'wp_ajax_ced_walmart_acknowledge_order', $plugin_admin, 'ced_walmart_acknowledge_order' );
		$this->loader->add_action( 'wp_ajax_ced_walmart_cancel_order', $plugin_admin, 'ced_walmart_cancel_order' );
		$this->loader->add_action( 'wp_ajax_ced_walmart_shipment_order', $plugin_admin, 'ced_walmart_shipment_order' );
		$this->loader->add_action( 'wp_ajax_ced_walmart_process_bulk_action', $plugin_admin, 'ced_walmart_process_bulk_action' );
		$this->loader->add_action( 'wp_ajax_ced_walmart_retire_item', $plugin_admin, 'ced_walmart_retire_item' );

		$this->loader->add_action( 'ced_walmart_auto_sync_existing_products', $plugin_admin, 'ced_walmart_auto_sync_existing_products' );
		$this->loader->add_action( 'ced_walmart_auto_update_product', $plugin_admin, 'ced_walmart_auto_update_product' );
		// $this->loader->add_action( 'admin_init', $plugin_admin, 'ced_walmart_set_schedules' );

		$this->loader->add_action( 'ced_walmart_license_panel', $plugin_admin, 'ced_walmart_license_panel' );
		$this->loader->add_action( 'wp_ajax_ced_walmart_validate_licensce', $plugin_admin, 'ced_walmart_validate_licensce_callback' );
		$this->loader->add_action( 'wp_ajax_nopriv_ced_walmart_validate_licensce', $plugin_admin, 'ced_walmart_validate_licensce_callback' );
		$this->loader->add_filter( 'ced_walmart_license_check', $plugin_admin, 'ced_walmart_license_check_function', 10, 1 );
		$this->loader->add_action( 'wp_ajax_ced_walmart_get_shipping_templates', $plugin_admin, 'ced_walmart_get_all_shipping_template' );

		$this->loader->add_action( 'wp_ajax_ced_walmart_save_fulfillment_center', $plugin_admin, 'ced_walmart_save_fulfillment_center' );

		$this->loader->add_action( 'wp_ajax_ced_walmart_save_shipping_template', $plugin_admin, 'ced_walmart_save_shipping_template' );

		$this->loader->add_action( 'wp_ajax_ced_walmart_save_shipping_template_paid_standard', $plugin_admin, 'ced_walmart_save_shipping_template_paid_standard' );

		$this->loader->add_action( 'wp_ajax_ced_walmart_save_cat', $plugin_admin, 'ced_walmart_save_cat' );

		$this->loader->add_action( 'wp_ajax_ced_walmart_refresh_insights_keys', $plugin_admin, 'ced_walmart_refresh_insights_keys' );
		$this->loader->add_action( 'wp_ajax_ced_walmart_save_listing_quality_for_product', $plugin_admin, 'ced_walmart_save_listing_quality_for_product' );

		$order_status = array(
			'new_order',
			'customer_processing_order',
			'cancelled_order',
			'customer_completed_order',
			'customer_on_hold_order',
			'customer_refunded_order',
			'customer_failed_order',
		);
		foreach ( $order_status as $key => $status ) {
			$this->loader->add_filter( 'woocommerce_email_enabled_' . esc_attr( $status ), $plugin_admin, 'ced_walmart_email_restriction', 10, 2 );
		}

		$accounts = ced_walmart_return_partner_detail_option();

		foreach ( $accounts as $store_id => $value ) {
			$this->loader->add_action( 'ced_walmart_auto_fetch_orders_' . $store_id, $plugin_admin, 'ced_walmart_auto_fetch_orders' );
			$this->loader->add_action( 'ced_walmart_auto_sync_existing_products_' . $store_id, $plugin_admin, 'ced_walmart_auto_sync_existing_products' );
			$this->loader->add_action( 'ced_walmart_auto_update_inventory_' . $store_id, $plugin_admin, 'ced_walmart_auto_update_inventory' );
			$this->loader->add_action( 'ced_walmart_auto_update_price_' . $store_id, $plugin_admin, 'ced_walmart_auto_update_price' );
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Walmart_Woocommerce_Integration_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
