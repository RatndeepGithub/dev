<?php
namespace Ced\Ebay;
if ( ! class_exists( 'EbayGetCategories' ) ) {
	class EbayGetCategories {


		public $ebayConfigInstance;

		private static $_instance;

		public $siteID;

		public $catInfoUrl;

		public $apiClient;

		public $rsid;
		/**
		 * Get_instance Instance.
		 *
		 * Ensures only one instance of CedAuthorization is loaded or can be loaded.
		 *
		userId
		 *
		 * @since 1.0.0
		 * @static
		 * @return get_instance instance.
		 */
		public static function get_instance( $siteID, $rsid ) {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self( $siteID, $rsid );
			}
			return self::$_instance;
		}
		/**
		 * Constructor
		 */
		public function __construct( $siteID, $rsid ) {
			$this->loadDepenedency();
			$this->siteID     = $siteID;
			$this->rsid      = $rsid;
			$this->apiClient = new \Ced\Ebay\CED_EBAY_API_Client();
			$this->apiClient->setJwtToken('jhgjhghjg');
			
		}

		/*
		 * Get Categories form ebay
		 */
		public function GetCategories( $level = 1, $ParentcatID = null ) {
			$siteID      = $this->siteID;
			$rsid = $this->rsid;
			$this->apiClient->setRequestRemoteMethod('GET');
			$this->apiClient->setRequestRemoteQueryParams([
				"shop_id" => $rsid['remote_shop_id'],
				"type" => "GetCategories",
				"site_id" => $siteID,
				"level" => $level
			]);
			$this->apiClient->setRequestTopic('category');
			$apiResponse = $this->apiClient->post();
			if(isset($apiResponse['data'])){
				$categoryFetchResponse = json_decode($apiResponse['data'], true);
				return $categoryFetchResponse;
			} else {
				if(isset($apiResponse['error_code'])){
					return $apiResponse;
				} else {
					return new \WP_Error('api_error', 'An error occurred while fetching eBay categories');
				}
			}
			return false;	
		}

		public function GetCategorySpecifics( $catID ) {
			$siteID = $this->siteID;
			$rsid = $this->rsid;
			$this->apiClient->setRequestRemoteMethod('GET');
			$this->apiClient->setRequestRemoteQueryParams([
				"shop_id" => $rsid['remote_shop_id'],
				"type" => "GetItemAspectsForCategory",
				"site_id" => $siteID,
				"new_api" => true,
				"category_id" => $catID
			]);
			$this->apiClient->setRequestTopic('category');
			$apiResponse = $this->apiClient->post();
			if(isset($apiResponse['data'])){
				$response = json_decode($apiResponse['data'], true);
				if ( ! empty( $response['aspects'] ) ) {
					if ( ! isset( $response['aspects'][0] ) ) {
						$temp_response = $response['aspects'];
						unset( $response['aspects'] );
						$response['aspects'][] = $temp_response;
					}
					return $response['aspects'];
				} else {
					return new \WP_Error('item_specifics_error', $response);

				}
				if ( ! empty( $response['errors'] ) ) {
					if ( ! isset( $response['errors'][0] ) ) {
						$temp_response = $response['errors'];
						unset( $response['errors'] );
						$response['errors'][] = $temp_response;
					}
					return $response['errors'];
				}
			}
			return false;			
		}

		
		public function GetCategoryFeatures( $catID ) {

			$siteID      = $this->siteID;
			$rsid = $this->rsid;
			$configInstance           = \Ced\Ebay\Ebayconfig::get_instance();
			$countryDetails           = $configInstance->getEbaycountrDetail( $siteID );
			$country_code             = $countryDetails['countrycode'];
			$marketplace_enum         = 'EBAY_' . $country_code;
			$this->apiClient->setRequestRemoteMethod('GET');
			$this->apiClient->setRequestRemoteQueryParams([
				"shop_id" => $rsid['remote_shop_id'],
				"type" => "getItemConditionPolicies",
				"marketplace_id" => $marketplace_enum,
				'filter' => 'categoryIds:{'.$catID.'}',
			]);
			$this->apiClient->setRequestTopic('meta');
			$apiResponse = $this->apiClient->post();
			if(isset($apiResponse['data'])){
				$response = $apiResponse['data'];
				if ( ! empty( $response['itemConditionPolicies'] ) ) {
					if ( ! isset( $response['itemConditionPolicies'][0] ) ) {
						$temp_response = $response['itemConditionPolicies'];
						unset( $response['itemConditionPolicies'] );
						$response['itemConditionPolicies'][] = $temp_response;
					}
					return $response['itemConditionPolicies'];
				} else {
					return new \WP_Error('item_conditions_error', $response);
				}
				if ( ! empty( $response['errors'] ) ) {
					if ( ! isset( $response['errors'][0] ) ) {
						$temp_response = $response['errors'];
						unset( $response['errors'] );
						$response['errors'][] = $temp_response;
					}
					return $response['errors'];
				}
			}	
			
			return false;
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
			if ( is_file( __DIR__ . '/ebayConfig.php' ) ) {
				require_once 'ebayConfig.php';
				$this->ebayConfigInstance = \Ced\Ebay\Ebayconfig::get_instance();
			}
		}
	}
}
