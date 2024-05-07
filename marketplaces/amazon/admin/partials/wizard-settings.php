<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$part         = isset( $_GET['part'] ) ? sanitize_text_field( $_GET['part'] ) : '';
$current_page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
$user_id      = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
$seller_id    = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';


$sellernextShopIds = get_option( 'ced_amazon_sellernext_shop_ids', array() );
$amazon_accounts   = get_option( 'ced_amzon_configuration_validated', array() );


if ( empty( $seller_id ) ) {
	$seller_id = $sellernextShopIds[ $user_id ]['ced_mp_seller_key'];
}
if ( isset( $part ) && ! empty( $part ) ) {
	$sellernextShopIds[ $user_id ]['ced_amz_current_step'] = 2;
	update_option( 'ced_amazon_sellernext_shop_ids', $sellernextShopIds );
}

// Set this argument to pass in CRON scheduler
$seller_args = array( $seller_id );

// Prepare dropdown for meta keys end
if ( '' !== $part ) {

	$connection_setup           = '';
	$integration_settings_setup = '';
	$amazon_options_setup       = '';
	$general_settings_setup     = '';
	if ( empty( $part ) || 'ced-amazon-login' == $part ) {
		$connection_setup = 'active';
	} elseif ( 'amazon-options' == $part ) {
		$amazon_options_setup = 'active';
	} elseif ( 'settings' == $part ) {
		$general_settings_setup = 'active';
	} elseif ( 'configuration' == $part ) {
		$integration_settings_setup = 'active';
	}
}


if ( isset( $_POST['ced_amazon_setting_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_setting_nonce'] ), 'ced_amazon_setting_page_nonce' ) ) {
	if ( isset( $_POST['global_settings'] ) ) {

		$objDateTime         = new DateTime( 'NOW' );
		$timestamp           = $objDateTime->format( 'Y-m-d\TH:i:s\Z' );
		$global_setting_data = get_option( 'ced_amazon_global_settings', array() );
		$settings            = array();
		$sanitized_array     = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );

		if ( isset( $part ) && ! empty( $part ) ) {
			$sellernextShopIds                                     = get_option( 'ced_amazon_sellernext_shop_ids', array() );
			$sellernextShopIds[ $user_id ]['ced_amz_current_step'] = 3;
			update_option( 'ced_amazon_sellernext_shop_ids', $sellernextShopIds );
		}


		$sanitized_array['ced_amazon_global_settings']['ced_amazon_inventory_schedule_info'] = 'ced_amazon_10min';
		$sanitized_array['ced_amazon_global_settings']['ced_amazon_existing_products_sync']  = 'ced_amazon_10min';


		$settings                               = get_option( 'ced_amazon_global_settings', array() );
		// $settings[ $seller_id ]                 = isset( $sanitized_array['ced_amazon_global_settings'] ) ? ( $sanitized_array['ced_amazon_global_settings'] ) : array();
		// $settings[ $seller_id ]['last_updated'] = $timestamp;

		// newly added code starts

		$old_settings     = isset(  $settings[ $seller_id ] ) ?  $settings[ $seller_id ]    :    array();
		
		$new_settings     = isset( $sanitized_array['ced_amazon_global_settings'] ) ? ( $sanitized_array['ced_amazon_global_settings'] ) : array();
		$new_settings['last_updated'] = $timestamp;
		$new_settings = array_merge( $old_settings, $new_settings );

		$settings[ $seller_id ] = $new_settings;

		// newly added code ends

		update_option( 'ced_amazon_global_settings', $settings );
		if ( as_has_scheduled_action( 'ced_amazon_inventory_scheduler_job_' . $seller_id, $seller_args ) ) {
			as_unschedule_all_actions( 'ced_amazon_inventory_scheduler_job_' . $seller_id, $seller_args );
		}
		as_schedule_recurring_action( time(), 600, 'ced_amazon_inventory_scheduler_job_' . $seller_id, $seller_args );
		update_option( 'ced_amazon_inventory_scheduler_job_' . $seller_id, $price_schedule );


		if ( as_has_scheduled_action( 'ced_amazon_existing_products_sync_job_' . $seller_id, $seller_args ) ) {
			as_unschedule_all_actions( 'ced_amazon_existing_products_sync_job_' . $seller_id, $seller_args );
		}
		as_schedule_recurring_action( time(), 600, 'ced_amazon_existing_products_sync_job_' . $seller_id, $seller_args );
		update_option( 'ced_amazon_existing_products_sync_job_' . $seller_id, $price_schedule );

		

		$seller_id = str_replace( '|', '%7C', $seller_id );
		wp_safe_redirect( admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=setup-amazon&part=configuration&user_id=' . $user_id . '&seller_id=' . $seller_id );


	} elseif ( isset( $_POST['reset_global_settings'] ) ) {
		$ced_amazon_global_settings = get_option( 'ced_amazon_global_settings', array() );
		unset( $ced_amazon_global_settings[ $seller_id ] );
		update_option( 'ced_amazon_global_settings', $ced_amazon_global_settings );

		delete_option( 'ced_amazon_inventory_scheduler_job_' . $seller_id );
		delete_option( 'ced_amazon_order_scheduler_job_' . $seller_id );
		delete_option( 'ced_amazon_existing_products_sync_job_' . $seller_id );
		delete_option( 'ced_amazon_catalog_asin_sync_job_' . $seller_id );
		delete_option( 'ced_amazon_catalog_asin_sync_page_number_' . $seller_id );

		if ( as_has_scheduled_action( 'ced_amazon_price_scheduler_job_' . $seller_id, $seller_args ) ) {
			as_unschedule_all_actions( 'ced_amazon_price_scheduler_job_' . $seller_id, $seller_args );
		}
		
		if ( as_has_scheduled_action( 'ced_amazon_inventory_scheduler_job_' . $seller_id, $seller_args ) ) {
			as_unschedule_all_actions( 'ced_amazon_inventory_scheduler_job_' . $seller_id, $seller_args );
		}

		if ( as_has_scheduled_action( 'ced_amazon_order_scheduler_job_' . $seller_id, $seller_args ) ) {
			as_unschedule_all_actions( 'ced_amazon_order_scheduler_job_' . $seller_id, $seller_args );
		}

		if ( as_has_scheduled_action( 'ced_amazon_existing_products_sync_job_' . $seller_id, $seller_args ) ) {
			as_unschedule_all_actions( 'ced_amazon_existing_products_sync_job_' . $seller_id, $seller_args );
		}

		if ( as_has_scheduled_action( 'ced_amazon_catalog_asin_sync_job_' . $seller_id, $seller_args ) ) {
			as_unschedule_all_actions( 'ced_amazon_catalog_asin_sync_job_' . $seller_id, $seller_args );
		}


	}
}

