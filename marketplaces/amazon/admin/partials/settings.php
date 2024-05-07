<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$part              = isset( $_GET['part'] ) ? sanitize_text_field( $_GET['part'] ) : '';
$current_page      = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
$user_id           = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
$seller_id         = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
$sellernextShopIds = get_option( 'ced_amazon_sellernext_shop_ids', array() );
$amazon_accounts   = get_option( 'ced_amzon_configuration_validated', array() );


if ( empty( $seller_id ) ) {
	$seller_id = $sellernextShopIds[ $user_id ]['ced_mp_seller_key'];
}
if ( isset( $part ) && ! empty( $part ) ) {
	$sellernextShopIds[ $user_id ]['ced_amz_current_step'] = 2;
	update_option( 'ced_amazon_sellernext_shop_ids', $sellernextShopIds );
}


$seller_args = array( $seller_id );

function ced_amazon_profile_dropdown( $field_id, $metakey_val ) {

	global $wpdb;
	$results = $wpdb->get_results( "SELECT DISTINCT meta_key FROM {$wpdb->prefix}postmeta WHERE meta_key NOT LIKE '%wcf%' AND meta_key NOT LIKE '%elementor%' AND meta_key NOT LIKE '%_menu%'", 'ARRAY_A' );
	foreach ( $results as $key => $meta_key ) {
		$post_meta_keys[] = $meta_key['meta_key'];
	}
	$selectDropdownHTML = '';
	$selectDropdownHTML = '<select style="width: 100%;" class="select2 custom_category_attributes_select"  name="' . $field_id . '">';

	ob_start();
	$selectDropdownHTML .= '<option value=""> -- select -- </option>';
	$selected_value2     = isset( $metakey_val ) ? $metakey_val : '';
	if ( ! empty( $post_meta_keys ) ) {
		$post_meta_keys      = array_unique( $post_meta_keys );
		$selectDropdownHTML .= '<optgroup label="Custom Fields">';
		foreach ( $post_meta_keys as $key7 => $p_meta_key ) {
			$selected = '';
			if ( $selected_value2 == $p_meta_key ) {
				$selected = 'selected';
			}
			$selectDropdownHTML .= '<option ' . $selected . ' value="' . $p_meta_key . '">' . $p_meta_key . '</option>';
		}
	}
	$selectDropdownHTML .= '</select>';
	return $selectDropdownHTML;
}


?>

<?php
$file = CED_AMAZON_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $file ) ) {
	require_once $file;
}

