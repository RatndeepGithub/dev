<?php

$feed_id  = isset( $_GET['feed_id'] ) ? sanitize_text_field( $_GET['feed_id'] ) : 0;
$store_id = isset( $_GET['store_id'] ) ? sanitize_text_field( wp_unslash( $_GET['store_id'] ) ) : '';

$ced_walmart_import_data = get_option( 'ced_walmart_import_data' . $store_id . wifw_environment(), array() );
if ( isset( $ced_walmart_import_data[ $feed_id ]['feedStatus'] ) && ( 'ERROR' == $ced_walmart_import_data[ $feed_id ]['feedStatus'] || 'PROCESSED' == $ced_walmart_import_data[ $feed_id ]['feedStatus'] ) ) {
	$response = $ced_walmart_import_data[ $feed_id ]['feedData'];
} elseif ( $feed_id ) {
	$ced_walmart_process_request_file = CED_WALMART_DIRPATH . 'admin/walmart/lib/class-ced-walmart-process-request.php';
	include_file( $ced_walmart_process_request_file );
	$ced_walmart_process_instance = Ced_Walmart_Remote_Request::get_instance();
	$actions                      = 'feed';
	$query_args                   = array(
		'feed_id'   => esc_attr( $feed_id ),
		'includeDetails' => 'true'
	);
	/** Refresh token hook for walmart
	 *
	 * @since 1.0.0
	 */
	do_action( 'ced_walmart_refresh_token', $store_id );
	$ced_walmart_process_instance->store_id = $store_id;
	$response                               = $ced_walmart_process_instance->ced_walmart_process_request( $actions );
	if ( isset( $response['errors'] ) ) {
		$message = isset( $response['errors']['error']['description'] ) ? $response['errors']['error']['description'] : 'There is issue with Walmart Api . Try later';
		echo '<h2><' . esc_attr( $message ) . '/h2>';
		return;
	} elseif ( isset( $response['error'][0] ) ) {
		$message = isset( $response['error'][0]['description'] ) ? $response['error'][0]['description'] : 'There is issue with Walmart Api . Try later';
		echo "<h2 class='walmart-error'>" . esc_attr( $message ) . '</h2>';
		return;
	}
}
if ( isset( $response['error'] ) ) {
	esc_html_e( 'Feed General Information', 'walmart-woocommerce-integration' );
	echo '<table class="wp-list-table widefat fixed striped">';
	echo '<tbody>';
	foreach ( $response['error'] as $key => $value ) {
		if ( is_array( $value ) ) {
			continue;
		}
		echo '<tr>';
		echo '<th class="manage-column">' . esc_attr( ucfirst( $key ) ) . '</th>';
		if ( is_array( $value ) ) {
			echo '<td class="manage-column"></td>';
		} else {
			echo '<td class="manage-column">' . esc_attr( $value ) . '</td>';
		}
		echo '</tr>';
	}
	echo '</tbody>';
	echo '</table>';
} elseif ( isset( $response['result']['feedId'] ) ) {
	$ced_walmart_import_data                           = get_option( 'ced_walmart_import_data', array() );
	$ced_walmart_import_data[ $feed_id ]['feedStatus'] = $response['result']['feedStatus'];
	$ced_walmart_import_data[ $feed_id ]['feedData']   = $response['result'];
	update_option( 'ced_walmart_import_data', $ced_walmart_import_data );
	$response_meta                      = $response['result'];
	$feed_info_array                    = array();
	$feed_info_array['feedId']          = $response_meta['feedId'];
	$feed_info_array['feedStatus']      = $response_meta['feedStatus'];
	$feed_info_array['itemsReceived']   = $response_meta['itemsReceived'];
	$feed_info_array['itemsSucceeded']  = $response_meta['itemsSucceeded'];
	$feed_info_array['itemsFailed']     = $response_meta['itemsFailed'];
	$feed_info_array['itemsProcessing'] = $response_meta['itemsProcessing'];
	$feed_info_array['ingestionErrors'] = $response_meta['ingestionErrors'];

	echo '<h2 class="ced_umb_setting_header ced_umb_bottom_margin">Feed General Information</h2>';
	echo '<table class="wp-list-table widefat fixed striped">';
	echo '<tbody>';
	foreach ( $feed_info_array as $key => $value ) {
		echo '<tr>';
		echo '<th class="manage-column">' . esc_attr( ucfirst( $key ) ) . '</th>';
		echo '<td class="manage-column">';
		if ( 'ingestionErrors' == $key ) {
			if ( is_array( $value ) && ! empty( $value ) ) {
				foreach ( $value as $inr_value ) {
					if ( is_array( $inr_value ) ) {
						foreach ( $inr_value as $error_desc ) {
							echo '<span>' . esc_attr( $error_desc['description'] ) . '</span>';
						}
					} else {
						echo '<span>' . esc_attr( $inr_value ) . '</span>';
					}
				}
			} else {
				echo 'NONE'; }
		} else {
			echo esc_attr( $value );
		}
			echo '</td>';

			echo '</tr>';
	}
		echo '</tbody>';
		echo '</table>';

		$table_header = array( __( 'Product SKU', 'walmart-woocommerce-integration' ), __( 'Product Status', 'walmart-woocommerce-integration' ), __( 'Errors', 'walmart-woocommerce-integration' ) );
	if ( is_array( $response['result']['itemDetails']['itemIngestionStatus'] ) && ! empty( $response['result']['itemDetails']['itemIngestionStatus'] ) ) {
		echo '<br/>';
		echo '<h2 class="ced_umb_setting_header ced_umb_bottom_margin">Feed Details</h2>';
		echo '<table class="wp-list-table widefat fixed striped">';
		echo '<thead>';
		echo '<tr>';
		foreach ( $table_header as $value ) {
			echo '<th class="manage-column">' . esc_attr( $value ) . '</th>';
		}
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		foreach ( $response['result']['itemDetails']['itemIngestionStatus'] as $value ) {
			echo '<tr>';
			echo '<td class="manage-column">' . esc_attr( $value['sku'] ) . '</td>';
			echo '<td class="manage-column">' . esc_attr( $value['ingestionStatus'] ) . '</td>';
			echo '<td class="manage-column">';
			if ( is_array( $value['ingestionErrors'] ) && ! empty( $value['ingestionErrors'] ) ) {
				foreach ( $value['ingestionErrors'] as $ingestion_error ) {
					if ( is_array( $ingestion_error ) ) {
						foreach ( $ingestion_error as $key => $ingest_error ) {
							echo '<span><b>' . esc_attr( $ingest_error['field'] ) . ': </b>' . esc_attr( $ingest_error['description'] ) . '</span><br/>';
						}
					} else {
						echo '<span>' . esc_attr( $ingestion_error ) . '</span>';
					}
				}
			} else {
				echo 'NONE';
			}
			echo '</td>';
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
	}
}
