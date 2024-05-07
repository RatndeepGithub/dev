<?php

class Ced_Walmart_Settings {

	public $formData = array();

	public $all_settings = array();
	public function __construct() {
	}


	public function ced_walmart_settings_render() {

		?>

		<div class="components-card is-size-medium woocommerce-table">
			<div class="components-panel">
				<div class="wc-progress-form-content woocommerce-importer ced-padding">

					<form method="post" action="">
						<?php wp_nonce_field( 'ced_global_mapping', 'global_mapping' ); ?>
						<?php $this->ced_walmart_submit_global_setting(); ?>
						<?php $this->ced_walmart_product_export_settings(); ?>
						<?php $this->ced_walmart_order_import_setting(); ?>
						<?php $this->ced_walmart_cron_setting(); ?>

						<div class="ced-button-wrapper">
							<button type="submit" name="ced_walmart_save_global_fields" class="button-primary"><?php esc_html_e( 'Save Settings', 'walmart-woocommerce-integration' ); ?></button>			
						</div>
					</form>

					
				</div>
			</div>
		</div>

		<?php
	}



	public function ced_walmart_product_export_settings() {
		?>



		<div class="ced-walmart-integ-wrapper">
			<input class="ced-faq-trigger" id="product-export-setting" type="checkbox" checked />
			<label class="ced-walmart-settng-title" for="product-export-setting"> Product Export Setting  </label>
			<div class="ced-walmart-settng-content-wrap">
				<div class="ced-walmart-settng-content-holder">
					<div class="ced-form-accordian-wrap">
						<div class="wc-progress-form-content woocommerce-importer">
							<header>

								<div class="ced_walmart_child_element">
									<table class="form-table ced-settings">
										<tbody class="ced-settings-body-productspecificattributesExport">
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
											<?php

											$fields_file = CED_WALMART_DIRPATH . 'admin/setup-wizard/class-ced-walmart-setup-wizard-global-setting.php';
											if ( file_exists( $fields_file ) ) {
												include_once $fields_file;

												$objGlobalSetting = new Ced_Walmart_Setup_Wizard_Global_Setting();
												print_r( $objGlobalSetting->ced_walmart_render_global_fields() );
											}

											?>

										</tbody>
									</table>
								</div>
							</header>
						</div>
					</div>
				</div>
			</div>	
		</div>

		<?php
	}


	public function ced_walmart_order_import_setting() {

		$get_all_setting = get_option( 'ced_walmart_settings', '' );
		$store_id        = isset( $_GET['store_id'] ) ? sanitize_text_field( $_GET['store_id'] ) : '';

		if ( ! empty( $get_all_setting ) ) {
			$get_all_setting = json_decode( $get_all_setting, true );
		} else {
			$get_all_setting = array();
		}

		$order_prefix         = '';
		$use_walmart_order_id = '';

		if ( isset( $get_all_setting[ $store_id ] ) && isset( $get_all_setting[ $store_id ]['general_settings']['order_setting'] ) ) {
			$order_prefix         = ! empty( $get_all_setting[ $store_id ]['general_settings']['order_setting']['order_prefix'] ) ? $get_all_setting[ $store_id ]['general_settings']['order_setting']['order_prefix'] : '';
			$use_walmart_order_id = ! empty( $get_all_setting[ $store_id ]['general_settings']['order_setting']['use_walmart_order_id'] ) ? $get_all_setting[ $store_id ]['general_settings']['order_setting']['use_walmart_order_id'] : '';

		}

		?>

		<div class="ced-walmart-integ-wrapper">
			<input class="ced-faq-trigger" id="ced-walmart-order-import-setting" type="checkbox"/>
			<label class="ced-walmart-settng-title" for="ced-walmart-order-import-setting"> Order Import Settings  </label>
			<div class="ced-walmart-settng-content-wrap">
				<div class="ced-walmart-settng-content-holder">
					<div class="ced-form-accordian-wrap">
						<div class="wc-progress-form-content woocommerce-importer">
							<header>

								<div class="ced_walmart_child_element">
									<table class="form-table ced-settings">
										<tbody class="ced-settings-body-orderImport">

											<tr class="form-field">
												<th class="titledesc">
													<label class="" for="">Walmart Order Prefix
														<?php echo wc_help_tip( __( ' Attach a prefix/string in Walmart order id while creation of Walmart orders in WooCommerce ', 'walmart-woocommerce-integration' ) ); ?>
													</label>
												</th>
												
												<td>
													<input type="text"  value="<?php echo esc_attr__( $order_prefix, 'walmart-woocommerce-integration' ); ?>" name="ced_walmart_order_import_setting[order_prefix]" />
												</td>

											</tr>

											<tr class="form-field">
												
												<th class="titledesc">
													<label class="" for="walmart_order_prefix">Use Walmart Order
														<?php echo wc_help_tip( __( ' Use Walmart order number instead of WooCommerce order id.', 'walmart-woocommerce-integration' ) ); ?>
													</label>
												</th>
												
												<td>
													<input class="ced-checked-button" id="use_walmart_order_id" type="checkbox" name="ced_walmart_order_import_setting[use_walmart_order_id]"
													<?php echo ( 'on' == $use_walmart_order_id ) ? 'checked=checked' : ''; ?>
													>
													<label class="" for="use_walmart_order_id"></label>

												</td>
											</tr>


										</tbody>
									</table>
								</div>
							</header>
						</div>
					</div>
				</div>
			</div>	
		</div>
		<?php
	}



