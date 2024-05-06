<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( empty( get_option( 'ced_ebay_user_access_token' ) ) ) {
	wp_redirect( get_admin_url() . 'admin.php?page=ced_ebay' );
}

$fileHeader = CED_EBAY_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $fileHeader ) ) {
	require_once $fileHeader;
}

	$fileCategory = CED_EBAY_DIRPATH . 'admin/ebay/lib/cedGetcategories.php';
if ( file_exists( $fileCategory ) ) {
	require_once $fileCategory;
}
$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
$shop_data = ced_ebay_get_shop_data( $user_id );
if ( ! empty( $shop_data ) ) {
	$siteID      = $shop_data['site_id'];
	$token       = $shop_data['access_token'];
	$getLocation = $shop_data['location'];
}

$cedCatInstance          = CedGetCategories::get_instance( $siteID, $token );
$getCategoryTree         = $cedCatInstance->_getCategoryTree();
$ebay_site_category_tree = array();
$temp_ebay_cat_array     = array();
if ( 'Success' == $getCategoryTree['Ack'] ) {
	if ( ! empty( $getCategoryTree['CategoryArray']['Category'] ) ) {
		$temp_ebay_cat_array = $getCategoryTree['CategoryArray']['Category'];
		foreach ( $temp_ebay_cat_array as $key => $ebay_cat ) {
			$ebay_site_category_tree[ $ebay_cat['CategoryID'] ] = $ebay_cat;
		}
	}
}

// echo '<pre>';print_r($ebay_site_category_tree);die;

// wc_get_logger()->info()

function ced_ebay_make_nested_category_data( $data ) {

	$nested = array();

	// loop over each category
	foreach ( $data as &$category ) {
		// if there is no children array, add it
		if ( ! isset( $category['Children'] ) ) {
			$category['Children'] = array();
		}
		// check if there is a matching parent
		if ( isset( $data[ $category['CategoryParentID'] ] ) && 1 != $category['CategoryLevel'] ) {
			// add this under the parent as a child by reference
			if ( ! isset( $data[ $category['CategoryParentID'] ]['Children'] ) ) {
				$data[ $category['CategoryParentID'] ]['Children'] = array();
			}
			$data[ $category['CategoryParentID'] ]['Children'][ $category['CategoryID'] ] = &$category;
			// else, no parent found, add at top level
		} else {
			$nested[ $category['CategoryID'] ] = &$category;
		}
	}
	unset( $category );
	return $nested;
}

function ced_ebay_flatten_nested_category_data( $nested, $parent = '' ) {
	$out = array();
	foreach ( $nested as $category ) {
		$categoryName = $parent . $category['CategoryName'];
		if ( isset( $category['LeafCategory'] ) && true == $category['LeafCategory'] ) {
			$out[ $category['CategoryID'] ] = $categoryName;
		}
		$out += ced_ebay_flatten_nested_category_data( $category['Children'], $categoryName . ' > ' );
		// recurse for each child
	}
	return $out;
}



$ebay_site_category_structure = ced_ebay_flatten_nested_category_data( ced_ebay_make_nested_category_data( $ebay_site_category_tree ) );

?>

<div class="ced_ebay_profile_details_wrapper">
		<div class="ced_ebay_profile_details_fields">
			<table>
				<thead>
				<div class="ced-ebay-v2-header">
			
			<div class="ced-ebay-v2-header-content">
				<div class="ced-ebay-v2-title">

					<h2 style="font-size:18px;"><b>Add Custom Profile</h2>
				</div>
				<div class="ced-ebay-v2-actions">
				<a class="ced-ebay-v2-btn" href="<?php echo esc_attr( admin_url( 'admin.php?page=ced_ebay&section=profiles-view&user_id=' . $user_id ) ); ?>">
					Go Back					</a>
				<a class="ced-ebay-v2-btn" href="https://docs.woocommerce.com/document/ebay-integration-for-woocommerce/#section-8" target="_blank">
					Documentation					</a>

			</div>
		</div>
</div>
<div class="ced-ebay-bootstrap-wrapper">

<div class="container mw-100">
<div class="row">
<div class="card" style="min-width:100% !important;">
	<div class="card-body">
		<div class="row gutters">
			<div class="col-12">
				<h3 class="mb-3 text-primary">Profile Details
				<p class="text-secondary">this is a description of the section</p>

				</h3>
			</div>
			<div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-12">
				<div class="form-group">
					<label for="fullName">Profile Name</label>
					<input type="text" class="form-control" id="fullName" placeholder="Enter full name">
				</div>
			</div>
			<div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-12">
				<div class="form-group">
					<label for="eMail">WooCommerce Category</label>
					<?php


					wp_dropdown_categories(
						array(
							'show_option_all' => esc_html__( 'Select Category', 'woocommerce' ),
							'orderby'         => 'name',
							'hierarchical'    => 1,
							'echo'            => 1,
							'value_field'     => 'term_id',
							'taxonomy'        => 'product_cat',
							'name'            => 'ced_ebay_custom_profile_woo_category',
							'class'           => 'ced_ebay_custom_profile_woo_category form-control',
						)
					);

					?>
				</div>
			</div>
			<div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-12">
				<div class="form-group">
					<label for="ebay_site_category">eBay Site Category</label>
					<select class="form-control ced_ebay_cst_prf_ebay_cat">
						<?php
						if ( ! empty( $ebay_site_category_structure ) ) {
							foreach ( $ebay_site_category_structure as $key => $ebay_site_cat ) {
								?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $ebay_site_cat ); ?></option>
								<?php
							}
						}
						?>
					</select>
				</div>
			</div>
		</div>
		<hr/>

		<div class="row gutters">
			<div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
				<h6 class="mt-3 mb-2 text-primary">Address</h6>
			</div>
			<div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-12">
				<div class="form-group">
					<label for="Street">Street</label>
					<input type="name" class="form-control" id="Street" placeholder="Enter Street">
				</div>
			</div>
			<div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-12">
				<div class="form-group">
					<label for="ciTy">City</label>
					<input type="name" class="form-control" id="ciTy" placeholder="Enter City">
				</div>
			</div>
			<div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-12">
				<div class="form-group">
					<label for="sTate">State</label>
					<input type="text" class="form-control" id="sTate" placeholder="Enter State">
				</div>
			</div>
			<div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-12">
				<div class="form-group">
					<label for="zIp">Zip Code</label>
					<input type="text" class="form-control" id="zIp" placeholder="Zip Code">
				</div>
			</div>
		</div>
		<div class="row gutters">
			<div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
				<div class="text-right">
					<button type="button" id="submit" name="submit" class="btn btn-secondary">Cancel</button>
					<button type="button" id="submit" name="submit" class="btn btn-primary">Update</button>
				</div>
			</div>
		</div>
	</div>
</div>
</div>
</div>
</div>


<style>
	.bootstrap-select .dropdown-menu { max-width: 100% !important; }
</style>
