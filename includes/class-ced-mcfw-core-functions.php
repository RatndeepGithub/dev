<?php

if ( ! function_exists( 'ced_get_bearer_token' ) ) {
	function ced_get_bearer_token() {
		$btoken = get_option( 'ced_mcfw_user_token', '' );
		return $btoken;
	}
}

if ( ! function_exists( 'ced_get_navigation_url' ) ) {
	function ced_get_navigation_url( $channel = '', $query_args = array() ) {
		if( !empty($_GET['mode']) ) {
			$query_args['mode'] = $_GET['mode'];
		}
		if ( ! empty( $query_args ) ) {
			return apply_filters( 'ced_get_navigation_url' , admin_url( 'admin.php?page=sales_channel&channel=' . $channel . '&' . http_build_query( $query_args ) ) );
		}
		return apply_filters('ced_get_navigation_url',admin_url( 'admin.php?page=sales_channel&channel=' . $channel ));
	}
}

if ( ! function_exists( 'ced_get_auth_token' ) ) {
	function ced_get_auth_token() {
		$mode = $_GET['mode']??'';
		$subscription_info = get_option( 'ced_mcfw_subscription_details' . $mode , array() );
		$auth_token = $subscription_info['token']??'';
		return $auth_token;
	}
}


