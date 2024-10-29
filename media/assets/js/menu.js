/**
 * AwoCoupon
 *
 * @package WordPress AwoCoupon
 * @copyright Copyright (C) Seyi Awofadeju - All rights reserved.
 * @Website : http://awodev.com
 **/

jQuery( document ).ready(function () {
	jQuery( "#awomenu .dropdown-toggle" ).dropdown();
	jQuery( "#awomenu ul.nav li.dropdown" ).hover(
		function() {
			jQuery( this ).find( ".dropdown-menu" ).stop( true, true ).show();
			jQuery( this ).addClass( "active" );
		},
		function() {
			jQuery( this ).find( ".dropdown-menu" ).stop( true, true ).hide();
			jQuery( this ).removeClass( "active" );
		}
	);
})
