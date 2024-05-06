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

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil as CedAmazonHOPS;


class Ced_Amazon_List_Orders extends WP_List_Table {


	public $create_amz_order_hops = false;
	public $item_data;

	/**
	 * Class constructor
	 */
	public function __construct() {

		if ( CedAmazonHOPS::custom_orders_table_usage_is_enabled() ) {
			$this->create_amz_order_hops = true;
		}

		parent::__construct(
			array(
				'singular' => __( 'Amazon order', 'amazon-for-woocommerce' ), // singular name of the listed records
				'plural'   => __( 'Amazon orders', 'amazon-for-woocommerce' ), // plural name of the listed records
				'ajax'     => true, // does this table support ajax?
			)
		);
	}
	/**
	 *
	 * Function for preparing data to be displayed
	 */
	public function prepare_items() {

		/**
		 *  Function to list order based on per page
		 *
		 * @param 'function'
		 * @param  integer 'limit'
		 * @return 'count'
		 * @since  1.0.0
		 */
		$per_page = apply_filters( 'ced_amazon_orders_list_per_page', 10 );
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		// Column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}

		$this->items = self::ced_amazon_orders( $per_page, $current_page );
		$count       = self::get_count();
		// Set the pagination
		$this->set_pagination_args(
			array(
				'total_items' => $count,
				'per_page'    => $per_page,
				'total_pages' => ceil( $count / $per_page ),
			)
		);

		if ( ! $this->current_action() ) {
			$this->items = self::ced_amazon_orders( $per_page, $current_page );
			$this->renderHTML();
		}
	}

	/**
	 *
	 * Function to count number of responses in result
	 */
	public function get_count() {
		global $wpdb;

		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';

		$mplocation_arr = explode( '|', $seller_id );
		$mplocation     = isset( $mplocation_arr[1] ) ? $mplocation_arr[0] : '';

		if ( ! empty( $mplocation ) ) {
			
			if ( $this->create_amz_order_hops ) {

				$orders_post_id = wc_get_orders(
					array(
						'limit' => -1,
						'orderby' => 'date',
						'order' => 'DESC',
						'return' => 'ids',
						'meta_query' => array(
							array(
								'key'        => 'ced_amazon_order_countory_code',
								'value'      => $mplocation,
								'comparison' => 'LIKE',
							),
						),
					)
					
				);

			} else {
				
				$orders_post_id = $wpdb->get_results( $wpdb->prepare( "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key`=%s AND `meta_value`=%s group by `post_id` ", 'ced_amazon_order_countory_code', $mplocation ), 'ARRAY_A' );
	
			}

		} elseif ( $this->create_amz_order_hops ) {

			
				$orders_post_id = wc_get_orders(
					array(
						'limit' => -1,
						'orderby' => 'date',
						'order' => 'DESC',
						'return' => 'ids',
						'meta_query' => array(
							array(
								'key'        => 'ced_amazon_order_countory_code',
								'comparison' => 'EXISTS',
								
							),
						),
					)
					
				);

		} else {
			$orders_post_id = $wpdb->get_results( $wpdb->prepare( "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key`=%s  group by `post_id` ", 'ced_amazon_order_countory_code' ), 'ARRAY_A' );

		}


		return count( $orders_post_id );
	}

	/*
	 *
	 * Text displayed when no  data is available
	 *
	 */
	public function no_items() {
		esc_html_e( 'No Orders To Display.', 'amazon-for-woocommerce' );
	}
	/**
	 *
	 * Function for id column
	 */
	public function column_id( $items ) {

		foreach ( $items as $key => $value ) {

			if ( $this->create_amz_order_hops ) {

				$displayOrders = $value->get_data();
				echo '<a href ="' . esc_attr( get_admin_url() . 'admin.php?page=wc-orders&action=edit&id=' . $displayOrders['order_id'] ) . '" target="_blank">#' . esc_html__( $displayOrders['order_id'] ) . '</a>';
				break;

			} else {

				$displayOrders = $value->get_data();
				echo '<a href ="' . esc_attr( get_edit_post_link( $displayOrders['order_id'] ) ) . '" target="_blank">#' . esc_html__( $displayOrders['order_id'] ) . '</a>';
				break;

			}

		}

		echo '<a></a>';
	}



