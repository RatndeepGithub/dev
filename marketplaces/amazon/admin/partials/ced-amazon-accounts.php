<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( isset( $_GET['part'] ) ) {

	$allowed_parts = array(
		'wizard-options',
		'configuration',
		'wizard-settings',

	);

	$part = isset( $_GET['part'] ) ? sanitize_text_field( $_GET['part'] ) : '';

	if ( in_array( $part, $allowed_parts ) ) {

		switch ($part) {
			case 'wizard-options':
				if ( file_exists( CED_AMAZON_DIRPATH . 'admin/partials/wizard-options.php' ) ) {
					require_once CED_AMAZON_DIRPATH . 'admin/partials/wizard-options.php';
				}
				break;
			case 'configuration':
				if ( file_exists( CED_AMAZON_DIRPATH . 'admin/partials/configuration.php' ) ) {
					require_once CED_AMAZON_DIRPATH . 'admin/partials/configuration.php';
				}
				break;
			case 'wizard-settings':
				if ( file_exists( CED_AMAZON_DIRPATH . 'admin/partials/wizard-settings.php' ) ) {
					require_once CED_AMAZON_DIRPATH . 'admin/partials/wizard-settings.php';
				}
				break;
			default:
			  echo '';
		}
		
		wp_die();
	}
}



$part    = isset( $_GET['part'] ) ? sanitize_text_field( $_GET['part'] ) : '';
$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';


$ced_amazon_sellernext_shop_ids = get_option( 'ced_amazon_sellernext_shop_ids', array() );

$contract_data = get_option( 'ced_unified_contract_details', array() );
$contract_id   = isset( $contract_data['amazon'] ) && isset( $contract_data['amazon']['contract_id'] ) ? $contract_data['amazon']['contract_id'] : '';


$details    = get_details($contract_id);
$planstatus = isset( $details['plan_status'] ) ? $details['plan_status'] : '';
$end_date   = isset( $details['end_date'] ) ? $details['end_date'] : '';


$subscriptionVerified = 1;


