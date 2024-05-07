<?php

/**
 *  Profile section to be rendered
 *
 * @package  Woocommerce_Walmart_Integration
 * @version  1.0.0
 * @link     https://woocommerce.com/vendor/cedcommerce/
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$file_product_fields = CED_WALMART_DIRPATH . 'admin/partials/class-ced-walmart-product-fields.php';
get_walmart_header();
include_file( $file_product_fields );
$class_product_fields_instance = new Ced_Walmart_Product_Fields();
$profile_id                    = isset( $_GET['profile_id'] ) ? sanitize_text_field( wp_unslash( $_GET['profile_id'] ) ) : '';
$profile_id                    = urldecode( $profile_id );

$profile_type = isset( $_GET['profile_type'] ) ? sanitize_text_field( wp_unslash( $_GET['profile_type'] ) ) : '';
$profile_type = urldecode( $profile_type );


if ( isset( $_POST['add_meta_keys'] ) || isset( $_POST['ced_walmart_profile_save_button'] ) ) {

	if ( ! isset( $_POST['profile_creation_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['profile_creation_submit'] ) ), 'profile_creation' ) ) {
		return;
	}

	$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
	
	if ( empty( $profile_id ) ) {
		$profile_id = isset( $sanitized_array['_umb_walmart_category'][0] ) ? $sanitized_array['_umb_walmart_category'][0] : '';
	}
		
	$woo_categories = isset( $sanitized_array['woo_categories'] ) ? $sanitized_array['woo_categories'] : array();
	// $walmart_cat    = isset( $sanitized_array['ced_walmart_last_level_cat'] ) ? sanitize_text_field( $sanitized_array['ced_walmart_last_level_cat'] ) : '';
	$walmart_cat = isset( $sanitized_array['_umb_walmart_category'][0] ) ? $sanitized_array['_umb_walmart_category'][0] : '';
	
	ced_walmart_update_category_mapping( $walmart_cat, $woo_categories , $profile_type );
	ced_walmart_save_template_fields( $sanitized_array, $profile_id , $profile_type);
}

function ced_walmart_update_category_mapping( $walmart_cat, $wooCommerce_cat, $profile_type) {

	require_once CED_WALMART_DIRPATH . 'admin/class-walmart-woocommerce-integration-admin.php';
	$ced_walmart_admin_instance = new Walmart_Woocommerce_Integration_Admin( '', '' );
	$ced_walmart_admin_instance->ced_walmart_save_cat( $walmart_cat, $wooCommerce_cat , $profile_type );
}



function ced_walmart_save_template_fields( $post_data, $profile_id, $profile_type) {

	if ( ! isset( $_POST['profile_creation_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['profile_creation_submit'] ) ), 'profile_creation' ) ) {
		return;
	}
	$is_active                = isset( $_POST['profile_status'] ) ? 'Active' : 'Inactive';
	$marketplace_name         = isset( $_POST['marketplaceName'] ) ? sanitize_text_field( wp_unslash( $_POST['marketplaceName'] ) ) : 'walmart';
	$ced_walmart_profile_data = array();
	$profile_type             = isset($profile_type) ? $profile_type : '' ;

	if ( isset( $post_data['ced_walmart_required_common'] ) ) {
		$post_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		foreach ( ( $post_array['ced_walmart_required_common'] ) as $key ) {

			$array_to_save = array();
			isset( $post_array[ $key ][0] ) ? $array_to_save['default'] = trim( $post_array[ $key ][0] ) : $array_to_save['default'] = '';
			if ( '_umb_' . $marketplace_name . '_subcategory' == $key ) {
				isset( $post_array[ $key ] ) ? $array_to_save['default'] = trim( $post_array[ $key ] ) : $array_to_save['default'] = '';
			}
			isset( $post_array[ $key . '_attribute_meta' ] ) ? $array_to_save['metakey'] = $post_array[ $key . '_attribute_meta' ] : $array_to_save['metakey'] = 'null';
			$ced_walmart_profile_data[ $key ] = $array_to_save;
		}
	}
	
	$ced_walmart_profile_data    = json_encode( $ced_walmart_profile_data );
	$ced_walmart_profile_details = get_option( 'ced_mapped_cat_' . $profile_type );
	$ced_walmart_profile_details = json_decode( $ced_walmart_profile_details, 1 );

	if ( !empty($profile_id) && !empty($profile_type)) {
		
		$ced_walmart_profile_details['profile'][ $profile_id ]['profile_data'] = $ced_walmart_profile_data;
		update_option( 'ced_mapped_cat_' . $profile_type, json_encode( $ced_walmart_profile_details ) );
	}
}

?>
<form action="" method="post">
	<?php wp_nonce_field( 'profile_creation', 'profile_creation_submit' ); ?>
	<?php
	if ( empty( $profile_id ) ) {
		?>
		<p>
			<b><a id="ced_walmart_back" href="
				<?php
				echo esc_url(
					ced_get_navigation_url(
						'walmart',
						array(
							'section'  => 'templates',
							'store_id' => ced_walmart_get_current_active_store(),
						)
					)
				);
				?>
				"><span class="dashicons dashicons-arrow-left-alt2"></span></a> Create New Template</b>
			</p>
			<div>
				<?php
				include_once CED_WALMART_DIRPATH . 'admin/partials/class-ced-walmart-get-template-categories.php';
				?>
				<?php
				$template_categories = new Ced_Walmart_Get_Categories();
				?>
			</div>
			<?php

	} else {
		?>


			<div class="ced_walmart_heading">
			<?php
			if ( isset( $_GET['profile_id'] ) ) {

				?>
					<h2>
						<label><?php echo esc_html_e( 'BASIC INFORMATION', 'walmart-woocommerce-integration' ); ?></label>
					</h2>
					<div class="ced_walmart_child_element default_modal">
						<table class="form-table ced-settings widefat">
							<tr>
								
								<td>
									<label><?php esc_html_e( 'Profile ID', 'woocommerce-walmart-integration' ); ?></label>

									<p><span><?php echo esc_attr( isset( $_GET['profile_id'] ) ? sanitize_text_field( $_GET['profile_id'] ) : '' ); ?></span> </p>

								</td>
								<td>
									<label><?php esc_html_e( 'Mapped WooCommerce Categories', 'woocommerce-walmart-integration' ); ?></label>

								<?php
								$count  = 0;
								$result = get_option( 'ced_mapped_cat_' . $profile_type );
								if ( ! empty( $result ) ) {
									$result = json_decode( $result, 1 );

									if ( is_array( $result ) && isset( $result ) ) {

										foreach ( $result['profile'] as $key => $value ) {
											if ( $key == $profile_id ) {
												if ( empty( $value['woo_cat'] ) ) {
													continue;
												}
												foreach ( $value['woo_cat'] as $walmart_term_id ) {

													$terms = get_term_by( 'id', $walmart_term_id, 'product_cat' );
													echo '<p>' . esc_attr( $terms->name ) . '</p>';
													$count += $terms->count;
												}
												break;
											}
										}
									}

									?>
									</td>

									<td>
										<label> <?php esc_html_e( 'Product(s) Affected', 'woocommerce-walmart-integration' ); ?> </label>
										<p><?php echo esc_attr( $count ); ?></p>
									</td>
								</tr>
							<?php } ?>	
						</td>
					</tr>

				</table>
			</div>
		</div>

			<?php
			}
	}

	?>

<div class="ced_walmart_heading">

	<div class="components-card is-size-medium woocommerce-table">
		<div class="components-panel">
			<div class="wc-progress-form-content woocommerce-importer ced-padding">
				<div class="ced_walmart_parent_element">
					<h2>
						<label><?php echo esc_html_e( 'Product Export Settings', 'walmart-woocommerce-integration' ); ?></label>
					</h2>
				</div>
				<div class="ced_walmart_child_element default_modal">
					<div class="ced-walmart-integ-wrapper">
						<input class="ced-faq-trigger" id="ced-walmart-pro-exprt-wrapper_category" type="checkbox" checked><label class="ced-walmart-settng-title" for="ced-walmart-pro-exprt-wrapper_category"> Category Specific Attributes</label>
						<div class="ced-walmart-settng-content-wrap">
							<div class="ced-walmart-settng-content-holder">
								<div class="ced-form-accordian-wrap">
									<div class="wc-progress-form-content woocommerce-importer">
										<header>
											<table class='widefat wp-list-table widefat fixed table-view-list form-table ced-settings'>
												<tbody class="ced-settings-body">
													<?php
														
													if ( ! empty( $profile_id ) ) {
														
														include_once CED_WALMART_DIRPATH . 'admin/partials/class-ced-walmart-category-attributes.php';
														$obj = new Walmart_Category_Attributes( $profile_id , $profile_type);
														print_r( $obj->render_attributes( $profile_id , $profile_type ) );
													}
													?>

												</tbody>
											</table>

										</header>
									</div>
								</div>
							</div>
						</div>
					</div>

					<?php

					require_once CED_WALMART_DIRPATH . 'admin/partials/class-ced-walmart-product-attributes.php';
					$obj = new Ced_Walmart_Product_Attributes( $profile_id , $profile_type);
					print_r( $obj->render_main( $profile_id , $profile_type) );


					?>

					<!-- </div> -->
					<div class="ced-button-wrapper">
						<button type="submit"name="ced_walmart_profile_save_button" class="button-primary"><?php esc_html_e( 'Update Profile', 'walmart-woocommerce-integration' ); ?></button>			
					</div>
				</div>
			</div>

		</div>
	</div>
</div>

</form>
