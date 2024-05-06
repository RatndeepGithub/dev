<?php
$is_error    = filter_input( INPUT_GET, 'error', FILTER_SANITIZE_SPECIAL_CHARS );
$user_id     = filter_input( INPUT_GET, 'user_id', FILTER_SANITIZE_SPECIAL_CHARS );
$keys        = array_keys( $this->steps );
$keys_length = count( $keys );
$step_index  = array_search( $this->step, $keys, true );
?>



<style type="text/css">
	.ced-notification-error p {
		font-size: 13px !important;
		margin: 0.5em 0 !important;
		color: #1E1E1E !important;
		font-weight: 400;
	}

	.ced-notification-error {
		background: #F4A2A2 !important;
	}

	.ced-notification-notice p {
		margin: 0.5em 0 !important;
		font-size: 13px !important;
	}

	.ced-notification-notice {
		background: #EFF9F1 !important;
	}

	.ced-account-detail-wrapper {
		border: 1px solid #4AB866;
		border-radius: 2px;
		padding: 12px 8px 12px 8px;
	}

	.ced-account-details-holder {
		display: flex;
	}

	.ced-account-details-holder p:first-child {
		font-weight: 500;
		padding-right: 15px;
	}

	.ced-account-details-holder p {
		color: #1E1E1E !important;
		margin: 0 !important;
	}

	.ced-link a {
		text-decoration: none;
	}
</style>
<div class="woocommerce-progress-form-wrapper">
	
	<form class="wc-progress-form-content woocommerce-importer" name="ced_eBay_onboarding" enctype="multipart/form-data"
		action="" method="post">
		<header>
			<h2>Connect eBay</h2>
			<p>To get started, connect your eBay account by Selecting your eBay Account Region clicking the button. This
				is only a one time process and all the data is processed and stored on your website.</p>
		</header>
		<?php
		if ( null === $user_id ) {

			?>
			<section>
				<?php
				if ( null !== $is_error ) {
					?>
					<div id="message" class="error inline ced-notification-error">
						<p>
							An issue arose while connecting to your eBay account. Please consider attempting to reconnect. If
							issue persists, please contact support. <b>(Error Code:
								<?php echo esc_html( $is_error ); ?>)</p>
					</div>
					<?php
				}

				?>
				<table class="form-table woocommerce-importer-options">
					<tbody>


						<tr>
							<?php
							require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayConfig.php';
							$ebayConfig            = new Ced_Ebay_WooCommerce_Core\Ebayconfig();
							$ebayConfigInstance    = $ebayConfig->get_instance();
							$ebaySites             = $ebayConfigInstance->getEbaysites();
							$selectedSiteId        = '';
							$optionEbaysites['-1'] = 'Please Select';
							if ( is_array( $ebaySites ) && ! empty( $ebaySites ) ) {
								foreach ( $ebaySites as $sites ) {
									if ( '' != $selectedSiteId && $selectedSiteId == $sites['siteID'] ) {
										$selected = 'selected';
									} else {
										$selected = '';
									}
									$optionEbaysites[ $sites['siteID'] ] = $sites['name'];
								}
							}
							?>
							<th scope="row">
								<label for="ced_ebay_marketplace_region">
									<?php echo esc_html_e( 'eBay Site', 'ebay-woocommerce-integration' ); ?>
								</label>
							</th>
							<td id="ced_ebay_marketplace_region_select">
								<select type="text" name="ced_ebay_marketplace_region" id="ced_ebay_marketplace_region"
									class="ced_ebay_required_data" style="width:100%">
									<?php
									foreach ( $optionEbaysites as $key => $ebay_site ) {
										?>
										<option required value="<?php echo esc_attr( $key ); ?>"><?php esc_attr_e( $ebay_site, 'ebay-integration-for-woocommerce' ); ?></option>
										<?php
									}
									?>
								</select>
							</td>
						</tr>

					</tbody>
				</table>
			</section>
			<div class="wc-actions">
				<?php wp_nonce_field( 'ced_ebay_connect_action', 'ced_ebay_connect_button' ); ?>
				<input type="hidden" name="action" value="ced_ebay_connect_account" />
				<!-- <button type="submit" class="button button-primary button-next" value="Continue" id="ced_ebay_marketing_do_login" data-login-mode="sandbox" name="save_step">Connect
				</button> -->
				<button type="submit" style="float: right;" value="Connect" name="save_step"
					class="button-next components-button is-primary">Connect</button>

		</form>
			<?php

		} else {
			$user_id   = isset( $_GET['user_id'] ) ? wc_clean( $_GET['user_id'] ) : false;
			$shop_data = ced_ebay_get_shop_data( $user_id );
			if ( ! empty( $shop_data ) ) {
				$siteID = $shop_data['site_id'];
				$token  = $shop_data['access_token'];
			} else {
				$current_uri = home_url( remove_query_arg( array( 'user_id', 'site_id' ) ) );
				wp_safe_redirect(
					esc_url_raw(
						add_query_arg(
							array(
								'section' => $keys[ $step_index ],
								'error'   => 'invalid_user',
							),
							$current_uri
						)
					)
				);
				exit();
			}
			require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/cedMarketingRequest.php';
			$cedMarketingRequest = new \Ced_Marketing_API_Request( $siteID );
			$endpoint            = 'privilege';
			$responseAccountsApi = $cedMarketingRequest->sendHttpRequestForAccountAPI( $endpoint, $token, '' );
			$account_privileg    = json_decode( $responseAccountsApi, true );
			?>

		<form class="wc-progress-form-content woocommerce-importer" name="ced_eBay_onboarding" enctype="multipart/form-data"
			action="" method="post">


			<section>
				<div id="message" class="updated inline ced-notification-notice">
					<p><strong>🎉 Awesome, your eBay account <?php echo '<b>' . esc_attr( $user_id ) . '</b>'; ?> is now connected!</strong></p>
					
				</div>
				<p></p>
			</section>
			<div class="wc-actions">
				<?php wp_nonce_field( 'ced_ebay_connect_action', 'ced_ebay_connect_button' ); ?>
				<input type="hidden" name="action" value="ced_ebay_verify_account" />
				<button type="submit" style="float: right;" value="Verify and Continue" name="save_step"
					class="button-next components-button is-primary">Verify and Continue</button>
				</button>
		</form>
			<?php
		}
		?>
</div>