	/**
	 *
	 * Function for order Id column
	 */
	public function column_amazon_order_id( $items ) {
		
		foreach ( $items as $key => $value ) {

			$details         = $this->item_data;
			$order_meta_data = $details['meta_data'];

			if ( $this->create_amz_order_hops ) {

				foreach ( $order_meta_data as $key1 => $value1 ) {
					$order_id = $value1->get_data();
					if ( 'amazon_order_id' == $order_id['key'] ) {
						echo '<a href ="' . esc_attr( get_admin_url() . 'admin.php?page=wc-orders&action=edit&id=' . $details['id'] ) . '" target="_blank">#' . esc_attr( $order_id['value'] ) . '</a>';
			   
					}
				}

				break;

			} else {

				foreach ( $order_meta_data as $key1 => $value1 ) {
					$order_id = $value1->get_data();
					if ( 'amazon_order_id' == $order_id['key'] ) {
						echo '<a href ="' . esc_attr( get_edit_post_link( $details['id'] ) ) . '">#' . esc_attr( $order_id['value'] ) . '</a>';

					}
				}
				break;

			}

		}
	}

	/**
	 *
	 * Function for order status column
	 */
	public function column_order_status( $items ) {
		foreach ( $items as $key => $value ) {

			$details         = $this->item_data;
			$order_meta_data = $details['meta_data'];

			foreach ( $order_meta_data as $key1 => $value1 ) {
				$order_status = $value1->get_data();
				if ( '_amazon_umb_order_status' == $order_status['key'] ) {

					$status = $this->ced_amazon_return_amazon_status_classes( $order_status['value'] );
					$html   = '<div class="ced-' . esc_attr( $status ) . '-button-wrap"><a class="ced-' . esc_attr( $status ) . '-link"><span class="ced-circle" style=""></span> ' . esc_attr( ucfirst( $order_status['value'] ) ) . '</a> </div>';

					print_r( $html );

				}
			}
			break;
		}
	}


	public function ced_amazon_return_amazon_status_classes( $orderStatus = 'processing' ) {

		$status = 'processing';

		if ( 'created' == strtolower( $orderStatus ) || 'unshipped' == strtolower( $orderStatus ) ) {
			$status = 'processing';
		} elseif ( 'pending' == strtolower( $orderStatus ) || 'pendingavailability' == strtolower( $orderStatus ) ) {
			$status = 'pending';
		} elseif ( 'partiallyshipped' == strtolower( $orderStatus ) || 'shipped' == strtolower( $orderStatus ) ) {
			$status = 'completed';
		} elseif ( 'canceled' == strtolower( $orderStatus ) || 'unfulfillable' == strtolower( $orderStatus ) ) {
			$status = 'cancelled';
		}

		return $status;
	}



	public function column_woocommerce_status( $items ) {


		foreach ( $items as $key => $value ) {


			$value   = json_decode( $value, true);
			$orderID = $value['order_id'];
			$details = wc_get_order( $orderID );
			$data    = $details->get_data();

			$this->item_data = $data;

			$status = isset( $data['status'] ) ? $data['status'] : 'processing';
			$html   = '<div class="ced-' . esc_attr( $status ) . '-button-wrap"><a class="ced-' . esc_attr( $status ) . '-link"><span class="ced-circle" style=""></span> ' . esc_attr( ucfirst( $status ) ) . '</a> </div>';

			print_r( $html );
			break;
		}
	}


	public function column_ordered_items( $items ) {

		$count = 0;
		foreach ( $items as $key => $value ) {

			$displayOrders = $value->get_data();
			$orderID       = $displayOrders['order_id'];
			$_order        = wc_get_order( $orderID );
			$order_items   = $_order->get_items();
			if ( is_array( $order_items ) && ! empty( $order_items ) ) {

				foreach ( $order_items as $index => $_item ) {
					$line_items = $_item->get_data();
					$quantity   = isset( $line_items['quantity'] ) ? $line_items['quantity'] : 0;
					$count     += $quantity;

				}
			}

			break;
		}

		if ( 1 < $count ) {
			$text = 'Items';
		} else {
			$text = 'Item';
		}
		echo '<p> <a herf="#">  ' . esc_html__( $count, 'amazon-for-woocommerce' ) . ' ' . esc_html( $text, 'amazon-for-woocommerce' ) . '</a></p>';
	}


