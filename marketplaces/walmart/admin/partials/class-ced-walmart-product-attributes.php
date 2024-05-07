<?php




class Ced_Walmart_Product_Attributes {



	public function render_main( $profile_id = '' ) {

		$global_fields_file = CED_WALMART_DIRPATH . 'admin/walmart/lib/json/walmart-global-setting.json';
		$global_fields      = file_get_contents( $global_fields_file );
		$global_fields      = json_decode( $global_fields, true );

		$fields_file = CED_WALMART_DIRPATH . 'admin/partials/class-ced-walmart-product-fields.php';
		if ( file_exists( $fields_file ) ) {
			include_once $fields_file;
			$product_field_instance = Ced_Walmart_Product_Fields::get_instance();
		}

		$attr_options_file = CED_WALMART_DIRPATH . 'admin/partials/class-ced-walmart-category-attributes.php';
		$attr_options      = array();
		if ( file_exists( $attr_options_file ) ) {
			include_once $attr_options_file;
			$attr_options_file = new Walmart_Category_Attributes();
			$attr_options      = $attr_options_file->ced_walmart_return_attr_options();
		}

		// Fetch Profile data from db and assing previous seleceted value in fields .
		$ced_walmart_profile_details = get_option( 'ced_mapped_cat' );
		$ced_walmart_profile_details = json_decode( $ced_walmart_profile_details, 1 );
		$ced_walmart_profile_data    = isset( $ced_walmart_profile_details['profile'][ $profile_id ] ) ? $ced_walmart_profile_details['profile'][ $profile_id ] : array();

		if ( ! empty( $ced_walmart_profile_data ) ) {
			$ced_walmart_category_id = json_decode( $ced_walmart_profile_data['profile_data'], true );
			$ced_walmart_category_id = isset( $ced_walmart_category_id['_umb_walmart_category']['default'] ) ? $ced_walmart_category_id['_umb_walmart_category']['default'] : '';
		}

		$table_data               = $this->ced_walmart_product_specific_attributes( $global_fields, $attr_options, $product_field_instance, $ced_walmart_profile_data, 'product' );
		$table_data_for_orderable = $this->ced_walmart_product_specific_attributes( $global_fields, $attr_options, $product_field_instance, $ced_walmart_profile_data, 'orderable' );

		print_r( $this->ced_walmart_render_section_html( 'Product Specific Attributes', $table_data ) );
		print_r( $this->ced_walmart_render_section_html( 'Orderable Specific Attributes', $table_data_for_orderable ) );
	}


