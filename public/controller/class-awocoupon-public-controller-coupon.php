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

class AwoCoupon_Public_Controller_Coupon extends AwoCoupon_Library_Controller {

	public function __construct() {
		$this->model = AC()->helper->new_class( 'AwoCoupon_Public_Class_Coupon' );
	}

	public function show_default() {
		$this->render( 'public.view.coupon.list', array(
			'table_html' => $this->model->display_list(),
		));
	}

}

