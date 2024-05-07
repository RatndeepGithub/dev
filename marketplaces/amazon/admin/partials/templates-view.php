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

class Ced_Amazon_Profile_Table extends WP_List_Table {

	public $current_amazon_profile;
	public $cloneTemplateIds = array();
	

	/** Class constructor */
	public function __construct() {

		$this->cloneTemplateIds = get_option( 'ced_amz_cloned_templates', array() );

		parent::__construct(
			array(
				'singular' => __( 'Amazon Template', 'amazon-for-woocommerce' ),
				'plural'   => __( 'Amazon Templates', 'amazon-for-woocommerce' ),
				'ajax'     => false,
			)
		);
	}

	/**
	 *
	 * Function for preparing profile data to be displayed column
	 */
	public function prepare_items() {

		/**
		 * Function to get listing per page
		 *
		 * @param 'function'
		 * @param  integer 'limit'
		 * @return 'count'
		 * @since  1.0.0
		 */
		$per_page = apply_filters( 'ced_amazon_profile_list_per_page', 10 );
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

		$this->items = self::ced_amazon_get_profiles( $per_page, $current_page );

		$count = self::get_count();

		$this->set_pagination_args(
			array(
				'total_items' => $count,
				'per_page'    => $per_page,
				'total_pages' => ceil( $count / $per_page ),
			)
		);

		if ( ! $this->current_action() ) {
			
			$this->items = self::ced_amazon_get_profiles( $per_page, $current_page );
			$this->renderHTML();
		} else {
			
			$this->process_bulk_action();
		}
	}

	/**
	 *
	 * Function for status column
	 */
	public function ced_amazon_get_profiles( $per_page = 1, $page_number = 1 ) {

		global $wpdb;
		$offset    = ( $page_number - 1 ) * $per_page;
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';

		$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles WHERE `seller_id` = %s ORDER BY `id` DESC LIMIT %d OFFSET %d", $seller_id, $per_page, $offset ), 'ARRAY_A' );
		return $result;
	}

	/*
	 *
	 * Function to count number of responses in result
	 *
	 */
	public function get_count() {

		global $wpdb;
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';

		$amazon_profiles = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles WHERE `seller_id` = %s", $seller_id ), 'ARRAY_A' );
		if ( ! empty( $amazon_profiles ) ) {
			return count( $amazon_profiles );
		} else {
			return 0;
		}
	}

	/*
	*
	* Text displayed when no customer data is available
	*
	*/

