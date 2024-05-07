<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://woocommerce.com/vendor/cedcommerce
 * @since      1.0.0
 *
 * @package    EBay_Integration_For_Woocommerce
 * @subpackage EBay_Integration_For_Woocommerce/includes
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
 * @package    EBay_Integration_For_Woocommerce
 * @subpackage EBay_Integration_For_Woocommerce/includes
 */
class EBay_Integration_For_Woocommerce {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0

	 * @var      EBay_Integration_For_Woocommerce_Loader    $loader    Maintains and registers all hooks for the plugin.
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
		if ( defined( 'EBAY_INTEGRATION_FOR_WOOCOMMERCE_VERSION' ) ) {
			$this->version = EBAY_INTEGRATION_FOR_WOOCOMMERCE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'ebay-integration-for-woocommerce';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - EBay_Integration_For_Woocommerce_Loader. Orchestrates the hooks of the plugin.
	 * - EBay_Integration_For_Woocommerce_I18n. Defines internationalization functionality.
	 * - EBay_Integration_For_Woocommerce_Admin. Defines all hooks for the admin area.
	 * - EBay_Integration_For_Woocommerce_Public. Defines all hooks for the public side of the site.
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
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-woocommerce-ebay-integration-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-woocommerce-ebay-integration-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-woocommerce-ebay-integration-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */

		$this->loader = new EBay_Integration_For_Woocommerce_Loader();

		require_once plugin_dir_path( __DIR__ ) . 'admin/class-async-ajax-handler.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-ced-ebay-events-handler.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/ebay/class-ebay-schedule-managers.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/ebay/class-ebay-scheduled-actions-manager.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/ebay/class-ebay-import-listing.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/ebay/lib/cedGetcategories.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/ebay/lib/ebayAuthorization.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/ebay/lib/ebayUpload.php';
		require_once plugin_dir_path( __DIR__ ) .'admin/ebay/lib/class-ced-ebay-api-client.php';
		require_once plugin_dir_path( __DIR__ ) .'admin/ebay/lib/class-wc-product-data.php';
		require_once plugin_dir_path( __DIR__ ) .'admin/ebay/lib/ebayTemplateCustomMetabox.php';
		require_once plugin_dir_path( __DIR__ ) .'admin/partials/class-ced-ebay-product-filters.php';



	}

	

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the EBay_Integration_For_Woocommerce_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	private function set_locale() {

		$plugin_i18n = new EBay_Integration_For_Woocommerce_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Ced\Ebay\EBay_Integration_For_Woocommerce_Admin( $this->get_plugin_name(), $this->get_version() );

		if ( class_exists( 'Ced_Ebay_Async_Ajax_Handler' ) ) {
			$async_ajax_handler = new Ced_Ebay_Async_Ajax_Handler();
			$async_ajax_handler->init();
		}

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );


		$this->loader->add_action( 'wp_ajax_ced_ebay_category_refresh_button', $plugin_admin, 'ced_ebay_category_refresh_button' );

