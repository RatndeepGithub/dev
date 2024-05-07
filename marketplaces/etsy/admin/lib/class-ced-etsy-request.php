<?php
namespace Cedcommerce\EtsyManager;

/**
 * Class Ced_Etsy_Request
 * Handles API requests to Etsy.
 */
class Ced_Etsy_Request {
	/**
	 * Base URL for Etsy API.
	 *
	 * @var string
	 */
	public $base_url = 'https://api.cedcommerce.com/cedcommerce-validator/v1/remote';

	/**
	 * Topic of the request.
	 *
	 * @var string|null
	 */
	public $topic;

	/**
	 * Body of the request.
	 *
	 * @var array
	 */
	public $body;

	/**
	 * Query parameters of the request.
	 *
	 * @var array
	 */
	public $query_params;

	/**
	 * Method of the request (GET/POST).
	 *
	 * @var string
	 */
	public $method;

	/**
	 * Sends a remote request to Etsy API.
	 *
	 * @param string|null $topic Topic of the request.
	 * @param array       $body  Body of the request.
	 * @param array       $query_params Query parameters of the request.
	 * @param string      $method Method of the request (GET/POST).
	 * @return array Response from the API.
	 */
	public function ced_etsy_remote_req( $topic = null, $body = array(), $query_params = array(), $method = 'GET' ) {
		$response      = wp_remote_post(
			$this->base_url,
			array(
				'sslverify'   => 0,
				'timeout'     => 30000,
				'data_format' => 'body',
				'headers'     => array(
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
					'Product'       => 'etsy',
					'Authorization' => 'Bearer ' . ced_get_auth_token(),
				),
				'body'        => json_encode(
					array_filter(
						array(
							'marketplace'  => 'etsy',
							'topic'        => ! empty( $this->topic ) ? $this->topic : $topic,
							'body'         => ! empty( $this->body ) ? $this->body : $body,
							'query_params' => ! empty( $this->query_params ) ? $this->query_params : $query_params,
							'method'       => ! empty( $this->method ) ? $this->method : $method,
							'domain'       => site_url(),
						)
					)
				),
			)
		);
		$response_body = wp_remote_retrieve_body( $response );
		if ( $response_body ) {
			$b_res = json_decode( $response_body );
			if ( $b_res ) {
				$response_body = $b_res;
			}
		}
		$response = ! empty( $response_body ) ? json_decode( json_encode( $response_body ), 1 ) : array();
		$response = isset( $response['data'] ) ? $response['data'] : $response;

		if ( is_object( $response ) ) {
			return json_decode( $response );
		}
		return $response;
	}
}
