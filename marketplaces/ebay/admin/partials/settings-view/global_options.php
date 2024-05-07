<?php
/**
 * Settings View: Accordion - Global Options.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
												$this->global_options = isset( $this->global_options[ $this->ebay_user ][ $this->ebay_site ] ) ? $this->global_options[ $this->ebay_user ][ $this->ebay_site ] : array();
												foreach ( $this->global_options as $gKey => $gOption ) {
													$selectDropdownHTML = ced_ebay_get_options_for_dropdown();
													?>
													<tr>
														<th scope="row" class="titledesc">
															<label for="woocommerce_currency">
																<?php echo esc_html( $gKey ); ?>
															</label>
														</th>
														<td class="forminp forminp-select">
															<select class="ced_ebay_map_to_fields"
																name="ced_ebay_global_options[<?php echo esc_html( $this->ebay_user ); ?>][<?php echo esc_html( $this->ebay_site ); ?>][<?php echo esc_html( $gKey ); ?>|meta_key]">
																<?php
																if ( isset( $gOption['options'] ) && ! empty( $gOption['options'] ) ) {
																	foreach ( $gOption['options'] as $optValue => $optName ) {
																		?>
																		<option <?php echo ( $optValue == $gOption['meta_key'] ) ? 'selected' : ''; ?>
																			value="<?php echo esc_attr( $optValue ); ?>"><?php echo esc_attr( $optName ); ?></option>
																		<?php
																	}
																} else {
																	if ( ! empty( $gOption['meta_key'] ) ) {
																		$selectDropdownHTML = str_replace(
																			'<option value="' . esc_attr( $gOption['meta_key'] ) . '"',
																			'<option value="' . esc_attr( $gOption['meta_key'] ) . '" selected',
																			$selectDropdownHTML
																		);
																	}
																	print_r( $selectDropdownHTML );

																}

																?>
															</select>
														</td>
														<?php if ( ! isset( $gOption['options'] ) || empty( $gOption['options'] ) ) { ?>
															<td class="forminp forminp-select">
																<input type="text"
																	name="ced_ebay_global_options[<?php echo esc_html( $this->ebay_user ); ?>][<?php echo esc_html( $this->ebay_site ); ?>][<?php echo esc_html( $gKey ); ?>|custom_value]"
																	style="width:100%" ;
																	value="<?php echo esc_attr( ! empty( $gOption['custom_value'] ) ? $gOption['custom_value'] : '' ); ?>">
															</td>
															<td></td>
														</tr>

															<?php
														}
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