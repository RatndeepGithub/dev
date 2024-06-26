<div class="">
	<div class="woocommerce-progress-form-wrapper">
		<header style="text-align: left;">
			<h2><?php esc_html_e( 'Greetings! Welcome to CedCommerce Integrations.', 'woocommerce-etsy-integration' ); ?></h2>
			<p><?php esc_html_e( 'Boost your sales by linking with various marketplaces through CedCommerce. Connect to each marketplace below and watch your business grow!', 'woocommerce-etsy-integration' ); ?></p>
		</header>
		<div class="wc-progress-form-content woocommerce-importer">
			<header>
				<h2><?php esc_html_e( 'Connect to sell with:', 'woocommerce-etsy-integration' ); ?></h2>
			</header>
			<table class="wp-list-table widefat fixed striped table-view-list posts ced_mcfw_marketplace_lists">
				<tbody id="the-list">
					<?php

					/**
					 * Filter for supported marketplaces
					 *
					 * @since  1.0.0
					 */
					$activeMarketplaces = apply_filters( 'ced_sales_channels_list', array() );
					foreach ( $activeMarketplaces as $navigation ) {
						?>
						<tr id="post-319" style="background: #fff; border-bottom: 1px solid #c3c4c7;" class="iedit author-self level-0 post-319 type-product status-publish hentry" style="">
							<td style="width: 6%;" class="thumb column-thumb">
								<img width="150" height="150" src="<?php echo esc_url( $navigation['card_image_link'] ); ?>" class="woocommerce-placeholder wp-post-image" decoding="async" loading="lazy" sizes="(max-width: 150px) 100vw, 150px">
							</td>
							<td style="width: 60%;" class="name column-name has-row-actions column-primary" data-colname="<?php esc_attr_e( 'Name', 'woocommerce-etsy-integration' ); ?>">
								<strong>
									<span style="font-size: 14px; color: #1E1E1E;"><?php echo esc_html( $navigation['name'] ); ?></span>
									<br>
								</strong>
								<?php

								/**
								 * Action for displaying connected marketplaces and accounts
								 *
								 * @since  1.0.0
								 */
								do_action( 'ced_show_connected_accounts', $navigation['menu_link'] );
								?>
							</td>
							<td class="sku column-sku" data-colname="<?php esc_attr_e( 'SKU', 'woocommerce-etsy-integration' ); ?>">
								
							</td>
							<?php
							if ( $navigation['is_active'] ) {
								?>
								<td class="is_in_stock column-is_in_stock">
									<a class="components-button is-secondary" href="
									<?php
									echo esc_url(
										ced_get_navigation_url(
											$navigation['menu_link'],
											array(
												'add-new-account' => 'yes',
												'section' => 'setup-' . esc_attr( $navigation['menu_link'] ),
											),
											true
										)
									);
									?>
																					"><?php esc_html_e( 'Connect', 'woocommerce-etsy-integration' ); ?></a>
								</td>
								<?php
							} elseif ( $navigation['is_installed'] ) {
								?>
								<td class="is_in_stock column-is_in_stock">
									<a class="components-button is-secondary" href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>"><?php esc_html_e( 'Activate', 'woocommerce-etsy-integration' ); ?></a>
								</td>
								<?php
							} else {
								?>
								<td class="is_in_stock column-is_in_stock">
									<a class="components-button is-secondary" href="<?php echo esc_url( ced_get_navigation_url( 'pricing' ) ); ?>"><?php esc_html_e( 'Subscribe', 'woocommerce-etsy-integration' ); ?></a>
								</td>
								<?php
							}
							?>
						</tr>
						<?php

						/**
						 * Action for displaying connected marketplaces and accounts details
						 *
						 * @since  1.0.0
						 */
						do_action( 'ced_show_connected_accounts_details', $navigation['menu_link'] );
					}
					?>
				</tbody>
			</table>
		</div>
	</div>
</div>
</body>
</html>
