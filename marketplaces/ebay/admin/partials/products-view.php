<?php


namespace Ced\Ebay;

// If this file is called directly, abort.
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

class EBayListProducts extends \WP_List_Table {


	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'ced-ebay-product-table', 'ebay-integration-for-woocommerce' ), // singular name of the listed records
				'plural'   => __( 'ced-ebay-products-table', 'ebay-integration-for-woocommerce' ), // plural name of the listed records
				'ajax'     => true, // does this table support ajax?
			)
		);
		wp_raise_memory_limit( 'admin' );
	}

	/**
	 *
	 * Function for preparing data to be displayed
	 */

	public function prepare_items() {

		global $wpdb;
		$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';

		if ( isset( $_POST['ced_ebay_view_entries_product_section'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_ebay_view_entries_product_section'] ), 'ced_ebay_view_entries_product_section_nonce' ) ) {
			$per_page_preference = ! empty( $_POST['ced_ebay_view_entries_prod_section_input'] ) ? sanitize_text_field( $_POST['ced_ebay_view_entries_prod_section_input'] ) : 0;
			if ( is_numeric( $per_page_preference ) ) {
				update_option( 'ced_ebay_product_section_per_page_' . $user_id, $per_page_preference );
			} else {
				?>
						<div class="notice notice-error">
							<p>Invalid input. Only numbers are allowed.</p>
			</div>
				<?php
			}
		}

		$per_page = ! empty( get_option( 'ced_ebay_product_section_per_page_' . $user_id ) ) ? get_option( 'ced_ebay_product_section_per_page_' . $user_id ) : 50;

		$post_type = 'product';
		$columns   = $this->get_columns();
		$hidden    = array();
		$sortable  = $this->get_sortable_columns();

		// Column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}
		$this->items = self::ced_ebay_get_product_details( $per_page, $current_page, $post_type );
		$count       = self::get_count( $per_page, $current_page );
		// Set the pagination
		$this->set_pagination_args(
			array(
				'total_items' => $count,
				'per_page'    => $per_page,
				'total_pages' => ceil( $count / $per_page ),
			)
		);
		// echo '<pre>';print_r($this);die;

		if ( ! $this->current_action() ) {
			// $this->items = self::ced_ebay_get_product_details( $per_page, $current_page ,$post_type  );
			$this->renderHTML();
		} else {
			$this->process_bulk_action();
		}
	}

	/**
	 *
	 * Function for get product data
	 */
	public function ced_ebay_get_product_details( $per_page = '', $page_number = '', $post_type = '' ) {
		$product_data = array();
		$filterFile   = CED_EBAY_DIRPATH . 'admin/partials/products-filters.php';
		if ( file_exists( $filterFile ) ) {
			require_once $filterFile;
		}
		$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$site_id = isset( $_GET['sid'] ) ? sanitize_text_field( $_GET['sid'] ) : '';

		$instanceOf_FilterClass = new \FilterClass();

		$args = $this->GetFilteredData( $per_page, $page_number );

		if ( ! empty( $args ) && isset( $args['tax_query'] ) || isset( $args['meta_query'] ) || isset( $args['searchTerm'] ) ) {
			$args = $args;
		} elseif ( ! isset( $args['prodID'] ) && ! isset( $args['search_by_sku'] ) && ! isset( $args['profileID'] ) ) {
			$args = array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => $per_page,
				'paged'          => $page_number,
			);
		} elseif ( isset( $args['search_by_sku'] ) ) {
			$args = array(
				'post_type'           => 'product',
				'post_status'         => 'publish',
				'ignore_sticky_posts' => 1,
				'meta_key'            => '_sku',
				'meta_value'          => $args['search_by_sku'],
				'meta_compare'        => 'LIKE',
			);
		}
		if ( isset( $args['prodID'] ) ) {
			$prod           = new \stdClass();
			$prod->ID       = $args['prodID'];
			$product_data[] = $prod->ID;
		} elseif ( isset( $args['profileID'] ) ) {
			$cat_args = array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'meta_query' => array(
					'key'     => 'ced_ebay_profile_id_' . $user_id . '>' . $site_id,
					'value'   => $args['profileID'],
					'compare' => 'LIKE',

				),
			);
			$filtered_categories = get_terms( $cat_args );

			if ( ! empty( $filtered_categories ) ) {
				$product_terms_ids = array();
				$product_terms     = '';
				foreach ( $filtered_categories as $key => $value ) {
					if ( ! empty( $value->term_id ) ) {
						$product_term_ids[] = $value->term_id;
					}
				}
				if ( is_array( $product_term_ids ) && ! empty( $product_term_ids ) ) {
					$product_terms = implode( ',', $product_term_ids );
				}

				if ( ! empty( $product_terms ) ) {
					if ( ! empty( $args['meta_query'] ) ) {
						$loop = new \WP_Query(
							array(
								'post_type'      => 'product',
								'post_status'    => 'publish',
								'posts_per_page' => ! empty( get_option( 'ced_ebay_product_section_per_page_' . $user_id, true ) ) ? get_option( 'ced_ebay_product_section_per_page_' . $user_id, true ) : 10,
								'paged'          => $page_number,
								'tax_query'      => array(
									array(
										'taxonomy'         => 'product_cat',
										'terms'            => $product_term_ids,
										'include_children' => false,
										'operator'         => 'IN',
									),
								),
								'meta_query'     => $args['meta_query'],
							)
						);
					} else {
						$loop = new \WP_Query(
							array(
								'post_type'      => 'product',
								'post_status'    => 'publish',
								'posts_per_page' => ! empty( get_option( 'ced_ebay_product_section_per_page_' . $user_id, true ) ) ? get_option( 'ced_ebay_product_section_per_page_' . $user_id, true ) : 10,
								'paged'          => $page_number,
								'tax_query'      => array(
									array(
										'taxonomy'         => 'product_cat',
										'terms'            => $product_term_ids,
										'include_children' => false,
										'operator'         => 'IN',
									),
								),
							)
						);
					}

					if ( $loop->have_posts() ) {
						update_option( 'ced_ebay_profile_filter_count_' . $user_id, $loop->found_posts );
						$product_data = wp_list_pluck( $loop->posts, 'ID' );
					}
				}
			}
		} else {
			if ( isset( $args['searchTerm'] ) && class_exists( '\WC_Data_Store' ) ) {
				$data_store = \WC_Data_Store::load( 'product' );
				$ids        = $data_store->search_products( $args['searchTerm'] );
				if ( ! empty( $ids ) && is_array( $ids ) ) {
					$args = array(
						'post_type'           => 'product',
						'post_status'         => 'publish',
						'ignore_sticky_posts' => 1,
						'post__in'            => $ids,
						'paged'               => $page_number,
					);
				}
			}
				$loop     = new \WP_Query( $args );
			$product_data = wp_list_pluck( $loop->posts, 'ID' );
		}

		$woo_categories = get_terms( 'product_cat' );
		$woo_products   = array();
		if ( is_array( $product_data ) && ! empty( $product_data ) ) {

			foreach ( $product_data as $key => $value ) {
				$get_product_data = wc_get_product( $value );
				// check if $get_product_data is a valid product or not
				if ( ! $get_product_data instanceof \WC_Product ) {
					continue;
				}
				$get_product_data = $get_product_data->get_data();
				if ( ! empty( $get_product_data['category_ids'] ) ) {
					rsort( $get_product_data['category_ids'] );
				}
				if ( ! empty( $args['profileID'] ) ) {
					foreach ( $get_product_data['category_ids'] as $key_cat => $cat_id ) {
						if ( ! empty( get_term_meta( $cat_id, 'ced_ebay_profile_id_' . $user_id . '>' . $site_id, true ) ) && ( get_term_meta( $cat_id, 'ced_ebay_profile_id_' . $user_id . '>' . $site_id, true ) ) == $args['profileID'] ) {
							$woo_products[ $key ]['category_id'][] = $cat_id;

						}
					}
				} else {
					$woo_products[ $key ]['category_id'] = isset( $get_product_data['category_ids'] ) ? $get_product_data['category_ids'] : '';

				}

				$woo_products[ $key ]['id']           = $value;
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
		}

		if ( isset( $_POST['ced_ebay_product_filter_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_ebay_product_filter_nonce'] ), 'ced_ebay_product_filter_page_nonce' ) ) {
			if ( isset( $_POST['filter_button'] ) ) {
				$woo_products = $instanceOf_FilterClass->ced_ebay_filters_on_products();
			}
		}
		if ( isset( $_POST['s'] ) && ! empty( $_POST['s'] ) ) {
			if ( isset( $_POST['ced_ebay_product_filter_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_ebay_product_filter_nonce'] ), 'ced_ebay_product_filter_page_nonce' ) ) {
				$s            = isset( $_POST['s'] ) ? sanitize_text_field( $_POST['s'] ) : '';
				$woo_products = $instanceOf_FilterClass->ced_ebay_product_search_box();

			}
		}

		return $woo_products;
	}

	/**
	 *
	 * Text displayed when no data is available
	 */
	public function no_items() {
		esc_html_e( 'No Products To Show.', 'ebay-integration-for-woocommerce' );
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
			'<input type="checkbox" name="ebay_product_ids[]" class="ebay_products_id" value="%s" /></div></div>',
			$item['id']
		);
	}

	/**
	 *
	 * Function for name column
	 */
	public function column_name( $item ) {
		$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$url     = get_edit_post_link( $item['id'], '' );
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
		echo '<b><a class="ced_ebay_prod_name" href="' . esc_attr( $url ) . '" target="_blank">' . esc_attr( $item['name'] ) . '</a></b><br>';
		echo '</div></div>';
		// $actions['modify'] = '<a href="#" data-prod-id="' . esc_html( $item['id'] ) . '">' . __( 'Modify', 'ebay-integration-for-woocommerce' ) . '</a>';
	}

	/**
	 *
	 * Function for profile column
	 */
	public function column_profile( $item ) {
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
		$user_id                      = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$site_id                      = isset( $_GET['sid'] ) ? sanitize_text_field( $_GET['sid'] ) : '';
		$get_profile_id_of_prod_level = get_post_meta( $item['id'], 'ced_ebay_profile_assigned' . isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '', true );
		$filtered_profile             = isset( $_GET['profileID'] ) ? sanitize_text_field( $_GET['profileID'] ) : '';
		if ( ! empty( $get_profile_id_of_prod_level ) ) {
			global $wpdb;
			$profile_name = $wpdb->get_results( $wpdb->prepare( "SELECT `profile_name` FROM {$wpdb->prefix}ced_ebay_profiles WHERE `id` = %s AND `ebay_user`=%s AND `ebay_site`=%s", $get_profile_id_of_prod_level, $user_id, $site_id ), 'ARRAY_A' );
			$profile_name = isset( $profile_name[0]['profile_name'] ) ? $profile_name[0]['profile_name'] : '';
			echo '<b>' . esc_attr( $profile_name ) . '</b>';
			$profile_id = $get_profile_id_of_prod_level;
		} else {
			$get_ebay_category_id = '';
			if ( isset( $item['category_id'] ) ) {
				foreach ( $item['category_id'] as $key => $category_id ) {
					$get_ebay_category_id_data = get_term_meta( $category_id );
					$get_ebay_category_id      = ! empty( $get_ebay_category_id_data[ 'ced_ebay_mapped_category_' . $user_id . '>' . $site_id ] ) ? $get_ebay_category_id_data[ 'ced_ebay_mapped_category_' . $user_id . '>' . $site_id ] : '';
					if ( ! empty( $get_ebay_category_id ) ) {
						break;
					}
				}
			}
			if ( empty( $get_ebay_category_id ) && ! empty( $get_ebay_category_id_data[ 'ced_ebay_profile_id_' . $user_id . '>' . $site_id ] ) ) {
				$ebay_profile_id = $get_ebay_category_id_data[ 'ced_ebay_profile_id_' . $user_id . '>' . $site_id ][0];
				global $wpdb;
				$profile_data           = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_ebay_profiles WHERE `id`=%d AND `ebay_user`=%s AND `ebay_site=`%s", $ebay_profile_id, $user_id, $site_id ), 'ARRAY_A' );
				$profile_category_data  = json_decode( $profile_data[0]['profile_data'], true );
				$profile_category_data  = isset( $profile_category_data ) ? $profile_category_data : '';
				$get_ebay_category_id[] = isset( $profile_category_data['_umb_ebay_category']['default'] ) ? $profile_category_data['_umb_ebay_category']['default'] : '';
			}
			if ( ! empty( $get_ebay_category_id ) ) {
					$profile_id   = isset( $get_ebay_category_id_data[ 'ced_ebay_profile_id_' . $user_id . '>' . $site_id ] ) ? $get_ebay_category_id_data[ 'ced_ebay_profile_id_' . $user_id . '>' . $site_id ] : '';
					$profile_name = isset( $get_ebay_category_id_data[ 'ced_ebay_profile_name_' . $user_id . '>' . $site_id ] ) ? $get_ebay_category_id_data[ 'ced_ebay_profile_name_' . $user_id . '>' . $site_id ] : '';
					$profile_id   = isset( $profile_id[0] ) ? $profile_id[0] : 0;
					$profile_name = isset( $profile_name[0] ) ? $profile_name[0] : '';
				if ( empty( $profile_id ) || empty( $profile_name ) ) {
					echo '<b class="not_completed">' . esc_attr( 'No Profile Assigned', 'ebay-integration-for-woocommerce' ) . '</b>';
					return;
				}
					$assigned_profile_html = sprintf( '<a target="_blank" data-profile-id="%s" data-ebay-cat-id="%s" href="?page=%s&channel=ebay&section=view-templates&user_id=%s&profileID=%s&eBayCatID=%s&site_id=%s">%s</a>', $profile_id, $get_ebay_category_id[0], esc_attr( isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '' ), esc_attr( isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '' ), $profile_id, ! empty( $get_ebay_category_id[0] ) ? $get_ebay_category_id[0] : 0, $site_id, $profile_name );
					print_r( $assigned_profile_html );
			} else {
				echo '<b class="not_completed">' . esc_attr( 'No Profile Assigned', 'ebay-integration-for-woocommerce' ) . '</b>';
			}
		}
		echo '</div></div>';
	}
	/**
	 *
	 * Function for stock column
	 */
	public function column_stock( $item ) {
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';

		if ( 'instock' == $item['stock_status'] ) {
			if ( 0 == $item['stock'] || '0' == $item['stock'] ) {
				return '<b class="stock_alert_instock" >' . esc_attr( 'In Stock', 'ebay-integration-for-woocommerce' ) . '</b>';
			} else {
				return '<b class="stock_alert_instock">In Stock(' . $item['stock'] . ')</b>';
			}
		} else {
			return '<b class="stock_alert_outofstock" >' . esc_attr( 'Out of Stock', 'ebay-integration-for-woocommerce' ) . '</b>';
		}

		echo '</div></div>';
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
				$allCategories .= '<b>-->' . $prodCat . '</b><br>';
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
		return $currencySymbol . '&nbsp<b class="success_upload_on_ebay">' . $item['price'] . '</b>';
		echo '</div></div>';
	}
	/**
	 *
	 * Function for product type column
	 */
	public function column_type( $item ) {
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';

		$product      = wc_get_product( $item['id'] );
		$product_type = $product->get_type();
		return '<b>' . $product_type . '</b>';
		echo '</div></div>';
	}
	/**
	 *
	 * Function for sku column
	 */
	public function column_sku( $item ) {
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
		return '<b>' . $item['sku'] . '</b>';
		echo '</div></div>';
	}
	/**
	 *
	 * Function for image column
	 */
	public function column_image( $item ) {
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
		return '<img height="50" width="50" src="' . $item['image'] . '">';
		echo '</div></div>';
	}

	public function column_woo_id( $item ) {
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
		return '<b class="success_upload_on_ebay">#' . $item['id'] . '</b>';
		echo '</div></div>';
	}
	/**
	 *
	 * Function for status column
	 */
	public function column_status( $item ) {
		$actions    = array();
		$user_id    = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$site_id    = isset( $_GET['sid'] ) ? sanitize_text_field( $_GET['sid'] ) : '';
		$listing_id = get_post_meta( $item['id'], '_ced_ebay_listing_id_' . $user_id . '>' . $site_id, true );
		if ( ! empty( get_post_meta( $item['id'], 'ced_ebay_alt_prod_description_' . $item['id'] . '_' . $user_id, true ) ) || ! empty( get_post_meta( $item['id'], 'ced_ebay_alt_prod_title_' . $item['id'] . '_' . $user_id, true ) ) ) {
			echo '<button class="px-3 py-1 mr-3 text-white font-semibold bg-blue-500 rounded">Modified</button><br>';

		}
		if ( ! empty( get_post_meta( $item['id'], '_ced_ebay_relist_item_id_' . $user_id . '>' . $site_id, true ) ) ) {
			echo '<button class="px-3 py-1 mr-3 text-white font-semibold bg-blue-500 rounded">Re-Listed</button><br>';
		}
		if ( isset( $listing_id ) && ! empty( $listing_id ) ) {
			if ( ! empty( get_option( 'ced_ebay_listing_url_tld_' . $user_id . '>' . $site_id ) ) ) {
				$listing_url_tld     = get_option( 'ced_ebay_listing_url_tld_' . $user_id . '>' . $site_id, true );
				$view_url_production = 'https://www.ebay' . $listing_url_tld . '/itm/' . $listing_id;
				$view_url_sandbox    = 'https://sandbox.ebay' . $listing_url_tld . '/itm/' . $listing_id;
			} else {
				$view_url_production = 'https://www.ebay.com/itm/' . $listing_id;
				$view_url_sandbox    = 'https://sandbox.ebay.com/itm/' . $listing_id;

			}
			$mode_of_operation = get_option( 'ced_ebay_mode_of_operation', '' );
			if ( 'sandbox' == $mode_of_operation ) {
				echo '<div class="ced-connected-button-wrap">';
				echo '<a target="_blank" href="' . esc_attr( $view_url_sandbox ) . '" class="ced-connected-link"><span class="ced-circle"></span>View on eBay</a>';
				echo '</div>';
			} elseif ( 'production' == $mode_of_operation ) {
				echo '<div class="ced-connected-button-wrap">';
				echo '<a target="_blank" href="' . esc_attr( $view_url_production ) . '" class="ced-connected-link"><span class="ced-circle"></span>View on eBay</a>';
				echo '</div>';
			} else {
				echo '<div class="ced-connected-button-wrap">';
				echo '<a target="_blank" href="' . esc_attr( $view_url_production ) . '" class="ced-connected-link"><span class="ced-circle"></span>View on eBay</a>';
				echo '</div>';
			}
		} else {
			echo '<div class="ced-disconnected-button-wrap">';
			echo '<a class="ced-connected-link"><span class="ced-circle" style="background:#000000;"></span>Not Uploaded</a>';
			echo '</div>';
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
			'woo_id'   => __( 'Woo ID', 'ebay-integration-for-woocommerce' ),
			'image'    => __( 'Image', 'ebay-integration-for-woocommerce' ),
			'name'     => __( 'Name', 'ebay-integration-for-woocommerce' ),
			'type'     => __( 'Type', 'ebay-integration-for-woocommerce' ),
			'price'    => __( 'Price', 'ebay-integration-for-woocommerce' ),
			'profile'  => __( 'Profile Assigned', 'ebay-integration-for-woocommerce' ),
			'sku'      => __( 'SKU', 'ebay-integration-for-woocommerce' ),
			'stock'    => __( 'Stock', 'ebay-integration-for-woocommerce' ),
			'category' => __( 'Woo Category', 'ebay-integration-for-woocommerce' ),
			'status'   => __( 'Status', 'ebay-integration-for-woocommerce' ),
		);
		return $columns;
	}

	/**
	 *
	 * Function to count number of responses in result
	 */
	public function get_count( $per_page, $page_number ) {
		$args    = $this->GetFilteredData( $per_page, $page_number );
		$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$site_id = isset( $_GET['sid'] ) ? sanitize_text_field( $_GET['sid'] ) : '';
		if ( ! empty( $args['profileID'] ) ) {
			$profile_filter_count = get_option( 'ced_ebay_profile_filter_count_' . $user_id, true );
			return $profile_filter_count;
		} elseif ( ( ! empty( $args ) && isset( $args['tax_query'] ) || isset( $args['meta_query'] ) || isset( $args['searchTerm'] ) ) ) {
			$args = $args;
		} else {
			$args = array( 'post_type' => 'product' );
		}

		if ( isset( $args['searchTerm'] ) && class_exists( '\WC_Data_Store' ) ) {
			$data_store = \WC_Data_Store::load( 'product' );
			$ids        = $data_store->search_products( $args['searchTerm'] );
			if ( ! empty( $ids ) && is_array( $ids ) ) {
				$args = array(
					'post_type'           => 'product',
					'post_status'         => 'publish',
					'ignore_sticky_posts' => 1,
					'post__in'            => $ids,
				);
			}

			$loop         = new \WP_Query( $args );
			$product_data = $loop->posts;
			$product_data = $loop->found_posts;
			return $product_data;

		}
		$loop         = new \WP_Query( $args );
		$product_data = $loop->posts;
		$product_data = $loop->found_posts;

		return $product_data;
	}

	public function GetFilteredData( $per_page, $page_number ) {
		$args    = array();
		
		

		$product_filters = new \Ced\Ebay\Product_Filters();
		$args = $product_filters->getFilteredData($per_page, $page_number);

		
		if ( ! empty( $_REQUEST['searchTerm'] ) ) {
			$search_by = isset( $_GET['searchTerm'] ) ? sanitize_text_field( wp_unslash( $_GET['searchTerm'] ) ) : '';
			if ( ! empty( $search_by ) ) {
				$args['searchTerm'] = $search_by;
		}
	}		
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
			echo '<select name="action' . esc_attr( $two ) . '" class="ced_ebay_select_ebay_product_action">';
			echo '<option value="-1">' . esc_attr( 'Bulk Actions' ) . "</option>\n";

			foreach ( $this->_actions as $name => $title ) {
				$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

				echo "\t" . '<option value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . "</option>\n";
			}

			echo "</select>\n";

			submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => 'ced_ebay_bulk_operation' ) );
			echo "\n";
		endif;
	}

	protected function product_search( WC_Product $current_product ) {
		?>
		<label class="screen-reader-text" for="filter-by-product"><?php esc_html_e( 'Filter by product', 'woocommerce' ); ?></label>
		<select
			id="filter-by-product"
			class="wc-product-search"
			name="product_id"
			style="width: 200px;"
			data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>"
			data-action="woocommerce_json_search_products_and_variations"
			data-allow_clear="true">
			<?php if ( $current_product instanceof WC_Product ) : ?>
				<option value="<?php echo esc_attr( $current_product->get_id() ); ?>" selected="selected"><?php echo esc_html( $current_product->get_formatted_name() ); ?></option>
			<?php endif; ?>
		</select>
		<?php
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'upload_product' => __( 'Upload', 'ebay-integration-for-woocommerce' ),
			'relist_product' => __( 'Relist', 'ebay-integration-for-woocommerce' ),
			'link_with_eBay' => __( 'Link with eBay', 'ebay-integration-for-woocommerce' ),
			'update_product' => __( 'Update Product', 'ebay-integration-for-woocommerce' ),
			'update_stock'   => __( 'Update Inventory', 'ebay-integration-for-woocommerce' ),
			'remove_product' => __( 'End/Reset Product', 'ebay-integration-for-woocommerce' ),

		);
		return $actions;
	}
	/**
	 *
	 * Function for rendering html
	 */
	public function renderHTML() {
		$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$site_id = isset( $_GET['sid'] ) ? sanitize_text_field( $_GET['sid'] ) : '';
		?>
		

		

		<div class="ced-ebay-products-view-notice success-admin-notices is-dismissible"></div>

		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<?php
					$status_actions = array(
						'Uploaded'    => __( 'Uploaded', 'ebay-integration-for-woocommerce' ),
						'NotUploaded' => __( 'Not Uploaded', 'ebay-integration-for-woocommerce' ),
					);

					$product_types = get_terms( 'product_type' );
					$temp_array_type    = array();
					foreach ( $product_types as $key => $value ) {
						if ( 'simple' == $value->name || 'variable' == $value->name ) {
							$temp_array_type[ $value->term_id ] = ucfirst( $value->name );
						}
					}
					$product_types      = $temp_array_type;
					$product_categories = $this->ced_ebay_get_taxonomy_hierarchy( 'product_cat', 0, 0 );
					$temp_array         = array();
					// foreach ( $product_categories as $key => $value ) {
					// $temp_array[ $value->term_id ] = $value->name;
					// }
					// $product_categories = $temp_array;
					$profiles_array = array();
					global $wpdb;
					$tableName         = $wpdb->prefix . 'ced_ebay_profiles';
					$assigned_profiles = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_ebay_profiles WHERE `ebay_user`=%s AND `ebay_site`=%s", $user_id, $site_id ), 'ARRAY_A' );
					foreach ( $assigned_profiles as $key => $ced_profiles ) {
						$profiles_array[ $ced_profiles['id'] ] = $ced_profiles['profile_name'];
					}
					$assigned_profiles              = $profiles_array;
					$previous_selected_status       = isset( $_GET['status_sorting'] ) ? sanitize_text_field( $_GET['status_sorting'] ) : '';
					$previous_selected_cat          = isset( $_GET['pro_cat_sorting'] ) ? sanitize_text_field( $_GET['pro_cat_sorting'] ) : '';
					$previous_selected_type         = isset( $_GET['pro_type_sorting'] ) ? sanitize_text_field( $_GET['pro_type_sorting'] ) : '';
					$previous_selected_stock_status = isset( $_GET['pro_stock_sorting'] ) ? sanitize_text_field( $_GET['pro_stock_sorting'] ) : '';
					echo '<div class="ced_ebay_wrap">';
					echo '<form method="post" action="">';
					echo '<div class="ced_ebay_top_wrapper">';
					echo '<select name="status_sorting" class="select_boxes_product_page">';
					echo '<option value="">' . esc_attr( 'Product Status', 'ebay-integration-for-woocommerce' ) . '</option>';
					foreach ( $status_actions as $name => $title ) {
						$selectedStatus = ( $previous_selected_status == $name ) ? 'selected="selected"' : '';
						$class          = 'edit' === $name ? ' class="hide-if-no-js"' : '';
						echo '<option ' . esc_attr( $selectedStatus ) . ' value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . '</option>';
					}

					echo '</select>';
					$previous_selected_cat = isset( $_GET['pro_cat_sorting'] ) ? sanitize_text_field( $_GET['pro_cat_sorting'] ) : '';
					echo '<select name="pro_type_sorting" class="select_boxes_product_page">';
					echo '<option value="">' . esc_attr( 'Product Type', 'ebay-integration-for-woocommerce' ) . '</option>';
					foreach ( $product_types as $name => $title ) {
						$selectedType = ( $previous_selected_type == $name ) ? 'selected="selected"' : '';
						$class        = 'edit' === $name ? ' class="hide-if-no-js"' : '';
						echo '<option ' . esc_attr( $selectedType ) . ' value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . '</option>';
					}
					echo '</select>';

					echo '<select name="pro_stock_sorting" class="select_boxes_product_page">';
					echo '<option value="">' . esc_attr( 'Stock Status', 'ebay-integration-for-woocommerce' ) . '</option>';
					echo '<option ' . esc_attr( ( 'instock' == $previous_selected_stock_status ) ? 'selected="selected"' : '' ) . ' value="instock">In Stock</option>';
					echo '<option ' . esc_attr( ( 'outofstock' == $previous_selected_stock_status ) ? 'selected="selected"' : '' ) . ' value="outofstock">Out Of Stock</option>';
					echo '</select>';

					// $dropdown_cat_args = array(
					// 'name'            => 'pro_cat_sorting',
					// 'show_count'      => 1,
					// 'hierarchical'    => 1,
					// 'depth'           => 10,
					// 'taxonomy'        => 'product_cat',
					// 'class'           => 'ced_ebay_category_dropdown_product_page',
					// 'selected'        => $previous_selected_cat,
					// 'show_option_all' => 'Product Category',

					// );
					// if ( ! isset( $_GET['profileID'] ) ) {
					// wp_dropdown_categories( $dropdown_cat_args );
					// }

					$product_categories = get_terms(
						array(
							'taxonomy'   => 'product_cat',
							'hide_empty' => false,
							'orderby'    => 'name',
							'order'      => 'ASC',
						)
					);

					echo '<select style="width:17% !important;"name="pro_cat_sorting" id="pro_cat_sorting"><option value="-1">Filter By Category</option>';
					foreach ( $product_categories as $items ) {
						print_r( $this->ced_woocommerce_cateogories( $items ) );
					}
					echo '</select>';
					wp_nonce_field( 'ced_ebay_product_filter_page_nonce', 'ced_ebay_product_filter_nonce' );
					submit_button( __( 'Filter', 'ebay-integration-for-woocommerce' ), 'action', 'filter_button', false, array() );

					$this->search_box( 'Search', 'ced_ebay_search_id', 'ced_ebay_search_product' );

					?>


					<?php
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

	public function ced_ebay_categories_tree( $value, $cat_name ) {
		$cat_array = array();
		if ( 0 != $value->parent ) {
			$parent_id = $value->parent;
			$cat_batch = get_term( $parent_id );
			if ( ! empty( $cat_batch ) ) {
				$cat_batch_name = $cat_batch->name . ' > ';
			}
			$cat_name = $cat_batch_name . '' . $cat_name;
			if ( 0 != $cat_batch->parent ) {
				$cat_name = $this->ced_ebay_categories_tree( $cat_batch, $cat_name );
			}
		}
		return $cat_name;
	}

	public function ced_woocommerce_cateogories( $items ) {
		$cat_name = $items->name;
		$cat_name = $this->ced_ebay_categories_tree( $items, $cat_name );
		echo '<option value="' . esc_attr( $items->term_id ) . '">' . esc_attr( $cat_name ) . '</option>';
	}

	public function extra_tablenav( $which ) {
		$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		if ( 'top' == $which ) {
			ob_start();
			?>

		<div class="alignleft actions bulkactions" style="padding-right:0px !important;">

		<input type="text" placeholder="No. of entries to view" name="ced_ebay_view_entries_prod_section_input" style="line-height:0;" value="<?php echo ! empty( get_option( 'ced_ebay_product_section_per_page_' . $user_id ) ) ? esc_attr( get_option( 'ced_ebay_product_section_per_page_' . $user_id, true ) ) : ''; ?>">

		<button style="vertical-align:bottom;" type="submit" class="button" name="ced_ebay_view_entries_product_section">
<span>Save</span>
</button>


		</div>
	 


			<?php
			ob_flush();

			?>
			<div class="alignright actions bulkactions">

				
			<?php wp_nonce_field( 'ced_ebay_view_entries_product_section_nonce', 'ced_ebay_view_entries_product_section' ); ?>


			</div>
			<?php
		}
	}
}

$ced_ebay_products_obj = new EBayListProducts();
$ced_ebay_products_obj->prepare_items();

?>



<script>
		
(function( $ ) {

	jQuery(document.body).trigger('wc-enhanced-select-init');
		jQuery('#pro_cat_sorting').selectWoo({
			width:'resolve'
		});

jQuery(".ced_ebay_category_dropdown_product_page").selectWoo(
		{
		dropdownPosition: 'below',
		dropdownAutoWidth : true,
		}
	);
})(jQuery);



</script>

