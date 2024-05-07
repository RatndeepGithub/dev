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


$user_id                        = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
$seller_id                      = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
$connected                      = isset( $_GET['connected'] ) ? sanitize_text_field( $_GET['connected'] ) : false;
$ced_amazon_sellernext_shop_ids = get_option( 'ced_amazon_sellernext_shop_ids', array() );

if ( $connected ) {

	if ( ! empty( $user_id ) && ! empty( $seller_id ) ) {

		$marketplace_id = get_option( 'ced_amazon_current_marketplace_id', '' );

		if ( isset( $ced_amazon_sellernext_shop_ids[ $user_id ] ) ) {
			unset( $ced_amazon_sellernext_shop_ids[ $user_id ] );
			update_option( 'ced_amazon_sellernext_shop_ids', $ced_amazon_sellernext_shop_ids );
		}

		if ( ! isset( $ced_amazon_sellernext_shop_ids[ $user_id ] ) ) {

			$fileAccounts = CED_AMAZON_DIRPATH . 'admin/partials/amazonRegions.php';
			if ( file_exists( $fileAccounts ) ) {
				require_once $fileAccounts;
			}

			$ced_amazon_sellernext_shop_ids[ $user_id ] = array(
				'marketplace_id'    => $marketplace_id,
				'ced_mp_name'       => $ced_amazon_regions_info[ $marketplace_id ]['shop-name'],
				'ced_mp_seller_key' => $seller_id,
			);
			update_option( 'ced_amazon_sellernext_shop_ids', $ced_amazon_sellernext_shop_ids );


			$ced_amzon_configuration_validated[ $seller_id ] = array(
				'marketplace_id'      => $marketplace_id,
				'marketplace_region'  => $ced_amazon_regions_info[ $marketplace_id ]['region_value'],
				'country_name'        => $ced_amazon_regions_info[ $marketplace_id ]['country-name'],
				'country_value'       => $ced_amazon_regions_info[ $marketplace_id ]['value'],
				'seller_next_shop_id' => $user_id,
				'merchant_id'         => $seller_id,
				'ced_mp_name'         => $ced_amazon_regions_info[ $marketplace_id ]['shop-name'],
				'ced_mp_seller_key'   => $seller_id,

			);
			update_option( 'ced_amzon_configuration_validated', $ced_amzon_configuration_validated );


		}

		$connect_to_amazon['will_connect']  = 'none';
		$connect_to_amazon['did_connected'] = 'block';

		$current_amaz_shop_id = $user_id;

	}
} else {
	$current_amaz_shop_id = '';
}


$part = isset( $_GET['part'] ) ? sanitize_text_field( $_GET['part'] ) : false;
require_once CED_AMAZON_DIRPATH . 'admin/partials/ced-amazon-login.php';
