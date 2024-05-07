<?php 
/**
 * Settings View: Accordion - Advanced Settings.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! empty( $this->advanced_settings_field ) && is_array( $this->advanced_settings_field ) ) { ?>
					<div class="ced-faq-wrapper">
						<input class="ced-faq-trigger" id="ced-faq-wrapper-two" type="checkbox"><label class="ced-faq-title"
							for="ced-faq-wrapper-two">Advanced Settings</label>
						<div class="ced-faq-content-wrap">
							<div class="ced-faq-content-holder ced-advance-table-wrap">
								<table class="form-table">
									<tbody>
										<?php
										foreach ( $this->advanced_settings_field as $advFieldKey => $advFeilds ) {
											?>
											<tr>
												<th scope="row" class="titledesc">
													<label for="woocommerce_currency">
														<?php echo esc_attr( $advFeilds['title'] ); ?>
														<?php echo wc_help_tip( $advFeilds['description'], 'ebay-integration-for-woocommerce' ); ?>
													</label>
												</th>
												<td class="forminp forminp-select">
												<?php
												if ( isset( $advFeilds['is_link'] ) && true === $advFeilds['is_link'] ) {
													echo '<a href="#" class="' . esc_attr( $advFeilds['div_class'] ) . '">asddasdas</a>';
													continue;
												}
												?>

													<div class="woocommerce-list__item-after">
														<label class="components-form-toggle 
											<?php
											if ( 'on' == $advFeilds['value'] ) {
												echo esc_attr( 'is-checked' );
											}
											?>
											">
															<input name="<?php echo esc_attr( $advFeilds['div_name'] ); ?>"
																class="components-form-toggle__input ced-settings-checkbox-ebay"
																id="inspector-toggle-control-0" type="checkbox" 
																<?php
																if ( 'on' == $advFeilds['value'] ) {
																	echo 'checked';
																}
																?>
																>
															<span class="components-form-toggle__track"></span>
															<span class="components-form-toggle__thumb"></span>
														</label>
													</div>

												</td>
												<td></td>
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
				
		<?php } ?>