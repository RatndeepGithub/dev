<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}


/**
 * Amazon shipment manager file.
 *
 * @since      1.0.0
 *
 * @package    Amazon_Integration_For_Woocommerce
 * @subpackage Amazon_Integration_For_Woocommerce/admin/amazon/lib
 */

if ( ! class_exists( 'Ced_Amazon_Shipment_Manager' ) ) :

	/**
	 * Order related functionalities.
	 *
	 * @since      1.0.0
	 * @package    Amazon_Integration_For_Woocommerce
	 * @subpackage Amazon_Integration_For_Woocommerce/admin/amazon/lib
	 * @link       http://www.cedcommerce.com/
	 */
	class Ced_Amazon_Shipment_Manager {

		public function getTrackingDetails( $pluginName, $order_id ) {

			switch ( $pluginName ) {
				case 'Advanced Shipment Tracking for WooCommerce':
					$this->get_Advanced_Shipment_Tracking_for_WooCommerce( $order_id );
				default:
					return array();
			}
		}

		public function get_Advanced_Shipment_Tracking_for_WooCommerce( $order_id ) {

			// Check if function exist
			if ( function_exists( 'ast_get_tracking_items' ) ) {

				$tracking_array = array();
				$tracking_items = ast_get_tracking_items( $order_id );

				foreach ( $tracking_items as $tracking_item ) {
					$tracking_array['tracking_number']        = $tracking_item['tracking_number'];
					$tracking_array['tracking_provider_name'] = $tracking_item['formatted_tracking_provider'];
					$tracking_array['tracking_provider_code'] = $tracking_item['tracking_provider'];
					$tracking_array['tracking_url']           = $tracking_item['formatted_tracking_link'];
					$tracking_array['date_shipped']           = date_i18n( get_option( 'date_format' ), $tracking_item['date_shipped'] );
				}

				return $tracking_array;
			}

		}

	}

endif;
