<?php

defined( 'ABSPATH' ) || die( 'Direct access not allowed' );


class BulkUpload_Background_Process extends WP_Background_Process {

	public function __construct() {
		parent::__construct();
	}

	protected $action = 'schedule_async_bulk_upload_task';

	protected function task( $item ) {
		global $wpdb;
		require_once CED_EBAY_DIRPATH . 'admin/ebay/class-ebay.php';
		if ( class_exists( 'Class_Ced_EBay_Manager' ) ) {
			$ced_ebay_manager = Class_Ced_EBay_Manager::get_instance();
		} else {
			return false;
		}
		$user_id    = $item['user_id'];
		$product_id = $item['product_id'];
		$profile_id = $item['profile_id']['id'];
		ced_ebay_log_data( 'Woo product ID - ' . $product_id, 'ced_ebay_bulk_upload_background_process' );
		ced_ebay_log_data( 'eBay profile ID - ' . $profile_id, 'ced_ebay_bulk_upload_background_process' );
		$shop_data = ced_ebay_get_shop_data( $user_id );
		if ( ! empty( $shop_data ) ) {
			$siteID      = $shop_data['site_id'];
			$token       = $shop_data['access_token'];
			$getLocation = $shop_data['location'];
		}
		$product_ids       = array();
			$database_data = $wpdb->get_results( $wpdb->prepare( "SELECT `product_id` FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `user_id` = %s", $user_id ) );
		if ( ! empty( $database_data ) ) {
			foreach ( $database_data as $key => $value ) {
				array_push( $product_ids, $value->product_id );
			}
		}

			$SimpleXml = $ced_ebay_manager->prepareProductHtmlForUpload( $user_id, $product_id );
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
										'group'  => 'ced_ebay_bulk_upload_' . $user_id,
										'status' => ActionScheduler_Store::STATUS_PENDING,
									),
									'ARRAY_A'
								);
							}
							if ( ! empty( $has_action ) ) {
								if ( function_exists( 'as_unschedule_all_actions' ) ) {
									$unschedule_actions = as_unschedule_all_actions( null, null, 'ced_ebay_bulk_upload_' . $user_id );
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
				update_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id, $ebayID );
				$time   = time();
				$offset = '.000Z';
				$date   = gmdate( 'Y-m-d', $time ) . 'T' . gmdate( 'H:i:s', $time ) . $offset;
				if ( ! empty( $product_ids ) && in_array( $product_id, $product_ids ) ) {
					$table_data = array(
						'product_id'       => $product_id,
						'profile_id'       => $profile_id,
						'operation_status' => 'Uploaded',
						'user_id'          => (string) $user_id,
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

		return false;
	}

	protected function complete() {
		wc_get_logger()->info( 'Finalized' );
		parent::complete();
	}
}
