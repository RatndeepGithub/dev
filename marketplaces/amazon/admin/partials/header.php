<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}


$file = CED_AMAZON_DIRPATH . 'admin/partials/amazonRegions.php';
if ( file_exists( $file ) ) {
	require_once $file;
}

$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : false;




$sellernextShopIds = get_option( 'ced_amazon_sellernext_shop_ids', array() );

if ( empty( $seller_id ) ) {
	$seller_id = isset( $sellernextShopIds[ $user_id ] ) && isset( $sellernextShopIds[ $user_id ]['ced_mp_seller_key'] ) ? $sellernextShopIds[ $user_id ]['ced_mp_seller_key'] : '';
}

$amz_data_validated = get_option( 'ced_amzon_configuration_validated', array() );

// Check account participation
$seller_participation = false;
$participate_accounts = isset( $sellernextShopIds[ $user_id ] ) && isset( $sellernextShopIds[ $user_id ]['marketplaces_participation'] ) ? $sellernextShopIds[ $user_id ]['marketplaces_participation'] : '';


if ( is_array( $participate_accounts ) && $participate_accounts[ $seller_id ] ) {
	$seller_participation = true;
}

if ( isset( $amz_data_validated[ $seller_id ] ) && ! empty( $amz_data_validated[ $seller_id ] ) && is_array( $amz_data_validated[ $seller_id ] ) ) {
	$shop_id = $amz_data_validated[ $seller_id ]['seller_next_shop_id'];
}

if ( isset( $_GET['section'] ) ) {
	$section = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : '';
}

?>


<div class="ced-menu-container">
	<ul class="subsubsub">
		<li><a href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=amazon&section=overview&user_id=' . $user_id . '&seller_id=' . $seller_id ) ); ?>"
				class="
				<?php
				if ( 'overview' == $section ) {
					echo 'current';
				}
				?>
								"><?php echo esc_html__( 'Overview', 'amazon-for-woocommerce' ); ?></a> |</li>
					<li><a href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=amazon&section=settings&user_id=' . $user_id . '&seller_id=' . $seller_id ) ); ?>"
				class="
				<?php
				if ( 'settings' == $section ) {
					echo 'current';
				}
				?>
								"><?php echo esc_html__( 'Settings', 'amazon-for-woocommerce' ); ?></a>|</li>
					<li><a href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=amazon&section=templates-view&user_id=' . $user_id . '&seller_id=' . $seller_id ) ); ?>"
				class="
				<?php
				if ( 'templates-view' == $section || 'add-new-template' == $section ) {
					echo 'current';
				}
				?>
								"><?php echo esc_html__( 'Templates', 'amazon-for-woocommerce' ); ?></a> |</li>
		
	
		<li><a href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=amazon&section=products-view&user_id=' . $user_id . '&seller_id=' . $seller_id ) ); ?>"
				class="
				<?php
				if ( 'products-view' == $section ) {
					echo 'current';
				}
				?>
								"><?php echo esc_html__( 'Products', 'amazon-for-woocommerce' ); ?></a> |</li>
					<li><a href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=amazon&section=orders-view&user_id=' . $user_id . '&seller_id=' . $seller_id ) ); ?>"
				class="
				<?php
				if ( 'orders-view' == $section ) {
					echo 'current';
				}
				?>
								"><?php echo esc_html__( 'Orders', 'amazon-for-woocommerce' ); ?></a> |</li>
		<li><a href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=amazon&section=feeds-view&user_id=' . $user_id . '&seller_id=' . $seller_id ) ); ?>"
				class="
				<?php
				if ( 'feeds-view' == $section || 'feed-view' == $section ) {
					echo 'current';
				}
				?>
								"><?php echo esc_html__( 'Feeds', 'amazon-for-woocommerce' ); ?></a> </li>
		
		

	</ul>

	<div class="ced-right">
		<?php
		$ced_amazon_sellernext_shop_ids = get_option( 'ced_amazon_sellernext_shop_ids', array() );
		$current_active_section         = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : 'overview';

		if ( ! empty( $ced_amazon_sellernext_shop_ids ) ) {
			?>
			<select style="min-width: 160px;" id="media-attachment-filters" name="ced_amazon_change_acc"
				class="attachment-filters ced_amazon_change_acc">
				<?php
				foreach ( $ced_amazon_sellernext_shop_ids as $sellernextId => $sellernextData ) {

					$current_marketplace_id   = isset( $sellernextData['marketplace_id'] ) ? $sellernextData['marketplace_id'] : '';
					$current_marketplace_name = isset( $ced_amazon_regions_info[ $current_marketplace_id ] ) && isset( $ced_amazon_regions_info[ $current_marketplace_id ]['country-name'] ) ? $ced_amazon_regions_info[ $current_marketplace_id ]['country-name'] : '';

					if ( empty( $current_marketplace_id ) ) {
						continue;
					}

					$selected = '';
					if ( $user_id == $sellernextId && $seller_id == $sellernextData['ced_mp_seller_key'] ) {
						$selected = 'selected';
					}


					if ( 3 < $sellernextData['ced_amz_current_step'] ) {
						$url = add_query_arg(
							array(
								'page'      => 'sales_channel',
								'channel'   => 'amazon',
								'section'   => $current_active_section,
								'user_id'   => $sellernextId,
								'seller_id' => $sellernextData['ced_mp_seller_key'],
							),
							admin_url() . 'admin.php'
						);
						?>

						<option value="all" <?php echo esc_attr( $selected ); ?> data-href="<?php echo esc_url( $url ); ?>"><?php echo esc_attr( $current_marketplace_name ); ?></option>


						<?php
					} else {

						$current_step = $sellernextData['ced_amz_current_step'];
						if ( empty( $current_step ) ) {
							$urlKey = 'section=setup-amazon';
						} elseif ( 1 == $current_step ) {
							$urlKey = 'section=setup-amazon&part=wizard-options';
						} elseif ( 2 == $current_step ) {
							$urlKey = 'section=setup-amazon&part=wizard-settings';
						} elseif ( 3 == $current_step ) {
							$urlKey = 'section=setup-amazon&part=configuration';
						} else {

							$part = 'section=overview';
						}

						$url = get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&' . $urlKey . '&user_id=' . $sellernextId . '&seller_id=' . $sellernextData['ced_mp_seller_key'];

						?>
						<option value="all" <?php echo esc_attr( $selected ); ?> data-href="<?php echo esc_url( $url ); ?>"><?php echo esc_attr( $current_marketplace_name ); ?></option>
						<?php
					}
				}
				?>

				<option value="image"
					data-href="<?php echo esc_url( get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=setup-amazon&add-new-account=yes' ); ?>">
					+ Add New Account</option>
			</select>
			<?php

		}

		?>

	</div>
</div>


<?php

$new_account = isset( $_GET['add-new-account'] ) ? sanitize_text_field( $_GET['add-new-account'] ) : '';


if ( ! $seller_participation && empty( $new_account ) ) {
	?>
	<div class="notice notice-error is-dismissable">
		<p><?php echo esc_html__( "Something went wrong with seller's participation, please check your Amazon seller account!", 'amazon-for-woocommerce' ); ?></p>
	</div>
	<?php
}

?>
