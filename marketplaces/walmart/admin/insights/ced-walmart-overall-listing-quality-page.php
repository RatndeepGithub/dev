<?php


$ced_walmart_insight_file = CED_WALMART_DIRPATH . 'admin/insights/class-ced-walmart-insights.php';

if ( file_exists( $ced_walmart_insight_file ) ) {
	include_once $ced_walmart_insight_file;
}

$insight_obj          = new Ced_Walmart_Insights();
$listing_quality_data = $insight_obj->ced_walmart_overall_listing_quality_data();



?>




<div class="ced-walmart-card-wrapper css-1pd4mph">
	<div class="ced-walmart-card-wrap">
		<div class="ced-walmart-icon">
			<img src="<?php echo esc_url( CED_WALMART_URL . 'admin/images/Item-Status.png' ); ?>">
		</div>
		<div class="ced-card-title-wrapper">
			<div class="ced-card-title">
				<h3>Listing Quality</h3>
				<a href="#" id="ced-popup-button-overall-listing" class="components-button is-primary">View breakdown</a>
				<div id="ced-popup-overall-listing" class="ced-modal">

					<div class="ced-modal-text-content-two">
						<h2 style="font-size: 22px;border-bottom: 1px solid #888; margin-top: 0; padding-bottom: 22px;">Listing Quality Breakdown</h2>
						<div class="ced-popup-two-progress-wrap">
							<div class="ced-popup-two-progress-container">
								<label for="Content">Content & Discoverability:</label>
								<progress id="Content" value="<?php echo esc_attr( $listing_quality_data['contentScore'] ); ?>" min="0" max="100"> <?php echo esc_attr( $listing_quality_data['contentScore'] ); ?>% </progress>
							</div>
							<div class="ced-popup-two-progress-container">
								<label for="Offer">Offer:</label>
								<progress id="Offer" value="<?php echo esc_attr( $listing_quality_data['offerScore'] ); ?>" min="0" max="100"> <?php echo esc_attr( $listing_quality_data['offerScore'] ); ?>% </progress>
							</div>
							<div class="ced-popup-two-progress-container">
								<label for="Reviews">Ratings & Reviews:</label>
								<progress id="Reviews" value="<?php echo esc_attr( $listing_quality_data['ratingReviewScore'] ); ?>" min="0" max="100"> <?php echo esc_attr( $listing_quality_data['ratingReviewScore'] ); ?>% </progress>
							</div>
						</div>
						<div class="ced-post-purchase quality-wrap">
							<div class="ced-post-purchase-content-wrap">
								<h3>Post-Purchase Quality</h3>
								<p><?php echo esc_attr( $listing_quality_data['defectRatio'] ); ?> % of your catalog has issues</p>
							</div>
							<div class="ced-post-purchase-number-wrap">
								<h2><?php echo esc_attr( $listing_quality_data['itemDefectCnt'] ); ?></h2>
								<p>items with issues</p>
							</div>
						</div>
						<div class="ced-button-wrap-popup alignright ced-close-button">
							<span class="ced-close-button-overall-listing button-primary woocommerce-save-button">Close</span>
						</div>
					</div>

				</div>
			</div>
			<div class="ced-card-progress-wrap">
				<div class="cards">
					<div class="box">
						<div class="percent">
							<svg>
								<circle cx="40" cy="40" r="40"></circle>
								<circle cx="40" cy="40" r="40"></circle>
							</svg>
							<div class="number">
								<h2><?php echo esc_attr( $listing_quality_data['overAllQuality'] ); ?><span>%</span></h2>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>












