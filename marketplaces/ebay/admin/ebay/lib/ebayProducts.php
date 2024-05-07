<?php
namespace Ced\Ebay;
use Ced\Ebay\WC\Product_Data as Product_Data;
use Ced\Ebay\WC\Listings_Data as Listings_Data;
include_once(CED_EBAY_DIRPATH.'admin/ebay/lib/class-wc-product-data.php');
include_once(CED_EBAY_DIRPATH.'admin/ebay/lib/class-ebay-listing-data.php');
class Class_Ced_EBay_Products {

	private static $_instance;

	private $ebay_user;

    private $ebay_site;

	private $isProfileAssignedToProduct;

	private $profile_data = [];
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

	private $wc_product_data;

	/*
	 *
	 *function for preparing product data to be uploaded
	 *
	 *
	 */
	public function ced_ebay_prepareDataForUploading( $site_id, $proIDs = array(), $userId = '' ) {
		$ebay_items = [];
		$rsid = ced_ebay_get_shop_data( $userId, $site_id );
		foreach ( $proIDs as $key => $value ) {
			$preparedData     = $this->getFormattedData( $site_id, $value, $userId );
			if(is_wp_error($preparedData)){
				return $preparedData;
			}
			$ebay_items[] = $preparedData;
		}
			$apiClient = new \Ced\Ebay\CED_EBAY_API_Client();
			$apiClient->setJwtToken('abc');
			$apiClient->setRequestTopic('product');
			$apiClient->setRequestRemoteMethod('POST');
			$apiClient->setRequestRemoteBody(
				[
				'shop_id' => $rsid['remote_shop_id'],
				'items' => $ebay_items,
				'type' => 'AddItems'
				]
			);
			
			$apiResponse = $apiClient->post();
			if(isset($apiResponse['data'])){
				$productUploadResponse = json_decode($apiResponse['data'], true);
				return $productUploadResponse;
			} else {
				if(isset($apiResponse['error_code'])){
					return $apiResponse;
				} else {
					return new \WP_Error('api_error', 'An error occurred while uploading product');
				}
			}
	}
	/*
	 *
	 *function for preparing product data to be updated
	 *
	 *
	 */
	public function ced_ebay_prepareDataForUpdating( $userId, $site_id, $proIDs = array() ) {
		$ebay_items = [];
		$rsid = ced_ebay_get_shop_data( $userId, $site_id );
		foreach ( $proIDs as $key => $value ) {
			$prod_data        = wc_get_product( $value );
			$item_id          = get_post_meta( $value, '_ced_ebay_listing_id_' . $userId . '>' . $site_id, true );
			$preparedData     = $this->getFormattedData( $site_id, $value, $userId, $item_id );
			if(is_wp_error($preparedData)){
				return $preparedData;
			}
			$ebay_items[] = $preparedData;
		}

			$apiClient = new \Ced\Ebay\CED_EBAY_API_Client();
			$apiClient->setJwtToken('abc');
			$apiClient->setRequestTopic('product');
			$apiClient->setRequestRemoteBody(
				[
				'items' => $ebay_items
				]
			);
			$apiClient->setRequestRemoteQueryParams([
				'shop_id' => $rsid['remote_shop_id'],
				'type' => 'ReviseItem',
			]);
			$apiResponse = $apiClient->post();
			if(isset($apiResponse['data'])){
				$productUpdateResponse = json_decode($apiResponse['data'], true);
				return $productUpdateResponse;
			} else {
				if(isset($apiResponse['error_code'])){
					return $apiResponse;
				} else {
					return new \WP_Error('api_error', 'An error occurred while updating product');
				}
			}

		
	}
	/*
	 *
	 *function for getting stock of products to be updated
	 *
	 *
	 */
	public function ced_ebay_prepareDataForUpdatingStock( $rsid, $_to_update_productIds = array(), $notAjax = false, $ebay_variation_sku = array() ) {
		$price_sync = 'off';
		$sending_sku = 'off';
		$ebay_user_and_site = ced_ebay_get_details_using_rsid($rsid);
		if(!empty($ebay_user_and_site) && isset($ebay_user_and_site['user_id']) && isset($ebay_user_and_site['site_id']) )
		{
			$ebay_user = $ebay_user_and_site['user_id'];
			$ebay_site = $ebay_user_and_site['site_id'];
		} else {
			return new \WP_Error('missing_ebay_details', 'Invalid or empty eBay user and site');
		}
		if ( empty( $_to_update_productIds ) ) {
			return new \WP_Error('empty_products', 'The supplied products are either empty or invalid');
		}
		$inventory_items = [];
		$apiClient = new \Ced\Ebay\CED_EBAY_API_Client();
		$apiClient->setJwtToken('abc');
		$apiClient->setRequestTopic('product');
		$apiClient->setRequestRemoteMethod('PUT');

		$wc_products_data = new Product_Data;
		$wc_products_data->setEbayUser($ebay_user);
		$wc_products_data->setEbaySite($ebay_site);

		foreach ( $_to_update_productIds as $productId => $itemId ) {
			$sku = get_post_meta( $productId, '_sku', true );
			if ( ! empty( $ebay_variation_sku['sku'] ) ) {
				$product_sku = get_post_meta( $productId, '_sku', true );
				if ( empty( $product_sku ) ) {
					$product_sku = $productId;
				}
				if ( ! in_array( $product_sku, $ebay_variation_sku['sku'] ) ) {
					continue;
				}
			}
			$wc_products_data->setProductId( $productId );
			
			$quantity_to_update = $wc_products_data->getStock();

			$inventory_items[$productId] = [
				'listing_id' => $itemId,
				'quantity' => (int)$quantity_to_update,
				'sku'      => $sku
			];
			if ( get_option( 'ced_ebay_global_settings', false ) ) {
				$dataInGlobalSettings = get_option( 'ced_ebay_global_settings', false );
				$price_sync   = isset( $dataInGlobalSettings[ $ebay_user ][ $ebay_site ]['ced_ebay_sync_price'] ) ? $dataInGlobalSettings[ $ebay_user ][ $ebay_site ]['ced_ebay_sync_price'] : '';
				$sending_sku          = isset( $dataInGlobalSettings[ $ebay_user ][ $ebay_site ]['ced_ebay_sending_sku'] ) ? $dataInGlobalSettings[ $ebay_user ][ $ebay_site ]['ced_ebay_sending_sku'] : '';
			}
			if ( 'on' == $price_sync && ! empty( $price ) ){
				$inventory_items[$productId] = [
					'start_price' => $wc_products_data->getPrice(),
				];		
			}
			if ( 'off' == $sending_sku && ! empty( $sku ) ){
				if(isset($inventory_items[$productId]['sku'])){
					unset($inventory_items[$productId]['sku']);	
				}
			}
		}

		$apiClient->setRequestRemoteBody(
			[
			'items' => $inventory_items
			]
		);

		$apiClient->setRequestRemoteQueryParams([
			'shop_id' => $rsid,
			'type' => 'ReviseInventoryStatus',
		]);
		$apiResponse = $apiClient->post();
		if(isset($apiResponse['data'])){
			$inventoryUpdateResponse = json_decode($apiResponse['data'], true);
			return $inventoryUpdateResponse;
		} else {
			if(isset($apiResponse['error_code'])){
				return $apiResponse;
			} else {
				return new \WP_Error('api_error', 'An error occurred while updating inventory');
			}
		}
		
	}

