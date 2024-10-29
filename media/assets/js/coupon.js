/**
 * AwoCoupon
 *
 * @package WordPress AwoCoupon
 * @copyright Copyright (C) Seyi Awofadeju - All rights reserved.
 * @Website : http://awodev.com
 **/



function function_type_change(is_edit) {
	var form = document.adminForm;

	is_edit = ( is_edit == undefined ) ? false : is_edit;
	if ( ! is_edit ) {
		resetall();
	} else {
		hideall();
	}

	// clear validation errors when changing function types
	jQuery( "#adminForm" ).find( "label.error" ).remove();
	jQuery( "#adminForm" ).find( ".error" ).removeClass( "error" );

	if ( ! is_edit ) {
	}
	form.asset0_name.value = '';
	form.asset0_id.value = '';

	v_function_type = jQuery( 'input[name=function_type]:checked', 'form[name="adminForm"]' ).val();

	if ( v_function_type == '' ) {
	} else if ( v_function_type == 'shipping' ) {
		jQuery( '.f_shipping' ).show();

		// rename titles as necessary
		jQuery( '#li_section_assets0 a, #section_assets0 fieldset legend' ).html( str_product );
		jQuery( '#li_section_assets2 a, #section_assets2 fieldset legend' ).html( str_product );

		asset_type_change( 0, is_edit );

		//jQuery('#li_section_assets0 a, #section_assets0 fieldset legend').html(str_shipping);
		//form.asset0_function_type.value = 'shipping';
	} else if (v_function_type == 'coupon') {
		jQuery( '.f_coupon' ).show();

		// rename titles as necessary
		jQuery( '#li_section_assets0 a, #section_assets0 fieldset legend' ).html( str_product );

		asset_type_change( 0, is_edit );
	}

	valuedefinition_change();

	// refresh items
	jQuery( 'body' ).scrollspy( 'refresh' );
	jQuery( 'select' ).not( ".noselect2" ).select2({
		minimumResultsForSearch: 7,
		width: 'resolve'
	});

}

function asset_type_change( intype, is_edit ) {
	var form = document.adminForm;

	is_edit = ( is_edit == undefined ) ? false : is_edit;

	v_function_type = jQuery( 'input[name=function_type]:checked', 'form[name="adminForm"]' ).val();
	if ( form.elements['asset0_function_type'].value == '' ) {
		form.elements['asset0_function_type'].value = 'product';
	}
	val = form.elements['asset0_function_type'].value;

	if ( val == '' ) {
		jQuery( '#div_asset0_inner' ).hide();
	} else {
		if ( ! is_edit ) {
			var tbl = document.getElementById( 'tbl_assets' + intype );
			for ( var i = tbl.rows.length - 1; i > 0; i-- ) {
				tbl.deleteRow( i );
			}
		}
		view_some( 'asset' + intype );
		getjqdd( 'asset0_search', 'asset0_id', 'ajax_elements', val, base_url, undefined, 'btn_asset0_search' );
		jQuery( '#div_asset0_inner' ).show();
	}
	jQuery( '.aw-asset0-row' ).hide();
	jQuery( '.f_asset0_' + val ).show();

}

function couponvalue_type_change() {
	var form = document.adminForm;
	v_function_type = jQuery( 'input[name=function_type]:checked', 'form[name="adminForm"]' ).val();
	if ( v_function_type != 'coupon' ) {
		return;
	}
	if ( form.couponvalue_hidden.value != 'advanced' ) {
		return;
	}
	if ( jQuery.trim( form.coupon_value_def.value ) == '' ) {
		return;
	}

	jQuery.ajax({
		type: "POST",
		url: base_url,
		data: { type:'ajax', task:'ajax_value_definition', string:form.coupon_value_def.value, vtype:form.coupon_value_type.value },
		success: function( data ) {
			jQuery( '#value_definition_description' ).html( data );
		}
	});

}

