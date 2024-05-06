<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil as CedAmazonHOPS;

$file = CED_AMAZON_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $file ) ) {
	require_once $file;
}

$create_amz_order_hops = false;

if ( CedAmazonHOPS::custom_orders_table_usage_is_enabled() ) {
	$create_amz_order_hops = true;
}


$actions   = array();
$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';


$active_marketplace              = array();
$active_marketplace['user_id']   = $user_id;
$active_marketplace['seller_id'] = $seller_id;

update_option( 'ced_amz_active_marketplace', $active_marketplace );

$seller_loc_arr = explode( '|', $seller_id );
$mp_location    = isset( $seller_loc_arr['0'] ) ? $seller_loc_arr['0'] : '';

$uploadedProductArgs = array(
	'post_type'           => 'product',
	'post_status'         => 'publish',
	'ignore_sticky_posts' => 1,
	'meta_key'            => 'ced_amazon_product_asin_' . $mp_location,
	'meta_compare'        => '=',
);

$uploadedProductsResults = new \WP_Query( $uploadedProductArgs );
$uploadedProducts        = $uploadedProductsResults->found_posts;

$totalProductArgs = array(
	'post_type'           => 'product',
	'post_status'         => 'publish',
	'ignore_sticky_posts' => 1,
);

$totalProductsResults = new \WP_Query( $totalProductArgs );
$totalProducts        = $totalProductsResults->found_posts;


global $wpdb;

