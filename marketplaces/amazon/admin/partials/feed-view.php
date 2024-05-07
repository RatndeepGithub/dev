<?php


if ( ! defined( 'ABSPATH' ) ) {
	die;
}


$file = CED_AMAZON_DIRPATH . 'admin/partials/header.php';

if ( file_exists( $file ) ) {
	require_once $file;
}


$feedId    = isset( $_GET['feed-id'] ) ? sanitize_text_field( $_GET['feed-id'] ) : '';
$feedType  = isset( $_GET['feed-type'] ) ? sanitize_text_field( $_GET['feed-type'] ) : '';
$wooFeedId = isset( $_GET['woo-feed-id'] ) ? sanitize_text_field( $_GET['woo-feed-id'] ) : '';

$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
$mode      = isset( $_GET['mode'] ) ? sanitize_text_field( $_GET['mode'] ) : 'production';

$seller_id_array = explode( '|', $seller_id );
$country         = isset( $seller_id_array[0] ) ? $seller_id_array[0] : '';
$mod_seller_id   = isset( $seller_id_array[1] ) ? $seller_id_array[1] : '';

require_once CED_AMAZON_DIRPATH . 'admin/amazon/lib/class-feed-manager.php';

if ( empty( $feedId ) ) {
	echo '<pre>';
	echo "<table border='3'><tbody>";
	echo 'Feed id not found';
	echo '</tbody></table>';
	echo '</pre>';
	return;
}

global $wpdb;
$tableName        = $wpdb->prefix . 'ced_amazon_feeds';
$feed_request_ids = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_feeds WHERE `feed_id` = %d", $feedId ), 'ARRAY_A' );


if ( ! is_array( $feed_request_ids ) || ! is_array( $feed_request_ids[0] ) ) {
	echo '<pre>';
	echo "<table border='3'><tbody>";
	echo 'Sorry details not found!!';
	echo '</tbody></table>';
	echo '</pre>';
	return;
}

$feed_request_id = $feed_request_ids[0];
$main_id         = $feed_request_id['id'];
$feed_type       = $feed_request_id['feed_action'];
$location_id     = $feed_request_id['feed_location'];

$opt_type     = isset( $feed_request_id['opt_type'] ) ? $feed_request_id['opt_type'] : '';
$product_data = isset( $feed_request_id['sku'] ) ? json_decode( $feed_request_id['sku'], true ) : array();

$response    = $feed_request_id['response'];
$response    = json_decode( $response, true );
$marketplace = 'amazon_spapi';

$response_format = false;

$feed_opt_array = array(
	'POST_INVENTORY_AVAILABILITY_DATA' => 'Inventory Update',
	'POST_FLAT_FILE_LISTINGS_DATA'     => 'Product Upload',
	'POST_PRODUCT_PRICING_DATA'        => 'Price Update',
	'POST_PRODUCT_IMAGE_DATA'          => 'Image Update',
	'POST_ORDER_FULFILLMENT_DATA'      => 'Order Fulfillment',
	'POST_PRODUCT_DATA'                => 'Relist Product',
	'Delete_Product'                   => 'Delete Product',
);

$feed_name = isset( $feed_opt_array[ $feed_type ] ) ? $feed_opt_array[ $feed_type ] : '-';


