<?php

$configFile = CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayConfig.php';
if(file_exists( $configFile )) {
	require_once $configFile;
}

	$apiResponse = [];
	$siteID                     = isset( $_GET['sid'] ) ? wc_clean( $_GET['sid'] ) : '';
	$user_id                    = isset( $_GET['user_id'] ) ? wc_clean( $_GET['user_id'] ) : '';
	$rsid                    = isset( $_GET['rsid'] ) ? wc_clean( $_GET['rsid'] ) : '';
	$isPaymentPolicyConfigured  = false;
	$isShippingPolicyConfigured = false;
	$isReturnPolicyConfigured   = false;
	if ( '' !== $siteID && ! empty( $user_id ) && !empty($rsid) ) {
		$business_policies        = array(
			'paymentPolicies'     => '',
			'returnPolicies'      => '',
			'fulfillmentPolicies' => '',
		);
		$configInstance           = \Ced\Ebay\Ebayconfig::get_instance();
		$countryDetails           = $configInstance->getEbaycountrDetail( $siteID );
		$country_code             = $countryDetails['countrycode'];
		$marketplace_enum         = 'EBAY_' . $country_code;
		
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
			$isPaymentPolicyConfigured            = true;
		}
		// $requestBody = $apiClient->setRequestBody('seller-profiles', 'GET', [
		// 	'marketplace_id' => $marketplace_enum,
		// 	'new_api' => true,
		// 	'shop_id' => $rsid,
		// 	'type' => 'GetReturnPolicy'
		// ], []);		

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
			$isReturnPolicyConfigured            = true;
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
			$isShippingPolicyConfigured               = true;
		}
		if ( ! empty( $business_policies['paymentPolicies'] ) && ! empty( $business_policies['returnPolicies'] ) && ! empty( $business_policies['fulfillmentPolicies'] ) ) {
			set_transient( 'ced_ebay_business_policies_' . $user_id . '>' . $siteID, $business_policies, 2 * HOUR_IN_SECONDS );
		}
	}
?>

<style type="text/css">
	.ced-label-wrap label {
		font-weight: 600;
		color: #1E1E1E;
	}

	.ced-label-wrap label {
		line-height: 32px;
	}

	.ced-label-wrap {
		margin-bottom: 15px;
	}

	.ced-progress li {
		width: 33% !important;
	}
