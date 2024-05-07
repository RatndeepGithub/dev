<div class="ced_walmart_heading">
	<div class="ced_walmart_render_meta_keys_wrapper ced_walmart_global_wrap">
		<div class="ced_walmart_parent_element">
			<h2>
				<label class="basic_heading ced_walmart_render_meta_keys_toggle"><?php esc_html_e( 'Search Product custom fields and attributes', 'walmart-woocommerce-integration' ); ?></label>
				<span class="dashicons dashicons-arrow-down-alt2 ced_walmart_instruction_icon"></span>
			</h2>
		</div>
		<div class="ced_walmart_child_element">
			<table class="wp-list-table widefat fixed">
				<tr>
					<td><label>Search for the product by its title</label></td>
					<td colspan="2"><input type="text" name="" id="ced_walmart_search_product_name">
						<ul class="ced-walmart-search-product-list">
						</ul>
					</td>
				</tr>
			</table>
			<div class="ced_walmart_render_meta_keys_content">
				<?php
				$meta_keys_to_be_displayed = get_option( 'ced_walmart_metakeys_to_be_displayed', array() );
				$added_meta_keys           = get_option( 'ced_walmart_selected_metakeys', array() );
				$metakey_html              = ced_walmart_render_html( $meta_keys_to_be_displayed, $added_meta_keys );
				print_r( $metakey_html );
				?>
			</div>
		</div>
	</div>
</div>
