<?php


if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( empty( get_option( 'ced_amazon_sellernext_shop_ids', array() ) ) ) {
	$connect_to_amazon['will_connect']  = 'block';
	$connect_to_amazon['did_connected'] = 'none';

} else {
	$connect_to_amazon['will_connect']  = 'none';
	$connect_to_amazon['did_connected'] = 'block';
}



$data                           = isset( $_GET['data'] ) ? sanitize_text_field( $_GET['data'] ) : '';
$ced_amazon_sellernext_shop_ids = get_option( 'ced_amazon_sellernext_shop_ids', array() );


if ( ! empty( $data ) ) {
	$redirection_data = $data;
	$redirection_data = explode( '.', $redirection_data );
	$redirection_data = base64_decode( $redirection_data[1], true );
	$redirection_data = json_decode( $redirection_data, true );

	if ( ! empty( $redirection_data['data']['shop_id'] ) ) {

		$marketplace_id = get_option( 'ced_amazon_current_marketplace_id', '' );

		if ( isset( $ced_amazon_sellernext_shop_ids[ $redirection_data['data']['shop_id'] ] ) ) {
			unset( $ced_amazon_sellernext_shop_ids[ $redirection_data['data']['shop_id'] ] );
			update_option( 'ced_amazon_sellernext_shop_ids', $ced_amazon_sellernext_shop_ids );
		}
		if ( ! isset( $ced_amazon_sellernext_shop_ids[ $redirection_data['data']['shop_id'] ] ) ) {

			$ced_amazon_sellernext_shop_ids[ $redirection_data['data']['shop_id'] ] = array( 'marketplace_id' => $marketplace_id );
			update_option( 'ced_amazon_sellernext_shop_ids', $ced_amazon_sellernext_shop_ids );

		}

		$connect_to_amazon['will_connect']  = 'none';
		$connect_to_amazon['did_connected'] = 'block';

		$current_amaz_shop_id = $redirection_data['data']['shop_id'];

	}
} else {
	$current_amaz_shop_id = '';
}


$part = isset( $_GET['part'] ) ? sanitize_text_field( $_GET['part'] ) : false;
require_once CED_AMAZON_DIRPATH . 'admin/partials/ced-amazon-login.php';
