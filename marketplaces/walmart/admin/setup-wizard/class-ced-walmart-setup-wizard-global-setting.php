<?php


class Ced_Walmart_Setup_Wizard_Global_Setting {

	public $formData = array();

	public function __construct() {
	}


	public function render_fields() {
		?>
		<div class="woocommerce-progress-form-wrapper">
			<?php
			print_r( ced_walmart_setup_wizard_bar() );
			$this->ced_walmart_submit_global_setting();
			?>

			<div class="wc-progress-form-content woocommerce-importer">
				<header>
					<h2>Global Options</h2>
					<p>Filling the following attributes can improve your listings on Walmart. </p>
				</header>
				<header>
					<form method="post" action="">
						<?php wp_nonce_field( 'ced_global_mapping', 'global_mapping' ); ?>
						<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row" class="titledesc">
										<label for="woocommerce_currency">
											Column name 
										</label>
									</th>
									<th scope="row" class="titledesc">
										<label for="woocommerce_currency">
											Map to fields
										</label>
									</th>
									<th scope="row" class="titledesc">
										<label for="woocommerce_currency">
											Custom Value 
										</label>
									</th>
								</tr>

								<?php print_r( $this->ced_walmart_render_global_fields() ); ?>
							</tbody>
						</table>
					</header>
					<div class="wc-actions">
						<button type="submit" class="components-button is-secondary">Reset all values</a>
							<button style="float: right;" type="submit" name="ced_walmart_save_global_fields" class="components-button is-primary button-next">Save and continue</button><button style="float: right;" type="submit"  name="ced_walmart_skip_global_fields"class="components-button woocommerce-admin-dismiss-notification button-next">Skip</button>
						</div>

					</form>
				</div>

			</div>

			<?php
	}



