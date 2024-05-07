<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

wp_raise_memory_limit( 'admin' );

$fileHeader   = CED_EBAY_DIRPATH . 'admin/partials/header.php';
$fileCategory = CED_EBAY_DIRPATH . 'admin/ebay/lib/cedGetcategories.php';
$fileFields   = CED_EBAY_DIRPATH . 'admin/partials/products_fields.php';


if ( file_exists( $fileHeader ) ) {
	require_once $fileHeader;
}
if ( file_exists( $fileCategory ) ) {
	require_once $fileCategory;
}
if ( file_exists( $fileFields ) ) {
	require_once $fileFields;
}
$user_id       = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
$site_id       = isset( $_GET['sid'] ) ? sanitize_text_field( $_GET['sid'] ) : '';
$wp_folder     = wp_upload_dir();
$wp_upload_dir = $wp_folder['basedir'];
$wp_upload_dir = $wp_upload_dir . '/ced-ebay/category-specifics/' . $user_id . '/' . $site_id . '/';
if ( ! is_dir( $wp_upload_dir ) ) {
	wp_mkdir_p( $wp_upload_dir, 0777 );
}
$rsid = ced_ebay_get_shop_data( $user_id,$site_id );
if(empty($rsid)){
	wp_send_json_error(
		array(
			'message' => 'Invalid eBay Account',
		)
	);
}

$isProfileSaved = false;
$profileID      = isset( $_GET['profileID'] ) ? sanitize_text_field( $_GET['profileID'] ) : '';
$ebay_cat_id    = isset( $_GET['eBayCatID'] ) ? sanitize_text_field( $_GET['eBayCatID'] ) : '';

if ( ! empty( $_POST['ced_ebay_profile_edit_category_dropdown'] ) && isset( $_POST['ced_ebay_profile_edit'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_ebay_profile_edit'] ), 'ced_ebay_profile_edit_page_nonce' ) ) {
	if ( isset( $_POST['add_meta_keys'] ) || isset( $_POST['ced_ebay_profile_save_button'] ) ) {

		global $wpdb;

		$tableName = $wpdb->prefix . 'ced_ebay_profiles';

		$profile_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_ebay_profiles WHERE `id`=%d AND `ebay_user`=%s AND `ebay_site`=%s", $profileID, $user_id, $site_id ), 'ARRAY_A' );
		if ( ! empty( $profile_data ) ) {
			$woo_categories        = ! empty( $profile_data[0]['woo_categories'] ) ? json_decode( $profile_data[0]['woo_categories'], true ) : false;
			$profile_category_data = json_decode( $profile_data[0]['profile_data'], true );
		}
		if ( ! empty( $woo_categories ) && is_array( $woo_categories ) ) {
			foreach ( $woo_categories as $key => $in_db_woo_cat ) {
				delete_term_meta( $in_db_woo_cat, 'ced_ebay_profile_created_' . $user_id . '>' . $site_id );
				delete_term_meta( $in_db_woo_cat, 'ced_ebay_profile_id_' . $user_id . '>' . $site_id );
				delete_term_meta( $in_db_woo_cat, 'ced_ebay_mapped_category_' . $user_id . '>' . $site_id );
				delete_term_meta( $in_db_woo_cat, 'ced_ebay_profile_name_' . $user_id . '>' . $site_id );
			}
		}
		$profile_category_data = isset( $profile_category_data ) ? $profile_category_data : '';
		$profile_category_id   = isset( $profile_category_data['_umb_ebay_category']['default'] ) ? sanitize_text_field( $profile_category_data['_umb_ebay_category']['default'] ) : '';
		$profile_category_id   = intval( $profile_category_id );

		if ( ! is_numeric( $profile_category_id ) ) {
			// reject input if it's not a valid integer
			return;
		}
		$sanitized_array = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );

		$woo_categories_mapped = isset( $sanitized_array['ced_ebay_profile_edit_category_dropdown'] ) ? $sanitized_array['ced_ebay_profile_edit_category_dropdown'] : false;
		if ( ! empty( $woo_categories_mapped ) && is_array( $woo_categories_mapped ) ) {
			foreach ( $woo_categories_mapped as $key => $woo_mapped_cat ) {
				update_term_meta( $woo_mapped_cat, 'ced_ebay_profile_created_' . $user_id . '>' . $site_id, 'yes' );
				update_term_meta( $woo_mapped_cat, 'ced_ebay_profile_id_' . $user_id . '>' . $site_id, $profileID );
				update_term_meta( $woo_mapped_cat, 'ced_ebay_mapped_category_' . $user_id . '>' . $site_id, $ebay_cat_id );
				update_term_meta( $woo_mapped_cat, 'ced_ebay_profile_name_' . $user_id . '>' . $site_id, $profile_data[0]['profile_name'] );
			}
			$woo_categories_mapped = json_encode( $woo_categories_mapped );
		}
		$is_active                    = isset( $sanitized_array['profile_status'] ) ? 'Active' : 'Inactive';
		$marketplaceName              = isset( $sanitized_array['marketplaceName'] ) ? ( $sanitized_array['marketplaceName'] ) : 'all';
		$updateinfo                   = array();
		$ced_ebay_custom_item_aspects = isset( $sanitized_array['custom_item_aspects'] ) ? ( $sanitized_array['custom_item_aspects'] ) : array();

		$common = isset( $sanitized_array['ced_ebay_required_common'] ) ? ( $sanitized_array['ced_ebay_required_common'] ) : array();
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

				isset( $sanitized_array[ $key ] ) ? $arrayToSave['default'] = ( $sanitized_array[ $key ] ) : $arrayToSave['default'] = '';
			}
			if ( '_umb_ebay_category' == $key && '' == $profileID ) {
				$category_id = isset( $sanitized_array['ced_ebay_level3_category'] ) ? ( $sanitized_array['ced_ebay_level3_category'] ) : '';
				$category_id = isset( $category_id[0] ) ? $category_id[0] : '';
				isset( $sanitized_array[ $key ][0] ) ? $arrayToSave['default'] = $category_id : $arrayToSave['default'] = '';
			}
			isset( $sanitized_array[ $key . '_attibuteMeta' ] ) ? $arrayToSave['metakey'] = ( $sanitized_array[ $key . '_attibuteMeta' ] )
				: $arrayToSave['metakey'] = 'null';
			$updateinfo[ $key ]           = $arrayToSave;
		}
		$updateinfo['selected_product_id']           = isset( $sanitized_array['selected_product_id'] ) ? ( $sanitized_array['selected_product_id'] ) : '';
		$updateinfo['selected_product_name']         = isset( $sanitized_array['ced_sears_pro_search_box'] ) ? ( $sanitized_array['ced_sears_pro_search_box'] ) : '';
		$updateinfo['_umb_ebay_category']['default'] = $profile_category_id;
		$updateinfo['custom_item_aspects']           = $ced_ebay_custom_item_aspects;
		$updateinfo                                  = json_encode( $updateinfo );
		if ( $profileID ) {
			global $wpdb;
			$tableName = $wpdb->prefix . 'ced_ebay_profiles';
			$wpdb->update(
				$tableName,
				array(
					'profile_status' => $is_active,
					'profile_data'   => $updateinfo,
					'woo_categories' => $woo_categories_mapped,
					'ebay_user'      => $user_id,
					'ebay_site'      => $site_id,
				),
				array( 'id' => $profileID )
			);

		}

		if ( '' === $wpdb->last_error ) {
			$isProfileSaved = true;
		}
	}
} elseif ( ! empty( $_POST ) ) {
	?>
<div class="notice notice-error">
	<p>Unable to save the profile. It looks like you haven't assigned any WooCommerce Category.</p>
	</div>
		<?php

}