</style>
<div class="woocommerce-progress-form-wrapper">

	<h2 style="text-align: left;">eBay Integration Onboarding</h2>
	<ol class="wc-progress-steps ced-progress">

		<?php

		foreach ( $keys as $index => $key ) {

			if ( $this->step == $key ) {
				echo '<li id="' . esc_attr( $key ) . '" class="active">' . esc_attr( $this->steps[ $key ]['name'] ) . ' </li>';
			} elseif ( $this->step !== $key && $index < $step_index ) {
				echo '<li id="' . esc_attr( $key ) . '" class="done">' . esc_attr( $this->steps[ $key ]['name'] ) . ' </li>';
			} else {
				echo '<li id="' . esc_attr( $key ) . '">' . esc_attr( $this->steps[ $key ]['name'] ) . ' </li>';

			}
		}
		?>



	</ol>
	<div class="wc-progress-form-content woocommerce-importer">
	<form class="wc-progress-form-content woocommerce-importer" name="ced_eBay_onboardings" enctype="multipart/form-data" action="" method="post">

		<header>
			<h2>General Settings</h2>
		</header>
		<header>
			<h3>Listings Configuration</h3>
			<p>Increase or decrease the price of eBay listings, adjust stock levels from WooCommerce.</p>
			<table class="form-table">
				<tbody>

					<tr>
						<th scope="row" class="titledesc">
							<label for="woocommerce_currency">
								Stock Levels
								<?php print_r( wc_help_tip( 'Stock level, also called inventory level, indicates the quantity of a particular product or product that you own on any platform', 'ebay-integration-for-woocommerce' ) ); ?>
							</label>
						</th>
						<td class="forminp forminp-select">
							<select style="width: 100%;" name="ced_ebay_global_settings[ced_ebay_product_stock_type]"
								id="ced_ebay_product_stock_type">
								<option value="">Select a value</option>
								<option value="MaxStock">Maximum Quantity</option>
							</select>
						</td>
						<td class="forminp forminp-select">
							<input style="width: 100%;" name="ced_ebay_global_settings[ced_ebay_listing_stock]" id="ced_ebay_listing_stock" type="number" style="min-width:50px;" value=""
								class="" placeholder="Enter Value">
						</td>
					</tr>
					<tr>
						<th scope="row" class="titledesc">
							<label for="woocommerce_currency">
								Price Markup
								<?php print_r( wc_help_tip( 'Markup is the amount you include in prices to earn profit while selling on eBay. You are able to increase or decrease the markup either by a fixed amount or by percentage.', 'ebay-integration-for-woocommerce' ) ); ?>
							</label>
						</th>
						<td class="forminp forminp-select">
							<select style="width: 100%;" name="ced_ebay_global_settings[ced_ebay_product_markup_type]"
								id="ced_ebay_product_markup_type">
								<option value="">Select a value</option>
								<option value="Fixed_Increased">Fixed Increase</option>
								<option value="Fixed_Decreased">Fixed Decrease</option>
								<option value="Percentage_Increased">Percentage Increase</option>
								<option value="Percentage_Decreased">Percentage Decrease</option>
							</select>
						</td>
						<td class="forminp forminp-select">
							<input style="width: 100%;" name="ced_ebay_global_settings[ced_ebay_product_markup]" id="ced_ebay_product_markup" type="number" style="min-width:50px;" value=""
								class="" placeholder="Enter Value">
						</td>
					</tr>
				</tbody>
			</table>
			<h3>eBay Business Policies</h3>
			<p>Select your eBay business policies before you are able to list new products on eBay or update existing products</p>
			<table class="form-table">
				<tbody>
					<?php
					if ( $isPaymentPolicyConfigured && $isReturnPolicyConfigured && $isShippingPolicyConfigured ) {
						?>
						<tr>
							<th colspan="2" scope="row" class="titledesc">
								<label for="woocommerce_currency">
									Shipping Policy
								</label>
							</th>
							<td colspan="3" class="forminp forminp-select">
								<select style="width: 100%;" name="ced_ebay_global_settings[ced_ebay_shipping_policy]"
									id="ced_ebay_shipping_policy">
									<option value="">Select a value</option>

									<?php
									foreach ( $account_shipping_policies['fulfillmentPolicies'] as $shipping_policy ) {
										if ( ! empty( $shipping_policy['name'] && ! empty( $shipping_policy['fulfillmentPolicyId'] ) ) ) {
											?>
											<option value="<?php echo esc_attr( $shipping_policy['fulfillmentPolicyId'] . '|' . $shipping_policy['name'] ); ?>"><?php echo esc_attr( $shipping_policy['name'] ); ?></option>
											<?php
										}
									}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<th colspan="2" scope="row" class="titledesc">
								<label for="woocommerce_currency">
									Payment Policy 
								</label>
							</th>
							<td colspan="3" class="forminp forminp-select">
								<select style="width: 100%;" name="ced_ebay_global_settings[ced_ebay_payment_policy]"
									id="ced_ebay_payment_policy">
									<option value="">Select a value</option>
									<?php
									foreach ( $account_payment_policies['paymentPolicies'] as $payment_policy ) {
										if ( ! empty( $payment_policy['name'] && ! empty( $payment_policy['paymentPolicyId'] ) ) ) {
											?>
											<option value="<?php echo esc_attr( $payment_policy['paymentPolicyId'] . '|' . $payment_policy['name'] ); ?>"><?php echo esc_attr( $payment_policy['name'] ); ?></option>
											<?php
										}
									}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<th colspan="2" scope="row" class="titledesc">
								<label for="woocommerce_currency">
									Return Policy
								</label>
							</th>
							<td colspan="3" class="forminp forminp-select">
								<select style="width: 100%;" name="ced_ebay_global_settings[ced_ebay_return_policy]"
									id="ced_ebay_return_policy">
									<option value="">Select a value</option>
									<?php
									foreach ( $account_return_policies['returnPolicies'] as $return_policy ) {
										if ( ! empty( $return_policy['name'] && ! empty( $return_policy['returnPolicyId'] ) ) ) {
											?>
											<option value="<?php echo esc_attr( $return_policy['returnPolicyId'] . '|' . $return_policy['name'] ); ?>"><?php echo esc_attr( $return_policy['name'] ); ?></option>
											<?php
										}
									}
									?>
								</select>
							</td>
						</tr>
						<?php
					} else {
						?>
						<p>You haven't configured Business Policies for your eBay account.</p>
						<?php
					}
					?>

				</tbody>
			</table>
		</header>
		<div class="wc-actions">
			<?php wp_nonce_field( 'ced_ebay_general_settings_action', 'ced_ebay_general_settings_button' ); ?>
			<button type="submit" style="float: right;" value="Save and continue" name="save_step"
				class="button-next components-button is-primary">Save and continue</button>
			
		</div>
	</div>
				</form>
</div>
