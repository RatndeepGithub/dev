<?php
/**
 * Manage all configuration and product related functionality required.
 *
 * @package  Woocommerce_Walmart_Integration
 * @version  1.0.0
 * @link     https://woocommerce.com/vendor/cedcommerce/
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}


	/**
	 * Ced_Walmart_Manager.
	 *
	 * @since 1.0.0
	 */
class Ced_Walmart_Manager {

	/**
	 * The instance variable of this class.
	 *
	 * @since    1.0.0
	 * @var      object    $_instance    The instance variable of this class.
	 */
	private static $_instance;
	/**
	 * Ced_Walmart_Manager Instance.
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
	 * Ced_Walmart_Manager construct.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->load_dependency();
		add_action( 'ced_walmart_refresh_token', array( $this, 'ced_walmart_refresh_token' ) );
	}

	/**
	 * Load the dependencies.
	 *
	 * @since    1.0.0
	 */
	public function load_dependency() {
		$ced_walmart_process_request_file = CED_WALMART_DIRPATH . 'admin/walmart/lib/class-ced-walmart-process-request.php';
		include_file( $ced_walmart_process_request_file );
		$this->ced_walmart_process_instance = Ced_Walmart_Remote_Request::get_instance();
		$ced_walmart_product_file           = CED_WALMART_DIRPATH . 'admin/walmart/lib/class-ced-walmart-product.php';
		include_file( $ced_walmart_product_file );
		$this->ced_walmart_product_manager = Ced_Walmart_Product::get_instance();
	}

	/**
	 * Ced_Walmart_Manager ced_walmart_refresh_token.
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_refresh_token( $store_id = '' ) {
		if ( ! get_transient( 'ced_walmart_token_transient_' . $store_id ) ) {
			$config_details                               = ced_walmart_return_partner_detail_option();
			$config_details                               = isset( $config_details[ $store_id ]['config_details'] ) ? $config_details[ $store_id ]['config_details'] : array();
			$client_id                                    = isset( $config_details['client_id'] ) ? $config_details['client_id'] : '';
			$client_secret                                = isset( $config_details['client_secret'] ) ? $config_details['client_secret'] : '';
			$action                                       = 'token';
			$parameters                                   = 'grant_type=client_credentials';
			$this->ced_walmart_process_instance->store_id = $store_id;
			$response                                     = $this->ced_walmart_process_instance->ced_walmart_process_request( $action, $parameters, array(), 'GET');
			$token                                        = get_option( 'ced_walmart_tokens', array() );
			$token[ $store_id ]                           = isset( $response['result']['access_token'] ) ? $response['result']['access_token'] : '';
			$expries_in                                   = isset( $response['result']['expires_in'] ) ? $response['result']['expires_in'] : '';
			if ( ! empty( $token ) ) {
				update_option( 'ced_walmart_tokens', $token );
				set_transient( 'ced_walmart_token_transient_' . $store_id, $token, $expries_in );
			}
		}
	}

	/**
	 * Ced_Walmart_Manager ced_walmart_create_auto_profiles.
	 *
	 * @since 1.0.0
	 * @param array $walmart_mapped_categories Mapped Categories.
	 * @param array $walmart_mapped_categories_name Mapped Categories Name.
	 */
	public function ced_walmart_create_auto_profiles( $walmart_mapped_categories = array(), $walmart_mapped_categories_name = array() ) {

		$woo_store_categories           = get_terms( 'product_cat' );
		$already_mapped_categories      = get_option( 'ced_woo_walmart_mapped_categories', array() );
		$already_mapped_categories_name = get_option( 'ced_woo_walmart_mapped_categories_name', array() );

		if ( ! empty( $walmart_mapped_categories ) ) {
			foreach ( $walmart_mapped_categories as $key => $value ) {
				$profile_id              = '';
				$profile_already_created = get_term_meta( $key, 'ced_walmart_profile_created', true );
				$created_profile_id      = get_term_meta( $key, 'ced_walmart_profile_id', true );
				if ( 'yes' == $profile_already_created && ! empty( $created_profile_id ) ) {
					$new_profile_need_to_be_created = $this->check_if_new_profile_need_to_be_created( $key, $value );

					if ( ! $new_profile_need_to_be_created ) {
						continue;
					} else {
						$this->reset_mapped_category_data( $key, $value );
					}
				}

				$woo_categories      = array();
				$category_attributes = array();

				$profile_name = isset( $walmart_mapped_categories_name[ $value ] ) ? $walmart_mapped_categories_name[ $value ] : 'Profile for Walmart - Category Id : ' . $value;

				$ced_walmart_profile_details = get_option( 'ced_walmart_profile_details', array() );
				$traverse_this               = true;
				foreach ( $ced_walmart_profile_details['ced_walmart_profile_details'] as $id => $profile_data ) {
					if ( $profile_name == $profile_data['profile_name'] && $traverse_this ) {
						$profile_id    = $id;
						$traverse_this = false;
					}
				}

				if ( empty( $profile_id ) ) {
					$is_active        = 1;
					$marketplace_name = 'Walmart';

					foreach ( $walmart_mapped_categories as $key1 => $value1 ) {
						if ( $value1 == $value ) {
							$woo_categories[] = $key1;
						}
					}

					$profile_data = array();
					$profile_data = $this->ced_walmart_prepare_profile_data( $value, $profile_name );

					$profile_details             = array(
						'profile_name'   => $profile_name,
						'profile_status' => 'active',
						'profile_data'   => json_encode( $profile_data ),
						'woo_categories' => json_encode( $woo_categories ),
					);
					$profile_id                  = $this->insert_walmart_profile( $profile_details, $profile_name );
					$ced_walmart_profile_details = get_option( 'ced_walmart_profile_details', array() );
					$ced_walmart_profile_details['ced_walmart_profile_details'][ $profile_id ]['profile_id'] = $profile_id;
					update_option( 'ced_walmart_profile_details', $ced_walmart_profile_details );
				} else {
					$woo_categories              = array();
					$ced_walmart_profile_details = get_option( 'ced_walmart_profile_details', array() );
					$woo_categories              = json_decode( $ced_walmart_profile_details['ced_walmart_profile_details'][ $profile_id ]['woo_categories'], true );
					$woo_categories[]            = $key;
					$ced_walmart_profile_details['ced_walmart_profile_details'][ $profile_id ]['woo_categories'] = json_encode( array_unique( $woo_categories ) );
					update_option( 'ced_walmart_profile_details', $ced_walmart_profile_details );
				}
				foreach ( $woo_categories as $index => $data ) {
					update_term_meta( $data, 'ced_walmart_profile_created', 'yes' );
					update_term_meta( $data, 'ced_walmart_profile_id', $profile_id );
					update_term_meta( $data, 'ced_walmart_mapped_category', $value );
				}
			}
		}
	}

