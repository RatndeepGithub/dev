<?php
$active_channel = ! empty( $_GET['channel'] ) ? sanitize_text_field( $_GET['channel'] ) : 'home';
require_once 'banner.php';
?>
<div class="ced-notification-top-wrap">
	<div class="woocommerce-layout__header">
		<div class="woocommerce-layout__header-wrapper">
			<h1 data-wp-c16t="true" data-wp-component="Text" class="components-truncate components-text woocommerce-layout__header-heading css-wv5nn e19lxcc00">
				<?php
				echo esc_attr( ucwords( $active_channel ) . ( isset( $_GET['section'] ) ? ' > ' . ucwords( str_replace( array( '-', '_' ), ' ', sanitize_text_field( $_GET['section'] ) ) ) : ( isset( $_GET['action'] ) ? ' > ' . ucwords( str_replace( array( '-', '_' ), ' ', sanitize_text_field( $_GET['action'] ) ) ) : '' ) ) );
				?>
			</h1>
		</div>
	</div>
</div>
<div class='ced-header-wrapper'>
	<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sales_channel' ) ); ?>" class="nav-tab <?php echo ( 'home' === $active_channel ? 'nav-tab-active' : '' ); ?>">
			<?php esc_html_e( 'Home', 'woocommerce-etsy-integration' ); ?>
		</a>
		<?php

		/**
		 * Filter for supported marketplaces
		 *
		 * @since  1.0.0
		 */
		$navigation_tabs = apply_filters( 'ced_sales_channels_list', array() );

		/**
		 * Filter for navigation tabs
		 *
		 * @since  1.0.0
		 */
		foreach ( apply_filters( 'ced_mcfw_navigation_tabs', $navigation_tabs ) as $navigation ) {
			if ( $navigation['is_active'] ) {
				echo '<a href="' . esc_url( ced_get_navigation_url( $navigation['menu_link'] ) ) . '" class="nav-tab ' . ( $navigation['menu_link'] === $active_channel ? 'nav-tab-active' : '' ) . '">';
				echo esc_html( $navigation['tab'] );
				echo '</a>';
			}
		}
		?>
	</nav>
</div>
<?php