global $wpdb;

$tableName = $wpdb->prefix . 'ced_ebay_profiles';

$profile_data       = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_ebay_profiles WHERE `id`=%d AND `ebay_user`=%s AND `ebay_site`=%s", $profileID, $user_id, $site_id ), 'ARRAY_A' );
$all_woo_cat_mapped = $wpdb->get_results( $wpdb->prepare( "SELECT woo_categories,id FROM {$wpdb->prefix}ced_ebay_profiles WHERE `id`!= %d AND `ebay_user`=%s AND `ebay_site`=%s", $profileID, $user_id, $site_id ), 'ARRAY_A' );
if ( ! empty( $all_woo_cat_mapped ) && is_array( $all_woo_cat_mapped ) ) {
	$woo_categories_to_exclude       = array();
	$get_all_woo_categories_term_ids = array();
	$get_all_woo_categories_terms    = get_terms( 'product_cat' );
	if ( ! empty( $get_all_woo_categories_terms ) ) {
		foreach ( $get_all_woo_categories_terms as $key => $woo_cat_terms ) {
			if ( ! empty( $woo_cat_terms->term_id ) ) {
				$get_all_woo_categories_term_ids[] = $woo_cat_terms->term_id;
			}
		}
	}
	if ( ! empty( $get_all_woo_categories_term_ids ) ) {
		foreach ( $all_woo_cat_mapped as $key => $mapped_in_woo ) {
			if ( ! empty( $mapped_in_woo['woo_categories'] ) ) {
				$woo_cat_mappings_in_db = json_decode( $mapped_in_woo['woo_categories'], true );
				if ( ! empty( $woo_cat_mappings_in_db ) && is_array( $woo_cat_mappings_in_db ) ) {
					foreach ( $woo_cat_mappings_in_db as $key_1 => $woo_db_mapping ) {
						if ( ( array_search( $woo_db_mapping, $get_all_woo_categories_term_ids ) ) !== false ) {
							$woo_categories_to_exclude[] = (int) $woo_db_mapping;
						}
					}
				}
			}
		}
	}
}
if ( ! empty( $woo_categories_to_exclude ) && is_array( $woo_categories_to_exclude ) ) {
	$woo_categories_to_exclude = implode( ',', $woo_categories_to_exclude );
} else {
	$woo_categories_to_exclude = array();
}

if ( ! empty( $profile_data ) ) {
	$woo_categories        = ! empty( $profile_data[0]['woo_categories'] ) ? json_decode( $profile_data[0]['woo_categories'], true ) : false;
	$profile_category_data = json_decode( $profile_data[0]['profile_data'], true );
}
$profile_category_data = isset( $profile_category_data ) ? $profile_category_data : '';
$profile_category_id   = isset( $profile_category_data['_umb_ebay_category']['default'] ) ? $profile_category_data['_umb_ebay_category']['default'] : '';
$profile_data          = isset( $profile_data[0] ) ? $profile_data[0] : $profile_data;
$attributes            = wc_get_attribute_taxonomies();
$attrOptions           = array();
$addedMetaKeys         = get_option( 'CedUmbProfileSelectedMetaKeys', false );
$selectDropdownHTML    = '';