$renderDataOnGlobalSettings = get_option( 'ced_amazon_global_settings', false );
?>

<div class="woocommerce-progress-form-wrapper">
	<h2 style="text-align: left;"> <?php echo esc_html__( 'Amazon for WooCommerce: Onboarding', 'amazon-for-woocommerce' ); ?></h2>
	<ol class="wc-progress-steps ced-progress">
		<li class="done"> <?php echo esc_html__( 'Global Options', 'amazon-for-woocommerce' ); ?></li>
		<li class="active"> <?php echo esc_html__( 'General Settings', 'amazon-for-woocommerce' ); ?></li>
		<li class=""><?php echo esc_html__( 'Done!', 'amazon-for-woocommerce' ); ?></li>
	</ol>
	<div class="wc-progress-form-content woocommerce-importer">
		<header>
			<h2><?php echo esc_html__( 'General Settings', 'amazon-for-woocommerce' ); ?></h2>
		</header>

		<header>
			<form  method="post" >
				<h3><?php echo esc_html__( 'Listings Configuration', 'amazon-for-woocommerce' ); ?></h3>
				<p><?php echo esc_html__( 'Effortlessly adjust Amazon listing prices and WooCommerce stock levels: Increase or decrease prices and efficiently manage inventory.', 'amazon-for-woocommerce' ); ?></p>
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label for="woocommerce_currency">
								<?php echo esc_html__( 'Column name', 'amazon-for-woocommerce' ); ?> 
								</label>
							</th>
							<th scope="row" class="titledesc">
								<label for="woocommerce_currency">
								<?php echo esc_html__( 'Map to Options', 'amazon-for-woocommerce' ); ?>
								</label>
							</th>
							<th scope="row" class="titledesc">
								<label for="woocommerce_currency">
								</label>
							</th>
						</tr>
						<tr>
							<th scope="row" class="titledesc">
								<label for="woocommerce_currency">
									<?php echo esc_html__( 'Stock Levels', 'amazon-for-woocommerce' ); ?> <?php print_r( wc_help_tip( 'Stock level, also called inventory level, indicates the quantity of a particular product or product that you own on any platform.', 'amazon-for-woocommerce' ) ); ?>
								</label>
							</th>
							<td class="forminp forminp-select">
								<?php
									$listing_stock = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_listing_stock'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_listing_stock'] : '';
									$stock_type    = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_stock_type'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_stock_type'] : '';
								?>
							   

								<select style="width: 100%;" id="bulk-action-selector-top" name="ced_amazon_global_settings[ced_amazon_product_stock_type]" data-fieldId="ced_amazon_product_stock_type">
									<option value=""><?php echo esc_html__( 'Select', 'amazon-for-woocommerce' ); ?></option>
									<option <?php echo ( 'MaxStock' == $stock_type ) ? 'selected' : ''; ?> value="MaxStock"><?php echo esc_html__( 'Maximum Stock', 'amazon-for-woocommerce' ); ?></option>
								</select> 
							</td>
							<td class="forminp forminp-select">
							
								<input style="width: 100%; min-width:50px;" placeholder="Enter Value" type="number"  value="<?php echo esc_attr( $listing_stock ); ?>" id="ced_amazon_listing_stock" name="ced_amazon_global_settings[ced_amazon_listing_stock]" min="1" >
												
							</td>
						</tr>
						<tr>
							<th scope="row" class="titledesc">
								<label for="woocommerce_currency">
									<?php echo esc_html__( 'Markup', 'amazon-for-woocommerce' ); ?> <?php print_r( wc_help_tip( 'Markup is the amount you include in prices to earn profit while selling on Amazon.', 'amazon-for-woocommerce' ) ); ?>
								</label>
							</th>
							<td class="forminp forminp-select">
								<?php
									$markup_type = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_markup_type'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_markup_type'] : '';
								?>
								<select style="width: 100%;"  id="bulk-action-selector-top" name="ced_amazon_global_settings[ced_amazon_product_markup_type]" data-fieldId="ced_amazon_product_markup">
									<option value=""><?php echo esc_html__( 'Select', 'amazon-for-woocommerce' ); ?></option>
									<option <?php echo ( 'Fixed_Increased' == $markup_type ) ? 'selected' : ''; ?> value="Fixed_Increased"><?php echo esc_html__( 'Fixed Increment', 'amazon-for-woocommerce' ); ?></option>
									<option <?php echo ( 'Fixed_Decreased' == $markup_type ) ? 'selected' : ''; ?> value="Fixed_Decreased"><?php echo esc_html__( 'Fixed Decrement', 'amazon-for-woocommerce' ); ?></option>
									<option <?php echo ( 'Percentage_Increased' == $markup_type ) ? 'selected' : ''; ?> value="Percentage_Increased"><?php echo esc_html__( 'Percentage Increment', 'amazon-for-woocommerce' ); ?></option>
									<option <?php echo ( 'Percentage_Decreased' == $markup_type ) ? 'selected' : ''; ?> value="Percentage_Decreased"><?php echo esc_html__( 'Percentage Decrement', 'amazon-for-woocommerce' ); ?></option>
								</select>
													
							</td>
							<td class="forminp forminp-select">
								<?php
									$markup_price = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_markup'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_markup'] : '';
								?>

								<input style="width: 100%; min-width:50px;" placeholder="Enter Value" type="number" value="<?php echo esc_attr( $markup_price ); ?>" id="ced_amazon_product_markup" name="ced_amazon_global_settings[ced_amazon_product_markup]" min="1" >
								
							</td>
						</tr>
					</tbody>
				</table>

							
				<div class="wc-actions">
					<?php wp_nonce_field( 'ced_amazon_setting_page_nonce', 'ced_amazon_setting_nonce' ); ?>
					<button type="submit" class="components-button is-secondary general_settings_reset_button" id="rest_global_settings" name="reset_global_settings" ><?php echo esc_html__( 'Reset all values', 'amazon-for-woocommerce' ); ?></button>
					<button style="float: right;" type="submit" name="global_settings" class="components-button is-primary button-next"><?php echo esc_html__( 'Save and continue', 'amazon-for-woocommerce' ); ?></button>
					<a style="float: right;" data-attr='3' id="ced_amazon_continue_wizard_button" href="<?php echo esc_url( admin_url( 'admin.php?page=sales_channel&channel=amazon&section=setup-amazon&part=configuration&user_id=' . $user_id . '&seller_id=' . $seller_id ) ); ?>" class="components-button woocommerce-admin-dismiss-notification"><?php echo esc_html__( 'Skip', 'amazon-for-woocommerce' ); ?></a>
				</div>

			</form>
		</header>

	</div>
</div>
