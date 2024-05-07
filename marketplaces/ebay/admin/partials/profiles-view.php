<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( empty( get_option( 'ced_ebay_user_access_token' ) ) ) {
	wp_redirect( get_admin_url() . 'admin.php?page=ced_ebay' );
}
$file = CED_EBAY_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $file ) ) {
	require_once $file;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Ced_EBay_Profile_Table extends WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => __( 'eBay Profile', 'ebay-integration-for-woocommerce' ), // singular name of the listed records
				'plural'   => __( 'eBay Profiles', 'ebay-integration-for-woocommerce' ), // plural name of the listed records
				'ajax'     => false, // does this table support ajax?
			)
		);
	}
	/**
	 *
	 * Function for preparing profile data to be displayed column
	 */
	public function prepare_items() {

		global $wpdb;

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

		$this->items = self::ced_ebay_get_profiles( $per_page, $current_page );

		$count = self::get_count();

		// Set the pagination
		$this->set_pagination_args(
			array(
				'total_items' => $count,
				'per_page'    => $per_page,
				'total_pages' => ceil( $count / $per_page ),
			)
		);

		if ( ! $this->current_action() ) {
			$this->items = self::ced_ebay_get_profiles( $per_page, $current_page );
			$this->renderHTML();
		} else {
			$this->process_bulk_action();
		}
	}
	/**
	 *
	 * Function for status column
	 */
	public function ced_ebay_get_profiles( $per_page = 10, $page_number = 1 ) {

		global $wpdb;
		$tableName = $wpdb->prefix . 'ced_ebay_profiles';
		$offset    = ( $page_number - 1 ) * $per_page;
		$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$site_id   = isset( $_GET['site_id'] ) ? sanitize_text_field( $_GET['site_id'] ) : '';
		$result    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_ebay_profiles WHERE `ebay_user`=%s AND `ebay_site`=%s ORDER BY `id` DESC LIMIT %d OFFSET %d", $user_id, $site_id, $per_page, $offset ), 'ARRAY_A' );
		return $result;
	}

	/*
	 *
	 * Function to count number of responses in result
	 *
	 */
	public function get_count() {
		global $wpdb;
		$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$site_id   = isset( $_GET['site_id'] ) ? sanitize_text_field( $_GET['site_id'] ) : '';
		$tableName = $wpdb->prefix . 'ced_ebay_profiles';
		$result    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_ebay_profiles WHERE `ebay_user`=%s AND `ebay_site`=%s", $user_id, $site_id ), 'ARRAY_A' );
		return count( $result );
	}

	/*
	 *
	 * Text displayed when no customer data is available
	 *
	 */
	public function no_items() {
		esc_attr_e( 'No Profiles Created.', 'ebay-integration-for-woocommerce' );
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="ebay_profile_ids[]" value="%s" class="ebay_profile_ids"/>',
			$item['id']
		);
	}


	public function column_profile_action( $item ) {
		$ebay_cat_id = false;
		$user_id     = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$site_id     = isset( $_GET['site_id'] ) ? sanitize_text_field( $_GET['site_id'] ) : '';
		if ( ! empty( $item['profile_data'] ) ) {
			if ( ! empty( json_decode( $item['profile_data'], true ) ) && is_array( json_decode( $item['profile_data'], true ) ) ) {
				$profile_data = json_decode( $item['profile_data'], true );
				$ebay_cat_id  = $profile_data['_umb_ebay_category']['default'];
			}
		}
		$is_default_profile  = false;
		$get_default_profile = get_option( 'ced_ebay_default_profile_' . $user_id );
		if ( ! empty( $get_default_profile ) && $item['id'] == $get_default_profile ) {
			$is_default_profile = true;
		} else {
			$is_default_profile = false;
		}
		$woo_categories = ! empty( $item['woo_categories'] ) ? json_decode( $item['woo_categories'], true ) : array();
		if ( ! empty( $woo_categories ) ) {
			if ( ! empty( $ebay_cat_id ) ) {
				$button_html  = sprintf( '<div class="ced-ebay-bootstrap-wrapper"><div class="btn-group">' );
				$button_html .= sprintf( '<a data-tippy-content="Click to edit the profile for setting up data for listing on eBay." class="ced_ebay_show_tippy btn btn-sm btn-primary"  href="?page=%s&channel=ebay&section=%s&user_id=%s&profileID=%s&eBayCatID=%s&site_id=%s">Edit</a>', esc_attr( isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '' ), 'view-templates', esc_attr( isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '' ), $item['id'], $ebay_cat_id, $site_id );
				$button_html .= sprintf( '<a style="margin-left:10px;" data-tippy-content="Click to show products belonging to this profile. Opens in a new tab." class="ced_ebay_show_tippy btn btn-primary btn-sm"   href="?page=%s&channel=ebay&section=%s&user_id=%s&profileID=%s&eBayCatID=%s&site_id=%s">Filter Products</a>', esc_attr( isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '' ), 'products-view', esc_attr( isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '' ), $item['id'], $ebay_cat_id, $site_id );
			}
		} else {
			$button_html      = sprintf( '<div class="ced-ebay-bootstrap-wrapper"><div class="btn-group">' );
				$button_html .= sprintf( '<a data-tippy-content="Click to edit the profile for setting up data for listing on eBay." class="ced_ebay_show_tippy btn btn-sm btn-primary"  href="?page=%s&channel=ebay&section=%s&user_id=%s&profileID=%s&eBayCatID=%s&site_id=%s">Edit</a>', esc_attr( isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '' ), 'view-templates', esc_attr( isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '' ), $item['id'], $ebay_cat_id, $site_id );

		}
		return $button_html;
	}
	/**
	 * Function for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	public function column_profile_name( $item ) {

		$user_id          = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$site_id          = isset( $_GET['site_id'] ) ? sanitize_text_field( $_GET['site_id'] ) : '';
		$profile_title    = $item['profile_name'] . '<br>';
		$profile_features = '';
		$ebay_cat_id      = '';
		if ( ! empty( $item['profile_data'] ) ) {
			if ( ! empty( json_decode( $item['profile_data'], true ) ) && is_array( json_decode( $item['profile_data'], true ) ) ) {
				$profile_data = json_decode( $item['profile_data'], true );
				$ebay_cat_id  = isset( $profile_data['_umb_ebay_category']['default'] ) ? sanitize_text_field( $profile_data['_umb_ebay_category']['default'] ) : '';
				$ebay_cat_id  = intval( $ebay_cat_id );

				if ( ! is_numeric( $ebay_cat_id ) ) {
					// reject input if it's not a valid integer
					return;
				}
			}
		}
		$wp_folder     = wp_upload_dir();
		$wp_upload_dir = $wp_folder['basedir'];
		$wp_upload_dir = $wp_upload_dir . '/ced-ebay/category-specifics/' . $user_id . '/' . $site_id . '/';

		$cat_specifics_file = $wp_upload_dir . 'ebaycatfeatures_' . $ebay_cat_id . '.json';
		$cat_specifics_file = realpath( $cat_specifics_file );

		if ( file_exists( $cat_specifics_file ) ) {
			$cat_features = json_decode( file_get_contents( $cat_specifics_file ), true );
		}

		// check if cat_features is not empty and get the Category index from the array.
		if ( ! empty( $cat_features ) && is_array( $cat_features['Category'] ) && ! empty( $cat_features['Category'] ) && $ebay_cat_id == $cat_features['Category']['CategoryID'] ) {
			$variations_enabled = false;
			$best_offer_enabled = false;
			$cat_features_array = $cat_features['Category'];
			if ( isset( $cat_features_array['VariationsEnabled'] ) && true == $cat_features_array['VariationsEnabled'] ) {
				$variations_enabled = true;
				$profile_features   = ' <span>Support Variations | </span>';
			} else {
				$profile_features = ' <span>Variations Not Supported | </span>';

			}
			if ( isset( $cat_features_array['BestOfferEnabled'] ) && true == $cat_features_array['BestOfferEnabled'] ) {
				$best_offer_enabled = true;
				$profile_features  .= ' <span>Allows Best Offer</span>';
			} else {
				$profile_features .= ' <span>Best Offer Not Supported</span>';
			}
		}
		$woo_categories = ! empty( $item['woo_categories'] ) ? json_decode( $item['woo_categories'], true ) : array();
		if ( ! empty( $item['profile_data'] ) ) {
			if ( ! empty( json_decode( $item['profile_data'], true ) ) && is_array( json_decode( $item['profile_data'], true ) ) ) {
				$profile_data        = json_decode( $item['profile_data'], true );
				$get_default_profile = get_option( 'ced_ebay_default_profile_' . $user_id );
				if ( ! empty( $get_default_profile ) && $item['id'] == $get_default_profile ) {
					$is_default_profile = '<span class="ml-2 badge badge-primary">Default</badge>';
				} else {
					$is_default_profile = false;
				}
			}
		}
			$title = sprintf( '<a   style="text-decoration:none;" href="?page=%s&channel=ebay&section=%s&user_id=%s&profileID=%s&eBayCatID=%s&site_id=%s"><strong>%s</strong></a>%s', esc_attr( isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '' ), 'view-templates', esc_attr( isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '' ), $item['id'], $ebay_cat_id, $site_id, $profile_title, $profile_features );

		return $title;
	}

	/**
	 *
	 * Function for profile status column
	 */
	public function column_profile_status( $item ) {

		if ( 'inactive' == $item['profile_status'] ) {
			return 'InActive';
		} else {
			return 'Active';
		}
	}

	/**
	 *
	 * Function for category column
	 */
	public function column_woo_categories( $item ) {
		$category       = '';
		$woo_categories = json_decode( $item['woo_categories'], true );
		$profile_id     = ! empty( $item['id'] ) ? $item['id'] : '';
		if ( ! empty( $woo_categories ) && '' != $profile_id ) {
			foreach ( $woo_categories as $key => $value ) {
				$term      = get_term_by( 'id', $value, 'product_cat' );
				$cat_struc = $this->ced_ebay_creating_category_structure( $term );
				$category  = '';
				if ( $term ) {
					if ( $cat_struc ) {
						$count = count( $cat_struc );
						foreach ( array_reverse( $cat_struc ) as $index => $cat_name ) {
							if ( ( $count - 1 ) > $index ) {
								$category .= $cat_name . ' > ';
							} else {
								$category .= $cat_name;
							}
						}
						echo '<p>' . esc_attr( $category ) . '<a href="#" data-profile-id="' . esc_attr( $profile_id ) . '" data-term-id="' . esc_attr( $value ) . '" style="color:red; font-weight:bold;" id="ced_ebay_remove_term_from_profile_btn"> (Remove) </a></p>';

					} else {
						echo '<p>' . esc_attr( $term->name ) . '<a href="#" data-profile-id="' . esc_attr( $profile_id ) . '" data-term-id="' . esc_attr( $value ) . '" style="color:red; font-weight:bold;" id="ced_ebay_remove_term_from_profile_btn"> (Remove) </a></p>';
					}
				}
			}
		} else {
			echo '<p style="color:red;"><b>No WooCommerce Categories Mapped!</b></p>';
		}
	}

	public function ced_ebay_creating_category_structure( $term, $cat_struc = array() ) {
		static $category_structure = array();

		if ( is_array( $cat_struc ) && empty( $cat_struc ) ) {
			$category_structure = array();
		}

		if ( ! empty( $term->parent ) ) {
			$category_structure[] = $term->name;
			$parent_id            = $term->parent;
			$parent_term          = get_term_by( 'id', $parent_id, 'product_cat' );
			$this->ced_ebay_creating_category_structure( $parent_term, $category_structure );

		} else {
			$category_structure[] = $term->name;
			// $category_structure = array_merge($category_structure, $cat_struc);
		}

		return $category_structure;
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'             => '<input type="checkbox" />',
			'profile_name'   => __( 'Profile Name', 'ebay-integration-for-woocommerce' ),
			'woo_categories' => __( 'Mapped WooCommerce Categories', 'ebay-integration-for-woocommerce' ),
			'profile_action' => __( ' Profile Actions', 'ebay-integration-for-woocommerce' ),

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
			echo '<select name="action' . esc_attr( $two ) . '" class="bulk-action-selector ">';
			echo '<option value="-1">' . esc_attr( 'Bulk Actions' ) . "</option>\n";

			foreach ( $this->_actions as $name => $title ) {
				$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

				echo "\t" . '<option value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . "</option>\n";
			}

			echo "</select>\n";

			submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => 'ced_ebay_profile_bulk_operation' ) );
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

			// 'bulk-activate'   => __('Activate', 'ebay-integration-for-woocommerce' ),
			// 'bulk-deactivate' => __('Deactivate', 'ebay-integration-for-woocommerce' ),
			'bulk-delete' => __( 'Delete', 'ebay-integration-for-woocommerce' ),
		);
		return $actions;
	}

	/**
	 * Function to get changes in html
	 */
	public function renderHTML() {
		$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$site_id = isset( $_GET['site_id'] ) ? sanitize_text_field( $_GET['site_id'] ) : '';
		?>


<div id="post-body" class="metabox-holder columns-2">

<div class="ced-button-wrapper-top">
<a  href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=ebay&section=product-template&user_id=' . $user_id . '&site_id=' . $site_id ) ); ?>">
<button type="button" class="components-button is-primary ced-ebay-add-new-template" >
							<?php echo esc_html__( 'Create new template', 'ebay-integration-for-woocommerce' ); ?>
							</button></a>
							<button id="ced_ebay_remove_all_profiles_btn" title="Remove All Profiles" type="button" class="components-button is-primary">
		<span>Delete All Templates</span>
	</button>
	<button id="ced_ebay_reset_item_aspects_btn" title="Reset Item Specifcs" type="button" class="components-button is-primary">
		<span>Reset Item Specifcs</span>
	</button>

						</div>

	


		

		</div>
</div>

				<?php
				if ( ! session_id() ) {
					session_start();
				}

				?>

					<div id="">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								wp_nonce_field( 'ebay_profile_view', 'ebay_profile_view_actions' );
								$this->display();
								?>
							</form>
						</div>
					</div>
					<div class="clear"></div>
				<br class="clear">

				</div>

		<?php
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
			if ( ! isset( $_POST['ebay_profile_view_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ebay_profile_view_actions'] ) ), 'ebay_profile_view' ) ) {
				return;
			}
			$action = isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : '';
			return $action;
		}
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
		wp_nonce_field( 'ced_ebay_profiles_view_page_nonce', 'ced_ebay_profiles_view_nonce' );
		if ( isset( $_GET['panel'] ) && 'edit' == $_GET['panel'] ) {

			$file = CED_EBAY_DIRPATH . 'admin/partials/profile-edit-view.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	}
}

$ced_ebay_profile_obj = new Ced_EBay_Profile_Table();
$ced_ebay_profile_obj->prepare_items();

?>
