<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$configFile = CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayConfig.php';
if(file_exists( $configFile )) {
	require_once $configFile;
}
$site_id = isset( $_GET['sid'] ) ? sanitize_text_field( $_GET['sid'] ) : '';
$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : false;
$rsid = isset( $_GET['rsid'] ) ? sanitize_text_field( $_GET['rsid'] ) : false;



if ( isset( $_GET['section'] ) ) {
	$section = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : '';
}

?>


<div class="ced-menu-container">
	<ul class="subsubsub">
		<li><a href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=ebay&section=overview&user_id=' . $user_id . '&sid=' . $site_id . '&rsid=' . $rsid ) ); ?>"
				class="
								<?php
								if ( 'overview' == $section ) {
									echo 'current';
								}
								?>
					">Overview</a> |</li>
					<li><a href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=ebay&section=settings&user_id=' . $user_id . '&sid=' . $site_id . '&rsid=' . $rsid ) ); ?>"
				class="
								<?php
								if ( 'settings' == $section || 'view-description-templates' == $section || 'description-template' == $section ) {
									echo 'current';
								}
								?>
					"><?php esc_attr_e( 'Settings', 'ebay-integration-for-woocommerce' ); ?></a>|</li>
					<li><a href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=ebay&section=view-templates&user_id=' . $user_id . '&sid=' . $site_id . '&rsid=' . $rsid ) ); ?>"
				class="
								<?php
								if ( 'view-templates' == $section || 'product-template' == $section ) {
									echo 'current';
								}
								?>
					"><?php esc_attr_e( 'Templates', 'ebay-integration-for-woocommerce' ); ?></a> |</li>
		
	
		<li><a href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=ebay&section=products-view&user_id=' . $user_id . '&sid=' . $site_id . '&rsid=' . $rsid ) ); ?>"
				class="
								<?php
								if ( 'products-view' == $section ) {
									echo 'current';
								}
								?>
					"><?php esc_attr_e( 'Products', 'ebay-integration-for-woocommerce' ); ?></a> |</li>
					<li><a href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=ebay&section=view-ebay-orders&user_id=' . $user_id . '&sid=' . $site_id . '&rsid=' . $rsid ) ); ?>"
				class="
								<?php
								if ( 'view-ebay-orders' == $section ) {
									echo 'current';
								}
								?>
		"><?php esc_attr_e( 'Orders', 'ebay-integration-for-woocommerce' ); ?></a> |</li>
		<li><a href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=ebay&section=feeds-view&user_id=' . $user_id . '&sid=' . $site_id . '&rsid=' . $rsid ) ); ?>"
				class="
								<?php
								if ( 'feeds-view' == $section || 'feed-view' == $section ) {
									echo 'current';
								}
								?>
					"><?php esc_attr_e( 'Feeds', 'ebay-integration-for-woocommerce' ); ?></a></li>
		
		

	</ul>

	<div class="ced-right">
		<?php
		$connected_ebay_accounts = ! empty( get_option( 'ced_ebay_connected_accounts' ) ) ? get_option( 'ced_ebay_connected_accounts', true ) : array();
		$current_active_section  = isset( $_GET['section'] ) ? sanitize_text_field( filter_input( INPUT_GET, 'section', FILTER_UNSAFE_RAW ) ) : 'overview';
		if ( 'description-template' == $current_active_section ) {
			$current_active_section = 'view-description-templates';
		}
		if ( ! empty( $connected_ebay_accounts ) ) {
			?>
			<select style="min-width: 160px;" id="media-attachment-filters" name="ced_ebay_change_acc"
				class="attachment-filters ced_ebay_change_acc">
				<?php
				foreach ( $connected_ebay_accounts as $key => $ebay_sites ) {
					$ebay_user_id = $key;
					if ( ! empty( $ebay_user_id ) && is_array( $ebay_sites ) ) {
						foreach ( $ebay_sites as $ebay_site => $connection_status ) {



							
							$remote_shop_id = $connection_status['remote_shop_id'];

							$selected = '';
							if ( $user_id == $ebay_user_id && $ebay_site == $site_id ) {
								$selected = 'selected';
							}
							$site_details = ced_ebay_get_site_details( $ebay_site );
							$site_name    = isset( $site_details['name'] ) ? $site_details['name'] : '';
							if ( empty( $site_name ) ) {
								continue;
							}

							if ( 1 < (int) $connected_ebay_accounts[ $ebay_user_id ][ $ebay_site ]['ced_ebay_current_step'] ) {
								$url = add_query_arg(
									array(
										'page'    => 'sales_channel',
										'channel' => 'ebay',
										'section' => $current_active_section,
										'user_id' => $ebay_user_id,
										'sid' => $ebay_site,
										'rsid' => $remote_shop_id
									),
									admin_url() . 'admin.php'
								);
								?>

						<option value="all" <?php echo esc_attr( $selected ); ?> data-href="<?php echo esc_url( $url ); ?>"><?php echo esc_attr( $ebay_user_id . ' (' . $site_name . ')' ); ?></option>


								<?php
							} else {

								$current_step = (int) $connected_ebay_accounts[ $ebay_user_id ][ $ebay_site ]['ced_ebay_current_step'];
								if ( false === $current_step ) {
									$urlKey = 'section=setup-ebay&add-new-account=yes';
								} elseif ( 0 == $current_step ) {
									$urlKey = 'section=onboarding-global-options&user_id=' . $ebay_user_id . '&sid=' . $ebay_site;
								} elseif ( 1 == $current_step ) {
									$urlKey = 'section=onboarding-general-settings&user_id=' . $ebay_user_id . '&sid=' . $ebay_site;
								}
								$visit_url = get_admin_url() . 'admin.php?page=sales_channel&channel=ebay&' . $urlKey;



								?>
						<option value="all" <?php echo esc_attr( $selected ); ?> data-href="<?php echo esc_url( $visit_url ); ?>"><?php echo esc_attr( $ebay_user_id . ' (' . $ebay_site . ')' ); ?></option>
								<?php
							}
						}
						?>

				
						<?php

					}
				}
		}

		?>
		<option value="image"
					data-href="<?php echo esc_url( get_admin_url() . 'admin.php?page=sales_channel&channel=ebay&section=setup-ebay&add-new-account=yes' ); ?>">
					+ Add New Account</option>
			</select>

	</div>
</div>

<div id="ced-ebay-admin-message"></div>








<style type="text/css">
	.ced-right {
		float: right;
	}

	.ced-menu-container {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
</style>
