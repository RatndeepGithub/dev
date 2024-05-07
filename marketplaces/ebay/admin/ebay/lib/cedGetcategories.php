<?php
namespace Ced\Ebay;

if ( ! class_exists( 'CedGetCategories' ) ) {
	class CedGetCategories {


		public $rsid;

		public $siteID;

		public static $_instance;

		public $ebayCatInstance;
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
		public function __construct( $siteID, $rsid ) {
			$this->rsid  = $rsid;
			$this->siteID = $siteID;
			$this->loadDepenedency();
		}

		public function _getCategories( $level, $ParentcatID = null ) {
			if ( null != $ParentcatID ) {
				$ebayCats = $this->ebayCatInstance->GetCategories( $level, $ParentcatID );
			} else {
				$ebayCats = $this->ebayCatInstance->GetCategories( $level );
			}
			if ( $ebayCats ) {
				return $ebayCats;
			}
			return false;
		}

		

		/**
		 * Function to get category specifics
		 *
		 * @name _getCatSpecifics
		 */
		public function _getCatSpecifics( $catId ) {
			$ebayCatSpecifics = false;
			if ( '' != $catId && null != $catId ) {
				$ebayCatSpecifics = $this->ebayCatInstance->GetCategorySpecifics( $catId );
			}
			if ( $ebayCatSpecifics ) {
				return $ebayCatSpecifics;
			}
			return new \WP_Error('api_error', 'An error occured while fetching category specifics');
		}
		/**
		 * Function to get category features
		 *
		 * @name _getCatFeatures()
		 */
		public function _getCatFeatures( $catID ) {
			$ebayCatFeatures = false;
			if ( '' != $catID && null != $catID ) {
				$ebayCatFeatures = $this->ebayCatInstance->GetCategoryFeatures( $catID );
			}
			if ( $ebayCatFeatures ) {
				return $ebayCatFeatures;
			}
			return new \WP_Error('api_error', 'An error occured while fetching category features');
		}
		
		/**
		 * Function to load dependencies
		 *
		 * @name loadDepenedency
		 */
		public function loadDepenedency() {
			if ( is_file( __DIR__ . '/ebayGetCategories.php' ) ) {
				require_once 'ebayGetCategories.php';
				$this->ebayCatInstance = \Ced\Ebay\EbayGetCategories::get_instance( $this->siteID, $this->rsid );

			}
		}
	}
}
