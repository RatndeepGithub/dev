<?php

namespace Ced\Ebay;
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}


class CED_EBAY_API_Client {
    private $base_url = 'https://api.cedcommerce.com/cedcommerce-validator/v1/remote';
    private $jwt_token;

    private $requestBodyTemplate = [
        'marketplace' => 'ebay'    
    ];

    public function setDomain(){
        $host = 'localhost';

		if ( isset( $_SERVER['HTTP_HOST'] ) ) {
			$host = filter_var( wp_unslash( $_SERVER['HTTP_HOST'] ) );
		}

        //  $this->requestBodyTemplate['domain'] = $host;
        $this->requestBodyTemplate['domain'] = 'http://localhost:8888/wordpress';
    }   
    public function setJwtToken($token) {
        $this->jwt_token = $token;
    }
    public function setRequestTopic($topic){
        $this->requestBodyTemplate['topic'] = $topic;
    }

    public function setRequestRemoteMethod($remote_method){
        $this->requestBodyTemplate['method'] = $remote_method;
    }

    public function setRequestRemoteQueryParams($query_params = []){
        $this->requestBodyTemplate['query_params'] = $query_params;
    }

    public function setRequestRemoteBody($body = []){
        $this->requestBodyTemplate['body'] = $body;
    }

    private function makeRequest($method) {
        $url = $this->base_url;
        $this->setDomain();
        $args = [
            'method'    => $method,
            'headers'   => [
                'Authorization' => 'Bearer ' . $this->jwt_token,
                'Content-Type'  => 'application/json',
                'Product'       => 'ebay'
            ],
            'body'      => $this->requestBodyTemplate ? json_encode($this->requestBodyTemplate) : null,
            'data_format' => 'body',
            'timeout' => 60
        ];

        switch ($method) {
            case 'POST':
                $response = wp_remote_post($url, $args);
                break;
            default:
                return new WP_Error('invalid_method', 'Invalid HTTP method', ['status' => 405]);
        }

        if (is_wp_error($response)) {
            return $response;
        } else {
            return json_decode(wp_remote_retrieve_body($response), true);
        }
    }



    public function post() {
        return $this->makeRequest('POST');
    }

}
