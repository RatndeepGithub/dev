<?php
namespace Ced\Ebay;
defined( 'ABSPATH' ) || die( 'Direct access not allowed' );


class ImportBackground_Process extends \WP_Background_Process {
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
		$siteID = $item['site_id'];
		$rsid = $item['rsid'];
		if ( ! isset( $Item_id[0] ) ) {
			$Item_id = array(
				0 => $Item_id,
			);
		}

		if(empty($user_id) || '' === $siteID){
			$logger->info( 'User Id or Site ID is empty', $context );
			return false;
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

		$ebayUploadInstance = \Ced\Ebay\EbayUpload::get_instance( $rsid );
		$itemDetails        = $ebayUploadInstance->get_item_details( $Item_id[0] );
		if ( 'Success' == $itemDetails['Ack'] || 'Warning' == $itemDetails['Ack'] ){
			if ( ! empty( $itemDetails['Item']['ListingDetails']['EndingReason'] ) || 'Completed' == $itemDetails['Item']['SellingStatus']['ListingStatus'] ){
				$logger->info( 'Listing ' . wc_print_r( $Item_id, true ).' is ended on eBay', $context );
				return false;
			}
			$import_listing = new \Ced\Ebay\Import_Listing();
			$ebay_title = isset($itemDetails['Item']['Title']) ? $itemDetails['Item']['Title'] : '';
			$ebay_description = isset($itemDetails['Item']['Description']) ? $itemDetails['Item']['Description'] : '';
			$ebay_item_specifics = isset($itemDetails['Item']['ItemSpecifics']) ? $itemDetails['Item']['ItemSpecifics'] : ['NameValueList' => []];
			
			$product_id = $import_listing->createProduct($ebay_title, 'publish');
			
			if(!empty($product_id) && !is_wp_error($product_id)){
				$meta_to_update = [
					'ced_ebay_synced_by_user' => ['user_id' => $user_id, 'site_id' => $siteID],
					'_ced_ebay_listing_id_'.$user_id.'>'.$siteID => $Item_id[0],
					'_ced_ebay_importer_listing_id_'.$user_id.'>'.$siteID => $Item_id[0],
				];
				$import_listing->importDescription($ebay_description);
				$import_listing->importProductMeta($meta_to_update);
				$import_listing->importProductAttributes($ebay_item_specifics);
				if ( isset( $itemDetails['Item']['Variations'] ) ){
					$import_listing->setWooProduct($product_id, 'variable');
					if ( ! isset( $itemDetails['Item']['Variations']['VariationSpecificsSet']['NameValueList'][0] ) ) {
						$tempNameValueList = array();
						$tempNameValueList = $itemDetails['Item']['Variations']['VariationSpecificsSet']['NameValueList'];
						unset( $itemDetails['Item']['Variations']['VariationSpecificsSet']['NameValueList'] );
						$itemDetails['Item']['Variations']['VariationSpecificsSet']['NameValueList'][] = $tempNameValueList;
					}
					$variation_specifics =  isset($itemDetails['Item']['Variations']['VariationSpecificsSet']) ? $itemDetails['Item']['Variations']['VariationSpecificsSet'] : ['NameValueList' => []];
					$import_listing->importProductAttributes($variation_specifics);
				} else {
					$import_listing->setWooProduct($product_id, 'simple');
					$ebay_price = isset($itemDetails['Item']['StartPrice']) ? $itemDetails['Item']['StartPrice'] : 0;
					$ebay_sku = isset($itemDetails['Item']['SKU']) ? $itemDetails['Item']['SKU'] : '';
					$ebay_quantity = isset($itemDetails['Item']['Quantity']) ? $itemDetails['Item']['Quantity'] : 0;
					$ebay_quantity_sold = isset($itemDetails['Item']['SellingStatus']['QuantitySold']) ? $itemDetails['Item']['SellingStatus']['QuantitySold'] : 0;
					$available_quantity = $ebay_quantity - $ebay_quantity_sold;
					$import_listing->importPrice($ebay_price);
					$import_listing->importSku($ebay_sku);
					$import_listing->importStock($available_quantity);
				}
				$import_listing->importListingImages($itemDetails['Item']);
			}

		}
		return false;
	}


	protected function complete() {
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
