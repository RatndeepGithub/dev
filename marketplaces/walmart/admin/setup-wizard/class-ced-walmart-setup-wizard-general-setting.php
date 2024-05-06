<?php


class Ced_Walmart_Setup_Wizard_General_Setting {

	public $formData = array();

	public function __construct() {
	}


	public function render_fields() {
		print_r( ced_walmart_setup_wizard_bar() );
		$this->ced_walmart_submit_general_setting();

		$attr_options = '';

		if ( file_exists( CED_WALMART_DIRPATH . 'admin/setup-wizard/class-ced-walmart-setup-wizard-global-setting.php' ) ) {
			include_once CED_WALMART_DIRPATH . 'admin/setup-wizard/class-ced-walmart-setup-wizard-global-setting.php';
			$obj          = new Ced_Walmart_Setup_Wizard_Global_Setting();
			$attr_options = $obj->ced_walmart_return_attr_options();

		}

		?>
		<form method="post" action="">
			<?php wp_nonce_field( 'ced_genral_setting', 'general_setting' ); ?>
			<div class="wc-progress-form-content woocommerce-importer">
				<header>
					<h2>General Settings</h2>
					<p>Filling the following attributes can improve your listings on Walmart.</p>
				</header>
				<header>
					<h3>Listings Configuration</h3>
					<p>Increase or decrease the price of walmart listings, adjust stock levels from WooCommerce.</p>
					<table class="form-table">
						<tbody>
							<tr valign="top">
								<th scope="row" class="titledesc">
									<label for="woocommerce_currency">
										Column name 
									</label>
								</th>
								<th scope="row" class="titledesc">
									<label for="woocommerce_currency">
										Map to fields
									</label>
								</th>
								<th scope="row" class="titledesc">
									<label for="woocommerce_currency">

										Custom Value
									</label>
								</th>
							</tr>
							<tr>
								<th scope="row" class="titledesc">
									<label for="woocommerce_currency">
										Markup Price  <?php print_r( wc_help_tip( 'Add markup price value.' ) ); ?>
									</label>
								</th>
								<td class="forminp forminp-select">
									<select style="width: 100%;" name="ced_walmart_general_settings[markup_value][metakey]" id="bulk-action-selector-top">
										<?php print_r( $attr_options ); ?>
									</select>
								</td>
								<td class="forminp forminp-select">
									<input style="width: 100%;" name="ced_walmart_general_settings[markup_value][default]" id="" type="text" style="min-width:50px;" value="" class="" placeholder="Enter Value">
								</td>
							</tr>
							<tr>
								<th scope="row" class="titledesc">
									<label for="woocommerce_currency">
										Markup Type <?php print_r( wc_help_tip( 'Select markup type .' ) ); ?>
									</label>
								</th>
								<td class="forminp forminp-select">
									<select style="width: 100%;" name="ced_walmart_general_settings[markup_type]" id="bulk-action-selector-top">
										<option value="">--Select--</option>
										<option value="fixed_increased">Fixed Increased</option>
										<option value="percentage_increased">Percenatage Increased</option>
									</select>
								</td>
								
							</tr>
						</tbody>
					</table>
					<h3>Scheduler Configuration</h3>
					<p>Manage the automatic sync of products, stock and orders.</p>
					<table class="form-table">
						<tbody>
							<tr valign="top">
								<th colspan="2" scope="row" class="titledesc">
									<label for="woocommerce_currency">
										Column name 
									</label>
								</th>
								<th colspan="1" scope="row" class="titledesc">
									<label for="woocommerce_currency">
										Map to fields
									</label>
								</th>
							</tr>
							<tr>
								<th colspan="2" scope="row" class="titledesc">
									<label for="woocommerce_currency">
										Auto Sync Orders <?php print_r( wc_help_tip( 'Auto fetch Walmart orders and create in WooCommerce.' ) ); ?>
									</label>
								</th>
								<td colspan="3" class="forminp forminp-select">
									<select style="width: 100%;" name="ced_walmart_cron_setting[ced_walmart_fetch_orders]" id="bulk-action-selector-top">
										<option value="on">Enabled</option>
										<option value="off">Disabled</option>
									</select>
								</td>
							</tr>


							<tr>
								<th colspan="2" scope="row" class="titledesc">
									<label for="woocommerce_currency">
										Auto Sync Price  <?php print_r( wc_help_tip( 'Auto update price from WooCommerce to Walmart.' ) ); ?>
									</label>
								</th>
								<td colspan="3" class="forminp forminp-select">
									<select style="width: 100%;" name="ced_walmart_cron_setting[ced_auto_update_price]" id="bulk-action-selector-top">
										<option value="on">Enabled</option>
										<option value="off">Disabled</option>
									</select>
								</td>
							</tr>

							<tr>
								<th colspan="2" scope="row" class="titledesc">
									<label for="woocommerce_currency">
										Auto Sync Invetory  <?php print_r( wc_help_tip( 'Auto update inventory from WooCommerce to Walmart.' ) ); ?>
									</label>
								</th>
								<td colspan="3" class="forminp forminp-select">
									<select style="width: 100%;" name="ced_walmart_cron_setting[ced_auto_update_inventory]" id="bulk-action-selector-top">
										<option value="on">Enabled</option>
										<option value="off">Disabled</option>
									</select>
								</td>
							</tr>


							<tr>
								<th colspan="2" scope="row" class="titledesc">
									<label for="woocommerce_currency">
										Sync Existing Products from Walmart  <?php print_r( wc_help_tip( 'Sync Existing Product On the basis of identifier from Walmart' ) ); ?>
									</label>
								</th>
								<td colspan="3" class="forminp forminp-select">
									<select style="width: 100%;" name="ced_walmart_cron_setting[ced_auto_sync_existing_product]" id="bulk-action-selector-top">
										<option value="on">Enabled</option>
										<option value="off">Disabled</option>
									</select>
								</td>
							</tr>
						</tbody>
					</table>
				</header>
				<div class="wc-actions">
					<button type="submit" class="components-button is-secondary">Reset all values</a>
						<button style="float: right;" type="submit" name="ced_walmart_save_general_fields" class="components-button is-primary button-next">Save and continue</button><button style="float: right;" type="submit"  name="ced_walmart_skip_general_fields"class="components-button woocommerce-admin-dismiss-notification button-next">Skip</button>
					</div>
				</div>
			</div>
		</form>
		<?php
	}







