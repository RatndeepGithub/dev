<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
if ( empty( get_option( 'ced_ebay_user_access_token' ) ) ) {
	wp_redirect( get_admin_url() . 'admin.php?page=ced_ebay' );
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

$file = CED_EBAY_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $file ) ) {
	require_once $file;
}

class Ced_Ebay_Import_Ebay_Products extends WP_List_Table {


	public static $_instance;
	/**
	 * Ced_EBay_Config Instance.
	 *
	 * Ensures only one instance of Ced_EBay_Config is loaded or can be loaded.

	 * @since 1.0.0
	 * @static
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {

		$this->loadDependency();
		parent::__construct(
			array(
				'singular' => __( 'Import Product', 'ebay-integration-for-woocommerce' ), // singular name of the listed records
				'plural'   => __( 'Import Products', 'ebay-integration-for-woocommerce' ), // plural name of the listed records
				'ajax'     => false, // does this table support ajax?
			)
		);
	}

	public function loadDependency() {

		$file = CED_EBAY_DIRPATH . 'admin/partials/header.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}

		$file_ebayUpload = CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';

		if ( file_exists( $file_ebayUpload ) ) {
			require_once $file_ebayUpload;
		}
	}

	public function get_columns() {
		$columns = array(

			'cb'             => '<input type="checkbox" />',
			'valueTitle'     => __( 'Name', 'ced-umb-ebay' ),
			'valueItemPrice' => __( 'Price', 'ced-umb-ebay' ),
			'valueQuantity'  => __( 'Inventory', 'ced-umb-ebay' ),
			'importStatus'   => __( 'Import Status', 'ced-umb-ebay' ),
		);
		return $columns;
	}

	public function column_valueItemPrice( $item ) {
		return sprintf( $item['3'] );
	}

	public function column_valueQuantity( $item ) {
		return sprintf( $item['4'] );
	}

	public function column_importStatus( $item ) {
		if ( isset( $item['5'] ) ) {
			return sprintf( '%s', $item['5'] );
		} else {
			return sprintf( '%s', 'Not Imported' );
		}
	}

	public function column_valueTitle( $item ) {

		if ( isset( $item['5'] ) && '' != $item['5'] ) {
			$woo_product_link = get_edit_post_link( $item['woo_product_id'] );
			$actions          = array(
				'view_in_woo'  => sprintf( '<a href=%s data-itemid=%s class=%s>View in Woo </a>', $woo_product_link, $item['0'], 'ced_ebay_view_woo_product' ),
				'view_on_ebay' => sprintf( '<a href="%s" target="_blank"> View on eBay</a>', $item['6'] ),

			);
		} else {
			$actions = array(

				'view_on_ebay' => sprintf( '<a href="%s" target="_blank"> View on eBay</a>', $item['6'] ),

			);
		}

		return sprintf(
			'%1$s <span style="color:silver">(Product Id:%2$s)</span>%3$s',
			$item['2'],
			$item['0'],
			$this->row_actions( $actions )
		);
	}

	public function column_cb( $item ) {
		$status = isset( $item['5'] ) ? $item['5'] : '';
		if ( ! empty( $status ) ) {
			return sprintf(
				'<input type="checkbox" class="ebay_products_id" name="%1$s[]" value="%2$s" disabled />',
				/*$1%s*/$this->_args['singular'],
				/*$2%s*/$item['0']
			);
		} else {
			return sprintf(
				'<input type="checkbox" class="ebay_products_id" name="%1$s[]" value="%2$s"/>',
				/*$1%s*/$this->_args['singular'],
				/*$2%s*/$item['0']
			);
		}
	}

	protected function bulk_actions( $which = '' ) {
		if ( 'top' == $which ) :
			if ( is_null( $this->_actions ) ) {
				$this->_actions = $this->get_bulk_actions();
				/**
				 * Filters the list table Bulk Actions drop-down.
				 *
				 * The dynamic portion of the hook name, `$this->screen->id`, refers
				 * to the ID of the current screen, usually a string.
				 *
				 * This filter can currently only be used to remove bulk actions.
				 *
				 * @since 3.5.0
				 *
				 * @param array $actions An array of the available bulk actions.
				 */
				$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );
				$two            = '';
			} else {
				$two = '2';
			}

			if ( empty( $this->_actions ) ) {
				return;
			}

			echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . esc_attr( 'Select bulk action' ) . '</label>';
			echo '<select name="action' . esc_attr( $two ) . '" class="bulk-action-selector">';
			echo '<option value="-1">' . esc_attr( 'Bulk Actions' ) . "</option>\n";

			foreach ( $this->_actions as $name => $title ) {
				$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

				echo "\t" . '<option value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . "</option>\n";
			}

			echo "</select>\n";

			submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => 'ced_ebay_bulk_import' ) );
			echo "\n";
		endif;
	}

	public function get_bulk_actions() {
		$actions = array(
			'bulk_import' => __( 'Import', 'ebay-integration-for-woocommerce' ),
		);
		return $actions;
	}

	public function no_items() {
		esc_html_e( 'Looks like you don\'t have any Active eBay Listings.', 'ced-umb-ebay' );
	}

	public function get_count() {
		return $this->count;
	}

	public function prepare_items( $per_page = 25, $PageNumber = 1 ) {
		require_once CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayUpload.php';
		$user_id          = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$pre_flight_check = ced_ebay_pre_flight_check( $user_id );
		if ( ! $pre_flight_check ) {
			return;
		}
		$shop_data = ced_ebay_get_shop_data( $user_id );
		if ( ! empty( $shop_data ) ) {
			$siteID          = $shop_data['site_id'];
				$token       = $shop_data['access_token'];
				$getLocation = $shop_data['location'];
		}
		$count              = 0;
		$PageNumber         = isset( $_GET['paged'] ) ? sanitize_text_field( $_GET['paged'] ) : 1;
		$length             = 25;
		$mainXml            = '
			<?xml version="1.0" encoding="utf-8"?>
			<GetMyeBaySellingRequest xmlns="urn:ebay:apis:eBLBaseComponents">
			  <RequesterCredentials>
			    <eBayAuthToken>' . $token . '</eBayAuthToken>
			  </RequesterCredentials>
			  <ActiveList>
			    <Sort>TimeLeft</Sort>
			    <Pagination>
			     <EntriesPerPage>' . $length . '</EntriesPerPage>
			      <PageNumber>' . $PageNumber . '</PageNumber>
			    </Pagination>
			  </ActiveList>
			</GetMyeBaySellingRequest>';
		$ebayUploadInstance = EbayUpload::get_instance( $siteID, $token );
		$activelist         = $ebayUploadInstance->get_active_products( $mainXml );
		if ( isset( $activelist['ActiveList']['ItemArray']['Item'][0] ) ) {
			foreach ( $activelist['ActiveList']['ItemArray']['Item'] as $key => $value ) {
				$store_product = array();
				$itemId        = $value['ItemID'];
				$store_product = get_posts(
					array(
						'numberposts'  => -1,
						'post_type'    => 'product',
						'post_status'  => 'publish',
						'meta_key'     => '_ced_ebay_listing_id_' . $user_id,
						'meta_value'   => $itemId,
						'meta_compare' => '=',
					)
				);
				$store_product = wp_list_pluck( $store_product, 'ID' );

					$count = 1;
				if ( empty( $store_product ) ) {
					$valueItemID             = $value['ItemID'];
					$data['data'][ $key ][0] = $valueItemID;

				} elseif ( ! empty( $store_product ) ) {
					$importStatus                           = '<img style="margin-top:0px;margin-left:10px;" class="ced_ebay_already_imported_product_image" src="' . CED_EBAY_URL . 'admin/images/check.png">';
					$data['data'][ $key ][5]                = $importStatus;
					$data['data'][ $key ][0]                = $value['ItemID'];
					$data['data'][ $key ]['woo_product_id'] = $store_product[0];

				}
				if ( ! empty( $value['PictureDetails']['GalleryURL'] ) ) {
					$valuePictureDetails     = "<img class='attachment-thumbnail size-thumbnail wp-post-image' src='" . $value['PictureDetails']['GalleryURL'] . "' width='50px' height='50px'>";
					$data['data'][ $key ][1] = $valuePictureDetails;
				}

				if ( empty( $store_product ) ) {
					$valueTitle = $value['Title'];

				} elseif ( ! empty( $store_product ) ) {
					$valueTitle = $value['Title'];
				}
					$data['data'][ $key ][2] = $valueTitle;
					$data['data'][ $key ][3] = wc_price( $value['BuyItNowPrice'] );
					$valueQuantity           = isset( $value['Quantity'] ) ? $value['Quantity'] : 0;
					$data['data'][ $key ][4] = $valueQuantity;
					$data['data'][ $key ][6] = $value['ListingDetails']['ViewItemURL'];

			}
		} elseif ( isset( $activelist['ActiveList']['ItemArray']['Item'] ) && is_array( $activelist['ActiveList']['ItemArray']['Item'] ) && ! empty( $activelist['ActiveList']['ItemArray']['Item'] ) ) {

			$valueItemID             = $activelist['ActiveList']['ItemArray']['Item']['ItemID'];
			$data['data'][ $key ][0] = $valueItemID;

			$valuePictureDetails     = "<img class='attachment-thumbnail size-thumbnail wp-post-image' src='" . $activelist['ActiveList']['ItemArray']['Item']['PictureDetails']['GalleryURL'] . "' width='50px' height='50px'>";
			$data['data'][ $key ][1] = $valuePictureDetails;

			$valueTitle              = $activelist['ActiveList']['ItemArray']['Item']['Title'];
			$data['data'][ $key ][2] = $valueTitle;

			$valueItemPrice          = wc_price( $activelist['ActiveList']['ItemArray']['Item']['BuyItNowPrice'] );
			$data['data'][ $key ][3] = $valueItemPrice;

			$valueQuantity = $activelist['ActiveList']['ItemArray']['Item']['Quantity'];
			if ( '' == $valueQuantity ) {
				$valueQuantity = 0;
			}
			$data['data'][ $key ][4] = $valueQuantity;
			$data['data'][ $key ][6] = $value['ListingDetails']['ViewItemURL'];

		}

		$data         = isset( $data['data'] ) ? $data['data'] : array();
		$recordsTotal = isset( $activelist['ActiveList']['PaginationResult']['TotalNumberOfEntries'] ) ? $activelist['ActiveList']['PaginationResult']['TotalNumberOfEntries'] : 0;

		$per_page = 25;
		$columns  = $this->get_columns();
		$hidden   = array();
		$this->process_bulk_action();
		$this->_column_headers = array( $columns, $hidden );
		$current_page          = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}
		$total_items = isset( $recordsTotal ) ? $recordsTotal : 0;
		$this->items = $data;
		$paged       = isset( $_REQUEST['paged'] ) ? max( 0, intval( $_REQUEST['paged'] - 1 ) * $length ) : 0;
		$this->set_pagination_args(
			array(

				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}
}

