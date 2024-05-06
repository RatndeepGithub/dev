<?php

class Ced_Walmart_Setup_Wizard_Completed_Onboarding {

	public $formData = array();

	public function render_fields() {
		?>
		<div class="woocommerce-progress-form-wrapper">
			<?php
			print_r( ced_walmart_setup_wizard_bar() );
			$this->ced_walmart_complete_onboard();
			?>

			<div class="wc-progress-form-content woocommerce-importer">
				<form method="post" action="">
					<?php wp_nonce_field( 'ced_onboarding_completed', 'onboarding_completed' ); ?>
					<header style="text-align: center;">
						<img style="width: 15%;" src="<?php echo esc_url( CED_WALMART_URL . 'admin/images/success.jpg' ); ?>" alt="">
						<p><strong>Great job! Your onboarding process is complete.</strong></p>
					</header>
					<div class="wc-actions">
						<button style="float: right;" type="submit" name="ced_walmart_onboard_done" class="components-button is-primary button-next">Go to Overview</button>

					</div>

				</form>
			</div>

		</div>

		<?php
	}




	public function ced_walmart_complete_onboard() {
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			if ( ! isset( $_POST['onboarding_completed'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['onboarding_completed'] ) ), 'ced_onboarding_completed' ) ) {
				return;
			}
			$this->formData = $_POST;
		}
		if ( ! empty( $this->formData ) ) {
			$store_id = isset( $_GET['store_id'] ) ? sanitize_text_field( $_GET['store_id'] ) : '';
			if ( isset( $this->formData['ced_walmart_onboard_done'] ) ) {

				$global_fields = $this->formData['ced_walmart_global_setting_common'];

				$all_settings = get_option( 'ced_walmart_settings', '' );

				if ( ! empty( $all_settings ) ) {
					$all_settings = json_decode( $all_settings, true );
				} else {
					$all_settings = array();
				}

				foreach ( $global_fields as $key => $value ) {

					if ( ! empty( $global_fields[ $key ]['metakey'] ) || ! empty( $global_fields[ $key ]['default'] ) ) {
						$all_settings[ $store_id ]['global_settings'][ 'global_' . $key ]['metakey'] = isset( $global_fields[ $key ]['metakey'] ) ? $global_fields[ $key ]['metakey'] : '';
						$all_settings[ $store_id ]['global_settings'][ 'global_' . $key ]['default'] = isset( $global_fields[ $key ]['default'] ) ? $global_fields[ $key ]['default'] : '';
					}
				}
				update_option( 'ced_walmart_settings', json_encode( $all_settings ) );
				$this->ced_walmart_update_status( $store_id );
			}
		}
	}
	public function ced_walmart_update_status( $store_id ) {
		$account_list                           = ced_walmart_return_partner_detail_option();
		$account_list[ $store_id ]['completed'] = true;
		unset( $account_list[ $store_id ]['current_step'] );
		update_option( 'ced_walmart_saved_account_list', json_encode( $account_list ) );
		delete_option( 'ced_walmart_onboarding_completed_steps' );
		$redirect_url = admin_url( 'admin.php?page=sales_channel&channel=walmart' );
		wp_safe_redirect( esc_url_raw( $redirect_url ) );
	}
}
?>
