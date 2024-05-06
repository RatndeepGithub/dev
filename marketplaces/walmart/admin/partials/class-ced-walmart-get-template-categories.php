<?php


class Ced_Walmart_Get_Categories {


	public function __construct() {
		$this->ced_walmart_get_categories();
	}




	public function ced_walmart_get_categories() {

		$ced_walmart_category = CED_WALMART_DIRPATH . 'admin/walmart/lib/json/MP_ITEM_SPEC.json';
		if ( file_exists( $ced_walmart_category ) ) {
			$ced_walmart_category = file_get_contents( $ced_walmart_category );
			$ced_walmart_category = json_decode( $ced_walmart_category, true );
			$ced_walmart_category = $ced_walmart_category['properties']['MPItem']['items']['properties']['Visible']['properties'];
		}

		if ( isset( $ced_walmart_category ) ) {
			print_r( $this->ced_walmart_render_select( $ced_walmart_category ) );
		} else {
			echo esc_attr__( 'Categories not found', 'walmart-integration-for-woocommerce' );
		}
	}


	public function ced_walmart_render_select( $categories ) {
		$html                 = '';
		$html                .= '<div class="components-card is-size-medium woocommerce-table pinterest-for-woocommerce-landing-page__faq-section css-1xs3c37-CardUI e1q7k77g0 ced_profile_table">
		<div class="components-panel ced-padding">
		<header>
		<h2>Category Mapping</h2>
		<p>Allocate an Walmart category to the template and then link the designated walmart category to the WooCommerce category that youve already set in advance.</p>
		</header>
		<table class="form-table css-off1bd">
		<tbody>
		<tr>
		<th scope="row" class="titledesc">
		<label for="woocommerce_currency">
		WooCommerce Category

		</label>
		</th>
		<td class="forminp forminp-select ced-input-setting">

		<select class="select2 custom_category_attributes_select2" name="woo_categories[]" multiple="" required="" tabindex="-1" aria-hidden="true">';
		$woo_store_categories = get_terms( 'product_cat' );
		foreach ( $woo_store_categories as $key => $value ) {
			$exists = get_term_meta( $value->term_id, 'ced_walmart_profile_created_' . ced_walmart_get_current_active_store(), 'yes' );
			if ( $exists ) {
				continue;
			}
			$cat_name = $value->name;
			$cat_name = ced_walmart_categories_tree( $value, $cat_name );
			$html    .= '<option value="' . $value->term_id . '">' . $cat_name . '</option>';
		}
		$html .= ' </select>
		</td>
		</tr>
		<tr>
		<th scope="row" class="titledesc">
		<label for="woocommerce_currency">
		Walmart Category 
		</label>
		</th>
		<td class="forminp forminp-select">
		<div class="ced-category-mapping-wrapper">
		<div class="ced-category-mapping">
		<strong><span id="ced_walmart_cat_header" data-level="1">Browse and Select a Category</span></strong>
		';
		$html .= '<ol id="ced_walmart_categories_1" class="ced_walmart_categories" data-level="1" data-node-value="Browse and Select a Category">';
		foreach ( $categories  as $index => $data ) {

			$cat_name = $index;

			if ( isset( $cat_name ) && ! empty( $cat_name ) ) {
				$html .= '<li class="ced_walmart_category" data-name="' . esc_attr( $cat_name ) . '" id="' . esc_attr( $cat_name ) . '" data-id="' . esc_attr( $cat_name ) . '"> ' . esc_attr( $cat_name ) . ' <input type="radio"  name="ced_walmart_last_level_cat" id="ced_walmart_last_level_cat" data-id="' . esc_attr( $cat_name ) . '" value="' . esc_attr( $cat_name ) . '" required/></li>';

			}
		}
		$html .= '</ol>';
		$html .= '
		</div>
		</div>
		<div class="ced-category-mapping-wrapper-breadcrumb"><p id="ced_walmart_breadcrumb" style="display: none;">
		</p></div>
		</td>
		</tr>
		</tbody>
		</table>
		</div>
		</div>';

				// $html.= '';
		return $html;
	}
}
