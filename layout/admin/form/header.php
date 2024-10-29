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

if ( isset( $data->form_action ) ) {
	$action = $data->form_action;
} else {
	$action = AC()->helper->get_request( 'urlx' );
	list( $part1, $action ) = explode( '#', $action );
}
?>

<form action="#<?php echo $action; ?>" method="post" id="adminForm" name="adminForm" enctype="multipart/form-data">
	<input type="hidden" name="form_id" value="adminForm" />
