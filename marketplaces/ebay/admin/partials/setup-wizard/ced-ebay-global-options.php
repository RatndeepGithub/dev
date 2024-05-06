

<style type="text/css">
	.ced-label-wrap label {
		font-weight: 600;
		color: #1E1E1E;
	}

	.ced-label-wrap label {
		line-height: 32px;
	}

	.ced-label-wrap {
		margin-bottom: 15px;
	}

	.ced-progress li {
		width: 33% !important;
	}
</style>
<?php
$global_options = ! empty( get_option( 'ced_ebay_global_options' ) ) ? get_option( 'ced_ebay_global_options', true ) : array();
if ( ! isset( $global_options[ $user_id ][ $site_id ] ) ) {
	$global_options[ $user_id ][ $site_id ] = array(
		'Brand'                 => array(
			'meta_key'     => '',
			'custom_value' => '',
			'description'  => 'asdsaddsadsads',
		),
		'Title'                 => array(
			'meta_key'     => '',
			'custom_value' => '',
			'description'  => 'asdsaddsadsads',

		),
		'SKU'                   => array(
			'meta_key'     => '',
			'custom_value' => '',
			'description'  => 'asdsaddsadsads',

		),
		'MPN'                   => array(
			'meta_key'     => '',
			'custom_value' => '',
			'description'  => 'asdsaddsadsads',
		),
		'Maximum Dispatch Time' => array(
			'meta_key'    => '',
			'description' => 'asdsaddsadsads',
			'options'     => array(
				''   => 'Select',
				'0'  => 'Same Business Day',
				'1'  => '1 Day',
				'2'  => '2 Days',
				'3'  => '3 Days',
				'4'  => '4 Days',
				'5'  => '5 Days',
				'10' => '10 Days',
				'15' => '15 Days',
				'20' => '20 Days',
				'30' => '30 Days',
			),
		),
		'Listing Duration'      => array(
			'meta_key'    => '',
			'description' => 'asdsaddsadsads',
			'options'     => array(
				''         => 'Select',
				'Days_1'   => 'Days_1',
				'Days_10'  => 'Days_10',
				'Days_120' => 'Days_120',
				'Days_14'  => 'Days_14',
				'Days_21'  => 'Days_21',
				'Days_3'   => 'Days_3',
				'Days_30'  => 'Days_30',
				'Days_5'   => 'Days_5',
				'Days_60'  => 'Days_60',
				'Days_7'   => 'Days_7',
				'Days_90'  => 'Days_90',
				'GTC'      => 'Good Till Cancelled',
			),
		),
	);

	update_option( 'ced_ebay_global_options', $global_options );
} else {
	$global_options = get_option( 'ced_ebay_global_options', true );
	if ( isset( $global_options[ $user_id ][ $site_id ] ) ) {
		$tempGlobalOptions = array();
		$tempGlobalOptions = $global_options;
		$tempGlobalOptions[ $user_id ][ $site_id ]['Maximum Dispatch Time']['options'] = array(
			''   => 'Select',
			'0'  => 'Same Business Day',
			'1'  => '1 Day',
			'2'  => '2 Days',
			'3'  => '3 Days',
			'4'  => '4 Days',
			'5'  => '5 Days',
			'10' => '10 Days',
			'15' => '15 Days',
			'20' => '20 Days',
			'30' => '30 Days',
		);
		$tempGlobalOptions[ $user_id ][ $site_id ]['Listing Duration']['options']      = array(
			''         => 'Select',
			'Days_1'   => 'Days_1',
			'Days_10'  => 'Days_10',
			'Days_120' => 'Days_120',
			'Days_14'  => 'Days_14',
			'Days_21'  => 'Days_21',
			'Days_3'   => 'Days_3',
			'Days_30'  => 'Days_30',
			'Days_5'   => 'Days_5',
			'Days_60'  => 'Days_60',
			'Days_7'   => 'Days_7',
			'Days_90'  => 'Days_90',
			'GTC'      => 'Good Till Cancelled',
		);

		$global_options = $tempGlobalOptions;
	}
}



?>

<style type="text/css">

.ced-notification-error p {
	font-size: 13px !important;
	margin: 0.5em 0 !important;
	color: #1E1E1E !important;
	font-weight: 400;
}
.ced-notification-error {
	background: #F4A2A2 !important;
}
			
			.ced-label-wrap label {
				font-weight: 600;
				color: #1E1E1E;
			}
			.ced-label-wrap label {
				line-height: 32px;
			}
			.ced-label-wrap {
				margin-bottom: 15px;
			}
			.ced-progress li{
				width: 33% !important;
			}
		</style>
		

		

		<div class="woocommerce-progress-form-wrapper">
	
	<h2 style="text-align: left;">eBay Integration Onboarding</h2>
	<ol class="wc-progress-steps ced-progress">

