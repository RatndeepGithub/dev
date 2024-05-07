<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}


$part       = isset( $_GET['part'] ) ? sanitize_text_field( $_GET['part'] ) : false;
$seller_id  = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : false;
$user_id    = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : false;
$planStatus = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : false;

$mode = isset( $_GET['mode'] ) ? sanitize_text_field( $_GET['mode'] ) : 'production';

if ( $planStatus ) {
	?>
	<div class="ced-success">
		<p><?php echo esc_html__( 'Plan purchased succesfully.', 'amazon-for-woocommerce' ); ?></p>
	</div>

	<?php
}


if ( empty( $user_id ) ) {
	$user_id = $current_amaz_shop_id;
}

$add_new_account = isset( $_GET['add-new-account'] ) ? sanitize_text_field( $_GET['add-new-account'] ) : false;

$ced_amazon_sellernext_shop_ids = get_option( 'ced_amazon_sellernext_shop_ids', array() );
if ( ! empty( $ced_amazon_sellernext_shop_ids ) && is_array( $ced_amazon_sellernext_shop_ids ) ) {
	$connect_to_amazon['will_connect']  = 'none';
	$connect_to_amazon['did_connected'] = 'block';
} else {
	$connect_to_amazon['will_connect']  = 'block';
	$connect_to_amazon['did_connected'] = 'none';
}
if ( $add_new_account ) {
	$connect_to_amazon['will_connect']  = 'block';
	$connect_to_amazon['did_connected'] = 'none';
}


$file = CED_AMAZON_DIRPATH . 'admin/partials/amazonRegions.php';
if ( file_exists( $file ) ) {
	require_once $file;
}

