<?php
/**
 * Curl requests
 *
 * @package  Woocommerce_Walmart_Integration
 * @version  1.0.0
 * @link     https://woocommerce.com/vendor/cedcommerce/
 * @since    1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Ced_Walmart_Curl_Request
 *
 * @since 1.0.0
 */
class Ced_Walmart_Curl_Request {
		/**
		 * The instance variable of this class.
		 *
		 * @since    1.0.0
		 * @var      object    $_instance    The instance variable of this class.
		 */
	public static $_instance;
		/**
		 * The endpoint variable.
		 *
		 * @since    1.0.0
		 * @var      string    $end_point_url    The endpoint variable.
		 */
		public $end_point_url;
		/**
		 * The client_id variable.
		 *
		 * @since    1.0.0
		 * @var      string    $client_id   The client_id variable.
		 */
		public $client_id;

		/**
		 * The client_secret variable
		 *
		 * @since    1.0.0
		 * @var      string    $client_secret    The client_secret variable.
		 */
		public $client_secret;

		/**
		 * The access_token variable
		 *
		 * @since    1.0.0
		 * @var      string    $access_token    The access_token variable.
		 */
		public $access_token;

		/**
		 * The channel_id variable
		 *
		 * @since    1.0.0
		 * @var      string    $channel_id    The channel_id variable.
		 */
		public $channel_id;

		public $store_id;


	/**
	 * Ced_Walmart_Curl_Request Instance.
	 *
	 * @since 1.0.0
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Ced_Walmart_Curl_Request construct
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Ced_Walmart_Curl_Request ced_walmart_get_request
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_get_request( $action = '', $parameters = '', $query_args = array(), $token = '' ) {
		if ( empty( $action ) ) {
			return;
		}

		$this->load_depenedency();

		if ( isset( $query_args['client_id'] ) && isset( $query_args['client_secret'] ) ) {
			$this->client_id     = $query_args['client_id'];
			$this->client_secret = $query_args['client_secret'];
			$this->environment   = $query_args['environment'];
			$query_args          = array();
		}

		$common_headers     = $this->get_common_headers( $action );
		$additional_headers = $this->get_additional_headers( $action, $token );
		$headers            = array_merge( $common_headers, $additional_headers );
		$api_url            = $this->end_point_url . $action;
		if ( ! empty( $query_args ) ) {
			$api_url = $api_url . '?' . http_build_query( $query_args );
		}

		if ( 'token' != $action ) {
			$headers[] = 'Content-Type: application/json';
		}

		$connection = curl_init();
		curl_setopt( $connection, CURLOPT_URL, $api_url );
		curl_setopt( $connection, CURLOPT_HTTPHEADER, array_unique( $headers ) );
		curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $connection, CURLOPT_ENCODING, '' );
		if ( ! empty( $parameters ) ) {
			curl_setopt( $connection, CURLOPT_POST, 1 );
			curl_setopt( $connection, CURLOPT_POSTFIELDS, $parameters );
		}
		curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
		$response = curl_exec( $connection );

		$error = curl_error( $connection );
		if ( $error ) {
			echo json_encode(
				array(
					'status'  => 400,
					'message' => 'Curl error : ' . $error,
				)
			);
			die;
		}
		curl_close( $connection );
		$response = $this->parse_response( $response );

		return $response;
	}

	/**
	 * Ced_Walmart_Curl_Request ced_walmart_post_request
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_post_request( $action = '', $parameters = array(), $query_args = array() ) {
		if ( empty( $action ) ) {
			return;
		}

		$this->load_depenedency();
		$common_headers     = $this->get_common_headers( $action );
		$additional_headers = $this->get_additional_headers( $action );
		$headers            = array_merge( $common_headers, $additional_headers );
		$api_url            = $this->end_point_url . $action;
		if ( ! empty( $query_args ) ) {
			$api_url = $api_url . '?' . http_build_query( $query_args );
		}

		if ( isset( $parameters['file'] ) ) {
			$parameters = file_get_contents( $parameters['file'] );
		} elseif ( ! empty( $parameters ) ) {
			$parameters = json_encode( $parameters );
			// $headers[]  = 'Content-Type: application/json';
		} else {
			$parameters = null;
			$headers[]  = 'Content-Type: application/json';
		}
		$connection = curl_init();

		curl_setopt( $connection, CURLOPT_URL, $api_url );
		curl_setopt( $connection, CURLOPT_HTTPHEADER, array_unique( $headers ) );
		curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $connection, CURLOPT_CUSTOMREQUEST, 'POST' );
		curl_setopt( $connection, CURLOPT_POSTFIELDS, $parameters );
		curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $connection, CURLOPT_ENCODING, '' );
		$response = curl_exec( $connection );
		$error    = curl_error( $connection );
		if ( $error ) {
			echo json_encode(
				array(
					'status'  => 400,
					'message' => 'Curl error : ' . $error,
				)
			);
			die;
		}
		curl_close( $connection );
		$response = $this->parse_response( $response );
		return $response;
	}

	/**
	 * Ced_Walmart_Curl_Request ced_walmart_Delete_request
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_delete_request( $action = '', $parameters = array(), $query_args = array() ) {
		if ( empty( $action ) ) {
			return;
		}

		$this->load_depenedency();
		$common_headers     = $this->get_common_headers( $action );
		$additional_headers = $this->get_additional_headers( $action );
		$headers            = array_merge( $common_headers, $additional_headers );
		$api_url            = $this->end_point_url . $action;
		if ( ! empty( $query_args ) ) {
			$api_url = $api_url . '?' . http_build_query( $query_args );
		}

		if ( isset( $parameters['file'] ) ) {
			$parameters = file_get_contents( $parameters['file'] );
		} elseif ( ! empty( $parameters ) ) {
			$parameters = json_encode( $parameters );
			$headers[]  = 'Content-Type: application/json';
		} else {
			$parameters = null;
			$headers[]  = 'Content-Type: application/json';
		}
		$connection = curl_init();
		curl_setopt( $connection, CURLOPT_URL, $api_url );
		curl_setopt( $connection, CURLOPT_HTTPHEADER, array_unique( $headers ) );
		curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $connection, CURLOPT_CUSTOMREQUEST, 'DELETE' );
		curl_setopt( $connection, CURLOPT_POSTFIELDS, $parameters );
		curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $connection, CURLOPT_ENCODING, '' );
		$response = curl_exec( $connection );
		$error    = curl_error( $connection );
		if ( $error ) {
			echo json_encode(
				array(
					'status'  => 400,
					'message' => 'Curl error : ' . $error,
				)
			);
			die;
		}
		curl_close( $connection );
		$response = $this->parse_response( $response );
		return $response;
	}
	/**
	 * Ced_Walmart_Curl_Request get_common_headers
	 *
	 * @since 1.0.0
	 */
	public function get_common_headers( $action = '' ) {

		$headers[] = 'Authorization: Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret );
		$headers[] = 'WM_SVC.NAME: Walmart Marketplace';
		$headers[] = 'WM_QOS.CORRELATION_ID: ' . base64_encode( uniqid() );
		$headers[] = 'Accept: application/json';
		return $headers;
	}

