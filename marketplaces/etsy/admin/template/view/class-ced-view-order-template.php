<?php
/**
 * Shipment Order Template
 *
 * @package  Woocommerce_Etsy_Integration
 * @version  1.0.0
 * @since    1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}


if ( empty( $umb_etsy_order_status ) || 'Fetched' == $umb_etsy_order_status ) {
	$umb_etsy_order_status = 'Created';
}

?>

<div id="umb_etsy_order_settings" class="panel woocommerce_options_panel">
	<div class="ced_etsy_loader" class="loading-style-bg" style="display: none;">
		<img src="<?php echo esc_url( CED_ETSY_URL . 'admin/assets/images/loading.gif' ); ?>">
	</div>

	<div class="options_group">
		<p class="form-field">
			<h3><center>
				<?php
				esc_html_e( 'ETSY ORDER STATUS : ', 'woocommerce-etsy-integration' );
				echo esc_html( strtoupper( $umb_etsy_order_status ) );
				?>
			</center></h3>
		</p>
	</div>
	<div class="ced_etsy_error"></div>
	<div class="options_group umb_etsy_options">
		<?php
		if ( 'Created' == $umb_etsy_order_status ) {
			?>
			<div id="ced_etsy_shipment_wrap">
				<div>
					<table class="form-table ced-settings widefat">
						<tbody>
							<tr>
								<td>
									<span><?php esc_html_e( 'Tracking Code', 'woocommerce-etsy-integration' ); ?></span>
								</td>
								<td>
									<input type="text" name="" id="ced_etsy_tracking_code" class="ced_etsy_required_data">
								</td>
							</tr>
							<tr>
								<td>
									<span><?php esc_html_e( 'Carrier Name', 'woocommerce-etsy-integration' ); ?></span>
								</td>
								<td>
									<input type="text" name="" id="ced_etsy_carrier_name" class="ced_etsy_required_data">
								</td>
							</tr>
							<tr>
								<td>
									<input type="button" class="button button-primary" name="" id="ced_etsy_submit_shipment" value="<?php esc_attr_e( 'Submit', 'woocommerce-etsy-integration' ); ?>" data-order-id="<?php echo esc_attr( $order_id ); ?>">
								</td>
								<td>	
									<span class="ced_spinner spinner"></span>									
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<?php
		}
		?>
	</div>    
</div>
