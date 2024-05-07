<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}


function ced_amazon_time_elapsed_string( $datetime, $full = false ) {
	$now  = new DateTime();
	$ago  = new DateTime( $datetime );
	$diff = $now->diff( $ago );

	$diff->w  = floor( $diff->d / 7 );
	$diff->d -= $diff->w * 7;

	$string = array(
		'y' => 'year',
		'm' => 'month',
		'w' => 'week',
		'd' => 'day',
		'h' => 'hour',
		'i' => 'minute',
		's' => 'second',
	);
	foreach ( $string as $k => &$v ) {
		if ( $diff->$k ) {
			$v = $diff->$k . ' ' . $v . ( $diff->$k > 1 ? 's' : '' );
		} else {
			unset( $string[ $k ] );
		}
	}

	if ( ! $full ) {
		$string = array_slice( $string, 0, 1 );
	}
	return $string ? implode( ', ', $string ) . ' ago' : 'just now';
}



function ced_amazon_log_data( $message, $log_name, $log_type = '' ) {

	if ( is_array( $message ) ) {
		$message = print_r( $message, true );
	} elseif ( is_object( $message ) ) {
		$ob_get_length = ob_get_length();
		if ( ! $ob_get_length ) {
			if ( false === $ob_get_length ) {
				ob_start();
			}
			var_dump( $message );
			$message = ob_get_contents();
			if ( false === $ob_get_length ) {
				ob_end_clean();
			} else {
				ob_clean();
			}
		} else {
			$message = '(' . get_class( $message ) . ' Object)';
		}
	}

	if ( ! empty( $log_name ) ) {
		$upload_dir = wp_upload_dir();
		$log_dir    = ! empty( $log_type ) ? $upload_dir['basedir'] . '/ced-amazon/logs/' . $log_type : $upload_dir['basedir'] . '/ced-amazon/logs';
		if ( ! is_dir( $log_dir ) ) {
			wp_mkdir_p( $log_dir, 0777 );
		}
		$log_file = $log_dir . '/' . $log_name;
		file_put_contents( $log_file, PHP_EOL . $message, FILE_APPEND );
	}
}

if ( ! function_exists( 'ced_get_navigation_url' ) ) {
	function ced_get_navigation_url( $channel = 'home', $query_args = array() ) {
		if ( ! empty( $query_args ) ) {
			return admin_url( 'admin.php?page=sales_channel&channel=' . $channel . '&' . http_build_query( $query_args ) );
		}
		return admin_url( 'admin.php?page=sales_channel&channel=' . $channel );
	}
}

function ced_amazon_get_access_token( $user_id, $seller_id = '' ) {

	if ( empty( $user_id ) ) {
		return wp_json_encode(
			array(
				'status'  => false,
				'message' => 'Invalid access token',
			)
		);
	}

	$sellernextShopIds                 = get_option( 'ced_amazon_sellernext_shop_ids', array() );
	$ced_amzon_configuration_validated = get_option( 'ced_amzon_configuration_validated', array() );

	if ( empty( $seller_id ) ) {
		$seller_id = $sellernextShopIds[ $user_id ]['ced_mp_seller_key'];
	}

	$access_token = isset( $ced_amzon_configuration_validated[ $seller_id ] ) ? $ced_amzon_configuration_validated[ $seller_id ]['seller_next_access_token'] : '';

	if ( ! empty( $access_token ) ) {
		return wp_json_encode(
			array(
				'status' => true,
				'data'   => $access_token,
			)
		);
	} else {
		return wp_json_encode(
			array(
				'status'  => false,
				'message' => 'Invalid UserId',
			)
		);
	}
}


function ced_amazon_get_categories_hierarchical( $args = array() ) {

	if ( ! isset( $args['parent'] ) ) {
		$args['parent'] = 0;
	}

	$categories = get_categories( $args );
	foreach ( $categories as $key => $category ) :
		$args['parent']                       = $category->term_id;
		$categories[ $key ]->child_categories = ced_amazon_get_categories_hierarchical( $args );
	endforeach;

	return $categories;
}

