<?php

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
	public function ced_amazon_filters_on_products() {
		if ( isset( $_POST['ced_amazon_product_filter_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_product_filter_nonce'] ), 'ced_amazon_product_filter_page_nonce' ) ) {
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
			$user_id         = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
			$seller_id       = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : get_option( 'ced_umb_amazon_bulk_profile_loc_temp' );
			$seller_id       = str_replace( '|', '%7C', $seller_id );

			if ( ( isset( $sanitized_array['status_sorting'] ) && '' != $sanitized_array['status_sorting'] ) || ( isset( $sanitized_array['pro_cat_sorting'] ) && '' != $sanitized_array['pro_cat_sorting'] && '' != $sanitized_array['pro_cat_sorting'] ) || ( isset( $sanitized_array['pro_type_sorting'] ) && '' != $sanitized_array['pro_type_sorting'] ) ) {
				$status_sorting    = isset( $sanitized_array['status_sorting'] ) ? ( $sanitized_array['status_sorting'] ) : '';
				$pro_cat_sorting   = isset( $sanitized_array['pro_cat_sorting'] ) ? ( $sanitized_array['pro_cat_sorting'] ) : '';
				$pro_type_sorting  = isset( $sanitized_array['pro_type_sorting'] ) ? ( $sanitized_array['pro_type_sorting'] ) : '';
				$pro_stock_sorting = isset( $sanitized_array['pro_stock_sorting'] ) ? ( $sanitized_array['pro_stock_sorting'] ) : '';

				$s = isset( $sanitized_array['s'] ) ? ( $sanitized_array['s'] ) : '';

				$current_url = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
				$current_url = $current_url . '&seller_id=' . $seller_id;
				wp_safe_redirect( $current_url . '&status_sorting=' . $status_sorting . '&pro_cat_sorting=' . $pro_cat_sorting . '&pro_type_sorting=' . $pro_type_sorting . '&pro_stock_sorting=' . $pro_stock_sorting . '&s=' . $s );
			} else {
				$url = admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=products-view&user_id=' . $user_id . '&seller_id=' . $seller_id;
				wp_safe_redirect( $url );
			}
		}
	}

	public function productSearch_box( $_products, $valueTobeSearched ) {

		if ( isset( $_POST['s'] ) && ! empty( $_POST['s'] ) ) {
			if ( ! isset( $_POST['ced_amazon_product_filter_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_product_filter_nonce'] ), 'ced_amazon_product_filter_page_nonce' ) ) {
				return;
			}

			$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( wp_unslash( $_GET['user_id'] ) ) : '';
			$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : get_option( 'ced_umb_amazon_bulk_profile_loc_temp' );
			$seller_id = str_replace( '|', '%7C', $seller_id );

			$current_url = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$current_url = $current_url . '&seller_id=' . $seller_id;

			$searchdata = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';
			$searchdata = str_replace( ' ', '+', urlencode( $searchdata ) );

			wp_safe_redirect( $current_url . '&s=' . $searchdata );

		} else {

			$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( wp_unslash( $_GET['user_id'] ) ) : '';
			$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : get_option( 'ced_umb_amazon_bulk_profile_loc_temp' );
			$seller_id = str_replace( '|', '%7C', $seller_id );

			$url = admin_url( 'admin.php?page=sales_channel&channel=amazon&section=products-view&user_id=' . $user_id . '&seller_id=' . $seller_id );
			wp_safe_redirect( $url );
		}
	}
}