	/*
	 *
	 *function for preparing  product data
	 *
	 *
	 */
	public function getFormattedData( $siteID, $proIds = '', $userId = '', $ebayItemID = '' ) {
		$profileData = $this->ced_ebay_getProfileAssignedData( $proIds, $userId, $siteID );
		if ( false == $this->isProfileAssignedToProduct ) {
			return new \WP_Error('profile_not_assigned', 'Profile is not assigned to the product');
		}
		$product = wc_get_product( $proIds );
		$ebay_item    = array();
		if ( WC()->version > '3.0.0' ) {
			$product_data            = $product->get_data();
			$productType             = $product->get_type();
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

		$rsid = ced_ebay_get_shop_data( $userId, $siteID );
		if (empty( $rsid )  ) {
			return new \WP_Error('invalid_rsid', 'Unable to get shop data');
		}

		$wc_products_data = new Product_Data;
		$wc_products_data->setEbayUser($userId);
		$wc_products_data->setEbaySite($siteID);
		$wc_products_data->setProductId( $proIds );
		$listings_data = new Listings_Data;
		$listings_data->setSourceProductId($proIds);

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

			$policy_data = [
			'shipping_policy' => [
				'profileId' => $ship_bussiness_id,
				'profileName' => $ship_bussiness_name
			],
			'return_policy' => [
				'profileId' => $return_id,
				'profileName' => $return_name
			],
			'payment_policy' => [
				'profileId' => $payment_id,
				'profileName' => $payment_name
			]
		];
			$listings_data->setPolicyData($policy_data);
		}
		
		$policy_data = [
			'shipping_policy' => [
				'profileId' => 5545377000,
				'profileName' => 'FlatOther  Hour business dayssqweqweqwe'
			],
			'return_policy' => [
				'profileId' => 5545375000,
				'profileName' => 'Returns Accepted'
			],
			'payment_policy' => [
				'profileId' => 5558717000,
				'profileName' => 'PayPalPersonal checkCredit'
			]
	];
		
		$listings_data->setPolicyData($policy_data);
		// else {
		// 	return new \WP_Error('business_policies', 'Business policies are not set');
		// }
		$lisyingType     = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_listing_type' );
		$pictureUrl = wp_get_attachment_image_url( get_post_meta( $proIds, '_thumbnail_id', true ), 'full' ) ? str_replace( ' ', '%20', wp_get_attachment_image_url( get_post_meta( $proIds, '_thumbnail_id', true ), 'full' ) ) : '';
		$pictureUrl = strtok( $pictureUrl, '?' );
		if ( strpos( $pictureUrl, 'https' ) === false ) {
			$pictureUrl = str_replace( 'http', 'https', $pictureUrl );
		}
		$primarycatId = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_category' );
		$listings_data->setCategory($primarycatId);
		

		$listings_data->setTitle($title);
		
		$ean   = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_ean' );
		$isbn  = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_isbn' );
		$upc   = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_upc' );
		if ( empty( $ean ) ) {
			$ean = 'Does Not Apply';
		}
		
		if ( '' != $ean || '' != $isbn || '' != $upc ) {
			
			if ( '' != $ean ) {
				$listings_data->setEan($ean);
			}
			if ( '' != $isbn ) {
				$listings_data->setIsbn($ean);
			}
			if ( '' != $upc ) {
				$listings_data->setUpc($ean);

			} else {
				$listings_data->setUpc('Does not apply');
			}
		}
		if ( ! empty( $ebayItemID ) ) {
			$listings_data->setEbayListingId($ebayItemID);
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
			
			if ( $product->is_type( 'simple' ) ) {
				$product_price = $wc_products_data->getPrice();
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
				preg_match( $regex, $template_html, $matches );

				if ( count( $matches ) === 2 ) {
					$image_width   = $matches[1];
					$imageheight   = $matches[2];
					$image_html    = '<img src="' . $pictureUrl . '" width="' . $image_width . 'px" height="' . $imageheight . 'px" >';
					$template_html = str_replace( '[woo_ebay_product_main_image width=' . $image_width . ' height=' . $imageheight . ']', $image_html, $template_html );

				} else {
					$template_html = str_replace( '[woo_ebay_product_main_image]', $product_image, $template_html );

				}
			}
			$template_html       = str_replace( '[woo_ebay_product_category]', $product_category[0]->name, $template_html );
			$template_html       = str_replace( '[woo_ebay_product_type]', $productType, $template_html );
			$template_html       = str_replace( '[woo_ebay_product_short_description]', $product_short_description, $template_html );
			$custom_css          = '<style type="text/css">' . $custom_css . '</style>';
			$product_description = $custom_css . ' <br> ' . $template_html . ' </br> ';
		}
	
		//Set Item Description
		$listing_description = ! empty( $product_description ) ? $product_description : $description;
		$listings_data->setDescription($listing_description);



		//Set Item Location
		$item_location        = ! empty( $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_item_location_state'] ) ? $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_item_location_state'] : $getLocation;
		$listings_data->setLocation($item_location);

		

		$listings_data->setAutoPay();

		$vat_percent     = isset( $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_vat_percent'] ) ? $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_vat_percent'] : '';
		if ( ! empty( $vat_percent ) && 0 < $vat_percent ) {
			$listings_data->setVatPercentage($vat_percent);
		}

		if ( $product->is_type( 'simple' ) ) {
			//Set Quantity and Price
			
			$listing_quantity = $wc_products_data->getStock();
			$listings_data->setQuantity($listing_quantity);
			$listing_price = $wc_products_data->getPrice();
			$listings_data->setPrice(($listing_price));

			//Set Best Offer
			$BestOfferEnabled              = $this->fetchMetaValueOfProduct( $proIds, '_umb_ebay_bestoffer' );
			if ( 'No' == $BestOfferEnabled ) {
				$listings_data->setBestOfferEnabled(false);
			} elseif ( 'Yes' == $BestOfferEnabled ) {
				$listings_data->setBestOfferEnabled(true);
			}
		}

		$sending_sku          = isset( $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_sending_sku'] ) ? $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_sending_sku'] : 'off';
		if ( 'off' == $sending_sku ) {
			$listings_data->setSku($product->get_sku());
			if ( empty( $product->get_sku() ) ) {
				$listings_data->setSku($proIds);
			}
		}

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
				$listings_data->setWeight($productWeight);
				$listings_data->setWeightUnit($weight_unit);	
			}
			
		}

		$wp_folder     = wp_upload_dir();
		$wp_upload_dir = $wp_folder['basedir'];
		$wp_upload_dir = $wp_upload_dir . '/ced-ebay/category-specifics/' . $userId . '/' . $siteID . '/';

		$cat_specifics_file = $wp_upload_dir . 'ebaycat_' . $primarycatId . '.json';
		if ( file_exists( $cat_specifics_file ) ) {
			$available_attribute = json_decode( file_get_contents( $cat_specifics_file ), true );
		}
		if ( ! is_array( $available_attribute ) ) {
			$available_attribute = array();
		}

		

		$ebayConfig = CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayConfig.php';
		if ( file_exists( $ebayConfig ) ) {
			require_once $ebayConfig;
		}

		$ebayCategoryInstance = \Ced\Ebay\CedGetCategories::get_instance( $siteID, $rsid );
		if ( ! empty( $available_attribute ) && is_array( $available_attribute ) ) {
			$getCatSpecifics = $available_attribute;
			$getCatFeatures  = $ebayCategoryInstance->_getCatFeatures( $primarycatId );
			if(!is_wp_error($getCatFeatures)){
				$getCatFeatures  = isset( $getCatFeatures[0] ) ? $getCatFeatures[0] : false;
			} else {
				return $getCatFeatures;
			}
		} else {
			$getCatSpecifics      = $ebayCategoryInstance->_getCatSpecifics( $primarycatId );
			if(is_wp_error($getCatSpecifics)){
				return $getCatSpecifics;
			}
			$getCatSpecifics_json = json_encode( $getCatSpecifics );
			$cat_specifics_file   = $wp_upload_dir . 'ebaycat_' . $primarycatId . '.json';
			if ( file_exists( $cat_specifics_file ) ) {
				wp_delete_file( $cat_specifics_file );
			}
			file_put_contents( $cat_specifics_file, $getCatSpecifics_json );
			$getCatFeatures = $ebayCategoryInstance->_getCatFeatures( $primarycatId, $limit );
			if(!is_wp_error($getCatFeatures)){
				$getCatFeatures  = isset( $getCatFeatures[0] ) ? $getCatFeatures[0] : false;
			} else {
				return $getCatFeatures;
			}
		}
		$nameValueList = '';
		$catSpecifics  = array();
		if ( ! empty( $getCatSpecifics ) ) {
			$catSpecifics = $getCatSpecifics;
		}

		if ( is_array( $catSpecifics ) && ! empty( $catSpecifics ) ) {
			$nameValueList = [];
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
						
					$nameValueList[] = [
						'name' => $specific['localizedAspectName'],
						'value' => [$catSpcfcs]
					];

					}
				}
			}
			$listings_data->setItemSpecifics($nameValueList);
		}
		
		$conditionID = '';
		if ( $getCatFeatures ) {
			if ( isset( $getCatFeatures['itemConditions'] ) ) {
				if ( ! empty( get_post_meta( $proIds, 'ced_ebay_listing_condition', true ) ) ) {
					$conditionID = get_post_meta( $proIds, 'ced_ebay_listing_condition', true );
				} else {
					$conditionID = $this->fetchMetaValueOfProduct( $proIds, $primarycatId . '_Condition' );
				}
				
				$listings_data->setCategoryCondition($conditionID);
				
			}
		}

		
		if ( 'variable' == $productType ) {
			$ebay_variation_data = $this->getFormattedDataForVariation( $proIds, $siteID, $userId );
			if (!empty($ebay_variation_data) && is_array($ebay_variation_data)){
				if(!isset($ebay_variation_data['variants'])){
					new \WP_Error('no_variants', 'No variants found');
				} else if(!isset($ebay_variation_data['variant_attributes'])){
					new \WP_Error('no_variant_attributes', 'No variantion attributes found');
				}
			$listings_data->setVariants($ebay_variation_data['variants']);
			$listings_data->setVariantAttributes($ebay_variation_data['variant_attributes']);
			}
		}

		if ( '' != $mpn || '' != $ean || '' != $isbn || '' != $upc ) {
			if ( '' != $ean ) {
				$ebay_item['ProductListingDetails']['EAN'] = $ean;
			}
			if ( '' != $isbn ) {
				$ebay_item['ProductListingDetails']['ISBN'] = $isbn;
			}
			if ( '' != $upc ) {
				$ebay_item['ProductListingDetails']['UPC'] = $upc;
			} else {
				$ebay_item['ProductListingDetails']['UPC'] = 'DoesNotApply';
			}
		}


		$listings_data->setConditionDescription($product_custom_condtion);


		
		$configInstance     = \Ced\Ebay\Ebayconfig::get_instance();
		$countyDetails      = $configInstance->getEbaycountrDetail( $siteID );
		$country            = $countyDetails['countrycode'];
		$currency           = $countyDetails['currency'][0];
		$listings_data->setSite($country);
		$item_country       = ! empty( $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_item_location_country'] ) ? $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_item_location_country'] : $country;
		$listings_data->setCountry($item_country);
		$listings_data->setCurrencey($currency);
		$ebay_item['PostalCode'] = ! empty( $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_postal_code'] ) ? $renderDataOnGlobalSettings[ $userId ][ $siteID ]['ced_ebay_postal_code'] : '';

		$listing_pictures_url = [];
		$listing_pictures_url[] = utf8_uri_encode( strtok( $pictureUrl, '?' ) );
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
						$listing_pictures_url[] = utf8_uri_encode( strtok( $img_urls, '?' ) );
					}
				}
			}
		}

		$listings_data->setPictureUrl($listing_pictures_url);

		return $listings_data->getEbayItem();

		
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
		$wc_products_data = new Product_Data;
		$wc_products_data->setEbayUser($userId);
		$wc_products_data->setEbaySite($site_id);
		$_product            = wc_get_product( $proIDs );
		$ebay_variation_data = [];
		$ebay_variant_attributes = [];
		$variation_attribute = $_product->get_variation_attributes();
		$allVariations       = $_product->get_children();

		

		
		foreach ( $variation_attribute as $attr_name => $attr_value ) {
			$attr_name_for_ebay = '';
			$taxonomy          = $attr_name;
			$attr_name         = str_replace( 'pa_', '', $attr_name );
			$attr_name         = str_replace( 'attribute_', '', $attr_name );
			$attr_name         = wc_attribute_label( $attr_name, $_product );
			$attr_name_by_slug = get_taxonomy( $taxonomy );
			if ( is_object( $attr_name_by_slug ) ) {
				$attr_name = $attr_name_by_slug->label;
			}
			if ( 'Quantity' == $attr_name || 'Type' == $attr_name || 'Größe' == $attr_name || 'Size' == $attr_name || 'Colour' == $attr_name || 'Color' == $attr_name ) {
				$attr_name_for_ebay = 'Prouct '.$attr_name;
			} else {
				$attr_name_for_ebay = $attr_name;
			}
			foreach ( $attr_value as $k => $v ) {
				$termObj = get_term_by( 'slug', $v, $taxonomy );
				if ( is_object( $termObj ) ) {
					$term_name = $termObj->name;
					if ( strpos( $term_name, '&' ) !== false ) {
						$term_name = str_replace( '&', '&amp;', $term_name );
					}
					$ebay_variant_attributes[$attr_name_for_ebay][] = $term_name;
				} else {
					if ( strpos( $v, '&' ) !== false ) {
						$v = str_replace( '&', '&amp;', $v );
						$ebay_variant_attributes[$attr_name_for_ebay][] = $v;
					}
				}
			}
		}
		foreach ( $allVariations as $key => $Id ) {
			$wc_products_data->setProductId( $Id );
			$var_attr   = wc_get_product_variation_attributes( $Id );
			$var_prod             = wc_get_product( $Id );
			$price = $wc_products_data->getPrice();
			$quantity = $wc_products_data->getStock();

			$sku = get_post_meta( $Id, '_sku', true );
			if ( empty( $sku ) ) {
				$sku = $Id;
			}

			$ebay_variation_data[$Id] = [
				'sku' => $sku,
				'price' => $price,
				'quantity' => $quantity,
			];
			$var_image_id = $var_prod->get_image_id();
			if ( ! empty( $var_image_id ) ) {
				$var_image_array = wp_get_attachment_image_src( $var_image_id, 'full' );
				$var_image_src   = $var_image_array[0];
			}

			
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
					$ebay_variation_data[$Id]['variantAttributes'][] = [
						'name' => $atr_name,
						'value' => $term_name
					];
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
						$ebay_variation_data[$Id]['variantAttributes'][] = [
							'name' => $atr_name,
							'value' => $term_name
						];
					} else {
						$ebay_variation_data[$Id]['variantAttributes'][] = [
							'name' => $atr_name,
							'value' => $term_name
						];
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
			
		}
		// $var_img_xml = '';
		// if ( ! empty( $variation_img ) ) {
		// 	$var_img_xml .= '<Pictures>';
		// 	$terms        = array();
		// 	foreach ( $variation_img as $attr_name => $attr_values ) {
		// 		if ( 'Quantity' == $attr_name || 'Type' == $attr_name || 'Größe' == $attr_name || 'Size' == $attr_name || 'Colour' == $attr_name || 'Color' == $attr_name ) {
		// 			$var_img_xml .= ' <VariationSpecificName>Product ' . $attr_name . '</VariationSpecificName>';

		// 		} else {
		// 				$var_img_xml .= ' <VariationSpecificName>' . $attr_name . '</VariationSpecificName>';

		// 		}               foreach ( $attr_values as $data_attr ) {
		// 			if ( in_array( $data_attr['term_name'], $terms ) ) {
		// 				continue;
		// 			}
		// 			$terms[]      = $data_attr['term_name'];
		// 			$var_img_xml .= '<VariationSpecificPictureSet>';
		// 			$var_img_xml .= '<VariationSpecificValue>' . $data_attr['term_name'] . '</VariationSpecificValue>';
		// 			if ( ! empty( $data_attr['image_set'] ) && is_array( $data_attr['image_set'] ) ) {
		// 				foreach ( $data_attr['image_set'] as $key => $additional_var_images ) {
		// 					if ( strpos( $additional_var_images, 'https' ) === false ) {
		// 						$additional_var_images = str_replace( 'http', 'https', $additional_var_images );
		// 					}
		// 					$var_img_xml .= '<PictureURL>' . utf8_uri_encode( strtok( $additional_var_images, '?' ) ) . '</PictureURL>';
		// 				}
		// 			} else {
		// 				if ( strpos( $data_attr['image_set'], 'https' ) === false ) {
		// 					$data_attr['image_set'] = str_replace( 'http', 'https', $data_attr['image_set'] );
		// 				}
		// 				$var_img_xml .= '<PictureURL>' . utf8_uri_encode( strtok( $data_attr['image_set'], '?' ) ) . '</PictureURL>';
		// 			}
		// 			$var_img_xml .= '</VariationSpecificPictureSet>';
		// 		}
		// 		break;
		// 	}
		// 	$var_img_xml .= '</Pictures>';
		// }

		return [
			'variants' => $ebay_variation_data,
			'variant_attributes' => $ebay_variant_attributes
		];
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
			$ebay_item['ItemID'] = $item_id;
		}

			$variation_xml      = $this->getFormattedDataForVariation( $value, $userId );
			$ebay_item['Variations'] = 'ced';
			$xmlArray['Item']   = $ebay_item;
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
			$ebay_item['ItemID'] = $item_id;
		}
			$ebay_item['Description'] = $description;

		if ( 'variable' == $productType ) {
			$xmlArray['Item'] = $ebay_item;
			$rootElement      = 'Item';
			$xml              = new SimpleXMLElement( "<$rootElement/>" );
			$this->array2XML( $xml, $xmlArray['Item'] );
		} else {
			$xmlArray['Item'] = $ebay_item;
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