global $wpdb;
$results = $wpdb->get_results( "SELECT DISTINCT meta_key FROM {$wpdb->prefix}postmeta WHERE meta_key NOT LIKE '%wcf%' AND meta_key NOT LIKE '%elementor%' AND meta_key NOT LIKE '%_menu%'", 'ARRAY_A' );
foreach ( $results as $key => $meta_key ) {
	$post_meta_keys[] = $meta_key['meta_key'];
}
$custom_prd_attrb = array();
$query            = $wpdb->get_results( $wpdb->prepare( "SELECT `meta_value` FROM  {$wpdb->prefix}postmeta WHERE `meta_key` LIKE %s", '_product_attributes' ), 'ARRAY_A' );
if ( ! empty( $query ) ) {
	foreach ( $query as $key => $db_attribute_pair ) {
		foreach ( maybe_unserialize( $db_attribute_pair['meta_value'] ) as $key => $attribute_pair ) {
			if ( 1 != $attribute_pair['is_taxonomy'] ) {
				$custom_prd_attrb[] = $attribute_pair['name'];
			}
		}
	}
}
if ( $addedMetaKeys && count( $addedMetaKeys ) > 0 ) {
	foreach ( $addedMetaKeys as $metaKey ) {
		$attrOptions[ $metaKey ] = $metaKey;
	}
}
if ( ! empty( $attributes ) ) {
	foreach ( $attributes as $attributesObject ) {
		$attrOptions[ 'umb_pattr_' . $attributesObject->attribute_name ] = $attributesObject->attribute_label;
	}
}
/* select dropdown setup */
ob_start();
$global_options = ! empty( get_option( 'ced_ebay_global_options' ) ) ? get_option( 'ced_ebay_global_options', true ) : array();
$fieldID        = '{{*fieldID}}';
$selectId       = $fieldID . '_attibuteMeta';
// $selectDropdownHTML .= '<label>Get value from</lable>';
$selectDropdownHTML .= '<select class="ced_ebay_search_item_sepcifics_mapping" id="' . $selectId . '" name="' . $selectId . '">';
$selectDropdownHTML .= '<option value="-1">Select</option>';
$selectDropdownHTML .= '<option value="ced_product_tags">Product Tags</option>';
$selectDropdownHTML .= '<option value="ced_product_cat_single">Product Category - Last Category</option>';
$selectDropdownHTML .= '<option value="ced_product_cat_hierarchy">Product Category - Hierarchy</option>';

if ( class_exists( 'ACF' ) ) {
	$acf_fields_posts = get_posts(
		array(
			'posts_per_page' => -1,
			'post_type'      => 'acf-field',
		)
	);

	foreach ( $acf_fields_posts as $key => $acf_posts ) {
		$acf_fields[ $key ]['field_name'] = $acf_posts->post_title;
		$acf_fields[ $key ]['field_key']  = $acf_posts->post_name;
	}
}
if ( is_array( $attrOptions ) ) {
	$selectDropdownHTML .= '<optgroup label="Global Attributes">';
	foreach ( $attrOptions as $attrKey => $attrName ) :
		$selectDropdownHTML .= '<option value="' . $attrKey . '">' . $attrName . '</option>';
	endforeach;
}

if ( ! empty( $custom_prd_attrb ) ) {
	$custom_prd_attrb    = array_unique( $custom_prd_attrb );
	$selectDropdownHTML .= '<optgroup label="Custom Attributes">';
	foreach ( $custom_prd_attrb as $key => $custom_attrb ) {
		$selectDropdownHTML .= '<option value="ced_cstm_attrb_' . esc_attr( $custom_attrb ) . '">' . esc_html( $custom_attrb ) . '</option>';
	}
}

if ( ! empty( $post_meta_keys ) ) {
	$post_meta_keys      = array_unique( $post_meta_keys );
	$selectDropdownHTML .= '<optgroup label="Custom Fields">';
	foreach ( $post_meta_keys as $key => $p_meta_key ) {
		$selectDropdownHTML .= '<option value="' . $p_meta_key . '">' . $p_meta_key . '</option>';
	}
}

