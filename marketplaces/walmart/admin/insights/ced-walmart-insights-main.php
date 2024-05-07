<?php
/**
 * Shipping Template
 *
 * @package  Walmart_Woocommerce_Integration
 * @version  1.0.0
 * @link     https://woocommerce.com/vendor/cedcommerce/
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
get_walmart_header();

$active_store = ced_walmart_get_current_active_store();

?>


<div class="ced-walmart-header-wrap">
	<div class="ced-walmart-header-content-holder">
		<div class="ced-walmart-header-content">
			<h2>Listing Quality & Rewards</h2>
			<p>Listing quality provides you with insights on how customers view your published items. <a href="https://sellerhelp.walmart.com/seller/s/guide?article=000009472">Learn more</a></p>
		</div>
		<div class="ced-walamrt-insight-button">
			<button class="components-button is-primary" id="ced_walmart_insight_refresh" data-store-id="<?php echo esc_attr( $active_store ); ?>">Update insights</button>
		</div>
	</div>
</div>

<!------ Overall Quality Card ----- -->

<div class="ced-walmart-insight-wrapper">
	<div class="ced-walmart-insight-common-wrap">
		<?php require_once CED_WALMART_DIRPATH . 'admin/insights/ced-walmart-pro-seller-badge-page.php'; ?>
		<?php require_once CED_WALMART_DIRPATH . 'admin/insights/ced-walmart-overall-listing-quality-page.php'; ?>
		<?php require_once CED_WALMART_DIRPATH . 'admin/insights/ced-walmart-unpublished-report.php'; ?>
	</div>
</div>




