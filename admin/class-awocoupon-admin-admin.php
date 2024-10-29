<?php
/**
 * AwoCoupon
 *
 * @package WordPress AwoCoupon
 * @copyright Copyright (C) Seyi Awofadeju - All rights reserved.
 * @Website : http://awodev.com
 **/

if ( ! defined( '_AWO_' ) ) {
	exit;
}

/**
 * Admin class
 */
class AwoCoupon_Admin_Admin {

	/**
	 * Displa admin
	 */
	public static function display() {
		AC()->helper->add_class( 'AwoCoupon_Admin_Menu' );
		$menu = new AwoCoupon_Admin_Menu();
		echo $menu->process();
		?>
		
		<script>
		var appsammyjs = null;
		var glb_str_err_valid = '<?php echo AC()->lang->__( 'enter a valid value' ); ?>';

		(function($) {
			ajax_url = '<?php echo AwoCoupon::instance()->ajax_url(); ?>';
			plugin_url = '<?php echo AwoCoupon::instance()->plugin_url(); ?>';
			
			appsammyjs = $.sammy('#awo-main', function() {
			
				this.get('#/', function(context) { gotoUrl('type=admin&view=dashboard',context); });
				this.get('#/dashboard', function(context) { gotoUrl('type=admin&view=dashboard',context); });
				this.get('#/about', function(context) { gotoUrl('type=admin&view=about',context); });
				
				this.get('#/config', function(context) { gotoUrl('type=admin&view=config',context); });
				this.post('#/config', function(context) { postUrl('type=admin&view=config',context); });

				this.get('#/coupon', function(context) { gotoUrl('type=admin&view=coupon',context); });
				this.get('#/coupon/edit', function(context) { gotoUrl('type=admin&view=coupon&layout=edit',context); });
				this.get('#/coupon/generate', function(context) { gotoUrl('type=admin&view=coupon&layout=generate',context); });
				this.post('#/coupon', function(context) { postUrl('type=admin&view=coupon',context); });
				this.post('#/coupon/edit', function(context) { postUrl('type=admin&view=coupon&layout=edit',context); });
				this.post('#/coupon/generate', function(context,x) { postUrl('type=admin&view=coupon&layout=generate',context); });
				
				this.get('#/couponauto', function(context) { gotoUrl('type=admin&view=couponauto',context); });
				this.get('#/couponauto/edit', function(context) { gotoUrl('type=admin&view=couponauto&layout=edit',context); });
				this.post('#/couponauto', function(context) { postUrl('type=admin&view=couponauto',context); });
				this.post('#/couponauto/edit', function(context) { postUrl('type=admin&view=couponauto&layout=edit',context); });
				
				this.get('#/history', function(context) { gotoUrl('type=admin&view=history',context); });
				this.get('#/history/edit', function(context) { gotoUrl('type=admin&view=history&layout=edit',context); });
				this.post('#/history', function(context) { postUrl('type=admin&view=history',context); });
				this.post('#/history/edit', function(context) { postUrl('type=admin&view=history&layout=edit',context); });
				
				this.get('#/import', function(context) { gotoUrl('type=admin&view=import',context); });
				this.post('#/import', function(context) { postUrlUpload('type=admin&view=import',context); });
			});
			
		
			$(function() {
				appsammyjs.run('#/');
			});
		
		})(jQuery);
		function ajaxCleanup(context) {
			
			// close menu items
			jQuery('ul.nav').find(".dropdown-menu").hide();
			jQuery('ul.nav').find("li.dropdown").removeClass('active');
			
			// empty the canvas and add waiting image
			jQuery(context.$element())
				.empty()
				.html('<div style="text-align:center;margin-top:20px;"><img id="waitingimg_parent" src="<?php echo AWOCOUPON_ASEET_URL; ?>/images/loading.gif" height="60" /></div>')
			;
			
			// highlight the menu
			updateAwoMenu();
		}
		function gotoUrl(url, context) {
			ajaxCleanup(context);
			params = context.params;
			params = Object.assign({}, params); // convert Sammy.object to normal object
			//parameters = jQuery.trim(jQuery.param(params));
			rand = Math.floor(Math.random() * 100000000);
			
			paramstring = '';
			for (var key in params) paramstring += '&'+key+'='+encodeURIComponent(params[key]);

			real_url = ajax_url+'&'+url+'&urlx='+encodeURIComponent(context.path)+paramstring+'&cache='+rand;
			//context.render(real_url, {}).appendTo(context.$element());
			jQuery.get(real_url, params, function(data) {
				jQuery(context.$element()).html(data);
			});
		}
		function postUrl(url, context) {
			ajaxCleanup(context);

			params = context.params;
			params = Object.assign({}, params); // convert Sammy.object to normal object
			parameters = '';
			x = context.path.split('?');
			if(x[1] != undefined) parameters = jQuery.trim(x[1]);
			rand = Math.floor(Math.random() * 100000000);

			real_url = ajax_url+'&'+url+'&urlx='+encodeURIComponent(context.path)+(parameters!='' ? '&parameters='+encodeURIComponent(parameters) : '')+'&cache='+rand;
			jQuery.post(real_url, params, function(data) {
				jQuery(context.$element()).html(data);
			});
		}
	
		function postUrlUpload(url, context) {
			// create formdata before destroying form
			var form = jQuery('#'+context.params.form_id)[0];
			var formData = new FormData(form);

			ajaxCleanup(context);

			parameters = '';
			x = context.path.split('?');
			if(x[1] != undefined) parameters = jQuery.trim(x[1]);
			rand = Math.floor(Math.random() * 100000000);

			real_url = ajax_url+'&'+url+'&urlx='+encodeURIComponent(context.path)+(parameters!='' ? '&parameters='+encodeURIComponent(parameters) : '')+'&cache='+rand;
			
			jQuery.ajax({
				url: real_url,
				type: 'POST',
				data: formData,
				async: false,
				cache: false,
				contentType: false,
				processData: false,
				success: function (data) {
					jQuery(context.$element()).html(data);
				}
			});

		}

		function updateAwoMenu() {
			url = window.location.href;
			jQuery('#awomenu').find('li').removeClass('active').removeClass('current');
			var $li = jQuery('#awomenu').find("a[href='"+url+"']").parent();
			if($li.length==0) {
				parts1 = url.split('#');
				if(parts1[1]!=undefined) {
					parts2 = parts1[1].split('?');
					url = parts1[0]+'#'+parts2[0];
					var $li = jQuery('#awomenu').find("a[href='"+url+"']").parent();
				}
			}
			if($li.length==0) {
				parts = url.split('#');
				if(parts[1]!=undefined) {
					last = parts[1].lastIndexOf('/');
					url = parts[0]+'#'+parts[1].substr(0,last);
					var $li = jQuery('#awomenu').find("a[href='"+url+"']").parent();
				}
			}
			if($li.length!=0) {
				var $parentli = $li.parent().parent();
				$li.addClass('current');
				$parentli.addClass('current');
			}
		}
		
		function refreshPage() { appsammyjs.runRoute('get', window.location.hash); }
		</script>
		<?php
		// Load editor files without loading editor.
		AC()->helper->get_editor( '', 'klsdkljskldjsd-klsdlkdsksjdk_klskdslkdjlskldj_' );
		?>
	  
		<div>
			<div id="awo-main"></div>
		</div>
		
		
	  
		<?php
	}
}