if ( ! empty( $feedId ) ) {

	if ( isset( $response['status'] ) && 'DONE' == $response['status'] ) {
		$response_format = true;

	} else {
		$feed_manager = Ced_Umb_Amazon_Feed_Manager::get_instance( $mode );
		$response     = $feed_manager->getFeedItemsStatusSpApi( $feedId, $feed_type, $location_id, $marketplace, $seller_id );

		if ( isset( $response['status'] ) && 'DONE' == $response['status'] ) {
			$response_format = true;
		}
		$response_data = wp_json_encode( $response );
		$wpdb->update( $tableName, array( 'response' => $response_data ), array( 'id' => $main_id ) );
	}

	if ( $response_format ) {

		if ( 'POST_FLAT_FILE_LISTINGS_DATA' == $feed_type ) {

			if ( isset( $response['feed_id'] ) && ! empty( $response['feed_id'] ) ) {
				echo '<div class=""><p><b>' . esc_html__( 'Feed Id: ' . $response['feed_id'], 'amazon-for-woocommerce' ) . '</b></p>';
			}

			$tab_response_data = explode( "\n", $response['body'] );

			$first_row_data         = explode( "\t", $tab_response_data[0] );
			$second_row_data        = explode( "\t", $tab_response_data[1] );
			$third_row_data         = explode( "\t", $tab_response_data[2] );
			$response_heading       = isset( $first_row_data[0] ) ? $first_row_data[0] : '';
			$processed_record_lable = isset( $second_row_data[1] ) ? $second_row_data[1] : '';
			$processed_record_value = isset( $second_row_data[3] ) ? $second_row_data[3] : '';
			$success_record_lable   = isset( $third_row_data[1] ) ? $third_row_data[1] : '';
			$success_record_value   = isset( $third_row_data[3] ) ? $third_row_data[3] : '';

			$tab_response_html = '';
			foreach ( $tab_response_data as $tabKey => $tabValue ) {

				$line_data = explode( "\t", $tabValue );
				if ( 'Feed Processing Summary' == $line_data[0] || 'Feed Processing Summary:' == $line_data[0] ) {
					continue;
				} elseif ( empty( $line_data[0] ) || '' == $line_data[0] ) {
					continue;
				} elseif ( 'original-record-number' == $line_data[0] ) {
					continue;
				} else {
					$tab_response_html .= '<tr><td >' . esc_html__( $line_data[0], 'amazon-for-woocommerce' ) . '</td>';
					$tab_response_html .= '<td>' . esc_html__( $line_data[1], 'amazon-for-woocommerce' ) . '</td>';
					$tab_response_html .= '<td>' . esc_html__( $line_data[2], 'amazon-for-woocommerce' ) . '</td>';
					$tab_response_html .= '<td>' . esc_html__( $line_data[3], 'amazon-for-woocommerce' ) . '</td>';
					$tab_response_html .= '<td >' . esc_html__( $line_data[4], 'amazon-for-woocommerce' ) . '</td></tr>';
				}
			}

			$tableHtml = ' <h3 class="ced_feed_product_table_heading" >Feed Summary Table: </h3> <table class="wp-list-table widefat striped table-view-list posts ced_feed_processing_summary_table" >
				<thead class="table-dark">
					<tr>
						<th scope="col" colspan="5" style="text-align: center;" >' . esc_html__( $response_heading, 'amazon-for-woocommerce' ) . '</th>
					</tr>
					<tr>
						<th scope="col">' . esc_html__( $processed_record_lable, 'amazon-for-woocommerce' ) . '</th>
						<th scope="col" colspan="4">' . esc_html__( $processed_record_value, 'amazon-for-woocommerce' ) . '</th>
					</tr>
					<tr>
						<th scope="col">' . esc_html__( $success_record_lable, 'amazon-for-woocommerce' ) . '</th>
						<th scope="col" colspan="4">' . esc_html__( $success_record_value, 'amazon-for-woocommerce' ) . '</th>
					</tr>
					<tr>
						<th scope="col">' . esc_html__( 'Original record number', 'amazon-for-woocommerce' ) . '</th>
						<th scope="col">' . esc_html__( 'SKU', 'amazon-for-woocommerce' ) . '</th>
						<th scope="col">' . esc_html__( 'Error code', 'amazon-for-woocommerce' ) . '</th>
						<th scope="col">' . esc_html__( 'Error type', 'amazon-for-woocommerce' ) . '</th>
						<th scope="col">' . esc_html__( 'Error message', 'amazon-for-woocommerce' ) . '</th>
					</tr>
				</thead>
				<tbody>';

			$tableHtml .= $tab_response_html;
			$tableHtml .= '</tbody>
	        </table></div>';

			print_r( $tableHtml );

		} elseif ( 'JSON_LISTINGS_FEED' == $feed_type ) {

			$feed_response = json_decode( $response['body'], true );

			if ( isset( $response['feed_id'] ) && ! empty( $response['feed_id'] ) ) {
				echo '<div class=""><p><b>Feed Id: ' . esc_attr( $response['feed_id'] ) . '</b></p>';
			}

			if ( isset( $feed_response ) && ! empty( $feed_response ) ) {

				$header_data      = isset( $feed_response['header'] ) ? $feed_response['header'] : array();
				$header_data_html = '';
				if ( ! empty( $header_data ) ) {
					foreach ( $header_data as $header_label => $header_fields ) {
						$header_data_html .= $header_label . ' : ' . $header_fields . '<br/>';
					}
				}

				$summary_data      = isset( $feed_response['summary'] ) ? $feed_response['summary'] : array();
				$summary_data_html = '';
				if ( ! empty( $summary_data ) ) {
					foreach ( $summary_data as $summary_label => $summary_fields ) {
						$summary_data_html .= $summary_label . ' : ' . $summary_fields . '<br/>';
					}
				}

				$error_data = isset( $feed_response['issues'] ) ? $feed_response['issues'] : array();
				$error_html = '';
				if ( ! empty( $error_data ) ) {
					foreach ( $error_data as $error_label => $error_fields ) {
						$message_id = isset( $error_fields['messageId'] ) ? $error_fields['messageId'] : '';
						$code       = isset( $error_fields['code'] ) ? $error_fields['code'] : '';
						$severity   = isset( $error_fields['severity'] ) ? $error_fields['severity'] : '';
						$message    = isset( $error_fields['message'] ) ? $error_fields['message'] : '';

						$error_html .= '<p><b>' . esc_html__( 'Message Id: ', 'amazon-for-woocommerce' ) . '</b>' . esc_html__( $message_id ) . '</p>';
						$error_html .= '<p><b>' . esc_html__( 'Code: ', 'amazon-for-woocommerce' ) . '</b>' . esc_html__( $code ) . '</p>';
						$error_html .= '<p><b>' . esc_html__( 'Severity: ', 'amazon-for-woocommerce' ) . '</b> ' . esc_html__( $severity ) . '</p>';
						$error_html .= '<p><b>' . esc_html__( 'Message: ', 'amazon-for-woocommerce' ) . '</b>' . esc_html__( $message ) . '<p/><hr/><br/>';
					}
				}

				$tableHtml  = ' <h3 class="ced_feed_product_table_heading" >Feed Summary Table: </h3> <table class="wp-list-table widefat striped table-view-list posts ced_feed_processing_summary_table" >
						<thead class="table-dark">
							<tr>
								<th scope="col">' . esc_html__( 'Header', 'amazon-for-woocommerce' ) . '</th>
								<th scope="col">' . esc_html__( 'Summary', 'amazon-for-woocommerce' ) . '</th>
								<th scope="col">' . esc_html__( 'Issues', 'amazon-for-woocommerce' ) . '</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>';
				$tableHtml .= $header_data_html;
				$tableHtml .= '</td>
							<td>';
				$tableHtml .= $summary_data_html;
				$tableHtml .= '</td>
							<td style="width: 30rem;">';
				$tableHtml .= $error_html;
				$tableHtml .= '</td>
						</tr>
						
					</tbody>
		        </table></div>';

				print_r( $tableHtml );

			} else {
				echo '<pre>';
				print_r( $feed_response );
				echo '</pre>';
			}
		} else {

			$sxml          = simplexml_load_string( $response['body'], 'SimpleXMLElement', LIBXML_NOCDATA );
			$arrayResponse = xml2array( $sxml );


			if ( isset( $response['feed_id'] ) && ! empty( $response['feed_id'] ) ) {
				echo '<div class="ced_feed_info_container">';
				echo '<div><p><b>Feed Id</b><span class="ced_feed_colon">:</span><span>' . esc_attr( $response['feed_id'] ) . '</span></p>';
				echo '</div>';
			}

			ced_prepare_feed_product_table( $product_data );

			if ( isset( $arrayResponse['Message'] ) && ! empty( $arrayResponse['Message'] ) ) {

				$processingSummary     = isset( $arrayResponse['Message'] ) && isset( $arrayResponse['Message']['ProcessingReport'] ) && isset( $arrayResponse['Message']['ProcessingReport']['ProcessingSummary'] ) ? $arrayResponse['Message']['ProcessingReport']['ProcessingSummary'] : array();
				$processingSummaryHtml = '';

				$results     = isset( $arrayResponse['Message']['ProcessingReport']['Result'][0] ) ? $arrayResponse['Message']['ProcessingReport']['Result'] : $arrayResponse['Message']['ProcessingReport'];
				$resultsHtml = '';

				if ( ! empty( $processingSummary ) ) {
					foreach ( $processingSummary as $label => $fields ) {
						$processingSummaryHtml .= $label . ' : ' . $fields . '<br/>';
					}
				}

				if ( ! empty( $results ) ) {

					foreach ( $results as $label => $fields ) {

						if ( 'Result' == $label || is_numeric( $label ) ) {
							if ( is_object( $fields ) ) {
								$fields = xml2array( $fields );
							}

							$resultCode        = isset( $fields['ResultCode'] ) ? $fields['ResultCode'] : '';
							$resultMessageCode = isset( $fields['ResultMessageCode'] ) ? $fields['ResultMessageCode'] : '';
							$resultDescription = isset( $fields['ResultDescription'] ) ? $fields['ResultDescription'] : '';
							$sku               = isset( $fields['AdditionalInfo'] ) && isset( $fields['AdditionalInfo']['SKU'] ) ? $fields['AdditionalInfo']['SKU'] : '';

							$resultsHtml .= '<p> <b>Result code : </b>' . esc_attr( $resultCode ) . '</p>';
							$resultsHtml .= '<p><b> Result Message Code : </b>' . esc_attr( $resultMessageCode ) . '</p>';
							$resultsHtml .= '<p> <b>Result Description : </b> ' . esc_attr( $resultDescription ) . '</p>';
							$resultsHtml .= '<p> <b> Sku : </b>' . esc_attr( $sku ) . '</p></hr><br/>';
						}
					}
				}

				$tableHtml = ' <h3 class="ced_feed_product_table_heading" >Feed Summary Table: </h3><table class="wp-list-table widefat striped table-view-list posts ced_feed_processing_summary_table"  >
						<thead class="table-dark">
							<tr>
								<th scope="col">Merchant Identifier </th>
								<th scope="col">Message Type</th>
								<th scope="col">Status Code</th>
								<th scope="col">ProcessingSummary</th>
								<th scope="col">Results</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<th scope="row">' . esc_attr( $arrayResponse['Header']['MerchantIdentifier'] ) . '</th>
								<td>' . esc_attr( $arrayResponse['MessageType'] ) . '</td>
								<td>' . esc_attr( $arrayResponse['Message']['ProcessingReport']['StatusCode'] ) . '</td>
								<td>';

					$tableHtml .= $processingSummaryHtml;
					$tableHtml .= '</td>
								<td style="width: 30rem;">';
					$tableHtml .= $resultsHtml;
					$tableHtml .= '</td>
							</tr>
							
						</tbody>
			        </table></div>';

				print_r( $tableHtml );
			}
		}
	} elseif ( isset( $response['feed_id'] ) && ! empty( $response['feed_id'] ) ) {

			echo '<div class="ced_feed_info_container">';
			echo '<div><p><b>Feed Id</b><span class="ced_feed_colon">:</span><span>' . esc_attr( $response['feed_id'] ) . '</span></p>';
			echo '</div>';

			ced_prepare_feed_product_table( $product_data );

			echo ' <h3 class="ced_feed_product_table_heading" >Feed Summary Table: </h3> <table class="wp-list-table widefat striped table-view-list posts ced_feed_processing_summary_table" >
				<thead class="table-dark">
					<tr>
						<th scope="col">Feed Id </th>
						<th scope="col">Feed Type</th>
						<th scope="col">Feed Status</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>' . esc_attr( $response['feed_id'] ) . '</td>
						<td>' . esc_attr( $response['feed_action'] ) . '</td>
						<td>' . esc_attr( $response['status'] ) . '</td>
					</tr>
					
				</tbody>
	        </table></div>';

	} else {
		$message = isset( $response['body'] ) ? $response['body'] : $response['message'];
		echo '<div class=""><p><b>' . esc_attr( $message ) . '</b></p></div>';
	}
}


