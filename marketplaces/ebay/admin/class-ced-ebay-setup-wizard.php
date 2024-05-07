<?php
namespace Ced\Ebay;

// Prevent direct access to this script
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'ed_Ebay_Setup_Wizard' ) ) {
	class Ced_Ebay_Setup_Wizard {


		public $steps     = array();
		public $step      = '';
		public $user_id   = '';
		public $func_name = '';

		public $tabs = array();
		public $tab  = '';

		public function setup_wizard() {
			$this->user_id = filter_input( INPUT_GET, 'user_id', FILTER_SANITIZE_SPECIAL_CHARS );

			$this->tabs  = array(
				'overview'                   => array(
					'name'    => 'Overview',
					'view'    => array( $this, 'renderHtml' ),
					'section' => 'overview',
				),
				'settings'                   => array(
					'name'    => 'Settings',
					'view'    => array( $this, 'renderHtml' ),
					'section' => 'settings',
				),
				'products-view'              => array(
					'name'    => 'Products Managements',
					'view'    => array( $this, 'renderHtml' ),
					'section' => 'products-view',
				),
				'feeds-view'                 => array(
					'name'    => 'Product Feeds',
					'view'    => array( $this, 'renderHtml' ),
					'section' => 'feeds-view',
				),
				'description-template'       => array(
					'name'    => 'Description Template',
					'view'    => array( $this, 'renderHtml' ),
					'section' => 'description-template',
				),
				'view-description-templates' => array(
					'name'    => 'View Description Templates',
					'view'    => array( $this, 'renderHtml' ),
					'section' => 'view-description-templates',
				),
				'product-template'           => array(
					'name'    => 'Product Template',
					'view'    => array( $this, 'renderHtml' ),
					'section' => 'product-template',
				),
				'view-templates'             => array(
					'name'    => 'View Templates',
					'view'    => array( $this, 'renderHtml' ),
					'section' => 'view-templates',
				),
				'view-ebay-orders'           => array(
					'name'    => 'View eBay Orders',
					'view'    => array( $this, 'renderHtml' ),
					'section' => 'view-ebay-orders',
				),
			);
			$this->steps = array(
				'setup-ebay'                  => array(
					'name'         => 'Login',
					'view'         => array( $this, 'renderHtml' ),
					'save_handler' => array( $this, 'save_options' ),
					'skip_handler' => array( $this, 'skip_options' ),
					'section'      => 'setup-ebay',

				),
				'onboarding-global-options'   => array(
					'name'         => 'Global Options',
					'view'         => array( $this, 'renderHtml' ),
					'save_handler' => array( $this, 'save_options' ),
					'skip_handler' => array( $this, 'skip_options' ),
					'section'      => 'onboarding-global-options',
				),
				'onboarding-general-settings' => array(
					'name'         => 'General Settings',
					'view'         => array( $this, 'renderHtml' ),
					'section'      => 'onboarding-general-settings',
					'save_handler' => array( $this, 'save_options' ),
					'skip_handler' => array( $this, 'skip_options' ),
				),
				'onboarding-completed'        => array(
					'name'         => 'Done',
					'view'         => array( $this, 'renderHtml' ),
					'save_handler' => array( $this, 'save_options' ),
					'section'      => 'onboarding-completed',
				),
			);

			// TODO: Check to see if the onboarding is completed and account is succesfully connected, redirect to overview or last visited tab.

			if ( isset( $_GET['section'] ) && in_array( sanitize_key( $_GET['section'] ), array_keys( $this->steps ), true ) ) {
				$this->step = sanitize_key( $_GET['section'] );
			} elseif ( isset( $_GET['section'] ) && in_array( $_GET['section'], array_keys( $this->tabs ), true ) ) {
					$current_uri = wp_http_validate_url( wc_get_current_admin_url() );
				if ( ! empty( $current_uri ) ) {
					update_option( 'ced_ebay_active_user_url', $current_uri );
				}
					$this->tab = isset( $_GET['section'] ) ? wc_clean( $_GET['section'] ) : 'overview';
			} else {
				$this->step = 'setup-ebay';
			}

			// Check if there is a save currently in progress, call save_handler if necessary
			$save_step = filter_input( \INPUT_POST, 'save_step', \FILTER_SANITIZE_SPECIAL_CHARS );
			$skip_step = filter_input( \INPUT_POST, 'skip_step', \FILTER_SANITIZE_SPECIAL_CHARS );
			if ( $save_step && isset( $this->steps[ $this->step ]['save_handler'] ) ) {
				call_user_func( $this->steps[ $this->step ]['save_handler'], $this );
			}
			if ( $skip_step && isset( $this->steps[ $this->step ]['skip_handler'] ) ) {
				call_user_func( $this->steps[ $this->step ]['skip_handler'], $this );
			}

			$this->run();
		}

		public function renderHtml( $instance, $key ) {
			// print_r('renderHtml'); die;
			$this->func_name = $key;
			$this->print_html();
		}

		public function run() {
			$this->print_step_content();
		}



		public function print_html() {
			$is_error = filter_input( INPUT_GET, 'error', FILTER_SANITIZE_SPECIAL_CHARS );
			$user_id  = filter_input( INPUT_GET, 'user_id', FILTER_SANITIZE_SPECIAL_CHARS );
			$site_id  = filter_input( INPUT_GET, 'sid', FILTER_SANITIZE_SPECIAL_CHARS );
			$remote_shop_id  = filter_input( INPUT_GET, 'rsid', FILTER_SANITIZE_SPECIAL_CHARS );
			$keys     = array_keys( $this->steps );
			if ( ! empty( $keys ) && is_array( $keys ) ) {
				unset( $keys[0] );
				$keys = array_values( $keys );
			}
			$keys_length = count( $keys );
			$step_index  = array_search( $this->step, $keys, true );
			
			switch ( $this->func_name ) {

				case 'setup-ebay':
					$this->step = '';
					require_once CED_EBAY_DIRPATH . 'admin/partials/setup-wizard/connect-with-ebay.php';
					break;
				case 'onboarding-global-options':
					$this->check_if_url_params_are_valid( $user_id, $site_id, $remote_shop_id );
					require_once CED_EBAY_DIRPATH . 'admin/partials/setup-wizard/ced-ebay-global-options.php';
					break;
				case 'onboarding-general-settings':
					$this->check_if_url_params_are_valid( $user_id, $site_id, $remote_shop_id );
					require_once CED_EBAY_DIRPATH . 'admin/partials/setup-wizard/ced-ebay-general-settings.php';
					break;
				case 'onboarding-completed':
					$this->check_if_url_params_are_valid( $user_id, $site_id, $remote_shop_id );
					require_once CED_EBAY_DIRPATH . 'admin/partials/setup-wizard/ced-ebay-onboarding-completed.php';
					break;
				case 'overview':
					$this->check_if_url_params_are_valid( $user_id, $site_id, $remote_shop_id );
					require_once CED_EBAY_DIRPATH . 'admin/partials/overview.php';
					break;
				case 'settings':
					$this->check_if_url_params_are_valid( $user_id, $site_id, $remote_shop_id );
					require_once CED_EBAY_DIRPATH . 'admin/partials/settings-view.php';
					break;
				case 'products-view':
					$this->check_if_url_params_are_valid( $user_id, $site_id, $remote_shop_id );
					require_once CED_EBAY_DIRPATH . 'admin/partials/products-view.php';
					break;
				case 'feeds-view':
					$this->check_if_url_params_are_valid( $user_id, $site_id, $remote_shop_id );
					require_once CED_EBAY_DIRPATH . 'admin/partials/status-feed.php';
					break;
				case 'description-template':
					$this->check_if_url_params_are_valid( $user_id, $site_id, $remote_shop_id );
					require_once CED_EBAY_DIRPATH . 'admin/partials/ced_ebay_description_styling.php';
					break;
				case 'view-description-templates':
					$this->check_if_url_params_are_valid( $user_id, $site_id,$remote_shop_id );
					require_once CED_EBAY_DIRPATH . 'admin/partials/ced-ebay-description-template-fields.php';
					break;
				case 'product-template':
					$this->check_if_url_params_are_valid( $user_id, $site_id );
					require_once CED_EBAY_DIRPATH . 'admin/partials/product-template.php';
					break;
				case 'view-templates':
					$this->check_if_url_params_are_valid( $user_id, $site_id );
					if ( isset( $_GET['profileID'] ) ) {
						require_once CED_EBAY_DIRPATH . 'admin/partials/profile-edit-view.php';
					} else {
						require_once CED_EBAY_DIRPATH . 'admin/partials/profiles-view.php';
					}
					break;
				case 'view-ebay-orders':
					$this->check_if_url_params_are_valid( $user_id, $site_id, $remote_shop_id );
					require_once CED_EBAY_DIRPATH . 'admin/partials/orders-view.php';
					break;
				default:
					require_once CED_EBAY_DIRPATH . 'admin/partials/setup-wizard/connect-with-ebay.php';
					break;

			}
		}

		public function check_if_url_params_are_valid( $user_id, $site_id, $remote_shop_id = '' ) {
			$shop_data          = ced_ebay_get_shop_data( $user_id, $site_id );
			if ( empty( $user_id ) || empty( $shop_data ) || false === $shop_data['remote_shop_id'] || ! isset( $shop_data['remote_shop_id'] ) ) {
				$current_uri = remove_query_arg( array( 'user_id', 'site_id', 'section', 'channel' ), wc_get_current_admin_url() );
				wp_safe_redirect( esc_url_raw( $current_uri ) );
				exit();
			}
			
		}
		public function print_step_content() {
			$view        = ! empty( $this->steps[ $this->step ]['view'] ) ? $this->steps[ $this->step ]['view'] : '';
			$section     = ! empty( $this->steps[ $this->step ]['section'] ) ? $this->steps[ $this->step ]['section'] : '';
			$tab_view    = ! empty( $this->tabs[ $this->tab ]['view'] ) ? $this->tabs[ $this->tab ]['view'] : '';
			$tab_section = ! empty( $this->tabs[ $this->tab ]['section'] ) ? $this->tabs[ $this->tab ]['section'] : '';

			if ( ! empty( $tab_view ) ) {
				call_user_func( $tab_view, $this, $tab_section );
			}
			if ( ! empty( $view ) ) {
				call_user_func( $view, $this, $section );
			}
		}

		public function skip_options() {
			$connected_accounts = ! empty( get_option( 'ced_ebay_connected_accounts' ) ) ? get_option( 'ced_ebay_connected_accounts', true ) : array();
			$user_id            = filter_input( INPUT_GET, 'user_id', FILTER_SANITIZE_SPECIAL_CHARS );
			$site_id            = filter_input( INPUT_GET, 'sid', FILTER_SANITIZE_SPECIAL_CHARS );
			$remote_shop_id     = filter_input( INPUT_GET, 'rsid', FILTER_SANITIZE_SPECIAL_CHARS );
			$section            = $this->steps[ $this->step ]['section'];
			$keys               = array_keys( $this->steps );
			$step_index         = array_search( $this->step, $keys, true );
			$connected_accounts = ! empty( get_option( 'ced_ebay_connected_accounts' ) ) ? get_option( 'ced_ebay_connected_accounts', true ) : array();
			if ( ! empty( $connected_accounts ) && isset( $connected_accounts[ $user_id ][ $site_id ] ) && false !== $step_index ) {
				$connected_accounts[ $user_id ][ $site_id ]['remote_shop_id'] = $remote_shop_id;
				$connected_accounts[ $user_id ][ $site_id ]['ced_ebay_current_step'] = (string) $step_index;
				update_option( 'ced_ebay_connected_accounts', $connected_accounts );
			}
			// add validations to the form submission event
			switch ( $section ) {
				case 'onboarding-global-options':
					if ( ! isset( $_REQUEST['ced_ebay_global_options_button'] ) || ! wp_verify_nonce( wc_clean( wp_unslash( $_REQUEST['ced_ebay_global_options_button'] ) ), 'ced_ebay_global_options_action' ) ) {
						// If nonce check fails, redirect the user back to login screen.
						wp_safe_redirect(
							esc_url_raw(
								add_query_arg(
									array(
										'section' => $keys[ $step_index ],
										'error'   => 'nonce_check_failed',
									)
								)
							)
						);
						exit();
					}

					wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
					exit();

				case 'onboarding-general-settings':
					if ( ! isset( $_REQUEST['ced_ebay_general_settings_button'] ) || ! wp_verify_nonce( wc_clean( wp_unslash( $_REQUEST['ced_ebay_general_settings_button'] ) ), 'ced_ebay_general_settings_action' ) ) {
						// If nonce check fails, redirect the user back to login screen.
						wp_safe_redirect(
							esc_url_raw(
								add_query_arg(
									array(
										'section' => $keys[ $step_index ],
										'error'   => 'nonce_check_failed',
									)
								)
							)
						);
						exit();
					}

					wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
					exit();
			}
		}

		public function save_options() {
			$section            = $this->steps[ $this->step ]['section'];
			$keys               = array_keys( $this->steps );
			$step_index         = array_search( $this->step, $keys, true );
			$user_id            = isset( $_GET['user_id'] ) ? wc_clean( $_GET['user_id'] ) : false;
			$site_id            = isset( $_GET['sid'] ) ? wc_clean( $_GET['sid'] ) : false;
			$remote_shop_id            = isset( $_GET['rsid'] ) ? wc_clean( $_GET['rsid'] ) : false;
			$login_mode         = isset( $_GET['login_mode'] ) ? wc_clean( $_GET['login_mode'] ) : 'production';
			$connected_accounts = ! empty( get_option( 'ced_ebay_connected_accounts' ) ) ? get_option( 'ced_ebay_connected_accounts', true ) : array();
			if ( false !== $step_index && !empty($user_id) && '' !== $site_id && !empty($remote_shop_id)) {
				$connected_accounts[ $user_id ][ $site_id ]['remote_shop_id'] = $remote_shop_id;
				$connected_accounts[ $user_id ][ $site_id ]['ced_ebay_current_step'] = (string) $step_index;
				update_option( 'ced_ebay_connected_accounts', $connected_accounts );
			}
			// add validations to the form submission event
			switch ( $section ) {
				case 'setup-ebay':
					if ( ! isset( $_REQUEST['ced_ebay_connect_button'] ) || ! wp_verify_nonce( wc_clean( wp_unslash( $_REQUEST['ced_ebay_connect_button'] ) ), 'ced_ebay_connect_action' ) ) {
						// If nonce check fails, redirect the user back to login screen.
						$current_uri = remove_query_arg( array( 'user_id', 'site_id' ), wc_get_current_admin_url() );
						wp_safe_redirect(
							esc_url_raw(
								add_query_arg(
									array(
										'section' => $keys[ $step_index ],
										'error'   => 'nonce_check_failed',
									),
									$current_uri
								)
							)
						);
						exit();
					}
					if ( isset( $_REQUEST['action'] ) && 'ced_ebay_connect_account' == wc_clean( wp_unslash( $_REQUEST['action'] ) ) ) {
						$selected_ebay_site = filter_input( INPUT_POST, 'ced_ebay_marketplace_region', FILTER_SANITIZE_SPECIAL_CHARS );
						if ( '0' !== $selected_ebay_site && '-1' === $selected_ebay_site ) {
							wp_safe_redirect(
								esc_url_raw(
									add_query_arg(
										array(
											'section' => $keys[ $step_index ],
											'error'   => 'missing_region',
										)
									)
								)
							);
							exit();
						} else {
								update_option( 'ced_ebay_mode_of_operation', $login_mode );
								$cedAuthorization        = new Ebayauthorization();
								$cedAuhorizationInstance = $cedAuthorization->get_instance();
								$authURL                 = $cedAuhorizationInstance->getOAuthUrl( $selected_ebay_site );
								wp_safe_redirect( esc_url_raw( $authURL ) );
								exit();
						}
					} elseif ( isset( $_REQUEST['action'] ) && 'ced_ebay_verify_account' == wc_clean( wp_unslash( $_REQUEST['action'] ) ) ) {
						require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
						if ( function_exists( 'as_enqueue_async_action' ) ) {
							$async_action_id = as_enqueue_async_action(
								'ced_ebay_fetch_site_categories',
								array(
									'data' => array(
										'site_id' => $site_id,
										'user_id' => $user_id,
										'remote_shop_id' => $remote_shop_id
									),
								),
								'ced_ebay'
							);
						}

						$ebayUploadInstance = EbayUpload::get_instance( $remote_shop_id );
						$activelist         = $ebayUploadInstance->get_active_products( 1,10 );
						$total_products     = 0;
						if ( ! empty( $activelist['Errors'] ) && '931' == $activelist['Errors']['ErrorCode'] ) {
							$current_uri = remove_query_arg( array( 'user_id', 'site_id', 'remote_shop_id' ), wc_get_current_admin_url() );
							wp_safe_redirect(
								esc_url_raw(
									add_query_arg(
										array(
											'section' => $keys[ $step_index ],
											'error'   => 'ebay_api_error',
										),
										$current_uri
									)
								)
							);
							exit();
						}
						if(!empty($activelist['error_code'])){
							$current_uri = remove_query_arg( array( 'user_id', 'site_id', 'remote_shop_id' ), wc_get_current_admin_url() );
							wp_safe_redirect(
								esc_url_raw(
									add_query_arg(
										array(
											'section' => $keys[ $step_index ],
											'error'   => $activelist['error_code'],
										),
										$current_uri
									)
								)
							);
							exit();
						}
						if ( isset( $activelist['ActiveList']['PaginationResult']['TotalNumberOfEntries'] ) && 0 < $activelist['ActiveList']['PaginationResult']['TotalNumberOfEntries'] && 'Success' == $activelist['Ack'] ) {
							$totalListingsOnEbay = absint( $activelist['ActiveList']['PaginationResult']['TotalNumberOfEntries'] );
							update_option( 'ced_ebay_total_listings_' . $user_id, $totalListingsOnEbay );

							// TODO: Turn on existing products sync scheduler

						}
						wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
						exit();
					}

					break;

				case 'onboarding-global-options':
					if ( ! isset( $_REQUEST['ced_ebay_global_options_button'] ) || ! wp_verify_nonce( wc_clean( wp_unslash( $_REQUEST['ced_ebay_global_options_button'] ) ), 'ced_ebay_global_options_action' ) ) {
						// If nonce check fails, redirect the user back to login screen.
						wp_safe_redirect(
							esc_url_raw(
								add_query_arg(
									array(
										'section' => $keys[ $step_index ],
										'error'   => 'nonce_check_failed',
									)
								)
							)
						);
						exit();
					}

					if ( ! empty( get_option( 'ced_ebay_global_options' ) ) && isset( $_REQUEST['ced_ebay_global_options'][ $user_id ][ $site_id ] ) ) {
						$global_options          = get_option( 'ced_ebay_global_options', true );
						$selected_global_options = isset( $_REQUEST['ced_ebay_global_options'][ $user_id ][ $site_id ] ) ? wc_clean( $_REQUEST['ced_ebay_global_options'][ $user_id ][ $site_id ] ) : array();
						if ( ! empty( $selected_global_options ) ) {
							$global_options_array = array();
							foreach ( $selected_global_options as $gKey => $gValue ) {
								$explode_gKey = explode( '|', $gKey );
								$global_options_array[ $explode_gKey[0] ][ $explode_gKey[1] ] = $gValue;
							}
						}
						if ( ! empty( $global_options_array ) ) {
							$global_options[ $user_id ][ $site_id ] = $global_options_array;
							update_option( 'ced_ebay_global_options', $global_options );
						}
					}

					wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
					exit();
				case 'onboarding-general-settings':
					if ( ! isset( $_REQUEST['ced_ebay_general_settings_button'] ) || ! wp_verify_nonce( wc_clean( wp_unslash( $_REQUEST['ced_ebay_general_settings_button'] ) ), 'ced_ebay_general_settings_action' ) ) {
						// If nonce check fails, redirect the user back to login screen.
						wp_safe_redirect(
							esc_url_raw(
								add_query_arg(
									array(
										'section' => $keys[ $step_index ],
										'error'   => 'nonce_check_failed',
									)
								)
							)
						);
						exit();
					}

					$global_settings_array = ! empty( get_option( 'ced_ebay_global_settings' ) ) ? get_option( 'ced_ebay_global_settings', true ) : array();
					if ( ! empty( $global_settings_array ) ) {
						if ( isset( $_REQUEST['ced_ebay_global_settings'] ) ) {
							$global_settings_array[ $user_id ][ $site_id ] = wc_clean( $_REQUEST['ced_ebay_global_settings'] );
							update_option( 'ced_ebay_global_settings', $global_settings_array );

						}
					} elseif ( isset( $_REQUEST['ced_ebay_global_settings'] ) ) {
							$global_settings                         = array();
							$global_settings[ $user_id ][ $site_id ] = wc_clean( $_REQUEST['ced_ebay_global_settings'] );
							update_option( 'ced_ebay_global_settings', $global_settings );
					}

					wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
					exit();

				case 'onboarding-completed':
					if ( ! isset( $_REQUEST['ced_ebay_onboarding_completed_button'] ) || ! wp_verify_nonce( wc_clean( wp_unslash( $_REQUEST['ced_ebay_onboarding_completed_button'] ) ), 'ced_ebay_onboarding_completed_action' ) ) {
						// If nonce check fails, redirect the user back to login screen.
						wp_safe_redirect(
							esc_url_raw(
								add_query_arg(
									array(
										'section' => $keys[ $step_index ],
										'error'   => 'nonce_check_failed',
									)
								)
							)
						);
						exit();
					}
					wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
					exit();

			}
		}


		private function get_next_step_link() {
			$keys = array_keys( $this->steps );
			if ( end( $keys ) === $this->step ) {
				return admin_url();
			}
			$step_index = array_search( $this->step, $keys, true );
			if ( false === $step_index ) {
				return '';
			}
			return add_query_arg( 'section', $keys[ $step_index + 1 ] );
		}
	}

}