function valuedefinition_change( is_toggle ) {
	var form = document.adminForm;
	v_function_type = jQuery( 'input[name=function_type]:checked', 'form[name="adminForm"]' ).val();
	if ( v_function_type != 'coupon' ) {
		jQuery( "#couponvalue_basic" ).show();
		return;
	}

	is_toggle = ( is_toggle == undefined ) ? false : is_toggle;

	if ( ( ! is_toggle && form.couponvalue_hidden.value == 'advanced') || ( is_toggle && form.couponvalue_hidden.value == 'basic' ) ) {
		form.couponvalue_hidden.value = 'advanced';
		jQuery( "#couponvalue_basic" ).hide();
		jQuery( "#couponvalue_advanced, #value_definition_description" ).show();

		src = jQuery( "#couponvalue_image" ).attr( "src" );
		dir = src.replace( /\\/g, '/' ).replace( /\/[^\/]*$/, '' );
		jQuery( "#couponvalue_image" ).attr( "src", dir + '/compress.png' );
	} else if ( ( ! is_toggle && form.couponvalue_hidden.value == 'basic' ) || ( is_toggle && form.couponvalue_hidden.value == 'advanced' ) ) {
		form.couponvalue_hidden.value = 'basic';
		jQuery( "#couponvalue_basic" ).show();
		jQuery( "#couponvalue_advanced, #value_definition_description" ).hide();

		src = jQuery( "#couponvalue_image" ).attr( "src" );
		dir = src.replace( /\\/g, '/' ).replace( /\/[^\/]*$/, '' );
		jQuery( "#couponvalue_image" ).attr( "src", dir + '/expand.png' );
	}

}

function resetall() {
	var form = document.adminForm;

	form.coupon_code.value = '';
	form.state.selectedIndex = 0;
	form.coupon_value_type.selectedIndex = 0;
	form.discount_type.selectedIndex = 0;
	form.coupon_value.value = '';
	form.coupon_value_def.value = '';
	jQuery( '#value_definition_description' ).html( '' );
	form.num_of_uses_total.value = '';
	form.num_of_uses_customer.value = '';
	form.min_value.value = '';
	form.min_qty.value = '';

	form.startdate_date.value = '';
	form.startdate_time.value = '';
	form.expiration_date.value = '';
	form.expiration_time.value = '';

	form.asset0_name.value = '';
	form.asset0_id.value = '';

	view_some( 'asset' );
	var tbl = document.getElementById( 'tbl_assets0' );
	for ( var i = tbl.rows.length - 1; i > 0; i-- ) {
		tbl.deleteRow( i );
	}

	jQuery( 'form[name="adminForm"] select[name="asset[0][rows][shipping][rows][][asset_id]"] option:selected' ).prop( 'selected', false );
	jQuery( 'form[name="adminForm"] select[name="asset[0][rows][user][rows][][asset_id]"] option:selected' ).prop( 'selected', false );
	jQuery( 'form[name="adminForm"] select[name="asset[0][rows][usergroup][rows][][asset_id]"] option:selected' ).prop( 'selected', false );
	jQuery( 'form[name="adminForm"] select[name="asset[0][rows][country][rows][][asset_id]"] option:selected' ).prop( 'selected', false );
	jQuery( 'form[name="adminForm"] select[name="asset[0][rows][countrystate][rows][][asset_id]"] option:selected' ).prop( 'selected', false );
	jQuery( 'form[name="adminForm"] select[name="asset[0][rows][paymentmethod][rows][][asset_id]"] option:selected' ).prop( 'selected', false );

	hideall();
}
function hideall() {
	jQuery( '.hide' ).hide();
}

