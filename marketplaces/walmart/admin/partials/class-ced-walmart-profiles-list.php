<?php
/**
 * Display list of profiles
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
 * Ced_Walmart_Profiles_List
 *
 * @since 1.0.0
 */
class Ced_Walmart_Profiles_List extends WP_List_Table {

	/**
	 * Ced_Walmart_Profiles_List construct
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Walmart Profile', 'walmart-woocommerce-integration' ),
				'plural'   => __( 'Walmart  Profiles', 'walmart-woocommerce-integration' ),
				'ajax'     => false,
			)
		);
	}

	/**
	 * Function for preparing profile data to be displayed column
	 *
	 * @since 1.0.0
	 */
	public function prepare_items() {
		global $wpdb;
		/** Get per page number to list profile
		 *
		 * @since 1.0.0
		 */
		$per_page = apply_filters( 'ced_walmart_profile_list_per_page', 10 );
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

		$this->items = self::ced_walmart_get_profiles( $per_page, $current_page );

		$count = self::get_count();

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
	 * Function for status get_profiles
	 *
	 * @since 1.0.0
	 * @param      int $per_page    Results per page.
	 * @param      int $page_number   Page number.
	 */
	public function ced_walmart_get_profiles( $per_page = 10, $page_number = 1 ) {
		$result = get_option( 'ced_mapped_cat' );
		$result = json_decode( $result, 1 );
		if ( is_array( $result ) && isset( $result ) ) {
			$profile_array = array();
			foreach ( $result['profile'] as $key => $value ) {
				if ( empty( $value['woo_cat'] ) ) {
					continue;
				}
				$profile_array[] = array(
					'cat_name'       => $key,
					'woo_categories' => $value['woo_cat'],
				);
			}
			return isset( $profile_array ) ? array_reverse( $profile_array ) : array();
		}
	}

	/**
	 * Function to count number of responses in result
	 *
	 * @since 1.0.0
	 */
	public function get_count() {

		$result        = get_option( 'ced_mapped_cat' );
		$result        = json_decode( $result, 1 );
		$profile_array = array();
		if ( is_array( $result ) && isset( $result ) ) {
			foreach ( $result['profile'] as $key => $value ) {
				if ( empty( $value['woo_cat'] ) ) {
					continue;
				}
				$profile_array[] = array(
					'cat_name'       => $key,
					'woo_categories' => $value['woo_cat'],
				);
			}
			return count( $profile_array );
		}
		return 0;
	}

	/**
	 * Text displayed when no customer data is available
	 *
	 * @since 1.0.0
	 */
	public function no_items() {
		esc_html_e( 'No  Profiles Created.', 'walmart-woocommerce-integration' );
	}

	/**
	 * Function for checkboxes
	 *
	 * @since 1.0.0
	 * @param array $ced_walmart_profile_details Profile Data.
	 */
	public function column_cb( $ced_walmart_profile_details ) {
		$profile_id = isset( $ced_walmart_profile_details['cat_name'] ) ? $ced_walmart_profile_details['cat_name'] : '';
		return sprintf(
			'<input type="checkbox" name="walmart_profile_ids[]" value="%s" />',
			$profile_id
		);
	}

	/**
	 * Function for name column
	 *
	 * @since 1.0.0
	 * @param array $ced_walmart_profile_details Profile Data.
	 */
	public function column_profile_name( $ced_walmart_profile_details ) {

		$store_id = isset( $_GET['store_id'] ) ? sanitize_text_field( wp_unslash( $_GET['store_id'] ) ) : '';

		$title              = '<strong>' . ucwords( $ced_walmart_profile_details['cat_name'] ) . '</strong>';
		$title_formatted    = str_replace( '_', ' ', $title );
		$request_page       = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';
		$format_cat_for_url = urlencode( $ced_walmart_profile_details['cat_name'] );
		$url                = admin_url( 'admin.php?page=sales_channel&channel=walmart&section=templates&details=edit&profile_id=' . $format_cat_for_url . '&section=templates&details=edit&store_id=' . $store_id );
		$actions['edit']    = '<a href=' . $url . '>Edit</a>';
		return $title_formatted . $this->row_actions( $actions, true );
	}

	/**
	 * Function for category column
	 *
	 * @since 1.0.0
	 * @param array $ced_walmart_profile_details Profile Data.
	 */
	public function column_woo_categories( $ced_walmart_profile_details ) {
		$woo_categories = $ced_walmart_profile_details['woo_categories'];

		if ( ! empty( $woo_categories ) ) {
			foreach ( $woo_categories as $key => $value ) {
				$term = get_term_by( 'id', $value, 'product_cat' );
				if ( $term ) {
					echo '<p>' . esc_attr( $term->name ) . '</p>';
				}
			}
		}
	}

	/**
	 * Associative array of columns
	 *
	 * @since 1.0.0
	 */
	public function get_columns() {

		$columns = array(
			'cb'             => '<input type="checkbox" />',
			'profile_name'   => __( 'Profile Name', 'walmart-woocommerce-integration' ),
			'woo_categories' => __( 'Mapped WooCommerce Categories', 'walmart-woocommerce-integration' ),
		);

		/** Get columns for profile
		 *
		 * @since 1.0.0
		 */
		$columns = apply_filters( 'ced_walmart_alter_profiles_table_columns', $columns );
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
	 * Returns an associative array containing the bulk action
	 *
	 * @since 1.0.0
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => __( 'Delete', 'walmart-woocommerce-integration' ),
		);

		return $actions;
	}

