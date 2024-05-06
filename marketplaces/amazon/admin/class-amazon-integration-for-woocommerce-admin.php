<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       care@cedcommerce.com
 * @since      1.0.0
 *
 * @package    Amazon_Integration_For_Woocommerce
 * @subpackage Amazon_Integration_For_Woocommerce/admin
 */

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil as CedAmazonHOPS;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Amazon_Integration_For_Woocommerce
 * @subpackage Amazon_Integration_For_Woocommerce/admin
 */
class Amazon_Integration_For_Woocommerce_Admin {


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
	 * The HPOS instance.
	 *
	 * @since    1.0.0
	 * @var      string    $create_amz_order_hops    The HPOS instane.
	 */
	public $create_amz_order_hops = false;


	/**
	 * The current instance of excel sheet.
	 *
	 * @since    1.0.0
	 * @var      string    $reader    The current instance of excel sheet..
	 */
	public $reader;

	public $order_manager;
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct() {


		ini_set( 'max_input_vars', 3000 );

		$this->plugin_name = 'amazon-for-woocommerce';
		$this->version     = '1.0.0';

		add_action( 'ced_show_connected_accounts', array( $this, 'ced_show_connected_accounts' ) );
		add_action( 'ced_show_connected_accounts_details', array( $this, 'ced_show_connected_accounts_details' ) );

		add_action( 'wp_ajax_ced_amazon_process_profile_bulk_action', array( $this, 'ced_amazon_process_profile_bulk_action' ) );
		add_filter( 'views_edit-shop_order', array( $this, 'ced_amazon_add_woo_order_views' ) );
		add_filter( 'parse_query', array( $this, 'ced_amazon_woo_admin_order_filter_query' ) );

		add_filter( 'upload_mimes', array( $this, 'allow_xlsm_upload' ), 1, 1 );

		// Amazon order in woo order section start
		$this->load_admin_classes();
		$this->instantiate_admin_classes();

		add_action( 'add_meta_boxes', array( $this->order_manager, 'add_meta_boxes' ), 30 );
		add_action( 'ced_sales_channel_include_template', array( $this, 'ced_amazon_accounts_page' ) );

		/** Custom column in order section */
		
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'custom_shop_order_column' ), 20 );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'custom_orders_list_column_content' ), 10, 2 );
		
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'custom_shop_order_column' ), 20 );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'custom_orders_list_column_content' ), 20, 2 );


		// Amazon order in woo order section end

		// Media scripts
		// add_action('wp_enqueue_scripts', array( $this, 'enqueue_scripts_styles_admin' ) );
		// Media scripts

		add_filter( 'woocommerce_order_number', array( $this, 'ced_amz_modify_woo_order_number' ), 20, 2 );

		// CRON scheduler functions
		$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', array() );
		if ( is_array( $saved_amazon_details ) && ! empty( $saved_amazon_details ) ) {
			foreach ( $saved_amazon_details as $sellerDataKey => $sellerDataValue ) {
				add_action( 'ced_amazon_inventory_scheduler_job_' . $sellerDataKey, array( $this, 'ced_amazon_cron_inventory_sync' ), 10, 1 );
				add_action( 'ced_amazon_order_scheduler_job_' . $sellerDataKey, array( $this, 'ced_amazon_cron_order_sync' ), 10, 1 );

				add_action( 'ced_amazon_existing_products_sync_job_' . $sellerDataKey, array( $this, 'ced_amazon_cron_exist_product_sync' ), 10, 1 );
				add_action( 'ced_amazon_catalog_asin_sync_job_' . $sellerDataKey, array( $this, 'ced_amazon_cron_catalog_asin_sync' ), 10, 1 );

				add_action( 'ced_amazon_price_scheduler_job_' . $sellerDataKey, array( $this, 'ced_amazon_cron_price_sync' ), 10, 1 );
			}
		}
	}

	public function allow_xlsm_upload( $mimes ) {
		
		$mimes['xlsm'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
		return $mimes;
	}


	/**
	 * Ced Amazon modifiy woocommerce order number.
	 *
	 * @param [int] $order_id
	 * @param [int] $order
	 * @return int 
	 */
	public function ced_amz_modify_woo_order_number( $order_id, $order ) {

		if ( CedAmazonHOPS::custom_orders_table_usage_is_enabled() ) {
			$this->create_amz_order_hops = true;
		}


		if ( !empty( $order_id ) ) {


			if ( $this->create_amz_order_hops ) {

				global $wpdb;

				$meta_key1 = 'amazon_order_id';
				$meta_key2 = 'ced_amazon_order_seller_id';

				
				$ced_amazon_order_id = $wpdb->get_results( $wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}wc_orders_meta WHERE order_id = %d AND meta_key = %s", $order_id, $meta_key1) );

				$ced_amazon_order_id = isset( $ced_amazon_order_id[0] ) ? json_decode( json_encode($ced_amazon_order_id[0]), true ) : array();
				$ced_amazon_order_id = isset( $ced_amazon_order_id['meta_value'] ) ? $ced_amazon_order_id['meta_value'] : '';

				$ced_amazon_order_seller_id = $wpdb->get_results( $wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}wc_orders_meta WHERE order_id = %d AND meta_key = %s", $order_id, $meta_key2) );

				$ced_amazon_order_seller_id = isset( $ced_amazon_order_seller_id[0] ) ? json_decode( json_encode($ced_amazon_order_seller_id[0]), true ) : array();
				$ced_amazon_order_seller_id = isset( $ced_amazon_order_seller_id['meta_value'] ) ? $ced_amazon_order_seller_id['meta_value'] : '';

	
			} else {
				$ced_amazon_order_id        = get_post_meta( $order->get_id(), 'amazon_order_id', true );
				$ced_amazon_order_seller_id = get_post_meta( $order->get_id(), 'ced_amazon_order_seller_id', true );
			}


			$ced_amazon_global_settings = get_option( 'ced_amazon_global_settings', array() );
			$ced_use_amz_order_no       = isset( $ced_amazon_global_settings[ $ced_amazon_order_seller_id ]['ced_use_amz_order_no'] ) ? $ced_amazon_global_settings[ $ced_amazon_order_seller_id ]['ced_use_amz_order_no'] : '';

			if ( ! empty( $ced_amazon_order_id ) && '1' == $ced_use_amz_order_no ) {
				return $ced_amazon_order_id;
			}

		}

		return $order_id;
	}

	

	public function ced_show_connected_accounts( $channel = 'amazon' ) {
		if ( 'amazon' == $channel ) {
			$ced_amazon_sellernext_shop_ids = get_option( 'ced_amazon_sellernext_shop_ids', array() );
			if ( ! empty( $ced_amazon_sellernext_shop_ids ) ) {
				?>
				<a class="woocommerce-importer-done-view-errors-amazon" href="javascript:void(0)" ><?php echo esc_attr( count( $ced_amazon_sellernext_shop_ids ) ); ?> account
					connected <span class="dashicons dashicons-arrow-down-alt2"></span></a>  
				<?php
			}
		}
	}

	public function ced_show_connected_accounts_details( $channel = 'amazon' ) {
		if ( 'amazon' == $channel ) {

			$file = CED_AMAZON_DIRPATH . 'admin/partials/amazonRegions.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}

			$ced_amazon_sellernext_shop_ids = get_option( 'ced_amazon_sellernext_shop_ids', array() );

			if ( ! empty( $ced_amazon_sellernext_shop_ids ) ) {
				?>
				<tr class="wc-importer-error-log-amazon" style="display:none;">
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
										
									</div> 
								</div>

							<?php

							$sellernextShopIds = get_option( 'ced_amazon_sellernext_shop_ids', array() );

							if ( ! empty( $sellernextShopIds ) && is_array( $sellernextShopIds ) ) {
								foreach ( $ced_amazon_sellernext_shop_ids as $sellernextId => $sellernextData ) {
									$current_marketplace_id   = isset( $sellernextData['marketplace_id'] ) ? $sellernextData['marketplace_id'] : '';
									$current_marketplace_name = isset( $ced_amazon_regions_info[ $current_marketplace_id ] ) && isset( $ced_amazon_regions_info[ $current_marketplace_id ]['country-name'] ) ? $ced_amazon_regions_info[ $current_marketplace_id ]['country-name'] : '';

									if ( isset( $sellernextData['ced_amz_current_step'] ) && 3 < $sellernextData['ced_amz_current_step'] ) {
										$url = get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=overview&user_id=' . $sellernextId . '&seller_id=' . $sellernextData['ced_mp_seller_key'];

										?>

										<div class="ced-account-body"> 
											<div class="ced-acount-body-label">
												<strong><?php echo esc_attr( $current_marketplace_name ); ?></strong>
											</div>
											<div class="ced-connected-button-wrapper">
												<div class="ced-connected-link-account" href="javascript:void(0)"><span class="ced-circle"></span>Onboarding Completed</div>
											</div>
											
											<div class="ced-account-button">																											
												<button id="ced_amazon_disconnect_account_btn" type="button" class="components-button is-tertiary" sellernext-shop-id = "<?php echo esc_attr( $sellernextId ); ?>" seller-id = "<?php echo esc_attr( $sellernextData['ced_mp_seller_key'] ); ?>" > <?php echo esc_html__( 'Disconnect', 'amazon-for-woocommerce' ); ?></button>
												<a type="button" class="components-button is-primary" href="<?php echo esc_url( $url ); ?>">Manage</a></div>
										</div>

													<?php
									} else {
										$current_step = isset( $sellernextData['ced_amz_current_step'] ) ? $sellernextData['ced_amz_current_step'] : '';
										if ( empty( $current_step ) ) {
											$urlKey = 'section=setup-amazon';
										} elseif ( 1 == $current_step ) {
											$urlKey = 'section=setup-amazon&part=wizard-options';
										} elseif ( 2 == $current_step ) {
											$urlKey = 'section=setup-amazon&part=wizard-settings';
										} elseif ( 3 == $current_step ) {
											$urlKey = 'section=setup-amazon&part=configuration';
										} else {
											$part = 'section=overview';
										}

										$sellerID = isset( $sellernextData['ced_mp_seller_key'] ) ? $sellernextData['ced_mp_seller_key'] : '';
										$url      = get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&' . $urlKey . '&user_id=' . $sellernextId . '&seller_id=' . $sellerID;

										?>

										<div class="ced-account-body">
											<div class="ced-acount-body-label">
												<strong><?php echo esc_attr( $current_marketplace_name ); ?></strong>
											</div>
											<div class="ced-pending-button-wrap">
												<a class="ced-pending-link" href="<?php echo esc_url( $url ); ?>"><span class="ced-circle"></span>Onboarding Pending</a>
											</div>
											<div class="ced-account-button">																											
												<button id="ced_amazon_disconnect_account_btn" type="button" class="components-button is-tertiary" sellernext-shop-id = "<?php echo esc_attr( $sellernextId ); ?>" seller-id = "<?php echo esc_attr( $sellerID ); ?>" > <?php echo esc_html__( 'Disconnect', 'amazon-for-woocommerce' ); ?></button>
												<a type="button" class="components-button is-primary" href="<?php echo esc_url( $url ); ?>">Manage</a></div>
											</div>
										</div>

										<?php
									}
								}
							}
							?>

							</div>
						</div>
					</td>
				</tr>
				<?php
			}
		}
	}


	/**
	 * Including feed manager and order manager classes.
	 *
	 * @name load_admin_classes()
	 * @since 1.0.0
	 * @link  http://www.cedcommerce.com/
	 */
	private function load_admin_classes() {
		$classes_names = array(
			'admin/amazon/lib/class-order-manager.php',
			'admin/amazon/lib/class-feed-manager.php',
		);

		foreach ( $classes_names as $class_name ) {
			require_once CED_AMAZON_DIRPATH . $class_name;
		}
	}


	/**
	 * Storing instance of feed manager and order manager classes.
	 *
	 * @name instantiate_admin_classes()
	 * @since 1.0.0
	 * @link  http://www.cedcommerce.com/
	 */
	private function instantiate_admin_classes() {

		if ( class_exists( 'Ced_Umb_Amazon_Order_Manager' ) ) {
			$this->order_manager = Ced_Umb_Amazon_Order_Manager::get_instance();
		}

		if ( class_exists( 'Ced_Umb_Amazon_Feed_Manager' ) ) {
			$this->amazon_feed_manager = new Ced_Umb_Amazon_Feed_Manager();
		}
	}


	/**
	 * Price update via cron scheduler.
	 *
	 * @name ced_amazon_cron_price_sync()
	 * @since 1.0.0
	 * @link  http://www.cedcommerce.com/
	 */
	public function ced_amazon_cron_price_sync( $seller_id ) {


		if ( is_null( $this->amazon_feed_manager ) ) {
			return;
		}

		// Log file name
		$log_date = gmdate( 'Y-m-d' );
		$log_name = 'price_api_' . $log_date . '.txt';

		if ( empty( $seller_id ) ) {
			$log_message  = ced_woo_timestamp() . "\n";
			$log_message .= "Seller id argument is missing from inventory CRON scheduler! \n\n\n";
			ced_amazon_log_data( $log_message, $log_name, 'feed' );
			return;
		}

		$ced_price_chunk        = 50;
		$products_price_to_sync = get_option( 'ced_amazon_price_sync_' . $seller_id, array() );

		if ( empty( $products_price_to_sync ) ) {

			$args = array(

				'post_type'      => array( 'product' ),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			);

			$product_ids            = get_posts( $args );
			$products_price_to_sync = array_chunk( $product_ids, $ced_price_chunk );

		}

		$product_ids_chunk = isset( $products_price_to_sync[0] ) ? $products_price_to_sync[0] : array();

		if ( isset( $product_ids_chunk ) && ! empty( $product_ids_chunk ) ) {

			$mplocation = '';
			if ( ! empty( $seller_id ) ) {
				$mplocation_arr = explode( '|', $seller_id );
				$mplocation     = isset( $mplocation_arr[1] ) ? $mplocation_arr[0] : '';
			}
			$this->amazon_feed_manager->ced_amazon_bulk_price_update( $product_ids_chunk, $mplocation, $seller_id );
		}

		unset( $products_price_to_sync[0] );
		$products_price_to_sync = array_values( $products_price_to_sync );
		update_option( 'ced_amazon_price_sync_' . $seller_id, $products_price_to_sync );
	}

	/**
	 * Inventory update via cron scheduler.
	 *
	 * @name ced_amazon_cron_inventory_sync()
	 * @since 1.0.0
	 * @link  http://www.cedcommerce.com/
	 */
	public function ced_amazon_cron_inventory_sync( $seller_id ) {

		if ( is_null( $this->amazon_feed_manager ) ) {
			return;
		}

		// Log file name
		$log_date = gmdate( 'Y-m-d' );
		$log_name = 'inventory_api_' . $log_date . '.txt';

		if ( empty( $seller_id ) ) {
			$log_message  = ced_woo_timestamp() . "\n";
			$log_message .= "Seller id argument is missing from inventory CRON scheduler! \n\n\n";
			ced_amazon_log_data( $log_message, $log_name, 'feed' );
			return;
		}

		$ced_inv_chunk              = 50;
		$products_inventory_to_sync = get_option( 'ced_amazon_inventory_sync_' . $seller_id, array() );

		if ( empty( $products_inventory_to_sync ) ) {

			$args = array(

				'post_type'      => array( 'product' ),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			);

			$product_ids                = get_posts( $args );
			$products_inventory_to_sync = array_chunk( $product_ids, $ced_inv_chunk );

		}

		$product_chunk = isset( $products_inventory_to_sync[0] ) ? $products_inventory_to_sync[0] : array();

		if ( isset( $product_chunk ) && ! empty( $product_chunk ) ) {

			$mplocation = '';
			if ( ! empty( $seller_id ) ) {
				$mplocation_arr = explode( '|', $seller_id );
				$mplocation     = isset( $mplocation_arr[1] ) ? $mplocation_arr[0] : '';
			}
			$this->amazon_feed_manager->ced_amazon_bulk_inventory_update( $product_chunk, $mplocation, $seller_id );
		}

		unset( $products_inventory_to_sync[0] );
		$products_inventory_to_sync = array_values( $products_inventory_to_sync );
		update_option( 'ced_amazon_inventory_sync_' . $seller_id, $products_inventory_to_sync );
	}


	/**
	 * Order sync via cron scheduler.
	 *
	 * @name ced_amazon_cron_order_sync()
	 * @since 1.0.0
	 * @link  http://www.cedcommerce.com/
	 */
	public function ced_amazon_cron_order_sync( $seller_id ) {

		if ( is_null( $this->order_manager ) ) {
			return;
		}

		// Log file name
		$log_date = gmdate( 'Y-m-d' );
		$log_name = 'order_api_' . $log_date . '.txt';

		if ( empty( $seller_id ) ) {
			$log_message  = ced_woo_timestamp() . "\n";
			$log_message .= "Seller id argument is missing from order CRON scheduler! \n\n\n";
			ced_amazon_log_data( $log_message, $log_name, 'order' );
			return;
		}

		$mplocation = '';
		if ( ! empty( $seller_id ) ) {
			$mplocation_arr = explode( '|', $seller_id );
			$mplocation     = isset( $mplocation_arr[1] ) ? $mplocation_arr[0] : '';
		}
		$this->order_manager->fetchOrders( $mplocation, $cron = true, $amazon_order_id = '', $seller_id );
	}


	/**
	 * Exist products sync via cron scheduler.
	 *
	 * @name ced_amazon_cron_exist_product_sync()
	 * @since 1.0.0
	 * @link  http://www.cedcommerce.com/
	 */
	public function ced_amazon_cron_exist_product_sync( $seller_id = '' ) {

		// Log file name
		$log_date = gmdate( 'Y-m-d' );
		$log_name = 'report_data_api_' . $log_date . '.txt';

		$amzonCurlRequest = CED_AMAZON_DIRPATH . 'admin/amazon/lib/ced-amazon-curl-request.php';
		if ( file_exists( $amzonCurlRequest ) ) {
			require_once $amzonCurlRequest;
			$amzonCurlRequestInstance = new Ced_Amazon_Curl_Request();

		} else {
			$log_message  = ced_woo_timestamp() . "\n";
			$log_message .= "Amazon curl request file instance doesn't exist \n\n\n";
			ced_amazon_log_data( $log_message, $log_name, 'report' );
			return;
		}

		$mplocation = '';
		if ( empty( $seller_id ) ) {
			$log_message  = ced_woo_timestamp() . "\n";
			$log_message .= "Seller id argument is missing from report data CRON scheduler, please check! \n\n\n";
			ced_amazon_log_data( $log_message, $log_name, 'report' );
			return;
		} else {
			$mplocation_arr = explode( '|', $seller_id );
			$mplocation     = isset( $mplocation_arr[1] ) ? $mplocation_arr[0] : '';
		}


		// throttle check
		$ced_amazon_exist_product_sync_throttle = get_transient('ced_amazon_exist_product_sync_throttle') ;
		if ( $ced_amazon_exist_product_sync_throttle ) {

			$log_message  = ced_woo_timestamp() . "\n";
			$log_message .= "API call limit exceeded. Please try after 5 mins.! \n\n\n";
			ced_amazon_log_data( $log_message, $log_name, 'catalog' );
			return;
			
		}

		// check active marketplace or not
		$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', false );

		$location_for_seller = $seller_id;
		if ( ! isset( $saved_amazon_details[ $location_for_seller ] ) || $saved_amazon_details[ $location_for_seller ]['ced_mp_name'] != $mplocation ) {
			$log_message  = ced_woo_timestamp() . "\n";
			$log_message .= "Mplocation is not matching with validated account while report data call! \n\n\n";
			ced_amazon_log_data( $log_message, $log_name, 'report' );
			return;
		}

		if ( isset( $saved_amazon_details[ $location_for_seller ] ) && ! empty( $saved_amazon_details[ $location_for_seller ] ) && is_array( $saved_amazon_details[ $location_for_seller ] ) ) {
			$shop_data = $saved_amazon_details[ $location_for_seller ];
		}

		$refresh_token  = isset( $shop_data['amazon_refresh_token'] ) ? $shop_data['amazon_refresh_token'] : '';
		$marketplace_id = isset( $shop_data['marketplace_id'] ) ? $shop_data['marketplace_id'] : '';
		$merchant_id    = isset( $shop_data['merchant_id'] ) ? $shop_data['merchant_id'] : '';

		if ( empty( $refresh_token ) || empty( $marketplace_id ) ) {
			// Save error in log
			$log_message  = ced_woo_timestamp() . "\n";
			$log_message .= "Refresh token/marketplace id are missing while report data call! \n\n\n";
			ced_amazon_log_data( $log_message, $log_name, 'report' );
			return;
		}

		$products_to_sync = get_option( 'ced_amazon_exist_product_ids' . $seller_id, array() );

		if ( empty( $products_to_sync ) ) {
			$args = array(
				'post_type'        => array( 'product' ),
				'post_status'      => 'publish',
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'meta_query'       => array(
					array(
						'key'     => '_sku',
						'value'   => '',
						'compare' => '!=',
					),
				),
				'suppress_filters' => false,

			);
			$product_ids      = get_posts( $args );
			$products_to_sync = array_chunk( $product_ids, 20 );
		}

		$product_chunks = $products_to_sync[0];
		$array_sku      = array();
		foreach ( $product_chunks as $key => $value ) {
			$sku         = get_post_meta( $value, '_sku', true );
			$array_sku[] = $sku;
		}
		// check sku exists or not
		if ( isset( $array_sku ) && ! empty( $array_sku ) ) {
			try {

				$contract_data = get_option( 'ced_unified_contract_details', array() );
				$contract_id   = isset( $contract_data['amazon'] ) && isset( $contract_data['amazon']['contract_id'] ) ? $contract_data['amazon']['contract_id'] : '';

				$catalog_topic = 'webapi/amazon/search-catalog-items-using-identifier';
				$catalog_data  = array(
					'refresh_token'    => $refresh_token,
					'seller_id'        => $merchant_id,
					'marketplace_id'   => $marketplace_id,
					'identifiers'      => $array_sku,
					'identifiers_type' => array( 'SKU' ),
					'included_data'    => array( 'summaries', 'identifiers' ),
					'contract_id'      => $contract_id,
				);

				$catalog_response = $amzonCurlRequestInstance->ced_amazon_serverless_process( $catalog_topic, $catalog_data, 'POST');

				$code = wp_remote_retrieve_response_code( $catalog_response );
				if ( 429 == $code ) {
					set_transient( 'ced_amazon_exist_product_sync_throttle', 'on', 300 );
				}

				$catalog_body = json_decode( $catalog_response['body'], true );
				$catalog_body = $catalog_body['data'];

				if ( isset( $catalog_body['items'] ) ) {

					$array_sku_amazon = array();
					foreach ( $catalog_body['items'] as $key => $value ) {

						$asin        = $value['asin'];
						$identifiers = $value['identifiers'][0]['identifiers'];

						foreach ( $identifiers as $k => $val ) {
							if ( 'SKU' == $val['identifierType'] ) {
								$sku                = $val['identifier'];
								$array_sku_amazon[] = $sku;
							}
						}

						if ( isset( $sku ) && isset( $asin ) ) {
							$pro_id = wc_get_product_id_by_sku( $sku );

							if ( empty( $pro_id ) ) {
								$pro_id = $this->get_amazon_seller_sku( 'item_sku', $sku );
							}

							$pro_id = (int) $pro_id;
							if ( '' != $pro_id && 0 != $pro_id && ! empty( $pro_id ) ) {

								update_post_meta( $pro_id, 'ced_amazon_already_uploaded_' . $mplocation, 'yes' );
								update_post_meta( $pro_id, 'ced_amazon_product_asin_' . $mplocation, $asin );
								
								$wooc_product = wc_get_product( $pro_id );
								$product_data = $wooc_product->get_data();
								if ( 0 != $product_data['parent_id'] ) {
									$parent_asin = get_post_meta( $product_data['parent_id'], 'ced_amazon_product_asin_' . $mplocation, true );
									if ( empty( $parent_asin ) ) {
										update_post_meta( $product_data['parent_id'], 'ced_amazon_already_uploaded_' . $mplocation, 'yes' );
										update_post_meta( $product_data['parent_id'], 'ced_amazon_product_asin_' . $mplocation, $asin );
									}
								}
							}
						}
					}

					if ( isset( $array_sku_amazon ) && isset( $array_sku ) ) {
						foreach ( $array_sku as $key => $sku_value ) {
							if ( ! in_array( $sku_value, $array_sku_amazon ) ) {
								$pro_id = wc_get_product_id_by_sku( $sku_value );
								if ( empty( $pro_id ) ) {
									$pro_id = $this->get_amazon_seller_sku( 'item_sku', $sku );
								}
								$pro_id = (int) $pro_id;
								if ( ! empty( $pro_id ) ) {
									delete_post_meta( $pro_id, 'ced_amazon_already_uploaded_' . $mplocation );
									delete_post_meta( $pro_id, 'ced_amazon_product_asin_' . $mplocation );
								}
							}
						}
					}
				}
			} catch ( Exception $e ) {
				echo 'Exception when calling report data API end point: ', esc_attr( $e->getMessage() ), PHP_EOL;
				// Save error in log
				ced_amazon_log_data( $e->getMessage(), $log_name, 'report' );
			}
		}

		unset( $products_to_sync[0] );
		$products_to_sync = array_values( $products_to_sync );
		update_option( 'ced_amazon_exist_product_ids' . $seller_id, $products_to_sync );
	}


	/**
	 * Search and sync Amazon catalog ASIN in woo product using UPC/EAN.
	 *
	 * @name ced_amazon_cron_catalog_asin_sync()
	 * @since 1.0.0
	 */
	public function ced_amazon_cron_catalog_asin_sync( $seller_id ) {

		// Log file name
		$log_date = gmdate( 'Y-m-d' );
		$log_name = 'catalog_api_' . $log_date . '.txt';

		$amzonCurlRequest = CED_AMAZON_DIRPATH . 'admin/amazon/lib/ced-amazon-curl-request.php';
		if ( file_exists( $amzonCurlRequest ) ) {
			require_once $amzonCurlRequest;
			$amzonCurlRequestInstance = new Ced_Amazon_Curl_Request();

		} else {
			$log_message  = ced_woo_timestamp() . "\n";
			$log_message .= "Amazon curl request file instance doesn't exist \n\n\n";
			ced_amazon_log_data( $log_message, $log_name, 'report' );
			return;
		}

		$mplocation = '';
		if ( empty( $seller_id ) ) {
			$log_message  = ced_woo_timestamp() . "\n";
			$log_message .= "Seller id argument is missing from ASIN sync CRON scheduler! \n\n\n";
			ced_amazon_log_data( $log_message, $log_name, 'catalog' );
			return;
		} else {
			$mplocation_arr = explode( '|', $seller_id );
			$mplocation     = isset( $mplocation_arr[1] ) ? $mplocation_arr[0] : '';
		}


		// throttle check
		$ced_amazon_catalog_asin_sync_throttle = get_transient('ced_amazon_catalog_asin_sync_throttle') ;
		if ( $ced_amazon_catalog_asin_sync_throttle ) {

			$log_message  = ced_woo_timestamp() . "\n";
			$log_message .= "API call limit exceeded. Please try after 5 mins.! \n\n\n";
			ced_amazon_log_data( $log_message, $log_name, 'catalog' );
			return;
			
		}

		$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', false );
		$location_for_seller  = $seller_id;
		if ( isset( $saved_amazon_details[ $location_for_seller ] ) && ! empty( $saved_amazon_details[ $location_for_seller ] ) && is_array( $saved_amazon_details[ $location_for_seller ] ) ) {
			$shop_data = $saved_amazon_details[ $location_for_seller ];
		}

		$refresh_token  = isset( $shop_data['amazon_refresh_token'] ) ? $shop_data['amazon_refresh_token'] : '';
		$marketplace_id = isset( $shop_data['marketplace_id'] ) ? $shop_data['marketplace_id'] : '';

		if ( empty( $refresh_token ) || empty( $marketplace_id ) || empty( $mplocation ) || empty( $location_for_seller ) ) {
			// Save error in log
			$log_message  = ced_woo_timestamp() . "\n";
			$log_message .= "Refresh_token/marketplace_id/mplocation/seller_id are missing while ASIN sync! \n\n\n";
			ced_amazon_log_data( $log_message, $log_name, 'catalog' );
			return;
		}

		$page_number = get_option( 'ced_amazon_catalog_asin_sync_page_number_' . $location_for_seller );
		if ( empty( $page_number ) ) {
			$page_number = 1;
		}

		// Get UPC/EAN mapping data from global settings
		$global_setting_data = get_option( 'ced_amazon_global_settings', false );
		$meta_key_map        = ! empty( $global_setting_data[ $location_for_seller ]['ced_amazon_catalog_asin_sync_meta'] ) ? $global_setting_data[ $location_for_seller ]['ced_amazon_catalog_asin_sync_meta'] : '_sku';

		$args = array(
			'post_type'        => array( 'product', 'product_variation' ),
			'post_status'      => array( 'publish', 'draft' ),
			'posts_per_page'   => 30,  
			'paged'            => $page_number,
			'suppress_filters' => false,
			'fields'           => 'ids',
		);

		$products = get_posts( $args );

		if ( isset( $products ) && ! empty( $products ) ) {

			foreach ( $products as $product_id ) {

				$product   = wc_get_product( $product_id );
				$parent_id = $product->get_parent_id(); 

				// Get UPC/EAN number from woo meta
				$upc_number        = get_post_meta( $product_id, $meta_key_map, true );
				$upc_number_length = strlen( $upc_number );

				if ( ! empty( $upc_number ) && is_numeric( $upc_number ) && ( 11 == $upc_number_length || 12 == $upc_number_length || 13 == $upc_number_length || 14 == $upc_number_length ) ) {
					// Request to get product data using UPC/EAN

					$contract_data = get_option( 'ced_unified_contract_details', array() );
					$contract_id   = isset( $contract_data['amazon'] ) && isset( $contract_data['amazon']['contract_id'] ) ? $contract_data['amazon']['contract_id'] : '';

					
					$catalog_topic = 'webapi/amazon/search_catalog_items';
					$catalog_data  = array(
						'seller_id'      => $seller_id,
						'marketplace_id' => $marketplace_id,
						'token'          => $refresh_token,
						'ean'            => $upc_number,
						'contract_id'    => $contract_id,
					);

					$catalog_response_main = $amzonCurlRequestInstance->ced_amazon_serverless_process( $catalog_topic, $catalog_data, 'POST');

					$code = wp_remote_retrieve_response_code( $catalog_response_main );
					if ( 429 == $code ) {
						set_transient( 'ced_amazon_catalog_asin_sync_throttle', 'on', 300 );
					}


					if ( is_wp_error( $catalog_response_main ) ) {
						ced_amazon_log_data( $catalog_response_main, $log_name, 'catalog' );
						continue;
					}
					$catalog_response = json_decode( $catalog_response_main['body'], true );
					$catalog_response = $catalog_response['data'];
					if ( isset( $catalog_response['success'] ) && 'false' == $catalog_response['success'] ) {
						// Save error in log
						ced_amazon_log_data( $catalog_response_main, $log_name, 'catalog' );
						continue;
					}

					if ( isset( $catalog_response['payload']['Items'][0] ) && is_array( $catalog_response['payload']['Items'][0] ) && ! empty( $catalog_response['payload']['Items'][0] ) ) {

						$child_asin  = $catalog_response['payload']['Items'][0]['Identifiers']['MarketplaceASIN']['ASIN'];
						$parent_asin = isset( $catalog_response['payload']['Items'][0]['Relationships'][0]['Identifiers']['MarketplaceASIN']['ASIN'] ) ? $catalog_response['payload']['Items'][0]['Relationships'][0]['Identifiers']['MarketplaceASIN']['ASIN'] : '';

						if ( ! empty( $child_asin ) ) {
							update_post_meta( $product_id, 'ced_amazon_catalog_asin_' . $mplocation, $child_asin );
						}

						if ( 0 != $parent_id && ! empty( $parent_asin ) ) {
							update_post_meta( $parent_id, 'ced_amazon_catalog_asin_' . $mplocation, $parent_asin );
						}
					}
				}
			}

			++$page_number;
			update_option( 'ced_amazon_catalog_asin_sync_page_number_' . $location_for_seller, $page_number );

		} else {
			update_option( 'ced_amazon_catalog_asin_sync_page_number_' . $location_for_seller, '' );
		}
	}


	/**
	 * Add column in order table
	 *
	 * @since    1.0.0
	 */
	public function custom_shop_order_column( $columns ) {

		$modified_columns = array();

		
		// Inserting columns to a specific location
		foreach ( $columns as $key => $column ) {
			$modified_columns[ $key ] = $column;
			// columns => order_status, order_total
			if ( 'order_number' == $key ) {
				$modified_columns['order_from'] = '<span title="Order source">Order source</span>';
			}
			if ( 'order_status' == $key ) {
				// Inserting after "Status" column
				$modified_columns['sales_channel'] = __( 'Amazon sales channel', 'ced' );
			}
		}
		return $modified_columns;
	}

	/**
	 * Show column data in order table
	 *
	 * @since    1.0.0
	 */
	public function custom_orders_list_column_content( $column, $post_id ) {

		if ( CedAmazonHOPS::custom_orders_table_usage_is_enabled() ) {
			$this->create_amz_order_hops = true;
		}


		switch ( $column ) {
			case 'order_from':
				if ( $this->create_amz_order_hops ) {

					$order           = wc_get_order( $post_id );
					$amazon_order_id = $order->get_meta( 'amazon_order_id' ) ;
					 
				} else {
					$amazon_order_id = get_post_meta( $post_id, 'amazon_order_id', true );
				}
				
				if ( ! empty( $amazon_order_id ) ) {
					$amazon_icon = plugin_dir_url( __FILE__ ) . 'images/amazon-logo.png';
					echo '<p><img src="' . esc_url( $amazon_icon ) . '" height="35" width="60"></p>';
				}
				break;

			case 'sales_channel':
				if ( $this->create_amz_order_hops ) {

					$order               = wc_get_order( $post_id );
					$order_sales_channel = $order->get_meta( 'ced_umb_order_sales_channel' ) ;
					 
				} else {
					// Get custom post meta data
					$order_sales_channel = get_post_meta( $post_id, 'ced_umb_order_sales_channel', true );


				}

				if ( ! empty( $order_sales_channel ) ) {
					echo esc_attr( $order_sales_channel );
				} else {
					echo esc_attr_e( '---' );
				}

				break;

		}
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
			* defined in Amazon_Integration_For_Woocommerce_Loader as all of the hooks are defined
			* in that particular class.
			*
			* The Amazon_Integration_For_Woocommerce_Loader will then create the relationship
			* between the defined hooks and the functions defined in this
			* class.
			*/

		$section = ! empty( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : false;
		$channel = ! empty( $_GET['channel'] ) ? sanitize_text_field( $_GET['channel'] ) : false;

		if ( isset( $_GET['page'] ) && ( 'sales_channel' == $_GET['page'] ) ) {

			wp_enqueue_style( WC_ADMIN_APP );
			wp_enqueue_style( 'woocommerce_admin_styles' );

			wp_enqueue_style( 'marketplace-amazon-integration', plugin_dir_url( __FILE__ ) . 'css/marketplace_amazon_integration.css', array(), '1.0', 'all' );
			if ( 'amazon' == $channel ) {
				wp_enqueue_style( 'amazon', plugin_dir_url( __FILE__ ) . 'css/amazon.css', array(), time(), 'all' );
			}
		}
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
		 * defined in Amazon_Integration_For_Woocommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Amazon_Integration_For_Woocommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		$channel = ! empty( $_GET['channel'] ) ? sanitize_text_field( $_GET['channel'] ) : false;
		$page    = ! empty( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : false;

		$seller_id = ! empty( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		$user_id   = ! empty( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';

		$ced_amzon_configuration_validated = get_option( 'ced_amzon_configuration_validated', array() );
		$sellernextShopIds                 = get_option( 'ced_amazon_sellernext_shop_ids', array() );

		if ( empty( $seller_id ) && isset( $sellernextShopIds[ $user_id ]['ced_mp_seller_key'] ) ) {
			$seller_id = $sellernextShopIds[ $user_id ]['ced_mp_seller_key'];
		} else {
			$seller_id = urldecode( $seller_id );
		}

		$access_token = isset( $ced_amzon_configuration_validated[ $seller_id ]['seller_next_access_token'] ) ? $ced_amzon_configuration_validated[ $seller_id ]['seller_next_access_token'] : '';

		// Ensure nonce is properly generated
		$ajax_nonce     = wp_create_nonce( 'ced-amazon-ajax-seurity-string' );
		$localize_array = array(
			'ajax_url'     => admin_url( 'admin-ajax.php' ),
			'ajax_nonce'   => $ajax_nonce,
			'site_url'     => get_option( 'siteurl' ),
			'user_id'      => $user_id,
			'access_token' => $access_token,
		);

		wp_enqueue_media();

		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-datepicker' );

		wp_enqueue_script( 'jquery-tiptip' );
		wp_enqueue_script( 'selectWoo' );

		wp_enqueue_script( 'jquery-ui-spinner' );
		wp_enqueue_script( 'jquery-blockui' );

		if ( 'sales_channel' == $page ) {

			$suffix = '';
			wp_register_script( 'woocommerce_admin', WC()->plugin_url() . '/assets/js/admin/woocommerce_admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-tiptip' ), WC_VERSION );
			wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), WC_VERSION, true );

			$params = array(
				'strings' => array(
					'import_products' => __( 'Import', 'woocommerce' ),
					'export_products' => __( 'Export', 'woocommerce' ),
				),
				'urls'    => array(
					'import_products' => esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_importer' ) ),
					'export_products' => esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_exporter' ) ),
				),
			);

			wp_localize_script( 'woocommerce_admin', 'woocommerce_admin', $params );
			wp_enqueue_script( 'woocommerce_admin' );

			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/amazon-integration-for-woocommerce-admin.js', array( 'jquery', 'jquery-tiptip', 'jquery-ui-spinner', 'jquery-blockui' ), $this->version, false );

		}

		wp_localize_script( $this->plugin_name, 'ced_amazon_admin_obj', $localize_array );

		// Load relevant js and css file for "Manage Amazon Order" section on order edit page
		global $post;
		$post_type = get_post_type( $post );
		$order_id  = isset( $post->ID ) ? intval( $post->ID ) : '';

		$screen      = get_current_screen();
		$screen_id   = $screen ? $screen->id : '';
		$order_types = wc_get_order_types();

		if ( in_array( $post_type, $order_types ) && in_array( $screen_id, $order_types ) ) {

			$marketplace = $this->order_manager->get_marketplace_info( $order_id );
			if ( $marketplace && ! is_null( $marketplace ) ) {

				wp_enqueue_style( 'Ced_Umb_Amazon_Order_Manager', plugin_dir_url( __FILE__ ) . '/css/jquery-ui-timepicker-addon.css', array(), $this->version );

				$file_url = plugin_dir_url( __FILE__ ) . '/js/order_manager.js';
				wp_register_script( 'Ced_Umb_Amazon_Order_Manager', $file_url, array( 'jquery' ), $this->version );
				wp_localize_script(
					'Ced_Umb_Amazon_Order_Manager',
					'ced_order_localize',
					array(

						'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
						'ajax_nonce' => wp_create_nonce( 'ced-amazon-order-shipment' ),

					)
				);
				wp_enqueue_script( 'Ced_Umb_Amazon_Order_Manager' );
			}
		}
	}




	/*
	 *
	 * Function to create menu
	 */
	public function ced_amazon_add_menus() {
		global $submenu;

		$menu_slug = 'woocommerce';

		if ( ! empty( $submenu[ $menu_slug ] ) ) {
			$sub_menus = array_column( $submenu[ $menu_slug ], 2 );
			if ( ! in_array( 'sales_channel', $sub_menus ) ) {
				add_submenu_page( 'woocommerce', 'CedCommerce', 'CedCommerce', 'manage_woocommerce', 'sales_channel', array( $this, 'ced_marketplace_home_page' ) );
			}
		}
	}

	/**
	 *
	 * Function to render home page
	 */

	public function ced_marketplace_home_page() {
		?>
		<div class='woocommerce'>
		<?php
		require CED_AMAZON_DIRPATH . 'admin/partials/home.php';
		if ( isset( $_GET['page'] ) && 'sales_channel' == $_GET['page'] && ! isset( $_GET['channel'] ) ) {
			require CED_AMAZON_DIRPATH . 'admin/partials/marketplaces.php';
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




	/**
	 *
	 * Function to create submenus
	 */
	public function ced_amazon_add_marketplace_menus_to_array( $menus = array() ) {

		$installed_plugins = get_plugins();
		$menus             = array(
			'woocommerce-etsy-integration'        => array(
				'name'            => 'Etsy Integration',
				'tab'             => 'Etsy',
				'page_url'        => 'https://woocommerce.com/products/etsy-integration-for-woocommerce/',
				'doc_url'         => 'https://woocommerce.com/document/etsy-integration-for-woocommerce/',
				'slug'            => 'woocommerce-etsy-integration',
				'menu_link'       => 'etsy',
				'card_image_link' => CED_AMAZON_URL . 'admin/images/etsy-logo.png',
				'is_active'       => in_array(
					'woocommerce-etsy-integration/woocommerce-etsy-integration.php',
					/**
										 * Function to get list of active plugins
										 *
										 * @param 'function'
										 * @return 'list'
										 * @since 1.0.0
										 */
					apply_filters( 'active_plugins', get_option( 'active_plugins' ) )
				),
				'is_installed'    => isset( $installed_plugins['woocommerce-etsy-integration/woocommerce-etsy-integration.php'] ) ? true : false,
			),
			'walmart-integration-for-woocommerce' => array(
				'name'            => 'Walmart Integration',
				'tab'             => 'Walmart',
				'page_url'        => 'https://woocommerce.com/products/walmart-integration-for-woocommerce/',
				'doc_url'         => 'https://woocommerce.com/document/walmart-integration-for-woocommerce/',
				'slug'            => 'walmart-integration-for-woocommerce',
				'menu_link'       => 'walmart',
				'card_image_link' => CED_AMAZON_URL . 'admin/images/walmart-logo.png',
				'is_active'       => in_array(
					'walmart-integration-for-woocommerce/walmart-woocommerce-integration.php',
					/**
										 * Function to get list of active plugins
										 *
										 * @param 'function'
										 * @return 'list'
										 * @since 1.0.0
										 */
										apply_filters( 'active_plugins', get_option( 'active_plugins' ) )
				),
				'is_installed'    => isset( $installed_plugins['walmart-integration-for-woocommerce/walmart-woocommerce-integration.php'] ) ? true : false,
			),
			'ebay-integration-for-woocommerce'    => array(
				'name'            => 'eBay Integration',
				'tab'             => 'eBay',
				'page_url'        => 'https://woocommerce.com/products/ebay-integration-for-woocommerce/',
				'doc_url'         => 'https://woocommerce.com/document/ebay-integration-for-woocommerce/',
				'slug'            => 'ebay-integration-for-woocommerce',
				'menu_link'       => 'ebay',
				'card_image_link' => CED_AMAZON_URL . 'admin/images/ebay-logo.png',
				'is_active'       => in_array(
					'ebay-integration-for-woocommerce/ebay-integration-for-woocommerce.php',
					/**
										 * Function to get list of active plugins
										 *
										 * @param 'function'
										 * @return 'list'
										 * @since 1.0.0
										 */
					apply_filters( 'active_plugins', get_option( 'active_plugins' ) )
				),
				'is_installed'    => isset( $installed_plugins['ebay-integration-for-woocommerce/ebay-woocommerce-integration.php'] ) ? true : false,
			),
			'amazon-for-woocommerce'              => array(
				'name'            => 'Amazon Integration',
				'tab'             => 'Amazon',
				'page_url'        => 'https://woocommerce.com/products/amazon-for-woocommerce/',
				'doc_url'         => 'https://woocommerce.com/document/amazon-for-woocommerce/',
				'slug'            => 'amazon-for-woocommerce',
				'menu_link'       => 'amazon',
				'card_image_link' => CED_AMAZON_URL . 'admin/images/amazon-logo.png',
				'is_active'       => in_array(
					'amazon-for-woocommerce/amazon-for-woocommerce.php',
					/**
										 * Function to get list of active plugins
										 *
										 * @param 'function'
										 * @return 'list'
										 * @since 1.0.0
										 */
										apply_filters( 'active_plugins', get_option( 'active_plugins' ) )
				),
				'is_installed'    => isset( $installed_plugins['amazon-for-woocommerce/amazon-for-woocommerce.php'] ) ? true : false,
			),
		);
		return $menus;
	}


	/**
	 *
	 * Function for displaying default page
	 */
	public function ced_amazon_accounts_page( $channel = 'amazon' ) {

		if ( 'amazon' == $channel ) {
			$fileAccounts = CED_AMAZON_DIRPATH . 'admin/partials/ced-amazon-accounts.php';
			if ( file_exists( $fileAccounts ) ) {
				require_once $fileAccounts;
			}
		}
	}

	/**
	 *
	 * Function to fetch next level Category
	 */
	public function ced_amazon_fetch_next_level_category() {

		$check_ajax = check_ajax_referer( 'ced-amazon-ajax-seurity-string', 'ajax_nonce' );
		if ( ! $check_ajax ) {
			return;
		}

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( WP_Filesystem() ) {
			global $wp_filesystem;
		}

		$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( $_POST['template_id'] ) : '';
		$user_id     = isset( $_POST['user_id'] ) ? sanitize_text_field( $_POST['user_id'] ) : '';
		$seller_id   = isset( $_POST['seller_id'] ) ? sanitize_text_field( $_POST['seller_id'] ) : '';

		$select_html = '';
		global $wpdb;

		$sanitized_array = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );

		$amazon_category_data = isset( $sanitized_array['category_data'] ) ? ( $sanitized_array['category_data'] ) : array();
		$level                = isset( $sanitized_array['level'] ) ? ( $sanitized_array['level'] ) : '';
		$template_id          = isset( $sanitized_array['template_id'] ) ? ( $sanitized_array['template_id'] ) : '';
		$next_level           = intval( $level ) + 1;

		$display_saved_values = isset( $sanitized_array['display_saved_values'] ) ? ( $sanitized_array['display_saved_values'] ) : '';

		$amzonCurlRequest = CED_AMAZON_DIRPATH . 'admin/amazon/lib/ced-amazon-curl-request.php';

		if ( file_exists( $amzonCurlRequest ) ) {
			require_once $amzonCurlRequest;
			$amzonCurlRequestInstance = new Ced_Amazon_Curl_Request();
		} else {
			return;
		}

		$template_type = '';
		if ( ! empty( $template_id ) ) {

			global $wpdb;
			$result                 = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles WHERE `id` = %s ", $template_id ), 'ARRAY_A' );
			$current_amazon_profile = isset( $result[0] ) ? $result[0] : array();

			$template_type = isset( $current_amazon_profile['template_type'] ) ? $current_amazon_profile['template_type'] : '';
			$file_url      = isset( $current_amazon_profile['file_url'] ) ? $current_amazon_profile['file_url'] : '';

		}

		if ( 'no' == $display_saved_values ) {
			$current_amazon_profile = array();
		}

		if ( is_array( $amazon_category_data ) && ! empty( $amazon_category_data ) ) {

			$category_id     = isset( $amazon_category_data['primary_category'] ) ? $amazon_category_data['primary_category'] : '';
			$sub_category_id = isset( $amazon_category_data['secondary_category'] ) ? $amazon_category_data['secondary_category'] : '';
			$browse_nodes    = isset( $amazon_category_data['browse_nodes'] ) ? $amazon_category_data['browse_nodes'] : '';
		}

		$url_array = array(

			4 => array(
				'url' => 'webapi/rest/v1/category-attribute/?shop_id=' . $user_id . '&category_id=' . $category_id . '&sub_category_id=' . $sub_category_id . '&browse_node_id=' . $browse_nodes . '&barcode_exemption=false',
				'key' => 'category_attributes',
			),
		);

		$modified_key = explode( '_', $url_array[ $next_level ]['key'] );
		$modified_key = ucfirst( $modified_key[0] ) . ' ' . ucfirst( $modified_key[1] );

		$ced_amzon_configuration_validated = get_option( 'ced_amzon_configuration_validated', array() );

		if ( empty( $seller_id ) ) {

			$ced_amazon_sellernext_shop_ids = get_option( 'ced_amazon_sellernext_shop_ids', array() );
			$seller_id                      = isset( $ced_amazon_sellernext_shop_ids[ $user_id ] ) ? $ced_amazon_sellernext_shop_ids[ $user_id ]['ced_mp_seller_key'] : '';

		}

		$userData    = $ced_amzon_configuration_validated[ $seller_id ];
		$userCountry = $userData['ced_mp_name'];

		if ( 4 == $next_level ) {

			if ( 'amazonTemplate' == $template_type ) {
				$this->ced_amazon_prepare_upload_template( $file_url, '', $display_saved_values, $template_id, $seller_id ) ;
				wp_die();
			}

			$upload_dir = wp_upload_dir();

			// fetch product template
			$product_template = $upload_dir['basedir'] . '/ced-amazon/templates/' . $userCountry . '/' . $category_id . '/products_template_fields.json';

			if ( 'no' == $display_saved_values || ! file_exists( $product_template ) ) {
				$amzonCurlRequestInstance->fetchProductTemplate( $category_id, $userCountry, $seller_id );
			}

			// fetch product template

			// save profile
			$dirname           = $upload_dir['basedir'] . '/ced-amazon/templates/' . $userCountry . '/' . $category_id . '/' . $sub_category_id;
			$fileName          = $dirname . '/products.json';
			$valid_values_file = $upload_dir['basedir'] . '/ced-amazon/templates/' . $userCountry . '/' . $category_id . '/valid_values.json';

			if ( ! file_exists( $fileName ) || ! file_exists( $valid_values_file ) ) {

				if ( ! is_dir( $dirname ) ) {
					wp_mkdir_p( $dirname );
				}

				wp_mkdir_p( $dirname );

				$amazon_profile_data_response         = $amzonCurlRequestInstance->ced_amazon_get_category( $url_array[ $next_level ]['url'], $user_id, $seller_id );
				$decoded_amazon_profile_data_response = json_decode( $amazon_profile_data_response, true );

				if ( $decoded_amazon_profile_data_response['status'] ) {
					$amazon_profile_data = $decoded_amazon_profile_data_response['data'];
				} else {
					echo esc_attr( wp_send_json( $decoded_amazon_profile_data_response ) );
					die;
				}

				$amazon_profile_template      = $amazon_profile_data['response'];
				$amazon_profile_template_data = wp_json_encode( $amazon_profile_template );

				$amazon_profile_valid_values      = $amazon_profile_data['valid_values'];
				$amazon_profile_valid_values_data = wp_json_encode( $amazon_profile_valid_values );

				if ( ! file_exists( $fileName ) && WP_Filesystem() ) {
					if ( $wp_filesystem ) {
						$wp_filesystem->put_contents( $fileName, $amazon_profile_template_data, FS_CHMOD_FILE );
					}
				}

				if ( ! file_exists( $valid_values_file ) && WP_Filesystem() ) {
					if ( $wp_filesystem ) {
						$wp_filesystem->put_contents( $valid_values_file, $amazon_profile_valid_values_data, FS_CHMOD_FILE );
					}
				}
			} elseif ( WP_Filesystem() && $wp_filesystem ) {

					$amazon_profile_template_data     = $wp_filesystem->get_contents( $fileName );
					$amazon_profile_valid_values_data = $wp_filesystem->get_contents( $valid_values_file );
			}

			$amazonCategoryList = json_decode( $amazon_profile_template_data, true );
			$valid_values       = json_decode( $amazon_profile_valid_values_data, true );

			if ( ! empty( $amazonCategoryList ) ) {

				global $wpdb;

				$results       = $wpdb->get_results( "SELECT DISTINCT meta_key FROM {$wpdb->prefix}postmeta", 'ARRAY_A' );
				$query         = $wpdb->get_results( $wpdb->prepare( "SELECT `meta_value` FROM  {$wpdb->prefix}postmeta WHERE `meta_key` LIKE %s", '_product_attributes' ), 'ARRAY_A' );
				$addedMetaKeys = get_option( 'CedUmbProfileSelectedMetaKeys', false );

				$optionalFields = array();
				$html           = '';

				foreach ( $amazonCategoryList as $fieldsKey => $fieldsArray ) {

					$select_html2 = $this->prepareProfileFieldsSection( $results, $query, $addedMetaKeys, $fieldsKey, $fieldsArray, $current_amazon_profile, $display_saved_values, $valid_values, $sub_category_id, $seller_id, $template_id );

					if ( $select_html2['display_heading'] ) {
						$select_html .= '<tr class="categoryAttributes "><th colspan="3" class="profileSectionHeading">
						<label style="font-size: 1.25rem;" >';

						$select_html .= $fieldsKey;
						$select_html .= ' Fields </label></th></tr>';

					}

					$select_html     .= $select_html2['html'];
					$optionalFields[] = $select_html2['optionsFields'];

				}

				if ( 'no' == $display_saved_values ) {

					if ( ! empty( $optionalFields ) ) {

						$html .= '<tr class="categoryAttributes"><th colspan="3" class="px-4 mt-4 py-6 sm:p-6 border-t-2 border-green-500" style="text-align:left;margin:0;">
						<label style="font-size: 1.25rem;" > Optional Fields </label></th></tr>';

						$html .= '<tr class="categoryAttributes" ><td></td><td><select id="optionalFields"><option  value="" >--Select--</option>';

						foreach ( $optionalFields as $optionalField ) {
							foreach ( $optionalField as $fieldsKey1 => $fieldsValue1 ) {
								$html .= '<optgroup label="' . $fieldsKey1 . '">';
								foreach ( $fieldsValue1 as $fieldsKey2 => $fieldsValue ) {

									$html .= '<option value="';
									$html .= htmlspecialchars( wp_json_encode( array( $fieldsKey1 => array( $fieldsKey2 => $fieldsValue[0] ) ) ) );
									$html .= '" >';
									$html .= $fieldsValue[0]['label'];
									$html .= ' (';
									$html .= $fieldsKey2;
									$html .= ') </option>';

								}

								$html .= '</optgroup>';
							}
						}

						$html        .= '</select></td>';
						$html        .= '<td><button class="button-primary ced_amazon_add_rows_button" id="';
						$modFieldsKey = str_replace( ' ', '', $fieldsKey );
						$html        .= $modFieldsKey;
						$html        .= '">Add Row</button></td></tr>';

					}

					$select_html .= $html;

				} elseif ( ! empty( $optionalFields ) ) {

						$select_html .= '<tr class="categoryAttributes"><th colspan="3" class="profileSectionHeading" >
						<label style="font-size: 1.25rem;" > Optional Fields </label></th></tr>';

						$optionalFieldsHtml = '';
						$saved_value        = json_decode( $current_amazon_profile['category_attributes_data'], true );

						$html .= '<tr class="categoryAttributes"><td></td><td><select id="optionalFields"><option  value="" >--Select--</option>';
					foreach ( $optionalFields as $optionalField ) {
						foreach ( $optionalField as $fieldsKey1 => $fieldsValue1 ) {
							$html .= '<optgroup label="' . $fieldsKey1 . '">';
							foreach ( $fieldsValue1 as $fieldsKey2 => $fieldsValue ) {

								if ( ! array_key_exists( $fieldsKey2, $saved_value ) ) {
									$html .= '<option  value="' . htmlspecialchars( wp_json_encode( array( $fieldsKey1 => array( $fieldsKey2 => $fieldsValue[0] ) ) ) ) . '" >' . $fieldsValue[0]['label'] . ' (' . $fieldsKey2 . ') </option>';

								} else {

									$prodileRowHTml      = $this->prepareProfileRows( $results, $query, $addedMetaKeys, $current_amazon_profile, 'yes', $valid_values, $sub_category_id, '', '', $fieldsKey2, $fieldsValue[0], 'yes', '', '', '' );
									$optionalFieldsHtml .= $prodileRowHTml;
								}
							}
							$html .= '</optgroup>';
						}
					}

						$html        .= '</select></td>';
						$modFieldsKey = str_replace( ' ', '', $fieldsKey );
						$html        .= '<td><button class="button-primary ced_amazon_add_rows_button" id="' . $modFieldsKey . '">Add Row</button></td></tr>';

						$select_html .= $optionalFieldsHtml;
						$select_html .= $html;
				}
			}

			echo esc_attr( wp_send_json_success( $select_html ) );
			wp_die();

		}

		wp_die();
	}

	/*
	 *
	 * Function to prepare profile fields section
	 */
	public function prepareProfileFieldsSection( $results, $query, $addedMetaKeys, $fieldsKey, $fieldsArray, $current_amazon_profile, $display_saved_values, $valid_values, $sub_category_id, $seller_id, $template_id ) {

		if ( ! empty( $fieldsArray ) ) {
			$profileSectionHtml = '';
			$optionalFields     = array();
			$display_heading    = 0;

			$ced_amazon_general_options_arr = get_option( 'ced_amazon_general_options', array() );
			$ced_amazon_general_options     = isset( $ced_amazon_general_options_arr[ $seller_id ] ) ? $ced_amazon_general_options_arr[ $seller_id ] : array();

			foreach ( $fieldsArray as $fieldsKey2 => $fieldsValue ) {

				if ( 'Mandantory' == $fieldsKey ) {

					$required = isset( $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ) && 'required' == $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ? ' [' . ucfirst( $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ) . ']' : '';
					$req      = isset( $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ) && 'required' == $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ? 'required' : '';

				} else {
					$required = isset( $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ) && 'required' == $fieldsValue['productTypeSpecific'][ $sub_category_id ]['condition'] ? ' [Suggested]' : '';
					$req      = '';

				}

				$globalValue = 'no';

				if ( ' [Required]' == $required || ' [Suggested]' == $required ) {
					if ( isset( $ced_amazon_general_options[ $fieldsKey2 ] ) && is_array( $ced_amazon_general_options[ $fieldsKey2 ] ) && ( '' !== $ced_amazon_general_options[ $fieldsKey2 ]['default'] || '' !== $ced_amazon_general_options[ $fieldsKey2 ]['metakey'] ) ) {

						$req            = '';
						$globalValue    = 'yes';
						$defaultGlobal  = $ced_amazon_general_options[ $fieldsKey2 ]['default'];
						$meta_keyGlobal = $ced_amazon_general_options[ $fieldsKey2 ]['metakey'];

					} else {
						$defaultGlobal  = '';
						$meta_keyGlobal = '';
					}

					$display_heading     = 1;
					$prodileRowHTml      = $this->prepareProfileRows( $results, $query, $addedMetaKeys, $current_amazon_profile, $display_saved_values, $valid_values, $sub_category_id, $req, $required, $fieldsKey2, $fieldsValue, $globalValue, $defaultGlobal, $meta_keyGlobal, '' );
					$profileSectionHtml .= $prodileRowHTml;

				} else {
					$optionalFields[ $fieldsKey ][ $fieldsKey2 ][] = $fieldsValue;
				}
			}

			return array(
				'html'            => $profileSectionHtml,
				'display_heading' => $display_heading,
				'optionsFields'   => $optionalFields,
			);

		}
	}


	/*
	 *
	 * Function to prepare profile rows
	 */

	public function prepareProfileRows( $results, $query, $addedMetaKeys, $current_amazon_profile, $display_saved_values, $valid_values, $sub_category_id, $req, $required, $fieldsKey2, $fieldsValue, $globalValue, $globalValueDefault, $globalValueMetakey, $cross = 'no' ) {

		$rowHtml  = '';
		$rowHtml .= '<tr class="categoryAttributes" id="ced_amazon_categories" data-attr="' . $req . '">';

		if ( 'yes' == $display_saved_values ) {
			$req = '';
		}

		$row_label = $fieldsValue['label'];

		$index = strpos( $fieldsKey2, '_custom_field' );
		if ( $index > -1 ) {
			$slug = substr( $fieldsKey2, 0, $index );
		} else {
			$slug = $fieldsKey2;
		}

		$rowHtml .= '<td class="ced_template_labels" >
		<label for="" class="">' . $row_label;
		$rowHtml .= wc_help_tip( $fieldsValue['accepted_value'], 'amazon-for-woocommerce' );

		$rowHtml .= '</label>';
		$rowHtml .= '<p class="cat_attr_para"> (' . $slug . ') </p></td>';

		if ( ! empty( $current_amazon_profile ) ) {
			$saved_value = json_decode( $current_amazon_profile['category_attributes_data'], true );
			$saved_value = isset( $saved_value[ $fieldsKey2 ] ) ? $saved_value[ $fieldsKey2 ] : '';
		} else {
			$saved_value = array();
		}

		$default_value = isset( $saved_value['default'] ) ? $saved_value['default'] : '';

		if ( empty( $default_value ) && 'yes' == $globalValue && empty( $template_id ) ) {
			$default_value = $globalValueDefault;
		}

		$rowHtml .= '<td>';
		if ( 'yes' == $cross ) {
			$rowHtml .= '<input type="hidden" name="ced_amazon_profile_data[' . $slug . '_custom_field][label]" value="' . $row_label . '" >';

		} else {
			$rowHtml .= '<input type="hidden" name="ced_amazon_profile_data[ref_attribute_list][' . $fieldsKey2 . ']" />';

		}

		if ( ( isset( $valid_values[ $fieldsKey2 ] ) && isset( $valid_values[ $fieldsKey2 ][ $sub_category_id ] ) ) || ( isset( $valid_values[ $row_label ] ) && isset( $valid_values[ $row_label ][ $sub_category_id ] ) ) ) {

			$rowHtml .= '<select class="custom_category_attributes_select2" id="' . $fieldsKey2 . '"  name="ced_amazon_profile_data[' . $fieldsKey2 . '][default]"><option value="">--Select--</option>';

			$optionLabels = ! empty( $valid_values[ $fieldsKey2 ][ $sub_category_id ] ) ? $valid_values[ $fieldsKey2 ][ $sub_category_id ] : $valid_values[ $row_label ][ $sub_category_id ];

			foreach ( $optionLabels as $acpt_key => $acpt_value ) {
				$selected = '';
				if ( $acpt_key == $default_value ) {
					$selected = 'selected';
				} elseif ( $acpt_key == $sub_category_id && 'feed_product_type' == $fieldsKey2 ) {
					$selected = 'selected';
				}
				$rowHtml .= '<option value="' . $acpt_key . '"' . $selected . '>' . $acpt_value . '</option>';
			}

			$rowHtml .= '</select>';

		} elseif ( ( isset( $valid_values[ $fieldsKey2 ] ) && isset( $valid_values[ $fieldsKey2 ]['all_cat'] ) ) || ( isset( $valid_values[ $row_label ] ) && isset( $valid_values[ $row_label ]['all_cat'] ) ) ) {

			$rowHtml .= '<select class="custom_category_attributes_select2" id="' . $fieldsKey2 . '"  name="ced_amazon_profile_data[' . $fieldsKey2 . '][default]"><option value="">--Select--</option>';

			$optionLabels = ! empty( $valid_values[ $fieldsKey2 ]['all_cat'] ) ? $valid_values[ $fieldsKey2 ]['all_cat'] : $valid_values[ $row_label ]['all_cat'];

			foreach ( $optionLabels as $acpt_key => $acpt_value ) {
				$selected = '';
				if ( $acpt_key == $default_value ) {
					$selected = 'selected';
				} elseif ( $acpt_key == $sub_category_id && 'feed_product_type' == $fieldsKey2 ) {
					$selected = 'selected';
				}
				$rowHtml .= '<option value="' . $acpt_key . '"' . $selected . '>' . $acpt_value . '</option>';
			}
			$rowHtml .= '</select>';

		} elseif ( 'feed_product_type' == $fieldsKey2 && empty( $default_value) ) {
				$rowHtml .= '<input class="custom_category_attributes_input" value="' . esc_attr( $sub_category_id ) . '" id="' . esc_attr( $fieldsKey2 ) . '" type="text" name="ced_amazon_profile_data[' . esc_attr( $fieldsKey2 ) . '][default]" />';
	
		} else { 
			$rowHtml .= '<input class="custom_category_attributes_input" value="' . esc_attr( $default_value ) . '" id="' . esc_attr( $fieldsKey2 ) . '" type="text" name="ced_amazon_profile_data[' . esc_attr( $fieldsKey2 ) . '][default]" />';
		}

		$rowHtml .= '</td>';

		$rowHtml        .= '<td>';
		$selected_value2 = isset( $saved_value['metakey'] ) ? $saved_value['metakey'] : '';

		if ( empty( $selected_value2 ) && 'yes' == $globalValue && empty( $template_id ) ) {
			$selected_value2 = $globalValueMetakey;
		}

		$selectDropdownHTML = '<select class="select2 custom_category_attributes_select"  name="ced_amazon_profile_data[' . $fieldsKey2 . '][metakey]">';

		foreach ( $results as $key2 => $meta_key ) {
			$post_meta_keys[] = $meta_key['meta_key'];
		}

		$custom_prd_attrb = array();
		$attrOptions      = array();

		if ( ! empty( $query ) ) {
			foreach ( $query as $key3 => $db_attribute_pair ) {
				foreach ( maybe_unserialize( $db_attribute_pair['meta_value'] ) as $key4 => $attribute_pair ) {
					if ( 1 != $attribute_pair['is_taxonomy'] ) {
						$custom_prd_attrb[] = $attribute_pair['name'];
					}
				}
			}
		}

		if ( $addedMetaKeys && 0 < count( $addedMetaKeys ) ) {
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

		/* select dropdown setup */
		ob_start();
		$selectDropdownHTML .= '<option value=""> -- select -- </option>';

		if ( is_array( $attrOptions ) ) {
			$selectDropdownHTML .= '<optgroup label="Global Attributes">';
			foreach ( $attrOptions as $attrKey => $attrName ) {
				$selected = '';
				if ( $selected_value2 == $attrKey ) {
					$selected = 'selected';
				}
				$selectDropdownHTML .= '<option ' . $selected . ' value="' . $attrKey . '">' . $attrName . '</option>';
			}
		}

		if ( ! empty( $custom_prd_attrb ) ) {
			$custom_prd_attrb    = array_unique( $custom_prd_attrb );
			$selectDropdownHTML .= '<optgroup label="Custom Attributes">';

			foreach ( $custom_prd_attrb as $key5 => $custom_attrb ) {
				$selected = '';
				if ( 'ced_cstm_attrb_' . esc_attr( $custom_attrb ) == $selected_value2 ) {
					$selected = 'selected';
				}
				$selectDropdownHTML .= '<option ' . $selected . ' value="ced_cstm_attrb_' . esc_attr( $custom_attrb ) . '">' . esc_html( $custom_attrb ) . '</option>';

			}
		}

		if ( ! empty( $post_meta_keys ) ) {
			$post_meta_keys      = array_unique( $post_meta_keys );
			$selectDropdownHTML .= '<optgroup label="Custom Fields">';
			foreach ( $post_meta_keys as $key7 => $p_meta_key ) {
				$selected = '';
				if ( $selected_value2 == $p_meta_key ) {
					$selected = 'selected';
				}
				$selectDropdownHTML .= '<option ' . $selected . ' value="' . $p_meta_key . '">' . $p_meta_key . '</option>';
			}
		}

		$selectDropdownHTML .= '</select>';

		$rowHtml .= $selectDropdownHTML;
		$rowHtml .= '</td>';
		$rowHtml .= '</tr>';

		return $rowHtml;
	}


	/*
	 *
	 * Function to create sellernext user
	 */
	public function ced_amazon_create_sellernext_user() {
		$check_ajax = check_ajax_referer( 'ced-amazon-ajax-seurity-string', 'ajax_nonce' );
		if ( ! $check_ajax ) {
			return;
		}

		$marketplace_id = isset( $_POST['marketplace_id'] ) ? sanitize_text_field( $_POST['marketplace_id'] ) : false;
		$seller_email   = isset( $_POST['seller_email'] ) ? sanitize_text_field( $_POST['seller_email'] ) : false;

		if ( ! empty( $marketplace_id && $seller_email ) ) {
			$username    = parse_url( get_site_url(), PHP_URL_HOST ) . '_' . $seller_email;
			$requestBody = array(
				'username'          => 'wooced_' . $username . rand(),
				'name'              => $seller_email,
				'marketplace'       => 'amazon',
				'app_id'            => 2,
				'platform'          => 'woocommerce',
				'website'           => get_site_url(),
				'email'             => $seller_email,
				'password'          => 'password',
				'domain'            => get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=setup-amazon',
				'confirmation_link' => 'https://amazon-sales-channel-api-backend.cifapps.com/apiconnect/uservalidate/validate',
			);

			$requestBody      = wp_json_encode( $requestBody );
			$response_payload = wp_remote_post(
				'https://amazon-sales-channel-api-backend.cifapps.com/apiconnect/user/create',
				array(
					'body'        => $requestBody,
					'headers'     => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJ1c2VyX2lkIjoiNjM2Y2ZjYTgxNzkwOGMwNzg0MGFhOWY3Iiwicm9sZSI6ImFwcCIsImlzcyI6Imh0dHBzOlwvXC9hcHBzLmNlZGNvbW1lcmNlLmNvbSIsInRva2VuX2lkIjoiNjM2ZDBiYWU5ZDAxYTY2OTIxMDI1ZDUyIn0.k3NiJh1LoqzapwQiXkqpgn-JIoFhwfntaWHcANS3PL49xuasdSpZhhVcMTerkRU0gcpLTMKTowvuM4GCcDtOYjhxPSpVVQvbCZKyc0JRNj4bTW7HX8fhDZskbqec8S2XyMhUGZcSz2qVwZRJd9ZlyxUeifk93L4QeYydgO4jt0RV_HlxlZXID_mSaLDyMmoeYw5jXggN7ihCkxQ2l7avHeyp-SJ7ZIVt_tR_Z4sKGd9q4E-hmJE9JBseUag_zwCqpKKPYRKPVMPU-q04vQ4WwBtRHYkGZVn9JeIbcJJHJYs6v4oQNWz9Gu2ONExq8g-bv34HtmMzW45Jr2diNsXA0A',

					),
					'httpversion' => '1.0',
					'sslverify'   => true,
					'timeout'     => 200,
				)
			);

			wc_get_logger()->info( wc_print_r( $response_payload, true ) );

			if ( isset( $response_payload['body'] ) && ! empty( $response_payload['body'] ) ) {
				$response_body = json_decode( $response_payload['body'], true );

				if ( $response_body['success'] && isset( $response_body['data'] ) ) {

					$sellernextResponse = array(
						'username'          => isset( $response_body['data'] ) && isset( $response_body['data']['username'] ) ? $response_body['data']['username'] : '',
						'name'              => isset( $response_body['data'] ) && isset( $response_body['data']['name'] ) ? $response_body['data']['name'] : '',
						'marketplace'       => isset( $response_body['data'] ) && isset( $response_body['data']['marketplace'] ) ? $response_body['data']['marketplace'] : '',
						'app_id'            => isset( $response_body['data'] ) && isset( $response_body['data']['app_id'] ) ? $response_body['data']['app_id'] : '',
						'email'             => isset( $response_body['data'] ) && isset( $response_body['data']['email'] ) ? $response_body['data']['email'] : '',
						'password'          => isset( $response_body['data'] ) && isset( $response_body['data']['password'] ) ? $response_body['data']['password'] : '',
						'domain'            => isset( $response_body['data'] ) && isset( $response_body['data']['domain'] ) ? $response_body['data']['domain'] : '',
						'confirmation_link' => isset( $response_body['data'] ) && isset( $response_body['data']['confirmation_link'] ) ? $response_body['data']['confirmation_link'] : '',

					);

					update_option( 'ced_amazon_current_marketplace_id', $marketplace_id );
					update_option( 'ced_amazon_sellernext_user_creation_response', $sellernextResponse );

					if ( isset( $response_body['data']['apps'][0] ) && ! empty( $response_body['data']['apps'][0]['refresh_token'] ) ) {
						$refresh_token = $response_body['data']['apps'][0]['refresh_token'];

						wp_send_json(
							array(
								'status'        => 'success',
								'message'       => 'Redirecting to Amazon...',
								'refresh_token' => $refresh_token,
							)
						);
					}
				}
				if ( 'false' == $response_body['success'] || '' == $response_body['success'] ) {
					wp_send_json(
						array(
							'status'  => 'failed',
							'message' => $response_body['data']['errors'][0],
						)
					);
				}
			}
		}

		wp_die();
	}


	/*
	 *
	 * Function to get sellernext access token
	 */
	public function ced_amazon_sellernext_get_access_token_and_redirect() {
		$check_ajax = check_ajax_referer( 'ced-amazon-ajax-seurity-string', 'ajax_nonce' );
		if ( ! $check_ajax ) {
			return;
		}
		$marketplace_id     = isset( $_POST['marketplace_id'] ) ? sanitize_text_field( $_POST['marketplace_id'] ) : false;
		$refresh_token      = isset( $_POST['refresh_token'] ) ? sanitize_text_field( $_POST['refresh_token'] ) : false;
		$marketplace_region = isset( $_POST['marketplace_region'] ) ? sanitize_text_field( $_POST['marketplace_region'] ) : false;
		$end_point          = isset( $_POST['end_pt'] ) ? sanitize_text_field( $_POST['end_pt'] ) : false;

		$shop_name       = isset( $_POST['shop_name'] ) ? sanitize_text_field( $_POST['shop_name'] ) : false;
		$name_value      = isset( $_POST['name_value'] ) ? sanitize_text_field( $_POST['name_value'] ) : false;
		$country_name    = isset( $_POST['country_name'] ) ? sanitize_text_field( $_POST['country_name'] ) : false;
		$marketplace_url = isset( $_POST['marketplace_url'] ) ? sanitize_text_field( $_POST['marketplace_url'] ) : false;

		if ( ! empty( $refresh_token ) && ! empty( $marketplace_id ) ) {
			$access_token_response = wp_safe_remote_get(
				'https://amazon-sales-channel-api-backend.cifapps.com/core/token/getTokenByRefresh/?bearer=' . $refresh_token,
				array(

					'httpversion' => '1.0',
					'sslverify'   => false,
					'timeout'     => 200,
				)
			);

			wc_get_logger()->info( wc_print_r( $access_token_response, true ) );

			if ( ! empty( $access_token_response['body'] ) ) {
				$access_token_data = json_decode( $access_token_response['body'], true );

				if ( 'true' == $access_token_data['success'] ) {
					$access_token         = $access_token_data['data']['token'];
					$access_token_decoded = json_decode( base64_decode( str_replace( '_', '/', str_replace( '-', '+', explode( '.', $access_token )[1] ) ) ), true );

					$sub_app_id = $access_token_decoded['sub_app_id'];

					// saving data

					$ced_amazon_accounts_data = get_option( 'ced_amazon_account_data', array() );

					$current_amazon_account = array(
						'marketplace_region'        => $marketplace_region,
						'seller_next_refresh_token' => $refresh_token,
						'seller_next_access_token'  => $access_token,
						'sub_app_id'                => $sub_app_id,
						'service_url'               => $end_point,
						'marketplace_id'            => $marketplace_id,
						'country_name'              => $country_name,
						'country_value'             => $name_value,
						'ced_mp_name'               => $shop_name,
						'marketplace_url'           => $marketplace_url,

					);

					if ( empty( $ced_amazon_accounts_data ) ) {

						$ced_amazon_accounts_data = array(
							$marketplace_id => $current_amazon_account,
						);

					} else {
						$ced_amazon_accounts_data[ $marketplace_id ] = $current_amazon_account;
					}

					update_option( 'ced_amazon_account_data', $ced_amazon_accounts_data );

					// Prepare the redirect URL and redirect user
					if ( ! empty( $access_token ) ) {
						$redirect_url = 'https://amazon-sales-channel-api-backend.cifapps.com/apiconnect/request/auth?region=' . $marketplace_region . '&version=new&state=test&sAppId=' . $sub_app_id . '&bearer=' . $access_token . '&marketplace_id=' . $marketplace_id;
						wp_send_json(
							array(
								'status'       => 'success',
								'redirect_url' => $redirect_url,
							)
						);
					}
				}
			}
		}

		wp_die();
	}


	/*
	 *
	 *Function to fetch next level Category
	 */
	public function my_amazon_cron_schedules( $schedules ) {
		if ( ! isset( $schedules['ced_amazon_1min'] ) ) {
			$schedules['ced_amazon_1min'] = array(
				'interval' => 1 * 60,
				'display'  => __( 'Once every 1 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_amazon_6min'] ) ) {
			$schedules['ced_amazon_6min'] = array(
				'interval' => 6 * 60,
				'display'  => __( 'Once every 6 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_amazon_10min'] ) ) {
			$schedules['ced_amazon_10min'] = array(
				'interval' => 10 * 60,
				'display'  => __( 'Once every 10 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_amazon_15min'] ) ) {
			$schedules['ced_amazon_15min'] = array(
				'interval' => 15 * 60,
				'display'  => __( 'Once every 15 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_amazon_30min'] ) ) {
			$schedules['ced_amazon_30min'] = array(
				'interval' => 30 * 60,
				'display'  => __( 'Once every 30 minutes' ),
			);
		}
		return $schedules;
	}

	/*
	 *
	 * Function to update wizard step
	 */
	public function ced_amazon_update_current_step() {
		$check_ajax = check_ajax_referer( 'ced-amazon-ajax-seurity-string', 'ajax_nonce' );
		if ( ! $check_ajax ) {
			return;
		}

		$current_step = isset( $_POST['current_step'] ) ? sanitize_text_field( $_POST['current_step'] ) : false;
		$user_id      = isset( $_POST['user_id'] ) ? sanitize_text_field( $_POST['user_id'] ) : false;

		if ( ! empty( $current_step ) && ! empty( $user_id ) ) {

			$sellernextShopIds                                     = get_option( 'ced_amazon_sellernext_shop_ids', array() );
			$sellernextShopIds[ $user_id ]['ced_amz_current_step'] = $current_step;

			update_option( 'ced_amazon_sellernext_shop_ids', $sellernextShopIds );

			return wp_json_encode(
				array(
					'message' => 'updated',
					'status'  => '200',
				)
			);
		} else {
			return wp_json_encode(
				array(
					'message' => 'failed',
					'status'  => '400',
				)
			);
		}
	}

	/*
	 *
	 * Function to get orders.
	 */
	public function ced_amazon_get_orders() {
		$check_ajax = check_ajax_referer( 'ced-amazon-ajax-seurity-string', 'ajax_nonce' );
		if ( ! $check_ajax ) {
			return;
		}

		$seller_id  = isset( $_POST['seller_id'] ) ? sanitize_text_field( $_POST['seller_id'] ) : '';
		$mplocation = '';
		if ( ! empty( $seller_id ) ) {
			$mplocation_arr = explode( '|', $seller_id );
			$mplocation     = isset( $mplocation_arr[1] ) ? $mplocation_arr[0] : '';
		}

		$file_name = plugin_dir_path( __FILE__ ) . 'amazon/lib/class-order-manager.php';
		if ( file_exists( $file_name ) ) {

			require_once $file_name;
			$class_name = 'Ced_Umb_Amazon_Order_Manager';
			if ( class_exists( $class_name ) ) {

				$OrderInstance = Ced_Umb_Amazon_Order_Manager::get_instance();
				if ( ! is_wp_error( $OrderInstance ) ) {

					$notices = $OrderInstance->fetchOrders( $mplocation, $cron = false, $amazon_order_id = '', $seller_id );

					if ( $notices ) {
						$message = __( 'Order fetch requested successfully.', 'amazon-for-woocommerce' );
						$classes = 'success is-dismissable';
						echo wp_json_encode(
							array(
								'message' => $message,
								'status'  => 'success',
							)
						);
					} else {
						$message = __( 'No result found!', 'amazon-for-woocommerce' );
						$classes = 'success is-dismissable';
						echo wp_json_encode(
							array(
								'message' => $message,
								'status'  => 'No Results',
							)
						);
					}
				} else {
					
					$message = __( 'An unexpected error occurred. Please try again.', 'amazon-for-woocommerce' );
					$classes = 'error is-dismissable';
					echo wp_json_encode(
						array(
							'message' => $message,
							'status'  => 'error',
						)
					);

				}
			} else {
				$message = __( 'Class missing to perform operation, please check if extension configured successfully!', 'amazon-for-woocommerce' );
				echo wp_json_encode(
					array(
						'message' => $message,
						'status'  => 'error',
					)
				);

			}
		} else {
			$message = __( 'Please check if selected marketplace is active!', 'amazon-for-woocommerce' );
			echo wp_json_encode(
				array(
					'message' => $message,
					'status'  => 'error',
				)
			);

		}

		wp_die();
	}

	public function ced_amazon_remove_account_from_integration() {
		$check_ajax = check_ajax_referer( 'ced-amazon-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {

			$seller_id        = isset( $_POST['seller_id'] ) ? sanitize_text_field( $_POST['seller_id'] ) : false;
			$sellernextShopId = isset( $_POST['sellernextShopId'] ) ? sanitize_text_field( $_POST['sellernextShopId'] ) : false;

			if ( ! empty( $seller_id ) && ! empty( $sellernextShopId ) ) {

				$ced_amazon_sellernext_shop_ids = get_option( 'ced_amazon_sellernext_shop_ids', array() );
				unset( $ced_amazon_sellernext_shop_ids[ $sellernextShopId ] );
				update_option( 'ced_amazon_sellernext_shop_ids', $ced_amazon_sellernext_shop_ids );

				update_option( 'ced_amazon_mode_of_operation', '' );

				if ( wp_next_scheduled( 'ced_amazon_order_scheduler_job_' . $seller_id ) ) {
					wp_clear_scheduled_hook( 'ced_amazon_order_scheduler_job_' . $seller_id );
				}
				if ( wp_next_scheduled( 'ced_amazon_existing_products_sync_job_' . $seller_id ) ) {
					wp_clear_scheduled_hook( 'ced_amazon_existing_products_sync_job_' . $seller_id );
				}
				if ( wp_next_scheduled( 'ced_amazon_import_products_job_' . $seller_id ) ) {
					wp_clear_scheduled_hook( 'ced_amazon_import_products_job_' . $seller_id );
				}
				if ( wp_next_scheduled( 'ced_amazon_inventory_scheduler_job_' . $seller_id ) ) {
					wp_clear_scheduled_hook( 'ced_amazon_inventory_scheduler_job_' . $seller_id );
				}

				if ( function_exists( 'as_has_scheduled_action' ) ) {

					if ( as_has_scheduled_action( 'ced_amazon_inventory_scheduler_job_' . $seller_id ) ) {
						as_unschedule_all_actions( 'ced_amazon_inventory_scheduler_job_' . $seller_id );
					}
				}

				$amazon_accounts = get_option( 'ced_amzon_configuration_validated', array() );
				if ( is_array( $amazon_accounts ) && isset( $amazon_accounts[ $seller_id ] ) ) {

					// Delete option value if account is current active account
					$mplocation_temp = get_option( 'ced_umb_amazon_bulk_profile_loc_temp', true );
					if ( $mplocation_temp == $seller_id ) {
						delete_option( 'ced_umb_amazon_bulk_profile_loc_temp' );
					}
					$mplocation = get_option( 'ced_umb_amazon_bulk_profile_loc', true );
					if ( $mplocation == $amazon_accounts[ $seller_id ]['ced_mp_name'] ) {
						delete_option( 'ced_umb_amazon_bulk_profile_loc' );
					}

					unset( $amazon_accounts[ $seller_id ] );
					update_option( 'ced_amzon_configuration_validated', $amazon_accounts );
				}

				// Delete scheduler option
				delete_option( 'ced_amazon_inventory_scheduler_job_' . $seller_id );
				delete_option( 'ced_amazon_order_scheduler_job_' . $seller_id );
				delete_option( 'ced_amazon_existing_products_sync_job_' . $seller_id );

				// Delete account participation option value

				wp_send_json(
					array(
						'status'  => 'success',
						'message' => 'Account Deleted Successfully',
						'title'   => 'Account Deleted',
					)
				);

			} elseif ( ! empty( $sellernextShopId ) ) {

				$ced_amazon_sellernext_shop_ids = get_option( 'ced_amazon_sellernext_shop_ids', array() );
				unset( $ced_amazon_sellernext_shop_ids[ $sellernextShopId ] );
				update_option( 'ced_amazon_sellernext_shop_ids', $ced_amazon_sellernext_shop_ids );

				wp_send_json(
					array(
						'status'  => 'success',
						'message' => 'Account Deleted Successfully',
						'title'   => 'Account Deleted',
					)
				);

			} else {
				wp_send_json(
					array(
						'status'  => 'error',
						'message' => 'User ID not found',
						'title'   => 'Invalid User ID',
					)
				);
			}
		}
	}


	public function ced_amazon_add_custom_profile_rows() {

		$check_ajax = check_ajax_referer( 'ced-amazon-ajax-seurity-string', 'ajax_nonce' );
		if ( ! $check_ajax ) {
			return;
		}


		$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$custom_field    = isset( $sanitized_array['custom_field'] ) ? $sanitized_array['custom_field'] : array();
		$category_id     = isset( $_POST['primary_cat'] ) ? sanitize_text_field( $_POST['primary_cat'] ) : '';
		$sub_category_id = isset( $_POST['secondary_cat'] ) ? sanitize_text_field( $_POST['secondary_cat'] ) : '';

		$user_id   = isset( $_POST['userid'] ) ? sanitize_text_field( $_POST['userid'] ) : '';
		$seller_id = '';

		$file_url = isset( $_POST['fileUrl'] ) ? sanitize_text_field( $_POST['fileUrl'] ) : '';

	
		if ( empty( $seller_id ) ) {
			$ced_amazon_sellernext_shop_ids = get_option( 'ced_amazon_sellernext_shop_ids', array() );
			$seller_id                      = isset( $ced_amazon_sellernext_shop_ids[ $user_id ] ) ? $ced_amazon_sellernext_shop_ids[ $user_id ]['ced_mp_seller_key'] : '';

		}

		$this->ced_amazon_profile_dropdown( '', '', $sanitized_array, $custom_field, $category_id, $sub_category_id, 'yes', $user_id, $seller_id, $file_url );

		wp_die();
	}

	public function ced_amazon_profile_dropdown( $field_id = '', $required = '', $sanitized_array = array(), $custom_field = array(), $category_id = '', $sub_category_id = '', $display_hidden = 'no', $user_id = '', $seller_id = '', $file_url = '' ) {

		global $wpdb;
		$results       = $wpdb->get_results( "SELECT DISTINCT meta_key FROM {$wpdb->prefix}postmeta", 'ARRAY_A' );
		$query         = $wpdb->get_results( $wpdb->prepare( "SELECT `meta_value` FROM  {$wpdb->prefix}postmeta WHERE `meta_key` LIKE %s", '_product_attributes' ), 'ARRAY_A' );
		$addedMetaKeys = get_option( 'CedUmbProfileSelectedMetaKeys', false );

		$row_html         = '';
		$mod_custom_field = array_values( $custom_field );

		foreach ( $mod_custom_field[0] as $custom_key => $custom_value ) {

			$index = strpos( $custom_key, '_custom_field' );
			if ( $index > -1 ) {
				$slug = substr( $custom_key, 0, $index );
			} else {
				$slug = $custom_key;
			}

			$optionLabel = $custom_value['label'];

			$row_html .= '<tr class="categoryAttributes" id="ced_amazon_categories" >
				<td class="ced_template_labels" >
					<label for="" class="">' . $optionLabel . ' (' . $slug . ') ';

			$row_html .= wc_help_tip( $custom_value['definition'], 'amazon-for-woocommerce' );
			$row_html .= '</label>';
			$row_html .= '</td>';

			$ced_amzon_configuration_validated = get_option( 'ced_amzon_configuration_validated', array() );

			if ( empty( $seller_id ) ) {
				$ced_amazon_sellernext_shop_ids = get_option( 'ced_amazon_sellernext_shop_ids', array() );
				$seller_id                      = isset( $ced_amazon_sellernext_shop_ids[ $user_id ] ) ? $ced_amazon_sellernext_shop_ids[ $user_id ]['ced_mp_seller_key'] : '';

			}

			$userData    = isset( $ced_amzon_configuration_validated[ $seller_id ] ) ? $ced_amzon_configuration_validated[ $seller_id ] : array();
			$userCountry = isset( $userData['ced_mp_name'] ) ? $userData['ced_mp_name'] : '';

			$upload_dir = wp_upload_dir();


			if ( empty( $file_url ) ) {

				$valid_values_file = $upload_dir['basedir'] . '/ced-amazon/templates/' . $userCountry . '/' . $category_id . '/valid_values.json';
				$valid_values      = file_get_contents( $valid_values_file );
				$valid_values2     = json_decode( $valid_values, true );

			} else {

				
				$sub_category_id = array_keys( $custom_value['productTypeSpecific'] ); 
				$sub_category_id = $sub_category_id[0]; 


				global $wpdb;

				$results       = $wpdb->get_results( "SELECT DISTINCT meta_key FROM {$wpdb->prefix}postmeta", 'ARRAY_A' );
				$query         = $wpdb->get_results( $wpdb->prepare( "SELECT `meta_value` FROM  {$wpdb->prefix}postmeta WHERE `meta_key` LIKE %s", '_product_attributes' ), 'ARRAY_A' );
				$addedMetaKeys = get_option( 'CedUmbProfileSelectedMetaKeys', false );
				
				$attributes = wc_get_attribute_taxonomies();
				$attributes = isset($attributes) ? $attributes : array();
				
				$ced_amazon_general_options = get_option( 'ced_amazon_general_options', array() );
			
				// $ced_amazon_general_options = isset( $ced_amazon_general_options[$seller_id] ) ? $ced_amazon_general_options[$seller_id] : array();
				$current_amazon_profile = array();

				if ( !empty( $template_id ) ) {

					global $wpdb;
					$tableName              = $wpdb->prefix . 'ced_amazon_profiles';
					$result                 = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles WHERE `id` = %s ", $template_id ), 'ARRAY_A' );
					$current_amazon_profile = isset( $result[0] ) ? $result[0] : array();

				} 
				
				
				$results       = isset($results) ? $results : array();
				$query         = isset($query) ? $query : array();
				$addedMetaKeys = isset($results) ? $addedMetaKeys : array();
				

				$post = array(
					'fileUrl' => $file_url,
					'fileName' => '',
					'display_saved_values' => 'no',
					'template_id' => '',
					'seller_id' => $seller_id,
					'last' => true,
					'session' => '',
					'attributes' => $attributes,
					'ced_amazon_general_options' => $ced_amazon_general_options,
					'addedMetaKeys' => $addedMetaKeys,
					'results' => $results,
					'query' => $query,
					'current_amazon_profile' => $current_amazon_profile,
					
				);
				
				
				$curl = curl_init();
				curl_setopt_array( $curl, array(
					CURLOPT_URL => 'http://localhost/Amazon%20Upload%20Template/api/validValues.php',
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => '',
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 0,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => 'POST',
					CURLOPT_POSTFIELDS => json_encode($post),
					CURLOPT_HTTPHEADER => array(
						'Authorization: application/json',
					),
					)
				);

				$valid_value_response = curl_exec($curl);
				$valid_value_response = json_decode( $valid_value_response, true );

				curl_close($curl);
				
				if ( isset( $valid_value_response['success'] ) ) {
					
					if ( isset( $valid_value_response['valid_values_data'] ) ) {
						$valid_values2 = $valid_value_response['valid_values_data'];
						
					} else {
						$valid_values2 = array();
					}
					
				}


			}



			if ( ( isset( $valid_values2[ $custom_key ] ) && isset( $valid_values2[ $custom_key ][ $sub_category_id ] ) ) ||  ( isset( $valid_values2[ $optionLabel ] ) && isset( $valid_values2[ $optionLabel ][ $sub_category_id ] ) ) ) {

				$row_html    .= '<td><select class="custom_category_attributes_select2" id="' . $custom_key . '"  name="ced_amazon_profile_data[' . $custom_key . '][default]" ><option value="">--Select--</option>';
				$optionValues = !empty( $valid_values2[ $custom_key ][ $sub_category_id ] ) ? $valid_values2[ $custom_key ][ $sub_category_id ] : $valid_values2[ $optionLabel ][ $sub_category_id ] ;

				foreach ( $optionValues as $acpt_key => $acpt_value ) {
					$selected = '';

					$row_html .= '<option value="' . $acpt_key . '">' . $acpt_value . '</option>';
				}

				$row_html .= '</select>
								<span >
								   <i class="fa fa-info-circle" data-tooltip-content="' . $custom_value['definition'] . '" ></i>
							    </span> 
				            </td>';

			} elseif ( ( isset( $valid_values2[ $custom_key ] ) && isset( $valid_values2[ $custom_key ][ 'all_cat' ] ) ) ||  ( isset( $valid_values2[ $optionLabel ] ) && isset( $valid_values2[ $optionLabel ][ 'all_cat' ] ) ) ) {

				$row_html    .= '<td><select class="custom_category_attributes_select2" id="' . $custom_key . '"  name="ced_amazon_profile_data[' . $custom_key . '][default]" ><option value="">--Select--</option>';
				$optionValues = !empty( $valid_values2[ $custom_key ][ 'all_cat' ] ) ? $valid_values2[ $custom_key ][ 'all_cat' ] : $valid_values2[ $optionLabel ][ 'all_cat' ] ;

				foreach ( $optionValues as $acpt_key => $acpt_value ) {
					$selected = '';

					$row_html .= '<option value="' . $acpt_key . '">' . $acpt_value . '</option>';
				}

				$row_html .= '</select>
								<span >
								   <i class="fa fa-info-circle" data-tooltip-content="' . $custom_value['definition'] . '" ></i>
							    </span> 
				            </td>';

			} else {

				$row_html .= '<td>';

				if ( 'yes' == $display_hidden ) {
					$row_html .= '<input type="hidden" name="ced_amazon_profile_data[ref_attribute_list][' . $custom_key . ']">';
				} else {
					$row_html .= '<input type="hidden" name="ced_amazon_profile_data[' . $custom_key . '][label]" value="' . $optionLabel . '" >';
				}

				$row_html .= '<input class="custom_category_attributes_input" value="" id="' . $custom_key . '" type="text" name="ced_amazon_profile_data[' . $custom_key . '][default]" >
							<span class="app ">
						<i class="fa fa-info-circle" data-tooltip-content="' . $custom_value['definition'] . '" ></i>
						</span> 
				</td>';

			}


			$row_html          .= '<td>';
			$selectDropdownHTML = '<select class="select2 custom_category_attributes_select"  name="ced_amazon_profile_data[' . esc_attr( $custom_key ) . '][metakey]" >';

			foreach ( $results as $key2 => $meta_key ) {
				$post_meta_keys[] = $meta_key['meta_key'];
			}

			$custom_prd_attrb = array();
			$attrOptions      = array();

			if ( ! empty( $query ) ) {
				foreach ( $query as $key3 => $db_attribute_pair ) {
					foreach ( maybe_unserialize( $db_attribute_pair['meta_value'] ) as $key4 => $attribute_pair ) {
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

			/* select dropdown setup */
			ob_start();
			$fieldID             = '{{*fieldID}}';
			$selectId            = $fieldID . '_attibuteMeta';
			$selectDropdownHTML .= '<option value=""> -- select -- </option>';
			if ( is_array( $attrOptions ) ) {
				$selectDropdownHTML .= '<optgroup label="Global Attributes">';
				foreach ( $attrOptions as $attrKey => $attrName ) {
					$selected            = '';
					$selectDropdownHTML .= '<option ' . $selected . ' value="' . $attrKey . '">' . $attrName . '</option>';
				}
			}

			if ( ! empty( $custom_prd_attrb ) ) {
				$custom_prd_attrb    = array_unique( $custom_prd_attrb );
				$selectDropdownHTML .= '<optgroup label="Custom Attributes">';

				foreach ( $custom_prd_attrb as $key5 => $custom_attrb ) {
					$selected            = '';
					$selectDropdownHTML .= '<option ' . $selected . ' value="ced_cstm_attrb_' . esc_attr( $custom_attrb ) . '">' . esc_html( $custom_attrb ) . '</option>';

				}
			}

			if ( ! empty( $post_meta_keys ) ) {
				$post_meta_keys      = array_unique( $post_meta_keys );
				$selectDropdownHTML .= '<optgroup label="Custom Fields">';
				foreach ( $post_meta_keys as $key7 => $p_meta_key ) {
					$selected            = '';
					$selectDropdownHTML .= '<option ' . $selected . ' value="' . $p_meta_key . '">' . $p_meta_key . '</option>';
				}
			}

			$selectDropdownHTML .= '</select>';
			$row_html           .= $selectDropdownHTML;

			$row_html .= '</td></tr>';

			echo wp_json_encode(
				array(
					'succes' => true,
					'data'   => $row_html,
				)
			);

			wp_die();

		}
	}


	public function ced_amazon_update_template() {
		$check_ajax = check_ajax_referer( 'ced-amazon-ajax-seurity-string', 'ajax_nonce' );
		if ( ! $check_ajax ) {
			return;
		}

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( WP_Filesystem() ) {
			global $wp_filesystem;
		}

		$next_level = 4;

		$category_id     = isset( $_POST['primary_cat'] ) ? sanitize_text_field( $_POST['primary_cat'] ) : '';
		$sub_category_id = isset( $_POST['secondary_cat'] ) ? sanitize_text_field( $_POST['secondary_cat'] ) : '';
		$browse_nodes    = isset( $_POST['browse_nodes'] ) ? sanitize_text_field( $_POST['browse_nodes'] ) : '';

		$seller_id = isset( $_POST['seller_id'] ) ? sanitize_text_field( $_POST['seller_id'] ) : '';
		$user_id   = isset( $_POST['user_id'] ) ? sanitize_text_field( $_POST['user_id'] ) : '';

		$ced_amzon_configuration_validated = get_option( 'ced_amzon_configuration_validated', array() );

		if ( empty( $seller_id ) ) {
			$ced_amazon_sellernext_shop_ids = get_option( 'ced_amazon_sellernext_shop_ids', array() );
			$seller_id                      = isset( $ced_amazon_sellernext_shop_ids[ $user_id ] ) ? $ced_amazon_sellernext_shop_ids[ $user_id ]['ced_mp_seller_key'] : '';

		}

		$userData    = $ced_amzon_configuration_validated[ $seller_id ];
		$userCountry = $userData['ced_mp_name'];

		$amzonCurlRequest = CED_AMAZON_DIRPATH . 'admin/amazon/lib/ced-amazon-curl-request.php';

		if ( file_exists( $amzonCurlRequest ) ) {
			require_once $amzonCurlRequest;
			$amzonCurlRequestInstance = new Ced_Amazon_Curl_Request();
		} else {
			return;
		}

		if ( empty( $user_id ) ) {
			echo wp_json_encode(
				array(
					'success' => false,
					'message' => 'Invalid Shop Id',
					'status'  => 'error',
				)
			);
			die;
		}

		$url_array = array(
			4 => array(
				'url' => 'webapi/rest/v1/category-attribute/?shop_id=' . $user_id . '&category_id=' . $category_id . '&sub_category_id=' . $sub_category_id . '&browse_node_id=' . $browse_nodes . '&barcode_exemption=false',
				'key' => 'category_attributes',
			),
		);

		$upload_dir = wp_upload_dir();

		$dirname  = $upload_dir['basedir'] . '/ced-amazon/templates/' . $userCountry . '/' . $category_id . '/' . $sub_category_id;
		$fileName = $dirname . '/products.json';

		if ( ! is_dir( $dirname ) ) {
			wp_mkdir_p( $dirname );
		}

		$amazon_profile_data_response         = $amzonCurlRequestInstance->ced_amazon_get_category( $url_array[ $next_level ]['url'], $user_id, $seller_id );
		$decoded_amazon_profile_data_response = json_decode( $amazon_profile_data_response, true );

		if ( $decoded_amazon_profile_data_response['status'] ) {
			$amazon_profile_data = $decoded_amazon_profile_data_response['data'];
		} else {
			echo esc_attr( wp_send_json( $decoded_amazon_profile_data_response ) );
			die;
		}

		$amazon_profile_template = isset( $amazon_profile_data['response'] ) ? $amazon_profile_data['response'] : array();

		// Update product flat file template stricture json file
		$amzonCurlRequestInstance->fetchProductTemplate( $category_id, $userCountry, $seller_id );

		if ( empty( $amazon_profile_template ) ) {
			echo wp_json_encode(
				array(
					'success' => false,
					'message' => 'Unable to fetch Data.',
					'status'  => 'error',
				)
			);
			die;
		}

		$amazon_profile_template_data = wp_json_encode( $amazon_profile_template );

		if ( ! file_exists( $fileName ) && WP_Filesystem() ) {
			if ( $wp_filesystem ) {
				$wp_filesystem->put_contents( $fileName, $amazon_profile_template_data, FS_CHMOD_FILE );
			}
		}

		echo wp_json_encode(
			array(
				'success' => true,
				'message' => 'Product Template has been updated',
				'status'  => 'success',
			)
		);
		die;
	}

	/**
	 * Upload custom amazon template and process template data.
	 *
	 * @name ced_amazon_prepare_template()
	 * @since 1.0.0
	 */


	public function ced_amazon_checkSellerNextCategoryApi() {
		$check_ajax = check_ajax_referer( 'ced-amazon-ajax-seurity-string', 'ajax_nonce' );
		if ( ! $check_ajax ) {
			return;
		}

		$seller_id = isset( $_POST['seller_id'] ) ? sanitize_text_field( $_POST['seller_id'] ) : '';
		$user_id   = isset( $_POST['user_id'] ) ? sanitize_text_field( $_POST['user_id'] ) : '';

		$select_html = '';
		$next_level  = 1;
		global $wpdb;
		$sanitized_array = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
		$tableName       = $wpdb->prefix . 'ced_amazon_accounts';

		$amazon_category_data = isset( $sanitized_array['category_data'] ) ? ( $sanitized_array['category_data'] ) : array();

		$amzonCurlRequest = CED_AMAZON_DIRPATH . 'admin/amazon/lib/ced-amazon-curl-request.php';

		if ( file_exists( $amzonCurlRequest ) ) {
			require_once $amzonCurlRequest;
			$amzonCurlRequestInstance = new Ced_Amazon_Curl_Request();
		} else {
			return;
		}

		if ( ! empty( $template_id ) ) {

			global $wpdb;
			$tableName              = $wpdb->prefix . 'ced_amazon_profiles';
			$result                 = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles WHERE `id` = %s ", $template_id ), 'ARRAY_A' );
			$current_amazon_profile = isset( $result[0] ) ? $result[0] : array();
		}

		if ( is_array( $amazon_category_data ) && ! empty( $amazon_category_data ) ) {

			$category_id     = isset( $amazon_category_data['primary_category'] ) ? $amazon_category_data['primary_category'] : '';
			$sub_category_id = isset( $amazon_category_data['secondary_category'] ) ? $amazon_category_data['secondary_category'] : '';
			$browse_nodes    = isset( $amazon_category_data['browse_nodes'] ) ? $amazon_category_data['browse_nodes'] : '';
		}

		$url_array = array(
			1 => array(
				'url' => 'webapi/rest/v1/category/?shop_id=' . $user_id,
				'key' => 'primary_category',
			),
		);

		$modified_key = explode( '_', $url_array[ $next_level ]['key'] );
		$modified_key = ucfirst( $modified_key[0] ) . ' ' . ucfirst( $modified_key[1] );

		$ced_amzon_configuration_validated = get_option( 'ced_amzon_configuration_validated', array() );

		if ( empty( $seller_id ) ) {
			$ced_amazon_sellernext_shop_ids = get_option( 'ced_amazon_sellernext_shop_ids', array() );
			$seller_id                      = isset( $ced_amazon_sellernext_shop_ids[ $user_id ] ) ? $ced_amazon_sellernext_shop_ids[ $user_id ]['ced_mp_seller_key'] : '';

		}
		$amazonCategoryListResponse        = $amzonCurlRequestInstance->ced_amazon_get_category( $url_array[ $next_level ]['url'], $user_id, $seller_id );
		$decodedAmazonCategoryListResponse = json_decode( $amazonCategoryListResponse, true );

		if ( $decodedAmazonCategoryListResponse['status'] ) {
			$amazonCategoryList = $decodedAmazonCategoryListResponse['data'];
		} else {
			echo esc_attr( wp_send_json( $decodedAmazonCategoryListResponse ) );
			die;
		}

		echo esc_attr( wp_send_json_success( $amazonCategoryList ) );
		die;
	}


	/** Get product id based on Amazon Seller SKU field of product level (if product SKU not exist) **/
	public function get_amazon_seller_sku( $meta_key = '', $meta_value = '' ) {

		if ( empty( $meta_key ) || empty( $meta_value ) ) {
			return;
		}

		global $wpdb;
		$productId = $wpdb->get_var( $wpdb->prepare( " SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = %s AND meta_value = %s", $meta_key, $meta_value ) );

		return $productId;
	}


	/**
	 * Function to verify seller
	 */

	public function ced_amazon_seller_verification() {
		$check_ajax = check_ajax_referer( 'ced-amazon-ajax-seurity-string', 'ajax_nonce' );
		if ( ! $check_ajax ) {
			return;
		}

		$user_id           = isset( $_POST['user_id'] ) ? sanitize_text_field( $_POST['user_id'] ) : '';
		$sellernextShopIds = get_option( 'ced_amazon_sellernext_shop_ids', array() );

		if ( ! empty( $user_id ) ) {

			$marketplace_id = isset( $sellernextShopIds[ $user_id ] ) ? $sellernextShopIds[ $user_id ]['marketplace_id'] : '';

			$ced_amazon_accounts_data = get_option( 'ced_amazon_account_data', array() );
			$access_token             = isset( $ced_amazon_accounts_data[ $marketplace_id ] ) && isset( $ced_amazon_accounts_data[ $marketplace_id ]['seller_next_access_token'] ) ? $ced_amazon_accounts_data[ $marketplace_id ]['seller_next_access_token'] : '';

			$amz_connected_accounts = wp_safe_remote_get(
				'https://amazon-sales-channel-api-backend.cifapps.com/webapi/rest/v1/connected-accounts/?remote_shop_id[]=' . $user_id,
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $access_token,
						'timeout'       => 120,
					),
				)
			);


			// newly added code starts

			$all_data = get_option( 'ced_amazon_account_data', true );

			if ( is_wp_error( $amz_connected_accounts ) ) {
				wp_send_json_success(
					array(
						'status'  => false,
						'message' => 'unable to verify you',
					)
				);
			}

			if ( ! empty( $amz_connected_accounts['body'] ) ) {

				$amz_connected_accounts_data = json_decode( $amz_connected_accounts['body'], true );
				$amz_connected_data          = isset( $amz_connected_accounts_data['data'] ) && isset( $amz_connected_accounts_data['data'][0] ) ? $amz_connected_accounts_data['data'][0] : array();

				if ( empty( $amz_connected_data ) ) {
					wp_send_json_success(
						array(
							'status'  => false,
							'message' => 'unable to verify you',
						)
					);

				}

				$seller_id      = isset( $amz_connected_data['seller_id'] ) ? $amz_connected_data['seller_id'] : '' ;
				$marketplace_id = isset( $amz_connected_data['marketplace_id'] ) ? $amz_connected_data['marketplace_id'] : '';
				$ced_mp_name    = isset( $ced_amazon_accounts_data[ $marketplace_id ] ) ? $ced_amazon_accounts_data[ $marketplace_id ]['ced_mp_name'] : '';

				if ( ! empty( $ced_mp_name ) && ! empty( $seller_id ) ) {

					$config_array_key                                   = $ced_mp_name . '|' . $seller_id;
					$sellernextShopIds[ $user_id ]['ced_mp_name']       = $ced_mp_name;
					$sellernextShopIds[ $user_id ]['ced_mp_seller_key'] = $config_array_key;

					update_option( 'ced_amazon_sellernext_shop_ids', $sellernextShopIds );


				} else {
					wp_send_json_success(
						array(
							'status'  => false,
							'message' => 'unable to verify marketplace/seller id',
						)
					);
				}
			} 

			$refresh_token = '';

			if ( is_wp_error( $amz_connected_accounts ) ) {
				$refresh_token = '';
			} elseif ( ! empty( $amz_connected_accounts['body'] ) ) {
		
				$amz_connected_accounts_data = json_decode( $amz_connected_accounts['body'], true );
		
				if ( '1' == $amz_connected_accounts_data['success'] ) {
		
					$amz_connected_data = isset( $amz_connected_accounts_data['data'] ) && isset( $amz_connected_accounts_data['data'][0] ) ? $amz_connected_accounts_data['data'][0]['apps'][0] : array();
					$refresh_token      = ! empty( $amz_connected_data ) && isset( $amz_connected_data['refresh_token'] ) ? $amz_connected_data['refresh_token'] : '';
					$config_array       = array(
						'ced_umb_amazon_marketplace_configuration' => 1,
						'key_id'                                   => $amz_connected_data['access_key'],
						'merchant_id'                              => $amz_connected_data['seller_id'],
						'secret_key'                               => $amz_connected_data['secret_key'],
						'auth_token'                               => $amz_connected_data['mws_auth_token'],
						'spapi_oauth_code'                         => $amz_connected_data['spapi_oauth_code'],
						'seller_next_shop_id'                      => $user_id,
						'amazon_refresh_token'                     => $amz_connected_data['refresh_token'],
						'amazon_access_token'                      => $amz_connected_data['access_token'],
						'ced_umb_amazon_validate_marketplace_config' => 'Validate',
					);
		
					$ced_amazon_data_store                                = $all_data[ $amz_connected_data['marketplace_id'] ];
					$final_amazon_configuration                           = array_merge( $ced_amazon_data_store, $config_array );
					$config_array_key                                     = $final_amazon_configuration['ced_mp_name'] . '|' . $final_amazon_configuration['merchant_id'];
					$final_amazon_configuration_save                      = array();
					$final_amazon_configuration_save[ $config_array_key ] = $final_amazon_configuration;
					$configuration_validated_array                        = get_option( 'ced_amzon_configuration_validated', array() );
		
					if ( is_array( $configuration_validated_array ) && ! empty( $configuration_validated_array ) ) {
						$configuration_validated_array = array_replace( $configuration_validated_array, $final_amazon_configuration_save );
					} else {
						$configuration_validated_array = $final_amazon_configuration_save;
					}
		
		
					update_option( 'ced_amzon_configuration_validated', $configuration_validated_array );
					
		
				} else {
					$refresh_token = '';
				}
			}



			if ( ! isset( $refresh_token ) ) {
				$refresh_token = '';
			}


			$configuration_validated_array = get_option( 'ced_amzon_configuration_validated', array() );

			$sellerDetails     = isset( $configuration_validated_array[ $config_array_key ] ) ? $configuration_validated_array[ $config_array_key ] : array();
			$shop_id           = isset( $sellerDetails['seller_next_shop_id'] ) ? $sellerDetails['seller_next_shop_id'] : '';
			$merchant_id       = isset( $sellerDetails['merchant_id'] ) ? $sellerDetails['merchant_id'] : '';
			$refresh_token     = isset( $sellerDetails['amazon_refresh_token'] ) ? $sellerDetails['amazon_refresh_token'] : '';
			$marketplace_id    = isset( $sellerDetails['marketplace_id'] ) ? $sellerDetails['marketplace_id'] : '';
			$ced_mp_name       = isset( $sellerDetails['ced_mp_name'] ) ? $sellerDetails['ced_mp_name'] : '';
			$sellernextShopIds = get_option( 'ced_amazon_sellernext_shop_ids', array() );
			$amzonCurlRequest  = CED_AMAZON_DIRPATH . 'admin/amazon/lib/ced-amazon-curl-request.php';

			if ( file_exists( $amzonCurlRequest ) ) {
				require_once $amzonCurlRequest;
				$amzonCurlRequestInstance = new Ced_Amazon_Curl_Request();
			} else {
				return;
			}


			$payload         = array();
			$originalPayload = array();


			if ( ! empty( $refresh_token ) && ! empty( $marketplace_id ) && ! empty( $merchant_id ) ) {

				$originalPayload = $amzonCurlRequestInstance->getMarketplaceParticipations( $refresh_token, $marketplace_id, $merchant_id );
				if ( $originalPayload['success'] ) {

					$payload = isset( $originalPayload['data'] ) && isset( $originalPayload['data']['payload'] ) ? json_decode( wp_json_encode( $originalPayload['data']['payload'] ), true ) : array();

				}
			}


			$accountData         = array();
			$sellerParticipation = false;

			$participate_accounts = array();

			if ( ! empty( $payload ) && is_array( $payload ) && isset( $originalPayload['success'] ) && $originalPayload['success'] ) {

				foreach ( $payload as $index => $accountsConnected ) {
					if ( $accountsConnected['marketplace']['id'] == $marketplace_id ) {
						$accountData         = $accountsConnected;
						$sellerParticipation = isset( $accountsConnected['participation']['isParticipating'] ) ? $accountsConnected['participation']['isParticipating'] : false;


						$current_mp_participation = array( $config_array_key => $sellerParticipation );
						if ( is_array( $participate_accounts ) && ! empty( $participate_accounts ) ) {
							$participate_accounts = array_replace( $participate_accounts, $current_mp_participation );
						} else {
							$participate_accounts = $current_mp_participation;
						}

						$sellernextShopIds[ $user_id ]['marketplaces_participation'] = $participate_accounts;
						// $sellernextShopIds[ $user_id ]['ced_amz_current_step']       = 4;

						update_option( 'ced_amazon_sellernext_shop_ids', $sellernextShopIds );

						unset( $all_data[ $amz_connected_data['marketplace_id'] ] );
						update_option( 'ced_amazon_account_data', $all_data );

						wp_send_json_success(
							array(
								'status' => true,
								'data'   => array(
									'seller_id'         => $seller_id,
									'marketplace_id'    => $marketplace_id,
									'ced_mp_name'       => $ced_mp_name,
									'user_id'           => $user_id,
									// 'sellernextShopIds' => $sellernextShopIds,
								),
							)
						);

					}
				}
			} else {

				$ced_amzon_configuration_validated = get_option( 'ced_amzon_configuration_validated', array() );
				$sellernextShopIds                 = get_option( 'ced_amazon_sellernext_shop_ids', array() );

				$ced_mp_seller_key = '';

				if ( ! empty( $user_id ) && isset( $sellernextShopIds[ $user_id ] ) ) {
					$ced_mp_seller_key = isset( $sellernextShopIds[ $user_id ] ) && isset( $sellernextShopIds[ $user_id ]['ced_mp_seller_key'] ) ? $sellernextShopIds[ $user_id ]['ced_mp_seller_key'] : '';

					if ( isset( $ced_mp_seller_key ) && ! empty( $ced_mp_seller_key ) ) {
						unset( $ced_amzon_configuration_validated[ $ced_mp_seller_key ] );

						update_option( 'ced_amzon_configuration_validated', $ced_amzon_configuration_validated );
					}
				}

				unset( $sellernextShopIds[ $user_id ] );
				update_option( 'ced_amazon_sellernext_shop_ids', $sellernextShopIds );


				wp_send_json_success(
					array(
						'status'  => false,
						'message' => 'unable to verify marketplace/seller id',
					)
				);

			}
			

			// newly added code ends

			die;


		}
	}



	/**
	 * Quick view feed response using modal in feeds table.
	 *
	 * @name ced_amazon_view_feed_response()
	 * @since 1.0.0
	 */
	public function ced_amazon_view_feed_response() {
		$check_ajax = check_ajax_referer( 'ced-amazon-ajax-seurity-string', 'ajax_nonce' );
		if ( ! $check_ajax ) {
			return;
		}

		require_once CED_AMAZON_DIRPATH . 'admin/amazon/lib/class-feed-manager.php';

		$feed_id   = isset( $_POST['feed_id'] ) ? sanitize_text_field( $_POST['feed_id'] ) : '';
		$seller_id = isset( $_POST['seller_id'] ) ? sanitize_text_field( $_POST['seller_id'] ) : '';

		if ( empty( $feed_id ) || empty( $seller_id ) ) {
			$html_response = '<h6>Error: Feed id or seller id missing!</h6>';
			wp_send_json_success( $html_response );
			wp_die();
		}

		global $wpdb;
		$tableName        = $wpdb->prefix . 'ced_amazon_feeds';
		$feed_request_ids = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_feeds WHERE `feed_id` = %d", $feed_id ), 'ARRAY_A' );

		if ( ! is_array( $feed_request_ids ) || ! is_array( $feed_request_ids[0] ) ) {
			$html_response = '<h6>Error: Feed details not found!</h6>';
			wp_send_json_success( $html_response );
			wp_die();
		}

		$feed_request_id = $feed_request_ids[0];
		$main_id         = $feed_request_id['id'];
		$feed_type       = $feed_request_id['feed_action'];
		$location_id     = $feed_request_id['feed_location'];
		$response        = $feed_request_id['response'];
		$response        = json_decode( $response, true );
		$marketplace     = 'amazon_spapi';

		$response_format = false;
		if ( ! empty( $feed_id ) ) {

			if ( isset( $response['status'] ) && 'DONE' == $response['status'] ) {
				$response        = $response;
				$response_format = true;

			} else {
				$feed_manager = Ced_Umb_Amazon_Feed_Manager::get_instance();
				$response     = $feed_manager->getFeedItemsStatusSpApi( $feed_id, $feed_type, $location_id, $marketplace, $seller_id );

				if ( isset( $response['status'] ) && 'DONE' == $response['status'] ) {
					$response_format = true;
				}
				$response_data = wp_json_encode( $response );
				$wpdb->update( $tableName, array( 'response' => $response_data ), array( 'id' => $main_id ) );
			}

			if ( $response_format ) {

				if ( 'POST_FLAT_FILE_LISTINGS_DATA' == $feed_type ) {

					$tab_response_data = explode( "\n", $response['body'] );

					$first_row_data         = explode( "\t", $tab_response_data[0] );
					$second_row_data        = explode( "\t", $tab_response_data[1] );
					$third_row_data         = explode( "\t", $tab_response_data[2] );
					$response_heading       = isset( $first_row_data[0] ) ? $first_row_data[0] : '';
					$processed_record_lable = isset( $second_row_data[1] ) ? $second_row_data[1] : '';
					$processed_record_value = isset( $second_row_data[3] ) ? $second_row_data[3] : '';
					$success_record_lable   = isset( $third_row_data[1] ) ? $third_row_data[1] : '';
					$success_record_value   = isset( $third_row_data[3] ) ? $third_row_data[3] : '';

					$tab_response_html  = '';
					$tab_error_code_arr = array();
					foreach ( $tab_response_data as $tabKey => $tabValue ) {

						$line_data = explode( "\t", $tabValue );
						if ( 'Feed Processing Summary' == $line_data[0] || 'Feed Processing Summary:' == $line_data[0] ) {
							continue;
						} elseif ( empty( $line_data[0] ) || '' == $line_data[0] ) {
							continue;
						} elseif ( 'original-record-number' == $line_data[0] ) {
							continue;
						} elseif ( in_array( $line_data[2], $tab_error_code_arr ) ) {
							continue;
						} elseif ( ! empty( $line_data[2] ) ) {
								$tab_error_code_arr[] = $line_data[2];
								$tab_response_html   .= '<tr><td>' . esc_attr( $line_data[2] ) . '</td>';
								$tab_response_html   .= '<td >' . esc_attr( $line_data[4] ) . '</td></tr>';
						}
					}

					if ( isset( $tab_response_html ) && '' != $tab_response_html ) {
						$tableHtml = '<table class="wp-list-table widefat striped table-view-list posts" >
						<thead class="table-dark">
						<tr>
						<th scope="col">Error code</th>
						<th scope="col">Error message</th>
						</tr>
						</thead>
						<tbody>';

						$tableHtml .= $tab_response_html;
						$tableHtml .= '</tbody>
						</table>';
					} else {
						$tableHtml = '<p> Successful records: ' . $success_record_value . '</p>';
					}
				} elseif ( 'JSON_LISTINGS_FEED' == $feed_type ) {

					$feed_response = json_decode( $response['body'], true );

					if ( isset( $feed_response ) && ! empty( $feed_response ) ) {

						$summary_data   = isset( $feed_response['summary'] ) ? $feed_response['summary'] : array();
						$success_record = '';
						if ( ! empty( $summary_data ) ) {
							foreach ( $summary_data as $summary_label => $summary_fields ) {
								if ( 'messagesAccepted' == $summary_label ) {
									$success_record = $summary_fields;
								}
							}
						}

						$error_data          = isset( $feed_response['issues'] ) ? $feed_response['issues'] : array();
						$error_html          = '';
						$json_error_code_arr = array();
						if ( ! empty( $error_data ) ) {
							foreach ( $error_data as $error_label => $error_fields ) {
								$error_code = isset( $error_fields['code'] ) ? $error_fields['code'] : '';
								$message    = isset( $error_fields['message'] ) ? $error_fields['message'] : '';

								if ( in_array( $error_code, $json_error_code_arr ) ) {
									continue;
								}

								if ( isset( $error_code ) && ! empty( $error_code ) ) {
									$json_error_code_arr[] = $error_code;

									$error_html .= '<tr><td>' . esc_attr( $error_code ) . '</td>';
									$error_html .= '<td >' . esc_attr( $message ) . '</td></tr>';
								}
							}
						}

						if ( isset( $error_html ) && '' != $error_html ) {
							$tableHtml = '<table class="wp-list-table widefat striped table-view-list posts" >
							<thead class="table-dark">
							<tr>
							<th scope="col">Error code</th>
							<th scope="col">Error message</th>
							</tr>
							</thead>
							<tbody>';

							$tableHtml .= $error_html;
							$tableHtml .= '</tbody>
							</table>';
						} else {
							$tableHtml = '<h4>Successful records: ' . $success_record . '</h4>';
						}
					} else {
						$tableHtml = $feed_response;
					}
				} else {

					$sxml = simplexml_load_string( $response['body'], 'SimpleXMLElement', LIBXML_NOCDATA );

					$arrayResponse = wp_json_encode( $sxml );
					$arrayResponse = json_decode( $arrayResponse, true );

					if ( isset( $arrayResponse['Message'] ) && ! empty( $arrayResponse['Message'] ) ) {

						$processingSummary     = isset( $arrayResponse['Message'] ) && isset( $arrayResponse['Message']['ProcessingReport'] ) && isset( $arrayResponse['Message']['ProcessingReport']['ProcessingSummary'] ) ? $arrayResponse['Message']['ProcessingReport']['ProcessingSummary'] : array();
						$processingSummaryHtml = '';

						$results     = isset( $arrayResponse['Message']['ProcessingReport']['Result'][0] ) ? $arrayResponse['Message']['ProcessingReport']['Result'] : $arrayResponse['Message']['ProcessingReport'];
						$resultsHtml = '';

						$success_record = '';
						if ( ! empty( $processingSummary ) ) {
							foreach ( $processingSummary as $label => $fields ) {
								$processingSummaryHtml .= $label . ' : ' . $fields . '<br/>';
								if ( 'MessagesSuccessful' == $label ) {
									$success_record = $fields;
								}
							}
						}

						$xml_error_code_arr = array();
						if ( ! empty( $results ) ) {

							foreach ( $results as $label => $fields ) {

								if ( 'Result' == $label || is_numeric( $label ) ) {
									if ( is_object( $fields ) ) {
										$fields = $this->xml2array( $fields );
									}

									$resultMessageCode = isset( $fields['ResultMessageCode'] ) ? $fields['ResultMessageCode'] : '';
									$resultDescription = isset( $fields['ResultDescription'] ) ? $fields['ResultDescription'] : '';

									if ( in_array( $resultMessageCode, $xml_error_code_arr ) ) {
										continue;
									}

									if ( isset( $resultMessageCode ) && ! empty( $resultMessageCode ) ) {
										$xml_error_code_arr[] = $resultMessageCode;
										$resultsHtml         .= '<tr><td>' . esc_attr( $resultMessageCode ) . '</td>';
										$resultsHtml         .= '<td >' . esc_attr( $resultDescription ) . '</td></tr>';
									}
								}
							}
						}

						if ( isset( $resultsHtml ) && '' != $resultsHtml ) {
							$tableHtml = '<table class="wp-list-table widefat striped table-view-list posts" >
							<thead class="table-dark">
							<tr>
							<th scope="col">Error code</th>
							<th scope="col">Error message</th>
							</tr>
							</thead>
							<tbody>';

							$tableHtml .= $resultsHtml;
							$tableHtml .= '</tbody>
							</table>';
						} else {
							$tableHtml = '<h4>Successful records: ' . $success_record . '</h4>';
						}
					}
				}
			} elseif ( isset( $response['feed_id'] ) && ! empty( $response['feed_id'] ) ) {
					$tableHtml = '<table class="wp-list-table widefat striped table-view-list posts" >
					<thead class="table-dark">
					<tr>
					<th scope="col">Feed Id </th>
					<th scope="col">Feed Type</th>
					<th scope="col">Feed Status</th>
					</tr>
					</thead>
					<tbody>
					<tr>
					<td>' . esc_attr( $response['feed_id'] ) . '</td>
					<td>' . esc_attr( $response['feed_action'] ) . '</td>
					<td>' . esc_attr( $response['status'] ) . '</td>
					</tr>

					</tbody>
					</table>';

			} else {
				$message   = isset( $response['body'] ) ? $response['body'] : $response['message'];
				$tableHtml = '<p><b>' . esc_attr( $message ) . '</b></p>';
			}
		}

		// Final html response preparation
		$html_response = $tableHtml;
		wp_send_json_success( $html_response );
		wp_die();
	}

	// Function for XML feed response format
	public function xml2array( $xmlObject, $out = array() ) {
		foreach ( (array) $xmlObject as $index => $node ) {
			$out[ $index ] = ( is_object( $node ) ) ? xml2array( $node ) : $node;
		}

		return $out;
	}


	/**
	 * Product specific all feed actions using modal in products table.
	 *
	 * @name ced_amazon_product_specific_feeds()
	 * @since 1.0.0
	 */
	public function ced_amazon_product_specific_feeds() {
		$check_ajax = check_ajax_referer( 'ced-amazon-ajax-seurity-string', 'ajax_nonce' );
		if ( ! $check_ajax ) {
			return;
		}

		$product_id = isset( $_POST['product_id'] ) ? sanitize_text_field( $_POST['product_id'] ) : '';
		$seller_id  = isset( $_POST['seller_id'] ) ? sanitize_text_field( $_POST['seller_id'] ) : '';
		$user_id    = isset( $_POST['user_id'] ) ? sanitize_text_field( $_POST['user_id'] ) : '';

		if ( empty( $product_id ) || empty( $seller_id ) || empty( $user_id ) ) {
			$html_response = '<h6>Error: Product_id / Seller_id / User_id is missing!</h6>';
			wp_send_json_success( $html_response );
			wp_die();
		}

		$seller_id_val = str_replace( '|', '_', $seller_id );

		$product_feeds = get_post_meta( $product_id, 'ced_amazon_feed_actions_' . $seller_id_val, true );

		$tab_response_html = '';
		if ( is_array( $product_feeds ) && ! empty( $product_feeds ) ) {

			foreach ( $product_feeds as $feed_action => $feed_id ) {
				$tab_response_html .= '<tr><td>' . esc_attr( $feed_action ) . '</td>';
				$tab_response_html .= '<td ><a class="feed-view" target="_blank" href="' . get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=feed-view&feed-id=' . esc_attr( $feed_id ) . '&user_id=' . esc_attr( $user_id ) . '&seller_id=' . esc_attr( $seller_id ) . '" > ' . esc_attr( $feed_id ) . '</a></td></tr>';
			}
		}

		$table_html  = '<table class="wp-list-table widefat striped table-view-list posts"  >
		<thead class="table-dark">
		<tr>
		<th scope="col">Feed action</th>
		<th scope="col">Feed Id</th>
		</tr>
		</thead>
		<tbody>';
		$table_html .= $tab_response_html;
		$table_html .= '</tbody>
		</table>';

		// Final html preparation
		$html_response = $table_html;

		wp_send_json_success( $html_response );
		wp_die();
	}


	/**
	 * Amazon order detail with amazon data using modal in orders table.
	 *
	 * @name ced_amazon_order_detail_amazon_data()
	 * @since 1.0.0
	 */
	public function ced_amazon_order_detail_amazon_data() {
		$check_ajax = check_ajax_referer( 'ced-amazon-ajax-seurity-string', 'ajax_nonce' );
		if ( ! $check_ajax ) {
			return;
		}

		$order_id  = isset( $_POST['order_id'] ) ? sanitize_text_field( $_POST['order_id'] ) : '';
		$seller_id = isset( $_POST['seller_id'] ) ? sanitize_text_field( $_POST['seller_id'] ) : '';
		$user_id   = isset( $_POST['user_id'] ) ? sanitize_text_field( $_POST['user_id'] ) : '';

		if ( empty( $order_id ) || empty( $seller_id ) || empty( $user_id ) ) {
			$html_response = '<h6>Error: Order_id / Seller_id / User_id is missing!</h6>';
			wp_send_json_success( $html_response );
			wp_die();
		}

		// Order info
		$amazon_order_data     = get_post_meta( $order_id, 'umb_amazon_shippied_data', true );
		$amazon_order_id       = isset( $amazon_order_data['amazon_order_id'] ) ? $amazon_order_data['amazon_order_id'] : '-';
		$order_purchase_date   = isset( $amazon_order_data['order_detail']['PurchaseDate'] ) ? $amazon_order_data['order_detail']['PurchaseDate'] : '-';
		$order_lastupdate_date = isset( $amazon_order_data['order_detail']['LastUpdateDate'] ) ? $amazon_order_data['order_detail']['LastUpdateDate'] : '-';
		$order_total           = isset( $amazon_order_data['order_detail']['OrderTotal']['Amount'] ) ? $amazon_order_data['order_detail']['OrderTotal']['Amount'] : '-';
		$order_currency        = isset( $amazon_order_data['order_detail']['OrderTotal']['CurrencyCode'] ) ? $amazon_order_data['order_detail']['OrderTotal']['CurrencyCode'] : '-';
		$order_status          = isset( $amazon_order_data['order_detail']['OrderStatus'] ) ? $amazon_order_data['order_detail']['OrderStatus'] : '-';
		$order_buyer_name      = isset( $amazon_order_data['order_detail']['BuyerName'] ) && ! empty( $amazon_order_data['order_detail']['BuyerName'] ) ? $amazon_order_data['order_detail']['BuyerName'] : '-';
		$order_buyer_email     = isset( $amazon_order_data['order_detail']['BuyerEmail'] ) ? $amazon_order_data['order_detail']['BuyerEmail'] : '-';
		$order_fulfillment     = isset( $amazon_order_data['order_detail']['FulfillmentChannel'] ) ? $amazon_order_data['order_detail']['FulfillmentChannel'] : '-';

		// Shipping info
		$shipping_service = isset( $amazon_order_data['order_detail']['ShipServiceLevel'] ) ? $amazon_order_data['order_detail']['ShipServiceLevel'] : '-';
		$order_shipping   = isset( $amazon_order_data['order_detail']['ShippingAddress'] ) ? $amazon_order_data['order_detail']['ShippingAddress'] : '';
		$customer_name    = isset( $order_shipping['Name'] ) && ! empty( $order_shipping['Name'] ) ? $order_shipping['Name'] : '-';
		$address_line_1   = isset( $order_shipping['AddressLine1'] ) ? $order_shipping['AddressLine1'] : '-';
		$address_line_2   = isset( $order_shipping['AddressLine2'] ) ? $order_shipping['AddressLine2'] : '-';
		$city             = isset( $order_shipping['City'] ) ? $order_shipping['City'] : '-';
		$county           = isset( $order_shipping['County'] ) ? $order_shipping['County'] : '-';
		$district         = isset( $order_shipping['District'] ) ? $order_shipping['District'] : '-';
		$state_or_region  = isset( $order_shipping['StateOrRegion'] ) ? $order_shipping['StateOrRegion'] : '-';
		$postal_code      = isset( $order_shipping['PostalCode'] ) ? $order_shipping['PostalCode'] : '-';
		$country_code     = isset( $order_shipping['CountryCode'] ) ? $order_shipping['CountryCode'] : '-';
		$phone            = isset( $order_shipping['Phone'] ) ? $order_shipping['Phone'] : '-';

		// Order items info
		$order_items      = isset( $amazon_order_data['order_item_detail'] ) ? $amazon_order_data['order_item_detail'] : array();
		$order_items_html = '';
		if ( is_array( $order_items ) && ! empty( $order_items ) ) {

			foreach ( $order_items as $order_item_key => $order_item_value ) {
				$asin           = isset( $order_item_value['ASIN'] ) ? $order_item_value['ASIN'] : '-';
				$seller_sku     = isset( $order_item_value['SellerSKU'] ) ? $order_item_value['SellerSKU'] : '-';
				$title          = isset( $order_item_value['Title'] ) ? $order_item_value['Title'] : '-';
				$quantity       = isset( $order_item_value['QuantityOrdered'] ) ? $order_item_value['QuantityOrdered'] : '-';
				$item_price     = isset( $order_item_value['ItemPrice']['Amount'] ) ? $order_item_value['ItemPrice']['Amount'] : '-';
				$item_tax       = isset( $order_item_value['ItemTax']['Amount'] ) ? $order_item_value['ItemTax']['Amount'] : '-';
				$shipping_price = isset( $order_item_value['ShippingPrice']['Amount'] ) ? $order_item_value['ShippingPrice']['Amount'] : '-';
				$shipping_tax   = isset( $order_item_value['ShippingTax']['Amount'] ) ? $order_item_value['ShippingTax']['Amount'] : '-';
				$discount       = isset( $order_item_value['PromotionDiscount']['Amount'] ) ? $order_item_value['PromotionDiscount']['Amount'] : '-';

				$order_items_html .= '<tr><td>' . esc_attr( $asin ) . '</td>';
				$order_items_html .= '<td>' . esc_attr( $seller_sku ) . '</td>';
				$order_items_html .= '<td>' . esc_attr( $title ) . '</td>';
				$order_items_html .= '<td>' . esc_attr( $quantity ) . '</td>';
				$order_items_html .= '<td>' . esc_attr( $item_price ) . '</td>';
				$order_items_html .= '<td>' . esc_attr( $item_tax ) . '</td>';
				$order_items_html .= '<td>' . esc_attr( $shipping_price ) . '</td>';
				$order_items_html .= '<td>' . esc_attr( $shipping_tax ) . '</td>';
				$order_items_html .= '<td>' . esc_attr( $discount ) . '</td></tr>';
			}
		}

		$order_heading = '<h6>Woo Order Id: #' . esc_attr( $order_id ) . '</h6>';

		$html_response  = ''; // Final html response variable
		$html_response .= $order_heading;

		// Order detail html
		$order_detail_html  = '';
		$order_detail_html .= '<tr><td><strong>Order ID:</strong></td>';
		$order_detail_html .= '<td>' . esc_attr( $amazon_order_id ) . '</td></tr>';
		$order_detail_html .= '<tr><td><strong>Purchase Date:</strong></td>';
		$order_detail_html .= '<td>' . esc_attr( $order_purchase_date ) . '</td></tr>';
		$order_detail_html .= '<tr><td><strong>Last Update Date:</strong></td>';
		$order_detail_html .= '<td>' . esc_attr( $order_lastupdate_date ) . '</td></tr>';
		$order_detail_html .= '<tr><td><strong>Order Status:</strong></td>';
		$order_detail_html .= '<td>' . esc_attr( $order_status ) . '</td></tr>';
		$order_detail_html .= '<tr><td><strong>Fulfillment Channel:</strong></td>';
		$order_detail_html .= '<td>' . esc_attr( $order_fulfillment ) . '</td></tr>';
		$order_detail_html .= '<tr><td><strong>Buyer Name:</strong></td>';
		$order_detail_html .= '<td>' . esc_attr( $order_buyer_name ) . '</td></tr>';
		$order_detail_html .= '<tr><td><strong>Buyer Email:</strong></td>';
		$order_detail_html .= '<td>' . esc_attr( $order_buyer_email ) . '</td></tr>';
		$order_detail_html .= '<tr><td><strong>Order Total:</strong></td>';
		$order_detail_html .= '<td>' . esc_attr( $order_currency ) . ' ' . esc_attr( $order_total ) . '</td></tr>';

		$order_detail_table_html  = '<table class="wp-list-table widefat striped table-view-list posts"  >
		<thead class="table-dark">
		<tr>
		<th scope="col" colspan="2">Order Detail:</th>
		</tr>
		</thead>
		<tbody>';
		$order_detail_table_html .= $order_detail_html;
		$order_detail_table_html .= '</tbody>
		</table>';

		// Html response preparation with order detail
		$html_response .= $order_detail_table_html;

		// Shipping detail html
		$shipping_detail_html  = '';
		$shipping_detail_html .= '<tr><td><strong>Name:</strong></td>';
		$shipping_detail_html .= '<td>' . esc_attr( $customer_name ) . '</td></tr>';
		$shipping_detail_html .= '<tr><td><strong>Address 1:</strong></td>';
		$shipping_detail_html .= '<td>' . esc_attr( $address_line_1 ) . '</td></tr>';
		$shipping_detail_html .= '<tr><td><strong>Address 2:</strong></td>';
		$shipping_detail_html .= '<td>' . esc_attr( $address_line_2 ) . '</td></tr>';
		$shipping_detail_html .= '<tr><td><strong>City:</strong></td>';
		$shipping_detail_html .= '<td>' . esc_attr( $city ) . '</td></tr>';
		$shipping_detail_html .= '<tr><td><strong>County:</strong></td>';
		$shipping_detail_html .= '<td>' . esc_attr( $county ) . '</td></tr>';
		$shipping_detail_html .= '<tr><td><strong>District:</strong></td>';
		$shipping_detail_html .= '<td>' . esc_attr( $district ) . '</td></tr>';
		$shipping_detail_html .= '<tr><td><strong>State or Region:</strong></td>';
		$shipping_detail_html .= '<td>' . esc_attr( $state_or_region ) . '</td></tr>';
		$shipping_detail_html .= '<tr><td><strong>Postal Code:</strong></td>';
		$shipping_detail_html .= '<td>' . esc_attr( $postal_code ) . '</td></tr>';
		$shipping_detail_html .= '<tr><td><strong>Country Code:</strong></td>';
		$shipping_detail_html .= '<td>' . esc_attr( $country_code ) . '</td></tr>';
		$shipping_detail_html .= '<tr><td><strong>Phone Number:</strong></td>';
		$shipping_detail_html .= '<td>' . esc_attr( $phone ) . '</td></tr>';
		$shipping_detail_html .= '<tr><td><strong>Shipping Service:</strong></td>';
		$shipping_detail_html .= '<td>' . esc_attr( $shipping_service ) . '</td></tr>';

		$shipping_detail_table_html  = '<table class="wp-list-table widefat striped table-view-list posts" >
		<thead class="table-dark">
		<tr>
		<th scope="col" colspan="2">Shipping Detail:</th>
		</tr>
		</thead>
		<tbody>';
		$shipping_detail_table_html .= $shipping_detail_html;
		$shipping_detail_table_html .= '</tbody>
		</table>';

		// Html response preparation with shipping detail
		$html_response .= $shipping_detail_table_html;

		// Order items html
		$order_items_table_html  = '<table class="wp-list-table widefat striped table-view-list posts"  >
		<thead class="table-dark">
		<tr>
		<th scope="col" colspan="9">Items Detail:</th>
		</tr>
		</thead>
		<tbody>
		<tr>
		<th>ASIN</th>
		<th>SKU</th>
		<th>Title</th>
		<th>Quantity</th>
		<th>Price</th>
		<th>Price Tax</th>
		<th>Shipping</th>
		<th>Shipping Tax</th>
		<th>Discount</th>
		</tr>';
		$order_items_table_html .= $order_items_html;
		$order_items_table_html .= '</tbody>
		</table>';

		// Final html response preparation with order items
		$html_response .= $order_items_table_html;

		wp_send_json_success( $html_response );
		wp_die();
	}



	/**
	 * Add filter in order
	 *
	 * @since    1.0.0
	 */
	public function ced_amazon_add_woo_order_views( $views ) {
		if ( ! current_user_can( 'edit_others_pages' ) ) {
			return $views;
		}
		$class        = ( isset( $_REQUEST['order_from_amazon'] ) && 'yes' == sanitize_text_field( $_REQUEST['order_from_amazon'] ) ) ? 'current' : '';
		$query_string = esc_url_raw( remove_query_arg( array( 'order_from_amazon' ) ) );

		$query_string = add_query_arg( 'order_from_amazon', urlencode( 'yes' ), $query_string );

		$views['amazon_order'] = '<a href="' . $query_string . '" class="' . $class . '">' . __( 'Amazon order', 'amazon-for-woocommerce' ) . '</a>';
		return $views;
	}

	/**
	 * Add filter in order
	 *
	 * @since    1.0.0
	 */
	public function ced_amazon_woo_admin_order_filter_query( $query ) {
		global $typenow, $wp_query, $wpdb;

		if ( 'shop_order' == $typenow ) {

			if ( ! empty( $_GET['order_from_amazon'] ) ) {

				if ( 'yes' == $_GET['order_from_amazon'] ) {

					$query->query_vars['meta_query'][] = array(
						'key'     => 'ced_umb_order_sales_channel',
						'compare' => 'EXISTS',
					);
				}
			}
		}
	}


	/**
	 * Change Amazon Region
	 *
	 * @since    1.0.0
	 */
	public function ced_amazon_change_region() {
		$check_ajax = check_ajax_referer( 'ced-amazon-ajax-seurity-string', 'ajax_nonce' );
		if ( ! $check_ajax ) {
			return;
		}

		$params              = array();
		$params['user_id']   = isset( $_POST['user_id'] ) ? sanitize_text_field( $_POST['user_id'] ) : '';
		$params['seller_id'] = isset( $_POST['seller_id'] ) ? sanitize_text_field( $_POST['seller_id'] ) : '';

		update_option( 'ced_amz_active_marketplace', $params );
		echo wp_json_encode( array( 'success' => true ) );
		die;
	}


	/**
	 * Upload custom amazon template and process template data.
	 *
	 * @name ced_amazon_prepare_template()
	 * @since 1.0.0
	 * 
	 */
	public function ced_amazon_prepare_template() {

		$check_ajax = check_ajax_referer( 'ced-amazon-ajax-seurity-string', 'ajax_nonce' );
		if ( ! $check_ajax ) {
			return;
		}

		$user_id     = isset( $_POST['user_id'] ) ? sanitize_text_field( $_POST['user_id'] ) : '';
		$fileUrl     = isset( $_POST['fileUrl'] ) ? trim( sanitize_text_field( $_POST['fileUrl'] ) ) : '';
		$fileName    = isset( $_POST['fileName'] ) ? trim( sanitize_text_field( $_POST['fileName'] ) ) : '';
		$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( $_POST['template_id'] )  : '';
		$seller_id   = isset( $_POST['seller_id'] ) ? sanitize_text_field( $_POST['seller_id'] )  : '';  

		$display_saved_values = isset( $_POST['display_saved_values'] ) ? sanitize_text_field( $_POST['display_saved_values'] ) : 'no';
		$this->ced_amazon_prepare_upload_template( $fileUrl, $fileName, $display_saved_values, $template_id, $seller_id );
	}


	public function ced_amazon_prepare_upload_template( $fileUrl = '', $fileName = '', $display_saved_values = 'no', $template_id = '', $seller_id = '', $rowNum = 0, $rowName = '', $last = false ) {

		global $wpdb;

		$results       = $wpdb->get_results( "SELECT DISTINCT meta_key FROM {$wpdb->prefix}postmeta", 'ARRAY_A' );
		$query         = $wpdb->get_results( $wpdb->prepare( "SELECT `meta_value` FROM  {$wpdb->prefix}postmeta WHERE `meta_key` LIKE %s", '_product_attributes' ), 'ARRAY_A' );
		$addedMetaKeys = get_option( 'CedUmbProfileSelectedMetaKeys', false );
		
		$attributes = wc_get_attribute_taxonomies();
		$attributes = isset($attributes) ? $attributes : array();
		
		$ced_amazon_general_options = get_option( 'ced_amazon_general_options', array() );
	
		// $ced_amazon_general_options = isset( $ced_amazon_general_options[$seller_id] ) ? $ced_amazon_general_options[$seller_id] : array();
		$current_amazon_profile = array();

		if ( !empty( $template_id ) ) {

			global $wpdb;
			$tableName              = $wpdb->prefix . 'ced_amazon_profiles';
			$result                 = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles WHERE `id` = %s ", $template_id ), 'ARRAY_A' );
			$current_amazon_profile = isset( $result[0] ) ? $result[0] : array();

		} 
		
		
		$results       = isset($results) ? $results : array();
		$query         = isset($query) ? $query : array();
		$addedMetaKeys = isset($results) ? $addedMetaKeys : array();
		

		$post = array(
			'fileUrl' => $fileUrl,
			'fileName' => $fileName,
			'display_saved_values' => $display_saved_values,
			'template_id' => $template_id,
			'seller_id' => $seller_id,
			'rowNum' => $rowNum,
			'rowName' => $rowName,
			'last' => true,
			'session' => $session,
			'attributes' => $attributes,
			'ced_amazon_general_options' => $ced_amazon_general_options,
			'addedMetaKeys' => $addedMetaKeys,
			'results' => $results,
			'query' => $query,
			'current_amazon_profile' => $current_amazon_profile,
			
		);
		
		
		$curl = curl_init();
		curl_setopt_array( $curl, array(
			CURLOPT_URL => 'http://localhost/Amazon%20Upload%20Template/api/singleprepareUploadTemplate.php',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => json_encode($post),
			CURLOPT_HTTPHEADER => array(
				'Authorization: application/json',
			),
			)
		);

		$response = curl_exec($curl);

		curl_close($curl);
		print_r($response);
		
		wp_die();
	}


	public function ced_amazon_clone_template_modal() {

		$check_ajax = check_ajax_referer( 'ced-amazon-ajax-seurity-string', 'ajax_nonce' );
		if ( ! $check_ajax ) {
			return;
		}

		$user_id   = isset( $_POST['user_id'] ) ? sanitize_text_field( $_POST['user_id'] ) : '';
		$seller_id = isset( $_POST['seller_id'] ) ? sanitize_text_field( $_POST['seller_id'] ) : '';

		$template_id = isset( $_POST['template_id'] ) ? trim( sanitize_text_field( $_POST['template_id'] ) ) : '';

		$sanitized_array = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
		$woo_cat         = isset( $sanitized_array['woo_cat'] ) ? ( $sanitized_array['woo_cat'] ) : array();


		global $wpdb;
		$tableName              = $wpdb->prefix . 'ced_amazon_profiles';
		$result                 = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles WHERE `id` = %s ", $template_id ), 'ARRAY_A' );
		$current_amazon_profile = isset( $result[0] ) ? $result[0] : array();

		if ( empty( $current_amazon_profile ) ) {
		   wp_send_json_error('Unable to fetch selected template . Please try again later.');
		   wp_die();
		}

		$wpdb->insert(
			$tableName,
			array(

				'primary_category'              => isset( $current_amazon_profile['primary_category'] )   ? $current_amazon_profile['primary_category'] : '',
				'secondary_category'            => isset( $current_amazon_profile['secondary_category'] ) ? $current_amazon_profile['secondary_category'] : '',
				'category_attributes_response'  => '',
				'wocoommerce_category'          => isset( $woo_cat ) ? wp_json_encode( $woo_cat ) : '[]',
				'category_attributes_structure' => isset( $current_amazon_profile['category_attributes_structure'] ) ? $current_amazon_profile['category_attributes_structure'] : '',
				'browse_nodes'                  => isset( $current_amazon_profile['browse_nodes'] ) ? $current_amazon_profile['browse_nodes'] : '',
				'browse_nodes_name'             => isset( $current_amazon_profile['browse_nodes_name'] ) ?  $current_amazon_profile['browse_nodes_name'] : '',
				'amazon_categories_name'        => isset( $current_amazon_profile['amazon_categories_name'] ) ? $current_amazon_profile['amazon_categories_name'] : '',
				'category_attributes_data'      => isset( $current_amazon_profile['category_attributes_data'] ) ? $current_amazon_profile['category_attributes_data'] : '',
				'seller_id'                     => $seller_id,
			),
			array( '%s' )
		);

		$clone_template_id = $wpdb->insert_id;

		if ( $clone_template_id ) {
			$href = get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=add-new-template&template_id=' . esc_attr( $clone_template_id ) . '&user_id=' . esc_attr( $user_id ) . '&seller_id=' . esc_attr( $seller_id );


			$ced_woo_amazon_mapping                                     = get_option( 'ced_woo_amazon_mapping', array() );
			$ced_woo_amazon_mapping[ $seller_id ][ $clone_template_id ] = $woo_cat;
			update_option( 'ced_woo_amazon_mapping', $ced_woo_amazon_mapping );


			$ced_amz_cloned_templates                 = get_option( 'ced_amz_cloned_templates', array() );
			$ced_amz_cloned_templates[ $seller_id ][] = $clone_template_id;
			update_option( 'ced_amz_cloned_templates', $ced_amz_cloned_templates );
			

			wp_send_json_success( "Template cloned successfully <a href='" . $href . "' target='_blank' >View/Edit Template</a> ." );

		} else {
			wp_send_json_error( 'Unable to clone template . Please try again later.' );
			
		}

		wp_die();
	}
}

?>
