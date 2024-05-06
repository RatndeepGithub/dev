<?php
/**
 * Configuration
 *
 * @package  Walmart_Woocommerce_Integration
 * @version  1.0.0
 * @link     https://woocommerce.com/vendor/cedcommerce/
 * @since    1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$step            = isset( $_GET['step'] ) ? sanitize_text_field( $_GET['step'] ) : '';
$new_action      = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
$add_new_account = isset( $_GET['add-new-account'] ) ? sanitize_text_field( $_GET['add-new-account'] ) : '';

$account_list = ced_walmart_return_partner_detail_option();




if ( empty( $account_list ) || ( 'yes' === $add_new_account && ! isset( $_GET['step'] ) ) ) {
	include_file( CED_WALMART_DIRPATH . 'admin/accounts/class-ced-walmart-connect-account.php' );
	$obj = new Ced_Walmart_Connect_Account();
	$obj->render_fields();
	return;
} elseif ( ! empty( $new_action ) ) {
	ced_walmart_get_active_action_steps_template( $new_action, $step );
	return;
} elseif ( isset( $step ) && ! empty( $step ) ) {
	return;
}




$store_id           = ced_walmart_get_current_active_store();
$connected_accounts = ced_walmart_return_partner_detail_option();
?>
<body>
	<?php
	if ( isset( $connected_accounts[ $store_id ]['current_step'] ) && ! empty( $connected_accounts[ $store_id ]['current_step'] ) ) {
		?>
		<div class="">
			<div class="woocommerce-progress-form-wrapper">
				<div class="wc-progress-form-content woocommerce-importer">
					<header>
						<h2>Onboarding</h2>
					</header>
					<div data-wp-c16t="true" data-wp-component="Card" class="components-surface components-card woocommerce-task-card woocommerce-homescreen-card css-1pd4mph e19lxcc00">
						<div class="css-10klw3m e19lxcc00">
							<ul class="woocommerce-experimental-list">
								<li role="button" tabindex="0" class="woocommerce-experimental-list__item has-action transitions-disabled woocommerce-task-list__item index-4 is-active">
									<div class="woocommerce-task-list__item-before"><div class="woocommerce-task__icon"></div></div>
									<div class="woocommerce-task-list__item-text">
										<div data-wp-c16t="true" data-wp-component="Text" class="components-truncate components-text css-10hubey e19lxcc00"><span class="woocommerce-task-list__item-title"><a href="<?php echo esc_attr__( $connected_accounts[ $store_id ]['current_step'], 'walmart-woocommerce-integration' ); ?>">Onboarding Pending</a></span></div>
									</div>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
	?>

	<div class="woocommerce-progress-form-wrapper">
		<div class="wc-progress-form-content">
			<header>
				<h2>Product Stats</h2>
				<p>Track your product listing status on the go. Click on 'View all products' button to see the product details.</p>
				<div class="woocommerce-dashboard__store-performance">
					<div role="menu" aria-orientation="horizontal" aria-label="Performance Indicators" aria-describedby="woocommerce-summary-helptext-87">
						<ul class="woocommerce-summary has-2-items ced-woocommerce-summary ced_walmart_overview_dash">

							<li class="woocommerce-summary__item-container">
								<a href="
								<?php
								echo esc_url(
									ced_get_navigation_url(
										'walmart',
										array(
											'section'  => 'products',
											'store_id' => ced_walmart_get_current_active_store(),
										)
									)
								);
								?>
								" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span data-wp-c16t="true" data-wp-component="Text" class="components-truncate components-text css-jfofvs e19lxcc00">Total Products</span></div>
									<div class="woocommerce-summary__item-data">
										<div class="woocommerce-summary__item-value"><span data-wp-c16t="true" data-wp-component="Text" class="components-truncate components-text css-2x4s0q e19lxcc00"><?php echo esc_attr( get_walmart_products_count( ced_walmart_get_current_active_store(), 'total' ) ); ?></span></div>
									</div>
								</a>
							</li>
							<li class="woocommerce-summary__item-container">
								<a href="
								<?php
								echo esc_url(
									ced_get_navigation_url(
										'walmart',
										array(
											'section'  => 'products',
											'store_id' => ced_walmart_get_current_active_store(),
											'status_sorting' => 'Uploaded',
										)
									)
								);
								?>
								" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span data-wp-c16t="true" data-wp-component="Text" class="components-truncate components-text css-jfofvs e19lxcc00">Listed on Walmart</span></div>
									<div class="woocommerce-summary__item-data">
										<div class="woocommerce-summary__item-value"><span data-wp-c16t="true" data-wp-component="Text" class="components-truncate components-text css-2x4s0q e19lxcc00"><?php echo esc_attr( get_walmart_products_count( ced_walmart_get_current_active_store() ) ); ?></span></div>
									</div>
								</a>
							</li>
						</ul>
					</div>
				</div>
			</header>
			<div class="wc-actions">
				<a href="
				<?php
				echo esc_url(
					ced_get_navigation_url(
						'walmart',
						array(
							'section'  => 'products',
							'store_id' => ced_walmart_get_current_active_store(),
						)
					)
				);
				?>
				"><button style="float: right;" type="button" class="components-button is-primary">View all Products</button></a>
			</div>
		</div>
	</div>
		<div class="woocommerce-progress-form-wrapper">
		<div class="wc-progress-form-content">
			<header>
				<h2>Order Stats</h2>
				<p>Keep track of your order's journey. Click on 'View all orders' to see the order details.</p>
				<div class="woocommerce-dashboard__store-performance">
					<div role="menu" aria-orientation="horizontal" aria-label="Performance Indicators" aria-describedby="woocommerce-summary-helptext-87">
						<ul class="woocommerce-summary has-2-items ced-woocommerce-summary ced_walmart_overview_dash">
							<li class="woocommerce-summary__item-container">
								<a href="
								<?php
								echo esc_url(
									ced_get_navigation_url(
										'walmart',
										array(
											'section'  => 'orders',
											'store_id' => ced_walmart_get_current_active_store(),
										)
									)
								);
								?>
								" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span data-wp-c16t="true" data-wp-component="Text" class="components-truncate components-text css-jfofvs e19lxcc00">Total Orders</span></div>
									<div class="woocommerce-summary__item-data">
										<div class="woocommerce-summary__item-value"><span data-wp-c16t="true" data-wp-component="Text" class="components-truncate components-text css-2x4s0q e19lxcc00"><?php echo esc_attr( get_walmart_orders_count( ced_walmart_get_current_active_store() ) ); ?></span></div>
									</div>
								</a>
							</li>

							<li class="woocommerce-summary__item-container">
								<a href="javascript:void(0)" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span data-wp-c16t="true" data-wp-component="Text" class="components-truncate components-text css-jfofvs e19lxcc00">Revenue generated</span></div>
									<div class="woocommerce-summary__item-data">
										<div class="woocommerce-summary__item-value"><span data-wp-c16t="true" data-wp-component="Text" class="components-truncate components-text css-2x4s0q e19lxcc00"><?php echo esc_attr( get_walmart_orders_revenue( ced_walmart_get_current_active_store() ) ); ?></span></div>
									</div>
								</a>
							</li>

						</ul>
					</div>
				</div>
			</header>
			<div class="wc-actions">
				<a href="
				<?php
				echo esc_url(
					ced_get_navigation_url(
						'walmart',
						array(
							'section'  => 'orders',
							'store_id' => ced_walmart_get_current_active_store(),
						)
					)
				);
				?>
				"><button style="float: right;" type="button" class="components-button is-primary">View all Orders</button></a>
			</div>
		</div>
	</div>