	public function ced_walmart_cron_setting() {

		$get_all_setting = get_option( 'ced_walmart_settings', '' );
		$store_id        = isset( $_GET['store_id'] ) ? sanitize_text_field( $_GET['store_id'] ) : '';

		if ( ! empty( $get_all_setting ) ) {
			$get_all_setting = json_decode( $get_all_setting, true );
		} else {
			$get_all_setting = array();
		}

		$auto_fetch_orders          = '';
		$auto_update_price          = '';
		$auto_update_inventory      = '';
		$auto_sync_existing_product = '';

		if ( isset( $get_all_setting[ $store_id ] ) && isset( $get_all_setting[ $store_id ]['general_settings']['cron_setting'] ) ) {

			$auto_fetch_orders          = ! empty( $get_all_setting[ $store_id ]['general_settings']['cron_setting']['ced_walmart_fetch_orders'] ) ? $get_all_setting[ $store_id ]['general_settings']['cron_setting']['ced_walmart_fetch_orders'] : '';
			$auto_update_price          = ! empty( $get_all_setting[ $store_id ]['general_settings']['cron_setting']['ced_auto_update_price'] ) ? $get_all_setting[ $store_id ]['general_settings']['cron_setting']['ced_auto_update_price'] : '';
			$auto_update_inventory      = ! empty( $get_all_setting[ $store_id ]['general_settings']['cron_setting']['ced_auto_update_inventory'] ) ? $get_all_setting[ $store_id ]['general_settings']['cron_setting']['ced_auto_update_inventory'] : '';
			$auto_sync_existing_product = ! empty( $get_all_setting[ $store_id ]['general_settings']['cron_setting']['ced_auto_sync_existing_product'] ) ? $get_all_setting[ $store_id ]['general_settings']['cron_setting']['ced_auto_sync_existing_product'] : '';

		}

		?>

		<div class="ced-walmart-integ-wrapper">
			<input class="ced-faq-trigger" id="ced-walmart-cron-setting" type="checkbox"/>
			<label class="ced-walmart-settng-title" for="ced-walmart-cron-setting"> Cron Settings  </label>
			<div class="ced-walmart-settng-content-wrap">
				<div class="ced-walmart-settng-content-holder">
					<div class="ced-form-accordian-wrap">
						<div class="wc-progress-form-content woocommerce-importer">
							<header>

								<div class="ced_walmart_child_element">
									<table class="form-table ced-settings">
										<tbody class="ced-settings-body-cronSetting">


											<tr class="form-field">
												
												<th class="titledesc">
													<label class="" for="fetch_walmart_order">Import Walmart Orders
														<?php echo wc_help_tip( __( 'Auto fetch Walmart orders and create in WooCommerce.', 'walmart-woocommerce-integration' ) ); ?>
													</label>
												</th>
												
												<td>

													<input class="ced-checked-button" id="ced_walmart_fetch_orders_cron" type="checkbox" name="ced_walmart_cron_setting[ced_walmart_fetch_orders]" <?php echo ( 'on' == $auto_fetch_orders ) ? 'checked=checked' : ''; ?>>
													<label class="" for="ced_walmart_fetch_orders_cron"></label>

												</td>
											</tr>


											<tr class="form-field">
												
												<th class="titledesc">
													<label class="" for="ced_auto_update_price">Auto Update Price
														<?php echo wc_help_tip( __( 'Auto update price from WooCommerce to Walmart', 'walmart-woocommerce-integration' ) ); ?>
													</label>
												</th>
												
												<td>

													<input class="ced-checked-button" id="ced_auto_update_price" type="checkbox" name="ced_walmart_cron_setting[ced_auto_update_price]" <?php echo ( 'on' == $auto_update_price ) ? 'checked=checked' : ''; ?>>
													<label class="" for="ced_auto_update_price"></label>

												</td>
											</tr>


											<tr class="form-field">
												
												<th class="titledesc">
													<label class="" for="ced_auto_update_inventory">Auto Update Inventory
														<?php echo wc_help_tip( __( 'Auto update inventory from WooCommerce to Walmart', 'walmart-woocommerce-integration' ) ); ?>
													</label>
												</th>
												
												<td>

													<input class="ced-checked-button" id="ced_auto_update_inventory" type="checkbox" name="ced_walmart_cron_setting[ced_auto_update_inventory]" <?php echo ( 'on' == $auto_update_inventory ) ? 'checked=checked' : ''; ?>>
													<label class="" for="ced_auto_update_inventory"></label>

												</td>
											</tr>


											<tr class="form-field">
												
												<th class="titledesc">
													<label class="" for="ced_auto_sync_existing_product">Sync Existing Products from Walmart
														<?php echo wc_help_tip( __( 'Sync Existing Product On the basis of identifier from Walmart', 'walmart-woocommerce-integration' ) ); ?>
													</label>
												</th>
												
												<td>

													<input class="ced-checked-button" id="ced_auto_sync_existing_product" type="checkbox" name="ced_walmart_cron_setting[ced_auto_sync_existing_product]" <?php echo ( 'on' == $auto_sync_existing_product ) ? 'checked=checked' : ''; ?>>
													<label class="" for="ced_auto_sync_existing_product"></label>

												</td>
											</tr>


										</tbody>
									</table>
								</div>
							</header>
						</div>
					</div>
				</div>
			</div>	
		</div>
		<?php
	}