function view_some(type) {
	jQuery( '#div_' + type + '_simple_table' ).show();
	jQuery( '#div_' + type + '_advanced_table' ).hide();
	if ( document.adminForm.elements['_' + type + 'list'] != undefined ) {
		document.adminForm.elements['_' + type + 'list'].options.length = 0;
	}
	jQuery( '#div_' + type + '_advanced_grid' ).hide();
	jQuery( '#' + type + '_search_grid_table' ).DataTable().clear().destroy();

	jQuery( '#img_' + type + '_simple_link,#img_' + type + '_advanced_link,#img_' + type + '_grid_link' ).removeClass( "c_table_select" );
	jQuery( '#img_' + type + '_simple_link' ).addClass( "c_table_select" );

}
function view_all( type ) {
	form = document.adminForm;

	v_function_type = jQuery( 'input[name=function_type]:checked', 'form[name="adminForm"]' ).val();
	if ( type == 'asset0' ) {
		field = form.asset0_function_type.value;
		sel = form._asset0list;
	} else if ( type == 'asset1' ) {
		field = form.asset1_function_type.value;
		sel = form._asset1list;
	} else if ( type == 'asset2' ) {
		field = form.asset2_function_type.value;
		sel = form._asset2list;
	} else {
		return;
	}

	if ( field == '' ) {
		return;
	}

	jQuery( '#div_' + type + '_simple_table' ).hide();
	jQuery( '#div_' + type + '_advanced_table' ).show();
	jQuery( '#div_' + type + '_advanced_grid' ).hide();
	jQuery( '#' + type + '_search_grid_table' ).DataTable().clear().destroy();

	jQuery( '#img_' + type + '_simple_link,#img_' + type + '_advanced_link,#img_' + type + '_grid_link' ).removeClass( "c_table_select" );
	jQuery( '#img_' + type + '_advanced_link' ).addClass( "c_table_select" );

	jQuery.getJSON(
		base_url,
		{ type:'ajax', task:'ajax_elements_all', element:field, tmpl:'component', no_html:1 },
		function( data ) {
			i = 0;
			sel.options.length = 0;
			jQuery.each( data, function( key, val ) {
				sel.options[i++] = new Option( val.label, val.id );
			})
		}
	);

}

function view_all_grid( type ) {

	if ( ! jQuery( '#' + type + '_search_grid_table' ).length ) {
		return;
	}

	v_function_type = jQuery( 'input[name=function_type]:checked', 'form[name="adminForm"]' ).val();

	jQuery( '#div_' + type + '_simple_table' ).hide();
	jQuery( '#div_' + type + '_advanced_table' ).hide();
	if ( document.adminForm.elements['_' + type + 'list'] != undefined ) {
		document.adminForm.elements['_' + type + 'list'].options.length = 0;
	}
	jQuery( '#div_' + type + '_advanced_grid' ).show();

	jQuery( '#img_' + type + '_simple_link,#img_' + type + '_advanced_link,#img_' + type + '_grid_link' ).removeClass( "c_table_select" );
	jQuery( '#img_' + type + '_grid_link' ).addClass( "c_table_select" );

	if ( type.substr( 0, 5 ) == 'asset' ) {
		asset_type = document.adminForm.elements[type + '_function_type'].value;
	}

	jQuery( '#' + type + '_search_grid_table' ).DataTable({
		processing: true,
		serverSide: true,
		orderMulti: false,
		ajax: {
			url: base_url,
			data: {
				type: 'ajax',
				task: 'ajax_elements_datatables',
				category: asset_type
			}
		},
		columns: [
			{ "name": "id" },
			{ "name": "label" }
		],
		select: { style: "os" },
		dom: 'Bfrtip',
		buttons: [
			{
				text: str_add,
				action: function (e,datatable) {
					id = type + '_search_grid_table';
					if ( id.substr( 0, 5 ) == 'user_' ) {
						var parts = id.match( /^asset(\d+)\_/i );
						if ( parts ) {
							mytype = 'asset' + parts[1];
							type_index = parts[1];
						}
					}
					if ( mytype == undefined ) {
						return;
					}

					rowdata = datatable.rows( { selected: true } ).data().toArray();
					for ( i = 0; i < rowdata.length; i++ ) {

						document.adminForm.elements[mytype + '_id'].value = rowdata[i][0];
						document.adminForm.elements[mytype + '_name'].value = rowdata[i][1];

						if ( mytype.substr( 0, 5 ) == 'asset' ) {
							dd_itemselectf_v3( type_index );
						}
					}
				}
		}
		]
	});

	jQuery( '#' + type + '_search_grid_table tbody' ).on( 'dblclick', 'tr', function () {
		id = jQuery( this ).parent().parent().attr( 'id' );
		if ( id.substr( 0, 5 ) == 'user_' ) {
			var parts = id.match( /^asset(\d+)\_/i );
			if ( parts ) {
				mytype = 'asset' + parts[1];
				type_index = parts[1];
			}
		}

		if ( mytype == undefined ) {
			return;
		}

		if ( jQuery( this ).hasClass( 'selected' ) ) {
			jQuery( this ).removeClass( 'selected' );
		} else {
			jQuery( this ).find( 'td' ).each(function() {
				index = jQuery( this ).index();
				if ( index == 0 ) {
					document.adminForm.elements[mytype + '_id'].value = jQuery( this ).html();
				}
				if ( index == 1 ) {
					document.adminForm.elements[mytype + '_name'].value = jQuery( this ).html();
				}
			});

			if ( mytype.substr( 0, 5 ) == 'asset' ) {
				dd_itemselectf_v3( type_index );
			}

			jQuery( 'tr.selected' ).removeClass( 'selected' );
			jQuery( this ).addClass( 'selected' );
		}
	} );
}

