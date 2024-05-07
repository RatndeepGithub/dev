<?php

	// die if called directly
if ( ! defined( 'ABSPATH' ) ) {
	die;
}


	$file = CED_EBAY_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $file ) ) {
	require_once $file;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Ced_Ebay_Status_Feed extends WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => __( 'Bulk Exchange Job', 'ebay-integration-for-woocommerce' ),
				'plural'   => __( 'Bulk Exchange Jobs', 'ebay-integration-for-woocommerce' ),
				'ajax'     => false,

			)
		);
	}

	public function get_columns() {
		$columns = array(
			'cb'             => '<input type="checkbox" />',
			'product_name'   => __( 'Name', 'ebay-integration-for-woocommerce' ),
			'product_type'   => __( 'Type', 'ebay-integration-for-woocommerce' ),
			'product_status' => __( 'Status', 'ebay-integration-for-woocommerce' ),
			'scheduled_time' => __( 'Scheduled', 'ebay-integration-for-woocommerce' ),
		);

		return $columns;
	}


	public function ced_ebay_get_bulk_upload_job_data( $per_page = 10, $page_number = 1 ) {
		$filter_type = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : '';
		global $wpdb;
		$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$site_id = isset( $_GET['sid'] ) ? sanitize_text_field( $_GET['sid'] ) : '';
		$offset  = ( $page_number - 1 ) * $per_page;
		if ( 'uploaded' == $filter_type ) {
			$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `user_id` = %s AND `site_id` = %s AND `operation_status` = %s ORDER BY `scheduled_time` DESC LIMIT %d OFFSET %d", $user_id, $site_id, 'Uploaded', $per_page, $offset ), 'ARRAY_A' );
		} elseif ( 'not_uploaded' == $filter_type ) {
			$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `user_id` = %s AND `site_id` = %s AND `operation_status` = %s ORDER BY `scheduled_time` DESC LIMIT %d OFFSET %d", $user_id, $site_id, 'Error', $per_page, $offset ), 'ARRAY_A' );
		} elseif ( 'all_entries' == $filter_type || '' == $filter_type || 'uploaded' != $filter_type || 'not_uploaded' != $filter_type ) {
			$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `user_id` = %s AND `site_id` = %s ORDER BY `scheduled_time` DESC LIMIT %d OFFSET %d", $user_id, $site_id, $per_page, $offset ), 'ARRAY_A' );

		}

		return $result;
	}


	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		$filter_type = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : '';
		global $wpdb;
		$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$site_id = isset( $_GET['sid'] ) ? sanitize_text_field( $_GET['sid'] ) : '';
		if ( 'uploaded' == $filter_type ) {
			$sql = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `user_id` = %s AND `site_id` = %s AND `operation_status` = %s", $user_id, $site_id, 'Uploaded' ) );

		} elseif ( 'not_uploaded' == $filter_type ) {
			$sql = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `user_id` = %s AND `site_id` = %s AND `operation_status` = %s", $user_id, $site_id, 'Error' ) );

		} else {
			$sql = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}ced_ebay_bulk_upload WHERE `user_id` = %s AND `site_id` = %s", $user_id, $site_id ) );

		}

		return $sql;
	}


	/** Text displayed when no customer data is available */
	public function no_items() {
		esc_attr_e( 'No Product upload found.', 'ebay-integration-for-woocommerce' );
	}


	/**
	 * Render the bulk action checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-exchange-job-action[]" value="%s" />',
			$item['id']
		);
	}

	/**
	 * Render a column when no column specific method exists.
	 *
	 * @param array  $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'product_type':
			case 'product_name':
			case 'product_status':
			case 'scheduled_time':
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}

	public function column_scheduled_time( $item ) {
		echo '<div class="admin-custom-action-button-outer" style="margin-top:0px;"><div class="admin-custom-action-show-button-outer">';
		$time = $item['scheduled_time'];
		$time = ced_ebay_time_elapsed_string( $time );
		echo '<button type="button" class="button btn-normal-sbc"><span>' . esc_attr( $time ) . '</span></button></div></div>';
	}

	public function column_product_type( $item ) {
		$product_id = $item['product_id'];
		$product    = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		$product_type = $product->get_type();
		$title        = '<strong>' . $product_type . '</strong>';

		return $title;
	}


	public function column_product_name( $item ) {
		$actions       = array();
		$user_id       = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$site_id       = isset( $_GET['sid'] ) ? sanitize_text_field( $_GET['sid'] ) : '';
		$product_id    = $item['product_id'];
		$listing_id    = get_post_meta( $product_id, '_ced_ebay_listing_id_' . $user_id . '>' . $site_id, true );
		$product       = wc_get_product( $product_id );
		$product_title = get_the_title( $product_id );
		$url           = get_edit_post_link( $product_id, '' );
		echo '<strong><a style="font-size:16px;" href="' . esc_attr( $url ) . '" target="_blank">' . esc_attr( $product_title ) . '</a></strong>';

		// $title         = '<strong>' . $product_title . '</strong>';
		if ( ! empty( $listing_id ) ) {
			if ( ! empty( get_option( 'ced_ebay_listing_url_tld_' . $user_id ) ) ) {
				$listing_url_tld     = get_option( 'ced_ebay_listing_url_tld_' . $user_id, true );
				$view_url_production = 'https://www.ebay' . $listing_url_tld . '/itm/' . $listing_id;
				$view_url_sandbox    = 'https://sandbox.ebay' . $listing_url_tld . '/itm/' . $listing_id;
			} else {
				$view_url_production = 'https://www.ebay.com/itm/' . $listing_id;
				$view_url_sandbox    = 'https://sandbox.ebay.com/itm/' . $listing_id;
			}
			$mode_of_operation = get_option( 'ced_ebay_mode_of_operation', '' );
			if ( 'sandbox' == $mode_of_operation ) {
				$actions['view_on_ebay'] = '<a href="' . esc_attr( $view_url_sandbox ) . '" target="_blank">' . __( 'View on eBay', 'ebay-integration-for-woocommerce' ) . '</a>';
			} elseif ( 'production' == $mode_of_operation ) {
				$actions['view_on_ebay'] = '<a href="' . esc_attr( $view_url_production ) . '" target="_blank">' . __( 'View on eBay', 'ebay-integration-for-woocommerce' ) . '</a>';
			}
				return $this->row_actions( $actions );

		}
	}

	public function column_product_status( $item ) {
		if ( 'Uploaded' == $item['operation_status'] ) {
			$title = '<strong>' . $item['operation_status'] . '</strong>';
		} elseif ( 'Error' == $item['operation_status'] ) {
			$upload_errors = $item['error'];
			if ( ! empty( $upload_errors ) ) {
				$upload_errors = json_decode( $upload_errors, true );
				if ( ! is_array( $upload_errors ) ) {
					if ( is_array( json_decode( $upload_errors, true ) ) ) {
						$upload_errors = json_decode( $upload_errors, true );
					}
				}
				if ( ! empty( $upload_errors ) && is_array( $upload_errors ) ) {
					$title = '<ul style="margin-top:0px;">';
					foreach ( $upload_errors as $key => $upload_err ) {
						$title .= '<li><b><span style="color:red;">[' . $upload_err['severity'] . ']</span> ' . $upload_err['message'] . '</b></li>';
					}
					$title .= '</ul>';
				}
			} else {
				$title = '<strong style="color:red;">Unable to fetch error log!</strong>';
			}
		}
		return $title;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {
		$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		if ( isset( $_POST['ced_ebay_bulk_upload_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_ebay_bulk_upload_nonce'] ), 'ced_ebay_view_entries_bulk_upload_nonce' ) ) {
			$per_page_preference = ! empty( $_POST['ced_ebay_view_entries_bulk_upld_input'] ) ? sanitize_text_field( $_POST['ced_ebay_view_entries_bulk_upld_input'] ) : 0;
			update_option( 'ced_ebay_bulk_upload_per_page_' . $user_id, $per_page_preference );
		}

		$per_page              = ! empty( get_option( 'ced_ebay_bulk_upload_per_page_' . $user_id ) ) ? get_option( 'ced_ebay_bulk_upload_per_page_' . $user_id ) : 10;
		$this->_column_headers = $this->get_column_info();
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();

		// Column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}
		/** Process bulk action */
		$this->process_bulk_action();

		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		// Set the pagination
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);

		$this->items = self::ced_ebay_get_bulk_upload_job_data( $per_page, $current_page );
		$this->renderHTML();
	}

	public function renderHTML() {
		$total_items = self::record_count();
		$user_id     = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$site_id = isset( $_GET['sid'] ) ? sanitize_text_field( $_GET['sid'] ) : '';
		?>
		<div class="ced-ebay-v2-header">
				
				<div class="ced-ebay-v2-header-content">
					
					<div class="ced-ebay-v2-actions">
					<div class="admin-custom-action-button-outer">
					<div class="admin-custom-action-show-button-outer">
			<?php
			if ( function_exists( 'as_get_scheduled_actions' ) ) {
				$scheduled_recurring_upload    = false;
				$scheduled_bulk_upload_actions = as_get_scheduled_actions(
					array(
						'group'  => 'ced_ebay_bulk_upload_' . $user_id,
						'status' => \ActionScheduler_Store::STATUS_PENDING,
					),
					'ARRAY_A'
				);

				if ( as_has_scheduled_action( 'ced_ebay_recurring_bulk_upload_' . $user_id ) ) {
					$scheduled_recurring_upload = true;
				}
			}
			if ( ! empty( $scheduled_bulk_upload_actions || $scheduled_recurring_upload ) ) {
				?>
	<!-- <button style="background:red;" data-action="turn_off" style="margin-left:5px;" id="ced_ebay_toggle_bulk_upload_btn" type="button" class="button btn-normal-tt">
	<span>Bulk Upload In Progress. Click To Turn Off!</span>
	</button> -->
				<?php
			} else {
				?>
	<!-- <button  style="margin-left:5px;" data-action="turn_on" id="ced_ebay_toggle_bulk_upload_btn" type="button" class="button btn-normal-sbc">
	<span>Turn On Bulk Products Upload</span>
	</button> -->
				<?php
			}
			?>
	</div>


					<!-- <div class="admin-custom-action-show-button-outer">
					<button style="background:#135e96;" style="margin-left:5px;" id="ced_ebay_del_blk_upld_logs_btn" type="button" class="button btn-normal-tt">
	<span>Delete Logs</span>
	</button>

		</div> -->


	</div>

				</div>
			</div>
	</div>
		<div class="ced_ebay_wrap ced_ebay_wrap_extn">

			<div>
			<?php
			if ( ! session_id() ) {
				session_start();
			}

			?>
				<div id="post-body" class="metabox-holder columns-2">
					<div id="">
						<div class="meta-box-sortables ui-sortable">
						<ul class="subsubsub">
						<?php
						$filter_type = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : '';
						if ( defined( 'EBAY_INTEGRATION_FOR_WOOCOMMERCE_VERSION' ) ) {
							$plugin_version = EBAY_INTEGRATION_FOR_WOOCOMMERCE_VERSION;
						}
						if ( 'uploaded' == $filter_type ) {
							$all_products_filter_html = sprintf( '<li><a href="?page=%s&channel=ebay&section=%s&user_id=%s&type=all_entries&site_id=%s" aria-current="page">All</a>|</li>', esc_attr( isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '' ), 'feeds-view', esc_attr( isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '' ), $site_id );
							print_r( $all_products_filter_html );

							$uploaded_filter_html = sprintf( '<li><a class="current"  href="?page=%s&channel=ebay&section=%s&user_id=%s&type=uploaded&site_id=%s" aria-current="page">Uploaded</a>|</li>', esc_attr( isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '' ), 'feeds-view', esc_attr( isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '' ), $site_id );
							print_r( $uploaded_filter_html );

							$not_uploaded_filter_html = sprintf( '<li><a href="?page=%s&channel=ebay&section=%s&user_id=%s&type=not_uploaded&site_id=%s" aria-current="page"> Not Uploaded</a></li>', esc_attr( isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '' ), 'feeds-view', esc_attr( isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '' ), $site_id );
							print_r( $not_uploaded_filter_html );
						} elseif ( 'not_uploaded' == $filter_type ) {
							$all_products_filter_html = sprintf( '<li><a href="?page=%s&channel=ebay&section=%s&user_id=%s&type=all_entries&site_id=%s" aria-current="page">All</a>|</li>', esc_attr( isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '' ), 'feeds-view', esc_attr( isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '' ), $site_id );
							print_r( $all_products_filter_html );

							$uploaded_filter_html = sprintf( '<li><a href="?page=%s&channel=ebay&section=%s&user_id=%s&type=uploaded&site_id=%s" aria-current="page">Uploaded</a>|</li>', esc_attr( isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '' ), 'feeds-view', esc_attr( isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '' ), $site_id );
							print_r( $uploaded_filter_html );

							$not_uploaded_filter_html = sprintf( '<li><a class="current" href="?page=%s&channel=ebay&section=%s&user_id=%s&type=not_uploaded&site_id=%s" aria-current="page">Not Uploaded</a></li>', esc_attr( isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '' ), 'feeds-view', esc_attr( isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '' ), $site_id );
							print_r( $not_uploaded_filter_html );
						} elseif ( 'all_entries' == $filter_type || '' == $filter_type || 'uploaded' != $filter_type || 'not_uploaded' != $filter_type ) {
							$all_products_filter_html = sprintf( '<li><a class="current" href="?page=%s&channel=ebay&section=%s&user_id=%s&type=all_entries&site_id=%s" aria-current="page">All</a>|</li>', esc_attr( isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '' ), 'feeds-view', esc_attr( isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '' ), $site_id );
							print_r( $all_products_filter_html );

							$uploaded_filter_html = sprintf( '<li><a   href="?page=%s&channel=ebay&section=%s&user_id=%s&type=uploaded&site_id=%s" aria-current="page">Uploaded</a>|</li>', esc_attr( isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '' ), 'feeds-view', esc_attr( isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '' ), $site_id );
							print_r( $uploaded_filter_html );

							$not_uploaded_filter_html = sprintf( '<li><a href="?page=%s&channel=ebay&section=%s&user_id=%s&type=not_uploaded&site_id=%s" aria-current="page"> Not Uploaded</a></li>', esc_attr( isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '' ), 'feeds-view', esc_attr( isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '' ), $site_id );
							print_r( $not_uploaded_filter_html );
						}

						?>
		<!-- <li><a href=""  aria-current="page">Not Uploaded <span class="count">(1)</span></a></li> -->
	</ul>
								<div class="ced-ebay-feeds-table">
							<form method="post">
						<?php
							wp_nonce_field( 'ebay_profile_view', 'ebay_profile_view_actions' );
							$this->display();
						?>
							</form>
							</div>
						</div>
					</div>
					<div class="clear"></div>
				</div>
				<br class="clear">
			</div>
		</div>
			<?php
	}

	public function extra_tablenav( $which ) {
		$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		if ( 'top' == $which ) {
			ob_start();
			?>
<div style="font-size:14px !important;">Entries Per Page</div>
<input type="text" name="ced_ebay_view_entries_bulk_upld_input" value="<?php echo ! empty( get_option( 'ced_ebay_bulk_upload_per_page_' . $user_id ) ) ? esc_attr( get_option( 'ced_ebay_bulk_upload_per_page_' . $user_id, true ) ) : '10'; ?>">
					<button style="vertical-align:middle;" type="submit" class="button" name="ced_ebay_view_entries_bulk_upload">
	<span>Save</span>
	</button>
		<div class="alignright actions bulkactions" style="padding-right:0px !important;">

									
								


		</div>

				<?php
				wp_nonce_field( 'ced_ebay_view_entries_bulk_upload_nonce', 'ced_ebay_bulk_upload_nonce' );
				ob_flush();
		}
	}
}
	$obj = new Ced_Ebay_Status_Feed();
	$obj->prepare_items();

?>

	<style>
	table.wp-list-table .column-product_type {
		width:auto !important;
		}
	</style>
