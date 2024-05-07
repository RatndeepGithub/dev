<?php


class Walmart_Category_Attributes {

	public function render_attributes( $profile_id, $profile_type ) {
		// Fetch Profile data from db and assigning previous seleceted value in fields .
		$profile_type = isset($profile_type) ? $profile_type : '';
		$profile_id   = isset($profile_id) ? $profile_id : '';
		
		$ced_walmart_profile_details = get_option( 'ced_mapped_cat_' . $profile_type );
		$ced_walmart_profile_details = json_decode( $ced_walmart_profile_details, 1 );
		$ced_walmart_profile_data    = isset( $ced_walmart_profile_details['profile'][ $profile_id ] ) ? $ced_walmart_profile_details['profile'][ $profile_id ] : array();

		if ( ! empty( $ced_walmart_profile_data ) ) {
			$ced_walmart_category_id = json_decode( $ced_walmart_profile_data['profile_data'], true );
			$ced_walmart_category_id = isset( $ced_walmart_category_id['_umb_walmart_category']['default'] ) ? $ced_walmart_category_id['_umb_walmart_category']['default'] : '';
		}

		$attr_options = $this->ced_walmart_return_attr_options();
		// getting product fields from file to render .
		$file_product_fields = CED_WALMART_DIRPATH . 'admin/partials/class-ced-walmart-product-fields.php';
		include_file( $file_product_fields );
		$class_product_fields_instance = new Ced_Walmart_Product_Fields();
		$ced_walmart_product_fields    = $class_product_fields_instance->ced_walmart_get_custom_products_fields();
		
		if ( ! empty( $ced_walmart_product_fields ) && ! empty( $ced_walmart_product_fields ) ) {
			$required_in_any_case = array( '_umb_id_type', '_umb_id_val', '_umb_brand' );
			$market_place         = 'ced_walmart_required_common';
			$product_id           = 0;
			$index_to_use         = 0;
			
			foreach ( $ced_walmart_product_fields as $index => $fields_data ) {
				$required = $fields_data['required'];
				$is_text  = true;
				$field_id = trim( $fields_data['fields']['id'], '_' );

				$attribute_name_to_render = ucfirst( $fields_data['fields']['label'] );

				if ( isset( $ced_walmart_profile_data['profile_data'] ) ) {
					$profile_data = json_decode( $ced_walmart_profile_data['profile_data'], true );

				} else {
					$profile_data = array();
				}
				
				$default = isset( $profile_data[ $fields_data['fields']['id'] ]['default'] ) ? $profile_data[ $fields_data['fields']['id'] ]['default'] : '';
			
				echo '<tr class="form-field _umb_id_type_field ">';
				
				if ( '_hidden' == $fields_data['type'] ) {
					$class_product_fields_instance->render_input_text_html_hidden(
						$field_id,
						$attribute_name_to_render,
						'',
						$product_id,
						$market_place,
						$fields_data['fields']['description'],
						$index_to_use,
						array(
							'case'  => 'profile',
							'value' => $profile_id,
						),
						$required
					);
					$is_text = false;
				} else {
					$is_text = true;
				}
				
				echo '<td>';
				if ( $is_text ) {
					
					$previous_selected_value = 'null';
					if ( isset( $profile_data[ $fields_data['fields']['id'] ]['metakey'] ) && 'null' != $profile_data[ $fields_data['fields']['id'] ]['metakey'] ) {
						$previous_selected_value = $profile_data[ $fields_data['fields']['id'] ]['metakey'];
					}

					$select_id = $fields_data['fields']['id'] . '_attribute_meta';
					?>
					<select id="<?php echo esc_attr( $select_id ); ?>" name="<?php echo esc_attr( $select_id ); ?>">
						<option value="null" selected> -- select -- </option>
						<?php
						if ( is_array( $attr_options ) ) {

							foreach ( $attr_options as $attr_key => $attr_name ) :
								if ( trim( $previous_selected_value ) == $attr_key ) {
									$selected = 'selected';
								} else {
									$selected = '';
								}
								?>
								<option value="<?php echo esc_attr( $attr_key ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_attr( $attr_name ); ?></option>
								<?php
							endforeach;
						}
						?>
					</select>
					<?php
					
				}
				echo '</td>';
				echo '</tr>';
				
			}
		}

		$get_profile_name = $profile_id;
		$profile_type     = $profile_type;
		$category_data    = json_decode(get_option( 'ced_walmart_category_attribute_' . $get_profile_name . $profile_type ), true);
		
		if ( isset($category_data) && is_array($category_data) && !empty($category_data)) {
			
			$category_data = $category_data;
			$cat_search    = $category_data;
			
		} else {

			$ced_walmart_process_request_file = CED_WALMART_DIRPATH . 'admin/walmart/lib/class-ced-walmart-process-request.php';

			include_file( $ced_walmart_process_request_file );
			$this->ced_walmart_process_request_instance = Ced_Walmart_Remote_Request::get_instance();

			$action     = 'category_attributes';
			$query_args = array('id' => $get_profile_name, 'type' => 'MP_ITEM');
			$response   = $this->ced_walmart_process_request_instance->ced_walmart_process_request( $action, array(), $query_args, 'GET' );
			$schema     = json_decode( $response['body'], true );
			$cat_search = isset($schema['result']['Visible']) ? $schema['result']['Visible'] : array();
			
			update_option( 'ced_walmart_category_attribute_' . $get_profile_name . $profile_type, json_encode($cat_search));
		}
		
		if (isset($cat_search) && !empty($cat_search)) {
			
			$market_place                  = 'ced_walmart_required_common';
			$index_to_use                  = 0;
			$product_id                    = 0;
			$attribute_data_required_label = '';
			$attribute_data_required       = false;
			$type_walmart_cat_attribute    = array();
			$temp                          = array();
			
			$attributes_to_escape = array( 'sku', 'productIdentifiers', 'productIdentifier', 'productIdType', 'productIdentifiers', 'productIdentifier', 'productId', 'productName', 'keyFeatures', 'keyFeaturesValue', 'shortDescription', 'mainImageUrl', 'MPOffer', 'productTaxCode', 'MPOffer', 'price', 'MPOffer', 'MinimumAdvertisedPrice', 'MPOffer', 'StartDate', 'MPOfferEndDate', 'MPOffer', 'ShippingWeight', 'measure', 'MPOffer', 'unit', 'brand', 'variantGroupId', 'additionalProductAttributes', 'isPrimaryVariant', 'swatchImages', 'variantAttributeNames' );

			if (!empty($cat_search) && is_array($cat_search)) {
				
				$key = str_replace( ' ', '', $get_profile_name );
				
				update_option( 'ced_walmart_cat_visible_' . $profile_type . '_' . $get_profile_name, json_encode( unserialize( str_replace( array( 'NAN;', 'INF;' ), '0;', serialize( $cat_search['properties'] ) ) ) ) );

			
				$merged_preapre          = $cat_search['properties'];
				$merged_preapre_required = $cat_search['required'];
				$variationAttr           = isset($cat_search['properties']['variantAttributeNames']) ? $cat_search['properties']['variantAttributeNames']['items']['enum'] : array();

				foreach ( $merged_preapre as $attributesKey => $attributesValue ) {
					
					if ( in_array( $attributesKey, $attributes_to_escape ) ) {
						if ( 'variantAttributeNames' == $attributesKey ) {
							$temp = $attributesValue;
						}

						continue;
					}

					$attribute_data_required_label = '';
					$attribute_data_required       = false;
					$objectFulfilled               = '';
					$isVariationExist              = false;
					if ( in_array( $attributesKey, $merged_preapre_required ) ) {
						$attribute_data_required_label = 'Required';
						$attribute_data_required       = true;
					}

					if ( in_array( $attributesKey, $variationAttr ) ) {
						$isVariationExist = true;
					}

					if ( 'object' == $attributesValue['type'] ) {
						foreach ( $attributesValue['properties'] as $objectkey => $objectvalue ) {

							if ( ! isset( $objectvalue['enum'] ) && ! isset( $objectvalue['items']['enum'] ) ) {
								$isText          = true;
								$objectFulfilled = $attributesKey;
								if ( isset( $objectvalue['title'] ) && ! empty( $objectvalue['title'] ) ) {
									$field_name_to_render = ucfirst( $objectvalue['title'] );
								} else {
									continue;
								}
								echo '<tr>';
								$type_walmart_cat = 'text';
								$input_type       = 'text';
								if ( 'integer' == $objectvalue['type'] || 'number' == $objectvalue['type'] ) {
									$type_walmart_cat = 'number';
								} elseif ( isset( $objectvalue['format'] ) && 'date-time' == $objectvalue['format'] ) {
									$type_walmart_cat = 'datetime-local';
									$input_type       = 'datetime-local';
								} elseif ( isset( $objectvalue['format'] ) && 'uri' == $objectvalue['format'] ) {
									$type_walmart_cat = 'url';
									$input_type       = 'text';
								}
								$type_walmart_cat_attribute[ $attributesKey . '_' . $objectkey ] = $type_walmart_cat;
								$field_id   = $key . '_' . $attributesKey . '_' . str_replace( ' ', '', $objectkey );
								$field_data = isset( $profile_data[ $field_id ] ) ? $profile_data[ $field_id ] : array();
								
								$default = isset( $field_data['default'] ) ? $field_data['default'] : null;
								$metakey = isset( $field_data['metakey'] ) ? $field_data['metakey'] : null;
								$class_product_fields_instance->ced_walmart_render_text_html(
									$attributesKey . '_' . $objectkey,
									$field_name_to_render,
									$key,
									$product_id,
									$market_place,
									$objectvalue['title'],
									$index_to_use,
									array(
										'case'  => 'profile',
										'value' => $default,
									),
									$attribute_data_required,
									false,
									$attribute_data_required_label,
									$input_type,
									$objectFulfilled,
									$isVariationExist
								);

								echo '<td>';
								if ( $isText ) {
									$previous_selected_value = 'null';
									if ( isset( $profile_data[ $key . '_' . $attributesKey . '_' . str_replace( ' ', '', $objectkey ) ] ) && 'null' != $profile_data[ $key . '_' . $attributesKey . '_' . str_replace( ' ', '', $objectkey ) ]['metakey'] ) {
										$previous_selected_value = $profile_data[ $key . '_' . $attributesKey . '_' . str_replace( ' ', '', $objectkey ) ]['metakey'];
									}
									$select_id = $key . '_' . $attributesKey . '_' . str_replace( ' ', '', $objectkey ) . '_attribute_meta';
									?>
									<select id="<?php echo esc_attr( $select_id ); ?>" name="<?php echo esc_attr( $select_id ); ?>[]" multiple>
										<option value="null"> -- select -- </option>
										<?php

										if ( is_array( $attr_options ) ) {
											foreach ( $attr_options as $attr_key => $attr_name ) :

												if ( is_array( $previous_selected_value ) ) {
													if ( in_array( $attr_key, $previous_selected_value ) ) {
														$selected = 'selected';
													} else {
														$selected = '';
													}
												}

												?>
												<option value="<?php echo esc_attr( $attr_key ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_attr( $attr_name ); ?></option>
												<?php
											endforeach;
										}
										?>
									</select>
									<?php
								}
								echo '</td>';
								echo '</tr>';
							} elseif ( isset( $objectvalue['enum'] ) && ! empty( $objectvalue['enum'] ) ) {
								$temp_value_for_dropdown = array();
								$type_walmart_cat        = 'text';
								if ( 'integer' == $objectvalue['type'] || 'number' == $objectvalue['type'] ) {
									$type_walmart_cat = 'number';
								} elseif ( isset( $objectvalue['format'] ) && 'date-time' == $objectvalue['format'] ) {
									$type_walmart_cat = 'datetime-local';
								} elseif ( isset( $objectvalue['format'] ) && 'uri' == $objectvalue['format'] ) {
									$type_walmart_cat = 'url';
								}
								$type_walmart_cat_attribute[ $attributesKey . '_' . $objectkey ] = $type_walmart_cat;
								foreach ( $objectvalue['enum'] as $key_attributesValue => $value__attributesValue ) {
									$temp_value_for_dropdown[ $value__attributesValue ] = $value__attributesValue;

								}
								$value_for_dropdown   = $temp_value_for_dropdown;
								$field_name_to_render = $objectvalue['title'];
								$objectFulfilled      = $attributesKey;
								$field_id             = $key . '_' . $attributesKey . '_' . str_replace( ' ', '', $objectkey );

								$field_data = isset( $profile_data[ $field_id ] ) ? $profile_data[ $field_id ] : array();
								$default    = isset( $field_data['default'] ) ? $field_data['default'] : null;
								$metakey    = isset( $field_data['metakey'] ) ? $field_data['metakey'] : null;
								$class_product_fields_instance->ced_walmart_render_dropdown_html(
									$attributesKey . '_' . $objectkey,
									$field_name_to_render,
									$value_for_dropdown,
									$key,
									$product_id,
									$market_place,
									$objectvalue['title'],
									$index_to_use,
									array(
										'case'  => 'profile',
										'value' => $default,
									),
									$attribute_data_required,
									$attribute_data_required_label,
									$objectFulfilled,
									$isVariationExist
								);

								$isText = false;
							}
						}
					}

					$isText = true;
					if ( isset( $attributesValue['title'] ) && ! empty( $attributesValue['title'] ) ) {
						$field_name_to_render = ucfirst( $attributesValue['title'] );
					} else {
						continue;
					}
					echo '<tr>';
					if ( ! isset( $attributesValue['enum'] ) && ! isset( $attributesValue['items']['enum'] ) && ! isset( $attributesValue['items']['properties'] ) ) {

						$type_walmart_cat = 'text';
						$input_type       = 'text';
						if ( 'integer' == $attributesValue['type'] || 'number' == $attributesValue['type'] ) {
							$type_walmart_cat = 'number';
						} elseif ( isset( $attributesValue['format'] ) && 'date-time' == $attributesValue['format'] ) {
							$type_walmart_cat = 'datetime-local';
							$input_type       = 'datetime-local';
						} elseif ( isset( $attributesValue['format'] ) && 'uri' == $attributesValue['format'] ) {
							$type_walmart_cat = 'url';
							$input_type       = 'text';
						} elseif ( 'array' == $attributesValue['type'] ) {
							$type_walmart_cat = 'array';
						}
						$type_walmart_cat_attribute[ $attributesKey ] = $type_walmart_cat;
						$field_id                                     = $key . '_' . str_replace( ' ', '', $attributesKey );
						$field_data                                   = isset( $profile_data[ $field_id ] ) ? $profile_data[ $field_id ] : array();

						$default = isset( $field_data['default'] ) ? $field_data['default'] : null;
						$metakey = isset( $field_data['metakey'] ) ? $field_data['metakey'] : null;
						$class_product_fields_instance->ced_walmart_render_text_html(
							$attributesKey,
							$field_name_to_render,
							$key,
							$product_id,
							$market_place,
							$attributesValue['title'],
							$index_to_use,
							array(
								'case'  => 'profile',
								'value' => $default,
							),
							$attribute_data_required,
							false,
							$attribute_data_required_label,
							$input_type,
							$objectFulfilled,
							$isVariationExist
						);

						echo '<td>';
						if ( $isText ) {
							$previous_selected_value = 'null';
							if ( isset( $profile_data[ $key . '_' . str_replace( ' ', '', $attributesKey ) ] ) && 'null' != $profile_data[ $key . '_' . str_replace( ' ', '', $attributesKey ) ]['metakey'] ) {
								$previous_selected_value = $profile_data[ $key . '_' . str_replace( ' ', '', $attributesKey ) ]['metakey'];
							}
							$select_id = $key . '_' . str_replace( ' ', '', $attributesKey ) . '_attribute_meta';
							?>
							<select id="<?php echo esc_attr( $select_id ); ?>" name="<?php echo esc_attr( $select_id ); ?>[]" multiple>
								<option value="null"> -- select -- </option>
								<?php

								if ( is_array( $attr_options ) ) {
									foreach ( $attr_options as $attr_key => $attr_name ) :
										$selected = '';
										if ( is_array( $previous_selected_value ) ) {
											if ( in_array( $attr_key, $previous_selected_value ) ) {
												$selected = 'selected';
											} else {
												$selected = '';
											}
										}

										?>
										<option value="<?php echo esc_attr( $attr_key ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_attr( $attr_name ); ?></option>
										<?php
									endforeach;
								}
								?>
							</select>
							<?php
						}
						echo '</td>';
						echo '</tr>';

					} elseif ( isset( $attributesValue['enum'] ) && ! empty( $attributesValue['enum'] ) ) {
						$type_walmart_cat = 'text';
						if ( 'integer' == $attributesValue['type'] || 'number' == $attributesValue['type'] ) {
							$type_walmart_cat = 'number';
						} elseif ( isset( $attributesValue['format'] ) && 'date-time' == $attributesValue['format'] ) {
							$type_walmart_cat = 'datetime-local';
						} elseif ( isset( $attributesValue['format'] ) && 'uri' == $attributesValue['format'] ) {
							$type_walmart_cat = 'url';
						}
						$type_walmart_cat_attribute[ $attributesKey ] = $type_walmart_cat;
						$temp_value_for_dropdown                      = array();
						foreach ( $attributesValue['enum'] as $key_attributesValue => $value__attributesValue ) {
							$temp_value_for_dropdown[ $value__attributesValue ] = $value__attributesValue;
						}
						$value_for_dropdown = $temp_value_for_dropdown;
						$field_id           = $key . '_' . str_replace( ' ', '', $attributesKey );
						$field_data         = isset( $profile_data[ $field_id ] ) ? $profile_data[ $field_id ] : array();
						$default            = isset( $field_data['default'] ) ? $field_data['default'] : null;
						$metakey            = isset( $field_data['metakey'] ) ? $field_data['metakey'] : null;
						$class_product_fields_instance->ced_walmart_render_dropdown_html(
							$attributesKey,
							$field_name_to_render,
							$value_for_dropdown,
							$key,
							$product_id,
							$market_place,
							$attributesValue['title'],
							$index_to_use,
							array(
								'case'  => 'profile',
								'value' => $default,
							),
							$attribute_data_required,
							$attribute_data_required_label,
							$objectFulfilled,
							$isVariationExist
						);
						$isText = false;
					} elseif ( 'array' == $attributesValue['type'] ) {
						if ( isset( $attributesValue['items']['enum'] ) && ! empty( $attributesValue['items']['enum'] ) ) {

							$temp_value_for_dropdown = array();
							foreach ( $attributesValue['items']['enum'] as $key_attributesValue => $value__attributesValue ) {
								$temp_value_for_dropdown[ $value__attributesValue ] = $value__attributesValue;
							}
							$type_walmart_cat                             = 'array';
							$type_walmart_cat_attribute[ $attributesKey ] = $type_walmart_cat;

							$value_for_dropdown = $temp_value_for_dropdown;
							$field_id           = $key . '_' . str_replace( ' ', '', $attributesKey );
							$field_data         = isset( $profile_data[ $field_id ] ) ? $profile_data[ $field_id ] : array();
							$default            = isset( $field_data['default'] ) ? $field_data['default'] : null;
							$metakey            = isset( $field_data['metakey'] ) ? $field_data['metakey'] : null;
							$class_product_fields_instance->ced_walmart_render_dropdown_html(
								$attributesKey,
								$field_name_to_render,
								$value_for_dropdown,
								$key,
								$product_id,
								$market_place,
								$attributesValue['title'],
								$index_to_use,
								array(
									'case'  => 'profile',
									'value' => $default,
								),
								$attribute_data_required,
								$attribute_data_required_label,
								$objectFulfilled,
								$isVariationExist
							);

							$isText = false;

						} elseif ( isset( $attributesValue['items']['type'] ) && 'object' == $attributesValue['items']['type'] ) {

							foreach ( $attributesValue['items']['properties'] as $objectkey => $objectvalue ) {

								if ( isset( $objectvalue['enum'] ) ) {
									$temp_value_for_dropdown = array();
									$type_walmart_cat        = 'array';

									$type_walmart_cat_attribute[ $attributesKey . '_' . $objectkey ] = $type_walmart_cat;

									foreach ( $objectvalue['enum'] as $key_attributesValue => $value__attributesValue ) {
										$temp_value_for_dropdown[ $value__attributesValue ] = $value__attributesValue;

									}
									$value_for_dropdown   = $temp_value_for_dropdown;
									$field_name_to_render = $objectvalue['title'];
									$objectFulfilled      = $attributesKey;
									$field_id             = $key . '_' . $attributesKey . '_' . str_replace( ' ', '', $objectkey );

									$field_data = isset( $profile_data[ $field_id ] ) ? $profile_data[ $field_id ] : array();
									$default    = isset( $field_data['default'] ) ? $field_data['default'] : null;
									$metakey    = isset( $field_data['metakey'] ) ? $field_data['metakey'] : null;
									$class_product_fields_instance->ced_walmart_render_dropdown_html(
										$attributesKey . '_' . $objectkey,
										$field_name_to_render,
										$value_for_dropdown,
										$key,
										$product_id,
										$market_place,
										$objectvalue['title'],
										$index_to_use,
										array(
											'case'  => 'profile',
											'value' => $default,
										),
										$attribute_data_required,
										$attribute_data_required_label,
										$objectFulfilled,
										$isVariationExist
									);

									$isText = false;

								} else {

									$isText          = true;
									$objectFulfilled = $attributesKey;
									if ( isset( $objectvalue['title'] ) && ! empty( $objectvalue['title'] ) ) {
										$field_name_to_render = ucfirst( $objectvalue['title'] );
									} else {
										continue;
									}
									echo '<tr>';
									$type_walmart_cat = 'array';

									if ( 'integer' == $objectvalue['type'] || 'number' == $objectvalue['type'] ) {
										$input_type = 'text';
									} elseif ( isset( $objectvalue['format'] ) && 'date-time' == $objectvalue['format'] ) {

										$input_type = 'datetime-local';
									} elseif ( isset( $objectvalue['format'] ) && 'uri' == $objectvalue['format'] ) {
										$input_type = 'text';
									}
									$type_walmart_cat_attribute[ $attributesKey . '_' . $objectkey ] = $type_walmart_cat;
									$field_id   = $key . '_' . $attributesKey . '_' . str_replace( ' ', '', $objectkey );
									$field_data = isset( $profile_data[ $field_id ] ) ? $profile_data[ $field_id ] : array();

									$default = isset( $field_data['default'] ) ? $field_data['default'] : null;
									$metakey = isset( $field_data['metakey'] ) ? $field_data['metakey'] : null;
									$class_product_fields_instance->ced_walmart_render_text_html(
										$attributesKey . '_' . $objectkey,
										$field_name_to_render,
										$key,
										$product_id,
										$market_place,
										$objectvalue['title'],
										$index_to_use,
										array(
											'case'  => 'profile',
											'value' => $default,
										),
										false,
										false,
										$attribute_data_required_label,
										$input_type,
										$objectFulfilled,
										$isVariationExist
									);

									echo '<td>';
									if ( $isText ) {
										$previous_selected_value = 'null';
										if ( isset( $profile_data[ $key . '_' . $attributesKey . '_' . str_replace( ' ', '', $objectkey ) ] ) && 'null' != $profile_data[ $key . '_' . $attributesKey . '_' . str_replace( ' ', '', $objectkey ) ]['metakey'] ) {
											$previous_selected_value = $profile_data[ $key . '_' . $attributesKey . '_' . str_replace( ' ', '', $objectkey ) ]['metakey'];
										}
										$select_id = $key . '_' . $attributesKey . '_' . str_replace( ' ', '', $objectkey ) . '_attribute_meta';
										?>
										<select id="<?php echo esc_attr( $select_id ); ?>" name="<?php echo esc_attr( $select_id ); ?>[]" multiple class="select2">
											<option value="null"> -- select -- </option>
											<?php

											if ( is_array( $attr_options ) ) {
												foreach ( $attr_options as $attr_key => $attr_name ) :
													$selected = '';
													if ( is_array( $previous_selected_value ) ) {
														if ( in_array( $attr_key, $previous_selected_value ) ) {
															$selected = 'selected';
														} else {
															$selected = '';
														}
													}

													?>
													<option value="<?php echo esc_attr( $attr_key ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_attr( $attr_name ); ?></option>
													<?php
												endforeach;
											}
											?>
										</select>
										<?php
									}
									echo '</td>';
									echo '</tr>';

								}
							}
						}
					}
				}

				if ( ! empty( $temp ) ) {
					foreach ( $temp['items']['enum'] as $variantAttributeName ) {
						update_option( 'ced_walmart_cat_visible_variable_attr_' . $profile_type . '_' . $get_profile_name, json_encode( $temp['items']['enum'] ) );
					}
				}
				
			}
					
			update_option( 'ced_walmart_cat_type_attribute_' . $profile_type . '_' . $get_profile_name , json_encode( $type_walmart_cat_attribute ) );
		}
		
	}

	public function ced_walmart_return_attr_options() {

		$global_attr  = $this->ced_walmart_return_global_attributes();
		$local_attr   = $this->ced_walmart_return_product_meta();
		$attr_options = array();

		if ( is_array( $global_attr ) && is_array( $local_attr ) ) {
			$attr_options = array_unique( array_merge( $global_attr, $local_attr ) );
		}

		return $attr_options;
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


	public function ced_walmart_return_global_attributes() {
		$attributes  = wc_get_attribute_taxonomies();
		$global_attr = array();
		if ( ! empty( $attributes ) ) {
			foreach ( $attributes as $attributes_object ) {
				$global_attr[ 'umb_pattr_' . $attributes_object->attribute_name ] = $attributes_object->attribute_label;
			}

			return $global_attr;
		}

		return $global_attr;
	}
}
