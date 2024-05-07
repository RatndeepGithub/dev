<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
get_walmart_header();
if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
class Ced_Walmart_Feeds_List extends WP_List_Table {

	/**
	 * Ced_Walmart_Feeds_List construct
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Walmart Feed', 'walmart-woocommerce-integration' ), // singular name of the listed records
				'plural'   => __( 'Walmart Feeds', 'walmart-woocommerce-integration' ), // plural name of the listed records
				'ajax'     => false, // does this table support ajax?
			)
		);
	}

	/**
	 * Function to prepare feed data to be displayed
	 *
	 * @since 1.0.0
	 */
	public function prepare_items() {

		global $wpdb;
		/** Get per page number to list feed status
		 *
		 * @since 1.0.0
		 */
		$per_page = apply_filters( 'ced_walmart_import_status_per_page', 20 );
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

		$this->items = self::ced_walmart_get_import_ids( $per_page );

		$count = self::get_count();

		if ( ! $this->current_action() ) {
			$this->set_pagination_args(
				array(
					'total_items' => $count,
					'per_page'    => $per_page,
					'total_pages' => ceil( $count / $per_page ),
				)
			);
			$this->render_html();
		} else {
			$this->process_bulk_action();
		}
	}

	/**
	 * Function to get import ids
	 *
	 * @since 1.0.0
	 */
	public function ced_walmart_get_import_ids( $per_page = 10 ) {
		$store_id                               = ced_walmart_get_current_active_store();
		$ced_walmart_import_data                = get_option( 'ced_walmart_import_data' . $store_id . wifw_environment(), array() );
		$ced_walmart_import_data                = array_reverse( $ced_walmart_import_data );
		$current_page                           = $this->get_pagenum();
		$count                                  = 0;
		$total_count                            = ( $current_page - 1 ) * $per_page;
		$ced_walmart_import_ids_to_be_displayed = array();
		foreach ( $ced_walmart_import_data as $key => $value ) {
			if ( 1 == $current_page && $count < $per_page ) {
				$count++;

				$ced_walmart_import_ids_to_be_displayed[ $value['feedId'] ]['feedId'] = isset( $value['feedId'] ) ? $value['feedId'] : '';
				$ced_walmart_import_ids_to_be_displayed[ $value['feedId'] ]['type']   = $value['type'];
				$ced_walmart_import_ids_to_be_displayed[ $value['feedId'] ]['time']   = $value['time'];

			} elseif ( $current_page > 1 ) {
				if ( $key < $total_count ) {
					continue;
				} elseif ( $count < $per_page ) {
					$count++;
					$ced_walmart_import_ids_to_be_displayed[ $value['feedId'] ]['feedId'] = $value['feedId'];
					$ced_walmart_import_ids_to_be_displayed[ $value['feedId'] ]['type']   = $value['type'];
					$ced_walmart_import_ids_to_be_displayed[ $value['feedId'] ]['time']   = $value['time'];
				}
			}
		}
		return $ced_walmart_import_ids_to_be_displayed;
	}

	/**
	 * Function to get number of responses
	 *
	 * @since 1.0.0
	 */
	public function get_count() {
		$ced_walmart_import_ids = get_option( 'ced_walmart_import_data' . wifw_environment(), array() );
		return count( $ced_walmart_import_ids );
	}