if ( ! empty( $acf_fields ) ) {
	$selectDropdownHTML .= '<optgroup label="ACF Fields">';
	foreach ( $acf_fields as $key => $acf_field ) :
		$selectDropdownHTML .= '<option value="acf_' . $acf_field['field_key'] . '">' . $acf_field['field_name'] . '</option>';
	endforeach;
}
$selectDropdownHTML .= '</select>';
$cat_specifics_file  = $wp_upload_dir . 'ebaycat_' . $profile_category_id . '.json';
if ( file_exists( $cat_specifics_file ) ) {
	$available_attribute = json_decode( file_get_contents( $cat_specifics_file ), true );
} else {
	$available_attribute = array();
}
if ( ! empty( $available_attribute ) ) {
	$categoryAttributes = $available_attribute;
	if ( ! empty( get_option( 'ced_ebay_required_item_aspects_for_ebay_category' ) ) ) {
		$required_item_aspects_for_category = get_option( 'ced_ebay_required_item_aspects_for_ebay_category', true );
	} else {
		$required_item_aspects_for_category = array();
	}
	if ( ! empty( $woo_categories ) && is_array( $woo_categories ) ) {
		foreach ( $woo_categories as $wooTermId ) {
			if ( isset( $required_item_aspects_for_category[ $wooTermId ] ) ) {
				unset( $required_item_aspects_for_category[ $wooTermId ] );
			}
			foreach ( $categoryAttributes as $key => $catItemAspect ) {
				if ( true === $catItemAspect['aspectConstraint']['aspectRequired'] ) {
					$required_item_aspects_for_category[ $wooTermId ][ $profile_category_id . '_' . urlencode( $catItemAspect['localizedAspectName'] ) ] = array(
						'key'  => $profile_category_id . '_' . urlencode( $catItemAspect['localizedAspectName'] ),
						'name' => $catItemAspect['localizedAspectName'],
					);
				}
			}
		}
	}

	if ( ! empty( $required_item_aspects_for_category ) && is_array( $required_item_aspects_for_category ) ) {
		update_option( 'ced_ebay_required_item_aspects_for_ebay_category', $required_item_aspects_for_category );
	}
} else {
	$ebayCategoryInstance    = Ced\Ebay\CedGetCategories::get_instance( $site_id, $rsid );
	$categoryAttributes      = $ebayCategoryInstance->_getCatSpecifics( $profile_category_id );
	$categoryAttributes_json = json_encode( $categoryAttributes );
	if ( ! empty( get_option( 'ced_ebay_required_item_aspects_for_ebay_category' ) ) ) {
		$required_item_aspects_for_category = get_option( 'ced_ebay_required_item_aspects_for_ebay_category', true );
	} else {
		$required_item_aspects_for_category = array();
	}
	if ( ! empty( $woo_categories ) && is_array( $woo_categories ) ) {
		foreach ( $woo_categories as $wooTermId ) {
			if ( isset( $required_item_aspects_for_category[ $wooTermId ] ) ) {
				unset( $required_item_aspects_for_category[ $wooTermId ] );
			}
			foreach ( $categoryAttributes as $key => $catItemAspect ) {
				if ( true === $catItemAspect['aspectConstraint']['aspectRequired'] ) {
					$required_item_aspects_for_category[ $wooTermId ][ $profile_category_id . '_' . urlencode( $catItemAspect['localizedAspectName'] ) ] = array(
						'key'  => $profile_category_id . '_' . urlencode( $catItemAspect['localizedAspectName'] ),
						'name' => $catItemAspect['localizedAspectName'],
					);
				}
			}
		}
	}

	if ( ! empty( $required_item_aspects_for_category ) && is_array( $required_item_aspects_for_category ) ) {
		update_option( 'ced_ebay_required_item_aspects_for_ebay_category', $required_item_aspects_for_category );
	}
	$cat_specifics_file = $wp_upload_dir . 'ebaycat_' . $profile_category_id . '.json';
	if ( file_exists( $cat_specifics_file ) ) {
		wp_delete_file( $cat_specifics_file );
	}
	file_put_contents( $cat_specifics_file, $categoryAttributes_json );


}
$ebayCategoryInstance = Ced\Ebay\CedGetCategories::get_instance( $site_id, $rsid );
$limit                = array( 'ConditionEnabled', 'ConditionValues' );
$getCatFeatures       = $ebayCategoryInstance->_getCatFeatures( $profile_category_id);
if(is_wp_error($getCatFeatures)){
	wp_send_json_error(
		array(
			'message' => $getCatFeatures->get_error_message(),
		)
	);
}
$getCatFeatures_json  = json_encode( $getCatFeatures );
$cat_features_file    = $wp_upload_dir . 'ebaycatfeatures_' . $profile_category_id . '.json';
if ( file_exists( $cat_features_file ) ) {
	wp_delete_file( $cat_features_file );
}
file_put_contents( $cat_features_file, $getCatFeatures_json );
$getCatFeatures = isset( $getCatFeatures[0] ) ? $getCatFeatures[0] : false;
if ( ! empty( $woo_categories ) && is_array( $woo_categories ) && ! empty( $getCatFeatures['ConditionValues'] ) ) {
	if ( ! empty( get_option( 'ced_ebay_profiles_assigned_to_categories' ) ) ) {
		$existingConditionValuesToTermIdsMapping = get_option( 'ced_ebay_profiles_assigned_to_categories', true );
	} else {
		$existingConditionValuesToTermIdsMapping = array();
	}
	foreach ( $woo_categories as $wooTermId ) {
		if ( isset( $getCatFeatures['SpecialFeatures']['Condition'] ) && ! isset( $getCatFeatures['SpecialFeatures']['Condition'][0] ) ) {
			$tempSpecialFeatures = array();
			$tempSpecialFeatures = $getCatFeatures['SpecialFeatures']['Condition'];
			unset( $getCatFeatures['SpecialFeatures']['Condition'] );
			$getCatFeatures['SpecialFeatures']['Condition'][] = $tempSpecialFeatures;
		}
		if ( ! empty( $getCatFeatures['SpecialFeatures']['Condition'] ) && is_array( $getCatFeatures['SpecialFeatures']['Condition'] ) ) {
			$existingConditionValuesToTermIdsMapping[ $wooTermId ] = array_merge( $getCatFeatures['itemConditions'], $getCatFeatures['SpecialFeatures']['Condition'] );
		} else {
			$existingConditionValuesToTermIdsMapping[ $wooTermId ] = $getCatFeatures['itemConditions'];
		}
	}
	if ( ! empty( $existingConditionValuesToTermIdsMapping ) && is_array( $existingConditionValuesToTermIdsMapping ) ) {
		update_option( 'ced_ebay_profiles_assigned_to_categories', $existingConditionValuesToTermIdsMapping );
	}
}
$attribute_data = array();


$productFieldInstance = Ced\Ebay\CedeBayProductsFields::get_instance();
$fields               = $productFieldInstance->ced_ebay_get_custom_products_fields( $user_id, $profile_category_id, $site_id );
$user_id              = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';


?>
<form action="" method="post">
<?php
if ( $isProfileSaved ) {
	?>
	<div class="notice notice-success">
	<p> We’ve saved your profile data. </p>
	</div>
	<?php
}
?>
	<div class="ced_ebay_profile_details_wrapper components-panel ced-padding">
		<div class="ced_ebay_profile_details_fields">
			<table class="ced-ebay-profile-edit-table">
				<thead>
			<div class="ced-ebay-v2-header-content">
				<div class="ced-ebay-v2-title">

					<h2 style="font-size:18px;"><b><?php echo esc_attr_e( $profile_data['profile_name'] ); ?></b></h2>
				</div>
				<div class="ced-ebay-v2-actions">
					<?php
					if ( ! empty( $woo_categories ) && is_array( $woo_categories ) ) {
						?>
				<a class="ced-ebay-v2-btn" target="_blank" href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=ebay&section=products-view&user_id=' . $user_id . '&sid=' . $site_id . '&rsid=' . $rsid['remote_shop_id'] . '&profileID=' . $profileID . '&eBayCatID=' . $ebay_cat_id ) ); ?>">
					Filter Products				</a> | 
					<a class="ced-ebay-v2-btn" href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=ebay&section=product-template&user_id=' . $user_id . '&sid=' . $site_id . '&rsid=' . $rsid['remote_shop_id'] . '&profileID=' . $profileID . '&eBayCatID=' . $ebay_cat_id ) ); ?>">
					Create New Template</a>
						<?php
					}
					?>
			

			</div>
		</div>
		<hr>
</div>

	<!-- End Alert Success -->


	</div>
	</div>
	
				</thead>
				<tbody>

<th colspan="3" class="ced-profile-general-settings-heading" style="text-align:left;margin:0;">

