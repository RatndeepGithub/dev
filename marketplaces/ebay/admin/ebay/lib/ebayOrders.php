<?php
use GuzzleHttp\json_decode;

if ( ! class_exists( 'EbayOrders' ) ) {
	class EbayOrders {



		private static $_instance;

		public $siteID;

		public $token;

		/**
		 * Get_instance Instance.
		 *
		 * Ensures only one instance of CedAuthorization is loaded or can be loaded.
		 *
		userId
		 *
		 * @since 1.0.0
		 * @static
		 * @return get_instance instance.
		 */
		public static function get_instance( $siteID, $token ) {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self( $siteID, $token );
			}
			return self::$_instance;
		}
		/**
		 * Construct
		 */
		public function __construct( $siteID, $token ) {
			$this->loadDepenedency();
			$this->siteID = $siteID;
			$this->token  = $token;
		}

		/**
		 * Ebay orders
		 *
		 * @name getOrders
		 */

		public function sample_order() {
			$sample = file_get_contents( CED_EBAY_DIRPATH . '/admin/fulfillment_api.json' );
			return $sample;
		}
		public function getOrders() {
			$siteID     = $this->siteID;
			$token      = $this->token;
			$verb       = 'GetOrders';
			$xmlbody    = $this->createGetOrderXml( $siteID, $token );
			$cedRequest = new \Ced_Ebay_WooCommerce_Core\Cedrequest( $siteID, $verb );
			$response   = $cedRequest->sendHttpRequest( $xmlbody );
			// $response = json_decode( $this->sample_order(), true );
			if ( $response ) {
				return $response;
			}
			return false;
		}


		public function create_localOrders( $orders, $site_id, $userId = '' ) {
			delete_option( 'ced_ebay_order_fetch_log_' . $userId );
			if ( is_array( $orders ) && ! empty( $orders ) ) {
				$OrderItemInfo = array();
				$wp_folder     = wp_upload_dir();
				$wp_upload_dir = $wp_folder['basedir'];
				$wp_upload_dir = $wp_upload_dir . '/ced-ebay/logs/order-sync/';
				if ( ! is_dir( $wp_upload_dir ) ) {
					wp_mkdir_p( $wp_upload_dir, 0777 );
				}
				foreach ( $orders as $order ) {
					$actualShippingCost  = 0;
					$finalTax            = 0;
					$pendingPaymentOrder = 0;

					if ( 'PAID' == $order['orderPaymentStatus'] && 'FULFILLED' == $order['orderFulfillmentStatus'] ) {
						$pendingPaymentOrder = 2;
					} elseif ( 'FULLY_REFUNDED' == $order['orderPaymentStatus'] ) {
						$pendingPaymentOrder = 3;
					} elseif ( 'PARTIALLY_REFUNDED' == $order['orderPaymentStatus'] ) {
						$pendingPaymentOrder = 4;
					} elseif ( 'FAILED' == $order['orderPaymentStatus'] ) {
						$pendingPaymentOrder = 5;
					} elseif ( 'PENDING' == $order['orderPaymentStatus'] ) {
						$pendingPaymentOrder = 6;
					}

					$OrderNumber = ! empty( $order['orderId'] ) ? $order['orderId'] : false;

					if ( ! empty( $OrderNumber ) ) {
						$getOrderLogPost = get_posts(
							array(
								'post_type' => 'ced_ebay_wp_log',
								'title'     => 'eBay Order ' . $OrderNumber,
								'fields'    => 'ids',
							)
						);

						if ( ! empty( $getOrderLogPost ) && is_array( $getOrderLogPost ) ) {
							foreach ( $getOrderLogPost as $postId ) {
								wp_delete_post( $postId, true );
							}
						}
					}

					$log_file = $wp_upload_dir . $OrderNumber . '.txt';
					if ( $log_file ) {
						if ( file_exists( $log_file ) ) {
							wp_delete_file( $log_file );
						}
						file_put_contents( $log_file, PHP_EOL . 'Start of orders fetch', FILE_APPEND );
					}
					ced_ebay_log_data( $order, 'ced_ebayorders', $log_file );
					ced_ebay_log_data( '-----------------------------------------', 'ced_ebayorders', $log_file );
					ced_ebay_log_data( 'Starting to fetch eBay order ' . $OrderNumber, 'ced_ebayorders', $log_file );
					ced_ebay_log_data( 'Order payment status ' . $order['orderPaymentStatus'], 'ced_ebayorders', $log_file );
					if ( empty( $OrderNumber ) ) {
						ced_ebay_log_data( 'No eBay order ID found. Bailing!', 'ced_ebayorders', $log_file );
						continue;
					}

					if ( ! empty( $order['fulfillmentStartInstructions'] ) && is_array( $order['fulfillmentStartInstructions'] ) ) {
						ced_ebay_log_data( 'Getting fulfillment instructions', 'ced_ebayorders', $log_file );
						$ShipToFirstName     = isset( $order['fulfillmentStartInstructions'][0]['shippingStep']['shipTo']['fullName'] ) ? $order['fulfillmentStartInstructions'][0]['shippingStep']['shipTo']['fullName'] : '';
						$CustomerPhoneNumber = isset( $order['fulfillmentStartInstructions'][0]['shippingStep']['shipTo']['primaryPhone']['phoneNumber'] ) ? $order['fulfillmentStartInstructions'][0]['shippingStep']['shipTo']['primaryPhone']['phoneNumber'] : '';
						$ShipToAddress1      = isset( $order['fulfillmentStartInstructions'][0]['shippingStep']['shipTo']['contactAddress']['addressLine1'] ) ? $order['fulfillmentStartInstructions'][0]['shippingStep']['shipTo']['contactAddress']['addressLine1'] : '';
						$ShipToAddress2      = isset( $order['fulfillmentStartInstructions'][0]['shippingStep']['shipTo']['contactAddress']['addressLine2'] ) ? $order['fulfillmentStartInstructions'][0]['shippingStep']['shipTo']['contactAddress']['addressLine2'] : '';
						$ShipToCityName      = isset( $order['fulfillmentStartInstructions'][0]['shippingStep']['shipTo']['contactAddress']['city'] ) ? $order['fulfillmentStartInstructions'][0]['shippingStep']['shipTo']['contactAddress']['city'] : '';
						$ShipToStateCode     = isset( $order['fulfillmentStartInstructions'][0]['shippingStep']['shipTo']['contactAddress']['stateOrProvince'] ) ? $order['fulfillmentStartInstructions'][0]['shippingStep']['shipTo']['contactAddress']['stateOrProvince'] : '';

						$ShipToZipCode = isset( $order['fulfillmentStartInstructions'][0]['shippingStep']['shipTo']['contactAddress']['postalCode'] ) ? $order['fulfillmentStartInstructions'][0]['shippingStep']['shipTo']['contactAddress']['postalCode'] : '';
						$Country       = isset( $order['fulfillmentStartInstructions'][0]['shippingStep']['shipTo']['contactAddress']['countryCode'] ) ? $order['fulfillmentStartInstructions'][0]['shippingStep']['shipTo']['contactAddress']['countryCode'] : '';

						$site_url     = str_replace( array( 'http://', 'https://', 'www.' ), array( '', '', '' ), get_bloginfo( 'url' ) );
						$siteUrlParts = parse_url( $site_url );
						if ( isset( $siteUrlParts['port'] ) ) {
							unset( $siteUrlParts['port'] );
							$site_url = $siteUrlParts['host'];
						}
						if ( ! empty( $site_url ) && ! empty( $order['orderId'] ) ) {
							$EmailAddress = $order['orderId'] . '@' . $site_url;
						} else {
							$EmailAddress = isset( $order['fulfillmentStartInstructions'][0]['shippingStep']['shipTo']['email'] ) ? $order['fulfillmentStartInstructions'][0]['shippingStep']['shipTo']['email'] : '';
						}
						ced_ebay_log_data( 'Generated buyer email ' . $EmailAddress, 'ced_ebayorders', $log_file );

						$ShippingService = isset( $order['fulfillmentStartInstructions'][0]['shippingStep']['shippingServiceCode'] ) ? $order['fulfillmentStartInstructions'][0]['shippingStep']['shippingServiceCode'] : '';
						$ReferenceID     = isset( $order['fulfillmentStartInstructions'][0]['shippingStep']['shipToReferenceId'] ) ? $order['fulfillmentStartInstructions'][0]['shippingStep']['shipToReferenceId'] : '';
					}

					$buyerUser   = isset( $order['buyer']['username'] ) ? $order['buyer']['username'] : false;
					$customer_id = '';
					ced_ebay_log_data( 'Buyer eBay username is ' . $buyerUser, 'ced_ebayorders', $log_file );

					if ( get_option( 'ced_ebay_global_settings' ) ) {
						$renderDataOnGlobalSettings = get_option( 'ced_ebay_global_settings', false );
						$customer_creation          = isset( $renderDataOnGlobalSettings[ $userId ][ $this->siteID ]['ced_ebay_create_customer'] ) ? $renderDataOnGlobalSettings[ $userId ][ $this->siteID ]['ced_ebay_create_customer'] : '';
						if ( 'on' == $customer_creation ) {

							if ( ! empty( $ShipToFirstName ) ) {
								$parts = explode( ' ', $ShipToFirstName );
								if ( count( $parts ) > 1 ) {
									$lastname  = array_pop( $parts );
									$firstname = implode( ' ', $parts );
								} else {
									$firstname = $ShipToFirstName;
									$lastname  = ' ';
								}
							}
							if ( ! empty( $buyerUser ) ) {
								ced_ebay_log_data( 'Starting customer creation process', 'ced_ebayorders', $log_file );
								if ( null == username_exists( $buyerUser ) || false == username_exists( $buyerUser ) ) {

									$customer_id = wp_insert_user(
										wp_slash(
											array(
												'user_login' => $buyerUser,
												'user_pass' => 'password',
												'user_email' => $EmailAddress,
												'first_name' => $firstname,
												'last_name' => $lastname,
												'display_name' => $ShipToFirstName,
												'role' => 'customer',
											)
										)
									);

								} else {
									$customer_id = username_exists( $buyerUser );
								}
							} else {
								ced_ebay_log_data( 'Skipping customer creation since no eBay username is found in the API response.', 'ced_ebayorders', $log_file );
							}
						}
					}

					if ( ! $order['lineItems'][0] ) {
						$tempLineItemArray = $order['lineItems'];
						unset( $order['lineItems'] );
						$order['lineItems'][0] = $tempLineItemArray;
					}

					if ( ! empty( $order['pricingSummary']['deliveryCost']['value'] ) ) {
						$actualShippingCost = $order['pricingSummary']['deliveryCost']['value'];
						if ( isset( $order['pricingSummary']['deliveryDiscount']['value'] ) ) {
							$discount = $order['pricingSummary']['deliveryDiscount']['value'];
							ced_ebay_log_data( 'Pricing summary delivery discount cost ' . $discount, 'ced_ebayorders', $log_file );
							if ( false !== strpos( $discount, '-' ) ) {
								$actualShippingCost = $actualShippingCost + ( $discount );
							} else {
								$actualShippingCost = $actualShippingCost - ( $discount );
							}
						}
						ced_ebay_log_data( 'Pricing summary delivery cost ' . $actualShippingCost, 'ced_ebayorders', $log_file );

					} elseif ( ! empty( $order['lineItems'][0]['deliveryCost']['shippingCost']['value'] ) && ! empty( $order['lineItems'][0]['deliveryCost']['handlingCost']['value'] ) ) {
							$delivery_cost      = (float) $order['lineItems'][0]['deliveryCost']['shippingCost']['value'];
							$handling_cost      = (float) $order['lineItems'][0]['deliveryCost']['handlingCost']['value'];
							$actualShippingCost = $delivery_cost + $handling_cost;
							ced_ebay_log_data( 'Line item delivery cost ' . $delivery_cost, 'ced_ebayorders', $log_file );
							ced_ebay_log_data( 'Line item handling cost ' . $handling_cost, 'ced_ebayorders', $log_file );
					} else {
						$actualShippingCost = ! empty( $order['lineItems'][0]['deliveryCost']['shippingCost']['value'] ) ? $order['lineItems'][0]['deliveryCost']['shippingCost']['value'] : 0;
						ced_ebay_log_data( 'Line item delivery cost ' . $actualShippingCost, 'ced_ebayorders', $log_file );
					}

					$ShippingAddress = array(
						'first_name' => $ShipToFirstName,
						'phone'      => $CustomerPhoneNumber,
						'address_1'  => $ShipToAddress1,
						'address_2'  => $ShipToAddress2,
						'city'       => $ShipToCityName,
						'state'      => $ShipToStateCode,
						'postcode'   => $ShipToZipCode,
						'country'    => $Country,
						'email'      => $EmailAddress,
					);
					$BillToFirstName = $ShipToFirstName;
					$BillPhoneNumber = $CustomerPhoneNumber;

					$BillingAddress = array(
						'first_name' => $BillToFirstName,
						'email'      => $EmailAddress,
						'phone'      => $BillPhoneNumber,
						'address_1'  => $ShipToAddress1,
						'address_2'  => $ShipToAddress2,
						'city'       => $ShipToCityName,
						'state'      => $ShipToStateCode,
						'postcode'   => $ShipToZipCode,
						'country'    => $Country,
					);
					$address        = array(
						'shipping' => $ShippingAddress,
						'billing'  => $BillingAddress,
					);

					ced_ebay_log_data( $address, 'ced_ebayorders', $log_file );

					$transactions = ! empty( $order['lineItems'] ) ? $order['lineItems'] : false;
					if ( empty( $transactions ) ) {
						ced_ebay_log_data( 'Unable to fetch transactions from the API response. Bailing!', 'ced_ebayorders', $log_file );
						continue;
					}
					if ( 'NONE_REQUESTED' == $order['cancelStatus']['cancelState'] ) {
						$cancel = 0;
					}

					if ( is_array( $transactions ) && ! empty( $transactions ) ) {
						$ItemArray = array();
						ced_ebay_log_data( 'Process eBay order transactions', 'ced_ebayorders', $log_file );
						foreach ( $transactions as $transaction ) {
							$ID                   = false;
							$sku                  = '';
							$var_sku              = '';
							$OrderedQty           = $transaction['quantity'];
							$listingMarketplaceId = isset( $transaction['listingMarketplaceId'] ) ? $transaction['listingMarketplaceId'] : '';
							$listingEbaySite      = '';
							if ( ! empty( $listingMarketplaceId ) ) {
								$eBaySiteDetails = ! empty( ced_ebay_get_site_using_marketplace_enum( $listingMarketplaceId ) ) ? ced_ebay_get_site_using_marketplace_enum( $listingMarketplaceId ) : array();
								if ( ! empty( $eBaySiteDetails ) && isset( $eBaySiteDetails['siteID'] ) ) {
									$listingEbaySite = $eBaySiteDetails['siteID'];
								} else {
									$listingEbaySite = '';
								}
							}
							ced_ebay_log_data( 'Listing eBay Site - ' . $listingEbaySite, 'ced_ebayorders', $log_file );
							$purchaseMarketplaceId = isset( $transaction['purchaseMarketplaceId'] ) ? $transaction['purchaseMarketplaceId'] : '';
							$CancelQty             = $cancel;
							if ( isset( $transaction['discountedLineItemCost']['value'] ) && ! empty( $transaction['discountedLineItemCost']['value'] ) ) {
								$basePrice = $transaction['discountedLineItemCost']['value'];
								$basePrice = $basePrice / $OrderedQty;
							} else {
								$basePrice = $transaction['lineItemCost']['value'];
								$basePrice = $basePrice / $OrderedQty;
							}
							if ( ! empty( $transaction['legacyVariationId'] ) ) {
								ced_ebay_log_data( 'Ordered product is a variation on eBay', 'ced_ebayorders', $log_file );
								// SKU in the data is the SKU of the variation
								$var_sku = ! empty( $transaction['sku'] ) ? $transaction['sku'] : false;
								$sku     = false;
							} else {
								ced_ebay_log_data( 'Ordered product is a simple product on eBay', 'ced_ebayorders', $log_file );
								$sku = ! empty( $transaction['sku'] ) ? $transaction['sku'] : false;
							}
							$ItemID = ! empty( $transaction['legacyItemId'] ) ? $transaction['legacyItemId'] : false;

							if ( $sku || $var_sku ) {
								ced_ebay_log_data( 'Getting WooCommerce product by SKU', 'ced_ebayorders', $log_file );
								if ( ! empty( $sku ) ) {
									$ID = wc_get_product_id_by_sku( $sku );
									if ( empty( $ID ) ) {
										$args        = array(
											'post_type'    => 'product',
											'post_status'  => 'publish',
											'numberposts'  => -1,
											'meta_key'     => '_sku',
											'meta_value'   => $sku,
											'meta_compare' => '=',
										);
										$simple_post = get_posts( $args );
										$simple_post = wp_list_pluck( $simple_post, 'ID' );
										if ( ! empty( $simple_post ) ) {
											$ID = $simple_post[0];
										}
									}
									ced_ebay_log_data( 'Found simple product ' . $ID, 'ced_ebayorders', $log_file );
								} elseif ( ! empty( $var_sku ) ) {
									$ID = wc_get_product_id_by_sku( $var_sku );
									if ( empty( $ID ) ) {
										$args           = array(
											'post_type'  => 'product_variation',
											'meta_query' => array(
												array(
													'key' => '_sku',
													'value' => $var_sku,
												),
											),
										);
										$variation_post = get_posts( $args );
										if ( ! empty( $variation_post ) && is_array( $variation_post ) ) {
											$ID = $variation_post[0]->ID;
										} else {
											$var_product = wc_get_product( $var_sku );
											if ( ! is_wp_error( $var_product ) && $var_product ) {
												if ( $var_product->is_type( 'variation' ) ) {
													$ID = $var_sku;
												}
											}
										}
									}
									ced_ebay_log_data( 'Found Woo variation ' . $ID, 'ced_ebayorders', $log_file );
								}
							}

							if ( empty( $ID ) && ! empty( $ItemID ) ) {
								$store_products = get_posts(
									array(
										'numberposts'  => -1,
										'post_type'    => 'product',
										'meta_key'     => '_ced_ebay_listing_id_' . $userId . '>' . $listingEbaySite,
										'meta_value'   => $ItemID,
										'meta_compare' => '=',
									)
								);
								$localItemID    = wp_list_pluck( $store_products, 'ID' );
								if ( ! empty( $localItemID ) && is_array( $localItemID ) ) {
									$ID = $localItemID[0];
								}
								ced_ebay_log_data( 'Found Woo product using eBay item ID ' . $ID, 'ced_ebayorders', $log_file );
							}

							$item = array(
								'OrderedQty'            => $OrderedQty,
								'CancelQty'             => $CancelQty,
								'UnitPrice'             => $basePrice,
								'Sku'                   => $sku,
								'ID'                    => $ID,
								'ebayItemId'            => $ItemID,
								'listingMarketplaceId'  => $listingMarketplaceId,
								'purchaseMarketplaceId' => $purchaseMarketplaceId,
							);

							$ItemArray[] = $item;
							if ( empty( $transaction['taxes'] ) ) {
								$finalTax = 0;
							} else {
								$finalTax = $finalTax + isset( $transaction['taxes']['totalTaxAmount'] ) ? $transaction['taxes']['totalTaxAmount'] : 0;

							}
						}
						ced_ebay_log_data( '----------------------', 'ced_ebayorders' );
					}

					$currentDateTime      = gmdate( 'Y-m-d H:i:s' );
					$eBayOrderCreatedDate = $order['creationDate'];
					if ( '' != $eBayOrderCreatedDate ) {
						$orderCreatedDate = new WC_DateTime( $eBayOrderCreatedDate );
						$orderCreatedDate = $orderCreatedDate->date( 'Y-m-d H:i:s' );
					} else {
						$orderCreatedDate = $currentDateTime;
					}

					$OrderItemsInfo = array(
						'OrderNumber'      => $OrderNumber,
						'ItemsArray'       => $ItemArray,
						'tax'              => $finalTax,
						'ShippingAmount'   => $actualShippingCost,
						'ShippingService'  => $ShippingService,
						'orderCreatedDate' => $orderCreatedDate,
					);

					$orderItems      = $transactions;
					$buyerUserId     = isset( $order['buyer']['username'] ) ? $order['buyer']['username'] : 'N/A';
					$merchantOrderId = $OrderNumber;
					$purchaseOrderId = $OrderNumber;
					$orderDetail     = isset( $order ) ? $order : array();
					$ebayOrderMeta   = array(
						'merchant_order_id'       => $merchantOrderId,
						'purchaseOrderId'         => $purchaseOrderId,
						'order_detail'            => $orderDetail,
						'order_items'             => $orderItems,
						'eBayPendingPaymentOrder' => $pendingPaymentOrder,
						'ebayBuyerUserId'         => $buyerUserId,
					);

					$order_id = $this->create_order( $userId, $address, $OrderItemsInfo, 'Ebay', $ebayOrderMeta, $ReferenceID, $customer_id );

					ced_ebay_log_data( '----------------------', 'ced_ebayorders' );
				}
			}
		}





		public function create_order( $userId, $address = array(), $OrderItemsInfo = array(), $frameworkName = 'UMB', $orderMeta = array(), $order_note = '', $customer_id = '' ) {
			global $cedumbhelper;

			$order_id      = '';
			$order_created = false;
			if ( count( $OrderItemsInfo ) ) {
				$OrderNumber   = isset( $OrderItemsInfo['OrderNumber'] ) ? $OrderItemsInfo['OrderNumber'] : 0;
				$wp_folder     = wp_upload_dir();
				$wp_upload_dir = $wp_folder['basedir'];
				$wp_upload_dir = $wp_upload_dir . '/ced-ebay/logs/order-sync/';
				$log_file      = $wp_upload_dir . $OrderNumber . '.txt';

				$order_tax = isset( $OrderItemsInfo['tax'] ) ? $OrderItemsInfo['tax'] : 0;
				$order_id  = $this->is_umb_order_exists( $OrderNumber );
				if ( $order_id ) {
					return $order_id;
				}
				$productIdsToUpdate = array();
				if ( count( $OrderItemsInfo ) ) {
					$ItemsArray = isset( $OrderItemsInfo['ItemsArray'] ) ? $OrderItemsInfo['ItemsArray'] : array();
					if ( is_array( $ItemsArray ) ) {
						foreach ( $ItemsArray as $ItemInfo ) {
							$ProID         = isset( $ItemInfo['ID'] ) ? intval( $ItemInfo['ID'] ) : 0;
							$MfrPartNumber = isset( $ItemInfo['MfrPartNumber'] ) ? $ItemInfo['MfrPartNumber'] : '';
							$Upc           = isset( $ItemInfo['UPCCode'] ) ? $ItemInfo['UPCCode'] : '';
							$Asin          = isset( $ItemInfo['ASIN'] ) ? $ItemInfo['ASIN'] : '';
							$Sku           = isset( $ItemInfo['Sku'] ) ? $ItemInfo['Sku'] : '';

							$params = array( '_sku' => $Sku );
							if ( ! $ProID && ! empty( $Sku ) ) {
								global $wpdb;
								$sku_ProID = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value=%s LIMIT 1", $Sku ) );
								if ( $sku_ProID ) {
									$ProID = $sku_ProID;
								}
							}
							if ( ! $ProID ) {
								$meta  = array( 'ced_uumb_ebay_item_id' => $ItemInfo['ebayItemId'] );
								$ProID = $this->umb_get_product_by( $meta );
							}
							if ( empty( $ProID ) ) {
								ced_ebay_log_data( 'Product ID is empty | create_order', 'ced_ebayorders', $log_file );
								$objDateTime = new DateTime( 'NOW' );
								$timestamp   = $objDateTime->format( 'Y-m-d\TH:i:s\Z' );
								if ( empty( get_option( 'ced_ebay_order_fetch_log_' . $userId ) ) ) {
									$order_fetch_errors_log['timestamp'] = $timestamp;
									$order_fetch_errors_log[]            = 'We can\'t find the eBay Listing on your WooCommerce Store for eBay Order#' . $OrderNumber;
									update_option( 'ced_ebay_order_fetch_log_' . $userId, $order_fetch_errors_log );
								} else {
									$tempArr              = get_option( 'ced_ebay_order_fetch_log_' . $userId, true );
									$tempArr['timestamp'] = $timestamp;
									$tempArr[]            = 'We can\'t find the eBay Listing on your WooCommerce Store for eBay Order#' . $OrderNumber;
									update_option( 'ced_ebay_order_fetch_log_' . $userId, $tempArr );
								}
								continue;
							}
							update_post_meta( $ProID, 'ced_uumb_ebay_item_id', $ItemInfo['ebayItemId'] );
							$productIdsToUpdate[] = $ProID;

							$Qty                        = isset( $ItemInfo['OrderedQty'] ) ? intval( $ItemInfo['OrderedQty'] ) : 1;
							$UnitPrice                  = isset( $ItemInfo['UnitPrice'] ) ? floatval( $ItemInfo['UnitPrice'] ) : 0;
							$ExtendUnitPrice            = isset( $ItemInfo['ExtendUnitPrice'] ) ? floatval( $ItemInfo['ExtendUnitPrice'] ) : 0;
							$ExtendShippingCharge       = isset( $ItemInfo['ExtendShippingCharge'] ) ? floatval( $ItemInfo['ExtendShippingCharge'] ) : 0;
							$ShippingAmount             = isset( $OrderItemsInfo['ShippingAmount'] ) ? $OrderItemsInfo['ShippingAmount'] : 0;
							$renderDataOnGlobalSettings = get_option( 'ced_ebay_global_settings', false );

							$taxes = WC_Tax::get_rates_for_tax_class( '' );

							$all_tax_rates = array();
							$tax_classes   = WC_Tax::get_tax_classes(); // Retrieve all tax classes.
							if ( ! in_array( '', $tax_classes ) ) { // Make sure "Standard rate" (empty class name) is present.
								array_unshift( $tax_classes, '' );
							}
							foreach ( $tax_classes as $tax_class ) { // For each tax class, get all rates.
								$taxes         = WC_Tax::get_rates_for_tax_class( $tax_class );
								$all_tax_rates = array_merge( $all_tax_rates, $taxes );
							}

							$standard_tax_rates = array();
							foreach ( $all_tax_rates as $tax_rates ) {
								if ( '' == $tax_rates->tax_rate_class ) {
									$standard_tax_rates[] = $tax_rates;
								}
							}

							foreach ( $standard_tax_rates as $key => $std_tax_rate ) {
								if ( ( $std_tax_rate->tax_rate_country == $address['billing']['country'] ) || ( $std_tax_rate->tax_rate_country == $address['shipping']['country'] ) ) {
									$exclude_product_vat = isset( $renderDataOnGlobalSettings[ $userId ][ $this->siteID ]['ced_ebay_exclude_product_vat'] ) ? $renderDataOnGlobalSettings[ $userId ][ $this->siteID ]['ced_ebay_exclude_product_vat'] : '';
									if ( 'on' == $exclude_product_vat ) {
										$UnitPrice = $UnitPrice / ( ( $std_tax_rate->tax_rate ) / 100 + 1 );
									}
									if ( ! empty( $std_tax_rate->tax_rate_shipping ) ) {
										$ShippingAmount = $ShippingAmount / ( ( $std_tax_rate->tax_rate ) / 100 + 1 );
									}
								}
								if ( ! empty( $std_tax_rate->tax_rate ) && empty( $std_tax_rate->tax_rate_country ) ) {
									$exclude_product_vat = isset( $renderDataOnGlobalSettings[ $userId ][ $this->siteID ]['ced_ebay_exclude_product_vat'] ) ? $renderDataOnGlobalSettings[ $userId ][ $this->siteID ]['ced_ebay_exclude_product_vat'] : '';
									if ( 'on' == $exclude_product_vat ) {
										$UnitPrice = $UnitPrice / ( ( $std_tax_rate->tax_rate ) / 100 + 1 );
									}
									if ( ! empty( $std_tax_rate->tax_rate_shipping ) ) {
										$ShippingAmount = $ShippingAmount / ( ( $std_tax_rate->tax_rate ) / 100 + 1 );
									}
								}
							}
							$_product = wc_get_product( $ProID );

							if ( is_wp_error( $_product ) ) {
								continue;
							} elseif ( is_null( $_product ) ) {
								continue;
							} elseif ( ! $_product ) {
								continue;
							} else {
								if ( ! $order_created ) {

									$orderNote = isset( $orderMeta['order_detail']['BuyerCheckoutMessage'] ) ? $orderMeta['order_detail']['BuyerCheckoutMessage'] : 'Order from eBay[' . $userId . ']';

									$order_data = array(
										'status'        => 'pending',
										'customer_note' => $orderNote,
										'created_via'   => $frameworkName,
									);

									/* ORDER CREATED IN WOOCOMMERCE */
									$order = wc_create_order( $order_data );

									if ( is_plugin_active( 'woocommerce-sequential-order-numbers-pro/woocommerce-sequential-order-numbers-pro.php' ) ) {
										if ( function_exists( 'wc_seq_order_number_pro' ) && method_exists( 'wc_seq_order_number_pro', 'set_sequential_order_number' ) ) {
											wc_seq_order_number_pro()->set_sequential_order_number( $order->get_id(), get_post( $order->get_id() ) );
										}
									}

									/* ORDER NOTE FOR STREET 1 */
									if ( ! empty( $order_note ) ) {
										$order->add_order_note( 'Address Reference: ' . $order_note );
									}

									if ( '' != $customer_id ) {
										$order->set_customer_id( $customer_id );
									}
									$order->add_order_note( 'Order Number: ' . $OrderNumber );

									/* ORDER CREATED IN WOOCOMMERCE */

									if ( is_wp_error( $order ) ) {
										continue;
									} elseif ( false === $order ) {
										continue;
									} else {
										if ( WC()->version < '3.0.0' ) {
											$order_id = $order->id;
										} else {
											$order_id = $order->get_id();
										}
										$order_created = true;
									}
								}
								update_post_meta( $order_id, 'ced_ebay_order_user_id', $userId );
								$order->add_product(
									$_product,
									$Qty,
									array(
										'subtotal' => $Qty * $UnitPrice,
										'total'    => $Qty * $UnitPrice,
									)
								);
								$order->save();
								$BillingAddress = isset( $address['billing'] ) ? $address['billing'] : '';
								if ( is_array( $BillingAddress ) && ! empty( $BillingAddress ) ) {
									$order->set_address( $BillingAddress, 'billing' );
								}
								$ShippingAddress = isset( $address['shipping'] ) ? $address['shipping'] : '';
								if ( is_array( $ShippingAddress ) && ! empty( $ShippingAddress ) ) {
									$order->set_address( $ShippingAddress, 'shipping' );

								}
								$order->calculate_totals();
							}
						}
					}

					if ( ! $order_created ) {
						return false;
					}
					if ( isset( $order ) && ! empty( $order ) ) {
						$order->save();

						$order->payment_complete();
					}
					$listingMarketplaceId  = isset( $ItemsArray[0]['listingMarketplaceId'] ) ? $ItemsArray[0]['listingMarketplaceId'] : '';
					$purchaseMarketplaceId = isset( $ItemsArray[0]['purchaseMarketplaceId'] ) ? $ItemsArray[0]['purchaseMarketplaceId'] : '';

					$order->set_payment_method( 'paypal' );

					$orderCreatedDate = new WC_DateTime( $OrderItemsInfo['orderCreatedDate'] );
					$order->set_date_created( $orderCreatedDate );
					$order->save();

					$OrderItemAmount = isset( $OrderItemsInfo['OrderItemAmount'] ) ? $OrderItemsInfo['OrderItemAmount'] : 0;
					$DiscountAmount  = isset( $OrderItemsInfo['DiscountAmount'] ) ? $OrderItemsInfo['DiscountAmount'] : 0;
					$RefundAmount    = isset( $OrderItemsInfo['RefundAmount'] ) ? $OrderItemsInfo['RefundAmount'] : 0;
					$ShipService     = isset( $OrderItemsInfo['ShippingService'] ) ? $OrderItemsInfo['ShippingService'] : 'eBay Shipping';

					$order->calculate_totals();

						$shipping_item = new WC_Order_Item_Shipping();
						$shipping_item->set_method_title( $ShipService );
						$shipping_item->set_method_id( 'ebay-shipping' );
						$shipping_item->set_total( $ShippingAmount );
						$order->add_item( $shipping_item );

					$order->calculate_totals();

					if ( isset( $orderMeta['order_detail']['lineItems'][0] ) && ! empty( $orderMeta['order_detail']['lineItems'][0] ) ) {
						foreach ( $orderMeta['order_detail']['lineItems'] as $key => $value ) {
							$array[ $value['legacyItemId'] ] = $value['lineItemId'];

						}
						update_post_meta( $order_id, 'ced_ebay_order_lineItem_id_' . $userId, $array );
					}

					if ( isset( $orderMeta['order_detail']['BuyerCheckoutMessage'] ) && '' != $orderMeta['order_detail']['BuyerCheckoutMessage'] ) {
						$order->add_order_note( $orderMeta['order_detail']['BuyerCheckoutMessage'], 1, true );
					}

					$order->save();

					update_post_meta( $order_id, '_ced_ebay_order_id', $OrderNumber );
					update_post_meta( $order_id, '_is_ced_ebay_order', 1 );
					update_post_meta( $order_id, '_ebay_umb_order_status', 'Fetched' );
					update_post_meta( $order_id, '_umb_ebay_marketplace', $frameworkName );
					update_post_meta( $order_id, 'ced_ebay_purchaseMarketplaceId', $purchaseMarketplaceId );
					update_post_meta( $order_id, 'ced_ebay_listingMarketplaceId', $listingMarketplaceId );

					if ( count( $orderMeta ) ) {
						foreach ( $orderMeta as $oKey => $oValue ) {
							update_post_meta( $order_id, $oKey, $oValue );
						}
					}
				}
				if ( isset( $orderMeta['eBayPendingPaymentOrder'] ) && 1 == $orderMeta['eBayPendingPaymentOrder'] ) {
					$order->update_status( 'pending' );
				} elseif ( isset( $orderMeta['eBayPendingPaymentOrder'] ) && 6 == $orderMeta['eBayPendingPaymentOrder'] ) {
					$order->update_status( 'pending' );
				} elseif ( isset( $orderMeta['eBayPendingPaymentOrder'] ) && 2 == $orderMeta['eBayPendingPaymentOrder'] ) {
					$order->update_status( 'completed' );
				} elseif ( isset( $orderMeta['eBayPendingPaymentOrder'] ) && 3 == $orderMeta['eBayPendingPaymentOrder'] ) {
					$order->update_status( 'cancelled' );
				} elseif ( isset( $orderMeta['eBayPendingPaymentOrder'] ) && 4 == $orderMeta['eBayPendingPaymentOrder'] ) {
					$order->update_status( 'processing' );
				} elseif ( isset( $orderMeta['eBayPendingPaymentOrder'] ) && 5 == $orderMeta['eBayPendingPaymentOrder'] ) {
					$order->update_status( 'failed' );
				} else {
					$order->update_status( 'processing' );
				}
				$order    = new WC_Order( $order_id );
				$order_id = $order->save();
				// do_action( 'woocommerce_store_api_checkout_order_processed', $order );
				return $order_id;
			}
			return false;
		}


		public function getOrderDetails( $ebayOrderId ) {
			$siteID  = $this->siteID;
			$token   = $this->token;
			$verb    = 'GetOrders';
			$xmlbody = $this->createGetOrderDetailXml( $siteID, $token, $ebayOrderId );

			$cedRequest = new \Ced_Ebay_WooCommerce_Core\Cedrequest( $siteID, $verb );
			$response   = $cedRequest->sendHttpRequest( $xmlbody );

			if ( $response ) {
				return $response;
			}
			return false;
		}

		public function umb_get_product_by( $params ) {
			global $wpdb;
			$where = '';
			if ( count( $params ) ) {
				$Flag = false;
				foreach ( $params as $meta_key => $meta_value ) {
					if ( ! empty( $meta_value ) && ! empty( $meta_key ) ) {
						if ( ! $Flag ) {
							$where .= 'meta_key=' . $meta_key . ' AND meta_value=' . $meta_value;
							$Flag   = true;
						} else {
							$where .= ' OR meta_key=' . $meta_key . ' AND meta_value=' . $meta_value;
						}
					}
				}
				if ( $Flag ) {
					$product_id = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE %s LIMIT 1", $where ) );
					if ( $product_id ) {
						return $product_id;
					}
				}
			}
			return false;
		}
		/**
		 * Check if order already imported or not.
		 *
		 * @since 1.0.0
		 */
		public function is_umb_order_exists( $order_number = 0 ) {
			global $wpdb;
			if ( $order_number ) {
				$wp_folder     = wp_upload_dir();
				$wp_upload_dir = $wp_folder['basedir'];
				$wp_upload_dir = $wp_upload_dir . '/ced-ebay/logs/order-sync/';
				$log_file      = $wp_upload_dir . $order_number . '.txt';

				$args         = array(
					'post_type'   => 'shop_order',
					'post_status' => array( 'wc-processing', 'wc-completed' ),
					'numberposts' => 1,
					'meta_query'  => array(
						'relation' => 'OR',
						array(
							'key'     => '_ced_ebay_order_id',
							'value'   => $order_number,
							'compare' => '=',
						),
						array(
							'key'     => '_ebay_order_id',
							'value'   => $order_number,
							'compare' => '=',
						),
					),
				);
				$order        = get_posts( $args );
				$order_id_arr = wp_list_pluck( $order, 'ID' );
				if ( ! empty( $order_id_arr ) ) {
					$order_id = $order_id_arr[0];
				} else {
					return false;
				}

				if ( $order_id && 'trash' !== get_post_status( $order_id ) ) {
					ced_ebay_log_data( 'eBay Order ' . $order_number . ' with order ID ' . $order_id . ' already exists in Woo', 'ced_ebayorders', $log_file );
					return $order_id;
				}
			}
			return false;
		}
		public function createGetOrderDetailXml( $siteID, $token, $ebayOrderId ) {
			$xmlBody = '<?xml version="1.0" encoding="utf-8"?>
						<GetOrdersRequest xmlns="urn:ebay:apis:eBLBaseComponents">
						  <RequesterCredentials>
						    <eBayAuthToken>' . $token . '</eBayAuthToken>
						  </RequesterCredentials>
						   <DetailLevel>ReturnAll</DetailLevel>
						   <OrderIDArray>
						    <OrderID>' . $ebayOrderId . '</OrderID>
						  </OrderIDArray>
						  <OrderRole>Seller</OrderRole>
						  <OrderStatus>All</OrderStatus>
						  <Version>859</Version>
						</GetOrdersRequest>';
			return $xmlBody;
		}


		public function createGetOrderXml( $siteID, $token ) {
			$currentime = time();
			$toDate     = $currentime - ( 1 * 60 );
			$fromDate   = $currentime - ( 3 * 24 * 60 * 60 );
			$offset     = '.000Z';
			$toDate     = gmdate( 'Y-m-d', $toDate ) . 'T' . gmdate( 'H:i:s', $toDate ) . $offset;
			$fromDate   = gmdate( 'Y-m-d', $fromDate ) . 'T' . gmdate( 'H:i:s', $fromDate ) . $offset;
			$xmlBody    = '<?xml version="1.0" encoding="utf-8"?>
						<GetOrdersRequest xmlns="urn:ebay:apis:eBLBaseComponents">
						  <RequesterCredentials>
						    <eBayAuthToken>' . $token . '</eBayAuthToken>
						  </RequesterCredentials>
						   <DetailLevel>ReturnAll</DetailLevel>
						   <CreateTimeFrom>' . $fromDate . '</CreateTimeFrom>
  						   <CreateTimeTo>' . $toDate . '</CreateTimeTo>
						  <OrderRole>Seller</OrderRole>
						  <OrderStatus>Completed</OrderStatus>
						  <Version>1193</Version>
						</GetOrdersRequest>';
			return $xmlBody;
		}


		/**
		 * Function loadDepenedency
		 *
		 * @name loadDepenedency
		 */
		public function loadDepenedency() {
			if ( is_file( __DIR__ . '/cedRequest.php' ) ) {
				require_once 'cedRequest.php';
			}
		}
	}
}
