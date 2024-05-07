<?php
if ( ! class_exists( 'Ced_Pricing_Plans' ) ) {

	class Ced_Pricing_Plans {

		public function __construct() {

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			add_action( 'wp_ajax_ced_woo_pricing_plan_selection', array( $this, 'ced_woo_pricing_plan_selection' ) );
			add_action( 'wp_ajax_ced_woo_check_marketplaces', array( $this, 'ced_woo_check_marketplaces' ) );
			add_action( 'wp_ajax_ced_woo_pricing_plan_cancellation', array( $this, 'ced_woo_pricing_plan_cancellation' ) );

			add_action( 'admin_init', array( $this, 'ced_mcfw_save_subscription_details' ) );
		}

		public function get_plan_options() {

			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => 'https://api.cedcommerce.com/woobilling/live/ced_pricing_plan_options.json',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'GET',
			));

			$response = curl_exec($curl);

			curl_close($curl);
			return $response;
		}

		public function ced_mcfw_save_subscription_details() {
			if ( isset( $_GET['page'] ) && 'sales_channel' == $_GET['page'] && isset( $_GET['success'] ) && 'yes' == $_GET['success'] ) {
				update_option( 'ced_mcfw_subscription_details', $_GET );
				wp_redirect( admin_url( 'admin.php?page=sales_channel&channel=pricing' ) );
				exit;
			}
		}

		public function enqueue_scripts() {

			wp_enqueue_script( 'ced_mcfw_pricing', plugin_dir_url( __FILE__ ) . '/js/ced-mcfw-pricing.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-tiptip' ), '1.0.0', false );

			$ajax_nonce = wp_create_nonce( 'ced-mcfw-pricing-ajax-seurity-string' );

			wp_localize_script(
				'ced_mcfw_pricing',
				'ced_mcfw_pricing_obj',
				array(
					'ajax_url'   => admin_url( 'admin-ajax.php' ),
					'ajax_nonce' => $ajax_nonce,
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
				$build_query            = http_build_query( $params );
				$url                    = 'https://api.cedcommerce.com/woobilling/live/ced-process-payment.php?' . $build_query;
				$connection             = curl_init();
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
					$data['unified-bundle']['plan_status'] = 'canceled';

				} elseif ( 400 == $response_data['status'] && 'Contract is already canceled.' == $response_data['message'] ) {
					$data['unified-bundle']['plan_status'] = 'canceled';
				}
				update_option( 'ced_unified_contract_details', $data );
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
				$params['plan_period']          = ! empty( $_POST['plan_period'] ) ? sanitize_text_field( wp_unslash( $_POST['plan_period'] ) ) : '';
				$params['selected_marketplace'] = base64_encode( implode( ',', array_values( $selected_market ) ) );
				$params['channel']              = 'unified-bundle';
				$params['redirect_url']         = home_url();
				$build_query                    = http_build_query( $params );
				$url                            = 'https://api.cedcommerce.com/woobilling/live/ced-process-payment.php?' . $build_query;
				$connection                     = curl_init();
				curl_setopt( $connection, CURLOPT_URL, $url );
				curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
				curl_setopt( $connection, CURLOPT_POST, 0 );
				curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, 0 );
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
				$response = curl_exec( $connection );
				print_r( $response );
				die;
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
			if ( in_array( $marketplace_name, $selected_market ) ) {
				$key = array_search( $marketplace_name, $selected_market );
				if ( ( $key ) !== false ) {
					unset( $selected_market[ $key ] );
				}
				update_option( 'ced_selected_marketplaces', $selected_market );
			} else {
				$selected_market[ $marketplace_name ] = $marketplace_name;
				update_option( 'ced_selected_marketplaces', $selected_market );
			}

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
			echo json_encode(
				array(
					'basic_price'   => $price_total_basic,
					'advance_price' => $price_total_advance,
				)
			);
			wp_die();
		}

		public function ced_pricing_plan_display() {

			// $amzonBilling = CED_AMAZON_DIRPATH . 'admin/amazon/lib/ced-amazon-billing-apis.php';

			// if ( file_exists( $amzonBilling ) ) {
			// require_once $amzonBilling;
			// $amzonBilling = new Billing_Apis();
			// }

			// $contract_data = get_option( 'ced_unified_contract_details', array() );
			// $contract_id   = isset($contract_data['amazon']) && isset( $contract_data['amazon']['contract_id'] ) ? $contract_data['amazon']['contract_id'] : '';

			$subscription_details = get_option( 'ced_mcfw_subscription_details', array() );
			$contract_id          = isset( $subscription_details['contract_id'] ) ? $subscription_details['contract_id'] : 0;
			$currentPlan          = '';

			// var_dump($contract_id);

			if ( $contract_id ) {
				$currentPlan = $this->getCurrentPlanById( $contract_id );
			}
			// echo '<pre>';
			// print_r($currentPlan);
			// die();
			$plan_type    = ! empty( $_GET['plan_type'] ) ? sanitize_text_field( wp_unslash( $_GET['plan_type'] ) ) : 'monthly';
			$is_update    = ! empty( $_GET['is_update'] ) ? sanitize_text_field( wp_unslash( $_GET['is_update'] ) ) : 'no';
			$product_plan = ! empty( $_GET['product_plan'] ) ? sanitize_text_field( wp_unslash( $_GET['product_plan'] ) ) : '';

			$plan_type       = isset( $_GET['plan_type'] ) ? sanitize_text_field( $_GET['plan_type'] ) : 'monthly';
			$selected_market = get_option( 'ced_selected_marketplaces', array( 'Etsy', 'Walmart', 'Ebay', 'Amazon' ) );

			// echo '<pre>';
			// print_r($selected_market);
			// echo '</pre>';
			$prod_data = $this->ced_get_pricing_plan();

			// get all plans
			if ( ! empty( $prod_data ) ) {

				$subscribed_data = get_option( 'ced_unified_contract_details', '' );
				$subscribed_data = isset( $subscribed_data['amazon'] ) ? $subscribed_data['amazon'] : array();

				$subscriptionVerified = 0;
				$plan_name            = '';

				// check the subscription is verified or not
				$subscriptionStatus = '';

				if ( is_array( $currentPlan ) && ! empty( $currentPlan ) && isset( $currentPlan['data'] ) ) {
					$plan_data         = $currentPlan['data'];
					$status_is         = ! empty( $plan_data['status'] ) ? $plan_data['status'] : '';
					$next_payment_date = ! empty( $plan_data['next_payment_date'] ) ? $plan_data['next_payment_date'] : '';
					$end_date          = ! empty( $plan_data['end_date'] ) ? $plan_data['end_date'] : '';
					$next_payment_date = ! empty( $plan_data['next_payment_date'] ) ? $plan_data['next_payment_date'] : '';

					$contract_id = ! empty( $plan_data['id'] ) ? $plan_data['id'] : '';

					$billing_intents = isset( $plan_data['billing_intents'] ) ? $plan_data['billing_intents'] : array();

					if ( ! empty( $billing_intents ) ) {

						usort(
							$billing_intents,
							function ( $a, $b ) {
								return $a['id'] - $b['id'];
							}
						);

						$len            = count( $billing_intents ) - 1;
						$subscribedPlan = $billing_intents[ $len ]['payload'];

					}

					$plan_name      = ! empty( $subscribedPlan['name'] ) ? $subscribedPlan['name'] : '';
					$price          = ! empty( $subscribedPlan['price'] ) ? $subscribedPlan['price'] : '';
					$billing_period = ! empty( $subscribedPlan['billing_period'] ) ? $subscribedPlan['billing_period'] : '';

					$plan_name            = explode( '-', $plan_name );
					$subscribtion_details = array();

					$subscribtion_details['name']              = $plan_name[0];
					$subscribtion_details['billing_period']    = $billing_period;
					$subscribtion_details['status']            = $status_is;
					$subscribtion_details['end_date']          = $end_date;
					$subscribtion_details['next_payment_date'] = $next_payment_date;
					$subscribtion_details['price']             = $price;

					// discussion
					// if ( 'yes' === $is_update ) {
					// $contract_id = isset( $subscribed_data['contract_id'] ) ? $subscribed_data['contract_id'] : '';
					// }
					// discussion

					$cancelled   = '';
					$status_html = '';
					if ( 'active' === $status_is ) {
						$status_html = "<span style='color:green'>Active</span>";
					} elseif ( 'pending' === $status_is ) {
						$status_html = "<span style='color:yellow'>Pending</span>";
					} elseif ( 'canceled' === $status_is ) {
						$status_html = "<span style='color:red'>Canceled</span>";
					}

					if ( ! empty( $end_date ) ) {
						$current_date = strtotime( gmdate( 'Y-m-d h:i:s' ) );
						$end_date_str = strtotime( $end_date );
						if ( $current_date >= $end_date_str ) {
							$next_html = '<p style="margin-top: 20px;">Current plan is already expired on <span style="color:red;"><b>' . $end_date . '</b></span>. Please Renew it to continue the services.</p>';
						} else {
							$next_html = '<p style="margin-top: 20px;">Current plan is expiring on <span style="color:red;"><b>' . $end_date . '</b></span>. Please Renew it to continue the services.</p>';
						}
					} elseif ( 'canceled' == $status_is ) {
						$cancelled = 'disabled';
						$next_html = '<p style="margin-top: 20px;">Current plan is expiring on <span style="color:red;"><b>' . $next_payment_date . '</b></span>. Please Renew it to continue the services.</p>';
					} elseif ( ! empty( $next_payment_date ) ) {
						$next_html = '<p style="margin-top: 20px;">Your next subscription payment is scheduled on <span style="color:green;"><b>' . $next_payment_date . '</b></span>.</p>';
					} else {
						$next_html = '';
					}

					$responseBody       = isset( $currentPlan['data'] ) ? $currentPlan['data'] : array();
					$subscriptionStatus = isset( $responseBody['status'] ) ? $responseBody['status'] : '';

					$end_date   = is_null( $responseBody['end_date'] ) ? '' : $responseBody['end_date'];
					$timestamp1 = strtotime( $end_date );

					$currentTimestamp = time();
					$currentDateTime  = gmdate( 'Y-m-d H:i:s', $currentTimestamp );

					$timestamp2 = strtotime( $currentDateTime );

					// $timestamp2 = strtotime("today");
					// $timestamp1 = strtotime("27 Mar 2020") ;

					if ( 'active' == $subscriptionStatus || ( 'canceled' == $subscriptionStatus && $timestamp1 > $timestamp2 ) ) {
						$subscriptionVerified = 1;
					}
				}

				// new client || update plan case ||  subscription not verified  ==> display all plans
				if ( 'yes' == $is_update || ! $subscriptionVerified ) {

					// subscription  not verified;
					if ( 'pending' == $subscriptionStatus ) {

						?>

						<div class="ced-error ced_pending_checkout_container" >
							<p><?php echo esc_html__( 'You have a pending transaction. Please proceed to ', 'amazon-for-woocommerce' ); ?>
							<a href="#" class="btn btn-primary text-uppercase woo_ced_plan_selection_button" 
							data-plan_name="<?php echo esc_attr( $plan_name[0] ); ?>"  
							data-contract_id="<?php echo esc_attr( $plan_data['id'] ); ?>" ><?php echo esc_html__( 'checkout', 'amazon-for-woocommerce' ); ?></a> </p>
						</div>

						<?php
					}

					if ( 'paused' == $subscriptionStatus ) {
						?>

						<div class="ced-error ced_pending_checkout_container" >
							<p><?php echo esc_html__( "You don't currently have an active plan. Please choose a plan to proceed.", 'amazon-for-woocommerce' ); ?></p>
						</div>

						<?php
					}

					?>

					<div class="ced-pricing-plan-card-holder">

						<div class="switch-wrapper-container">
							<div class="switch-wrapper">
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
										>
										<label for="monthly">Monthly</label>
										<label for="yearly">Yearly</label>
										<span class="highlighter"></span>
									</div>

								</div>

								<div class="ced-pricing-card-holder">

									<?php
									$marketplaces = isset( $prod_data['basic']['marketplaces'] ) ? $prod_data['basic']['marketplaces'] : array();
									echo "<div id='ced_mcbc_pricing_wrapper'>";
									foreach ( $marketplaces as $market => $names ) {
										$checked = '';

										if ( in_array( $market, $selected_market ) ) {
											$checked   = 'checked';
											$sel_count = count( $selected_market );
										}if ( empty( $selected_market ) ) {
											$sel_count = 4;
											$checked   = 'checked';
										}
										echo '<div class="ced-marketplaces-names"><input type="checkbox" value=' . esc_attr( $market ) . ' ' . esc_attr( $checked ) . ' class="select-marketplace" >';
										echo '<label>' . esc_attr( $market ) . '</label></div>';
									}
									echo '</div>';

									// count($selected_market);
									?>

									<div class="ced-pricing-card-wrapper">

										<?php
										foreach ( $prod_data as $key => $plan ) {
											echo '<div class="ced-pricing-card">
											<div class="ced-pricing-card-content">
											<div class="ced-pricing-content">
											<h3>' . esc_attr( $plan['plan_name'] ) . '</h3>';
											$price_total_basic = $prod_data[ $key ]['pricing'][ $sel_count ]['plan_price'];
													// var_dump($price_total_basic);
											$plan_price = isset( $price_total_basic ) ? $price_total_basic : $plan['plan_price'];
													// var_dump($plan_price);
											echo '<h4><span class="ced-price-value" id="ced-price-' . esc_attr( $key ) . '">$' . esc_attr( $plan_price ) . '</span>/month</h4>';
											if ( 'yearly' === $plan_type ) {
												echo '<i>* billed annually</i>';
											}

											echo '</div>
											<div class="ced-pricing-list-text">
											<ol>';
											$desc = explode( ',', $plan['plan_description'] );
											if ( is_array( $desc ) && ! empty( $desc ) ) {
												foreach ( $desc as $k => $v ) {
													echo '<li>' . esc_attr( $v ) . '</li>';
												}
											}
											echo '</ol>
											</div>
											<div class="ced-pricing-select">
											<div class="ced-pricing-select-button">
											<a href="#" class="btn btn-primary text-uppercase woo_ced_plan_selection_button" data-count="' . esc_attr( count( $selected_market ) ) . '"  data-plan_name="' . esc_attr( $plan['plan_name'] ) . '" data-plan_cost="' . esc_attr( $plan['price_total'] ) . '" 
											data-contract_id="';

											if ( isset( $plan_data['status'] ) && 'canceled' !== $plan_data['status'] ) {
												echo esc_attr( $contract_id ); }

												echo '">Select plan</a>
												</div>
												</div>
												</div>
												</div>';
										}

										?>

										</div>        
									</div>    


								</div>
								<?php
								$trial_description = $this->get_trial_description();

								?>
								<div id="free_trial_notice" class="ced-center"><span><?php echo esc_html__( $trial_description ); ?></span></div>

								<?php
				} else {

					// subscription is verified  and display current plan
					?>

								<div class="ced-pricing-card-holder">
									<div class="ced-pricing-card-wrapper ced-center">
										<div class="ced-pricing-card">

											<div class="ced-stripe-container">
												<div class="ced-stripe">
													<div class="ced-stripe-active-tag" ><?php echo esc_html__( 'Active', 'amazon-for-woocommerce' ); ?></div>
												</div>
											</div>

											<div class="ced-pricing-card-content" style="margin-top: -110px;" >
												<div class="ced-pricing-content">

													<div class="ced-pricing-content">

														<h3><?php echo esc_attr( $subscribtion_details['name'] ); ?> </h3>

											<?php

											if ( 'month' == $subscribtion_details['billing_period'] ) {
												?>
															<h4><span class="ced-price-value">$<?php echo esc_attr( $subscribtion_details['price'] ); ?></span>/month</h4>
															<?php
											} elseif ( 'year' == $subscribtion_details['billing_period'] ) {
												$perMonth = $subscribtion_details['price'] / 12;

												?>
															<h4><span class="ced-price-value">$<?php echo esc_attr( round( $perMonth, 2 ) ); ?></span>/month</h4>
															<?php
											}

											if ( 'year' === $subscribtion_details['billing_period'] ) {
												echo '<i>* billed annually</i>';
											}
											?>

											<?php if ( 'active' == $status_is ) { ?>

															<div style="margin: 10px 0px; text-align: center;" >
																<label class="ced_amazon_next_billing_date"> Next billing: <b><?php print_r( substr( $responseBody['next_payment_date'], 0, 10 ) ); ?></b> </label>
															</div>

														<?php } else { ?>

															<div style="margin: 10px 0px; text-align: center;" >
																<label class="ced_amazon_end_billing_date"> Valid till: <b><?php print_r( substr( $responseBody['end_date'], 0, 10 ) ); ?></b> </label>
															</div>

															<?php

														}

														foreach ( $prod_data as $key => $plan ) {

															if ( $plan_name[0] == $plan['plan_name'] ) {
																?>

																<div class="ced-pricing-list-text">
																	<ol>
																		<?php
																		$desc = explode( ',', $plan['plan_description'] );
																		if ( is_array( $desc ) && ! empty( $desc ) ) {
																			foreach ( $desc as $k => $v ) {
																				echo '<li>' . esc_attr( $v ) . '</li>';
																			}
																		}
																		?>
																	</ol>
																</div>

																<?php
																break;
															}
														}

														?>

													</div>

												</div>

												<div class="ced-pricing-select">
													<?php
													if ( 'active' == $subscriptionStatus ) {
														?>
														<div class="ced-pricing-select-button ced-flex">
															<a class="ced-cancel-plan" href="javascript:void(0)" data-contract_id="<?php echo esc_attr( $contract_id ); ?>">Cancel plan</a>
															<a class="ced-change-plan">Manage plan</a>
														</div>
													<?php } elseif ( 'canceled' == $subscriptionStatus ) { ?>
														<div class="ced-pricing-select-button">
															<a class="ced-change-plan">Select plan</a>
														</div>
													<?php } ?>
												</div> 
											</div>
										</div>
									</div>
								</div>

								<?php

				}
			} else {

				// unable to get all plans
				?>

							<div class="jumbotron ced_subscription_warning" >
								<h1 class="display-4"><?php echo esc_html__( 'Hello, User!', 'amazon-for-woocommerce' ); ?></h1>
								<p class="lead"><?php echo esc_html__( 'At this moment we are unable to load your current plan details, please Refresh the page or contact support.', 'amazon-for-woocommerce' ); ?></p>
								<hr class="my-4">

								<p class="lead">
									<a class="components-button is-primary" href="#" role="button" onclick="history.back()" ><?php echo esc_html__( 'Go Back', 'amazon-for-woocommerce' ); ?></a>
								</p>
							</div>


							<?php
			}
		}


		public function ced_get_pricing_plan() {

			$plan_type    = ! empty( $_GET['plan_type'] ) ? sanitize_text_field( wp_unslash( $_GET['plan_type'] ) ) : 'monthly';
			$product_data = 'unified-bundle';
			$plan_data    = $this->get_plan_options();

			$prod_data   = array();
			$contract_id = '';

			if ( ! empty( $plan_data ) ) {
				$plan_data = json_decode( $plan_data, true );

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

		public function getCurrentPlanById( $id ) {
			if ( empty( $id ) ) {
				return(
					array(
						'status'  => false,
						'message' => 'Failed to fetch your current plans details. Please try again later or contact support.',
					)
				);

			}

			$data = array(
				'action'          => 'get_subscription',
				'subscription_id' => $id,
				'channel'         => 'unified-bundle',
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
