<?php
/**
 * Shipping Template
 *
 * @package  Walmart_Woocommerce_Integration
 * @version  1.0.0
 * @link     https://woocommerce.com/vendor/cedcommerce/
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
?>





<?php


// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
get_walmart_header();
if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
class Ced_Walmart_Shipping_Template_List extends WP_List_Table {

	/**
	 * Ced_Walmart_Feeds_List construct
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Walmart Shipping Template', 'walmart-woocommerce-integration' ), // singular name of the listed records
				'plural'   => __( 'Walmart Shipping Template', 'walmart-woocommerce-integration' ), // plural name of the listed records
				'ajax'     => false, // does this table support ajax?
			)
		);
	}

	/**
	 * Function to prepare Shipping Templates to be displayed
	 *
	 * @since 1.0.0
	 */
	public function prepare_items() {

		global $wpdb;
		/** Get per page number to list shipping templates
		 *
		 * @since 1.0.0
		 */
		$per_page = apply_filters( 'ced_walmart_shipping_templates_per_page', 20 );
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

		$this->items = self::ced_walmart_get_shipping_templates();

		$count = self::get_count();

		if ( ! $this->current_action() ) {
			$this->render_html();
		} else {
			$this->process_bulk_action();
		}
	}

	/**
	 * Function to get get_shipping_templates
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_get_shipping_templates() {
		$store_id                       = ced_walmart_get_current_active_store();
		$ced_walmart_shipping_templates = get_option( 'ced_walmart_shipping_templates' . wifw_environment() . $store_id );
		$ced_walmart_shipping_templates = json_decode( $ced_walmart_shipping_templates, 1 );
		if ( is_array( $ced_walmart_shipping_templates ) ) {
			$ced_walmart_shipping_templates = array_reverse( $ced_walmart_shipping_templates );
			return $ced_walmart_shipping_templates['shippingTemplates'];
		}
	}

	/**
	 * Function to get number of responses
	 *
	 * @since 1.0.0
	 */
	public function get_count() {
		$store_id                       = ced_walmart_get_current_active_store();
		$ced_walmart_shipping_templates = json_decode( get_option( 'ced_walmart_shipping_templates' . wifw_environment() . $store_id ), 1 );
		if ( is_array( $ced_walmart_shipping_templates ) && isset( $ced_walmart_shipping_templates ) ) {
			return count( $ced_walmart_shipping_templates );
		}
		return;
	}

	/**
	 * Function to display text when no data availbale
	 *
	 * @since 1.0.0
	 */
	public function no_items() {
		esc_html_e( 'No Shipping Templates to show.', 'walmart-woocommerce-integration' );
	}


	/**
	 * Function for template id column
	 *
	 * @since 1.0.0
	 * @param array $ced_walmart_feed_data
	 */
	public function column_id( $ced_walmart_shipping_template_data ) {

		if ( isset( $ced_walmart_shipping_template_data['id'] ) && ! empty( $ced_walmart_shipping_template_data['id'] ) ) {
			$request_page = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';
			echo '<b>Template Id: <a>' . esc_attr( $ced_walmart_shipping_template_data['id'] ) . '</a></b>';
			$url               = admin_url( 'admin.php?page=ced_walmart&section=shipping_template&panel' );
			$actions['delete'] = sprintf( '<a href="?page=%s&section=%s&template_id=%s&panel=delete">Delete</a>', $request_page, 'shipping_template', $ced_walmart_shipping_template_data['id'] );
			return $this->row_actions( $actions, true );
		}
	}

	public function column_name( $ced_walmart_shipping_template_data ) {
		if ( isset( $ced_walmart_shipping_template_data['name'] ) && ! empty( $ced_walmart_shipping_template_data['name'] ) ) {

			return esc_attr( $ced_walmart_shipping_template_data['name'] );
		}
	}

	public function column_status( $ced_walmart_shipping_template_data ) {
		if ( isset( $ced_walmart_shipping_template_data['status'] ) && ! empty( $ced_walmart_shipping_template_data['status'] ) ) {

			return esc_attr( $ced_walmart_shipping_template_data['status'] );
		}
	}



	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'id'     => __( 'Shipping Template Id', 'walmart-woocommerce-integration' ),
			'name'   => __( 'Shipping Template Name', 'walmart-woocommerce-integration' ),
			'status' => __( 'Shipping Template Status', 'walmart-woocommerce-integration' ),
		);
		/** Get columns for shipping templates
		 *
		 * @since 1.0.0
		 */
		$columns = apply_filters( 'ced_walmart_alter_shipping_templates_table_columns', $columns );
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
	 * Function to get changes in html
	 */
	public function render_html() {

		$store_id = isset( $_GET['store_id'] ) ? sanitize_text_field( $_GET['store_id'] ) : '';
		?>

		<div class="ced_walmart_wrap ced_walmart_wrap_extn">
			
			<div class="ced-div-wrapper">
				<div>
					<input type="button" class="button button-primary" id="ced_walmart_get_shipping_template" name="" value="Get Updated Shipping Template">
					
					<input type="button" class="button button-primary" id="ced_walmart_get_fulfillment_center" name="" value="Fetch Fulfillment Center's"></div>
					
					<div class="ced_shipping_template_wrapper">
						<input type="button" class="button button-primary" id="ced_walmart_create_shipping_template"  style="float: right"value="Create New Shipping Template">


						<div id="ced_shipping_types">
							<a  href="?page=sales_channel&channel=walmart&section=shipping_template&panel=create_template&type=standard&store_id=<?php echo esc_attr( $store_id ); ?>" type="button" class="ced_shipping_type" name="">
								Standard Template
							</a>

							<a  href="?page=sales_channel&channel=walmart&section=shipping_template&panel=create_template&type=paidstandard&store_id=<?php echo esc_attr( $store_id ); ?>" type="button" class="ced_shipping_type" name="">
								Paid Standard Template
							</a>

					<!-- <a href="?page=ced_walmart&section=shipping_template&panel=create_template&type=freight" type="button" class="ced_shipping_type" name="">
						Freight Template
					</a> -->
					

				</div>
			</div>
		</div>


		<div id="post-body" class="metabox-holder">
			
			<?php
			wp_nonce_field( 'walmart_shipping_templates', 'walmart_shipping_templates_actions' );
			$this->display();
			?>
			
			<div class="clear"></div>
		</div>
		
	</div>
		<?php
	}

	// **
	// * Function for getting current status
	// *
	// * @since 1.0.0
	// */
	public function current_action() {
		if ( isset( $_GET['panel'] ) ) {
			$action = isset( $_GET['panel'] ) ? sanitize_text_field( wp_unslash( $_GET['panel'] ) ) : '';
			return $action;
		}
	}


	// /**
	// * Function to process bulk actions.
	// */
	public function process_bulk_action() {
		if ( isset( $_GET['panel'] ) && 'edit' == $_GET['panel'] ) {
			$file = CED_WALMART_DIRPATH . 'admin/pages/ced-walmart-shipping-template-edit.php';
			if ( file_exists( $file ) ) {
				include_once $file;
			}
		}

		if ( isset( $_GET['panel'] ) && 'delete' == $_GET['panel'] ) {
			$template_id = isset( $_GET['template_id'] ) ? sanitize_text_field( $_GET['template_id'] ) : 0;
			if ( $template_id ) {
				$ced_walmart_curl_instance = Ced_Walmart_Curl_Request::get_instance();
				$action                    = 'settings/shipping/templates/' . esc_attr( $template_id );
				$status                    = 400;
				$message                   = 'Some error occurred';
					/** Refresh token hook for walmart
					 *
					 * @since 1.0.0
					 */
					do_action( 'ced_walmart_refresh_token' );
					$response = $ced_walmart_curl_instance->ced_walmart_delete_request( $action );

				if ( isset( $response['id'] ) ) {
					$status  = 200;
					$message = 'Shipping Template Deleted Successfully';
					wp_redirect( 'admin.php?page=sales_channel&channel=walmart&section=shipping_template' );
				}

				if ( isset( $response['errors'] ) ) {
					$status  = 400;
					$message = isset( $response['errors'][0]['description'] ) ? $response['errors'][0]['description'] : 'Some error occured';
					wp_redirect( 'admin.php?page=sales_channel&channel=walmart&section=shipping_template' );
				}
			}
		}

		if ( isset( $_GET['panel'] ) && 'create_template' == $_GET['panel'] ) {
			$file = CED_WALMART_DIRPATH . 'admin/pages/ced-walmart-create-shipping-template.php';
			if ( file_exists( $file ) ) {
				include_once $file;
			}
		}
	}
}

	$ced_walmart_feed_obj = new Ced_Walmart_Shipping_Template_List();
	$ced_walmart_feed_obj->prepare_items();
