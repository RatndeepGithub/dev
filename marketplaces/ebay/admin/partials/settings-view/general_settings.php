<?php
/**
 * Settings View: Accordion - General Settings.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="ced-faq-wrapper">
					<input class="ced-faq-trigger" id="ced-faq-wrapper-one" type="checkbox" checked=""><label
						class="ced-faq-title" for="ced-faq-wrapper-one">General Settings</label>
					<div class="ced-faq-content-wrap">
						<div class="ced-faq-content-holder">
							<div class="ced-form-accordian-wrap">
								<div class="wc-progress-form-content woocommerce-importer">
									<header>
										<h3>Listings Configuration</h3>

										<p>Increase or decrease the Price of eBay Listings, Adjust Stock Levels, Sync
											Price from WooCommerce and import
											eBay Categories.</p>
										<table class="form-table">
											<tbody>
												<tr>
													<?php
													$listing_stock = isset( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_listing_stock'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_listing_stock'] : '';
													$stock_type    = isset( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_product_stock_type'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_product_stock_type'] : '';
													?>

													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															Stock Levels
															<?php print_r( wc_help_tip( 'Stock level, also called inventory level, indicates the quantity of a particular product or product that you own on any platform', 'ebay-integration-for-woocommerce' ) ); ?>
														</label>
													</th>
													<td class="forminp forminp-select">

														<select
															name="ced_ebay_global_settings[ced_ebay_product_stock_type]"
															data-fieldId="ced_ebay_product_stock_type">
															<option value="">
																<?php esc_attr_e( 'Select', 'ebay-integration-for-woocommerce' ); ?>
															</option>
															<option <?php echo ( 'MaxStock' == $stock_type ) ? 'selected' : ''; ?> value="MaxStock"><?php esc_attr_e( 'Maximum Quantity', 'ebay-integration-for-woocommerce' ); ?>
															</option>
														</select>

													</td>
													<td class="forminp forminp-select">

														<input type="number"
															value="<?php echo esc_attr( $listing_stock ); ?>"
															id="ced_ebay_listing_stock"
															name="ced_ebay_global_settings[ced_ebay_listing_stock]">

													</td>
												</tr>
												<tr>
													<?php
													$markup_type  = isset( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_product_markup_type'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_product_markup_type'] : '';
													$markup_price = isset( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_product_markup'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_product_markup'] : '';
													?>

													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
														Price Markup 
															<?php print_r( wc_help_tip( 'Markup is the amount you include in prices to earn profit while selling on eBay. You are able to increase or decrease the markup either by a fixed amount or by percentage.', 'ebay-integration-for-woocommerce' ) ); ?>
														</label>
													</th>
													<td class="forminp forminp-select">
														<select
															name="ced_ebay_global_settings[ced_ebay_product_markup_type]"
															data-fieldId="ced_ebay_product_markup">
															<option value="">
																<?php esc_attr_e( 'Select', 'ebay-integration-for-woocommerce' ); ?>
															</option>
															<option <?php echo ( 'Fixed_Increased' == $markup_type ) ? 'selected' : ''; ?> value="Fixed_Increased"><?php esc_attr_e( 'Fixed Increment', 'ebay-integration-for-woocommerce' ); ?></option>
															<option <?php echo ( 'Fixed_Decreased' == $markup_type ) ? 'selected' : ''; ?> value="Fixed_Decreased"><?php esc_attr_e( 'Fixed Decrement', 'ebay-integration-for-woocommerce' ); ?></option>
															<option <?php echo ( 'Percentage_Increased' == $markup_type ) ? 'selected' : ''; ?> value="Percentage_Increased"><?php esc_attr_e( 'Percentage Increment', 'ebay-integration-for-woocommerce' ); ?></option>
															<option <?php echo ( 'Percentage_Decreased' == $markup_type ) ? 'selected' : ''; ?> value="Percentage_Decreased"><?php esc_attr_e( 'Percentage Decrement', 'ebay-integration-for-woocommerce' ); ?></option>
														</select>

													</td>

													<td class="forminp forminp-select">
														<input type="text"
															value="<?php echo esc_attr( $markup_price ); ?>"
															id="ced_ebay_product_markup"
															name="ced_ebay_global_settings[ced_ebay_product_markup]">

													</td>
												</tr>
												<tr>
													<?php
													$upload_dir    = wp_upload_dir();
													$templates_dir = $upload_dir['basedir'] . '/ced-ebay/templates/';
													$templates     = array();
													$files         = glob( $upload_dir['basedir'] . '/ced-ebay/templates/*/template.html' );
													if ( is_array( $files ) ) {
														foreach ( $files as $file ) {
															$file     = basename( dirname( $file ) );
															$fullpath = $templates_dir . $file;

															if ( file_exists( $fullpath . '/info.txt' ) ) {
																$template_header       = array(
																	'Template' => 'Template',
																);
																$template_data         = get_file_data( $fullpath . '/info.txt', $template_header, 'theme' );
																$item['template_name'] = $template_data['Template'];
															}
															$template_id                                = basename( $fullpath );
															$templates[ $template_id ]['template_name'] = $item['template_name'];
														}
													}
													$listing_description_template = isset( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_listing_description_template'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_listing_description_template'] : '';
													?>

													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															Description Template
															<?php print_r( wc_help_tip( 'Create and select a listing description template to make your eBay listing description stand out to buyers', 'ebay-integration-for-woocommerce' ) ); ?>
														</label>
													</th>
													<td class="forminp forminp-select">
														<?php
														if ( ! empty( $templates ) ) {
															?>
															<select
																name="ced_ebay_global_settings[ced_ebay_listing_description_template]"
																data-fieldId="ced_ebay_listing_description_template">
																<option value="">
																	<?php esc_attr_e( 'Select', 'ebay-integration-for-woocommerce' ); ?>
																</option>
																<?php
																foreach ( $templates as $key => $value ) {
																	?>
																	<option <?php echo ( $key == $listing_description_template ) ? 'selected' : ''; ?>
																		value="<?php echo esc_attr( $key ); ?>"><?php esc_attr_e( $value['template_name'], 'ebay-integration-for-woocommerce' ); ?></option>
																	<?php
																}
																?>
															</select>
															<?php
														} else {
															?>
															<p>No description templates were found. You can create
																description template here.</p>

															<?php
														}
														?>

													</td>
													<td class="forminp forminp-select">
														<a
															href="<?php echo esc_attr( wp_nonce_url( admin_url( 'admin.php?page=sales_channel&channel=ebay&section=description-template&user_id=' . $this->ebay_user . '&site_id=' . $this->ebay_site . '&action=ced_ebay_add_new_template' ), 'ced_ebay_add_new_template_action', 'ced_ebay_add_new_template_nonce' ) ); ?>">
															<?php esc_attr_e( 'Create Template', 'ebay-integration-for-woocommerce' ); ?>
														</a>
														<span style="margin: 0px 3px;">|</span>
														<a
															href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=ebay&section=view-description-templates&user_id=' . $this->ebay_user . '&site_id=' . $this->ebay_site ) ); ?>">View
															Templates</a>
													</td>
												</tr>
												<tr>
													<?php
													// an array of all the supported eBay sites
													$ebay_sites          = array(
														'US' => 'United States',
														'UK' => 'United Kingdom',
														'Australia' => 'Australia',
														'Austria' => 'Austria',
														'Belgium_French' => 'Belgium (French)',
														'Belgium_Dutch' => 'Belgium (Dutch)',
														'Canada' => 'Canada',
														'CanadaFrench' => 'Canada French',
														'France' => 'France',
														'Germany' => 'Germany',
														'Italy' => 'Italy',
														'Netherlands' => 'Netherlands',
														'Spain' => 'Spain',
														'Switzerland' => 'Switzerland',
														'HongKong' => 'Hong Kong',
														'India' => 'India',
														'Ireland' => 'Ireland',
														'Malaysia' => 'Malaysia',
														'Philippines' => 'Philippines',
														'Poland' => 'Poland',
														'Singapore' => 'Singapore',
														'Russia' => 'Russia',
														'eBayMotors' => 'eBay Motors',
													);
													$item_import_country = isset( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_item_import_country'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_item_import_country'] : '';
													?>

													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															Import Products from eBay Site 
															<?php print_r( wc_help_tip( 'Choose which eBay site you would like to import products from if you have products listed across multiple eBay regions.', 'ebay-integration-for-woocommerce' ) ); ?>	
														</label>
													</th>
													<td class="forminp forminp-select">

														<select
															name="ced_ebay_global_settings[ced_ebay_item_import_country]"
															data-fieldId="ced_ebay_import_product_location">
															<option value="">
																<?php esc_attr_e( 'Select', 'ebay-integration-for-woocommerce' ); ?>
															</option>
															<?php
															foreach ( $ebay_sites as $key => $import_country ) {
																?>
																<option <?php echo ( $key == $item_import_country ) ? 'selected' : ''; ?>
																	value="<?php echo esc_attr( $key ); ?>"><?php esc_attr_e( $import_country, 'ebay-integration-for-woocommerce' ); ?></option>
																<?php
															}
															?>
														</select>

													</td>
												</tr>
												<tr>
													<?php
													$postal_code = isset( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_postal_code'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_postal_code'] : '';
													?>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															Postal Code
															<?php print_r( wc_help_tip( 'Enter the postal code where your products are located', 'ebay-integration-for-woocommerce' ) ); ?>	
														</label>
													</th>

													<td class="forminp forminp-select">
														<input type="text"
															value="<?php echo esc_attr( $postal_code ); ?>"
															id="ced_ebay_postal_code"
															name="ced_ebay_global_settings[ced_ebay_postal_code]">

													</td>
												</tr>
												<tr>
													<?php
													$wc_countries          = new WC_Countries();
													$countries             = $wc_countries->get_countries();
													$item_location_country = isset( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_item_location_country'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_item_location_country'] : '';
													$item_location_state   = isset( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_item_location_state'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_item_location_state'] : '';
													?>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															Item Location
															<?php print_r( wc_help_tip( 'You can ignore this field if the Postal Code is set above else choose the item location by choosing country and entering state/city', 'ebay-integration-for-woocommerce' ) ); ?>	
														</label>
													</th>

													<td class="forminp forminp-select">
														<select
															name="ced_ebay_global_settings[ced_ebay_item_location_country]"
															data-fieldId="ced_ebay_product_location">
															<option value="">
																<?php esc_attr_e( 'Select', 'ebay-integration-for-woocommerce' ); ?>
															</option>
															<?php
															foreach ( $countries as $key => $country ) {
																?>
																<option <?php echo ( $key == $item_location_country ) ? 'selected' : ''; ?>
																	value="<?php echo esc_attr( $key ); ?>"><?php esc_attr_e( $country, 'ebay-integration-for-woocommerce' ); ?></option>
																<?php
															}
															?>
														</select>
													</td>
													<td class="forminp forminp-select">
														<input type="text" placeholder="Enter City Name"
															value="<?php echo esc_attr( $item_location_state ); ?>"
															id="ced_ebay_product_markup"
															name="ced_ebay_global_settings[ced_ebay_item_location_state]">

													</td>
												</tr>
												<tr>
													<?php
														if ( '3' == $this->ebay_site || '101' == $this->ebay_site || '77' == $this->ebay_site ) {
															$exclude_product_vat = isset( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_exclude_product_vat'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_exclude_product_vat'] : '';
															?>


															<th scope="row" class="titledesc">
																<label for="woocommerce_currency">
																	Exclude Order Product VAT
																	<?php print_r( wc_help_tip( 'Exclude the VAT amount from the eBay orders imported in WooCommerce', 'ebay-integration-for-woocommerce' ) ); ?>	
																</label>
															</th>
															<td class="forminp forminp-select">


																<div class="woocommerce-list__item-after">
																	<label class="components-form-toggle 
															<?php
															if ( 'on' == $exclude_product_vat ) {
																echo esc_attr( 'is-checked' );
															}
															?>
											">
																		<input
																			name="ced_ebay_global_settings[ced_ebay_exclude_product_vat]"
																			class="components-form-toggle__input ced-settings-checkbox-ebay"
																			id="inspector-toggle-control-0" type="checkbox"
																			<?php
																			if ( 'on' == $exclude_product_vat ) {
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
														}
													?>
												</tr>
												<tr>
													<?php
													$import_categories = isset( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_import_ebay_categories'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_import_ebay_categories'] : '';
													$is_store_category_present = false;
													$this->apiClient->setRequestRemoteMethod('GET');
													$this->apiClient->setRequestTopic('user-account');
													$this->apiClient->setRequestRemoteQueryParams([
														'type' => 'GetStore',
														'shop_id' => $this->rsid,
														'user_id' => $this->ebay_user
													]);
													$apiResponse = $this->apiClient->post();
												
													if(isset($apiResponse['data'])){
														$storeDetails = json_decode($apiResponse['data'], true);
													} else {
														$storeDetails = false;
													}														
													if ( ! empty( $storeDetails ) && 'Success' == $storeDetails['Ack'] ) {
														$store_categories = $storeDetails['Store']['CustomCategories']['CustomCategory'];
														if ( ! empty( $store_categories ) ) {
															$is_store_category_present = true;
														}
													}													
													?>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php echo esc_html__( 'Import and Assign eBay Categories', 'ebay-integration-for-woocommerce' ); ?>
															<?php print_r( wc_help_tip( 'Choose to automatically create and assing eBay site or store cateogry when importing eBay listings in WooCommerce', 'ebay-integration-for-woocommerce' ) ); ?>	
														</label>
													</th>
													
													
														<td colspan="4"
															class="ced_ebay_cat_import_row forminp forminp-select">
															<?php
															$import_categories_type = isset( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_import_categories_type'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_import_categories_type'] : '';
															?>

															<select
																name="ced_ebay_global_settings[ced_ebay_import_categories_type]"
																data-fieldId="ced_ebay_import_categories_type">
																<option value="">
																	<?php esc_attr_e( 'None', 'ebay-integration-for-woocommerce' ); ?>
																</option>
																<option <?php echo ( 'ebay_site' == $import_categories_type ) ? 'selected' : ''; ?> value="ebay_site"><?php esc_attr_e( 'eBay Site Categories', 'ebay-integration-for-woocommerce' ); ?></option>
																<?php
																if ( $is_store_category_present ) {
																	?>
																<option <?php echo ( 'ebay_store' == $import_categories_type ) ? 'selected' : ''; ?> value="ebay_store"><?php esc_attr_e( 'eBay Store Categories', 'ebay-integration-for-woocommerce' ); ?></option>
																	<?php
																}

																?>
															</select>
													</td>
												</tr>
												<tr>
													<?php
													$skip_sku_sending = isset( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_sending_sku'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_sending_sku'] : '';
													?>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															Skip Sending SKU for Simple Products 
															<?php print_r( wc_help_tip( 'If your simple products on eBay, without variations, don\'t have SKUs as compared to WooCommerce products then turn this ON for inventory sync to run successfully', 'ebay-integration-for-woocommerce' ) ); ?>	
														</label>
													</th>
													<td class="forminp forminp-select">


														<div class="woocommerce-list__item-after">
															<label class="components-form-toggle 
											<?php
											if ( 'on' == $skip_sku_sending ) {
												echo esc_attr( 'is-checked' );
											}
											?>
											">
																<input
																	name="ced_ebay_global_settings[ced_ebay_sending_sku]"
																	class="components-form-toggle__input ced-settings-checkbox-ebay"
																	id="inspector-toggle-control-0" type="checkbox"
																	<?php
																	if ( 'on' == $skip_sku_sending ) {
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
													<?php
													$sync_price = isset( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_sync_price'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_sync_price'] : '';
													?>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															Sync price to eBay
															<?php print_r( wc_help_tip( 'Turn this toggle ON to sync WooCommerce prices with your eBay listings. Make sure that the stock sync is running before turning this ON.', 'ebay-integration-for-woocommerce' ) ); ?>	
														</label>
													</th>
													<td class="forminp forminp-select">


														<div class="woocommerce-list__item-after">
															<label class="components-form-toggle 
											<?php
											if ( 'on' == $sync_price ) {
												echo esc_attr( 'is-checked' );
											}
											?>
											">
																<input
																	name="ced_ebay_global_settings[ced_ebay_sync_price]"
																	class="components-form-toggle__input ced-settings-checkbox-ebay"
																	id="inspector-toggle-control-0" type="checkbox"
																	<?php
																	if ( 'on' == $sync_price ) {
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
											</tbody>
										</table>

									</header>
								</div>
							</div>
						</div>
					</div>
				</div>					