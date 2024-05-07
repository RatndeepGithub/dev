<?php

namespace Ced\Ebay;

use Ced\Ebay\SCHEDULE_MANAGER as SCHEDULE_MANAGER;
use Ced\Ebay\SCHEDULED_ACTIONS_MANAGER as SCHEDULED_ACTIONS_MANAGER;

if (!defined('ABSPATH')) {
	exit;
}


class EVENTS_HANDLER
{
	private $loadInstance;
	public static function init()
	{
		self::add_ajax_events();
		self::add_scheduled_actions();
	}

	public static function add_scheduled_actions()
	{
		$scheduled_actions = [
			'ced_ebay_import_products_action',
			'ced_ebay_async_update_stock_action',
			'ced_ebay_async_bulk_upload_action',
			'ced_ebay_async_order_sync_action',
			'ced_ebay_order_schedule_manager',
			'ced_ebay_inventory_schedule_manager',
			'ced_ebay_existing_products_sync_manager',
			'ced_ebay_manually_ended_listings_manager'
		];

		$scheduled_actions = apply_filters('ced_ebay_scheduled_actions', $scheduled_actions);
		$scheduledActionsManagerObj = new SCHEDULED_ACTIONS_MANAGER();
		$scheduleManagerObj = new SCHEDULE_MANAGER();
		foreach ($scheduled_actions as $key => $action) {
			if (method_exists($scheduledActionsManagerObj, $action.'_manager')) {
				add_action($action, array($scheduledActionsManagerObj, $action . '_manager'));
			} else if(method_exists($scheduleManagerObj, $action)){
				add_action($action, array($scheduleManagerObj, $action));
			}
		}
	}

	public static function add_ajax_events()
	{
		$ajax_events = [
			'ced_ebay_order_schedule_manager' => true,
			'ced_ebay_inventory_schedule_manager' => true,
			'ced_ebay_existing_products_sync_manager' => true,
			'ced_ebay_sync_seller_event' => true,
			'ced_ebay_manually_ended_listings_manager' => true,
			'ced_ebay_update_stock_on_webhook' => true,
		];
		$ajax_events = apply_filters('ced_ebay_ajax_events', $ajax_events);
		$scheduleManagerObj = new SCHEDULE_MANAGER();
		foreach ($ajax_events as $ajax_event => $nopriv) {
			if (method_exists($scheduleManagerObj, $ajax_event)) {
				add_action('wp_ajax_' . $ajax_event, array($scheduleManagerObj, $ajax_event));
				if ($nopriv) {
					add_action('wp_ajax_nopriv_' . $ajax_event, array($scheduleManagerObj, $ajax_event));
				}
			} else {
				add_action('wp_ajax_' . $ajax_event, array(__CLASS__, $ajax_event));
				if ($nopriv) {
					add_action('wp_ajax_nopriv_' . $ajax_event, array(__CLASS__, $ajax_event));
				}
			}
		}
	}

	private function loadDependency($file, $className)
	{
		if (file_exists($file)) {
			require_once $file;
			$this->loadInstance = $className::get_instance();
		}
	}

	public function ced_ebay_update_stock_on_webhook()
	{

		$wp_folder     = wp_upload_dir();
		$wp_upload_dir = $wp_folder['basedir'];
		$logs_folder   = $wp_upload_dir . '/ced-ebay/logs/stock-update/webhook/';
		$current_date  = new DateTime();
		$current_date  = $current_date->format('ymd');
		$log_file      = $logs_folder . 'logs_' . $current_date . '.txt';
		if (!file_exists($log_file)) {
			file_put_contents($log_file, '');
		}
		ced_ebay_log_data('Commence Webhook based stock update', 'ced_ebay_async_update_stock_callback', $log_file);
		if (file_get_contents('php://input')) {
			$response_body    = json_decode(file_get_contents('php://input'), true);
			$product_id       = $response_body['id'];
			$access_token_arr = get_option('ced_ebay_user_access_token');
			if (!empty($access_token_arr) && !empty($product_id)) {
				foreach ($access_token_arr as $user_id => $value) {
					if (!empty(get_post_meta($product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID))) {
						ced_ebay_log_data('Webhook stock update | Product ID ' . $product_id, 'ced_ebay_async_update_stock_callback', $log_file);
						$stock_update_data               = array();
						$stock_update_data['product_id'] = $product_id;
						$stock_update_data['user_id']    = $user_id;
						$this->ced_ebay_async_update_stock_callback($stock_update_data, $log_file);
					} else {
						ced_ebay_log_data('Woo product #' . $product_id . ' is not an eBay listing!', 'ced_ebay_async_update_stock_callback', $log_file);
						ced_ebay_log_data('----------------', 'ced_ebay_async_update_stock_callback', $log_file);
					}
				}
			}
		}
	}

