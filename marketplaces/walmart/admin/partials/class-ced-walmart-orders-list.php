<?php
/**
 * Display list of orders
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
 * Ced_Walmart_Orders_List
 *
 * @since 1.0.0
 */
class Ced_Walmart_Orders_List extends WP_List_Table {

	/**
	 * Ced_Walmart_Orders_List construct
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Walmart Order', 'walmart-woocommerce-integration' ),
				'plural'   => __( 'Walmart Orders', 'walmart-woocommerce-integration' ),
				'ajax'     => true,
			)
		);
	}

	/**
	 * Function for preparing data to be displayed
	 *
	 * @since 1.0.0
	 */
	public function prepare_items() {
		/** Get per page number for listing orders
		 *
		 * @since 1.0.0
		 */
		$per_page = apply_filters( 'ced_walmart_orders_list_per_page', 20 );
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}

		$this->items = self::ced_walmart_orders( $per_page, $current_page );
		$count       = self::get_count();

		$this->set_pagination_args(
			array(
				'total_items' => $count,
				'per_page'    => $per_page,
				'total_pages' => ceil( $count / $per_page ),
			)
		);

		if ( ! $this->current_action() ) {
			$this->render_html();
		} else {
			$this->process_bulk_action();
		}
	}

	/**
	 * Function to count number of responses in result
	 *
	 * @since 1.0.0
	 */
	public function get_count() {
		global $wpdb;
		$orders_post_ids = $wpdb->get_results( $wpdb->prepare( "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key`=%s AND `meta_value`=%d  order by `post_id` ", '_ced_walmart_order' . wifw_environment(), 1 ), 'ARRAY_A' );
		return count( $orders_post_ids );
	}

	/**
	 * Text displayed when no  data is available
	 *
	 * @since 1.0.0
	 */
	public function no_items() {
		esc_html_e( 'No Orders To Display.', 'walmart-woocommerce-integration' );
	}

	/**
	 * Function for id column
	 *
	 * @since 1.0.0
	 * @param array $post_data Order Id.
	 */
	public function column_id( $post_data ) {
		$order_id = isset( $post_data['post_id'] ) ? esc_attr( $post_data['post_id'] ) : '';
		$url      = get_edit_post_link( $order_id, '' );
		echo '<a href="' . esc_url( $url ) . '" target="_blank">#' . esc_attr( $order_id ) . '</a>';
	}

	/**
	 * Function for name column
	 *
	 * @since 1.0.0
	 * @param array $post_data Order Id.
	 */
	public function column_items( $post_data ) {
		$order_id    = isset( $post_data['post_id'] ) ? esc_attr( $post_data['post_id'] ) : '';
		$_order      = wc_get_order( $order_id );
		$order_items = $_order->get_items();
		if ( is_array( $order_items ) && ! empty( $order_items ) ) {
			foreach ( $order_items as $index => $_item ) {
				$line_items = $_item->get_data();
				$quantity   = isset( $line_items['quantity'] ) ? $line_items['quantity'] : 0;
				$item_name  = isset( $line_items['name'] ) ? $line_items['name'] : '';
				$product_id = isset( $line_items['id'] ) ? $line_items['id'] : '';
				$url        = get_edit_post_link( $product_id, '' );

				echo '<b><a class="ced_walmart_prod_name" href="' . esc_attr( $url ) . '" target="#">' . esc_attr( $item_name ) . '</a></b></br>';

			}
		}
	}

	/**
	 * Function for order Id column
	 *
	 * @since 1.0.0
	 * @param array $post_data Order Id.
	 */
	public function column_walmart_order_id( $post_data ) {
		$order_id         = isset( $post_data['post_id'] ) ? esc_attr( $post_data['post_id'] ) : '';
		$walmart_order_id = get_post_meta( $order_id, '_ced_walmart_order_id', true );
		echo '<span>#' . esc_attr( $walmart_order_id ) . '</span>';
	}



	/**
	 *
	 * Function for order status column
	 */
	public function column_order_status( $post_data ) {
		$order_id = isset( $post_data['post_id'] ) ? esc_attr( $post_data['post_id'] ) : '';
		$details  = wc_get_order( $order_id );
		$status   = $details->get_status();
		$html     = '<div class="ced-' . $status . '-button-wrap"><a class="ced-' . $status . '-link"><span class="ced-circle" style=""></span> ' . esc_attr( ucfirst( $status ) ) . '</a> </div>';
		print_r( $html );
	}


	/**
	 * Function for order status column
	 *
	 * @since 1.0.0
	 * @param array $post_data Order Id.
	 */
	public function column_walmart_order_status( $post_data ) {
		$order_id             = isset( $post_data['post_id'] ) ? esc_attr( $post_data['post_id'] ) : '';
		$walmart_order_status = get_post_meta( $order_id, '_ced_walmart_order_status', true );

		$status = 'processing';
		if ( 'Created' === $walmart_order_status || 'Acknowledged' === $walmart_order_status ) {
			$status = 'processing';
		} elseif ( 'Shipped' === $walmart_order_status || 'Delivered' === $walmart_order_status ) {
			$status = 'completed';
		} elseif ( 'Cancelled' === $walmart_order_status ) {
			$status = 'cancelled';
		}

		echo '<div class="ced-' . esc_attr( $status ) . '-button-wrap"><a class="ced-' . esc_attr( $status ) . '-link"><span class="ced-circle" style=""></span> ' . esc_attr( ucfirst( $walmart_order_status ) ) . '</a> </div>';
	}



	/**
	 * Function for customer name column
	 *
	 * @since 1.0.0
	 * @param array $post_data Order Id.
	 */
	public function column_customer_name( $post_data ) {
		$order_id = isset( $post_data['post_id'] ) ? esc_attr( $post_data['post_id'] ) : '';
		$details  = wc_get_order( $order_id );
		$details  = $details->get_data();
		echo '<b>' . esc_attr( $details['billing']['first_name'] ) . '</b>';
	}


	/**
	 * Function for order total column
	 *
	 * @since 1.0.0
	 * @param array $post_data Order Id.
	 */

	public function column_total( $post_data ) {
		$order_id = isset( $post_data['post_id'] ) ? esc_attr( $post_data['post_id'] ) : '';
		$details  = wc_get_order( $order_id );
		$details  = $details->get_total();
		print_r( wc_price( $details ) );
	}


	/**
	 * Function for order created column
	 *
	 * @since 1.0.0
	 * @param array $post_data Order Id.
	 */

	public function column_action( $post_data ) {
		$store_id             = isset( $_GET['store_id'] ) ? sanitize_text_field( $_GET['store_id'] ) : '';
		$order_id             = isset( $post_data['post_id'] ) ? esc_attr( $post_data['post_id'] ) : '';
		$walmart_order_status = get_post_meta( $order_id, '_ced_walmart_order_status', true );
		$order_edit_link      = admin_url( 'admin.php?page=sales_channel&channel=walmart&section=orders&store_id=' . $store_id . '&panel=edit&id=' . $order_id );

		if ( 'Created' === $walmart_order_status || 'Acknowledged' === $walmart_order_status ) {
			echo '<a href="' . esc_url( $order_edit_link ) . '" >' . esc_html( __( 'Ship Order', 'walmart-woocommerce-integration' ) ) . '</a>';
		} elseif ( 'Shipped' === $walmart_order_status || 'Delivered' === $walmart_order_status ) {
			echo '<a href="' . esc_url( $order_edit_link ) . '" >' . esc_html( __( 'View Order Details', 'walmart-woocommerce-integration' ) ) . '</a>';
		} elseif ( 'Cancelled' === $walmart_order_status ) {
			echo '<a href="' . esc_url( $order_edit_link ) . '" >' . esc_html( __( 'View Order Details', 'walmart-woocommerce-integration' ) ) . '</a>';
		}
	}

	/**
	 * Associative array of columns
	 *
	 * @since 1.0.0
	 */
	public function get_columns() {
		$columns = array(
			'id'                   => __( 'Store Order ID', 'walmart-woocommerce-integration' ),
			'order_status'         => __( 'Store Status', 'walmart-woocommerce-integration' ),
			'walmart_order_id'     => __( 'Walmart Order ID', 'walmart-woocommerce-integration' ),
			'walmart_order_status' => __( 'Walmart Status', 'walmart-woocommerce-integration' ),
			'items'                => __( 'Ordered Items', 'walmart-woocommerce-integration' ),
			'total'                => __( 'Order Total', 'walmart-woocommerce-integration' ),
			'customer_name'        => __( 'Customer Name', 'walmart-woocommerce-integration' ),
			'action'               => __( 'Action', 'walmart-woocommerce-integration' ),
		);

		/** Get columns for orders
		 *
		 * @since 1.0.0
		 */
		$columns = apply_filters( 'ced_walmart_orders_columns', $columns );
		return $columns;
	}

	/**
	 * Columns to make sortable.
	 *
	 * @since 1.0.0
	 */
	public function get_sortable_columns() {
		$sortable_columns = array();
		return $sortable_columns;
	}

	/**
	 * Render html content
	 *
	 * @since 1.0.0
	 */
	public function render_html() {
		$store_id = isset( $_GET['store_id'] ) ? sanitize_text_field( wp_unslash( $_GET['store_id'] ) ) : '';

		?>
		<div class="ced_walmart_wrap ced_walmart_wrap_extn">
			<div id="post-body" class="metabox-holder columns-2">

				<div class="wrap">
				<h1 class="wp-heading-inline">Orders</h1>	
			
			</div>
			<button  class="button button-primary alignright" id="ced_walmart_fetch_orders" data-id="<?php esc_attr( $store_id ); ?> " ><?php esc_html_e( 'Fetch Orders', 'walmart-woocommerce-integration' ); ?></button>	

			<div id="post-body" class="metabox-holder columns-2">
				<div id="">
					<div class="meta-box-sortables ui-sortable">
						<form method="post">
							<?php
							wp_nonce_field( 'walmart_profiles', 'walmart_profiles_actions' );
							$this->display();
							?>
						</form>
					</div>
				</div>
				<div class="clear"></div>
			</div>

		</div>
		<?php
	}

	/**
	 * Function for getting current status
	 *
	 * @since 1.0.0
	 */
	public function current_action() {
		if ( isset( $_GET['panel'] ) ) {
			$action = isset( $_GET['panel'] ) ? sanitize_text_field( wp_unslash( $_GET['panel'] ) ) : '';
			return $action;
		}
	}


		/**
		 * Function for processing bulk actions
		 *
		 * @since 1.0.0
		 */
	public function process_bulk_action() {
		if ( isset( $_GET['panel'] ) && 'edit' == $_GET['panel'] ) {
			$file = CED_WALMART_DIRPATH . 'admin/pages/ced-walmart-order-edit.php';
			include_file( $file );
		}
	}

	/**
	 * Function to get all the orders
	 *
	 * @since 1.0.0
	 * @param      int $per_page    Results per page.
	 * @param      int $page_number   Page number.
	 */
	public function ced_walmart_orders( $per_page, $page_number = 1 ) {

		$store_id = isset( $_GET['store_id'] ) ? sanitize_text_field( wp_unslash( $_GET['store_id'] ) ) : '';
		global $wpdb;
		$offset          = ( $page_number - 1 ) * $per_page;
		$orders_post_ids = $wpdb->get_results( $wpdb->prepare( "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key`=%s AND `meta_value`=%d  order by `post_id` DESC LIMIT %d OFFSET %d", '_ced_walmart_order_store_id' . wifw_environment(), $store_id, $per_page, $offset ), 'ARRAY_A' );

		return( $orders_post_ids ) ? $orders_post_ids : array();
	}
}

$ced_walmart_orders_obj = new Ced_Walmart_Orders_List();
$ced_walmart_orders_obj->prepare_items();
