<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil as CedEtsyHPOS;
class Ced_Etsy_List_Orders extends WP_List_Table {
	private $show_from_hpos;
	/** Class constructor */
	public function __construct() {

		$this->show_from_hpos = false;
		if ( CedEtsyHPOS::custom_orders_table_usage_is_enabled() ) {
			$this->show_from_hpos = true;
		}

		parent::__construct(
			array(
				'singular' => __( 'Etsy Order', 'woocommerce-etsy-integration' ), // singular name of the listed records
				'plural'   => __( 'Etsy Orders', 'woocommerce-etsy-integration' ), // plural name of the listed records
				'ajax'     => true, // does this table support ajax?
			)
		);
	}
	/**
	 *
	 * Function for preparing data to be displayed
	 */
	public function prepare_items() {

		$per_page = 10;
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

		$this->items = self::get_orders( $per_page, $current_page );
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

			$this->renderHTML();
		}
	}
	/*
	*
	* Text displayed when no  data is available
	*
	*/
	public function no_items() {
		esc_html_e( 'No Orders To Display.', 'woocommerce-etsy-integration' );
	}
	/**
	 *
	 * Function for id column
	 */
	public function column_id( $orderID ) {
		if ( empty( $orderID ) || null === $orderID || '' === $orderID ) {
			return false;
		}
		$woo_order_url = get_edit_post_link( $orderID, '' );
		if ( is_null( $woo_order_url ) || empty( $woo_order_url ) || '' === $woo_order_url ) {
			$woo_order_url = get_admin_url() . 'admin.php?page=wc-orders&action=edit&id=' . $orderID;
		}
		echo '<a href="' . esc_url( $woo_order_url ) . '" target="_blank">#' . esc_attr( $orderID ) . '</a>';
	}
	/**
	 *
	 * Function for name column
	 */
	public function column_name( $orderID ) {
		if ( empty( $orderID ) || null === $orderID || '' === $orderID ) {
			return false;
		}
		$items = wc_get_order( $orderID );
		foreach ( $items as $key => $value ) {
			$displayOrders = $value->get_data();
			$productId     = isset( $displayOrders['product_id'] ) ? $displayOrders['product_id'] : 0;
			$url           = get_edit_post_link( $productId, '' );
			echo '<b><a class="ced_etsy_prod_name" href="' . esc_attr( $url ) . '" target="#">' . esc_attr( $displayOrders['name'] ) . '</a></b></br>';

		}
	}
	/**
	 *
	 * Function for order Id column
	 */
	public function column_etsy_order_id( $orderID ) {
		if ( empty( $orderID ) || null === $orderID || '' === $orderID ) {
			return false;
		}
		if ( $this->show_from_hpos ) {
			$wc_order      = wc_get_order( $orderID );
			$etsy_order_id = $wc_order->get_meta( 'merchant_order_id', true );
		} else {
			$etsy_order_id = get_post_meta( $orderID, 'merchant_order_id', true );
		}
		echo '<span>#' . esc_attr( $etsy_order_id ) . '</span>';
	}
	/**
	 *
	 * Function for order status column
	 */
	public function column_order_status( $orderID ) {
		if ( empty( $orderID ) || null === $orderID || '' === $orderID ) {
			return false;
		}
		$wc_order = wc_get_order( $orderID );
		$status   = $wc_order->get_status();
		echo '<div class="ced-' . esc_attr( $status ) . '-button-wrap"><a class="ced-' . esc_attr( $status ) . '-link"><span class="ced-circle" style=""></span> ' . esc_attr( ucfirst( $status ) ) . '</a> </div>';
	}

	public function column_etsy_order_status( $orderID ) {
		if ( empty( $orderID ) || null === $orderID || '' === $orderID ) {
			return false;
		}
		$e_details = array();
		if ( $this->show_from_hpos ) {
			$wc_order  = wc_get_order( $orderID );
			$e_details = $wc_order->get_meta( 'order_detail', true );
		} else {
			$e_details = get_post_meta( $orderID, 'order_detail', true );
		}
		$is_paid    = isset( $e_details['is_paid'] ) ? $e_details['is_paid'] : '';
		$is_shipped = isset( $e_details['is_shipped'] ) ? $e_details['is_shipped'] : '';
		$status     = 'processing';
		$etsyStaus  = 'Paid';
		if ( $is_shipped ) {
			$status    = 'completed';
			$etsyStaus = 'Shipped';
		}
		echo '<div class="ced-' . esc_attr( $status ) . '-button-wrap"><a class="ced-' . esc_attr( $status ) . '-link"><span class="ced-circle" style=""></span> ' . esc_attr( ucfirst( $etsyStaus ) ) . '</a> </div>';
	}

	/**
	 *
	 * Function for Edit order column
	 */
	public function column_action( $orderID ) {
		if ( empty( $orderID ) || null === $orderID || '' === $orderID ) {
			return false;
		}
		$woo_order_url = get_edit_post_link( $orderID, '' );
		if ( is_null( $woo_order_url ) || empty( $woo_order_url ) || '' === $woo_order_url ) {
			$woo_order_url = get_admin_url() . 'admin.php?page=wc-orders&action=edit&id=' . $orderID;
		}
		echo '<a href="' . esc_url( $woo_order_url ) . '" target="_blank">Edit</a>';
	}
	/**
	 *
	 * Function for customer name column
	 */
	public function column_customer_name( $orderID ) {
		if ( empty( $orderID ) || null === $orderID || '' === $orderID ) {
			return false;
		}
		$details    = wc_get_order( $orderID );
		$details    = $details->get_data();
		$first_name = isset( $details['billing']['first_name'] ) ? $details['billing']['first_name'] : '';
		echo '<b>' . esc_attr( $first_name ) . '</b>';
	}

	public function column_created( $orderID ) {
		if ( empty( $orderID ) || null === $orderID || '' === $orderID ) {
			return false;
		}
		$details = wc_get_order( $orderID );
		$details = $details->get_date_created();
		echo '<b>' . esc_attr( $details ) . '</b>';
	}


	public function column_items( $orderID ) {
		if ( empty( $orderID ) || null === $orderID || '' === $orderID ) {
			return false;
		}
		$orders     = wc_get_order( $orderID );
		$line_items = ! empty( $orders->get_items() ) ? $orders->get_items() : array();
		return '<a>' . count( $line_items ) . ' items</a>';
	}

	public function column_total( $orderID ) {
		if ( empty( $orderID ) || null === $orderID || '' === $orderID ) {
			return false;
		}
		$wc_order_info  = wc_get_order( $orderID );
		$wc_order_total = $wc_order_info->get_total();
		$currencySymbol = get_woocommerce_currency_symbol();
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
		echo esc_attr( $currencySymbol ) . '&nbsp' . esc_attr( $wc_order_total ) . '</div></div>';
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */

	public function get_columns() {
		$columns = array(
			'id'                => __( 'Store Order ID', 'woocommerce-etsy-integration' ),
			'order_status'      => __( 'Store Status', 'woocommerce-etsy-integration' ),
			'etsy_order_id'     => __( 'Etsy Order ID', 'woocommerce-etsy-integration' ),
			'etsy_order_status' => __( 'Etsy Status', 'woocommerce-etsy-integration' ),
			'items'             => __( 'Ordered Items', 'woocommerce-etsy-integration' ),
			'total'             => __( 'Order Total', 'woocommerce-etsy-integration' ),
			'customer_name'     => __( 'Customer Name', 'woocommerce-etsy-integration' ),
			'created'           => __( 'Created On', 'woocommerce-etsy-integration' ),
		);
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
	public function renderHTML() {
		$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		?>
		<div class="ced_etsy_wrap ced_etsy_wrap_extn">
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Orders', 'woocommerce-etsy-integration' ); ?></h1>
			<?php echo '<button  class="button-primary alignright" id="ced_etsy_fetch_orders" data-id="' . esc_attr( $shop_name ) . '" >' . esc_html( __( 'Fetch Orders', 'woocommerce-etsy-integration' ) ) . '</button>'; ?>
		</div>
		
			<div id="post-body" class="metabox-holder columns-2">
				<div id="">
					<div class="meta-box-sortables ui-sortable">
						<form method="post">
							<?php $this->display(); ?>
						</form>
					</div>
				</div>
				<div class="clear"></div>
			</div>
		</div>
		<?php
	}

	public function get_count() {
		$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		$order_ids = array();
		if ( $this->show_from_hpos ) {
			$hpos_orders = wc_get_orders(
				array(
					'limit'      => -1,
					'status'     => 'all',
					'return'     => 'ids',
					'meta_query' => array(
						array(
							'key'        => '_umb_etsy_marketplace',
							'value'      => 'Etsy',
							'comparison' => '==',
						),
						array(
							'key'        => 'ced_etsy_order_shop_id',
							'value'      => $shop_name,
							'comparison' => '==',
						),
						'fields' => 'ids',

					),
				)
			);
			$ced_e_all_ordrs = is_array( $hpos_orders ) ? $hpos_orders : array();
			return count( $ced_e_all_ordrs );
		} else {
			global $wpdb;
			$orders_post_id = $wpdb->get_results( $wpdb->prepare( "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key`=%s AND `meta_value`=%s", 'ced_etsy_order_shop_id', $shop_name ), 'ARRAY_A' );
			return count( $orders_post_id );
		}

	}

	/*
	 *
	 *  Function to get all the orders
	 *
	 */
	public function get_orders( $per_page, $current_page ) {
		$order_ids = array();
		$shop_name = isset( $_GET['shop_name'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_name'] ) ) : '';
		if ( $this->show_from_hpos ) {
			$offset    = ( $current_page - 1 ) * $per_page;
			$order_ids = wc_get_orders(
				array(
					'limit'      => $per_page,
					'offset'     => $offset,
					'status'     => 'all',
					'orderby'    => 'date',
					'order'      => 'DESC',
					'return'     => 'ids',
					'meta_query' => array(
						array(
							'key' => '_is_ced_etsy_order',
						),
						array(
							'key'        => '_umb_etsy_marketplace',
							'value'      => 'Etsy',
							'comparison' => '==',
						),
						array(
							'key'        => 'ced_etsy_order_shop_id',
							'value'      => $shop_name,
							'comparison' => '==',
						),
					),
				)
			);
		} else {
			global $wpdb;
			$orders_post_id = array();
			$offset         = ( $current_page - 1 ) * $per_page;
			$orders_post_id = $wpdb->get_results( $wpdb->prepare( "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key`=%s AND `meta_value`=%s  order by `post_id` DESC LIMIT %d OFFSET %d", 'ced_etsy_order_shop_id', $shop_name, $per_page, $offset ), 'ARRAY_A' );
			foreach ( $orders_post_id as $key => $value ) {
				$order_ids[] = isset( $value['post_id'] ) ? $value['post_id'] : '';
			}
		}
		return $order_ids;
	}
}

$ced_etsy_orders_obj = new Ced_Etsy_List_Orders();
$ced_etsy_orders_obj->prepare_items();

