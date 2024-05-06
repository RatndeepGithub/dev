(function( $ ) {
	'use strict';

	var ajaxUrl   = ced_ebay_admin_obj.ajax_url;
	var ajaxNonce = ced_ebay_admin_obj.ajax_nonce;
	var user_id   = ced_ebay_admin_obj.user_id;
	var siteUrl   = ced_ebay_admin_obj.site_url;
	var siteId    = ced_ebay_admin_obj.site_id;

	$( document ).on(
		'click',
		'.woocommerce-importer-done-view-errors-ebay',
		function(){
			$( '.wc-importer-error-log-ebay' ).slideToggle( 'slow' );
					return false;
		}
	);

	jQuery( document ).on(
		'click',
		'.ced-ebay-categories-import-select',
		function (e) {
	
			if ( $( this ).parent( 'label' ).hasClass( 'is-checked' ) ) {
			$( '.ced_ebay_cat_import_row' ).toggle()
	
			} else {
			$( '.ced_ebay_cat_import_row' ).toggle()
	
			}
	
		}
		);

	$( document ).on(
		'change',
		'.ced_ebay_change_acc',
		function (e) {
			$( '#wpbody-content' ).block(
				{
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				}
			);
			let href = $( 'select[name="ced_ebay_change_acc"] :selected' ).attr( 'data-href' );

			window.location.href = href;

		}
	)

	jQuery( document ).on(
		'click',
		'.ced_ebay_disconnect_account_btn',
		function(e){
			e.preventDefault();
			var site      = $( this ).data( "site" );
			var ebay_user = $( this ).data( "ebay-user" );
			$( '#wpbody-content' ).block(
				{
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				}
			);
			jQuery.ajax(
				{
					type:'post',
					url: ajaxUrl,
					data: {
						userid: ebay_user,
						site_id: site,
						ajax_nonce: ajaxNonce,
						action: 'ced_ebay_remove_account_from_integration'
					},
					success: function(response){
						$( '#wpbody-content' ).unblock()
						window.location.reload();

					}
				}
			)
		}
	);

	$( document ).on(
		'click',
		'#ced_ebay_bulk_operation',
		function(e){
			e.preventDefault();
			var operation = $( ".ced_ebay_select_ebay_product_action" ).val();
			if (operation <= 0 ) {
				var notice = "";
				notice    += "<div class='notice notice-error'><p>Please Select Operation To Be Performed</p></div>";
				$( ".success-admin-notices" ).append( notice );
			} else {
				if (operation == 'create_ads') {
					$( '.ced-ebay-bulk-create-ads-modal-wrapper' ).show();
					$( '.ced-ebay-bulk-create-ads-trigger' ).click(
						function() {
							$( '.ced-ebay-bulk-create-ads-modal-wrapper' ).hide();
						}
					);
					return;

				}
				var operation        = $( ".ced_ebay_select_ebay_product_action" ).val();
				var ebay_products_id = new Array();
				$( '.ebay_products_id:checked' ).each(
					function(){
						ebay_products_id.push( $( this ).val() );
					}
				);
				performBulkAction( ebay_products_id,operation );
			}

		}
	);

	$( document ).on(
		'change',
		'#ced_ebay_scheduler_info',
		function(){

			if (this.checked) {
				$( ".ced_ebay_scheduler_info" ).css( 'display','contents' );

			} else {
				$( ".ced_ebay_scheduler_info" ).css( 'display','none' );

			}
		}
	);

	function performBulkAction(ebay_products_id,operation)
		{
		if (ebay_products_id == "") {
			var notice = "";
			notice    += "<div class='notice notice-error is-dismissiable' style='margin-left:0px;margin-top:15px;'><p>No Products Selected</p><button type='button' class='notice-dismiss'><span class='screen-reader-text'>Dismiss this notice.</span></button></div>";
			$( ".ced-ebay-products-view-notice" ).append( notice );
			return;
		}
		$( '#wpbody-content' ).block(
			{
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			}
		);
		$.ajax(
			{
				url : ajaxUrl,
				data : {
					ajax_nonce : ajaxNonce,
					action : 'ced_ebay_process_bulk_action',
					operation_to_be_performed : operation,
					id : ebay_products_id,
					userid:user_id,
					site_id:siteId
				},
				type : 'POST',
				success: function(response)
				{
					$( '#wpbody-content' ).unblock()
					var response  = jQuery.parseJSON( response );
					var response1 = jQuery.trim( response.message );
					if (response.status == 200) {
						var id               = response.prodid;
						var Response_message = jQuery.trim( response.message );
						var product_title    = jQuery.trim( response.title );
						var notice           = "";

							notice += "<div class='notice notice-success' style='margin-left:0px;margin-top:15px;'><p><b>" + response.title + "</b> >> " + response.message + ". Please refresh the page!</p></div>";

						$( ".ced-ebay-products-view-notice" ).append( notice );
						if (Response_message == 'Product Deleted Successfully') {
							$( "#" + id + "" ).html( '<b class="not_completed">Not Uploaded</b>' );
							$( "." + id + "" ).remove();
						} else {
							$( "#" + id + "" ).html( '<b class="success_upload_on_ebay">Uploaded</b>' );
						}

						var remainig_products_id = ebay_products_id.splice( 1 );
						if (remainig_products_id == "") {
							return;
						} else {
							performBulkAction( remainig_products_id,operation );
						}

					} else if (response.status == 400) {
						var notice = "";
						notice    += "<div class='notice notice-error' style='margin-left:0px;margin-top:15px;'><p><b>" + response.title + "</b> >> " + response.message + "</p></div>";
						$( ".ced-ebay-products-view-notice" ).append( notice );
						var remainig_products_id = ebay_products_id.splice( 1 );
						if (remainig_products_id == "") {
							return;
						} else {
							performBulkAction( remainig_products_id,operation );
						}

					}
				}
			}
		);
	}

	jQuery( document ).on(
		'click',
		"#ced_ebay_submit_order_fulfillment",
		function(event){
			event.preventDefault();
			var order_id        = $( this ).data( 'order_id' );
			var trackingNumber  = $( '#umb_ebay_tracking_number' ).val();
			var shippingService = $( '#ced_ebay_shipping_service_selected option:selected' ).val();
			if (shippingService == -1) {
				alert( 'Please select a shipping service' );
				return;
			}
			jQuery( '#wpbody-content' ).append( ebay_loader_overlay );
			jQuery( "#ced_ebay_progress_text" ).html( "Fulfilling eBay Order" );
			var data = {
				'action':'ced_ebay_fulfill_order',
				ajax_nonce : ajaxNonce,
				userid : user_id,
				order_id: order_id,
				tracking_number: trackingNumber,
				shipping_service: shippingService
			};
			jQuery.post(
				ajaxUrl,
				data,
				function(response){
					jQuery( '#wpbody-content .ced_ebay_overlay' ).remove();
					alert( response.message );
				}
			);
		}
	);

	$( document ).on(
		'click',
		'.ced-ebay-refresh-categories',
		function(e){
			jQuery( '.ced-ebay-new-template-panel' ).block(
				{
					message: 'Starting to fetch eBay categories',
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				}
			);
			var levels_arr = [1,2,3,4,5,6,7,8];
			performCategoryRefresh( levels_arr );
		}
	);

	function performCategoryRefresh(levels_arr){

		if (levels_arr == '') {
			var notice = "";
			notice    += "<div class='notice notice-error'><p>Categories not found.</p></div>";
			$( ".success-admin-notices" ).html( notice );
		}
				$.ajax(
					{
						url: ajaxUrl,
						data: {
							ajax_nonce : ajaxNonce,
							action : 'ced_ebay_category_refresh_button',
							userid:user_id,
							levels: levels_arr,
							site_id: siteId
						},
						type: 'POST',
						success: function(response){
							response           = jQuery.parseJSON( response );
							var category_level = response.level;
							if (response.status == 'error') {
								jQuery( '.ced-ebay-new-template-panel' ).block(
									{
										message: response.message,
										overlayCSS: {
											background: '#fff',
											opacity: 0.6
										}
									}
								);
								window.setTimeout( function(){window.location.reload()}, 2000 );
							} else {
								notice = "Imported " + category_level + " out of 8 Category Files Successfully";
								jQuery( '.ced-ebay-new-template-panel' ).block(
									{
										message: notice,
										overlayCSS: {
											background: '#fff',
											opacity: 0.6
										}
									}
								);
								// jQuery('#ced_ebay_progress_text').html(notice);
								var remaining_levels_arr = levels_arr.splice( 1 );
								if (remaining_levels_arr != '') {
									performCategoryRefresh( remaining_levels_arr );
								} else {

									// jQuery('#ced_ebay_progress_text').html('All Category Files Imported Successfully');
									jQuery( '.ced-ebay-new-template-panel' ).block(
										{
											message: 'All Category Files Imported Successfully',
											overlayCSS: {
												background: '#fff',
												opacity: 0.6
											}
										}
									);
									window.setTimeout( function(){window.location.reload()}, 2000 );

								}
							}

						}
					}
				)

	}

	$( document ).on(
		'click',
		'#ced_ebay_fetch_orders',
		function(event)
		{
			   event.preventDefault();
			   var store_id = $( this ).attr( 'data-id' );
			$( '#wpbody-content' ).block(
				{
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				}
			);
			$.ajax(
				{
					url : ajaxUrl,
					data : {
						ajax_nonce : ajaxNonce,
						action : 'ced_ebay_get_orders',
						userid:store_id,
						site_id: siteId
					},
					type : 'POST',
					success: function(response)
				{
						 $( '#wpbody-content' ).unblock();
						if (response.success) {
							CedEbayDisplayAdminMessage( response.data.message, '', true );
						} else {
							CedEbayDisplayAdminMessage( response.data.message, '', true );
						}

					}
				}
			);
		}
	);
	$( document ). on(
		'click',
		'#ced_ebay_profile_bulk_operation',
		function(e){
			e.preventDefault();
			var operation = $( ".bulk-action-selector" ).val();
			if (operation <= 0 ) {
				CedEbayDisplayAdminMessage( 'Please select profile first', 'red' );
			} else {
				var operation   = $( ".bulk-action-selector" ).val();
				var profile_ids = new Array();
				$( '.ebay_profile_ids:checked' ).each(
					function(){
						profile_ids.push( $( this ).val() );
					}
				);
				performProfileBulkAction( profile_ids, operation );
			}

		}
	);

	function performProfileBulkAction(profile_ids, operation)
	{
		if (profile_ids == "") {
			var notice = "";
			CedEbayDisplayAdminMessage( 'Please select profile first', 'red' );
		}
		$( '#wpbody-content' ).block(
			{
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			}
		);
				$.ajax(
					{
						url : ajaxUrl,
						data : {
							ajax_nonce : ajaxNonce,
							action : 'ced_ebay_process_profile_bulk_action',
							operation_to_be_performed : operation,
							profile_ids : profile_ids,
							user_id:user_id,
							site_id:siteId
						},
						type : 'POST',
						success: function(response){
							response = jQuery.parseJSON( response );
							$( '#wpbody-content' ).unblock();
							CedEbayDisplayAdminMessage( 'Selected profiles successfully deleted' );
							window.location.reload();
						}
					}
				);
	}

	function CedEbayDisplayAdminMessage(adminMessage, adminMessageColor, pageReload) {
		if (jQuery( '.error.notice' ).length > 0) {
			// Slide up and remove the existing message if any
			jQuery( '.error.notice' ).slideUp(
				300,
				function() {
					jQuery( this ).remove();
					CedEbayAddNewMessage( adminMessage, adminMessageColor, pageReload );
				}
			);
		} else {
			CedEbayAddNewMessage( adminMessage, adminMessageColor, pageReload );
		}
	}

	function CedEbayAddNewMessage(adminMessage, adminMessageColor, pageReload) {
		// Add the new message
		var newMessage = '<div class="error notice is-dismissible"><p>' + adminMessage + '</p><button id="ced-ebay-dismiss-admin-message" class="notice-dismiss" type="button"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
		jQuery( '#ced-ebay-admin-message' ).after( newMessage );

		// Add click event to the dismiss button
		jQuery( "#ced-ebay-dismiss-admin-message" ).click(
			function(event) {
				event.preventDefault();
				jQuery( '.error' ).slideUp(
					300,
					function() {
						jQuery( this ).remove();
					}
				);
				if (pageReload) {
					window.location.reload();
				}
			}
		);

		// Change the color of the message based on adminMessageColor
		switch (adminMessageColor) {
			case 'yellow':
				jQuery( "div.error" ).css( "border-left", "4px solid #ffba00" );
				break;
			case 'red':
				jQuery( "div.error" ).css( "border-left", "4px solid #dd3d36" );
				break;
			default:
				jQuery( "div.error" ).css( "border-left", "4px solid #00a32a" );
		}
	}

	jQuery( document ).on(
		'click',
		'#ced_ebay_profile_edit_btn',
		function(event){
			event.preventDefault();
			$( '.ced_ebay_loader' ).show();
			var eBayCatId = jQuery( this ).data( 'ebay-cat-id' );
			var profileID = jQuery( this ).data( 'profile-id' );
			if (eBayCatId == '' || profileID == '') {
				$( '.ced_ebay_loader' ).hide();
				Swal.fire(
					{
						title: "Error",
						text: 'Failed to get the eBay Category ID or profile ID',
						icon: 'error',
					}
				);
			}
			jQuery.ajax(
				{
					url: ajaxUrl,
					type: 'post',
					data: {
						ajax_nonce: ajaxNonce,
						user_id: user_id,
						action: 'ced_ebay_check_if_able_to_fetch_item_aspect',
						ebay_cat_id : eBayCatId,
						profile_id: profileID
					},
					success: function (response){
						$( '.ced_ebay_loader' ).hide();
						if (response.success == false) {
							Swal.fire(
								{
									title: "Error reading data",
									text: response.data.message,
									icon: 'error',
								}
							);
						}
						if (response.success == true) {
							window.open( response.data.url, '_blank' );

						}

					}
				}
			)

		}
	);

	jQuery( document ).on(
		"click",
		".ced_ebay_update_business_policies",
		function(e){
			e.preventDefault();
			$( '#wpbody-content' ).block(
				{
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}

				}
			);
			jQuery.ajax(
				{
					url: ajaxUrl,
					type: 'post',
					data: {
						ajax_nonce: ajaxNonce,
						user_id: user_id,
						action: 'ced_ebay_async_ajax_handler',
						route: 'ced_ebay_sync_business_policies',
						site_id: siteId

					},
					success: function (response){
						$( '#wpbody-content' ).unblock();
						if (response.success) {
							CedEbayDisplayAdminMessage( response.data.message );
						} else {
							CedEbayDisplayAdminMessage( response.data.message, 'red' );
						}

					}
				}
			)
		}
	)

	// handling promotion actions

	jQuery( document ).on(
		"click",
		".notice-dismiss",
		function(event){
			event.preventDefault();
			jQuery( document ).find( ".notice" ).fadeTo(
				100,
				0,
				function(){
					jQuery( document ).find( ".notice" ).slideUp(
						100,
						function(){
							jQuery( document ).find( ".notice" ).remove();
						}
					);
				}
			);
		}
	);

	jQuery( document ).on(
		'click',
		'#ced_ebay_remove_all_profiles_btn',
		function(e){
			e.preventDefault();
			$( '#wpbody-content' ).block(
				{
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				}
			);
			jQuery.ajax(
				{
					type:'post',
					url: ajaxUrl,
					data: {
						userid: user_id,
						site_id: siteId,
						ajax_nonce: ajaxNonce,
						action: 'ced_ebay_remove_all_profiles'
					},
					success: function(response){
						$( '#wpbody-content' ).unblock();
						if (response.success) {
							CedEbayDisplayAdminMessage( response.data.message, '', true );
						} else {
							CedEbayDisplayAdminMessage( response.data.message, 'red', true );
						}
					}
				}
			)
		}
	);

	jQuery( document ).on(
		'click',
		'#ced_ebay_reset_item_aspects_btn',
		function(e){
			e.preventDefault();
			$( '#wpbody-content' ).block(
				{
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				}
			);
			jQuery.ajax(
				{
					type:'post',
					url: ajaxUrl,
					data: {
						userid: user_id,
						site_id: siteId,
						ajax_nonce: ajaxNonce,
						action: 'ced_ebay_reset_category_item_specifics'
					},
					success: function(response){
						$( '#wpbody-content' ).unblock();
						if (response.success) {
							CedEbayDisplayAdminMessage( response.data.message, '', true );
						} else {
							CedEbayDisplayAdminMessage( response.data.message, 'red', true );
						}
					}
				}
			)
		}
	);

	jQuery( document ).on(
		'click',
		'#ced_ebay_remove_term_from_profile_btn',
		function(e){
			e.preventDefault();
			var termID    = jQuery( this ).attr( 'data-term-id' );
			var profileID = jQuery( this ).attr( 'data-profile-id' );
			$( '#wpbody-content' ).block(
				{
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				}
			);				jQuery.ajax(
				{
					type:'post',
					url: ajaxUrl,
					data: {
						userid: user_id,
						site_id: siteId,
						term_id: termID,
						profile_id : profileID,
						ajax_nonce: ajaxNonce,
						action: 'ced_ebay_remove_term_from_profile'
					},
					success: function(response){
						$( '#wpbody-content' ).unblock();
						if (response.success) {
							CedEbayDisplayAdminMessage( response.data.message, '' );
						} else {
							CedEbayDisplayAdminMessage( response.data.message, 'red' );
						}
					}
				}
			);
		}
	);

	jQuery( document ).on(
		'click',
		'#ced_ebay_del_blk_upld_logs_btn',
		function(e){
			e.preventDefault();
			jQuery( '#wpbody-content' ).append( ebay_loader_overlay );
			jQuery( "#ced_ebay_progress_text" ).html( "Clearing logs..." );
			jQuery.ajax(
				{
					type:'post',
					url: ajaxUrl,
					data: {
						userid: user_id,
						ajax_nonce: ajaxNonce,
						action: 'ced_ebay_delete_bulk_upload_logs_action'
					},
					success: function(response){
						jQuery( '#wpbody-content .ced_ebay_overlay' ).remove();
						response = jQuery.parseJSON( response );
						window.location.reload();

					}
				}
			)

		}
	);

	jQuery( document ).on(
		'click',
		'#ced_ebay_fetch_single_order_btn',
		function(e){
			e.preventDefault();
			$( '#wpbody-content' ).block(
				{
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				}
			);
			var order_id = '';
			var order_id = jQuery( document ).find( "#ced_ebay_fetch_single_order_input" ).val();
			if (order_id == '') {
				order_id = jQuery( this ).attr( 'data-ebayorderid' );
				if (order_id == '') {
					alert( 'Please enter a valid eBay Order ID' );
					return;
				}

			}

			jQuery.ajax(
				{
					type:'post',
					url: ajaxUrl,
					data: {
						userid: user_id,
						ajax_nonce: ajaxNonce,
						order_id: order_id,
						site_id: siteId,
						action: 'ced_ebay_fetch_order_using_order_id'
					},
					success: function(response){
						$( '#wpbody-content' ).unblock();
						if (response.success) {
							CedEbayDisplayAdminMessage( response.data.message, 'green', true );
						} else {
							CedEbayDisplayAdminMessage( response.data.message, 'red', true );
						}
					}
				}
			)
		}
	);

	$( document ).on(
		'change',
		'input[name="ced_ebay_global_settings[ced_ebay_import_ebay_categories]"]',
		function(){
			if ( jQuery( 'input[name="ced_ebay_global_settings[ced_ebay_import_ebay_categories]"]:checked' ).val() == 'Enabled') {
				jQuery( '#ced_ebay_select_categories_type_to_import' ).show();
			} else if ( jQuery( 'input[name="ced_ebay_global_settings[ced_ebay_import_ebay_categories]"]:checked' ).val() == 'Disabled') {
				jQuery( '#ced_ebay_select_categories_type_to_import' ).hide();

			}
		}
	);

	$(document).on(
		'click',
		'#ced_ebay_check_token_status_btn',
		function(e){
			e.preventDefault();
			$( '.ced-ebay-account-details-section' ).block(
				{
					message: '',
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				}
			);

			$.ajax(
				{
					url: ajaxUrl,
					method: 'POST',
					dataType: 'json',
					data: {
						action: 'ced_ebay_ajax_check_token_status',
						user_id : user_id,
						site_id : siteId,
						ajax_nonce: ajaxNonce
					},
					success: function(response) {
						$( '.ced-ebay-account-details-section' ).unblock();
						if (response.success) {
							CedEbayDisplayAdminMessage( response.data.message );
						} else {
							CedEbayDisplayAdminMessage( response.data.message, 'red' );
						}
					},
				}
			);
		}
	)
	$( document ).on(
		'click',
		'.ced_ebay_add_custom_item_aspects',
		function(e){

			e.preventDefault();
			e.stopPropagation();

			let title = $( '.ced_ebay_add_custom_item_aspect_title' ).val();
			let existing_custom_item_aspects_json;
			// title = title.replace( " ", "+e");
			let existing_custom_item_aspects_string = $( '.ced_ebay_add_custom_item_aspect_heading' ).attr( 'data-attr' );
			existing_custom_item_aspects_string     = existing_custom_item_aspects_string.replaceAll( "+", " " );
			if ( existing_custom_item_aspects_string !== '' ) {

				existing_custom_item_aspects_json = JSON.parse( existing_custom_item_aspects_string );

				if ( existing_custom_item_aspects_json.hasOwnProperty( title ) ) {
					let html = '<tr class="ced_ebay_add_custom_item_aspect_error" ><td colspan="3">Please enter another custom title. Same custom title has already been used.</td></tr>'
					$( '.ced_ebay_add_custom_item_aspect_row' ).before( html );

					setTimeout(
						() => {
							$( '.ced_ebay_add_custom_item_aspect_error' ).remove();
						},
						3000
					)
					return;
				}

			}

			if ( title.length <= 0  ) {
				let html = '<tr class="ced_ebay_add_custom_item_aspect_error" ><td colspan="3">Please enter custom item aspects title.</td></tr>'
				$( '.ced_ebay_add_custom_item_aspect_row' ).before( html );

				setTimeout(
					() => {
					$( '.ced_ebay_add_custom_item_aspect_error' ).remove();
					},
					3000
				)

			} else {

				if ( existing_custom_item_aspects_string == '' ) {
					existing_custom_item_aspects_json        = {};
					existing_custom_item_aspects_json[title] = title;
				} else {
					existing_custom_item_aspects_json        = JSON.parse( existing_custom_item_aspects_string );
					existing_custom_item_aspects_json[title] = title;
				}

				$( '.ced_ebay_add_custom_item_aspect_heading' ).attr( 'data-attr' , JSON.stringify( existing_custom_item_aspects_json ) )

				jQuery( '#wpbody-content' ).append( ebay_loader_overlay );

				jQuery.ajax(
					{
						type:'post',
						url: ajaxUrl,
						dataType: 'html',
						data: {
							user_id: user_id,
							ajax_nonce: ajaxNonce,
							title: title,
							action: 'ced_ebay_add_custom_item_aspects_row'
						},
						success: function(response){

							jQuery( '#wpbody-content .ced_ebay_overlay' ).remove();
							$( '.ced_ebay_add_custom_item_aspect_heading' ).after( response );
							$( '.ced_ebay_add_custom_item_aspect_title' ).val( '' );
							$( '.ced_ebay_item_specifics_options' ).selectWoo( {width: '90%'} );
							$( '.ced_ebay_search_item_sepcifics_mapping' ).selectWoo( {width: '90%'} );

						}
					}
				)

			}

		}
	)

	$(
		function() {
			$( document ).on(
				'keyup',
				'.ced_ebay_type_custom_value',
				function(e){
					e.preventDefault();
					if ( e.keyCode == 20 ) {
						return; }

					if ( $( this ).val().length == 0 ) {
						let name = $( this ).attr( 'name' );
						if ($( this ).val() == '') {
							showOptions( name )

						}
					}

				}
			);
		}
	);

	$( ".ced_ebay_type_custom_value" ).change(
		function(){

			if ( $( this ).val().length == 0 ) {
				let name = $( this ).attr( 'name' );
				if ($( this ).val() == '') {
					showOptions( name )
				}
			}
		}
	);

	$( document ).on(
		'change',
		'.ced_ebay_fill_custom_value',
		function(e){

			let name = $( this ).attr( 'name' );
			if ($( this ).val() == 'customOption') {
				showCustomInput( name )

			}
		}
	)

	$( document ).on(
		'blur',
		'.ced_ebay_type_custom_value',
		function(e){

			let name = $( this ).attr( 'name' );
			// console.log( $( this ).val() );

			if ($( this ).val() == '') {
				showOptions( name )

			}
		}
	)

	function toggle( toBeHidden, toBeShown) {
		toBeHidden.hide().prop( 'disabled', true );
		toBeShown.show().prop( 'disabled', false ).focus();
	}

	function showOptions(inputName) {
		let select = $( `select[name = "${inputName}"]` );
		toggle( $( `input[name = "${inputName}"]` ), select );
		select.val( null );
	}

	jQuery( document ).on(
		'click',
		'.ced-settings-checkbox-ebay',
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
	function showCustomInput(inputName) {
		toggle( $( `select[name = "${inputName}"]` ), $( `input[name = "${inputName}"]` ) );
	}

	// Function to initiate the import process
	$( document ).ready(
		function() {
			$( document ).on(
				'click',
				'.ced_ebay_start_import',
				function() {
					var clickEvent = $( this ).attr( 'data-action' );

					$( '.ced-ebay-product-importer-section' ).block(
						{
							message: 'Please wait while we are initialising the product importer',
							overlayCSS: {
								background: '#fff',
								opacity: 0.6
							}
						}
					);
					// return;
					$.ajax(
						{
							url: ajaxUrl,
							method: 'POST',
							dataType: 'json',
							data: {
								action: 'ced_ebay_init_import_by_loader',
								user_id : user_id,
								site_id : siteId,
								ajax_nonce: ajaxNonce,
								click_event : clickEvent
							},
							success: function(response) {
								$( '.ced-ebay-product-importer-section' ).unblock();
								if (response.success) {
									CedEbayDisplayAdminMessage( response.data.message );
								} else {
									CedEbayDisplayAdminMessage( response.data.message, 'red' );
								}
							},
						}
					);
				}
			);

			$( document ).on(
				'click',
				'.ced_ebay_stop_import',
				function() {
					$( '.ced-ebay-product-importer-section' ).block(
						{
							message: null,
							overlayCSS: {
								background: '#fff',
								opacity: 0.6
							}
						}
					);
					$.ajax(
						{
							url: ajaxUrl,
							method: 'POST',
							dataType: 'json',
							data: {
								action: 'ced_ebay_stop_import_loader',
								user_id : user_id,
								site_id : siteId,
								ajax_nonce: ajaxNonce,
							},
							success: function(response) {
								location.reload();
							}
						}
					);
				}
			);

			$(document).on( 'click', '#ced_ebay_meta_box_template_select_btn', function() {
				var site_id                 = $(this).attr('data-site_id');
				var user_id                 = $(this).attr('data-user_id');
				var profile_id              = $(".ced_ebay_meta_box_template_select_" + user_id + site_id ).val();
				var product_id              = $(".ced_ebay_meta_box_template_product_id").val();
				var profile_selected_option = $(".ced_ebay_meta_box_template_select_" + user_id + site_id ).find("option:selected");
				var ebay_catid              = profile_selected_option.data("ebay_catid"); 
				if ( profile_id == "" ) {
					alert('Please Select the Template');
					return;
				}
				$('.ced-ebay-product-template-fields-panel').block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});
				$.ajax({
					url: ajaxUrl,
					method: 'POST',
					data: {
						action: 'ced_ebay_get_profile_for_meta_box',
						user_id    : user_id,
						site_id    : site_id,
						profile_id : profile_id,
						ebay_catid : ebay_catid,
						product_id : product_id,
						ajax_nonce : ajaxNonce,
					},
					success: function(response) {
						$('.ced-ebay-product-template-fields-panel').unblock();
						$(".ced_ebay_meta_box_template_section_" + user_id + site_id + " .ced_ebay_profile_details_wrapper .ced_ebay_profile_details_fields " ).html(response);
					}
				});	
			});
		
			$(document).on( 'click', '#ced_ebay_meta_box_template_reset_btn', function() {
				var site_id    = $(this).attr('data-site_id');
				var user_id    = $(this).attr('data-user_id');
				var product_id = $(this).attr('data-product_id');
				var profile_id = $(".ced_ebay_meta_box_template_select_" + user_id + site_id ).val();
				if ( profile_id == "" ) {
					alert('Please Select the Template');
					return;
				}
				if ( product_id == "" ) {
					alert('Product ID cannot be empty!!!');
					return;
				}
				$('.ced-ebay-product-template-fields-panel').block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});
				$.ajax({
					url: ajaxUrl,
					method: 'POST',
					data: {
						action     : 'ced_ebay_reset_profile_for_meta_box',
						user_id    : user_id,
						site_id    : site_id,
						product_id : product_id,
						profile_id : profile_id,
						ajax_nonce : ajaxNonce,
					},
					success: function(response) {
						response = JSON.parse(response);
						$('.ced-ebay-product-template-fields-panel').unblock();
						alert(response.message);
						location.reload();
					}
				});	
			});

		}
	);

	$(document).on( 'click', '.ced_add_meta_field_prod_spec_template', function() {
		var selectedMeta = $(this).next().val();
		$('.ced-ebay-product-template-fields-panel').block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});
		var thisForm = this;
		$.ajax({
			url: ajaxUrl,
			method: 'POST',
			data: {
				action     : 'ced_add_meta_field_prod_spec_template',
				ajax_nonce : ajaxNonce,
			},
			success: function(response) {
				$('.ced-ebay-product-template-fields-panel').unblock();
				var newDropdown = $("<select class='ced_fetched_meta_field_dropdown'>" + response + "</select>");
				if ( null != selectedMeta ) {
					newDropdown.val(selectedMeta);
				}
				$(thisForm).after(newDropdown);
				$(thisForm).hide();
			}
		});	
	});
	
	$(document).on("change", '.ced_fetched_meta_field_dropdown', function () {
		var selectedValue = $(this).val();
		var textField     = $(this).next().next().next("input[type='hidden']");
		textField.val(selectedValue);
	});

	$(document).on( 'click', '#ced_ebay_meta_box_template_update_upload_btn', function() {
		var site_id                 = $(this).attr('data-site_id');
		var user_id                 = $(this).attr('data-user_id');
		var profile_id              = $(".ced_ebay_meta_box_template_select_" + user_id + site_id ).val();
		var product_id              = [$(".ced_ebay_meta_box_template_product_id").val()];
		var profile_selected_option = $(".ced_ebay_meta_box_template_select_" + user_id + site_id ).find("option:selected");
		var ebay_catid              = profile_selected_option.data("ebay_catid"); 
		var opr                     = $(this).val();
		if ( opr == 'Upload' ) {
			opr = 'upload_product';
		} else if ( opr == 'Update' ) {
			opr = 'update_product';
		} else if ( opr == 'End/Reset' ) {
			opr = 'remove_product';
		}
		if ( profile_id == "" ) {
			alert('Please Select the Template');
			return;
		}
		$('.ced-ebay-product-template-fields-panel').block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});
		$.ajax({
			url: ajaxUrl,
			method: 'POST',
			data: {
				action: 'ced_ebay_process_bulk_action',
				userid    : user_id,
				site_id    : site_id,
				id : product_id,
				operation_to_be_performed : opr,
				ajax_nonce : ajaxNonce,
			},
	
			success: function(response){
				$('.ced-ebay-product-template-fields-panel').unblock();
				var response  = jQuery.parseJSON( response );
				var response1 = jQuery.trim( response.message );
				if (response.status == 200) {
					var id               = response.prodid;
					var Response_message = jQuery.trim( response.message );
					var product_title    = jQuery.trim(response.title);
					var notice           = "";
	
						notice += "<div class='notice notice-success' style='margin-left:0px;margin-top:15px;'><p><b>"+response.title + "</b> >> " + response.message + ". Please refresh the page!</p></div>";
	
					$( ".ced-ebay-products-view-notice" ).append( notice );
					if (Response_message == 'Product Deleted Successfully') {
						$( "#" + id + "" ).html( '<b class="not_completed">Not Uploaded</b>' );
						$( "." + id + "" ).remove();
					} else {
						$( "#" + id + "" ).html( '<b class="success_upload_on_ebay">Uploaded</b>' );
					}
				} else if (response.status == 400) {
					var notice = "";
					notice    += "<div class='notice notice-error' style='margin-left:0px;margin-top:15px;'><p><b>"+response.title + "</b> >> " + response.message + "</p></div>";
					$( ".ced-ebay-products-view-notice" ).append( notice );
				}
			}
			
		});	
	});


})( jQuery );
