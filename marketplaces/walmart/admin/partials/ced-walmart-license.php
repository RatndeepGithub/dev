<div class="ced_walmart_loader">
	<img src="<?php echo esc_url( CED_WALMART_URL . 'admin/images/loading.gif' ); ?>" width="50px" height="50px" class="ced_walmart_loading_img" >
</div>
<div class="success-admin-notices" ></div>
<div class="ced_walmart_wrap">
	<h2 class="ced_walmart_setting_header ced_walmart_bottom_margin"><?php esc_html_e( 'WALMART LICENSE CONFIGURATION', 'woocommerce-walmart-integration' ); ?></h2>
	<div class="ced_walmart_license_divs">
		<form method="post">
			<table class="wp-list-table widefat fixed striped ced_walmart_config_table">
				<tbody>
					<tr>
						<th class="manage-column">
							<label><b><?php esc_html_e( 'Enter License Key', 'woocommerce-walmart-integration' ); ?></b></label>
							<input type="text" value="" class="ced_walmart_inputs" id="ced_walmart_license_key">
						</th>
						<td>
							<input type="button" value="Validate" class="ced_walmart_custom_button" id="ced_walmart_save_license" class="button button-ced_walmart button-primary">
						</td>
					</tr>
				</tbody>
			</table>
		</form>
	</div>
<div>
