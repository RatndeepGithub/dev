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

	$post_meta_keys = array();
	foreach ( $results as $key => $meta_key ) {
		$post_meta_keys[] = $meta_key['meta_key'];
	}

	$query = $wpdb->get_results( $wpdb->prepare( "SELECT `meta_value` FROM  {$wpdb->prefix}postmeta WHERE `meta_key` LIKE %s", '_product_attributes' ), 'ARRAY_A' );

	$custom_prd_attrb = array();
	if ( ! empty( $query ) ) {
		foreach ( $query as $key3 => $db_attribute_pair ) {
			foreach ( maybe_unserialize( $db_attribute_pair['meta_value'] ) as $key4 => $attribute_pair ) {
				if ( 1 != $attribute_pair['is_taxonomy'] ) {
					$custom_prd_attrb[] = $attribute_pair['name'];
				}
			}
		}
	}

	$attrOptions = array();
	$attributes  = wc_get_attribute_taxonomies();
	if ( ! empty( $attributes ) ) {
		foreach ( $attributes as $attributesObject ) {
			$attrOptions[ 'umb_pattr_' . $attributesObject->attribute_name ] = $attributesObject->attribute_label;
		}
	}

	$selectDropdownHTML = '';
	$selectDropdownHTML = '<select style="width: 100%;" class="select2 custom_category_attributes_select"  name="' . $field_id . '">';

	ob_start();
	$selectDropdownHTML .= '<option value=""> -- select -- </option>';

	$selected_value2 = isset( $metakey_val ) ? $metakey_val : '';

	if ( is_array( $attrOptions ) ) {
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

		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';


		$objDateTime         = new DateTime( 'NOW' );
		$timestamp           = $objDateTime->format( 'Y-m-d\TH:i:s\Z' );
		$global_setting_data = get_option( 'ced_amazon_global_settings', array() );
		$settings            = array();

		$sanitized_array = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
		$settings        = get_option( 'ced_amazon_global_settings', array() );

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


		$price_schedule           = isset( $sanitized_array['ced_amazon_global_settings']['ced_amazon_price_schedule_info'] ) && '' != $sanitized_array['ced_amazon_global_settings']['ced_amazon_price_schedule_info'] ? 'on' : '';
		$inventory_schedule       = isset( $sanitized_array['ced_amazon_global_settings']['ced_amazon_inventory_schedule_info'] ) && '' != $sanitized_array['ced_amazon_global_settings']['ced_amazon_inventory_schedule_info'] ? 'on' : '';
		$order_schedule           = isset( $sanitized_array['ced_amazon_global_settings']['ced_amazon_order_schedule_info'] ) && '' != $sanitized_array['ced_amazon_global_settings']['ced_amazon_order_schedule_info'] ? 'on' : '';
		$existing_products_sync   = isset( $sanitized_array['ced_amazon_global_settings']['ced_amazon_existing_products_sync'] ) && '' != $sanitized_array['ced_amazon_global_settings']['ced_amazon_existing_products_sync'] ? 'on' : '';
		$amazon_catalog_asin_sync = isset( $sanitized_array['ced_amazon_global_settings']['ced_amazon_catalog_asin_sync'] ) && '' != $sanitized_array['ced_amazon_global_settings']['ced_amazon_catalog_asin_sync'] ? 'on' : '';

		$ced_amazon_shipment_tracking_plugin = isset( $sanitized_array['ced_amazon_global_settings']['ced_amazon_shipment_tracking_plugin'] ) && '' != $sanitized_array['ced_amazon_global_settings']['ced_amazon_shipment_tracking_plugin'] ? 'on' : '';


		if ( ! empty( $price_schedule ) && 'on' == $price_schedule ) {
			update_option( 'ced_amazon_price_scheduler_job_' . $seller_id, $price_schedule );
		}
		if ( ! empty( $inventory_schedule ) && 'on' == $inventory_schedule ) {
			update_option( 'ced_amazon_inventory_scheduler_job_' . $seller_id, $inventory_schedule );
		}
		if ( ! empty( $order_schedule ) && 'on' == $order_schedule ) {
			update_option( 'ced_amazon_order_scheduler_job_' . $seller_id, $order_schedule );
		}
		if ( ! empty( $existing_products_sync ) && 'on' == $existing_products_sync ) {
			update_option( 'ced_amazon_existing_products_sync_job_' . $seller_id, $existing_products_sync );
		}
		if ( ! empty( $amazon_catalog_asin_sync ) && 'on' == $amazon_catalog_asin_sync ) {
			update_option( 'ced_amazon_catalog_asin_sync_job_' . $seller_id, $amazon_catalog_asin_sync );
		}

		if ( ! empty( $ced_amazon_shipment_tracking_plugin ) && 'on' == $ced_amazon_shipment_tracking_plugin ) {
			update_option( 'ced_amazon_shipment_schedule_job_' . $seller_id, $ced_amazon_shipment_tracking_plugin );
		}

		update_option( 'ced_amazon_global_settings', $settings );

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

		delete_option( 'ced_amazon_shipment_schedule_job_' . $seller_id );

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
													
														<?php ced_amazon_print_table_label( 'Use Amazon Order Number', 'Check this option if you want to create Amazon orders on WooCommerce using Amazon order number.', true ); ?>
													
													<td class="forminp forminp-select">
														<?php
														$ced_use_amz_order_no = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_use_amz_order_no'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_use_amz_order_no'] : '';
														$checked              = '';
														if ( ! empty( $ced_use_amz_order_no ) && '1' == $ced_use_amz_order_no ) {
																$checked = 'checked';
														}
														?>
														<input <?php echo esc_attr( $checked ); ?> type="checkbox" class="" value="1" name="ced_amazon_global_settings[ced_use_amz_order_no]" data-fieldId="ced_use_amz_order_no" />
												
													</td>
												</tr>

												<tr>
													
													   <?php ced_amazon_print_table_label( 'Email Notifications', 'Uncheck this option if you don\'t want to receive woocommerce email notifications for Amazon Orders', true ); ?>
													
													
													<td class="forminp forminp-select">
														<?php
														$ced_amz_stp_email_nfc = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amz_stp_email_nfc'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amz_stp_email_nfc'] : '';
														$checked               = 'checked';

														if ( empty( $ced_amz_stp_email_nfc ) || '1' !== $ced_amz_stp_email_nfc ) {
															$checked = '';
														}
														?>
														<input <?php echo esc_attr( $checked ); ?> type="checkbox" class="" value="1" name="ced_amazon_global_settings[ced_amz_stp_email_nfc]" data-fieldId="ced_amz_stp_email_nfc" />
												
													</td>
												</tr>


												<tr >
													
													<?php ced_amazon_print_table_label( 'Create order in WooCommerce store currency', 'By default, we will be creating Amazon orders in Amazon store currency.', true ); ?>
														
													
													<td class="forminp forminp-select">
														<?php
														$ced_amazon_order_currency = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_order_currency'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_order_currency'] : '';
														$checked2                  = '';
														if ( ! empty( $ced_amazon_order_currency ) && '1' == $ced_amazon_order_currency ) {
																$checked2 = 'checked';
														}
														?>
														
														<input id="ced_amazon_order_currency" <?php echo esc_attr( $checked2 ); ?> type="checkbox" class="" value="1" name="ced_amazon_global_settings[ced_amazon_order_currency]" data-fieldId="ced_amazon_order_currency" />
												
													</td>
												</tr>

												<?php

												$style2 = '';
												if ( 'checked' !== $checked2 ) {
													$style2 = 'display:none';
												}

												$ced_amz_conversion_type = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amz_conversion_type'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amz_conversion_type'] : '';

												?>

												<tr class="ced_amz_currency_convert_row" style="<?php echo esc_attr( $style2 ); ?>" >
												
													<th scope="row" class="titledesc"></th>

													<td class="forminp forminp-select" colspan="2" > 
														<p style="display: flex" >
															<input 
																<?php

																if ( 'manual' == $ced_amz_conversion_type ) {
																	echo esc_attr( 'checked' ); }
																?>
																 
															type="radio" class="ced_amz_fulfill_chn" value="manual" id="ced_amz_manual_curr_change"
															name="ced_amazon_global_settings[ced_amz_conversion_type]"  > Manual Currency Conversion </input> 
													
															<?php
																$ced_amazon_currency_convert_rate = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_currency_convert_rate'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_currency_convert_rate'] : '';

															if ( 'manual' == $ced_amz_conversion_type ) {
																$ced_conversion_display = 'display: flex;';
															} else {
																$ced_conversion_display = 'display: none';
															}
															?>

																<span class="ced_amazon_currency_convert_rate_container" style="<?php echo esc_attr( $ced_conversion_display ); ?>" >
																	<input  type="text" inputmode="decimal" style="margin-left: 63px;"
																		pattern="[1-9]*[.,]?[1-9]*"
																		value="<?php echo esc_attr( $ced_amazon_currency_convert_rate ); ?>"
																		placeholder="Enter Value" id="ced_amazon_currency_convert_rate"
																		name="ced_amazon_global_settings[ced_amazon_currency_convert_rate]"  >

																	<i style="margin-left: 63px;" >By default its value is 1.</i>	
															</span>
																
														 </p>
																
													</td>

												</tr>

												<tr class="ced_amz_currency_convert_row" style="<?php echo esc_attr( $style2 ); ?>" >
													<th scope="row" class="titledesc"></th>
													<td class="forminp forminp-select" colspan="2" > 

													<p style="display: flex" >
															<input 
																<?php
																if ( 'automatic' == $ced_amz_conversion_type ) {
																	echo esc_attr( 'checked' ); }
																?>
																 
															type="radio" class="ced_amz_fulfill_chn" value="automatic"  id="ced_amz_auto_curr_change"
															name="ced_amazon_global_settings[ced_amz_conversion_type]"  > Automatic Currency Conversion </input> 
													
															<?php

															if ( 'automatic' == $ced_amz_conversion_type ) {
																$ced_conversion_display = '';
															} else {
																$ced_conversion_display = 'display: none';
															}

																$ced_amazon_currency_conversion_plugin = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_currency_conversion_plugin'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_currency_conversion_plugin'] : '';

															?>

															<select  style="margin-left: 45px;<?php echo esc_attr( $ced_conversion_display ); ?>" class="ced_amazon_currency_conversion_plugin"
																name="ced_amazon_global_settings[ced_amazon_currency_conversion_plugin]"  >
																<option value="" >-- Select --</option> 

																<?php
																$comp_plugins = array( 'Curcy' );

																foreach ( $comp_plugins as $key => $val ) {
																	$selected = '';
																	if ( $val == $ced_amazon_currency_conversion_plugin ) {
																		$selected = 'selected';
																	}

																	?>
																	<option <?php echo esc_attr( $selected ); ?> value="<?php echo esc_attr( $val ); ?>" ><?php echo esc_attr( $val ); ?></option>
																<?php } ?>
															   

															</select>

															
														 </p>
														
																
													</td>

												</tr>


												<tr>
													
													   <?php ced_amazon_print_table_label( 'Fulfillment Channels', 'Check the fulfillment channels for which you want to import orders. By default we import order for both FBA AND FBM.', true ); ?>
													
													<td class="forminp forminp-select">
														<?php
														$ced_amz_fulfill_chn = isset( $renderDataOnGlobalSettings[ $seller_id ]['fulfillment_channels'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['fulfillment_channels'] : '';

														?>
														<p> <input 
														<?php
														if ( 'MFN' == $ced_amz_fulfill_chn ) {
															echo esc_attr( 'checked' ); }
														?>
														 type="radio" class="ced_amz_fulfill_chn" value="MFN"  name="ced_amazon_global_settings[fulfillment_channels]"  > Only FBM Orders </input> </p>
														<p> <input 
														<?php
														if ( 'AFN' == $ced_amz_fulfill_chn ) {
															echo esc_attr( 'checked' ); }
														?>
														 type="radio" class="ced_amz_fulfill_chn" value="AFN"  name="ced_amazon_global_settings[fulfillment_channels]"  > Only FBA Orders </input> </p>
														<p> <input 
														<?php
														if ( 'both' == $ced_amz_fulfill_chn || '' == $ced_amz_fulfill_chn ) {
															echo esc_attr( 'checked' ); }
														?>
														 type="radio" class="ced_amz_fulfill_chn" value="both"  name="ced_amazon_global_settings[fulfillment_channels]"  > Both Orders </input> </p>
												
													</td>
												</tr>

												<tr >
													
													  <?php ced_amazon_print_table_label( 'Amazon orders time limit', 'Time in hours of which you want to fetch Amazon orders', true ); ?>
													
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


												<!-- <tr >
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Shipment Tracking', 'amazon-for-woocommerce' ); ?>
															<?php print_r( wc_help_tip( 'Auto update tracking details on Amazon' ) ); ?>
														</label>
													</th>
													
													<td class="forminp forminp-select"> 
														<?php
														$ced_amazon_shipment_tracking_plugin = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_shipment_tracking_plugin'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_shipment_tracking_plugin'] : '';

														?>
														 
														 <select class="ced_amazon_shipment_tracking_plugin" name="ced_amazon_global_settings[ced_amazon_shipment_tracking_plugin]"  >
																<option value="" >-- Select --</option> 
																<?php
																$com_plugins = array(
																	'Advanced Shipment Tracking for WooCommerce' => 'Advanced Shipment Tracking for WooCommerce',
																	// 'Shipment Tracking by WooCommerce' => 'Shipment Tracking by WooCommerce'
																);

																foreach ( $com_plugins as $key => $val ) {
																	$selected = '';
																	if ( $key == $ced_amazon_shipment_tracking_plugin ) {
																		$selected = 'selected';
																	}

																	?>
																	<option <?php echo esc_attr( $selected ); ?> value="<?php echo esc_attr( $key ); ?>" > <?php echo esc_attr( $val ); ?></option>
																	
																<?php } ?>
															   

															</select>

														
																
													</td>

												</tr> -->



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
						class="ced-faq-title" for="ced-faq-wrapper-one"><?php echo esc_html__( 'Inventory Settings', 'amazon-for-woocommerce' ); ?></label>
					<div class="ced-faq-content-wrap">
						<div class="ced-faq-content-holder">
							<div class="ced-form-accordian-wrap">
								<div class="wc-progress-form-content woocommerce-importer">
									<header>
										
										<table class="form-table">
											<tbody>

												<tr>
													
													  <?php ced_amazon_print_table_label( 'Reserve Stock', 'Add the product stock/invenotry that you want to reserve fro WooCommerce store.', true ); ?>
													
													<td class="forminp forminp-select">
														<?php
														   $ced_amazon_rsrve_stck = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_rsrve_stck'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_rsrve_stck'] : '';
														?>

														<input style="width: 100%;min-width:50px;" type="number"
															value="<?php echo esc_attr( $ced_amazon_rsrve_stck ); ?>"
															placeholder="Enter reserve stock" id="ced_amazon_rsrve_stck"
															name="ced_amazon_global_settings[ced_amazon_rsrve_stck]"
															min="1" > 

													</td>
													<td class="forminp forminp-select">
														  
													</td>
												</tr>
												
												<!-- <tr>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Exclude Products', 'amazon-for-woocommerce' ); ?>
															<?php print_r( wc_help_tip( 'Upload a CSV file, containing SKUs which seller dont want to sync with Amazon', 'amazon-for-woocommerce' ) ); ?>
														</label>
													</th>
													<td class="forminp forminp-select">
														<?php
														   $ced_amazon_exclude_inventory_syncing_file = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_exclude_inventory_syncing_file'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_exclude_inventory_syncing_file'] : '';
														?>

														<div style="float: left; margin-bottom: 8px;" >
															<button type="button" class="components-button is-primary" id="ced_amazon_exclude_inventory_syncing_file" >Upload file</button> 
															 <input style="width: 100%;min-width:50px;" type="text"
																value="<?php echo esc_attr( $ced_amazon_exclude_inventory_syncing_file ); ?>"
																placeholder="U" id="ced_amazon_exclude_inventory_syncing_file"
																name="ced_amazon_global_settings[ced_amazon_exclude_inventory_syncing_file]" > 

														</div>		
														<p class="ced_sample_json_file_container" ><a class="ced_sample_json_file" href="<?php echo esc_attr( CED_AMAZON_DIRPATH . 'admin/js/sample.json' ); ?>" >See Sample CSV file structure</a></p>

														
													</td>
													<td class="forminp forminp-select">
														<?php

														if ( ! empty( $ced_amazon_exclude_inventory_syncing_file ) ) {

															$file_name = explode( '/', $ced_amazon_exclude_inventory_syncing_file );
															$file_name = is_array( $file_name ) && ! empty( $file_name ) ? end( $file_name ) : 'sample.csv';

															?>

															<p class="ced_amz_current_fba_file_cont" ><?php echo esc_attr( $file_name ); ?><span id="ced_amz_current_fba_file" link="<?php echo esc_attr( $ced_amazon_exclude_inventory_syncing_file ); ?>" 
															class="dashicons dashicons-download"></span> </p>
												
														<?php } ?>     
														</td>
												</tr> -->
												
											</tbody>
										</table>

									</header>
								</div>
							</div>
						</div>
					</div>
				</div>


				<div class="ced-faq-wrapper">
					<input class="ced-faq-trigger" id="ced-faq-wrapper-two" type="checkbox" ><label
						class="ced-faq-title" for="ced-faq-wrapper-two"><?php echo esc_html__( 'General Settings', 'amazon-for-woocommerce' ); ?></label>
					<div class="ced-faq-content-wrap">
						<div class="ced-faq-content-holder">
							<div class="ced-form-accordian-wrap">
								<div class="wc-progress-form-content woocommerce-importer">
									<header>
										
										<table class="form-table">
											<tbody>
												<tr valign="top">
													
														<?php

															ced_amazon_print_table_label( 'Column name', '', false );
															ced_amazon_print_table_label( 'Map to Options', '', false );
															ced_amazon_print_table_label( 'Custom Value', '', false );
														?>
													
												</tr>
												<tr>
													
													<?php ced_amazon_print_table_label( 'Stock Levels', 'Stock level, also called inventory level, indicates the quantity of a particular product or product that you own on any platform', true ); ?>
														
													
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
													
														<?php ced_amazon_print_table_label( 'Markup', 'Markup is the amount you include in prices to earn profit while selling on Amazon.', true ); ?>
									
													
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
															name="ced_amazon_global_settings[ced_amazon_product_markup]" min="0" step="0.01" >

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
													
													   <?php
														ced_amazon_print_table_label( 'Attributes', '', false );
														ced_amazon_print_table_label( 'Map to Fields', '', false );
														ced_amazon_print_table_label( 'Custom Value', '', false );
														?>
														
													
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
														
															<?php ced_amazon_print_table_label( $opt_value['name'], $opt_value['tooltip'], true ); ?>
															
														

														<?php if ( 'external_product_id_type' !== $opt_key ) { ?>

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

														<?php } else { ?>
															<td class="forminp forminp-select">
																<select style="width: 100%;" class="select2 ced_amazon_search_item_sepcifics_mapping"
																	name="<?php echo 'ced_amazon_general_options[' . esc_attr( $opt_key ) . '][metakey]'; ?>">
																	<option value=''>--Select--</option>
																	<?php
																	$selected_value = isset( $ced_amazon_general_options[ $opt_key ]['metakey'] ) ? $ced_amazon_general_options[ $opt_key ]['metakey'] : '';
																	foreach ( $opt_value['options'] as $key1 => $value ) {
																		$selected = '';
																		if ( $selected_value == $value ) {
																			$selected = 'selected';
																		}
																		?>
																		<option <?php echo $selected; ?> value='<?php echo esc_attr( $value ); ?>'>
																			<?php echo esc_attr( $value ); ?> </option>
																		<?php
																	}
																	?>
																</select>
															</td> 
														<?php } ?>		

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
																		<option <?php echo $selected; ?> value='<?php echo esc_attr( $value ); ?>'>
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
					<input class="ced-faq-trigger" id="ced-faq-wrapper-four" type="checkbox" /><label
						class="ced-faq-title" for="ced-faq-wrapper-four"><?php echo esc_html__( 'Advanced Settings', 'amazon-for-woocommerce' ); ?></label>

					<div class="ced-faq-content-wrap">
						<div class="ced-faq-content-holder ced-advance-table-wrap">
							<table class="form-table">
								<tbody>

									<?php

									$ced_amz_advancec_settings_fields = array(

										'ced_amazon_order_schedule_info' => array(
											'label'        => 'Fetch Amazon orders',
											'tooltip_desc' => 'Enable the setting to fetch Amazon orders automatically.',
										),
										'ced_amazon_inventory_schedule_info' => array(
											'label'        => 'Update inventory on Amazon',
											'tooltip_desc' => 'Enable the setting to update inventory from WooCommerce to Amazon automatically.',
										),
										'ced_amazon_price_schedule_info' => array(
											'label'        => 'Update price on Amazon',
											'tooltip_desc' => 'Enable the setting to update price from WooCommerce to Amazon automatically.',
										),
										'ced_amazon_existing_products_sync' => array(
											'label'        => 'Existing products sync',
											'tooltip_desc' => 'Enable the setting to update price from WooCommerce to Amazon automatically.',
										),


									);


									foreach ( $ced_amz_advancec_settings_fields as $scheduler_key => $scheduler_info ) {
										?>
											  
											<tr>
												
													<?php ced_amazon_print_table_label( $scheduler_info['label'], $scheduler_info['tooltip_desc'], true ); ?>
												
												<td class="forminp forminp-select">

													<?php
													$is_scheduled = isset( $renderDataOnGlobalSettings[ $seller_id ][ $scheduler_key ] ) ? $renderDataOnGlobalSettings[ $seller_id ][ $scheduler_key ] : '';
													$name         = 'ced_amazon_global_settings[' . $scheduler_key . ']';
													?>

													<div class="woocommerce-list__item-after">
														<label class="components-form-toggle  
														<?php
														if ( ! empty( $is_scheduled ) ) {
															echo esc_attr( 'is-checked' ); }
														?>
														" >
															<input
																name="<?php echo $name; ?>"
																class="components-form-toggle__input ced-settings-checkbox"
																id="inspector-toggle-control-0" type="checkbox" 
															<?php
															if ( ! empty( $is_scheduled ) ) {
																echo 'checked'; }
															?>
															 >
																
															<span class="components-form-toggle__track"></span>
															<span class="components-form-toggle__thumb"></span>
														</label>
													</div>

												</td>
											</tr> 

											<?php

									}

									?>

							
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

