<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}


$file = CED_EBAY_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $file ) ) {
	require_once $file;
}

?>


<style type="text/css">
		.ced-woocommerce-summary li a {
			font-size: 16px;
			padding: 0;
		}

		.ced-woocommerce-summary {
			border: unset;
		}

		.ced-woocommerce-summary li a {
			font-size: 16px;
			padding: 0;
			background: #fff;
			border: unset;
		}

		.ced-woocommerce-summary li a:hover {
			background: #fff;
		}

		.ced-woocommerce-summary li a:focus {
			box-shadow: unset !important;
		}
	</style>

<?php

$user_id                 = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
$site_id                 = isset( $_GET['site_id'] ) ? sanitize_text_field( $_GET['site_id'] ) : '';
$activeEbayListings      = ! empty( get_option( 'ced_ebay_total_listings_' . $user_id ) ) ? get_option( 'ced_ebay_total_listings_' . $user_id, true ) : 0;
$lastImportedTitle       = ! empty( get_option( 'ced_ebay_last_imported_title_' . $user_id . '>' . $site_id ) ) ? get_option( 'ced_ebay_last_imported_title_' . $user_id . '>' . $site_id, true ) : '';
$uploadedProducts        = 0;
$importedFromEbay        = 0;
$totalrevenue            = 0;
$country_code            = '';
$configInstance          = \Ced_Ebay_WooCommerce_Core\Ebayconfig::get_instance();
		$ebaySiteDetails = $configInstance->getEbaycountrDetail( $site_id );
if ( ! empty( $ebaySiteDetails ) && is_array( $ebaySiteDetails ) && isset( $ebaySiteDetails['countrycode'] ) ) {
	$country_code = $ebaySiteDetails['countrycode'];
}
$uploadedProductArgs = array(
	'numberposts' => -1,
	'post_type'   => 'product',
	'post_status' => 'publish',
	'fields'      => 'ids',
	'meta_query'  => array(
		array(
			'key'     => '_ced_ebay_listing_id_' . $user_id . '>' . $site_id,
			'compare' => 'EXISTS',
		),
	),
);
// update_post_meta( $product_id, '_ced_ebay_importer_listing_id_' . $user_id . '>' . $siteID, $itemid );

$uploadedProductsResults = new \WP_Query( $uploadedProductArgs );
$uploadedProducts        = $uploadedProductsResults->found_posts;

$importedProductsArgs = array(
	'numberposts' => -1,
	'post_type'   => 'product',
	'post_status' => 'publish',
	'fields'      => 'ids',
	'meta_query'  => array(
		array(
			'key'     => '_ced_ebay_importer_listing_id_' . $user_id . '>' . $site_id,
			'compare' => 'EXISTS',
		),
	),
);

$importedProductsQuery = new \WP_Query( $importedProductsArgs );
$importedFromEbay      = $importedProductsQuery->found_posts;

$totalProductArgs = array(
	'post_type'           => 'product',
	'post_status'         => 'publish',
	'ignore_sticky_posts' => 1,
);

$totalProductsResults = new \WP_Query( $totalProductArgs );
$totalProducts        = $totalProductsResults->found_posts;

$totalOrdersArgs = array(
	'type'                          => 'shop_order',
	'order'                         => 'DESC',
	'ced_ebay_order_user_id'        => $user_id,
	'ced_ebay_listingMarketplaceId' => 'EBAY_' . $country_code,
	'return'                        => 'ids',
);
$wooOrderIds     = wc_get_orders( $totalOrdersArgs );
if ( is_wp_error( $wooOrderIds ) || empty( $wooOrderIds ) ) {
	$totalOrders = 0;
} else {
	$totalOrders = count( $wooOrderIds );
}
$progress_status = get_option( 'ced_ebay_importer_progress_status_' . $user_id . '>' . $site_id );