function dd_searchg(type) {
	if ( type == 'asset0' ) {
		var input_text = 'asset0_search_txt';
		var searchDD = document.adminForm.elements['_asset0list'];
	} else if ( type == 'asset1' ) {
		var input_text = 'asset1_search_txt';
		var searchDD = document.adminForm.elements['_asset1list'];
	} else if ( type == 'asset2' ) {
		var input_text = 'asset2_search_txt';
		var searchDD = document.adminForm.elements['_asset2list'];
	} else {
		return;
	}

	//searchDD.multiple = false;
	var input = document.getElementById( input_text ).value.toLowerCase();
	if ( jQuery.trim( input ) == '' ) {
		searchDD.selectedIndex = -1;
		return;
	}

	searchDD.selectedIndex = -1;
	var output = searchDD.options;
	for ( var i = 0, len = output.length;i < len; i++ ) {
		if ( output[i].text.toLowerCase().indexOf( input ) == 0 ) {
			output[i].selected = true;
			break;
		}
	}
	//searchDD.multiple = true;
}

function dd_itemselectf_v3( type ) {

	form = document.adminForm;
	v_function_type = jQuery( 'input[name=function_type]:checked', 'form[name="adminForm"]' ).val();
	if ( type == 0 || type == 1 || type == 2 ) {
		id = form.elements['asset' + type + '_id'].value;
		name = form.elements['asset' + type + '_name'].value;
		asset_type = form.elements['asset' + type + '_function_type'].value;
		asset_mode = jQuery( "input[name='asset[" + type + "][rows][" + asset_type + "][mode]']:checked" , form ).val();
		value_list_id = 'asset[' + type + '][rows][' + asset_type + '][rows][' + id + '][asset_id]';
		value_list_name = 'asset[' + type + '][rows][' + asset_type + '][rows][' + id + '][asset_name]';
		tbl = 'tbl_assets' + type;
		if ( type == 0 ) {
			// set coupon to specific if new and assets are selected
			if ( v_function_type != 'shipping' && form.id.value == '' && form.elements[value_list_id] == undefined ) {
				form.discount_type.selectedIndex = 1;
			}
		}
	} else {
		return;
	}

	if ( jQuery.trim( id ) != '' ) {

		// do not add duplicates
		valueDD = form.elements[value_list_id];
		if ( valueDD != undefined ) {
			if ( valueDD.value != undefined && valueDD.value == id ) {
				return;
			} else {
				is_continue = false;
				for ( j = 0, len2 = valueDD.length; j < len2; j++ ) {
					if ( valueDD[j].value == id ) {
						is_continue = true;
						break;
					}
				}
				if ( is_continue ) {
					return;
				}
			}
		}

		jQuery( '#' + tbl + ' > tbody:last' ).append(
			'<tr id="' + tbl + '_tr' + id + '">\
				<td class="last" align="right">\
						<button type="button" onclick="deleterow(\'' + tbl + '_tr' + id + '\');return false;" >X</button>\
						<input type="hidden" name="asset' + type + 'listadded[]" value="' + id + '">\
						<input type="hidden" name="' + value_list_id + '" value="' + id + '">\
						<input type="hidden" name="' + value_list_name + '" value="' + name + '">\
				</td>\
				<td>' + id + '</td>\
				<td>' + str_assetlist[asset_type] + '</td>\
				<td>\
					<label class="awbtn active awbtn-success  aw-asset' + type + '-item-' + asset_type + '-row f_asset' + type + '_item_' + asset_type + '_include" style="' + ( asset_mode == '' || asset_mode == 'include' ? '' : 'display:none;' ) + '">' + str_include + '</label>\
					<label class="awbtn active awbtn-danger   aw-asset' + type + '-item-' + asset_type + '-row f_asset' + type + '_item_' + asset_type + '_exclude" style="' + ( asset_mode == 'exclude' ? '' : 'display:none;' ) + '">' + str_exclude + '</label>\
				</td>\
				<td>' + name + '</td>\
			</tr>'
		);

	}
}

