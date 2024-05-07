<?php
/**
 * Core Functions
 *
 * @package  Walmart_Woocommerce_Integration
 * @version  1.0.0
 * @link     https://woocommerce.com/vendor/cedcommerce/
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Environment check for Walmart.
 *
 * @since 1.0.0
 */
function wifw_environment() {
	$config_details = get_option( 'ced_walmart_configuration_details', array() );
	if ( isset( $config_details['environment'] ) && 'sandbox' == $config_details['environment'] ) {
		return '_sandbox';
	}
	return '';
}

/**
 * Check WooCommmerce active or not.
 *
 * @since 1.0.0
 */
function ced_walmart_check_woocommerce_active() {
	/** Get active plugin list
	 *
	 * @since 1.0.0
	 */
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		return true;
	}
	return false;
}
/**
 * This code runs when WooCommerce is not activated,
 *
 * @since 1.0.0
 */
function deactivate_ced_walmart_woo_missing() {
	deactivate_plugins( CED_WALMART_PLUGIN_BASENAME );
	add_action( 'admin_notices', 'ced_walmart_woo_missing_notice' );
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
}
/**
 * Callback function for sending notice if woocommerce is not activated.
 *
 * @since 1.0.0
 */
function ced_walmart_woo_missing_notice() {
	// translators: %s: search term !!
	echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html( __( 'Walmart Integration for Woocommerce requires WooCommerce to be installed and active. You can download %s from here.', 'walmart-woocommerce-integration' ) ), '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>' ) . '</p></div>';
}


function ced_walmart_cedcommerce_logo() {
	?>
	<img src="<?php echo esc_url( CED_WALMART_URL . 'admin/images/ced-logo.png' ); ?> ">
	<?php
}

/**
 * Callback function for including header.
 *
 * @since 1.0.0
 */
function get_walmart_header() {
	$header_file = CED_WALMART_DIRPATH . 'admin/partials/header.php';
	include_file( $header_file );
}
/**
 * Callback function for including files.
 *
 * @since 1.0.0
 */
function include_file( $filepath = '' ) {
	if ( file_exists( $filepath ) ) {
		include_once $filepath;
		return true;
	}
	return false;
}
/**
 * Callback function for printing admin notice.
 *
 * @since 1.0.0
 */
function print_success_notice( $message = 'Details saved successfully.' ) {
	?>
	<div class="notice notice-success"><p><?php esc_html_e( $message, 'walmart-woocommerce-integration' ); ?></p></div>
	<?php
}
/**
 * Callback function for navigation menus.
 *
 * @since 1.0.0
 */
function get_navigation_menus() {

	$ced_walmart_configuration_details = get_option( 'ced_walmart_configuration_details', array() );
	$wfs                               = isset( $ced_walmart_configuration_details['wfs'] ) ? $ced_walmart_configuration_details['wfs'] : '';

	$navigation_menus = array(
		'overview'          => admin_url( 'admin.php?page=sales_channel&channel=walmart' ),
		'settings'          => admin_url( 'admin.php?page=sales_channel&channel=walmart&section=settings' ),
		'templates'         => admin_url( 'admin.php?page=sales_channel&channel=walmart&section=templates' ),
		'products'          => admin_url( 'admin.php?page=sales_channel&channel=walmart&section=products' ),
		'orders'            => admin_url( 'admin.php?page=sales_channel&channel=walmart&section=orders' ),
		'shipping_template' => admin_url( 'admin.php?page=sales_channel&channel=walmart&section=shipping_template' ),
		'insights'          => admin_url( 'admin.php?page=sales_channel&channel=walmart&section=insights' ),
		'feeds'             => admin_url( 'admin.php?page=sales_channel&channel=walmart&section=feeds' ),
	);
	if ( 'on' == $wfs ) {
		$navigation_menus['wfs'] = admin_url( 'admin.php?page=sales_channel&channel=walmart&section=wfs' );
	}
	/** Get walmart menus
	 *
	 * @since 1.0.0
	 */
	$navigation_menus = apply_filters( 'ced_walmart_navigation_menus', $navigation_menus );
	return $navigation_menus;
}




function ced_walmart_onboarding_steps() {
	$steps = array(
		'global_setting'  => 'Global Options',
		'general_setting' => 'General Settings',
		'completed'       => 'Done',
	);
	/**
		 * Filter for getting onboarding steps
		 *
		 * @since  1.0.0
		 */
	$steps = apply_filters( 'ced_walmart_onboarding_steps', $steps );
	return $steps;
}


function ced_walmart_setup_wizard_bar() {
	$steps           = ced_walmart_onboarding_steps();
	$completed_steps = get_option( 'ced_walmart_onboarding_completed_steps', array() );
	$current_step    = isset( $_GET['step'] ) ? sanitize_text_field( $_GET['step'] ) : '';

	$step_bar  = '<div class="woocommerce-progress-form-wrapper">';
	$step_bar .= '<h2 style="text-align: left;">Walmart Integration: Onboarding</h2>';
	$step_bar .= '<ol class="wc-progress-steps ced-progress">';

	foreach ( $steps as $key => $value ) {
		$active = '';
		if ( $current_step === $key || in_array( $key, $completed_steps ) ) {
			$active = 'active';
		}
		$step_bar .= '<li id="' . $key . '" class="' . $active . '">' . __( $value, 'walmart-woocommerce-integration' ) . ' </li>';
	}

	$step_bar .= '</ol>';
	return $step_bar;
}