if ( !empty($mp_location) ) {

	if ( $create_amz_order_hops ) {
	
		$totalOrders = wc_get_orders(
			array( 
				'orderby' => 'date',
				'order' => 'DESC',
				'return' => 'ids',
				'limit'  => -1,
				'status' => array_keys( wc_get_order_statuses() ),
				'meta_query' => array(
					array(
						'key'        => 'ced_amazon_order_countory_code',
						'value'      => $mp_location,
						'comparison' => 'LIKE',
					),
				),
			)
			
		);

		$totalOrders = count( $totalOrders );

		$cancelledOrders = wc_get_orders(
			array( 
				'orderby' => 'date',
				'order' => 'DESC',
				'return' => 'ids',
				'limit'  => -1,
				'status' => array( 'wc-cancelled' ),
				'meta_query' => array(
					array(
						'key'        => 'ced_amazon_order_countory_code',
						'value'      => $mp_location,
						'comparison' => 'LIKE',
					),
				),
			)
			
		);

		$cancelledOrders = count( $cancelledOrders );


		global $wpdb;
		$totalrevenue = 0.00;
		
		$sql_results = wc_get_orders(

			array( 
				'orderby' => 'date',
				'order' => 'DESC',
				'return' => 'ids',
				'limit'  => -1,
				'status' => array( 'wc-completed', 'wc-processing' ),
				'meta_query' => array(
					array(
						'key'        => 'ced_amazon_order_countory_code',
						'value'      => $mp_location,
						'comparison' => 'LIKE',
					),
				),
			)
			
		);
		
		
		if ( is_array( $sql_results ) && isset( $sql_results ) ) {
			
			if ( ! $sql_results ) {
				$totalrevenue = 0.00;
			}
		
			$totalrevenue = array_map(
				function ( $id ) {
					$order = wc_get_order( $id );
					return $order->get_total();
				},
				$sql_results
			);
			$totalrevenue = array_sum( $totalrevenue );
		
		}


	} else {

		$totalOrdersArgs = array(
			'post_type'    => 'shop_order',
			'numberposts'  => '-1',
			'post_status'  => array_keys( wc_get_order_statuses() ),
			'meta_key'     => 'ced_amazon_order_countory_code',
			'meta_compare' => '=',
			'meta_value'   => $mp_location,
		);
		
		$totalOrdersResults = new \WP_Query( $totalOrdersArgs );
		$totalOrders        = $totalOrdersResults->found_posts;
		
		
		$cancelledOrdersArgs = array(
			'post_type'    => 'shop_order',
			'meta_key'     => 'ced_amazon_order_countory_code',
			'meta_compare' => '=',
			'meta_value'   => $mp_location,
			'numberposts'  => '-1',
			'post_status'  => array( 'wc-cancelled' ),
		);
		
		$cancelledOrdersResults = new \WP_Query( $cancelledOrdersArgs );
		$cancelledOrders        = $cancelledOrdersResults->found_posts;

		$meta_key1    = 'ced_amazon_order_countory_code';
		$meta_key2    = '_order_total';
		$totalrevenue = 0.00;

		$sql_results = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM wp_postmeta WHERE meta_key='ced_amazon_order_countory_code' AND meta_value=%s AND post_id IN ( SELECT ID FROM wp_posts  WHERE post_type = 'shop_order' AND post_status IN ('wc-completed'))", $mp_location ), ARRAY_A );

		if ( is_array( $sql_results ) && isset( $sql_results ) ) {
			$ids = array_column( $sql_results, 'post_id' );
			if ( ! $ids ) {
				$totalrevenue = 0.00;
			}

			$totalrevenue = array_map(
				function ( $id ) {
					$order = wc_get_order( $id );
					return $order->get_total();
				},
				$ids
			);
			$totalrevenue = array_sum( $totalrevenue );

		}
		


	}


} elseif ( $create_amz_order_hops ) {


		$totalOrders = wc_get_orders(
			array( 
				'orderby' => 'date',
				'order' => 'DESC',
				'return' => 'ids',
				'limit'  => -1,
				'status' => array_keys( wc_get_order_statuses() ),
				'meta_query' => array(
					array(
						'key'        => 'ced_amazon_order_countory_code',
						// 'value'      => $mp_location,
						'comparison' => 'LIKE',
					),
				),
			)
			
		);

		$totalOrders = count( $totalOrders );


		$cancelledOrders = wc_get_orders(
			array( 
				'orderby' => 'date',
				'order' => 'DESC',
				'return' => 'ids',
				'limit'  => -1,
				'status' => array( 'wc-cancelled' ),
				'meta_query' => array(
					array(
						'key'        => 'ced_amazon_order_countory_code',
						// 'value'      => $mp_location,
						'comparison' => 'LIKE',
					),
				),
			)
			
		);

		$cancelledOrders = count( $cancelledOrders );


		global $wpdb;
		$totalrevenue = 0.00;

		$sql_results = wc_get_orders(

			array( 
				'orderby' => 'date',
				'order' => 'DESC',
				'return' => 'ids',
				'limit'  => -1,
				'status' => array( 'wc-completed', 'wc-processing' ),
				'meta_query' => array(
					array(
						'key'        => 'ced_amazon_order_countory_code',
						// 'value'      => $mp_location,
						'comparison' => 'LIKE',
					),
				),
			)
			
		);
		
		
	if ( is_array( $sql_results ) && isset( $sql_results ) ) {
			
		if ( ! $sql_results ) {
			$totalrevenue = 0.00;
		}
		
		$totalrevenue = array_map(
			function ( $id ) {
				$order = wc_get_order( $id );
				return $order->get_total();
			},
			$sql_results
		);
		$totalrevenue = array_sum( $totalrevenue );
		
	}

} else {

	$totalOrdersArgs = array(
		'post_type'    => 'shop_order',
		'numberposts'  => '-1',
		'post_status'  => array_keys( wc_get_order_statuses() ),
		'meta_key'     => 'ced_amazon_order_countory_code',
			
	);
		
	$totalOrdersResults = new \WP_Query( $totalOrdersArgs );
	$totalOrders        = $totalOrdersResults->found_posts;
		
		
	$cancelledOrdersArgs = array(
		'post_type'    => 'shop_order',
		'meta_key'     => 'ced_amazon_order_countory_code',
		'numberposts'  => '-1',
		'post_status'  => array( 'wc-cancelled' ),
	);
		
	$cancelledOrdersResults = new \WP_Query( $cancelledOrdersArgs );
	$cancelledOrders        = $cancelledOrdersResults->found_posts;



	$meta_key1    = 'ced_amazon_order_countory_code';
	$meta_key2    = '_order_total';
	$totalrevenue = 0.00;

	$sql_results = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM wp_postmeta WHERE meta_key='ced_amazon_order_countory_code' AND post_id IN ( SELECT ID FROM wp_posts  WHERE post_type = 'shop_order' AND post_status IN ('wc-completed'))" ), ARRAY_A );

	if ( is_array( $sql_results ) && isset( $sql_results ) ) {
		$ids = array_column( $sql_results, 'post_id' );
		if ( ! $ids ) {
			$totalrevenue = 0.00;
		}

		$totalrevenue = array_map(
			function ( $id ) {
				$order = wc_get_order( $id );
				return $order->get_total();
			},
			$ids
		);
		$totalrevenue = array_sum( $totalrevenue );

	}
	
}

