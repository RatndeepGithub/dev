<?php
if ( ! class_exists( 'EbayTemplateCustomMetabox' ) ) {

	class EbayTemplateCustomMetabox {
		public $site_id;
		public $user_id;
		public $profile_id;
		public $ebay_cat_id;
		public $product_id;
		public function __construct( $user_id, $site_id, $profile_id, $ebay_cat_id, $product_id ) {
			$this->user_id     = $user_id;
			$this->site_id     = $site_id;
			$this->profile_id  = $profile_id;
			$this->ebay_cat_id = $ebay_cat_id;
			$this->product_id  = $product_id;

			$fileCategory          = CED_EBAY_DIRPATH . 'admin/ebay/lib/cedGetcategories.php';
			$fileFields            = CED_EBAY_DIRPATH . 'admin/partials/products_fields_for_meta_box.php';
			$ebayAuthorizationFile = CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayAuthorization.php';
			if ( file_exists( $fileCategory ) ) {
				require_once $fileCategory;
			}
			if ( file_exists( $fileFields ) ) {
				require_once $fileFields;
			}
			if ( file_exists( $ebayAuthorizationFile ) ) {
				require_once $ebayAuthorizationFile;
			}
		}

		public function ced_ebay_get_general_profile_section() {
			$user_id             = $this->user_id;
			$site_id             = $this->site_id;
			$profileID           = $this->profile_id;
			$product_id          = $this->product_id;
			$profile_category_id = $this->ebay_cat_id;

			$shop_data = ced_ebay_get_shop_data( $user_id );
			if ( ! empty( $shop_data ) ) {
				$token       = $shop_data['access_token'];
				$getLocation = $shop_data['location'];
			}

			$profile_data_product_level = get_post_meta( $product_id, 'ced_ebay_product_level_profile_data', true );
			if ( isset( $profile_data_product_level[ $user_id . '>' . $site_id ] ) &&
			! empty( $profile_data_product_level[ $user_id . '>' . $site_id ] ) &&
			isset( $profile_data_product_level[ $user_id . '>' . $site_id ]['_umb_ebay_profile_id']['default'] ) &&
			$profileID == $profile_data_product_level[ $user_id . '>' . $site_id ]['_umb_ebay_profile_id']['default'] ) {
				$profile_data                 = $profile_data_product_level[ $user_id . '>' . $site_id ];
				$profile_data['profile_data'] = json_encode( $profile_data );
			} else {
				global $wpdb;
				$tableName    = $wpdb->prefix . 'ced_ebay_profiles';
				$profile_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_ebay_profiles WHERE `id`=%d AND `ebay_user`=%s AND `ebay_site`=%s", $profileID, $user_id, $site_id ), 'ARRAY_A' );
				if ( ! empty( $profile_data ) ) {
					$woo_categories        = ! empty( $profile_data[0]['woo_categories'] ) ? json_decode( $profile_data[0]['woo_categories'], true ) : false;
					$profile_category_data = json_decode( $profile_data[0]['profile_data'], true );
				}
				$profile_category_data = isset( $profile_category_data ) ? $profile_category_data : '';
				$profile_category_id   = isset( $profile_category_data['_umb_ebay_category']['default'] ) ? $profile_category_data['_umb_ebay_category']['default'] : '';
				$profile_data          = isset( $profile_data[0] ) ? $profile_data[0] : $profile_data;
			}

			$selectDropdownHTML = '';
			/* select dropdown setup */
			ob_start();

			$productFieldInstance = CedeBayProductsFieldsMetaBox::get_instance();
			$fields               = $productFieldInstance->ced_ebay_get_custom_products_fields( $user_id, $profile_category_id, $site_id );

			$woo_store_categories = get_terms( 'product_cat' );
			?>
			<table class="ced-ebay-profile-edit-table">
				<tbody>
					<tr>
					<th colspan="3" class="ced-profile-general-settings-heading-product-level ced-profile-general-settings-heading" style="text-align:left;margin:0;">
						<label style="font-size: 1.25rem;color: #6574cd;">GENERAL DETAILS</label>
					</th>
					<?php

					$requiredInAnyCase = array( 'umb_id_type', '_umb_id_val', '_umb_brand' );
					global $global_CED_ebay_Render_Attributes;
					$marketPlace = 'ced_ebay_required_common_' . $user_id . '>' . $site_id;
					$productID   = 0;
					$categoryID  = '';
					$indexToUse  = 0;
					if ( ! empty( $profile_data ) ) {
						$data               = json_decode( $profile_data['profile_data'], true );
						$formatted_new_data = array();
						foreach ( $data as $key => $profile_key_value ) {
							$formatted_new_data[ $key . '_' . $user_id . '>' . $site_id ] = isset( $data[ $key ]['default'] ) ? $data[ $key ]['default'] : '';
						}
					}
					foreach ( $fields as $value ) {
						$isText   = false;
						$field_id = isset( $value['fields']['id'] ) ? trim( $value['fields']['id'], '_' ) : '';
						if ( isset( $value['fields']['id'] ) && in_array( $value['fields']['id'], $requiredInAnyCase ) ) {
							$attributeNameToRender  = ucfirst( $value['fields']['label'] );
							$attributeNameToRender .= '<span class="ced_ebay_wal_required">' . __( '[ Required ]', 'ebay-integration-for-woocommerce' ) . '</span>';
						} else {
							$attributeNameToRender = isset( $value['fields']['id'] ) ? ucfirst( $value['fields']['label'] ) : '';
						}
						$default           = isset( $value['fields']['id'] ) && isset( $formatted_new_data[ $value['fields']['id'] ] ) ? $formatted_new_data[ $value['fields']['id'] ] : '';
						$field_description = ! empty( $value['fields']['description'] ) ? $value['fields']['description'] : '';
						echo '<tr class="form-field _umb_id_type_field ">';

						if ( isset( $value['type'] ) && '_select' == $value['type'] ) {
							$valueForDropdown = $value['fields']['options'];
							if ( '_umb_id_type' == $value['fields']['id'] ) {
								unset( $valueForDropdown['null'] );
							}
							$productFieldInstance->renderDropdownHTML(
								$user_id,
								$site_id,
								$field_id,
								$attributeNameToRender,
								$valueForDropdown,
								$categoryID,
								$productID,
								$marketPlace,
								$indexToUse,
								array(
									'case'  => 'profile',
									'value' => $default,
								),
								'',
								'SINGLE',
								$field_description
							);
							$isText = false;
						} elseif ( isset( $value['type'] ) && '_text_input' == $value['type'] ) {
							$productFieldInstance->renderInputTextHTML(
								$user_id,
								$site_id,
								$field_id,
								$attributeNameToRender,
								$categoryID,
								$productID,
								$marketPlace,
								$indexToUse,
								array(
									'case'  => 'profile',
									'value' => $default,
								),
								false,
								'SINGLE',
								$field_description
							);
							$isText = true;
						} elseif ( isset( $value['type'] ) && '_hidden' == $value['type'] ) {
							$productFieldInstance->renderInputTextHTMLhidden(
								$user_id,
								$site_id,
								$field_id,
								$attributeNameToRender,
								$categoryID,
								$productID,
								$marketPlace,
								$indexToUse,
								array(
									'case'  => 'profile',
									'value' => $profile_category_id,
								),
								false,
								$field_description
							);
							$isText = false;
						} else {
							$isText = false;
						}
						echo '<td>';

						if ( $isText ) {
							$previousSelectedValue = 'null';
							$value_fields_id       = str_replace( '_' . $user_id . '>' . $site_id, '', $value['fields']['id'] );
							if ( isset( $data[ $value_fields_id ]['metakey'] ) && 'null' != $data[ $value_fields_id ]['metakey'] ) {
								$previousSelectedValue = $data[ $value_fields_id ]['metakey'];
							}
							if ( '-1' == $previousSelectedValue || 'null' == $previousSelectedValue ) {
								echo '<button type="button" class="button ced_add_meta_field_prod_spec_template">Add Meta Field</button>';
								echo '<input type="hidden" value="' . esc_attr( $previousSelectedValue ) . '" id="' . esc_attr( $value_fields_id ) . '_attibuteMeta" name="' . esc_attr( $value_fields_id ) . '_attibuteMeta">';
							} else {
								echo 'Meta Key: ' . esc_attr( $previousSelectedValue ) . ' <button type="button" class="button ced_add_meta_field_prod_spec_template">Edit</button>';
								echo '<input type="hidden" value="' . esc_attr( $previousSelectedValue ) . '" id="' . esc_attr( $value_fields_id ) . '_attibuteMeta" name="' . esc_attr( $value_fields_id ) . '_attibuteMeta">';
							}
						}
						echo '</td>';
						echo '</tr>';
					}
					?>
				</tbody>
			</table>
			<?php
		}

		public function ced_ebay_get_item_aspects_profile_section() {
			$user_id             = $this->user_id;
			$site_id             = $this->site_id;
			$profileID           = $this->profile_id;
			$product_id          = $this->product_id;
			$profile_category_id = $this->ebay_cat_id;
			$marketPlace         = 'ced_ebay_required_common_' . $user_id . '>' . $site_id;
			$productID           = 0;
			$categoryID          = '';
			$indexToUse          = 0;
			$wp_folder           = wp_upload_dir();
			$wp_upload_dir       = $wp_folder['basedir'];
			$wp_upload_dir       = $wp_upload_dir . '/ced-ebay/category-specifics/' . $user_id . '/' . $site_id . '/';
			if ( ! is_dir( $wp_upload_dir ) ) {
				wp_mkdir_p( $wp_upload_dir, 0777 );
			}

			$shop_data = ced_ebay_get_shop_data( $user_id );
			if ( ! empty( $shop_data ) ) {
				$token       = $shop_data['access_token'];
				$getLocation = $shop_data['location'];
			}

			$profile_data_product_level = get_post_meta( $product_id, 'ced_ebay_product_level_profile_data', true );
			if ( isset( $profile_data_product_level[ $user_id . '>' . $site_id ] ) &&
			! empty( $profile_data_product_level[ $user_id . '>' . $site_id ] ) &&
			isset( $profile_data_product_level[ $user_id . '>' . $site_id ]['_umb_ebay_profile_id']['default'] ) &&
			$profileID == $profile_data_product_level[ $user_id . '>' . $site_id ]['_umb_ebay_profile_id']['default'] ) {
				$profile_data                 = $profile_data_product_level[ $user_id . '>' . $site_id ];
				$profile_data['profile_data'] = json_encode( $profile_data );
			} else {
				global $wpdb;
				$tableName    = $wpdb->prefix . 'ced_ebay_profiles';
				$profile_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_ebay_profiles WHERE `id`=%d AND `ebay_user`=%s AND `ebay_site`=%s", $profileID, $user_id, $site_id ), 'ARRAY_A' );
				if ( ! empty( $profile_data ) ) {
					$woo_categories        = ! empty( $profile_data[0]['woo_categories'] ) ? json_decode( $profile_data[0]['woo_categories'], true ) : false;
					$profile_category_data = json_decode( $profile_data[0]['profile_data'], true );
				}
				$profile_category_data = isset( $profile_category_data ) ? $profile_category_data : '';
				$profile_category_id   = isset( $profile_category_data['_umb_ebay_category']['default'] ) ? $profile_category_data['_umb_ebay_category']['default'] : '';
				$profile_data          = isset( $profile_data[0] ) ? $profile_data[0] : $profile_data;
			}

			$selectDropdownHTML = '';
			$cat_specifics_file = $wp_upload_dir . 'ebaycat_' . $profile_category_id . '.json';
			if ( file_exists( $cat_specifics_file ) ) {
				$available_attribute = json_decode( file_get_contents( $cat_specifics_file ), true );
			} else {
				$available_attribute = array();
			}
			if ( ! empty( $available_attribute ) ) {
				$categoryAttributes = $available_attribute;
				if ( ! empty( get_option( 'ced_ebay_required_item_aspects_for_ebay_category' ) ) ) {
					$required_item_aspects_for_category = get_option( 'ced_ebay_required_item_aspects_for_ebay_category', true );
				} else {
					$required_item_aspects_for_category = array();
				}
				if ( ! empty( $woo_categories ) && is_array( $woo_categories ) ) {
					foreach ( $woo_categories as $wooTermId ) {
						if ( isset( $required_item_aspects_for_category[ $wooTermId ] ) ) {
							unset( $required_item_aspects_for_category[ $wooTermId ] );
						}
						foreach ( $categoryAttributes as $key => $catItemAspect ) {
							if ( true === $catItemAspect['aspectConstraint']['aspectRequired'] ) {
								$required_item_aspects_for_category[ $wooTermId ][ $profile_category_id . '_' . urlencode( $catItemAspect['localizedAspectName'] ) ] = array(
									'key'  => $profile_category_id . '_' . urlencode( $catItemAspect['localizedAspectName'] ),
									'name' => $catItemAspect['localizedAspectName'],
								);
							}
						}
					}
				}

				if ( ! empty( $required_item_aspects_for_category ) && is_array( $required_item_aspects_for_category ) ) {
					update_option( 'ced_ebay_required_item_aspects_for_ebay_category', $required_item_aspects_for_category );
				}
			} else {
				$ebayCategoryInstance    = CedGetCategories::get_instance( $site_id, $token );
				$categoryAttributes      = $ebayCategoryInstance->_getCatSpecifics( $profile_category_id );
				$categoryAttributes_json = json_encode( $categoryAttributes );
				if ( ! empty( get_option( 'ced_ebay_required_item_aspects_for_ebay_category' ) ) ) {
					$required_item_aspects_for_category = get_option( 'ced_ebay_required_item_aspects_for_ebay_category', true );
				} else {
					$required_item_aspects_for_category = array();
				}
				if ( ! empty( $woo_categories ) && is_array( $woo_categories ) ) {
					foreach ( $woo_categories as $wooTermId ) {
						if ( isset( $required_item_aspects_for_category[ $wooTermId ] ) ) {
							unset( $required_item_aspects_for_category[ $wooTermId ] );
						}
						foreach ( $categoryAttributes as $key => $catItemAspect ) {
							if ( true === $catItemAspect['aspectConstraint']['aspectRequired'] ) {
								$required_item_aspects_for_category[ $wooTermId ][ $profile_category_id . '_' . urlencode( $catItemAspect['localizedAspectName'] ) ] = array(
									'key'  => $profile_category_id . '_' . urlencode( $catItemAspect['localizedAspectName'] ),
									'name' => $catItemAspect['localizedAspectName'],
								);
							}
						}
					}
				}

				if ( ! empty( $required_item_aspects_for_category ) && is_array( $required_item_aspects_for_category ) ) {
					update_option( 'ced_ebay_required_item_aspects_for_ebay_category', $required_item_aspects_for_category );
				}
				$cat_specifics_file = $wp_upload_dir . 'ebaycat_' . $profile_category_id . '.json';
				if ( file_exists( $cat_specifics_file ) ) {
					wp_delete_file( $cat_specifics_file );
				}
				file_put_contents( $cat_specifics_file, $categoryAttributes_json );

			}

			$ebayCategoryInstance = CedGetCategories::get_instance( $site_id, $token );
			$limit                = array( 'ConditionEnabled', 'ConditionValues' );
			$getCatFeatures       = $ebayCategoryInstance->_getCatFeatures( $profile_category_id, array() );
			$getCatFeatures_json  = json_encode( $getCatFeatures );
			$cat_features_file    = $wp_upload_dir . 'ebaycatfeatures_' . $profile_category_id . '.json';
			if ( file_exists( $cat_features_file ) ) {
				wp_delete_file( $cat_features_file );
			}
			file_put_contents( $cat_features_file, $getCatFeatures_json );
			$getCatFeatures = isset( $getCatFeatures['Category'] ) ? $getCatFeatures['Category'] : false;
			if ( ! empty( $woo_categories ) && is_array( $woo_categories ) && ! empty( $getCatFeatures['ConditionValues'] ) ) {
				if ( ! empty( get_option( 'ced_ebay_profiles_assigned_to_categories' ) ) ) {
					$existingConditionValuesToTermIdsMapping = get_option( 'ced_ebay_profiles_assigned_to_categories', true );
				} else {
					$existingConditionValuesToTermIdsMapping = array();
				}
				foreach ( $woo_categories as $wooTermId ) {
					if ( isset( $getCatFeatures['SpecialFeatures']['Condition'] ) && ! isset( $getCatFeatures['SpecialFeatures']['Condition'][0] ) ) {
						$tempSpecialFeatures = array();
						$tempSpecialFeatures = $getCatFeatures['SpecialFeatures']['Condition'];
						unset( $getCatFeatures['SpecialFeatures']['Condition'] );
						$getCatFeatures['SpecialFeatures']['Condition'][] = $tempSpecialFeatures;
					}
					if ( ! empty( $getCatFeatures['SpecialFeatures']['Condition'] ) && is_array( $getCatFeatures['SpecialFeatures']['Condition'] ) ) {
						$existingConditionValuesToTermIdsMapping[ $wooTermId ] = array_merge( $getCatFeatures['ConditionValues']['Condition'], $getCatFeatures['SpecialFeatures']['Condition'] );
					} else {
						$existingConditionValuesToTermIdsMapping[ $wooTermId ] = $getCatFeatures['ConditionValues']['Condition'];
					}
				}
				if ( ! empty( $existingConditionValuesToTermIdsMapping ) && is_array( $existingConditionValuesToTermIdsMapping ) ) {
					update_option( 'ced_ebay_profiles_assigned_to_categories', $existingConditionValuesToTermIdsMapping );
				}
			}
			$attribute_data = array();

			$productFieldInstance = CedeBayProductsFieldsMetaBox::get_instance();
			$fields               = $productFieldInstance->ced_ebay_get_custom_products_fields( $user_id, $profile_category_id, $site_id );

			?>
			<table class="ced-ebay-profile-edit-table">
				<tbody>
					<th colspan="3" class="ced-profile-general-settings-heading-product-level ced-profile-item-aspects-heading" style="text-align:left;margin:0;">
						<label style="font-size: 1.25rem;color: #6574cd;" ><?php esc_attr_e( 'ITEM ASPECTS', 'ebay-integration-for-woocommerce' ); ?></label>
					</th>
					<?php
					if ( ! empty( $categoryAttributes ) ) {
						$ced_ebay_custom_item_specific = get_option( 'ced_ebay_custom_item_specific', true );
						$ced_ebay_custom_item_specific = isset( $ced_ebay_custom_item_specific[ $user_id ][ $site_id ] ) ? $ced_ebay_custom_item_specific[ $user_id ][ $site_id ] : array();
						if ( ! empty( $ced_ebay_custom_item_specific ) ) {
							$custom_category_attributes = array();
							foreach ( $ced_ebay_custom_item_specific as $key => $custom_item_specific ) {
								$attribute_name               = isset( $custom_item_specific['attribute'] ) ? $custom_item_specific['attribute'] : '';
								$attribute_custom_value       = isset( $custom_item_specific['custom_value'] ) ? $custom_item_specific['custom_value'] : '';
								$custom_category_attributes[] = array(
									'localizedAspectName' => $attribute_name,
									'aspectConstraint'    => array(
										'aspectDataType' => 'STRING',
										'itemToAspectCardinality' => 'SINGLE',
										'aspectMode'     => 'FREE_TEXT',
										'aspectRequired' => false,
										'aspectUsage'    => 'OPTIONAL',
										'aspectEnabledForVariations' => false,
										'aspectApplicableTo' => array(
											'ITEM',
										),
									),
									'default'             => $attribute_custom_value,
								);
							}
							$categoryAttributes = array_merge( $categoryAttributes, $custom_category_attributes );
						}
						if ( ! empty( $profile_data ) ) {
							$data               = json_decode( $profile_data['profile_data'], true );
							$formatted_new_data = array();
							foreach ( $data as $key => $profile_key_value ) {
								$formatted_new_data[ $key . '_' . $user_id . '>' . $site_id ] = isset( $data[ $key ]['default'] ) ? $data[ $key ]['default'] : '';
							}
						}
						foreach ( $categoryAttributes as $key1 => $value ) {
							$isText   = true;
							$field_id = trim( urlencode( $value['localizedAspectName'] ) ) . '_' . $user_id . '>' . $site_id;
							// if ( isset( $global_options[ $user_id ][ $site_id ][ $field_id ]['custom_value'] ) ) {
							// $global_value = $global_options[ $user_id ][ $site_id ][ $field_id ]['custom_value'];
							// } else {
							// $global_value = '';
							// }

							$default = isset( $formatted_new_data[ $profile_category_id . '_' . $field_id ] ) ? $formatted_new_data[ $profile_category_id . '_' . $field_id ] : '';

							if ( empty( $default ) ) {
								$default = isset( $value['default'] ) ? $value['default'] : '';
							}
							$required = '';
							echo '<tr class="form-field _umb_brand_field ">';

							if ( 'SELECTION_ONLY' == $value['aspectConstraint']['aspectMode'] && isset( $value['aspectValues'] ) ) {
								$cardinality          = 'SINGLE';
								$valueForDropdown     = $value['aspectValues'];
								$tempValueForDropdown = array();
								foreach ( $valueForDropdown as $key => $_value ) {
									$tempValueForDropdown[ $_value['localizedValue'] ] = $_value['localizedValue'];
								}
								$valueForDropdown = $tempValueForDropdown;

								if ( 'MULTI' == $value['aspectConstraint']['itemToAspectCardinality'] ) {
									$cardinality = 'MULTI';
								}
								if ( 'true' == $value['aspectConstraint']['aspectRequired'] ) {
									$required = 'required';
								}

								$productFieldInstance->renderDropdownHTML(
									$user_id,
									$site_id,
									$field_id,
									ucfirst( $value['localizedAspectName'] ),
									$valueForDropdown,
									$profile_category_id,
									$productID,
									$marketPlace,
									$indexToUse,
									array(
										'case'  => 'profile',
										'value' => $default,
									),
									$required,
									$cardinality
								);
								$isText = false;
							} elseif ( 'COMBO_BOX' == isset( $value['input_type'] ) ? $value['input_type'] : '' ) {
								$cardinality = 'SINGLE';
								$isText      = true;
								if ( 'true' == $value['aspectConstraint']['aspectRequired'] ) {
									$required = 'required';
								}
								if ( 'MULTI' == $value['aspectConstraint']['itemToAspectCardinality'] ) {
									$cardinality = 'MULTI';
								}
								$productFieldInstance->renderInputTextHTML(
									$user_id,
									$site_id,
									$field_id,
									ucfirst( $value['localizedAspectName'] ),
									$profile_category_id,
									$productID,
									$marketPlace,
									$indexToUse,
									array(
										'case'  => 'profile',
										'value' => $default,
									),
									$required,
									$cardinality
								);
							} elseif ( 'text' == isset( $value['input_type'] ) ? $value['input_type'] : '' ) {
								$cardinality = 'SINGLE';
								$isText      = true;
								if ( 'true' == $value['aspectConstraint']['aspectRequired'] ) {
									$required = 'required';
								}
								if ( 'MULTI' == $value['aspectConstraint']['itemToAspectCardinality'] ) {
									$cardinality = 'MULTI';
								}
								$productFieldInstance->renderInputTextHTML(
									$user_id,
									$site_id,
									$field_id,
									ucfirst( $value['localizedAspectName'] ),
									$profile_category_id,
									$productID,
									$marketPlace,
									$indexToUse,
									array(
										'case'  => 'profile',
										'value' => $default,
									),
									$required,
									$cardinality
								);
							} else {
								$cardinality = 'SINGLE';
								$isText      = true;
								if ( 'true' == $value['aspectConstraint']['aspectRequired'] ) {
									$required = 'required';
								}
								if ( 'MULTI' == $value['aspectConstraint']['itemToAspectCardinality'] ) {
									$cardinality = 'MULTI';
								}
								$productFieldInstance->renderInputTextHTML(
									$user_id,
									$site_id,
									$field_id,
									ucfirst( $value['localizedAspectName'] ),
									$profile_category_id,
									$productID,
									$marketPlace,
									$indexToUse,
									array(
										'case'  => 'profile',
										'value' => $default,
									),
									$required,
									$cardinality
								);
							}
							echo '<td>';
							if ( $isText ) {
								$previousSelectedValue = '';
								$field_id              = str_replace( '_' . $user_id . '>' . $site_id, '', $field_id );
								// if ( isset( $global_options[ $user_id ][ $site_id ][ $field_id ] ) ) {
								// $previousSelectedValue = $global_options[ $user_id ][ $site_id ][ $field_id ]['meta_key'];
								// }

								if ( isset( $data[ $profile_category_id . '_' . $field_id ] ) && 'null' != $data[ $profile_category_id . '_' . $field_id ] && isset( $data[ $profile_category_id . '_' . $field_id ]['metakey'] ) && '-1' != $data[ $profile_category_id . '_' . $field_id ]['metakey'] ) {
									$previousSelectedValue = $data[ $profile_category_id . '_' . $field_id ]['metakey'];
								}

								if ( empty( $previousSelectedValue ) ) {
									if ( ! empty( $ced_ebay_custom_item_specific ) ) {
										$custom_category_attributes = array();
										foreach ( $ced_ebay_custom_item_specific as $key => $custom_item_specific ) {
											if ( urlencode( $custom_item_specific['attribute'] ) == $field_id ) {
												$previousSelectedValue = $custom_item_specific['meta_key'];
												break;
											}
										}
									}
								}

								if ( '-1' == $previousSelectedValue || 'null' == $previousSelectedValue || '' == $previousSelectedValue ) {
									echo '<button type="button" class="button ced_add_meta_field_prod_spec_template">Add Meta Field</button>';
									echo '<input type="hidden" value="' . esc_attr( $previousSelectedValue ) . '" id="' . esc_attr( $profile_category_id ) . '_' . esc_attr( $field_id ) . '_attibuteMeta" name="' . esc_attr( $profile_category_id ) . '_' . esc_attr( $field_id ) . '_attibuteMeta">';
								} else {
									echo 'Meta Key: ' . esc_attr( $previousSelectedValue ) . ' <button type="button" class="button ced_add_meta_field_prod_spec_template">Edit</button>';
									echo '<input type="hidden" value="' . esc_attr( $previousSelectedValue ) . '" id="' . esc_attr( $profile_category_id ) . '_' . esc_attr( $field_id ) . '_attibuteMeta" name="' . esc_attr( $profile_category_id ) . '_' . esc_attr( $field_id ) . '_attibuteMeta">';
								}
							}
							echo '</td>';
							echo '</tr>';
						}
					}
					if ( isset( $getCatFeatures ) && ! empty( $getCatFeatures ) ) {
						$isText = false;
						if ( isset( $getCatFeatures['ConditionValues'] ) ) {
							$field_id = 'Condition';
							$isText   = true;
							if ( isset( $getCatFeatures['SpecialFeatures']['Condition'] ) && ! isset( $getCatFeatures['SpecialFeatures']['Condition'][0] ) ) {
								$tempSpecialFeatures = array();
								$tempSpecialFeatures = $getCatFeatures['SpecialFeatures']['Condition'];
								unset( $getCatFeatures['SpecialFeatures']['Condition'] );
								$getCatFeatures['SpecialFeatures']['Condition'][] = $tempSpecialFeatures;
							}
							if ( ! empty( $getCatFeatures['SpecialFeatures']['Condition'] ) && is_array( $getCatFeatures['SpecialFeatures']['Condition'] ) ) {
								// $valueForDropdown = $getCatFeatures['ConditionValues']['Condition'] + $getCatFeatures['SpecialFeatures']['Condition'];
								$valueForDropdown = array_merge( $getCatFeatures['ConditionValues']['Condition'], $getCatFeatures['SpecialFeatures']['Condition'] );
							} else {
								$valueForDropdown = $getCatFeatures['ConditionValues']['Condition'];
							}
							$tempValueForDropdown = array();
							if ( isset( $valueForDropdown[0] ) ) {
								foreach ( $valueForDropdown as $key => $value ) {
									$tempValueForDropdown[ $value['ID'] ] = $value['DisplayName'];
								}
							} else {
								$tempValueForDropdown[ $valueForDropdown['ID'] ] = $valueForDropdown['DisplayName'];
							}
							$valueForDropdown = $tempValueForDropdown;
							$name             = 'Condition';
							$default          = isset( $profile_category_data[ $profile_category_id . '_' . $name ] ) ? $profile_category_data[ $profile_category_id . '_' . $name ] : '';
							$default          = isset( $default['default'] ) ? $default['default'] : '';
							if ( isset( $getCatFeatures['ConditionEnabled'] ) && ( 'Enabled' == $getCatFeatures['ConditionEnabled'] || 'Required' == $getCatFeatures['ConditionEnabled'] ) ) {
								$required                                       = true;
								$catFeatureSavingForvalidation[ $categoryID ][] = 'Condition';
								$productFieldInstance->renderDropdownHTML(
									$user_id,
									$site_id,
									'Condition',
									$name,
									$valueForDropdown,
									$profile_category_id,
									$productID,
									$marketPlace,
									$indexToUse,
									array(
										'case'  => 'profile',
										'value' => $default,
									),
									$required
								);
							}
						}
						echo '<td>';
						if ( $isText ) {
							$field_id = str_replace( '_' . $user_id . '>' . $site_id, '', $field_id );
							if ( isset( $global_options[ $user_id ][ $site_id ][ $field_id ] ) ) {
								$previousSelectedValue = $global_options[ $user_id ][ $site_id ][ $field_id ]['meta_key'];
							}
							if ( isset( $data[ $profile_category_id . '_' . $field_id ] ) && 'null' != $data[ $profile_category_id . '_' . $field_id ] && isset( $data[ $profile_category_id . '_' . $field_id ]['metakey'] ) && '-1' != $data[ $profile_category_id . '_' . $field_id ]['metakey'] ) {
								$previousSelectedValue = $data[ $profile_category_id . '_' . $field_id ]['metakey'];
							}
							if ( '-1' == $previousSelectedValue || 'null' == $previousSelectedValue || '' == $previousSelectedValue ) {
								echo '<button type="button" class="button ced_add_meta_field_prod_spec_template">Add Meta Field</button>';
								echo '<input type="hidden" value="' . esc_attr( $previousSelectedValue ) . '" id="' . esc_attr( $profile_category_id ) . '_' . esc_attr( $field_id ) . '_attibuteMeta" name="' . esc_attr( $profile_category_id ) . '_' . esc_attr( $field_id ) . '_attibuteMeta">';
							} else {
								echo 'Meta Key: ' . esc_attr( $previousSelectedValue ) . ' <button type="button" class="button ced_add_meta_field_prod_spec_template">Edit</button>';
								echo '<input type="hidden" value="' . esc_attr( $previousSelectedValue ) . '" id="' . esc_attr( $profile_category_id ) . '_' . esc_attr( $field_id ) . '_attibuteMeta" name="' . esc_attr( $profile_category_id ) . '_' . esc_attr( $field_id ) . '_attibuteMeta">';
							}
						}
						echo '</td>';
						echo '</tr>';
					}
					?>
				</tbody>
			</table>
			<?php
		}

		public function ced_ebay_get_framework_profile_section() {
			$user_id             = $this->user_id;
			$site_id             = $this->site_id;
			$profileID           = $this->profile_id;
			$product_id          = $this->product_id;
			$profile_category_id = $this->ebay_cat_id;
			$marketPlace         = 'ced_ebay_required_common_' . $user_id . '>' . $site_id;
			$productID           = 0;
			$categoryID          = '';
			$indexToUse          = 0;

			$shop_data = ced_ebay_get_shop_data( $user_id );
			if ( ! empty( $shop_data ) ) {
				$token       = $shop_data['access_token'];
				$getLocation = $shop_data['location'];
			}

			$profile_data_product_level = get_post_meta( $product_id, 'ced_ebay_product_level_profile_data', true );
			if ( isset( $profile_data_product_level[ $user_id . '>' . $site_id ] ) &&
			! empty( $profile_data_product_level[ $user_id . '>' . $site_id ] ) &&
			isset( $profile_data_product_level[ $user_id . '>' . $site_id ]['_umb_ebay_profile_id']['default'] ) &&
			$profileID == $profile_data_product_level[ $user_id . '>' . $site_id ]['_umb_ebay_profile_id']['default'] ) {
				$profile_data                 = $profile_data_product_level[ $user_id . '>' . $site_id ];
				$profile_data['profile_data'] = json_encode( $profile_data );
			} else {
				global $wpdb;
				$tableName    = $wpdb->prefix . 'ced_ebay_profiles';
				$profile_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_ebay_profiles WHERE `id`=%d AND `ebay_user`=%s AND `ebay_site`=%s", $profileID, $user_id, $site_id ), 'ARRAY_A' );
				if ( ! empty( $profile_data ) ) {
					$woo_categories        = ! empty( $profile_data[0]['woo_categories'] ) ? json_decode( $profile_data[0]['woo_categories'], true ) : false;
					$profile_category_data = json_decode( $profile_data[0]['profile_data'], true );
				}
				$profile_category_data = isset( $profile_category_data ) ? $profile_category_data : '';
				$profile_category_id   = isset( $profile_category_data['_umb_ebay_category']['default'] ) ? $profile_category_data['_umb_ebay_category']['default'] : '';
				$profile_data          = isset( $profile_data[0] ) ? $profile_data[0] : $profile_data;
			}
			$productFieldInstance = CedeBayProductsFieldsMetaBox::get_instance();
			$fields               = $productFieldInstance->ced_ebay_get_custom_products_fields( $user_id, $profile_category_id, $site_id );
			$requiredInAnyCase    = array( '_umb_id_type', '_umb_id_val', '_umb_brand' );
			global $global_CED_ebay_Render_Attributes;
			?>
			<table class="ced-ebay-profile-item-aspects-table">
				<tbody>
					<tr>
						<th colspan="3" class="ced-profile-general-settings-heading-product-level ced-profile-framework-specific-heading" style="text-align:left;margin:0;">
							<label style="font-size: 1.25rem;color: #6574cd;" ><?php esc_attr_e( 'FRAMEWORK SPECIFIC', 'ebay-integration-for-woocommerce' ); ?></label>
						</th>
					</tr>
					<?php
					if ( ! empty( $profile_data ) ) {
						$data               = json_decode( $profile_data['profile_data'], true );
						$formatted_new_data = array();
						foreach ( $data as $key => $profile_key_value ) {
							$formatted_new_data[ $key . '_' . $user_id . '>' . $site_id ] = isset( $data[ $key ]['default'] ) ? $data[ $key ]['default'] : '';
						}
					}
					$productFieldInstance = CedeBayProductsFieldsMetaBox::get_instance();
					$fields               = $productFieldInstance->ced_ebay_get_profile_framework_specific( $user_id, $site_id );

					foreach ( $fields as $value ) {
						$isText   = false;
						$field_id = trim( $value['fields']['id'], '_' );
						if ( in_array( $value['fields']['id'], $requiredInAnyCase ) ) {
							$attributeNameToRender  = ucfirst( $value['fields']['label'] );
							$attributeNameToRender .= '<span class="ced_ebay_wal_required">' . __( '[ Required ]', 'ebay-integration-for-woocommerce' ) . '</span>';
						} else {
							$attributeNameToRender = ucfirst( $value['fields']['label'] );
						}

						$default           = isset( $value['fields']['id'] ) && isset( $formatted_new_data[ $value['fields']['id'] ] ) && '-1' != $formatted_new_data[ $value['fields']['id'] ] ? $formatted_new_data[ $value['fields']['id'] ] : '';
						$field_description = ! empty( $value['fields']['description'] ) ? $value['fields']['description'] : '';
						$required          = isset( $value['required'] ) ? $value['required'] : '';
						echo '<tr class="form-field _umb_id_type_field ">';

						if ( '_select' == $value['type'] ) {

							$default          = '' != $default || '-1' != $default ? $default : $global_options[ $user_id ][ $site_id ][ $value['fields']['global_id'] ]['meta_key'];
							$valueForDropdown = $value['fields']['options'];
							if ( '_umb_id_type' == $value['fields']['id'] ) {
								unset( $valueForDropdown['null'] );
							}
							$productFieldInstance->renderDropdownHTML(
								$user_id,
								$site_id,
								$field_id,
								$attributeNameToRender,
								$valueForDropdown,
								$categoryID,
								$productID,
								$marketPlace,
								$indexToUse,
								array(
									'case'  => 'profile',
									'value' => $default,
								),
								$required,
								'SINGLE',
								$field_description
							);
							$isText = false;
						} elseif ( '_text_input' == $value['type'] ) {
							if ( isset( $global_options[ $user_id ][ $site_id ][ $value['fields']['global_id'] ]['custom_value'] ) ) {
								$default = ! empty( $default ) ? $default : $global_options[ $user_id ][ $site_id ][ $value['fields']['global_id'] ]['custom_value'];
							}
							$productFieldInstance->renderInputTextHTML(
								$user_id,
								$site_id,
								$field_id,
								$attributeNameToRender,
								$categoryID,
								$productID,
								$marketPlace,
								$indexToUse,
								array(
									'case'  => 'profile',
									'value' => $default,
								),
								$required,
								'SINGLE',
								$field_description
							);
							$isText = true;
						} elseif ( '_hidden' == $value['type'] ) {
							$productFieldInstance->renderInputTextHTMLhidden(
								$user_id,
								$site_id,
								$field_id,
								$attributeNameToRender,
								$categoryID,
								$productID,
								$marketPlace,
								$indexToUse,
								array(
									'case'  => 'profile',
									'value' => $profile_category_id,
								),
								'',
								'SINGLE',
								$field_description
							);
							$isText = false;
						} else {
							$isText = false;
						}
						echo '<td>';

						if ( $isText ) {
							$previousSelectedValue = 'null';
							$value_fields_id       = str_replace( '_' . $user_id . '>' . $site_id, '', $value['fields']['id'] );
							if ( isset( $data[ $value_fields_id ]['metakey'] ) && 'null' != $data[ $value_fields_id ]['metakey'] ) {
								$previousSelectedValue = $data[ $value_fields_id ]['metakey'];
							}
							if ( '-1' == esc_attr( $previousSelectedValue ) || 'null' == esc_attr( $previousSelectedValue ) ) {
								echo '<button type="button" class="button ced_add_meta_field_prod_spec_template">Add Meta Field</button>';
								echo '<input type="hidden" value="' . esc_attr( $previousSelectedValue ) . '" id="' . esc_attr( $value_fields_id ) . '_attibuteMeta" name="' . esc_attr( $value_fields_id ) . '_attibuteMeta">';
							} else {
								echo 'Meta Key: ' . esc_attr( $previousSelectedValue ) . ' <button type="button" class="button ced_add_meta_field_prod_spec_template">Edit</button>';
								echo '<input type="hidden" value="' . esc_attr( $previousSelectedValue ) . '" id="' . esc_attr( $value_fields_id ) . '_attibuteMeta" name="' . esc_attr( $value_fields_id ) . '_attibuteMeta">';
							}
						}
						echo '</td>';
						echo '</tr>';
					}
					?>
				</tr>
				<input type="hidden" name="<?php echo esc_attr( 'ced_ebay_required_common_' . $user_id . '>' . $site_id . '[]' ); ?>" value="<?php echo esc_attr( '_umb_ebay_category_' . $user_id . '>' . $site_id ); ?>">
				<input type="hidden" value="<?php echo esc_attr( $profile_category_id ); ?>" name="<?php echo esc_attr( '_umb_ebay_category_' . $user_id . '>' . $site_id . '[0]' ); ?>">
				<input type="hidden" name="<?php echo esc_attr( 'ced_ebay_required_common_' . $user_id . '>' . $site_id . '[]' ); ?>" value="<?php echo esc_attr( '_umb_ebay_profile_id_' . $user_id . '>' . $site_id ); ?>">
				<input type="hidden" value="<?php echo esc_attr( $profileID ); ?>" name="<?php echo esc_attr( '_umb_ebay_profile_id_' . $user_id . '>' . $site_id . '[0]' ); ?>">
				</tbody>
			</table>
			<?php
		}

		public function ced_render_meta_fields( $user_id, $site_id ) {
			$attributes         = wc_get_attribute_taxonomies();
			$attrOptions        = array();
			$addedMetaKeys      = get_option( 'CedUmbProfileSelectedMetaKeys', false );
			$selectDropdownHTML = '';

			global $wpdb;
			$results = $wpdb->get_results( "SELECT DISTINCT meta_key FROM {$wpdb->prefix}postmeta WHERE meta_key NOT LIKE '%wcf%' AND meta_key NOT LIKE '%elementor%' AND meta_key NOT LIKE '%_menu%'", 'ARRAY_A' );
			foreach ( $results as $key => $meta_key ) {
				$post_meta_keys[] = $meta_key['meta_key'];
			}
			$custom_prd_attrb = array();
			$query            = $wpdb->get_results( $wpdb->prepare( "SELECT `meta_value` FROM  {$wpdb->prefix}postmeta WHERE `meta_key` LIKE %s", '_product_attributes' ), 'ARRAY_A' );
			if ( ! empty( $query ) ) {
				foreach ( $query as $key => $db_attribute_pair ) {
					foreach ( maybe_unserialize( $db_attribute_pair['meta_value'] ) as $key => $attribute_pair ) {
						if ( 1 != $attribute_pair['is_taxonomy'] ) {
							$custom_prd_attrb[] = $attribute_pair['name'];
						}
					}
				}
			}
			if ( $addedMetaKeys && count( $addedMetaKeys ) > 0 ) {
				foreach ( $addedMetaKeys as $metaKey ) {
					$attrOptions[ $metaKey ] = $metaKey;
				}
			}
			if ( ! empty( $attributes ) ) {
				foreach ( $attributes as $attributesObject ) {
					$attrOptions[ 'umb_pattr_' . $attributesObject->attribute_name ] = $attributesObject->attribute_label;
				}
			}
			/* select dropdown setup */
			ob_start();
			$global_options = ! empty( get_option( 'ced_ebay_global_options' ) ) ? get_option( 'ced_ebay_global_options', true ) : array();
			$fieldID        = '{{*fieldID}}';
			$selectId       = $fieldID . '_attibuteMeta';
			// $selectDropdownHTML .= '<label>Get value from</lable>';
			$selectDropdownHTML .= '<select class="ced_ebay_search_item_sepcifics_mapping" id="' . $selectId . '" name="' . $selectId . '">';
			$selectDropdownHTML .= '<option value="-1">Select</option>';
			$selectDropdownHTML .= '<option value="ced_product_tags">Product Tags</option>';
			$selectDropdownHTML .= '<option value="ced_product_cat_single">Product Category - Last Category</option>';
			$selectDropdownHTML .= '<option value="ced_product_cat_hierarchy">Product Category - Hierarchy</option>';

			if ( class_exists( 'ACF' ) ) {
				$acf_fields_posts = get_posts(
					array(
						'posts_per_page' => -1,
						'post_type'      => 'acf-field',
					)
				);

				foreach ( $acf_fields_posts as $key => $acf_posts ) {
					$acf_fields[ $key ]['field_name'] = $acf_posts->post_title;
					$acf_fields[ $key ]['field_key']  = $acf_posts->post_name;
				}
			}
			if ( is_array( $attrOptions ) ) {
				$selectDropdownHTML .= '<optgroup label="Global Attributes">';
				foreach ( $attrOptions as $attrKey => $attrName ) :
					$selectDropdownHTML .= '<option value="' . $attrKey . '">' . $attrName . '</option>';
				endforeach;
			}

			if ( ! empty( $custom_prd_attrb ) ) {
				$custom_prd_attrb    = array_unique( $custom_prd_attrb );
				$selectDropdownHTML .= '<optgroup label="Custom Attributes">';
				foreach ( $custom_prd_attrb as $key => $custom_attrb ) {
					$selectDropdownHTML .= '<option value="ced_cstm_attrb_' . esc_attr( $custom_attrb ) . '">' . esc_html( $custom_attrb ) . '</option>';
				}
			}

			if ( ! empty( $post_meta_keys ) ) {
				$post_meta_keys      = array_unique( $post_meta_keys );
				$selectDropdownHTML .= '<optgroup label="Custom Fields">';
				foreach ( $post_meta_keys as $key => $p_meta_key ) {
					$selectDropdownHTML .= '<option value="' . $p_meta_key . '">' . $p_meta_key . '</option>';
				}
			}

			if ( ! empty( $acf_fields ) ) {
				$selectDropdownHTML .= '<optgroup label="ACF Fields">';
				foreach ( $acf_fields as $key => $acf_field ) :
					$selectDropdownHTML .= '<option value="acf_' . $acf_field['field_key'] . '">' . $acf_field['field_name'] . '</option>';
				endforeach;
			}
			$selectDropdownHTML .= '</select>';
			return $selectDropdownHTML;
		}

		public function ced_ebay_recursive_array_search( $store_categories, $depth = 0, $args = array() ) {

			$product_id                 = isset( $args['product_id'] ) ? sanitize_text_field( $args['product_id'] ) : '';
			$user_id                    = isset( $args['user_id'] ) ? sanitize_text_field( $args['user_id'] ) : '';
			$site_id                    = isset( $args['site_id'] ) ? sanitize_text_field( $args['site_id'] ) : '';
			$type                       = isset( $args['type'] ) ? sanitize_text_field( $args['type'] ) : '';
			$store_cat_val              = '';
			$profile_data_product_level = get_post_meta( $product_id, 'ced_ebay_product_level_profile_data', true );
			if ( isset( $profile_data_product_level[ $user_id . '>' . $site_id ] ) &&
			! empty( $profile_data_product_level[ $user_id . '>' . $site_id ] ) &&
			isset( $profile_data_product_level[ $user_id . '>' . $site_id ][ '_umb_ebay_store_' . $type . '_category' ]['default'] ) ) {
				$store_cat_val = $profile_data_product_level[ $user_id . '>' . $site_id ][ '_umb_ebay_store_' . $type . '_category' ]['default'];
			}

			$store_cat_html = '';
			$indent_str     = str_repeat( '-', $depth );
			foreach ( $store_categories as $key => $value ) {
				if ( isset( $value['ChildCategory'] ) ) {
					if ( $value['CategoryID'] == $store_cat_val ) {
						$store_cat_html .= '<option name="' . $value['Name'] . '" value="' . $value['CategoryID'] . '" selected>' . $indent_str . ' ' . $value['Name'] . '</option>';
					} else {
						$store_cat_html .= '<option name="' . $value['Name'] . '" value="' . $value['CategoryID'] . '" disabled>' . $indent_str . ' ' . $value['Name'] . '</option>';
					}

					$store_cat_html .= $this->ced_ebay_recursive_array_search( $value['ChildCategory'], ( $depth + 1 ), $args );
				} elseif ( isset( $value['Name'] ) ) {
					if ( $value['CategoryID'] == $store_cat_val ) {
						$store_cat_html .= '<option name="' . $value['Name'] . '" value="' . $value['CategoryID'] . '" selected>' . $indent_str . ' ' . $value['Name'] . '</option>';
					} else {
						$store_cat_html .= '<option name="' . $value['Name'] . '" value="' . $value['CategoryID'] . '">' . $indent_str . ' ' . $value['Name'] . '</option>';
					}
				}
			}
			return $store_cat_html;
		}
	}
}