function dd_itemselectg_v3( type ) {
	form = document.adminForm;
	v_function_type = jQuery( 'input[name=function_type]:checked', 'form[name="adminForm"]' ).val();
	if ( type == 0 || type == 1 || type == 2 ) {

		asset_type = form.elements['asset' + type + '_function_type'].value;
		asset_mode = jQuery( "input[name='asset[" + type + "][rows][" + asset_type + "][mode]']:checked" , form ).val();
		tbl = 'tbl_assets' + type;
		searchDD = form.elements['_asset' + type + 'list'];

	} else {
		return;
	}

	for ( var i = 0, len = searchDD.options.length; i < len; i++ ) {
		if ( searchDD.options[i].selected ) {
			id = searchDD.options[i].value;
			if ( jQuery.trim( id ) == '' ) {
				continue;
			}

			name = searchDD.options[i].innerHTML;
			value_list_id = 'asset[' + type + '][rows][' + asset_type + '][rows][' + id + '][asset_id]';
			value_list_name = 'asset[' + type + '][rows][' + asset_type + '][rows][' + id + '][asset_name]';

			// set coupon to specific if new and assets are selected
			if ( v_function_type != 'shipping' && form.elements[value_list_id] == undefined && type == 0 ) {
				form.discount_type.selectedIndex = 1;
			}

			// do not add duplicates
			valueDD = form.elements[value_list_id];
			if ( valueDD != undefined ) {
				if ( valueDD.value != undefined && valueDD.value == id ) {
					continue;
				} else {
					is_continue = false;
					for ( j = 0,len2 = valueDD.length; j < len2; j++ ) {
						if ( valueDD[j].value == id ) {
							is_continue = true;
							break;
						}
					}
					if ( is_continue ) {
						continue;
					}
				}
			}
			// add body
			jQuery( '#' + tbl + ' > tbody:last' ).append(
				'<tr id="' + tbl + '_tr' + id + '">\
					<td class="last" align="right">\
							<button type="button" onclick="deleterow(\'' + tbl + '_tr' + id + '\');return false;" >X</button>\
							<input type="hidden" name="asset' + type + 'listadded[]" value="' + id + '">\
							<input type="hidden" name="' + value_list_id + '" value="' + id + '">\
							<input type="hidden" name="' + value_list_name + '" value="' + name + '">\
					</td>\
					<td>' + id + '</td>\
					<td>' + str_assetlist[asset_type] + '</td>\
					<td>\
						<label class="awbtn active awbtn-success  aw-asset' + type + '-item-' + asset_type + '-row f_asset' + type + '_item_' + asset_type + '_include" style="' + ( asset_mode == '' || asset_mode == 'include' ? '' : 'display:none;' ) + '">' + str_include + '</label>\
						<label class="awbtn active awbtn-danger   aw-asset' + type + '-item-' + asset_type + '-row f_asset' + type + '_item_' + asset_type + '_exclude" style="' + ( asset_mode == 'exclude' ? '' : 'display:none;' ) + '">' + str_exclude + '</label>\
					</td>\
					<td>' + name + '</td>\
				</tr>'
			);
		}
	}
}

function deleterow( id ) {
	var tr = document.getElementById( id );
	tr.parentNode.removeChild( tr );
}

function moverow( id, direction ) {

	var tr = document.getElementById( id );
	var tbl = tr.parentNode;

	clickedRowIndex = 0;
	for ( i = 0; i < tbl.rows.length; i++ ) {
		if ( tbl.rows[i].id == id ) {
			clickedRowIndex = i;
			break;
		}
	}

	if ( direction == 'up' && clickedRowIndex <= 0 ) {
		return false;
	} else if ( direction == 'down' && clickedRowIndex == ( tbl.rows.length - 1 ) ) {
		return false;
	}

	if ( direction == 'up' ) {
		adjacentRowIndex = clickedRowIndex - 1;
	} else if ( direction == 'down' ) {
		adjacentRowIndex = clickedRowIndex + 1;
	} else {
		return;
	}

	clickedrow = tbl.getElementsByTagName( 'tr' )[clickedRowIndex];
	adjacentrow = tbl.getElementsByTagName( 'tr' )[adjacentRowIndex];

	clickedrow_clone = clickedrow.cloneNode( true );
	adjacentrow_clone = adjacentrow.cloneNode( true );

	adjacentrow = tbl.replaceChild( clickedrow_clone, adjacentrow );
	clickedrow = tbl.replaceChild( adjacentrow_clone, clickedrow );
}