$totalOrders     = !empty( $totalOrders ) ? $totalOrders : 0;
$cancelledOrders = !empty( $cancelledOrders ) ? $cancelledOrders : 0;




$totalrevenue = number_format( $totalrevenue, 2, '.', '' );


?>

	<div class="woocommerce-progress-form-wrapper">
		<div class="wc-progress-form-content">
			<header>
				<h2><?php echo esc_html__( 'Product Stats', 'amazon-for-woocommerce' ); ?></h2>
				<p><?php echo esc_html__( "Track your product listing status on the go. Click on 'View all products' button to see the product details.", 'amazon-for-woocommerce' ); ?></p>
				<div class="woocommerce-dashboard__store-performance">
					<div role="menu" aria-orientation="horizontal" aria-label="Performance Indicators"
						aria-describedby="woocommerce-summary-helptext-87">
						<ul class="woocommerce-summary has-3-items ced-woocommerce-summary">
							<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span data-wp-c16t="true"
											data-wp-component="Text"
											class="components-truncate components-text css-jfofvs e19lxcc00"><?php echo esc_html__( 'Total Products', 'amazon-for-woocommerce' ); ?> </span></div>
									<div class="woocommerce-summary__item-data">
										<div class="woocommerce-summary__item-value"><span data-wp-c16t="true"
												data-wp-component="Text"
												class="components-truncate components-text css-2x4s0q e19lxcc00"><?php echo esc_html__( $totalProducts, 'amazon-for-woocommerce' ); ?></span>
										</div>
									</div>
								</a>
							</li>
							<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span data-wp-c16t="true"
											data-wp-component="Text"
											class="components-truncate components-text css-jfofvs e19lxcc00"><?php echo esc_html__( 'Uploaded Products', 'amazon-for-woocommerce' ); ?>
											</span></div>
									<div class="woocommerce-summary__item-data">
										<div class="woocommerce-summary__item-value"><span data-wp-c16t="true"
												data-wp-component="Text"
												class="components-truncate components-text css-2x4s0q e19lxcc00"><?php echo esc_html__( $uploadedProducts, 'amazon-for-woocommerce' ); ?></span>
										</div>
									</div>
								</a>
							</li>
							<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">

									

								</a>
							</li>
						</ul>
					</div>
				</div>
			</header>
			<div class="wc-actions">
				<a style="float: right;" 
				href="<?php echo esc_url( get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=products-view&user_id=' . $user_id . '&seller_id=' . $seller_id ); ?>"
				class="components-button is-primary">View all products</a>
			</div>
		</div>
	</div>


	<div class="woocommerce-progress-form-wrapper">
		<div class="wc-progress-form-content">
			<header>
				<h2> <?php echo esc_html__( 'Order Stats', 'amazon-for-woocommerce' ); ?></h2>
				<p><?php echo esc_html__( "Keep track of your order's journey. Click on 'View all orders' to see the order details.", 'amazon-for-woocommerce' ); ?></p>
				<div class="woocommerce-dashboard__store-performance">
					<div role="menu" aria-orientation="horizontal" aria-label="Performance Indicators"
						aria-describedby="woocommerce-summary-helptext-87">
						<ul class="woocommerce-summary has-3-items ced-woocommerce-summary">
							<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span data-wp-c16t="true"
											data-wp-component="Text"
											class="components-truncate components-text css-jfofvs e19lxcc00"><?php echo esc_html__( 'Total Orders', 'amazon-for-woocommerce' ); ?></span></div>
									<div class="woocommerce-summary__item-data">
										<div class="woocommerce-summary__item-value"><span data-wp-c16t="true"
												data-wp-component="Text"
												class="components-truncate components-text css-2x4s0q e19lxcc00"><?php echo esc_html__( $totalOrders, 'amazon-for-woocommerce' ); ?></span>
										</div>
									</div>
								</a>
							</li>
							<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span data-wp-c16t="true"
											data-wp-component="Text"
											class="components-truncate components-text css-jfofvs e19lxcc00"><?php echo esc_html__( 'Cancelled Orders', 'amazon-for-woocommerce' ); ?></span></div>
									<div class="woocommerce-summary__item-data">
										<div class="woocommerce-summary__item-value"><span data-wp-c16t="true"
												data-wp-component="Text"
												class="components-truncate components-text css-2x4s0q e19lxcc00"><?php echo esc_html__( $cancelledOrders, 'amazon-for-woocommerce' ); ?></span>
										</div>
									</div>
								</a>
							</li>
							<li class="woocommerce-summary__item-container">
								<a href="#" class="woocommerce-summary__item" role="menuitem" data-link-type="wc-admin">
									<div class="woocommerce-summary__item-label"><span data-wp-c16t="true"
											data-wp-component="Text"
											class="components-truncate components-text css-jfofvs e19lxcc00"><?php echo esc_html__( 'Revenue', 'amazon-for-woocommerce' ); ?></span>
									</div>
									<div class="woocommerce-summary__item-data">
										<div class="woocommerce-summary__item-value">
											<span data-wp-c16t="true" data-wp-component="Text"
												class="components-truncate components-text css-2x4s0q e19lxcc00"> 
												<?php 

												$location_for_seller = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';

												// newly added code starts

												$global_setting_data       = get_option( 'ced_amazon_global_settings', false );
												$ced_amazon_order_currency = ! empty( $global_setting_data[ $location_for_seller ]['ced_amazon_order_currency'] ) ? $global_setting_data[ $location_for_seller ]['ced_amazon_order_currency'] : '';
						
												if ( !empty( $ced_amazon_order_currency ) && '1' == $ced_amazon_order_currency ) {
												
													$symbol = get_woocommerce_currency_symbol();
						
												} else {
													$ced_amazon_currency_code =  get_option( 'ced_amazon_currency_code', '');
													$symbol                   = get_woocommerce_currency_symbol( $ced_amazon_currency_code );
												}

												// newly added code end
												
												?>

												<span class="ced_amz_curr_sym" > <?php echo esc_attr($symbol); ?> </span>
												<span class="ced_amz_curr_val" > <?php echo esc_html__( $totalrevenue, 'amazon-for-woocommerce' ); ?> </span>
											
												
											</span>
										</div>
									</div>
								</a>
							</li>
						</ul>
					</div>
				</div>
			</header>
			<div class="wc-actions">
				<a style="float: right;" 
				href="<?php echo esc_url( get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=orders-view&user_id=' . $user_id . '&seller_id=' . $seller_id ); ?>"
				class="components-button is-primary">&nbsp View all orders &nbsp</a>
			</div>
		</div>
	</div>
