
<div class="woocommerce-progress-form-wrapper">

	<h2 style="text-align: left;">eBay Integration Onboarding</h2>
	<ol class="wc-progress-steps ced-progress">

		<?php

		foreach ( $keys as $index => $key ) {

			if ( $this->step == $key ) {
				echo '<li id="' . esc_attr( $key ) . '" class="active">' . esc_attr( $this->steps[ $key ]['name'] ) . ' </li>';
			} elseif ( $this->step !== $key && $index < $step_index ) {
				echo '<li id="' . esc_attr( $key ) . '" class="done">' . esc_attr( $this->steps[ $key ]['name'] ) . ' </li>';
			} else {
				echo '<li id="' . esc_attr( $key ) . '">' . esc_attr( $this->steps[ $key ]['name'] ) . ' </li>';

			}
		}
		?>



	</ol>
	<div class="wc-progress-form-content woocommerce-importer">
	<header style="text-align: center;">
					<?php $ebay_icon = CED_EBAY_URL . 'admin/images/success.jpg'; ?>
					<img style="width: 15%;" src="<?php echo esc_url( $ebay_icon ); ?>" alt="">
					<p><strong><?php echo esc_html__( 'Great job! Your onboarding process is complete.', 'ebay-integration-for-woocommerce' ); ?></strong></p>
				</header>
				<div class="wc-actions">
				<?php
				wp_nonce_field( 'ced_ebay_onboarding_completed_action', 'ced_ebay_onboarding_completed_button' );
				?>
						<a class="components-button is-primary" style="float: right;" data-attr='4' id="ced_ebay_continue_wizard_button" href="<?php esc_attr_e( admin_url( 'admin.php?page=sales_channel&channel=ebay&section=overview&user_id=' . $user_id . '&site_id=' . $site_id ) ); ?>" ><?php echo esc_html__( 'Go to overview', 'ebay-integration-for-woocommerce' ); ?></a>

				
			</div>
	</div>

	</div>
