<?php
namespace Ced\Ebay;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SCHEDULE_MANAGER' ) ) {

    class SCHEDULE_MANAGER {
        private $loadInstance;

        private function loadDependency($file, $className) {
			if ( file_exists( $file ) ) {
				require_once $file;
                $this->loadInstance = $className::get_instance();
			}
		}

        public function ced_ebay_order_schedule_manager($data = []) {
            $fetchCurrentAction = current_action();
            if ( strpos( $fetchCurrentAction, 'wp_ajax_nopriv_' ) !== false ) {
                $user_id = isset( $_GET['user_id'] ) ? wc_clean( $_GET['user_id'] ) : false;
                $siteID = isset( $_GET['site_id'] ) ? wc_clean( $_GET['site_id'] ) : false;
            } else {
                $user_id = isset( $data['user_id'] ) ? $data['user_id'] : '';
                $siteID = isset( $data['site_id'] ) ? $data['site_id'] : '';
            }
            $rsid = ced_ebay_get_shop_data( $user_id, $siteID );
            if(empty($rsid)){
                return new WP_Error('invalid_rsid', 'Unable to fetch rsid');
            }
            $logger    = wc_get_logger();
            $context   = array( 'source' => 'ced_ebay_order_schedule_manager' );
        
            $list_offset = ! empty( get_option( 'ced_ebay_order_fetch_offset_' . $user_id . '>' . $siteID ) ) ? get_option( 'ced_ebay_order_fetch_offset_' . $user_id . '>' . $siteID ) : 0;
            
            if ( isset( $ebay_orders_data['total'] ) && $ebay_orders_data['total'] > 0 ) {
                if ( ! empty( $ebay_orders_data['orders'] ) && is_array( $ebay_orders_data['orders'] ) ) {
                    $list_offset = $list_offset + 10;
                    update_option( 'ced_ebay_order_fetch_offset_' . $user_id . '>' . $siteID, $list_offset );
                    $ebay_orders = array();
                    $ebay_orders = $ebay_orders_data['orders'];
                    foreach ( $ebay_orders as $key => $ebay_order ) {
                        $order_id      = '';
                        $ebay_order_id = $ebay_order['orderId'];
                        if ( ! empty( $ebay_order_id ) && 'PAID' == $ebay_order['orderPaymentStatus'] ) {
                            $args         = array(
                                'post_type'   => 'shop_order',
                                'post_status' => 'wc-processing',
                                'numberposts' => 1,
                                'meta_query'  => array(
                                    'relation' => 'OR',
                                    array(
                                        'key'     => '_ced_ebay_order_id',
                                        'value'   => $ebay_order_id,
                                        'compare' => '=',
                                    ),
                                    array(
                                        'key'     => '_ebay_order_id',
                                        'value'   => $ebay_order_id,
                                        'compare' => '=',
                                    ),
                                ),
                            );
                            $order        = get_posts( $args );
                            $order_id_arr = wp_list_pluck( $order, 'ID' );
                            if ( ! empty( $order_id_arr ) ) {
                                $order_id = $order_id_arr[0];
                            }
                            if ( $order_id ) {
                                $logger->info( wc_print_r( 'Order ' . $ebay_order_id . ' with WooCommerce order ID ' . $order_id . ' already exists.', true ), $context );
                                continue;
                            }
                            $data = array(
                                'order_id' => $ebay_order_id,
                                'user_id'  => $user_id,
                            );
                            $this->schedule_order_task->push_to_queue( $data );
    
                        } else {
                            $logger->info( wc_print_r( 'Skipping order ' . $ebay_order_id, true ), $context );
                            if ( 'PAID' != $ebay_order['orderPaymentStatus'] ) {
                                $logger->info( wc_print_r( 'PAYMENT STATUS - ' . $ebay_order['orderPaymentStatus'], true ), $context );
                            }
                            continue;
                        }
                    }
                    $this->schedule_order_task->save()->dispatch();
                } else {
                    $logger->info( 'End of orders. Resetting offset!', $context );
                    update_option( 'ced_ebay_order_fetch_offset_' . $user_id . '>' . $siteID, 0 );
                }
            } else {
                $logger->info( 'End of totals. Resetting offset!', $context );
                update_option( 'ced_ebay_order_fetch_offset_' . $user_id . '>' . $siteID, 0 );
            }
        }

        public function ced_ebay_inventory_schedule_manager( $data = array() ) {
            $fetchCurrentAction = current_action();
            if ( strpos( $fetchCurrentAction, 'wp_ajax_nopriv_' ) !== false ) {
                $user_id = isset( $_GET['user_id'] ) ? wc_clean( $_GET['user_id'] ) : false;
            } else {
                $user_id = isset( $data['user_id'] ) ? $data['user_id'] : '';
                $siteID = isset( $data['site_id'] ) ? $data['site_id'] : '';
            }
            $rsid = ced_ebay_get_shop_data( $user_id, $siteID );
            if(empty($rsid)){
                return new WP_Error('invalid_rsid', 'Unable to fetch rsid');
            }
            $logger           = wc_get_logger();
            $context          = array( 'source' => 'ced_ebay_inventory_schedule_manager' );
            
        
            $file             = CED_EBAY_DIRPATH . 'admin/ebay/class-ebay.php';
            $renderDependency = $this->renderDependency( $file );
            if ( $renderDependency ) {
                $cedeBay           = new Class_Ced_EBay_Manager();
                $cedebayInstance   = $cedeBay->get_instance();
                $seller_prefrences = $cedebayInstance->ced_ebay_get_seller_preferences( $user_id, $token, $siteID );
            }
    
            $products_to_update = get_option( 'ced_eBay_update_chunk_product_' . $user_id . '>' . $siteID, array() );
            if ( empty( $products_to_update ) ) {
                $logger->info( '#####Commencing Stock Update#####', $context );
                $store_products = get_posts(
                    array(
                        'numberposts' => -1,
                        'post_type'   => 'product',
                        'post_status' => 'publish',
                        'fields'      => 'ids',
                        'meta_query'  => array(
                            array(
                                'key'     => '_ced_ebay_listing_id_' . $user_id . '>' . $siteID,
                                'compare' => 'EXISTS',
                            ),
                        ),
                    )
                );
                update_option(
                    'ced_ebay_stock_sync_progress_' . $user_id . '>' . $siteID,
                    array(
                        'total_count' => count( $store_products ),
                        'synced'      => 0,
                    )
                );
                $products_to_update = array_chunk( $store_products, 50 );
            }
            if ( is_array( $products_to_update[0] ) && ! empty( $products_to_update[0] ) ) {
                $prodIDs = $products_to_update[0];
                foreach ( $products_to_update[0] as $key => $value ) {
                    $product_actual_stock = get_post_meta( $value, '_stock', true );
                    $manage_stock         = get_post_meta( $value, '_manage_stock', true );
                    $product_status       = get_post_meta( $value, '_stock_status', true );
                    if ( 'yes' != $manage_stock && 'instock' == $product_status ) {
                        $renderDataOnGlobalSettings = get_option( 'ced_ebay_global_settings', false );
                        $default_stock              = isset( $renderDataOnGlobalSettings[ $user_id ][ $siteID ]['ced_ebay_product_default_stock'] ) ? $renderDataOnGlobalSettings[ $user_id ][ $siteID ]['ced_ebay_product_default_stock'] : 0;
                        $product_actual_stock       = $default_stock;
                    }
    
                    $async_action_id = as_enqueue_async_action(
                        'ced_ebay_async_update_stock_action',
                        array(
                            'data' => array(
                                'product_id' => $value,
                                'user_id'    => $user_id,
                                'site_id'    => $siteID,
                            ),
                        ),
                        'ced_ebay_inventory_scheduler_group_' . $user_id . '>' . $siteID
                    );
                    if ( $async_action_id ) {
                        $logger->info( wc_print_r( 'Product ID ' . $value, true ), $context );
                        $logger->info( wc_print_r( 'Async Stock Update Scheduled with ID ' . $async_action_id, true ), $context );
                    }
                }
                unset( $products_to_update[0] );
                $products_to_update = array_values( $products_to_update );
                update_option( 'ced_eBay_update_chunk_product_' . $user_id . '>' . $siteID, $products_to_update );
                $logger->info( '--------------------------------', $context );
    
            }
        }

        public function ced_ebay_existing_products_sync_manager( $user_id = '', $site_id = '' ) {
            $logger = wc_get_logger();
            $context            = array( 'source' => 'ebay-existing-products-sync' );
            $fetchCurrentAction = current_action();
            if ( strpos( $fetchCurrentAction, 'wp_ajax_nopriv_' ) !== false ) {
                $user_id = isset( $_GET['user_id'] ) ? wc_clean( $_GET['user_id'] ) : '';
                $site_id = isset( $_GET['sid'] ) ? wc_clean( $_GET['sid'] ) : '';
            }
    
            $rsid_data       = ced_ebay_get_shop_data( $user_id, $site_id );
            if ( empty( $rsid_data ) || !isset($rsid_data['remote_shop_id']) ) {
                return new WP_Error('rsid_missing', 'Invalid rsid');
            } else {
                $rsid = $rsid_data['remote_shop_id'];
            }
            $page = ! empty( get_option( 'ced_ebay_get_products_page_' . $user_id . '>' . $site_id ) ) ? get_option( 'ced_ebay_get_products_page_' . $user_id . '>' . $site_id ) : 1;
            if ( empty( $page ) ) {
                $page = 1;
            }
            
            require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
            $ebayUploadInstance = \EbayUpload::get_instance( $rsid );
            $activelist         = $ebayUploadInstance->get_active_products($page);
            $logger->info( wc_print_r( '>>>>>> Page Number ' . $page . '/' . $activelist['ActiveList']['PaginationResult']['TotalNumberOfPages'] . ' <<<<<<<<', true ), $context );
            if ( ! empty( $activelist['ActiveList']['PaginationResult']['TotalNumberOfPages'] ) ) {
                if ( $page > $activelist['ActiveList']['PaginationResult']['TotalNumberOfPages'] ) {
                    $logger->info( 'Reached end of list. Resetting page.', $context );
                    update_option( 'ced_ebay_get_products_page_' . $user_id . '>' . $siteID, 1 );
                    return;
                }
            }
    
            if ( isset( $activelist['ActiveList']['ItemArray']['Item'] ) && ! empty( $activelist['ActiveList']['ItemArray']['Item'] ) ) {
                if ( ! empty( $activelist['ActiveList']['ItemArray']['Item'] ) && ! isset( $activelist['ActiveList']['ItemArray']['Item'][0] ) ) {
                    $temp = $activelist['ActiveList']['ItemArray']['Item'];
                    unset( $activelist['ActiveList']['ItemArray']['Item'] );
                    $activelist['ActiveList']['ItemArray']['Item'][] = $temp;
                }
                foreach ( $activelist['ActiveList']['ItemArray']['Item'] as $item_value ) {
                    $ItemID = $item_value['ItemID'];
                    if ( isset( $item_value['Variations']['Variation'] ) && ! empty( $item_value['Variations']['Variation'] ) ) {
                        if ( ! isset( $item_value['Variations']['Variation'][0] ) ) {
                            $temp = $item_value['Variations']['Variation'];
                            unset( $item_value['Variations']['Variation'] );
                            $item_value['Variations']['Variation'][] = $temp;
                        }
                        foreach ( $item_value['Variations']['Variation'] as $key => $value ) {
                            $eby_sku_var = ! empty( $value['SKU'] ) ? $value['SKU'] : '';
                            if ( isset( $eby_sku_var ) && ! empty( $eby_sku_var ) ) {
                                $args           = array(
                                    'post_type'  => 'product_variation',
                                    'meta_query' => array(
                                        array(
                                            'key'   => '_sku',
                                            'value' => $eby_sku_var,
                                        ),
                                    ),
                                );
                                $variation_post = get_posts( $args );
                                if ( ! empty( $variation_post ) ) {
                                    $variation_post_id = $variation_post[0]->ID;
                                    $logger->info( wc_print_r( $eby_sku_var, true ) . ' (eBay SKU) ->  ' . wc_print_r( $ItemID, true ) . ' (eBay Item ID) -> ' . wc_print_r( $variation_post_id, true ) . ' (Woo Variation ID) ', $context );
                                    if ( isset( $variation_post_id ) && ! empty( $variation_post_id ) ) {
                                        update_post_meta( $variation_post_id, '_ced_ebay_listing_id_' . $user_id . '>' . $site_id, $ItemID );
                                        $var_product = wc_get_product( $variation_post_id );
    
                                        $parent_id = $var_product->get_parent_id();
                                        if ( isset( $parent_id ) && ! empty( $parent_id ) ) {
                                            update_post_meta( $parent_id, '_ced_ebay_listing_id_' . $user_id . '>' . $site_id, $ItemID );
                                            update_post_meta( $parent_id, 'ced_ebay_synced_by_user_id', $user_id );
    
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        $eby_sku_simple = ! empty( $item_value['SKU'] ) ? $item_value['SKU'] : '';
                        if ( isset( $eby_sku_simple ) && ! empty( $eby_sku_simple ) ) {
                            $args        = array(
                                'post_type'    => 'product',
                                'post_status'  => 'publish',
                                'numberposts'  => -1,
                                'meta_key'     => '_sku',
                                'meta_value'   => $eby_sku_simple,
                                'meta_compare' => '=',
                            );
                            $woo_product = get_posts( $args );
                            $woo_product = wp_list_pluck( $woo_product, 'ID' );
                            if ( ! empty( $woo_product ) ) {
                                $simple_post_id = $woo_product[0];
                            } else {
                                continue;
                            }
                            if ( ! empty( $simple_post_id ) ) {
                                $logger->info( wc_print_r( $eby_sku_simple, true ) . ' (eBay SKU) ->  ' . wc_print_r( $ItemID, true ) . ' (eBay Item ID) -> ' . wc_print_r( $simple_post_id, true ) . ' (Woo Product ID) ', $context );
                                update_post_meta( $simple_post_id, '_ced_ebay_listing_id_' . $user_id . '>' . $site_id, $ItemID );
                                update_post_meta( $simple_post_id, 'ced_ebay_synced_by_user_id', $user_id );
    
                            }
                        }
                    }
                }
                ++$page;
                update_option( 'ced_ebay_get_products_page_' . $user_id . '>' . $site_id, $page );
            } else {
                $page = 0;
                update_option( 'ced_ebay_get_products_page_' . $user_id . '>' . $site_id, $page );
            }
        }

        public function ced_ebay_manually_ended_listings_manager( $data = array() ) {
            $fetchCurrentAction = current_action();
            if ( strpos( $fetchCurrentAction, 'wp_ajax_nopriv_' ) !== false ) {
                $user_id = isset( $_GET['user_id'] ) ? wc_clean( $_GET['user_id'] ) : false;
                $site_id = isset( $_GET['sid'] ) ? wc_clean( $_GET['sid'] ) : false;
            } else {
                $user_id = isset( $data['user_id'] ) ? $data['user_id'] : '';
                $site_id = isset( $data['sid'] ) ? $data['sid'] : '';
            }
    
            $logger  = wc_get_logger();
            $context = array( 'source' => 'ced_ebay_manually_ended_listings_manager' );
            $logger->info( '>>>Commencing scheduled job', $context );
            $shop_data = ced_ebay_get_shop_data( $user_id, $site_id );
            
            $file             = CED_EBAY_DIRPATH . 'admin/ebay/class-ebay.php';
            $renderDependency = $this->renderDependency( $file );
            if ( $renderDependency ) {
                $cedeBay             = new Class_Ced_EBay_Manager();
                $cedebayInstance     = $cedeBay->get_instance();
                $page_number         = ! empty( get_option( 'ced_ebay_ended_listings_pagination_' . $user_id . '>' . $siteID ) ) ? get_option( 'ced_ebay_ended_listings_pagination_' . $user_id . '>' . $siteID, true ) : 1;
                $ended_products_list = $cedebayInstance->ced_ebay_get_manually_ended_listings( $token, $siteID, $page_number );
                if ( 'api-error' != $ended_products_list ) {
                    $logger->info( wc_print_r( 'Page Number ' . $page_number, true ), $context );
                    ++$page_number;
                    update_option( 'ced_ebay_ended_listings_pagination_' . $user_id . '>' . $siteID, $page_number );
                    if ( ! empty( $ended_products_list['ItemArray']['Item'] ) ) {
                        if ( ! isset( $ended_products_list['ItemArray']['Item'][0] ) ) {
                            $temp_ended_list = array();
                            $temp_ended_list = $ended_products_list['ItemArray']['Item'];
                            unset( $ended_products_list['ItemArray']['Item'] );
                            $ended_products_list['ItemArray']['Item'][] = $temp_ended_list;
                        }
                        foreach ( $ended_products_list['ItemArray']['Item'] as $key => $ended_ebay_listing ) {
                            if ( ! empty( $ended_ebay_listing['ItemID'] ) && isset( $ended_ebay_listing['ListingDetails']['EndingReason'] ) ) {
    
                                $logger->info( wc_print_r( 'Listing ' . $ended_ebay_listing['ItemID'] . ' has been manually ended on eBay', true ), $context );
                                if ( isset( $ended_ebay_listing['ListingDetails']['RelistedItemID'] ) && ! empty( $ended_ebay_listing['ListingDetails']['RelistedItemID'] ) ) {
                                    $logger->info( wc_print_r( 'Listing ' . $ended_ebay_listing['ItemID'] . ' has been relisted on eBay', true ), $context );
                                }
                                $itemId        = $ended_ebay_listing['ItemID'];
                                $store_product = get_posts(
                                    array(
                                        'numberposts'  => -1,
                                        'post_type'    => 'product',
                                        'meta_key'     => '_ced_ebay_listing_id_' . $user_id . '>' . $siteID,
                                        'meta_value'   => $itemId,
                                        'meta_compare' => '=',
                                    )
                                );
                                $store_product = wp_list_pluck( $store_product, 'ID' );
                                if ( ! empty( $store_product ) ) {
                                    $product_id = $store_product[0];
                                    $logger->info( wc_print_r( 'Found Woo Product ' . $product_id, true ), $context );
                                    $product = wc_get_product( $product_id );
                                    $product->set_stock_quantity( '0' );
                                    $product->set_stock_status( 'outofstock' );
                                    $product->save();
                                    delete_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
                                    delete_post_meta( $product_id, '_ced_ebay_relist_item_id_' . $user_id . '>' . $siteID );
                                    if ( isset( $ended_ebay_listing['ListingDetails']['RelistedItemID'] ) && ! empty( $ended_ebay_listing['ListingDetails']['RelistedItemID'] ) ) {
                                        $logger->info( wc_print_r( 'Relisted item ID is ' . $ended_ebay_listing['ListingDetails']['RelistedItemID'], true ), $context );
                                        update_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, $ended_ebay_listing['ListingDetails']['RelistedItemID'] );
                                        update_post_meta( $product_id, '_ced_ebay_relist_item_id_' . $user_id . '>' . $siteID, $ended_ebay_listing['ListingDetails']['RelistedItemID'] );
                                    }
                                    $product->save();
                                }
                            }
                        }
                    } else {
                        $logger->info( 'Unable to get ItemArray', $context );
                        update_option( 'ced_ebay_ended_listings_pagination_' . $user_id . '>' . $siteID, 1 );
                    }
                } else {
                    $logger->info( 'API Error', $context );
                    update_option( 'ced_ebay_ended_listings_pagination_' . $user_id . '>' . $siteID, 1 );
                }
            }
        }
    }
}
