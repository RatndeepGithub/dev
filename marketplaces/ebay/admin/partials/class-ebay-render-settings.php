<?php

namespace Ced\Ebay;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) )
	die;

if(!class_exists('Settings_View')){
class Settings_View {

    /**
		 * The reference the *Singleton* instance of this class.
		 *
		 * @var $instance
		 */
		private static $instance;

        private $global_settings = array();
        private $global_options = array();
        private $ebay_user;
        private $ebay_site;
        private $rsid;
        private $apiClient;
        private $advanced_settings_field;

		/**
		 * Returns the *Singleton* instance of this class.
		 *
		 * @return self::$instance The *Singleton* instance.
		 */
		public static function get_instance($ebay_user, $ebay_site) {
			if ( null === self::$instance ) {
				self::$instance = new self($ebay_user, $ebay_site);
			}

			return self::$instance;
		}

        protected function __construct($ebay_user, $ebay_site) {
            $this->apiClient = new \Ced\Ebay\CED_EBAY_API_Client();
            $this->apiClient->setJwtToken('abc');
            $this->ebay_user = $ebay_user;
            $this->ebay_site = $ebay_site;
            self::init();
        }

        public function init(){
            $remote_shop = ced_ebay_get_shop_data($this->ebay_user, $this->ebay_site);
            if(!empty($remote_shop) && isset($remote_shop['remote_shop_id'])){
                $this->rsid = $remote_shop['remote_shop_id'];
            }

            $this->global_settings = get_option( 'ced_ebay_global_settings', false );
            $this->global_options             = ! empty( get_option( 'ced_ebay_global_options' ) ) ? get_option( 'ced_ebay_global_options', array() ) : array();
            $this->advanced_settings_field    = array(
                'ced_ebay_inventory_schedule_info'       => array(
                    'title'       => 'Sync Stock Levels from Woo to eBay',
                    'div_name'    => 'ced_ebay_global_settings[ced_ebay_inventory_schedule_info]',
                    'value'       => ! empty( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_inventory_schedule_info'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_inventory_schedule_info'] : '',
                    'description' => 'Sync your WooCommerce product stock with your eBay listings. If you have variations on eBay, make sure that they have SKUs and the same SKUs are present in WooCommerce for the stock sync to work.',
                ),
                'ced_ebay_existing_products_sync'        => array(
                    'title'       => 'Link Existing eBay Products (Using same SKUs)',
                    'div_name'    => 'ced_ebay_global_settings[ced_ebay_existing_products_sync]',
                    'value'       => ! empty( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_existing_products_sync'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_existing_products_sync'] : '',
                    'description' => 'Link your WooCommerce products with eBay listings using same SKUs. No data is overwritten on either WooCommerce or eBay. This process is required for stock sync to work.',
                ),
                'ced_ebay_import_product_scheduler_info' => array(
                    'title'       => 'Import eBay Products to WooCommerce',
                    'div_name'    => 'ced_ebay_global_settings[ced_ebay_import_product_scheduler_info]',
                    'value'       => ! empty( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_import_product_scheduler_info'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_import_product_scheduler_info'] : '',
                    'description' => 'Automatically import your eBay listings and create them as WooCommerce products.',
                ),
                'ced_ebay_sync_ended_listings_info'      => array(
                    'title'       => 'Sync Manually Ended eBay Listings',
                    'div_name'    => 'ced_ebay_global_settings[ced_ebay_sync_ended_listings_invfo]',
                    'value'       => ! empty( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_sync_ended_listings_info'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_sync_ended_listings_info'] : '',
                    'description' => 'Remove eBAy listings in WooCommerce which have been removed from eBay seller hub.',
                ),
                'ced_ebay_order_schedule_info'           => array(
                    'title'       => 'Sync eBay Orders',
                    'div_name'    => 'ced_ebay_global_settings[ced_ebay_order_schedule_info]',
                    'value'       => ! empty( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_order_schedule_info'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_order_schedule_info'] : '',
                    'description' => 'Sync eBay orders in WooCommerce and create them as native WooCommerce orders. The synced eBay orders are easily distinguishable in WooCommerce Orders section.',
                ),
                'ced_ebay_auto_upload'                   => array(
                    'title'       => 'Automatically List Woo Products on eBay',
                    'div_name'    => 'ced_ebay_global_settings[ced_ebay_auto_upload]',
                    'value'       => ! empty( $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_auto_upload'] ) ? $this->global_settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_auto_upload'] : '',
                    'description' => 'Automatically list your WooCommerce products on eBay. Make sure that you have created templates before turning this ON.',
                ),
            );

            if ( ! isset( $this->global_options[ $this->ebay_user ][ $this->ebay_site ] ) ) {
                $this->global_options[ $this->ebay_user ][ $this->ebay_site ] = array(
                    'Brand'                 => array(
                        'meta_key'     => '',
                        'custom_value' => '',
                        'description'  => 'asdsaddsadsads',
                    ),
                    'Type'                   => array(
                        'meta_key'     => '',
                        'custom_value' => '',
                        'description'  => 'asdsaddsadsads',
                    ),
                    'MPN'                   => array(
                        'meta_key'     => '',
                        'custom_value' => '',
                        'description'  => 'asdsaddsadsads',
                    )
                );
            
                update_option( 'ced_ebay_global_options', $this->global_options );
            } else {
                $this->global_options = get_option( 'ced_ebay_global_options', true );
                if ( isset( $this->global_options[ $this->ebay_user ][ $this->ebay_site ] ) ) {
                    $tempGlobalOptions = array();
                    $tempGlobalOptions = $this->global_options;
                    $this->global_options = $tempGlobalOptions;
                }
            }
        }
        
        public function save_settings($_post_data){
            if ( isset( $_post_data['global_settings'] ) ) {
                $objDateTime = new \DateTime( 'NOW' );
                $timestamp   = $objDateTime->format( 'Y-m-d\TH:i:s\Z' );
        
                $settings                         = array();
                $sanitized_array                  = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
                $settings                         = get_option( 'ced_ebay_global_settings', array() );
                $settings[ $this->ebay_user ][ $this->ebay_site ] = isset( $sanitized_array['ced_ebay_global_settings'] ) ? $this->trimKeysRecursive( $sanitized_array['ced_ebay_global_settings'] ) : array();
                if ( ! empty( $settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_vat_percent'] ) && 0 < $settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_vat_percent'] ) {
                    $formatted_vat_percent                                    = number_format( $settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_vat_percent'], 1 );
                    $settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_vat_percent'] = $formatted_vat_percent;
                }
                $settings[ $this->ebay_user ][ $this->ebay_site ]['last_updated'] = $timestamp;
                update_option( 'ced_ebay_global_settings', $settings );
        
                $attribute_name = isset( $sanitized_array['ced_ebay_custom_item_specific']['attribute'] ) ? $sanitized_array['ced_ebay_custom_item_specific']['attribute'] : '';
        
                if ( isset( $settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_shipping_policy'] ) && ! empty( $settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_shipping_policy'] ) ) {
                    $selectedFulfillmentPolicy = $settings[ $this->ebay_user ][ $this->ebay_site ]['ced_ebay_shipping_policy'];
                    $ship_array                = explode( '|', $selectedFulfillmentPolicy );
                    $ship_bussiness_id         = $ship_array[0];
                    $ship_bussiness_name       = $ship_array[1];
                    if ( ! empty( $ship_bussiness_id ) ) {
                        $this->apiClient->setRequestRemoteMethod('GET');
                        $this->apiClient->setRequestTopic('seller-profiles');
                        $this->apiClient->setRequestRemoteQueryParams([
                            'type' => 'GetShippingPolicyById',
                            'shop_id' => $this->rsid,
                            'new_api' => true,
                            'fulfillmentPolicyId' => $ship_bussiness_id
                        ]);
                        $apiResponse = $this->apiClient->post();
                        if(isset($apiResponse['data'])){
                            $getPolicyDetails = json_decode($apiResponse['data'], true);
                        } else {
                            if(isset($apiResponse['error_code'])){
                                return $apiResponse;
                            } else {
                                return false;
                            }
                        }
                        if ( ! is_array( $getPolicyDetails ) ) {
                            return false;
                        }
                        if ( ! empty( $getPolicyDetails ) ) {
                            update_option( 'ced_ebay_business_policy_details_' . $this->ebay_user . '>' . $this->ebay_site, $getPolicyDetails );
                        }
                    }
                }
                // UPDATE GLOBAL OPTIONS
        
                $global_options          = get_option( 'ced_ebay_global_options', array() );
                $selected_global_options = isset( $sanitized_array['ced_ebay_global_options'][ $this->ebay_user ][ $this->ebay_site ] ) ? ( $sanitized_array['ced_ebay_global_options'][ $this->ebay_user ][ $this->ebay_site ] ) : array();
                if ( ! empty( $selected_global_options ) ) {
                    $global_options_array = array();
                    foreach ( $selected_global_options as $gKey => $gValue ) {
                        $explode_gKey = explode( '|', $gKey );
                        $global_options_array[ $explode_gKey[0] ][ $explode_gKey[1] ] = $gValue;
                    }
                }
                if ( ! empty( $global_options_array ) ) {
                    $global_options[ $this->ebay_user ][ $this->ebay_site ] = $global_options_array;
                    update_option( 'ced_ebay_global_options', $global_options );
                }
        
                if ( ! empty( $attribute_name ) ) {
                    $custom_item_specific                           = get_option( 'ced_ebay_custom_item_specific', array() );
                    $custom_item_specific[ $this->ebay_user ][ $this->ebay_site ][] = isset( $sanitized_array['ced_ebay_custom_item_specific'] ) ? ( $sanitized_array['ced_ebay_custom_item_specific'] ) : array();
                    update_option( 'ced_ebay_custom_item_specific', $custom_item_specific );
        
                    $ced_ebay_global_options = get_option( 'ced_ebay_global_options', array() );
                    $ced_ebay_global_options[ $this->ebay_user ][ $this->ebay_site ][ urlencode( $attribute_name ) ] = array(
                        'meta_key'     => isset( $sanitized_array['ced_ebay_custom_item_specific']['meta_key'] ) ? $sanitized_array['ced_ebay_custom_item_specific']['meta_key'] : '',
                        'custom_value' => isset( $sanitized_array['ced_ebay_custom_item_specific']['custom_value'] ) ? $sanitized_array['ced_ebay_custom_item_specific']['custom_value'] : '',
                    );
                    update_option( 'ced_ebay_global_options', $ced_ebay_global_options );
        
                }
        
                $scheduler_args                = array(
                    'user_id' => $this->ebay_user,
                    'site_id' => $this->ebay_site,
                );
                $inventory_schedule            = isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_inventory_schedule_info'] ) && '0' != $sanitized_array['ced_ebay_global_settings']['ced_ebay_inventory_schedule_info'] ? ( $sanitized_array['ced_ebay_global_settings']['ced_ebay_inventory_schedule_info'] ) : as_unschedule_all_actions( null, null, 'ced_ebay_inventory_scheduler_group_' . $this->ebay_user . '>' . $this->ebay_site );
                $order_schedule                = isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_order_schedule_info'] ) && '0' != $sanitized_array['ced_ebay_global_settings']['ced_ebay_order_schedule_info'] ? ( $sanitized_array['ced_ebay_global_settings']['ced_ebay_order_schedule_info'] ) : as_unschedule_all_actions( 'ced_ebay_order_scheduler_job_' . $this->ebay_user, array( 'data' => $scheduler_args ) );
                $existing_product_sync         = isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_existing_products_sync'] ) && '0' != $sanitized_array['ced_ebay_global_settings']['ced_ebay_existing_products_sync'] ? ( $sanitized_array['ced_ebay_global_settings']['ced_ebay_existing_products_sync'] ) : wp_clear_scheduled_hook( 'ced_ebay_existing_products_sync_job_' . $this->ebay_user, $scheduler_args );
                $sync_ended_listings_scheduler = isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_sync_ended_listings_info'] ) && '0' != $sanitized_array['ced_ebay_global_settings']['ced_ebay_sync_ended_listings_info'] ? ( $sanitized_array['ced_ebay_global_settings']['ced_ebay_sync_ended_listings_info'] ) : as_unschedule_all_actions( null, null, 'ced_ebay_sync_ended_listings_group_' . $this->ebay_user . '>' . $this->ebay_site );
                $auto_upload                   = isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_auto_upload'] ) && '0' != $sanitized_array['ced_ebay_global_settings']['ced_ebay_auto_upload'] ? ( $sanitized_array['ced_ebay_global_settings']['ced_ebay_auto_upload'] ) : as_unschedule_all_actions( null, null, 'ced_ebay_bulk_upload_' . $this->ebay_user . '>' . $this->ebay_site );
                $import_products_schedule      = isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_import_product_scheduler_info'] ) && '0' != $sanitized_array['ced_ebay_global_settings']['ced_ebay_import_product_scheduler_info'] ? ( $sanitized_array['ced_ebay_global_settings']['ced_ebay_import_product_scheduler_info'] ) :as_unschedule_all_actions( null, null, 'ced_ebay_product_importer_' . $this->ebay_user );;
                $plugin_migration              = isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_plugin_migration'] ) && '0' != $sanitized_array['ced_ebay_global_settings']['ced_ebay_plugin_migration'] ? ( $sanitized_array['ced_ebay_global_settings']['ced_ebay_plugin_migration'] ) : 'off';
        
                if ( isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_import_product_scheduler_info'] ) && empty( $sanitized_array['ced_ebay_global_settings']['ced_ebay_import_product_scheduler_info'] ) ) {
                    update_option( 'ced_ebay_clear_import_process', true );
                }
                if ( isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_inventory_schedule_info'] ) && empty( $sanitized_array['ced_ebay_global_settings']['ced_ebay_inventory_schedule_info'] ) ) {
                    delete_option( 'ced_ebay_stock_sync_progress_' . $this->ebay_user . '>' . $this->ebay_site );
                    if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_unschedule_all_actions' ) ) {
                        if ( as_has_scheduled_action( null, null, 'ced_ebay_inventory_scheduler_group_' . $this->ebay_user . '>' . $this->ebay_site ) ) {
                            as_unschedule_all_actions( null, null, 'ced_ebay_inventory_scheduler_group_' . $this->ebay_user . '>' . $this->ebay_site );
                        }
                    }
                }
                if ( isset( $sanitized_array['ced_ebay_global_settings']['ced_ebay_sync_ended_listings_info'] ) && empty( $sanitized_array['ced_ebay_global_settings']['ced_ebay_sync_ended_listings_info'] ) ) {
                    if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_unschedule_all_actions' ) ) {
                        if ( as_has_scheduled_action( null, null, 'ced_ebay_sync_ended_listings_group_' . $this->ebay_user . '>' . $this->ebay_site ) ) {
                            as_unschedule_all_actions( null, null, 'ced_ebay_sync_ended_listings_group_' . $this->ebay_user . '>' . $this->ebay_site );
                        }
                    }
                }
                if ( ! empty( $auto_upload ) && 'on' == $auto_upload && function_exists( 'as_schedule_recurring_action' ) ) {
                    if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_unschedule_all_actions' ) ) {
                        if ( as_has_scheduled_action( null, null, 'ced_ebay_bulk_upload_' . $this->ebay_user . '>' . $this->ebay_site ) ) {
                            as_unschedule_all_actions( null, null, 'ced_ebay_bulk_upload_' . $this->ebay_user . '>' . $this->ebay_site );
                        }
                    }
                    $action_scheduled = as_schedule_recurring_action( time(), 360, 'ced_ebay_recurring_bulk_upload', array( 'data' => $scheduler_args ), 'ced_ebay_bulk_upload' );
                }
                if ( ! empty( $inventory_schedule ) && 'on' == $inventory_schedule && function_exists( 'as_schedule_recurring_action' ) ) {
                    if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_unschedule_all_actions' ) ) {
                        if ( as_has_scheduled_action( null, null, 'ced_ebay_inventory_scheduler_group_' . $this->ebay_user . '>' . $this->ebay_site ) ) {
                            as_unschedule_all_actions( null, null, 'ced_ebay_inventory_scheduler_group_' . $this->ebay_user . '>' . $this->ebay_site );
                        }
                    }
                    delete_option( 'ced_eBay_update_chunk_product_' . $this->ebay_user . '>' . $this->ebay_site );
                    as_schedule_recurring_action( time(), 360, 'ced_ebay_inventory_scheduler_job_' . $this->ebay_user, array( 'data' => $scheduler_args ), 'ced_ebay_inventory_scheduler_group_' . $this->ebay_user . '>' . $this->ebay_site );
                    if ( class_exists( 'WC_Webhook' ) ) {
                        $get_webhook_id = ! empty( get_option( 'ced_ebay_prduct_update_webhook_id_' . $this->ebay_user, true ) ) ? get_option( 'ced_ebay_prduct_update_webhook_id_' . $this->ebay_user, true ) : false;
                        if ( $get_webhook_id ) {
                            $webhook        = new \WC_Webhook( $get_webhook_id );
                            $webhook_status = $webhook->get_status();
                            if ( 'active' == $webhook_status ) {
                                $webhook->set_status( 'paused' );
                                $webhook->save();
                            }
                        }
                    }
                    update_option( 'ced_ebay_inventory_scheduler_job_' . $this->ebay_user, $this->ebay_user );
                }
        
                if ( ! empty( $order_schedule ) && 'on' == $order_schedule && function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_unschedule_all_actions' ) && function_exists( 'as_schedule_recurring_action' ) ) {
                    if ( as_has_scheduled_action( 'ced_ebay_order_scheduler_job' ) ) {
                        as_unschedule_all_actions( 'ced_ebay_order_scheduler_job' );
                    }
                    as_schedule_recurring_action( time(), 360, 'ced_ebay_order_scheduler_job', array( 'data' => $scheduler_args ), 'ced_ebay_order_scheduler_group' );
                    update_option( 'ced_ebay_order_scheduler_job', 'active' );
                }
        
                if ( ! empty( $sync_ended_listings_scheduler ) && 'on' == $sync_ended_listings_scheduler && function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_unschedule_all_actions' ) && function_exists( 'as_schedule_recurring_action' ) ) {
                    if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_unschedule_all_actions' ) ) {
                        if ( as_has_scheduled_action( null, null, 'ced_ebay_sync_ended_listings_group_' . $this->ebay_user . '>' . $this->ebay_site ) ) {
                            as_unschedule_all_actions( null, null, 'ced_ebay_sync_ended_listings_group_' . $this->ebay_user . '>' . $this->ebay_site );
                        }
                    }
                    as_schedule_recurring_action( time(), 360, 'ced_ebay_sync_ended_listings_scheduler_job_' . $this->ebay_user, array( 'data' => $scheduler_args ), 'ced_ebay_sync_ended_listings_group_' . $this->ebay_user . '>' . $this->ebay_site );
                    update_option( 'ced_ebay_sync_ended_listings_scheduler_job_' . $this->ebay_user, 'active' );
                }
        
                if ( ! empty( $existing_product_sync ) && 'on' == $existing_product_sync ) {
                    if ( wp_next_scheduled( 'ced_ebay_existing_products_sync_job_' . $this->ebay_user, $scheduler_args ) ) {
                        wp_clear_scheduled_hook( 'ced_ebay_existing_products_sync_job_' . $this->ebay_user, $scheduler_args );
                    }
                    wp_schedule_event( time(), 'ced_ebay_6min', 'ced_ebay_existing_products_sync_job_' . $this->ebay_user, $scheduler_args );
                    update_option( 'ced_ebay_existing_products_sync_job_' . $this->ebay_user, $this->ebay_user );
                }

                if ( ! empty( $import_products_schedule ) && 'on' == $import_products_schedule && function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_unschedule_all_actions' ) && function_exists( 'as_schedule_recurring_action' ) ) {

              
                    if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_unschedule_all_actions' ) ) {
                        if ( as_has_scheduled_action( null, null, 'ced_ebay_product_importer_' . $this->ebay_user  ) ) {
                            as_unschedule_all_actions( null, null, 'ced_ebay_product_importer_' . $this->ebay_user );
                        }

                        as_schedule_recurring_action( time(), 360, 'ced_ebay_import_products_action', array( 'data' => $scheduler_args ), 'ced_ebay_product_importer_' . $this->ebay_user );
                    }

                    
                }
                
                
                $admin_success_notice = '<div class="notice notice-success"> <p>Your configuration has been saved! </p></div>';
                print_r( $admin_success_notice );
            } elseif ( isset( $_post_data['reset_global_settings'] ) ) {
                delete_option( 'ced_ebay_global_settings' );
                $admin_success_notice = '<div class="notice notice-success">
              <p>Your configuration has been Reset!</p></div>';
                print_r( $admin_success_notice );
            }

        }
        public function render_general_settings(){
            $this->global_settings = get_option( 'ced_ebay_global_settings', false );
            include_once CED_EBAY_DIRPATH . 'admin/partials/settings-view/general_settings.php';

        }

        public function render_business_policies(){
            $this->global_settings = get_option( 'ced_ebay_global_settings', false );
            include_once CED_EBAY_DIRPATH . 'admin/partials/settings-view/business_policies.php';
        }

        public function render_global_options(){
            $this->global_options             = ! empty( get_option( 'ced_ebay_global_options' ) ) ? get_option( 'ced_ebay_global_options', array() ) : array();
            include_once CED_EBAY_DIRPATH . 'admin/partials/settings-view/global_options.php';

        }

        public function render_advanced_settings(){
            $this->global_settings = get_option( 'ced_ebay_global_settings', false );
            include_once CED_EBAY_DIRPATH . 'admin/partials/settings-view/advanced_settings.php';

        }
        
        private function trimKeysRecursive($array_to_trim){
            $trimmedArray = array();
            foreach ( $array_to_trim as $key => $value ) {
                $trimmedKey = trim( $key );
                if ( is_array( $value ) ) {
                    $trimmedArray[ $trimmedKey ] = $this->trimKeysRecursive( $value );
                } else {
                    $trimmedArray[ $trimmedKey ] = $value;
                }
            }
            return $trimmedArray;
        }

       
        
}
}