if ( isset( $_POST['ced_amazon_setting_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_setting_nonce'] ), 'ced_amazon_setting_page_nonce' ) ) {
	if ( isset( $_POST['global_settings'] ) ) {
		$objDateTime                            = new DateTime( 'NOW' );
		$timestamp                              = $objDateTime->format( 'Y-m-d\TH:i:s\Z' );
		$global_setting_data                    = get_option( 'ced_amazon_global_settings', array() );
		$settings                               = array();
		$sanitized_array                        = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
		$settings                               = get_option( 'ced_amazon_global_settings', array() );
		$settings[ $seller_id ]                 = isset( $sanitized_array['ced_amazon_global_settings'] ) ? ( $sanitized_array['ced_amazon_global_settings'] ) : array();
		$settings[ $seller_id ]['last_updated'] = $timestamp;


		$global_options_data                               = get_option( 'ced_amazon_general_options', array() );
		$global_options_data[ $seller_id ]                 = isset( $sanitized_array['ced_amazon_general_options'] ) ? ( $sanitized_array['ced_amazon_general_options'] ) : array();
		$global_options_data[ $seller_id ]['last_updated'] = $timestamp;
		update_option( 'ced_amazon_general_options', $global_options_data );

		if ( isset( $part ) && ! empty( $part ) ) {
			$sellernextShopIds                                     = get_option( 'ced_amazon_sellernext_shop_ids', array() );
			$sellernextShopIds[ $user_id ]['ced_amz_current_step'] = 3;
			update_option( 'ced_amazon_sellernext_shop_ids', $sellernextShopIds );
		}

		update_option( 'ced_amazon_global_settings', $settings );

		$price_schedule = isset( $sanitized_array['ced_amazon_global_settings']['ced_amazon_price_schedule_info'] ) && '' != $sanitized_array['ced_amazon_global_settings']['ced_amazon_price_schedule_info'] ? 'ced_amazon_10min' : wp_clear_scheduled_hook( 'ced_amazon_price_scheduler_job_' . $seller_id );

		$inventory_schedule       = isset( $sanitized_array['ced_amazon_global_settings']['ced_amazon_inventory_schedule_info'] ) && '' != $sanitized_array['ced_amazon_global_settings']['ced_amazon_inventory_schedule_info'] ? 'ced_amazon_10min' : wp_clear_scheduled_hook( 'ced_amazon_inventory_scheduler_job_' . $seller_id );
		$order_schedule           = isset( $sanitized_array['ced_amazon_global_settings']['ced_amazon_order_schedule_info'] ) && '' != $sanitized_array['ced_amazon_global_settings']['ced_amazon_order_schedule_info'] ? 'ced_amazon_10min' : wp_clear_scheduled_hook( 'ced_amazon_order_scheduler_job_' . $seller_id );
		$existing_product_sync    = isset( $sanitized_array['ced_amazon_global_settings']['ced_amazon_existing_products_sync'] ) && '' != $sanitized_array['ced_amazon_global_settings']['ced_amazon_existing_products_sync'] ? 'ced_amazon_10min' : wp_clear_scheduled_hook( 'ced_amazon_existing_products_sync_job_' . $seller_id );
		$amazon_catalog_asin_sync = isset( $sanitized_array['ced_amazon_global_settings']['ced_amazon_catalog_asin_sync'] ) && '' != $sanitized_array['ced_amazon_global_settings']['ced_amazon_catalog_asin_sync'] ? 'ced_amazon_10min' : wp_clear_scheduled_hook( 'ced_amazon_catalog_asin_sync_job_' . $seller_id );


		$current_price_sync = isset( $global_setting_data[ $seller_id ]['ced_amazon_price_schedule_info'] ) ? $global_setting_data[ $seller_id ]['ced_amazon_price_schedule_info'] : 0;
		if ( $current_price_sync !== $price_schedule ) {

			if ( as_has_scheduled_action( 'ced_amazon_price_scheduler_job_' . $seller_id, $seller_args ) ) {
				as_unschedule_all_actions( 'ced_amazon_price_scheduler_job_' . $seller_id, $seller_args );
			}

			if ( ! empty( $price_schedule ) ) {
				as_schedule_recurring_action( time(), 600, 'ced_amazon_price_scheduler_job_' . $seller_id, $seller_args );
				update_option( 'ced_amazon_price_scheduler_job_' . $seller_id, $price_schedule );

			}

		}


		$current_inventory_sync = isset( $global_setting_data[ $seller_id ]['ced_amazon_inventory_schedule_info'] ) ? $global_setting_data[ $seller_id ]['ced_amazon_inventory_schedule_info'] : 0;
		if ( $current_inventory_sync !== $inventory_schedule ) {

			if ( as_has_scheduled_action( 'ced_amazon_inventory_scheduler_job_' . $seller_id, $seller_args ) ) {
				as_unschedule_all_actions( 'ced_amazon_inventory_scheduler_job_' . $seller_id, $seller_args );
			}

			if ( ! empty( $inventory_schedule ) ) {
				as_schedule_recurring_action( time(), 600, 'ced_amazon_inventory_scheduler_job_' . $seller_id, $seller_args );
				update_option( 'ced_amazon_inventory_scheduler_job_' . $seller_id, $price_schedule );

			}

		}

		$current_order_sync = isset( $global_setting_data[ $seller_id ]['ced_amazon_order_schedule_info'] ) ? $global_setting_data[ $seller_id ]['ced_amazon_order_schedule_info'] : 0;
		if ( $current_order_sync !== $order_schedule ) {

			if ( as_has_scheduled_action( 'ced_amazon_order_scheduler_job_' . $seller_id, $seller_args ) ) {
				as_unschedule_all_actions( 'ced_amazon_order_scheduler_job_' . $seller_id, $seller_args );
			}

			if ( ! empty( $order_schedule ) ) {
				as_schedule_recurring_action( time(), 600, 'ced_amazon_order_scheduler_job_' . $seller_id, $seller_args );
				update_option( 'ced_amazon_order_scheduler_job_' . $seller_id, $price_schedule );

			}

		}

		$current_exist_product_sync = isset( $global_setting_data[ $seller_id ]['ced_amazon_existing_products_sync'] ) ? $global_setting_data[ $seller_id ]['ced_amazon_existing_products_sync'] : 0;
		if ( $current_exist_product_sync !== $existing_product_sync ) {

			if ( as_has_scheduled_action( 'ced_amazon_existing_products_sync_job_' . $seller_id, $seller_args ) ) {
				as_unschedule_all_actions( 'ced_amazon_existing_products_sync_job_' . $seller_id, $seller_args );
			}

			if ( ! empty( $existing_product_sync ) ) {
				as_schedule_recurring_action( time(), 600, 'ced_amazon_existing_products_sync_job_' . $seller_id, $seller_args );
				update_option( 'ced_amazon_existing_products_sync_job_' . $seller_id, $price_schedule );

			}

		}

		$current_asin_sync = isset( $global_setting_data[ $seller_id ]['ced_amazon_catalog_asin_sync'] ) ? $global_setting_data[ $seller_id ]['ced_amazon_catalog_asin_sync'] : 0;
		if ( $current_asin_sync !== $amazon_catalog_asin_sync ) {

			if ( as_has_scheduled_action( 'ced_amazon_catalog_asin_sync_job_' . $seller_id, $seller_args ) ) {
				as_unschedule_all_actions( 'ced_amazon_catalog_asin_sync_job_' . $seller_id, $seller_args );
			}

			if ( ! empty( $amazon_catalog_asin_sync ) ) {
				as_schedule_recurring_action( time(), 600, 'ced_amazon_catalog_asin_sync_job_' . $seller_id, $seller_args );
				update_option( 'ced_amazon_catalog_asin_sync_job_' . $seller_id, $price_schedule );

			}

		}

		$message = 'saved';

	} elseif ( isset( $_POST['reset_global_settings'] ) ) {

		$ced_amazon_global_settings = get_option( 'ced_amazon_global_settings', array() );
		unset( $ced_amazon_global_settings[ $seller_id ] );
		update_option( 'ced_amazon_global_settings', $ced_amazon_global_settings );

		delete_option( 'ced_amazon_inventory_scheduler_job_' . $seller_id );
		delete_option( 'ced_amazon_price_scheduler_job_' . $seller_id );
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

		$message = 'reset';
	}

	$admin_success_notice = '<div class="saved_container" ><p class="text-green-800"> Your configuration has been ' . esc_html__( $message ) . ' ! </p> </div>';
	print_r( $admin_success_notice );

}


