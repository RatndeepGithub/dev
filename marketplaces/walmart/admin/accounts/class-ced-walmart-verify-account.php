<?php


class Ced_Walmart_Verify_Account {

	public $formData = array();



	public function __construct() {
		$this->render_fields();
	}




	public function render_fields() {
		?>
		<div class="woocommerce-progress-form-wrapper">
			<h2 style="text-align: left;">Walmart Integration: Onboarding</h2>
			<div class="wc-progress-form-content">
				<header>
					<h2>Connect Marketplace</h2>
					<p><?php esc_attr_e( 'To get started, connect your Walmart account by verifing details .This is only a one time process and all the data is processed and stored on your website.', 'walmart-woocommerce-integration' ); ?></p>
					<div id="message" class="updated inline ced-notification-notice">
						<p><strong>🎉 <?php esc_html_e( 'Awesome, your Walmart account is now connected!', 'walmart-woocommerce-integration' ); ?></strong></p>
						<?php $this->ced_walmart_submit_account(); ?>
						<form method="post" action="">
							<?php wp_nonce_field( 'ced_onboarding_verify', 'onboarding_verify' ); ?>
							<?php print_r( $this->ced_walmart_return_partner_detail_html() ); ?>
						</div>
					</header>
					<div class="wc-actions">
						<button style="float: right;" type="submit" name="ced_walmart_save_account_verify" class="components-button is-primary button-next">Verify and continue</button>
					</div>
				</form>
			</div>
		</div>
	<?php	}


	public function ced_walmart_submit_account() {

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			if ( ! isset( $_POST['onboarding_verify'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['onboarding_verify'] ) ), 'ced_onboarding_verify' ) ) {
				return;
			}
			$this->formData = $_POST;
		}
		if ( ! empty( $this->formData ) ) {
			if ( isset( $this->formData['ced_walmart_save_account_verify'] ) ) {

				$count        = $this->ced_walmart_get_store_product_count();
				$store_id     = isset( $_GET['store_id'] ) ? sanitize_text_field( $_GET['store_id'] ) : '';
				$account_list = ced_walmart_return_partner_detail_option();
				$account_list[ $store_id ]['varification_completed'] = true;
				if ( $count > 0 ) {
					$redirect_url                              = add_query_arg(
						array(
							'action'   => 'onboarding',
							'step'     => 'product_found',
							'store_id' => $store_id,
							'count'    => $count,
						)
					);
					$account_list[ $store_id ]['current_step'] = $redirect_url;
					update_option( 'ced_walmart_saved_account_list', json_encode( $account_list ) );
					wp_safe_redirect( esc_url_raw( $redirect_url ) );
				} else {
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

	public function ced_walmart_return_partner_detail_html() {

		$partner_details = get_option( 'ced_walmart_us_partner_details', '' );

		if ( empty( $partner_details ) ) {
			$partner_details = array();
		} else {
			$partner_details = json_decode( $partner_details, true );
			$partne_id       = isset( $partner_details['partner']['partnerId'] ) ? $partner_details['partner']['partnerId'] : '';
			$partnerName     = isset( $partner_details['partner']['partnerName'] ) ? $partner_details['partner']['partnerName'] : '';
			$partnerStoreId  = isset( $partner_details['partner']['partnerStoreId'] ) ? $partner_details['partner']['partnerStoreId'] : '';
			if ( isset( $partner_details['configurations'][0] ) && 'ACCOUNT' == $partner_details['configurations'][0]['configurationName'] ) {
				$acc_status = $partner_details['configurations'][0]['configuration'] ['status'];
			}

			$html  = '';
			$html .= '<div class="ced-account-detail-wrapper">';
			$html .= '<div class="ced-account-details-holder">';
			$html .= '<p>Account details:</p>';
			$html .= '<p> Name : ' . $partnerName . ' <br> Store Id:' . $partnerStoreId . ' <br> Status : ' . $acc_status . ' </p> ';
			$html .= '</div>';
			$html .= '</div>';
			return $html;
		}
	}


	public function ced_walmart_get_store_product_count() {

		$ced_walmart_curl_file = CED_WALMART_DIRPATH . 'admin/walmart/lib/class-ced-walmart-curl-request.php';
		include_file( $ced_walmart_curl_file );
		$ced_walmart_curl_instance = Ced_Walmart_Curl_Request::get_instance();
		$action                    = 'items';
		$query_args                = array(
			'publishedStatus' => 'PUBLISHED',
		);
		$response                  = $ced_walmart_curl_instance->ced_walmart_get_request( $action, '', $query_args );
		$count                     = 0;
		if ( isset( $response['totalItems'] ) && ! empty( $response['totalItems'] ) ) {
			$count = $response['totalItems'];
		}
		return $count;
	}
}
$obj = new Ced_Walmart_Verify_Account();

?>