	public function column_order_total( $items ) {

		$location_for_seller = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';

		foreach ( $items as $key => $value ) {
			$displayOrders = $value->get_data();
			$orderID       = $displayOrders['order_id'];
			$_order        = wc_get_order( $orderID );
			$total         = $_order->get_total();


			$ccCode = $_order->get_currency();
			$symbol = get_woocommerce_currency_symbol($ccCode);

			echo '<b> ' . esc_attr( $symbol ) . esc_attr(  number_format( $total, 2, '.', '' ) ) . '</b>';
			break;
			
		}
	}


	/**
	 *
	 * Function display amazon fulfillment channel column
	 */
	public function column_fulfillment_channel( $items ) {
		foreach ( $items as $key => $value ) {

			$displayOrders      = $value->get_data();
			$orderID            = $displayOrders['order_id'];
			$fulfillmentChannel = '-';

			if ( $this->create_amz_order_hops ) {

				$order              = wc_get_order( $orderID );
				$fulfillmentChannel = $order->get_meta( 'ced_umb_amazon_fulfillment_channel' ) ;


			} else {
				$fulfillmentChannel = get_post_meta( $orderID, 'ced_umb_amazon_fulfillment_channel', true );
			}


			echo '<b>' . esc_html__( $fulfillmentChannel ) . '</b>';
			break;
		}
	}


