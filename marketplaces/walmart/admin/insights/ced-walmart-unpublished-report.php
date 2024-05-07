<?php

$ced_walmart_insight_file = CED_WALMART_DIRPATH . 'admin/insights/class-ced-walmart-insights.php';

if ( file_exists( $ced_walmart_insight_file ) ) {
	include_once $ced_walmart_insight_file;
}

$insight_obj                 = new Ced_Walmart_Insights();
$unpublished_item_count_data = $insight_obj->ced_walmart_unpublished_counts();


?>







<div class="ced-walmart-card-wrapper css-1pd4mph">
	<div class="ced-walmart-card-wrap">
		<div class="ced-walmart-icon">
			<img src="<?php echo esc_url( CED_WALMART_URL . 'admin/images/Listing-Quality.png' ); ?>">
		</div>
		<div class="ced-card-title">
			<h3>Unpublished Items Status</h3>
			<p>Show how many items are unpublished for each reason.</p>
		</div>
		<div class="ced-card-button-wrap">
			<a href="#" id="ced-popup-button-unpublished-report" class="components-button is-primary">View Counts</a>
			<div id="ced-popup-unpublished-report" class="ced-modal">
				<div class="ced-modal-text-content-three">
					<h2 style="font-size: 22px;border-bottom: 1px solid #888; margin-top: 0; padding-bottom: 22px;">Unpublished Item Counts</h2>
					<span> Data of past 30 days</span>
					<div class="ced-popup-two-progress-wrap">
						<div class="col-wrap">
							<table class="widefat attributes-table wp-list-table ui-sortable" style="width:100%">
								<thead>
									<tr>
										<th scope="col">ISSUES</th>
										<th scope="col">ITEMS AFFECTED</th>
										<th scope="col">UNPUBLISHED VALUE</th>
									</tr>
								</thead>
								<tbody>
									<?php
									if ( is_array( $unpublished_item_count_data ) && ! empty( $unpublished_item_count_data ) ) {
										foreach ( $unpublished_item_count_data as $key ) {
											$number = $key['unpublishedValue'];
											if ( $number < 1000000 ) {
												$number_formatted = number_format( $number ) . ' K';
											} elseif ( $number < 1000000000 ) {
												$number_formatted = number_format( $number / 1000000, 1 ) . ' M';
											} else {
												$number_formatted = number_format( $number / 1000000000, 1 ) . ' B';
											}
											echo "<tr class='alternate'><td>" . esc_attr( $key['unpublishedReasonCode'] ) . '</td><td>' . esc_attr( $key['unpublishedCount'] ) . '</td><td>$ ' . esc_attr( $number_formatted ) . '</td> </tr>';
										}
									}
									?>

								</tbody>
							</table>
						</div>
					</div>

					<div class="ced-button-wrap-popup alignright ced-close-button">
						<span class="ced-close-button-unpublished-report button-primary woocommerce-save-button">Close</span>
					</div>
				</div>

			</div>
		</div>
	</div>
</div>










