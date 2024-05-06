<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$file = CED_EBAY_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $file ) ) {
	require_once $file;
}

$shop_data = ced_ebay_get_shop_data( $user_id, $site_id );
if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
	$token = $shop_data['access_token'];
}
function ced_ebay_trimKeysRecursive( $array ) {
	$trimmedArray = array();
	foreach ( $array as $key => $value ) {
		$trimmedKey = trim( $key );
		if ( is_array( $value ) ) {
			$trimmedArray[ $trimmedKey ] = ced_ebay_trimKeysRecursive( $value );
		} else {
			$trimmedArray[ $trimmedKey ] = $value;
		}
	}
	return $trimmedArray;
}
if ( isset( $_POST['ced_ebay_setting_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_ebay_setting_nonce'] ), 'ced_ebay_setting_page_nonce' ) ) {
	if ( isset( $_POST['global_settings'] ) ) {
		$objDateTime = new DateTime( 'NOW' );
		$timestamp   = $objDateTime->format( 'Y-m-d\TH:i:s\Z' );

		$settings                         = array();
		$sanitized_array                  = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
		$settings                         = get_option( 'ced_ebay_global_settings', array() );
		$settings[ $user_id ][ $site_id ] = isset( $sanitized_array['ced_ebay_global_settings'] ) ? ced_ebay_trimKeysRecursive( $sanitized_array['ced_ebay_global_settings'] ) : array();
		if ( ! empty( $settings[ $user_id ][ $site_id ]['ced_ebay_vat_percent'] ) && 0 < $settings[ $user_id ][ $site_id ]['ced_ebay_vat_percent'] ) {
			$formatted_vat_percent                                    = number_format( $settings[ $user_id ][ $site_id ]['ced_ebay_vat_percent'], 1 );
			$settings[ $user_id ][ $site_id ]['ced_ebay_vat_percent'] = $formatted_vat_percent;
		}
		$settings[ $user_id ][ $site_id ]['last_updated'] = $timestamp;
		update_option( 'ced_ebay_global_settings', $settings );

		$attribute_name = isset( $sanitized_array['ced_ebay_custom_item_specific']['attribute'] ) ? $sanitized_array['ced_ebay_custom_item_specific']['attribute'] : '';

		if ( isset( $settings[ $user_id ][ $site_id ]['ced_ebay_shipping_policy'] ) && ! empty( $settings[ $user_id ][ $site_id ]['ced_ebay_shipping_policy'] ) ) {
			$selectedFulfillmentPolicy = $settings[ $user_id ][ $site_id ]['ced_ebay_shipping_policy'];
			$ship_array                = explode( '|', $selectedFulfillmentPolicy );
			$ship_bussiness_id         = $ship_array[0];
			$ship_bussiness_name       = $ship_array[1];
			if ( ! empty( $ship_bussiness_id ) ) {
				if ( ! file_exists( CED_EBAY_DIRPATH . 'admin/ebay/lib/cedMarketingRequest.php' ) ) {
					return false;
				}
				require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/cedMarketingRequest.php';
				$accountApiRequest = new Ced_Marketing_API_Request( $site_id );
				$getPolicyResponse = $accountApiRequest->sendHttpRequestForAccountAPI( 'fulfillment_policy/' . $ship_bussiness_id, $token );
				if ( ! is_array( json_decode( $getPolicyResponse, true ) ) ) {
					return false;
				}
				$getPolicyDetails = json_decode( $getPolicyResponse, true );
				if ( ! empty( $getPolicyDetails ) ) {
					update_option( 'ced_ebay_business_policy_details_' . $user_id . '>' . $site_id, $getPolicyDetails );
				}
			}
		}
		// UPDATE GLOBAL OPTIONS

		$global_options          = get_option( 'ced_ebay_global_options', array() );
		$selected_global_options = isset( $sanitized_array['ced_ebay_global_options'][ $user_id ][ $site_id ] ) ? ( $sanitized_array['ced_ebay_global_options'][ $user_id ][ $site_id ] ) : array();
		if ( ! empty( $selected_global_options ) ) {
			$global_options_array = array();
			foreach ( $selected_global_options as $gKey => $gValue ) {
				$explode_gKey = explode( '|', $gKey );
				$global_options_array[ $explode_gKey[0] ][ $explode_gKey[1] ] = $gValue;
			}
		}
		if ( ! empty( $global_options_array ) ) {
			$global_options[ $user_id ][ $site_id ] = $global_options_array;
			update_option( 'ced_ebay_global_options', $global_options );
		}

		if ( ! empty( $attribute_name ) ) {
			$custom_item_specific                           = get_option( 'ced_ebay_custom_item_specific', array() );
			$custom_item_specific[ $user_id ][ $site_id ][] = isset( $sanitized_array['ced_ebay_custom_item_specific'] ) ? ( $sanitized_array['ced_ebay_custom_item_specific'] ) : array();
			update_option( 'ced_ebay_custom_item_specific', $custom_item_specific );

			$ced_ebay_global_options = get_option( 'ced_ebay_global_options', array() );
			$ced_ebay_global_options[ $user_id ][ $site_id ][ urlencode( $attribute_name ) ] = array(
				'meta_key'     => isset( $sanitized_array['ced_ebay_custom_item_specific']['meta_key'] ) ? $sanitized_array['ced_ebay_custom_item_specific']['meta_key'] : '',
				'custom_value' => isset( $sanitized_array['ced_ebay_custom_item_specific']['custom_value'] ) ? $sanitized_array['ced_ebay_custom_item_specific']['custom_value'] : '',
			);
			update_option( 'ced_ebay_global_options', $ced_ebay_global_options );

		}

		$scheduler_args                = array(
			'user_id' => $user_id,
			'site_id' => $site_id,
		);
		$inventory_schedule            = isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_inventory_schedule_info'] ) && '0' != $sanitized_array['ced_ebay_global_settings']['ced_ebay_inventory_schedule_info'] ? ( $sanitized_array['ced_ebay_global_settings']['ced_ebay_inventory_schedule_info'] ) : as_unschedule_all_actions( null, null, 'ced_ebay_inventory_scheduler_group_' . $user_id . '>' . $site_id );
		$order_schedule                = isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_order_schedule_info'] ) && '0' != $sanitized_array['ced_ebay_global_settings']['ced_ebay_order_schedule_info'] ? ( $sanitized_array['ced_ebay_global_settings']['ced_ebay_order_schedule_info'] ) : as_unschedule_all_actions( 'ced_ebay_order_scheduler_job_' . $user_id, array( 'data' => $scheduler_args ) );
		$existing_product_sync         = isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_existing_products_sync'] ) && '0' != $sanitized_array['ced_ebay_global_settings']['ced_ebay_existing_products_sync'] ? ( $sanitized_array['ced_ebay_global_settings']['ced_ebay_existing_products_sync'] ) : wp_clear_scheduled_hook( 'ced_ebay_existing_products_sync_job_' . $user_id, $scheduler_args );
		$import_products_schedule      = isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_import_product_scheduler_info'] ) && '0' != $sanitized_array['ced_ebay_global_settings']['ced_ebay_import_product_scheduler_info'] ? ( $sanitized_array['ced_ebay_global_settings']['ced_ebay_import_product_scheduler_info'] ) : wp_clear_scheduled_hook( 'ced_ebay_import_products_job_' . $user_id, $scheduler_args );
		$instant_stock_update          = isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_instant_stock_update'] ) && '0' != $sanitized_array['ced_ebay_global_settings']['ced_ebay_instant_stock_update'] ? ( $sanitized_array['ced_ebay_global_settings']['ced_ebay_instant_stock_update'] ) : false;
		$sync_ended_listings_scheduler = isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_sync_ended_listings_info'] ) && '0' != $sanitized_array['ced_ebay_global_settings']['ced_ebay_sync_ended_listings_info'] ? ( $sanitized_array['ced_ebay_global_settings']['ced_ebay_sync_ended_listings_info'] ) : as_unschedule_all_actions( null, null, 'ced_ebay_sync_ended_listings_group_' . $user_id . '>' . $site_id );
		$auto_upload                   = isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_auto_upload'] ) && '0' != $sanitized_array['ced_ebay_global_settings']['ced_ebay_auto_upload'] ? ( $sanitized_array['ced_ebay_global_settings']['ced_ebay_auto_upload'] ) : as_unschedule_all_actions( null, null, 'ced_ebay_bulk_upload_' . $user_id . '>' . $site_id );
		$plugin_migration              = isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_plugin_migration'] ) && '0' != $sanitized_array['ced_ebay_global_settings']['ced_ebay_plugin_migration'] ? ( $sanitized_array['ced_ebay_global_settings']['ced_ebay_plugin_migration'] ) : 'off';




		if ( 'on' == $instant_stock_update ) {
			if ( class_exists( 'WC_Webhook' ) ) {
				$wp_folder     = wp_upload_dir();
				$wp_upload_dir = $wp_folder['basedir'];
				$logs_folder   = $wp_upload_dir . '/ced-ebay/logs/stock-update/webhook/';
				if ( ! is_dir( $logs_folder ) ) {
					wp_mkdir_p( $logs_folder, 0777 );
					$current_date = new DateTime();
					$current_date = $current_date->format( 'ymd' );
					$log_file     = $logs_folder . 'logs_' . $current_date . '.txt';
					file_put_contents( $log_file, '' );
				}
				delete_option( 'ced_ebay_stock_sync_progress_' . $user_id . '>' . $site_id );
				if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_unschedule_all_actions' ) ) {
					if ( as_has_scheduled_action( 'ced_ebay_inventory_scheduler_job_' . $user_id, array( 'data' => $scheduler_args ) ) ) {
						as_unschedule_all_actions( 'ced_ebay_inventory_scheduler_job_' . $user_id, array( 'data' => $scheduler_args ) );
					}
				}
				$get_webhook_id = ! empty( get_option( 'ced_ebay_prduct_update_webhook_id_' . $user_id, true ) ) ? get_option( 'ced_ebay_prduct_update_webhook_id_' . $user_id, true ) : false;
				if ( $get_webhook_id ) {
					$webhook        = new WC_Webhook( $get_webhook_id );
					$webhook_status = $webhook->get_status();
					if ( 'paused' == $webhook_status ) {
						$webhook->set_status( 'active' );
						$webhook->save();
					}
					if ( 'disabled' == $webhook_status ) {
						$delivery_url = get_admin_url() . 'admin-ajax.php?action=ced_ebay_update_stock_on_webhook';
						$webhook      = new WC_Webhook();
						$webhook->set_name( 'Product update to eBay' );
						$webhook->set_user_id( get_current_user_id() ); // User ID used while generating the webhook payload.
						$webhook->set_topic( 'product.updated' ); // Event used to trigger a webhook.
						$webhook->set_delivery_url( $delivery_url ); // URL where webhook should be sent.
						$webhook->set_status( 'active' ); // Webhook status.
						$webhook_id = $webhook->save();
						if ( ! empty( $webhook_id ) ) {
							update_option( 'ced_ebay_prduct_update_webhook_id_' . $user_id, $webhook_id );
						}
					}
				} else {
					$delivery_url = get_admin_url() . 'admin-ajax.php?action=ced_ebay_update_stock_on_webhook';
					$webhook      = new WC_Webhook();
					$webhook->set_name( 'Product update to eBay' );
					$webhook->set_user_id( get_current_user_id() ); // User ID used while generating the webhook payload.
					$webhook->set_topic( 'product.updated' ); // Event used to trigger a webhook.
					$webhook->set_delivery_url( $delivery_url ); // URL where webhook should be sent.
					$webhook->set_status( 'active' ); // Webhook status.
					$webhook_id = $webhook->save();
					if ( ! empty( $webhook_id ) ) {
						update_option( 'ced_ebay_prduct_update_webhook_id_' . $user_id, $webhook_id );
					}
				}
			}
		} elseif ( class_exists( 'WC_Webhook' ) ) {
				$get_webhook_id = ! empty( get_option( 'ced_ebay_prduct_update_webhook_id_' . $user_id, true ) ) ? get_option( 'ced_ebay_prduct_update_webhook_id_' . $user_id, true ) : false;
			if ( $get_webhook_id ) {
				$webhook        = new WC_Webhook( $get_webhook_id );
				$webhook_status = $webhook->get_status();
				if ( 'active' == $webhook_status ) {
					$webhook->set_status( 'paused' );
					$webhook->save();
				}
			}
		}

		if ( isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_import_product_scheduler_info'] ) && empty( $sanitized_array['ced_ebay_global_settings']['ced_ebay_import_product_scheduler_info'] ) ) {
			update_option( 'ced_ebay_clear_import_process', true );
		}
		if ( isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_inventory_schedule_info'] ) && empty( $sanitized_array['ced_ebay_global_settings']['ced_ebay_inventory_schedule_info'] ) ) {
			delete_option( 'ced_ebay_stock_sync_progress_' . $user_id . '>' . $site_id );
			if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_unschedule_all_actions' ) ) {
				if ( as_has_scheduled_action( null, null, 'ced_ebay_inventory_scheduler_group_' . $user_id . '>' . $site_id ) ) {
					as_unschedule_all_actions( null, null, 'ced_ebay_inventory_scheduler_group_' . $user_id . '>' . $site_id );
				}
			}
		}
		if ( isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_sync_ended_listings_info'] ) && empty( $sanitized_array['ced_ebay_global_settings']['ced_ebay_sync_ended_listings_info'] ) ) {
			if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_unschedule_all_actions' ) ) {
				if ( as_has_scheduled_action( null, null, 'ced_ebay_sync_ended_listings_group_' . $user_id . '>' . $site_id ) ) {
					as_unschedule_all_actions( null, null, 'ced_ebay_sync_ended_listings_group_' . $user_id . '>' . $site_id );
				}
			}
		}
		if ( ! empty( $auto_upload ) && 'on' == $auto_upload && function_exists( 'as_schedule_recurring_action' ) ) {
			if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_unschedule_all_actions' ) ) {
				if ( as_has_scheduled_action( null, null, 'ced_ebay_bulk_upload_' . $user_id . '>' . $site_id ) ) {
					as_unschedule_all_actions( null, null, 'ced_ebay_bulk_upload_' . $user_id . '>' . $site_id );
				}
			}
			$action_scheduled = as_schedule_recurring_action( time(), 360, 'ced_ebay_recurring_bulk_upload_' . $user_id, array( 'data' => $scheduler_args ), 'ced_ebay_bulk_upload_' . $user_id . '>' . $site_id );
		}
		if ( ! empty( $inventory_schedule ) && 'on' == $inventory_schedule && function_exists( 'as_schedule_recurring_action' ) ) {
			if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_unschedule_all_actions' ) ) {
				if ( as_has_scheduled_action( null, null, 'ced_ebay_inventory_scheduler_group_' . $user_id . '>' . $site_id ) ) {
					as_unschedule_all_actions( null, null, 'ced_ebay_inventory_scheduler_group_' . $user_id . '>' . $site_id );
				}
			}
			delete_option( 'ced_eBay_update_chunk_product_' . $user_id . '>' . $site_id );
			as_schedule_recurring_action( time(), 360, 'ced_ebay_inventory_scheduler_job_' . $user_id, array( 'data' => $scheduler_args ), 'ced_ebay_inventory_scheduler_group_' . $user_id . '>' . $site_id );
			if ( class_exists( 'WC_Webhook' ) ) {
				$get_webhook_id = ! empty( get_option( 'ced_ebay_prduct_update_webhook_id_' . $user_id, true ) ) ? get_option( 'ced_ebay_prduct_update_webhook_id_' . $user_id, true ) : false;
				if ( $get_webhook_id ) {
					$webhook        = new WC_Webhook( $get_webhook_id );
					$webhook_status = $webhook->get_status();
					if ( 'active' == $webhook_status ) {
						$webhook->set_status( 'paused' );
						$webhook->save();
					}
				}
			}
			update_option( 'ced_ebay_inventory_scheduler_job_' . $user_id, $user_id );
		}

		if ( ! empty( $order_schedule ) && 'on' == $order_schedule && function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_unschedule_all_actions' ) && function_exists( 'as_schedule_recurring_action' ) ) {
			if ( as_has_scheduled_action( 'ced_ebay_order_scheduler_job_' . $user_id ) ) {
				as_unschedule_all_actions( 'ced_ebay_order_scheduler_job_' . $user_id );
			}
			as_schedule_recurring_action( time(), 360, 'ced_ebay_order_scheduler_job_' . $user_id, $scheduler_args );
			update_option( 'ced_ebay_order_scheduler_job_' . $user_id, 'active' );
		}

		if ( ! empty( $sync_ended_listings_scheduler ) && 'on' == $sync_ended_listings_scheduler && function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_unschedule_all_actions' ) && function_exists( 'as_schedule_recurring_action' ) ) {
			if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_unschedule_all_actions' ) ) {
				if ( as_has_scheduled_action( null, null, 'ced_ebay_sync_ended_listings_group_' . $user_id . '>' . $site_id ) ) {
					as_unschedule_all_actions( null, null, 'ced_ebay_sync_ended_listings_group_' . $user_id . '>' . $site_id );
				}
			}
			as_schedule_recurring_action( time(), 360, 'ced_ebay_sync_ended_listings_scheduler_job_' . $user_id, array( 'data' => $scheduler_args ), 'ced_ebay_sync_ended_listings_group_' . $user_id . '>' . $site_id );
			update_option( 'ced_ebay_sync_ended_listings_scheduler_job_' . $user_id, 'active' );
		}

		if ( ! empty( $existing_product_sync ) && 'on' == $existing_product_sync ) {
			if ( wp_next_scheduled( 'ced_ebay_existing_products_sync_job_' . $user_id, $scheduler_args ) ) {
				wp_clear_scheduled_hook( 'ced_ebay_existing_products_sync_job_' . $user_id, $scheduler_args );
			}
			wp_schedule_event( time(), 'ced_ebay_6min', 'ced_ebay_existing_products_sync_job_' . $user_id, $scheduler_args );
			update_option( 'ced_ebay_existing_products_sync_job_' . $user_id, $user_id );
		}
		if ( ! empty( $import_products_schedule ) && 'on' == $import_products_schedule ) {
			wp_schedule_event( time(), 'ced_ebay_6min', 'ced_ebay_import_products_job_' . $user_id, $scheduler_args );
			update_option( 'ced_ebay_import_products_job_' . $user_id, $user_id );
			delete_option( 'ced_ebay_clear_import_process' );
		}
		$admin_success_notice = '<div class="notice notice-success"> <p>Your configuration has been saved! </p></div>';
		print_r( $admin_success_notice );
	} elseif ( isset( $_POST['reset_global_settings'] ) ) {
		delete_option( 'ced_ebay_global_settings' );
		$admin_success_notice = '<div class="notice notice-success">
	  <p>Your configuration has been Reset!</p></div>';
		print_r( $admin_success_notice );
	}
}
$renderDataOnGlobalSettings = get_option( 'ced_ebay_global_settings', false );
$global_options             = ! empty( get_option( 'ced_ebay_global_options' ) ) ? get_option( 'ced_ebay_global_options', array() ) : array();
$advanced_settings_field    = array(
	'ced_ebay_inventory_schedule_info'       => array(
		'title'       => 'Sync Stock Levels from Woo to eBay',
		'div_name'    => 'ced_ebay_global_settings[ced_ebay_inventory_schedule_info]',
		'value'       => ! empty( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_inventory_schedule_info'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_inventory_schedule_info'] : '',
		'description' => 'Sync your WooCommerce product stock with your eBay listings. If you have variations on eBay, make sure that they have SKUs and the same SKUs are present in WooCommerce for the stock sync to work.',
	),
	'ced_ebay_existing_products_sync'        => array(
		'title'       => 'Link Existing eBay Products (Using same SKUs)',
		'div_name'    => 'ced_ebay_global_settings[ced_ebay_existing_products_sync]',
		'value'       => ! empty( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_existing_products_sync'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_existing_products_sync'] : '',
		'description' => 'Link your WooCommerce products with eBay listings using same SKUs. No data is overwritten on either WooCommerce or eBay. This process is required for stock sync to work.',
	),
	'ced_ebay_import_product_scheduler_info' => array(
		'title'       => 'Import eBay Products to WooCommerce',
		'div_name'    => 'ced_ebay_global_settings[ced_ebay_import_product_scheduler_info]',
		'value'       => ! empty( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_import_product_scheduler_info'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_import_product_scheduler_info'] : '',
		'description' => 'Automatically import your eBay listings and create them as WooCommerce products.',
	),
	'ced_ebay_sync_ended_listings_info'      => array(
		'title'       => 'Sync Manually Ended eBay Listings',
		'div_name'    => 'ced_ebay_global_settings[ced_ebay_sync_ended_listings_info]',
		'value'       => ! empty( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_sync_ended_listings_info'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_sync_ended_listings_info'] : '',
		'description' => 'Remove eBAy listings in WooCommerce which have been removed from eBay seller hub.',
	),
	'ced_ebay_order_schedule_info'           => array(
		'title'       => 'Sync eBay Orders',
		'div_name'    => 'ced_ebay_global_settings[ced_ebay_order_schedule_info]',
		'value'       => ! empty( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_order_schedule_info'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_order_schedule_info'] : '',
		'description' => 'Sync eBay orders in WooCommerce and create them as native WooCommerce orders. The synced eBay orders are easily distinguishable in WooCommerce Orders section.',
	),
	'ced_ebay_auto_upload'                   => array(
		'title'       => 'Automatically List Woo Products on eBay',
		'div_name'    => 'ced_ebay_global_settings[ced_ebay_auto_upload]',
		'value'       => ! empty( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_auto_upload'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_auto_upload'] : '',
		'description' => 'Automatically list your WooCommerce products on eBay. Make sure that you have created templates before turning this ON.',
	),
);
if ( ! isset( $global_options[ $user_id ][ $site_id ] ) ) {
	$global_options[ $user_id ][ $site_id ] = array(
		'Brand'                 => array(
			'meta_key'     => '',
			'custom_value' => '',
			'description'  => 'asdsaddsadsads',
		),
		'MPN'                   => array(
			'meta_key'     => '',
			'custom_value' => '',
			'description'  => 'asdsaddsadsads',
		),
		'Maximum Dispatch Time' => array(
			'meta_key'    => '',
			'description' => 'asdsaddsadsads',
			'options'     => array(
				''   => 'Select',
				'0'  => 'Same Business Day',
				'1'  => '1 Day',
				'2'  => '2 Days',
				'3'  => '3 Days',
				'4'  => '4 Days',
				'5'  => '5 Days',
				'10' => '10 Days',
				'15' => '15 Days',
				'20' => '20 Days',
				'30' => '30 Days',
			),
		),
		'Listing Duration'      => array(
			'meta_key'    => '',
			'description' => 'asdsaddsadsads',
			'options'     => array(
				''         => 'Select',
				'Days_1'   => 'Days_1',
				'Days_10'  => 'Days_10',
				'Days_120' => 'Days_120',
				'Days_14'  => 'Days_14',
				'Days_21'  => 'Days_21',
				'Days_3'   => 'Days_3',
				'Days_30'  => 'Days_30',
				'Days_5'   => 'Days_5',
				'Days_60'  => 'Days_60',
				'Days_7'   => 'Days_7',
				'Days_90'  => 'Days_90',
				'GTC'      => 'Good Till Cancelled',
			),
		),
	);

	update_option( 'ced_ebay_global_options', $global_options );
} else {
	$global_options = get_option( 'ced_ebay_global_options', true );
	if ( isset( $global_options[ $user_id ][ $site_id ] ) ) {
		$tempGlobalOptions = array();
		$tempGlobalOptions = $global_options;
		$tempGlobalOptions[ $user_id ][ $site_id ]['Maximum Dispatch Time']['options'] = array(
			''   => 'Select',
			'0'  => 'Same Business Day',
			'1'  => '1 Day',
			'2'  => '2 Days',
			'3'  => '3 Days',
			'4'  => '4 Days',
			'5'  => '5 Days',
			'10' => '10 Days',
			'15' => '15 Days',
			'20' => '20 Days',
			'30' => '30 Days',
		);
		$tempGlobalOptions[ $user_id ][ $site_id ]['Listing Duration']['options']      = array(
			''         => 'Select',
			'Days_1'   => 'Days_1',
			'Days_10'  => 'Days_10',
			'Days_120' => 'Days_120',
			'Days_14'  => 'Days_14',
			'Days_21'  => 'Days_21',
			'Days_3'   => 'Days_3',
			'Days_30'  => 'Days_30',
			'Days_5'   => 'Days_5',
			'Days_60'  => 'Days_60',
			'Days_7'   => 'Days_7',
			'Days_90'  => 'Days_90',
			'GTC'      => 'Good Till Cancelled',
		);

		$global_options = $tempGlobalOptions;
	}
}
if ( class_exists( 'WC_Webhook' ) ) {
	$get_webhook_id = ! empty( get_option( 'ced_ebay_prduct_update_webhook_id_' . $user_id, true ) ) ? get_option( 'ced_ebay_prduct_update_webhook_id_' . $user_id, true ) : false;
	if ( $get_webhook_id ) {
		$webhook        = new WC_Webhook( $get_webhook_id );
		$webhook_status = $webhook->get_status();
		if ( 'disabled' == $webhook_status || 'paused' == $webhook_status ) {
			if ( ! empty( $renderDataOnGlobalSettings[ $site_id ][ $user_id ] ) && is_array( $renderDataOnGlobalSettings[ $site_id ][ $user_id ] ) ) {
				$global_settings      = array();
				$temp_global_settings = array();
				foreach ( $renderDataOnGlobalSettings as $key => $global_setting ) {
					if ( $user_id == $key ) {
						$temp_global_settings                                  = $global_setting;
						$temp_global_settings['ced_ebay_instant_stock_update'] = 'off';
						$global_settings[ $user_id ]                           = $temp_global_settings;
						break;
					}
					$global_settings[ $key ] = $global_setting;
				}
				update_option( 'ced_ebay_global_settings', $global_settings );

			}
		}
		if ( 'active' == $webhook_status ) {
			if ( ! empty( $renderDataOnGlobalSettings[ $user_id ][ $site_id ] ) && is_array( $renderDataOnGlobalSettings[ $user_id ][ $site_id ] ) ) {
				$global_settings      = array();
				$temp_global_settings = array();
				foreach ( $renderDataOnGlobalSettings as $key => $global_setting ) {
					if ( $user_id == $key ) {
						$temp_global_settings                                  = $global_setting;
						$temp_global_settings['ced_ebay_instant_stock_update'] = 'on';
						$global_settings[ $user_id ]                           = $temp_global_settings;
						break;
					}
					$global_settings[ $key ] = $global_setting;
				}
				update_option( 'ced_ebay_global_settings', $global_settings );

			}
		}
	}
}
?>
<form action="" method="post">

	<div
		class="components-card is-size-medium woocommerce-table pinterest-for-woocommerce-landing-page__faq-section css-1xs3c37-CardUI e1q7k77g0">
		<div class="components-panel ced_amazon_settings_new">
			<div class="wc-progress-form-content woocommerce-importer ced-padding">

				<!-- Listings Configuration -->
				<div class="ced-faq-wrapper">
					<input class="ced-faq-trigger" id="ced-faq-wrapper-one" type="checkbox" checked=""><label
						class="ced-faq-title" for="ced-faq-wrapper-one">General Settings</label>
					<div class="ced-faq-content-wrap">
						<div class="ced-faq-content-holder">
							<div class="ced-form-accordian-wrap">
								<div class="wc-progress-form-content woocommerce-importer">
									<header>
										<h3>Listings Configuration</h3>

										<p>Increase or decrease the Price of eBay Listings, Adjust Stock Levels, Sync
											Price from WooCommerce and import
											eBay Categories.</p>
										<table class="form-table">
											<tbody>
												<tr>
													<?php
													$listing_stock = isset( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_listing_stock'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_listing_stock'] : '';
													$stock_type    = isset( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_product_stock_type'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_product_stock_type'] : '';
													?>

													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															Stock Levels
															<?php print_r( wc_help_tip( 'Stock level, also called inventory level, indicates the quantity of a particular product or product that you own on any platform', 'ebay-integration-for-woocommerce' ) ); ?>
														</label>
													</th>
													<td class="forminp forminp-select">

														<select
															name="ced_ebay_global_settings[ced_ebay_product_stock_type]"
															data-fieldId="ced_ebay_product_stock_type">
															<option value="">
																<?php esc_attr_e( 'Select', 'ebay-integration-for-woocommerce' ); ?>
															</option>
															<option <?php echo ( 'MaxStock' == $stock_type ) ? 'selected' : ''; ?> value="MaxStock"><?php esc_attr_e( 'Maximum Quantity', 'ebay-integration-for-woocommerce' ); ?>
															</option>
														</select>

													</td>
													<td class="forminp forminp-select">

														<input type="number"
															value="<?php echo esc_attr( $listing_stock ); ?>"
															id="ced_ebay_listing_stock"
															name="ced_ebay_global_settings[ced_ebay_listing_stock]">

													</td>
												</tr>
												<tr>
													<?php
													$markup_type  = isset( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_product_markup_type'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_product_markup_type'] : '';
													$markup_price = isset( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_product_markup'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_product_markup'] : '';
													?>

													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
														Price Markup 
															<?php print_r( wc_help_tip( 'Markup is the amount you include in prices to earn profit while selling on eBay. You are able to increase or decrease the markup either by a fixed amount or by percentage.', 'ebay-integration-for-woocommerce' ) ); ?>
														</label>
													</th>
													<td class="forminp forminp-select">
														<select
															name="ced_ebay_global_settings[ced_ebay_product_markup_type]"
															data-fieldId="ced_ebay_product_markup">
															<option value="">
																<?php esc_attr_e( 'Select', 'ebay-integration-for-woocommerce' ); ?>
															</option>
															<option <?php echo ( 'Fixed_Increased' == $markup_type ) ? 'selected' : ''; ?> value="Fixed_Increased"><?php esc_attr_e( 'Fixed Increment', 'ebay-integration-for-woocommerce' ); ?></option>
															<option <?php echo ( 'Fixed_Decreased' == $markup_type ) ? 'selected' : ''; ?> value="Fixed_Decreased"><?php esc_attr_e( 'Fixed Decrement', 'ebay-integration-for-woocommerce' ); ?></option>
															<option <?php echo ( 'Percentage_Increased' == $markup_type ) ? 'selected' : ''; ?> value="Percentage_Increased"><?php esc_attr_e( 'Percentage Increment', 'ebay-integration-for-woocommerce' ); ?></option>
															<option <?php echo ( 'Percentage_Decreased' == $markup_type ) ? 'selected' : ''; ?> value="Percentage_Decreased"><?php esc_attr_e( 'Percentage Decrement', 'ebay-integration-for-woocommerce' ); ?></option>
														</select>

													</td>

													<td class="forminp forminp-select">
														<input type="text"
															value="<?php echo esc_attr( $markup_price ); ?>"
															id="ced_ebay_product_markup"
															name="ced_ebay_global_settings[ced_ebay_product_markup]">

													</td>
												</tr>
												<tr>
													<?php
													$upload_dir    = wp_upload_dir();
													$templates_dir = $upload_dir['basedir'] . '/ced-ebay/templates/';
													$templates     = array();
													$files         = glob( $upload_dir['basedir'] . '/ced-ebay/templates/*/template.html' );
													if ( is_array( $files ) ) {
														foreach ( $files as $file ) {
															$file     = basename( dirname( $file ) );
															$fullpath = $templates_dir . $file;

															if ( file_exists( $fullpath . '/info.txt' ) ) {
																$template_header       = array(
																	'Template' => 'Template',
																);
																$template_data         = get_file_data( $fullpath . '/info.txt', $template_header, 'theme' );
																$item['template_name'] = $template_data['Template'];
															}
															$template_id                                = basename( $fullpath );
															$templates[ $template_id ]['template_name'] = $item['template_name'];
														}
													}
													$listing_description_template = isset( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_listing_description_template'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_listing_description_template'] : '';
													?>

													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															Description Template
															<?php print_r( wc_help_tip( 'Create and select a listing description template to make your eBay listing description stand out to buyers', 'ebay-integration-for-woocommerce' ) ); ?>
														</label>
													</th>
													<td class="forminp forminp-select">
														<?php
														if ( ! empty( $templates ) ) {
															?>
															<select
																name="ced_ebay_global_settings[ced_ebay_listing_description_template]"
																data-fieldId="ced_ebay_listing_description_template">
																<option value="">
																	<?php esc_attr_e( 'Select', 'ebay-integration-for-woocommerce' ); ?>
																</option>
																<?php
																foreach ( $templates as $key => $value ) {
																	?>
																	<option <?php echo ( $key == $listing_description_template ) ? 'selected' : ''; ?>
																		value="<?php echo esc_attr( $key ); ?>"><?php esc_attr_e( $value['template_name'], 'ebay-integration-for-woocommerce' ); ?></option>
																	<?php
																}
																?>
															</select>
															<?php
														} else {
															?>
															<p>No description templates were found. You can create
																description template here.</p>

															<?php
														}
														?>

													</td>
													<td class="forminp forminp-select">
														<a
															href="<?php echo esc_attr( wp_nonce_url( admin_url( 'admin.php?page=sales_channel&channel=ebay&section=description-template&user_id=' . $user_id . '&site_id=' . $site_id . '&action=ced_ebay_add_new_template' ), 'ced_ebay_add_new_template_action', 'ced_ebay_add_new_template_nonce' ) ); ?>">
															<?php esc_attr_e( 'Create Template', 'ebay-integration-for-woocommerce' ); ?>
														</a>
														<span style="margin: 0px 3px;">|</span>
														<a
															href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=ebay&section=view-description-templates&user_id=' . $user_id . '&site_id=' . $site_id ) ); ?>">View
															Templates</a>
													</td>
												</tr>
												<tr>
													<?php
													// an array of all the supported eBay sites
													$ebay_sites          = array(
														'US' => 'United States',
														'UK' => 'United Kingdom',
														'Australia' => 'Australia',
														'Austria' => 'Austria',
														'Belgium_French' => 'Belgium (French)',
														'Belgium_Dutch' => 'Belgium (Dutch)',
														'Canada' => 'Canada',
														'CanadaFrench' => 'Canada French',
														'France' => 'France',
														'Germany' => 'Germany',
														'Italy' => 'Italy',
														'Netherlands' => 'Netherlands',
														'Spain' => 'Spain',
														'Switzerland' => 'Switzerland',
														'HongKong' => 'Hong Kong',
														'India' => 'India',
														'Ireland' => 'Ireland',
														'Malaysia' => 'Malaysia',
														'Philippines' => 'Philippines',
														'Poland' => 'Poland',
														'Singapore' => 'Singapore',
														'Russia' => 'Russia',
														'eBayMotors' => 'eBay Motors',
													);
													$item_import_country = isset( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_item_import_country'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_item_import_country'] : '';
													?>

													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															Import Products from eBay Site 
															<?php print_r( wc_help_tip( 'Choose which eBay site you would like to import products from if you have products listed across multiple eBay regions.', 'ebay-integration-for-woocommerce' ) ); ?>	
														</label>
													</th>
													<td class="forminp forminp-select">

														<select
															name="ced_ebay_global_settings[ced_ebay_item_import_country]"
															data-fieldId="ced_ebay_import_product_location">
															<option value="">
																<?php esc_attr_e( 'Select', 'ebay-integration-for-woocommerce' ); ?>
															</option>
															<?php
															foreach ( $ebay_sites as $key => $import_country ) {
																?>
																<option <?php echo ( $key == $item_import_country ) ? 'selected' : ''; ?>
																	value="<?php echo esc_attr( $key ); ?>"><?php esc_attr_e( $import_country, 'ebay-integration-for-woocommerce' ); ?></option>
																<?php
															}
															?>
														</select>

													</td>
												</tr>
												<tr>
													<?php
													$postal_code = isset( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_postal_code'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_postal_code'] : '';
													?>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															Postal Code
															<?php print_r( wc_help_tip( 'Enter the postal code where your products are located', 'ebay-integration-for-woocommerce' ) ); ?>	
														</label>
													</th>

													<td class="forminp forminp-select">
														<input type="text"
															value="<?php echo esc_attr( $postal_code ); ?>"
															id="ced_ebay_postal_code"
															name="ced_ebay_global_settings[ced_ebay_postal_code]">

													</td>
												</tr>
												<tr>
													<?php
													$wc_countries          = new WC_Countries();
													$countries             = $wc_countries->get_countries();
													$item_location_country = isset( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_item_location_country'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_item_location_country'] : '';
													$item_location_state   = isset( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_item_location_state'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_item_location_state'] : '';
													?>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															Item Location
															<?php print_r( wc_help_tip( 'You can ignore this field if the Postal Code is set above else choose the item location by choosing country and entering state/city', 'ebay-integration-for-woocommerce' ) ); ?>	
														</label>
													</th>

													<td class="forminp forminp-select">
														<select
															name="ced_ebay_global_settings[ced_ebay_item_location_country]"
															data-fieldId="ced_ebay_product_location">
															<option value="">
																<?php esc_attr_e( 'Select', 'ebay-integration-for-woocommerce' ); ?>
															</option>
															<?php
															foreach ( $countries as $key => $country ) {
																?>
																<option <?php echo ( $key == $item_location_country ) ? 'selected' : ''; ?>
																	value="<?php echo esc_attr( $key ); ?>"><?php esc_attr_e( $country, 'ebay-integration-for-woocommerce' ); ?></option>
																<?php
															}
															?>
														</select>
													</td>
													<td class="forminp forminp-select">
														<input type="text" placeholder="Enter City Name"
															value="<?php echo esc_attr( $item_location_state ); ?>"
															id="ced_ebay_product_markup"
															name="ced_ebay_global_settings[ced_ebay_item_location_state]">

													</td>
												</tr>
												<tr>
													<?php
													$shop_data = ced_ebay_get_shop_data( $user_id, $site_id );
													if ( ! empty( $shop_data ) && $shop_data['is_site_valid'] ) {
														if ( '3' == $site_id || '101' == $site_id || '77' == $site_id ) {
															$exclude_product_vat = isset( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_exclude_product_vat'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_exclude_product_vat'] : '';
															?>


															<th scope="row" class="titledesc">
																<label for="woocommerce_currency">
																	Exclude Order Product VAT
																	<?php print_r( wc_help_tip( 'Exclude the VAT amount from the eBay orders imported in WooCommerce', 'ebay-integration-for-woocommerce' ) ); ?>	
																</label>
															</th>
															<td class="forminp forminp-select">


																<div class="woocommerce-list__item-after">
																	<label class="components-form-toggle 
															<?php
															if ( 'on' == $exclude_product_vat ) {
																echo esc_attr( 'is-checked' );
															}
															?>
											">
																		<input
																			name="ced_ebay_global_settings[ced_ebay_exclude_product_vat]"
																			class="components-form-toggle__input ced-settings-checkbox-ebay"
																			id="inspector-toggle-control-0" type="checkbox"
																			<?php
																			if ( 'on' == $exclude_product_vat ) {
																				echo 'checked';
																			}
																			?>
																			>
																		<span class="components-form-toggle__track"></span>
																		<span class="components-form-toggle__thumb"></span>
																	</label>
																</div>

															</td>
															<?php
														}
													}
													?>
												</tr>
												<tr>
													<?php
													$import_categories = isset( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_import_ebay_categories'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_import_ebay_categories'] : '';
													$shop_data         = ced_ebay_get_shop_data( $user_id, $site_id );
													if ( ! empty( $shop_data ) && $shop_data['is_site_valid'] ) {

														$is_store_category_present = false;
														require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayAuthorization.php';
														$cedAuthorization        = new Ced_Ebay_WooCommerce_Core\Ebayauthorization();
														$cedAuhorizationInstance = $cedAuthorization->get_instance();
														$storeDetails            = $cedAuhorizationInstance->getStoreData( $site_id, $user_id );
														if ( ! empty( $storeDetails ) && 'Success' == $storeDetails['Ack'] ) {
															$store_categories = $storeDetails['Store']['CustomCategories']['CustomCategory'];
															if ( ! empty( $store_categories ) ) {
																$is_store_category_present = true;
															}
														}
													}
													?>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Import and Assign eBay Categories', 'ebay-integration-for-woocommerce' ); ?>
															<?php print_r( wc_help_tip( 'Choose to automatically create and assing eBay site or store cateogry when importing eBay listings in WooCommerce', 'ebay-integration-for-woocommerce' ) ); ?>	
														</label>
													</th>
													
													
														<td colspan="4"
															class="ced_ebay_cat_import_row forminp forminp-select">
															<?php
															$import_categories_type = isset( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_import_categories_type'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_import_categories_type'] : '';
															?>

															<select
																name="ced_ebay_global_settings[ced_ebay_import_categories_type]"
																data-fieldId="ced_ebay_import_categories_type">
																<option value="">
																	<?php esc_attr_e( 'None', 'ebay-integration-for-woocommerce' ); ?>
																</option>
																<option <?php echo ( 'ebay_site' == $import_categories_type ) ? 'selected' : ''; ?> value="ebay_site"><?php esc_attr_e( 'eBay Site Categories', 'ebay-integration-for-woocommerce' ); ?></option>
																<?php
																if ( $is_store_category_present ) {
																	?>
																<option <?php echo ( 'ebay_store' == $import_categories_type ) ? 'selected' : ''; ?> value="ebay_store"><?php esc_attr_e( 'eBay Store Categories', 'ebay-integration-for-woocommerce' ); ?></option>
																	<?php
																}

																?>
															</select>
													</td>
												</tr>
												<tr>
													<?php
													$skip_sku_sending = isset( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_sending_sku'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_sending_sku'] : '';
													?>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															Skip Sending SKU for Simple Products 
															<?php print_r( wc_help_tip( 'If your simple products on eBay, without variations, don\'t have SKUs as compared to WooCommerce products then turn this ON for inventory sync to run successfully', 'ebay-integration-for-woocommerce' ) ); ?>	
														</label>
													</th>
													<td class="forminp forminp-select">


														<div class="woocommerce-list__item-after">
															<label class="components-form-toggle 
											<?php
											if ( 'on' == $skip_sku_sending ) {
												echo esc_attr( 'is-checked' );
											}
											?>
											">
																<input
																	name="ced_ebay_global_settings[ced_ebay_sending_sku]"
																	class="components-form-toggle__input ced-settings-checkbox-ebay"
																	id="inspector-toggle-control-0" type="checkbox"
																	<?php
																	if ( 'on' == $skip_sku_sending ) {
																		echo 'checked';
																	}
																	?>
																	>
																<span class="components-form-toggle__track"></span>
																<span class="components-form-toggle__thumb"></span>
															</label>
														</div>

													</td>
												</tr>
												<tr>
													<?php
													$sync_price = isset( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_sync_price'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_sync_price'] : '';
													?>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															Sync price to eBay
															<?php print_r( wc_help_tip( 'Turn this toggle ON to sync WooCommerce prices with your eBay listings. Make sure that the stock sync is running before turning this ON.', 'ebay-integration-for-woocommerce' ) ); ?>	
														</label>
													</th>
													<td class="forminp forminp-select">


														<div class="woocommerce-list__item-after">
															<label class="components-form-toggle 
											<?php
											if ( 'on' == $sync_price ) {
												echo esc_attr( 'is-checked' );
											}
											?>
											">
																<input
																	name="ced_ebay_global_settings[ced_ebay_sync_price]"
																	class="components-form-toggle__input ced-settings-checkbox-ebay"
																	id="inspector-toggle-control-0" type="checkbox"
																	<?php
																	if ( 'on' == $sync_price ) {
																		echo 'checked';
																	}
																	?>
																	>
																<span class="components-form-toggle__track"></span>
																<span class="components-form-toggle__thumb"></span>
															</label>
														</div>

													</td>
												</tr>
											</tbody>
										</table>

									</header>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!--- Shipping, Payment, and Returns -->
				<div class="ced-faq-wrapper">
					<input class="ced-faq-trigger" id="ced-faq-wrapper-four" type="checkbox">
					<label class="ced-faq-title" for="ced-faq-wrapper-four"><?php echo esc_html__( 'Shipping, Payment, and Returns Policy', 'ebay-integration-for-woocommerce' ); ?>
						<a href="#" class="ced_ebay_update_business_policies">Update Business Policies</a>
					</label>
					<div class="ced-faq-content-wrap ced-ebay-business-policy-content">
						<div class="ced-faq-content-holder">
							<div class="ced-form-accordian-wrap">
								<div class="wc-progress-form-content woocommerce-importer">
									<header>
										<table class="form-table">
											<tbody>



												<?php
												$business_policies = ced_ebay_get_business_policies( $user_id, $site_id );
												if ( ! empty( $business_policies ) && is_array( $business_policies ) && isset( $business_policies['paymentPolicies'] ) && isset( $business_policies['fulfillmentPolicies'] ) && isset( $business_policies['returnPolicies'] ) ) {
													$paymentPolicyId     = isset( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_payment_policy'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_payment_policy'] : '';
													$returnPolicyId      = isset( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_return_policy'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_return_policy'] : '';
													$fulfillmentPolicyId = isset( $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_shipping_policy'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $site_id ]['ced_ebay_shipping_policy'] : '';

													foreach ( $business_policies as $gKey => $policies ) {
														$nameForPolicyIdKey  = str_replace( 'Policies', '', $gKey );
														$nameForPolicyIdKey .= 'PolicyId';
														$suffix              = '';
														if ( 'paymentPolicies' == $gKey ) {
															$suffix = 'payment_policy';
														} elseif ( 'returnPolicies' == $gKey ) {
															$suffix = 'return_policy';
														} elseif ( 'fulfillmentPolicies' == $gKey ) {
															$suffix = 'shipping_policy';
														}
														?>
														<tr>
															<th scope="row" class="titledesc">
																<label for="woocommerce_currency">
																	<?php echo esc_html( $gKey ); ?>
																</label>
															</th>
															<td class="forminp forminp-select">
															<select class="ced_ebay_map_to_fields" name="ced_ebay_global_settings[ced_ebay_<?php echo esc_attr( $suffix ); ?>]">
																	<option value="">Select</option>
																	<?php
																	if ( isset( $policies[ $gKey ] ) && ! empty( $policies[ $gKey ] ) ) {
																		foreach ( $policies[ $gKey ] as $xKey => $individual_policy ) {
																			if ( ! empty( $individual_policy['name'] && ! empty( $individual_policy[ $nameForPolicyIdKey ] ) ) ) {
																				?>
																				<option <?php echo ( $$nameForPolicyIdKey == $individual_policy[ $nameForPolicyIdKey ] . '|' . $individual_policy['name'] ) ? 'selected' : ''; ?>
																					value="<?php echo esc_attr( $individual_policy[ $nameForPolicyIdKey ] . '|' . $individual_policy['name'] ); ?>">
																					<?php echo esc_attr( $individual_policy['name'] ); ?>
																				</option>
																				<?php
																			}
																		}
																	}
																	?>
																</select>
															</td>
															<td></td>
														</tr>

														<?php
													}
												} else {
													?>
													<p>You haven't setup Business Policies for your eBay account.</p>

													<?php
												}
												?>


											</tbody>
										</table>

									</header>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Global Options -->

				<div class="ced-faq-wrapper">
					<input class="ced-faq-trigger" id="ced-faq-wrapper-three" type="checkbox"><label
						class="ced-faq-title" for="ced-faq-wrapper-three"><?php echo esc_html__( 'Global Options', 'amazon-for-woocommerce' ); ?></label>
					<div class="ced-faq-content-wrap">
						<div class="ced-faq-content-holder">
							<div class="ced-form-accordian-wrap">
								<div class="wc-progress-form-content woocommerce-importer">
									<header>
										<table class="form-table">
											<tbody>

												<tr valign="top">
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Attributes', 'amazon-for-woocommerce' ); ?>
														</label>
													</th>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Map to Fields', 'amazon-for-woocommerce' ); ?>
														</label>
													</th>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Custom Value', 'amazon-for-woocommerce' ); ?>
														</label>
													</th>
												</tr>

												<?php
												$global_options = isset( $global_options[ $user_id ][ $site_id ] ) ? $global_options[ $user_id ][ $site_id ] : array();
												foreach ( $global_options as $gKey => $gOption ) {
													$selectDropdownHTML = ced_ebay_get_options_for_dropdown();
													?>
													<tr>
														<th scope="row" class="titledesc">
															<label for="woocommerce_currency">
																<?php echo esc_html( $gKey ); ?>
															</label>
														</th>
														<td class="forminp forminp-select">
															<select class="ced_ebay_map_to_fields"
																name="ced_ebay_global_options[<?php echo esc_html( $user_id ); ?>][<?php echo esc_html( $site_id ); ?>][<?php echo esc_html( $gKey ); ?>|meta_key]">
																<?php
																if ( isset( $gOption['options'] ) && ! empty( $gOption['options'] ) ) {
																	foreach ( $gOption['options'] as $optValue => $optName ) {
																		?>
																		<option <?php echo ( $optValue == $gOption['meta_key'] ) ? 'selected' : ''; ?>
																			value="<?php echo esc_attr( $optValue ); ?>"><?php echo esc_attr( $optName ); ?></option>
																		<?php
																	}
																} else {
																	if ( ! empty( $gOption['meta_key'] ) ) {
																		$selectDropdownHTML = str_replace(
																			'<option value="' . esc_attr( $gOption['meta_key'] ) . '"',
																			'<option value="' . esc_attr( $gOption['meta_key'] ) . '" selected',
																			$selectDropdownHTML
																		);
																	}
																	print_r( $selectDropdownHTML );

																}

																?>
															</select>
														</td>
														<?php if ( ! isset( $gOption['options'] ) || empty( $gOption['options'] ) ) { ?>
															<td class="forminp forminp-select">
																<input type="text"
																	name="ced_ebay_global_options[<?php echo esc_html( $user_id ); ?>][<?php echo esc_html( $site_id ); ?>][<?php echo esc_html( $gKey ); ?>|custom_value]"
																	style="width:100%" ;
																	value="<?php echo esc_attr( ! empty( $gOption['custom_value'] ) ? $gOption['custom_value'] : '' ); ?>">
															</td>
															<td></td>
														</tr>

															<?php
														}
												}
												?>


											</tbody>
										</table>

									</header>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Business Policies -->
				<!-- Custom Item Specific -->

				<!-- <div class="ced-faq-wrapper">
					<input class="ced-faq-trigger" id="ced-faq-wrapper-five" type="checkbox"><label
						class="ced-faq-title" for="ced-faq-wrapper-five"><?php echo esc_html__( 'Custom Item Specific', 'amazon-for-woocommerce' ); ?></label>
					<div class="ced-faq-content-wrap">
						<div class="ced-faq-content-holder">
							<div class="ced-form-accordian-wrap">
								<div class="wc-progress-form-content woocommerce-importer">
									<header>
										<table class="form-table">
											<tbody>
												<tr valign="top">
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Attribute Name', 'amazon-for-woocommerce' ); ?>
														</label>
													</th>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Map to Fields', 'amazon-for-woocommerce' ); ?>
														</label>
													</th>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Custom Value', 'amazon-for-woocommerce' ); ?>
														</label>
													</th>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Action', 'amazon-for-woocommerce' ); ?>
														</label>
													</th>
												</tr>
												<tr valign="top">
													<th scope="row" class="titledesc">
														<input type="text" style="width:100%" ; value="" name="ced_ebay_custom_item_specific[attribute]">
													</th>
													<th scope="row" class="titledesc">
															<select class="ced_ebay_map_to_fields"
																name="ced_ebay_custom_item_specific[meta_key]">
																<?php
																print_r( $selectDropdownHTML );
																?>
															</select>
													</th>
													<th scope="row" class="titledesc">
														<input type="text" style="width:100%" ; value="" name="ced_ebay_custom_item_specific[custom_value]">
													</th>
													<th scope="row" class="titledesc">
														<button type="submit" id="global_settings" class="config_button components-button is-primary" name="global_settings">Save</button>
													</th>
												</tr>										
											</tbody>
										</table>
										<table class="form-table">
												<tr valign="top">
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Attribute Name', 'amazon-for-woocommerce' ); ?>
														</label>
													</th>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Map to Fields', 'amazon-for-woocommerce' ); ?>
														</label>
													</th>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Custom Value', 'amazon-for-woocommerce' ); ?>
														</label>
													</th>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Action', 'amazon-for-woocommerce' ); ?>
														</label>
													</th>													
												</tr>
											<tbody>
											<?php
											$ced_ebay_custom_item_specific = get_option( 'ced_ebay_custom_item_specific', true );
											$ced_ebay_custom_item_specific = isset( $ced_ebay_custom_item_specific[ $user_id ][ $site_id ] ) ? $ced_ebay_custom_item_specific[ $user_id ][ $site_id ] : array();
											if ( ! empty( $ced_ebay_custom_item_specific ) ) {
												foreach ( $ced_ebay_custom_item_specific as $key => $custom_item_specific ) {
													echo '<tr>';
													echo '<td>' . esc_attr( $custom_item_specific['attribute'] ) . '</td>';
													echo '<td>' . esc_attr( $custom_item_specific['meta_key'] ) . '</td>';
													echo '<td>' . esc_attr( $custom_item_specific['custom_value'] ) . '</td>';
													echo '<td><button type="button" data-id = "' . esc_attr( $key ) . '" data-user_id = "' . esc_attr( $user_id ) . '" data-site_id = "' . esc_attr( $site_id ) . '" class="button ced_ebay_delete_custom_item_specific">Delete</button></td>';
													echo '</tr>';
												}
											} else {
												echo '<p>There is no Item Specific</p>';
											}
											?>
											</tbody>
										</table>		
									</header>
								</div>
							</div>
						</div>
					</div>
				</div>								 -->

				<!-- Advanced Settings -->
				<?php if ( ! empty( $advanced_settings_field ) && is_array( $advanced_settings_field ) ) { ?>
					<div class="ced-faq-wrapper">
						<input class="ced-faq-trigger" id="ced-faq-wrapper-two" type="checkbox"><label class="ced-faq-title"
							for="ced-faq-wrapper-two">Advanced Settings</label>
						<div class="ced-faq-content-wrap">
							<div class="ced-faq-content-holder ced-advance-table-wrap">
								<table class="form-table">
									<tbody>
										<?php
										foreach ( $advanced_settings_field as $advFieldKey => $advFeilds ) {
											?>
											<tr>
												<th scope="row" class="titledesc">
													<label for="woocommerce_currency">
														<?php echo esc_attr( $advFeilds['title'] ); ?>
														<?php echo wc_help_tip( $advFeilds['description'], 'ebay-integration-for-woocommerce' ); ?>
													</label>
												</th>
												<td class="forminp forminp-select">
												<?php
												if ( isset( $advFeilds['is_link'] ) && true === $advFeilds['is_link'] ) {
													echo '<a href="#" class="' . esc_attr( $advFeilds['div_class'] ) . '">asddasdas</a>';
													continue;
												}
												?>

													<div class="woocommerce-list__item-after">
														<label class="components-form-toggle 
											<?php
											if ( 'on' == $advFeilds['value'] ) {
												echo esc_attr( 'is-checked' );
											}
											?>
											">
															<input name="<?php echo esc_attr( $advFeilds['div_name'] ); ?>"
																class="components-form-toggle__input ced-settings-checkbox-ebay"
																id="inspector-toggle-control-0" type="checkbox" 
																<?php
																if ( 'on' == $advFeilds['value'] ) {
																	echo 'checked';
																}
																?>
																>
															<span class="components-form-toggle__track"></span>
															<span class="components-form-toggle__thumb"></span>
														</label>
													</div>

												</td>
												<td></td>
											</tr>
											<?php
										}
										?>
									</tbody>
								</table>

								</header>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>

		<div class="ced-margin-top">
			<?php wp_nonce_field( 'ced_ebay_setting_page_nonce', 'ced_ebay_setting_nonce' ); ?>
			<button id="save_global_settings" name="global_settings" style="float: right;"
				class="config_button components-button is-primary">
				<?php esc_attr_e( 'Save Configuration', 'ebay-integration-for-woocommerce' ); ?>
			</button>

		</div>


	</div>
	</div>
	</div>




</form>

<script type="text/javascript">
	jQuery(".ced_ebay_map_to_fields").selectWoo({
		dropdownPosition: 'below',
		dropdownAutoWidth : false,
	});
</script>
