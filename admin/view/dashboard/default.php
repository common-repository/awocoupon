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


<link type="text/css" rel="stylesheet" media="all" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" />

<style>
	div.icon { text-align: center; margin-right: 15px; float: left; margin-bottom: 15px; }
	#cpanel .large-icons span { display: block; text-align: center; }
	#cpanel .large-icons img { padding: 10px 0; margin: 0 auto; }
	#cpanel .large-icons div.icon a {background-color: white;background-position: -30px;display: block;float: left;height: 97px;width: 108px;color: #565656;vertical-align: middle;text-decoration: none;border: 1px solid #CCC;-webkit-border-radius: 5px;-moz-border-radius: 5px;border-radius: 5px;-webkit-transition-property: background-position, -webkit-border-bottom-left-radius, -webkit-box-shadow;-moz-transition-property: background-position, -moz-border-radius-bottomleft, -moz-box-shadow;-webkit-transition-duration: 0.8s;-moz-transition-duration: 0.8s;border-top-left-radius: 5px 5px;border-top-right-radius: 5px 5px;border-bottom-right-radius: 5px 5px;border-bottom-left-radius: 5px 5px;}

	table { width: 100%; }
	table  td { border-top: #ececec 1px solid; padding: 3px 0; white-space: nowrap; }
	table tr.first td { border-top: none; }
	td.b { padding-right: 6px; text-align: right; font-family: Georgia, "Times New Roman", "Bitstream Charter", Times, serif; font-size: 14px; }
	td.b a { font-size: 18px; }
	td.b a:hover { color: #d54e21; }
	.t { font-size: 12px; padding-right: 12px; padding-top: 6px; color: #777; }
	td.first, td.last { width: 1px; }
	.inactive { color: red; }
	.template { color: darkblue; }
	.trackbacks { color: black; }
	.waiting { color: orange;	}
	.approved { color: green; }
	ul.pro li {line-height:1.6em;}
	ul.pro span { font-size:1.05em; }
	/*ul.pro span span.h { font-weight:bold; color:#b45508;}
	ul.pro span.h a { text-decoration: none; }*/
	ul.pro span span.h { font-weight:bold; }
	ul.pro img {padding-right:5px;}
	ul.pro li i {color:#a1a1a1;}
</style>

<form action="index.php" method="post" id="adminForm" name="adminForm">
	<input type="hidden" name="option" value="com_awocoupon" />
	<input type="hidden" name="view" value="dashboard" />
	<input type="hidden" name="cid" value="" />
	<input type="hidden" name="cid2" value="" />
	<input type="hidden" name="task" value="" />
</form>
<div id="dash_generalstats">
<table cellspacing="0" cellpadding="0" border="0" width="100%">
	<tr>
		<td width="55%" valign="top">
			<div id="cpanel" class="panel postbox" style="padding:12px;">		
				<div class="large-icons">
					<div style="float:left;"><div class="icon"><a href="#/coupon/edit"><img src="<?php echo AWOCOUPON_ASEET_URL; ?>/images/icon-48-new.png" alt=""><span><?php echo AC()->lang->__( 'New Coupon' ); ?></span></a></div></div>
					<div style="float:left;"><div class="icon"><a href="#/coupon"><img src="<?php echo AWOCOUPON_ASEET_URL; ?>/images/coupons.png" alt=""><span><?php echo AC()->lang->__( 'Coupons' ); ?></span></a></div></div>
					<div style="float:left;"><div class="icon"><a href="#/history"><img src="<?php echo AWOCOUPON_ASEET_URL; ?>/images/icon-48-history.png" alt=""><span><?php echo AC()->lang->__( 'History of Uses' ); ?></span></a></div></div>
					<div style="float:left;"><div class="icon"><a href="#/config"><img src="<?php echo AWOCOUPON_ASEET_URL; ?>/images/icon-48-config.png" alt=""><span><?php echo AC()->lang->__( 'Configuration' ); ?></span></a></div></div>
				<?php
				/*
				<div style="float:left;"><div class="icon"><a href="#/giftcert"><img src="<?php echo AWOCOUPON_ASEET_URL; ?>/images/icon-48-giftcert.png" alt=""><span><?php echo AC()->lang->__( 'Gift Certificates' ); ?></span></a></div></div>
				<div style="float:left;"><div class="icon"><a href="#/profile"><img src="<?php echo AWOCOUPON_ASEET_URL; ?>/images/icon-48-profile.png" alt=""><span><?php echo AC()->lang->__( 'Email Templates' ); ?></span></a></div></div>
				<div style="float:left;"><div class="icon"><a href="#/report"><img src="<?php echo AWOCOUPON_ASEET_URL; ?>/images/icon-48-report.png" alt=""><span><?php echo AC()->lang->__( 'Reports' ); ?></span></a></div></div>
				*/
				?>
				<div style="clear:both;"></div>
				</div>
				<hr />
				<h3>Need more features? <a href="http://awodev.com/products/wordpress/awocoupon" target="_blank">Get the paid version</a>.
					<a href="http://awodev.com/products/wordpress/awocoupon"><img src="<?php echo AWOCOUPON_ASEET_URL; ?>/images/awoprologo.png" alt="" style="height:30px;vertical-align:middle;"></a>
				</h3>
				<div style="padding-left:10px;">
					<ul class="pro">
						<li><i aria-hidden="true" class="fa fa-fw fa-gift"></i>
							<span><span class="h">Gift certificates</span>: use a voucher until the value runs out</span>
						</li>
						<li><i aria-hidden="true" class="fa fa-fw fa-window-restore"></i>
							<span><span class="h"><a href="http://awodev.com/blog/buy-x-get-y-joomla-guide" target="_blank">Buy X Get Y</a></span></span> Coupons
						</li>
						<li><i aria-hidden="true" class="fa fa-fw fa-plus-square-o"></i>
							<span>Create <span class="h">combination coupons</span> which allow you to add multiple coupons into one</span>
						</li>
						<li><i aria-hidden="true" class="fa fa-fw fa-credit-card"></i>
							<span><span class="h"><a href="http://awodev.com/blog/awocoupon-gift-certificate-balance" target="_blank">Customer store credit</a></span>: use balance directly in cart</span>
						</li>
						<li><i aria-hidden="true" class="fa fa-fw fa-shopping-cart"></i>
							<span><span class="h"><a href="http://awodev.com/blog/sell-gift-certificates-online-your-virtuemart-store" target="_blank">Sell vouchers</a></span> in your store: customers purchase voucher and automatically receive email with valid code, email fully customizable</span>
						</li>
						<li><i aria-hidden="true" class="fa fa-fw fa-link"></i>
							<span><span class="h">Add to cart coupon links</span></span>
						</li>
						<li><i aria-hidden="true" class="fa fa-fw fa-line-chart"></i>
							<span><span class="h">Reports</span></span>
						</li>
						<li><i aria-hidden="true" class="fa fa-fw fa-envelope-o"></i>
							<span><span class="h">Send a voucher</span> through email to anyone</span>
						</li>
						<li>....
							<span>and more</span>
						</li>
					</ul>
				</div>
			</div>
		</td>
		<td width="45%" valign="top">
			<div class="panel postbox">
				<table>
				<thead><tr><th colspan="2"><?php echo AC()->lang->__( 'General Statistics' ); ?></th></tr></thead>
				<tr class="first"><td class="first b"><?php echo $data->genstats->total; ?></td><td class="t"><?php echo AC()->lang->__( 'Total Coupons' ); ?></td></tr>
				<tr><td class="first b"><?php echo $data->genstats->active; ?></td><td class=" t approved"><?php echo AC()->lang->__( 'Active Coupons' ); ?></td></tr>
				<tr><td class="first b"><?php echo $data->genstats->inactive; ?></td><td class=" t inactive"><?php echo AC()->lang->__( 'Inactive Coupons' ); ?></td></tr>
				<tr><td class="first b"><?php echo $data->genstats->templates; ?></td><td class=" t template"><?php echo AC()->lang->__( 'Template Coupons' ); ?></td></tr>
				</table>
			</div>

		</td>
	</tr>
</table>
</div>

