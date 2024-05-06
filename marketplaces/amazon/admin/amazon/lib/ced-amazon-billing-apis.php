<?php

class Billing_Apis {


	/**
	 * Function to get Amaazon plan by ID
	 *
	 * @param            $id woocommerce contract ID
	 * @since            1.0.0
	 * @return           array
	 */
	public function getAmazonPlanById( $id ) {

		if ( empty( $id ) ) {
			return(
				array(
					'status'  => false,
					'message' => 'Failed to fetch your current plans details. Please try again later or contact support.',
				)
			);

		}

		$data = array(
			'action'          => 'get_subscription',
			'channel'         => 'amazon',
			'subscription_id' => $id,
		);
		$curl = curl_init();

		$url = 'https://api.cedcommerce.com/woobilling/live/ced_api_request.php';
		$url = $url . '?' . http_build_query( $data );
		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING       => '',
				CURLOPT_MAXREDIRS      => 10,
				CURLOPT_TIMEOUT        => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,

				CURLOPT_POSTFIELDS     => $id,
			)
		);

		$currentPlanResponse = curl_exec( $curl );
		curl_close( $curl );

		if ( is_wp_error( $currentPlanResponse ) ) {
			return(
				array(
					'status'  => false,
					'message' => 'Failed to fetch your current plans details. Please try again later or contact support.',
				)
			);

		} else {
			$response = json_decode( $currentPlanResponse, true );
			return $response;

		}
	}
}
