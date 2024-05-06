(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	var ajax_url   = ced_walmart_admin_obj.ajax_url;
	var ajax_nonce = ced_walmart_admin_obj.ajax_nonce;
	var store_id   = ced_walmart_admin_obj.store_id;
	var message , classes , notice , parsed_response , can_ajax , status;

	// Conncet Account Accordian
	$( document ).on(
		'click',
		'.woocommerce-importer-done-view-errors-walmart',
		function(){
			$( '.wc-importer-error-log-walmart' ).slideToggle();
			return false;
		}
	);

	// Account Switcher

	$( document ).on(
		'change',
		'#ced_walmart_switch_account',
		function() {
			let url = $( this ).val();
			if ( url != "" ) {
				window.location.href = url;
			}
		}
	);

	// Delete Account Modal Handling

	$( document ).on(
		'click',
		'#ced_walmart_disconnect_account',
		function() {
			let store_id = $( this ).data( 'store-id' );
			if ( store_id == "" ) {
				return;
			}
			$( "#ced-walmart-delete-account" ).attr( 'data-store-id' , store_id );
			$( '#ced-walmart-disconnect-account-modal' ).show();
		}
	);
	$( document ).on(
		'click',
		'.ced-close-button',
		function() {

			$( '#ced-walmart-disconnect-account-modal' ).hide();
		}
	);

	$( document ).on(
		'click',
		'#ced-walmart-delete-account',
		function() {
			$( this ).prev().show();
			$( this ).prev().css( 'visibility','visible' );
			let store_id = $( this ).data( 'store-id' );
			console.log( store_id );
			$.ajax(
				{
					url : ajax_url,
					data : {
						ajax_nonce   : ajax_nonce,
						store_id    : store_id,
						action : 'ced_walmart_delete_account',
					},
					type : 'POST',
					success : function( response ){
						$( '#ced-walmart-delete-account' ).prev().css( 'visibility','hidden' );
						var html = '<div class="notice notice-success"><p>Account deleted successfully.</p></div>';
						$( ".ced-button-wrap-popup" ).html( "" );
						$( '.ced_walmart_error' ).html( html );
						window.setTimeout( function() {window.location.reload();},3000 );
					}
				}
			);
		}
	);

	// Li selection

	$( document ).ready(
		function() {
			$( '.ced_walmart_category' ).click(
				function() {
					$( this ).find( 'input[type="radio"]' ).prop( 'checked', true );
					let catId = $( this ).find( 'input[type="radio"]' ).val();
					$( "#ced_walmart_breadcrumb" ).text( catId );
					$( "#ced_walmart_breadcrumb" ).show();

					$.ajax(
						{
							url : ajax_url,
							data : {
								ajax_nonce   : ajax_nonce,
								catId    : catId,
								action : 'ced_walmart_append_category_attr',
							},
							type : 'POST',
							success : function( response ){

								$( '.ced-settings-body' ).html( "" );
								$( '.ced-settings-body' ).html( response );
								jQuery( "select" ).select2();

							}
						}
					);
				}
			);
		}
	);

	$( document ).ready(
		function() {
			$( '.image-variable-item img', this ).on(
				'mouseenter',
				function(){
					var img_src = jQuery( this ).attr( 'src' );
					$( 'div.pro-large-img img' ).attr( 'src', img_src );
				}
			);
		}
	);

	 // For Overall Quality Circle Progress Bar
	$(
		function() {
			$( ".progress-circle" ).each(
				function() {

					var value = $( this ).attr( 'data-value' );
					var left  = $( this ).find( '.progress-circle-left .progress-circle-bar' );
					var right = $( this ).find( '.progress-circle-right .progress-circle-bar' );

					if (value > 0) {
						if (value <= 50) {
							right.css( 'transform', 'rotate(' + percentageToDegrees( value ) + 'deg)' )
						} else {
							right.css( 'transform', 'rotate(180deg)' )
							left.css( 'transform', 'rotate(' + percentageToDegrees( value - 50 ) + 'deg)' )
						}
					}

				}
			)
			function percentageToDegrees(percentage) {
				return percentage / 100 * 360
			}

		}
	);

	$( document ).on(
		'click',
		'.ced_walmart_preview_product_popup_main_wrapper',
		function(){
			$( this ).removeClass( 'show' );
		}
	);

	$( document ).on(
		'click',
		'#ced_walmart_insight_refresh',
		function(){
			let store_id = $( this ).data( 'store-id' );
			ced_walmart_show_loader()
			$.ajax(
				{
					url : ajax_url,
					data :{
						ajax_nonce : ajax_nonce,
						store_id : store_id,
						action : 'ced_walmart_refresh_insights_keys',
					},
					type : 'POST',
					success: function( response ) {
						ced_walmart_hide_loader()
						parsed_response = jQuery.parseJSON( response );
						message         = parsed_response.message;
						status          = parsed_response.status;
						ced_walmart_display_notice( message , status );
					}
				}
			);
		}
	);

	// Fetching and appending data for product level listing quality
	$( document ).on(
		'click',
		'.pro-insight',
		function(){
			ced_walmart_show_loader()
			var product_id = $( this ).data( 'id' );
			$( '.modal-content .modal-body-data' ).empty();

			$.ajax(
				{
					url : ajax_url,
					data :{
						ajax_nonce : ajax_nonce,
						product_id:product_id,
						action : 'ced_walmart_save_listing_quality_for_product',
					},
					type : 'POST',
					success: function(response) {

						ced_walmart_hide_loader()
						$( '.modal-content .modal-body-data' ).append( jQuery.parseJSON( response ) );
						$( '#insight' ).modal( 'show' );
						$( ".product-progress-circle" ).each(
							function() {
								var value = $( this ).attr( 'data-value' );
								var left  = $( this ).find( '.product-progress-circle-left .product-progress-circle-bar' );
								var right = $( this ).find( '.product-progress-circle-right .product-progress-circle-bar' );
								if (value > 0) {
									if (value <= 50) {
										right.css( 'transform', 'rotate(' + percentageToDegrees( value ) + 'deg)' )
									} else {
										right.css( 'transform', 'rotate(180deg)' )
										left.css( 'transform', 'rotate(' + percentageToDegrees( value - 50 ) + 'deg)' )
									}
								}

							}
						)

						function percentageToDegrees(percentage) {
							return percentage / 100 * 360
						}
					}
				}
			);

		}
	)

	// For Unpublished item modal

	$( document ).on(
		'click',
		'#ced_show_unpublished_items',
		function(){
			ced_walmart_show_loader()
			$( '.modal-content .modal-body-unpublished' ).empty()
			$.ajax(
				{
					url : ajax_url,
					data :{
						ajax_nonce : ajax_nonce,
						action : 'ced_walmart_fetch_unpublished_items',
					},
					type : 'POST',
					success: function(response) {
						ced_walmart_hide_loader()
						$( '.modal-content .modal-body-unpublished' ).append( jQuery.parseJSON( response ) );
						$( '#unpublished_items' ).modal( 'show' );
					}
				}
			);

		}
	)

	/*-------------------Toggle Chile Elements-----------------------*/

	// For walmart  category Selection

	$( document ).on(
		'change',
		'#ced_walmart_category',
		function(){
			let value = $( this ).val();
			let catId = $( this ).attr( "data-store-category-id" );
			ced_walmart_show_loader()
			$.ajax(
				{
					url : ajax_url,
					data : {
						ajax_nonce : ajax_nonce,
						action : 'ced_walmart_save_cat',
						value:value,
						catId:catId,
					},
					type : 'POST',
					success: function(response) {
						ced_walmart_hide_loader()
					}
				}
			);

		}
	);

	$( document ).on(
		'keyup' ,
		'#ced_walmart_search_product_name' ,
		function() {
			var keyword = $( this ).val();
			if ( keyword.length < 3 ) {
				var html = '';
				html    += '<li>Please enter 3 or more characters.</li>';
				$( document ).find( '.ced-walmart-search-product-list' ).html( html );
				$( document ).find( '.ced-walmart-search-product-list' ).show();
				return;
			}
			$.ajax(
				{
					url : ajax_url,
					data : {
						ajax_nonce : ajax_nonce,
						keyword : keyword,
						action : 'ced_walmart_search_product_name',
					},
					type:'POST',
					success : function( response ) {
						parsed_response = jQuery.parseJSON( response );
						$( document ).find( '.ced-walmart-search-product-list' ).html( parsed_response.html );
						$( document ).find( '.ced-walmart-search-product-list' ).show();
					}
				}
			);
		}
	);

	$( document ).on(
		'click' ,
		'.ced_walmart_searched_product' ,
		function() {
			ced_walmart_show_loader()
			var post_id = $( this ).data( 'post-id' );
			$.ajax(
				{
					url : ajax_url,
					data : {
						ajax_nonce : ajax_nonce,
						post_id : post_id,
						action : 'ced_walmart_get_product_metakeys',
					},
					type:'POST',
					success : function( response ) {
						ced_walmart_hide_loader()
						parsed_response = jQuery.parseJSON( response );
						$( document ).find( '.ced-walmart-search-product-list' ).hide();
						$( ".ced_walmart_render_meta_keys_content" ).html( parsed_response.html );
						$( ".ced_walmart_render_meta_keys_content" ).show();
					}
				}
			);
		}
	);

	$( document ).on(
		'change',
		'.ced_walmart_meta_key',
		function(){
			ced_walmart_show_loader()
			var metakey = $( this ).val();
			var operation;
			if ( $( this ).is( ':checked' ) ) {
				operation = 'store';
			} else {
				operation = 'remove';
			}

			$.ajax(
				{
					url : ajax_url,
					data : {
						ajax_nonce : ajax_nonce,
						action : 'ced_walmart_process_metakeys',
						metakey : metakey ,
						operation : operation,
					},
					type : 'POST',
					success: function(response)
				{
						ced_walmart_hide_loader()
					}
				}
			);
		}
	);

	$( document ).on(
		'click' ,
		'.ced_walmart_navigation' ,
		function() {
			ced_walmart_show_loader()
			var page_no = $( this ).data( 'page' );
			$( '.ced_walmart_metakey_body' ).hide();
			window.setTimeout( function() {$( '.ced_walmart_loader' ).hide()},500 );
			$( document ).find( '.ced_walmart_metakey_list_' + page_no ).show();
		}
	);

	$( document ).on(
		'click' ,
		'#ced_walmart_fetch_orders' ,
		function() {
			ced_walmart_show_loader()
			$.ajax(
				{
					url : ajax_url,
					data : {
						ajax_nonce : ajax_nonce,
						action : 'ced_walmart_get_orders_manual',
						store_id : store_id,
					},
					type : 'POST',
					success: function(response)
				{
						ced_walmart_hide_loader()
						parsed_response = jQuery.parseJSON( response );
						message         = parsed_response.message;
						status          = parsed_response.status;
						ced_walmart_display_notice( message , status );
					}

				}
			);
		}
	);

	$( document ).on(
		'click' ,
		'#ced_walmart_product_error' ,
		function(){
			var id = $( this ).attr( "data-id" );
			$( "#ced_walmart_error_data" + id ).toggle();
		}
	);

	$( document ).on(
		'click' ,
		'#ced_walmart_wfs_product_error' ,
		function(){
			var id = $( this ).attr( "data-id" );
			$( "#ced_walmart_error_data_wfs" + id ).toggle();
		}
	);

	$( document ).on(
		'click' ,
		'#ced_walmart_ack_action' ,
		function() {
			ced_walmart_show_loader()
			var order_id = $( this ).data( 'order_id' );
			$.ajax(
				{
					url : ajax_url,
					data : {
						ajax_nonce : ajax_nonce,
						action : 'ced_walmart_acknowledge_order',
						order_id :order_id,
					},
					type : 'POST',
					success: function(response)
				{
						ced_walmart_hide_loader()
						parsed_response = jQuery.parseJSON( response );
						message         = parsed_response.message;
						status          = parsed_response.status;
						ced_walmart_display_notice( message , status );
					}

				}
			);
		}
	);

	$( document ).on(
		'click' ,
		'#ced_walmart_cancel_action' ,
		function() {
			ced_walmart_show_loader()
			var order_id = $( this ).data( 'order_id' );
			$.ajax(
				{
					url : ajax_url,
					data : {
						ajax_nonce : ajax_nonce,
						action : 'ced_walmart_cancel_order',
						order_id :order_id,
					},
					type : 'POST',
					success: function(response)
				{
						ced_walmart_hide_loader()
						parsed_response = jQuery.parseJSON( response );
						message         = parsed_response.message;
						status          = parsed_response.status;
						ced_walmart_display_notice( message , status );
					}

				}
			);
		}
	);

	$( document ).on(
		'click' ,
		'#ced_walmart_shipment_submit'  ,
		function(){

			var walmart_order_item_count = jQuery( this ).data( "items" );
			var store_id                 = $( this ).data( "storeid" );
			var walmart_order_id         = jQuery( "#walmart_orderid" ).val();
			var woocommerce_orderid      = jQuery( "#woocommerce_orderid" ).val();
			var walmart_carrier          = jQuery( "#ced_walmart_carrier" ).val();
			var walmart_methodCode       = jQuery( "#ced_walmart_methodCode" ).val();
			var walmart_tracking         = jQuery( "#ced_walmart_tracking" ).val();
			var walmart_tracking_url     = jQuery( "#ced_walmart_tracking_url" ).val();
			var walmart_ship_date        = jQuery( "#ced_walmart_ship_date" ).val();
			var walmart_ex_deliverydate  = jQuery( "#ced_walmart_ex_deliverydate" ).val();

			var shipmonth = parseInt( walmart_ship_date.substring( 5,8 ) );
			var shipdate  = parseInt( walmart_ship_date.substring( 8,11 ) );

			if (walmart_tracking == "") {
				alert( "Please Enter Tracking Number" );
				return;
			}

			if (walmart_ship_date == "") {
				alert( "Please enter Ship to date" );
				return;
			}

			var order_items = [];

			for (var i = 0; i < walmart_order_item_count;  i++) {
				var order_lineNumber     = $( "#lineNumber_" + i ).val();
				var product_sku          = $( "#sku_" + i ).val();
				var requested_quantity   = $( "#qty_" + i ).val();
				var cancel_quantity      = $( "#can_" + i ).val();
				var ship_quantity        = $( "#ship_" + i ).val();
				var avail_check_quantity = $( "#avail_" + i ).val();

				if (ship_quantity == "") {
					alert( "Item Sku: " + product_sku + ". Please enter Ship Quantity" );
					return;
				}
				if (cancel_quantity == "") {
					cancel_quantity = 0;
				}

				if (ship_quantity > (requested_quantity - cancel_quantity)) {
					alert( "Item Sku: " + product_sku + " .Total quantity available for shipping/cancellation : " + (avail_check_quantity) );
					return;
				}
				if (parseInt( ship_quantity ) < 0 || parseInt( cancel_quantity ) < 0) {
					alert( "please enter Quantity to Ship or Quantity to Cancel greater than zero" );
					return;
				}

				if ((parseInt( ship_quantity ) + parseInt( cancel_quantity )) > parseInt( avail_check_quantity ) ) {
					alert( "Error in Item Sku: " + product_sku + " Please provide either shipping quantity or cancel quantity for item" );
					return;
				}

				var product_data = {
					'product_sku' : product_sku,
					'requested_quantity' : requested_quantity,
					'cancel_quantity' : cancel_quantity,
					'address' : jQuery( "#address_" + i ).val(),
					'rma_' : jQuery( "#rma_" + i ).val(),
					'days_return' : jQuery( "#days_return_" + i ).val(),
					'ship_quantity' : ship_quantity,
					'avail' : jQuery( "#avail_" + i ).val(),
					'order_lineNumber' : order_lineNumber
				};
				order_items.push( product_data );
			}

			if (walmart_carrier == 'USPS First Class Mail' || walmart_carrier == 'UPS Ground') {
				walmart_carrier = 'UPS';
			}
			ced_walmart_show_loader();

			$.ajax(
				{
					url : ajax_url,
					data : {
						ajax_nonce : ajax_nonce,
						action : 'ced_walmart_shipment_order',
						order_id :walmart_order_id,
						store_id : store_id,
						items : order_items,
						carrier:walmart_carrier,
						methodCode:walmart_methodCode,
						order:woocommerce_orderid,
						tracking:walmart_tracking,
						tracking_url:walmart_tracking_url,
						ship_todate:walmart_ship_date,
					},
					type : 'POST',
					success: function(response)
				{
						ced_walmart_hide_loader()
						parsed_response = jQuery.parseJSON( response );
						message         = parsed_response.message;
						status          = parsed_response.status;
						ced_walmart_display_notice( message , status );
					}

				}
			);

		}
	);

	// Shipping part

	$( document ).on(
		'click' ,
		'#ced_walmart_get_shipping_template' ,
		function() {
			ced_walmart_show_loader()
			$.ajax(
				{
					url : ajax_url,
					data : {
						ajax_nonce : ajax_nonce,
						action : 'ced_walmart_get_shipping_templates',
						store_id : store_id,
					},
					type : 'POST',
					success: function(response)
				{
						ced_walmart_hide_loader()
						parsed_response = jQuery.parseJSON( response );
						message         = parsed_response.message;
						status          = parsed_response.status;
						ced_walmart_display_notice( message , status );
					}

				}
			);
		}
	);

	$( document ).on(
		'click' ,
		'#ced_walmart_create_shipping_template' ,
		function() {
			$( '#ced_shipping_types' ).toggle(
				function() {
					$( '.menu' ).slideDown( "slow" );
				},
				function() {
					$( '.menu' ).slideUp( "slow" );
				}
			);
		}
	);

	// For Standard Template table Coloumn Changes according to Rate Model type

	$( document ).on(
		'change',
		'input[name="rate_model_type"]',
		function(){
			var rate_model_type = $( 'input[name="rate_model_type"]:checked' ).val();
			if ('TIERED_PRICING' == rate_model_type) {
				$( "<th> <label> Order Price Range <label> </th>'" ).insertAfter( $( "#ced_heading_transitTime,#ced_heading_transitTime_standard,#ced_heading_transitTime_twoday" ) );
				$( "#ced_tiered_pricing" ).css( "display","block" );
			} else {
				$( "#ced_heading_transitTime,#ced_heading_transitTime_standard,#ced_heading_transitTime_twoday" ).next( 'th' ).remove();
				$( "#ced_tiered_pricing" ).css( "display","none" );
			}

		}
	);

	// For Paid Standard Template table Coloumn Changes according to Rate Model type

	$( document ).on(
		'change',
		'input[name="rate_model_type_paid"]',
		function(){
			var rate_model_type = $( 'input[name="rate_model_type_paid"]:checked' ).val();
			if ('TIERED_PRICING' == rate_model_type) {
				$( "<th> <label> Order Price Range <label> </th>'" ).insertAfter( $( "#ced_heading_transitTime_paid" ) );
				$( "#ced_tiered_pricing_paid" ).css( "display","block" );
				$( "#ced_tiered_pricing_paid" ).next( 'td' ).remove();
				$( "<td> <input type='number' id='ced_paid_rate_with_tiered' name='ced_paid_rate_with_tiered' value='0'> </td>" ).insertAfter( $( "#ced_tiered_pricing_paid" ) );
			} else {
				$( "#ced_heading_transitTime_paid" ).next( 'th' ).remove();
				$( "#ced_tiered_pricing_paid" ).next( 'td' ).remove();
				$( " <td> <input type='number' id='ced_paid_rate' value='0'> <br> Shipping & Handling  <br> + <br> <input type='number' id='ced_paid_shipcharge' value='0'> <br> <select id='ced_standard_shipcharge_name'><option value='chargePerItem'>chargePerItem</option><option value='chargePerWeight'> chargePerWeight </option></select> </td>" ).insertAfter( $( "#ced_tiered_pricing_paid" ) );
				$( "#ced_tiered_pricing_paid" ).css( "display","none" );
			}

		}
	);

	$( document ).on(
		'click',
		'#ced_walmart_save_shipping_template',
		function(){

			var templateName = $( "#ced_walmart_shipping_template_name" ).val();
			var store_id     = $( this ).data( 'storeID' );

			if (templateName == '' || ! isNaN( templateName ) ) {
				$( '#ced_walmart_shipping_template_name' ).css( 'border-color', 'red' );
				return;
			} else {
				$( '#ced_walmart_shipping_template_name' ).css( 'border-color', '' );

			}

			var type            = $( "#ced_walmart_shipping_template_type" ).val();
			var rate_model_type = $( 'input[name="rate_model_type"]:checked' ).val();
			var rateModelType   = '';
			if ('TIERED_PRICING' == rate_model_type) {
				rateModelType = "TIERED_PRICING";
			} else {
				rateModelType = "PER_SHIPMENT_PRICING";
			}
			var status                 = $( "#ced_template_status option:selected" ).val();
			var shippingMethodValue    = CedValueShippingmethod();
			var shippingMethodStandard = CedStandardShippingMethod();
			var shippingMethod2day     = Ced2dayShippingMethod();
			ced_walmart_show_loader()
			$.ajax(
				{
					url : ajax_url,
					data : {
						ajax_nonce : ajax_nonce,
						action : 'ced_walmart_save_shipping_template',
						templateName:templateName,
						store_id:store_id,
						type:type,
						rateModelType:rateModelType,
						status:status,
						shippingMethodValue:shippingMethodValue,
						shippingMethodStandard:shippingMethodStandard,
						shippingMethod2day:shippingMethod2day
					},
					type : 'POST',
					success: function(response)
				{
						ced_walmart_hide_loader()
						parsed_response = jQuery.parseJSON( response );
						message         = parsed_response.message;
						status          = parsed_response.status;
						ced_walmart_display_notice( message , status );
					}
				}
			);

		}
	)

	// Add Paid Standard Template

	$( document ).on(
		'click',
		'#ced_walmart_save_shipping_template_paid',
		function(){

			var templateName = $( "#ced_walmart_shipping_template_name_paid" ).val();
			var store_id     = $( this ).data( 'storeID' );

			if (templateName == '' || ! isNaN( templateName ) ) {
				$( '#ced_walmart_shipping_template_name_paid' ).css( 'border-color', 'red' );
				return;
			} else {
				$( '#ced_walmart_shipping_template_name_paid' ).css( 'border-color', '' );

			}

			var type            = $( "#ced_walmart_shipping_template_type_paid" ).val();
			var rate_model_type = $( 'input[name="rate_model_type_paid"]:checked' ).val();
			var rateModelType   = '';
			var transitTime     = $( "[name='ced_paid_transit_time_data']" ).val();
			if ('TIERED_PRICING' == rate_model_type) {
				rateModelType  = "TIERED_PRICING";
				var minLimit   = $( "#ced_paid_min" ).val();
				var maxLimit   = $( "#ced_paid_max" ).val();
				var shipCharge = $( "#ced_paid_rate_with_tiered" ).val();
			} else {
				rateModelType             = "PER_SHIPMENT_PRICING";
				var rate                  = $( "#ced_paid_rate" ).val();
				var shipChargePerShipping = $( "#ced_paid_shipcharge" ).val();
				var shipChargeName        = $( "#ced_standard_shipcharge_name" ).val();
			}
			var status = $( "#ced_template_status_paid option:selected" ).val();
			ced_walmart_show_loader()
			$.ajax(
				{
					url : ajax_url,
					data : {
						ajax_nonce : ajax_nonce,
						action : 'ced_walmart_save_shipping_template_paid_standard',
						templateName:templateName,
						store_id:store_id,
						type:type,
						rateModelType:rateModelType,
						transitTime:transitTime,
						status:status,
						minLimit:minLimit,
						maxLimit:maxLimit,
						shipCharge:shipCharge,
						rate:rate,
						shipChargePerShipping:shipChargePerShipping,
						shipChargeName:shipChargeName
					},
					type : 'POST',
					success: function(response)
				{
						ced_walmart_hide_loader()
						parsed_response = jQuery.parseJSON( response );
						message         = parsed_response.message;
						status          = parsed_response.status;
						ced_walmart_display_notice( message , status );
					}
				}
			);

		}
	)

	var urlParams = new URLSearchParams( window.location.search );
	let section   = urlParams.get( 'section' );

	if ("shipping_template" == section) {
		$( document ).on(
			'click',
			'input[type=checkbox]',
			function(){
				if (this.checked) {
					$( this ).parents( 'li' ).children( 'input[type=checkbox]' ).prop( 'checked',true );
				}
				$( this ).parent().find( 'input[type=checkbox]' ).prop( 'checked',this.checked );
			}
		);
	}

	$( document ).on(
		'click',
		'#ced_state_show',
		function(e){
			let stateName = $( this ).attr( "data-val" );
			$( '#ced_substate_standard_' + stateName ).toggle();

		}
	);

	$( document ).on(
		'click',
		'#ced_state_2day_show',
		function(e){
			let stateName = $( this ).attr( "data-val" );
			$( '#ced_substate_2day_' + stateName ).toggle();
			e.stopPropagation();
		}
	);

	// Standard Template Rules

	var i = 1;

	$( document ).on(
		'click',
		'#ced_save_rule_standard',
		function(){
			var main = [];

			$( "input:checkbox[name=subregions]:checked" ).each(
				function(){
					if ($( this ).attr( "disabled" )) {
						return true;
					} else {
						$( this ).attr( "disabled", "disabled" );
					}
				}
			);

			$( "input:checkbox[name=state]:checked" ).each(
				function(){
					if ($( this ).attr( "disabled" )) {
						return true;
					} else {
						$( this ).attr( "disabled", "disabled" );
					}
				}
			);

			$( "input:checkbox[name=substate]:checked" ).each(
				function(){
					if ($( this ).attr( "disabled" )) {
						return true;
					} else {
						var subregionsCode = $( this ).attr( "data-subregionsCode" );
						var subregionsName = $( this ).attr( "data-subregionsName" );
						var stateCode      = $( this ).attr( "data-stateCode" );
						var stateName      = $( this ).attr( "data-stateName" );
						var substateCode   = $( this ).attr( "data-substateCode" );
						var substateName   = $( this ).attr( "data-substateName" );

						$( this ).attr( "disabled", "disabled" );

						main.push(
							{	regionCode:"C",
								regionName:"48 State",
								subRegions:{
									subRegionCode:subregionsCode,
									subRegionName:subregionsName,
									states:{
										stateCode:stateCode,
										stateName:stateName,
										stateSubregions:{
											stateSubregionCode:substateCode,
											stateSubregionName:substateName
										},
									},
								},
							}
						);
					}
				}
			);

			if (main.length === 0) {
				return;
			} else {

				$( 'input[name="rate_model_type"]' ).attr( "disabled", "disabled" );
				var checked_string = '';
				$( main ).each(
					function(index, el) {
						checked_string += el.subRegions.states.stateSubregions.stateSubregionName;
						checked_string += ',';
					}
				);
				var rate_model_type = $( 'input[name="rate_model_type"]:checked' ).val();
				var convertedData   = JSON.stringify( main );

				var tds = "<tr class='shipping_rule_standard' valign='top'>";
				tds    += "<td>  <input type='hidden' value='" + convertedData + "' name='ced_standard_region_data_field" + i + "'> " + checked_string + " </td>";
				tds    += "<td> <input type='hidden' value='STREET' name='ced_standard_address_data_field" + i + "'> STREET </td>";
				tds    += "<td> <select name ='ced_standard_transit_time_data" + i + "'><option value='3'> 3 days</option><option value='4'>4 days</option><option value='5'>5 days</option></select> </td>";
				if ('TIERED_PRICING' == rate_model_type) {
					tds += "<td> <input type='number' name='ced_standard_min" + i + "'  value='0'> <br> to <br> <input type='number' name='ced_standard_max" + i + "' value='-1'>  </td>";
					tds += "<td> <input type='number' name='ced_standard_rate" + i + "'  value='0'> </td>";
				} else {
					tds += "<td> <input type='number' name='ced_standard_rate" + i + "' value='0'> <br> Shipping & Handling  <br> + <br> <input type='number' name='ced_standard_shipcharge" + i + "' value='0'> <br> <select name ='ced_standard_shipcharge_name" + i + "'><option value='chargePerItem'>chargePerItem</option> <option value='chargePerWeight'> chargePerWeight </option></select>  </td>";
				}
				tds += "</tr>";

				$( "#ced_standard_shipping_table" ).append( tds );
				$( "#ced_popup_standard" ).css( "display", "none" );
				$( "#ced_standard_shipping_table" ).on(
					'click',
					'.ced_remove',
					function(){
						$( this ).parent().parent().remove();
					}
				);
				i++;

				$( '#ced_remove' ).css( "display", "block" );
			}

		}
	);

	$( document ).on(
		'click',
		'#ced_add_rule_standard',
		function(){
			$( "#ced_popup_standard" ).css( "display", "block" );
		}
	);

	$( document ).on(
		'click',
		'#ced_cancel_rule_standard',
		function(){
			$( "#ced_popup_standard" ).css( "display", "none" );
		}
	);

	// 2 Day Shipping Template Rules'

	var j = 1;

	$( document ).on(
		'click',
		'#ced_save_rule_2day',
		function(){
			var twoday = [];

			$( "input:checkbox[name=subregions_2day]:checked" ).each(
				function(){
					if ($( this ).attr( "disabled" )) {
						return true;
					} else {
						$( this ).attr( "disabled", "disabled" );
					}
				}
			);

			$( "input:checkbox[name=states_2day]:checked" ).each(
				function(){
					if ($( this ).attr( "disabled" )) {
						return true;
					} else {
						$( this ).attr( "disabled", "disabled" );
					}
				}
			);

			$( "input:checkbox[name=substate_2day]:checked" ).each(
				function(){
					if ($( this ).attr( "disabled" )) {
						return true;
					} else {
						var subregionsCode = $( this ).attr( "data-subregionsCode" );
						var subregionsName = $( this ).attr( "data-subregionsName" );
						var stateCode      = $( this ).attr( "data-stateCode" );
						var stateName      = $( this ).attr( "data-stateName" );
						var substateCode   = $( this ).attr( "data-substateCode" );
						var substateName   = $( this ).attr( "data-substateName" );

						$( this ).attr( "disabled", "disabled" );

						twoday.push(
							{	regionCode:"C",
								regionName:"48 State",
								subRegions:{
									subRegionCode:subregionsCode,
									subRegionName:subregionsName,
									states:{
										stateCode:stateCode,
										stateName:stateName,
										stateSubregions:{
											stateSubregionCode:substateCode,
											stateSubregionName:substateName
										},
									},
								},
							}
						);
					}
				}
			);

			if (twoday.length === 0) {
				return;
			} else {

				$( 'input[name="rate_model_type"]' ).attr( "disabled", "disabled" );
				var checked_string = '';
				$( twoday ).each(
					function(index, el) {
						checked_string += el.subRegions.states.stateSubregions.stateSubregionName;
						checked_string += ',';
					}
				);
				var rate_model_type = $( 'input[name="rate_model_type"]:checked' ).val();
				var convertedData   = JSON.stringify( twoday );

				var tds = "<tr class='shipping_rule_2day' valign='top'>";
				tds    += "<td>  <input type='hidden' value='" + convertedData + "' name='ced_2day_region_data_field" + j + "'> " + checked_string + " </td>";
				tds    += "<td> <input type='hidden' value='STREET' name='ced_2day_address_data_field" + j + "'> STREET </td>";
				tds    += "<td> 2 Day </td>";
				if ('TIERED_PRICING' == rate_model_type) {
					tds += "<td> $0.00 and up  </td>";
					tds += "<td>  Free Shipping</td>";
				} else {
					tds += "<td> Free Shipping</td>";
				}
				tds += "</tr>";

				$( "#ced_2day_shipping_table" ).append( tds );
				$( "#ced_popup_Two_day_standard" ).css( "display", "none" );
				$( "#ced_2day_shipping_table" ).on(
					'click',
					'.ced_remove',
					function(){
						$( this ).parent().parent().remove();
					}
				);
				i++;

				$( '#ced_remove_2day' ).css( "display", "block" );
			}

		}
	);

	$( document ).on(
		'click',
		'#ced_add_rule_2day',
		function(){
			$( "#ced_popup_Two_day_standard" ).css( "display", "block" );
		}
	);

	$( document ).on(
		'click',
		'#ced_cancel_rule_2day',
		function(){
			$( "#ced_popup_Two_day_standard" ).css( "display", "none" );
		}
	);

	// Fetch All fulfillment Centers

	$( document ).on(
		'click',
		"#ced_walmart_get_fulfillment_center",
		function(e){
			e.preventDefault();
			ced_walmart_show_loader()
			$.ajax(
				{
					url : ajax_url,
					data : {
						ajax_nonce : ajax_nonce,
						action : 'ced_walmart_save_fulfillment_center',
						store_id : store_id,
					},
					type : 'POST',
					success: function(response)
				{
						ced_walmart_hide_loader()
						parsed_response = jQuery.parseJSON( response );
						message         = parsed_response.message;
						status          = parsed_response.status;
						ced_walmart_display_notice( message , status );
					}
				}
			);

		}
	);

	$( document ).on(
		'click',
		'#ced_walmart_bulk_operation',
		function(e){
			e.preventDefault();
			ced_walmart_show_loader();
			var operation = $( '.bulk-action-selector' ).val();
			if (operation <= 0 ) {
				message = 'Please select any bulk operation.';
				status  = 400;
				ced_walmart_hide_loader();
				ced_walmart_display_notice( message,status );
				return false;
			} else {
				var operation            = $( '.bulk-action-selector' ).val();
				var walmart_products_ids = new Array();
				$( '.walmart_products_id:checked' ).each(
					function(){
						walmart_products_ids.push( $( this ).val() );
					}
				);
				perform_bulk_action( walmart_products_ids,operation );
			}

		}
	);

	function perform_bulk_action(walmart_products_ids,operation) {
		if (walmart_products_ids == '') {
			ced_walmart_hide_loader();
			message = 'Please select any products.';
			status  = 400;
			ced_walmart_display_notice( message,status );
			return false;
		}
		$.ajax(
			{
				url : ajax_url,
				data : {
					ajax_nonce : ajax_nonce,
					action : 'ced_walmart_process_bulk_action',
					operation : operation,
					walmart_products_ids : walmart_products_ids,
					store_id : store_id,
				},
				type : 'POST',
				success: function(response)
			{
					ced_walmart_hide_loader();
					parsed_response = jQuery.parseJSON( response );
					message         = parsed_response.message;
					status          = parsed_response.status;
					ced_walmart_display_notice( message , status );
				}
			}
		);
	}

	function isNumberKey(evt) {
		var charCode = (evt.which) ? evt.which : event.keyCode;
		if (charCode != 46 && charCode > 31
			&& (charCode < 48 || charCode > 57)) {
			return false;
		}

		return true;
	}

	/* ---------- Loader -----------------------------------------*/

	function ced_walmart_show_loader() {
		$( '#wpbody-content' ).block(
			{
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			}
		);

	}

	function ced_walmart_hide_loader() {
		$( '#wpbody-content' ).unblock();
	}

	/*-------------------Display Notices-----------------------*/
	function ced_walmart_display_notice( message = '' , status = 200){
		if ( status == 400 ) {
			classes = 'notice-error';
		} else {
			classes = 'notice-success';
		}
		notice  = '';
		notice += '<div class="notice ' + classes + ' ced_walmart_notices_content">';
		notice += '<p>' + message + '</p>';
		notice += '</div>';
		scroll_at_top();
		$( '#ced_walmart_notices' ).html( notice );
		window.setTimeout( function(){window.location.reload()}, 5000 );
	}

	function scroll_at_top() {
		$( "html, body" ).animate(
			{
				scrollTop: 0
			},
			600
		);
	}

	function CedValueShippingmethod(){

		var  shippingMethod = {};
		// var arr_shippingMethod=new Array();
		shippingMethod.shipMethod   = "VALUE";
		shippingMethod.status       = $( "#ced_template_status option:selected" ).val();
		shippingMethod.regions      = $( "#ced_all_regions_field_value" ).val();
		shippingMethod.addressTypes = $( "#ced_address_field_value" ).val();
		shippingMethod.transitTime  = $( "#ced_transitTime_value option:selected" ).val();
		var rate_model_type         = $( 'input[name="rate_model_type"]:checked' ).val();
		if ('TIERED_PRICING' == rate_model_type) {
			shippingMethod.minLimit   = $( "#ced_tieredShippingCharges_minLimit_value" ).val();
			shippingMethod.maxLimit   = $( "#ced_tieredShippingCharges_maxLimit_value" ).val();
			shippingMethod.shipCharge = $( "#ced_tieredShippingCharges_shipCharge_value" ).val();
		}

		return shippingMethod;
	}

	function CedStandardShippingMethod(){

		var  shippingMethodStandard       = [];
		var  tieredShippingCharges        = [];
		shippingMethodStandard.shipMethod = "STANDARD";
		shippingMethodStandard.status     = $( "#ced_template_status option:selected" ).val();
		var rule_length                   = $( ".shipping_rule_standard" ).length;
		for (var i = 1; i <= rule_length; i++) {
			var regions         = $( "[name='ced_standard_region_data_field" + i + "']" ).val();
			var addressTypes    = "STREET";
			var transitTime     = $( "[name='ced_standard_transit_time_data" + i + "']" ).val();
			var rate_model_type = $( 'input[name="rate_model_type"]:checked' ).val();
			if ('TIERED_PRICING' == rate_model_type) {
				var minLimit = $( "[name='ced_standard_min" + i + "']" ).val();
				var maxLimit = $( "[name='ced_standard_max" + i + "']" ).val();
				var amount   = $( "[name='ced_standard_rate" + i + "']" ).val();
				tieredShippingCharges.push( {minLimit:minLimit , maxLimit:maxLimit , shipCharge:amount} );
				shippingMethodStandard.push(
					{
						regions:regions,
						addressTypes:addressTypes,
						transitTime:transitTime,
						tieredShippingCharges:tieredShippingCharges
					}
				);
			} else {
				var perShippingCharge = $( "[name='ced_standard_rate" + i + "']" ).val();
				var shipcharge        = $( "[name='ced_standard_shipcharge" + i + "']" ).val();
				var shipchargeName    = $( "[name='ced_standard_shipcharge_name" + i + "']" ).val();
				shippingMethodStandard.push(
					{
						regions:regions,
						addressTypes:addressTypes,
						transitTime:transitTime,
						perShippingCharge:perShippingCharge,
						shipcharge:shipcharge,
						shipchargeName:shipchargeName
					}
				);
			}

		}
		return shippingMethodStandard;

	}

	// 2 Day Shipping

	function Ced2dayShippingMethod(){

		var  shippingMethod2day       = [];
		shippingMethod2day.shipMethod = "TWO_DAY";
		shippingMethod2day.status     = $( "#ced_template_status option:selected" ).val();
		var two_day_rule_length       = $( ".shipping_rule_2day" ).length;
		for (var j = 1; j <= two_day_rule_length; j++) {
			var regions      = $( "[name='ced_2day_region_data_field" + j + "']" ).val();
			var addressTypes = "STREET";
			shippingMethod2day.push(
				{
					regions:regions,
					addressTypes:addressTypes,
				}
			);

		}
		return shippingMethod2day;

	}

	// var btn = document.getElementById("ced-popup-button");
	// var span = document.getElementsByClassName("ced-close-button");

	$( document ).ready(
		function() {
			$( document ).on(
				'click',
				'#ced-popup-button',
				function() {
					$( "#ced-popup" ).css( "display", "block" );
				}
			);
		}
	);

	$( document ).ready(
		function() {
			$( document ).on(
				'click',
				'.ced-close-button',
				function() {

					$( "#ced-popup" ).css( "display", "none" );
				}
			);
		}
	);

	$( document ).ready(
		function() {
			$( document ).on(
				'click',
				'#ced-popup-button-overall-listing',
				function() {
					$( "#ced-popup-overall-listing" ).css( "display", "block" );
				}
			);
		}
	);

	$( document ).ready(
		function() {
			$( document ).on(
				'click',
				'.ced-close-button-overall-listing',
				function() {
					$( "#ced-popup-overall-listing" ).css( "display", "none" );
				}
			);
		}
	);

	$( document ).ready(
		function() {
			$( document ).on(
				'click',
				'#ced-popup-button-unpublished-report',
				function() {
					$( "#ced-popup-unpublished-report" ).css( "display", "block" );
				}
			);
		}
	);

	$( document ).ready(
		function() {
			$( document ).on(
				'click',
				'.ced-close-button-unpublished-report',
				function() {
					$( "#ced-popup-unpublished-report" ).css( "display", "none" );
				}
			);
		}
	);

})( jQuery );


// For Tab Shipping Methods
function CedOpenShippingmethod(evt, methodName) {
	var i, tabcontent, tablinks;
	tabcontent = document.getElementsByClassName( "ced_tabcontent" );
	for (i = 0; i < tabcontent.length; i++) {
		tabcontent[i].style.display = "none";
	}
	tablinks = document.getElementsByClassName( "ced_tablinks" );
	for (i = 0; i < tablinks.length; i++) {
		tablinks[i].className = tablinks[i].className.replace( " active", "" );
	}
	document.getElementById( methodName ).style.display = "block";
	evt.currentTarget.className                        += " active";
}