function generate_code(estore) {
	jQuery.ajax({
		type: "POST",
		url: base_url,
		data: "type=ajax&task=ajax_generate_coupon_code&estore=" + estore,
		success: function( data ) {
			var form = document.adminForm;
			form.coupon_code.value = data;
		}
	});
}

function countrystatechange( elem, dest, ids ) {
	var opt = jQuery( elem ),
	optValues = opt.val() || [],
	byAjax = [];
	if ( typeof state_to_country === "undefined") {
		state_to_country = {};
	}

	if ( ! jQuery.isArray( optValues ) ) {
		optValues = jQuery.makeArray( optValues );
	}
	if ( typeof oldValues !== "undefined" ) {
		//remove if not in optValues
		jQuery.each( oldValues, function (key, oldValue ) {
			if ( ( jQuery.inArray( oldValue, optValues ) ) < 0 ) {
				jQuery( "#group" + oldValue ).remove();
			}
		});
	}
	//push in 'byAjax' values and do it in ajax
	jQuery.each( optValues, function( optkey, optValue ) {
		if ( opt.data( 'd' + optValue ) === undefined ) {
			byAjax.push( optValue );
		}
	});

	if ( byAjax.length > 0 ) {

		jQuery.ajax({
			dataType: "json",
			url: base_url,
			data: {
				term: params.term, // search term
				page: params.page,
				type: 'ajax',
				task: 'ajax_elements_all',
				element: 'countrystate',
				'country_id': byAjax
			},
			success: function( result ) {

				jQuery.each( result, function( key, value ) {
					opt.data( 'd' + key, objectLength( value ) > 0 ? value : 0 );
				});

				jQuery.each( optValues, function( dataKey, dataValue ) {
					var groupExist = jQuery( "#group" + dataValue + "" ).size();
					if ( ! groupExist ) {
						var datas = opt.data( 'd' + dataValue );

						if ( objectLength( datas ) > 0 ) {
							var label = opt.find( "option[value='" + dataValue + "']" ).text();
							var group = '<optgroup id="group' + dataValue + '" label="' + label + '">';
							jQuery.each( datas  , function( key, value) {
								if ( value ) {
									state_to_country[value.id] = dataValue;
									group += '<option value="' + value.id + '">' + value.label + '</option>';
								}
							});
							group += '</optgroup>';
							jQuery( dest ).append( group );
						}
					}
				});

				if ( typeof ids !== "undefined" ) {
					var states = ids.length ? ids.split( ',' ) : [] ;
					jQuery.each( states, function( k, id ) {
						jQuery( dest ).find( '[value=' + id + ']' ).attr( "selected", "selected" );
					});
				}
				jQuery( dest ).trigger( "liszt:updated" );
				jQuery( dest ).trigger( "change" );
			}
		});
	} else {
		jQuery.each( optValues, function( dataKey, dataValue ) {
			var groupExist = jQuery( "#group" + dataValue + "" ).size();
			if ( ! groupExist ) {
				var datas = opt.data( 'd' + dataValue );

				if ( objectLength( datas ) > 0 ) {
					var label = opt.find( "option[value='" + dataValue + "']" ).text();
					var group = '<optgroup id="group' + dataValue + '" label="' + label + '">';
					jQuery.each( datas  , function( key, value) {
						if ( value ) {
							group += '<option value="' + value.id + '">' + value.label + '</option>';
						}
					});
					group += '</optgroup>';
					jQuery( dest ).append( group );
				}
			}
		});
		states = jQuery( dest ).val() || [];
		jQuery( dest ).trigger( "liszt:updated" );
	}
	jQuery( dest ).select2();
	oldValues = optValues ;
}

function objectLength( obj ) {
	if ( obj !== null && typeof obj === 'object' ) {
		return jQuery.map( obj, function(n, i) {
			return i;
		}).length;
	}
	return 0;
}
