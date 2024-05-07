<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}


function ced_ebay_get_options_for_dropdown() {
	$getebayDropdownFromTransient = get_transient( 'ced_ebay_dropdown_options' );
	if ( false === $getebayDropdownFromTransient || null == $getebayDropdownFromTransient || empty( $getebayDropdownFromTransient ) ) {

		$selectDropdownHTML = '';
		$attrOptions        = array();
		global $wpdb;
		$results = $wpdb->get_results( "SELECT DISTINCT meta_key FROM {$wpdb->prefix}postmeta WHERE meta_key NOT LIKE '%wcf%' AND meta_key NOT LIKE '%elementor%' AND meta_key NOT LIKE '%_menu%'", 'ARRAY_A' );
		foreach ( $results as $key => $meta_key ) {
			$post_meta_keys[] = $meta_key['meta_key'];
		}
		$custom_prd_attrb = array();
		$query            = $wpdb->get_results( $wpdb->prepare( "SELECT `meta_value` FROM  {$wpdb->prefix}postmeta WHERE `meta_key` LIKE %s", '_product_attributes' ), 'ARRAY_A' );
		if ( ! empty( $query ) ) {
			foreach ( $query as $key => $db_attribute_pair ) {
				foreach ( maybe_unserialize( $db_attribute_pair['meta_value'] ) as $key => $attribute_pair ) {
					if ( 1 != $attribute_pair['is_taxonomy'] ) {
						$custom_prd_attrb[] = $attribute_pair['name'];
					}
				}
			}
		}
		$attributes = wc_get_attribute_taxonomies();

		if ( ! empty( $attributes ) ) {
			foreach ( $attributes as $attributesObject ) {
				$attrOptions[ 'umb_pattr_' . $attributesObject->attribute_name ] = $attributesObject->attribute_label;
			}
		}
		/* select dropdown setup */
		ob_start();

		$selectDropdownHTML .= '<option value="">Select</option>';
		$selectDropdownHTML .= '<option value="ced_product_tags">Product Tags</option>';
		$selectDropdownHTML .= '<option value="ced_product_cat_single">Product Category - Last Category</option>';
		$selectDropdownHTML .= '<option value="ced_product_cat_hierarchy">Product Category - Hierarchy</option>';

		if ( class_exists( 'ACF' ) ) {
			$acf_fields_posts = get_posts(
				array(
					'posts_per_page' => -1,
					'post_type'      => 'acf-field',
				)
			);

			foreach ( $acf_fields_posts as $key => $acf_posts ) {
				$acf_fields[ $key ]['field_name'] = $acf_posts->post_title;
				$acf_fields[ $key ]['field_key']  = $acf_posts->post_name;
			}
		}
		if ( is_array( $attrOptions ) ) {
			$selectDropdownHTML .= '<optgroup label="Global Attributes">';
			foreach ( $attrOptions as $attrKey => $attrName ) :
				$selectDropdownHTML .= '<option value="' . $attrKey . '">' . $attrName . '</option>';
			endforeach;
		}

		if ( ! empty( $custom_prd_attrb ) ) {
			$custom_prd_attrb    = array_unique( $custom_prd_attrb );
			$selectDropdownHTML .= '<optgroup label="Custom Attributes">';
			foreach ( $custom_prd_attrb as $key => $custom_attrb ) {
				$selectDropdownHTML .= '<option value="ced_cstm_attrb_' . esc_attr( $custom_attrb ) . '">' . esc_html( $custom_attrb ) . '</option>';
			}
		}

		if ( ! empty( $post_meta_keys ) ) {
			$post_meta_keys      = array_unique( $post_meta_keys );
			$selectDropdownHTML .= '<optgroup label="Custom Fields">';
			foreach ( $post_meta_keys as $key => $p_meta_key ) {
				$selectDropdownHTML .= '<option value="' . $p_meta_key . '">' . $p_meta_key . '</option>';
			}
		}

		if ( ! empty( $acf_fields ) ) {
			$selectDropdownHTML .= '<optgroup label="ACF Fields">';
			foreach ( $acf_fields as $key => $acf_field ) :
				$selectDropdownHTML .= '<option value="acf_' . $acf_field['field_key'] . '">' . $acf_field['field_name'] . '</option>';
			endforeach;
		}
		set_transient( 'ced_ebay_dropdown_options', $selectDropdownHTML, 4 * HOUR_IN_SECONDS );

		return $selectDropdownHTML;

	} else {
		return $getebayDropdownFromTransient;
	}
}

