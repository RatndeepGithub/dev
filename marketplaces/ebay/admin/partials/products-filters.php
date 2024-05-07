<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * FilterClass.
 *
 * @since 1.0.0
 */
class FilterClass {

	/**
	 * Function- filter_by_category.
	 * Used to Apply Filter on Product Page
	 *
	 * @since 1.0.0
	 */
	public function ced_ebay_filters_on_products() {
		if ( isset( $_POST['ced_ebay_product_filter_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_ebay_product_filter_nonce'] ), 'ced_ebay_product_filter_page_nonce' ) ) {
			$sanitized_array     = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
			$user_id             = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
			$site_id             = isset( $_GET['site_id'] ) ? sanitize_text_field( $_GET['site_id'] ) : '';
			$filtered_profile_id = isset( $_GET['profileID'] ) ? sanitize_text_field( $_GET['profileID'] ) : '';
			if ( ( isset( $sanitized_array['status_sorting'] ) && '' != $sanitized_array['status_sorting'] ) || ( isset( $sanitized_array['pro_cat_sorting'] ) && '' != $sanitized_array['pro_cat_sorting'] && '' != $sanitized_array['pro_cat_sorting'] ) || ( isset( $sanitized_array['pro_type_sorting'] ) && '' != $sanitized_array['pro_type_sorting'] ) ) {
				$status_sorting    = isset( $sanitized_array['status_sorting'] ) ? ( $sanitized_array['status_sorting'] ) : '';
				$pro_cat_sorting   = isset( $sanitized_array['pro_cat_sorting'] ) ? ( $sanitized_array['pro_cat_sorting'] ) : '';
				$pro_type_sorting  = isset( $sanitized_array['pro_type_sorting'] ) ? ( $sanitized_array['pro_type_sorting'] ) : '';
				$pro_stock_sorting = isset( $sanitized_array['pro_stock_sorting'] ) ? ( $sanitized_array['pro_stock_sorting'] ) : '';
				$current_url       = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
				wp_redirect( $current_url . '&status_sorting=' . $status_sorting . '&pro_cat_sorting=' . $pro_cat_sorting . '&pro_type_sorting=' . $pro_type_sorting . '&pro_stock_sorting=' . $pro_stock_sorting . '&profileID=' . $filtered_profile_id );
			} else {
				$url = admin_url( 'admin.php?page=sales_channel&channel=ebay&section=products-view&user_id=' . $user_id . '&site_id=' . $site_id );
				wp_redirect( $url );
			}
		}
	}

	public function ced_ebay_product_search_box() {
		$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( wp_unslash( $_GET['user_id'] ) ) : '';
		$site_id = isset( $_GET['site_id'] ) ? sanitize_text_field( $_GET['site_id'] ) : '';
		if ( ! isset( $_POST['ced_ebay_product_filter_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['ced_ebay_product_filter_nonce'] ), 'ced_ebay_product_filter_page_nonce' ) ) {
			return;
		}
		if ( isset( $_POST['s'] ) && ! empty( $_POST['s'] ) ) {

			$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( wp_unslash( $_GET['user_id'] ) ) : '';
			$site_id = isset( $_GET['site_id'] ) ? sanitize_text_field( $_GET['site_id'] ) : '';

			$current_url = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

			$searchdata = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';
			$searchdata = str_replace( ' ', '+', urlencode( $searchdata ) );

			wp_redirect( $current_url . '&searchTerm=' . $searchdata );

		} else {

			$url = admin_url( 'admin.php?page=sales_channel&channel=ebay&section=products-view&user_id=' . $user_id . '&site_id=' . $site_id );
			wp_redirect( $url );
		}
	}
}//end class