	/**
	 * Function to display text when no data availbale
	 *
	 * @since 1.0.0
	 */
	public function no_items() {
		esc_html_e( 'No feeds to show.', 'walmart-woocommerce-integration' );
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @since 1.0.0
	 * @param array $ced_walmart_users_detail Account Data.
	 */
	public function column_cb( $ced_walmart_feed_data ) {
		if ( isset( $ced_walmart_feed_data['feedId'] ) && ! empty( $ced_walmart_feed_data['feedId'] ) ) {
			echo "<input type='checkbox' value=" . esc_attr( $ced_walmart_feed_data['feedId'] ) . " name='ced_walmart_import_ids[]'>";
		}
	}

	/**
	 * Function for import id column
	 *
	 * @since 1.0.0
	 * @param array $ced_walmart_users_detail Account Data.
	 */
	public function column_import_id( $ced_walmart_feed_data ) {
		if ( isset( $ced_walmart_feed_data['feedId'] ) && ! empty( $ced_walmart_feed_data['feedId'] ) ) {

			$store_id = isset( $_GET['store_id'] ) ? sanitize_text_field( wp_unslash( $_GET['store_id'] ) ) : '';

			$request_page = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';
			echo '<b>Feed Id : <a>' . esc_attr( $ced_walmart_feed_data['feedId'] ) . '</a></b>';
			$url             = admin_url( 'admin.php?page=ced_walmart&section=feeds&panel' );
			$actions['edit'] = sprintf( '<a href="?page=%s&section=%s&feed_id=%s&panel=edit">View Details</a>', $request_page, 'feeds', $ced_walmart_feed_data['feedId'] );
			$actions['edit'] = sprintf( '<a href="?page=sales_channel&channel=walmart&section=feeds&panel=edit&feed_id=%s&store_id=%s">View Details</a>', $ced_walmart_feed_data['feedId'], $store_id );
			return $this->row_actions( $actions, true );
		}
	}



	/**
	 * Function for feed type column
	 *
	 * @since 1.0.0
	 * @param array $ced_walmart_users_detail Account Data.
	 */
	public function column_type( $ced_walmart_feed_data ) {
		if ( isset( $ced_walmart_feed_data['feedId'] ) && ! empty( $ced_walmart_feed_data['feedId'] ) ) {
			echo '<b class="walmart-success">' . esc_attr( strtoupper( str_replace( '_', ' ', $ced_walmart_feed_data['type'] ) ) ) . '</b>';
		}
	}




	/**
	 * Function for feed time column
	 *
	 * @since 1.0.0
	 * @param array $ced_walmart_users_detail Account Data.
	 */
	public function column_time( $ced_walmart_feed_data ) {
		if ( isset( $ced_walmart_feed_data['feedId'] ) && ! empty( $ced_walmart_feed_data['feedId'] ) ) {
			echo '<b>' . esc_attr( $ced_walmart_feed_data['time'] ) . '</b>';
		}
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox">',
			'import_id' => __( 'Walmart Feed ID', 'walmart-woocommerce-integration' ),
			'time'      => __( 'Walmart Feed Time', 'walmart-woocommerce-integration' ),
			'type'      => __( 'Walmart Feed Type', 'walmart-woocommerce-integration' ),
		);
		/** Get columns for  feed status listing
		 *
		 * @since 1.0.0
		 */
		$columns = apply_filters( 'ced_walmart_alter_import_status_table_columns', $columns );
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
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', 'walmart-woocommerce-integration' ),
		);
		return $actions;
	}

	/**
	 * Function to get changes in html
	 */
	public function render_html() {
		?>
		<div class="ced_walmart_wrap ced_walmart_wrap_extn">
			<div>
				
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
		} elseif ( isset( $_POST['action'] ) ) {

			if ( ! isset( $_POST['walmart_profiles_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['walmart_profiles_actions'] ) ), 'walmart_profiles' ) ) {
				return;
			}

			$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';
			return $action;
		} elseif ( isset( $_POST['action2'] ) ) {

			if ( ! isset( $_POST['walmart_profiles_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['walmart_profiles_actions'] ) ), 'walmart_profiles' ) ) {
				return;
			}

			$action = isset( $_POST['action2'] ) ? sanitize_text_field( wp_unslash( $_POST['action2'] ) ) : '';
			return $action;
		}
	}


	/**
	 * Function to process bulk actions.
	 */
	public function process_bulk_action() {
		if ( ( isset( $_POST['action'] ) && 'delete' == $_POST['action'] ) || ( isset( $_POST['action2'] ) && 'delete' == $_POST['action2'] ) ) {

			if ( ! isset( $_POST['walmart_profiles_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['walmart_profiles_actions'] ) ), 'walmart_profiles' ) ) {
				return;
			}
			$store_id                             = isset( $_GET['store_id'] ) ? sanitize_text_field( wp_unslash( $_GET['store_id'] ) ) : '';
			$sanitized_array                      = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$ced_walmart_import_ids_to_be_deleted = isset( $sanitized_array['ced_walmart_import_ids'] ) ? $sanitized_array['ced_walmart_import_ids'] : array();
			if ( ! empty( $ced_walmart_import_ids_to_be_deleted ) && is_array( $ced_walmart_import_ids_to_be_deleted ) ) {
				$ced_walmart_import_data = get_option( 'ced_walmart_import_data' . $store_id . wifw_environment(), array() );
				foreach ( $ced_walmart_import_ids_to_be_deleted as $index => $import_id ) {
					unset( $ced_walmart_import_data[ $import_id ] );
					update_option( 'ced_walmart_import_data' . $store_id . wifw_environment(), $ced_walmart_import_data );
				}
				$redirectURL = get_admin_url() . 'admin.php?page=sales_channel&channel=walmart&section=feeds&store_id=' . $store_id;
				wp_redirect( $redirectURL );
				exit;
			}
		} elseif ( isset( $_GET['panel'] ) && 'edit' == $_GET['panel'] ) {
			$file = CED_WALMART_DIRPATH . 'admin/pages/ced-walmart-feed-edit.php';
			if ( file_exists( $file ) ) {
				include_once $file;
			}
		}
	}
}

$ced_walmart_feed_obj = new Ced_Walmart_Feeds_List();
$ced_walmart_feed_obj->prepare_items();
