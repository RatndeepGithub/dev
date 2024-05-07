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

	$( document ).ready(
		function(){
			var ajaxUrl   = ced_etsy_shipment_obj.ajax_url;
			var ajaxNonce = ced_etsy_shipment_obj.ajax_nonce;
			$( document ).on(
				'click',
				'#ced_etsy_submit_shipment',
				function(){

					var can_ajax = true;
					$( '.ced_etsy_required_data' ).each(
						function() {
							if ( $( this ).val() == '' ) {
								$( this ).css( 'border' , '1px solid red' );
								can_ajax = false;
								return false;
							} else {
								$( this ).removeAttr( 'style' );
							}
						}
					);

					if (can_ajax) {
						$( this ).addClass( 'disabled' );
						$( '.ced_spinner' ).css( 'visibility' , 'visible' );
						var ced_etsy_tracking_code = $( '#ced_etsy_tracking_code' ).val();
						var ced_etsy_carrier_name  = $( '#ced_etsy_carrier_name' ).val();
						var order_id               = $( this ).data( 'order-id' );

						$.ajax(
							{
								url : ajaxUrl,
								data : {
									ajax_nonce : ajaxNonce,
									action : 'ced_etsy_submit_shipment',
									ced_etsy_tracking_code: ced_etsy_tracking_code,
									ced_etsy_carrier_name:ced_etsy_carrier_name,
									order_id:order_id,
								},
								type : 'POST',
								success: function(response){
									console.log( response );
									$( "#ced_etsy_submit_shipment" ).removeClass( 'disabled' );
									$( '.ced_spinner' ).css( 'visibility' , 'hidden' );
									let parsed_response = jQuery.parseJSON( response );
									var classes         = classes = 'notice notice-success';
									if (parsed_response.status == 400) {
										classes = 'notice notice-error';
									}
									var html = '<div class="' + classes + '"><p>' + parsed_response.message + '</p></div>';
									$( '.ced_etsy_error' ).html( html );
									window.setTimeout( function() {window.location.reload();},5000 );
								}
							}
						);
					}
				}
			);
		}
	);

})( jQuery );
