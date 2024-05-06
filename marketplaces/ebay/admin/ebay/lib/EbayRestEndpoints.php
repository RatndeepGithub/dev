<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Ebay\DigitalSignature\Signature as DigitalSignature;

class CedCommerce_Ebay_Rest_API_Endpoints extends WP_REST_Controller {
	private $api_namespace;
	private $api_version;
	private $required_capability;

	private $ebay_config_instance;

	public function __construct() {
		$this->api_namespace       = 'ced-ebay-woo/';
		$this->api_version         = 'v1';
		$this->required_capability = 'read';  // Minimum capability to use the endpoint
		$this->ced_ebay_init_rest_endpoints();
	}
	// Register our REST Server
	public function ced_ebay_init_rest_endpoints() {
		add_action( 'rest_api_init', array( $this, 'ced_ebay_register_routes' ) );
	}
	public function ced_ebay_register_routes() {
		$namespace = $this->api_namespace . $this->api_version;

		register_rest_route(
			$namespace,
			'/ebay/listing',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'ced_ebay_get_ebay_listing_data' ),
					'permission_callback' => array( $this, 'ced_ebay_permission_check' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/ebay/listings',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'ced_ebay_get_ebay_listings_data' ),
					'permission_callback' => array( $this, 'ced_ebay_permission_check' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/ebay/listings/import',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'ced_ebay_import_listing_in_woocommerce' ),
					'permission_callback' => array( $this, 'ced_ebay_permission_check' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/ebay/order',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'ced_ebay_get_single_ebay_order_data' ),
					'permission_callback' => array( $this, 'ced_ebay_permission_check' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/ebay/profiles',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'ced_ebay_get_all_listing_profiles' ),
					'permission_callback' => array( $this, 'ced_ebay_permission_check' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/ebay/item-aspects',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'ced_ebay_fetch_cat_item_aspects' ),
					'permission_callback' => array( $this, 'ced_ebay_permission_check' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/ebay/store-data',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'ced_ebay_fetch_store_data' ),
					'permission_callback' => array( $this, 'ced_ebay_permission_check' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/ebay/seller-data',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'ced_ebay_fetch_seller_data' ),
					'permission_callback' => array( $this, 'ced_ebay_permission_check' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/ebay/get-inventory-report',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'ced_ebay_get_inventory_report_file' ),
					'permission_callback' => array( $this, 'ced_ebay_permission_check' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/ebay/inventory-report/sync-products',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'ced_ebay_inventory_report_file_based_syncing' ),
					'permission_callback' => array( $this, 'ced_ebay_permission_check' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/ebay/get-wc-logs',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'ced_ebay_get_wc_logs' ),
					'permission_callback' => array( $this, 'ced_ebay_permission_check' ),
				),
			)
		);

		// register_rest_route(
		// $namespace,
		// '/ebay/update_stock_levels_from_orders',
		// array(
		// array(
		// 'methods'             => WP_REST_Server::READABLE,
		// 'callback'            => array( $this, 'ced_ebay_update_stock_levels_from_orders' ),
		// 'permission_callback' => array( $this, 'ced_ebay_permission_check' ),
		// ),
		// )
		// );
	}



	// Check User Is Authorized or Not
	public function ced_ebay_permission_check( $request ) {
		$headers = getallheaders();

		// Get username and password from the submitted headers.
		if ( array_key_exists( 'Authorization', $headers ) || array_key_exists( 'authorization', $headers ) ) {

			// Don't authenticate twice
			if ( ! empty( $user ) ) {
				return true;
			}

			// Check that we're trying to authenticate
			if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) ) {
				return true;
			}

			$username = ! empty( $_SERVER['PHP_AUTH_USER'] ) ? sanitize_text_field( $_SERVER['PHP_AUTH_USER'] ) : '';
			$password = ! empty( $_SERVER['PHP_AUTH_PW'] ) ? sanitize_text_field( $_SERVER['PHP_AUTH_PW'] ) : '';

			/**
			 * In multi-site, wp_authenticate_spam_check filter is run on authentication. This filter calls
			 * get_currentuserinfo which in turn calls the determine_current_user filter. This leads to infinite
			 * recursion and a stack overflow unless the current function is removed from the determine_current_user
			 * filter during authentication.
			 */
			remove_filter( 'determine_current_user', 'json_basic_auth_handler', 20 );

			$user = wp_authenticate( $username, $password );

			add_filter( 'determine_current_user', 'json_basic_auth_handler', 20 );

			if ( is_wp_error( $user ) ) {
				return new WP_Error( 'incorrect_password', 'The password you entered for the username ' . $username . ' is incorrect.', array( 'status' => 401 /* Unauthorized */ ) );
			}

			return true;
		} else {
			return new WP_Error( 'invalid-method', 'You must specify a valid username and password.', array( 'status' => 400 /* Bad Request */ ) );
		}
	}

	public function ced_ebay_get_ebay_listings_data( WP_REST_Request $request ) {

		$json_params = $request->get_json_params();
		if ( ! is_array( $json_params ) || empty( $json_params ) || empty( $json_params['user_id'] ) || empty( $json_params['paged'] ) || ! is_numeric( $json_params['paged'] ) ) {
			return new WP_Error( 'invalid-data', 'The request data is invalid or missing fields. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}

		$entries_per_page = ! empty( $json_params['length'] ) ? $json_params['length'] : 25;

		$ebay_login_data = ced_ebay_get_shop_data( $json_params['user_id'] );
		if ( ! empty( $ebay_login_data ) && is_array( $ebay_login_data ) ) {
			$site_id = $ebay_login_data['site_id'];
			$token   = ! empty( $ebay_login_data['access_token'] ) ? $ebay_login_data['access_token'] : false;

		}

		if ( empty( $token ) ) {
			return new WP_Error( 'invalid-data', 'The supplied user ID is invalid. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}

		require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
		$ebayUploadInstance     = EbayUpload::get_instance( $site_id, $token );
		$get_listings_xml       = '
			<?xml version="1.0" encoding="utf-8"?>
			<GetMyeBaySellingRequest xmlns="urn:ebay:apis:eBLBaseComponents">
			  <RequesterCredentials>
				<eBayAuthToken>' . $token . '</eBayAuthToken>
			  </RequesterCredentials>
			  <ActiveList>
				<Sort>TimeLeft</Sort>
				<Pagination>
				 <EntriesPerPage>' . $entries_per_page . '</EntriesPerPage>
				  <PageNumber>' . $json_params['paged'] . '</PageNumber>
				</Pagination>
			  </ActiveList>
			</GetMyeBaySellingRequest>';
			$ebayUploadInstance = EbayUpload::get_instance( $site_id, $token );
			$activelist         = $ebayUploadInstance->get_active_products( $get_listings_xml );

		return $activelist;
	}

	public function ced_ebay_get_ebay_listing_data( WP_REST_Request $request ) {

		$user_id    = $request->get_param( 'user_id' );
		$listing_id = $request->get_param( 'listing_id' );
		if ( empty( $user_id ) || empty( $listing_id ) ) {
			return new WP_Error( 'invalid-data', 'The request data is invalid or missing fields. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}
		$ebay_login_data = ced_ebay_get_shop_data( $user_id );
		if ( ! empty( $ebay_login_data ) && is_array( $ebay_login_data ) ) {
			$site_id = $ebay_login_data['site_id'];
			$token   = ! empty( $ebay_login_data['access_token'] ) ? $ebay_login_data['access_token'] : false;

		}
		if ( empty( $token ) ) {
			return new WP_Error( 'invalid-data', 'The supplied user ID is invalid. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}

		require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
		$ebayUploadInstance = EbayUpload::get_instance( $site_id, $token );
		$get_listing_xml    = '
			<?xml version="1.0" encoding="utf-8"?>
			<GetItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
			  <RequesterCredentials>
				<eBayAuthToken>' . $token . '</eBayAuthToken>
			  </RequesterCredentials>
			  <DetailLevel>ReturnAll</DetailLevel>
			  <ItemID>' . $listing_id . '</ItemID>
			</GetItemRequest>';
			$listing_data   = $ebayUploadInstance->get_item_details( $get_listing_xml );
			return $listing_data;
	}

	public function ced_ebay_get_single_ebay_order_data( WP_REST_Request $request ) {
		$user_id  = $request->get_param( 'user_id' );
		$order_id = $request->get_param( 'order_id' );
		if ( empty( $user_id ) || empty( $order_id ) ) {
			return new WP_Error( 'invalid-data', 'The request data is invalid or missing fields. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}
		$ebay_login_data = ced_ebay_get_shop_data( $user_id );
		if ( ! empty( $ebay_login_data ) && is_array( $ebay_login_data ) ) {
			$site_id = $ebay_login_data['site_id'];
			$token   = ! empty( $ebay_login_data['access_token'] ) ? $ebay_login_data['access_token'] : false;

		}
		if ( empty( $token ) ) {
			return new WP_Error( 'invalid-data', 'The supplied user ID is invalid. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}
		require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayOrders.php';
			require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/cedMarketingRequest.php';
			$fulfillmentRequest = new Ced_Marketing_API_Request( $site_id );
			$get_order_detail   = $fulfillmentRequest->sendHttpRequestForFulfillmentAPI( '/' . $order_id . '?fieldGroups=TAX_BREAKDOWN', $token, '', '' );
			$get_order_detail   = json_decode( $get_order_detail, true );

		return $get_order_detail;
	}

	public function ced_ebay_get_all_listing_profiles( WP_REST_Request $request ) {
		$user_id = $request->get_param( 'user_id' );
		if ( empty( $user_id ) ) {
			return new WP_Error( 'invalid-data', 'The request data is invalid or missing fields. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}

		global $wpdb;
		$listing_profiles = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_ebay_profiles WHERE `user_id`=%s ORDER BY `id` DESC", $user_id ), 'ARRAY_A' );

		return $listing_profiles;
	}

	public function ced_ebay_fetch_cat_item_aspects( WP_REST_Request $request ) {
		$user_id = $request->get_param( 'user_id' );
		$cat_id  = $request->get_param( 'cat' );
		if ( empty( $user_id ) || empty( $cat_id ) || ! is_numeric( $cat_id ) ) {
			return new WP_Error( 'invalid-data', 'The request data is invalid or missing fields. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}
		$ebay_login_data = ced_ebay_get_shop_data( $user_id );
		if ( ! empty( $ebay_login_data ) && is_array( $ebay_login_data ) ) {
			$site_id = $ebay_login_data['site_id'];
			$token   = ! empty( $ebay_login_data['access_token'] ) ? $ebay_login_data['access_token'] : false;

		}

		require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/cedGetcategories.php';
		$ebayCategoryInstance = CedGetCategories::get_instance( $site_id, $token );
		$categoryAttributes   = $ebayCategoryInstance->_getCatSpecifics( $cat_id );
		return $categoryAttributes;
	}

	public function ced_ebay_fetch_store_data( WP_REST_Request $request ) {
		$user_id = $request->get_param( 'user_id' );
		if ( empty( $user_id ) ) {
			return new WP_Error( 'invalid-data', 'The request data is invalid or missing fields. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}
		$ebay_login_data = ced_ebay_get_shop_data( $user_id );
		if ( ! empty( $ebay_login_data ) && is_array( $ebay_login_data ) ) {
			$site_id = $ebay_login_data['site_id'];
			$token   = ! empty( $ebay_login_data['access_token'] ) ? $ebay_login_data['access_token'] : false;

		}
		require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/cedRequest.php';
		$requestXmlBody = '<?xml version="1.0" encoding="utf-8"?>
		<GetStoreRequest xmlns="urn:ebay:apis:eBLBaseComponents">
			<RequesterCredentials>
				<eBayAuthToken>' . $token . '</eBayAuthToken>
			</RequesterCredentials>
		</GetStoreRequest>';
		$verb           = 'GetStore';
		$cedRequest     = new Cedrequest( $site_id, $verb );
		$response       = $cedRequest->sendHttpRequest( $requestXmlBody );
		return $response;
	}

	public function ced_ebay_fetch_seller_data( WP_REST_Request $request ) {

		$user_id = $request->get_param( 'user_id' );
		if ( empty( $user_id ) ) {
			return new WP_Error( 'invalid-data', 'The request data is invalid or missing fields. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}
		$ebay_login_data = ced_ebay_get_shop_data( $user_id );
		if ( ! empty( $ebay_login_data ) && is_array( $ebay_login_data ) ) {
			$site_id = $ebay_login_data['site_id'];
			$token   = ! empty( $ebay_login_data['access_token'] ) ? $ebay_login_data['access_token'] : false;

		}
		require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayAuthorization.php';
		$cedAuthorization        = new Ebayauthorization();
		$cedAuhorizationInstance = $cedAuthorization->get_instance();
		$userDetails             = $cedAuhorizationInstance->getUserData( $token, $site_id );
		return $userDetails;
	}

	public function ced_ebay_inventory_report_file_based_syncing( WP_REST_Request $request ) {
		$json_params = $request->get_json_params();
		if ( ! is_array( $json_params ) || empty( $json_params ) || empty( $json_params['user_id'] ) ) {
			return new WP_Error( 'invalid-data', 'The request data is invalid or missing fields. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}
		$user_id         = ! empty( $json_params['user_id'] ) ? $json_params['user_id'] : false;
		$ebay_login_data = ced_ebay_get_shop_data( $user_id );
		if ( empty( $ebay_login_data ) ) {
			return new WP_Error( 'invalid-data', 'Invalid user ID supplied', array( 'status' => 400 /* Bad Request */ ) );
		}
		$wp_filesystem = $this->ced_ebay_get_wp_filesystem();
		global $wp_filesystem;
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();
		$destination      = wp_upload_dir();
		$destination_path = $destination['basedir'] . '/ced-ebay/';
		$files            = glob( $destination_path . 'activeinventory-*' );
		if ( is_array( ( $files ) ) && ! empty( $files ) ) {
			usort(
				$files,
				function ( $a, $b ) {
					return filemtime( $b ) - filemtime( $a );
				}
			);
			$latest_file      = $files[0];
			$latest_file_name = basename( $latest_file );
			if ( ! empty( $latest_file_name ) ) {
				if ( class_exists( 'XMLReader' ) ) {
					$reader = new XMLReader();
					$reader->open( $destination_path . $latest_file_name );
					$ebay_listing_ids = array();
					$temp_ebay_list   = array();
					$index_key        = 0;
					while ( $reader->read() ) {
						if ( 'SKUDetails' == $reader->name && XMLReader::ELEMENT == $reader->nodeType ) {
							while ( $reader->read() ) {
								if ( 'ItemID' == $reader->name && XMLReader::ELEMENT === $reader->nodeType ) {
									$reader->read();
									$temp_ebay_list[]                                      = $reader->value;
									$ebay_listing_ids['simple_list']['data'][ $index_key ] = $reader->value;
								}

								if ( 'Variations' === $reader->name && XMLReader::ELEMENT === $reader->nodeType ) {
									$ebay_listing_ids['variable_list']['data'][] = $ebay_listing_ids['simple_list']['data'][ $index_key ];
									unset( $ebay_listing_ids['simple_list']['data'][ $index_key ] );
									$ebay_listing_ids['simple_list']['data'] = array_values( $ebay_listing_ids['simple_list']['data'] );
								}
							}
								++$index_key;

						}
					}
					// return $temp_ebay_list;
					if ( ! empty( $ebay_listing_ids['variable_list']['data'] ) ) {
						$ebay_listing_ids['simple_list']['data']    = array_diff( $temp_ebay_list, $ebay_listing_ids['variable_list']['data'] );
						$ebay_listing_ids['variable_list']['count'] = count( $ebay_listing_ids['variable_list']['data'] );

					} else {
						$ebay_listing_ids['variable_list']['data']  = array();
						$ebay_listing_ids['variable_list']['count'] = 0;
						$ebay_listing_ids['simple_list']['data']    = array_diff( $temp_ebay_list, $ebay_listing_ids['variable_list']['data'] );
						$ebay_listing_ids['simple_list']['count']   = count( $ebay_listing_ids['simple_list']['data'] );

					}
				}

					$ebay_listing_ids['combined']['data']  = array_merge( $ebay_listing_ids['simple_list']['data'], $ebay_listing_ids['variable_list']['data'] );
					$ebay_listing_ids['combined']['count'] = count( $ebay_listing_ids['combined']['data'] );

					$reader->close();
				if ( is_array( $ebay_listing_ids['combined']['data'] ) && ! empty( $ebay_listing_ids['combined']['data'] ) ) {
					$woo_linked_listing_ids = array();
					$ebay_listing_ids_assoc = array_flip( $ebay_listing_ids['combined']['data'] );
					$store_products         = get_posts(
						array(
							'numberposts' => -1,
							'post_type'   => 'product',
							'post_status' => 'publish',
							'fields'      => 'ids',
							'meta_query'  => array(
								array(
									'key'     => '_ced_ebay_listing_id_' . $user_id,
									'compare' => 'EXISTS',
								),
							),
						)
					);
					if ( is_array( $store_products ) && ! empty( $store_products ) ) {
						foreach ( $store_products as $key => $value ) {
							if ( ! empty( get_post_meta( $value, '_ced_ebay_listing_id_' . $user_id, true ) ) ) {
								$woo_linked_listing_ids[ $value ] = get_post_meta( $value, '_ced_ebay_listing_id_' . $user_id, true );
							}
						}
					}

					if ( is_array( $woo_linked_listing_ids ) && ! empty( $woo_linked_listing_ids ) ) {
						$response['filename']         = $latest_file_name;
						$woo_linked_listing_ids_assoc = array_flip( $woo_linked_listing_ids );
						$diff_woo_ebay                = array_diff_key( $woo_linked_listing_ids_assoc, $ebay_listing_ids_assoc );
						$diff_ebay_woo                = array_diff_key( $ebay_listing_ids_assoc, $woo_linked_listing_ids_assoc );
						if ( is_array( $diff_woo_ebay ) && ! empty( $diff_woo_ebay ) ) {
							$response['missing_woo_in_ebay'] = $diff_woo_ebay;
						}
						if ( is_array( $diff_ebay_woo ) && ! empty( $diff_ebay_woo ) ) {
							$response['missing_ebay_in_woo'] = array_keys( $diff_ebay_woo );
						}
						return $response;

					}

					return array(
						'success'     => true,
						'filename'    => $latest_file_name,
						'listing_ids' => $ebay_listing_ids,
					);

				}
			} else {
				return array(
					'success' => false,
					'message' => 'Missing XMLReader',
				);
			}
		}
	}



	public function ced_ebay_get_inventory_report_file( WP_REST_Request $request ) {

		$json_params = $request->get_json_params();

		$allowed_feed_actions = array(
			'create_task',
			'get_task',
		);
		if ( ! is_array( $json_params ) || empty( $json_params ) || empty( $json_params['user_id'] ) || empty( $json_params['feed_action'] ) || ! in_array( $json_params['feed_action'], $allowed_feed_actions ) ) {
			return new WP_Error( 'invalid-data', 'The request data is invalid or missing fields. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}
		$user_id         = ! empty( $json_params['user_id'] ) ? $json_params['user_id'] : false;
		$ebay_login_data = ced_ebay_get_shop_data( $user_id );
		if ( ! empty( $ebay_login_data ) && is_array( $ebay_login_data ) ) {
			$site_id = $ebay_login_data['site_id'];
			$token   = ! empty( $ebay_login_data['access_token'] ) ? $ebay_login_data['access_token'] : false;
		} else {
			return new WP_Error( 'invalid-data', 'Failed to fetch token for the user. Please check the user_id and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}

		if ( file_exists( CED_EBAY_DIRPATH . 'admin/ebay/lib/cedMarketingRequest.php' ) ) {
			require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/cedMarketingRequest.php';
			$feed_api_request_instance = new Ced_Marketing_API_Request( $site_id );
			switch ( $json_params['feed_action'] ) {
				case 'create_task':
					$payload              = array(
						'schemaVersion' => '1.0',
						'feedType'      => 'LMS_ACTIVE_INVENTORY_REPORT',
					);
					$payload              = json_encode( $payload );
					$create_task_response = $feed_api_request_instance->sendHttpRequestForFeedAPI( 'inventory_task', $token, 'POST_GET_HEADER', $payload );
					if ( is_array( $create_task_response['headers'] ) && ! empty( $create_task_response['headers']['location'] ) ) {
						update_option( 'ced_ebay_inventory_report_location_' . $user_id, $create_task_response['headers']['location'] );
						return array(
							'success'  => 'true',
							'location' => $create_task_response['headers']['location'],
						);
					} elseif ( ! empty( $create_task_response['body'] && is_array( json_decode( $create_task_response['body'], true ) ) ) ) {
							return json_decode( $create_task_response['body'], true );
					} else {
						return new WP_Error( 'invalid-response', 'Something went wrong!', array( 'status' => 400 /* Bad Request */ ) );
					}
				case 'get_task':
					if ( ! empty( get_option( 'ced_ebay_inventory_report_location_' . $user_id ) ) ) {
						$endpoint          = trim( get_option( 'ced_ebay_inventory_report_location_' . $user_id, true ) );
						$get_task_response = $feed_api_request_instance->sendHttpRequestForFeedAPI( $endpoint, $token );
						if ( $get_task_response ) {
							$get_task_response = json_decode( $get_task_response, true );
							if ( 'COMPLETED' == $get_task_response['status'] ) {
								$wp_filesystem = $this->ced_ebay_get_wp_filesystem();
								$download_file = $feed_api_request_instance->sendHttpRequestForFeedAPI( $endpoint . '/download_result_file', $token, 'DOWNLOAD_FILE', '' );
								if ( $download_file ) {
									global $wp_filesystem;
									require_once ABSPATH . '/wp-admin/includes/file.php';
									WP_Filesystem();
									$destination      = wp_upload_dir();
									$destination_path = $destination['basedir'] . '/ced-ebay/';
									$unzipfile        = unzip_file( $destination_path . '/inventory_report.zip', $destination_path );
									if ( ! is_wp_error( $unzipfile ) ) {
										return array(
											'success' => true,
											'message' => 'File downloaded successfully!',
										);
									} else {
										return new WP_Error( 'zip-error', 'Failed to download report', array( 'status' => 400 /* Bad Request */ ) );
									}
								} else {
									return new WP_Error( 'api-error', 'Failed to download report.', array( 'status' => 400 /* Bad Request */ ) );
								}
							} else {
								return $get_task_response;
							}
						} else {
							return new WP_Error( 'invalid-response', 'Failed to get API response', array( 'status' => 400 /* Bad Request */ ) );
						}
					} else {
						return new WP_Error( 'invalid-data', 'Failed to get task ID!', array( 'status' => 400 /* Bad Request */ ) );
					}
			}
		}
	}


	public function ced_ebay_get_wc_logs( WP_REST_Request $request ) {
		$keyword     = $request->get_param( 'keyword' );
		$date_suffix = ! empty( $request->get_param( 'date' ) ) ? $request->get_param( 'date' ) : gmdate( 'Y-m-d', time() );
		if ( empty( $keyword ) ) {
			return new WP_Error( 'invalid-data', 'The request data is invalid or missing fields. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}

		if ( class_exists( 'WC_Log_Handler_File' ) ) {
			if ( ! function_exists( 'wp_hash' ) ) {
				return new WP_Error( 'function-not-found', 'Function not found', array( 'status' => 400 /* Bad Request */ ) );
			}
			$log_files     = WC_Log_Handler_File::get_log_files();
			$hash_suffix   = wp_hash( $keyword );
			$log_file_name = sanitize_file_name( implode( '-', array( $keyword, $date_suffix, $hash_suffix ) ) . '.log' );
			if ( in_array( $log_file_name, $log_files ) ) {
				if ( defined( 'WC_LOG_DIR' ) ) {
					$log_file_path = trailingslashit( WC_LOG_DIR ) . $log_file_name;
					if ( file_exists( $log_file_path ) ) {
						header( 'Content-Description: File Transfer' );
						header( 'Content-Type: application/octet-stream' );
						header( 'Content-Disposition: attachment; filename="' . basename( $log_file_path ) . '"' );
						header( 'Expires: 0' );
						header( 'Cache-Control: must-revalidate' );
						header( 'Pragma: public' );
						header( 'Content-Length: ' . filesize( $log_file_path ) );
						readfile( $log_file_path );
						exit;
					} else {
						return new WP_Error( 'log-file-not-found', 'Log file not found.', array( 'status' => 400 /* Bad Request */ ) );

					}
				} else {
					return new WP_Error( 'invalid-constant', 'Invalid constant', array( 'status' => 400 /* Bad Request */ ) );

				}
			} else {
				return new WP_Error( 'invalid-data', 'The request data is invalid or missing fields. Please check date and keyword and try again!', array( 'status' => 400 /* Bad Request */ ) );

			}
		} else {
			return new WP_Error( 'invalid-class', 'Unable to load the log files.', array( 'status' => 400 /* Bad Request */ ) );
		}
	}




	public function ced_ebay_get_wp_filesystem() {
		global $wp_filesystem;

		if ( is_null( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem;
	}
}

$ced_ebay_rest_api_endpoints = new CedCommerce_Ebay_Rest_API_Endpoints();
