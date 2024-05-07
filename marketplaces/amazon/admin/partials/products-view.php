<?php


if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$file = CED_AMAZON_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $file ) ) {
	require_once $file;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}


require_once CED_AMAZON_DIRPATH . 'admin/amazon/lib/class-feed-manager.php';

$notices = array();

if ( isset( $_POST['ced_amazon_product_bulk_action_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_product_bulk_action_nonce'] ), 'ced_amazon_product_bulk_action_page_nonce' ) ) {

	if ( isset( $_POST['doaction'] ) ) {

		$marketplace = 'amazon';

		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';

		if ( ! empty( $seller_id ) ) {
			$mplocation_arr = explode( '|', $seller_id );
			$mplocation     = isset( $mplocation_arr[0] ) ? $mplocation_arr[0] : '';
		}
		$product_action = isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : -1;

		$sanitized_array = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
		$proIds          = isset( $sanitized_array['amazon_product_ids'] ) ? $sanitized_array['amazon_product_ids'] : array();


		$allset = true;


		if ( empty( $product_action ) || -1 == $product_action ) {
			$allset    = false;
			$message   = __( 'Please select the bulk actions to perform an action!', 'amazon-for-woocommerce' );
			$classes   = 'error is-dismissable';
			$notices[] = array(
				'message' => $message,
				'classes' => $classes,
			);
		}

		if ( empty( $seller_id ) || '' == $seller_id ) {
			$allset    = false;
			$message   = __( 'Seller ID is missing to perform the action!', 'amazon-for-woocommerce' );
			$classes   = 'error is-dismissable';
			$notices[] = array(
				'message' => $message,
				'classes' => $classes,
			);
		}

		if ( empty( $mplocation ) || '' == $mplocation ) {
			$allset    = false;
			$message   = __( 'Seller location is missing to perform action!', 'amazon-for-woocommerce' );
			$classes   = 'error is-dismissable';
			$notices[] = array(
				'message' => $message,
				'classes' => $classes,
			);
		}

		if ( empty( $marketplace ) || -1 == $marketplace ) {
			$allset    = false;
			$message   = __( 'No marketplace is activated!', 'amazon-for-woocommerce' );
			$classes   = 'error is-dismissable';
			$notices[] = array(
				'message' => $message,
				'classes' => $classes,
			);
		}

		if ( ! is_array( $proIds ) ) {

			$allset    = false;
			$message   = __( 'Please select products to perform the bulk action!', 'amazon-for-woocommerce' );
			$classes   = 'error is-dismissable';
			$notices[] = array(
				'message' => $message,
				'classes' => $classes,
			);
		}

		if ( $allset ) {

			if ( class_exists( 'Ced_Umb_Amazon_Feed_Manager' ) ) {
				$feed_manager = Ced_Umb_Amazon_Feed_Manager::get_instance();
				$notice       = $feed_manager->process_feed_request( $product_action, $marketplace, $proIds, $mplocation, $seller_id );

				$notice_array = json_decode( $notice, true );

				if ( is_array( $notice_array ) ) {
					$message   = isset( $notice_array['message'] ) ? $notice_array['message'] : '';
					$classes   = isset( $notice_array['classes'] ) ? $notice_array['classes'] : 'error is-dismissable';
					$notices[] = array(
						'message' => $message,
						'classes' => $classes,
					);
				} else {

					
					$message   = __( 'An unexpected error occurred. Please try again.', 'amazon-for-woocommerce' );
					$classes   = 'error is-dismissable';
					$notices[] = array(
						'message' => $message,
						'classes' => $classes,
					);
				}
			}
		}
	}
}

if ( count( $notices ) ) {
	foreach ( $notices as $notice_array ) {
		$message = isset( $notice_array['message'] ) ? esc_html( $notice_array['message'] ) : '';
		$classes = isset( $notice_array['classes'] ) ? esc_attr( $notice_array['classes'] ) : 'error is-dismissable';

		if ( strpos( $classes, 'error' ) !== false ) {
			$classes = 'ced-error';
		}
		if ( strpos( $classes, 'success' ) !== false ) {
			$classes = 'ced-success';
		}
		if ( ! empty( $message ) ) {
			?>
			<div class="<?php echo esc_attr( $classes ); ?>">
				<p><?php echo esc_attr_e( $message, 'amazon-for-woocommerce' ); ?></p>
			</div>
			<?php
		}
	}
	unset( $notices );
}



class AmazonListProducts extends WP_List_Table {

	public $show_reset;

	/**
	 *
	 * Function to construct
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'ced-amazon-product', 'amazon-for-woocommerce' ),
				'plural'   => __( 'ced-amazon-products', 'amazon-for-woocommerce' ),
				'ajax'     => true,
			)
		);
	}


	/**
	 *
	 * Function for preparing data to be displayed
	 */

