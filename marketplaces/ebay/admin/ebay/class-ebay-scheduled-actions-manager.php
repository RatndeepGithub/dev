<?php


namespace Ced\Ebay;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SCHEDULED_ACTIONS_MANAGER' ) ) {

    class SCHEDULED_ACTIONS_MANAGER {

        private $loadInstance;

        private $schedule_import_task;

        private function loadDependency($file, $className) {
			if ( file_exists( $file ) ) {
				require_once $file;
                $this->loadInstance = $className::get_instance();
			}
		}

        public function ced_ebay_async_update_stock_action_manager( $stock_update_data, $log_file = '' ) {
            $synced = 0;
            if ( ! empty( $stock_update_data ) && ! empty( $stock_update_data['product_id'] ) && ! empty( $stock_update_data['user_id'] ) && is_array( $stock_update_data ) ) {
                $product_id = $stock_update_data['product_id'];
                $user_id    = $stock_update_data['user_id'];
                $site_id    = $stock_update_data['site_id'];
                $woo_prd    = wc_get_product( $product_id );
                ced_ebay_log_data( 'eBay User ID ' . $user_id, 'ced_ebay_async_update_stock_callback', $log_file );
                ced_ebay_log_data( 'Product ID ' . $product_id, 'ced_ebay_async_update_stock_callback', $log_file );
                $ced_ebay_manager = $this->ced_ebay_manager;
                $shop_data        = ced_ebay_get_shop_data( $user_id, $site_id );
                if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
                    $siteID      = $site_id;
                    $token       = $shop_data['access_token'];
                    $getLocation = $shop_data['location'];
                } else {
                    return false;
                }
                $pre_flight_check = ced_ebay_pre_flight_check( $user_id, $siteID );
                if ( ! $pre_flight_check ) {
                    return false;
                }
                require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
                $ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
                $already_uploaded   = get_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
                if ( ! $already_uploaded ) {
                    ced_ebay_log_data( 'Product is not an eBay listing. Returning!', 'ced_ebay_async_update_stock_callback', $log_file );
                    return;
                }
                if ( $already_uploaded ) {
                    $itemIDs[ $product_id ] = $already_uploaded;
                    require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
                    $ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
                    $check_stauts_xml   = '
                    <?xml version="1.0" encoding="utf-8"?>
                    <GetItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                    <RequesterCredentials>
                    <eBayAuthToken>' . $token . '</eBayAuthToken>
                    </RequesterCredentials>
                    <DetailLevel>ReturnAll</DetailLevel>
                    <ItemID>' . $already_uploaded . '</ItemID>
                    </GetItemRequest>';
                    $itemDetails        = $ebayUploadInstance->get_item_details( $check_stauts_xml );
                    if ( 'Success' == $itemDetails['Ack'] || 'Warning' == $itemDetails['Ack'] ) {
    
                        if ( isset( $itemDetails['Item']['ListingDetails']['RelistedItemID'] ) ) {
                            update_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, $itemDetails['Item']['ListingDetails']['RelistedItemID'] );
                            update_post_meta( $product_id, '_ced_ebay_relist_item_id_' . $user_id, $already_uploaded );
                        }
                    }
                }
                if ( 'false' === get_option( 'ced_ebay_out_of_stock_preference_' . $user_id, true ) ) {
                    $file             = CED_EBAY_DIRPATH . 'admin/ebay/class-ebay.php';
                    $renderDependency = $this->renderDependency( $file );
                    if ( $renderDependency ) {
                        $cedeBay               = new Class_Ced_EBay_Manager();
                        $cedebayInstance       = $cedeBay->get_instance();
                        $check_if_out_of_stock = $cedebayInstance->ced_ebay_check_out_of_stock_product( $user_id, $product_id );
                        if ( ! $check_if_out_of_stock ) {
                            ced_ebay_log_data( 'Product ID ' . $product_id . ' is out of stock on WooCommerce!', 'ced_ebay_async_update_stock_callback', $log_file );
                            $already_uploaded = get_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
                            if ( $already_uploaded ) {
                                $itemIDs[ $product_id ] = $already_uploaded;
                                if ( 'Success' == $itemDetails['Ack'] || 'Warning' == $itemDetails['Ack'] ) {
                                    if ( ! empty( $itemDetails['Item']['ListingDetails']['EndingReason'] ) || 'Completed' == $itemDetails['Item']['SellingStatus']['ListingStatus'] ) {
                                        delete_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
                                        delete_post_meta( $product_id, '_ced_ebay_relist_item_id_' . $user_id . '>' . $siteID );
                                        global $wpdb;
                                        $remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id`=%s", $product_id, $user_id, $siteID ) );
                                        ced_ebay_log_data( 'eBay ID ' . $already_uploaded . ' has been ended from eBay. Resetting product.', 'ced_ebay_async_update_stock_callback', $log_file );
                                    }
                                } elseif ( 'Failure' == $itemDetails['Ack'] ) {
                                    if ( ! empty( $itemDetails['Errors']['ErrorCode'] ) && '17' == $itemDetails['Errors']['ErrorCode'] ) {
                                        delete_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
                                        delete_post_meta( $product_id, '_ced_ebay_relist_item_id_' . $user_id . '>' . $siteID );
                                        global $wpdb;
                                        $remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id`=%s", $product_id, $user_id, $siteID ) );
                                        ced_ebay_log_data( 'eBay ID ' . $already_uploaded . ' error code 17. Resetting product.', 'ced_ebay_async_update_stock_callback', $log_file );
                                    }
                                }
    
                                $archiveProducts = $ebayUploadInstance->endItems( $itemIDs );
                                if ( is_array( $archiveProducts ) && ! empty( $archiveProducts ) ) {
                                    if ( isset( $archiveProducts['Ack'] ) ) {
                                        if ( 'Warning' == $archiveProducts['Ack'] || 'Success' == $archiveProducts['Ack'] ) {
                                            delete_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
                                            delete_post_meta( $product_id, '_ced_ebay_relist_item_id_' . $user_id . '>' . $siteID );
                                            global $wpdb;
                                            $remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id` = %s", $product_id, $user_id, $siteID ) );
                                            ced_ebay_log_data( 'eBay ID ' . $already_uploaded . ' Removed from eBay successfully', 'ced_ebay_async_update_stock_callback', $log_file );
                                        } else {
                                            if ( 1047 == $archiveProducts['EndItemResponseContainer']['Errors']['ErrorCode'] ) {
                                                delete_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
                                                delete_post_meta( $product_id, '_ced_ebay_relist_item_id_' . $user_id . '>' . $siteID );
                                                global $wpdb;
                                                $remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id` = %s", $product_id, $user_id, $siteID ) );
                                            }
                                            $endResponse = $archiveProducts['EndItemResponseContainer']['Errors']['LongMessage'];
                                            ced_ebay_log_data( 'eBay ID ' . $already_uploaded . ' ' . $endResponse, 'ced_ebay_async_update_stock_callback', $log_file );
                                        }
                                    }
                                }
                            }
                            ced_ebay_log_data( '----------------', 'ced_ebay_async_update_stock_callback', $log_file );
                            return;
                        }
                    }
                }
                $SimpleXml = $ced_ebay_manager->prepareProductHtmlForUpdateStock( $user_id, $siteID, $product_id );
                if ( is_array( $SimpleXml ) && ! empty( $SimpleXml ) ) {
                    $stock_sync_progress = get_option( 'ced_ebay_stock_sync_progress_' . $user_id . '>' . $siteID );
                    if ( ! empty( $stock_sync_progress ) ) {
                        $synced      = $stock_sync_progress['synced'];
                        $total_count = $stock_sync_progress['total_count'];
                        ++$synced;
                        update_option(
                            'ced_ebay_stock_sync_progress_' . $user_id . '>' . $siteID,
                            array(
                                'total_count' => $total_count,
                                'synced'      => $synced,
                            )
                        );
                    }
                    ced_ebay_log_data( 'Product has more than 4 variations.', 'ced_ebay_async_update_stock_callback', $log_file );
                    foreach ( $SimpleXml as $key => $value ) {
                        $uploadOnEbay[] = $ebayUploadInstance->cedEbayUpdateInventory( $value );
    
                    }
                } else {
                    $stock_sync_progress = get_option( 'ced_ebay_stock_sync_progress_' . $user_id . '>' . $siteID );
                    if ( ! empty( $stock_sync_progress ) ) {
                        $synced      = $stock_sync_progress['synced'];
                        $total_count = $stock_sync_progress['total_count'];
                        ++$synced;
                        update_option(
                            'ced_ebay_stock_sync_progress_' . $user_id . '>' . $siteID,
                            array(
                                'total_count' => $total_count,
                                'synced'      => $synced,
                            )
                        );
                    }
                    $uploadOnEbay = $ebayUploadInstance->cedEbayUpdateInventory( $SimpleXml );
    
                    if ( ! empty( $uploadOnEbay ) ) {
                        if ( 'Failure' == $uploadOnEbay['Ack'] ) {
                            $temp_inv_update_error = array();
                            if ( ! isset( $uploadOnEbay['Errors'][0] ) ) {
                                $temp_inv_update_error = $uploadOnEbay['Errors'];
                                unset( $uploadOnEbay['Errors'] );
                                $uploadOnEbay['Errors'][] = $temp_inv_update_error;
                            }
                            if ( ! empty( $uploadOnEbay['Errors'] ) ) {
                                foreach ( $uploadOnEbay['Errors'] as $key => $ebay_api_error ) {
                                    if ( '21916750' == $ebay_api_error['ErrorCode'] ) {
                                        delete_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
                                        delete_post_meta( $product_id, '_ced_ebay_relist_item_id_' . $user_id );
                                        global $wpdb;
                                        $remove_from_bulk_upload_logs = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `product_id` IN (%d) AND `user_id` = %s AND `site_id`=%s", $product_id, $user_id, $siteID ) );
                                        ced_ebay_log_data( 'Stock sync unable to update the listing ' . $already_uploaded . ' since it is removed from eBay!', 'ced_ebay_async_update_stock_callback' );
                                    }
                                    if ( '518' == $ebay_api_error['ErrorCode'] ) {
                                        if ( function_exists( 'as_unschedule_all_actions' ) ) {
                                            as_unschedule_all_actions( 'ced_ebay_inventory_scheduler_job_' . $user_id );
                                        }
                                        if ( function_exists( 'as_get_scheduled_actions' ) ) {
                                            $has_action = as_get_scheduled_actions(
                                                array(
                                                    'group'  => 'ced_ebay_inventory_scheduler_' . $user_id,
                                                    'status' => \ActionScheduler_Store::STATUS_PENDING,
                                                ),
                                                'ARRAY_A'
                                            );
                                        }
                                        if ( ! empty( $has_action ) ) {
                                            if ( function_exists( 'as_unschedule_all_actions' ) ) {
                                                $unschedule_actions = as_unschedule_all_actions( null, null, 'ced_ebay_inventory_scheduler_' . $user_id );
    
                                                ced_ebay_log_data( 'Call usage limit reached. Unscheduling all inventory syncing actions.', 'ced_ebay_async_update_stock_callback', $log_file );
                                                continue;
                                            }
                                        }
                                    }
    
                                    if ( '231' == $ebay_api_error['ErrorCode'] || '21916750' == $ebay_api_error['ErrorCode'] ) {
                                        $error_code      = $ebay_api_error['ErrorCode'];
                                        $ebay_listing_id = get_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
                                        ced_ebay_log_data( 'Error code ' . $error_code . '. eBay item ' . $ebay_listing_id . ' no longer exists on eBay. Removing from Woo.', 'ced_ebay_async_update_stock_callback', $log_file );
                                        delete_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID );
                                        delete_post_meta( $product_id, '_ced_ebay_relist_item_id_' . $user_id );
                                    }
    
                                    if ( '21919474' == $ebay_api_error['ErrorCode'] || 21919474 == $ebay_api_error['ErrorCode'] ) {
                                        $status = $this->ced_ebay_update_stock_using_inventory_api( $user_id, $siteID, $token, $product_id );
                                        if ( 200 == $status || '200' == $status ) {
                                            $logger->info( 'Initiating Product update through Inventory API call', $context );
                                        } elseif ( 400 == $status || '400' == $status ) {
                                            $logger->info( 'Failure error code, Operation not allowed', $context );
                                        }
                                    }
                                }
                            }
                        }
                    }
                    ced_ebay_log_data( $uploadOnEbay, 'ced_ebay_async_update_stock_callback', $log_file );
                }
    
                ced_ebay_log_data( '----------------', 'ced_ebay_async_update_stock_callback', $log_file );
    
            } else {
                ced_ebay_log_data( 'Missing Product ID or User ID', 'ced_ebay_async_update_stock_callback', $log_file );
                return;
            }
        }

        public function ced_ebay_async_bulk_upload_action_manager( $data ) {
            $logger  = wc_get_logger();
            $context = array( 'source' => 'ced_ebay_async_bulk_upload_callback' );
            if ( is_array( $data ) && ! empty( $data ) ) {
                global $wpdb;
                $user_id       = $data['user_id'];
                $site_id       = $data['site_id'];
                $product_id    = $data['product_id'];
                $profile_id    = $data['profile_id']['id'];
                $product_ids   = array();
                $database_data = $wpdb->get_results( $wpdb->prepare( "SELECT `product_id` FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `user_id` = %s AND `site_id` = %s", $user_id, $site_id ) );
                if ( ! empty( $database_data ) ) {
                    foreach ( $database_data as $key => $value ) {
                        array_push( $product_ids, $value->product_id );
                    }
                }
                $ced_ebay_manager = $this->ced_ebay_manager;
                $shop_data        = ced_ebay_get_shop_data( $user_id, $site_id );
                if ( ! empty( $shop_data ) && true === $shop_data['is_site_valid'] ) {
                    $siteID      = $site_id;
                    $token       = $shop_data['access_token'];
                    $getLocation = $shop_data['location'];
                } else {
                    return;
                }
                $scheduler_args = array(
                    'user_id' => $user_id,
                    'site_id' => $site_id,
                );
                $SimpleXml      = $ced_ebay_manager->prepareProductHtmlForUpload( $user_id, $siteID, $product_id );
                if ( is_array( $SimpleXml ) && ! empty( $SimpleXml ) ) {
                    require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
                } else {
                    return false;
                }
                $ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
                $uploadOnEbay       = $ebayUploadInstance->upload( $SimpleXml[0], $SimpleXml[1] );
    
                if ( isset( $uploadOnEbay['Ack'] ) ) {
                    $temp_prd_upload_error = array();
                    if ( ! isset( $uploadOnEbay['Errors'][0] ) ) {
                        $temp_prd_upload_error = $uploadOnEbay['Errors'];
                        unset( $uploadOnEbay['Errors'] );
                        $uploadOnEbay['Errors'][] = $temp_prd_upload_error;
                    }
                    if ( 'Failure' == $uploadOnEbay['Ack'] ) {
                        if ( ! empty( $uploadOnEbay['Errors'] ) ) {
                            foreach ( $uploadOnEbay['Errors'] as $key => $ebay_api_error ) {
                                if ( '518' == $ebay_api_error['ErrorCode'] ) {
                                    if ( function_exists( 'as_get_scheduled_actions' ) ) {
                                        $has_action = as_get_scheduled_actions(
                                            array(
                                                'args'   => array( 'data' => $scheduler_args ),
                                                'group'  => 'ced_ebay_bulk_upload_' . $user_id,
                                                'status' => \ActionScheduler_Store::STATUS_PENDING,
                                            ),
                                            'ARRAY_A'
                                        );
                                    }
                                    if ( ! empty( $has_action ) ) {
                                        if ( function_exists( 'as_unschedule_all_actions' ) ) {
                                            $unschedule_actions = as_unschedule_all_actions( null, null, 'ced_ebay_bulk_upload_' . $user_id, array( 'data' => $scheduler_args ) );
                                            $logger->info( 'Call usage limit reached. Unscheduling all bulk upload actions.', $context );
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
    
                    if ( 'Warning' == $uploadOnEbay['Ack'] || 'Success' == $uploadOnEbay['Ack'] ) {
                        $ebayID = $uploadOnEbay['ItemID'];
                        update_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, $ebayID );
                        $time   = time();
                        $offset = '.000Z';
                        $date   = gmdate( 'Y-m-d', $time ) . 'T' . gmdate( 'H:i:s', $time ) . $offset;
                        if ( ! empty( $product_ids ) && in_array( $product_id, $product_ids ) ) {
                            $table_data = array(
                                'product_id'       => $product_id,
                                'profile_id'       => $profile_id,
                                'operation_status' => 'Uploaded',
                                'user_id'          => (string) $user_id,
                                'site_id'          => (int) $site_id,
                                'error'            => null,
                                'scheduled_time'   => $date,
                                'bulk_action_type' => 'upload',
                            );
    
                            $profileTableName = $wpdb->prefix . 'ced_ebay_bulk_upload';
                            $wpdb->update( $profileTableName, $table_data, array( 'product_id' => $product_id ), array( '%s' ) );
    
                        } else {
                            $table_data       = array(
                                'product_id'       => $product_id,
                                'profile_id'       => $profile_id,
                                'operation_status' => 'Uploaded',
                                'user_id'          => (string) $user_id,
                                'site_id'          => (int) $site_id,
                                'scheduled_time'   => $date,
                                'bulk_action_type' => 'upload',
                            );
                            $profileTableName = $wpdb->prefix . 'ced_ebay_bulk_upload';
                            $wpdb->insert( $profileTableName, $table_data, array( '%s' ) );
                            $bulkId = $wpdb->insert_id;
                        }
                    }
    
                    if ( 'Failure' == $uploadOnEbay['Ack'] ) {
                        if ( ! empty( $uploadOnEbay['Errors'][0] ) ) {
                            $error = array();
                            foreach ( $uploadOnEbay['Errors'] as $key => $value ) {
                                if ( 'Error' == $value['SeverityCode'] ) {
                                    $error_data                               = str_replace( array( '<', '>' ), array( '{', '}' ), $value['LongMessage'] );
                                    $error[ $value['ErrorCode'] ]['message']  = $error_data;
                                    $error[ $value['ErrorCode'] ]['severity'] = $value['SeverityCode'];
    
                                }
                            }
                            $error_json = json_encode( $error );
    
                        } else {
                            $error = array();
                            $error[ $uploadOnEbay['Errors']['ErrorCode'] ]['message']  = str_replace( array( '<', '>' ), array( '{', '}' ), $uploadOnEbay['Errors']['LongMessage'] );
                            $error[ $uploadOnEbay['Errors']['ErrorCode'] ]['severity'] = $uploadOnEbay['Errors']['SeverityCode'];
                            $error_json = json_encode( $error );
    
                        }
                        $time   = time();
                        $offset = '.000Z';
                        $date   = gmdate( 'Y-m-d', $time ) . 'T' . gmdate( 'H:i:s', $time ) . $offset;
                        if ( ! empty( $product_ids ) && in_array( $product_id, $product_ids ) ) {
    
                            $table_data = array(
                                'product_id'       => $product_id,
                                'profile_id'       => $profile_id,
                                'operation_status' => 'Error',
                                'user_id'          => (string) $user_id,
                                'site_id'          => (int) $site_id,
                                'scheduled_time'   => $date,
                                'error'            => $error_json,
                                'bulk_action_type' => 'upload',
                            );
    
                            $profileTableName = $wpdb->prefix . 'ced_ebay_bulk_upload';
                            $wpdb->update( $profileTableName, $table_data, array( 'product_id' => $product_id ), array( '%s' ) );
    
                        } else {
    
                            $table_data = array(
                                'product_id'       => $product_id,
                                'profile_id'       => $profile_id,
                                'operation_status' => 'Error',
                                'user_id'          => (string) $user_id,
                                'site_id'          => (int) $site_id,
                                'scheduled_time'   => $date,
                                'error'            => $error_json,
                                'bulk_action_type' => 'upload',
                            );
    
                            $profileTableName = $wpdb->prefix . 'ced_ebay_bulk_upload';
    
                            $wpdb->insert( $profileTableName, $table_data, array( '%s' ) );
                            $bulkId = $wpdb->insert_id;
                        }
                    }
                }
            }
        }


        public function ced_ebay_async_order_sync_action_manager( $data ) {
            $logger  = wc_get_logger();
            $context = array( 'source' => 'ced_ebay_async_order_sync_manager' );
            if ( is_array( $data ) && ! empty( $data ) ) {
                $user_id       = $data['user_id'];
                $ebay_order_id = $data['ebay_order_id'];
                $shop_data     = ced_ebay_get_shop_data( $user_id );
                if ( ! empty( $shop_data ) ) {
                    $siteID      = $shop_data['site_id'];
                    $token       = $shop_data['access_token'];
                    $getLocation = $shop_data['location'];
                }
                $access_token_arr = get_option( 'ced_ebay_user_access_token' );
                if ( ! empty( $access_token_arr ) ) {
                    foreach ( $access_token_arr as $key => $value ) {
                        $tokenValue = get_transient( 'ced_ebay_user_access_token_' . $key );
                        if ( false === $tokenValue || null == $tokenValue || empty( $tokenValue ) ) {
                            $user_refresh_token = $value['refresh_token'];
                            $this->ced_ebay_save_user_access_token( $key, $user_refresh_token, 'refresh_user_token' );
                        }
                    }
                }
                $pre_flight_check = ced_ebay_pre_flight_check( $user_id );
                if ( ! $pre_flight_check ) {
                    $logger->info( 'Unable to communicate with eBay APIs at the moment!', $context );
                    return;
                }
                $get_saved_ebay_orders = array();
                $get_saved_ebay_orders = get_option( 'ced_ebay_fulfillment_api_order_data_' . $user_id, true );
                if ( ! empty( $get_saved_ebay_orders ) ) {
                    if ( array_search( $ebay_order_id, array_column( $get_saved_ebay_orders, 'orderId' ) ) !== false ) {
                        $data_position = array_search( $ebay_order_id, array_column( $get_saved_ebay_orders, 'orderId' ) );
                        ced_ebay_log_data( 'Async importing Order ' . $ebay_order_id, 'ced_ebay_async_order_sync_manager' );
                        if ( ! empty( $get_saved_ebay_orders[ $data_position ] ) ) {
                            require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayOrders.php';
                            $orderInstance  = EbayOrders::get_instance( $siteID, $token );
                            $create_order   = array();
                            $create_order[] = $get_saved_ebay_orders[ $data_position ];
                            $createOrder    = $orderInstance->create_localOrders( $create_order, $siteID, $user_id );
                            unset( $get_saved_ebay_orders[ $data_position ] );
                            $get_saved_ebay_orders = array_values( $get_saved_ebay_orders );
                            update_option( 'ced_ebay_fulfillment_api_order_data_' . $user_id, $get_saved_ebay_orders );
                        }
                    }
                } else {
                    $logger->info( 'No saved data found.', $context );

                }
            }
        }

        public function ced_ebay_import_products_action_manager( $args ) {
            $this->schedule_import_task = new \Ced\Ebay\ImportBackground_Process();
            $user_id = isset( $args['user_id'] ) ? wc_clean( $args['user_id'] ) : '';
            $siteID = isset( $args['site_id'] ) ? wc_clean( $args['site_id'] ) : '';
            $logger  = wc_get_logger();
            $context = array( 'source' => 'ced-ebay-product-import' );
            $fetchCurrentAction = current_action();
            if ( strpos( $fetchCurrentAction, 'wp_ajax_nopriv_' ) !== false ) {
                $user_id = isset( $_GET['user_id'] ) ? wc_clean( $_GET['user_id'] ) : '';
                $siteID = isset( $_GET['sid'] ) ? wc_clean( $_GET['sid'] ) : '';
            }
            $remote_shop        = ced_ebay_get_shop_data( $user_id, $siteID );
            if ( ! empty( $remote_shop ) && isset($remote_shop['remote_shop_id']) ) {
                $rsid = $remote_shop['remote_shop_id'];
            } else {
                update_option( 'ced_ebay_product_importer_product_error_' . $user_id . '>' . $siteID, 'Invalid remote shop' );
                return false;
            }
    
            $page_number        = get_option( 'ced_ebay_product_import_pagination_' . $user_id . '>' . $siteID ) ? get_option( 'ced_ebay_product_import_pagination_' . $user_id . '>' . $siteID ) : 1;
            $length             = 25;
            
            $ebayUploadInstance = EbayUpload::get_instance( $rsid );
            $activelist         = $ebayUploadInstance->get_active_products( $page_number, $length );
            if ( isset( $activelist['ActiveList']['PaginationResult']['TotalNumberOfPages'] ) && isset( $activelist['ActiveList']['PaginationResult']['TotalNumberOfEntries'] ) && ! empty( $activelist['ActiveList']['PaginationResult']['TotalNumberOfPages'] ) && ! empty( $activelist['ActiveList']['PaginationResult']['TotalNumberOfEntries'] ) ) {
                update_option( 'ced_ebay_product_import_total_pages_' . $user_id . '>' . $siteID, $activelist['ActiveList']['PaginationResult']['TotalNumberOfPages'] );
                update_option( 'ced_ebay_product_import_total_entries_' . $user_id . '>' . $siteID, $activelist['ActiveList']['PaginationResult']['TotalNumberOfEntries'] );
                if ( $page_number > $activelist['ActiveList']['PaginationResult']['TotalNumberOfPages'] ) {
                    $logger->info( 'Reached end of list. Resetting page.', $context );
                    update_option( 'ced_ebay_product_import_pagination_' . $user_id . '>' . $siteID, 1 );
                    update_option( 'ced_ebay_product_importer_product_error_' . $user_id . '>' . $siteID, 'Reached end of list. Resetting page.' );
                    return;
                }
            }
            $total_ebay_listings = 0;
            if ( isset( $activelist['ActiveList']['PaginationResult']['TotalNumberOfEntries'] ) && 0 < $activelist['ActiveList']['PaginationResult']['TotalNumberOfEntries'] ) {
                $total_ebay_listings = $activelist['ActiveList']['PaginationResult']['TotalNumberOfEntries'];
                update_option( 'ced_ebay_total_listings_' . $user_id, $total_ebay_listings );
            }
    
            $meta_key                = '_ced_ebay_importer_listing_id_' . $user_id . '>' . $siteID;
            $alreadyImportedProducts = get_posts(
                array(
                    'meta_query'     => array(
                        array(
                            'key'     => $meta_key,
                            'compare' => 'EXISTS',
                        ),
                    ),
                    'post_type'      => 'product',
                    'posts_per_page' => -1,
                    'post_status'    => 'publish',
                )
            );
    
            if ( ! is_wp_error( $alreadyImportedProducts ) ) {
                $alreadyImportedProductsId       = array();
                $alreadyImportedProductsId       = wp_list_pluck( $alreadyImportedProducts, 'ID' );
                $currentImportedProductsProgress = get_option( 'ced_ebay_product_importer_product_progress_' . $user_id . '>' . $siteID );
                if ( ! empty( $alreadyImportedProductsId ) && is_array( $alreadyImportedProductsId ) && ! empty( $currentImportedProductsProgress ) ) {
                    update_option( 'ced_ebay_product_importer_product_progress_' . $user_id . '>' . $siteID, count( $alreadyImportedProductsId ) );
                }
            }
    
            if ( isset( $activelist['ActiveList']['ItemArray']['Item'][0] ) ) {
                $count_import_operations = 0;
                foreach ( $activelist['ActiveList']['ItemArray']['Item'] as $key => $value ) {
                  
                    $itemId         = ! empty( $value['ItemID'] ) ? $value['ItemID'] : false;
                   
                    $itemDetails    = $ebayUploadInstance->get_item_details( $itemId );
                    if ( false != $itemId ) {
                        if ( ! empty( $itemDetails['Item']['SellingStatus']['ListingStatus'] ) && 'Active' == $itemDetails['Item']['SellingStatus']['ListingStatus'] ) {
    
                            ++$count_import_operations;
                            $store_product = get_posts(
                                array(
                                    'numberposts'  => -1,
                                    'post_type'    => 'product',
                                    'meta_key'     => '_ced_ebay_importer_listing_id_' . $user_id . '>' . $siteID,
                                    'meta_value'   => $itemId,
                                    'meta_compare' => '=',
                                )
                            );
                        }
                    }
                    $store_product = wp_list_pluck( $store_product, 'ID' );
                    if ( ! empty( $store_product ) ) {
                        if ( ! empty( $itemDetails['Item']['ListingDetails']['RelistedItemID'] ) ) {
                            update_post_meta( $store_product[0], '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, $itemDetails['Item']['ListingDetails']['RelistedItemID'] );
                        }
                        if ( false != $itemId ) {
                            $existing_product_listing_id = get_post_meta( $store_product[0], '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, true );
                            if ( $existing_product_listing_id == $itemId ) {
                                $logger->info( 'ItemID ' . wc_print_r( $itemId, true ) . ' | Quantity ' . wc_print_r( $itemDetails['Item']['Quantity'], true ) . ' | QuantitySold ' . wc_print_r( $itemDetails['Item']['SellingStatus']['QuantitySold'], true ), $context );
                                $product = wc_get_product( $store_product[0] );
                                if ( $product->is_type( 'simple' ) ) {
                                    if ( ! empty( $value['SellingStatus']['CurrentPrice'] ) ) {
                                        $product_sale_price = $value['SellingStatus']['CurrentPrice'];
                                        $product->set_price( $product_sale_price );
                                        $product->set_regular_price( $value['SellingStatus']['CurrentPrice'] );
                                        $product->save();
                                    }
                                    if ( ( $itemDetails['Item']['Quantity'] - $itemDetails['Item']['SellingStatus']['QuantitySold'] ) > 0 ) {
                                        $product->set_manage_stock( true );
                                        $product->set_stock_quantity( $itemDetails['Item']['Quantity'] - $itemDetails['Item']['SellingStatus']['QuantitySold'] );
                                        $product->set_stock_status( 'instock' );
                                        $product->save();
                                    } else {
                                        $logger->info( wc_print_r( 'Product ' . $store_product[0] . ' is out of stock!', true ), $context );
                                        $product->set_manage_stock( true );
                                        $product->set_stock_quantity( 0 );
                                        $product->set_stock_status( 'outofstock' );
                                        $product->save();
                                    }
                                } elseif ( $product->is_type( 'variable' ) ) {
                                    update_post_meta( $store_product[0], '_sku', $itemDetails['Item']['SKU'] );
                                    $logger->info( wc_print_r( 'Variable Product', true ), $context );
                                    if ( isset( $itemDetails['Item']['Variations']['Variation'][0] ) && ! empty( $itemDetails['Item']['Variations']['Variation'][0] ) ) {
                                        $product_variations = $itemDetails['Item']['Variations']['Variation'];
                                        foreach ( $product_variations as $key => $variation ) {
                                            $variation_sku = $variation['SKU'];
                                            $logger->info( wc_print_r( 'Variation SKU -> ' . $variation_sku, true ), $context );
    
                                            if ( $variation_sku ) {
                                                $variation_prod_id = wc_get_product_id_by_sku( $variation_sku );
                                            }
                                            if ( $variation['Quantity'] - $variation['SellingStatus']['QuantitySold'] > 0 ) {
                                                update_post_meta( $variation_prod_id, '_stock_status', 'instock' );
                                                update_post_meta( $variation_prod_id, '_stock', $variation['Quantity'] - $variation['SellingStatus']['QuantitySold'] );
                                                update_post_meta( $variation_prod_id, '_manage_stock', 'yes' );
                                                update_post_meta( $product->get_id(), '_stock_status', 'instock' );
                                            } else {
                                                update_post_meta( $variation_prod_id, '_stock_status', 'outofstock' );
                                            }
                                        }
                                    }
                                }
    
                                $product->set_status( 'publish' );
                                $product_id = $product->save();
                                $logger->info( wc_print_r( 'Product Updated -> ' . $product_id, true ), $context );
                                if ( false != $itemId ) {
                                    update_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $siteID, $itemId );
                                }
                                $progress_key_product = 'ced_ebay_product_importer_product_progress_' . $user_id . '>' . $siteID;
                                $next_page            = ! empty( get_option( $progress_key_product ) ) ? get_option( $progress_key_product ) : 1;
                                ++$next_page;
                                update_option( $progress_key_product, $next_page );
                            }
                        }
                    } elseif ( ! empty( $itemDetails['Item']['SellingStatus']['ListingStatus'] ) && 'Active' == $itemDetails['Item']['SellingStatus']['ListingStatus'] && 'Chinese' != $itemDetails['Item']['ListingType'] ) {
                            $itemId_array = array(
                                0 => $itemId,
                            );
    
                            $data = array(
                                'total_listings' => $total_ebay_listings,
                                'page_number'    => $page_number,
                                'item_id'        => $itemId_array,
                                'user_id'        => $user_id,
                                'site_id'	     => $siteID,
                                'rsid'			 => $rsid
                            );
                            $this->schedule_import_task->push_to_queue( $data );
                    }
                }
                $dispatch_process = $this->schedule_import_task->save()->dispatch();
                if ( is_wp_error( $dispatch_process ) ) {
                    update_option( 'ced_ebay_error_import_dispatch_' . $user_id, 'yes' );
                    update_option( 'ced_ebay_product_import_pagination_' . $user_id . '>' . $siteID, 1 );
                }
                if ( 25 == $count_import_operations ) {
                    $next_page = ! empty( get_option( 'ced_ebay_product_import_pagination_' . $user_id . '>' . $siteID ) ) ? get_option( 'ced_ebay_product_import_pagination_' . $user_id . '>' . $siteID ) : 1;
                    ++$next_page;
                    update_option( 'ced_ebay_product_import_pagination_' . $user_id . '>' . $siteID, $next_page );
                } else {
                    update_option( 'ced_ebay_product_import_pagination_' . $user_id . '>' . $siteID, 1 );
                }
            }
        }
    
    
    }

}