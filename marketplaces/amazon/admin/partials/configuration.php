<?php

	$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
	$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
	$seller_id = str_replace( '|', '%7C', $seller_id );

	$mode = isset( $_GET['mode'] ) ? sanitize_text_field( $_GET['mode'] ) : 'production';


	$sellernextShopIds                                     = get_option( 'ced_amazon_sellernext_shop_ids', array() );
	$sellernextShopIds[ $user_id ]['ced_amz_current_step'] = 4;
	update_option( 'ced_amazon_sellernext_shop_ids', $sellernextShopIds );

?>


	<div class="woocommerce-progress-form-wrapper">
		<h2 style="text-align: left;"><?php echo esc_html__( 'Amazon for WooCommerce: Onboarding', 'amazon-for-woocommerce' ); ?></h2>
		<ol class="wc-progress-steps ced-progress">
			<li class="done"><?php echo esc_html__( 'Global Options', 'amazon-for-woocommerce' ); ?></li>
			<li class="done"><?php echo esc_html__( 'General Settings', 'amazon-for-woocommerce' ); ?></li>
			<li class="active"><?php echo esc_html__( 'Done!', 'amazon-for-woocommerce' ); ?></li>
		</ol>
		<div class="wc-progress-form-content woocommerce-importer">

				<header style="text-align: center;">
					<?php $amazon_icon = CED_AMAZON_URL . 'admin/images/success.jpg'; ?>
					<img style="width: 15%;" src="<?php echo esc_url( $amazon_icon ); ?>" alt="">
					<p><strong><?php echo esc_html__( 'Onboarding successfully completed!', 'amazon-for-woocommerce' ); ?></strong></p>
				</header>

			<div class="wc-actions">
				<?php $ced_base_uri = ced_amazon_base_uri( $mode ); ?>
				<a class="components-button is-primary" style="float: right;" data-attr='4' id="ced_amazon_continue_wizard_button" href="<?php esc_attr_e( admin_url( $ced_base_uri . '&section=overview&user_id=' . $user_id . '&seller_id=' . $seller_id ) ); ?>" ><?php echo esc_html__( 'Go to overview', 'amazon-for-woocommerce' ); ?></a>
					
			</div>


		</div>
	</div>