	public function ced_walmart_submit_general_setting() {

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			if ( ! isset( $_POST['general_setting'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['general_setting'] ) ), 'ced_genral_setting' ) ) {

				return;
			}

			$this->formData = $_POST;
		}

		if ( ! empty( $this->formData ) ) {
			$store_id = isset( $_GET['store_id'] ) ? sanitize_text_field( $_GET['store_id'] ) : '';

			if ( isset( $this->formData['ced_walmart_save_general_fields'] ) ) {

				$general_fields = $this->formData['ced_walmart_general_settings'];
				$cron_setting   = $this->formData['ced_walmart_cron_setting'];

				$all_settings = get_option( 'ced_walmart_settings', '' );

				if ( ! empty( $all_settings ) ) {
					$all_settings = json_decode( $all_settings, true );
				} else {
					$all_settings = array();
				}

				foreach ( $general_fields as $key => $value ) {
					if ( isset( $general_fields[ $key ] ) ) {
						if ( ! empty( $general_fields[ $key ]['metakey'] ) || ! empty( $general_fields[ $key ]['default'] ) ) {
							$all_settings[ $store_id ]['general_settings'][ $key ]['metakey']             = isset( $general_fields[ $key ]['metakey'] ) ? $general_fields[ $key ]['metakey'] : '';
							$all_settings[ $store_id ]['general_settings'][ 'global_' . $key ]['default'] = isset( $general_fields[ $key ]['default'] ) ? $general_fields[ $key ]['default'] : '';
						} else {
							$all_settings[ $store_id ]['general_settings'][ $key ] = $general_fields[ $key ];
						}
					}
				}

				$auto_fetch_orders          = isset( $cron_setting['ced_walmart_fetch_orders'] ) ? $cron_setting['ced_walmart_fetch_orders'] : '';
				$auto_update_price          = isset( $cron_setting['ced_auto_update_price'] ) ? $cron_setting['ced_auto_update_price'] : '';
				$auto_update_inventory      = isset( $cron_setting['ced_auto_update_inventory'] ) ? $cron_setting['ced_auto_update_inventory'] : '';
				$auto_sync_existing_product = isset( $cron_setting['ced_auto_sync_existing_product'] ) ? $cron_setting['ced_auto_sync_existing_product'] : '';

				if ( 'on' == $auto_fetch_orders ) {
					wp_clear_scheduled_hook( 'ced_walmart_auto_fetch_orders_' . $store_id );
					$all_settings[ $store_id ]['general_settings']['cron_setting']['ced_walmart_fetch_orders'] = $auto_fetch_orders;
					wp_schedule_event( time(), 'ced_walmart_15min', 'ced_walmart_auto_fetch_orders_' . $store_id );
				} else {
					wp_clear_scheduled_hook( 'ced_walmart_auto_fetch_orders_' . $store_id );
					$all_settings[ $store_id ]['general_settings']['cron_setting']['ced_walmart_fetch_orders'] = $auto_fetch_orders;
				}

				if ( 'on' == $auto_update_price ) {
					wp_clear_scheduled_hook( 'ced_walmart_auto_update_price_' . $store_id );
					$all_settings[ $store_id ]['general_settings']['cron_setting']['ced_auto_update_price'] = $auto_update_price;
					wp_schedule_event( time(), 'ced_walmart_30min', 'ced_walmart_auto_update_price_' . $store_id );
				} else {
					wp_clear_scheduled_hook( 'ced_walmart_auto_update_price_' . $store_id );
					$all_settings[ $store_id ]['general_settings']['cron_setting']['ced_auto_update_price'] = $auto_update_price;

				}

				if ( 'on' == $auto_update_inventory ) {
					wp_clear_scheduled_hook( 'ced_walmart_auto_update_inventory_' . $store_id );
					$all_settings[ $store_id ]['general_settings']['cron_setting']['ced_auto_update_inventory'] = $auto_update_inventory;
					wp_schedule_event( time(), 'ced_walmart_30min', 'ced_walmart_auto_update_inventory_' . $store_id );
				} else {
					wp_clear_scheduled_hook( 'ced_walmart_auto_update_inventory_' . $store_id );
					$all_settings[ $store_id ]['general_settings']['cron_setting']['ced_auto_update_inventory'] = $auto_update_inventory;

				}

				if ( 'on' == $auto_sync_existing_product ) {
					wp_clear_scheduled_hook( 'ced_walmart_auto_sync_existing_products_' . $store_id );
					$all_settings[ $store_id ]['general_settings']['cron_setting']['ced_auto_sync_existing_product'] = $auto_sync_existing_product;
					wp_schedule_event( time(), 'ced_walmart_5min', 'ced_walmart_auto_sync_existing_products_' . $store_id );
				} else {
					wp_clear_scheduled_hook( 'ced_walmart_auto_sync_existing_products_' . $store_id );
					$all_settings[ $store_id ]['general_settings']['cron_setting']['ced_auto_sync_existing_product'] = $auto_sync_existing_product;

				}

				update_option( 'ced_walmart_settings', json_encode( $all_settings ) );
				$this->ced_walmart_update_status( $store_id );

			} elseif ( isset( $this->formData['ced_walmart_skip_general_fields'] ) ) {
				$this->ced_walmart_update_status( $store_id );
			}
		}
	}


	public function ced_walmart_update_status( $store_id ) {
		$account_list                                 = ced_walmart_return_partner_detail_option();
		$account_list[ $store_id ]['general_setting'] = true;
		$step = isset( $_GET['step'] ) ? sanitize_text_field( $_GET['step'] ) : '';
		ced_walmart_update_steps( $step );
		$redirect_url                              = add_query_arg(
			array(
				'action'   => 'setup-wizard',
				'step'     => 'completed',
				'store_id' => $store_id,
			)
		);
		$account_list[ $store_id ]['current_step'] = $redirect_url;
		update_option( 'ced_walmart_saved_account_list', json_encode( $account_list ) );
		wp_safe_redirect( esc_url_raw( $redirect_url ) );
	}
}


$obj = new Ced_Walmart_Setup_Wizard_General_Setting();
