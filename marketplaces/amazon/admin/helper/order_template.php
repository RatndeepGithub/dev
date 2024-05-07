<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

global $post;


$order_id                = isset( $post->ID ) ? intval( $post->ID ) : '';
$feedstatus              = get_post_meta( $order_id, '_umb_order_feed_status', true );
$umb_amazon_order_status = get_post_meta( $order_id, '_amazon_umb_order_status', true );
$amazon_shipped_details  = get_post_meta( $order_id, 'umb_amazon_shippied_data', true );

if ( ( isset( $amazon_shipped_details ) && ! empty( $amazon_shipped_details ) ) || ( isset( $umb_amazon_order_status ) && ! empty( $umb_amazon_order_status ) ) ) {
	$merchant_order_id = get_post_meta( $order_id, 'amazon_order_id', true );
	$order_detail      = get_post_meta( $order_id, 'order_detail', true );
	$order_items       = get_post_meta( $order_id, 'order_items', true );
	$order_details     = get_post_meta( $order_id, 'order_item_detail', true );
	$number_items      = 0;

	// Get order status

	$umb_amazon_order_status = get_post_meta( $order_id, '_amazon_umb_order_status', true );

	if ( empty( $umb_amazon_order_status ) ) {
		$umb_amazon_order_status = __( 'Created', 'amazon-for-woocommerce' );
	}
	?>
	
	<div id="umb_amazon_order_settings" class="panel woocommerce_options_panel">
		<div class="ced_amazon_loader">
			<img src="<?php echo esc_attr( CED_AMAZON_URL ) . 'admin/images/loading.gif'; ?>" width="50px" height="50px" class="ced_amazon_loading_img" style="display: none;" >
		</div>
	
		<div class="options_group">
		<p class="form-field">
				<h3><center>
				<?php
				esc_attr_e( 'AMAZON ORDER STATUS : ', 'amazon-for-woocommerce' );
				echo esc_attr( strtoupper( $umb_amazon_order_status ) );
				?>
				</center></h3>
			</p>
		</div>
	<div class="options_group umb_amazon_options"> 
	<?php
	if ( $feedstatus ) {
		$feeddetails = get_post_meta( $order_id, '_umb_order_feed_details', true );
		?>
			<p class="form-field">
			<b><?php echo esc_attr_e( 'Order', 'amazon-for-woocommerce' ) . esc_attr( $feeddetails['request'] ) . esc_attr_e( 'request is under process', 'amazon-for-woocommerce' ); ?></b>
			<input type="button" class="button primary " value="Check Status" data-order_id = "<?php echo esc_attr( $order_id ); ?>" data-feed_id = "<?php echo esc_attr( $feeddetails['id'] ); ?>" data-feed_req = "<?php echo esc_attr( $feeddetails['request'] ); ?>"  id="umb_amazon_checkfeedstatus"/>
		</p>
		<?php
	} else {
		if ( 'Cancelled' == $umb_amazon_order_status ) {
			?>
				<h1 style="text-align:center;"><?php esc_attr_e( 'ORDER CANCELLED ', 'amazon-for-woocommerce' ); ?></h1>
			<?php
		}
		$umb_amazon_order_status = 'Acknowledged';

		if ( 'Created' == $umb_amazon_order_status ) {
			?>
			<p class="form-field">
			<label><?php esc_attr_e( 'Select Order Action:', 'amazon-for-woocommerce' ); ?></label>
			<input type="button" class="button primary " value="<?php esc_attr_e( 'Acknowledge Order', 'amazon-for-woocommerce' ); ?>" data-order_id = "<?php echo esc_attr( $order_id ); ?>" id="umb_amazon_ack_action"/>
			<input type="button" class="button primary " value="<?php esc_attr_e( 'Cancel Order', 'amazon-for-woocommerce' ); ?>" data-order_id = "<?php echo esc_attr( $order_id ); ?>" id="umb_amazon_cancel_action"/>
		</p>
			<?php
		} elseif ( 'Acknowledged' == $umb_amazon_order_status ) {
			?>
				<input type="hidden" id="amazon_orderid" value="<?php echo esc_attr( $order_detail['AmazonOrderId'] ); ?>" readonly>
				<input type="hidden" id="woocommerce_orderid" value="<?php echo esc_attr( $order_id ); ?>">
				<h2 class="title"><?php esc_attr_e( 'Shipment Information', 'amazon-for-woocommerce' ); ?> -
			  
				<!-- Ship Complete Order -->
				<div id="ced_umb_amazon_complete_order_shipping">
					<table class="wp-list-table widefat fixed striped">
					<tbody>
							<tr>
								<td><b><?php esc_attr_e( 'Reference Order Id on Amazon.com', 'amazon-for-woocommerce' ); ?></b></td>
								<td><?php echo esc_attr( $order_detail['AmazonOrderId'] ); ?></td>
							</tr>
						<tr>
								<td><b><?php esc_attr_e( 'Order Placed on Amazon.com', 'amazon-for-woocommerce' ); ?></b></td>
								<td><?php echo esc_attr( gmdate( 'l, F jS Y \a\t g:ia', strtotime( $order_detail['PurchaseDate'] ) ) ); ?></td>
						</tr>
						<tr>
							<td><b><?php esc_attr_e( 'Shipping carrier used', 'amazon-for-woocommerce' ); ?></b></td>
							<td>
								<select id="umb_amazon_carrier_order">
									<option value="USPS"><?php esc_attr_e( 'USPS', 'amazon-for-woocommerce' ); ?></option>
									<option value="UPS"><?php esc_attr_e( 'UPS', 'amazon-for-woocommerce' ); ?></option>
									<option value="UPSMI"><?php esc_attr_e( 'UPSMI', 'amazon-for-woocommerce' ); ?></option>
									<option value="FedEx"><?php esc_attr_e( 'FedEx', 'amazon-for-woocommerce' ); ?></option>
									<option value="DHL"><?php esc_attr_e( 'DHL', 'amazon-for-woocommerce' ); ?></option>
									<option value="Fastway"><?php esc_attr_e( 'Fastway', 'amazon-for-woocommerce' ); ?></option>
									<option value="GLS"><?php esc_attr_e( 'GLS', 'amazon-for-woocommerce' ); ?></option>
									<option value="GO!"><?php esc_attr_e( 'GO!', 'amazon-for-woocommerce' ); ?></option>
									<option value="Hermes Logistik Gruppe"><?php esc_attr_e( 'Hermes Logistik Gruppe', 'amazon-for-woocommerce' ); ?></option>
									<option value="Royal Mail"><?php esc_attr_e( 'Royal Mail', 'amazon-for-woocommerce' ); ?></option>
									<option value="Parcelforce"><?php esc_attr_e( 'Parcelforce', 'amazon-for-woocommerce' ); ?></option>
									<option value="City Link"><?php esc_attr_e( 'City Link', 'amazon-for-woocommerce' ); ?></option>
									<option value="TNT"><?php esc_attr_e( 'TNT', 'amazon-for-woocommerce' ); ?></option>
									<option value="Target"><?php esc_attr_e( 'Target', 'amazon-for-woocommerce' ); ?></option>
									<option value="SagawaExpress"><?php esc_attr_e( 'SagawaExpress', 'amazon-for-woocommerce' ); ?></option>
									<option value="NipponExpress"><?php esc_attr_e( 'NipponExpress', 'amazon-for-woocommerce' ); ?></option>
									<option value="YamatoTransport"><?php esc_attr_e( 'YamatoTransport', 'amazon-for-woocommerce' ); ?></option>
									<option value="DHL Global Mail"><?php esc_attr_e( 'DHL Global Mail', 'amazon-for-woocommerce' ); ?></option>
									<option value="UPS Mail Innovations"><?php esc_attr_e( 'UPS Mail Innovations', 'amazon-for-woocommerce' ); ?></option>
									<option value="FedEx SmartPost"><?php esc_attr_e( 'FedEx SmartPost', 'amazon-for-woocommerce' ); ?></option>
									<option value="OSM"><?php esc_attr_e( 'OSM', 'amazon-for-woocommerce' ); ?></option>
									<option value="OnTrac"><?php esc_attr_e( 'OnTrac', 'amazon-for-woocommerce' ); ?></option>
									<option value="Streamlite"><?php esc_attr_e( 'Streamlite', 'amazon-for-woocommerce' ); ?></option>
									<option value="Newgistics"><?php esc_attr_e( 'Newgistics', 'amazon-for-woocommerce' ); ?></option>
									<option value="Canada Post"><?php esc_attr_e( 'Canada Post', 'amazon-for-woocommerce' ); ?></option>
									<option value="Blue Package"><?php esc_attr_e( 'Blue Package', 'amazon-for-woocommerce' ); ?></option>
									<option value="Chronopost"><?php esc_attr_e( 'Chronopost', 'amazon-for-woocommerce' ); ?></option>
									<option value="Deutsche Post"><?php esc_attr_e( 'Deutsche Post', 'amazon-for-woocommerce' ); ?></option>
									<option value="DPD"><?php esc_attr_e( 'DPD', 'amazon-for-woocommerce' ); ?></option>
									<option value="La Poste"><?php esc_attr_e( 'La Poste', 'amazon-for-woocommerce' ); ?></option>
									<option value="Poste Italiane"><?php esc_attr_e( 'Poste Italiane', 'amazon-for-woocommerce' ); ?></option>
									<option value="SDA"><?php esc_attr_e( 'SDA', 'amazon-for-woocommerce' ); ?></option>
									<option value="Smartmail"><?php esc_attr_e( 'Smartmail', 'amazon-for-woocommerce' ); ?></option>
									<option value="FEDEX_JP"><?php esc_attr_e( 'FEDEX_JP', 'amazon-for-woocommerce' ); ?></option>
									<option value="JPesc_attr_eXPRESS"><?php esc_attr_e( 'JPesc_attr_eXPRESS', 'amazon-for-woocommerce' ); ?></option>
									<option value="NITTSU"><?php esc_attr_e( 'NITTSU', 'amazon-for-woocommerce' ); ?></option>
									<option value="SAGAWA"><?php esc_attr_e( 'SAGAWA', 'amazon-for-woocommerce' ); ?></option>
									<option value="YAMATO"><?php esc_attr_e( 'YAMATO', 'amazon-for-woocommerce' ); ?></option>
									<option value="BlueDart"><?php esc_attr_e( 'BlueDart', 'amazon-for-woocommerce' ); ?></option>
									<option value="AFL/Fedex"><?php esc_attr_e( 'AFL/Fedex', 'amazon-for-woocommerce' ); ?></option>
									<option value="Aramex"><?php esc_attr_e( 'Aramex', 'amazon-for-woocommerce' ); ?></option>
									<option value="India Post"><?php esc_attr_e( 'India Post', 'amazon-for-woocommerce' ); ?></option>
									<option value="Australia Post"><?php esc_attr_e( 'Australia Post', 'amazon-for-woocommerce' ); ?></option>
									<option value="Professional"><?php esc_attr_e( 'Professional', 'amazon-for-woocommerce' ); ?></option>
									<option value="DTDC"><?php esc_attr_e( 'DTDC', 'amazon-for-woocommerce' ); ?></option>
									<option value="Overnite Express"><?php esc_attr_e( 'Overnite Express', 'amazon-for-woocommerce' ); ?></option>
									<option value="First Flight"><?php esc_attr_e( 'First Flight', 'amazon-for-woocommerce' ); ?></option>
									<option value="Delhivery"><?php esc_attr_e( 'Delhivery', 'amazon-for-woocommerce' ); ?></option>
									<option value="Lasership"><?php esc_attr_e( 'Lasership', 'amazon-for-woocommerce' ); ?></option>
									<option value="Yodel"><?php esc_attr_e( 'Yodel', 'amazon-for-woocommerce' ); ?></option>
									<option value="Other"><?php esc_attr_e( 'Other', 'amazon-for-woocommerce' ); ?></option>
								</select>
								<input type="text" id="umb_amazon_other_carrier" name="umb_amazon_other_carrier" value="" style="margin-top: 5px; width: 70%; display: none;">
							</td>
						</tr>
						<tr>
							<td><b><?php esc_attr_e( 'Shipping Type', 'amazon-for-woocommerce' ); ?></b></td>
							<td>
								<select id="umb_amazon_methodCode_order">
									<option value="Standard"><?php esc_attr_e( 'Standard', 'amazon-for-woocommerce' ); ?></option>
									<option value="Express"><?php esc_attr_e( 'Express', 'amazon-for-woocommerce' ); ?></option>
									<option value="OneDay"><?php esc_attr_e( 'OneDay', 'amazon-for-woocommerce' ); ?></option>
									<option value="Freight"><?php esc_attr_e( 'Freight', 'amazon-for-woocommerce' ); ?></option>
									<option value="WhiteGlove"><?php esc_attr_e( 'WhiteGlove', 'amazon-for-woocommerce' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<td><b><?php esc_attr_e( 'Tracking Number', 'amazon-for-woocommerce' ); ?></b></td>
							<td><input type="text" id="umb_amazon_tracking_order" value=""></td>
						</tr>
						<tr>
							<td><b><?php esc_attr_e( 'Fulfillment Date', 'amazon-for-woocommerce' ); ?></b></td>
							<td><input class=" input-text required-entry"  type="text" id="umb_amazon_ship_date_order" name="ship_date"/></td>
						</tr>
					</tbody>
				</table>	
			</div>
			 
			<input data-items="<?php echo esc_attr( $number_items ); ?>" type="button" class="button" id="ced_amzon_shipment_submit" value="<?php esc_attr_e( 'Submit Shipment', 'amazon-for-woocommerce' ); ?>">
			<?php
		} elseif ( 'Shipped' == $umb_amazon_order_status ) {
			$amazon_postshipped_data = get_post_meta( $order_id, 'ced_amzon_shipped_data', true );
			$amazon_shipped_details  = get_post_meta( $order_id, 'umb_amazon_shippied_data', true );

			$amazon_shipping_carrier = isset( $amazon_postshipped_data[0]['carrier'] ) ? $amazon_postshipped_data[0]['carrier'] : '';
			$amazon_shipping_type    = isset( $amazon_postshipped_data[0]['methodCode'] ) ? $amazon_postshipped_data[0]['methodCode'] : '';
			$amazon_tracking_no      = isset( $amazon_postshipped_data[0]['tracking'] ) ? $amazon_postshipped_data[0]['tracking'] : '';
			$amazon_ship_date        = isset( $amazon_postshipped_data[0]['ship_todate'] ) ? $amazon_postshipped_data[0]['ship_todate'] : '';
			?>
				<input type="hidden" id="amazon_orderid" value="<?php echo esc_attr( $amazon_shipped_details['AmazonOrderId'] ); ?>" readonly>
				<input type="hidden" id="woocommerce_orderid" value="<?php echo esc_attr( $amazon_postshipped_data['order'] ); ?>">
				<h2 class="title"><?php esc_attr_e( 'Shipment Information' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
				<tbody>
						<tr>
							<td><b><?php esc_attr_e( 'Reference Order Id on Amazon.com', 'amazon-for-woocommerce' ); ?></b></td>
							<td><?php echo esc_attr( $order_detail['AmazonOrderId'] ); ?></td>
						</tr>
						<tr>
							<td><b><?php esc_attr_e( 'Order Placed on Amazon.com', 'amazon-for-woocommerce' ); ?></b></td>
							<td><?php echo esc_attr( gmdate( 'l, F jS Y \a\t g:ia', strtotime( $order_detail['PurchaseDate'] ) ) ); ?></td>
						</tr>
						<tr>
							<td><b><?php esc_attr_e( 'Shipping carrier used', 'amazon-for-woocommerce' ); ?></b></td>
							<td>
							<?php echo esc_attr( $amazon_shipping_carrier ); ?>
							</td>
						</tr>
						<tr>
							<td><b><?php esc_attr_e( 'Shipping Type', 'amazon-for-woocommerce' ); ?></b></td>
							<td>
						   
								<?php echo esc_attr( $amazon_shipping_type ); ?>
						</td>
						</tr>
						<tr>
							<td><b><?php esc_attr_e( 'Tracking Number', 'amazon-for-woocommerce' ); ?></b></td>
							<td><?php echo esc_attr( $amazon_tracking_no ); ?></td>
						</tr>
				  
						<tr>
							<td><b><?php esc_attr_e( 'Ship To Date', 'amazon-for-woocommerce' ); ?></td>
							<td><?php echo esc_attr( gmdate( 'l, F jS Y \a\t g:ia', strtotime( $amazon_ship_date ) ) ); ?></td>
						</tr>
					</tbody>
				</table>  
			<?php
		}
	}
	?>
	</div>    
</div>    
	<?php
}
?>