	/**
	 * Ced_Walmart_Manager check_if_new_profile_need_to_be_created.
	 *
	 * @since 1.0.0
	 * @param int $woo_category_id Woocommerce Category Id.
	 * @param int $walmart_category_id Walmart category Id.
	 */
	public function check_if_new_profile_need_to_be_created( $woo_category_id = '', $walmart_category_id = '' ) {
		$old_walmart_category_mapped = get_term_meta( $woo_category_id, 'ced_walmart_mapped_category', true );
		if ( $old_walmart_category_mapped == $walmart_category_id ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Ced_Walmart_Manager reset_mapped_category_data.
	 *
	 * @since 1.0.0
	 * @param int $woo_category_id Woocommerce Category Id.
	 * @param int $walmart_category_id Walmart category Id.
	 */
	public function reset_mapped_category_data( $woo_category_id = '', $walmart_category_id = '' ) {
		update_term_meta( $woo_category_id, 'ced_walmart_mapped_category', $walmart_category_id );
		delete_term_meta( $woo_category_id, 'ced_walmart_profile_created' );
		$created_profile_id = get_term_meta( $woo_category_id, 'ced_walmart_profile_id', true );
		delete_term_meta( $woo_category_id, 'ced_walmart_profile_id' );
		$this->remove_category_mapping_from_profile( $created_profile_id, $woo_category_id );
	}

	/**
	 * Ced_Walmart_Manager remove_category_mapping_from_profile.
	 *
	 * @since 1.0.0
	 * @param int $created_profile_id Profile Id.
	 * @param int $woo_category_id Woocommerce Category Id.
	 */
	public function remove_category_mapping_from_profile( $created_profile_id = '', $woo_category_id = '' ) {

		$ced_walmart_profile_details = get_option( 'ced_walmart_profile_details', array() );
		$woo_categories              = isset( $ced_walmart_profile_details[ $created_profile_id ]['woo_categories'] ) ? $ced_walmart_profile_details[ $created_profile_id ]['woo_categories'] : array();

		if ( is_array( $woo_categories ) && ! empty( $woo_categories ) ) {
			$categories = array();
			foreach ( $woo_categories as $key => $value ) {
				if ( $value != $woo_category_id ) {
					$categories[] = $value;
				}
			}
			$categories = json_encode( $categories );
			$ced_walmart_profile_details[ $created_profile_id ]['woo_categories'] = $categories;
		}
	}

	/**
	 * Ced_Walmart_Manager ced_walmart_prepare_profile_data.
	 *
	 * @since 1.0.0
	 * @param int $walmart_store_id Walmart Shop Id.
	 * @param int $walmart_category_id Walmart category Id.
	 */
	public function ced_walmart_prepare_profile_data( $walmart_category_id ) {
		$profile_data                                     = array();
		$profile_data['_umb_walmart_category']['default'] = $walmart_category_id;
		return $profile_data;
	}


	/**
	 * Ced_Walmart_Manager insert_walmart_profile.
	 *
	 * @since 1.0.0
	 * @param array $profile_details Profile details.
	 */
	public function insert_walmart_profile( $profile_details, $profile_name ) {
		$ced_walmart_profile_details = get_option( 'ced_walmart_profile_details', array() );
		$count                       = get_option( 'ced_walmart_profile_counts', '' );
		if ( empty( $count ) ) {
			$count = 0;
		}
		$count++;
		$ced_walmart_profile_details['ced_walmart_profile_details'][ $count ] = $profile_details;
		update_option( 'ced_walmart_profile_details', $ced_walmart_profile_details );
		update_option( 'ced_walmart_profile_counts', $count );
		return $count;
	}

	/**
	 * Ced_Walmart_Manager ced_walmart_upload.
	 *
	 * @since 1.0.0
	 * @param array  $walmart_product_ids.
	 * @param string $process_mode.
	 */
	public function ced_walmart_upload( $walmart_product_ids = array(), $process_mode = 'CREATE', $store_id = '' ) {
		$response = $this->ced_walmart_product_manager->ced_walmart_prepare_data( $walmart_product_ids, $process_mode, $store_id );
		if ( $response ) {
			$filepath = $response;
			$response = $this->ced_walmart_product_manager->ced_walmart_upload( $filepath, 'MP_ITEM', $store_id );
		}
		return $response;
	}

	/**
	 * Ced_Walmart_Manager ced_walmart_update_price.
	 *
	 * @since 1.0.0
	 * @param array $walmart_product_ids.
	 */
	public function ced_walmart_update_price( $walmart_product_ids = array(), $store_id = '' ) {
		$response = $this->ced_walmart_product_manager->ced_walmart_prepare_price_data( $walmart_product_ids, $store_id );
		if ( $response ) {
			$filepath = $response;
			$response = $this->ced_walmart_product_manager->ced_walmart_upload( $filepath, 'price', $store_id );
		}
		return $response;
	}

	/**
	 * Ced_Walmart_Manager ced_walmart_update_stock.
	 *
	 * @since 1.0.0
	 * @param array $walmart_product_ids.
	 */
	public function ced_walmart_update_stock( $walmart_product_ids = array(), $store_id = '' ) {
		$response = $this->ced_walmart_product_manager->ced_walmart_prepare_stock_data( $walmart_product_ids, $store_id );
		if ( $response ) {
			$filepath = $response;
			$response = $this->ced_walmart_product_manager->ced_walmart_upload( $filepath, 'inventory', $store_id );
		}
		return $response;
	}


	/**
	 * Ced_Walmart_Manager ced_walmart_convert_to_wfs.
	 *
	 * @since 1.0.0
	 * @param array $walmart_product_ids.
	 */
	public function ced_walmart_upload_as_wfs( $walmart_product_ids = array() ) {
		$response = $this->ced_walmart_product_manager->ced_walmart_prepare_wfs_new_item_data( $walmart_product_ids );
		if ( $response ) {
			$filepath = $response;
			$response = $this->ced_walmart_product_manager->ced_walmart_upload( $filepath, 'MP_WFS_ITEM' );
		}
		return $response;
	}


	/**
	 * Ced_Walmart_Manager ced_walmart_convert_to_wfs.
	 *
	 * @since 1.0.0
	 * @param array $walmart_product_ids.
	 */
	public function ced_walmart_convert_to_wfs( $walmart_product_ids = array() ) {
		$response = $this->ced_walmart_product_manager->ced_walmart_prepare_wfs_data( $walmart_product_ids );
		if ( $response ) {
			$filepath = $response;
			$response = $this->ced_walmart_product_manager->ced_walmart_upload( $filepath, 'OMNI_WFS' );
		}
		return $response;
	}

	/**
	 * Ced_Walmart_Manager ced_walmart_update_shipping_template.
	 *
	 * @since 1.0.0
	 * @param array $walmart_product_ids.
	 */
	public function ced_walmart_update_shipping_template( $walmart_product_ids = array(), $action = 'Add', $store_id = '' ) {
		$response = $this->ced_walmart_product_manager->ced_walmart_prepare_shipping_template_data( $walmart_product_ids, $action );
		if ( $response ) {
			$filepath = $response;
			$response = $this->ced_walmart_product_manager->ced_walmart_upload( $filepath, 'SKU_TEMPLATE_MAP', $store_id );
		}
		return $response;
	}



	/**
	 * Ced_Walmart_Manager ced_walmart_retire_items
	 *
	 * @since 1.0.0
	 * @param array $walmart_product_ids.
	 */

	public function ced_walmart_retire_items( $walmart_product_ids = array(), $store_id = '' ) {
		$response = $this->ced_walmart_product_manager->ced_walmart_prepare_retire_items( $walmart_product_ids );
		if ( $response ) {
			$filepath = $response;
			$response = $this->ced_walmart_product_manager->ced_walmart_upload( $filepath, 'RETIRE_ITEM' );
		}
		return $response;
	}
}