<?php

foreach ( $keys as $index => $key ) {

	if ( $this->step == $key ) {
		echo '<li id="' . esc_attr( $key ) . '" class="active">' . esc_attr( $this->steps[ $key ]['name'] ) . ' </li>';
	} elseif ( $this->step !== $key && $index < $step_index ) {
		echo '<li id="' . esc_attr( $key ) . '" class="done">' . esc_attr( $this->steps[ $key ]['name'] ) . ' </li>';
	} else {
		echo '<li id="' . esc_attr( $key ) . '">' . esc_attr( $this->steps[ $key ]['name'] ) . ' </li>';

	}
}
?>



	</ol>

	<form class="wc-progress-form-content woocommerce-importer" name="ced_eBay_onboardings" enctype="multipart/form-data" action="" method="post">

	<header>
			<h2>Global Options</h2>
					<p>

Enhance your eBay listing with the following attributes. Efficient and time-saving, these can be reused later on in product templates.</p>			</header>
</header>
<header>
	<?php
	if ( null !== $is_error ) {
		?>
<div id="message" class="error inline ced-notification-error"><p>
	An issue arose while trying to save the options. Please try again. If the issue persists, please contact support. <b>(Error Code: <?php echo esc_html( $is_error ); ?>)</p></div> 
		<?php
	}

	?>
					<table class="form-table">
						<tbody>
							<tr valign="top">
								<th scope="row" class="titledesc">
									<label for="woocommerce_currency">
										Column name 
									</label> 
								</th>
								<th scope="row" class="titledesc">
									<label for="woocommerce_currency">
										Map to fields
									</label> 
								</th>
								<th scope="row" class="titledesc">
									<label for="woocommerce_currency">
										Custom Value 
									</label> 
								</th>
							</tr>
							<?php
							foreach ( $global_options[ $user_id ][ $site_id ] as $gKey => $gOption ) {
								$selectDropdownHTML = ced_ebay_get_options_for_dropdown();
								?>
								<tr>
								<th scope="row" class="titledesc">
									<label for="woocommerce_currency">
										<?php echo esc_html( $gKey ); ?>
									</label>
								</th>
								<td class="forminp forminp-select">
								<select class="ced_ebay_map_to_fields" name="ced_ebay_global_options[<?php echo esc_html( $user_id ); ?>][<?php echo esc_html( $site_id ); ?>][<?php echo esc_html( $gKey ); ?>|meta_key]">
										<?php

										if ( isset( $gOption['options'] ) && ! empty( $gOption['options'] ) ) {
											foreach ( $gOption['options'] as $optValue => $optName ) {
												?>
		<option 
												<?php echo ( isset( $gOption['meta_key'] ) && $optValue == $gOption['meta_key'] ) ? 'selected' : ''; ?>
		value="<?php echo esc_attr( $optValue ); ?>"><?php echo esc_attr( $optName ); ?></option>																	
												<?php
											}
										} else {
											if ( isset( $gOption['meta_key'] ) && ! empty( $gOption['meta_key'] ) ) {
												$selectDropdownHTML = str_replace(
													'<option value="' . esc_attr( $gOption['meta_key'] ) . '"',
													'<option value="' . esc_attr( $gOption['meta_key'] ) . '" selected',
													$selectDropdownHTML
												);
											}
											print_r( $selectDropdownHTML );

										}

										?>
								</select>
							</td>
								<?php if ( ! isset( $gOption['options'] ) || empty( $gOption['options'] ) ) { ?>

							<td class="forminp forminp-select">
									<input type="text" name="ced_ebay_global_options[<?php echo esc_html( $user_id ); ?>][<?php echo esc_html( $site_id ); ?>][<?php echo esc_html( $gKey ); ?>|custom_value]" style="width:100%"; value="<?php echo esc_attr( ! empty( $gOption['custom_value'] ) ? $gOption['custom_value'] : '' ); ?>">
								</td>
							</tr>

									<?php
								}
							}
							?>
						</tbody>
					</table>

				</header>
				<div class="wc-actions">
					<?php wp_nonce_field( 'ced_ebay_global_options_action', 'ced_ebay_global_options_button' ); ?>
					<button type="submit" style="float: right;" value="Save and continue" name="save_step" class="button-next components-button is-primary">Save and continue</button>
				</div>
		
</form>
</div>

<script type="text/javascript">
	jQuery(".ced_ebay_map_to_fields").selectWoo({
		dropdownPosition: 'below',
		dropdownAutoWidth : false,
	});
</script>