?>


<?php

$importProductsListTable = new Ced_Ebay_Import_Ebay_Products();
$importProductsListTable->prepare_items();
$page_count = isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '';
$section    = isset( $_REQUEST['section'] ) ? sanitize_text_field( $_REQUEST['section'] ) : '';
$user_id    = isset( $_REQUEST['user_id'] ) ? sanitize_text_field( $_REQUEST['user_id'] ) : '';
?>
<div class="ced-ebay-v2-header">
<div class="ced-ebay-v2-logo">
				</div>
			<div class="ced-ebay-v2-header-content">
				<div class="ced-ebay-v2-title">
					<h1>eBay Products Import</h1>
				</div>
				<div class="ced-ebay-v2-actions">
				<div class="admin-custom-action-button-outer">

				<div class="admin-custom-action-show-button-outer">
<button  type="button" class="button btn-normal-sbc" id="ced_ebay_import_store_categories_recursively">
<span>Import eBay Store Categories</span>
</button>

</div>

<div class="admin-custom-action-show-button-outer">
<button style="background:#5850ec !important;" type="button" class="button btn-normal-tt">
<span><a style="all:unset;" href="https://docs.woocommerce.com/document/ebay-integration-for-woocommerce/#section-16" target="_blank">
Documentation					</a></span>
</button>

</div>

</div>
			</div>
		</div>
</div>

<div id="post-body" class="metabox-holder columns-2">
<form method="get" action="">
<div id="admin-custom-actions-info-sbc" class="custom-actions-info-div" style="display: none;">
			<div style="margin-bottom: 10px;"><b>Filter by Listings Start Date on eBay</b></div>
			<select name="ced_ebay_listing_start_time_filter" id="ced_ebay_listing_start_time_select">
				<option value="-1">Select Duration</option>
				<option value="P3D">Last 3 Days</option>
				<option value="P5D">Last 5 Days</option>
			</select>
			<button id="send-price-sbc" title="Filter" type="submit" class="button btn-light-sbc">
				<span>Filter</span>
			</button>
		</div>
		<input type="hidden" name="page" value="<?php print_r( $page_count ); ?>"/>
		<input type="hidden" name="section" value="<?php print_r( $section ); ?>"/>
		<input type="hidden" name="user_id" value="<?php print_r( $user_id ); ?>"/>
			<?php $importProductsListTable->display(); ?>
			</form>
</div>




<script type="text/javascript">
	jQuery("input[name='_wp_http_referer'], input[name='_wpnonce']").remove();
</script>
