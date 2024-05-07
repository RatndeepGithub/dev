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


class Ced_Amazon_List_Feeds extends WP_List_Table {


	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Amazon feed', 'amazon-for-woocommerce' ), // singular name of the listed records
				'plural'   => __( 'Amazon feeds', 'amazon-for-woocommerce' ), // plural name of the listed records
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
		 * Function to list order based on per page
		 *
		 * @param 'function'
		 * @param  integer 'limit'
		 * @return 'count'
		 * @since  1.0.0
		 */
		$per_page = apply_filters( 'ced_amazon_feeds_list_per_page', 10 );
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

		$this->items = self::ced_amazon_feeds( $per_page, $current_page );
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
			$this->items = self::ced_amazon_feeds( $per_page, $current_page );
			$this->renderHTML();
		} else {
			$this->process_bulk_action();
		}
	}

	/**
	 *
	 * Function for getting current status
	 */
	public function current_action() {
		if ( isset( $_GET['panel'] ) ) {
			$action = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
			return $action;
		} elseif ( isset( $_POST['action'] ) ) {
			if ( ! isset( $_POST['amazon_feed_view_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['amazon_feed_view_actions'] ) ), 'amazon_feed_view' ) ) {
				return;
			}
			$action = isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : '';
			return $action;
		}
	}

	/**
	 *
	 * Function to count number of responses in result
	 */
	public function get_count() {

		global $wpdb;
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		if ( ! empty( $seller_id ) ) {
			$mplocation_arr = explode( '|', $seller_id );
			$mplocation     = isset( $mplocation_arr[0] ) ? $mplocation_arr[0] : '';
			$allFeeds       = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_feeds WHERE `feed_location` = %s", $mplocation ), 'ARRAY_A' );
		} else {
			$allFeeds = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ced_amazon_feeds", 'ARRAY_A' );
		}
		return count( $allFeeds );
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

			echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . esc_html__( 'Select bulk action', 'amazon-for-woocommerce' ) . '</label>';
			echo '<select name="action' . esc_html__( $two, 'amazon-for-woocommerce' ) . '" class="bulk-action-selector ">';
			echo '<option value="-1">' . esc_html__( 'Bulk actions', 'amazon-for-woocommerce' ) . "</option>\n";

			foreach ( $this->_actions as $name => $title ) {
				$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

				echo "\t" . '<option value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_html__( $title ) . "</option>\n";
			}

			echo "</select>\n";

			submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => 'ced_amazon_feed_bulk_operation' ) );
			echo "\n";
		endif;
	}

	/*
	 *
	 * Text displayed when no  data is available
	 *
	 */
	public function no_items() {
		esc_html_e( 'No feeds To Display.', 'amazon-for-woocommerce' );
	}

	/*
	 * Render the bulk edit checkbox
	 *
	 */
	public function column_cb( $item ) {
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
		return sprintf(
			'<input type="checkbox" name="amazon_feed_ids[]" class="amazon_feeds_ids" value="%s" /></div></div>',
			$item['id']
		);
	}

	/**
	 *
	 * Function for id column
	 */
	public function column_id( $item ) {

		$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		echo '<b>' . esc_attr( $item['feed_id'] ) . '</b>';
		$actions['view'] = '<a class="feed-view" target="_blank" href="' . esc_url( get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=feed-view&woo-feed-id=' . esc_attr( $item['id'] ) . '&feed-id=' . esc_attr( $item['feed_id'] ) . '&feed-type=' . esc_attr( $item['feed_action'] ) . '&user_id=' . esc_attr( $user_id ) . '&seller_id=' . esc_attr( $seller_id ) ) . '" > ' . esc_html__( 'View', 'amazon-for-woocommerce' ) . '</a>';

		return $this->row_actions( $actions, true );
	}


	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => __( 'Delete', 'amazon-for-woocommerce' ),
		);
		return $actions;
	}

	/**
	 *
	 * Function for feed date column
	 */
	public function column_date( $item ) {
		echo '<b>' . esc_html__( $item['feed_date_time'], 'amazon-for-woocommerce' ) . '</b>';
	}


	/**
	 *
	 * Function display amazon feed action column
	 */
	public function column_feedFor( $item ) {

			echo '<b>' . esc_html__( $item['feed_action'], 'amazon-for-woocommerce' ) . '</b>';
	}

	/**
	 *
	 * Function for feed location column
	 */
	public function column_location( $item ) {

		echo '<b>' . esc_html__( strtoupper( $item['feed_location'] ), 'amazon-for-woocommerce' ) . '</b>';
	}


	/**
	 *
	 * Function for feed response column
	 */
	public function column_feedResponse( $item ) {

		$html  = '<div class="admin-custom-action-button-outer feed-response-main">';
		$html .= '<div class="admin-custom-action-show-button-outer">';
		$html .= '<a href="javascript:void(0)" type="button" style="" class="button feed-response button-primary" data-attr="' . esc_attr( $item['feed_id'] ) . '"><span>' . esc_html__( 'Quick response', 'amazon-for-woocommerce' ) . '</span></a>';
		$html .= '</div></div>';

		print_r( $html );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */

	public function get_columns() {
		$columns = array(
			'cb'           => '<input type="checkbox" />',
			'id'           => __( 'Feed Id', 'amazon-for-woocommerce' ),
			'date'         => __( 'Date & Time', 'amazon-for-woocommerce' ),
			'feedFor'      => __( 'Feed For', 'amazon-for-woocommerce' ),
			'location'     => __( 'Location', 'amazon-for-woocommerce' ),
			'feedResponse' => __( 'Feed Response', 'amazon-for-woocommerce' ),

		);

		/**
		 * Function to list order based on per page

		 * @param 'function'
		 * @param  integer 'limit'
		 * @return 'count'
		 * @since 1.0.0
		 */
		$columns = apply_filters( 'ced_amazon_feeds_columns', $columns );
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
	 * Function to renderHTML
	 */


	public function renderHTML() {
		$user_id        = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$feed_error_log = get_option( 'ced_amazon_feed_fetch_log_' . $user_id );
		?>
		
		
		<div id="feedViewModal" class="ced-modal">
			<div class="ced-modal-text-content modal-body">
				<div class="feed-response-modal"></div>
				<div class="ced-button-wrap-popup">
					<span class="ced-close-button ced_feed_cancel button-primary woocommerce-save-button ced-cancel"><?php echo esc_html( 'Close', 'amazon-for-woocommerce' ); ?></span>
				</div>
			</div>

		</div>

		<?php
		if ( ! empty( $feed_error_log ) ) {
			?>
				<section class="woocommerce-inbox-message plain">
					<div class="woocommerce-inbox-message__wrapper">
						<div class="woocommerce-inbox-message__content">
							<span class="woocommerce-inbox-message__date"><?php echo esc_html( ced_amazon_time_elapsed_string( $feed_error_log['timestamp'] ), 'amazon-for-woocommerce' ); ?></span>
							<h3 class="woocommerce-inbox-message__title"><?php echo esc_html( 'Whoops! It looks like there were some errors in fetching your Amazon feeds.', 'amazon-for-woocommerce' ); ?> </h3>
							<div class="woocommerce-inbox-message__text">
							<?php
							foreach ( $feed_error_log as $key => $fetch_error ) {
								if ( is_numeric( $key ) ) {
									?>
											<b><span><?php echo esc_html( $fetch_error, 'amazon-for-woocommerce' ); ?></span></b><br>
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
		
		<div id="post-body" class="metabox-holder columns-2">
			<div id="">
				<div class="meta-box-sortables ui-sortable">
					<form method="post">
						<?php
						wp_nonce_field( 'amazon_feed_view', 'amazon_feed_view_actions' );
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
	 *  Function to get all the feeds
	 *
	 */
	public function ced_amazon_feeds( $per_page = 1, $page_number = 10 ) {

		global $wpdb;
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		if ( ! empty( $seller_id ) ) {
			$mplocation_arr = explode( '|', $seller_id );
			$mplocation     = isset( $mplocation_arr[0] ) ? $mplocation_arr[0] : '';
			$offset         = ( $page_number - 1 ) * $per_page;
			$requiredFeeds  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_feeds WHERE `feed_location` = %s ORDER BY `id` DESC LIMIT %d OFFSET %d", $mplocation, $per_page, $offset ), 'ARRAY_A' );
		} else {
			$offset        = ( $page_number - 1 ) * $per_page;
			$requiredFeeds = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_feeds ORDER BY `id` DESC LIMIT %d OFFSET %d", $per_page, $offset ), 'ARRAY_A' );
		}

		return ( $requiredFeeds );
	}

	/**
	 *
	 * Function for processing bulk actions
	 */
	public function process_bulk_action() {
		$sanitized_array = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );

		if ( ! session_id() ) {
			session_start();
		} 

		wp_nonce_field( 'ced_amazon_feed_view_page_nonce', 'ced_amazon_feed_view_nonce' );

		$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( wp_unslash( $_GET['user_id'] ) ) : '';
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( wp_unslash( $_GET['seller_id'] ) ) : '';

		if ( 'bulk-delete' === $this->current_action() || ( isset( $_GET['action'] ) && 'bulk-delete' === $_GET['action'] ) ) {

			if ( ! isset( $_POST['amazon_feed_view_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['amazon_feed_view_actions'] ) ), 'amazon_feed_view' ) ) {
				return;
			}

			$feedIds = isset( $sanitized_array['amazon_feed_ids'] ) ? $sanitized_array['amazon_feed_ids'] : array();

			if ( is_array( $feedIds ) && ! empty( $feedIds ) ) {

				global $wpdb;
				foreach ( $feedIds as $id ) {
					$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_amazon_feeds WHERE `id` IN (%s)", $id ) );
				}

				header( 'Location: ' . get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=feeds-view&user_id=' . esc_attr( $user_id ) . '&seller_id=' . esc_attr( $seller_id ) );
				exit();

			} else {

				$seller_id = str_replace( '|', '%7C', $seller_id );
				wp_safe_redirect( admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=feeds-view&user_id=' . $user_id . '&seller_id=' . $seller_id );
				exit();

			}


		} elseif ( isset( $_GET['panel'] ) && 'edit' == $_GET['panel'] ) {

			$file = CED_AMAZON_DIRPATH . 'admin/partials/profile-edit-view.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}

		} else {

			$seller_id = str_replace( '|', '%7C', $seller_id );
			wp_safe_redirect( admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=feeds-view&user_id=' . $user_id . '&seller_id=' . $seller_id );
			exit();
			
		}
	}
}

$ced_amazon_feeds_obj = new Ced_Amazon_List_Feeds();
$ced_amazon_feeds_obj->prepare_items();

?>