if ( ! $subscriptionVerified ) {

	$pricing_url = add_query_arg(
		array(
			'page'    => 'sales_channel',
			'channel' => 'pricing',

		),
		admin_url() . 'admin.php'
	);

	wp_safe_redirect( $pricing_url );


} elseif ( isset( $_GET['section'] ) ) {

	$allowed_sections = array(
		'overview',
		'orders-view',
		'templates-view',
		'products-view',
		'feeds-view',
		'feed-view',
		'settings',
		'amazon-options',
		'plans-view',
		'add-new-template',
		'setup-amazon',
	);

	$section = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : '';

	if ( in_array( $section, $allowed_sections ) ) {

		switch ($section) {
			case 'overview':
				if ( file_exists( CED_AMAZON_DIRPATH . 'admin/partials/overview.php' ) ) {
					require_once CED_AMAZON_DIRPATH . 'admin/partials/overview.php';
				}
				break;
			case 'orders-view':
				if ( file_exists( CED_AMAZON_DIRPATH . 'admin/partials/orders-view.php' ) ) {
					require_once CED_AMAZON_DIRPATH . 'admin/partials/orders-view.php';
				}
				break;
			case 'templates-view':
				if ( file_exists( CED_AMAZON_DIRPATH . 'admin/partials/templates-view.php' ) ) {
					require_once CED_AMAZON_DIRPATH . 'admin/partials/templates-view.php';
				}
				break;

			
			case 'products-view':
				if ( file_exists( CED_AMAZON_DIRPATH . 'admin/partials/products-view.php' ) ) {
					require_once CED_AMAZON_DIRPATH . 'admin/partials/products-view.php';
				}
				break;
			case 'feeds-view':
				if ( file_exists( CED_AMAZON_DIRPATH . 'admin/partials/feeds-view.php' ) ) {
					require_once CED_AMAZON_DIRPATH . 'admin/partials/feeds-view.php';
				}
				break;
			case 'feed-view':
				if ( file_exists( CED_AMAZON_DIRPATH . 'admin/partials/feed-view.php' ) ) {
					require_once CED_AMAZON_DIRPATH . 'admin/partials/feed-view.php';
				}
				break;  

			 
			case 'settings':
				if ( file_exists( CED_AMAZON_DIRPATH . 'admin/partials/settings.php' ) ) {
					require_once CED_AMAZON_DIRPATH . 'admin/partials/settings.php';
				}
				break;
			case 'amazon-options':
				if ( file_exists( CED_AMAZON_DIRPATH . 'admin/partials/amazon-options.php' ) ) {
					require_once CED_AMAZON_DIRPATH . 'admin/partials/amazon-options.php';
				}
				break;
			case 'plans-view':
				if ( file_exists( CED_AMAZON_DIRPATH . 'admin/partials/plans-view.php' ) ) {
					require_once CED_AMAZON_DIRPATH . 'admin/partials/plans-view.php';
				}
				break;    

			  
			case 'add-new-template':
				if ( file_exists( CED_AMAZON_DIRPATH . 'admin/partials/add-new-template.php' ) ) {
					require_once CED_AMAZON_DIRPATH . 'admin/partials/add-new-template.php';
				}
				break;
			case 'setup-amazon':
				if ( file_exists( CED_AMAZON_DIRPATH . 'admin/partials/setup-amazon.php' ) ) {
					require_once CED_AMAZON_DIRPATH . 'admin/partials/setup-amazon.php';
				}
				break;    


			default:
				if ( file_exists( CED_AMAZON_DIRPATH . 'admin/partials/overview.php' ) ) {
					require_once CED_AMAZON_DIRPATH . 'admin/partials/overview.php';
				}
		}
	}

} else {

	if ( ! session_id() ) {
		session_start();
	}
	$create_user_response = get_option( 'ced_amazon_sellernext_user_creation_response', array() );
	$user_name            = isset( $create_user_response['email'] ) ? $create_user_response['email'] : 'User';
	$sellernextShopIds    = get_option( 'ced_amazon_sellernext_shop_ids', array() );
	$current_step         = isset( $sellernextShopIds[ $user_id ] ) && isset( $sellernextShopIds[ $user_id ]['ced_amz_current_step'] ) ? $sellernextShopIds[ $user_id ]['ced_amz_current_step'] : '';


	?>


	<div class="ced-amazon-login-dashboard-wrapper">
		<div class="ced-amazon-login-dashboard">
			<div class="ced-amazon-wrap-login">
				<div class="ced-amazon-common-wrap-head">
					<h1><?php echo esc_html__( 'Amazon for WooCommerce', 'amazon-for-woocommerce' ); ?></h1>
				</div>
				<div class="ced-amazon-user-container">
					<div class="ced-amazon-user-holder">

						<?php

						if ( ! empty( $ced_amazon_sellernext_shop_ids ) && is_array( $ced_amazon_sellernext_shop_ids ) ) {

							$ced_amz_active_marketplace = get_option( 'ced_amz_active_marketplace' );
							$current_shop               = array();

							if ( ! empty( $ced_amz_active_marketplace ) ) {

								$keys   = array_keys( $ced_amazon_sellernext_shop_ids );
								$values = array_values( $ced_amazon_sellernext_shop_ids );

								$user_id  = isset( $ced_amz_active_marketplace['user_id'] ) ? $ced_amz_active_marketplace['user_id'] : '';
								$sellerID = isset( $ced_amazon_sellernext_shop_ids[ $user_id ] ) && isset( $ced_amazon_sellernext_shop_ids[ $user_id ]['ced_mp_seller_key'] ) ? $ced_amazon_sellernext_shop_ids[ $user_id ]['ced_mp_seller_key'] : '';

								$current_shop = array(
									'user_id'   => $user_id,
									'seller_id' => str_replace( '|', '%7C', $sellerID ),

								);

							} else {

								$lastIndex = count( $ced_amazon_sellernext_shop_ids ) - 1;

								$keys     = array_keys( $ced_amazon_sellernext_shop_ids );
								$values   = array_values( $ced_amazon_sellernext_shop_ids );
								$sellerID = str_replace( '|', '%7C', $values[ $lastIndex ]['ced_mp_seller_key'] );

								$current_shop = array(
									'user_id'   => $keys[ $lastIndex ],
									'seller_id' => $sellerID,
								);

							}


							if ( 3 < $ced_amazon_sellernext_shop_ids[ $current_shop['user_id'] ]['ced_amz_current_step'] ) {

								$url = add_query_arg(
									array(
										'page'      => 'sales_channel',
										'channel'   => 'amazon',
										'section'   => 'overview',
										'user_id'   => $current_shop['user_id'],
										'seller_id' => $current_shop['seller_id'],


									),
									admin_url() . 'admin.php'
								);

							} else {

								$current_step = $ced_amazon_sellernext_shop_ids[ $current_shop['user_id'] ]['ced_amz_current_step'];
								if ( empty( $current_step ) ) {
									$part = 'setup-amazon';
								} elseif ( 1 == $current_step ) {
									$part = 'wizard-options';
								} elseif ( 2 == $current_step ) {
									$part = 'wizard-settings';
								} elseif ( 3 == $current_step ) {
									$part = 'sconfiguration';
								}

								$url = add_query_arg(
									array(
										'page'      => 'sales_channel',
										'channel'   => 'amazon',
										'section'   => 'setup-amazon',
										'part'      => $part,
										'user_id'   => $current_shop['user_id'],
										'seller_id' => $current_shop['seller_id'],


									),
									admin_url() . 'admin.php'
								);
							}

							wp_safe_redirect( $url );


						} else {

							$url = add_query_arg(
								array(
									'page'    => 'sales_channel',
									'channel' => 'amazon',
									'section' => 'setup-amazon',

								),
								admin_url() . 'admin.php'
							);

							wp_safe_redirect( $url );
							exit();

						}
						?>


					</div>
				</div>
			</div>
		</div>
	</div>

	<?php

}
?>