function ced_walmart_update_steps( $steps = '' ) {
	$completed_steps   = get_option( 'ced_walmart_onboarding_completed_steps', array() );
	$completed_steps[] = $steps;
	update_option( 'ced_walmart_onboarding_completed_steps', array_unique( $completed_steps ) );
}


function ced_walmart_return_partner_detail_option() {
	$account_list = get_option( 'ced_walmart_saved_account_list', '' );
	if ( empty( $account_list ) ) {
		$account_list = array();
	} else {
		$account_list = json_decode( $account_list, true );
	}

	return $account_list;
}





function ced_walmart_get_active_action_steps_template( $action = '', $step = '' ) {
	switch ( $action ) {
		case 'onboarding':
			switch ( $step ) {
				case 'verify':
					include_file( CED_WALMART_DIRPATH . 'admin/accounts/class-ced-walmart-verify-account.php' );
					break;
				case 'check_store':
					include_file( CED_WALMART_DIRPATH . 'admin/accounts/class-ced-walmart-check-store.php' );
					break;
				case 'product_found':
					include_file( CED_WALMART_DIRPATH . 'admin/accounts/class-ced-walmart-store-sync.php' );
					break;
			}
			break;

		case 'setup-wizard':
			switch ( $step ) {
				case 'global_setting':
					include_file( CED_WALMART_DIRPATH . 'admin/setup-wizard/class-ced-walmart-setup-wizard-global-setting.php' );
					$obj = new Ced_Walmart_Setup_Wizard_Global_Setting();
					$obj->render_fields();
					break;
				case 'general_setting':
					include_file( CED_WALMART_DIRPATH . 'admin/setup-wizard/class-ced-walmart-setup-wizard-general-setting.php' );
					$obj = new Ced_Walmart_Setup_Wizard_General_Setting();
					$obj->render_fields();
					break;
				case 'completed':
					include_file( CED_WALMART_DIRPATH . 'admin/setup-wizard/class-ced-walmart-setup-wizard-complete.php' );
					$obj = new Ced_Walmart_Setup_Wizard_Completed_Onboarding();
					$obj->render_fields();
					break;

			}
			break;
	}
}



if ( ! function_exists( 'get_walmart_orders_count' ) ) {
	function get_walmart_orders_count( $shop_id ) {

		global $wpdb;
		$orders_post_ids = $wpdb->get_results( $wpdb->prepare( "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key`=%s AND `meta_value`=%s", '_ced_walmart_order_store_id' . wifw_environment(), $shop_id ), 'ARRAY_A' );
		return count( $orders_post_ids );
	}
}

if ( ! function_exists( 'get_walmart_products_count' ) ) {
	function get_walmart_products_count( $shop_id, $is_all = false ) {
		$args = array(
			'post_type'   => 'product',
			'numberposts' => -1,
			'fields'      => 'ids',

		);

		if ( ! $is_all ) {
			$args['meta_query'] = array(
				array(
					'key'     => 'ced_walmart_product_uploaded_' . $shop_id . wifw_environment(),
					'compare' => '!=',
					'value'   => '',
				),
			);
		}
		$posts = get_posts(
			$args
		);

		return count( $posts );
	}
}


if ( ! function_exists( 'get_walmart_orders_revenue' ) ) {
	function get_walmart_orders_revenue( $shop_id ) {
		$env = '_ced_walmart_order_store_id' . wifw_environment();

		$args = array(
			'post_type'   => 'shop_order',
			'numberposts' => -1,
			'fields'      => 'ids',
			'post_status' => array( 'wc-completed' ),
		);

		$args['meta_query'] = array(
			array(
				'key'     => $env,
				'compare' => '=',
				'value'   => $shop_id,
			),
		);

		$ids = get_posts(
			$args
		);

		if ( is_array( $ids ) && ! empty( $ids ) ) {

			$total_value = 0;
			$total_value = array_map(
				function ( $id ) {
					$order = wc_get_order( $id );
					return $order->get_total();
				},
				$ids
			);
			$total_value = array_sum( $total_value );

		}
		return ! empty( $total_value ) ? get_woocommerce_currency_symbol() . $total_value : get_woocommerce_currency_symbol() . 0.00;
	}
}


/**
 * Callback function for current section.
 *
 * @since 1.0.0
 */
