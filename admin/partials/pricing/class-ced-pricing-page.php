<?php
if ( ! class_exists( 'Ced_Pricing_Plans' ) ) {

	class Ced_Pricing_Plans {

		public function __construct() {
			// $subscription_data = get_option('ced_mcfw_subscription_details_sandbox');
			// echo '<pre>';
			// print_r($subscription_data);
			// die('fdd');
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			add_action( 'wp_ajax_ced_woo_pricing_plan_selection', array( $this, 'ced_woo_pricing_plan_selection' ) );
			add_action( 'wp_ajax_ced_woo_validate_coupon', array( $this, 'ced_woo_validate_coupon' ) );
			add_action( 'wp_ajax_ced_woo_check_marketplaces', array( $this, 'ced_woo_check_marketplaces' ) );
			add_action( 'wp_ajax_ced_woo_pricing_plan_cancellation', array( $this, 'ced_woo_pricing_plan_cancellation' ) );
			add_action( 'admin_init', array( $this, 'ced_mcfw_save_subscription_details' ) );
		}


		public function ced_woo_validate_coupon() {
			$check_ajax = check_ajax_referer( 'ced-mcfw-pricing-ajax-seurity-string', 'ajax_nonce' );
			if ( $check_ajax ) {
				$params                  = array();
				$pricing_subscribed_data = get_option( 'ced_unified_contract_details', array() );
				if ( ! empty( $pricing_subscribed_data['unified-bundle']['plan_status'] ) ) {
					$pricing_status = $pricing_subscribed_data['unified-bundle']['plan_status'];
				} else {
					$pricing_status = '';
				}
				$selected_market             = get_option( 'ced_selected_marketplaces', array( 'Etsy', 'Walmart', 'Ebay', 'Amazon' ) );
				$params['plan_type']         = ! empty( $_POST['plan_type'] ) ? sanitize_text_field( wp_unslash( $_POST['plan_type'] ) ) : 'advanced';
				$params['plan_cost']         = ! empty( $_POST['plan_cost'] ) ? sanitize_text_field( wp_unslash( $_POST['plan_cost'] ) ) : '';
				$params['coupon_code']       = ! empty( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) : '';
				$params['marketplace_count'] = ! empty( $_POST['count'] ) ? sanitize_text_field( wp_unslash( $_POST['count'] ) ) : count( $selected_market );
				if ( 'canceled' !== $pricing_status ) {
					$params['contract_id'] = ! empty( $_POST['contract_id'] ) ? sanitize_text_field( wp_unslash( $_POST['contract_id'] ) ) : '';
				}
				$params['plan_period']          = ! empty( $_POST['plan_period'] ) ? sanitize_text_field( wp_unslash( $_POST['plan_period'] ) ) : '';
				$params['selected_marketplace'] = base64_encode( implode( ',', array_values( $selected_market ) ) );
				$params['channel']              = 'unified-bundle';
				$params['mode']                 = ! empty( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : '';
				$params['check_validity']       = true;
				$params['redirect_url']         = home_url();
				$build_query                    = http_build_query( $params );
				// $url = 'https://api.cedcommerce.com/pricing/checkout?'.$build_query;
				$url        = 'https://api.cedcommerce.com/woobilling/live/ced-process-payment.php?' . $build_query;
				$connection = curl_init();

				curl_setopt( $connection, CURLOPT_URL, $url );
				curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
				curl_setopt( $connection, CURLOPT_POST, 0 );
				curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, 0 );
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
				$response = curl_exec( $connection );
				$response = json_decode( $response, 1 );
				curl_close( $connection );
				echo json_encode( $response );
				wp_die();
			}
		}

		public function get_plan_options() {

			$curl = curl_init();

			curl_setopt_array(
				$curl,
				array(
					CURLOPT_URL            => 'https://api.cedcommerce.com/woobilling/live/ced_pricing_plan_options.json',
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING       => '',
					CURLOPT_MAXREDIRS      => 10,
					CURLOPT_TIMEOUT        => 0,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST  => 'GET',
				)
			);

			$response = curl_exec( $curl );
			curl_close( $curl );
			return $response;
		}

		public function ced_mcfw_save_subscription_details() {
			if ( isset( $_GET['page'] ) && 'sales_channel' == $_GET['page'] && isset( $_GET['success'] ) && 'yes' == $_GET['success'] ) {
				$mode = ! empty( $_GET['mode'] ) ? sanitize_text_field( wp_unslash( $_GET['mode'] ) ) : '';
				update_option( 'ced_mcfw_subscription_details' . $mode, $_GET );

				wp_redirect( admin_url( 'admin.php?page=sales_channel&channel=pricing&mode=' . $mode ) );
				exit;
			}
		}

		public function enqueue_scripts() {

			wp_enqueue_script( 'ced_mcfw_pricing', plugin_dir_url( __FILE__ ) . '/js/ced-mcfw-pricing.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-tiptip' ), '1.0.0', false );

			$ajax_nonce = wp_create_nonce( 'ced-mcfw-pricing-ajax-seurity-string' );
			$mode       = ! empty( $_GET['mode'] ) ? sanitize_text_field( wp_unslash( $_GET['mode'] ) ) : 'is_dev';
			wp_localize_script(
				'ced_mcfw_pricing',
				'ced_mcfw_pricing_obj',
				array(
					'ajax_url'   => admin_url( 'admin-ajax.php' ),
					'ajax_nonce' => $ajax_nonce,
					'mode'       => $mode,
				)
			);
		}

		public function enqueue_styles() {
			wp_enqueue_style( 'ced_mcfw_pricing', plugin_dir_url( __FILE__ ) . '/css/ced-mcfw-pricing.css', array(), '1.0.0', 'all' );
		}

		public function ced_woo_pricing_plan_cancellation() {
			$check_ajax = check_ajax_referer( 'ced-mcfw-pricing-ajax-seurity-string', 'ajax_nonce' );
			if ( $check_ajax ) {
				$params                 = array();
				$params['contract_id']  = ! empty( $_POST['contract_id'] ) ? sanitize_text_field( wp_unslash( $_POST['contract_id'] ) ) : '';
				$params['channel']      = 'unified-bundle';
				$params['is_cancel']    = 'yes';
				$params['redirect_url'] = home_url();
				$params['mode']         = ! empty( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : '';
				$build_query            = http_build_query( $params );
				// $url = 'https://api.cedcommerce.com/pricing/checkout?'.$build_query;
				$url        = 'https://api.cedcommerce.com/woobilling/live/ced-process-payment.php?' . $build_query;
				$connection = curl_init();
				curl_setopt( $connection, CURLOPT_URL, $url );
				curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
				curl_setopt( $connection, CURLOPT_POST, 0 );
				curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, 0 );
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
				$response = curl_exec( $connection );
				curl_close( $connection );
				$response_data = json_decode( $response, true );
				// var_dump($response);
				// die('kk');
				$data = get_option( 'ced_unified_contract_details', array() );
				if ( ! empty( $response_data ) && '200' == $response_data['status'] ) {
					delete_option( 'ced_unified_contract_details' );

				} elseif ( 400 == $response_data['status'] && 'Contract is already canceled.' == $response_data['message'] ) {
					delete_option( 'ced_unified_contract_details' );
				}
				// update_option( 'ced_unified_contract_details', $data );
				print_r( $response );
				wp_die();
			}
		}


		public function ced_woo_pricing_plan_selection() {
			$check_ajax = check_ajax_referer( 'ced-mcfw-pricing-ajax-seurity-string', 'ajax_nonce' );
			if ( $check_ajax ) {
				$params                  = array();
				$pricing_subscribed_data = get_option( 'ced_unified_contract_details', array() );
				if ( ! empty( $pricing_subscribed_data['unified-bundle']['plan_status'] ) ) {
					$pricing_status = $pricing_subscribed_data['unified-bundle']['plan_status'];
				} else {
					$pricing_status = '';
				}
				$selected_market             = get_option( 'ced_selected_marketplaces', array( 'Etsy', 'Walmart', 'Ebay', 'Amazon' ) );
				$params['plan_type']         = ! empty( $_POST['plan_type'] ) ? sanitize_text_field( wp_unslash( $_POST['plan_type'] ) ) : '';
				$params['plan_cost']         = ! empty( $_POST['plan_cost'] ) ? sanitize_text_field( wp_unslash( $_POST['plan_cost'] ) ) : '';
				$params['marketplace_count'] = ! empty( $_POST['count'] ) ? sanitize_text_field( wp_unslash( $_POST['count'] ) ) : count( $selected_market );
				if ( 'canceled' !== $pricing_status ) {
					$params['contract_id'] = ! empty( $_POST['contract_id'] ) ? sanitize_text_field( wp_unslash( $_POST['contract_id'] ) ) : '';
				}
				$sanitized_array                = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
				$selected_market                = ! empty( $sanitized_array['selected_marketplace'] ) ? $sanitized_array['selected_marketplace'] : '';
				$params['plan_period']          = ! empty( $_POST['plan_period'] ) ? sanitize_text_field( wp_unslash( $_POST['plan_period'] ) ) : '';
				$params['selected_marketplace'] = base64_encode( implode( ',', array_values( $selected_market ) ) );
				$params['channel']              = 'unified-bundle';
				$params['mode']                 = ! empty( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : '';
				$params['redirect_url']         = home_url();
				$params['coupon_code']          = ! empty( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) : '';
				$build_query                    = http_build_query( $params );

				$url        = 'https://api.cedcommerce.com/woobilling/live/ced-process-payment.php?' . $build_query;
				$connection = curl_init();
				curl_setopt( $connection, CURLOPT_URL, $url );
				curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
				curl_setopt( $connection, CURLOPT_POST, 0 );
				curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, 0 );
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
				$response = curl_exec( $connection );

				$response = json_decode( $response, 1 );

				curl_close( $connection );
				echo json_encode( $response );
				wp_die();
			}
		}

		public function ced_woo_check_marketplaces() {

			$check_ajax = check_ajax_referer( 'ced-mcfw-pricing-ajax-seurity-string', 'ajax_nonce' );

			if ( ! $check_ajax ) {
				return;
			}
			$checkcount       = isset( $_POST['checkcount'] ) ? sanitize_text_field( $_POST['checkcount'] ) : false;
			$plan_type        = isset( $_POST['plan_type'] ) ? sanitize_text_field( $_POST['plan_type'] ) : false;
			$marketplace_name = isset( $_POST['marketplace_name'] ) ? sanitize_text_field( $_POST['marketplace_name'] ) : false;
			$selected_market  = get_option( 'ced_selected_marketplaces', array( 'Etsy', 'Walmart', 'Ebay', 'Amazon' ) );
			// if ( in_array( $marketplace_name, $selected_market ) ) {
			// $key = array_search( $marketplace_name, $selected_market );
			// if ( ( $key ) !== false ) {
			// unset( $selected_market[ $key ] );
			// }
			// update_option( 'ced_selected_marketplaces', $selected_market );
			// } else {
			// $selected_market[ $marketplace_name ] = $marketplace_name;
			// update_option( 'ced_selected_marketplaces', $selected_market );
			// }

			$product_data = 'unified-bundle';
			$plan_data    = $this->get_plan_options();
			$prod_data    = array();
			$contract_id  = '';
			if ( ! empty( $plan_data ) ) {
				$plan_data = json_decode( $plan_data, true );

				$prod_data = $plan_data[ $product_data ][ $plan_type ];

			}

			$price_total_basic   = $prod_data['basic']['pricing'][ $checkcount ]['plan_price'];
			$price_total_advance = $prod_data['advanced']['pricing'][ $checkcount ]['plan_price'];
			$final_basic_price   = $prod_data['basic']['pricing'][ $checkcount ]['price_total'];
			$final_advance_price = $prod_data['advanced']['pricing'][ $checkcount ]['price_total'];
			echo json_encode(
				array(
					'basic_price'         => $price_total_basic,
					'advance_price'       => $price_total_advance,
					'final_basic_price'   => $final_basic_price,
					'final_advance_price' => $final_advance_price,
				)
			);
			wp_die();
		}


		public function new_ced_existing_plan_display( $subscription_data ) {

			$data               = isset( $subscription_data['data'] ) ? json_decode( $subscription_data['data'], true ) : '';
			$transactions       = isset( $data['transactions'][0] ) ? end( $data['transactions'] ) : '';
			$billing_intent_id  = isset( $transactions['billing_intent_id'] ) ? $transactions['billing_intent_id'] : '';
			$billing_intents    = isset( $data['billing_intents'][0] ) ? $data['billing_intents'] : array();
			$plan_status        = isset( $subscription_data['status'] ) ? $subscription_data['status'] : '';
			$next_payment_date  = isset( $data['next_payment_date'] ) ? $data['next_payment_date'] : '';
			$filter_arr         = array_filter(
				$billing_intents,
				function ( $arr ) use ( $billing_intent_id ) {
					return ( $arr['id'] == $billing_intent_id );
				}
			);
			$latest_transaction = array_values( $filter_arr );

			$payload        = isset( $latest_transaction[0]['payload'] ) ? $latest_transaction[0]['payload'] : '';
			$plan_name      = isset( $payload['name'] ) ? $payload['name'] : '';
			$plan_price     = isset( $payload['price'] ) ? $payload['price'] : '';
			$billing_period = isset( $payload['billing_period'] ) ? $payload['billing_period'] : '';
			$return_url     = isset( $payload['return_url'] ) ? $payload['return_url'] : '';
			$url_components = parse_url( $return_url );
			parse_str( $url_components['query'], $params );
			$selected_marketplaces = isset( $params['selected_markeplaces'] ) ? base64_decode( $params['selected_markeplaces'] ) : '';
			$status_class          = 'ced-mcbc-active';
			$update                = 'Re-Subscribe';
			if ( 'active' == $plan_status ) {
				$update = 'Update';
			}
			if ( 'paused' == $plan_status ) {
				$status_class = 'paused';
			} elseif ( 'canceled' == $plan_status ) {
				$status_class = 'canceled';
			}
			?>

			<div class="ced-pricing-plan-wrapper">
					<div class="ced-pricing-plan-wrap">
						<div class="ced-pricing-plan-container">
							<div class="ced-pricing-plan-common-wrapper">
								<h2>Pricing Plan Details</h2>
							</div>
							<div class="ced-pricing-plan-details-container">
								<div class="ced-pricing-plan-details-wrap">
									<table>
										<tbody>
											<tr>
												<td>Plan Status</td>
												<td>:</td>
												<td><span class="<?php print_r( $status_class ); ?>"><?php print_r( ucfirst( $plan_status ) ); ?></span></td>
											</tr>
											<tr>
												<td>Plan Name</td>
												<td>:</td>
												<td><?php print_r( $plan_name . '/' . $billing_period ); ?></td>
											</tr>
											<tr>
												<td>Plan Price</td>
												<td>:</td>
												<td><?php print_r( $plan_price ); ?></td>
											</tr>
											<tr>
												<td>Next Payment Date</td>
												<td>:</td>
												<td><?php print_r( $next_payment_date ); ?></td>
											</tr>
											<tr>
												<td>Active Marketplace</td>
												<td>:</td>
												<td><?php print_r( $selected_marketplaces ); ?></td>
											</tr>
										</tbody>
									</table>
								</div>
								<div class="ced-pricing-plan-action-container">
									<div class="ced-pricing-action-buttons">
										<button class="ced-update ced-update-current-plan"><?php print_r( $update ); ?></button>
										<?php if ( 'active' == $plan_status ) : ?>
										<button class="ced-cancel ced-cancel-current-plan">Cancel</button>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php
		}
		public function new_ced_pricing_plan_display( $display ) {
			$mode                 = ! empty( $_GET['mode'] ) ? sanitize_text_field( wp_unslash( $_GET['mode'] ) ) : '';
			$subscription_details = get_option( 'ced_mcfw_subscription_details' . $mode, array() );

			$contract_id = isset( $subscription_details['contract_id'] ) ? $subscription_details['contract_id'] : 0;
			$currentPlan = '';

			// var_dump($contract_id);
			// $currentPlan = $this->getCurrentPlanById();
			// echo '<pre>';
			// print_r($currentPlan);
			// die();
			$plan_type    = ! empty( $_GET['plan_type'] ) ? sanitize_text_field( wp_unslash( $_GET['plan_type'] ) ) : 'monthly';
			$is_update    = ! empty( $_GET['is_update'] ) ? sanitize_text_field( wp_unslash( $_GET['is_update'] ) ) : 'no';
			$product_plan = ! empty( $_GET['product_plan'] ) ? sanitize_text_field( wp_unslash( $_GET['product_plan'] ) ) : '';

			// $selected_market = get_option( 'ced_selected_marketplaces', array( 'Etsy', 'Walmart', 'Ebay', 'Amazon' ) );
			$selected_market = array( 'Etsy', 'Walmart', 'Ebay', 'Amazon' );
			$prod_data       = $this->ced_get_pricing_plan();

			?>
		<div class="ced-pricing-page-container <?php print_r( $display ); ?>" id="ced-main-pricing">
		<div class="ced-page-plan-content">
			<div class="ced-pricing-plan-common-header">
			<?php
				$trial_description = $this->get_trial_description();
			?>
				<div class="ced-pricing-plan-header-content">
					<h2>Choose your right plan!</h2>
					<p><?php print_r( $trial_description ); ?></p>
				</div>
			</div>
			<div class="plan-selector-wrapper">
				<div class="ced-pricing-container-common">
					<div class="ced-sricth-common-wrap">
						<div class="ced-switch-wrapper">
							<input id="monthly" type="radio" name="switch" value="monthly" 
								<?php
								if ( 'monthly' == $plan_type ) {
									echo 'checked'; }
								?>
									>
									<input id="yearly" type="radio" name="switch" value="yearly" 
									<?php
									if ( 'yearly' == $plan_type ) {
										echo 'checked'; }
									?>
								/>

							<label for="monthly">Monthly</label>
							<label for="yearly">Yearly</label>
							<span class="highlighter"></span>
						</div>
					</div>
				</div>
			</div>
			<div class="ced-pricing-card-common-wrapper">
				<div class="ced-pricing-card-common">
					<div class="ced-pricing-select-marketplace">
					<h3>Select Marketplace</h3>
					<?php
					$marketplaces = isset( $prod_data['basic']['marketplaces'] ) ? $prod_data['basic']['marketplaces'] : array();

					echo '<div class=ced-select-marketplace-common>';
						echo '<ul>';
					foreach ( $marketplaces as $market => $names ) {
							$checked = '';

						if ( in_array( $market, $selected_market ) ) {
								$checked   = 'checked';
								$sel_count = count( $selected_market );
						}if ( empty( $selected_market ) ) {
									$sel_count = 4;
									$checked   = 'checked';
						}
									echo '<li><input type="checkbox" name="selected-marketplaces" id="amazon" value=' . esc_attr( $market ) . ' ' . esc_attr( $checked ) . ' class="select-marketplace"><label for=' . esc_attr( $market ) . '>' . esc_attr( $market ) . '</label></li>';
					}
						echo '</ul>';
					echo '</div>';
					?>
					</div>
						<?php
						foreach ( $prod_data as $key => $plan ) {
							echo '<a href="#" class="btn btn-primary text-uppercase woo_ced_plan_selection_button" data-plan_type="' . esc_attr( $plan_type ) . '" id="ced-cost-' . esc_attr( $key ) . '" data-count="' . esc_attr( count( $selected_market ) ) . '"  data-plan_cost-' . esc_attr( $key ) . '=' . esc_attr( $plan['price_total'] ) . ' data-final_cost-' . esc_attr( $key ) . '=' . esc_attr( $plan['price_total'] ) . ' data-plan_name="' . esc_attr( $plan['plan_name'] ) . '" 
											data-contract_id="';
							if ( isset( $plan_data['status'] ) && 'canceled' !== $plan_data['status'] ) {
								echo esc_attr( $contract_id ); }

									echo '"><div class="ced-pricing-marketplace-card-container">
								<div class="ced-pricing-basic-card">
								<div class="ced-card-basic-card-common">
								<label>
									<h3>' . esc_attr( $plan['plan_name'] ) . '</h3>';
							$price_total_basic = $prod_data[ $key ]['pricing'][ $sel_count ]['plan_price'];
							$plan_price        = isset( $price_total_basic ) ? $price_total_basic : $plan['plan_price'];
							$desc              = explode( ',', $plan['plan_description'] );
							echo '<h2><span class="ced-price-value" id="ced-price-' . esc_attr( $key ) . '">$' . esc_attr( $plan_price ) . '</span>/month</h2>';
							if ( 'yearly' == $plan_type ) {
								echo '<h4>Billed Anually at $<span id="ced_billed_annualy-' . esc_attr( $key ) . '">' . esc_attr( $plan['price_total'] ) . '</span></h4>';
							}
							echo '<div class="ced-card-property-common">
									<ul>';
							if ( is_array( $desc ) && ! empty( $desc ) ) {
								foreach ( $desc as $k => $v ) {
											echo '<li>' . esc_attr( $v ) . '</li>';
								}
							}
								echo '</ul>
								</div>
								<div class="ced-pricing-button-common">
									
								</div>
							</label>
							</div>
							</div>
						</div></a>';
						}
						?>
				</div>
			</div>
			
			</div>
		</div>
	</div>
	<div class="ced-bottom-cart-container">
				<div class="ced-bottom-content-card">
					<div class="ced-cart-product-name">
						<h3><img src="<?php print_r( CED_MCFW_URL . 'admin\partials\pricing\mcbc.png' ); ?>">Multichannel by CedCommerce <span id="ced_show_plan_name"></span></h3>
					</div>
					<div class="ced-product-value-wrapper">
						<div class="ced-product-value-button">
							<h3><del><span id="ced_previous_price"></span></del><span id="ced_checkout_total">$8.25</span><span id="ced_total_plan_name"></span><a href="#"><button class="components-button is-primary ced_final_checkout" data-planName="basic" data-planCost="" data-count=<?php echo count( $selected_market ); ?>>Buy Now</button></a></h3>
							
						</div>
						<div class="ced-product-coupon-wrapper ced_add_coupon_link_div">
							<p id="ced_add_coupon">Do you have a coupon ?</p>
							<span id="ced_coupon_error"></span>
						</div>
						<div class="ced_add_coupon_form_div">
							<p><input type="text" size="20" name="coupon_code" placeholder="Enter Coupon code"> <button class="components-button is-secondary validate_coupon" data-planName="basic" data-planCost="" data-count=<?php echo count( $selected_market ); ?>>Apply Coupon</button></p>
						</div>
						<div class="ced_remove_coupon_div">
							<p><a class="ced_remove_coupon" data-planCost="" data-planType=""><span class="dashicons dashicons-no-alt"></span><span class="coupon_message"></span></a></p>
						</div>
						</div>
					</div>
				</div>
			<?php
		}
		public function ced_get_pricing_plan() {

			$plan_type    = ! empty( $_GET['plan_type'] ) ? sanitize_text_field( wp_unslash( $_GET['plan_type'] ) ) : 'monthly';
			$product_data = 'unified-bundle';
			$plan_data    = $this->get_plan_options();
			$prod_data    = array();
			$contract_id  = '';
			$plan_data    = json_decode( $plan_data, true );
			if ( ! empty( $plan_data ) ) {
				$prod_data = $plan_data[ $product_data ][ $plan_type ];

			}
			return $prod_data;
		}

		public function get_trial_description() {
			$plan_type    = ! empty( $_GET['plan_type'] ) ? sanitize_text_field( wp_unslash( $_GET['plan_type'] ) ) : 'monthly';
			$product_data = 'unified-bundle';
			$plan_data    = $this->get_plan_options();
			$plan_data    = json_decode( $plan_data, true );
			$prod_data    = array();
			$contract_id  = '';

			$description = isset( $plan_data[ $product_data ]['description'] ) ? $plan_data[ $product_data ]['description'] : '';
			return $description;
		}

		public function getCurrentPlanById( $id = '' ) {
			// if ( empty( $id ) ) {
			// return(
			// array(
			// 'status'  => false,
			// 'message' => 'Failed to fetch your current plans details. Please try again later or contact support.',
			// )
			// );

			// }

			$data = array(
				'action'  => 'get_subscription_by_domain',
				'domain'  => home_url(),
				'channel' => 'unified-bundle',
			);
			$curl = curl_init();

			$url = 'https://api.cedcommerce.com/woobilling/live/ced_api_request.php';
			$url = $url . '?' . http_build_query( $data );
			curl_setopt_array(
				$curl,
				array(
					CURLOPT_URL            => $url,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING       => '',
					CURLOPT_MAXREDIRS      => 10,
					CURLOPT_TIMEOUT        => 0,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,

					CURLOPT_POSTFIELDS     => $id,
				)
			);

			$currentPlanResponse = curl_exec( $curl );
			curl_close( $curl );

			if ( is_wp_error( $currentPlanResponse ) ) {
				return(
					array(
						'status'  => false,
						'message' => 'Failed to fetch your current plans details. Please try again later or contact support.',
					)
				);

			} else {
				$response = json_decode( $currentPlanResponse, true );
				return $response;

			}
		}
	}
}
