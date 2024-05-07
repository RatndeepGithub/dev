<?php
$template_type = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : 'standard';
if ( 'paidstandard' == $template_type ) {
	$file = CED_WALMART_DIRPATH . 'admin/pages/ced-walmart-create-shipping-template-paidstandard.php';
	if ( file_exists( $file ) ) {
		include_once $file;
	}
	return;
}
$regionArray = valueRegionArray();
$allStates   = valueRegionNames();

$store_id = isset( $_GET['store_id'] ) ? sanitize_text_field( $_GET['store_id'] ) : '';

?>
<div class="ced_walmart_section_wrapper">
	<div class="ced_walmart_heading ced_walmart_parent_element">
		<h2><label>Standard Shipping Template</label></h2>
	</div>

	<div>
		<table class="wp-list-table widefat fixed activityfeeds ced_walmart_table">
			<tbody>
				<tr>
					<th><label>Template Name<span class="required">[Required]</span></label></th>
					<td class="manage-column"><input  id="ced_walmart_shipping_template_name" value="" type="text"  
						placeholder="Enter Template Name">
						<input required="required" id="ced_walmart_shipping_template_type" value="CUSTOM" type="hidden">

					</td>
				</tr>

				<tr>
					<th><label>Shipping Rate Model <span class="required">[Required]</span></label></th>
					<td class="manage-column">
						<div>
							<input type="radio" value="PER_SHIPMENT_PRICING" id="ced_weight_model" checked="" name="rate_model_type">
							<label for="ced_weight_model">The weight of the total order</label>
						</div>
						<div>
							<input type="radio" value="TIERED_PRICING"
							id="ced_price_model" name="rate_model_type">
							<label for="price_model">The price of the total order (tiers)</label>
						</div>

					</tr>
					<tr>
						<th><label>Status <span class="required">[Required]</span></label></th>
						<td class="manage-column">
							<select id="ced_template_status">
								<option value="active" selected>ACTIVE</option>
								<option value="inactive">INACTIVE</option>
							</select>

						</td>


						<tr>
							<td>
								<h2 style="font-size: 18px">Choose your shipping method <i class="fa fa-hand-o-down"></i></h2>
								<div class="ced_tab">
									<button class="ced_tablinks"  onclick="CedOpenShippingmethod(event, 'ced_Value')">VALUE <i class="fa fa-list-ol"></i></button>
									<button class="ced_tablinks" onclick="CedOpenShippingmethod(event, 'ced_standard')">STANDARD <i class="fa fa-star-o"></i></button>
									<button class="ced_tablinks" onclick="CedOpenShippingmethod(event, 'ced_Two_day')">TWO DAY <i class="fa fa-sun-o"></i></button>
								</div>
								<!-- Value Shipping Method -->

								<div id="ced_Value" class="ced_tabcontent">

									<table class="wp-list-table widefat fixed stripped activityfeeds ced_walmart_table">
										<thead>
											<tr>
												<th id="ced_heading_regions"><label>Regions</label></th>
												<th id="ced_heading_address"><label>Address</label></th>
												<th id="ced_heading_transitTime"><label>Transit Time</label></th>
												<th id="ced_heading_rateModel"><label>Rate</label></th>

											</tr>
										</thead>
										<tbody id="ced_value_shipping">
											<tr>
												<td>
													<input type="hidden" value='<?php echo json_encode( $regionArray ); ?>'
													id="ced_all_regions_field_value">
													<span><?php echo esc_attr( implode( ',', $allStates ) ); ?></span>
												</td>


												<td>
													<input type="hidden" value="STREET" id="ced_address_field_value">
													<span id="address_field">STREET</span>
												</td>


												<td>
													<select class="form-control" id="ced_transitTime_value">
														<option value="6" selected>6 Days</option>
														<option value="7">7 Days</option>
													</select>
												</td>

												<td id="ced_tiered_pricing" style="display:none;">
													<input type="hidden"
													id="ced_tieredShippingCharges_minLimit_value"
													value="0">
													<input type="hidden"
													id="ced_tieredShippingCharges_maxLimit_value"
													value="-1">
													<input type="hidden"
													id="ced_tieredShippingCharges_shipCharge_value"
													value="0">

													<span>$0.00 and up</span>
												</td>

												<td>
													<span>Free Shipping</span>
												</td>
											</tr>
										</tbody>
									</table>
								</div>

								<!-- Standard Shipping Method -->


								<div id="ced_standard" class="ced_tabcontent">

									<!-- Modal HTML embedded directly into document -->
									<div id="ced_popup_standard" class="ced_overlay">
										<div class="ced_popup">
											<h4>Shipping Regions :</h4>
											<a class="ced_close" href="#">&times;</a>
											<div class="ced_content">
												<!-- 	Displaying Regions with check box  -->
												<?php
												if ( is_array( $regionArray ) && isset( $regionArray ) ) {
													foreach ( $regionArray['regions']['48_state'] as $regionsArrayKey => $regionsArrayValue ) {
														?>

														<ul class='ced_regions'>
															<li>
															
																	<input  class="ced_regions_checkbox" type="checkbox" name='<?php echo esc_attr( $regionsArrayValue['regionCode'] ); ?>' value='<?php echo esc_attr( $regionsArrayValue['regionCode'] ); ?>' data-selectedname='<?php echo esc_attr( $regionsArrayValue['label'] ); ?>'>
																	<label> Select All Lower 48 </label>
																

																<?php
																foreach ( $regionsArrayValue['subRegions'] as $subregionArrayKey => $subregionArrayValue ) {
																	?>
																	<ul class="ced_subRegions">
																		<li>
																			
																				<input class="ced_regions_checkbox" type="checkbox" name='subregions' value='<?php echo esc_attr( $subregionArrayValue['subRegionCode'] ); ?>' data-selectedname='<?php echo esc_attr( $subregionArrayValue['label'] ); ?>'>
																				<label> <?php echo esc_attr( $subregionArrayValue['label'] ); ?> </label>
																			
																			<ul class="ced_states">
																				<?php

																				foreach ( $subregionArrayValue['states'] as $statesKey => $statesValue ) {
																					?>
																					<li>
																						
																							<input  class="ced_regions_checkbox" type="checkbox"  name='state' value='<?php echo esc_attr( $statesValue['value'] ); ?>' data-selectedname='<?php echo esc_attr( $statesValue['label'] ); ?>'>
																							<label id="ced_state_show"
																							data-val='<?php echo esc_attr( $statesValue['value'] ); ?>'>
																							<?php echo esc_attr( $statesValue['label'] ); ?>
																							<i class="fa fa-caret-down"></i>
																							  </label>
																							
																						
																						<ul class="ced_substates" id='ced_substate_standard_<?php echo esc_attr( $statesValue['value'] ); ?>'>
																							<?php
																							foreach ( $statesValue['state_sub_region'] as $subStateKey => $subStateValue ) {
																								?>
																								<li>
																									<input class="ced_regions_checkbox" type="checkbox"
																									 name='substate' value='<?php echo esc_attr( $subStateValue['value'] ); ?>' 

																									data-subregionsCode='<?php echo esc_attr( $subregionArrayValue['subRegionCode'] ); ?>'
																									data-subregionsName='<?php echo esc_attr( $subregionArrayValue['label'] ); ?>'
																									data-stateCode='<?php echo esc_attr( $statesValue['value'] ); ?>'
																									data-stateName='<?php echo esc_attr( $statesValue['label'] ); ?>'
																									data-substateCode='<?php echo esc_attr( $subStateValue['value'] ); ?>'
																									data-substateName='<?php echo esc_attr( $subStateValue['label'] ); ?>'>
																									<label> <?php echo esc_attr( $subStateValue['label'] ); ?> </label>
																								</li>
																							<?php } ?>

																						</ul>
																					</li>


																				<?php } ?>
																			</ul>
																		</li>
																	</ul>
																<?php } ?>
															</li>													
														</ul>

														<?php
													}
												}
												?>
												<button id="ced_save_rule_standard" class="button button-primary"> Save Shipping Rule</button>
												<button id="ced_cancel_rule_standard" class="button button-primary">Cancel</button>
											</div>
										</div>
									</div>
									<table id="ced_standard_shipping_table" class="wp-list-table widefat fixed stripped activityfeeds ced_walmart_table">
										<thead>
											<tr>
												<th id="ced_heading_regions_standard"><label>Regions</label></th>
												<th id="ced_heading_address_standard"><label>Address</label></th>
												<th id="ced_heading_transitTime_standard"><label>Transit Time</label></th>
												<th id="ced_heading_rateModel_standard"><label>Rate</label></th>

											</tr>
										</thead>
										<tbody id="ced_standard_shipping">
											<tr>
												<td>
													<a href="#ced_popup_standard" id="ced_add_rule_standard" class="button button-primary">+ New Shipping Rule</a>
												</td>
											</tr>
										</tbody>
									</table>
									<button id='ced_remove' class="ced_remove_style" onClick="window.location.reload();">  Remove </button> 
								</div>

								<!-- Standard Shipping Method end -->


								<div id="ced_Two_day" class="ced_tabcontent">

									<!-- Modal HTML embedded directly into document -->
									<div id="ced_popup_Two_day_standard" class="ced_overlay">
										<div class="ced_popup">
											<h4>Shipping Regions For 2 Day :</h4>
											<a class="ced_close" href="#">&times;</a>
											<div class="ced_content">

												<!-- 	Displaying Regions with check box  -->
												<?php
												if ( is_array( $regionArray ) && isset( $regionArray ) ) {
													foreach ( $regionArray['regions']['48_state'] as $regionsArrayKey => $regionsArrayValue ) {
														?>

														<ul class='ced_regions_2day'>
															<li>

																
																	<input  class="ced_regions_checkbox_2day" type="checkbox" name='<?php echo esc_attr( $regionsArrayValue['regionCode'] ); ?>' value='<?php echo esc_attr( $regionsArrayValue['regionCode'] ); ?>' data-selectedname='<?php echo esc_attr( $regionsArrayValue['label'] ); ?>'>
																	<label> Select Lower 48 </label>
																
																
																<?php
																foreach ( $regionsArrayValue['subRegions'] as $subregionArrayKey => $subregionArrayValue ) {
																	?>
																	<ul class="ced_subRegions_2day">
																		<li>
																			
																				<input class="ced_regions_checkbox_2day" type="checkbox" name='subregions_2day' value='<?php echo esc_attr( $subregionArrayValue['subRegionCode'] ); ?>' data-selectedname='<?php echo esc_attr( $subregionArrayValue['label'] ); ?>'>
																				<label> <?php echo esc_attr( $subregionArrayValue['label'] ); ?> </label>
																			
																			<ul class="ced_states_2day">
																				<?php

																				foreach ( $subregionArrayValue['states'] as $statesKey => $statesValue ) {
																					?>
																					<li>
																						
																							<input  class="ced_regions_checkbox_2day" type="checkbox" name='states_2day' value='<?php echo esc_attr( $statesValue['value'] ); ?>' data-selectedname='<?php echo esc_attr( $statesValue['label'] ); ?>'>
																		

																						<label id="ced_state_2day_show"
																							data-val='<?php echo esc_attr( $statesValue['value'] ); ?>'>
																							<?php echo esc_attr( $statesValue['label'] ); ?>
																								<i class="fa fa-caret-down"></i>
																							  </label>

																						<ul class="ced_substates_2day" id='ced_substate_2day_<?php echo esc_attr( $statesValue['value'] ); ?>'>
																							<?php
																							foreach ( $statesValue['state_sub_region'] as $subStateKey => $subStateValue ) {
																								?>
																								<li>
																									<input class="ced_regions_checkbox_2day" type="checkbox" name='substate_2day' value='<?php echo esc_attr( $subStateValue['value'] ); ?>' 

																									data-subregionsCode='<?php echo esc_attr( $subregionArrayValue['subRegionCode'] ); ?>'
																									data-subregionsName='<?php echo esc_attr( $subregionArrayValue['label'] ); ?>'
																									data-stateCode='<?php echo esc_attr( $statesValue['value'] ); ?>'
																									data-stateName='<?php echo esc_attr( $statesValue['label'] ); ?>'
																									data-substateCode='<?php echo esc_attr( $subStateValue['value'] ); ?>'
																									data-substateName='<?php echo esc_attr( $subStateValue['label'] ); ?>'>
																									<label> <?php echo esc_attr( $subStateValue['label'] ); ?> </label>
																								</li>
																							<?php } ?>

																						</ul>
																					</li>


																				<?php } ?>
																			</ul>
																		</li>
																	</ul>
																<?php } ?>
																
															</li>
														</ul>

														<?php
													}
												}
												?>
												<button id="ced_save_rule_2day" class="button button-primary"> Save Shipping Rule</button>
												<button id="ced_cancel_rule_2day" class="button button-primary">Cancel</button>
											</div>
										</div>
									</div>

									<table id="ced_2day_shipping_table" class="wp-list-table widefat fixed stripped activityfeeds ced_walmart_table">
										<thead>
											<tr>
												<th id="ced_heading_regions_twoday"><label>Regions</label></th>
												<th id="ced_heading_address_twoday"><label>Address</label></th>
												<th id="ced_heading_transitTime_twoday"><label>Transit Time</label></th>
												<th id="ced_heading_rateModel_twoday"><label>Rate</label></th>
											</tr>
										</thead>

										<tbody id="ced_twoday_shipping">
											<tr>
												<td>
													<a href="#ced_popup_Two_day_standard" id="ced_add_rule_2day" class="button button-primary">+ New Shipping Rule</button>
													</td>
												</tr>
											</tbody>
										</table>
										<button id='ced_remove_2day' class="ced_remove_style" onClick="window.location.reload();">  Remove </button> 
									</div>

								</td>
							</tr>

						</tbody>
					</table>
					<div class="save_shippingbutton_align">
						<input type="button" class="shipping_save_button_style" id="ced_walmart_save_shipping_template" data-storeID ="<?php echo esc_attr( $store_id ); ?>"  value="Save Shipping
						Template">
					</div>
				</div>

			</div>
