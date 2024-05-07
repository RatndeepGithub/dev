<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class Ced_Ebay_Async_Ajax_Handler {

	private $async_background_action;
	public function init() {
		if ( ! class_exists( 'Sync_Business_Policies_Async' ) ) {
			require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/SyncBusinessPoliciesAsync.php';
			$this->async_background_action = new Sync_Business_Policies_Async();
		}
		add_action( 'wp_ajax_ced_ebay_async_ajax_handler', array( $this, 'ced_ebay_handle_async_ajax_requests' ) );
	}

	public function ced_ebay_handle_async_ajax_requests() {
		$check_ajax = check_ajax_referer( 'ced-ebay-ajax-seurity-string', 'ajax_nonce' );
		if ( ! $check_ajax ) {
			wp_send_json_error( array( 'message' => 'Nonce check failed!' ) );
		}
		// Get the 'route' parameter to determine which function to call.
		$route = isset( $_POST['route'] ) ? sanitize_text_field( $_POST['route'] ) : '';
		// Sanitize each element in the $_POST array.
		$sanitized_data = array_map( 'sanitize_text_field', $_POST );
		if ( ! is_array( $sanitized_data ) || empty( $sanitized_data ) ) {
			wp_send_json_error( array( 'message' => 'Invalid request data' ) );
		}
		if ( ! empty( $route ) && method_exists( $this, $route ) ) {
			call_user_func( array( $this, $route ), array( $this, $sanitized_data ) );
		} else {
			wp_send_json_error( array( 'message' => 'Invalid AJAX request' ) );
		}
	}

	public function ced_ebay_sync_business_policies( $args ) {
		if ( is_array( $args ) && ! empty( $args ) && isset( $args[1] ) ) {
			$user_id    = isset( $args[1]['user_id'] ) ? $args[1]['user_id'] : '';
			$site_id    = isset( $args[1]['site_id'] ) ? $args[1]['site_id'] : '';
			$ajax_nonce = isset( $args[1]['ajax_nonce'] ) ? $args[1]['ajax_nonce'] : '';
			$this->async_background_action->data(
				array(
					'user_id'        => $user_id,
					'site_id'        => $site_id,
					'perform-action' => 'sync-policy',
					'ajax_nonce'     => $ajax_nonce,
				)
			);
			$dispatch = $this->async_background_action->dispatch();
			if ( is_wp_error( $dispatch ) || ! $dispatch ) {
				wp_send_json_error(
					( array(
						'message' => 'Failed to perform action',
					) )
				);
			} else {
				wp_send_json_success(
					array(
						'message' => 'Business Policies are being synced',
					)
				);
			}
		}
	}
}
