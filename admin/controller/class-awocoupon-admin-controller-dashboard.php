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
 * Class
 */
class AwoCoupon_Admin_Controller_Dashboard extends AwoCoupon_Library_Controller {

	/**
	 * Constructor
	 **/
	public function __construct() {
		$this->model = AC()->helper->new_class( 'AwoCoupon_Admin_Class_Dashboard' );
	}

	/**
	 * Display list
	 **/
	public function show_default() {
		$this->render( 'admin.view.dashboard.default', array(
			'genstats' => $this->model->get_stats(),
		) );
	}

}