<label style="font-size: 1.25rem;color: #6574cd;" ><?php esc_attr_e( 'GENERAL DETAILS', 'ebay-integration-for-woocommerce' ); ?></label>

</th>

<tr class="form-field _umb_id_type_field ">
<input type="hidden" name="ced_ebay_required_common[]" value="_umb_ebay_mapped_woocommerce_category">
<td>
				<label class="ced_ebay_show_tippy" data-tippy-content="Add/Remove the WooCommerce categories associated with this profile.">Assigned WooCommerce Category</label>
			</td>
	<td>
	<?php
	$dropdown_cat_args      = array(
		'name'              => 'ced_ebay_profile_edit_category_dropdown[]',
		'id'                => 'ced_ebay_profile_edit_category_dropdown_select',
		'class'             => 'select2 form-control',
		'show_count'        => 1,
		'hierarchical'      => 1,
		'option_none_value' => '-1',
		'selected'          => false,
		'hide_empty'        => false,
		'taxonomy'          => 'product_cat',
		'echo'              => 0,
		'exclude'           => $woo_categories_to_exclude,
	);
	$dropdown               = wp_dropdown_categories( $dropdown_cat_args );
	$multi                  = str_replace( '<select', '<select multiple ', $dropdown );
	$multi_dropdown         = preg_replace( '#class *= *["\']?([^"\']*)"#', '', $multi );
	$already_mapped_woo_cat = $woo_categories;
	if ( null !== $multi_dropdown ) {
		foreach ( $already_mapped_woo_cat as $key => $mapped_woo_cat ) {
			// add the selected to each selected value
			$multi_dropdown = str_replace(
				'<option  value="' . esc_attr( $mapped_woo_cat ) . '"',
				'<option value="' . esc_attr( $mapped_woo_cat ) . '" selected="selected"',
				$multi_dropdown
			);
		}
	}
	print_r( $multi_dropdown );






	?>
	</td>
	<td></td>
