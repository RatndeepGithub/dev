

<style type="text/css">
	table tbody tr td {
		border-bottom: 1px solid #eee;
	}
	.widefat td {
		vertical-align: middle;
		padding: 16px 10px;
	}
	table{
		border: 1px solid #eee;
	}
</style>

		<!-- <div class="wrap woocommerce"> -->
			<div class="woocommerce-progress-form-wrapper">
				<header style="text-align: left;">
					<h2>Welcome to CedCommerce Integrations</h2>
					<p>Accelerate your sales by connecting to different marketplaces by CedCommerce. You can connect each marketplace from below or by clicking on marketplace tab above.</p>
				</header>
				<div class="wc-progress-form-content woocommerce-importer">
					<header>
						<h2>Connect Integration</h2>
					</header>
					<table class="wp-list-table widefat fixed striped table-view-list posts">
						<tbody id="the-list">
							<?php
							/**
							 *
							 * Filter to get array of active marketplaces
							 *
							 * @since  1.0.0
							 */
							$activeMarketplaces = apply_filters( 'ced_sales_channels_list', array() );
							foreach ( $activeMarketplaces as $navigation ) {
								?>
								<tr id="post-319" style="background: #fff; border-bottom: 1px solid #c3c4c7;" class="iedit author-self level-0 post-319 type-product status-publish hentry" style="">
									<td style="width: 6%;" class="thumb column-thumb" data-colname="Image">
											<img width="150" height="150" src="<?php echo esc_url( $navigation['card_image_link'] ); ?>" class="woocommerce-placeholder wp-post-image" alt="Placeholder" decoding="async" loading="lazy" sizes="(max-width: 150px) 100vw, 150px">
									</td>
									<td style="width: 60%;" class="name column-name has-row-actions column-primary" data-colname="Name">
										<strong>
										<span style="font-size: 14px; color: #1E1E1E;"><?php echo esc_attr( $navigation['name'] ); ?></span>
										<br>
									</strong>			
										<?php
										/**
										 * Ced_show_connected_accounts.
										 *
										 * @since 1.0.0
										 */
										do_action( 'ced_show_connected_accounts', $navigation['menu_link'] );
										?>
								</td>
								<td class="sku column-sku" data-colname="SKU">
									<a href="<?php echo esc_url( $navigation['doc_url'] ); ?>" target="_blank">View Guide</a>
								</td>
								<?php
								if ( $navigation['is_active'] ) {
									?>
									<td class="is_in_stock column-is_in_stock" data-colname="Stock"><a class="components-button is-secondary" href="
									<?php
									echo esc_url(
										ced_get_navigation_url(
											$navigation['menu_link'],
											array(
												'section' => 'setup-' . $navigation['menu_link'],
												'add-new-account' => 'yes',
											)
										)
									);
									?>
									">Connect</a>
									</td>		
									<?php
								} elseif ( $navigation['is_installed'] ) {
									?>
										<td class="is_in_stock column-is_in_stock" data-colname="Stock"><a class="components-button is-secondary" href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>">Activate</a>
									</td>		
									<?php
								} else {
									?>
									<td class="is_in_stock column-is_in_stock" data-colname="Stock"><a class="components-button is-secondary" href="<?php echo esc_url( $navigation['page_url'] ); ?>">Buy now</a>
									</td>	
											<?php

								}
								?>
								</tr>
								<?php
								/**
								 * Ced_show_connected_accounts_details.
								 *
								 * @since 1.0.0
								 */
								do_action( 'ced_show_connected_accounts_details', $navigation['menu_link'] );
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		<!-- </div> -->

