<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil as CedAmazonHOPS;

global $post;
$create_amz_order_hops = false;

if ( CedAmazonHOPS::custom_orders_table_usage_is_enabled() ) {
	$create_amz_order_hops = true;
}


if ( ! $create_amz_order_hops ) {

	$order_id                = isset( $post->ID ) ? intval( $post->ID ) : '';
	$feedstatus              = get_post_meta( $order_id, '_umb_order_feed_status', true );
	$umb_amazon_order_status = get_post_meta( $order_id, '_amazon_umb_order_status', true );
	$amazon_shipped_details  = get_post_meta( $order_id, 'umb_amazon_shippied_data', true );

} else {
	$order_id = isset( $_GET['id'] ) ? sanitize_text_field( $_GET['id'] ) : '';

	$wc_order = wc_get_order( $order_id );

	$feedstatus              = $wc_order->get_meta( '_umb_order_feed_status' );
	$umb_amazon_order_status = $wc_order->get_meta( '_amazon_umb_order_status' );
	$amazon_shipped_details  = $wc_order->get_meta( 'umb_amazon_shippied_data' );

}


if ( ( isset( $amazon_shipped_details ) && ! empty( $amazon_shipped_details ) ) || ( isset( $umb_amazon_order_status ) && ! empty( $umb_amazon_order_status ) ) ) {

	if ( ! $create_amz_order_hops ) {
		$merchant_order_id       = get_post_meta( $order_id, 'amazon_order_id', true );
		$order_detail            = get_post_meta( $order_id, 'order_detail', true );
		$order_items             = get_post_meta( $order_id, 'order_items', true );
		$order_details           = get_post_meta( $order_id, 'order_item_detail', true );
		$umb_amazon_order_status = get_post_meta( $order_id, '_amazon_umb_order_status', true );

	} else {
		$merchant_order_id       = $wc_order->get_meta( 'amazon_order_id' );
		$order_detail            = $wc_order->get_meta( 'order_detail' );
		$order_items             = $wc_order->get_meta( 'order_items' );
		$order_details           = $wc_order->get_meta( 'order_item_detail' );
		$umb_amazon_order_status = $wc_order->get_meta( '_amazon_umb_order_status' );

	}

	$number_items = 0;

	// Get order status

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
		if ( ! $create_amz_order_hops ) {
			$feeddetails = get_post_meta( $order_id, '_umb_order_feed_details', true );
		} else {
			$feeddetails = $wc_order->get_meta( '_umb_order_feed_details' );
		}
		?>
			<p class="form-field">
			<b><?php echo esc_attr_e( 'Order ', 'amazon-for-woocommerce' ) . esc_attr( $feeddetails['request'] ) . esc_attr( ' request is under process', 'amazon-for-woocommerce' ); ?></b>
			<input type="button" class="button primary " value="Check Status" data-hpos="<?php echo esc_attr( $create_amz_order_hops ); ?>" data-order_id = "<?php echo esc_attr( $order_id ); ?>" data-feed_id = "<?php echo esc_attr( $feeddetails['id'] ); ?>" data-feed_req = "<?php echo esc_attr( $feeddetails['request'] ); ?>"  id="umb_amazon_checkfeedstatus"/>
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

							<?php
							   $carrier_options = array(
								   'USPS'                 => esc_attr__( 'USPS', 'amazon-for-woocommerce' ),
								   'UPS'                  => esc_attr__( 'UPS', 'amazon-for-woocommerce' ),
								   'UPSMI'                => esc_attr__( 'UPSMI', 'amazon-for-woocommerce' ),
								   'FedEx'                => esc_attr__( 'FedEx', 'amazon-for-woocommerce' ),
								   'DHL'                  => esc_attr__( 'DHL', 'amazon-for-woocommerce' ),
								   'Fastway'              => esc_attr__( 'Fastway', 'amazon-for-woocommerce' ),
								   'GLS'                  => esc_attr__( 'GLS', 'amazon-for-woocommerce' ),
								   'GO!'                  => esc_attr__( 'GO!', 'amazon-for-woocommerce' ),
								   'Hermes Logistik Gruppe' => esc_attr__( 'Hermes Logistik Gruppe', 'amazon-for-woocommerce' ),
								   'Royal Mail'           => esc_attr__( 'Royal Mail', 'amazon-for-woocommerce' ),
								   'Parcelforce'          => esc_attr__( 'Parcelforce', 'amazon-for-woocommerce' ),
								   'City Link'            => esc_attr__( 'City Link', 'amazon-for-woocommerce' ),
								   'TNT'                  => esc_attr__( 'TNT', 'amazon-for-woocommerce' ),
								   'Target'               => esc_attr__( 'Target', 'amazon-for-woocommerce' ),
								   'SagawaExpress'        => esc_attr__( 'SagawaExpress', 'amazon-for-woocommerce' ),
								   'NipponExpress'        => esc_attr__( 'NipponExpress', 'amazon-for-woocommerce' ),
								   'YamatoTransport'      => esc_attr__( 'YamatoTransport', 'amazon-for-woocommerce' ),
								   'DHL Global Mail'      => esc_attr__( 'DHL Global Mail', 'amazon-for-woocommerce' ),
								   'UPS Mail Innovations' => esc_attr__( 'UPS Mail Innovations', 'amazon-for-woocommerce' ),
								   'FedEx SmartPost'      => esc_attr__( 'FedEx SmartPost', 'amazon-for-woocommerce' ),
								   'OSM'                  => esc_attr__( 'OSM', 'amazon-for-woocommerce' ),
								   'OnTrac'               => esc_attr__( 'OnTrac', 'amazon-for-woocommerce' ),
								   'Streamlite'           => esc_attr__( 'Streamlite', 'amazon-for-woocommerce' ),
								   'Newgistics'           => esc_attr__( 'Newgistics', 'amazon-for-woocommerce' ),
								   'Canada Post'          => esc_attr__( 'Canada Post', 'amazon-for-woocommerce' ),
								   'Blue Package'         => esc_attr__( 'Blue Package', 'amazon-for-woocommerce' ),
								   'Chronopost'           => esc_attr__( 'Chronopost', 'amazon-for-woocommerce' ),
								   'Deutsche Post'        => esc_attr__( 'Deutsche Post', 'amazon-for-woocommerce' ),
								   'DPD'                  => esc_attr__( 'DPD', 'amazon-for-woocommerce' ),
								   'La Poste'             => esc_attr__( 'La Poste', 'amazon-for-woocommerce' ),
								   'Poste Italiane'       => esc_attr__( 'Poste Italiane', 'amazon-for-woocommerce' ),
								   'SDA'                  => esc_attr__( 'SDA', 'amazon-for-woocommerce' ),
								   'Smartmail'            => esc_attr__( 'Smartmail', 'amazon-for-woocommerce' ),
								   'FEDEX_JP'             => esc_attr__( 'FEDEX_JP', 'amazon-for-woocommerce' ),
								   'JPesc_attr_eXPRESS'   => esc_attr__( 'JPesc_attr_eXPRESS', 'amazon-for-woocommerce' ),
								   'NITTSU'               => esc_attr__( 'NITTSU', 'amazon-for-woocommerce' ),
								   'SAGAWA'               => esc_attr__( 'SAGAWA', 'amazon-for-woocommerce' ),
								   'YAMATO'               => esc_attr__( 'YAMATO', 'amazon-for-woocommerce' ),
								   'BlueDart'             => esc_attr__( 'BlueDart', 'amazon-for-woocommerce' ),
								   'AFL/Fedex'            => esc_attr__( 'AFL/Fedex', 'amazon-for-woocommerce' ),
								   'Aramex'               => esc_attr__( 'Aramex', 'amazon-for-woocommerce' ),
								   'India Post'           => esc_attr__( 'India Post', 'amazon-for-woocommerce' ),
								   'Australia Post'       => esc_attr__( 'Australia Post', 'amazon-for-woocommerce' ),
								   'Professional'         => esc_attr__( 'Professional', 'amazon-for-woocommerce' ),
								   'DTDC'                 => esc_attr__( 'DTDC', 'amazon-for-woocommerce' ),
								   'Overnite Express'     => esc_attr__( 'Overnite Express', 'amazon-for-woocommerce' ),
								   'First Flight'         => esc_attr__( 'First Flight', 'amazon-for-woocommerce' ),
								   'Delhivery'            => esc_attr__( 'Delhivery', 'amazon-for-woocommerce' ),
								   'Lasership'            => esc_attr__( 'Lasership', 'amazon-for-woocommerce' ),
								   'Yodel'                => esc_attr__( 'Yodel', 'amazon-for-woocommerce' ),
								   'Other'                => esc_attr__( 'Other', 'amazon-for-woocommerce' ),

							   );

								?>

								<select id="umb_amazon_carrier_order">
									<?php foreach ( $carrier_options as $value => $label ) { ?>
										<option value="<?php echo $value; ?>"><?php echo $label; ?></option>
									<?php } ?>
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
							<td><input class=" input-text required-entry"  type="date" id="umb_amazon_ship_date_order" name="ship_date"/></td>
						</tr>
					</tbody>
				</table>	
			</div>
			 
			<input data-items="<?php echo esc_attr( $number_items ); ?>" data-hpos="<?php echo esc_attr( $create_amz_order_hops ); ?>" type="button" class="button" id="ced_amzon_shipment_submit" value="<?php esc_attr_e( 'Submit Shipment', 'amazon-for-woocommerce' ); ?>">
			<?php
		} elseif ( 'Shipped' == $umb_amazon_order_status ) {

			if ( ! $create_amz_order_hops ) {
				$amazon_postshipped_data = get_post_meta( $order_id, 'ced_amzon_shipped_data', true );
				$amazon_shipped_details  = get_post_meta( $order_id, 'umb_amazon_shippied_data', true );

			} else {
				$amazon_postshipped_data = $wc_order->get_meta( 'ced_amzon_shipped_data' );
				$amazon_shipped_details  = $wc_order->get_meta( 'umb_amazon_shippied_data' );
			}

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
