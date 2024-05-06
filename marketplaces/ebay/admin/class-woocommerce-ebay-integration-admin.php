<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://woocommerce.com/vendor/cedcommerce
 * @since      1.0.0
 *
 * @package    EBay_Integration_For_Woocommerce
 * @subpackage EBay_Integration_For_Woocommerce/admin
 */
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    EBay_Integration_For_Woocommerce
 * @subpackage EBay_Integration_For_Woocommerce/admin
 */
class EBay_Integration_For_Woocommerce_Admin {

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

	public $user_id;
	public $schedule_order_task;
	public $schedule_import_task;
	public $schedule_bulk_upload_task;

	public $sync_business_policies;
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
		$this->loadDependency();
		// error_reporting( ~0 );
		// ini_set( 'display_errors', 1 );
		$this->user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		add_action( 'ced_show_connected_accounts_details', array( $this, 'ced_show_connected_accounts_details_callback' ) );
		add_action( 'ced_show_connected_accounts', array( $this, 'ced_show_connected_accounts_callback' ) );
		add_action( 'manage_edit-shop_order_columns', array( $this, 'ced_ebay_add_table_columns' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'ced_ebay_manage_table_columns' ), 10, 2 );
		add_action( 'wp_ajax_ced_ebay_order_schedule_manager', array( $this, 'ced_ebay_order_schedule_manager' ) );
		add_action( 'wp_ajax_nopriv_ced_ebay_order_schedule_manager', array( $this, 'ced_ebay_order_schedule_manager' ) );
		add_action( 'wp_ajax_nopriv_ced_ebay_import_products_manager', array( $this, 'ced_ebay_import_products_manager' ) );
		add_action( 'wp_ajax_nopriv_ced_ebay_inventory_schedule_manager', array( $this, 'ced_ebay_inventory_schedule_manager' ) );
		add_action( 'wp_ajax_nopriv_ced_ebay_existing_products_sync_manager', array( $this, 'ced_ebay_existing_products_sync_manager' ) );
		add_action( 'wp_ajax_nopriv_ced_ebay_sync_seller_event', array( $this, 'ced_ebay_sync_seller_event' ) );
		add_action( 'wp_ajax_nopriv_ced_ebay_manually_ended_listings_manager', array( $this, 'ced_ebay_manually_ended_listings_manager' ) );
		add_action( 'admin_notices', array( $this, 'ced_ebay_show_active_inventory_sync_notice' ) );
		add_action( 'ced_ebay_refresh_access_token_schedule', array( $this, 'ced_ebay_refresh_access_token_schedule_action' ) );
		add_filter( 'woocommerce_shop_order_search_fields', array( $this, 'ced_ebay_search_orders_query' ) );
		add_action( 'init', array( $this, 'ced_ebay_init_background_process' ) );
		// async stock update
		add_action( 'ced_ebay_async_update_stock_action', array( $this, 'ced_ebay_async_update_stock_callback' ) );
		add_action( 'ced_ebay_async_bulk_upload_action', array( $this, 'ced_ebay_async_bulk_upload_callback' ) );

		add_action( 'ced_ebay_async_order_sync_action', array( $this, 'ced_ebay_async_order_sync_manager' ) );

		// add_action( 'admin_footer', array( $this, 'ced_ebay_product_modifications_modal_template' ) );
		add_action( 'wp_ajax_ced_ebay_get_product_details', array( $this, 'ced_ebay_get_product_details' ) );

		add_action( 'wp_ajax_ced_ebay_update_stock_on_webhook', array( $this, 'ced_ebay_update_stock_on_webhook' ) );
		add_action( 'wp_ajax_nopriv_ced_ebay_update_stock_on_webhook', array( $this, 'ced_ebay_update_stock_on_webhook' ) );

		add_action( 'wp_ajax_ced_ebay_get_categories_for_category_mapping', array( $this, 'ced_ebay_get_categories_for_category_mapping' ) );

		add_action( 'wp_ajax_ced_ebay_bulk_upload_endpoint', array( $this, 'ced_ebay_schedule_bulk_upload_using_external_endpoint' ) );
		add_action( 'wp_ajax_nopriv_ced_ebay_bulk_upload_endpoint', array( $this, 'ced_ebay_schedule_bulk_upload_using_external_endpoint' ) );

		// Fires when a product is removed from trash.
		add_action( 'before_delete_post', array( $this, 'ced_ebay_delete_product_images_when_trashed' ) );

		add_action( 'woocommerce_product_set_stock', array( $this, 'ced_ebay_instant_stock_sync' ) );
		add_action( 'woocommerce_variation_set_stock', array( $this, 'ced_ebay_instant_stock_sync' ) );

		add_filter( 'allowed_redirect_hosts', array( $this, 'ced_ebay_allowed_redirect_hosts' ) );
		add_action( 'ced_sales_channel_include_template', array( $this, 'ced_ebay_accounts_page' ) );

		add_action(
			'wp_ajax_nopriv_ced_ebay_force_run_action_scheduler',
			function () {
				/**
				 * Action_scheduler_run_queue.
				 *
				 * @since 1.0.0
				 */
				do_action( 'action_scheduler_run_queue', 'Async Request' );
			}
		);

		add_filter( 'wp_ajax_ced_ebay_async_update_stock_using_ajax', array( $this, 'ced_ebay_async_update_stock_using_ajax_callback' ) );
		add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( $this, 'ced_ebay_handle_custom_query_var' ), 10, 2 );

		// Disable SSL checks for curl. Useful when debugging webhooks on local.
		// add_filter('https_ssl_verify', '__return_false');

		if ( file_exists( CED_EBAY_DIRPATH . 'admin/ebay/lib/EbayRestEndpoints.php' ) ) {
			require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/EbayRestEndpoints.php';
		}

		if ( file_exists( CED_EBAY_DIRPATH . 'admin/includes/ced_logging/Ced_Logging.php' ) ) {
			require_once CED_EBAY_DIRPATH . 'admin/includes/ced_logging/Ced_Logging.php';
		}

		if ( file_exists( CED_EBAY_DIRPATH . 'admin/vendor/autoload.php' ) ) {
			require_once CED_EBAY_DIRPATH . 'admin/vendor/autoload.php';
		}

		if ( file_exists( CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayTemplateCustomMetabox.php' ) ) {
			require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayTemplateCustomMetabox.php';
		}
		add_action( 'admin_print_footer_scripts', array( $this, 'ced_ebay_product_importer_heartbeat_footer_js' ), 20 );
		add_filter( 'heartbeat_received', array( $this, 'ced_ebay_product_importer_heartbeat_received' ), 10, 2 );

		// Inhibit eBay order emails
		add_filter( 'woocommerce_email_enabled_new_order', array( $this, 'ced_ebay_inhibit_order_emails' ), 10, 2 );
		add_filter( 'woocommerce_email_enabled_cancelled_order', array( $this, 'ced_ebay_inhibit_order_emails' ), 10, 2 );
		add_filter( 'woocommerce_email_enabled_customer_completed_order', array( $this, 'ced_ebay_inhibit_order_emails' ), 10, 2 );
		add_filter( 'woocommerce_email_enabled_customer_invoice', array( $this, 'ced_ebay_inhibit_order_emails' ), 10, 2 );
		add_filter( 'woocommerce_email_enabled_customer_note', array( $this, 'ced_ebay_inhibit_order_emails' ), 10, 2 );
		add_filter( 'woocommerce_email_enabled_customer_on_hold_order', array( $this, 'ced_ebay_inhibit_order_emails' ), 10, 2 );
		add_filter( 'woocommerce_email_enabled_customer_processing_order', array( $this, 'ced_ebay_inhibit_order_emails' ), 10, 2 );
		add_filter( 'woocommerce_email_enabled_customer_refunded_order', array( $this, 'ced_ebay_inhibit_order_emails' ), 10, 2 );
		add_filter( 'woocommerce_email_enabled_failed_order', array( $this, 'ced_ebay_inhibit_order_emails' ), 10, 2 );

		add_filter( 'woocommerce_product_pre_search_products', array( $this, 'ced_ebay_custom_search_products_logic' ), 10, 6 );
	}


	public function ced_ebay_allowed_redirect_hosts( $hosts ) {
		$ebay_hosts = array(
			'auth.sandbox.ebay.com',
			'ebay.com',
			'auth.ebay.com',
		);
		return array_merge( $hosts, $ebay_hosts );
	}


	public function ced_ebay_search_orders_query( $search_fields ) {
		$search_fields[] = 'ebayBuyerUserId';
		$search_fields[] = 'purchaseOrderId';
		$search_fields[] = '_ebay_order_id';
		return $search_fields;
	}

	public function ced_ebay_handle_custom_query_var( $query, $query_vars ) {
		if ( isset( $query_vars['ced_ebay_listingMarketplaceId'] ) && isset( $query_vars['ced_ebay_order_user_id'] ) && ! empty( $query_vars['ced_ebay_listingMarketplaceId'] && ! empty( $query_vars['ced_ebay_order_user_id'] ) ) ) {
			$query['meta_query'][] = array(
				'key'   => 'ced_ebay_listingMarketplaceId',
				'value' => esc_attr( $query_vars['ced_ebay_listingMarketplaceId'] ),
			);
			$query['meta_query'][] = array(
				'key'   => 'ced_ebay_order_user_id',
				'value' => esc_attr( $query_vars['ced_ebay_order_user_id'] ),
			);
		}

		return $query;
	}

	public function ced_ebay_accounts_page() {
		$active_channel = isset( $_GET['channel'] ) ? sanitize_text_field( $_GET['channel'] ) : 'ebay';
		$panel          = isset( $_GET['panel'] ) ? sanitize_text_field( $_GET['panel'] ) : '';
		if ( 'ebay' == $active_channel ) {

			require_once CED_EBAY_DIRPATH . 'admin/class-ced-ebay-setup-wizard.php';
			$ced_ebay_setup_wizard = new \Ced_Ebay_WooCommerce_Core\Ced_Ebay_Setup_Wizard();
			$ced_ebay_setup_wizard->setup_wizard();

		}
	}

	public function ced_ebay_add_stock_location_column_wc_order( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$order_id    = $order->get_id();
		$eBayOrderId = get_post_meta( $order_id, '_ced_ebay_order_id', true );
		echo '<th>eBay Listing</th>';
	}

	public function ced_ebay_add_stock_location_inputs_wc_order( $product, $item, $item_id ) {

		if ( ! $item instanceof WC_Order_Item_Product ) {
			return;
		}
		if ( empty( $item ) || empty( $product ) || empty( $item_id ) ) {
			echo '<td width="15%">';
			echo '<div display="block">n/a</div>';
			echo '</td>';
			return;
		}
		$order_id = isset( $_GET['post'] ) ? sanitize_text_field( $_GET['post'] ) : '';
		$order    = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$purchaseMarketplaceId = ! empty( get_post_meta( $order_id, 'ced_ebay_purchaseMarketplaceId', true ) ) ? get_post_meta( $order_id, 'ced_ebay_purchaseMarketplaceId', true ) : '';
		$eBayUser              = get_post_meta( $order_id, 'ced_ebay_order_user_id', true );
		$eBaySiteID            = $this->ced_ebay_get_ebay_site( '', $purchaseMarketplaceId );
		$listing_url_tld       = ! empty( get_option( 'ced_ebay_listing_url_tld_' . $eBayUser . '>' . $eBaySiteID ) ) ? get_option( 'ced_ebay_listing_url_tld_' . $eBayUser . '>' . $eBaySiteID, true ) : false;
		$mode_of_operation     = get_option( 'ced_ebay_mode_of_operation', '' );
		if ( false === $eBaySiteID || '' === $eBaySiteID || empty( $eBayUser ) || empty( $listing_url_tld ) ) {
			echo '<td width="15%">';
			echo '<div display="block">n/a</div>';
			echo '</td>';
			return;
		}

		if ( ! $product instanceof WC_Product ) {
			return;
		}
		if ( $product->get_type() === 'variation' ) {

			// Get variation parent id
			$parent_id = $item->get_product_id();

			$eBayListingId = get_post_meta( $parent_id, '_ced_ebay_listing_id_' . $eBayUser . '>' . $eBaySiteID );
			if ( empty( $eBayListingId ) ) {
				echo '<td width="15%">';
				echo '<div display="block">n/a</div>';
				echo '</td>';
				return;
			}
			$view_url            = '';
			$view_url_production = 'https://www.ebay' . $listing_url_tld . '/itm/' . $eBayListingId;
			$view_url_sandbox    = 'https://sandbox.ebay' . $listing_url_tld . '/itm/' . $eBayListingId;
			if ( 'sandbox' == $mode_of_operation ) {
				$view_url = $view_url_sandbox;
			} else {
				$view_url = $view_url_production;
			}
			if ( empty( $view_url ) ) {
				echo '<td width="15%">';
				echo '<div display="block">n/a</div>';
				echo '</td>';
				return;
			} else {
				echo '<td width="15%">';
				echo '<div display="block"><a href=' . esc_url( $view_url ) . '>View on eBay</a></div>';
				echo '<div display="block"><a target="_blank" href="' . esc_url( get_admin_url() . 'admin.php?page=sales_channel&channel=ebay&section=products-view&user_id=' . $eBayUser . '&site_id=' . $eBaySiteID . '&prodID=' . $parent_id ) . '">Update Stock</a></div>';
				echo '</td>';
			}
		} else {

			// Get the product id
			$product_id = $item->get_product_id();

			$eBayListingId = get_post_meta( $product_id, '_ced_ebay_listing_id_' . $eBayUser . '>' . $eBaySiteID, true );
			if ( empty( $eBayListingId ) ) {
				echo '<td width="15%">';
				echo '<div display="block">n/a</div>';
				echo '</td>';
				return;
			}
			$view_url            = '';
			$view_url_production = 'https://www.ebay' . $listing_url_tld . '/itm/' . $eBayListingId;
			$view_url_sandbox    = 'https://sandbox.ebay' . $listing_url_tld . '/itm/' . $eBayListingId;
			if ( 'sandbox' == $mode_of_operation ) {
				$view_url = $view_url_sandbox;
			} else {
				$view_url = $view_url_production;
			}
			if ( empty( $view_url ) ) {
				echo '<td width="15%">';
				echo '<div display="block">n/a</div>';
				echo '</td>';
				return;
			} else {
				echo '<td width="15%">';
				echo '<div display="block"><a target="_blank" href=' . esc_url( $view_url ) . '>View on eBay</a></div>';
				echo '<div display="block"><a target="_blank" href="' . esc_url( get_admin_url() . 'admin.php?page=sales_channel&channel=ebay&section=products-view&user_id=' . $eBayUser . '&site_id=' . $eBaySiteID . '&prodID=' . $product_id ) . '">Update Stock</a></div>';
				echo '</td>';
			}
		}
	}
	public function ced_ebay_get_ebay_site( $site_id = '', $marketplaceEnum = '' ) {
		if ( ! file_exists( CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayConfig.php' ) ) {
			return false;
		}
		require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayConfig.php';
		$configInstance  = \Ced_Ebay_WooCommerce_Core\Ebayconfig::get_instance();
		$ebaySiteDetails = $configInstance->getEbaycountrDetail( $site_id, $marketplaceEnum );
		if ( ! empty( $ebaySiteDetails ) && is_array( $ebaySiteDetails ) && isset( $ebaySiteDetails['siteID'] ) ) {
			return $ebaySiteDetails['siteID'];
		} else {
			return '';
		}
	}

	public function ced_ebay_inhibit_order_emails( $enabled, $order ) {
		if ( $enabled && $order ) {
			$orderId = $order->get_id();
			if ( get_post_meta( $orderId, 'ced_ebay_order_user_id' ) ) {
				return false;
			}
		}

		return $enabled;
	}

	public function ced_ebay_custom_search_products_logic( $preempt, $query, $type = '', $include = false, $all_statuses = false, $limit = null ) {
		$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$site_id = isset( $_GET['site_id'] ) ? sanitize_text_field( $_GET['site_id'] ) : '';
		if ( preg_match( '/^ebay:/', $query ) ) {
			$search_terms       = explode( ':', $query, 2 );
			$actual_search_term = isset( $search_terms[1] ) ? trim( $search_terms[1] ) : '';
			$args               = array(
				'post_type'   => 'product',
				'meta_query'  => array(
					'relation' => 'AND',
					array(
						'key'     => '_ced_ebay_listing_id_' . $user_id . '>' . $site_id,
						'compare' => 'LIKE',
					),
					array(
						'key'     => '_ced_ebay_listing_id_' . $user_id . '>' . $site_id,
						'value'   => $actual_search_term,
						'compare' => 'LIKE',
					),
				),
				'numberposts' => -1,
				'fields'      => 'ids',
			);
			$search_results     = get_posts( $args );
			if ( ! empty( $search_results ) ) {
				return $search_results;
			} else {
				return array();
			}
		}
		return $preempt;
	}

	public function ced_ebay_async_fetch_site_categories( $data ) {
		if ( ! empty( $data ) && isset( $data['user_id'] ) && isset( $data['site_id'] ) && is_array( $data ) ) {
			$user_id   = isset( $data['user_id'] ) ? $data['user_id'] : '';
			$site_id   = isset( $data['site_id'] ) ? $data['site_id'] : '';
			$shop_data = ced_ebay_get_shop_data( $user_id, $site_id );
			if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
				$token        = $shop_data['access_token'];
				$site_details = ced_ebay_get_site_details( $site_id );
				if ( empty( $site_details ) ) {
					return;
				}
				$location      = isset( $site_details['name'] ) ? $site_details['name'] : function () {
					return;
				};
				$location      = strtolower( $location );
				$location      = str_replace( ' ', '', $location );
				$levels        = array( 1, 2, 3, 4, 5, 6, 7, 8 );
				$wp_folder     = wp_upload_dir();
				$wp_upload_dir = $wp_folder['basedir'];
				$wp_upload_dir = $wp_upload_dir . '/ced-ebay/category-templates-json/';
				if ( ! is_dir( $wp_upload_dir ) ) {
					wp_mkdir_p( $wp_upload_dir, 0777 );
				}
				$fileCategory = CED_EBAY_DIRPATH . 'admin/ebay/lib/cedGetcategories.php';
				if ( ! file_exists( $fileCategory ) ) {
					return;
				}
				require_once $fileCategory;
				$categoryLevel      = 0;
				$cedCatInstance     = CedGetCategories::get_instance( $site_id, $token );
				$connected_accounts = ! empty( get_option( 'ced_ebay_connected_accounts', true ) ) ? get_option( 'ced_ebay_connected_accounts', true ) : array();
				foreach ( $levels as $level ) {
					$is_file_write_error = false;
					$getCat              = $cedCatInstance->_getCategories( $level );
					if ( $getCat && is_array( $getCat ) ) {
						$cates = array();
						if ( ! empty( $connected_accounts ) && isset( $connected_accounts[ $user_id ][ $site_id ] ) && isset( $connected_accounts[ $user_id ][ $site_id ]['cat_level'] ) ) {
							if ( $level < $connected_accounts[ $user_id ][ $site_id ]['cat_level'] ) {
								$connected_accounts[ $user_id ][ $site_id ]['cat_level'] = $level;
								update_option( 'ced_ebay_connected_accounts', $connected_accounts );
							}
						}
						foreach ( $getCat['CategoryArray']['Category'] as $key => $value ) {
							if ( "$level" == $value['CategoryLevel'] ) {
								$cates[] = $value;
							}
						}

						$getCat['CategoryArray']['Category'] = $cates;
						$folderName                          = $wp_upload_dir;
						$fileName                            = $folderName . 'categoryLevel-' . $level . '_' . $location . '.json';
						$upload_dir                          = wp_upload_dir();
						$file                                = fopen( $fileName, 'w' );
						$sanitized_json                      = json_encode( $getCat, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS );
						$pieces                              = str_split( $sanitized_json, 1024 );
						foreach ( $pieces as $piece ) {
							if ( ! fwrite( $file, $piece, strlen( $piece ) ) ) {
								$is_file_write_error = true;
								continue;
							}
						}
						fclose( $file );
						if ( ! $is_file_write_error ) {
							if ( ! empty( $connected_accounts ) && isset( $connected_accounts[ $user_id ][ $site_id ] ) ) {
								$connected_accounts[ $user_id ][ $site_id ]['cat_level'] = $level;
								update_option( 'ced_ebay_connected_accounts', $connected_accounts );
							}
						}
					}
				}
			}
		}
	}

	public function ced_ebay_async_update_stock_using_ajax_callback() {
		if ( isset( $_POST['product_id'] ) && ! check_ajax_referer( 'ced_ebay_async_stock_update-' . sanitize_text_field( $_POST['product_id'] ), false, false ) ) {
			exit();
		}
		$shop_data  = get_option( 'ced_ebay_user_access_token' );
		$product_id = isset( $_POST['product_id'] ) ? sanitize_text_field( $_POST['product_id'] ) : '';
		if ( ! empty( $shop_data ) && is_array( $shop_data ) && ! empty( $product_id ) ) {
			foreach ( $shop_data as $user_id => $eBayAccountData ) {
				$this->ced_ebay_async_update_stock_callback(
					array(
						'user_id'    => $user_id,
						'product_id' => $product_id,
					),
					''
				);
			}
		}
	}

	public function ced_ebay_instant_stock_sync( $product ) {

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$shop_data                  = get_option( 'ced_ebay_user_access_token' );
		$renderDataOnGlobalSettings = get_option( 'ced_ebay_global_settings', false );
		if ( ! empty( $shop_data ) && is_array( $shop_data ) ) {
			foreach ( $shop_data as $user_id => $eBayAccountData ) {
				$dataInGlobalSettings = ! empty( $renderDataOnGlobalSettings ) && is_array( $renderDataOnGlobalSettings ) && ! empty( $renderDataOnGlobalSettings[ $user_id ] ) ? $renderDataOnGlobalSettings[ $user_id ] : array();
				if ( ! empty( 'on' === $dataInGlobalSettings ) && $dataInGlobalSettings['ced_ebay_instant_stock_update'] ) {
					$product_id = $product->get_id();
					if ( ! empty( $product_id ) ) {
						$context     = 'wp';
						$action      = 'ced_ebay_async_update_stock_using_ajax';
						$_ajax_nonce = wp_create_nonce( 'ced_ebay_async_stock_update-' . $product_id );
						$body        = compact( 'action', '_ajax_nonce', 'product_id', 'context' );
						$args        = array(
							'timeout'   => 0.01,
							'blocking'  => false,
							'body'      => $body,
							'cookies'   => isset( $_COOKIE ) && is_array( $_COOKIE ) ? $_COOKIE : array(),
							/**
							 * Https_local_ssl_verify.
							 *
							 * @since 1.0.0
							 */
							'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
						);
						if ( getenv( 'WORDPRESS_HOST' ) !== false ) {
							wp_remote_post( getenv( 'WORDPRESS_HOST' ) . '/wp-admin/admin-ajax.php', $args );
						} else {
							wp_remote_post( admin_url( 'admin-ajax.php' ), $args );
						}
					}
				}
			}
		}
		return;
	}




	public function ced_ebay_init_background_process() {
		if ( ! class_exists( 'OrderBackground_Process' ) ) {
			require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/OrderBackgroundProcess.php';
		}
		if ( ! class_exists( 'ImportBackground_Process' ) ) {
			require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ImportBackground.php';
		}
		if ( ! class_exists( 'BulkUpload_Background_Process' ) ) {
			require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/BulkUploadBackgroundProcess.php';
		}

		$this->schedule_order_task       = new OrderBackground_Process();
		$this->schedule_import_task      = new ImportBackground_Process();
		$this->schedule_bulk_upload_task = new BulkUpload_Background_Process();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles( $hook ) {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in EBay_Integration_For_Woocommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The EBay_Integration_For_Woocommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		$page    = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		$action  = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
		$channel = isset( $_GET['channel'] ) ? sanitize_text_field( $_GET['channel'] ) : '';
		// wp_enqueue_style('ced-ebay-admin-menu', plugin_dir_url(__FILE__) . 'css/ced-ebay-admin-menu.css', array(), '1.0', 'all');
		global $pagenow;

		if ( 'sales_channel' == $page || 'ebay' == $channel ) {
			wp_enqueue_style( 'woocommerce_admin_styles' );
			wp_enqueue_style( WC_ADMIN_APP );
			wp_enqueue_style( 'ebay-new-ui', plugin_dir_url( __FILE__ ) . 'css/ebay.css', array(), '1.1', 'all' );
		}
		if ( 'ced_ebay' == $page || 'cedcommerce-integrations' == $page || 'edit' == $action || 'post-new.php' == $pagenow ) {
			wp_enqueue_style( 'wc-admin-css', get_site_url() . '/wp-content/plugins/woocommerce/assets/css/admin.css', array(), '1.1', 'all' );
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woocommerce-ebay-integration-admin.css', array(), '1.1', 'all' );

		}
	}
	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts( $hook ) {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in EBay_Integration_For_Woocommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The EBay_Integration_For_Woocommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( 'selectWoo' );
		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_script( 'jquery-ui-spinner' );
		wp_enqueue_script( 'jquery-blockui' );
		wp_enqueue_script( 'jquery-tiptip' );
		$action  = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
		$page    = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		$channel = isset( $_GET['channel'] ) ? sanitize_text_field( $_GET['channel'] ) : '';
		$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$site_id = isset( $_GET['site_id'] ) ? sanitize_text_field( $_GET['site_id'] ) : '';
		global $pagenow;
		if ( 'sales_channel' == $page && ( 'amazon' != $channel || 'edit' == $action || 'post-new.php' == $pagenow ) ) {
			wp_enqueue_script( 'sales-channel-ebay', plugin_dir_url( __FILE__ ) . 'js/sales_channel_ebay.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-spinner' ), time(), false );
		}

		if ( 'sales_channel' == $page && 'ebay' == $channel ) {
			wp_enqueue_script(
				'backbone-modal',
				get_site_url() . '/wp-content/plugins/woocommerce/assets/js/admin/backbone-modal.js',
				array( 'jquery', 'wp-util', 'backbone' ),
				time(),
				false
			);
			wp_enqueue_script( 'ced-ebay-product-template', plugin_dir_url( __FILE__ ) . 'js/ced-eBay-cat.js', array( 'jquery', 'wp-hooks', 'jquery-tiptip', 'jquery-blockui', 'jquery-ui-spinner' ), time(), false );
		}

		if ( 'ced_ebay_add_new_template' == $action || 'ced_ebay_edit_template' == $action ) {
			wp_enqueue_script( 'ace', plugin_dir_url( __FILE__ ) . 'js/ace/ace.js', array( 'jquery' ), '1.4.12' );
			wp_enqueue_script( 'mode-css', plugin_dir_url( __FILE__ ) . 'js/ace/mode-css.js', array( 'jquery' ), '1.4.12' );
		}
		$wp_upload_dir  = wp_upload_dir() ['baseurl'];
		$ebay_folder    = $wp_upload_dir . '/ced-ebay/category-templates-json';
		$ajax_nonce     = wp_create_nonce( 'ced-ebay-ajax-seurity-string' );
		$localize_array = array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'ajax_nonce' => $ajax_nonce,
			'user_id'    => isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '',
			'site_id'    => isset( $_GET['site_id'] ) ? sanitize_text_field( $_GET['site_id'] ) : '',
			'site_url'   => get_option( 'siteurl' ),
			'ebay_path'  => $ebay_folder,
		);
		wp_localize_script( $this->plugin_name, 'ced_ebay_admin_obj', $localize_array );
		wp_localize_script( 'sales-channel-ebay', 'ced_ebay_admin_obj', $localize_array );
		if ( 'plugins.php' == $hook ) {
			wp_enqueue_script( 'jquery-ui-dialog' );
			wp_enqueue_style( 'wp-jquery-ui-dialog' );

		}
	}




	public function ced_ebay_get_product_details() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$product_id = isset( $_POST['product_id'] ) ? sanitize_text_field( $_POST['product_id'] ) : '';
			$user_id    = isset( $_POST['user_id'] ) ? sanitize_text_field( $_POST['user_id'] ) : '';
			if ( ! empty( $product_id ) && ! empty( $user_id ) ) {
				$product_data          = array();
				$product               = wc_get_product( $product_id );
				$product_data['id']    = $product_id;
				$product_data['title'] = $product->get_title();
				if ( ! empty( get_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true ) ) ) {
					$ebay_listing_id   = get_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
					$listing_url_tld   = get_option( 'ced_ebay_listing_url_tld_' . $user_id, true );
					$mode_of_operation = get_option( 'ced_ebay_mode_of_operation', '' );
					if ( 'sandbox' == $mode_of_operation ) {
						$view_url = 'https://sandbox.ebay' . $listing_url_tld . '/itm/' . $ebay_listing_id;
					} elseif ( 'production' == $mode_of_operation ) {
						$view_url = 'https://www.ebay' . $listing_url_tld . '/itm/' . $ebay_listing_id;

					}
					$product_data['view_url'] = ! empty( $view_url ) ? $view_url : false;
				}
				if ( $product->is_type( 'simple' ) ) {
					$product_data['type']  = 'simple';
					$product_data['price'] = ! empty( $product->get_price() ) ? $product->get_price() : 0;
					$product_data['sku']   = ! empty( $product->get_sku() ) ? $product->get_sku() : false;
					$product_data['stock'] = ! empty( $product->get_stock_quantity() ) ? $product->get_stock_quantity() : 0;
				} elseif ( $product->is_type( 'variable' ) ) {
					$product_data['type']      = 'variable';
					$product_data['parent_id'] = $product_id;
					$product_childrens         = $product->get_children();
					if ( ! empty( $product_childrens ) ) {
						foreach ( $product_childrens as $key => $variation_id ) {
							$product_data['child'][ $variation_id ] = array(
								'sku'   => ! empty( get_post_meta( $variation_id, '_sku', true ) ) ? get_post_meta( $variation_id, '_sku', true ) : false,
								'price' => ! empty( get_post_meta( $variation_id, '_price', true ) ) ? get_post_meta( $variation_id, '_price', true ) : 0,
								'stock' => ! empty( get_post_meta( $variation_id, '_stock', true ) ) ? get_post_meta( $variation_id, '_stock', true ) : 0,
							);
						}
					}
				}
			}

			wp_send_json_success(
				array(
					'product_id'   => $product_id,
					'product_data' => $product_data,
				)
			);
		}
	}

	public function ced_ebay_product_modifications_modal_template() {
		?>

		<script type="text/template" id="tmpl-wc-ced-ebay-modify-product-modal">
			<div class="wc-backbone-modal wc-order-preview">
				<div class="wc-backbone-modal-content" tabindex="0">
					<section class="wc-backbone-modal-main" role="main">
						<header class="wc-backbone-modal-header">
							<# if(data.product_data.view_url === undefined){ #>
								<mark class="order-status status-cancelled"><span>Not Uploaded</span></mark>
								<# } else { #>
									<a target="_blank" href="{{data.product_data.view_url}}"><mark class="order-status status-processing"><span>View on eBay</span></mark></a>
									<# } #>							
										<h1>{{data.product_data.title}}</h1>
										<button class="modal-close modal-close-link dashicons dashicons-no-alt">
											<span class="screen-reader-text">Close modal panel</span>
										</button>
									</header>
									<article style="max-height: 541.5px;">

										<div class="wc-order-preview-addresses">
											<div class="wc-order-preview-address">
												<h2>Enter custom title</h2>
												<input type="text" name="ced_order_date" class="ced_order_date">
												<strong>Some text here</strong>		
											</div>

											<div class="wc-order-preview-address">
												<h2>Select shipping template</h2>

												<select name="" id="">
													<option value="">One</option>
													<option value="">Two</option>
													<option value="">Three</option>
												</select>										
												<strong>Some text here</strong>		



											</div>


										</div>
										<div class="wc-order-preview-addresses">
											<div class="wc-order-preview-address">
												<h2>Enter custom title</h2>
												<input type="text" name="ced_order_date" class="ced_order_date">
												<strong>Some text here</strong>		
											</div>

											<div class="wc-order-preview-address">
												<h2>Select shipping template</h2>

												<select name="" id="">
													<option value="">One</option>
													<option value="">Two</option>
													<option value="">Three</option>
												</select>										
												<strong>Some text here</strong>		



											</div>


										</div>


										<div class="wc-order-preview-table-wrapper ced-ebay-bootstrap-wrapper">

											<table class="table table-bordered table-hover">
												<thead>
													<tr>
														<th class="col-md-4">Name</th>
														<th class="col-md-4">SKU</th>
														<th class="col-md-2">Price</th>
														<th class="col-md-2">Inventory</th>
													</tr>
												</thead>
												<tbody>
													<tr>
														<td>Stackoverflow</td>
														<td><input type="text"></td>
														<td><input type="text"></td>
														<td><input type="text" placeholder="2016"></td>
													</tr>
												</tbody>
											</table>

										</div>

									</article>
									<footer>
										<div class="inner">
											<div class="wc-action-button-group"><label>Change status: </label> <span class="wc-action-button-group__items"><a class="button wc-action-button wc-action-button-complete complete" href="https://amazon.dev.test/wp-admin/admin-ajax.php?action=woocommerce_mark_order_status&amp;status=completed&amp;order_id=75&amp;_wpnonce=955f7c301a" aria-label="Change order status to completed" title="Change order status to completed">Completed</a></span></div>

											<a class="button button-primary button-large" aria-label="Edit this order" href="https://amazon.dev.test/wp-admin/post.php?action=edit&amp;post=75">Edit</a>
										</div>
									</footer>
								</section>
							</div>
						</div>
						<div class="wc-backbone-modal-backdrop modal-close"></div>
					</script>

					<?php
	}




	public function ced_ebay_add_menus() {
		global $submenu;
		$menu_slug = 'woocommerce';

		if ( ! empty( $submenu[ $menu_slug ] ) ) {
			$sub_menus = array_column( $submenu[ $menu_slug ], 2 );
			if ( ! in_array( 'sales_channel', $sub_menus ) ) {
				add_submenu_page( 'woocommerce', 'Sales Channel', 'Sales Channel', 'manage_woocommerce', 'sales_channel', array( $this, 'ced_marketplace_home_page' ) );
			}
		}
	}

	public function ced_show_connected_accounts_callback( $channel ) {
		if ( 'ebay' == $channel ) {
			$ebay_connected_accounts = ! empty( get_option( 'ced_ebay_connected_accounts', true ) ) ? get_option( 'ced_ebay_connected_accounts', true ) : array();
			if ( ! empty( $ebay_connected_accounts ) && is_array( $ebay_connected_accounts ) ) {
				$total_connected_counts = count( $ebay_connected_accounts );
			} else {
				$total_connected_counts = 0;
			}
			if ( $total_connected_counts > 0 ) {
				?>

							<a class="woocommerce-importer-done-view-errors-ebay" href="javascript:void(0)" ><?php echo esc_html( $total_connected_counts ); ?> 				
							<?php echo esc_html( $total_connected_counts ) > 1 ? 'accounts' : 'account'; ?>
							connected <span class="dashicons dashicons-arrow-down-alt2"></span></a>  

							<?php
			}
		}
	}

	public function ced_show_connected_accounts_details_callback( $channel ) {
		if ( 'ebay' == $channel ) {

			$ebay_connected_accounts = ! empty( get_option( 'ced_ebay_connected_accounts', true ) ) ? get_option( 'ced_ebay_connected_accounts', true ) : array();
			if ( ! empty( $ebay_connected_accounts ) && is_array( $ebay_connected_accounts ) ) {
				?>
							<tr class="wc-importer-error-log-ebay" style="display:none;">
								<td colspan="4">
									<div>
										<div class="ced-account-connected-form">
											<div class="ced-account-head">
												<div class="ced-account-label">
													<strong>Account Details</strong>
												</div>
												<div class="ced-account-label">
													<strong>Status</strong>
												</div> 
												<div class="ced-account-label">
													<!-- <strong>Status</strong> -->
												</div> 
											</div>

								<?php

								foreach ( $ebay_connected_accounts as $key => $ebay_sites ) {
									$ebay_user_id = $key;
									if ( ! empty( $ebay_user_id ) && is_array( $ebay_sites ) ) {
										foreach ( $ebay_sites as $ebay_site => $connection_status ) {
											$site_details = ced_ebay_get_site_details( $ebay_site );
											$site_name    = isset( $site_details['name'] ) ? $site_details['name'] : '';
											?>
														<div class="ced-account-body">
															<div class="ced-acount-body-label">
																<strong><?php echo esc_html__( $ebay_user_id . ' (' . $site_name . ')', 'ebay-integration-for-woocommerce' ); ?></strong>
															</div>
															<div class="ced-connected-button-wrapper">
													<?php
													if ( isset( $connection_status['onboarding_error'] ) && 'unable_to_connect' == $connection_status['onboarding_error'] ) {
														$visit_url            = get_admin_url() . 'admin.php?page=sales_channel&channel=ebay&section=setup-ebay&add-new-account=yes';
														$overview_section_url = get_admin_url() . 'admin.php?page=sales_channel&channel=ebay&section=overview&user_id=' . $ebay_user_id . '&site_id=' . $ebay_site;
														?>
																	<a class="ced-pending-link" href="<?php echo esc_url( $visit_url ); ?>"><span class="ced-circle"></span>Unable to connect with eBay</a>
																</div>
															</div>
																<div class="ced-account-button">																											
																	<button id="" type="button" data-ebay-user="<?php echo esc_attr( $ebay_user_id ); ?>" data-site="<?php echo esc_attr( $ebay_site ); ?>" class="ced_ebay_disconnect_account_btn components-button is-tertiary"> <?php echo esc_html__( 'Disconnect', 'ebay-integration-for-woocommerce' ); ?></button>
																	<a type="button" class="components-button is-primary" href="<?php echo esc_url( $overview_section_url ); ?>">Manage</a></div>
																
																<?php
													} elseif ( isset( $connection_status['ced_ebay_current_step'] ) && 1 < (int) $connection_status['ced_ebay_current_step'] ) {
															$visit_url = get_admin_url() . 'admin.php?page=sales_channel&channel=ebay&section=overview&user_id=' . $ebay_user_id . '&site_id=' . $ebay_site;

														?>
																	<a style="width: 33%;" class="ced-connected-link-account" href=""><span class="ced-circle"></span>Onboarding Completed</a>
																</div>

																	<div class="ced-account-button">																											
																		<button id="" data-site="<?php echo esc_attr( $ebay_site ); ?>" data-ebay-user="<?php echo esc_attr( $ebay_user_id ); ?>" type="button" class="ced_ebay_disconnect_account_btn components-button is-tertiary"> <?php echo esc_html__( 'Disconnect', 'ebay-integration-for-woocommerce' ); ?></button>
																		<a type="button" class="components-button is-primary" href="<?php echo esc_url( $visit_url ); ?>">Manage</a></div>
																	</div>

																	<?php
													} else {
														$current_step = isset( $connection_status['ced_ebay_current_step'] ) ? (int) $connection_status['ced_ebay_current_step'] : false;
														if ( false === $current_step ) {
															$urlKey = 'section=setup-ebay&add-new-account=yes';
														} elseif ( 0 == $current_step ) {
															$urlKey = 'section=onboarding-global-options&user_id=' . $ebay_user_id . '&site_id=' . $ebay_site;
														} elseif ( 1 == $current_step ) {
															$urlKey = 'section=onboarding-general-settings&user_id=' . $ebay_user_id . '&site_id=' . $ebay_site;
														}
														$visit_url = get_admin_url() . 'admin.php?page=sales_channel&channel=ebay&' . $urlKey;

														?>
																<a class="ced-pending-link-account" href="<?php echo esc_url( $visit_url ); ?>"><span class="ced-circle"></span>Onboarding Pending</a>
															</div>
															<div class="ced-account-button">																											
																<button  type="button" data-site="<?php echo esc_attr( $ebay_site ); ?>" data-ebay-user="<?php echo esc_attr( $ebay_user_id ); ?>" class="ced_ebay_disconnect_account_btn components-button is-tertiary"> <?php echo esc_html__( 'Disconnect', 'ebay-integration-for-woocommerce' ); ?></button>
																<a type="button" class="components-button is-primary" href="<?php echo esc_url( $visit_url ); ?>">Manage</a></div>
															</div>
															<?php

													}
													?>
													</div>
													<?php
										}
									}
								}
			}
			?>
									</div>
								</td>
							</tr>
				<?php
		}
	}



	public function ced_marketplace_home_page() {
		?>
		<div class='woocommerce'>
			<?php
			require CED_EBAY_DIRPATH . 'admin/partials/home.php';
			if ( isset( $_GET['page'] ) && 'sales_channel' == $_GET['page'] && ! isset( $_GET['channel'] ) ) {
				require CED_EBAY_DIRPATH . 'admin/partials/marketplaces.php';
			} else {
				$channel = ! empty( $_GET['channel'] ) ? sanitize_text_field( $_GET['channel'] ) : '';
				/**
				 *
				 * This action will be used in each plugin and basis of url segments to load the marketplace landing page.
				 *
				 * @since  1.0.0
				 */
				do_action( 'ced_sales_channel_include_template', $channel );
			}
			?>
		</div>
		<?php
	}




	public function ced_ebay_add_marketplace_menus_to_array( $menus = array() ) {

		$installed_plugins = get_plugins();
		$menus             = array(
			'woocommerce-etsy-integration'        => array(
				'name'            => 'Etsy Integration',
				'tab'             => 'Etsy',
				'page_url'        => 'https://woocommerce.com/products/etsy-integration-for-woocommerce/',
				'doc_url'         => 'https://woocommerce.com/document/etsy-integration-for-woocommerce/',
				'slug'            => 'woocommerce-etsy-integration',
				'menu_link'       => 'etsy',
				'card_image_link' => CED_EBAY_URL . 'admin/images/etsy-logo.png',
				/**
				 * Active_plugins.
				 *
				 * @since 1.0.0
				 */
				'is_active'       => in_array( 'woocommerce-etsy-integration/woocommerce-etsy-integration.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ),
				'is_installed'    => isset( $installed_plugins['woocommerce-etsy-integration/woocommerce-etsy-integration.php'] ) ? true : false,
			),
			'walmart-integration-for-woocommerce' => array(
				'name'            => 'Walmart Integration',
				'tab'             => 'Walmart',
				'page_url'        => 'https://woocommerce.com/products/walmart-integration-for-woocommerce/',
				'doc_url'         => 'https://woocommerce.com/document/walmart-integration-for-woocommerce/',
				'slug'            => 'walmart-integration-for-woocommerce',
				'menu_link'       => 'walmart',
				'card_image_link' => CED_EBAY_URL . 'admin/images/walmart-logo.png',
				/**
				 * Active_plugins.
				 *
				 * @since 1.0.0
				 */
				'is_active'       => in_array( 'walmart-integration-for-woocommerce/walmart-woocommerce-integration.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ),
				'is_installed'    => isset( $installed_plugins['walmart-integration-for-woocommerce/walmart-woocommerce-integration.php'] ) ? true : false,
			),
			'ebay-integration-for-woocommerce'    => array(
				'name'            => 'eBay Integration',
				'tab'             => 'eBay',
				'page_url'        => 'https://woocommerce.com/products/ebay-integration-for-woocommerce/',
				'doc_url'         => 'https://woocommerce.com/document/ebay-integration-for-woocommerce/',
				'slug'            => 'ebay-integration-for-woocommerce',
				'menu_link'       => 'ebay',
				'card_image_link' => CED_EBAY_URL . 'admin/images/ebay-logo.png',
				/**
				 * Active_plugins.
				 *
				 * @since 1.0.0
				 */
				'is_active'       => in_array( 'ebay-integration-for-woocommerce/woocommerce-ebay-integration.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ),
				'is_installed'    => isset( $installed_plugins['ebay-integration-for-woocommerce/woocommerce-ebay-integration.php'] ) ? true : false,
			),
			'amazon-for-woocommerce'              => array(
				'name'            => 'Amazon Integration',
				'tab'             => 'Amazon',
				'page_url'        => 'https://woocommerce.com/products/walmart-integration-for-woocommerce/',
				'doc_url'         => 'https://woocommerce.com/document/amazon-integration-for-woocommerce/',
				'slug'            => 'amazon-for-woocommerce',
				'menu_link'       => 'amazon',
				'card_image_link' => CED_EBAY_URL . 'admin/images/amazon-logo.png',
				/**
				 * Active_plugins.
				 *
				 * @since 1.0.0
				 */
				'is_active'       => in_array( 'amazon-for-woocommerce/amazon-for-woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ),
				'is_installed'    => isset( $installed_plugins['amazon-for-woocommerce/amazon-for-woocommerce.php'] ) ? true : false,
			),
		);
		return $menus;
	}

	/*
	 *
	 *Function for Storing mapped categories
	 *
	 *
	 */

	public function ced_ebay_get_categories_hierarchical( $args = array() ) {

		if ( ! isset( $args['parent'] ) ) {
			$args['parent'] = 0;
		}

		$categories = get_categories( $args );

		foreach ( $categories as $key => $category ) :

			$args['parent'] = $category->term_id;

			$categories[ $key ]->child_categories = $this->ced_ebay_get_categories_hierarchical( $args );

		endforeach;

		return $categories;
	}

	public function ced_ebay_category_refresh_button() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$ced_ebay_manager = $this->ced_ebay_manager;
			$sanitized_array  = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
			$userid           = isset( $_POST['userid'] ) ? sanitize_text_field( $_POST['userid'] ) : '';
			$site_id          = isset( $_POST['site_id'] ) ? sanitize_text_field( $_POST['site_id'] ) : '';
			$levels           = isset( $sanitized_array['levels'] ) ? ( $sanitized_array['levels'] ) : '';
			$shop_data        = ced_ebay_get_shop_data( $userid, $site_id );
			if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
				$siteID = $site_id;
				$token  = $shop_data['access_token'];
			} else {
				echo json_encode(
					array(
						'status'  => 'error',
						'message' => 'Invalid eBay site or account!',
					)
				);
				die;
			}
			$pre_flight_check = ced_ebay_pre_flight_check( $userid );
			if ( ! $pre_flight_check ) {
				echo json_encode(
					array(
						'status'  => 'error',
						'message' => 'Unable to fetch categories. Token has been revoked.',
					)
				);
				die;
			}
			$site_details = ced_ebay_get_site_details( $siteID );
			if ( empty( $site_details ) ) {
				echo json_encode(
					array(
						'status'  => 'error',
						'message' => 'Unable to eBay site data',
					)
				);
				die;
			}
			$getLocation   = isset( $site_details['name'] ) ? $site_details['name'] : function () {
				echo json_encode(
					array(
						'status'  => 'error',
						'message' => 'Unable to eBay site data',
					)
				);
				die;
			};
			$getLocation   = strtolower( $getLocation );
			$getLocation   = str_replace( ' ', '', $getLocation );
			$wp_folder     = wp_upload_dir();
			$wp_upload_dir = $wp_folder['basedir'];
			$wp_upload_dir = $wp_upload_dir . '/ced-ebay/category-templates-json/';
			if ( ! is_dir( $wp_upload_dir ) ) {
				wp_mkdir_p( $wp_upload_dir, 0777 );
			}
			$fileCategory = CED_EBAY_DIRPATH . 'admin/ebay/lib/cedGetcategories.php';
			if ( file_exists( $fileCategory ) ) {
				require_once $fileCategory;
			}
			$categoryLevel  = 0;
			$levels         = isset( $sanitized_array['levels'] ) ? ( $sanitized_array['levels'] ) : '';
			$cedCatInstance = CedGetCategories::get_instance( $siteID, $token );
			foreach ( $levels as $level ) {
				$getCat = $cedCatInstance->_getCategories( $level );
				if ( $getCat && is_array( $getCat ) ) {
					$cates = array();
					foreach ( $getCat['CategoryArray']['Category'] as $key => $value ) {
						if ( "$level" == $value['CategoryLevel'] ) {
							$cates[] = $value;
						}
					}

					$getCat['CategoryArray']['Category'] = $cates;
					$folderName                          = $wp_upload_dir;
					$fileName                            = $folderName . 'categoryLevel-' . $level . '_' . $getLocation . '.json';
					$upload_dir                          = wp_upload_dir();
					$file                                = fopen( $fileName, 'w' );
					$sanitized_json                      = json_encode( $getCat, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS );
					$pieces                              = str_split( $sanitized_json, 1024 );
					foreach ( $pieces as $piece ) {
						if ( ! fwrite( $file, $piece, strlen( $piece ) ) ) {
							echo json_encode(
								array(
									'status'  => 'error',
									'message' => 'Permission Denied',
								)
							);
							die;
						}
					}
					fclose( $file );
					echo json_encode(
						array(
							'statuts' => 'success',
							'level'   => $level,
						)
					);
					die;

				} else {
					echo json_encode(
						array(
							'status'  => 'error',
							'message' => 'Unable to fetch categories.',
						)
					);

					die;
				}
			}
			echo json_encode(
				array(
					'status'  => 'success',
					'message' => 'Success',
				)
			);
			die;
		}
	}

	public function ced_ebay_get_wp_filesystem() {
		global $wp_filesystem;

		if ( is_null( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem;
	}


	public function ced_ebay_process_bulk_action() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$ced_ebay_manager = $this->ced_ebay_manager;
			$user_id          = isset( $_POST['userid'] ) ? sanitize_text_field( $_POST['userid'] ) : '';
			$site_id          = isset( $_POST['site_id'] ) ? sanitize_text_field( $_POST['site_id'] ) : '';
			$shop_data        = ced_ebay_get_shop_data( $user_id, $site_id );
			if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
				$siteID      = $site_id;
				$token       = $shop_data['access_token'];
				$getLocation = $shop_data['location'];
			} else {
				echo json_encode(
					array(
						'status'  => 400,
						'message' => 'Unable to verify eBay user.',
						'prodid'  => '',
						'title'   => 'Error',
					)
				);
				die;
			}

			$access_token_arr = get_option( 'ced_ebay_user_access_token' );
			if ( ! empty( $access_token_arr ) ) {
				foreach ( $access_token_arr as $key => $value ) {
					$tokenValue = get_transient( 'ced_ebay_user_access_token_' . $key );
					if ( false === $tokenValue || null == $tokenValue || empty( $tokenValue ) ) {
						$user_refresh_token = $value['refresh_token'];
						$this->ced_ebay_save_user_access_token( $key, $user_refresh_token, 'refresh_user_token' );
					}
				}
			}

			$pre_flight_check = ced_ebay_pre_flight_check( $user_id, $siteID );
			$sanitized_array  = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
			$operation        = isset( $sanitized_array['operation_to_be_performed'] ) ? ( $sanitized_array['operation_to_be_performed'] ) : '';
			$product_id       = isset( $sanitized_array['id'] ) ? ( $sanitized_array['id'] ) : '';
			if ( $pre_flight_check ) {
				if ( is_array( $product_id ) ) {
					if ( 'upload_product' == $operation ) {
						global $wpdb;
						$prodIDs    = $product_id[0];
						$wc_product = wc_get_product( $prodIDs );
						if ( 'false' === get_option( 'ced_ebay_out_of_stock_preference_' . $user_id, true ) ) {

							if ( $wc_product->is_type( 'simple' ) ) {
								$count_simple_product_in_stock = $wpdb->get_var(
									$wpdb->prepare(
										"
										SELECT COUNT(ID)
										FROM {$wpdb->posts} p
										INNER JOIN {$wpdb->postmeta} pm
										ON p.ID           =  pm.post_id
										WHERE p.post_type     =  'product'
										AND p.post_status =  'publish'
										AND p.ID =  %d
										AND pm.meta_key   =  '_stock_status'
										AND pm.meta_value != 'outofstock'
										",
										$prodIDs
									)
								);
								$count_simple_product_in_stock = $count_simple_product_in_stock > 0 ? true : false;
								if ( ! $count_simple_product_in_stock ) {
									echo json_encode(
										array(
											'status'  => 400,
											'message' => 'Unable to upload to eBay. The product is out of stock',
											'prodid'  => $prodIDs,
											'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
										)
									);
									die;
								}
							} elseif ( $wc_product->is_type( 'variable' ) ) {
								// Skip a varible product from upload if all of its variations are out of stock.
								$count_variations_in_stock = $wpdb->get_var(
									$wpdb->prepare(
										"
										SELECT COUNT(ID)
										FROM {$wpdb->posts} p
										INNER JOIN {$wpdb->postmeta} pm
										ON p.ID           =  pm.post_id
										WHERE p.post_type     =  'product_variation'
										AND p.post_status =  'publish'
										AND p.post_parent =  %d
										AND pm.meta_key   =  '_stock_status'
										AND pm.meta_value != 'outofstock'
										",
										$prodIDs
									)
								);
								$count_variations_in_stock = $count_variations_in_stock > 0 ? true : false;
								if ( ! $count_variations_in_stock ) {
									echo json_encode(
										array(
											'status'  => 400,
											'message' => 'Unable to upload to eBay. All the variations of the product out of stock',
											'prodid'  => $prodIDs,
											'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
										)
									);
									die;
								}
							}
						}
						$already_uploaded = get_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
						$wp_folder        = wp_upload_dir();
						$wp_upload_dir    = $wp_folder['basedir'];
						$wp_upload_dir    = $wp_upload_dir . '/ced-ebay/logs/upload/';
						if ( ! is_dir( $wp_upload_dir ) ) {
							wp_mkdir_p( $wp_upload_dir, 0777 );
						}
						$log_file_product_xml = $wp_upload_dir . 'product_' . $prodIDs . '.xml';
						if ( $already_uploaded ) {
							echo json_encode(
								array(
									'status'  => 400,
									'message' => 'Product Already Uploaded',
									'prodid'  => $prodIDs,
									'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
								)
							);
							die;
						} else {
							if ( file_exists( $log_file_product_xml ) ) {
								wp_delete_file( $log_file_product_xml );
							}
							$SimpleXml = $ced_ebay_manager->prepareProductHtmlForUpload( $user_id, $siteID, $prodIDs );

							if ( is_array( $SimpleXml ) && ! empty( $SimpleXml ) && ! isset( $SimpleXml['error'] ) ) {

								if ( function_exists( 'simplexml_load_string' ) ) {
									$addItemXml                                      = simplexml_load_string( $SimpleXml[0] );
									$addItemXml->RequesterCredentials->eBayAuthToken = 'xxx';
									$addItemXmlOutput                                = $addItemXml->asXML();
									file_put_contents( $log_file_product_xml, $addItemXmlOutput );
								}
								require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
							} elseif ( isset( $SimpleXml['error'] ) ) {
								echo json_encode(
									array(
										'status'  => 400,
										'message' => $SimpleXml['error'],
										'prodid'  => $prodIDs,
										'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',

									)
								);
								die;
							}
							$log_file_product_upload = $wp_upload_dir . 'upload_' . $prodIDs . '.json';
							$ebayUploadInstance      = EbayUpload::get_instance( $siteID, $token );
							$uploadOnEbay            = $ebayUploadInstance->upload( $SimpleXml[0], $SimpleXml[1] );

							if ( file_exists( $log_file_product_upload ) ) {
								wp_delete_file( $log_file_product_upload );
							}
							file_put_contents( $log_file_product_upload, json_encode( $uploadOnEbay ) );
							if ( ! is_array( $uploadOnEbay ) && ! empty( $uploadOnEbay ) ) {
								echo json_encode(
									array(
										'status'  => 400,
										'message' => 'No Profile Assigned to the product.',
										'prodid'  => $prodIDs,
										'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
									)
								);
								die;
							}
							if ( isset( $uploadOnEbay['Ack'] ) ) {
								if ( 'Warning' == $uploadOnEbay['Ack'] || 'Success' == $uploadOnEbay['Ack'] ) {
									$ebayID = $uploadOnEbay['ItemID'];
									update_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, $ebayID );
									echo json_encode(
										array(
											'status'  => 200,
											'message' => 'Product Uploaded Successfully',
											'prodid'  => $prodIDs,
											'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
										)
									);
									die;
								} else {
									$error = '';
									if ( isset( $uploadOnEbay['Errors'][0] ) ) {
										foreach ( $uploadOnEbay['Errors'] as $key => $value ) {
											if ( 'Error' == $value['SeverityCode'] ) {
												$error_data = str_replace( array( '<', '>' ), array( '{', '}' ), $value['LongMessage'] );
												$error     .= $error_data . '<br>';
											}
										}
									} else {
										$error_data = str_replace( array( '<', '>' ), array( '{', '}' ), $uploadOnEbay['Errors']['LongMessage'] );
										$error     .= $error_data . '<br>';
									}
									echo json_encode(
										array(
											'status'  => 400,
											'message' => $error,
											'prodid'  => $prodIDs,
											'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
										)
									);
									die;
								}
							} else {
								echo json_encode(
									array(
										'status'  => 400,
										'message' => ! empty( $uploadOnEbay ) ? $uploadOnEbay : 'Some error occured! Please try again later.',
										'prodid'  => $prodIDs,
										'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',

									)
								);
								die;
							}
						}
					} elseif ( 'link_with_eBay' == $operation ) {
						$prodIDs          = $product_id[0];
						$wc_product       = wc_get_product( $prodIDs );
						$already_uploaded = get_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
						if ( ! class_exists( 'XMLReader' ) ) {
							echo json_encode(
								array(
									'status'  => 400,
									'message' => 'Unable to process! Please contact support.',
									'prodid'  => $prodIDs,
									'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',

								)
							);
							die;
						}
						if ( ! $already_uploaded ) {
							$latestInventoryReportFilePath = '';
							$wp_filesystem                 = $this->ced_ebay_get_wp_filesystem();
							global $wp_filesystem;
							require_once ABSPATH . '/wp-admin/includes/file.php';
							WP_Filesystem();
							$destination      = wp_upload_dir();
							$destination_path = $destination['basedir'] . '/ced-ebay/';
							$files            = glob( $destination_path . 'activeinventory-*' );
							if ( is_array( ( $files ) ) && ! empty( $files ) ) {
								usort(
									$files,
									function ( $a, $b ) {
										return filemtime( $b ) - filemtime( $a );
									}
								);
								$latest_file               = $files[0];
								$latestInventoryReportFile = basename( $latest_file );
								if ( file_exists( $destination_path . $latestInventoryReportFile ) ) {
									$latestInventoryReportFilePath = $destination_path . $latestInventoryReportFile;
								}
							}
							if ( empty( $latestInventoryReportFilePath ) ) {
								echo json_encode(
									array(
										'status'  => 400,
										'message' => 'Missing Inventory File. Please try again after sometime.',
										'prodid'  => $prodIDs,
										'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',

									)
								);
								die;
							}
							if ( $wc_product->is_type( 'variable' ) ) {
								$linkedVariationsCount = 0;
								$foundSku              = array();
								$wcProductVariations   = $wc_product->get_children();
								$itemID                = '';
								foreach ( $wcProductVariations as $key => $varProdID ) {
									if ( ! empty( get_post_meta( $varProdID, '_sku', true ) ) ) {
										$reader = new XMLReader();
										$reader->open( $latestInventoryReportFilePath );
										$varSku           = get_post_meta( $varProdID, '_sku', true );
										$insideVariations = false;
										while ( $reader->read() ) {
											if ( XMLReader::ELEMENT == $reader->nodeType ) {
												if ( 'ItemID' == $reader->name ) {
													// Get the ItemID value
													$itemID = $reader->readString();
												}
												if ( 'Variations' == $reader->name ) {
													$insideVariations = true;
												} elseif ( 'Variations' == $reader->name && ! $reader->isEmptyElement ) {
													$insideVariations = false;
												}

												if ( 'SKU' == $insideVariations && $reader->name ) {
													$sku = $reader->readString();
													if ( $sku == $varSku ) {
														$foundSku[ $itemID ][] = array(
															'SKU' => $sku,
														);
														++$linkedVariationsCount;
													}
												}
											}
										}
									}
								}
								$reader->close();
								if ( ! empty( $linkedVariationsCount ) && ! empty( $foundSku ) ) {
									$eBayItemIdKey = array();
									$eBayItemIdKey = array_keys( $foundSku );
									$eBayItemId    = $eBayItemIdKey[0];
									update_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, $eBayItemId );
									echo json_encode(
										array(
											'status'  => 200,
											'message' => 'Successfully linked ' . $linkedVariationsCount . ' variations of the product to the eBay Item ' . $eBayItemId,
											'prodid'  => $prodIDs,
											'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',

										)
									);
									die;
								} else {
									echo json_encode(
										array(
											'status'  => 400,
											'message' => 'Unable to link variations of the product. Please ensure that the SKUs on both eBay and WooCommerce are same for the variations.',
											'prodid'  => $prodIDs,
											'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',

										)
									);
									die;
								}
							} elseif ( $wc_product->is_type( 'simple' ) ) {

								if ( ! empty( get_post_meta( $prodIDs, '_sku', true ) ) ) {
									$foundSku         = false;
									$itemID           = '';
									$simpleProductSku = get_post_meta( $prodIDs, '_sku', true );
									$reader           = new XMLReader();
									$reader->open( $latestInventoryReportFilePath );
									$skipNode = false; // Flag to skip <SKUDetails> element if it has <Variations>
									$sku      = '';
									while ( $reader->read() ) {

										if ( XMLReader::ELEMENT === $reader->nodeType && 'SKUDetails' === $reader->name ) {
											$skipNode = $reader->isEmptyElement; // Check if the <SKUDetails> element is empty
										}

										if ( XMLReader::ELEMENT === $reader->nodeType && 'SKU' === $reader->name ) {
											if ( ! $skipNode ) {
												$reader->read(); // Move to the text node
												$sku = $reader->value; // Get the SKU value
												if ( $sku == $simpleProductSku ) {
													$foundSku = true;
												}
											}
										}

										if ( XMLReader::ELEMENT === $reader->nodeType && 'ItemID' === $reader->name ) {
											if ( ! $skipNode ) {
												$reader->read(); // Move to the text node
												$itemID = $reader->value; // Get the ItemID value
											}
										}

										if ( XMLReader::END_ELEMENT === $reader->nodeType && 'SKUDetails' === $reader->name ) {
											if ( ! empty( $foundSku ) && ! empty( $itemID ) ) {
												break; // Found both SKU and ItemID, exit the loop
											}
											$skipNode = false; // Reset the skipNode flag
										}
									}
									// print_r($itemID);die;
									$reader->close();
									if ( ! empty( $foundSku ) && ! empty( $itemID ) ) {
										$eBayItemId = $itemID;
										update_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, $eBayItemId );
										echo json_encode(
											array(
												'status'  => 200,
												'message' => 'Successfully linked the WooCommerce SKU ' . $simpleProductSku . ' to the eBay Item ' . $eBayItemId,
												'prodid'  => $prodIDs,
												'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',

											)
										);
										die;
									} else {
										echo json_encode(
											array(
												'status'  => 400,
												'message' => 'Unable to link the WooCommerce product. Please ensure that the SKUs on both eBay and WooCommerce are same for the product.',
												'prodid'  => $prodIDs,
												'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',

											)
										);
										die;
									}
								} else {
									echo json_encode(
										array(
											'status'  => 400,
											'message' => 'Missing WooCommerce SKU',
											'prodid'  => $prodIDs,
											'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',

										)
									);
									die;
								}
							} else {
								echo json_encode(
									array(
										'status'  => 400,
										'message' => 'This product type is not supported',
										'prodid'  => $prodIDs,
										'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',

									)
								);
								die;
							}
						} else {
							echo json_encode(
								array(
									'status'  => 400,
									'message' => 'This WooCommerce product is already linked with eBay',
									'prodid'  => $prodIDs,
									'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',

								)
							);
							die;
						}
					}

					if ( 'sync_from_ebay' == $operation ) {
						$prodIDs          = $product_id[0];
						$wc_product       = wc_get_product( $prodIDs );
						$already_uploaded = get_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
						if ( $already_uploaded ) {
							echo json_encode(
								array(
									'status'  => 400,
									'message' => 'Product is already Synced!',
									'prodid'  => $prodIDs,
								)
							);
							die;
						} else {
							require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
							$ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
							$synced_items       = $ebayUploadInstance->sync_from_ebay( $prodIDs );

						}
					} elseif ( 'update_description' == $operation ) {
						$prodIDs          = $product_id[0];
						$wc_product       = wc_get_product( $prodIDs );
						$already_uploaded = get_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
						if ( $already_uploaded ) {
							$SimpleXml = $ced_ebay_manager->prepareProductHtmlForUpdatingDescription( $user_id, $prodIDs );
							require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
							$ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
							$uploadOnEbay       = $ebayUploadInstance->update( $SimpleXml[0], $SimpleXml[1] );
							if ( is_array( $uploadOnEbay ) && ! empty( $uploadOnEbay ) ) {
								if ( isset( $uploadOnEbay['Ack'] ) ) {
									if ( 'Warning' == $uploadOnEbay['Ack'] || 'Success' == $uploadOnEbay['Ack'] ) {
										$ebayID = $uploadOnEbay['ItemID'];
										echo json_encode(
											array(
												'status'  => 200,
												'message' => 'Product Updated Successfully',
												'prodid'  => $prodIDs,
											)
										);
										die;
									} else {
										$error = '';
										if ( isset( $uploadOnEbay['Errors'][0] ) ) {
											foreach ( $uploadOnEbay['Errors'] as $key => $value ) {
												if ( 'Error' == $value['SeverityCode'] ) {
													$error .= $value['ShortMessage'] . '<br>';
												}
											}
										} else {
											$error .= $uploadOnEbay['Errors']['ShortMessage'] . '<br>';
										}
										echo json_encode(
											array(
												'status'  => 400,
												'message' => $error,
												'prodid'  => $prodIDs,
											)
										);
										die;
									}
								}
							}
						} else {
							echo json_encode(
								array(
									'status'  => 400,
									'message' => __(
										'Product Not Found On eBay',
										'woocommerce-ebay-integration'
									),
								)
							);
							die;
						}
					} elseif ( 'relist_product' == $operation ) {
						$prodIDs          = $product_id[0];
						$wc_product       = wc_get_product( $prodIDs );
						$already_uploaded = get_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
						if ( $already_uploaded ) {
							$itemIDs[ $prodIDs ] = $already_uploaded;
							require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
							$ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
							$itemId             = get_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
							$check_stauts_xml   = '
							<?xml version="1.0" encoding="utf-8"?>
							<GetItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
							<RequesterCredentials>
							<eBayAuthToken>' . $token . '</eBayAuthToken>
							</RequesterCredentials>
							<DetailLevel>ReturnAll</DetailLevel>
							<ItemID>' . $itemId . '</ItemID>
							</GetItemRequest>';
							$itemDetails        = $ebayUploadInstance->get_item_details( $check_stauts_xml );
							if ( 'Success' == $itemDetails['Ack'] || 'Warning' == $itemDetails['Ack'] ) {
								if ( ! empty( $itemDetails['Item']['ListingDetails']['EndingReason'] ) || 'Completed' == $itemDetails['Item']['SellingStatus']['ListingStatus'] ) {
									update_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id, $itemId );
									$relistXML = $ced_ebay_manager->prepareProductHtmlForRelist( $user_id, $prodIDs, $siteID );
									require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
									$ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
									$relistOnEbay       = $ebayUploadInstance->relist( $relistXML );
									if ( is_array( $relistOnEbay ) && ! empty( $relistOnEbay ) ) {
										if ( isset( $relistOnEbay['Ack'] ) ) {
											if ( 'Warning' == $relistOnEbay['Ack'] || 'Success' == $relistOnEbay['Ack'] ) {
												$ebayID = $relistOnEbay['ItemID'];
												update_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, $ebayID );
												echo json_encode(
													array(
														'status' => 200,
														'message' => 'Product Re-Listed Successfully',
														'prodid' => $prodIDs,
														'title' => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
													)
												);
												die;
											} else {
												$error = '';
												if ( isset( $relistOnEbay['Errors'][0] ) ) {
													foreach ( $relistOnEbay['Errors'] as $key => $value ) {
														if ( 'Error' == $value['SeverityCode'] ) {
															$error .= $value['ShortMessage'] . '<br>';
														}
													}
												} else {
													$error .= $relistOnEbay['Errors']['ShortMessage'] . '<br>';
												}
												echo json_encode(
													array(
														'status' => 400,
														'message' => $error,
														'prodid' => $prodIDs,
														'title' => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
													)
												);
												die;
											}
										}
									}
								} else {
									$archiveProducts = $ebayUploadInstance->endItems( $itemIDs );
									if ( is_array( $archiveProducts ) && ! empty( $archiveProducts ) ) {
										if ( isset( $archiveProducts['Ack'] ) ) {
											if ( 'Warning' == $archiveProducts['Ack'] || 'Success' == $archiveProducts['Ack'] ) {
												update_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id, $already_uploaded );
												$relistXML = $ced_ebay_manager->prepareProductHtmlForRelist( $user_id, $prodIDs, $siteID );
												require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
												$ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
												$relistOnEbay       = $ebayUploadInstance->relist( $relistXML );
												if ( is_array( $relistOnEbay ) && ! empty( $relistOnEbay ) ) {
													if ( isset( $relistOnEbay['Ack'] ) ) {
														if ( 'Warning' == $relistOnEbay['Ack'] || 'Success' == $relistOnEbay['Ack'] ) {
															$ebayID = $relistOnEbay['ItemID'];
															update_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, $ebayID );
															echo json_encode(
																array(
																	'status' => 200,
																	'message' => 'Product Re-Listed Successfully',
																	'prodid' => $prodIDs,
																	'title' => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
																)
															);
															die;
														} else {
															$error = '';
															if ( isset( $relistOnEbay['Errors'][0] ) ) {
																foreach ( $relistOnEbay['Errors'] as $key => $value ) {
																	if ( 'Error' == $value['SeverityCode'] ) {
																		$error .= $value['ShortMessage'] . '<br>';
																	}
																}
															} else {
																$error .= $relistOnEbay['Errors']['ShortMessage'] . '<br>';
															}
															echo json_encode(
																array(
																	'status' => 400,
																	'message' => $error,
																	'prodid' => $prodIDs,
																	'title' => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
																)
															);
															die;
														}
													}
												}
											} else {
												delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
												echo json_encode(
													array(
														'status' => 400,
														'message' => 'Failed to end the listing on eBay. Please contact support.',
														'prodid' => $prodIDs,
														'title' => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',

													)
												);
												die;
											}
										}
									}
								}
							}
						} else {
							echo json_encode(
								array(
									'status'  => 400,
									'message' => __(
										'Product Not Found On eBay',
										'woocommerce-ebay-integration'
									),
									'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',

								)
							);
							die;
						}
					} elseif ( 'update_product' == $operation ) {
						$prodIDs          = $product_id[0];
						$wc_product       = wc_get_product( $prodIDs );
						$already_uploaded = get_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
						$wp_folder        = wp_upload_dir();
						$wp_upload_dir    = $wp_folder['basedir'];
						$wp_upload_dir    = $wp_upload_dir . '/ced-ebay/logs/update/';
						if ( ! is_dir( $wp_upload_dir ) ) {
							wp_mkdir_p( $wp_upload_dir, 0777 );
						}

						if ( $already_uploaded ) {
							if ( 'false' === get_option( 'ced_ebay_out_of_stock_preference_' . $user_id, true ) ) {
								global $wpdb;
								if ( $wc_product->is_type( 'simple' ) ) {
									$count_product_in_stock = $wpdb->get_var(
										$wpdb->prepare(
											"
											SELECT COUNT(ID)
											FROM {$wpdb->posts} p
											INNER JOIN {$wpdb->postmeta} pm
											ON p.ID           =  pm.post_id
											WHERE p.post_type     =  'product'
											AND p.post_status =  'publish'
											AND p.ID =  %d
											AND pm.meta_key   =  '_stock_status'
											AND pm.meta_value != 'outofstock'
											",
											$prodIDs
										)
									);
								} elseif ( $wc_product->is_type( 'variable' ) ) {
									$count_product_in_stock = $wpdb->get_var(
										$wpdb->prepare(
											"
											SELECT COUNT(ID)
											FROM {$wpdb->posts} p
											INNER JOIN {$wpdb->postmeta} pm
											ON p.ID           =  pm.post_id
											WHERE p.post_type     =  'product_variation'
											AND p.post_status =  'publish'
											AND p.post_parent =  %d
											AND pm.meta_key   =  '_stock_status'
											AND pm.meta_value != 'outofstock'
											",
											$prodIDs
										)
									);

								}
								$count_product_in_stock = $count_product_in_stock > 0 ? true : false;
								if ( ! $count_product_in_stock ) {

									$itemIDs[ $prodIDs ] = $already_uploaded;
									require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
									$ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
									$itemId             = get_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
									$check_stauts_xml   = '
									<?xml version="1.0" encoding="utf-8"?>
									<GetItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
									<RequesterCredentials>
									<eBayAuthToken>' . $token . '</eBayAuthToken>
									</RequesterCredentials>
									<DetailLevel>ReturnAll</DetailLevel>
									<ItemID>' . $itemId . '</ItemID>
									</GetItemRequest>';
									$itemDetails        = $ebayUploadInstance->get_item_details( $check_stauts_xml );
									if ( 'Success' == $itemDetails['Ack'] || 'Warning' == $itemDetails['Ack'] ) {
										if ( ! empty( $itemDetails['Item']['ListingDetails']['EndingReason'] ) || 'Completed' == $itemDetails['Item']['SellingStatus']['ListingStatus'] ) {
											delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
											delete_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id );
											global $wpdb;
											$remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id`=%s", $prodIDs, $user_id, $siteID ) );
											echo json_encode(
												array(
													'status' => 200,
													'message' => 'Product has been Reset!',
													'prodid' => $prodIDs,
													'title' => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
												)
											);
											die;
										}
									} elseif ( 'Failure' == $itemDetails['Ack'] ) {
										if ( ! empty( $itemDetails['Errors']['ErrorCode'] ) && '17' == $itemDetails['Errors']['ErrorCode'] ) {
											delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
											delete_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id );
											global $wpdb;
											$remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id`=%s", $prodIDs, $user_id, $siteID ) );
											echo json_encode(
												array(
													'status' => 200,
													'message' => 'Product has been Reset!',
													'prodid' => $prodIDs,
													'title' => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
												)
											);
											die;
										}
									}

									$archiveProducts = $ebayUploadInstance->endItems( $itemIDs );
									if ( is_array( $archiveProducts ) && ! empty( $archiveProducts ) ) {
										if ( isset( $archiveProducts['Ack'] ) ) {
											if ( 'Warning' == $archiveProducts['Ack'] || 'Success' == $archiveProducts['Ack'] ) {
												delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
												delete_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id );
												$remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id`=%s", $prodIDs, $user_id, $siteID ) );
												echo json_encode(
													array(
														'status' => 200,
														'message' => 'Product is out of stock and has been removed from eBay',
														'prodid' => $prodIDs,
														'title' => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
													)
												);
												die;
											} else {
												if ( 1047 == $archiveProducts['EndItemResponseContainer']['Errors']['ErrorCode'] ) {
													delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
													delete_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id );
													global $wpdb;
													$remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id`=%s", $prodIDs, $user_id, $siteID ) );
												}
												$endResponse = $archiveProducts['EndItemResponseContainer']['Errors']['LongMessage'];
												echo json_encode(
													array(
														'status' => 400,
														'message' => $endResponse,
														'prodid' => $prodIDs,
														'title' => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
													)
												);
												die;
											}
										}
									}
								}
							}
							$log_file = $wp_upload_dir . 'product_' . $prodIDs . '.xml';
							if ( file_exists( $log_file ) ) {
								wp_delete_file( $log_file );
							}
							$SimpleXml = $ced_ebay_manager->prepareProductHtmlForUpdate( $user_id, $siteID, $prodIDs );
							if ( is_array( $SimpleXml ) && ! empty( $SimpleXml ) ) {
								if ( function_exists( 'simplexml_load_string' ) ) {
									$reviseItemXml                                      = simplexml_load_string( $SimpleXml[0] );
									$reviseItemXml->RequesterCredentials->eBayAuthToken = 'xxx';
									$reviseItemXmlOutput                                = $reviseItemXml->asXML();
									file_put_contents( $log_file, $reviseItemXmlOutput );

								}
								require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
								$ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
							} elseif ( 'No Profile Assigned' == $SimpleXml ) {
								echo json_encode(
									array(
										'status'  => 400,
										'message' => 'No Profile Assigned to the product.',
										'prodid'  => $prodIDs,
										'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',

									)
								);
								die;
							} elseif ( 'No international country' == $SimpleXml ) {
								echo json_encode(
									array(
										'status'  => 400,
										'message' => 'Please select atleast one country in Shipping Template for International Shipping',
										'prodid'  => $prodIDs,
										'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
									)
								);
								die;
							}
							$log_file_product_update = $wp_upload_dir . 'update_' . $prodIDs . '.json';
							$uploadOnEbay            = $ebayUploadInstance->update( $SimpleXml[0], $SimpleXml[1] );
							if ( file_exists( $log_file_product_update ) ) {
								wp_delete_file( $log_file_product_update );
							}
							file_put_contents( $log_file_product_update, json_encode( $uploadOnEbay ) );
							if ( is_array( $uploadOnEbay ) && ! empty( $uploadOnEbay ) ) {
								if ( isset( $uploadOnEbay['Ack'] ) ) {
									if ( 'Warning' == $uploadOnEbay['Ack'] || 'Success' == $uploadOnEbay['Ack'] ) {
										$ebayID = $uploadOnEbay['ItemID'];
										echo json_encode(
											array(
												'status'  => 200,
												'message' => 'Product Updated Successfully',
												'prodid'  => $prodIDs,
												'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',

											)
										);
										die;
									} else {
										$error = '';

										if ( isset( $uploadOnEbay['Errors'][0] ) ) {
											foreach ( $uploadOnEbay['Errors'] as $key => $value ) {
												if ( 'Error' == $value['SeverityCode'] ) {
													$error .= $value['ShortMessage'] . '<br>';
												}
											}
										} else {
											$error .= $uploadOnEbay['Errors']['ShortMessage'] . '<br>';
										}
										echo json_encode(
											array(
												'status'  => 400,
												'message' => $error,
												'prodid'  => $prodIDs,
												'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
											)
										);
										die;
									}
								}
							}
						} else {
							echo json_encode(
								array(
									'status'  => 400,
									'message' => __(
										'Product Not Found On eBay',
										'woocommerce-ebay-integration'
									),
									'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
								)
							);
							die;
						}
					} elseif ( 'remove_product' == $operation ) {
						$prodIDs          = $product_id[0];
						$wc_product       = wc_get_product( $prodIDs );
						$already_uploaded = get_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
						if ( $already_uploaded ) {
							$itemIDs[ $prodIDs ] = $already_uploaded;
							require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
							$ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
							$itemId             = get_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
							$check_stauts_xml   = '
							<?xml version="1.0" encoding="utf-8"?>
							<GetItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
							<RequesterCredentials>
							<eBayAuthToken>' . $token . '</eBayAuthToken>
							</RequesterCredentials>
							<DetailLevel>ReturnAll</DetailLevel>
							<ItemID>' . $itemId . '</ItemID>
							</GetItemRequest>';
							$itemDetails        = $ebayUploadInstance->get_item_details( $check_stauts_xml );
							if ( 'Success' == $itemDetails['Ack'] || 'Warning' == $itemDetails['Ack'] ) {
								if ( ! empty( $itemDetails['Item']['ListingDetails']['EndingReason'] ) || 'Completed' == $itemDetails['Item']['SellingStatus']['ListingStatus'] ) {
									delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
									delete_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id . '>' . $siteID );
									global $wpdb;
									$remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id`=%s", $prodIDs, $user_id, $siteID ) );
									echo json_encode(
										array(
											'status'  => 200,
											'message' => 'Product has been Reset!',
											'prodid'  => $prodIDs,
											'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
										)
									);
									die;
								}
							} elseif ( 'Failure' == $itemDetails['Ack'] ) {
								if ( ! empty( $itemDetails['Errors']['ErrorCode'] ) && '17' == $itemDetails['Errors']['ErrorCode'] ) {
									delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
									delete_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id . '>' . $siteID );
									global $wpdb;
									$remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id`=%s", $prodIDs, $user_id, $siteID ) );
									echo json_encode(
										array(
											'status'  => 200,
											'message' => 'Product has been Reset!',
											'prodid'  => $prodIDs,
											'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
										)
									);
									die;
								}
							}

							$archiveProducts = $ebayUploadInstance->endItems( $itemIDs );
							if ( is_array( $archiveProducts ) && ! empty( $archiveProducts ) ) {
								if ( isset( $archiveProducts['Ack'] ) ) {
									if ( 'Warning' == $archiveProducts['Ack'] || 'Success' == $archiveProducts['Ack'] ) {
										delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
										delete_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id . '>' . $siteID );
										global $wpdb;
										$remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id`=%s", $prodIDs, $user_id, $siteID ) );
										echo json_encode(
											array(
												'status'  => 200,
												'message' => 'Product Deleted Successfully',
												'prodid'  => $prodIDs,
												'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
											)
										);
										die;
									} else {
										if ( 1047 == $archiveProducts['EndItemResponseContainer']['Errors']['ErrorCode'] ) {
											delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
											delete_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id . '>' . $siteID );
											global $wpdb;
											$remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id`=%s", $prodIDs, $user_id, $siteID ) );
										}
										$endResponse = $archiveProducts['EndItemResponseContainer']['Errors']['LongMessage'];
										echo json_encode(
											array(
												'status'  => 400,
												'message' => $endResponse,
												'prodid'  => $prodIDs,
												'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
											)
										);
										die;
									}
								}
							}
						} else {
							echo json_encode(
								array(
									'status'  => 400,
									'message' => __(
										'Product Not Found On eBay',
										'woocommerce-ebay-integration'
									),
									'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
								)
							);
							die;
						}
					} elseif ( 'update_stock' == $operation ) {

						$prodIDs          = $product_id[0];
						$wc_product       = wc_get_product( $prodIDs );
						$already_uploaded = get_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
						if ( $already_uploaded ) {
							if ( 'false' === get_option( 'ced_ebay_out_of_stock_preference_' . $user_id, true ) ) {
								global $wpdb;
								if ( $wc_product->is_type( 'simple' ) ) {
									$count_product_in_stock = $wpdb->get_var(
										$wpdb->prepare(
											"
											SELECT COUNT(ID)
											FROM {$wpdb->posts} p
											INNER JOIN {$wpdb->postmeta} pm
											ON p.ID           =  pm.post_id
											WHERE p.post_type     =  'product'
											AND p.post_status =  'publish'
											AND p.ID =  %d
											AND pm.meta_key   =  '_stock_status'
											AND pm.meta_value != 'outofstock'
											",
											$prodIDs
										)
									);
								} elseif ( $wc_product->is_type( 'variable' ) ) {
									$count_product_in_stock = $wpdb->get_var(
										$wpdb->prepare(
											"
											SELECT COUNT(ID)
											FROM {$wpdb->posts} p
											INNER JOIN {$wpdb->postmeta} pm
											ON p.ID           =  pm.post_id
											WHERE p.post_type     =  'product_variation'
											AND p.post_status =  'publish'
											AND p.post_parent =  %d
											AND pm.meta_key   =  '_stock_status'
											AND pm.meta_value != 'outofstock'
											",
											$prodIDs
										)
									);

								}
								$count_product_in_stock = $count_product_in_stock > 0 ? true : false;
								if ( ! $count_product_in_stock ) {
									$already_uploaded = get_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
									if ( $already_uploaded ) {
										$itemIDs[ $prodIDs ] = $already_uploaded;
										require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
										$ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
										$itemId             = get_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
										$check_stauts_xml   = '
										<?xml version="1.0" encoding="utf-8"?>
										<GetItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
										<RequesterCredentials>
										<eBayAuthToken>' . $token . '</eBayAuthToken>
										</RequesterCredentials>
										<DetailLevel>ReturnAll</DetailLevel>
										<ItemID>' . $itemId . '</ItemID>
										</GetItemRequest>';
										$itemDetails        = $ebayUploadInstance->get_item_details( $check_stauts_xml );
										if ( 'Success' == $itemDetails['Ack'] || 'Warning' == $itemDetails['Ack'] ) {
											if ( isset( $itemDetails['Item']['ListingDetails']['RelistedItemID'] ) ) {
												update_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, $itemDetails['Item']['ListingDetails']['RelistedItemID'] );
												update_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id, $already_uploaded );
											}
											if ( ! empty( $itemDetails['Item']['ListingDetails']['EndingReason'] ) || 'Completed' == $itemDetails['Item']['SellingStatus']['ListingStatus'] ) {
												delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
												delete_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id );
												global $wpdb;
												$remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id`=%s", $prodIDs, $user_id, $siteID ) );

												echo json_encode(
													array(
														'status' => 200,
														'message' => 'Product has been Reset!',
														'prodid' => $prodIDs,
														'title' => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
													)
												);
												die;
											}
										} elseif ( 'Failure' == $itemDetails['Ack'] ) {
											if ( ! empty( $itemDetails['Errors']['ErrorCode'] ) && '17' == $itemDetails['Errors']['ErrorCode'] ) {
												delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
												delete_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id );

												global $wpdb;
												$remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id`=%s", $prodIDs, $user_id, $siteID ) );
												echo json_encode(
													array(
														'status' => 200,
														'message' => 'Product has been Reset!',
														'prodid' => $prodIDs,
														'title' => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
													)
												);
												die;
											}
										}

										$archiveProducts = $ebayUploadInstance->endItems( $itemIDs );
										if ( is_array( $archiveProducts ) && ! empty( $archiveProducts ) ) {
											if ( isset( $archiveProducts['Ack'] ) ) {
												if ( 'Warning' == $archiveProducts['Ack'] || 'Success' == $archiveProducts['Ack'] ) {
													delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
													delete_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id );
													$remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id`=%s", $prodIDs, $user_id, $siteID ) );

													echo json_encode(
														array(
															'status' => 200,
															'message' => 'Product is out of stock and has been removed from eBay',
															'prodid' => $prodIDs,
															'title' => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
														)
													);
													die;
												} else {
													if ( 1047 == $archiveProducts['EndItemResponseContainer']['Errors']['ErrorCode'] ) {
														delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
														delete_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id );
														global $wpdb;
														$remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id`=%s", $prodIDs, $user_id, $siteID ) );

													}
													$endResponse = $archiveProducts['EndItemResponseContainer']['Errors']['LongMessage'];

													echo json_encode(
														array(
															'status' => 400,
															'message' => $endResponse,
															'prodid' => $prodIDs,
															'title' => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
														)
													);
													die;
												}
											}
										}
									}
								}
								require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
								$ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
								$SimpleXml          = $ced_ebay_manager->prepareProductHtmlForUpdateStock( $user_id, $siteID, $prodIDs, false );
								if ( is_array( $SimpleXml ) && ! empty( $SimpleXml ) ) {

									foreach ( $SimpleXml as $key => $value ) {
										$uploadOnEbay[] = $ebayUploadInstance->cedEbayUpdateInventory( $value );
									}
								} else {

									$uploadOnEbay = $ebayUploadInstance->cedEbayUpdateInventory( $SimpleXml );
								}

								if ( is_array( $uploadOnEbay ) && ! empty( $uploadOnEbay[0] ) ) {
									foreach ( $uploadOnEbay as $key => $inventory_update ) {
										if ( isset( $inventory_update['Ack'] ) ) {
											if ( 'Warning' == $inventory_update['Ack'] || 'Success' == $inventory_update['Ack'] ) {

												$ebayID = $inventory_update['ItemID'];
												echo json_encode(
													array(
														'status' => 200,
														'message' => 'Stock Updated Successfully',
														'prodid' => $prodIDs,
														'title' => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
													)
												);
												die;
											} else {

												$error = '';
												if ( isset( $inventory_update['Errors'][0] ) ) {
													foreach ( $inventory_update['Errors'] as $key => $value ) {
														if ( 'Error' == $value['SeverityCode'] ) {
															if ( '231' == $value['ErrorCode'] || '21916750' == $value['ErrorCode'] ) {
																delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
																delete_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id );
															}
															$error .= $value['ShortMessage'] . '<br>';
														}
													}
												} else {
													if ( '231' == $inventory_update['Errors']['ErrorCode'] || '21916750' == $inventory_update['Errors']['ErrorCode'] ) {
														delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
														delete_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id );
													}
													$error .= $inventory_update['Errors']['ShortMessage'] . '<br>';
												}
											}
										}
									}

									echo json_encode(
										array(
											'status'  => 400,
											'message' => $error,
											'prodid'  => $prodIDs,
											'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
										)
									);
									die;
								} elseif ( isset( $uploadOnEbay['Ack'] ) ) {
									if ( 'Warning' == $uploadOnEbay['Ack'] || 'Success' == $uploadOnEbay['Ack'] ) {

										echo json_encode(
											array(
												'status'  => 200,
												'message' => 'Stock Updated Successfully',
												'prodid'  => $prodIDs,
												'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
											)
										);
										die;
									} else {

										$error = '';
										if ( isset( $uploadOnEbay['Errors'][0] ) ) {
											foreach ( $uploadOnEbay['Errors'] as $key => $value ) {
												if ( 'Error' == $value['SeverityCode'] ) {
													if ( '231' == $value['ErrorCode'] || '21916750' == $value['ErrorCode'] ) {
														delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
														delete_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id );
													}
													$error .= $value['ShortMessage'] . '<br>';
												}
											}
										} else {
											if ( '231' == $uploadOnEbay['Errors']['ErrorCode'] || '21916750' == $uploadOnEbay['Errors']['ErrorCode'] ) {
												delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
												delete_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id );
											}
											$error .= $uploadOnEbay['Errors']['ShortMessage'] . '<br>';
										}

										echo json_encode(
											array(
												'status'  => 400,
												'message' => $error,
												'prodid'  => $prodIDs,
												'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
											)
										);
										die;
									}
								}
							}

							require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
							$ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
							$SimpleXml          = $ced_ebay_manager->prepareProductHtmlForUpdateStock( $user_id, $siteID, $prodIDs, false );
							if ( is_array( $SimpleXml ) && ! empty( $SimpleXml ) ) {

								foreach ( $SimpleXml as $key => $value ) {
									$uploadOnEbay[] = $ebayUploadInstance->cedEbayUpdateInventory( $value );
								}
							} else {

								$uploadOnEbay = $ebayUploadInstance->cedEbayUpdateInventory( $SimpleXml );
							}

							if ( is_array( $uploadOnEbay ) && ! empty( $uploadOnEbay[0] ) ) {
								foreach ( $uploadOnEbay as $key => $inventory_update ) {
									if ( isset( $inventory_update['Ack'] ) ) {
										if ( 'Warning' == $inventory_update['Ack'] || 'Success' == $inventory_update['Ack'] ) {
											$ebayID = $inventory_update['ItemID'];

											echo json_encode(
												array(
													'status' => 200,
													'message' => 'Stock Updated Successfully',
													'prodid' => $prodIDs,
													'title' => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
												)
											);
											die;
										} else {

											$error = '';
											if ( isset( $inventory_update['Errors'][0] ) ) {
												foreach ( $inventory_update['Errors'] as $key => $value ) {
													if ( 'Error' == $value['SeverityCode'] ) {
														if ( '231' == $value['ErrorCode'] || '21916750' == $value['ErrorCode'] ) {
															delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
															delete_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id );
														}
														$error .= $value['ShortMessage'] . '<br>';
													}
												}
											} else {
												if ( '231' == $inventory_update['Errors']['ErrorCode'] || '21916750' == $inventory_update['Errors']['ErrorCode'] ) {
													delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
													delete_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id );
												}
												$error .= $inventory_update['Errors']['ShortMessage'] . '<br>';
											}
										}
									}
								}

								echo json_encode(
									array(
										'status'  => 400,
										'message' => $error,
										'prodid'  => $prodIDs,
										'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
									)
								);
								die;
							} elseif ( isset( $uploadOnEbay['Ack'] ) ) {
								if ( 'Warning' == $uploadOnEbay['Ack'] || 'Success' == $uploadOnEbay['Ack'] ) {

									echo json_encode(
										array(
											'status'  => 200,
											'message' => 'Stock Updated Successfully',
											'prodid'  => $prodIDs,
											'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
										)
									);
									die;
								} else {

									$error = '';
									if ( isset( $uploadOnEbay['Errors'][0] ) ) {
										foreach ( $uploadOnEbay['Errors'] as $key => $value ) {
											if ( 'Error' == $value['SeverityCode'] ) {
												if ( '231' == $value['ErrorCode'] || '21916750' == $value['ErrorCode'] ) {
													delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
													delete_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id );
												}
												$error .= $value['ShortMessage'] . '<br>';
											}
										}
									} else {
										if ( '231' == $uploadOnEbay['Errors']['ErrorCode'] || '21916750' == $uploadOnEbay['Errors']['ErrorCode'] ) {
											delete_post_meta( $prodIDs, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
											delete_post_meta( $prodIDs, '_ced_ebay_relist_item_id_' . $user_id );
										}
										$error .= $uploadOnEbay['Errors']['ShortMessage'] . '<br>';
									}

									echo json_encode(
										array(
											'status'  => 400,
											'message' => $error,
											'prodid'  => $prodIDs,
											'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
										)
									);
									die;
								}
							}
						} else {
							echo json_encode(
								array(
									'status'  => 400,
									'message' => __(
										'Product Not Found On eBay',
										'woocommerce-ebay-integration'
									),
									'title'   => ! empty( $wc_product->get_title() ) ? $wc_product->get_title() : '',
								)
							);
							die;
						}
					}
				}
			} else {
				echo json_encode(
					array(
						'status'  => 400,
						'message' => 'We are not able to connect to eBay at the moment. Please try again later. If the issue persists, please contact support.',
						'title'   => 'Error',
					)
				);
				die;
			}
		}
	}

	public function ced_ebay_manually_ended_listings_manager( $data = array() ) {
		$fetchCurrentAction = current_action();
		$this->ced_ebay_onWpAdminInit();
		if ( strpos( $fetchCurrentAction, 'wp_ajax_nopriv_' ) !== false ) {
			$user_id = isset( $_GET['user_id'] ) ? wc_clean( $_GET['user_id'] ) : false;
			$site_id = isset( $_GET['site_id'] ) ? wc_clean( $_GET['site_id'] ) : false;
		} else {
			$user_id = isset( $data['user_id'] ) ? $data['user_id'] : '';
			$site_id = isset( $data['site_id'] ) ? $data['site_id'] : '';
		}

		$logger  = wc_get_logger();
		$context = array( 'source' => 'ced_ebay_manually_ended_listings_manager' );
		$logger->info( '>>>Commencing scheduled job', $context );
		$shop_data = ced_ebay_get_shop_data( $user_id, $site_id );
		if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
			$siteID = $site_id;
			$token  = $shop_data['access_token'];
		} else {
			return false;
		}
		$file             = CED_EBAY_DIRPATH . 'admin/ebay/class-ebay.php';
		$renderDependency = $this->renderDependency( $file );
		if ( $renderDependency ) {
			$cedeBay             = new Class_Ced_EBay_Manager();
			$cedebayInstance     = $cedeBay->get_instance();
			$page_number         = ! empty( get_option( 'ced_ebay_ended_listings_pagination_' . $user_id . '>' . $siteID ) ) ? get_option( 'ced_ebay_ended_listings_pagination_' . $user_id . '>' . $siteID, true ) : 1;
			$ended_products_list = $cedebayInstance->ced_ebay_get_manually_ended_listings( $token, $siteID, $page_number );
			if ( 'api-error' != $ended_products_list ) {
				$logger->info( wc_print_r( 'Page Number ' . $page_number, true ), $context );
				++$page_number;
				update_option( 'ced_ebay_ended_listings_pagination_' . $user_id . '>' . $siteID, $page_number );
				if ( ! empty( $ended_products_list['ItemArray']['Item'] ) ) {
					if ( ! isset( $ended_products_list['ItemArray']['Item'][0] ) ) {
						$temp_ended_list = array();
						$temp_ended_list = $ended_products_list['ItemArray']['Item'];
						unset( $ended_products_list['ItemArray']['Item'] );
						$ended_products_list['ItemArray']['Item'][] = $temp_ended_list;
					}
					foreach ( $ended_products_list['ItemArray']['Item'] as $key => $ended_ebay_listing ) {
						if ( ! empty( $ended_ebay_listing['ItemID'] ) && isset( $ended_ebay_listing['ListingDetails']['EndingReason'] ) ) {

							$logger->info( wc_print_r( 'Listing ' . $ended_ebay_listing['ItemID'] . ' has been manually ended on eBay', true ), $context );
							if ( isset( $ended_ebay_listing['ListingDetails']['RelistedItemID'] ) && ! empty( $ended_ebay_listing['ListingDetails']['RelistedItemID'] ) ) {
								$logger->info( wc_print_r( 'Listing ' . $ended_ebay_listing['ItemID'] . ' has been relisted on eBay', true ), $context );
							}
							$itemId        = $ended_ebay_listing['ItemID'];
							$store_product = get_posts(
								array(
									'numberposts'  => -1,
									'post_type'    => 'product',
									'meta_key'     => '_ced_ebay_listing_id_' . $user_id . '>' . $siteID,
									'meta_value'   => $itemId,
									'meta_compare' => '=',
								)
							);
							$store_product = wp_list_pluck( $store_product, 'ID' );
							if ( ! empty( $store_product ) ) {
								$product_id = $store_product[0];
								$logger->info( wc_print_r( 'Found Woo Product ' . $product_id, true ), $context );
								$product = wc_get_product( $product_id );
								$product->set_stock_quantity( '0' );
								$product->set_stock_status( 'outofstock' );
								$product->save();
								delete_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
								delete_post_meta( $product_id, '_ced_ebay_relist_item_id_' . $user_id . '>' . $siteID );
								if ( isset( $ended_ebay_listing['ListingDetails']['RelistedItemID'] ) && ! empty( $ended_ebay_listing['ListingDetails']['RelistedItemID'] ) ) {
									$logger->info( wc_print_r( 'Relisted item ID is ' . $ended_ebay_listing['ListingDetails']['RelistedItemID'], true ), $context );
									update_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, $ended_ebay_listing['ListingDetails']['RelistedItemID'] );
									update_post_meta( $product_id, '_ced_ebay_relist_item_id_' . $user_id . '>' . $siteID, $ended_ebay_listing['ListingDetails']['RelistedItemID'] );
								}
								$product->save();
							}
						}
					}
				} else {
					$logger->info( 'Unable to get ItemArray', $context );
					update_option( 'ced_ebay_ended_listings_pagination_' . $user_id . '>' . $siteID, 1 );
				}
			} else {
				$logger->info( 'API Error', $context );
				update_option( 'ced_ebay_ended_listings_pagination_' . $user_id . '>' . $siteID, 1 );
			}
		}
	}




	public function ced_ebay_fetch_order_using_order_id() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$order_id  = isset( $_POST['order_id'] ) ? sanitize_text_field( $_POST['order_id'] ) : '';
			$user_id   = isset( $_POST['userid'] ) ? sanitize_text_field( $_POST['userid'] ) : '';
			$site_id   = isset( $_POST['site_id'] ) ? sanitize_text_field( $_POST['site_id'] ) : '';
			$shop_data = ced_ebay_get_shop_data( $user_id, $site_id );
			if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
				$siteID      = $site_id;
				$token       = $shop_data['access_token'];
				$getLocation = $shop_data['location'];
			}
			require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayOrders.php';
			require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/cedMarketingRequest.php';
			$fulfillmentRequest = new Ced_Marketing_API_Request( $siteID );
			$get_order_detail   = $fulfillmentRequest->sendHttpRequestForFulfillmentAPI( '/' . $order_id . '?fieldGroups=TAX_BREAKDOWN', $token, '', '' );
			$get_order_detail   = json_decode( $get_order_detail, true );
			if ( ! empty( $get_order_detail ) ) {
				if ( isset( $get_order_detail['orders'] ) ) {
					$order = $get_order_detail['orders'];
				} else {
					$order[] = $get_order_detail;
				}

				$orderInstance = EbayOrders::get_instance( $siteID, $token );
				$createOrder   = $orderInstance->create_localOrders( $order, $siteID, $user_id );
				wp_send_json_success(
					array(
						'message' => 'Processed eBay Order ' . $order_id . ' successfully',
					)
				);
			} else {
				wp_send_json_error(
					array(
						'message' => 'We can\'t fetch the eBay order ' . $order_id . '. It might have been that you may have entered an Invalid Order ID. If the issue still persists, please contact support.',
					)
				);
				die;
			}
		}
	}

	public function ced_ebay_get_orders() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$user_id   = isset( $_POST['userid'] ) ? sanitize_text_field( $_POST['userid'] ) : '';
			$site_id   = isset( $_POST['site_id'] ) ? sanitize_text_field( $_POST['site_id'] ) : '';
			$shop_data = ced_ebay_get_shop_data( $user_id, $site_id );
			if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
				$siteID      = $site_id;
				$token       = $shop_data['access_token'];
				$getLocation = $shop_data['location'];
			} else {
				wp_send_json_error(
					array(
						'message' => 'Invalid eBay user or site',
					)
				);
			}

			$file                   = CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayOrders.php';
			$renderDependency       = $this->renderDependency( $file );
			$fulfillmentRequestFile = CED_EBAY_DIRPATH . 'admin/ebay/lib/cedMarketingRequest.php';
			$renderDependency       = $this->renderDependency( $fulfillmentRequestFile );
			if ( $renderDependency ) {
				$currentime = time();
				$toDate     = $currentime - ( 1 * 60 );
				$fromDate   = $currentime - ( 3 * 24 * 60 * 60 );
				$offset     = '.000Z';
				$toDate     = gmdate( 'Y-m-d', $toDate ) . 'T' . gmdate( 'H:i:s', $toDate ) . $offset;
				$fromDate   = gmdate( 'Y-m-d', $fromDate ) . 'T' . gmdate( 'H:i:s', $fromDate ) . $offset;
				$endpoint   = '?filter=creationdate:%5B' . $fromDate . '..' . $toDate . '%5D,orderfulfillmentstatus:%7BNOT_STARTED%7CIN_PROGRESS%7D';

				$fulfillmentRequest = new Ced_Marketing_API_Request( $siteID );
				$get_orders_data    = $fulfillmentRequest->sendHttpRequestForFulfillmentAPI( $endpoint, $token, '', '' );
				// $get_orders_data = file_get_contents( CED_EBAY_DIRPATH . 'admin/fulfillment_api.json' );
				$fetchorders = json_decode( $get_orders_data, true );
				if ( isset( $fetchorders['total'] ) && 0 < $fetchorders['total'] ) {
					$getTotalOrdersCount = $fetchorders['total'];
				} else {
					$getTotalOrdersCount = 0;
					wp_send_json_error(
						array(
							'message' => 'We couldn\'t find any eBay Orders. If you think this is an error, please contact support.',
						)
					);
				}
				if ( ! empty( $fetchorders ) ) {
					if ( isset( $fetchorders['orders'] ) ) {
						$order = $fetchorders['orders'];
					} else {
						$order[] = $fetchorders;
					}
					$orderInstance = EbayOrders::get_instance( $siteID, $token );
					$createOrder   = $orderInstance->create_localOrders( $order, $siteID, $user_id );
					wp_send_json_success(
						array(
							'message' => 'Successfully processed ' . $getTotalOrdersCount . ' eBay Orders',
						)
					);

				} else {
					wp_send_json_error(
						array(
							'message' => 'We couldn\'t find any eBay Orders. If you think this is an error, please contact support.',
						)
					);
				}
			}
		}
	}







	public function ced_ebay_update_stock_on_webhook() {
		$wp_folder     = wp_upload_dir();
		$wp_upload_dir = $wp_folder['basedir'];
		$logs_folder   = $wp_upload_dir . '/ced-ebay/logs/stock-update/webhook/';
		$current_date  = new DateTime();
		$current_date  = $current_date->format( 'ymd' );
		$log_file      = $logs_folder . 'logs_' . $current_date . '.txt';
		if ( ! file_exists( $log_file ) ) {
			file_put_contents( $log_file, '' );
		}
		ced_ebay_log_data( 'Commence Webhook based stock update', 'ced_ebay_async_update_stock_callback', $log_file );
		if ( file_get_contents( 'php://input' ) ) {
			$response_body    = json_decode( file_get_contents( 'php://input' ), true );
			$product_id       = $response_body['id'];
			$access_token_arr = get_option( 'ced_ebay_user_access_token' );
			if ( ! empty( $access_token_arr ) && ! empty( $product_id ) ) {
				foreach ( $access_token_arr as $user_id => $value ) {
					if ( ! empty( get_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID ) ) ) {
						ced_ebay_log_data( 'Webhook stock update | Product ID ' . $product_id, 'ced_ebay_async_update_stock_callback', $log_file );
						$stock_update_data               = array();
						$stock_update_data['product_id'] = $product_id;
						$stock_update_data['user_id']    = $user_id;
						$this->ced_ebay_async_update_stock_callback( $stock_update_data, $log_file );
					} else {
						ced_ebay_log_data( 'Woo product #' . $product_id . ' is not an eBay listing!', 'ced_ebay_async_update_stock_callback', $log_file );
						ced_ebay_log_data( '----------------', 'ced_ebay_async_update_stock_callback', $log_file );

					}
				}
			}
		}
	}



	public function ced_ebay_inventory_schedule_manager( $data = array() ) {
		$fetchCurrentAction = current_action();
		if ( strpos( $fetchCurrentAction, 'wp_ajax_nopriv_' ) !== false ) {
			$user_id = isset( $_GET['user_id'] ) ? wc_clean( $_GET['user_id'] ) : false;
		} else {
			$user_id = isset( $data['user_id'] ) ? $data['user_id'] : '';
			$site_id = isset( $data['site_id'] ) ? $data['site_id'] : '';
		}
		$ced_ebay_manager = $this->ced_ebay_manager;
		$shop_data        = ced_ebay_get_shop_data( $user_id, $site_id );
		$logger           = wc_get_logger();
		$context          = array( 'source' => 'ced_ebay_inventory_schedule_manager' );
		if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
			$siteID      = $site_id;
			$token       = $shop_data['access_token'];
			$getLocation = $shop_data['location'];
		} else {
			return false;
		}
		$access_token_arr = get_option( 'ced_ebay_user_access_token' );
		if ( ! empty( $access_token_arr ) ) {
			foreach ( $access_token_arr as $key => $value ) {
				$tokenValue = get_transient( 'ced_ebay_user_access_token_' . $key );
				if ( false === $tokenValue || null == $tokenValue || empty( $tokenValue ) ) {
					$user_refresh_token = $value['refresh_token'];
					$this->ced_ebay_save_user_access_token( $key, $user_refresh_token, 'refresh_user_token' );
				}
			}
		}
		$pre_flight_check = ced_ebay_pre_flight_check( $user_id, $siteID );
		if ( ! $pre_flight_check ) {
			return;
		}
		$file             = CED_EBAY_DIRPATH . 'admin/ebay/class-ebay.php';
		$renderDependency = $this->renderDependency( $file );
		if ( $renderDependency ) {
			$cedeBay           = new Class_Ced_EBay_Manager();
			$cedebayInstance   = $cedeBay->get_instance();
			$seller_prefrences = $cedebayInstance->ced_ebay_get_seller_preferences( $user_id, $token, $siteID );
		}

		$products_to_update = get_option( 'ced_eBay_update_chunk_product_' . $user_id . '>' . $siteID, array() );
		if ( empty( $products_to_update ) ) {
			$logger->info( '#####Commencing Stock Update#####', $context );
			$store_products = get_posts(
				array(
					'numberposts' => -1,
					'post_type'   => 'product',
					'post_status' => 'publish',
					'fields'      => 'ids',
					'meta_query'  => array(
						array(
							'key'     => '_ced_ebay_listing_id_' . $user_id . '>' . $siteID,
							'compare' => 'EXISTS',
						),
					),
				)
			);
			update_option(
				'ced_ebay_stock_sync_progress_' . $user_id . '>' . $siteID,
				array(
					'total_count' => count( $store_products ),
					'synced'      => 0,
				)
			);
			$products_to_update = array_chunk( $store_products, 50 );
		}
		if ( is_array( $products_to_update[0] ) && ! empty( $products_to_update[0] ) ) {
			$prodIDs = $products_to_update[0];
			foreach ( $products_to_update[0] as $key => $value ) {
				$product_actual_stock = get_post_meta( $value, '_stock', true );
				$manage_stock         = get_post_meta( $value, '_manage_stock', true );
				$product_status       = get_post_meta( $value, '_stock_status', true );
				if ( 'yes' != $manage_stock && 'instock' == $product_status ) {
					$renderDataOnGlobalSettings = get_option( 'ced_ebay_global_settings', false );
					$default_stock              = isset( $renderDataOnGlobalSettings[ $user_id ][ $siteID ]['ced_ebay_product_default_stock'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $siteID ]['ced_ebay_product_default_stock'] : 0;
					$product_actual_stock       = $default_stock;
				}

				$async_action_id = as_enqueue_async_action(
					'ced_ebay_async_update_stock_action',
					array(
						'data' => array(
							'product_id' => $value,
							'user_id'    => $user_id,
							'site_id'    => $siteID,
						),
					),
					'ced_ebay_inventory_scheduler_group_' . $user_id . '>' . $siteID
				);
				if ( $async_action_id ) {
					$logger->info( wc_print_r( 'Product ID ' . $value, true ), $context );
					$logger->info( wc_print_r( 'Async Stock Update Scheduled with ID ' . $async_action_id, true ), $context );
				}
			}
			unset( $products_to_update[0] );
			$products_to_update = array_values( $products_to_update );
			update_option( 'ced_eBay_update_chunk_product_' . $user_id . '>' . $siteID, $products_to_update );
			$logger->info( '--------------------------------', $context );

		}
	}


	public function ced_ebay_async_update_stock_callback( $stock_update_data, $log_file = '' ) {
		$synced = 0;
		if ( ! empty( $stock_update_data ) && ! empty( $stock_update_data['product_id'] ) && ! empty( $stock_update_data['user_id'] ) && is_array( $stock_update_data ) ) {
			$product_id = $stock_update_data['product_id'];
			$user_id    = $stock_update_data['user_id'];
			$site_id    = $stock_update_data['site_id'];
			$woo_prd    = wc_get_product( $product_id );
			ced_ebay_log_data( 'eBay User ID ' . $user_id, 'ced_ebay_async_update_stock_callback', $log_file );
			ced_ebay_log_data( 'Product ID ' . $product_id, 'ced_ebay_async_update_stock_callback', $log_file );
			$ced_ebay_manager = $this->ced_ebay_manager;
			$shop_data        = ced_ebay_get_shop_data( $user_id, $site_id );
			if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
				$siteID      = $site_id;
				$token       = $shop_data['access_token'];
				$getLocation = $shop_data['location'];
			} else {
				return false;
			}
			$pre_flight_check = ced_ebay_pre_flight_check( $user_id, $siteID );
			if ( ! $pre_flight_check ) {
				return false;
			}
			require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
			$ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
			$already_uploaded   = get_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
			if ( ! $already_uploaded ) {
				ced_ebay_log_data( 'Product is not an eBay listing. Returning!', 'ced_ebay_async_update_stock_callback', $log_file );
				return;
			}
			if ( $already_uploaded ) {
				$itemIDs[ $product_id ] = $already_uploaded;
				require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
				$ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
				$check_stauts_xml   = '
				<?xml version="1.0" encoding="utf-8"?>
				<GetItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
				<RequesterCredentials>
				<eBayAuthToken>' . $token . '</eBayAuthToken>
				</RequesterCredentials>
				<DetailLevel>ReturnAll</DetailLevel>
				<ItemID>' . $already_uploaded . '</ItemID>
				</GetItemRequest>';
				$itemDetails        = $ebayUploadInstance->get_item_details( $check_stauts_xml );
				if ( 'Success' == $itemDetails['Ack'] || 'Warning' == $itemDetails['Ack'] ) {

					if ( isset( $itemDetails['Item']['ListingDetails']['RelistedItemID'] ) ) {
						update_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, $itemDetails['Item']['ListingDetails']['RelistedItemID'] );
						update_post_meta( $product_id, '_ced_ebay_relist_item_id_' . $user_id, $already_uploaded );
					}
				}
			}
			if ( 'false' === get_option( 'ced_ebay_out_of_stock_preference_' . $user_id, true ) ) {
				$file             = CED_EBAY_DIRPATH . 'admin/ebay/class-ebay.php';
				$renderDependency = $this->renderDependency( $file );
				if ( $renderDependency ) {
					$cedeBay               = new Class_Ced_EBay_Manager();
					$cedebayInstance       = $cedeBay->get_instance();
					$check_if_out_of_stock = $cedebayInstance->ced_ebay_check_out_of_stock_product( $user_id, $product_id );
					if ( ! $check_if_out_of_stock ) {
						ced_ebay_log_data( 'Product ID ' . $product_id . ' is out of stock on WooCommerce!', 'ced_ebay_async_update_stock_callback', $log_file );
						$already_uploaded = get_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
						if ( $already_uploaded ) {
							$itemIDs[ $product_id ] = $already_uploaded;
							if ( 'Success' == $itemDetails['Ack'] || 'Warning' == $itemDetails['Ack'] ) {
								if ( ! empty( $itemDetails['Item']['ListingDetails']['EndingReason'] ) || 'Completed' == $itemDetails['Item']['SellingStatus']['ListingStatus'] ) {
									delete_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
									delete_post_meta( $product_id, '_ced_ebay_relist_item_id_' . $user_id . '>' . $siteID );
									global $wpdb;
									$remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id`=%s", $product_id, $user_id, $siteID ) );
									ced_ebay_log_data( 'eBay ID ' . $already_uploaded . ' has been ended from eBay. Resetting product.', 'ced_ebay_async_update_stock_callback', $log_file );
								}
							} elseif ( 'Failure' == $itemDetails['Ack'] ) {
								if ( ! empty( $itemDetails['Errors']['ErrorCode'] ) && '17' == $itemDetails['Errors']['ErrorCode'] ) {
									delete_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
									delete_post_meta( $product_id, '_ced_ebay_relist_item_id_' . $user_id . '>' . $siteID );
									global $wpdb;
									$remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id`=%s", $product_id, $user_id, $siteID ) );
									ced_ebay_log_data( 'eBay ID ' . $already_uploaded . ' error code 17. Resetting product.', 'ced_ebay_async_update_stock_callback', $log_file );
								}
							}

							$archiveProducts = $ebayUploadInstance->endItems( $itemIDs );
							if ( is_array( $archiveProducts ) && ! empty( $archiveProducts ) ) {
								if ( isset( $archiveProducts['Ack'] ) ) {
									if ( 'Warning' == $archiveProducts['Ack'] || 'Success' == $archiveProducts['Ack'] ) {
										delete_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
										delete_post_meta( $product_id, '_ced_ebay_relist_item_id_' . $user_id . '>' . $siteID );
										global $wpdb;
										$remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id` = %s", $product_id, $user_id, $siteID ) );
										ced_ebay_log_data( 'eBay ID ' . $already_uploaded . ' Removed from eBay successfully', 'ced_ebay_async_update_stock_callback', $log_file );
									} else {
										if ( 1047 == $archiveProducts['EndItemResponseContainer']['Errors']['ErrorCode'] ) {
											delete_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
											delete_post_meta( $product_id, '_ced_ebay_relist_item_id_' . $user_id . '>' . $siteID );
											global $wpdb;
											$remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id` = %s", $product_id, $user_id, $siteID ) );
										}
										$endResponse = $archiveProducts['EndItemResponseContainer']['Errors']['LongMessage'];
										ced_ebay_log_data( 'eBay ID ' . $already_uploaded . ' ' . $endResponse, 'ced_ebay_async_update_stock_callback', $log_file );
									}
								}
							}
						}
						ced_ebay_log_data( '----------------', 'ced_ebay_async_update_stock_callback', $log_file );
						return;
					}
				}
			}
			$SimpleXml = $ced_ebay_manager->prepareProductHtmlForUpdateStock( $user_id, $siteID, $product_id );
			if ( is_array( $SimpleXml ) && ! empty( $SimpleXml ) ) {
				$stock_sync_progress = get_option( 'ced_ebay_stock_sync_progress_' . $user_id . '>' . $siteID );
				if ( ! empty( $stock_sync_progress ) ) {
					$synced      = $stock_sync_progress['synced'];
					$total_count = $stock_sync_progress['total_count'];
					++$synced;
					update_option(
						'ced_ebay_stock_sync_progress_' . $user_id . '>' . $siteID,
						array(
							'total_count' => $total_count,
							'synced'      => $synced,
						)
					);
				}
				ced_ebay_log_data( 'Product has more than 4 variations.', 'ced_ebay_async_update_stock_callback', $log_file );
				foreach ( $SimpleXml as $key => $value ) {
					$uploadOnEbay[] = $ebayUploadInstance->cedEbayUpdateInventory( $value );

				}
			} else {
				$stock_sync_progress = get_option( 'ced_ebay_stock_sync_progress_' . $user_id . '>' . $siteID );
				if ( ! empty( $stock_sync_progress ) ) {
					$synced      = $stock_sync_progress['synced'];
					$total_count = $stock_sync_progress['total_count'];
					++$synced;
					update_option(
						'ced_ebay_stock_sync_progress_' . $user_id . '>' . $siteID,
						array(
							'total_count' => $total_count,
							'synced'      => $synced,
						)
					);
				}
				$uploadOnEbay = $ebayUploadInstance->cedEbayUpdateInventory( $SimpleXml );

				if ( ! empty( $uploadOnEbay ) ) {
					if ( 'Failure' == $uploadOnEbay['Ack'] ) {
						$temp_inv_update_error = array();
						if ( ! isset( $uploadOnEbay['Errors'][0] ) ) {
							$temp_inv_update_error = $uploadOnEbay['Errors'];
							unset( $uploadOnEbay['Errors'] );
							$uploadOnEbay['Errors'][] = $temp_inv_update_error;
						}
						if ( ! empty( $uploadOnEbay['Errors'] ) ) {
							foreach ( $uploadOnEbay['Errors'] as $key => $ebay_api_error ) {
								if ( '21916750' == $ebay_api_error['ErrorCode'] ) {
									delete_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
									delete_post_meta( $product_id, '_ced_ebay_relist_item_id_' . $user_id );
									global $wpdb;
									$remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id`=%s", $product_id, $user_id, $siteID ) );
									ced_ebay_log_data( 'Stock sync unable to update the listing ' . $already_uploaded . ' since it is removed from eBay!', 'ced_ebay_async_update_stock_callback' );
								}
								if ( '518' == $ebay_api_error['ErrorCode'] ) {
									if ( function_exists( 'as_unschedule_all_actions' ) ) {
										as_unschedule_all_actions( 'ced_ebay_inventory_scheduler_job_' . $user_id );
									}
									if ( function_exists( 'as_get_scheduled_actions' ) ) {
										$has_action = as_get_scheduled_actions(
											array(
												'group'  => 'ced_ebay_inventory_scheduler_' . $user_id,
												'status' => ActionScheduler_Store::STATUS_PENDING,
											),
											'ARRAY_A'
										);
									}
									if ( ! empty( $has_action ) ) {
										if ( function_exists( 'as_unschedule_all_actions' ) ) {
											$unschedule_actions = as_unschedule_all_actions( null, null, 'ced_ebay_inventory_scheduler_' . $user_id );

											ced_ebay_log_data( 'Call usage limit reached. Unscheduling all inventory syncing actions.', 'ced_ebay_async_update_stock_callback', $log_file );
											continue;
										}
									}
								}

								if ( '231' == $ebay_api_error['ErrorCode'] || '21916750' == $ebay_api_error['ErrorCode'] ) {
									$error_code      = $ebay_api_error['ErrorCode'];
									$ebay_listing_id = get_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
									ced_ebay_log_data( 'Error code ' . $error_code . '. eBay item ' . $ebay_listing_id . ' no longer exists on eBay. Removing from Woo.', 'ced_ebay_async_update_stock_callback', $log_file );
									delete_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
									delete_post_meta( $product_id, '_ced_ebay_relist_item_id_' . $user_id );
								}

								if ( '21919474' == $ebay_api_error['ErrorCode'] || 21919474 == $ebay_api_error['ErrorCode'] ) {
									$status = $this->ced_ebay_update_stock_using_inventory_api( $user_id, $siteID, $token, $product_id );
									if ( 200 == $status || '200' == $status ) {
										$logger->info( 'Initiating Product update through Inventory API call', $context );
									} elseif ( 400 == $status || '400' == $status ) {
										$logger->info( 'Failure error code, Operation not allowed', $context );
									}
								}
							}
						}
					}
				}
				ced_ebay_log_data( $uploadOnEbay, 'ced_ebay_async_update_stock_callback', $log_file );
			}

			ced_ebay_log_data( '----------------', 'ced_ebay_async_update_stock_callback', $log_file );

		} else {
			ced_ebay_log_data( 'Missing Product ID or User ID', 'ced_ebay_async_update_stock_callback', $log_file );
			return;
		}
	}

	public function ced_ebay_update_stock_using_inventory_api( $user_id, $siteID, $token, $product_id ) {
		$logger  = wc_get_logger();
		$context = array( 'source' => 'ced_ebay_update_stock_using_inventory_api' );
		$logger->info( 'Product id - ' . wc_print_r( $product_id, true ), $context );
		require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/cedMarketingRequest.php';
		$inventoryApiRequest = new Ced_Marketing_API_Request( $siteID );
		if ( ! empty( $product_id ) ) {
			$siteDetails = ced_ebay_get_site_details( $siteID );
			if ( empty( $siteDetails ) ) {
				return;
			}
			$marketplace_enum = isset( $siteDetails['inventory_enum'] ) ? $siteDetails['inventory_enum'] : function () {
				return;
			};
			$sku              = get_post_meta( $product_id, '_sku', true );
			$logger->info( 'Woo SKU - ' . wc_print_r( $sku, true ), $context );
			$renderDataOnGlobalSettings = get_option( 'ced_ebay_global_settings', false );
			$manage_stock               = get_post_meta( $product_id, '_manage_stock', true );
			$stock_status               = get_post_meta( $product_id, '_stock_status', true );
			if ( 'yes' != $manage_stock && 'instock' == $stock_status ) {
				$listing_stock_type = isset( $renderDataOnGlobalSettings[ $user_id ][ $siteID ]['ced_ebay_product_stock_type'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $siteID ]['ced_ebay_product_stock_type'] : '';
				$listing_stock      = isset( $renderDataOnGlobalSettings[ $user_id ][ $siteID ]['ced_ebay_listing_stock'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $siteID ]['ced_ebay_listing_stock'] : '';
				if ( ! empty( $listing_stock_type ) && ! empty( $listing_stock ) && 'MaxStock' == $listing_stock_type ) {
					$quantity = $listing_stock;
				} else {
					$quantity = 1;
				}
			} elseif ( 'outofstock' != $stock_status ) {
					$quantity           = get_post_meta( $product_id, '_stock', true );
					$listing_stock_type = isset( $renderDataOnGlobalSettings[ $user_id ][ $siteID ]['ced_ebay_product_stock_type'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $siteID ]['ced_ebay_product_stock_type'] : '';
					$listing_stock      = isset( $renderDataOnGlobalSettings[ $user_id ][ $siteID ]['ced_ebay_listing_stock'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $siteID ]['ced_ebay_listing_stock'] : '';
				if ( ! empty( $listing_stock_type ) && ! empty( $listing_stock ) && 'MaxStock' == $listing_stock_type ) {
					if ( $quantity > $listing_stock ) {
						$quantity = $listing_stock;
					} else {
						$quantity = intval( $quantity );
						if ( $quantity < 1 ) {
							$quantity = '0';
						}
					}
				} else {
					$quantity = intval( $quantity );
					if ( $quantity < 1 ) {
						$quantity = '0';
					}
				}
			} else {
				$quantity = 0;
			}

			if ( ! empty( $sku ) ) {
				$get_offer_id_detail = $inventoryApiRequest->sendHttpRequestForInventoryAPI( 'offer?sku=' . $sku . '&marketplace_id=' . $marketplace_enum, $token, '' );
				$get_offer_id_detail = json_decode( $get_offer_id_detail, true );
				if ( ! empty( $get_offer_id_detail ) && ! empty( $get_offer_id_detail['offers'] ) ) {
					$eBay_offer_id       = isset( $get_offer_id_detail['offers'][0]['offerId'] ) ? $get_offer_id_detail['offers'][0]['offerId'] : '';
					$eBay_offer_quantity = isset( $get_offer_id_detail['offers'][0]['availableQuantity'] ) ? $get_offer_id_detail['offers'][0]['availableQuantity'] : 0;
					$logger->info( 'ebay offer ID - ' . wc_print_r( $eBay_offer_id, true ) . ' | ebay offer quantity ' . wc_print_r( $eBay_offer_quantity, true ), $context );

				}
			}

			if ( ! empty( $eBay_offer_id ) ) {
				$request = array();
				if ( $eBay_offer_quantity > $quantity ) {
					$request['requests'][] = array(
						'offers'                     => array(
							array(
								'availableQuantity' => $eBay_offer_quantity,
								'offerId'           => $eBay_offer_id,
							),
						),
						'shipToLocationAvailability' => array( 'quantity' => $quantity ),
						'sku'                        => $sku,
					);
				} elseif ( $eBay_offer_quantity < $quantity ) {
					$request['requests'][] = array(
						'offers' => array(
							array(
								'availableQuantity' => $quantity,
								'offerId'           => $eBay_offer_id,
							),
						),
						'sku'    => $sku,
					);
				}
				$request_body       = json_encode( $request, true );
				$get_success_detail = $inventoryApiRequest->sendHttpRequestForInventoryAPI( 'bulk_update_price_quantity', $token, 'POST_GET_HEADER_STATUS', $request_body );
				if ( 200 == $get_success_detail || '200' == $get_success_detail ) {
					$logger->info( 'Product stock update successfully - ' . wc_print_r( $product_id, true ), $context );
					return $get_success_detail;
				} else {
					$logger->info( 'Product stock update failure - ' . wc_print_r( $get_success_detail, true ), $context );
					return '400';
				}
			} else {
				$logger->info( 'No offer found for product # - ' . wc_print_r( $product_id, true ), $context );
			}
		}
	}




	public function ced_ebay_add_order_metabox() {
		global $post;
		$order_id          = isset( $post->ID ) ? intval( $post->ID ) : '';
		$purchase_order_id = get_post_meta( $order_id, '_ced_ebay_order_id', true );
		if ( '' != $purchase_order_id ) {
			add_meta_box( 'shipping_fields', __( 'Fulfill Your eBay Order', 'woocommerce' ), array( $this, 'add_html_for_acknowledge' ), 'shop_order', 'normal', 'core' );
		}
	}


	public function ced_ebay_manage_table_columns( $column, $post_id ) {
		if ( ! empty( get_post_meta( $post_id, '_ced_ebay_order_id', true ) ) ) {
			switch ( $column ) {
				case 'order_from':
					$ced_ebay_order_id = get_post_meta( $post_id, '_ced_ebay_order_id', true );
					if ( ! empty( $ced_ebay_order_id ) ) {
						echo '<h3>eBay</h3>';
					} else {
						echo '<h3>N/A</h3>';
					}
					break;
				case 'ebay_user':
					$ced_ebay_user_id = get_post_meta( $post_id, 'ebayBuyerUserId', true );
					if ( ! empty( $ced_ebay_user_id ) ) {
						echo '<h3>' . esc_html( $ced_ebay_user_id ) . '</h3>';
					} else {
						echo '<h3>N/A</h3>';
					}
					break;

			}
		}
	}


	public function ced_ebay_add_table_columns( $columns ) {
		$modified_columns = array();
		foreach ( $columns as $key => $value ) {
			$modified_columns[ $key ] = $value;
			if ( 'order_number' == $key ) {
				$modified_columns['order_from'] = '<span title="Order Source">Order Source</span>';
				$modified_columns['ebay_user']  = '<span title="eBay User">eBay User</span>';
			}
		}
		return $modified_columns;
	}



	public function add_html_for_acknowledge() {
		global $post;
		$order_id = isset( $post->ID ) ? intval( $post->ID ) : '';
		if ( ! is_null( $order_id ) ) {
			$order = wc_get_order( $order_id );

			$user_id = get_post_meta( $order_id, 'ced_ebay_order_user_id', true );
			if ( empty( get_option( 'ced_ebay_user_access_token' ) ) ) {
				?>
				<div class="ced_ebay_loader" class="loading-style-bg" style="display: none;">
					<img src="<?php echo esc_attr( CED_EBAY_URL ) . 'admin/images/loading.gif'; ?>">
				</div>
				<div class="ced_ebay_marketing-view-container" style="padding-bottom:60px;">
					<div class="ced-ebay-v2-title">
						<h1 style="text-align:center;">
							eBay Order Fulfillment
						</h1>
					</div>
					<p>
						In order to fulfill your eBay order, you need to authorize your eBay account by clicking the Login button below.
						<br><b>Before login, please make sure that you allow popups in your browser.</b>
					</p>

					<div class="button-container">
						<div class="button -blue center" id="ced_ebay_marketing_do_login" data-user_id=<?php echo esc_attr( $user_id ); ?>>Login</div>

					</div>

				</div>
				<?php
			} else {
				$template_path = CED_EBAY_DIRPATH . 'admin/partials/order_template.php';
				if ( file_exists( $template_path ) ) {
					require_once $template_path;
				}
			}
		}
	}

	public function ced_ebay_async_order_sync_manager( $data ) {
		$logger  = wc_get_logger();
		$context = array( 'source' => 'ced_ebay_async_order_sync_manager' );
		if ( is_array( $data ) && ! empty( $data ) ) {
			$user_id       = $data['user_id'];
			$ebay_order_id = $data['ebay_order_id'];
			$shop_data     = ced_ebay_get_shop_data( $user_id );
			if ( ! empty( $shop_data ) ) {
				$siteID      = $shop_data['site_id'];
				$token       = $shop_data['access_token'];
				$getLocation = $shop_data['location'];
			}
			$access_token_arr = get_option( 'ced_ebay_user_access_token' );
			if ( ! empty( $access_token_arr ) ) {
				foreach ( $access_token_arr as $key => $value ) {
					$tokenValue = get_transient( 'ced_ebay_user_access_token_' . $key );
					if ( false === $tokenValue || null == $tokenValue || empty( $tokenValue ) ) {
						$user_refresh_token = $value['refresh_token'];
						$this->ced_ebay_save_user_access_token( $key, $user_refresh_token, 'refresh_user_token' );
					}
				}
			}
			$pre_flight_check = ced_ebay_pre_flight_check( $user_id );
			if ( ! $pre_flight_check ) {
				$logger->info( 'Unable to communicate with eBay APIs at the moment!', $context );
				return;
			}
			$get_saved_ebay_orders = array();
			$get_saved_ebay_orders = get_option( 'ced_ebay_fulfillment_api_order_data_' . $user_id, true );
			if ( ! empty( $get_saved_ebay_orders ) ) {
				if ( array_search( $ebay_order_id, array_column( $get_saved_ebay_orders, 'orderId' ) ) !== false ) {
					$data_position = array_search( $ebay_order_id, array_column( $get_saved_ebay_orders, 'orderId' ) );
					ced_ebay_log_data( 'Async importing Order ' . $ebay_order_id, 'ced_ebay_async_order_sync_manager' );
					if ( ! empty( $get_saved_ebay_orders[ $data_position ] ) ) {
						require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayOrders.php';
						$orderInstance  = EbayOrders::get_instance( $siteID, $token );
						$create_order   = array();
						$create_order[] = $get_saved_ebay_orders[ $data_position ];
						$createOrder    = $orderInstance->create_localOrders( $create_order, $siteID, $user_id );
						unset( $get_saved_ebay_orders[ $data_position ] );
						$get_saved_ebay_orders = array_values( $get_saved_ebay_orders );
						update_option( 'ced_ebay_fulfillment_api_order_data_' . $user_id, $get_saved_ebay_orders );
					}
				}
			} else {
				$logger->info( 'No saved data found.', $context );

			}
		}
	}

	public function ced_ebay_order_schedule_manager() {
		$fetchCurrentAction = current_action();
		$this->ced_ebay_onWpAdminInit();
		if ( strpos( $fetchCurrentAction, 'wp_ajax_nopriv_' ) !== false ) {
			$user_id = isset( $_GET['user_id'] ) ? wc_clean( $_GET['user_id'] ) : false;
		} else {
			$user_id = str_replace( 'ced_ebay_order_scheduler_job_', '', $fetchCurrentAction );
		}
		$shop_data = ced_ebay_get_shop_data( $user_id );
		$logger    = wc_get_logger();
		$context   = array( 'source' => 'ced_ebay_order_schedule_manager' );
		if ( ! empty( $shop_data ) ) {
			$siteID      = $shop_data['site_id'];
			$token       = $shop_data['access_token'];
			$getLocation = $shop_data['location'];
		}
		$access_token_arr = get_option( 'ced_ebay_user_access_token' );
		if ( ! empty( $access_token_arr ) ) {
			foreach ( $access_token_arr as $key => $value ) {
				$tokenValue = get_transient( 'ced_ebay_user_access_token_' . $key );
				if ( false === $tokenValue || null == $tokenValue || empty( $tokenValue ) ) {
					$user_refresh_token = $value['refresh_token'];
					$this->ced_ebay_save_user_access_token( $key, $user_refresh_token, 'refresh_user_token' );
				}
			}
		}
		$pre_flight_check = ced_ebay_pre_flight_check( $user_id );
		if ( ! $pre_flight_check ) {
			$logger->info( 'Unable to communicate with eBay APIs at the moment!', $context );
			return;
		}
		$list_offset = ! empty( get_option( 'ced_ebay_order_fetch_offset_' . $user_id . '>' . $siteID ) ) ? get_option( 'ced_ebay_order_fetch_offset_' . $user_id . '>' . $siteID ) : 0;
		if ( file_exists( CED_EBAY_DIRPATH . 'admin/ebay/lib/cedMarketingRequest.php' ) ) {
			require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/cedMarketingRequest.php';
			$currentime         = time();
			$toDate             = $currentime - ( 1 * 60 );
			$fromDate           = $currentime - ( 7 * 24 * 60 * 60 );
			$offset             = '.000Z';
			$toDate             = gmdate( 'Y-m-d', $toDate ) . 'T' . gmdate( 'H:i:s', $toDate ) . $offset;
			$fromDate           = gmdate( 'Y-m-d', $fromDate ) . 'T' . gmdate( 'H:i:s', $fromDate ) . $offset;
			$endpoint           = '?filter=creationdate:%5B' . $fromDate . '..' . $toDate . '%5D,orderfulfillmentstatus:%7BNOT_STARTED%7CIN_PROGRESS%7D&limit=10&offset=' . $list_offset;
			$fulfillmentRequest = new Ced_Marketing_API_Request( $siteID );
			$get_orders_data    = $fulfillmentRequest->sendHttpRequestForFulfillmentAPI( $endpoint, $token, '', '' );

			if ( ! empty( $get_orders_data ) ) {
				$ebay_orders_data = json_decode( $get_orders_data, true );
			}
		}
		if ( isset( $ebay_orders_data['total'] ) && $ebay_orders_data['total'] > 0 ) {
			if ( ! empty( $ebay_orders_data['orders'] ) && is_array( $ebay_orders_data['orders'] ) ) {
				$list_offset = $list_offset + 10;
				update_option( 'ced_ebay_order_fetch_offset_' . $user_id . '>' . $siteID, $list_offset );
				$ebay_orders = array();
				$ebay_orders = $ebay_orders_data['orders'];
				foreach ( $ebay_orders as $key => $ebay_order ) {
					$order_id      = '';
					$ebay_order_id = $ebay_order['orderId'];
					if ( ! empty( $ebay_order_id ) && 'PAID' == $ebay_order['orderPaymentStatus'] ) {
						$args         = array(
							'post_type'   => 'shop_order',
							'post_status' => 'wc-processing',
							'numberposts' => 1,
							'meta_query'  => array(
								'relation' => 'OR',
								array(
									'key'     => '_ced_ebay_order_id',
									'value'   => $ebay_order_id,
									'compare' => '=',
								),
								array(
									'key'     => '_ebay_order_id',
									'value'   => $ebay_order_id,
									'compare' => '=',
								),
							),
						);
						$order        = get_posts( $args );
						$order_id_arr = wp_list_pluck( $order, 'ID' );
						if ( ! empty( $order_id_arr ) ) {
							$order_id = $order_id_arr[0];
						}
						if ( $order_id ) {
							$logger->info( wc_print_r( 'Order ' . $ebay_order_id . ' with WooCommerce order ID ' . $order_id . ' already exists.', true ), $context );
							continue;
						}
						$data = array(
							'order_id' => $ebay_order_id,
							'user_id'  => $user_id,
						);
						$this->schedule_order_task->push_to_queue( $data );

					} else {
						$logger->info( wc_print_r( 'Skipping order ' . $ebay_order_id, true ), $context );
						if ( 'PAID' != $ebay_order['orderPaymentStatus'] ) {
							$logger->info( wc_print_r( 'PAYMENT STATUS - ' . $ebay_order['orderPaymentStatus'], true ), $context );
						}
						continue;
					}
				}
				$this->schedule_order_task->save()->dispatch();
			} else {
				$logger->info( 'End of orders. Resetting offset!', $context );
				update_option( 'ced_ebay_order_fetch_offset_' . $user_id . '>' . $siteID, 0 );
			}
		} else {
			$logger->info( 'End of totals. Resetting offset!', $context );
			update_option( 'ced_ebay_order_fetch_offset_' . $user_id . '>' . $siteID, 0 );
		}
	}

	public function ced_ebay_delete_product_images_when_trashed( $post_id ) {
		$wc_product = wc_get_product( $post_id );
		if ( ! $wc_product ) {
			return;
		}
		$ebay_user_id = get_post_meta( $post_id, 'ced_ebay_listing_user_id', true );
		if ( empty( $ebay_user_id ) ) {
			return;
		}
		$ebay_listing_id = get_post_meta( $post_id, '_ced_ebay_listing_id_' . $ebay_user_id, true );
		if ( empty( $ebay_listing_id ) ) {
			return;
		}

		$thum_id = get_post_thumbnail_id( $wc_product->get_id() );
		if ( empty( $thum_id ) ) {
			return;
		} else {
			wp_delete_attachment( $thum_id, true );
		}

		$gallery_image_ids = $wc_product->get_gallery_image_ids();
		if ( empty( $gallery_image_ids ) ) {
			return;
		}
		foreach ( $gallery_image_ids as $gallery_image_id ) {
			wp_delete_attachment( $gallery_image_id, true );
		}
	}


	public function ced_ebay_show_active_inventory_sync_notice() {
		$user_id      = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : false;
		$site_id      = isset( $_GET['site_id'] ) ? sanitize_text_field( $_GET['site_id'] ) : false;
		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : false;
		if ( ! empty( get_option( 'ced_ebay_stock_sync_progress_' . $user_id . '>' . $site_id, true ) ) ) {
			$stock_sync_progress = get_option( 'ced_ebay_stock_sync_progress_' . $user_id . '>' . $site_id, true );
			if ( ! empty( $stock_sync_progress['synced'] ) && $stock_sync_progress['synced'] != $stock_sync_progress['total_count'] ) {
				?>
				<div class="notice notice-success">
					<p><?php esc_attr_e( 'Stock Sync is running. Synced ' . $stock_sync_progress['synced'] . '/' . $stock_sync_progress['total_count'] . ' Products', 'ebay-integration-for-woocommerce' ); ?></p>
				</div>
				<?php
			}
		}
	}

	public function insert_product_variations( $post_id, $variations ) {
		$logger              = wc_get_logger();
		$context             = array( 'source' => 'insert_product_variations' );
		$variations_pictures = array();
		if ( ! empty( $variations['Pictures'] ) ) {
			$variations_pictures['Pictures'] = $variations['Pictures'];
			unset( $variations['Pictures'] );
		}

		if ( ! isset( $variations['Variation'][0] ) ) {
			$tempVariationList = array();
			$tempVariationList = $variations['Variation'];
			unset( $variations['Variation'] );
			$variations['Variation'][] = $tempVariationList;
		}

		if ( ! isset( $variations_pictures['Pictures'][0] ) && ! empty( $variations_pictures['Pictures'] ) ) {
			$tempVariationPicturesList = array();
			$tempVariationPicturesList = $variations_pictures['Pictures'];
			unset( $variations_pictures['Pictures'] );
			$variations_pictures['Pictures'][] = $tempVariationPicturesList;
		}

		foreach ( $variations['Variation'] as $key => $prod_variation ) {
			if ( ! isset( $prod_variation['VariationSpecifics']['NameValueList'][0] ) ) {
				$tempNameValueList = array();
				$tempNameValueList = $prod_variation['VariationSpecifics']['NameValueList'];
				unset( $variations['Variation'][ $key ]['VariationSpecifics']['NameValueList'] );
				$variations['Variation'][ $key ]['VariationSpecifics']['NameValueList'][] = $tempNameValueList;
			}
		}

		if ( isset( $variations['Variation'][0] ) ) {
			foreach ( $variations['Variation'] as $index => $variation ) {
				$variation_post = array(
					// Setup the post data for the variation

					'post_name'   => 'product-' . $post_id . '-variation-' . $index,
					'post_status' => 'publish',
					'post_parent' => $post_id,
					'post_type'   => 'product_variation',
					'guid'        => home_url() . '/?product_variation=product-' . $post_id . '-variation-' . $index,
				);

				$variation_post_id = wp_insert_post( $variation_post ); // Insert the variation

				// Get product attribute
				$values = array();
				if ( ! empty( $variation['VariationSpecifics']['NameValueList'] ) ) {
					foreach ( $variation['VariationSpecifics']['NameValueList'] as $k2 => $ebay_attr_values ) {
						$attr_name = $ebay_attr_values['Name'];
						$values[]  = $ebay_attr_values['Value'];
						wp_set_object_terms( $variation_post_id, $values, $attr_name );
						$attribute = sanitize_title( $attr_name );
						update_post_meta( $variation_post_id, 'attribute_' . $attribute, $ebay_attr_values['Value'] );
						$thedata = array(
							$attribute => array(
								'name'         => $attr_name,
								'value'        => '',
								'is_visible'   => '1',
								'is_variation' => '1',
								'is_taxonomy'  => '1',
							),
						);
						update_post_meta( $variation_post_id, '_product_attributes', $thedata );
						if ( ! empty( $variations_pictures ) ) {
							foreach ( $variations_pictures['Pictures'] as $key => $wc_var_ebay_pic ) {

								if ( isset( $wc_var_ebay_pic['VariationSpecificPictureSet'] ) ) {
									if ( ! isset( $wc_var_ebay_pic['VariationSpecificPictureSet'][0] ) ) {
										$temp_var_picture_list = array();
										$temp_var_picture_list = $wc_var_ebay_pic['VariationSpecificPictureSet'];
										unset( $wc_var_ebay_pic['VariationSpecificPictureSet'] );
										$wc_var_ebay_pic['VariationSpecificPictureSet'][] = $temp_var_picture_list;
									}
									foreach ( $wc_var_ebay_pic['VariationSpecificPictureSet'] as $key => $wc_var_picture ) {
										if ( $wc_var_picture['VariationSpecificValue'] == $ebay_attr_values['Value'] ) {
											$image_url = is_array( $wc_var_picture['PictureURL'] ) ? $wc_var_picture['PictureURL'][0] : $wc_var_picture['PictureURL'];
											$image_url = remove_query_arg( array( 'set_id' ), $image_url );

											$image_name = basename( $image_url );
											$upload_dir = wp_upload_dir();
											$image_url  = str_replace( 'https', 'http', $image_url );
											$image_data = file_get_contents( $image_url );
											if ( empty( $image_data ) ) {
												$connection = curl_init();
												curl_setopt( $connection, CURLOPT_URL, $image_url );

												curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
												$image_data = curl_exec( $connection );
												curl_close( $connection );
											}

											$unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name );
											$filename         = basename( $unique_file_name );

											if ( wp_mkdir_p( $upload_dir['path'] ) ) {
												$file = $upload_dir['path'] . '/' . $filename;
											} else {
												$file = $upload_dir['basedir'] . '/' . $filename;
											}
											file_put_contents( $file, $image_data );
											$wp_filetype = wp_check_filetype( $filename, null );
											$attachment  = array(
												'post_mime_type' => $wp_filetype['type'],
												'post_title' => sanitize_file_name( $filename ),
												'post_content' => '',
												'post_status' => 'inherit',
											);
											$attach_id   = wp_insert_attachment( $attachment, $file, $post_id );
											require_once ABSPATH . 'wp-admin/includes/image.php';
											$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
											wp_update_attachment_metadata( $attach_id, $attach_data );

											if ( $attach_id ) {
												$wc_var_product = wc_get_product( $variation_post_id );
												$wc_var_product->set_image_id( $attach_id );
												$wc_var_product->save();
											}
										}
									}
								}
							}
						}
					}
				}
				if ( $variation['Quantity'] - $variation['SellingStatus']['QuantitySold'] > 0 ) {
					update_post_meta( $variation_post_id, '_stock_status', 'instock' );
					update_post_meta( $variation_post_id, '_stock', $variation['Quantity'] - $variation['SellingStatus']['QuantitySold'] );
					update_post_meta( $variation_post_id, '_manage_stock', 'yes' );
					update_post_meta( $post_id, '_stock_status', 'instock' );
				} else {
					update_post_meta( $variation_post_id, '_stock_status', 'outofstock' );
				}
				update_post_meta( $variation_post_id, '_sku', $variation['SKU'] );
				update_post_meta( $variation_post_id, '_price', $variation['StartPrice'] );
				update_post_meta( $variation_post_id, '_regular_price', $variation['StartPrice'] );

				$variation_prod = wc_get_product( $variation_post_id );
				$variation_prod->save();
			}
		} else {
			foreach ( $variations['Variation']['VariationSpecifics']['NameValueList'] as $index => $variation_attr ) {
				$attr_name         = $variation_attr['Name'];
				$variation         = $variations['Variation'];
				$variation_post    = array(
					// Setup the post data for the variation

					'post_title'  => $variation['VariationTitle'],
					'post_name'   => 'product-' . $post_id . '-variation-' . $index,
					'post_status' => 'publish',
					'post_parent' => $post_id,
					'post_type'   => 'product_variation',
					'guid'        => home_url() . '/?product_variation=product-' . $post_id . '-variation-' . $index,
				);
				$variation_post_id = wp_insert_post( $variation_post ); // Insert the variation
				$values            = array();
				$attr_values[]     = $variation_attr['Value'];
				$attr_values       = array_unique( $values );

				wp_set_object_terms( $variation_post_id, $values, $attr_name );

				$attribute = sanitize_title( $attr_name );

				update_post_meta( $variation_post_id, 'attribute_' . $attribute, $variation_attr['Value'] );
				$thedata = array(
					$attribute => array(
						'name'         => $variation_attr['Value'],
						'value'        => '',
						'is_visible'   => '1',
						'is_variation' => '1',
						'is_taxonomy'  => '1',
					),
				);

				update_post_meta( $variation_post_id, '_product_attributes', $thedata );
				if ( ! empty( $variations_pictures ) ) {
					foreach ( $variations_pictures['Pictures'] as $key => $wc_var_ebay_pic ) {
						if ( isset( $wc_var_ebay_pic['VariationSpecificPictureSet'] ) ) {
							foreach ( $wc_var_ebay_pic['VariationSpecificPictureSet'] as $key => $wc_var_picture ) {
								if ( $wc_var_picture['VariationSpecificValue'] == $variation_attr['Value'] ) {
									$image_url = $wc_var_picture['PictureURL'][0];
									$image_url = remove_query_arg( array( 'set_id' ), $image_url );

									$image_name = basename( $image_url );
									$upload_dir = wp_upload_dir();
									$image_url  = str_replace( 'https', 'http', $image_url );
									$image_data = file_get_contents( $image_url );
									if ( empty( $image_data ) ) {
										$connection = curl_init();
										curl_setopt( $connection, CURLOPT_URL, $image_url );

										curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
										$image_data = curl_exec( $connection );
										curl_close( $connection );
									}

									$unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name );
									$filename         = basename( $unique_file_name );

									if ( wp_mkdir_p( $upload_dir['path'] ) ) {
										$file = $upload_dir['path'] . '/' . $filename;
									} else {
										$file = $upload_dir['basedir'] . '/' . $filename;
									}
									file_put_contents( $file, $image_data );
									$wp_filetype = wp_check_filetype( $filename, null );
									$attachment  = array(
										'post_mime_type' => $wp_filetype['type'],
										'post_title'     => sanitize_file_name( $filename ),
										'post_content'   => '',
										'post_status'    => 'inherit',
									);
									$attach_id   = wp_insert_attachment( $attachment, $file, $post_id );
									require_once ABSPATH . 'wp-admin/includes/image.php';
									$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
									wp_update_attachment_metadata( $attach_id, $attach_data );

									if ( $attach_id ) {
										$wc_var_product = wc_get_product( $variation_post_id );
										$wc_var_product->set_image_id( $attach_id );
										$wc_var_product->save();
									}
								}
							}
						}
					}
				}
			}
			if ( $variation['Quantity'] - $variation['SellingStatus']['QuantitySold'] > 0 ) {
				update_post_meta( $variation_post_id, '_stock_status', 'instock' );
				update_post_meta( $variation_post_id, '_stock', $variation['Quantity'] - $variation['SellingStatus']['QuantitySold'] );
				update_post_meta( $variation_post_id, '_manage_stock', 'yes' );
				update_post_meta( $post_id, '_stock_status', 'instock' );
			} else {
				update_post_meta( $variation_post_id, '_stock_status', 'outofstock' );
			}
			update_post_meta( $variation_post_id, '_sku', $variation['SKU'] );
			update_post_meta( $variation_post_id, '_price', $variation['StartPrice'] );
			update_post_meta( $variation_post_id, '_regular_price', $variation['StartPrice'] );

			$variation_prod = wc_get_product( $variation_post_id );
			$variation_prod->save();
		}
	}


	public function ced_ebay_recursive_find_category_id( $needle, $haystack ) {

		$queue = new SplQueue();
		$queue->enqueue( array( $haystack, array(), array() ) );
		while ( ! $queue->isEmpty() ) {
			list($value, $path, $cat_ids) = $queue->dequeue();
			if ( isset( $value['CategoryID'] ) && $value['CategoryID'] == $needle ) {

				$path = array_values( array_unique( $path ) );

				$cat_ids = array_values( array_unique( $cat_ids ) );

				$combine_value = array_combine( $cat_ids, $path );

				return $combine_value;
			}
			if ( is_array( $value ) ) {
				foreach ( $value as $key => $subValue ) {
					if ( ! empty( $value['ChildCategory'] ) && 'ChildCategory' == $key ) {
						$queue->enqueue( array( $subValue, array_merge( $path, array( $value['Name'] ) ), array_merge( $cat_ids, array( $value['CategoryID'] ) ) ) );

					}
					if ( isset( $subValue['ChildCategory'] ) || isset( $subValue['Name'] ) ) {
						$queue->enqueue( array( $subValue, array_merge( $path, array( $subValue['Name'] ) ), array_merge( $cat_ids, array( $subValue['CategoryID'] ) ) ) );

					}
				}
			}
		}

		return array();
	}




	public function ced_ebay_bulk_import_to_store( $itemId = array(), $user_id = '', $isPhpInvoked = '' ) {
		$isPhpInvoked = empty( $isPhpInvoked ) ? false : $isPhpInvoked;
		if ( false == $isPhpInvoked ) {
			$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
			if ( ! $check_ajax ) {
				wp_die();
			}
		}

		$ced_ebay_manager = $this->ced_ebay_manager;
		$user_id          = isset( $_POST['userid'] ) ? sanitize_text_field( $_POST['userid'] ) : $user_id;
		$shop_data        = ced_ebay_get_shop_data( $user_id );
		if ( ! empty( $shop_data ) ) {
			$siteID      = $shop_data['site_id'];
			$token       = $shop_data['access_token'];
			$getLocation = $shop_data['location'];
		}

		$pre_flight_check = ced_ebay_pre_flight_check( $user_id );
		if ( ! $pre_flight_check ) {
			$response = array(
				'status' => 'Bulk Import Error',
				'title'  => 'We are not able to connect to eBay at the moment. Please try again later. If the issue persists, please contact support.',
			);
			wp_send_json( $response );
			update_option( 'ced_ebay_product_importer_product_error_' . $user_id . '>' . $siteID, 'We are not able to connect to eBay at the moment. Please try again later. If the issue persists, please contact support.' );
		}
		require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';

		$sanitized_array = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
		$operation       = isset( $sanitized_array['operation_to_be_performed'] ) ? ( $sanitized_array['operation_to_be_performed'] ) : '';
		$itemId          = ! empty( $itemId ) ? $itemId : $sanitized_array['products_id'];
		$count_imports   = 0;
		if ( is_array( $itemId ) && ! empty( $itemId ) ) {
			foreach ( $itemId as $key => $value ) {
				if ( '' == $value && null == $value ) {
					continue;
				}
				$store_products = get_posts(
					array(
						'numberposts'  => 1,
						'post_type'    => 'product',
						'meta_key'     => '_ced_ebay_importer_listing_id_' . $user_id . '>' . $siteID,
						'meta_value'   => $value,
						'meta_compare' => '=',
					)
				);
				$localItemID    = wp_list_pluck( $store_products, 'ID' );
				if ( ! empty( $localItemID ) && is_array( $localItemID ) ) {
					$existing_product_id = $localItemID[0];
					if ( ! empty( $existing_product_id ) ) {
						$response = array(
							'status' => 'Bulk Import Error',
							'title'  => 'The eBay listing you are trying to import already exists in WooCommerce. If you think this is an error, please contact support.',
						);
						wp_send_json( $response );
						update_option( 'ced_ebay_product_importer_product_error_' . $user_id . '>' . $siteID, 'The eBay listing you are trying to import already exists in WooCommerce. If you think this is an error, please contact support.' );
					}
				}
				$mainXml            = '
				<?xml version="1.0" encoding="utf-8"?>
				<GetItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
				<RequesterCredentials>
				<eBayAuthToken>' . $token . '</eBayAuthToken>
				</RequesterCredentials>
				<DetailLevel>ReturnAll</DetailLevel>
				<IncludeItemSpecifics>true</IncludeItemSpecifics>
				<ItemID>' . $value . '</ItemID>
				</GetItemRequest>';
				$ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
				$itemDetails        = $ebayUploadInstance->get_item_details( $mainXml );
				if ( 'Success' == $itemDetails['Ack'] || 'Warning' == $itemDetails['Ack'] ) {
					$renderDataOnGlobalSettings = ! empty( get_option( 'ced_ebay_global_settings', false ) ) ? get_option( 'ced_ebay_global_settings', false ) : false;
					$import_products_ebay_site  = ! empty( $renderDataOnGlobalSettings[ $user_id ]['ced_ebay_item_import_country'] ) ? $renderDataOnGlobalSettings[ $user_id ]['ced_ebay_item_import_country'] : false;
					if ( ! empty( $import_products_ebay_site ) ) {

						if ( $import_products_ebay_site !== $itemDetails['Item']['Site'] ) {
							if ( $isPhpInvoked ) {
								continue;
							} else {
								$response = array(
									'status' => 'Bulk Import Error',
									'title'  => 'The product you are trying to import belongs to an eBay site other than the eBay listing site you selected in the General Settings section.',
								);
								wp_send_json( $response );
								update_option( 'ced_ebay_product_importer_product_error_' . $user_id . '>' . $siteID, 'The product you are trying to import belongs to an eBay site other than the eBay listing site you selected in the General Settings section.' );
							}
						}
					}
					if ( 'Chinese' == $itemDetails['Item']['ListingType'] ) {
						echo 'Skipping Auction Product';
						continue;
					}
					$itemid = $itemDetails['Item']['ItemID'];

					$product_id = wp_insert_post(
						array(
							'post_title'   => $itemDetails['Item']['Title'],
							'post_status'  => 'publish',
							'post_type'    => 'product',
							'post_content' => $itemDetails['Item']['Description'],
						)
					);

					if ( isset( $itemDetails['Item']['Title'] ) ) {
						update_option( 'ced_ebay_last_imported_title_' . $user_id . '>' . $siteID, $itemDetails['Item']['Title'] );

					}
					$progress_key = 'ced_ebay_product_importer_product_progress_' . $user_id . '>' . $siteID;
					$next_product = ! empty( get_option( $progress_key ) ) ? get_option( $progress_key ) : 1;
					++$next_product;
					update_option( $progress_key, $next_product );

					update_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, $itemid );
					update_post_meta( $product_id, '_ced_ebay_importer_listing_id_' . $user_id . '>' . $siteID, $itemid );
					update_post_meta( $product_id, 'ced_ebay_listing_user_id', $user_id );

					$get_prod = wc_get_product( $product_id );
					if ( isset( $itemDetails['Item']['ShippingPackageDetails'] ) ) {

						$listing_weight_major = isset( $itemDetails['Item']['ShippingPackageDetails']['WeightMajor'] ) ? $itemDetails['Item']['ShippingPackageDetails']['WeightMajor'] : 0;
						$listing_weight_minor = isset( $itemDetails['Item']['ShippingPackageDetails']['WeightMinor'] ) ? $itemDetails['Item']['ShippingPackageDetails']['WeightMinor'] : 0;
						$listing_weight       = $listing_weight_major . '.' . $listing_weight_minor;
						$listingEbaySite      = isset( $itemDetails['Item']['Site'] ) ? $itemDetails['Item']['Site'] : '';
						if ( ! empty( $listingEbaySite ) ) {
							$wcWeightUnit = get_option( 'woocommerce_weight_unit' );
							if ( 'US' == $listingEbaySite || 'eBayMotors' == $listingEbaySite ) {
								if ( '' != $wcWeightUnit ) {
									if ( 'oz' == $wcWeightUnit ) {
										$weight_in_ounces = ceil( $listing_weight * 16 );
										$listing_weight   = $weight_in_ounces;
									}
								}
							} elseif ( '' != $wcWeightUnit ) {
								if ( 'g' == $wcWeightUnit ) {
									$weight_in_grams = $listing_weight * 1000;
									$listing_weight  = $weight_in_grams;
								}
							}
						}
						$get_prod->set_weight( $listing_weight );

						$get_prod->save();

						$listing_length = isset( $itemDetails['Item']['ShippingPackageDetails']['PackageLength'] ) ? $itemDetails['Item']['ShippingPackageDetails']['PackageLength'] : 0;
						$listing_width  = isset( $itemDetails['Item']['ShippingPackageDetails']['PackageWidth'] ) ? $itemDetails['Item']['ShippingPackageDetails']['PackageWidth'] : 0;
						$listing_height = isset( $itemDetails['Item']['ShippingPackageDetails']['PackageDepth'] ) ? $itemDetails['Item']['ShippingPackageDetails']['PackageDepth'] : 0;
						$get_prod->set_props(
							array(
								'width'  => $listing_width,
								'height' => $listing_height,
								'length' => $listing_length,
							)
						);

						$get_prod->save();
					}

					$category             = explode( ':', $itemDetails['Item']['PrimaryCategory']['CategoryName'] );
					$ebay_category_id     = ! empty( $itemDetails['Item']['PrimaryCategory']['CategoryID'] ) ? $itemDetails['Item']['PrimaryCategory']['CategoryID'] : false;
					$woo_store_categories = get_terms( 'product_cat' );
					foreach ( $woo_store_categories as $key => $woo_category ) {
						$mapped_to_ebay_category = get_term_meta( $woo_category->term_id, 'ced_ebay_mapped_category_' . $user_id, true );
						if ( ! empty( $mapped_to_ebay_category ) ) {
							if ( $mapped_to_ebay_category == $ebay_category_id ) {
								wp_set_object_terms( $product_id, $woo_category->term_id, 'product_cat' );
							}
						}
					}
					if ( false != $renderDataOnGlobalSettings ) {
						$import_categories = $renderDataOnGlobalSettings[ $user_id ]['ced_ebay_import_ebay_categories'];
						if ( isset( $renderDataOnGlobalSettings[ $user_id ]['ced_ebay_import_categories_type'] ) ) {
							$import_categories_type = ! empty( $renderDataOnGlobalSettings[ $user_id ]['ced_ebay_import_categories_type'] ) ? $renderDataOnGlobalSettings[ $user_id ]['ced_ebay_import_categories_type'] : 'ebay_store';
						} else {
							$import_categories_type = 'ebay_site';
						}
						if ( ! empty( $import_categories ) && 'Enabled' == $import_categories ) {
							if ( 'ebay_site' == $import_categories_type ) {
								if ( ! empty( $category ) ) {
									$parent_id = '';
									foreach ( $category as $key => $value ) {
										$term = wp_insert_term(
											$value,
											'product_cat',
											array(
												'description' => $value,
												'parent' => $parent_id,
											)
										);
										if ( isset( $term->error_data['term_exists'] ) ) {

											$term_id = $term->error_data['term_exists'];
										} elseif ( isset( $term['term_id'] ) ) {

											$term_id = $term['term_id'];
										}

										$parent_id = ! empty( $term_id ) ? $term_id : '';
									}
									wp_set_object_terms( $product_id, $term_id, 'product_cat' );
								}
							} elseif ( 'ebay_store' == $import_categories_type ) {
								if ( ! empty( $itemDetails['Item']['Storefront']['StoreCategoryID'] ) ) {
									$category = $itemDetails['Item']['Storefront']['StoreCategoryID'];
									if ( ! empty( $category ) ) {
										if ( file_exists( CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayAuthorization.php' ) ) {
											require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayAuthorization.php';
											$cedAuthorization        = new Ebayauthorization();
											$cedAuhorizationInstance = $cedAuthorization->get_instance();
											$shopDetails             = $cedAuhorizationInstance->getStoreData( $siteID, $user_id );
											if ( ! empty( $shopDetails ) && 'Success' == $shopDetails['Ack'] ) {
												$store_categories    = $shopDetails['Store']['CustomCategories']['CustomCategory'];
												$store_cateogry_name = $this->ced_ebay_recursive_find_category_id( $category, $store_categories );
												if ( ! empty( $store_cateogry_name ) && is_array( $store_cateogry_name ) ) {
													foreach ( $store_cateogry_name as $key => $prodMainCategory ) {
														$get_term_by_slug = get_term_by( 'slug', $key, 'product_cat' );

														if ( empty( $get_term_by_slug ) ) {

															$mainTerm    = wp_insert_term(
																$prodMainCategory,
																'product_cat',
																array(
																	'',
																	'slug' => $key,
																	'parent' => $parent,
																)
															);
															$newParentID = $mainTerm['term_id'];
															$tem_slug    = $mainTerm['slug'];
														} else {

															$get_term    = get_term_by( 'slug', $key, 'product_cat' );
															$parentCatId = $get_term->parent;

															$mainTerm = array(
																'term_id' => $get_term->term_id,
																'term_taxonomy_id' => $get_term->term_taxonomy_id,
																'slug' => $get_term->slug,

															);

															$newParentID = $mainTerm['term_id'];
															$term_slug   = $mainTerm['slug'];

															if ( $prodMainCategory != $get_term->name ) {
																wp_update_term( $mainTerm['term_id'], 'product_cat', array( 'name' => $prodMainCategory ) );
															}
														}
														$parent = $newParentID;
													}
													wp_set_object_terms( $product_id, $newParentID, 'product_cat' );
												} else {
													wc_get_logger()->info( 'Failed to recursively find store category name!', array( 'source' => 'ced_ebay_bulk_import_to_store' ) );
												}
											} else {
												wc_get_logger()->info( 'Failed to fetch store categories from the API!', array( 'source' => 'ced_ebay_bulk_import_to_store' ) );
											}
										}
									}
								}
							}
						}
					}
				}
				$get_prod->save();
				$image_url = ! empty( $itemDetails['Item']['PictureDetails']['GalleryURL'] ) ? $itemDetails['Item']['PictureDetails']['GalleryURL'] : false;
				if ( ! empty( $image_url ) ) {
					$upload = wc_rest_upload_image_from_url( esc_url_raw( $image_url ) );
					if ( ! is_wp_error( $upload ) ) {
						$attachment_id = wc_rest_set_uploaded_image_as_attachment( $upload, $product_id );
						if ( wp_attachment_is_image( $attachment_id ) ) {
							$get_prod->set_image_id( $attachment_id );
							$get_prod->save();
							// Set the image name
							wp_update_post(
								array(
									'ID'         => $attachment_id,
									'post_title' => $itemDetails['Item']['Title'] . ' Main Image',
								)
							);
						}
					}
				} else {
					if ( is_array( $itemDetails['Item']['PictureDetails']['PictureURL'] ) ) {
						$image_url = $itemDetails['Item']['PictureDetails']['PictureURL'][0];
					} else {
						$image_url = $itemDetails['Item']['PictureDetails']['PictureURL'];
					}

					$upload = wc_rest_upload_image_from_url( esc_url_raw( $image_url ) );
					if ( ! is_wp_error( $upload ) ) {
						$attachment_id = wc_rest_set_uploaded_image_as_attachment( $upload, $product_id );
						if ( wp_attachment_is_image( $attachment_id ) ) {
							$get_prod->set_image_id( $attachment_id );
							$get_prod->save();
							wp_update_post(
								array(
									'ID'         => $attachment_id,
									'post_title' => $itemDetails['Item']['Title'] . ' Main Image',
								)
							);
						}
					}
				}

				if ( is_array( $itemDetails['Item']['PictureDetails']['PictureURL'] ) ) {
					if ( count( $itemDetails['Item']['PictureDetails']['PictureURL'] ) != 1 ) {
						if ( isset( $itemDetails['Item']['PictureDetails']['PictureURL'] ) ) {
							unset( $itemDetails['Item']['PictureDetails']['PictureURL'][0] );

							$itemDetails['Item']['PictureDetails']['PictureURL'] = array_values( $itemDetails['Item']['PictureDetails']['PictureURL'] );
							if ( isset( $itemDetails['Item']['PictureDetails']['PictureURL'][0] ) ) {
								$attach_ids = array();

								$product = wc_get_product( $product_id );
								foreach ( $itemDetails['Item']['PictureDetails']['PictureURL'] as $key11 => $value11 ) {
									$image_url = $value11; // Define the image URL here

									if ( ! empty( $image_url ) ) {

										$upload = wc_rest_upload_image_from_url( esc_url_raw( $image_url ) );
										if ( ! is_wp_error( $upload ) ) {
											$attachment_id = wc_rest_set_uploaded_image_as_attachment( $upload, $product_id );
											if ( wp_attachment_is_image( $attachment_id ) ) {
												$attach_ids[] = $attachment_id;

												wp_update_post(
													array(
														'ID' => $attachment_id,
														'post_title' => $itemDetails['Item']['Title'] . ' Gallery Image ' . $key11,
													)
												);

											}
										}
									}
								}

								$product->set_gallery_image_ids( $attach_ids );
								$product->save();

							} else {

								$attach_ids   = array();
								$attach_ids[] = $itemDetails['Item']['PictureDetails']['PictureURL'];
								update_post_meta( $product_id, '_product_image_gallery', implode( ',', $attach_ids ) );
							}
						}
					}
				}

				if ( isset( $itemDetails['Item']['Variations'] ) ) {
					wp_set_object_terms( $product_id, 'variable', 'product_type' );
					update_post_meta( $product_id, '_sku', $itemDetails['Item']['SKU'] );
					$data = array();
					if ( ! isset( $itemDetails['Item']['Variations']['VariationSpecificsSet']['NameValueList'][0] ) ) {
						$tempNameValueList = array();
						$tempNameValueList = $itemDetails['Item']['Variations']['VariationSpecificsSet']['NameValueList'];
						unset( $itemDetails['Item']['Variations']['VariationSpecificsSet']['NameValueList'] );
						$itemDetails['Item']['Variations']['VariationSpecificsSet']['NameValueList'][] = $tempNameValueList;
					}
					if ( isset( $itemDetails['Item']['Variations']['VariationSpecificsSet'] ) ) {
						foreach ( $itemDetails['Item']['Variations']['VariationSpecificsSet']['NameValueList'] as $key => $ebay_attr ) {
							$data['attribute_names'][] = $ebay_attr['Name'];
						}
						$data['attribute_position'][] = 0;
						$visibility_values            = array();
						$use_for_variation            = array();
						$values                       = array();
						foreach ( $itemDetails['Item']['Variations']['VariationSpecificsSet']['NameValueList'] as $key => $value ) {
							if ( is_array( $value['Value'] ) ) {
								$values[] = implode( '|', $value['Value'] );
							} else {
								$values[] = $value['Value'];
							}
							$visibility_values[] = 1;
							$use_for_variation[] = 1;

						}
						$values                       = array_unique( $values );
						$data['attribute_values'][]   = $values;
						$data['attribute_visibility'] = $visibility_values;
						$data['attribute_variation']  = $use_for_variation;
					} else {
						$data['attribute_names'][]      = $itemDetails['Item']['Variations']['Variation']['VariationSpecifics']['NameValueList']['Name'];
						$data['attribute_position'][]   = 0;
						$values                         = array();
						$values[]                       = $itemDetails['Item']['Variations']['Variation']['VariationSpecifics']['NameValueList']['Value'];
						$values                         = array_unique( $values );
						$data['attribute_values'][]     = implode( '|', $values );
						$data['attribute_visibility'][] = 1;
						$data['attribute_variation'][]  = 1;
					}
					if ( isset( $data['attribute_names'], $data['attribute_values'] ) ) {
						$attribute_names         = $data['attribute_names'];
						$attribute_values        = $data['attribute_values'][0];
						$attribute_visibility    = isset( $data['attribute_visibility'] ) ? $data['attribute_visibility'] : array();
						$attribute_variation     = isset( $data['attribute_variation'] ) ? $data['attribute_variation'] : array();
						$attribute_position      = $data['attribute_position'];
						$attribute_names_max_key = max( array_keys( $attribute_names ) );
						for ( $i = 0; $i <= $attribute_names_max_key; $i++ ) {
							if ( empty( $attribute_names[ $i ] ) || ! isset( $attribute_values[ $i ] ) ) {
								continue;
							}
							$attribute_id   = 0;
							$attribute_name = wc_clean( $attribute_names[ $i ] );
							if ( 'pa_' === substr( $attribute_name, 0, 3 ) ) {
								$attribute_id = wc_attribute_taxonomy_id_by_name( $attribute_name );
							}
							$options = isset( $attribute_values[ $i ] ) ? $attribute_values[ $i ] : '';
							if ( is_array( $options ) ) {
								$options = wp_parse_id_list( $options );
							} else {
								$options = wc_get_text_attributes( $options );
							}

							if ( empty( $options ) ) {
								continue;
							}
							$attribute = new WC_Product_Attribute();
							$attribute->set_id( $attribute_id );
							$attribute->set_name( $attribute_name );
							$attribute->set_options( $options );
							$attribute->set_position( $attribute_position[0] );
							$attribute->set_visible( isset( $attribute_visibility[ $i ] ) );
							$attribute->set_variation( isset( $attribute_variation[ $i ] ) );
							$attributes[] = $attribute;
						}
					}
					$product_type = 'variable';
					$classname    = WC_Product_Factory::get_product_classname( $product_id, $product_type );
					$product      = new $classname( $product_id );
					$product->set_attributes( $attributes );
					$product->save();
					$this->insert_product_variations( $product_id, $itemDetails['Item']['Variations'] );

					update_post_meta( $product_id, '_umb_ebay_listing_type', $itemDetails['Item']['ListingType'] );
					update_post_meta( $product_id, '_umb_ebay_listing_duration', $itemDetails['Item']['ListingDuration'] );
					update_post_meta( $product_id, '_umb_ebay_dispatch_time', $itemDetails['Item']['DispatchTimeMax'] );
					update_post_meta( $product_id, '_umb_ebay_category', $itemDetails['Item']['PrimaryCategory']['CategoryID'] );

					update_post_meta( $product_id, 'ced_umb_ebay_ebay_status', 'PUBLISHED' );
					update_post_meta( $product_id, 'ced_ebay_product_data', $itemDetails['Item'] );
					if ( isset( $itemDetails['Item']['ProductListingDetails']['UPC'] ) ) {
						update_post_meta( $product_id, 'ced_ebay_product_upc', $itemDetails['Item']['ProductListingDetails']['UPC'] );
					}

					if ( isset( $itemDetails['Item']['ProductListingDetails']['BrandMPN']['MPN'] ) ) {
						update_post_meta( $product_id, 'ced_ebay_product_mpn', $itemDetails['Item']['ProductListingDetails']['BrandMPN']['MPN'] );
					}

					if ( isset( $itemDetails['Item']['ProductListingDetails']['BrandMPN']['Brand'] ) ) {
						update_post_meta( $product_id, 'ced_ebay_product_brand', $itemDetails['Item']['ProductListingDetails']['BrandMPN']['Brand'] );
					}

					if ( isset( $itemDetails['Item']['ItemSpecifics']['NameValueList'] ) && ! isset( $itemDetails['Item']['ItemSpecifics']['NameValueList'][0] ) ) {
						$tempNameValueList = array();
						$tempNameValueList = $itemDetails['Item']['ItemSpecifics']['NameValueList'];
						unset( $itemDetails['Item']['ItemSpecifics']['NameValueList'] );
						$itemDetails['Item']['ItemSpecifics']['NameValueList'][] = $tempNameValueList;
					}
					if ( ! empty( $itemDetails['Item']['ItemSpecifics']['NameValueList'] ) ) {
						foreach ( $itemDetails['Item']['ItemSpecifics']['NameValueList'] as $key => $attributes ) {

							delete_transient( 'wc_attribute_taxonomies' );
							WC_Cache_Helper::incr_cache_prefix( 'woocommerce-attributes' );
							$attributeName = $attributes['Name'];

							if ( 'Year' == $attributes['Name'] ) {
								$attributeSlug = $attributes['Name'] . '-attr';
							} elseif ( 'Type' == $attributes['Name'] ) {
								$attributeSlug = $attributes['Name'] . '-attr';
							} else {
								$attributeSlug = $attributes['Name'];
							}
							if ( 28 < strlen( $attributeSlug ) ) {
								$attributeSlug = substr( $attributeSlug, 0, 10 );
							}
							$attributeLabels = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name' );
							$attributeWCName = array_search( $attributeSlug, $attributeLabels, true );

							if ( ! $attributeWCName ) {
								$attributeWCName = wc_sanitize_taxonomy_name( $attributeSlug );
							}

							$attributeId = wc_attribute_taxonomy_id_by_name( $attributeWCName );
							if ( ! $attributeId ) {
								$taxonomyName = wc_attribute_taxonomy_name( $attributeWCName );
								unregister_taxonomy( $taxonomyName );
								$attributeId = wc_create_attribute(
									array(
										'name'         => $attributeName,
										'slug'         => $attributeSlug,
										'type'         => 'select',
										'order_by'     => 'menu_order',
										'has_archives' => 0,
									)
								);

								register_taxonomy(
									$taxonomyName,
									/**
										 * Taxonomy objects
										 *
										 * @since 1.0.0
										 */
									apply_filters(
										'woocommerce_taxonomy_objects_' . $taxonomyName,
										array(
											'product',
										)
									),
									/**
										 * Taxonomy args
										 *
										 * @since 1.0.0
										 */
									apply_filters(
										'woocommerce_taxonomy_args_' . $taxonomyName,
										array(
											'labels'       => array(
												'name' => $attributeSlug,
											),
											'hierarchical' => false,
											'show_ui'      => false,
											'query_var'    => true,
											'rewrite'      => false,
										)
									)
								);
							}

							$taxonomy = $attributes['Name'];
							if ( strlen( $taxonomy ) > 28 ) {
								$taxonomy = substr( $taxonomy, 0, 10 );
							}
							$taxonomyName = wc_attribute_taxonomy_name( $taxonomy );
							if ( 'Year' == $taxonomy ) {
								$taxonomyName = $taxonomyName . '-attr';
							}
							if ( 'Type' == $taxonomy ) {
								$taxonomyName = $taxonomyName . '-attr';
							}
							if ( strlen( $taxonomyName ) > 28 ) {
								$taxonomyName = substr( $taxonomyName, 0, 10 );
							}
							if ( ! empty( $attributes['Value'] ) && is_array( $attributes['Value'] ) ) {
								$term_id = array();
								foreach ( $attributes['Value'] as $key => $attr_terms ) {
									$termArrayName = $attr_terms;
									$termArraySlug = $attr_terms;
									$term          = get_term_by( 'slug', $termArrayName, $taxonomyName );
									if ( ! $term ) {
										$term = wp_insert_term(
											$termArrayName,
											$taxonomyName,
											array(
												'slug' => $termArraySlug,
											)
										);
										if ( isset( $term->error_data['term_exists'] ) ) {
											$term_id = $term->error_data['term_exists'];
										} elseif ( is_array( $term ) ) {
												$term_id[] = $term['term_id'];
										} else {
											$term_id[] = $term->term_id;
										}
									} else {
										$term_id[] = $term->term_id;
									}
								}
							}
							if ( ! empty( $attributes['Value'] ) && ! is_array( $attributes['Value'] ) ) {
								$termName = $attributes['Value'];
								$termSlug = $attributes['Value'];
								$term     = get_term_by( 'slug', $termSlug, $taxonomyName );
								if ( ! $term ) {
									$term = wp_insert_term(
										$termName,
										$taxonomyName,
										array(
											'slug' => $termSlug,
										)
									);
									if ( isset( $term->error_data['term_exists'] ) ) {
										$term_id = $term->error_data['term_exists'];
									} elseif ( is_array( $term ) ) {
											$term_id = $term['term_id'];
									} else {
										$term_id = $term->term_id;
									}
								} else {
									$term_id = $term->term_id;
								}
							}
							$product_attributes = (array) $product->get_attributes();
							if ( array_key_exists( $taxonomy, $product_attributes ) ) {
								foreach ( $product_attributes as $key1 => $product_attribute ) {
									if ( $key1 == $taxonomy ) {
										if ( is_array( $term_id ) ) {
											$product_attribute->set_options( $term_id );
										} else {
											$product_attribute->set_options( array( $term_id ) );
										}
										$product_attributes[ $key1 ] = $product_attribute;
										break;
									}
								}
								$product->set_attributes( $product_attributes );
							} else {
								$prod_attribute = new WC_Product_Attribute();

								$prod_attribute->set_id( count( $product_attributes ) + 1 );
								$prod_attribute->set_name( $taxonomyName );
								if ( is_array( $term_id ) ) {
									$prod_attribute->set_options( $term_id );
								} else {
									$prod_attribute->set_options( array( $term_id ) );
								}
								$prod_attribute->set_position( count( $product_attributes ) + 1 );
								$prod_attribute->set_visible( true );
								$prod_attribute->set_variation( false );
								$product_attributes[] = $prod_attribute;

								$product->set_attributes( $product_attributes );
							}
						}

						$product->save();

					}

					if ( isset( $itemDetails['Item']['ConditionDisplayName'] ) && ! empty( $itemDetails['Item']['ConditionDisplayName'] ) ) {
						WC_Cache_Helper::incr_cache_prefix( 'woocommerce-attributes' );
						$attributeName = 'Condition';

						$attributeSlug = 'Condition';

						$attributeLabels = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name' );
						$attributeWCName = array_search( $attributeSlug, $attributeLabels, true );

						if ( ! $attributeWCName ) {
							$attributeWCName = wc_sanitize_taxonomy_name( $attributeSlug );
						}

						$attributeId = wc_attribute_taxonomy_id_by_name( $attributeWCName );
						if ( ! $attributeId ) {
							$taxonomyName = wc_attribute_taxonomy_name( $attributeWCName );
							unregister_taxonomy( $taxonomyName );
							$attributeId = wc_create_attribute(
								array(
									'name'         => $attributeName,
									'slug'         => $attributeSlug,
									'type'         => 'select',
									'order_by'     => 'menu_order',
									'has_archives' => 0,
								)
							);
							/**
							 * Woocommerce_taxonomy_objects.
							 *
							 * @since 1.0.0
							 */
							register_taxonomy(
								$taxonomyName,
								/**
										 * Taxonomy objects
										 *
										 * @since 1.0.0
										 */
								apply_filters(
									'woocommerce_taxonomy_objects_' . $taxonomyName,
									array(
										'product',
									)
								),
								/**
										 * Taxonomy args
										 *
										 * @since 1.0.0
										 */
								apply_filters(
									'woocommerce_taxonomy_args_' . $taxonomyName,
									array(
										'labels'       => array(
											'name' => $attributeSlug,
										),
										'hierarchical' => false,
										'show_ui'      => false,
										'query_var'    => true,
										'rewrite'      => false,
									)
								)
							);
						}

						$taxonomy = 'Condition';

						$taxonomyName = wc_attribute_taxonomy_name( $taxonomy );

						if ( strlen( $taxonomyName ) > 28 ) {
							$taxonomyName = substr( $taxonomyName, 0, 10 );
						}
						$termName = $itemDetails['Item']['ConditionDisplayName'];
						$termSlug = $itemDetails['Item']['ConditionDisplayName'];
						$term     = get_term_by( 'slug', $termSlug, $taxonomyName );
						if ( ! $term ) {
							$term = wp_insert_term(
								$termName,
								$taxonomyName,
								array(
									'slug' => $termSlug,
								)
							);
							if ( isset( $term->error_data['term_exists'] ) ) {
								$term_id = $term->error_data['term_exists'];
							} elseif ( is_array( $term ) ) {
									$term_id = $term['term_id'];
							} else {
								$term_id = $term->term_id;
							}
						} else {
							$term_id = $term->term_id;
						}

						wp_set_object_terms( $term_id, $itemDetails['Item']['ConditionDisplayName'], 'pa_condition' );
						$get_attribute      = get_term_by( 'name', $itemDetails['Item']['ConditionDisplayName'], 'pa_condition' );
						$product_attributes = $product->get_attributes();

						// Set the new attribute value

						$prod_attribute = new WC_Product_Attribute();
						$prod_attribute->set_id( count( $product_attributes ) + 1 );
						$prod_attribute->set_name( $taxonomyName );
						if ( is_array( $term_id ) ) {
							$prod_attribute->set_options( $term_id );
						} else {
							$prod_attribute->set_options( array( $term_id ) );
						}
						$prod_attribute->set_position( count( $product_attributes ) + 1 );
						$prod_attribute->set_visible( true );
						$prod_attribute->set_variation( false );
						$product_attributes[] = $prod_attribute;

						$product->set_attributes( $product_attributes );

						$product->save();
					}
				} else {
					wp_set_object_terms( $product_id, 'simple', 'product_type' );
					$simpleProduct = wc_get_product( $product_id );
					update_post_meta( $product_id, '_visibility', 'visible' );
					if ( ( $itemDetails['Item']['Quantity'] - $itemDetails['Item']['SellingStatus']['QuantitySold'] ) > 0 ) {
						update_post_meta( $product_id, '_stock_status', 'instock' );
						update_post_meta( $product_id, '_stock', $itemDetails['Item']['Quantity'] - $itemDetails['Item']['SellingStatus']['QuantitySold'] );
						update_post_meta( $product_id, '_umb_stock', $itemDetails['Item']['Quantity'] - $itemDetails['Item']['SellingStatus']['QuantitySold'] );
					} else {
						update_post_meta( $product_id, '_stock_status', 'outofstock' );
						update_post_meta( $product_id, '_umb_stock', 0 );
					}

					update_post_meta( $product_id, '_sku', $itemDetails['Item']['SKU'] );
					update_post_meta( $product_id, '_manage_stock', 'yes' );
					update_post_meta( $product_id, 'total_sales', '0' );
					update_post_meta( $product_id, '_downloadable', 'no' );
					update_post_meta( $product_id, '_sale_price', '' );
					update_post_meta( $product_id, '_purchase_note', '' );
					update_post_meta( $product_id, '_featured', 'no' );
					update_post_meta( $product_id, '_regular_price', $itemDetails['Item']['StartPrice'] );
					update_post_meta( $product_id, '_price', $itemDetails['Item']['StartPrice'] );
					$prod_update_price = wc_get_product( $product_id );
					$prod_update_price->save();
					update_post_meta( $product_id, '_umb_ebay_price', $itemDetails['Item']['StartPrice'] );
					update_post_meta( $product_id, '_umb_ebay_listing_type', $itemDetails['Item']['ListingType'] );
					update_post_meta( $product_id, '_umb_ebay_listing_duration', $itemDetails['Item']['ListingDuration'] );
					update_post_meta( $product_id, '_umb_ebay_dispatch_time', $itemDetails['Item']['DispatchTimeMax'] );
					update_post_meta( $product_id, '_umb_ebay_category', $itemDetails['Item']['PrimaryCategory']['CategoryID'] );

					update_post_meta( $product_id, 'ced_umb_ebay_ebay_status', 'PUBLISHED' );
					update_post_meta( $product_id, 'ced_ebay_product_data', $itemDetails['Item'] );
					if ( isset( $itemDetails['Item']['ProductListingDetails']['UPC'] ) ) {
						update_post_meta( $product_id, 'ced_ebay_product_upc', $itemDetails['Item']['ProductListingDetails']['UPC'] );
					}

					if ( isset( $itemDetails['Item']['ProductListingDetails']['BrandMPN']['MPN'] ) ) {
						update_post_meta( $product_id, 'ced_ebay_product_mpn', $itemDetails['Item']['ProductListingDetails']['BrandMPN']['MPN'] );
					}

					if ( isset( $itemDetails['Item']['ProductListingDetails']['BrandMPN']['Brand'] ) ) {
						update_post_meta( $product_id, 'ced_ebay_product_brand', $itemDetails['Item']['ProductListingDetails']['BrandMPN']['Brand'] );
					}

					if ( isset( $itemDetails['Item']['ItemSpecifics']['NameValueList'] ) && ! isset( $itemDetails['Item']['ItemSpecifics']['NameValueList'][0] ) ) {
						$tempNameValueList = array();
						$tempNameValueList = $itemDetails['Item']['ItemSpecifics']['NameValueList'];
						unset( $itemDetails['Item']['ItemSpecifics']['NameValueList'] );
						$itemDetails['Item']['ItemSpecifics']['NameValueList'][] = $tempNameValueList;
					}
					if ( ! empty( $itemDetails['Item']['ItemSpecifics']['NameValueList'] ) ) {
						foreach ( $itemDetails['Item']['ItemSpecifics']['NameValueList'] as $key => $attributes ) {

							delete_transient( 'wc_attribute_taxonomies' );
							WC_Cache_Helper::incr_cache_prefix( 'woocommerce-attributes' );
							$attributeName = $attributes['Name'];

							if ( 'Year' == $attributes['Name'] ) {
								$attributeSlug = $attributes['Name'] . '-attr';
							} elseif ( 'Type' == $attributes['Name'] ) {
								$attributeSlug = $attributes['Name'] . '-attr';
							} else {
								$attributeSlug = $attributes['Name'];
							}
							if ( 28 < strlen( $attributeSlug ) ) {
								$attributeSlug = substr( $attributeSlug, 0, 10 );
							}
							$attributeLabels = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name' );
							$attributeWCName = array_search( $attributeSlug, $attributeLabels, true );

							if ( ! $attributeWCName ) {
								$attributeWCName = wc_sanitize_taxonomy_name( $attributeSlug );
							}

							$attributeId = wc_attribute_taxonomy_id_by_name( $attributeWCName );
							if ( ! $attributeId ) {
								$taxonomyName = wc_attribute_taxonomy_name( $attributeWCName );
								unregister_taxonomy( $taxonomyName );
								$attributeId = wc_create_attribute(
									array(
										'name'         => $attributeName,
										'slug'         => $attributeSlug,
										'type'         => 'select',
										'order_by'     => 'menu_order',
										'has_archives' => 0,
									)
								);
								/**
								 * Woocommerce_taxonomy_objects.
								 *
								 * @since 1.0.0
								 */
								register_taxonomy(
									$taxonomyName,
									/**
										 * Taxonomy objects
										 *
										 * @since 1.0.0
										 */
									apply_filters(
										'woocommerce_taxonomy_objects_' . $taxonomyName,
										array(
											'product',
										)
									),
									/**
										 * Taxonomy args
										 *
										 * @since 1.0.0
										 */
									apply_filters(
										'woocommerce_taxonomy_args_' . $taxonomyName,
										array(
											'labels'       => array(
												'name' => $attributeSlug,
											),
											'hierarchical' => false,
											'show_ui'      => false,
											'query_var'    => true,
											'rewrite'      => false,
										)
									)
								);
							}

							$taxonomy = $attributes['Name'];
							if ( strlen( $taxonomy ) > 28 ) {
								$taxonomy = substr( $taxonomy, 0, 10 );
							}
							$taxonomyName = wc_attribute_taxonomy_name( $taxonomy );
							if ( 'Year' == $taxonomy ) {
								$taxonomyName = $taxonomyName . '-attr';
							}
							if ( 'Type' == $taxonomy ) {
								$taxonomyName = $taxonomyName . '-attr';
							}
							if ( strlen( $taxonomyName ) > 28 ) {
								$taxonomyName = substr( $taxonomyName, 0, 10 );
							}
							if ( ! empty( $attributes['Value'] ) && is_array( $attributes['Value'] ) ) {
								$term_id = array();
								foreach ( $attributes['Value'] as $key => $attr_terms ) {
									$termArrayName = $attr_terms;
									$termArraySlug = $attr_terms;
									$term          = get_term_by( 'slug', $termArrayName, $taxonomyName );
									if ( ! $term ) {
										$term = wp_insert_term(
											$termArrayName,
											$taxonomyName,
											array(
												'slug' => $termArraySlug,
											)
										);
										if ( isset( $term->error_data['term_exists'] ) ) {
											$term_id = $term->error_data['term_exists'];
										} elseif ( is_array( $term ) ) {
												$term_id[] = $term['term_id'];
										} else {
											$term_id[] = $term->term_id;
										}
									} else {
										$term_id[] = $term->term_id;
									}
								}
							}
							if ( ! empty( $attributes['Value'] ) && ! is_array( $attributes['Value'] ) ) {
								$termName = $attributes['Value'];
								$termSlug = $attributes['Value'];
								$term     = get_term_by( 'slug', $termSlug, $taxonomyName );
								if ( ! $term ) {
									$term = wp_insert_term(
										$termName,
										$taxonomyName,
										array(
											'slug' => $termSlug,
										)
									);
									if ( isset( $term->error_data['term_exists'] ) ) {
										$term_id = $term->error_data['term_exists'];
									} elseif ( is_array( $term ) ) {
											$term_id = $term['term_id'];
									} else {
										$term_id = $term->term_id;
									}
								} else {
									$term_id = $term->term_id;
								}
							}
							$product_attributes = (array) $simpleProduct->get_attributes();
							if ( array_key_exists( $taxonomy, $product_attributes ) ) {
								foreach ( $product_attributes as $key1 => $product_attribute ) {
									if ( $key1 == $taxonomy ) {
										if ( is_array( $term_id ) ) {
											$product_attribute->set_options( $term_id );
										} else {
											$product_attribute->set_options( array( $term_id ) );
										}
										$product_attributes[ $key1 ] = $product_attribute;
										break;
									}
								}
								$simpleProduct->set_attributes( $product_attributes );
							} else {
								$prod_attribute = new WC_Product_Attribute();

								$prod_attribute->set_id( count( $product_attributes ) + 1 );
								$prod_attribute->set_name( $taxonomyName );
								if ( is_array( $term_id ) ) {
									$prod_attribute->set_options( $term_id );
								} else {
									$prod_attribute->set_options( array( $term_id ) );
								}
								$prod_attribute->set_position( count( $product_attributes ) + 1 );
								$prod_attribute->set_visible( true );
								$prod_attribute->set_variation( false );
								$product_attributes[] = $prod_attribute;

								$simpleProduct->set_attributes( $product_attributes );
							}
						}

						$simpleProduct->save();

					}

					if ( isset( $itemDetails['Item']['ConditionDisplayName'] ) && ! empty( $itemDetails['Item']['ConditionDisplayName'] ) ) {
						// delete_transient( 'wc_attribute_taxonomies' );
						WC_Cache_Helper::incr_cache_prefix( 'woocommerce-attributes' );
						$attributeName = 'Condition';

						$attributeSlug = 'Condition';

						$attributeLabels = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name' );
						$attributeWCName = array_search( $attributeSlug, $attributeLabels, true );

						if ( ! $attributeWCName ) {
							$attributeWCName = wc_sanitize_taxonomy_name( $attributeSlug );
						}

						$attributeId = wc_attribute_taxonomy_id_by_name( $attributeWCName );
						if ( ! $attributeId ) {
							$taxonomyName = wc_attribute_taxonomy_name( $attributeWCName );
							unregister_taxonomy( $taxonomyName );
							$attributeId = wc_create_attribute(
								array(
									'name'         => $attributeName,
									'slug'         => $attributeSlug,
									'type'         => 'select',
									'order_by'     => 'menu_order',
									'has_archives' => 0,
								)
							);

							/**
							 * Woocommerce_taxonomy_objects.
							 *
							 * @since 1.0.0
							 */
							register_taxonomy(
								$taxonomyName,
								/**
										 * Taxonomy objects
										 *
										 * @since 1.0.0
										 */
								apply_filters(
									'woocommerce_taxonomy_objects_' . $taxonomyName,
									array(
										'product',
									)
								),
								/**
										 * Taxonomy args
										 *
										 * @since 1.0.0
										 */
								apply_filters(
									'woocommerce_taxonomy_args_' . $taxonomyName,
									array(
										'labels'       => array(
											'name' => $attributeSlug,
										),
										'hierarchical' => false,
										'show_ui'      => false,
										'query_var'    => true,
										'rewrite'      => false,
									)
								)
							);
						}

						$taxonomy = 'Condition';

						$taxonomyName = wc_attribute_taxonomy_name( $taxonomy );

						if ( strlen( $taxonomyName ) > 28 ) {
							$taxonomyName = substr( $taxonomyName, 0, 10 );
						}
						$termName = $itemDetails['Item']['ConditionDisplayName'];
						$termSlug = $itemDetails['Item']['ConditionDisplayName'];
						$term     = get_term_by( 'slug', $termSlug, $taxonomyName );
						if ( ! $term ) {
							$term = wp_insert_term(
								$termName,
								$taxonomyName,
								array(
									'slug' => $termSlug,
								)
							);
							if ( isset( $term->error_data['term_exists'] ) ) {
								$term_id = $term->error_data['term_exists'];
							} elseif ( is_array( $term ) ) {
									$term_id = $term['term_id'];
							} else {
								$term_id = $term->term_id;
							}
						} else {
							$term_id = $term->term_id;
						}

						wp_set_object_terms( $term_id, $itemDetails['Item']['ConditionDisplayName'], 'pa_condition' );
						$get_attribute      = get_term_by( 'name', $itemDetails['Item']['ConditionDisplayName'], 'pa_condition' );
						$product_attributes = $simpleProduct->get_attributes();

						// Set the new attribute value

						$prod_attribute = new WC_Product_Attribute();

						$prod_attribute->set_id( count( $product_attributes ) + 1 );
						$prod_attribute->set_name( $taxonomyName );
						if ( is_array( $term_id ) ) {
							$prod_attribute->set_options( $term_id );
						} else {
							$prod_attribute->set_options( array( $term_id ) );
						}
						$prod_attribute->set_position( count( $product_attributes ) + 1 );
						$prod_attribute->set_visible( true );
						$prod_attribute->set_variation( false );
						$product_attributes[] = $prod_attribute;

						$simpleProduct->set_attributes( $product_attributes );

						$simpleProduct->save();
					}
				}
				$update_prod = wc_get_product( $product_id );
				$update_prod->set_status( 'publish' );
				$update_prod->save();
			}
			if ( false == $isPhpInvoked ) {
				$response = array(
					'status' => 'Bulk Import Successful',
					'title'  => $itemDetails['Item']['Title'],
				);
				wp_send_json( $response );
			}
		} else {
			$response['status'] = __( 'Please select any product for import!', 'ced-umb-ebay' );
			wp_send_json( $response );
		}
	}



	public function ced_ebay_oauth_authorization() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$site_id          = isset( $_POST['site_id'] ) ? sanitize_text_field( $_POST['site_id'] ) : '';
			$login_mode       = isset( $_POST['login_mode'] ) ? sanitize_text_field( $_POST['login_mode'] ) : '';
			$oAuthFile        = CED_EBAY_DIRPATH . 'admin/ebay/lib/cedOAuthAuthorization.php';
			$renderDependency = $this->renderDependency( $oAuthFile );
			if ( 'production' == $login_mode || '' == $login_mode ) {
				update_option( 'ced_ebay_mode_of_operation', 'production' );
			}
			if ( $renderDependency ) {
				$cedAuthorization        = new Ced_Ebay_OAuth_Authorization();
				$cedAuhorizationInstance = $cedAuthorization->get_instance();
				$authURL                 = $cedAuhorizationInstance->doOAuthAuthorization( $site_id );
				echo json_encode( $authURL );
				die;
			}
		}
	}






	public function ced_ebay_recurring_bulk_upload_manager( $args ) {
		$fetchCurrentAction = current_action();
		if ( strpos( $fetchCurrentAction, 'wp_ajax_nopriv_' ) !== false ) {
			$user_id = isset( $_GET['user_id'] ) ? wc_clean( $_GET['user_id'] ) : false;
			$site_id = isset( $_GET['site_id'] ) ? wc_clean( $_GET['site_id'] ) : false;
		}
		if ( ! empty( $args ) && isset( $args['user_id'] ) && isset( $args['site_id'] ) ) {
			$user_id          = $args['user_id'];
			$site_id          = $args['site_id'];
			$pre_flight_check = ced_ebay_pre_flight_check( $user_id, $site_id );
			if ( ! $pre_flight_check ) {
				return;
			}
			$has_action = as_get_scheduled_actions(
				array(
					'args'   => array(
						'data' =>
						array(
							'user_id' => $user_id,
							'site_id' => $site_id,
						),
					),
					'group'  => 'ced_ebay_bulk_upload_' . $user_id,
					'status' => ActionScheduler_Store::STATUS_PENDING,
				),
				'ARRAY_A'
			);
			if ( empty( $has_action ) ) {
				$this->ced_ebay_toggle_bulk_upload_action( $site_id, $user_id, true );
			}
		}
	}


	public function ced_ebay_toggle_bulk_upload_action( $site_id, $ebay_user_id, $if_php_invoked = false, $endpoint_enabled = false ) {
		if ( ! $if_php_invoked ) {
			$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		} else {
			$check_ajax = true;
		}
		if ( $check_ajax ) {
			$user_id          = $ebay_user_id;
			$pre_flight_check = ced_ebay_pre_flight_check( $user_id, $site_id );
			if ( ! $pre_flight_check ) {
				return false;
			}

			if ( ( 'turn_on' == $toggle_action || $if_php_invoked ) && ! empty( $user_id ) ) {
				$renderDataOnGlobalSettings = get_option( 'ced_ebay_global_settings', false );
				if ( isset( $renderDataOnGlobalSettings[ $user_id ][ $site_id ] ) && is_array( $renderDataOnGlobalSettings[ $user_id ][ $site_id ] ) ) {

					$paymentPolicyIdAndName     = ! empty( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_payment_policy'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_payment_policy'] : '';
					$returnPolicyIdAndName      = ! empty( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_return_policy'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_return_policy'] : '';
					$fulfillmentPolicyIdAndName = ! empty( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_shipping_policy'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_shipping_policy'] : '';
					if ( empty( $fulfillmentPolicyIdAndName ) || empty( $returnPolicyIdAndName ) || empty( $fulfillmentPolicyIdAndName ) ) {
						return false;
					}
				}
				$skip                    = false;
				$total_profiles          = 0;
				$selected_profiles_count = 0;
				$async_action_id         = array();
				$products_to_upload      = 0;
				global $wpdb;
				$profile_ids = $wpdb->get_results( $wpdb->prepare( "SELECT `id` FROM {$wpdb->prefix}ced_ebay_profiles WHERE `ebay_user` = %s AND `ebay_site`=%s", $user_id, $site_id ), 'ARRAY_A' );
				if ( ! empty( $profile_ids ) && is_array( $profile_ids ) ) {
					if ( ! as_has_scheduled_action(
						'ced_ebay_recurring_bulk_upload_' . $user_id,
						array(
							'data' => array(
								'user_id' => $user_id,
								'site_id' => $site_id,
							),
						)
					) && ! $endpoint_enabled ) {
						as_schedule_recurring_action(
							time(),
							'300',
							'ced_ebay_recurring_bulk_upload_' . $user_id,
							array(
								'data' => array(
									'user_id' => $user_id,
									'site_id' => $site_id,
								),
							)
						);
					}
					$total_profiles = count( $profile_ids );
					foreach ( $profile_ids as $index => $id ) {
						$skip         = false;
						$profile_data = $wpdb->get_results( $wpdb->prepare( "SELECT `profile_data` FROM {$wpdb->prefix}ced_ebay_profiles WHERE `id` = %s ", $id['id'] ), 'ARRAY_A' );
						$profile_data = json_decode( $profile_data[0]['profile_data'], true );
						foreach ( $profile_data as $key => $data ) {
							$catId = $profile_data['_umb_ebay_category']['default'];
							if ( strpos( $key, $catId ) !== false ) {
								if ( isset( $data['required'] ) && empty( $data['default'] ) ) {
									if ( 'null' == $data['metakey'] ) {
										$skip = true;
									}
								}
							}
							if ( count( $profile_data ) == 1 ) {
								$skip = true;
							}
						}
						if ( true == $skip ) {
							continue;
						} else {
							++$selected_profiles_count;
						}

						$profiles_to_be_mapped = array();

						$get_assigned_categories = $wpdb->get_results( $wpdb->prepare( "SELECT `woo_categories` FROM {$wpdb->prefix}ced_ebay_profiles WHERE `id` IN (%s) ", $id ), 'ARRAY_A' );
						if ( isset( $get_assigned_categories[0] ) ) {
							$profiles_to_be_mapped[] = $get_assigned_categories[0];
						}
						$product_categories = array();

						foreach ( $profiles_to_be_mapped as $key => $category_array ) {
							$product_categories = array_merge( $product_categories, json_decode( $category_array['woo_categories'], true ) );
						}
						if ( empty( $product_categories ) ) {
							continue;
						}

						$meta_key       = '_ced_ebay_listing_id_' . $user_id . '>' . $site_id;
						$store_products = get_posts(
							array(
								'meta_query'     => array(
									array(
										'key'     => $meta_key,
										'compare' => 'NOT EXISTS',
									),
								),
								'post_type'      => 'product',
								'posts_per_page' => -1,
								'post_status'    => 'publish',
								'tax_query'      => array(
									array(
										'taxonomy'         => 'product_cat',
										'terms'            => $product_categories,
										'operator'         => 'IN',
										'include_children' => false,
									),
								),
							)
						);

						$store_products          = wp_list_pluck( $store_products, 'ID' );
						$selected_store_products = array();
						foreach ( $store_products as $key1 => $product_id ) {
							$product = wc_get_product( $product_id );
							if ( $product->is_type( 'simple' ) ) {
								$count_simple_product_in_stock = $wpdb->get_var(
									$wpdb->prepare(
										"
										SELECT COUNT(ID)
										FROM {$wpdb->posts} p
										INNER JOIN {$wpdb->postmeta} pm
										ON p.ID           =  pm.post_id
										WHERE p.post_type     =  'product'
										AND p.post_status =  'publish'
										AND p.ID =  %d
										AND pm.meta_key   =  '_stock_status'
										AND pm.meta_value != 'outofstock'
										",
										$product_id
									)
								);
								$count_simple_product_in_stock = $count_simple_product_in_stock > 0 ? true : false;
								if ( ! $count_simple_product_in_stock ) {
									continue;
								} else {
									$selected_store_products[] = $product_id;
								}
							} elseif ( $product->is_type( 'variable' ) ) {
								// Skip a varible product from upload if all of its variations are out of stock.
								$count_variations_in_stock = $wpdb->get_var(
									$wpdb->prepare(
										"
										SELECT COUNT(ID)
										FROM {$wpdb->posts} p
										INNER JOIN {$wpdb->postmeta} pm
										ON p.ID           =  pm.post_id
										WHERE p.post_type     =  'product_variation'
										AND p.post_status =  'publish'
										AND p.post_parent =  %d
										AND pm.meta_key   =  '_stock_status'
										AND pm.meta_value != 'outofstock'
										",
										$product_id
									)
								);
								$count_variations_in_stock = $count_variations_in_stock > 0 ? true : false;
								if ( ! $count_variations_in_stock ) {
									continue;
								} else {
									$selected_store_products[] = $product_id;
								}
							}
						}
						$store_products = $selected_store_products;
						if ( empty( $store_products ) ) {
							continue;
						}
						$products_to_upload = $products_to_upload + count( $store_products );
						foreach ( $store_products as $key1 => $product_id ) {
							$product = wc_get_product( $product_id );

							if ( $product->is_type( 'simple' ) ) {
								$product_type = 'simple';
							} elseif ( $product->is_type( 'variable' ) ) {
								$product_type = 'variable';
							}
							$time   = time();
							$offset = '.000Z';
							$date   = gmdate( 'Y-m-d', $time ) . 'T' . gmdate( 'H:i:s', $time ) . $offset;
							if ( ! $endpoint_enabled ) {
								$async_action_id[] = as_enqueue_async_action(
									'ced_ebay_async_bulk_upload_action',
									array(
										'data' => array(
											'product_id'   => $product_id,
											'user_id'      => $user_id,
											'site_id'      => $site_id,
											'product_type' => $product_type,
											'profile_id'   => $id,
											'schedule_time' => $date,

										),
									),
									'ced_ebay_bulk_upload_' . $user_id . '>' . $site_id
								);
							} else {
								$backgrounProcessArgs = array(
									'product_id' => $product_id,
									'user_id'    => $user_id,
									'site_id'    => $site_id,
									'profile_id' => $id,
								);
								$this->schedule_bulk_upload_task->push_to_queue( $data );
							}
						}

						if ( ! empty( $backgrounProcessArgs ) ) {
							$this->schedule_bulk_upload_task->save()->dispatch();
						}
					}
				} else {
					return false;
				}
			}
		}
	}


	public function ced_ebay_delete_bulk_upload_logs_action() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$user_id = isset( $_POST['userid'] ) ? sanitize_text_field( $_POST['userid'] ) : '';
			global $wpdb;
			$table_name = $wpdb->prefix . 'ced_ebay_bulk_upload';
			$get_logs   = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM  %s WHERE user_id = %s', $table_name, $user_id ) );
			if ( empty( $wpdb->last_error ) && empty( $get_logs ) ) {
				echo json_encode(
					array(
						'status'  => 'success',
						'message' => 'No logs found',
						'title'   => 'Bulk Upload',
					)
				);
				wp_die();
			} else {
				$delete_logs = $wpdb->get_results( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `user_id` = %s", $user_id ) );
				if ( empty( $wpdb->last_error ) ) {
					echo json_encode(
						array(
							'status'  => 'success',
							'message' => 'Bulk Upload logs have been deleted successfully',
							'title'   => 'Delete logs',
						)
					);
					wp_die();
				} else {
					echo json_encode(
						array(
							'status'  => 'error',
							'message' => 'Failed to delete logs. Please contact support!',
							'title'   => 'Delete logs',
						)
					);
					wp_die();
				}
			}
		}
	}



	public function ced_ebay_async_bulk_upload_callback( $data ) {
		$logger  = wc_get_logger();
		$context = array( 'source' => 'ced_ebay_async_bulk_upload_callback' );
		if ( is_array( $data ) && ! empty( $data ) ) {
			global $wpdb;
			$user_id       = $data['user_id'];
			$site_id       = $data['site_id'];
			$product_id    = $data['product_id'];
			$profile_id    = $data['profile_id']['id'];
			$product_ids   = array();
			$database_data = $wpdb->get_results( $wpdb->prepare( "SELECT `product_id` FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `user_id` = %s AND `site_id` = %s", $user_id, $site_id ) );
			if ( ! empty( $database_data ) ) {
				foreach ( $database_data as $key => $value ) {
					array_push( $product_ids, $value->product_id );
				}
			}
			$ced_ebay_manager = $this->ced_ebay_manager;
			$shop_data        = ced_ebay_get_shop_data( $user_id, $site_id );
			if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
				$siteID      = $site_id;
				$token       = $shop_data['access_token'];
				$getLocation = $shop_data['location'];
			} else {
				return;
			}
			$scheduler_args = array(
				'user_id' => $user_id,
				'site_id' => $site_id,
			);
			$SimpleXml      = $ced_ebay_manager->prepareProductHtmlForUpload( $user_id, $siteID, $product_id );
			if ( is_array( $SimpleXml ) && ! empty( $SimpleXml ) ) {
				require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
			} else {
				return false;
			}
			$ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
			$uploadOnEbay       = $ebayUploadInstance->upload( $SimpleXml[0], $SimpleXml[1] );

			if ( isset( $uploadOnEbay['Ack'] ) ) {
				$temp_prd_upload_error = array();
				if ( ! isset( $uploadOnEbay['Errors'][0] ) ) {
					$temp_prd_upload_error = $uploadOnEbay['Errors'];
					unset( $uploadOnEbay['Errors'] );
					$uploadOnEbay['Errors'][] = $temp_prd_upload_error;
				}
				if ( 'Failure' == $uploadOnEbay['Ack'] ) {
					if ( ! empty( $uploadOnEbay['Errors'] ) ) {
						foreach ( $uploadOnEbay['Errors'] as $key => $ebay_api_error ) {
							if ( '518' == $ebay_api_error['ErrorCode'] ) {
								if ( function_exists( 'as_get_scheduled_actions' ) ) {
									$has_action = as_get_scheduled_actions(
										array(
											'args'   => array( 'data' => $scheduler_args ),
											'group'  => 'ced_ebay_bulk_upload_' . $user_id,
											'status' => ActionScheduler_Store::STATUS_PENDING,
										),
										'ARRAY_A'
									);
								}
								if ( ! empty( $has_action ) ) {
									if ( function_exists( 'as_unschedule_all_actions' ) ) {
										$unschedule_actions = as_unschedule_all_actions( null, null, 'ced_ebay_bulk_upload_' . $user_id, array( 'data' => $scheduler_args ) );
										$logger->info( 'Call usage limit reached. Unscheduling all bulk upload actions.', $context );
										break;
									}
								}
							}
						}
					}
				}

				if ( 'Warning' == $uploadOnEbay['Ack'] || 'Success' == $uploadOnEbay['Ack'] ) {
					$ebayID = $uploadOnEbay['ItemID'];
					update_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, $ebayID );
					$time   = time();
					$offset = '.000Z';
					$date   = gmdate( 'Y-m-d', $time ) . 'T' . gmdate( 'H:i:s', $time ) . $offset;
					if ( ! empty( $product_ids ) && in_array( $product_id, $product_ids ) ) {
						$table_data = array(
							'product_id'       => $product_id,
							'profile_id'       => $profile_id,
							'operation_status' => 'Uploaded',
							'user_id'          => (string) $user_id,
							'site_id'          => (int) $site_id,
							'error'            => null,
							'scheduled_time'   => $date,
							'bulk_action_type' => 'upload',
						);

						$profileTableName = $wpdb->prefix . 'ced_ebay_bulk_upload';
						$wpdb->update( $profileTableName, $table_data, array( 'product_id' => $product_id ), array( '%s' ) );

					} else {
						$table_data       = array(
							'product_id'       => $product_id,
							'profile_id'       => $profile_id,
							'operation_status' => 'Uploaded',
							'user_id'          => (string) $user_id,
							'site_id'          => (int) $site_id,
							'scheduled_time'   => $date,
							'bulk_action_type' => 'upload',
						);
						$profileTableName = $wpdb->prefix . 'ced_ebay_bulk_upload';
						$wpdb->insert( $profileTableName, $table_data, array( '%s' ) );
						$bulkId = $wpdb->insert_id;
					}
				}

				if ( 'Failure' == $uploadOnEbay['Ack'] ) {
					if ( ! empty( $uploadOnEbay['Errors'][0] ) ) {
						$error = array();
						foreach ( $uploadOnEbay['Errors'] as $key => $value ) {
							if ( 'Error' == $value['SeverityCode'] ) {
								$error_data                               = str_replace( array( '<', '>' ), array( '{', '}' ), $value['LongMessage'] );
								$error[ $value['ErrorCode'] ]['message']  = $error_data;
								$error[ $value['ErrorCode'] ]['severity'] = $value['SeverityCode'];

							}
						}
						$error_json = json_encode( $error );

					} else {
						$error = array();
						$error[ $uploadOnEbay['Errors']['ErrorCode'] ]['message']  = str_replace( array( '<', '>' ), array( '{', '}' ), $uploadOnEbay['Errors']['LongMessage'] );
						$error[ $uploadOnEbay['Errors']['ErrorCode'] ]['severity'] = $uploadOnEbay['Errors']['SeverityCode'];
						$error_json = json_encode( $error );

					}
					$time   = time();
					$offset = '.000Z';
					$date   = gmdate( 'Y-m-d', $time ) . 'T' . gmdate( 'H:i:s', $time ) . $offset;
					if ( ! empty( $product_ids ) && in_array( $product_id, $product_ids ) ) {

						$table_data = array(
							'product_id'       => $product_id,
							'profile_id'       => $profile_id,
							'operation_status' => 'Error',
							'user_id'          => (string) $user_id,
							'site_id'          => (int) $site_id,
							'scheduled_time'   => $date,
							'error'            => $error_json,
							'bulk_action_type' => 'upload',
						);

						$profileTableName = $wpdb->prefix . 'ced_ebay_bulk_upload';
						$wpdb->update( $profileTableName, $table_data, array( 'product_id' => $product_id ), array( '%s' ) );

					} else {

						$table_data = array(
							'product_id'       => $product_id,
							'profile_id'       => $profile_id,
							'operation_status' => 'Error',
							'user_id'          => (string) $user_id,
							'site_id'          => (int) $site_id,
							'scheduled_time'   => $date,
							'error'            => $error_json,
							'bulk_action_type' => 'upload',
						);

						$profileTableName = $wpdb->prefix . 'ced_ebay_bulk_upload';

						$wpdb->insert( $profileTableName, $table_data, array( '%s' ) );
						$bulkId = $wpdb->insert_id;
					}
				}
			}
		}
	}


	public function ced_ebay_reset_category_item_specifics() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			global $wp_filesystem;
			$wp_folder     = wp_upload_dir();
			$wp_upload_dir = $wp_folder['basedir'];
			$user_id       = isset( $_POST['userid'] ) ? sanitize_text_field( $_POST['userid'] ) : '';
			$site_id       = isset( $_POST['site_id'] ) ? sanitize_text_field( $_POST['site_id'] ) : '';

			$wp_upload_dir = $wp_upload_dir . '/ced-ebay/category-specifics/' . $user_id . '/' . $site_id . '/';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
			$dir_exists = $wp_filesystem->exists( $wp_upload_dir );
			if ( $dir_exists ) {
				$is_deleted = $wp_filesystem->rmdir( $wp_upload_dir, true );
				if ( $is_deleted ) {
					if ( ! is_dir( $wp_upload_dir ) ) {
						if ( wp_mkdir_p( $wp_upload_dir, 0777 ) ) {
							wp_send_json_success(
								array(
									'message' => 'eBay category item specifics has been reset.',
								)
							);
						} else {
							wp_send_json_error(
								array(
									'message' => 'An error has occured while trying to delete and re-create directory structure. Please contact support!',
								)
							);
						}
					}
				}
			} else {
				wp_send_json_error(
					array(
						'message' => 'Unable to find the directory to delete!',
					)
				);
			}
		}
	}


	public function ced_ebay_remove_term_from_profile() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$user_id    = isset( $_POST['userid'] ) ? sanitize_text_field( $_POST['userid'] ) : '';
			$site_id    = isset( $_POST['site_id'] ) ? sanitize_text_field( $_POST['site_id'] ) : '';
			$term_id    = isset( $_POST['term_id'] ) ? sanitize_text_field( $_POST['term_id'] ) : '';
			$profile_id = isset( $_POST['profile_id'] ) ? sanitize_text_field( $_POST['profile_id'] ) : '';
			if ( ! empty( $term_id ) && ! empty( $profile_id ) ) {
				global $wpdb;
				$get_ebay_profile = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_ebay_profiles WHERE `ebay_user`=%s AND `ebay_site`=%s AND `id` = %s", $user_id, $site_id, $profile_id ), 'ARRAY_A' );
				if ( empty( $wpdb->last_error ) ) {
					if ( ! empty( $get_ebay_profile[0] ) ) {
						$woo_categories = json_decode( $get_ebay_profile[0]['woo_categories'], true );
						if ( ! empty( $woo_categories ) ) {
							if ( ( array_search( $term_id, $woo_categories ) ) !== false ) {
								$term_position = array_search( $term_id, $woo_categories );
								delete_term_meta( $term_id, 'ced_ebay_profile_created_' . $user_id . '>' . $site_id );
								delete_term_meta( $term_id, 'ced_ebay_profile_id_' . $user_id . '>' . $site_id );
								delete_term_meta( $term_id, 'ced_ebay_mapped_category_' . $user_id . '>' . $site_id );
								delete_term_meta( $term_id, 'ced_ebay_mapped_to_store_category_' . $user_id . '>' . $site_id );
								delete_term_meta( $term_id, 'ced_ebay_mapped_to_store_secondary_category_' . $user_id . '>' . $site_id );
								delete_term_meta( $term_id, 'ced_ebay_mapped_secondary_category_' . $user_id . '>' . $site_id );
								unset( $woo_categories[ $term_position ] );
								$tableName = $wpdb->prefix . 'ced_ebay_profiles';
								$wpdb->update(
									$tableName,
									array(
										'woo_categories' => json_encode( $woo_categories ),
									),
									array( 'id' => $profile_id ),
									array( '%s' )
								);
								if ( empty( $wpdb->last_error ) ) {
									wp_send_json_success(
										array(
											'message' => 'The selected category has been removed from the template.',
										)
									);
								} else {
									wp_send_json_error(
										array(
											'message' => 'There was an error in removing the category from the template.',
										)
									);
								}
							}
						}
					}
				} else {
					wp_send_json_error(
						array(
							'message' => 'There was an error in removing the category from the template.',
						)
					);
				}
			}
		}
	}





	public function ced_ebay_remove_all_profiles() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			global $wpdb;
			$user_id              = isset( $_POST['userid'] ) ? sanitize_text_field( $_POST['userid'] ) : '';
			$site_id              = isset( $_POST['site_id'] ) ? sanitize_text_field( $_POST['site_id'] ) : '';
			$woo_store_categories = get_categories(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
				)
			);
			$profile_ids          = array();
			$ebay_profiles        = $wpdb->get_results( $wpdb->prepare( "SELECT `id` FROM {$wpdb->prefix}ced_ebay_profiles WHERE `ebay_user`=%s AND `ebay_site`=%s", $user_id, $site_id ), 'ARRAY_A' );
			if ( empty( $ebay_profiles ) ) {
				wp_send_json_error(
					array(
						'message' => 'There are no templates to delete',
					)
				);
			}
			foreach ( $ebay_profiles as $key => $ebay_profile ) {
				$profile_ids[] = $ebay_profile['id'];
			}
			$wp_folder     = wp_upload_dir();
			$wp_upload_dir = $wp_folder['basedir'];
			$wp_upload_dir = $wp_upload_dir . '/ced-ebay/category-specifics/' . $user_id . '/' . $site_id . '/';
			foreach ( $profile_ids as $key => $pid ) {
				$product_ids_assigned = get_option( 'ced_ebay_product_ids_in_profile_' . $pid, array() );
				if ( ! empty( $product_ids_assigned ) ) {
					foreach ( $product_ids_assigned as $index => $ppid ) {
						delete_post_meta( $ppid, 'ced_ebay_profile_assigned' . $user_id );
					}
				}
				$ebay_category_id_array = $wpdb->get_results( $wpdb->prepare( "SELECT `profile_data` FROM {$wpdb->prefix}ced_ebay_profiles WHERE `id` = %s ", $pid ), 'ARRAY_A' );
				$ebay_category_id_array = json_decode( $ebay_category_id_array[0]['profile_data'], true );
				if ( ! empty( $ebay_category_id_array['_umb_ebay_category']['default'] ) ) {
					$ebay_cat_id = $ebay_category_id_array['_umb_ebay_category']['default'];
					if ( ! empty( $ebay_cat_id ) && file_exists( $wp_upload_dir . 'ebaycat_' . $ebay_cat_id . '.json' ) ) {
						wp_delete_file( $wp_upload_dir . 'ebaycat_' . $ebay_cat_id . '.json' );
					}
				}

				$term_id = $wpdb->get_results( $wpdb->prepare( "SELECT `woo_categories` FROM {$wpdb->prefix}ced_ebay_profiles WHERE `id` = %s ", $pid ), 'ARRAY_A' );
				$term_id = json_decode( $term_id[0]['woo_categories'], true );
				foreach ( $term_id as $key => $value ) {
					delete_term_meta( $value, 'ced_ebay_mapped_to_store_category_' . $user_id . '>' . $site_id );
					delete_term_meta( $value, 'ced_ebay_profile_created_' . $user_id . '>' . $site_id );
					delete_term_meta( $value, 'ced_ebay_profile_id_' . $user_id . '>' . $site_id );
					delete_term_meta( $value, 'ced_ebay_mapped_category_' . $user_id . '>' . $site_id );
					delete_term_meta( $value, 'ced_ebay_mapped_to_store_category_' . $user_id . '>' . $site_id );
					delete_term_meta( $value, 'ced_ebay_mapped_to_store_secondary_category_' . $user_id . '>' . $site_id );
					delete_term_meta( $value, 'ced_ebay_mapped_secondary_category_' . $user_id . '>' . $site_id );

				}

				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_profiles WHERE `id` IN (%s)", $pid ) );
			}

			if ( empty( $wpdb->last_error ) ) {
				wp_send_json_success(
					array(
						'message' => 'Templates Deleted Successfully',
					)
				);
			} else {
				wp_send_json_error(
					array(
						'message' => 'There was an error while trying to delete templates.',
					)
				);
			}
		}
	}

	public function ced_ebay_remove_account_from_integration() {
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$user_id         = isset( $_POST['userid'] ) ? sanitize_text_field( $_POST['userid'] ) : '';
			$site_id         = isset( $_POST['site_id'] ) ? sanitize_text_field( $_POST['site_id'] ) : '';
			$shop_data       = ced_ebay_get_shop_data( $user_id, $site_id );
			$is_site_primary = false;
			if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] && isset( $shop_data['is_site_primary'] ) && true == $shop_data['is_site_primary'] ) {
				$is_site_primary = true;
			}
			if ( empty( $shop_data ) || true !== $shop_data['is_site_valid'] ) {
				wp_send_json_error(
					array(
						'status'  => 'error',
						'message' => 'Invalid eBay Account',
					)
				);
			}
			$connected_accounts = ! empty( get_option( 'ced_ebay_connected_accounts' ) ) ? get_option( 'ced_ebay_connected_accounts', true ) : array();
			if ( ! empty( $connected_accounts ) ) {
				if ( isset( $connected_accounts[ $user_id ][ $site_id ] ) ) {
					unset( $connected_accounts[ $user_id ][ $site_id ] );
					if ( 0 == count( $connected_accounts[ $user_id ] ) ) {
						$access_token_arr = ! empty( get_option( 'ced_ebay_user_access_token' ) ) ? get_option( 'ced_ebay_user_access_token' ) : array();
						if ( isset( $access_token_arr[ $user_id ] ) ) {
							unset( $access_token_arr[ $user_id ] );
							update_option( 'ced_ebay_user_access_token', $access_token_arr );
						}
						unset( $connected_accounts[ $user_id ] );
					}
					update_option( 'ced_ebay_connected_accounts', $connected_accounts );
				}
				$scheduler_args = array(
					'user_id' => $user_id,
					'site_id' => $site_id,
				);
				if ( wp_next_scheduled( 'ced_ebay_existing_products_sync_job_' . $user_id, $scheduler_args ) ) {
					wp_clear_scheduled_hook( 'ced_ebay_existing_products_sync_job_' . $user_id, $scheduler_args );
				}
				if ( wp_next_scheduled( 'ced_ebay_import_products_job_' . $user_id, $scheduler_args ) ) {
					wp_clear_scheduled_hook( 'ced_ebay_import_products_job_' . $user_id, $scheduler_args );
				}
				if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_unschedule_all_actions' ) && function_exists( 'as_get_scheduled_actions' ) ) {
					if ( as_has_scheduled_action( null, null, 'ced_ebay_inventory_scheduler_group_' . $user_id . '>' . $site_id ) ) {
						as_unschedule_all_actions( null, null, 'ced_ebay_inventory_scheduler_group_' . $user_id . '>' . $site_id );
					}
					if ( as_has_scheduled_action( null, null, 'ced_ebay_bulk_upload_' . $user_id . '>' . $site_id ) ) {
						as_unschedule_all_actions( null, null, 'ced_ebay_bulk_upload_' . $user_id . '>' . $site_id );
					}
					if ( as_has_scheduled_action( 'ced_ebay_order_scheduler_job_' . $user_id, array( 'data' => $scheduler_args ) ) ) {
						as_unschedule_all_actions( 'ced_ebay_order_scheduler_job_' . $user_id, array( 'data' => $scheduler_args ) );
						as_unschedule_all_actions( 'ced_ebay_async_order_sync_action', array(), 'ced_ebay_async_order_sync_' . $user_id, array( 'data' => $scheduler_args ) );
					}
					if ( as_has_scheduled_action( 'ced_ebay_refresh_access_token_schedule', array( 'data' => array( 'user_id' => $user_id ) ) ) ) {
						as_unschedule_all_actions( 'ced_ebay_refresh_access_token_schedule', array( 'data' => array( 'user_id' => $user_id ) ) );
					}
					if ( as_has_scheduled_action( null, null, 'ced_ebay_sync_ended_listings_group_' . $user_id . '>' . $site_id ) ) {
						as_unschedule_all_actions( null, null, 'ced_ebay_sync_ended_listings_group_' . $user_id . '>' . $site_id );
					}
				}

				if ( empty( get_option( 'ced_ebay_connected_accounts' ) ) ) {
					delete_option( 'ced_ebay_active_user_url' );
				}
				wp_send_json_success(
					array(
						'status'  => 'success',
						'message' => 'Account Disconnected Successfully',
					)
				);
			}
		}
	}

	public function ced_ebay_fetch_oauth_access_code() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$user_id    = isset( $_POST['userid'] ) ? sanitize_text_field( $_POST['userid'] ) : '';
			$accessCode = isset( $_POST['accessCode'] ) ? wp_kses( $_POST['accessCode'], '' ) : '';
			if ( isset( $user_id ) && isset( $accessCode ) ) {

				$this->ced_ebay_save_user_access_token( $user_id, $accessCode, 'get_user_token' );
			}
		}
	}

	public function ced_ebay_refresh_access_token_schedule_action( $user_id_array ) {
		$user_id          = $user_id_array['user_id'];
		$access_token_arr = get_option( 'ced_ebay_user_access_token' );
		if ( ! empty( $access_token_arr ) ) {
			foreach ( $access_token_arr as $key => $value ) {
				$check_if_token_valid = ced_ebay_pre_flight_check( $key );
				if ( ! $check_if_token_valid ) {
					// check if the token has expired
					if ( ! empty( get_option( 'ced_ebay_pre_flight_check_response_' . $key ) ) ) {
						$api_response = get_option( 'ced_ebay_pre_flight_check_response_' . $key );
						if ( ! empty( $api_response ) && is_array( $api_response ) ) {
							if ( ! empty( $api_response['Errors'] ) && '931' == $api_response['Errors']['ErrorCode'] ) {
								delete_transient( 'ced_ebay_user_access_token_' . $key );
							}
						}
					}
				}
				$tokenValue = get_transient( 'ced_ebay_user_access_token_' . $key );
				if ( false === $tokenValue || null == $tokenValue || empty( $tokenValue ) ) {
					$user_refresh_token = $value['refresh_token'];
					$this->ced_ebay_save_user_access_token( $key, $user_refresh_token, 'refresh_user_token' );
				}
			}
		}
	}

	public function ced_ebay_save_user_access_token( $user_id, $accessCode, $action ) {
		$shop_data = ced_ebay_get_shop_data( $user_id );
		if ( ! empty( $shop_data ) ) {
			$siteID = $shop_data['site_id'];
			$token  = $shop_data['access_token'];
		}
		$oAuthFile        = CED_EBAY_DIRPATH . 'admin/ebay/lib/cedOAuthAuthorization.php';
		$renderDependency = $this->renderDependency( $oAuthFile );
		if ( $renderDependency ) {
			$cedAuthorization        = new Ced_Ebay_WooCommerce_Core\Ced_Ebay_OAuth_Authorization();
			$cedAuhorizationInstance = $cedAuthorization->get_instance();
			if ( 'get_user_token' == $action ) {
				$accessCodeResponse = $cedAuhorizationInstance->fetchOAuthUserAccessToken( $accessCode, $siteID, 'authorization_code' );
				$accessCodeResponse = json_decode( $accessCodeResponse );
				$responseArr        = get_option( 'ced_ebay_access_token_response' );
				if ( ! is_array( $responseArr ) ) {
					$responseArr = array();
				}
				$responseArr[ $user_id ] = $accessCodeResponse;
				update_option( 'ced_ebay_access_token_response', $responseArr );
				if ( ! empty( $accessCodeResponse->access_token ) ) {
					$accessToken  = $accessCodeResponse->access_token;
					$refreshToken = $accessCodeResponse->refresh_token;
					$tokenArr     = get_option( 'ced_ebay_user_access_token' );
					if ( ! is_array( $tokenArr ) ) {
						$tokenArr = array();
					}
					$tokenArr[ $user_id ] = array(
						'access_token'  => $accessToken,
						'refresh_token' => $refreshToken,
					);
					update_option( 'ced_ebay_user_access_token', $tokenArr );
					set_transient( 'ced_ebay_user_access_token_' . $user_id, $accessToken, 2 * HOUR_IN_SECONDS );

					echo 'Success';
					die;
				} else {
					echo 'Failed';
					die;
				}
			} elseif ( 'refresh_user_token' == $action ) {
				$accessCodeResponse = $cedAuhorizationInstance->fetchOAuthUserAccessToken( $accessCode, $siteID, 'refresh_token' );
				$accessCodeResponse = json_decode( $accessCodeResponse );
				$newTokenArr        = get_option( 'ced_ebay_user_access_token' );
				$newAccessToken     = $accessCodeResponse->access_token;
				if ( ! empty( $accessCodeResponse->error ) ) {
					wc_get_logger()->info( wc_print_r( $accessCodeResponse, true ), array( 'source' => 'ced_ebay_refresh_token' ) );
					return;
				}
				if ( ! is_array( $newTokenArr ) ) {
					$newTokenArr = array();
				}
				if ( isset( $newTokenArr[ $user_id ] ) ) {
					$newTokenArr[ $user_id ]['access_token'] = $newAccessToken;
					set_transient( 'ced_ebay_user_access_token_' . $user_id, $newAccessToken, 2 * HOUR_IN_SECONDS );
				}
				update_option( 'ced_ebay_access_token_response', $newTokenArr );
				update_option( 'ced_ebay_user_access_token', $newTokenArr );
				return;
			}
		}
	}

	public function ced_ebay_fulfill_order() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$order_id          = isset( $_POST['order_id'] ) ? sanitize_text_field( $_POST['order_id'] ) : '';
			$user_id           = get_post_meta( $order_id, 'ced_ebay_order_user_id', true );
			$purchase_order_id = get_post_meta( $order_id, '_ced_ebay_order_id', true );
			$tracking_number   = isset( $_POST['tracking_number'] ) ? sanitize_text_field( $_POST['tracking_number'] ) : '';
			$shipping_service  = isset( $_POST['shipping_service'] ) ? sanitize_text_field( $_POST['shipping_service'] ) : '';
			$shop_data         = ced_ebay_get_shop_data( $user_id );
			if ( ! empty( $shop_data ) ) {
				$siteID      = $shop_data['site_id'];
				$token       = $shop_data['access_token'];
				$getLocation = $shop_data['location'];
			}
			$fulfillmentRequestFile = CED_EBAY_DIRPATH . 'admin/ebay/lib/cedMarketingRequest.php';
			$renderDependency       = $this->renderDependency( $fulfillmentRequestFile );
			if ( $renderDependency ) {
				$fulfillmentRequest = new Ced_Marketing_API_Request( $siteID );
				$ebay_line_id_array = array();
				$get_order_detail   = $fulfillmentRequest->sendHttpRequestForFulfillmentAPI( '/' . $purchase_order_id, $token, '', '' );
				$get_order_detail   = json_decode( $get_order_detail, true );
				if ( ! empty( $get_order_detail['lineItems'] ) ) {
					foreach ( $get_order_detail['lineItems'] as $k2 => $line_item ) {
						$ebay_line_id_array[] = array(
							'lineItemId' => $line_item['lineItemId'],
							'quantity'   => $line_item['quantity'],
						);
					}
				} else {
					wp_send_json(
						array(
							'status'  => 'error',
							'message' => 'Failed to get Order Details.',
						)
					);
				}
				$payload              = array(
					'lineItems'           => $ebay_line_id_array,
					'shippingCarrierCode' => $shipping_service,
					'trackingNumber'      => $tracking_number,
				);
				$payload              = json_encode( $payload );
				$fulfillmentRequest   = new Ced_Marketing_API_Request( $siteID );
				$fulfill_order_status = $fulfillmentRequest->sendHttpRequestForFulfillmentAPI( '/' . $purchase_order_id . '/shipping_fulfillment', $token, 'POST_GET_HEADER_STATUS', $payload );
				if ( '201' == $fulfill_order_status ) {
					update_post_meta( $order_id, '_ebay_umb_order_status', 'OrderFulfilledManually' );
					wp_send_json(
						array(
							'status'  => 'success',
							'message' => 'Order Fulfilled Successfully.',
						)
					);
				}
			} else {
				wp_send_json(
					array(
						'status'  => 'error',
						'message' => 'There was some error in Fulfilling your eBay Order. Please contact support.',
					)
				);
			}
		}
	}

	public function ced_ebay_add_query_vars_filter( $vars ) {
		$vars[] = 'code';
		return $vars;
	}

	public function ced_ebay_onWpAdminInit() {
		$current_uri = home_url( add_query_arg( null, null ) );
		$path        = parse_url( $current_uri, PHP_URL_QUERY );
		$user_id     = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$channel     = isset( $_GET['channel'] ) ? sanitize_text_field( $_GET['channel'] ) : '';
		if ( '' !== $path && ! is_null( $path ) ) {
			$params = array();
			parse_str( $path, $params );
			$access_code             = isset( $params['code'] ) ? $params['code'] : '';
			$bigcom_parms            = isset( $params['bigcom'] ) ? $params['bigcom'] : '';
			$oauth_error_description = isset( $params['error_description'] ) ? $params['error_description'] : '';
			$site_id                 = str_replace( 'ced_woo_ebay_', '', $bigcom_parms );
			if ( '' !== $site_id && '' !== $access_code ) {
				echo '<h1>Please wait while we are redirecting you to complete Authentication!</h1>';
				$this->ced_ebay_fetch_user_data_using_access_token( $access_code, $site_id );
			}
		}
		if ( ! empty( $user_id ) ) {
			// Workaround for removing scheduled inventory sync action if instant sync webhook is on.
			// Since turning on instant stock sync webhook, for some reason, doesn't remove scheduled inventory sync action.
			if ( function_exists( 'as_has_scheduled_action' ) ) {
				if ( class_exists( 'WC_Webhook' ) ) {
					$get_webhook_id = ! empty( get_option( 'ced_ebay_prduct_update_webhook_id_' . $user_id, true ) ) ? get_option( 'ced_ebay_prduct_update_webhook_id_' . $user_id, true ) : false;
					if ( $get_webhook_id ) {
						$webhook        = new WC_Webhook( $get_webhook_id );
						$webhook_status = $webhook->get_status();
						if ( 'active' == $webhook_status ) {
							if ( as_has_scheduled_action( 'ced_ebay_inventory_scheduler_job_' . $user_id ) ) {
								$renderDataOnGlobalSettings = get_option( 'ced_ebay_global_settings', false );
								if ( ! empty( $renderDataOnGlobalSettings ) && is_array( $renderDataOnGlobalSettings ) ) {
									if ( ! empty( $renderDataOnGlobalSettings ) && is_array( $renderDataOnGlobalSettings ) ) {
										foreach ( $renderDataOnGlobalSettings as $key => $global_setting ) {
											if ( $user_id == $key ) {
												if ( '0' == $global_setting['ced_ebay_inventory_schedule_info'] ) {
													as_unschedule_all_actions( 'ced_ebay_inventory_scheduler_job_' . $user_id );
													break;
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
			if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_schedule_recurring_action' ) ) {
				if ( ! as_has_scheduled_action( 'ced_ebay_refresh_access_token_schedule', array( 'data' => array( 'user_id' => $user_id ) ) ) && 'ebay' == $channel ) {
					$isActionScheduled = as_schedule_recurring_action( time(), '7000', 'ced_ebay_refresh_access_token_schedule', array( 'data' => array( 'user_id' => $user_id ) ) );
				}
			}

			$access_token_arr = get_option( 'ced_ebay_user_access_token' );
			if ( ! empty( $access_token_arr ) ) {
				foreach ( $access_token_arr as $key => $value ) {
					$tokenValue = get_transient( 'ced_ebay_user_access_token_' . $key );
					if ( false === $tokenValue || null == $tokenValue || empty( $tokenValue ) ) {
						$user_refresh_token = $value['refresh_token'];
						$this->ced_ebay_save_user_access_token( $key, $user_refresh_token, 'refresh_user_token' );
					}
				}
			}
		}
	}

	public function ced_ebay_fetch_user_data_using_access_token( $access_code, $siteID ) {
		$oAuthFile        = CED_EBAY_DIRPATH . 'admin/ebay/lib/cedOAuthAuthorization.php';
		$renderDependency = $this->renderDependency( $oAuthFile );
		if ( $renderDependency ) {
			$cedAuthorization        = new Ced_Ebay_WooCommerce_Core\Ced_Ebay_OAuth_Authorization();
			$cedAuhorizationInstance = $cedAuthorization->get_instance();
			$accessCodeResponse      = $cedAuhorizationInstance->fetchOAuthUserAccessToken( $access_code, $siteID, 'authorization_code' );
			$accessCodeResponse      = json_decode( $accessCodeResponse );
			if ( ! empty( $accessCodeResponse->access_token ) ) {
				$file             = CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayAuthorization.php';
				$renderDependency = $this->renderDependency( $file );
				if ( $renderDependency ) {
					$cedAuthorization        = new Ced_Ebay_WooCommerce_Core\Ebayauthorization();
					$cedAuhorizationInstance = $cedAuthorization->get_instance();
					$userDetails             = $cedAuhorizationInstance->getUserData( $accessCodeResponse->access_token, $siteID );
					if ( empty( $userDetails ) ) {
						wp_redirect( get_admin_url() . 'admin.php?page=sales_channel&channel=ebay&section=setup-ebay&error=seller_store_data_error' );
					}
					$primaryEBaySite = '';
					require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayConfig.php';
					$ebayConfig         = new Ced_Ebay_WooCommerce_Core\Ebayconfig();
					$ebayConfigInstance = $ebayConfig->get_instance();
					$ebaySites          = $ebayConfigInstance->getEbaycountrDetail( $siteID );
					if ( is_array( $ebaySites ) && ! empty( $ebaySites ) ) {
						$listing_tld = isset( $ebaySites['tld'] ) && ! empty( $ebaySites['tld'] ) ? $ebaySites['tld'] : '.com';
						$user_id     = $userDetails['UserID'];
						if ( ! empty( $listing_tld ) && ! empty( $user_id ) ) {
							update_option( 'ced_ebay_listing_url_tld_' . $user_id . '>' . $siteID, $listing_tld );
						}
					}

					if ( '' == $user_id || '' == $siteID ) {
						update_option( 'ced_ebay_oauth_error_description', 'Failed to fetch eBay User ID or Site ID!' );
						wp_redirect( get_admin_url() . 'admin.php?page=sales_channel&channel=ebay&section=setup-ebay&error=oauth_error' );
						return;
					}

					$accessToken  = $accessCodeResponse->access_token;
					$refreshToken = $accessCodeResponse->refresh_token;

					$tokenArr = get_option( 'ced_ebay_user_access_token' );
					if ( ! is_array( $tokenArr ) ) {
						$tokenArr = array();
					}
					$tokenArr[ $user_id ] = array(
						'user_id'       => $user_id,
						'access_token'  => $accessToken,
						'refresh_token' => $refreshToken,
						'site_id'       => $siteID,
						'location'      => $userDetails['Site'],
						'seller_email'  => $userDetails['Email'],
						'eiastoken'     => $userDetails['EIASToken'],
						'seller_data'   => $userDetails,
					);

					$connected_ebay_accounts                                  = ! empty( get_option( 'ced_ebay_connected_accounts' ) ) ? get_option( 'ced_ebay_connected_accounts', true ) : array();
					$connected_ebay_accounts[ $user_id ][ $siteID ]['status'] = 'connected';
					if ( isset( $userDetails['Site'] ) && isset( $ebaySites['name'] ) && $userDetails['Site'] == $ebaySites['name'] ) {
						$connected_ebay_accounts[ $user_id ][ $siteID ]['is_primary_site'] = true;
					} else {
						$connected_ebay_accounts[ $user_id ][ $siteID ]['is_primary_site'] = false;
					}
					update_option( 'ced_ebay_connected_accounts', $connected_ebay_accounts );
					$file             = CED_EBAY_DIRPATH . 'admin/ebay/class-ebay.php';
					$renderDependency = $this->renderDependency( $file );
					if ( $renderDependency ) {
						$cedeBay           = new \Class_Ced_EBay_Manager();
						$cedebayInstance   = $cedeBay->get_instance();
						$seller_prefrences = $cedebayInstance->ced_ebay_get_seller_preferences( $user_id, $tokenArr[ $user_id ]['access_token'], $tokenArr[ $user_id ]['site_id'] );
						$ebay_tax_table    = $cedebayInstance->ced_ebay_get_tax_table( $user_id, $tokenArr[ $user_id ]['access_token'], $tokenArr[ $user_id ]['site_id'] );
					}
					update_option( 'ced_ebay_user_access_token', $tokenArr );
					set_transient( 'ced_ebay_user_access_token_' . $user_id, $accessToken, 2 * HOUR_IN_SECONDS );
					if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_schedule_recurring_action' ) ) {
						if ( ! as_has_scheduled_action( 'ced_ebay_refresh_access_token_schedule', array( 'data' => array( 'user_id' => $user_id ) ) ) ) {
							as_schedule_recurring_action( time(), '7000', 'ced_ebay_refresh_access_token_schedule', array( 'data' => array( 'user_id' => $user_id ) ) );
						}
					}
					wp_redirect( get_admin_url() . 'admin.php?page=sales_channel&channel=ebay&section=setup-ebay&user_id=' . $user_id . '&site_id=' . $siteID );

				}
			} elseif ( isset( $accessCodeResponse->error ) ) {
				update_option( 'ced_ebay_oauth_error_description', $accessCodeResponse->error_description );
				wp_redirect( get_admin_url() . 'admin.php?page=sales_channel&channel=ebay&section=setup-ebay&error=oauth_error' );
			}
		}
	}

	public function ced_ebay_add_woo_order_views( $views ) {
		if ( ! current_user_can( 'edit_others_pages' ) ) {
			return $views;
		}
		$class               = ( isset( $_REQUEST['order_from_ebay'] ) && 'yes' == sanitize_text_field( $_REQUEST['order_from_ebay'] ) ) ? 'current' : '';
		$query_string        = esc_url_raw( remove_query_arg( array( 'order_from_ebay' ) ) );
		$query_string        = add_query_arg( 'order_from_ebay', urlencode( 'yes' ), $query_string );
		$views['ebay_order'] = '<a href="' . $query_string . '" class="' . $class . '">' . __( 'eBay Order', 'ebay-integration-for-woocommerce' ) . '</a>';
		return $views;
	}

	public function ced_ebay_woo_admin_order_filter_query( $query ) {
		global $typenow, $wp_query, $wpdb;

		if ( 'shop_order' == $typenow ) {

			// filter by ebay status
			if ( ! empty( $_GET['order_from_ebay'] ) ) {

				if ( 'yes' == $_GET['order_from_ebay'] ) {

					$query->query_vars['meta_query'][] = array(
						'key'     => 'ced_ebay_order_user_id',
						'compare' => 'EXISTS',
					);
				}
			}
		}
	}



	public function ced_ebay_ajax_check_token_status() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( ! $check_ajax ) {
			wp_send_json_error(
				array(
					'message' => 'Nonce check failed!',
				)
			);
		}

		$user_id   = isset( $_POST['user_id'] ) ? sanitize_text_field( $_POST['user_id'] ) : '';
		$site_id   = isset( $_POST['site_id'] ) ? sanitize_text_field( $_POST['site_id'] ) : '';
		$shop_data = ced_ebay_get_shop_data( $user_id, $site_id );
		if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
			$siteID = $site_id;
			$token  = $shop_data['access_token'];
		} else {
			wp_send_json_error(
				array(
					'message' => 'Unable to verify eBay account',
				)
			);
		}

		$file             = CED_EBAY_DIRPATH . 'admin/ebay/class-ebay.php';
		$renderDependency = $this->renderDependency( $file );
		if ( $renderDependency ) {
			$cedeBay            = new Class_Ced_EBay_Manager();
			$cedebayInstance    = $cedeBay->get_instance();
			$check_token_status = $cedebayInstance->ced_ebay_check_token_status( $token, $site_id );
			// print_r($check_token_status);die('123')
			if ( isset( $check_token_status['Ack'] ) ) {
				$error = '';
				if ( 'Success' == $check_token_status['Ack'] ) {
					wp_send_json_success(
						array(
							'message' => 'Great! Your token is valid',
						)
					);
				} else {

					if ( isset( $check_token_status['Errors'][0] ) ) {
						foreach ( $check_token_status['Errors'] as $key => $value ) {
							if ( 'Error' == $value['SeverityCode'] ) {
								$error_data             = str_replace( array( '<', '>' ), array( '{', '}' ), $value['LongMessage'] );
												$error .= $error_data . '<br>';
							}
						}
					} else {
										$error_data = str_replace( array( '<', '>' ), array( '{', '}' ), $check_token_status['Errors']['LongMessage'] );
										$error     .= $error_data . '<br>';
					}
									wp_send_json_error(
										array(
											'message' => $error,
										)
									);
				}
			}
		}
	}


	public function ced_ebay_process_profile_bulk_action() {
		$check_ajax      = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		$sanitized_array = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
		$user_id         = isset( $_POST['user_id'] ) ? sanitize_text_field( $_POST['user_id'] ) : '';
		$site_id         = isset( $_POST['site_id'] ) ? sanitize_text_field( $_POST['site_id'] ) : '';
		if ( $check_ajax ) {

			$profile_ids               = isset( $sanitized_array['profile_ids'] ) ? $sanitized_array['profile_ids'] : '';
			$operation_to_be_performed = isset( $_POST['operation_to_be_performed'] ) ? sanitize_text_field( $_POST['operation_to_be_performed'] ) : false;
			if ( $operation_to_be_performed ) {
				if ( 'bulk-delete' == $operation_to_be_performed ) {
					$wp_folder     = wp_upload_dir();
					$wp_upload_dir = $wp_folder['basedir'];
					$wp_upload_dir = $wp_upload_dir . '/ced-ebay/category-specifics/' . $user_id . '/' . $site_id . '/';

					if ( is_array( $profile_ids ) && ! empty( $profile_ids ) ) {
						$profile_delete_error = false;
						global $wpdb;
						foreach ( $profile_ids as $index => $pid ) {
							$product_ids_assigned = get_option( 'ced_ebay_product_ids_in_profile_' . $pid, array() );
							foreach ( $product_ids_assigned as $index => $ppid ) {
								delete_post_meta( $ppid, 'ced_ebay_profile_assigned' . $user_id );
							}
							$ebay_category_id_array = $wpdb->get_results( $wpdb->prepare( "SELECT `profile_data` FROM {$wpdb->prefix}ced_ebay_profiles WHERE `id` = %s", $pid ), 'ARRAY_A' );
							$ebay_category_id_array = json_decode( $ebay_category_id_array[0]['profile_data'], true );
							if ( ! empty( $ebay_category_id_array['_umb_ebay_category']['default'] ) ) {
								$ebay_cat_id = $ebay_category_id_array['_umb_ebay_category']['default'];
								if ( ! empty( $ebay_cat_id ) && file_exists( $wp_upload_dir . 'ebaycat_' . $ebay_cat_id . '.json' ) ) {
									wp_delete_file( $wp_upload_dir . 'ebaycat_' . $ebay_cat_id . '.json' );
								}
							}
							$term_id = $wpdb->get_results( $wpdb->prepare( "SELECT `woo_categories` FROM {$wpdb->prefix}ced_ebay_profiles WHERE `id` = %s ", $pid ), 'ARRAY_A' );
							$term_id = json_decode( $term_id[0]['woo_categories'], true );
							foreach ( $term_id as $key => $value ) {
								delete_term_meta( $value, 'ced_ebay_profile_created_' . $user_id . '>' . $site_id );
								delete_term_meta( $value, 'ced_ebay_profile_id_' . $user_id . '>' . $site_id );
								delete_term_meta( $value, 'ced_ebay_mapped_category_' . $user_id . '>' . $site_id );
							}
						}
						foreach ( $profile_ids as $id ) {
							$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_profiles WHERE `id` IN (%s)", $id ) );
							if ( ! empty( $wpdb->last_error ) ) {
								$profile_delete_error = true;
							}
						}
						if ( false == $profile_delete_error ) {
							echo json_encode(
								array(
									'status'  => 'success',
									'message' => 'Profile successfully deleted!',
								)
							);
							die;
						} else {
							echo json_encode(
								array(
									'status'  => 'error',
									'message' => 'There was an error in deleting profile',
								)
							);
							die;
						}
					}
				}
			}
		}
	}



	public function ced_ebay_duplicate_product_exclude_meta( $metakeys = array() ) {
		$shop_data          = get_option( 'ced_ebay_user_access_token' );
		$connected_accounts = get_option( 'ced_ebay_connected_accounts' );
		if ( ! empty( $shop_data ) ) {
			foreach ( $shop_data as $key => $value ) {
				if ( ! empty( $key ) && isset( $connected_accounts[ $key ] ) ) {
					foreach ( $connected_accounts[ $key ] as $ebay_site => $ebay_account ) {
						if ( isset( $connected_accounts[ $key ][ $ebay_site ] ) ) {
							$metakeys[] = '_ced_ebay_listing_id_' . $key . '>' . $ebay_site;
						}
					}
				}
			}
		}
		return $metakeys;
	}

	public function ced_ebay_sync_seller_event() {
		$this->ced_ebay_onWpAdminInit();
		$logger             = wc_get_logger();
		$context            = array( 'source' => 'ced_ebay_sync_seller_event' );
		$fetchCurrentAction = current_action();
		if ( strpos( $fetchCurrentAction, 'wp_ajax_nopriv_' ) !== false ) {
			$user_id = isset( $_GET['user_id'] ) ? wc_clean( $_GET['user_id'] ) : false;
		} else {
			$user_id = str_replace( 'ced_ebay_sync_seller_event_job_', '', $fetchCurrentAction );
		}
		$shop_data = ced_ebay_get_shop_data( $user_id );
		if ( ! empty( $shop_data ) ) {
			$siteID      = $shop_data['site_id'];
			$token       = $shop_data['access_token'];
			$getLocation = $shop_data['location'];
		}
		$file             = CED_EBAY_DIRPATH . 'admin/ebay/class-ebay.php';
		$renderDependency = $this->renderDependency( $file );
		if ( $renderDependency ) {
			$cedeBay         = new Class_Ced_EBay_Manager();
			$cedebayInstance = $cedeBay->get_instance();
			$page_number     = ! empty( get_option( 'ced_ebay_seller_event_pagination_' . $user_id ) ) ? get_option( 'ced_ebay_seller_event_pagination_' . $user_id, true ) : 1;
			$result          = $cedebayInstance->ced_ebay_get_seller_events( $token, $siteID, $page_number );
			if ( 'api-error' != $result && 'request-file-not-found' != $result ) {
				$logger->info( wc_print_r( 'Page Number ' . $page_number, true ), $context );
				++$page_number;
				update_option( 'ced_ebay_seller_event_pagination_' . $user_id, $page_number );
				$logger->info( 'Getting Data from api', $context );

				if ( ! empty( $result['ItemArray']['Item'] ) ) {
					$logger->info( 'Getting Item array in Data', $context );
					if ( ! isset( $result['ItemArray']['Item'][0] ) ) {
						$temp_item_list = array();
						$temp_item_list = $result['ItemArray']['Item'];
						unset( $result['ItemArray']['Item'] );
						$result['ItemArray']['Item'][] = $temp_item_list;
					}

					foreach ( $result['ItemArray']['Item'] as $key => $value ) {
						$ID = false;
						if ( isset( $value['ItemID'] ) && ! empty( $value['ItemID'] ) ) {
							$logger->info( 'Item ID - ' . wc_print_r( $value['ItemID'], true ), $context );
							$store_products = get_posts(
								array(
									'numberposts'  => -1,
									'post_type'    => 'product',
									'meta_key'     => '_ced_ebay_listing_id_' . $user_id . '>' . $siteID,
									'meta_value'   => $value['ItemID'],
									'meta_compare' => '=',
								)
							);
							$localItemID    = wp_list_pluck( $store_products, 'ID' );
							if ( ! empty( $localItemID ) ) {
								$ID = $localItemID[0];
							}

							if ( empty( $ID ) ) {
								$logger->info( 'Product Not found on Woo', $context );
								continue;
							} else {
								$logger->info( 'Found woo product in woo - ' . wc_print_r( $ID, true ), $context );
								$product = wc_get_product( $ID );
								update_post_meta( $ID, 'ced_ebay_stock_ebay_to_woo_running', 'Running' );

								if ( $product->is_type( 'simple' ) && empty( $value['Variations'] ) ) {
									if ( ( $value['Quantity'] - $value['SellingStatus']['QuantitySold'] ) > 0 ) {
										$available_quantity = $value['Quantity'] - $value['SellingStatus']['QuantitySold'];
										$logger->info( 'WOO quantity ' . wc_print_r( $product->get_stock_quantity(), true ) . '| EBAY quantity ' . wc_print_r( $value['Quantity'], true ) . '| quantity sold ' . wc_print_r( $value['SellingStatus']['QuantitySold'], true ) . ' | available quantity ' . wc_print_r( $available_quantity, true ), $context );
										$current_woo_quantity = $product->get_stock_quantity();
										if ( ! empty( $current_woo_quantity ) ) {
											if ( $current_woo_quantity == $available_quantity ) {
												$logger->info( 'Woo quantity and ebay quantity is same', $context );
												delete_post_meta( $ID, 'ced_ebay_stock_ebay_to_woo_running' );
												continue;
											}
										}
										$logger->info( 'Updating quantity', $context );
										$product->set_manage_stock( true );
										$product->set_stock_quantity( $value['Quantity'] - $value['SellingStatus']['QuantitySold'] );
										$product->set_stock_status( 'instock' );
										$product->save();
									} else {
										$logger->info( 'ebay quantity is zero Product out of stock', $context );
										$product->set_stock_quantity( 0 );
										$product->set_stock_status( 'outofstock' );
										$product->save();
									}
								} elseif ( $product->is_type( 'variable' ) ) {
									$logger->info( wc_print_r( 'Variable Product', true ), $context );
									if ( ! isset( $value['Variations']['Variation'][0] ) ) {
										$temp_array = array();
										$temp_array = $value['Variations']['Variation'];
										unset( $value['Variations']['Variation'] );
										$value['Variations']['Variation'][] = $temp_array;

									}
									if ( isset( $value['Variations']['Variation'][0] ) && ! empty( $value['Variations']['Variation'][0] ) ) {
										$product_variations = $value['Variations']['Variation'];
										foreach ( $product_variations as $key => $variation ) {
											$variation_sku = $variation['SKU'];
											$logger->info( wc_print_r( 'Variation SKU -> ' . $variation_sku, true ), $context );

											if ( $variation_sku ) {
												$variation_prod_id = wc_get_product_id_by_sku( $variation_sku );
												$logger->info( 'Variation_id ' . wc_print_r( $variation_prod_id, true ), $context );
												if ( $variation_prod_id ) {
													$var_product = wc_get_product( $variation_prod_id );
												} else {
													continue;
												}
											} else {
												continue;
											}
											$logger->info( 'Quantity - ' . wc_print_r( $variation['Quantity'] - $variation['SellingStatus']['QuantitySold'], true ), $context );
											if ( $variation['Quantity'] - $variation['SellingStatus']['QuantitySold'] > 0 ) {
												if ( ! is_wp_error( $var_product ) ) {
													$var_product->set_stock_quantity( $variation['Quantity'] - $variation['SellingStatus']['QuantitySold'] );
													$var_product->save();
												} else {
													$logger->info( 'Error while fetching Variation product', $context );
												}
											} else {
												$var_product->set_stock_quantity( 0 );
												$var_product->save();
											}
										}
									} else {
										$logger->info( 'SIngle variation', $context );
									}
								}
							}
						}
					}
				} else {
					update_option( 'ced_ebay_seller_event_pagination_' . $user_id, 1 );
					$logger->info( 'Item array is empty', $context );
					return;
				}
			} else {
				update_option( 'ced_ebay_seller_event_pagination_' . $user_id, 1 );
				$logger->info( 'Error in api call', $context );
				return;
			}
		}
	}


	public function ced_ebay_existing_products_sync_manager( $user_id = '', $site_id = '' ) {
		$logger = wc_get_logger();
		$this->ced_ebay_onWpAdminInit();
		$context            = array( 'source' => 'ebay-existing-products-sync' );
		$fetchCurrentAction = current_action();
		if ( strpos( $fetchCurrentAction, 'wp_ajax_nopriv_' ) !== false ) {
			$user_id = isset( $_GET['user_id'] ) ? wc_clean( $_GET['user_id'] ) : '';
			$site_id = isset( $_GET['site_id'] ) ? wc_clean( $_GET['site_id'] ) : '';
		}

		$ced_ebay_manager = $this->ced_ebay_manager;
		$shop_data        = ced_ebay_get_shop_data( $user_id, $site_id );
		if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
			$siteID = $site_id;
			$token  = $shop_data['access_token'];
		} else {
			return false;
		}
		$pre_flight_check = ced_ebay_pre_flight_check( $user_id );
		if ( ! $pre_flight_check ) {
			return;
		}
		$page = ! empty( get_option( 'ced_ebay_get_products_page_' . $user_id . '>' . $siteID ) ) ? get_option( 'ced_ebay_get_products_page_' . $user_id . '>' . $siteID ) : 1;
		if ( empty( $page ) ) {
			$page = 1;
		}
		$access_token_arr = get_option( 'ced_ebay_user_access_token' );
		if ( ! empty( $access_token_arr ) ) {
			foreach ( $access_token_arr as $key => $value ) {
				$tokenValue = get_transient( 'ced_ebay_user_access_token_' . $key );
				if ( false === $tokenValue || null == $tokenValue || empty( $tokenValue ) ) {
					$user_refresh_token = $value['refresh_token'];
					$this->ced_ebay_save_user_access_token( $key, $user_refresh_token, 'refresh_user_token' );
				}
			}
		}
		require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
		$count              = 0;
		$PageNumber         = $page;
		$length             = 200;
		$mainXml            = '
		<?xml version="1.0" encoding="utf-8"?>
		<GetMyeBaySellingRequest xmlns="urn:ebay:apis:eBLBaseComponents">
		<RequesterCredentials>
		<eBayAuthToken>' . $token . '</eBayAuthToken>
		</RequesterCredentials>
		<ActiveList>
		<Sort>TimeLeft</Sort>
		<Pagination>
		<EntriesPerPage>' . $length . '</EntriesPerPage>
		<PageNumber>' . $PageNumber . '</PageNumber>
		</Pagination>
		</ActiveList>`
		</GetMyeBaySellingRequest>';
		$ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
		$activelist         = $ebayUploadInstance->get_active_products( $mainXml );
		$logger->info( wc_print_r( '>>>>>> Page Number ' . $PageNumber . '/' . $activelist['ActiveList']['PaginationResult']['TotalNumberOfPages'] . ' <<<<<<<<', true ), $context );
		if ( ! empty( $activelist['ActiveList']['PaginationResult']['TotalNumberOfPages'] ) ) {
			if ( $PageNumber > $activelist['ActiveList']['PaginationResult']['TotalNumberOfPages'] ) {
				$logger->info( 'Reached end of list. Resetting page.', $context );
				update_option( 'ced_ebay_get_products_page_' . $user_id . '>' . $siteID, 1 );
				return;
			}
		}

		if ( isset( $activelist['ActiveList']['ItemArray']['Item'] ) && ! empty( $activelist['ActiveList']['ItemArray']['Item'] ) ) {
			if ( ! empty( $activelist['ActiveList']['ItemArray']['Item'] ) && ! isset( $activelist['ActiveList']['ItemArray']['Item'][0] ) ) {
				$temp = $activelist['ActiveList']['ItemArray']['Item'];
				unset( $activelist['ActiveList']['ItemArray']['Item'] );
				$activelist['ActiveList']['ItemArray']['Item'][] = $temp;
			}
			foreach ( $activelist['ActiveList']['ItemArray']['Item'] as $item_value ) {
				$ItemID = $item_value['ItemID'];
				if ( isset( $item_value['Variations']['Variation'] ) && ! empty( $item_value['Variations']['Variation'] ) ) {
					if ( ! isset( $item_value['Variations']['Variation'][0] ) ) {
						$temp = $item_value['Variations']['Variation'];
						unset( $item_value['Variations']['Variation'] );
						$item_value['Variations']['Variation'][] = $temp;
					}
					foreach ( $item_value['Variations']['Variation'] as $key => $value ) {
						$eby_sku_var = ! empty( $value['SKU'] ) ? $value['SKU'] : '';
						if ( isset( $eby_sku_var ) && ! empty( $eby_sku_var ) ) {
							$args           = array(
								'post_type'  => 'product_variation',
								'meta_query' => array(
									array(
										'key'   => '_sku',
										'value' => $eby_sku_var,
									),
								),
							);
							$variation_post = get_posts( $args );
							if ( ! empty( $variation_post ) ) {
								$variation_post_id = $variation_post[0]->ID;
								$logger->info( wc_print_r( $eby_sku_var, true ) . ' (eBay SKU) ->  ' . wc_print_r( $ItemID, true ) . ' (eBay Item ID) -> ' . wc_print_r( $variation_post_id, true ) . ' (Woo Variation ID) ', $context );
								if ( isset( $variation_post_id ) && ! empty( $variation_post_id ) ) {
									update_post_meta( $variation_post_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, $ItemID );
									$var_product = wc_get_product( $variation_post_id );

									$parent_id = $var_product->get_parent_id();
									if ( isset( $parent_id ) && ! empty( $parent_id ) ) {
										update_post_meta( $parent_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, $ItemID );
										update_post_meta( $parent_id, 'ced_ebay_synced_by_user_id', $user_id );

									}
								}
							}
						}
					}
				} else {
					$eby_sku_simple = ! empty( $item_value['SKU'] ) ? $item_value['SKU'] : '';
					if ( isset( $eby_sku_simple ) && ! empty( $eby_sku_simple ) ) {
						$args        = array(
							'post_type'    => 'product',
							'post_status'  => 'publish',
							'numberposts'  => -1,
							'meta_key'     => '_sku',
							'meta_value'   => $eby_sku_simple,
							'meta_compare' => '=',
						);
						$woo_product = get_posts( $args );
						$woo_product = wp_list_pluck( $woo_product, 'ID' );
						if ( ! empty( $woo_product ) ) {
							$simple_post_id = $woo_product[0];
						} else {
							continue;
						}
						if ( ! empty( $simple_post_id ) ) {
							$logger->info( wc_print_r( $eby_sku_simple, true ) . ' (eBay SKU) ->  ' . wc_print_r( $ItemID, true ) . ' (eBay Item ID) -> ' . wc_print_r( $simple_post_id, true ) . ' (Woo Product ID) ', $context );
							update_post_meta( $simple_post_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, $ItemID );
							update_post_meta( $simple_post_id, 'ced_ebay_synced_by_user_id', $user_id );

						}
					}
				}
			}
			++$page;
			update_option( 'ced_ebay_get_products_page_' . $user_id . '>' . $siteID, $page );
		} else {
			$page = 0;
			update_option( 'ced_ebay_get_products_page_' . $user_id . '>' . $siteID, $page );
		}
	}
	public function ced_ebay_modify_product_data_for_upload() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$product_id           = isset( $_POST['productId'] ) ? sanitize_text_field( $_POST['productId'] ) : '';
			$product_title        = isset( $_POST['prodTitle'] ) ? sanitize_text_field( $_POST['prodTitle'] ) : '';
			$store_category_value = isset( $_POST['store_category_value'] ) ? sanitize_text_field( $_POST['store_category_value'] ) : '';
			$store_category_name  = isset( $_POST['store_category_name'] ) ? sanitize_text_field( $_POST['store_category_name'] ) : '';
			$user_id              = isset( $_POST['user_id'] ) ? sanitize_text_field( $_POST['user_id'] ) : '';
			if ( empty( $product_id ) ) {
				echo json_encode(
					array(
						'status'  => 'error',
						'message' => 'Failed to fetch product!',
					)
				);
				die;
			}
			if ( empty( $product_title ) ) {
				echo json_encode(
					array(
						'status'  => 'error',
						'message' => 'Please enter a title.',
					)
				);
				die;
			}
			$add_new_title = update_post_meta( $product_id, 'ced_ebay_alt_prod_title_' . $product_id . '_' . $user_id, $product_title );
			if ( $add_new_title ) {
				echo json_encode(
					array(
						'status'  => 'success',
						'message' => 'Title Modifed. Please close the popup and proceed to upload the product.',
					)
				);
				die;
			}

			$add_store_category_value = update_post_meta( $product_id, 'ced_ebay_prod_store_cat_value_' . $product_id . '_' . $user_id, $store_category_value );
			$add_store_category_name  = update_post_meta( $product_id, 'ced_ebay_prod_store_cat_name_' . $product_id . '_' . $user_id, $store_category_name );

		}
	}

	public function ced_ebay_get_modifed_product_details() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$product_id = isset( $_POST['productId'] ) ? sanitize_text_field( $_POST['productId'] ) : '';
			$user_id    = isset( $_POST['user_id'] ) ? sanitize_text_field( $_POST['user_id'] ) : '';
			if ( empty( $product_id ) ) {
				echo json_encode(
					array(
						'status'  => 'error',
						'message' => 'Failed to fetch product!',
					)
				);
				die;
			}
			$get_alt_title = get_post_meta( $product_id, 'ced_ebay_alt_prod_title_' . $product_id . '_' . $user_id, true );
			if ( $get_alt_title && ! empty( $get_alt_title ) ) {
				echo json_encode(
					array(
						'status' => 'success',
						'data'   => $get_alt_title,
					)
				);
				die;
			}
		}
	}


	public function renderDependency( $file ) {
		if ( null != $file || '' != $file ) {
			require_once "$file";
			return true;
		}
		return false;
	}
	public function loadDependency() {
		require_once CED_EBAY_DIRPATH . 'admin/ebay/class-ebay.php';
		$this->ced_ebay_manager = Class_Ced_EBay_Manager::get_instance();

		require_once ABSPATH . '/wp-admin/includes/file.php';
	}


	public function ced_ebay_add_custom_item_aspects_row() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( ! $check_ajax ) {
			return;
		}

		$user_id = isset( $_POST['user_id'] ) ? sanitize_text_field( $_POST['user_id'] ) : '';
		$title   = isset( $_POST['title'] ) ? trim( sanitize_text_field( $_POST['title'] ) ) : '';
		$section = 'profile-edit';
		?>
		<tr class="form-field _umb_brand_field" attr=""> 
			<input type="hidden"  >
			<td> <label> <?php echo esc_attr( $title ); ?></label> </td>
			<td>
				<input class="short" name="custom_item_aspects[<?php echo esc_attr( str_replace( ' ', '+', $title ) ); ?>][default]" id="" value="" >
				<span style="padding-left:10px;font-size:18px;color:#5850ec;"><b>Or</b></span>
			</td>
			<td>
				<?php
				$this->ced_ebay_profile_dropdown( 'custom_item_aspects[' . str_replace( ' ', '+', $title ) . '][metakey]', '', array(), array(), $section );

				?>
				<i class="fa fa-times ced_ebay_remove_custom_row" aria-hidden="true"></i>
			</td>

		</tr>

		<?php

		die;
	}
	public function ced_ebay_get_category_item_aspects() {

		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( ! $check_ajax ) {
			return;
		}
		$profile_category_id = isset( $_POST['category_id'] ) ? sanitize_text_field( $_POST['category_id'] ) : '';
		$user_id             = isset( $_POST['userid'] ) ? sanitize_text_field( $_POST['userid'] ) : '';
		$site_id             = isset( $_POST['site_id'] ) ? sanitize_text_field( $_POST['site_id'] ) : '';

		$wp_folder     = wp_upload_dir();
		$wp_upload_dir = $wp_folder['basedir'];
		$wp_upload_dir = $wp_upload_dir . '/ced-ebay/category-specifics/' . $user_id . '/' . $site_id . '/';
		if ( ! is_dir( $wp_upload_dir ) ) {
			wp_mkdir_p( $wp_upload_dir, 0777 );
		}

		$shop_data = ced_ebay_get_shop_data( $user_id, $site_id );
		if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
			$token = $shop_data['access_token'];
		}

		$fileCategory = CED_EBAY_DIRPATH . 'admin/ebay/lib/cedGetcategories.php';
		$fileFields   = CED_EBAY_DIRPATH . 'admin/partials/products_fields.php';

		if ( file_exists( $fileCategory ) ) {
			require_once $fileCategory;
		}

		if ( file_exists( $fileFields ) ) {
			require_once $fileFields;
		}

		$cat_specifics_file = $wp_upload_dir . 'ebaycat_' . $profile_category_id . '.json';

		if ( file_exists( $cat_specifics_file ) ) {
			$available_attribute = json_decode( file_get_contents( $cat_specifics_file ), true );
		} else {
			$available_attribute = array();
		}
		if ( ! empty( $available_attribute ) ) {
			$categoryAttributes = $available_attribute;
		} else {
			$ebayCategoryInstance    = CedGetCategories::get_instance( $site_id, $token );
			$categoryAttributes      = $ebayCategoryInstance->_getCatSpecifics( $profile_category_id );
			$categoryAttributes_json = json_encode( $categoryAttributes );
			$cat_specifics_file      = $wp_upload_dir . 'ebaycat_' . $profile_category_id . '.json';
			if ( file_exists( $cat_specifics_file ) ) {
				wp_delete_file( $cat_specifics_file );
			}
			file_put_contents( $cat_specifics_file, $categoryAttributes_json );
		}

		$ebayCategoryInstance = CedGetCategories::get_instance( $site_id, $token );
		$limit                = array( 'ConditionEnabled', 'ConditionValues' );
		$getCatFeatures       = $ebayCategoryInstance->_getCatFeatures( $profile_category_id, array() );
		$getCatFeatures_json  = json_encode( $getCatFeatures );
		$cat_features_file    = $wp_upload_dir . 'ebaycatfeatures_' . $profile_category_id . '.json';
		// $getCatFeatures = file_get_contents( $cat_features_file );
		// $getCatFeatures = json_decode($getCatFeatures, true);
		if ( file_exists( $cat_features_file ) ) {
			wp_delete_file( $cat_features_file );
		}
		file_put_contents( $cat_features_file, $getCatFeatures_json );
		$getCatFeatures = isset( $getCatFeatures['Category'] ) ? $getCatFeatures['Category'] : false;

		$productFieldInstance = CedeBayProductsFields::get_instance();
		$addedMetaKeys        = get_option( 'CedUmbProfileSelectedMetaKeys', false );
		$selectDropdownHTML   = '';

		global $wpdb;
		$results = $wpdb->get_results( "SELECT DISTINCT meta_key FROM {$wpdb->prefix}postmeta WHERE meta_key NOT LIKE '%wcf%' AND meta_key NOT LIKE '%elementor%' AND meta_key NOT LIKE '%_menu%'", 'ARRAY_A' );
		foreach ( $results as $key => $meta_key ) {
			$post_meta_keys[] = $meta_key['meta_key'];
		}
		$custom_prd_attrb = array();
		$query            = $wpdb->get_results( $wpdb->prepare( "SELECT `meta_value` FROM  {$wpdb->prefix}postmeta WHERE `meta_key` LIKE %s", '_product_attributes' ), 'ARRAY_A' );
		if ( ! empty( $query ) ) {
			foreach ( $query as $key => $db_attribute_pair ) {
				foreach ( maybe_unserialize( $db_attribute_pair['meta_value'] ) as $key => $attribute_pair ) {
					if ( 1 != $attribute_pair['is_taxonomy'] ) {
						$custom_prd_attrb[] = $attribute_pair['name'];
					}
				}
			}
		}
		if ( $addedMetaKeys && count( $addedMetaKeys ) > 0 ) {
			foreach ( $addedMetaKeys as $metaKey ) {
				$attrOptions[ $metaKey ] = $metaKey;
			}
		}
		$attributes = wc_get_attribute_taxonomies();
		if ( ! empty( $attributes ) ) {
			foreach ( $attributes as $attributesObject ) {
				$attrOptions[ 'umb_pattr_' . $attributesObject->attribute_name ] = $attributesObject->attribute_label;
			}
		}

		$global_options = ! empty( get_option( 'ced_ebay_global_options' ) ) ? get_option( 'ced_ebay_global_options', true ) : array();

		/* select dropdown setup */
		ob_start();
		$fieldID             = '{{*fieldID}}';
		$selectId            = $fieldID . '_attibuteMeta';
		$selectDropdownHTML .= '<select class="ced_ebay_search_item_sepcifics_mapping" id="' . $selectId . '" name="' . $selectId . '">';
		$selectDropdownHTML .= '<option value="-1">Select</option>';
		$selectDropdownHTML .= '<option value="ced_product_tags">Product Tags</option>';
		$selectDropdownHTML .= '<option value="ced_product_cat_single">Product Category - Last Category</option>';
		$selectDropdownHTML .= '<option value="ced_product_cat_hierarchy">Product Category - Hierarchy</option>';

		if ( class_exists( 'ACF' ) ) {
			$acf_fields_posts = get_posts(
				array(
					'posts_per_page' => -1,
					'post_type'      => 'acf-field',
				)
			);

			foreach ( $acf_fields_posts as $key => $acf_posts ) {
				$acf_fields[ $key ]['field_name'] = $acf_posts->post_title;
				$acf_fields[ $key ]['field_key']  = $acf_posts->post_name;
			}
		}
		if ( is_array( $attrOptions ) && ! empty( $attrOptions ) ) {
			$selectDropdownHTML .= '<optgroup label="Global Attributes">';
			foreach ( $attrOptions as $attrKey => $attrName ) :
				$selectDropdownHTML .= '<option value="' . $attrKey . '">' . $attrName . '</option>';
			endforeach;
		}

		if ( ! empty( $custom_prd_attrb ) ) {
			$custom_prd_attrb    = array_unique( $custom_prd_attrb );
			$selectDropdownHTML .= '<optgroup label="Custom Attributes">';
			foreach ( $custom_prd_attrb as $key => $custom_attrb ) {
				$selectDropdownHTML .= '<option value="ced_cstm_attrb_' . esc_attr( $custom_attrb ) . '">' . esc_html( $custom_attrb ) . '</option>';
			}
		}

		if ( ! empty( $post_meta_keys ) ) {
			$post_meta_keys      = array_unique( $post_meta_keys );
			$selectDropdownHTML .= '<optgroup label="Custom Fields">';
			foreach ( $post_meta_keys as $key => $p_meta_key ) {
				$selectDropdownHTML .= '<option value="' . $p_meta_key . '">' . $p_meta_key . '</option>';
			}
		}

		if ( ! empty( $acf_fields ) ) {
			$selectDropdownHTML .= '<optgroup label="ACF Fields">';
			foreach ( $acf_fields as $key => $acf_field ) :
				$selectDropdownHTML .= '<option value="acf_' . $acf_field['field_key'] . '">' . $acf_field['field_name'] . '</option>';
			endforeach;
		}
		$selectDropdownHTML .= '</select>';

		if ( ! empty( $categoryAttributes ) ) {
			if ( ! empty( $profile_data ) ) {
				$data = json_decode( $profile_data['profile_data'], true );
			}
			$requiredInAnyCase = array( '_umb_id_type', '_umb_id_val', '_umb_brand' );
			global $global_CED_ebay_Render_Attributes;
			$marketPlace        = 'ced_ebay_required_common';
			$productID          = 0;
			$categoryID         = '';
			$indexToUse         = 0;
			$selectDropdownHTML = $selectDropdownHTML;

			?>
			<div class="components-card is-size-medium woocommerce-table pinterest-for-woocommerce-landing-page__faq-section css-1xs3c37-CardUI e1q7k77g0">
				<div class="components-panel">
					<div class="wc-progress-form-content woocommerce-importer">

						<div class="ced-faq-wrapper ced-margin-border">
							<input class="ced-faq-trigger" id="ced-faq-wrapper-one" type="checkbox" checked /><label class="ced-faq-title" for="ced-faq-wrapper-one"> <?php echo esc_attr_e( 'Template Fields', 'amazon-for-woocommerce' ); ?></label>
							<div class="ced-faq-content-wrap">
								<div class="ced-faq-content-holder">
									<table class = "wp-list-table widefat fixed table-view-list ced-table-filed form-table" >
										<tbody class="ced_template_required_attributes"> 
											<tr>
												<th colspan="3" ><h3 class="mb-3 text-primary">
													<?php esc_attr_e( 'GENERAL DETAILS', 'ebay-integration-for-woocommerce' ); ?></h3>
												</th>
											</tr>	

											<!-- <tr class=""> -->
												<?php
												$requiredInAnyCase = array( '_umb_id_type', '_umb_id_val', '_umb_brand' );
												global $global_CED_ebay_Render_Attributes;
												$marketPlace          = 'ced_ebay_required_common';
												$productID            = 0;
												$categoryID           = '';
												$indexToUse           = 0;
												$selectDropdownHTML   = $selectDropdownHTML;
												$productFieldInstance = CedeBayProductsFields::get_instance();
												$fields               = $productFieldInstance->ced_ebay_get_custom_products_fields( $user_id, $profile_category_id, $site_id );
												if ( ! empty( $profile_data ) ) {
													$data = json_decode( $profile_data['profile_data'], true );
												}
												foreach ( $fields as $value ) {
													$isText   = false;
													$field_id = isset( $value['fields']['id'] ) ? trim( $value['fields']['id'], '_' ) : '';
													if ( isset( $value['fields']['id'] ) && in_array( $value['fields']['id'], $requiredInAnyCase ) ) {
														$attributeNameToRender  = ucfirst( $value['fields']['label'] );
														$attributeNameToRender .= '<span class="ced_ebay_wal_required">' . __( '[ Required ]', 'ebay-integration-for-woocommerce' ) . '</span>';
													} else {
														$attributeNameToRender = isset( $value['fields']['label'] ) ? ucfirst( $value['fields']['label'] ) : '';
													}
													$default           = isset( $value['fields']['id'] ) && isset( $data[ $value['fields']['id'] ]['default'] ) ? $data[ $value['fields']['id'] ]['default'] : '';
													$field_description = isset( $value['fields']['description'] ) ? $value['fields']['description'] : '';
													echo '<tr class="form-field _umb_id_type_field ">';

													if ( isset( $value['type'] ) && '_select' == $value['type'] ) {
														$valueForDropdown = $value['fields']['options'];
														if ( '_umb_id_type' == $value['fields']['id'] ) {
															unset( $valueForDropdown['null'] );
														}
														$productFieldInstance->renderDropdownHTML(
															$user_id,
															$site_id,
															$field_id,
															$attributeNameToRender,
															$valueForDropdown,
															$categoryID,
															$productID,
															$marketPlace,
															$indexToUse,
															array(
																'case'  => 'profile',
																'value' => $default,
															),
															'',
															'SINGLE',
															$field_description
														);
														$isText = false;
													} elseif ( isset( $value['type'] ) && '_text_input' == $value['type'] ) {
														$productFieldInstance->renderInputTextHTML(
															$user_id,
															$site_id,
															$field_id,
															$attributeNameToRender,
															$categoryID,
															$productID,
															$marketPlace,
															$indexToUse,
															array(
																'case'  => 'profile',
																'value' => $default,
															),
															false,
															'SINGLE',
															$field_description
														);
														$isText = true;
													} elseif ( isset( $value['type'] ) && '_hidden' == $value['type'] ) {
														$productFieldInstance->renderInputTextHTMLhidden(
															$user_id,
															$site_id,
															$field_id,
															$attributeNameToRender,
															$categoryID,
															$productID,
															$marketPlace,
															$indexToUse,
															array(
																'case'  => 'profile',
																'value' => $profile_category_id,
															),
															false,
															$field_description
														);
														$isText = false;
													} else {
														$isText = false;
													}

													echo '<td>';
													if ( $isText ) {
														$previousSelectedValue = 'null';
														if ( isset( $data[ $value['fields']['id'] ]['metakey'] ) && 'null' != $data[ $value['fields']['id'] ]['metakey'] ) {
															$previousSelectedValue = $data[ $value['fields']['id'] ]['metakey'];
														}
														$updatedDropdownHTML = str_replace( '{{*fieldID}}', $value['fields']['id'], $selectDropdownHTML );
														$updatedDropdownHTML = str_replace( 'value="' . $previousSelectedValue . '"', 'value="' . $previousSelectedValue . '" selected="selected"', $updatedDropdownHTML );
														print_r( $updatedDropdownHTML );
													}
													echo '</td>';
													echo '</tr>';
												}
												?>


												<tr>

													<th colspan="3" ><h3 class="mb-3 text-primary">
														<?php esc_attr_e( 'ITEM ASPECTS', 'ebay-integration-for-woocommerce' ); ?></h3>
														<p class="text-secondary">Specify additional details about products listed on eBay based on the selected eBay Category.
															You can only set the values of <span style="color:red;">[Required]</span> Item Aspects and leave other fields in this section.
														To do so, either enter a custom value or get the values from Product Attributes or Custom Fields.</p>
													</th>
												</tr>	

												<?php
												if ( ! empty( $categoryAttributes ) ) {
													if ( ! empty( $profile_data ) ) {
														$data = json_decode( $profile_data['profile_data'], true );
													}
													foreach ( $categoryAttributes as $key1 => $value ) {
														$isText   = true;
														$field_id = trim( urlencode( $value['localizedAspectName'] ) );
														if ( isset( $global_options[ $user_id ][ $site_id ][ $field_id ]['custom_value'] ) ) {
															$global_value = $global_options[ $user_id ][ $site_id ][ $field_id ]['custom_value'];
														} else {
															$global_value = '';
														}
														$default  = isset( $data[ $profile_category_id . '_' . $field_id ] ) ? $data[ $profile_category_id . '_' . $field_id ] : array( 'default' => $global_value );
														$default  = isset( $default['default'] ) ? $default['default'] : '';
														$required = '';
														echo '<tr class="form-field _umb_brand_field ">';

														if ( 'SELECTION_ONLY' == $value['aspectConstraint']['aspectMode'] ) {
															$cardinality          = 'SINGLE';
															$valueForDropdown     = $value['aspectValues'];
															$tempValueForDropdown = array();
															foreach ( $valueForDropdown as $key => $_value ) {
																$tempValueForDropdown[ $_value['localizedValue'] ] = $_value['localizedValue'];
															}
															$valueForDropdown = $tempValueForDropdown;

															if ( 'MULTI' == $value['aspectConstraint']['itemToAspectCardinality'] ) {
																$cardinality = 'MULTI';
															}
															if ( 'true' == $value['aspectConstraint']['aspectRequired'] ) {
																$required = 'required';
															}

															$productFieldInstance->renderDropdownHTML(
																$user_id,
																$site_id,
																$field_id,
																ucfirst( $value['localizedAspectName'] ),
																$valueForDropdown,
																$profile_category_id,
																$productID,
																$marketPlace,
																$indexToUse,
																array(
																	'case'  => 'profile',
																	'value' => $default,
																),
																$required,
																$cardinality
															);
															$isText = false;
														} elseif ( 'COMBO_BOX' == isset( $value['input_type'] ) ? $value['input_type'] : '' ) {
															$cardinality = 'SINGLE';
															$isText      = true;
															if ( 'true' == $value['aspectConstraint']['aspectRequired'] ) {
																$required = 'required';
															}
															if ( 'MULTI' == $value['aspectConstraint']['itemToAspectCardinality'] ) {
																$cardinality = 'MULTI';
															}
															$productFieldInstance->renderInputTextHTML(
																$user_id,
																$site_id,
																$field_id,
																ucfirst( $value['localizedAspectName'] ),
																$profile_category_id,
																$productID,
																$marketPlace,
																$indexToUse,
																array(
																	'case'  => 'profile',
																	'value' => $default,
																),
																$required,
																$cardinality
															);
														} elseif ( 'text' == isset( $value['input_type'] ) ? $value['input_type'] : '' ) {
															$cardinality = 'SINGLE';
															$isText      = true;
															if ( 'true' == $value['aspectConstraint']['aspectRequired'] ) {
																$required = 'required';
															}
															if ( 'MULTI' == $value['aspectConstraint']['itemToAspectCardinality'] ) {
																$cardinality = 'MULTI';
															}
															$productFieldInstance->renderInputTextHTML(
																$user_id,
																$site_id,
																$field_id,
																ucfirst( $value['localizedAspectName'] ),
																$profile_category_id,
																$productID,
																$marketPlace,
																$indexToUse,
																array(
																	'case'  => 'profile',
																	'value' => $default,
																),
																$required,
																$cardinality
															);
														} else {
															$cardinality = 'SINGLE';
															$isText      = true;
															if ( 'true' == $value['aspectConstraint']['aspectRequired'] ) {
																$required = 'required';
															}
															if ( 'MULTI' == $value['aspectConstraint']['itemToAspectCardinality'] ) {
																$cardinality = 'MULTI';
															}
															$productFieldInstance->renderInputTextHTML(
																$user_id,
																$site_id,
																$field_id,
																ucfirst( $value['localizedAspectName'] ),
																$profile_category_id,
																$productID,
																$marketPlace,
																$indexToUse,
																array(
																	'case'  => 'profile',
																	'value' => $default,
																),
																$required,
																$cardinality
															);
														}

														echo '<td>';
														if ( $isText ) {
															$previousSelectedValue = 'null';
															if ( isset( $global_options[ $user_id ][ $site_id ][ $field_id ] ) ) {
																$previousSelectedValue = $global_options[ $user_id ][ $site_id ][ $field_id ]['meta_key'];
															}
															if ( isset( $data[ $profile_category_id . '_' . $field_id ] ) && 'null' != $data[ $profile_category_id . '_' . $field_id ] && isset( $data[ $profile_category_id . '_' . $field_id ]['metakey'] ) && '-1' != $data[ $profile_category_id . '_' . $field_id ]['metakey'] ) {

																$previousSelectedValue = $data[ $profile_category_id . '_' . $field_id ]['metakey'];
															}
															$updatedDropdownHTML = str_replace( '{{*fieldID}}', $profile_category_id . '_' . $field_id, $selectDropdownHTML );
															$updatedDropdownHTML = str_replace( 'value="' . $previousSelectedValue . '"', 'value="' . $previousSelectedValue . '" selected="selected"', $updatedDropdownHTML );
															print_r( $updatedDropdownHTML );
														}
														echo '</td>';

														echo '</tr>';
													}
												}
												if ( isset( $getCatFeatures ) && ! empty( $getCatFeatures ) ) {
													$isText = false;
													if ( isset( $getCatFeatures['ConditionValues'] ) ) {
														$isText   = true;
														$field_id = 'Condition';
														if ( isset( $getCatFeatures['SpecialFeatures']['Condition'] ) && ! isset( $getCatFeatures['SpecialFeatures']['Condition'][0] ) ) {
															$tempSpecialFeatures = array();
															$tempSpecialFeatures = $getCatFeatures['SpecialFeatures']['Condition'];
															unset( $getCatFeatures['SpecialFeatures']['Condition'] );
															$getCatFeatures['SpecialFeatures']['Condition'][] = $tempSpecialFeatures;
														}
														if ( ! empty( $getCatFeatures['SpecialFeatures']['Condition'] ) && is_array( $getCatFeatures['SpecialFeatures']['Condition'] ) ) {
															// $valueForDropdown = $getCatFeatures['ConditionValues']['Condition'] + $getCatFeatures['SpecialFeatures']['Condition'];
															$valueForDropdown = array_merge( $getCatFeatures['ConditionValues']['Condition'], $getCatFeatures['SpecialFeatures']['Condition'] );
														} else {
															$valueForDropdown = $getCatFeatures['ConditionValues']['Condition'];
														}
														$tempValueForDropdown = array();
														if ( isset( $valueForDropdown[0] ) ) {
															foreach ( $valueForDropdown as $key => $value ) {
																$tempValueForDropdown[ $value['ID'] ] = $value['DisplayName'];
															}
														} else {
															$tempValueForDropdown[ $valueForDropdown['ID'] ] = $valueForDropdown['DisplayName'];
														}
														$valueForDropdown = $tempValueForDropdown;
														$name             = 'Condition';
														$default          = isset( $profile_category_data[ $profile_category_id . '_' . $name ] ) ? $profile_category_data[ $profile_category_id . '_' . $name ] : '';
														$default          = isset( $default['default'] ) ? $default['default'] : '';
														if ( isset( $getCatFeatures['ConditionEnabled'] ) && ( 'Enabled' == $getCatFeatures['ConditionEnabled'] || 'Required' == $getCatFeatures['ConditionEnabled'] ) ) {
															$required                                       = true;
															$catFeatureSavingForvalidation[ $categoryID ][] = 'Condition';
															$productFieldInstance->renderDropdownHTML(
																$user_id,
																$site_id,
																'Condition',
																$name,
																$valueForDropdown,
																$profile_category_id,
																$productID,
																$marketPlace,
																$indexToUse,
																array(
																	'case'  => 'profile',
																	'value' => $default,
																),
																$required
															);
														}
													}

													echo '<td>';
													if ( $isText ) {
														$previousSelectedValue = 'null';
														if ( isset( $global_options[ $user_id ][ $site_id ][ $field_id ] ) ) {
															$previousSelectedValue = $global_options[ $user_id ][ $site_id ][ $field_id ]['meta_key'];
														}
														if ( isset( $data[ $profile_category_id . '_' . $field_id ] ) && 'null' != $data[ $profile_category_id . '_' . $field_id ] && isset( $data[ $profile_category_id . '_' . $field_id ]['metakey'] ) && '-1' != $data[ $profile_category_id . '_' . $field_id ]['metakey'] ) {

															$previousSelectedValue = $data[ $profile_category_id . '_' . $field_id ]['metakey'];
														}
														$updatedDropdownHTML = str_replace( '{{*fieldID}}', $profile_category_id . '_' . $field_id, $selectDropdownHTML );
														$updatedDropdownHTML = str_replace( 'value="' . $previousSelectedValue . '"', 'value="' . $previousSelectedValue . '" selected="selected"', $updatedDropdownHTML );
														print_r( $updatedDropdownHTML );
													}
													echo '</td>';

													echo '</tr>';

												}

												?>



												<tr>
													<th colspan="3" ><h3 class="mb-3 text-primary">
														<?php esc_attr_e( 'FRAMEWORK SPECIFIC', 'ebay-integration-for-woocommerce' ); ?></h3>
													</th>
												</tr>	

												<?php
												if ( ! empty( $profile_data ) ) {
													$data = json_decode( $profile_data['profile_data'], true );
												}
												$productFieldInstance = CedeBayProductsFields::get_instance();
												$fields               = $productFieldInstance->ced_ebay_get_profile_framework_specific( $profile_category_id );

												foreach ( $fields as $value ) {
													$isText   = false;
													$field_id = trim( $value['fields']['id'], '_' );
													if ( in_array( $value['fields']['id'], $requiredInAnyCase ) ) {
														$attributeNameToRender  = isset( $value['fields']['label'] ) ? ucfirst( $value['fields']['label'] ) : '';
														$attributeNameToRender .= '<span class="ced_ebay_wal_required">' . __( '[ Required ]', 'ebay-integration-for-woocommerce' ) . '</span>';
													} else {
														$attributeNameToRender = isset( $value['fields']['label'] ) ? ucfirst( $value['fields']['label'] ) : '';
													}

													$default           = isset( $data[ $value['fields']['id'] ]['default'] ) && ! empty( $data[ $value['fields']['id'] ]['default'] ) ? $data[ $value['fields']['id'] ]['default'] : '';
													$field_description = isset( $value['fields']['description'] ) ? $value['fields']['description'] : '';
													$required          = isset( $value['required'] ) ? $value['required'] : '';
													echo '<tr class="form-field _umb_id_type_field ">';

													if ( isset( $value['type'] ) && '_select' == $value['type'] ) {
														$default          = isset( $global_options[ $user_id ][ $site_id ][ $value['fields']['global_id'] ]['meta_key'] ) && '' != $global_options[ $user_id ][ $site_id ][ $value['fields']['global_id'] ]['meta_key'] ? $global_options[ $user_id ][ $site_id ][ $value['fields']['global_id'] ]['meta_key'] : $default;
														$valueForDropdown = $value['fields']['options'];
														if ( '_umb_id_type' == $value['fields']['id'] ) {
															unset( $valueForDropdown['null'] );
														}
														$productFieldInstance->renderDropdownHTML(
															$user_id,
															$site_id,
															$field_id,
															$attributeNameToRender,
															$valueForDropdown,
															$categoryID,
															$productID,
															$marketPlace,
															$indexToUse,
															array(
																'case'  => 'profile',
																'value' => $default,
															),
															$required,
															'SINGLE',
															$field_description
														);
														$isText = false;
													} elseif ( isset( $value['type'] ) && '_text_input' == $value['type'] ) {
														$default = isset( $global_options[ $user_id ][ $site_id ][ $value['fields']['global_id'] ]['custom_value'] ) && '' != $global_options[ $user_id ][ $site_id ][ $value['fields']['global_id'] ]['custom_value'] ? $global_options[ $user_id ][ $site_id ][ $value['fields']['global_id'] ]['custom_value'] : $default;
														$productFieldInstance->renderInputTextHTML(
															$user_id,
															$site_id,
															$field_id,
															$attributeNameToRender,
															$categoryID,
															$productID,
															$marketPlace,
															$indexToUse,
															array(
																'case'  => 'profile',
																'value' => $default,
															),
															$required,
															'SINGLE',
															$field_description
														);
														$isText = true;
													} elseif ( isset( $value['type'] ) && '_hidden' == $value['type'] ) {
														$productFieldInstance->renderInputTextHTMLhidden(
															$user_id,
															$site_id,
															$field_id,
															$attributeNameToRender,
															$categoryID,
															$productID,
															$marketPlace,
															$indexToUse,
															array(
																'case'  => 'profile',
																'value' => $profile_category_id,
															),
															'',
															'SINGLE',
															$field_description
														);
														$isText = false;
													} else {
														$isText = false;
													}

													echo '<td>';
													if ( $isText ) {
														$previousSelectedValue = 'null';
														if ( isset( $global_options[ $user_id ][ $site_id ][ $value['fields']['global_id'] ] ) && '-1' != $global_options[ $user_id ][ $site_id ][ $value['fields']['global_id'] ] ) {
															$previousSelectedValue = $global_options[ $user_id ][ $site_id ][ $value['fields']['global_id'] ]['meta_key'];
														}

														if ( isset( $data[ $value['fields']['id'] ]['metakey'] ) && 'null' != $data[ $value['fields']['id'] ]['metakey'] && '-1' != $data[ $value['fields']['id'] ]['metakey'] ) {
															$previousSelectedValue = $data[ $value['fields']['id'] ]['metakey'];
														}
														$updatedDropdownHTML = str_replace( '{{*fieldID}}', $value['fields']['id'], $selectDropdownHTML );
														$updatedDropdownHTML = str_replace( 'value="' . $previousSelectedValue . '"', 'value="' . $previousSelectedValue . '" selected="selected"', $updatedDropdownHTML );
														print_r( $updatedDropdownHTML );
													}
													echo '</td>';
													echo '</tr>';
												}
												?>
												<input type="hidden" value="<?php echo esc_attr( $profile_category_id ); ?>" name="profile_category_id">
												<input type="hidden" value="<?php echo esc_attr( $user_id ); ?>" name="user_id">
												<input type="hidden" value="<?php echo esc_attr( $site_id ); ?>" name="site_id">
												<?php wp_nonce_field( 'ced_ebay_profile_clone_page_nonce', 'ced_ebay_profile_clone' ); ?>
											</tbody></table>

										</div>
									</div>
								</div>


							</div>
						</div>
					</div>

					<div class = "row" style="padding-bottom: 3%;">
						<div class="add-new-template-footer">
							<input type="submit" class="components-button is-primary button-next alignright" name="ced_ebay_profile_save_button"></input>
						</div>
					</div>

					<?php
					echo '<script>jQuery(".ced_ebay_multi_select_item_aspects").selectWoo({});jQuery( ".ced_ebay_item_specifics_options" ).selectWoo({width: "90%"});
					jQuery( ".ced_ebay_search_item_sepcifics_mapping" ).selectWoo({width: "90%"});</script>';
					die;
		}
	}

	// Importer functionality
	public function ced_ebay_init_import_by_loader() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$user_id     = isset( $_POST['user_id'] ) ? sanitize_text_field( wp_unslash( $_POST['user_id'] ) ) : '';
			$site_id     = isset( $_POST['site_id'] ) ? sanitize_text_field( wp_unslash( $_POST['site_id'] ) ) : '';
			$click_event = isset( $_POST['click_event'] ) ? sanitize_text_field( wp_unslash( $_POST['click_event'] ) ) : '';
			$shop_data   = ced_ebay_get_shop_data( $user_id, $site_id );
			if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
				$siteID = $site_id;
				$token  = $shop_data['access_token'];
			} else {
				wp_send_json_error(
					array(
						'message' => 'Invalid eBay site or account!',
					)
				);
			}
			$totalEbayListings = ! empty( get_option( 'ced_ebay_total_listings_' . $user_id ) ) ? get_option( 'ced_ebay_total_listings_' . $user_id ) : 0;
			if ( empty( $totalEbayListings ) ) {
				wp_send_json_error(
					array(
						'message' => 'Unable to start the product import since there are no active listings in your eBay account.',
					)
				);
			}

			$args = array(
				'data' => array(
					'user_id' => $user_id,
					'site_id' => $site_id,
				),
			);
			if ( 'start_import' == $click_event ) {
				if ( function_exists( 'as_schedule_recurring_action' ) ) {
					$schedule_action = as_schedule_recurring_action( strtotime( 'now' ), 360, 'ced_ebay_import_products_manager_for_loader', $args, 'ced_ebay_product_importer_' . $user_id );
				}
				update_option( 'ced_ebay_importer_progress_status_' . $user_id . '>' . $site_id, 'running' );
				wp_send_json_success(
					array(
						'status'  => true,
						'message' => 'Import process has begun. Please refresh the page to view the progress.',
					)
				);
			} elseif ( 'reset_import' == $click_event ) {
				delete_option( 'ced_ebay_product_import_pagination_' . $user_id . '>' . $site_id );
				wp_send_json_success(
					array(
						'status'  => true,
						'message' => 'Importer Progress has been reset!',
					)
				);
			}
		}
	}

	public function ced_ebay_import_products_manager_for_loader( $args ) {
		$user_id = isset( $args['user_id'] ) ? wc_clean( $args['user_id'] ) : '';
		$site_id = isset( $args['site_id'] ) ? wc_clean( $args['site_id'] ) : '';
		$logger  = wc_get_logger();
		$this->ced_ebay_onWpAdminInit();
		$context = array( 'source' => 'ced-ebay-product-import' );
		require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
		$fetchCurrentAction = current_action();
		if ( strpos( $fetchCurrentAction, 'wp_ajax_nopriv_' ) !== false ) {
			$user_id = isset( $_GET['user_id'] ) ? wc_clean( $_GET['user_id'] ) : '';
			$site_id = isset( $_GET['site_id'] ) ? wc_clean( $_GET['site_id'] ) : '';
		}
		$ced_ebay_manager = $this->ced_ebay_manager;
		$shop_data        = ced_ebay_get_shop_data( $user_id, $site_id );
		if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
			$siteID = $site_id;
			$token  = $shop_data['access_token'];
		} else {
			update_option( 'ced_ebay_product_importer_product_error_' . $user_id . '>' . $siteID, 'Site Data is Invalid' );
			return false;
		}

		$page_number        = get_option( 'ced_ebay_product_import_pagination_' . $user_id . '>' . $siteID ) ? get_option( 'ced_ebay_product_import_pagination_' . $user_id . '>' . $siteID ) : 1;
		$length             = 25;
		$mainXml            = '
				<?xml version="1.0" encoding="utf-8"?>
				<GetMyeBaySellingRequest xmlns="urn:ebay:apis:eBLBaseComponents">
				<RequesterCredentials>
				<eBayAuthToken>' . $token . '</eBayAuthToken>
				</RequesterCredentials>
				<ActiveList>
				<Sort>TimeLeft</Sort>
				<Pagination>
				<EntriesPerPage>' . $length . '</EntriesPerPage>
				<PageNumber>' . $page_number . '</PageNumber>
				</Pagination>
				</ActiveList>
				</GetMyeBaySellingRequest>';
		$ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
		$activelist         = $ebayUploadInstance->get_active_products( $mainXml );
		if ( isset( $activelist['ActiveList']['PaginationResult']['TotalNumberOfPages'] ) && isset( $activelist['ActiveList']['PaginationResult']['TotalNumberOfEntries'] ) && ! empty( $activelist['ActiveList']['PaginationResult']['TotalNumberOfPages'] ) && ! empty( $activelist['ActiveList']['PaginationResult']['TotalNumberOfEntries'] ) ) {
			update_option( 'ced_ebay_product_import_total_pages_' . $user_id . '>' . $siteID, $activelist['ActiveList']['PaginationResult']['TotalNumberOfPages'] );
			update_option( 'ced_ebay_product_import_total_entries_' . $user_id . '>' . $siteID, $activelist['ActiveList']['PaginationResult']['TotalNumberOfEntries'] );
			if ( $page_number > $activelist['ActiveList']['PaginationResult']['TotalNumberOfPages'] ) {
				$logger->info( 'Reached end of list. Resetting page.', $context );
				update_option( 'ced_ebay_product_import_pagination_' . $user_id . '>' . $siteID, 1 );
				update_option( 'ced_ebay_product_importer_product_error_' . $user_id . '>' . $siteID, 'Reached end of list. Resetting page.' );
				return;
			}
		}
		$total_ebay_listings = 0;
		if ( isset( $activelist['ActiveList']['PaginationResult']['TotalNumberOfEntries'] ) && 0 < $activelist['ActiveList']['PaginationResult']['TotalNumberOfEntries'] ) {
			$total_ebay_listings = $activelist['ActiveList']['PaginationResult']['TotalNumberOfEntries'];
			update_option( 'ced_ebay_total_listings_' . $user_id, $total_ebay_listings );
		}

		$meta_key                = '_ced_ebay_importer_listing_id_' . $user_id . '>' . $site_id;
		$alreadyImportedProducts = get_posts(
			array(
				'meta_query'     => array(
					array(
						'key'     => $meta_key,
						'compare' => 'EXISTS',
					),
				),
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			)
		);

		if ( ! is_wp_error( $alreadyImportedProducts ) ) {
			$alreadyImportedProductsId       = array();
			$alreadyImportedProductsId       = wp_list_pluck( $alreadyImportedProducts, 'ID' );
			$currentImportedProductsProgress = get_option( 'ced_ebay_product_importer_product_progress_' . $user_id . '>' . $siteID );
			if ( ! empty( $alreadyImportedProductsId ) && is_array( $alreadyImportedProductsId ) && ! empty( $currentImportedProductsProgress ) ) {
				update_option( 'ced_ebay_product_importer_product_progress_' . $user_id . '>' . $siteID, count( $alreadyImportedProductsId ) );
			}
		}

		if ( isset( $activelist['ActiveList']['ItemArray']['Item'][0] ) ) {
			$count_import_operations = 0;
			foreach ( $activelist['ActiveList']['ItemArray']['Item'] as $key => $value ) {
				$store_product  = array();
				$itemId         = ! empty( $value['ItemID'] ) ? $value['ItemID'] : false;
				$itemDetailsXML = '
						<?xml version="1.0" encoding="utf-8"?>
						<GetItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
						<RequesterCredentials>
						<eBayAuthToken>' . $token . '</eBayAuthToken>
						</RequesterCredentials>
						<DetailLevel>ReturnAll</DetailLevel>
						<ItemID>' . $itemId . '</ItemID>
						</GetItemRequest>';
				$itemDetails    = $ebayUploadInstance->get_item_details( $itemDetailsXML );
				if ( false != $itemId ) {
					if ( ! empty( $itemDetails['Item']['SellingStatus']['ListingStatus'] ) && 'Active' == $itemDetails['Item']['SellingStatus']['ListingStatus'] ) {

						++$count_import_operations;
						$store_product = get_posts(
							array(
								'numberposts'  => -1,
								'post_type'    => 'product',
								'meta_key'     => '_ced_ebay_importer_listing_id_' . $user_id . '>' . $siteID,
								'meta_value'   => $itemId,
								'meta_compare' => '=',
							)
						);
					}
				}
				$store_product = wp_list_pluck( $store_product, 'ID' );
				if ( ! empty( $store_product ) ) {
					if ( ! empty( $itemDetails['Item']['ListingDetails']['RelistedItemID'] ) ) {
						update_post_meta( $store_product[0], '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, $itemDetails['Item']['ListingDetails']['RelistedItemID'] );
					}
					if ( false != $itemId ) {
						$existing_product_listing_id = get_post_meta( $store_product[0], '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
						if ( $existing_product_listing_id == $itemId ) {
							$logger->info( 'ItemID ' . wc_print_r( $itemId, true ) . ' | Quantity ' . wc_print_r( $itemDetails['Item']['Quantity'], true ) . ' | QuantitySold ' . wc_print_r( $itemDetails['Item']['SellingStatus']['QuantitySold'], true ), $context );
							$product = wc_get_product( $store_product[0] );
							if ( $product->is_type( 'simple' ) ) {
								if ( ! empty( $value['SellingStatus']['CurrentPrice'] ) ) {
									$product_sale_price = $value['SellingStatus']['CurrentPrice'];
									$product->set_price( $product_sale_price );
									$product->set_regular_price( $value['SellingStatus']['CurrentPrice'] );
									$product->save();
								}
								if ( ( $itemDetails['Item']['Quantity'] - $itemDetails['Item']['SellingStatus']['QuantitySold'] ) > 0 ) {
									$product->set_manage_stock( true );
									$product->set_stock_quantity( $itemDetails['Item']['Quantity'] - $itemDetails['Item']['SellingStatus']['QuantitySold'] );
									$product->set_stock_status( 'instock' );
									$product->save();
								} else {
									$logger->info( wc_print_r( 'Product ' . $store_product[0] . ' is out of stock!', true ), $context );
									$product->set_manage_stock( true );
									$product->set_stock_quantity( 0 );
									$product->set_stock_status( 'outofstock' );
									$product->save();
								}
							} elseif ( $product->is_type( 'variable' ) ) {
								update_post_meta( $store_product[0], '_sku', $itemDetails['Item']['SKU'] );
								$logger->info( wc_print_r( 'Variable Product', true ), $context );
								if ( isset( $itemDetails['Item']['Variations']['Variation'][0] ) && ! empty( $itemDetails['Item']['Variations']['Variation'][0] ) ) {
									$product_variations = $itemDetails['Item']['Variations']['Variation'];
									foreach ( $product_variations as $key => $variation ) {
										$variation_sku = $variation['SKU'];
										$logger->info( wc_print_r( 'Variation SKU -> ' . $variation_sku, true ), $context );

										if ( $variation_sku ) {
											$variation_prod_id = wc_get_product_id_by_sku( $variation_sku );
										}
										if ( $variation['Quantity'] - $variation['SellingStatus']['QuantitySold'] > 0 ) {
											update_post_meta( $variation_prod_id, '_stock_status', 'instock' );
											update_post_meta( $variation_prod_id, '_stock', $variation['Quantity'] - $variation['SellingStatus']['QuantitySold'] );
											update_post_meta( $variation_prod_id, '_manage_stock', 'yes' );
											update_post_meta( $product->get_id(), '_stock_status', 'instock' );
										} else {
											update_post_meta( $variation_prod_id, '_stock_status', 'outofstock' );
										}
									}
								}
							}

							$product->set_status( 'publish' );
							$product_id = $product->save();
							$logger->info( wc_print_r( 'Product Updated -> ' . $product_id, true ), $context );
							if ( false != $itemId ) {
								update_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, $itemId );
							}
							$progress_key_product = 'ced_ebay_product_importer_product_progress_' . $user_id . '>' . $siteID;
							$next_page            = ! empty( get_option( $progress_key_product ) ) ? get_option( $progress_key_product ) : 1;
							++$next_page;
							update_option( $progress_key_product, $next_page );
						}
					}
				} elseif ( ! empty( $itemDetails['Item']['SellingStatus']['ListingStatus'] ) && 'Active' == $itemDetails['Item']['SellingStatus']['ListingStatus'] && 'Chinese' != $itemDetails['Item']['ListingType'] ) {
						$itemId_array = array(
							0 => $itemId,
						);

						$data = array(
							'total_listings' => $total_ebay_listings,
							'page_number'    => $page_number,
							'item_id'        => $itemId_array,
							'user_id'        => $user_id,
						);
						$this->schedule_import_task->push_to_queue( $data );
				}
			}
			$dispatch_process = $this->schedule_import_task->save()->dispatch();
			if ( is_wp_error( $dispatch_process ) ) {
				update_option( 'ced_ebay_error_import_dispatch_' . $user_id, 'yes' );
				update_option( 'ced_ebay_product_import_pagination_' . $user_id . '>' . $siteID, 1 );
			}
			if ( 25 == $count_import_operations ) {
				$next_page = ! empty( get_option( 'ced_ebay_product_import_pagination_' . $user_id . '>' . $siteID ) ) ? get_option( 'ced_ebay_product_import_pagination_' . $user_id . '>' . $siteID ) : 1;
				++$next_page;
				update_option( 'ced_ebay_product_import_pagination_' . $user_id . '>' . $siteID, $next_page );
			} else {
				update_option( 'ced_ebay_product_import_pagination_' . $user_id . '>' . $siteID, 1 );
			}
		}
	}

	public function ced_ebay_stop_import_loader() {

		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$user_id        = isset( $_POST['user_id'] ) ? sanitize_text_field( wp_unslash( $_POST['user_id'] ) ) : '';
			$site_id        = isset( $_POST['site_id'] ) ? sanitize_text_field( wp_unslash( $_POST['site_id'] ) ) : '';
			$scheduler_args = array(
				'user_id' => $user_id,
				'site_id' => $site_id,
			);

			global $wpdb;
			if ( as_has_scheduled_action( null, array( 'data' => $scheduler_args ), 'ced_ebay_product_importer_' . $user_id ) ) {
				$remove_action = as_unschedule_action( null, array( 'data' => $scheduler_args ), 'ced_ebay_product_importer_' . $user_id );
			}
			update_option( 'ced_ebay_product_importer_product_progress_' . $user_id . '>' . $site_id, '' );
			update_option( 'ced_ebay_product_importer_product_error_' . $user_id . '>' . $site_id, '' );
			update_option( 'ced_ebay_importer_progress_status_' . $user_id . '>' . $site_id, '' );
			update_option( 'ced_ebay_product_import_total_pages_' . $user_id . '>' . $site_id, '' );
			update_option( 'ced_ebay_product_import_total_entries_' . $user_id . '>' . $site_id, '' );
			// $this->schedule_import_task->delete_all();
			wp_send_json_success( array( 'status' => true ) );

		}
		wp_die();
	}

	public function ced_ebay_product_importer_heartbeat_footer_js() {
		if ( 'woocommerce_page_sales_channel' == get_current_screen()->id && isset( $_GET['section'] ) && 'overview' == $_GET['section'] ) {
			$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
			$site_id = isset( $_GET['site_id'] ) ? sanitize_text_field( $_GET['site_id'] ) : '';
			if ( empty( $user_id ) || '' == $site_id ) {
				return false;
			}
			$scheduler_args    = array(
				'user_id' => $user_id,
				'site_id' => $site_id,
			);
			$is_action_running = as_get_scheduled_actions(
				array(
					'group'  => 'ced_ebay_product_importer_' . $user_id,
					'args'   => array(
						'data' => $scheduler_args,
					),
					'status' => ActionScheduler_Store::STATUS_RUNNING,
				),
				'ARRAY_A'
			);

			if ( ! as_has_scheduled_action( null, null, 'ced_ebay_product_importer_' . $user_id ) ) {
				return false;
			}

			?>
					<script>
						(function($){

			// Hook into the heartbeat-send
							$(document).on( 'heartbeat-send', function(e, data) {
								data['ced_ebay_import_percent'] = 'ced_ebay_importer_percentage';
								data['ced_ebay_user_id'] = '<?php echo isset( $_GET['user_id'] ) ? esc_attr( wc_clean( $_GET['user_id'] ) ) : ''; ?>';
								data['ced_ebay_site_id'] = '<?php echo isset( $_GET['site_id'] ) ? esc_attr( wc_clean( $_GET['site_id'] ) ) : ''; ?>';
							});

			// Listen for the custom event "heartbeat-tick" on $(document).
							$(document).on( 'heartbeat-tick', function(e, data) {
								if ( ! data['ced_ebay_import_percent'] ) {
									return;
								}

								if ( $(".wc_progress_importer_bar").length > 0) {
									if( data['ced_ebay_import_percent'] <= 100 ) {
										$('.ced_ebay_importer_progress').val(data['ced_ebay_import_percent']);
										$('#ced_ebay_product_import_progress').text(data['ced_ebay_product_import'] + '/' + data['ced_ebay_total_product_entries']);
										$('#ced_ebay_product_import_next_schedule_run').text(data['ced_ebay_next_schedule_run']);
										$('#ced_ebay_product_import_progress_error').text(data['ced_ebay_progress_error']);
										$('.ced-ebay-last-import-title').text(data['ced_ebay_last_import_title']);
									} else {
										$.ajax({
											url: ajaxUrl,
											method: 'POST',
											dataType: 'json',
											data: {
												event : 'finished',
												action: 'ced_ebay_stop_import_loader',
												user_id : '<?php echo isset( $_GET['user_id'] ) ? esc_attr( wc_clean( $_GET['user_id'] ) ) : ''; ?>',
												site_id : '<?php echo isset( $_GET['site_id'] ) ? esc_attr( wc_clean( $_GET['site_id'] ) ) : ''; ?>',
												ajax_nonce: ajaxNonce,
											},
											success: function(response) {
												location.reload();
											}
										});
									}
								}
							});
						}(jQuery));
					</script>
					<?php
		}
	}

	public function ced_ebay_product_importer_heartbeat_received( $response, $data ) {
		$user_id = isset( $data['ced_ebay_user_id'] ) ? esc_attr( wc_clean( $data['ced_ebay_user_id'] ) ) : '';
		$site_id = isset( $data['ced_ebay_site_id'] ) ? esc_attr( wc_clean( $data['ced_ebay_site_id'] ) ) : '';

		if ( empty( $user_id ) || '' == $site_id ) {
			return $response;
		}

		$scheduler_args = array(
			'site_id' => $site_id,
			'user_id' => $user_id,
		);
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			return $response;
		}

		$is_action_scheduled = as_has_scheduled_action( null, null, 'ced_ebay_product_importer_' . $user_id );
		if ( ! $is_action_scheduled ) {
			return $response;
		} else {
			$time_stamp = (int) as_next_scheduled_action( 'ced_ebay_import_products_manager_for_loader', null, 'ced_ebay_product_importer_' . $user_id );
		}

		if ( 'ced_ebay_importer_percentage' == $data['ced_ebay_import_percent'] ) {
			$product_progress         = (int) get_option( 'ced_ebay_product_importer_product_progress_' . $user_id . '>' . $site_id );
			$total_products_to_import = (int) get_option( 'ced_ebay_product_import_total_entries_' . $user_id . '>' . $site_id );
			$error_found              = get_option( 'ced_ebay_product_importer_product_error_' . $user_id . '>' . $site_id );
			// ced_ebay_last_imported_title_
			$lastImportedTitle                          = ! empty( get_option( 'ced_ebay_last_imported_title_' . $user_id . '>' . $site_id, true ) ) ? get_option( 'ced_ebay_last_imported_title_' . $user_id . '>' . $site_id, true ) : '';
			$progress_percentage                        = ( $total_products_to_import > 0 ) ? ( $product_progress / $total_products_to_import ) * 100 : 0;
			$response['ced_ebay_import_percent']        = $progress_percentage;
			$response['ced_ebay_product_import']        = $product_progress;
			$response['ced_ebay_total_product_entries'] = $total_products_to_import;
			$response['ced_ebay_progress_error']        = ! empty( $error_found ) ? $error_found : '';
			$response['ced_ebay_next_schedule_run']     = $time_stamp && $time_stamp > 1 ? 'Next execution in ' . ced_ebay_time_elapsed_string( gmdate( 'Y-m-d H:i:s', $time_stamp ), true, true ) : '';
			$response['ced_ebay_last_import_title']     = $lastImportedTitle;
		}
		return $response;
	}

	/**
	 * Ced_ebay_register_meta_for_template_settings.
	 *
	 * @return void
	 */
	public function ced_ebay_register_meta_for_template_settings() {
		add_meta_box( 'ced_ebay_register_template_setting_metabox', __( 'eBay Settings', 'ebay-integration-for-woocommerce' ), array( $this, 'ced_ebay_display_template_settings_metabox_callback' ), 'product' );
	}

	/**
	 * Ced_ebay_display_template_settings_metabox_callback.
	 *
	 * @param  mixed $product product.
	 * @return void
	 */
	public function ced_ebay_display_template_settings_metabox_callback( $product ) {
		$connected_ebay_accounts = ! empty( get_option( 'ced_ebay_connected_accounts' ) ) ? get_option( 'ced_ebay_connected_accounts', true ) : array();
		if ( ! empty( $connected_ebay_accounts ) ) {
			foreach ( $connected_ebay_accounts as $key => $ebay_sites ) {
				$ebay_user_id = $key;
				if ( ! empty( $ebay_user_id ) && is_array( $ebay_sites ) ) {
					echo '<style>#ced-ebay-woocommerce-product-data .inside, #ced-ebay-woocommerce-product-type-options .inside {
								margin: 0;
								padding: 0;
							}
							#ced-ebay-woocommerce-product-data .panel-wrap, .woocommerce .panel-wrap {
								overflow: hidden;
							}
							#ced-ebay-woocommerce-product-data ul.wc-tabs::after, .woocommerce ul.wc-tabs::after {
								content: "";
								display: block;
								width: 100%;
								height: 9999em;
								position: absolute;
								bottom: -9999em;
								left: 0;
								background-color: #fafafa;
								border-right: 1px solid #eee;
							}
							#ced-ebay-woocommerce-product-data ul.wc-tabs li a span, .woocommerce ul.wc-tabs li a span {
								margin-left: 0.618em;
								margin-right: 0.618em;
							}
							#ced-ebay-woocommerce-product-data ul.wc-tabs li a::before, .woocommerce ul.wc-tabs li a::before {
								font-family: Dashicons;
								speak: never;
								font-weight: 400;
								font-variant: normal;
								text-transform: none;
								line-height: 1;
								-webkit-font-smoothing: antialiased;
								content: "\f107";
								text-decoration: none;
							}
							#ced-ebay-woocommerce-product-data ul.wc-tabs li a, .woocommerce ul.wc-tabs li a {
								margin: 0;
								padding: 10px;
								display: block;
								box-shadow: none;
								text-decoration: none;
								line-height: 20px!important;
								border-bottom: 1px solid #eee;
							}
							#ced-ebay-woocommerce-product-data ul.wc-tabs, .woocommerce ul.wc-tabs {
								margin: 0;
								width: 20%;
								float: left;
								line-height: 1em;
								padding: 0 0 10px;
								position: relative;
								background-color: #fafafa;
								border-right: 1px solid #eee;
								box-sizing: border-box;
							}
							#ced-ebay-woocommerce-product-data ul.wc-tabs li, .woocommerce ul.wc-tabs li {
								margin: 0;
								padding: 0;
								display: block;
								position: relative;
							}
							#ced-ebay-woocommerce-product-data ul.wc-tabs li.active a, .woocommerce ul.wc-tabs li.active a {
								color: #555;
								position: relative;
								background-color: #eee;
							}#ced-ebay-woocommerce-product-data ul.wc-tabs, .woocommerce ul.wc-tabs {
								margin: 0;
								width: 20%;
								float: left;
								line-height: 1em;
								padding: 0 0 10px;
								position: relative;
								background-color: #fafafa;
								border-right: 1px solid #eee;
								box-sizing: border-box;
							}#ced-ebay-woocommerce-product-data .woocommerce_options_panel {
								float: left;
								width: 80%;
							}.ced_ebay_profile_details_wrapper {
								padding-right: 20px;
								padding-left: 150px;
							}
							</style>';
					echo '<div class="ced-ebay-products-view-notice"></div>';
					echo '<div id="ced-ebay-woocommerce-product-data" class="postbox "><div class="inside"><div class="panel-wrap"><ul class="wc-tabs">';
					foreach ( $ebay_sites as $ebay_site => $connection_status ) {
						if ( 'connected' !== $connection_status['status'] ) {
							continue;
						}
						$site_details = ced_ebay_get_site_details( $ebay_site );
						$site_name    = isset( $site_details['name'] ) ? $site_details['name'] : '';
						if ( empty( $site_name ) ) {
							continue;
						}

						echo '<li class="">';
						echo '<a href="#' . esc_attr( $ebay_user_id ) . '_' . esc_attr( $site_name ) . '"><span>' . esc_attr( $ebay_user_id . ' (' . $site_name . ')' ) . '</span></a>';
						echo '</li>';

					}
					echo '</ul>';
					foreach ( $ebay_sites as $ebay_site => $connection_status ) {
						if ( 'connected' !== $connection_status['status'] ) {
							continue;
						}
						$site_details = ced_ebay_get_site_details( $ebay_site );
						$site_name    = isset( $site_details['name'] ) ? $site_details['name'] : '';
						$site_id      = isset( $site_details['siteID'] ) ? $site_details['siteID'] : '';
						if ( empty( $site_name ) ) {
							continue;
						}

						echo '<div id="' . esc_attr( $ebay_user_id ) . '_' . esc_attr( $site_name ) . '" class="panel woocommerce_options_panel ced-ebay-product-template-fields-panel" style="">';
						global $wpdb;
						$profile_data       = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_ebay_profiles WHERE `ebay_user`=%s AND `ebay_site`=%s", $ebay_user_id, $site_id ), 'ARRAY_A' );
						$saved_profile_data = get_post_meta( $product->ID, 'ced_ebay_product_level_profile_data', true );
						if ( ! empty( $saved_profile_data ) ) {
							$selected_profile = isset( $saved_profile_data[ $ebay_user_id . '>' . $site_id ] ) ? $saved_profile_data[ $ebay_user_id . '>' . $site_id ] : '';
							$selected_profile = isset( $selected_profile['_umb_ebay_profile_id']['default'] ) ? $selected_profile['_umb_ebay_profile_id']['default'] : '';
						} else {
							$selected_profile = '';
						}

						echo '<select name="ced_ebay_meta_box_template_select" class="ced_ebay_meta_box_template_select_' . esc_attr( $ebay_user_id ) . esc_attr( $site_id ) . '">';
						echo '<option value="">Select Template</option>';
						if ( ! empty( $profile_data ) ) {
							foreach ( $profile_data as $key => $profile ) {
								if ( ! empty( $profile['profile_data'] ) ) {
									$profile_data     = json_decode( $profile['profile_data'], true );
									$ebay_category_id = isset( $profile_data['_umb_ebay_category']['default'] ) && ! empty( $profile_data['_umb_ebay_category']['default'] ) ? $profile_data['_umb_ebay_category']['default'] : '';
									if ( $selected_profile == $profile['id'] ) {
										echo '<option data-ebay_catid = "' . esc_attr( $ebay_category_id ) . '" value="' . esc_attr( $profile['id'] ) . '" selected>' . esc_attr( $profile['profile_name'] ) . '</option>';
									} else {
										echo '<option data-ebay_catid = "' . esc_attr( $ebay_category_id ) . '" value="' . esc_attr( $profile['id'] ) . '">' . esc_attr( $profile['profile_name'] ) . '</option>';
									}
								}
							}
						}
						echo '</select><input type="button" class="button button-primary" id="ced_ebay_meta_box_template_select_btn" value="View" data-user_id="' . esc_attr( $ebay_user_id ) . '" data-site_id="' . esc_attr( $site_id ) . '">';
						if ( isset( $saved_profile_data[ $ebay_user_id . '>' . $site_id ] ) && ! empty( $saved_profile_data[ $ebay_user_id . '>' . $site_id ] ) && ! empty( $profile_data ) ) {
							echo '<input type="button" class="button primary" id="ced_ebay_meta_box_template_reset_btn" value="Reset" data-user_id="' . esc_attr( $ebay_user_id ) . '" data-site_id="' . esc_attr( $site_id ) . '" data-product_id = "' . esc_attr( $product->ID ) . '">';

							$listing_id = get_post_meta( $product->ID, '_ced_ebay_listing_id_' . $ebay_user_id . '>' . $site_id, true );
							if ( isset( $listing_id ) && ! empty( $listing_id ) ) {
								echo '<br><br>';
								echo '<input type="button" class="button button-primary" id="ced_ebay_meta_box_template_update_upload_btn" value="Update" data-user_id="' . esc_attr( $ebay_user_id ) . '" data-site_id="' . esc_attr( $site_id ) . '">';
								echo '<input type="button" class="button button-primary" style="background:red;" id="ced_ebay_meta_box_template_update_upload_btn" value="End/Reset" data-user_id="' . esc_attr( $ebay_user_id ) . '" data-site_id="' . esc_attr( $site_id ) . '">';
								if ( ! empty( get_option( 'ced_ebay_listing_url_tld_' . $ebay_user_id . '>' . $site_id ) ) ) {
									$listing_url_tld     = get_option( 'ced_ebay_listing_url_tld_' . $ebay_user_id . '>' . $site_id, true );
									$view_url_production = 'https://www.ebay' . $listing_url_tld . '/itm/' . $listing_id;
									$view_url_sandbox    = 'https://sandbox.ebay' . $listing_url_tld . '/itm/' . $listing_id;
								} else {
									$view_url_production = 'https://www.ebay.com/itm/' . $listing_id;
									$view_url_sandbox    = 'https://sandbox.ebay.com/itm/' . $listing_id;
								}
								$mode_of_operation = get_option( 'ced_ebay_mode_of_operation', '' );
								if ( 'sandbox' == $mode_of_operation ) {
									echo '&nbsp;&nbsp;<a target="_blank" href="' . esc_attr( $view_url_sandbox ) . '" class="button button-primary" style="background:green">View on eBay</a>';
								} elseif ( 'production' == $mode_of_operation ) {
									echo '&nbsp;&nbsp;<a target="_blank" href="' . esc_attr( $view_url_production ) . '" class="button button-primary" style="background:green">View on eBay</a>';
								} else {
									echo '&nbsp;&nbsp;<a target="_blank" href="' . esc_attr( $view_url_production ) . '" class="button button-primary" style="background:green">View on eBay</a>';
								}
							} else {
								echo '<br><br>';
								echo '<input type="button" class="button button-primary" id="ced_ebay_meta_box_template_update_upload_btn" value="Upload" data-user_id="' . esc_attr( $ebay_user_id ) . '" data-site_id="' . esc_attr( $site_id ) . '">';
							}
						}
						echo '<br><span class="ced_ebay_meta_box_template_section_' . esc_attr( $ebay_user_id ) . esc_attr( $site_id ) . '"><div class="ced_ebay_profile_details_wrapper_product_level ced_ebay_profile_details_wrapper components-panel ced-padding"><div class="ced_ebay_profile_details_fields"></div></div></span>';
						echo '<input type="hidden" value="' . esc_attr( $ebay_user_id . '>' . $site_id ) . '" name="ced_ebay_template_metabox_user_details[]">';
						echo '<input type ="hidden" value="' . esc_attr( $product->ID ) . '" class="ced_ebay_meta_box_template_product_id">';
						echo '</div>';

					}
					echo '</div><div class="clear"></div></div></div>';
				}
			}
		}
		?>
		<?php
	}

			/**
			 * Ced_ebay_save_custom_template_setting_product_meta_box.
			 *
			 * @param  mixed $product_id product_id.
			 * @return void
			 */
	public function ced_ebay_save_custom_template_setting_product_meta_box( $product_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_product', $product_id ) ) {
			return;
		}

		$sanitized_array = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
		$user_details    = isset( $sanitized_array['ced_ebay_template_metabox_user_details'] ) ? $sanitized_array['ced_ebay_template_metabox_user_details'] : array();
		if ( ! empty( $user_details ) ) {

			foreach ( $user_details as $key => $user_detail ) {
				$saved_profile_data = get_post_meta( $product_id, 'ced_ebay_product_level_profile_data', true );
				$common_fields      = isset( $sanitized_array[ 'ced_ebay_required_common_' . $user_detail ] ) ? $sanitized_array[ 'ced_ebay_required_common_' . $user_detail ] : array();

				if ( ! empty( $saved_profile_data ) ) {
					if ( isset( $saved_profile_data[ $user_detail ] ) && ! empty( $saved_profile_data[ $user_detail ] ) ) {
						$fields_data = array();
						if ( ! empty( $common_fields ) ) {
							foreach ( $common_fields as $k => $common_field ) {

								if ( false !== strpos( $common_field, '_required' ) ) {
									$position     = strpos( $common_field, '_required' );
									$common_field = substr( $common_field, 0, $position );
								}

								if ( isset( $sanitized_array[ $common_field ] ) ) {
									$common_field_wo_userid                            = str_replace( '_' . $user_detail, '', $common_field );
									$fields_data[ $common_field_wo_userid ]['default'] = $sanitized_array[ $common_field ][0];
									$fields_data[ $common_field_wo_userid ]['metakey'] = $sanitized_array[ $common_field_wo_userid . '_attibuteMeta' ];
								}
							}
							$saved_profile_data[ $user_detail ] = $fields_data;
							update_post_meta( $product_id, 'ced_ebay_product_level_profile_data', $saved_profile_data );
						}
					} else {
						$fields_data = array();
						foreach ( $common_fields as $k => $common_field ) {
							if ( false !== strpos( $common_field, '_required' ) ) {
								$position     = strpos( $common_field, '_required' );
								$common_field = substr( $common_field, 0, $position );
							}
							if ( isset( $sanitized_array[ $common_field ] ) ) {
								$common_field_wo_userid                            = str_replace( '_' . $user_detail, '', $common_field );
								$fields_data[ $common_field_wo_userid ]['default'] = $sanitized_array[ $common_field ][0];
								$fields_data[ $common_field_wo_userid ]['metakey'] = $sanitized_array[ $common_field_wo_userid . '_attibuteMeta' ];
							}
						}
						$data[ $user_detail ]   = $fields_data;
						$new_saved_profile_data = array_merge( $saved_profile_data, $data );
						update_post_meta( $product_id, 'ced_ebay_product_level_profile_data', $new_saved_profile_data );
					}
				} else {
					if ( ! empty( $common_fields ) ) {
						$fields_data = array();
						foreach ( $common_fields as $k => $common_field ) {
							if ( false !== strpos( $common_field, '_required' ) ) {
								$position     = strpos( $common_field, '_required' );
								$common_field = substr( $common_field, 0, $position );
							}
							if ( isset( $sanitized_array[ $common_field ] ) ) {
								$common_field_wo_userid                            = str_replace( '_' . $user_detail, '', $common_field );
								$fields_data[ $common_field_wo_userid ]['default'] = $sanitized_array[ $common_field ][0];
								$fields_data[ $common_field_wo_userid ]['metakey'] = $sanitized_array[ $common_field_wo_userid . '_attibuteMeta' ];
							}
						}
						$data[ $user_detail ] = $fields_data;
					} else {
						$data[ $user_detail ] = array();
					}
					update_post_meta( $product_id, 'ced_ebay_product_level_profile_data', $data );
				}
			}
		}
	}


	/**
	 * Ced_ebay_get_profile_for_meta_box.
	 *
	 * @return void
	 */
	public function ced_ebay_get_profile_for_meta_box() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			wp_raise_memory_limit( 'admin' );
			$user_id                   = isset( $_POST['user_id'] ) ? sanitize_text_field( wp_unslash( $_POST['user_id'] ) ) : '';
			$site_id                   = isset( $_POST['site_id'] ) ? sanitize_text_field( wp_unslash( $_POST['site_id'] ) ) : '';
			$profile_id                = isset( $_POST['profile_id'] ) ? sanitize_text_field( wp_unslash( $_POST['profile_id'] ) ) : '';
			$ebay_catid                = isset( $_POST['ebay_catid'] ) ? sanitize_text_field( wp_unslash( $_POST['ebay_catid'] ) ) : '';
			$product_id                = isset( $_POST['product_id'] ) ? sanitize_text_field( wp_unslash( $_POST['product_id'] ) ) : '';
			$ebay_template_obj         = new EbayTemplateCustomMetabox( $user_id, $site_id, $profile_id, $ebay_catid, $product_id );
			$general_section_data      = '';
			$item_aspects_section_data = '';
			$framework_section_data    = '';
			$access_token_arr          = get_option( 'ced_ebay_user_access_token' );
			if ( ! empty( $access_token_arr ) ) {
				foreach ( $access_token_arr as $key => $value ) {
					$tokenValue = get_transient( 'ced_ebay_user_access_token_' . $key );
					if ( false === $tokenValue || null == $tokenValue || empty( $tokenValue ) ) {
						$user_refresh_token = $value['refresh_token'];
						$this->ced_ebay_save_user_access_token( $key, $user_refresh_token, 'refresh_user_token' );
					}
				}
			}
			$general_section_data      = $ebay_template_obj->ced_ebay_get_general_profile_section();
			$item_aspects_section_data = $ebay_template_obj->ced_ebay_get_item_aspects_profile_section();
			$framework_section_data    = $ebay_template_obj->ced_ebay_get_framework_profile_section();
			ob_start();
			echo esc_html( $general_section_data );
			echo esc_html( $item_aspects_section_data );
			echo esc_html( $framework_section_data );
			echo '<script>jQuery(".ced_ebay_multi_select_item_aspects").selectWoo({});jQuery( ".ced_ebay_item_specifics_options" ).selectWoo({width: "90%"});
            jQuery( ".ced_ebay_search_item_sepcifics_mapping" ).selectWoo({width: "90%"});</script>';
		}
		wp_die();
	}

	/**
	 * Ced_ebay_reset_profile_for_meta_box.
	 *
	 * @return void
	 */
	public function ced_ebay_reset_profile_for_meta_box() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$user_id            = isset( $_POST['user_id'] ) ? sanitize_text_field( wp_unslash( $_POST['user_id'] ) ) : '';
			$site_id            = isset( $_POST['site_id'] ) ? sanitize_text_field( wp_unslash( $_POST['site_id'] ) ) : '';
			$product_id         = isset( $_POST['product_id'] ) ? sanitize_text_field( wp_unslash( $_POST['product_id'] ) ) : '';
			$profile_id         = isset( $_POST['profile_id'] ) ? sanitize_text_field( wp_unslash( $_POST['profile_id'] ) ) : '';
			$saved_profile_data = get_post_meta( $product_id, 'ced_ebay_product_level_profile_data', true );
			if ( isset( $saved_profile_data[ $user_id . '>' . $site_id ] ) && isset( $saved_profile_data[ $user_id . '>' . $site_id ]['_umb_ebay_profile_id']['default'] ) && $profile_id == $saved_profile_data[ $user_id . '>' . $site_id ]['_umb_ebay_profile_id']['default'] ) {
				$saved_profile_data[ $user_id . '>' . $site_id ] = array();
				update_post_meta( $product_id, 'ced_ebay_product_level_profile_data', $saved_profile_data );
				echo json_encode(
					array(
						'message' => 'Product Specific Template Reset Successfully!!!',
					)
				);
			} else {
				echo json_encode(
					array(
						'message' => 'Product Specific Template Data Not Found!!!',
					)
				);
			}
		}
		wp_die();
	}

	/**
	 * Ced_add_meta_field_prod_spec_template.
	 *
	 * @return void
	 */
	public function ced_add_meta_field_prod_spec_template() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$attributes         = wc_get_attribute_taxonomies();
			$attrOptions        = array();
			$addedMetaKeys      = get_option( 'CedUmbProfileSelectedMetaKeys', false );
			$selectDropdownHTML = '';

			global $wpdb;
			$results = $wpdb->get_results( "SELECT DISTINCT meta_key FROM {$wpdb->prefix}postmeta WHERE meta_key NOT LIKE '%wcf%' AND meta_key NOT LIKE '%elementor%' AND meta_key NOT LIKE '%_menu%'", 'ARRAY_A' );
			foreach ( $results as $key => $meta_key ) {
				$post_meta_keys[] = $meta_key['meta_key'];
			}
			$custom_prd_attrb = array();
			$query            = $wpdb->get_results( $wpdb->prepare( "SELECT `meta_value` FROM  {$wpdb->prefix}postmeta WHERE `meta_key` LIKE %s", '_product_attributes' ), 'ARRAY_A' );
			if ( ! empty( $query ) ) {
				foreach ( $query as $key => $db_attribute_pair ) {
					foreach ( maybe_unserialize( $db_attribute_pair['meta_value'] ) as $key => $attribute_pair ) {
						if ( 1 != $attribute_pair['is_taxonomy'] ) {
							$custom_prd_attrb[] = $attribute_pair['name'];
						}
					}
				}
			}
			if ( $addedMetaKeys && count( $addedMetaKeys ) > 0 ) {
				foreach ( $addedMetaKeys as $metaKey ) {
					$attrOptions[ $metaKey ] = $metaKey;
				}
			}
			if ( ! empty( $attributes ) ) {
				foreach ( $attributes as $attributesObject ) {
					$attrOptions[ 'umb_pattr_' . $attributesObject->attribute_name ] = $attributesObject->attribute_label;
				}
			}
			/* select dropdown setup */
			ob_start();
			$global_options = ! empty( get_option( 'ced_ebay_global_options' ) ) ? get_option( 'ced_ebay_global_options', true ) : array();
			$fieldID        = '{{*fieldID}}';
			$selectId       = $fieldID . '_attibuteMeta';
			// $selectDropdownHTML .= '<label>Get value from</lable>';
			$selectDropdownHTML .= '<option value="-1">Select</option>';
			$selectDropdownHTML .= '<option value="ced_product_tags">Product Tags</option>';
			$selectDropdownHTML .= '<option value="ced_product_cat_single">Product Category - Last Category</option>';
			$selectDropdownHTML .= '<option value="ced_product_cat_hierarchy">Product Category - Hierarchy</option>';

			if ( class_exists( 'ACF' ) ) {
				$acf_fields_posts = get_posts(
					array(
						'posts_per_page' => -1,
						'post_type'      => 'acf-field',
					)
				);

				foreach ( $acf_fields_posts as $key => $acf_posts ) {
					$acf_fields[ $key ]['field_name'] = $acf_posts->post_title;
					$acf_fields[ $key ]['field_key']  = $acf_posts->post_name;
				}
			}
			if ( is_array( $attrOptions ) ) {
				$selectDropdownHTML .= '<optgroup label="Global Attributes">';
				foreach ( $attrOptions as $attrKey => $attrName ) :
					$selectDropdownHTML .= '<option value="' . $attrKey . '">' . $attrName . '</option>';
				endforeach;
			}

			if ( ! empty( $custom_prd_attrb ) ) {
				$custom_prd_attrb    = array_unique( $custom_prd_attrb );
				$selectDropdownHTML .= '<optgroup label="Custom Attributes">';
				foreach ( $custom_prd_attrb as $key => $custom_attrb ) {
					$selectDropdownHTML .= '<option value="ced_cstm_attrb_' . esc_attr( $custom_attrb ) . '">' . esc_html( $custom_attrb ) . '</option>';
				}
			}

			if ( ! empty( $post_meta_keys ) ) {
				$post_meta_keys      = array_unique( $post_meta_keys );
				$selectDropdownHTML .= '<optgroup label="Custom Fields">';
				foreach ( $post_meta_keys as $key => $p_meta_key ) {
					$selectDropdownHTML .= '<option value="' . $p_meta_key . '">' . $p_meta_key . '</option>';
				}
			}

			if ( ! empty( $acf_fields ) ) {
				$selectDropdownHTML .= '<optgroup label="ACF Fields">';
				foreach ( $acf_fields as $key => $acf_field ) :
					$selectDropdownHTML .= '<option value="acf_' . $acf_field['field_key'] . '">' . $acf_field['field_name'] . '</option>';
				endforeach;
			}
			$selectDropdownHTML .= '</select>';
			print_r( $selectDropdownHTML );
			echo '<script>jQuery( ".ced_fetched_meta_field_dropdown" ).selectWoo({width: "90%"});</script>';
			wp_die();
		}
	}
}