$marketingRequestFile = CED_EBAY_DIRPATH . 'admin/ebay/lib/cedMarketingRequest.php';
if ( file_exists( $marketingRequestFile ) ) {
	$shop_data = ced_ebay_get_shop_data( $user_id, $site_id );
	if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
		$siteID = $site_id;
		$token  = $shop_data['access_token'];

		require_once $marketingRequestFile;
		$cedMarketingRequest        = new \Ced_Marketing_API_Request( $siteID );
		$endpoint                   = 'privilege';
		$responseAccountsApi        = $cedMarketingRequest->sendHttpRequestForAccountAPI( $endpoint, $token, '' );
		$account_privilege_response = json_decode( $responseAccountsApi, true );
		// print_r($account_privilege_response);die('123');
		$selling_limit_currency           = '';
		$selling_limit_value              = '-';
		$is_seller_registration_completed = 'Unknown';
		$selling_limit_quantity           = '-';
		if ( is_array( $account_privilege_response ) && ( isset( $account_privilege_response['sellingLimit'] ) || isset( $account_privilege_response['sellerRegistrationCompleted'] ) ) ) {
			$selling_limit_currency           = isset( $account_privilege_response['sellingLimit']['amount']['currency'] ) ? $account_privilege_response['sellingLimit']['amount']['currency'] : '';
			$selling_limit_value              = isset( $account_privilege_response['sellingLimit']['amount']['value'] ) ? $account_privilege_response['sellingLimit']['amount']['value'] : '-';
			$selling_limit_quantity           = isset( $account_privilege_response['sellingLimit']['quantity'] ) ? $account_privilege_response['sellingLimit']['quantity'] : '-';
			$is_seller_registration_completed = isset( $account_privilege_response['sellerRegistrationCompleted'] ) ? $account_privilege_response['sellerRegistrationCompleted'] : 'Unknown';
		}

		// print_r($selling_limit_currency);
		// print_r(number_format_i18n($selling_limit_value));
		// print_r($selling_limit_quantity);
		// print_r($is_seller_registration_completed);
		// die('123');
	}
}
?>

