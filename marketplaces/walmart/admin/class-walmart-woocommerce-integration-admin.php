<?php


use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://woocommerce.com/vendor/cedcommerce/
 * @since      1.0.0
 *
 * @package    Walmart_Woocommerce_Integration
 * @subpackage Walmart_Woocommerce_Integration/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Walmart_Woocommerce_Integration
 * @subpackage Walmart_Woocommerce_Integration/admin
 */
class Walmart_Woocommerce_Integration_Admin {

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

		// ini_set( 'display_errors', 1 );
		// ini_set( 'display_startup_errors', 1 );
		// error_reporting( E_ALL );

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->load_dependency();
		add_action( 'woocommerce_loaded', array( $this, 'ced_walmart_fire_hooks' ), 10, 1 );
		add_filter( 'woocommerce_order_number', array( $this, 'ced_walmart_modify_woo_order_number' ), 20, 2 );
		add_action( 'ced_walmart_auto_submit_shipment', array( $this, 'ced_walmart_auto_submit_shipment' ) );

		$store_id = isset( $_GET['store_id'] ) ? sanitize_text_field( $_GET['store_id'] ) : '';
		if ( ! empty( $store_id ) ) {
			update_option( 'ced_walmart_active_store', $store_id );
		}
	}


	/**
	 * Load the dependencies.
	 *
	 * @since    1.0.0
	 */
	public function load_dependency() {
		$ced_walmart_manager_file         = CED_WALMART_DIRPATH . 'admin/walmart/class-ced-walmart-manager.php';
		$ced_walmart_process_request_file = CED_WALMART_DIRPATH . 'admin/walmart/lib/class-ced-walmart-process-request.php';
		$ced_walmart_order_file           = CED_WALMART_DIRPATH . 'admin/walmart/lib/class-ced-walmart-order.php';
		include_file( $ced_walmart_manager_file );
		include_file( $ced_walmart_process_request_file );
		include_file( $ced_walmart_order_file );
		$this->ced_walmart_manager_instance = Ced_Walmart_Manager::get_instance();
		$this->ced_walmart_process_instance = Ced_Walmart_Remote_Request::get_instance();
		$this->ced_walmart_order_manager    = Ced_Walmart_Order::get_instance();
	}

	public function ced_walmart_fire_hooks() {
		if (OrderUtil::custom_orders_table_usage_is_enabled()) {
			add_filter( 'woocommerce_shop_order_list_table_columns' , array( $this, 'ced_walmart_add_table_columns' ));
			add_action( 'manage_woocommerce_page_wc-orders_custom_column' , array( $this, 'ced_walmart_manage_table_columns' ) , 10 , 2 );
			
		} else {
			add_filter( 'manage_edit-shop_order_columns' , array( $this, 'ced_walmart_add_table_columns' ));
			add_action( 'manage_shop_order_posts_custom_column' , array( $this, 'ced_walmart_manage_table_columns' ) , 10 , 2 );
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
		 * defined in Walmart_Woocommerce_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Walmart_Woocommerce_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if ( isset( $_GET['page'] ) && ( 'sales_channel' == sanitize_text_field( $_GET['page'] ) ) ) {

			wp_enqueue_style( 'woocommerce_admin_styles' );
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/walmart-woocommerce-integration-admin.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name . '_font', plugin_dir_url( __FILE__ ) . 'css/font-awesome-4.7.0/css/font-awesome.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( WC_ADMIN_APP );
			wp_enqueue_style( 'wc-material-icons' );
			wp_enqueue_style( 'wc-onboarding' );

			if ( isset( $_GET['channel'] ) && 'walmart' == $_GET['channel'] && isset( $_GET['section'] ) && 'templates' == $_GET['section'] && isset( $_GET['details'] ) && 'edit' == $_GET['details'] ) {

				wp_enqueue_style( $this->plugin_name . '_select2', plugin_dir_url( __FILE__ ) . 'css/select2.min.css', array(), $this->version, 'all' );
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
		 * defined in Walmart_Woocommerce_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Walmart_Woocommerce_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if ( isset( $_GET['page'] ) && ( 'sales_channel' == sanitize_text_field( $_GET['page'] ) ) ) {
			$suffix = '';
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/walmart-woocommerce-integration-admin.js', array( 'jquery' ), time(), false );

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

			if ( isset( $_GET['channel'] ) && 'walmart' == $_GET['channel'] && isset( $_GET['section'] ) && 'templates' == $_GET['section'] && isset( $_GET['details'] ) && 'edit' == $_GET['details'] ) {

				wp_enqueue_script( $this->plugin_name . '_select2', plugin_dir_url( __FILE__ ) . 'js/select2.min.js', array( 'jquery' ), $this->version, false );

			}
		}

		$ajax_nonce     = wp_create_nonce( 'ced-walmart-ajax-seurity-string' );
		$localize_array = array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'ajax_nonce' => $ajax_nonce,
			'category_url' => CED_WALMART_URL . 'admin/walmart/lib/json/walmart-categories-latest.json',
			'store_id'   => isset( $_GET['store_id'] ) ? sanitize_text_field( $_GET['store_id'] ) : '',
		);
		wp_localize_script( $this->plugin_name, 'ced_walmart_admin_obj', $localize_array );
	}



	public function ced_walmart_add_menus() {
		global $submenu;

		$menu_slug = 'woocommerce';

		if ( ! empty( $submenu[ $menu_slug ] ) ) {
			$sub_menus = array_column( $submenu[ $menu_slug ], 2 );
			if ( ! in_array( 'sales_channel', $sub_menus ) ) {
				add_submenu_page( 'woocommerce', 'Sales Channel', 'Sales Channel', 'manage_woocommerce', 'sales_channel', array( $this, 'ced_marketplace_home_page' ) );
			}
		}
	}


	/**
	 * Active Marketplace List
	 *
	 * @since    1.0.0
	 */

	public function ced_marketplace_home_page() {

		require CED_WALMART_DIRPATH . 'admin/partials/home/home.php';

		$page    = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		$channel = isset( $_GET['channel'] ) ? sanitize_text_field( $_GET['channel'] ) : '';
		if ( 'sales_channel' == $page && ! empty( $channel ) ) {
			/**
		 * Action for including template based on active channel
		 *
		 * @since  1.0.0
		 */
			do_action( 'ced_sales_channel_include_template', $channel );
		}
	}

	public function ced_walmart_add_marketplace_menus_to_array( $menus = array() ) {
		return array();
	}


	public function ced_sales_channel_include_template( $channel = 'walmart' ) {
		switch ( $channel ) {
			case 'walmart':
				$add_new_account = isset( $_GET['add-new-account'] ) ? sanitize_text_field( $_GET['add-new-account'] ) : '';
				$account_list    = ced_walmart_return_partner_detail_option();
				if ( ! $add_new_account && ! empty( $account_list ) && ! isset( $_GET['action'] ) ) {
					get_walmart_header();
				}

				$file_accounts = CED_WALMART_DIRPATH . 'admin/partials/ced-walmart-overview.php';
				if ( isset( $_GET['section'] ) ) {
					include_once CED_WALMART_DIRPATH . 'admin/partials/ced-walmart-main.php';
				} elseif ( file_exists( $file_accounts ) ) {
					include_once $file_accounts;
				}
				break;
		}
	}



	public function ced_show_connected_accounts( $channel = 'etsy' ) {
		if ( 'walmart' == $channel ) {
			$connected_accounts = ced_walmart_return_partner_detail_option();
			if ( ! empty( $connected_accounts ) ) {

				?>
				<a class="woocommerce-importer-done-view-errors-walmart" href="javascript:void(0)"><?php echo esc_attr( count( $connected_accounts ) ); ?> account
					connected <span class="dashicons dashicons-arrow-down-alt2"></span></a>
					<?php
			}
		}
	}

	public function ced_show_connected_accounts_details( $channel = 'walmart' ) {
		if ( 'walmart' == $channel ) {
			$connected_accounts = ced_walmart_return_partner_detail_option();
			if ( ! empty( $connected_accounts ) ) {

				?>
					<div id="ced-walmart-disconnect-account-modal" class="ced-modal">

						<div class="ced-modal-text-content">
							<div class="ced_walmart_error"></div>
							<h4>Are you sure want to delete the account ?</h4>
							<div class="ced-button-wrap-popup">
								<span class="spinner"></span>
								<span id="ced-walmart-delete-account" data-store-id="" class="button-primary">Confirm</span>
								<span class="ced-close-button">Cancel</span>
							</div>
						</div>

					</div>
					<tr class="wc-importer-error-log-walmart" style="display:none;">
						<td colspan="4">
							<div class="">
								<div class="ced-account-connected-form">
									<div class="ced-account-head">
										<div class="ced-account-label">
											<strong>Account Details</strong>
										</div>
										<div class="ced-account-label">
											<strong>Status</strong>
										</div>
										<div class="ced-account-label">
											<!-- <p>Status</p> -->
										</div>
									</div>
								<?php
								foreach ( $connected_accounts as $key => $value ) {
									if ( empty( $key ) ) {
										continue;
									}
									$store_id = $key;
									?>
										<div class="ced-account-body">
											<div class="ced-acount-body-label">
												<strong><?php echo esc_attr( ced_walmart_get_store_name_by_id( $key ) ); ?></strong>
											</div>
											<div class="ced-connected-button-wrapper">

											<?php
											$setup_steps = ced_walmart_return_partner_detail_option();
											if ( empty( $setup_steps[ $store_id ]['current_step'] ) ) {
												?>
													<a style="width: 33%;" class="ced-connected-link-account" href=""><span class="ced-circle"></span>Onboarding Completed</a>
												<?php
											} else {
												?>
													<a style="width: 33%;" class="ced-pending-link-account" href="<?php echo esc_url( $setup_steps[ $store_id ]['current_step'] ); ?>"><span class="ced-circle"></span>Onboarding Pending</a>
													<?php
											}
											?>

											</div>
											<div class="">

												<a href="
											<?php
											echo esc_url(
												ced_get_navigation_url(
													'walmart',
													array(
														'section'     => 'overview',
														'store_id' => $store_id,
													)
												)
											);
											?>
												" ><button type="button" class="components-button is-primary alignright">

												Manage</button> </a>

												<button type="button" style="margin-right: 18px" class="components-button is-tertiary alignright" id="ced_walmart_disconnect_account" data-store-id="<?php echo esc_attr( $store_id ); ?>"> Disconnect</button> 

												

											</div>
										</div>

										<?php
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



	public function ced_walmart_add_table_columns( $columns ) {
		$modified_columns = array();
		foreach ( $columns as $key => $value ) {
			$modified_columns[ $key ] = $value;
			if ( 'order_number' == $key ) {
				$modified_columns['order_from'] = '<span title="Walmart Order">Order source</span>';
			}
		}
		return $modified_columns;
	}


	public function ced_walmart_manage_table_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'order_from':
				$order				   = wc_get_order($post_id);
				$_ced_walmart_order_id = $order->get_meta( '_ced_walmart_order_id', true );
				$store_id              = $order->get_meta( '_ced_walmart_order_store_id' . wifw_environment(), true );
				if ( ! empty( $_ced_walmart_order_id ) ) {
					echo '<p><a>[ Walmart Order ]</a><br>Store : ' . esc_attr( ced_walmart_get_store_name_by_id( $store_id ) ) . '</p>';
				}
		}
	}


	public function ced_walmart_modify_woo_order_number( $order_id, $order ) {
		$_ced_walmart_order_id = $order->get_meta( '_ced_walmart_order_id', true );
		$store_id              = $order->get_meta( '_ced_walmart_order_store_id' . wifw_environment(), true );

		if ( ! empty( $_ced_walmart_order_id ) ) {

			$all_settings = get_option( 'ced_walmart_settings', '' );

			if ( ! empty( $all_settings ) ) {
				$all_settings = json_decode( $all_settings, true );
			} else {
				$all_settings = array();
			}

			if ( isset( $all_settings[ $store_id ] ) && isset( $all_settings[ $store_id ]['general_settings']['order_setting'] ) ) {
				$is_setting_activated_walmart_order_number_show_on_order_page = ! empty( $all_settings[ $store_id ]['general_settings']['order_setting']['use_walmart_order_id'] ) ? $all_settings[ $store_id ]['general_settings']['order_setting']['use_walmart_order_id'] : '';

				if ( ! empty( $is_setting_activated_walmart_order_number_show_on_order_page ) && 'on' == $is_setting_activated_walmart_order_number_show_on_order_page ) {
					return $_ced_walmart_order_id;
				} else {
					$order_prefix = ! empty( $all_settings[ $store_id ]['general_settings']['order_setting']['order_prefix'] ) ? $all_settings[ $store_id ]['general_settings']['order_setting']['order_prefix'] : '';
					if ( ! empty( $order_prefix ) ) {
						$order_id = $order_prefix . $order_id;
					}
				}
			}
		}

		return $order_id;
	}

	/**
	 * Function ced_walmart_auto_submit_shipment
	 *
	 * @since    1.0.0
	 * @param      array $walmart_orders
	 */

	public function ced_walmart_auto_submit_shipment() {
		$walmart_orders = wc_get_orders(
			array(
				'limit' => -1,
				'meta_key'    => '_ced_walmart_order_status',
				'meta_value'  => 'Acknowledged',
				'type'        => wc_get_order_types(),
				'status'      => array_keys( wc_get_order_statuses() ),
				'orderby'     => 'date',
				'order'       => 'DESC',
				'return'      => 'ids',
			)
		);

		if ( ! empty( $walmart_orders ) && is_array( $walmart_orders ) ) {
			foreach ( $walmart_orders as $woo_order_id ) {
				$this->ced_walmart_auto_ship_order( $woo_order_id );
			}
		}
	}


	/**
	 * Function ced_walmart_auto_submit_shipmentbreakdown
	 *
	 * @since   1.0.0
	 *  args    $woo_order_id
	 * @param   $_ced_walmart_order_status
	 */

	public function ced_walmart_auto_ship_order( $woo_order_id = 0 ) {

		$order                     = wc_get_order( $woo_order_id );
		$_ced_walmart_order_status = $order->get_meta( '_ced_walmart_order_status', true );
		if ( empty( $_ced_walmart_order_status ) || 'Acknowledged' != $_ced_walmart_order_status ) {
			return;
		}

		$tracking_names = array(
			'UPS'      => 'ups',
			'USPS'     => 'usps',
			'FedEx'    => 'fedex',
			'Airborne' => 'airborne',
			'OnTrac'   => 'ontrac',
			'DHL'      => 'dhl',
			'LS'       => 'ls',
			'UDS'      => 'uds',
			'UPSMI'    => 'upsmi',
			'FDX'      => 'fdx',
			'PILOT'    => 'pilot',
			'ESTES'    => 'estes',
			'SAIA'     => 'saia',
		);

		$method_codes  = array(
			'Standard'   => 'standard',
			'Express'    => 'express',
			'Oneday'     => 'oneday',
			'Freight'    => 'freight',
			'WhiteGlove' => 'whiteglove',
			'Value'      => 'value',
		);
		$tracking_no   = '';
		$tracking_code = '';
		$method_code   = '';
		$date          = '';

		$tracking_details = $order->get_meta( '_wc_shipment_tracking_items', true );
		$ship_service     = $order->get_meta( 'ship_service', true );
		$method_code      = array_search( strtolower( $ship_service ), $method_codes );

		if ( ! empty( $tracking_details ) ) {
			$tracking_code = isset( $tracking_details[0]['tracking_provider'] ) ? $tracking_details[0]['tracking_provider'] : '';
			$tracking_code = array_search( strtolower( $tracking_code ), $tracking_names );

			$tracking_no = isset( $tracking_details[0]['tracking_number'] ) ? $tracking_details[0]['tracking_number'] : '';

			$date = isset( $tracking_details[0]['date_shipped'] ) ? $tracking_details[0]['date_shipped'] : '';

			if ( ! empty( $tracking_no ) && ! empty( $tracking_code ) ) {

				$order_line_items  = $order->get_meta( 'order_detail', true );
				$purchase_order_id = $order_line_items['purchaseOrderId'];
				$order_id          = $order->get_meta( 'purchaseOrderId', true );
				$tracking_url      = '';

				if ( empty( $method_code ) ) {
					$method_code = 'Standard';
				}

				$offset      = '.000Z';
				$ship_todate = gmdate( 'Y-m-d', ( $date ) ) . 'T' . gmdate( 'H:i:s', ( $date ) ) . $offset;

				$shipment_array = $this->get_shipment_array( $order_line_items, $order_id, $ship_todate, $tracking_code, $method_code, $tracking_no, $tracking_url );
				$action         = 'orders/' . $purchase_order_id . '/shipping';
				/** Refresh token hook for walmart
				 *
				 * @since 1.0.0
				 */
				do_action( 'ced_walmart_refresh_token' );
				$response = $this->ced_walmart_process_request_instance->ced_walmart_process_request( $action, $shipment_array, array(), 'POST' );
				if ( isset( $response['order']['orderLines'] ) ) {
					$order->add_meta_data( '_ced_walmart_order_status', 'Shipped' );
					$order->update_status( 'wc-completed' );
					$order->save();
				}
			}
		}
	}

	/**
	 * Function get_shipment_array
	 *
	 * @since   1.0.0
	 *  args    $woo_order_id
	 * @param   order_line_items, $order_id,$ship_todate,$carrier,$method_code
	 */

	public function get_shipment_array( $order_line_items = array(), $order_id = '', $ship_todate = '', $carrier = '', $method_code = '', $tracking = '', $tracking_url = '' ) {

		$ship_order_lines = array();

		foreach ( $order_line_items['orderLines']['orderLine'] as $key => $value ) {
			$line_array['lineNumber']        = $value['lineNumber'];
			$line_array['sellerOrderId']     = $order_id;
			$line_array['orderLineStatuses'] = array(
				'orderLineStatus' => array(
					array(
						'status'              => 'Shipped',
						'statusQuantity'      => array(
							'unitOfMeasurement' => 'EACH',
							'amount'            => '1',
						),
						'trackingInfo'        => array(
							'shipDateTime'   => $ship_todate,
							'carrierName'    => array(
								'otherCarrier' => null,
								'carrier'      => $carrier,
							),
							'methodCode'     => $method_code,
							'trackingNumber' => $tracking,
							'trackingURL'    => esc_url( $tracking_url ),
						),
						'returnCenterAddress' => get_return_address(),
					),
				),
			);

			$ship_order_lines[] = $line_array;
		}

		$shipment_array['orderShipment']['orderLines']['orderLine'] = $ship_order_lines;
		return $shipment_array;
	}






	/**
	 * Walmart_Woocommerce_Integration_Admin ced_walmart_cron_schedules.
	 *
	 * @since 1.0.0
	 * @param array $schedules Cron Schedules.
	 */
	public function ced_walmart_cron_schedules( $schedules ) {
		if ( ! isset( $schedules['ced_walmart_5min'] ) ) {
			$schedules['ced_walmart_5min'] = array(
				'interval' => 5 * 60,
				'display'  => __( 'Once every 5 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_walmart_10min'] ) ) {
			$schedules['ced_walmart_10min'] = array(
				'interval' => 10 * 60,
				'display'  => __( 'Once every 10 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_walmart_15min'] ) ) {
			$schedules['ced_walmart_15min'] = array(
				'interval' => 15 * 60,
				'display'  => __( 'Once every 15 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_walmart_30min'] ) ) {
			$schedules['ced_walmart_30min'] = array(
				'interval' => 30 * 60,
				'display'  => __( 'Once every 30 minutes' ),
			);
		}
		return $schedules;
	}



	/**
	 * Woocommerce_Walmart_Integration_Admin ced_walmart_configuration_page.
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_configuration_page() {
		$file_accounts = CED_WALMART_DIRPATH . 'admin/partials/ced-walmart-configuration.php';
		echo "<div class='ced_walmart_body'>";
		if ( isset( $_GET['section'] ) ) {
			include_once CED_WALMART_DIRPATH . 'admin/partials/ced-walmart-main.php';
		} elseif ( file_exists( $file_accounts ) ) {
			include_once $file_accounts;
		}
		echo '</div>';
	}

	/**
	 * Woocommerce_Walmart_Integration_Admin ced_marketplace_listing_page.
	 *
	 * @since 1.0.0
	 */
	public function ced_marketplace_listing_page() {
		/** Get all connected marketplaces
		 *
		 * @since 1.0.0
		 */
		$active_marketplaces = apply_filters( 'ced_add_marketplace_menus_array', array() );
		if ( is_array( $active_marketplaces ) && ! empty( $active_marketplaces ) ) {
			require CED_WALMART_DIRPATH . 'admin/partials/marketplaces.php';
		}
	}



	public function ced_walmart_update_partnet_detail( $token = '', $query_args = array() ) {
		$action   = 'partnerprofile';
		$response = $this->ced_walmart_process_instance->ced_walmart_process_request( $action, array(), $query_args, 'GET' );

		if ( isset( $response['result'] ) && isset( $response['result']['partner'] ) ) {
			update_option( 'ced_walmart_us_partner_details', json_encode( $response['result'] ) );
		}
		return $response['result'];
	}

	/**
	 * Woocommerce_Walmart_Integration_Admin ced_walmart_save_cat.
	 *
	 * @since 2.0.0
	 */

	public function ced_walmart_save_cat( $cat, $cat_id, $profile_type) {
	
		$mapped_cat = get_option( 'ced_mapped_cat_' . $profile_type );
		
		if ( ! empty( $mapped_cat ) ) {
			$mapped_cat = json_decode( $mapped_cat, 1 );
		} else {
			$mapped_cat = array();
		}

		if ( ! empty( $cat_id ) && is_array( $cat_id ) ) {
			foreach ( $cat_id as $key => $value ) {
				$cat_id = $value;
				if ( ! isset( $cat ) || empty( $cat ) ) {
					delete_term_meta( $cat_id, 'ced_walmart_category' );
					foreach ( $mapped_cat['profile'] as $key => $value ) {
						if ( ! empty( $mapped_cat['profile'][ $key ] ) ) {
							unset( $mapped_cat['profile'][ $key ]['woo_cat'][ $cat_id ] );
							update_option( 'ced_mapped_cat_' . $profile_type, json_encode( $mapped_cat ), 1 );
						} else {
							unset( $mapped_cat['profile'][ $key ] );
							update_option( 'ced_mapped_cat_' . $profile_type, json_encode( $mapped_cat ), 1 );
						}
					}
				} else {
					update_term_meta( $cat_id, 'ced_walmart_category', $cat );
					if ( empty( $mapped_cat ) ) {
						$mapped_cat['profile'][ $cat ]['woo_cat'][ $cat_id ] = $cat_id;
						$mapped_cat['profile'][ $cat ]['profile_data']       = '';
						update_option( 'ced_mapped_cat_' . $profile_type, json_encode( $mapped_cat ), 1 );
					} else {
						foreach ( $mapped_cat['profile'] as $key => $value ) {
							
							if ( in_array( $cat_id, $value['woo_cat'] ) ) {
								$temp_cat = $key;
								unset( $mapped_cat['profile'][ $temp_cat ]['woo_cat'][ $cat_id ] );
								if ( empty( $mapped_cat['profile'][ $temp_cat ]['woo_cat'] ) ) {
									unset( $mapped_cat['profile'][ $temp_cat ] );
								}
								$mapped_cat['profile'][ $cat ]['woo_cat'][ $cat_id ] = $cat_id;
								if ( ! isset( $mapped_cat['profile'][ $cat ]['profile_data'] ) ) {
									$mapped_cat['profile'][ $cat ]['profile_data'] = '';

								}
								update_option( 'ced_mapped_cat_' . $profile_type, json_encode( $mapped_cat ), 1 );
							} else {
								$mapped_cat['profile'][ $cat ]['woo_cat'][ $cat_id ] = $cat_id;
								if ( ! isset( $mapped_cat['profile'][ $cat ]['profile_data'] ) ) {
									$mapped_cat['profile'][ $cat ]['profile_data'] = '';
								}
								update_option( 'ced_mapped_cat_' . $profile_type, json_encode( $mapped_cat ), 1 );
							}
						}
					}
				}
			}
		}
	}


	/**
	 * Woocommerce_Walmart_Integration_Admin ced_walmart_product_data_tabs.
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_product_data_tabs( $tabs ) {
		$tabs['walmart_inventory'] = array(
			'label'  => __( 'Walmart', 'walmart-woocommerce-integration' ),
			'target' => 'walmart_inventory_options',
			'class'  => array( 'show_if_simple' ),
		);
		return $tabs;
	}

	/**
	 * Woocommerce_Walmart_Integration_Admin ced_walmart_render_product_fields.
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_render_product_fields( $loop, $variation_data, $variation ) {
		if ( ! empty( $variation_data ) ) {
			?>
			<div id='walmart_inventory_options_variable' class='panel woocommerce_options_panel' style="width: 100%;max-height:400px;overflow:scroll;border:1px solid #ccc;border-radius:4px;"><div class='options_group'>
				<form>
					<?php wp_nonce_field( 'ced_product_settings', 'ced_product_settings_submit' ); ?>
				</form>
				<?php
				echo "<div class='ced_walmart_variation_product_level_wrap'>";
				echo "<div class='ced_walmart_parent_element'>";
				echo "<h2 class='walmart-cool'>Walmart Product Data";
				echo '</h2>';
				echo '</div>';
				echo "<div class='ced_walmart_variation_product_content ced_walmart_child_element'>";
				$this->render_fields( $variation->ID );
				echo '</div>';
				echo '</div>';
				?>
			</div></div>
			<?php
		}
	}

	/**
	 * Woocommerce_Walmart_Integration_Admin ced_walmart_product_data_panels.
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_product_data_panels() {

		global $post;

		?>
		<div id='walmart_inventory_options' class='panel woocommerce_options_panel' style="max-height:400px;overflow:scroll;"><div class='options_group'>
			<form>
				<?php wp_nonce_field( 'ced_product_settings', 'ced_product_settings_submit' ); ?>
			</form>
			<?php
			echo "<div class='ced_walmart_simple_product_level_wrap'>";
			echo "<div class=''>";
			echo "<h2 class='walmart-cool'>Walmart Product Data";
			echo '</h2>';
			echo '</div>';
			echo "<div class='ced_walmart_simple_product_content'>";
			$this->render_fields( $post->ID );
			echo '</div>';
			echo '</div>';
			?>
		</div></div>
		<?php
	}

	public function render_fields( $post_id ) {

		$filename       = CED_WALMART_DIRPATH . 'admin/walmart/lib/json/walmart-global-setting.json';
		$product_fields = file_get_contents( $filename );
		$store_id       = ced_walmart_get_current_active_store();
					// Fetching Shipping Template
		$shipping_template_array        = array();
		$ced_walmart_shipping_templates = get_option( 'ced_walmart_shipping_templates' . wifw_environment() . $store_id );
		$ced_walmart_shipping_templates = json_decode( $ced_walmart_shipping_templates, 1 );
		$shipping_template_array        = array(
			'code'           => 'shipping_template',
			'default_value'  => null,
			'description'    => 'Add Shipping Template for item on Walmart.',
			'label'          => 'Shipping Templates',
			'required'       => false,
			'type'           => 'LIST',
			'type_parameter' => null,
			'values'         => null,
		);
		if ( isset( $ced_walmart_shipping_templates ) && is_array( $ced_walmart_shipping_templates ) ) {
			foreach ( $ced_walmart_shipping_templates['shippingTemplates'] as $key => $value ) {
				$shipping_template_array['values_list'][] = array(
					'code'  => $value['id'],
					'label' => $value['name'],
				);
			}
		}

					// Fetching Fulfillment Centers
		$fulfillment_center_array        = array();
		$ced_walmart_fulfillment_centers = get_option( 'ced_walmart_fulfillment_center' . wifw_environment() );
		$ced_walmart_fulfillment_centers = json_decode( $ced_walmart_fulfillment_centers, 1 );
		$fulfillment_center_array        = array(
			'code'           => 'fulfillment_center',
			'default_value'  => null,
			'description'    => 'Add Fulfillment Center for  Template for item on Walmart.',
			'label'          => 'Fulfillment Center',
			'required'       => false,
			'type'           => 'LIST',
			'type_parameter' => null,
			'values'         => null,
		);
		if ( isset( $ced_walmart_fulfillment_centers ) && is_array( $ced_walmart_fulfillment_centers ) ) {
			foreach ( $ced_walmart_fulfillment_centers as $key => $value ) {
				$fulfillment_center_array['values_list'][] = array(
					'code'  => $value['shipNode'],
					'label' => $value['shipNodeName'],
				);
			}
		}

		$product_fields                     = json_decode( $product_fields, true );
		$product_fields['product_specific'] = array_merge( $product_fields['product_specific'], array( $shipping_template_array ), array( $fulfillment_center_array ) );

		foreach ( $product_fields as $key => $value ) {
			foreach ( $value as $index => $fields ) {
				if ( 'sku' == $fields['code'] ) {
					continue;
				}
				$key                   = '_custom_' . esc_attr( $fields['code'] );
				$walmart_product_value = get_post_meta( $post_id, $key, true );
				$id                    = 'ced_walmart_product_data[' . $post_id . '][' . esc_attr( $key ) . ']';
				if ( 'TEXT' == $fields['type'] ) {

					woocommerce_wp_text_input(
						array(
							'id'       => $id,
							'label'    => $fields['label'],
							'desc_tip' => 'false',
							'type'     => 'text',
							'value'    => isset( $walmart_product_value ) ? $walmart_product_value : '',
						)
					);
				} elseif ( 'LIST' == $fields['type'] ) {
					$value_for_dropdown = ! empty( $fields['values_list'] ) ? $fields['values_list'] : array();
					$options            = array();
					$options[]          = '--select--';
					foreach ( $value_for_dropdown as $index => $otion_data ) {
						$options[ $otion_data['code'] ] = $otion_data['label'];
					}
					woocommerce_wp_select(
						array(
							'id'      => $id,
							'label'   => $fields['label'],
							'options' => $options,
							'value'   => isset( $walmart_product_value ) ? $walmart_product_value : '',
						)
					);
				}
			}
		}
	}

	/**
	 * Woocommerce_Walmart_Integration_Admin ced_walmart_save_meta_data.
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_save_meta_data( $post_id = '' ) {
		if ( empty( $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['ced_product_settings_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ced_product_settings_submit'] ) ), 'ced_product_settings' ) ) {
			return;
		}

		if ( isset( $_POST['ced_walmart_product_data'] ) ) {
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( ! empty( $sanitized_array ) ) {
				foreach ( $sanitized_array['ced_walmart_product_data'] as $id => $value ) {
					foreach ( $value as $option => $opt_value ) {
						update_post_meta( $id, $option, $opt_value );
					}
				}
			}
		}
	}

	/**
	 * Woocommerce_Walmart_Integration_Admin ced_walmart_save_product_fields.
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_save_product_fields( $post_id = '', $i = '' ) {
		if ( empty( $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['ced_product_settings_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ced_product_settings_submit'] ) ), 'ced_product_settings' ) ) {
			return;
		}

		if ( isset( $_POST['ced_walmart_product_data'] ) ) {
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( ! empty( $sanitized_array ) ) {
				foreach ( $sanitized_array['ced_walmart_product_data'] as $id => $value ) {
					foreach ( $value as $option => $opt_value ) {
						update_post_meta( $id, $option, $opt_value );
					}
				}
			}
		}
	}

	/**
	 * Woocommerce_Walmart_Integration_Admin ced_walmart_list_per_page.
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_list_per_page() {

		$check_ajax = check_ajax_referer( 'ced-walmart-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$_per_page = isset( $_POST['per_page'] ) ? sanitize_text_field( $_POST['per_page'] ) : '10';
			update_option( 'ced_walmart_list_per_page', $_per_page );
			wp_die();
		}
	}

	/**
	 * Woocommerce_Walmart_Integration_Admin ced_walmart_search_product_name.
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_search_product_name() {

		$check_ajax = check_ajax_referer( 'ced-walmart-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$keyword      = isset( $_POST['keyword'] ) ? sanitize_text_field( $_POST['keyword'] ) : '';
			$product_list = '';
			if ( ! empty( $keyword ) ) {
				$arguements = array(
					'numberposts' => -1,
					'post_type'   => array( 'product', 'product_variation' ),
					's'           => $keyword,
				);
				$post_data  = get_posts( $arguements );
				if ( ! empty( $post_data ) ) {
					foreach ( $post_data as $key => $data ) {
						$product_list .= '<li class="ced_walmart_searched_product" data-post-id="' . esc_attr( $data->ID ) . '">' . esc_html( __( $data->post_title, 'walmart-woocommerce-integration' ) ) . '</li>';
					}
				} else {
					$product_list .= '<li>No products found.</li>';
				}
			} else {
				$product_list .= '<li>No products found.</li>';
			}
			echo json_encode( array( 'html' => $product_list ) );
			wp_die();
		}
	}

		/**
		 * Woocommerce_Walmart_Integration_Admin ced_walmart_get_product_metakeys.
		 *
		 * @since 1.0.0
		 */
	public function ced_walmart_get_product_metakeys() {

		$check_ajax = check_ajax_referer( 'ced-walmart-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$product_id = isset( $_POST['post_id'] ) ? sanitize_text_field( $_POST['post_id'] ) : '';
			include_once CED_WALMART_DIRPATH . 'admin/partials/ced-walmart-metakeys-list.php';
		}
	}


	/**
	 * Woocommerce_Walmart_Integration_Admin ced_walmart_get_orders_manual.
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_get_orders_manual() {

		$check_ajax = check_ajax_referer( 'ced-walmart-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$store_id = isset( $_POST['store_id'] ) ? sanitize_text_field( $_POST['store_id'] ) : '';
			$response = $this->fetch_walmart_orders( $store_id );
			$status   = 400;
			$message  = 'some error occurred.';
			if ( isset( $response['result']['list']['elements']['order'][0] ) ) {
				$orders  = $response['result']['list']['elements']['order'];
				$message = 'Orders fetched successfully.';
				$status  = 200;
				$this->ced_walmart_order_manager->create_local_order( $orders, $store_id );
			} elseif ( isset( $response['result']['list']['elements']['order'] ) && empty( $response['result']['list']['elements']['order'] ) ) {
				$message = 'No new orders to fetch.';
			} elseif ( isset( $response['error']['error'] ) ) {
				$message = isset( $response['error']['error'][0]['description'] ) ? $response['error']['error'][0]['description'] : 'some error occurred.';
			} elseif ( isset( $response['error']['error'] ) ) {
				$message = isset( $response['error']['error']['description'] ) ? $response['errors']['error']['description'] : 'some error occurred.';
			}
			echo json_encode(
				array(
					'status'  => $status,
					'message' => $message,
				)
			);
			wp_die();
		}
	}

	/**
	 * Woocommerce_Walmart_Integration_Admin ced_walmart_auto_fetch_orders.
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_auto_fetch_orders() {
		$store_id = str_replace( 'ced_walmart_auto_fetch_orders_', '', current_action() );
		$response = $this->fetch_walmart_orders( $store_id );
		if ( isset( $response['result']['list']['elements']['order'][0] ) ) {
			$orders  = $response['result']['list']['elements']['order'];
			$message = 'Orders fetched successfully.';
			$status  = 200;
			$this->ced_walmart_order_manager->create_local_order( $orders, $store_id );
		}
	}

	public function ced_walmart_email_restriction( $enable = '', $order = array() ) {
		if ( ! is_object( $order ) ) {
			return $enable;
		}
		$marketplace    = $order->get_meta( '_order_marketplace', true );
		$email_restrict = get_option( 'ced_walmart_email_restriction', true );
		if ( 'Walmart' == $marketplace ) {
			$enable = false;
		}
		return $enable;
	}


	/**
	 * Woocommerce_Walmart_Integration_Admin ced_walmart_auto_sync_existing_products.
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_auto_sync_existing_products() {
		$store_id    = str_replace( 'ced_walmart_auto_sync_existing_products_', '', current_action() );
		$next_cursor = get_option( '_ced_walmart_next_cursor_' . $store_id, '' );
		$offset      = get_option( '_ced_walmart_offset_new_' . $store_id, 0 );
		if ( empty( $offset ) ) {
			$offset = 0;
		}
		$limit      = 50;
		$query_args = array(
			'offset' => $offset,
			'limit'  => $limit,
		);
		$action     = 'product';
		/** Refresh token hook for walmart
		 *
		 * @since 1.0.0
		 */
		do_action( 'ced_walmart_refresh_token', $store_id );
		$this->ced_walmart_process_instance->store_id = $store_id;
		$response                                     = $this->ced_walmart_process_instance->ced_walmart_process_request( $action, array(), $query_args , 'GET' );
		if ( isset( $response['result']['ItemResponse'] ) && ! empty( $response['result']['ItemResponse'] ) && is_array( $response['result']['ItemResponse'] ) ) {
			$offset = $offset + $limit;
			update_option( '_ced_walmart_offset_new_' . $store_id, $offset );

			foreach ( $response['result']['ItemResponse'] as $key => $item_data ) {
				$sku = isset( $item_data['sku'] ) ? $item_data['sku'] : '';
				if ( ! empty( $sku ) ) {
					$product_id               = wc_get_product_id_by_sku( $sku );
					$product_status           = isset( $item_data['publishedStatus'] ) ? $item_data['publishedStatus'] : 'PUBLISHED';
					$product_lifecycle_status = isset( $item_data['lifecycleStatus'] ) ? $item_data['lifecycleStatus'] : 'ACTIVE';
					if ( $product_id ) {

						update_post_meta( $product_id, 'ced_walmart_product_uploaded' . $store_id . wifw_environment(), 'yes' );
						update_post_meta( $product_id, 'ced_walmart_product_status' . $store_id . wifw_environment(), $product_status );
						update_post_meta( $product_id, 'ced_walmart_product_lifecycle' . $store_id . wifw_environment(), $product_lifecycle_status );
						$_product = wc_get_product( $product_id );
						if ( 'variation' == $_product->get_type() ) {
							$product_id = $_product->get_parent_id();
							update_post_meta( $product_id, 'ced_walmart_product_uploaded' . $store_id . wifw_environment(), 'yes' );
							update_post_meta( $product_id, 'ced_walmart_product_status' . $store_id . wifw_environment(), $product_status );
							update_post_meta( $product_id, 'ced_walmart_product_lifecycle' . $store_id . wifw_environment(), $product_lifecycle_status );
						}
					}
				}
			}
			if ( isset( $response['nextCursor'] ) && ! empty( $response['nextCursor'] ) ) {
				update_option( '_ced_walmart_next_cursor_' . $store_id, $response['nextCursor'] );
			} else {
				update_option( '_ced_walmart_next_cursor_' . $store_id, '' );
			}
		} else {
			update_option( '_ced_walmart_offset_new_' . $store_id, '' );
			update_option( '_ced_walmart_next_cursor_' . $store_id, '' );
		}
	}


	public function get_product_id_by_params( $meta_key = '', $meta_value = '' ) {
		if ( ! empty( $meta_value ) ) {
			$posts = get_posts(
				array(

					'numberposts' => -1,
					'post_type'   => array( 'product', 'product_variation' ),
					'meta_query'  => array(
						array(
							'key'     => $meta_key,
							'value'   => trim( $meta_value ),
							'compare' => '=',
						),
					),
					'fields'      => 'ids',

				)
			);
			if ( ! empty( $posts ) ) {
				return $posts[0];
			}
			return false;
		}
		return false;
	}

	/**
	 * Woocommerce_Walmart_Integration_Admin ced_walmart_auto_update_price.
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_auto_update_price() {

		$store_id     = str_replace( 'ced_walmart_auto_update_price_', '', current_action() );
		$products_ids = get_option( 'ced_walmart_product_ids_to_be_updated_price' . $store_id, array() );
		if ( empty( $products_ids ) ) {
			$posts = get_posts(
				array(
					'numberposts' => -1,
					'post_type'   => 'product',
					'meta_query'  => array(
						array(
							'key'     => 'ced_walmart_product_uploaded' . $store_id . wifw_environment(),
							'value'   => 'yes',
							'compare' => '=',
						),
					),
					'fields'      => 'ids',
				)
			);

			if ( ! empty( $posts ) ) {
				$products_ids = array_chunk( $posts, 150 );
			}
		}
		if ( isset( $products_ids[0] ) && is_array( $products_ids[0] ) && ! empty( $products_ids[0] ) ) {
			$this->ced_walmart_curl_instance->store_id = $store_id;
			$this->ced_walmart_manager_instance->ced_walmart_update_price( $products_ids[0], $store_id );
			unset( $products_ids[0] );
			$products_ids = array_values( $products_ids );
			update_option( 'ced_walmart_product_ids_to_be_updated_price' . $store_id, $products_ids );
		}
	}


	/**
	 * Woocommerce_Walmart_Integration_Admin ced_walmart_auto_update_inventory.
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_auto_update_inventory() {
		$store_id     = str_replace( 'ced_walmart_auto_update_inventory_', '', current_action() );
		$products_ids = get_option( 'ced_walmart_product_ids_to_be_updated_inventory' . $store_id, array() );
		if ( empty( $products_ids ) ) {
			$posts = get_posts(
				array(
					'numberposts' => -1,
					'post_type'   => 'product',
					'meta_query'  => array(
						array(
							'key'     => 'ced_walmart_product_uploaded' . $store_id . wifw_environment(),
							'value'   => 'yes',
							'compare' => '=',
						),
					),
					'fields'      => 'ids',
				)
			);

			if ( ! empty( $posts ) ) {
				$products_ids = array_chunk( $posts, 150 );
			}
		}
		if ( isset( $products_ids[0] ) && is_array( $products_ids[0] ) && ! empty( $products_ids[0] ) ) {
			$this->ced_walmart_process_instance->store_id = $store_id;
			$this->ced_walmart_manager_instance->ced_walmart_update_stock( $products_ids[0], $store_id );
			unset( $products_ids[0] );
			$products_ids = array_values( $products_ids );
			update_option( 'ced_walmart_product_ids_to_be_updated_inventory' . $store_id, $products_ids );
		}
	}




	/**
	 * Woocommerce_Walmart_Integration_Admin fetch_walmart_orders.
	 *
	 * @since 1.0.0
	 */
	public function fetch_walmart_orders( $store_id = '' ) {
		$action       = 'orders';
		$created_date = gmdate( 'Y-m-d', strtotime( ' -5 day' ) );
		$ship_node    = get_option( 'ced_walmart_ship_node', '' );
		if ( empty( $ship_node ) ) {
			$ship_node = 'SellerFulfilled';
		}
		$query_args = array(
			'createdStartDate' => $created_date,
			'limit'            => 15,
			'shipNodeType'     => $ship_node,
		);
		/** Refresh token hook for walmart
		 *
		 * @since 1.0.0
		 */
		do_action( 'ced_walmart_refresh_token', $store_id );
		$this->ced_walmart_process_instance->store_id = $store_id;
		$response                                     = $this->ced_walmart_process_instance->ced_walmart_process_request( $action, array(), $query_args, 'GET' );

		echo '<pre>';
		print_r($response);
		die('>>>');
		return $response;
	}

	/**
	 * Woocommerce_Walmart_Integration_Admin ced_walmart_acknowledge_order.
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_acknowledge_order() {
		$check_ajax = check_ajax_referer( 'ced-walmart-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$order_id = isset( $_POST['order_id'] ) ? sanitize_text_field( $_POST['order_id'] ) : '';
			$order    = wc_get_order($order_id);
			if ( ! empty( $order_id ) ) {
				$response = $this->ced_walmart_order_manager->acknowledge_order( $order_id );
				if ( isset( $response['error'] ) ) {
					$status  = 400;
					$message = isset( $response['error'][0]['description'] ) ? $response['error'][0]['description'] : 'some error occurred';
				} elseif ( isset( $response['errors']['error'] ) ) {
					$status  = 400;
					$message = isset( $response['errors']['error'][0]['description'] ) ? $response['errors']['error'][0]['description'] : 'some error occurred';
				} elseif ( isset( $response['order']['orderLines']['orderLine'][0] ) ) {
					$order->add_meta_data( '_ced_walmart_order_status', 'Acknowledged' );
					$order->save();
					$status  = 200;
					$message = 'Order acknowledged successfully.';
				}
			}

			echo json_encode(
				array(
					'status'  => $status,
					'message' => $message,
				)
			);
			wp_die();
		}
	}


		/**
		 * Woocommerce_Walmart_Integration_Admin ced_walmart_shipment_order.
		 *
		 * @since 1.0.0
		 */
	public function ced_walmart_shipment_order() {
		$check_ajax = check_ajax_referer( 'ced-walmart-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$post              = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$store_id          = isset( $post['store_id'] ) ? $post['store_id'] : '';
			$order_id          = isset( $post['order_id'] ) ? $post['order_id'] : '';
			$carrier           = isset( $post['carrier'] ) ? $post['carrier'] : '';
			$method_code       = isset( $post['methodCode'] ) ? $post['methodCode'] : '';
			$order             = isset( $post['order'] ) ? $post['order'] : '';
			$tracking          = isset( $post['tracking'] ) ? $post['tracking'] : '';
			$tracking_url      = isset( $post['tracking_url'] ) ? $post['tracking_url'] : '';
			$ship_todate       = isset( $post['ship_todate'] ) ? $post['ship_todate'] : '';
			$offset            = '.000Z';
			$ship_todate       = gmdate( 'Y-m-d', strtotime( $ship_todate ) ) . 'T' . gmdate( 'H:i:s', strtotime( $ship_todate ) ) . $offset;
			$return_address    = get_return_address();
			$_order            = wc_get_order($order);
			$order_line_items  = $_order->get_meta( 'order_detail', true );
			$ship_order_lines  = array();
			$purchase_order_id = $order_line_items['purchaseOrderId'];
			foreach ( $order_line_items['orderLines']['orderLine'] as $key => $value ) {
				$line_array['lineNumber']        = $value['lineNumber'];
				$line_array['sellerOrderId']     = $order_id;
				$line_array['orderLineStatuses'] = array(
					'orderLineStatus' => array(
						array(
							'status'         => 'Shipped',
							'statusQuantity' => array(
								'unitOfMeasurement' => 'EACH',
								'amount'            => '1',
							),
							'trackingInfo'   => array(
								'shipDateTime'   => $ship_todate,
								'carrierName'    => array(
									'otherCarrier' => null,
									'carrier'      => $carrier,
								),
								'methodCode'     => $method_code,
								'trackingNumber' => $tracking,
								'trackingURL'    => esc_url( $tracking_url ),
							),
						),
					),
				);

				$ship_order_lines[] = $line_array;
			}

			$shipment_array['orderShipment']['orderLines']['orderLine'] = $ship_order_lines;
			$action = 'orders/' . $purchase_order_id . '/shipping';
			/** Refresh token hook for walmart
			 *
			 * @since 1.0.0
			 */
			do_action( 'ced_walmart_refresh_token', $store_id );
			$this->ced_walmart_process_instance->store_id = $store_id;
			$response                                     = $this->ced_walmart_process_instance->ced_walmart_process_request( $action, $shipment_array, array(), 'POST' );

			if ( isset( $response['error'] ) ) {
				$status  = 400;
				$message = isset( $response['error'][0]['description'] ) ? $response['error'][0]['description'] : 'some error occurred';
			} elseif ( isset( $response['errors']['error'] ) ) {
				$status  = 400;
				$message = isset( $response['errors']['error'][0]['description'] ) ? $response['errors']['error'][0]['description'] : 'some error occurred';
			} elseif ( isset( $response['order']['orderLines']['orderLine'] ) ) {

				$walmart_order_items                        = isset( $response['order']['orderLines']['orderLine'][0] ) ? $response['order']['orderLines']['orderLine'][0] : $response['order']['orderLines']['orderLine'];
				$shipped_detail['purchaseOrderId']          = $response['order']['purchaseOrderId'];
				$shipped_detail['alt_shipment_id']          = 0;
				$shipped_detail['shipment_tracking_number'] = isset( $walmart_order_items['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['trackingNumber'] ) ? $walmart_order_items['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['trackingNumber'] : 0;
				$shipped_detail['response_shipment_date']   = isset( $walmart_order_items['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['shipDateTime'] ) ? $walmart_order_items['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['shipDateTime'] : 0;
				$shipped_detail['response_shipment_method'] = '';
				$shipped_detail['expected_delivery_date']   = isset( $response['order']['shippingInfo']['estimatedDeliveryDate'] ) ? $response['order']['shippingInfo']['estimatedDeliveryDate'] : 0;
				$shipped_detail['ship_from_zip_code']       = 'zipcode';
				$shipped_detail['carrier_pick_up_date']     = isset( $walmart_order_items['statusDate'] ) ? $walmart_order_items['statusDate'] : 0;
				$shipped_detail['carrier']                  = isset( $walmart_order_items['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['carrierName']['carrier'] ) ? $walmart_order_items['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['carrierName']['carrier'] : 0;
				$shipped_detail['shipment_tracking_url']    = isset( $walmart_order_items['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['trackingURL'] ) ? $walmart_order_items['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['trackingURL'] : 0;
				$shipped_detail['methodCode']               = isset( $walmart_order_items['fulfillment']['shipMethod'] ) ? $walmart_order_items['fulfillment']['shipMethod'] : 0;
				$shipped_detail['shipment_items']           = $walmart_order_shipped_item;
				$shipped_detail['cancel_items']             = $walmart_order_cancel_item;

				$walmart_shipped_details['shipments'][0] = $shipped_detail;
				$_order->add_meta_data( '_ced_walmart_order_status', 'Shipped' );
				$_order->add_meta_data( '_ced_walmart_shipped_data', $walmart_shipped_details );
				$_order->update_status( 'wc-completed' );
				$_order->save();
				$status  = 200;
				$message = 'Order shipped successfully.';
			}
		}

		echo json_encode(
			array(
				'status'  => $status,
				'message' => $message,
			)
		);
		wp_die();
	}


	/**
	 * Woocommerce_Walmart_Integration_Admin prepare_order_line_array.
	 *
	 * @since 1.0.0
	 */
	public function prepare_order_line_array( $order_line_items = array() ) {
		$order_line_array = array();
		$order_lines      = array();
		if ( is_array( $order_line_items ) && ! empty( $order_line_items ) ) {
			foreach ( $order_line_items as $key => $value ) {
				$order_line['lineNumber']                           = $value['lineNumber'];
				$order_line['orderLineStatuses']['orderLineStatus'] = array(
					array(
						'status'             => 'Cancelled',
						'cancellationReason' => 'SELLER_CANCEL_OUT_OF_STOCK',
						'statusQuantity'     => array(
							'unitOfMeasurement' => $value['orderLineQuantity']['unitOfMeasurement'],
							'amount'            => $value['orderLineQuantity']['amount'],
						),
					),
				);
				$order_lines[]                                      = $order_line;
			}
		}
		$order_line_array['orderCancellation']['orderLines']['orderLine'] = $order_lines;
		return $order_line_array;
	}



	/**
	 * Woocommerce_Walmart_Integration_Admin ced_walmart_process_bulk_action.
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_process_bulk_action() {
		$check_ajax = check_ajax_referer( 'ced-walmart-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$status              = 400;
			$operation           = isset( $_POST['operation'] ) ? sanitize_text_field( $_POST['operation'] ) : '';
			$post_data           = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$walmart_product_ids = isset( $post_data['walmart_products_ids'] ) ? $post_data['walmart_products_ids'] : array();
			$store_id            = isset( $post_data['store_id'] ) ? $post_data['store_id'] : '';
			if ( 'upload' == $operation ) {
				$process_mode = 'CREATE';
				$response     = $this->ced_walmart_manager_instance->ced_walmart_upload(
					$walmart_product_ids,
					$process_mode,
					$store_id
				);
			} elseif ( 'update_price' == $operation ) {
				$response = $this->ced_walmart_manager_instance->ced_walmart_update_price(
					$walmart_product_ids,
					$store_id
				);
			} elseif ( 'update_stock' == $operation ) {
				$response = $this->ced_walmart_manager_instance->ced_walmart_update_stock(
					$walmart_product_ids,
					$store_id
				);
			} elseif ( 'update_shipping_template' == $operation ) {
				$response   = $this->ced_walmart_manager_instance->ced_walmart_update_shipping_template(
					$walmart_product_ids,
					$action = 'Add',
					$store_id
				);
			} elseif ( 'remove_shipping_template' == $operation ) {
				$response   = $this->ced_walmart_manager_instance->ced_walmart_update_shipping_template(
					$walmart_product_ids,
					$action = 'Remove',
					$store_id
				);
			} elseif ( 'retire_bulk_item' == $operation ) {
				$response = $this->ced_walmart_manager_instance->ced_walmart_retire_items(
					$walmart_product_ids,
					$store_id
				);
			}

			if ( isset( $response['feedId'] ) ) {
				$status  = 200;
				$message = 'Feed uploaded successfully.';
				if ( isset( $response['items_with_erros'] ) ) {
					$message = $message . 'Some items were skipped due to invalid details.';
				}
			} elseif ( isset( $response['errors'] ) ) {
				$message = isset( $response['errors']['error']['description'] ) ? $response['errors']['error']['description'] : '';
			} elseif ( isset( $response['error'][0] ) ) {
				$message = isset( $response['error'][0]['description'] ) ? $response['error'][0]['description'] : '';
				if ( empty( $message ) ) {
					$message = isset( $response['error'][0]['info'] ) ? $response['error'][0]['info'] : '';

				}
			} else {
				$message = 'Feed not uploaded.';
			}
			echo json_encode(
				array(
					'status'  => $status,
					'message' => $message,
				)
			);
			wp_die();
		}
	}





	/**
	 * Woocommerce_Walmart_Integration_Admin ced_walmart_get_all_shipping_template
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_get_all_shipping_template() {

		$check_ajax = check_ajax_referer( 'ced-walmart-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$action   = 'shippingtemplates';
			$status   = 400;
			$message  = 'Some error occurred';
			$store_id = isset( $_POST['store_id'] ) ? sanitize_text_field( $_POST['store_id'] ) : '';
			/** Refresh token hook for walmart
			 *
			 * @since 1.0.0
			 */
			do_action( 'ced_walmart_refresh_token', $store_id );
			$this->ced_walmart_process_instance->store_id = $store_id;
			$response                                     = $this->ced_walmart_process_instance->ced_walmart_process_request( $action, array() , array() , 'GET' );
			if ( isset( $response['result']['shippingTemplates'] ) ) {
				$shipping_templates = json_encode( $response['result'] );
				update_option( 'ced_walmart_shipping_templates' . wifw_environment() . $store_id, $shipping_templates, true );
				$status  = 200;
				$message = 'Shipping Templates Fetched Successfully';
			}

			if ( isset( $response['error'] ) ) {
				$status  = 400;
				$message = isset( $response['error'][0]['description'] ) ? $response['error'][0]['description'] : '';
			}

			echo json_encode(
				array(
					'status'  => $status,
					'message' => $message,
				)
			);
			wp_die();
		}
	}
		/**
		 * Woocommerce_Walmart_Integration_Admin ced_walmart_save_fulfillment_center
		 *
		 * @since 1.0.0
		 */
	public function ced_walmart_save_fulfillment_center() {
		$check_ajax = check_ajax_referer( 'ced-walmart-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$action  = 'shipnodes';
			$status  = 400;
			$message = 'Some error occurred';

			$store_id = isset( $_POST['store_id'] ) ? sanitize_text_field( $_POST['store_id'] ) : '';

			/**
		 * Refresh token hook for walmart
		 *
		 * @since  1.0.0
		 */
			do_action( 'ced_walmart_refresh_token', $store_id );
			$this->ced_walmart_process_instance->store_id = $store_id;
			$response                                     = $this->ced_walmart_process_instance->ced_walmart_process_request( $action, array(), array(), 'GET' );
			if ( isset( $response['result'][0]['shipNode'] ) ) {
				$fulfillment_center = json_encode( $response );
				update_option( 'ced_walmart_fulfillment_center' . wifw_environment() . $store_id, $fulfillment_center, true );
				$status  = 200;
				$message = "Fulfillment Center's Fetched Successfully";
			}

			if ( isset( $response['error'] ) ) {
				$status  = 400;
				$message = isset( $response['error'][0]['description'] ) ? $response['error'][0]['description'] : '';
			}

			echo json_encode(
				array(
					'status'  => $status,
					'message' => $message,
				)
			);
			wp_die();

		}
	}

			/**
			 * Woocommerce_Walmart_Integration_Admin ced_walmart_save_shipping_template
			 *
			 * @since 1.0.0
			 */
	public function ced_walmart_save_shipping_template() {

		$check_ajax = check_ajax_referer( 'ced-walmart-ajax-seurity-string', 'ajax_nonce' );
		$saveData   = array();

		if ( $check_ajax ) {
			$sanitized                = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$template_name            = isset( $sanitized['templateName'] ) ? sanitize_text_field( $sanitized['templateName'] ) : '';
			$store_id                 = isset( $sanitized['store_id'] ) ? sanitize_text_field( $sanitized['store_id'] ) : '';
			$type                     = isset( $sanitized['type'] ) ? sanitize_text_field( $sanitized['type'] ) : 'CUSTOM';
			$rate_model_type          = isset( $sanitized['rateModelType'] ) ? sanitize_text_field( $sanitized['rateModelType'] ) : 'PER_SHIPMENT_PRICING';
			$status                   = isset( $sanitized['status'] ) ? sanitize_text_field( $sanitized['status'] ) : 'ACTIVE';
			$shipping_method_value    = isset( $sanitized['shippingMethodValue'] ) ? $sanitized['shippingMethodValue'] : '';
			$shipping_method_standard = isset( $sanitized['shippingMethodStandard'] ) ? $sanitized['shippingMethodStandard'] : '';

			$shipping_method_2day = isset( $sanitized['shippingMethod2day'] ) ? $sanitized['shippingMethod2day'] : '';

			if ( 'TIERED_PRICING' === $rate_model_type ) {
				$model             = 'tieredShippingCharges';
				$shippingCharges[] = array(
					'minLimit'   => intval( $shipping_method_value['minLimit'] ),
					'maxLimit'   => intval( $shipping_method_value['maxLimit'] ),
					'shipCharge' => array(
						'amount'   => floatval( $shipping_method_value['shipCharge'] ),
						'currency' => 'USD',
					),
				);
			} else {
				$model                                  = 'perShippingCharge';
				$shippingCharges['unitOfMeasure']       = 'LB';
				$shippingCharges['shippingAndHandling'] = array(
					'amount'   => 0,
					'currency' => 'USD',
				);
				$shippingCharges['chargePerItem']       = array(
					'amount'   => 0,
					'currency' => 'USD',
				);
			}

			$saveData['name']          = $template_name;
			$saveData['type']          = $type;
			$saveData['rateModelType'] = $rate_model_type;
			$saveData['status']        = 'ACTIVE';

			// Data Prepration For Value Type Template

			$regionsForValue[] = array(
				'regionCode' => 'C',
				'regionName' => '48 State',
			);
			$valueMethod       = array(
				'shipMethod' => strtoupper( $shipping_method_value['shipMethod'] ),
				'status'     => strtoupper( $shipping_method_value['status'] ),
			);
			$configurations[]  = array(
				'regions'      => $regionsForValue,
				'addressTypes' => array( $shipping_method_value['addressTypes'] ),
				'transitTime'  => intval( $shipping_method_value['transitTime'] ),
				$model         => $shippingCharges,
			);

			$valueMethod['configurations'] = $configurations;

			$saveData['shippingMethods'][] = $valueMethod;

			// Data Prepared For Value Type Template

			// Data Prepration For Standard Type Template

			if ( isset( $shipping_method_standard ) && is_array( $shipping_method_standard ) ) {

				$standardMethod = array(
					'shipMethod' => 'STANDARD',
					'status'     => strtoupper( $status ),
				);

				$standardConfigurations = array();

				foreach ( $shipping_method_standard as $key => $value ) {
					$regionsforStandard      = json_decode( stripslashes( $value['regions'] ), 1 );
					$formattedArrayRegion    = $this->formatArray( $regionsforStandard );
					$addressTypesforStandard = strtoupper( $value['addressTypes'] );
					$transitTimeforStandard  = strtoupper( $value['transitTime'] );
					if ( isset( $value['tieredShippingCharges'] ) && ! empty( $value['tieredShippingCharges'] ) ) {
						foreach ( $value['tieredShippingCharges'] as $elements => $elementValue ) {
							$minLimit   = floatval( $elementValue['minLimit'] );
							$maxLimit   = floatval( $elementValue['maxLimit'] );
							$shipCharge = array(
								'amount'   => intval( $elementValue['shipCharge'] ),
								'currency' => 'USD',
							);
						}

						$standardConfigurations[] = array(
							'regions'               => array( $formattedArrayRegion ),
							'addressTypes'          => array( $addressTypesforStandard ),
							'transitTime'           => intval( $transitTimeforStandard ),
							'tieredShippingCharges' => array(
								array(
									'minLimit'   => $minLimit,
									'maxLimit'   => $maxLimit,
									'shipCharge' => $shipCharge,
								),
							),
						);
					} else {
						if ( isset( $value['perShippingCharge'] ) && ! empty( $value['perShippingCharge'] ) ) {
							$shippingCharges['unitOfMeasure']            = 'LB';
							$shippingCharges['shippingAndHandling']      = array(
								'amount'   => floatval( $value['perShippingCharge'][0] ),
								'currency' => 'USD',
							);
							$shippingCharges[ $value['shipchargeName'] ] = array(
								'amount'   => floatval( $value['shipcharge'] ),
								'currency' => 'USD',
							);
						}

						$standardConfigurations[] = array(
							'regions'           => array( $formattedArrayRegion ),
							'addressTypes'      => array( $addressTypesforStandard ),
							'transitTime'       => intval( $transitTimeforStandard ),
							'perShippingCharge' => $shippingCharges,
						);

					}
				}

				$standardMethod['configurations'] = $standardConfigurations;
				$saveData['shippingMethods'][]    = $standardMethod;

			}

			// Data Prepared For Standard Type Template

			// Data Prepartaion For 2 Day Shipping Template
			if ( isset( $shipping_method_2day ) && is_array( $shipping_method_2day ) ) {
				$two_day_method         = array(
					'shipMethod' => 'TWO_DAY',
					'status'     => strtoupper( $status ),
				);
				$two_day_Configurations = array();
				foreach ( $shipping_method_2day as $key => $value ) {
					$regionsfor2day       = json_decode( stripslashes( $value['regions'] ), 1 );
					$formattedArrayRegion = $this->formatArray( $regionsfor2day );
					$addressTypesfor2day  = strtoupper( $value['addressTypes'] );
					$transitTimefor2day   = 2;

					if ( 'TIERED_PRICING' === $rate_model_type ) {

						$minLimit   = 00;
						$maxLimit   = -1;
						$shipCharge = array(
							'amount'   => 00,
							'currency' => 'USD',
						);

						$two_day_Configurations[] = array(
							'regions'               => array( $formattedArrayRegion ),
							'addressTypes'          => array( $addressTypesfor2day ),
							'transitTime'           => intval( $transitTimefor2day ),
							'tieredShippingCharges' => array(
								array(
									'minLimit'   => $minLimit,
									'maxLimit'   => $maxLimit,
									'shipCharge' => $shipCharge,
								),
							),
						);

					} else {

						$shippingCharges['unitOfMeasure']       = 'LB';
						$shippingCharges['shippingAndHandling'] = array(
							'amount'   => 00,
							'currency' => 'USD',
						);
						$shippingCharges['chargePerItem']       = array(
							'amount'   => 00,
							'currency' => 'USD',
						);

						$two_day_Configurations[] = array(
							'regions'           => array( $formattedArrayRegion ),
							'addressTypes'      => array( $addressTypesfor2day ),
							'transitTime'       => intval( $transitTimefor2day ),
							'perShippingCharge' => $shippingCharges,
						);

					}
				}

				$two_day_method['configurations'] = $two_day_Configurations;
				$saveData['shippingMethods'][]    = $two_day_method;

			}

			// Data Prepared For 2 Day Shipping Template

			$action  = 'settings/shipping/templates';
			$status  = 400;
			$message = 'Some error occurred';
			/** Refresh token hook for walmart
			 *
			 * @since 1.0.0
			 */
			do_action( 'ced_walmart_refresh_token', $store_id );
			$this->ced_walmart_process_instance->store_id = $store_id;
			$response                                     = $this->ced_walmart_process_instance->ced_walmart_process_request( $action, $saveData, array(), 'POST' );

			if ( isset( $response['id'] ) ) {
				$status  = 200;
				$message = 'Shipping Template Created Successfully';
			}

			if ( isset( $response['errors'] ) ) {
				$status  = 400;
				$message = isset( $response['errors'][0]['description'] ) ? $response['errors'][0]['description'] : 'Some error occured';
			}

			echo json_encode(
				array(
					'status'  => $status,
					'message' => $message,
				)
			);

		}

		wp_die();
	}



	public function formatArray( $regionsforStandard ) {
		$prep_array       = array();
		$sub_region_array = array();
		foreach ( $regionsforStandard as $key => $value ) {
			$sub_region_array[ $value['subRegions']['subRegionCode'] ]['subRegionCode'] = $value['subRegions']['subRegionCode'];
			$sub_region_array[ $value['subRegions']['subRegionCode'] ]['subRegionName'] = $value['subRegions']['subRegionName'];
			$sub_region_array[ $value['subRegions']['subRegionCode'] ]['states'][ $value['subRegions']['states']['stateCode'] ]['stateCode'] = $value['subRegions']['states']['stateCode'];
			$sub_region_array[ $value['subRegions']['subRegionCode'] ]['states'][ $value['subRegions']['states']['stateCode'] ]['stateName'] = $value['subRegions']['states']['stateName'];
			$sub_region_array[ $value['subRegions']['subRegionCode'] ]['states'][ $value['subRegions']['states']['stateCode'] ]['stateSubregions'][ $value['subRegions']['states']['stateSubregions']['stateSubregionCode'] ]['stateSubregionCode'] = $value['subRegions']['states']['stateSubregions']['stateSubregionCode'];
			$sub_region_array[ $value['subRegions']['subRegionCode'] ]['states'][ $value['subRegions']['states']['stateCode'] ]['stateSubregions'][ $value['subRegions']['states']['stateSubregions']['stateSubregionCode'] ]['stateSubregionName'] = $value['subRegions']['states']['stateSubregions']['stateSubregionName'];

		}
		$sub_region_array = array_values( $sub_region_array );
		$temp             = array();
		foreach ( $sub_region_array as $key => $value ) {
			$value['states'] = array_values( $value['states'] );
			$new_temp        = array();
			foreach ( $value['states'] as $k => $v ) {
				$v['stateSubregions'] = array_values( $v['stateSubregions'] );
				$new_temp[]           = $v;
			}
			$value['states'] = $new_temp;
			$temp[]          = $value;
		}
		$prep_array['regionCode'] = 'C';
		$prep_array['regionName'] = '48 State';
		$prep_array['subRegions'] = $temp;
		return $prep_array;
	}


	/**
	 * Woocommerce_Walmart_Integration_Admin ced_walmart_save_shipping_template_paid_standard
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_save_shipping_template_paid_standard() {

		$check_ajax               = check_ajax_referer( 'ced-walmart-ajax-seurity-string', 'ajax_nonce' );
		$ced_walmart_regions_paid = CED_WALMART_DIRPATH . 'admin/walmart/lib/json/walmart-regions.json';
		if ( file_exists( $ced_walmart_regions_paid ) ) {
			$regions_paid_encoded = file_get_contents( $ced_walmart_regions_paid );
			$regions_paid_encoded = json_decode( $regions_paid_encoded, true );
		}
		$saveData = array(); // Main Array

		if ( $check_ajax ) {
			$template_name   = isset( $_POST['templateName'] ) ? sanitize_text_field( $_POST['templateName'] ) : '';
			$store_id        = isset( $sanitized['store_id'] ) ? sanitize_text_field( $sanitized['store_id'] ) : '';
			$type            = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'CUSTOM';
			$rate_model_type = isset( $_POST['rateModelType'] ) ? sanitize_text_field( $_POST['rateModelType'] ) : 'PER_SHIPMENT_PRICING';
			$status          = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'ACTIVE';
			$transitTime     = isset( $_POST['transitTime'] ) ? sanitize_text_field( $_POST['transitTime'] ) : '';

			if ( 'TIERED_PRICING' === $rate_model_type ) {
				$model           = 'tieredShippingCharges';
				$minLimit        = isset( $_POST['minLimit'] ) ? sanitize_text_field( $_POST['minLimit'] ) : 0;
				$maxLimit        = isset( $_POST['maxLimit'] ) ? sanitize_text_field( $_POST['maxLimit'] ) : 0;
				$shipCharge      = isset( $_POST['shipCharge'] ) ? sanitize_text_field( $_POST['shipCharge'] ) : '';
				$shippingCharges = array(
					'minLimit'   => intval( $minLimit ),
					'maxLimit'   => intval( $maxLimit ),
					'shipCharge' => array(
						'amount'   => floatval( $shipCharge ),
						'currency' => 'USD',
					),
				);
			} else {
				$model               = 'perShippingCharge';
				$shippingAndHandling = isset( $_POST['rate'] ) ? sanitize_text_field( $_POST['rate'] ) : 0;

				$shipChargePerShipping            = isset( $_POST['shipChargePerShipping'] ) ? sanitize_text_field( $_POST['shipChargePerShipping'] ) : 0;
				$shipChargeName                   = isset( $_POST['shipChargeName'] ) ? sanitize_text_field( $_POST['shipChargeName'] ) : '';
				$shippingCharges['unitOfMeasure'] = 'LB';

				$shippingCharges['shippingAndHandling'] = array(
					'amount'   => intval( $shippingAndHandling ),
					'currency' => 'USD',
				);
				$shippingCharges[ $shipChargeName ]     = array(
					'amount'   => intval( $shipChargePerShipping ),
					'currency' => 'USD',
				);
			}

			$saveData['name']                       = $template_name;
			$saveData['type']                       = $type;
			$saveData['rateModelType']              = $rate_model_type;
			$saveData['status']                     = strtoupper( $status );
			$regions_paid_standard                  = $regions_paid_encoded;
			$paid_standard_method                   = array(
				'shipMethod' => 'STANDARD',
				'status'     => strtoupper( $status ),
			);
			$configurations[]                       = array(
				'regions'      => $regions_paid_standard,
				'addressTypes' => array( 'STREET' ),
				'transitTime'  => intval( $transitTime ),
				$model         => $shippingCharges,
			);
			$paid_standard_method['configurations'] = $configurations;
			$saveData['shippingMethods'][]          = $paid_standard_method;
			$action                                 = 'settings/shipping/templates';
			$status                                 = 400;
			$message                                = 'Some error occurred';
			/** Refresh token hook for walmart
			 *
			 * @since 1.0.0
			 */
			do_action( 'ced_walmart_refresh_token', $store_id );
			$this->ced_walmart_process_instance->store_id = $store_id;
			$response                                     = $this->ced_walmart_process_instance->ced_walmart_process_request( $action, $saveData, array(), 'POST' );

			if ( isset( $response['id'] ) ) {
				$status  = 200;
				$message = 'Shipping Template Created Successfully';
			}

			if ( isset( $response['errors'] ) ) {
				$status  = 400;
				$message = isset( $response['errors'][0]['description'] ) ? $response['errors'][0]['description'] : 'Some error occured';
			}
			echo json_encode(
				array(
					'status'  => $status,
					'message' => $message,
				)
			);

		}

		wp_die();
	}



	public function ced_walmart_refresh_insights_keys() {
		$check_ajax = check_ajax_referer( 'ced-walmart-ajax-seurity-string', 'ajax_nonce' );

		if ( $check_ajax ) {
			$store_id                         = isset( $_POST['store_id'] ) ? sanitize_text_field( $_POST['store_id'] ) : '';
			$response_pro_seller_badge        = $this->ced_walmart_fetch_pro_seller_badge( $store_id );
			$response_overall_listing_quality = $this->ced_walmart_fetch_over_all_listing_quality( $store_id );
			$response_unpublished_reports     = $this->ced_walmart_fetch_unpublished_reports( $store_id );
			if ( 200 == $response_pro_seller_badge['code'] && 200 == $response_overall_listing_quality['code'] ) {
				echo json_encode(
					array(
						'status'  => 200,
						'message' => 'Data Fetched Successfully for Pro Seller Badge and Overall Listing Quality ,Reloading ...',
					)
				);
			} elseif ( 200 == $response_pro_seller_badge['code'] && 400 == $response_overall_listing_quality['code'] ) {
				echo json_encode(
					array(
						'status'  => 200,
						'message' => 'Data Fetched Successfully for Pro Seller Badge and Failed for overall listing quality , Reloading ...',
					)
				);
			} elseif ( 400 == $response_pro_seller_badge['code'] && 200 == $response_overall_listing_quality['code'] ) {

				echo json_encode(
					array(
						'status'  => 200,
						'message' => 'Data Fetched Successfully for  overall listing quality  and Failed for Pro Seller Badge , Reloading ... ',
					)
				);

			} else {
				echo json_encode(
					array(
						'status'  => 400,
						'message' => 'Something went wrong !!!!',
					)
				);
			}
		}

		wp_die();
	}


	// Function for fetching the pro seller badge data
	public function ced_walmart_fetch_pro_seller_badge( $store_id = '' ) {
		$action     = 'prosellerbadge';
		$result     = array();
		$query_args = array();
		/** Refresh token hook for walmart
		 *
		 * @since 1.0.0
		 */
		do_action( 'ced_walmart_refresh_token', $store_id );
		$this->ced_walmart_process_instance->store_id = $store_id;
		$response                                     = $this->ced_walmart_process_instance->ced_walmart_process_request( $action, array(), $query_args, 'GET' );
		if ( isset( $response['result']['meetsCriteria'] ) ) {
			update_option( 'ced_walmart_pro_seller_badge_details' . $store_id, json_encode( $response ) );

			$result['code']    = 200;
			$result['message'] = 'Data Fetched Successfully for Pro Seller Badge';
		} elseif ( isset( $response['error'] ) ) {
			$result['code']    = 400;
			$result['message'] = $response['error'][0]['info'];

		} else {
			$result['code']    = 400;
			$result['message'] = 'Internal Error';
		}

		return $result;
	}


	// Function for fetching the Overall Listing Quality data
	public function ced_walmart_fetch_over_all_listing_quality( $store_id = '' ) {
		$action     = 'qualityscore';
		$result     = array();
		$query_args = array();
		/** Refresh token hook for walmart
		 *
		 * @since 1.0.0
		 */
		do_action( 'ced_walmart_refresh_token' );
		$response = $this->ced_walmart_process_instance->ced_walmart_process_request( $action, array(), $query_args, 'GET' );
		if ( isset( $response['result']['payload'] ) ) {
			update_option( 'ced_walmart_overall_listing_quality_details' . $store_id, json_encode( $response['result'] ) );
			$result['code']    = 200;
			$result['message'] = 'Data Fetched Successfully for overall listing quality';
		} elseif ( isset( $response['error']['error'] ) ) {
			$result['code']    = 400;
			$result['message'] = $response['error']['error'][0]['info'];
		} else {
			$result['code']    = 400;
			$result['message'] = 'Internal Error';
		}

		return $result;
	}


	// Function for fetching the Overall Listing Quality data
	public function ced_walmart_fetch_unpublished_reports( $store_id = '' ) {
		$action     = 'itemcount';
		$fromDate   = gmdate( 'Y-m-d', strtotime( ' -30 day' ) );
		$result     = array();
		$query_args = array(
			'fromDate' => $fromDate,
		);
		/** Refresh token hook for walmart
		 *
		 * @since 1.0.0
		 */
		do_action( 'ced_walmart_refresh_token' );
		$response = $this->ced_walmart_process_instance->ced_walmart_process_request( $action, array(), $query_args, 'GET' );
		if ( isset( $response['result']['payload'] ) ) {
			update_option( 'ced_walmart_unpublished_items_counts' . $store_id, json_encode( $response ) );
			$result['code']    = 200;
			$result['message'] = 'Data Fetched Successfully for unpublished counts';
		} elseif ( isset( $response['error']['error'] ) ) {
			$result['code']    = 400;
			$result['message'] = $response['error']['error'][0]['info'];
		} else {
			$result['code']    = 400;
			$result['message'] = 'Internal Error';
		}
		return $result;
	}

	// Function for fecthing listing quality for individual product
	public function ced_walmart_save_listing_quality_for_product() {

		$check_ajax = check_ajax_referer( 'ced-walmart-ajax-seurity-string', 'ajax_nonce' );

		if ( $check_ajax ) {
			$product_id = isset( $_POST['product_id'] ) ? sanitize_text_field( $_POST['product_id'] ) : '';
			require_once CED_WALMART_DIRPATH . 'admin/partials/class-ced-walmart-listing-quality-product-level.php';

			$obj = new Ced_Walmart_Insights_Product_Level();

			$_product = wc_get_product( $product_id );
			$type     = $_product->get_type();
			if ( 'variable' == $type ) {
				$variations = $_product->get_children();
				if ( is_array( $variations ) && ! empty( $variations ) ) {
					foreach ( $variations as $index => $variation_id ) {
						$sku      = get_post_meta( $variation_id, '_sku', true );
						$response = $this->ced_walmart_fetch_product_listing_quality_data( $sku );
						if ( isset( $response ) && ! empty( $response['payload'] ) && 'OK' == $response['status'] ) {
							update_post_meta( $variation_id, 'ced_walmart_listing_quality_data', json_encode( $response['payload'] ) );
							update_post_meta( $variation_id, 'ced_walmart_listing_quality_data_available', true );
						}
					}
				}
			} else {
				$sku      = get_post_meta( $product_id, '_sku', true );
				$response = $this->ced_walmart_fetch_product_listing_quality_data( $sku );
				if ( isset( $response ) && ! empty( $response['payload'] ) && 'OK' == $response['status'] ) {
					update_post_meta( $product_id, 'ced_walmart_listing_quality_data', json_encode( $response['payload'] ) );
					update_post_meta( $product_id, 'ced_walmart_listing_quality_data_available', true );

				}
			}

			if ( 'variable' == $type ) {
				$html       = '';
				$variations = $_product->get_children();
				if ( is_array( $variations ) && ! empty( $variations ) ) {
					foreach ( $variations as $index => $variation_id ) {
						$body = $obj->ced_walmart_prepare_html_for_listing_quality( $variation_id );
						if ( empty( $body ) ) {
							continue;
						}
						$variation     = new WC_Product_Variation( $variation_id );
						$variationName = implode( ' / ', $variation->get_variation_attributes() );
						$html         .= '<div class="accordion mt-2" id="accordion' . $variation_id . '">
						<div class="accordion-item">
						<h2 class="accordion-header" id="headingOne">
						<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $variation_id . '" aria-expanded="true" aria-controls="collapse' . $variation_id . '">
						Variation Name :' . $variationName . '
						</button>
						</h2>
						<div id="collapse' . $variation_id . '" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordion' . $variation_id . '">
						<div class="accordion-body"></div>
						' . $body . '
						</div>
						</div>
						</div>
						';
					}
				}

				echo json_encode( $html );

			} else {
				$html = $obj->ced_walmart_prepare_html_for_listing_quality( $product_id );
				echo json_encode( $html );

			}
		}

		wp_die();
	}

	public function ced_walmart_fetch_product_listing_quality_data( $sku = '' ) {
		$action                       = 'insights/items/listingQuality/items';
		$parameters                   = array();
		$parameters['query']['field'] = 'sku';
		$parameters['query']['value'] = $sku;
		$query_args                   = array();
		/** Refresh token hook for walmart
		 *
		 * @since 1.0.0
		 */
		do_action( 'ced_walmart_refresh_token' );
		$response = $this->ced_walmart_process_instance->ced_walmart_process_request( $action, $parameters, $query_args, 'POST' );
		return $response;
	}

	public function ced_walmart_delete_account() {
		$check_ajax = check_ajax_referer( 'ced-walmart-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$store_id        = isset( $sanitized_array['store_id'] ) ? $sanitized_array['store_id'] : '';
			$account_list    = ced_walmart_return_partner_detail_option();
			unset( $account_list[ $store_id ] );
			update_option( 'ced_walmart_saved_account_list', json_encode( $account_list ) );
			die;
		}
	}


	public function ced_walmart_append_category_attr() {
		$check_ajax = check_ajax_referer( 'ced-walmart-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
		
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$catId           = isset( $sanitized_array['catId'] ) ? html_entity_decode( $sanitized_array['catId'] ) : '';
			$profile_type    = isset( $sanitized_array['profileType'] ) ? html_entity_decode( $sanitized_array['profileType'] ) : '';
			$profile_id      = str_replace( ' and ', ' & ', $catId );

			include_once CED_WALMART_DIRPATH . 'admin/partials/class-ced-walmart-category-attributes.php';

			$obj = new Walmart_Category_Attributes( $catId, $profile_type );
			print_r( $obj->render_attributes( $catId , $profile_type ) );

		}
		wp_die();
	}
}




