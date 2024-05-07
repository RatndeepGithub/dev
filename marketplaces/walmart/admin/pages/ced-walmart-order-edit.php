<?php
/**
 * Order edit section to be rendered
 *
 * @package  Woocommerce_Walmart_Integration
 * @version  1.0.0
 * @link     https://woocommerce.com/vendor/cedcommerce/
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

get_walmart_header();

$order_id                 = isset( $_GET['id'] ) ? sanitize_text_field( $_GET['id'] ) : 0;
$order_obj                = wc_get_order($order_id);
$store_id                 = isset( $_GET['store_id'] ) ? sanitize_text_field( $_GET['store_id'] ) : '';
$ced_walmart_order_status = $order_obj->get_meta( '_ced_walmart_order_status', true );
$walmart_shipped_details  = $order_obj->get_meta( '_ced_walmart_shipped_data', true );
if ( ( isset( $walmart_shipped_details ) && ! empty( $walmart_shipped_details ) ) || ( isset( $ced_walmart_order_status ) && ! empty( $ced_walmart_order_status ) ) ) {
	$merchant_order_id = $order_obj->get_meta( 'merchant_order_id', true );
	$purchase_order_id = $order_obj->get_meta( 'purchaseOrderId', true );
	$fulfillment_node  = $order_obj->get_meta( 'fulfillment_node', true );
	$order_detail      = $order_obj->get_meta( 'order_detail', true );
	$order_item        = $order_obj->get_meta( 'order_items', true );
	if ( isset( $order_item[0] ) ) {
		$order_items = $order_item;
	} else {
		$order_items[0] = $order_item['orderLine'];
	}

	$number_items = 0;
	// Get order status

	$ced_walmart_order_status = $order_obj->get_meta( '_ced_walmart_order_status', true );

	if ( empty( $ced_walmart_order_status ) ) {
		$ced_walmart_order_status = __( 'Created', 'walmart-woocommerce-integration' );
	}
	?>
	<div id="ced_walmart_order_settings" class="panel woocommerce_options_panel ced_walmart_section_wrapper">
		<div class="options_group">
			<p class="form-field">
				<h3><center>
					<?php
					esc_html_e( 'WALMART ORDER STATUS : ', 'walmart-woocommerce-integration' );
					echo esc_attr( strtoupper( $ced_walmart_order_status ) );
					?>
				</center></h3>
			</p>
		</div>
		<div class="options_group umb_walmart_options"> 
			<?php
			if ( 'Cancelled' == $ced_walmart_order_status ) {
				?>
				<h1 style="text-align:center;" class="walmart-error"><?php esc_html_e( 'ORDER CANCELLED ', 'walmart-woocommerce-integration' ); ?></h1>
				<?php
			}
			if ( 'Created' == $ced_walmart_order_status ) {
				?>
				<p class="form-field">
					<label><?php esc_html_e( 'Select Order Action:', 'walmart-woocommerce-integration' ); ?></label>
				</p>
				<p>
					<input type="button" class="button-primary " value="Acknowledge Order" data-order_id = "<?php echo esc_attr( $order_id ); ?>" id="ced_walmart_ack_action"/>
					<input type="button" class="button " value="Cancel Order" data-order_id = "<?php echo esc_attr( $order_id ); ?>" id="ced_walmart_cancel_action"/>
				</p>
				<?php
			} elseif ( 'Acknowledged' == $ced_walmart_order_status ) {
				?>
				
				<input type="hidden" id="walmart_orderid" value="<?php echo esc_attr( $purchase_order_id ); ?>" readonly>
				<input type="hidden" id="woocommerce_orderid" value="<?php echo esc_attr( $order_id ); ?>">
				<h2 class="title"><?php esc_html_e( 'Shipment Information', 'walmart-woocommerce-integration' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<tbody>
						<tr>
							<td><b><?php esc_html_e( 'Reference Order Id on Walmart.com', 'walmart-woocommerce-integration' ); ?></b></td>
							<td class="walmart-success"><?php echo esc_attr( $merchant_order_id ); ?></td>
						</tr>
						<!-- <tr>
							   <td><b><?php esc_html_e( 'Order Placed on Walmart.com', 'ced-umb' ); ?></b></td>
							   <td><?php echo esc_attr( gmdate( 'l, F jS Y \a\t g:ia', strtotime( $order_detail['orderDate'] ) ) ); ?> </td>
						</tr>
						<tr>
							   <td><b><?php esc_html_e( 'Estimated Ship Date', 'ced-umb' ); ?></b></td>
							   <td><?php echo esc_attr( gmdate( 'l, F jS Y \a\t g:ia', strtotime( $order_detail['shippingInfo']['estimatedShipDate'] ) ) ); ?></td>
						</tr>
						<tr>
							   <td><b><?php esc_html_e( 'Estimated Delivery Date', 'ced-umb' ); ?></b></td>
							   <td><?php echo esc_attr( gmdate( 'l, F jS Y \a\t g:ia', strtotime( $order_detail['shippingInfo']['estimatedDeliveryDate'] ) ) ); ?></td>
							</tr> -->
							<tr>
								<td><b><?php esc_html_e( 'Shipping Carrier Type', 'walmart-woocommerce-integration' ); ?></b></td>
								<td>
									<select id="ced_walmart_carrier">
										<option value="UPS">UPS</option>
										<option value="USPS">USPS</option>
										<option value="FedEx">FedEx</option>
										<option value="Airborne">Airborne</option>
										<option value="OnTrac">OnTrac</option>
									</select>
								</td>
							</tr>  
							<tr>
								<td><b><?php esc_html_e( 'Shipping Method Code Type', 'walmart-woocommerce-integration' ); ?></b></td>
								<td>
									<select id="ced_walmart_methodCode">
										<option value="Standard">Standard</option>
										<option value="Express">Express</option>
										<option value="OneDay">OneDay</option>
										<option value="Freight">Freight</option>
										<option value="WhiteGlove">WhiteGlove</option>
										<option value="Value">Value</option>
									</select>
								</td>
							</tr>
							<tr>
								<td><b><?php esc_html_e( 'Tracking Number', 'walmart-woocommerce-integration' ); ?></b></td>
								<td><input type="text" id="ced_walmart_tracking" value=""></td>
							</tr>
							<tr>
								<td><b><?php esc_html_e( 'Tracking URL', 'walmart-woocommerce-integration' ); ?></b></td>
								<td><input type="text" id="ced_walmart_tracking_url" value=""></td>
							</tr>
							<tr>
								<td><b><?php esc_html_e( 'Ship To Date', 'walmart-woocommerce-integration' ); ?></b></td>
								<td><input class=" input-text required-entry"  type="date" id="ced_walmart_ship_date" name="ship_date" /></td>
							</tr>

						</tbody>
					</table>	
					<h2 class="title"><?php esc_html_e( 'Shipment Items', 'walmart-woocommerce-integration' ); ?></h2>
					<table class=" widefat fixed striped">
						<thead>
							<tr class="headings">
								<th><?php esc_html_e( 'Product sku', 'walmart-woocommerce-integration' ); ?></th>
								<th><?php esc_html_e( 'Quantity ordered', 'walmart-woocommerce-integration' ); ?></th>
								<th><?php esc_html_e( 'Quantity to Ship', 'walmart-woocommerce-integration' ); ?></th>
								<th><?php esc_html_e( 'Quantity to Cancel', 'walmart-woocommerce-integration' ); ?></th>
								<th><?php esc_html_e( 'Qty Available for ship', 'walmart-woocommerce-integration' ); ?></th>
								<th><?php esc_html_e( 'Return Address', 'walmart-woocommerce-integration' ); ?></th>
								<th><?php esc_html_e( 'RMA Number', 'walmart-woocommerce-integration' ); ?></th>
								<th><?php esc_html_e( 'Days to Return', 'walmart-woocommerce-integration' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $order_items as $k => $valdata ) {
								$number_items++;
								$cancel_qty      = 0;
								$real_cancel_qty = 0;
								$avail_qty       = $valdata['orderLineQuantity']['amount'];
								$line_number     = $valdata['lineNumber'];
								$ship_qty        = (int) ( $valdata['orderLineQuantity']['amount'] );

								?>
								<tr>
									<td>
										<input type="hidden" id="lineNumber_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $valdata['lineNumber'] ); ?>">
										<input type="hidden" id="sku_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $valdata['item']['sku'] ); ?>">
										<strong><?php echo esc_attr( $valdata['item']['sku'] ); ?></strong>
									</td>
									<td>
										<input type="hidden" id="qty_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $valdata['orderLineQuantity']['amount'] ); ?>">
										<strong><?php echo esc_attr( $valdata['orderLineQuantity']['amount'] ); ?></strong>
									</td>
									<?php
									if ( $avail_qty > 0 ) :
										?>
										<td>
											<input class="admin__control-text" type="text" maxlength="70" id="ship_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $avail_qty ); ?>" onkeypress="return isNumberKey(event);">
										</td>
										<td>
											<input class="admin__control-text" type="text" id="can_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $cancel_qty ); ?>">
										</td>
									<?php else : ?>
										<td>
											<input type="hidden" id="ship_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $avail_qty ); ?>">
											<strong 
											<?php
											if ( $avail_qty <= 0 ) {
												echo ' style="color: #EE0000" ';}
											?>
												>
												<?php echo esc_attr( $avail_qty ); ?>
											</strong>
										</td>
										<td>
											<input type="hidden" id="can_<?php echo esc_attr( $k ); ?>"value="<?php echo esc_attr( $cancel_qty ); ?>">
											<strong 
											<?php
											if ( $avail_qty <= 0 ) {
												echo ' style="color: #EE0000" ';}
											?>
												> 
												<?php echo esc_attr( $cancel_qty ); ?>
											</strong>
										</td>
									<?php endif; ?>
									<td>
										<input type="hidden" id="avail_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $avail_qty ); ?>">
										<strong 
										<?php
										if ( $avail_qty > 0 ) {
											echo ' style="color: #008800" ';
										} else {
											echo ' style="color: #EE0000" ';}
										?>
											> 
											<?php echo esc_attr( $avail_qty ); ?>
										</strong>
									</td>
									<td id="return-adress">
										<select class="admin__control-select" name="address" id="address_<?php echo esc_attr( $k ); ?>" onchange="">
											<option value="" disabled="disabled" >Please select a option</option>
											<option value="1" selected="selected">Yes</option>
											<option value="0">No</option>
										</select>
									</td>
									<!-- Rma input -->
									<td>
										<input class="admin__control-text" type="text" id="rma_<?php echo esc_attr( $k ); ?>" value="">
									</td>
									<td>
										<input class="admin__control-text" type="text" id="days_return_<?php echo esc_attr( $k ); ?>" value="" placeholder="0" onkeypress="return isNumberKey(event);">
									</td>
								</tr>
								<?php

							}
							?>
						</tbody>
					</table>
					<input data-items="<?php echo esc_attr( $number_items ); ?>" type="button" class="button-primary" id="ced_walmart_shipment_submit"  data-storeID="<?php echo esc_attr( $store_id ); ?>" value="Submit Shipment">
					<?php
			} elseif ( 'Shipped' == $ced_walmart_order_status ) {
				$walmart_shipped_details  = $order_obj->get_meta( '_ced_walmart_shipped_data', true );
				$walmart_shipped_data     = $walmart_shipped_details['shipments'][0];
				$walmart_shipping_carrier = $walmart_shipped_data['carrier'];
				$walmart_shipping_type    = $walmart_shipped_data['methodCode'];
				$walmart_tracking_no      = $walmart_shipped_data['shipment_tracking_number'];
				$walmart_tracking_url     = $walmart_shipped_data['shipment_tracking_url'];
				$walmart_ship_date        = $walmart_shipped_data['response_shipment_date'];
				$walmart_carrier_date     = $walmart_shipped_data['carrier_pick_up_date'];
				$walmart_ex_del_date      = $walmart_shipped_data['expected_delivery_date'];
				?>
					<input type="hidden" id="walmart_orderid" value="<?php echo esc_attr( $purchase_order_id ); ?>" readonly>
					<input type="hidden" id="woocommerce_orderid" value="<?php echo esc_attr( $order_id ); ?>">
					<h2 class="title"><?php esc_html_e( 'Shipment Information' ); ?></h2>
					<table class="wp-list-table widefat fixed striped">
						<tbody>
							<tr>
								<td><b><?php esc_html_e( 'Reference Order Id on Walmart.com', 'walmart-woocommerce-integration' ); ?></b></td>
								<td class="walmart-success"><?php echo esc_attr( $merchant_order_id ); ?></td>
							</tr>
						 <!-- <tr>
								 <td><b><?php esc_html_e( 'Order Placed on Walmart.com', 'ced-umb' ); ?></b></td>
								 <td><?php echo esc_attr( gmdate( 'l, F jS Y \a\t g:ia', strtotime( $order_detail['orderDate'] ) ) ); ?></td>
						  </tr>
							 <tr>
								 <td><b><?php esc_html_e( 'Request Delivery By', 'ced-umb' ); ?></b></td>
								 <td><?php echo esc_attr( gmdate( 'l, F jS Y \a\t g:ia', strtotime( $order_detail['shippingInfo']['estimatedDeliveryDate'] ) ) ); ?></td>
								</tr> -->
								<tr>
									<td><b><?php esc_html_e( 'Shipping Carrier Type', 'walmart-woocommerce-integration' ); ?></b></td>
									<td>
									<?php echo esc_attr( $walmart_shipping_carrier ); ?>
									</td>
								</tr>
								<tr>
									<td><b><?php esc_html_e( 'Shipping Method Code Type ', 'walmart-woocommerce-integration' ); ?></b></td>
									<td>

									<?php echo esc_attr( $walmart_shipping_type ); ?>
									</td>
								</tr>
								<tr>
									<td><b><?php esc_html_e( 'Tracking Number', 'walmart-woocommerce-integration' ); ?></b></td>
									<td><?php echo esc_attr( $walmart_tracking_no ); ?></td>
								</tr>
								<tr>
									<td><b><?php esc_html_e( 'Tracking URL', 'walmart-woocommerce-integration' ); ?></b></td>
									<td><?php echo esc_attr( $walmart_tracking_url ); ?></td>
								</tr>
							 <!--  <tr>
							  <td><b><?php esc_html_e( 'Ship To Date', 'ced-umb' ); ?></td>
							  <td><?php echo esc_attr( gmdate( 'l, F jS Y \a\t g:ia', strtotime( $walmart_ship_date ) ) ); ?></td>
						  </tr>
						  <tr>
							  <td><b><?php esc_html_e( 'Carrier Pick Up Date', 'ced-umb' ); ?></b></td>
							  <td>
							<?php echo esc_attr( gmdate( 'l, F jS Y \a\t g:ia', strtotime( $walmart_carrier_date ) ) ); ?>
							  </td>
					  </tr>
					  <tr>	
						  <td><b><?php esc_html_e( 'Expected delivery Date(respectively)', 'ced-umb' ); ?></b></td>
						  <td>    
						<?php echo esc_attr( gmdate( 'l, F jS Y \a\t g:ia', strtotime( $walmart_ex_del_date ) ) ); ?>
						  </td>
						</tr> -->
					</tbody>
				</table>	
				<h2 class="title"><?php esc_html_e( 'Shipment Items', 'walmart-woocommerce-integration' ); ?></h2>
				<table class="widefat fixed striped">
					<thead>
						<tr class="headings">
							<th><?php esc_html_e( 'Product sku', 'walmart-woocommerce-integration' ); ?></th>
							<th><?php esc_html_e( 'Quantity ordered', 'walmart-woocommerce-integration' ); ?></th>
							<th><?php esc_html_e( 'Quantity to Ship', 'walmart-woocommerce-integration' ); ?></th>
							<th><?php esc_html_e( 'Quantity to Cancel', 'walmart-woocommerce-integration' ); ?></th>
							<th><?php esc_html_e( 'Qty Available for ship', 'walmart-woocommerce-integration' ); ?></th>
							<th><?php esc_html_e( 'Return Address', 'walmart-woocommerce-integration' ); ?></th>
							<th><?php esc_html_e( 'RMA Number', 'walmart-woocommerce-integration' ); ?></th>
							<th><?php esc_html_e( 'Days to Return', 'walmart-woocommerce-integration' ); ?></th>
						</tr>
					</thead>
					<tbody id="walmart_line_data">
					<?php


					foreach ( $order_items as $k => $valdata ) {
						$walmart_shipping_data = $walmart_shipped_details['shipments'];
						foreach ( $walmart_shipping_data as $walmart_line_data ) {
							if ( isset( $walmart_line_data['shipment_items'] ) && ! empty( $walmart_line_data['shipment_items'] ) ) {
								if ( $walmart_line_data['shipment_items'][0]['lineNumber'] == $valdata['lineNumber'] ) {
									$walmart_line_detail    = $walmart_line_data['shipment_items'][0];
									$walmart_rma            = $walmart_line_detail['RMA_number'];
									$walmart_address        = $walmart_line_detail['address'];
									$walmart_days_to_return = $walmart_line_detail['days_to_return'];
								}
							} elseif ( isset( $walmart_line_data['cancel_items'] ) && ! empty( $walmart_line_data['cancel_items'] ) ) {
								if ( $walmart_line_data['cancel_items'][0]['lineNumber'] == $valdata['lineNumber'] ) {
									$walmart_line_detail    = $walmart_line_data['cancel_items'][0];
									$walmart_rma            = $walmart_line_detail['RMA_number'];
									$walmart_address        = $walmart_line_detail['address'];
									$walmart_days_to_return = $walmart_line_detail['days_to_return'];
								}
							}
						}
						$number_items++;
						$cancel_qty      = 0;
						$real_cancel_qty = 0;
						$avail_qty       = $valdata['orderLineQuantity']['amount'];
						$line_number     = $valdata['lineNumber'];
						$cancel_qty      = $order_obj->get_meta( 'walmart_line_item_cancel_' . $line_number, true );
						if ( empty( $cancel_qty ) ) {
							$cancel_qty = 0;
						}
						$shipped_qty = $order_obj->get_meta( 'walmart_line_item_shipped_' . $line_number, true );
						if ( empty( $shipped_qty ) ) {
							$shipped_qty = 0;
						}
						$avail_qty = $avail_qty - ( $shipped_qty + $cancel_qty );
						$ship_qty  = (int) ( $valdata['orderLineQuantity']['amount'] );
						if ( empty( $walmart_address ) ) {
							$walmart_address = 'No';
						} else {
							$walmart_address = 'Yes';
						}
						?>
							<tr>
								<td>
									<input type="hidden" id="lineNumber_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $valdata['lineNumber'] ); ?>">
									<input type="hidden" id="sku_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $valdata['item']['sku'] ); ?>">
									<strong><?php echo esc_attr( $valdata['item']['sku'] ); ?></strong>
								</td>
								<td>
									<input type="hidden" id="qty_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $valdata['orderLineQuantity']['amount'] ); ?>">
									<strong><?php echo esc_attr( $valdata['orderLineQuantity']['amount'] ); ?></strong>
								</td>
							<?php if ( $avail_qty > 0 ) : ?>
									<td>
										<input class="admin__control-text" type="text" maxlength="70" id="ship_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $avail_qty ); ?>" onkeypress="return isNumberKey(event);">
									</td>
									<td>
										<input class="admin__control-text" type="text" id="can_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $cancel_qty ); ?>">
									</td>
								<?php else : ?>
									<td>
										<input type="hidden" id="ship_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $shipped_qty ); ?>">
										<strong 
										<?php
										if ( $avail_qty <= 0 ) {
											echo ' style="color: #EE0000" ';}
										?>
											>
											<?php echo esc_attr( $shipped_qty ); ?>
										</strong>
									</td>
									<td>
										<input type="hidden" id="can_<?php echo esc_attr( $k ); ?>"value="<?php echo esc_attr( $cancel_qty ); ?>">
										<strong 
										<?php
										if ( $avail_qty <= 0 ) {
											echo ' style="color: #EE0000" ';}
										?>
											> 
											<?php echo esc_attr( $cancel_qty ); ?>
										</strong>
									</td>
								<?php endif; ?>
								<td>
									<input type="hidden" id="avail_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $avail_qty ); ?>">
									<strong 
									<?php
									if ( $avail_qty > 0 ) {
										echo ' style="color: #008800" ';
									} else {
										echo ' style="color: #EE0000" ';}
									?>
										> 
									<?php echo esc_attr( $avail_qty ); ?>
									</strong>
								</td>
								<td>
									<?php echo esc_attr( $walmart_address ); ?>
								</td>
								<!-- Rma input -->
								<td>
									<?php echo esc_attr( isset( $walmart_rma ) ? $walmart_rma : '' ); ?>
								</td>
								<td>
									<?php echo esc_attr( isset( $walmart_days_to_return ) ? $walmart_days_to_return : '' ); ?>
								</td>
							</tr>
							<?php
					}
					?>
					</tbody>
				</table>
				
				<?php
			}

			?>
		</div>    
	</div>    
	<?php
}
?>
