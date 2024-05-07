<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( file_exists( CED_EBAY_DIRPATH . 'admin/partials/header.php' ) ) {
	require_once CED_EBAY_DIRPATH . 'admin/partials/header.php';
}
class Ced_Ebay_Get_Categories {


	public function __construct() {
	}
	public function ced_ebay_get_categories() {
		$user_id        = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$site_id        = isset( $_GET['sid'] ) ? sanitize_text_field( $_GET['sid'] ) : '';
		$wp_upload_dir  = wp_upload_dir() ['baseurl'];
		$is_ebay_motors = false;
		$ebay_folder    = $wp_upload_dir . '/ced-ebay/category-templates-json';
		$site_details   = ced_ebay_get_site_details( $site_id );
		if ( empty( $site_details ) ) {
			return;
		}
		$location = isset( $site_details['name'] ) ? $site_details['name'] : function () {
			return;
		};
		$location = strtolower( $location );
		$location = str_replace( ' ', '', $location );
		if ( '100' == $site_id ) {
			$is_ebay_motors = true;
			$categories     = @file_get_contents( $ebay_folder . '/categoryLevel-2_' . $location . '.json' );
		} else {
			$categories = @file_get_contents( $ebay_folder . '/categoryLevel-1_' . $location . '.json' );
		}
		if ( ! empty( $categories ) ) {
			$categories = json_decode( $categories, 1 );
			$categories = isset( $categories['CategoryArray']['Category'] ) ? filter_var_array( $categories['CategoryArray']['Category'], FILTER_SANITIZE_SPECIAL_CHARS ) : array();
		} else {
			$categories = array();
		}

		if ( isset( $categories ) ) {
			print_r( $this->ced_ebay_render_select( $categories, $location, $is_ebay_motors ) );
		} else {
			echo esc_attr( __( 'Categories not found', 'ebay-integration-for-woocommerce' ) );
		}
	}


