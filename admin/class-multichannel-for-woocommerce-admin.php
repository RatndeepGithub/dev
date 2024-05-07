<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://woocommerce.com/vendor/cedcommerce/
 * @since      1.0.0
 *
 * @package    Multichannel_By_Cedcommerce
 * @subpackage Multichannel_By_Cedcommerce/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Multichannel_By_Cedcommerce
 * @subpackage Multichannel_By_Cedcommerce/admin
 */
class Multichannel_By_Cedcommerce_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		require CED_MCFW_DIRPATH . 'admin/partials/pricing/class-ced-pricing-page.php';
		$pricing = new Ced_Pricing_Plans();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Multichannel_By_Cedcommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Multichannel_By_Cedcommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		if ( isset( $_GET['page'] ) && 'sales_channel' == $_GET['page'] ) {
			wp_enqueue_style( 'woocommerce_admin_styles' );
			wp_enqueue_style( WC_ADMIN_APP );
		}
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/multichannel-for-woocommerce-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Multichannel_By_Cedcommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Multichannel_By_Cedcommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/multichannel-for-woocommerce-admin.js', array( 'jquery' ), $this->version, false );
	}

	public function ced_mcfw_add_menus() {
		global $submenu;

		$menu_slug = 'woocommerce';

		if ( ! empty( $submenu[ $menu_slug ] ) ) {
			$sub_menus = array_column( $submenu[ $menu_slug ], 2 );
			if ( ! in_array( 'sales_channel', $sub_menus ) ) {
				add_submenu_page( 'woocommerce', 'CedCommerce', 'CedCommerce', 'manage_woocommerce', 'sales_channel', array( $this, 'ced_marketplace_home_page' ) );
			}
		}
	}


	public function ced_mcfw_add_marketplace_menus_to_array( $menus = array() ) {

		$subscription_details   = get_option( 'ced_mcfw_subscription_details', array() );
		$subscibed_marketplaces = isset( $subscription_details['selected_marketplace'] ) ? explode( ',', base64_decode( $subscription_details['selected_marketplace'] ) ) : array();
		// $subscibed_marketplaces = 0;
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

		$installed_plugins = get_plugins();
		$menus             = array(
			'woocommerce-etsy-integration'        => array(
				'name'            => 'Etsy Integration',
				'tab'             => 'Etsy',
				'page_url'        => 'https://woocommerce.com/products/etsy-integration-for-woocommerce/',
				'doc_url'         => 'https://woocommerce.com/document/etsy-integration-for-woocommerce/',
				'slug'            => 'woocommerce-etsy-integration',
				'menu_link'       => 'etsy',
				'card_image_link' => CED_MCFW_URL . 'admin/images/etsy-logo.png',
				/**
										 * Checking whether tab needs to be active or not
										 *
										 * @since 1.0.0
										 */
				'is_active'       => in_array( 'Etsy', $subscibed_marketplaces ) ||  in_array( $supported_marketplaces['etsy']['parent-slug'], apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ),
				/**
										 * Checking whether tab needs to be active or not
										 *
										 * @since 1.0.0
										 */
				'is_installed'    => in_array( 'Etsy', $subscibed_marketplaces ) ||  in_array( $supported_marketplaces['etsy']['parent-slug'], apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ),
			),
			'walmart-integration-for-woocommerce' => array(
				'name'            => 'Walmart Integration',
				'tab'             => 'Walmart',
				'page_url'        => 'https://woocommerce.com/products/walmart-integration-for-woocommerce/',
				'doc_url'         => 'https://woocommerce.com/document/walmart-integration-for-woocommerce/',
				'slug'            => 'walmart-integration-for-woocommerce',
				'menu_link'       => 'walmart',
				'card_image_link' => CED_MCFW_URL . 'admin/images/walmart-logo.png',
				/**
										 * Checking whether tab needs to be active or not
										 *
										 * @since 1.0.0
										 */
				'is_active'       => in_array( 'Walmart', $subscibed_marketplaces ) ||  in_array( $supported_marketplaces['walmart']['parent-slug'], apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ),
				/**
										 * Checking whether tab needs to be active or not
										 *
										 * @since 1.0.0
										 */
				'is_installed'    => in_array( 'Walmart', $subscibed_marketplaces ) ||  in_array( $supported_marketplaces['walmart']['parent-slug'], apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ),
			),
			'ebay-integration-for-woocommerce'    => array(
				'name'            => 'eBay Integration',
				'tab'             => 'eBay',
				'page_url'        => 'https://woocommerce.com/products/ebay-integration-for-woocommerce/',
				'doc_url'         => 'https://woocommerce.com/document/ebay-integration-for-woocommerce/',
				'slug'            => 'ebay-integration-for-woocommerce',
				'menu_link'       => 'ebay',
				'card_image_link' => CED_MCFW_URL . 'admin/images/ebay-logo.png',
				/**
										 * Checking whether tab needs to be active or not
										 *
										 * @since 1.0.0
										 */
				'is_active'       => in_array( 'Ebay', $subscibed_marketplaces ) ||  in_array( $supported_marketplaces['ebay']['parent-slug'], apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ),
				/**
										 * Checking whether tab needs to be active or not
										 *
										 * @since 1.0.0
										 */
				'is_installed'    => in_array( 'Ebay', $subscibed_marketplaces ) ||  in_array( $supported_marketplaces['ebay']['parent-slug'], apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ),
			),
			'amazon-for-woocommerce'              => array(
				'name'            => 'Amazon Integration',
				'tab'             => 'Amazon',
				'page_url'        => 'https://woocommerce.com/products/walmart-integration-for-woocommerce/',
				'doc_url'         => 'https://woocommerce.com/document/amazon-integration-for-woocommerce/',
				'slug'            => 'amazon-for-woocommerce',
				'menu_link'       => 'amazon',
				'card_image_link' => CED_MCFW_URL . 'admin/images/amazon-logo.png',
				/**
										 * Checking whether tab needs to be active or not
										 *
										 * @since 1.0.0
										 */
				'is_active'       => in_array( 'Amazon', $subscibed_marketplaces ) ||  in_array( $supported_marketplaces['amazon']['parent-slug'], apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ),
				/**
										 * Checking whether tab needs to be active or not
										 *
										 * @since 1.0.0
										 */
				'is_installed'    => in_array( 'Amazon', $subscibed_marketplaces ) ||  in_array( $supported_marketplaces['amazon']['parent-slug'], apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ),
			),
		);
		return $menus;
	}

	public function ced_mcfw_alter_navigation_tabs( $navigation_tabs = array() ) {
		$navigation_tabs['pricing'] = array(
			'name'         => 'Pricing',
			'tab'          => 'Pricing',
			'menu_link'    => 'pricing',
			'is_active'    => 1,
			'is_installed' => 1,
		);
		return $navigation_tabs;
	}
	public function ced_marketplace_home_page() {

		$user_registered = get_option( 'ced_mcfw_user_details', '' );
		if ( ! $user_registered ) {
			require CED_MCFW_DIRPATH . 'admin/partials/multichannel-for-woocommerce-admin-display.php';
		} else {
			require CED_MCFW_DIRPATH . 'admin/template/home/home.php';
			if ( isset( $_GET['page'] ) && 'sales_channel' == $_GET['page'] && ! isset( $_GET['channel'] ) || empty( $_GET['channel'] ) ) {
				require CED_MCFW_DIRPATH . 'admin/template/home/marketplaces.php';
			} elseif ( isset( $_GET['channel'] ) && 'pricing' == $_GET['channel'] ) {
				require CED_MCFW_DIRPATH . 'admin/partials/pricing/class-ced-pricing-page.php';
				$pricing = new Ced_Pricing_Plans();
				$pricing->ced_pricing_plan_display();
			} elseif ( isset( $_GET['page'] ) && 'sales_channel' == $_GET['page'] && isset( $_GET['channel'] ) ) {
				/**
		 * Action for including a template based on active channel
		 *
		 * @since  1.0.0
		 */
				do_action( 'ced_sales_channel_include_template', sanitize_text_field( $_GET['channel'] ) );
			}
		}
	}
}
