<?php
/**
 * Settings View: Accordion - Shipping, Payment, and Returns.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<!--- Shipping, Payment, and Returns -->
<div class="ced-faq-wrapper">
					<input class="ced-faq-trigger" id="ced-faq-wrapper-four" type="checkbox">
					<label class="ced-faq-title" for="ced-faq-wrapper-four"><?php echo esc_html__( 'Shipping, Payment, and Returns Policy', 'ebay-integration-for-woocommerce' ); ?>
						<a href="#" class="ced_ebay_update_business_policies">Update Business Policies</a>
					</label>
					<div class="ced-faq-content-wrap ced-ebay-business-policy-content">
						<div class="ced-faq-content-holder">
							<div class="ced-form-accordian-wrap">
								<div class="wc-progress-form-content woocommerce-importer">
									<header>
										<table class="form-table">
											<tbody>



												<?php
												$business_policies = ced_ebay_get_business_policies( $this->ebay_user, $this->ebay_site, $this->rsid );
												if ( ! empty( $business_policies ) && is_array( $business_policies ) && isset( $business_policies['paymentPolicies'] ) && isset( $business_policies['fulfillmentPolicies'] ) && isset( $business_policies['returnPolicies'] ) ) {
													$paymentPolicyId     = isset( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_payment_policy'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_payment_policy'] : '';
													$returnPolicyId      = isset( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_return_policy'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_return_policy'] : '';
													$fulfillmentPolicyId = isset( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_shipping_policy'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_shipping_policy'] : '';

													foreach ( $business_policies as $gKey => $policies ) {
														$nameForPolicyIdKey  = str_replace( 'Policies', '', $gKey );
														$nameForPolicyIdKey .= 'PolicyId';
														$suffix              = '';
														if ( 'paymentPolicies' == $gKey ) {
															$suffix = 'payment_policy';
														} elseif ( 'returnPolicies' == $gKey ) {
															$suffix = 'return_policy';
														} elseif ( 'fulfillmentPolicies' == $gKey ) {
															$suffix = 'shipping_policy';
														}
														?>
														<tr>
															<th scope="row" class="titledesc">
																<label for="woocommerce_currency">
																	<?php echo esc_html( $gKey ); ?>
																</label>
															</th>
															<td class="forminp forminp-select">
															<select class="ced_ebay_map_to_fields" name="ced_ebay_global_settings[ced_ebay_<?php echo esc_attr( $suffix ); ?>]">
																	<option value="">Select</option>
																	<?php
																	if ( isset( $policies[ $gKey ] ) && ! empty( $policies[ $gKey ] ) ) {
																		foreach ( $policies[ $gKey ] as $xKey => $individual_policy ) {
																			if ( ! empty( $individual_policy['name'] && ! empty( $individual_policy[ $nameForPolicyIdKey ] ) ) ) {
																				?>
																				<option <?php echo ( $$nameForPolicyIdKey == $individual_policy[ $nameForPolicyIdKey ] . '|' . $individual_policy['name'] ) ? 'selected' : ''; ?>
																					value="<?php echo esc_attr( $individual_policy[ $nameForPolicyIdKey ] . '|' . $individual_policy['name'] ); ?>">
																					<?php echo esc_attr( $individual_policy['name'] ); ?>
																				</option>
																				<?php
																			}
																		}
																	}
																	?>
																</select>
															</td>
															<td></td>
														</tr>

														<?php
													}
												} else {
													?>
													<p>You haven't setup Business Policies for your eBay account.</p>

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