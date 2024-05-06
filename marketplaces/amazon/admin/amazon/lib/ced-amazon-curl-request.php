<?php

class Ced_Amazon_Curl_Request {


	public function ced_amazon_get_category( $url, $user_id, $seller_id = '' ) {

		$access_token_response = ced_amazon_get_access_token( $user_id, $seller_id );
		$decoded_response      = json_decode( $access_token_response, true );

		if ( ! $decoded_response['status'] ) {
			return $access_token_response;  // json_encoded_response
		}

		$access_token = $decoded_response['data'];

		$args = array(
			'headers'     => array(
				'Authorization' => 'Bearer ' . $access_token,
			),
			'timeout'     => 1000,
			'httpversion' => '1.0',
			'sslverify'   => false,
		);

		$response   = wp_safe_remote_get( 'https://amazon-sales-channel-api-backend.cifapps.com/' . $url, $args );
		$categories = array();

		if ( is_array( $response ) && isset( $response['body'] ) ) {
			$categories = json_decode( $response['body'], true );
			return wp_json_encode(
				array(
					'status' => true,
					'data'   => $categories,
				)
			);

		} elseif ( is_object( $response ) ) {
				echo wp_json_encode(
					array(
						'success' => false,
						'message' => $response->errors['http_request_failed'][0],
						'status'  => 'error',
					)
				);
				die;
		} else {
			return wp_json_encode(
				array(
					'status' => true,
					'data'   => $categories,
				)
			);
			// return $categories;
		}
	}

	public function fetchProductTemplate( $category_id, $userCountry, $seller_id = '' ) {

		$contract_data = get_option( 'ced_unified_contract_details', array() );
		$contract_id   = isset( $contract_data['amazon'] ) && isset( $contract_data['amazon']['contract_id'] ) ? $contract_data['amazon']['contract_id'] : '';

		// Product flat file template structure json file
		$file_location = 'lib/' . $userCountry . '/' . $category_id . '/json/products_template_fields.json';  // products_all_fields.json'
		
		$topic = 'webapi/amazon/get_template';
		$data  = array(
			'location'    => $file_location,
			'contract_id' => $contract_id,
			'seller_id'   => $seller_id,
		);

		$data_response = $this->ced_amazon_serverless_process( $topic, $data, 'POST');


		$data_response = json_decode( $data_response['body'], true );
		$data_response = isset( $data_response['data'] ) ? $data_response['data'] : array();
		if ( ! isset( $data_response['url'] ) ) {
			echo wp_json_encode(
				array(
					'status'  => 'error',
					'message' => 'Unable to fetch template fields. Please try again later.',
					'success' => false,
				)
			);
			die;
		}
		$json_url = $data_response['url'];
		$json_url = stripslashes( $json_url );
		// $json_template_data = file_get_contents( $json_url );
		$json_template_data = wp_safe_remote_get( $json_url ); 
		$json_template_data = isset( $json_template_data['body'] ) ? $json_template_data['body'] : '';

		$upload_dir     = wp_upload_dir();
		$dirname        = $upload_dir['basedir'] . '/ced-amazon/templates/' . $userCountry . '/' . $category_id;
		$json_file_name = 'products_template_fields.json';

		if ( ! file_exists( $dirname . '/' . $json_file_name ) ) {
			if ( ! is_dir( $dirname ) ) {
				wp_mkdir_p( $dirname );
			}
			$templateFile = fopen( $dirname . '/' . $json_file_name, 'w' );
			fwrite( $templateFile, $json_template_data );

		} else {
			$templateFile = fopen( $dirname . '/' . $json_file_name, 'w' );
			fwrite( $templateFile, $json_template_data );
		}

		fclose( $templateFile );
		chmod( $dirname . '/' . $json_file_name, 0777 );
	}

	public function getMarketplaceParticipations( $refresh_token, $marketplace_id, $seller_id ) {

		$contract_data = get_option( 'ced_unified_contract_details', array() );
		$contract_id   = isset( $contract_data['amazon'] ) && isset( $contract_data['amazon']['contract_id'] ) ? $contract_data['amazon']['contract_id'] : '';

	
		$topic    = 'webapi/amazon/get_marketplace_participations';
		$data     = array(
			'marketplace_id' => $marketplace_id,
			'seller_id'      => $seller_id,
			'token'          => $refresh_token,
			'contract_id'    => $contract_id,
		);
		$response = $this->ced_amazon_serverless_process( $topic, $data, 'POST');

		if ( is_array( $response ) && isset( $response['body'] ) ) {
			return json_decode( $response['body'], true );
		} else {
			return array(
				'status'  => 'error',
				'message' => 'Unable to fetch your details and verify you',
			);
		}
	}


	public function ced_amazon_serverless_process( $topic = '', $data = array(), $optType = 'GET' ) {


		$seller_id = isset( $data['seller_id'] ) ? $data['seller_id'] : '-';

		$endpoint = 'https://amazon-sales-channel-api-backend.cifapps.com/webapi/rest/v1/woocommerce/serverlessProcess';
		$body     = array(
			'topic' => $topic,
			'data'  => $data,

		);

		$body = wp_json_encode( $body );

		$options = array(
			'body'    => $body,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJ1c2VyX2lkIjoiNjM2Y2ZjYTgxNzkwOGMwNzg0MGFhOWY5Iiwicm9sZSI6ImN1c3RvbWVyX2FwaSIsImV4cCI6MTgxNTgzMzIzMywiYXBwX2lkIjoiMiIsInN1Yl9hcHBfaWQiOiIyIiwic3VidXNlcl9pZCI6IjEiLCJpc3MiOiJodHRwczpcL1wvYXBwcy5jZWRjb21tZXJjZS5jb20iLCJ0b2tlbl9pZCI6IjY0ZDBmYTkxNTlkZmVmNmMxYTBiNDU1MiJ9.CEsagjPfX0maHL-lh1hn_tCgBhuvng0-Ta9BDbrqtCn4OFNPSk5CKh9UWq6nvGZpDNUzvy6y2t_6G5WYHf3zS-saSk-uJX7SgQX0X5VNxioIp0QbLoZVMHQ9AFQ30qbZ-4pAhjpHq9pJLYNDxMwWeg60hJzlSeTnzrQoXhOh6fw3_EXnzsmarWGvsE1EnzZIx4EWJfqzDWmFBcs9lHIZtWCb8FObnBUsnF7e4IkchCdLgSTFyD56rHifo9Wv4LU8DFPtWO3BykCHVHULj7afQRSIWAdODnGr90G5tKWlcxqGjayXbdGCdJSMT9OdfcHB5uU3kLp9ZXRNDcqzrJKexQ',
			),
			'timeout' => 200,
		);

		if ( 'POST' == $optType ) {
			$response = wp_remote_post( $endpoint, $options );
		} else {
			$response = wp_remote_get( $endpoint, $options );
		}

		if ( is_array( $response ) && isset( $response['body'] ) ) {
			$body      = json_decode( $response['body'], true );
			$http_code = isset($body['data']) && isset($body['data']['http_code'])? $body['data']['http_code']:'200';

			if (isset($http_code) && '200'!= $http_code) {
				if ( file_exists( CED_AMAZON_DIRPATH . 'admin/amazon/lib/class-ced-amazon-logger.php' ) ) {
					require_once CED_AMAZON_DIRPATH . 'admin/amazon/lib/class-ced-amazon-logger.php';
				   
					$loggerInstance = new Class_Ced_Amazon_Logger();
					$loggerInstance->ced_add_log_response_serverless($seller_id, $body, $topic, time(), $http_code);
				}
			}

			
		} 

		return $response;
	}
}
