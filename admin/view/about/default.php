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
   
<div class="wrap"><h1><?php echo AC()->lang->__( 'About' ); ?></h1></div>

<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

<div style="background-color:#ffffff;">
	<div class="edit-panel">

		<div class="submitpanel"><span>&nbsp;</span><div class="clear"></div></div>
		<div class="inner">
			<fieldset class="aw-row">
				<table cellpadding="4" cellspacing="0" border="0" width="100%">
					<tr><td width="100%"><img src="<?php echo AWOCOUPON_ASEET_URL; ?>/images/logo.png" style="margin-left:10px;" /></td></tr>
					<tr><td>
							<blockquote>
								<p><?php echo AC()->lang->__( 'AwoCoupon is created by Seyi Awofadeju.' ); ?></p>
								<p><?php echo AC()->lang->__( 'Please visit <a href="http://awodev.com">http://awodev.com</a> to find out more about us.' ); ?></p>
								<p>&nbsp;</p>
							</blockquote>
						</td>
					</tr>
					<tr>
						<td>
							<div style="font-weight: 700;">
								<?php echo AC()->lang->__( 'Version' ) . ': ' . AWOCOUPON_VERSION; ?>
							</div>
						</td>
					</tr>
				</table>
			</fieldset>
		</div>
		<div class="submitpanel"><span>&nbsp;</span><div class="clear"></div></div>
		
	</div>
</div>
