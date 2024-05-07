<?php
namespace Ced\Ebay;
use WP_Error;
if ( ! class_exists( 'EbayUpload' ) ) {
	class EbayUpload {


		private static $_instance;

		public $remote_shop_id;

		/**
		 * Get_instance Instance.
		 *
		 * Ensures only one instance of CedAuthorization is loaded or can be loaded.
		 *
		 *
		 * @since 1.0.0
		 * @static
		 * @return get_instance instance.
		 */
		public static function get_instance( $remote_shop_id ) {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self( $remote_shop_id );
			}
			return self::$_instance;
		}
		/**
		 * Construct
		 */
		public function __construct( $remote_shop_id ) {
			$this->loadDepenedency();
			$this->remote_shop_id  = $remote_shop_id;
		}

		/**
		 * Function to get all active seller products from Ebay
		 *
		 * @name update
		 */
		public function get_active_products($page=1, $limit=200) {
			$apiClient = new \Ced\Ebay\CED_EBAY_API_Client();
			$apiClient->setJwtToken('jhgjhghjg');
			$apiClient->setRequestRemoteMethod('GET');
			$apiClient->setRequestRemoteQueryParams([
				"shop_id" => $this->remote_shop_id,
				"type" => "GetMyEbaySelling",
				"count" => $limit,
				"page" => $page,

			]);
			$apiClient->setRequestTopic('product');
			$apiResponse = $apiClient->post();
			if(isset($apiResponse['data'])){
				$apiResponse = json_decode($apiResponse['data'], true);
				return $apiResponse;
			} else {
				if(isset($apiResponse['error_code'])){
					return new WP_Error($apiResponse['error_code'], 'Unable to get active products from eBay');
				} else {
					return false;
				}
			}
			
		}

		/**
		 * Function to get item details while importing from ebay
		 *
		 * @name update
		 */
		public function get_item_details( $itemId ) {
			$apiClient = new \Ced\Ebay\CED_EBAY_API_Client();
			$apiClient->setJwtToken('jhgjhghjg');
			$apiClient->setRequestRemoteMethod('GET');
			$apiClient->setRequestRemoteQueryParams([
				"shop_id" => $this->remote_shop_id,
				"type" => "GetItem",
				"id" => $itemId

			]);
			$apiClient->setRequestTopic('product');
			$apiResponse = $apiClient->post();
			if(isset($apiResponse['data'])){
				$apiResponse = json_decode($apiResponse['data'], true);
				return $apiResponse;
			} else {
				if(isset($apiResponse['error_code'])){
					return new WP_Error($apiResponse['error_code'], 'Unable to fetch eBay listing details');
				} else {
					return false;
				}
			}
		}

		/**
		 * Function to get
		 *
		 * @name endItems
		 */
		public function endItems( $itemId ) {
			$apiClient = new \Ced\Ebay\CED_EBAY_API_Client();
			$apiClient->setJwtToken('jhgjhghjg');
			$apiClient->setRequestRemoteMethod('GET');
			$apiClient->setRequestRemoteQueryParams([
				"shop_id" => $this->remote_shop_id,
				"type" => "EndItem",
				"listing_id" => $itemId

			]);
			$apiClient->setRequestTopic('product');
			$apiResponse = $apiClient->post();
			if(isset($apiResponse['data'])){
				$apiResponse = json_decode($apiResponse['data'], true);
				return $apiResponse;
			} else {
				if(isset($apiResponse['error_code'])){
					return new WP_Error($apiResponse['error_code'], 'Unable to end the listing on eBay');
				} else {
					return false;
				}
			}


		}
		/**
		 * Function loadDepenedency
		 *
		 * @name loadDepenedency
		 */
		public function loadDepenedency() {
			if ( is_file( __DIR__ . '/cedRequest.php' ) ) {
				require_once 'cedRequest.php';
			}
		}
	}
}
