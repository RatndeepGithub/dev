<?php
if ( ! class_exists( 'EbayUpload' ) ) {
	class EbayUpload {


		private static $_instance;

		public $siteID;
		public $token;

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
		/**
		 * Construct
		 */
		public function __construct( $siteID, $token ) {
			$this->loadDepenedency();
			$this->token  = $token;
			$this->siteID = $siteID;
		}



		/**
		 * Function to upload products on Ebay
		 *
		 * @name upload
		 */
		public function upload( $xmlbody, $variation ) {

			$siteID = $this->siteID;
			global $cedumbhelper;
			$param = array();
			if ( $variation ) {
				$verb = 'AddFixedPriceItem';
			} else {
				$verb = 'AddItem';
			}
			$cedRequest = new Ced_Ebay_WooCommerce_Core\Cedrequest( $siteID, $verb );
			$response   = $cedRequest->sendHttpRequest( $xmlbody );
			if ( isset( $response['Ack'] ) && 'Failure' == $response['Ack'] ) {
				$cronRelatedData = get_option( 'ced_umb_ebay_cronRelatedData', false );

				$ced_umb_ebay_allow_access_to_dev = isset( $cronRelatedData['ced_umb_ebay_allow_access_to_dev'] ) ? $cronRelatedData['ced_umb_ebay_allow_access_to_dev'] : 'no';
				if ( 'yes' == $ced_umb_ebay_allow_access_to_dev ) {
					$param['action'] = $verb;
					$param['issue']  = 'Failed Product Upload due to -> ' . json_encode( $response );
				}
			}
			if ( $response ) {
				return $response;
			}
			return false;
		}

		public function setNotificationPreference( $xmlbody ) {
			$siteID     = $this->siteID;
			$verb       = 'SetNotificationPreferences';
			$cedRequest = new Ced_Ebay_WooCommerce_Core\Cedrequest( $siteID, $verb );
			$response   = $cedRequest->sendHttpRequest( $xmlbody );
			if ( isset( $response['Ack'] ) && 'Success' == $response['Ack'] ) {
				return $response;
			} else {
				return 'Error!';
			}
		}
		/**
		 * Function to upload products on Ebay
		 *
		 * @name upload
		 */
		public function relist( $xmlbody ) {

			$siteID = $this->siteID;
			global $cedumbhelper;
			$param      = array();
			$verb       = 'RelistFixedPriceItem';
			$cedRequest = new Ced_Ebay_WooCommerce_Core\Cedrequest( $siteID, $verb );
			$response   = $cedRequest->sendHttpRequest( $xmlbody );
			if ( $response ) {
				return $response;
			}
			return false;
		}




		/**
		 * Function to get all active seller products from Ebay
		 *
		 * @name update
		 */
		public function get_active_products( $xmlbody ) {
			global $cedumbhelper;
			$param      = array();
			$siteID     = $this->siteID;
			$verb       = 'GetMyeBaySelling';
			$cedRequest = new Ced_Ebay_WooCommerce_Core\Cedrequest( $siteID, $verb );
			$response   = $cedRequest->sendHttpRequest( $xmlbody );

			if ( $response ) {
				return $response;
			}
			return false;
		}

		public function get_ebay_time( $xmlbody ) {
			global $cedumbhelper;
			$param      = array();
			$siteID     = $this->siteID;
			$verb       = 'GeteBayOfficialTime';
			$cedRequest = new Ced_Ebay_WooCommerce_Core\Cedrequest( $siteID, $verb );
			$response   = $cedRequest->sendHttpRequest( $xmlbody );

			if ( $response ) {
				return $response;
			}
			return false;
		}

		/**
		 * Function to get shipping services for the site location
		 *
		 * @name update
		 */
		public function get_shipping_return_service_for_site( $xmlbody ) {
			$siteID     = $this->siteID;
			$verb       = 'GeteBayDetails';
			$cedRequest = new Ced_Ebay_WooCommerce_Core\Cedrequest( $siteID, $verb );
			$response   = $cedRequest->sendHttpRequest( $xmlbody );

			if ( $response ) {
				return $response;
			}
			return false;
		}


		/**
		 * Function to get payment methods for the site location
		 *
		 * @name update
		 */
		public function get_sitespecific_payment_methods( $xmlbody ) {
			$siteID     = $this->siteID;
			$verb       = 'GetCategoryFeatures';
			$cedRequest = new Ced_Ebay_WooCommerce_Core\Cedrequest( $siteID, $verb );
			$response   = $cedRequest->sendHttpRequest( $xmlbody );

			if ( $response ) {
				return $response;
			}
			return false;
		}

		/**
		 * Function to get payment methods for the site location
		 *
		 * @name update
		 */
		public function get_sitespecific_return_methods( $xmlbody ) {
			$siteID     = $this->siteID;
			$verb       = 'GeteBayDetails';
			$cedRequest = new Ced_Ebay_WooCommerce_Core\Cedrequest( $siteID, $verb );
			$response   = $cedRequest->sendHttpRequest( $xmlbody );

			if ( $response ) {
				return $response;
			}
			return false;
		}

		public function cedEbayUpdateInventory( $xmlbody ) {
			global $cedumbhelper;
			$param      = array();
			$siteID     = $this->siteID;
			$verb       = 'ReviseInventoryStatus';
			$cedRequest = new Ced_Ebay_WooCommerce_Core\Cedrequest( $siteID, $verb );
			$response   = $cedRequest->sendHttpRequest( $xmlbody );
			if ( $response ) {
				return $response;
			}
			return false;
		}
		/**
		 * Function to get item details while importing from ebay
		 *
		 * @name update
		 */
		public function get_item_details( $xmlbody ) {
			$siteID     = $this->siteID;
			$verb       = 'GetItem';
			$cedRequest = new Ced_Ebay_WooCommerce_Core\Cedrequest( $siteID, $verb );
			$response   = $cedRequest->sendHttpRequest( $xmlbody );
			if ( $response ) {
				return $response;
			}
			return false;
		}

		/**
		 * Function to update products on Ebay
		 *
		 * @name update
		 */
		public function update( $xmlbody, $variation ) {
			global $cedumbhelper;
			$param  = array();
			$siteID = $this->siteID;
			if ( $variation ) {
				$verb = 'ReviseFixedPriceItem';
			} else {
				$verb = 'ReviseItem';
			}
			$cedRequest = new Ced_Ebay_WooCommerce_Core\Cedrequest( $siteID, $verb );
			$response   = $cedRequest->sendHttpRequest( $xmlbody );

			if ( isset( $response['Ack'] ) && 'Failure' == $response['Ack'] ) {
				$cronRelatedData = get_option( 'ced_umb_ebay_cronRelatedData', false );

				$ced_umb_ebay_allow_access_to_dev = isset( $cronRelatedData['ced_umb_ebay_allow_access_to_dev'] ) ? $cronRelatedData['ced_umb_ebay_allow_access_to_dev'] : 'no';
				if ( 'yes' == $ced_umb_ebay_allow_access_to_dev ) {
					$param['action'] = $verb;
					$param['issue']  = 'Unable to update products due to -> ' . json_encode( $response );
				}
			}

			if ( $response ) {
				return $response;
			}
			return false;
		}


		/**
		 * Function to get
		 *
		 * @name endItems
		 */
		public function endItems( $productIds ) {
			if ( is_array( $productIds ) && ! empty( $productIds ) ) {
				$endItemsXML = $this->EndITemsXml( $productIds );
				$siteID      = $this->siteID;
				$verb        = 'EndItems';
				$cedRequest  = new Ced_Ebay_WooCommerce_Core\Cedrequest( $siteID, $verb );
				$response    = $cedRequest->sendHttpRequest( $endItemsXML );
				if ( $response ) {
					return $response;
				}
				return false;
			}
		}
		public function end_fixed_price_items_request( $productIds ) {
			if ( is_array( $productIds ) && ! empty( $productIds ) ) {
				$endItemsXML = $this->end_fixed_price_items_xml( $productIds );
				$siteID      = $this->siteID;
				$verb        = 'EndFixedPriceItem';
				$cedRequest  = new Ced_Ebay_WooCommerce_Core\Cedrequest( $siteID, $verb );
				$response    = $cedRequest->sendHttpRequest( $endItemsXML );
				if ( $response ) {
					return $response;
				}
				return false;
			}
		}

		public function end_fixed_price_items_xml( $productIds ) {
			$requestBody  = '<?xml version="1.0" encoding="utf-8" ?>';
			$requestBody .= '<EndItemsRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
			$requestBody .= '<RequesterCredentials>
							    <eBayAuthToken>' . $this->token . '</eBayAuthToken>
							  </RequesterCredentials>';
			$message      = '';
			foreach ( $productIds as $key => $value ) {
				$msgID        = $key;
				$listing_type = get_post_meta( $key, 'ebay_listing_type', true );
				$message      = 'NotAvailable';
				if ( 'Chinese' == $listing_type ) {
					$message = 'SellToHighBidder';
				}
				$requestBody .= '<EndItemRequestContainer>
								    <MessageID>' . $msgID . '</MessageID>
								    <EndingReason>' . $message . '</EndingReason>
								    <ItemID>' . $value . '</ItemID>
								  </EndItemRequestContainer>';

			}
			$requestBody .= '</EndItemsRequest>';
			return $requestBody;
		}

		public function ced_ebay_upload_image_to_eps( $xml ) {
			$siteID     = $this->siteID;
			$token      = $this->token;
			$verb       = 'UploadSiteHostedPictures';
			$cedRequest = new Ced_Ebay_WooCommerce_Core\Cedrequest( $siteID, $verb, $token );
			$response   = $cedRequest->sendHttpRequest( $xml );
			if ( $response ) {
				return $response;
			}
			return false;
		}

		public function EndITemsXml( $productIds ) {
			$requestBody  = '<?xml version="1.0" encoding="utf-8" ?>';
			$requestBody .= '<EndItemsRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
			$requestBody .= '<RequesterCredentials>
							    <eBayAuthToken>' . $this->token . '</eBayAuthToken>
							  </RequesterCredentials>';
			$message      = '';
			foreach ( $productIds as $key => $value ) {
				$msgID        = $key;
				$listing_type = get_post_meta( $key, 'ebay_listing_type', true );
				$message      = 'NotAvailable';
				if ( 'Chinese' == $listing_type ) {
					$message = 'SellToHighBidder';
				}
				$requestBody .= '<EndItemRequestContainer>
								    <MessageID>' . $msgID . '</MessageID>
								    <EndingReason>' . $message . '</EndingReason>
								    <ItemID>' . $value . '</ItemID>
								  </EndItemRequestContainer>';

			}
			$requestBody .= '</EndItemsRequest>';
			return $requestBody;
		}

		public function set_user_preferences( $xmlbody ) {
			global $cedumbhelper;
			$param      = array();
			$siteID     = $this->siteID;
			$verb       = 'SetUserPreferences';
			$cedRequest = new Ced_Ebay_WooCommerce_Core\Cedrequest( $siteID, $verb );
			$response   = $cedRequest->sendHttpRequest( $xmlbody );

			if ( $response ) {
				return $response;
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
		}
	}
}