	public function no_items() {
		echo esc_html__( 'No Templates Created.', 'amazon-for-woocommerce' );
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
			'<input type="checkbox" name="amazon_profile_ids[]" value="%s" class="amazon_profile_ids"/>',
			$item['id']
		);
	}


	/**
	 * Function for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	public function column_profile_name( $item ) {

		$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';

		$template_id = $item['id'];
		$cloned = false;

		if ( isset( $this->cloneTemplateIds[ $seller_id ] ) && in_array( $template_id, $this->cloneTemplateIds[ $seller_id ] ) ) {
			$cloned = true;
		}

		global $wpdb;
		$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles WHERE `id` = %s ", $template_id ), 'ARRAY_A' );

		$current_amazon_profile = isset( $result[0] ) ? $result[0] : array();

		$amazonCategories = '-';
		if ( ! empty( $current_amazon_profile['amazon_categories_name'] ) ) {
			$amazonCategories = $current_amazon_profile['amazon_categories_name'];
		} elseif ( ! empty( $current_amazon_profile['primary_category'] ) && ! empty( $current_amazon_profile['secondary_category'] ) && ! empty( $current_amazon_profile['browse_nodes_name'] ) ) {
				$amazonCategories = $current_amazon_profile['primary_category'] . ' > ' . $current_amazon_profile['secondary_category'] . ' > ' . $current_amazon_profile['browse_nodes_name'];
		} elseif ( ! empty( $current_amazon_profile['primary_category'] ) && ! empty( $current_amazon_profile['secondary_category'] ) ) {
			$amazonCategories = $current_amazon_profile['primary_category'] . ' > ' . $current_amazon_profile['secondary_category'];
		} elseif ( ! empty( $current_amazon_profile['primary_category'] ) ) {
			$amazonCategories = $current_amazon_profile['primary_category'];
		}

		$ced_amaz_cat_val_array = $this->ced_amz_get_woo_categories();

		$wooUsedCategoriesArray = isset( $ced_amaz_cat_val_array['wooUsedCategoriesArray'] ) ? $ced_amaz_cat_val_array['wooUsedCategoriesArray'] : array() ;
		$allWooCategories       = isset( $ced_amaz_cat_val_array['allWooCategories'] ) ? $ced_amaz_cat_val_array['allWooCategories'] : array() ;

		echo '<p>' . esc_html__( $amazonCategories, 'amazon-for-woocommerce' );

		if ( $cloned ) { ?>  
			<span class="ced-clone-lable-wrapper"> <span>Clone</span> </span>
			<?php
		}
		
		echo '</p>';

		$actions['edit'] = '<a class="profile-edit" target="_blank" href="' . esc_attr( get_admin_url() ) . 'admin.php?page=sales_channel&channel=amazon&section=add-new-template&template_id=' . esc_attr( $item['id'] ) . '&template_type=' . esc_attr( $item['template_type'] ) . '&user_id=' . esc_attr( $user_id ) . '&seller_id=' . esc_attr( $seller_id ) . '">Edit</a>';
	
		$actions['clone'] = '<a class="ced-amz-profile-clone"  href="#" data-clone_tmp_id="' . esc_attr($template_id) . '"

		data-woo-used-cat="' . esc_attr( htmlspecialchars( json_encode( $wooUsedCategoriesArray ) ) ) . '" data-woo-all-cat = "' . esc_attr( htmlspecialchars( json_encode( $allWooCategories ) ) ) . '"
		>Clone</a>';
	
	
	
		return $this->row_actions( $actions, true );
	}

	/**
	 *
	 * Function for profile status column
	 */
	public function column_profile_status( $item ) {
		if ( isset( $item['profile_status'] ) && ! empty( $item['profile_status'] ) ) {

			if ( 'inactive' == $item['profile_status'] ) {
				return 'InActive';
			} else {
				return 'Active';
			}
		} else {
			return 'Active';
		}
	}

	/**
	 *
	 * Function for category column
	 */
	public function column_woo_categories( $item ) {

		$woo_categories = json_decode( $item['wocoommerce_category'], true );

		if ( ! empty( $woo_categories ) ) {
			foreach ( $woo_categories as $key => $value ) {
				$term = get_term_by( 'id', $value, 'product_cat' );
				if ( isset( $term ) && ! empty( $term ) ) {
					echo '<span class="' . esc_attr( $item['id'] ) . '" id="' . esc_attr( $term->term_id ) . '">' . esc_attr( $term->name ) . ' </span>';
					if ( $key + 1 < count( $woo_categories ) ) {
						echo '<br>';
					}
				}
			}
		} else {
			echo esc_html__( 'No category mapped', 'amazon-for-woocommerce' );
		}
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'             => '<input type="checkbox" />',
			'profile_name'   => __( 'Amazon category', 'amazon-for-woocommerce' ),
			'profile_status' => __( 'Template status', 'amazon-for-woocommerce' ),
			'woo_categories' => __( 'Mapped WooCommerce categories', 'amazon-for-woocommerce' ),

		);

		/**
		 * Function to alter profile table columns
		 *
		 * @param 'function'
		 * @param  integer 'limit'
		 * @return 'count'
		 * @since 1.0.0
		 */
		$columns = apply_filters( 'ced_amazon_alter_profiles_table_columns', $columns );
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
			echo '<option value="-1">' . esc_attr( 'Bulk actions' ) . "</option>\n";

			foreach ( $this->_actions as $name => $title ) {
				$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

				echo "\t" . '<option value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . "</option>\n";
			}

			echo "</select>\n";

			submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => 'ced_amazon_profile_bulk_operation' ) );
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
			'bulk-delete' => __( 'Delete', 'amazon-for-woocommerce' ),
		);
		return $actions;
	}


	public function ced_amz_get_woo_categories() {

		global $wpdb;
		$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';

		$wooUsedCategoriesArray = array();
		$wooUsedCategories      = $wpdb->get_results( $wpdb->prepare( "SELECT `wocoommerce_category` FROM {$wpdb->prefix}ced_amazon_profiles WHERE `seller_id` = %s", $seller_id ), 'ARRAY_A' );

		if ( ! empty( $wooUsedCategories ) ) {
			foreach ( $wooUsedCategories as $wooUsedCategory ) {
				$decoded_woo_categories = json_decode( $wooUsedCategory['wocoommerce_category'], true );
				if ( ! empty( $decoded_woo_categories ) ) {
					foreach ( $decoded_woo_categories as $decoded_woo_category ) {

						settype( $decoded_woo_category, 'integer' );
						$wooUsedCategoriesArray[] = $decoded_woo_category;
					}
				}
			}
		}

		$wooUsedCategoriesArray = array_values( array_unique( $wooUsedCategoriesArray ) );

		$allWooCategories = array();
		$categories       = get_terms( 'product_cat' );

		if ( ! empty( $categories ) ) {
			foreach ( $categories as $category ) {
				$cat                = json_decode( wp_json_encode( $category ), true );
				$allWooCategories[] = $cat['term_id'];
			}
		}

		return array( 'allWooCategories' => $allWooCategories, 'wooUsedCategoriesArray' => $wooUsedCategoriesArray );
	}

	/**
	 * Function to get changes in html
	 */
	public function renderHTML() {

		if ( isset( $_POST['ced_amazon_profile_edit'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_profile_edit'] ), 'ced_amazon_profile_edit_page_nonce' ) ) {

			if ( isset( $_POST['add_meta_keys'] ) || isset( $_POST['ced_amazon_profile_save_button'] ) ) {


				$sanitized_array     = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
				$amazon_profile_data = isset( $sanitized_array['ced_amazon_profile_data'] ) ? ( $sanitized_array['ced_amazon_profile_data'] ) : array();

				$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';

				$profileDetails = array(
					'primary_category'     => isset( $amazon_profile_data['primary_category'] ) ? $amazon_profile_data['primary_category'] : '',
					'secondary_category'   => isset( $amazon_profile_data['secondary_category'] ) ? $amazon_profile_data['secondary_category'] : '',
					'browse_nodes'         => isset( $amazon_profile_data['browse_nodes'] ) ? $amazon_profile_data['browse_nodes'] : '',
					'wocoommerce_category' => isset( $amazon_profile_data['wocoommerce_category'] ) ? $amazon_profile_data['wocoommerce_category'] : '',
					'template_type'        => isset( $amazon_profile_data['template_type'] ) ? $amazon_profile_data['template_type'] : '',
					'file_url'             => isset( $amazon_profile_data['file_url'] ) ? $amazon_profile_data['file_url'] : '',
					'browse_nodes_name'    => isset( $amazon_profile_data['browse_nodes_name'] ) ? $amazon_profile_data['browse_nodes_name'] : '',
					'amazon_categories_name'   => isset( $amazon_profile_data['amazon_categories_name'] ) ?  $amazon_profile_data['amazon_categories_name'] : '',
				);

				$profileDetails['category_attributes_structure'] = wp_json_encode( $amazon_profile_data['ref_attribute_list'] );

				unset( $amazon_profile_data['primary_category'] );
				unset( $amazon_profile_data['secondary_category'] );
				unset( $amazon_profile_data['browse_nodes'] );

				unset( $amazon_profile_data['browse_nodes_name'] );
				unset( $amazon_profile_data['amazon_categories_name'] );

				unset( $amazon_profile_data['ref_attribute_list'] );
				unset( $amazon_profile_data['wocoommerce_category'] );

				unset( $amazon_profile_data['template_type'] );
				unset( $amazon_profile_data['file_url'] );

				$profileDetails['category_attributes_data'] = wp_json_encode( $amazon_profile_data );

				global $wpdb;
				$tableName = $wpdb->prefix . 'ced_amazon_profiles';

				
				$wpdb->insert(
					$tableName,
					array(
						'primary_category'              => $profileDetails['primary_category'],
						'secondary_category'            => $profileDetails['secondary_category'],
						'category_attributes_response'  => '',
						'wocoommerce_category'          => wp_json_encode( $profileDetails['wocoommerce_category'] ),
						'category_attributes_structure' => $profileDetails['category_attributes_structure'],
						'browse_nodes'                  => $profileDetails['browse_nodes'],

						'browse_nodes_name'             => $profileDetails['browse_nodes_name'],
						'amazon_categories_name'        => $profileDetails['amazon_categories_name'],

						'category_attributes_data'      => $profileDetails['category_attributes_data'],
						'seller_id'                     => $seller_id,
						'file_url'                      => $profileDetails['file_url'],
						'template_type'                 => $profileDetails['template_type'],
					),
					array( '%s' )
				);

				$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
				$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
				$seller_id = str_replace( '|', '%7C', $seller_id );
				wp_safe_redirect( admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=templates-view&user_id=' . $user_id . '&seller_id=' . $seller_id );
				exit();

			}
		} 
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';

		global $wpdb;
		$tableName            = $wpdb->prefix . 'ced_amazon_profiles';
		$amazon_profiles      = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles WHERE `seller_id` = %s ", $seller_id ), 'ARRAY_A' );
		$amazon_wooCategories = array();

		if ( ! empty( $amazon_profiles ) ) {
			foreach ( $amazon_profiles as $amazon_profile ) {

				$wooCatIds = json_decode( $amazon_profile['wocoommerce_category'], true );
				if ( ! empty( $wooCatIds ) ) {
					foreach ( $wooCatIds as $wooCatId ) {
						$amazon_wooCategories[] = $wooCatId;
					}
				}
			}
		}

		$ced_amaz_cat_val_array = $this->ced_amz_get_woo_categories();

		$wooUsedCategoriesArray = isset( $ced_amaz_cat_val_array['wooUsedCategoriesArray'] ) ? $ced_amaz_cat_val_array['wooUsedCategoriesArray'] : array() ;
		$allWooCategories       = isset( $ced_amaz_cat_val_array['allWooCategories'] ) ? $ced_amaz_cat_val_array['allWooCategories'] : array() ;

		
		if ( ! empty( $seller_id ) ) {

			?>

				<div class="ced-button-wrapper-top">
					<button type="button" class="components-button is-primary add-new-template-btn" data-woo-used-cat="<?php echo esc_attr( htmlspecialchars( wp_json_encode( $wooUsedCategoriesArray ) ) ); ?>" data-woo-all-cat = "<?php print_r( htmlspecialchars( wp_json_encode( $allWooCategories ) ) ); ?>" >
				<?php echo esc_html__( 'Create new template', 'amazon-for-woocommerce' ); ?>
					</button>

					<!-- <button type="button" class="components-button is-primary ced_amazon_upload_image_button" data-woo-used-cat="<?php echo esc_attr( htmlspecialchars( json_encode( $wooUsedCategoriesArray ) ) ); ?>" data-woo-all-cat = "<?php echo esc_attr( htmlspecialchars( json_encode( $allWooCategories ) ) ); ?>" 
					> Upload Template </button> 

					<span> 
					<?php 
					echo wc_help_tip('Here you can upload your own XLSM feed template, generated on Seller Central.
					<p> </p>
					<b> To generate your own feed templates, log in to Seller Central, visit Inventory / Add Products via Upload and open the "Download an Inventory file" tab.</b>'); 
					?>
							</span> -->

				</div>
				
				<?php

		}

		if ( ! session_id() ) {
			session_start();
		}

		?>

			<!-- Clone modal code starts -->

			<div id="cloneTemplateModal" class="ced-modal">
				<div class="ced-modal-text-content modal-body ced-amz-clone-tmp">

					<div class="ced-amaz-clone-response-modal">
						<div class="modal-body">
							<h2>Clone Template </h2>
							<form action="" method="post">

								<div class="components-card is-size-medium woocommerce-table pinterest-for-woocommerce-landing-page__faq-section css-1xs3c37-CardUI e1q7k77g0">
									<div class="components-panel ced-padding">	
										
										<table class="form-table">
											
											<?php 
												
												$woo_store_categories = ced_amazon_get_categories_hierarchical(
													array(
														'taxonomy'   => 'product_cat',
														'hide_empty' => false,
													)
												);

											?>
											
											<tbody>
												
												<tr>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php esc_attr_e( 'WooCommerce Category', 'amazon-for-woocommerce' ); ?>
															<?php print_r( wc_help_tip( 'Select a WooCommerce category to map with the new template.', 'amazon-for-woocommerce' ) ); ?>
														</label>
													</th>
													<td class="forminp forminp-select">

														<select  class="select2 wooCategories" name="ced_amazon_profile_data[wocoommerce_category][]"  multiple="multiple" >
															<!-- <option value = '' >--Select--</option> -->
															<?php ced_amazon_nestdiv( $woo_store_categories, $this->current_amazon_profile, 0, $amazon_wooCategories ); ?>
														</select>
						
													</td> 
												</tr>

												<tr>
													<td colspan="2">
													<p><i>During the template cloning process, please note that Amazon category and template details will be automatically copied from the selected template.</i></p>
													</td>

												</tr>


											</tbody>
										</table>
										
									</div>
								</div>
								

								<div class="modal-footer" style="float: right; padding: 0px; margin-right: 10px; margin-bottom: 7px;" >
									
									<button type="button" class="components-button is-secondary ced-close-button ced_clone_modal_cancel button-primary woocommerce-save-button ced-cancel" refresh="false" >Close</button>
									<button class="components-button is-primary ced_amazon_clone_template_button"  ><?php esc_attr_e( 'Clone template', 'amazon-integration-for-woocommerce' ); ?></button>
		
								</div>

							
							</form>		

						</div>

					</div>
					

					
				</div>

			</div>


			<!-- Clone modal code ends -->

			<!-- Upload template modal code starts -->

			<div id="uploadTemplateModal" class="ced-modal">
				<div class="ced-modal-text-content modal-body ced-amz-upl-tmp"> 
					<div class="template-response-modal">

						<div class="modal-body">
									
							<form action="" method="post">

								<div class="components-card is-size-medium woocommerce-table pinterest-for-woocommerce-landing-page__faq-section css-1xs3c37-CardUI e1q7k77g0 ced_profile_table">
									<div class="components-panel ced-padding">	
										
										<table class="form-table">
											
												
											<?php
												$amzonCurlRequest = CED_AMAZON_DIRPATH . 'admin/amazon/lib/ced-amazon-curl-request.php';
												$shopId           = get_option( 'ced_amazon_sellernext_shop_id', true );
											if ( file_exists( $amzonCurlRequest ) ) {
												require_once $amzonCurlRequest;
												$amzonCurlRequestInstance = new Ced_Amazon_Curl_Request();
												$amazonCategoryList       = $amzonCurlRequestInstance->ced_amazon_get_category( 'webapi/rest/v1/category/?shop_id=' . $shopId, $user_id, $seller_id );

											}

												$woo_store_categories = ced_amazon_get_categories_hierarchical(
													array(
														'taxonomy'   => 'product_cat',
														'hide_empty' => false,
													)
												);

											?>
											
											<tbody>
												
												<tr>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php esc_attr_e( 'WooCommerce Category', 'amazon-for-woocommerce' ); ?>
															<?php print_r( wc_help_tip( 'Select a WooCommerce category to map with Amazon category.', 'amazon-for-woocommerce' ) ); ?>
														</label>
													</th>
													<td class="forminp forminp-select">

														<select  class="select2 wooCategories" name="ced_amazon_profile_data[wocoommerce_category][]"  multiple="multiple" >
															<!-- <option>--Select--</option> -->
															<?php ced_amazon_nestdiv( $woo_store_categories, $this->current_amazon_profile, 0, $amazon_wooCategories ); ?>
														</select>
						
													</td> 
												</tr>


												<tr>
													<th scope="row" class="titledesc">
														<label for="woocommerce_currency">
															<?php esc_attr_e( 'Amazon Category', 'amazon-for-woocommerce' ); ?> 
															<!-- <?php print_r( wc_help_tip( 'Choose an Amazon category to which you want to upload products.', 'amazon-for-woocommerce' ) ); ?> -->
														</label>
													</th>
													<td  >
														<!-- <input type="hidden" class="ced_primary_category" name="ced_amazon_profile_data[primary_category]" value="<?php echo esc_attr( $current_amazon_profile['primary_category'] ); ?>" />
														<input type="hidden" class="ced_secondary_category" name="ced_amazon_profile_data[secondary_category]" value="<?php echo esc_attr( $current_amazon_profile['secondary_category'] ); ?>" /> -->

														<input type="hidden" class="ced_browse_category" name="ced_amazon_profile_data[browse_nodes]" value="" />
														<input type="hidden" class="ced_browse_node_name" name="ced_amazon_profile_data[browse_nodes_name]" value="" />
														<input type="hidden" class="ced_amz_cat_name_arr" name="ced_amazon_profile_data[amazon_categories_name]" value="" > 
															
														<?php
															// $categoryArray = array(
															//  'primary_category' => isset( $current_amazon_profile['primary_category'] ) ? $current_amazon_profile['primary_category'] : '',
															//  'secondary_category' => isset( $current_amazon_profile['secondary_category'] ) ? $current_amazon_profile['secondary_category'] : '',
															//  'browse_nodes' => isset( $current_amazon_profile['browse_nodes'] ) ? $current_amazon_profile['browse_nodes'] : '',
															//  'browse_nodes_name' => isset( $current_amazon_profile['browse_nodes_name'] ) ? $current_amazon_profile['browse_nodes_name'] : '',
															// );

															// $amazonCategories = '';
															// if ( ! empty( $current_amazon_profile['amazon_categories_name'] ) ) {
															//  $amazonCategories = $current_amazon_profile['amazon_categories_name'];
															// }

														?>
														<p class="ced_amz_cat_name" 
														data-category=""
														> </p>
													</td>

												</tr>


											</tbody>
										</table>



										<div class="components-card is-size-medium woocommerce-table pinterest-for-woocommerce-landing-page__faq-section css-1xs3c37-CardUI e1q7k77g0">
											<div class="components-panel">
												<div class="wc-progress-form-content woocommerce-importer">

													<div class="ced-faq-wrapper ced-margin-border">
													<input class="ced-faq-trigger" id="ced-faq-wrapper-one" type="checkbox" checked /><label class="ced-faq-title" for="ced-faq-wrapper-one"> <?php echo esc_attr_e( 'Template Fields', 'amazon-for-woocommerce' ); ?></label>
													<div class="ced-faq-content-wrap ced-amz-upl-tmp-modal">
														<div class="ced-faq-content-holder">
															<table class = "upload-template-response-modal wp-list-table widefat fixed table-view-list ced-table-filed form-table" >
																
																<tbody class="ced_template_required_attributes" >
																</tbody>
															</table>
														
														</div>
													</div>
													</div>
													
													
												</div>
											</div>
										</div>

										
									</div>
								</div>
								
								

								<div class="modal-footer" style="float: right;" >
									<?php wp_nonce_field( 'ced_amazon_profile_edit_page_nonce', 'ced_amazon_profile_edit' ); ?>
									<button type="button" class="components-button is-secondary ced-close-button ced_template_cancel button-primary woocommerce-save-button ced-cancel" >Close</button>
									<button class="components-button is-primary save_profile_button" name="ced_amazon_profile_save_button" ><?php esc_attr_e( 'Save Profile Data', 'amazon-integration-for-woocommerce' ); ?></button>
		
								</div>

								<!-- <div class="modal-footer">
									<?php wp_nonce_field( 'ced_amazon_profile_edit_page_nonce', 'ced_amazon_profile_edit' ); ?>
									<span class="ced-close-button ced_template_cancel button-primary woocommerce-save-button ced-cancel"><?php echo esc_html( 'Close', 'amazon-for-woocommerce' ); ?></span>
									<buttom class="ced-close-button save_profile_button button-primary woocommerce-save-button ced-cancel"  name="ced_amazon_profile_save_button" ><?php echo esc_html( 'Save Profile Data', 'amazon-for-woocommerce' ); ?></buttom>
								</div> -->

							</form>		

						</div>

					</div>
					

					
				</div>

			</div>
		
			

		   <!-- Upload template modal code ends -->


				<div id="post-body" class="metabox-holder columns-2">
					<div id="">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								wp_nonce_field( 'amazon_profile_view', 'amazon_profile_view_actions' );
								$this->display();
								?>
							</form>
						</div>
					</div>


					<div class="clear"></div>
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
			if ( ! isset( $_POST['amazon_profile_view_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['amazon_profile_view_actions'] ) ), 'amazon_profile_view' ) ) {
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

		wp_nonce_field( 'ced_amazon_profiles_view_page_nonce', 'ced_amazon_profiles_view_nonce' );

		$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';

		if ( 'bulk-delete' === $this->current_action() || ( isset( $_GET['action'] ) && 'bulk-delete' === $_GET['action'] ) ) {
			if ( ! isset( $_POST['amazon_profile_view_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['amazon_profile_view_actions'] ) ), 'amazon_profile_view' ) ) {
				return;
			}
			$profileIds = isset( $sanitized_array['amazon_profile_ids'] ) ? $sanitized_array['amazon_profile_ids'] : array();

			if ( is_array( $profileIds ) && ! empty( $profileIds ) ) {

				global $wpdb;
				
				$seller_id_array = explode( '|', $seller_id );
				$country         = isset( $seller_id_array[0] ) ? $seller_id_array[0] : '';

				foreach ( $profileIds as $index => $pid ) {

					$product_ids_assigned = get_option( 'ced_amazon_product_ids_in_profile_' . $pid, array() );
					foreach ( $product_ids_assigned as $index => $ppid ) {
						delete_post_meta( $ppid, 'ced_amazon_profile_assigned' . $user_id );
					}

					$term_id = $wpdb->get_results( $wpdb->prepare( "SELECT `wocoommerce_category` FROM {$wpdb->prefix}ced_amazon_profiles WHERE `id` = %s ", $pid ), 'ARRAY_A' );
					$term_id = json_decode( $term_id[0]['wocoommerce_category'], true );
					foreach ( $term_id as $key => $value ) {
						delete_term_meta( $value, 'ced_amazon_profile_created_' . $user_id );
						delete_term_meta( $value, 'ced_amazon_profile_id_' . $user_id );
						delete_term_meta( $value, 'ced_amazon_mapped_category_' . $user_id );
					}
				}

				foreach ( $profileIds as $id ) {
					$ced_woo_amazon_mapping   = get_option( 'ced_woo_amazon_mapping', array() );
					$ced_woo_amazon_cat_array = isset( $ced_woo_amazon_mapping[ $seller_id ] ) ? $ced_woo_amazon_mapping[ $seller_id ] : array();

					if ( ! empty( $ced_woo_amazon_cat_array ) && is_array( $ced_woo_amazon_cat_array ) ) {

						unset( $ced_woo_amazon_mapping[ $seller_id ][ $id ] );
						update_option( 'ced_woo_amazon_mapping', $ced_woo_amazon_mapping );

					}

					$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_amazon_profiles WHERE `id` IN (%s)", $id ) );

				}

				header( 'Location: ' . get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=templates-view&user_id=' . esc_attr( $user_id ) . '&seller_id=' . esc_attr( $seller_id ) );
				exit();
			} else {

				$seller_id = str_replace( '|', '%7C', $seller_id );
				wp_safe_redirect( admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=templates-view&user_id=' . $user_id . '&seller_id=' . $seller_id );
				exit();

			}

		} elseif ( isset( $_GET['panel'] ) && 'edit' == $_GET['panel'] ) {

			$file = CED_AMAZON_DIRPATH . 'admin/partials/profile-edit-view.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		} else {

			$seller_id = str_replace( '|', '%7C', $seller_id );
			wp_safe_redirect( admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=templates-view&user_id=' . $user_id . '&seller_id=' . $seller_id );
			exit();

		}
	}
}

	$ced_amazon_profile_obj = new Ced_amazon_Profile_Table();
	$ced_amazon_profile_obj->prepare_items();


?>


<script>
	

	jQuery(document).ready(function() {
		jQuery('.ced_amazon_select_category').selectWoo();
		jQuery(".wooCategories").selectWoo({
			dropdownPosition: 'below',
			dropdownAutoWidth : true,
			allowClear: true,
			placeholder: '--Select--',
			width: '100%'
		});
	});

</script>