	public function ced_walmart_product_specific_attributes( $global_fields = array(), $attr_options = array(), $product_field_instance = '', $global_data = array(), $case = '' ) {

		$global_data = isset( $global_data['profile_data'] ) ? json_decode( $global_data['profile_data'], 1 ) : array();

		$store_id = isset( $_GET['store_id'] ) ? sanitize_text_field( $_GET['store_id'] ) : '';

		$html = '';

		if ( ! empty( $global_fields ) && ! empty( $global_fields ) ) {
			$global_data    = isset( $global_data ) ? $global_data : array();
			$market_place   = 'ced_walmart_required_common';
			$product_id     = 0;
			$index_to_use   = 0;
			$ced_walmart_id = 'global';

			// echo "<pre>";
			// print_r($global_data);
			// die;

			// Fetching Shipping Template
			$shipping_template_array        = array();
			$ced_walmart_shipping_templates = get_option( 'ced_walmart_shipping_templates' . $store_id . wifw_environment() );
			$ced_walmart_shipping_templates = json_decode( $ced_walmart_shipping_templates, 1 );
			$shipping_template_array        = array(
				'code'           => 'shipping_template',
				'default_value'  => null,
				'description'    => 'Add Shipping Template for item on Walmart.',
				'label'          => 'Shipping Templates',
				'required'       => false,
				'type'           => 'LIST',
				'type_parameter' => null,
				'values'         => null,
			);
			if ( isset( $ced_walmart_shipping_templates ) && is_array( $ced_walmart_shipping_templates ) ) {
				foreach ( $ced_walmart_shipping_templates['shippingTemplates'] as $key => $value ) {
					$shipping_template_array['values_list'][] = array(
						'code'  => $value['id'],
						'label' => $value['name'],
					);
				}
			}

					// Fetching Fulfillment Centers
			$fulfillment_center_array        = array();
			$ced_walmart_fulfillment_centers = get_option( 'ced_walmart_fulfillment_center' . $store_id . wifw_environment() );
			$ced_walmart_fulfillment_centers = json_decode( $ced_walmart_fulfillment_centers, 1 );
			$fulfillment_center_array        = array(
				'code'           => 'fulfillment_center',
				'default_value'  => null,
				'description'    => 'Add Fulfillment Center for  Template for item on Walmart.',
				'label'          => 'Fulfillment Center',
				'required'       => false,
				'type'           => 'LIST',
				'type_parameter' => null,
				'values'         => null,
			);
			if ( isset( $ced_walmart_fulfillment_centers ) && is_array( $ced_walmart_fulfillment_centers ) ) {
				foreach ( $ced_walmart_fulfillment_centers as $key => $value ) {
					$fulfillment_center_array['values_list'][] = array(
						'code'  => $value['shipNode'],
						'label' => $value['shipNodeName'],
					);
				}
			}

			$global_fields['shipping_specific'] = array_merge( $global_fields['shipping_specific'], array( $shipping_template_array ), array( $fulfillment_center_array ) );

			foreach ( $global_fields as $key => $value ) {
				$class = '';

				if ( ! empty( $case ) && 'orderable' == $case ) {
					if ( 'product_specific' == $key || 'price_specific' == $key || 'shipping_specific' == $key ) {
						continue;
					}
				} elseif ( 'orderable_specific' == $key ) {
						continue;
				}

				foreach ( $value as $index => $fields_data ) {
					$is_add_html    = false;
					$is_text        = true;
					$required       = isset( $fields_data['required'] ) ? $fields_data['required'] : false;
					$required_label = '*';
					$description    = isset( $fields_data['description'] ) ? $fields_data['description'] : '';
					if ( empty( $description ) ) {
						$description = isset( $fields_data['label'] ) ? $fields_data['label'] : '';
					}
					$field_id   = trim( $fields_data['code'] );
					$field_name = $ced_walmart_id . '_' . $field_id;

					$default = isset( $global_data[ $field_name ] ) ? $global_data[ $field_name ] : '';

					$default = isset( $default['default'] ) ? $default['default'] : '';

					$html .= '<tr class="form-field ' . esc_attr( $key ) . '">';
					if ( 'LIST' == $fields_data['type'] ) {
						$value_for_dropdown = ! empty( $fields_data['values_list'] ) ? $fields_data['values_list'] : array();

						$html .= '<input type="hidden" name="' . esc_attr( $market_place ) . '[]" value="' . esc_attr( $field_name ) . '"/>';
						$html .= '<td>';
						$html .= '<label for=""><b> ' . esc_attr( ucfirst( $fields_data['label'] ) ) . ' </b> </label';
						$html .= '</td>';
						$html .= '<td>';

						$html .= '<select id=""  name="' . esc_attr( $field_name . '[' . $index_to_use . ']' ) . '" class="" style="">';
						$html .= '<option value="">' . esc_html( __( 'Select', 'walmart-woocommerce-integration' ) ) . '</option>';
						foreach ( $value_for_dropdown as $dropdown_key => $dropdown_value ) {
							if ( isset( $dropdown_value['code'] ) ) {
								if ( $default == $dropdown_value['code'] ) {
									$html .= '<option value="' . esc_attr( $dropdown_value['code'] ) . '" selected>' . esc_attr( $dropdown_value['label'] ) . '</option>';
								} else {
									$html .= '<option value="' . esc_attr( $dropdown_value['code'] ) . '">' . esc_attr( $dropdown_value['label'] ) . '</option>';
								}
							} elseif ( $default == $dropdown_key ) {
									$html .= '<option value="' . esc_attr( $dropdown_key ) . '" selected>' . esc_attr( $dropdown_value ) . '</option>';
							} else {
								$html .= '<option value="' . esc_attr( $dropdown_key ) . '">' . esc_attr( $dropdown_value ) . '</option>';
							}
						}
						$html   .= '</select>';
						$html   .= '</td>';
						$is_text = false;
					} else {
						$is_text = true;
						$html   .= '<input type="hidden" name="' . esc_attr( $market_place ) . '[]" value="' . esc_attr( $field_name ) . '"/>';
						$html   .= '<td>';
						$html   .= '<label for=""><b> ' . esc_attr( ucfirst( $fields_data['label'] ) ) . ' </b> </label';
						$html   .= '</td>';
						$html   .= '<td>';
						$html   .= '<input class="short" style="" name="' . esc_attr( $field_name . '[' . $index_to_use . ']' ) . '" id="" value="' . esc_attr( $default ) . '" placeholder="" type="text" />';
						$html   .= '</td>';
					}
					$html .= '<td>';
					if ( $is_text ) {
						$previous_selected_value = 'null';
						if ( isset( $global_data[ $field_name ] ) && ! empty( $global_data[ $field_name ] ) ) {
							$previous_selected_value = $global_data[ $field_name ]['metakey'];
						}
						$select_id = $ced_walmart_id . '_' . $fields_data['code'] . '_attribute_meta';

						$html .= '<select id="' . esc_attr( $select_id ) . '" name="' . esc_attr( $select_id ) . '[]"  multiple class="select2">';

						if ( is_array( $attr_options ) ) {
							$selected = '';
							foreach ( $attr_options as $attr_key => $attr_name ) :
								if ( is_array( $previous_selected_value ) ) {
									if ( in_array( $attr_key, $previous_selected_value ) ) {
										$selected = 'selected';
									} else {
										$selected = '';
									}
								}

								$html .= '<option value="' . esc_attr( $attr_key ) . '"  ' . esc_attr( $selected ) . '> ' . esc_attr( $attr_name ) . '</option>';

							endforeach;
						}

						$html .= '</select>';
					}
					$html .= '</td>';
					$html .= '</tr>';

				}
			}
			return $html;

		}
	}