function xml2array( $xmlObject, $out = array() ) {
	foreach ( (array) $xmlObject as $index => $node ) {
		$out[ $index ] = ( is_object( $node ) ) ? xml2array( $node ) : $node;
	}

	return $out;
}


function ced_prepare_feed_product_table( $product_data ) {

	$headers = array(
		'product_name' => 'Product Name',
		'sku'          => 'SKU',
		'type'         => 'Type',
		'parent_sku'   => 'Parent SKU',
		'value'        => 'Value Transmitted',
	);

	?>
			<h3 class="ced_feed_product_table_heading" >Feed Product Table: </h3>
			<table class="wp-list-table widefat striped table-view-list posts ced_feed_product_table">
				<thead class="table-dark">
					<tr>
						<?php
						foreach ( $headers as $key => $value ) {
							?>
							<th scope="col"><?php echo( $value ); ?></th>
							<?php } ?>
					</tr>
				</thead>
				<tbody>
					
						<?php

						foreach ( $product_data as $key => $value ) {
							$key_value = isset( $product_data[ $key ] ) ? $product_data[ $key ] : '';
							?>
								<tr>
									<th scope="row"><?php echo esc_attr( $value['product_name'] ); ?></th> 
									<td scope="col"><?php echo esc_attr( $key ); ?></td>
									<td scope="col"><?php echo esc_attr( $value['type'] ); ?></td>
									<td scope="col"><?php echo esc_attr( $value['parent_sku'] ); ?></td>
									<td scope="col"><?php echo esc_attr( $value['value'] ); ?></td>

								</tr>	
								<?php

						}

						?>
						
					</tr>
				</tbody>
			</table>
	<?php
}

?>
