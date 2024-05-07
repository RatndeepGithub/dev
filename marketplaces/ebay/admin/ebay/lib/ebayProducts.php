<?php

class Class_Ced_EBay_Products {


	public static $_instance;

	/**
	 * Ced_EBay_Config Instance.
	 * Ensures only one instance of Ced_EBay_Config is loaded or can be loaded.

	 * @since 1.0.0
	 * @static
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
	}
	/*
	 *
	 *function for preparing product data to be uploaded
	 *
	 *
	 */

	public function ced_ebay_prepareDataForUploading( $site_id, $proIDs = array(), $userId = '' ) {
		foreach ( $proIDs as $key => $value ) {
			$prod_data        = wc_get_product( $value );
			$type             = $prod_data->get_type();
			$already_uploaded = get_post_meta( $value, '_ced_ebay_listing_id_' . $userId, true );
			$preparedData     = $this->getFormattedData( $site_id, $value, $userId );
			if ( 'No Profile Assigned' == $preparedData ) {
				return array( 'error' => 'No Profile Assigned' );
			}
			return $preparedData;
		}
	}
	/*
	 *
	 *function for preparing product data to be updated
	 *
	 *
	 */
	public function ced_ebay_prepareDataForUpdating( $userId, $site_id, $proIDs = array() ) {
		foreach ( $proIDs as $key => $value ) {
			$prod_data        = wc_get_product( $value );
			$type             = $prod_data->get_type();
			$item_id          = get_post_meta( $value, '_ced_ebay_listing_id_' . $userId . '>' . $site_id, true );
			$already_uploaded = get_post_meta( $value, '_ced_ebay_listing_id_' . $userId, true );
			$preparedData     = $this->getFormattedData( $site_id, $value, $userId, $item_id );
			return $preparedData;
		}
	}
	/*
	 *
	 *function for getting stock of products to be updated
	 *
	 *
	 */
	public function ced_ebay_prepareDataForUpdatingStock( $userId, $site_id, $_to_update_productIds = array(), $notAjax = false, $ebay_variation_sku = array() ) {
		if ( empty( $_to_update_productIds ) ) {
			return 'Empty Product Ids';
		}
		$shop_data = ced_ebay_get_shop_data( $userId, $site_id );

		if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
			$siteID      = $site_id;
			$token       = $shop_data['access_token'];
			$getLocation = $shop_data['location'];
		} else {
			return 'Unable to verify eBay user';
		}
		$reviseInventoryXml = '<?xml version="1.0" encoding="utf-8"?>
			<ReviseInventoryStatusRequest xmlns="urn:ebay:apis:eBLBaseComponents">
				<RequesterCredentials>
					<eBayAuthToken>' . $token . '</eBayAuthToken>
				</RequesterCredentials>
				<Version>1267</Version>
				<WarningLevel>High</WarningLevel>
				<CedInventoryStatus>ced</CedInventoryStatus>
			</ReviseInventoryStatusRequest>';

		$CedInventoryStatusXml = '';

