var ajax_url   = ced_mcfw_pricing_obj.ajax_url;
var ajax_nonce = ced_mcfw_pricing_obj.ajax_nonce;

jQuery( document ).on(
	'click',
	'.woo_ced_plan_selection_button',
	function(e) {
		e.preventDefault();

		var plan_type   = jQuery( this ).attr( 'data-plan_name' );
		var contract_id = jQuery( this ).attr( 'data-contract_id' );
		var count       = jQuery( this ).attr( 'data-count' );
		var plan_period = jQuery('input[name=switch]:checked').val();
		

		jQuery('#wpbody-content').block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});

		jQuery.ajax(
			{
				url 	: ajax_url,
				type	: 'post',
				data	: {
					ajax_nonce 	: ajax_nonce,
					plan_type	: plan_type,
					contract_id	: contract_id,
					plan_period	: plan_period,
					count		: count,
					action		: "ced_woo_pricing_plan_selection"
				},
				success : function( response ) {

					jQuery('#wpbody-content').unblock();

					var parsed_response = jQuery.parseJSON( response );
					let errorOccured    = 0;

					if ( !parsed_response || parsed_response.status == '400' ) {
						
						errorOccured = 1;
					} else {
						
						var confirmation_url = parsed_response.confirmation_url ? parsed_response.confirmation_url : '';
						if (confirmation_url != "") {
							window.location.href = confirmation_url;
						} else {
							errorOccured = 1;
						}
					}


					if (errorOccured) {

						window.scrollTo( 0, 0 );
						let title = 'Plan checkout failed!';
						let text  = 'Your request for plan checkout has failed. Please try again later.';
					  
						let notice = "<div  class='notice notice-error'><p> <b>" + title + "</b>. " + text + " </p></div>";

						if ( jQuery( ".notice-error" ).length == 0 ) {
							
							jQuery( "#wpbody-content" ).prepend( notice );
						} else {
							
							jQuery( "#wpbody-content" ).find('.notice-error').html('<p><b>'+ title + '</b>. ' + text + '</p>')
						}

						setTimeout( () => {
							jQuery( "#wpbody-content" ).find( '.notice' ).remove();
							//window.location.reload();
						}, 5000 )

					}
				}
			}
		)

	}
)


		jQuery( document ).on( 'change', 'input[name=switch]',
		function(e){
			var plan_type         = jQuery('input[name=switch]:checked').val();
			window.location.href += '&plan_type=' + plan_type;
			

		})
	jQuery( document ).on(
		'click',
		'.select-marketplace',
		function() {
			let checkcount       = jQuery('.select-marketplace:checked').length;
			let marketplace_name =jQuery(this).val();
			//alert(marketplace_name);
			var plan_type = jQuery('input[name=switch]:checked').val();
			console.log(checkcount);
			if (checkcount==0) {
				return false;
			}
			jQuery('#wpbody-content').block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});
			jQuery.ajax(
				{
					url 	: ajax_url,
					type	: 'post',
					data	: {
						ajax_nonce 	: ajax_nonce,
						checkcount	: checkcount,
						plan_type	: plan_type,
						marketplace_name: marketplace_name,
						action		: "ced_woo_check_marketplaces"
					},
					success : function( response ) {
						let data          = jQuery.parseJSON( response );
						let basic_price   =data.basic_price;
						let advance_price =data.advance_price;
						jQuery('#ced-price-basic').html("$"+basic_price);
						jQuery('#ced-price-advanced').html("$"+advance_price);
						jQuery('.woo_ced_plan_selection_button').attr( 'data-count',checkcount );
						console.log(basic_price);
						console.log(advance_price);
						jQuery('#wpbody-content').unblock();
					}
				})
			//console.log(countCheckedCheckboxes);
		}
	)

jQuery( document ).on(
	'click',
	'.ced-change-plan',
	function(e) {
		e.preventDefault();
		window.location.href += '&is_update=yes';
	}
)



jQuery( document ).on(
	'click',
	'.ced-cancel-plan',
	function(e) {
		e.preventDefault();
		if (confirm( "Are you sure you want to cancel the ongoing plan?\n \n Note : You can still use the plan for the remaining subscribed days." )) {
			var contract_id = jQuery( this ).attr( 'data-contract_id' );

			jQuery('#wpbody-content').block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			jQuery.ajax(
				{
					url 	: ajax_url,
					type	: 'post',
					data	: {
						ajax_nonce 	: ajax_nonce,
						contract_id	: contract_id,
						action		: "ced_woo_pricing_plan_cancellation"
					},
					success : function( response ) {

						console.log(response);
						jQuery('#wpbody-content').unblock();
						var response = jQuery.parseJSON( response );
						// var message         = parsed_response.message;

						window.scrollTo( 0, 0 );

						let title;
						let text;

						if ( response.status == '200' ) {

							title = 'Plan cancelled';
							text  = 'Your plan has been successfully cancelled.';

						} else {
							title = 'Plan cancellation failed!';
							text  = 'Your plan has been successfully cancelled.';
						}
					  
						let notice = "<div  class='notice notice-success'><p> <b>" + title + "</b>. " + text + " </p></div>";

						if ( jQuery( ".notice-success" ).length == 0 ) {
							jQuery( "#wpbody-content" ).prepend( notice );
						} else {
							jQuery( "#wpbody-content" ).find('.notice-success').html('<p><b>'+ title + '</b>. ' + text + '</p>')
						}

						setTimeout( () => {
							jQuery( "#wpbody-content" ).find( '.notice' ).remove();
							window.location.reload();
						}, 3000 )
						
					}
				}
			)
		} else {
			return;
		}

	}
)
