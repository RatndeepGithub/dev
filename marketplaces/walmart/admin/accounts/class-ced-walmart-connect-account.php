<?php
if ( ! session_id() ) {
	session_start();
}


class Ced_Walmart_Connect_Account {


	public $formData = array();
	public $message  = '';



	public function render_fields() {

		$fields_html = '';

		if ( file_exists( CED_WALMART_DIRPATH . 'admin/setup-wizard/class-ced-walmart-setup-wizard-fields.php' ) ) {
			include_once CED_WALMART_DIRPATH . 'admin/setup-wizard/class-ced-walmart-setup-wizard-fields.php';
			$config_fields_obj = new Ced_Walmart_Setup_Wizard_Fields();
			$config_fields     = $config_fields_obj->ced_walmart_account_config_fields();
			if ( ! empty( $config_fields ) && is_array( $config_fields ) ) {
				foreach ( $config_fields as $key => $value ) {
					$fields_html .= '<div class="form-field form-required term-name-wrap">';
					if ( 'text' === $value['type'] ) {
						$fields_html .= '<label for="ced_walmart_onboarding_config' . $value['fields']['id'] . '"> ' . __( $value['fields']['label'], 'walmart-woocommerce-integration' ) . '</label>';
						$fields_html .= '<input type="text" name="' . $value['fields']['id'] . '" id="' . $value['fields']['id'] . '" value="" style="width:100%" required></td>';

					} elseif ( 'dropdown' === $value['type'] ) {

						$value_for_dropdown = ! empty( $value['fields']['options'] ) ? $value['fields']['options'] : array();
						// $fields_html       .= '<label for="ced_walmart_onboarding_config' . $value['fields']['id'] . '"> ' . __( $value['fields']['label'], 'walmart-woocommerce-integration' ) . '</label>';
						$fields_html .= '<select name="' . $value['fields']['id'] . '"  style="width:100%;display:none;">';

						foreach ( $value_for_dropdown as $key => $index ) {
							$fields_html .= '<option value="' . $index . '"> ' . __( $index, 'walmart-woocommerce-integration' ) . '</option>';
						}
						$fields_html .= '</select>';

					}
					$fields_html .= '</div>';
				}
			}
		}
		?>
		<div class="woocommerce-progress-form-wrapper">
			<h2 style="text-align: left;">Walmart Integration: Onboarding</h2>
			<form method="post" action="">
				<?php wp_nonce_field( 'ced_sync_mapping', 'sync_mapping' ); ?>
				<div class="wc-progress-form-content woocommerce-importer">
					<header>
						<h2>Connect Marketplace</h2>
						<p><?php esc_attr_e( 'To get started, connect your Walmart account by entering your client id and secret key .This is only a one time process and all the data is processed and stored on your website.', 'walmart-woocommerce-integration' ); ?></p>
					</header>
					<?php $this->ced_walmart_submit_account(); ?>
					<header class="ced-label-wrap">
						<?php wp_nonce_field( 'ced_onboarding_config', 'onboarding_config' ); ?>
						<?php print_r( $fields_html ); ?>
					</header>
					<div class="wc-actions">
						<button type="submit" name="ced_walmart_save_account_config" class="components-button is-primary button-next
						" style="float:right;">Connect Walmart</button>

					</div>
				</form>
			</div>
		</div>

		
		<?php
	}




	public function ced_walmart_submit_account() {

		// Check if the form is submitted
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' == $_SERVER['REQUEST_METHOD'] ) {

			if ( ! isset( $_POST['onboarding_config'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['onboarding_config'] ) ), 'ced_onboarding_config' ) ) {
				return;
			}

