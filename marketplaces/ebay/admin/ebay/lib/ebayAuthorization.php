<?php
namespace Ced\Ebay;

if ( ! class_exists( 'Ebayauthorization' ) ) {
	class Ebayauthorization {


		private static $_instance;

		public $siteID;
		/**
		 * Get_instance Instance.
		 *
		 * Ensures only one instance of Ebayauthorization is loaded or can be loaded.
		 *
		userId
		 *
		 * @since 1.0.0
		 * @static
		 * @return get_instance instance.
		 */
		public static function get_instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}
		/**
		 * Constructor
		 */
		public function __construct() {
			$this->loadDepenedency();
			$this->siteID            = isset( $siteID ) ? $siteID : '';
		}


		public function getOAuthUrl( $siteID ) {
			// $redirectTo = get_admin_url() . 'admin.php?page=sales_channel&channel=ebay&section=setup-ebay';
			$redirectTo = urlencode(remove_query_arg( array( 'sandbox','add-new-account','login_mode' ), wc_get_current_admin_url() ));
			if(!empty(get_option('ced_ebay_mode_of_operation')) && 'sandbox' == get_option('ced_ebay_mode_of_operation', true)){
				$is_sandbox = true; 
			} else {
				$is_sandbox = false;
			}
			// $oauthUrl = 'https://api.cedcommerce.com/cedcommerce-validator/v1/auth?marketplace=ebay&site_id='.$siteID.'&domain='.get_site_url().'&home_redirect_url='.$redirectTo.'&sandbox='.$is_sandbox;
			$oauthUrl = 'https://api.cedcommerce.com/cedcommerce-validator/v1/auth?marketplace=ebay&site_id='.$siteID.'&domain=http://localhost:8888/wordpress&home_redirect_url='.$redirectTo.'&sandbox='.$is_sandbox;
			return $oauthUrl;
		}

		

		
		public function getUserData( $access_token, $siteID ) {
			if ( defined( 'EBAY_INTEGRATION_FOR_WOOCOMMERCE_VERSION' ) ) {
				$plugin_version = EBAY_INTEGRATION_FOR_WOOCOMMERCE_VERSION;
			}
			$requestXmlBody = '<?xml version="1.0" encoding="utf-8"?>
			<GetUserRequest xmlns="urn:ebay:apis:eBLBaseComponents">
			  <RequesterCredentials>
			    <eBayAuthToken>' . $access_token . '</eBayAuthToken>
			  </RequesterCredentials>
			</GetUserRequest>';
			$verb           = 'GetUser';
			$cedRequest     = new Ced\Ebay\Cedrequest( $siteID, $verb );
			$response       = $cedRequest->sendHttpRequest( $requestXmlBody );
			$wp_folder      = wp_upload_dir();
			$wp_upload_dir  = $wp_folder['basedir'];
			$wp_upload_dir  = $wp_upload_dir . '/ced-ebay/logs/';
			if ( ! is_dir( $wp_upload_dir ) ) {
					wp_mkdir_p( $wp_upload_dir, 0777 );
			}
			$log_file = $wp_upload_dir . 'user.txt';
			if ( $log_file ) {
				if ( file_exists( $log_file ) ) {
					wp_delete_file( $log_file );
				}
				file_put_contents( $log_file, PHP_EOL . 'Version - ' . $plugin_version, FILE_APPEND );
				file_put_contents( $log_file, PHP_EOL . 'Getting seller data...', FILE_APPEND );
			}
			$mode = get_option( 'ced_ebay_mode_of_operation', true );
			if ( 'production' == $mode ) {
				$marketingRequestFile = CED_EBAY_DIRPATH . 'admin/ebay/lib/cedMarketingRequest.php';
				if ( file_exists( $marketingRequestFile ) ) {
					require_once $marketingRequestFile;
					$cedMarketingRequest   = new \Ced_Marketing_API_Request( $siteID );
					$endpoint              = 'advertising_eligibility';
					$responseAccountsApi   = $cedMarketingRequest->sendHttpRequestForAccountAPI( $endpoint, $access_token, '' );
					$eligiblility_response = json_decode( $responseAccountsApi, true );
					if ( ! empty( $eligiblility_response['advertisingEligibility'] ) ) {
						if ( file_exists( $log_file ) ) {
							ced_ebay_log_data( $eligiblility_response, 'ced_getUserData', $log_file );
						}
					}

					$identityApi         = $cedMarketingRequest->sendHttpRequestForIdentityAPI( '', $access_token );
					$identityApiResponse = json_decode( $identityApi, true );
					if ( ! empty( $identityApiResponse ) && is_array( $identityApiResponse ) && isset( $identityApiResponse['username'] ) ) {
						$username = $identityApiResponse['username'];
						update_option( 'ced_ebay_user_identity_' . $username, $identityApiResponse );
					}
				}
			}

			ced_ebay_log_data( $response, 'ced_getUserData', $log_file );
			if ( isset( $response['Ack'] ) && 'Success' == $response['Ack'] ) {
				return $response['User'];
			}
			return $response;
		}

		public function getStoreData( $siteID, $user_id ) {
			
		}

		public $ebayConfigInstance;
		public $ebayConfig;
		public $cedRequestInstance;
		/**
		 * Function to get session id
		 *
		 * @name getSessionid
		 */
		public function loadDepenedency() {
			if ( is_file( __DIR__ . '/ebayConfig.php' ) ) {
				require_once 'ebayConfig.php';
				$this->ebayConfigInstance = Ebayconfig::get_instance();
			}
			if ( is_file( __DIR__ . '/cedRequest.php' ) ) {
				require_once 'cedRequest.php';
			}
		}
	}
}