<div class="woocommerce-progress-form-wrapper">
		<div class="wc-progress-form-content">
			<header>
				<h2>eBay Account Details</h2>
				<p>Keep track of your selling limits on eBay, account registration status, and other vital info about your eBay account</p>
				<div class="woocommerce-dashboard__store-performance">
					<div role="menu" aria-orientation="horizontal" aria-label="Performance Indicators" class="ced-ebay-account-details-section"
						aria-describedby="woocommerce-summary-helptext-87">
						<ul class="woocommerce-summary has-3-items ced-woocommerce-summary">
							<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span
											data-wp-component="Text"
											class="components-truncate components-text css-jfofvs e19lxcc00">Total Amount <?php echo ! empty( $selling_limit_currency ) ? esc_html__( '(' . $selling_limit_currency . ')', 'ebay-integration-for-woocommerce' ) : ''; ?></span></div>
									<div class="woocommerce-summary__item-ced-data">
										<div class="woocommerce-summary__item-value"><span
												data-wp-component="Text"
												class="components-truncate components-text css-2x4s0q e19lxcc00"><?php echo '-' !== $selling_limit_value ? esc_html__( number_format_i18n( $selling_limit_value ), 'ebay-integration-for-woocommerce' ) : '-'; ?></span>
										</div>
									</div>
								</a>
							</li>
							
							<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span
											data-wp-component="Text"
											class="components-truncate components-text css-jfofvs e19lxcc00">Total Quantity</span>
									</div>
									<div class="woocommerce-summary__item-ced-data">
										<div class="woocommerce-summary__item-value"><span
												data-wp-component="Text"
												class="components-truncate components-text css-2x4s0q e19lxcc00"><?php echo '-' !== $selling_limit_quantity ? esc_html__( number_format_i18n( $selling_limit_quantity ), 'ebay-integration-for-woocommerce' ) : '-'; ?></span>
										</div>
									</div>
								</a>
							</li>

							<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span
											data-wp-component="Text"
											class="components-truncate components-text css-jfofvs e19lxcc00">Seller Registration</span>
									</div>
									<div class="woocommerce-summary__item-ced-data">
										<div class="woocommerce-summary__item-value"><span
												data-wp-component="Text"
												class="components-truncate components-text css-2x4s0q e19lxcc00">
												<?php
												if ( true == $is_seller_registration_completed ) {
													echo esc_html( 'Completed' );
												} elseif ( false === $is_seller_registration_completed ) {
													echo esc_html( 'Pending' );
												} else {
													echo esc_html( $is_seller_registration_completed );
												}
												?>
												</span>
										</div>
									</div>
								</a>
							</li>
							
						</ul>
					</div>
				</div>
			</header>
			<div class="wc-actions">
				<a style="float: right;" href="#" id="ced_ebay_check_token_status_btn"
				class="components-button is-primary">Check Token Status</a>
			</div>
		</div>
	</div>
	<div class="woocommerce-progress-form-wrapper">
		<div class="wc-progress-form-content ced-ebay-product-importer-section">
			<header>
				<h2>eBay Listings Importer</h2>
				<?php if ( empty( $progress_status ) ) { ?>
					<div class="woocommerce-dashboard__store-performance">
					<div role="menu" aria-orientation="horizontal" aria-label="Performance Indicators"
						aria-describedby="woocommerce-summary-helptext-87">
						<div class="wc_progress_importer_bar woocommerce-progress-form-wrapper">
						<ul class="woocommerce-summary has-3-items ced-woocommerce-summary">
						<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span
											data-wp-component="Text"
											class="components-truncate components-text css-jfofvs e19lxcc00">Imported from eBay
											</span></div>
									<div class="woocommerce-summary__item-ced-data">
										<div class="woocommerce-summary__item-value"><span
												data-wp-component="Text"
												class="components-truncate components-text css-2x4s0q e19lxcc00"><?php echo esc_html__( $importedFromEbay, 'ebay-integration-for-woocommerce' ); ?></span>
										</div>
									</div>
								</a>
							</li>
							<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span
											data-wp-component="Text"
											class="components-truncate components-text css-jfofvs e19lxcc00">Active Listings on eBay
											</span></div>
									<div class="woocommerce-summary__item-ced-data">
										<div class="woocommerce-summary__item-value"><span
												data-wp-component="Text"
												class="components-truncate components-text css-2x4s0q e19lxcc00"><?php echo esc_html__( $activeEbayListings, 'ebay-integration-for-woocommerce' ); ?></span>
										</div>
									</div>
								</a>
							</li>
							<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span
											data-wp-component="Text"
											class="components-truncate components-text css-jfofvs e19lxcc00">Last Imported Product
											</span></div>
									<div class="woocommerce-summary__item-ced-data">
										<div class="woocommerce-summary__item-value"><span data-wp-component="Text"
												class="ced-ebay-last-import-title components-truncate components-text css-2x4s0q e19lxcc00"><?php echo esc_attr( $lastImportedTitle ); ?></span>
										</div>
									</div>
								</a>
							</li>
				</ul>
				</div>
				</div>
				</div>
				<div class="wc-actions ced-ebay-product-importer-button">
					<button type="button" class="ced_ebay_start_import components-button is-primary" data-action="start_import">Start</button>
					<button type="button"  class="components-button is-secondary ced_ebay_start_import" data-action="reset_import">Reset</button>
				</div>
				<?php } else { ?>
				<div class="woocommerce-dashboard__store-performance">
					<div role="menu" aria-orientation="horizontal" aria-label="Performance Indicators"
						aria-describedby="woocommerce-summary-helptext-87">
						<div class="wc_progress_importer_bar woocommerce-progress-form-wrapper ced-ebay-product-importer-section">
						<ul class="woocommerce-summary has-3-items ced-woocommerce-summary">
						<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span
											data-wp-component="Text"
											class="components-truncate components-text css-jfofvs e19lxcc00">Imported from eBay
											</span></div>
									<div class="woocommerce-summary__item-ced-data">
										<div class="woocommerce-summary__item-value"><span
												data-wp-component="Text"
												class="components-truncate components-text css-2x4s0q e19lxcc00"><?php echo esc_html__( $importedFromEbay, 'ebay-integration-for-woocommerce' ); ?></span>
										</div>
									</div>
								</a>
							</li>
							<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span
											data-wp-component="Text"
											class="components-truncate components-text css-jfofvs e19lxcc00">Active Listings on eBay
											</span></div>
									<div class="woocommerce-summary__item-ced-data">
										<div class="woocommerce-summary__item-value"><span
												data-wp-component="Text"
												class="components-truncate components-text css-2x4s0q e19lxcc00"><?php echo esc_html__( $activeEbayListings, 'ebay-integration-for-woocommerce' ); ?></span>
										</div>
									</div>
								</a>
							</li>
							<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span
											data-wp-component="Text"
											class="components-truncate components-text css-jfofvs e19lxcc00">Last Imported Product
											</span></div>
									<div class="woocommerce-summary__item-ced-data">
										<div class="woocommerce-summary__item-value"><span data-wp-component="Text"
												class="ced-ebay-last-import-title components-truncate components-text css-2x4s0q e19lxcc00"><?php echo esc_attr( $lastImportedTitle ); ?></span>
										</div>
									</div>
								</a>
							</li>
				</ul>
							<div class="wc-progress-form-content woocommerce-importer woocommerce-importer__importing">
								<header>
									<span class="spinner is-active"></span>
									<h2>Importing</h2>
									<p>Your products are now being imported...</p>
								</header>
								<section>
									<progress class="woocommerce-importer-progress ced_ebay_importer_progress" max="100" value="0"></progress>
								</section>
							</div>
							<span id="ced_ebay_product_import_progress"></span>
						</div>
						<span id="ced_ebay_product_import_progress_error"></span>
						<span id="ced_ebay_product_import_next_schedule_run"></span>		
					</div>
				</div>
				<div class="wc-actions ced-ebay-product-importer-button">
					<button type="button" class="ced_ebay_stop_import components-button is-primary">Stop</button>
				</div>
				<?php } ?>
			</header>
		</div>
	</div>

	<div class="woocommerce-progress-form-wrapper">
		<div class="wc-progress-form-content">
			<header>
				<h2>Product Stats</h2>
				<p>Track your product listing status on the go. Click on 'View all products' button to see the product details.</p>
				<div class="woocommerce-dashboard__store-performance">
					<div role="menu" aria-orientation="horizontal" aria-label="Performance Indicators"
						aria-describedby="woocommerce-summary-helptext-87">
						<ul class="woocommerce-summary has-3-items ced-woocommerce-summary">
							<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span
											data-wp-component="Text"
											class="components-truncate components-text css-jfofvs e19lxcc00">Total
											Products </span></div>
									<div class="woocommerce-summary__item-ced-data">
										<div class="woocommerce-summary__item-value"><span
												data-wp-component="Text"
												class="components-truncate components-text css-2x4s0q e19lxcc00"><?php echo esc_html__( $totalProducts, 'ebay-integration-for-woocommerce' ); ?></span>
										</div>
									</div>
								</a>
							</li>
							<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span
											data-wp-component="Text"
											class="components-truncate components-text css-jfofvs e19lxcc00">Listed on eBay
											</span></div>
									<div class="woocommerce-summary__item-ced-data">
										<div class="woocommerce-summary__item-value"><span
												data-wp-component="Text"
												class="components-truncate components-text css-2x4s0q e19lxcc00"><?php echo esc_html__( $uploadedProducts, 'ebay-integration-for-woocommerce' ); ?></span>
										</div>
									</div>
								</a>
							</li>
							<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span
											data-wp-component="Text"
											class="components-truncate components-text css-jfofvs e19lxcc00">Imported from eBay
											</span></div>
									<div class="woocommerce-summary__item-ced-data">
										<div class="woocommerce-summary__item-value"><span
												data-wp-component="Text"
												class="components-truncate components-text css-2x4s0q e19lxcc00"><?php echo esc_html__( $importedFromEbay, 'ebay-integration-for-woocommerce' ); ?></span>
										</div>
									</div>
								</a>
							</li>
							<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<!-- <div class="woocommerce-summary__item-label"><span
											data-wp-component="Text"
											class="components-truncate components-text css-jfofvs e19lxcc00">Revenue</span>
									</div>
									<div class="woocommerce-summary__item-ced-data">
										<div class="woocommerce-summary__item-value"><span
												data-wp-component="Text"
												class="components-truncate components-text css-2x4s0q e19lxcc00">$1200</span>
										</div>
									</div> -->
								</a>
							</li>
						</ul>
					</div>
				</div>
			</header>
			<div class="wc-actions">
				<a style="float: right;" 
				href="<?php echo esc_url( get_admin_url() . 'admin.php?page=sales_channel&channel=ebay&section=products-view&user_id=' . $user_id . '&site_id=' . $site_id ); ?>"
				class="components-button is-primary">View all Products</a>
			</div>
		</div>
	</div>


	<div class="woocommerce-progress-form-wrapper">
		<div class="wc-progress-form-content">
			<header>
				<h2>Order Stats</h2>
				<p>Keep track of your order's journey. Click on 'View all orders' to see the order details.</p>
				<div class="woocommerce-dashboard__store-performance">
					<div role="menu" aria-orientation="horizontal" aria-label="Performance Indicators"
						aria-describedby="woocommerce-summary-helptext-87">
						<ul class="woocommerce-summary has-2-items ced-woocommerce-summary">
							<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span
											data-wp-component="Text"
											class="components-truncate components-text css-jfofvs e19lxcc00">Total
											Orders</span></div>
									<div class="woocommerce-summary__item-ced-data">
										<div class="woocommerce-summary__item-value"><span
												data-wp-component="Text"
												class="components-truncate components-text css-2x4s0q e19lxcc00"><?php echo esc_html__( $totalOrders, 'ebay-integration-for-woocommerce' ); ?></span>
										</div>
									</div>
								</a>
							</li>
							
							<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span
											data-wp-component="Text"
											class="components-truncate components-text css-jfofvs e19lxcc00">Revenue generated</span>
									</div>
									<div class="woocommerce-summary__item-ced-data">
										<div class="woocommerce-summary__item-value"><span
												data-wp-component="Text"
												class="components-truncate components-text css-2x4s0q e19lxcc00"><?php echo esc_attr( get_woocommerce_currency_symbol() ); ?><?php echo esc_html__( $totalrevenue, 'ebay-integration-for-woocommerce' ); ?></span>
										</div>
									</div>
								</a>
							</li>
							
						</ul>
					</div>
				</div>
			</header>
			<div class="wc-actions">
				<a style="float: right;" 
				href="<?php echo esc_url( get_admin_url() . 'admin.php?page=sales_channel&channel=ebay&section=view-ebay-orders&user_id=' . $user_id . '&site_id=' . $site_id ); ?>"
				class="components-button is-primary">View all Orders</a>
			</div>
		</div>
	</div>