		$this->loader->add_action( 'wp_ajax_ced_ebay_process_bulk_action', $plugin_admin, 'ced_ebay_process_bulk_action' );
		$this->loader->add_action( 'wp_ajax_ced_ebay_fulfill_order', $plugin_admin, 'ced_ebay_fulfill_order' );

		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'ced_ebay_add_order_metabox' );
		$this->loader->add_action( 'wp_ajax_ced_ebay_get_orders', $plugin_admin, 'ced_ebay_get_orders' );
		$this->loader->add_action( 'wp_ajax_ced_ebay_add_custom_item_aspects_row', $plugin_admin, 'ced_ebay_add_custom_item_aspects_row' );

	
		global $wpdb;
		$shopDetails = get_option( 'ced_ebay_user_access_token' );
		if ( ! empty( $shopDetails ) ) {
			foreach ( $shopDetails as $key => $value ) {
				$this->loader->add_action( 'ced_ebay_inventory_scheduler_job_' . $key, $plugin_admin, 'ced_ebay_inventory_schedule_manager' );
				$this->loader->add_action( 'ced_ebay_order_scheduler_job', $plugin_admin, 'ced_ebay_order_schedule_manager' );
				$this->loader->add_action( 'ced_ebay_existing_products_sync_job_' . $key, $plugin_admin, 'ced_ebay_existing_products_sync_manager', 10, 2 );
				$this->loader->add_action( 'ced_ebay_recurring_bulk_upload', $plugin_admin, 'ced_ebay_recurring_bulk_upload_manager' );
				$this->loader->add_action( 'ced_ebay_sync_ended_listings_scheduler_job_' . $key, $plugin_admin, 'ced_ebay_manually_ended_listings_manager' );

			}
		}
		// $this->loader->add_filter( 'ced_sales_channels_list', $plugin_admin, 'ced_ebay_add_marketplace_menus_to_array', 13 );

		$this->loader->add_filter( 'woocommerce_duplicate_product_exclude_meta', $plugin_admin, 'ced_ebay_duplicate_product_exclude_meta' );


		$this->loader->add_action( 'wp_ajax_ced_ebay_process_profile_bulk_action', $plugin_admin, 'ced_ebay_process_profile_bulk_action' );

		// order filter
		$this->loader->add_filter( 'views_edit-shop_order', $plugin_admin, 'ced_ebay_add_woo_order_views' );
		$this->loader->add_filter( 'parse_query', $plugin_admin, 'ced_ebay_woo_admin_order_filter_query' );


		$this->loader->add_action( 'wp_ajax_ced_ebay_remove_account_from_integration', $plugin_admin, 'ced_ebay_remove_account_from_integration' );
		$this->loader->add_action( 'wp_ajax_ced_ebay_remove_all_profiles', $plugin_admin, 'ced_ebay_remove_all_profiles' );
		$this->loader->add_action( 'wp_ajax_ced_ebay_reset_category_item_specifics', $plugin_admin, 'ced_ebay_reset_category_item_specifics' );
		$this->loader->add_action( 'wp_ajax_ced_ebay_remove_term_from_profile', $plugin_admin, 'ced_ebay_remove_term_from_profile' );
		$this->loader->add_action( 'wp_ajax_ced_ebay_toggle_bulk_upload_action', $plugin_admin, 'ced_ebay_toggle_bulk_upload_action' );
		$this->loader->add_action( 'wp_ajax_ced_ebay_toggle_bulk_inventory_action', $plugin_admin, 'ced_ebay_toggle_bulk_inventory_action' );
		$this->loader->add_action( 'wp_ajax_ced_ebay_toggle_import_orders_scheduler', $plugin_admin, 'ced_ebay_toggle_import_orders_scheduler' );
		$this->loader->add_action( 'wp_ajax_ced_ebay_delete_bulk_upload_logs_action', $plugin_admin, 'ced_ebay_delete_bulk_upload_logs_action' );
		$this->loader->add_action( 'wp_ajax_ced_ebay_fetch_order_using_order_id', $plugin_admin, 'ced_ebay_fetch_order_using_order_id' );

		$this->loader->add_action( 'wp_ajax_ced_ebay_remove_category_mapping', $plugin_admin, 'ced_ebay_remove_category_mapping' );

		$this->loader->add_action( 'wp_ajax_ced_ebay_get_category_item_aspects', $plugin_admin, 'ced_ebay_get_category_item_aspects' );

		// add_action( 'ced_ebay_async_update_stock_action', array( $this, 'ced_ebay_async_update_stock_callback' ) );

		// Async fetch site categories
		$this->loader->add_action( 'ced_ebay_fetch_site_categories', $plugin_admin, 'ced_ebay_async_fetch_site_categories' );

		// Import with loader.
		$this->loader->add_action( 'wp_ajax_ced_ebay_init_import_by_loader', $plugin_admin, 'ced_ebay_init_import_by_loader' );
		$this->loader->add_action( 'wp_ajax_ced_ebay_stop_import_loader', $plugin_admin, 'ced_ebay_stop_import_loader' );

		// WooCommerce order items header and values
		$this->loader->add_action( 'woocommerce_admin_order_item_headers', $plugin_admin, 'ced_ebay_add_stock_location_column_wc_order', 10, 1 );  // Since WC 3.0.2
		$this->loader->add_action( 'woocommerce_admin_order_item_values', $plugin_admin, 'ced_ebay_add_stock_location_inputs_wc_order', 10, 3 );   // Since WC 3.0.2

		// Add custom ebay template meta box in product edit page
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'ced_ebay_register_meta_for_template_settings' );
		$this->loader->add_action( 'woocommerce_process_product_meta', $plugin_admin, 'ced_ebay_save_custom_template_setting_product_meta_box' );
		$this->loader->add_action( 'wp_ajax_ced_ebay_get_profile_for_meta_box', $plugin_admin, 'ced_ebay_get_profile_for_meta_box' );
		$this->loader->add_action( 'wp_ajax_ced_ebay_reset_profile_for_meta_box', $plugin_admin, 'ced_ebay_reset_profile_for_meta_box' );

		// Fetch meta field dropdown - Product Specific template.
		$this->loader->add_action( 'wp_ajax_ced_add_meta_field_prod_spec_template', $plugin_admin, 'ced_add_meta_field_prod_spec_template' );

		// Check token status AJAX
		$this->loader->add_action( 'wp_ajax_ced_ebay_ajax_check_token_status', $plugin_admin, 'ced_ebay_ajax_check_token_status' );
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
	 * @return    EBay_Integration_For_Woocommerce_Loader    Orchestrates the hooks of the plugin.
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
