(function ($) {
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

	var ajaxUrl      = ced_amazon_admin_obj.ajax_url;
	var ajaxNonce    = ced_amazon_admin_obj.ajax_nonce;
	var user_id      = ced_amazon_admin_obj.user_id;
	var siteUrl      = ced_amazon_admin_obj.site_url;
	var access_token = ced_amazon_admin_obj.access_token;

	var amazon_loader_overlay = '<div class="ced_amazon_overlay"><div class="ced_amazon_overlay__inner"><div class="ced_amazon_overlay__content"><div class="ced_amazon_page-loader-indicator ced_amazon_overlay_loader"><svg class="ced_amazon_overlay_spinner" width="65px" height="65px" viewBox="0 0 66 66" xmlns="http://www.w3.org/2000/svg"><circle class="path" fill="none" stroke-width="6" stroke-linecap="round" cx="33" cy="33" r="30"></circle></svg></div><div class="ced_amazon_page-loader-info"><p class="ced_amazon_page-loader-info-text" id="ced_amazon_progress_text">Loading...</p><p class="ced_amazon_page-loader-info-text" style="font-size:19px;" id="ced_amazon_countdown_timer"></p></div></div></div></div>';

	const queryString = window.location.search;
	var urlParams     = new URLSearchParams( queryString );

	

	document.addEventListener("readystatechange", (event) => {
		
		if (event.target.readyState === "interactive") {
			$('#wpbody-content').block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
						});
		} else if (event.target.readyState === "complete") {
			setTimeout(() => {
				$('#wpbody-content').unblock()
			}, 500)
		}
	  });


	function remove_custom_notice(reload = 'no') {

		if ($( '#ced_amazon_custom_notice' ).hasClass( 'ced_amazon_notice' )) {
			setTimeout(
				() => {
					$( '.ced_amazon_notice' ).remove();
					if (reload == 'yes') {
						window.location.reload();
					}
				},
				3000
			)
		}
	}


	async function ced_amazon_fetch_next_level_category( level, category_data, template_id, display_saved_values ){
		$('#wpbody-content').block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});
		let user_id   = urlParams.get( 'user_id' );
		let seller_id = urlParams.get( 'seller_id' );

		let categoryResponse;

		await	$.ajax(
			{
				url : ajaxUrl,
				data : {
					ajax_nonce : ajaxNonce,
					action : 'ced_amazon_fetch_next_level_category',
					level : level,
					category_data: category_data,
					template_id : template_id,
					user_id: user_id,
					seller_id: seller_id,
					display_saved_values: display_saved_values
				},
				type : 'POST',
				success: function(response){
					
					if (response.success == false) {
						if ($('.notice-error').length > 0) {
  
							$('.notice-error').html('<p>'+ response.data.message + '</p>');
							$('#ced_amazon_last_level_cat').prop("checked", false);
						} else {
							$(document).find('form').prepend('<div class="notice notice-error is-dismissible"><p>'+response.data.message+'</p></div>');
							$('#ced_amazon_last_level_cat').prop("checked", false);
						}
					}
					categoryResponse = response;
				}
			}
		);

		return categoryResponse;
	}

	$( document ).on(
		'click',
		'.ced_amazon_add_account_button',
		function () {

			var nameValue         = jQuery( "#ced_amazon_select_marketplace_region" ).find( "option:selected" ).val();
			var endPt             = jQuery( "#ced_amazon_select_marketplace_region" ).find( "option:selected" ).attr( "end-pt" );
			var marketplaceId     = jQuery( "#ced_amazon_select_marketplace_region" ).find( "option:selected" ).attr( "mp-id" );
			var shopName          = jQuery( "#ced_amazon_select_marketplace_region" ).find( "option:selected" ).attr( "shop-name" );
			var sellerEmail       = jQuery( ".ced_amazon_seller_email" ).val();
			var countryName       = jQuery( "#ced_amazon_select_marketplace_region" ).find( "option:selected" ).attr( "country-name" );
			var marketplaceUrl    = jQuery( "#ced_amazon_select_marketplace_region" ).find( "option:selected" ).attr( "mp-url" );
			var marketplaceRegion = jQuery( "#ced_amazon_select_marketplace_region" ).find( "option:selected" ).parents( 'optgroup' ).data( 'attr' );

			if (sellerEmail == '') {

				customSwal({
					title: 'Error',
					text: 'Please enter email ID.',
					icon: 'error',
				}, () => { }
			)

				return;
			} else {

				if ( !validateEmail(sellerEmail) ) {

					customSwal({
						title: 'Error',
						text: 'Please enter a valid email ID.',
						icon: 'error',
						}, () => { }
					)

					return;
				}
				  
			}

			if ( marketplaceId == '' || marketplaceId == undefined || marketplaceId == null ) {

				customSwal({
					title: 'Error',
					text: 'Please select valid marketplace region.',
					icon: 'error',
					}, () => { }
				)
				return;
			}

			let params = {
				marketplace_id: marketplaceId,
				marketplace_region: marketplaceRegion,
				name_value: nameValue,
				end_pt: endPt,
				shop_name: shopName,
				country_name: countryName,
				marketplace_url: marketplaceUrl,
			}

			sessionStorage.setItem( "amazonAccountParams", params );

			$('#wpbody-content').block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			jQuery.ajax({

				type: 'POST',
				url: ajaxUrl,
				data: {
					ajax_nonce: ajaxNonce,
					marketplace_id: marketplaceId,
					seller_email: sellerEmail,
					
					action: 'ced_amazon_create_sellernext_user',
				},
				success: function (response) {

					if (response.status == 'success') {
							
						jQuery.ajax({
							type: 'POST',
							url: ajaxUrl,
							data: {
								ajax_nonce: ajaxNonce,
								marketplace_id: marketplaceId,
								refresh_token: response.refresh_token,
								marketplace_region: marketplaceRegion,
								name_value: nameValue,
								end_pt: endPt,
								shop_name: shopName,
								country_name: countryName,
								marketplace_url: marketplaceUrl,
								action: 'ced_amazon_sellernext_get_access_token_and_redirect',

							},
							success: function (response) {
								if (response.status == 'success') {

									$('#wpbody-content').unblock();
									window.location.replace( response.redirect_url );
								}
							}
								}
						)
					}

						
					if (response.status == 'failed' || response.status == '') {
						jQuery( '.ced_amazon_overlay' ).remove();
						jQuery( '#wpbody-content' ).prepend( '<div class="notice notice-error ced_amazon_notice" id="ced_amazon_custom_notice">' + response.message + '.</div>' )
						remove_custom_notice();
					}

				}
				}
			)

		}
	);


	function validateEmail(email) {
		const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
		return emailRegex.test(email);
	}

	function notNullAndEmpty(variable) {

		if (variable == null || variable == "" || variable == 0 || variable == 'null') {
			return false;
		}

		return true;
	}

	$( document ).on(
		'click',
		'#ced_amazon_fetch_orders',
		function (event) {
			event.preventDefault();
			var store_id  = $( this ).attr( 'data-id' );
			var seller_id = urlParams.get( 'seller_id' );

			$('#wpbody-content').block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});
						
			$.ajax({
				url: ajaxUrl,
				data: {
					ajax_nonce: ajaxNonce,
					action: 'ced_amazon_get_orders',
					userid: store_id,
					seller_id: seller_id
				},
				type: 'POST',
				success: function (response) {
					$('#wpbody-content').unblock();

					var response  = jQuery.parseJSON( response );
					var response1 = jQuery.trim( response.message );
					
					if (response1 == "Shop is Not Active") {
						
						customSwal({
							title: 'Error',
							text: 'Currently, the shop is not active. Please activate your shop to start fetching orders.',
							icon: 'error',
							}, () => { }
						)

					} else if (response.status == 'success') {
						customSwal({
							title: 'Success',
							text: 'Orders have been fetched successfully. Please reload the page to view your new orders.',
							icon: 'success',
							}, () => { }
						)
						
					} else if (response.status == 'No Results') {
						customSwal({
							title: 'Error',
							text: 'We can\'t find any new orders in the API response.',
							icon: 'error',
							}, () => { }
						)
					
					} else if (response.status == 'error') {
						customSwal({
							title: 'Error',
							text: 'Something went wrong. Please try again!',
							icon: 'error',
							}, () => { }
						)
						
					}

				}
				}
			);
		}
	);

	$( document ).on(
		'click',
		'#ced_amazon_continue_wizard_button',
		function (e) {

			let currentStep = $( this ).data( 'attr' );
			let user_id     = urlParams.get( 'user_id' );

			jQuery.ajax(
				{
					type: 'POST',
					url: ajaxUrl,
					data: {
						ajax_nonce: ajaxNonce,
						action: 'ced_amazon_update_current_step',
						current_step: currentStep,
						user_id: user_id
					},
					success: function (response) {
					}
				}
			)

		}
	)

	

	jQuery( document ).on(
		'click',
		'#ced_amazon_disconnect_account_btn',
		function (e) {

			let sellernextShopId = $( this ).attr( 'sellernext-shop-id' );
			let seller_id        = $( this ).attr( 'seller-id' );
			e.preventDefault();
			
			customSwal(
				{
					title: 'Warning',
					text: " Disconnecting your account will stop the automation process, but your configuration will remain unchanged. Do you want to disconnect the account? <a id='ced_amazon_verf_disconnect_account_btn' sellernext-shop-id='" + sellernextShopId + "' seller_id = '" + seller_id + "' >Click to disconnect</a>",
					icon: "warning",
					buttons: true,
					dangerMode: true,
				},
				() => {return; },
				250000000
			);
		}
	);

	jQuery( document ).on(
		'click',
		'#ced_amazon_verf_disconnect_account_btn',
		function (e) {

			let sellernextShopId = $( this ).attr( 'sellernext-shop-id' );
			let seller_id        = $( this ).attr( 'seller_id' );
			e.preventDefault();
				$('#wpbody-content').block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});

			jQuery.ajax(
				{
					type: 'post',
					url: ajaxUrl,
					data: {
						seller_id: seller_id,
						sellernextShopId: sellernextShopId,
						ajax_nonce: ajaxNonce,
						action: 'ced_amazon_remove_account_from_integration'
					},
					success: function (response) {
						$('#wpbody-content').unblock();
						customSwal(
							{
								title: response.title,
								text: response.message,
								icon: response.status,
							},
							() => {
							window.location.reload();
							}, 2000
						)
					}
				}
			)

		}
	);



	jQuery( document ).on(
		"click",
		".ced_amazon_add_rows_button",
		function (e) {

			e.preventDefault();
			let custom_field     = $( this ).parents( 'tr' ).children( 'td' ).eq( 1 ).find( 'select' );
			let custom_field_val = custom_field.val();
			let id               = $( this ).attr( 'id' );
			let fileUrl          = $( '.ced_amazon_profile_url' ).val();

			id = escapeBrackets( id );

			let primary_cat   = $( '.ced_primary_category' ).val();
			let secondary_cat = $( '.ced_secondary_category' ).val();

			let file_url = '';

			if ( notNullAndEmpty(primary_cat) && notNullAndEmpty(secondary_cat) ) {

				if ('' == custom_field_val) {
					window.scrollTo( 0, 0 );
					if ($('.notice-error').length > 0) {
						$('.notice-error').html('<p>Unable to add new optional rows.</p>')
					} else {
						$(document).find('form').prepend('<div class="notice notice-error is-dismissible"><p>Please select a optional row. </p></div>')
						
					}
					return;
				}
				
				$('#wpbody-content').block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});

				jQuery.ajax(
					{
						type: 'post',
						url: ajaxUrl,
						data: {
							userid: user_id,
							ajax_nonce: ajaxNonce,
							custom_field: JSON.parse( custom_field_val ),
							primary_cat: primary_cat,
							secondary_cat: secondary_cat,
							template_type: $( '.ced_amazon_template_type' ).val(),
							fileUrl: fileUrl,
							dataType: "html",
							action: 'ced_amazon_add_custom_profile_rows'
						},
						success: function (response) {

							response = JSON.parse( response );

							$('#wpbody-content').unblock();
							$( '#' + id ).parents( 'tr' ).before( response.data );
							$( '#optionalFields option:selected' ).remove();
							custom_field.val( '' );
							$( "#optionalFields" ).val( null ).trigger( "change" );
							$( '.custom_category_attributes_select' ).selectWoo(
								{
									dropdownPosition: 'below',
									dropdownAutoWidth : true,
									allowClear: true,
									placeholder: '--Select--',
									width: 'resolve'
								}
							);
							$( '.custom_category_attributes_select2' ).selectWoo({ 
								dropdownPosition: 'below',
								allowClear: true,
								placeholder: '--Select--',
								width: '90%'
							});

							jQuery('.woocommerce-help-tip').tipTip( {
								attribute: 'data-tip',
								content: jQuery('.woocommerce-help-tip').attr('data-tip'),
								fadeIn: 50,
								fadeOut: 50,
								delay: 200,
								keepAlive: true,
							} );

							
							$('.woocommerce-importer').find('.woocommerce-help-tip').css({position: 'absolute',right:0, top:'47%'})

						}
					}
				)

			} else if ( ! ( primary_cat && secondary_cat ) ) {

				if ('' == custom_field_val) {
					window.scrollTo( 0, 0 );
					if ($('.notice-error').length > 0) {
						$('.notice-error').html('<p>Unable to add new optional rows.</p>')
					} else {
						$(document).find('form').prepend('<div class="notice notice-error is-dismissible"><p>Please select a optional row. </p></div>')
						
					}
					return;
				}
				
				file_url = $('.ced_amazon_profile_url').val();

				$('#wpbody-content').block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});

				jQuery.ajax(
					{
						type: 'post',
						url: ajaxUrl,
						data: {
							userid: user_id,
							ajax_nonce: ajaxNonce,
							custom_field: JSON.parse( custom_field_val ),
							primary_cat: primary_cat,
							secondary_cat: secondary_cat,
							template_type: $( '.ced_amazon_template_type' ).val(),
							fileUrl: fileUrl,
							dataType: "html",
							action: 'ced_amazon_add_custom_profile_rows'
						},
						success: function (response) {

							response = JSON.parse( response );

							$('#wpbody-content').unblock();
							$( '#' + id ).parents( 'tr' ).before( response.data );
							$( '#optionalFields option:selected' ).remove();
							custom_field.val( '' );
							$( "#optionalFields" ).val( null ).trigger( "change" );
							$( '.custom_category_attributes_select' ).selectWoo(
								{
									dropdownPosition: 'below',
									dropdownAutoWidth : true,
									allowClear: true,
									placeholder: '--Select--',
									width: 'resolve'
								}
							);
							$( '.custom_category_attributes_select2' ).selectWoo({
								dropdownPosition: 'below',
								allowClear: true,
								placeholder: '--Select--',
								width: '90%'
							});

							jQuery('.woocommerce-help-tip').tipTip( {
								attribute: 'data-tip',
								content: jQuery('.woocommerce-help-tip').attr('data-tip'),
								fadeIn: 50,
								fadeOut: 50,
								delay: 200,
								keepAlive: true,
							} );

							
							$('.woocommerce-importer').find('.woocommerce-help-tip').css({position: 'absolute',right:0, top:'47%'})

						}
					}
				)
			} else {
				window.scrollTo( 0, 0 );
				if ($('.notice-error').length > 0) {
					$('.notice-error').html('<p>Unable to add new optional rows.</p>')
				} else {
					$(document).find('form').prepend('<div class="notice notice-error is-dismissible"><p>Unable to add new optional rows</p></div>')
					return;
				}
			   
			}
		}
	);


	jQuery( document ).on(
		"click",
		"#update_template",
		function (e) {

			e.preventDefault();

			let primary_cat   = $( '.ced_primary_category' ).val();
			let secondary_cat = $( '.ced_secondary_category' ).val();
			let browse_nodes  = $( '.ced_browse_category' ).val();

			if ( notNullAndEmpty(primary_cat) && notNullAndEmpty(secondary_cat) ) {


				let user_id   = urlParams.get( 'user_id' );
				let seller_id = urlParams.get( 'seller_id' );

				$('#wpbody-content').block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});

				jQuery.ajax(
					{
						type: 'post',
						url: ajaxUrl,
						data: {
							ajax_nonce: ajaxNonce,
							primary_cat: primary_cat,
							secondary_cat: secondary_cat,
							browse_nodes: browse_nodes,
							user_id: user_id,
							seller_id: seller_id,
							action: 'ced_amazon_update_template'
						},
						success: function (response) {
							if ( typeof response == 'string' ) {
								response = JSON.parse( response );
							} else if ( typeof response == 'object' ) {
								response = response;
							}
							
							$('#wpbody-content').unblock();
							
							customSwal(
								{
									title: 'Update',
									text: response.message,
									icon: response.status,
								},
								() => { window.location.reload(); }
							)

						},
						error: function(error){
							window.scrollTo( 0, 0 );
							if ($('.notice-error').length > 0) {
								$('.notice-error').html('<p>Unable to update template fields. Please try again later.</p>')
							} else {
								$(document).find('form').prepend('<div class="notice notice-error is-dismissible"><p>Unable to update template fields. Please try again later.</p></div>')
								return;
							}
						}
					}
				)

			} else {
				window.scrollTo( 0, 0 );
				if ($('.notice-error').length > 0) {
					$('.notice-error').html('<p>Unable to update template fields. Please try again later.</p>')
				} else {
					$(document).find('form').prepend('<div class="notice notice-error is-dismissible"><p>Unable to update template fields. Please try again later.</p></div>')
					return;
				}
				
			}

		}
	);

	function checkSellerNextCategoryApi(){

		jQuery.ajax(
			{
				type:'post',
				url: ajaxUrl,
				data: {
					ajax_nonce: ajaxNonce,
					user_id: urlParams.get( 'user_id' ),
					action: 'ced_amazon_checkSellerNextCategoryApi'
				},
				success: function(response){

					let template_id = urlParams.get( 'template_id' );
					if (  response.success == 1 ) {
						if ( template_id !== '' && template_id !== null ) {
							CategoryApiLoop( template_id );

						} else {
							jQuery( '#wpbody-content .ced_amazon_overlay' ).remove();
						}
					} else {

						customSwal(
							{
								title: 'Product Category',
								text: 'We are facing some issue while loading data. Please try after sometime. ',
								icon: 'error',
							},
							() => {   window.history.back() }
						);

					}

				}
			}
		)
	}

	async function CategoryApiLoop( template_id ){

		let template_type = urlParams.get( 'template_type' );

			let i             = 3;
			let category_data = $( '.ced_amz_cat_name' ).attr( 'data-category' );

			category_data = JSON.parse( category_data );

			$('#wpbody-content').block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			let categoryResponse = await ced_amazon_fetch_next_level_category( i, category_data, template_id, 'yes' );
			$('#wpbody-content').unblock();

			await handleCategoryResponse( categoryResponse )

	}

	function handleCategoryResponse( response ){


		if ( typeof response == 'string' ) {
		   response = JSON.parse(response);
		}


		if ( response.success ) {

			$( '.ced_template_required_attributes' ).html( response.data );
			$( '.custom_category_attributes_select' ).selectWoo(
				{
					dropdownPosition: 'below',
					allowClear: true,
					placeholder: '--Select--',
					width: 'resolve'
				}
			);

			$( '.custom_category_attributes_select2' ).selectWoo({
				dropdownPosition: 'below',
				allowClear: true,
				placeholder: '--Select--',
				width: '90%'
			});

			$( '#optionalFields' ).selectWoo({
				dropdownPosition: 'below',
				allowClear: true,
				placeholder: '--Select--',
				width: '90%'
			});
			jQuery('.woocommerce-help-tip').tipTip( {
				attribute: 'data-tip',
				content: jQuery('.woocommerce-help-tip').attr('data-tip'),
				fadeIn: 50,
				fadeOut: 50,
				delay: 200,
				keepAlive: true,
			} );

			$('.woocommerce-importer').find('.woocommerce-help-tip').css({position: 'absolute',right:0, top:'47%'})


		} else {

			if ($('.notice-error').length > 0) {

				let message = response.hasOwnProperty("data") && response.data.hasOwnProperty("message") ? response.data.message : '';

				if ( message.length == 0 ) {
					message = response.message;
				}
  
				$('.notice-error').html('<p>'+ message + '</p>');
				$('#ced_amazon_last_level_cat').prop("checked", false);
			} else {

				
				let message = response.hasOwnProperty("data") && response.data.hasOwnProperty("message") ? response.data.message : '';

				if ( message.length == 0 ) {
					message = response.message;
				}

				$(document).find('form').prepend('<div class="notice notice-error is-dismissible"><p>'+message+'</p></div>');
				$('#ced_amazon_last_level_cat').prop("checked", false);
			}
		}

		


	}


	jQuery( document ).on(
		'click',
		'#amazon_seller_verification',
		function (e) {

			e.preventDefault();
			let user_id = $( this ).attr( 'dta-amz-shop-id' );

			$('#wpbody-content').block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			jQuery.ajax({
				type: 'post',
				url: ajaxUrl,
				data: {
					ajax_nonce: ajaxNonce,
					user_id: user_id,
					action: 'ced_amazon_seller_verification'
				},
				success: function (response) {

					$('#wpbody-content').unblock();

					if (response.success) {

						if (response.data.status) {

							let url = window.location.href;

							url = removeParam( "data", url );
							url = removeParam( 'app_code', url );
							url = removeParam( 'success', url );
							url = removeParam( 'marketplace', url );
							url = removeParam( 'state', url );

							
							window.location.replace( url + '&part=wizard-options&user_id=' + response.data.data.user_id + '&seller_id=' + response.data.data.ced_mp_name + '|' + response.data.data.seller_id )

						} else {
							customSwal(
								{
									title: 'Seller Verification Failed',
									text: 'Unable to verify you. Please try again.',
									icon: 'error',
									},
								() => {return; }
							)
						}
					} else {
						customSwal(
							{
								title: 'Seller Verification Failed',
								text: 'Unable to verify you. Please try again.',
								icon: 'error',
								},
							() => {return; }
						)
					}
				}
				}
			);

		}
	);

	function removeParam(key, sourceURL) {
		var rtn         = sourceURL.split( "?" )[0],
			param,
			params_arr  = [],
			queryString = (sourceURL.indexOf( "?" ) !== -1) ? sourceURL.split( "?" )[1] : "";
		if (queryString !== "") {
			params_arr = queryString.split( "&" );
			for (var i = params_arr.length - 1; i >= 0; i -= 1) {
				param = params_arr[i].split( "=" )[0];
				if (param === key) {
					params_arr.splice( i, 1 );
				}
			}
			if (params_arr.length) {
				rtn = rtn + "?" + params_arr.join( "&" );
			}
		}
		return rtn;
	}

	$( document ).on(
		'click',
		'.add-new-template-btn',
		function (e) {

			let woo_used_categories = $( this ).attr( 'data-woo-used-cat' );
			let woo_all_categories  = $( this ).attr( 'data-woo-all-cat' );

			woo_used_categories = JSON.parse( woo_used_categories );
			woo_all_categories  = JSON.parse( woo_all_categories );


			if (woo_all_categories.sort().join( ',' ) === woo_used_categories.sort().join( ',' )) {

				customSwal(
					{
						title: 'WooCommerce Category',
						text: 'All existing WooCommerce categories are already mapped. Please create a new WooCommerce category or remove some WooCommerce categories from the existing mapped profiles.',
						icon: 'error',
					},
					() => {return; }
				)
			} else {
				if ('URLSearchParams' in window) {
					var searchParams = new URLSearchParams( queryString );
					searchParams.set( "section", "add-new-template" );
					window.location.search = searchParams.toString();

				}
			}
		}
	)

	$( document ).on(
		'click',
		'#ced_amazon_reset_product_page',
		function (e) {

			e.preventDefault();
			var searchParams = new URLSearchParams( queryString );
			searchParams.delete( 'searchType' );
			searchParams.delete( 'searchQuery' );
			searchParams.delete( 'searchCriteria' );

			window.location.search = searchParams.toString();

		}
	)

	$( document ).on(
		'click',
		'.ced_amazon_add_missing_fields',
		function (e) {

			e.preventDefault();
			e.stopPropagation();

			let title = $( '.ced_amazon_add_missing_field_title' ).val();
			let slug  = $( '.ced_amazon_add_missing_field_slug' ).val();
			title     = title.trim();

			let existing_custom_item_aspects_json;

			let existing_custom_item_aspects_string = $( '.ced_amazon_add_missing_fields_heading' ).attr( 'data-attr' );

			existing_custom_item_aspects_string = existing_custom_item_aspects_string.replaceAll( "+", " " );
			if (existing_custom_item_aspects_string !== '') {

				existing_custom_item_aspects_json = JSON.parse( existing_custom_item_aspects_string );

				if (existing_custom_item_aspects_json.hasOwnProperty( slug ) || Object.values( existing_custom_item_aspects_json ).indexOf( title ) > -1) {
					let html = '<tr class="ced_amazon_add_missing_field_error" ><td colspan="3">Please enter another custom title or slug. Same custom title or slug has already been used.</td></tr>'
					$( '.ced_amazon_add_missing_field_row' ).before( html );

					setTimeout(
						() => {
						$( '.ced_amazon_add_missing_field_error' ).remove();
						},
						3000
					)
					return;
				}

			}

			if (title.length <= 0 || slug.length <= 0) {
				let html = '<tr class="ced_amazon_add_missing_field_error" ><td colspan="3">Please enter additional field title and slug.</td></tr>'
				$( '.ced_amazon_add_missing_field_row' ).before( html );

				setTimeout(
					() => {
					$( '.ced_amazon_add_missing_field_error' ).remove();
					},
					3000
				)

			} else {

				if (existing_custom_item_aspects_string == '') {
					existing_custom_item_aspects_json       = {};
					existing_custom_item_aspects_json[slug] = title;
				} else {
					existing_custom_item_aspects_json       = JSON.parse( existing_custom_item_aspects_string );
					existing_custom_item_aspects_json[slug] = title;
				}

				$( '.ced_amazon_add_missing_fields_heading' ).attr( 'data-attr', JSON.stringify( existing_custom_item_aspects_json ) );

				let primary_cat   = $( '#ced_amazon_primary_category_selection' ).val();
				let secondary_cat = $( '#ced_amazon_secondary_category_selection' ).val();

				jQuery( '#wpbody-content' ).append( amazon_loader_overlay );

				jQuery.ajax(
					{
						type: 'post',
						url: ajaxUrl,
						dataType: 'html',
						data: {
							user_id: user_id,
							ajax_nonce: ajaxNonce,
							title: title,
							slug: slug,
							primary_cat: primary_cat,
							secondary_cat: secondary_cat,
							action: 'ced_amazon_add_missing_field_row'
						},
						success: function (response) {

							response = JSON.parse( response );
							jQuery( '#wpbody-content .ced_amazon_overlay' ).remove();
							$( '.ced_amazon_add_missing_fields_heading' ).after( response.data );
							$( '.ced_amazon_add_missing_field_title' ).val( '' );
							$( '.ced_amazon_add_missing_field_slug' ).val( '' );
							$( '.custom_category_attributes_select' ).selectWoo();
							remove_custom_notice();

						}
					}
				)

			}

		}
	)

	$( document ).on(
		'click',
		'.ced_amazon_remove_custom_row',
		function (e) {
			e.preventDefault();
			$( this ).parents( 'tr' ).remove();
		}
	)

	$( document ).on(
		'change',
		'.ced_amazon_change_acc',
		function (e) {
			$('#wpbody-content').block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			let href       = $( 'select[name="ced_amazon_change_acc"] :selected' ).attr( 'data-href' );
			let hrefParams = new URLSearchParams( href );

			if ( notNullAndEmpty( hrefParams.get( 'user_id' ) ) && notNullAndEmpty( hrefParams.get( 'seller_id' ) ) ) {

				jQuery.ajax(
					{
						type: 'post',
						url: ajaxUrl,
						data: {
							ajax_nonce: ajaxNonce,
							user_id: hrefParams.get( 'user_id' ),
							seller_id: hrefParams.get( 'seller_id' ),
							action: 'ced_amazon_change_region'
						},
						success: function (response) {

							response = JSON.parse( response )
							
							jQuery( '#wpbody-content .ced_amazon_overlay' ).remove();
							window.location.href = href;

						}
					}
				);

			} else {
				window.location.href = href;
			}


		}
	)

	$( document ).ready(

		function(){

			jQuery('.woocommerce-help-tip').tipTip( {
				attribute: 'data-tip',
				content: jQuery('.woocommerce-help-tip').attr('data-tip'),
				fadeIn: 50,
				fadeOut: 50,
				delay: 200,
				keepAlive: true,
			} );
			let page    = urlParams.get( 'page' );
			let section = urlParams.get( 'section' ) ? urlParams.get( 'section' ) : '';
			if (section == 'add-new-template') {
				checkSellerNextCategoryApi();
			}
			if ( page == 'ced_amazon' && section !== '' && section !== 'setup-amazon' ) {
				  jQuery( "#wpbody-content" ).addClass( "ced-amz-not-setup" );
			}

			
		}
	)

	

	// View feed response in feed table via ajax using modal
	jQuery( document ).on(
		'click',
		'.feed-response',
		function (e) {

			$('#wpbody-content').block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			jQuery( '.feed-response-modal' ).html( '' );

			let feed_id   = $( this ).attr( "data-attr" );
			let seller_id = urlParams.get( 'seller_id' ) ? urlParams.get( 'seller_id' ) : '';
			if ( seller_id == '' ) {
				console.log( 'Seller Id is missing!' );
				return false;
			}

			jQuery.ajax(
				{
					type: 'post',
					url: ajaxUrl,
					data: {
						ajax_nonce: ajaxNonce,
						feed_id: feed_id,
						seller_id: seller_id,
						action: 'ced_amazon_view_feed_response'
					},
					success: function (response) {
						$('#wpbody-content').unblock();

						jQuery( '.feed-response-modal' ).append( response.data );
						
						var modal           = document.getElementById("feedViewModal");
						modal.style.display = "block";
						
					}
				}
			);

		}
	);

	
	jQuery( document ).on( 'click','.ced_feed_cancel',
		function (e) {
			var modal           = document.getElementById("feedViewModal");
			modal.style.display = "none";

		})


	jQuery( document ).on(
		'click',
		'.ced_template_cancel',
		function (e) {
			var modal           = document.getElementById("uploadTemplateModal");
			modal.style.display = "none";

		})


	function escapeBrackets(str) {
		// Use regular expression to find and escape brackets
		return str.replace( /[(){}\[\]]/g, '\\$&' );
	}

	function customSwal(swalObj = {}, callback , time = 250000 ){

		window.scrollTo( 0, 0 );
		let notice = "";

		let title = swalObj.title ? swalObj.title : '';
		let text  = swalObj.text ? swalObj.text : '';

		if ( swalObj.icon == "success") {

			notice += "<div  class='notice notice-success'><p> <b>" + title + "</b>. " + text + " </p></div>";

			if ( $( ".notice-success" ).length == 0 ) {
				$( "#wpbody-content" ).prepend( notice );
			} else {
				$( "#wpbody-content" ).find('.notice-success').html('<p><b>'+ title + '</b>. ' + text + '</p>')
			}

		} else if ( swalObj.icon == 'error') {

			notice += "<div  class='notice notice-error'><p> <b>" + title + "</b>. " + text + " </p></div>";
		
			if ( $( ".notice-error" ).length == 0 ) {
				$( "#wpbody-content" ).prepend( notice );
			} else {
				$( "#wpbody-content" ).find('.notice-error').html('<p><b>'+ title + '</b>. ' + text + '</p>')
			}

		} else if ( swalObj.icon == 'warning') {

			notice += "<div class='notice notice-warning'><p> <b>" + title + "</b>. " + text + " </p></div>";

			if ( $( ".notice-warning" ).length == 0 ) {
				$( "#wpbody-content" ).prepend( notice );
			} else {
				$( "#wpbody-content" ).find('.notice-warning').html('<p><b>'+ title + '</b>. ' + text + '</p>')
			}

		}

		setTimeout( () => {
				$( "#wpbody-content" ).find( '.notice' ).remove();
				callback();
			}, time
		)

	}

	jQuery( document ).on(
		'click',
		'.ced-settings-checkbox',
		function (e) {
			if ( $( this ).parent( 'label' ).hasClass( 'is-checked' ) ) {
				$( this ).parent( 'label' ).removeClass( 'is-checked' );
				$( this ).removeAttr( 'value','off' )
			} else {
				$( this ).parent( 'label' ).addClass( 'is-checked' );
				$( this ).attr( 'value','on' )
			}

		}
	);

	$( document ).on(
		'click',
		'.ced_amz_child_category',
		function (e) {

			let template_id   = urlParams.get( 'template_id' ) ? urlParams.get( 'template_id' ) : '';
			let category_data = $( this ).attr( 'data-category' );
			let browseNodeId  = $( this ).attr( 'data-browseNodeId' );

			$(this).find('input').attr('checked', 'checked');

			browseNodeId = browseNodeId.replaceAll( '"', "" );

			category_data    = JSON.parse( category_data );
			let categoryData = {
				primary_category: category_data['primary-category'],
				secondary_category: category_data['sub-category'],
				browse_nodes: browseNodeId
			}

			$( '.ced_primary_category' ).val( categoryData['primary_category'] );
			$( '.ced_secondary_category' ).val( categoryData['secondary_category'] );
			$( '.ced_browse_category' ).val( categoryData['browse_nodes'] );
			$( '.ced_browse_node_name' ).val( $( this ).text() );

			$('#wpbody-content').block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});
			
			let categoryResponse = ced_amazon_fetch_next_level_category( 3, categoryData , template_id ,'no' );
			categoryResponse.then(
				response => {
				$('#wpbody-content').unblock();
				handleCategoryResponse( response )
				}
			)

		}
	);

	jQuery(document).on('click', '.ced-asin-sync-toggle-select', function (e) {

		if ( $(this).parent('label').hasClass('is-checked') ) {
		   $('.ced_amazon_catalog_asin_sync_meta_row').toggle()
			
		} else { 
			$('.ced_amazon_catalog_asin_sync_meta_row').toggle()
			
		}
	   
	});


	$( document ).ready(
		function() {


			var breadCrumbArr = [];
			var lastLevalCat  = [];
			// Function to handle the change event of the select element
			$( document ).on(
				'click',
				'.ced_amazon_category_arrow',
				function() {
					var selectedValue = $( this ).data( 'id' );
					var name          = $( this ).data( 'name' );
					var level         = $( this ).data( 'level' );
					let user_id       = urlParams.get( 'user_id' );
					var endPoint      = "webapi/rest/v1/category-all/?sAppId=2724&shop_id=" + user_id + "&selected=" + selectedValue;
					var apiUrl        = "https://amazon-sales-channel-api-backend.cifapps.com/" + endPoint;
					var bearerToken   = access_token;
					$('#wpbody-content').block({
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 0.6
						}
					});
					$.ajax(
						{
							url: apiUrl,
							type: "GET",
							data: { option: selectedValue }, // You can pass any additional data if needed
							dataType: "json",
							beforeSend: function(xhr) {
								xhr.setRequestHeader( "Authorization", "Bearer " + bearerToken );
							},
							success: function(response) {
								$('#wpbody-content').unblock();
								if ( ! response.success ) {
									if ($('.notice-error').length > 0) {
  
										$('.notice-error').html('<p>Unable to fetch categories. Try again later.</p>')
									} else {
										$(document).find('form').prepend('<div class="notice notice-error is-dismissible"><p>Unable to fetch categories. Try again later.</p></div>')
										return;
									}
									
								}
								$( '#ced_amazon_cat_header' ).html( "" );
								$( '#ced_amazon_cat_header' ).html(
									"<span data-level='" + level + "' class='dashicons dashicons-arrow-left-alt2 ced_amazon_prev_category_arrow' \
                                data-id='" + selectedValue + "'' data-name='" + name + "'' class='dashicons dashicons-arrow-right-alt2'></span><strong id='ced_cat_label'>" + name + " </strong>"
								);

								breadCrumbArr.push( name );
								ced_amazon_update_breadCrumb();

								let olList = ced_amazon_add_next_level_cat( response,level,name );

							},
							error: function(xhr, status, error) {
								$('#wpbody-content').unblock();
								if ($('.notice-error').length > 0) {
									$('.notice-error').html('<p>Unable to fetch categories. Try again later.</p>')
								} else {
									$(document).find('form').prepend('<div class="notice notice-error is-dismissible"><p>Unable to fetch categories. Try again later.</p></div>')
									return;
								}

							}
						}
					);
				}
			);

			$( document ).on(
				'click',
				'.ced_amazon_prev_category_arrow',
				function() {
					let level = $( this ).attr( 'data-level' );

					if ( level <= 0) {
						return;
					}
					$( this ).attr( 'data-level',(parseInt( level ) - 1) );
					$( '.ced_amz_categories' ).each(
						function(){
							$( this ).hide();
						}
					);
					$( '#ced_amz_categories_' + level ).show();
					
					let label = $( "#ced_amz_categories_" + level ).attr( 'data-node-value' );
					$( "#ced_cat_label" ).text( label );

					if (lastLevalCat.length >= 0 ) {
						lastLevalCat.pop();
					}

					breadCrumbArr.pop( label );
					ced_amazon_update_breadCrumb();

					return;
				
					
				}
			);


			$( document ).on(
				'click',
				'.ced_amz_child_category',
				function() {
					let val = $( this ).find('input').val();
					if (val.length <= 0) {
						lastLevalCat.push( val )
						ced_amazon_update_breadCrumb()
					} else {
						lastLevalCat.pop();
						ced_amazon_update_breadCrumb();
						lastLevalCat.push( val );
						ced_amazon_update_breadCrumb();
					}

				}
			)


			function ced_amazon_add_next_level_cat(response,level=1,name) {

				let data                     = (response && response['response'] && response['response'].length > 0) ? response['response'] : [];
				let next_level               = level + 1;
				var hasChildrenElementOption = "";
				var html                     = "";
				var element_class            = '';
				let categoryData             = '';
				let browseNodeId             = '';

				html       += '<ol id="ced_amz_categories_' + next_level + '" class="ced_amz_categories" data-level="' + next_level + '" data-node-value="' + name + '">';
				Object.keys( data )?.map(
					(val) => {
					let ids = data[val]?.parent_id.join( ',' );
						if (data[val]?.hasChildren) {
							element_class            = 'ced_amazon_category_arrow';
							hasChildrenElementOption = "<span \ class='dashicons dashicons-arrow-right-alt2 \
					' \
					\
					\
					\
					\
					class='dashicons dashicons-arrow-right-alt2'>\
                    </span>";
						} else {
							element_class            = 'ced_amz_child_category';
							categoryData             = JSON.stringify( data[val].category );
							browseNodeId             = JSON.stringify( data[val].browseNodeId );
							hasChildrenElementOption = '<input type="radio" name="ced_amazon_last_level_cat" id="ced_amazon_last_level_cat" value="' + data[val]?.name + '" />';
						}
						html += "<li  data-browseNodeId='" + browseNodeId + "' data-category='" + categoryData + "'  class='" + element_class + "' data-name='" + data[val]?.name + "' data-children=" + data[val]?.hasChildren + " id='" + ids + "' data-id=" + ids + " data-level=" + next_level + ">" + data[val].name + "" + hasChildrenElementOption + "</li>"

					}
				);
				html += '</ol>';
				$( '#ced_amz_categories_' + (level + 1) ).remove();
				$( '#ced_amz_categories_' + level ).after( html );
				$( '#ced_amz_categories_' + level ).hide();

			}

			function ced_amazon_update_breadCrumb(){
				var breadCrumbHtml = "";

				for (var i = 0; i < breadCrumbArr.length; i++) {

					if (i === 0) {
						breadCrumbHtml += breadCrumbArr[i];
					} else {
						breadCrumbHtml += " > " + breadCrumbArr[i];

					}

				}
				if (lastLevalCat.length > 0) {
					breadCrumbHtml += " > " + lastLevalCat.join( " " );
				}
				if ( breadCrumbArr.length > 0 ) {
					$( "#ced_amazon_breadcrumb" ).css('display', 'block');
				} else {
					$( "#ced_amazon_breadcrumb" ).css('display', 'none');
				}
				
				$( "#ced_amazon_breadcrumb" ).text( breadCrumbHtml );
				$( ".ced_amz_cat_name_arr" ).val( breadCrumbHtml );
			}

		}
	);

	$( document ).on(
		'click',
		'.woocommerce-importer-done-view-errors-amazon',
		function(){
			$( '.wc-importer-error-log-amazon' ).slideToggle( 'slow' );
					return false;
		}
	);



	$( document ).on(
		'click',
		'.save_profile_button',
		function(e){

			let woo_val = $('.wooCategories').find(":selected").val();
			let section = urlParams.get('section');

			if (!woo_val) {
				e.preventDefault(e);
				customSwal({
					title: 'WooCommerce Category',
					text:  'Please select a WooCommerce category.',
					icon:  'error',
				})
				return;
			} 

			let val = $('.ced_browse_category').val();

			if ( section == 'add-new-template') {
				if (!val) {
					e.preventDefault(e);
					customSwal({
						title: 'Amazon Category',
						text:  'Please select an Amazon category.',
						icon:  'error',
					})
					return;
				} 

			} 
			


		}
	);


	var custom_uploader;

	$(document).on('click', '.ced_amazon_upload_image_button', function (e) {
		e.preventDefault();

		let woo_used_categories = $(this).attr('data-woo-used-cat');
		let woo_all_categories  = $(this).attr('data-woo-all-cat');

		woo_used_categories = JSON.parse(woo_used_categories);
		woo_all_categories  = JSON.parse(woo_all_categories);


		if (woo_all_categories.sort().join(',') === woo_used_categories.sort().join(',')) {

			customSwal({
				title: 'Woocommerce Category',
				text: 'All existing woocommerce category are already mapped. Please create new woocommerce category or remove some woocommerce category from existed mapped profiles.',
				icon: 'error',
			})
			return;

		} else { 

			var $upload_button = $(this);

			// Extend the wp.media object
			custom_uploader = wp.media.frames.file_frame = wp.media({
				title: 'Choose File',
				button: {
					text: 'Choose File'
				},
				multiple: false
			});

			//When a file is selected, grab the URL and set it as the text field's value
			custom_uploader.on('select', function () {
				var attachment = custom_uploader.state().get('selection').first().toJSON();

				let obj, fileName, fileUrl;
				if (attachment.hasOwnProperty('filename') && attachment.filename.length > 0) {

					fileName            = attachment.filename;
					fileUrl             = attachment.url;
					const filenameArray = fileName.split(".");
					let ext             = filenameArray[filenameArray.length - 1];

					if (ext == 'xls' || ext == 'xlsm') {
						obj = { status: true, title: 'File Uploaded', text: 'Product template has been uploaded.', icon: 'success' };
					} else {
						obj = { status: false, title: 'File Uploaded', text: 'Product template upload has been failed. Invalid file extension or type.', icon: 'error' };
					}

				} else {
					obj = { status: false, title: 'Select File', text: 'Please select a file to upload.', icon: 'error' };

				}

				// swal(obj);

				if (obj.status) {
					jQuery( '#wpbody-content' ).append( amazon_loader_overlay );

					jQuery.ajax({
						type: 'post',
						url: ajaxUrl,
						dataType: 'html',
						data: {
							user_id: user_id,
							seller_id: urlParams.get('seller_id'),
							ajax_nonce: ajaxNonce,
							fileUrl: fileUrl,
							fileName: fileName,
							action: 'ced_amazon_prepare_template'
						},
						success: function (response) {

							response = JSON.parse(response);
							

							$('.ced_browse_category').val(response.session.browseNodeID);
							$('.ced_amz_cat_name_arr').val(response.session.browseNodePath);
							$('.ced_amz_cat_name').text(response.session.browseNodePath);


							// jQuery('#wpbody-content .ced_amazon_overlay').remove();
							$('.upload-template-response-modal').append(response.data);
							$('.ced_amazon_add_missing_field_title').val('');
							$('.ced_amazon_add_missing_field_slug').val('');
							$('.custom_category_attributes_select').selectWoo({ "width": "400" });
							$('#optionalFields').selectWoo();
							remove_custom_notice();
							$('#uploadTemplateModal').show();
							//$( '#TemplateModal' ).animate({display: "block"}, 'slow' ,'swing' );
							// createTooltip();

							jQuery('.woocommerce-help-tip').tipTip( {
								attribute: 'data-tip',
								content: jQuery('.woocommerce-help-tip').attr('data-tip'),
								fadeIn: 50,
								fadeOut: 50,
								delay: 200,
								keepAlive: true,
							} );

						},
						error: function (error) {
							jQuery('#wpbody-content .ced_amazon_overlay').remove();
						}
					}
					)
				}

			});

			//Open the uploader dialog
			custom_uploader.open();

		}	

	});



	jQuery( document ).on( 'click','.ced-amz-profile-clone',
		function (e) {

			let woo_used_categories = $(this).attr('data-woo-used-cat');
			let woo_all_categories  = $(this).attr('data-woo-all-cat');

			woo_used_categories = JSON.parse(woo_used_categories);
			woo_all_categories  = JSON.parse(woo_all_categories);


			if (woo_all_categories.sort().join(',') === woo_used_categories.sort().join(',')) {

				customSwal({
					title: 'Woocommerce Category',
					text: 'All existing woocommerce category are already mapped. Please create new woocommerce category or remove some woocommerce category from existed mapped profiles.',
					icon: 'error',
				})
				return;

			} 

			let template_id = $(this).data('clone_tmp_id');
			$('.ced-amz-clone-tmp').attr('clone_tmp_id', template_id)

			var modal           = document.getElementById("cloneTemplateModal");
			modal.style.display = "block";
						
					
		}

	);


	jQuery( document ).on( 'click','.ced_clone_modal_cancel',
		function (e) {


			var modal           = document.getElementById("cloneTemplateModal");
			modal.style.display = "none";

			$('.ced_modal_notice').remove();

			$('#cloneTemplateModal').find('.wooCategories').selectWoo('destroy');
			$('#cloneTemplateModal').find('.wooCategories').val([]);
			$('#cloneTemplateModal').find('.wooCategories').selectWoo();

			$('.ced_amazon_clone_template_button').removeAttr('disabled')

			
			let refreshPage = $(this).attr("refresh");

			if ( refreshPage == "true" ) {
				window.location.reload();
			}

		})


	jQuery( document ).on( 'click','.ced_amazon_clone_template_button', 
		function (e) {
			e.preventDefault();
			let template_id = $('.ced-amz-clone-tmp').attr('clone_tmp_id');

			
			if ( notNullAndEmpty(template_id) ) {

				let woo_cat = $('#cloneTemplateModal').find('.wooCategories').val();
				
				if ( woo_cat.length <= 0 ) {

					customModalSwal({
						title: 'Error',
						text: 'Please select atleast one WooCommerce category.',
						icon: 'error',
					}, () => { }, '' , 'class', 'ced-amz-clone-tmp', true );
					return;

				}

				$('#wpbody-content').block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});

				jQuery.ajax({
					type: 'post',
					url: ajaxUrl,
					dataType: 'html',
					data: {
						user_id: user_id,
						seller_id: urlParams.get('seller_id'),
						ajax_nonce: ajaxNonce,
						template_id: template_id,
						woo_cat: woo_cat,
						action: 'ced_amazon_clone_template_modal'
					},
					success: function (response) {

						$('#wpbody-content').unblock();
						
						response = JSON.parse(response);

						$( '.ced-amz-clone-tmp' ).find('.notice').remove()
						if ( response.success ) {

							$('.ced_clone_modal_cancel').attr('refresh', "true");
							customModalSwal({
								title: 'Success',
								text: response.data,
								icon: 'success',
							}, () => { }, '' , 'class', 'ced-amz-clone-tmp', true )

							$('.ced_amazon_clone_template_button').attr('disabled','disabled');
						} else {
							customModalSwal({
								title: 'Error',
								text: response.data,
								icon: 'error',
							}, () => { }, '' , 'class', 'ced-amz-clone-tmp', true )
						}

					}
					
				}
				)


			} else {
				
				customModalSwal({
					title: 'Error',
					text: 'Unable to Clone template currently. Please try again later.',
					icon: 'error',
				}, () => { }, '' , 'class', 'ced-amz-clone-tmp', true )
			}

		})


	function customModalSwal( swalObj = {}, callback , time = '', tag_type, tag_name, is_dismissible ){

		let notice = "";

		let title = swalObj.title ? swalObj.title : '';
		let text  = swalObj.text ? swalObj.text : '';

		let tag = '';
		if ( tag_type = 'class' ) {
			tag = '.' + tag_name ;
		} else if ( tag_type = 'id' ) {
			tag = '#' + tag_name; 
		}

		let isDismissible = '';
		if ( is_dismissible ) {
			isDismissible = 'is-dismissible';
		}

		if ( swalObj.icon == "success") {

			notice += "<div  class='ced_modal_notice notice notice-success " + isDismissible + " '><p> <b>" + title + "</b>. " + text + " </p></div>";

			if ( $( ".notice-success" ).length == 0 ) {
				$( tag ).prepend( notice );
			} else {
				$( tag ).find('.notice-success').html('<p><b>'+ title + '</b>. ' + text + '</p>')
			}

		} else if ( swalObj.icon == 'error') {

			notice += "<div  class='ced_modal_notice notice notice-error " + isDismissible + " '><p> <b>" + title + "</b>. " + text + " </p></div>";
		
			if ( $( ".notice-error" ).length == 0 ) {
				
				$('.ced-amz-clone-tmp').prepend( notice );
			} else {
				
				$( tag ).find('.notice-error').html('<p><b>'+ title + '</b>. ' + text + '</p>')
			}

		} else if ( swalObj.icon == 'warning') {

			notice += "<div class='ced_modal_notice notice notice-warning " + isDismissible + " '><p> <b>" + title + "</b>. " + text + " </p></div>";

			if ( $( ".notice-warning" ).length == 0 ) {
				$( tag ).prepend( notice );
			} else {
				$( tag ).find('.notice-warning').html('<p><b>'+ title + '</b>. ' + text + '</p>')
			}

		}

		if ( time.length > 0 ) {

			setTimeout( () => {
				$( tag ).find( '.notice' ).remove();
				callback();
			}, time )

		} 
		

	}


	
	jQuery( document ).on( 'change','#ced_amazon_order_currency', 
		function (e) {
		
			if ( $( this ).is(":checked") ) {
   
				$('.ced_amz_currency_convert_row').css( 'display', 'contents')

			} else {
				$('.ced_amz_currency_convert_row').css( 'display', 'none')
			}


		})

	

})( jQuery );
