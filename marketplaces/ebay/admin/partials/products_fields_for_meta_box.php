<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Function- Product Fields.
 * Used to add product Fields

 * @since      1.0.0
 *
 * @package    eBay Integration for Woocommerce
 * @subpackage eBay Integration for Woocommerce/admin/helper
 */

if ( ! class_exists( 'CedeBayProductsFieldsMetaBox' ) ) {

	/**
	 * Single product related functionality.
	 *
	 * Manage all single product related functionality required for listing product on marketplaces.
	 *
	 * @since      1.0.0
	 * @package    eBay Integration for Woocommerce
	 * @subpackage eBay Integration for Woocommerce/admin/helper
	 */
	class CedeBayProductsFieldsMetaBox {


		/**
		 * The Instace of CED_ebay_product_fields.
		 *
		 * @since    1.0.0
		 * @var      $_instance   The Instance of CED_ebay_product_fields class.
		 */
		private static $_instance;

		/**
		 * CED_ebay_product_fields Instance.
		 *
		 * Ensures only one instance of CED_ebay_product_fields is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 * @static
		 * @return CED_ebay_product_fields instance.
		 */
		public static function get_instance() {

			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}



		public function ced_ebay_get_profile_framework_specific( $user_id, $site_id ) {
			$ebaySpecificFields = array(
				array(
					'type'   => '_text_input',
					'id'     => '_umb_ebay_ean_' . $user_id . '>' . $site_id,
					'fields' => array(
						'id'          => '_umb_ebay_ean_' . $user_id . '>' . $site_id,
						'global_id'   => 'EAN',
						'label'       => __( 'EAN', 'ced-umb-ebay' ),
						'desc_tip'    => true,
						'description' => __( 'EAN is a unique 8 or 13 digit identifier used to identify products.', 'ced-umb-ebay' ),
						'type'        => 'text',
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_text_input',

					'id'     => '_umb_ebay_isbn_' . $user_id . '>' . $site_id,
					'fields' => array(
						'id'          => '_umb_ebay_isbn_' . $user_id . '>' . $site_id,
						'global_id'   => 'ISBN',
						'label'       => __( 'ISBN', 'ced-umb-ebay' ),
						'desc_tip'    => true,
						'description' => __( 'ISBN is a unique identifer for books (an international standard). Specify a 10 or 13-character ISBN.', 'ced-umb-ebay' ),
						'type'        => 'text',
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_text_input',
					'id'     => '_umb_ebay_upc_' . $user_id . '>' . $site_id,
					'fields' => array(
						'id'          => '_umb_ebay_upc_' . $user_id . '>' . $site_id,
						'global_id'   => 'UPC',
						'label'       => __( 'UPC', 'ced-umb-ebay' ),
						'desc_tip'    => true,
						'description' => __( 'UPC is a unique, 12-character identifier that many industries use to identify products.', 'ced-umb-ebay' ),
						'type'        => 'text',
						'class'       => 'wc_input_price',
					),
				),
			);

			return $ebaySpecificFields;
		}

		/**
		 *
		 * Function for render dropdown html
		 */
		public function renderDropdownHTML( $user_id, $site_id, $attribute_id, $attribute_name, $values, $categoryID, $productID, $marketPlace, $indexToUse, $additionalInfo = array( 'case' => 'product' ), $is_required = '', $cardinality = 'SINGLE', $field_description = '' ) {
			if ( 'MULTI' == $cardinality ) {
				$this->renderMultipleFieldHTML( $user_id, $site_id, $attribute_id, $attribute_name, $categoryID, $productID, $marketPlace, $indexToUse, $additionalInfo, $is_required, $field_description );
				return;
			}

			$fieldName = $categoryID . '_' . $attribute_id;
			if ( 'product' == $additionalInfo['case'] ) {
				$previousValue = get_post_meta( $productID, $fieldName, true );
			} else {
				$previousValue = $additionalInfo['value'];
			}

			?><input type="hidden" name="<?php echo esc_attr( $marketPlace ) . '[]'; ?>" value="<?php echo esc_attr( $fieldName ); ?>" />

			<td>
				<label class="ced_ebay_show_tippy" data-tippy-content="<?php echo esc_attr( $field_description ); ?>"><?php echo esc_attr( $attribute_name ); ?>
				<?php
				if ( 'required' == $is_required ) {
					?>
					<span class="ced_ebay_wal_required"><?php echo esc_attr( '[Required]' ); ?></span>
					<?php
				}
				if ( 'MULTI' == $cardinality ) {
					?>
					<span style="color:green;"><?php echo esc_attr( '[Multiple]' ); ?></span>
					<?php
				}
				?>
			</label>
			</td>
			<td>
				<select class="ced_ebay_item_specifics_options" name="<?php echo esc_attr( $fieldName ) . '[' . esc_attr( $indexToUse ) . ']'; ?>" class="select short" style="">
				<?php
				echo '<option value="">' . esc_attr( '-- Select --' ) . '</option>';
				foreach ( $values as $key => $value ) {
					if ( $previousValue == $key ) {
						echo '<option value="' . esc_attr( $key ) . '" selected>' . esc_attr( $value ) . '</option>';
					} else {
						echo '<option value="' . esc_attr( $key ) . '">' . esc_attr( $value ) . '</option>';
					}
				}
				?>
			</select>
			</td>


			<?php
		}

		/**
		 *
		 * Function to render input fields
		 */

		public function renderInputTextHTML( $user_id, $site_id, $attribute_id, $attribute_name, $categoryID, $productID, $marketPlace, $indexToUse, $additionalInfo = array( 'case' => 'product' ), $conditionally_required = false, $cardinality = 'SINGLE', $field_description = '', $global_value = '' ) {

			if ( 'MULTI' == $cardinality ) {
				$this->renderMultipleFieldHTML( $user_id, $site_id, $attribute_id, $attribute_name, $categoryID, $productID, $marketPlace, $indexToUse, $additionalInfo, $conditionally_required, $field_description );
				return;
			}

			global $post, $product, $loop;
			$fieldName = $categoryID . '_' . $attribute_id;
			if ( 'product' == $additionalInfo['case'] ) {
				$previousValue = get_post_meta( $productID, $fieldName, true );
			} else {
				$previousValue = $additionalInfo['value'];
			}

			if ( 'required' == $conditionally_required ) {
				$required_item_specific = true;
			} else {
				$required_item_specific = false;
			}
			if ( $required_item_specific ) {
				$fieldNameRequired  = $fieldName;
				$fieldNameRequired .= '_required';
			}

			?>

			<input type="hidden" name="<?php echo esc_attr( $marketPlace ) . '[]'; ?>" value="<?php echo esc_attr( ! empty( $fieldNameRequired ) ? $fieldNameRequired : $fieldName ); ?>" />
			<td>
				<?php
				if ( ! empty( $field_description ) ) {
					?>
						<label class="ced_ebay_show_tippy" data-tippy-content="<?php echo esc_attr( $field_description ); ?>">
						<?php
				} else {

					?>
					<label> <?php } ?>

				<?php echo esc_attr( $attribute_name ); ?>
				<?php
				if ( 'required' == $conditionally_required ) {
					$required_item_specific = true;
					?>
					<span class="ced_ebay_wal_required"><?php echo esc_attr( '[Required]' ); ?></span>
					<?php
				} else {
					$required_item_specific = false;
				}

				if ( 'MULTI' == $cardinality ) {
					?>
					<span style="color:green;"><?php echo esc_attr( '[Multiple]' ); ?></span>
					<?php
				}

				?>
			</label>
			</td>
			<!-- style="display: block; margin-bottom: 25px;" -->
			<td>
				<!-- <label ></label> -->
				
				<?php

					$selectValueExists   = 0;
					$profile_category_id = $categoryID;

					$wp_folder     = wp_upload_dir();
					$wp_upload_dir = $wp_folder['basedir'];
					$wp_upload_dir = $wp_upload_dir . '/ced-ebay/category-specifics/' . $user_id . '/' . $site_id . '/';

					$cat_specifics_file = $wp_upload_dir . 'ebaycat_' . $profile_category_id . '.json';
					$option             = '';

				if ( file_exists( $cat_specifics_file ) ) {

					$available_cat_specifics = json_decode( file_get_contents( $cat_specifics_file ), true );
					if ( ! empty( $available_cat_specifics ) ) {
						foreach ( $available_cat_specifics as $available_cat ) {
							if ( isset( $available_cat['localizedAspectName'] ) && $attribute_name == $available_cat['localizedAspectName'] ) {
								if ( isset( $available_cat['aspectValues'] ) ) {
									$recommendedValuesArray = $available_cat['aspectValues'];
									if ( ! empty( $recommendedValuesArray ) ) {
										foreach ( $recommendedValuesArray as $recommendedValues ) {
											$selected = '';
											if ( $recommendedValues['localizedValue'] == $previousValue ) {
												$selected          = 'selected';
												$selectValueExists = 1;
											}

											$option .= '<option ' . $selected . ' value="' . $recommendedValues['localizedValue'] . '" >' . $recommendedValues['localizedValue'] . '</option>';

										}
									}
								}
							}
						}
					}
				}

				if ( empty( $previousValue ) ) {
					$displaySelectbox = 'display:block; float: left;';
					$displayInputbox  = 'display:none;';
					$disabled         = 'input';
				} elseif ( $selectValueExists ) {

						$displaySelectbox = 'display:block; float: left;';
						$displayInputbox  = 'display:none;';
						$disabled         = 'input';

				} else {
					$displaySelectbox = 'display:none;';
					$displayInputbox  = 'display:block; float: left;';
					$disabled         = 'select';
				}

				if ( ! empty( $option ) ) {
					?>
					<select 
						<?php
						if ( 'select' == $disabled ) {
							echo esc_attr( 'disabled="disabled"' );}
						?>
						style="<?php echo esc_attr( $displaySelectbox ); ?>" name="<?php echo esc_attr( $fieldName ) . '[' . esc_attr( $indexToUse ) . ']'; ?>" class="ced_ebay_fill_custom_value" >
							<option value=''></option>
							<option value="customOption">[Enter a custom value]</option>
							<optgroup label="Recommended" >
								<?php

								$allowedHtml = array(

									'select'   => array(

										'value' => array(),
										'name'  => array(),
										'class' => array(),
										'id'    => array(),

									),
									'option'   => array(

										'value'    => array(),
										'name'     => array(),
										'class'    => array(),
										'id'       => array(),
										'selected' => array(),

									),
									'optgroup' => array(

										'value' => array(),
										'name'  => array(),
										'class' => array(),
										'id'    => array(),

									),

								);

								echo wp_kses( $option, $allowedHtml );
								// echo htmlspecialchars_decode( $option );
								?>
							</optgroup>
					</select>

					<input 
						<?php
						if ( 'input' == $disabled ) {
							echo esc_attr( 'disabled="disabled"' );}
						?>
						name="<?php echo esc_attr( $fieldName ) . '[' . esc_attr( $indexToUse ) . ']'; ?>" id="" value="<?php echo esc_attr( $previousValue ); ?>"
						style="<?php echo esc_attr( $displayInputbox ); ?>" class="short ced_ebay_type_custom_value" placeholder="" type="text" value="<?php echo esc_attr( $previousValue ); ?>" >

					<!-- <span style="padding-left:10px;font-size:18px;color:#5850ec;padding-top: 12px;display: inline-block;"><b>Or</b></span> -->
 
				<?php } else { ?>

					<input class="short ced_ebay_item_specifics_text" name="<?php echo esc_attr( $fieldName ) . '[' . esc_attr( $indexToUse ) . ']'; ?>" id="" value="<?php echo esc_attr( $previousValue ); ?>" placeholder="" type="text" />
			
				<?php } ?>
			</td>


			<?php
		}

		public static function ced_ebay_get_custom_products_fields( $user_id, $categoryID, $site_id ) {
			global $post;
			$upload_dir = wp_upload_dir();

			$templates_dir = $upload_dir['basedir'] . '/ced-ebay/templates/';

			$templates = array();
			$files     = glob( $upload_dir['basedir'] . '/ced-ebay/templates/*/template.html' );
			if ( is_array( $files ) ) {
				foreach ( $files as $file ) {
					$file     = basename( dirname( $file ) );
					$fullpath = $templates_dir . $file;

					if ( file_exists( $fullpath . '/info.txt' ) ) {
						$template_header       = array(
							'Template' => 'Template',
						);
						$template_data         = get_file_data( $fullpath . '/info.txt', $template_header, 'theme' );
						$item['template_name'] = $template_data['Template'];
					}
					$template_id                                = basename( $fullpath );
					$templates[ $template_id ]['template_name'] = $item['template_name'];
				}
			}

			$wp_folder               = wp_upload_dir();
			$wp_upload_dir           = $wp_folder['basedir'];
			$wp_upload_dir           = $wp_upload_dir . '/ced-ebay/category-specifics/' . $user_id . '/' . $site_id . '/';
			$best_offer_enabled      = false;
			$cat_best_offer_override = false;
			$categoryID              = sanitize_text_field( $categoryID );
			$cat_features_file       = $wp_upload_dir . 'ebaycatfeatures_' . sanitize_file_name( $categoryID ) . '.json';
			if ( file_exists( $cat_features_file ) ) {
				$available_cat_features = json_decode( file_get_contents( $cat_features_file ), true );
				if ( ! empty( $available_cat_features ) ) {
					if ( ! empty( $available_cat_features['Category']['BestOfferEnabled'] ) && ! empty( $available_cat_features['Category']['BestOfferAutoAcceptEnabled'] ) && ! empty( $available_cat_features['Category']['BestOfferAutoDeclineEnabled'] ) ) {
						$best_offer_enabled      = true;
						$cat_best_offer_override = true;
					}
					if ( ! $cat_best_offer_override && ! empty( $available_cat_features['SiteDefaults']['BestOfferEnabled'] ) && ! empty( $available_cat_features['SiteDefaults']['BestOfferAutoAcceptEnabled'] ) && ! empty( $available_cat_features['SiteDefaults']['BestOfferAutoDeclineEnabled'] ) ) {
						$best_offer_enabled = true;
					}
				}
			}
			foreach ( $templates as $key => $value ) {
				$description_template_name[ $key ] = $value['template_name'];
			}
			$business_policies = ced_ebay_get_business_policies( $user_id, $site_id );
			if ( ! empty( $business_policies ) && is_array( $business_policies ) && isset( $business_policies['paymentPolicies'] ) && isset( $business_policies['fulfillmentPolicies'] ) && isset( $business_policies['returnPolicies'] ) ) {
				$payment_policy_options     = array();
				$return_policy_options      = array();
				$fulfillment_policy_options = array();
				foreach ( $business_policies as $gKey => $policies ) {
					$nameForPolicyIdKey  = str_replace( 'Policies', '', $gKey );
					$nameForPolicyIdKey .= 'PolicyId';
					if ( isset( $policies[ $gKey ] ) ) {
						if ( 'paymentPolicies' == $gKey ) {
							foreach ( $policies[ $gKey ] as $xKey => $payment_policies ) {
								$payment_policy_options[] = array(
									$payment_policies[ $nameForPolicyIdKey ] . '|' . $payment_policies['name'] => $payment_policies['name'],
								);
							}
						}
						if ( 'returnPolicies' == $gKey ) {
							foreach ( $policies[ $gKey ] as $xKey => $return_policies ) {
								$return_policy_options[] = array(
									$return_policies[ $nameForPolicyIdKey ] . '|' . $return_policies['name'] => $return_policies['name'],
								);
							}
						}
						if ( 'fulfillmentPolicies' == $gKey ) {
							foreach ( $policies[ $gKey ] as $xKey => $fulfillment_policies ) {
								$fulfillment_policy_options[] = array(
									$fulfillment_policies[ $nameForPolicyIdKey ] . '|' . $fulfillment_policies['name'] => $fulfillment_policies['name'],
								);
							}
						}
					}
				}
				if ( ! empty( $payment_policy_options ) && ! empty( $return_policy_options ) && ! empty( $fulfillment_policy_options ) ) {
					$payment_policy_options     = array_merge( ...$payment_policy_options );
					$return_policy_options      = array_merge( ...$return_policy_options );
					$fulfillment_policy_options = array_merge( ...$fulfillment_policy_options );
				}

				$payment_policy_dropdown     = array(
					'type'   => '_select',
					'id'     => '_umb_ebay_payment_policy_' . $user_id . '>' . $site_id,
					'fields' => array(
						'id'          => '_umb_ebay_payment_policy_' . $user_id . '>' . $site_id,
						'label'       => __( 'Payment Policy', 'ced-umb-ebay' ),
						'description' => __( 'ADASSADDSA.', 'ebay-integration-for-woocommerce' ),
						'options'     => $payment_policy_options,
						'desc_tip'    => true,
					),
				);
				$return_policy_dropdown      = array(
					'type'   => '_select',
					'id'     => '_umb_ebay_return_policy_' . $user_id . '>' . $site_id,
					'fields' => array(
						'id'          => '_umb_ebay_return_policy_' . $user_id . '>' . $site_id,
						'label'       => __( 'Return Policy', 'ced-umb-ebay' ),
						'description' => __( 'ADASSADDSA.', 'ebay-integration-for-woocommerce' ),
						'options'     => $return_policy_options,
						'desc_tip'    => true,
					),
				);
				$fulfillment_policy_dropdown = array(
					'type'   => '_select',
					'id'     => '_umb_ebay_fulfillment_policy_' . $user_id . '>' . $site_id,
					'fields' => array(
						'id'          => '_umb_ebay_fulfillment_policy_' . $user_id . '>' . $site_id,
						'label'       => __( 'Fulfillment Policy', 'ced-umb-ebay' ),
						'description' => __( 'ADASSADDSA.', 'ebay-integration-for-woocommerce' ),
						'options'     => $fulfillment_policy_options,
						'desc_tip'    => true,
					),
				);

			}

			if ( $best_offer_enabled ) {
				$required_fields = array(
					array(
						'type'   => '_text_input',
						'id'     => '_umb_ebay_listing_title_' . $user_id . '>' . $site_id,
						'fields' => array(
							'id'          => '_umb_ebay_listing_title_' . $user_id . '>' . $site_id,
							'label'       => __( 'eBay Title', 'ced-umb-ebay' ),
							'desc_tip'    => true,
							'type'        => 'text',
							'description' => 'Specify a custom title for your eBay listing',
						),
					),
					array(
						'type'   => '_select',
						'id'     => '_umb_ebay_description_template_' . $user_id . '>' . $site_id,
						'fields' => array(
							'id'          => '_umb_ebay_description_template_' . $user_id . '>' . $site_id,
							'label'       => __( 'eBay Product Description Template', 'ced-umb-ebay' ),
							'options'     => ! empty( $description_template_name ) ? $description_template_name : array(),
							'desc_tip'    => true,
							'description' => __( 'Assign a custom description template to your eBay Listings.', 'ced-umb-ebay' ),
						),
					),
					array(
						'type'   => '_select',
						'id'     => '_umb_ebay_profile_price_markup_type_' . $user_id . '>' . $site_id,
						'fields' => array(
							'id'          => '_umb_ebay_profile_price_markup_type_' . $user_id . '>' . $site_id,
							'label'       => __( 'eBay Product Markup Type', 'ced-umb-ebay' ),
							'description' => __( 'Select the type of Price Increase or Decrease of your products on eBay.', 'ebay-integration-for-woocommerce' ),
							'options'     => array(
								'Fixed_Increase'      => __( 'Fixed Increase', 'ced-umb-ebay' ),
								'Fixed_Decrease'      => __( 'Fixed Decrease', 'ced-umb-ebay' ),
								'Percentage_Increase' => __( 'Percentage Increase', 'ced-umb-ebay' ),
								'Percentage_Decrease' => __( 'Percentage Decrease', 'ced-umb-ebay' ),
							),
							'desc_tip'    => true,
						),
					),
					array(
						'type'   => '_text_input',
						'id'     => '_umb_ebay_profile_price_markup_' . $user_id . '>' . $site_id,
						'fields' => array(
							'id'          => '_umb_ebay_profile_price_markup_' . $user_id . '>' . $site_id,
							'label'       => __( 'Markup Price', 'ced-umb-ebay' ),
							'desc_tip'    => true,
							'type'        => 'number',
							'description' => 'Specify by how much the product price will increase or decrease.',
						),
					),
					array(
						'type'   => '_select',
						'id'     => '_umb_ebay_bestoffer_' . $user_id . '>' . $site_id,
						'fields' => array(
							'id'          => '_umb_ebay_bestoffer_' . $user_id . '>' . $site_id,
							'label'       => __( 'Enable Best Offer?', 'ced-umb-ebay' ),
							'options'     => array(
								'Yes' => __( 'YES', 'ced-umb-ebay' ),
								'No'  => __( 'NO', 'ced-umb-ebay' ),
							),
							'desc_tip'    => true,
							'description' => 'Allows buyers to send you their best offers for your consideration',
						),
					),

					array(
						'type'   => '_text_input',
						'id'     => '_umb_ebay_auto_accept_offers_' . $user_id . '>' . $site_id,
						'fields' => array(
							'id'          => '_umb_ebay_auto_accept_offers_' . $user_id . '>' . $site_id,
							'label'       => __( 'Automatically accept offers of at least', 'ced-umb-ebay' ),
							'desc_tip'    => true,
							'description' => __( 'Auto Accept Offer.', 'ced-umb-ebay' ),
							'type'        => 'number',
						),
					),

					array(
						'type'   => '_text_input',
						'id'     => '_umb_ebay_auto_decline_offers_' . $user_id . '>' . $site_id,
						'fields' => array(
							'id'          => '_umb_ebay_auto_decline_offers_' . $user_id . '>' . $site_id,
							'label'       => __( 'Automatically decline offers lower than', 'ced-umb-ebay' ),
							'desc_tip'    => true,
							'description' => __( 'Auto Decline Offer.', 'ced-umb-ebay' ),
							'type'        => 'number',
						),
					),
					isset( $payment_policy_dropdown ) ? $payment_policy_dropdown : array(),
					isset( $return_policy_dropdown ) ? $return_policy_dropdown : array(),
					isset( $fulfillment_policy_dropdown ) ? $fulfillment_policy_dropdown : array(),

				);
			} else {
				$required_fields = array(

					array(
						'type'   => '_select',
						'id'     => '_umb_ebay_description_template_' . $user_id . '>' . $site_id,
						'fields' => array(
							'id'          => '_umb_ebay_description_template_' . $user_id . '>' . $site_id,
							'label'       => __( 'eBay Product Description Template', 'ced-umb-ebay' ),
							'options'     => ! empty( $description_template_name ) ? $description_template_name : array(),
							'desc_tip'    => true,
							'description' => __( 'Assign a custom description template your eBay Listings.', 'ced-umb-ebay' ),
						),
					),
					array(
						'type'   => '_select',
						'id'     => '_umb_ebay_profile_price_markup_type_' . $user_id . '>' . $site_id,
						'fields' => array(
							'id'          => '_umb_ebay_profile_price_markup_type_' . $user_id . '>' . $site_id,
							'label'       => __( 'eBay Product Markup Type', 'ced-umb-ebay' ),
							'description' => __( 'Select the type of Price Increase or Decrease of your products on eBay.', 'ebay-integration-for-woocommerce' ),
							'options'     => array(
								'Fixed_Increase'      => __( 'Fixed Increase', 'ced-umb-ebay' ),
								'Fixed_Decrease'      => __( 'Fixed Decrease', 'ced-umb-ebay' ),
								'Percentage_Increase' => __( 'Percentage Increase', 'ced-umb-ebay' ),
								'Percentage_Decrease' => __( 'Percentage Decrease', 'ced-umb-ebay' ),
							),
							'desc_tip'    => true,
						),
					),
					array(
						'type'   => '_text_input',
						'id'     => '_umb_ebay_profile_price_markup_' . $user_id . '>' . $site_id,
						'fields' => array(
							'id'          => '_umb_ebay_profile_price_markup_' . $user_id . '>' . $site_id,
							'label'       => __( 'Markup Price', 'ced-umb-ebay' ),
							'description' => __( 'Specify by how much the product price will increase or decrease.', 'ebay-integration-for-woocommerce' ),
							'desc_tip'    => true,
							'type'        => 'number',
						),
					),
					isset( $payment_policy_dropdown ) ? $payment_policy_dropdown : array(),
					isset( $return_policy_dropdown ) ? $return_policy_dropdown : array(),
					isset( $fulfillment_policy_dropdown ) ? $fulfillment_policy_dropdown : array(),

				);
			}

			return $required_fields;
		}

		/**
		 *
		 * Function to render hidden input fields
		 */
		public function renderInputTextHTMLhidden( $user_id, $site_id, $attribute_id, $attribute_name, $categoryID, $productID, $marketPlace, $indexToUse, $additionalInfo = array( 'case' => 'product' ), $conditionally_required = false, $field_description = '' ) {
			global $post, $product, $loop;
			$fieldName = $categoryID . '_' . $attribute_id;
			if ( 'product' == $additionalInfo['case'] ) {
				$previousValue = get_post_meta( $productID, $fieldName, true );
			} else {
				$previousValue = $additionalInfo['value'];
			}

			?>

				<input type="hidden" name="<?php echo esc_attr( $marketPlace ) . '[]'; ?>" value="<?php echo esc_attr( $fieldName ); ?>" />
			<td>
			</td>
						<td>
				<input class="short" style="" name="<?php echo esc_attr( $fieldName ) . '[' . esc_attr( $indexToUse ) . ']'; ?>" id="" value="<?php echo esc_attr( $previousValue ); ?>" placeholder="" type="hidden" />
			</td>


			<?php
		}

		/**
		 *
		 * Function to render Multiple Field HTML
		 */
		public function renderMultipleFieldHTML( $user_id, $site_id, $attribute_id, $attribute_name, $categoryID, $productID, $marketPlace, $indexToUse, $additionalInfo = array( 'case' => 'product' ), $is_required = '', $field_description = '' ) {

			$fieldName = $categoryID . '_' . $attribute_id;
			if ( 'product' == $additionalInfo['case'] ) {
				$previousValue = get_post_meta( $productID, $fieldName, true );
			} else {
				$previousValue = $additionalInfo['value'];
			}

			// echo 'previousValue' . print_r($previousValue);

			$selectValueExists   = 0;
			$profile_category_id = $categoryID;

			$wp_folder     = wp_upload_dir();
			$wp_upload_dir = $wp_folder['basedir'];
			$wp_upload_dir = $wp_upload_dir . '/ced-ebay/category-specifics/' . $user_id . '/' . $site_id . '/';

			$cat_specifics_file = $wp_upload_dir . 'ebaycat_' . $profile_category_id . '.json';
			$option             = '';
			$customOptions      = $previousValue;

			if ( file_exists( $cat_specifics_file ) ) {

				$available_cat_specifics = json_decode( file_get_contents( $cat_specifics_file ), true );
				if ( ! empty( $available_cat_specifics ) ) {
					foreach ( $available_cat_specifics as $available_cat ) {
						if ( isset( $available_cat['localizedAspectName'] ) && $attribute_name == $available_cat['localizedAspectName'] ) {
							if ( isset( $available_cat['aspectValues'] ) ) {
								$recommendedValuesArray = $available_cat['aspectValues'];
								if ( ! empty( $recommendedValuesArray ) ) {
									foreach ( $recommendedValuesArray as $recommendedValues ) {
										$selected = '';
										if ( $recommendedValues['localizedValue'] == $previousValue ) {
											$selected          = 'selected';
											$selectValueExists = 1;
										} elseif ( 'array' == gettype( $previousValue ) && in_array( $recommendedValues['localizedValue'], $previousValue ) ) {
											$selected = 'selected';
											$option  .= '<option ' . $selected . ' value="' . $recommendedValues['localizedValue'] . '" >' . $recommendedValues['localizedValue'] . '</option>';
											unset( $customOptions[ $recommendedValues['localizedValue'] ] );
											if ( array_search( $recommendedValues['localizedValue'], $customOptions ) !== false ) {
												$key = array_search( $recommendedValues['localizedValue'], $customOptions );
												unset( $customOptions[ $key ] );
											}
										} else {
											$option .= '<option  value="' . $recommendedValues['localizedValue'] . '" >' . $recommendedValues['localizedValue'] . '</option>';

										}
									}
								}
							}
						}
					}
				}

				if ( ! empty( $customOptions ) && is_array( $customOptions ) ) {
					foreach ( $customOptions as $customOption ) {
						$option .= '<option selected value="' . $customOption . '" >' . ucfirst( $customOption ) . '</option>';

					}
				}
			}

			?>
			<input type="hidden" name="<?php echo esc_attr( $marketPlace ) . '[]'; ?>" value="<?php echo esc_attr( $fieldName ); ?>" />

			<td> 
				<label class="ced_ebay_show_tippy" data-tippy-content="<?php echo esc_attr( $field_description ); ?>"><?php echo esc_attr( $attribute_name ); ?>
					
					<span style="color:green;"><?php echo esc_attr( '[Multiple]' ); ?></span>
					
				</label>
			</td>

			<td>
				<select class="ced_ebay_multi_select_item_aspects" name="<?php echo esc_attr( $fieldName ) . '[' . esc_attr( $indexToUse ) . '][]'; ?>" multiple >
					<optgroup label="Recommended" >
						<?php

							$allowedHtml = array(

								'select'   => array(

									'value' => array(),
									'name'  => array(),
									'class' => array(),
									'id'    => array(),

								),
								'option'   => array(

									'value'    => array(),
									'name'     => array(),
									'class'    => array(),
									'id'       => array(),
									'selected' => array(),

								),
								'optgroup' => array(

									'value' => array(),
									'name'  => array(),
									'class' => array(),
									'id'    => array(),

								),

							);

							echo wp_kses( $option, $allowedHtml );

							// echo htmlspecialchars_decode( $option );
							?>
					</optgroup>
				</select>
			</td>

			<?php
		}
	}
}