	/**
	 * Function to get changes in html
	 *
	 * @since 1.0.0
	 */
	public function render_html() {
		?>
		<div class="ced_walmart_wrap ced_walmart_wrap_extn">
			<div class="wrap">
				<h1 class="wp-heading-inline">Templates</h1>	
			<a href="
			<?php
			echo esc_attr(
				ced_get_navigation_url(
					'walmart',
					array(
						'section'  => 'templates',
						'details'  => 'edit',
						'store_id' => ced_walmart_get_current_active_store(),
					)
				)
			);
			?>
						" class="button-primary alignright">Create new template</a>
			</div>	
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
				<br class="clear">
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

		$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$profile_ids = isset( $sanitized_array['walmart_profile_ids'] ) ? $sanitized_array['walmart_profile_ids'] : array();
		if ( isset( $_GET['details'] ) ) {
			$action = isset( $_GET['details'] ) ? sanitize_text_field( wp_unslash( $_GET['details'] ) ) : '';
			return $action;
		} elseif ( isset( $_POST['action'] ) ) {

			if ( ! isset( $_POST['walmart_profiles_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['walmart_profiles_actions'] ) ), 'walmart_profiles' ) ) {
				return;
			}

			$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';
			return $action;
		} elseif ( empty( $profile_ids ) ) {
			return;
		}
	}

	/**
	 * Function for processing bulk actions
	 *
	 * @since 1.0.0
	 */
	public function process_bulk_action() {

		if ( isset( $_POST['action'] ) && 'bulk-delete' == $_POST['action'] || isset( $_POST['action2'] ) && 'bulk-delete' == $_POST['action2'] ) {

			if ( ! isset( $_POST['walmart_profiles_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['walmart_profiles_actions'] ) ), 'walmart_profiles' ) ) {
				return;
			}

			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$profile_ids     = isset( $sanitized_array['walmart_profile_ids'] ) ? $sanitized_array['walmart_profile_ids'] : array();

			$store_id = isset( $_GET['store_id'] ) ? sanitize_text_field( wp_unslash( $_GET['store_id'] ) ) : '';
			foreach ( $profile_ids as $index => $profile_id ) {
				$ced_walmart_profile_details = get_option( 'ced_mapped_cat' );
				$ced_walmart_profile_details = json_decode( $ced_walmart_profile_details, 1 );
				foreach ( $ced_walmart_profile_details['profile'] as $key => $value ) {
					if ( $key == $profile_id ) {
						foreach ( $value['woo_cat'] as $key_id => $value_id ) {
							delete_term_meta( $value_id, 'ced_walmart_category' );
						}
						unset( $ced_walmart_profile_details['profile'][ $profile_id ] );
						delete_option( 'ced_walmart_cat_visible_' . $profile_id );
						delete_option( 'ced_walmart_cat_visible_variable_attr' . $profile_id );
						if ( empty( $ced_walmart_profile_details['profile'] ) ) {
							delete_option( 'ced_mapped_cat' );
						} else {
							update_option( 'ced_mapped_cat', json_encode( $ced_walmart_profile_details ), 1 );
						}
					}
				}
			}
			$redirectURL = get_admin_url() . 'admin.php?page=sales_channel&channel=walmart&section=templates&store_id=' . $store_id;
			wp_redirect( $redirectURL );
			exit;
		} elseif ( isset( $_GET['details'] ) && 'edit' == $_GET['details'] ) {
			$file = CED_WALMART_DIRPATH . 'admin/partials/ced-walmart-profile-edit.php';
			if ( file_exists( $file ) ) {
				include_once $file;
			}
		} else {
			$redirectURL = get_admin_url() . 'admin.php?page=sales_channel&channel=walmart&section=templates&store_id=' . $store_id;
			wp_redirect( $redirectURL );
			exit;
		}
	}
}

$ced_walmart_profile_obj = new Ced_Walmart_Profiles_List();
$ced_walmart_profile_obj->prepare_items();
