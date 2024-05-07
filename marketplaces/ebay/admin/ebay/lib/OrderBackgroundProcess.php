<?php

namespace Ced\Ebay;
defined( 'ABSPATH' ) || die( 'Direct access not allowed' );


class OrderBackground_Process extends \WP_Background_Process {

	public function __construct() {
		parent::__construct();
	}

	protected $action = 'schedule_async_task';

	protected function task( $item ) {

		$logger   = wc_get_logger();
		$context  = array( 'source' => 'ced_ebay_background_process' );
		$order_id = $item['order_id'];
		$user_id  = $item['user_id'];
		$logger->info( 'User Id - ' . wc_print_r( $user_id, true ), $context );
		$logger->info( 'Processing order ID - ' . wc_print_r( $order_id, true ), $context );
		$args         = array(
			'post_type'   => 'shop_order',
			'post_status' => 'any',
			'numberposts' => 1,
			'meta_query'  => array(
				'relation' => 'OR',
				array(
					'key'     => '_ced_ebay_order_id',
					'value'   => $order_id,
					'compare' => '=',
				),
				array(
					'key'     => '_ebay_order_id',
					'value'   => $order_id,
					'compare' => '=',
				),
			),
		);
		$order        = get_posts( $args );
		$order_id_arr = wp_list_pluck( $order, 'ID' );
		if ( ! empty( $order_id_arr ) ) {
			$logger->info( 'Woo 0rder already exists with ID - ' . wc_print_r( $order_id, true ), $context );
			return false;
		}
		$shop_data = ced_ebay_get_shop_data( $user_id );
		if ( ! empty( $shop_data ) ) {
			$siteID      = $shop_data['site_id'];
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
			$createOrder   = $orderInstance->create_localOrders( $order, $user_id );
		} else {
			$logger->info( 'Unable to find the order data by API', $context );
		}

		return false;
	}

	protected function complete() {
		wc_get_logger()->info( 'Finalized' );
		parent::complete();
	}
}