</tr>
	

					<?php
					$requiredInAnyCase = array( '_umb_id_type', '_umb_id_val', '_umb_brand' );
					global $global_CED_ebay_Render_Attributes;
					$marketPlace = 'ced_ebay_required_common';
					$productID   = 0;
					$categoryID  = '';
					$indexToUse  = 0;
					?>


						<?php
						if ( ! empty( $profile_data ) ) {
							$data = json_decode( $profile_data['profile_data'], true );
						}
						foreach ( $fields as $value ) {
							$isText   = false;
							$field_id = isset( $value['fields']['id'] ) ? trim( $value['fields']['id'], '_' ) : '';
							if ( isset( $value['fields']['id'] ) && in_array( $value['fields']['id'], $requiredInAnyCase ) ) {
								$attributeNameToRender  = ucfirst( $value['fields']['label'] );
								$attributeNameToRender .= '<span class="ced_ebay_wal_required">' . __( '[ Required ]', 'ebay-integration-for-woocommerce' ) . '</span>';
							} else {
								$attributeNameToRender = isset( $value['fields']['id'] ) ? ucfirst( $value['fields']['label'] ) : '';
							}
							$default           = isset( $value['fields']['id'] ) && isset( $data[ $value['fields']['id'] ]['default'] ) ? $data[ $value['fields']['id'] ]['default'] : '';
							$field_description = ! empty( $value['fields']['description'] ) ? $value['fields']['description'] : '';
							echo '<tr class="form-field _umb_id_type_field ">';

							if ( isset( $value['type'] ) && '_select' == $value['type'] ) {
								$valueForDropdown = $value['fields']['options'];
								if ( '_umb_id_type' == $value['fields']['id'] ) {
									unset( $valueForDropdown['null'] );
								}
								$productFieldInstance->renderDropdownHTML(
									$user_id,
									$site_id,
									$field_id,
									$attributeNameToRender,
									$valueForDropdown,
									$categoryID,
									$productID,
									$marketPlace,
									$indexToUse,
									array(
										'case'  => 'profile',
										'value' => $default,
									),
									'',
									'SINGLE',
									$field_description
								);
								$isText = false;
							} elseif ( isset( $value['type'] ) && '_text_input' == $value['type'] ) {
								$productFieldInstance->renderInputTextHTML(
									$user_id,
									$site_id,
									$field_id,
									$attributeNameToRender,
									$categoryID,
									$productID,
									$marketPlace,
									$indexToUse,
									array(
										'case'  => 'profile',
										'value' => $default,
									),
									false,
									'SINGLE',
									$field_description
								);
								$isText = true;
							} elseif ( isset( $value['type'] ) && '_hidden' == $value['type'] ) {
								$productFieldInstance->renderInputTextHTMLhidden(
									$user_id,
									$site_id,
									$field_id,
									$attributeNameToRender,
									$categoryID,
									$productID,
									$marketPlace,
									$indexToUse,
									array(
										'case'  => 'profile',
										'value' => $profile_category_id,
									),
									false,
									$field_description
								);
								$isText = false;
							} else {
								$isText = false;
							}

							echo '<td>';
							if ( $isText ) {
								$previousSelectedValue = 'null';
								if ( isset( $data[ $value['fields']['id'] ]['metakey'] ) && 'null' != $data[ $value['fields']['id'] ]['metakey'] ) {
									$previousSelectedValue = $data[ $value['fields']['id'] ]['metakey'];
								}
								$updatedDropdownHTML = str_replace( '{{*fieldID}}', $value['fields']['id'], $selectDropdownHTML );
								$updatedDropdownHTML = str_replace( 'value="' . $previousSelectedValue . '"', 'value="' . $previousSelectedValue . '" selected="selected"', $updatedDropdownHTML );
								print_r( $updatedDropdownHTML );
							}
							echo '</td>';
							echo '</tr>';
						}
						?>
						</tbody>
					</table>
										<table class="ced-ebay-profile-edit-table">
						<tbody>

						<th colspan="3" class="ced-profile-item-aspects-heading" style="text-align:left;margin:0;">

						<label style="font-size: 1.25rem;color: #6574cd;" ><?php esc_attr_e( 'ITEM ASPECTS', 'ebay-integration-for-woocommerce' ); ?></label>
						<p style="font-size:16px;text-style:none;">Specify additional details about products listed on eBay based on the selected eBay Category.</p>
						<p style="font-size:14px;"><span style="color:red;">[Required]</span> - Filling or mapping fields with this tag is REQUIRED for successfully listing a product on eBay.</p>
						<p style="font-size:14px;"><span style="color:green;">[Multiple]</span> - For such fields, you can specify multiple comma-separated values.</p>

						</th>

					<?php
					if ( ! empty( $categoryAttributes ) ) {
						if ( ! empty( $profile_data ) ) {
							$data = json_decode( $profile_data['profile_data'], true );
						}
						foreach ( $categoryAttributes as $key1 => $value ) {
							$isText   = true;
							$field_id = trim( urlencode( $value['localizedAspectName'] ) );
							if ( isset( $global_options[ $user_id ][ $site_id ][ $field_id ]['custom_value'] ) ) {
								$global_value = $global_options[ $user_id ][ $site_id ][ $field_id ]['custom_value'];
							} else {
								$global_value = '';
							}
							$default  = isset( $data[ $profile_category_id . '_' . $field_id ] ) ? $data[ $profile_category_id . '_' . $field_id ] : '';
							$default  = isset( $default['default'] ) ? $default['default'] : '';
							$required = '';
							echo '<tr class="form-field _umb_brand_field ">';

							if ( 'SELECTION_ONLY' == $value['aspectConstraint']['aspectMode'] && isset( $value['aspectValues'] ) ) {
								$cardinality          = 'SINGLE';
								$valueForDropdown     = $value['aspectValues'];
								$tempValueForDropdown = array();
								foreach ( $valueForDropdown as $key => $_value ) {
									$tempValueForDropdown[ $_value['localizedValue'] ] = $_value['localizedValue'];
								}
								$valueForDropdown = $tempValueForDropdown;

								if ( 'MULTI' == $value['aspectConstraint']['itemToAspectCardinality'] ) {
									$cardinality = 'MULTI';
								}
								if ( 'true' == $value['aspectConstraint']['aspectRequired'] ) {
									$required = 'required';
								}

								$productFieldInstance->renderDropdownHTML(
									$user_id,
									$site_id,
									$field_id,
									ucfirst( $value['localizedAspectName'] ),
									$valueForDropdown,
									$profile_category_id,
									$productID,
									$marketPlace,
									$indexToUse,
									array(
										'case'  => 'profile',
										'value' => $default,
									),
									$required,
									$cardinality
								);
								$isText = false;
							} elseif ( 'COMBO_BOX' == isset( $value['input_type'] ) ? $value['input_type'] : '' ) {
								$cardinality = 'SINGLE';
								$isText      = true;
								if ( 'true' == $value['aspectConstraint']['aspectRequired'] ) {
									$required = 'required';
								}
								if ( 'MULTI' == $value['aspectConstraint']['itemToAspectCardinality'] ) {
									$cardinality = 'MULTI';
								}
								$productFieldInstance->renderInputTextHTML(
									$user_id,
									$site_id,
									$field_id,
									ucfirst( $value['localizedAspectName'] ),
									$profile_category_id,
									$productID,
									$marketPlace,
									$indexToUse,
									array(
										'case'  => 'profile',
										'value' => $default,
									),
									$required,
									$cardinality
								);
							} elseif ( 'text' == isset( $value['input_type'] ) ? $value['input_type'] : '' ) {
								$cardinality = 'SINGLE';
								$isText      = true;
								if ( 'true' == $value['aspectConstraint']['aspectRequired'] ) {
									$required = 'required';
								}
								if ( 'MULTI' == $value['aspectConstraint']['itemToAspectCardinality'] ) {
									$cardinality = 'MULTI';
								}
								$productFieldInstance->renderInputTextHTML(
									$user_id,
									$site_id,
									$field_id,
									ucfirst( $value['localizedAspectName'] ),
									$profile_category_id,
									$productID,
									$marketPlace,
									$indexToUse,
									array(
										'case'  => 'profile',
										'value' => $default,
									),
									$required,
									$cardinality
								);
							} else {
								$cardinality = 'SINGLE';
								$isText      = true;
								if ( 'true' == $value['aspectConstraint']['aspectRequired'] ) {
									$required = 'required';
								}
								if ( 'MULTI' == $value['aspectConstraint']['itemToAspectCardinality'] ) {
									$cardinality = 'MULTI';
								}
								$productFieldInstance->renderInputTextHTML(
									$user_id,
									$site_id,
									$field_id,
									ucfirst( $value['localizedAspectName'] ),
									$profile_category_id,
									$productID,
									$marketPlace,
									$indexToUse,
									array(
										'case'  => 'profile',
										'value' => $default,
									),
									$required,
									$cardinality
								);
							}


							echo '<td>';
							if ( $isText ) {
								$previousSelectedValue = 'null';
								if ( isset( $global_options[ $user_id ][ $site_id ][ $field_id ] ) ) {
									$previousSelectedValue = $global_options[ $user_id ][ $site_id ][ $field_id ]['meta_key'];
								}
								if ( isset( $data[ $profile_category_id . '_' . $field_id ] ) && 'null' != $data[ $profile_category_id . '_' . $field_id ] && isset( $data[ $profile_category_id . '_' . $field_id ]['metakey'] ) && '-1' != $data[ $profile_category_id . '_' . $field_id ]['metakey'] ) {

									$previousSelectedValue = $data[ $profile_category_id . '_' . $field_id ]['metakey'];
								}


								$updatedDropdownHTML = str_replace( '{{*fieldID}}', $profile_category_id . '_' . $field_id, $selectDropdownHTML );
								$updatedDropdownHTML = str_replace( 'value="' . $previousSelectedValue . '"', 'value="' . $previousSelectedValue . '" selected="selected"', $updatedDropdownHTML );
								print_r( $updatedDropdownHTML );
							}
							echo '</td>';


							echo '</tr>';
						}
					}
					if ( isset( $getCatFeatures ) && ! empty( $getCatFeatures ) ) {
						$isText = false;
						if ( isset( $getCatFeatures['itemConditions'] ) ) {
							$field_id = 'Condition';
							$isText   = true;
							if ( isset( $getCatFeatures['SpecialFeatures']['Condition'] ) && ! isset( $getCatFeatures['SpecialFeatures']['Condition'][0] ) ) {
								$tempSpecialFeatures = array();
								$tempSpecialFeatures = $getCatFeatures['SpecialFeatures']['Condition'];
								unset( $getCatFeatures['SpecialFeatures']['Condition'] );
								$getCatFeatures['SpecialFeatures']['Condition'][] = $tempSpecialFeatures;
							}
							if ( ! empty( $getCatFeatures['SpecialFeatures']['Condition'] ) && is_array( $getCatFeatures['SpecialFeatures']['Condition'] ) ) {
								$valueForDropdown = array_merge( $getCatFeatures['itemConditions'], $getCatFeatures['SpecialFeatures']['Condition'] );
							} else {
								$valueForDropdown = $getCatFeatures['itemConditions'];
							}
							$tempValueForDropdown = array();
							if ( isset( $valueForDropdown[0] ) ) {
								foreach ( $valueForDropdown as $key => $value ) {
									$tempValueForDropdown[ $value['conditionId'] ] = $value['conditionDescription'];
								}
							} else {
								$tempValueForDropdown[ $valueForDropdown['conditionId'] ] = $valueForDropdown['conditionDescription'];
							}
							$valueForDropdown = $tempValueForDropdown;
							$name             = 'Condition';
							$default          = isset( $profile_category_data[ $profile_category_id . '_' . $name ] ) ? $profile_category_data[ $profile_category_id . '_' . $name ] : '';
							$default          = isset( $default['default'] ) ? $default['default'] : '';
							if ( isset( $getCatFeatures['itemConditionRequired'] )  ) {
								$required                                       = true;
								$catFeatureSavingForvalidation[ $categoryID ][] = 'Condition';
								$productFieldInstance->renderDropdownHTML(
									$user_id,
									$site_id,
									'Condition',
									$name,
									$valueForDropdown,
									$profile_category_id,
									$productID,
									$marketPlace,
									$indexToUse,
									array(
										'case'  => 'profile',
										'value' => $default,
									),
									$required
								);
							}
						}

						echo '<td>';
						if ( $isText ) {
							$previousSelectedValue = 'null';
							if ( isset( $global_options[ $user_id ][ $site_id ][ $field_id ] ) ) {
								$previousSelectedValue = $global_options[ $user_id ][ $site_id ][ $field_id ]['meta_key'];
							}
							if ( isset( $data[ $profile_category_id . '_' . $field_id ] ) && 'null' != $data[ $profile_category_id . '_' . $field_id ] && isset( $data[ $profile_category_id . '_' . $field_id ]['metakey'] ) && '-1' != $data[ $profile_category_id . '_' . $field_id ]['metakey'] ) {

								$previousSelectedValue = $data[ $profile_category_id . '_' . $field_id ]['metakey'];
							}
							$updatedDropdownHTML = str_replace( '{{*fieldID}}', $profile_category_id . '_' . $field_id, $selectDropdownHTML );
							$updatedDropdownHTML = str_replace( 'value="' . $previousSelectedValue . '"', 'value="' . $previousSelectedValue . '" selected="selected"', $updatedDropdownHTML );
							print_r( $updatedDropdownHTML );
						}
							echo '</td>';


							echo '</tr>';


					}



					?>
					</tbody>
				</table>

				<table class="ced-ebay-profile-item-aspects-table">
					<tbody>
						<tr>
							<th colspan="3" class="ced-profile-framework-specific-heading" style="text-align:left;margin:0;">
							<label style="font-size: 1.25rem;color: #6574cd;" ><?php esc_attr_e( 'FRAMEWORK SPECIFIC', 'ebay-integration-for-woocommerce' ); ?></label>
							<p style="font-size:16px;">Specify additional details about products listed on eBay based on the selected eBay Category.
							You can only set the values of <span style="color:red;">[Required]</span> Item Aspects and leave other fields in this section.
							To do so, either enter a custom value or get the values from Product Attributes or Custom Fields.</p>
							</th>
						</tr>
						<?php
						if ( ! empty( $profile_data ) ) {
							$data = json_decode( $profile_data['profile_data'], true );
						}
						$productFieldInstance = \Ced\Ebay\CedeBayProductsFields::get_instance();
						$fields               = $productFieldInstance->ced_ebay_get_profile_framework_specific( $profile_category_id );

						foreach ( $fields as $value ) {
							$isText   = false;
							$field_id = trim( $value['fields']['id'], '_' );
							if ( in_array( $value['fields']['id'], $requiredInAnyCase ) ) {
								$attributeNameToRender  = ucfirst( $value['fields']['label'] );
								$attributeNameToRender .= '<span class="ced_ebay_wal_required">' . __( '[ Required ]', 'ebay-integration-for-woocommerce' ) . '</span>';
							} else {
								$attributeNameToRender = ucfirst( $value['fields']['label'] );
							}

							$default           = isset( $data[ $value['fields']['id'] ]['default'] ) && '-1' != $data[ $value['fields']['id'] ]['default'] ? $data[ $value['fields']['id'] ]['default'] : '';
							$field_description = ! empty( $value['fields']['description'] ) ? $value['fields']['description'] : '';
							$required          = isset( $value['required'] ) ? $value['required'] : '';
							echo '<tr class="form-field _umb_id_type_field ">';

							if ( '_select' == $value['type'] ) {

								$default          = '' != $default || '-1' != $default ? $default : $global_options[ $user_id ][ $site_id ][ $value['fields']['global_id'] ]['meta_key'];
								$valueForDropdown = $value['fields']['options'];
								if ( '_umb_id_type' == $value['fields']['id'] ) {
									unset( $valueForDropdown['null'] );
								}
								$productFieldInstance->renderDropdownHTML(
									$user_id,
									$site_id,
									$field_id,
									$attributeNameToRender,
									$valueForDropdown,
									$categoryID,
									$productID,
									$marketPlace,
									$indexToUse,
									array(
										'case'  => 'profile',
										'value' => $default,
									),
									$required,
									'SINGLE',
									$field_description
								);
								$isText = false;
							} elseif ( '_text_input' == $value['type'] ) {
								if ( isset( $global_options[ $user_id ][ $site_id ][ $value['fields']['global_id'] ]['custom_value'] ) ) {
									$default = ! empty( $default ) ? $default : $global_options[ $user_id ][ $site_id ][ $value['fields']['global_id'] ]['custom_value'];
								}
								$productFieldInstance->renderInputTextHTML(
									$user_id,
									$site_id,
									$field_id,
									$attributeNameToRender,
									$categoryID,
									$productID,
									$marketPlace,
									$indexToUse,
									array(
										'case'  => 'profile',
										'value' => $default,
									),
									$required,
									'SINGLE',
									$field_description
								);
								$isText = true;
							} elseif ( '_hidden' == $value['type'] ) {
								$productFieldInstance->renderInputTextHTMLhidden(
									$user_id,
									$site_id,
									$field_id,
									$attributeNameToRender,
									$categoryID,
									$productID,
									$marketPlace,
									$indexToUse,
									array(
										'case'  => 'profile',
										'value' => $profile_category_id,
									),
									'',
									'SINGLE',
									$field_description
								);
								$isText = false;
							} else {
								$isText = false;
							}

							echo '<td>';
							if ( $isText ) {
								$previousSelectedValue = 'null';
								if ( isset( $global_options[ $user_id ][ $site_id ][ $value['fields']['global_id'] ] ) ) {
									$previousSelectedValue = $global_options[ $user_id ][ $site_id ][ $value['fields']['global_id'] ]['meta_key'];
								}

								if ( isset( $data[ $value['fields']['id'] ]['metakey'] ) && 'null' != $data[ $value['fields']['id'] ]['metakey'] && '-1' != $data[ $value['fields']['id'] ]['metakey'] ) {
									$previousSelectedValue = $data[ $value['fields']['id'] ]['metakey'];
								}
								$updatedDropdownHTML = str_replace( '{{*fieldID}}', $value['fields']['id'], $selectDropdownHTML );
								$updatedDropdownHTML = str_replace( 'value="' . $previousSelectedValue . '"', 'value="' . $previousSelectedValue . '" selected="selected"', $updatedDropdownHTML );
								print_r( $updatedDropdownHTML );
							}
							echo '</td>';
							echo '</tr>';
						}
						?>
					</tr>
				</tbody>

			</table>
			<button type="submit" style="margin:50px 5px;" class="components-button is-primary button-next" name="ced_ebay_profile_save_button" ><?php esc_attr_e( 'Save Profile Data', 'ebay-integration-for-woocommerce' ); ?></button>

		</div>
	</div>
	<?php wp_nonce_field( 'ced_ebay_profile_edit_page_nonce', 'ced_ebay_profile_edit' ); ?>
	<?php
		echo '<script>jQuery(".ced_ebay_multi_select_item_aspects").selectWoo({});jQuery( ".ced_ebay_item_specifics_options" ).selectWoo({width: "90%"});jQuery( ".ced_ebay_search_item_sepcifics_mapping" ).selectWoo({width: "90%"});</script>';
	?>
