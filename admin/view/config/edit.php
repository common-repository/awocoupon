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

echo AC()->helper->render_layout( 'admin.header' );
?>

<script>
var base_url = "<?php echo AC()->ajax_url(); ?>";

jQuery(document).ready(function() {
	
	//update_cron_url();
	
	hideOtherLanguage("<?php echo $data->default_language; ?>");
	
});


function error_messages_debug() {
	jQuery("input.error_message").each(function() {
		var data = jQuery(this).closest('td').parent().find('.key span').html();
		jQuery(this).val(data);
	});
}
function error_messages_clear() {
	jQuery("input.error_message").each(function() {
		jQuery(this).val('');
	});
}

function update_cron_url() {
	key = adminForm.elements['params[cron_key]'].value;
	document.getElementById('cronkey_in_url').innerHTML = key;
	
}

function resetTables() {
	form = document.adminForm2;
	form.task.value = 'configResetTables';
	form.submit();
}
</script>


<div class="wrap"><h1><?php echo AC()->lang->__( 'Configuration' ); ?></h1></div>

<?php echo AC()->helper->render_layout( 'admin.form.header' ); ?>






<div class="inside">

	<div class="tabs-wrap">
		<div class="submitpanel"><span>
			<button type="button" onclick="submitForm(this.form, 'apply');" class="button  button-large"><?php echo AC()->lang->__( 'Apply' ); ?></button>
			<button type="button" onclick="submitForm(this.form, 'save');" class="button button-primary button-large"><?php echo AC()->lang->__( 'Save' ); ?></button>
		</span><div class="clear"></div></div>


		<ul class="wc-tabs" style="">
			<li class=""><a href="#tab_div_general"><span><?php echo AC()->lang->__( 'General' ); ?></span></a></li>
			<li class=""><a href="#tab_div_multiplecoupon"><span><?php echo AC()->lang->__( 'Multiple Coupons' ); ?></span></a></li>
			<li class=""><a href="#tab_div_trigger"><span><?php echo AC()->lang->__( 'Triggers' ); ?></span></a></li>
			<li class=""><a href="#tab_div_errormsg"><span><?php echo AC()->lang->__( 'Coupon Code Error Description' ); ?></span></a></li>
			
		</ul>
			
		<div id="tab_div_general" class="panel">
			<table class="">
			<?php echo $this->display_yes_no( AC()->lang->__( 'Enable Store Coupons' ),'enable_store_coupon' ); ?>
			<?php echo $this->display_yes_no( AC()->lang->__( 'Case Sensitive Coupon Code' ),'is_case_sensitive' ); ?>
			<?php echo $this->display_yes_no( AC()->lang->__( 'Calculate the discount before tax' ) . ' (' . AC()->lang->__( 'Coupons' ) . ')','enable_coupon_discount_before_tax' ); ?>
			<?php echo $this->display_yes_no( AC()->lang->__( 'Enable zero value coupon' ),'enable_zero_value_coupon' ); ?>
			<?php echo $this->display_yes_no( AC()->lang->__( 'Enable negative value coupon' ),'enable_negative_value_coupon' ); ?>
			</table>

		</div>
		<div id="tab_div_multiplecoupon" class="panel">
			<table class="">
			<?php echo $this->display_yes_no( AC()->lang->__( 'Activate' ),'enable_multiple_coupon' ); ?>
			<tr><td class="key"><?php echo AC()->lang->__( 'All' ); ?> (<?php echo AC()->lang->__( 'Max' ); ?>)</td>
				<td nowrap><input type="text" size="4" name="params[multiple_coupon_max]" value="<?php echo AC()->param->get( 'multiple_coupon_max', '' ); ?>" > &nbsp;</td>
			</tr>
			<tr><td class="key"><?php echo AC()->lang->__( 'Automatic Discounts' ); ?> (<?php echo AC()->lang->__( 'Max' ); ?>)</td>
				<td nowrap><input type="text" size="4" name="params[multiple_coupon_max_auto]" value="<?php AC()->param->get( 'multiple_coupon_max_auto', '' ); ?>" > &nbsp;</td>
			</tr>
			<tr><td class="key"><?php echo AC()->lang->__( 'Coupons' ); ?> (<?php echo AC()->lang->__( 'Max' ); ?>)</td>
				<td nowrap><input type="text" size="4" name="params[multiple_coupon_max_coupon]" value="<?php echo AC()->param->get( 'multiple_coupon_max_coupon', '' ); ?>" > &nbsp;</td>
			</tr>
			<?php echo $this->display_yes_no( AC()->lang->__( 'Apply only one discount per product' ),'multiple_coupon_product_discount_limit' ); ?>
			</table>


		</div>
		<div id="tab_div_trigger" class="panel">
	
			<table class="admintable">
			<tr valign="top"><td class="key"><?php echo AC()->lang->__( 'Restore coupon\'s number of uses if order is not processed' ); ?></td>
				<td>
					<select id="paramsordercancel_order_status" name="params[ordercancel_order_status][]" multiple="" class="inputbox" size="7" style="width:100%;">
						<?php foreach ( $data->orderstatuses as $orderstatus ) { ?>
							<option value="<?php echo $orderstatus->order_status_code; ?>"  
								<?php
								if ( in_array( $orderstatus->order_status_code, AC()->param->get( 'ordercancel_order_status', array() ) ) ) {
									echo 'SELECTED';
								}
								?>
							><?php echo $orderstatus->order_status_name; ?></option>
						<?php } ?>
					</select>
					<div style="width:250px;"></div>
				</td>
			</tr>
			<tr><td class="key"><?php echo AC()->lang->__( 'Delete expired coupons x days after expiration' ); ?></td>
				<td><input type="text" size="4" name="params[delete_expired]" value="<?php echo AC()->param->get( 'delete_expired', '' ); ?>" ></td>
			</tr>
			</table>
		</div>
		<div id="tab_div_errormsg" class="panel">
			<table class="admintable">
			<tr><td align="right" colspan="2">
				<button type="button" onclick="error_messages_debug();return false;"><?php echo AC()->lang->__( 'Debug' ); ?></button>
				<button type="button" onclick="error_messages_clear();return false;"><?php echo AC()->lang->__( 'Clear' ); ?></button>
			</td></tr>
			<?php
			$lang_params = array(
				'class' => 'error_message',
			);
			?>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'No record, unpublished or expired' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errNoRecord', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'Minimum value not reached' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errMinVal', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'Minimum product quantity not reached' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errMinQty', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'Customer not logged in' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errUserLogin', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'Customer not on customer list' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errUserNotOnList', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'Customer not on shopper group list' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errUserGroupNotOnList', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'Per user: already used coupon max number of times' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errUserMaxUse', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'Total: already used coupon max number of times' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errTotalMaxUse', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( '(include) Product(s) not on product list' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errProductInclList', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( '(exclude) Product(s) on product list' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errProductExclList', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( '(include) Product(s) not on category list' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errCategoryInclList', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( '(exclude) Product(s) on category list' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errCategoryExclList', $data->language_data, $lang_params ); ?></td>
			</tr>
			<?php
			/*<tr><td class="key"><span><?php echo AC()->lang->__( '(include) Product(s) not on manufacturer list' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errManufacturerInclList', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( '(exclude) Product(s) on manufacturer list' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errManufacturerExclList', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( '(include) Product(s) not on vendor list' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errVendorInclList', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( '(exclude) Product(s) on vendor list' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errVendorExclList', $data->language_data, $lang_params ); ?></td>
			</tr>*/
			?>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'No shipping selected' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errShippingSelect', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'No valid shipping selected' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errShippingValid', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( '(include) Selected shipping not on shipping list' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errShippingInclList', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( '(exclude) Selected shipping on shipping list' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errShippingExclList', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( 'Coupon value definition, threshold not reached' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errProgressiveThreshold', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span><?php echo AC()->lang->__( '(exclude) Discounted Products' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errDiscountedExclude', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span>(<?php echo AC()->lang->__( 'Include' ); ?>) <?php echo AC()->lang->__( 'Country' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errCountryInclude', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span>(<?php echo AC()->lang->__( 'Exclude' ); ?>) <?php echo AC()->lang->__( 'Country' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errCountryExclude', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span>(<?php echo AC()->lang->__( 'Include' ); ?>) <?php echo AC()->lang->__( 'State' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errCountrystateInclude', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span>(<?php echo AC()->lang->__( 'Exclude' ); ?>) <?php echo AC()->lang->__( 'State' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errCountrystateExclude', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span>(<?php echo AC()->lang->__( 'Include' ); ?>) <?php echo AC()->lang->__( 'Payment Method' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errPaymentMethodInclude', $data->language_data, $lang_params ); ?></td>
			</tr>
			<tr><td class="key"><span>(<?php echo AC()->lang->__( 'Exclude' ); ?>) <?php echo AC()->lang->__( 'Payment Method' ); ?></span></td>
				<td><?php echo AC()->lang->write_fields( 'text', 'errPaymentMethodExclude', $data->language_data, $lang_params ); ?></td>
			</tr>
			

			</table>
		</div>
		
		<div class="clear"></div>
		

	</div>
	<div class="submitpanel"><span>
		<button type="button" onclick="submitForm(this.form, 'apply');" class="button  button-large"><?php echo AC()->lang->__( 'Apply' ); ?></button>
		<button type="button" onclick="submitForm(this.form, 'save');" class="button button-primary button-large"><?php echo AC()->lang->__( 'Save' ); ?></button>
	</span><div class="clear"></div></div>
	</div>


<input type="hidden" name="casesensitiveold" value="<?php echo $data->is_case_sensitive ? 1 : 0; ?>'" />
<?php
echo AC()->helper->render_layout( 'admin.form.footer' );
