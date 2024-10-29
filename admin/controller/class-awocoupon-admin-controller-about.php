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
class AwoCoupon_Admin_Controller_About extends AwoCoupon_Library_Controller {

	/**
	 * Constructor
	 **/
	public function __construct() {
	}

	/**
	 * Display page
	 **/
	public function show_default() {
		$this->render( 'admin.view.about.default' );
	}
}

