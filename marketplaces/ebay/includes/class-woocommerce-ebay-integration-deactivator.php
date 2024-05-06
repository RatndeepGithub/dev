<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://woocommerce.com/vendor/cedcommerce
 * @since      1.0.0
 *
 * @package    EBay_Integration_For_Woocommerce
 * @subpackage EBay_Integration_For_Woocommerce/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    EBay_Integration_For_Woocommerce
 * @subpackage EBay_Integration_For_Woocommerce/includes
 */
class EBay_Integration_For_Woocommerce_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

		if ( ! empty( get_option( 'ced_ebay_user_access_token' ) ) ) {
			$ebay_data = get_option( 'ced_ebay_user_access_token', true );
			foreach ( $ebay_data as $key => $value ) {
				$user_id = $key;
				if ( ! empty( $user_id ) ) {
					$connectedEbayAccount = ! empty( get_option( 'ced_ebay_connected_accounts' ) ) ? get_option( 'ced_ebay_connected_accounts', true ) : array();
					if ( ! empty( $connectedEbayAccount ) && is_array( $connectedEbayAccount ) ) {
						if ( isset( $connectedEbayAccount[ $user_id ] ) && ! empty( $connectedEbayAccount[ $user_id ] ) ) {
							foreach ( $connectedEbayAccount[ $user_id ] as $ebaySite => $connectedEbaySiteData ) {
								$site_id        = $ebaySite;
								$scheduler_args = array();
								$scheduler_args = array(
									'user_id' => $user_id,
									'site_id' => $site_id,
								);
								if ( wp_next_scheduled( 'ced_ebay_existing_products_sync_job_' . $user_id, $scheduler_args ) ) {
									wp_clear_scheduled_hook( 'ced_ebay_existing_products_sync_job_' . $user_id, $scheduler_args );
								}
								if ( wp_next_scheduled( 'ced_ebay_import_products_job_' . $user_id, $scheduler_args ) ) {
									wp_clear_scheduled_hook( 'ced_ebay_import_products_job_' . $user_id, $scheduler_args );
								}
								if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_unschedule_all_actions' ) && function_exists( 'as_get_scheduled_actions' ) ) {
									if ( as_has_scheduled_action( 'ced_ebay_refresh_access_token_schedule', array( 'data' => array( 'user_id' => $user_id ) ) ) ) {
										as_unschedule_all_actions( 'ced_ebay_refresh_access_token_schedule', array( 'data' => array( 'user_id' => $user_id ) ) );
									}
									if ( as_has_scheduled_action( null, null, 'ced_ebay_inventory_scheduler_group_' . $user_id . '>' . $site_id ) ) {
										as_unschedule_all_actions( null, null, 'ced_ebay_inventory_scheduler_group_' . $user_id . '>' . $site_id );
									}
									if ( as_has_scheduled_action( null, null, 'ced_ebay_bulk_upload_' . $user_id . '>' . $site_id ) ) {
										as_unschedule_all_actions( null, null, 'ced_ebay_bulk_upload_' . $user_id . '>' . $site_id );
									}
									if ( as_has_scheduled_action( 'ced_ebay_order_scheduler_job_' . $user_id, array( 'data' => $scheduler_args ) ) ) {
										as_unschedule_all_actions( 'ced_ebay_order_scheduler_job_' . $user_id, array( 'data' => $scheduler_args ) );
										as_unschedule_all_actions( 'ced_ebay_async_order_sync_action', array(), 'ced_ebay_async_order_sync_' . $user_id, array( 'data' => $scheduler_args ) );
									}

									if ( as_has_scheduled_action( null, null, 'ced_ebay_sync_ended_listings_group_' . $user_id . '>' . $site_id ) ) {
										as_unschedule_all_actions( null, null, 'ced_ebay_sync_ended_listings_group_' . $user_id . '>' . $site_id );
									}
								}
							}
						}
					}
					$wp_folder     = wp_upload_dir();
					$wp_upload_dir = $wp_folder['basedir'];
					$wp_upload_dir = $wp_upload_dir . '/ced-ebay/logs/';
					$log_file      = $wp_upload_dir . 'user.txt';
					if ( file_exists( $log_file ) ) {
						wp_delete_file( $log_file );
					}
				}
			}
		}
	}
}
