<?php
$active_channel = isset( $_GET['channel'] ) ? sanitize_text_field( $_GET['channel'] ) : 'home';
$success        = isset( $_GET['success'] ) ? sanitize_text_field( $_GET['success'] ) : '';
$contract_id    = isset( $_GET['contract_id'] ) ? sanitize_text_field( $_GET['contract_id'] ) : '';



$sales_channel_page = ! empty( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : false;
if ( 'sales_channel' == $sales_channel_page ) {
	$active_channel = isset( $_GET['channel'] ) ? sanitize_text_field( $_GET['channel'] ) : 'home';
	?>


	<div class="ced-notification-top-wrap">
		<div class="woocommerce-layout__header">
			<div class="woocommerce-layout__header-wrapper"><h1 class="components-truncate components-text woocommerce-layout__header-heading">
				<?php echo esc_attr( ucwords( $active_channel ) . ( isset( $_GET['section'] ) ? ' > ' . ucwords( str_replace( array( '-', 'profile' ), array( ' ', 'template' ), sanitize_text_field( $_GET['section'] ) ) ) : '' ) ); ?></h1></div>
			</div>
		</div>

	<?php
}


if ( ! empty( $success ) && 'yes' == $success && ! empty( $contract_id ) ) {
	?>

	<div class="notice notice-success">
		<p><?php echo esc_html__( 'Plan purchased succesfully.', 'amazon-for-woocommerce' ); ?></p>
	</div>


	<?php
} elseif ( ! empty( $success ) && 'no' == $success && empty( $contract_id ) ) {
	?>

	<div class="notice notice-error">
		<p><?php echo esc_html__( 'Failed while purchasing a plan. Please try again.', 'amazon-for-woocommerce' ); ?></p>
	</div>

	<?php
}

?>


	<div class='ced-header-wrapper'>
		<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
			<?php $url = admin_url( 'admin.php?page=sales_channel' ); ?>
			<a href="<?php echo esc_url( $url ); ?>"
				class="nav-tab <?php echo ( 'home' == $active_channel ? 'nav-tab-active' : '' ); ?>">Home</a>
				<?php
				/**
				 *
				 * Filter to get array of active marketplaces
				 *
				 * @since  1.0.0
				 */
				$activeMarketplaces = apply_filters( 'ced_sales_channels_list', array() );
				foreach ( $activeMarketplaces as $navigation ) {
					if ( $navigation['is_active'] ) {

						echo '<a href="' . esc_url( ced_get_navigation_url( $navigation['menu_link'] ) ) . '" class="nav-tab ' . ( $navigation['menu_link'] == $active_channel ? 'nav-tab-active' : '' ) . '">' . esc_html__( $navigation['tab'], 'amazon-for-woocommerce' ) . '</a>';
					}
				}

				if ( file_exists( CED_AMAZON_DIRPATH . 'admin/saas/template/class-ced-common-pricing-plan.php' ) ) {
					?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=sales_channel&channel=pricing' ) ); ?>" class="nav-tab <?php echo ( 'pricing' === $active_channel ? 'nav-tab-active' : '' ); ?>">
						<?php esc_html_e( 'Pricing', 'amazon-for-woocommerce' ); ?>
					</a>
					<?php
				}
				?>
		</nav>
		