function ced_ebay_get_site_details( $site_id ) {
	if ( file_exists( CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayConfig.php' ) ) {
		require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayConfig.php';
		$ebayConfig         = new \Ced\Ebay\Ebayconfig();
		$ebayConfigInstance = $ebayConfig->get_instance();
		$ebaySiteDetails    = $ebayConfig->getEbaycountrDetail( $site_id );
		if ( ! empty( $ebaySiteDetails ) && is_array( $ebaySiteDetails ) ) {
			return $ebaySiteDetails;
		} else {
			return false;
		}
	}
}

function ced_ebay_get_site_using_marketplace_enum( $marketplace_enum ) {
	if ( file_exists( CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayConfig.php' ) ) {
		require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayConfig.php';
		$ebayConfig         = new \Ced\Ebay\Ebayconfig();
		$ebayConfigInstance = $ebayConfig->get_instance();
		$ebaySiteDetails    = $ebayConfig->getSiteIdUsingMarketplaceEnum( $marketplace_enum );
		if ( ! empty( $ebaySiteDetails ) && is_array( $ebaySiteDetails ) ) {
			return $ebaySiteDetails;
		} else {
			return false;
		}
	}
}
function ced_ebay_get_shop_data( $user_id = '', $site_id = '' ) {
	if(!empty(get_option('ced_ebay_user_shop_details'))){
		$shop_data = get_option( 'ced_ebay_user_shop_details', true );
		if ( ! empty( $shop_data[ $user_id ][$site_id] ) ){
            return $shop_data[$user_id][$site_id];
		} else {
			return false;
		}
	}
}

function ced_ebay_get_details_using_rsid($rsid){
	if(!empty(get_option('ced_ebay_user_shop_details'))){
		$shop_data = get_option( 'ced_ebay_user_shop_details', true );
		foreach($shop_data as $user_id => $shops)
			foreach($shops as $site_id => $rs){
				if(isset($shop_data[$user_id][$site_id]) && $rsid == $rs['remote_shop_id']){
					return array('user_id' => $user_id, 'site_id' => $site_id);
				}
		}
			
		}
}


function ced_ebay_get_business_policies( $user_id, $siteID, $rsid ) {
		if ( '' !== $siteID && ! empty( $user_id ) && !empty($rsid) ) {
			$apiResponse = [];
			$getBusinessPoliciesFromTransient = get_transient( 'ced_ebay_business_policies_' . $user_id . '>' . $siteID );
			if ( false === $getBusinessPoliciesFromTransient || null == $getBusinessPoliciesFromTransient || empty( $getBusinessPoliciesFromTransient ) ) {
				$business_policies        = array();
				$configInstance           = \Ced\Ebay\Ebayconfig::get_instance();
				$countryDetails           = $configInstance->getEbaycountrDetail( $siteID );
				$country_code             = $countryDetails['countrycode'];
				$marketplace_enum         = 'EBAY_' . $country_code;
				// $account_payment_policies = $accountRequest->sendHttpRequestForAccountAPI( 'payment_policy?marketplace_id=' . $marketplace_enum, $token );
				// $account_payment_policies = json_decode( $account_payment_policies, true );
				$apiClient = new \Ced\Ebay\CED_EBAY_API_Client();
				$apiClient->setJwtToken('abc');
				$apiClient->setRequestTopic('seller-profiles');
				$apiClient->setRequestRemoteMethod('GET');
				$apiClient->setRequestRemoteQueryParams([
					'marketplace_id' => $marketplace_enum,
					'new_api' => true,
					'shop_id' => $rsid,
					'type' => 'GetPaymentPolicy'
				]);
				$apiResponse = $apiClient->post();
				if(isset($apiResponse['data'])){
					$account_payment_policies = json_decode($apiResponse['data'], true);
				} else {
					if(isset($apiResponse['error_code'])){
						return $apiResponse;
					} else {
						return false;
					}
				}
				if ( isset( $account_payment_policies['total'] ) && $account_payment_policies['total'] > 0 ) {
					$business_policies['paymentPolicies'] = $account_payment_policies;
				}
				
				$apiClient->setRequestTopic('seller-profiles');
				$apiClient->setRequestRemoteMethod('GET');
				$apiClient->setRequestRemoteQueryParams([
					'marketplace_id' => $marketplace_enum,
					'new_api' => true,
					'shop_id' => $rsid,
					'type' => 'GetReturnPolicy'
				]);
				$apiResponse = $apiClient->post();
				if(isset($apiResponse['data'])){
					$account_return_policies = json_decode($apiResponse['data'], true);
				} else {
					if(isset($apiResponse['error_code'])){
						return $apiResponse;
					} else {
						return false;
					}
				}
				if ( isset( $account_return_policies['total'] ) && $account_return_policies['total'] > 0 ) {
					$business_policies['returnPolicies'] = $account_return_policies;
				}
				
				$apiClient->setRequestTopic('seller-profiles');
				$apiClient->setRequestRemoteMethod('GET');
				$apiClient->setRequestRemoteQueryParams([
					'marketplace_id' => $marketplace_enum,
					'new_api' => true,
					'shop_id' => $rsid,
					'type' => 'GetShippingPolicy'
				]);
				$apiResponse = $apiClient->post();
				if(isset($apiResponse['data'])){
					$account_shipping_policies = json_decode($apiResponse['data'], true);
				} else {
					if(isset($apiResponse['error_code'])){
						return $apiResponse;
					} else {
						return false;
					}
				}
				if ( isset( $account_shipping_policies['total'] ) && $account_shipping_policies['total'] > 0 ) {
					$business_policies['fulfillmentPolicies'] = $account_shipping_policies;
				}
				if(isset($business_policies['fulfillmentPolicies']) && isset($business_policies['returnPolicies']) && isset($business_policies['paymentPolicies'])){
					set_transient( 'ced_ebay_business_policies_' . $user_id . '>' . $siteID, $business_policies, 2 * HOUR_IN_SECONDS );
				}
				return $business_policies;
			} else {
				return $getBusinessPoliciesFromTransient;
			}
		}
	
}



function ced_ebay_test_cron_spawn( $cache = true ) {
	global $wp_version;

	if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
		return new WP_Error(
			'crontrol_info',
			sprintf(
				__( 'WP-Cron spawning is disabled.', 'ebay-integration-for-woocommerce' ),
				'DISABLE_WP_CRON'
			)
		);
	}

	if ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) {
		return new WP_Error(
			'crontrol_info',
			sprintf(
			/* translators: 1: The name of the PHP constant that is set. */
				__( 'The %s constant is set to true.', 'wp-crontrol' ),
				'ALTERNATE_WP_CRON'
			)
		);
	}

	$cached_status = get_transient( 'crontrol-cron-test-ok' );

	if ( $cache && $cached_status ) {
		return true;
	}

	$sslverify     = version_compare( $wp_version, '4.0', '<' );
	$doing_wp_cron = sprintf( '%.22F', microtime( true ) );

	/**
										 * Cron request
										 *
										 * @since 1.0.0
										 */
	$cron_request = apply_filters(
		'cron_request',
		array(
			'url'  => add_query_arg( 'doing_wp_cron', $doing_wp_cron, site_url( 'wp-cron.php' ) ),
			'key'  => $doing_wp_cron,
			'args' => array(
				'timeout'   => 3,
				'blocking'  => true,
				/**
										 * Verify ssl
										 *
										 * @since 1.0.0
										 */
				'sslverify' => apply_filters( 'https_local_ssl_verify', $sslverify ),
			),
		),
		$doing_wp_cron
	);

	$cron_request['args']['blocking'] = true;

	$result = wp_remote_post( $cron_request['url'], $cron_request['args'] );

	if ( is_wp_error( $result ) ) {
		return $result;
	} elseif ( wp_remote_retrieve_response_code( $result ) >= 300 ) {
		return new WP_Error(
			'unexpected_http_response_code',
			sprintf(
			/* translators: 1: The HTTP response code. */
				__( 'Unexpected HTTP response code: %s', 'wp-crontrol' ),
				intval( wp_remote_retrieve_response_code( $result ) )
			)
		);
	} else {
		set_transient( 'crontrol-cron-test-ok', 1, 3600 );
		return true;
	}
}



