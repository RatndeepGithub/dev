<?php

defined( 'ABSPATH' ) || die( 'Direct access not allowed' );


class ImportBackground_Process extends WP_Background_Process {
	const STATUS_CANCELLED = 1;
	public function __construct() {
		parent::__construct();
	}

	protected $action = 'schedule_async_import_task';

	protected function handle() {
		// Check to see if sync is supposed to be cleared
		$clear = get_option( 'ced_ebay_clear_import_process' );

		// If we do, manually clear the options from the database
		if ( $clear ) {
			wc_get_logger()->info( wc_print_r( 'going to stop import process', true ) );
			// Get current batch and delete it
			$batch = $this->get_batch();
			$this->delete( $batch->key );

			// Clear out transient that locks the process
			$this->unlock_process();

			// Call the complete method, which will tie things up
			$this->complete();

			// Remove the "clear" flag we had manually set
			delete_option( 'ced_ebay_clear_import_process' );

			// Ensure we don't actually handle anything
			return;
		}

		parent::handle();
	}

	protected function task( $item ) {

		$logger  = wc_get_logger();
		$context = array( 'source' => 'ced_ebay_Import_background_process' );
		$Item_id = $item['item_id'];
		$user_id = $item['user_id'];
		if ( ! isset( $Item_id[0] ) ) {
			$Item_id = array(
				0 => $Item_id,
			);
		}
		$shop_data = ced_ebay_get_shop_data( $user_id );
		if ( ! empty( $shop_data ) ) {
			$siteID      = $shop_data['site_id'];
			$token       = $shop_data['access_token'];
			$getLocation = $shop_data['location'];
		}
		$logger->info( 'User Id - ' . wc_print_r( $user_id, true ), $context );
		$logger->info( 'Processing Item ID - ' . wc_print_r( $Item_id, true ), $context );
		$store_products = get_posts(
			array(
				'numberposts'  => -1,
				'post_type'    => 'product',
				'meta_key'     => '_ced_ebay_importer_listing_id_' . $user_id . '>' . $siteID,
				'meta_value'   => $Item_id[0],
				'meta_compare' => '=',
			)
		);
		$localItemID    = wp_list_pluck( $store_products, 'ID' );
		if ( ! empty( $localItemID ) ) {
			$ID = $localItemID[0];
			$logger->info( 'Item ID already exist in store - ' . wc_print_r( $ID, true ), $context );
			return false;
		}

		require_once CED_EBAY_DIRPATH . 'admin/class-woocommerce-ebay-integration-admin.php';
		$adminFileImportRequest = new EBay_Integration_For_Woocommerce_Admin( 'ebay-integration-for-woocommerce', '1.0.0' );
		$adminFileImportRequest->ced_ebay_bulk_import_to_store( $Item_id, $user_id, true );
		return false;
	}


	protected function complete() {
		wc_get_logger()->info( 'Finalized' );
		parent::complete();
	}

	/**
	 * Delete a batch of queued items.
	 *
	 * @param string $key Key.
	 *
	 * @return $this
	 */
	public function delete( $key ) {
		delete_site_option( $key );

		return $this;
	}

	/**
	 * Delete entire job queue.
	 */
	public function delete_all() {
		$batches = $this->get_batches();

		foreach ( $batches as $batch ) {
			$this->delete( $batch->key );
		}

		delete_site_option( $this->get_status_key() );

		$this->cancelled();
	}

	/**
	 * Get batches.
	 *
	 * @param int $limit Number of batches to return, defaults to all.
	 *
	 * @return array of stdClass
	 */
	public function get_batches( $limit = 0 ) {
		global $wpdb;

		if ( empty( $limit ) || ! is_int( $limit ) ) {
			$limit = 0;
		}

		$table        = $wpdb->options;
		$column       = 'option_name';
		$key_column   = 'option_id';
		$value_column = 'option_value';

		if ( is_multisite() ) {
			$table        = $wpdb->sitemeta;
			$column       = 'meta_key';
			$key_column   = 'meta_id';
			$value_column = 'meta_value';
		}

		$key = $wpdb->esc_like( $this->identifier . '_batch_' ) . '%';

		$sql = '
		SELECT *
		FROM ' . $table . '
		WHERE ' . $column . ' LIKE %s
		ORDER BY ' . $key_column . ' ASC
		';

		$args = array( $key );

		if ( ! empty( $limit ) ) {
			$sql .= ' LIMIT %d';

			$args[] = $limit;
		}

		// $items = $wpdb->get_results( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( empty( $offset ) ) {
			$items = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %s WHERE %s LIKE %s ORDER BY %s ASC',
					$table,
					$column,
					'%' . $wpdb->esc_like( $args ) . '%',
					$key_column
				)
			);
		}

		$batches = array();

		if ( ! empty( $items ) ) {
			$batches = array_map(
				function ( $item ) use ( $column, $value_column ) {
					$batch       = new stdClass();
					$batch->key  = $item->{$column};
					$batch->data = maybe_unserialize( $item->{$value_column} );

					return $batch;
				},
				$items
			);
		}

		return $batches;
	}

	/**
	 * Cancel job on next batch.
	 */
	public function cancel() {
		update_site_option( $this->get_status_key(), self::STATUS_CANCELLED );

		// Just in case the job was paused at the time.
		$this->dispatch();
	}

	/**
	 * Get the status key.
	 *
	 * @return string
	 */
	protected function get_status_key() {
		return $this->identifier . '_status';
	}

	/**
	 * Has the process been cancelled?
	 *
	 * @return bool
	 */
	public function is_cancelled() {
		$status = get_site_option( $this->get_status_key(), 0 );

		if ( absint( $status ) === self::STATUS_CANCELLED ) {
			return true;
		}

		return false;
	}

	/**
	 * Called when background process has been cancelled.
	 */
	protected function cancelled() {
		/**
		 * Identifier.
		 *
		 * @since 1.0.0
		 */
		do_action( $this->identifier . '_cancelled' );
	}

	/**
	 * Pause job on next batch.
	 */
	public function pause() {
		update_site_option( $this->get_status_key(), self::STATUS_PAUSED );
	}

	/**
	 * Is the job paused?
	 *
	 * @return bool
	 */
	public function is_paused() {
		$status = get_site_option( $this->get_status_key(), 0 );

		if ( absint( $status ) === self::STATUS_PAUSED ) {
			return true;
		}

		return false;
	}

	/**
	 * Called when background process has been paused.
	 */
	protected function paused() {
		/**
		 * Identifier.
		 *
		 * @since 1.0.0
		 */
		do_action( $this->identifier . '_paused' );
	}
}
