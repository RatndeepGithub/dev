<?php

$regionArray = valueRegionArray();
$allStates   = valueRegionNames();

$store_id = isset( $_GET['store_id'] ) ? sanitize_text_field( $_GET['store_id'] ) : '';


?>
<div class="ced_walmart_section_wrapper">
<div class="ced_walmart_heading ced_walmart_parent_element">
	<h2><label>Paid Standard Shipping Template</label></h2>
</div>



<div>
	<table class="wp-list-table widefat fixed activityfeeds ced_walmart_table">
		<tbody>
			<tr>
				<th><label>Template Name<span class="required">[Required]</span></label></th>
				<td class="manage-column"><input  id="ced_walmart_shipping_template_name_paid" value="" type="text"  
				placeholder="Enter Template Name">
				<input required="required" id="ced_walmart_shipping_template_type_paid" value="CUSTOM" type="hidden">

			</td>
			</tr>

			<tr>
				<th><label>Shipping Rate Model <span class="required">[Required]</span></label></th>
				<td class="manage-column">
				<div>
						<input type="radio" value="PER_SHIPMENT_PRICING" id="ced_weight_model_paid" checked="" name="rate_model_type_paid">
						<label for="ced_weight_model_paid">The weight of the total order</label>
				</div>
				<div>
					  <input type="radio" value="TIERED_PRICING"
					  id="ced_price_model_paid" name="rate_model_type_paid">
					  <label for="price_model">The price of the total order (tiers)</label>
				   </div>

			</tr>
			<tr>
				<th><label>Status <span class="required">[Required]</span></label></th>
				<td class="manage-column">
				<select id="ced_template_status_paid">
					<option value="active" selected>ACTIVE</option>
					<option value="inactive">INACTIVE</option>
				</select>

			</td>
		</tr>
	</td>
</tr>
</tbody>
</table>

	<table id="ced_paid_shipping_table" class="wp-list-table widefat fixed stripped activityfeeds ced_walmart_table">
					<thead>
					<tr>
						<th id="ced_heading_regions_paid"><label>Regions</label></th>
						<th id="ced_heading_address_paid"><label>Address</label></th>
						<th id="ced_heading_transitTime_paid"><label>Transit Time</label></th>
						<th id="ced_heading_rateModel_paid"><label>Rate</label></th>
					</tr>
					</thead>

					 <tbody id="ced_paid_shipping">
					<tr>
						<td>
						<input type="hidden" value='<?php echo json_encode( $regionArray ); ?>'
								   id="ced_all_regions_field_paid">
							<span><?php print_r( implode( ',', $allStates ) ); ?></span>
						</td>
				   
					 
						<td>
						 <input type="hidden" value="STREET" id="ced_address_field_paid">
						 <span id="address_field">STREET</span>
						</td>

						<td > 
							<select name ='ced_paid_transit_time_data'>
								<option value='3'> 3 days</option>
								<option value='4'> 4 days</option>
								<option value='5'> 5 days</option>
							</select> 
						</td>

						<td id="ced_tiered_pricing_paid" style="display:none;"> <input type='number' id='ced_paid_min'  value='0'> <br> to <br> 
								<input type='number' id='ced_paid_max' value='-1'>  
						</td>


						<td> <input type='number' id="ced_paid_rate"  value='0'> <br> Shipping & Handling  <br> + <br> <input type='number' id='ced_paid_shipcharge' value='0'> <br> <select  id='ced_standard_shipcharge_name'>
							<option value='chargePerItem'>chargePerItem</option>
							 <option value='chargePerWeight'> chargePerWeight </option>
							</select>  
						</td>
					</tr>
				</tbody>
		   </table>

		   <div class='save_shippingbutton_align'> 
  <input type="button" class="shipping_save_button_style" id="ced_walmart_save_shipping_template_paid" data-storeID ="<?php echo esc_attr( $store_id ); ?>"  value="Save Shipping
Template">
		   </div>
	  
		</div>
	</div>


