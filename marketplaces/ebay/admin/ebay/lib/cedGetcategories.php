<?php
if ( ! class_exists( 'cedGetcategories' ) ) {
	class CedGetCategories {


		public $token;

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
		public static function get_instance( $siteID, $token ) {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self( $siteID, $token );
			}
			return self::$_instance;
		}
		public function __construct( $siteID, $token ) {
			$this->token  = $token;
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

		public function _getCategoryTree( $ParentcatID = null ) {
				$ebayCats = $this->ebayCatInstance->GetCategoryTree( $ParentcatID );
			if ( $ebayCats ) {
				return $ebayCats;
			}
			return false;
		}

		public function _getJsonCategories( $level, $ParentcatID = null ) {
			if ( null != $ParentcatID ) {
				$ebayCats = $this->ebayCatInstance->GetJsonCategories( $level, $ParentcatID );
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
			return false;
		}
		/**
		 * Function to get category features
		 *
		 * @name _getCatFeatures()
		 */
		public function _getCatFeatures( $catID, $limit ) {
			$ebayCatFeatures = false;
			if ( '' != $catID && null != $catID ) {
				$ebayCatFeatures = $this->ebayCatInstance->GetCategoryFeatures( $catID, $limit );
			}
			if ( $ebayCatFeatures ) {
				return $ebayCatFeatures;
			}
			return false;
		}
		/**
		 * Function to get category information
		 *
		 * @name _getCatFeatures()
		 */
		public function _getCatInfo( $catID ) {
			$ebayCatInfo = array();
			if ( '' != $catID && null != $catID ) {
				$ebayCatInfo = $this->ebayCatInstance->GetCategoryInfo( $catID );
			}
			if ( $ebayCatInfo ) {
				return $ebayCatInfo;
			}
			return false;
		}
		/**
		 * Function to load dependencies
		 *
		 * @name loadDepenedency
		 */
		public function loadDepenedency() {
			if ( is_file( __DIR__ . '/ebayGetCategories.php' ) ) {
				require_once 'ebayGetCategories.php';
				$this->ebayCatInstance = EbayGetCategories::get_instance( $this->siteID, $this->token );

			}
		}
	}
}
