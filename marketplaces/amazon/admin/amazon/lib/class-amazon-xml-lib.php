<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Manage xml related functions to use in amazon.
 *
 * @class    Ced_Amzon_XML_Lib
 * @version  1.0.0
 * @package Class
 * @link  http://www.cedcommerce.com/
 */

class Ced_Amzon_XML_Lib {

	public $isProfileAssignedToProduct = false;
	public $template_details           = '';


	/**
	 * This function fetches meta value of a product in accordance with profile assigned and meta value available.
	 *
	 * @name fetchMetaValueOfProduct()
	 * @link  http://www.cedcommerce.com/
	 */

	public function fetchMetaValueOfProduct( $product_id, $metaKey, $getopt_data = '' ) {

		$final_value = '';

		if ( 'browse_node_ids' == $metaKey ) {
			if ( isset( $this->browse_node_ids ) && '' != $this->browse_node_ids ) {
				return $this->browse_node_ids;
			}
		}

		if ( $this->isProfileAssignedToProduct ) {

			$_product = wc_get_product( $product_id );
			if ( 'variation' == $_product->get_type() ) {
				$parentId        = $_product->get_parent_id();
				$_product_parent = wc_get_product( $parentId );
			} else {
				$parentId = '0';
			}

			if ( '' != $getopt_data && '' != $metaKey ) {
				$geo_value = get_post_meta( $product_id, $metaKey, true );

				if ( '' != $geo_value ) {
					return $geo_value;
				}
			}

			if ( ! empty( $this->profile_data ) && isset( $this->profile_data[ $metaKey ] ) ) {

				$tempProfileData = $this->profile_data[ $metaKey ];

				// Fields mapping from general option data if profile level fields not mapped
				$seller_id                       = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
				$global_profile_mapping_data_arr = get_option( 'ced_amazon_general_options', array() );
				$global_profile_mapping_data     = isset( $global_profile_mapping_data_arr[ $seller_id ] ) ? $global_profile_mapping_data_arr[ $seller_id ] : array();

				if ( isset( $global_profile_mapping_data[ $metaKey ] ) && ! empty( $global_profile_mapping_data[ $metaKey ] ) ) {

					if ( empty( $tempProfileData['default'] ) && 'null' == $tempProfileData['metakey'] ) {
						$globalTempData = $global_profile_mapping_data[ $metaKey ];
						if ( isset( $globalTempData['default'] ) && ! empty( $globalTempData['default'] ) ) {
							$tempProfileData['default'] = $globalTempData['default'];
						}
						if ( isset( $globalTempData['metakey'] ) && 'null' != $globalTempData['metakey'] ) {
							$tempProfileData['metakey'] = $globalTempData['metakey'];
						}
					}
				}

				if ( isset( $tempProfileData['default'] ) && ! empty( $tempProfileData['default'] ) && '' != $tempProfileData['default'] && ! is_null( $tempProfileData['default'] ) ) {

					$value = $tempProfileData['default'];
					return $value;

				} elseif ( isset( $tempProfileData['metakey'] ) && ! empty( $tempProfileData['metakey'] ) && '' != $tempProfileData['metakey'] && ! is_null( $tempProfileData['metakey'] ) ) {

					$meta_code = $tempProfileData['metakey'];
					$value     = get_post_meta( $product_id, $meta_code, true );

					// If woo attribute is selected
					if ( false !== strpos( $tempProfileData['metakey'], 'umb_pattr_' ) ) {

						$wooAttribute = explode( 'umb_pattr_', $tempProfileData['metakey'] );
						$wooAttribute = end( $wooAttribute );

						if ( 'variation' == $_product->get_type() ) {
							$attributes = $_product->get_variation_attributes();
							if ( isset( $attributes[ 'attribute_pa_' . $wooAttribute ] ) && ! empty( $attributes[ 'attribute_pa_' . $wooAttribute ] ) ) {
								$wooAttributeValue = $attributes[ 'attribute_pa_' . $wooAttribute ];
								if ( '0' != $parentId ) {
									$product_terms = get_the_terms( $parentId, 'pa_' . $wooAttribute );
								} else {
									$product_terms = get_the_terms( $product_id, 'pa_' . $wooAttribute );
								}
							} else {

								$wooAttributeValue = $_product_parent->get_attribute( 'pa_' . $wooAttribute );

								if ( '0' != $parentId ) {
									$product_terms = get_the_terms( $parentId, 'pa_' . $wooAttribute );
								} else {
									$product_terms = get_the_terms( $product_id, 'pa_' . $wooAttribute );
								}
							}

							if ( is_array( $product_terms ) && ! empty( $product_terms ) ) {
								foreach ( $product_terms as $tempkey => $tempvalue ) {
									if ( $tempvalue->slug == $wooAttributeValue ) {
										$wooAttributeValue = $tempvalue->name;
										break;
									}
								}
								if ( isset( $wooAttributeValue ) && ! empty( $wooAttributeValue ) ) {
									$value = $wooAttributeValue;
								} else {
									$value = get_post_meta( $product_id, $metaKey, true );
								}
							} else {
								$value = get_post_meta( $product_id, $metaKey, true );
							}
						} else {
							$wooAttributeValue = $_product->get_attribute( 'pa_' . $wooAttribute );
							$product_terms     = get_the_terms( $product_id, 'pa_' . $wooAttribute );
							if ( is_array( $product_terms ) && ! empty( $product_terms ) ) {
								foreach ( $product_terms as $tempkey => $tempvalue ) {
									if ( $tempvalue->slug == $wooAttributeValue ) {
										$wooAttributeValue = $tempvalue->name;
										break;
									}
								}
								if ( isset( $wooAttributeValue ) && ! empty( $wooAttributeValue ) ) {
									$value = $wooAttributeValue;
								} else {
									$value = get_post_meta( $product_id, $metaKey, true );
								}
							} else {
								$value = get_post_meta( $product_id, $metaKey, true );
							}
						}
					} elseif ( false !== strpos( $tempProfileData['metakey'], 'ced_cstm_attrb_' ) ) {
						$custom_prd_attrb = explode( 'ced_cstm_attrb_', $tempProfileData['metakey'] );
						$custom_prd_attrb = end( $custom_prd_attrb );
						$wooAttribute     = $custom_prd_attrb;
						if ( ! empty( $wooAttribute ) ) {
							if ( 'variation' == $_product->get_type() ) {

								$attributes        = $_product->get_variation_attributes();
								$wooAttributeLower = strtolower( $wooAttribute );
								if ( isset( $attributes[ 'attribute_' . $wooAttributeLower ] ) && ! empty( $attributes[ 'attribute_' . $wooAttributeLower ] ) ) {
									$wooAttributeValue = $attributes[ 'attribute_' . $wooAttributeLower ];
								} else {

									$wooAttributeValue = $_product_parent->get_attribute( $wooAttribute );
									if ( ! empty( $wooAttributeValue ) ) {
										$wooAttributeValue = str_replace( '|', ',', $wooAttributeValue );
									}
								}

								if ( isset( $wooAttributeValue ) && ! empty( $wooAttributeValue ) ) {
									$value = $wooAttributeValue;
								} else {
									$value = get_post_meta( $product_id, $metaKey, true );
								}
							} else {
								$wooAttributeValue = $_product->get_attribute( $wooAttribute );
								if ( ! empty( $wooAttributeValue ) ) {
									$wooAttributeValue = str_replace( '|', ',', $wooAttributeValue );
									$value             = $wooAttributeValue;
								}
							}
						}
					} elseif ( false !== strpos( $tempProfileData['metakey'], 'ced_product_tags' ) ) {
						$terms             = get_the_terms( $product_id, 'product_tag' );
						$product_tags_list = array();
						if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
							foreach ( $terms as $term ) {
								$product_tags_list[] = $term->name;
							}
						}
						if ( ! empty( $product_tags_list ) ) {
							$value = implode( ',', $product_tags_list );
						} else {
							$value = '';
						}
					} elseif ( false !== strpos( $tempProfileData['metakey'], 'acf_' ) ) {
						$acf_field        = explode( 'acf_', $tempProfileData['metakey'] );
						$acf_field        = end( $acf_field );
						$acf_field_object = get_field_object( $acf_field, $product_id );
						$value            = $acf_field_object['value'];
					} else {
						$value = get_post_meta( $product_id, $tempProfileData['metakey'], true );
						if ( '_thumbnail_id' == $tempProfileData['metakey'] ) {
							$value = wp_get_attachment_image_url( get_post_meta( $product_id, '_thumbnail_id', true ), 'full' ) ? wp_get_attachment_image_url( get_post_meta( $product_id, '_thumbnail_id', true ), 'full' ) : '';
						}
						if ( ! isset( $value ) || empty( $value ) || '' == $value || is_null( $value ) || '0' == $value || 'null' == $value ) {
							if ( '0' != $parentId ) {

								$value = get_post_meta( $parentId, $tempProfileData['metakey'], true );
								if ( '_thumbnail_id' == $tempProfileData['metakey'] ) {
									$value = wp_get_attachment_image_url( get_post_meta( $parentId, '_thumbnail_id', true ), 'full' ) ? wp_get_attachment_image_url( get_post_meta( $parentId, '_thumbnail_id', true ), 'full' ) : '';
								}

								if ( ! isset( $value ) || empty( $value ) || '' == $value || is_null( $value ) ) {
									$value = get_post_meta( $product_id, $metaKey, true );

								}
							} else {
								$value = get_post_meta( $product_id, $metaKey, true );
							}
						}
					}
				} else {
					$value = get_post_meta( $product_id, $metaKey, true );
				}
			} else {
				$value = get_post_meta( $product_id, $metaKey, true );
			}
		} else {
			$value = get_post_meta( $product_id, $metaKey, true );
		}