	/**
	 * Ced_Walmart_Curl_Request get_common_headers
	 *
	 * @since 1.0.0
	 */
	public function get_additional_headers( $action = '', $token = '' ) {
		$headers = array();
		if ( 'token' == $action ) {
			$headers[] = 'WM_SVC.VERSION: 1.0.0';
			$headers[] = 'Content-Type: application/x-www-form-urlencoded';
		} else {
			$headers[] = 'HOST: marketplace.walmartapis.com';
			if ( ! empty( $token ) ) {
				$headers[] = 'WM_SEC.ACCESS_TOKEN: ' . $token;
			} else {
				$headers[] = 'WM_SEC.ACCESS_TOKEN: ' . $this->access_token;
			}

			$headers[] = 'WM_CONSUMER.CHANNEL.TYPE: ' . $this->channel_id;

		}
		return $headers;
	}

	/**
	 * Function for parse_response
	 *
	 * @since 1.0.0
	 * @param string $response Response from walmart.
	 */
	public function parse_response( $response ) {
		if ( ! empty( $response ) ) {
			return json_decode( $response, true );
		}
	}

	/**
	 * Function load_depenedency
	 *
	 * @since 1.0.0
	 */
	public function load_depenedency() {
		$file = CED_WALMART_DIRPATH . 'admin/walmart/lib/class-ced-walmart-config.php';
		if ( file_exists( $file ) ) {
			include_once $file;
			$this->ced_walmart_config_instance = new Ced_Walmart_Config( $this->store_id );
			$this->end_point_url               = $this->ced_walmart_config_instance->end_point_url;
			$this->client_id                   = $this->ced_walmart_config_instance->client_id;
			$this->client_secret               = $this->ced_walmart_config_instance->client_secret;
			$this->access_token                = $this->ced_walmart_config_instance->access_token;
			$this->channel_id                  = $this->ced_walmart_config_instance->channel_id;
			$this->environment                 = $this->ced_walmart_config_instance->environment;
		}
	}
}
