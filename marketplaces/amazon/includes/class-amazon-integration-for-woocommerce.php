<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       care@cedcommerce.com
 * @since      1.0.0
 *
 * @package    Amazon_Integration_For_Woocommerce
 * @subpackage Amazon_Integration_For_Woocommerce/includes
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
 * @package    Amazon_Integration_For_Woocommerce
 * @subpackage Amazon_Integration_For_Woocommerce/includes
 */
class Amazon_Integration_For_Woocommerce {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @var      Amazon_Integration_For_Woocommerce_Loader    $loader    Maintains and registers all hooks for the plugin.
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
		if ( defined( 'AMAZON_INTEGRATION_FOR_WOOCOMMERCE_VERSION' ) ) {
			$this->version = AMAZON_INTEGRATION_FOR_WOOCOMMERCE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'amazon-for-woocommerce';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Amazon_Integration_For_Woocommerce_Loader. Orchestrates the hooks of the plugin.
	 * - Amazon_Integration_For_Woocommerce_I18n. Defines internationalization functionality.
	 * - Amazon_Integration_For_Woocommerce_Admin. Defines all hooks for the admin area.
	 * - Amazon_Integration_For_Woocommerce_Public. Defines all hooks for the public side of the site.
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
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-amazon-integration-for-woocommerce-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-amazon-integration-for-woocommerce-I18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-amazon-integration-for-woocommerce-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */

		$this->loader = new Amazon_Integration_For_Woocommerce_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Amazon_Integration_For_Woocommerce_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	private function set_locale() {

		$plugin_i18n = new Amazon_Integration_For_Woocommerce_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Amazon_Integration_For_Woocommerce_Admin();

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// $this->loader->add_action( 'admin_menu', $plugin_admin, 'ced_amazon_add_menus', 22 );
		// $this->loader->add_filter( 'ced_sales_channels_list', $plugin_admin, 'ced_amazon_add_marketplace_menus_to_array', 13 );
		$this->loader->add_action( 'wp_ajax_ced_amazon_fetch_next_level_category', $plugin_admin, 'ced_amazon_fetch_next_level_category' );
		$this->loader->add_action( 'wp_ajax_ced_amazon_add_custom_profile_rows', $plugin_admin, 'ced_amazon_add_custom_profile_rows' );

		$this->loader->add_action( 'wp_ajax_ced_amazon_create_sellernext_user', $plugin_admin, 'ced_amazon_create_sellernext_user' );
		$this->loader->add_action( 'wp_ajax_ced_amazon_sellernext_get_access_token_and_redirect', $plugin_admin, 'ced_amazon_sellernext_get_access_token_and_redirect' );

		$this->loader->add_action( 'wp_ajax_ced_amazon_get_orders', $plugin_admin, 'ced_amazon_get_orders' );
		$this->loader->add_filter( 'cron_schedules', $plugin_admin, 'my_amazon_cron_schedules' );

		$this->loader->add_action( 'wp_ajax_ced_amazon_update_current_step', $plugin_admin, 'ced_amazon_update_current_step' );

		$this->loader->add_action( 'wp_ajax_ced_amazon_remove_account_from_integration', $plugin_admin, 'ced_amazon_remove_account_from_integration' );
		$this->loader->add_action( 'wp_ajax_ced_amazon_modify_product_data_for_upload', $plugin_admin, 'ced_amazon_modify_product_data_for_upload' );
		$this->loader->add_action( 'wp_ajax_ced_amazon_update_template', $plugin_admin, 'ced_amazon_update_template' );

		$this->loader->add_action( 'wp_ajax_ced_amazon_checkSellerNextCategoryApi', $plugin_admin, 'ced_amazon_checkSellerNextCategoryApi' );

		$this->loader->add_action( 'wp_ajax_ced_amazon_seller_verification', $plugin_admin, 'ced_amazon_seller_verification' );

		$this->loader->add_action( 'wp_ajax_ced_amazon_add_missing_field_row', $plugin_admin, 'ced_amazon_add_missing_field_row' );
		$this->loader->add_action( 'wp_ajax_ced_amazon_view_feed_response', $plugin_admin, 'ced_amazon_view_feed_response' );
		$this->loader->add_action( 'wp_ajax_ced_amazon_product_specific_feeds', $plugin_admin, 'ced_amazon_product_specific_feeds' );

		$this->loader->add_action( 'wp_ajax_ced_amazon_order_detail_amazon_data', $plugin_admin, 'ced_amazon_order_detail_amazon_data' );
		$this->loader->add_action( 'wp_ajax_ced_amazon_prepare_template', $plugin_admin, 'ced_amazon_prepare_template' );

		$this->loader->add_action( 'wp_ajax_ced_amazon_change_region', $plugin_admin, 'ced_amazon_change_region' );

		$this->loader->add_action( 'wp_ajax_ced_amazon_clone_template_modal', $plugin_admin, 'ced_amazon_clone_template_modal' );
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
	 * @return    Amazon_Integration_For_Woocommerce_Loader    Orchestrates the hooks of the plugin.
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
