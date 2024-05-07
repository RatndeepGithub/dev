<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}


class EtsyListImportedProducts extends WP_List_Table {
	public $reset;
	public $show_reset;
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Product import', 'woocommerce-etsy-integration' ), // singular name of the listed records
				'plural'   => __( 'Products import', 'woocommerce-etsy-integration' ), // plural name of the listed records
				'ajax'     => true, // does this table support ajax?
			)
		);
	}

	public function prepare_items() {
		global $wpdb;
		$per_page  = 15;
		$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		// Column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$current_page          = $this->get_pagenum();

		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}
		if ( ! $this->current_action() ) {
			$this->items = self::get_product_details( $per_page, $offset, $shop_name /*, $search_key*/ );
			$count       = self::get_count( $per_page, $current_page, $shop_name );
			$this->set_pagination_args(
				array(
					'total_items' => $count,
					'per_page'    => $per_page,
					'total_pages' => ceil( $count / $per_page ),
				)
			);
			$this->renderHTML();
		} else {
			$this->process_bulk_action();
		}
	}

	/**
	 * FUNCTION TO GET PRODUCT DETAILS
	 *
	 * @param $per_page Define how many number of products want to so on a single page
	 * @param $offset used for pagination
	 * @param $shop_name Etsy Active Shop Name
	 * @param $search Search keyword to search product on table section
	 *
	 * @return product details
	 */

	public function get_product_details( $per_page = '', $offset = 1, $shop_name = '', $search = '' ) {
		$product_to_show = array();
		$woo_status      = isset( $_GET['woo_status_sorting'] ) ? sanitize_text_field( wp_unslash( $_GET['woo_status_sorting'] ) ) : '';

		// Handle filtering requests
		if ( isset( $_POST['filter_button'] ) ) {
			$this->handleFilterButton();
			return $product_to_show;
		}

		// Handle search requests
		if ( isset( $_POST['s'] ) ) {
			$this->handleSearchRequest( $shop_name, $search, $product_to_show );
			return $product_to_show;
		}

		// Handle regular product listing request
		$params = array(
			'state'  => isset( $_GET['status_sorting'] ) ? sanitize_text_field( wp_unslash( $_GET['status_sorting'] ) ) : 'active',
			'offset' => $offset,
			'limit'  => $per_page,
		);

		$response = $this->ced_etsy_get_product_to_import_on_woo( 'listings/byShop', $shop_name, $params );

		if ( empty( $response['count'] ) ) {
			update_option( 'ced_etsy_total_import_product_' . $shop_name, 0 );
			return $product_to_show;
		}

		update_option( 'ced_etsy_total_import_product_' . $shop_name, $response['count'] );
		$shop_id = get_etsy_shop_id( $shop_name );
		foreach ( $response['results'] as $value ) {
			if ( $this->shouldSkipProduct( $woo_status, $shop_name, $value['listing_id'] ) ) {
				continue;
			}

			$product_to_show[] = $this->ced_etsy_prepare_data_to_show( $value, $shop_name, $shop_id );

			$product_exists = etsy_get_product_id_by_shopname_and_listing_id( $shop_name, $value['listing_id'] );
			if ( ! empty( $product_exists ) ) {
				$count[] = $product_exists;
				update_option( 'ced_etsy_total_created_product_' . $shop_name, $count );
			}
		}

		// Update total import product count if searched
		if ( ! empty( $search ) ) {
			update_option( 'ced_etsy_total_import_product_' . $shop_name, count( $product_to_show ) );
			if ( empty( $product_to_show ) ) {
				update_option( 'ced_etsy_total_import_product_' . $shop_name, 0 );
			}
		}

		return $product_to_show;
	}

	  // Helper functions

	private function handleFilterButton() {
		$this->reset = true;
		if ( ! isset( $_POST['manage_product_filters'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['manage_product_filters'] ) ), 'manage_products' ) ) {
			return;
		}
		$status_sorting     = isset( $_POST['status_sorting'] ) ? sanitize_text_field( wp_unslash( $_POST['status_sorting'] ) ) : '';
		$woo_status_sorting = isset( $_POST['woo_status_sorting'] ) ? sanitize_text_field( wp_unslash( $_POST['woo_status_sorting'] ) ) : '';
		$current_url        = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		wp_redirect( $current_url . '&status_sorting=' . $status_sorting . '&woo_status_sorting=' . $woo_status_sorting . '&shop_name=' . $shop_name );
	}

	private function handleSearchRequest( $shop_name, $search, &$product_to_show ) {
		if ( ! isset( $_POST['import_by_search_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['import_by_search_nonce'] ) ), 'import_by_search' ) ) {
			return;
		}
		$this->reset       = true;
		$response          = $this->ced_etsy_get_product_to_import_on_woo( 'listings/byIds', $shop_name, array( 'listing_ids' => $search ) );
		$product_to_show[] = $this->ced_etsy_prepare_data_to_show( $response, $shop_name );
	}

	private function shouldSkipProduct( $woo_status, $shop_name, $listing_id ) {
		if ( 'Uploaded' == $woo_status ) {
			$product_id = etsy_get_product_id_by_shopname_and_listing_id( $shop_name, $listing_id );
			return ! $product_id;
		} elseif ( 'NotUploaded' == $woo_status ) {
			$product_id = etsy_get_product_id_by_shopname_and_listing_id( $shop_name, $listing_id );
			return $product_id;
		}
		return false;
	}



	/**
	 * FUNCTION IS USED TO IMPORT PRODUCTS ON WOOCOMMERCE
	 *
	 * @param $action for api call.
	 * @param $shop_name Etsy Active Shop Name.
	 * @param $params to pass into api calls.
	 *
	 * @return RESPONSE.
	 */

	public function ced_etsy_get_product_to_import_on_woo( $action = '', $shop_name = '', $query_args = array() ) {
		$shop_id  = get_etsy_shop_id( $shop_name );
		$response = etsy_request()->ced_etsy_remote_req( $action, array(), array_merge( $query_args, array( 'shop_id' => $shop_id ) ), 'GET' );
		return $response;

	}



	/**
	 * FUNCTION IS USED TO SET PRODUCT DETAILS INTO SPECIFIED VARIABLES
	 *
	 * @param $response return from api calls.
	 * @param $shop_name Etsy Active Shop Name.
	 *
	 * @return RESPONSE.
	 */

	public function ced_etsy_prepare_data_to_show( $response = array(), $shop_name = '', $shop_id = '' ) {

		$amount                         = isset( $response['price']['amount'] ) && $response['price']['amount'] > 0 ? $response['price']['amount'] : 0;
		$price                          = $amount > 0 ? $amount / ( isset( $response['price']['divisor'] ) ? $response['price']['divisor'] : 1 ) : 0;
		$currency_code                  = isset( $response['price']['currency_code'] ) ? $response['price']['currency_code'] : '';
		$products_to_list['name']       = isset( $response['title'] ) ? $response['title'] : '';
		$products_to_list['price']      = get_woocommerce_currency_symbol() . (float) $price;
		$products_to_list['stock']      = isset( $response['quantity'] ) ? $response['quantity'] : 0;
		$products_to_list['status']     = isset( $response['state'] ) ? $response['state'] : '';
		$products_to_list['url']        = isset( $response['url'] ) ? $response['url'] : '';
		$products_to_list['listing_id'] = isset( $response['listing_id'] ) ? $response['listing_id'] : '';
		$products_to_list['shop_name']  = $shop_name;
		$listing_id                     = isset( $response['listing_id'] ) ? $response['listing_id'] : '';
		$image_details                  = etsy_request()->ced_etsy_remote_req(
			'listings/images',
			array(),
			array(
				'shop_id'    => get_etsy_shop_id( $shop_name ),
				'listing_id' => $listing_id,
			),
			'GET'
		);
		$products_to_list['image']      = isset( $image_details['results'][0]['url_170x135'] ) ? $image_details['results'][0]['url_170x135'] : '';
		return $products_to_list;
	}



	public function no_items() {
		esc_html_e( 'No Products To Show.', 'woocommerce-etsy-integration' );
	}

	/**
	 *
	 * FUNCTION TO COUNT NUMBER OF RESPONSE IN RESULTS
	 *
	 * @return TOTAL COUNT.
	 */
	public function get_count( $per_page = '', $page_number = '', $shop_name = '' ) {

		$total_items = get_option( 'ced_etsy_total_import_product_' . $shop_name, array() );
		if ( ! empty( $total_items ) ) {
			return $total_items;
		} else {
			return 0;
		}

	}

	/**
	 * COLUMNS TO MAKE SORTABLE.
	 *
	 * @return ARRAY.
	 */

	public function get_sortable_columns() {
		$sortable_columns = array();
		return $sortable_columns;
	}

	/**
	 * FUNATION TO MAKE CHECKBOX.
	 *
	 * @return CHECKBOX.
	 */

	public function column_cb( $item ) {

		$if_product_exists = etsy_get_product_id_by_shopname_and_listing_id( $item['shop_name'], $item['listing_id'] );
		if ( ! empty( $if_product_exists ) ) {
			update_option( 'ced_product_is_availabe_in_woo_' . $item['listing_id'], $item['listing_id'] );
			$image_path = CED_ETSY_URL . 'admin/assets/images/check.png';
			return sprintf( '<img class="check_image" src="' . $image_path . '" alt="Done">' );
		} else {
			return sprintf(
				'<input type="checkbox" name="etsy_import_products_id[]" class="etsy_import_products_id" value="%s" />',
				$item['listing_id']
			);
		}
	}


	/**
	 * FUNCTION TO SHOW IMAGES.
	 *
	 * @return IMAGES.
	 */

	public function column_image( $item = '' ) {
		$shop_name  = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		$listing_id = isset( $item['listing_id'] ) ? $item['listing_id'] : '';
		$product_id = etsy_get_product_id_by_shopname_and_listing_id( $shop_name, $listing_id );
		if ( ! empty( $product_id ) ) {
			$product   = wc_get_product( $product_id );
			$image_id  = $product->get_image_id();
			$image_url = wp_get_attachment_image_url( $image_id );
			echo '<a><img src="' . esc_url( $image_url ) . '" height="50" width="50" ></a>';

		} elseif ( isset( $item['image'] ) && ! empty( $item['image'] ) ) {
			echo '<a><img src="' . esc_url( $item['image'] ) . '" height="50" width="50" ></a>';
		} else {
			$image_path = CED_ETSY_URL . 'admin/assets/images/etsy.png';
			return sprintf( '<img height="35" width="60" src="' . $image_path . '" alt="Done">' );
		}

	}

	/**
	 * FUNCTION TO SET PRODUCTS NAME TO THIS COLUMN.
	 *
	 * @return COLOUMN WITH PRODUCT NAME.
	 */

	public function column_name( $item ) {
		$shop_name     = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		$listing_id    = isset( $item['listing_id'] ) ? $item['listing_id'] : '';
		$product_id    = etsy_get_product_id_by_shopname_and_listing_id( $shop_name, $listing_id );
		$product_id    = isset( $product_id ) ? $product_id : '';
		$editUrl       = get_edit_post_link( $product_id, '' );
		$actions['id'] = 'ID:' . $listing_id;
		if ( ! empty( $product_id ) ) {
			$editUrl = $editUrl;
		} else {
			$editUrl = $item['url'];
		}

		$actions['import'] = '<a href="' . $editUrl . '" class="import_single_product" data-listing-id="' . $listing_id . '"> Import</a>';
		echo '<b><a class="ced_etsy_prod_name" href="' . esc_attr( $editUrl ) . '" >' . esc_attr( isset( $item['name'] ) ? $item['name'] : $item['post_title'] ) . '</a></b>';
		return $this->row_actions( $actions, true );

	}


	/**
	 * FUNCTION TO SET STOCK INTO STOCK COLOUMN.
	 *
	 * @return STOCK.
	 */


	public function column_stock( $item ) {
		$stock = isset( $item['stock'] ) ? $item['stock'] : get_post_meta( $item['ID'], '_stock', true );
		return $stock;
	}


	/**
	 * FUNCTION TO SET PRICE ON PRICE COLOUMN .
	 *
	 * @return PRICE.
	 */


	public function column_price( $item ) {
		$price = isset( $item['price'] ) ? $item['price'] : get_post_meta( $item['ID'], 'price', true );
		echo esc_attr( $price );
	}

	/**
	 * FUNCTION TO SET STATUS ON STATUS COLOUMN .
	 *
	 * @return STATUS.
	 */

	public function column_status( $item ) {
		$status = isset( $item['status'] ) ? $item['status'] : '';
		if ( ! empty( $status ) ) {
			echo esc_attr( $status );
		}
	}



	public function column_view_url( $item ) {
		$shop_name  = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		$listing_id = isset( $item['listing_id'] ) ? $item['listing_id'] : '';
		$url        = isset( $item['url'] ) ? $item['url'] : '';
		$etsy_icon  = CED_ETSY_URL . 'admin/assets/images/etsy.png';
			echo '<a href="' . esc_url( $url ) . '" target="_blank">preview</a>';
	}

	public function column_update_inventory_etsy_to_woo( $item ) {
		$product_id = etsy_get_product_id_by_shopname_and_listing_id( $item['shop_name'], $item['listing_id'] );
		if ( ! empty( $product_id ) ) {
			$update = '<a class="button-primary update_inventory_etsy_to_wooc" data-listing-id ="' . $item['listing_id'] . '" href="javascript:void(0)">' . __( 'Update', 'woocommerce-etsy-integration' ) . '</a>';
			return $update;
		} else {
			return;
		}
	}


	/**
	 * FUNCTION TO GET COLOUMNS  .
	 *
	 * @return COLOUMNS.
	 */

	public function get_columns() {
		$columns = array(
			'cb'       => '<input type="checkbox" />',
			'image'    => __( 'Image', 'woocommerce-etsy-integration' ),
			'name'     => __( 'Name', 'woocommerce-etsy-integration' ),
			'price'    => __( 'Price', 'woocommerce-etsy-integration' ),
			'stock'    => __( 'Stock', 'woocommerce-etsy-integration' ),
			'status'   => __( 'Status', 'woocommerce-etsy-integration' ),
			'view_url' => __( ' View Link', 'woocommerce-etsy-integration' ),

		);
		return $columns;
	}


	/**
	 * FUNCTION TO PERFORMS BULK ACTIONS .
	 */

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

			echo '<label for="bulk-import-action-selector' . esc_attr( $which ) . '" class="screen-reader-text">' . esc_html( __( 'Select bulk action' ) ) . '</label>';
			echo '<select name="action' . esc_attr( $two ) . '" class="bulk-import-action-selectorf">';
			echo '<option value="-1">' . esc_html( __( 'Bulk Actions' ) ) . "</option>\n";

			foreach ( $this->_actions as $name => $title ) {
				$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

				echo "\t" . '<option value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . "</option>\n";
			}

			echo "</select>\n";

			submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => 'ced_esty_import_product_bulk_operation' ) );
			echo "\n";
		endif;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'import_product' => 'Import Product',
		);
		return $actions;
	}
	public function renderHTML() {
		?>
		<div class="ced_etsy_heading">
			<?php echo esc_html_e( get_etsy_instuctions_html() ); ?>
		</div>
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<?php
					$shop_name                           = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
					$status_actions                      = array(
						'draft'    => __( 'Draft', 'woocommerce-etsy-integration' ),
						'active'   => __( 'Active', 'woocommerce-etsy-integration' ),
						'inacitve' => __( 'Inactive', 'woocommerce-etsy-integration' ),
						'expired'  => __( 'Expired', 'woocommerce-etsy-integration' ),
					);
					$upload_status_actions               = array(
						'Uploaded'    => __( 'On WooCommerce', 'woocommerce-etsy-integration' ),
						'NotUploaded' => __( 'Not on WooCommerce', 'woocommerce-etsy-integration' ),
					);
					$previous_selected_status            = isset( $_GET['status_sorting'] ) ? sanitize_text_field( wp_unslash( $_GET['status_sorting'] ) ) : 'active';
					$previous_selected_status_for_upload = isset( $_GET['woo_status_sorting'] ) ? sanitize_text_field( wp_unslash( $_GET['woo_status_sorting'] ) ) : '';
					echo '<div class="ced_etsy_wrap">';
					echo '<form method="post" action="">';
					wp_nonce_field( 'manage_products', 'manage_product_filters' );

					$total_created_product    = get_option( 'ced_etsy_total_created_product_' . $shop_name );
					$total_created_product    = isset( $total_created_product ) ? $total_created_product : array();
					$total_etsy_total_product = get_option( 'ced_etsy_total_import_product_' . $shop_name );
					echo '<div class="ced_etsy_top_wrapper">';
					echo '<select name="status_sorting" class="select_boxes_product_page">';
					echo '<option value="">' . esc_html( __( 'Import By Status', 'woocommerce-etsy-integration' ) ) . '</option>';
					foreach ( $status_actions as $name => $title ) {
						$selectedStatus = ( $previous_selected_status == $name ) ? 'selected="selected"' : '';
						$class          = 'edit' === $name ? ' class="hide-if-no-js"' : '';
						echo '<option ' . esc_attr( $selectedStatus ) . ' value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . '</option>';
					}
					echo '</select>';
					echo '<select name="woo_status_sorting" class="select_boxes_product_page">';
					echo '<option value="">' . esc_html( __( ' — Filter by Woo Product Status — ', 'woocommerce-etsy-integration' ) ) . '</option>';
					foreach ( $upload_status_actions as $up_name => $up_title ) {
						$up_selectedStatus = ( $previous_selected_status_for_upload == $up_name ) ? 'selected="selected"' : '';
						$up_class          = 'edit' === $up_name ? ' class="hide-if-no-js"' : '';
						echo '<option ' . esc_attr( $up_selectedStatus ) . ' value="' . esc_attr( $up_name ) . '"' . esc_attr( $up_class ) . '>' . esc_attr( $up_title ) . '</option>';
					}
					echo '</select>';
					submit_button( __( ' Filter', 'ced-etsy' ), 'action', 'filter_button', false, array() );
					if ( $this->reset ) {
						echo '<span class="ced_reset"><a href="' . esc_url(
							ced_get_navigation_url(
								'etsy',
								array(
									'section'   => 'importer',
									'shop_name' => $shop_name,
								)
							)
						) . '" class="button">X</a></span>';
					}
					echo '</div>';
					echo '</form>';
					echo '</div>';

					?>
				

					<form method="post">
						<?php
						wp_nonce_field( 'import_by_search', 'import_by_search_nonce' );
						$this->search_box( 'Search', 'search', 'Search By Listing Id' );
						$this->display();
						?>
					</form>
					
				</div>
			</div>
			<div class="clear"></div>
		</div>
		<div class="ced_etsy_preview_product_popup_main_wrapper"></div>
		<?php
	}

	public function search_box( $text, $input_id, $placeholder = '' ) {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
			return;
		}

		$input_id = $input_id . '-search-input';
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : '';
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $orderby ) . '" />';
		}
		if ( ! empty( $_REQUEST['order'] ) ) {
			$order = isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : '';
			echo '<input type="hidden" name="order" value="' . esc_attr( $order ) . '" />';
		}
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_attr( $text ); ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" class="wp-filter-search" name="s" value="<?php _admin_search_query(); ?>" placeholder="<?php esc_attr_e( $placeholder ); ?>" />
			<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
		</p>
		<?php
	}
}

$ced_etsy_import_products_obj = new EtsyListImportedProducts();
$ced_etsy_import_products_obj->prepare_items();