</form>

<script>
						
	jQuery(document).on("keydown", "form", function(event) { return event. key != "Enter"; }); 
	
	jQuery(".ced_ebay_search_item_sepcifics_mapping").selectWoo( {
		dropdownPosition: 'below',  
		dropdownAutoWidth : false,
		placeholder: 'Select...',
		
	});
	
	jQuery(".ced_ebay_item_specifics_options").selectWoo({
		allowClear: true,
		dropdownPosition: 'below',
		dropdownAutoWidth : false,
		placeholder: 'Select...',
	});


	jQuery("#ced_ebay_profile_edit_category_dropdown_select").selectWoo({
		allowClear: true,
	});

	tippy('.ced_ebay_show_tippy', {
		arrow: true
	});

	// jQuery('.ced_ebay_fill_custom_value').selectWoo();
	
	jQuery(".js-example-tags").select2({
		tags: true
	});


	jQuery(".test").selectWoo({
		// height:'40px',
		tags: true,
		allowClear: true, //
		closeOnSelect: true,
		// tokenSeparators: [',', ' '],
		// templateSelection: function(selection) {

		// 	console.log(selection)
		// if(selection.selected) {
		//     return $.parseHTML('<span class="customclass">' + selection.text + '</span>');
		// }
		// else {
		//     return $.parseHTML('<span class="customclass">' + selection.text + '</span>');
		// // }
		//}
	});

	jQuery('.test').on("select2:select", function(e) { 
		e.preventDefault();
		let id = jQuery(this).attr('id');
		let sibling = jQuery('#' + id).next(); 

		let field = sibling.find('.select2-search__field');
		
		jQuery("li[aria-selected='true']").addClass("customclass");
		jQuery("li[aria-selected='false']").removeClass("customclass");
		jQuery('.select2-search-choice:not(.my-custom-css)', this).addClass('my-custom-css');
		jQuery(field).val("");
		
	});

	jQuery(".test").on("select2:unselect", function(e) { 

		jQuery("li[aria-selected='false']").removeClass("customclass");
	});

</script>


<style>


.ced_ebay_add_custom_item_aspect_heading{
	text-align: left;
}

.selectRow {
	display : block;
	padding : 20px;
}
.select2-container {
	width: 200px;
}

.customclass{
	color:grey;
}
</style>