	public function prepare_items() {

		global $wpdb;

		/**
		 * Function to list order based on per page
		 *
		 * @param 'function'
		 * @param  integer 'limit'
		 * @return 'count'
		 * @since  1.0.0
		 */
		$per_page  = apply_filters( 'ced_amazon_products_per_page', 10 );
		$post_type = 'product';
		$columns   = $this->get_columns();
		$hidden    = array();
		$sortable  = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}

		$this->items = self::ced_amazon_get_product_details( $per_page, $current_page, $post_type );
		$count       = self::get_count( $per_page, $current_page );

		$this->set_pagination_args(
			array(
				'total_items' => $count,
				'per_page'    => $per_page,
				'total_pages' => ceil( $count / $per_page ),
			)
		);

		if ( $this->current_action() ) {
			$this->process_bulk_action();
		}
		$this->renderHTML();
	}

	/**
	 *
	 * Function for get product data
	 */
	public function ced_amazon_get_product_details( $per_page = '', $page_number = '', $post_type = '' ) {

		$filterFile = CED_AMAZON_DIRPATH . 'admin/partials/products-filters.php';
		if ( file_exists( $filterFile ) ) {
			require_once $filterFile;
		}

		$instanceOf_FilterClass = new FilterClass();
		$args                   = $this->GetFilteredData( $per_page, $page_number );

		if ( ! empty( $args ) && isset( $args['tax_query'] ) || isset( $args['meta_query'] ) || isset( $args['s'] ) ) {
			$args = $args;

		} else {
			$args = array(
				'post_type'      => $post_type,
				'posts_per_page' => $per_page,
				'paged'          => $page_number,
			);
		}

		$args['product_type'] = array( 'simple', 'variable' );
		$args['post_status']  = 'publish';
		$args['order']        = 'DESC';
		$args['orderby']      = 'ID';

		$loop         = new WP_Query( $args );
		$product_data = $loop->posts;

		$woo_categories = get_terms( 'product_cat' );
		$woo_products   = array();

		foreach ( $product_data as $key => $value ) {
			$get_product_data = wc_get_product( $value->ID );
			$get_product_data = $get_product_data->get_data();
			if ( ! empty( $get_product_data['category_ids'] ) ) {
				rsort( $get_product_data['category_ids'] );
			}
			$woo_products[ $key ]['category_id']  = isset( $get_product_data['category_ids'] ) ? $get_product_data['category_ids'] : '';
			$woo_products[ $key ]['id']           = $value->ID;
			$woo_products[ $key ]['name']         = $get_product_data['name'];
			$woo_products[ $key ]['stock']        = $get_product_data['stock_quantity'];
			$woo_products[ $key ]['stock_status'] = $get_product_data['stock_status'];
			$woo_products[ $key ]['sku']          = $get_product_data['sku'];
			$woo_products[ $key ]['price']        = $get_product_data['price'];
			$Image_url_id                         = $get_product_data['image_id'];
			$woo_products[ $key ]['image']        = wp_get_attachment_url( $Image_url_id );
			foreach ( $woo_categories as $key1 => $value1 ) {
				if ( isset( $get_product_data['category_ids'] ) ) {
					foreach ( $get_product_data['category_ids'] as $key2 => $prodCat ) {
						if ( $value1->term_id == $prodCat ) {
							$woo_products[ $key ]['category'][] = $value1->name;
						}
					}
				}
			}
		}

		if ( isset( $_POST['filter_button'] ) ) {
			if ( isset( $_POST['ced_amazon_product_filter_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_product_filter_nonce'] ), 'ced_amazon_product_filter_page_nonce' ) ) {
				$woo_products = $instanceOf_FilterClass->ced_amazon_filters_on_products();

			}
		} elseif ( isset( $_POST['s'] ) ) {
			if ( isset( $_POST['ced_amazon_product_filter_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_product_filter_nonce'] ), 'ced_amazon_product_filter_page_nonce' ) ) {
				$s            = isset( $_POST['s'] ) ? sanitize_text_field( $_POST['s'] ) : '';
				$woo_products = $instanceOf_FilterClass->productSearch_box( $wooProducts, $s );

			}
		}

		return $woo_products;
	}

	/**
	 *
	 * Text displayed when no data is available
	 */
	public function no_items() {
		esc_html_e( 'No Products To Show.', 'amazon-for-woocommerce' );
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */

	public function get_sortable_columns() {
		$sortable_columns = array();
		return $sortable_columns;
	}

	/*
	 * Render the bulk edit checkbox
	 *
	 */
	public function column_cb( $item ) {
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
		return sprintf(
			'<input type="checkbox" name="amazon_product_ids[]" class="amazon_products_id" value="%s" /></div></div>',
			$item['id']
		);
	}

	/**
	 *
	 * Function for name column
	 */
	public function column_name( $item ) {
		$actions       = array();
		$user_id       = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$url           = get_edit_post_link( $item['id'], '' );
		$actions['id'] = 'ID:' . __( $item['id'] );
		echo '<b><a class="ced_amazon_prod_name" href="' . esc_url( $url ) . '" target="_blank">' . esc_html__( $item['name'], 'amazon-for-woocommerce' ) . '</a></b><br>';
		return $this->row_actions( $actions );
	}

	/**
	 *
	 * Function for profile column
	 */
	public function column_profile( $item ) {

		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';

		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';

		$terms = wp_get_post_terms(
			$item['id'],
			'product_cat',
			array(
				'order'   => '',
				'orderby' => '',
			)
		);

		$terms   = json_decode( wp_json_encode( $terms ), true );
		$cat_ids = array();
		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$cat_ids[] = $term['term_id'];
			}
		}

		$mapped                 = 0;
		$ced_woo_amazon_mapping = get_option( 'ced_woo_amazon_mapping', array() );
		$ced_woo_amazon_mapping = isset( $ced_woo_amazon_mapping[ $seller_id ] ) ? $ced_woo_amazon_mapping[ $seller_id ] : array();

		if ( ! empty( $ced_woo_amazon_mapping ) ) {
			foreach ( $ced_woo_amazon_mapping as $key => $woo_cat_array ) {

				$match_woo_cat = array_intersect( $woo_cat_array, $cat_ids );
				if ( is_array( $match_woo_cat ) && ! empty( $match_woo_cat ) ) {

					$mapped = $key;
					global $wpdb;
					$amazon_profiles = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles WHERE `id` = %s", $key ), 'ARRAY_A' );
					$amazon_profiles = isset( $amazon_profiles[0] ) ? $amazon_profiles[0] : array();


					$profile = isset( $amazon_profiles['amazon_categories_name'] ) ? $amazon_profiles['amazon_categories_name'] : '';

					if ( empty( $profile ) ) {

						if ( ! empty( $amazon_profiles['primary_category'] ) && ! empty( $amazon_profiles['secondary_category'] ) && ! empty( $amazon_profiles['browse_nodes_name'] ) ) {
							$profile = $amazon_profiles['primary_category'] . ' > ' . $amazon_profiles['secondary_category'] . ' > ' . $amazon_profiles['browse_nodes_name'];
						} elseif ( ! empty( $amazon_profiles['primary_category'] ) && ! empty( $amazon_profiles['secondary_category'] ) ) {
							$profile = $amazon_profiles['primary_category'] . ' > ' . $amazon_profiles['secondary_category'];
						} elseif ( ! empty( $amazon_profiles['primary_category'] ) ) {
							$profile = $amazon_profiles['primary_category'];
						}
					}

					break;
				}
			}
		}

		if ( $mapped ) {

			echo '<a target="_blank" 
			href="' . esc_url( get_admin_url() ) . 'admin.php?page=sales_channel&channel=amazon&section=add-new-template&template_id=' . esc_attr( $mapped ) . '&user_id=' . esc_attr( $user_id ) . '&seller_id=' . esc_attr( $seller_id ) . '">'
				. esc_attr( $profile ) . '</a>';

		} else {
			echo esc_attr( 'No template assigned' );
		}
	}



	/**
	 *
	 * Function for stock column
	 */
	public function column_stock( $item ) {

		if ( 'instock' == $item['stock_status'] ) {
			if ( 0 == $item['stock'] || '0' == $item['stock'] ) {
				return '<div class="ced-connected-button-wrap"><a class="ced-connected-link"><b class="stock_alert_instock"><span class="ced-circle"></span>' . esc_attr( 'In stock', 'amazon-for-woocommerce' ) . '</b></a></div>';
			} else {
				return '<div class="ced-connected-button-wrap"><a class="ced-connected-link"><b class="stock_alert_instock"><span class="ced-circle"></span>In stock(' . $item['stock'] . ')</b></a></div>';
			}
		} else {
			return '<div class="ced-connected-button-wrap"><a class="ced-connected-link"><b class="stock_alert_outofstock"><span class="ced-circle" style="background:#e2401c;"></span>' . esc_attr( 'Out of stock', 'amazon-for-woocommerce' ) . '</b></a></div>';
		}
	}
	/**
	 *
	 * Function for category column
	 */
	public function column_category( $item ) {
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';

		if ( isset( $item['category'] ) ) {
			$allCategories = '';
			foreach ( $item['category'] as $key => $prodCat ) {
				$allCategories .= '<b>' . $prodCat . '</b><br>';
			}
			return $allCategories;
		}

		echo '</div></div>';
	}

	/**
	 *
	 * Function for price column
	 */
	public function column_price( $item ) {
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
		$currencySymbol = get_woocommerce_currency_symbol();
		return $currencySymbol . '&nbsp<b class="success_upload_on_amazon">' . $item['price'] . '</b></div></div>';
	}

	/**
	 *
	 * Function for product type column
	 */
	public function column_type( $item ) {
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';

		$product      = wc_get_product( $item['id'] );
		$product_type = $product->get_type();
		echo '<b>' . esc_html__( $product_type, 'amazon-for-woocommerce' ) . '</b></div></div>';
	}

	/**
	 *
	 * Function for sku column
	 */
	public function column_sku( $item ) {
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
		echo '<b>' . esc_html__( $item['sku'], 'amazon-for-woocommerce' ) . '</b>';
		echo '</div></div>';
	}

	/**
	 *
	 * Function for image column
	 */
	public function column_image( $item ) {
		$item_image = ( $item['image'] ) ? $item['image'] : wc_placeholder_img_src( 'woocommerce_thumbnail' );
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
		echo '<img height="50" width="50" src="' . esc_url( $item_image ) . '">';
		echo '</div></div>';
	}

	/**
	 *
	 * Function for status column
	 */
	public function column_status( $item ) {
		$actions             = array();
		$user_id             = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$seller_id           = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		$seller_loc_arr      = explode( '|', $seller_id );
		$mp_location         = isset( $seller_loc_arr['1'] ) ? $seller_loc_arr['0'] : '';
		$listing_id          = get_post_meta( $item['id'], 'ced_amazon_product_asin_' . $mp_location, true );
		$amazon_catalog_asin = get_post_meta( $item['id'], 'ced_amazon_catalog_asin_' . $mp_location, true );
		if ( ! empty( get_post_meta( $item['id'], 'ced_amazon_alt_prod_description_' . $item['id'] . '_' . $user_id, true ) ) || ! empty( get_post_meta( $item['id'], 'ced_amazon_alt_prod_title_' . $item['id'] . '_' . $user_id, true ) ) ) {
			echo '<button class="px-3 py-1 mr-3 text-white font-semibold bg-blue-500 rounded">Modified</button><br>';

		}
		if ( ! empty( get_post_meta( $item['id'], '_ced_amazon_relist_item_id_' . $user_id, true ) ) ) {
			echo '<button class="px-3 py-1 mr-3 text-white font-semibold bg-blue-500 rounded">Re-Listed</button><br>';
		}

		$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', false );
		if ( isset( $saved_amazon_details[ $seller_id ]['marketplace_url'] ) && ! empty( $saved_amazon_details[ $seller_id ]['marketplace_url'] ) ) {

			$view_url_production = $saved_amazon_details[ $seller_id ]['marketplace_url'] . 'dp/' . $listing_id;
			$catalog_asin_url    = $saved_amazon_details[ $seller_id ]['marketplace_url'] . 'dp/' . $amazon_catalog_asin;
		} else {
			$view_url_production = 'https://www.amazon.com/dp/' . $listing_id;
			$catalog_asin_url    = 'https://www.amazon.com/dp/' . $amazon_catalog_asin;
		}

		if ( isset( $listing_id ) && ! empty( $listing_id ) ) {
			$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';

			$view_url_sandbox  = 'https://sandbox.amazon.com/itm/' . $listing_id;
			$mode_of_operation = get_option( 'ced_amazon_mode_of_operation', '' );
			if ( 'sandbox' == $mode_of_operation ) {

				echo '<div class="admin-custom-action-button-outer">';
				echo '<div class="admin-custom-action-show-button-outer">';

				echo '<div class="ced-connected-button-wrap"><a class="ced-connected-link" target="_blank" href="' . esc_attr( $view_url_sandbox ) . '" ><span class="ced-circle"></span>' . esc_html__( 'View on Amazon', 'amazon-for-woocommerce' ) . '</a> </div>';
				echo '</div></div>';

			} elseif ( 'production' == $mode_of_operation ) {

				echo '<div class="admin-custom-action-button-outer">';
				echo '<div class="admin-custom-action-show-button-outer">';

				echo '<div class="ced-connected-button-wrap"><a class="ced-connected-link" target="_blank" href="' . esc_attr( $view_url_production ) . '" ><span class="ced-circle"></span>' . esc_html__( 'View on Amazon', 'amazon-for-woocommerce' ) . '</a> </div>';
				echo '</div></div>';

			} else {

				echo '<div class="admin-custom-action-button-outer">';
				echo '<div class="admin-custom-action-show-button-outer">';

				echo '<div class="ced-connected-button-wrap"><a class="ced-connected-link" target="_blank" href="' . esc_attr( $view_url_production ) . '" ><span class="ced-circle"></span>' . esc_html__( 'View on Amazon', 'amazon-for-woocommerce' ) . '</a> </div>';

				if ( isset( $amazon_catalog_asin ) && ! empty( $amazon_catalog_asin ) ) {
					echo '<br><div class="ced-connected-button-wrap"><a class="ced-connected-link" target="_blank" href="' . esc_url( $catalog_asin_url ) . '" ><span class="ced-circle"></span>' . esc_html__( 'View ASIN', 'amazon-for-woocommerce' ) . '</a> </div>';
				}
				echo '</div></div>';
			}
		} else {
			echo '<div class="admin-custom-action-button-outer">';
			echo '<div class="admin-custom-action-show-button-outer">';

			echo '<div class="ced-disconnected-button-wrap"><a class="ced-connected-link"><span class="ced-circle" style="background:#000000;"></span>' . esc_html__( 'Not uploaded', 'amazon-for-woocommerce' ) . '</a> </div>';
			if ( isset( $amazon_catalog_asin ) && ! empty( $amazon_catalog_asin ) ) {
			
				echo '<br><div class="ced-connected-button-wrap"><a class="ced-connected-link" target="_blank" href="' . esc_url( $catalog_asin_url ) . '" ><span class="ced-circle"></span>' . esc_html__( 'View ASIN', 'amazon-for-woocommerce' ) . '</a> </div>';      
			}
			echo '</div></div>';
		}
		return $this->row_actions( $actions );
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'       => '<input type="checkbox" />',
			'image'    => __( 'Image', 'amazon-for-woocommerce' ),
			'name'     => __( 'Name', 'amazon-for-woocommerce' ),
			'type'     => __( 'Type', 'amazon-for-woocommerce' ),
			'price'    => __( 'Price', 'amazon-for-woocommerce' ),
			'profile'  => __( 'Template assigned', 'amazon-for-woocommerce' ),
			'sku'      => __( 'Sku', 'amazon-for-woocommerce' ),
			'stock'    => __( 'Stock', 'amazon-for-woocommerce' ),
			'category' => __( 'Woo category', 'amazon-for-woocommerce' ),
			'status'   => __( 'Status', 'amazon-for-woocommerce' ),

		);
		/**
		 * Function to list order based on per page
		 *
		 * @param 'function'
		 * @param  integer 'limit'
		 * @return 'count'
		 * @since 1.0.0
		 */
		$columns = apply_filters( 'ced_amazon_alter_product_table_columns', $columns );
		return $columns;
	}

	/**
	 *
	 * Function to count number of responses in result
	 */
	public function get_count( $per_page, $page_number ) {
		$args = $this->GetFilteredData( $per_page, $page_number );
		if ( ! empty( $args ) && isset( $args['tax_query'] ) || isset( $args['meta_query'] ) || isset( $args['s'] ) ) {
			$args = $args;
		} else {
			$args = array( 'post_type' => 'product' );
		}
		$loop         = new WP_Query( $args );
		$product_data = $loop->posts;
		$product_data = $loop->found_posts;

		return $product_data;
	}

	/**
	 *
	 * Function for GetFilteredData
	 */

	public function GetFilteredData( $per_page, $page_number ) {

		$this->show_reset = false;
		$args             = array();
		$user_id          = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$seller_id        = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		$seller_loc_arr   = explode( '|', $seller_id );
		$mp_location      = isset( $seller_loc_arr['1'] ) ? $seller_loc_arr['0'] : '';

		if ( ( isset( $_GET['status_sorting'] ) || isset( $_GET['pro_cat_sorting'] ) || isset( $_GET['pro_type_sorting'] ) || isset( $_GET['pro_profile_sorting'] ) ) ) {
			$this->show_reset = true;

			if ( isset( $_REQUEST['pro_cat_sorting'] ) && ! empty( $_REQUEST['pro_cat_sorting'] ) ) {
				$pro_cat_sorting = isset( $_GET['pro_cat_sorting'] ) ? sanitize_text_field( $_GET['pro_cat_sorting'] ) : '';
				if ( '' != $pro_cat_sorting ) {
					$selected_cat          = array( $pro_cat_sorting );
					$tax_query             = array();
					$tax_queries           = array();
					$tax_query['taxonomy'] = 'product_cat';
					$tax_query['field']    = 'id';
					$tax_query['terms']    = $selected_cat;
					$args['tax_query'][]   = $tax_query;
				}
			}

			if ( isset( $_REQUEST['pro_type_sorting'] ) && ! empty( $_REQUEST['pro_type_sorting'] ) ) {
				$pro_type_sorting = isset( $_GET['pro_type_sorting'] ) ? sanitize_text_field( $_GET['pro_type_sorting'] ) : '';
				if ( '' != $pro_type_sorting ) {
					$selected_type         = array( $pro_type_sorting );
					$tax_query             = array();
					$tax_queries           = array();
					$tax_query['taxonomy'] = 'product_type';
					$tax_query['field']    = 'id';
					$tax_query['terms']    = $selected_type;
					$args['tax_query'][]   = $tax_query;
				}
			}

			if ( isset( $_REQUEST['status_sorting'] ) && ! empty( $_REQUEST['status_sorting'] ) ) {
				$status_sorting = isset( $_GET['status_sorting'] ) ? sanitize_text_field( $_GET['status_sorting'] ) : '';
				if ( '' != $status_sorting ) {
					$meta_query = array();
					if ( 'Uploaded' == $status_sorting ) {

						$meta_query[] = array(
							'key'     => 'ced_amazon_product_asin_' . $mp_location,
							'compare' => 'EXISTS',
						);
					} elseif ( 'NotUploaded' == $status_sorting ) {
						$meta_query[] = array(
							'key'     => 'ced_amazon_product_asin_' . $mp_location,
							'compare' => 'NOT EXISTS',
						);
					} elseif ( 'CatalogASIN' == $status_sorting ) {
						$meta_query[] = array(
							'key'     => 'ced_amazon_catalog_asin_' . $mp_location,
							'compare' => 'EXISTS',

						);
					}
					$args['meta_query'] = $meta_query;
				}
			}

			if ( isset( $_REQUEST['pro_stock_sorting'] ) && ! empty( $_REQUEST['pro_stock_sorting'] ) ) {
				$sort_by_stock = isset( $_GET['pro_stock_sorting'] ) ? sanitize_text_field( $_GET['pro_stock_sorting'] ) : '';
				if ( '' != $sort_by_stock ) {
					$meta_query = array();
					if ( 'instock' == $sort_by_stock ) {
						if ( 'Uploaded' == $_REQUEST['status_sorting'] ) {
							$args['meta_query'] = array(
								'relation' => 'AND',
								array(
									'key'     => 'ced_amazon_product_asin_' . $mp_location,
									'compare' => 'EXISTS',
								),
								array(
									'key'     => '_stock_status',
									'value'   => 'instock',
									'compare' => '=',
								),

							);

						} elseif ( 'NotUploaded' == $_REQUEST['status_sorting'] ) {
							$args['meta_query'] = array(
								'relation' => 'AND',
								array(
									'key'     => 'ced_amazon_product_asin_' . $mp_location,
									'compare' => 'NOT EXISTS',
								),
								array(
									'key'     => '_stock_status',
									'value'   => 'instock',
									'compare' => '=',
								),

							);

						} elseif ( 'CatalogASIN' == $_REQUEST['status_sorting'] ) {
							$args['meta_query'] = array(
								'relation' => 'AND',
								array(
									'key'     => 'ced_amazon_catalog_asin_' . $mp_location,
									'compare' => 'EXISTS',
								),
								array(
									'key'     => '_stock_status',
									'value'   => 'instock',
									'compare' => '=',
								),

							);

						} else {
							$args['meta_query'][] = array(
								'key'     => '_stock_status',
								'value'   => 'instock',
								'compare' => '=',
							);
						}
					} elseif ( 'outofstock' == $sort_by_stock ) {

						if ( 'Uploaded' == $_REQUEST['status_sorting'] ) {
							$args['meta_query'] = array(
								'relation' => 'AND',
								array(
									'key'     => 'ced_amazon_product_asin_' . $mp_location,
									'compare' => 'EXISTS',
								),
								array(
									'key'     => '_stock_status',
									'value'   => 'outofstock',
									'compare' => '=',
								),

							);

						} elseif ( 'NotUploaded' == $_REQUEST['status_sorting'] ) {
							$args['meta_query'] = array(
								'relation' => 'AND',
								array(
									'key'     => 'ced_amazon_product_asin_' . $mp_location,
									'compare' => 'NOT EXISTS',
								),

								array(
									'key'     => '_stock_status',
									'value'   => 'outofstock',
									'compare' => '=',
								),

							);

						} elseif ( 'CatalogASIN' == $_REQUEST['status_sorting'] ) {
							$args['meta_query'] = array(
								'relation' => 'AND',
								array(
									'key'     => 'ced_amazon_catalog_asin_' . $mp_location,
									'compare' => 'EXISTS',
								),

								array(
									'key'     => '_stock_status',
									'value'   => 'outofstock',
									'compare' => '=',
								),

							);

						} else {

							$args['meta_query'][] = array(
								'key'     => '_stock_status',
								'value'   => 'outofstock',
								'compare' => '=',
							);
						}
					}
				}
			}
		}

		if ( ! empty( $_REQUEST['s'] ) ) {
			$s = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
			if ( ! empty( $s ) ) {
				$args['s'] = $s;
			}
		}

		$args['post_type']      = 'product';
		$args['posts_per_page'] = $per_page;
		$args['paged']          = $page_number;

		return $args;
	}


	/**
	 *
	 * Render bulk actions
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

			echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . esc_attr( 'Select bulk action' ) . '</label>';
			echo '<select name="action' . esc_attr( $two ) . '" class="ced_amazon_select_amazon_product_action">';
			echo '<option value="-1">' . esc_attr( 'Bulk actions' ) . "</option>\n";

			foreach ( $this->_actions as $name => $title ) {
				$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

				echo "\t" . '<option value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_html( $title, 'amazon-for-woocommerce' ) . "</option>\n";
			}

			echo "</select>\n";

			wp_nonce_field( 'ced_amazon_product_bulk_action_page_nonce', 'ced_amazon_product_bulk_action_nonce' );
			submit_button( __( 'Apply' ), 'action', 'doaction', false, array( 'id' => 'ced_amazon_bulk_operation' ) );
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
			'upload_product'   => __( 'Upload', 'amazon-for-woocommerce' ),
			'relist_product'   => __( 'Relist Product', 'amazon-for-woocommerce' ),
			'update_inventory' => __( 'Update Inventory', 'amazon-for-woocommerce' ),
			'update_price'     => __( 'Update Price', 'amazon-for-woocommerce' ),
			'update_images'    => __( 'Update Images', 'amazon-for-woocommerce' ),
			'delete_product'   => __( 'Delete Listing', 'amazon-for-woocommerce' ),
			'look_up'          => __( 'Look up on amazon', 'amazon-for-woocommerce' ),
		);
		return $actions;
	}

	/**
	 *
	 * Function for rendering html
	 */
	public function renderHTML() {
		$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		?>
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<?php
					$status_actions = array(
						'Uploaded'    => __( 'Uploaded', 'amazon-for-woocommerce' ),
						'NotUploaded' => __( 'Not Uploaded', 'amazon-for-woocommerce' ),
						'CatalogASIN' => __( 'Amazon ASIN', 'amazon-for-woocommerce' ),
					);

					$product_types = get_terms( 'product_type' );
					$temp_array    = array();
					foreach ( $product_types as $key => $value ) {
						if ( 'simple' == $value->name || 'variable' == $value->name ) {
							$temp_array_type[ $value->term_id ] = ucfirst( $value->name );
						}
					}
					$product_types      = $temp_array_type;
					$product_categories = $this->ced_amazon_get_taxonomy_hierarchy( 'product_cat', 0, 0 );
					$temp_array         = array();

					$profiles_array = array();

					$assigned_profiles              = $profiles_array;
					$previous_selected_status       = isset( $_GET['status_sorting'] ) ? sanitize_text_field( $_GET['status_sorting'] ) : '';
					$previous_selected_cat          = isset( $_GET['pro_cat_sorting'] ) ? sanitize_text_field( $_GET['pro_cat_sorting'] ) : '';
					$previous_selected_type         = isset( $_GET['pro_type_sorting'] ) ? sanitize_text_field( $_GET['pro_type_sorting'] ) : '';
					$previous_selected_stock_status = isset( $_GET['pro_stock_sorting'] ) ? sanitize_text_field( $_GET['pro_stock_sorting'] ) : '';
					echo '<div class="ced_amazon_wrap">';
					echo '<form method="post" action="">';
					echo '<div class="ced_amazon_top_wrapper">';
					echo '<select name="status_sorting" class="select_boxes_product_page">';
					echo '<option value="">' . esc_attr( 'Product status', 'amazon-for-woocommerce' ) . '</option>';
					foreach ( $status_actions as $name => $title ) {
						$selectedStatus = ( $previous_selected_status == $name ) ? 'selected="selected"' : '';
						$class          = 'edit' === $name ? ' class="hide-if-no-js"' : '';
						echo '<option ' . esc_attr( $selectedStatus ) . ' value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . '</option>';
					}

					echo '</select>';
					$previous_selected_cat = isset( $_GET['pro_cat_sorting'] ) ? sanitize_text_field( $_GET['pro_cat_sorting'] ) : '';

					$dropdown_cat_args = array(
						'name'            => 'pro_cat_sorting',
						'show_count'      => 1,
						'hierarchical'    => 1,
						'taxonomy'        => 'product_cat',
						'class'           => 'select_boxes_product_page',
						'selected'        => $previous_selected_cat,
						'show_option_all' => 'Product category',
						'hide_if_empty'   => true,

					);
					wp_dropdown_categories( $dropdown_cat_args );
					echo '<select name="pro_type_sorting" class="select_boxes_product_page">';
					echo '<option value="">' . esc_attr( 'Product type', 'amazon-for-woocommerce' ) . '</option>';
					foreach ( $product_types as $name => $title ) {
						$selectedType = ( $previous_selected_type == $name ) ? 'selected="selected"' : '';
						$class        = 'edit' === $name ? ' class="hide-if-no-js"' : '';
						echo '<option ' . esc_attr( $selectedType ) . ' value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . '</option>';
					}
					echo '</select>';

					echo '<select name="pro_stock_sorting" class="select_boxes_product_page">';
					echo '<option value="">' . esc_attr( 'Stock status', 'amazon-for-woocommerce' ) . '</option>';
					echo '<option ' . esc_attr( ( 'instock' == $previous_selected_stock_status ) ? 'selected="selected"' : '' ) . ' value="instock">In Stock</option>';
					echo '<option ' . esc_attr( ( 'outofstock' == $previous_selected_stock_status ) ? 'selected="selected"' : '' ) . ' value="outofstock">Out Of Stock</option>';
					echo '</select>';

					wp_nonce_field( 'ced_amazon_product_filter_page_nonce', 'ced_amazon_product_filter_nonce' );

					submit_button( __( 'Filter', 'amazon-for-woocommerce' ), 'action', 'filter_button', false, array() );

					$this->search_box( 'Search', 'search_id', 'search_product' );

					if ( $this->show_reset ) {

						$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
						$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';

						echo '<span class="ced_reset"><a href="' . esc_url( admin_url( 'admin.php?page=sales_channel&channel=amazon&section=products-view&user_id=' . $user_id . '&seller_id=' . $seller_id ) ) . '" class="button">X</a></span>';
					}
						echo '</div>';
						echo '</form>';
						echo '</div>';
					?>
					  

					<form method="post">
					</div>
				</div>
				<?php

				$this->display();
				?>
			</form>
		</div>
	</div>
	<div class="clear"></div>
</div>
		<?php
	}


	public function column_report( $item ) {
		$seller_id     = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		$seller_id_val = str_replace( '|', '_', $seller_id );
		$product_feeds = get_post_meta( $item['id'], 'ced_amazon_feed_actions_' . $seller_id_val, true );

		if ( is_array( $product_feeds ) && ! empty( $product_feeds ) ) {

			global $wpdb;
			$tableName        = $wpdb->prefix . 'ced_amazon_feeds';
			$feed_request_ids = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_feeds WHERE `feed_id` = %d", $product_feeds['POST_FLAT_FILE_LISTINGS_DATA'] ), 'ARRAY_A' );
			$feed_request_id  = $feed_request_ids[0];
			$main_id          = $feed_request_id['id'];
			$feed_type        = $feed_request_id['feed_action'];
			$location_id      = $feed_request_id['feed_location'];
			$marketplace      = 'amazon_spapi';

			$response        = $feed_request_id['response'];
			$response_format = false;
			$response        = json_decode( $response, true );
			if ( isset( $response['status'] ) && 'DONE' == $response['status'] ) {
				$response        = $response;
				$response_format = true;

			} else {

				$feed_manager = Ced_Umb_Amazon_Feed_Manager::get_instance();
				$response     = $feed_manager->getFeedItemsStatusSpApi( $product_feeds['POST_FLAT_FILE_LISTINGS_DATA'], $feed_type, $location_id, $marketplace, $seller_id );

				if ( isset( $response['status'] ) && 'DONE' == $response['status'] ) {
					$response_format = true;
				}
				$response_data = wp_json_encode( $response );
				$wpdb->update( $tableName, array( 'response' => $response_data ), array( 'id' => $main_id ) );
			}

			if ( 'POST_FLAT_FILE_LISTINGS_DATA' == $feed_type ) {
				if ( $response_format ) {
					if ( isset( $main_id ) && ! isset( $response['body'] ) ) {
						echo 'record under process';

					} elseif ( isset( $response['body'] ) ) {
						$tab_response_data = explode( "\n", $response['body'] );
						foreach ( $tab_response_data as $tabKey => $tabValue ) {

							$line_data = explode( "\t", $tabValue );
							if ( 'Feed Processing Summary' == $line_data[0] || 'Feed Processing Summary:' == $line_data[0] ) {
								continue;
							} elseif ( empty( $line_data[0] ) || '' == $line_data[0] ) {
								continue;
							} elseif ( 'original-record-number' == $line_data[0] ) {
								continue;
							} elseif ( 'Error' == $line_data[3] ) {
								if ( '99001' == $line_data[2] ) {
									?>
										<span>Failure</span>
									<?php
								} elseif ( '90057' == $line_data[2] ) {
									?>
										<span> Failure </span>
									<?php
								} else {
									?>
										<span> Failure</span> </td>
										</div>
									<?php
								}

									break;
							}
						}
					}
				} elseif ( isset( $main_id ) && ! isset( $response['body'] ) ) {
						echo 'record under process';
				}
			}
		}
	}
}

$ced_amazon_products_obj = new AmazonListProducts();
$ced_amazon_products_obj->prepare_items();
?>