	public function ced_ebay_sync_seller_event()
	{
		$logger             = wc_get_logger();
		$context            = array('source' => 'ced_ebay_sync_seller_event');
		$fetchCurrentAction = current_action();
		if (strpos($fetchCurrentAction, 'wp_ajax_nopriv_') !== false) {
			$user_id = isset($_GET['user_id']) ? wc_clean($_GET['user_id']) : false;
		} else {
			$user_id = str_replace('ced_ebay_sync_seller_event_job_', '', $fetchCurrentAction);
		}
		$shop_data = ced_ebay_get_shop_data($user_id);

		$file             = CED_EBAY_DIRPATH . 'admin/ebay/class-ebay.php';
		$renderDependency = $this->renderDependency($file);
		if ($renderDependency) {
			$cedeBay         = new Class_Ced_EBay_Manager();
			$cedebayInstance = $cedeBay->get_instance();
			$page_number     = !empty(get_option('ced_ebay_seller_event_pagination_' . $user_id)) ? get_option('ced_ebay_seller_event_pagination_' . $user_id, true) : 1;
			$result          = $cedebayInstance->ced_ebay_get_seller_events($token, $siteID, $page_number);
			if ('api-error' != $result && 'request-file-not-found' != $result) {
				$logger->info(wc_print_r('Page Number ' . $page_number, true), $context);
				++$page_number;
				update_option('ced_ebay_seller_event_pagination_' . $user_id, $page_number);
				$logger->info('Getting Data from api', $context);

				if (!empty($result['ItemArray']['Item'])) {
					$logger->info('Getting Item array in Data', $context);
					if (!isset($result['ItemArray']['Item'][0])) {
						$temp_item_list = array();
						$temp_item_list = $result['ItemArray']['Item'];
						unset($result['ItemArray']['Item']);
						$result['ItemArray']['Item'][] = $temp_item_list;
					}

					foreach ($result['ItemArray']['Item'] as $key => $value) {
						$ID = false;
						if (isset($value['ItemID']) && !empty($value['ItemID'])) {
							$logger->info('Item ID - ' . wc_print_r($value['ItemID'], true), $context);
							$store_products = get_posts(
								array(
									'numberposts'  => -1,
									'post_type'    => 'product',
									'meta_key'     => '_ced_ebay_listing_id_' . $user_id . '>' . $siteID,
									'meta_value'   => $value['ItemID'],
									'meta_compare' => '=',
								)
							);
							$localItemID    = wp_list_pluck($store_products, 'ID');
							if (!empty($localItemID)) {
								$ID = $localItemID[0];
							}

							if (empty($ID)) {
								$logger->info('Product Not found on Woo', $context);
								continue;
							} else {
								$logger->info('Found woo product in woo - ' . wc_print_r($ID, true), $context);
								$product = wc_get_product($ID);
								update_post_meta($ID, 'ced_ebay_stock_ebay_to_woo_running', 'Running');

								if ($product->is_type('simple') && empty($value['Variations'])) {
									if (($value['Quantity'] - $value['SellingStatus']['QuantitySold']) > 0) {
										$available_quantity = $value['Quantity'] - $value['SellingStatus']['QuantitySold'];
										$logger->info('WOO quantity ' . wc_print_r($product->get_stock_quantity(), true) . '| EBAY quantity ' . wc_print_r($value['Quantity'], true) . '| quantity sold ' . wc_print_r($value['SellingStatus']['QuantitySold'], true) . ' | available quantity ' . wc_print_r($available_quantity, true), $context);
										$current_woo_quantity = $product->get_stock_quantity();
										if (!empty($current_woo_quantity)) {
											if ($current_woo_quantity == $available_quantity) {
												$logger->info('Woo quantity and ebay quantity is same', $context);
												delete_post_meta($ID, 'ced_ebay_stock_ebay_to_woo_running');
												continue;
											}
										}
										$logger->info('Updating quantity', $context);
										$product->set_manage_stock(true);
										$product->set_stock_quantity($value['Quantity'] - $value['SellingStatus']['QuantitySold']);
										$product->set_stock_status('instock');
										$product->save();
									} else {
										$logger->info('ebay quantity is zero Product out of stock', $context);
										$product->set_stock_quantity(0);
										$product->set_stock_status('outofstock');
										$product->save();
									}
								} elseif ($product->is_type('variable')) {
									$logger->info(wc_print_r('Variable Product', true), $context);
									if (!isset($value['Variations']['Variation'][0])) {
										$temp_array = array();
										$temp_array = $value['Variations']['Variation'];
										unset($value['Variations']['Variation']);
										$value['Variations']['Variation'][] = $temp_array;
									}
									if (isset($value['Variations']['Variation'][0]) && !empty($value['Variations']['Variation'][0])) {
										$product_variations = $value['Variations']['Variation'];
										foreach ($product_variations as $key => $variation) {
											$variation_sku = $variation['SKU'];
											$logger->info(wc_print_r('Variation SKU -> ' . $variation_sku, true), $context);

											if ($variation_sku) {
												$variation_prod_id = wc_get_product_id_by_sku($variation_sku);
												$logger->info('Variation_id ' . wc_print_r($variation_prod_id, true), $context);
												if ($variation_prod_id) {
													$var_product = wc_get_product($variation_prod_id);
												} else {
													continue;
												}
											} else {
												continue;
											}
											$logger->info('Quantity - ' . wc_print_r($variation['Quantity'] - $variation['SellingStatus']['QuantitySold'], true), $context);
											if ($variation['Quantity'] - $variation['SellingStatus']['QuantitySold'] > 0) {
												if (!is_wp_error($var_product)) {
													$var_product->set_stock_quantity($variation['Quantity'] - $variation['SellingStatus']['QuantitySold']);
													$var_product->save();
												} else {
													$logger->info('Error while fetching Variation product', $context);
												}
											} else {
												$var_product->set_stock_quantity(0);
												$var_product->save();
											}
										}
									} else {
										$logger->info('SIngle variation', $context);
									}
								}
							}
						}
					}
				} else {
					update_option('ced_ebay_seller_event_pagination_' . $user_id, 1);
					$logger->info('Item array is empty', $context);
					return;
				}
			} else {
				update_option('ced_ebay_seller_event_pagination_' . $user_id, 1);
				$logger->info('Error in api call', $context);
				return;
			}
		}
	}
}
