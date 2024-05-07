<?php

defined( 'ABSPATH' ) || die( 'Direct access not allowed' );

class Sync_Business_Policies_Async extends WP_Async_Request {
	protected $prefix = 'ced-ebay';

	protected $action = 'ced-ebay-sync-business-policies';

	protected function handle() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( ! $check_ajax ) {
			wp_send_json_error( array( 'message' => 'Nonce check failed!' ) );
		}
		$user_id   = isset( $_POST['user_id'] ) ? sanitize_text_field( $_POST['user_id'] ) : '';
		$site_id   = isset( $_POST['site_id'] ) ? sanitize_text_field( $_POST['site_id'] ) : '';
		$action    = isset( $_POST['perform-action'] ) ? sanitize_text_field( $_POST['perform-action'] ) : '';
		$shop_data = ced_ebay_get_shop_data( $user_id, $site_id );
		if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
			$token = $shop_data['access_token'];
			if ( empty( $token ) ) {
				return;
			}
			if ( 'sync-policy' == $action || '' == $action ) {
				$business_polices = ced_ebay_get_business_policies( $user_id, $site_id );
			} elseif ( 'fetch-policy' == $action ) {
				$api_endpoint = isset( $_POST['api-endpoint'] ) ? sanitize_text_field( $_POST['api-endpoint'] ) : '';
				$policy_id    = isset( $_POST['policy-id'] ) ? sanitize_text_field( $_POST['policy-id'] ) : '';
				if ( empty( $api_endpoint ) || empty( $policy_id ) ) {
					return false;
				}
				if ( ! file_exists( CED_EBAY_DIRPATH . 'admin/ebay/lib/cedMarketingRequest.php' ) ) {
					return false;
				}
				require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/cedMarketingRequest.php';
				$accountApiRequest = new Ced_Marketing_API_Request( $site_id );
				$getPolicyResponse = $accountApiRequest->sendHttpRequestForAccountAPI( $api_endpoint . '/' . $policy_id, $token );
				// wc_get_logger()->info(wc_print_r($getPolicyDetails, true));
				if ( ! is_array( json_decode( $getPolicyResponse, true ) ) ) {
					return false;
				}
				$getPolicyDetails = json_decode( $getPolicyResponse, true );
				if ( ! empty( $getPolicyDetails ) ) {
					update_option( 'ced_ebay_business_policy_details_' . $user_id . '>' . $site_id, $getPolicyDetails );
				}
			} else {
				return false;
			}
		}
	}
}
