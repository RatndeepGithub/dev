<?php


class Ced_Amazon_Get_Categoires {


	public function __construct( $shop_id ) {
		$this->ced_amazon_get_categories( $shop_id );
	}


	public function ced_amazon_get_categories( $shop_id ) {
		$access_token_response = ced_amazon_get_access_token( $shop_id );
		$decoded_response      = json_decode( $access_token_response, true );

		if ( ! $decoded_response['status'] ) {
			return $access_token_response;     // json_encoded_response
		}

		$bearerToken = $decoded_response['data'];
		$apiUrl      = 'https://amazon-sales-channel-api-backend.cifapps.com/webapi/rest/v1/category-all/?shop_id=' . $shop_id;

		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL, $apiUrl );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array(
				'Authorization: Bearer ' . $bearerToken,
			)
		);
		$response = curl_exec( $ch );
		if ( curl_errno( $ch ) ) {
			print_r( 'cURL Error: ' . curl_error( $ch ) );
		}
		curl_close( $ch );
		if ( $response ) {
			$response = json_decode( $response, true );
			$response = isset( $response['response'] ) ? $response['response'] : array();

			if ( $response ) {

				print_r( $this->ced_amazon_render_select( $response ) );
			} else {
				print_r( $response );
			}
		} else {
			// No response from the API
			echo 'No response from the API';
		}
	}


	public function ced_amazon_render_select( $categories ) {

		$html = '';

		$html .= '<div class="ced-category-mapping-wrapper">';
		$html .= '<div class="ced-category-mapping">';

		$html .= '<input type="hidden" class="ced_amz_cat_name_arr" name="ced_amazon_profile_data[amazon_categories_name]" value="" />';
		$html .= '<input type="hidden" class="ced_primary_category" name="ced_amazon_profile_data[primary_category]" />';
		$html .= '<input type="hidden" class="ced_secondary_category" name="ced_amazon_profile_data[secondary_category]" />';
		$html .= '<input type="hidden" class="ced_browse_category" name="ced_amazon_profile_data[browse_nodes]" />';
		$html .= '<input type="hidden" class="ced_browse_node_name" name="ced_amazon_profile_data[browse_nodes_name]" />';

		$html .= '<input type="hidden" id="ced-category-header" value="Browse and Select a Category">';
		$html .= '<strong><span id="ced_amazon_cat_header" data-level="1">' . __( 'Browse and Select a Category', 'amazon-for-woocommerce' ) . '</span></strong>';
		$html .= '<ol id="ced_amz_categories_1" class="ced_amz_categories" data-level="1" data-node-value="Browse and Select a Category">';

		foreach ( $categories as $key => $value ) {

			$parent_ids = isset( $value['parent_id'] ) ? $value['parent_id'] : array();
			if ( isset( $parent_ids ) && is_array( $parent_ids ) ) {
				$parent_ids = implode( ',', $parent_ids );
			}
			$hasChildren = isset( $value['hasChildren'] ) ? $value['hasChildren'] : false;

			$html .= '<li id="' . esc_attr( $parent_ids ) . '" data-level="1" class="ced_amazon_category_arrow" data-name="' . esc_attr( $value['name'] ) . '" data-children="' . esc_attr( $hasChildren ) . '" data-id="' . esc_attr( $parent_ids ) . '" >' . esc_attr( $value['name'] ) . '<span  class="dashicons dashicons-arrow-right-alt2"></span></li>';
		}
		$html .= '</ol>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '<div class="ced-category-mapping-wrapper-breadcrumb" >';
		$html .= '<p  id="ced_amazon_breadcrumb" > </p>';
		$html .= '</div>';

		return $html;
	}
}
