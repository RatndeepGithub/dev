jQuery( document ).ready(
	function () {

		/**
		 * Check feed status
		 */

		var ajaxShipmentNonce = ced_order_localize.ajax_nonce;

		jQuery( "#umb_amazon_checkfeedstatus" ).click(
			function () {
				var order_id = jQuery( this ).data( 'order_id' );
				var feed_id  = jQuery( this ).data( 'feed_id' );
				var feed_req = jQuery( this ).data( 'feed_req' );

				jQuery( '#wpbody-content' ).block(
					{
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 0.6
						}
					}
				);

				jQuery.post(
					ced_order_localize.ajaxUrl,
					{
						'action' : 'umb_amazon_check_feed_status',
						'ajax_nonce' : ajaxShipmentNonce,
						'feed_id' : feed_id,
						'order_id' : order_id,
						'feed_req' : feed_req,
						"hpos": jQuery( this ).data( 'hpos' )
					},
					function (response) {
						jQuery( '#wpbody-content' ).unblock();

						alert( response );
						window.location.reload();
					}
				);
			}
		);
		/**
		 * Acknowledge Order
		 */

		jQuery( "#umb_amazon_ack_action" ).click(
			function () {
				// alert( 'helllooo!!!' );
				var order_id = jQuery( this ).data( 'order_id' );
				jQuery( '#wpbody-content' ).block(
					{
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 0.6
						}
					}
				);
				jQuery.post(
					ced_order_localize.ajaxUrl,
					{
						'action' : 'umb_amazon_acknowledge_order',
						'order_id' : order_id,
					},
					function (response) {

						jQuery( '#wpbody-content' ).unblock();
						alert( response );
						window.location.reload();
					}
				);
			}
		);

		/**
		 * Cancel Order
		 */

		jQuery( '#umb_amazon_cancel_action' ).click(
			function () {

				var amazon_order_id = jQuery( this ).data( "order_id" );
				jQuery( '#wpbody-content' ).block(
					{
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 0.6
						}
					}
				);
				jQuery.post(
					ced_order_localize.ajaxUrl,
					{
						'action' : 'umb_amazon_cancel_order',
						'order_id' : amazon_order_id,
					},
					function (response) {

						jQuery( '#wpbody-content' ).unblock();
						alert( response );
						window.location.reload();
					}
				);

			}
		);

		/**
		 * Ship Order
		 */
		jQuery( "#ced_amzon_shipment_submit" ).click(
			function () {

				var shiptype = 'order';
				jQuery( '.ced_amzon_shipping_option' ).each(
					function () {

						if (jQuery( this ).is( ':checked' )) {
							shiptype = jQuery( this ).val();
						}
					}
				);

				var amazon_order_id     = jQuery( "#amazon_orderid" ).val();
				var woocommerce_orderid = jQuery( "#woocommerce_orderid" ).val();

				var amazon_tracking   = jQuery( "#umb_amazon_tracking_order" ).val();
				var amazon_ship_date  = jQuery( "#umb_amazon_ship_date_order" ).val();
				var amazon_carrier    = jQuery( "#umb_amazon_carrier_order" ).val();
				var amazon_methodCode = jQuery( "#umb_amazon_methodCode_order" ).val();

				var amazon_other_carrier = jQuery( "#umb_amazon_other_carrier" ).val();
				if (amazon_carrier == 'Other') {
					if (amazon_other_carrier == "") {
						amazon_other_carrier = 'Other';
					}
					amazon_carrier = amazon_other_carrier;
				}

				if (amazon_tracking == "") {
					alert( "Please Enter Tracking Number" );
					return;
				}

				if (amazon_ship_date == "") {
					alert( "Please enter Ship to date" );
					return;
				}

				jQuery( ".ced_amazon_loader" ).css( {"background-color":"rgba(255, 255, 255, 0.6)", "bottom":"0", "height":"100%", "left":"0", "position":"fixed", "right":"0", "top":"0", "width":"100%", "z-index":"99999"} );
				jQuery( ".ced_amazon_loader img" ).css( {"text-align":"center", "margin":"0 auto", "width":"50px", "display":"block", "margin-top":"25%"} );
				jQuery( ".ced_amazon_loader" ).show();

				jQuery.post(
					ced_order_localize.ajaxUrl,
					{
						'action' : 'umb_amazon_shipment_order',
						'ajax_nonce' : ajaxShipmentNonce,
						'order_id' : amazon_order_id,
						"carrier":amazon_carrier,
						'methodCode':amazon_methodCode,
						"order":woocommerce_orderid,
						"tracking":amazon_tracking,
						"ship_todate":amazon_ship_date,
						"hpos": jQuery( this ).data( 'hpos' )
					},
					function (response) {

						jQuery( ".ced_amazon_loader" ).hide();
						alert( response );
						window.location.reload();
					}
				);
			}
		);

		// Enable input field for Other carrier name
		jQuery( "#umb_amazon_carrier_order" ).on(
			'change',
			function () {
				// alert(this.value);
				var carrier_name = jQuery( this ).val();
				if (carrier_name == 'Other') {
					jQuery( "#umb_amazon_other_carrier" ).css( 'display','block' );
				} else {
					jQuery( "#umb_amazon_other_carrier" ).css( 'display','none' );
				}
			}
		);

		jQuery( '.ced_amzon_shipping_option' ).each(
			function () {
				var shipping_option = jQuery( this ).val();
				if (jQuery( this ).is( ':checked' )) {
					if (shipping_option == 'order') {
						jQuery( "#ced_umb_amazon_complete_order_shipping" ).show();
						jQuery( "#ced_umb_amazon_complete_line_shipping" ).hide();
					}
					if (shipping_option == 'lineitem') {
						jQuery( "#ced_umb_amazon_complete_order_shipping" ).hide();
						jQuery( "#ced_umb_amazon_complete_line_shipping" ).show();
					}
				}
			}
		);

		jQuery( ".ced_amzon_shipping_option" ).click(
			function () {
				var shipping_option = jQuery( this ).val();
				if (shipping_option == 'order') {
					jQuery( "#ced_umb_amazon_complete_order_shipping" ).show();
					jQuery( "#ced_umb_amazon_complete_line_shipping" ).hide();
				}
				if (shipping_option == 'lineitem') {
					jQuery( "#ced_umb_amazon_complete_order_shipping" ).hide();
					jQuery( "#ced_umb_amazon_complete_line_shipping" ).show();
				}
			}
		);

	}
);
function isNumberKey(evt) {
	var charCode = (evt.which) ? evt.which : event.keyCode;
	if (charCode != 46 && charCode > 31
	&& (charCode < 48 || charCode > 57)) {
		return false;
	}

	return true;
}