	public function column_created_on( $items ) {
		foreach ( $items as $key => $value ) {
			$displayOrders = $value->get_data();
			$orderID       = $displayOrders['order_id'];
			$_order        = wc_get_order( $orderID );
			$date          = $_order->get_date_created();

			$dateTime = new DateTime( $date );

			$normalDateTime = $dateTime->format( 'Y-m-d H:i:s' );

			echo '<b>' . esc_html__( $normalDateTime ) . '</b>';
			break;
		}
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */

	public function get_columns() {

		$columns = array(
			'id'                  => __( 'WooCommerce order ID', 'amazon-for-woocommerce' ),
			'woocommerce_status'  => __( 'WooCommerce status', 'amazon-for-woocommerce' ),
			'amazon_order_id'     => __( 'Amazon order ID', 'amazon-for-woocommerce' ),
			'order_status'        => __( 'Amazon status', 'amazon-for-woocommerce' ),
			'ordered_items'       => __( 'Ordered items', 'amazon-for-woocommerce' ),
			'order_total'         => __( 'Total', 'amazon-for-woocommerce' ),
			'fulfillment_channel' => __( 'Fulfillment channel', 'amazon-for-woocommerce' ),
			'created_on'          => __( 'Created on', 'amazon-for-woocommerce' ),

		);

		/**
		 *  Function to list order based on per page
		 *
		 * @param 'function'
		 * @param  integer 'limit'
		 * @return 'count'
		 * @since  1.0.0
		 */
		$columns = apply_filters( 'ced_amazon_orders_columns', $columns );
		return $columns;
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

	/**
	 *
	 * Function to renderHTML
	 */
	public function renderHTML() {
		$user_id         = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$order_error_log = get_option( 'ced_amazon_order_fetch_log_' . $user_id );
		?>
		<div class="ced_amazon_fetch_orders_button_wrap ced-button-wrapper-top">
			<button id="ced_amazon_fetch_orders"  data-id="<?php echo esc_attr( isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '' ); ?>" style="margin-left:5px;"  title="Fetch Orders" type="button" class="components-button is-primary">
				<?php echo esc_html__( 'Fetch orders', 'amazon-for-woocommerce' ); ?>
			</button>

		</div>
		
		<!-- Render amazon order detail via ajax in modal -->
		
		<?php
		if ( ! empty( $order_error_log ) ) {
			?>
			<section class="woocommerce-inbox-message plain">
				<div class="woocommerce-inbox-message__wrapper">
					<div class="woocommerce-inbox-message__content">
						<span class="woocommerce-inbox-message__date"><?php echo esc_html( ced_amazon_time_elapsed_string( $order_error_log['timestamp'] ) ); ?></span>
						<h3 class="woocommerce-inbox-message__title"><?php echo esc_html__( 'Whoops! It looks like there were some errors in fetching your Amazon Orders.', 'amazon-for-woocommerce' ); ?></h3>
						<div class="woocommerce-inbox-message__text">
							<?php
							foreach ( $order_error_log as $key => $fetch_error ) {
								if ( is_numeric( $key ) ) {
									?>
									<b><span><?php echo esc_html( $fetch_error ); ?></span></b><br>
									<?php
								}
							}
							?>
						</div>
					</div>
				</div>
			</section>
			<?php
		}
		?>
		
	<div id="post-body" class="metabox-holder ced-marketplace-order-wrapper columns-2">
		<div id="">
			<div class="meta-box-sortables ui-sortable">

				<?php $this->display(); ?>

			</div>
		</div>
		<div class="clear"></div>
	</div>

		<?php
	}
	/*
	 *
	 *  Function to get all the orders
	 *
	 */
	public function ced_amazon_orders( $per_page = 10, $page_number = 1 ) {

		$filterFile = CED_AMAZON_DIRPATH . 'admin/partials/order-filters.php';
		if ( file_exists( $filterFile ) ) {
			require_once $filterFile;
		}

		global $wpdb;
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		$offset    = ( $page_number - 1 ) * $per_page;

		$mplocation_arr = explode( '|', $seller_id );
		$mplocation     = isset( $mplocation_arr[1] ) ? $mplocation_arr[0] : '';

		if ( ! empty( $mplocation ) ) {
			
			if ( $this->create_amz_order_hops ) {
				$orders_post_id = wc_get_orders(
					array(
						'limit' => $per_page,
						'paged' => $page_number,
						'orderby' => 'date',
						'order' => 'DESC',
						'return' => 'ids',
						'meta_query' => array(
							array(
								'key'        => 'ced_amazon_order_countory_code',
								'value'      => $mplocation,
								'comparison' => 'LIKE',
							),
						),
					)
					
				);


			} else {

				$orders_post_id = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE `meta_key`=%s AND `meta_value`=%s GROUP BY `post_id` ORDER BY `post_id` DESC LIMIT %d OFFSET %d", 'ced_amazon_order_countory_code', $mplocation, $per_page, $offset ), 'ARRAY_A' );
				
			}

		} elseif ( $this->create_amz_order_hops ) {
			
			
				$orders_post_id = wc_get_orders(
					array(
						'limit' => $per_page,
						'paged' => $page_number,
						'orderby' => 'date',
						'order' => 'DESC',
						'return' => 'ids',
						'meta_query' => array(
							array(
								'key'        => 'ced_amazon_order_countory_code',
								'comparison' => 'EXISTS',
								
							),
						),
					)
				);
		} else {

			$orders_post_id = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE `meta_key`=%s GROUP BY `post_id` ORDER BY `post_id` DESC LIMIT %d OFFSET %d", 'ced_amazon_order_countory_code', $per_page, $offset ), 'ARRAY_A' );

		}


		foreach ( $orders_post_id as $key => $value ) {
			// $post_id        = isset( $value['post_id'] ) ? $value['post_id'] : '';

			if ( $this->create_amz_order_hops ) {
			   $post_details = wc_get_order( $value );
			} else {
				$post_id      = isset( $value['post_id'] ) ? $value['post_id'] : '';
				$post_details = wc_get_order( $post_id );
			}
			$order_detail[] = $post_details->get_items();
		}



		$order_detail = isset( $order_detail ) ? $order_detail : '';

		$filterFile = CED_AMAZON_DIRPATH . 'admin/partials/order-filters.php';
		if ( file_exists( $filterFile ) ) {
			require_once $filterFile;
		}

		return ( $order_detail );
	}
}


$ced_amazon_orders_obj = new Ced_Amazon_List_Orders();
$ced_amazon_orders_obj->prepare_items();


?>