	public function ced_walmart_submit_global_setting() {
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			if ( ! isset( $_POST['global_mapping'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['global_mapping'] ) ), 'ced_global_mapping' ) ) {
				return;
			}
			$this->formData = $_POST;
		}
		if ( ! empty( $this->formData ) ) {
			$store_id = isset( $_GET['store_id'] ) ? sanitize_text_field( $_GET['store_id'] ) : '';
			if ( isset( $this->formData['ced_walmart_save_global_fields'] ) ) {

				$global_fields = $this->formData['ced_walmart_global_setting_common'];

				$all_settings = get_option( 'ced_walmart_settings', '' );

				if ( ! empty( $all_settings ) ) {
					$all_settings = json_decode( $all_settings, true );
				} else {
					$all_settings = array();
				}

				foreach ( $global_fields as $key => $value ) {

					if ( ! empty( $global_fields[ $key ]['metakey'] ) || ! empty( $global_fields[ $key ]['default'] ) ) {
						$all_settings[ $store_id ]['global_settings'][ 'global_' . $key ]['metakey'] = isset( $global_fields[ $key ]['metakey'] ) ? $global_fields[ $key ]['metakey'] : '';
						$all_settings[ $store_id ]['global_settings'][ 'global_' . $key ]['default'] = isset( $global_fields[ $key ]['default'] ) ? $global_fields[ $key ]['default'] : '';
					}
				}
				update_option( 'ced_walmart_settings', json_encode( $all_settings ) );
				$this->ced_walmart_update_status( $store_id );
			} elseif ( isset( $this->formData['ced_walmart_skip_global_fields'] ) ) {
				$this->ced_walmart_update_status( $store_id );
			}
		}
	}


	public function ced_walmart_update_status( $store_id ) {
		$account_list                                 = ced_walmart_return_partner_detail_option();
		$account_list[ $store_id ]['global_settings'] = true;
		$step = isset( $_GET['step'] ) ? sanitize_text_field( $_GET['step'] ) : '';
		ced_walmart_update_steps( $step );
		$redirect_url                              = add_query_arg(
			array(
				'action'   => 'setup-wizard',
				'step'     => 'general_setting',
				'store_id' => $store_id,
			)
		);
		$account_list[ $store_id ]['current_step'] = $redirect_url;
		update_option( 'ced_walmart_saved_account_list', json_encode( $account_list ) );
		wp_safe_redirect( esc_url_raw( $redirect_url ) );
	}


	public function ced_walmart_render_global_fields() {

		$fields_file = CED_WALMART_DIRPATH . 'admin/setup-wizard/class-ced-walmart-setup-wizard-fields.php';
		if ( file_exists( $fields_file ) ) {
			include_once $fields_file;
			$global_fields_obj = new Ced_Walmart_Setup_Wizard_Fields();
			$global_fields     = $global_fields_obj->ced_walmart_setup_wizard_global_fields();

			$all_settings = get_option( 'ced_walmart_settings', '' );

			if ( ! empty( $all_settings ) ) {
				$all_settings = json_decode( $all_settings, true );
			} else {
				$all_settings = array();
			}

			$store_id = isset( $_GET['store_id'] ) ? sanitize_text_field( $_GET['store_id'] ) : '';

			$global_settings = isset( $all_settings[ $store_id ] ) ? isset( $all_settings[ $store_id ]['global_settings'] ) ? is_array( $all_settings[ $store_id ]['global_settings'] ) ? $all_settings[ $store_id ]['global_settings'] : array() : array() : array();

			// echo "<pre>";
			// print_r($global_settings);

			if ( ! empty( $global_fields ) && is_array( $global_fields ) ) {
				$html = '';
				foreach ( $global_fields as $key => $value ) {

					$id            = $value['id'];
					$default_value = '';
					$metakey       = '';
					$temp_id       = 'global_' . $id;
					foreach ( $global_settings as $global_key => $global_value ) {

						if ( $temp_id == $global_key ) {

							$default_value = ! empty( $global_value['default'] ) ? $global_value['default'] : '';
							$metakey       = ! empty( $global_value['metakey'] ) ? $global_value['metakey'] : '';
						}
					}

					$html .= '<tr>';
					$html .= '<th scope="row" class="' . $id . '">';
					$html .= '<label for="woocommerce_currency">';
					$html .= '' . $value['label'] . '' . wc_help_tip( $value['description'], 'walmart-woocommerce-integration' );
					$html .= '</label>';
					$html .= '</th>';
					$html .= '<td class="forminp forminp-select">';
					$html .= '<select class="select2" style="width: 100%;" name="ced_walmart_global_setting_common[' . $id . '][metakey] id="bulk-action-selector-top">';
					$html .= $this->ced_walmart_return_attr_options( $metakey );
					$html .= '</select>';
					$html .= '</td>';
					$html .= '<td class="forminp forminp-select">';
					$html .= '<input type="text" style="width: 100%;" value="' . __( esc_attr( $default_value ), 'walmart-woocommerce-integration' ) . '" name="ced_walmart_global_setting_common[' . $id . '][default]" id="bulk-action-selector-top"/>';
					$html .= '</td>';
					$html .= '</tr>';
				}

				return $html;

			}
		}
	}



	public function ced_walmart_return_all_attributes() {
		$attributes           = wc_get_attribute_taxonomies();
		$attr_options         = array();
		$select_dropdown_html = '';

		if ( ! empty( $attributes ) ) {
			foreach ( $attributes as $attributes_object ) {
				$attr_options[ 'umb_pattr_' . $attributes_object->attribute_name ] = $attributes_object->attribute_label;
			}
		}

		return $attr_options;
	}


	public function ced_walmart_return_attr_options( $metakey = '' ) {
		$attr_options     = $this->ced_walmart_return_all_attributes();
		$custom_prd_attrb = $this->ced_walmart_return_product_meta();
		$html             = '<option value="" selected> -- select -- </option>';

		if ( is_array( $attr_options ) ) {
			$html .= '<optgroup label="Global Attributes">';
			foreach ( $attr_options as $attrKey => $attrName ) {
				$selected = '';
				if ( $attrKey == $metakey ) {
					$selected = 'selected';
				}

				$html .= '<option  value="' . $attrKey . '" ' . esc_attr( $selected ) . '>' . $attrName . '</option>';
			}

			$html .= '</optgroup>';
		}
		if ( ! empty( $custom_prd_attrb ) ) {
			$custom_prd_attrb = array_unique( $custom_prd_attrb );
			$html            .= '<optgroup label="Custom Attributes">';
			foreach ( $custom_prd_attrb as $key5 => $custom_attrb ) {
				$selected = '';
				if ( $custom_attrb == $metakey ) {
					$selected = 'selected';
				}
				$html .= '<option value="' . esc_attr( $custom_attrb ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $custom_attrb ) . '</option>';
			}
			$html .= '</optgroup>';
		}

		return $html;
	}


	public function ced_walmart_return_product_meta() {
		global $wpdb;
		$metakeys = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT meta_key 
					FROM {$wpdb->prefix}postmeta 
					WHERE meta_key NOT LIKE %s 
					AND meta_key NOT LIKE %s 
					AND meta_key NOT LIKE %s",
				'%wcf%',
				'%elementor%',
				'%_menu%'
			),
			'ARRAY_A'
		);

		$metakeys = array_column( $metakeys, 'meta_key' );
		$metakeys = array_merge( $metakeys, array( '_product_title', '_product_short_description', '_product_long_description', '_product_long_and_short_description', '_product_id' ) );
		$metakeys = array_combine( $metakeys, $metakeys );

		return $metakeys;
	}
}


	$obj = new Ced_Walmart_Setup_Wizard_Global_Setting();
