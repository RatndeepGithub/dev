<?php
/**
 * Gettting mandatory data
 *
 * @package  Woocommerce_Walmart_Integration
 * @version  1.0.0
 * @link     https://woocommerce.com/vendor/cedcommerce/
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

	/**
	 * Ced_Walmart_Process_Request
	 *
	 * @since 3.1.0
	 */

class Ced_Walmart_Remote_Request {

	/**
	 * The instance variable of this class.
	 *
	 * @since    3.1.0
	 * @var      object    $_instance    The instance variable of this class.
	 */
	public static $_instance;

	/**
	 * The store id to be used for this class.
	 *
	 * @since    3.1.0
	 * @var      object    $_instance    The instance variable of this class.
	 */
	public static $store_id;

	/**
	 * Ced_Walmart_Process_Request Instance.
	 *
	 * @since 3.1.0
	 */
	
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function ced_walmart_process_request( $action = '', $parameters = array(), $query_args = array(), $request_method = '' ) {
		if ( empty( $action ) || empty($request_method) ) {
			return;
		}
		
		$remote_api_url        = 'https://api.cedcommerce.com/cedcommerce-validator/v1/remote';
		$query_args['shop_id'] = '29';
		$body                  = array(
			'marketplace'  => 'walmart',
			'topic'        => $action,
			'query_params' => $query_args,
			'method'       => $request_method,
			'domain'       => 'http://localhost:8888/wordpress',
		);

		if (isset($parameters) && !empty($parameters) && is_array($parameters)) {
			$body['body'] = $parameters;
		}

		$body = wp_json_encode( $body );

		$access_token =  'eyjsbjsdjk5sdbbkaowpk';

		$options = array(   // $args
			'body'    => $body,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $access_token,
				'Product'       => 'walmart'
			),
			'timeout'     => 30,
			'sslverify'   => 0,
			'data_format' => 'body',
		);

		$response = wp_remote_post($remote_api_url , $options);
		
		return $response;
	}

	public function ced_walmart_orders_count_request( $order_ids = array() ) {
		if ( empty( $order_ids ) ) {
			return;
		} elseif ( ! empty( $order_ids) && is_array( $order_ids ) ) {

			$remote_api_url = 'https://api.cedcommerce.com/api/v1/orders_count';
			
			$body = array(
				'marketplace' => 'walmart',
				'topic'       => 'orders_count',
				'order_ids'   => $order_ids,
				'domain'	  => str_replace(array('http://', 'https://'), '', site_url()),
				
			);
		}
	}
}