			$this->formData = $_POST;
		}
		if ( ! empty( $this->formData ) ) {

			if ( isset( $this->formData['ced_walmart_save_account_config'] ) ) {
				if ( ! isset( $this->formData['onboarding_config'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $this->formData['onboarding_config'] ) ), 'ced_onboarding_config' ) ) {
					return;
				}

				$client_id     = $this->formData['ced_walmart_client_id'];
				$client_secret = $this->formData['ced_walmart_client_secret'];
				$environment   = $this->formData['ced_walmart_environment'];
				$response      = $this->ced_walmart_validate_credentials( $client_id, $client_secret, $environment );
				if ( 400 == $response['status'] ) {
					?>
					<div id="message" class="error inline is-dismissible ced-notification-error">
						<p><?php esc_attr_e( $response['message'], 'walmart-woocommerce-integration' ); ?></p>
					</div>
					<?php
				}
			}
		}
	}


	public function ced_walmart_validate_credentials( $client_id = '', $client_secret = '', $environment = '' ) {
		$ced_walmart_process_request_file = CED_WALMART_DIRPATH . 'admin/walmart/lib/class-ced-walmart-process-request.php';
		include_file( $ced_walmart_process_request_file );
		$ced_walmart_process_instance = Ced_Walmart_Remote_Request::get_instance();

		/**
		 * Filter for walmart channel id
		 *
		 * @since  1.0.0
		 */
		$channel_id                        = apply_filters( 'ced_walmart_channel_id', '7b2c8dab-c79c-4cee-97fb-0ac399e17ade' );
		$ced_walmart_configuration_details = array(
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'channel_id'    => $channel_id,
			'environment'   => strtolower( $environment ),
			'wfs'           => '',
		);

		$action     = 'token';
		$parameters = array('grant_type' => 'client_credentials');
		$response   = $ced_walmart_process_instance->ced_walmart_process_request(
			$action,
			$parameters,
			array(
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
				'environment'   => strtolower( $environment ),
			),
			'POST'
		);

		$token      = isset( $response['result']['access_token'] ) ? $response['result']['access_token'] : '';
		$expries_in = isset( $response['result']['expires_in'] ) ? $response['result']['expires_in'] : '';

		if ( ! empty( $token ) ) {

			update_option( 'ced_walmart_token', $token );
			set_transient( 'ced_walmart_token_transient', $token, $expries_in );

			require_once CED_WALMART_DIRPATH . 'admin/class-walmart-woocommerce-integration-admin.php';
			$ced_walmart_admin_instance = new Walmart_Woocommerce_Integration_Admin( '', '' );
			$response                   = $ced_walmart_admin_instance->ced_walmart_update_partnet_detail(
				$token,
				array(
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'environment'   => strtolower( $environment ),
				)
			);

			$store_id                                    = $response['partner']['partnerStoreId'];
			$store_name                                  = $response['partner']['partnerDisplayName'];
			$account_list                                = ced_walmart_return_partner_detail_option();
			$account_list[ $store_id ]['config_details'] = $ced_walmart_configuration_details;
			$account_list[ $store_id ]['token']          = $token;
			$redirect_url                                = add_query_arg(
				array(
					'action'   => 'onboarding',
					'step'     => 'verify',
					'store_id' => $store_id,
				)
			);
			$account_list[ $store_id ]['current_step']   = $redirect_url;
			$account_list[ $store_id ]['store_name']     = $store_name;

			update_option( 'ced_walmart_active_store', $store_id );
			update_option( 'ced_walmart_saved_account_list', json_encode( $account_list ) );
			wp_safe_redirect( esc_url_raw( $redirect_url ) );

		} elseif ( isset( $response['errors'] ) ) {

			$message = isset( $response['errors']['error']['description'] ) ? $response['errors']['error']['description'] : '';
			return array(
				'status'  => 400,
				'message' => $message,
			);
		} elseif ( isset( $response['error']['error'][0] ) ) {

			$message = isset( $response['error']['error'][0]['description'] ) ? $response['error']['error'][0]['description'] : '';

			if ( empty( $message ) ) {
				$message = isset( $response['error']['error_description'] ) ? $response['error']['error_description'] : '';

			}
			return array(
				'status'  => 400,
				'message' => $message,
			);
		}
	}
}
