<?php
/**
 * Product listing in manage products
 *
 * @package  Woocommerce_Walmart_Integration
 * @version  1.0.0
 * @link     https://woocommerce.com/vendor/cedcommerce/
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
get_walmart_header();
if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Ced_Walmart_Products_List
 *
 * @since 1.0.0
 */
class Ced_Walmart_Products_List extends WP_List_Table {


	public $show_reset;
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Product', 'walmart-woocommerce-integration' ), // singular name of the listed records
				'plural'   => __( 'Products', 'walmart-woocommerce-integration' ), // plural name of the listed records
				'ajax'     => true, // does this table support ajax?
			)
		);
	}

	public function prepare_items() {

		global $wpdb;
		$per_page  = 25;
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
		$this->items = self::get_product_details( $per_page, $current_page, $post_type );
		$count       = self::get_count( $per_page, $current_page );

		// Set the pagination
		$this->set_pagination_args(
			array(
				'total_items' => $count,
				'per_page'    => $per_page,
				'total_pages' => ceil( $count / $per_page ),
			)
		);

		if ( ! $this->current_action() ) {
			$this->items = self::get_product_details( $per_page, $current_page, $post_type );
			$this->renderHTML();
		} else {
			$this->process_bulk_action();
		}
	}


	public function get_product_details( $per_page = '', $page_number = 1, $post_type = 'product' ) {
		$filter_file = CED_WALMART_DIRPATH . 'admin/partials/class-ced-walmart-products-filter.php';
		if ( file_exists( $filter_file ) ) {
			include_once $filter_file;
		}

		$instance_of_filter_class = new Ced_Walmart_Products_Filter();
		$store_id                 = isset( $_GET['store_id'] ) ? sanitize_text_field( wp_unslash( $_GET['store_id'] ) ) : '';
		$args                     = $this->GetFilteredData( $per_page, $page_number );
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
		$loop                 = new WP_Query( $args );
		$product_data         = $loop->posts;
		$wooProducts          = array();
		foreach ( $product_data as $key => $value ) {
			$prodID        = $value->ID;
			$productDATA   = wc_get_product( $prodID );
			$productDATA   = $productDATA->get_data();
			$wooProducts[] = $productDATA;
		}

		if ( isset( $_POST['filter_button'] ) ) {
			if ( ! isset( $_POST['manage_product_filters'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['manage_product_filters'] ) ), 'manage_products' ) ) {
				return;
			}
			$wooProducts = $instance_of_filter_class->ced_walmart_filters_on_products( $wooProducts, $store_id );
		} elseif ( isset( $_POST['s'] ) ) {
			if ( ! isset( $_POST['manage_product_filters'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['manage_product_filters'] ) ), 'manage_products' ) ) {
				return;
			}
			$wooProducts = $instance_of_filter_class->product_search_box( $wooProducts, $store_id );
		}
		return $wooProducts;
	}


	public function GetFilteredData( $per_page, $page_number ) {
		$this->show_reset = false;
		if ( isset( $_GET['status_sorting'] ) || isset( $_GET['pro_cat_sorting'] ) || isset( $_GET['pro_type_sorting'] ) || isset( $_GET['s'] ) || isset( $_GET['stock_status'] ) ) {
			$this->show_reset = true;
			if ( ! empty( $_REQUEST['pro_cat_sorting'] ) ) {
				$pro_cat_sorting = isset( $_GET['pro_cat_sorting'] ) ? sanitize_text_field( wp_unslash( $_GET['pro_cat_sorting'] ) ) : '';
				if ( ! empty( $pro_cat_sorting ) ) {
					$selected_cat          = array( $pro_cat_sorting );
					$tax_query             = array();
					$tax_queries           = array();
					$tax_query['taxonomy'] = 'product_cat';
					$tax_query['field']    = 'id';
					$tax_query['terms']    = $selected_cat;
					$args['tax_query'][]   = $tax_query;
				}
			}

			if ( ! empty( $_REQUEST['pro_type_sorting'] ) ) {
				$pro_type_sorting = isset( $_GET['pro_type_sorting'] ) ? sanitize_text_field( wp_unslash( $_GET['pro_type_sorting'] ) ) : '';
				if ( ! empty( $pro_type_sorting ) ) {
					$selected_type         = array( $pro_type_sorting );
					$tax_query             = array();
					$tax_queries           = array();
					$tax_query['taxonomy'] = 'product_type';
					$tax_query['field']    = 'id';
					$tax_query['terms']    = $selected_type;
					$args['tax_query'][]   = $tax_query;
				}
			}

			if ( ! empty( $_REQUEST['status_sorting'] ) ) {
				$status_sorting = isset( $_GET['status_sorting'] ) ? sanitize_text_field( wp_unslash( $_GET['status_sorting'] ) ) : '';
				$store_id       = isset( $_GET['store_id'] ) ? sanitize_text_field( wp_unslash( $_GET['store_id'] ) ) : '';
				if ( ! empty( $status_sorting ) ) {
					$meta_query = array();
					if ( 'Uploaded' == $status_sorting ) {
						$args['orderby'] = 'meta_value_num';
						$args['order']   = 'ASC';

						$meta_query[] = array(
							'key'     => 'ced_walmart_product_uploaded' . $store_id . wifw_environment(),
							'compare' => 'EXISTS',
						);
					} elseif ( 'NotUploaded' == $status_sorting ) {
						$meta_query[] = array(
							'key'     => 'ced_walmart_product_uploaded' . $store_id . wifw_environment(),
							'compare' => 'NOT EXISTS',
						);
					}
				}
			}

			if ( ! empty( $_REQUEST['s'] ) ) {
				$s = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
				if ( ! empty( $s ) ) {
					$args['s'] = $s;
				}
			}

			if ( ! empty( $_REQUEST['stock_status'] ) ) {
				$stock_status = isset( $_GET['stock_status'] ) ? sanitize_text_field( wp_unslash( $_GET['stock_status'] ) ) : '';

				$meta_query[] = array(
					'key'     => '_stock_status',
					'compare' => '=',
					'value'   => $stock_status,
				);

			}

			if ( ! empty( $_GET['stock_status'] ) && ! empty( $_GET['status_sorting'] ) ) {
				$meta_query['relation'] = 'AND';
			}
			if ( ! empty( $meta_query ) ) {
				$args['meta_query'] = $meta_query;
			}

			$args['post_type']      = 'product';
			$args['posts_per_page'] = $per_page;
			$args['paged']          = $page_number;
			return $args;
		}
	}

	public function no_items() {
		esc_html_e( 'No Products To Show.', 'walmart-woocommerce-integration' );
	}

	/**
	 *
	 * Function to count number of responses in result
	 */
	public function get_count( $per_page, $page_number ) {
		$args = $this->GetFilteredData( $per_page, $page_number );
		if ( ! empty( $args ) && isset( $args['tax_query'] ) || isset( $args['meta_query'] ) ) {
			$args = $args;
		} else {
			$args = array( 'post_type' => 'product' );
		}
		$args['product_type'] = array( 'simple', 'variable' );
		$args['post_status']  = 'publish';
		$args['order']        = 'DESC';
		$args['orderby']      = 'ID';
		$loop                 = new WP_Query( $args );
		$product_data         = $loop->posts;
		$product_data         = $loop->found_posts;
		return $product_data;
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

	public function column_cb( $item ) {

		return sprintf(
			'<input type="checkbox" name="walmart_product_ids[]" class="walmart_products_id" value="%s" />',
			$item['id']
		);
	}


	public function column_price( $item ) {
		$price = get_post_meta( $item['id'], '_price', true );
		if ( empty( $price ) ) {
			$price = get_post_meta( $item['id'], '_regular_price', true );
		}
		print_r( wc_price( $price ) );
	}

	public function column_sku( $item ) {
		$sku = get_post_meta( $item['id'], '_sku', true );

		echo( esc_attr( $sku ) );
	}

	public function column_name( $item ) {
		$product           = wc_get_product( $item['id'] );
		$product_type      = $product->get_type();
		$store_id          = isset( $_GET['store_id'] ) ? sanitize_text_field( wp_unslash( $_GET['store_id'] ) ) : '';
		$editUrl           = get_edit_post_link( $item['id'], '' );
		$actions['id']     = '<strong>ID :' . $item['id'] . '</strong>';
		$actions['status'] = '<strong>' . ucwords( $item['status'] ) . '</strong>';
		$actions['type']   = '<strong>' . ucwords( $product_type ) . '</strong>';
		echo '<b><a class="ced_walmart_prod_name" href="' . esc_attr( $editUrl ) . '" >' . esc_attr( $item['name'] ) . '</a></b>';
		return $this->row_actions( $actions, true );
	}

	public function column_profile( $item ) {
		$store_id = isset( $_GET['store_id'] ) ? sanitize_text_field( wp_unslash( $_GET['store_id'] ) ) : '';

		$is_profile_assigned  = false;
		$actions              = array();
		$category_ids         = isset( $item['category_ids'] ) ? $item['category_ids'] : array();
		$mapped_cat           = !empty(get_option( 'ced_mapped_cat_MP_ITEM' )) ? json_decode( get_option( 'ced_mapped_cat_MP_ITEM' ), 1) : array();
		$wfsMappedCat         = !empty(get_option('ced_mapped_cat_MP_WFS_ITEM')) ? json_decode( get_option('ced_mapped_cat_MP_WFS_ITEM') , 1) : array();
		$convert_wfsMappedCat = !empty(get_option('ced_mapped_cat_OMNI_WFS')) ? json_decode(get_option('ced_mapped_cat_OMNI_WFS') , 1) : array();
		$category             = '';
		$wfsCategory          = '';
		$convertWfsCategory   = '';

		if ( isset( $mapped_cat['profile'] ) ) {
			foreach ( $category_ids as $index => $term_id ) {
				foreach ( $mapped_cat['profile'] as $key => $value ) {
					if ( in_array( $term_id, $value['woo_cat'] ) ) {
						$category = $key;

					}
				}
			}
		}

		if ( isset( $wfsMappedCat['profile'] ) ) {
			foreach ( $category_ids as $index => $term_id ) {
				foreach ( $wfsMappedCat['profile'] as $key => $value ) {
					if ( in_array( $term_id, $value['woo_cat'] ) ) {
						
						$wfsCategory = $key;

					}
				}
			}
		}

		if ( isset( $convert_wfsMappedCat['profile'] ) ) {
			foreach ( $category_ids as $index => $term_id ) {
				foreach ( $convert_wfsMappedCat['profile'] as $key => $value ) {
					if ( in_array( $term_id, $value['woo_cat'] ) ) {
						$convertWfsCategory = $key;

					}
				}
			}
		}
	
		$htmlForTemplate = '';
		if ( $category ) {
			$edit_profile_url = admin_url( 'admin.php?page=sales_channel&channel=walmart&profile_id=' . ( urlencode( $category ) ) . '&section=templates&details=edit&profile_type=MP_ITEM&store_id=' . $store_id );
			$htmlForTemplate .='MP ITEM - <a href="' . esc_url( $edit_profile_url ) . '">' . esc_attr( $category ) . '</a>';
		} else {
			$cat_mapping_section = admin_url( 'admin.php?page=sales_channel&channel=walmart&section=category&store_id=' . $store_id );
			$htmlForTemplate    .= "<span class=''><span>";
		}

		if ( $wfsCategory ) {
			$edit_profile_url = admin_url( 'admin.php?page=sales_channel&channel=walmart&profile_id=' . ( urlencode( $wfsCategory ) ) . '&section=templates&details=edit&profile_type=MP_WFS_ITEM&store_id=' . $store_id );
			$htmlForTemplate .='<br>WFS - <a href="' . esc_url( $edit_profile_url ) . '">' . esc_attr( $wfsCategory ) . '</a>';
		} else {
			$cat_mapping_section = admin_url( 'admin.php?page=sales_channel&channel=walmart&section=category&store_id=' . $store_id );
			$htmlForTemplate    .= "<span class=''><span>";
		}
		if ( $convertWfsCategory ) {
			$edit_profile_url = admin_url( 'admin.php?page=sales_channel&channel=walmart&profile_id=' . ( urlencode( $convertWfsCategory ) ) . '&section=templates&details=edit&profile_type=OMNI_WFS&store_id=' . $store_id );
			$htmlForTemplate .='<br>Convert WFS - <a href="' . esc_url( $edit_profile_url ) . '">' . esc_attr( $convertWfsCategory ) . '</a>';
		} else {
			$cat_mapping_section = admin_url( 'admin.php?page=sales_channel&channel=walmart&section=category&store_id=' . $store_id );
			$htmlForTemplate    .= "<span class=''><span>";
		}

		echo $htmlForTemplate;
	}


	public function column_category( $item ) {
		foreach ( $item['category_ids'] as $key => $value ) {
			$wooCategory = get_term_by( 'id', $value, 'product_cat', 'ARRAY_A' );
			echo esc_attr( $wooCategory['name'] ) . '</br>';
		}
	}
	public function column_stock( $item ) {

		if ( 'instock' == $item['stock_status'] ) {
			if ( 0 == $item['stock_quantity'] || '0' == $item['stock_quantity'] ) {
				return '<div class="ced-connected-button-wrap"><span class="ced-circle-instock"></span><span class="stock_alert_instock">' . esc_attr( 'In Stock', 'walmart-integration-for-woocommerce' ) . '</span></div>';
			} else {
				return '<div class="ced-connected-spanutton-wrap"><span class="ced-circle-instock"></span><span class="stock_alert_instock">In Stock(' . $item['stock_quantity'] . ')</span></div>';
			}
		} else {
			return '<div class="ced-connected-spanutton-wrap"><span class="ced-circle-outofstock" style="spanackground:#e2401c;"></span><span class="stock_alert_outofstock">' . esc_attr( 'Out of Stock', 'walmart-integration-for-woocommerce' ) . '</span></div>';
		}
	}

	public function column_image( $item ) {
		$image = wp_get_attachment_url( $item['image_id'] );
		return '<img height="50" width="50" src="' . $image . '">';
	}
	public function column_status( $item ) {
		$store_id = isset( $_GET['store_id'] ) ? sanitize_text_field( wp_unslash( $_GET['store_id'] ) ) : '';

		$lifecycleStatus = get_post_meta( $item['id'], 'ced_walmart_product_lifecycle' . $store_id . wifw_environment(), true );
		if ( 'RETIRED' == $lifecycleStatus ) {
			echo '<b class="walmart-error">' . esc_html( __( 'RETIRED', 'walmart-woocommerce-integration' ) ) . '</b>';
		} else {
			$status = get_post_meta( $item['id'], 'ced_walmart_product_uploaded' . $store_id . wifw_environment(), true );
			if ( isset( $status ) && ! empty( $status ) ) {
				$product_state = get_post_meta( $item['id'], 'ced_walmart_product_status' . $store_id . wifw_environment(), true );

				if ( 'PUBLISHED' == $product_state ) {

					echo '<div class="ced_walmart_product_status_wrap"><p><span class="success_upload_on_walmart" id="' . esc_attr( $item['id'] ) . '"><span class="ced-circle-instock"></span><span class="ced_walmart_product_status">Present on Walmart</span></p></div>';

				} else {

					echo '<div class="ced_walmart_product_status_wrap"><p><span class="success_upload_on_walmart" id="' . esc_attr( $item['id'] ) . '"><span class="ced-circle-instock"></span><span class="ced_walmart_product_status">' . esc_attr( $product_state ) . '</span></p></div>';
				}
			} else {
				echo '<div class="ced_walmart_product_status_wrap"><p><span class="ced-circle-notuploaded"></span><span class="not_completed ced_walmart_product_status" id="' . esc_attr( $item['id'] ) . '">Not on Walmart</span></p></div>';
			}
		}
	}


	public function get_columns() {
		$columns = array(
			'cb'       => '<input type="checkbox" />',
			'image'    => __( '<b>Image</b>', 'walmart-woocommerce-integration' ),
			'name'     => __( '<b>Name</b>', 'walmart-woocommerce-integration' ),
			'price'    => __( '<b>Price</b>', 'walmart-woocommerce-integration' ),
			'profile'  => __( '<b>Template</b>', 'walmart-woocommerce-integration' ),
			'sku'      => __( '<b>SKU</b>', 'walmart-woocommerce-integration' ),
			'stock'    => __( '<b>Stock</b>', 'walmart-woocommerce-integration' ),
			'category' => __( '<b>Category</b>', 'walmart-woocommerce-integration' ),
			'status'   => __( '<b>Walmart Status</b>', 'walmart-woocommerce-integration' ),
		);
		return $columns;
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

			echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . esc_html( __( 'Select bulk action' ) ) . '</label>';
			echo '<select name="action' . esc_attr( $two ) . '" class="bulk-action-selector " id="ced-walmart-bulk-operation">';
			echo '<option value="-1">' . esc_html( __( 'Bulk Actions' ) ) . "</option>\n";

			foreach ( $this->_actions as $name => $title ) {
				$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

				echo "\t" . '<option value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . "</option>\n";
			}

			echo "</select>\n";

			submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => 'ced_walmart_bulk_operation' ) );
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
			'upload'                   => __( 'Upload / Update', 'walmart-woocommerce-integration' ),
			// 'upload_wfs_item'          => __( 'Upload As WFS', 'walmart-woocommerce-integration' ),
			'update_price'             => __( 'Update Price', 'walmart-woocommerce-integration' ),
			'update_stock'             => __( 'Update Stock', 'walmart-woocommerce-integration' ),
			// 'convert_to_wfs'           => __( 'Convert To WFS', 'walmart-woocommerce-integration' ),
			'update_shipping_template' => __( 'Update Shipping Template', 'walmart-woocommerce-integration' ),
			'remove_shipping_template' => __( 'Remove Shipping Template', 'walmart-woocommerce-integration' ),
			'retire_bulk_item'         => __( 'Retire Items', 'walmart-woocommerce-integration' ),
		);
		return $actions;
	}


	public function renderHTML() {
		?>
			<div id="post-body" class="metabox-holder columns-2">

				<div class="wrap">
				<h1 class="wp-heading-inline">Products</h1>	
			
			</div>

				<div id="post-body-content">
					<div class="meta-box-sortables ui-sortable">
					<?php
					$store_id       = isset( $_GET['store_id'] ) ? sanitize_text_field( wp_unslash( $_GET['store_id'] ) ) : '';
					$status_actions = array(
						'Uploaded'    => __( 'On walmart', 'walmart-woocommerce-integration' ),
						'NotUploaded' => __( 'Not on walmart', 'walmart-woocommerce-integration' ),
					);
					$stock_status   = array(
						'instock'    => __( 'In stock', 'walmart-woocommerce-integration' ),
						'outofstock' => __( 'Out of stock', 'walmart-woocommerce-integration' ),
					);
					$product_types  = get_terms( 'product_type' );
					$temp_array     = array();
					foreach ( $product_types as $key => $value ) {
						if ( 'simple' == $value->name || 'variable' == $value->name ) {
							$temp_array_type[ $value->term_id ] = ucfirst( $value->name );
						}
					}
					$product_types      = $temp_array_type;
					$product_categories = get_terms( 'product_cat' );
					$temp_array         = array();
					foreach ( $product_categories as $key => $value ) {
						$temp_array[ $value->term_id ] = $value->name;
					}
					$product_categories             = $temp_array;
					$previous_selected_status       = isset( $_GET['status_sorting'] ) ? sanitize_text_field( wp_unslash( $_GET['status_sorting'] ) ) : '';
					$previous_selected_cat          = isset( $_GET['pro_cat_sorting'] ) ? sanitize_text_field( wp_unslash( $_GET['pro_cat_sorting'] ) ) : '';
					$previous_selected_type         = isset( $_GET['pro_type_sorting'] ) ? sanitize_text_field( wp_unslash( $_GET['pro_type_sorting'] ) ) : '';
					$previous_selected_stock_status = isset( $_GET['stock_status'] ) ? sanitize_text_field( wp_unslash( $_GET['stock_status'] ) ) : '';
					echo '<div class="ced_walmart_wrap">';

					echo '<form method="post" action="">';
					wp_nonce_field( 'manage_products', 'manage_product_filters' );

					echo '<div class="ced_walmart_top_wrapper">';
					// echo "<span class='ced_walmart_filter_label'>Filter product by</span>";
					echo '<select name="status_sorting" class="select_boxes_product_page">';
					echo '<option value="">' . esc_html( __( 'Filter by walmart Product Status', 'walmart-woocommerce-integration' ) ) . '</option>';
					foreach ( $status_actions as $name => $title ) {
						$selectedStatus = ( $previous_selected_status == $name ) ? 'selected="selected"' : '';
						$class          = 'edit' === $name ? ' class="hide-if-no-js"' : '';
						echo '<option ' . esc_attr( $selectedStatus ) . ' value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . '</option>';
					}
					echo '</select>';
					wp_dropdown_categories(
						array(
							'name'            => 'pro_cat_sorting',
							'taxonomy'        => 'product_cat',
							'class'           => 'select_boxes_product_page',
							'orderby'         => 'NAME',
							'order'           => 'ASC',
							'hierarchical'    => 1,
							'hide_empty'      => 1,
							'show_count'      => true,
							'selected'        => $previous_selected_cat,
							'show_option_all' => __(
								'Filter by Product Category',
								'walmart-woocommerce-integration'
							),
						)
					);
						echo '<select name="pro_type_sorting" class="select_boxes_product_page">';
						echo '<option value="">' . esc_html( __( 'Filter by Product Type', 'walmart-woocommerce-integration' ) ) . '</option>';
					foreach ( $product_types as $name => $title ) {
						$selectedType = ( $previous_selected_type == $name ) ? 'selected="selected"' : '';
						$class        = 'edit' === $name ? ' class="hide-if-no-js"' : '';
						echo '<option ' . esc_attr( $selectedType ) . ' value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . '</option>';
					}
						echo '</select>';
						echo '<select name="stock_status" class="select_boxes_product_page">';
						echo '<option value="">' . esc_html( __( 'Filter by Stock Status', 'walmart-woocommerce-integration' ) ) . '</option>';
					foreach ( $stock_status as $name => $title ) {
						$selectedType = ( $previous_selected_stock_status == $name ) ? 'selected="selected"' : '';
						$class        = 'edit' === $name ? ' class="hide-if-no-js"' : '';
						echo '<option ' . esc_attr( $selectedType ) . ' value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . '</option>';
					}
						echo '</select>';
						$this->search_box( 'Search', 'search_id', 'search_product' );
						submit_button( __( 'Filter', 'ced-walmart' ), 'action', 'filter_button', false, array() );
					if ( $this->show_reset ) {
						echo '<span class="ced_reset"><a href="' . esc_url( admin_url( 'admin.php?page=sales_channel&channel=walmart&section=products&store_id=' . $store_id ) ) . '" class="button">X</a></span>';
					}
						echo '</div>';
						echo '</form>';
						echo '</div>';
						$bulk_actions = array(
							'upload_product'   => array(
								'label' => 'Upload Products',
								'class' => 'success',
							),
							'update_product'   => array(
								'label' => 'Update Products',
								'class' => 'primary',
							),
							'update_inventory' => array(
								'label' => 'Update Inventory',
								'class' => 'cool',
							),
							'update_image'     => array(
								'label' => 'Update Images',
								'class' => 'warm',
							),
							'remove_product'   => array(
								'label' => 'Remove Product',
								'class' => 'fail',
							),
							'unlink_product'   => array(
								'label' => 'Unlink Product',
								'class' => 'warm',
							),

						);

						?>
						<form method="post">
						<?php
						$this->display();
						?>
						</form>

					</div>
				</div>
				<div class="clear"></div>
			</div>
			<div class="ced_walmart_preview_product_popup_main_wrapper"></div>
			<?php
	}
}

	$ced_walmart_products_obj = new Ced_Walmart_Products_List();
	$ced_walmart_products_obj->prepare_items();