	public function ced_ebay_render_select( $categories, $location, $is_ebay_motors = false ) {

		if ( isset( $_POST['ced_ebay_profile_clone'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_ebay_profile_clone'] ), 'ced_ebay_profile_clone_page_nonce' ) ) {
			if ( isset( $_POST['ced_ebay_profile_save_button'] ) ) {
				global $wpdb;
				$tableName           = $wpdb->prefix . 'ced_ebay_profiles';
				$sanitized_array     = filter_input_array( INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS );
				$marketplaceName     = isset( $sanitized_array['marketplaceName'] ) ? ( $sanitized_array['marketplaceName'] ) : 'all';
				$updateinfo          = array();
				$common              = isset( $sanitized_array['ced_ebay_required_common'] ) ? ( $sanitized_array['ced_ebay_required_common'] ) : array();
				$woo_category        = isset( $sanitized_array['woo_categories'] ) ? ( $sanitized_array['woo_categories'] ) : '';
				$site_id             = isset( $sanitized_array['site_id'] ) ? ( $sanitized_array['site_id'] ) : '';
				$user_id             = isset( $sanitized_array['user_id'] ) ? ( $sanitized_array['user_id'] ) : '';
				$profile_category_id = isset( $sanitized_array['profile_category_id'] ) ? $sanitized_array['profile_category_id'] : '';
				$ebay_profile_name   = isset( $sanitized_array['ebay_profile_name'] ) ? $sanitized_array['ebay_profile_name'] : '';
				$rsid           = ced_ebay_get_shop_data( $user_id );
				
				foreach ( $common as $key ) {
					$arrayToSave = array();
					if ( false !== strpos( $key, '_required' ) ) {
						$position                            = strpos( $key, '_required' );
						$key                                 = substr( $key, 0, $position );
						$sanitized_array[ $key ]['required'] = true;
						$arrayToSave['required']             = $sanitized_array[ $key ]['required'];
					}
					isset( $sanitized_array[ $key ][0] ) ? $arrayToSave['default'] = ( $sanitized_array[ $key ][0] ) : $arrayToSave['default'] = '';

					if ( '_umb_' . $marketplaceName . '_subcategory' == $key ) {

						isset( $sanitized_array[ $key ] ) ? $arrayToSave['default'] = ( $sanitized_array[ $key ] )
						: $arrayToSave['default']                                   = '';
					}
					if ( '_umb_ebay_category' == $key && '' == $profileID ) {
						$category_id = isset( $sanitized_array['ced_ebay_level3_category'] ) ? ( $sanitized_array['ced_ebay_level3_category'] )
						: '';
						$category_id = isset( $category_id[0] ) ? $category_id[0] : '';
						isset( $sanitized_array[ $key ][0] ) ? $arrayToSave['default'] = $category_id : $arrayToSave['default'] = '';
					}

					isset( $sanitized_array[ $key . '_attibuteMeta' ] ) ? $arrayToSave['metakey'] = ( $sanitized_array[ $key . '_attibuteMeta' ] )
					: $arrayToSave['metakey'] = 'null';
					$updateinfo[ $key ]       = $arrayToSave;
				}

				// echo '<pre>'; print_r($updateinfo); echo '</pre>'; die('opp');
				$updateinfo['selected_product_id']           = isset( $sanitized_array['selected_product_id'] ) ? ( $sanitized_array['selected_product_id'] )
				: '';
				$updateinfo['selected_product_name']         = isset( $sanitized_array['ced_sears_pro_search_box'] ) ? ( $sanitized_array['ced_sears_pro_search_box'] )
				: '';
				$updateinfo['_umb_ebay_category']['default'] = $profile_category_id;
				$updateinfo                                  = json_encode( $updateinfo );
				$ebay_categories_name                        = get_option( 'ced_ebay_categories' . $user_id, array() );
				$categoryIDs                                 = array();
				for ( $i = 1; $i <= 6; $i++ ) {
					$categoryIDs[] = isset( $sanitized_array[ 'ced_ebay_level' . $i . '_category' ] ) ? ( ( $sanitized_array[ 'ced_ebay_level' . $i . '_category' ] ) )
					: '';
				}
					// $categoryName = '';
					// foreach ( $categoryIDs as $index => $categoryId ) {
					// foreach ( $ebay_categories_name['categories'] as $key => $value ) {
					// if ( isset( $categoryId[0] ) && ! empty( $categoryId[0] ) && $categoryId[0] == $value['category_id'] ) {
					// $categoryName    .= $value['category_name'] . ' --> ';
					// $ebay_category_id = $value['category_id'];
					// }
					// }
					// }
					// $profile_name   = substr( $categoryName, 0, -5 );

					$woo_category_mapped = wp_json_encode( $woo_category );
					$profileDetails      = array(
						'profile_name'   => $ebay_profile_name,
						'profile_status' => 'active',
						'ebay_user'      => (string) $user_id,
						'profile_data'   => $updateinfo,
						'woo_categories' => $woo_category_mapped,
						'ebay_site'      => $site_id,
					);
					global $wpdb;
					$profileTableName = $wpdb->prefix . 'ced_ebay_profiles';

					$wpdb->insert( $profileTableName, $profileDetails );
					$profileId = $wpdb->insert_id;
					$profileId = $wpdb->insert_id;
					if ( ! empty( $profileId ) && ! empty( $woo_category ) && is_array( $woo_category ) && ! empty( $profile_category_id ) ) {
						foreach ( $woo_category as $key => $woo_mapped_cat ) {
							update_term_meta( $woo_mapped_cat, 'ced_ebay_profile_created_' . $user_id . '>' . $site_id, 'yes' );
							update_term_meta( $woo_mapped_cat, 'ced_ebay_profile_id_' . $user_id . '>' . $site_id, $profileId );
							update_term_meta( $woo_mapped_cat, 'ced_ebay_mapped_category_' . $user_id . '>' . $site_id, $profile_category_id );
							update_term_meta( $woo_mapped_cat, 'ced_ebay_profile_name_' . $user_id . '>' . $site_id, $ebay_profile_name );
						}
					}
					
					$profile_edit_url = admin_url( 'admin.php?page=sales_channel&channel=ebay&section=view-templates&user_id=' . $user_id . '&profileID=' . $profileId . '&sid=' . $site_id . '&eBayCatID=' . $profile_category_id );
					wp_redirect( $profile_edit_url );
			}
		}
		$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$site_id = isset( $_GET['sid'] ) ? sanitize_text_field( $_GET['sid'] ) : '';
		global $wpdb;
		$tableName    = $wpdb->prefix . 'ced_ebay_profiles';
		$profile_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_ebay_profiles WHERE `ebay_user`=%s AND `ebay_site`=%s", $user_id, $site_id ), 'ARRAY_A' );
		if ( ! empty( $profile_data ) ) {
			$woo_exclude_cat_id = array();
			foreach ( $profile_data as $profile_data_row ) {
				$wc_cat_exclude = array();
				if ( ! empty( $profile_data_row['woo_categories'] ) ) {
					$woo_exclude_cat_id[] = json_decode( $profile_data_row['woo_categories'], true );
					foreach ( $woo_exclude_cat_id as $innerArray ) {
						foreach ( $innerArray as $value ) {
							$wc_cat_exclude[] = $value;
						}
					}
				}
			}
		}
		$html                 = '';
		$html                .= '<div class="notice notice-error is-dismissable" style="display:none;"><p></p></div><form class="ced_ebay_clone_profile" action="" method="post"><div class="components-card is-size-medium woocommerce-table pinterest-for-woocommerce-landing-page__faq-section css-1xs3c37-CardUI e1q7k77g0 ced_profile_table">
		<div class="components-panel ced-padding ced-ebay-new-template-panel">
		<header>
		<h2>Create New Template</h2>
		<a href="#" class="ced-ebay-refresh-categories alignright">Fetch eBay Categories</a>
		<p>Allocate an eBay category to the template and then link the designated ebay category to the WooCommerce category that you have already set in advance.</p>
		</header>
		
		<table class="form-table css-off1bd">
		<tbody>
		<tr>
		<th scope="row" class="titledesc">
		<label for="woocommerce_currency">
		Select WooCommerce Category
		</label>
		</th>
		<td class="forminp forminp-select ced-input-setting">

		<select class="select2 custom_category_attributes_select2" name="woo_categories[]" multiple="" required="" tabindex="-1" aria-hidden="true" required>';
		$woo_store_categories = get_terms( 'product_cat' );
		foreach ( $woo_store_categories as $key => $value ) {
			$cat_name = $value->name;
			$cat_name = ced_ebay_categories_tree( $value, $cat_name );
			if ( ! empty( $wc_cat_exclude ) ) {
				if ( ! in_array( $value->term_id, $wc_cat_exclude ) ) {
					$html .= '<option value="' . $value->term_id . '">' . $cat_name . '</option>';
				}
			} else {
				$html .= '<option value="' . $value->term_id . '">' . $cat_name . '</option>';
			}
		}

		if ( $is_ebay_motors ) {
			$html .= ' </select>
		</td>
		</tr>
		<tr>
		<th scope="row" class="titledesc">
		<label for="woocommerce_currency">
		eBay Category 
		</label>
		</th>
		<td class="forminp forminp-select">
		<div class="ced-category-mapping-wrapper">
		<div class="ced-category-mapping">
		<strong><span id="ced_ebay_cat_header" data-level="2">Browse and Select a Category</span></strong>
		';
			$html .= '<ol id="ced_ebay_categories_2" class="ced_ebay_categories" data-level="2" data-node-value="Browse and Select a Category">';
			foreach ( $categories as $key => $value ) {
				$parent_id = isset( $value['CategoryParentID'][0] ) ? $value['CategoryParentID'][0] : '';
				$cat_id    = isset( $value['CategoryID'] ) ? $value['CategoryID'] : '';
				$html     .= '<li data-location="' . $location . '" id="' . $cat_id . '" data-level="2" class="ced_ebay_category_arrow" data-name="' . $value['CategoryName'] . '"  data-parentId = "' . $parent_id . '" data-id="' . $cat_id . '" >' . esc_attr( $value['CategoryName'] ) . '<span  class="dashicons dashicons-arrow-right-alt2"></span></li>';
			}
			$html .= '</ol>';
			$html .= '
		</div>
		</div>
		<div class="ced-category-mapping-wrapper-breadcrumb"><p id="ced_ebay_breadcrumb" style="display: none;">
		</p></div>
		<input type="hidden" value="" name="ebay_profile_name" id="ebay-profile_name">		
		</td>
		</tr>
		</tbody>
		</table>
		<div class="row item-aspects"></div>
		</div>
		</div>
		</form>';

		} else {
			$html .= ' </select>
		</td>
		</tr>
		<tr>
		<th scope="row" class="titledesc">
		<label for="woocommerce_currency">
		eBay Category 
		</label>
		</th>
		<td class="forminp forminp-select">
		<div class="ced-category-mapping-wrapper">
		<div class="ced-category-mapping">
		<strong><span id="ced_ebay_cat_header" data-level="1">Browse and Select a Category</span></strong>
		';
			$html .= '<ol id="ced_ebay_categories_1" class="ced_ebay_categories" data-level="1" data-node-value="Browse and Select a Category">';
			foreach ( $categories as $key => $value ) {
				$parent_id = isset( $value['CategoryParentID'][0] ) ? $value['CategoryParentID'][0] : '';
				$cat_id    = isset( $value['CategoryID'] ) ? $value['CategoryID'] : '';
				$html     .= '<li data-location="' . $location . '" id="' . $cat_id . '" data-level="1" class="ced_ebay_category_arrow" data-name="' . $value['CategoryName'] . '"  data-parentId = "' . $parent_id . '" data-id="' . $cat_id . '" >' . esc_attr( $value['CategoryName'] ) . '<span  class="dashicons dashicons-arrow-right-alt2"></span></li>';
			}
			$html .= '</ol>';
			$html .= '
		</div>
		</div>
		<div class="ced-category-mapping-wrapper-breadcrumb"><p id="ced_ebay_breadcrumb" style="display: none;">
		</p></div>
		<input type="hidden" value="" name="ebay_profile_name" id="ebay-profile_name">		
		</td>
		</tr>
		</tbody>
		</table>
		<div class="row item-aspects"></div>
		</div>
		</div>
		</form>';

		}
			return $html;
	}
}

$obj = new Ced_Ebay_Get_Categories();
$obj->ced_ebay_get_categories();
