<?php

if ( ! function_exists( 'ced_get_bearer_token' ) ) {
	function ced_get_bearer_token() {
		$btoken = get_option( 'ced_mcfw_user_token', '' );
		return $btoken;
	}
}

if ( ! function_exists( 'ced_get_navigation_url' ) ) {
	function ced_get_navigation_url( $channel = '', $query_args = array(), $setup = false ) {

		if ( ! empty( get_option( 'ced_ebay_active_user_url' ) ) && 'ebay' == $channel && ! $setup ) {
			return get_option( 'ced_ebay_active_user_url', true );
		}

		if ( ! empty( $query_args ) ) {
			return admin_url( 'admin.php?page=sales_channel&channel=' . $channel . '&' . http_build_query( $query_args ) );
		}
		return admin_url( 'admin.php?page=sales_channel&channel=' . $channel );
	}
}
