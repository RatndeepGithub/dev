<?php

function get_plan_options() {

	$curl = curl_init();

	curl_setopt_array(
		$curl,
		array(
			CURLOPT_URL            => 'https://api.cedcommerce.com/woobilling/live/ced_pricing_plan_options.json',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING       => '',
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => 'GET',
		)
	);

	$response = curl_exec( $curl );
	curl_close( $curl );
	return $response;
}
$prod_data = get_transient( 'ced_is_sale_data' );
if ( empty( $prod_data ) ) {
	$product_data = 'unified-bundle';
	$plan_data    = get_plan_options();
	$prod_data    = array();
	$contract_id  = '';
	if ( ! empty( $plan_data ) ) {
		$plan_data = json_decode( $plan_data, true );
		$prod_data = $plan_data[ $product_data ]['sale'];
		set_transient( 'ced_is_sale_data', $prod_data, 0.25 * HOUR_IN_SECONDS );
	}
}

if ( $prod_data['is_sale'] ) :
	?>
<div>
	<input type="hidden" id="copy_copoun_code" value="<?php print_r( $prod_data['coupon_code'] ); ?>">
	<div class="banner_img"><img src="<?php print_r( $prod_data['banner'] ); ?>"></div>
</div>
	<?php
endif;
?>
