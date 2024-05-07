<?php

if ( ! class_exists( 'Ced_Marketing_API_Request' ) ) {
	class Ced_Marketing_API_Request {


		public $marketingURL;
		public $fulfillmentURL;
		public $feedApiUrl;
		public $postOrderURL;
		public $accountUrl;

		public $browseApiUrl;

		public $inventoryUrl;
		public $identityUrl;
		public $ebayConfigInstance;
		public $verb;

		public $taxonomyURL;

		public $ebaySites;

		public $siteID;

		public function __construct( $siteToUseID ) {
			$this->loadDependency();
			$this->siteID         = $siteToUseID;
			$this->marketingURL   = $this->ebayConfigInstance->marketingURL;
			$this->fulfillmentURL = $this->ebayConfigInstance->fulfillmentURL;
			$this->taxonomyURL    = $this->ebayConfigInstance->taxonomyURL;
			$this->postOrderURL   = $this->ebayConfigInstance->postOrderURL;
			$this->ebaySites      = $this->ebayConfigInstance->getEbaysites();
			$this->accountUrl     = $this->ebayConfigInstance->accountURL;
			$this->identityUrl    = $this->ebayConfigInstance->identityUrl;
			$this->inventoryUrl   = $this->ebayConfigInstance->inventoryUrl;
			$this->feedApiUrl     = $this->ebayConfigInstance->feedApiUrl;
			$this->browseApiUrl   = $this->ebayConfigInstance->browseApiUrl;
		}

		public function sendHttpRequestForInventoryAPI( $endpoint, $token, $curlRequestType = '', $requestBody = '' ) {
			// build eBay headers using variables passed via constructor
			$headers = $this->buildMarketingHeaders( $token );
			// initialise a CURL session
			$connection = curl_init();
			// set the server we are using (could be Sandbox or Production server)
			curl_setopt( $connection, CURLOPT_URL, $this->inventoryUrl . $endpoint );
			// stop CURL from verifying the peer's certificate
			curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
			curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, 0 );
			if ( 'POST_GET_HEADER_STATUS' == $curlRequestType ) {
				curl_setopt( $connection, CURLOPT_HTTPHEADER, $headers );
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $connection, CURLOPT_VERBOSE, 1 );
				curl_setopt( $connection, CURLOPT_HEADER, 1 );
				curl_setopt( $connection, CURLOPT_POST, 1 );
				curl_setopt( $connection, CURLOPT_POSTFIELDS, $requestBody );
				$response = curl_exec( $connection );
				$httpcode = curl_getinfo( $connection, CURLINFO_HTTP_CODE );
				curl_close( $connection );
				return $httpcode;
			} else {
				curl_setopt( $connection, CURLOPT_HTTPHEADER, $headers );

				curl_setopt( $connection, CURLOPT_HTTPGET, 1 );

				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );

				$response = curl_exec( $connection );

				curl_close( $connection );

				return $response;
			}

			// set the headers using the array of headers
		}

		public function sendHttpRequestForBrowseAPI( $endpoint, $token, $curlRequestType = '', $requestBody = '' ) {
			// build eBay headers using variables passed via constructor
			$headers = $this->buildMarketingHeaders( $token );
			// initialise a CURL session
			$connection = curl_init();
			// set the server we are using (could be Sandbox or Production server)
			curl_setopt( $connection, CURLOPT_URL, $this->browseApiUrl . $endpoint );
			// stop CURL from verifying the peer's certificate
			curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
			curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, 0 );
			if ( 'POST_GET_HEADER_STATUS' == $curlRequestType ) {
				curl_setopt( $connection, CURLOPT_HTTPHEADER, $headers );
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $connection, CURLOPT_VERBOSE, 1 );
				curl_setopt( $connection, CURLOPT_HEADER, 1 );
				curl_setopt( $connection, CURLOPT_POST, 1 );
				curl_setopt( $connection, CURLOPT_POSTFIELDS, $requestBody );
				$response = curl_exec( $connection );
				$httpcode = curl_getinfo( $connection, CURLINFO_HTTP_CODE );
				curl_close( $connection );
				return $httpcode;
			} else {
				curl_setopt( $connection, CURLOPT_HTTPHEADER, $headers );

				curl_setopt( $connection, CURLOPT_HTTPGET, 1 );

				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );

				$response = curl_exec( $connection );

				curl_close( $connection );

				return $response;
			}

			// set the headers using the array of headers
		}

		public function sendHttpRequestForCampaignAPI( $requestBody, $endpoint, $user_access_token, $curlRequestType ) {
			// build eBay headers using variables passed via constructor
			$headers = $this->buildMarketingHeaders( $user_access_token );
			// initialise a CURL session
			$connection = curl_init();
			// set the server we are using (could be Sandbox or Production server)
			curl_setopt( $connection, CURLOPT_URL, $this->marketingURL . '/' . $endpoint );

			// stop CURL from verifying the peer's certificate
			curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
			curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, 0 );

			// set the headers using the array of headers
			curl_setopt( $connection, CURLOPT_HTTPHEADER, $headers );

			if ( 'POST_GET_HEADER_STATUS' == $curlRequestType ) {
					curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
					curl_setopt( $connection, CURLOPT_HEADER, 1 );
					curl_setopt( $connection, CURLOPT_POST, 1 );
					curl_setopt( $connection, CURLOPT_POSTFIELDS, $requestBody );
					$response    = curl_exec( $connection );
					$header_size = curl_getinfo( $connection, CURLINFO_HEADER_SIZE );
					$httpcode    = curl_getinfo( $connection, CURLINFO_HTTP_CODE );
				if ( 201 == $httpcode ) {
					return $httpcode;
				} elseif ( 204 == $httpcode ) {
					return $httpcode;
				} else {
					$body = substr( $response, $header_size );
					return $body;
				}
					curl_close( $connection );
			} else {
				if ( 'POST' == $curlRequestType ) {
					curl_setopt( $connection, CURLOPT_POST, 1 );

					// set the XML body of the request
					curl_setopt( $connection, CURLOPT_POSTFIELDS, $requestBody );
				} elseif ( 'DELETE' == $curlRequestType ) {
					curl_setopt( $connection, CURLOPT_CUSTOMREQUEST, 'DELETE' );
					curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
					$response    = curl_exec( $connection );
					$header_size = curl_getinfo( $connection, CURLINFO_HEADER_SIZE );
					$httpcode    = curl_getinfo( $connection, CURLINFO_HTTP_CODE );
					if ( 204 != $httpcode ) {
						$body = substr( $response, $header_size );
						return $body;
					} elseif ( 204 == $httpcode ) {
						return $httpcode;
					}
					curl_close( $connection );

				} elseif ( 'POST_UPDATE_AD_RATE' == $curlRequestType ) {
					// used to get http status code of updateBid call.
					curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
					curl_setopt( $connection, CURLOPT_HEADER, true );
					curl_setopt( $connection, CURLOPT_NOBODY, true );
					curl_setopt( $connection, CURLOPT_TIMEOUT, 10 );
					curl_setopt( $connection, CURLOPT_POST, 1 );
					curl_setopt( $connection, CURLOPT_POSTFIELDS, $requestBody );
					$response = curl_exec( $connection );
					$httpcode = curl_getinfo( $connection, CURLINFO_HTTP_CODE );
					return $httpcode;
					curl_close( $connection );

				}

				// set it to return the transfer as a string from curl_exec
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );

				// Send the Request
				$response = curl_exec( $connection );
				// close the connection
				curl_close( $connection );
				return $response;
			}
		}

		/** SendHttpRequest
		Sends a HTTP request to the server for this session
		Input:  $requestBody
		Output: The HTTP Response as a String
		 */

		public function sendHttpRequestForMarketingAPI( $endpoint, $token, $curlRequestType = '' ) {
			// build eBay headers using variables passed via constructor
			$headers = $this->buildMarketingHeaders( $token );
			// initialise a CURL session
			$connection = curl_init();
			// set the server we are using (could be Sandbox or Production server)
			curl_setopt( $connection, CURLOPT_URL, $this->marketingURL . '/' . $endpoint );

			// stop CURL from verifying the peer's certificate
			curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
			curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, 0 );

			if ( 'POST_GET_HEADER_STATUS' == $curlRequestType ) {
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $connection, CURLOPT_VERBOSE, 1 );
				curl_setopt( $connection, CURLOPT_HEADER, 1 );
				curl_setopt( $connection, CURLOPT_POST, 1 );
				curl_setopt( $connection, CURLOPT_POSTFIELDS, '' );
				$response    = curl_exec( $connection );
				$header_size = curl_getinfo( $connection, CURLINFO_HEADER_SIZE );
				$header      = substr( $response, 0, $header_size );
				$body        = substr( $response, $header_size );
				$httpcode    = curl_getinfo( $connection, CURLINFO_HTTP_CODE );
				curl_close( $connection );
				return $httpcode;
			} else {
				curl_setopt( $connection, CURLOPT_HTTPHEADER, $headers );

				// set method as GET
				curl_setopt( $connection, CURLOPT_HTTPGET, 1 );

				// set it to return the transfer as a string from curl_exec
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );

				// Send the Request
				$response = curl_exec( $connection );

				curl_close( $connection );

				return $response;
			}
		}

		public function sendHttpRequestForTaxonomyAPI( $endpoint, $token, $curlRequestType = '' ) {
			// build eBay headers using variables passed via constructor
			$headers    = $this->buildMarketingHeaders( $token );
			$connection = curl_init();
			// set the server we are using (could be Sandbox or Production server)
			curl_setopt( $connection, CURLOPT_URL, $this->taxonomyURL . '/' . $endpoint );

			// stop CURL from verifying the peer's certificate
			curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
			curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, 0 );

			if ( 'POST_GET_HEADER_STATUS' == $curlRequestType ) {
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $connection, CURLOPT_VERBOSE, 1 );
				curl_setopt( $connection, CURLOPT_HEADER, 1 );
				curl_setopt( $connection, CURLOPT_POST, 1 );
				curl_setopt( $connection, CURLOPT_POSTFIELDS, '' );
				$response    = curl_exec( $connection );
				$header_size = curl_getinfo( $connection, CURLINFO_HEADER_SIZE );
				$header      = substr( $response, 0, $header_size );
				$body        = substr( $response, $header_size );
				$httpcode    = curl_getinfo( $connection, CURLINFO_HTTP_CODE );
				curl_close( $connection );
				return $httpcode;
			} else {
				curl_setopt( $connection, CURLOPT_HTTPHEADER, $headers );

				// set method as GET
				curl_setopt( $connection, CURLOPT_HTTPGET, 1 );

				// set it to return the transfer as a string from curl_exec
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );

				// Send the Request
				$response = curl_exec( $connection );

				curl_close( $connection );

				return $response;
			}

			// set the headers using the array of headers
		}

		public function sendHttpRequestForAccountAPI( $endpoint, $token, $curlRequestType = '' ) {
			// build eBay headers using variables passed via constructor
			if ( 'advertising_eligibility' == $endpoint ) {
				if ( is_array( $this->ebaySites ) && ! empty( $this->ebaySites ) ) {
					foreach ( $this->ebaySites as $site ) {
						if ( $this->siteID == $site['siteID'] ) {
							if ( ! empty( $site['countrycode'] ) ) {
								$ebay_marketplace_id = $site['countrycode'];
								break;
							}
						}
					}
				}
				$headers = array(
					'Authorization:Bearer ' . $token,
					'Accept:application/json',
					'Content-Type:application/json',
					'X-EBAY-C-MARKETPLACE-ID:EBAY_' . $ebay_marketplace_id,
				);
			} else {
				$headers = array(
					'Authorization:Bearer ' . $token,
					'Accept:application/json',
					'Content-Type:application/json',
				);
			}
			$connection = curl_init();
			// set the server we are using (could be Sandbox or Production server)
			curl_setopt( $connection, CURLOPT_URL, $this->accountUrl . $endpoint );

			// stop CURL from verifying the peer's certificate
			curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
			curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, 0 );

			if ( 'POST_GET_HEADER_STATUS' == $curlRequestType ) {
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $connection, CURLOPT_VERBOSE, 1 );
				curl_setopt( $connection, CURLOPT_HEADER, 1 );
				curl_setopt( $connection, CURLOPT_POST, 1 );
				curl_setopt( $connection, CURLOPT_POSTFIELDS, '' );
				$response    = curl_exec( $connection );
				$header_size = curl_getinfo( $connection, CURLINFO_HEADER_SIZE );
				$header      = substr( $response, 0, $header_size );
				$body        = substr( $response, $header_size );
				$httpcode    = curl_getinfo( $connection, CURLINFO_HTTP_CODE );
				curl_close( $connection );
				return $httpcode;
			} else {
				curl_setopt( $connection, CURLOPT_HTTPHEADER, $headers );

				// set method as GET
				curl_setopt( $connection, CURLOPT_HTTPGET, 1 );

				// set it to return the transfer as a string from curl_exec
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );

				// Send the Request
				$response = curl_exec( $connection );

				curl_close( $connection );

				return $response;
			}

			// set the headers using the array of headers
		}


		public function sendHttpRequestForFeedAPI( $endpoint, $token, $curlRequestType = '', $requestBody = '' ) {
			// build eBay headers using variables passed via constructor
			// $headers = $this->buildMarketingHeaders( $token );

			if ( is_array( $this->ebaySites ) && ! empty( $this->ebaySites ) ) {
				foreach ( $this->ebaySites as $site ) {
					if ( $this->siteID == $site['siteID'] ) {
						if ( ! empty( $site['countrycode'] ) ) {
							$ebay_marketplace_id = $site['countrycode'];
							break;
						} else {
							return 'Marketplace ID is empty for the selected eBay Site!';
						}
					}
				}
			}
			$headers = array(
				'Authorization: Bearer ' . $token,
				'Content-Type: application/json',
				'X-EBAY-C-MARKETPLACE-ID: EBAY_' . $ebay_marketplace_id,
			);

			// initialise a CURL session
			$connection = curl_init();
			// set the server we are using (could be Sandbox or Production server)

			// stop CURL from verifying the peer's certificate
			curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
			curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, 0 );
			if ( 'POST_GET_HEADER' == $curlRequestType ) {
				curl_setopt( $connection, CURLOPT_URL, $this->feedApiUrl . $endpoint );
				curl_setopt( $connection, CURLOPT_HTTPHEADER, $headers );
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $connection, CURLOPT_VERBOSE, 1 );
				curl_setopt( $connection, CURLOPT_HEADER, 1 );
				curl_setopt( $connection, CURLOPT_POST, 1 );
				curl_setopt( $connection, CURLOPT_POSTFIELDS, $requestBody );
				$response    = curl_exec( $connection );
				$header_size = curl_getinfo( $connection, CURLINFO_HEADER_SIZE );
				$body        = substr( $response, $header_size );
				// return json_decode($body, true);
				$response_headers = substr( $response, 0, $header_size );
				$header_array     = $this->ced_ebay_headersToArray( $response_headers );
				curl_close( $connection );
				return array(
					'body'    => $body,
					'headers' => $header_array,
				);
			} elseif ( 'DOWNLOAD_FILE' == $curlRequestType ) {
				$wp_folder     = wp_upload_dir();
				$wp_upload_dir = $wp_folder['basedir'];
				$wp_upload_dir = $wp_upload_dir . '/ced-ebay/inventory_report.zip';
				if ( file_exists( $wp_upload_dir ) ) {
					wp_delete_file( $wp_upload_dir );
				}
				$response = wp_remote_get(
					$endpoint,
					array(
						'headers'  => array(
							'Authorization' => 'Bearer ' . $token,
						),
						'stream'   => true,
						'filename' => $wp_upload_dir,
					)
				);
				// return $response;
				if ( ( ! is_wp_error( $response ) ) && ( 200 === wp_remote_retrieve_response_code( $response ) ) ) {
					return true;
				} else {
					return false;
				}
			} else {
				curl_setopt( $connection, CURLOPT_URL, $endpoint );
				curl_setopt( $connection, CURLOPT_HTTPHEADER, $headers );

				// set method as GET
				curl_setopt( $connection, CURLOPT_HTTPGET, 1 );

				// set it to return the transfer as a string from curl_exec
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );

				// Send the Request
				$response = curl_exec( $connection );

				curl_close( $connection );

				return $response;
			}

			// set the headers using the array of headers
		}

		public function sendHttpRequestForFulfillmentAPI( $endpoint, $token, $curlRequestType = '', $requestBody = '' ) {
			// build eBay headers using variables passed via constructor
			$headers = $this->buildMarketingHeaders( $token );
			// initialise a CURL session
			$connection = curl_init();
			// set the server we are using (could be Sandbox or Production server)
			curl_setopt( $connection, CURLOPT_URL, $this->fulfillmentURL . $endpoint );

			// stop CURL from verifying the peer's certificate
			curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
			curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, 0 );
			if ( 'POST_GET_HEADER_STATUS' == $curlRequestType ) {
				curl_setopt( $connection, CURLOPT_HTTPHEADER, $headers );
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $connection, CURLOPT_VERBOSE, 1 );
				curl_setopt( $connection, CURLOPT_HEADER, 1 );
				curl_setopt( $connection, CURLOPT_POST, 1 );
				curl_setopt( $connection, CURLOPT_POSTFIELDS, $requestBody );
				$response    = curl_exec( $connection );
				$header_size = curl_getinfo( $connection, CURLINFO_HEADER_SIZE );
				$header      = substr( $response, 0, $header_size );
				$body        = substr( $response, $header_size );
				$httpcode    = curl_getinfo( $connection, CURLINFO_HTTP_CODE );
				curl_close( $connection );
				return $httpcode;
			} else {
				curl_setopt( $connection, CURLOPT_HTTPHEADER, $headers );

				// set method as GET
				curl_setopt( $connection, CURLOPT_HTTPGET, 1 );

				// set it to return the transfer as a string from curl_exec
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );

				// Send the Request
				$response = curl_exec( $connection );

				curl_close( $connection );

				return $response;
			}

			// set the headers using the array of headers
		}

		public function sendHttpRequestForIdentityAPI( $endpoint, $token, $curlRequestType = '', $requestBody = '' ) {
			// build eBay headers using variables passed via constructor
			$headers = $this->buildMarketingHeaders( $token );
			// initialise a CURL session
			$connection = curl_init();
			// set the server we are using (could be Sandbox or Production server)
			curl_setopt( $connection, CURLOPT_URL, $this->identityUrl . $endpoint );

			// stop CURL from verifying the peer's certificate
			curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
			curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, 0 );
			if ( 'POST_GET_HEADER_STATUS' == $curlRequestType ) {
				curl_setopt( $connection, CURLOPT_HTTPHEADER, $headers );
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $connection, CURLOPT_VERBOSE, 1 );
				curl_setopt( $connection, CURLOPT_HEADER, 1 );
				curl_setopt( $connection, CURLOPT_POST, 1 );
				curl_setopt( $connection, CURLOPT_POSTFIELDS, $requestBody );
				$response    = curl_exec( $connection );
				$header_size = curl_getinfo( $connection, CURLINFO_HEADER_SIZE );
				$header      = substr( $response, 0, $header_size );
				$body        = substr( $response, $header_size );
				$httpcode    = curl_getinfo( $connection, CURLINFO_HTTP_CODE );
				curl_close( $connection );
				return $httpcode;
			} else {
				curl_setopt( $connection, CURLOPT_HTTPHEADER, $headers );

				// set method as GET
				curl_setopt( $connection, CURLOPT_HTTPGET, 1 );

				// set it to return the transfer as a string from curl_exec
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );

				// Send the Request
				$response = curl_exec( $connection );

				curl_close( $connection );

				return $response;
			}

			// set the headers using the array of headers
		}

		public function sendHttpRequestForPostOrdersAPI( $endpoint, $token, $curlRequestType = '' ) {
			// build eBay headers using variables passed via constructor
			if ( is_array( $this->ebaySites ) && ! empty( $this->ebaySites ) ) {
				foreach ( $this->ebaySites as $site ) {
					if ( $this->siteID == $site['siteID'] ) {
						if ( ! empty( $site['countrycode'] ) ) {
							$ebay_marketplace_id = $site['countrycode'];
							break;
						} else {
							return 'Marketplace ID is empty for the selected eBay Site!';
						}
					}
				}
			}
			$headers = array(
				'Authorization: IAF ' . $token,
				'Content-Type: application/json',
				'X-EBAY-C-MARKETPLACE-ID: EBAY_' . $ebay_marketplace_id,
			);
			// initialise a CURL session
			$connection = curl_init();
			// set the server we are using (could be Sandbox or Production server)
			curl_setopt( $connection, CURLOPT_URL, $this->postOrderURL . $endpoint );

			// stop CURL from verifying the peer's certificate
			curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
			curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, 0 );

			if ( 'POST_GET_HEADER_STATUS' == $curlRequestType ) {
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $connection, CURLOPT_VERBOSE, 1 );
				curl_setopt( $connection, CURLOPT_HEADER, 1 );
				curl_setopt( $connection, CURLOPT_POST, 1 );
				curl_setopt( $connection, CURLOPT_POSTFIELDS, '' );
				$response    = curl_exec( $connection );
				$header_size = curl_getinfo( $connection, CURLINFO_HEADER_SIZE );
				$header      = substr( $response, 0, $header_size );
				$body        = substr( $response, $header_size );
				$httpcode    = curl_getinfo( $connection, CURLINFO_HTTP_CODE );
				curl_close( $connection );
				return $httpcode;
			} else {
				curl_setopt( $connection, CURLOPT_HTTPHEADER, $headers );

				// set method as GET
				curl_setopt( $connection, CURLOPT_HTTPGET, 1 );

				// set it to return the transfer as a string from curl_exec
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );

				// Send the Request
				$response = curl_exec( $connection );

				curl_close( $connection );

				return $response;
			}

			// set the headers using the array of headers
		}




		public function buildMarketingHeaders( $token ) {
			$headers = array(
				'Authorization:Bearer ' . $token,
				'Accept:application/json',
				'Content-Type:application/json',
			);

			return $headers;
		}

		public function loadDependency() {
			if ( is_file( __DIR__ . '/ebayConfig.php' ) ) {
				require_once 'ebayConfig.php';
				$this->ebayConfigInstance = Ced_Ebay_WooCommerce_Core\Ebayconfig::get_instance();
			}
		}

		public function ced_ebay_headersToArray( $str ) {
			$headers         = array();
			$headersTmpArray = explode( "\r\n", $str );
			for ( $i = 0; $i < count( $headersTmpArray ); ++$i ) {
				// we dont care about the two \r\n lines at the end of the headers
				if ( strlen( $headersTmpArray[ $i ] ) > 0 ) {
					// the headers start with HTTP status codes, which do not contain a colon so we can filter them out too
					if ( strpos( $headersTmpArray[ $i ], ':' ) ) {
						$headerName             = substr( $headersTmpArray[ $i ], 0, strpos( $headersTmpArray[ $i ], ':' ) );
						$headerValue            = substr( $headersTmpArray[ $i ], strpos( $headersTmpArray[ $i ], ':' ) + 1 );
						$headers[ $headerName ] = $headerValue;
					}
				}
			}
			return $headers;
		}
	}
}