		if ( '' != $final_value && '' == $value ) {

			$value = $final_value;
		}

		return $value;
	}


	/**
	 * This function formats php array in SIMPLE_XML_ELEMENT object.
	 *
	 * @name array2XML()
	 * @link  http://www.cedcommerce.com/
	 */
	public function array2XML( $xml_obj, $array ) {
		foreach ( $array as $key => $value ) {
			if ( is_numeric( $key ) ) {
				$key = $key;
			}
			if ( is_array( $value ) ) {
				$node = $xml_obj->addChild( $key );
				$this->array2XML( $node, $value );
			} else {
				$xml_obj->addChild( $key, htmlspecialchars( $value ) );
			}
		}
	}


	/**
	 * This function removes uncessary tags that creates issue in making xml.
	 *
	 * @name removeXMLTagFromXMLString()
	 * @link  http://www.cedcommerce.com/
	 */
	public function removeXMLTagFromXMLString( $xmlString ) {
		$str       = $this->get_string_between( $xmlString, '<?', '?>' );
		$str       = '<?' . $str . '?>';
		$xmlString = str_replace( $str, '', $xmlString );
		return $xmlString;
	}

	/**
	 * This function gets substring between to string chunks.
	 *
	 * @name get_string_between()
	 * @link  http://www.cedcommerce.com/
	 */
	public function get_string_between( $string, $start, $end ) {
		$string = ' ' . $string;
		$ini    = strpos( $string, $start );
		if ( 0 == $ini ) {
			return '';
		}
		$ini += strlen( $start );
		$len  = strpos( $string, $end, $ini ) - $ini;
		return substr( $string, $ini, $len );
	}



	/**
	 * This function fetches data in accordance with profile assigned to product.
	 *
	 * @name fetchAssignedProfileDataOfProduct()
	 * @link  http://www.cedcommerce.com/
	 */
	public function fetchAssignedProfileDataOfProduct( $product_id, $getopt_data = '', $profileID = '' ) {

		global $wpdb;
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';

		if ( '' == $profileID ) {

			// updated code
			$terms      = get_the_terms( $product_id, 'product_cat' );
			$term_array = array();
			if ( $terms && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$term_array[] = $term->term_id;
				}
			}

			$profileID              = '';
			$ced_woo_amazon_mapping = get_option( 'ced_woo_amazon_mapping', array() );
			$ced_woo_amazon_mapping = isset( $ced_woo_amazon_mapping[ $seller_id ] ) ? $ced_woo_amazon_mapping[ $seller_id ] : array();

			if ( ! empty( $ced_woo_amazon_mapping ) ) {
				foreach ( $ced_woo_amazon_mapping as $key => $woo_cat_array ) {

					$match_woo_cat = array_intersect( $woo_cat_array, $term_array );
					if ( is_array( $match_woo_cat ) && ! empty( $match_woo_cat ) ) {

						$profileID = $key;
						break;
					}
				}
			}
			// updated code

		}

		$profile_data = array();
		if ( isset( $profileID ) && ! empty( $profileID ) && '' != $profileID ) {

			$this->isProfileAssignedToProduct = true;
			$profileid                        = (int) $profileID;

			$profile_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles WHERE `id`= %d", $profileid ), 'ARRAY_A' );

			if ( is_array( $profile_data ) ) {
				if ( ! isset( $profile_data['0'] ) ) {
					return;
				}
				$temp_profile_data   = $profile_data['0'];
				$profile_country     = $getopt_data;
				$profile_subcountry  = '';
				$profile_category    = $temp_profile_data['primary_category'];
				$profile_subcategory = $temp_profile_data['secondary_category'];
				$template_name       = $profile_country . '_' . $profile_category . '_' . $profile_subcategory;
				if ( isset( $profile_subcountry ) && '' != $profile_subcountry ) {
					$template_name = $profile_country . '_' . $profile_subcountry . '_' . $profile_category . '_' . $profile_subcategory;
				}

				$final_template_details = array(
					'country'       => $profile_country,
					'subcountry'    => $profile_subcountry,
					'category'      => $profile_category,
					'subcategory'   => $profile_subcategory,
					'template_name' => $template_name,
				);

				$template_details = '';

				if ( empty( $template_details ) || '' == $template_details ) {

					$template_details = array();

					$amazonProductsFilePath = 'templates/' . $profile_country . '/' . $profile_category . '/' . $profile_subcategory . '/products.json';
					

					$amazonProductsDetails = '';
					//$amazonProductsDetails = ABSPATH . 'wp-content/uploads/ced-amazon/' . $amazonProductsFilePath;
					$upload_dir = wp_upload_dir();
					$amazonProductsDetails = $upload_dir['basedir'] . '/ced-amazon/' . $amazonProductsFilePath;

					ob_start();
					readfile( $amazonProductsDetails );

					$json_data = ob_get_clean();

					$amazonProductsDetails = json_decode( $json_data, true );

					
					if ( is_array( $amazonProductsDetails ) ) {


						$possible_type              = array(
							'Mandantory'         => 'Mandantory',
							'Images'             => 'Images',
							'Variation'          => 'Variation',
							'Basic'              => 'Basic',
							'Discovery'          => 'Discovery',
							'Product Enrichment' => 'Product Enrichment',
							'Dimensions'         => 'Dimensions',
							'Fulfillment'        => 'Fulfillment',
							'Compliance'         => 'Compliance',
							'Offer'              => 'Offer',
						);
						$required_field             = array();
						$categories_specific_fields = array();

						foreach ( $amazonProductsDetails as $main_key => $main_value ) {

							foreach ( $main_value as $prod_key => $prod_value ) {
								if ( isset( $prod_value['productTypeSpecific'][ $profile_subcategory ] ) ) {
									$categories_specific_fields[ $prod_key ] = $prod_key;

								}
								if ( isset( $prod_value['productTypeSpecific'][ $profile_subcategory ]['condition'] ) && 'required' == $prod_value['productTypeSpecific'][ $profile_subcategory ]['condition'] ) {

									$required_field[ $main_key ][ $prod_key ] = $prod_key;
								}
							}
						}

						$required_fields = array();
						if ( isset( $required_field['Mandantory'] ) ) {
							$required_fields                              = $required_field['Mandantory'];
							$template_details['mandantory']               = wp_json_encode( $required_fields );
							$template_details['category_specific_fields'] = wp_json_encode( $categories_specific_fields );
							$template_details['possible_fields_types']    = wp_json_encode( $possible_type );
							$template_details['template_details_info']    = wp_json_encode( $final_template_details );
						}

						$template_details['product_details'] = wp_json_encode( $amazonProductsDetails );
					}

					

					// This is for 'products_all_fields.json' which data store in table
					$json_data                 = $temp_profile_data['category_attributes_structure'];
					$amazonAllFieldsDetailsNew = array();
					$amazonAllFieldsDetails    = json_decode( $json_data, true );
					if ( is_array( $amazonAllFieldsDetails ) ) {

						foreach ( $amazonAllFieldsDetails as $allFieldsKey => $allFieldsValue ) {
							$amazonAllFieldsDetailsNew[ $allFieldsKey ] = $allFieldsKey;
						}

						$template_details['all_fields_details'] = wp_json_encode( $amazonAllFieldsDetailsNew );
					}

					// This is for flat file template feed structure
					$amazonTemplateFieldsFilePath = 'templates/' . $profile_country . '/' . $profile_category . '/products_template_fields.json';
					$amazonTemplateDetails        = '';
					//$amazonTemplateDetails        = ABSPATH . 'wp-content/uploads/ced-amazon/' . $amazonTemplateFieldsFilePath;
					$upload_dir = wp_upload_dir();
					$amazonTemplateDetails = $upload_dir['basedir'] . '/ced-amazon/' . $amazonTemplateFieldsFilePath;

					ob_start();
					readfile( $amazonTemplateDetails );
					$json_data             = ob_get_clean();
					$amazonTemplateDetails = json_decode( $json_data, true );

					if ( is_array( $amazonTemplateDetails ) ) {

						$template_details['template_fields_details'] = wp_json_encode( $amazonTemplateDetails );
					}
				}

				$profile_data  = isset( $profile_data[0] ) ? $profile_data[0] : $profile_data;
				$browsenode_id = isset( $profile_data['browse_nodes'] ) ? $profile_data['browse_nodes'] : '';
				$profile_data  = isset( $profile_data['category_attributes_data'] ) ? json_decode( $profile_data['category_attributes_data'], true ) : array();

				$template_details = is_array( $template_details ) ? $template_details : array();

			}
		} else {
			$this->isProfileAssignedToProduct = false;
		}

		if ( isset( $browsenode_id ) && '' != $browsenode_id ) {
			$this->browse_node_ids = $browsenode_id;
		}

		$this->profile_data = $profile_data;

		if ( isset( $template_details ) ) {

			$this->template_details = $template_details;
		}
	}

	/**
	 * This function make array for simple, variations and variable product
	 *
	 * @name prepareAllProductTypeData()
	 * @link  http://www.cedcommerce.com/
	 */
	public function prepareAllProductTypeData( $product_id = '', $getopt_data = '', $profileId = '' ) {

		$wooc_product = wc_get_product( $product_id );
		if ( ! is_object( $wooc_product ) ) {
			return;
		}
		$productType = $wooc_product->get_type();
		$this->getFormatedData( $product_id, $wooc_product, $productType, $getopt_data );

		return;
	}



	/*
		function to get all product informations from woocommerce
	*/
	public function getFormatedData( $productId = '', $wooc_product = '', $productType = '', $getopt_data = '' ) {

		// Get global settings data
		$seller_global_settings = array();
		$global_settings        = get_option( 'ced_amazon_global_settings' );
		$seller_location        = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		if ( isset( $global_settings[ $seller_location ] ) && ! empty( $global_settings[ $seller_location ] ) ) {
			$seller_global_settings = $global_settings[ $seller_location ];
		}

		$productData = $wooc_product->get_data();

		if ( 0 != $productData['parent_id'] ) {
			$wooc_par_product      = wc_get_product( $productData['parent_id'] );
			$wooc_par_product_data = $wooc_par_product->get_data();
		}

		$description_amazon = '';
		$Description_string = '';
		
		if ( '' != $productData['description'] ) {
			$description_amazon = isset( $productData['description'] ) ? $productData['description'] : '';
		}

		// My desc
		if ( isset( $productData['short_description'] ) && ! empty( $productData['short_description'] ) ) {
			$description_amazon .= $productData['short_description'];

		}

		if ( '' == $description_amazon ) {
			$description_amazon = isset( $productData['short_description'] ) ? $productData['short_description'] : '';
		}
		if ( '' == $description_amazon && isset( $wooc_par_product_data['description'] ) ) {
			$description_amazon = isset( $wooc_par_product_data['description'] ) ? $wooc_par_product_data['description'] : '';
		}
		if ( '' == $description_amazon && isset( $wooc_par_product_data['short_description'] ) ) {
			$description_amazon = isset( $wooc_par_product_data['short_description'] ) ? $wooc_par_product_data['short_description'] : '';
		}

		// My desc
		if ( isset( $wooc_par_product_data['short_description'] ) && ! empty( $wooc_par_product_data['short_description'] ) ) {
			$description_amazon .= $wooc_par_product_data['short_description'];

		}

		$ship_dimension_unit = strtoupper( get_option( 'woocommerce_dimension_unit' ) );
		$ship_weight_unit    = strtoupper( get_option( 'woocommerce_weight_unit' ) );

		$product_keys = array( 'id', 'name', 'slug', 'status', 'description', 'short_description', 'sku', 'price', 'regular_price', 'sale_price', 'tax_status', 'manage_stock', 'stock_quantity', 'stock_status', 'low_stock_amount', 'backorders', 'weight', 'length', 'width', 'height', 'parent_id', 'tag_ids', 'category_ids', 'image_id', 'gallery_image_ids', 'date_on_sale_from', 'date_on_sale_to' );

		$from_woo_details = array(

			'item_sku'             => 'sku',
			'item_name'            => 'name',

			'part_number'          => 'sku',
			'sale_price'           => 'sale_price',
			'maximum_retail_price' => 'price',
			'standard_price'       => 'regular_price',

			'quantity'             => 'stock_quantity',
			'item_width'           => 'width',
			'item_height'          => 'height',
			'item_length'          => 'length',
			'item_weight'          => 'weight',

			'package_width'        => 'width',
			'package_height'       => 'height',
			'package_length'       => 'length',
			'package_weight'       => 'weight',

		);
		$product_details = array();
		if ( is_object( $wooc_product ) ) {
			foreach ( $product_keys as $key => $value ) {
				if ( isset( $productData[ $value ] ) ) {

					$product_details[ $value ] = $productData[ $value ];
					if ( empty( $productData[ $value ] ) && isset( $wooc_par_product_data[ $value ] ) ) {
						if ( ! empty( $wooc_par_product_data[ $value ] )

							&& ( '' != $wooc_par_product_data[ $value ] && 'sale_price' != $value ) ) {

							$product_details[ $value ] = $wooc_par_product_data[ $value ];
						}
					}
				}
			}
		}

		$product_array = array();
		foreach ( $from_woo_details as $fieldKey => $fieldValue ) {

			if ( isset( $from_woo_details[ $fieldKey ] ) && isset( $product_details[ $fieldValue ] ) && ! empty( $product_details[ $fieldValue ] ) ) {
				$product_array[ $fieldKey ] = $product_details[ $fieldValue ];
			}
		}

		if ( ! isset( $product_array['number_of_items'] ) || empty( $product_array['number_of_items'] ) ) {
			$product_array['number_of_items'] = 1;
		}
		if ( ! isset( $product_array['condition_type'] ) || empty( $product_array['condition_type'] ) ) {
			$product_array['condition_type'] = 'New';
		}

		if ( ! isset( $product_array['fulfillment_latency'] ) || empty( $product_array['fulfillment_latency'] ) ) {
			$product_array['fulfillment_latency'] = 2;
		}
		if ( ! isset( $product_array['parent_sku'] ) && isset( $wooc_par_product_data['sku'] ) && '' != $wooc_par_product_data['sku'] ) {

			$product_array['parent_sku'] = $wooc_par_product_data['sku'];
		}

		$image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $productId ), 'full' );

		if ( isset( $image_url[0] ) ) {
			$image_url = $image_url[0];

		} elseif ( 0 != $product_details['parent_id'] ) {
				$image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $product_details['parent_id'] ), 'full' );
			if ( isset( $image_url[0] ) ) {
				$image_url = $image_url[0];
			}
		}
		if ( ! empty( $image_url ) ) {
			$attachment_url_modified = $this->modifyImageUrl( $image_url );

			$image_url = ! empty( $attachment_url_modified ) ? $attachment_url_modified : $image_url;

			$product_array['main_image_url'] = $image_url;
		}

		if ( '3.0.0' > WC()->version ) {
			$attachment_ids = $wooc_product->get_gallery_attachment_ids();
		} else {
			$attachment_ids = $wooc_product->get_gallery_image_ids();
		}

		if ( ! isset( $attachment_ids['0'] ) && 0 != $productData['parent_id'] ) {

			if ( '3.0.0' > WC()->version ) {
				$attachment_ids = $wooc_par_product->get_gallery_attachment_ids();
			} else {
				$attachment_ids = $wooc_par_product->get_gallery_image_ids();
			}
		}

		foreach ( $attachment_ids   as $key => $attachment_id ) {
			if ( $key > 7 ) {
				continue;
			}
			$image_id_key            = $key + 1;
			$attachment_url          = wp_get_attachment_image_src( $attachment_id, 'full' );
			$attachment_url_modified = $this->modifyImageUrl( $attachment_url[0] );

			$attachment_url = ! empty( $attachment_url_modified ) ? $attachment_url_modified : $attachment_url[0];

			if ( ! empty( $attachment_url ) ) {
				$product_array[ 'other_image_url' . $image_id_key ] = $attachment_url;
			}
		}

		if ( '' != $description_amazon ) {

			$Description_string = preg_replace( '/<([a-z][a-z0-9]*)[^>]*?(\/?)>/i', '<$1$2>', $description_amazon );
			$Description_string = htmlspecialchars_decode( $Description_string );
			$Description_string = preg_replace( '/(<)([img])(\w+)([^>]*>)/', '', $Description_string );
			$Description_string = $this->remove_empty_tags_recursive( $Description_string );

			$Description_string = preg_replace( '/\s+/', ' ', $Description_string );

			if ( '' != $Description_string && ! isset( $product_array['product_description'] ) ) {

				$product_array['product_description'] = $Description_string;
			}
		}

		if ( 'simple' == $productType || 'variation' == $productType ) {
			$product_array['product_description'] = $Description_string;

		}

		if ( 'variation' == $productType ) {

			$variation_attriburte_value = wc_get_formatted_variation( $wooc_product->get_variation_attributes(), true );

			if ( isset( $wooc_par_product_data['name'] ) && $wooc_par_product_data['name'] == $productData['name'] ) {
				$product_array['item_name'] = $productData['name'] . ' - ' . $variation_attriburte_value;
				$product_details['name']    = $product_array['item_name'];
			}

			$product_array['parent_child']      = 'Child';
			$product_array['relationship_type'] = 'Variation';

			if ( false !== stripos( $variation_attriburte_value, 'Size' ) ) {
				$child_theme_1 = 'SizeName';
			}
			if ( false !== stripos( $variation_attriburte_value, 'Color' ) || false !== stripos( $variation_attriburte_value, 'Colour' ) ) {
				$child_theme_2 = 'ColorName';
			}

			if ( isset( $child_theme_1 ) && ! empty( $child_theme_1 ) ) {
				$product_array['variation_theme'] = $child_theme_1;
			}

			if ( isset( $child_theme_2 ) && ! empty( $child_theme_2 ) ) {
				$product_array['variation_theme'] = $child_theme_2;
			}

			if ( ( isset( $child_theme_1 ) && ! empty( $child_theme_1 ) ) && ( isset( $child_theme_2 ) && ! empty( $child_theme_2 ) ) ) {
				$product_array['variation_theme'] = 'SizeName-ColorName';
			}
		}

		if ( 'variable' == $productType ) {

			$product_array['parent_child'] = 'Parent';

			$parentProduct = wc_get_product( $productId );
			$variationData = $parentProduct->get_available_variations();

			if ( ! empty( $variationData[0]['variation_id'] ) ) {
				$variationId           = $variationData[0]['variation_id'];
				$variationProduct      = wc_get_product( $variationId );
				$variation_attr_value  = wc_get_formatted_variation( $variationProduct->get_variation_attributes(), true );
				$child_variation_theme = get_post_meta( $variationId, 'variation_theme', true );

				if ( false !== stripos( $variation_attr_value, 'Size' ) ) {
					$var_theme_1 = 'SizeName';
				}
				if ( false !== stripos( $variation_attr_value, 'Color' ) || false !== stripos( $variation_attr_value, 'Colour' ) ) {
					$var_theme_2 = 'ColorName';
				}

				if ( isset( $var_theme_1 ) && ! empty( $var_theme_1 ) ) {
					$product_array['variation_theme'] = $var_theme_1;
				}

				if ( isset( $var_theme_2 ) && ! empty( $var_theme_2 ) ) {
					$product_array['variation_theme'] = $var_theme_2;
				}

				if ( ( isset( $var_theme_1 ) && ! empty( $var_theme_1 ) ) && ( isset( $var_theme_2 ) && ! empty( $var_theme_2 ) ) ) {
					$product_array['variation_theme'] = 'SizeName-ColorName';
				}

				if ( ( ! isset( $product_array['variation_theme'] ) || empty( $product_array['variation_theme'] ) ) && ! empty( $child_variation_theme ) ) {
					$product_array['variation_theme'] = $child_variation_theme;
				}
			}
		}

		if ( isset( $product_array['external_product_id'] ) ) {
			$ext_prod_id_len = strlen( $product_array['external_product_id'] );
			if ( $ext_prod_id_len < 10 || $ext_prod_id_len > 16 ) {
				$product_array['external_product_id']      = '';
				$product_array['external_product_id_type'] = '';
			} elseif ( 10 == $ext_prod_id_len ) {
				$product_array['external_product_id_type'] = 'ASIN';
			} elseif ( 11 == $ext_prod_id_len || 12 == $ext_prod_id_len ) {
				$product_array['external_product_id_type'] = 'UPC';
			} elseif ( 13 == $ext_prod_id_len || 14 == $ext_prod_id_len ) {
				$product_array['external_product_id_type'] = 'EAN';
			} elseif ( 15 == $ext_prod_id_len ) {
				$product_array['external_product_id_type'] = 'GTIN';
			} elseif ( 16 == $ext_prod_id_len ) {
				$product_array['external_product_id_type'] = 'GCID';
			}
		}

		if ( isset( $product_details['category_ids']['0'] ) && ! empty( $product_details['category_ids'] ) ) {

			$product_category_names        = array();
			$product_category_names_string = '';
			foreach ( $product_details['category_ids'] as $key => $value ) {
				$term = get_term_by( 'id', $value, 'product_cat' );

				if ( '' != $term->name ) {
					$product_category_names_string        .= $term->name . ', ';
					$product_category_names[ $term->name ] = $term->name;
				}
			}
			$product_category_names_string             = rtrim( $product_category_names_string, ', ' );
			$product_details['category_ids']           = $product_category_names;
			$product_details['product_category_names'] = $product_category_names_string;

		}

		if ( isset( $product_details['tag_ids']['0'] ) && ! empty( $product_details['tag_ids'] ) ) {
			$product_tag_names_string = '';
			$product_tag_names        = array();
			foreach ( $product_details['tag_ids'] as $key => $value ) {
				$term = get_term_by( 'id', $value, 'product_tag' );

				if ( '' != $term->name ) {
					$product_tag_names_string .= $term->name . ', ';

					$product_tag_names[ $term->name ] = $term->name;
				}
			}
			$product_tag_names_string             = rtrim( $product_tag_names_string, ', ' );
			$product_details['tag_ids']           = $product_tag_names;
			$product_details['product_tag_names'] = $product_tag_names_string;

		}

		if ( isset( $product_array['product_description'] ) && ! empty( $product_array['product_description'] ) ) {
			$bullet_point  = $product_array['product_description'];
			$bullet_point  = strip_tags( $bullet_point );
			$bullet_point  = explode( '.', $bullet_point );
			$bullet_points = array();

			if ( isset( $product_details['name'] ) && ! empty( $product_details['name'] ) ) {
				$bullet_points[] = substr( $product_details['name'], 0, 140 );
			}
			if ( isset( $bullet_point['0'] ) && ! empty( $bullet_point['0'] ) ) {
				$bullet_points[] = substr( $bullet_point['0'], 0, 180 );
			}
			if ( isset( $bullet_point['1'] ) && ! empty( $bullet_point['1'] ) ) {
				$bullet_points[] = substr( $bullet_point['1'], 0, 180 );
			}

			if ( ( ! isset( $product_details['product_tag_names'] ) || empty( $product_details['product_tag_names'] ) ) && ( isset( $bullet_point['2'] ) && ! empty( $bullet_point['2'] ) ) ) {
				$bullet_points[] = substr( $bullet_point['2'], 0, 180 );
			}

			if ( isset( $product_details['product_category_names'] ) && ! empty( $product_details['product_category_names'] ) ) {
				$bullet_points[] = substr( $product_details['product_category_names'], 0, 140 );
			}

			if ( isset( $product_details['product_tag_names'] ) && ! empty( $product_details['product_tag_names'] ) ) {
				$bullet_points[] = substr( $product_details['product_tag_names'], 0, 140 );

				$product_array['generic_keywords'] = substr( $product_details['product_tag_names'], 0, 50 );
			}

			if ( isset( $bullet_points['1'] ) ) {
				foreach ( $bullet_points as $key => $value ) {
					$key_val                                    = $key + 1;
					$product_array[ 'bullet_point' . $key_val ] = $value;
				}
			}
		}

		if ( ! isset( $product_array['item_package_quantity'] ) || '' == $product_array['item_package_quantity'] ) {
			$product_array['item_package_quantity'] = 1;
		}
		if ( ! isset( $product_array['number_of_items'] ) || '' == $product_array['number_of_items'] ) {
			$product_array['number_of_items'] = 1;
		}
		if ( ! isset( $product_array['handling_time'] ) || '' == $product_array['handling_time'] ) {
			$product_array['handling_time'] = 2;
		}
		if ( ! isset( $product_array['currency'] ) || '' == $product_array['currency'] ) {
			$currency_code = get_option( 'woocommerce_currency' );
			if ( '' != $currency_code ) {
				$product_array['currency'] = $currency_code;
			}
		}

		/*Commented and edited by Arun*/
		if ( '' != $ship_weight_unit ) {
			if ( 'G' == $ship_weight_unit ) {
				$ship_weight_unit = 'GR';
			}
			if ( 'LBS' == $ship_weight_unit ) {
				$ship_weight_unit = 'LB';
			}
			$product_array['item_weight_unit_of_measure']             = $ship_weight_unit;
			$product_array['package_weight_unit_of_measure']          = $ship_weight_unit;
			$product_array['website_shipping_weight_unit_of_measure'] = $ship_weight_unit;
		} else {
			$product_array['item_weight_unit_of_measure']             = 'GR';
			$product_array['package_weight_unit_of_measure']          = 'GR';
			$product_array['website_shipping_weight_unit_of_measure'] = 'GR';
		}

		if ( '' != $ship_dimension_unit ) {
			$product_array['item_dimensions_unit_of_measure'] = $ship_dimension_unit;
			$product_array['item_length_unit_of_measure']     = $ship_dimension_unit;
			$product_array['package_length_unit_of_measure']  = $ship_dimension_unit;
		}

		if ( isset( $product_array['sale_price'] ) && ! empty( $product_array['sale_price'] ) ) {
			$sale_price_val = (float) $product_array['sale_price'];
		} else {
			$sale_price_val = '';
		}
		$sale_from_date = '';
		$sale_from_date = gmdate( 'Y-m-d' );
		if ( isset( $product_details['date_on_sale_to'] ) && ! empty( $product_details['date_on_sale_to'] ) ) {

			if ( isset( $product_details['date_on_sale_to']->date ) ) {
				$sale_to_date = $product_details['date_on_sale_to']->date;
				if ( strtotime( $sale_to_date ) < strtotime( $sale_from_date ) ) {
					$sale_to_date = '';
				}
			} else {
				$sale_to_date = '';
			}
			if ( '' == $sale_to_date ) {
				$sale_to_date = gmdate( 'Y-m-d', strtotime( '+3 months' ) );
			} else {
				$sale_to_date = gmdate( 'Y-m-d', strtotime( $sale_to_date ) );
			}

			if ( isset( $sale_to_date ) && '' != $sale_price_val && 0 < $sale_price_val && '' != $sale_to_date ) {
				$product_array['sale_price']     = $sale_price_val;
				$product_array['sale_from_date'] = $sale_from_date;
				$product_array['sale_end_date']  = $sale_to_date;
			} elseif ( isset( $product_array['sale_price'] ) ) {
					unset( $product_array['sale_price'] );
					unset( $product_array['sale_end_date'] );
					unset( $product_array['sale_from_date'] );
			}
		}

		if ( isset( $product_array['sale_price'] ) && empty( $product_array['sale_price'] ) ) {
			unset( $product_array['sale_price'] );
			unset( $product_array['sale_end_date'] );
			unset( $product_array['sale_from_date'] );
		}

		if ( ! isset( $product_array['product_tax_code'] ) ) {
			$product_array['product_tax_code'] = 'A_GEN_NOTAX';
		}

		if ( ! isset( $product_array['update_delete'] ) ) {
			$product_array['update_delete'] = 'Update';
		}

		if ( ! isset( $product_array['fulfillment_center_id'] ) ) {
			$product_array['fulfillment_center_id'] = 'DEFAULT';
		}

		$standard_price_val = -1;
		if ( isset( $product_array['standard_price'] ) ) {
			$standard_price_val = (int) $product_array['standard_price'];
		}

		if ( $standard_price_val <= 0 ) {
			$product_array['standard_price']       = '0.00';
			$product_array['maximum_retail_price'] = '0.00';
		}

		$product_array['condition_type'] = 'New';

		/* Browze node id managment for all marketplaces using profiel data*/
		if ( ! isset( $product_array['recommended_browse_nodes'] ) || '' == $product_array['recommended_browse_nodes'] ) {
			$recommended_browse_nodes = $this->fetchMetaValueOfProduct( $productId, 'browse_node_ids', $getopt_data );
			if ( '' != $recommended_browse_nodes ) {
				$product_array['recommended_browse_nodes'] = $recommended_browse_nodes;
			}
		}

		if ( ! isset( $product_array['recommended_browse_nodes'] ) || empty( $product_array['recommended_browse_nodes'] ) ) {
			$product_array['recommended_browse_nodes'] = ! empty( get_post_meta( $productId, 'ced_umb_amazon_profile_browse_id_' . $getopt_data, true ) ) ? get_post_meta( $productId, 'ced_umb_amazon_profile_browse_id_' . $getopt_data, true ) : '';
		}

		if ( ! isset( $this->template_details ) ) {
			return;
		}

		$template_details = $this->template_details;

		$final_product_details  = array();
		$final_validated_fields = array();
		$missing_fields_list    = array();

		if ( isset( $template_details['category_specific_fields'] ) && ! empty( $template_details['category_specific_fields'] ) ) {

			$validation_fields = false;
			if ( isset( $template_details['validation_field_details'] ) && ! empty( $template_details['validation_field_details'] ) ) {

				$validation_field_details = json_decode( $template_details['validation_field_details'], true );
				$validation_fields        = true;

			}

			if ( isset( $template_details['mandantory'] ) ) {
				$category_all_required_fields = json_decode( $template_details['mandantory'], true );
				$category_all_fields          = json_decode( $template_details['template_fields_details'], true );
				$category_all_fields          = $category_all_fields[3];

			} else {

				$category_all_required_fields = json_decode( $template_details['category_specific_fields'], true );
				$category_all_fields          = json_decode( $template_details['template_fields_details'], true );
				$category_all_fields          = $category_all_fields[3];
			}
			$field_image_found = false;

			foreach ( $category_all_fields as $test_keys => $field_key ) {

				$assigned_profile_field_data = $this->fetchMetaValueOfProduct( $productId, $field_key, $getopt_data );

				if ( isset( $product_array[ $field_key ] ) && '' != $product_array[ $field_key ] ) {
					$final_product_details[ $field_key ] = $product_array[ $field_key ];
				}
				if ( '' != $assigned_profile_field_data ) {
					$final_product_details[ $field_key ] = $assigned_profile_field_data;

				}
				if ( ( ( ! $field_image_found ) && ( isset( $category_all_required_fields[ $field_key ] ) && empty( $final_product_details[ $field_key ] ) ) ) ) {
					$variable_index_to_skip =
						array( 'product_description', 'item_type', 'feed_product_type', 'bullet_point1', 'bullet_point2', 'bullet_point3', 'bullet_point4', 'bullet_point5', 'special_features1', 'special_features2', 'special_features3', 'target_audience_keyword', 'target_audience_keywords1', 'target_audience_keywords2', 'target_audience_keywords3', 'special_features4', 'special_features5', 'main_image_url', 'manufacturer', 'brand_name', 'item_name', 'department_name', 'style_name', 'closure_type', 'lifestyle', 'material_type', 'material_type1', 'pattern_type', 'model_year', 'shoe_dimension_unit_of_measure', 'binding', 'condition_type', 'publication_date', 'author', 'part_number', 'external_product_id', 'external_product_id_type', 'standard_price' );

					if ( 'variable' == $productType ) {

						if ( in_array( $field_key, $variable_index_to_skip ) ) {

								continue;

						}
					}
						$missing_fields_list[ $field_key ] = $field_key;
				}
				if ( 'main_image_url' == $field_key ) {
					$field_image_found = true;
				}

				if ( $validation_fields && isset( $validation_field_details[ $field_key ]['base'] ) && '' != $validation_field_details[ $field_key ]['base'] && isset( $final_product_details[ $field_key ] ) && '' != $final_product_details[ $field_key ] ) {
					$validated_value = '';

					if ( '' != $validated_value ) {
						$final_validated_fields[ $field_key ] = array( $validated_value => $final_product_details[ $field_key ] );
						$final_product_details[ $field_key ]  = $validated_value;
					}
				}
			}
		}

		if ( 'variable' == $productType ) {

			$final_product_details['parent_child'] = 'Parent';

			$final_product_details['relationship_type']        = '';
			$final_product_details['external_product_id']      = '';
			$final_product_details['external_product_id_type'] = '';
			$final_product_details['standard_price']           = '';
			$final_product_details['product_tax_code']         = '';

			// Unset relevant amazon fields which is not required for parent product
			if ( isset( $final_product_details['size'] ) ) {
				unset( $final_product_details['size'] );
			}
			if ( isset( $final_product_details['size_name'] ) ) {
				unset( $final_product_details['size_name'] );
			}
			if ( isset( $final_product_details['size_map'] ) ) {
				unset( $final_product_details['size_map'] );
			}
			if ( isset( $final_product_details['color_name'] ) ) {
				unset( $final_product_details['color_name'] );
			}
			if ( isset( $final_product_details['color_map'] ) ) {
				unset( $final_product_details['color_map'] );
			}
			if ( isset( $final_product_details['apparel_size'] ) ) {
				unset( $final_product_details['apparel_size'] );
			}
			if ( isset( $final_product_details['apparel_size_system'] ) ) {
				unset( $final_product_details['apparel_size_system'] );
			}
			if ( isset( $final_product_details['apparel_size_class'] ) ) {
				unset( $final_product_details['apparel_size_class'] );
			}
			if ( isset( $final_product_details['apparel_body_type'] ) ) {
				unset( $final_product_details['apparel_body_type'] );
			}
			if ( isset( $final_product_details['apparel_height_type'] ) ) {
				unset( $final_product_details['apparel_height_type'] );
			}
			if ( isset( $final_product_details['shirt_size'] ) ) {
				unset( $final_product_details['shirt_size'] );
			}
			if ( isset( $final_product_details['shirt_size_system'] ) ) {
				unset( $final_product_details['shirt_size_system'] );
			}
			if ( isset( $final_product_details['shirt_size_class'] ) ) {
				unset( $final_product_details['shirt_size_class'] );
			}
			if ( isset( $final_product_details['shirt_body_type'] ) ) {
				unset( $final_product_details['shirt_body_type'] );
			}
			if ( isset( $final_product_details['shirt_height_type'] ) ) {
				unset( $final_product_details['shirt_height_type'] );
			}
			if ( isset( $final_product_details['height_type'] ) ) {
				unset( $final_product_details['height_type'] );
			}
			if ( isset( $final_product_details['body_type'] ) ) {
				unset( $final_product_details['body_type'] );
			}
			if ( isset( $final_product_details['bottoms_size_system'] ) ) {
				unset( $final_product_details['bottoms_size_system'] );
			}
			if ( isset( $final_product_details['bottoms_size_class'] ) ) {
				unset( $final_product_details['bottoms_size_class'] );
			}
			if ( isset( $final_product_details['bottoms_body_type'] ) ) {
				unset( $final_product_details['bottoms_body_type'] );
			}
			if ( isset( $final_product_details['bottoms_height_type'] ) ) {
				unset( $final_product_details['bottoms_height_type'] );
			}
			if ( isset( $final_product_details['bottoms_size'] ) ) {
				unset( $final_product_details['bottoms_size'] );
			}
		}

		if ( ! isset( $final_product_details['sale_price'] ) || empty( $final_product_details['sale_price'] ) ) {
			unset( $final_product_details['sale_price'] );
			unset( $final_product_details['sale_end_date'] );
			unset( $final_product_details['sale_from_date'] );
		}

		if ( 'simple' == $productType && isset( $final_product_details['variation_theme'] ) ) {
			unset( $final_product_details['variation_theme'] );
		}

		/** Price makup start */
		if ( isset( $seller_global_settings['ced_amazon_product_markup_type'] ) && ! empty( $seller_global_settings['ced_amazon_product_markup_type'] ) && isset( $seller_global_settings['ced_amazon_product_markup'] ) && ! empty( $seller_global_settings['ced_amazon_product_markup'] ) ) {

			if ( 'Fixed_Increased' == $seller_global_settings['ced_amazon_product_markup_type'] ) {

				if ( isset( $final_product_details['standard_price'] ) && ! empty( $final_product_details['standard_price'] ) ) {
					$markup_price                            = (float) $final_product_details['standard_price'] + (float) $seller_global_settings['ced_amazon_product_markup'];
					$final_product_details['standard_price'] = round( $markup_price, 2 );
				}

				if ( isset( $final_product_details['maximum_retail_price'] ) && ! empty( $final_product_details['maximum_retail_price'] ) ) {
					$markup_price                                  = (float) $final_product_details['maximum_retail_price'] + (float) $seller_global_settings['ced_amazon_product_markup'];
					$final_product_details['maximum_retail_price'] = round( $markup_price, 2 );
				}

				if ( isset( $final_product_details['sale_price'] ) && ! empty( $final_product_details['sale_price'] ) ) {
					$markup_price                        = (float) $final_product_details['sale_price'] + (float) $seller_global_settings['ced_amazon_product_markup'];
					$final_product_details['sale_price'] = round( $markup_price, 2 );
				}
			} elseif ( 'Fixed_Decreased' == $seller_global_settings['ced_amazon_product_markup_type'] ) {

				if ( isset( $final_product_details['standard_price'] ) && ! empty( $final_product_details['standard_price'] ) ) {
					$markup_price                            = (float) $final_product_details['standard_price'] - (float) $seller_global_settings['ced_amazon_product_markup'];
					$final_product_details['standard_price'] = round( $markup_price, 2 );
				}

				if ( isset( $final_product_details['maximum_retail_price'] ) && ! empty( $final_product_details['maximum_retail_price'] ) ) {
					$markup_price                                  = (float) $final_product_details['maximum_retail_price'] - (float) $seller_global_settings['ced_amazon_product_markup'];
					$final_product_details['maximum_retail_price'] = round( $markup_price, 2 );
				}

				if ( isset( $final_product_details['sale_price'] ) && ! empty( $final_product_details['sale_price'] ) ) {
					$markup_price                        = (float) $final_product_details['sale_price'] - (float) $seller_global_settings['ced_amazon_product_markup'];
					$final_product_details['sale_price'] = round( $markup_price, 2 );
				}
			} elseif ( 'Percentage_Increased' == $seller_global_settings['ced_amazon_product_markup_type'] ) {

				if ( isset( $final_product_details['standard_price'] ) && ! empty( $final_product_details['standard_price'] ) ) {
					$markup_price                            = ( ( ( (float) $final_product_details['standard_price'] * (float) $seller_global_settings['ced_amazon_product_markup'] ) / 100 ) + $final_product_details['standard_price'] );
					$final_product_details['standard_price'] = round( $markup_price, 2 );
				}

				if ( isset( $final_product_details['maximum_retail_price'] ) && ! empty( $final_product_details['maximum_retail_price'] ) ) {
					$markup_price                                  = ( ( ( (float) $final_product_details['maximum_retail_price'] * (float) $seller_global_settings['ced_amazon_product_markup'] ) / 100 ) + $final_product_details['maximum_retail_price'] );
					$final_product_details['maximum_retail_price'] = round( $markup_price, 2 );
				}

				if ( isset( $final_product_details['sale_price'] ) && ! empty( $final_product_details['sale_price'] ) ) {
					$markup_price                        = ( ( ( (float) $final_product_details['sale_price'] * (float) $seller_global_settings['ced_amazon_product_markup'] ) / 100 ) + $final_product_details['sale_price'] );
					$final_product_details['sale_price'] = round( $markup_price, 2 );
				}
			} elseif ( 'Percentage_Decreased' == $seller_global_settings['ced_amazon_product_markup_type'] ) {

				if ( isset( $final_product_details['standard_price'] ) && ! empty( $final_product_details['standard_price'] ) ) {
					$markup_price                            = ( (float) $final_product_details['standard_price'] ) - ( ( (float) $final_product_details['standard_price'] * (float) $seller_global_settings['ced_amazon_product_markup'] ) / 100 );
					$final_product_details['standard_price'] = round( $markup_price, 2 );
				}

				if ( isset( $final_product_details['maximum_retail_price'] ) && ! empty( $final_product_details['maximum_retail_price'] ) ) {
					$markup_price                                  = ( (float) $final_product_details['maximum_retail_price'] ) - ( ( (float) $final_product_details['maximum_retail_price'] * (float) $seller_global_settings['ced_amazon_product_markup'] ) / 100 );
					$final_product_details['maximum_retail_price'] = round( $markup_price, 2 );
				}

				if ( isset( $final_product_details['sale_price'] ) && ! empty( $final_product_details['sale_price'] ) ) {
					$markup_price                        = ( (float) $final_product_details['sale_price'] ) - ( ( (float) $final_product_details['sale_price'] * (float) $seller_global_settings['ced_amazon_product_markup'] ) / 100 );
					$final_product_details['sale_price'] = round( $markup_price, 2 );
				}
			}
		}
		/** Price makup end */

		// Set product quantity if manage quantity not enable but product status in stock/out of stock
		$product_data = wc_get_product( $productId );
		$qty_status   = $product_data->get_stock_status();
		if ( ( ! isset( $final_product_details['quantity'] ) || '' == $final_product_details['quantity'] ) && 'instock' == $qty_status ) {
			$final_product_details['quantity'] = 1;
		} elseif ( ( ! isset( $final_product_details['quantity'] ) || '' == $final_product_details['quantity'] ) && 'outofstock' == $qty_status ) {
			$final_product_details['quantity'] = 0;
		}

		/** Stock quantity thershold */
		if ( isset( $seller_global_settings['ced_amazon_product_stock_type'] ) && ! empty( $seller_global_settings['ced_amazon_product_stock_type'] ) && isset( $seller_global_settings['ced_amazon_listing_stock'] ) && ! empty( $seller_global_settings['ced_amazon_listing_stock'] ) ) {

			$max_quantity_threshold = $seller_global_settings['ced_amazon_listing_stock'];
			if ( isset( $final_product_details['quantity'] ) && $final_product_details['quantity'] > $max_quantity_threshold ) {
				$final_product_details['quantity'] = $max_quantity_threshold;
			}
		}


		if ( 'variable' == $productType ) {

			if ( isset(  $final_product_details['maximum_retail_price'] ) ) {
				unset( $final_product_details['maximum_retail_price'] );
			}

			if ( isset( $final_product_details['standard_price'] ) ) {
				unset( $final_product_details['standard_price'] );
			}

			if ( isset( $final_product_details['sale_price'] ) ) {
				unset( $final_product_details['sale_price'] );
			}
			
	
		}


		if ( is_array( $missing_fields_list ) && ! empty( $missing_fields_list ) ) {
			$missing_fields_list = array_values( $missing_fields_list );
			if ( isset( $missing_fields_list['0'] ) ) {
				update_post_meta( $productId, 'ced_amazon_val_err_list_' . $getopt_data, $missing_fields_list );
			}
		} else {
			update_post_meta( $productId, 'ced_amazon_val_err_list_' . $getopt_data, array() );
		}

		if ( is_array( $final_product_details ) && ! empty( $final_product_details ) ) {
			update_post_meta( $productId, 'ced_amazon_final_pro_det_' . $getopt_data, $final_product_details );
		} else {
			update_post_meta( $productId, 'ced_amazon_final_pro_det_' . $getopt_data, array() );
		}
	}


	/**
	 * Function to remove_empty_tags_recursive
	 */
	public function remove_empty_tags_recursive( $str, $repto = null ) {
		if ( ! function_exists( 'woocommerce_product_loop_start' ) ) {

			include_once dirname( WC_PLUGIN_FILE ) . '/includes/wc-template-functions.php';

			/**
			 * Function to get content
			 *
			 * @param 'function'
			 * @param  integer 'limit'
			 * @return 'count'
			 * @since 1.0.0
			 */
			$str = apply_filters( 'the_content', $str );
		}

		$doshortcode_str = do_shortcode( $str );
		if ( ! empty( $doshortcode_str ) ) {
			$str = $doshortcode_str;
		}

		if ( preg_match_all( '/\[([^\]]+)\]/', $str, $shortCodes ) ) {
			foreach ( $shortCodes[0] as $eachShortCode ) {
				$str = str_replace( $eachShortCode, '', $str );
			}
		}
		// convert UTF-8 characters AND REMOVE WHITE AND TAB SPACES AND ANY SHOP LINKS , SHORTCODE ETC.
		$str = htmlentities( $str, ENT_QUOTES, 'UTF-8', false );
		$str = htmlspecialchars_decode( $str, ENT_QUOTES );

		$str = str_replace( chr( 0xE2 ) . chr( 0x97 ) . chr( 0x8F ), '&bull;', $str );

		$str = str_replace( chr( 194 ) . chr( 160 ), ' ', $str );

		$str = preg_replace( '#<a.*?>(.*?)</a>#i', ' $1 ', $str );

		$str = nl2br( trim( strip_tags( $str, '<b><i>' ) ) );
		$str = str_replace( array( "\n", "\r" ), '', $str );
		$str = str_replace( array( "\t" ), ' ', $str );
		if ( strlen( $str ) > 2000 ) {
			$str = substr( $str, 0, 1900 );
		}

		return preg_replace( '/<([^<\/>]*)>([\s]*?|(?R))<\/\1>/imsU', ! is_string( $repto ) ? '' : $repto, $str );
	}

	/**
	 * Function to convertTextIntoRedirectURL
	 */
	public function convertTextIntoRedirectURL( $url ) {

		$redirect_html = preg_replace( '@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" target="_blank">$1</a>', $url );
		return $redirect_html;
	}


	/**
	 * Get Time Zone
	 *
	 * @name getStandardOffsetUTC()
	 * @link  http://www.cedcommerce.com/
	 */
	public function getStandardOffsetUTC() {
		$timezone = date_default_timezone_get();

		if ( 'UTC' == $timezone ) {
			return '';
		} else {
			$timezone    = new DateTimeZone( $timezone );
			$transitions = array_slice( $timezone->getTransitions(), -3, null, true );

			foreach ( array_reverse( $transitions, true ) as $transition ) {
				if ( 1 == $transition['isdst'] ) {
					continue;
				}
				return sprintf( 'UTC %+03d:%02u', $transition['offset'] / 3600, abs( $transition['offset'] ) % 3600 / 60 );
			}

			return false;
		}
	}


	/**
	 * Function to check if sku valid
	 */
	public function isSKUValid( $sku ) {
		if ( preg_match( '/[^a-z_\-\.\/0-9]/i', $sku ) ) {
			return false;
		}

		if ( 0 === strpos( $sku, '0' ) && 50 > strlen( $sku ) ) {
			return false;
		}
		return true;
	}


	/**
	 * This function replace the https:// to http:// as per amazon standard.
	 *
	 * @name modifyImageUrl()
	 * @link  http://www.cedcommerce.com/
	 */
	public function modifyImageUrl( $image_url ) {

		if ( '/wp-content/' == substr( $image_url, 0, 12 ) ) {
			$image_url = str_replace( '/wp-content', content_url(), $image_url );
		}
		$image_url = str_replace( ':443', '', $image_url );
		$image_url = str_replace( 'https://', 'http://', $image_url );
		return $image_url;
	}

	/**
	 * Function to processProfilePrice
	 */
	public function processProfilePrice( $price, $percentage, $flat_price, $getOrigionalPrice = false ) {

		$percentage = isset( $percentage ) ? $percentage : false;
		$flat_price = isset( $flat_price ) ? $flat_price : false;

		if ( $percentage ) {
			$price += $price * floatval( $percentage ) / 100;
		}

		if ( $flat_price ) {
			$price += floatval( $flat_price );
		}

		$price = number_format( $price, 2, null, '' );
		if ( $getOrigionalPrice ) {
			if ( $flat_price ) {
				$price -= floatval( $flat_price );
			}

			if ( $percentage ) {

				$final_price = $price - $price / ( 1 + ( 1 / ( floatval( $percentage ) / 100 ) ) );
				$price       = $final_price;
			}

			$price = round( $price, 2 );
		}
		return $price;
	}
}