function ced_ebay_time_elapsed_string( $datetime, $full = false, $is_next_run = false ) {
	$now  = new DateTime();
	$ago  = new DateTime( $datetime );
	$diff = $now->diff( $ago );

	$diff->w  = floor( $diff->d / 7 );
	$diff->d -= $diff->w * 7;

	$string = array(
		'y' => 'year',
		'm' => 'month',
		'w' => 'week',
		'd' => 'day',
		'h' => 'hour',
		'i' => 'minute',
		's' => 'second',
	);
	foreach ( $string as $k => &$v ) {
		if ( $diff->$k ) {
			$v = $diff->$k . ' ' . $v . ( $diff->$k > 1 ? 's' : '' );
		} else {
			unset( $string[ $k ] );
		}
	}

	if ( ! $full ) {
		$string = array_slice( $string, 0, 1 );
	}
	if ( ! $is_next_run ) {
		return $string ? implode( ', ', $string ) . ' ago' : 'just now';
	} else {
		return $string ? implode( ', ', $string ) : 'just now';

	}
}

function ced_ebay_log_data( $message, $log_name, $log_file = '' ) {
	$log = new WC_Logger();
	if ( is_array( $message ) ) {
		$message = print_r( $message, true );
	} elseif ( is_object( $message ) ) {
		$ob_get_length = ob_get_length();
		if ( ! $ob_get_length ) {
			if ( false === $ob_get_length ) {
				ob_start();
			}
			var_dump( $message );
			$message = ob_get_contents();
			if ( false === $ob_get_length ) {
				ob_end_clean();
			} else {
				ob_clean();
			}
		} else {
			$message = '(' . get_class( $message ) . ' Object)';
		}
	}
	$log->add( $log_name, $message );
	if ( ! empty( $log_file ) ) {
		if ( file_exists( $log_file ) ) {
			file_put_contents( $log_file, PHP_EOL . $message, FILE_APPEND );
		} else {
			return;
		}
	}
}


