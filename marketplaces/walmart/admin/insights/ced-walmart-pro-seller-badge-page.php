<?php

$ced_walmart_insight_file = CED_WALMART_DIRPATH . 'admin/insights/class-ced-walmart-insights.php';

if ( file_exists( $ced_walmart_insight_file ) ) {
	include_once $ced_walmart_insight_file;
}

$insight_obj           = new Ced_Walmart_Insights();
$pro_seller_badge_data = $insight_obj->ced_walmart_pro_seller_badge_data();
$trueSign              = '<span class="ced-check"><span class="dashicons dashicons-yes-alt"></span></span';
$falseSign             = '<span class="ced-uncheck"><span class="dashicons dashicons-dismiss"></span></span>';

?>


<div class="ced-walmart-card-wrapper css-1pd4mph">
	<div class="ced-walmart-card-wrap">
		<div class="ced-walmart-icon">
			<img src="<?php echo esc_url( CED_WALMART_URL . 'admin/images/Pro-Seller.png' ); ?>">
		</div>
		<div class="ced-card-title">
			<h3>Become a Pro Seller</h3>
			<p>Stand out to customers and get rewarded</p>
		</div>



		<?php
		if ( isset( $pro_seller_badge_data ) && ! empty( $pro_seller_badge_data ) ) {

			?>
			<div class="ced-card-button-wrap">
				<a href="#" id="ced-popup-button" class="components-button is-primary">View Progress</a>
			</div>

			<?php
		} else {
			?>
			<h4>No data available , Click Update Insights button </h4>

		<?php } ?>



		<div id="ced-popup" class="ced-modal">
			<div class="ced-modal-text-content">
				<h2 style="font-size: 22px;border-bottom: 1px solid #888; margin-top: 0; padding-bottom: 22px;">Pro Seller Status</h2>
				<div class="ced-popup-seller-wrap">
					<h2>Become a Pro Seller <i class="fa fa-check-circle" aria-hidden="true"></i></h2>
					<p>Badge Since:  <?php echo esc_attr( substr( $pro_seller_badge_data['badgedSince'], 0, strpos( $pro_seller_badge_data['badgedSince'], 'T' ) ) ); ?></p>
				</div>
				<div class="ced-popup-seller-badge-identifications">
					<div class="ced-badge-wrap">
						<p>Has Badge: <?php print_r( $pro_seller_badge_data['hasBadge'] ? $trueSign : $falseSign ); ?></p>
					</div>
					<div class="ced-badge-wrap">
						<p>Is Eligible: <?php print_r( $pro_seller_badge_data['isEligible'] ? $trueSign : $falseSign ); ?></p>
					</div>
					<div class="ced-badge-wrap">
						<p>Is Prohibited: <?php print_r( $pro_seller_badge_data['isProhibited'] ? $trueSign : $falseSign ); ?></p>
					</div>
				</div>

				<?php
				if ( $pro_seller_badge_data['badgeStatus'] ) {
					echo '<span>Badge Status :' . esc_attr( $pro_seller_badge_data['badgeStatus'] ) . '</span>';
				}
				?>

				<progress style="width:<?php echo esc_attr( $pro_seller_badge_data['healthyCountPercentage'] ); ?>%" class="woocommerce-task-progress-header__progress-bar" min="0" max="<?php echo esc_attr( $pro_seller_badge_data['criteriaCount'] ); ?>" value="<?php echo esc_attr( $pro_seller_badge_data['healthyCount'] ); ?>">
					
					<?php echo esc_attr( $pro_seller_badge_data['healthyCount'] ); ?> / <?php echo esc_attr( $pro_seller_badge_data['criteriaCount'] ); ?> criteria met
				</progress>
				<p>Meet all the criteria and qualifications* below to unlock incredible benefits and stand out to your customers.
					<a target="_blank" href="https://sellerhelp.walmart.com/seller/s/guide?article=000009472">Learn more</a>
				</p>
				<div class="ced-popup-table-wrapper">
					<div class="col-wrap">
						<table class="widefat attributes-table wp-list-table ui-sortable" style="width:100%">
							<thead>
								<tr>
									<th scope="col">MEETS CRITERIA</th>
									<th scope="col">PASS / FAIL</th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ( $pro_seller_badge_data['meetsCriteria'] as $key => $value ) {
									$signForCriteria = $value ? $trueSign : $falseSign;
									echo '<tr class="alternate">';
									echo '<td>' . esc_attr( ucwords( $key ) ) . '</td>';
									print_r( '<td>' . $signForCriteria . ' </td>' );
									echo '</tr>';
								}
								?>
							</tbody>
						</table>
					</div>
					<div class="col-wrap">
						<table class="widefat attributes-table wp-list-table ui-sortable" style="width:100%">
							<thead>
								<tr>
									<th scope="col">CRITERIA</th>
									<th scope="col">CURRENT MATRICS</th>
								</tr>
							</thead>
							<tbody>



								<?php
								foreach ( $pro_seller_badge_data['criteriaData'] as $key => $value ) {
									$toolTip = '';
									foreach ( $pro_seller_badge_data['recommendations'] as $key_recommendations => $value_recommendations ) {
										if ( $key == $key_recommendations ) {
											$toolTip = '<span> ' . wc_help_tip( __( $value_recommendations, 'walmart-woocommerce-integration' ) ) . '</span>';
										}
									}
									echo '<tr>';
									print_r( '<td>' . ucwords( $key ) . $toolTip . ' </td>' );
									echo '<td>' . esc_attr( $value ) . ' </td>';
									echo '</tr>';
								}

								?>
							</tbody>
						</table>
					</div>
				</div>
				<div class="ced-button-wrap-popup alignright">
					<span class="ced-close-button button-primary woocommerce-save-button ced-cancel">Close</span>
				</div>
			</div>
		</div>
	</div>
</div>









