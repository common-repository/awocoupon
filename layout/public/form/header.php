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

$action = AC()->helper->get_link();
$formid = 'awocouponForm' . mt_rand();
?>

<form action="<?php echo $action; ?>" method="post" id="<?php echo $formid; ?>" name="<?php echo $formid; ?>" >
	<input type="hidden" name="form_id" value="<?php echo $formid; ?>" />