function ced_ebay_create_zip( $files = array(), $destination = '', $overwrite = false ) {
	if ( file_exists( $destination ) && ! $overwrite ) {
		return false;
	}

	if ( ! class_exists( 'ZipArchive' ) ) {
		return false;
	}

	$valid_files = array();

	if ( is_array( $files ) ) {

		foreach ( $files as $file ) {

			if ( file_exists( $file['path'] ) ) {

				$valid_files[] = $file;
			}
		}
	}

	if ( count( $valid_files ) ) {
		$zip = new ZipArchive();

		if ( $zip->open( $destination, $overwrite ? ZipArchive::OVERWRITE : ZipArchive::CREATE ) !== true ) {
			return false;
		}

		foreach ( $valid_files as $file ) {

			$zip->addFile( $file['path'], basename( $file['path'] ) );
		}

		$zip->close();

		return file_exists( $destination );

	} else {
		return false;
	}
}

if ( ! function_exists( 'ced_get_navigation_url' ) ) {
	function ced_get_navigation_url( $channel = 'home', $query_args = array() ) {
		if ( ! empty( $query_args ) ) {
			return admin_url( 'admin.php?page=sales_channel&channel=' . $channel . '&' . http_build_query( $query_args ) );

		}
		if ( ! empty( get_option( 'ced_ebay_active_user_url' ) ) ) {
			return get_option( 'ced_ebay_active_user_url', true );
		} else {
			return admin_url( 'admin.php?page=sales_channel&channel=' . $channel );
		}
	}
}

function ced_eBay_categories_tree( $value, $cat_name ) {
	if ( 0 != $value->parent ) {
		$parent_id = $value->parent;
		$sbcatch2  = get_term( $parent_id );
		$cat_name  = $sbcatch2->name . ' --> ' . $cat_name;
		if ( 0 != $sbcatch2->parent ) {
			$cat_name = ced_eBay_categories_tree( $sbcatch2, $cat_name );
		}
	}
	return $cat_name;
}
