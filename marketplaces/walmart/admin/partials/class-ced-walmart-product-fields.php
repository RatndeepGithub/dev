<?php
/**
 * Product Feilds section
 *
 * @package  Walmart_Woocommerce_Integration
 * @version  1.0.0
 * @link     https://woocommerce.com/vendor/cedcommerce/
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}


if ( ! class_exists( 'Ced_Walmart_Product_Fields' ) ) {

	/**
	 * Ced_Walmart_Product_Fields.
	 *
	 * @since    1.0.0
	 */
	class Ced_Walmart_Product_Fields {

		/**
		 * Ced_Walmart_Product_Fields Instance Variable.
		 *
		 * @var $_instance
		 */
		private static $_instance;

		/**
		 * Ced_Walmart_Product_Fields Instance.
		 *
		 * @since    1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Get product custom fields for preparing
		 * product data information to send on different
		 * marketplaces accoding to there requirement.
		 *
		 * @since 1.0.0
		 */
		public static function ced_walmart_get_custom_products_fields() {
			$ced_walmart_required_fields = array(
				array(
					'type'     => '_hidden',
					'id'       => '_umb_walmart_category',
					'fields'   => array(
						'id'          => '_umb_walmart_category',
						'label'       => __( 'Category Name', 'walmart-woocommerce-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Specify the category name.', 'walmart-woocommerce-integration' ),
						'type'        => 'hidden',
						'class'       => 'wc_input_price',
					),
					'required' => true,
				),
			);
			return $ced_walmart_required_fields;
		}

		/**
		 * Render dropdown html in the profile edit section
		 *
		 * @since 1.0.0
		 * @param int    $attribute_id Attribute Id.
		 * @param string $attribute_name Attribute name.
		 * @param array  $values Option values.
		 * @param int    $category_id Category Id.
		 * @param int    $product_id Product Id.
		 * @param string $market_place Marketplace.
		 * @param string $attribute_description Attribute Description.
		 * @param int    $index_to_use Index to be used.
		 * @param array  $additional_info Additional data.
		 * @param bool   $is_required Whether required or not.
		 */
		public function ced_walmart_render_dropdown_html( $attribute_id = 0, $attribute_name = '', $values = array(), $category_id = 0, $product_id = 0, $market_place = '', $attribute_description = null, $index_to_use = 0, $additional_info = array( 'case' => 'product' ), $is_required = '', $attribute_data_required_label = '', $objectFulfilled = '', $isVariationExist = false ) {
			$field_name = $category_id . '_' . $attribute_id;

			if ( 'product' == $additional_info['case'] ) {
				$previous_value = get_post_meta( $product_id, $field_name, true );
			} else {
				$previous_value = $additional_info['value'];
			} ?><input type="hidden" name="<?php echo esc_attr( $market_place . '[]' ); ?>" value="<?php echo esc_attr( $field_name ); ?>" />

			<td>
				<label for=""><b><?php echo esc_attr( $attribute_name ); ?> </b>

				<?php
				if ( 'required' == $is_required ) {
					?>
					<span class="ced_walmart_wal_required <?php echo esc_attr( str_replace( ' ', '', $attribute_data_required_label ) ); ?>"><?php esc_html_e( $attribute_data_required_label, 'walmart-woocommerce-integration' ); ?></span>
					<?php
				}

				if ( $objectFulfilled && ! empty( $objectFulfilled ) ) {

					?>
						<span>  <?php esc_html_e( '[ For : ' . ucwords( $objectFulfilled ) . ' ]', 'walmart-woocommerce-integration' ); ?> </span>
						<?php
				}
				?>

				

			</label>
		</td>
		<td>
			<select id="" name="<?php echo esc_attr( $field_name . '[' . $index_to_use . ']' ); ?>" class="" style="">
				<?php
				echo '<option value="">' . esc_html( __( '-- Select --', 'walmart-woocommerce-integration' ) ) . '</option>';
				foreach ( $values as $key => $value ) {
					if ( isset( $value['code'] ) ) {
						if ( $previous_value == $value['code'] ) {
							echo '<option value="' . esc_attr( $value['code'] ) . '" selected>' . esc_attr( $value['label'] ) . '</option>';
						} else {
							echo '<option value="' . esc_attr( $value['code'] ) . '">' . esc_attr( $value['label'] ) . '</option>';
						}
					} elseif ( $previous_value == $key ) {
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
		 * Render text html in the profile edit section
		 *
		 * @since 1.0.0
		 * @param int    $attribute_id Attribute Id.
		 * @param string $attribute_name Attribute name.
		 * @param int    $category_id Category Id.
		 * @param int    $product_id Product Id.
		 * @param string $market_place Marketplace.
		 * @param string $attribute_description Attribute Description.
		 * @param int    $index_to_use Index to be used.
		 * @param array  $additional_info Additional data.
		 * @param bool   $conditionally_required Whether required or not.
		 * @param string $conditionally_required_text Conditionally required data.
		 * @param string $input_type input type.
		 */
		public function ced_walmart_render_text_html( $attribute_id = 0, $attribute_name = '', $category_id = 0, $product_id = 0, $market_place = '', $attribute_description = null, $index_to_use = 0, $additional_info = array( 'case' => 'product' ), $conditionally_required = false, $is_add_html = false, $conditionally_required_text = '', $input_type = '', $objectFulfilled = '', $isVariationExist = false ) {
			global $post, $product, $loop;
			$field_name = $category_id . '_' . $attribute_id;
			if ( 'product' == $additional_info['case'] ) {
				$previous_value = get_post_meta( $product_id, $field_name, true );
			} else {
				$previous_value = $additional_info['value'];
			}
			?>

			<input type="hidden" name="<?php echo esc_attr( $market_place . '[]' ); ?>" value="<?php echo esc_attr( $field_name ); ?>" />
			<td>
				<label for=""><b><?php echo esc_attr( $attribute_name ); ?> </b>
				<?php
				if ( 'required' == $conditionally_required ) {
					?>
					<span class="ced_walmart_wal_required <?php echo esc_attr( str_replace( ' ', '', $conditionally_required_text ) ); ?>"><?php esc_html_e( $conditionally_required_text, 'walmart-woocommerce-integration' ); ?></span>

					<?php
				}

				if ( $objectFulfilled && ! empty( $objectFulfilled ) ) {

					?>
						<span>  <?php esc_html_e( '[ For : ' . ucwords( $objectFulfilled ) . ' ]', 'walmart-woocommerce-integration' ); ?> </span>
						<?php
				}

				?>

			</label>
		</td>
		<td>
			<?php
			$field_type = 'text';

			if ( isset( $input_type ) && ! empty( $input_type ) ) {
				$field_type = $input_type;
			} elseif ( 'integer' == $input_type ) {
				$field_type = 'number';
			}
			if ( 'keyfeatures' == $attribute_id ) {
				$count = 0;
				if ( is_array( $previous_value ) ) {
					foreach ( $previous_value as $key => $value ) {
						if ( empty( $value ) ) {
							continue;
						}
						?>
						<input class="short" style="" name="<?php echo esc_attr( $field_name . '[' . $count . ']' ); ?>" id="" value="<?php echo esc_attr( $value ); ?>" placeholder="" type="text" />
						<?php
						$count++;
					}
				}
			} else {
				?>
				<input class="short" style="" name="<?php echo esc_attr( $field_name . '[' . $index_to_use . ']' ); ?>" id="" value="<?php echo esc_attr( $previous_value ); ?>" placeholder="" type="<?php echo esc_attr( $field_type ); ?>" /> 
				<?php
			}

			?>
		</td>
			<?php
		}

		/**
		 * Render text html for hidden fields in the profile edit section
		 *
		 * @since 1.0.0
		 * @param int    $attribute_id Attribute Id.
		 * @param string $attribute_name Attribute name.
		 * @param int    $category_id Category Id.
		 * @param int    $product_id Product Id.
		 * @param string $market_place Marketplace.
		 * @param string $attribute_description Attribute Description.
		 * @param int    $index_to_use Index to be used.
		 * @param array  $additional_info Additional data.
		 * @param bool   $conditionally_required Whether required or not.
		 * @param string $conditionally_required_text Conditionally required data.
		 */
		public function render_input_text_html_hidden( $attribute_id = 0, $attribute_name = '', $category_id = 0, $product_id = 0, $market_place = '', $attribute_description = null, $index_to_use = 0, $additional_info = array( 'case' => 'product' ), $conditionally_required = false, $conditionally_required_text = '' ) {
			global $post, $product, $loop;
			$field_name = $category_id . '_' . $attribute_id;
			if ( 'product' == $additional_info['case'] ) {
				$previous_value = get_post_meta( $product_id, $field_name, true );
			} else {
				$previous_value = $additional_info['value'];
			}
			?>

			<input type="hidden" name="<?php echo esc_attr( $market_place . '[]' ); ?>" value="<?php echo esc_attr( $field_name ); ?>" />
			<td>
			</label>
		</td>
		<td>
			<label></label>
			<input class="short" style="" name="<?php echo esc_attr( $field_name . '[' . $index_to_use . ']' ); ?>" id="" value="<?php echo esc_attr( $previous_value ); ?>" placeholder="" type="hidden" /> 
		</td>
			<?php
		}
	}
}