function get_active_section() {
	return ( isset( $_GET['section'] ) && ! isset( $_GET['add-new-account'] ) ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : 'overview';
}

/**
 * Callback function for client_id.
 *
 * @since 1.0.0
 */
function get_client_id() {
	$config_details = get_option( 'ced_walmart_configuration_details', array() );
	return isset( $config_details['client_id'] ) ? $config_details['client_id'] : '';
}

/**
 * Callback function for client_secret.
 *
 * @since 1.0.0
 */
function get_client_secret() {
	$config_details = get_option( 'ced_walmart_configuration_details', array() );
	return isset( $config_details['client_secret'] ) ? $config_details['client_secret'] : '';
}

/**
 * Callback function for access_token.
 *
 * @since 1.0.0
 */
function get_access_token() {
	return get_option( 'ced_walmart_token', '' );
}


function ced_walmart_tool_tip( $tip = '' ) {
	print_r( "</br><span class='cedcommerce-tip'>[ $tip ]</span>" );
}


/**
 * Callback function for display html.
 *
 * @since 1.0.0
 */
function get_instuctions_html( $label = 'Instructions' ) {
	?>
	<div class="ced_walmart_parent_element">
		<h2>
			<label><?php echo esc_html_e( $label, 'walmart-woocommerce-integration' ); ?></label>
			<span class="dashicons dashicons-arrow-down-alt2 ced_walmart_instruction_icon"></span>
		</h2>
	</div>
	<?php
}
/**
 * Callback function get_return_address.
 *
 * @since 1.0.0
 */
function get_return_address() {
	$return_address = get_option( 'ced_walmart_merchant_return_address', array() );
	$return_country = isset( $return_address['ced_walmart_return_country'] ) ? $return_address['ced_walmart_return_country'] : '';
	$return_state   = isset( $return_address['ced_walmart_return_state'] ) ? $return_address['ced_walmart_return_state'] : '';
	$first_address  = isset( $return_address['ced_walmart_return_first_address'] ) ? $return_address['ced_walmart_return_first_address'] : '';
	$second_address = isset( $return_address['ced_walmart_return_second_address'] ) ? $return_address['ced_walmart_return_second_address'] : '';
	$return_city    = isset( $return_address['ced_walmart_return_city'] ) ? $return_address['ced_walmart_return_city'] : '';
	$zip_code       = isset( $return_address['ced_walmart_return_zip_code'] ) ? $return_address['ced_walmart_return_zip_code'] : '';
	$center         = isset( $return_address['ced_walmart_center_name'] ) ? $return_address['ced_walmart_center_name'] : '';
	$phone          = isset( $return_address['ced_walmart_return_mobile'] ) ? $return_address['ced_walmart_return_mobile'] : '';
	$email          = isset( $return_address['ced_walmart_return_email'] ) ? $return_address['ced_walmart_return_email'] : '';
	$address        = array(
		'name'       => $center,
		'address1'   => $first_address,
		'address2'   => $second_address,
		'city'       => $return_city,
		'state'      => $return_state,
		'postalCode' => $zip_code,
		'country'    => $return_country,
		'dayPhone'   => $phone,
		'emailId'    => $email,
	);
	return $address;
}





function ced_walmart_subcategories( $category ) {
	$categories = array(
		'animal_accessories'                  => 'Animal Accessories',
		'animal_food'                         => 'Animal Food',
		'animal_health_and_grooming'          => 'Animal Health & Grooming',
		'animal_other'                        => 'Animal Other',
		'art_and_craft_other'                 => 'Art & Craft',
		'baby_other'                          => 'Baby Diapering, Care, & Other',
		'baby_food'                           => 'Baby Food',
		'baby_furniture'                      => 'Baby Furniture',
		'baby_toys'                           => 'Baby Toys',
		'child_car_seats'                     => 'Baby Transport',
		'personal_care'                       => 'Beauty, Personal Care, & Hygiene',
		'bedding'                             => 'Bedding',
		'books_and_magazines'                 => 'Books & Magazines',
		'building_supply'                     => 'Building Supply',
		'cameras_and_lenses'                  => 'Cameras & Lenses',
		'carriers_and_accessories_other'      => 'Carriers & Accessories',
		'cases_and_bags'                      => 'Cases & Bags',
		'cell_phones'                         => 'Cell Phones',
		'ceremonial_clothing_and_accessories' => 'Ceremonial Clothing & Accessories',
		'clothing_other'                      => 'Clothing',
		'computer_components'                 => 'Computer Components',
		'computers'                           => 'Computers',
		'costumes'                            => 'Costumes',
		'cycling'                             => 'Cycling',
		'decorations_and_favors'              => 'Decorations & Favors',
		'electrical'                          => 'Electrical',
		'electronics_accessories'             => 'Electronics Accessories',
		'electronics_cables'                  => 'Electronics Cables',
		'electronics_other'                   => 'Electronics Other',
		'food_and_beverage_other'             => 'Food & Beverage',
		'footwear_other'                      => 'Footwear',
		'fuels_and_lubricants'                => 'Fuels & Lubricants',
		'funeral'                             => 'Funeral',
		'furniture_other'                     => 'Furniture',
		'garden_and_patio_other'              => 'Garden & Patio',
		'gift_supply_and_awards'              => 'Gift Supply & Awards',
		'grills_and_outdoor_cooking'          => 'Grills & Outdoor Cooking',
		'hardware'                            => 'Hardware',
		'health_and_beauty_electronics'       => 'Health & Beauty Electronics',
		'home_other'                          => 'Home Decor, Kitchen, & Other',
		'cleaning_and_chemical'               => 'Household Cleaning Products & Supplies',
		'instrument_accessories'              => 'Instrument Accessories',
		'jewelry_other'                       => 'Jewelry',
		'land_vehicles'                       => 'Land Vehicles',
		'large_appliances'                    => 'Large Appliances',
		'medical_aids'                        => 'Medical Aids & Equipment',
		'medicine_and_supplements'            => 'Medicine & Supplements',
		'movies'                              => 'Movies',
		'music_cases_and_bags'                => 'Music Cases & Bags',
		'music'                               => 'Music',
		'musical_instruments'                 => 'Musical Instruments',
		'office_other'                        => 'Office',
		'optical'                             => 'Optical',
		'optics'                              => 'Optics',
		'other_other'                         => 'Other',
		'photo_accessories'                   => 'Photo Accessories',
		'plumbing_and_hvac'                   => 'Plumbing & HVAC',
		'printers_scanners_and_imaging'       => 'Printers, Scanners, & Imaging',
		'safety_and_emergency'                => 'Safety & Emergency',
		'software'                            => 'Software',
		'sound_and_recording'                 => 'Sound & Recording',
		'sport_and_recreation_other'          => 'Sport & Recreation Other',
		'storage'                             => 'Storage',
		'tv_shows'                            => 'TV Shows',
		'tvs_and_video_displays'              => 'TVs & Video Displays',
		'tires'                               => 'Tires',
		'tools_and_hardware_other'            => 'Tools & Hardware Other',
		'tools'                               => 'Tools',
		'toys_other'                          => 'Toys',
		'vehicle_other'                       => 'Vehicle Other',
		'vehicle_parts_and_accessories'       => 'Vehicle Parts & Accessories',
		'video_games'                         => 'Video Games',
		'video_projectors'                    => 'Video Projectors',
		'watches_other'                       => 'Watches',
		'watercraft'                          => 'Watercraft',
		'wheels_and_wheel_components'         => 'Wheels & Wheel Components',
	);

	$subCategory = '';
	foreach ( $categories as $key => $value ) {
		if ( $value == $category ) {
			$subCategory = $key;
			break;
		}
	}

	return $subCategory;
}


function ced_walmart_get_current_active_store() {
	$store_id = isset( $_GET['store_id'] ) ? sanitize_text_field( $_GET['store_id'] ) : get_option( 'ced_walmart_active_store', '' );
	return $store_id;
}

function ced_walmart_get_store_name_by_id( $store_id = '' ) {

	$connected_accounts = ced_walmart_return_partner_detail_option();
	$store_name         = isset( $connected_accounts[ $store_id ]['store_name'] ) ? esc_attr( $connected_accounts[ $store_id ]['store_name'] ) : esc_attr( $store_id );

	return $store_name;
}

if ( ! function_exists( 'ced_get_navigation_url' ) ) {
	function ced_get_navigation_url( $channel = 'home', $query_args = array() ) {
		if ( ! empty( $query_args ) ) {
			return admin_url( 'admin.php?page=sales_channel&channel=' . $channel . '&' . http_build_query( $query_args ) );
		}
		return admin_url( 'admin.php?page=sales_channel&channel=' . $channel );
	}
}


function ced_walmart_categories_tree( $value, $cat_name ) {
	if ( 0 != $value->parent ) {
		$parent_id = $value->parent;
		$sbcatch2  = get_term( $parent_id );
		$cat_name  = $sbcatch2->name . ' --> ' . $cat_name;
		if ( 0 != $sbcatch2->parent ) {
			$cat_name = ced_walmart_categories_tree( $sbcatch2, $cat_name );
		}
	}
	return $cat_name;
}




function valueRegionArray() {
	$regionArray = array(
		'regions' =>
		array(
			'48_state' => array(
				array(
					'label'      => '48 State',
					'regionName' => '48 State',
					'regionCode' => 'C',
					'subRegions' => array(
						array(
							'label'         => 'North East',
							'subRegionCode' => 'NE',
							'subRegionName' => 'NE',
							'states'        => array(
								'NY' => array(
									'label'            => 'New York',
									'value'            => 'NY',
									'state_sub_region' => array(
										array(
											'label' => 'NY_BROOKLYN',
											'value' => 'NY1',
										),
										array(
											'label' => 'NY_CENTRAL',
											'value' => 'NY2',
										),
										array(
											'label' => 'NY_NORTH_CENTRAL',
											'value' => 'NY3',
										),
										array(
											'label' => 'NY_NORTH_WEST',
											'value' => 'NY4',
										),
										array(
											'label' => 'NY_SOUTH',
											'value' => 'NY5',
										),
									),
								),
								'CT' => array(
									'label'            => 'Connecticut',
									'value'            => 'CT',
									'state_sub_region' => array(
										array(
											'label' => 'CT_SOUTH_WEST',
											'value' => 'CT2',
										),
										array(
											'label' => 'CT_REST_ALL',
											'value' => 'CT1',
										),
									),
								),
								'VT' => array(
									'label'            => 'Vermont',
									'value'            => 'VT',
									'state_sub_region' => array(
										array(
											'label' => 'VT',
											'value' => 'VT',
										),
									),
								),
								'ME' => array(
									'label'            => 'Maine',
									'value'            => 'ME',
									'state_sub_region' => array(
										array(
											'label' => 'ME_EAST',
											'value' => 'ME1',
										),
										array(
											'label' => 'ME_WEST',
											'value' => 'ME2',
										),
									),
								),
								'NH' => array(
									'label'            => 'New Hampshire',
									'value'            => 'NH',
									'state_sub_region' => array(
										array(
											'label' => 'NH',
											'value' => 'NH',
										),
									),
								),
								'MA' => array(
									'label'            => 'Massachusetts',
									'value'            => 'MA',
									'state_sub_region' => array(
										array(
											'label' => 'MA_EAST',
											'value' => 'MA1',
										),
										array(
											'label' => 'MA_WEST',
											'value' => 'MA2',
										),
									),
								),
								'PA' => array(
									'label'            => 'Pennsylvania',
									'value'            => 'PA',
									'state_sub_region' => array(
										array(
											'label' => 'PA_CENTRAL',
											'value' => 'PA1',
										),
										array(
											'label' => 'PA_CENTRAL_NORTH',
											'value' => 'PA2',
										),
										array(
											'label' => 'PA_NORTH_EAST',
											'value' => 'PA3',
										),
										array(
											'label' => 'PA_SOUTH',
											'value' => 'PA4',
										),
										array(
											'label' => 'PA_WEST',
											'value' => 'PA5',
										),
									),
								),
								'NJ' => array(
									'label'            => 'New Jersey',
									'value'            => 'NJ',
									'state_sub_region' => array(
										array(
											'label' => 'NJ_CENTRAL',
											'value' => 'NJ1',
										),
										array(
											'label' => 'NJ_NORTH',
											'value' => 'NJ2',
										),
										array(
											'label' => 'NJ_SOUTH',
											'value' => 'NJ3',
										),
										array(
											'label' => 'NJ_KEARNY',
											'value' => 'NJ4',
										),
									),
								),
								'RI' => array(
									'label'            => 'Rhode Island',
									'value'            => 'RI',
									'state_sub_region' => array(
										array(
											'label' => 'RI',
											'value' => 'RI',
										),
									),
								),
							),
						),
						array(
							'label'         => 'Mid West',
							'subRegionCode' => 'MW',
							'subRegionName' => 'MW',
							'states'        => array(
								'IN' => array(
									'label'            => 'Indiana',
									'value'            => 'IN',
									'state_sub_region' => array(
										array(
											'label' => 'IN_CENTRAL_AND_EAST',
											'value' => 'IN1',
										),
										array(
											'label' => 'IN_NORTH',
											'value' => 'IN2',
										),
										array(
											'label' => 'IN_SOUTH',
											'value' => 'IN3',
										),
										array(
											'label' => 'IN_WEST',
											'value' => 'IN4',
										),
									),
								),
								'NE' => array(
									'label'            => 'Nebraska',
									'value'            => 'NE',
									'state_sub_region' => array(
										array(
											'label' => 'NE',
											'value' => 'NE',
										),
									),
								),
								'ND' => array(
									'label'            => 'North Dakota',
									'value'            => 'ND',
									'state_sub_region' => array(
										array(
											'label' => 'ND',
											'value' => 'ND',
										),
									),
								),
								'OH' => array(
									'label'            => 'Ohio',
									'value'            => 'OH',
									'state_sub_region' => array(
										array(
											'label' => 'OH_CENTRAL',
											'value' => 'OH1',
										),
										array(
											'label' => 'OH_NORTH',
											'value' => 'OH2',
										),
										array(
											'label' => 'OH_SOUTH_EAST',
											'value' => 'OH3',
										),
										array(
											'label' => 'OH_WEST',
											'value' => 'OH4',
										),
									),
								),
								'MN' => array(
									'label'            => 'Minnesota',
									'value'            => 'MN',
									'state_sub_region' => array(
										array(
											'label' => 'MN_CENTRAL_EAST',
											'value' => 'MN1',
										),
										array(
											'label' => 'MN_REST_ALL',
											'value' => 'MN2',
										),
									),
								),
								'KS' => array(
									'label'            => 'Kansas',
									'value'            => 'KS',
									'state_sub_region' => array(
										array(
											'label' => 'KS_EAST',
											'value' => 'KS1',
										),
										array(
											'label' => 'KS_WEST',
											'value' => 'KS2',
										),
									),
								),
								'WI' => array(
									'label'            => 'Wisconsin',
									'value'            => 'WI',
									'state_sub_region' => array(
										array(
											'label' => 'WI_EAST',
											'value' => 'WI1',
										),
										array(
											'label' => 'WI_WEST',
											'value' => 'WI2',
										),
									),
								),
								'IL' => array(
									'label'            => 'Illinois',
									'value'            => 'IL',
									'state_sub_region' => array(
										array(
											'label' => 'IL_CHICAGO',
											'value' => 'IL1',
										),
										array(
											'label' => 'IL_NORTH_EAST',
											'value' => 'IL2',
										),
										array(
											'label' => 'IL_NORTH_WEST',
											'value' => 'IL3',
										),
										array(
											'label' => 'IL_SOUTHEAST',
											'value' => 'IL4',
										),
										array(
											'label' => 'IL_SOUTHWEST',
											'value' => 'IL5',
										),
									),
								),
								'MO' => array(
									'label'            => 'Missouri',
									'value'            => 'MO',
									'state_sub_region' => array(
										array(
											'label' => 'MO_EAST',
											'value' => 'MO1',
										),
										array(
											'label' => 'MO_SOUTH',
											'value' => 'MO2',
										),
										array(
											'label' => 'MO_WEST',
											'value' => 'MO3',
										),
									),
								),
								'IA' => array(
									'label'            => 'Iowa',
									'value'            => 'IA',
									'state_sub_region' => array(
										array(
											'label' => 'IA_CENTRAL',
											'value' => 'IA1',
										),
										array(
											'label' => 'IA_REST',
											'value' => 'IA2',
										),
									),
								),
								'SD' => array(
									'label'            => 'South Dakota',
									'value'            => 'SD',
									'state_sub_region' => array(
										array(
											'label' => 'SD_EAST',
											'value' => 'SD1',
										),
										array(
											'label' => 'SD_WEST',
											'value' => 'SD2',
										),
									),
								),
								'MI' => array(
									'label'            => 'Michigan',
									'value'            => 'MI',
									'state_sub_region' => array(
										array(
											'label' => 'MI_CENTRAL',
											'value' => 'MI1',
										),
										array(
											'label' => 'MI_NORTH',
											'value' => 'MI2',
										),
										array(
											'label' => 'MI_SOUTH_EAST',
											'value' => 'MI3',
										),
									),
								),
							),
						),
						array(
							'label'         => 'South',
							'subRegionCode' => 'SO',
							'subRegionName' => 'SO',
							'states'        => array(
								'DE' => array(
									'label'            => 'Delaware',
									'value'            => 'DE',
									'state_sub_region' => array(
										array(
											'label' => 'DE',
											'value' => 'DE',
										),
									),
								),
								'TX' => array(
									'label'            => 'Texas',
									'value'            => 'TX',
									'state_sub_region' => array(
										array(
											'label' => 'TX_NORTH_EAST',
											'value' => 'TX6',
										),
										array(
											'label' => 'TX_HOUSTON',
											'value' => 'TX5',
										),
										array(
											'label' => 'TX_SOUTH',
											'value' => 'TX8',
										),
										array(
											'label' => 'TX_NORTH_WEST',
											'value' => 'TX7',
										),
										array(
											'label' => 'TX_SOUTH_EAST',
											'value' => 'TX9',
										),
										array(
											'label' => 'TX_SOUTH_WEST',
											'value' => 'TX10',
										),
										array(
											'label' => 'TX_CENTRAL_NORTH',
											'value' => 'TX2',
										),
										array(
											'label' => 'TX_CENTRAL',
											'value' => 'TX1',
										),
										array(
											'label' => 'TX_DALLAS',
											'value' => 'TX4',
										),
										array(
											'label' => 'TX_CENTRAL_WEST',
											'value' => 'TX3',
										),

									),
								),
								'FL' => array(
									'label'            => 'Florida',
									'value'            => 'FL',
									'state_sub_region' => array(
										array(
											'label' => 'FL_CENTRAL_WEST',
											'value' => 'FL2',
										),
										array(
											'label' => 'FL_CENTRAL_EAST',
											'value' => 'FL1',
										),
										array(
											'label' => 'FL_NORTH',
											'value' => 'FL4',
										),
										array(
											'label' => 'FL_MIAMI',
											'value' => 'FL3',
										),
										array(
											'label' => 'FL_SOUTH_WEST',
											'value' => 'FL6',
										),
										array(
											'label' => 'FL_SOUTH_EAST',
											'value' => 'FL5',
										),
									),
								),
								'MS' => array(
									'label'            => 'Mississippi',
									'value'            => 'MS',
									'state_sub_region' => array(
										array(
											'label' => 'MS_SOUTH',
											'value' => 'MS2',
										),
										array(
											'label' => 'MS_NORTH',
											'value' => 'MS1',
										),

									),
								),
								'AL' => array(
									'label'            => 'Alabama',
									'value'            => 'AL',
									'state_sub_region' => array(
										array(
											'label' => 'AL_NORTH',
											'value' => 'AL1',
										),
										array(
											'label' => 'AL_SOUTH',
											'value' => 'AL2',
										),
									),
								),
								'VA' => array(
									'label'            => 'Virginia',
									'value'            => 'VA',
									'state_sub_region' => array(
										array(
											'label' => 'VA_CENTRAL',
											'value' => 'VA1',
										),
										array(
											'label' => 'VA_SOUTH_EAST',
											'value' => 'VA3',
										),
										array(
											'label' => 'VA_NORTH',
											'value' => 'VA2',
										),
										array(
											'label' => 'VA_SOUTH_WEST',
											'value' => 'VA4',
										),
									),
								),
								'KY' => array(
									'label'            => 'Kentucky',
									'value'            => 'KY',
									'state_sub_region' => array(
										array(
											'label' => 'KY_WEST',
											'value' => 'KY2',
										),
										array(
											'label' => 'KY_EAST',
											'value' => 'KY1',
										),
									),
								),
								'SC' => array(
									'label'            => 'South Carolina',
									'value'            => 'SC',
									'state_sub_region' => array(
										array(
											'label' => 'SC_NORTH',
											'value' => 'SC2',
										),
										array(
											'label' => 'SC_CENTRAL',
											'value' => 'SC1',
										),
										array(
											'label' => 'SC_SOUTH',
											'value' => 'SC3',
										),
									),
								),
								'AR' => array(
									'label'            => 'Arkansas',
									'value'            => 'AR',
									'state_sub_region' => array(
										array(
											'label' => 'AR_WEST',
											'value' => 'AR2',
										),
										array(
											'label' => 'AR_EAST',
											'value' => 'AR1',
										),
									),
								),
								'LA' => array(
									'label'            => 'Louisiana',
									'value'            => 'LA',
									'state_sub_region' => array(
										array(
											'label' => 'LA_NORTH',
											'value' => 'LA1',
										),
										array(
											'label' => 'LA_SOUTH',
											'value' => 'LA2',
										),
									),
								),
								'NC' => array(
									'label'            => 'North Carolina',
									'value'            => 'NC',
									'state_sub_region' => array(
										array(
											'label' => 'NC_EAST',
											'value' => 'NC1',
										),
										array(
											'label' => 'NC_SOUTH',
											'value' => 'NC3',
										),
										array(
											'label' => 'NC_NORTH',
											'value' => 'NC2',
										),
										array(
											'label' => 'NC_WEST',
											'value' => 'NC4',
										),

									),
								),
								'MD' => array(
									'label'            => 'Maryland',
									'value'            => 'MD',
									'state_sub_region' => array(
										array(
											'label' => 'MD_CENTRAL',
											'value' => 'MD1',
										),
										array(
											'label' => 'MD_REST_ALL',
											'value' => 'MD2',
										),
									),
								),

								'GA' => array(
									'label'            => 'Georgia',
									'value'            => 'GA',
									'state_sub_region' => array(
										array(
											'label' => 'GA_CENTRAL',
											'value' => 'GA2',
										),
										array(
											'label' => 'GA_ATLANTA',
											'value' => 'GA1',
										),
										array(
											'label' => 'GA_NORTH_WEST',
											'value' => 'GA4',
										),
										array(
											'label' => 'GA_NORTH_EAST',
											'value' => 'GA3',
										),
										array(
											'label' => 'GA_SOUTH',
											'value' => 'GA5',
										),

									),
								),

								'TN' => array(
									'label'            => 'Tennessee',
									'value'            => 'TN',
									'state_sub_region' => array(
										array(
											'label' => 'TN_EAST',
											'value' => 'TN2',
										),
										array(
											'label' => 'TN_CENTRAL',
											'value' => 'TN1',
										),
										array(
											'label' => 'TN_WEST',
											'value' => 'TN3',
										),

									),
								),
								'OK' => array(
									'label'            => 'Oklahoma',
									'value'            => 'OK',
									'state_sub_region' => array(
										array(
											'label' => 'OK_REST_ALL',
											'value' => 'OK2',
										),
										array(
											'label' => 'OK_NORTH_EAST',
											'value' => 'OK1',
										),

									),
								),
								'WV' => array(
									'label'            => 'West Virginia',
									'value'            => 'WV',
									'state_sub_region' => array(
										array(
											'label' => 'WV_EAST',
											'value' => 'WV1',
										),
										array(
											'label' => 'WV_WEST',
											'value' => 'WV2',
										),

									),
								),
								'DC' => array(
									'label'            => 'District of Columbia',
									'value'            => 'DC',
									'state_sub_region' => array(
										array(
											'label' => 'DC',
											'value' => 'DC',
										),

									),
								),
							),
						),
						array(
							'label'         => 'West',
							'subRegionCode' => 'WE',
							'subRegionName' => 'WE',
							'states'        => array(
								'CA' => array(
									'label'            => 'California',
									'value'            => 'CA',
									'state_sub_region' => array(
										array(
											'label' => 'CA_CENTRAL',
											'value' => 'CA1',
										),
										array(
											'label' => 'CA_CENTRAL_NORTH',
											'value' => 'CA2',
										),
										array(
											'label' => 'CA_CENTRAL_SOUTH',
											'value' => 'CA3',
										),
										array(
											'label' => 'CA_CENTRAL_WEST',
											'value' => 'CA4',
										),
										array(
											'label' => 'CA_LONG_BEACH',
											'value' => 'CA5',
										),
										array(
											'label' => 'CA_LOS_ANGELES',
											'value' => 'CA6',
										),
										array(
											'label' => 'CA_LOS_ANGELES_VE',
											'value' => 'CA7',
										),
										array(
											'label' => 'CA_NORTH',
											'value' => 'CA8',
										),
										array(
											'label' => 'CA_ONTARIO',
											'value' => 'CA9',
										),
										array(
											'label' => 'CA_RIVERSIDE_IMPE',
											'value' => 'CA10',
										),
										array(
											'label' => 'CA_SAN_BERNARDIN',
											'value' => 'CA11',
										),
										array(
											'label' => 'CA_SAN_DIEGO',
											'value' => 'CA12',
										),
										array(
											'label' => 'CA_SAN_DIEGO_COUN',
											'value' => 'CA13',
										),
										array(
											'label' => 'CA_SAN_FRANCISCO',
											'value' => 'CA14',
										),
										array(
											'label' => 'CA_SANTA_ANA',
											'value' => 'CA15',
										),
									),
								),
								'WA' => array(
									'label'            => 'Washington',
									'value'            => 'WA',
									'state_sub_region' => array(
										array(
											'label' => 'WA',
											'value' => 'WA',
										),
									),
								),
								'UT' => array(
									'label'            => 'Utah',
									'value'            => 'UT',
									'state_sub_region' => array(
										array(
											'label' => 'UT_NORTH',
											'value' => 'UT1',
										),
										array(
											'label' => 'UT_SOUTH',
											'value' => 'UT2',
										),
									),
								),
								'MT' => array(
									'label'            => 'Montana',
									'value'            => 'MT',
									'state_sub_region' => array(
										array(
											'label' => 'MT',
											'value' => 'MT',
										),
									),
								),
								'AZ' => array(
									'label'            => 'Arizona',
									'value'            => 'AZ',
									'state_sub_region' => array(
										array(
											'label' => 'AZ_NORTH',
											'value' => 'AZ1',
										),
										array(
											'label' => 'AZ_PHOENIX',
											'value' => 'AZ2',
										),
										array(
											'label' => 'AZ_SOUTH',
											'value' => 'AZ3',
										),
									),
								),
								'OR' => array(
									'label'            => 'Oregon',
									'value'            => 'OR',
									'state_sub_region' => array(
										array(
											'label' => 'OR_CENTRAL',
											'value' => 'OR1',
										),
										array(
											'label' => 'OR_NORTH_WEST',
											'value' => 'OR2',
										),
									),
								),
								'NM' => array(
									'label'            => 'New Mexico',
									'value'            => 'NM',
									'state_sub_region' => array(
										array(
											'label' => 'NM_CENTRAL',
											'value' => 'NM1',
										),
										array(
											'label' => 'NM_REST_ALL',
											'value' => 'NM2',
										),
									),
								),
								'WY' => array(
									'label'            => 'Wyoming',
									'value'            => 'WY',
									'state_sub_region' => array(
										array(
											'label' => 'WY',
											'value' => 'WY',
										),
									),
								),
								'CO' => array(
									'label'            => 'Colorado',
									'value'            => 'CO',
									'state_sub_region' => array(
										array(
											'label' => 'CO_CENTRAL',
											'value' => 'CO1',
										),
										array(
											'label' => 'CO_REST_ALL',
											'value' => 'CO2',
										),
									),
								),
								'ID' => array(
									'label'            => 'Idaho',
									'value'            => 'ID',
									'state_sub_region' => array(
										array(
											'label' => 'ID_BOISE_AND_NAMP',
											'value' => 'ID1',
										),
										array(
											'label' => 'ID_NORTH_AND_EAS',
											'value' => 'ID2',
										),
									),
								),
								'NV' => array(
									'label'            => 'Nevada',
									'value'            => 'NV',
									'state_sub_region' => array(
										array(
											'label' => 'NV_LAS_VEGAS',
											'value' => 'NV1',
										),
										array(
											'label' => 'NV_NORTH',
											'value' => 'NV2',
										),
										array(
											'label' => 'NV_SOUTH',
											'value' => 'NV3',
										),
									),
								),
							),
						),

					),
				),
			),
		),
	);
	return $regionArray;
}

function valueRegionNames() {
	$allStates = array(
		'NY' => 'New York',
		'CT' => 'Connecticut',
		'VT' => 'Vermont',
		'ME' => 'Maine',
		'NH' => 'New Hampshire',
		'MA' => 'Massachusetts',
		'PA' => 'Pennsylvania',
		'NJ' => 'New Jersey',
		'RI' => 'Rhode Island',
		'IN' => 'Indiana',
		'NE' => 'Nebraska',
		'ND' => 'North Dakota',
		'OH' => 'Ohio',
		'MN' => 'Minnesota',
		'KS' => 'Kansas',
		'WI' => 'Wisconsin',
		'IL' => 'Illinois',
		'MO' => 'Missouri',
		'IA' => 'Iowa',
		'SD' => 'South Dakota',
		'MI' => 'Michigan',
		'AR' => 'Arkansas',
		'NC' => 'North Carolina',
		'GA' => 'Georgia',
		'SC' => 'South Carolina',
		'DC' => 'District of Columbia',
		'AL' => 'Alabama',
		'WV' => 'West Virginia',
		'LA' => 'Louisiana',
		'DE' => 'Delaware',
		'TX' => 'Texas',
		'TN' => 'Tennessee',
		'MS' => 'Mississippi',
		'VA' => 'Virginia',
		'KY' => 'Kentucky',
		'OK' => 'Oklahoma',
		'MD' => 'Maryland',
		'FL' => 'Florida',
		'CA' => 'California',
		'WA' => 'Washington',
		'UT' => 'Utah',
		'MT' => 'Montana',
		'AZ' => 'Arizona',
		'OR' => 'Oregon',
		'NM' => 'New Mexico',
		'WY' => 'Wyoming',
		'CO' => 'Colorado',
		'ID' => 'Idaho',
		'NV' => 'Nevada',

	);
	return $allStates;
}





