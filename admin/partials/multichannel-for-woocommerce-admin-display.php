<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://woocommerce.com/vendor/cedcommerce/
 * @since      1.0.0
 *
 * @package    Multichannel_By_Cedcommerce
 * @subpackage Multichannel_By_Cedcommerce/admin/partials
 */

if ( isset( $_POST['ced-mcfw-user-register'] ) ) {

	if ( ! isset( $_POST['user_register_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['user_register_submit'] ) ), 'user_register' ) ) {
		return;
	}

	$response = wp_safe_remote_post(
		CED_MCFW_API_URL . '/user',
		array(
			'method'    => 'POST',
			'sslverify' => false,
			'body'      =>
			array(
				'user_email' => isset( $_POST['user_email'] ) ? sanitize_text_field( $_POST['user_email'] ) : '',
				'domain'     => home_url(),
			),
		)
	);

	$response_body = wp_remote_retrieve_body( $response );

	if ( $response_body ) {
		$response_body = json_decode( $response_body, 1 );
		if ( isset( $response_body['status'] ) && 200 == $response_body['status'] ) {
			update_option( 'ced_mcfw_user_token', $response_body['result']['token'] );
			update_option( 'ced_mcfw_user_details', json_encode( $response_body['result'] ) );
			echo '<div class="notice notice-success is-dismissable"><p>User registration successful .</p></div>';
			wp_safe_redirect( ced_get_navigation_url() );
			exit;
		} else {
			echo '<div class="notice notice-error is-dismissable"><p>' . esc_attr( $response_body['message'] ) . '</p></div>';
		}
	}
}
?>

<div id="ced_mcfw_user_register_wrap">
	<div class="ced-register-banner-text">
		<div class="ced-banner-text">
			<h3>Multichannel by CedCommerce</h3>
		</div>
	</div>
	<div class="ced-subsrible-form-wrap">
		<div class="ced-subscribe-form-wrapper">
			<div class="ced-subscribe-header">
				<h1>Register</h1>
				<p>This email address will be used for important communication and updates related to our plugins. </p>
			</div>
			<div class="ced-subscribe-form-content-holder">
				<div class="ced-subscribe-form-content">
					<form action="" method="post">
						<?php wp_nonce_field( 'user_register', 'user_register_submit' ); ?>
						<input type="email" placeholder="Enter user email" name="user_email" id="ced_mcfw_user_email" value="" required>
						<div class="ced-accept-wrap">
							<input type="checkbox" required checked>I agree to <a href="<?php echo esc_url( 'https://cedcommerce.com/privacy-policy' ); ?>" target="_blank">CedCommerce Privacy Policy</a>
						</div>
						<div class="ced-register-button-wrap">
							<button type="submit" class="button button-primary" name="ced-mcfw-user-register" id="ced_mcfw_user_register">Register</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