function ced_amazon_nestdiv( $woo_store_categories = array(), $current_amazon_profile = array(), $depth = 0, $amazon_wooCategories = array() ) {

	foreach ( $woo_store_categories as $key => $value ) {
		$selected = '';
		if ( ! empty( $current_amazon_profile ) ) {
			$woo_cat = json_decode( $current_amazon_profile['wocoommerce_category'], true );
			if ( isset( $woo_cat ) && in_array( $value->term_id, $woo_cat ) ) {

				$selected = 'selected';
			}
		}

		if ( ! in_array( $value->term_id, $amazon_wooCategories ) ) {
			?>
			<option id="<?php echo esc_attr( $value->term_id ); ?>" value="<?php echo esc_attr( $value->term_id ); ?>" <?php echo esc_attr( $selected ); ?> ><?php echo esc_attr( $value->name ); ?></option>
			<?php
		} else {
			if ( empty( $current_amazon_profile ) ) {
				$woo = array();
			} else {
				$woo = json_decode( $current_amazon_profile['wocoommerce_category'], true );
			}
			if ( isset( $woo ) && in_array( $value->term_id, $woo ) ) {
				?>
				<option id="<?php echo esc_attr( $value->term_id ); ?>" value="<?php echo esc_attr( $value->term_id ); ?>" <?php echo esc_attr( $selected ); ?> ><?php echo esc_attr( $value->name ); ?></option>
				<?php
			}
		}

		if ( isset( $value->child_categories[0] ) ) {
			ced_amazon_nestdiv( $value->child_categories, $current_amazon_profile, ( $depth + 1 ), $amazon_wooCategories );
		}
	}
}



function get_details( $contract_id ) {
							   
	$ced_unified_plan_details = get_transient('ced_unified_plan_details') ;
	if ( $ced_unified_plan_details ) {
		return $ced_unified_plan_details;
	}
	
	require_once CED_AMAZON_DIRPATH . 'admin/amazon/lib/ced-amazon-billing-apis.php';

	$ced_billing_instance  = new Billing_Apis();
	$current_plan_response = $ced_billing_instance->getAmazonPlanById($contract_id);

	if ( isset( $current_plan_response['status'] ) && $current_plan_response['status'] ) {

		$responseBody = isset( $current_plan_response['data'] ) ? $current_plan_response['data'] : array() ;

		$subscriptionStatus = isset( $responseBody['status'] ) ? $responseBody['status'] : '';
		$end_date           = isset( $responseBody['end_date'] ) ? $responseBody['end_date'] : '' ;

		$data = array( 'plan_status' => $subscriptionStatus, 'end_date' => $end_date );

		set_transient( 'ced_unified_plan_details', $data, 3600 );
		return $data;

	} else {
		return array( 'plan_status' => '', 'end_date' => '' );
	}
}


function ced_woo_timestamp() {

	$current_offset = get_option( 'gmt_offset' );
	$tzstring       = get_option( 'timezone_string' );

	$check_zone_info = true;

	// Remove old Etc mappings. Fallback to gmt_offset.
	if ( str_contains( $tzstring, 'Etc/GMT' ) ) {
		$tzstring = '';
	}

	if ( empty( $tzstring ) ) { 

		$check_zone_info = false;
		if ( 0 == $current_offset ) {


			$tzstring = 'UTC';
			$target_timezone = new DateTimeZone( $tzstring); 
			$current_time_target_timezone = new DateTime('now', $target_timezone);

		} elseif ( $current_offset < 0 ) {

			$tzstring = 'UTC' ;
			$target_timezone_offset_seconds = $current_offset * 3600;


			$current_time_utc = new DateTime('now', new DateTimeZone('UTC'));
			$current_time_target_timezone = $current_time_utc->modify("$target_timezone_offset_seconds seconds");


		} else {
			$tzstring = 'UTC' ;
			$target_timezone_offset_seconds = $current_offset * 3600;

			$current_time_utc = new DateTime('now', new DateTimeZone('UTC'));
			$current_time_target_timezone = $current_time_utc->modify("+$target_timezone_offset_seconds seconds");

		}

	} else {

		$target_timezone = new DateTimeZone( $tzstring); 
		$current_time_target_timezone = new DateTime('now', $target_timezone);

	}

	
	// $current_time = new DateTime('now', $target_timezone);

	$formatted_time = $current_time_target_timezone->format('Y-m-d H:i:s');

	return !empty( $formatted_time ) ? $formatted_time : gmdate('Y-m-d H:i:s');
}