	public function ced_walmart_submit_global_setting() {
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			if ( ! isset( $_POST['global_mapping'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['global_mapping'] ) ), 'ced_global_mapping' ) ) {
				return;
			}
			$this->formData = $_POST;
		}
		if ( ! empty( $this->formData ) ) {
			$store_id = isset( $_GET['store_id'] ) ? sanitize_text_field( $_GET['store_id'] ) : '';
			if ( isset( $this->formData['ced_walmart_save_global_fields'] ) ) {

				$global_fields        = isset( $this->formData['ced_walmart_global_setting_common'] ) ? $this->formData['ced_walmart_global_setting_common'] : array();
				$order_import_setting = isset( $this->formData['ced_walmart_order_import_setting'] ) ? $this->formData['ced_walmart_order_import_setting'] : array();
				$cron_setting         = isset( $this->formData['ced_walmart_cron_setting'] ) ? $this->formData['ced_walmart_cron_setting'] : array();

				$this->all_settings = get_option( 'ced_walmart_settings', '' );

				if ( ! empty( $this->all_settings ) ) {
					$this->all_settings = json_decode( $this->all_settings, true );
				} else {
					$this->all_settings = array();
				}

				$this->ced_walmart_save_global_setting( $global_fields, $store_id );
				$this->ced_walmart_save_order_import_setting( $order_import_setting, $store_id );
				$this->ced_walmart_save_cron_setting( $cron_setting, $store_id );
				update_option( 'ced_walmart_settings', json_encode( $this->all_settings ) );

			}
		}
	}


	public function ced_walmart_save_global_setting( $global_fields = array(), $store_id = '' ) {

		if ( isset( $global_fields ) && is_array( $global_fields ) ) {
			foreach ( $global_fields as $key => $value ) {
				if ( ! empty( $global_fields[ $key ]['metakey'] ) || ! empty( $global_fields[ $key ]['default'] ) ) {
					$this->all_settings[ $store_id ]['global_settings'][ 'global_' . $key ]['metakey'] = isset( $global_fields[ $key ]['metakey'] ) ? $global_fields[ $key ]['metakey'] : '';
					$this->all_settings[ $store_id ]['global_settings'][ 'global_' . $key ]['default'] = isset( $global_fields[ $key ]['default'] ) ? $global_fields[ $key ]['default'] : '';
				}
			}
		}
	}

	public function ced_walmart_save_order_import_setting( $order_import_setting = array(), $store_id = '' ) {

		if ( isset( $order_import_setting ) && is_array( $order_import_setting ) ) {

			$order_prefix         = isset( $order_import_setting['order_prefix'] ) ? sanitize_text_field( $order_import_setting['order_prefix'] ) : '';
			$use_walmart_order_id = isset( $order_import_setting['use_walmart_order_id'] ) ? $order_import_setting['use_walmart_order_id'] : '';

			$this->all_settings[ $store_id ]['general_settings']['order_setting']['order_prefix']         = $order_prefix;
			$this->all_settings[ $store_id ]['general_settings']['order_setting']['use_walmart_order_id'] = $use_walmart_order_id;

		}
	}

	public function ced_walmart_save_cron_setting( $cron_setting = array(), $store_id = '' ) {

		if ( isset( $cron_setting ) && is_array( $cron_setting ) ) {

			$auto_fetch_orders          = isset( $cron_setting['ced_walmart_fetch_orders'] ) ? $cron_setting['ced_walmart_fetch_orders'] : '';
			$auto_update_price          = isset( $cron_setting['ced_auto_update_price'] ) ? $cron_setting['ced_auto_update_price'] : '';
			$auto_update_inventory      = isset( $cron_setting['ced_auto_update_inventory'] ) ? $cron_setting['ced_auto_update_inventory'] : '';
			$auto_sync_existing_product = isset( $cron_setting['ced_auto_sync_existing_product'] ) ? $cron_setting['ced_auto_sync_existing_product'] : '';

			if ( 'on' == $auto_fetch_orders ) {
				wp_clear_scheduled_hook( 'ced_walmart_auto_fetch_orders_' . $store_id );
				$this->all_settings[ $store_id ]['general_settings']['cron_setting']['ced_walmart_fetch_orders'] = $auto_fetch_orders;
				wp_schedule_event( time(), 'ced_walmart_15min', 'ced_walmart_auto_fetch_orders_' . $store_id );
			} else {
				wp_clear_scheduled_hook( 'ced_walmart_auto_fetch_orders_' . $store_id );
				$this->all_settings[ $store_id ]['general_settings']['cron_setting']['ced_walmart_fetch_orders'] = $auto_fetch_orders;
			}

			if ( 'on' == $auto_update_price ) {
				wp_clear_scheduled_hook( 'ced_walmart_auto_update_price_' . $store_id );
				$this->all_settings[ $store_id ]['general_settings']['cron_setting']['ced_auto_update_price'] = $auto_update_price;
				wp_schedule_event( time(), 'ced_walmart_30min', 'ced_walmart_auto_update_price_' . $store_id );
			} else {
				wp_clear_scheduled_hook( 'ced_walmart_auto_update_price_' . $store_id );
				$this->all_settings[ $store_id ]['general_settings']['cron_setting']['ced_auto_update_price'] = $auto_update_price;

			}

			if ( 'on' == $auto_update_inventory ) {
				wp_clear_scheduled_hook( 'ced_walmart_auto_update_inventory_' . $store_id );
				$this->all_settings[ $store_id ]['general_settings']['cron_setting']['ced_auto_update_inventory'] = $auto_update_inventory;
				wp_schedule_event( time(), 'ced_walmart_30min', 'ced_walmart_auto_update_inventory_' . $store_id );
			} else {
				wp_clear_scheduled_hook( 'ced_walmart_auto_update_inventory_' . $store_id );
				$this->all_settings[ $store_id ]['general_settings']['cron_setting']['ced_auto_update_inventory'] = $auto_update_inventory;

			}

			if ( 'on' == $auto_sync_existing_product ) {
				wp_clear_scheduled_hook( 'ced_walmart_auto_sync_existing_products_' . $store_id );
				$this->all_settings[ $store_id ]['general_settings']['cron_setting']['ced_auto_sync_existing_product'] = $auto_sync_existing_product;
				wp_schedule_event( time(), 'ced_walmart_5min', 'ced_walmart_auto_sync_existing_products_' . $store_id );
			} else {
				wp_clear_scheduled_hook( 'ced_walmart_auto_sync_existing_products_' . $store_id );
				$this->all_settings[ $store_id ]['general_settings']['cron_setting']['ced_auto_sync_existing_product'] = $auto_sync_existing_product;

			}

			// update_option('ced_walmart_settings',json_encode($all_settings));

		}
	}
}


$obj = new Ced_Walmart_Settings();
$obj->ced_walmart_settings_render();