?>

	<div class="woocommerce-progress-form-wrapper" style="display: <?php echo esc_attr( $connect_to_amazon['will_connect'] ); ?>" >
		<h2 style="text-align: left;"><?php echo esc_html__( 'Amazon Integration', 'amazon-for-woocommerce' ); ?></h2>
		<div class="wc-progress-form-content woocommerce-importer">
			<header>
				<h2><?php echo esc_html__( 'Connect Amazon seller account', 'amazon-for-woocommerce' ); ?></h2>
				<p class="ced_wizard_content"><?php echo esc_html__( "To connect with Amazon, simply fill in the required details and click the 'Connect' button.", 'amazon-for-woocommerce' ); ?></p>
				<div class="form-field form-required term-name-wrap ced-label-wrap">
					<label for="woocommerce_email_from_address"><?php echo esc_html__( 'Seller Email Address', 'amazon-for-woocommerce' ); ?></label>
					<input name="tag-name" id="tag-name" class="ced_amazon_seller_email" type="email" value="" placeholder="Enter Email Address" size="40" aria-required="true" aria-describedby="name-description">
				</div>
				<div class="form-field form-required term-name-wrap ced-label-wrap">
					<label for="tag-name"><?php echo esc_html__( 'Amazon Store Region', 'amazon-for-woocommerce' ); ?></label>

					<?php

						$file = CED_AMAZON_DIRPATH . 'admin/partials/amazonRegions.php';
					if ( file_exists( $file ) ) {
						require_once $file;
					}

						$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', array() );
						$saved_amazon_details = array_values( $saved_amazon_details );

						$keyToGet          = 'country_name';
						$connectedAccounts = array_column( $saved_amazon_details, $keyToGet );

					?>

				
					<select name="ced_amazon_select_marketplace_region" id="ced_amazon_select_marketplace_region" style="width: 100%;" >
											
						<?php

							$na_marketplaces = '';
							$eu_marketplaces = '';
							$fe_marketplaces = '';

							$ced_amazon_regions = array(
								'NA' => 'North America',
								'EU' => 'Europe',
								'FE' => 'Far East region',
							);
							foreach ( $ced_amazon_regions_info as $marketplace_id => $marketplace_data ) {

								if ( ! in_array( $marketplace_data['country-name'], $connectedAccounts ) ) {
									$option = '<option value="' . esc_attr( $marketplace_data['value'] ) . '" country-name="' . esc_attr( $marketplace_data['country-name'] ) . '" shop-name="' . esc_attr( $marketplace_data['shop-name'] ) . '" end-pt="' . esc_attr( $marketplace_data['end-pt'] ) . '" mp-id="' . esc_attr( $marketplace_data['mp-id'] ) . '" mp-url="' . esc_attr( $marketplace_data['mp-url'] ) . '">' . __( $marketplace_data['country-name'], 'amazon-for-woocommerce' ) . '</option>';
									if ( 'NA' == $marketplace_data['region_value'] ) {
										$na_marketplaces .= $option;
									}
									if ( 'EU' == $marketplace_data['region_value'] ) {
										$eu_marketplaces .= $option;
									}
									if ( 'FE' == $marketplace_data['region_value'] ) {
										$fe_marketplaces .= $option;
									}
								}
							}

							$na_region_html = '<option>-- Select -- </option><optgroup data-attr="NA" label="North America">' . __( $na_marketplaces, 'amazon-for-woocommerce' ) . '</optgroup>';
							$eu_region_html = '<optgroup data-attr="EU" label="Europe">' . __( $eu_marketplaces ) . '</optgroup>';
							$fe_region_html = '<optgroup data-attr="FE" label="Far East region">' . __( $fe_marketplaces ) . '</optgroup>';

							print_r( $na_region_html );
							print_r( $eu_region_html );
							print_r( $fe_region_html );

							?>
					</select>
				</div>
			</header>
			<div class="wc-actions">
				<button style="float: right;" type="button" class="components-button is-primary ced_amazon_add_account_button">Connect</button>
			</div>
		</div>
	</div>


	<div class="woocommerce-progress-form-wrapper" style="display: <?php echo esc_attr( $connect_to_amazon['did_connected'] ); ?>" >
		<h2 style="text-align: left;"><?php echo esc_html__( 'Amazon for WooCommerce: Onboarding', 'amazon-for-woocommerce' ); ?></h2>
		<div class="wc-progress-form-content">
			<header>
				<h2><?php echo esc_html__( 'Connect Amazon', 'amazon-for-woocommerce' ); ?></h2>

				<div id="message" class="updated inline ced-notification-notice">
					<p><strong>🎉 <?php echo esc_html__( 'Awesome, your Amazon account is now connected!', 'amazon-for-woocommerce' ); ?></strong></p>

					<?php
						$sellernextShopIds     = get_option( 'ced_amazon_sellernext_shop_ids', array() );
						$sellernextShopIdsKeys = array_keys( $sellernextShopIds );
						$latestShopID          = $sellernextShopIdsKeys[ count( $sellernextShopIdsKeys ) - 1 ];

						$current_marketplace_id   = isset( $sellernextShopIds[ $latestShopID ] ) && isset( $sellernextShopIds[ $latestShopID ]['marketplace_id'] ) ? $sellernextShopIds[ $latestShopID ]['marketplace_id'] : '';
						$current_marketplace_name = isset( $ced_amazon_regions_info[ $current_marketplace_id ] ) && isset( $ced_amazon_regions_info[ $current_marketplace_id ]['country-name'] ) ? $ced_amazon_regions_info[ $current_marketplace_id ]['country-name'] : '';

					?>
					<div class="ced-account-detail-wrapper">
						<div class="ced-account-details-holder"> 
							<p><?php echo esc_html__( 'Account details:', 'amazon-for-woocommerce' ); ?></p>
							<p>
								<?php
								echo esc_html__( 'Store region: ', 'amazon-for-woocommerce' );
								echo esc_attr( $current_marketplace_name );
								?>
							</p>
						</div>
					</div>
					<?php


					$ced_base_uri = ced_amazon_base_uri( $mode );

					$url = get_admin_url() . $ced_base_uri . '&section=setup-amazon';

					?>
					<p class="ced-link">
						
					</p>
				</div>
				<p></p>
			</header>
			<div class="wc-actions">
				<button style="float: right;" type="button" class="components-button is-primary" id="amazon_seller_verification" dta-amz-shop-id = "<?php echo esc_attr( $user_id ); ?>" ><?php echo esc_html__( 'Verify and continue', 'amazon-for-woocommerce' ); ?></button>
			</div>
		</div>
	</div>