		foreach ( $_to_update_productIds as $productId => $itemId ) {
			if ( ! empty( $ebay_variation_sku['sku'] ) ) {
				$product_sku = get_post_meta( $productId, '_sku', true );
				if ( empty( $product_sku ) ) {
					$product_sku = $productId;
				}
				if ( ! in_array( $product_sku, $ebay_variation_sku['sku'] ) ) {
					continue;
				}
			}
			$product = wc_get_product( $productId );
			if ( $product->is_type( 'variation' ) ) {
				$variation_parent_id = $product->get_parent_id();
			}
			if ( ! empty( $variation_parent_id ) ) {
				$profileData = $this->ced_ebay_getProfileAssignedData( $variation_parent_id, $userId, $site_id );
			} else {
				$profileData = $this->ced_ebay_getProfileAssignedData( $productId, $userId, $site_id );
			}
			$stock_status = get_post_meta( $productId, '_stock_status', true );
			if ( get_option( 'ced_ebay_global_settings', false ) ) {
				$dataInGlobalSettings = get_option( 'ced_ebay_global_settings', false );
				$price_markup_type    = isset( $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_markup_type'] ) ? $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_markup_type'] : '';
				$price_markup_value   = isset( $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_markup'] ) ? $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_markup'] : '';
				$price_sync           = isset( $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_sync_price'] ) ? $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_sync_price'] : '';
				$sending_sku          = isset( $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_sending_sku'] ) ? $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_sending_sku'] : 'off';

			}

			$price_selection = isset( $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_price_option'] ) ? $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_price_option'] : '';
			if ( 'Regular_Price' == $price_selection ) {
				$price = $product->get_regular_price();
			} elseif ( 'Sale_Price' == $price_selection ) {
				$price = $product->get_sale_price();
			} else {
				$price = $product->get_price();
			}

			if ( ! empty( $variation_parent_id ) ) {
				$profile_price_markup_type = $this->fetchMetaValueOfProduct( $variation_parent_id, '_umb_ebay_profile_price_markup_type' );
				$profile_price_markup      = $this->fetchMetaValueOfProduct( $variation_parent_id, '_umb_ebay_profile_price_markup' );

			} else {
				$profile_price_markup_type = $this->fetchMetaValueOfProduct( $productId, '_umb_ebay_profile_price_markup_type' );
				$profile_price_markup      = $this->fetchMetaValueOfProduct( $productId, '_umb_ebay_profile_price_markup' );

			}
			if ( ! empty( $profile_price_markup_type ) && ! empty( $profile_price_markup ) ) {
				if ( 'Fixed_Increase' == $profile_price_markup_type ) {
					$price = $price + $profile_price_markup;
				} elseif ( 'Percentage_Increase' == $profile_price_markup_type ) {
					$price = $price + ( ( $price * $profile_price_markup ) / 100 );
				} elseif ( 'Percentage_Decrease' == $profile_price_markup_type ) {
					$price = $price - ( ( $price * $profile_price_markup ) / 100 );
				} elseif ( 'Fixed_Decrease' == $profile_price_markup_type ) {
					$price = $price - $profile_price_markup;
				}
			} elseif ( ! empty( $price_markup_type ) && ! empty( $price_markup_value ) ) {
				if ( 'Fixed_Increased' == $price_markup_type ) {
					$price = $price + $price_markup_value;
				} elseif ( 'Percentage_Increased' == $price_markup_type ) {
					$price = $price + ( ( $price * $price_markup_value ) / 100 );
				} elseif ( 'Percentage_Decreased' == $price_markup_type ) {
					$price = $price - ( ( $price * $price_markup_value ) / 100 );
				} elseif ( 'Fixed_Decreased' == $price_markup_type ) {
					$price = $price - $price_markup_value;
				}
			}
			$renderDataOnGlobalSettings = get_option( 'ced_ebay_global_settings', false );
			$manage_stock               = get_post_meta( $productId, '_manage_stock', true );
			if ( 'yes' != $manage_stock && 'instock' == $stock_status ) {
				$listing_stock_type = isset( $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_stock_type'] ) ? $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_stock_type'] : '';
				$listing_stock      = isset( $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_listing_stock'] ) ? $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_listing_stock'] : '';
				if ( ! empty( $listing_stock_type ) && ! empty( $listing_stock ) && 'MaxStock' == $listing_stock_type ) {
					$quantity = $listing_stock;
				} else {
					$quantity = 1;
				}
			} elseif ( 'outofstock' != $stock_status ) {
					$quantity           = get_post_meta( $productId, '_stock', true );
					$listing_stock_type = isset( $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_stock_type'] ) ? $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_stock_type'] : '';
					$listing_stock      = isset( $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_listing_stock'] ) ? $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_listing_stock'] : '';
				if ( ! empty( $listing_stock_type ) && ! empty( $listing_stock ) && 'MaxStock' == $listing_stock_type ) {
					if ( $quantity > $listing_stock ) {
						$quantity = $listing_stock;
					} else {
						$quantity = intval( $quantity );
						if ( $quantity < 1 ) {
							$quantity = '0';
						}
					}
				} else {
					$quantity = intval( $quantity );
					if ( $quantity < 1 ) {
						$quantity = '0';
					}
				}
			} else {
				$quantity = 0;
			}

			if ( 'on' == $price_sync && ! empty( $price ) ) {
				if ( $product->is_type( 'variation' ) ) {
					$sku = get_post_meta( $productId, '_sku', true );
					if ( empty( $sku ) ) {
						$sku = $productId;
					}
					$CedInventoryStatusXml .= '<InventoryStatus>
						<ItemID>' . $itemId . '</ItemID>
						<SKU>' . $sku . '</SKU>
						<Quantity>' . (int) $quantity . '</Quantity>
						<StartPrice>' . $price . '</StartPrice>
						</InventoryStatus>';
				} elseif ( 'off' == $sending_sku ) {
						$sku = get_post_meta( $productId, '_sku', true );
					if ( '' != $sku && null != $sku ) {
						$CedInventoryStatusXml .= '<InventoryStatus>
								<ItemID>' . $itemId . '</ItemID>
								<SKU>' . $sku . '</SKU>
								<Quantity>' . (int) $quantity . '</Quantity>
								<StartPrice>' . $price . '</StartPrice>
								</InventoryStatus>';
					} else {
						$CedInventoryStatusXml .= '<InventoryStatus>
								<ItemID>' . $itemId . '</ItemID>
								<SKU>' . $productId . '</SKU>
								<Quantity>' . (int) $quantity . '</Quantity>
								<StartPrice>' . $price . '</StartPrice>
							</InventoryStatus>';
					}
				} else {
					$CedInventoryStatusXml .= '<InventoryStatus>
								<ItemID>' . $itemId . '</ItemID>
								<Quantity>' . (int) $quantity . '</Quantity>
								<StartPrice>' . $price . '</StartPrice>
							</InventoryStatus>';
				}
			} elseif ( $product->is_type( 'variation' ) ) {

					$sku = get_post_meta( $productId, '_sku', true );
				if ( empty( $sku ) ) {
					$sku = $productId;
				}
					$CedInventoryStatusXml .= '<InventoryStatus>
						<ItemID>' . $itemId . '</ItemID>
						<SKU>' . $sku . '</SKU>
						<Quantity>' . (int) $quantity . '</Quantity>
						</InventoryStatus>';
			} elseif ( 'off' == $sending_sku ) {
					$sku = get_post_meta( $productId, '_sku', true );
				if ( '' != $sku && null != $sku ) {
					$CedInventoryStatusXml .= '<InventoryStatus>
								<ItemID>' . $itemId . '</ItemID>
								<SKU>' . $sku . '</SKU>
								<Quantity>' . (int) $quantity . '</Quantity>
								</InventoryStatus>';
				} else {
					$CedInventoryStatusXml .= '<InventoryStatus>
								<ItemID>' . $itemId . '</ItemID>
								<SKU>' . $productId . '</SKU>
								<Quantity>' . (int) $quantity . '</Quantity>
							</InventoryStatus>';
				}
			} else {
				$CedInventoryStatusXml .= '<InventoryStatus>
								<ItemID>' . $itemId . '</ItemID>
								<Quantity>' . (int) $quantity . '</Quantity>
							</InventoryStatus>';
			}
		}
		if ( '' != $CedInventoryStatusXml ) {
			$reviseInventoryXml = str_replace( '<CedInventoryStatus>ced</CedInventoryStatus>', $CedInventoryStatusXml, $reviseInventoryXml );
		}
		return $reviseInventoryXml;
	}

	/*
	 *
	 *function for preparing  product data
	 *
	 *
	 */
	public function getFormattedData( $site_id, $proIds = '', $userId = '', $ebayItemID = '' ) {
		$variation   = true;
		$finalXml    = '';
		$counter     = 0;
		$profileData = $this->ced_ebay_getProfileAssignedData( $proIds, $userId, $site_id );
		if ( false == $this->isProfileAssignedToProduct ) {
			return array( 'error' => 'No Profile Assigned' );
		}
		$product = wc_get_product( $proIds );
		$item    = array();
		if ( WC()->version > '3.0.0' ) {
			$product_data            = $product->get_data();
			$productType             = $product->get_type();
			$quantity                = (int) get_post_meta( $proIds, '_stock', true );
			$title                   = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_listing_title' );
			$product_custom_condtion = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_product_custom_condition' );
			$subtitle                = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_product_subtitle_val' );
			$get_alt_description     = get_post_meta( $proIds, 'ced_ebay_alt_prod_description_' . $proIds . '_' . $userId, true );
			if ( ! empty( $get_alt_description ) ) {
				$description = urldecode( $get_alt_description );
				$description = nl2br( $description );
			} else {
				$description = $product_data['description'] . ' ' . $product_data['short_description'];
				$description = nl2br( $description );
			}
			if ( '' == $title ) {
				$get_alt_title = get_post_meta( $proIds, 'ced_ebay_alt_prod_title_' . $proIds . '_' . $userId, true );
				if ( ! empty( $get_alt_title ) ) {
					$title = $get_alt_title;
				} else {
					$title = $product_data['name'];
				}
			}
		}

		$shop_data = ced_ebay_get_shop_data( $userId, $site_id );
		if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
			$siteID      = $site_id;
			$token       = $shop_data['access_token'];
			$getLocation = $shop_data['location'];
		} else {
			return 'Unable to verify eBay user';
		}
		$renderDataOnGlobalSettings = get_option( 'ced_ebay_global_settings', false );
		$shipping_policy            = ! empty( $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_shipping_policy'] ) ? $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_shipping_policy'] : '';
		$payment_policy             = ! empty( $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_payment_policy'] ) ? $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_payment_policy'] : '';
		$return_policy              = ! empty( $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_return_policy'] ) ? $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_return_policy'] : '';

		$template_return_policy =       $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_return_policy' );
		$template_shipping_policy =     $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_fulfillment_policy' );
		$template_payment_policy =      $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_payment_policy' );

		if (!empty($template_shipping_policy)) {
			$shipping_policy = $template_shipping_policy;
		}
		if (!empty($template_return_policy)) {
			$return_policy = $template_return_policy;
		}
		if (!empty($template_payment_policy)) {
			$payment_policy = $template_payment_policy;
		}

		if ( ! empty( $payment_policy ) && ! empty( $return_policy ) && ! empty( $shipping_policy ) ) {

			$pay_array    = explode( '|', $payment_policy );
			$payment_id   = $pay_array[0];
			$payment_name = $pay_array[1];

			$ret_array   = explode( '|', $return_policy );
			$return_id   = $ret_array[0];
			$return_name = $ret_array[1];

			$ship_array          = explode( '|', $shipping_policy );
			$ship_bussiness_id   = $ship_array[0];
			$ship_bussiness_name = $ship_array[1];

			$item['SellerProfiles']['SellerShippingProfile']['ShippingProfileID']   = $ship_bussiness_id;
			$item['SellerProfiles']['SellerShippingProfile']['ShippingProfileName'] = $ship_bussiness_name;

			$item['SellerProfiles']['SellerPaymentProfile']['PaymentProfileID']   = $payment_id;
			$item['SellerProfiles']['SellerPaymentProfile']['PaymentProfileName'] = $payment_name;

			$item['SellerProfiles']['SellerReturnProfile']['ReturnProfileID']   = $return_id;
			$item['SellerProfiles']['SellerReturnProfile']['ReturnProfileName'] = $return_name;

			// line

		} else {
			return array( 'error' => 'Business policies are not set' );
		}
		$listingDuration = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_listing_duration' );
		$lisyingType     = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_listing_type' );
		$dispatchTime    = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_dispatch_time' );
		if ( '' === $dispatchTime ) {
			$dispatchTime = 0;
		}
		$pictureUrl = wp_get_attachment_image_url( get_post_meta( $proIds, '_thumbnail_id', true ), 'full' ) ? str_replace( ' ', '%20', wp_get_attachment_image_url( get_post_meta( $proIds, '_thumbnail_id', true ), 'full' ) ) : '';
		$pictureUrl = strtok( $pictureUrl, '?' );
		if ( strpos( $pictureUrl, 'https' ) === false ) {
			$pictureUrl = str_replace( 'http', 'https', $pictureUrl );
		}
		$primarycatId = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_category' );

		$category_id = isset( $product_data['category_ids'] ) ? $product_data['category_ids'] : array();
		foreach ( $category_id as $key => $value ) {
			$storeCustomCatId = get_term_meta( $value, 'ced_ebay_mapped_to_store_category_' . $userId, true );
			if ( ! empty( $storeCustomCatId ) ) {
				break;
			}
		}
		foreach ( $category_id as $key => $value ) {
			$storeSecondaryID = get_term_meta( $value, 'ced_ebay_mapped_to_store_secondary_category_' . $userId, true );
			if ( ! empty( $storeSecondaryID ) ) {
				break;
			}
		}
		foreach ( $category_id as $key => $value ) {
			$ebay_secondary_category_id = get_term_meta( $value, 'ced_ebay_mapped_secondary_category_' . $userId, true );
			if ( ! empty( $ebay_secondary_category_id ) ) {
				break;
			}
		}

		$store_data = ! empty( get_option( 'ced_ebay_store_data_' . $userId, true ) ) ? get_option( 'ced_ebay_store_data_' . $userId, true ) : false;

		if ( $store_data ) {
			$store_custom_categories = ! empty( $store_data['Store']['CustomCategories']['CustomCategory'] ) ? $store_data['Store']['CustomCategories']['CustomCategory'] : false;
		}

		if ( $store_data ) {

			if ( $storeCustomCatId ) {
				$storeCustomCatName = $this->ced_ebay_recursive_find_category_id( $storeCustomCatId, $store_data );
			}

			if ( $storeSecondaryID ) {
				$storeCustomSecondaryCatName = $this->ced_ebay_recursive_find_category_id( $storeSecondaryID, $store_data );
			}
		}

		global $wpdb;

		$item['Title'] = $title;
		if ( ! empty( $subtitle ) ) {
			$item['Subtitle'] = $subtitle;
		}
		$mpn   = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_mpn' );
		$ean   = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_ean' );
		$isbn  = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_isbn' );
		$upc   = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_upc' );
		$brand = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_brand' );
		if ( empty( $ean ) ) {
			$ean = 'Does Not Apply';
		}
		if ( empty( $mpn ) ) {
			if ( ! empty( get_post_meta( $proIds, '_sku', true ) ) ) {
				$mpn = get_post_meta( $proIds, '_sku', true );
			} else {
				$mpn = 'Does Not Apply';
			}
		}
		if ( '' != $mpn || '' != $ean || '' != $isbn || '' != $upc ) {
			if ( '' != $brand ) {
				$item['ProductListingDetails']['BrandMPN']['Brand'] = $brand;
				$item['ProductListingDetails']['BrandMPN']['MPN']   = $mpn;

			} else {
				$item['ProductListingDetails']['BrandMPN']['Brand'] = 'No Brand Availaible';
				$item['ProductListingDetails']['BrandMPN']['MPN']   = $mpn;
			}
			if ( '' != $ean ) {
				$item['ProductListingDetails']['EAN'] = $ean;
			}
			if ( '' != $isbn ) {
				$item['ProductListingDetails']['ISBN'] = $isbn;
			}
			if ( '' != $upc ) {
				$item['ProductListingDetails']['UPC'] = $upc;
			} else {
				$item['ProductListingDetails']['UPC'] = 'Does Not Apply';
			}
		}
		if ( ! empty( $ebayItemID ) ) {
			$item['ItemID'] = $ebayItemID;
		}
		$description_template_id = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_description_template' );
		if ( empty( $description_template_id ) || '' == $description_template_id ) {
			$description_template_id = ! empty( $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_listing_description_template'] ) ? $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_listing_description_template'] : '';
		}
		if ( isset( $description_template_id ) && '' != $description_template_id ) {
			$upload_dir    = wp_upload_dir();
			$templates_dir = $upload_dir['basedir'] . '/ced-ebay/templates/';
			if ( file_exists( $templates_dir . $description_template_id ) ) {
				$template_html = @file_get_contents( $templates_dir . $description_template_id . '/template.html' );
				$custom_css    = @file_get_contents( $templates_dir . $description_template_id . '/style.css' );
			}
			if ( get_option( 'ced_ebay_global_settings', false ) ) {
				$dataInGlobalSettings = get_option( 'ced_ebay_global_settings', false );
				$price_markup_type    = isset( $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_markup_type'] ) ? $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_markup_type'] : '';
				$price_markup_value   = isset( $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_markup'] ) ? $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_markup'] : '';
			}
			$dataInGlobalSettings = ! empty( get_option( 'ced_ebay_global_settings', false ) ) ? get_option( 'ced_ebay_global_settings', false ) : '';
			$price_selection      = isset( $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_price_option'] ) ? $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_price_option'] : '';

			if ( $product->is_type( 'variable' ) ) {
				$allVariations = $product->get_children();
				foreach ( $allVariations as $key => $var_prod_id ) {
					if ( strpos( $template_html, '[woo_ebay_product_price][' . $key . ']' ) ) {
						$var_prod = wc_get_product( $var_prod_id );

						if ( 'Regular_Price' == $price_selection ) {
							$product_price = $var_prod->get_regular_price();
						} elseif ( 'Sale_Price' == $price_selection ) {
							$product_price = $var_prod->get_sale_price();
						} else {
							$product_price = $var_prod->get_price();
						}

						if ( 'Fixed_Increased' == $price_markup_type ) {
							$product_price = $product_price + $price_markup_value;
						} elseif ( 'Percentage_Increased' == $price_markup_type ) {
							$product_price = $product_price + ( ( $product_price * $price_markup_value ) / 100 );
						} elseif ( 'Percentage_Decreased' == $price_markup_type ) {
							$product_price = $product_price - ( ( $product_price * $price_markup_value ) / 100 );
						} elseif ( 'Fixed_Decreased' == $price_markup_type ) {
							$product_price = $product_price - $price_markup_value;
						}
						$template_html = str_replace( '[woo_ebay_product_price][' . $key . ']', $product_price, $template_html );

					}
				}
			} else {
				if ( 'Regular_Price' == $price_selection ) {
					$product_price = $product->get_regular_price();
				} elseif ( 'Sale_Price' == $price_selection ) {
					$product_price = $product->get_sale_price();
				} else {
					$product_price = $product->get_price();
				}
				if ( 'Fixed_Increased' == $price_markup_type ) {
					$product_price = $product_price + $price_markup_value;
				} elseif ( 'Percentage_Increased' == $price_markup_type ) {
					$product_price = $product_price + ( ( $product_price * $price_markup_value ) / 100 );
				} elseif ( 'Percentage_Decreased' == $price_markup_type ) {
					$product_price = $product_price - ( ( $product_price * $price_markup_value ) / 100 );
				} elseif ( 'Fixed_Decreased' == $price_markup_type ) {
					$product_price = $product_price - $price_markup_value;
				}
							$template_html = str_replace( '[woo_ebay_product_price]', $product_price, $template_html );

			}

			$product_image             = '<img src="' . utf8_uri_encode( strtok( $pictureUrl, '?' ) ) . '" >';
			$product_content           = wp_kses(
				$product->get_description(),
				array(
					'br' => array(),
					'h1' => array(),
					'h2' => array(),
					'h3' => array(),
					'h4' => array(),
					'p'  => array(),
					'ul' => array(),
					'li' => array(),
					'ol' => array(),
				)
			);
			$product_short_description = nl2br( $product->get_short_description() );
			$product_sku               = $product->get_sku();
			$product_category          = wp_get_post_terms( $proIds, 'product_cat' );
			$product_gallery_images    = array();
			$attachment_ids = $product->get_gallery_image_ids();
			if ( ! empty( $attachment_ids ) ) {
				foreach ( $attachment_ids as $attachment_id ) {

					$img_urls = wp_get_attachment_url( $attachment_id );

					if ( strpos( $img_urls, 'https' ) === false ) {
						$img_urls = str_replace( 'http', 'https', $img_urls );

					}
					$product_gallery_images[] = $img_urls;
				}
			}

			if ( ! empty( $product_gallery_images ) ) {
				foreach ( $product_gallery_images as $key => $image_url ) {
					if ( strpos( $template_html, '[ced_ebay_gallery_image][' . $key . ']' ) ) {
						$gallery_image_html = '<img src="' . $image_url . '" >';
						$template_html      = str_replace( '[ced_ebay_gallery_image][' . $key . ']', $gallery_image_html, $template_html );
					}
				}
			}

			$template_html = str_replace( '[woo_ebay_product_title]', $title, $template_html );
			$template_html = str_replace( '[woo_ebay_product_description]', $product_content, $template_html );
			$template_html = str_replace( '[woo_ebay_product_short_description]', $product_short_description, $template_html );
			$template_html = str_replace( '[woo_ebay_product_sku]', $product_sku, $template_html );
			if ( false !== strpos( $template_html, 'woo_ebay_product_main_image' ) ) {
				$regex = '/\[woo_ebay_product_main_image (width|height)=([^"]+)\]/';

				$matches = array();
				preg_match( $regex, $shortcode, $matches );

				if ( count( $matches ) === 2 ) {
					$image_width   = $matches[1];
					$imageheight   = $matches[2];
					$image_html    = '<img src="' . $pictureUrl . '" width="' . $image_width . 'px" height="' . $imageheight . 'px" >';
					$template_html = str_replace( '[woo_ebay_product_main_image width=' . $image_width . ' height=' . $imageheight . ']', $image_html, $template_html );

				} else {
					$template_html = str_replace( '[woo_ebay_product_main_image]', $product_image, $template_html );

				}
			}
			// $template_html       = str_replace( '[woo_ebay_product_main_image]', $product_image, $template_html );
			$template_html       = str_replace( '[woo_ebay_product_category]', $product_category[0]->name, $template_html );
			$template_html       = str_replace( '[woo_ebay_product_type]', $productType, $template_html );
			$template_html       = str_replace( '[woo_ebay_product_short_description]', $product_short_description, $template_html );
			$custom_css          = '<style type="text/css">' . $custom_css . '</style>';
			$product_description = $custom_css . ' <br> ' . $template_html . ' </br> ';
		}

		$item['Description']                   = ! empty( $product_description ) ? $product_description : $description;
		$item['PrimaryCategory']['CategoryID'] = $primarycatId;
		$item['CategoryMappingAllowed']        = true;
		$item['Site']                          = $getLocation;
		if ( ! empty( get_post_meta( $proIds, 'ced_ebay_prod_store_cat_value_' . $proIds . '_' . $userId, true ) && ! empty( get_post_meta( $proIds, 'ced_ebay_prod_store_cat_name_' . $proIds . '_' . $userId, true ) ) ) ) {
			$store_category_id                       = get_post_meta( $proIds, 'ced_ebay_prod_store_cat_value_' . $proIds . '_' . $userId, true );
			$store_category_name                     = get_post_meta( $proIds, 'ced_ebay_prod_store_cat_name_' . $proIds . '_' . $userId, true );
			$item['Storefront']['StoreCategoryID']   = $store_category_id;
			$item['Storefront']['StoreCategoryName'] = $store_category_name;
		}

		if ( ( ! empty( $storeCustomCatId ) && ! empty( $storeCustomCatName ) ) ) {
			$item['Storefront']['StoreCategoryID']   = $storeCustomCatId;
			$item['Storefront']['StoreCategoryName'] = $storeCustomCatName;

		}
		if ( ! empty( $storeSecondaryID ) && ! empty( $storeCustomSecondaryCatName ) ) {
			$item['Storefront']['StoreCategory2ID']   = $storeSecondaryID;
			$item['Storefront']['StoreCategory2Name'] = $storeCustomSecondaryCatName;
		}

		if ( ! empty( $ebay_secondary_category_id ) ) {
			$item['SecondaryCategory']['CategoryID'] = $ebay_secondary_category_id;
		}

		$renderDataOnGlobalSettings = get_option( 'ced_ebay_global_settings', false );
		$item_location_state        = ! empty( $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_item_location_state'] ) ? $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_item_location_state'] : $getLocation;
		$item['Location']           = $item_location_state;
		$amount                     = get_post_meta( $proIds, '_stock', true );
		$listing_stock_type         = isset( $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_stock_type'] ) ? $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_stock_type'] : '';
		$listing_stock              = isset( $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_listing_stock'] ) ? $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_listing_stock'] : '';

			$manage_stock   = get_post_meta( $proIds, '_manage_stock', true );
			$product_status = get_post_meta( $proIds, '_stock_status', true );

		if ( 'yes' != $manage_stock && 'instock' == $product_status ) {
			$renderDataOnGlobalSettings = get_option( 'ced_ebay_global_settings', false );
			if ( ! empty( $listing_stock_type ) && ! empty( $listing_stock ) && 'MaxStock' == $listing_stock_type ) {
				$amount = $listing_stock;
			} else {
				$amount = 1;
			}
		} elseif ( 'outofstock' != $product_status ) {
			if ( ! empty( $listing_stock_type ) && ! empty( $listing_stock ) && 'MaxStock' == $listing_stock_type ) {
				if ( $amount > $listing_stock ) {
					$amount = $listing_stock;
				} else {
					$amount = intval( $amount );
					if ( $amount < 1 ) {
						$amount = '0';
					}
				}
			} else {
				$amount = intval( $amount );
				if ( $amount < 1 ) {
					$amount = '0';
				}
			}
		} else {
			$amount = 0;
		}

		$dataInGlobalSettings      = ! empty( get_option( 'ced_ebay_global_settings', false ) ) ? get_option( 'ced_ebay_global_settings', false ) : '';
		$price_selection           = isset( $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_price_option'] ) ? $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_price_option'] : '';
		$profile_price_markup_type = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_profile_price_markup_type' );
		$profile_price_markup      = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_profile_price_markup' );
		if ( 'Regular_Price' == $price_selection ) {
			$price = $product->get_regular_price();
		} elseif ( 'Sale_Price' == $price_selection ) {
			$price = $product->get_sale_price();
		} else {
			$price = $product->get_price();
		}
			$dataInGlobalSettings = get_option( 'ced_ebay_global_settings', false );
			$price_markup_type    = isset( $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_markup_type'] ) ? $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_markup_type'] : '';
			$price_markup_value   = isset( $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_markup'] ) ? $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_markup'] : '';

		if ( ! empty( $profile_price_markup_type ) && ! empty( $profile_price_markup ) ) {
			if ( 'Fixed_Increase' == $profile_price_markup_type ) {
				$price = $price + $profile_price_markup;
			} elseif ( 'Percentage_Increase' == $profile_price_markup_type ) {
				$price = $price + ( ( $price * $profile_price_markup ) / 100 );
			} elseif ( 'Percentage_Decrease' == $profile_price_markup_type ) {
				$price = $price - ( ( $price * $profile_price_markup ) / 100 );
			} elseif ( 'Fixed_Decrease' == $profile_price_markup_type ) {
				$price = $price - $profile_price_markup;
			}
		} elseif ( ! empty( $price_markup_type ) && ! empty( $price_markup_value ) ) {
			if ( 'Fixed_Increased' == $price_markup_type ) {
				$price = $price + $price_markup_value;
			} elseif ( 'Percentage_Increased' == $price_markup_type ) {
				$price = $price + ( ( $price * $price_markup_value ) / 100 );
			} elseif ( 'Percentage_Decreased' == $price_markup_type ) {
				$price = $price - ( ( $price * $price_markup_value ) / 100 );
			} elseif ( 'Fixed_Decreased' == $price_markup_type ) {
				$price = $price - $price_markup_value;
			}
		}
		$item['AutoPay'] = 'true';
		$vat_percent     = isset( $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_vat_percent'] ) ? $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_vat_percent'] : '';
		if ( ! empty( $vat_percent ) && 0 < $vat_percent ) {
			$item['VATDetails']['VATPercent'] = $vat_percent;
		}
		if ( $product->is_type( 'simple' ) ) {
			$item['StartPrice']            = $price;
			$BestOfferEnabled              = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_bestoffer' );
			$_umb_ebay_auto_accept_offers  = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_auto_accept_offers' );
			$_umb_ebay_auto_decline_offers = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_auto_decline_offers' );
			if ( 'No' == $BestOfferEnabled ) {
				$item['BestOfferDetails']['BestOfferEnabled'] = 'false';
			} elseif ( 'Yes' == $BestOfferEnabled && ! empty( $_umb_ebay_auto_accept_offers ) && ! empty( $_umb_ebay_auto_decline_offers ) ) {
				$item['BestOfferDetails']['BestOfferEnabled'] = 'true';
				if ( strpos( $_umb_ebay_auto_accept_offers, '|F' ) !== false || strpos( $_umb_ebay_auto_accept_offers, '|P' ) !== false ) {
					$auto_accept_price_shortcode = explode( '|', $_umb_ebay_auto_accept_offers );
					$price_operation             = trim( $auto_accept_price_shortcode[0] );
					$price_modify_by             = trim( $auto_accept_price_shortcode[1] );
					$modification_type           = trim( $auto_accept_price_shortcode[2] );
					if ( $price < $price_modify_by ) {
						return 'Price of the product can\'t be less than Best Offer Auto Accept Price.';
					}
					if ( ! empty( $price_operation ) && ( '+' == $price_operation || '-' == $price_operation ) && ! empty( $price_modify_by ) && 'F' == $modification_type ) {
						if ( '+' == $price_operation ) {
							$item['ListingDetails']['BestOfferAutoAcceptPrice'] = $price + $price_modify_by;
						} elseif ( '-' == $price_operation ) {
							$item['ListingDetails']['BestOfferAutoAcceptPrice'] = $price - $price_modify_by;
						}
					} elseif ( ! empty( $price_operation ) && ( '+' == $price_operation || '-' == $price_operation ) && ! empty( $price_modify_by ) && 'P' == $modification_type ) {
						if ( '+' == $price_operation ) {
							$item['ListingDetails']['BestOfferAutoAcceptPrice'] = $price + ( $price * ( $price_modify_by / 100 ) );
						} elseif ( '-' == $price_operation ) {
							$item['ListingDetails']['BestOfferAutoAcceptPrice'] = $price - ( $price * ( $price_modify_by / 100 ) );
						}
					}
				}
				if ( strpos( $_umb_ebay_auto_decline_offers, '|F' ) !== false || strpos( $_umb_ebay_auto_decline_offers, '|P' ) !== false ) {
					$auto_decline_price_shortcode = explode( '|', $_umb_ebay_auto_decline_offers );
					$price_operation              = trim( $auto_decline_price_shortcode[0] );
					$price_modify_by              = trim( $auto_decline_price_shortcode[1] );
					$modification_type            = trim( $auto_decline_price_shortcode[2] );
					if ( $price < $price_modify_by ) {
						return 'Price of the product can\'t be less than Best Offer Auto Decline Price.';
					}
					if ( ! empty( $price_operation ) && ( '+' == $price_operation || '-' == $price_operation ) && ! empty( $price_modify_by ) && 'F' == $modification_type ) {
						if ( '+' == $price_operation ) {
							$item['ListingDetails']['MinimumBestOfferPrice'] = $price + $price_modify_by;
						} elseif ( '-' == $price_operation ) {
							$item['ListingDetails']['MinimumBestOfferPrice'] = $price - $price_modify_by;
						}
					} elseif ( ! empty( $price_operation ) && ( '+' == $price_operation || '-' == $price_operation ) && ! empty( $price_modify_by ) && 'P' == $modification_type ) {
						if ( '+' == $price_operation ) {
							$item['ListingDetails']['MinimumBestOfferPrice'] = $price + ( $price * ( $price_modify_by / 100 ) );
						} elseif ( '-' == $price_operation ) {
							$item['ListingDetails']['MinimumBestOfferPrice'] = $price - ( $price * ( $price_modify_by / 100 ) );
						}
					}
				}
			} elseif ( 'Yes' == $BestOfferEnabled && empty( $_umb_ebay_auto_accept_offers ) && empty( $_umb_ebay_auto_decline_offers ) ) {
				$item['BestOfferDetails']['BestOfferEnabled'] = 'true';
			}
		}

		$dataInGlobalSettings = get_option( 'ced_ebay_global_settings', false );
		$sending_sku          = isset( $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_sending_sku'] ) ? $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_sending_sku'] : 'off';
		if ( 'off' == $sending_sku ) {
			$item['SKU'] = $product->get_sku();
			if ( empty( $item['SKU'] ) ) {
				$item['SKU'] = $proIds;
			}
		}
		$item['ListingDuration'] = ! empty( $listingDuration ) ? $listingDuration : 'GTC';
		$item['ListingType']     = 'FixedPriceItem';
		$item['DispatchTimeMax'] = 'cedDispatchTime';

		$isDomShippingOptionCalculated  = false;
		$isIntlShippingOptionCalculated = false;
		if ( ! empty( get_option( 'ced_ebay_business_policy_details_' . $userId . '>' . $siteID ) ) ) {
			$fulfillmentPolicyDetails = get_option( 'ced_ebay_business_policy_details_' . $userId . '>' . $siteID, true );
			if ( isset( $fulfillmentPolicyDetails['shippingOptions'] ) && ! empty( $fulfillmentPolicyDetails['shippingOptions'] ) ) {
				foreach ( $fulfillmentPolicyDetails['shippingOptions'] as $fKey => $shippingOptions ) {
					if ( isset( $shippingOptions['costType'] ) && 'CALCULATED' == $shippingOptions['costType'] ) {
						if ( isset( $shippingOptions['optionType'] ) && 'DOMESTIC' == $shippingOptions['optionType'] ) {
							$isDomShippingOptionCalculated = true;
						}
						if ( isset( $shippingOptions['optionType'] ) && 'INTERNATIONAL' == $shippingOptions['optionType'] ) {
							$isIntlShippingOptionCalculated = true;
						}
					}
				}
			}
		}
		if ( $isIntlShippingOptionCalculated || $isDomShippingOptionCalculated ) {
			$wcDimensionUnit = get_option( 'woocommerce_dimension_unit' );

			$productWeight = get_post_meta( $proIds, '_weight', true );
			$weight_unit   = get_option( 'woocommerce_weight_unit' );
			if ( '' != $productWeight ) {
				if ( 'lbs' == $weight_unit ) {
					$item['ShippingPackageDetails']['MeasurementUnit'] = 'English';
					$weight_in_pounds                                  = (int) $productWeight;
					$weight_frac                                       = $productWeight - $weight_in_pounds;
					$weight_in_ounces                                  = ceil( $weight_frac * 16 );
					$weight_major_xml                                  = '<WeightMajor unit="lbs">' . $weight_in_pounds . '</WeightMajor><WeightMinor unit="oz">' . $weight_in_ounces . '</WeightMinor>';
				} elseif ( 'kg' == $weight_unit ) {
					$item['ShippingPackageDetails']['MeasurementUnit'] = 'Metric';
					$weight_in_kg                                      = (int) $productWeight;
					$weight_frac                                       = $productWeight - $weight_in_kg;
					$weight_in_grams                                   = $weight_frac * 1000;
					$weight_major_xml                                  = '<WeightMajor unit="kg">' . $weight_in_kg . '</WeightMajor><WeightMinor unit="gr">' . $weight_in_grams . '</WeightMinor>';
				} elseif ( 'g' == $weight_unit ) {

					$item['ShippingPackageDetails']['MeasurementUnit'] = 'Metric';
					$weightConverter                                   = new Olifolkerd\Convertor\Convertor( $productWeight, $weight_unit );
					$weight_in_kg                                      = $weightConverter->to( 'kg' );
					$weight_kg_whole                                   = (int) $weight_in_kg;
					$frac                = $weight_in_kg - $weight_kg_whole;
					$fracWeightConverter = new Olifolkerd\Convertor\Convertor( $frac, 'kg' );
					$weight_in_g         = floor( $fracWeightConverter->to( 'g' ) );
					$weight_major_xml    = '<WeightMajor unit="kg">' . $weight_kg_whole . '</WeightMajor><WeightMinor unit="gr">' . $weight_in_g . '</WeightMinor>';
				} elseif ( 'oz' == $weight_unit ) {
					$item['ShippingPackageDetails']['MeasurementUnit'] = 'English';
					$weightConverter                                   = new Olifolkerd\Convertor\Convertor( $productWeight, $weight_unit );
					$weight_in_lb                                      = $weightConverter->to( 'lb' );
					$weight_lb_whole                                   = (int) $weight_in_lb;
					$frac                = $weight_in_lb - $weight_lb_whole;
					$fracWeightConverter = new Olifolkerd\Convertor\Convertor( $frac, 'lb' );
					$weight_in_oz        = floor( $fracWeightConverter->to( 'oz' ) );
					$weight_major_xml    = '<WeightMajor unit="lbs">' . $weight_lb_whole . '</WeightMajor><WeightMinor unit="oz">' . $weight_in_oz . '</WeightMinor>';
				}
				$item['ShippingPackageDetails']['WeightMajor'] = 'cedWeightMajor';
			}
			if ( '' == $productWeight ) {
				$productWeight = 0;
			}
		}

		$dispatch_time_xml = '<DispatchTimeMax>' . $dispatchTime . '</DispatchTimeMax>';
		$Autopay           = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_autopay' );
		if ( 'No' == $Autopay ) {
			$item['AutoPay'] = 'false';
		} elseif ( 'Yes' == $Autopay ) {
			$item['AutoPay'] = 'true';
		}

		$postalCode = '';

		$wp_folder     = wp_upload_dir();
		$wp_upload_dir = $wp_folder['basedir'];
		$wp_upload_dir = $wp_upload_dir . '/ced-ebay/category-specifics/' . $userId . '/' . $site_id . '/';

		$cat_specifics_file = $wp_upload_dir . 'ebaycat_' . $primarycatId . '.json';
		if ( file_exists( $cat_specifics_file ) ) {
			$available_attribute = json_decode( file_get_contents( $cat_specifics_file ), true );
		}
		if ( ! is_array( $available_attribute ) ) {
			$available_attribute = array();
		}

		$fileCategory = CED_EBAY_DIRPATH . 'admin/ebay/lib/cedGetcategories.php';
		if ( file_exists( $fileCategory ) ) {
			require_once $fileCategory;
		}

		$ebayConfig = CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayConfig.php';
		if ( file_exists( $ebayConfig ) ) {
			require_once $ebayConfig;
		}

		$ebayCategoryInstance = CedGetCategories::get_instance( $siteID, $token );
		if ( ! empty( $available_attribute ) && is_array( $available_attribute ) ) {
			$getCatSpecifics = $available_attribute;
			$limit           = array( 'ConditionEnabled', 'ConditionValues' );
			$getCatFeatures  = $ebayCategoryInstance->_getCatFeatures( $primarycatId, $limit );
			$getCatFeatures  = isset( $getCatFeatures['Category'] ) ? $getCatFeatures['Category'] : false;
		} else {
			$getCatSpecifics      = $ebayCategoryInstance->_getCatSpecifics( $primarycatId );
			$getCatSpecifics_json = json_encode( $getCatSpecifics );
			$cat_specifics_file   = $wp_upload_dir . 'ebaycat_' . $primarycatId . '.json';
			if ( file_exists( $cat_specifics_file ) ) {
				wp_delete_file( $cat_specifics_file );
			}
			file_put_contents( $cat_specifics_file, $getCatSpecifics_json );
			$limit          = array( 'ConditionEnabled', 'ConditionValues' );
			$getCatFeatures = $ebayCategoryInstance->_getCatFeatures( $primarycatId, $limit );
			$getCatFeatures = isset( $getCatFeatures['Category'] ) ? $getCatFeatures['Category'] : false;
		}
		$nameValueList = '';
		$catSpecifics  = array();
		if ( ! empty( $getCatSpecifics ) ) {
			$catSpecifics = $getCatSpecifics;
		}

		if ( is_array( $catSpecifics ) && ! empty( $catSpecifics ) ) {
			foreach ( $catSpecifics as $specific ) {
				if ( isset( $specific['localizedAspectName'] ) ) {
					$catSpcfcs = $this->fetchMetaValueOfProduct( $proIds, urlencode( $primarycatId . '_' . $specific['localizedAspectName'] ) );
					if ( $catSpcfcs ) {
						if ( is_array( $catSpcfcs ) && ! empty( $catSpcfcs ) ) {
							$catSpcfcs = implode( ',', $catSpcfcs );
						}
						if ( strpos( $catSpcfcs, '&' ) !== false ) {
							$catSpcfcs = str_replace( '&', '&amp;', $catSpcfcs );
						} elseif ( strpos( $specific['localizedAspectName'], '&' ) !== false ) {
							$specific['localizedAspectName'] = str_replace( '&', '&amp;', $specific['localizedAspectName'] );
						}
						$nameValueList .= '<NameValueList>
						<Name>' . $specific['localizedAspectName'] . '</Name>
						<Value>' . $catSpcfcs . '</Value>
					</NameValueList>';
					}
				}
			}
		}
		if ( ! empty( get_option( 'ced_ebay_custom_item_specific', true ) ) ) {
			$global_custom_attributes = get_option( 'ced_ebay_custom_item_specific', true );
			foreach ( $global_custom_attributes[ $userId ][ $siteID ] as $data_key => $data_value ) {
				if ( isset( $data_value['attribute'] ) ) {
					$catSpcfcs = $this->fetchMetaValueOfProduct( $proIds, urlencode( $primarycatId . '_' . $data_value['attribute'] ) );

					if ( $catSpcfcs ) {
						if ( is_array( $catSpcfcs ) && ! empty( $catSpcfcs ) ) {
							$catSpcfcs = implode( ',', $catSpcfcs );
						}
						if ( strpos( $catSpcfcs, '&' ) !== false ) {
							$catSpcfcs = str_replace( '&', '&amp;', $catSpcfcs );
						} elseif ( strpos( $data_value['attribute'], '&' ) !== false ) {
							$data_value['attribute'] = str_replace( '&', '&amp;', $data_value['attribute'] );
						}

						$nameValueList .= '<NameValueList>
						<Name>' . $data_value['attribute'] . '</Name>
						<Value>' . $catSpcfcs . '</Value>
					</NameValueList>';
					}
				}
			}
		}
		$conditionID = '';
		if ( $getCatFeatures ) {
			if ( isset( $getCatFeatures['ConditionValues'] ) ) {
				$valueForDropdown     = $getCatFeatures['ConditionValues']['Condition'];
				$tempValueForDropdown = array();
				if ( ! empty( get_post_meta( $proIds, 'ced_ebay_listing_condition', true ) ) ) {
					$conditionID = get_post_meta( $proIds, 'ced_ebay_listing_condition', true );
				} else {
					$conditionID = $this->fetchMetaValueOfProduct( $proIds, $primarycatId . '_Condition' );
				}               if ( '' == $conditionID || null == $conditionID ) {
					$missingValues[] = 'Condition id';
				}
			}
		}

		$item['Title']      = $title;
		$custom_template_id = 0;
		$custom_template_id = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_custom_description_template' );

		if ( '' != $nameValueList && null != $nameValueList ) {
			$nameValueList         = '<ItemSpecifics>' . $nameValueList . '</ItemSpecifics>';
			$item['ItemSpecifics'] = 'ced';
		}

		if ( 'variable' == $productType ) {
			$VariationSpecificsFinalSet = $this->getFormattedDataForVariation( $proIds, $siteID, $userId );
			if ( '' != $VariationSpecificsFinalSet && null != $VariationSpecificsFinalSet ) {
				$item['Variations'] = 'ced';
			}
		}
		$item['PrimaryCategory']['CategoryID'] = $primarycatId;

		if ( '' != $mpn || '' != $ean || '' != $isbn || '' != $upc ) {
			if ( '' != $ean ) {
				$item['ProductListingDetails']['EAN'] = $ean;
			}
			if ( '' != $isbn ) {
				$item['ProductListingDetails']['ISBN'] = $isbn;
			}
			if ( '' != $upc ) {
				$item['ProductListingDetails']['UPC'] = $upc;
			} else {
				$item['ProductListingDetails']['UPC'] = 'DoesNotApply';
			}
		}

		$_umb_ebay_prefill_listing_with_ebay_catalog = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_prefill_listing_with_ebay_catalog' );
		$_umb_ebay_use_stock_image                   = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_use_stock_image' );

		if ( ! empty( $_umb_ebay_prefill_listing_with_ebay_catalog ) ) {
			$item['ProductListingDetails']['IncludeeBayProductDetails'] = $_umb_ebay_prefill_listing_with_ebay_catalog;
		}
		if ( ! empty( $_umb_ebay_use_stock_image ) ) {
			$item['ProductListingDetails']['IncludeStockPhotoURL'] = $_umb_ebay_use_stock_image;
		}

		if ( ! empty( $product_custom_condtion ) ) {
			$item['ConditionDescription'] = $product_custom_condtion;
		}

		$item['CategoryMappingAllowed'] = true;
		if ( '' != $conditionID && null != $conditionID ) {
			$item['ConditionID'] = $conditionID;
		} else {
			$item['ConditionID'] = 1000;
		}
		if ( ! empty( $amount ) || 0 == $amount ) {
			$item['Quantity'] = $amount;
		}
		$item['ListingDuration'] = ! empty( $listingDuration ) ? $listingDuration : 'GTC';
		$item['ListingType']     = 'FixedPriceItem';

		$wcDimensionUnit = get_option( 'woocommerce_dimension_unit' );
		if ( ! empty( $wcDimensionUnit ) ) {
			$lengthValue                                     = ! empty( get_post_meta( $proIds, '_length', true ) ) ? get_post_meta( $proIds, '_length', true ) : 0;
			$lengthXml                                       = '<PackageLength unit="' . $wcDimensionUnit . '">' . $lengthValue . '</PackageLength>';
			$item['ShippingPackageDetails']['PackageLength'] = 'cedPackageLength';

			$widthValue                                     = ! empty( get_post_meta( $proIds, '_width', true ) ) ? get_post_meta( $proIds, '_width', true ) : 0;
			$widthXml                                       = '<PackageWidth unit="' . $wcDimensionUnit . '">' . $widthValue . '</PackageWidth>';
			$item['ShippingPackageDetails']['PackageWidth'] = 'cedPackageWidth';

			$heightValue                                    = ! empty( get_post_meta( $proIds, '_height', true ) ) ? get_post_meta( $proIds, '_height', true ) : 0;
			$heightXml                                      = '<PackageDepth unit="' . $wcDimensionUnit . '">' . $heightValue . '</PackageDepth>';
			$item['ShippingPackageDetails']['PackageDepth'] = 'cedPackageDepth';
		}

		$productWeight = get_post_meta( $proIds, '_weight', true );

		if ( '' == $productWeight ) {
			$productWeight = 0;
		}

		$themeId = get_post_meta( $proIds, 'ced_umb_ebay_product_template', true );
		if ( '' != $themeId || null != $themeId ) {
			$item['ListingDesigner']['OptimalPictureSize'] = true;
			$item['ListingDesigner']['ThemeID']            = $themeId;
		}
		$private_listing = get_post_meta( $proIds, '_umb_ebay_private_listing', true );
		if ( 'yes' == $private_listing ) {
			$item['PrivateListing'] = true;
		}
		$configInstance     = \Ced_Ebay_WooCommerce_Core\Ebayconfig::get_instance();
		$countyDetails      = $configInstance->getEbaycountrDetail( $siteID );
		$country            = $countyDetails['countrycode'];
		$currency           = $countyDetails['currency'][0];
		$item_country       = ! empty( $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_item_location_country'] ) ? $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_item_location_country'] : $country;
		$item['Country']    = $item_country;
		$item['Currency']   = $currency;
		$item['PostalCode'] = ! empty( $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_postal_code'] ) ? $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_postal_code'] : '';

		$item['PictureDetails']['PictureURL'] = 'cedPicture';

		$str_pictures = '<PictureURL>' . utf8_uri_encode( strtok( $pictureUrl, '?' ) ) . '</PictureURL>';
		if ( ! empty( $ebayhostedUrl ) && is_array( $ebayhostedUrl ) ) {

			foreach ( $ebayhostedUrl as $key => $url ) {

				$str_pictures .= '<PictureURL>' . utf8_uri_encode( $url ) . '</PictureURL>';

			}
		} else {

			$attachment_ids = $product->get_gallery_image_ids();
			if ( ! empty( $attachment_ids ) ) {
				foreach ( $attachment_ids as $attachment_id ) {
					if ( ! empty( wp_get_attachment_url( $attachment_id ) ) ) {
						$img_urls = wp_get_attachment_url( $attachment_id );
						if ( strpos( $img_urls, 'https' ) === false ) {
							$img_urls = str_replace( 'http', 'https', $img_urls );
						}
						$str_pictures .= '<PictureURL>' . utf8_uri_encode( strtok( $img_urls, '?' ) ) . '</PictureURL>';
					}
				}
			}
		}

		if ( 'variable' == $productType ) {
			$xmlArray['MessageID'] = $proIds;
			$xmlArray['Item']      = $item;
			$rootElement           = 'Item';
			$xml                   = new SimpleXMLElement( "<$rootElement/>" );
			$this->array2XML( $xml, $xmlArray['Item'] );
		} else {
			$xmlArray['MessageID'] = $proIds;
			$xmlArray['Item']      = $item;
			$rootElement           = 'Item';
			$xml                   = new SimpleXMLElement( "<$rootElement/>" );
			$this->array2XML( $xml, $xmlArray['Item'] );
		}
		$val = $xml->asXML();
		if ( false !== strpos( $val, '<ItemSpecifics>ced</ItemSpecifics>' ) ) {
			$val = str_replace( '<ItemSpecifics>ced</ItemSpecifics>', $nameValueList, $val );
		}
		if ( false !== strpos( $val, '<DispatchTimeMax>cedDispatchTime</DispatchTimeMax>' ) ) {
			$val = str_replace( '<DispatchTimeMax>cedDispatchTime</DispatchTimeMax>', $dispatch_time_xml, $val );
		}

		if ( false !== strpos( $val, '<PictureURL>cedPicture</PictureURL>' ) ) {
			$val = str_replace( '<PictureURL>cedPicture</PictureURL>', $str_pictures, $val );
		}

		if ( false !== strpos( $val, '<WeightMajor>cedWeightMajor</WeightMajor>' ) ) {
			$val = str_replace( '<WeightMajor>cedWeightMajor</WeightMajor>', $weight_major_xml, $val );
		}

		if ( false !== strpos( $val, '<PackageLength>cedPackageLength</PackageLength>' ) ) {
			$val = str_replace( '<PackageLength>cedPackageLength</PackageLength>', $lengthXml, $val );
		}

		if ( false !== strpos( $val, '<PackageWidth>cedPackageWidth</PackageWidth>' ) ) {
			$val = str_replace( '<PackageWidth>cedPackageWidth</PackageWidth>', $widthXml, $val );
		}

		if ( false !== strpos( $val, '<PackageDepth>cedPackageDepth</PackageDepth>' ) ) {
			$val = str_replace( '<PackageDepth>cedPackageDepth</PackageDepth>', $heightXml, $val );
		}

		$finalXml .= $val;

		++$counter;

		$finalXml = str_replace( '<?xml version="1.0"?>', '', $finalXml );
		if ( 'variable' == $productType ) {
			if ( ! empty( $ebayItemID ) ) {
				$xmlHeader = '<?xml version="1.0" encoding="utf-8"?>
				<ReviseFixedPriceItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
					<RequesterCredentials>
						<eBayAuthToken>' . $token . '</eBayAuthToken>
					</RequesterCredentials>
					<MessageID>' . $proIds . '</MessageID>
					<Version>1267</Version>
					<ErrorLanguage>en_US</ErrorLanguage>
					<WarningLevel>High</WarningLevel>';
				$xmlFooter = '</ReviseFixedPriceItemRequest>';
			} else {
				$xmlHeader = '<?xml version="1.0" encoding="utf-8"?>
				<AddFixedPriceItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
					<RequesterCredentials>
						<eBayAuthToken>' . $token . '</eBayAuthToken>
					</RequesterCredentials>
					<MessageID>' . $proIds . '</MessageID>
					<Version>1267</Version>
					<ErrorLanguage>en_US</ErrorLanguage>
					<WarningLevel>High</WarningLevel>';
				$xmlFooter = '</AddFixedPriceItemRequest>';
			}
		} elseif ( ! empty( $ebayItemID ) ) {
				$xmlHeader = '<?xml version="1.0" encoding="utf-8"?>
				<ReviseItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
					<RequesterCredentials>
						<eBayAuthToken>' . $token . '</eBayAuthToken>
					</RequesterCredentials>
					<MessageID>' . $proIds . '</MessageID>
					<Version>1267</Version>
					<ErrorLanguage>en_US</ErrorLanguage>
					<WarningLevel>High</WarningLevel>';
				$xmlFooter = '</ReviseItemRequest>';
		} else {
			$xmlHeader = '<?xml version="1.0" encoding="utf-8"?>
				<AddItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
					<RequesterCredentials>
						<eBayAuthToken>' . $token . '</eBayAuthToken>
					</RequesterCredentials>
					<MessageID>' . $proIds . '</MessageID>
					<Version>1267</Version>
					<ErrorLanguage>en_US</ErrorLanguage>
					<WarningLevel>High</WarningLevel>';
			$xmlFooter = '</AddItemRequest>';
		}

		$mainXML = $xmlHeader . $finalXml . $xmlFooter;
		if ( 'variable' == $productType ) {
			if ( '' != $VariationSpecificsFinalSet && null != $VariationSpecificsFinalSet ) {
				$mainXML = str_replace( '<Variations>ced</Variations>', $VariationSpecificsFinalSet, $mainXML );
			}
		}

		if ( 'variable' == $productType ) {
			return array( $mainXML, true );
		} else {
			return array( $mainXML, false );
		}
	}



	public function ced_ebay_recursive_find_category_id( $needle, $haystack ) {
		foreach ( $haystack as $key => $value ) {
			if ( isset( $value['ChildCategory'] ) ) {
				if ( isset( $value['CategoryID'] ) && $value['CategoryID'] == $needle ) {
					return $value['Name'];
				} else {
					$nextKey = $this->ced_ebay_recursive_find_category_id( $needle, $value['ChildCategory'] );
					if ( $nextKey ) {
						return $nextKey;
					}
				}
			} elseif ( isset( $value['CategoryID'] ) && $value['CategoryID'] == $needle ) {
				return $value['Name'];
			}
		}
		return false;
	}



	public function getFormattedDataForVariation( $proIDs, $site_id, $userId = '' ) {
		$shop_data = ced_ebay_get_shop_data( $userId, $site_id );
		if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
			$siteID          = $site_id;
				$token       = $shop_data['access_token'];
				$getLocation = $shop_data['location'];
		} else {
			return 'Unable to verify eBay user';
		}
		$_product            = wc_get_product( $proIDs );
		$variation_attribute = $_product->get_variation_attributes();
		$allVariations       = $_product->get_children();
		$primarycatId        = $this->fetchMetaValueOfProduct( $proIDs, '_umb_ebay_category' );

		$file             = CED_EBAY_DIRPATH . 'admin/ebay/lib/cedGetcategories.php';
		$renderDependency = $this->renderDependency( $file );

		$variationspecificsset  = '';
		$variationspecificsset .= '<VariationSpecificsSet>';

		foreach ( $variation_attribute as $attr_name => $attr_value ) {
			$taxonomy          = $attr_name;
			$attr_name         = str_replace( 'pa_', '', $attr_name );
			$attr_name         = str_replace( 'attribute_', '', $attr_name );
			$attr_name         = wc_attribute_label( $attr_name, $_product );
			$attr_name_by_slug = get_taxonomy( $taxonomy );
			if ( is_object( $attr_name_by_slug ) ) {
				$attr_name = $attr_name_by_slug->label;
			}
			$variationspecificsset .= '<NameValueList>';
			if ( 'Quantity' == $attr_name || 'Type' == $attr_name || 'Größe' == $attr_name || 'Size' == $attr_name || 'Colour' == $attr_name || 'Color' == $attr_name ) {
				$variationspecificsset .= '<Name>Product ' . $attr_name . '</Name>';
			} else {
				$variationspecificsset .= '<Name>' . $attr_name . '</Name>';
			}
			foreach ( $attr_value as $k => $v ) {
				$termObj = get_term_by( 'slug', $v, $taxonomy );
				if ( is_object( $termObj ) ) {
					$term_name = $termObj->name;
					if ( strpos( $term_name, '&' ) !== false ) {
						$term_name = str_replace( '&', '&amp;', $term_name );
					}
					$variationspecificsset .= '<Value>' . $term_name . '</Value>';
				} else {
					if ( strpos( $v, '&' ) !== false ) {
						$v = str_replace( '&', '&amp;', $v );
					}
					$variationspecificsset .= '<Value>' . $v . '</Value>';
				}
			}
			$variationspecificsset .= '</NameValueList>';
		}
		$variationspecificsset .= '</VariationSpecificsSet>';
		$variation              = '';
		foreach ( $allVariations as $key => $Id ) {
			// add a foreach for each finish of the product
			$var_attr   = wc_get_product_variation_attributes( $Id );
			$variation .= '<Variation>';
			$mpn        = $this->fetchMetaValueOfProduct( $Id, '_umb_ebay_mpn' );
			$ean        = $this->fetchMetaValueOfProduct( $Id, '_umb_ebay_ean' );
			$isbn       = $this->fetchMetaValueOfProduct( $Id, '_umb_ebay_isbn' );
			$upc        = $this->fetchMetaValueOfProduct( $Id, '_umb_ebay_upc' );

			if ( empty( $mpn ) ) {
				$mpn = 'Does Not Apply';
			}
			if ( empty( $ean ) ) {
				$ean = 'Does Not Apply';
			}
			if ( empty( $upc ) ) {
				$upc = 'Does Not Apply';
			}
			if ( '' != $ean || '' != $isbn || '' != $upc ) {
				$variation .= '<VariationProductListingDetails>';
				if ( '' != $ean ) {
					$variation .= '<EAN>' . $ean . '</EAN>';
				}
				if ( '' != $isbn ) {
					$variation .= '<ISBN>' . $isbn . '</ISBN>';
				}
				if ( '' != $upc ) {
					$variation .= '<UPC>' . $upc . '</UPC>';
				}
				$variation .= '</VariationProductListingDetails>';
			} else {
				$variation .= '<VariationProductListingDetails>';
				$variation .= '<EAN>Does Not Apply</EAN>';
				$variation .= '<UPC>Does Not Apply</UPC>';
				$variation .= '<ISBN>Does Not Apply</ISBN>';
				$variation .= '</VariationProductListingDetails>';
			}
			$renderDataOnGlobalSettings = get_option( 'ced_ebay_global_settings', false );
			$amount                     = get_post_meta( $Id, '_stock', true );
			$manage_stock               = get_post_meta( $Id, '_manage_stock', true );
			$product_status             = get_post_meta( $Id, '_stock_status', true );

			$listing_stock_type = isset( $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_stock_type'] ) ? $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_stock_type'] : '';
			$listing_stock      = isset( $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_listing_stock'] ) ? $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_listing_stock'] : '';

			if ( 'yes' != $manage_stock && 'instock' == $product_status ) {
				$renderDataOnGlobalSettings = get_option( 'ced_ebay_global_settings', false );
				if ( ! empty( $listing_stock_type ) && ! empty( $listing_stock ) && 'MaxStock' == $listing_stock_type ) {
					$amount = $listing_stock;
				} else {
					$amount = 1;
				}
			} elseif ( 'outofstock' != $product_status ) {
				if ( ! empty( $listing_stock_type ) && ! empty( $listing_stock ) && 'MaxStock' == $listing_stock_type ) {
					if ( $amount > $listing_stock ) {
						$amount = $listing_stock;
					} else {
						$amount = intval( $amount );
						if ( $amount < 1 ) {
							$amount = '0';
						}
					}
				} else {
					$amount = intval( $amount );
					if ( $amount < 1 ) {
						$amount = '0';
					}
				}
			} else {
				$amount = 0;
			}

			$var_prod             = wc_get_product( $Id );
			$dataInGlobalSettings = ! empty( get_option( 'ced_ebay_global_settings', false ) ) ? get_option( 'ced_ebay_global_settings', false ) : '';
			$price_selection      = isset( $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_price_option'] ) ? $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_price_option'] : '';
			if ( 'Regular_Price' == $price_selection ) {
				$price = $var_prod->get_regular_price();
			} elseif ( 'Sale_Price' == $price_selection ) {
				$price = $var_prod->get_sale_price();
			} else {
				$price = $var_prod->get_price();
			}
			$profile_price_markup_type = $this->fetchMetaValueOfProduct( $proIDs, '_umb_ebay_profile_price_markup_type' );
			$profile_price_markup      = $this->fetchMetaValueOfProduct( $proIDs, '_umb_ebay_profile_price_markup' );
			if ( get_option( 'ced_ebay_global_settings', false ) ) {
				$dataInGlobalSettings = get_option( 'ced_ebay_global_settings', false );
				$price_markup_type    = isset( $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_markup_type'] ) ? $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_markup_type'] : '';
				$price_markup_value   = isset( $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_markup'] ) ? $dataInGlobalSettings[ $userId ][ $siteID ]['ced_ebay_product_markup'] : '';
			}
			if ( ! empty( $profile_price_markup_type ) && ! empty( $profile_price_markup ) ) {
				if ( 'Fixed_Increase' == $profile_price_markup_type ) {
					$price = $price + $profile_price_markup;
				} elseif ( 'Percentage_Increase' == $profile_price_markup_type ) {
					$price = $price + ( ( $price * $profile_price_markup ) / 100 );
				} elseif ( 'Percentage_Decrease' == $profile_price_markup_type ) {
					$price = $price - ( ( $price * $profile_price_markup ) / 100 );
				} elseif ( 'Fixed_Decrease' == $profile_price_markup_type ) {
					$price = $price - $profile_price_markup;
				}
			} elseif ( ! empty( $price_markup_type ) && ! empty( $price_markup_value ) ) {
				if ( 'Percentage_Increased' == $price_markup_type ) {
					$price = $price + ( ( $price * $price_markup_value ) / 100 );

				} elseif ( 'Fixed_Increased' == $price_markup_type ) {
					$price = $price + $price_markup_value;
				} elseif ( 'Percentage_Decreased' == $price_markup_type ) {
					$price = $price - ( ( $price * $price_markup_value ) / 100 );
				} elseif ( 'Fixed_Decreased' == $price_markup_type ) {
					$price = $price - $price_markup_value;
				}
			}

			$sku = get_post_meta( $Id, '_sku', true );
			if ( empty( $sku ) ) {
				$sku = $Id;
			}

			$var_image_id = $var_prod->get_image_id();
			if ( ! empty( $var_image_id ) ) {
				$var_image_array = wp_get_attachment_image_src( $var_image_id, 'full' );
				$var_image_src   = $var_image_array[0];
			}

			$variation .= '<StartPrice>' . $price . '</StartPrice>
		<Quantity>' . $amount . '</Quantity><SKU>' . $sku . '</SKU>';
			$variation .= '<VariationSpecifics>';
			foreach ( $var_attr as $key => $value ) {
				$taxonomy          = $key;
				$atr_name          = str_replace( 'attribute_', '', $key );
				$taxonomy          = $atr_name;
				$atr_name          = str_replace( 'pa_', '', $atr_name );
				$atr_name          = wc_attribute_label( $atr_name, $_product );
				$termObj           = get_term_by( 'slug', $value, $taxonomy );
				$attr_name_by_slug = get_taxonomy( $taxonomy );

				if ( is_object( $attr_name_by_slug ) ) {
					$atr_name = $attr_name_by_slug->label;
				}

				if ( is_object( $termObj ) ) {
					$term_name = $termObj->name;
					if ( strpos( $term_name, '&' ) !== false ) {
						$term_name = str_replace( '&', '&amp;', $term_name );
					}
					$variation .= '<NameValueList><Name>' . $atr_name . '</Name><Value>' . $term_name . '</Value></NameValueList>';
					if ( ! empty( $additional_image_url ) ) {
						$variation_img[ $atr_name ][] = array(
							'term_name' => $term_name,
							'image_set' => $additional_image_url,
						);
					} elseif ( ! empty( $var_image_src ) ) {
						$variation_img[ $atr_name ][] = array(
							'term_name' => $term_name,
							'image_set' => $var_image_src,
						);
					}
				} else {
					if ( strpos( $value, '&' ) !== false ) {
						$value = str_replace( '&', '&amp;', $value );
					}
					if ( 'Quantity' == $attr_name || 'Type' == $attr_name || 'Größe' == $attr_name || 'Size' == $attr_name || 'Colour' == $attr_name || 'Color' == $attr_name ) {
						$variation .= '<NameValueList><Name>Product ' . $atr_name . '</Name><Value>' . $value . '</Value></NameValueList>';
					} else {
						$variation .= '<NameValueList><Name>' . $atr_name . '</Name><Value>' . $value . '</Value></NameValueList>';
					}                   if ( ! empty( $additional_image_url ) ) {
						$variation_img[ $atr_name ][] = array(
							'term_name' => $value,
							'image_set' => $additional_image_url,
						);
					} elseif ( ! empty( $var_image_src ) ) {
						$variation_img[ $atr_name ][] = array(
							'term_name' => $value,
							'image_set' => $var_image_src,
						);
					}
				}
			}
			$variation .= '</VariationSpecifics>';
			$variation .= '</Variation>';
		}
		$var_img_xml = '';
		if ( ! empty( $variation_img ) ) {
			$var_img_xml .= '<Pictures>';
			$terms        = array();
			foreach ( $variation_img as $attr_name => $attr_values ) {
				if ( 'Quantity' == $attr_name || 'Type' == $attr_name || 'Größe' == $attr_name || 'Size' == $attr_name || 'Colour' == $attr_name || 'Color' == $attr_name ) {
					$var_img_xml .= ' <VariationSpecificName>Product ' . $attr_name . '</VariationSpecificName>';

				} else {
						$var_img_xml .= ' <VariationSpecificName>' . $attr_name . '</VariationSpecificName>';

				}               foreach ( $attr_values as $data_attr ) {
					if ( in_array( $data_attr['term_name'], $terms ) ) {
						continue;
					}
					$terms[]      = $data_attr['term_name'];
					$var_img_xml .= '<VariationSpecificPictureSet>';
					$var_img_xml .= '<VariationSpecificValue>' . $data_attr['term_name'] . '</VariationSpecificValue>';
					if ( ! empty( $data_attr['image_set'] ) && is_array( $data_attr['image_set'] ) ) {
						foreach ( $data_attr['image_set'] as $key => $additional_var_images ) {
							if ( strpos( $additional_var_images, 'https' ) === false ) {
								$additional_var_images = str_replace( 'http', 'https', $additional_var_images );
							}
							$var_img_xml .= '<PictureURL>' . utf8_uri_encode( strtok( $additional_var_images, '?' ) ) . '</PictureURL>';
						}
					} else {
						if ( strpos( $data_attr['image_set'], 'https' ) === false ) {
							$data_attr['image_set'] = str_replace( 'http', 'https', $data_attr['image_set'] );
						}
						$var_img_xml .= '<PictureURL>' . utf8_uri_encode( strtok( $data_attr['image_set'], '?' ) ) . '</PictureURL>';
					}
					$var_img_xml .= '</VariationSpecificPictureSet>';
				}
				break;
			}
			$var_img_xml .= '</Pictures>';
		}

		$main_attribute = '<Variations>' . $var_img_xml . $variationspecificsset . $variation . '</Variations>';
		return $main_attribute;
	}

	/*
	 *
	 *function for getting profile data of the product
	 *
	 *
	 */
	public function ced_ebay_getProfileAssignedData( $proIds, $userId, $site_id ) {
		global $wpdb;
		$productData = wc_get_product( $proIds );
		$product     = $productData->get_data();
		$category_id = isset( $product['category_ids'] ) ? $product['category_ids'] : array();
		if ( ! empty( $category_id ) ) {
			rsort( $category_id );
		}
		$productTemplateData = get_post_meta( $proIds, 'ced_ebay_product_level_profile_data', true );
		if ( ! empty( $productTemplateData ) && isset( $productTemplateData[ $userId . '>' . $site_id ]['_umb_ebay_category'] ) ) {
			$this->isProfileAssignedToProduct = true;
			$this->profile_data               = $productTemplateData[ $userId . '>' . $site_id ];
			return $this->profile_data;
		}
		$profile_id = get_post_meta( $proIds, 'ced_ebay_profile_assigned' . $userId, true );
		if ( ! empty( $profile_id ) ) {
			$profile_id = $profile_id;
		} else {
			foreach ( $category_id as $key => $value ) {
				$profile_id = get_term_meta( $value, 'ced_ebay_profile_id_' . $userId . '>' . $site_id, true );
				if ( ! empty( $profile_id ) ) {
					break;

				}
			}
		}
		if ( isset( $profile_id ) && ! empty( $profile_id ) && '' != $profile_id ) {
			$this->isProfileAssignedToProduct = true;
			$profile_data                     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_ebay_profiles WHERE `id`=%s AND `ebay_user`=%s AND `ebay_site`=%s", $profile_id, $userId, $site_id ), 'ARRAY_A' );
			if ( is_array( $profile_data ) ) {
				$profile_data = isset( $profile_data[0] ) ? $profile_data[0] : $profile_data;
				$profile_data = isset( $profile_data['profile_data'] ) ? json_decode( $profile_data['profile_data'], true ) : array();

			}
		} else {
			$this->isProfileAssignedToProduct = false;
		}
		$this->profile_data = isset( $profile_data ) ? $profile_data : '';
	}

	/*
	 *
	 *function for getting meta value of the product
	 *
	 *
	 */
	public function fetchMetaValueOfProduct( $proIds, $metaKey ) {
		if ( isset( $this->isProfileAssignedToProduct ) && $this->isProfileAssignedToProduct ) {
			$value       = '';
			$_product    = wc_get_product( $proIds );
			$productData = $_product->get_data();

			if ( is_bool( $_product ) ) {
				return;
			}

			if ( 'variation' == $_product->get_type() ) {
				$parentId = $_product->get_parent_id();
			} else {
				$parentId = '0';
			}

			$productLevelValue = '';

			if ( ! empty( $this->profile_data ) && isset( $this->profile_data[ $metaKey ] ) ) {
				$profileData     = $this->profile_data[ $metaKey ];
				$tempProfileData = $profileData;
				if ( false !== strpos( $metaKey, '_Brand' ) ) {
					$brandNameValue = '';
					global $wpdb;
					// Get all 'brand' taxnomy names from DB
					$brandTaxonomyNames = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT DISTINCT `taxonomy`
			FROM `{$wpdb->prefix}term_taxonomy`
			WHERE `taxonomy` LIKE %s
			LIMIT 50",
							'%brand%'
						),
						'ARRAY_A'
					);
					if ( empty( $wpdb->last_error ) && ! empty( $brandTaxonomyNames ) && is_array( $brandTaxonomyNames ) ) {
						foreach ( $brandTaxonomyNames as $bKey => $brandTaxName ) {
							$brand_taxonomy_name = $brandTaxName['taxonomy'];
							if ( empty( $brandTaxName ) ) {
								continue;
							}
							$brand_names = wp_get_post_terms( $proIds, $brand_taxonomy_name, array( 'fields' => 'names' ) );
							if ( empty( $brand_names ) || is_wp_error( $brand_names ) ) {
								continue;
							}
							$brandNameValue = $brand_names[0];
							if ( ! empty( $brandNameValue ) ) {
								break;
							}
						}
						if ( ! empty( $brandNameValue ) ) {
							return $brandNameValue;
						}
					}
				}
				if ( ! empty( $parentId ) ) {
					if ( ! empty( get_post_meta( $parentId, 'ced_ebay_product_' . $metaKey, true ) ) ) {
						$productLevelValue = get_post_meta( $parentId, 'ced_ebay_product_' . $metaKey, true );
					}
				} elseif ( ! empty( get_post_meta( $proIds, 'ced_ebay_product_' . $metaKey, true ) ) ) {
						$productLevelValue = get_post_meta( $proIds, 'ced_ebay_product_' . $metaKey, true );
				}

				if ( ! empty( $productLevelValue ) ) {
					return $productLevelValue;
				}

				if ( ! empty( $tempProfileData['default'] ) && empty( $tempProfileData['metakey'] ) ) {
					if ( '{product_title}' == $tempProfileData['default'] ) {
						if ( ! empty( $parentId ) ) {
							$parent_product = wc_get_product( $parentId );
							$prnt_prd_data  = $parent_product->get_data();
							$prd_title      = $prnt_prd_data['name'];
							$value          = $prd_title;
						} else {
							$prd_data  = $_product->get_data();
							$prd_title = $prd_data['name'];
							$value     = $prd_title;
						}
					} else {
						$value = $tempProfileData['default'];
					}
				} elseif ( isset( $tempProfileData['metakey'] ) && ! empty( $tempProfileData['metakey'] ) && 'null' != $tempProfileData['metakey'] ) {

					if ( false !== strpos( $tempProfileData['metakey'], 'umb_pattr_' ) ) {

						$wooAttribute = explode( 'umb_pattr_', $tempProfileData['metakey'] );
						$wooAttribute = end( $wooAttribute );

						if ( 'variation' == $_product->get_type() ) {
							$var_product = wc_get_product( $parentId );
							$attributes  = $var_product->get_variation_attributes();
							if ( isset( $attributes[ 'attribute_pa_' . $wooAttribute ] ) && ! empty( $attributes[ 'attribute_pa_' . $wooAttribute ] ) ) {
								$wooAttributeValue = $attributes[ 'attribute_pa_' . $wooAttribute ];
								if ( '0' != $parentId ) {
									$product_terms = get_the_terms( $parentId, 'pa_' . $wooAttribute );
								} else {
									$product_terms = get_the_terms( $proIds, 'pa_' . $wooAttribute );
								}
							} else {
								$wooAttributeValue = $var_product->get_attribute( 'pa_' . $wooAttribute );
								$wooAttributeValue = explode( ',', $wooAttributeValue );
								$wooAttributeValue = $wooAttributeValue[0];

								if ( '0' != $parentId ) {
									$product_terms = get_the_terms( $parentId, 'pa_' . $wooAttribute );
								} else {
									$product_terms = get_the_terms( $proIds, 'pa_' . $wooAttribute );
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
									$value = get_post_meta( $proIds, $metaKey, true );
								}
							} else {
								$value = get_post_meta( $proIds, $metaKey, true );
							}
						} else {
							$wooAttributeValue = $_product->get_attribute( 'pa_' . $wooAttribute );
							$product_terms     = get_the_terms( $proIds, 'pa_' . $wooAttribute );
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
									$value = get_post_meta( $proIds, $metaKey, true );
								}
							} else {
								$value = get_post_meta( $proIds, $metaKey, true );
							}
						}
					} elseif ( false !== strpos( $tempProfileData['metakey'], 'ced_cstm_attrb_' ) ) {
						$custom_prd_attrb = explode( 'ced_cstm_attrb_', $tempProfileData['metakey'] );
						$custom_prd_attrb = end( $custom_prd_attrb );
						$wooAttribute     = $custom_prd_attrb;
						if ( ! empty( $wooAttribute ) ) {
							if ( 'variation' == $_product->get_type() ) {
								$var_product = wc_get_product( $parentId );
								$attributes  = $var_product->get_variation_attributes();
								if ( isset( $attributes[ 'attribute_' . $wooAttribute ] ) && ! empty( $attributes[ 'attribute_' . $wooAttribute ] ) ) {
									$wooAttributeValue = $attributes[ 'attribute_' . $wooAttribute ];
									if ( '0' != $parentId ) {
										$product_terms = get_the_terms( $parentId, $wooAttribute );
									} else {
										$product_terms = get_the_terms( $proIds, $wooAttribute );
									}
								} else {
									$wooAttributeValue = $var_product->get_attribute( $wooAttribute );
									$wooAttributeValue = explode( ',', $wooAttributeValue );
									$wooAttributeValue = $wooAttributeValue[0];

									if ( '0' != $parentId ) {
										$product_terms = get_the_terms( $parentId, $wooAttribute );
									} else {
										$product_terms = get_the_terms( $proIds, $wooAttribute );
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
										$value = get_post_meta( $proIds, $metaKey, true );
									}
								} else {
									$value = get_post_meta( $proIds, $metaKey, true );
								}
							} else {
								$wooAttributeValue = $_product->get_attribute( $wooAttribute );
								if ( ! empty( $wooAttributeValue ) ) {
									$value = $wooAttributeValue;
								}
							}
						}
					} elseif ( false !== strpos( $tempProfileData['metakey'], 'ced_product_tags' ) ) {
						$terms             = get_the_terms( $proIds, 'product_tag' );
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
					} elseif ( false !== strpos( $tempProfileData['metakey'], 'ced_product_cat_single' ) ) {
						// Use the product category as the value for the mapped metakey in the profile.

						$category_ids = isset( $productData['category_ids'] ) ? $productData['category_ids'] : array();
						if ( ! empty( $category_ids ) ) {
							$category_ids_length = count( $category_ids );
							if ( 0 < $category_ids_length ) {
								$product_last_category = $category_ids[ $category_ids_length - 1 ];
								if ( ! empty( $product_last_category ) ) {
									$product_cat_term = get_term_by( 'term_id', $product_last_category, 'product_cat' );
									if ( ! is_wp_error( $product_cat_term ) && is_object( $product_cat_term ) ) {
										$product_cat_name_single = $product_cat_term->name;
										$value                   = $product_cat_name_single;
									}
								}
							}
						}
					} elseif ( false !== strpos( $tempProfileData['metakey'], 'ced_product_cat_hierarchy' ) ) {
						// Use the product category hierarchy as the value for the mapped metakey in the profile.
						$term_names_array  = wp_get_post_terms( $proIds, 'product_cat', array( 'fields' => 'names' ) ); // Array of product category term names
						$term_names_string = count( $term_names_array ) > 0 ? implode( ', ', $term_names_array ) : ''; // Convert to a coma separated string
						$value             = ! empty( $term_names_string ) ? $term_names_string : '';
					} elseif ( false !== strpos( $tempProfileData['metakey'], 'acf_' ) ) {
						$acf_field        = explode( 'acf_', $tempProfileData['metakey'] );
						$acf_field        = end( $acf_field );
						$acf_field_object = get_field_object( $acf_field, $proIds );
						$value            = '';
						if ( isset( $acf_field_object['value'] ) && $acf_field_object['value'] instanceof \WP_Post ) {
							$value = $acf_field_object['value']->post_title;
						} else {
							$value = $acf_field_object['value'];
						}
					} else {
						$value = get_post_meta( $proIds, $tempProfileData['metakey'], true );
						if ( '_thumbnail_id' == $tempProfileData['metakey'] ) {
							$value = wp_get_attachment_image_url( get_post_meta( $proIds, '_thumbnail_id', true ), 'thumbnail' ) ? wp_get_attachment_image_url( get_post_meta( $proIds, '_thumbnail_id', true ), 'thumbnail' ) : '';
						}
						if ( ! isset( $value ) || empty( $value ) || '' == $value || is_null( $value ) || '0' == $value || 'null' == $value ) {
							if ( '0' != $parentId ) {

								$value = get_post_meta( $parentId, $tempProfileData['metakey'], true );
								if ( '_thumbnail_id' == $tempProfileData['metakey'] ) {
									$value = wp_get_attachment_image_url( get_post_meta( $parentId, '_thumbnail_id', true ), 'thumbnail' ) ? wp_get_attachment_image_url( get_post_meta( $parentId, '_thumbnail_id', true ), 'thumbnail' ) : '';
								}

								if ( ! isset( $value ) || empty( $value ) || '' == $value || is_null( $value ) ) {
									$value = get_post_meta( $proIds, $metaKey, true );

								}
							} else {
								$value = get_post_meta( $proIds, $metaKey, true );
							}
						}
					}
				} else {
					$value = get_post_meta( $proIds, $metaKey, true );
				}
			} else {
				$value = get_post_meta( $proIds, $metaKey, true );
			}
			if ( '' == $value ) {
				if ( isset( $tempProfileData['default'] ) && ! empty( $tempProfileData['default'] ) && '' != $tempProfileData['default'] && ! is_null( $tempProfileData['default'] ) ) {
					$value = $tempProfileData['default'];
				}
			}
			return $value;
		}
	}


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


	public function renderDependency( $file ) {
		if ( null != $file || '' != $file ) {
			require_once "$file";
			return true;
		}
		return false;
	}

	public function ced_ebay_prepareDataForSetNotificationPreferences( $notificationType, $userId ) {
		if ( ! empty( $notificationType ) ) {
			$shop_data = ced_ebay_get_shop_data( $userId );
			if ( ! empty( $shop_data ) ) {
				$siteID      = $shop_data['site_id'];
				$token       = $shop_data['access_token'];
				$getLocation = $shop_data['location'];
			}

			$xmlHeader                   = '<?xml version="1.0" encoding="utf-8"?>
	<SetNotificationPreferencesRequest xmlns="urn:ebay:apis:eBLBaseComponents">
	  <RequesterCredentials>
		<eBayAuthToken>' . $token . '</eBayAuthToken>
	  </RequesterCredentials>
	  <DeliveryURLName>' . $userId . '</DeliveryURLName>
		<ErrorLanguage>en_US</ErrorLanguage>
		<WarningLevel>High</WarningLevel>';
			$xmlFooter                   = '</SetNotificationPreferencesRequest>';
			$set_delivery_preference     = array(
				'AlertEmail'         => 'mailto://alirizvi@cedcommerce.com',
				'AlertEnable'        => 'Enable',
				'ApplicationEnable'  => 'Enable',
				'ApplicationURL'     => 'https://cedcommerce.com',
				'DeliveryURLDetails' => array(
					'DeliveryURL'     => get_site_url() . '/wp-admin/admin-ajax.php?action=ced_ebay_notification_endpoint',
					'DeliveryURLName' => $userId,
				),
				'DeviceType'         => 'Platform',
				'PayloadVersion'     => '1173',
			);
			$set_delivery_preference_xml = new SimpleXMLElement( '<ApplicationDeliveryPreferences/>' );
			$this->array2XML( $set_delivery_preference_xml, $set_delivery_preference );
			$set_delivery_preference_xml = $set_delivery_preference_xml->asXML();
			$set_delivery_preference_xml = str_replace( '<?xml version="1.0"?>', '', $set_delivery_preference_xml );

			$set_notification_preference['NotificationEnable']['EventType']   = $notificationType;
			$set_notification_preference['NotificationEnable']['EventEnable'] = 'Enable';
			$set_notification_xml = new SimpleXMLElement( '<UserDeliveryPreferenceArray/>' );
			$this->array2XML( $set_notification_xml, $set_notification_preference );
			$set_notification_xml = $set_notification_xml->asXML();
			$set_notification_xml = str_replace( '<?xml version="1.0"?>', '', $set_notification_xml );

			$mainXML = $xmlHeader . $set_delivery_preference_xml . $set_notification_xml . $xmlFooter;
			return $mainXML;
		} else {
			echo 'No Notification Type Received';
			die;
		}
	}

	public function ced_ebay_prepareProductHtmlForUpdatingSKU( $userId, $proIDs = array() ) {
		foreach ( $proIDs as $key => $value ) {
			$prod_data = wc_get_product( $value );
			$type      = $prod_data->get_type();
			if ( 'variable' == $type ) {
				$item_id      = get_post_meta( $value, '_ced_ebay_listing_id_' . $userId, true );
				$preparedData = $this->getFormattedDataForUpdatingSKU( $value, $userId, $item_id );
				return $preparedData;
			} else {
				return 'This action only works for Variable Products on WooCommerce!';
			}
		}
	}

	public function prepareDataForUploadingImageToEPS( $user_id, $picture_url ) {

		if ( ! empty( $user_id ) ) {
			if ( ! empty( $shop_data ) ) {
				$siteID          = $shop_data['site_id'];
					$token       = $shop_data['access_token'];
					$getLocation = $shop_data['location'];
			}
			$picture_array = array();

			if ( ! is_array( $picture_url ) ) {
				$picture_array = array( $picture_url );
			} else {
				$picture_array = $picture_url;
			}

			require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
			$ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );

			if ( is_array( $picture_array ) && ! empty( $picture_array ) ) {
				$xml = '<?xml version="1.0" encoding="utf-8"?>
					<UploadSiteHostedPicturesRequest xmlns="urn:ebay:apis:eBLBaseComponents">
					<WarningLevel>High</WarningLevel>
					<ExternalPictureURL>ced</ExternalPictureURL>
					<PictureName>ced</PictureName>
					</UploadSiteHostedPicturesRequest>';

				foreach ( $picture_array as $key => $url ) {
					$pathinfo     = pathinfo( $url );
					$imageName    = $pathinfo['filename'];
					$str          = '<ExternalPictureURL>' . $url . '</ExternalPictureURL>';
					$image_name   = '<PictureName>' . $imageName . '</PictureName>';
					$xml          = str_replace( '<ExternalPictureURL>ced</ExternalPictureURL>', $str, $xml );
					$xml          = str_replace( '<PictureName>ced</PictureName>', $image_name, $xml );
					$uploadOnEbay = $ebayUploadInstance->ced_ebay_upload_image_to_eps( $xml );
					if ( ! empty( $uploadOnEbay ) ) {
						if ( isset( $uploadOnEbay['Ack'] ) ) {
							if ( 'Warning' == $uploadOnEbay['Ack'] || 'Success' == $uploadOnEbay['Ack'] ) {
								$response_Urls = array();
								if ( isset( $uploadOnEbay['SiteHostedPictureDetails'] ) && is_array( $uploadOnEbay['SiteHostedPictureDetails'] ) ) {
									$response_Urls[] = $uploadOnEbay['SiteHostedPictureDetails']['FullURL'];
								}
							}
						}
					}
				}

				if ( ! empty( $response_Urls ) && is_array( $response_Urls ) ) {
					return $response_Urls;
				} else {
					return false;
				}
			}
		}
	}



	public function getFormattedDataForUpdatingSKU( $value, $userId, $item_id ) {
		$logger       = wc_get_logger();
		$context      = array( 'source' => 'getFormattedDataForUpdatingSKU' );
		$product      = wc_get_product( $value );
		$product_data = $product->get_data();
		$productType  = $product->get_type();
		$finalXml     = '';
		$xmlArray     = array();
		$shop_data    = ced_ebay_get_shop_data( $userId );
		if ( ! empty( $shop_data ) ) {
			$siteID          = $shop_data['site_id'];
				$token       = $shop_data['access_token'];
				$getLocation = $shop_data['location'];
		}
		if ( ! empty( $item_id ) ) {
			$item['ItemID'] = $item_id;
		}

			$variation_xml      = $this->getFormattedDataForVariation( $value, $userId );
			$item['Variations'] = 'ced';
			$xmlArray['Item']   = $item;
			$rootElement        = 'Item';
			$xml                = new SimpleXMLElement( "<$rootElement/>" );
			$this->array2XML( $xml, $xmlArray['Item'] );

		$val       = $xml->asXML();
		$finalXml .= $val;
		$finalXml  = str_replace( '<?xml version="1.0"?>', '', $finalXml );

		if ( ! empty( $item_id ) ) {
			$xmlHeader = '<?xml version="1.0" encoding="utf-8"?>
				<ReviseFixedPriceItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
					<RequesterCredentials>
						<eBayAuthToken>' . $token . '</eBayAuthToken>
					</RequesterCredentials>
					<Version>1267</Version>
					<ErrorLanguage>en_US</ErrorLanguage>
					<WarningLevel>High</WarningLevel>';
			$xmlFooter = '</ReviseFixedPriceItemRequest>';
		}

		$mainXML = $xmlHeader . $finalXml . $xmlFooter;
		$mainXML = str_replace( '<Variations>ced</Variations>', $variation_xml, $mainXML );
		if ( 'variable' == $productType ) {
			return array( $mainXML, true );
		} else {
			return array( $mainXML, false );
		}
	}





	public function ced_ebay_prepareDataForReListing( $userId, $site_id, $proIDs = array() ) {

		$shop_data = ced_ebay_get_shop_data( $userId, $site_id );
		if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
			$siteID          = $site_id;
				$token       = $shop_data['access_token'];
				$getLocation = $shop_data['location'];
		} else {
			return 'Unable to verify eBay user';
		}
		$response = '<?xml version="1.0" encoding="utf-8"?>
			<RelistFixedPriceItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
				<RequesterCredentials>
					<eBayAuthToken>' . $token . '</eBayAuthToken>
				</RequesterCredentials><Item>';
		foreach ( $proIDs as $key => $value ) {
			$listing_id = get_post_meta( $value, '_ced_ebay_relist_item_id_' . $userId, true );
			$response  .= '<ItemID>' . $listing_id . '</ItemID>';
		}
		$response .= '</Item></RelistFixedPriceItemRequest>';
		return $response;
	}

	public function ced_ebay_prepareDataForUpdatingDescription( $userId, $proIDs = array() ) {
		foreach ( $proIDs as $key => $value ) {
			$prod_data    = wc_get_product( $value );
			$type         = $prod_data->get_type();
			$item_id      = get_post_meta( $value, '_ced_ebay_listing_id_' . $userId, true );
			$preparedData = $this->getFormattedDataForProductDescription( $value, $userId, $item_id );
			return $preparedData;
		}
	}

	public function getFormattedDataForProductDescription( $value, $userId, $item_id ) {
		$product      = wc_get_product( $value );
		$product_data = $product->get_data();
		$productType  = $product->get_type();
		$finalXml     = '';
		$xmlArray     = array();
		$title        = $product_data['name'];
		$description  = $product_data['description'] . ' ' . $product_data['short_description'];
		$shop_data    = ced_ebay_get_shop_data( $userId );
		if ( ! empty( $shop_data ) ) {
			$siteID      = $shop_data['site_id'];
			$token       = $shop_data['access_token'];
			$getLocation = $shop_data['location'];
		}
		if ( ! empty( $item_id ) ) {
			$item['ItemID'] = $item_id;
		}
			$item['Description'] = $description;

		if ( 'variable' == $productType ) {
			$xmlArray['Item'] = $item;
			$rootElement      = 'Item';
			$xml              = new SimpleXMLElement( "<$rootElement/>" );
			$this->array2XML( $xml, $xmlArray['Item'] );
		} else {
			$xmlArray['Item'] = $item;
			$rootElement      = 'Item';
			$xml              = new SimpleXMLElement( "<$rootElement/>" );
			$this->array2XML( $xml, $xmlArray['Item'] );
		}
		$val       = $xml->asXML();
		$finalXml .= $val;
		$finalXml  = str_replace( '<?xml version="1.0"?>', '', $finalXml );

		if ( 'variable' == $productType ) {
			if ( ! empty( $item_id ) ) {
				$xmlHeader = '<?xml version="1.0" encoding="utf-8"?>
				<ReviseFixedPriceItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
					<RequesterCredentials>
						<eBayAuthToken>' . $token . '</eBayAuthToken>
					</RequesterCredentials>
					<Version>1267</Version>
					<ErrorLanguage>en_US</ErrorLanguage>
					<WarningLevel>High</WarningLevel>';
				$xmlFooter = '</ReviseFixedPriceItemRequest>';
			}
		} elseif ( ! empty( $item_id ) ) {
				$xmlHeader = '<?xml version="1.0" encoding="utf-8"?>
				<ReviseItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
					<RequesterCredentials>
						<eBayAuthToken>' . $token . '</eBayAuthToken>
					</RequesterCredentials>
					<Version>1267</Version>
					<ErrorLanguage>en_US</ErrorLanguage>
					<WarningLevel>High</WarningLevel>';
				$xmlFooter = '</ReviseItemRequest>';
		}

		$mainXML = $xmlHeader . $finalXml . $xmlFooter;
		if ( 'variable' == $productType ) {
			return array( $mainXML, true );
		} else {
			return array( $mainXML, false );
		}
	}
}
