(function( $ ) {
	'use strict';

	$( document ).ready(
		function(){
			$( document ).ready(
				function(){
					$( '.custom_category_attributes_select2' ).selectWoo(
						{
							dropdownPosition: 'below',
							dropdownAutoWidth : true,
							allowClear: true,
							width: 'resolve'
						}
					);

					// jQuery(".ced_ebay_multi_select_item_aspects").selectWoo({})
				}
			)
			var ajaxUrl             = ced_ebay_admin_obj.ajax_url;
			var ajaxNonce           = ced_ebay_admin_obj.ajax_nonce;
			var user_id             = ced_ebay_admin_obj.user_id;
			var site_id             = ced_ebay_admin_obj.site_id;
			var siteUrl             = ced_ebay_admin_obj.site_url;
			var ebay_loader_overlay = '<div class="ced_ebay_overlay"><div class="ced_ebay_overlay__inner"><div class="ced_ebay_overlay__content"><div class="ced_ebay_page-loader-indicator ced_ebay_overlay_loader"><svg class="ced_ebay_overlay_spinner" width="65px" height="65px" viewBox="0 0 66 66" xmlns="http://www.w3.org/2000/svg"><circle class="path" fill="none" stroke-width="6" stroke-linecap="round" cx="33" cy="33" r="30"></circle></svg></div><div class="ced_ebay_page-loader-info"><p class="ced_ebay_page-loader-info-text" id="ced_ebay_progress_text">Loading...</p><p class="ced_ebay_page-loader-info-text" style="font-size:19px;" id="ced_ebay_countdown_timer"></p></div></div></div></div>';

			const path          = window.ced_ebay_admin_obj || {}
			const { ebay_path } = path;
			// console.log( path );
			var breadCrumbArr = [];
			var lastLevalCat  = [];

			$( document ).on(
				"click",
				".ced_ebay_category_arrow",
				async function(){
					var selectedValue = $( this ).data( "id" );
					var parent_id     = $( this ).data( "parentid" );
					var site          = $( this ).data( "location" );
					var name          = $( this ).data( "name" );
					var level         = $( this ).data( "level" );
					var fileLevel     = level + 1;

					const jsonData = await ced_ebay_getJsonData( fileLevel, site );
					if (jsonData) {
						$( '#ced_ebay_cat_header' ).html( "" );
						$( '#ced_ebay_cat_header' ).html(
							"<span data-level='" + level + "' class='dashicons dashicons-arrow-left-alt2 ced_ebay_prev_category_arrow' \
					data-id='" + selectedValue + "'' data-name='" + name + "'' class='dashicons dashicons-arrow-right-alt2'></span><strong id='ced_cat_label'>" + name + " </strong>"
						);
						breadCrumbArr.push( name );
						ced_ebay_update_breadCrumb();
						let olList = ced_ebay_add_next_level_cat( jsonData,level,name,selectedValue, site );
					} else {
						console.log( "An error has occurred." );
					}

					var olElement = $( "#ced_ebay_categories_2" );
					if ( olElement.length > 0 ) {
						var liElements = olElement.find( "li" );
						if ( ! liElements.length > 0) {
							$( '.notice-error' ).show();
							$( '.notice-error p' ).text( 'There is no More Child Category' );
						}
					}

				}
			)

			$( document ).on(
				'click',
				'.ced_ebay_prev_category_arrow',
				function() {
					$( '.notice-error' ).hide();
					$( '.item-aspects' ).html( '' );
					let level = $( this ).attr( 'data-level' );
					if ( level <= 0) {
						return;
					}
					$( this ).attr( 'data-level',(parseInt( level ) - 1) );
					$( '.ced_ebay_categories' ).each(
						function(){
							$( this ).hide();
						}
					);
					$( '#ced_ebay_categories_' + level ).show();

					let label = $( "#ced_ebay_categories_" + level ).attr( 'data-node-value' );
					$( "#ced_cat_label" ).text( label );
					if (lastLevalCat.length >= 0 ) {
						lastLevalCat.pop();
					}
					breadCrumbArr.pop( label );
					ced_ebay_update_breadCrumb();
					return;

				}
			);

			$( document ).on(
				'click',
				'#ced_ebay_last_level_cat',
				function() {
					$( '.notice-error' ).hide();
					let val = $( this ).val();
					let id  = $( this ).data( 'id' );

					// Getting category specific Rendering
					$( '.item-aspects' ).html( '' );
					getItemAspects( id, val );

					$( '#_umb_ebay_category' ).attr( 'value',id );
					$( '#_umb_ebay_category_name' ).attr( 'value',val );
					if (val.length <= 0) {
						lastLevalCat.push( val )
						ced_ebay_update_breadCrumb()
					} else {
						lastLevalCat.pop();
						ced_ebay_update_breadCrumb();
						lastLevalCat.push( val );
						ced_ebay_update_breadCrumb();
					}

				}
			);

			function getItemAspects( category_id, category_name ){
				// $('#wpbody-content').append(ebay_loader_overlay);
				// $('#ced_ebay_progress_text').html('Please wait while we are loading item aspects...');
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
						type:'post',
						url: ajaxUrl,
						dataType: 'html',
						data: {
							userid: user_id,
							ajax_nonce: ajaxNonce,
							category_id: category_id,
							site_id: site_id,
							action: 'ced_ebay_get_category_item_aspects'
						},
						success: function(response){
							$( '#wpbody-content' ).unblock()
							if ( response == '400' ) {
								$( '.item-aspects' ).html( '' );
								$( '.notice-error' ).show();
								$( '.notice-error p' ).text( 'There is some error while fetching the -- ' + category_name + ' -- Category Specific' );
							} else {
								$( '.notice-error' ).hide();
								$( '.item-aspects' ).html( response );
							}

							$( '.ced_ebay_item_specifics_options' ).selectWoo();
							$( ".ced_ebay_search_item_sepcifics_mapping" ).selectWoo(
								{
									dropdownPosition: 'below',
									dropdownAutoWidth : false,
									placeholder: 'Select...',
								}
							);
						}
					}
				)
			}

			async function ced_ebay_getJsonData(level, site) {

				try {
					let response = await $.getJSON( ebay_path + "/categoryLevel-" + level + "_" + site + ".json" );
					response     = response['CategoryArray']['Category'];
					return response;
				} catch (error) {
					console.log( "An error has occurred." );
				}
			}

			async function ced_ebay_add_next_level_cat(response, level = 1, name, selectedValue, site) {
				let data                   = response.length > 0 ? response : [];
				let next_level             = level + 1;
				let level_after_next_level = next_level + 1;
				var html                   = "";

				html += '<ol data-location="' + site + '" id="ced_ebay_categories_' + next_level + '" class="ced_ebay_categories" data-level="' + next_level + '" data-node-value="' + name + '">';
				for (const val of data) {
					let parentId = val.CategoryParentID;
					let id       = val.CategoryID;
					if (selectedValue == parentId) {
						if (val.LeafCategory) {
							html += '<li data-location="' + site + '" data-name="' + val.CategoryName + '"  id="' + id + '" data-id="' + id + '" data-level="' + next_level + '">' + val.CategoryName + '<input type="radio"  name="ced_ebay_last_level_cat" id="ced_ebay_last_level_cat" data-id="' + id + '" value="' + val.CategoryName + '" /></li>';
						} else {
							html += '<li data-location="' + site + '" class="ced_ebay_category_arrow" data-name="' + val.CategoryName + '"  id="' + id + '" data-id="' + id + '" data-level="' + next_level + '">' + val.CategoryName + '<span class="dashicons dashicons-arrow-right-alt2"></span></li>';
						}
					}
				}

				html += '</ol>';
				$( '#ced_ebay_categories_' + (level + 1) ).remove();
				$( '#ced_ebay_categories_' + level ).after( html );
				$( '#ced_ebay_categories_' + level ).hide();
			}

			function ced_ebay_update_breadCrumb(){
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
				$( "#ced_ebay_breadcrumb" ).text( breadCrumbHtml );
				$( "#ebay-profile_name" ).val( breadCrumbHtml );
				if (breadCrumbHtml == '') {
					$( "#ced_ebay_breadcrumb" ).hide();
				} else {
					$( "#ced_ebay_breadcrumb" ).show();
				}

			}

		}
	);

})( jQuery );