	public function ced_walmart_render_section_html( $parent_section_name = '', $table_data = '' ) {
		$html  = '<div class="ced-walmart-integ-wrapper">';
		$html .= '<input class="ced-faq-trigger" id="ced-walmart-pro-exprt-' . strtolower( str_replace( ' ', '', $parent_section_name ) ) . '" type="checkbox" >';
		$html .= '<label class="ced-walmart-settng-title" for="ced-walmart-pro-exprt-' . strtolower( str_replace( ' ', '', $parent_section_name ) ) . '">';
		$html .= esc_attr__( $parent_section_name, 'walmart-woocommerce-integration' );
		$html .= '</label>';
		$html .= '<div class="ced-walmart-settng-content-wrap">';
		$html .= '<div class="ced-walmart-settng-content-holder">';
		$html .= '<div class="ced-form-accordian-wrap">';
		$html .= '<div class="wc-progress-form-content woocommerce-importer">';
		$html .= '<header>';
		$html .= '<table class="widefat wp-list-table widefat fixed table-view-list form-table ced-settings">';
		$html .= '<tbody class="ced-settings-body-' . strtolower( str_replace( ' ', '', $parent_section_name ) ) . '">';
		$html .= $table_data;
		$html .= '</tbody>';
		$html .= '</table>';
		$html .= '</header>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}
}
