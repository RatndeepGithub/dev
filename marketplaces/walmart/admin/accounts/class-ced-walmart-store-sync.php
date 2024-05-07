<?php




class Ced_Walmart_Store_Sync {

	public $formData = array();

	public function __construct() {
		$this->render_fields();
	}



	public function render_fields() {
		$count = isset( $_GET['count'] ) ? sanitize_text_field( $_GET['count'] ) : 0;
		if ( $count <= 0 ) {
			return;
		}
		?>
		
		<div class="woocommerce-progress-form-wrapper">
			<h2 style="text-align: left;">Walmart Integration: Onboarding</h2>
			<div class="ced-onboarding-notification">
				<p><?php esc_html_e( "We've discovered <b>" . $count . '</b> items available on Walmart. Establish a connection for syncing the products between Walmart and WooCommerce by selecting an identification source', 'walmart-woocommerce-integration' ); ?></p>
			</div>

			<?php $this->ced_walmart_submit_sync_mapping(); ?>
			<form method="post" action="">
				<?php wp_nonce_field( 'ced_sync_mapping', 'sync_mapping' ); ?>
				<div class="wc-progress-form-content woocommerce-importer">
					<header>
						<h2>Product Mapping</h2>
						<p>Filling the following attributes can improve your listings on Walmart.</p>
					</header>
					<header class="ced-label-wrap">
						<div class="form-field form-required term-name-wrap">
							<label for="tag-name">Walmart Identification</label>

							<select style="width: 100%;" name="ced_walmart_unique_id">
								<option value="sku">SKU</option>
								<option value="gtin">GTIN</option>
								<option value="ean">EAN</option>
								<option value="upc">UPC</option>
							</select>

						</div>
						<div class="form-field form-required term-name-wrap">
							<label for="tag-name">WooCommerce Identification</label>
							<select style="width: 100%;" name="ced_woo_unique_id">
								<option value="_sku">SKU</option>
								<option value="gtin">GTIN</option>
								<option value="ean">EAN</option>
								<option value="upc">UPC</option>
							</select>

						</div>
					</header>
					<div class="wc-actions">
						<button type="submit" name="ced_walmart_save_sync_mapping" class="components-button is-primary button-next" style="float:right;">Save And Continue</button>
					</form>
				</div>
			</div>
		</div>
		<?php
	}


	public function ced_walmart_submit_sync_mapping() {

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			if ( ! isset( $_POST['sync_mapping'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sync_mapping'] ) ), 'ced_sync_mapping' ) ) {
				return;
			}
			$this->formData = $_POST;
		}
		if ( ! empty( $this->formData ) ) {
			if ( isset( $this->formData['ced_walmart_save_sync_mapping'] ) ) {

				$store_id          = isset( $_GET['store_id'] ) ? sanitize_text_field( $_GET['store_id'] ) : '';
				$walmart_unique_id = $this->formData['ced_walmart_unique_id'];
				$woo_unique_id     = $this->formData['ced_woo_unique_id'];
				if ( '' == $store_id ) {
					?>
					<div class="notice notice-error is-dismissible">
						<p><?php esc_html_e( 'Operation Failed , Store Id not found ', 'walmart-woocommerce-integration' ); ?></p>
					</div>
					<?php
				} else {
					$account_list = ced_walmart_return_partner_detail_option();
					$account_list[ $store_id ]['sync_mapping']['walmart_unique_id'] = $walmart_unique_id;
					$account_list[ $store_id ]['sync_mapping']['woo_unique_id']     = $woo_unique_id;
					$account_list[ $store_id ]['sync_mapping_completed']            = true;
					$account_list[ $store_id ]['current_step']                      = $redirect_url;
					$redirect_url                              = add_query_arg(
						array(
							'action'   => 'setup-wizard',
							'step'     => 'global_setting',
							'store_id' => $store_id,
						)
					);
					$account_list[ $store_id ]['current_step'] = $redirect_url;
					update_option( 'ced_walmart_saved_account_list', json_encode( $account_list ) );
					wp_safe_redirect( esc_url_raw( $redirect_url ) );

				}
			}
		}
	}
}





$obj = new Ced_Walmart_Store_Sync();
