<?php
namespace Ced_Ebay_WooCommerce_Core;

if ( ! class_exists( 'Ced_Ebay_OAuth_Authorization' ) ) {
	class Ced_Ebay_OAuth_Authorization {
		public $ebayConfigInstance;

		public $ebayAuthInstance;

		private static $_instance;

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
			$this->loadDependency();
		}

		public function doOAuthAuthorization( $siteID ) {
			$authURL = $this->ebayOAuthInstance->getOAuthUrl( $siteID );
			return $authURL;
		}

		public function fetchOAuthUserAccessToken( $code, $siteID, $grantType ) {
			$accessCode = $this->ebayOAuthInstance->oauthRequestAccessToken( $code, $siteID, $grantType );
			return $accessCode;
		}

		public function loadDependency() {
			if ( is_file( __DIR__ . '/ebayAuthorization.php' ) ) {
				require_once 'ebayAuthorization.php';
				$this->ebayOAuthInstance = Ebayauthorization::get_instance();
			}
		}
	}
}
