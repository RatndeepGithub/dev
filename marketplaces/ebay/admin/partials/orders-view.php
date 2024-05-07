<?php
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

class Ced_EBay_List_Orders extends WP_List_Table {



	/** Class constructor */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'eBay Order', 'ebay-integration-for-woocommerce' ), // singular name of the listed records
				'plural'   => __( 'eBay Orders', 'ebay-integration-for-woocommerce' ), // plural name of the listed records
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

		$this->items = self::ced_ebay_orders( $per_page, $current_page );
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
			$this->items = self::ced_ebay_orders( $per_page, $current_page );
			$this->renderHTML();
		}
	}

	/**
	 *
	 * Function to count number of responses in result
	 */
	public function get_count() {
		global $wpdb;
		$user_id      = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$site_id      = isset( $_GET['sid'] ) ? sanitize_text_field( $_GET['sid'] ) : '';
		$country_code = false !== ( $this->ced_ebay_get_ebay_site( $site_id ) ) ? $this->ced_ebay_get_ebay_site( $site_id ) : '';
		$wooOrderIds  = array();
		$args         = array(
			'type'                          => 'shop_order',
			'order'                         => 'DESC',
			'ced_ebay_order_user_id'        => $user_id,
			'ced_ebay_listingMarketplaceId' => 'EBAY_' . $country_code,
			'return'                        => 'ids',
		);
		$wooOrderIds  = wc_get_orders( $args );
		if ( is_wp_error( $wooOrderIds ) || empty( $wooOrderIds ) ) {
			return 0;
		} else {
			return count( $wooOrderIds );
		}
	}

	public function ced_ebay_get_ebay_site( $site_id ) {
		$configInstance  = \Ced\Ebay\Ebayconfig::get_instance();
		$ebaySiteDetails = $configInstance->getEbaycountrDetail( $site_id );
		if ( ! empty( $ebaySiteDetails ) && is_array( $ebaySiteDetails ) && isset( $ebaySiteDetails['countrycode'] ) ) {
			return $ebaySiteDetails['countrycode'];
		} else {
			return false;
		}
	}

	/*
	 *
	 * Text displayed when no  data is available
	 *
	 */
	public function no_items() {
		esc_html_e( 'No Orders To Display.', 'ebay-integration-for-woocommerce' );
	}
	/**
	 *
	 * Function for id column
	 */
	public function column_id( $items ) {

		foreach ( $items as $key => $value ) {
			$displayOrders = $value->get_data();
			echo '<b>' . esc_attr( $displayOrders['order_id'] )
				. '</b>';
			break;
		}
	}
	/**
	 *
	 * Function for name column
	 */
	public function column_name( $items ) {

		foreach ( $items as $key => $value ) {
			$displayOrders = $value->get_data();
			$productId     = $displayOrders['product_id'];
			$url           = get_edit_post_link( $productId, '' );
			echo '<b><a class="ced_ebay_prod_name" href="' . esc_attr( $url )
				. '" target="#">' . esc_attr( $displayOrders['name'] )
				. '</a></b><br>';
		}
	}
	/**
	 *
	 * Function for order Id column
	 */
	public function column_ebay_order_id( $items ) {

		foreach ( $items as $key => $value ) {
			$displayOrders   = $value->get_data();
			$orderID         = $displayOrders['order_id'];
			$details         = wc_get_order( $orderID );
			$details         = $details->get_data();
			$order_meta_data = $details['meta_data'];
			foreach ( $order_meta_data as $key1 => $value1 ) {
				$order_id = $value1->get_data();
				if ( 'merchant_order_id' == $order_id['key'] ) {
					echo '<b>' . esc_attr( $order_id['value'] )
						. '</b>';
				}
			}
			break;
		}
	}
	/**
	 *
	 * Function for order status column
	 */
	public function column_order_status( $items ) {

		foreach ( $items as $key => $value ) {
			$displayOrders   = $value->get_data();
			$orderID         = $displayOrders['order_id'];
			$details         = wc_get_order( $orderID );
			$details         = $details->get_data();
			$order_meta_data = $details['meta_data'];
			foreach ( $order_meta_data as $key1 => $value1 ) {
				$order_status = $value1->get_data();
				if ( '_ebay_umb_order_status' == $order_status['key'] ) {
					echo '<b>' . esc_attr( $order_status['value'] )
						. '</b>';
				}
			}
			break;
		}
	}
	/**
	 *
	 * Function display ebay user id column
	 */
	public function column_ebay_user_id( $items ) {

		foreach ( $items as $key => $value ) {
			$displayOrders = $value->get_data();
			$orderID       = $displayOrders['order_id'];
			$ebayUserId    = get_post_meta( $orderID, 'ebayBuyerUserId', true );
			echo '<b>' . esc_attr( $ebayUserId ) . '</b>';
			break;
		}
	}
	/**
	 *
	 * Function for Edit order column
	 */
	public function column_action( $items ) {

		foreach ( $items as $key => $value ) {
			$displayOrders = $value->get_data();
			$woo_order_url = get_edit_post_link( $displayOrders['order_id'], '' );
			echo '<a href="' . esc_attr( $woo_order_url )
				. '" target="#">' . esc_attr( 'Edit', 'ebay-integration-for-woocommerce' ) . '</a>';
			break;
		}
	}
	/**
	 *
	 * Function for customer name column
	 */
	public function column_customer_name( $items ) {

		foreach ( $items as $key => $value ) {
			$displayOrders = $value->get_data();
			$orderID       = $displayOrders['order_id'];
			$details       = wc_get_order( $orderID );
			$details       = $details->get_data();
			echo '<b>' . esc_attr( $details['billing']['first_name'] )
				. '</b>';
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
			'id'            => __( 'WooCommerce Order', 'ebay-integration-for-woocommerce' ),
			'name'          => __( 'Product Name', 'ebay-integration-for-woocommerce' ),
			'ebay_order_id' => __( 'eBay Order ID', 'ebay-integration-for-woocommerce' ),
			'customer_name' => __( 'Customer Name', 'ebay-integration-for-woocommerce' ),
			'order_status'  => __( 'Order Status', 'ebay-integration-for-woocommerce' ),
			'action'        => __( 'Action', 'ebay-integration-for-woocommerce' ),
			'ebay_user_id'  => __( 'eBay User ID', 'ebay-integration-for-woocommerce' ),

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
		$user_id         = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$order_error_log = get_option( 'ced_ebay_order_fetch_log_' . $user_id );
		?>
		
		<?php
		if ( ! empty( $order_error_log ) ) {
			?>
			<div class="woocommerce-homepage-notes-wrapper"><div>
				<div data-wp-c16t="true" data-wp-component="Card" class="components-surface components-card css-1pd4mph e19lxcc00">
					<div class="css-10klw3m e19lxcc00"><div role="menu"><section class="woocommerce-inbox-message plain ced-ebay-order-errors-notification">
						<div class="woocommerce-inbox-message__wrapper"><div class="woocommerce-inbox-message__content ced-ebay-error-notifications-content">
							<span class="woocommerce-inbox-message__date"><?php echo esc_html( ced_ebay_time_elapsed_string( $order_error_log['timestamp'] ) ); ?>
						</span><h3 class="woocommerce-inbox-message__title"><a href="#" class="components-button is-link">
						Whoops! It looks like there were some errors in fetching your eBay Orders.</a></h3><div class="woocommerce-inbox-message__text">
						<?php
						foreach ( $order_error_log as $key => $fetch_error ) {
							if ( is_numeric( $key ) ) {
								?>
									<b><span><?php echo esc_html( $fetch_error ); ?></span></b><br>
								<?php
							}
						}
						?>
						

			</div></div></div></section></div></div><div data-wp-c16t="true" data-wp-component="Elevation" class="components-elevation css-7g516l e19lxcc00" aria-hidden="true"></div><div data-wp-c16t="true" data-wp-component="Elevation" class="components-elevation css-7g516l e19lxcc00" aria-hidden="true"></div></div></div></div>

			<?php
		}
		?>
		<div class="ced-ebay-order-actions-buttons">
		<button id="ced_ebay_fetch_orders"  data-id="<?php echo esc_attr( isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '' ); ?>"  title="Fetch Orders" type="button" class="components-button is-primary">
<span>Fetch Orders</span>
</button>
		

<div class="ced-ebay-fetch-order-action-button">
	<input type="text" id="ced_ebay_fetch_single_order_input" placeholder="Enter eBay Order Number">
<button id="ced_ebay_fetch_single_order_btn"  title="Fetch Order" type="button" class="button">
<span>Fetch Order</span>
</button>
</div>
		</div>
		



		<div id="post-body" class="metabox-holder columns-2">
			<div id="">
				<div class="meta-box-sortables ui-sortable">
					<form method="post">
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
	/*
	 *
	 *  Function to get all the orders
	 *
	 */
	public function ced_ebay_orders( $per_page = 10, $page_number = 1 ) {
		global $wpdb;
		$user_id      = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$site_id      = isset( $_GET['sid'] ) ? sanitize_text_field( $_GET['sid'] ) : '';
		$country_code = false !== ( $this->ced_ebay_get_ebay_site( $site_id ) ) ? $this->ced_ebay_get_ebay_site( $site_id ) : '';
		$order_detail = array();
		$wooOrderIds  = array();
		$offset       = ( $page_number - 1 ) * $per_page;
		$args         = array(
			'type'                          => 'shop_order',
			'paged'                         => $page_number,
			'offset'                        => $offset,
			'limit'                         => $per_page,
			'order'                         => 'DESC',
			'ced_ebay_order_user_id'        => $user_id,
			'ced_ebay_listingMarketplaceId' => 'EBAY_' . $country_code,
			'return'                        => 'ids',
		);
		$wooOrderIds  = wc_get_orders( $args );
		if ( is_wp_error( $wooOrderIds ) ) {
			return $order_detail;
		}
		if ( ! empty( $wooOrderIds ) ) {
			foreach ( $wooOrderIds as $key => $wooOrderId ) {
				$post_details   = wc_get_order( $wooOrderId );
				$order_detail[] = $post_details->get_items();
			}
			$order_detail = isset( $order_detail ) ? $order_detail : array();
		}
		return ( $order_detail );
	}
}

$ced_ebay_orders_obj = new Ced_EBay_List_Orders();
$ced_ebay_orders_obj->prepare_items();
