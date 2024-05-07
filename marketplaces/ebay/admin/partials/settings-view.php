<?php
// If this file is called directly, abort.

use Ced\Ebay\Settings_View;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$file = CED_EBAY_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $file ) ) {
	require_once $file;
}

$settings_view_class_file = CED_EBAY_DIRPATH . 'admin/partials/class-ebay-render-settings.php';
if ( file_exists( $settings_view_class_file ) ) {
	require_once $settings_view_class_file;
}

$apiClient = new \Ced\Ebay\CED_EBAY_API_Client();
$apiClient->setJwtToken('abc');

$settings_obj = Settings_View::get_instance($user_id, $site_id);


if ( isset( $_POST['ced_ebay_setting_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_ebay_setting_nonce'] ), 'ced_ebay_setting_page_nonce' ) ) {
	$settings_obj->save_settings($_POST);
}
?>
<form action="" method="post">

	<div
		class="components-card is-size-medium woocommerce-table pinterest-for-woocommerce-landing-page__faq-section css-1xs3c37-CardUI e1q7k77g0">
		<div class="components-panel ced_amazon_settings_new">
			<div class="wc-progress-form-content woocommerce-importer ced-padding">

				<?php
				$settings_obj->render_general_settings();
				$settings_obj->render_business_policies();
				$settings_obj->render_global_options();
				$settings_obj->render_advanced_settings();
				?>

		<div class="ced-margin-top">
			<?php wp_nonce_field( 'ced_ebay_setting_page_nonce', 'ced_ebay_setting_nonce' ); ?>
			<button id="save_global_settings" name="global_settings" style="float: right;"
				class="config_button components-button is-primary">
				<?php esc_attr_e( 'Save Configuration', 'ebay-integration-for-woocommerce' ); ?>
			</button>

		</div>


	</div>
	</div>
	</div>




</form>

<script type="text/javascript">
	jQuery(".ced_ebay_map_to_fields").selectWoo({
		dropdownPosition: 'below',
		dropdownAutoWidth : false,
	});
</script>