$global_setting_data = get_option( 'ced_amazon_global_settings', array() );


?>

 

<form action="" method="post">
	<?php
	$renderDataOnGlobalSettings = get_option( 'ced_amazon_global_settings', false );
	?>

	<div
		class="components-card is-size-medium woocommerce-table pinterest-for-woocommerce-landing-page__faq-section css-1xs3c37-CardUI e1q7k77g0">
		<div class="components-panel ced_amazon_settings_new">
			<div class="wc-progress-form-content woocommerce-importer ced-padding">


			  <div class="ced-faq-wrapper">
					<input class="ced-faq-trigger" id="ced-faq-wrapper-six" type="checkbox" checked ><label class="ced-faq-title" for="ced-faq-wrapper-six">Orders Import Settings</label>
					<div class="ced-faq-content-wrap">
						<div class="ced-faq-content-holder">
							<div class="ced-form-accordian-wrap">
								<div class="wc-progress-form-content woocommerce-importer">
									<header>
										
										<table class="form-table">
											<tbody>

												<tr>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Use Amazon Order Number', 'amazon-for-woocommerce' ); ?>
															<?php print_r( wc_help_tip( 'Check this option if you want to create Amazon orders on WooCommerce using Amazon order number.', 'amazon-for-woocommerce' ) ); ?>
														</label>
													</th>
													<td class="forminp forminp-select">
														<?php
														$ced_use_amz_order_no = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_use_amz_order_no'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_use_amz_order_no'] : '';
														$checked = '';
														if ( !empty($ced_use_amz_order_no) && '1' == $ced_use_amz_order_no ) {
																$checked = 'checked';
														}
														?>
														<input <?php echo esc_attr($checked); ?> type="checkbox" class="" value="1" name="ced_amazon_global_settings[ced_use_amz_order_no]" data-fieldId="ced_use_amz_order_no" />
												
													</td>
												</tr>

												<tr >
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Create Order in Store Currency', 'amazon-for-woocommerce' ); ?>
															<?php print_r( wc_help_tip( 'By default, we will be creating orders using Amazon store currency.', 'amazon-for-woocommerce' ) ); ?>
														</label>
													</th>
													<td class="forminp forminp-select">
														<?php
														$ced_amazon_order_currency = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_order_currency'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_order_currency'] : '';
														$checked2 = '';
														if ( !empty($ced_amazon_order_currency) && '1' == $ced_amazon_order_currency ) {
																$checked2 = 'checked';
														}
														?>
														
														<input id="ced_amazon_order_currency" <?php echo esc_attr($checked2); ?> type="checkbox" class="" value="1" name="ced_amazon_global_settings[ced_amazon_order_currency]" data-fieldId="ced_amazon_order_currency" />
												
													</td>
												</tr>

												<?php

												if ( 'checked' == $checked2 ) {
													$style2 = 'display:contents';
												} else {
													$style2 = 'display:none';
												}

												?>

												<tr class="ced_amz_currency_convert_row" style="<?php echo esc_attr($style2); ?>">
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Currency Conversion Rate', 'amazon-for-woocommerce' ); ?>
															<?php print_r( wc_help_tip( 'Convert your Amazon order revenue to WooCommerce currency', 'amazon-for-woocommerce' ) ); ?>
														</label>
													</th>
													
													<td class="forminp forminp-select"> 
														<?php 
														$ced_amazon_currency_convert_rate = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_currency_convert_rate'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_currency_convert_rate'] : '';
														
														?>
														 

															<input  type="text" inputmode="decimal" 
																pattern="[1-9]*[.,]?[1-9]*"
																value="<?php echo esc_attr( $ced_amazon_currency_convert_rate ); ?>"
																placeholder="Enter Value" id="ced_amazon_currency_convert_rate"
																name="ced_amazon_global_settings[ced_amazon_currency_convert_rate]"  >
																
													</td>

													<td class="forminp forminp-select">
														<i>By default its value is 1.</i>
													</td>

												</tr>


												<!-- test -->

												<tr >
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Amazon orders time limit', 'amazon-for-woocommerce' ); ?>
															<?php print_r( wc_help_tip( 'Time in hours of which you want to fetch Amazon orders', 'amazon-for-woocommerce' ) ); ?>
														</label>
													</th>
													
													<td class="forminp forminp-select"> 
														<?php 
														$ced_amazon_order_sync_time_limit = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_order_sync_time_limit'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_order_sync_time_limit'] : '';
														
														?>
														 

															<input  type="text" inputmode="decimal" value="<?php echo esc_attr( $ced_amazon_order_sync_time_limit ); ?>"
																placeholder="Enter Value" id="ced_amazon_order_sync_time_limit"
																name="ced_amazon_global_settings[ced_amazon_order_sync_time_limit]"  >
																
													</td>

													<td class="forminp forminp-select">
														<i>By default we fetch orders of last 24 hours.</i>
													</td>

												</tr>



												<!-- test -->


											</tbody>
										</table>
									</header>
								</div>
							</div>
						</div>
					</div>
				</div>


				<div class="ced-faq-wrapper">
					<input class="ced-faq-trigger" id="ced-faq-wrapper-one" type="checkbox" ><label
						class="ced-faq-title" for="ced-faq-wrapper-one"><?php echo esc_html__( 'General Settings', 'amazon-for-woocommerce' ); ?></label>
					<div class="ced-faq-content-wrap">
						<div class="ced-faq-content-holder">
							<div class="ced-form-accordian-wrap">
								<div class="wc-progress-form-content woocommerce-importer">
									<header>
										
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
															<?php echo esc_html__( 'Custom Value', 'amazon-for-woocommerce' ); ?>
														</label>
													</th>
												</tr>
												<tr>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Stock Levels', 'amazon-for-woocommerce' ); ?>
															<?php print_r( wc_help_tip( 'Stock level, also called inventory level, indicates the quantity of a particular product or product that you own on any platform', 'amazon-for-woocommerce' ) ); ?>
														</label>
													</th>
													<td class="forminp forminp-select">
														<?php
														$listing_stock = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_listing_stock'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_listing_stock'] : '';
														$stock_type    = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_stock_type'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_stock_type'] : '';
														?>

														<select style="width: 100%;"
															name="ced_amazon_global_settings[ced_amazon_product_stock_type]"
															data-fieldId="ced_amazon_product_stock_type">
															<option value="">
																<?php echo esc_html__( 'Select', 'amazon-for-woocommerce' ); ?>
															</option>
															<option <?php echo ( 'MaxStock' == $stock_type ) ? 'selected' : ''; ?> value="MaxStock"><?php echo esc_html__( 'Maximum Stock', 'amazon-for-woocommerce' ); ?>
															</option>
														</select>

													</td>
													<td class="forminp forminp-select">
														<input style="width: 100%;min-width:50px;" type="number"
															value="<?php echo esc_attr( $listing_stock ); ?>"
															placeholder="Enter Value" id="ced_amazon_listing_stock"
															name="ced_amazon_global_settings[ced_amazon_listing_stock]"
															min="1" >
															
													</td>
												</tr>
												<tr>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Markup', 'amazon-for-woocommerce' ); ?>
															<?php print_r( wc_help_tip( 'Markup is the amount you include in prices to earn profit while selling on Amazon.', 'amazon-for-woocommerce' ) ); ?>
														</label>
													</th>
													<td class="forminp forminp-select">
														<?php
														$markup_type = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_markup_type'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_markup_type'] : '';
														?>
														<select style="width: 100%;"
															name="ced_amazon_global_settings[ced_amazon_product_markup_type]"
															data-fieldId="ced_amazon_product_markup">
															<option value="">
																<?php echo esc_html__( 'Select', 'amazon-for-woocommerce' ); ?>
															</option>
															<option <?php echo ( 'Fixed_Increased' == $markup_type ) ? 'selected' : ''; ?> value="Fixed_Increased"><?php echo esc_html__( 'Fixed Increment', 'amazon-for-woocommerce' ); ?></option>
															<option <?php echo ( 'Fixed_Decreased' == $markup_type ) ? 'selected' : ''; ?> value="Fixed_Decreased"><?php echo esc_html__( 'Fixed Decrement', 'amazon-for-woocommerce' ); ?></option>
															<option <?php echo ( 'Percentage_Increased' == $markup_type ) ? 'selected' : ''; ?> value="Percentage_Increased"><?php echo esc_html__( 'Percentage Increment', 'amazon-for-woocommerce' ); ?></option>
															<option <?php echo ( 'Percentage_Decreased' == $markup_type ) ? 'selected' : ''; ?> value="Percentage_Decreased"><?php echo esc_html__( 'Percentage Decrement', 'amazon-for-woocommerce' ); ?></option>
														</select>
														<?php
														$markup_price = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_markup'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_markup'] : '';
														?>

													</td>
													<td class="forminp forminp-select">
														<input style="width: 100%;min-width:50px;" type="number"
															value="<?php echo esc_attr( $markup_price ); ?>"
															placeholder="Enter Value" id="ced_amazon_product_markup"
															name="ced_amazon_global_settings[ced_amazon_product_markup]" min="1" >

													</td>
												</tr>

											</tbody>
										</table>

									</header>
								</div>
							</div>
						</div>
					</div>
				</div>

				


				<?php

				$optionsFile = CED_AMAZON_DIRPATH . 'admin/partials/globalOptions.php';
				if ( file_exists( $optionsFile ) ) {
					require_once $optionsFile;
				}


				$showOnlyCustomFieldsMapping = false;

				?>
				<div class="ced-faq-wrapper">
					<input class="ced-faq-trigger" id="ced-faq-wrapper-three" type="checkbox"><label
						class="ced-faq-title" for="ced-faq-wrapper-three"><?php echo esc_html__( 'Global Options', 'amazon-for-woocommerce' ); ?></label>
					<div class="ced-faq-content-wrap">
						<div class="ced-faq-content-holder">
							<div class="ced-form-accordian-wrap">
								<div class="wc-progress-form-content woocommerce-importer">
									<header>
										<table class="form-table">
											<tbody>

												<tr valign="top">
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
														<?php echo esc_html__( 'Attributes', 'amazon-for-woocommerce' ); ?>
														</label>
													</th>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
														<?php echo esc_html__( 'Map to Fields', 'amazon-for-woocommerce' ); ?>
														</label>
													</th>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
														<?php echo esc_html__( 'Custom Value', 'amazon-for-woocommerce' ); ?>
														</label>
													</th>
												</tr>

												<?php
												$ced_amazon_general_options = get_option( 'ced_amazon_general_options', array() );
												$ced_amazon_general_options = isset( $ced_amazon_general_options[ $seller_id ] ) ? $ced_amazon_general_options[ $seller_id ] : array();
												global $wpdb;
												$results = $wpdb->get_results( "SELECT DISTINCT meta_key FROM {$wpdb->prefix}postmeta", 'ARRAY_A' );
												$query   = $wpdb->get_results( $wpdb->prepare( "SELECT `meta_value` FROM  {$wpdb->prefix}postmeta WHERE `meta_key` LIKE %s", '_product_attributes' ), 'ARRAY_A' );


												foreach ( $options as $opt_key => $opt_value ) {

													?>

													<tr>
														<th scope="row" class="titledesc">
															<label for="woocommerce_currency">
																<?php echo esc_html__( $opt_value['name'], 'amazon-for-woocommerce' ); ?>
																<?php print_r( wc_help_tip( $opt_value['tooltip'], 'amazon-for-woocommerce' ) ); ?>
															</label>
														</th>

														<td class="forminp forminp-select">

															<?php


															$selected_value2    = isset( $ced_amazon_general_options[ $opt_key ]['metakey'] ) ? $ced_amazon_general_options[ $opt_key ]['metakey'] : '';
															$selectDropdownHTML = '<select style="width: 100%;" class="ced_amazon_search_item_sepcifics_mapping select2" id="" name="ced_amazon_general_options[' . $opt_key . '][metakey]" >';
															foreach ( $results as $key2 => $meta_key ) {
																$post_meta_keys[] = $meta_key['meta_key'];
															}
															$custom_prd_attrb = array();
															$attrOptions      = array();

															if ( ! empty( $query ) ) {
																foreach ( $query as $key3 => $db_attribute_pair ) {
																	foreach ( maybe_unserialize( $db_attribute_pair['meta_value'] ) as $key4 => $attribute_pair ) {

																		if ( 1 != $attribute_pair['is_taxonomy'] ) {
																			$custom_prd_attrb[] = $attribute_pair['name'];
																		}
																	}
																}
															}


															$attributes = wc_get_attribute_taxonomies();
															if ( ! empty( $attributes ) ) {
																foreach ( $attributes as $attributesObject ) {
																	$attrOptions[ 'umb_pattr_' . $attributesObject->attribute_name ] = $attributesObject->attribute_label;
																}
															}


															ob_start();
															$fieldID             = '{{*fieldID}}';
															$selectId            = $fieldID . '_attibuteMeta';
															$selectDropdownHTML .= '<option value=""> -- select -- </option>';

															if ( is_array( $attrOptions ) && ! empty( $attrOptions ) ) {
																$selectDropdownHTML .= '<optgroup label="Global Attributes">';
																foreach ( $attrOptions as $attrKey => $attrName ) {
																	$selected = '';
																	if ( $selected_value2 == $attrKey ) {
																		$selected = 'selected';
																	}
																	$selectDropdownHTML .= '<option ' . $selected . ' value="' . $attrKey . '">' . $attrName . '</option>';
																}
															}

															if ( ! empty( $custom_prd_attrb ) ) {
																$custom_prd_attrb    = array_unique( $custom_prd_attrb );
																$selectDropdownHTML .= '<optgroup label="Custom Attributes">';
																foreach ( $custom_prd_attrb as $key5 => $custom_attrb ) {
																	$selected = '';
																	if ( 'ced_cstm_attrb_' . esc_attr( $custom_attrb ) == $selected_value2 ) {
																		$selected = 'selected';
																	}
																	$selectDropdownHTML .= '<option ' . $selected . ' value="ced_cstm_attrb_' . esc_attr( $custom_attrb ) . '">' . esc_html( $custom_attrb ) . '</option>';
																}
															}

															if ( ! empty( $post_meta_keys ) ) {
																$post_meta_keys      = array_unique( $post_meta_keys );
																$selectDropdownHTML .= '<optgroup label="Custom Fields">';
																foreach ( $post_meta_keys as $key7 => $p_meta_key ) {
																	$selected = '';
																	if ( $selected_value2 == $p_meta_key ) {
																		$selected = 'selected';
																	}
																	$selectDropdownHTML .= '<option ' . $selected . ' value="' . $p_meta_key . '">' . $p_meta_key . '</option>';
																}
															}

															$selectDropdownHTML .= '</select>';

															print_r( $selectDropdownHTML );

															?>

														</td>
														<td class="forminp forminp-select">
															<?php
															if ( 'select' == $opt_value['type'] ) {
																?>
																<select class="select2"
																	name="<?php echo 'ced_amazon_general_options[' . esc_attr( $opt_key ) . '][default]'; ?>">
																	<option value=''>--Select--</option>
																	<?php
																	$selected_value = isset( $ced_amazon_general_options[ $opt_key ]['default'] ) ? $ced_amazon_general_options[ $opt_key ]['default'] : '';
																	foreach ( $opt_value['options'] as $key1 => $value ) {
																		$selected = '';
																		if ( $selected_value == $value ) {
																			$selected = 'selected';
																		}
																		?>
																		<option $selected value='<?php echo esc_attr( $value ); ?>'>
																			<?php echo esc_attr( $value ); ?> </option>
																		<?php
																	}
																	?>
																</select>
																<?php
															} else {
																?>

																<input type='text' style="width: 100%;min-width:50px;" 
																	value="<?php echo isset( $ced_amazon_general_options[ $opt_key ]['default'] ) ? esc_attr( $ced_amazon_general_options[ $opt_key ]['default'] ) : ''; ?>"
																	name="<?php echo 'ced_amazon_general_options[' . esc_attr( $opt_key ) . '][default]'; ?>" />
																<?php
															}
															?>
															
														</td>
													</tr>

													<?php
												}
												?>


											</tbody>
										</table>

									</header>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="ced-faq-wrapper">
					<input class="ced-faq-trigger" id="ced-faq-wrapper-two" type="checkbox" /><label
						class="ced-faq-title" for="ced-faq-wrapper-two"><?php echo esc_html__( 'Advanced Settings', 'amazon-for-woocommerce' ); ?></label>
					<div class="ced-faq-content-wrap">
						<div class="ced-faq-content-holder ced-advance-table-wrap">
							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row" class="titledesc">
											<label for="woocommerce_currency">
											<?php echo esc_html__( 'Fetch Amazon orders', 'amazon-for-woocommerce' ); ?>
												<?php print_r( wc_help_tip( 'Enable the setting to fetch Amazon orders automatically.', 'amazon-for-woocommerce' ) ); ?>
											</label>
										</th>
										<td class="forminp forminp-select">

											<?php
											$order_schedule = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_order_schedule_info'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_order_schedule_info'] : '';
											?>

											<div class="woocommerce-list__item-after">
												<label class="components-form-toggle 
												<?php
												if ( ! empty( $order_schedule ) ) {
													echo esc_attr( 'is-checked' );
												}
												?>
												">
													<input
														name="ced_amazon_global_settings[ced_amazon_order_schedule_info]"
														class="components-form-toggle__input ced-settings-checkbox"
														id="inspector-toggle-control-0" type="checkbox" 
														<?php
														if ( ! empty( $order_schedule ) ) {
															echo 'checked';
														}
														?>
														>
													<span class="components-form-toggle__track"></span>
													<span class="components-form-toggle__thumb"></span>
												</label>
											</div>

										</td>
									</tr>
									<tr>
										<th scope="row" class="titledesc">
											<label for="woocommerce_currency">
											<?php echo esc_html__( 'Update inventory on Amazon', 'amazon-for-woocommerce' ); ?>
												<?php print_r( wc_help_tip( 'Enable the setting to update inventory from WooCommerce to Amazon automatically.', 'amazon-for-woocommerce' ) ); ?>
											</label>
										</th>
										<td class="forminp forminp-select">
											<?php
											$inventory_schedule = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_inventory_schedule_info'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_inventory_schedule_info'] : '';
											?>
											<div class="woocommerce-list__item-after">
												<label class="components-form-toggle 
												<?php
												if ( ! empty( $inventory_schedule ) ) {
													echo esc_attr( 'is-checked' );
												}
												?>
												">
													<input
														name="ced_amazon_global_settings[ced_amazon_inventory_schedule_info]"
														class="components-form-toggle__input ced-settings-checkbox"
														id="inspector-toggle-control-0" type="checkbox" 
														<?php
														if ( ! empty( $inventory_schedule ) ) {
															echo 'checked';
														}
														?>
														>
													<span class="components-form-toggle__track"></span>
													<span class="components-form-toggle__thumb"></span>
												</label>
											</div>
										</td>
									</tr>

									

									<tr>
										<th scope="row" class="titledesc">
											<label for="woocommerce_currency">
											<?php echo esc_html__( 'Update price on Amazon', 'amazon-for-woocommerce' ); ?>
												<?php print_r( wc_help_tip( 'Enable the setting to update price from WooCommerce to Amazon automatically.', 'amazon-for-woocommerce' ) ); ?>
											</label>
										</th>
										<td class="forminp forminp-select">
											<?php
											$price_schedule = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_price_schedule_info'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_price_schedule_info'] : '';
											?>
											<div class="woocommerce-list__item-after">
												<label class="components-form-toggle 
												<?php
												if ( ! empty( $price_schedule ) ) {
													echo esc_attr( 'is-checked' );
												}
												?>
												">
													<input
														name="ced_amazon_global_settings[ced_amazon_price_schedule_info]"
														class="components-form-toggle__input ced-settings-checkbox"
														id="inspector-toggle-control-0" type="checkbox" 
														<?php
														if ( ! empty( $price_schedule ) ) {
															echo 'checked';
														}
														?>
														>
													<span class="components-form-toggle__track"></span>
													<span class="components-form-toggle__thumb"></span>
												</label>
											</div>
										</td>
									</tr>

									

									<tr>
										<th scope="row" class="titledesc">
											<label for="woocommerce_currency">
											<?php echo esc_html__( 'Existing products sync', 'amazon-for-woocommerce' ); ?>
												<?php print_r( wc_help_tip( 'Enable the scheduler to sync products automatically.', 'amazon-for-woocommerce' ) ); ?>
											</label>
										</th>
										<td class="forminp forminp-select">
											<?php
											$existing_product_sync = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_existing_products_sync'] ) ? ( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_existing_products_sync'] ) : '';
											?>

											<div class="woocommerce-list__item-after">
												<label class="components-form-toggle  
												<?php
												if ( ! empty( $existing_product_sync ) ) {
													echo esc_attr( 'is-checked' );
												}
												?>
												">
													<input
														name="ced_amazon_global_settings[ced_amazon_existing_products_sync]"
														class="components-form-toggle__input ced-settings-checkbox"
														id="inspector-toggle-control-0" type="checkbox" 
														<?php
														if ( ! empty( $existing_product_sync ) ) {
															echo 'checked';
														}
														?>
														>
													<span class="components-form-toggle__track"></span>
													<span class="components-form-toggle__thumb"></span>
												</label>
											</div>


										</td>
									</tr>
									<tr>
										<th scope="row" class="titledesc">
											<label for="woocommerce_currency">
											<?php echo esc_html__( 'ASIN sync', 'amazon-for-woocommerce' ); ?>
												<?php print_r( wc_help_tip( 'Enable the scheduler to start the ASIN sync.', 'amazon-for-woocommerce' ) ); ?>
											</label>
										</th>
										<td class="forminp forminp-select">
											<?php
											$amazon_catalog_asin_sync = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_catalog_asin_sync'] ) ? ( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_catalog_asin_sync'] ) : '';
											?>

											<div class="woocommerce-list__item-after">
												<label class="components-form-toggle 
												<?php
												if ( ! empty( $amazon_catalog_asin_sync ) ) {
													echo esc_attr( 'is-checked' );
												}
												?>
												">
													<input
														name="ced_amazon_global_settings[ced_amazon_catalog_asin_sync]"
														class="components-form-toggle__input ced-settings-checkbox ced-asin-sync-toggle-select"
														id="inspector-toggle-control-0" type="checkbox" 
														<?php
														if ( ! empty( $amazon_catalog_asin_sync ) ) {
															echo 'checked';
														}
														?>
														>
													<span class="components-form-toggle__track"></span>
													<span class="components-form-toggle__thumb"></span>
												</label>
											</div>

										</td>
										<?php
										if ( ! empty( $amazon_catalog_asin_sync ) ) {
											$style = 'display: contents';
										} else {
											$style = 'display: none';
										}
										?>
										

											<td colspan="4" class="ced_amazon_catalog_asin_sync_meta_row forminp forminp-select" style="<?php echo esc_attr( $style ); ?>" >											
							
											<?php
											$metakey_val = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_catalog_asin_sync_meta'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_catalog_asin_sync_meta'] : '';
											$html        = ced_amazon_profile_dropdown( 'ced_amazon_global_settings[ced_amazon_catalog_asin_sync_meta]', $metakey_val );

											$allowed_tags = array(
												'select'   => array(
													'style' => array(),
													'name' => array(),
													'class' => array(),
												),
												'optgroup' => array(
													'label' => array(),
												),
												'option'   => array(
													'value' => array(),
													'selected' => array(),
												),
											);


											echo wp_kses( $html, $allowed_tags );

											?>

										</td>
									</tr>

									
										

								</tbody>
							</table>
						</div>
						
					</div>
					
				</div>
				<div class="ced-margin-top">
		<?php
		wp_nonce_field( 'ced_amazon_setting_page_nonce', 'ced_amazon_setting_nonce' );
		?>
		<button id="save_global_settings" class="config_button components-button is-primary" style="float: right;"
			name="global_settings">
			<?php echo esc_html__( 'Save', 'amazon-for-woocommerce' ); ?>
		</button>
									</div>


			</div>
		</div>
	</div>

	
</form>

