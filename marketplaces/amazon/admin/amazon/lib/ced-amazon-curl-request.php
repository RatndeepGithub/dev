<?php

class Ced_Amazon_Curl_Request {


	public function ced_amazon_get_category( $url, $user_id, $seller_id = '' ) {

		$topic = $url;
		$data  = array(
			'contract_id'    => $contract_id,
			'mode'           => 'production',
			'remote_shop_id' => $user_id,
		);

		$data_response = $this->ced_amazon_serverless_process( $topic, $data, 'GET' );

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

	public function fetchProductTemplate( $category_id, $userCountry, $seller_id = '', $marketplace_id = '', $user_id = '' ) {

		$contract_data = get_option( 'ced_unified_contract_details', array() );
		$contract_id   = isset( $contract_data['amazon'] ) && isset( $contract_data['amazon']['contract_id'] ) ? $contract_data['amazon']['contract_id'] : '';

		// Product flat file template structure json file
		$file_location = 'lib/' . $userCountry . '/' . $category_id . '/json/products_template_fields.json';

		$topic = 'get_template?location=' . $file_location;
		$data  = array(

			'contract_id'    => $contract_id,
			'mode'           => 'production',
			'remote_shop_id' => $user_id,
		);

		$data_response = $this->ced_amazon_serverless_process( $topic, $data, 'GET' );

		// print_r($data_response);
		// die;

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

	public function getMarketplaceParticipations( $marketplace_id, $seller_id, $user_id, $mode = 'production' ) {

		$contract_data = get_option( 'ced_unified_contract_details', array() );
		$contract_id   = isset( $contract_data['amazon'] ) && isset( $contract_data['amazon']['contract_id'] ) ? $contract_data['amazon']['contract_id'] : '';

		$topic = 'marketplace';
		$data  = array(
			'contract_id'    => $contract_id,
			'mode'           => $mode,
			'remote_shop_id' => $user_id,
		);

		$response = $this->ced_amazon_serverless_process( $topic, $data, 'GET' );
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
		if ( '_sandbox' == $data['mode'] ) {
			unset( $data['contract_id'] );
		}

		unset( $data['mode'] );

		$url_components = parse_url( $topic );
		$mod_topic      = $url_components['path'];
		$query          = $url_components['query'] ?? '';
		parse_str( $query, $query_params );

		$endpoint = 'https://api.cedcommerce.com/cedcommerce-validator/v1/remote';
		$body     = array_filter(
			array(
				'topic'       => $mod_topic,
				'body'        => $data,
				'marketplace' => 'amazon',
				'method'      => $optType,
				'domain'      => 'http://localhost:8888/wordpress',

			)
		);

		$query_params['shop_id'] = $data['remote_shop_id'];
		// if( !empty( $query_params ) ){
			$body['query_params'] = $query_params;
		// }

		$body = wp_json_encode( $body );

		if ( isset( $data['marketplace_id'] ) && is_array( $data['marketplace_id'] ) ) {
			$marketplace_id = isset( $data['marketplace_id'][0] ) ? $data['marketplace_id'][0] : '';
		} elseif ( isset( $data['marketplace_id'] ) && is_string( $data['marketplace_id'] ) ) {
			$marketplace_id = $data['marketplace_id'];
		}

		$access_token = 'edgfdzsrtwreteryrtyhrsdgerhgyrtur6ghfgkyghwabejwkgehwka';

		$options = array(   // $args
			'body'        => $body,
			'headers'     => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $access_token,
				'Product'       => 'amazon',
			),
			'timeout'     => 30,
			'sslverify'   => 0,
			'data_format' => 'body',
		);

		// echo '<pre>';
		// print_r( $options );

		$response = wp_remote_post( $endpoint, $options );
		// if ( 'POST' == $optType ) {
		// $response = wp_remote_post( $endpoint, $options );
		// } else {
		// $response = wp_remote_get( $endpoint, $options );
		// }

		if ( is_array( $response ) && isset( $response['body'] ) ) {
			$body      = json_decode( $response['body'], true );
			$http_code = isset( $body['data'] ) && isset( $body['data']['http_code'] ) ? $body['data']['http_code'] : '200';

			if ( isset( $http_code ) && '200' != $http_code ) {
				if ( file_exists( CED_AMAZON_DIRPATH . 'admin/amazon/lib/class-ced-amazon-logger.php' ) ) {
					require_once CED_AMAZON_DIRPATH . 'admin/amazon/lib/class-ced-amazon-logger.php';

					$loggerInstance = new Class_Ced_Amazon_Logger();
					$loggerInstance->ced_add_log_response_serverless( $seller_id, $body, $topic, time(), $http_code );
				}
			}
		}

		return $response;
	}

	public function ced_search_amz_cat( $user_id, $seller_id, $cat_value, $mode = 'production' ) {

		$contract_data = get_option( 'ced_unified_contract_details', array() );
		$contract_id   = isset( $contract_data['amazon'] ) && isset( $contract_data['amazon']['contract_id'] ) ? $contract_data['amazon']['contract_id'] : '';

		$category_query_params = array(
			'selected'    => $cat_value,
			'hasChildren' => false,
		);
		$category_data         = array(
			'contract_id'    => $contract_id,
			'mode'           => $mode,
			'remote_shop_id' => $user_id,
		);

		$catalog_topic = 'itemsbyean?' . http_build_query( $category_query_params );
		$response      = $this->ced_amazon_serverless_process( $catalog_topic, $category_data, 'GET' );

		if ( is_wp_error( $response ) ) {
			$errorResponse = json_decode( json_encode( $response ), true );
			wp_send_json_error( 'Unable to fetch data. Please try again.' );

		}
		$response_body = json_decode( $response['body'], true );
		$response_data = isset( $response_body['response'] ) ? $response_body['response'] : array();
		$response_data = json_decode( json_encode( $response_data ), true );

		return $response_data;
	}

}

