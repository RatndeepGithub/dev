<?php
/**
 * Gettting order related data
 *
 * @package  Walmart_Woocommerce_Integration
 * @version  1.0.0
 * @link     https://woocommerce.com/vendor/cedcommerce/
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Ced_Walmart_Order
 *
 * @since 1.0.0
 * @param object $_instance Class instance.
 */
class Ced_Walmart_Order {

	/**
	 * The instance variable of this class.
	 *
	 * @since    1.0.0
	 * @var      object    $_instance    The instance variable of this class.
	 */

	public static $_instance;

	/**
	 * Ced_Walmart_Order Instance.
	 *
	 * @since 1.0.0
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Ced_Walmart_Order construct.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->load_dependency();
	}

	/**
	 * Ced_Walmart_Order loading dependency.
	 *
	 * @since 1.0.0
	 */
	public function load_dependency() {
		$ced_walmart_curl_file = CED_WALMART_DIRPATH . 'admin/walmart/lib/class-ced-walmart-curl-request.php';
		include_file( $ced_walmart_curl_file );
		$this->ced_walmart_curl_instance = Ced_Walmart_Curl_Request::get_instance();
	}

	/**
	 * Function for creating a local order
	 *
	 * @since 1.0.0
	 * @param array $orders Order Details.
	 */
	public function create_local_order( $orders, $store_id ) {
		if ( is_array( $orders ) && ! empty( $orders ) ) {

			foreach ( $orders as $order_detail ) {

				$order_number = isset( $order_detail['purchaseOrderId'] ) ? $order_detail['purchaseOrderId'] : '';

				$final_tax             = 0;
				$shipping_to_array     = isset( $order_detail['shippingInfo'] ) ? $order_detail['shippingInfo'] : array();
				$recipient             = isset( $shipping_to_array['postalAddress'] ) ? $shipping_to_array['postalAddress'] : array();
				$ship_to_first_name    = isset( $recipient['name'] ) ? $recipient['name'] : '';
				$customer_phone_number = isset( $shipping_to_array['phone'] ) ? $shipping_to_array['phone'] : '';

				$address_array      = isset( $shipping_to_array['postalAddress'] ) ? $shipping_to_array['postalAddress'] : '';
				$ship_to_address1   = isset( $address_array['address1'] ) ? $address_array['address1'] : '';
				$ship_to_address2   = isset( $address_array['address2'] ) ? $address_array['address2'] : '';
				$ship_to_city_name  = isset( $address_array['city'] ) ? $address_array['city'] : '';
				$ship_to_state_code = isset( $address_array['state'] ) ? $address_array['state'] : '';
				$ship_to_zip_code   = isset( $address_array['postalCode'] ) ? $address_array['postalCode'] : '';
				$ship_to_country    = isset( $address_array['country'] ) ? $address_array['country'] : '';

				$shipping_address = array(
					'first_name' => $ship_to_first_name,
					'phone'      => $customer_phone_number,
					'address_1'  => $ship_to_address1,
					'address_2'  => $ship_to_address2,
					'city'       => $ship_to_city_name,
					'state'      => $ship_to_state_code,
					'postcode'   => $ship_to_zip_code,
					'country'    => $ship_to_country,
				);

				$buyer              = isset( $order_detail['buyer'] ) ? $order_detail['buyer'] : array();
				$bill_to_first_name = isset( $buyer['name'] ) ? $buyer['name'] : $ship_to_first_name;
				$bill_email_address = isset( $order_detail['customerEmailId'] ) ? $order_detail['customerEmailId'] : '';
				$bill_phone_number  = isset( $buyer['phone_number'] ) ? $buyer['phone_number'] : $customer_phone_number;

				$billing_address = array(
					'first_name' => $bill_to_first_name,
					'email'      => $bill_email_address,
					'phone'      => $bill_phone_number,
				);

				$order_items = isset( $order_detail['orderLines']['orderLine'][0] ) ? $order_detail['orderLines']['orderLine'] : $order_detail['orderLines'];

				$walmart_order_cancel_item  = array();
				$walmart_order_shipped_item = array();

				$walmart_shipped_status = false;

				$shipping_amount = 0;
				if ( count( $order_items ) ) {
					$item_array = array();
					foreach ( $order_items as $order_item ) {
						$walmart_order_item = array();

						$sku = isset( $order_item['item']['sku'] ) ? $order_item['item']['sku'] : '';

						$ordered_qty = isset( $order_item['orderLineQuantity']['amount'] ) ? $order_item['orderLineQuantity']['amount'] : '';
						$cancel_qty  = isset( $order_item['orderLineStatuses']['orderLineStatus'][0]['status'] ) && 'Cancelled' == $order_item['orderLineStatuses']['orderLineStatus'][0]['status'] ? $order_item['orderLineStatuses']['orderLineStatus'][0]['statusQuantity']['amount'] : '';
						$item_prices = isset( $order_item['charges']['charge'][0] ) ? $order_item['charges']['charge'] : $order_item['charges'];

						$base_price = 0;
						if ( isset( $item_prices ) && ! empty( $item_prices ) ) {
							foreach ( $item_prices as $item_price ) {
								if ( 'PRODUCT' == $item_price['chargeType'] ) {
									$pro_price   = isset( $item_price['chargeAmount']['amount'] ) ? floatval( $item_price['chargeAmount']['amount'] ) : 0;
									$tax_price   = isset( $item_price['tax']['taxAmount']['amount'] ) ? floatval( $item_price['tax']['taxAmount']['amount'] ) : 0;
									$final_price = $pro_price;
									$final_tax  += $tax_price;
									$base_price += $final_price;
								}
								if ( 'SHIPPING' == $item_price['chargeType'] ) {
									$pro_price        = isset( $item_price['chargeAmount']['amount'] ) ? floatval( $item_price['chargeAmount']['amount'] ) : 0;
									$tax_price        = isset( $item_price['tax']['taxAmount']['amount'] ) ? floatval( $item_price['tax']['taxAmount']['amount'] ) : 0;
									$final_price      = $pro_price;
									$final_tax       += $tax_price;
									$shipping_amount += $final_price;
								}
							}
						}

						if ( isset( $order_item['orderLineStatuses']['orderLineStatus'][0]['status'] ) ) {
							if ( 'Created' == $order_item['orderLineStatuses']['orderLineStatus'][0]['status'] ) {
								$order_status         = 'wc-processing';
								$walmart_order_status = 'Created';
							}
							if ( 'Acknowledged' == $order_item['orderLineStatuses']['orderLineStatus'][0]['status'] ) {
								$order_status         = 'wc-processing';
								$walmart_order_status = 'Acknowledged';
							}
							if ( 'Shipped' == $order_item['orderLineStatuses']['orderLineStatus'][0]['status'] ) {
								$order_status         = 'wc-completed';
								$walmart_order_status = 'Shipped';
							}
							if ( 'Cancelled' == $order_item['orderLineStatuses']['orderLineStatus'][0]['status'] ) {
								$order_status         = 'wc-cancelled';
								$walmart_order_status = 'Cancelled';
							}
						}

						$ship_service = isset( $order_item['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['carrierName']['carrier'] ) ? $order_item['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['carrierName']['carrier'] : '';

						$item = array(
							'OrderedQty' => $ordered_qty,
							'CancelQty'  => $cancel_qty,
							'UnitPrice'  => $base_price,
							'Sku'        => $sku,
						);

						$item_array[] = $item;

						$walmart_order_item['lineNumber']                     = $order_item['lineNumber'];
						$walmart_order_item['shipment_item_id']               = $order_item['lineNumber'];
						$walmart_order_item['merchant_sku']                   = $sku;
						$walmart_order_item['response_shipment_sku_quantity'] = $ordered_qty;
						$walmart_order_item['response_shipment_cancel_qty']   = $cancel_qty;
						$walmart_order_item['RMA_number']                     = 0;
						$walmart_order_item['days_to_return']                 = 0;
						$walmart_order_item['return_location']['address1']    = $ship_to_address1;
						$walmart_order_item['return_location']['address2']    = $ship_to_address2;
						$walmart_order_item['return_location']['city']        = $ship_to_city_name;
						$walmart_order_item['return_location']['state']       = $ship_to_state_code;
						$walmart_order_item['return_location']['zip_code']    = $ship_to_zip_code;
						$walmart_order_item['status']                         = $walmart_order_status;
						$walmart_order_item['address']                        = 1;

						if ( 'Shipped' == $walmart_order_status ) {
							$walmart_order_shipped_item[] = $walmart_order_item;
						}

						if ( 'Cancelled' == $walmart_order_status ) {
							$walmart_order_cancel_item[] = $walmart_order_item;
						}
					}
				}
				$ship_service     = isset( $order_detail['orderLines']['orderLine'][0]['fulfillment']['shipMethod'] ) ? $order_detail['orderLines']['orderLine'][0]['fulfillment']['shipMethod'] : '';
				$order_number     = isset( $order_detail['purchaseOrderId'] ) ? $order_detail['purchaseOrderId'] : '';
				$order_items_info = array(
					'OrderNumber'    => $order_number,
					'ship_service'   => $ship_service,
					'ShippingAmount' => $shipping_amount,
					'ItemsArray'     => $item_array,
					'tax'            => $final_tax,
				);

				$address = array(
					'shipping' => $shipping_address,
					'billing'  => $billing_address,
				);

				$merchant_order_id = isset( $order_detail['customerOrderId'] ) ? $order_detail['customerOrderId'] : '';
				$purchase_order_id = isset( $order_detail['purchaseOrderId'] ) ? $order_detail['purchaseOrderId'] : '';

				$fulfillment_node = isset( $order_detail['fulfillment_node'] ) ? $order_detail['fulfillment_node'] : '';
				$order_detail     = isset( $order_detail ) ? $order_detail : array();

				$walmart_order_meta = array(
					'merchant_order_id' => $merchant_order_id,
					'purchaseOrderId'   => $purchase_order_id,
					'fulfillment_node'  => $fulfillment_node,
					'order_detail'      => $order_detail,
					'order_items'       => $order_items,
				);

				$walmart_shipped_details = array();

				if ( 'Shipped' == $walmart_order_status ) {
					$walmart_order_items                        = isset( $order_detail['orderLines']['orderLine'][0] ) ? $order_detail['orderLines']['orderLine'][0] : $order_detail['orderLines']['orderLine'];
					$shipped_detail['purchaseOrderId']          = $order_detail['purchaseOrderId'];
					$shipped_detail['alt_shipment_id']          = 0;
					$shipped_detail['shipment_tracking_number'] = isset( $walmart_order_items['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['trackingNumber'] ) ? $walmart_order_items['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['trackingNumber'] : 0;
					$shipped_detail['response_shipment_date']   = isset( $walmart_order_items['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['shipDateTime'] ) ? $walmart_order_items['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['shipDateTime'] : 0;
					$shipped_detail['response_shipment_method'] = '';
					$shipped_detail['expected_delivery_date']   = isset( $order_detail['shippingInfo']['estimatedDeliveryDate'] ) ? $order_detail['shippingInfo']['estimatedDeliveryDate'] : 0;
					$shipped_detail['ship_from_zip_code']       = 'zipcode';
					$shipped_detail['carrier_pick_up_date']     = isset( $walmart_order_items['statusDate'] ) ? $walmart_order_items['statusDate'] : 0;
					$shipped_detail['carrier']                  = isset( $walmart_order_items['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['carrierName']['carrier'] ) ? $walmart_order_items['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['carrierName']['carrier'] : 0;
					$shipped_detail['shipment_tracking_url']    = isset( $walmart_order_items['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['trackingURL'] ) ? $walmart_order_items['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['trackingURL'] : 0;
					$shipped_detail['methodCode']               = isset( $walmart_order_items['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['methodCode'] ) ? $walmart_order_items['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['methodCode'] : 0;
					$shipped_detail['shipment_items']           = $walmart_order_shipped_item;
					$shipped_detail['cancel_items']             = $walmart_order_cancel_item;

					$walmart_shipped_details['shipments'][0] = $shipped_detail;
				}

						// CREATE ORDER

				$order_id = $this->create_order( $address, $order_items_info, 'Walmart', $walmart_order_meta, $store_id );
				if ( isset( $order_id ) && ! empty( $order_id ) ) {
					$order = wc_get_order( $order_id );
					$order->update_status( $order_status );

					if ( count( $order_items ) ) {
						$item_array = array();
						foreach ( $order_items as $order_item ) {
							$sku         = isset( $order_item['item']['sku'] ) ? $order_item['item']['sku'] : '';
							$ordered_qty = isset( $order_item['orderLineQuantity']['amount'] ) ? $order_item['orderLineQuantity']['amount'] : '';
							$cancel_qty  = isset( $order_item['orderLineStatuses']['orderLineStatus'][0]['status'] ) && 'Cancelled' == $order_item['orderLineStatuses']['orderLineStatus'][0]['status'] ? $order_item['orderLineStatuses']['orderLineStatus'][0]['statusQuantity']['amount'] : '';
							$item_prices = isset( $order_item['charges']['charge'][0] ) ? $order_item['charges']['charge'] : $order_item['charges'];
							$base_price  = 0;
							if ( isset( $item_prices ) && ! empty( $item_prices ) ) {
								$shipping_amount = 0;
								foreach ( $item_prices as $item_price ) {

									if ( 'SHIPPING' == $item_price['chargeType'] ) {
										$pro_price        = isset( $item_price['chargeAmount']['amount'] ) ? floatval( $item_price['chargeAmount']['amount'] ) : 0;
										$tax_price        = isset( $item_price['tax']['taxAmount']['amount'] ) ? floatval( $item_price['tax']['taxAmount']['amount'] ) : 0;
										$final_price      = $pro_price;
										$final_tax       += $tax_price;
										$shipping_amount += $final_price;
										$ship_service     = isset( $order_item['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['carrierName']['carrier'] ) ? $order_item['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['carrierName']['carrier'] : 'Shipping';
										$method_code      = isset( $order_item['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['methodCode'] ) ? $order_item['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['methodCode'] : '';
										$tracking_number  = isset( $order_item['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['trackingNumber'] ) ? $order_item['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['trackingNumber'] : '';
										$tracking_url     = isset( $order_item['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['trackingURL'] ) ? $order_item['orderLineStatuses']['orderLineStatus'][0]['trackingInfo']['trackingURL'] : '';

									}
								}

								$shipping_label = 'Shipping - ' . $order_item['item']['sku'];
								if ( ! empty( $method_code ) && ! empty( $tracking_number ) && ! empty( $tracking_url ) ) {
									$shipping_label = "$ship_service - " . $order_item['item']['sku'] . "\n\n" . 'Method Code - ' . $method_code . "\n\n" . 'Tracking Number - ' . $tracking_number . "\n\n" . 'Tracking URL - ' . $tracking_url;
								}

								$args = array(
									'id'      => "$ship_service - " . $order_item['item']['sku'], // ID for the rate. If not passed, this id:instance default will be used.
									'label'   => $shipping_label, // Label for the rate
									'cost'    => $shipping_amount, // Amount or array of costs (per item shipping)
									'taxes'   => '', // Pass taxes, or leave empty to have it calculated for you, or 'false' to disable calculations
									'package' => false, // Package array this rate was generated for @since 2.6.0
								);

								$rate                      = new WC_Shipping_Rate( $args['id'], $args['label'], $args['cost'], $args['taxes'], $args['id'] );
								$shipping_added_succefully = get_post_meta( $order_id, 'shipping_added_succefully', true );

								if ( isset( $shipping_added_succefully ) && 'yes' != $shipping_added_succefully ) {
									$order->add_item( $rate );
									update_post_meta( $order_id, 'shipping_added_succefully', 'yes' );
								}
							}
						}
					}

					if ( 'Acknowledged' == $walmart_order_status || 'Created' == $walmart_order_status ) {
						$auto_acknowledge = get_option( 'ced_walmart_auto_acknowledge_orders', '' );
						$response         = $this->acknowledge_order( $order_id );
						if ( isset( $response['order']['orderLines'] ) ) {
							update_post_meta( $order_id, '_ced_walmart_order_status', 'Acknowledged' );
						} else {
							update_post_meta( $order_id, '_ced_walmart_order_status', $walmart_order_status );
						}
					} else {
						update_post_meta( $order_id, '_ced_walmart_order_status', $walmart_order_status );
					}

					update_post_meta( $order_id, '_ced_walmart_shipped_data', $walmart_shipped_details );
				}
			}
		}
	}

	/**
	 * Function for creating order in woocommerce
	 *
	 * @since 1.0.0
	 * @param array  $address Shipping and billing address.
	 * @param array  $order_items_info Order items details.
	 * @param string $marketplace marketplace name.
	 * @param array  $order_meta Order meta details.
	 */
	public function create_order( $address = array(), $order_items_info = array(), $marketplace = 'Walmart', $order_meta = array(), $store_id = '' ) {
		$order_id      = '';
		$order_created = false;
		if ( count( $order_items_info ) ) {

			$order_number = isset( $order_items_info['OrderNumber'] ) ? $order_items_info['OrderNumber'] : 0;
			$order_id     = $this->is_walmart_order_exists( $order_number );
			if ( $order_id ) {
				return $order_id;
			}

			if ( count( $order_items_info ) ) {
				$items_array = isset( $order_items_info['ItemsArray'] ) ? $order_items_info['ItemsArray'] : array();
				if ( is_array( $items_array ) ) {
					foreach ( $items_array as $item_info ) {
						$pro_id          = isset( $item_info['ID'] ) ? intval( $item_info['ID'] ) : 0;
						$sku             = isset( $item_info['Sku'] ) ? $item_info['Sku'] : '';
						$mfr_part_number = isset( $item_info['MfrPartNumber'] ) ? $item_info['MfrPartNumber'] : '';
						$upc             = isset( $item_info['UPCCode'] ) ? $item_info['UPCCode'] : '';
						$asin            = isset( $item_info['ASIN'] ) ? $item_info['ASIN'] : '';

						if ( ! $pro_id && ! empty( $sku ) ) {
							$pro_id = wc_get_product_id_by_sku( $sku );
						}
						if ( ! $pro_id ) {
							$pro_id = $sku;
						}

						$qty                    = isset( $item_info['OrderedQty'] ) ? intval( $item_info['OrderedQty'] ) : 0;
						$unit_price             = isset( $item_info['UnitPrice'] ) ? floatval( $item_info['UnitPrice'] ) : 0;
						$extend_unit_price      = isset( $item_info['ExtendUnitPrice'] ) ? floatval( $item_info['ExtendUnitPrice'] ) : 0;
						$extend_shipping_charge = isset( $item_info['ExtendShippingCharge'] ) ? floatval( $item_info['ExtendShippingCharge'] ) : 0;

						$_product = wc_get_product( $pro_id );
						if ( is_wp_error( $_product ) ) {
							continue;
						} elseif ( is_null( $_product ) ) {
							continue;
						} elseif ( ! $_product ) {
							continue;
						} else {
							if ( ! $order_created ) {
								$order_data = array(

									/** Get default order status
									 *
									 * @since 1.0.0
									 */
									'status'        => apply_filters( 'woocommerce_default_order_status', 'pending' ),
									'customer_note' => __( 'Order from ', 'walmart-woocommmerce-integration' ) . $marketplace,
									'created_via'   => $marketplace,
								);

								/* ORDER CREATED IN WOOCOMMERCE */
								$order = wc_create_order( $order_data );

								/* ORDER CREATED IN WOOCOMMERCE */

								if ( is_wp_error( $order ) ) {
									continue;
								} elseif ( false === $order ) {
									continue;
								} else {
									$order_id      = $order->get_id();
									$order_created = true;
								}
							}
							$_product->set_price( $unit_price );
							$order->add_product( $_product, $qty );
							$order->calculate_totals();
						}
					}
				}

				if ( ! $order_created ) {
					return false;
				}

				$order_item_amount = isset( $order_items_info['OrderItemAmount'] ) ? $order_items_info['OrderItemAmount'] : 0;
				$shipping_amount   = isset( $order_items_info['ShippingAmount'] ) ? $order_items_info['ShippingAmount'] : 0;
				$discount_amount   = isset( $order_items_info['DiscountAmount'] ) ? $order_items_info['DiscountAmount'] : 0;
				$refund_amount     = isset( $order_items_info['RefundAmount'] ) ? $order_items_info['RefundAmount'] : 0;
				$ship_service      = isset( $order_items_info['ship_service'] ) ? $order_items_info['ship_service'] : '';

				if ( ! empty( $ship_service ) ) {
					$ship_params = array(
						'ShippingCost' => $shipping_amount,
						'ship_service' => $ship_service,
					);
					$this->add_shipping_charge( $order, $ship_params );
				}

				$shipping_address = isset( $address['shipping'] ) ? $address['shipping'] : '';
				if ( is_array( $shipping_address ) ) {
					$order->set_address( $shipping_address, 'shipping' );
				}

				$new_fee            = new stdClass();
				$new_fee->name      = 'Tax';
				$new_fee->amount    = (float) esc_attr( $order_items_info['tax'] );
				$new_fee->tax_class = '';
				$new_fee->taxable   = 0;
				$new_fee->tax       = '';
				$new_fee->tax_data  = array();
				$item_id            = $order->add_item( $new_fee );

				$billing_address = isset( $address['billing'] ) ? $address['billing'] : '';
				if ( is_array( $billing_address ) ) {
					$order->set_address( $billing_address, 'billing' );
				}

				$order->set_payment_method( 'check' );

				$order->set_total( $discount_amount, '' );

				$order->calculate_totals();

				update_post_meta( $order_id, '_ced_walmart_order_id', $order_number );
				update_post_meta( $order_id, '_ced_walmart_order', 1 );
				update_post_meta( $order_id, '_ced_walmart_order_status', 'Acknowledged' );
				update_post_meta( $order_id, '_order_marketplace', $marketplace );
				update_post_meta( $order_id, '_ced_walmart_order_store_id' . wifw_environment(), $store_id );

				if ( count( $order_meta ) ) {
					foreach ( $order_meta as $o_key => $o_value ) {
						update_post_meta( $order_id, $o_key, $o_value );
					}
				}
			}
			return $order_id;
		}
		return false;
	}

	/**
	 * Function to check  if order already exists
	 *
	 * @since 1.0.0
	 * @param int $order_number Walmart Order Id.
	 */
	public function is_walmart_order_exists( $order_number = 0 ) {
		global $wpdb;
		if ( $order_number ) {
			$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_ced_walmart_order_id' AND meta_value=%s LIMIT 1", $order_number ) );
			if ( $order_id ) {
				return $order_id;
			}
		}
		return false;
	}

	/**
	 * Function to acknowledge_order
	 *
	 * @since 1.0.0
	 * @param int $order_id Woo Order Id.
	 */
	public function acknowledge_order( $order_id = 0 ) {
		$order_details     = get_post_meta( $order_id, 'order_detail', true );
		$purchase_order_id = isset( $order_details['purchaseOrderId'] ) ? $order_details['purchaseOrderId'] : '';
		$action            = 'orders/' . esc_attr( $purchase_order_id ) . '/acknowledge';
		/** Refresh token hook for walmart
		 *
		 * @since 1.0.0
		 */
		do_action( 'ced_walmart_refresh_token' );
		$response = $this->ced_walmart_curl_instance->ced_walmart_post_request( $action );
		return $response;
	}

	/**
	 * Function to add shipping data
	 *
	 * @since 1.0.0
	 * @param object $order Order details.
	 * @param array  $ship_params Shipping details.
	 */
	public function add_shipping_charge( $order, $ship_params = array() ) {
		$ship_name = isset( $ship_params['ship_service'] ) ? ( $ship_params['ship_service'] ) : 'UMB Default Shipping';
		$ship_cost = isset( $ship_params['ShippingCost'] ) ? $ship_params['ShippingCost'] : 0;
		$ship_tax  = isset( $ship_params['ShippingTax'] ) ? $ship_params['ShippingTax'] : 0;

		$item = new WC_Order_Item_Shipping();

		$item->set_method_title( $ship_name );
		$item->set_method_id( $ship_name );
		$item->set_total( $ship_cost );
		$order->add_item( $item );

		$order->calculate_totals();
		$order->save();
	}
}
